<?php

namespace App\Services;

use App\Models\User;
use App\Config\Constants;

class AuthService
{
    public function attempt($email, $password)
    {
        $user = User::findByEmail($email);
        
        if (!$user || !password_verify($password, $user['password'])) {
            return null;
        }

        if ($user['status'] !== 'ativo') {
            return null;
        }

        return $user;
    }

    public function login($user)
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['nome'];
        $_SESSION['cfc_id'] = $user['cfc_id'] ?? Constants::CFC_ID_DEFAULT;
        $_SESSION['must_change_password'] = !empty($user['must_change_password']) && $user['must_change_password'] == 1;
        
        // Definir papel inicial (primeiro papel do usuário ou o último usado)
        $roles = User::getUserRoles($user['id']);
        if (!empty($roles)) {
            $_SESSION['available_roles'] = $roles;
            $_SESSION['current_role'] = $_SESSION['last_role'] ?? $roles[0]['role'];
        } else {
            $_SESSION['current_role'] = Constants::ROLE_ALUNO;
        }
    }

    public function logout()
    {
        session_destroy();
        session_start();
    }

    public function switchRole($role)
    {
        if (empty($_SESSION['user_id'])) {
            return false;
        }

        $availableRoles = $_SESSION['available_roles'] ?? [];
        $roleExists = false;
        
        foreach ($availableRoles as $userRole) {
            if ($userRole['role'] === $role) {
                $roleExists = true;
                break;
            }
        }

        if ($roleExists) {
            $_SESSION['current_role'] = $role;
            $_SESSION['last_role'] = $role;
            return true;
        }

        return false;
    }
}
