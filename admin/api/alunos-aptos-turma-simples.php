<?php
/**
 * API simplificada para buscar alunos aptos para matrícula em turmas teóricas
 */

header('Content-Type: application/json; charset=utf-8');

// Incluir dependências
$rootPath = dirname(__DIR__, 2);
require_once $rootPath . '/includes/config.php';
require_once $rootPath . '/includes/database.php';

try {
    $db = Database::getInstance();
    
    // Obter turma_id da requisição
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    $turmaId = $input['turma_id'] ?? 7; // Default para turma 7
    
    // Buscar alunos aptos do CFC 36
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
            AND a.cfc_id = 36
            AND em.id IS NOT NULL 
            AND ep.id IS NOT NULL
        ORDER BY a.nome
    ", [$turmaId]);
    
    // Filtrar apenas alunos disponíveis
    $alunosDisponiveis = array_filter($alunosAptos, function($aluno) {
        return $aluno['status_matricula'] === 'disponivel';
    });
    
    $response = [
        'sucesso' => true,
        'alunos' => array_values($alunosDisponiveis),
        'estatisticas' => [
            'total_aptos' => count($alunosAptos),
            'total_disponiveis' => count($alunosDisponiveis),
            'total_matriculados' => count($alunosAptos) - count($alunosDisponiveis)
        ],
        'debug' => [
            'turma_id' => $turmaId,
            'cfc_id' => 36,
            'alunos_encontrados' => count($alunosDisponiveis)
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro interno do servidor',
        'debug' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
