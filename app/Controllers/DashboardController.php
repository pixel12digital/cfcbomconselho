<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Step;
use App\Models\StudentStep;
use App\Models\RescheduleRequest;
use App\Models\Notification;
use App\Config\Constants;
use App\Config\Database;

class DashboardController extends Controller
{
    public function index()
    {
        $currentRole = $_SESSION['current_role'] ?? '';
        $userId = $_SESSION['user_id'] ?? null;
        
        // Se for ALUNO, carregar dados específicos
        if ($currentRole === Constants::ROLE_ALUNO && $userId) {
            return $this->dashboardAluno($userId);
        }
        
        // Se for INSTRUTOR, carregar dados específicos
        if ($currentRole === Constants::ROLE_INSTRUTOR && $userId) {
            return $this->dashboardInstrutor($userId);
        }
        
        // Se for ADMIN ou SECRETARIA, carregar dashboard administrativo
        if (($currentRole === Constants::ROLE_ADMIN || $currentRole === Constants::ROLE_SECRETARIA) && $userId) {
            return $this->dashboardAdmin($userId);
        }
        
        // Dashboard genérico para outros perfis
        $data = [
            'pageTitle' => 'Dashboard'
        ];
        $this->view('dashboard', $data);
    }
    
    private function dashboardAluno($userId)
    {
        $userModel = new User();
        $user = $userModel->findWithLinks($userId);
        
        if (!$user || empty($user['student_id'])) {
            // Aluno sem vínculo, mostrar mensagem
            $data = [
                'pageTitle' => 'Dashboard',
                'student' => null
            ];
            $this->view('dashboard/aluno', $data);
            return;
        }
        
        $studentId = $user['student_id'];
        $studentModel = new Student();
        $enrollmentModel = new Enrollment();
        $lessonModel = new Lesson();
        $stepModel = new Step();
        $studentStepModel = new StudentStep();
        
        // Buscar dados do aluno
        $student = $studentModel->find($studentId);
        $enrollments = $enrollmentModel->findByStudent($studentId);
        $nextLesson = $lessonModel->findNextByStudent($studentId);
        
        // Verificar se existe solicitação pendente para a próxima aula
        $hasPendingRequest = false;
        if ($nextLesson) {
            $rescheduleRequestModel = new RescheduleRequest();
            $pendingRequest = $rescheduleRequestModel->findPendingByLessonAndStudent($nextLesson['id'], $studentId);
            $hasPendingRequest = !empty($pendingRequest);
        }
        
        // Buscar progresso da primeira matrícula ativa
        $activeEnrollment = null;
        $steps = [];
        $studentSteps = [];
        
        foreach ($enrollments as $enr) {
            if ($enr['status'] === 'ativa') {
                $activeEnrollment = $enr;
                break;
            }
        }
        
        if ($activeEnrollment) {
            $steps = $stepModel->findAllActive();
            $studentSteps = $studentStepModel->findByEnrollment($activeEnrollment['id']);
        }
        
        // Calcular situação financeira
        $totalDebt = 0;
        $totalPaid = 0;
        $hasPending = false;
        
        foreach ($enrollments as $enr) {
            if ($enr['status'] !== 'cancelada') {
                $finalPrice = (float)$enr['final_price'];
                $entryAmount = (float)($enr['entry_amount'] ?? 0);
                
                $totalPaid += $entryAmount;
                $remainingDebt = max(0, $finalPrice - $entryAmount);
                $totalDebt += $remainingDebt;
                
                if ($remainingDebt > 0) {
                    $hasPending = true;
                }
            }
        }
        
        // Determinar status geral
        $statusGeral = 'Em andamento';
        if (empty($enrollments)) {
            $statusGeral = 'Sem matrícula';
        } elseif ($hasPending) {
            $statusGeral = 'Pendência financeira';
        } elseif (!empty($enrollments) && $enrollments[0]['status'] === 'concluida') {
            $statusGeral = 'Concluído';
        }
        
        $data = [
            'pageTitle' => 'Meu Progresso',
            'student' => $student,
            'enrollments' => $enrollments,
            'activeEnrollment' => $activeEnrollment,
            'nextLesson' => $nextLesson,
            'steps' => $steps,
            'studentSteps' => $studentSteps,
            'statusGeral' => $statusGeral,
            'totalDebt' => $totalDebt,
            'totalPaid' => $totalPaid,
            'hasPending' => $hasPending,
            'hasPendingRequest' => $hasPendingRequest
        ];
        
        $this->view('dashboard/aluno', $data);
    }
    
