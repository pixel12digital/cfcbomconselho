<?php
/**
 * Script para verificar se as lessons de teste foram criadas
 */

require_once __DIR__ . '/../public_html/index.php';

use App\Config\Database;

$db = Database::getInstance()->getConnection();

echo "=== VERIFICAÇÃO: Lessons de Teste ===\n\n";

$testMarker = 'TEST_STACK_1400';
$targetDate = '2026-01-15';
$targetTime = '14:00:00';

$stmt = $db->prepare("
    SELECT 
        id,
        cfc_id,
        student_id,
        enrollment_id,
        instructor_id,
        vehicle_id,
        type,
        status,
        scheduled_date,
        scheduled_time,
        duration_minutes,
        notes
    FROM lessons 
    WHERE notes = ? 
      AND scheduled_date = ? 
      AND scheduled_time = ?
    ORDER BY id
");

$stmt->execute([$testMarker, $targetDate, $targetTime]);
$lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total de lessons encontradas: " . count($lessons) . "\n\n";

if (empty($lessons)) {
    echo "❌ Nenhuma lesson de teste encontrada!\n";
    echo "   Execute o script test_cluster_stack_lessons.sql primeiro.\n";
} else {
    echo "✅ Lessons criadas com sucesso:\n\n";
    
    foreach ($lessons as $index => $lesson) {
        echo "Lesson #" . ($index + 1) . " (ID: {$lesson['id']}):\n";
        echo "   - Aluno ID: {$lesson['student_id']}\n";
        echo "   - Instrutor ID: {$lesson['instructor_id']}\n";
        echo "   - Veículo ID: {$lesson['vehicle_id']}\n";
        echo "   - Status: {$lesson['status']}\n";
        echo "   - Horário: {$lesson['scheduled_time']} ({$lesson['duration_minutes']} min)\n";
        echo "\n";
    }
    
    echo "✅ Todas as lessons estão no mesmo horário (14:00-14:50) e devem aparecer agrupadas na agenda!\n";
}
