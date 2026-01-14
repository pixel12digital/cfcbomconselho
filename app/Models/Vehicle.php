<?php

namespace App\Models;

class Vehicle extends Model
{
    protected $table = 'vehicles';

    public function findByCfc($cfcId, $activeOnly = true)
    {
        $sql = "SELECT * FROM {$this->table} WHERE cfc_id = ?";
        $params = [$cfcId];
        
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        
        $sql .= " ORDER BY plate ASC";
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function findActive($cfcId)
    {
        return $this->findByCfc($cfcId, true);
    }

    public function findByPlate($cfcId, $plate)
    {
        $stmt = $this->query(
            "SELECT * FROM {$this->table} WHERE cfc_id = ? AND plate = ?",
            [$cfcId, strtoupper($plate)]
        );
        return $stmt->fetch();
    }
}
