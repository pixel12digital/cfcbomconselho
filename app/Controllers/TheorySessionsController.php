<?php

namespace App\Controllers;

use App\Models\TheorySession;
use App\Models\TheoryClass;
use App\Models\TheoryEnrollment;
use App\Models\Lesson;
use App\Models\TheoryDiscipline;
use App\Services\PermissionService;
use App\Services\AuditService;
use App\Services\TheoryProgressService;
use App\Config\Constants;

class TheorySessionsController extends Controller
{
    private $cfcId;
    private $auditService;

    public function __construct()
    {
        $this->cfcId = $_SESSION['cfc_id'] ?? Constants::CFC_ID_DEFAULT;
        $this->auditService = new AuditService();
        
        if (!PermissionService::check('turmas_teoricas', 'view')) {
            $_SESSION['error'] = 'Você não tem permissão para acessar este módulo.';
            redirect(base_url('dashboard'));
        }
    }

    /**
     * Formulário nova sessão
     */
    public function novo($classId)
    {
        if (!PermissionService::check('turmas_teoricas', 'create')) {
            $_SESSION['error'] = 'Você não tem permissão para criar sessões.';
            redirect(base_url("turmas-teoricas/{$classId}"));
        }

        $classModel = new TheoryClass();
        $class = $classModel->findWithDetails($classId);

        if (!$class || $class['cfc_id'] != $this->cfcId) {
            $_SESSION['error'] = 'Turma não encontrada.';
            redirect(base_url('turmas-teoricas'));
        }

        // Buscar disciplinas do curso
        $courseModel = new \App\Models\TheoryCourse();
        $course = $courseModel->findWithDisciplines($class['course_id']);

        // Buscar estatísticas de sessões por disciplina (para contexto)
        $db = \App\Config\Database::getInstance()->getConnection();
        $sessionsByDiscipline = [];
        if ($course && !empty($course['disciplines'])) {
            foreach ($course['disciplines'] as $cd) {
                $disciplineId = $cd['discipline_id'];
                
                // Buscar sessões já agendadas (não canceladas) com detalhes
                $stmt = $db->prepare(
                    "SELECT id, starts_at, ends_at, status 
                     FROM theory_sessions 
                     WHERE class_id = ? AND discipline_id = ? AND status != 'canceled'
                     ORDER BY starts_at DESC"
                );
                $stmt->execute([$classId, $disciplineId]);
                $scheduledSessions = $stmt->fetchAll();
                $scheduledCount = count($scheduledSessions);
                
                // Última sessão agendada
                $lastSession = !empty($scheduledSessions) ? $scheduledSessions[0] : null;
                
                // Calcular previsto e restante
                // Se não tem minutes no curso, usar default_minutes da disciplina
                $minutes = $cd['minutes'] ?? $cd['default_minutes'] ?? 0;
                $lessonMinutes = $cd['lesson_minutes'] ?? $cd['default_lesson_minutes'] ?? 50;
                $lessonsPlanned = $minutes > 0 ? ceil($minutes / $lessonMinutes) : 0;
                $lessonsRemaining = max(0, $lessonsPlanned - $scheduledCount);
                
                $sessionsByDiscipline[$disciplineId] = [
                    'minutes' => $minutes,
                    'lessons_planned' => $lessonsPlanned,
                    'lessons_scheduled' => $scheduledCount,
                    'lessons_remaining' => $lessonsRemaining,
                    'lesson_minutes' => $lessonMinutes,
                    'last_session' => $lastSession ? [
                        'id' => $lastSession['id'],
                        'starts_at' => $lastSession['starts_at'],
                        'ends_at' => $lastSession['ends_at']
                    ] : null,
                    'sessions' => $scheduledSessions
                ];
            }
        }

        $data = [
            'pageTitle' => 'Nova Sessão Teórica',
            'class' => $class,
            'course' => $course,
            'session' => null,
            'sessionsByDiscipline' => $sessionsByDiscipline
        ];

        $this->view('theory_sessions/form', $data);
    }

    /**
     * Criar sessão
     */
    public function criar($classId)
    {
        if (!PermissionService::check('turmas_teoricas', 'create')) {
            $_SESSION['error'] = 'Você não tem permissão para criar sessões.';
            redirect(base_url("turmas-teoricas/{$classId}"));
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url("turmas-teoricas/{$classId}/sessoes/novo"));
        }

