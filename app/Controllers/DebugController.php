<?php

namespace App\Controllers;

use App\Config\Database;

class DebugController extends Controller
{
    /**
     * Endpoint temporário de debug - APENAS LOCAL
     * 
     * Retorna JSON com informações de debug:
     * - Configuração do banco
     * - Banco atual em uso (SELECT DATABASE())
     * - Status do usuário admin
     */
    public function database()
    {
        // Verificar se está em ambiente local (segurança)
        $isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']) || 
                   strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;

        if (!$isLocal) {
            http_response_code(403);
            echo json_encode(['error' => 'Este endpoint só pode ser acessado em ambiente local']);
            return;
        }

        header('Content-Type: application/json');

        try {
            $db = Database::getInstance()->getConnection();

            // Configuração
            $config = [
                'DB_HOST' => $_ENV['DB_HOST'] ?? 'localhost',
                'DB_NAME' => $_ENV['DB_NAME'] ?? 'cfc_db',
                'DB_USER' => $_ENV['DB_USER'] ?? 'root',
                'DB_PASS' => isset($_ENV['DB_PASS']) ? '***' : '(vazio)'
            ];

            // Banco atual
            $stmt = $db->query("SELECT DATABASE() as current_db");
            $currentDb = $stmt->fetch();

            // Verificar admin
            $stmt = $db->prepare("SELECT id, email, password, status FROM usuarios WHERE email = ? LIMIT 1");
            $stmt->execute(['admin@cfc.local']);
            $admin = $stmt->fetch();

            $result = [
                'config' => $config,
                'current_database' => $currentDb['current_db'] ?? null,
                'database_match' => ($currentDb['current_db'] ?? null) === $config['DB_NAME'],
                'admin_exists' => $admin !== false,
                'admin' => $admin ? [
                    'id' => $admin['id'],
                    'email' => $admin['email'],
                    'status' => $admin['status'],
                    'password_hash' => $admin['password'],
                    'password_valid' => password_verify('admin123', $admin['password'])
                ] : null
            ];

            echo json_encode($result, JSON_PRETTY_PRINT);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
