<?php

namespace App\Models;

class StudentStep extends Model
{
    protected $table = 'student_steps';

    public function findByEnrollment($enrollmentId)
    {
        $stmt = $this->query(
            "SELECT ss.*, s.code, s.name, s.description, s.order
             FROM {$this->table} ss
             INNER JOIN steps s ON ss.step_id = s.id
             WHERE ss.enrollment_id = ?
             ORDER BY s.order ASC",
            [$enrollmentId]
        );
        return $stmt->fetchAll();
    }

    public function findByEnrollmentAndStep($enrollmentId, $stepId)
    {
        $stmt = $this->query(
            "SELECT * FROM {$this->table} WHERE enrollment_id = ? AND step_id = ?",
            [$enrollmentId, $stepId]
        );
        return $stmt->fetch();
    }

    public function toggleStatus($id, $status, $source, $validatedByUserId = null)
    {
        $data = [
            'status' => $status,
            'source' => $source
        ];
        
        if ($status === 'concluida' && $source === 'cfc' && $validatedByUserId) {
            $data['validated_by_user_id'] = $validatedByUserId;
            $data['validated_at'] = date('Y-m-d H:i:s');
        } else {
            $data['validated_by_user_id'] = null;
            $data['validated_at'] = null;
        }
        
        return $this->update($id, $data);
    }
}
