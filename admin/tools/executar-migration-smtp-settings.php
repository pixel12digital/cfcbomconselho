<?php
/**
 * Script para executar migration: Tabela smtp_settings
 * Acesse: http://seu-dominio/admin/tools/executar-migration-smtp-settings.php
 * 
 * Este script cria a tabela smtp_settings para configurações SMTP do painel admin.
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar se está logado como admin (opcional, mas recomendado)
$auth = new Auth();
if (!$auth->isLoggedIn() || !hasPermission('admin')) {
    $isAdmin = false;
} else {
    $isAdmin = true;
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration: smtp_settings</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1A365D;
            border-bottom: 3px solid #1A365D;
            padding-bottom: 10px;
        }
        .success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Migration: Tabela smtp_settings</h1>
        
        <?php
        try {
            $db = db();
            
            echo "<div class='info'>";
            echo "<strong>📋 Informações da Migration:</strong><br>";
            echo "• Tabela: <code>smtp_settings</code><br>";
            echo "• Propósito: Armazenar configurações SMTP configuráveis pelo painel admin<br>";
            echo "• Segurança: Senha SMTP armazenada criptografada (AES-256-CBC)<br>";
            echo "</div>";
            
            // Ler arquivo SQL
            $sqlFile = __DIR__ . '/../../docs/scripts/migration-smtp-settings.sql';
            if (!file_exists($sqlFile)) {
                throw new Exception("Arquivo SQL não encontrado: $sqlFile");
            }
            
            $sql = file_get_contents($sqlFile);
            
            // Verificar se tabela já existe
            $tableExists = $db->fetch("
                SELECT COUNT(*) as count
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'smtp_settings'
            ");
            
            if ($tableExists['count'] > 0) {
                echo "<div class='info'>";
                echo "✅ A tabela <code>smtp_settings</code> já existe.";
                echo "</div>";
            } else {
                echo "<div class='info'>";
                echo "⚠️ A tabela <code>smtp_settings</code> não existe. Criando agora...";
                echo "</div>";
                
                // Executar migration
                $db->query($sql);
                
                echo "<div class='success'>";
                echo "✅ <strong>Migration executada com sucesso!</strong><br>";
                echo "A tabela <code>smtp_settings</code> foi criada.";
                echo "</div>";
            }
            
            // Verificar estrutura
            $columns = $db->fetchAll("
                SELECT 
                    COLUMN_NAME,
                    DATA_TYPE,
                    IS_NULLABLE,
                    COLUMN_DEFAULT
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'smtp_settings'
                ORDER BY ORDINAL_POSITION
            ");
            
            if (!empty($columns)) {
                echo "<div class='info'>";
                echo "<strong>Estrutura da Tabela:</strong><br>";
                echo "<pre>";
                foreach ($columns as $col) {
                    echo htmlspecialchars($col['COLUMN_NAME']) . " - " . 
                         htmlspecialchars($col['DATA_TYPE']) . " (" . 
                         ($col['IS_NULLABLE'] === 'YES' ? 'NULL' : 'NOT NULL') . ")\n";
                }
                echo "</pre>";
                echo "</div>";
            }
            
            // Log de auditoria
            if (LOG_ENABLED) {
                $logMessage = sprintf(
                    '[MIGRATION] smtp_settings executada - IP: %s, User: %s, Timestamp: %s',
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    $isAdmin ? ($auth->getCurrentUser()['email'] ?? 'admin') : 'anonymous',
                    date('Y-m-d H:i:s')
                );
                error_log($logMessage);
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>";
            echo "<strong>❌ ERRO ao executar migration:</strong><br>";
            echo htmlspecialchars($e->getMessage());
            echo "</div>";
            
            if (LOG_ENABLED) {
                error_log('[MIGRATION] Erro ao executar smtp_settings: ' . $e->getMessage());
            }
        }
        ?>
        
        <hr style="margin: 30px 0;">
        <p><a href="javascript:location.reload()" class="btn" style="display: inline-block; padding: 10px 20px; background: #1A365D; color: white; text-decoration: none; border-radius: 4px;">🔄 Recarregar Página</a></p>
        <p><small>Este script pode ser executado múltiplas vezes com segurança (usa CREATE TABLE IF NOT EXISTS).</small></p>
    </div>
</body>
</html>
