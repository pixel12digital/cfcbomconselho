<?php
// API para gerenciamento de usuários
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Usar caminho relativo que sabemos que funciona
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
            // Listar usuários ou buscar usuário específico
            if (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                $usuario = $db->fetch("SELECT id, nome, email, tipo, ativo, criado_em FROM usuarios WHERE id = ?", [$id]);
                
                if ($usuario) {
                    echo json_encode(['success' => true, 'data' => $usuario]);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Usuário não encontrado']);
                }
            } else {
                // Listar todos os usuários
                $usuarios = $db->fetchAll("SELECT id, nome, email, tipo, ativo, criado_em FROM usuarios ORDER BY nome");
                echo json_encode(['success' => true, 'data' => $usuarios]);
            }
            break;
            
        case 'POST':
            // Criar novo usuário
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                $data = $_POST;
            }
            
            // Validações
            if (empty($data['nome']) || empty($data['email']) || empty($data['senha']) || empty($data['tipo'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Todos os campos são obrigatórios']);
                exit;
            }
            
            // Verificar se email já existe
            $existingUser = $db->fetch("SELECT id FROM usuarios WHERE email = ?", [$data['email']]);
            if ($existingUser) {
                http_response_code(400);
                echo json_encode(['error' => 'E-mail já cadastrado']);
                exit;
            }
            
            // Hash da senha
            $senha_hash = password_hash($data['senha'], PASSWORD_DEFAULT);
            
            // Inserir usuário
            $result = $db->insert('usuarios', [
                'nome' => $data['nome'],
                'email' => $data['email'],
                'senha' => $senha_hash,
                'tipo' => $data['tipo'],
                'ativo' => isset($data['ativo']) ? (bool)$data['ativo'] : true,
                'criado_em' => date('Y-m-d H:i:s')
            ]);
            
            if ($result) {
                $usuario = $db->fetch("SELECT id, nome, email, tipo, ativo, criado_em FROM usuarios WHERE id = ?", [$result]);
                echo json_encode(['success' => true, 'message' => 'Usuário criado com sucesso', 'data' => $usuario]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao criar usuário']);
            }
            break;
            
        case 'PUT':
            // Atualizar usuário
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                parse_str(file_get_contents('php://input'), $data);
            }
            
            if (empty($data['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID do usuário é obrigatório']);
                exit;
            }
            
            $id = (int)$data['id'];
            
            // Verificar se usuário existe
            $existingUser = $db->fetch("SELECT id FROM usuarios WHERE id = ?", [$id]);
            if (!$existingUser) {
                http_response_code(404);
                echo json_encode(['error' => 'Usuário não encontrado']);
                exit;
            }
            
            // Preparar dados para atualização
            $updateData = [];
            if (!empty($data['nome'])) $updateData['nome'] = $data['nome'];
            if (!empty($data['email'])) $updateData['email'] = $data['email'];
            if (!empty($data['tipo'])) $updateData['tipo'] = $data['tipo'];
            if (isset($data['ativo'])) $updateData['ativo'] = (bool)$data['ativo'];
            
            // Atualizar senha se fornecida
            if (!empty($data['senha'])) {
                $updateData['senha'] = password_hash($data['senha'], PASSWORD_DEFAULT);
            }
            
            $updateData['atualizado_em'] = date('Y-m-d H:i:s');
            
            // Atualizar usuário
            $result = $db->update('usuarios', $updateData, 'id = ?', [$id]);
            
            if ($result) {
                $usuario = $db->fetch("SELECT id, nome, email, tipo, ativo, criado_em FROM usuarios WHERE id = ?", [$id]);
                echo json_encode(['success' => true, 'message' => 'Usuário atualizado com sucesso', 'data' => $usuario]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao atualizar usuário']);
            }
            break;
            
        case 'DELETE':
            // Excluir usuário
            if (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                
                // Verificar se usuário existe
                $existingUser = $db->fetch("SELECT id FROM usuarios WHERE id = ?", [$id]);
                if (!$existingUser) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Usuário não encontrado']);
                    exit;
                }
                
                // Excluir usuário
                $result = $db->delete('usuarios', 'id = ?', [$id]);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Usuário excluído com sucesso']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Erro ao excluir usuário']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'ID do usuário é obrigatório']);
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
        error_log('Erro na API de usuários: ' . $e->getMessage());
    }
}
?>
