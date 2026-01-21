<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Student;
use App\Models\Instructor;
use App\Models\AccountActivationToken;
use App\Services\PermissionService;
use App\Services\AuditService;
use App\Services\EmailService;
use App\Services\UserCreationService;
use App\Config\Constants;
use App\Config\Database;

class UsuariosController extends Controller
{
    private $cfcId;
    private $auditService;
    private $emailService;

    public function __construct()
    {
        $this->cfcId = $_SESSION['cfc_id'] ?? Constants::CFC_ID_DEFAULT;
        $this->auditService = new AuditService();
        $this->emailService = new EmailService();
        
        // Apenas ADMIN pode gerenciar usuários
        if (!PermissionService::check('usuarios', 'view') && $_SESSION['current_role'] !== 'ADMIN') {
            $_SESSION['error'] = 'Você não tem permissão para acessar este módulo.';
            redirect(base_url('dashboard'));
        }
    }

    /**
     * Lista todos os usuários e pendências
     */
    public function index()
    {
        $userModel = new User();
        $users = $userModel->findAllWithLinks($this->cfcId);
        
        // Processar roles para exibição
        foreach ($users as &$user) {
            $roles = explode(',', $user['roles'] ?? '');
            $user['roles_array'] = array_filter($roles);
        }
        
        // Buscar pendências: alunos e instrutores sem acesso
        $db = Database::getInstance()->getConnection();
        
        // Alunos sem usuário (com e-mail para poder criar acesso)
        $stmt = $db->prepare("
            SELECT id, name, full_name, cpf, email, status
            FROM students 
            WHERE cfc_id = ? 
            AND (user_id IS NULL OR user_id = 0)
            AND email IS NOT NULL 
            AND email != ''
            ORDER BY COALESCE(full_name, name) ASC
        ");
        $stmt->execute([$this->cfcId]);
        $studentsWithoutAccess = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Instrutores sem usuário (com e-mail)
        $stmt = $db->prepare("
            SELECT id, name, cpf, email, is_active
            FROM instructors 
            WHERE cfc_id = ? 
            AND (user_id IS NULL OR user_id = 0)
            AND email IS NOT NULL 
            AND email != ''
            ORDER BY name ASC
        ");
        $stmt->execute([$this->cfcId]);
        $instructorsWithoutAccess = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $data = [
            'pageTitle' => 'Gerenciamento de Usuários',
            'users' => $users,
            'studentsWithoutAccess' => $studentsWithoutAccess,
            'instructorsWithoutAccess' => $instructorsWithoutAccess
        ];
        
        $this->view('usuarios/index', $data);
    }

    /**
     * Formulário para criar/vincular acesso
     */
    public function novo()
    {
        if (!PermissionService::check('usuarios', 'create') && $_SESSION['current_role'] !== 'ADMIN') {
            $_SESSION['error'] = 'Você não tem permissão para criar usuários.';
            redirect(base_url('usuarios'));
        }

        $studentModel = new Student();
        $instructorModel = new Instructor();
        
        // Buscar alunos e instrutores sem usuário vinculado
        // Inclui alunos sem user_id OU com user_id que não existe na tabela usuarios
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            SELECT s.id, s.name, s.full_name, s.cpf, s.email, s.user_id
            FROM students s
            LEFT JOIN usuarios u ON u.id = s.user_id
            WHERE s.cfc_id = ? 
            AND (s.user_id IS NULL OR s.user_id = 0 OR u.id IS NULL)
            AND s.email IS NOT NULL 
            AND s.email != ''
            ORDER BY COALESCE(s.full_name, s.name) ASC
        ");
        $stmt->execute([$this->cfcId]);
        $students = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Log para diagnóstico
        error_log("[USUARIOS_NOVO] Alunos encontrados sem acesso: " . count($students));
        
        $stmt = $db->prepare("
            SELECT id, name, cpf, email 
            FROM instructors 
            WHERE cfc_id = ? AND (user_id IS NULL OR user_id = 0)
            ORDER BY name ASC
        ");
        $stmt->execute([$this->cfcId]);
        $instructors = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $data = [
            'pageTitle' => 'Criar Acesso',
            'students' => $students,
            'instructors' => $instructors
        ];
        
        $this->view('usuarios/form', $data);
    }

    /**
     * Cria novo acesso/vínculo
     */
    public function criar()
    {
        if (!PermissionService::check('usuarios', 'create') && $_SESSION['current_role'] !== 'ADMIN') {
            $_SESSION['error'] = 'Você não tem permissão para criar usuários.';
            redirect(base_url('usuarios'));
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url('usuarios/novo'));
        }

        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? '';
        $linkType = $_POST['link_type'] ?? 'none'; // 'student', 'instructor', 'none'
        $linkId = !empty($_POST['link_id']) ? (int)$_POST['link_id'] : null;
        $sendEmail = isset($_POST['send_email']);

        // Validações
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'E-mail inválido.';
            redirect(base_url('usuarios/novo'));
        }

        if (empty($role) || !in_array($role, ['ADMIN', 'SECRETARIA', 'INSTRUTOR', 'ALUNO'])) {
            $_SESSION['error'] = 'Perfil inválido.';
            redirect(base_url('usuarios/novo'));
        }

        // Validar vínculo
        $userModel = new User();
        if ($linkType === 'student' && $linkId) {
            if ($userModel->hasStudentUser($linkId)) {
                $_SESSION['error'] = 'Este aluno já possui um acesso vinculado.';
                redirect(base_url('usuarios/novo'));
            }
        } elseif ($linkType === 'instructor' && $linkId) {
            if ($userModel->hasInstructorUser($linkId)) {
                $_SESSION['error'] = 'Este instrutor já possui um acesso vinculado.';
                redirect(base_url('usuarios/novo'));
            }
        }

        // Verificar se email já existe
        $existing = $userModel->findByEmail($email);
        if ($existing) {
            $_SESSION['error'] = 'Este e-mail já está em uso.';
            redirect(base_url('usuarios/novo'));
        }

        // Gerar senha temporária segura
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*';
        $tempPassword = substr(str_shuffle(str_repeat($chars, ceil(12 / strlen($chars)))), 0, 12);
        $hashedPassword = password_hash($tempPassword, PASSWORD_BCRYPT);

        // Buscar nome do vínculo
        $nome = '';
        if ($linkType === 'student' && $linkId) {
            $studentModel = new Student();
            $student = $studentModel->find($linkId);
            $nome = $student['full_name'] ?? $student['name'] ?? 'Aluno';
        } elseif ($linkType === 'instructor' && $linkId) {
            $instructorModel = new Instructor();
            $instructor = $instructorModel->find($linkId);
            $nome = $instructor['name'] ?? 'Instrutor';
        } else {
            $nome = trim($_POST['nome'] ?? '');
            if (empty($nome)) {
                $_SESSION['error'] = 'Nome é obrigatório para usuários administrativos.';
                redirect(base_url('usuarios/novo'));
            }
        }

        $db = Database::getInstance()->getConnection();
        
        try {
            $db->beginTransaction();

            // Criar usuário (com must_change_password = 1 para senhas temporárias)
            $mustChangePassword = ($linkType !== 'none') ? 1 : 0; // Senhas vinculadas devem ser trocadas
            $stmt = $db->prepare("
                INSERT INTO usuarios (cfc_id, nome, email, password, status, must_change_password) 
                VALUES (?, ?, ?, ?, 'ativo', ?)
            ");
            $stmt->execute([$this->cfcId, $nome, $email, $hashedPassword, $mustChangePassword]);
            $userId = $db->lastInsertId();

            // Vincular com aluno ou instrutor
            if ($linkType === 'student' && $linkId) {
                $stmt = $db->prepare("UPDATE students SET user_id = ? WHERE id = ?");
                $stmt->execute([$userId, $linkId]);
            } elseif ($linkType === 'instructor' && $linkId) {
                $stmt = $db->prepare("UPDATE instructors SET user_id = ? WHERE id = ?");
                $stmt->execute([$userId, $linkId]);
            }

            // Associar role
            $stmt = $db->prepare("INSERT INTO usuario_roles (usuario_id, role) VALUES (?, ?)");
            $stmt->execute([$userId, $role]);

            $db->commit();

            // Auditoria
            $this->auditService->logCreate('usuarios', $userId, [
                'email' => $email,
                'role' => $role,
                'link_type' => $linkType,
                'link_id' => $linkId
            ]);

            // Enviar e-mail se solicitado
            if ($sendEmail) {
                try {
                    $loginUrl = base_url('/login');
                    $this->emailService->sendAccessCreated($email, $tempPassword, $loginUrl);
                } catch (\Exception $e) {
                    // Log erro mas não bloqueia criação
                    error_log("Erro ao enviar e-mail: " . $e->getMessage());
                }
            }

            $_SESSION['success'] = 'Acesso criado com sucesso!' . ($sendEmail ? ' E-mail enviado.' : '');
            redirect(base_url('usuarios'));
            
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'Erro ao criar acesso: ' . $e->getMessage();
            redirect(base_url('usuarios/novo'));
        }
    }

    /**
     * Formulário para editar usuário
     */
    public function editar($id)
    {
        if (!PermissionService::check('usuarios', 'update') && $_SESSION['current_role'] !== 'ADMIN') {
            $_SESSION['error'] = 'Você não tem permissão para editar usuários.';
            redirect(base_url('usuarios'));
        }

        $userModel = new User();
        $user = $userModel->findWithLinks($id);

        if (!$user || $user['cfc_id'] != $this->cfcId) {
            $_SESSION['error'] = 'Usuário não encontrado.';
            redirect(base_url('usuarios'));
        }

        // Buscar roles do usuário
        $roles = User::getUserRoles($id);
        $user['roles'] = $roles;

        // Verificar status de acesso
        $db = Database::getInstance()->getConnection();
        
        // Verificar se tem senha definida (senha não pode ser vazia)
        $hasPassword = !empty($user['password']);
        
        // Verificar se tem token de ativação ativo
        $tokenModel = new AccountActivationToken();
        $activeToken = $tokenModel->findActiveToken($id);
        $hasActiveToken = !empty($activeToken);
        
        // Buscar último login (se houver campo na tabela)
        $lastLogin = null;
        // TODO: Adicionar campo last_login em usuarios se necessário

        $data = [
            'pageTitle' => 'Editar Usuário',
            'user' => $user,
            'hasPassword' => $hasPassword,
            'hasActiveToken' => $hasActiveToken,
            'activeToken' => $activeToken,
            'tempPasswordGenerated' => $_SESSION['temp_password_generated'] ?? null,
            'activationLinkGenerated' => $_SESSION['activation_link_generated'] ?? null
        ];

        // Limpar sessões após exibir
        unset($_SESSION['temp_password_generated']);
        unset($_SESSION['activation_link_generated']);

        $this->view('usuarios/form', $data);
    }

    /**
     * Atualiza usuário
     */
    public function atualizar($id)
    {
        if (!PermissionService::check('usuarios', 'update') && $_SESSION['current_role'] !== 'ADMIN') {
            $_SESSION['error'] = 'Você não tem permissão para editar usuários.';
            redirect(base_url('usuarios'));
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url("usuarios/{$id}/editar"));
        }

        $userModel = new User();
        $user = $userModel->find($id);

        if (!$user || $user['cfc_id'] != $this->cfcId) {
            $_SESSION['error'] = 'Usuário não encontrado.';
            redirect(base_url('usuarios'));
        }

        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? '';
        $status = $_POST['status'] ?? 'ativo';

        // Validações
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'E-mail inválido.';
            redirect(base_url("usuarios/{$id}/editar"));
        }

        if (empty($role) || !in_array($role, ['ADMIN', 'SECRETARIA', 'INSTRUTOR', 'ALUNO'])) {
            $_SESSION['error'] = 'Perfil inválido.';
            redirect(base_url("usuarios/{$id}/editar"));
        }

        // Verificar se email já existe em outro usuário
        $existing = $userModel->findByEmail($email);
        if ($existing && $existing['id'] != $id) {
            $_SESSION['error'] = 'Este e-mail já está em uso por outro usuário.';
            redirect(base_url("usuarios/{$id}/editar"));
        }

        // Não permitir alterar vínculo (aluno/instrutor)
        // Apenas email, role e status podem ser alterados

        $db = Database::getInstance()->getConnection();
        
        try {
            $db->beginTransaction();

            $dataBefore = $user;

            // Atualizar usuário
            $stmt = $db->prepare("UPDATE usuarios SET email = ?, status = ? WHERE id = ?");
            $stmt->execute([$email, $status, $id]);

            // Atualizar role (remover antigas e adicionar nova)
            $stmt = $db->prepare("DELETE FROM usuario_roles WHERE usuario_id = ?");
            $stmt->execute([$id]);
            
            $stmt = $db->prepare("INSERT INTO usuario_roles (usuario_id, role) VALUES (?, ?)");
            $stmt->execute([$id, $role]);

            $db->commit();

            // Auditoria
            $dataAfter = array_merge($user, ['email' => $email, 'status' => $status, 'role' => $role]);
            $this->auditService->logUpdate('usuarios', $id, $dataBefore, $dataAfter);

            $_SESSION['success'] = 'Usuário atualizado com sucesso!';
            redirect(base_url('usuarios'));
            
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'Erro ao atualizar usuário: ' . $e->getMessage();
            redirect(base_url("usuarios/{$id}/editar"));
        }
    }

