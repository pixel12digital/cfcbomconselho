<?php
/**
 * Script de validação: Aulas teóricas do dia 15/01/2026
 */

require_once __DIR__ . '/../public_html/index.php';

use App\Config\Database;

$db = Database::getInstance()->getConnection();
$cfcId = 1; // Ajustar se necessário
$targetDate = '2026-01-15';

echo "=== VALIDAÇÃO: Aulas Teóricas 15/01/2026 ===\n\n";

// A) Validar lessons teóricas no banco
echo "A) Lessons teóricas no banco para 15/01/2026:\n";
$stmt = $db->prepare("
    SELECT 
        l.id,
        l.cfc_id,
        l.scheduled_date,
        l.scheduled_time,
        l.type,
        l.status as lesson_status,
        l.theory_session_id,
        ts.id as session_id,
        ts.status as session_status,
        ts.starts_at,
        ts.ends_at,
        td.name as discipline_name,
        tc.name as class_name
    FROM lessons l
    INNER JOIN theory_sessions ts ON l.theory_session_id = ts.id
    INNER JOIN theory_disciplines td ON ts.discipline_id = td.id
    INNER JOIN theory_classes tc ON ts.class_id = tc.id
    WHERE l.cfc_id = ? 
      AND l.type = 'teoria'
      AND l.scheduled_date = ?
    ORDER BY l.scheduled_time
");
$stmt->execute([$cfcId, $targetDate]);
$lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "   Total encontrado: " . count($lessons) . "\n";
if (empty($lessons)) {
    echo "   ❌ NENHUMA lesson teórica encontrada para 15/01/2026!\n";
} else {
    echo "   ✅ Lessons encontradas:\n";
    foreach ($lessons as $lesson) {
        echo "      - Lesson ID: {$lesson['id']}, ";
        echo "Horário: {$lesson['scheduled_time']}, ";
        echo "Status Lesson: {$lesson['lesson_status']}, ";
        echo "Status Session: {$lesson['session_status']}, ";
        echo "Disciplina: {$lesson['discipline_name']}, ";
        echo "Turma: {$lesson['class_name']}\n";
    }
}

// B) Validar query da agenda (simular findByPeriodWithTheoryDedupe)
echo "\nB) Simulando query da agenda (findByPeriodWithTheoryDedupe):\n";
$startDate = $targetDate;
$endDate = $targetDate;
$filters = ['type' => 'teoria'];

$sqlTeoria = "SELECT MIN(l.id) as id,
                     l.cfc_id,
                     MIN(l.student_id) as student_id,
                     MIN(l.enrollment_id) as enrollment_id,
                     l.instructor_id,
                     NULL as vehicle_id,
                     l.type,
                     l.status as status,
                     l.scheduled_date,
                     l.scheduled_time,
                     MIN(l.duration_minutes) as duration_minutes,
                     MIN(l.started_at) as started_at,
                     MIN(l.completed_at) as completed_at,
                     MIN(l.notes) as notes,
                     MIN(l.created_by) as created_by,
                     MIN(l.created_at) as created_at,
                     MIN(l.updated_at) as updated_at,
                     MIN(l.canceled_at) as canceled_at,
                     MIN(l.canceled_by) as canceled_by,
                     MIN(l.cancel_reason) as cancel_reason,
                     MIN(l.km_start) as km_start,
                     MIN(l.km_end) as km_end,
                     l.theory_session_id,
                     ts.class_id,
                     ts.discipline_id,
                     td.name as discipline_name,
                     tc.name as class_name,
                     i.name as instructor_name,
                     NULL as student_name,
                     NULL as vehicle_plate,
                     COUNT(DISTINCT l.student_id) as student_count,
                     'teoria' as lesson_type,
                     GROUP_CONCAT(DISTINCT s.name ORDER BY s.name SEPARATOR ', ') as student_names
              FROM lessons l
              INNER JOIN students s ON l.student_id = s.id
              INNER JOIN theory_sessions ts ON l.theory_session_id = ts.id
              INNER JOIN theory_disciplines td ON ts.discipline_id = td.id
              INNER JOIN theory_classes tc ON ts.class_id = tc.id
              INNER JOIN instructors i ON l.instructor_id = i.id
              WHERE l.cfc_id = ?
                AND l.scheduled_date BETWEEN ? AND ?
                AND l.type = 'teoria'
                AND l.theory_session_id IS NOT NULL
                AND l.status != 'cancelada' 
                AND ts.status != 'canceled'
              GROUP BY l.theory_session_id, l.scheduled_date, l.scheduled_time, ts.class_id, ts.discipline_id, ts.status, l.instructor_id, l.cfc_id, l.type";

try {
    $stmt = $db->prepare($sqlTeoria);
    $stmt->execute([$cfcId, $startDate, $endDate]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Query executada com sucesso!\n";
    echo "   Total retornado: " . count($results) . "\n";
    
    if (empty($results)) {
        echo "   ❌ Query retornou VAZIO!\n";
        echo "   Verificando condições...\n";
        
        // Verificar cada condição
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM lessons WHERE cfc_id = ? AND scheduled_date = ? AND type = 'teoria'");
        $stmt->execute([$cfcId, $targetDate]);
        $cnt = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "      - Lessons com cfc_id={$cfcId}, date={$targetDate}, type='teoria': {$cnt['cnt']}\n";
        
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM lessons WHERE cfc_id = ? AND scheduled_date = ? AND type = 'teoria' AND status != 'cancelada'");
        $stmt->execute([$cfcId, $targetDate]);
        $cnt = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "      - Lessons não canceladas: {$cnt['cnt']}\n";
        
        $stmt = $db->prepare("
            SELECT COUNT(*) as cnt 
            FROM lessons l
            INNER JOIN theory_sessions ts ON l.theory_session_id = ts.id
            WHERE l.cfc_id = ? AND l.scheduled_date = ? AND l.type = 'teoria' AND ts.status != 'canceled'
        ");
        $stmt->execute([$cfcId, $targetDate]);
        $cnt = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "      - Lessons com session não cancelada: {$cnt['cnt']}\n";
    } else {
        echo "   ✅ Resultados encontrados:\n";
        foreach ($results as $result) {
            echo "      - Data: {$result['scheduled_date']} {$result['scheduled_time']}, ";
            echo "Disciplina: {$result['discipline_name']}, ";
            echo "Alunos: {$result['student_count']}, ";
            echo "Status: {$result['status']}\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ Erro na query: " . $e->getMessage() . "\n";
}

// C) Verificar teoria_sessions diretamente
echo "\nC) Theory_sessions para 15/01/2026:\n";
$stmt = $db->prepare("
    SELECT ts.id, ts.class_id, ts.discipline_id, ts.starts_at, ts.ends_at, ts.status,
           DATE(ts.starts_at) as session_date,
           TIME(ts.starts_at) as session_time,
           td.name as discipline_name,
           tc.name as class_name,
           tc.cfc_id
    FROM theory_sessions ts
    INNER JOIN theory_disciplines td ON ts.discipline_id = td.id
    INNER JOIN theory_classes tc ON ts.class_id = tc.id
    WHERE tc.cfc_id = ?
      AND DATE(ts.starts_at) = ?
    ORDER BY ts.starts_at
");
$stmt->execute([$cfcId, $targetDate]);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "   Total encontrado: " . count($sessions) . "\n";
if (empty($sessions)) {
    echo "   ❌ NENHUMA theory_session encontrada para 15/01/2026!\n";
} else {
    echo "   ✅ Sessions encontradas:\n";
    foreach ($sessions as $session) {
        echo "      - Session ID: {$session['id']}, ";
        echo "Início: {$session['starts_at']}, ";
        echo "Status: {$session['status']}, ";
        echo "Disciplina: {$session['discipline_name']}, ";
        echo "Turma: {$session['class_name']}\n";
    }
}

echo "\n=== FIM DA VALIDAÇÃO ===\n";
