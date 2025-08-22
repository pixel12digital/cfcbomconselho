<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

// Verificar se está logado e tem permissão
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
            $id = $_GET['id'] ?? null;
            
            if ($id) {
                // Buscar aluno específico
                $aluno = $db->findWhere('alunos', 'id = ?', [$id], '*', null, 1);
                if ($aluno && is_array($aluno)) {
                    $aluno = $aluno[0]; // Pegar o primeiro resultado
                }
                if (!$aluno) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Aluno não encontrado']);
                    exit;
                }
                
                // Buscar dados do CFC
                $cfc = $db->findWhere('cfcs', 'id = ?', [$aluno['cfc_id']], '*', null, 1);
                if ($cfc && is_array($cfc)) {
                    $cfc = $cfc[0]; // Pegar o primeiro resultado
                }
                $aluno['cfc_nome'] = $cfc ? $cfc['nome'] : 'N/A';
                
                echo json_encode(['success' => true, 'aluno' => $aluno]);
            } else {
                // Listar todos os alunos
                $alunos = $db->fetchAll("
                    SELECT a.*, c.nome as cfc_nome 
                    FROM alunos a 
                    LEFT JOIN cfcs c ON a.cfc_id = c.id 
                    ORDER BY a.nome ASC
                ");
                
                echo json_encode(['success' => true, 'alunos' => $alunos]);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                http_response_code(400);
                echo json_encode(['error' => 'Dados inválidos']);
                exit;
            }
            
            // Validações básicas
            if (empty($input['nome']) || empty($input['cpf']) || empty($input['cfc_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Nome, CPF e CFC são obrigatórios']);
                exit;
            }
            
            // Verificar se CPF já existe
            $cpfExistente = $db->findWhere('alunos', 'cpf = ? AND id != ?', [$input['cpf'], $input['id'] ?? 0], '*', null, 1);
            if ($cpfExistente && is_array($cpfExistente)) {
                $cpfExistente = $cpfExistente[0]; // Pegar o primeiro resultado
            }
            if ($cpfExistente) {
                http_response_code(400);
                echo json_encode(['error' => 'CPF já cadastrado']);
                exit;
            }
            
            // Verificar se CFC existe
            $cfc = $db->findWhere('cfcs', 'id = ?', [$input['cfc_id']], '*', null, 1);
            if ($cfc && is_array($cfc)) {
                $cfc = $cfc[0]; // Pegar o primeiro resultado
            }
            if (!$cfc) {
                http_response_code(400);
                echo json_encode(['error' => 'CFC não encontrado']);
                exit;
            }
            
            $alunoData = [
                'cfc_id' => $input['cfc_id'],
                'nome' => $input['nome'],
                'cpf' => $input['cpf'],
                'rg' => $input['rg'] ?? '',
                'data_nascimento' => $input['data_nascimento'] ?? null,
                'telefone' => $input['telefone'] ?? '',
                'email' => $input['email'] ?? '',
                'endereco' => $input['endereco'] ?? '',
                'bairro' => $input['bairro'] ?? '',
                'cidade' => $input['cidade'] ?? '',
                'estado' => $input['estado'] ?? '',
                'cep' => $input['cep'] ?? '',
                'categoria_cnh' => $input['categoria_cnh'] ?? 'B',
                'status' => $input['status'] ?? 'ativo',
                'observacoes' => $input['observacoes'] ?? '',
                'criado_em' => date('Y-m-d H:i:s')
            ];
            
            $alunoId = $db->insert('alunos', $alunoData);
            if (!$alunoId) {
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao criar aluno']);
                exit;
            }
            
            $alunoData['id'] = $alunoId;
            echo json_encode(['success' => true, 'aluno' => $alunoData, 'mensagem' => 'Aluno criado com sucesso']);
            break;
            
        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $_GET['id'] ?? null;
            
            if (!$id || !$input) {
                http_response_code(400);
                echo json_encode(['error' => 'ID e dados são obrigatórios']);
                exit;
            }
            
            // Verificar se aluno existe
            $alunoExistente = $db->findWhere('alunos', 'id = ?', [$id], '*', null, 1);
            if ($alunoExistente && is_array($alunoExistente)) {
                $alunoExistente = $alunoExistente[0]; // Pegar o primeiro resultado
            }
            if (!$alunoExistente) {
                http_response_code(404);
                echo json_encode(['error' => 'Aluno não encontrado']);
                exit;
            }
            
            // Verificar se CPF já existe (exceto para o próprio aluno)
            if (isset($input['cpf'])) {
                $cpfExistente = $db->findWhere('alunos', 'cpf = ? AND id != ?', [$input['cpf'], $id], '*', null, 1);
                if ($cpfExistente && is_array($cpfExistente)) {
                    $cpfExistente = $cpfExistente[0]; // Pegar o primeiro resultado
                }
                if ($cpfExistente) {
                    http_response_code(400);
                    echo json_encode(['error' => 'CPF já cadastrado']);
                    exit;
                }
            }
            
            // Verificar se CFC existe
            if (isset($input['cfc_id'])) {
                $cfc = $db->findWhere('cfcs', 'id = ?', [$input['cfc_id']], '*', null, 1);
                if ($cfc && is_array($cfc)) {
                    $cfc = $cfc[0]; // Pegar o primeiro resultado
                }
                if (!$cfc) {
                    http_response_code(400);
                    echo json_encode(['error' => 'CFC não encontrado']);
                    exit;
                }
            }
            
            $alunoData = array_filter($input, function($value) {
                return $value !== null && $value !== '';
            });
            
            $alunoData['atualizado_em'] = date('Y-m-d H:i:s');
            
            $resultado = $db->update('alunos', $alunoData, 'id = ?', [$id]);
            if (!$resultado) {
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao atualizar aluno']);
                exit;
            }
            
            echo json_encode(['success' => true, 'mensagem' => 'Aluno atualizado com sucesso']);
            break;
            
        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? $_GET['id'] ?? null;
            
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID é obrigatório']);
                exit;
            }
            
            // Verificar se aluno existe
            $aluno = $db->findWhere('alunos', 'id = ?', [$id], '*', null, 1);
            if ($aluno && is_array($aluno)) {
                $aluno = $aluno[0]; // Pegar o primeiro resultado
            }
            if (!$aluno) {
                http_response_code(404);
                echo json_encode(['error' => 'Aluno não encontrado']);
                exit;
            }
            
            // Verificar se há aulas vinculadas
            $aulasVinculadas = $db->count('aulas', 'aluno_id = ?', [$id]);
            if ($aulasVinculadas > 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Não é possível excluir aluno com aulas vinculadas']);
                exit;
            }
            
            $resultado = $db->delete('alunos', 'id = ?', [$id]);
            if (!$resultado) {
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao excluir aluno']);
                exit;
            }
            
            echo json_encode(['success' => true, 'mensagem' => 'Aluno excluído com sucesso']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno: ' . $e->getMessage()]);
}
?>
