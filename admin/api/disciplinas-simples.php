<?php
/**
 * API Ultra-Simplificada para Disciplinas
 * Sistema CFC Bom Conselho - Versão de Emergência
 */

// Configurações básicas
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Verificar se é requisição OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Função para enviar resposta JSON
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Log de debug
error_log('[API Disciplinas Ultra-Simples] Requisição: ' . $_SERVER['REQUEST_METHOD'] . ' - ' . ($_GET['action'] ?? 'sem ação'));

try {
    // Conectar ao banco diretamente (sem usar a classe Database)
    $host = 'localhost';
    $dbname = 'cfc_bom_conselho';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    error_log('[API Disciplinas Ultra-Simples] Conexão com banco estabelecida');
    
    // CFC ID fixo para debug
    $cfcId = 1;
    
    // Verificar se a tabela disciplinas existe, se não, criar
    $pdo->exec("
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
        $stmt = $pdo->prepare("SELECT id FROM disciplinas WHERE nome = ? AND cfc_id = ?");
        $stmt->execute([$disciplina[0], $cfcId]);
        $existe = $stmt->fetch();
        
        if (!$existe) {
            $stmt = $pdo->prepare("INSERT INTO disciplinas (nome, carga_horaria, descricao, cfc_id, ativa) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$disciplina[0], $disciplina[1], $disciplina[2], $cfcId, true]);
        }
    }
    
    // Processar requisições
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    
    if ($method === 'GET' && $action === 'listar') {
        error_log('[API Disciplinas Ultra-Simples] Listando disciplinas...');
        
        $stmt = $pdo->prepare("SELECT * FROM disciplinas WHERE cfc_id = ? AND ativa = 1 ORDER BY nome ASC");
        $stmt->execute([$cfcId]);
        $disciplinas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log('[API Disciplinas Ultra-Simples] Disciplinas encontradas: ' . count($disciplinas));
        
        sendResponse([
            'success' => true,
            'disciplinas' => $disciplinas
        ]);
    } else {
        sendResponse(['success' => false, 'error' => 'Ação não especificada'], 400);
    }
    
} catch (Exception $e) {
    error_log('[API Disciplinas Ultra-Simples] Erro: ' . $e->getMessage());
    error_log('[API Disciplinas Ultra-Simples] Stack trace: ' . $e->getTraceAsString());
    
    sendResponse([
        'success' => false, 
        'error' => 'Erro interno: ' . $e->getMessage()
    ], 500);
}
?>
