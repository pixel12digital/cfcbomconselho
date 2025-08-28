<?php
// Debug da API de CFCs - Identificar erro 500
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Debug da API de CFCs</h1>";
echo "<p>Verificando possíveis problemas que causam erro 500...</p>";

// Teste 1: Verificar se os arquivos de include existem
echo "<h2>1. Verificação de Arquivos</h2>";

$files = [
    'includes/config.php',
    'includes/database.php',
    'includes/auth.php',
    'admin/api/cfcs.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "<p>✅ {$file} - Existe</p>";
    } else {
        echo "<p>❌ {$file} - NÃO EXISTE</p>";
    }
}

// Teste 2: Verificar sintaxe dos arquivos
echo "<h2>2. Verificação de Sintaxe</h2>";

foreach ($files as $file) {
    if (file_exists($file)) {
        $output = [];
        $returnCode = 0;
        exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $returnCode);
        
        if ($returnCode === 0) {
            echo "<p>✅ {$file} - Sintaxe OK</p>";
        } else {
            echo "<p>❌ {$file} - Erro de sintaxe:</p>";
            echo "<pre>" . implode("\n", $output) . "</pre>";
        }
    }
}

// Teste 3: Verificar se a API pode ser carregada
echo "<h2>3. Teste de Carregamento da API</h2>";

try {
    // Tentar carregar a API
    ob_start();
    include 'admin/api/cfcs.php';
    $output = ob_get_clean();
    
    echo "<p>✅ API carregada com sucesso</p>";
    echo "<details>";
    echo "<summary>📋 Saída da API</summary>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    echo "</details>";
    
} catch (Exception $e) {
    echo "<p>❌ Erro ao carregar API: " . $e->getMessage() . "</p>";
    echo "<p>Arquivo: " . $e->getFile() . "</p>";
    echo "<p>Linha: " . $e->getLine() . "</p>";
} catch (Error $e) {
    echo "<p>❌ Erro fatal ao carregar API: " . $e->getMessage() . "</p>";
    echo "<p>Arquivo: " . $e->getFile() . "</p>";
    echo "<p>Linha: " . $e->getLine() . "</p>";
}

// Teste 4: Verificar configuração do banco
echo "<h2>4. Teste de Conexão com Banco</h2>";

try {
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    
    $db = Database::getInstance();
    echo "<p>✅ Conexão com banco estabelecida</p>";
    
    // Verificar se o CFC existe
    $cfc = $db->fetch("SELECT * FROM cfcs WHERE id = 26");
    if ($cfc) {
        echo "<p>✅ CFC ID 26 encontrado: {$cfc['nome']}</p>";
    } else {
        echo "<p>❌ CFC ID 26 não encontrado</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro de banco: " . $e->getMessage() . "</p>";
}

// Teste 5: Verificar permissões de arquivo
echo "<h2>5. Verificação de Permissões</h2>";

foreach ($files as $file) {
    if (file_exists($file)) {
        $perms = fileperms($file);
        $readable = is_readable($file) ? '✅' : '❌';
        $writable = is_writable($file) ? '✅' : '❌';
        
        echo "<p>{$readable} {$file} - Permissões: " . substr(sprintf('%o', $perms), -4) . "</p>";
        echo "<p>  - Legível: " . (is_readable($file) ? 'Sim' : 'Não') . "</p>";
        echo "<p>  - Gravável: " . (is_writable($file) ? 'Sim' : 'Não') . "</p>";
    }
}

echo "<hr>";
echo "<h2>🧪 Próximos Passos</h2>";
echo "<p>1. Verifique os logs de erro do PHP (error_log)</p>";
echo "<p>2. Teste a API diretamente via navegador</p>";
echo "<p>3. Verifique se há problemas de permissão</p>";
echo "<p>4. Confirme se o banco está acessível</p>";
?>
