<?php

namespace App\Models;

class StudentHistory extends Model
{
    protected $table = 'student_history';

    /**
     * Busca histórico de um aluno ordenado do mais recente para o mais antigo
     */
    public function findByStudent($studentId, $limit = null)
    {
        $sql = "SELECT sh.*, u.nome as created_by_name 
                FROM {$this->table} sh
                LEFT JOIN usuarios u ON sh.created_by = u.id
                WHERE sh.student_id = ?
                ORDER BY sh.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $this->query($sql, [$studentId]);
        return $stmt->fetchAll();
    }

    /**
     * Busca histórico por tipo
     */
    public function findByStudentAndType($studentId, $type)
    {
        $stmt = $this->query(
            "SELECT * FROM {$this->table} 
             WHERE student_id = ? AND type = ? 
             ORDER BY created_at DESC",
            [$studentId, $type]
        );
        return $stmt->fetchAll();
    }

    /**
     * Conta eventos por tipo
     */
    public function countByType($studentId, $type)
    {
        $stmt = $this->query(
            "SELECT COUNT(*) as total 
             FROM {$this->table} 
             WHERE student_id = ? AND type = ?",
            [$studentId, $type]
        );
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
}
