<?php
/**
 * Script de Limpeza - Etapa 1: Aluno e Dependências
 * 
 * Executa a limpeza do aluno e todas as suas dependências dentro de uma transação.
 * Inclui validações de segurança e confirmação antes do COMMIT.
 * 
 * Uso: php tools/limpeza_etapa1_aluno.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

require_once APP_PATH . '/autoload.php';

use App\Config\Database;
use App\Config\Env;

// Carregar variáveis de ambiente
Env::load();

echo "========================================\n";
echo "ETAPA 1: LIMPEZA DE ALUNO E DEPENDÊNCIAS\n";
echo "========================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Iniciar transação
    $db->beginTransaction();
    
    echo "1. Identificando aluno...\n";
    $stmt = $db->query("SELECT id FROM students ORDER BY id ASC LIMIT 1");
    $student = $stmt->fetch();
    
    if (!$student) {
        echo "   ⚠️  Nenhum aluno encontrado. Nada a fazer.\n";
        $db->rollBack();
        exit(0);
    }
    
    $studentId = $student['id'];
    echo "   ✅ Aluno ID encontrado: {$studentId}\n\n";
    
    // Contar registros ANTES
    echo "2. Contando registros ANTES da limpeza...\n";
    $countsBefore = [];
    
    $tables = [
        'students' => "SELECT COUNT(*) as cnt FROM students",
        'enrollments' => "SELECT COUNT(*) as cnt FROM enrollments WHERE student_id = {$studentId}",
        'lessons' => "SELECT COUNT(*) as cnt FROM lessons WHERE student_id = {$studentId}",
        'student_history' => "SELECT COUNT(*) as cnt FROM student_history WHERE student_id = {$studentId}",
        'student_steps' => "SELECT COUNT(*) as cnt FROM student_steps ss JOIN enrollments e ON e.id = ss.enrollment_id WHERE e.student_id = {$studentId}",
        'theory_attendance' => "SELECT COUNT(*) as cnt FROM theory_attendance WHERE student_id = {$studentId}",
        'theory_enrollments' => "SELECT COUNT(*) as cnt FROM theory_enrollments WHERE student_id = {$studentId}",
        'reschedule_requests' => "SELECT COUNT(*) as cnt FROM reschedule_requests WHERE student_id = {$studentId}",
        'user_recent_financial_queries' => "SELECT COUNT(*) as cnt FROM user_recent_financial_queries WHERE student_id = {$studentId}",
    ];
    
    foreach ($tables as $table => $sql) {
        $stmt = $db->query($sql);
        $result = $stmt->fetch();
        $countsBefore[$table] = (int)$result['cnt'];
        echo "   {$table}: {$countsBefore[$table]} registros\n";
    }
    
    echo "\n";
    
    // Executar deletes
    echo "3. Executando exclusões...\n";
    
    // 2) Apagar dependências (filhas) do aluno
    echo "   - Apagando theory_attendance...\n";
    $db->exec("DELETE FROM theory_attendance WHERE student_id = {$studentId}");
    
    echo "   - Apagando theory_enrollments...\n";
    $db->exec("DELETE FROM theory_enrollments WHERE student_id = {$studentId}");
    
    echo "   - Apagando user_recent_financial_queries...\n";
    $db->exec("DELETE FROM user_recent_financial_queries WHERE student_id = {$studentId}");
    
    echo "   - Apagando student_history...\n";
    $db->exec("DELETE FROM student_history WHERE student_id = {$studentId}");
    
    echo "   - Apagando reschedule_requests...\n";
    $db->exec("DELETE FROM reschedule_requests WHERE student_id = {$studentId}");
    
    // 3) Apagar aulas/agendamentos do aluno
    echo "   - Apagando lessons...\n";
    $db->exec("DELETE FROM lessons WHERE student_id = {$studentId}");
    
    // 4) Limpar sessões teóricas órfãs
    echo "   - Limpando theory_sessions órfãs...\n";
    $db->exec("DELETE ts FROM theory_sessions ts LEFT JOIN lessons l ON l.id = ts.lesson_id WHERE ts.lesson_id IS NOT NULL AND l.id IS NULL");
    
    // 5) Apagar matrículas do aluno (e steps ligados)
    echo "   - Apagando student_steps...\n";
    $db->exec("DELETE ss FROM student_steps ss JOIN enrollments e ON e.id = ss.enrollment_id WHERE e.student_id = {$studentId}");
    
    echo "   - Apagando enrollments...\n";
    $db->exec("DELETE FROM enrollments WHERE student_id = {$studentId}");
    
    // 6) Finalmente, apagar o aluno
    echo "   - Apagando students...\n";
    $db->exec("DELETE FROM students WHERE id = {$studentId}");
    
    echo "\n";
    
    // Contar registros DEPOIS
    echo "4. Contando registros DEPOIS da limpeza...\n";
    $countsAfter = [];
    
    $tablesAfter = [
        'students' => "SELECT COUNT(*) as cnt FROM students",
        'enrollments' => "SELECT COUNT(*) as cnt FROM enrollments",
        'lessons' => "SELECT COUNT(*) as cnt FROM lessons",
        'student_history' => "SELECT COUNT(*) as cnt FROM student_history",
        'student_steps' => "SELECT COUNT(*) as cnt FROM student_steps",
        'theory_attendance' => "SELECT COUNT(*) as cnt FROM theory_attendance",
        'theory_enrollments' => "SELECT COUNT(*) as cnt FROM theory_enrollments",
        'reschedule_requests' => "SELECT COUNT(*) as cnt FROM reschedule_requests",
        'user_recent_financial_queries' => "SELECT COUNT(*) as cnt FROM user_recent_financial_queries",
    ];
    
    foreach ($tablesAfter as $table => $sql) {
        $stmt = $db->query($sql);
        $result = $stmt->fetch();
        $countsAfter[$table] = (int)$result['cnt'];
        $status = ($countsAfter[$table] == 0) ? '✅' : '⚠️';
        echo "   {$status} {$table}: {$countsAfter[$table]} registros\n";
    }
    
    echo "\n";
    
    // Validação final
    echo "5. Validação final...\n";
    $studentsCount = $countsAfter['students'];
    
    if ($studentsCount == 0) {
        echo "   ✅ students = 0 (OK)\n";
        
        // Verificar se outras tabelas também estão zeradas (ou coerentes)
        $allOk = true;
        foreach (['enrollments', 'lessons', 'student_history', 'student_steps', 'theory_attendance', 'theory_enrollments', 'reschedule_requests', 'user_recent_financial_queries'] as $table) {
            if ($countsAfter[$table] > 0) {
                echo "   ⚠️  {$table} ainda tem {$countsAfter[$table]} registros (pode ser de outros alunos ou dados de exemplo)\n";
            }
        }
        
        echo "\n";
        echo "✅ Validação OK. Fazendo COMMIT...\n";
        $db->commit();
        echo "✅ Transação confirmada!\n\n";
        
        // Resetar AUTO_INCREMENT
        echo "6. Resetando AUTO_INCREMENT...\n";
        $resetTables = [
            'theory_attendance',
            'theory_enrollments',
            'user_recent_financial_queries',
            'student_history',
            'reschedule_requests',
            'lessons',
            'theory_sessions',
            'student_steps',
            'enrollments',
            'students'
        ];
        
        foreach ($resetTables as $table) {
            try {
                $db->exec("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
                echo "   ✅ {$table}\n";
            } catch (\Exception $e) {
                echo "   ⚠️  {$table}: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n";
        echo "✅ Etapa 1 concluída com sucesso!\n";
        
    } else {
        echo "   ❌ ERRO: students ainda tem {$studentsCount} registros!\n";
        echo "   Fazendo ROLLBACK...\n";
        $db->rollBack();
        echo "   ❌ Transação revertida. Nenhuma alteração foi aplicada.\n";
        exit(1);
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Fazendo ROLLBACK...\n";
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "❌ Transação revertida.\n";
    exit(1);
}
