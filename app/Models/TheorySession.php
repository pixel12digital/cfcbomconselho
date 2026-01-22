<?php

namespace App\Models;

class TheorySession extends Model
{
    protected $table = 'theory_sessions';

    /**
     * Busca sessões de uma turma
     */
    public function findByClass($classId)
    {
        $stmt = $this->query(
            "SELECT ts.*, 
                    td.name as discipline_name,
                    l.id as lesson_id, l.status as lesson_status
             FROM {$this->table} ts
             INNER JOIN theory_disciplines td ON ts.discipline_id = td.id
             LEFT JOIN lessons l ON ts.lesson_id = l.id
             WHERE ts.class_id = ?
             ORDER BY ts.starts_at ASC",
            [$classId]
        );
        return $stmt->fetchAll();
    }

    /**
     * Busca sessões por período (para agenda)
     */
    public function findByPeriod($cfcId, $startDate, $endDate, $instructorId = null)
    {
        $sql = "SELECT ts.*, 
                       td.name as discipline_name,
                       tc.name as class_name,
                       tc.course_id,
                       i.name as instructor_name,
                       l.id as lesson_id, l.status as lesson_status
                FROM {$this->table} ts
                INNER JOIN theory_classes tc ON ts.class_id = tc.id
                INNER JOIN theory_disciplines td ON ts.discipline_id = td.id
                INNER JOIN instructors i ON tc.instructor_id = i.id
                LEFT JOIN lessons l ON ts.lesson_id = l.id
                WHERE tc.cfc_id = ?
                  AND DATE(ts.starts_at) BETWEEN ? AND ?";
        
        $params = [$cfcId, $startDate, $endDate];
        
        if ($instructorId) {
            $sql .= " AND tc.instructor_id = ?";
            $params[] = $instructorId;
        }
        
        $sql .= " ORDER BY ts.starts_at ASC";
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Busca sessão com detalhes
     */
    public function findWithDetails($id)
    {
        $stmt = $this->query(
            "SELECT ts.*, 
                    td.name as discipline_name,
                    tc.id as class_id, tc.name as class_name,
                    i.name as instructor_name,
                    l.id as lesson_id
             FROM {$this->table} ts
             INNER JOIN theory_classes tc ON ts.class_id = tc.id
             INNER JOIN theory_disciplines td ON ts.discipline_id = td.id
             INNER JOIN instructors i ON tc.instructor_id = i.id
             LEFT JOIN lessons l ON ts.lesson_id = l.id
             WHERE ts.id = ?",
            [$id]
        );
        return $stmt->fetch();
    }
}
