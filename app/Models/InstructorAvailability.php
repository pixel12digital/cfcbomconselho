<?php

namespace App\Models;

class InstructorAvailability extends Model
{
    protected $table = 'instructor_availability';

    /**
     * Busca disponibilidade de um instrutor
     */
    public function findByInstructor($instructorId)
    {
        $stmt = $this->query(
            "SELECT * FROM {$this->table} 
             WHERE instructor_id = ? 
             ORDER BY day_of_week ASC",
            [$instructorId]
        );
        return $stmt->fetchAll();
    }

    /**
     * Busca disponibilidade de um instrutor por dia da semana
     */
    public function findByInstructorAndDay($instructorId, $dayOfWeek)
    {
        $stmt = $this->query(
            "SELECT * FROM {$this->table} 
             WHERE instructor_id = ? AND day_of_week = ?",
            [$instructorId, $dayOfWeek]
        );
        return $stmt->fetch();
    }

    /**
     * Verifica se instrutor está disponível em um horário específico
     */
    public function isAvailableAt($instructorId, $dayOfWeek, $time)
    {
        $availability = $this->findByInstructorAndDay($instructorId, $dayOfWeek);
        
        if (!$availability || !$availability['is_available']) {
            return false;
        }
        
        $timeStr = date('H:i:s', strtotime($time));
        $startTime = $availability['start_time'];
        $endTime = $availability['end_time'];
        
        return $timeStr >= $startTime && $timeStr <= $endTime;
    }

    /**
     * Salva ou atualiza disponibilidade
     */
    public function saveAvailability($instructorId, $dayOfWeek, $startTime, $endTime, $isAvailable = true)
    {
        $existing = $this->findByInstructorAndDay($instructorId, $dayOfWeek);
        
        if ($existing) {
            return $this->update($existing['id'], [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'is_available' => $isAvailable ? 1 : 0
            ]);
        } else {
            return $this->create([
                'instructor_id' => $instructorId,
                'day_of_week' => $dayOfWeek,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'is_available' => $isAvailable ? 1 : 0
            ]);
        }
    }

    /**
     * Remove todas as disponibilidades de um instrutor
     */
    public function deleteByInstructor($instructorId)
    {
        $this->query(
            "DELETE FROM {$this->table} WHERE instructor_id = ?",
            [$instructorId]
        );
        return true;
    }
}
