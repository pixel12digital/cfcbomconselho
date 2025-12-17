<?php
/**
 * Diagnóstico: CPF na recuperação de senha
 * 
 * Script para verificar por que um CPF não está sendo encontrado
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/database.php';

// Script de diagnóstico - permite acesso sem login para facilitar testes
// Em produção, considere adicionar verificação de IP ou token

$cpfTeste = $_GET['cpf'] ?? '03454769990';
$cpfLimpo = preg_replace('/[^0-9]/', '', $cpfTeste);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico CPF - Recuperação de Senha</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1A365D;
        }
        h2 {
            color: #2c3e50;
            border-bottom: 2px solid #1A365D;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background: #1A365D;
            color: white;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        form {
            margin: 20px 0;
        }
        input[type="text"] {
            padding: 10px;
            width: 300px;
            border: 2px solid #1A365D;
            border-radius: 4px;
        }
        button {
            padding: 10px 20px;
            background: #1A365D;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: #2d4a6b;
        }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico: CPF na Recuperação de Senha</h1>
    
    <div class="card">
        <form method="GET">
            <label>
                <strong>CPF para testar:</strong>
                <input type="text" name="cpf" value="<?php echo htmlspecialchars($cpfTeste); ?>" 
                       placeholder="03454769990 ou 034.547.699-90">
            </label>
            <button type="submit">🔍 Verificar</button>
        </form>
    </div>

    <?php
    $db = db();
    
    // 1. Verificar na tabela usuarios
    echo '<div class="card">';
    echo '<h2>1. Busca na Tabela usuarios (tipo = aluno)</h2>';
    
    // Busca exata com CPF limpo
    $usuarioExato = $db->fetch(
        "SELECT id, email, cpf, tipo, ativo FROM usuarios WHERE cpf = :cpf AND tipo = 'aluno' LIMIT 1",
        ['cpf' => $cpfLimpo]
    );
    
    // Busca normalizada (com REPLACE)
    $usuarioNormalizado = $db->fetch(
        "SELECT id, email, cpf, tipo, ativo FROM usuarios 
         WHERE REPLACE(REPLACE(cpf, '.', ''), '-', '') = :cpf 
         AND tipo = 'aluno' 
         LIMIT 1",
        ['cpf' => $cpfLimpo]
    );
    
    // Listar todos os alunos na tabela usuarios
    $todosAlunos = $db->fetchAll(
        "SELECT id, email, cpf, tipo, ativo FROM usuarios WHERE tipo = 'aluno' ORDER BY id DESC LIMIT 20"
    );
    
    echo '<div class="info">';
    echo '<strong>CPF Limpo:</strong> ' . htmlspecialchars($cpfLimpo) . '<br>';
    echo '<strong>CPF Original:</strong> ' . htmlspecialchars($cpfTeste);
    echo '</div>';
    
    if ($usuarioExato) {
        echo '<p class="success">✅ Encontrado com busca exata (cpf = :cpf)</p>';
        echo '<table>';
        echo '<tr><th>Campo</th><th>Valor</th></tr>';
        foreach ($usuarioExato as $key => $value) {
            echo '<tr><td>' . htmlspecialchars($key) . '</td><td>' . htmlspecialchars($value ?? 'NULL') . '</td></tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="error">❌ NÃO encontrado com busca exata (cpf = :cpf)</p>';
    }
    
    if ($usuarioNormalizado) {
        echo '<p class="success">✅ Encontrado com busca normalizada (REPLACE)</p>';
        echo '<table>';
        echo '<tr><th>Campo</th><th>Valor</th></tr>';
        foreach ($usuarioNormalizado as $key => $value) {
            echo '<tr><td>' . htmlspecialchars($key) . '</td><td>' . htmlspecialchars($value ?? 'NULL') . '</td></tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="error">❌ NÃO encontrado com busca normalizada (REPLACE)</p>';
    }
    
    echo '</div>';
    
    // 2. Verificar na tabela alunos
    echo '<div class="card">';
    echo '<h2>2. Busca na Tabela alunos (comparação)</h2>';
    
    $alunoTabelaAlunos = $db->fetch(
        "SELECT id, nome, cpf, email, ativo FROM alunos 
         WHERE REPLACE(REPLACE(cpf, '.', ''), '-', '') = :cpf 
         LIMIT 1",
        ['cpf' => $cpfLimpo]
    );
    
    if ($alunoTabelaAlunos) {
        echo '<p class="success">✅ Encontrado na tabela alunos</p>';
        echo '<table>';
        echo '<tr><th>Campo</th><th>Valor</th></tr>';
        foreach ($alunoTabelaAlunos as $key => $value) {
            echo '<tr><td>' . htmlspecialchars($key) . '</td><td>' . htmlspecialchars($value ?? 'NULL') . '</td></tr>';
        }
        echo '</table>';
        
        // Verificar se existe em usuarios também
        if (!$usuarioNormalizado) {
            echo '<p class="error">⚠️ ALERTA: Este aluno existe na tabela alunos, mas NÃO na tabela usuarios!</p>';
            echo '<p>Para recuperação de senha funcionar, o aluno precisa existir na tabela usuarios com tipo = "aluno".</p>';
        }
    } else {
        echo '<p class="error">❌ NÃO encontrado na tabela alunos</p>';
    }
    
    echo '</div>';
    
    // 3. Listar exemplos de CPFs na tabela usuarios
    echo '<div class="card">';
    echo '<h2>3. Exemplos de CPFs na tabela usuarios (tipo = aluno)</h2>';
    
    if (!empty($todosAlunos)) {
        echo '<p>Últimos 20 alunos cadastrados:</p>';
        echo '<table>';
        echo '<tr><th>ID</th><th>CPF (original)</th><th>CPF Limpo</th><th>Email</th><th>Ativo</th></tr>';
        foreach ($todosAlunos as $aluno) {
            $cpfOriginal = $aluno['cpf'] ?? 'N/A';
            $cpfLimpado = preg_replace('/[^0-9]/', '', $cpfOriginal);
            $match = ($cpfLimpado === $cpfLimpo) ? ' style="background: #ffeb3b; font-weight: bold;"' : '';
            echo '<tr' . $match . '>';
            echo '<td>' . htmlspecialchars($aluno['id'] ?? 'N/A') . '</td>';
            echo '<td>' . htmlspecialchars($cpfOriginal) . '</td>';
            echo '<td>' . htmlspecialchars($cpfLimpado) . '</td>';
            echo '<td>' . htmlspecialchars($aluno['email'] ?? 'N/A') . '</td>';
            echo '<td>' . ($aluno['ativo'] ? '✅ Sim' : '❌ Não') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="error">❌ Nenhum aluno encontrado na tabela usuarios!</p>';
    }
    
    echo '</div>';
    
    // 4. Recomendações
    echo '<div class="card">';
    echo '<h2>4. Recomendações</h2>';
    
    if ($alunoTabelaAlunos && !$usuarioNormalizado) {
        echo '<div class="info">';
        echo '<h3>⚠️ Problema Identificado:</h3>';
        echo '<p>O CPF <strong>' . htmlspecialchars($cpfLimpo) . '</strong> existe na tabela <code>alunos</code>, mas NÃO existe na tabela <code>usuarios</code>.</p>';
        echo '<p><strong>Solução:</strong> É necessário criar um registro na tabela <code>usuarios</code> para este aluno.</p>';
        echo '</div>';
    } elseif (!$alunoTabelaAlunos && !$usuarioNormalizado) {
        echo '<div class="info">';
        echo '<p class="error">❌ O CPF não foi encontrado em nenhuma das tabelas.</p>';
        echo '<p>Verifique se o CPF está correto e se o aluno está cadastrado no sistema.</p>';
        echo '</div>';
    } elseif ($usuarioNormalizado && $usuarioNormalizado['ativo'] == 0) {
        echo '<div class="info">';
        echo '<p class="error">⚠️ O aluno foi encontrado, mas está INATIVO (ativo = 0).</p>';
        echo '<p>A recuperação de senha só funciona para alunos ativos.</p>';
        echo '</div>';
    } else {
        echo '<div class="info">';
        echo '<p class="success">✅ O aluno foi encontrado e está ativo!</p>';
        echo '<p>Se ainda não funciona, verifique os logs do PHP para mais detalhes.</p>';
        echo '</div>';
    }
    
    echo '</div>';
    ?>
    
    <div class="card">
        <h2>5. Query de Teste</h2>
        <p>Execute esta query no banco para verificar:</p>
        <pre style="background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto;">
-- Busca normalizada (como o código faz)
SELECT id, email, cpf, tipo, ativo 
FROM usuarios 
WHERE REPLACE(REPLACE(cpf, '.', ''), '-', '') = '<?php echo $cpfLimpo; ?>' 
AND tipo = 'aluno' 
AND ativo = 1;

-- Ver todos os CPFs de alunos
SELECT id, cpf, REPLACE(REPLACE(cpf, '.', ''), '-', '') as cpf_limpo, email, ativo 
FROM usuarios 
WHERE tipo = 'aluno' 
ORDER BY id DESC 
LIMIT 50;
        </pre>
    </div>
</body>
</html>
