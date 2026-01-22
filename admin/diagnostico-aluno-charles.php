<?php
/**
 * Script de Diagnóstico - Aluno Charles Dietrich Wutzke
 * FASE 1 - AREA ALUNO PENDENCIAS - Diagnóstico de identificação do aluno
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Verificar se é admin
$user = getCurrentUser();
if (!$user || $user['tipo'] !== 'admin') {
    die('Acesso negado. Apenas administradores podem executar este script.');
}

$db = db();

echo "<h1>Diagnóstico - Aluno Charles Dietrich Wutzke</h1>";
echo "<pre>";

// 1. Buscar usuário na tabela usuarios
echo "\n=== 1. BUSCAR USUÁRIO NA TABELA usuarios ===\n";
$usuarios = $db->fetchAll("
    SELECT id, nome, email, cpf, tipo, ativo 
    FROM usuarios 
    WHERE nome LIKE '%Charles%' OR email LIKE '%charles%' OR cpf LIKE '%03454769990%'
    ORDER BY id DESC
");

echo "Usuários encontrados: " . count($usuarios) . "\n";
foreach ($usuarios as $u) {
    echo "  - ID: {$u['id']}, Nome: {$u['nome']}, Email: {$u['email']}, CPF: {$u['cpf']}, Tipo: {$u['tipo']}, Ativo: {$u['ativo']}\n";
}

// 2. Verificar se coluna usuario_id existe
echo "\n=== 2. VERIFICAR ESTRUTURA DA TABELA alunos ===\n";
$colunas = $db->fetchAll("SHOW COLUMNS FROM alunos");
$temUsuarioId = false;
foreach ($colunas as $col) {
    if ($col['Field'] === 'usuario_id') {
        $temUsuarioId = true;
        break;
    }
}
echo "Coluna usuario_id existe: " . ($temUsuarioId ? 'SIM' : 'NÃO') . "\n";

// 3. Buscar aluno na tabela alunos
echo "\n=== 3. BUSCAR ALUNO NA TABELA alunos ===\n";
$campos = "id, nome, email, cpf, cfc_id, status";
if ($temUsuarioId) {
    $campos .= ", usuario_id";
}

$alunos = $db->fetchAll("
    SELECT $campos
    FROM alunos 
    WHERE nome LIKE '%Charles%' OR email LIKE '%charles%' OR cpf LIKE '%03454769990%' OR cpf LIKE '%034.547.699-90%'
    ORDER BY id DESC
");

echo "Alunos encontrados: " . count($alunos) . "\n";
foreach ($alunos as $a) {
    echo "  - ID: {$a['id']}, Nome: {$a['nome']}, Email: " . ($a['email'] ?? 'N/A') . ", CPF: {$a['cpf']}";
    if ($temUsuarioId) {
        echo ", usuario_id: " . ($a['usuario_id'] ?? 'NULL');
    }
    echo ", Status: {$a['status']}\n";
}

// 4. Testar getCurrentAlunoId() para cada usuário encontrado
echo "\n=== 4. TESTAR getCurrentAlunoId() ===\n";
foreach ($usuarios as $u) {
    if ($u['tipo'] === 'aluno') {
        echo "\nTestando getCurrentAlunoId() para usuario_id: {$u['id']}\n";
        $alunoId = getCurrentAlunoId($u['id']);
        echo "  Resultado: " . ($alunoId ? "Encontrado aluno_id: $alunoId" : "NULL (não encontrado)") . "\n";
    }
}

// 5. Verificar matrículas em turmas teóricas
echo "\n=== 5. VERIFICAR MATRÍCULAS EM TURMAS TEÓRICAS ===\n";
if (!empty($alunos)) {
    foreach ($alunos as $a) {
        $matriculas = $db->fetchAll("
            SELECT tm.*, tt.nome as turma_nome
            FROM turma_matriculas tm
            JOIN turmas_teoricas tt ON tm.turma_id = tt.id
            WHERE tm.aluno_id = ?
        ", [$a['id']]);
        
        echo "\nAluno ID {$a['id']} ({$a['nome']}):\n";
        echo "  Matrículas encontradas: " . count($matriculas) . "\n";
        foreach ($matriculas as $m) {
            echo "    - Turma: {$m['turma_nome']} (ID: {$m['turma_id']}), Status: {$m['status']}, Frequência: {$m['frequencia_percentual']}%\n";
        }
    }
}

// 6. Verificar se há diferença de formatação no CPF
echo "\n=== 6. VERIFICAR FORMATO DO CPF ===\n";
if (!empty($usuarios) && !empty($alunos)) {
    foreach ($usuarios as $u) {
        if ($u['tipo'] === 'aluno' && !empty($u['cpf'])) {
            $cpfUsuario = $u['cpf'];
            $cpfLimpoUsuario = preg_replace('/[^0-9]/', '', $cpfUsuario);
            echo "\nUsuário ID {$u['id']}:\n";
            echo "  CPF original: $cpfUsuario\n";
            echo "  CPF limpo: $cpfLimpoUsuario\n";
            
            foreach ($alunos as $a) {
                $cpfAluno = $a['cpf'];
                $cpfLimpoAluno = preg_replace('/[^0-9]/', '', $cpfAluno);
                echo "  Aluno ID {$a['id']}:\n";
                echo "    CPF original: $cpfAluno\n";
                echo "    CPF limpo: $cpfLimpoAluno\n";
                echo "    Match: " . ($cpfLimpoUsuario === $cpfLimpoAluno ? "SIM" : "NÃO") . "\n";
            }
        }
    }
}

// 7. Verificar emails
echo "\n=== 7. VERIFICAR EMAILS ===\n";
if (!empty($usuarios) && !empty($alunos)) {
    foreach ($usuarios as $u) {
        if ($u['tipo'] === 'aluno' && !empty($u['email'])) {
            echo "\nUsuário ID {$u['id']}:\n";
            echo "  Email: {$u['email']}\n";
            
            foreach ($alunos as $a) {
                $emailAluno = $a['email'] ?? '';
                echo "  Aluno ID {$a['id']}:\n";
                echo "    Email: " . ($emailAluno ?: 'N/A') . "\n";
                echo "    Match: " . (strtolower($u['email']) === strtolower($emailAluno) ? "SIM" : "NÃO") . "\n";
            }
        }
    }
} else {
    echo "Nenhum aluno encontrado na tabela alunos!\n";
    echo "Isso explica por que getCurrentAlunoId() retorna null.\n";
    
    // Buscar TODOS os alunos para ver se existe algum
    echo "\n=== 7.1. BUSCAR TODOS OS ALUNOS (últimos 10) ===\n";
    $todosAlunos = $db->fetchAll("SELECT id, nome, email, cpf FROM alunos ORDER BY id DESC LIMIT 10");
    echo "Total de alunos no sistema: " . count($todosAlunos) . "\n";
    foreach ($todosAlunos as $a) {
        echo "  - ID: {$a['id']}, Nome: {$a['nome']}, Email: " . ($a['email'] ?? 'N/A') . ", CPF: {$a['cpf']}\n";
    }
    
    // Buscar especificamente por CPF limpo
    echo "\n=== 7.2. BUSCAR POR CPF LIMPO ===\n";
    $cpfLimpo = preg_replace('/[^0-9]/', '', '03454769990');
    echo "CPF limpo: $cpfLimpo\n";
    $alunoPorCpf = $db->fetch("SELECT id, nome, email, cpf FROM alunos WHERE cpf = ?", [$cpfLimpo]);
    if ($alunoPorCpf) {
        echo "Aluno encontrado por CPF limpo: ID {$alunoPorCpf['id']}, Nome: {$alunoPorCpf['nome']}\n";
    } else {
        echo "Nenhum aluno encontrado com CPF limpo: $cpfLimpo\n";
    }
    
    // Buscar por email
    echo "\n=== 7.3. BUSCAR POR EMAIL ===\n";
    $email = 'dietrich.representacoes@gmail.com';
    echo "Email: $email\n";
    $alunoPorEmail = $db->fetch("SELECT id, nome, email, cpf FROM alunos WHERE LOWER(email) = LOWER(?)", [$email]);
    if ($alunoPorEmail) {
        echo "Aluno encontrado por email: ID {$alunoPorEmail['id']}, Nome: {$alunoPorEmail['nome']}\n";
    } else {
        echo "Nenhum aluno encontrado com email: $email\n";
    }
}

echo "\n=== FIM DO DIAGNÓSTICO ===\n";
echo "</pre>";

