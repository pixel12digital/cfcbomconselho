<?php
/**
 * Script para executar a Migration 025: Módulo de Curso Teórico
 * 
 * Executa a migration que cria todas as tabelas necessárias para o módulo de Curso Teórico:
 * - theory_disciplines (Disciplinas)
 * - theory_courses (Cursos/templates)
 * - theory_course_disciplines (Relação curso-disciplinas)
 * - theory_classes (Turmas)
 * - theory_sessions (Sessões/aulas)
 * - theory_enrollments (Matrículas na turma)
 * - theory_attendance (Presença)
 * - Modifica lessons para suportar type='teoria'
 */

require_once __DIR__ . '/../app/Config/Database.php';

use App\Config\Database;

$db = Database::getInstance()->getConnection();
$migrationFile = __DIR__ . '/../database/migrations/025_create_theory_course_tables.sql';

if (!file_exists($migrationFile)) {
    die("❌ Arquivo de migration não encontrado: {$migrationFile}\n");
}

echo "🔄 Executando Migration 025: Módulo de Curso Teórico...\n\n";

try {
    $sql = file_get_contents($migrationFile);
    
    // Dividir em comandos individuais (separados por ;)
    $commands = array_filter(
        array_map('trim', explode(';', $sql)),
        function($cmd) {
            return !empty($cmd) && !preg_match('/^--/', $cmd) && !preg_match('/^SET\s+/i', $cmd);
        }
    );
    
    $db->beginTransaction();
    
    $executed = 0;
    foreach ($commands as $command) {
        if (empty(trim($command))) continue;
        
        try {
            $db->exec($command);
            $executed++;
        } catch (PDOException $e) {
            // Ignorar erros de "table already exists" ou "duplicate column"
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'Duplicate column') === false) {
                throw $e;
            }
            echo "⚠️  Aviso: " . $e->getMessage() . "\n";
        }
    }
    
    $db->commit();
    
    echo "✅ Migration executada com sucesso!\n";
    echo "📊 Comandos executados: {$executed}\n\n";
    
    // Verificar tabelas criadas
    $tables = [
        'theory_disciplines',
        'theory_courses',
        'theory_course_disciplines',
        'theory_classes',
        'theory_sessions',
        'theory_enrollments',
        'theory_attendance'
    ];
    
    echo "🔍 Verificando tabelas criadas:\n";
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            echo "  ✅ {$table}\n";
        } else {
            echo "  ❌ {$table} (não encontrada)\n";
        }
    }
    
    // Verificar alteração em lessons
    echo "\n🔍 Verificando alterações em 'lessons':\n";
    $stmt = $db->query("SHOW COLUMNS FROM lessons WHERE Field = 'type'");
    $column = $stmt->fetch();
    if ($column && strpos($column['Type'], 'teoria') !== false) {
        echo "  ✅ Campo 'type' agora aceita 'teoria'\n";
    } else {
        echo "  ⚠️  Campo 'type' pode não ter sido alterado corretamente\n";
    }
    
    $stmt = $db->query("SHOW COLUMNS FROM lessons WHERE Field = 'theory_session_id'");
    if ($stmt->rowCount() > 0) {
        echo "  ✅ Campo 'theory_session_id' adicionado\n";
    } else {
        echo "  ⚠️  Campo 'theory_session_id' não encontrado\n";
    }
    
    echo "\n✨ Pronto! O módulo de Curso Teórico está configurado.\n";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "❌ Erro ao executar migration:\n";
    echo "   " . $e->getMessage() . "\n";
    exit(1);
}
