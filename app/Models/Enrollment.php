<?php

namespace App\Models;

class Enrollment extends Model
{
    protected $table = 'enrollments';

    public function findByStudent($studentId)
    {
        $stmt = $this->query(
            "SELECT e.*, s.name as service_name, s.category as service_category
             FROM {$this->table} e
             INNER JOIN services s ON e.service_id = s.id
             WHERE e.student_id = ?
             ORDER BY e.created_at DESC",
            [$studentId]
        );
        return $stmt->fetchAll();
    }

    public function findWithDetails($id)
    {
        $stmt = $this->query(
            "SELECT e.*, 
                    s.name as service_name, s.category as service_category,
                    st.name as student_name, st.cpf as student_cpf
             FROM {$this->table} e
             INNER JOIN services s ON e.service_id = s.id
             INNER JOIN students st ON e.student_id = st.id
             WHERE e.id = ?",
            [$id]
        );
        return $stmt->fetch();
    }

    public function calculateFinalPrice($basePrice, $discountValue, $extraValue)
    {
        return max(0, $basePrice - $discountValue + $extraValue);
    }
}
