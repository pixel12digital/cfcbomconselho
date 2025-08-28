<?php
// Debug da página real de CFCs
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Debug da Página Real de CFCs</h1>";

// Verificar se a página está sendo carregada corretamente
echo "<h2>1. Verificação da Página</h2>";

try {
    // Simular carregamento da página
    ob_start();
    
    // Incluir arquivos necessários
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    require_once 'includes/auth.php';
    
    // Simular variáveis de sessão
    $_SESSION['user_id'] = 1;
    $_SESSION['user_type'] = 'admin';
    $_SESSION['last_activity'] = time();
    
    echo "<p>✅ Arquivos de configuração carregados</p>";
    
    // Simular dados
    $cfcs = [];
    $usuarios = [];
    $mensagem = '';
    $tipo_mensagem = 'info';
    
    // Incluir a página de CFCs
    include 'admin/pages/cfcs.php';
    
    $output = ob_get_clean();
    
    echo "<p>✅ Página de CFCs carregada com sucesso</p>";
    
    // Verificar se o formulário está sendo renderizado
    if (strpos($output, 'id="formCFC"') !== false) {
        echo "<p>✅ Formulário com ID 'formCFC' encontrado na saída</p>";
    } else {
        echo "<p>❌ Formulário com ID 'formCFC' NÃO encontrado na saída</p>";
    }
    
    // Verificar se o botão está sendo renderizado
    if (strpos($output, 'id="btnSalvarCFC"') !== false) {
        echo "<p>✅ Botão com ID 'btnSalvarCFC' encontrado na saída</p>";
    } else {
        echo "<p>❌ Botão com ID 'btnSalvarCFC' NÃO encontrado na saída</p>";
    }
    
    // Verificar se a função está sendo definida
    if (strpos($output, 'function salvarCFC') !== false) {
        echo "<p>✅ Função 'salvarCFC' encontrada na saída</p>";
    } else {
        echo "<p>❌ Função 'salvarCFC' NÃO encontrada na saída</p>";
    }
    
    // Verificar se há erros de sintaxe
    if (strpos($output, 'Parse error') !== false || strpos($output, 'Fatal error') !== false) {
        echo "<p>❌ ERRO FATAL encontrado na saída</p>";
        echo "<details>";
        echo "<summary>📋 Saída com erro</summary>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
        echo "</details>";
    } else {
        echo "<p>✅ Nenhum erro fatal encontrado</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Exceção ao carregar página: " . $e->getMessage() . "</p>";
    echo "<p>Arquivo: " . $e->getFile() . "</p>";
    echo "<p>Linha: " . $e->getLine() . "</p>";
} catch (Error $e) {
    echo "<p>❌ Erro fatal ao carregar página: " . $e->getMessage() . "</p>";
    echo "<p>Arquivo: " . $e->getFile() . "</p>";
    echo "<p>Linha: " . $e->getLine() . "</p>";
}

echo "<hr>";
echo "<h2>2. Verificação de Arquivos JavaScript</h2>";

// Verificar se os arquivos JavaScript existem
$jsFiles = [
    'admin/assets/js/admin.js',
    'admin/assets/js/components.js'
];

foreach ($jsFiles as $jsFile) {
    if (file_exists($jsFile)) {
        echo "<p>✅ {$jsFile} - Existe</p>";
        
        // Verificar se há erros de sintaxe
        $output = [];
        $returnCode = 0;
        exec("node -c " . escapeshellarg($jsFile) . " 2>&1", $output, $returnCode);
        
        if ($returnCode === 0) {
            echo "<p>  ✅ Sintaxe JavaScript OK</p>";
        } else {
            echo "<p>  ❌ Erro de sintaxe JavaScript:</p>";
            echo "<pre>" . implode("\n", $output) . "</pre>";
        }
    } else {
        echo "<p>❌ {$jsFile} - NÃO EXISTE</p>";
    }
}

echo "<hr>";
echo "<h2>3. Verificação de Dependências</h2>";

// Verificar se o Bootstrap está sendo carregado
if (strpos($output, 'bootstrap') !== false) {
    echo "<p>✅ Bootstrap referenciado na página</p>";
} else {
    echo "<p>❌ Bootstrap NÃO referenciado na página</p>";
}

// Verificar se o jQuery está sendo carregado
if (strpos($output, 'jquery') !== false) {
    echo "<p>✅ jQuery referenciado na página</p>";
} else {
    echo "<p>❌ jQuery NÃO referenciado na página</p>";
}

echo "<hr>";
echo "<h2>🧪 Próximos Passos</h2>";
echo "<p>1. Se a página está carregando, teste no navegador</p>";
echo "<p>2. Se há erros, verifique o console do navegador</p>";
echo "<p>3. Verifique se todos os arquivos JavaScript estão sendo carregados</p>";
echo "<p>4. Teste se a função está disponível no console</p>";

echo "<hr>";
echo "<h2>📋 Comandos para Testar</h2>";
echo "<p>1. Acesse: <code>http://localhost:8080/cfc-bom-conselho/admin/index.php?page=cfcs&action=list</code></p>";
echo "<p>2. Abra o console (F12)</p>";
echo "<p>3. Digite: <code>typeof salvarCFC</code></p>";
echo "<p>4. Se retornar 'function', a função está carregada</p>";
echo "<p>5. Se retornar 'undefined', há problema no carregamento</p>";
?>
