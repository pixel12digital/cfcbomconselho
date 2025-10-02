<?php
// =====================================================
// TESTE DE CONEXÃO COM BANCO REMOTO DA HOSTINGER
// =====================================================

// Configurações do banco (mesmo do config.php)
define('DB_HOST', 'auth-db1607.hstgr.io');
define('DB_NAME', 'u502697186_cfcbomconselho');
define('DB_USER', 'u502697186_cfcbomconselho');
define('DB_PASS', 'Los@ngo#081081');
define('DB_CHARSET', 'utf8mb4');

echo "<h2>🔍 Teste de Conexão com Banco da Hostinger</h2>\n";
echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 20px 0;'>\n";
echo "<h3>📋 Informações de Conexão</h3>\n";
echo "<strong>Host:</strong> " . DB_HOST . "<br>\n";
echo "<strong>Banco:</strong> " . DB_NAME . "<br>\n";
echo "<strong>Usuário:</strong> " . DB_USER . "<br>\n";
echo "<strong>Senha:</strong> " . (empty(DB_PASS) ? '(vazia)' : '***configurada***') . "<br>\n";
echo "<strong>Charset:</strong> " . DB_CHARSET . "<br>\n";
echo "<strong>IP Local:</strong> " . ($_SERVER['REMOTE_ADDR'] ?? 'Não detectado') . "<br>\n";
echo "<strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "<br>\n";
echo "</div>\n";
echo "<hr>\n";

// Teste 1: Conexão básica
echo "<h3>1️⃣ Teste de Conexão</h3>\n";
try {
    $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo "✅ <strong>Conexão estabelecida com sucesso!</strong><br>\n";
    echo "Host: " . DB_HOST . "<br>\n";
    echo "Usuário: " . DB_USER . "<br>\n";
} catch (PDOException $e) {
    echo "❌ <strong>Erro na conexão:</strong> " . $e->getMessage() . "<br>\n";
    echo "<strong>Código do erro:</strong> " . $e->getCode() . "<br>\n";
    exit("🛑 Teste interrompido por erro de conexão.\n");
}

// Teste 2: Verificar se o banco existe
echo "<h3>2️⃣ Verificação do Banco</h3>\n";
try {
    $result = $pdo->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
    $databases = $result->fetchAll();
    
    if (count($databases) > 0) {
        echo "✅ <strong>Banco encontrado!</strong><br>\n";
        echo "Nome: " . DB_NAME . "<br>\n";
        
        // Teste 3: Conectar ao banco específico
        echo "<h3>3️⃣ Conectar ao Banco</h3>\n";
        try {
            $dsn_db = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo_db = new PDO($dsn_db, DB_USER, DB_PASS, $options);
            echo "✅ <strong>Conexão ao banco específico OK!</strong><br>\n";
            
            // Teste 4: Verificar tabelas
            echo "<h3>4️⃣ Verificação de Tabelas</h3>\n";
            $tables_result = $pdo_db->query("SHOW TABLES");
            $tables = $tables_result->fetchAll();
            
            if (count($tables) > 0) {
                echo "✅ <strong>Tabelas encontradas:</strong> " . count($tables) . "<br>\n";
                echo "<strong>Lista de tabelas:</strong><br>\n";
                echo "<ul>\n";
                foreach ($tables as $table) {
                    echo "<li>" . reset($table) . "</li>\n";
                }
                echo "</ul>\n";
            } else {
                echo "⚠️ <strong>Banco vazio:</strong> Nenhuma tabela encontrada.<br>\n";
                echo "<strong>Solução:</strong> Importe o arquivo database_structure.sql<br>\n";
            }
            
        } catch (PDOException $e) {
            echo "❌ <strong>Erro ao conectar no banco:</strong> " . $e->getMessage() . "<br>\n";
        }
        
    } else {
        echo "❌ <strong>Banco não encontrado!</strong><br>\n";
        echo "<strong>Solução:</strong> Crie o banco 'cfcbomconselho' na Hostinger<br>\n";
    }
    
} catch (PDOException $e) {
    echo "❌ <strong>Erro ao verificar bancos:</strong> " . $e->getMessage() . "<br>\n";
}

// Teste 5: Informações do servidor
echo "<h3>5️⃣ Informações do Servidor</h3>\n";
try {
    $result = $pdo->query("SELECT @@hostname as hostname, @@version as version, NOW() as current_time");
    $info = $result->fetch();
    
    echo "✅ <strong>Informações do servidor:</strong><br>\n";
    echo "Hostname: " . $info['hostname'] . "<br>\n";
    echo "Versão MySQL: " . $info['version'] . "<br>\n";
    echo "Hora atual: " . $info['current_time'] . "<br>\n";
    
} catch (PDOException $e) {
    echo "❌ <strong>Erro ao obter info do servidor:</strong> " . $e->getMessage() . "<br>\n";
}

// Teste 6: Verificar permissões do usuário
echo "<h3>6️⃣ Verificação de Permissões</h3>\n";
try {
    $result = $pdo->query("SHOW GRANTS FOR CURRENT_USER()");
    $grants = $result->fetchAll();
    
    echo "✅ <strong>Permissões do usuário:</strong><br>\n";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>\n";
    foreach ($grants as $grant) {
        echo reset($grant) . "\n";
    }
    echo "</pre>\n";
    
} catch (PDOException $e) {
    echo "❌ <strong>Erro ao verificar permissões:</strong> " . $e->getMessage() . "<br>\n";
}

echo "<br><hr>\n";
echo "<h3>📋 Resumo</h3>\n";
echo "✅ Se todos os testes passaram, seu banco está pronto!<br>\n";
echo "⚠️ Se algum teste falhou, verifique as configurações na Hostinger.<br>\n";
echo "<strong>Importante:</strong> Certifique-se de que o acesso remoto está liberado.<br>\n";

echo "<br><a href='login.php'>🔗 Testar Login</a>\n";
?>
