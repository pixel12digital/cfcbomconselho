<?php
/**
 * API para Gerenciamento de Disciplinas
 * Sistema CFC Bom Conselho
 * 
 * @author Sistema CFC Bom Conselho
 * @version 1.0
 * @since 2024
 */

// Configurações de segurança
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Verificar se é requisição OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir dependências
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

// Obter dados do usuário
$user = $_SESSION['user'] ?? null;
$cfcId = $user['cfc_id'] ?? 1;

// Função para enviar resposta JSON
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Obter método da requisição
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $db = Database::getInstance();
    
    // Verificar se a tabela disciplinas existe, se não, criar
    $db->query("
        CREATE TABLE IF NOT EXISTS disciplinas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            carga_horaria INT NOT NULL DEFAULT 1,
            descricao TEXT DEFAULT NULL,
            ativa BOOLEAN DEFAULT TRUE,
            cfc_id INT NOT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_cfc_ativa (cfc_id, ativa),
            FOREIGN KEY (cfc_id) REFERENCES cfcs(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Inserir disciplinas padrão se não existirem
    $disciplinasPadrao = [
        ['Legislação de Trânsito', 18, 'Normas e regulamentações do trânsito brasileiro'],
        ['Direção Defensiva', 16, 'Técnicas de direção segura e preventiva'],
        ['Primeiros Socorros', 4, 'Noções básicas de primeiros socorros'],
        ['Meio Ambiente e Cidadania', 4, 'Consciência ambiental e cidadania no trânsito'],
        ['Mecânica Básica', 3, 'Conhecimentos básicos sobre funcionamento do veículo']
    ];
    
    foreach ($disciplinasPadrao as $disciplina) {
        $sql = "SELECT id FROM disciplinas WHERE nome = ? AND cfc_id = ?";
        $existe = $db->fetch($sql, [$disciplina[0], $cfcId]);
        if (!$existe) {
            $sql = "INSERT INTO disciplinas (nome, carga_horaria, descricao, cfc_id, ativa) VALUES (?, ?, ?, ?, ?)";
            $db->query($sql, [$disciplina[0], $disciplina[1], $disciplina[2], $cfcId, true]);
        }
    }
    
    switch ($method) {
        case 'GET':
            if ($action === 'listar') {
                $sql = "SELECT * FROM disciplinas WHERE cfc_id = ? AND ativa = 1 ORDER BY nome ASC";
                $disciplinas = $db->fetchAll($sql, [$cfcId]);
                sendJsonResponse([
                    'success' => true,
                    'disciplinas' => $disciplinas ?: []
                ]);
            } elseif ($action === 'obter' && isset($_GET['id'])) {
                $sql = "SELECT * FROM disciplinas WHERE id = ? AND cfc_id = ?";
                $disciplina = $db->fetch($sql, [$_GET['id'], $cfcId]);
                
                if ($disciplina) {
                    sendJsonResponse([
                        'success' => true,
                        'disciplina' => $disciplina
                    ]);
                } else {
                    sendJsonResponse(['success' => false, 'error' => 'Disciplina não encontrada'], 404);
                }
            } else {
                sendJsonResponse(['success' => false, 'error' => 'Ação não especificada'], 400);
            }
            break;
            
        case 'POST':
            if ($action === 'criar') {
                $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
                
                // Validações
                if (empty($data['nome'])) {
                    sendJsonResponse(['success' => false, 'error' => 'Nome da disciplina é obrigatório'], 400);
                }
                
                if (empty($data['carga_horaria']) || !is_numeric($data['carga_horaria']) || $data['carga_horaria'] < 1) {
                    sendJsonResponse(['success' => false, 'error' => 'Carga horária deve ser um número maior que 0'], 400);
                }
                
                // Verificar se já existe disciplina com mesmo nome
                $sql = "SELECT id FROM disciplinas WHERE nome = ? AND cfc_id = ?";
                $existe = $db->fetch($sql, [$data['nome'], $cfcId]);
                if ($existe) {
                    sendJsonResponse(['success' => false, 'error' => 'Já existe uma disciplina com este nome'], 400);
                }
                
                // Criar disciplina
                $sql = "INSERT INTO disciplinas (nome, carga_horaria, descricao, cfc_id, ativa) VALUES (?, ?, ?, ?, ?)";
                $result = $db->query($sql, [trim($data['nome']), (int)$data['carga_horaria'], $data['descricao'] ?? null, $cfcId, true]);
                
                if ($result) {
                    $disciplinaId = $db->lastInsertId();
                    $sql = "SELECT * FROM disciplinas WHERE id = ?";
                    $novaDisciplina = $db->fetch($sql, [$disciplinaId]);
                    
                    sendJsonResponse([
                        'success' => true,
                        'message' => 'Disciplina criada com sucesso',
                        'disciplina' => $novaDisciplina
                    ], 201);
                } else {
                    sendJsonResponse(['success' => false, 'error' => 'Erro ao criar disciplina'], 500);
                }
            } else {
                sendJsonResponse(['success' => false, 'error' => 'Ação não especificada'], 400);
            }
            break;
            
        case 'PUT':
            if ($action === 'editar' && isset($_GET['id'])) {
                $data = json_decode(file_get_contents('php://input'), true);
                
                // Verificar se a disciplina existe
                $sql = "SELECT * FROM disciplinas WHERE id = ? AND cfc_id = ?";
                $disciplina = $db->fetch($sql, [$_GET['id'], $cfcId]);
                if (!$disciplina) {
                    sendJsonResponse(['success' => false, 'error' => 'Disciplina não encontrada'], 404);
                }
                
                // Validações
                if (empty($data['nome'])) {
                    sendJsonResponse(['success' => false, 'error' => 'Nome da disciplina é obrigatório'], 400);
                }
                
                if (empty($data['carga_horaria']) || !is_numeric($data['carga_horaria']) || $data['carga_horaria'] < 1) {
                    sendJsonResponse(['success' => false, 'error' => 'Carga horária deve ser um número maior que 0'], 400);
                }
                
                // Verificar se já existe outra disciplina com mesmo nome
                $sql = "SELECT id FROM disciplinas WHERE nome = ? AND cfc_id = ? AND id != ?";
                $existe = $db->fetch($sql, [$data['nome'], $cfcId, $_GET['id']]);
                if ($existe) {
                    sendJsonResponse(['success' => false, 'error' => 'Já existe uma disciplina com este nome'], 400);
                }
                
                // Atualizar disciplina
                $sql = "UPDATE disciplinas SET nome = ?, carga_horaria = ?, descricao = ? WHERE id = ? AND cfc_id = ?";
                $atualizado = $db->query($sql, [trim($data['nome']), (int)$data['carga_horaria'], $data['descricao'] ?? null, $_GET['id'], $cfcId]);
                
                if ($atualizado) {
                    $sql = "SELECT * FROM disciplinas WHERE id = ?";
                    $disciplinaAtualizada = $db->fetch($sql, [$_GET['id']]);
                    
                    sendJsonResponse([
                        'success' => true,
                        'message' => 'Disciplina atualizada com sucesso',
                        'disciplina' => $disciplinaAtualizada
                    ]);
                } else {
                    sendJsonResponse(['success' => false, 'error' => 'Erro ao atualizar disciplina'], 500);
                }
            } else {
                sendJsonResponse(['success' => false, 'error' => 'Ação não especificada'], 400);
            }
            break;
            
        case 'DELETE':
            if ($action === 'excluir' && isset($_GET['id'])) {
                // Verificar se a disciplina existe
                $sql = "SELECT * FROM disciplinas WHERE id = ? AND cfc_id = ?";
                $disciplina = $db->fetch($sql, [$_GET['id'], $cfcId]);
                if (!$disciplina) {
                    sendJsonResponse(['success' => false, 'error' => 'Disciplina não encontrada'], 404);
                }
                
                // Verificar se a disciplina está sendo usada em turmas
                $sql = "SELECT id FROM turmas_disciplinas WHERE disciplina_id = ? LIMIT 1";
                $emUso = $db->fetch($sql, [$_GET['id']]);
                if ($emUso) {
                    sendJsonResponse(['success' => false, 'error' => 'Não é possível excluir disciplina que está sendo usada em turmas'], 400);
                }
                
                // Excluir disciplina (soft delete - marcar como inativa)
                $sql = "UPDATE disciplinas SET ativa = 0 WHERE id = ? AND cfc_id = ?";
                $excluido = $db->query($sql, [$_GET['id'], $cfcId]);
                
                if ($excluido) {
                    sendJsonResponse([
                        'success' => true,
                        'message' => 'Disciplina excluída com sucesso'
                    ]);
                } else {
                    sendJsonResponse(['success' => false, 'error' => 'Erro ao excluir disciplina'], 500);
                }
            } else {
                sendJsonResponse(['success' => false, 'error' => 'Ação não especificada'], 400);
            }
            break;
            
        default:
            sendJsonResponse(['success' => false, 'error' => 'Método não permitido'], 405);
            break;
    }
    
} catch (Exception $e) {
    error_log('[API Disciplinas] Erro: ' . $e->getMessage());
    sendJsonResponse(['success' => false, 'error' => 'Erro interno do servidor'], 500);
}
?>
