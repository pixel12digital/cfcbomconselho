<?php

namespace App\Middlewares;

use App\Middlewares\MiddlewareInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(): bool
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        // Headers anti-cache para páginas autenticadas (segurança PWA)
        // Previne cache de HTML com dados sensíveis
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        return true;
    }
}