    /**
     * Cria acesso rápido para aluno (da lista de pendências)
     */
    public function criarAcessoAluno()
    {
        if (!PermissionService::check('usuarios', 'create') && $_SESSION['current_role'] !== 'ADMIN') {
            $_SESSION['error'] = 'Você não tem permissão para criar acessos.';
            redirect(base_url('usuarios'));
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url('usuarios'));
        }

        $studentId = (int)($_POST['student_id'] ?? 0);

        if (!$studentId) {
            $_SESSION['error'] = 'Aluno não especificado.';
            redirect(base_url('usuarios'));
        }

        $studentModel = new Student();
        $student = $studentModel->find($studentId);

        if (!$student || $student['cfc_id'] != $this->cfcId) {
            $_SESSION['error'] = 'Aluno não encontrado.';
            redirect(base_url('usuarios'));
        }

        // Verificar se aluno já tem usuário válido (que existe na tabela usuarios)
        if (!empty($student['user_id'])) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE id = ?");
            $stmt->execute([$student['user_id']]);
            $existingUser = $stmt->fetch();
            
            if ($existingUser) {
                $_SESSION['error'] = 'Este aluno já possui acesso vinculado.';
                redirect(base_url('usuarios'));
            } else {
                // user_id existe mas usuário não existe - limpar referência inválida
                error_log("[USUARIOS] Aluno ID {$studentId} tem user_id inválido ({$student['user_id']}). Limpando referência.");
                $studentModel->update($studentId, ['user_id' => null]);
                $student['user_id'] = null; // Atualizar para continuar
            }
        }

        $email = trim($student['email'] ?? '');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Aluno não possui e-mail válido. Atualize o cadastro do aluno primeiro.';
            redirect(base_url('usuarios'));
        }

        try {
            $userService = new UserCreationService();
            $userData = $userService->createForStudent($studentId, $email, $student['full_name'] ?? $student['name']);

            // Tentar enviar e-mail
            try {
                $emailService = new EmailService();
                $loginUrl = base_url('/login');
                $emailService->sendAccessCreated($email, $userData['temp_password'], $loginUrl);
            } catch (\Exception $e) {
                error_log("Erro ao enviar e-mail: " . $e->getMessage());
            }

            $this->auditService->logCreate('usuarios', $userData['user_id'], [
                'type' => 'student_access',
                'student_id' => $studentId
            ]);

            $_SESSION['success'] = 'Acesso criado com sucesso para o aluno!';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Erro ao criar acesso: ' . $e->getMessage();
        }

        redirect(base_url('usuarios'));
    }

    /**
     * Cria acesso rápido para instrutor (da lista de pendências)
     */
    public function criarAcessoInstrutor()
    {
        if (!PermissionService::check('usuarios', 'create') && $_SESSION['current_role'] !== 'ADMIN') {
            $_SESSION['error'] = 'Você não tem permissão para criar acessos.';
            redirect(base_url('usuarios'));
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url('usuarios'));
        }

        $instructorId = (int)($_POST['instructor_id'] ?? 0);

        if (!$instructorId) {
            $_SESSION['error'] = 'Instrutor não especificado.';
            redirect(base_url('usuarios'));
        }

        $instructorModel = new Instructor();
        $instructor = $instructorModel->find($instructorId);

        if (!$instructor || $instructor['cfc_id'] != $this->cfcId) {
            $_SESSION['error'] = 'Instrutor não encontrado.';
            redirect(base_url('usuarios'));
        }

        if (!empty($instructor['user_id'])) {
            $_SESSION['error'] = 'Este instrutor já possui acesso vinculado.';
            redirect(base_url('usuarios'));
        }

        $email = trim($instructor['email'] ?? '');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Instrutor não possui e-mail válido. Atualize o cadastro do instrutor primeiro.';
            redirect(base_url('usuarios'));
        }

        try {
            $userService = new UserCreationService();
            $userData = $userService->createForInstructor($instructorId, $email, $instructor['name']);

            // Tentar enviar e-mail
            try {
                $emailService = new EmailService();
                $loginUrl = base_url('/login');
                $emailService->sendAccessCreated($email, $userData['temp_password'], $loginUrl);
            } catch (\Exception $e) {
                error_log("Erro ao enviar e-mail: " . $e->getMessage());
            }

            $this->auditService->logCreate('usuarios', $userData['user_id'], [
                'type' => 'instructor_access',
                'instructor_id' => $instructorId
            ]);

            $_SESSION['success'] = 'Acesso criado com sucesso para o instrutor!';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Erro ao criar acesso: ' . $e->getMessage();
        }

        redirect(base_url('usuarios'));
    }

    /**
     * Gera senha temporária para usuário
     */
    public function gerarSenhaTemporaria($id)
    {
        if (!PermissionService::check('usuarios', 'update') && $_SESSION['current_role'] !== 'ADMIN') {
            $_SESSION['error'] = 'Você não tem permissão para esta ação.';
            redirect(base_url('usuarios'));
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url("usuarios/{$id}/editar"));
        }

        $userModel = new User();
        $user = $userModel->find($id);

        if (!$user || $user['cfc_id'] != $this->cfcId) {
            $_SESSION['error'] = 'Usuário não encontrado.';
            redirect(base_url('usuarios'));
        }

        // Gerar senha temporária segura
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*';
        $tempPassword = substr(str_shuffle(str_repeat($chars, ceil(12 / strlen($chars)))), 0, 12);
        $hashedPassword = password_hash($tempPassword, PASSWORD_BCRYPT);

        // Atualizar senha e marcar como obrigatória troca
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            UPDATE usuarios 
            SET password = ?, must_change_password = 1 
            WHERE id = ?
        ");
        $stmt->execute([$hashedPassword, $id]);

        // Auditoria
        $this->auditService->logUpdate('usuarios', $id, null, [
            'action' => 'generate_temp_password',
            'generated_by' => $_SESSION['user_id']
        ]);

        // Retornar senha temporária (exibir apenas uma vez)
        $_SESSION['temp_password_generated'] = [
            'user_id' => $id,
            'user_email' => $user['email'],
            'temp_password' => $tempPassword
        ];

        $_SESSION['success'] = 'Senha temporária gerada com sucesso!';
        redirect(base_url("usuarios/{$id}/editar"));
    }

    /**
     * Gera link de ativação para usuário
     */
    public function gerarLinkAtivacao($id)
    {
        if (!PermissionService::check('usuarios', 'update') && $_SESSION['current_role'] !== 'ADMIN') {
            $_SESSION['error'] = 'Você não tem permissão para esta ação.';
            redirect(base_url('usuarios'));
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url("usuarios/{$id}/editar"));
        }

        $userModel = new User();
        $user = $userModel->find($id);

        if (!$user || $user['cfc_id'] != $this->cfcId) {
            $_SESSION['error'] = 'Usuário não encontrado.';
            redirect(base_url('usuarios'));
        }

        // Gerar token único
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        
        // Expiração: 24 horas
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

        // Salvar token hash no banco
        $tokenModel = new AccountActivationToken();
        $tokenModel->create($id, $tokenHash, $expiresAt, $_SESSION['user_id']);

        // Auditoria
        $this->auditService->logUpdate('usuarios', $id, null, [
            'action' => 'generate_activation_link',
            'generated_by' => $_SESSION['user_id']
        ]);

        // URL completa de ativação
        $activationUrl = base_url("ativar-conta?token={$token}");

        // Retornar link (exibir apenas uma vez)
        // IMPORTANTE: Salvar token puro na sessão para poder usar ao enviar por e-mail
        $_SESSION['activation_link_generated'] = [
            'user_id' => $id,
            'user_email' => $user['email'],
            'activation_url' => $activationUrl,
            'token' => $token, // Token puro para envio por e-mail
            'expires_at' => $expiresAt
        ];

        $_SESSION['success'] = 'Link de ativação gerado com sucesso!';
        redirect(base_url("usuarios/{$id}/editar"));
    }

    /**
     * Envia link de ativação por e-mail
     */
    public function enviarLinkEmail($id)
    {
        if (!PermissionService::check('usuarios', 'update') && $_SESSION['current_role'] !== 'ADMIN') {
            $_SESSION['error'] = 'Você não tem permissão para esta ação.';
            redirect(base_url('usuarios'));
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url("usuarios/{$id}/editar"));
        }

        $userModel = new User();
        $user = $userModel->find($id);

        if (!$user || $user['cfc_id'] != $this->cfcId) {
            $_SESSION['error'] = 'Usuário não encontrado.';
            redirect(base_url('usuarios'));
        }

        // Verificar se há token ativo
        $tokenModel = new AccountActivationToken();
        $activeToken = $tokenModel->findActiveToken($id);

        if (!$activeToken) {
            $_SESSION['error'] = 'Nenhum link de ativação ativo. Gere um link primeiro.';
            redirect(base_url("usuarios/{$id}/editar"));
        }

        // Tentar enviar e-mail (não bloqueia se falhar)
        // Verificar se há token puro na sessão (gerado recentemente)
        $tokenFromSession = null;
        if (!empty($_SESSION['activation_link_generated']) && 
            $_SESSION['activation_link_generated']['user_id'] == $id &&
            !empty($_SESSION['activation_link_generated']['token'])) {
            $tokenFromSession = $_SESSION['activation_link_generated']['token'];
        }

        // Se não houver token na sessão, gerar novo
        if (!$tokenFromSession) {
            $tokenFromSession = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $tokenFromSession);
            
            // Atualizar token no banco
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE account_activation_tokens SET token_hash = ? WHERE id = ?");
            $stmt->execute([$tokenHash, $activeToken['id']]);
        }

        $activationUrl = base_url("ativar-conta?token={$tokenFromSession}");

        try {
            // Verificar se SMTP está configurado
            $smtpSettings = $this->emailService->getSmtpSettings();
            
            if (!$smtpSettings) {
                throw new \Exception('SMTP não configurado');
            }

            // Enviar e-mail
            $this->emailService->sendActivationLink($user['email'], $user['nome'], $activationUrl);

            // Auditoria
            $this->auditService->logUpdate('usuarios', $id, null, [
                'action' => 'send_activation_email',
                'sent_by' => $_SESSION['user_id'],
                'status' => 'success'
            ]);

            $_SESSION['success'] = 'Link de ativação enviado por e-mail com sucesso!';
        } catch (\Exception $e) {
            // Log erro mas não bloqueia
            error_log("Erro ao enviar e-mail de ativação: " . $e->getMessage());
            
            // Auditoria
            $this->auditService->logUpdate('usuarios', $id, null, [
                'action' => 'send_activation_email',
                'sent_by' => $_SESSION['user_id'],
                'status' => 'failed',
                'error' => $e->getMessage()
            ]);

            // Mostrar link copiável se SMTP não configurado
            $_SESSION['activation_link_generated'] = [
                'user_id' => $id,
                'user_email' => $user['email'],
                'activation_url' => $activationUrl,
                'expires_at' => $activeToken['expires_at']
            ];

            if (strpos($e->getMessage(), 'SMTP não configurado') !== false) {
                $_SESSION['warning'] = 'SMTP não configurado. Use o link copiável abaixo.';
            } else {
                $_SESSION['warning'] = 'Não foi possível enviar o e-mail automaticamente. Use o link copiável abaixo.';
            }
        }

        redirect(base_url("usuarios/{$id}/editar"));
    }
}
