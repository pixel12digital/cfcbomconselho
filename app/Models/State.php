<?php

namespace App\Models;

class State extends Model
{
    protected $table = 'states';

    /**
     * Busca todos os estados ordenados por UF
     */
    public function findAll()
    {
        $stmt = $this->query("SELECT * FROM {$this->table} ORDER BY uf ASC");
        return $stmt->fetchAll();
    }

    /**
     * Busca estado por UF
     */
    public function findByUf($uf)
    {
        $stmt = $this->query(
            "SELECT * FROM {$this->table} WHERE uf = ?",
            [strtoupper($uf)]
        );
        return $stmt->fetch();
    }
}
