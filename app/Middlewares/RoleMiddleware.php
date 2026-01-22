<?php

namespace App\Middlewares;

use App\Middlewares\MiddlewareInterface;

class RoleMiddleware implements MiddlewareInterface
{
    private $allowedRoles = [];

    public function __construct(...$roles)
    {
        $this->allowedRoles = $roles;
    }

    public function handle(): bool
    {
        if (empty($_SESSION['current_role'])) {
            header('Location: ' . base_url('login'));
            exit;
        }

        if (!in_array($_SESSION['current_role'], $this->allowedRoles)) {
            http_response_code(403);
            echo "Acesso negado";
            return false;
        }

        return true;
    }
}
