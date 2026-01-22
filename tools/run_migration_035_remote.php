<?php
/**
 * Script de Execução da Migration 035 - Adicionar campos de endereço estruturado na tabela cfcs
 * 
 * ✅ SEGURO PARA PRODUÇÃO
 * Execute via SSH: php tools/run_migration_035_remote.php
 * 
 * Este script pode ser executado em qualquer ambiente (local ou produção)
 */

// Inicialização
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

// Autoload
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
} else {
    require_once APP_PATH . '/autoload.php';
}

// Carregar variáveis de ambiente
use App\Config\Env;
use App\Config\Database;
Env::load();

echo "=== EXECUTANDO MIGRATION 035 - ADICIONAR CAMPOS DE ENDEREÇO ESTRUTURADO NA TABELA CFCS ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar banco atual
    echo "1. Verificando conexão com banco de dados...\n";
    $stmt = $db->query("SELECT DATABASE() as current_db");
    $currentDb = $stmt->fetch();
    $dbName = $_ENV['DB_NAME'] ?? 'cfc_db';
    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    
    echo "   Banco configurado: {$dbName}\n";
    echo "   Banco em uso: " . ($currentDb['current_db'] ?? 'N/A') . "\n";
    echo "   Host: {$dbHost}\n\n";
    
    // Verificar se a tabela cfcs existe
    echo "2. Verificando tabela cfcs...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'cfcs'");
    if ($stmt->rowCount() === 0) {
        die("   ❌ ERRO: Tabela 'cfcs' não existe!\n");
    }
    echo "   ✅ Tabela 'cfcs' existe\n\n";
    
    // Função para verificar se uma coluna existe
    $columnExists = function($table, $column) use ($db) {
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    };
    
    // Lista de colunas a adicionar
    $columns = [
        'endereco_logradouro' => [
            'type' => 'VARCHAR(255)',
            'after' => 'endereco',
            'comment' => 'Logradouro do endereço do CFC'
        ],
        'endereco_numero' => [
            'type' => 'VARCHAR(20)',
            'after' => 'endereco_logradouro',
            'comment' => 'Número do endereço do CFC'
        ],
        'endereco_complemento' => [
            'type' => 'VARCHAR(150)',
            'after' => 'endereco_numero',
            'comment' => 'Complemento do endereço do CFC'
        ],
        'endereco_bairro' => [
            'type' => 'VARCHAR(120)',
            'after' => 'endereco_complemento',
            'comment' => 'Bairro do endereço do CFC'
        ],
        'endereco_cidade' => [
            'type' => 'VARCHAR(120)',
            'after' => 'endereco_bairro',
            'comment' => 'Cidade do endereço do CFC'
        ],
        'endereco_uf' => [
            'type' => 'CHAR(2)',
            'after' => 'endereco_cidade',
            'comment' => 'UF do endereço do CFC'
        ],
        'endereco_cep' => [
            'type' => 'VARCHAR(10)',
            'after' => 'endereco_uf',
            'comment' => 'CEP do endereço do CFC'
        ]
    ];
    
    echo "3. Verificando e adicionando colunas de endereço estruturado...\n";
    
    $addedCount = 0;
    $skippedCount = 0;
    $lastColumn = 'endereco'; // Coluna base para posicionamento
    
    foreach ($columns as $columnName => $columnDef) {
        if ($columnExists('cfcs', $columnName)) {
            echo "   ⏭️  Coluna '{$columnName}' já existe, pulando...\n";
            $skippedCount++;
            $lastColumn = $columnName; // Atualizar para próxima iteração
            continue;
        }
        
        echo "   ➕ Adicionando coluna '{$columnName}'...\n";
        
        // Verificar se coluna base existe
        if (!$columnExists('cfcs', $lastColumn)) {
            echo "      ⚠️  Coluna base '{$lastColumn}' não existe. Adicionando no final da tabela...\n";
            $sql = "ALTER TABLE `cfcs` 
                    ADD COLUMN `{$columnName}` {$columnDef['type']} DEFAULT NULL 
                    COMMENT '{$columnDef['comment']}'";
        } else {
            $sql = "ALTER TABLE `cfcs` 
                    ADD COLUMN `{$columnName}` {$columnDef['type']} DEFAULT NULL 
                    COMMENT '{$columnDef['comment']}' 
                    AFTER `{$lastColumn}`";
        }
        
        try {
            $db->exec($sql);
            echo "      ✅ Coluna '{$columnName}' adicionada com sucesso\n";
            $addedCount++;
            $lastColumn = $columnName; // Atualizar para próxima iteração
        } catch (\PDOException $e) {
            echo "      ❌ Erro ao adicionar coluna '{$columnName}': " . $e->getMessage() . "\n";
            // Continuar com próxima coluna mesmo se uma falhar
        }
    }
    
    echo "\n";
    
    // Verificação final
    echo "4. Verificação final...\n";
    $allExist = true;
    foreach (array_keys($columns) as $columnName) {
        if ($columnExists('cfcs', $columnName)) {
            echo "   ✅ Coluna '{$columnName}' existe\n";
        } else {
            echo "   ❌ Coluna '{$columnName}' NÃO existe!\n";
            $allExist = false;
        }
    }
    
    echo "\n";
    
    if ($allExist) {
        echo "✅ MIGRATION 035 EXECUTADA COM SUCESSO!\n";
        echo "\nResumo:\n";
        echo "   - Colunas adicionadas: {$addedCount}\n";
        echo "   - Colunas já existentes: {$skippedCount}\n";
        echo "   - Total de colunas: " . count($columns) . "\n";
        echo "\nOs campos de endereço estruturado foram adicionados à tabela cfcs.\n";
        echo "Agora você pode preencher o endereço do CFC de forma estruturada.\n";
        echo "\nPróximos passos:\n";
        echo "1. Acesse /configuracoes/cfc no sistema\n";
        echo "2. Preencha os campos de endereço estruturado\n";
        echo "3. O sistema manterá compatibilidade com o campo endereco (TEXT) antigo\n";
    } else {
        echo "⚠️  MIGRATION PARCIALMENTE EXECUTADA\n";
        echo "Algumas colunas não foram adicionadas. Verifique os erros acima.\n";
        exit(1);
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
