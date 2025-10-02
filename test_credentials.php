<?php
// =====================================================
// TESTE DE CREDENCIAIS DA HOSTINGER
// =====================================================

echo "<h2>🔐 Teste de Credenciais da Hostinger</h2>\n";
echo "<hr>\n";

// Lista de credenciais possíveis para testar
$credentials_tests = [
    [
        'host' => 'auth-db1607.hstgr.io',
        'dbname' => 'u502697186_cfcbomconselho',
        'user' => 'u502697186_cfcbomconselho',
        'pass' => 'Los@ngo#081081',
        'label' => 'Configuração atual'
    ],
    [
        'host' => 'auth-db1607.hstgr.io',
        'dbname' => 'u502697186_cfcbomconselho',
        'user' => 'u502697186_cfcbomconselho',
        'pass' => '', // Sem senha
        'label' => 'Sem senha'
    ],
    [
        'host' => 'auth-db1607.hstgr.io',
        'dbname' => 'u502697186_cfcbomconselho',
        'user' => 'u502697186_cfcbomconselho',
        'pass' => 'u502697186', // Senha igual ao usuário
        'label' => 'Senha = nome usuário'
    ]
];

foreach ($credentials_tests as $index => $creds) {
    echo "<h3>🧪 Teste " . ($index + 1) . ": {$creds['label']}</h3>\n";
    
    try {
        $dsn = "mysql:host={$creds['host']};dbname={$creds['dbname']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10
        ];
        
        echo "🔄 Tentando conectar...<br>\n";
        echo "📋 DSN: " . $dsn . "<br>\n";
        echo "👤 Usuário: " . $creds['user'] . "<br>\n";
        echo "🔑 Senha: " . (empty($creds['pass']) ? '(vazia)' : str_repeat('*', strlen($creds['pass']))) . "<br>\n";
        
        $pdo = new PDO($dsn, $creds['user'], $creds['pass'], $options);
        
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>\n";
        echo "✅ <strong>SUCESSO!</strong> Conectado com sucesso!<br>\n";
        
        // Testar consulta básica
        $result = $pdo->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = '{$creds['dbname']}'");
        $count = $result->fetch();
        echo "📊 Tabelas encontradas: " . $count['table_count'] . "<br>\n";
        
        // Mostrar versão do MySQL
        $version_result = $pdo->query("SELECT @@version as version");
        $version = $version_result->fetch();
        echo "🔧 Versão MySQL: " . $version['version'] . "<br>\n";
        
        echo "</div>\n";
        
        // Se chegou até aqui, encontrou as credenciais corretas!
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0;'>\n";
        echo "<h4>🎉 CREDENCIAIS CORRETAS ENCONTRADAS!</h4>\n";
        echo "<strong>Host:</strong> " . $creds['host'] . "<br>\n";
        echo "<strong>Banco:</strong> " . $creds['dbname'] . "<br>\n";
        echo "<strong>Usuário:</strong> " . $creds['user'] . "<br>\n";
        echo "<strong>Senha:</strong> " . $creds['pass'] . "<br>\n";
        echo "</div>\n";
        
        break; // Parar nos testes se encontrou credenciais corretas
        
    } catch (PDOException $e) {
        $error_color = match($e->getCode()) {
            1045 => '#f8d7da', // Credenciais inválidas - vermelho
            2002 => '#fff3cd', // Host não encontrado - amarelo  
            default => '#e2e3e5' // Outros erros - cinza
        };
        
        echo "<div style='background: {$error_color}; padding: 10px; border-radius: 5px; margin: 10px 0;'>\n";
        echo "❌ <strong>FALHA:</strong> " . $e->getMessage() . "<br>\n";
        echo "🔍 <strong>Código:</strong> " . $e->getCode() . "<br>\n";
        echo "</div>\n";
    }
    
    echo "<hr>\n";
}

echo "<h3>📋 Instruções:</h3>\n";
echo "<ol>\n";
echo "<li>Se nenhum teste funcionou, verifique as credenciais na Hostinger</li>\n";
echo "<li>Vá em: Painel → Bancos de Dados MySQL</li>\n";
echo "<li>Verifique nome do banco, usuário e senha</li>\n";
echo "<li>Execute este teste novamente</li>\n";
echo "</ol>\n";

echo "<br><a href='login.php'>🔗 Testar Login após correção</a>\n";
?>