    private function dashboardInstrutor($userId)
    {
        $userModel = new User();
        $user = $userModel->findWithLinks($userId);
        
        if (!$user || empty($user['instructor_id'])) {
            // Instrutor sem vínculo, mostrar mensagem
            $data = [
                'pageTitle' => 'Dashboard',
                'instructor' => null
            ];
            $this->view('dashboard/instrutor', $data);
            return;
        }
        
        $instructorId = $user['instructor_id'];
        $db = Database::getInstance()->getConnection();
        $cfcId = $_SESSION['cfc_id'] ?? Constants::CFC_ID_DEFAULT;
        
        // Buscar próxima aula agendada
        $today = date('Y-m-d');
        $now = date('H:i:s');
        $stmt = $db->prepare(
            "SELECT l.*,
                    s.name as student_name,
                    v.plate as vehicle_plate
             FROM lessons l
             INNER JOIN students s ON l.student_id = s.id
             LEFT JOIN vehicles v ON l.vehicle_id = v.id
             WHERE l.instructor_id = ?
               AND l.cfc_id = ?
               AND l.status = 'agendada'
               AND (l.scheduled_date > ? OR (l.scheduled_date = ? AND l.scheduled_time >= ?))
             ORDER BY l.scheduled_date ASC, l.scheduled_time ASC
             LIMIT 1"
        );
        $stmt->execute([$instructorId, $cfcId, $today, $today, $now]);
        $nextLesson = $stmt->fetch();
        
        // Buscar aulas de hoje
        $stmt = $db->prepare(
            "SELECT l.*,
                    s.name as student_name,
                    v.plate as vehicle_plate
             FROM lessons l
             INNER JOIN students s ON l.student_id = s.id
             LEFT JOIN vehicles v ON l.vehicle_id = v.id
             WHERE l.instructor_id = ?
               AND l.cfc_id = ?
               AND l.scheduled_date = ?
               AND l.status != 'cancelada'
             ORDER BY l.scheduled_time ASC"
        );
        $stmt->execute([$instructorId, $cfcId, $today]);
        $todayLessons = $stmt->fetchAll();
        
        // Contadores
        $totalToday = count($todayLessons);
        $completedToday = count(array_filter($todayLessons, function($l) {
            return $l['status'] === 'concluida';
        }));
        $pendingToday = $totalToday - $completedToday;
        
        $data = [
            'pageTitle' => 'Dashboard',
            'instructor' => $user,
            'nextLesson' => $nextLesson,
            'todayLessons' => $todayLessons,
            'totalToday' => $totalToday,
            'completedToday' => $completedToday,
            'pendingToday' => $pendingToday
        ];
        
        $this->view('dashboard/instrutor', $data);
    }
    
