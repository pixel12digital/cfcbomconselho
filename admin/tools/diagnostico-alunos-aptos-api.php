<?php
/**
 * Script de Diagnóstico - API Alunos Aptos para Turma Teórica
 * 
 * Objetivo: Identificar exatamente onde o aluno 167 está sendo excluído
 * 
 * Uso: Acessar via navegador ou CLI
 *      admin/tools/diagnostico-alunos-aptos-api.php?turma_id=19&aluno_id=167
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
require_once __DIR__ . '/../includes/guards_exames.php';
require_once __DIR__ . '/../includes/FinanceiroAlunoHelper.php';

define('STATUS_ALUNO_PERMITIDOS_TURMA_TEORICA', ['ativo', 'em_andamento']);

$turmaId = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 19;
$alunoId = isset($_GET['aluno_id']) ? (int)$_GET['aluno_id'] : 167;

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Diagnóstico API Alunos Aptos</title>";
echo "<style>body{font-family:monospace;padding:20px;} .ok{color:green;} .fail{color:red;} .warning{color:orange;} pre{background:#f5f5f5;padding:10px;border:1px solid #ddd;} table{border-collapse:collapse;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f0f0f0;}</style></head><body>";
echo "<h1>🔍 Diagnóstico API Alunos Aptos - Turma {$turmaId} / Aluno {$alunoId}</h1>";

try {
    $db = Database::getInstance();
    
    // PASSO 1: Dados básicos do aluno
    echo "<h2>1. Dados Básicos do Aluno {$alunoId}</h2>";
    $aluno = $db->fetch("SELECT id, nome, status, cfc_id, categoria_cnh FROM alunos WHERE id = ?", [$alunoId]);
    if (!$aluno) {
        echo "<p class='fail'>❌ Aluno {$alunoId} não encontrado no banco!</p>";
        exit;
    }
    
    echo "<table>";
    echo "<tr><th>Campo</th><th>Valor</th><th>Status</th></tr>";
    echo "<tr><td>ID</td><td>{$aluno['id']}</td><td>✅</td></tr>";
    echo "<tr><td>Nome</td><td>{$aluno['nome']}</td><td>✅</td></tr>";
    
    $statusOK = in_array($aluno['status'], STATUS_ALUNO_PERMITIDOS_TURMA_TEORICA);
    echo "<tr><td>Status</td><td>{$aluno['status']}</td><td>" . ($statusOK ? "✅ OK (permitido)" : "❌ NÃO PERMITIDO (esperado: ativo ou em_andamento)") . "</td></tr>";
    
    echo "<tr><td>CFC ID</td><td>{$aluno['cfc_id']}</td><td>✅</td></tr>";
    echo "<tr><td>Categoria CNH</td><td>" . ($aluno['categoria_cnh'] ?? 'N/A') . "</td><td>-</td></tr>";
    echo "</table>";
    
    if (!$statusOK) {
        echo "<p class='fail'><strong>PROBLEMA ENCONTRADO: Status do aluno não está em STATUS_ALUNO_PERMITIDOS_TURMA_TEORICA</strong></p>";
    }
    
    // PASSO 2: Dados da turma
    echo "<h2>2. Dados da Turma {$turmaId}</h2>";
    $turma = $db->fetch("SELECT id, nome, cfc_id, curso_tipo, status FROM turmas_teoricas WHERE id = ?", [$turmaId]);
    if (!$turma) {
        echo "<p class='fail'>❌ Turma {$turmaId} não encontrada no banco!</p>";
        exit;
    }
    
    echo "<table>";
    echo "<tr><th>Campo</th><th>Valor</th><th>Status</th></tr>";
    echo "<tr><td>ID</td><td>{$turma['id']}</td><td>✅</td></tr>";
    echo "<tr><td>Nome</td><td>{$turma['nome']}</td><td>✅</td></tr>";
    echo "<tr><td>CFC ID</td><td>{$turma['cfc_id']}</td><td>✅</td></tr>";
    
    $cfcOK = ($aluno['cfc_id'] == $turma['cfc_id']);
    echo "<tr><td>CFC Aluno = CFC Turma?</td><td>" . ($cfcOK ? "SIM ({$aluno['cfc_id']})" : "NÃO (Aluno: {$aluno['cfc_id']}, Turma: {$turma['cfc_id']})") . "</td><td>" . ($cfcOK ? "✅" : "❌") . "</td></tr>";
    
    echo "<tr><td>Curso Tipo</td><td>{$turma['curso_tipo']}</td><td>-</td></tr>";
    echo "</table>";
    
    if (!$cfcOK) {
        echo "<p class='fail'><strong>PROBLEMA ENCONTRADO: CFC do aluno ({$aluno['cfc_id']}) é diferente do CFC da turma ({$turma['cfc_id']})</strong></p>";
    }
    
    // PASSO 3: Teste da query base (status + CFC)
    echo "<h2>3. Teste da Query Base (Status + CFC)</h2>";
    $statusPermitidos = STATUS_ALUNO_PERMITIDOS_TURMA_TEORICA;
    $placeholders = implode(',', array_fill(0, count($statusPermitidos), '?'));
    $params = array_merge([$alunoId], $statusPermitidos, [$turma['cfc_id']]);
    
    $resultadoBase = $db->fetchAll("
        SELECT a.id, a.nome, a.status, a.cfc_id
        FROM alunos a
        WHERE a.id = ?
          AND a.status IN ({$placeholders})
          AND a.cfc_id = ?
    ", $params);
    
    echo "<p>Query: <code>SELECT ... WHERE a.id = {$alunoId} AND a.status IN (" . implode(', ', $statusPermitidos) . ") AND a.cfc_id = {$turma['cfc_id']}</code></p>";
    echo "<p>Resultado: " . count($resultadoBase) . " aluno(s) encontrado(s)</p>";
    
    if (count($resultadoBase) === 0) {
        echo "<p class='fail'><strong>PROBLEMA: Aluno não passa na query base (status ou CFC)</strong></p>";
        echo "<p>Verifique se:</p><ul>";
        echo "<li>Status do aluno está em: " . implode(' ou ', $statusPermitidos) . "</li>";
        echo "<li>CFC do aluno ({$aluno['cfc_id']}) = CFC da turma ({$turma['cfc_id']})</li>";
        echo "</ul>";
    } else {
        echo "<p class='ok'>✅ Aluno passa na query base</p>";
    }
    
    // PASSO 4: Query completa (com JOINs)
    echo "<h2>4. Query Completa da API (com JOINs)</h2>";
    $paramsCompleto = array_merge([$turmaId], $statusPermitidos, [$turma['cfc_id']]);
    
    $resultadoCompleto = $db->fetchAll("
        SELECT 
            a.id,
            a.nome,
            a.status as status_aluno,
            a.cfc_id,
            c.nome as cfc_nome,
            m_ativa.id as matricula_id,
            m_ativa.status as matricula_status,
            CASE 
                WHEN tm.id IS NOT NULL THEN 'matriculado'
                ELSE 'disponivel'
            END as status_matricula
        FROM alunos a
        JOIN cfcs c ON a.cfc_id = c.id
        LEFT JOIN turma_matriculas tm ON a.id = tm.aluno_id 
            AND tm.turma_id = ? 
            AND tm.status IN ('matriculado', 'cursando')
        LEFT JOIN (
            SELECT aluno_id, id, status
            FROM matriculas
            WHERE status = 'ativa'
        ) m_ativa ON a.id = m_ativa.aluno_id
        WHERE a.status IN ({$placeholders})
            AND a.cfc_id = ?
            AND a.id = ?
        ORDER BY a.nome
    ", array_merge([$turmaId], $statusPermitidos, [$turma['cfc_id'], $alunoId]));
    
    echo "<p>Resultado: " . count($resultadoCompleto) . " aluno(s) encontrado(s)</p>";
    
    if (count($resultadoCompleto) === 0) {
        echo "<p class='fail'><strong>PROBLEMA: Aluno não aparece na query completa</strong></p>";
        echo "<p>Possíveis causas:</p><ul>";
        echo "<li>JOIN com cfcs falhou (CFC não existe?)</li>";
        echo "<li>Problema no JOIN com matriculas (mas é LEFT JOIN, não deveria excluir)</li>";
        echo "</ul>";
    } else {
        echo "<p class='ok'>✅ Aluno aparece na query completa</p>";
        echo "<pre>" . print_r($resultadoCompleto[0], true) . "</pre>";
    }
    
    // PASSO 5: Verificar exames
    echo "<h2>5. Verificação de Exames</h2>";
    $examesOK = GuardsExames::alunoComExamesOkParaTeoricas($alunoId);
    echo "<p>GuardsExames::alunoComExamesOkParaTeoricas({$alunoId}) = " . ($examesOK ? "<span class='ok'>true ✅</span>" : "<span class='fail'>false ❌</span>") . "</p>";
    
    if (!$examesOK) {
        $exames = GuardsExames::getStatusExames($alunoId);
        echo "<pre>" . print_r($exames, true) . "</pre>";
    }
    
    // PASSO 6: Verificar financeiro
    echo "<h2>6. Verificação Financeira</h2>";
    $verificacaoFinanceira = FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno($alunoId);
    $financeiroOK = $verificacaoFinanceira['liberado'];
    echo "<p>FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno({$alunoId})['liberado'] = " . ($financeiroOK ? "<span class='ok'>true ✅</span>" : "<span class='fail'>false ❌</span>") . "</p>";
    
    if (!$financeiroOK) {
        echo "<p class='warning'>Motivo: " . ($verificacaoFinanceira['motivo'] ?? 'N/A') . "</p>";
        echo "<pre>" . print_r($verificacaoFinanceira, true) . "</pre>";
    }
    
    // PASSO 7: Verificar se já está matriculado
    echo "<h2>7. Verificação de Matrícula na Turma</h2>";
    $jaMatriculado = $db->fetch("
        SELECT id, status 
        FROM turma_matriculas 
        WHERE aluno_id = ? AND turma_id = ? AND status IN ('matriculado', 'cursando')
    ", [$alunoId, $turmaId]);
    
    if ($jaMatriculado) {
        echo "<p class='warning'>⚠️ Aluno já está matriculado na turma (ID matrícula: {$jaMatriculado['id']}, Status: {$jaMatriculado['status']})</p>";
    } else {
        echo "<p class='ok'>✅ Aluno NÃO está matriculado na turma (OK para aparecer na lista)</p>";
    }
    
    // RESUMO FINAL
    echo "<h2>8. Resumo Final</h2>";
    echo "<table>";
    echo "<tr><th>Critério</th><th>Status</th><th>Resultado</th></tr>";
    
    echo "<tr><td>Status permitido</td><td>" . ($statusOK ? "✅" : "❌") . "</td><td>{$aluno['status']}</td></tr>";
    echo "<tr><td>CFC compatível</td><td>" . ($cfcOK ? "✅" : "❌") . "</td><td>Aluno: {$aluno['cfc_id']}, Turma: {$turma['cfc_id']}</td></tr>";
    echo "<tr><td>Query base retorna aluno</td><td>" . (count($resultadoBase) > 0 ? "✅" : "❌") . "</td><td>" . count($resultadoBase) . " aluno(s)</td></tr>";
    echo "<tr><td>Query completa retorna aluno</td><td>" . (count($resultadoCompleto) > 0 ? "✅" : "❌") . "</td><td>" . count($resultadoCompleto) . " aluno(s)</td></tr>";
    echo "<tr><td>Exames OK</td><td>" . ($examesOK ? "✅" : "❌") . "</td><td>" . ($examesOK ? "OK" : "FALHOU") . "</td></tr>";
    echo "<tr><td>Financeiro OK</td><td>" . ($financeiroOK ? "✅" : "❌") . "</td><td>" . ($financeiroOK ? "OK" : ($verificacaoFinanceira['motivo'] ?? 'FALHOU')) . "</td></tr>";
    echo "<tr><td>Não está matriculado</td><td>" . (!$jaMatriculado ? "✅" : "⚠️") . "</td><td>" . (!$jaMatriculado ? "OK" : "JÁ MATRICULADO") . "</td></tr>";
    
    $todosOK = $statusOK && $cfcOK && count($resultadoBase) > 0 && count($resultadoCompleto) > 0 && $examesOK && $financeiroOK && !$jaMatriculado;
    
    echo "<tr><td colspan='2'><strong>RESULTADO FINAL</strong></td><td>" . ($todosOK ? "<span class='ok'>✅ ALUNO DEVE APARECER NA LISTA</span>" : "<span class='fail'>❌ ALUNO NÃO DEVE APARECER (verifique critérios acima)</span>") . "</td></tr>";
    
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p class='fail'>❌ ERRO: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";

