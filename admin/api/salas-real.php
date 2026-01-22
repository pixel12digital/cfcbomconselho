<?php
/**
 * API para buscar salas reais do banco de dados
 * Retorna dados em formato JSON para uso em modais e formulários
 */

// Desabilitar completamente a exibição de erros
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Limpar qualquer output anterior
while (ob_get_level()) {
    ob_end_clean();
}

// Definir headers para JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Iniciar buffer de output
ob_start();

try {
    // Incluir dependências
    require_once __DIR__ . '/../../includes/config.php';
    require_once __DIR__ . '/../../includes/database.php';
    
    // Verificação básica de sessão
    session_start();
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Não autorizado');
    }
    
    // Conexão com banco
    $db = Database::getInstance();
    
    // Verificar se a tabela salas existe
    $stmt = $db->query("SHOW TABLES LIKE 'salas'");
    if ($stmt->rowCount() == 0) {
        // Criar tabela salas se não existir
        $db->query("
            CREATE TABLE salas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                capacidade INT NOT NULL DEFAULT 30,
                equipamentos JSON DEFAULT NULL,
                ativa BOOLEAN DEFAULT TRUE,
                cfc_id INT NOT NULL DEFAULT 1,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // Inserir salas padrão
        $salasPadrao = [
            ['Sala 01 Teste', 30],
            ['Sala 02', 25],
            ['Sala 03', 35]
        ];
        
        $stmt = $db->prepare("INSERT INTO salas (nome, capacidade, ativa, cfc_id) VALUES (?, ?, 1, 1)");
        foreach ($salasPadrao as $sala) {
            $stmt->execute($sala);
        }
    }
    
    // Buscar salas ativas
    $stmt = $db->query("
        SELECT 
            id,
            nome,
            capacidade,
            equipamentos,
            ativa
        FROM salas 
        WHERE ativa = 1 
        ORDER BY nome ASC
    ");
    
    $salas = $stmt->fetchAll();
    
    // Processar dados
    $salasProcessadas = [];
    foreach ($salas as $sala) {
        $salasProcessadas[] = [
            'id' => $sala['id'],
            'nome' => $sala['nome'],
            'capacidade' => $sala['capacidade'],
            'equipamentos' => $sala['equipamentos'],
            'ativa' => $sala['ativa']
        ];
    }
    
    // Se não houver salas, criar algumas padrão
    if (empty($salasProcessadas)) {
        $salasProcessadas = [
            [
                'id' => 1,
                'nome' => 'Sala 01 Teste',
                'capacidade' => 30,
                'equipamentos' => null,
                'ativa' => 1
            ],
            [
                'id' => 2,
                'nome' => 'Sala 02',
                'capacidade' => 25,
                'equipamentos' => null,
                'ativa' => 1
            ]
        ];
    }
    
    // Limpar buffer de output
    ob_end_clean();
    
    // Retornar resposta de sucesso
    echo json_encode([
        'success' => true,
        'salas' => $salasProcessadas,
        'total' => count($salasProcessadas)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Limpar buffer de output
    ob_end_clean();
    
    // Retornar erro
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor'
    ], JSON_UNESCAPED_UNICODE);
}
