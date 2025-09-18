<?php
// Verificação rápida dos alunos de teste
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = Database::getInstance();
$alunos_ids = [113, 127, 128];

echo "=== VERIFICAÇÃO DE EXCLUSÃO DOS ALUNOS DE TESTE ===\n";
echo "Data: " . date('d/m/Y H:i:s') . "\n\n";

// Verificar se os alunos ainda existem
foreach ($alunos_ids as $id) {
    $aluno = $db->fetch("SELECT id, nome FROM alunos WHERE id = ?", [$id]);
    if ($aluno) {
        echo "❌ ALUNO ID {$id} AINDA EXISTE: {$aluno['nome']}\n";
    } else {
        echo "✅ ALUNO ID {$id} FOI EXCLUÍDO\n";
    }
}

// Verificar aulas órfãs
$aulas_orfas = $db->count('aulas', 'aluno_id IN (113, 127, 128)');
echo "\nAulas órfãs: {$aulas_orfas}\n";

// Verificar slots órfãos
$slots_orfos = $db->count('aulas_slots', 'aluno_id IN (113, 127, 128)');
echo "Slots órfãos: {$slots_orfos}\n";

// Verificar logs órfãos
$logs_orfos = $db->count('logs', 'registro_id IN (113, 127, 128) AND tabela = "alunos"');
echo "Logs órfãos: {$logs_orfos}\n";

echo "\n=== RESULTADO ===\n";
if ($aulas_orfas == 0 && $slots_orfos == 0 && $logs_orfos == 0) {
    echo "✅ EXCLUSÃO COMPLETA E BEM-SUCEDIDA!\n";
} else {
    echo "❌ EXCLUSÃO INCOMPLETA - DADOS ÓRFÃOS ENCONTRADOS\n";
}
?>
