<?php
/**
 * Debug: aulas do instrutor logado (hoje) – práticas x teóricas.
 * Uso: instrutor/debug_aulas_carlos.php (apenas homolog/local).
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: text/html; charset=utf-8');

$user = getCurrentUser();
$db = db();

$instrutorId = getCurrentInstrutorId($user['id'] ?? null);
$hoje = date('Y-m-d');

$praticas = [];
$teoricas = [];
$combinadas = [];

if ($instrutorId) {
    $praticas = $db->fetchAll("SELECT id, data_aula, hora_inicio, hora_fim, status, 'pratica' as tipo FROM aulas WHERE instrutor_id = ? AND data_aula = ? AND status != 'cancelada'", [$instrutorId, $hoje]);
    $teoricas = $db->fetchAll("SELECT id, data_aula, hora_inicio, hora_fim, status, 'teorica' as tipo FROM turma_aulas_agendadas WHERE instrutor_id = ? AND data_aula = ? AND status != 'cancelada'", [$instrutorId, $hoje]);
    $combinadas = array_merge($praticas, $teoricas);
}
?>
<pre>
User:
<?php var_export($user); ?>

Instrutor ID (via getCurrentInstrutorId):
<?php var_export($instrutorId); ?>

Aulas práticas hoje (<?= htmlspecialchars($hoje) ?>): <?= count($praticas) . "\n"; ?>
<?php var_export($praticas); ?>

Aulas teóricas hoje (<?= htmlspecialchars($hoje) ?>): <?= count($teoricas) . "\n"; ?>
<?php var_export($teoricas); ?>

Combinadas hoje: <?= count($combinadas) . "\n"; ?>
<?php var_export($combinadas); ?>
</pre>
