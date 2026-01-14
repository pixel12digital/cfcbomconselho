<?php

namespace App\Services;

use App\Config\Database;
use App\Config\Constants;

class UserCreationService
{
    private $db;
    private $cfcId;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->cfcId = $_SESSION['cfc_id'] ?? Constants::CFC_ID_DEFAULT;
    }

    /**
     * Cria usuário automaticamente para um aluno
     */
    public function createForStudent($studentId, $email, $fullName = null)
    {
        // Verificar se aluno já tem usuário
        $stmt = $this->db->prepare("SELECT user_id FROM students WHERE id = ?");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch();
        
        if ($student && !empty($student['user_id'])) {
            return $student['user_id']; // Já tem usuário
        }

        // Verificar se email já existe
        $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new \Exception("E-mail já está em uso por outro usuário.");
        }

        // Gerar senha temporária segura
        $tempPassword = $this->generateTempPassword();
        $hashedPassword = password_hash($tempPassword, PASSWORD_BCRYPT);

        try {
            $this->db->beginTransaction();

            // Criar usuário
            $stmt = $this->db->prepare("
                INSERT INTO usuarios (cfc_id, nome, email, password, status, must_change_password) 
                VALUES (?, ?, ?, ?, 'ativo', 1)
            ");
            $stmt->execute([
                $this->cfcId,
                $fullName ?: 'Aluno',
                $email,
                $hashedPassword
            ]);
            $userId = $this->db->lastInsertId();

            // Vincular com aluno
            $stmt = $this->db->prepare("UPDATE students SET user_id = ? WHERE id = ?");
            $stmt->execute([$userId, $studentId]);

            // Associar role ALUNO
            $stmt = $this->db->prepare("INSERT INTO usuario_roles (usuario_id, role) VALUES (?, 'ALUNO')");
            $stmt->execute([$userId]);

            $this->db->commit();

            // Retornar dados para possível envio de e-mail
            return [
                'user_id' => $userId,
                'email' => $email,
                'temp_password' => $tempPassword
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Cria usuário automaticamente para um instrutor
     */
    public function createForInstructor($instructorId, $email, $name)
    {
        // Verificar se instrutor já tem usuário
        $stmt = $this->db->prepare("SELECT user_id FROM instructors WHERE id = ?");
        $stmt->execute([$instructorId]);
        $instructor = $stmt->fetch();
        
        if ($instructor && !empty($instructor['user_id'])) {
            return $instructor['user_id']; // Já tem usuário
        }

        // Verificar se email já existe
        $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new \Exception("E-mail já está em uso por outro usuário.");
        }

        // Gerar senha temporária segura
        $tempPassword = $this->generateTempPassword();
        $hashedPassword = password_hash($tempPassword, PASSWORD_BCRYPT);

        try {
            $this->db->beginTransaction();

            // Criar usuário
            $stmt = $this->db->prepare("
                INSERT INTO usuarios (cfc_id, nome, email, password, status, must_change_password) 
                VALUES (?, ?, ?, ?, 'ativo', 1)
            ");
            $stmt->execute([
                $this->cfcId,
                $name,
                $email,
                $hashedPassword
            ]);
            $userId = $this->db->lastInsertId();

            // Vincular com instrutor
            $stmt = $this->db->prepare("UPDATE instructors SET user_id = ? WHERE id = ?");
            $stmt->execute([$userId, $instructorId]);

            // Associar role INSTRUTOR
            $stmt = $this->db->prepare("INSERT INTO usuario_roles (usuario_id, role) VALUES (?, 'INSTRUTOR')");
            $stmt->execute([$userId]);

            $this->db->commit();

            // Retornar dados para possível envio de e-mail
            return [
                'user_id' => $userId,
                'email' => $email,
                'temp_password' => $tempPassword
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Gera senha temporária segura
     */
    private function generateTempPassword($length = 12)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*';
        return substr(str_shuffle(str_repeat($chars, ceil($length / strlen($chars)))), 0, $length);
    }

    /**
     * Verifica se aluno tem usuário
     */
    public function studentHasUser($studentId)
    {
        $stmt = $this->db->prepare("SELECT user_id FROM students WHERE id = ? AND user_id IS NOT NULL");
        $stmt->execute([$studentId]);
        return (bool)$stmt->fetch();
    }

    /**
     * Verifica se instrutor tem usuário
     */
    public function instructorHasUser($instructorId)
    {
        $stmt = $this->db->prepare("SELECT user_id FROM instructors WHERE id = ? AND user_id IS NOT NULL");
        $stmt->execute([$instructorId]);
        return (bool)$stmt->fetch();
    }
}
