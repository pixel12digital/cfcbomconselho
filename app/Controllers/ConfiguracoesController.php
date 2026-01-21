<?php

namespace App\Controllers;

use App\Models\Setting;
use App\Models\TheoryDiscipline;
use App\Models\TheoryCourse;
use App\Models\TheoryCourseDiscipline;
use App\Models\Cfc;
use App\Services\PermissionService;
use App\Services\EmailService;
use App\Services\AuditService;
use App\Helpers\PwaIconGenerator;
use App\Config\Constants;

class ConfiguracoesController extends Controller
{
    private $cfcId;
    private $auditService;

    public function __construct()
    {
        $this->cfcId = $_SESSION['cfc_id'] ?? Constants::CFC_ID_DEFAULT;
        $this->auditService = new AuditService();
        
        // Apenas ADMIN pode acessar configurações
        if ($_SESSION['current_role'] !== 'ADMIN') {
            $_SESSION['error'] = 'Você não tem permissão para acessar este módulo.';
            redirect(base_url('dashboard'));
        }
    }

    /**
     * Tela de configurações SMTP
     */
    public function smtp()
    {
        $settingModel = new Setting();
        $settings = $settingModel->findByCfc($this->cfcId);

        $data = [
            'pageTitle' => 'Configurações SMTP',
            'settings' => $settings
        ];

        $this->view('configuracoes/smtp', $data);
    }

