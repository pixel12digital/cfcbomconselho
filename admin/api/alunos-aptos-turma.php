<?php
/**
 * API para buscar alunos aptos para matrícula em turmas teóricas
 * Retorna apenas alunos com exames médico e psicotécnico aprovados
 */

// Configurações de cabeçalho
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Incluir dependências
$rootPath = dirname(__DIR__, 2); // Volta 2 níveis: admin/api -> admin -> raiz
require_once $rootPath . '/includes/config.php';
require_once $rootPath . '/includes/database.php';
require_once $rootPath . '/includes/auth.php';

// Verificar autenticação
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado']);
    exit;
}

// Verificar permissões - usar dados da sessão diretamente
$userType = $_SESSION['tipo'] ?? $_SESSION['user_type'] ?? null;
if (!in_array($userType, ['admin', 'instrutor'])) {
    http_response_code(403);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Permissão negada']);
    exit;
}

// Obter dados do usuário
$cfcId = $_SESSION['cfc_id'] ?? 1;

// Obter dados da requisição
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}
$turmaId = $input['turma_id'] ?? null;

if (!$turmaId) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID da turma é obrigatório']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Debug: Verificar se a turma existe
    $turma = $db->fetch("
        SELECT id, nome, cfc_id, max_alunos
        FROM turmas_teoricas 
        WHERE id = ? AND cfc_id = ?
    ", [$turmaId, $cfcId]);
    
    if (!$turma) {
        http_response_code(404);
        echo json_encode(['sucesso' => false, 'mensagem' => 'Turma não encontrada']);
        exit;
    }
    
    // Buscar alunos aptos (com exames médico e psicotécnico aprovados)
    $alunosAptos = $db->fetchAll("
        SELECT 
            a.id,
            a.nome,
            a.cpf,
            a.categoria_cnh,
            c.nome as cfc_nome,
            c.id as cfc_id,
            em.data_resultado as data_exame_medico,
            ep.data_resultado as data_exame_psicotecnico,
            em.protocolo as protocolo_medico,
            ep.protocolo as protocolo_psicotecnico,
            CASE 
                WHEN tm.id IS NOT NULL THEN 'matriculado'
                ELSE 'disponivel'
            END as status_matricula
        FROM alunos a
        JOIN cfcs c ON a.cfc_id = c.id
        LEFT JOIN exames em ON a.id = em.aluno_id 
            AND em.tipo = 'medico' 
            AND em.status = 'concluido' 
            AND em.resultado = 'apto'
        LEFT JOIN exames ep ON a.id = ep.aluno_id 
            AND ep.tipo = 'psicotecnico' 
            AND ep.status = 'concluido' 
            AND ep.resultado = 'apto'
        LEFT JOIN turma_matriculas tm ON a.id = tm.aluno_id AND tm.turma_id = ? AND tm.status IN ('matriculado', 'cursando')
        WHERE a.status = 'ativo'
            AND a.cfc_id = ?
            AND em.id IS NOT NULL 
            AND ep.id IS NOT NULL
        ORDER BY a.nome
    ", [$turmaId, $cfcId]);
    
    // Filtrar apenas alunos disponíveis (não matriculados)
    $alunosDisponiveis = array_filter($alunosAptos, function($aluno) {
        return $aluno['status_matricula'] === 'disponivel';
    });
    
    // Calcular estatísticas
    $totalAptos = count($alunosAptos);
    $totalDisponiveis = count($alunosDisponiveis);
    $totalMatriculados = $totalAptos - $totalDisponiveis;
    
    // Calcular alunos matriculados na turma
    $alunosMatriculadosTurma = $db->fetchColumn("
        SELECT COUNT(*) FROM turma_matriculas 
        WHERE turma_id = ? AND status IN ('matriculado', 'cursando')
    ", [$turmaId]);
    
    $vagasRestantes = max(0, $turma['max_alunos'] - $alunosMatriculadosTurma);
    
    // Preparar resposta
    $response = [
        'sucesso' => true,
        'alunos' => array_values($alunosDisponiveis), // Apenas alunos disponíveis
        'estatisticas' => [
            'total_aptos' => $totalAptos,
            'total_disponiveis' => $totalDisponiveis,
            'total_matriculados' => $totalMatriculados,
            'vagas_restantes' => $vagasRestantes,
            'max_alunos_turma' => $turma['max_alunos']
        ],
        'turma' => [
            'id' => $turma['id'],
            'nome' => $turma['nome'],
            'alunos_matriculados' => $alunosMatriculadosTurma
        ]
    ];
    
    // Verificar se há vagas disponíveis
    if ($vagasRestantes <= 0) {
        $response['aviso'] = 'Turma sem vagas disponíveis';
    }
    
    // Verificar se não há alunos aptos
    if ($totalDisponiveis === 0) {
        $response['aviso'] = 'Nenhum aluno apto disponível para matrícula';
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Erro na API alunos-aptos-turma: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'sucesso' => false, 
        'mensagem' => 'Erro interno do servidor',
        'debug' => DEBUG_MODE ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE);
}
?>
