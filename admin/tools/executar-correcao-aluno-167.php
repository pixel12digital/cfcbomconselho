<?php
/**
 * Script de Correção - Aluno 167 para Turma 19
 * 
 * Executa as correções identificadas no diagnóstico:
 * 1. Atualiza status do aluno 167 de 'concluido' para 'ativo'
 * 2. Atualiza CFC do aluno 167 para o CFC da turma 19
 * 
 * Uso: Acessar via navegador ou CLI
 *      admin/tools/executar-correcao-aluno-167.php
 * 
 * ⚠️ EXECUTAR APENAS EM HOMOLOG/TESTE
 */

// Permitir apenas admin (ou desabilitar em produção)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Para desenvolvimento: permitir acesso sem login se não estiver em produção
$isDev = (getenv('ENVIRONMENT') === 'development' || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false);
if (!$isDev && (!isset($_SESSION['user']) || $_SESSION['user']['tipo'] !== 'admin')) {
    die('Acesso negado. Apenas administradores podem executar este script.');
}

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';

$turmaId = 19;
$alunoId = 167;

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Correção Aluno 167</title>";
echo "<style>body{font-family:monospace;padding:20px;} .ok{color:green;} .fail{color:red;} .warning{color:orange;} pre{background:#f5f5f5;padding:10px;border:1px solid #ddd;} table{border-collapse:collapse;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f0f0f0;}</style></head><body>";
echo "<h1>🔧 Correção - Aluno 167 para Turma 19</h1>";

