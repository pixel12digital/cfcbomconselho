<?php
require_once __DIR__ . '/../app/autoload.php';

use App\Config\Database;

$db = Database::getInstance()->getConnection();
$sql = file_get_contents(__DIR__ . '/../database/migrations/025_create_theory_course_tables.sql');

try {
    // Executar SQL completo
    $db->exec($sql);
    echo "✅ Migration 025 executada com sucesso!\n";
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
