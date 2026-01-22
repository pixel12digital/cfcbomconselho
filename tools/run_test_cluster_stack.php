<?php
/**
 * Script PHP para executar test_cluster_stack_lessons.sql
 */

require_once __DIR__ . '/../public_html/index.php';

use App\Config\Database;

$db = Database::getInstance()->getConnection();

echo "=== EXECUTANDO: Teste de Cluster/Stack de Lessons ===\n\n";

try {
    $cfcId = 1;
    
    // Verificar recursos disponíveis
    echo "1. Verificando recursos disponíveis...\n";
    
    $stmt = $db->prepare("SELECT id FROM instructors WHERE cfc_id = ? AND is_active = 1 LIMIT 5");
    $stmt->execute([$cfcId]);
    $instructors = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt = $db->prepare("SELECT id FROM students WHERE cfc_id = ? LIMIT 5");
    $stmt->execute([$cfcId]);
    $students = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt = $db->prepare("SELECT id FROM vehicles WHERE cfc_id = ? AND is_active = 1 LIMIT 5");
    $stmt->execute([$cfcId]);
    $vehicles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($instructors)) {
        die("❌ Nenhum instrutor ativo encontrado para cfc_id={$cfcId}\n");
    }
    if (empty($students)) {
        die("❌ Nenhum aluno encontrado para cfc_id={$cfcId}\n");
    }
    if (empty($vehicles)) {
        die("❌ Nenhum veículo ativo encontrado para cfc_id={$cfcId}\n");
    }
    
    echo "   ✅ Instrutores: " . count($instructors) . "\n";
    echo "   ✅ Alunos: " . count($students) . "\n";
    echo "   ✅ Veículos: " . count($vehicles) . "\n\n";
    
    // Buscar enrollments
    $enrollments = [];
    foreach ($students as $studentId) {
        $stmt = $db->prepare("SELECT id FROM enrollments WHERE student_id = ? AND cfc_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$studentId, $cfcId]);
        $enrollment = $stmt->fetchColumn();
        $enrollments[$studentId] = $enrollment ?: 0;
    }
    
    // Remover lessons de teste existentes
    echo "2. Removendo lessons de teste existentes...\n";
    $testMarker = 'TEST_STACK_1400';
    $targetDate = '2026-01-15';
    $targetTime = '14:00:00';
    
    $stmt = $db->prepare("DELETE FROM lessons WHERE notes = ? AND scheduled_date = ? AND scheduled_time = ?");
    $stmt->execute([$testMarker, $targetDate, $targetTime]);
    $deleted = $stmt->rowCount();
    echo "   🗑️  Deletado: {$deleted} registro(s)\n\n";
    
    // Preparar dados para 5 lessons
    echo "3. Criando 5 lessons práticas...\n";
    
    $targetDuration = 50;
    $insertSql = "INSERT INTO lessons (
        cfc_id, student_id, enrollment_id, instructor_id, vehicle_id,
        type, status, scheduled_date, scheduled_time, duration_minutes,
        notes, created_at
    ) VALUES (?, ?, ?, ?, ?, 'pratica', 'agendada', ?, ?, ?, ?, NOW())";
    
    $stmt = $db->prepare($insertSql);
    $created = 0;
    
    for ($i = 0; $i < 5; $i++) {
        $studentId = $students[$i % count($students)];
        $instructorId = $instructors[$i % count($instructors)];
        $vehicleId = $vehicles[$i % count($vehicles)];
        $enrollmentId = $enrollments[$studentId] ?? 0;
        
        try {
            $stmt->execute([
                $cfcId,
                $studentId,
                $enrollmentId,
                $instructorId,
                $vehicleId,
                $targetDate,
                $targetTime,
                $targetDuration,
                $testMarker
            ]);
            $created++;
            echo "   ✅ Lesson " . ($i + 1) . " criada (Aluno: {$studentId}, Instrutor: {$instructorId}, Veículo: {$vehicleId})\n";
        } catch (Exception $e) {
            echo "   ❌ Erro ao criar lesson " . ($i + 1) . ": " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== RESUMO ===\n";
    echo "✅ Lessons criadas: {$created}/5\n\n";
    
    if ($created === 5) {
        echo "✅ Script executado com sucesso!\n\n";
        echo "📝 Próximos passos:\n";
        echo "   1. Acesse a agenda como admin/secretaria\n";
        echo "   2. Filtre por data: 15/01/2026\n";
        echo "   3. Visualize em modo semanal ou diário\n";
        echo "   4. Deve aparecer apenas 2 cards + indicador '+3 agendamento(s)'\n";
        echo "   5. Clique no indicador para ver o modal com todos os 5 eventos\n";
    } else {
        echo "⚠️  Nem todas as lessons foram criadas. Verifique os erros acima.\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ ERRO GERAL: " . $e->getMessage() . "\n";
    exit(1);
}
