<?php
/**
 * Script para Gerar Hash de Senha
 * 
 * ⚠️ APENAS PARA USO LOCAL/DEVELOPMENT
 * 
 * Gera um hash bcrypt para uma senha usando password_hash()
 * 
 * Uso: http://localhost/cfc-v.1/tools/generate_password_hash.php?password=admin123
 */

// Verificar se está em ambiente local (segurança)
$isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']) || 
           strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;

if (!$isLocal) {
    die('⚠️ Este script só pode ser executado em ambiente local!');
}

$password = $_GET['password'] ?? 'admin123';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerar Hash de Senha</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 700px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            word-break: break-all;
            font-size: 14px;
        }
        form {
            margin: 20px 0;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        button {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        button:hover {
            background: #0056b3;
        }
        .info {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <h1>🔑 Gerar Hash de Senha</h1>

    <div class="card">
        <form method="GET">
            <label for="password"><strong>Senha:</strong></label>
            <input type="text" id="password" name="password" value="<?= htmlspecialchars($password) ?>" placeholder="Digite a senha">
            <button type="submit">Gerar Hash</button>
        </form>
    </div>

    <?php if (isset($_GET['password'])): ?>
        <div class="card">
            <h2>Hash Gerado</h2>
            <div class="info">
                <strong>Senha:</strong> <?= htmlspecialchars($password) ?>
            </div>
            <pre><?= htmlspecialchars(password_hash($password, PASSWORD_DEFAULT)) ?></pre>
            
            <h3>SQL para atualizar no banco:</h3>
            <pre>UPDATE usuarios 
SET password = '<?= htmlspecialchars(password_hash($password, PASSWORD_DEFAULT)) ?>'
WHERE email = 'admin@cfc.local';</pre>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Como usar</h2>
        <ol>
            <li>Digite a senha desejada no campo acima</li>
            <li>Clique em "Gerar Hash"</li>
            <li>Copie o hash gerado</li>
            <li>Execute o SQL no banco de dados ou use o script <code>reset_admin_password.php</code></li>
        </ol>
    </div>

</body>
</html>
