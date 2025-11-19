<?php
/**
 * Script para executar a correção da Foreign Key de Pagamentos
 * 
 * Este script corrige a foreign key da tabela pagamentos para apontar
 * para financeiro_faturas em vez de faturas (antiga).
 * 
 * USO: Execute este arquivo uma vez via navegador ou linha de comando
 */

// Ajustar caminhos baseado em onde o script está sendo executado
$baseDir = dirname(__DIR__, 2); // Volta 2 níveis de admin/migrations para a raiz
require_once $baseDir . '/includes/config.php';
require_once $baseDir . '/includes/database.php';

// Verificar se está rodando via CLI ou web
$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    // Verificar autenticação se for via web
    require_once '../../includes/auth.php';
    if (!isLoggedIn() || !in_array(getCurrentUser()['tipo'], ['admin'])) {
        die('Acesso negado. Apenas administradores podem executar migrations.');
    }
}

echo "=== Correção de Foreign Key: Pagamentos -> Financeiro Faturas ===\n\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Ler o arquivo SQL
    $sqlFile = __DIR__ . '/009-fix-pagamentos-foreign-key-to-financeiro-faturas.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Arquivo de migration não encontrado: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Dividir em comandos individuais (separados por ;)
    // Remover comentários e linhas vazias
    $commands = array_filter(
        array_map('trim', explode(';', $sql)),
        function($cmd) {
            return !empty($cmd) && 
                   !preg_match('/^--/', $cmd) && 
                   !preg_match('/^\/\*/', $cmd) &&
                   strtoupper(trim($cmd)) !== 'SELECT';
        }
    );
    
    echo "Executando migration...\n\n";
    
    // Primeiro, tentar remover foreign keys antigas
    echo "Removendo foreign keys antigas...\n";
    
    // Buscar nome da constraint atual
    $stmt = $pdo->query("
        SELECT CONSTRAINT_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'pagamentos'
          AND COLUMN_NAME = 'fatura_id'
          AND REFERENCED_TABLE_NAME IS NOT NULL
        LIMIT 1
    ");
    
    $oldConstraint = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($oldConstraint) {
        $constraintName = $oldConstraint['CONSTRAINT_NAME'];
        echo "Encontrada constraint: $constraintName\n";
        
        try {
            $pdo->exec("ALTER TABLE pagamentos DROP FOREIGN KEY `$constraintName`");
            echo "✓ Foreign key antiga removida: $constraintName\n\n";
        } catch (PDOException $e) {
            echo "⚠ Erro ao remover constraint (pode já ter sido removida): " . $e->getMessage() . "\n\n";
        }
    } else {
        echo "⚠ Nenhuma foreign key encontrada para remover (pode já ter sido removida)\n\n";
    }
    
    // Adicionar nova foreign key diretamente
    echo "Adicionando nova foreign key...\n";
    try {
        $pdo->exec("
            ALTER TABLE pagamentos
            ADD CONSTRAINT fk_pagamentos_financeiro_faturas
            FOREIGN KEY (fatura_id) REFERENCES financeiro_faturas(id) ON DELETE CASCADE
        ");
        echo "✓ Nova foreign key criada com sucesso!\n\n";
    } catch (PDOException $e) {
        // Se for erro de constraint já existe
        if (strpos($e->getMessage(), 'Duplicate key name') !== false || 
            strpos($e->getMessage(), 'already exists') !== false ||
            strpos($e->getMessage(), 'Duplicate foreign key') !== false) {
            echo "⚠ Constraint já existe (pode já ter sido criada): " . $e->getMessage() . "\n\n";
        } else {
            throw $e;
        }
    }
    
    // Verificar resultado final
    echo "Verificando resultado...\n\n";
    
    $stmt = $pdo->query("
        SELECT 
            CONSTRAINT_NAME,
            TABLE_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'pagamentos'
          AND COLUMN_NAME = 'fatura_id'
          AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($result)) {
        echo "⚠ Nenhuma foreign key encontrada para pagamentos.fatura_id\n";
        echo "Isso pode significar que a constraint não foi criada ou já foi removida.\n\n";
    } else {
        echo "✓ Foreign Key encontrada:\n";
        foreach ($result as $fk) {
            echo "  - Constraint: {$fk['CONSTRAINT_NAME']}\n";
            echo "  - Tabela: {$fk['TABLE_NAME']}.{$fk['COLUMN_NAME']}\n";
            echo "  - Referência: {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
            
            if ($fk['REFERENCED_TABLE_NAME'] === 'financeiro_faturas') {
                echo "  ✓ CORRETO: Apontando para financeiro_faturas\n\n";
            } else {
                echo "  ✗ ATENÇÃO: Ainda apontando para {$fk['REFERENCED_TABLE_NAME']} (deveria ser financeiro_faturas)\n\n";
            }
        }
    }
    
    echo "\n=== Migration concluída! ===\n";
    echo "Agora você pode testar registrar um pagamento.\n";
    
} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

