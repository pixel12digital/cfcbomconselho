<?php

namespace App\Models;

class Service extends Model
{
    protected $table = 'services';

    public function findByCfc($cfcId, $includeDeleted = false)
    {
        $sql = "SELECT * FROM {$this->table} WHERE cfc_id = ?";
        $params = [$cfcId];
        
        if (!$includeDeleted) {
            $sql .= " AND deleted_at IS NULL";
        }
        
        $sql .= " ORDER BY name ASC";
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function findActiveByCfc($cfcId)
    {
        $stmt = $this->query(
            "SELECT * FROM {$this->table} WHERE cfc_id = ? AND is_active = 1 AND deleted_at IS NULL ORDER BY name ASC",
            [$cfcId]
        );
        return $stmt->fetchAll();
    }

    public function softDelete($id)
    {
        return $this->update($id, ['deleted_at' => date('Y-m-d H:i:s')]);
    }

    public function toggleActive($id)
    {
        $service = $this->find($id);
        if (!$service) {
            return false;
        }
        
        $newStatus = $service['is_active'] ? 0 : 1;
        return $this->update($id, ['is_active' => $newStatus]);
    }
}
