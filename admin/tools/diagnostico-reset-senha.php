<?php
/**
 * Script de Diagnóstico Completo - Reset de Senha
 * Ajuda a identificar problemas no fluxo de reset de senha
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/PasswordReset.php';

// Verificar se é admin
$user = getCurrentUser();
if (!$user || $user['tipo'] !== 'admin') {
    die('Acesso negado. Apenas administradores podem executar este script.');
}

header('Content-Type: text/html; charset=UTF-8');

// Tratamento de erros
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não mostrar erros na tela, apenas logar
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (LOG_ENABLED) {
        error_log(sprintf('[DIAGNOSTICO_RESET] Erro: %s em %s:%d', $errstr, $errfile, $errline));
    }
    return false;
});

// Processar ações
$action = $_GET['action'] ?? 'diagnostico';
$token = $_GET['token'] ?? '';
$login = $_GET['login'] ?? '';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico - Reset de Senha</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1400px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        h2 {
            color: #34495e;
            margin-top: 30px;
            border-left: 4px solid #3498db;
            padding-left: 10px;
        }
        .success { color: #27ae60; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; }
        .warning { color: #f39c12; font-weight: bold; }
        .info { color: #3498db; }
        pre {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            max-height: 500px;
            overflow-y: auto;
        }
        .form-group {
            margin: 15px 0;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        input[type="text"], input[type="email"] {
            width: 100%;
            max-width: 500px;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        button {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin: 5px;
        }
        button:hover {
            background: #2980b9;
        }
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: none;
            background: transparent;
            color: #7f8c8d;
            font-weight: 600;
        }
        .tab.active {
            color: #3498db;
            border-bottom: 3px solid #3498db;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #ecf0f1;
            font-weight: 600;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico Completo - Reset de Senha</h1>
        
        <div class="tabs">
            <button class="tab active" onclick="showTab('diagnostico')">Diagnóstico Geral</button>
            <button class="tab" onclick="showTab('token')">Validar Token</button>
            <button class="tab" onclick="showTab('usuario')">Buscar Usuário</button>
            <button class="tab" onclick="showTab('logs')">Logs Recentes</button>
        </div>
        
        <!-- Tab: Diagnóstico Geral -->
        <div id="diagnostico" class="tab-content active">
            <h2>📊 Status do Sistema</h2>
            <?php
            $db = db();
            
            // Verificar tabela password_resets
            echo "<h3>1. Tabela password_resets</h3>";
            try {
                $tableExists = $db->fetch("SHOW TABLES LIKE 'password_resets'");
                if ($tableExists) {
                    echo "<p class='success'>✅ Tabela existe</p>";
                    
                    // Contar registros
                    $total = $db->fetch("SELECT COUNT(*) as total FROM password_resets")['total'];
                    $naoUsados = $db->fetch("SELECT COUNT(*) as total FROM password_resets WHERE used_at IS NULL")['total'];
                    $expirados = $db->fetch("SELECT COUNT(*) as total FROM password_resets WHERE expires_at < UTC_TIMESTAMP() AND used_at IS NULL")['total'];
                    $validos = $db->fetch("SELECT COUNT(*) as total FROM password_resets WHERE expires_at > UTC_TIMESTAMP() AND used_at IS NULL")['total'];
                    
                    echo "<table>";
                    echo "<tr><th>Total de registros</th><td>{$total}</td></tr>";
                    echo "<tr><th>Não usados</th><td>{$naoUsados}</td></tr>";
                    echo "<tr><th>Expirados (não usados)</th><td>{$expirados}</td></tr>";
                    echo "<tr><th>Válidos (não expirados, não usados)</th><td class='success'>{$validos}</td></tr>";
                    echo "</table>";
                    
                    // Últimos 5 registros
                    $recent = $db->fetchAll("SELECT id, login, type, created_at, expires_at, used_at FROM password_resets ORDER BY created_at DESC LIMIT 5");
                    if ($recent) {
                        echo "<h4>Últimos 5 registros:</h4>";
                        echo "<table>";
                        echo "<tr><th>ID</th><th>Login</th><th>Tipo</th><th>Criado</th><th>Expira</th><th>Usado</th><th>Status</th></tr>";
                        foreach ($recent as $r) {
                            $now = new DateTime('now', new DateTimeZone('UTC'));
                            $expires = new DateTime($r['expires_at'], new DateTimeZone('UTC'));
                            $used = $r['used_at'] ? new DateTime($r['used_at'], new DateTimeZone('UTC')) : null;
                            
                            $status = 'Válido';
                            $badge = 'badge-success';
                            if ($used) {
                                $status = 'Usado';
                                $badge = 'badge-warning';
                            } elseif ($expires < $now) {
                                $status = 'Expirado';
                                $badge = 'badge-danger';
                            }
                            
                            echo "<tr>";
                            echo "<td>{$r['id']}</td>";
                            echo "<td>" . htmlspecialchars($r['login']) . "</td>";
                            echo "<td>{$r['type']}</td>";
                            echo "<td>{$r['created_at']}</td>";
                            echo "<td>{$r['expires_at']}</td>";
                            echo "<td>" . ($r['used_at'] ?: 'NULL') . "</td>";
                            echo "<td><span class='badge {$badge}'>{$status}</span></td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    }
                } else {
                    echo "<p class='error'>❌ Tabela não existe</p>";
                }
            } catch (Exception $e) {
                echo "<p class='error'>❌ Erro ao verificar tabela: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            
            // Verificar configuração
            echo "<h3>2. Configuração</h3>";
            echo "<table>";
            echo "<tr><th>LOG_ENABLED</th><td>" . (LOG_ENABLED ? "<span class='success'>✅ true</span>" : "<span class='error'>❌ false</span>") . "</td></tr>";
            echo "<tr><th>Timezone PHP</th><td>" . date_default_timezone_get() . "</td></tr>";
            echo "<tr><th>Timezone MySQL</th><td>";
            try {
                $tz = $db->fetch("SELECT @@session.time_zone as tz");
                echo $tz['tz'] ?? 'N/A';
            } catch (Exception $e) {
                echo 'Erro: ' . htmlspecialchars($e->getMessage());
            }
            echo "</td></tr>";
            echo "<tr><th>Data/Hora PHP (local)</th><td>" . date('Y-m-d H:i:s') . "</td></tr>";
            echo "<tr><th>Data/Hora PHP (UTC)</th><td>" . gmdate('Y-m-d H:i:s') . "</td></tr>";
            echo "<tr><th>Data/Hora MySQL (UTC)</th><td>";
            try {
                $now = $db->fetch("SELECT UTC_TIMESTAMP() as now");
                echo $now['now'] ?? 'N/A';
            } catch (Exception $e) {
                echo 'Erro: ' . htmlspecialchars($e->getMessage());
            }
            echo "</td></tr>";
            echo "</table>";
            
            // Verificar estrutura da tabela usuarios
            echo "<h3>3. Estrutura da tabela usuarios</h3>";
            try {
                $columns = $db->fetchAll("SHOW COLUMNS FROM usuarios WHERE Field IN ('id', 'email', 'cpf', 'senha', 'tipo', 'ativo')");
                if ($columns) {
                    echo "<table>";
                    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th></tr>";
                    foreach ($columns as $col) {
                        echo "<tr>";
                        echo "<td>{$col['Field']}</td>";
                        echo "<td>{$col['Type']}</td>";
                        echo "<td>{$col['Null']}</td>";
                        echo "<td>{$col['Key']}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            } catch (Exception $e) {
                echo "<p class='error'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            ?>
        </div>
        
        <!-- Tab: Validar Token -->
        <div id="token" class="tab-content">
            <h2>🔑 Validar Token</h2>
            <form method="GET" action="">
                <input type="hidden" name="action" value="validar_token">
                <div class="form-group">
                    <label>Token (cole o token completo do email):</label>
                    <input type="text" name="token" value="<?php echo htmlspecialchars($token); ?>" placeholder="Cole o token aqui..." required>
                </div>
                <button type="submit">Validar Token</button>
            </form>
            
            <?php
            if ($action === 'validar_token' && !empty($token)) {
                echo "<h3>Resultado da Validação</h3>";
                
                // Informações críticas do token
                $tokenLength = strlen($token);
                $tokenPreview = substr($token, 0, 6) . '...';
                $tokenHash = hash('sha256', $token);
                
                echo "<h4>📊 Informações do Token</h4>";
                echo "<table>";
                echo "<tr><th>Campo</th><th>Valor</th></tr>";
                echo "<tr><td><strong>token_length</strong></td><td><strong>{$tokenLength}</strong> caracteres</td></tr>";
                echo "<tr><td>token_preview</td><td><code>{$tokenPreview}</code></td></tr>";
                echo "<tr><td>token_hash (SHA256)</td><td><code>" . substr($tokenHash, 0, 16) . "...</code></td></tr>";
                echo "</table>";
                
                // Comparação de timezone
                $db = db();
                $phpTime = date('Y-m-d H:i:s');
                $phpTimeUtc = gmdate('Y-m-d H:i:s');
                $mysqlNow = $db->fetch("SELECT NOW() as now, UTC_TIMESTAMP() as utc_now");
                $mysqlNowLocal = $mysqlNow['now'] ?? 'N/A';
                $mysqlNowUtc = $mysqlNow['utc_now'] ?? 'N/A';
                
                echo "<h4>🕐 Comparação de Timezone</h4>";
                echo "<table>";
                echo "<tr><th>Origem</th><th>Data/Hora</th></tr>";
                echo "<tr><td>PHP (local)</td><td>{$phpTime}</td></tr>";
                echo "<tr><td>PHP (UTC)</td><td>{$phpTimeUtc}</td></tr>";
                echo "<tr><td>MySQL NOW()</td><td>{$mysqlNowLocal}</td></tr>";
                echo "<tr><td>MySQL UTC_TIMESTAMP()</td><td>{$mysqlNowUtc}</td></tr>";
                echo "<tr><td>PHP timezone</td><td>" . date_default_timezone_get() . "</td></tr>";
                echo "</table>";
                
                // Validar token
                $validation = PasswordReset::validateToken($token);
                
                echo "<h4>✅ Validação do Token</h4>";
                echo "<table>";
                echo "<tr><th>Campo</th><th>Valor</th></tr>";
                echo "<tr><td><strong>Token válido</strong></td><td>" . ($validation['valid'] ? "<span class='success'>✅ SIM</span>" : "<span class='error'>❌ NÃO</span>") . "</td></tr>";
                echo "<tr><td>Reset ID</td><td>" . ($validation['reset_id'] ?? 'N/A') . "</td></tr>";
                echo "<tr><td>Login</td><td>" . htmlspecialchars($validation['login'] ?? 'N/A') . "</td></tr>";
                echo "<tr><td>Tipo</td><td>" . htmlspecialchars($validation['type'] ?? 'N/A') . "</td></tr>";
                echo "<tr><td>Motivo (se inválido)</td><td>" . htmlspecialchars($validation['reason'] ?? 'N/A') . "</td></tr>";
                echo "</table>";
                
                if ($validation['valid']) {
                    // Buscar detalhes do registro
                    $db = db();
                    $tokenHash = hash('sha256', $token);
                    $reset = $db->fetch(
                        "SELECT * FROM password_resets WHERE token_hash = :hash LIMIT 1",
                        ['hash' => $tokenHash]
                    );
                    
                    if ($reset) {
                        echo "<h4>📋 Detalhes do Registro no Banco</h4>";
                        echo "<table>";
                        echo "<tr><th>Campo</th><th>Valor</th></tr>";
                        echo "<tr><td><strong>Token encontrado?</strong></td><td><span class='success'>✅ SIM</span></td></tr>";
                        echo "<tr><td>ID</td><td>{$reset['id']}</td></tr>";
                        echo "<tr><td>Login</td><td>" . htmlspecialchars($reset['login']) . "</td></tr>";
                        echo "<tr><td>Tipo</td><td>{$reset['type']}</td></tr>";
                        echo "<tr><td>Criado em</td><td>{$reset['created_at']}</td></tr>";
                        echo "<tr><td><strong>Expira em (expires_at)</strong></td><td><strong>{$reset['expires_at']}</strong></td></tr>";
                        echo "<tr><td><strong>Usado em (used_at)</strong></td><td><strong>" . ($reset['used_at'] ?: 'NULL') . "</strong></td></tr>";
                        
                        // Verificar se está expirado
                        try {
                            // Garantir que temos o timezone correto
                            if (empty($mysqlNowUtc)) {
                                $mysqlNowUtc = $db->fetch("SELECT UTC_TIMESTAMP() as utc_now")['utc_now'] ?? gmdate('Y-m-d H:i:s');
                            }
                            $nowUtc = new DateTime($mysqlNowUtc, new DateTimeZone('UTC'));
                            $expiresUtc = new DateTime($reset['expires_at'], new DateTimeZone('UTC'));
                            $isExpired = $expiresUtc < $nowUtc;
                        } catch (Exception $e) {
                            // Fallback se DateTime falhar
                            $isExpired = strtotime($reset['expires_at']) < time();
                        }
                        $isUsed = !empty($reset['used_at']);
                        
                        echo "<tr><td><strong>Status</strong></td><td>";
                        if ($isUsed) {
                            echo "<span class='error'>❌ JÁ USADO</span>";
                        } elseif ($isExpired) {
                            echo "<span class='error'>❌ EXPIRADO</span>";
                        } else {
                            echo "<span class='success'>✅ VÁLIDO</span>";
                        }
                        echo "</td></tr>";
                        echo "</table>";
                        
                        // Verificar se usuário existe (busca manual)
                        $usuario = null;
                        $loginBusca = $reset['login'];
                        $typeBusca = $reset['type'];
                        
                        if ($typeBusca === 'aluno') {
                            $cpfLimpo = preg_replace('/[^0-9]/', '', trim($loginBusca));
                            $isEmail = filter_var($loginBusca, FILTER_VALIDATE_EMAIL);
                            
                            if ($isEmail) {
                                $usuario = $db->fetch(
                                    "SELECT id, email, cpf, tipo FROM usuarios WHERE email = :email AND tipo = 'aluno' AND ativo = 1 LIMIT 1",
                                    ['email' => $loginBusca]
                                );
                            } elseif (!empty($cpfLimpo) && strlen($cpfLimpo) === 11) {
                                $usuario = $db->fetch(
                                    "SELECT id, email, cpf, tipo FROM usuarios 
                                     WHERE REPLACE(REPLACE(cpf, '.', ''), '-', '') = :cpf 
                                     AND tipo = 'aluno' 
                                     AND ativo = 1 
                                     LIMIT 1",
                                    ['cpf' => $cpfLimpo]
                                );
                            }
                        } else {
                            $usuario = $db->fetch(
                                "SELECT id, email, tipo FROM usuarios WHERE email = :email AND tipo = :type AND ativo = 1 LIMIT 1",
                                ['email' => $loginBusca, 'type' => $typeBusca]
                            );
                        }
                        
                        if ($usuario) {
                            echo "<h4 class='success'>✅ Usuário encontrado</h4>";
                            echo "<table>";
                            echo "<tr><th>Campo</th><th>Valor</th></tr>";
                            echo "<tr><td><strong>user_found</strong></td><td><span class='success'>✅ SIM</span></td></tr>";
                            echo "<tr><td><strong>user_id</strong></td><td><strong>{$usuario['id']}</strong></td></tr>";
                            echo "<tr><td>user_tipo</td><td>{$usuario['tipo']}</td></tr>";
                            echo "<tr><td>user_email</td><td>" . htmlspecialchars($usuario['email'] ?? 'N/A') . "</td></tr>";
                            echo "<tr><td>user_cpf</td><td>" . htmlspecialchars($usuario['cpf'] ?? 'N/A') . "</td></tr>";
                            
                            // Verificar se está ativo
                            $userAtivo = $db->fetch("SELECT ativo FROM usuarios WHERE id = :id", ['id' => $usuario['id']]);
                            $ativo = $userAtivo['ativo'] ?? null;
                            echo "<tr><td><strong>user_ativo</strong></td><td><strong>" . ($ativo ? 'SIM (1)' : 'NÃO (0)') . "</strong></td></tr>";
                            
                            // Verificar schema da coluna senha
                            $senhaColumn = $db->fetch("SHOW COLUMNS FROM usuarios WHERE Field = 'senha'");
                            if ($senhaColumn) {
                                echo "<tr><td colspan='2'><strong>Schema da coluna senha:</strong></td></tr>";
                                echo "<tr><td>Field</td><td>{$senhaColumn['Field']}</td></tr>";
                                echo "<tr><td>Type</td><td>{$senhaColumn['Type']}</td></tr>";
                                echo "<tr><td>Null</td><td>{$senhaColumn['Null']}</td></tr>";
                                echo "<tr><td>Key</td><td>{$senhaColumn['Key']}</td></tr>";
                                echo "<tr><td>Default</td><td>" . ($senhaColumn['Default'] ?? 'NULL') . "</td></tr>";
                            }
                            
                            // Verificar senha atual (apenas hash, não o valor)
                            $senhaAtual = $db->fetch("SELECT senha FROM usuarios WHERE id = :id", ['id' => $usuario['id']]);
                            $senhaHashAtual = $senhaAtual['senha'] ?? null;
                            if ($senhaHashAtual) {
                                echo "<tr><td>senha_hash_atual (primeiros 20)</td><td><code>" . substr($senhaHashAtual, 0, 20) . "...</code> (len=" . strlen($senhaHashAtual) . ")</td></tr>";
                            }
                            
                            echo "</table>";
                        } else {
                            echo "<h4 class='error'>❌ Usuário NÃO encontrado</h4>";
                            echo "<table>";
                            echo "<tr><th>Campo</th><th>Valor</th></tr>";
                            echo "<tr><td><strong>user_found</strong></td><td><span class='error'>❌ NÃO</span></td></tr>";
                            echo "<tr><td>login_buscado</td><td>" . htmlspecialchars($reset['login']) . "</td></tr>";
                            echo "<tr><td>type_buscado</td><td>" . htmlspecialchars($reset['type']) . "</td></tr>";
                            echo "</table>";
                        }
                    }
                } else {
                    // Verificar se token existe mas está inválido
                    $db = db();
                    $tokenHash = hash('sha256', $token);
                    $reset = $db->fetch(
                        "SELECT * FROM password_resets WHERE token_hash = :hash LIMIT 1",
                        ['hash' => $tokenHash]
                    );
                    
                    if ($reset) {
                        echo "<h4 class='warning'>⚠️ Token encontrado no banco, mas inválido:</h4>";
                        echo "<table>";
                        echo "<tr><th>Campo</th><th>Valor</th></tr>";
                        echo "<tr><td>ID</td><td>{$reset['id']}</td></tr>";
                        echo "<tr><td>Login</td><td>" . htmlspecialchars($reset['login']) . "</td></tr>";
                        echo "<tr><td>Tipo</td><td>{$reset['type']}</td></tr>";
                        echo "<tr><td>Criado em</td><td>{$reset['created_at']}</td></tr>";
                        echo "<tr><td>Expira em</td><td>{$reset['expires_at']}</td></tr>";
                        echo "<tr><td>Usado em</td><td>" . ($reset['used_at'] ?: 'NULL') . "</td></tr>";
                        
                        // Verificar motivo da invalidade
                        try {
                            $now = new DateTime('now', new DateTimeZone('UTC'));
                            $expires = new DateTime($reset['expires_at'], new DateTimeZone('UTC'));
                            
                            if ($reset['used_at']) {
                                echo "<tr><td>Motivo</td><td class='error'>Token já foi usado</td></tr>";
                            } elseif ($expires < $now) {
                                $diff = $now->diff($expires);
                                echo "<tr><td>Motivo</td><td class='error'>Token expirado há " . $diff->format('%a dias, %h horas, %i minutos') . "</td></tr>";
                            } else {
                                echo "<tr><td>Motivo</td><td class='warning'>Token válido (verificar código)</td></tr>";
                            }
                        } catch (Exception $e) {
                            // Fallback se DateTime falhar
                            if ($reset['used_at']) {
                                echo "<tr><td>Motivo</td><td class='error'>Token já foi usado</td></tr>";
                            } elseif (strtotime($reset['expires_at']) < time()) {
                                echo "<tr><td>Motivo</td><td class='error'>Token expirado</td></tr>";
                            } else {
                                echo "<tr><td>Motivo</td><td class='warning'>Token válido (verificar código)</td></tr>";
                            }
                        }
                        echo "</table>";
                    } else {
                        echo "<p class='error'>❌ Token não encontrado no banco de dados.</p>";
                        echo "<p>Isso pode significar que:</p>";
                        echo "<ul>";
                        echo "<li>O token foi digitado incorretamente</li>";
                        echo "<li>O token foi truncado (alguns clientes de email quebram URLs longas)</li>";
                        echo "<li>O token nunca foi gerado</li>";
                        echo "</ul>";
                    }
                }
            }
            ?>
        </div>
        
        <!-- Tab: Buscar Usuário -->
        <div id="usuario" class="tab-content">
            <h2>👤 Buscar Usuário</h2>
            <form method="GET" action="">
                <input type="hidden" name="action" value="buscar_usuario">
                <div class="form-group">
                    <label>Login (Email ou CPF):</label>
                    <input type="text" name="login" value="<?php echo htmlspecialchars($login); ?>" placeholder="Digite email ou CPF..." required>
                </div>
                <div class="form-group">
                    <label>Tipo:</label>
                    <select name="type" style="padding: 10px; border: 2px solid #ddd; border-radius: 4px;">
                        <option value="aluno">Aluno</option>
                        <option value="admin">Admin</option>
                        <option value="secretaria">Secretaria</option>
                        <option value="instrutor">Instrutor</option>
                    </select>
                </div>
                <button type="submit">Buscar</button>
            </form>
            
            <?php
            if ($action === 'buscar_usuario' && !empty($login)) {
                $type = $_GET['type'] ?? 'aluno';
                $db = db();
                
                echo "<h3>Resultado da Busca</h3>";
                
                // Buscar usuário manualmente (mesma lógica do PasswordReset)
                $usuario = null;
                if ($type === 'aluno') {
                    $cpfLimpo = preg_replace('/[^0-9]/', '', trim($login));
                    $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);
                    
                    if ($isEmail) {
                        $usuario = $db->fetch(
                            "SELECT id, email, cpf, tipo FROM usuarios WHERE email = :email AND tipo = 'aluno' AND ativo = 1 LIMIT 1",
                            ['email' => $login]
                        );
                    } elseif (!empty($cpfLimpo) && strlen($cpfLimpo) === 11) {
                        $usuario = $db->fetch(
                            "SELECT id, email, cpf, tipo FROM usuarios 
                             WHERE REPLACE(REPLACE(cpf, '.', ''), '-', '') = :cpf 
                             AND tipo = 'aluno' 
                             AND ativo = 1 
                             LIMIT 1",
                            ['cpf' => $cpfLimpo]
                        );
                    }
                } else {
                    $usuario = $db->fetch(
                        "SELECT id, email, tipo FROM usuarios WHERE email = :email AND tipo = :type AND ativo = 1 LIMIT 1",
                        ['email' => $login, 'type' => $type]
                    );
                }
                
                if ($usuario) {
                    echo "<p class='success'>✅ Usuário encontrado!</p>";
                    echo "<pre>";
                    print_r($usuario);
                    echo "</pre>";
                    
                    // Verificar se tem email válido
                    $email = $usuario['email'] ?? null;
                    $hasEmail = !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
                    echo "<p>Email válido: " . ($hasEmail ? "<span class='success'>✅ Sim ({$email})</span>" : "<span class='error'>❌ Não</span>") . "</p>";
                    
                    // Verificar tokens ativos
                    $tokens = $db->fetchAll(
                        "SELECT id, created_at, expires_at, used_at FROM password_resets WHERE login = :login ORDER BY created_at DESC LIMIT 5",
                        ['login' => $login]
                    );
                    
                    if ($tokens) {
                        echo "<h4>Últimos tokens gerados para este login:</h4>";
                        echo "<table>";
                        echo "<tr><th>ID</th><th>Criado</th><th>Expira</th><th>Usado</th><th>Status</th></tr>";
                        foreach ($tokens as $t) {
                            $now = new DateTime('now', new DateTimeZone('UTC'));
                            $expires = new DateTime($t['expires_at'], new DateTimeZone('UTC'));
                            $used = $t['used_at'] ? new DateTime($t['used_at'], new DateTimeZone('UTC')) : null;
                            
                            $status = 'Válido';
                            $badge = 'badge-success';
                            if ($used) {
                                $status = 'Usado';
                                $badge = 'badge-warning';
                            } elseif ($expires < $now) {
                                $status = 'Expirado';
                                $badge = 'badge-danger';
                            }
                            
                            echo "<tr>";
                            echo "<td>{$t['id']}</td>";
                            echo "<td>{$t['created_at']}</td>";
                            echo "<td>{$t['expires_at']}</td>";
                            echo "<td>" . ($t['used_at'] ?: 'NULL') . "</td>";
                            echo "<td><span class='badge {$badge}'>{$status}</span></td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    }
                } else {
                    echo "<p class='error'>❌ Usuário NÃO encontrado com login: " . htmlspecialchars($login) . ", tipo: " . htmlspecialchars($type) . "</p>";
                }
            }
            ?>
        </div>
        
        <!-- Tab: Logs Recentes -->
        <div id="logs" class="tab-content">
            <h2>📋 Logs Recentes</h2>
            <?php
            $logPath = __DIR__ . '/../logs/php_errors.log';
            if (file_exists($logPath)) {
                $lines = file($logPath);
                if ($lines) {
                    // Filtrar linhas relacionadas a reset de senha
                    $filteredLines = [];
                    foreach ($lines as $lineNum => $line) {
                        if (stripos($line, 'PASSWORD_RESET') !== false || 
                            stripos($line, 'RESET_PASSWORD') !== false ||
                            stripos($line, 'MAILER') !== false) {
                            $filteredLines[] = [
                                'line' => $lineNum + 1,
                                'content' => $line
                            ];
                        }
                    }
                    
                    // Mostrar últimas 100 entradas
                    $filteredLines = array_slice($filteredLines, -100);
                    
                    if (empty($filteredLines)) {
                        echo "<p class='info'>ℹ️ Nenhuma entrada encontrada.</p>";
                    } else {
                        echo "<p>Encontradas " . count($filteredLines) . " entradas relacionadas (mostrando últimas 100):</p>";
                        echo "<pre>";
                        foreach ($filteredLines as $entry) {
                            echo htmlspecialchars($entry['content']);
                        }
                        echo "</pre>";
                    }
                } else {
                    echo "<p class='error'>❌ Erro ao ler arquivo de log.</p>";
                }
            } else {
                echo "<p class='error'>❌ Arquivo de log não encontrado: " . htmlspecialchars($logPath) . "</p>";
            }
            ?>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Esconder todas as tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Mostrar tab selecionada
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