        $classModel = new TheoryClass();
        $class = $classModel->findWithDetails($classId);

        if (!$class || $class['cfc_id'] != $this->cfcId) {
            $_SESSION['error'] = 'Turma não encontrada.';
            redirect(base_url('turmas-teoricas'));
        }

        $disciplineId = (int)($_POST['discipline_id'] ?? 0);
        $startsAt = $_POST['starts_at'] ?? null;
        $lessonsCount = (int)($_POST['lessons_count'] ?? 1);
        $lessonMinutes = (int)($_POST['lesson_minutes'] ?? 50);
        $location = trim($_POST['location'] ?? '');

        if (!$disciplineId || !$startsAt) {
            $_SESSION['error'] = 'Preencha todos os campos obrigatórios.';
            redirect(base_url("turmas-teoricas/{$classId}/sessoes/novo"));
        }

        if ($lessonsCount < 1) {
            $_SESSION['error'] = 'Quantidade de aulas deve ser pelo menos 1.';
            redirect(base_url("turmas-teoricas/{$classId}/sessoes/novo"));
        }

        if ($lessonMinutes < 1 || $lessonMinutes > 180) {
            $_SESSION['error'] = 'Minutos por aula deve estar entre 1 e 180.';
            redirect(base_url("turmas-teoricas/{$classId}/sessoes/novo"));
        }

        // Validar data de início
        $startDateTime = new \DateTime($startsAt);
        
        // Calcular término automaticamente: início + (quantidade de aulas × minutos por aula)
        $totalMinutes = $lessonsCount * $lessonMinutes;
        $endDateTime = clone $startDateTime;
        $endDateTime->modify("+{$totalMinutes} minutes");

