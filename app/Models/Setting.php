<?php

namespace App\Models;

use App\Config\Database;
use App\Config\Constants;

class Setting extends Model
{
    protected $table = 'smtp_settings';

    public function findByCfc($cfcId)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT * FROM {$this->table} 
            WHERE cfc_id = ? AND is_active = 1 
            ORDER BY id DESC 
            LIMIT 1
        ");
        $stmt->execute([$cfcId]);
        return $stmt->fetch();
    }

    public function save($cfcId, $data)
    {
        $db = Database::getInstance()->getConnection();
        
        // Verificar se já existe configuração ativa
        $existing = $this->findByCfc($cfcId);
        
        if ($existing) {
            // Desativar configurações antigas
            $stmt = $db->prepare("UPDATE {$this->table} SET is_active = 0 WHERE cfc_id = ?");
            $stmt->execute([$cfcId]);
        }
        
        // Criar nova configuração
        $stmt = $db->prepare("
            INSERT INTO {$this->table} 
            (cfc_id, host, port, username, password, encryption, from_email, from_name, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        
        $result = $stmt->execute([
            $cfcId,
            $data['host'],
            $data['port'],
            $data['username'],
            $data['password'], // Já deve vir criptografada
            $data['encryption'],
            $data['from_email'],
            $data['from_name'] ?? null
        ]);
        
        return $result;
    }
}
