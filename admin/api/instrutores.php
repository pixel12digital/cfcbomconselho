<?php
// Habilitar exibição de erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// API para gerenciamento de instrutores
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

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
                $instrutor = $db->fetch("
                    SELECT i.*, u.nome as nome_usuario, u.email, c.nome as cfc_nome 
                    FROM instrutores i 
                    LEFT JOIN usuarios u ON i.usuario_id = u.id 
                    LEFT JOIN cfcs c ON i.cfc_id = c.id 
                    WHERE i.id = ?
                ", [$id]);
                
                if ($instrutor) {
                    // Adicionar campos para compatibilidade com o frontend
                    $instrutor['nome'] = $instrutor['nome_usuario'];
                    $instrutor['cfc_nome'] = $instrutor['cfc_nome'];
                    echo json_encode(['success' => true, 'data' => $instrutor]);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Instrutor não encontrado']);
                }
            } else {
                // Listar todos os instrutores
                $instrutores = $db->fetchAll("
                    SELECT i.*, u.nome as nome_usuario, u.email, c.nome as cfc_nome 
                    FROM instrutores i 
                    LEFT JOIN usuarios u ON i.usuario_id = u.id 
                    LEFT JOIN cfcs c ON i.cfc_id = c.id 
                    ORDER BY u.nome
                ");
                
                // Adicionar campos para compatibilidade com o frontend
                foreach ($instrutores as &$instrutor) {
                    $instrutor['nome'] = $instrutor['nome_usuario'];
                    $instrutor['cfc_nome'] = $instrutor['cfc_nome'];
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
            
            // Validações - verificar se é usuário novo ou existente
            if (empty($data['cfc_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'CFC é obrigatório']);
                exit;
            }
            
            // Se não tiver usuario_id, precisa criar usuário
            if (empty($data['usuario_id'])) {
                if (empty($data['nome']) || empty($data['email']) || empty($data['senha'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Para novo usuário: Nome, E-mail e Senha são obrigatórios']);
                    exit;
                }
            }
            
            // Verificar se CFC existe
            $existingCFC = $db->fetch("SELECT id FROM cfcs WHERE id = ?", [$data['cfc_id']]);
            if (!$existingCFC) {
                http_response_code(400);
                echo json_encode(['error' => 'CFC não encontrado']);
                exit;
            }
            
            // Iniciar transação
            $db->beginTransaction();
            
            try {
                // Verificar se é usuário novo ou existente
                if (empty($data['usuario_id'])) {
                    // Criar novo usuário
                    if (empty($data['nome']) || empty($data['email']) || empty($data['senha'])) {
                        throw new Exception('Para novo usuário: Nome, E-mail e Senha são obrigatórios');
                    }
                    
                    // Verificar se email já existe
                    $existingUser = $db->fetch("SELECT id FROM usuarios WHERE email = ?", [$data['email']]);
                    if ($existingUser) {
                        throw new Exception('E-mail já cadastrado');
                    }
                    
                    // Hash da senha
                    $senha_hash = password_hash($data['senha'], PASSWORD_DEFAULT);
                    
                    // Criar usuário
                    $usuario_id = $db->insert('usuarios', [
                        'nome' => $data['nome'],
                        'email' => $data['email'],
                        'senha' => $senha_hash,
                        'tipo' => 'instrutor',
                        'ativo' => isset($data['ativo']) ? (bool)$data['ativo'] : true,
                        'criado_em' => date('Y-m-d H:i:s')
                    ]);
                } else {
                    // Usar usuário existente
                    $usuario_id = $data['usuario_id'];
                    
                    // Verificar se usuário existe
                    $existingUser = $db->fetch("SELECT id FROM usuarios WHERE id = ?", [$usuario_id]);
                    if (!$existingUser) {
                        throw new Exception('Usuário não encontrado');
                    }
                }
                
                if (!$usuario_id) {
                    throw new Exception('Erro ao criar usuário');
                }
                
                // Criar instrutor com todos os campos
                $instrutorData = [
                    'usuario_id' => $usuario_id,
                    'cfc_id' => $data['cfc_id'],
                    'credencial' => $data['credencial'] ?? '',
                    'categoria_habilitacao' => $data['categoria_habilitacao'] ?? '',
                    'ativo' => isset($data['ativo']) ? (bool)$data['ativo'] : true,
                    'criado_em' => date('Y-m-d H:i:s')
                ];
                
                // Adicionar campos opcionais se existirem
                if (!empty($data['telefone'])) $instrutorData['telefone'] = $data['telefone'];
                if (!empty($data['endereco'])) $instrutorData['endereco'] = $data['endereco'];
                if (!empty($data['cidade'])) $instrutorData['cidade'] = $data['cidade'];
                if (!empty($data['uf'])) $instrutorData['uf'] = $data['uf'];
                if (!empty($data['cpf'])) $instrutorData['cpf'] = $data['cpf'];
                if (!empty($data['cnh'])) $instrutorData['cnh'] = $data['cnh'];
                if (!empty($data['data_nascimento'])) $instrutorData['data_nascimento'] = $data['data_nascimento'];
                
                $instrutor_id = $db->insert('instrutores', $instrutorData);
                
                if (!$instrutor_id) {
                    throw new Exception('Erro ao criar instrutor');
                }
                
                $db->commit();
                
                // Buscar instrutor criado
                $instrutor = $db->fetch("
                    SELECT i.*, u.nome as nome_usuario, u.email, c.nome as cfc_nome 
                    FROM instrutores i 
                    LEFT JOIN usuarios u ON i.usuario_id = u.id 
                    LEFT JOIN cfcs c ON i.cfc_id = c.id 
                    WHERE i.id = ?
                ", [$instrutor_id]);
                
                // Adicionar campos para compatibilidade com o frontend
                $instrutor['nome'] = $instrutor['nome_usuario'];
                $instrutor['cfc_nome'] = $instrutor['cfc_nome'];
                
                echo json_encode(['success' => true, 'message' => 'Instrutor criado com sucesso', 'data' => $instrutor]);
                
            } catch (Exception $e) {
                $db->rollback();
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
                if (!empty($data['nome'])) $updateUserData['nome'] = $data['nome'];
                if (!empty($data['email'])) $updateUserData['email'] = $data['email'];
                if (!empty($data['senha'])) {
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
                
                // Atualizar dados do instrutor
                $updateInstrutorData = [];
                if (!empty($data['cfc_id'])) $updateInstrutorData['cfc_id'] = $data['cfc_id'];
                if (!empty($data['credencial'])) $updateInstrutorData['credencial'] = $data['credencial'];
                if (!empty($data['categoria_habilitacao'])) $updateInstrutorData['categoria_habilitacao'] = $data['categoria_habilitacao'];
                if (!empty($data['telefone'])) $updateInstrutorData['telefone'] = $data['telefone'];
                if (!empty($data['endereco'])) $updateInstrutorData['endereco'] = $data['endereco'];
                if (!empty($data['cidade'])) $updateInstrutorData['cidade'] = $data['cidade'];
                if (!empty($data['uf'])) $updateInstrutorData['uf'] = $data['uf'];
                if (!empty($data['cpf'])) $updateInstrutorData['cpf'] = $data['cpf'];
                if (!empty($data['cnh'])) $updateInstrutorData['cnh'] = $data['cnh'];
                if (!empty($data['data_nascimento'])) $updateInstrutorData['data_nascimento'] = $data['data_nascimento'];
                if (isset($data['ativo'])) $updateInstrutorData['ativo'] = (bool)$data['ativo'];
                
                if (!empty($updateInstrutorData)) {
                    $updateInstrutorData['atualizado_em'] = date('Y-m-d H:i:s');
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
                    
                    // Excluir usuário
                    $result = $db->delete('usuarios', 'id = ?', [$existingInstrutor['usuario_id']]);
                    if (!$result) {
                        throw new Exception('Erro ao excluir usuário');
                    }
                    
                    error_log("Usuário ID {$existingInstrutor['usuario_id']} excluído com sucesso");
                    
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
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno: ' . $e->getMessage()]);
    
    if (LOG_ENABLED) {
        error_log('Erro na API de instrutores: ' . $e->getMessage());
    }
}
?>
