<?php
// Verificar CNPJs duplicados no banco
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Verificação de CNPJs Duplicados</h1>";

try {
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    
    $db = Database::getInstance();
    echo "<p>✅ Conexão com banco estabelecida</p>";
    
    // Verificar todos os CFCs no banco
    echo "<h2>1. Todos os CFCs no Banco</h2>";
    
    $cfcs = $db->fetchAll("SELECT id, nome, cnpj, cidade, uf, criado_em FROM cfcs ORDER BY id DESC");
    
    if (empty($cfcs)) {
        echo "<p>📋 Nenhum CFC encontrado no banco</p>";
    } else {
        echo "<p>📊 Total de CFCs: " . count($cfcs) . "</p>";
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr><th>ID</th><th>Nome</th><th>CNPJ</th><th>Cidade/UF</th><th>Criado em</th></tr>";
        
        foreach ($cfcs as $cfc) {
            echo "<tr>";
            echo "<td>{$cfc['id']}</td>";
            echo "<td>{$cfc['nome']}</td>";
            echo "<td>{$cfc['cnpj']}</td>";
            echo "<td>{$cfc['cidade']}/{$cfc['uf']}</td>";
            echo "<td>{$cfc['criado_em']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Verificar CNPJs duplicados
    echo "<h2>2. Verificação de CNPJs Duplicados</h2>";
    
    $duplicados = $db->fetchAll("
        SELECT cnpj, COUNT(*) as total, GROUP_CONCAT(id) as ids
        FROM cfcs 
        GROUP BY cnpj 
        HAVING COUNT(*) > 1
        ORDER BY total DESC
    ");
    
    if (empty($duplicados)) {
        echo "<p>✅ Nenhum CNPJ duplicado encontrado</p>";
    } else {
        echo "<p>⚠️ CNPJs duplicados encontrados:</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr><th>CNPJ</th><th>Total</th><th>IDs</th></tr>";
        
        foreach ($duplicados as $dup) {
            echo "<tr>";
            echo "<td>{$dup['cnpj']}</td>";
            echo "<td>{$dup['total']}</td>";
            echo "<td>{$dup['ids']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Verificar CNPJ específico que você tentou cadastrar
    echo "<h2>3. Verificar CNPJ Específico</h2>";
    
    $cnpjTeste = '77.777.777/0001-77'; // CNPJ que você tentou cadastrar
    echo "<p>🔍 Procurando por CNPJ: <strong>{$cnpjTeste}</strong></p>";
    
    $cfcExistente = $db->fetch("SELECT * FROM cfcs WHERE cnpj = ?", [$cnpjTeste]);
    
    if ($cfcExistente) {
        echo "<p>❌ <strong>CNPJ JÁ EXISTE!</strong></p>";
        echo "<p>📋 Detalhes:</p>";
        echo "<ul>";
        echo "<li>ID: {$cfcExistente['id']}</li>";
        echo "<li>Nome: {$cfcExistente['nome']}</li>";
        echo "<li>CNPJ: {$cfcExistente['cnpj']}</li>";
        echo "<li>Criado em: {$cfcExistente['criado_em']}</li>";
        echo "</ul>";
    } else {
        echo "<p>✅ CNPJ não existe no banco</p>";
    }
    
    // Verificar se há CNPJs similares
    echo "<h2>4. CNPJs Similares</h2>";
    
    $cnpjLimpo = preg_replace('/[^0-9]/', '', $cnpjTeste);
    echo "<p>🔍 CNPJ limpo (apenas números): {$cnpjLimpo}</p>";
    
    $cfcsSimilares = $db->fetchAll("
        SELECT id, nome, cnpj, criado_em 
        FROM cfcs 
        WHERE REPLACE(REPLACE(REPLACE(REPLACE(cnpj, '.', ''), '/', ''), '-', ''), ' ', '') = ?
        ORDER BY id DESC
    ", [$cnpjLimpo]);
    
    if (empty($cfcsSimilares)) {
        echo "<p>✅ Nenhum CNPJ similar encontrado</p>";
    } else {
        echo "<p>⚠️ CNPJs similares encontrados:</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr><th>ID</th><th>Nome</th><th>CNPJ</th><th>Criado em</th></tr>";
        
        foreach ($cfcsSimilares as $cfc) {
            echo "<tr>";
            echo "<td>{$cfc['id']}</td>";
            echo "<td>{$cfc['nome']}</td>";
            echo "<td>{$cfc['cnpj']}</td>";
            echo "<td>{$cfc['criado_em']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>🧪 Conclusão</h2>";
echo "<p>Se o CNPJ realmente não existe, pode ser:</p>";
echo "<ul>";
echo "<li>1. <strong>Cache do navegador</strong> - Pressione Ctrl+F5</li>";
echo "<li>2. <strong>Dados antigos</strong> - Verifique se não há dados de teste</li>";
echo "<li>3. <strong>Validação incorreta</strong> - Verifique a lógica da API</li>";
echo "</ul>";
echo "<p><strong>Próximo passo:</strong> Teste com um CNPJ completamente diferente!</p>";
?>
