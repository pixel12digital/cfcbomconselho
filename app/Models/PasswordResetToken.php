<?php

namespace App\Models;

use App\Config\Database;

class PasswordResetToken extends Model
{
    protected $table = 'password_reset_tokens';

    /**
     * Cria um novo token de recuperação
     */
    public function createToken($userId, $expiresInHours = 1)
    {
        $db = Database::getInstance()->getConnection();
        
        // Gerar token único
        $token = bin2hex(random_bytes(32));
        
        // Calcular expiração
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiresInHours} hours"));
        
        $stmt = $db->prepare("
            INSERT INTO {$this->table} (user_id, token, expires_at) 
            VALUES (?, ?, ?)
        ");
        
        if ($stmt->execute([$userId, $token, $expiresAt])) {
            return $token;
        }
        
        return null;
    }

    /**
     * Busca token válido
     */
    public function findValidToken($token)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT prt.*, u.id as user_id, u.email, u.nome
            FROM {$this->table} prt
            INNER JOIN usuarios u ON u.id = prt.user_id
            WHERE prt.token = ? 
            AND prt.expires_at > NOW()
            AND prt.used_at IS NULL
        ");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    /**
     * Marca token como usado
     */
    public function markAsUsed($token)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            UPDATE {$this->table} 
            SET used_at = NOW() 
            WHERE token = ?
        ");
        return $stmt->execute([$token]);
    }

    /**
     * Remove tokens expirados (limpeza)
     */
    public function cleanExpired()
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            DELETE FROM {$this->table} 
            WHERE expires_at < NOW() OR used_at IS NOT NULL
        ");
        return $stmt->execute();
    }
}