    private function dashboardAdmin($userId)
    {
        $cfcId = $_SESSION['cfc_id'] ?? Constants::CFC_ID_DEFAULT;
        $db = Database::getInstance()->getConnection();
        $today = date('Y-m-d');
        $now = date('H:i:s');
        
        $lessonModel = new Lesson();
        $rescheduleRequestModel = new RescheduleRequest();
        $notificationModel = new Notification();
        $enrollmentModel = new Enrollment();
        
        // 1. Aulas de hoje (com contadores por status) - incluir canceladas para contadores
        $todayLessons = $lessonModel->findByPeriod($cfcId, $today, $today, ['show_canceled' => true]);
        
        // Contadores do dia
        $totalToday = count($todayLessons);
        $completedToday = count(array_filter($todayLessons, function($l) {
            return $l['status'] === Constants::AULA_CONCLUIDA;
        }));
        $inProgressToday = count(array_filter($todayLessons, function($l) {
            return $l['status'] === Constants::AULA_EM_ANDAMENTO;
        }));
        $scheduledToday = count(array_filter($todayLessons, function($l) {
            return $l['status'] === Constants::AULA_AGENDADA;
        }));
        $canceledToday = count(array_filter($todayLessons, function($l) {
            return $l['status'] === Constants::AULA_CANCELADA;
        }));
        
        // Ordenar aulas de hoje por horário
        usort($todayLessons, function($a, $b) {
            $timeA = strtotime($a['scheduled_time']);
            $timeB = strtotime($b['scheduled_time']);
            return $timeA <=> $timeB;
        });
        
        // 2. Próximas aulas (top 10 futuras, independente de hoje)
        // Buscar aulas futuras a partir de hoje (mas excluir as que já passaram hoje)
        $nextMonth = date('Y-m-d', strtotime('+30 days'));
        $allUpcoming = $lessonModel->findByPeriod($cfcId, $today, $nextMonth, ['show_canceled' => false]);
        
        // Filtrar apenas aulas que ainda não aconteceram (futuras)
        $upcomingLessons = array_filter($allUpcoming, function($lesson) use ($today, $now) {
            $lessonDate = $lesson['scheduled_date'];
            $lessonTime = $lesson['scheduled_time'];
            
            // Se for hoje, verificar se o horário ainda não passou
            if ($lessonDate === $today) {
                return $lessonTime >= $now;
            }
            // Se for futuro, incluir
            return $lessonDate > $today;
        });
        
        // Ordenar e limitar a 10
        usort($upcomingLessons, function($a, $b) {
            $dateA = strtotime($a['scheduled_date'] . ' ' . $a['scheduled_time']);
            $dateB = strtotime($b['scheduled_date'] . ' ' . $b['scheduled_time']);
            return $dateA <=> $dateB;
        });
        $upcomingLessons = array_slice($upcomingLessons, 0, 10);
        
        // 3. Solicitações de reagendamento pendentes (top 5)
        $pendingRequests = $rescheduleRequestModel->findPending($cfcId);
        $pendingRequests = array_slice($pendingRequests, 0, 5);
        $pendingRequestsCount = $rescheduleRequestModel->countPending($cfcId);
        
        // 4. Notificações não lidas (top 5) + contador total
        $unreadNotifications = $notificationModel->findByUser($userId, true, 5);
        $unreadNotificationsCount = $notificationModel->countUnread($userId);
        
        // 5. Resumo financeiro
        // Total recebido: soma de entry_amount de todas as matrículas não canceladas
        // Total a receber: soma de (final_price - entry_amount) onde final_price > entry_amount
        // Alunos com saldo devedor: contagem de alunos únicos com saldo > 0
        
        $stmt = $db->prepare(
            "SELECT 
                SUM(CASE WHEN e.status != 'cancelada' THEN COALESCE(e.entry_amount, 0) ELSE 0 END) as total_recebido,
                SUM(CASE WHEN e.status != 'cancelada' AND e.final_price > COALESCE(e.entry_amount, 0) 
                    THEN (e.final_price - COALESCE(e.entry_amount, 0)) ELSE 0 END) as total_a_receber
             FROM enrollments e
             INNER JOIN students s ON e.student_id = s.id
             WHERE s.cfc_id = ?"
        );
        $stmt->execute([$cfcId]);
        $financialSummary = $stmt->fetch();
        
        $totalRecebido = (float)($financialSummary['total_recebido'] ?? 0);
        $totalAReceber = (float)($financialSummary['total_a_receber'] ?? 0);
        
        // Contar alunos com saldo devedor > 0
        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT e.student_id) as qtd_devedores
             FROM enrollments e
             INNER JOIN students s ON e.student_id = s.id
             WHERE s.cfc_id = ?
               AND e.status != 'cancelada'
               AND e.final_price > COALESCE(e.entry_amount, 0)"
        );
        $stmt->execute([$cfcId]);
        $debtorsResult = $stmt->fetch();
        $qtdDevedores = (int)($debtorsResult['qtd_devedores'] ?? 0);
        
        // 6. Alertas: verificar se há aulas com reagendamento pendente para hoje/amanhã
        $hasUrgentReschedule = false;
        $tomorrowDate = date('Y-m-d', strtotime('+1 day'));
        foreach ($pendingRequests as $req) {
            $lessonDate = $req['scheduled_date'] ?? '';
            if ($lessonDate === $today || $lessonDate === $tomorrowDate) {
                $hasUrgentReschedule = true;
                break;
            }
        }
        
        $data = [
            'pageTitle' => 'Dashboard',
            'todayLessons' => $todayLessons,
            'totalToday' => $totalToday,
            'completedToday' => $completedToday,
            'inProgressToday' => $inProgressToday,
            'scheduledToday' => $scheduledToday,
            'canceledToday' => $canceledToday,
            'upcomingLessons' => $upcomingLessons,
            'pendingRequests' => $pendingRequests,
            'pendingRequestsCount' => $pendingRequestsCount,
            'unreadNotifications' => $unreadNotifications,
            'unreadNotificationsCount' => $unreadNotificationsCount,
            'totalRecebido' => $totalRecebido,
            'totalAReceber' => $totalAReceber,
            'qtdDevedores' => $qtdDevedores,
            'hasUrgentReschedule' => $hasUrgentReschedule
        ];
        
        $this->view('dashboard/admin', $data);
    }
}
