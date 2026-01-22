<?php
/**
 * Script para verificar se as alterações de usuário estão sendo salvas no banco
 * Acesse via: /tools/verificar_usuario.php?id=1
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

require_once APP_PATH . '/autoload.php';

use App\Config\Database;
use App\Config\Env;

// Carregar variáveis de ambiente
Env::load();

header('Content-Type: text/html; charset=utf-8');

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 1;

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Verificar Usuário - ID {$userId}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #007bff; color: white; }
        tr:hover { background-color: #f5f5f5; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { background: #e7f3ff; padding: 10px; border-radius: 4px; margin: 10px 0; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔍 Verificação de Usuário - ID {$userId}</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Buscar dados do usuário
    echo "<h2>1. Dados do Usuário na Tabela 'usuarios'</h2>";
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<table>
            <tr><th>Campo</th><th>Valor</th></tr>
            <tr><td><strong>ID</strong></td><td>{$user['id']}</td></tr>
            <tr><td><strong>CFC ID</strong></td><td>{$user['cfc_id']}</td></tr>
            <tr><td><strong>Nome</strong></td><td>" . htmlspecialchars($user['nome'] ?? 'N/A') . "</td></tr>
            <tr><td><strong>E-mail</strong></td><td><code>" . htmlspecialchars($user['email'] ?? 'N/A') . "</code></td></tr>
            <tr><td><strong>Status</strong></td><td><code>" . htmlspecialchars($user['status'] ?? 'N/A') . "</code></td></tr>
            <tr><td><strong>Must Change Password</strong></td><td>" . ($user['must_change_password'] ?? 0) . "</td></tr>
            <tr><td><strong>Created At</strong></td><td>" . ($user['created_at'] ?? 'N/A') . "</td></tr>
            <tr><td><strong>Updated At</strong></td><td>" . ($user['updated_at'] ?? 'N/A') . "</td></tr>
        </table>";
    } else {
        echo "<p class='error'>❌ Usuário não encontrado!</p>";
    }
    
    // 2. Buscar roles do usuário
    echo "<h2>2. Roles do Usuário na Tabela 'usuario_roles'</h2>";
    $stmt = $db->prepare("SELECT ur.*, r.nome as role_nome FROM usuario_roles ur LEFT JOIN roles r ON r.role = ur.role WHERE ur.usuario_id = ?");
    $stmt->execute([$userId]);
    $roles = $stmt->fetchAll();
    
    if ($roles) {
        echo "<table>
            <tr><th>ID</th><th>Usuario ID</th><th>Role</th><th>Role Nome</th><th>Created At</th></tr>";
        foreach ($roles as $role) {
            echo "<tr>
                <td>{$role['id']}</td>
                <td>{$role['usuario_id']}</td>
                <td><code>{$role['role']}</code></td>
                <td>" . htmlspecialchars($role['role_nome'] ?? 'N/A') . "</td>
                <td>" . ($role['created_at'] ?? 'N/A') . "</td>
            </tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ Nenhum role encontrado para este usuário!</p>";
    }
    
    // 3. Verificar histórico de auditoria
    echo "<h2>3. Histórico de Auditoria (últimas 5 alterações)</h2>";
    $stmt = $db->prepare("
        SELECT * FROM audit_logs 
        WHERE table_name = 'usuarios' AND record_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $auditLogs = $stmt->fetchAll();
    
    if ($auditLogs) {
        echo "<table>
            <tr><th>ID</th><th>Ação</th><th>Usuário</th><th>Dados Antes</th><th>Dados Depois</th><th>Data</th></tr>";
        foreach ($auditLogs as $log) {
            $dataBefore = json_decode($log['data_before'] ?? '{}', true);
            $dataAfter = json_decode($log['data_after'] ?? '{}', true);
            
            echo "<tr>
                <td>{$log['id']}</td>
                <td><code>{$log['action']}</code></td>
                <td>{$log['user_id']}</td>
                <td><pre style='max-width: 300px; overflow: auto; font-size: 11px;'>" . htmlspecialchars(json_encode($dataBefore, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre></td>
                <td><pre style='max-width: 300px; overflow: auto; font-size: 11px;'>" . htmlspecialchars(json_encode($dataAfter, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre></td>
                <td>{$log['created_at']}</td>
            </tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='info'>ℹ️ Nenhum registro de auditoria encontrado.</p>";
    }
    
    // 4. Verificar logs do PHP
    echo "<h2>4. Últimas Entradas nos Logs do PHP</h2>";
    $logFile = ROOT_PATH . '/storage/logs/php_errors.log';
    if (file_exists($logFile)) {
        $lines = file($logFile);
        $relevantLines = [];
        foreach (array_reverse($lines) as $line) {
            if (stripos($line, 'USUARIOS_ATUALIZAR') !== false || 
                stripos($line, 'USUARIOS_EDITAR') !== false) {
                $relevantLines[] = $line;
                if (count($relevantLines) >= 20) break;
            }
        }
        
        if ($relevantLines) {
            echo "<div class='info'><pre style='max-height: 400px; overflow: auto; background: #f4f4f4; padding: 15px; border-radius: 4px;'>";
            foreach (array_reverse($relevantLines) as $line) {
                echo htmlspecialchars($line);
            }
            echo "</pre></div>";
        } else {
            echo "<p class='info'>ℹ️ Nenhuma entrada relevante encontrada nos logs.</p>";
        }
    } else {
        echo "<p class='error'>❌ Arquivo de log não encontrado: {$logFile}</p>";
    }
    
    // 5. Teste de atualização direta
    echo "<h2>5. Teste de Atualização Direta</h2>";
    echo "<div class='info'>
        <p><strong>Para testar se o problema é no código ou no banco:</strong></p>
        <p>Execute este SQL diretamente no banco de dados:</p>
        <pre style='background: #f4f4f4; padding: 10px; border-radius: 4px;'>
UPDATE usuarios 
SET email = 'teste@exemplo.com', status = 'ativo' 
WHERE id = {$userId};

SELECT * FROM usuarios WHERE id = {$userId};
        </pre>
        <p>Se o UPDATE funcionar, o problema está no código PHP. Se não funcionar, o problema está no banco de dados.</p>
    </div>";
    
} catch (\Exception $e) {
    echo "<p class='error'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</div></body></html>";
?>
