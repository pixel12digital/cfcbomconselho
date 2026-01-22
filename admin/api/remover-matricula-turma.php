<?php
/**
 * API para remover matrícula de aluno em turma teórica
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$rootPath = dirname(__DIR__, 2);

require_once $rootPath . '/includes/config.php';
require_once $rootPath . '/includes/database.php';
require_once $rootPath . '/includes/auth.php';
require_once $rootPath . '/admin/includes/TurmaTeoricaManager.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão expirada. Faça login novamente.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userTypeRaw = $_SESSION['tipo'] ?? $_SESSION['user_type'] ?? null;
$userType = is_string($userTypeRaw) ? strtolower($userTypeRaw) : null;

if (!in_array($userType, ['admin', 'instrutor'])) {
    http_response_code(403);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Permissão negada.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$inputRaw = file_get_contents('php://input');
$data = json_decode($inputRaw, true);

if (!is_array($data)) {
    $data = $_POST;
}

$turmaId = isset($data['turma_id']) ? (int) $data['turma_id'] : 0;
$alunoId = isset($data['aluno_id']) ? (int) $data['aluno_id'] : 0;

if (!$turmaId || !$alunoId) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Informe turma e aluno.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $manager = new TurmaTeoricaManager();
    $resultado = $manager->removerAluno($turmaId, $alunoId);

    if (!$resultado['sucesso']) {
        error_log(sprintf(
            'API remover-matricula-turma: falha ao remover aluno %d da turma %d - %s',
            $alunoId,
            $turmaId,
            $resultado['mensagem'] ?? 'Sem mensagem'
        ));
        http_response_code(400);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $db = Database::getInstance();
    $totalMatriculados = $db->fetchColumn("
        SELECT COUNT(*) 
        FROM turma_matriculas 
        WHERE turma_id = ? AND status IN ('matriculado', 'cursando')
    ", [$turmaId]);

    echo json_encode([
        'sucesso' => true,
        'mensagem' => $resultado['mensagem'] ?? 'Aluno removido da turma.',
        'dados' => [
            'turma_id' => $turmaId,
            'aluno_id' => $alunoId,
            'alunos_matriculados' => (int) $totalMatriculados
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('Erro API remover-matricula-turma: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro interno ao remover matrícula.'
    ], JSON_UNESCAPED_UNICODE);
}

