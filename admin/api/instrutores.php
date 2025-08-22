<?php
// API para gerenciamento de instrutores
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

// Verificar se o usuário está logado e tem permissão de admin
if (!isLoggedIn() || !hasPermission('admin')) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Listar instrutores ou buscar instrutor específico
            if (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                $instrutor = $db->fetch("
                    SELECT i.*, u.nome as nome_usuario, u.email, c.nome as nome_cfc 
                    FROM instrutores i 
                    LEFT JOIN usuarios u ON i.usuario_id = u.id 
                    LEFT JOIN cfcs c ON i.cfc_id = c.id 
                    WHERE i.id = ?
                ", [$id]);
                
                if ($instrutor) {
                    echo json_encode(['success' => true, 'data' => $instrutor]);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Instrutor não encontrado']);
                }
            } else {
                // Listar todos os instrutores
                $instrutores = $db->fetchAll("
                    SELECT i.*, u.nome as nome_usuario, u.email, c.nome as nome_cfc 
                    FROM instrutores i 
                    LEFT JOIN usuarios u ON i.usuario_id = u.id 
                    LEFT JOIN cfcs c ON i.cfc_id = c.id 
                    ORDER BY u.nome
                ");
                echo json_encode(['success' => true, 'data' => $instrutores]);
            }
            break;
            
        case 'POST':
            // Criar novo instrutor
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                $data = $_POST;
            }
            
            // Validações
            if (empty($data['nome']) || empty($data['email']) || empty($data['senha']) || empty($data['cfc_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Nome, E-mail, Senha e CFC são obrigatórios']);
                exit;
            }
            
            // Verificar se email já existe
            $existingUser = $db->fetch("SELECT id FROM usuarios WHERE email = ?", [$data['email']]);
            if ($existingUser) {
                http_response_code(400);
                echo json_encode(['error' => 'E-mail já cadastrado']);
                exit;
            }
            
            // Verificar se CFC existe
            $existingCFC = $db->fetch("SELECT id FROM cfcs WHERE id = ?", [$data['cfc_id']]);
            if (!$existingCFC) {
                http_response_code(400);
                echo json_encode(['error' => 'CFC não encontrado']);
                exit;
            }
            
            // Hash da senha
            $senha_hash = password_hash($data['senha'], PASSWORD_DEFAULT);
            
            // Iniciar transação
            $db->beginTransaction();
            
            try {
                // Criar usuário
                $usuario_id = $db->insert('usuarios', [
                    'nome' => $data['nome'],
                    'email' => $data['email'],
                    'senha' => $senha_hash,
                    'tipo' => 'instrutor',
                    'ativo' => isset($data['ativo']) ? (bool)$data['ativo'] : true,
                    'criado_em' => date('Y-m-d H:i:s')
                ]);
                
                if (!$usuario_id) {
                    throw new Exception('Erro ao criar usuário');
                }
                
                // Criar instrutor
                $instrutor_id = $db->insert('instrutores', [
                    'usuario_id' => $usuario_id,
                    'cfc_id' => $data['cfc_id'],
                    'credencial' => $data['credencial'] ?? '',
                    'categoria' => $data['categoria'] ?? '',
                    'telefone' => $data['telefone'] ?? '',
                    'endereco' => $data['endereco'] ?? '',
                    'cidade' => $data['cidade'] ?? '',
                    'uf' => $data['uf'] ?? '',
                    'ativo' => isset($data['ativo']) ? (bool)$data['ativo'] : true,
                    'criado_em' => date('Y-m-d H:i:s')
                ]);
                
                if (!$instrutor_id) {
                    throw new Exception('Erro ao criar instrutor');
                }
                
                $db->commit();
                
                // Buscar instrutor criado
                $instrutor = $db->fetch("
                    SELECT i.*, u.nome as nome_usuario, u.email, c.nome as nome_cfc 
                    FROM instrutores i 
                    LEFT JOIN usuarios u ON i.usuario_id = u.id 
                    LEFT JOIN cfcs c ON i.cfc_id = c.id 
                    WHERE i.id = ?
                ", [$instrutor_id]);
                
                echo json_encode(['success' => true, 'message' => 'Instrutor criado com sucesso', 'data' => $instrutor]);
                
            } catch (Exception $e) {
                $db->rollback();
                throw $e;
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
                if (!empty($data['categoria'])) $updateInstrutorData['categoria'] = $data['categoria'];
                if (!empty($data['telefone'])) $updateInstrutorData['telefone'] = $data['telefone'];
                if (!empty($data['endereco'])) $updateInstrutorData['endereco'] = $data['endereco'];
                if (!empty($data['cidade'])) $updateInstrutorData['cidade'] = $data['cidade'];
                if (!empty($data['uf'])) $updateInstrutorData['uf'] = $data['uf'];
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
                    SELECT i.*, u.nome as nome_usuario, u.email, c.nome as nome_cfc 
                    FROM instrutores i 
                    LEFT JOIN usuarios u ON i.usuario_id = u.id 
                    LEFT JOIN cfcs c ON i.cfc_id = c.id 
                    WHERE i.id = ?
                ", [$id]);
                
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
                
                // Verificar se instrutor existe
                $existingInstrutor = $db->fetch("SELECT id, usuario_id FROM instrutores WHERE id = ?", [$id]);
                if (!$existingInstrutor) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Instrutor não encontrado']);
                    exit;
                }
                
                // Iniciar transação
                $db->beginTransaction();
                
                try {
                    // Excluir instrutor
                    $result = $db->delete('instrutores', 'id = ?', [$id]);
                    if (!$result) {
                        throw new Exception('Erro ao excluir instrutor');
                    }
                    
                    // Excluir usuário
                    $result = $db->delete('usuarios', 'id = ?', [$existingInstrutor['usuario_id']]);
                    if (!$result) {
                        throw new Exception('Erro ao excluir usuário');
                    }
                    
                    $db->commit();
                    echo json_encode(['success' => true, 'message' => 'Instrutor excluído com sucesso']);
                    
                } catch (Exception $e) {
                    $db->rollback();
                    throw $e;
                }
            } else {
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
