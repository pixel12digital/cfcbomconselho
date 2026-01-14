<?php

namespace App\Controllers;

use App\Models\Student;
use App\Models\Enrollment;
use App\Models\User;
use App\Config\Constants;
use App\Config\Database;

class FinanceiroController extends Controller
{
    private $cfcId;
    private $db;

    public function __construct()
    {
        $this->cfcId = $_SESSION['cfc_id'] ?? Constants::CFC_ID_DEFAULT;
        $this->db = Database::getInstance()->getConnection();
    }

    public function index()
    {
        $currentRole = $_SESSION['current_role'] ?? '';
        $userId = $_SESSION['user_id'] ?? null;
        
        $studentModel = new Student();
        $enrollmentModel = new Enrollment();
        $userModel = new User();
        
        $student = null;
        $enrollments = [];
        $totalDebt = 0;
        $totalPaid = 0;
        $overdueStudents = [];
        $dueSoonStudents = [];
        $recentStudents = [];
        $students = [];
        $search = '';
        
        // Se for ALUNO, carregar automaticamente os dados do próprio aluno
        if ($currentRole === Constants::ROLE_ALUNO && $userId) {
            $user = $userModel->findWithLinks($userId);
            if ($user && !empty($user['student_id'])) {
                $studentId = $user['student_id'];
                $student = $studentModel->find($studentId);
                if ($student && $student['cfc_id'] == $this->cfcId) {
                    $enrollments = $enrollmentModel->findByStudent($studentId);
                    
                    // Calcular totais (usando entry_amount para total pago e outstanding_amount para saldo)
                    foreach ($enrollments as $enr) {
                        $finalPrice = (float)$enr['final_price'];
                        $entryAmount = (float)($enr['entry_amount'] ?? 0);
                        
                        $totalPaid += $entryAmount;
                        $totalDebt += max(0, $finalPrice - $entryAmount);
                    }
                }
            }
        } else {
            // Comportamento administrativo (ADMIN, SECRETARIA, etc)
            $search = $_GET['q'] ?? '';
            $studentId = $_GET['student_id'] ?? null;
            
            if ($studentId) {
                $student = $studentModel->find($studentId);
                if ($student && $student['cfc_id'] == $this->cfcId) {
                    $enrollments = $enrollmentModel->findByStudent($studentId);
                    
                    // Registrar consulta recente
                    $this->recordRecentQuery($studentId);
                    
                    // Calcular totais (usando entry_amount para total pago e outstanding_amount para saldo)
                    foreach ($enrollments as $enr) {
                        $finalPrice = (float)$enr['final_price'];
                        $entryAmount = (float)($enr['entry_amount'] ?? 0);
                        
                        $totalPaid += $entryAmount;
                        $totalDebt += max(0, $finalPrice - $entryAmount);
                    }
                }
            } elseif ($search) {
                // Buscar alunos
                $students = $studentModel->findByCfc($this->cfcId, $search);
            } else {
                $students = [];
                // Carregar dados dos cards apenas quando não houver busca
                $overdueStudents = $this->getOverdueStudents();
                $dueSoonStudents = $this->getDueSoonStudents();
                $recentStudents = $this->getRecentStudentsByUser();
            }
        }
        
        $data = [
            'pageTitle' => $currentRole === Constants::ROLE_ALUNO ? 'Financeiro' : 'Consulta Financeira',
            'student' => $student,
            'enrollments' => $enrollments,
            'totalDebt' => $totalDebt,
            'totalPaid' => $totalPaid,
            'search' => $search,
            'students' => $students ?? [],
            'overdueStudents' => $overdueStudents,
            'dueSoonStudents' => $dueSoonStudents,
            'recentStudents' => $recentStudents,
            'isAluno' => $currentRole === Constants::ROLE_ALUNO
        ];
        
        $this->view('financeiro/index', $data);
    }

