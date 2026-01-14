<?php

namespace App\Controllers;

use App\Models\Notification;
use App\Models\Student;
use App\Models\Instructor;
use App\Config\Constants;
use App\Config\Database;

class BroadcastNotificationsController extends Controller
{
    private $notificationModel;
    private $cfcId;

    public function __construct()
    {
        $this->notificationModel = new Notification();
        $this->cfcId = $_SESSION['cfc_id'] ?? Constants::CFC_ID_DEFAULT;
        
        // Apenas ADMIN e SECRETARIA podem acessar
        $currentRole = $_SESSION['current_role'] ?? '';
        if ($currentRole !== Constants::ROLE_ADMIN && $currentRole !== Constants::ROLE_SECRETARIA) {
            $_SESSION['error'] = 'Você não tem permissão para acessar este módulo.';
            redirect(base_url('dashboard'));
        }
    }

    /**
     * Exibe formulário para criar e enviar notificação
     */
    public function create()
    {
        $db = Database::getInstance()->getConnection();
        
        // Buscar alunos com user_id para seleção específica
        $stmt = $db->prepare("
            SELECT s.id, s.name, s.full_name, s.user_id, u.email
            FROM students s
            INNER JOIN usuarios u ON u.id = s.user_id
            WHERE s.cfc_id = ? AND s.user_id IS NOT NULL
            ORDER BY COALESCE(s.full_name, s.name) ASC
        ");
        $stmt->execute([$this->cfcId]);
        $students = $stmt->fetchAll();

        // Buscar instrutores com user_id para seleção específica
        $stmt = $db->prepare("
            SELECT i.id, i.name, i.user_id, u.email
            FROM instructors i
            INNER JOIN usuarios u ON u.id = i.user_id
            WHERE i.cfc_id = ? AND i.is_active = 1 AND i.user_id IS NOT NULL
            ORDER BY i.name ASC
        ");
        $stmt->execute([$this->cfcId]);
        $instructors = $stmt->fetchAll();

        $data = [
            'pageTitle' => 'Enviar Comunicado',
            'students' => $students,
            'instructors' => $instructors
        ];

        $this->view('broadcast_notifications/form', $data);
    }

    /**
     * Processa e envia notificação para os destinatários
     */
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('comunicados/novo'));
        }

