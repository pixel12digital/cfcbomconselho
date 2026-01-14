<?php

namespace App\Models;

class City extends Model
{
    protected $table = 'cities';

    /**
     * Busca cidades por UF
     */
    public function findByUf($uf)
    {
        $stmt = $this->query(
            "SELECT c.id, c.name 
             FROM {$this->table} c
             INNER JOIN states s ON c.state_id = s.id
             WHERE s.uf = ?
             ORDER BY c.name ASC",
            [strtoupper($uf)]
        );
        return $stmt->fetchAll();
    }

    /**
     * Busca cidade por ID
     */
    public function findById($id)
    {
        return $this->find($id);
    }

    /**
     * Busca cidade por ID e valida se pertence ao estado
     */
    public function findByIdAndUf($cityId, $uf)
    {
        $stmt = $this->query(
            "SELECT c.* 
             FROM {$this->table} c
             INNER JOIN states s ON c.state_id = s.id
             WHERE c.id = ? AND s.uf = ?",
            [$cityId, strtoupper($uf)]
        );
        return $stmt->fetch();
    }

    /**
     * Busca cidades por UF com filtro de texto (para autocomplete)
     * Retorna no máximo 30 resultados
     */
    public function searchByUf($uf, $query = '', $limit = 30)
    {
        $sql = "SELECT c.id, c.name 
                FROM {$this->table} c
                INNER JOIN states s ON c.state_id = s.id
                WHERE s.uf = ?";
        
        $params = [strtoupper($uf)];
        
        if (!empty($query) && strlen(trim($query)) >= 2) {
            $sql .= " AND c.name LIKE ?";
            $params[] = '%' . trim($query) . '%';
        }
        
        $sql .= " ORDER BY c.name ASC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Busca cidade por UF e nome exato (case-insensitive, trim)
     * Usado para resolver cidade do ViaCEP
     */
    public function findByUfAndName($uf, $cityName)
    {
        $stmt = $this->query(
            "SELECT c.id, c.name 
             FROM {$this->table} c
             INNER JOIN states s ON c.state_id = s.id
             WHERE s.uf = ? 
               AND LOWER(TRIM(c.name)) = LOWER(?)
             LIMIT 1",
            [strtoupper($uf), trim($cityName)]
        );
        return $stmt->fetch();
    }
}
