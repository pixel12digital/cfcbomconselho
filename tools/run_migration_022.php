<?php

require_once __DIR__ . '/../app/autoload.php';
require_once __DIR__ . '/../app/Bootstrap.php';

use App\Config\Database;

$db = Database::getInstance()->getConnection();

echo "=== Executando Migration 022 (Notificações) ===\n\n";

try {
    $sql = file_get_contents(__DIR__ . '/../database/migrations/022_create_notifications_table.sql');
    
    // Remover comentários
    $sql = preg_replace('/--.*$/m', '', $sql);
    
    // Executar cada statement
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && strlen($statement) > 5) {
            try {
                $db->exec($statement);
            } catch (\PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate') === false && 
                    strpos($e->getMessage(), 'already exists') === false) {
                    throw $e;
                }
            }
        }
    }
    
    echo "✅ Tabela notifications criada com sucesso!\n";
} catch (\Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate') !== false || 
        strpos($e->getMessage(), 'already exists') !== false) {
        echo "⚠️  Tabela já existe (ignorado)\n";
    } else {
        echo "❌ Erro: " . $e->getMessage() . "\n";
    }
}
