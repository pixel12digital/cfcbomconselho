<?php
// Habilitar exibição de erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Aumentar timeout para evitar 504
set_time_limit(60); // 60 segundos
ini_set('max_execution_time', 60);

// API para gerenciamento de instrutores
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Incluir arquivos na ordem correta
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

// Debug da sessão
error_log('Session ID: ' . (session_id() ?: 'Nenhuma'));
error_log('User ID: ' . ($_SESSION['user_id'] ?? 'Nenhum'));
error_log('User Type: ' . ($_SESSION['user_type'] ?? 'Nenhum'));
error_log('REQUEST_METHOD: ' . $_SERVER['REQUEST_METHOD']);
error_log('GET params: ' . json_encode($_GET));
error_log('POST data: ' . json_encode($_POST));

// Verificar se as funções estão disponíveis
if (!function_exists('isLoggedIn')) {
    error_log('ERRO: Função isLoggedIn não está disponível');
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno: Funções de autenticação não disponíveis']);
    exit;
}

if (!function_exists('hasPermission')) {
    error_log('ERRO: Função hasPermission não está disponível');
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno: Funções de autenticação não disponíveis']);
    exit;
}

// Verificar se o usuário está logado e tem permissão de admin
if (!isLoggedIn()) {
    error_log('Usuário não está logado');
    http_response_code(401);
    echo json_encode(['error' => 'Usuário não está logado']);
    exit;
}

if (!hasPermission('admin')) {
    error_log('Usuário não tem permissão de admin. User ID: ' . ($_SESSION['user_id'] ?? 'Nenhum'));
    http_response_code(403);
    echo json_encode(['error' => 'Permissão negada - Apenas administradores']);
    exit;
}

