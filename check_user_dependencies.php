<?php
// Script para verificar dependências do usuário ID=1
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = Database::getInstance();

echo "=== VERIFICAÇÃO DE DEPENDÊNCIAS DO USUÁRIO ID=1 ===\n\n";

// Verificar se o usuário existe
$usuario = $db->fetch("SELECT * FROM usuarios WHERE id = 1");
if (!$usuario) {
    echo "❌ Usuário ID=1 não encontrado!\n";
    exit;
}

echo "✅ Usuário encontrado:\n";
echo "   Nome: " . $usuario['nome'] . "\n";
echo "   Email: " . $usuario['email'] . "\n";
echo "   Tipo: " . $usuario['tipo'] . "\n\n";

// Verificar CFCs vinculados
$cfcsVinculados = $db->fetchAll("SELECT * FROM cfcs WHERE responsavel_id = 1");
if (count($cfcsVinculados) > 0) {
    echo "❌ Usuário possui CFCs vinculados:\n";
    foreach ($cfcsVinculados as $cfc) {
        echo "   - CFC ID: " . $cfc['id'] . " - Nome: " . $cfc['nome'] . "\n";
    }
    echo "\n";
} else {
    echo "✅ Nenhum CFC vinculado ao usuário\n\n";
}

// Verificar instrutores vinculados
$instrutoresVinculados = $db->fetchAll("SELECT * FROM instrutores WHERE usuario_id = 1");
if (count($instrutoresVinculados) > 0) {
    echo "❌ Usuário possui registros de instrutor:\n";
    foreach ($instrutoresVinculados as $instrutor) {
        echo "   - Instrutor ID: " . $instrutor['id'] . " - CFC ID: " . $instrutor['cfc_id'] . "\n";
    }
    echo "\n";
} else {
    echo "✅ Nenhum registro de instrutor vinculado\n\n";
}

// Verificar sessões
$sessoes = $db->fetchAll("SELECT * FROM sessoes WHERE usuario_id = 1");
if (count($sessoes) > 0) {
    echo "ℹ️  Usuário possui " . count($sessoes) . " sessões ativas\n\n";
} else {
    echo "✅ Nenhuma sessão ativa\n\n";
}

// Verificar logs
$logs = $db->fetchAll("SELECT * FROM logs WHERE usuario_id = 1");
if (count($logs) > 0) {
    echo "ℹ️  Usuário possui " . count($logs) . " registros de log\n\n";
} else {
    echo "✅ Nenhum registro de log\n\n";
}

// Verificar aulas como instrutor
$aulasComoInstrutor = $db->fetchAll("
    SELECT a.* FROM aulas a 
    INNER JOIN instrutores i ON a.instrutor_id = i.id 
    WHERE i.usuario_id = 1
");
if (count($aulasComoInstrutor) > 0) {
    echo "❌ Usuário possui aulas como instrutor:\n";
    foreach ($aulasComoInstrutor as $aula) {
        echo "   - Aula ID: " . $aula['id'] . " - Data: " . $aula['data_aula'] . "\n";
    }
    echo "\n";
} else {
    echo "✅ Nenhuma aula como instrutor\n\n";
}

echo "=== RESULTADO ===\n";
if (count($cfcsVinculados) > 0) {
    echo "❌ USUÁRIO NÃO PODE SER EXCLUÍDO - Possui CFCs vinculados\n";
    echo "   Solução: Remover ou alterar o responsável dos CFCs primeiro\n";
} elseif (count($instrutoresVinculados) > 0) {
    echo "❌ USUÁRIO NÃO PODE SER EXCLUÍDO - Possui registros de instrutor\n";
    echo "   Solução: Remover registros de instrutor primeiro\n";
} elseif (count($aulasComoInstrutor) > 0) {
    echo "❌ USUÁRIO NÃO PODE SER EXCLUÍDO - Possui aulas como instrutor\n";
    echo "   Solução: Remover ou alterar as aulas primeiro\n";
} else {
    echo "✅ USUÁRIO PODE SER EXCLUÍDO - Nenhuma dependência crítica encontrada\n";
}
?>
