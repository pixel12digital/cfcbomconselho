<?php
/**
 * Script CLI para executar migration: Tabela smtp_settings
 * Executar: php admin/tools/executar-migration-smtp-settings-cli.php
 * 
 * Este script cria a tabela smtp_settings no banco remoto.
 */

// Verificar se está sendo executado via CLI
if (php_sapi_name() !== 'cli' && !isset($_GET['force'])) {
    die("Este script deve ser executado via CLI ou acesse: admin/tools/executar-migration-smtp-settings.php\n");
}

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';

echo "========================================\n";
echo "Migration: Tabela smtp_settings\n";
echo "========================================\n\n";

try {
    $db = db();
    
    echo "📋 Informações:\n";
    echo "• Tabela: smtp_settings\n";
    echo "• Propósito: Configurações SMTP do painel admin\n";
    echo "• Segurança: Senha criptografada (AES-256-CBC)\n\n";
    
    // Ler arquivo SQL
    $sqlFile = __DIR__ . '/../../docs/scripts/migration-smtp-settings.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Arquivo SQL não encontrado: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Verificar se tabela já existe
    echo "🔍 Verificando se tabela existe...\n";
    $tableExists = $db->fetch(
        "SELECT COUNT(*) as count
         FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = 'smtp_settings'"
    );
    
    if ($tableExists['count'] > 0) {
        echo "✅ A tabela smtp_settings já existe.\n\n";
        
        // Verificar estrutura
        $columns = $db->fetchAll(
            "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = 'smtp_settings'
             ORDER BY ORDINAL_POSITION"
        );
        
        echo "📊 Estrutura atual:\n";
        foreach ($columns as $col) {
            $nullable = $col['IS_NULLABLE'] === 'YES' ? 'NULL' : 'NOT NULL';
            echo "  - {$col['COLUMN_NAME']}: {$col['DATA_TYPE']} ($nullable)\n";
        }
        echo "\n";
    } else {
        echo "⚠️ Tabela não existe. Criando agora...\n\n";
        
        // Executar migration
        $db->query($sql);
        
        echo "✅ Migration executada com sucesso!\n";
        echo "✅ Tabela smtp_settings criada.\n\n";
        
        // Verificar estrutura criada
        $columns = $db->fetchAll(
            "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = 'smtp_settings'
             ORDER BY ORDINAL_POSITION"
        );
        
        echo "📊 Estrutura criada:\n";
        foreach ($columns as $col) {
            $nullable = $col['IS_NULLABLE'] === 'YES' ? 'NULL' : 'NOT NULL';
            echo "  - {$col['COLUMN_NAME']}: {$col['DATA_TYPE']} ($nullable)\n";
        }
        echo "\n";
    }
    
    // Verificar índices
    $indexes = $db->fetchAll(
        "SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as columns
         FROM INFORMATION_SCHEMA.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = 'smtp_settings'
         GROUP BY INDEX_NAME
         ORDER BY INDEX_NAME"
    );
    
    if (!empty($indexes)) {
        echo "🔍 Índices:\n";
        foreach ($indexes as $idx) {
            echo "  - {$idx['INDEX_NAME']}: {$idx['columns']}\n";
        }
        echo "\n";
    }
    
    // Log de auditoria
    if (LOG_ENABLED) {
        $logMessage = sprintf(
            '[MIGRATION] smtp_settings executada via CLI - Timestamp: %s',
            date('Y-m-d H:i:s')
        );
        error_log($logMessage);
    }
    
    echo "========================================\n";
    echo "✅ Migration concluída com sucesso!\n";
    echo "========================================\n";
    echo "\nPróximos passos:\n";
    echo "1. Acesse: admin/index.php?page=configuracoes-smtp\n";
    echo "2. Configure as credenciais SMTP\n";
    echo "3. Teste o envio de e-mail\n\n";
    
} catch (Exception $e) {
    echo "========================================\n";
    echo "❌ ERRO ao executar migration:\n";
    echo "========================================\n";
    echo $e->getMessage() . "\n\n";
    
    if (LOG_ENABLED) {
        error_log('[MIGRATION] Erro ao executar smtp_settings: ' . $e->getMessage());
    }
    
    exit(1);
}
