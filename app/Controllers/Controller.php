<?php

namespace App\Controllers;

abstract class Controller
{
    protected function view($view, $data = [])
    {
        extract($data);
        $viewPath = APP_PATH . '/Views/' . str_replace('.', '/', $view) . '.php';
        
        if (!file_exists($viewPath)) {
            throw new \Exception("View não encontrada: {$view}");
        }

        // Se não usar layout (ex: login, forgot-password, reset-password), incluir view diretamente
        // change-password usa layout shell pois é acessada por usuário logado
        if (strpos($view, 'auth/') === 0 && $view !== 'auth/login' && $view !== 'auth/change-password') {
            include $viewPath;
            return;
        }

        // Usar layout shell
        $contentView = $viewPath;
        include APP_PATH . '/Views/layouts/shell.php';
    }
    
    protected function viewRaw($view, $data = [])
    {
        extract($data);
        $viewPath = APP_PATH . '/Views/' . str_replace('.', '/', $view) . '.php';
        
        if (!file_exists($viewPath)) {
            throw new \Exception("View não encontrada: {$view}");
        }

        include $viewPath;
    }

    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
