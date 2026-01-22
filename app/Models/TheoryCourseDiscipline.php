<?php

namespace App\Models;

class TheoryCourseDiscipline extends Model
{
    protected $table = 'theory_course_disciplines';

    /**
     * Busca disciplinas de um curso
     */
    public function findByCourse($courseId)
    {
        $stmt = $this->query(
            "SELECT tcd.*, td.name as discipline_name, td.default_minutes
             FROM {$this->table} tcd
             INNER JOIN theory_disciplines td ON tcd.discipline_id = td.id
             WHERE tcd.course_id = ?
             ORDER BY tcd.sort_order ASC",
            [$courseId]
        );
        return $stmt->fetchAll();
    }

    /**
     * Remove todas as disciplinas de um curso
     */
    public function deleteByCourse($courseId)
    {
        $stmt = $this->query(
            "DELETE FROM {$this->table} WHERE course_id = ?",
            [$courseId]
        );
        return true;
    }
}