        // Verificar idempotência: se já existe sessão com mesmo class_id, discipline_id, starts_at
        $db = \App\Config\Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "SELECT id FROM theory_sessions 
             WHERE class_id = ? AND discipline_id = ? AND starts_at = ? AND status != 'canceled'"
        );
        $stmt->execute([$classId, $disciplineId, $startDateTime->format('Y-m-d H:i:s')]);
        $existingSession = $stmt->fetch();
        
        if ($existingSession) {
            $_SESSION['error'] = 'Já existe uma sessão agendada para este horário e disciplina.';
            redirect(base_url("turmas-teoricas/{$classId}/sessoes/novo"));
        }

        // Criar sessão dentro de transação
        $db = \App\Config\Database::getInstance()->getConnection();
        $db->beginTransaction();
        
        try {
            $sessionModel = new TheorySession();
            $sessionData = [
                'class_id' => $classId,
                'discipline_id' => $disciplineId,
                'starts_at' => $startDateTime->format('Y-m-d H:i:s'),
                'ends_at' => $endDateTime->format('Y-m-d H:i:s'),
                'location' => $location ?: null,
                'status' => 'scheduled',
                'created_by' => $_SESSION['user_id'] ?? null
            ];

            $sessionId = $sessionModel->create($sessionData);
            $this->auditService->logCreate('theory_sessions', $sessionId, $sessionData);

            // Criar lessons para cada aluno matriculado na turma
            $theoryEnrollmentModel = new TheoryEnrollment();
            $enrollments = $theoryEnrollmentModel->findByClass($classId);
            
            $lessonModel = new Lesson();
            $studentModel = new \App\Models\Student();
            $mainEnrollmentModel = new \App\Models\Enrollment();
            $createdLessons = [];
            $firstLessonId = null;

            foreach ($enrollments as $enrollment) {
                if ($enrollment['status'] !== 'active') continue;

                $student = $studentModel->find($enrollment['student_id']);
                if (!$student) continue;

                // Buscar matrícula ativa do aluno (se não houver enrollment_id na theory_enrollment)
                $enrollmentId = $enrollment['enrollment_id'];
                if (!$enrollmentId) {
                    // Tentar buscar matrícula ativa do aluno
                    $activeEnrollments = $mainEnrollmentModel->findByStudent($enrollment['student_id']);
                    // Filtrar apenas matrículas ativas
                    $activeEnrollments = array_filter($activeEnrollments, function($e) {
                        return $e['status'] === 'ativa';
                    });
                    if (!empty($activeEnrollments)) {
                        $enrollmentId = reset($activeEnrollments)['id']; // Pega a primeira matrícula ativa
                    }
                }

                // Calcular duração em minutos
                $durationMinutes = (int)(($endDateTime->getTimestamp() - $startDateTime->getTimestamp()) / 60);

                // Verificar idempotência: se já existe lesson para este aluno nesta sessão
                $stmt = $db->prepare(
                    "SELECT id FROM lessons 
                     WHERE theory_session_id = ? AND student_id = ?"
                );
                $stmt->execute([$sessionId, $enrollment['student_id']]);
                $existingLesson = $stmt->fetch();

                if ($existingLesson) {
                    continue; // Já existe, pular
                }

                // Criar lesson para este aluno
                $lessonData = [
                    'cfc_id' => $this->cfcId,
                    'student_id' => $enrollment['student_id'],
                    'enrollment_id' => $enrollmentId ?: 0, // Usa matrícula ativa ou 0
                    'instructor_id' => $class['instructor_id'],
                    'vehicle_id' => null, // Aulas teóricas não têm veículo
                    'type' => 'teoria',
                    'status' => 'agendada',
                    'scheduled_date' => $startDateTime->format('Y-m-d'),
                    'scheduled_time' => $startDateTime->format('H:i:s'),
                    'duration_minutes' => $durationMinutes,
                    'theory_session_id' => $sessionId,
                    'notes' => "Sessão teórica: {$class['course_name']}",
                    'created_by' => $_SESSION['user_id'] ?? null
                ];

                $lessonId = $lessonModel->create($lessonData);
                $createdLessons[] = $lessonId;
                
                if (!$firstLessonId) {
                    $firstLessonId = $lessonId;
                }
            }

            // Atualizar theory_sessions.lesson_id com o primeiro lesson criado (para referência)
            if ($firstLessonId) {
                $sessionModel->update($sessionId, ['lesson_id' => $firstLessonId]);
            }
            
            // Gerar notificação para alunos matriculados
            $notificationModel = new \App\Models\Notification();
            $disciplineModel = new TheoryDiscipline();
            $discipline = $disciplineModel->find($disciplineId);
            
            foreach ($enrollments as $enrollment) {
                if ($enrollment['status'] !== 'active') continue;
                
                // Buscar usuário do aluno
                $db = \App\Config\Database::getInstance()->getConnection();
                $stmt = $db->prepare(
                    "SELECT u.id FROM usuarios u 
                     INNER JOIN students s ON s.user_id = u.id 
                     WHERE s.id = ?"
                );
                $stmt->execute([$enrollment['student_id']]);
                $user = $stmt->fetch();
                
                if ($user) {
                    $sessionDate = $startDateTime->format('d/m/Y');
                    $sessionTime = $startDateTime->format('H:i');
                    $notificationModel->createNotification(
                        $user['id'],
                        'theory_session_scheduled',
                        'Nova Sessão Teórica Agendada',
                        "Sessão de {$discipline['name']} agendada para {$sessionDate} às {$sessionTime}",
                        "turmas-teoricas/{$classId}/sessoes/{$sessionId}/presenca"
                    );
                }
            }
            
            $db->commit();
            
            $_SESSION['success'] = 'Sessão criada com sucesso! ' . count($createdLessons) . ' aula(s) gerada(s) na agenda.';
            redirect(base_url("turmas-teoricas/{$classId}"));
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'Erro ao criar sessão: ' . $e->getMessage();
            redirect(base_url("turmas-teoricas/{$classId}/sessoes/novo"));
        }
    }

    /**
     * Cancelar sessão
     */
    public function cancelar($classId, $sessionId)
    {
        if (!PermissionService::check('turmas_teoricas', 'update')) {
            $_SESSION['error'] = 'Você não tem permissão para cancelar sessões.';
            redirect(base_url("turmas-teoricas/{$classId}"));
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url("turmas-teoricas/{$classId}"));
        }

        $sessionModel = new TheorySession();
        $session = $sessionModel->find($sessionId);

        if (!$session || $session['class_id'] != $classId) {
            $_SESSION['error'] = 'Sessão não encontrada.';
            redirect(base_url("turmas-teoricas/{$classId}"));
        }

        // Atualizar sessão dentro de transação
        $db = \App\Config\Database::getInstance()->getConnection();
        $db->beginTransaction();
        
        try {
            $sessionModel->update($sessionId, ['status' => 'canceled']);

            // Cancelar todas as lessons relacionadas
            $lessonModel = new Lesson();
            $stmt = $db->prepare(
                "UPDATE lessons SET status = 'cancelada' WHERE theory_session_id = ?"
            );
            $stmt->execute([$sessionId]);

            // Atualizar progresso (já que uma sessão foi cancelada)
            $progressService = new TheoryProgressService();
            $enrollmentModel = new TheoryEnrollment();
            $enrollments = $enrollmentModel->findByClass($classId);
            
            foreach ($enrollments as $enrollment) {
                if ($enrollment['enrollment_id']) {
                    $progressService->updateTheoryStepStatus($enrollment['enrollment_id'], $classId);
                }
            }
            
            // Gerar notificação para alunos
            $notificationModel = new \App\Models\Notification();
            foreach ($enrollments as $enrollment) {
                if ($enrollment['status'] !== 'active') continue;
                
                $db = \App\Config\Database::getInstance()->getConnection();
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
                        'theory_session_canceled',
                        'Sessão Teórica Cancelada',
                        "A sessão teórica foi cancelada",
                        "turmas-teoricas/{$classId}"
                    );
                }
            }
            
            $db->commit();
            $_SESSION['success'] = 'Sessão cancelada com sucesso!';
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'Erro ao cancelar sessão: ' . $e->getMessage();
        }
        
        redirect(base_url("turmas-teoricas/{$classId}"));
    }

    /**
     * Editar sessão (atualizar horário/local)
     */
    public function editar($classId, $sessionId)
    {
        if (!PermissionService::check('turmas_teoricas', 'update')) {
            $_SESSION['error'] = 'Você não tem permissão para editar sessões.';
            redirect(base_url("turmas-teoricas/{$classId}"));
        }

        $sessionModel = new TheorySession();
        $session = $sessionModel->findWithDetails($sessionId);

        if (!$session || $session['class_id'] != $classId) {
            $_SESSION['error'] = 'Sessão não encontrada.';
            redirect(base_url("turmas-teoricas/{$classId}"));
        }

        $classModel = new TheoryClass();
        $class = $classModel->findWithDetails($classId);
        $courseModel = new \App\Models\TheoryCourse();
        $course = $courseModel->findWithDisciplines($class['course_id']);

        // Buscar estatísticas de sessões por disciplina (para contexto)
        $db = \App\Config\Database::getInstance()->getConnection();
        $sessionsByDiscipline = [];
        if ($course && !empty($course['disciplines'])) {
            foreach ($course['disciplines'] as $cd) {
                $disciplineId = $cd['discipline_id'];
                
                // Buscar sessões já agendadas (não canceladas, excluindo a atual se estiver editando) com detalhes
                $stmt = $db->prepare(
                    "SELECT id, starts_at, ends_at, status 
                     FROM theory_sessions 
                     WHERE class_id = ? AND discipline_id = ? AND status != 'canceled' AND id != ?
                     ORDER BY starts_at DESC"
                );
                $stmt->execute([$classId, $disciplineId, $sessionId]);
                $scheduledSessions = $stmt->fetchAll();
                $scheduledCount = count($scheduledSessions);
                
                // Última sessão agendada
                $lastSession = !empty($scheduledSessions) ? $scheduledSessions[0] : null;
                
                // Calcular previsto e restante
                // Se não tem minutes no curso, usar default_minutes da disciplina
                $minutes = $cd['minutes'] ?? $cd['default_minutes'] ?? 0;
                $lessonMinutes = $cd['lesson_minutes'] ?? $cd['default_lesson_minutes'] ?? 50;
                $lessonsPlanned = $minutes > 0 ? ceil($minutes / $lessonMinutes) : 0;
                $lessonsRemaining = max(0, $lessonsPlanned - $scheduledCount);
                
                $sessionsByDiscipline[$disciplineId] = [
                    'minutes' => $minutes,
                    'lessons_planned' => $lessonsPlanned,
                    'lessons_scheduled' => $scheduledCount,
                    'lessons_remaining' => $lessonsRemaining,
                    'lesson_minutes' => $lessonMinutes,
                    'last_session' => $lastSession ? [
                        'id' => $lastSession['id'],
                        'starts_at' => $lastSession['starts_at'],
                        'ends_at' => $lastSession['ends_at']
                    ] : null,
                    'sessions' => $scheduledSessions
                ];
            }
        }

        $data = [
            'pageTitle' => 'Editar Sessão Teórica',
            'class' => $class,
            'course' => $course,
            'session' => $session,
            'sessionsByDiscipline' => $sessionsByDiscipline
        ];

        $this->view('theory_sessions/form', $data);
    }

    /**
     * Atualizar sessão (propaga para lessons)
     */
    public function atualizar($classId, $sessionId)
    {
        if (!PermissionService::check('turmas_teoricas', 'update')) {
            $_SESSION['error'] = 'Você não tem permissão para editar sessões.';
            redirect(base_url("turmas-teoricas/{$classId}"));
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url("turmas-teoricas/{$classId}/sessoes/{$sessionId}/editar"));
        }

        $sessionModel = new TheorySession();
        $session = $sessionModel->find($sessionId);

        if (!$session || $session['class_id'] != $classId) {
            $_SESSION['error'] = 'Sessão não encontrada.';
            redirect(base_url("turmas-teoricas/{$classId}"));
        }

        $startsAt = $_POST['starts_at'] ?? null;
        $lessonsCount = (int)($_POST['lessons_count'] ?? 1);
        $lessonMinutes = (int)($_POST['lesson_minutes'] ?? 50);
        $location = trim($_POST['location'] ?? '');

        if (!$startsAt) {
            $_SESSION['error'] = 'Preencha todos os campos obrigatórios.';
            redirect(base_url("turmas-teoricas/{$classId}/sessoes/{$sessionId}/editar"));
        }

        if ($lessonsCount < 1) {
            $_SESSION['error'] = 'Quantidade de aulas deve ser pelo menos 1.';
            redirect(base_url("turmas-teoricas/{$classId}/sessoes/{$sessionId}/editar"));
        }

        if ($lessonMinutes < 1 || $lessonMinutes > 180) {
            $_SESSION['error'] = 'Minutos por aula deve estar entre 1 e 180.';
            redirect(base_url("turmas-teoricas/{$classId}/sessoes/{$sessionId}/editar"));
        }

        // Validar data de início
        $startDateTime = new \DateTime($startsAt);
        
        // SEMPRE recalcular término no backend (ignorar valor do frontend)
        $totalMinutes = $lessonsCount * $lessonMinutes;
        $endDateTime = clone $startDateTime;
        $endDateTime->modify("+{$totalMinutes} minutes");

        $db = \App\Config\Database::getInstance()->getConnection();
        $db->beginTransaction();
        
        try {
            // Atualizar sessão
            $sessionModel->update($sessionId, [
                'starts_at' => $startDateTime->format('Y-m-d H:i:s'),
                'ends_at' => $endDateTime->format('Y-m-d H:i:s'),
                'location' => $location ?: null
            ]);

            // Calcular duração em minutos
            $durationMinutes = (int)(($endDateTime->getTimestamp() - $startDateTime->getTimestamp()) / 60);

            // Verificar se status da sessão foi alterado para 'done'
            $sessionAfterUpdate = $sessionModel->find($sessionId);
            $isDone = ($sessionAfterUpdate['status'] ?? '') === 'done';

            // Propagação: atualizar todas as lessons relacionadas via theory_session_id
            $lessonModel = new Lesson();
            $updateFields = [
                'scheduled_date' => $startDateTime->format('Y-m-d'),
                'scheduled_time' => $startDateTime->format('H:i:s'),
                'duration_minutes' => $durationMinutes
            ];
            
            // Se sessão está 'done', sincronizar status das lessons para 'concluida'
            if ($isDone) {
                $updateFields['status'] = 'concluida';
                $updateFields['completed_at'] = date('Y-m-d H:i:s');
            }
            
            $setClause = [];
            $params = [];
            foreach ($updateFields as $field => $value) {
                $setClause[] = "{$field} = ?";
                $params[] = $value;
            }
            $params[] = $sessionId;
            
            $stmt = $db->prepare(
                "UPDATE lessons 
                 SET " . implode(', ', $setClause) . "
                 WHERE theory_session_id = ?"
            );
            $stmt->execute($params);

            $db->commit();
            $_SESSION['success'] = 'Sessão atualizada com sucesso!';
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'Erro ao atualizar sessão: ' . $e->getMessage();
        }

        redirect(base_url("turmas-teoricas/{$classId}"));
    }
}
