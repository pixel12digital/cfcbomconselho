<?php
/**
 * API simplificada para teste de salas
 */

// Headers primeiro
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Desabilitar qualquer output de erro
ini_set('display_errors', 0);
error_reporting(0);

// Função para conectar ao banco diretamente
function conectarBanco() {
    try {
        $dsn = "mysql:host=auth-db803.hstgr.io;dbname=u502697186_cfcbomconselho;charset=utf8mb4";
        $pdo = new PDO($dsn, 'u502697186_cfcbomconselho', 'Los@ngo#081081', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        return $pdo;
    } catch (PDOException $e) {
        return null;
    }
}

try {
    $acao = $_GET['acao'] ?? $_GET['action'] ?? $_POST['acao'] ?? $_POST['action'] ?? 'listar';
    
    if ($acao === 'listar') {
        $pdo = conectarBanco();
        if (!$pdo) {
            throw new Exception('Erro de conexão com banco de dados');
        }
        
        // Verificar se tabela existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'salas'");
        if ($stmt->rowCount() == 0) {
            // Criar tabela
            $pdo->exec("
                CREATE TABLE salas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nome VARCHAR(100) NOT NULL,
                    capacidade INT NOT NULL DEFAULT 30,
                    equipamentos JSON DEFAULT NULL,
                    ativa BOOLEAN DEFAULT TRUE,
                    cfc_id INT NOT NULL,
                    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }
        
        // Buscar salas
        $stmt = $pdo->prepare("SELECT * FROM salas WHERE cfc_id = ? ORDER BY nome ASC");
        $stmt->execute([1]);
        $salas = $stmt->fetchAll();
        
        echo json_encode([
            'sucesso' => true,
            'salas' => $salas,
            'total' => count($salas)
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        throw new Exception('Ação não reconhecida: ' . $acao);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => $e->getMessage(),
        'debug' => [
            'acao' => $acao ?? 'não definida',
            'get_data' => $_GET,
            'post_data' => $_POST
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>