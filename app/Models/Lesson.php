<?php

namespace App\Models;

class Lesson extends Model
{
    protected $table = 'lessons';

    /**
     * Busca aulas com detalhes completos
     */
    public function findWithDetails($id)
    {
        $stmt = $this->query(
            "SELECT l.*,
                    s.name as student_name, s.cpf as student_cpf,
                    e.id as enrollment_id, e.financial_status,
                    i.name as instructor_name,
                    v.plate as vehicle_plate, v.model as vehicle_model,
                    u.nome as created_by_name,
                    uc.nome as canceled_by_name
             FROM {$this->table} l
             INNER JOIN students s ON l.student_id = s.id
             INNER JOIN enrollments e ON l.enrollment_id = e.id
             INNER JOIN instructors i ON l.instructor_id = i.id
             LEFT JOIN vehicles v ON l.vehicle_id = v.id
             LEFT JOIN usuarios u ON l.created_by = u.id
             LEFT JOIN usuarios uc ON l.canceled_by = uc.id
             WHERE l.id = ?",
            [$id]
        );
        return $stmt->fetch();
    }

    /**
     * Busca aulas por período
     */
    public function findByPeriod($cfcId, $startDate, $endDate, $filters = [])
    {
        $sql = "SELECT l.*,
                       s.name as student_name,
                       i.name as instructor_name,
                       v.plate as vehicle_plate
                FROM {$this->table} l
                INNER JOIN students s ON l.student_id = s.id
                INNER JOIN instructors i ON l.instructor_id = i.id
                LEFT JOIN vehicles v ON l.vehicle_id = v.id
                WHERE l.cfc_id = ? 
                  AND l.scheduled_date BETWEEN ? AND ?";
        
        $params = [$cfcId, $startDate, $endDate];
        
        // Filtro por status
        if (!empty($filters['status'])) {
            $sql .= " AND l.status = ?";
            $params[] = $filters['status'];
        } else {
            // Se não há filtro de status específico, filtrar canceladas por padrão
            // (a menos que show_canceled esteja ativo)
            if (empty($filters['show_canceled'])) {
                $sql .= " AND l.status != 'cancelada'";
            }
        }
        
        // Filtro por instrutor
        if (!empty($filters['instructor_id'])) {
            $sql .= " AND l.instructor_id = ?";
            $params[] = $filters['instructor_id'];
        }
        
        // Filtro por veículo
        if (!empty($filters['vehicle_id'])) {
            $sql .= " AND l.vehicle_id = ?";
            $params[] = $filters['vehicle_id'];
        }
        
        $sql .= " ORDER BY l.scheduled_date ASC, l.scheduled_time ASC";
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Verifica conflito de horário para instrutor
     */
    public function hasInstructorConflict($instructorId, $scheduledDate, $scheduledTime, $durationMinutes, $excludeLessonId = null, $cfcId = null)
    {
        $startTime = $scheduledTime;
        $endTime = date('H:i:s', strtotime("+{$durationMinutes} minutes", strtotime($scheduledTime)));
        
        $sql = "SELECT COUNT(*) as count
                FROM {$this->table}
                WHERE instructor_id = ?
                  AND scheduled_date = ?
                  AND status NOT IN ('cancelada', 'concluida', 'no_show')
                  AND (
                    (scheduled_time <= ? AND DATE_ADD(scheduled_time, INTERVAL duration_minutes MINUTE) > ?)
                    OR
                    (scheduled_time < ? AND DATE_ADD(scheduled_time, INTERVAL duration_minutes MINUTE) >= ?)
                    OR
                    (scheduled_time >= ? AND scheduled_time < ?)
                  )";
        
        $params = [
            $instructorId,
            $scheduledDate,
            $startTime, $startTime,
            $endTime, $endTime,
            $startTime, $endTime
        ];
        
        // Filtrar por CFC se fornecido (importante para multi-CFC)
        if ($cfcId !== null) {
            $sql .= " AND cfc_id = ?";
            $params[] = $cfcId;
        }
        
        if ($excludeLessonId) {
            $sql .= " AND id != ?";
            $params[] = $excludeLessonId;
        }
        
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        
        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Verifica conflito de horário para veículo
     */
    public function hasVehicleConflict($vehicleId, $scheduledDate, $scheduledTime, $durationMinutes, $excludeLessonId = null, $cfcId = null)
    {
        if (!$vehicleId) {
            return false; // Veículo é obrigatório
        }
        
        $startTime = $scheduledTime;
        $endTime = date('H:i:s', strtotime("+{$durationMinutes} minutes", strtotime($scheduledTime)));
        
        $sql = "SELECT COUNT(*) as count
                FROM {$this->table}
                WHERE vehicle_id = ?
                  AND scheduled_date = ?
                  AND status NOT IN ('cancelada', 'concluida', 'no_show')
                  AND (
                    (scheduled_time <= ? AND DATE_ADD(scheduled_time, INTERVAL duration_minutes MINUTE) > ?)
                    OR
                    (scheduled_time < ? AND DATE_ADD(scheduled_time, INTERVAL duration_minutes MINUTE) >= ?)
                    OR
                    (scheduled_time >= ? AND scheduled_time < ?)
                  )";
        
        $params = [
            $vehicleId,
            $scheduledDate,
            $startTime, $startTime,
            $endTime, $endTime,
            $startTime, $endTime
        ];
        
        // Filtrar por CFC se fornecido (importante para multi-CFC)
        if ($cfcId !== null) {
            $sql .= " AND cfc_id = ?";
            $params[] = $cfcId;
        }
        
        if ($excludeLessonId) {
            $sql .= " AND id != ?";
            $params[] = $excludeLessonId;
        }
        
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        
        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Busca aulas de um aluno
     */
    public function findByStudent($studentId, $limit = null)
    {
        $sql = "SELECT l.*,
                       i.name as instructor_name,
                       v.plate as vehicle_plate
                FROM {$this->table} l
                INNER JOIN instructors i ON l.instructor_id = i.id
                LEFT JOIN vehicles v ON l.vehicle_id = v.id
                WHERE l.student_id = ?
                ORDER BY l.scheduled_date DESC, l.scheduled_time DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $this->query($sql, [$studentId]);
        return $stmt->fetchAll();
    }

    /**
     * Busca aulas de uma matrícula
     */
    public function findByEnrollment($enrollmentId)
    {
        $stmt = $this->query(
            "SELECT l.*,
                    i.name as instructor_name,
                    v.plate as vehicle_plate
             FROM {$this->table} l
             INNER JOIN instructors i ON l.instructor_id = i.id
             LEFT JOIN vehicles v ON l.vehicle_id = v.id
             WHERE l.enrollment_id = ?
             ORDER BY l.scheduled_date ASC, l.scheduled_time ASC",
            [$enrollmentId]
        );
        return $stmt->fetchAll();
    }

    /**
     * Busca a próxima aula agendada de um aluno
     */
    public function findNextByStudent($studentId)
    {
        $today = date('Y-m-d');
        $stmt = $this->query(
            "SELECT l.*,
                    i.name as instructor_name,
                    v.plate as vehicle_plate
             FROM {$this->table} l
             INNER JOIN instructors i ON l.instructor_id = i.id
             LEFT JOIN vehicles v ON l.vehicle_id = v.id
             WHERE l.student_id = ?
               AND l.status = 'agendada'
               AND (l.scheduled_date > ? OR (l.scheduled_date = ? AND l.scheduled_time >= CURTIME()))
             ORDER BY l.scheduled_date ASC, l.scheduled_time ASC
             LIMIT 1",
            [$studentId, $today, $today]
        );
        return $stmt->fetch();
    }
}
