<?php
// Debug da página de usuários
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Debug da Página de Usuários</h1>";

// Simular a página de usuários
echo "<h2>1. Simulando Página de Usuários</h2>";

// Incluir a página
ob_start();
include 'admin/pages/usuarios.php';
$output = ob_get_clean();

echo "<p>✅ Página incluída com sucesso</p>";

// Verificar se a função está no HTML
echo "<h2>2. Verificando Função no HTML</h2>";

if (strpos($output, 'function showCreateUserModal') !== false) {
    echo "<p>✅ Função showCreateUserModal encontrada no HTML</p>";
} else {
    echo "<p>❌ Função showCreateUserModal NÃO encontrada no HTML</p>";
}

if (strpos($output, 'showCreateUserModal()') !== false) {
    echo "<p>✅ Chamada showCreateUserModal() encontrada no HTML</p>";
} else {
    echo "<p>❌ Chamada showCreateUserModal() NÃO encontrada no HTML</p>";
}

// Verificar se há tags script
echo "<h2>3. Verificando Tags Script</h2>";

$scriptCount = substr_count($output, '<script>');
echo "<p>📊 Total de tags &lt;script&gt;: {$scriptCount}</p>";

if (strpos($output, '<script>') !== false) {
    echo "<p>✅ Tags script encontradas</p>";
} else {
    echo "<p>❌ Nenhuma tag script encontrada</p>";
}

// Verificar se há fechamento de script
if (strpos($output, '</script>') !== false) {
    echo "<p>✅ Tags script fechadas encontradas</p>";
} else {
    echo "<p>❌ Tags script não estão fechadas</p>";
}

// Verificar se há modal
echo "<h2>4. Verificando Modal</h2>";

if (strpos($output, 'id="userModal"') !== false) {
    echo "<p>✅ Modal userModal encontrado</p>";
} else {
    echo "<p>❌ Modal userModal NÃO encontrado</p>";
}

// Verificar se há botão
echo "<h2>5. Verificando Botão</h2>";

if (strpos($output, 'onclick="showCreateUserModal()"') !== false) {
    echo "<p>✅ Botão com onclick encontrado</p>";
} else {
    echo "<p>❌ Botão com onclick NÃO encontrado</p>";
}

// Mostrar parte do HTML para debug
echo "<h2>6. HTML da Página (Primeiros 2000 caracteres)</h2>";
echo "<pre>" . htmlspecialchars(substr($output, 0, 2000)) . "</pre>";

echo "<hr>";
echo "<h2>🧪 Conclusão</h2>";
echo "<p>Se a função está no HTML mas não funciona no navegador:</p>";
echo "<ul>";
echo "<li>1. <strong>Conflito de JavaScript</strong> - Outras funções podem estar sobrescrevendo</li>";
echo "<li>2. <strong>Ordem de carregamento</strong> - Script pode estar sendo carregado depois</li>";
echo "<li>3. <strong>Erro de sintaxe</strong> - JavaScript pode ter erro que impede execução</li>";
echo "</ul>";
?>
