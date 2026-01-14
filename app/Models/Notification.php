<?php

namespace App\Models;

class Notification extends Model
{
    protected $table = 'notifications';

    /**
     * Busca notificações de um usuário
     */
    public function findByUser($userId, $unreadOnly = false, $limit = null)
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ?";
        $params = [$userId];
        
        if ($unreadOnly) {
            $sql .= " AND is_read = 0";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Conta notificações não lidas de um usuário
     */
    public function countUnread($userId)
    {
        $stmt = $this->query(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
        $result = $stmt->fetch();
        return (int)($result['count'] ?? 0);
    }

    /**
     * Marca notificação como lida
     */
    public function markAsRead($id, $userId)
    {
        return $this->update($id, [
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Marca todas as notificações de um usuário como lidas
     */
    public function markAllAsRead($userId)
    {
        $stmt = $this->query(
            "UPDATE {$this->table} SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
        return true;
    }

    /**
     * Cria uma nova notificação
     */
    public function createNotification($userId, $type, $title, $body = null, $link = null)
    {
        return $this->create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'link' => $link,
            'is_read' => 0
        ]);
    }
}
