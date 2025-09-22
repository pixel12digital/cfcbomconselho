<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

echo "🚀 EXECUTANDO SCRIPT SISTEMA_TURMAS.SQL\n";
echo "=====================================\n";

$db = Database::getInstance();
$sql = file_get_contents('sistema_turmas.sql');

// Dividir em comandos individuais
$commands = explode(';', $sql);

$successCount = 0;
$errorCount = 0;

foreach ($commands as $command) {
    $command = trim($command);
    
    // Pular comandos vazios ou comentários
    if (empty($command) || strpos($command, '--') === 0) {
        continue;
    }
    
    try {
        // Executar comando
        $db->query($command);
        $successCount++;
        
        // Log de sucesso para comandos importantes
        if (strpos($command, 'CREATE TABLE') !== false || 
            strpos($command, 'INSERT INTO') !== false ||
            strpos($command, 'CREATE VIEW') !== false) {
            echo "✅ " . substr($command, 0, 60) . "...\n";
        }
        
    } catch (Exception $e) {
        $errorCount++;
        echo "❌ ERRO: " . $e->getMessage() . "\n";
        echo "   Comando: " . substr($command, 0, 100) . "...\n\n";
    }
}

echo "\n=====================================\n";
echo "📊 RELATÓRIO:\n";
echo "✅ Comandos executados: $successCount\n";
echo "❌ Comandos com erro: $errorCount\n";

if ($errorCount === 0) {
    echo "🎉 SCRIPT EXECUTADO COM SUCESSO!\n";
} else {
    echo "⚠️  SCRIPT EXECUTADO COM ALGUNS ERROS\n";
}

// Verificar se as tabelas foram criadas
echo "\n🔍 VERIFICANDO TABELAS CRIADAS:\n";
$tables = $db->fetchAll('SHOW TABLES');
foreach($tables as $table) {
    $tableName = $table[array_keys($table)[0]];
    if (in_array($tableName, ['turmas', 'turma_aulas', 'turma_alunos'])) {
        echo "✅ $tableName\n";
    }
}
?>
