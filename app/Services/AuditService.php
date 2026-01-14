<?php

namespace App\Services;

use App\Config\Database;

class AuditService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function log($action, $module, $recordId = null, $dataBefore = null, $dataAfter = null)
    {
        $cfcId = $_SESSION['cfc_id'] ?? 1;
        $userId = $_SESSION['user_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $sql = "INSERT INTO auditoria (cfc_id, usuario_id, acao, modulo, registro_id, dados_antes, dados_depois, ip, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $cfcId,
            $userId,
            $action,
            $module,
            $recordId,
            $dataBefore ? json_encode($dataBefore) : null,
            $dataAfter ? json_encode($dataAfter) : null,
            $ip,
            $userAgent
        ]);
    }

    public function logCreate($module, $recordId, $data)
    {
        $this->log('create', $module, $recordId, null, $data);
    }

    public function logUpdate($module, $recordId, $dataBefore, $dataAfter)
    {
        $this->log('update', $module, $recordId, $dataBefore, $dataAfter);
    }

    public function logToggle($module, $recordId, $dataBefore, $dataAfter)
    {
        $this->log('toggle', $module, $recordId, $dataBefore, $dataAfter);
    }

    public function logDelete($module, $recordId, $data)
    {
        $this->log('delete', $module, $recordId, $data, null);
    }
}
