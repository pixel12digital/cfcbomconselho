<?php
/**
 * Script para criar lessons faltantes para theory_sessions existentes
 */

require_once __DIR__ . '/../public_html/index.php';

use App\Config\Database;
use App\Models\TheorySession;
use App\Models\TheoryEnrollment;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Enrollment;

$db = Database::getInstance()->getConnection();
$cfcId = 1; // Ajustar se necessário

echo "=== CORREÇÃO: Criar lessons faltantes ===\n\n";

// Buscar todas as theory_sessions que não têm lessons
$stmt = $db->prepare("
    SELECT ts.id as session_id, ts.class_id, ts.discipline_id, ts.starts_at, ts.ends_at, ts.status,
           tc.cfc_id, tc.instructor_id, tc.name as class_name,
           tco.name as course_name
    FROM theory_sessions ts
    INNER JOIN theory_classes tc ON ts.class_id = tc.id
    INNER JOIN theory_courses tco ON tc.course_id = tco.id
    WHERE tc.cfc_id = ?
      AND ts.status != 'canceled'
      AND NOT EXISTS (
          SELECT 1 FROM lessons l 
          WHERE l.theory_session_id = ts.id
      )
    ORDER BY ts.starts_at DESC
");
$stmt->execute([$cfcId]);
$sessionsWithoutLessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Sessions sem lessons: " . count($sessionsWithoutLessons) . "\n\n";

if (empty($sessionsWithoutLessons)) {
    echo "✅ Todas as sessions já têm lessons criadas!\n";
    exit(0);
}

$sessionModel = new TheorySession();
$enrollmentModel = new TheoryEnrollment();
$lessonModel = new Lesson();
$studentModel = new Student();
$mainEnrollmentModel = new Enrollment();

$totalCreated = 0;

foreach ($sessionsWithoutLessons as $session) {
    echo "Processando Session ID: {$session['session_id']} - {$session['class_name']}\n";
    echo "  Data: {$session['starts_at']}\n";
    
    // Buscar alunos matriculados na turma
    $enrollments = $enrollmentModel->findByClass($session['class_id']);
    echo "  Alunos matriculados: " . count($enrollments) . "\n";
    
    if (empty($enrollments)) {
        echo "  ⚠️ Nenhum aluno matriculado na turma. Pulando...\n\n";
        continue;
    }
    
    $startDateTime = new \DateTime($session['starts_at']);
    $endDateTime = new \DateTime($session['ends_at']);
    $durationMinutes = (int)(($endDateTime->getTimestamp() - $startDateTime->getTimestamp()) / 60);
    
    $createdForSession = 0;
    
    foreach ($enrollments as $enrollment) {
        if ($enrollment['status'] !== 'active') {
            echo "    - Aluno {$enrollment['student_name']}: status não é 'active', pulando\n";
            continue;
        }
        
        $student = $studentModel->find($enrollment['student_id']);
        if (!$student) {
            echo "    - Aluno ID {$enrollment['student_id']}: não encontrado, pulando\n";
            continue;
        }
        
        // Buscar matrícula ativa
        $enrollmentId = $enrollment['enrollment_id'] ?? null;
        if (!$enrollmentId) {
            $activeEnrollments = $mainEnrollmentModel->findByStudent($enrollment['student_id'], $cfcId);
            $activeEnrollments = array_filter($activeEnrollments, function($e) {
                return $e['status'] === 'ativa';
            });
            if (!empty($activeEnrollments)) {
                $enrollmentId = reset($activeEnrollments)['id'];
            }
        }
        
        // Verificar se já existe
        $stmt = $db->prepare("SELECT id FROM lessons WHERE theory_session_id = ? AND student_id = ?");
        $stmt->execute([$session['session_id'], $enrollment['student_id']]);
        if ($stmt->fetch()) {
            echo "    - Aluno {$enrollment['student_name']}: lesson já existe, pulando\n";
            continue;
        }
        
        // Criar lesson
        $lessonData = [
            'cfc_id' => $cfcId,
            'student_id' => $enrollment['student_id'],
            'enrollment_id' => $enrollmentId ?: 0,
            'instructor_id' => $session['instructor_id'],
            'vehicle_id' => null,
            'type' => 'teoria',
            'status' => 'agendada',
            'scheduled_date' => $startDateTime->format('Y-m-d'),
            'scheduled_time' => $startDateTime->format('H:i:s'),
            'duration_minutes' => $durationMinutes,
            'theory_session_id' => $session['session_id'],
            'notes' => "Sessão teórica: {$session['course_name']}",
            'created_by' => null
        ];
        
        try {
            $lessonId = $lessonModel->create($lessonData);
            $createdForSession++;
            $totalCreated++;
            echo "    ✅ Lesson criada (ID: {$lessonId}) para {$enrollment['student_name']}\n";
        } catch (Exception $e) {
            echo "    ❌ Erro ao criar lesson para {$enrollment['student_name']}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "  Total criado para esta sessão: {$createdForSession}\n\n";
}

echo "=== RESUMO ===\n";
echo "Total de lessons criadas: {$totalCreated}\n";
echo "✅ Correção concluída!\n";
