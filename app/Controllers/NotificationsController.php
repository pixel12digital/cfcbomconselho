<?php

namespace App\Controllers;

use App\Models\Notification;
use App\Config\Constants;

class NotificationsController extends Controller
{
    private $notificationModel;

    public function __construct()
    {
        $this->notificationModel = new Notification();
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
}
