<?php

namespace App\Controllers;

use App\Models\TheoryAttendance;
use App\Models\TheorySession;
use App\Models\TheoryEnrollment;
use App\Services\PermissionService;
use App\Services\AuditService;
use App\Services\TheoryProgressService;
use App\Config\Constants;

class TheoryAttendanceController extends Controller
{
    private $cfcId;
    private $auditService;

    public function __construct()
    {
        $this->cfcId = $_SESSION['cfc_id'] ?? Constants::CFC_ID_DEFAULT;
        $this->auditService = new AuditService();
        
        if (!PermissionService::check('presenca_teorica', 'view')) {
            $_SESSION['error'] = 'Você não tem permissão para acessar este módulo.';
            redirect(base_url('dashboard'));
        }
    }

    /**
     * Tela de presença por sessão (mobile-first)
     */
    public function sessao($classId, $sessionId)
    {
        if (!PermissionService::check('presenca_teorica', 'view')) {
            $_SESSION['error'] = 'Você não tem permissão para visualizar presença.';
            redirect(base_url("turmas-teoricas/{$classId}"));
        }

        $sessionModel = new TheorySession();
        $session = $sessionModel->findWithDetails($sessionId);

        if (!$session || $session['class_id'] != $classId) {
            $_SESSION['error'] = 'Sessão não encontrada.';
            redirect(base_url("turmas-teoricas/{$classId}"));
        }

        // Buscar alunos matriculados na turma
        $enrollmentModel = new TheoryEnrollment();
        $enrollments = $enrollmentModel->findByClass($classId);

        // Buscar presenças já marcadas
        $attendanceModel = new TheoryAttendance();
        $attendances = $attendanceModel->findBySession($sessionId);
        $attendanceMap = [];
        foreach ($attendances as $attendance) {
            $attendanceMap[$attendance['student_id']] = $attendance;
        }

        $data = [
            'pageTitle' => 'Presença - ' . $session['discipline_name'],
            'session' => $session,
            'classId' => $classId,
            'enrollments' => $enrollments,
            'attendanceMap' => $attendanceMap
        ];

        $this->view('theory_attendance/sessao', $data);
    }

    /**
     * Salvar presença (submit rápido)
     */
    public function salvar($classId, $sessionId)
    {
        if (!PermissionService::check('presenca_teorica', 'create')) {
            $_SESSION['error'] = 'Você não tem permissão para marcar presença.';
            redirect(base_url("turmas-teoricas/{$classId}/sessoes/{$sessionId}/presenca"));
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url("turmas-teoricas/{$classId}/sessoes/{$sessionId}/presenca"));
        }

        $sessionModel = new TheorySession();
        $session = $sessionModel->find($sessionId);

        if (!$session || $session['class_id'] != $classId) {
            $_SESSION['error'] = 'Sessão não encontrada.';
            redirect(base_url("turmas-teoricas/{$classId}"));
        }

        // Processar presenças
        $attendances = [];
        $attendanceData = $_POST['attendance'] ?? [];

        foreach ($attendanceData as $studentId => $data) {
            $attendances[] = [
                'student_id' => (int)$studentId,
                'status' => $data['status'] ?? 'absent',
                'notes' => !empty($data['notes']) ? trim($data['notes']) : null
            ];
        }

        $db = \App\Config\Database::getInstance()->getConnection();
        $db->beginTransaction();
        
        try {
            // Salvar em lote
            $attendanceModel = new TheoryAttendance();
            $attendanceModel->markBatch($sessionId, $attendances);

            // Verificar se há ausências e gerar notificações
            $notificationModel = new \App\Models\Notification();
            $db = \App\Config\Database::getInstance()->getConnection();
            foreach ($attendances as $attendance) {
                if (in_array($attendance['status'], ['absent', 'no_show'])) {
                    $stmt = $db->prepare(
                        "SELECT u.id FROM usuarios u 
                         INNER JOIN students s ON s.user_id = u.id 
                         WHERE s.id = ?"
                    );
                    $stmt->execute([$attendance['student_id']]);
                    $user = $stmt->fetch();
                    
                    if ($user) {
                        $notificationModel->createNotification(
                            $user['id'],
                            'theory_attendance_marked',
                            'Falta registrada',
                            "Você faltou na sessão teórica de {$session['discipline_name']}",
                            "turmas-teoricas/{$classId}/sessoes/{$sessionId}/presenca"
                        );
                    }
                }
            }

            // Se sessão está concluída (status = 'done'), sincronizar status das lessons e atualizar progresso
            if ($session['status'] === 'done') {
                // Sincronizar status das lessons para 'concluida'
                $lessonModel = new \App\Models\Lesson();
                $stmt = $db->prepare(
                    "UPDATE lessons 
                     SET status = 'concluida', 
                         completed_at = ?
                     WHERE theory_session_id = ? 
                       AND status != 'concluida'"
                );
                $stmt->execute([date('Y-m-d H:i:s'), $sessionId]);
                
                $progressService = new TheoryProgressService();
                
                // Buscar todas as matrículas na turma
                $enrollmentModel = new TheoryEnrollment();
                $enrollments = $enrollmentModel->findByClass($classId);
                
                foreach ($enrollments as $enrollment) {
                    if ($enrollment['enrollment_id']) {
                        $isCompleted = $progressService->updateTheoryStepStatus($enrollment['enrollment_id'], $classId);
                        
                        // Se curso foi concluído, gerar notificação
                        if ($isCompleted) {
                            $stmt = $db->prepare(
                                "SELECT u.id FROM usuarios u 
                                 INNER JOIN students s ON s.user_id = u.id 
                                 WHERE s.id = ?"
                            );
                            $stmt->execute([$enrollment['student_id']]);
                            $user = $stmt->fetch();
                            
                            if ($user) {
                                $notificationModel->createNotification(
                                    $user['id'],
                                    'theory_course_completed',
                                    'Curso Teórico Concluído!',
                                    "Parabéns! Você concluiu o curso teórico.",
                                    "dashboard"
                                );
                            }
                        }
                    }
                }
            }
            
            $db->commit();
            $_SESSION['success'] = 'Presença salva com sucesso!';
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'Erro ao salvar presença: ' . $e->getMessage();
        }
        
        redirect(base_url("turmas-teoricas/{$classId}/sessoes/{$sessionId}/presenca"));
    }
}
