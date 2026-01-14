<?php

namespace App\Models;

class Step extends Model
{
    protected $table = 'steps';

    public function findAllActive()
    {
        $stmt = $this->query(
            "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY `order` ASC"
        );
        return $stmt->fetchAll();
    }

    public function findByCode($code)
    {
        $stmt = $this->query(
            "SELECT * FROM {$this->table} WHERE code = ?",
            [$code]
        );
        return $stmt->fetch();
    }
}
