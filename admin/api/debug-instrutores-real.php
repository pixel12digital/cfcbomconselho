<?php
/**
 * Debug: lista de instrutores (versão crua) para inspeção rápida.
 * Uso: admin/api/debug-instrutores-real.php?turma_id=19
 * Não altera dados. Replica os filtros básicos de admin/api/instrutores-real.php.
 */

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../includes/config.php';
    require_once __DIR__ . '/../../includes/database.php';

    session_start();

    $turmaId = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 0;
    $cfcId = null;

    $db = Database::getInstance();

    if ($turmaId > 0) {
        $turma = $db->fetch("SELECT cfc_id, nome FROM turmas_teoricas WHERE id = ?", [$turmaId]);
        if ($turma && !empty($turma['cfc_id'])) {
            $cfc = $db->fetch("SELECT id, ativo FROM cfcs WHERE id = ?", [$turma['cfc_id']]);
            if ($cfc && $cfc['ativo']) {
                $cfcId = (int)$turma['cfc_id'];
            }
        }
    }

    if (!$cfcId && isset($_SESSION['user'])) {
        $userCfcId = $_SESSION['user']['cfc_id'] ?? null;
        if ($userCfcId) {
            $cfc = $db->fetch("SELECT id, ativo FROM cfcs WHERE id = ?", [$userCfcId]);
            if ($cfc && $cfc['ativo']) {
                $cfcId = (int)$userCfcId;
            }
        }
    }

    $whereClause = "(i.ativo = 1 OR i.ativo = TRUE OR (i.ativo IS NOT NULL AND i.ativo != 0))";
    $params = [];
    if ($cfcId) {
        $whereClause .= " AND i.cfc_id = ?";
        $params[] = $cfcId;
    }

    $rows = $db->fetchAll("
        SELECT
            i.id,
            i.nome AS nome_instrutor,
            u.nome AS nome_usuario,
            i.cfc_id,
            i.ativo,
            i.credencial,
            i.categoria_habilitacao,
            u.email
        FROM instrutores i
        LEFT JOIN usuarios u ON i.usuario_id = u.id
        WHERE {$whereClause}
        ORDER BY COALESCE(i.nome, u.nome) ASC
    ", $params);

    $instrutores = [];
    foreach ($rows as $row) {
        $nomeInstrutor = trim((string)($row['nome_instrutor'] ?? ''));
        $nomeUsuario = trim((string)($row['nome_usuario'] ?? ''));
        $nome = $nomeInstrutor !== '' ? $nomeInstrutor : ($nomeUsuario !== '' ? $nomeUsuario : 'Instrutor sem nome');

        $instrutores[] = [
            'id' => (int)$row['id'],
            'nome' => $nome,
            'nome_instrutor' => $row['nome_instrutor'],
            'nome_usuario' => $row['nome_usuario'],
            'cfc_id' => $row['cfc_id'],
            'ativo' => $row['ativo'],
            'credencial' => $row['credencial'],
            'categoria_habilitacao' => $row['categoria_habilitacao'],
            'email' => $row['email']
        ];
    }

    echo json_encode([
        'success' => true,
        'turma_id' => $turmaId,
        'cfc_id_usado' => $cfcId,
        'total' => count($instrutores),
        'instrutores' => $instrutores
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
*** End Patch
