<?php
/**
 * TESTE #1: Conectividade com Banco de Dados
 * Este teste verifica se o sistema consegue se conectar ao banco e se a estrutura básica está funcionando
 */

// Configurações de teste
$testes = [];
$erros = [];
$sucessos = [];

echo "<h1>🔍 TESTE #1: Conectividade com Banco de Dados</h1>";
echo "<p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>";
echo "<p><strong>Ambiente:</strong> " . ($_SERVER['SERVER_PORT'] == '8080' ? 'XAMPP Local (Porta 8080)' : 'Produção') . "</p>";
echo "<p><strong>Banco:</strong> Remoto (Hostinger)</p>";
echo "<hr>";

// Teste 1.1: Verificar se os arquivos de configuração existem
echo "<h2>1.1 Verificação de Arquivos de Configuração</h2>";

$arquivos_necessarios = [
    '../includes/config.php',
    '../includes/database.php',
    '../includes/auth.php'
];

foreach ($arquivos_necessarios as $arquivo) {
    if (file_exists($arquivo)) {
        echo "✅ <strong>$arquivo</strong> - EXISTE<br>";
        $sucessos[] = "Arquivo $arquivo encontrado";
    } else {
        echo "❌ <strong>$arquivo</strong> - NÃO ENCONTRADO<br>";
        $erros[] = "Arquivo $arquivo não encontrado";
    }
}

// Teste 1.2: Verificar se conseguimos incluir os arquivos
echo "<h2>1.2 Teste de Inclusão de Arquivos</h2>";

try {
    require_once '../includes/config.php';
    echo "✅ <strong>config.php</strong> - INCLUÍDO COM SUCESSO<br>";
    $sucessos[] = "config.php incluído com sucesso";
} catch (Exception $e) {
    echo "❌ <strong>config.php</strong> - ERRO AO INCLUIR: " . $e->getMessage() . "<br>";
    $erros[] = "Erro ao incluir config.php: " . $e->getMessage();
}

try {
    require_once '../includes/database.php';
    echo "✅ <strong>database.php</strong> - INCLUÍDO COM SUCESSO<br>";
    $sucessos[] = "database.php incluído com sucesso";
} catch (Exception $e) {
    echo "❌ <strong>database.php</strong> - ERRO AO INCLUIR: " . $e->getMessage() . "<br>";
    $erros[] = "Erro ao incluir database.php: " . $e->getMessage();
}

// Teste 1.3: Verificar se as constantes de configuração estão definidas
echo "<h2>1.3 Verificação de Constantes de Configuração</h2>";

$constantes_necessarias = [
    'DB_HOST',
    'DB_NAME', 
    'DB_USER',
    'DB_PASS'
];

foreach ($constantes_necessarias as $constante) {
    if (defined($constante)) {
        echo "✅ <strong>$constante</strong> - DEFINIDA: " . constant($constante) . "<br>";
        $sucessos[] = "Constante $constante definida";
    } else {
        echo "❌ <strong>$constante</strong> - NÃO DEFINIDA<br>";
        $erros[] = "Constante $constante não definida";
    }
}

// Teste 1.4: Teste de Conexão com Banco
echo "<h2>1.4 Teste de Conexão com Banco de Dados (REMOTO)</h2>";
echo "<p><strong>Host:</strong> " . DB_HOST . "</p>";
echo "<p><strong>Database:</strong> " . DB_NAME . "</p>";
echo "<p><strong>Usuário:</strong> " . DB_USER . "</p>";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 30 // Timeout para conexão remota
        ]
    );
    
    echo "✅ <strong>Conexão PDO</strong> - ESTABELECIDA COM SUCESSO<br>";
    $sucessos[] = "Conexão PDO estabelecida";
    
    // Teste de query simples
    $stmt = $pdo->query("SELECT 1 as teste");
    $resultado = $stmt->fetch();
    
    if ($resultado && $resultado['teste'] == 1) {
        echo "✅ <strong>Query de Teste</strong> - EXECUTADA COM SUCESSO<br>";
        $sucessos[] = "Query de teste executada com sucesso";
    } else {
        echo "❌ <strong>Query de Teste</strong> - FALHOU<br>";
        $erros[] = "Query de teste falhou";
    }
    
} catch (PDOException $e) {
    echo "❌ <strong>Conexão PDO</strong> - ERRO: " . $e->getMessage() . "<br>";
    $erros[] = "Erro na conexão PDO: " . $e->getMessage();
}

// Teste 1.5: Verificar se as tabelas principais existem
echo "<h2>1.5 Verificação de Tabelas do Banco</h2>";

