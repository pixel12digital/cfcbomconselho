<?php
/**
 * Script de Execução - ETAPA 1.1: Estrutura de Banco
 * Sistema de Turmas Teóricas - CFC Bom Conselho
 */

// Incluir configurações do sistema
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

echo "🚀 INICIANDO ETAPA 1.1: ESTRUTURA DE BANCO\n";
echo "==========================================\n\n";

try {
    $db = Database::getInstance();
    
    // Ler o arquivo SQL
    $sqlFile = __DIR__ . '/fase1_etapa1_estrutura_banco.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Arquivo SQL não encontrado: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
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
                strpos($command, 'ALTER TABLE') !== false ||
                strpos($command, 'CREATE INDEX') !== false) {
                echo "✅ " . substr($command, 0, 50) . "...\n";
            }
            
        } catch (Exception $e) {
            $errorCount++;
            echo "❌ ERRO: " . $e->getMessage() . "\n";
            echo "   Comando: " . substr($command, 0, 100) . "...\n\n";
        }
    }
    
    echo "\n==========================================\n";
    echo "📊 RELATÓRIO DE EXECUÇÃO:\n";
    echo "✅ Comandos executados com sucesso: $successCount\n";
    echo "❌ Comandos com erro: $errorCount\n";
    
    if ($errorCount === 0) {
        echo "🎉 ETAPA 1.1 CONCLUÍDA COM SUCESSO!\n";
    } else {
        echo "⚠️  ETAPA 1.1 CONCLUÍDA COM ALGUNS ERROS\n";
    }
    
    // Executar validação
    echo "\n🔍 EXECUTANDO VALIDAÇÃO...\n";
    echo "==========================================\n";
    
    // Verificar tabelas criadas
    $tabelas = $db->fetchAll("
        SELECT TABLE_NAME 
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME IN ('turma_presencas', 'turma_diario')
    ");
    
    echo "📋 Tabelas criadas: " . count($tabelas) . "/2\n";
    foreach ($tabelas as $tabela) {
        echo "   ✅ " . $tabela['TABLE_NAME'] . "\n";
    }
    
    // Verificar campos adicionados em turmas
    $camposTurmas = $db->fetchAll("
        SELECT COLUMN_NAME 
        FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'turmas' 
        AND COLUMN_NAME IN ('capacidade_maxima', 'frequencia_minima', 'sala_local', 'link_online')
    ");
    
    echo "📋 Campos em turmas: " . count($camposTurmas) . "/4\n";
    foreach ($camposTurmas as $campo) {
        echo "   ✅ " . $campo['COLUMN_NAME'] . "\n";
    }
    
    // Verificar campos adicionados em aulas_slots
    $camposSlots = $db->fetchAll("
        SELECT COLUMN_NAME 
        FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'aulas_slots' 
        AND COLUMN_NAME IN ('turma_id', 'turma_aula_id')
    ");
    
    echo "📋 Campos em aulas_slots: " . count($camposSlots) . "/2\n";
    foreach ($camposSlots as $campo) {
        echo "   ✅ " . $campo['COLUMN_NAME'] . "\n";
    }
    
    // Verificar foreign keys
    $foreignKeys = $db->fetchAll("
        SELECT 
            TABLE_NAME,
            CONSTRAINT_NAME,
            REFERENCED_TABLE_NAME
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME IN ('turma_presencas', 'turma_diario', 'aulas_slots')
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    echo "📋 Foreign keys criadas: " . count($foreignKeys) . "\n";
    foreach ($foreignKeys as $fk) {
        echo "   ✅ " . $fk['TABLE_NAME'] . " → " . $fk['REFERENCED_TABLE_NAME'] . "\n";
    }
    
    // Verificar views
    $views = $db->fetchAll("
        SELECT TABLE_NAME 
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_TYPE = 'VIEW'
        AND TABLE_NAME IN ('vw_frequencia_alunos', 'vw_turmas_resumo')
    ");
    
    echo "📋 Views criadas: " . count($views) . "/2\n";
    foreach ($views as $view) {
        echo "   ✅ " . $view['TABLE_NAME'] . "\n";
    }
    
    echo "\n==========================================\n";
    echo "🎯 PRÓXIMA ETAPA: 1.2 - API de Presença\n";
    echo "📁 Arquivo: admin/api/turma-presencas.php\n";
    echo "⏰ Estimativa: 2 dias\n";
    echo "==========================================\n";
    
} catch (Exception $e) {
    echo "❌ ERRO CRÍTICO: " . $e->getMessage() . "\n";
    echo "📞 Contate o suporte técnico\n";
    exit(1);
}
?>
