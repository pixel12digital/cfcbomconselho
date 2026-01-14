<?php

namespace App\Models;

use App\Config\Database;

class AccountActivationToken
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Cria um novo token de ativação
     */
    public function create($userId, $tokenHash, $expiresAt, $createdBy = null)
    {
        // Invalidar tokens anteriores não usados
        $this->invalidatePreviousTokens($userId);

        $stmt = $this->db->prepare("
            INSERT INTO account_activation_tokens (user_id, token_hash, expires_at, created_by)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $tokenHash, $expiresAt, $createdBy]);
        return $this->db->lastInsertId();
    }

    /**
     * Busca token por hash
     */
    public function findByTokenHash($tokenHash)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM account_activation_tokens
            WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()
        ");
        $stmt->execute([$tokenHash]);
        return $stmt->fetch();
    }

    /**
     * Marca token como usado
     */
    public function markAsUsed($tokenId)
    {
        $stmt = $this->db->prepare("
            UPDATE account_activation_tokens
            SET used_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$tokenId]);
    }

    /**
     * Invalida tokens anteriores não usados do usuário
     */
    private function invalidatePreviousTokens($userId)
    {
        $stmt = $this->db->prepare("
            UPDATE account_activation_tokens
            SET used_at = NOW()
            WHERE user_id = ? AND used_at IS NULL AND expires_at > NOW()
        ");
        $stmt->execute([$userId]);
    }

    /**
     * Limpa tokens expirados (manutenção)
     */
    public function cleanExpired()
    {
        $stmt = $this->db->prepare("
            DELETE FROM account_activation_tokens
            WHERE expires_at < NOW() AND used_at IS NOT NULL
        ");
        return $stmt->execute();
    }

    /**
     * Verifica se usuário tem token ativo
     */
    public function hasActiveToken($userId)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count FROM account_activation_tokens
            WHERE user_id = ? AND used_at IS NULL AND expires_at > NOW()
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    /**
     * Busca token ativo do usuário
     */
    public function findActiveToken($userId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM account_activation_tokens
            WHERE user_id = ? AND used_at IS NULL AND expires_at > NOW()
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
}