        // Validar CSRF
        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url('comunicados/novo'));
        }

        // Validar campos obrigatórios
        $title = trim($_POST['title'] ?? '');
        $body = trim($_POST['body'] ?? '');
        $target = $_POST['target'] ?? '';
        $targetId = !empty($_POST['target_id']) ? (int)$_POST['target_id'] : null;
        $link = !empty($_POST['link']) ? trim($_POST['link']) : null;

        $errors = [];

        if (empty($title)) {
            $errors[] = 'Título é obrigatório.';
        }

        if (empty($body)) {
            $errors[] = 'Mensagem é obrigatória.';
        }

        if (empty($target)) {
            $errors[] = 'Público-alvo é obrigatório.';
        }

        // Validar target_id quando necessário
        if (in_array($target, ['ONE_STUDENT', 'ONE_INSTRUCTOR']) && empty($targetId)) {
            $errors[] = 'Selecione um destinatário específico.';
        }

        if (!empty($errors)) {
            $_SESSION['error'] = implode(' ', $errors);
            redirect(base_url('comunicados/novo'));
        }

        // Buscar destinatários
        $recipients = $this->getRecipients($target, $targetId);

        if (empty($recipients)) {
            $_SESSION['error'] = 'Nenhum destinatário encontrado para o público selecionado.';
            redirect(base_url('comunicados/novo'));
        }

        // Sanitizar conteúdo (escapar HTML na renderização, não aqui)
        // Aqui apenas validamos que não está vazio

        // Criar notificações
        $sentCount = 0;
        $type = 'broadcast';

        foreach ($recipients as $userId) {
            try {
                $this->notificationModel->createNotification(
                    $userId,
                    $type,
                    $title,
                    $body,
                    $link
                );
                $sentCount++;
            } catch (\Exception $e) {
                // Log error mas continua enviando para outros
                error_log("Erro ao criar notificação para user_id {$userId}: " . $e->getMessage());
            }
        }

        $_SESSION['success'] = "Comunicado enviado com sucesso para {$sentCount} destinatário(s).";
        redirect(base_url('comunicados/novo'));
    }

    /**
     * Busca lista de user_ids dos destinatários baseado no público-alvo
     */
    private function getRecipients($target, $targetId = null)
    {
        $db = Database::getInstance()->getConnection();
        $recipients = [];

        switch ($target) {
            case 'ALL_STUDENTS':
                // Todos os alunos com user_id
                $stmt = $db->prepare("
                    SELECT DISTINCT s.user_id
                    FROM students s
                    INNER JOIN usuarios u ON u.id = s.user_id
                    WHERE s.cfc_id = ? 
                      AND s.user_id IS NOT NULL
                      AND u.status = 'ativo'
                ");
                $stmt->execute([$this->cfcId]);
                $results = $stmt->fetchAll();
                foreach ($results as $row) {
                    if (!empty($row['user_id'])) {
                        $recipients[] = (int)$row['user_id'];
                    }
                }
                break;

            case 'ALL_INSTRUCTORS':
                // Todos os instrutores com user_id
                $stmt = $db->prepare("
                    SELECT DISTINCT i.user_id
                    FROM instructors i
                    INNER JOIN usuarios u ON u.id = i.user_id
                    WHERE i.cfc_id = ? 
                      AND i.is_active = 1
                      AND i.user_id IS NOT NULL
                      AND u.status = 'ativo'
                ");
                $stmt->execute([$this->cfcId]);
                $results = $stmt->fetchAll();
                foreach ($results as $row) {
                    if (!empty($row['user_id'])) {
                        $recipients[] = (int)$row['user_id'];
                    }
                }
                break;

            case 'ALL_USERS':
                // Todos os usuários (alunos + instrutores)
                $stmt = $db->prepare("
                    SELECT DISTINCT u.id as user_id
                    FROM usuarios u
                    LEFT JOIN students s ON s.user_id = u.id
                    LEFT JOIN instructors i ON i.user_id = u.id
                    WHERE u.cfc_id = ?
                      AND u.status = 'ativo'
                      AND (s.id IS NOT NULL OR i.id IS NOT NULL)
                ");
                $stmt->execute([$this->cfcId]);
                $results = $stmt->fetchAll();
                foreach ($results as $row) {
                    if (!empty($row['user_id'])) {
                        $recipients[] = (int)$row['user_id'];
                    }
                }
                break;

            case 'ONE_STUDENT':
                // Um aluno específico
                if ($targetId) {
                    $stmt = $db->prepare("
                        SELECT user_id
                        FROM students
                        WHERE id = ? AND cfc_id = ? AND user_id IS NOT NULL
                    ");
                    $stmt->execute([$targetId, $this->cfcId]);
                    $result = $stmt->fetch();
                    if ($result && !empty($result['user_id'])) {
                        $recipients[] = (int)$result['user_id'];
                    }
                }
                break;

            case 'ONE_INSTRUCTOR':
                // Um instrutor específico
                if ($targetId) {
                    $stmt = $db->prepare("
                        SELECT user_id
                        FROM instructors
                        WHERE id = ? AND cfc_id = ? AND user_id IS NOT NULL
                    ");
                    $stmt->execute([$targetId, $this->cfcId]);
                    $result = $stmt->fetch();
                    if ($result && !empty($result['user_id'])) {
                        $recipients[] = (int)$result['user_id'];
                    }
                }
                break;
        }

        // Remover duplicatas
        $recipients = array_unique($recipients);

        return $recipients;
    }
}
