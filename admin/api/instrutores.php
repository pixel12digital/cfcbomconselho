<?php
// Habilitar exibição de erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// API para gerenciamento de instrutores
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');  // Especificar origem específica
header('Access-Control-Allow-Credentials: true');  // Permitir credenciais
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Incluir arquivos na ordem correta
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/CredentialManager.php';

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

// Verificar se o usuário está logado e tem permissão de admin ou secretaria
if (!isLoggedIn()) {
    error_log('Usuário não está logado');
    http_response_code(401);
    echo json_encode(['error' => 'Usuário não está logado']);
    exit;
}

if (!canManageUsers()) {
    $currentUser = getCurrentUser();
    error_log('Usuário não tem permissão para gerenciar instrutores: ' . ($currentUser['tipo'] ?? 'desconhecido'));
    http_response_code(403);
    echo json_encode(['error' => 'Permissão negada - Apenas administradores e atendentes']);
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
                // Listar todos os instrutores
                $instrutores = $db->fetchAll("
                    SELECT i.*, u.nome as nome_usuario, u.email as email_usuario, c.nome as cfc_nome 
                    FROM instrutores i 
                    LEFT JOIN usuarios u ON i.usuario_id = u.id 
                    LEFT JOIN cfcs c ON i.cfc_id = c.id 
                    ORDER BY i.nome
                ");
                
                // Adicionar campos para compatibilidade com o frontend
                foreach ($instrutores as &$instrutor) {
                    // Usar o nome do instrutor (i.nome) como principal, fallback para nome_usuario se necessário
                    $instrutor['nome'] = $instrutor['nome'] ?: ($instrutor['nome_usuario'] ?: 'N/A');
                    $instrutor['email'] = $instrutor['email'] ?: ($instrutor['email_usuario'] ?: 'N/A');
                    $instrutor['cfc_nome'] = $instrutor['cfc_nome'] ?: 'N/A';
                }
                
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
            error_log('Campo usuario_id: ' . ($data['usuario_id'] ?? 'VAZIO'));
            error_log('Campo cfc_id: ' . ($data['cfc_id'] ?? 'VAZIO'));
            error_log('Campo nome: ' . ($data['nome'] ?? 'VAZIO'));
            error_log('Campo credencial: ' . ($data['credencial'] ?? 'VAZIO'));
            error_log('Campo id: ' . ($data['id'] ?? 'VAZIO'));
            
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
            
            // Verificar se credencial já existe (única validação necessária)
            $existingCredencial = $db->fetch("SELECT id FROM instrutores WHERE credencial = ?", [$data['credencial']]);
            if ($existingCredencial) {
                error_log('POST - Credencial já existe: ' . $data['credencial']);
                http_response_code(400);
                echo json_encode(['error' => 'Credencial já cadastrada para outro instrutor']);
                exit;
            }
            
            // Verificar se CFC existe
            $existingCFC = $db->fetch("SELECT id FROM cfcs WHERE id = ?", [$data['cfc_id']]);
            if (!$existingCFC) {
                error_log('POST - CFC não encontrado: ' . $data['cfc_id']);
                http_response_code(400);
                echo json_encode(['error' => 'CFC não encontrado']);
                exit;
            }
            error_log('POST - CFC encontrado: ' . $data['cfc_id']);
            
            // Remover validação de usuário - não é mais necessária
            
            // Iniciar transação
            $db->beginTransaction();
            
            try {
                // Verificar se usuário foi selecionado (opcional agora)
                $usuario_id = $data['usuario_id'] ?? null;
                
                // Se usuário foi fornecido, verificar se existe
                if ($usuario_id) {
                    $existingUser = $db->fetch("SELECT id FROM usuarios WHERE id = ?", [$usuario_id]);
                    if (!$existingUser) {
                        error_log('POST - Usuário não encontrado: ' . $usuario_id);
                        throw new Exception('Usuário não encontrado');
                    }
                    error_log('POST - Usuário encontrado: ' . $usuario_id);
                }
                
                // Criar instrutor com TODOS os campos
                $instrutorData = [
                    'nome' => $data['nome'] ?? '',
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
                    'credencial' => $data['credencial'] ?? '',
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
                
                // Debug: Log dos dados que serão inseridos
                error_log('Dados do instrutor para inserção: ' . json_encode($instrutorData));
                error_log('Categoria habilitação: ' . json_encode($data['categoria_habilitacao'] ?? 'VAZIO'));
                error_log('Dias da semana: ' . json_encode($data['dias_semana'] ?? 'VAZIO'));
                
                $instrutor_id = $db->insert('instrutores', $instrutorData);
                
                if (!$instrutor_id) {
                    error_log('Erro na inserção do instrutor. Último erro SQL: ' . $db->getLastError());
                    throw new Exception('Erro na execução da query: ' . $db->getLastError());
                }
                
                // Criar credenciais automáticas para o instrutor se não foi fornecido usuário_id
                if (!$usuario_id) {
                    if (LOG_ENABLED) {
                        error_log('[API Instrutores] Criando credenciais automáticas para instrutor ID: ' . $instrutor_id);
                    }
                    
                    $credentials = CredentialManager::createEmployeeCredentials([
                        'instrutor_id' => $instrutor_id,
                        'nome' => $instrutorData['nome'],
                        'email' => $instrutorData['email'],
                        'tipo' => 'instrutor'
                    ]);
                    
                    if ($credentials['success']) {
                        if (LOG_ENABLED) {
                            error_log('[API Instrutores] Credenciais criadas com sucesso para instrutor ID: ' . $instrutor_id);
                        }
                        
                        // Atualizar o instrutor com o usuario_id criado
                        $db->update('instrutores', ['usuario_id' => $credentials['usuario_id']], ['id' => $instrutor_id]);
                        
                        // Enviar credenciais por email (simulado)
                        CredentialManager::sendCredentials(
                            $credentials['email'], 
                            $credentials['senha_temporaria'], 
                            'instrutor'
                        );
                    } else {
                        if (LOG_ENABLED) {
                            error_log('[API Instrutores] Erro ao criar credenciais: ' . $credentials['message']);
                        }
                    }
                }
                
                $db->commit();
                
                // Buscar instrutor criado
                $instrutor = $db->fetch("
                    SELECT i.*, u.nome as nome_usuario, u.email as email_usuario, c.nome as cfc_nome 
                    FROM instrutores i 
                    LEFT JOIN usuarios u ON i.usuario_id = u.id 
                    LEFT JOIN cfcs c ON i.cfc_id = c.id 
                    WHERE i.id = ?
                ", [$instrutor_id]);
                
                // Adicionar campos para compatibilidade com o frontend
                $instrutor['nome'] = $instrutor['nome'] ?: ($instrutor['nome_usuario'] ?: 'N/A');
                $instrutor['email'] = $instrutor['email'] ?: ($instrutor['email_usuario'] ?: 'N/A');
                $instrutor['cfc_nome'] = $instrutor['cfc_nome'] ?: 'N/A';
                
                echo json_encode(['success' => true, 'message' => 'Instrutor criado com sucesso', 'data' => $instrutor]);
                
            } catch (Exception $e) {
                $db->rollback();
                error_log('Erro na criação do instrutor: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao criar instrutor: ' . $e->getMessage()]);
                exit;
            }
            break;
            
        case 'PUT':
            // Atualizar instrutor
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                parse_str(file_get_contents('php://input'), $data);
            }
            
            // Debug: Log dos dados recebidos
            error_log('PUT - Dados recebidos na API: ' . json_encode($data));
            
            if (empty($data['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID do instrutor é obrigatório']);
                exit;
            }
            
            $id = (int)$data['id'];
            
            // Verificar se instrutor existe
            $existingInstrutor = $db->fetch("SELECT id, usuario_id, cfc_id FROM instrutores WHERE id = ?", [$id]);
            if (!$existingInstrutor) {
                http_response_code(404);
                echo json_encode(['error' => 'Instrutor não encontrado']);
                exit;
            }
            
            // Iniciar transação
            $db->beginTransaction();
            
            try {
                // Atualizar dados do usuário
                $updateUserData = [];
                if (isset($data['nome'])) $updateUserData['nome'] = $data['nome'];
                if (isset($data['email'])) $updateUserData['email'] = $data['email'];
                if (isset($data['cpf'])) $updateUserData['cpf'] = $data['cpf'];
                if (isset($data['telefone'])) $updateUserData['telefone'] = $data['telefone'];
                if (isset($data['senha']) && !empty($data['senha'])) {
                    $updateUserData['senha'] = password_hash($data['senha'], PASSWORD_DEFAULT);
                }
                if (isset($data['ativo'])) $updateUserData['ativo'] = (bool)$data['ativo'];
                
                if (!empty($updateUserData)) {
                    $updateUserData['atualizado_em'] = date('Y-m-d H:i:s');
                    $result = $db->update('usuarios', $updateUserData, 'id = ?', [$existingInstrutor['usuario_id']]);
                    if (!$result) {
                        throw new Exception('Erro ao atualizar usuário');
                    }
                }
                
                // Atualizar dados do instrutor com TODOS os campos
                $updateInstrutorData = [];
                if (isset($data['nome'])) $updateInstrutorData['nome'] = $data['nome'];
                if (isset($data['cpf'])) $updateInstrutorData['cpf'] = $data['cpf'];
                if (isset($data['cnh'])) $updateInstrutorData['cnh'] = $data['cnh'];
                if (isset($data['data_nascimento'])) $updateInstrutorData['data_nascimento'] = !empty($data['data_nascimento']) ? $data['data_nascimento'] : null;
                if (isset($data['email'])) $updateInstrutorData['email'] = $data['email'];
                if (isset($data['telefone'])) $updateInstrutorData['telefone'] = $data['telefone'];
                if (isset($data['endereco'])) $updateInstrutorData['endereco'] = $data['endereco'];
                if (isset($data['cidade'])) $updateInstrutorData['cidade'] = $data['cidade'];
                if (isset($data['uf'])) $updateInstrutorData['uf'] = $data['uf'];
                if (isset($data['cfc_id'])) $updateInstrutorData['cfc_id'] = $data['cfc_id'];
                if (isset($data['credencial'])) $updateInstrutorData['credencial'] = $data['credencial'];
                if (isset($data['categoria_habilitacao'])) $updateInstrutorData['categorias_json'] = json_encode($data['categoria_habilitacao']);
                if (isset($data['tipo_carga'])) $updateInstrutorData['tipo_carga'] = $data['tipo_carga'];
                if (isset($data['validade_credencial'])) $updateInstrutorData['validade_credencial'] = !empty($data['validade_credencial']) ? $data['validade_credencial'] : null;
                if (isset($data['observacoes'])) $updateInstrutorData['observacoes'] = $data['observacoes'];
                if (isset($data['dias_semana'])) $updateInstrutorData['dias_semana'] = json_encode($data['dias_semana']);
                if (isset($data['horario_inicio'])) $updateInstrutorData['horario_inicio'] = $data['horario_inicio'];
                if (isset($data['horario_fim'])) $updateInstrutorData['horario_fim'] = $data['horario_fim'];
                if (isset($data['ativo'])) $updateInstrutorData['ativo'] = (bool)$data['ativo'];
                
                // Debug: Log dos dados que serão atualizados
                error_log('PUT - Dados do instrutor para atualização: ' . json_encode($updateInstrutorData));
                error_log('PUT - Categorias recebidas: ' . (isset($data['categorias']) ? json_encode($data['categorias']) : 'NÃO DEFINIDO'));
                error_log('PUT - Dias da semana recebidos: ' . (isset($data['dias_semana']) ? json_encode($data['dias_semana']) : 'NÃO DEFINIDO'));
                
                if (!empty($updateInstrutorData)) {
                    $updateInstrutorData['updated_at'] = date('Y-m-d H:i:s');
                    $result = $db->update('instrutores', $updateInstrutorData, 'id = ?', [$id]);
                    if (!$result) {
                        throw new Exception('Erro ao atualizar instrutor');
                    }
                }
                
                $db->commit();
                
                // Buscar instrutor atualizado
                $instrutor = $db->fetch("
                    SELECT i.*, u.nome as nome_usuario, u.email, c.nome as cfc_nome 
                    FROM instrutores i 
                    LEFT JOIN usuarios u ON i.usuario_id = u.id 
                    LEFT JOIN cfcs c ON i.cfc_id = c.id 
                    WHERE i.id = ?
                ", [$id]);
                
                // Adicionar campos para compatibilidade com o frontend
                $instrutor['nome'] = $instrutor['nome_usuario'];
                $instrutor['cfc_nome'] = $instrutor['cfc_nome'];
                
                echo json_encode(['success' => true, 'message' => 'Instrutor atualizado com sucesso', 'data' => $instrutor]);
                
            } catch (Exception $e) {
                $db->rollback();
                throw $e;
            }
            break;
            
        case 'DELETE':
            // Excluir instrutor
            if (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                
                // Log para debug
                error_log("Tentando excluir instrutor ID: $id");
                
                // Verificar se instrutor existe
                $existingInstrutor = $db->fetch("SELECT id, usuario_id FROM instrutores WHERE id = ?", [$id]);
                if (!$existingInstrutor) {
                    error_log("Instrutor ID $id não encontrado");
                    http_response_code(404);
                    echo json_encode(['error' => 'Instrutor não encontrado']);
                    exit;
                }
                
                error_log("Instrutor encontrado: " . json_encode($existingInstrutor));
                
                // Iniciar transação
                $db->beginTransaction();
                
                try {
                    // Excluir instrutor
                    $result = $db->delete('instrutores', 'id = ?', [$id]);
                    if (!$result) {
                        throw new Exception('Erro ao excluir instrutor');
                    }
                    
                    error_log("Instrutor ID $id excluído com sucesso");
                    
                    // SOFT DELETE: Marcar usuário como inativo em vez de excluí-lo
                    $result = $db->update('usuarios', [
                        'ativo' => false,
                        'atualizado_em' => date('Y-m-d H:i:s')
                    ], 'id = ?', [$existingInstrutor['usuario_id']]);
                    
                    if (!$result) {
                        error_log("AVISO: Não foi possível marcar usuário como inativo");
                        // Não falhar se não conseguir marcar como inativo
                    }
                    
                    error_log("Usuário ID {$existingInstrutor['usuario_id']} marcado como INATIVO (soft delete)");
                    
                    $db->commit();
                    echo json_encode(['success' => true, 'message' => 'Instrutor excluído com sucesso']);
                    
                } catch (Exception $e) {
                    error_log("Erro na transação: " . $e->getMessage());
                    $db->rollback();
                    throw $e;
                }
            } else {
                error_log("DELETE sem ID fornecido");
                http_response_code(400);
                echo json_encode(['error' => 'ID do instrutor é obrigatório']);
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
    
    // Garantir que a resposta seja JSON válido
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
} catch (Error $e) {
    error_log('Erro fatal na API de instrutores: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    // Garantir que a resposta seja JSON válido
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro fatal do servidor: ' . $e->getMessage()
    ]);
}
?>
