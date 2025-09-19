<?php
/**
 * API de Busca Global - Sistema CFC
 * Busca em alunos, instrutores, veículos, aulas, etc.
 */

// Configurações
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Verificar se é uma requisição GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Verificar parâmetro de busca
$query = $_GET['q'] ?? '';
if (empty($query) || strlen($query) < 3) {
    echo json_encode(['success' => true, 'results' => []]);
    exit;
}

// Incluir dependências
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

// Verificar autenticação
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

try {
    $db = Database::getInstance();
    $results = [];
    $searchTerm = '%' . $query . '%';
    
    // Buscar alunos
    $alunos = $db->fetchAll("
        SELECT 
            'aluno' as type,
            'Aluno' as type_label,
            CONCAT('Aluno: ', nome) as title,
            CONCAT('CPF: ', COALESCE(cpf, 'Não informado'), ' | CFC: CFC BOM CONSELHO') as subtitle,
            'fas fa-graduation-cap' as icon,
            '#9b59b6' as color,
            CONCAT('?page=alunos&action=view&id=', id) as url
        FROM alunos 
        WHERE nome LIKE ? OR cpf LIKE ? OR telefone LIKE ? OR email LIKE ?
        ORDER BY nome ASC
        LIMIT 5
    ", [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    
    $results = array_merge($results, $alunos);
    
    // Buscar instrutores
    $instrutores = $db->fetchAll("
        SELECT 
            'instrutor' as type,
            'Instrutor' as type_label,
            CONCAT('Instrutor: ', u.nome) as title,
            CONCAT('Credencial: ', COALESCE(i.credencial, 'Não informada'), ' | CFC: ', COALESCE(c.nome, 'CFC BOM CONSELHO')) as subtitle,
            'fas fa-chalkboard-teacher' as icon,
            '#e67e22' as color,
            CONCAT('?page=instrutores&action=view&id=', i.id) as url
        FROM instrutores i
        JOIN usuarios u ON i.usuario_id = u.id
        LEFT JOIN cfcs c ON i.cfc_id = c.id
        WHERE u.nome LIKE ? OR u.email LIKE ? OR i.credencial LIKE ?
        ORDER BY u.nome ASC
        LIMIT 5
    ", [$searchTerm, $searchTerm, $searchTerm]);
    
    $results = array_merge($results, $instrutores);
    
    // Buscar veículos
    $veiculos = $db->fetchAll("
        SELECT 
            'veiculo' as type,
            'Veículo' as type_label,
            CONCAT('Veículo: ', placa) as title,
            CONCAT(marca, ' ', modelo, ' (', ano, ') | Categoria: ', COALESCE(categoria_cnh, 'Não informada')) as subtitle,
            'fas fa-car' as icon,
            '#27ae60' as color,
            CONCAT('?page=veiculos&action=view&id=', id) as url
        FROM veiculos 
        WHERE placa LIKE ? OR marca LIKE ? OR modelo LIKE ? OR chassi LIKE ?
        ORDER BY placa ASC
        LIMIT 5
    ", [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    
    $results = array_merge($results, $veiculos);
    
    // Buscar aulas (próximas)
    $aulas = $db->fetchAll("
        SELECT 
            'aula' as type,
            'Aula' as type_label,
            CONCAT('Aula: ', DATE_FORMAT(data_aula, '%d/%m/%Y'), ' às ', TIME_FORMAT(hora_inicio, '%H:%i')) as title,
            CONCAT('Aluno: ', COALESCE(al.nome, 'Não informado'), ' | Instrutor: ', COALESCE(i.nome, 'Não informado')) as subtitle,
            'fas fa-calendar-check' as icon,
            '#3498db' as color,
            CONCAT('?page=agendar-aula&action=view&id=', a.id) as url
        FROM aulas a
        LEFT JOIN alunos al ON a.aluno_id = al.id
        LEFT JOIN instrutores i ON a.instrutor_id = i.id
        LEFT JOIN usuarios u ON i.usuario_id = u.id
        WHERE a.data_aula >= CURDATE() 
        AND (al.nome LIKE ? OR u.nome LIKE ? OR a.observacoes LIKE ?)
        ORDER BY a.data_aula ASC, a.hora_inicio ASC
        LIMIT 5
    ", [$searchTerm, $searchTerm, $searchTerm]);
    
    $results = array_merge($results, $aulas);
    
    // Buscar usuários do sistema
    $usuarios = $db->fetchAll("
        SELECT 
            'usuario' as type,
            'Usuário' as type_label,
            CONCAT('Usuário: ', nome) as title,
            CONCAT('Email: ', COALESCE(email, 'Não informado'), ' | Tipo: ', UPPER(tipo)) as subtitle,
            'fas fa-user' as icon,
            '#8e44ad' as color,
            CONCAT('?page=usuarios&action=view&id=', id) as url
        FROM usuarios 
        WHERE nome LIKE ? OR email LIKE ? OR cpf LIKE ?
        ORDER BY nome ASC
        LIMIT 5
    ", [$searchTerm, $searchTerm, $searchTerm]);
    
    $results = array_merge($results, $usuarios);
    
    // Ordenar resultados por relevância (título que começa com o termo primeiro)
    usort($results, function($a, $b) use ($query) {
        $aStartsWith = stripos($a['title'], $query) === 0;
        $bStartsWith = stripos($b['title'], $query) === 0;
        
        if ($aStartsWith && !$bStartsWith) return -1;
        if (!$aStartsWith && $bStartsWith) return 1;
        
        return strcasecmp($a['title'], $b['title']);
    });
    
    // Limitar a 10 resultados totais
    $results = array_slice($results, 0, 10);
    
    // Retornar resultados
    echo json_encode([
        'success' => true,
        'results' => $results,
        'total' => count($results),
        'query' => $query
    ]);
    
} catch (Exception $e) {
    error_log('Erro na busca global: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor',
        'error' => DEBUG_MODE ? $e->getMessage() : null
    ]);
}
?>