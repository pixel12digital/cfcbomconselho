<?php
/**
 * Script para executar migration: Tabela password_resets
 * Acesse: http://seu-dominio/admin/tools/executar-migration-password-resets.php
 * 
 * Este script cria a tabela password_resets para o sistema de recuperação de senha.
 * Pode ser executado múltiplas vezes com segurança (usa CREATE TABLE IF NOT EXISTS).
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar se está logado como admin (opcional, mas recomendado)
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasPermission('configuracoes')) {
    // Se não estiver logado, mostrar página mas com aviso
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
    <title>Migration: password_resets</title>
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
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        th {
            background: #1A365D;
            color: white;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #1A365D;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #2d4a6b;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Migration: Tabela password_resets</h1>
        
        <?php if (!$isAdmin): ?>
            <div class="warning">
                <strong>⚠️ Aviso:</strong> Você não está logado como administrador. A migration será executada mesmo assim, mas recomenda-se estar logado como admin para auditoria.
            </div>
        <?php endif; ?>
        
        <?php
        try {
            $db = db();
            
            echo "<div class='info'>";
            echo "<strong>📋 Informações da Migration:</strong><br>";
            echo "• Tabela: <code>password_resets</code><br>";
            echo "• Propósito: Armazenar tokens de recuperação de senha<br>";
            echo "• Segurança: Tokens armazenados como hash SHA256 (nunca em texto puro)<br>";
            echo "• Expiração: 30 minutos<br>";
            echo "• Uso único: Tokens não podem ser reutilizados<br>";
            echo "</div>";
            
            // Verificar se tabela já existe
            echo "<h2>🔍 Verificação Inicial</h2>";
            
            $tableExists = $db->fetch("
                SELECT COUNT(*) as count
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'password_resets'
            ");
            
            if ($tableExists['count'] > 0) {
                echo "<div class='info'>";
                echo "✅ A tabela <code>password_resets</code> já existe. Verificando estrutura...";
                echo "</div>";
                
                // Verificar estrutura da tabela
                $columns = $db->fetchAll("
                    SELECT 
                        COLUMN_NAME,
                        DATA_TYPE,
                        IS_NULLABLE,
                        COLUMN_DEFAULT,
                        COLUMN_COMMENT
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'password_resets'
                    ORDER BY ORDINAL_POSITION
                ");
                
                echo "<h3>Estrutura Atual da Tabela</h3>";
                echo "<table>";
                echo "<tr><th>Coluna</th><th>Tipo</th><th>Null?</th><th>Default</th><th>Comentário</th></tr>";
                foreach ($columns as $col) {
                    echo "<tr>";
                    echo "<td><strong>" . htmlspecialchars($col['COLUMN_NAME']) . "</strong></td>";
                    echo "<td>" . htmlspecialchars($col['DATA_TYPE']) . "</td>";
                    echo "<td>" . ($col['IS_NULLABLE'] === 'YES' ? 'SIM' : 'NÃO') . "</td>";
                    echo "<td>" . htmlspecialchars($col['COLUMN_DEFAULT'] ?? 'NULL') . "</td>";
                    echo "<td>" . htmlspecialchars($col['COLUMN_COMMENT'] ?? '') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                // Verificar índices
                $indexes = $db->fetchAll("
                    SELECT 
                        INDEX_NAME,
                        GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as columns
                    FROM INFORMATION_SCHEMA.STATISTICS
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'password_resets'
                    GROUP BY INDEX_NAME
                    ORDER BY INDEX_NAME
                ");
                
                if (!empty($indexes)) {
                    echo "<h3>Índices Existentes</h3>";
                    echo "<table>";
                    echo "<tr><th>Nome do Índice</th><th>Colunas</th></tr>";
                    foreach ($indexes as $idx) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($idx['INDEX_NAME']) . "</td>";
                        echo "<td>" . htmlspecialchars($idx['columns']) . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
                
                // Verificar se há dados
                $rowCount = $db->fetchColumn("SELECT COUNT(*) FROM password_resets", []);
                echo "<div class='info'>";
                echo "📊 Registros existentes na tabela: <strong>" . $rowCount . "</strong>";
                echo "</div>";
                
            } else {
                echo "<div class='warning'>";
                echo "⚠️ A tabela <code>password_resets</code> não existe. Criando agora...";
                echo "</div>";
                
                // Executar migration
                echo "<h2>🚀 Executando Migration</h2>";
                
                $sql = "
                CREATE TABLE IF NOT EXISTS password_resets (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    login VARCHAR(100) NOT NULL COMMENT 'Email ou CPF do usuário que solicitou recuperação',
                    token_hash VARCHAR(64) NOT NULL COMMENT 'Hash SHA256 do token (não armazenar token em texto puro)',
                    type ENUM('admin', 'secretaria', 'instrutor', 'aluno') NOT NULL COMMENT 'Tipo do usuário (apenas para auditoria/UI, não para permissão)',
                    ip VARCHAR(45) NOT NULL COMMENT 'IP de onde foi solicitado o reset',
                    expires_at TIMESTAMP NOT NULL COMMENT 'Data/hora de expiração do token (30 minutos após criação)',
                    used_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Data/hora em que o token foi usado (NULL = não usado)',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Data/hora de criação do token',
                    
                    INDEX idx_token_hash (token_hash),
                    INDEX idx_login (login),
                    INDEX idx_expires_at (expires_at),
                    INDEX idx_login_type (login, type),
                    INDEX idx_login_ip_created (login, ip, created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                COMMENT='Tabela para armazenar tokens de recuperação de senha';
                ";
                
                $db->query($sql);
                
                echo "<div class='success'>";
                echo "✅ <strong>Migration executada com sucesso!</strong><br>";
                echo "A tabela <code>password_resets</code> foi criada.";
                echo "</div>";
                
                // Verificar estrutura criada
                $columns = $db->fetchAll("
                    SELECT 
                        COLUMN_NAME,
                        DATA_TYPE,
                        IS_NULLABLE,
                        COLUMN_DEFAULT,
                        COLUMN_COMMENT
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'password_resets'
                    ORDER BY ORDINAL_POSITION
                ");
                
                echo "<h3>Estrutura Criada</h3>";
                echo "<table>";
                echo "<tr><th>Coluna</th><th>Tipo</th><th>Null?</th><th>Default</th><th>Comentário</th></tr>";
                foreach ($columns as $col) {
                    echo "<tr>";
                    echo "<td><strong>" . htmlspecialchars($col['COLUMN_NAME']) . "</strong></td>";
                    echo "<td>" . htmlspecialchars($col['DATA_TYPE']) . "</td>";
                    echo "<td>" . ($col['IS_NULLABLE'] === 'YES' ? 'SIM' : 'NÃO') . "</td>";
                    echo "<td>" . htmlspecialchars($col['COLUMN_DEFAULT'] ?? 'NULL') . "</td>";
                    echo "<td>" . htmlspecialchars($col['COLUMN_COMMENT'] ?? '') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
            echo "<div class='success'>";
            echo "<h3>✅ Migration Concluída</h3>";
            echo "<p>A tabela <code>password_resets</code> está pronta para uso pelo sistema de recuperação de senha.</p>";
            echo "<p><strong>Próximos passos:</strong></p>";
            echo "<ul>";
            echo "<li>✅ Testar o fluxo de recuperação de senha</li>";
            echo "<li>✅ Configurar SMTP em <code>includes/config.php</code> (se ainda não configurado)</li>";
            echo "<li>✅ Monitorar logs de <code>[PASSWORD_RESET_*]</code> para acompanhar uso</li>";
            echo "</ul>";
            echo "</div>";
            
            // Log de auditoria
            if (LOG_ENABLED) {
                $logMessage = sprintf(
                    '[MIGRATION] password_resets executada - IP: %s, User: %s, Timestamp: %s',
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
                error_log('[MIGRATION] Erro ao executar password_resets: ' . $e->getMessage());
            }
        }
        ?>
        
        <hr style="margin: 30px 0;">
        <p><a href="javascript:location.reload()" class="btn">🔄 Recarregar Página</a></p>
        <p><small>Este script pode ser executado múltiplas vezes com segurança (usa CREATE TABLE IF NOT EXISTS).</small></p>
    </div>
</body>
</html>
