<?php

namespace App\Services;

use App\Models\TheoryEnrollment;
use App\Models\TheorySession;
use App\Models\TheoryAttendance;
use App\Models\Step;
use App\Models\StudentStep;

class TheoryProgressService
{
    /**
     * Atualiza status do step CURSO_TEORICO baseado em attendance
     */
    public function updateTheoryStepStatus($enrollmentId, $classId = null)
    {
        // Buscar step CURSO_TEORICO
        $stepModel = new Step();
        $step = $stepModel->findByCode('CURSO_TEORICO');
        
        if (!$step) {
            return; // Step não existe ainda
        }

        // Se classId não informado, buscar pela enrollment
        if (!$classId) {
            $enrollmentModel = new \App\Models\Enrollment();
            $enrollment = $enrollmentModel->find($enrollmentId);
            if (!$enrollment || !$enrollment['theory_class_id']) {
                return; // Enrollment não tem turma vinculada
            }
            $classId = $enrollment['theory_class_id'];
        }

        // Buscar matrícula do aluno na turma
        $db = \App\Config\Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "SELECT * FROM theory_enrollments 
             WHERE class_id = ? AND enrollment_id = ? AND status = 'active'"
        );
        $stmt->execute([$classId, $enrollmentId]);
        $theoryEnrollment = $stmt->fetch();

        if (!$theoryEnrollment) {
            return; // Aluno não está matriculado na turma
        }

        // Buscar TODAS as sessões da turma (planejadas + concluídas)
        $stmt = $db->prepare(
            "SELECT id, status FROM theory_sessions 
             WHERE class_id = ? AND status != 'canceled'"
        );
        $stmt->execute([$classId]);
        $allSessions = $stmt->fetchAll();
        
        // Filtrar apenas sessões concluídas para verificar presenças
        $sessions = array_filter($allSessions, function($s) {
            return $s['status'] === 'done';
        });

        if (empty($sessions)) {
            return; // Nenhuma sessão concluída ainda
        }

        $sessionIds = array_column($sessions, 'id');

        // Buscar presenças do aluno
        if (empty($sessionIds)) {
            $attendances = [];
        } else {
            $placeholders = implode(',', array_fill(0, count($sessionIds), '?'));
            $stmt = $db->prepare(
                "SELECT session_id, status FROM theory_attendance 
                 WHERE student_id = ? AND session_id IN ({$placeholders})"
            );
            $stmt->execute(array_merge([$theoryEnrollment['student_id']], $sessionIds));
            $attendances = $stmt->fetchAll();
        }

        $attendanceMap = [];
        foreach ($attendances as $attendance) {
            $attendanceMap[$attendance['session_id']] = $attendance['status'];
        }

        // Verificar se todas as sessões têm presença 'present' ou 'justified'
        $allCompleted = true;
        foreach ($sessions as $session) {
            $status = $attendanceMap[$session['id']] ?? null;
            if (!$status || !in_array($status, ['present', 'justified'])) {
                $allCompleted = false;
                break;
            }
        }

        // Buscar student_step correspondente
        $stmt = $db->prepare(
            "SELECT * FROM student_steps 
             WHERE enrollment_id = ? AND step_id = ?"
        );
        $stmt->execute([$enrollmentId, $step['id']]);
        $studentStep = $stmt->fetch();

        $studentStepModel = new StudentStep();
        if (!$studentStep) {
            // Criar se não existir
            $studentStepModel->create([
                'enrollment_id' => $enrollmentId,
                'step_id' => $step['id'],
                'status' => $allCompleted ? 'concluida' : 'pendente',
                'source' => 'cfc'
            ]);
        } else {
            // Atualizar status
            $newStatus = $allCompleted ? 'concluida' : 'pendente';
            if ($studentStep['status'] !== $newStatus) {
                $studentStepModel->update($studentStep['id'], [
                    'status' => $newStatus,
                    'validated_by_user_id' => $allCompleted ? ($_SESSION['user_id'] ?? null) : null,
                    'validated_at' => $allCompleted ? date('Y-m-d H:i:s') : null
                ]);
            }
        }

        return $allCompleted;
    }
}
