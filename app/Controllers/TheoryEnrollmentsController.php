<?php

namespace App\Controllers;

use App\Models\TheoryEnrollment;
use App\Models\TheoryClass;
use App\Models\Student;
use App\Services\PermissionService;
use App\Services\AuditService;
use App\Config\Constants;

class TheoryEnrollmentsController extends Controller
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
     * Formulário adicionar aluno à turma
     */
    public function novo($classId)
    {
        if (!PermissionService::check('turmas_teoricas', 'create')) {
            $_SESSION['error'] = 'Você não tem permissão para matricular alunos.';
            redirect(base_url("turmas-teoricas/{$classId}"));
        }

        $classModel = new TheoryClass();
        $class = $classModel->find($classId);

        if (!$class || $class['cfc_id'] != $this->cfcId) {
            $_SESSION['error'] = 'Turma não encontrada.';
            redirect(base_url('turmas-teoricas'));
        }

        // Buscar alunos do CFC (ativos)
        $studentModel = new Student();
        $students = $studentModel->findByCfc($this->cfcId);

        // Buscar alunos já matriculados
        $enrollmentModel = new TheoryEnrollment();
        $enrolled = $enrollmentModel->findByClass($classId);
        $enrolledIds = array_column($enrolled, 'student_id');

        // Filtrar alunos já matriculados
        $availableStudents = array_filter($students, function($student) use ($enrolledIds) {
            return !in_array($student['id'], $enrolledIds);
        });

        $data = [
            'pageTitle' => 'Matricular Aluno na Turma',
            'class' => $class,
            'students' => $availableStudents
        ];

        $this->view('theory_enrollments/form', $data);
    }

    /**
     * API: Buscar matrículas ativas de um aluno (AJAX)
     */
    public function buscarMatriculas($classId)
    {
        if (!PermissionService::check('turmas_teoricas', 'view')) {
            http_response_code(403);
            echo json_encode(['error' => 'Sem permissão']);
            exit;
        }

        // Validar turma
        $classModel = new TheoryClass();
        $class = $classModel->find($classId);
        if (!$class || $class['cfc_id'] != $this->cfcId) {
            http_response_code(404);
            echo json_encode(['error' => 'Turma não encontrada']);
            exit;
        }

        $studentId = (int)($_GET['student_id'] ?? 0);
        
        if (!$studentId) {
            http_response_code(400);
            echo json_encode(['error' => 'student_id é obrigatório']);
            exit;
        }

        $studentModel = new Student();
        $student = $studentModel->find($studentId);

        if (!$student || $student['cfc_id'] != $this->cfcId) {
            http_response_code(404);
            echo json_encode(['error' => 'Aluno não encontrado']);
            exit;
        }

        // Buscar matrículas ativas do aluno (do mesmo CFC)
        $enrollmentModel = new \App\Models\Enrollment();
        $enrollments = $enrollmentModel->findByStudent($studentId, $this->cfcId);
        
        // Filtrar apenas matrículas ativas
        $activeEnrollments = array_filter($enrollments, function($e) {
            return $e['status'] === 'ativa';
        });

        // Formatar para resposta
        $formatted = [];
        foreach ($activeEnrollments as $enrollment) {
            $formatted[] = [
                'id' => $enrollment['id'],
                'label' => "#{$enrollment['id']} — {$enrollment['service_name']} — {$enrollment['status']} — " . date('d/m/Y', strtotime($enrollment['created_at']))
            ];
        }

        header('Content-Type: application/json');
        echo json_encode([
            'enrollments' => array_values($formatted),
            'count' => count($formatted)
        ]);
        exit;
    }

    /**
     * Matricular aluno na turma
     */
    public function criar($classId)
    {
        if (!PermissionService::check('turmas_teoricas', 'create')) {
            $_SESSION['error'] = 'Você não tem permissão para matricular alunos.';
            redirect(base_url("turmas-teoricas/{$classId}"));
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url("turmas-teoricas/{$classId}/matricular"));
        }

        $classModel = new TheoryClass();
        $class = $classModel->find($classId);

        if (!$class || $class['cfc_id'] != $this->cfcId) {
            $_SESSION['error'] = 'Turma não encontrada.';
            redirect(base_url('turmas-teoricas'));
        }

        $studentId = (int)($_POST['student_id'] ?? 0);
        $enrollmentId = !empty($_POST['enrollment_id']) ? (int)$_POST['enrollment_id'] : null;

        if (!$studentId) {
            $_SESSION['error'] = 'Selecione um aluno.';
            redirect(base_url("turmas-teoricas/{$classId}/matricular"));
        }

        // Validar aluno
        $studentModel = new Student();
        $student = $studentModel->find($studentId);
        if (!$student || $student['cfc_id'] != $this->cfcId) {
            $_SESSION['error'] = 'Aluno não encontrado.';
            redirect(base_url("turmas-teoricas/{$classId}/matricular"));
        }

        // Resolver enrollment_id automaticamente se não informado
        if (!$enrollmentId) {
            $enrollmentModel = new \App\Models\Enrollment();
            $enrollments = $enrollmentModel->findByStudent($studentId, $this->cfcId);
            
            // Filtrar apenas matrículas ativas
            $activeEnrollments = array_filter($enrollments, function($e) {
                return $e['status'] === 'ativa';
            });
            
            if (empty($activeEnrollments)) {
                $_SESSION['error'] = 'Aluno sem matrícula ativa. Crie/ative uma matrícula antes de vincular à turma.';
                redirect(base_url("turmas-teoricas/{$classId}/matricular"));
            }
            
            // Se houver múltiplas, usar a mais recente (ou a escolhida no select)
            // Se não foi escolhida no select mas há múltiplas, usar a mais recente
            if (count($activeEnrollments) > 1 && !$enrollmentId) {
                // Se múltiplas e não escolheu, usar a mais recente
                usort($activeEnrollments, function($a, $b) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });
                $enrollmentId = reset($activeEnrollments)['id'];
            } else {
                // Se apenas uma, usar ela
                $enrollmentId = reset($activeEnrollments)['id'];
            }
        } else {
            // Validar se enrollment_id informado é válido e pertence ao aluno
            $enrollmentModel = new \App\Models\Enrollment();
            $enrollment = $enrollmentModel->find($enrollmentId);
            if (!$enrollment || $enrollment['student_id'] != $studentId || $enrollment['status'] !== 'ativa') {
                $_SESSION['error'] = 'Matrícula inválida ou inativa.';
                redirect(base_url("turmas-teoricas/{$classId}/matricular"));
            }
        }

        // Verificar se já está matriculado
        $enrollmentModel = new TheoryEnrollment();
        if ($enrollmentModel->isEnrolled($classId, $studentId)) {
            $_SESSION['error'] = 'Aluno já está matriculado nesta turma.';
            redirect(base_url("turmas-teoricas/{$classId}/matricular"));
        }

        // Criar matrícula (idempotente via UNIQUE KEY)
        $db = \App\Config\Database::getInstance()->getConnection();
        $db->beginTransaction();
        
        try {
            // Tentar inserir (UNIQUE KEY class_student previne duplicidade)
            $data = [
                'class_id' => $classId,
                'student_id' => $studentId,
                'enrollment_id' => $enrollmentId,
                'status' => 'active',
                'created_by' => $_SESSION['user_id'] ?? null
            ];

            $id = $enrollmentModel->create($data);
            $this->auditService->logCreate('theory_enrollments', $id, $data);

            // Gerar notificação para o aluno
            $notificationModel = new \App\Models\Notification();
            $db = \App\Config\Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                "SELECT u.id FROM usuarios u 
                 INNER JOIN students s ON s.user_id = u.id 
                 WHERE s.id = ?"
            );
            $stmt->execute([$studentId]);
            $user = $stmt->fetch();
            
            if ($user) {
                $classModel = new \App\Models\TheoryClass();
                $class = $classModel->findWithDetails($classId);
                
                $notificationModel->createNotification(
                    $user['id'],
                    'theory_class_enrolled',
                    'Você foi matriculado em uma turma teórica',
                    "Você foi matriculado na turma: {$class['course_name']}",
                    "turmas-teoricas/{$classId}"
                );
            }
            
            $db->commit();
            $_SESSION['success'] = 'Aluno matriculado com sucesso!';
        } catch (\PDOException $e) {
            $db->rollBack();
            // Se erro de UNIQUE KEY, aluno já está matriculado
            if ($e->getCode() === '23000' || strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $_SESSION['error'] = 'Aluno já está matriculado nesta turma.';
            } else {
                $_SESSION['error'] = 'Erro ao matricular aluno: ' . $e->getMessage();
            }
        }
        
        redirect(base_url("turmas-teoricas/{$classId}"));
    }

    /**
     * Remover aluno da turma
     */
    public function remover($classId, $enrollmentId)
    {
        if (!PermissionService::check('turmas_teoricas', 'update')) {
            $_SESSION['error'] = 'Você não tem permissão para remover alunos.';
            redirect(base_url("turmas-teoricas/{$classId}"));
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url("turmas-teoricas/{$classId}"));
        }

        $enrollmentModel = new TheoryEnrollment();
        $enrollment = $enrollmentModel->find($enrollmentId);

        if (!$enrollment || $enrollment['class_id'] != $classId) {
            $_SESSION['error'] = 'Matrícula não encontrada.';
            redirect(base_url("turmas-teoricas/{$classId}"));
        }

        // Atualizar status para 'dropped'
        $enrollmentModel->update($enrollmentId, ['status' => 'dropped']);

        $_SESSION['success'] = 'Aluno removido da turma com sucesso!';
        redirect(base_url("turmas-teoricas/{$classId}"));
    }
}
