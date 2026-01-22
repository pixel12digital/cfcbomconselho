<?php
/**
 * API Restrita para Instrutor - Detalhes do Aluno
 * 
 * Este endpoint retorna apenas dados básicos do aluno e informações de frequência
 * relacionadas à turma atual, sem expor dados financeiros ou administrativos.
 * 
 * @author Sistema CFC Bom Conselho
 * @version 1.0
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Verificar método HTTP
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir dependências
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';

/**
 * Função helper para responder erros sempre em JSON
 */
function responderJsonErro($mensagem, $statusCode = 400, array $extra = []) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    $payload = array_merge([
        'success' => false,
        'message' => $mensagem,
    ], $extra);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar autenticação
$userId = $_SESSION['user_id'] ?? null;
$userType = $_SESSION['user_type'] ?? null;

if (!$userId || !$userType) {
    responderJsonErro('Sessão expirada. Faça login novamente.', 401, [
        'code' => 'AUTH_NO_SESSION',
    ]);
}

if (!isLoggedIn()) {
    responderJsonErro('Usuário não autenticado', 401, [
        'code' => 'AUTH_NOT_LOGGED_IN',
    ]);
}

// Verificar se é instrutor
if ($userType !== 'instrutor') {
    responderJsonErro('Acesso negado - Apenas instrutores podem acessar este endpoint', 403, [
        'code' => 'PERMISSAO_NEGADA',
    ]);
}

// Obter instrutor_id do usuário
$instrutorId = getCurrentInstrutorId($userId);
if (!$instrutorId) {
    responderJsonErro('Instrutor não encontrado ou não vinculado ao usuário', 403, [
        'code' => 'INSTRUTOR_NOT_FOUND',
    ]);
}

$db = Database::getInstance();

// Validar parâmetros
$alunoId = $_GET['aluno_id'] ?? null;
$turmaId = $_GET['turma_id'] ?? null; // Opcional: se não fornecido, validar aula prática

if (!$alunoId) {
    responderJsonErro('ID do aluno é obrigatório', 400, [
        'code' => 'ALUNO_ID_REQUIRED',
    ]);
}