try {
    $db = Database::getInstance();
    $db->beginTransaction();
    
    // PASSO 1: Verificar situação atual
    echo "<h2>1. Situação Atual</h2>";
    
    $aluno = $db->fetch("SELECT id, nome, status, cfc_id FROM alunos WHERE id = ?", [$alunoId]);
    if (!$aluno) {
        throw new Exception("Aluno {$alunoId} não encontrado!");
    }
    
    $turma = $db->fetch("SELECT id, nome, cfc_id FROM turmas_teoricas WHERE id = ?", [$turmaId]);
    if (!$turma) {
        throw new Exception("Turma {$turmaId} não encontrada!");
    }
    
    echo "<table>";
    echo "<tr><th>Item</th><th>Antes</th></tr>";
    echo "<tr><td>Aluno ID</td><td>{$aluno['id']}</td></tr>";
    echo "<tr><td>Aluno Nome</td><td>{$aluno['nome']}</td></tr>";
    echo "<tr><td>Status Atual</td><td><strong>{$aluno['status']}</strong></td></tr>";
    echo "<tr><td>CFC Aluno</td><td><strong>{$aluno['cfc_id']}</strong></td></tr>";
    echo "<tr><td>Turma ID</td><td>{$turma['id']}</td></tr>";
    echo "<tr><td>Turma Nome</td><td>{$turma['nome']}</td></tr>";
    echo "<tr><td>CFC Turma</td><td><strong>{$turma['cfc_id']}</strong></td></tr>";
    echo "</table>";
    
    $statusAntes = $aluno['status'];
    $cfcAntes = $aluno['cfc_id'];
    
    // PASSO 2: Executar correções
    echo "<h2>2. Executando Correções</h2>";
    
    // Correção 1: Status
    if ($aluno['status'] !== 'ativo') {
        echo "<p>Corrigindo status: '{$aluno['status']}' → 'ativo'</p>";
        $db->update('alunos', ['status' => 'ativo'], 'id = ?', [$alunoId]);
        echo "<p class='ok'>✅ Status atualizado para 'ativo'</p>";
    } else {
        echo "<p class='ok'>✅ Status já está 'ativo' (sem alteração necessária)</p>";
    }
    
    // Correção 2: CFC
    // Verificar se o CFC da turma existe antes de tentar atualizar
    $cfcTurmaExiste = $db->fetch("SELECT id, nome FROM cfcs WHERE id = ?", [$turma['cfc_id']]);
    
    if ($aluno['cfc_id'] != $turma['cfc_id']) {
        if ($cfcTurmaExiste) {
            echo "<p>Corrigindo CFC: {$aluno['cfc_id']} → {$turma['cfc_id']} ({$cfcTurmaExiste['nome']})</p>";
            try {
                $db->update('alunos', ['cfc_id' => $turma['cfc_id']], 'id = ?', [$alunoId]);
                echo "<p class='ok'>✅ CFC atualizado para {$turma['cfc_id']}</p>";
            } catch (Exception $e) {
                echo "<p class='warning'>⚠️ Não foi possível atualizar o CFC (foreign key constraint).</p>";
                echo "<p class='warning'>O CFC {$turma['cfc_id']} pode não existir ou não ser válido.</p>";
                echo "<p><strong>Solução alternativa:</strong> Criar uma turma teórica no CFC {$aluno['cfc_id']} (CFC do aluno) ao invés de alterar o CFC do aluno.</p>";
            }
        } else {
            echo "<p class='warning'>⚠️ CFC {$turma['cfc_id']} da turma não existe na tabela cfcs!</p>";
            echo "<p class='warning'>Não é possível atualizar o CFC do aluno para um valor inexistente.</p>";
            echo "<p><strong>Solução alternativa:</strong></p>";
            echo "<ul>";
            echo "<li>Criar uma turma teórica no CFC {$aluno['cfc_id']} (CFC do aluno)</li>";
            echo "<li>OU criar o CFC {$turma['cfc_id']} na tabela cfcs primeiro</li>";
            echo "</ul>";
        }
    } else {
        echo "<p class='ok'>✅ CFC já está correto ({$aluno['cfc_id']}) (sem alteração necessária)</p>";
    }
    
    // PASSO 3: Verificar resultado
    echo "<h2>3. Situação Após Correção</h2>";
    
    $alunoDepois = $db->fetch("SELECT id, nome, status, cfc_id FROM alunos WHERE id = ?", [$alunoId]);
    
    echo "<table>";
    echo "<tr><th>Item</th><th>Antes</th><th>Depois</th><th>Status</th></tr>";
    echo "<tr><td>Status</td><td>{$statusAntes}</td><td>{$alunoDepois['status']}</td><td>" . 
         ($alunoDepois['status'] === 'ativo' ? "<span class='ok'>✅ OK</span>" : "<span class='fail'>❌</span>") . "</td></tr>";
    $cfcCompativel = ($alunoDepois['cfc_id'] == $turma['cfc_id']);
    echo "<tr><td>CFC</td><td>{$cfcAntes}</td><td>{$alunoDepois['cfc_id']}</td><td>" . 
         ($cfcCompativel ? "<span class='ok'>✅ OK</span>" : "<span class='warning'>⚠️ Diferente da turma ({$turma['cfc_id']})</span>") . "</td></tr>";
    echo "</table>";
    
    // PASSO 4: Validar query base da API
    echo "<h2>4. Validação - Query Base da API</h2>";
    
    $statusPermitidos = ['ativo', 'em_andamento'];
    $placeholders = implode(',', array_fill(0, count($statusPermitidos), '?'));
    
    // Usar CFC do aluno após correção (se foi alterado)
    $alunoDepois = $db->fetch("SELECT cfc_id FROM alunos WHERE id = ?", [$alunoId]);
    $cfcFinal = $alunoDepois['cfc_id'];
    
    // Validar com CFC da turma
    $params = array_merge([$alunoId], $statusPermitidos, [$turma['cfc_id']]);
    $resultadoValidacaoTurma = $db->fetchAll("
        SELECT a.id, a.nome, a.status, a.cfc_id
        FROM alunos a
        WHERE a.id = ?
          AND a.status IN ({$placeholders})
          AND a.cfc_id = ?
    ", $params);
    
    // Validar com CFC do aluno (caso não tenha conseguido alterar)
    $paramsAluno = array_merge([$alunoId], $statusPermitidos, [$cfcFinal]);
    $resultadoValidacaoAluno = $db->fetchAll("
        SELECT a.id, a.nome, a.status, a.cfc_id
        FROM alunos a
        WHERE a.id = ?
          AND a.status IN ({$placeholders})
          AND a.cfc_id = ?
    ", $paramsAluno);
    
    if (count($resultadoValidacaoTurma) > 0) {
        echo "<p class='ok'>✅ <strong>SUCESSO!</strong> Aluno agora passa na query base da API com CFC da turma.</p>";
        echo "<p>O aluno deve aparecer na lista do modal 'Matricular Alunos na Turma' para a turma {$turmaId}.</p>";
    } elseif (count($resultadoValidacaoAluno) > 0) {
        echo "<p class='warning'>⚠️ <strong>ATENÇÃO:</strong> Aluno passa na query base, mas com CFC diferente da turma.</p>";
        echo "<p>Status: ✅ OK</p>";
        echo "<p>CFC do aluno: {$cfcFinal} | CFC da turma: {$turma['cfc_id']}</p>";
        echo "<p><strong>Problema:</strong> Para o aluno aparecer na lista da turma {$turmaId}, precisa estar no mesmo CFC da turma.</p>";
        echo "<p><strong>Soluções:</strong></p>";
        echo "<ul>";
        echo "<li><strong>Opção 1:</strong> Criar uma nova turma teórica no CFC {$cfcFinal}</li>";
        echo "<li><strong>Opção 2:</strong> Usar uma turma existente do CFC {$cfcFinal}</li>";
        echo "<li><strong>Opção 3:</strong> Criar o CFC {$turma['cfc_id']} na tabela cfcs e então atualizar o aluno</li>";
        echo "</ul>";
    } else {
        echo "<p class='fail'>❌ <strong>FALHOU!</strong> Aluno ainda não passa na query base.</p>";
        echo "<p>Verifique os critérios acima.</p>";
    }
    
    $db->commit();
    
    echo "<h2>5. Próximos Passos</h2>";
    echo "<ol>";
    echo "<li>Abrir o modal 'Matricular Alunos na Turma' para a turma {$turmaId}</li>";
    echo "<li>Verificar se o aluno {$alunoId} aparece na lista</li>";
    echo "<li>Tentar matricular o aluno e confirmar sucesso</li>";
    echo "</ol>";
    
    echo "<hr>";
    echo "<p><strong>⚠️ LEMBRE-SE:</strong> Esta correção foi apenas para teste/homolog. Em produção, alunos concluídos não devem ser automaticamente reabertos.</p>";
    
} catch (Exception $e) {
    $db->rollback();
    echo "<p class='fail'>❌ ERRO: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";

