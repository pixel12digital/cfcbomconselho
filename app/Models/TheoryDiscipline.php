<?php

namespace App\Models;

class TheoryDiscipline extends Model
{
    protected $table = 'theory_disciplines';

    /**
     * Busca disciplinas ativas de um CFC
     */
    public function findActiveByCfc($cfcId)
    {
        $stmt = $this->query(
            "SELECT * FROM {$this->table} 
             WHERE cfc_id = ? AND active = 1 
             ORDER BY sort_order ASC, name ASC",
            [$cfcId]
        );
        return $stmt->fetchAll();
    }

    /**
     * Busca todas as disciplinas de um CFC (incluindo inativas)
     */
    public function findByCfc($cfcId)
    {
        $stmt = $this->query(
            "SELECT * FROM {$this->table} 
             WHERE cfc_id = ? 
             ORDER BY sort_order ASC, name ASC",
            [$cfcId]
        );
        return $stmt->fetchAll();
    }
}
