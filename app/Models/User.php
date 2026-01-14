<?php

namespace App\Models;

use App\Config\Database;

class User extends Model
{
    protected $table = 'usuarios';

    public static function findByEmail($email)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public static function getUserRoles($userId)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT ur.role, r.nome 
            FROM usuario_roles ur
            JOIN roles r ON r.role = ur.role
            WHERE ur.usuario_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Busca usuário com informações de vinculação (aluno/instrutor)
     */
    public function findWithLinks($userId)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT u.*,
                   i.id as instructor_id, i.name as instructor_name,
                   s.id as student_id, s.name as student_name, s.full_name as student_full_name
            FROM usuarios u
            LEFT JOIN instructors i ON i.user_id = u.id
            LEFT JOIN students s ON s.user_id = u.id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    /**
     * Lista todos os usuários com informações de vinculação
     */
    public function findAllWithLinks($cfcId)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT u.*,
                   i.id as instructor_id, i.name as instructor_name,
                   s.id as student_id, s.name as student_name, s.full_name as student_full_name,
                   GROUP_CONCAT(ur.role) as roles
            FROM usuarios u
            LEFT JOIN instructors i ON i.user_id = u.id
            LEFT JOIN students s ON s.user_id = u.id
            LEFT JOIN usuario_roles ur ON ur.usuario_id = u.id
            WHERE u.cfc_id = ?
            GROUP BY u.id
            ORDER BY u.nome ASC
        ");
        $stmt->execute([$cfcId]);
        return $stmt->fetchAll();
    }

    /**
     * Verifica se um aluno já tem usuário vinculado
     */
    public function hasStudentUser($studentId)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM students WHERE id = ? AND user_id IS NOT NULL");
        $stmt->execute([$studentId]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    /**
     * Verifica se um instrutor já tem usuário vinculado
     */
    public function hasInstructorUser($instructorId)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM instructors WHERE id = ? AND user_id IS NOT NULL");
        $stmt->execute([$instructorId]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    /**
     * Atualiza senha do usuário
     */
    public function updatePassword($userId, $hashedPassword)
    {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        return $stmt->execute([$hashedPassword, $userId]);
    }
}
