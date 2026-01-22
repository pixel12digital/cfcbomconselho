<?php
/**
 * Script de Diagnóstico - Login de Aluno
 * Verifica problemas com login de alunos
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

header('Content-Type: text/html; charset=utf-8');

$cpf = $_GET['cpf'] ?? '034.547.699-90';
$senha = $_GET['senha'] ?? '';

echo "<h1>Diagnóstico de Login - Aluno</h1>";
echo "<pre>";

// Limpar CPF (remover pontos e traços)
$cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
echo "CPF Original: $cpf\n";
echo "CPF Limpo: $cpfLimpo\n\n";

$db = db();

// 1. Buscar na tabela usuarios
echo "=== BUSCA NA TABELA usuarios ===\n";
$usuario = $db->fetch("SELECT id, nome, email, cpf, tipo, ativo, senha FROM usuarios WHERE cpf = ? AND tipo = 'aluno'", [$cpfLimpo]);
if ($usuario) {
    echo "✅ Usuário encontrado na tabela usuarios:\n";
    echo "   ID: {$usuario['id']}\n";
    echo "   Nome: {$usuario['nome']}\n";
    echo "   Email: {$usuario['email']}\n";
    echo "   CPF: {$usuario['cpf']}\n";
    echo "   Tipo: {$usuario['tipo']}\n";
    echo "   Ativo: " . ($usuario['ativo'] ? 'Sim' : 'Não') . "\n";
    echo "   Senha (hash): " . substr($usuario['senha'], 0, 20) . "...\n";
    echo "   Comprimento do hash: " . strlen($usuario['senha']) . " caracteres\n\n";
    
    if ($senha) {
        echo "=== TESTE DE SENHA ===\n";
        $senhaValida = password_verify($senha, $usuario['senha']);
        echo "Senha testada: $senha\n";
        echo "Resultado password_verify: " . ($senhaValida ? "✅ VÁLIDA" : "❌ INVÁLIDA") . "\n\n";
        
        // Testar algumas variações
        echo "=== TESTE DE VARIAÇÕES ===\n";
        $variacoes = [
            $senha,
            trim($senha),
            $senha . ' ',
            ' ' . $senha,
        ];
        foreach ($variacoes as $var) {
            $result = password_verify($var, $usuario['senha']);
            echo "Senha: '" . addslashes($var) . "' (" . strlen($var) . " chars) -> " . ($result ? "✅" : "❌") . "\n";
        }
    }
} else {
    echo "❌ Usuário NÃO encontrado na tabela usuarios com CPF: $cpfLimpo\n\n";
    
    // Tentar buscar por email
    echo "=== TENTANDO BUSCAR POR EMAIL ===\n";
    $usuarioEmail = $db->fetch("SELECT id, nome, email, cpf, tipo, ativo FROM usuarios WHERE email LIKE ? AND tipo = 'aluno'", ["%$cpfLimpo%"]);
    if ($usuarioEmail) {
        echo "⚠️ Encontrado por email (parcial):\n";
        print_r($usuarioEmail);
    } else {
        echo "❌ Também não encontrado por email\n";
    }
}

// 2. Buscar na tabela alunos
echo "\n=== BUSCA NA TABELA alunos ===\n";
$aluno = $db->fetch("SELECT id, nome, cpf, ativo, senha FROM alunos WHERE cpf = ?", [$cpfLimpo]);
if ($aluno) {
    echo "✅ Aluno encontrado na tabela alunos:\n";
    echo "   ID: {$aluno['id']}\n";
    echo "   Nome: {$aluno['nome']}\n";
    echo "   CPF: {$aluno['cpf']}\n";
    echo "   Ativo: " . ($aluno['ativo'] ? 'Sim' : 'Não') . "\n";
    if (isset($aluno['senha'])) {
        echo "   Senha (hash): " . substr($aluno['senha'], 0, 20) . "...\n";
        echo "   Comprimento do hash: " . strlen($aluno['senha']) . " caracteres\n";
        
        if ($senha) {
            $senhaValida = password_verify($senha, $aluno['senha']);
            echo "   Resultado password_verify: " . ($senhaValida ? "✅ VÁLIDA" : "❌ INVÁLIDA") . "\n";
        }
    } else {
        echo "   ⚠️ Campo 'senha' não existe na tabela alunos\n";
    }
} else {
    echo "❌ Aluno NÃO encontrado na tabela alunos com CPF: $cpfLimpo\n";
}

// 3. Verificar todas as ocorrências do CPF
echo "\n=== TODAS AS OCORRÊNCIAS DO CPF ===\n";
$todosUsuarios = $db->fetchAll("SELECT id, nome, email, cpf, tipo, ativo FROM usuarios WHERE cpf LIKE ?", ["%$cpfLimpo%"]);
echo "Na tabela usuarios (busca parcial): " . count($todosUsuarios) . " resultado(s)\n";
foreach ($todosUsuarios as $u) {
    echo "   - ID: {$u['id']}, Nome: {$u['nome']}, CPF: {$u['cpf']}, Tipo: {$u['tipo']}\n";
}

$todosAlunos = $db->fetchAll("SELECT id, nome, cpf, ativo FROM alunos WHERE cpf LIKE ?", ["%$cpfLimpo%"]);
echo "Na tabela alunos (busca parcial): " . count($todosAlunos) . " resultado(s)\n";
foreach ($todosAlunos as $a) {
    echo "   - ID: {$a['id']}, Nome: {$a['nome']}, CPF: {$a['cpf']}\n";
}

// 4. Verificar estrutura das tabelas
echo "\n=== ESTRUTURA DAS TABELAS ===\n";
$colunasUsuarios = $db->fetchAll("SHOW COLUMNS FROM usuarios");
echo "Tabela usuarios - Colunas:\n";
foreach ($colunasUsuarios as $col) {
    echo "   - {$col['Field']} ({$col['Type']})\n";
}

$colunasAlunos = $db->fetchAll("SHOW COLUMNS FROM alunos");
echo "\nTabela alunos - Colunas:\n";
foreach ($colunasAlunos as $col) {
    echo "   - {$col['Field']} ({$col['Type']})\n";
}

echo "\n</pre>";

echo "<hr>";
echo "<h2>Teste de Login</h2>";
echo "<form method='GET'>";
echo "CPF: <input type='text' name='cpf' value='$cpf'><br>";
echo "Senha: <input type='password' name='senha'><br>";
echo "<button type='submit'>Testar</button>";
echo "</form>";

