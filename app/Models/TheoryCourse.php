<?php

namespace App\Models;

class TheoryCourse extends Model
{
    protected $table = 'theory_courses';

    /**
     * Busca cursos ativos de um CFC
     */
    public function findActiveByCfc($cfcId)
    {
        $stmt = $this->query(
            "SELECT * FROM {$this->table} 
             WHERE cfc_id = ? AND active = 1 
             ORDER BY name ASC",
            [$cfcId]
        );
        return $stmt->fetchAll();
    }

    /**
     * Busca curso com suas disciplinas
     */
    public function findWithDisciplines($id)
    {
        $course = $this->find($id);
        if (!$course) {
            return null;
        }

        $stmt = $this->query(
            "SELECT tcd.*, 
                    td.name as discipline_name, 
                    td.default_minutes,
                    td.default_lessons_count,
                    td.default_lesson_minutes
             FROM theory_course_disciplines tcd
             INNER JOIN theory_disciplines td ON tcd.discipline_id = td.id
             WHERE tcd.course_id = ?
             ORDER BY tcd.sort_order ASC",
            [$id]
        );
        $course['disciplines'] = $stmt->fetchAll();
        
        // Incluir campos de aulas no resultado
        foreach ($course['disciplines'] as &$cd) {
            // Se não tem minutes no curso, usar default_minutes da disciplina
            if (empty($cd['minutes']) && !empty($cd['default_minutes'])) {
                $cd['minutes'] = $cd['default_minutes'];
            }
            
            // Se não tem lesson_minutes no curso, usar default_lesson_minutes da disciplina
            if (empty($cd['lesson_minutes']) && !empty($cd['default_lesson_minutes'])) {
                $cd['lesson_minutes'] = $cd['default_lesson_minutes'];
            } elseif (empty($cd['lesson_minutes'])) {
                $cd['lesson_minutes'] = 50;
            }
            
            // Se não tem lessons_count mas tem minutes, inferir para exibição
            if (empty($cd['lessons_count']) && !empty($cd['minutes'])) {
                $cd['lessons_count'] = ceil($cd['minutes'] / $cd['lesson_minutes']);
            }
        }

        return $course;
    }
}
