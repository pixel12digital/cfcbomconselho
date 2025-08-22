<?php
// API para gerenciamento de CFCs
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
            // Listar CFCs ou buscar CFC específico
            if (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                $cfc = $db->fetch("SELECT * FROM cfcs WHERE id = ?", [$id]);
                
                if ($cfc) {
                    echo json_encode(['success' => true, 'data' => $cfc]);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'CFC não encontrado']);
                }
            } else {
                // Listar todos os CFCs
                $cfcs = $db->fetchAll("SELECT * FROM cfcs ORDER BY nome");
                echo json_encode(['success' => true, 'data' => $cfcs]);
            }
            break;
            
        case 'POST':
            // Criar novo CFC
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                $data = $_POST;
            }
            
            // Validações
            if (empty($data['nome']) || empty($data['cnpj']) || empty($data['cidade']) || empty($data['uf'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Nome, CNPJ, Cidade e UF são obrigatórios']);
                exit;
            }
            
            // Verificar se CNPJ já existe
            $existingCFC = $db->fetch("SELECT id FROM cfcs WHERE cnpj = ?", [$data['cnpj']]);
            if ($existingCFC) {
                http_response_code(400);
                echo json_encode(['error' => 'CNPJ já cadastrado']);
                exit;
            }
            
            // Inserir CFC
            $result = $db->insert('cfcs', [
                'nome' => $data['nome'],
                'cnpj' => $data['cnpj'],
                'razao_social' => $data['razao_social'] ?? $data['nome'],
                'endereco' => $data['endereco'] ?? '',
                'bairro' => $data['bairro'] ?? '',
                'cidade' => $data['cidade'],
                'uf' => $data['uf'],
                'cep' => $data['cep'] ?? '',
                'telefone' => $data['telefone'] ?? '',
                'email' => $data['email'] ?? '',
                'responsavel' => $data['responsavel'] ?? '',
                'ativo' => isset($data['ativo']) ? (bool)$data['ativo'] : true,
                'criado_em' => date('Y-m-d H:i:s')
            ]);
            
            if ($result) {
                $cfc = $db->fetch("SELECT * FROM cfcs WHERE id = ?", [$result]);
                echo json_encode(['success' => true, 'message' => 'CFC criado com sucesso', 'data' => $cfc]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao criar CFC']);
            }
            break;
            
        case 'PUT':
            // Atualizar CFC
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                parse_str(file_get_contents('php://input'), $data);
            }
            
            if (empty($data['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID do CFC é obrigatório']);
                exit;
            }
            
            $id = (int)$data['id'];
            
            // Verificar se CFC existe
            $existingCFC = $db->fetch("SELECT id FROM cfcs WHERE id = ?", [$id]);
            if (!$existingCFC) {
                http_response_code(404);
                echo json_encode(['error' => 'CFC não encontrado']);
                exit;
            }
            
            // Preparar dados para atualização
            $updateData = [];
            if (!empty($data['nome'])) $updateData['nome'] = $data['nome'];
            if (!empty($data['cnpj'])) $updateData['cnpj'] = $data['cnpj'];
            if (!empty($data['razao_social'])) $updateData['razao_social'] = $data['razao_social'];
            if (!empty($data['endereco'])) $updateData['endereco'] = $data['endereco'];
            if (!empty($data['bairro'])) $updateData['bairro'] = $data['bairro'];
            if (!empty($data['cidade'])) $updateData['cidade'] = $data['cidade'];
            if (!empty($data['uf'])) $updateData['uf'] = $data['uf'];
            if (!empty($data['cep'])) $updateData['cep'] = $data['cep'];
            if (!empty($data['telefone'])) $updateData['telefone'] = $data['telefone'];
            if (!empty($data['email'])) $updateData['email'] = $data['email'];
            if (!empty($data['responsavel'])) $updateData['responsavel'] = $data['responsavel'];
            if (isset($data['ativo'])) $updateData['ativo'] = (bool)$data['ativo'];
            
            $updateData['atualizado_em'] = date('Y-m-d H:i:s');
            
            // Atualizar CFC
            $result = $db->update('cfcs', $updateData, 'id = ?', [$id]);
            
            if ($result) {
                $cfc = $db->fetch("SELECT * FROM cfcs WHERE id = ?", [$id]);
                echo json_encode(['success' => true, 'message' => 'CFC atualizado com sucesso', 'data' => $cfc]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao atualizar CFC']);
            }
            break;
            
        case 'DELETE':
            // Excluir CFC
            if (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                
                // Verificar se CFC existe
                $existingCFC = $db->fetch("SELECT id FROM cfcs WHERE id = ?", [$id]);
                if (!$existingCFC) {
                    http_response_code(404);
                    echo json_encode(['error' => 'CFC não encontrado']);
                    exit;
                }
                
                // Verificar se há instrutores ou alunos vinculados
                $instrutores = $db->count('instrutores', 'cfc_id = ?', [$id]);
                $alunos = $db->count('alunos', 'cfc_id = ?', [$id]);
                
                if ($instrutores > 0 || $alunos > 0) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Não é possível excluir CFC com instrutores ou alunos vinculados']);
                    exit;
                }
                
                // Excluir CFC
                $result = $db->delete('cfcs', 'id = ?', [$id]);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'CFC excluído com sucesso']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Erro ao excluir CFC']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'ID do CFC é obrigatório']);
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
        error_log('Erro na API de CFCs: ' . $e->getMessage());
    }
}
?>
