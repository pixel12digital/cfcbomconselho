<?php
/**
 * Script de teste direto para verificar UPDATE
 * Acesse: instrutor/test-update-direct.php
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Verificar autenticação
$user = getCurrentUser();
if (!$user || $user['tipo'] !== 'instrutor') {
    die('Acesso negado');
}

$db = db();
$instrutorId = getCurrentInstrutorId($user['id']);

if (!$instrutorId) {
    die('Instrutor não encontrado');
}

echo "<h2>Teste de UPDATE Direto</h2>";
echo "<p>Instrutor ID: {$instrutorId}</p>";
echo "<p>Usuario ID: {$user['id']}</p>";

// Teste 1: Atualizar telefone na tabela instrutores
echo "<h3>Teste 1: UPDATE instrutores.telefone</h3>";
$telefoneTeste = '47997309525';
$sql1 = "UPDATE instrutores SET telefone = ?, updated_at = NOW() WHERE id = ?";
$params1 = [$telefoneTeste, $instrutorId];

try {
    $stmt1 = $db->query($sql1, $params1);
    $rows1 = $stmt1->rowCount();
    echo "<p style='color: green;'>✅ Query executada. Linhas afetadas: {$rows1}</p>";
    echo "<p>SQL: {$sql1}</p>";
    echo "<p>Params: " . json_encode($params1) . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

// Verificar se foi salvo
$verificacao1 = $db->fetch("SELECT telefone FROM instrutores WHERE id = ?", [$instrutorId]);
echo "<p>Telefone no banco após UPDATE: " . ($verificacao1['telefone'] ?? 'NULL') . "</p>";

// Teste 2: Atualizar telefone na tabela usuarios
echo "<h3>Teste 2: UPDATE usuarios.telefone</h3>";
$sql2 = "UPDATE usuarios SET telefone = ?, updated_at = NOW() WHERE id = ?";
$params2 = [$telefoneTeste, $user['id']];

try {
    $stmt2 = $db->query($sql2, $params2);
    $rows2 = $stmt2->rowCount();
    echo "<p style='color: green;'>✅ Query executada. Linhas afetadas: {$rows2}</p>";
    echo "<p>SQL: {$sql2}</p>";
    echo "<p>Params: " . json_encode($params2) . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

// Verificar se foi salvo
$verificacao2 = $db->fetch("SELECT telefone FROM usuarios WHERE id = ?", [$user['id']]);
echo "<p>Telefone no banco após UPDATE: " . ($verificacao2['telefone'] ?? 'NULL') . "</p>";

// Teste 3: Atualizar foto na tabela instrutores
echo "<h3>Teste 3: UPDATE instrutores.foto</h3>";
$fotoTeste = 'assets/uploads/instrutores/imagem-instrutor.png';
$sql3 = "UPDATE instrutores SET foto = ?, updated_at = NOW() WHERE id = ?";
$params3 = [$fotoTeste, $instrutorId];

try {
    $stmt3 = $db->query($sql3, $params3);
    $rows3 = $stmt3->rowCount();
    echo "<p style='color: green;'>✅ Query executada. Linhas afetadas: {$rows3}</p>";
    echo "<p>SQL: {$sql3}</p>";
    echo "<p>Params: " . json_encode($params3) . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

// Verificar se foi salvo
$verificacao3 = $db->fetch("SELECT foto FROM instrutores WHERE id = ?", [$instrutorId]);
echo "<p>Foto no banco após UPDATE: " . ($verificacao3['foto'] ?? 'NULL') . "</p>";

echo "<hr>";
echo "<h3>Estado Final no Banco</h3>";
$final = $db->fetch("SELECT telefone, foto FROM instrutores WHERE id = ?", [$instrutorId]);
echo "<pre>";
print_r($final);
echo "</pre>";