error_log('Usuário autenticado com sucesso. User ID: ' . $_SESSION['user_id']);

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Listar instrutores ou buscar instrutor específico
            if (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                error_log('Buscando instrutor ID: ' . $id);
                
                // Consulta simplificada para evitar timeout
                $instrutor = $db->fetch("
                    SELECT i.*, c.nome as cfc_nome 
                    FROM instrutores i 
                    LEFT JOIN cfcs c ON i.cfc_id = c.id 
                    WHERE i.id = ?
                ", [$id]);
                
                if ($instrutor) {
                    // Adicionar campos para compatibilidade com o frontend
                    $instrutor['nome'] = $instrutor['nome'] ?: 'N/A';
                    $instrutor['email'] = $instrutor['email'] ?: 'N/A';
                    $instrutor['cfc_nome'] = $instrutor['cfc_nome'] ?: 'N/A';
                    
                    error_log('Instrutor encontrado: ' . json_encode($instrutor));
                    echo json_encode(['success' => true, 'data' => $instrutor]);
                } else {
                    error_log('Instrutor não encontrado para ID: ' . $id);
                    http_response_code(404);
                    echo json_encode(['error' => 'Instrutor não encontrado']);
                }
            } else {
                // Listar todos os instrutores - consulta simplificada
                error_log('Listando todos os instrutores');
                $instrutores = $db->fetchAll("
                    SELECT i.*, c.nome as cfc_nome 
                    FROM instrutores i 
                    LEFT JOIN cfcs c ON i.cfc_id = c.id 
                    ORDER BY i.nome
                ");
                
                // Adicionar campos para compatibilidade com o frontend
                foreach ($instrutores as &$instrutor) {
                    $instrutor['nome'] = $instrutor['nome'] ?: 'N/A';
                    $instrutor['email'] = $instrutor['email'] ?: 'N/A';
                    $instrutor['cfc_nome'] = $instrutor['cfc_nome'] ?: 'N/A';
                }
                
                error_log('Instrutores encontrados: ' . count($instrutores));
                echo json_encode(['success' => true, 'data' => $instrutores]);
            }
            break;
            
        case 'POST':
            // Criar novo instrutor
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                $data = $_POST;
            }
            
            // Debug: Log dos dados recebidos
            error_log('Dados recebidos na API: ' . json_encode($data));
            
            // Validações - verificar campos obrigatórios
            if (empty($data['cfc_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'CFC é obrigatório']);
                exit;
            }
            
            if (empty($data['nome'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Nome é obrigatório']);
                exit;
            }
            
            if (empty($data['credencial'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Credencial é obrigatória']);
                exit;
            }
            
            // Verificar se credencial já existe
            $existingCredencial = $db->fetch("SELECT id FROM instrutores WHERE credencial = ?", [$data['credencial']]);
            if ($existingCredencial) {
                error_log('POST - Credencial já existe: ' . $data['credencial']);
                http_response_code(400);
                echo json_encode(['error' => 'Credencial já cadastrada para outro instrutor']);
                exit;
            }
            
            // Criar usuário se necessário
            $usuario_id = null;
            if (!empty($data['usuario_id'])) {
                $usuario_id = $data['usuario_id'];
            } elseif (!empty($data['senha'])) {
                // Criar novo usuário
                $usuarioData = [
                    'nome' => $data['nome'],
                    'email' => $data['email'],
                    'senha' => password_hash($data['senha'], PASSWORD_DEFAULT),
                    'tipo' => 'instrutor',
                    'ativo' => true,
                    'criado_em' => date('Y-m-d H:i:s')
                ];
                
                $usuario_id = $db->insert('usuarios', $usuarioData);
                if (!$usuario_id) {
                    error_log('Erro ao criar usuário');
                    http_response_code(500);
                    echo json_encode(['error' => 'Erro ao criar usuário']);
                    exit;
                }
            }
            
            // Criar instrutor
            $instrutorData = [
                'nome' => $data['nome'],
                'cpf' => $data['cpf'] ?? '',
                'cnh' => $data['cnh'] ?? '',
                'data_nascimento' => !empty($data['data_nascimento']) ? $data['data_nascimento'] : null,
                'email' => $data['email'] ?? '',
                'telefone' => $data['telefone'] ?? '',
                'endereco' => $data['endereco'] ?? '',
                'cidade' => $data['cidade'] ?? '',
                'uf' => $data['uf'] ?? '',
                'usuario_id' => $usuario_id,
                'cfc_id' => $data['cfc_id'],
                'credencial' => $data['credencial'],
                'categorias_json' => json_encode($data['categoria_habilitacao'] ?? []),
                'tipo_carga' => $data['tipo_carga'] ?? '',
                'validade_credencial' => !empty($data['validade_credencial']) ? $data['validade_credencial'] : null,
                'observacoes' => $data['observacoes'] ?? '',
                'dias_semana' => json_encode($data['dias_semana'] ?? []),
                'horario_inicio' => $data['horario_inicio'] ?? '',
                'horario_fim' => $data['horario_fim'] ?? '',
                'ativo' => isset($data['ativo']) ? (bool)$data['ativo'] : true,
                'criado_em' => date('Y-m-d H:i:s')
            ];
            
            $instrutor_id = $db->insert('instrutores', $instrutorData);
            
            if ($instrutor_id) {
                echo json_encode(['success' => true, 'message' => 'Instrutor criado com sucesso', 'id' => $instrutor_id]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao criar instrutor']);
            }
            break;
            
        case 'PUT':
            // Atualizar instrutor
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                $data = $_POST;
            }
            
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID do instrutor é obrigatório']);
                exit;
            }
            
            $id = (int)$_GET['id'];
            
            // Verificar se instrutor existe
            $existingInstrutor = $db->fetch("SELECT id FROM instrutores WHERE id = ?", [$id]);
            if (!$existingInstrutor) {
                http_response_code(404);
                echo json_encode(['error' => 'Instrutor não encontrado']);
                exit;
            }
            
            // Preparar dados para atualização
            $updateData = [];
            
            if (isset($data['nome'])) $updateData['nome'] = $data['nome'];
            if (isset($data['cpf'])) $updateData['cpf'] = $data['cpf'];
            if (isset($data['cnh'])) $updateData['cnh'] = $data['cnh'];
            if (isset($data['data_nascimento'])) $updateData['data_nascimento'] = $data['data_nascimento'];
            if (isset($data['email'])) $updateData['email'] = $data['email'];
            if (isset($data['telefone'])) $updateData['telefone'] = $data['telefone'];
            if (isset($data['endereco'])) $updateData['endereco'] = $data['endereco'];
            if (isset($data['cidade'])) $updateData['cidade'] = $data['cidade'];
            if (isset($data['uf'])) $updateData['uf'] = $data['uf'];
            if (isset($data['cfc_id'])) $updateData['cfc_id'] = $data['cfc_id'];
            if (isset($data['credencial'])) $updateData['credencial'] = $data['credencial'];
            if (isset($data['categoria_habilitacao'])) $updateData['categorias_json'] = json_encode($data['categoria_habilitacao']);
            if (isset($data['tipo_carga'])) $updateData['tipo_carga'] = $data['tipo_carga'];
            if (isset($data['validade_credencial'])) $updateData['validade_credencial'] = $data['validade_credencial'];
            if (isset($data['observacoes'])) $updateData['observacoes'] = $data['observacoes'];
            if (isset($data['dias_semana'])) $updateData['dias_semana'] = json_encode($data['dias_semana']);
            if (isset($data['horario_inicio'])) $updateData['horario_inicio'] = $data['horario_inicio'];
            if (isset($data['horario_fim'])) $updateData['horario_fim'] = $data['horario_fim'];
            if (isset($data['ativo'])) $updateData['ativo'] = (bool)$data['ativo'];
            
            if (!empty($updateData)) {
                $updateData['atualizado_em'] = date('Y-m-d H:i:s');
                $success = $db->update('instrutores', $updateData, ['id' => $id]);
                
                if ($success) {
                    echo json_encode(['success' => true, 'message' => 'Instrutor atualizado com sucesso']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Erro ao atualizar instrutor']);
                }
            } else {
                echo json_encode(['success' => true, 'message' => 'Nenhuma alteração realizada']);
            }
            break;
            
        case 'DELETE':
            // Excluir instrutor
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID do instrutor é obrigatório']);
                exit;
            }
            
            $id = (int)$_GET['id'];
            
            // Verificar se instrutor existe
            $existingInstrutor = $db->fetch("SELECT id FROM instrutores WHERE id = ?", [$id]);
            if (!$existingInstrutor) {
                http_response_code(404);
                echo json_encode(['error' => 'Instrutor não encontrado']);
                exit;
            }
            
            $success = $db->delete('instrutores', ['id' => $id]);
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Instrutor excluído com sucesso']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao excluir instrutor']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
            break;
    }
    
} catch (Exception $e) {
    error_log('Erro na API de instrutores: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor: ' . $e->getMessage()]);
}