try {
    // VALIDAÇÃO DE PERMISSÃO: Turma teórica OU aula prática
    if ($turmaId) {
        // CASO 1: Turma teórica - validar vínculo instrutor-turma
    $temAula = $db->fetch(
        "SELECT COUNT(*) as total FROM turma_aulas_agendadas WHERE turma_id = ? AND instrutor_id = ?",
        [$turmaId, $instrutorId]
    );
    
    if (!$temAula || $temAula['total'] == 0) {
        responderJsonErro('Você não é instrutor desta turma', 403, [
            'code' => 'INSTRUTOR_NAO_DA_TURMA',
        ]);
    }
    
    // Verificar se o aluno está matriculado nesta turma
    $matricula = $db->fetch(
        "SELECT id, status, data_matricula, frequencia_percentual 
         FROM turma_matriculas 
         WHERE turma_id = ? AND aluno_id = ?",
        [$turmaId, $alunoId]
    );
    
    if (!$matricula) {
        responderJsonErro('Aluno não está matriculado nesta turma', 404, [
            'code' => 'ALUNO_NAO_MATRICULADO',
        ]);
        }
    } else {
        // CASO 2: Aula prática - validar vínculo instrutor-aluno via aulas práticas
        $temAulaPratica = $db->fetch(
            "SELECT COUNT(*) as total 
             FROM aulas 
             WHERE instrutor_id = ? AND aluno_id = ? AND status != 'cancelada'",
            [$instrutorId, $alunoId]
        );
        
        if (!$temAulaPratica || $temAulaPratica['total'] == 0) {
            responderJsonErro('Você não tem aulas com este aluno', 403, [
                'code' => 'INSTRUTOR_SEM_AULA_PRATICA',
            ]);
        }
        
        // Para aulas práticas, não há matrícula de turma
        $matricula = null;
    }
    
    // Buscar dados básicos do aluno (sem dados financeiros ou administrativos)
    $aluno = $db->fetch("
        SELECT 
            a.id,
            a.nome,
            a.cpf,
            a.email,
            a.telefone,
            a.data_nascimento,
            a.categoria_cnh,
            a.foto,
            a.status as status_aluno
        FROM alunos a
        WHERE a.id = ?
    ", [$alunoId]);
    
    if (!$aluno) {
        responderJsonErro('Aluno não encontrado', 404, [
            'code' => 'ALUNO_NOT_FOUND',
        ]);
    }
    
    // Buscar categoria CNH da matrícula ativa (priorizar sobre alunos.categoria_cnh)
    $matriculaAtiva = $db->fetch("
        SELECT categoria_cnh, tipo_servico
        FROM matriculas
        WHERE aluno_id = ? AND status = 'ativa'
        ORDER BY data_inicio DESC
        LIMIT 1
    ", [$alunoId]);
    
    // Priorizar categoria da matrícula ativa, senão usar do aluno
    if ($matriculaAtiva && !empty($matriculaAtiva['categoria_cnh'])) {
        $aluno['categoria_cnh'] = $matriculaAtiva['categoria_cnh'];
    }
    
    // Buscar dados da turma (apenas se turma_id fornecido)
    $turma = null;
    if ($turmaId) {
    $turma = $db->fetch("
        SELECT 
            t.id,
            t.nome,
            t.curso_tipo,
            t.data_inicio,
            t.data_fim,
            t.status as status_turma
        FROM turmas_teoricas t
        WHERE t.id = ?
    ", [$turmaId]);
    }
    
    // Buscar frequência (apenas se for turma teórica)
    $totalAulas = 0;
    $totalPresentes = 0;
    $totalAusentes = 0;
    $totalRegistradas = 0;
    $frequenciaPercentual = 0;
    $historicoFormatado = [];
    
    if ($turmaId) {
    // Buscar resumo de presenças do aluno nesta turma
    $presencas = $db->fetch("
        SELECT 
            COUNT(*) as total_registradas,
            COUNT(CASE WHEN tp.presente = 1 THEN 1 END) as presentes,
            COUNT(CASE WHEN tp.presente = 0 THEN 1 END) as ausentes
        FROM turma_presencas tp
        INNER JOIN turma_aulas_agendadas taa ON tp.turma_aula_id = taa.id
        WHERE tp.turma_id = ? 
        AND tp.aluno_id = ?
        AND taa.status IN ('agendada', 'realizada')
    ", [$turmaId, $alunoId]);
    
    // Contar total de aulas válidas da turma
    $aulasValidas = $db->fetch("
        SELECT COUNT(*) as total
        FROM turma_aulas_agendadas 
        WHERE turma_id = ? 
        AND status IN ('agendada', 'realizada')
    ", [$turmaId]);
    
    $totalAulas = (int)($aulasValidas['total'] ?? 0);
    $totalPresentes = (int)($presencas['presentes'] ?? 0);
    $totalAusentes = (int)($presencas['ausentes'] ?? 0);
    $totalRegistradas = (int)($presencas['total_registradas'] ?? 0);
    
    // Calcular frequência percentual
    if ($totalAulas > 0) {
        $frequenciaPercentual = round(($totalPresentes / $totalAulas) * 100, 2);
    }
    
    // Buscar histórico de presenças (últimas 10 aulas)
    $historicoPresencas = $db->fetchAll("
        SELECT 
            tp.presente,
            tp.registrado_em,
            taa.nome_aula,
            taa.data_aula,
            taa.ordem_global as ordem
        FROM turma_presencas tp
        JOIN turma_aulas_agendadas taa ON tp.turma_aula_id = taa.id
        WHERE tp.turma_id = ? AND tp.aluno_id = ?
        ORDER BY taa.ordem_global DESC
        LIMIT 10
    ", [$turmaId, $alunoId]);
    
    // Formatar histórico
    foreach ($historicoPresencas as $presenca) {
        $historicoFormatado[] = [
            'data_aula' => $presenca['data_aula'],
            'nome_aula' => $presenca['nome_aula'],
            'ordem' => $presenca['ordem'],
            'presente' => (bool)$presenca['presente'],
            'status' => $presenca['presente'] ? 'PRESENTE' : 'AUSENTE',
            'registrado_em' => $presenca['registrado_em'],
        ];
        }
    }
    
    // Montar resposta
    $response = [
        'success' => true,
        'aluno' => [
            'id' => (int)$aluno['id'],
            'nome' => $aluno['nome'],
            'cpf' => $aluno['cpf'],
            'email' => $aluno['email'],
            'telefone' => $aluno['telefone'],
            'data_nascimento' => $aluno['data_nascimento'],
            'categoria_cnh' => $aluno['categoria_cnh'] ?: 'Não informado',
            'foto' => $aluno['foto'] ?: null,
            'status_aluno' => $aluno['status_aluno'],
        ],
    ];
    
    // Adicionar dados de turma e matrícula apenas se turma_id fornecido
    if ($turmaId && $turma) {
        $response['turma'] = [
            'id' => (int)$turma['id'],
            'nome' => $turma['nome'],
            'curso_tipo' => $turma['curso_tipo'],
            'data_inicio' => $turma['data_inicio'],
            'data_fim' => $turma['data_fim'],
            'status_turma' => $turma['status_turma'],
        ];
        
        if ($matricula) {
            $response['matricula'] = [
            'status' => $matricula['status'],
            'data_matricula' => $matricula['data_matricula'],
            'frequencia_percentual' => (float)($matricula['frequencia_percentual'] ?? 0),
            ];
        }
        
        $response['frequencia'] = [
            'total_aulas' => $totalAulas,
            'total_presentes' => $totalPresentes,
            'total_ausentes' => $totalAusentes,
            'total_registradas' => $totalRegistradas,
            'frequencia_percentual' => $frequenciaPercentual,
            'historico' => $historicoFormatado,
    ];
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('[aluno-detalhes-instrutor] Erro: ' . $e->getMessage());
    responderJsonErro('Erro interno do servidor: ' . $e->getMessage(), 500, [
        'code' => 'INTERNAL_ERROR',
    ]);
}

