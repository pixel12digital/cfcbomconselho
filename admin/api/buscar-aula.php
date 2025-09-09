<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Limpar buffer de saída
if (ob_get_level()) {
    ob_clean();
}

// Iniciar sessão se necessário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

require_once __DIR__ . '/../../includes/database.php';

try {
    $db = db();
    
    // Verificar se foi fornecido um ID de aula
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID da aula não fornecido']);
        exit;
    }
    
    $aulaId = (int)$_GET['id'];
    
    // Buscar dados da aula
    $aula = $db->fetch("
        SELECT a.*, 
               al.nome as aluno_nome,
               COALESCE(u.nome, i.nome) as instrutor_nome,
               i.credencial,
               v.placa, v.modelo, v.marca, v.tipo_veiculo
        FROM aulas a
        JOIN alunos al ON a.aluno_id = al.id
        JOIN instrutores i ON a.instrutor_id = i.id
        LEFT JOIN usuarios u ON i.usuario_id = u.id
        LEFT JOIN veiculos v ON a.veiculo_id = v.id
        WHERE a.id = ?
    ", [$aulaId]);
    
    if (!$aula) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Aula não encontrada']);
        exit;
    }
    
    // Buscar instrutores disponíveis
    $instrutores = $db->fetchAll("
        SELECT i.id, COALESCE(u.nome, i.nome) as nome, i.credencial
        FROM instrutores i
        LEFT JOIN usuarios u ON i.usuario_id = u.id
        WHERE i.ativo = 1
        ORDER BY nome
    ");
    
    // Buscar veículos disponíveis
    $veiculos = $db->fetchAll("
        SELECT * FROM veiculos 
        WHERE ativo = 1 
        ORDER BY marca, modelo
    ");
    
    // Buscar disciplinas disponíveis (se for aula teórica)
    $disciplinas = [
        'legislacao_transito' => 'Legislação de Trânsito',
        'direcao_defensiva' => 'Direção Defensiva',
        'primeiros_socorros' => 'Primeiros Socorros',
        'meio_ambiente' => 'Meio Ambiente',
        'cidadania' => 'Cidadania',
        'mecanica_basica' => 'Mecânica Básica'
    ];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'aula' => $aula,
            'instrutores' => $instrutores,
            'veiculos' => $veiculos,
            'disciplinas' => $disciplinas
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}
?>
