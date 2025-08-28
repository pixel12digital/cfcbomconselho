<?php
// Debug do formulário de CFC
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Debug do Formulário de CFC</h1>";

// Verificar se o arquivo cfcs.php existe e está acessível
$cfcFile = 'admin/pages/cfcs.php';
if (file_exists($cfcFile)) {
    echo "<p>✅ Arquivo cfcs.php encontrado</p>";
    
    // Verificar se a função salvarCFC está no arquivo
    $fileContent = file_get_contents($cfcFile);
    if (strpos($fileContent, 'function salvarCFC') !== false) {
        echo "<p>✅ Função salvarCFC encontrada</p>";
    } else {
        echo "<p>❌ Função salvarCFC não encontrada</p>";
    }
    
    // Verificar se o formulário está sendo renderizado
    if (strpos($fileContent, 'id="formCFC"') !== false) {
        echo "<p>✅ Formulário com ID 'formCFC' encontrado</p>";
    } else {
        echo "<p>❌ Formulário com ID 'formCFC' não encontrado</p>";
    }
    
    // Verificar se o botão de salvar está sendo renderizado
    if (strpos($fileContent, 'id="btnSalvarCFC"') !== false) {
        echo "<p>✅ Botão com ID 'btnSalvarCFC' encontrado</p>";
    } else {
        echo "<p>❌ Botão com ID 'btnSalvarCFC' não encontrado</p>";
    }
    
    // Verificar se há problemas de sintaxe JavaScript
    preg_match('/<script>(.*?)<\/script>/s', $fileContent, $scriptMatches);
    if (!empty($scriptMatches)) {
        echo "<p>✅ Tag script encontrada</p>";
        
        $scriptContent = $scriptMatches[1];
        
        // Verificar se há erros de sintaxe básicos
        if (strpos($scriptContent, 'console.log') !== false) {
            echo "<p>✅ Console.log encontrado no script</p>";
        }
        
        // Verificar se há problemas com aspas
        $singleQuotes = substr_count($scriptContent, "'");
        $doubleQuotes = substr_count($scriptContent, '"');
        echo "<p>📊 Contagem de aspas: Simples: {$singleQuotes}, Duplas: {$doubleQuotes}</p>";
        
        // Verificar se há problemas com parênteses
        $openParens = substr_count($scriptContent, '(');
        $closeParens = substr_count($scriptContent, ')');
        echo "<p>📊 Contagem de parênteses: Abertos: {$openParens}, Fechados: {$closeParens}</p>";
        
        if ($openParens !== $closeParens) {
            echo "<p>⚠️ ATENÇÃO: Parênteses não balanceados!</p>";
        }
        
    } else {
        echo "<p>❌ Tag script não encontrada</p>";
    }
    
} else {
    echo "<p>❌ Arquivo cfcs.php não encontrado</p>";
}

echo "<hr>";
echo "<h2>🧪 Teste do Formulário</h2>";
echo "<p>1. Abra a página de CFCs no navegador</p>";
echo "<p>2. Clique em 'Novo CFC' para abrir o modal</p>";
echo "<p>3. Preencha os campos obrigatórios:</p>";
echo "<ul>";
echo "<li>Nome do CFC: CFC Teste</li>";
echo "<li>CNPJ: 12.345.678/0001-90</li>";
echo "<li>Cidade: São Paulo</li>";
echo "<li>UF: SP</li>";
echo "</ul>";
echo "<p>4. Clique em 'Salvar CFC'</p>";
echo "<p>5. Abra o console (F12) para ver logs</p>";
echo "<p>6. Verifique se há erros JavaScript</p>";

echo "<hr>";
echo "<h2>🔧 Possíveis Problemas</h2>";
echo "<p>1. <strong>JavaScript não carregado:</strong> Verifique se há erros no console</p>";
echo "<p>2. <strong>Função não definida:</strong> Digite 'salvarCFC' no console</p>";
echo "<p>3. <strong>Formulário não encontrado:</strong> Verifique se o modal está abrindo</p>";
echo "<p>4. <strong>Validação falhando:</strong> Verifique se todos os campos obrigatórios estão preenchidos</p>";
echo "<p>5. <strong>API não respondendo:</strong> Verifique se a requisição está sendo feita</p>";

echo "<hr>";
echo "<h2>📋 Comandos para Testar no Console</h2>";
echo "<p>Digite estes comandos no console do navegador:</p>";
echo "<pre>";
echo "// Verificar se a função existe\n";
echo "typeof salvarCFC\n\n";
echo "// Verificar se o formulário existe\n";
echo "document.getElementById('formCFC')\n\n";
echo "// Verificar se o botão existe\n";
echo "document.getElementById('btnSalvarCFC')\n\n";
echo "// Verificar se há erros\n";
echo "console.error\n";
echo "</pre>";
?>
