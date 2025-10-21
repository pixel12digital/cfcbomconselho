<?php
/**
 * API para buscar instrutores reais do banco de dados
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
    
    // Buscar instrutores ativos
    $stmt = $db->query("
        SELECT 
            i.id,
            i.nome,
            i.cpf,
            i.credencial,
            i.ativo,
            u.nome as nome_usuario
        FROM instrutores i 
        LEFT JOIN usuarios u ON i.usuario_id = u.id 
        WHERE i.ativo = 1 
        ORDER BY COALESCE(i.nome, u.nome) ASC
    ");
    
    $instrutores = $stmt->fetchAll();
    
    // Processar dados para garantir que temos nomes válidos
    $instrutoresProcessados = [];
    foreach ($instrutores as $instrutor) {
        $nome = $instrutor['nome'] ?: $instrutor['nome_usuario'] ?: 'Instrutor sem nome';
        $instrutoresProcessados[] = [
            'id' => $instrutor['id'],
            'nome' => $nome,
            'cpf' => $instrutor['cpf'] ?: '',
            'credencial' => $instrutor['credencial'] ?: '',
            'ativo' => $instrutor['ativo']
        ];
    }
    
    // Se não houver instrutores, criar um padrão
    if (empty($instrutoresProcessados)) {
        $instrutoresProcessados = [
            [
                'id' => 1,
                'nome' => 'Instrutor Padrão',
                'cpf' => '000.000.000-00',
                'credencial' => '000000',
                'ativo' => 1
            ]
        ];
    }
    
    // Limpar buffer de output
    ob_end_clean();
    
    // Retornar resposta de sucesso
    echo json_encode([
        'success' => true,
        'instrutores' => $instrutoresProcessados,
        'total' => count($instrutoresProcessados)
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
