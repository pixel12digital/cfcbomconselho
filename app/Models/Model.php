<?php

namespace App\Models;

use App\Config\Database;

abstract class Model
{
    protected $table;
    protected $primaryKey = 'id';
    protected $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    protected function query($sql, $params = [])
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function find($id)
    {
        $stmt = $this->query("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?", [$id]);
        return $stmt->fetch();
    }

    public function all()
    {
        $stmt = $this->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll();
    }

    public function create($data)
    {
        $fields = array_keys($data);
        $values = array_values($data);
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES ({$placeholders})";
        $this->query($sql, $values);
        
        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        $fields = array_keys($data);
        $values = array_values($data);
        $values[] = $id;
        
        $setClause = implode(' = ?, ', $fields) . ' = ?';
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = ?";
        $this->query($sql, $values);
        
        return true;
    }

    public function delete($id)
    {
        $this->query("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?", [$id]);
        return true;
    }
}
