<?php
/**
 * Script Simples para Gerar Hash de Senha
 * 
 * Este script gera o hash bcrypt para uma senha e mostra o comando SQL
 * para atualizar no banco de dados remoto.
 * 
 * NÃO REQUER AUTENTICAÇÃO - apenas gera o hash
 */

$email = 'carlosteste@teste.com.br';
$senhaPlana = 'Los@ngo#081081';
$nome = 'Carlos da Silva';
$tipo = 'instrutor';

// Gerar hash bcrypt
$hash = password_hash($senhaPlana, PASSWORD_DEFAULT);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerar Hash de Senha</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
            max-width: 900px;
            margin: 0 auto;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        .info {
            background: #e7f3ff;
            padding: 15px;
            border-left: 4px solid #007bff;
            margin: 15px 0;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            padding: 15px;
            border-left: 4px solid #28a745;
            margin: 15px 0;
            border-radius: 5px;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border: 1px solid #dee2e6;
            font-size: 14px;
            line-height: 1.5;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background: #218838;
        }
        .btn-copy {
            background: #007bff;
        }
        .btn-copy:hover {
            background: #0056b3;
        }
        .warning {
            background: #fff3cd;
            padding: 15px;
            border-left: 4px solid #ffc107;
            margin: 15px 0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔑 Gerar Hash de Senha</h1>
        
        <div class="info">
            <strong>📧 Email:</strong> <?php echo htmlspecialchars($email); ?><br>
            <strong>🔑 Senha:</strong> <?php echo htmlspecialchars($senhaPlana); ?>
        </div>
        
        <div class="success">
            <strong>✅ Hash gerado com sucesso!</strong>
        </div>
        
        <h2>Hash Gerado:</h2>
        <pre id="hash"><?php echo htmlspecialchars($hash); ?></pre>
        <button class="btn btn-copy" onclick="copiarHash()">📋 Copiar Hash</button>
        
        <h2>Comando SQL para Criar/Atualizar no phpMyAdmin:</h2>
        <div class="warning">
            <strong>⚠️ ATENÇÃO:</strong> Execute este comando no phpMyAdmin do banco remoto!<br>
            <strong>Se o usuário não existir:</strong> Use o comando INSERT abaixo.<br>
            <strong>Se o usuário já existir:</strong> Use o comando UPDATE abaixo.
        </div>
        
        <h3>Opção 1: Criar Novo Usuário (INSERT)</h3>
        <pre id="sql-insert">INSERT INTO usuarios (
    nome,
    email,
    senha,
    tipo,
    ativo,
    criado_em
) VALUES (
    '<?php echo htmlspecialchars($nome); ?>',
    '<?php echo htmlspecialchars($email); ?>',
    '<?php echo htmlspecialchars($hash); ?>',
    '<?php echo htmlspecialchars($tipo); ?>',
    1,
    NOW()
);</pre>
        <button class="btn btn-copy" onclick="copiarSQLInsert()">📋 Copiar INSERT</button>
        
        <h3>Opção 2: Atualizar Usuário Existente (UPDATE)</h3>
        <pre id="sql-update">UPDATE usuarios 
SET 
    senha = '<?php echo htmlspecialchars($hash); ?>',
    tipo = '<?php echo htmlspecialchars($tipo); ?>'
WHERE email = '<?php echo htmlspecialchars($email); ?>';</pre>
        <button class="btn btn-copy" onclick="copiarSQLUpdate()">📋 Copiar UPDATE</button>
        
        <h2>Verificação (Execute após atualizar):</h2>
        <pre>SELECT 
    id,
    nome,
    email,
    tipo,
    ativo,
    LENGTH(senha) as comprimento_hash,
    LEFT(senha, 10) as hash_preview,
    CASE 
        WHEN senha LIKE '$2y$%' OR senha LIKE '$2a$%' OR senha LIKE '$2b$%' THEN '✅ Bcrypt (correto)'
        ELSE '❌ Não é Bcrypt'
    END as formato_hash
FROM usuarios 
WHERE email = '<?php echo htmlspecialchars($email); ?>';</pre>
        <button class="btn btn-copy" onclick="copiarVerificacao()">📋 Copiar Verificação</button>
        
        <div class="info" style="margin-top: 30px;">
            <strong>📝 Instruções:</strong>
            <ol>
                <li>Copie o comando SQL acima</li>
                <li>Acesse o phpMyAdmin do banco remoto</li>
                <li>Selecione o banco de dados do CFC</li>
                <li>Vá na aba "SQL"</li>
                <li>Cole o comando SQL e execute</li>
                <li>Execute a query de verificação para confirmar</li>
            </ol>
        </div>
        
        <div class="success" style="margin-top: 20px;">
            <strong>✅ Teste de Validação:</strong><br>
            <?php
            // Testar se o hash funciona
            $teste = password_verify($senhaPlana, $hash);
            if ($teste) {
                echo "✅ O hash gerado está correto e funcionará com a senha fornecida!";
            } else {
                echo "❌ ERRO: O hash não está funcionando!";
            }
            ?>
        </div>
        
        <p style="margin-top: 30px;">
            <a href="index.php?page=usuarios" class="btn">Voltar para Usuários</a>
        </p>
    </div>
    
    <script>
        function copiarHash() {
            const hash = document.getElementById('hash').textContent;
            navigator.clipboard.writeText(hash).then(() => {
                alert('Hash copiado para a área de transferência!');
            });
        }
        
        function copiarSQLInsert() {
            const sql = document.getElementById('sql-insert').textContent;
            navigator.clipboard.writeText(sql).then(() => {
                alert('Comando INSERT copiado para a área de transferência!');
            });
        }
        
        function copiarSQLUpdate() {
            const sql = document.getElementById('sql-update').textContent;
            navigator.clipboard.writeText(sql).then(() => {
                alert('Comando UPDATE copiado para a área de transferência!');
            });
        }
        
        function copiarVerificacao() {
            const verificacao = document.querySelectorAll('pre')[2].textContent;
            navigator.clipboard.writeText(verificacao).then(() => {
                alert('Query de verificação copiada para a área de transferência!');
            });
        }
    </script>
</body>
</html>