    /**
     * Salva configurações SMTP
     */
    public function salvarSmtp()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('configuracoes/smtp'));
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url('configuracoes/smtp'));
        }

        $host = trim($_POST['host'] ?? '');
        $port = (int)($_POST['port'] ?? 587);
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $encryption = $_POST['encryption'] ?? 'tls';
        $fromEmail = trim($_POST['from_email'] ?? '');
        $fromName = trim($_POST['from_name'] ?? '');

        // Validações
        if (empty($host) || empty($username) || empty($fromEmail)) {
            $_SESSION['error'] = 'Preencha todos os campos obrigatórios.';
            redirect(base_url('configuracoes/smtp'));
        }

        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'E-mail remetente inválido.';
            redirect(base_url('configuracoes/smtp'));
        }

        if (!in_array($encryption, ['tls', 'ssl', 'none'])) {
            $encryption = 'tls';
        }

        // Se senha não foi informada e já existe configuração, manter a atual
        if (empty($password) && $settings) {
            $encryptedPassword = $settings['password']; // Já está criptografada
        } else {
            // Criptografar senha (usar base64 simples por enquanto, ideal seria usar openssl)
            $encryptedPassword = base64_encode($password);
        }

        $settingModel = new Setting();
        $data = [
            'host' => $host,
            'port' => $port,
            'username' => $username,
            'password' => $encryptedPassword,
            'encryption' => $encryption,
            'from_email' => $fromEmail,
            'from_name' => $fromName
        ];

        if ($settingModel->save($this->cfcId, $data)) {
            $_SESSION['success'] = 'Configurações SMTP salvas com sucesso!';
        } else {
            $_SESSION['error'] = 'Erro ao salvar configurações.';
        }

        redirect(base_url('configuracoes/smtp'));
    }

    /**
     * Testa envio de e-mail
     */
    public function testarSmtp()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('configuracoes/smtp'));
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url('configuracoes/smtp'));
        }

        $testEmail = trim($_POST['test_email'] ?? '');

        if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'E-mail de teste inválido.';
            redirect(base_url('configuracoes/smtp'));
        }

        try {
            $emailService = new EmailService();
            $emailService->test($testEmail);
            $_SESSION['success'] = 'E-mail de teste enviado com sucesso! Verifique a caixa de entrada.';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Erro ao enviar e-mail de teste: ' . $e->getMessage();
        }

        redirect(base_url('configuracoes/smtp'));
    }

    // ============================================
    // MÓDULO: CURSO TEÓRICO - CONFIGURAÇÕES
    // ============================================

    /**
     * Lista disciplinas
     */
    public function disciplinas()
    {
        $disciplineModel = new TheoryDiscipline();
        $disciplines = $disciplineModel->findByCfc($this->cfcId);

        $data = [
            'pageTitle' => 'Disciplinas Teóricas',
            'disciplines' => $disciplines
        ];

        $this->view('configuracoes/disciplinas/index', $data);
    }

    /**
     * Formulário nova disciplina
     */
    public function disciplinaNovo()
    {
        $data = [
            'pageTitle' => 'Nova Disciplina',
            'discipline' => null
        ];
        $this->view('configuracoes/disciplinas/form', $data);
    }

    /**
     * Criar disciplina
     */
    public function disciplinaCriar()
    {
        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url('configuracoes/disciplinas/novo'));
        }

        $name = trim($_POST['name'] ?? '');
        $lessonsCount = !empty($_POST['default_lessons_count']) ? (int)$_POST['default_lessons_count'] : null;
        $lessonMinutes = !empty($_POST['default_lesson_minutes']) ? (int)$_POST['default_lesson_minutes'] : 50;
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $active = isset($_POST['active']) ? 1 : 0;

        if (empty($name)) {
            $_SESSION['error'] = 'Nome da disciplina é obrigatório.';
            redirect(base_url('configuracoes/disciplinas/novo'));
        }

        // Validar quantidade de aulas
        if ($lessonsCount !== null && $lessonsCount <= 0) {
            $_SESSION['error'] = 'A quantidade de aulas deve ser maior que zero.';
            redirect(base_url('configuracoes/disciplinas/novo'));
        }

        // Validar minutos por aula
        if ($lessonMinutes <= 0 || $lessonMinutes > 180) {
            $_SESSION['error'] = 'Minutos por aula deve estar entre 1 e 180.';
            redirect(base_url('configuracoes/disciplinas/novo'));
        }

        // Calcular total de minutos (backend sempre recalcula)
        $defaultMinutes = null;
        if ($lessonsCount !== null && $lessonsCount > 0) {
            $defaultMinutes = $lessonsCount * $lessonMinutes;
        }

        $disciplineModel = new TheoryDiscipline();
        $data = [
            'cfc_id' => $this->cfcId,
            'name' => $name,
            'default_minutes' => $defaultMinutes,
            'default_lessons_count' => $lessonsCount,
            'default_lesson_minutes' => $lessonMinutes,
            'sort_order' => $sortOrder,
            'active' => $active
        ];

        $id = $disciplineModel->create($data);
        $this->auditService->logCreate('theory_disciplines', $id, $data);

        $_SESSION['success'] = 'Disciplina criada com sucesso!';
        redirect(base_url('configuracoes/disciplinas'));
    }

    /**
     * Formulário editar disciplina
     */
    public function disciplinaEditar($id)
    {
        $disciplineModel = new TheoryDiscipline();
        $discipline = $disciplineModel->find($id);

        if (!$discipline || $discipline['cfc_id'] != $this->cfcId) {
            $_SESSION['error'] = 'Disciplina não encontrada.';
            redirect(base_url('configuracoes/disciplinas'));
        }

        $data = [
            'pageTitle' => 'Editar Disciplina',
            'discipline' => $discipline
        ];
        $this->view('configuracoes/disciplinas/form', $data);
    }

    /**
     * Atualizar disciplina
     */
    public function disciplinaAtualizar($id)
    {
        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url("configuracoes/disciplinas/{$id}/editar"));
        }

        $disciplineModel = new TheoryDiscipline();
        $discipline = $disciplineModel->find($id);

        if (!$discipline || $discipline['cfc_id'] != $this->cfcId) {
            $_SESSION['error'] = 'Disciplina não encontrada.';
            redirect(base_url('configuracoes/disciplinas'));
        }

        $name = trim($_POST['name'] ?? '');
        $lessonsCount = !empty($_POST['default_lessons_count']) ? (int)$_POST['default_lessons_count'] : null;
        $lessonMinutes = !empty($_POST['default_lesson_minutes']) ? (int)$_POST['default_lesson_minutes'] : 50;
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $active = isset($_POST['active']) ? 1 : 0;

        if (empty($name)) {
            $_SESSION['error'] = 'Nome da disciplina é obrigatório.';
            redirect(base_url("configuracoes/disciplinas/{$id}/editar"));
        }

        // Validar quantidade de aulas
        if ($lessonsCount !== null && $lessonsCount <= 0) {
            $_SESSION['error'] = 'A quantidade de aulas deve ser maior que zero.';
            redirect(base_url("configuracoes/disciplinas/{$id}/editar"));
        }

        // Validar minutos por aula
        if ($lessonMinutes <= 0 || $lessonMinutes > 180) {
            $_SESSION['error'] = 'Minutos por aula deve estar entre 1 e 180.';
            redirect(base_url("configuracoes/disciplinas/{$id}/editar"));
        }

        // Calcular total de minutos (backend sempre recalcula)
        $defaultMinutes = null;
        if ($lessonsCount !== null && $lessonsCount > 0) {
            $defaultMinutes = $lessonsCount * $lessonMinutes;
        }

        $dataBefore = $discipline;
        $data = [
            'name' => $name,
            'default_minutes' => $defaultMinutes,
            'default_lessons_count' => $lessonsCount,
            'default_lesson_minutes' => $lessonMinutes,
            'sort_order' => $sortOrder,
            'active' => $active
        ];

        $disciplineModel->update($id, $data);
        $this->auditService->logUpdate('theory_disciplines', $id, $dataBefore, array_merge($discipline, $data));

        $_SESSION['success'] = 'Disciplina atualizada com sucesso!';
        redirect(base_url('configuracoes/disciplinas'));
    }

    /**
     * Lista cursos
     */
    public function cursos()
    {
        $courseModel = new TheoryCourse();
        $courses = $courseModel->findActiveByCfc($this->cfcId);

        $data = [
            'pageTitle' => 'Cursos Teóricos',
            'courses' => $courses
        ];

        $this->view('configuracoes/cursos/index', $data);
    }

    /**
     * Formulário novo curso
     */
    public function cursoNovo()
    {
        $disciplineModel = new TheoryDiscipline();
        $disciplines = $disciplineModel->findActiveByCfc($this->cfcId);

        $data = [
            'pageTitle' => 'Novo Curso Teórico',
            'course' => null,
            'disciplines' => $disciplines,
            'courseDisciplines' => []
        ];
        $this->view('configuracoes/cursos/form', $data);
    }

    /**
     * Criar curso
     */
    public function cursoCriar()
    {
        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url('configuracoes/cursos/novo'));
        }

        $name = trim($_POST['name'] ?? '');
        $active = isset($_POST['active']) ? 1 : 0;
        $disciplines = $_POST['disciplines'] ?? [];

        if (empty($name)) {
            $_SESSION['error'] = 'Nome do curso é obrigatório.';
            redirect(base_url('configuracoes/cursos/novo'));
        }

        $courseModel = new TheoryCourse();
        $courseDisciplineModel = new TheoryCourseDiscipline();

        $courseData = [
            'cfc_id' => $this->cfcId,
            'name' => $name,
            'active' => $active
        ];

        $courseId = $courseModel->create($courseData);
        $this->auditService->logCreate('theory_courses', $courseId, $courseData);

        // Vincular disciplinas
        if (!empty($disciplines)) {
            foreach ($disciplines as $index => $disciplineData) {
                if (empty($disciplineData['discipline_id'])) continue;

                // Processar campos de aulas
                $lessonsCount = !empty($disciplineData['lessons_count']) ? (int)$disciplineData['lessons_count'] : null;
                $lessonMinutes = !empty($disciplineData['lesson_minutes']) ? (int)$disciplineData['lesson_minutes'] : 50;

                // Validar
                if ($lessonsCount !== null && $lessonsCount <= 0) {
                    $_SESSION['error'] = 'A quantidade de aulas deve ser maior que zero.';
                    redirect(base_url('configuracoes/cursos/novo'));
                }

                if ($lessonMinutes <= 0 || $lessonMinutes > 180) {
                    $_SESSION['error'] = 'Minutos por aula deve estar entre 1 e 180.';
                    redirect(base_url('configuracoes/cursos/novo'));
                }

                // Calcular minutos totais (backend sempre recalcula)
                $minutes = null;
                if ($lessonsCount !== null && $lessonsCount > 0) {
                    $minutes = $lessonsCount * $lessonMinutes;
                } elseif (!empty($disciplineData['minutes'])) {
                    // Fallback: se minutes veio direto (compatibilidade com registros antigos)
                    $minutes = (int)$disciplineData['minutes'];
                }

                $courseDisciplineModel->create([
                    'course_id' => $courseId,
                    'discipline_id' => (int)$disciplineData['discipline_id'],
                    'minutes' => $minutes,
                    'lessons_count' => $lessonsCount,
                    'lesson_minutes' => $lessonMinutes,
                    'sort_order' => (int)$index,
                    'required' => isset($disciplineData['required']) ? 1 : 0
                ]);
            }
        }

        $_SESSION['success'] = 'Curso criado com sucesso!';
        redirect(base_url('configuracoes/cursos'));
    }

    /**
     * Formulário editar curso
     */
    public function cursoEditar($id)
    {
        $courseModel = new TheoryCourse();
        $course = $courseModel->findWithDisciplines($id);

        if (!$course || $course['cfc_id'] != $this->cfcId) {
            $_SESSION['error'] = 'Curso não encontrado.';
            redirect(base_url('configuracoes/cursos'));
        }

        $disciplineModel = new TheoryDiscipline();
        $disciplines = $disciplineModel->findActiveByCfc($this->cfcId);

        // Processar courseDisciplines para inferir lessons_count/lesson_minutes se não existirem
        $courseDisciplines = $course['disciplines'] ?? [];
        foreach ($courseDisciplines as &$cd) {
            if (empty($cd['lessons_count']) && !empty($cd['minutes'])) {
                $cd['lesson_minutes'] = $cd['lesson_minutes'] ?? 50;
                $cd['lessons_count'] = ceil($cd['minutes'] / $cd['lesson_minutes']);
            }
        }

        $data = [
            'pageTitle' => 'Editar Curso Teórico',
            'course' => $course,
            'disciplines' => $disciplines,
            'courseDisciplines' => $courseDisciplines
        ];
        $this->view('configuracoes/cursos/form', $data);
    }

    /**
     * Atualizar curso
     */
    public function cursoAtualizar($id)
    {
        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url("configuracoes/cursos/{$id}/editar"));
        }

        $courseModel = new TheoryCourse();
        $course = $courseModel->find($id);

        if (!$course || $course['cfc_id'] != $this->cfcId) {
            $_SESSION['error'] = 'Curso não encontrado.';
            redirect(base_url('configuracoes/cursos'));
        }

        $name = trim($_POST['name'] ?? '');
        $active = isset($_POST['active']) ? 1 : 0;
        $disciplines = $_POST['disciplines'] ?? [];

        if (empty($name)) {
            $_SESSION['error'] = 'Nome do curso é obrigatório.';
            redirect(base_url("configuracoes/cursos/{$id}/editar"));
        }

        $courseDisciplineModel = new TheoryCourseDiscipline();
        $dataBefore = $course;

        $courseData = [
            'name' => $name,
            'active' => $active
        ];

        $courseModel->update($id, $courseData);
        $this->auditService->logUpdate('theory_courses', $id, $dataBefore, array_merge($course, $courseData));

        // Remover disciplinas antigas e adicionar novas
        $courseDisciplineModel->deleteByCourse($id);

        if (!empty($disciplines)) {
            foreach ($disciplines as $index => $disciplineData) {
                if (empty($disciplineData['discipline_id'])) continue;

                // Processar campos de aulas
                $lessonsCount = !empty($disciplineData['lessons_count']) ? (int)$disciplineData['lessons_count'] : null;
                $lessonMinutes = !empty($disciplineData['lesson_minutes']) ? (int)$disciplineData['lesson_minutes'] : 50;

                // Validar
                if ($lessonsCount !== null && $lessonsCount <= 0) {
                    $_SESSION['error'] = 'A quantidade de aulas deve ser maior que zero.';
                    redirect(base_url("configuracoes/cursos/{$id}/editar"));
                }

                if ($lessonMinutes <= 0 || $lessonMinutes > 180) {
                    $_SESSION['error'] = 'Minutos por aula deve estar entre 1 e 180.';
                    redirect(base_url("configuracoes/cursos/{$id}/editar"));
                }

                // Calcular minutos totais (backend sempre recalcula)
                $minutes = null;
                if ($lessonsCount !== null && $lessonsCount > 0) {
                    $minutes = $lessonsCount * $lessonMinutes;
                } elseif (!empty($disciplineData['minutes'])) {
                    // Fallback: se minutes veio direto (compatibilidade com registros antigos)
                    $minutes = (int)$disciplineData['minutes'];
                }

                $courseDisciplineModel->create([
                    'course_id' => $id,
                    'discipline_id' => (int)$disciplineData['discipline_id'],
                    'minutes' => $minutes,
                    'lessons_count' => $lessonsCount,
                    'lesson_minutes' => $lessonMinutes,
                    'sort_order' => (int)$index,
                    'required' => isset($disciplineData['required']) ? 1 : 0
                ]);
            }
        }

        $_SESSION['success'] = 'Curso atualizado com sucesso!';
        redirect(base_url('configuracoes/cursos'));
    }

    // ============================================
    // MÓDULO: CONFIGURAÇÕES DO CFC (LOGO PWA)
    // ============================================

    /**
     * Tela de configurações do CFC (logo para PWA)
     */
    public function cfc()
    {
        $cfcModel = new Cfc();
        $cfc = $cfcModel->getCurrent();

        $data = [
            'pageTitle' => 'Configurações do CFC',
            'cfc' => $cfc,
            'hasLogo' => !empty($cfc['logo_path']),
            'iconsExist' => $cfc ? PwaIconGenerator::iconsExist($cfc['id']) : false
        ];

        $this->view('configuracoes/cfc', $data);
    }

    /**
     * Upload de logo do CFC
     */
    public function uploadLogo()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('configuracoes/cfc'));
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url('configuracoes/cfc'));
        }

        $cfcModel = new Cfc();
        $cfc = $cfcModel->getCurrent();

        if (!$cfc) {
            $_SESSION['error'] = 'CFC não encontrado.';
            redirect(base_url('configuracoes/cfc'));
        }

        if (!isset($_FILES['logo'])) {
            $_SESSION['error'] = 'Nenhum arquivo foi enviado.';
            redirect(base_url('configuracoes/cfc'));
        }

        $file = $_FILES['logo'];
        
        // Verificar erro de upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'Arquivo excede o tamanho máximo permitido pelo PHP (upload_max_filesize).',
                UPLOAD_ERR_FORM_SIZE => 'Arquivo excede o tamanho máximo do formulário (MAX_FILE_SIZE).',
                UPLOAD_ERR_PARTIAL => 'Upload parcial do arquivo. Tente novamente.',
                UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi enviado.',
                UPLOAD_ERR_NO_TMP_DIR => 'Diretório temporário não encontrado no servidor.',
                UPLOAD_ERR_CANT_WRITE => 'Erro ao escrever arquivo no disco. Verifique permissões.',
                UPLOAD_ERR_EXTENSION => 'Upload bloqueado por extensão do PHP.'
            ];
            $errorMsg = $uploadErrors[$file['error']] ?? 'Erro desconhecido no upload (código: ' . $file['error'] . ').';
            $_SESSION['error'] = $errorMsg;
            redirect(base_url('configuracoes/cfc'));
        }
        
        // Validar tipo
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            $_SESSION['error'] = 'Tipo de arquivo inválido. Use JPG, PNG ou WEBP.';
            redirect(base_url('configuracoes/cfc'));
        }

        // Validar tamanho (5MB - logo pode ser maior que foto)
        if ($file['size'] > 5 * 1024 * 1024) {
            $_SESSION['error'] = 'Arquivo muito grande. Máximo 5MB.';
            redirect(base_url('configuracoes/cfc'));
        }

        // Criar diretório se não existir
        $uploadDir = dirname(__DIR__, 2) . '/storage/uploads/cfcs/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                $_SESSION['error'] = 'Erro ao criar diretório de upload. Verifique as permissões.';
                redirect(base_url('configuracoes/cfc'));
            }
        }

        // Verificar se diretório é gravável
        if (!is_writable($uploadDir)) {
            $_SESSION['error'] = 'Diretório de upload não tem permissão de escrita. Verifique as permissões.';
            redirect(base_url('configuracoes/cfc'));
        }

        // Gerar nome único
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'cfc_' . $cfc['id'] . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;

        // Remover logo antigo se existir
        if (!empty($cfc['logo_path'])) {
            $oldPath = dirname(__DIR__, 2) . '/' . $cfc['logo_path'];
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
            // Remover ícones PWA antigos
            PwaIconGenerator::removeIcons($cfc['id']);
        }

        // Log detalhado para diagnóstico
        $logFile = dirname(__DIR__, 2) . '/storage/logs/upload_logo.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            '_FILES' => [
                'name' => $file['name'],
                'type' => $file['type'],
                'size' => $file['size'],
                'error' => $file['error'],
                'tmp_name' => $file['tmp_name'] // Para verificar se existe
            ],
            'uploadDir' => $uploadDir,
            'uploadDirExists' => is_dir($uploadDir),
            'uploadDirWritable' => is_dir($uploadDir) ? is_writable($uploadDir) : false,
            'filepath' => $filepath,
            'tmpNameExists' => file_exists($file['tmp_name']),
            'isUploadedFile' => is_uploaded_file($file['tmp_name']),
            'cfcId' => $cfc['id']
        ];
        @file_put_contents($logFile, "=== UPLOAD START ===\n" . json_encode($logData, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

        // Mover arquivo
        $moveResult = move_uploaded_file($file['tmp_name'], $filepath);
        $logData['moveResult'] = $moveResult;
        $logData['fileExistsAfterMove'] = file_exists($filepath);
        $logData['fileSizeAfterMove'] = $moveResult && file_exists($filepath) ? filesize($filepath) : 0;
        
        if (!$moveResult) {
            $errorMsg = 'Erro ao salvar arquivo.';
            // Verificar tipo de erro
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $uploadErrors = [
                    UPLOAD_ERR_INI_SIZE => 'Arquivo excede o tamanho máximo permitido pelo PHP.',
                    UPLOAD_ERR_FORM_SIZE => 'Arquivo excede o tamanho máximo do formulário.',
                    UPLOAD_ERR_PARTIAL => 'Upload parcial do arquivo.',
                    UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi enviado.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Diretório temporário não encontrado.',
                    UPLOAD_ERR_CANT_WRITE => 'Erro ao escrever arquivo no disco.',
                    UPLOAD_ERR_EXTENSION => 'Upload bloqueado por extensão.'
                ];
                $errorMsg = $uploadErrors[$file['error']] ?? 'Erro desconhecido no upload.';
            } else {
                // Erro adicional: verificar permissões e espaço em disco
                $errorMsg .= ' Verifique permissões do diretório e espaço em disco.';
                if (!is_writable($uploadDir)) {
                    $errorMsg .= ' Diretório não é gravável.';
                }
                if (disk_free_space($uploadDir) < $file['size']) {
                    $errorMsg .= ' Espaço em disco insuficiente.';
                }
            }
            // Log do erro
            $logData['error'] = $errorMsg;
            $logData['lastError'] = error_get_last();
            @file_put_contents($logFile, "=== UPLOAD ERROR ===\n" . json_encode($logData, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
            $_SESSION['error'] = $errorMsg;
            redirect(base_url('configuracoes/cfc'));
        }

        // Verificar se arquivo foi salvo corretamente
        if (!file_exists($filepath)) {
            $logData['error'] = 'Arquivo não existe após move_uploaded_file';
            $logData['lastError'] = error_get_last();
            @file_put_contents($logFile, "=== UPLOAD ERROR (file not exists) ===\n" . json_encode($logData, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
            $_SESSION['error'] = 'Arquivo não foi salvo corretamente.';
            redirect(base_url('configuracoes/cfc'));
        }

        // Atualizar banco
        $relativePath = 'storage/uploads/cfcs/' . $filename;
        $updateResult = $cfcModel->update($cfc['id'], ['logo_path' => $relativePath]);
        
        // Verificar se foi atualizado no banco
        $cfcAfterUpdate = $cfcModel->findById($cfc['id']);
        $logData['dbUpdate'] = [
            'updateResult' => $updateResult,
            'relativePath' => $relativePath,
            'logoPathInDb' => $cfcAfterUpdate['logo_path'] ?? 'NULL',
            'dbUpdateSuccess' => ($cfcAfterUpdate['logo_path'] ?? null) === $relativePath
        ];
        
        // Log do sucesso
        $logData['success'] = true;
        $logData['fileExistsOnDisk'] = file_exists($filepath);
        $logData['fileSizeOnDisk'] = filesize($filepath);
        @file_put_contents($logFile, "=== UPLOAD SUCCESS ===\n" . json_encode($logData, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

        // Gerar ícones PWA
        $icons = PwaIconGenerator::generateIcons($filepath, $cfc['id']);
        
        if ($icons) {
            $_SESSION['success'] = 'Logo atualizado e ícones PWA gerados com sucesso!';
        } else {
            $_SESSION['warning'] = 'Logo atualizado, mas houve erro ao gerar ícones PWA. Verifique se a extensão GD está habilitada.';
        }

        // Auditoria
        $this->auditService->log('upload_logo', 'cfcs', $cfc['id'], ['old_logo' => $cfc['logo_path'] ?? null], ['new_logo' => $relativePath]);

        redirect(base_url('configuracoes/cfc'));
    }

    /**
     * Remover logo do CFC
     */
    public function removerLogo()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('configuracoes/cfc'));
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url('configuracoes/cfc'));
        }

        $cfcModel = new Cfc();
        $cfc = $cfcModel->getCurrent();

        if (!$cfc) {
            $_SESSION['error'] = 'CFC não encontrado.';
            redirect(base_url('configuracoes/cfc'));
        }

        // Remover arquivo de logo
        if (!empty($cfc['logo_path'])) {
            $oldPath = dirname(__DIR__, 2) . '/' . $cfc['logo_path'];
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }

        // Remover ícones PWA
        PwaIconGenerator::removeIcons($cfc['id']);

        // Atualizar banco
        $cfcModel->update($cfc['id'], ['logo_path' => null]);

        // Auditoria
        $this->auditService->log('remove_logo', 'cfcs', $cfc['id'], ['old_logo' => $cfc['logo_path'] ?? null], []);

        $_SESSION['success'] = 'Logo removido com sucesso!';
        redirect(base_url('configuracoes/cfc'));
    }

    /**
     * Salvar informações do CFC (nome, CNPJ, etc)
     */
    public function salvarCfc()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('configuracoes/cfc'));
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url('configuracoes/cfc'));
        }

        $cfcModel = new Cfc();
        $cfc = $cfcModel->getCurrent();

        if (!$cfc) {
            $_SESSION['error'] = 'CFC não encontrado.';
            redirect(base_url('configuracoes/cfc'));
        }

        $nome = trim($_POST['nome'] ?? '');
        $cnpj = trim($_POST['cnpj'] ?? '');

        // Validações
        if (empty($nome)) {
            $_SESSION['error'] = 'Nome do CFC é obrigatório.';
            redirect(base_url('configuracoes/cfc'));
        }

        if (strlen($nome) > 255) {
            $_SESSION['error'] = 'Nome do CFC muito longo (máximo 255 caracteres).';
            redirect(base_url('configuracoes/cfc'));
        }

        // Validar CNPJ se fornecido (formato básico)
        if (!empty($cnpj) && strlen($cnpj) > 18) {
            $_SESSION['error'] = 'CNPJ inválido (máximo 18 caracteres).';
            redirect(base_url('configuracoes/cfc'));
        }

        // Preparar dados para atualização
        $data = ['nome' => $nome];
        if (isset($_POST['cnpj'])) {
            $data['cnpj'] = !empty($cnpj) ? $cnpj : null;
        }

        // Atualizar banco
        $dataBefore = $cfc;
        $cfcModel->update($cfc['id'], $data);

        // Auditoria
        $this->auditService->logUpdate('cfcs', $cfc['id'], $dataBefore, array_merge($cfc, $data));

        $_SESSION['success'] = 'Informações do CFC atualizadas com sucesso!';
        redirect(base_url('configuracoes/cfc'));
    }

    /**
     * Servir logo do CFC (protegido)
     */
    public function logo()
    {
        $cfcModel = new Cfc();
        $cfc = $cfcModel->getCurrent();

        if (!$cfc || empty($cfc['logo_path'])) {
            http_response_code(404);
            exit('Logo não encontrado');
        }

        $filepath = dirname(__DIR__, 2) . '/' . $cfc['logo_path'];

        if (!file_exists($filepath)) {
            http_response_code(404);
            exit('Logo não encontrado');
        }

        // Determinar tipo MIME
        $mimeType = mime_content_type($filepath);
        if (!$mimeType) {
            $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
            $mimeTypes = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp'
            ];
            $mimeType = $mimeTypes[$extension] ?? 'image/jpeg';
        }

        header('Content-Type: ' . $mimeType);
        header('Cache-Control: public, max-age=3600');
        readfile($filepath);
        exit;
    }
}