    /**
     * Busca alunos em atraso (financial_status bloqueado ou pendente)
     */
    private function getOverdueStudents($limit = 10)
    {
        $sql = "SELECT s.id, s.name, s.cpf, s.full_name,
                SUM(CASE WHEN e.financial_status IN ('bloqueado', 'pendente') THEN e.final_price ELSE 0 END) as total_debt,
                MIN(COALESCE(
                    NULLIF(e.first_due_date, '0000-00-00'),
                    NULLIF(e.down_payment_due_date, '0000-00-00'),
                    DATE(e.created_at)
                )) as oldest_due_date
                FROM students s
                INNER JOIN enrollments e ON e.student_id = s.id
                WHERE s.cfc_id = ? 
                AND e.financial_status IN ('bloqueado', 'pendente')
                AND e.status != 'cancelada'
                GROUP BY s.id, s.name, s.cpf, s.full_name
                HAVING total_debt > 0
                ORDER BY oldest_due_date ASC, total_debt DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->cfcId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Busca alunos com vencimentos próximos (7 dias)
     */
    private function getDueSoonStudents($days = 7, $limit = 10)
    {
        $today = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime("+{$days} days"));
        
        $sql = "SELECT s.id, s.name, s.cpf, s.full_name,
                MIN(LEAST(
                    COALESCE(NULLIF(e.first_due_date, '0000-00-00'), '9999-12-31'),
                    COALESCE(NULLIF(e.down_payment_due_date, '0000-00-00'), '9999-12-31')
                )) as next_due_date,
                SUM(e.final_price) as total_debt
                FROM students s
                INNER JOIN enrollments e ON e.student_id = s.id
                WHERE s.cfc_id = ?
                AND e.status != 'cancelada'
                AND (
                    (e.first_due_date >= ? AND e.first_due_date <= ? AND e.first_due_date != '0000-00-00')
                    OR (e.down_payment_due_date >= ? AND e.down_payment_due_date <= ? AND e.down_payment_due_date != '0000-00-00')
                )
                GROUP BY s.id, s.name, s.cpf, s.full_name
                HAVING next_due_date != '9999-12-31'
                ORDER BY next_due_date ASC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->cfcId, $today, $endDate, $today, $endDate, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Busca alunos consultados recentemente pelo usuário
     */
    private function getRecentStudentsByUser($limit = 10)
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return [];
        }

        // Verificar se a tabela existe
        try {
            $sql = "SELECT s.id, s.name, s.cpf, s.full_name, rq.last_viewed_at
                    FROM user_recent_financial_queries rq
                    INNER JOIN students s ON s.id = rq.student_id
                    WHERE rq.user_id = ? AND s.cfc_id = ?
                    ORDER BY rq.last_viewed_at DESC
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $this->cfcId, $limit]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            // Tabela não existe ainda, retornar array vazio
            return [];
        }
    }

    /**
     * Registra consulta recente de um aluno
     */
    private function recordRecentQuery($studentId)
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return;
        }

        // Verificar se a tabela existe antes de tentar inserir
        try {
            // Usar INSERT ... ON DUPLICATE KEY UPDATE para atualizar ou inserir
            $sql = "INSERT INTO user_recent_financial_queries (user_id, student_id, last_viewed_at)
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE last_viewed_at = NOW()";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $studentId]);
        } catch (\PDOException $e) {
            // Tabela não existe ainda, ignorar silenciosamente
        }
    }

    /**
     * Endpoint de autocomplete para busca
     */
    public function autocomplete()
    {
        header('Content-Type: application/json');
        
        $query = $_GET['q'] ?? '';
        if (strlen($query) < 2) {
            echo json_encode([]);
            exit;
        }

        $studentModel = new Student();
        $searchTerm = "%{$query}%";
        
        $sql = "SELECT id, name, cpf, full_name 
                FROM students 
                WHERE cfc_id = ? 
                AND (name LIKE ? OR full_name LIKE ? OR cpf LIKE ?)
                ORDER BY COALESCE(full_name, name) ASC
                LIMIT 10";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->cfcId, $searchTerm, $searchTerm, $searchTerm]);
        $results = $stmt->fetchAll();
        
        $output = [];
        foreach ($results as $row) {
            $output[] = [
                'id' => $row['id'],
                'name' => $row['full_name'] ?: $row['name'],
                'cpf' => $row['cpf']
            ];
        }
        
        echo json_encode($output);
        exit;
    }
}
