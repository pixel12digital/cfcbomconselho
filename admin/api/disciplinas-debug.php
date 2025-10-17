<?php
/**
 * API Simplificada para Debug de Disciplinas
 * Sistema CFC Bom Conselho
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

// Iniciar sessão se não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Função para enviar resposta JSON
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Log de debug
error_log('[API Disciplinas Debug] Iniciando requisição: ' . $_SERVER['REQUEST_METHOD'] . ' - ' . ($_GET['action'] ?? 'sem ação'));

try {
    // Incluir dependências
    require_once __DIR__ . '/../../includes/database.php';
    
    // Verificar se Database está disponível
    if (!class_exists('Database')) {
        throw new Exception('Classe Database não encontrada');
    }
    
    $db = Database::getInstance();
    
    // Verificar se a conexão está funcionando
    if (!$db) {
        throw new Exception('Não foi possível conectar ao banco de dados');
    }
    
    // Obter método da requisição
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    error_log('[API Disciplinas Debug] Método: ' . $method . ', Ação: ' . $action);
    
    // Para debug, vamos usar um CFC ID fixo
    $cfcId = 1;
    
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
            
            INDEX idx_cfc_ativa (cfc_id, ativa)
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
                error_log('[API Disciplinas Debug] Listando disciplinas...');
                
                $sql = "SELECT * FROM disciplinas WHERE cfc_id = ? AND ativa = 1 ORDER BY nome ASC";
                $disciplinas = $db->fetchAll($sql, [$cfcId]);
                
                error_log('[API Disciplinas Debug] Disciplinas encontradas: ' . count($disciplinas ?: []));
                
                sendJsonResponse([
                    'success' => true,
                    'disciplinas' => $disciplinas ?: []
                ]);
            } else {
                sendJsonResponse(['success' => false, 'error' => 'Ação não especificada'], 400);
            }
            break;
            
        default:
            sendJsonResponse(['success' => false, 'error' => 'Método não permitido'], 405);
            break;
    }
    
} catch (Exception $e) {
    error_log('[API Disciplinas Debug] Erro: ' . $e->getMessage());
    error_log('[API Disciplinas Debug] Stack trace: ' . $e->getTraceAsString());
    
    sendJsonResponse([
        'success' => false, 
        'error' => 'Erro interno do servidor: ' . $e->getMessage()
    ], 500);
}
?>
