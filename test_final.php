<?php
// =====================================================
// TESTE FINAL DE CONEXÃO COM HOSTINGER
// =====================================================

echo "<h2>🎯 Teste Final - Hostinger Configurado</h2>\n";
echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 8px; margin: 20px 0;'>\n";
echo "<h3>✅ Configurações atuais:</h3>\n";
echo "<strong>Servidor:</strong> auth-db1607.hstgr.io<br>\n";
echo "<strong>Acesso remoto:</strong> Qualquer host (%) ✅<br>\n";
echo "<strong>Seu IP:</strong> " . ($_SERVER['REMOTE_ADDR'] ?? 'Não detectado') . "<br>\n";
echo "<strong>Problema:</strong> Credenciais incorretas (código 1045)<br>\n";
echo "</div>\n";
echo "<hr>\n";

// Lista mais abrangente de credenciais para testar
$tests = [
    ['user' => 'u502697186_cfcbomconselho', 'pass' => '', 'label' => 'Sem senha'],
    ['user' => 'u502697186_cfcbomconselho', 'pass' => 'u502697186_cfcbomconselho', 'label' => 'Senha = usuário'],
    ['user' => 'u502697186_cfcbomconselho', 'pass' => 'cfcbomconselho', 'label' => 'Senha = nome banco'],
    ['user' => 'u502697186_cfcbomconselho', 'pass' => 'u502697186', 'label' => 'Senha = prefixo usuário'],
    ['user' => 'u502697186_cfcbomconselho', 'pass' => 'Los@ngo#081081', 'label' => 'Configuração original'],
    ['user' => 'u502697186_cfcbomconselho', 'pass' => 'admin123', 'label' => 'Senha comum'],
    ['user' => 'cfcbomconselho', 'pass' => 'cfcbomconselho', 'label' => 'Sem prefixo usuário/banco'],
    ['user' => 'u502697186', 'pass' => 'u502697186', 'label' => 'Apenas prefixo']
];

foreach ($tests as $i => $test) {
    echo "<h3>🧪 Teste " . ($i + 1) . ": {$test['label']}</h3>\n";
    
    try {
        $dsn = "mysql:host=auth-db1607.hstgr.io;dbname=u502697186_cfcbomconselho;charset=utf8mb4";
        $pdo = new PDO($dsn, $test['user'], $test['pass'], [
            PDO::ATTR_TIMEOUT => 10,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px;'>\n";
        echo "<h4>🎉 SUCESSO! Credenciais encontradas!</h4>\n";
        echo "<strong>Usuário correto:</strong> " . $test['user'] . "<br>\n";
        echo "<strong>Senha correta:</strong> " . $test['pass'] . "<br>\n";
        
        // Testar consulta
        $result = $pdo->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = 'u502697186_cfcbomconselho'");
        $tableCount = $result->fetch()['count'];
        echo "<strong>Tabelas no banco:</strong> " . $tableCount . "<br>\n";
        
        if ($tableCount > 0) {
            echo "<strong>Status:</strong> Banco pronto para uso ✅<br>\n";
        } else {
            echo "<strong>Status:</strong> Banco vazio - precisa importar estrutura ⚠️<br>\n";
        }
        
        echo "</div>\n";
        
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0;'>\n";
        echo "<h4>🔧 Próximo passo:</h4>\n";
        echo "<p>Atualize o arquivo <code>includes/config.php</code> com:</p>\n";
        echo "<pre>define('DB_USER', '" . $test['user'] . "');\n";
        echo "define('DB_PASS', '" . $test['pass'] . "');</pre>\n";
        echo "</div>\n";
        
        break;
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>\n";
        echo "❌ Falhou: " . $e->getMessage() . "<br>\n";
        echo "</div>\n";
    }
    
    echo "<hr>\n";
}

echo "<h3>💡 Se todos falharam:</h3>\n";
echo "<ol>\n";
echo "<li>Vá na Hostinger → Bancos de Dados MySQL</li>\n";
echo "<li>Verifique o nome <strong>real</strong> do usuário e senha</li>\n";
echo "<li>Ou crie um novo banco com credenciais conhecidas</li>\n";
echo "<li>Execute este teste novamente</li>\n";
echo "</ol>\n";

echo "<br><a href='login.php'>🔗 Testar Login</a> | <a href='test_credentials.php'>🔄 Voltar Teste Anterior</a>\n";
?>
