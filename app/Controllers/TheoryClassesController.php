<?php

namespace App\Controllers;

use App\Models\TheoryClass;
use App\Models\TheoryCourse;
use App\Models\Instructor;
use App\Services\PermissionService;
use App\Services\AuditService;
use App\Config\Constants;

class TheoryClassesController extends Controller
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
     * Lista turmas
     */
    public function index()
    {
        $classModel = new TheoryClass();
        $status = $_GET['status'] ?? null;
        $classes = $classModel->findByCfc($this->cfcId, $status);

        $data = [
            'pageTitle' => 'Turmas Teóricas',
            'classes' => $classes,
            'status' => $status
        ];

        $this->view('theory_classes/index', $data);
    }

    /**
     * Formulário nova turma
     */
    public function novo()
    {
        if (!PermissionService::check('turmas_teoricas', 'create')) {
            $_SESSION['error'] = 'Você não tem permissão para criar turmas.';
            redirect(base_url('turmas-teoricas'));
        }

        $courseModel = new TheoryCourse();
        $instructorModel = new Instructor();
        
        $courses = $courseModel->findActiveByCfc($this->cfcId);
        $instructors = $instructorModel->findActive($this->cfcId);

        $data = [
            'pageTitle' => 'Nova Turma Teórica',
            'class' => null,
            'courses' => $courses,
            'instructors' => $instructors
        ];

        $this->view('theory_classes/form', $data);
    }

    /**
     * Criar turma
     */
    public function criar()
    {
        if (!PermissionService::check('turmas_teoricas', 'create')) {
            $_SESSION['error'] = 'Você não tem permissão para criar turmas.';
            redirect(base_url('turmas-teoricas'));
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url('turmas-teoricas/novo'));
        }

        $courseId = (int)($_POST['course_id'] ?? 0);
        $instructorId = (int)($_POST['instructor_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $startDate = $_POST['start_date'] ?? null;

        if (!$courseId || !$instructorId) {
            $_SESSION['error'] = 'Curso e instrutor são obrigatórios.';
            redirect(base_url('turmas-teoricas/novo'));
        }

        // Validar curso
        $courseModel = new TheoryCourse();
        $course = $courseModel->find($courseId);
        if (!$course || $course['cfc_id'] != $this->cfcId || !$course['active']) {
            $_SESSION['error'] = 'Curso inválido ou inativo.';
            redirect(base_url('turmas-teoricas/novo'));
        }

        // Validar instrutor
        $instructorModel = new Instructor();
        $instructor = $instructorModel->find($instructorId);
        if (!$instructor || $instructor['cfc_id'] != $this->cfcId || !$instructor['is_active']) {
            $_SESSION['error'] = 'Instrutor inválido ou inativo.';
            redirect(base_url('turmas-teoricas/novo'));
        }

        $classModel = new TheoryClass();
        $data = [
            'cfc_id' => $this->cfcId,
            'course_id' => $courseId,
            'instructor_id' => $instructorId,
            'name' => $name ?: null,
            'start_date' => $startDate ?: null,
            'status' => 'scheduled',
            'created_by' => $_SESSION['user_id'] ?? null
        ];

        $id = $classModel->create($data);
        $this->auditService->logCreate('theory_classes', $id, $data);

        $_SESSION['success'] = 'Turma criada com sucesso!';
        redirect(base_url("turmas-teoricas/{$id}"));
    }

    /**
     * Detalhes da turma
     */
    public function show($id)
    {
        $classModel = new TheoryClass();
        $class = $classModel->findWithDetails($id);

        if (!$class || $class['cfc_id'] != $this->cfcId) {
            $_SESSION['error'] = 'Turma não encontrada.';
            redirect(base_url('turmas-teoricas'));
        }

        // Buscar sessões
        $sessionModel = new \App\Models\TheorySession();
        $sessions = $sessionModel->findByClass($id);

        // Buscar matrículas
        $enrollmentModel = new \App\Models\TheoryEnrollment();
        $enrollments = $enrollmentModel->findByClass($id);

        $data = [
            'pageTitle' => 'Turma: ' . ($class['name'] ?: $class['course_name']),
            'class' => $class,
            'sessions' => $sessions,
            'enrollments' => $enrollments
        ];

        $this->view('theory_classes/show', $data);
    }

    /**
     * Formulário editar turma
     */
    public function editar($id)
    {
        if (!PermissionService::check('turmas_teoricas', 'update')) {
            $_SESSION['error'] = 'Você não tem permissão para editar turmas.';
            redirect(base_url('turmas-teoricas'));
        }

        $classModel = new TheoryClass();
        $class = $classModel->find($id);

        if (!$class || $class['cfc_id'] != $this->cfcId) {
            $_SESSION['error'] = 'Turma não encontrada.';
            redirect(base_url('turmas-teoricas'));
        }

        $courseModel = new TheoryCourse();
        $instructorModel = new Instructor();
        
        $courses = $courseModel->findActiveByCfc($this->cfcId);
        $instructors = $instructorModel->findActive($this->cfcId);

        $data = [
            'pageTitle' => 'Editar Turma Teórica',
            'class' => $class,
            'courses' => $courses,
            'instructors' => $instructors
        ];

        $this->view('theory_classes/form', $data);
    }

    /**
     * Atualizar turma
     */
    public function atualizar($id)
    {
        if (!PermissionService::check('turmas_teoricas', 'update')) {
            $_SESSION['error'] = 'Você não tem permissão para editar turmas.';
            redirect(base_url('turmas-teoricas'));
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url("turmas-teoricas/{$id}/editar"));
        }

        $classModel = new TheoryClass();
        $class = $classModel->find($id);

        if (!$class || $class['cfc_id'] != $this->cfcId) {
            $_SESSION['error'] = 'Turma não encontrada.';
            redirect(base_url('turmas-teoricas'));
        }

        $courseId = (int)($_POST['course_id'] ?? 0);
        $instructorId = (int)($_POST['instructor_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $startDate = $_POST['start_date'] ?? null;
        $status = $_POST['status'] ?? $class['status'];

        if (!$courseId || !$instructorId) {
            $_SESSION['error'] = 'Curso e instrutor são obrigatórios.';
            redirect(base_url("turmas-teoricas/{$id}/editar"));
        }

        $dataBefore = $class;
        $data = [
            'course_id' => $courseId,
            'instructor_id' => $instructorId,
            'name' => $name ?: null,
            'start_date' => $startDate ?: null,
            'status' => $status
        ];

        $classModel->update($id, $data);
        $this->auditService->logUpdate('theory_classes', $id, $dataBefore, array_merge($class, $data));

        $_SESSION['success'] = 'Turma atualizada com sucesso!';
        redirect(base_url("turmas-teoricas/{$id}"));
    }

    /**
     * Excluir turma
     */
    public function excluir($id)
    {
        if (!PermissionService::check('turmas_teoricas', 'delete')) {
            $_SESSION['error'] = 'Você não tem permissão para excluir turmas.';
            redirect(base_url('turmas-teoricas'));
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url('turmas-teoricas'));
        }

        $classModel = new TheoryClass();
        $class = $classModel->find($id);

        if (!$class || $class['cfc_id'] != $this->cfcId) {
            $_SESSION['error'] = 'Turma não encontrada.';
            redirect(base_url('turmas-teoricas'));
        }

        // Verificar se há alunos matriculados
        $enrollmentModel = new \App\Models\TheoryEnrollment();
        $enrollments = $enrollmentModel->findByClass($id);
        $activeEnrollments = array_filter($enrollments, function($e) {
            return $e['status'] === 'active';
        });

        if (!empty($activeEnrollments)) {
            $_SESSION['error'] = 'Não é possível excluir uma turma com alunos matriculados. Remova os alunos primeiro ou cancele a turma.';
            redirect(base_url("turmas-teoricas/{$id}"));
        }

        // Verificar se há sessões agendadas ou em andamento
        $sessionModel = new \App\Models\TheorySession();
        $sessions = $sessionModel->findByClass($id);
        $activeSessions = array_filter($sessions, function($s) {
            return in_array($s['status'], ['scheduled', 'in_progress']);
        });

        if (!empty($activeSessions)) {
            $_SESSION['error'] = 'Não é possível excluir uma turma com sessões agendadas ou em andamento. Cancele ou finalize as sessões primeiro.';
            redirect(base_url("turmas-teoricas/{$id}"));
        }

        $dataBefore = $class;

        $db = \App\Config\Database::getInstance()->getConnection();
        $db->beginTransaction();

        try {
            // Excluir sessões relacionadas
            $stmt = $db->prepare("DELETE FROM theory_sessions WHERE class_id = ?");
            $stmt->execute([$id]);
            
            // Excluir matrículas relacionadas (se houver inativas)
            $stmt = $db->prepare("DELETE FROM theory_enrollments WHERE class_id = ?");
            $stmt->execute([$id]);
            
            // Excluir turma
            $classModel->delete($id);
            
            $this->auditService->logDelete('theory_classes', $id, $dataBefore);

            $db->commit();
            $_SESSION['success'] = 'Turma excluída com sucesso!';
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'Erro ao excluir turma: ' . $e->getMessage();
        }

        redirect(base_url('turmas-teoricas'));
    }
}
