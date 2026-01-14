<?php

namespace App\Services;

use App\Models\StudentHistory;

class StudentHistoryService
{
    private $historyModel;

    public function __construct()
    {
        $this->historyModel = new StudentHistory();
    }

    /**
     * Registra um evento no histórico do aluno
     * 
     * @param int $studentId ID do aluno
     * @param string $type Tipo do evento (ex: 'cadastro', 'matricula', 'financeiro', etc.)
     * @param string $description Descrição curta e clara do evento
     * @param int|null $createdBy ID do usuário que realizou a ação (null = sistema)
     * @return int ID do registro criado
     */
    public function add($studentId, $type, $description, $createdBy = null)
    {
        if (empty($createdBy)) {
            $createdBy = $_SESSION['user_id'] ?? null;
        }

        $data = [
            'student_id' => $studentId,
            'type' => $type,
            'description' => $description,
            'created_by' => $createdBy
        ];

        return $this->historyModel->create($data);
    }

    /**
     * Registra cadastro do aluno
     */
    public function logStudentCreated($studentId, $studentName)
    {
        return $this->add($studentId, 'cadastro', "Aluno cadastrado no sistema: {$studentName}");
    }

    /**
     * Registra alteração de dados pessoais
     */
    public function logStudentUpdated($studentId, $changes = [])
    {
        $description = "Dados pessoais atualizados";
        if (!empty($changes)) {
            $fields = [];
            $fieldNames = [
                'full_name' => 'Nome completo',
                'cpf' => 'CPF',
                'birth_date' => 'Data de nascimento',
                'phone_primary' => 'Telefone principal',
                'email' => 'Email',
                'address' => 'Endereço'
            ];
            
            foreach ($changes as $field => $value) {
                if (isset($fieldNames[$field])) {
                    $fields[] = $fieldNames[$field];
                }
            }
            
            if (!empty($fields)) {
                $description .= ": " . implode(", ", $fields);
            }
        }
        
        return $this->add($studentId, 'cadastro', $description);
    }

    /**
     * Registra criação de matrícula
     */
    public function logEnrollmentCreated($studentId, $enrollmentId, $serviceName)
    {
        return $this->add($studentId, 'matricula', "Matrícula criada: {$serviceName} (#{$enrollmentId})");
    }

    /**
     * Registra alteração de status da matrícula
     */
    public function logEnrollmentStatusChanged($studentId, $enrollmentId, $oldStatus, $newStatus)
    {
        $statusLabels = [
            'ativa' => 'Ativa',
            'concluida' => 'Concluída',
            'cancelada' => 'Cancelada'
        ];
        
        $oldLabel = $statusLabels[$oldStatus] ?? $oldStatus;
        $newLabel = $statusLabels[$newStatus] ?? $newStatus;
        
        return $this->add($studentId, 'matricula', "Status da matrícula #{$enrollmentId} alterado: {$oldLabel} → {$newLabel}");
    }

    /**
     * Registra cancelamento de matrícula
     */
    public function logEnrollmentCancelled($studentId, $enrollmentId, $serviceName)
    {
        return $this->add($studentId, 'matricula', "Matrícula cancelada: {$serviceName} (#{$enrollmentId})");
    }

    /**
     * Registra evento financeiro (resumo)
     */
    public function logFinancialEvent($studentId, $description)
    {
        return $this->add($studentId, 'financeiro', $description);
    }

    /**
     * Registra situação financeira alterada
     */
    public function logFinancialStatusChanged($studentId, $oldStatus, $newStatus)
    {
        $statusLabels = [
            'em_dia' => 'Em Dia',
            'pendente' => 'Pendente',
            'bloqueado' => 'Bloqueado'
        ];
        
        $oldLabel = $statusLabels[$oldStatus] ?? $oldStatus;
        $newLabel = $statusLabels[$newStatus] ?? $newStatus;
        
        return $this->add($studentId, 'financeiro', "Situação financeira alterada: {$oldLabel} → {$newLabel}");
    }

    /**
     * Registra evento de agenda/aula
     */
    public function logAgendaEvent($studentId, $description)
    {
        return $this->add($studentId, 'agenda', $description);
    }

    /**
     * Registra evento do processo DETRAN
     */
    public function logDetranEvent($studentId, $description)
    {
        return $this->add($studentId, 'detran', $description);
    }

    /**
     * Registra RENACH informado
     */
    public function logRenachInformed($studentId, $renach)
    {
        return $this->add($studentId, 'detran', "RENACH informado: {$renach}");
    }

    /**
     * Registra mudança na situação do processo DETRAN
     */
    public function logDetranProcessStatusChanged($studentId, $oldStatus, $newStatus)
    {
        $statusLabels = [
            'nao_iniciado' => 'Não Iniciado',
            'em_andamento' => 'Em Andamento',
            'concluido' => 'Concluído',
            'cancelado' => 'Cancelado'
        ];
        
        $oldLabel = $statusLabels[$oldStatus] ?? $oldStatus;
        $newLabel = $statusLabels[$newStatus] ?? $newStatus;
        
        return $this->add($studentId, 'detran', "Situação do processo DETRAN alterada: {$oldLabel} → {$newLabel}");
    }

    /**
     * Registra observação manual
     */
    public function logManualObservation($studentId, $observation)
    {
        // Limitar tamanho da observação para não inflar o histórico
        $description = mb_substr($observation, 0, 200);
        if (mb_strlen($observation) > 200) {
            $description .= '...';
        }
        
        return $this->add($studentId, 'observacao', "Observação registrada: {$description}");
    }

    /**
     * Registra ação administrativa relevante
     */
    public function logAdministrativeAction($studentId, $description)
    {
        return $this->add($studentId, 'administrativo', $description);
    }
}