if (isset($pdo)) {
    $tabelas_necessarias = [
        'usuarios',
        'cfcs', 
        'alunos',
        'instrutores',
        'veiculos',
        'aulas',
        'logs'
    ];
    
    foreach ($tabelas_necessarias as $tabela) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$tabela'");
            if ($stmt->rowCount() > 0) {
                echo "✅ <strong>Tabela $tabela</strong> - EXISTE<br>";
                $sucessos[] = "Tabela $tabela existe";
            } else {
                echo "❌ <strong>Tabela $tabela</strong> - NÃO EXISTE<br>";
                $erros[] = "Tabela $tabela não existe";
            }
        } catch (Exception $e) {
            echo "❌ <strong>Tabela $tabela</strong> - ERRO AO VERIFICAR: " . $e->getMessage() . "<br>";
            $erros[] = "Erro ao verificar tabela $tabela: " . $e->getMessage();
        }
    }
}

// Teste 1.6: Verificar versão do PHP e extensões
echo "<h2>1.6 Verificação do Ambiente PHP</h2>";

echo "✅ <strong>Versão PHP:</strong> " . PHP_VERSION . "<br>";
$sucessos[] = "Versão PHP: " . PHP_VERSION;

$extensoes_necessarias = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
foreach ($extensoes_necessarias as $extensao) {
    if (extension_loaded($extensao)) {
        echo "✅ <strong>Extensão $extensao</strong> - CARREGADA<br>";
        $sucessos[] = "Extensão $extensao carregada";
    } else {
        echo "❌ <strong>Extensão $extensao</strong> - NÃO CARREGADA<br>";
        $erros[] = "Extensão $extensao não carregada";
    }
}

// Resumo dos Testes
echo "<hr>";
echo "<h2>📊 RESUMO DOS TESTES</h2>";

echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>✅ SUCESSOS (" . count($sucessos) . ")</h3>";
foreach ($sucessos as $sucesso) {
    echo "• $sucesso<br>";
}
echo "</div>";

if (count($erros) > 0) {
    echo "<div style='background: #ffe8e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>❌ ERROS (" . count($erros) . ")</h3>";
    foreach ($erros as $erro) {
        echo "• $erro<br>";
    }
    echo "</div>";
}

// Status Final
$total_testes = count($sucessos) + count($erros);
$percentual_sucesso = $total_testes > 0 ? round(($total_testes - count($erros)) / $total_testes * 100, 1) : 0;

echo "<div style='background: " . (count($erros) == 0 ? '#d4edda' : '#f8d7da') . "; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>🎯 STATUS FINAL</h3>";
echo "<strong>Total de Testes:</strong> $total_testes<br>";
echo "<strong>Sucessos:</strong> " . count($sucessos) . "<br>";
echo "<strong>Erros:</strong> " . count($erros) . "<br>";
echo "<strong>Taxa de Sucesso:</strong> $percentual_sucesso%<br>";

if (count($erros) == 0) {
    echo "<br><strong style='color: #155724;'>🎉 TODOS OS TESTES PASSARAM! Sistema pronto para próximo teste.</strong>";
} else {
    echo "<br><strong style='color: #721c24;'>⚠️ Existem erros que precisam ser corrigidos antes de prosseguir.</strong>";
}
echo "</div>";

// Próximo Passo
echo "<hr>";
echo "<h2>🔄 PRÓXIMO PASSO</h2>";
if (count($erros) == 0) {
    echo "<p>✅ <strong>TESTE #1 CONCLUÍDO COM SUCESSO!</strong></p>";
    echo "<p>🎯 <strong>Próximo:</strong> TESTE #2 - Estrutura de Arquivos e Diretórios</p>";
    echo "<p>📝 <strong>Instrução:</strong> Execute este teste e me informe o resultado. Se tudo estiver OK, criarei o próximo teste.</p>";
} else {
    echo "<p>❌ <strong>TESTE #1 COM ERROS!</strong></p>";
    echo "<p>🔧 <strong>Ação Necessária:</strong> Corrija os erros listados acima e execute novamente.</p>";
    echo "<p>📝 <strong>Instrução:</strong> Me informe quais erros apareceram para que eu possa ajudar a corrigi-los.</p>";
}

// Informações adicionais para XAMPP
echo "<hr>";
echo "<h2>💡 INFORMAÇÕES PARA XAMPP</h2>";
echo "<p><strong>URL de Teste:</strong> <code>http://localhost:8080/cfc-bom-conselho/admin/teste-01-conectividade.php</code></p>";
echo "<p><strong>Porta XAMPP:</strong> 8080</p>";
echo "<p><strong>Banco Remoto:</strong> Hostinger (auth-db1607.hstgr.io)</p>";
echo "<p><strong>Timeout de Conexão:</strong> 30 segundos (configurado para conexão remota)</p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #2c3e50; }
h2 { color: #34495e; margin-top: 30px; }
h3 { color: #7f8c8d; }
hr { border: 1px solid #ecf0f1; margin: 20px 0; }
</style>
