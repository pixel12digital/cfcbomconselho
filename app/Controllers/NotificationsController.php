<?php

namespace App\Controllers;

use App\Models\Notification;
use App\Config\Constants;
use App\Services\AuditService;

class NotificationsController extends Controller
{
    private $notificationModel;
    private $auditService;

    public function __construct()
    {
        $this->notificationModel = new Notification();
        $this->auditService = new AuditService();
    }

    /**
     * Lista notificações do usuário logado
     */
    public function index()
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            redirect(base_url('login'));
        }

        $filter = $_GET['filter'] ?? 'all'; // all, unread
        $unreadOnly = ($filter === 'unread');
        
        $notifications = $this->notificationModel->findByUser($userId, $unreadOnly);
        $unreadCount = $this->notificationModel->countUnread($userId);

        $data = [
            'pageTitle' => 'Notificações',
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'filter' => $filter
        ];

        $this->view('notifications/index', $data);
    }

    /**
     * Marca uma notificação como lida
     */
    public function markAsRead($id)
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            redirect(base_url('login'));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_verify($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                redirect(base_url('notificacoes'));
            }

            // Verificar se a notificação pertence ao usuário
            $notification = $this->notificationModel->find($id);
            if ($notification && $notification['user_id'] == $userId) {
                $this->notificationModel->markAsRead($id, $userId);
            }
        }

        redirect(base_url('notificacoes'));
    }

    /**
     * Marca todas as notificações como lidas
     */
    public function markAllAsRead()
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            redirect(base_url('login'));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_verify($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                redirect(base_url('notificacoes'));
            }

            $this->notificationModel->markAllAsRead($userId);
            $_SESSION['success'] = 'Todas as notificações foram marcadas como lidas.';
        }

        redirect(base_url('notificacoes'));
    }

    /**
     * API: Retorna contador de não lidas (para header)
     */
    public function getUnreadCount()
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            $this->json(['count' => 0]);
            return;
        }

        $count = $this->notificationModel->countUnread($userId);
        $this->json(['count' => $count]);
    }

    /**
     * Excluir todo o histórico de notificações (apenas ADMIN)
     */
    public function excluirHistorico()
    {
        // Verificar se é ADMIN
        $currentRole = $_SESSION['current_role'] ?? '';
        if ($currentRole !== Constants::ROLE_ADMIN) {
            $_SESSION['error'] = 'Você não tem permissão para executar esta ação.';
            redirect(base_url('notificacoes'));
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('notificacoes'));
        }

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido.';
            redirect(base_url('notificacoes'));
        }

        try {
            $db = \App\Config\Database::getInstance()->getConnection();
            
            // Contar notificações antes de deletar
            $count = $db->query("SELECT COUNT(*) as count FROM notifications")->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Registrar auditoria
            $this->auditService->log('delete_all_notifications', 'notifications', null, ['count' => $count], null);
            
            // Deletar todas as notificações
            $db->exec("DELETE FROM notifications");
            
            $_SESSION['success'] = "Histórico de notificações excluído com sucesso! ({$count} notificação(ões) removida(s))";
        } catch (\Exception $e) {
            error_log("Erro ao excluir histórico de notificações: " . $e->getMessage());
            $_SESSION['error'] = 'Erro ao excluir histórico de notificações: ' . $e->getMessage();
        }

        redirect(base_url('notificacoes'));
    }
}
