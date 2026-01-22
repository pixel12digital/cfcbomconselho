<?php
/**
 * Script de Diagnóstico - Aluno 167 (Charles) para Matrícula em Turma Teórica
 * 
 * Este script verifica TODOS os critérios que impedem o aluno 167 de aparecer
 * na lista de alunos aptos para matrícula em turma teórica.
 * 
 * USO:
 * - Acesse via navegador: admin/tools/diagnostico-aluno-167-turma-teorica.php?turma_id=16
 * - Substitua 16 pelo ID da turma que você está tentando matricular
 * 
 * ⚠️ ATENÇÃO: Este é um script temporário de diagnóstico. Remova após uso.
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../includes/guards_exames.php';
require_once __DIR__ . '/../includes/FinanceiroAlunoHelper.php';

// Verificar se é admin (via navegador - banco remoto requer autenticação)
// Verificar se sessão já foi iniciada para evitar erro
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user = $_SESSION['user'] ?? null;
// Permitir execução mesmo sem login para diagnóstico (com aviso)
$isLoggedIn = ($user && ($user['tipo'] ?? '') === 'admin');
if (!$isLoggedIn) {
    echo "<div style='background:#fff3cd;padding:15px;border:2px solid #ffc107;margin:20px;border-radius:5px;'>";
    echo "<strong>⚠️ AVISO:</strong> Você não está logado como administrador. O diagnóstico será executado, mas algumas funcionalidades podem não funcionar corretamente.<br>";
    echo "Recomenda-se fazer login como admin antes de executar este script.";
    echo "</div>";
}

$db = Database::getInstance();
$alunoId = 167;
$turmaId = (int)($_GET['turma_id'] ?? 0);

if (!$turmaId) {
    die('❌ Erro: Parâmetro turma_id é obrigatório.<br><br>Use: <code>?turma_id=16</code> (substitua 16 pelo ID da turma)');
}

// Script funciona via navegador (banco remoto)
echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Diagnóstico Aluno 167 - Turma Teórica</title>";
echo "<style>body{font-family:Arial;margin:20px;background:#f5f5f5;} .container{background:white;padding:20px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);} table{border-collapse:collapse;margin:10px 0;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f0f0f0;font-weight:bold;} .ok{color:green;font-weight:bold;} .erro{color:red;font-weight:bold;} .warning{color:orange;font-weight:bold;} h1{color:#333;} h2{color:#555;border-bottom:2px solid #ddd;padding-bottom:10px;margin-top:30px;}</style>";
echo "</head><body>";
echo "<div class='container'>";
echo "<h1>🔍 Diagnóstico: Aluno 167 (Charles) - Matrícula em Turma Teórica {$turmaId}</h1>";
echo "<p><strong>⚠️ BANCO REMOTO:</strong> Conexão com Hostinger (auth-db803.hstgr.io)</p>";

// =====================================================
// 1. DADOS BÁSICOS DO ALUNO
// =====================================================
echo "<h2>1. Dados Básicos do Aluno 167</h2>";

$aluno = $db->fetch("
    SELECT id, nome, cpf, status, cfc_id
    FROM alunos
    WHERE id = ?
", [$alunoId]);

if (!$aluno) {
    echo "<p class='erro'>❌ ERRO: Aluno 167 não encontrado no banco de dados!</p>";
    die();
}

echo "<table>";
echo "<tr><th>Campo</th><th>Valor</th><th>Status</th></tr>";
echo "<tr><td>ID</td><td>{$aluno['id']}</td><td>-</td></tr>";
echo "<tr><td>Nome</td><td>{$aluno['nome']}</td><td>-</td></tr>";
echo "<tr><td>CPF</td><td>{$aluno['cpf']}</td><td>-</td></tr>";
echo "<tr><td>Status</td><td><strong>{$aluno['status']}</strong></td><td>" . 
     ($aluno['status'] === 'ativo' ? "<span class='ok'>✅ OK (ativo)</span>" : 
      "<span class='erro'><strong>❌ BLOQUEADOR CRÍTICO: Status '{$aluno['status']}' - não passará no filtro da query (exige 'ativo')</strong></span>") . 
     "</td></tr>";

// Adicionar alerta destacado se status não for 'ativo'
if ($aluno['status'] !== 'ativo') {
    echo "</table>";
    echo "<div style='background:#ffebee;padding:15px;border:3px solid #f44336;margin:15px 0;border-radius:5px;'>";
    echo "<h3 style='color:#c62828;margin-top:0;'>🚨 BLOQUEADOR CRÍTICO DETECTADO</h3>";
    echo "<p><strong>O aluno tem status '{$aluno['status']}', mas a query de candidatos exige status = 'ativo'.</strong></p>";
    echo "<p>Por isso, o aluno <strong>NÃO PASSARÁ</strong> no filtro inicial da query, independente de outros critérios (exames, financeiro, etc.).</p>";
    echo "<p><strong>Query que bloqueia:</strong></p>";
    echo "<pre style='background:#f5f5f5;padding:10px;border-radius:3px;'>";
    echo "WHERE a.status = 'ativo'  ← Filtro que bloqueia o aluno\n";
    echo "</pre>";
    echo "<p><strong>⚠️ SOLUÇÃO POSSÍVEL (verificar se é apropriado):</strong></p>";
    echo "<pre style='background:#f5f5f5;padding:10px;border-radius:3px;'>";
    echo "UPDATE alunos SET status = 'ativo' WHERE id = 167;\n";
    echo "</pre>";
    echo "<p><strong>⚠️ ATENÇÃO:</strong> Verifique a regra de negócio antes de alterar. Alunos 'concluidos' podem não dever aparecer em novas turmas.</p>";
    echo "</div>";
    echo "<table>";
    echo "<tr><th>Campo</th><th>Valor</th><th>Status</th></tr>";
}
echo "<tr><td>CFC ID</td><td>{$aluno['cfc_id']}</td><td>-</td></tr>";
echo "</table>";

// =====================================================
// 2. DADOS DA TURMA
// =====================================================
echo "<h2>2. Dados da Turma {$turmaId}</h2>";

$turma = $db->fetch("
    SELECT id, nome, cfc_id, curso_tipo, status
    FROM turmas_teoricas
    WHERE id = ?
", [$turmaId]);

if (!$turma) {
    echo "<div style='background:#ffebee;padding:15px;border:3px solid #f44336;margin:15px 0;border-radius:5px;'>";
    echo "<h3 style='color:#c62828;margin-top:0;'>⚠️ TURMA {$turmaId} NÃO ENCONTRADA</h3>";
    echo "<p><strong>A turma {$turmaId} não existe no banco de dados.</strong></p>";
    echo "<p>Isso confirma que a turma foi excluída. O diagnóstico continuará para verificar matrículas órfãs.</p>";
    echo "</div>";
    
    // Continuar diagnóstico mesmo sem a turma (ela pode ter sido excluída)
    $turma = ['id' => $turmaId, 'nome' => 'Turma Excluída', 'cfc_id' => null, 'curso_tipo' => null, 'status' => 'excluida'];
    $turmaNaoExiste = true;
} else {
    $turmaNaoExiste = false;
}

echo "<table>";
echo "<tr><th>Campo</th><th>Valor</th><th>Status</th></tr>";
echo "<tr><td>ID</td><td>{$turma['id']}</td><td>-</td></tr>";
echo "<tr><td>Nome</td><td>" . ($turma['nome'] ?? 'N/A') . "</td><td>" . ($turmaNaoExiste ? "<span class='erro'>❌ Turma Excluída</span>" : "-") . "</td></tr>";
if (!$turmaNaoExiste) {
    echo "<tr><td>CFC ID</td><td>{$turma['cfc_id']}</td><td>-</td></tr>";
    echo "<tr><td>Curso Tipo</td><td>" . ($turma['curso_tipo'] ?? 'N/A') . "</td><td>-</td></tr>";
    echo "<tr><td>Status</td><td>{$turma['status']}</td><td>-</td></tr>";
} else {
    echo "<tr><td colspan='3'><em>Dados da turma não disponíveis (turma foi excluída)</em></td></tr>";
}
echo "</table>";

// Verificar compatibilidade de CFC (só se a turma existir)
if (!$turmaNaoExiste && !empty($turma['cfc_id'])) {
    $cfcCompatible = ((int)$aluno['cfc_id'] === (int)$turma['cfc_id']);
    echo "<p><strong>Compatibilidade CFC:</strong> ";
    if ($cfcCompatible) {
        echo "<span class='ok'>✅ Aluno e Turma têm o mesmo CFC ({$aluno['cfc_id']})</span>";
    } else {
        echo "<span class='erro'>❌ PROBLEMA: Aluno tem CFC {$aluno['cfc_id']}, mas Turma tem CFC {$turma['cfc_id']} - aluno não passará no filtro da query</span>";
    }
    echo "</p>";
} else {
    echo "<p><strong>Compatibilidade CFC:</strong> <em>Não verificável (turma não existe mais)</em></p>";
}

// =====================================================
// 3. VERIFICAÇÃO DE MATRÍCULA NA TURMA
// =====================================================
echo "<h2>3. Verificação de Matrícula na Turma</h2>";

$matriculaNaTurma = $db->fetch("
    SELECT tm.*, tt.nome as turma_nome
    FROM turma_matriculas tm
    LEFT JOIN turmas_teoricas tt ON tm.turma_id = tt.id
    WHERE tm.aluno_id = ? AND tm.turma_id = ?
    AND tm.status IN ('matriculado', 'cursando')
", [$alunoId, $turmaId]);

if ($matriculaNaTurma) {
    echo "<p class='warning'>⚠️ O aluno 167 JÁ está matriculado nesta turma (status: {$matriculaNaTurma['status']})</p>";
    echo "<p>Por isso, o campo <code>status_matricula</code> será 'matriculado' e o aluno não aparecerá na lista (condição de elegibilidade exige 'disponivel').</p>";
    if (!$matriculaNaTurma['turma_nome']) {
        echo "<p class='erro'>🚨 ATENÇÃO: Esta turma parece ter sido EXCLUÍDA! (LEFT JOIN retornou NULL)</p>";
    }
} else {
    echo "<p class='ok'>✅ Aluno não está matriculado nesta turma - campo <code>status_matricula</code> será 'disponivel'</p>";
}

// CRÍTICO: Verificar matrículas órfãs (em turmas que foram excluídas)
echo "<h3>3.1. 🚨 Verificação de Matrículas Órfãs (Turmas Excluídas)</h3>";
$matriculasOrfas = $db->fetchAll("
    SELECT tm.*
    FROM turma_matriculas tm
    LEFT JOIN turmas_teoricas tt ON tm.turma_id = tt.id
    WHERE tm.aluno_id = 167
    AND tt.id IS NULL
    AND tm.status IN ('matriculado', 'cursando')
");

if (!empty($matriculasOrfas)) {
    echo "<div style='background:#ffebee;padding:15px;border:3px solid #f44336;margin:15px 0;border-radius:5px;'>";
    echo "<h3 style='color:#c62828;margin-top:0;'>🚨 PROBLEMA CRÍTICO DETECTADO!</h3>";
    echo "<p><strong>O aluno 167 possui " . count($matriculasOrfas) . " matrícula(s) ativa(s) em turma(s) que foram EXCLUÍDAS!</strong></p>";
    echo "<p>Isso pode estar impedindo o aluno de aparecer na lista de candidatos.</p>";
    echo "<table style='margin:10px 0;'>";
    echo "<tr><th>ID Matrícula</th><th>Turma ID (Excluída)</th><th>Status</th><th>Data Matrícula</th></tr>";
    foreach ($matriculasOrfas as $orf) {
        echo "<tr><td>{$orf['id']}</td><td>{$orf['turma_id']}</td><td class='erro'>{$orf['status']}</td><td>{$orf['data_matricula']}</td></tr>";
    }
    echo "</table>";
    echo "<p><strong>⚠️ SOLUÇÃO:</strong> Essas matrículas precisam ser atualizadas para status 'cancelada'.</p>";
    echo "<p>SQL sugerido (execute manualmente após verificação):</p>";
    echo "<pre style='background:#f5f5f5;padding:10px;border-radius:3px;'>";
    foreach ($matriculasOrfas as $orf) {
        echo "UPDATE turma_matriculas SET status = 'cancelada' WHERE id = {$orf['id']};\n";
    }
    echo "</pre>";
    echo "</div>";
} else {
    echo "<p class='ok'>✅ Nenhuma matrícula órfã encontrada.</p>";
}

// CRÍTICO: Verificar matrículas órfãs (em turmas que foram excluídas)
echo "<h3>3.1. Verificação de Matrículas Órfãs (Turmas Excluídas)</h3>";
$matriculasOrfas = $db->fetchAll("
    SELECT tm.*, tm.turma_id as turma_id_orf
    FROM turma_matriculas tm
    LEFT JOIN turmas_teoricas tt ON tm.turma_id = tt.id
    WHERE tm.aluno_id = 167
    AND tt.id IS NULL
    AND tm.status IN ('matriculado', 'cursando')
");

if (!empty($matriculasOrfas)) {
    echo "<div style='background:#ffebee;padding:15px;border:3px solid #f44336;margin:15px 0;border-radius:5px;'>";
    echo "<h3 style='color:#c62828;margin-top:0;'>🚨 PROBLEMA CRÍTICO: Matrículas Órfãs Detectadas!</h3>";
    echo "<p><strong>O aluno 167 possui " . count($matriculasOrfas) . " matrícula(s) ativa(s) em turma(s) que foram EXCLUÍDAS!</strong></p>";
    echo "<p>Isso pode estar causando problemas. A query da API filtra por turma_id específico, mas matrículas órfãs podem indicar inconsistência de dados.</p>";
    echo "<table style='margin:10px 0;'>";
    echo "<tr><th>ID Matrícula</th><th>Turma ID (Excluída)</th><th>Status</th><th>Data Matrícula</th></tr>";
    foreach ($matriculasOrfas as $orf) {
        echo "<tr><td>{$orf['id']}</td><td>{$orf['turma_id_orf']}</td><td class='erro'>{$orf['status']}</td><td>{$orf['data_matricula']}</td></tr>";
    }
    echo "</table>";
    echo "<p><strong>⚠️ AÇÃO NECESSÁRIA:</strong> Essas matrículas precisam ser atualizadas para status 'cancelada' ou 'concluida' para limpar os dados.</p>";
    echo "<p>SQL sugerido (execute manualmente após verificação):</p>";
    echo "<pre style='background:#f5f5f5;padding:10px;border-radius:3px;'>";
    foreach ($matriculasOrfas as $orf) {
        echo "UPDATE turma_matriculas SET status = 'cancelada', atualizado_em = NOW() WHERE id = {$orf['id']};\n";
    }
    echo "</pre>";
    echo "</div>";
} else {
    echo "<p class='ok'>✅ Nenhuma matrícula órfã encontrada (todas as matrículas estão em turmas existentes).</p>";
}

// Verificar TODAS as matrículas do aluno (histórico completo)
echo "<h3>3.2. Histórico Completo de Matrículas do Aluno 167</h3>";
$todasMatriculas = $db->fetchAll("
    SELECT 
        tm.*,
        tt.id as turma_existe,
        tt.nome as turma_nome,
        tt.status as turma_status,
        CASE 
            WHEN tt.id IS NULL THEN '❌ TURMA EXCLUÍDA'
            ELSE '✅ Turma existe'
        END as situacao
    FROM turma_matriculas tm
    LEFT JOIN turmas_teoricas tt ON tm.turma_id = tt.id
    WHERE tm.aluno_id = 167
    ORDER BY tm.data_matricula DESC
");

if (empty($todasMatriculas)) {
    echo "<p class='ok'>✅ Aluno 167 não possui nenhuma matrícula registrada.</p>";
} else {
    echo "<table>";
    echo "<tr><th>ID</th><th>Turma ID</th><th>Turma Existe?</th><th>Nome Turma</th><th>Status Matrícula</th><th>Data</th><th>Situação</th></tr>";
    foreach ($todasMatriculas as $mat) {
        $rowClass = ($mat['turma_existe'] ? '' : 'erro');
        echo "<tr class='{$rowClass}'>";
        echo "<td>{$mat['id']}</td>";
        echo "<td>{$mat['turma_id']}</td>";
        echo "<td>" . ($mat['turma_existe'] ? "<span class='ok'>✅</span>" : "<span class='erro'>❌</span>") . "</td>";
        echo "<td>" . ($mat['turma_nome'] ?? '<em>N/A</em>') . "</td>";
        echo "<td>{$mat['status']}</td>";
        echo "<td>{$mat['data_matricula']}</td>";
        echo "<td><strong>{$mat['situacao']}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
}

// =====================================================
// 4. VERIFICAÇÃO DE EXAMES
// =====================================================
echo "<h2>4. Verificação de Exames</h2>";

$exames = $db->fetchAll("
    SELECT id, tipo, status, resultado, data_resultado, data_agendada
    FROM exames
    WHERE aluno_id = ?
    AND tipo IN ('medico', 'psicotecnico')
    ORDER BY tipo, data_agendada DESC
", [$alunoId]);

echo "<table>";
echo "<tr><th>Tipo</th><th>Status</th><th>Resultado</th><th>Data Resultado</th><th>Data Agendada</th></tr>";

$medico = null;
$psicotecnico = null;

foreach ($exames as $exame) {
    if ($exame['tipo'] === 'medico' && !$medico) {
        $medico = $exame;
    } elseif ($exame['tipo'] === 'psicotecnico' && !$psicotecnico) {
        $psicotecnico = $exame;
    }
    
    $statusClass = ($exame['resultado'] === 'apto' || $exame['resultado'] === 'aprovado') ? 'ok' : 'warning';
    echo "<tr>";
    echo "<td>{$exame['tipo']}</td>";
    echo "<td>{$exame['status']}</td>";
    echo "<td class='{$statusClass}'>{$exame['resultado']}</td>";
    echo "<td>" . ($exame['data_resultado'] ?? 'N/A') . "</td>";
    echo "<td>" . ($exame['data_agendada'] ?? 'N/A') . "</td>";
    echo "</tr>";
}

echo "</table>";

// Verificar usando função centralizada
$examesOK = GuardsExames::alunoComExamesOkParaTeoricas($alunoId);

echo "<p><strong>Validação via GuardsExames::alunoComExamesOkParaTeoricas():</strong> ";
if ($examesOK) {
    echo "<span class='ok'>✅ TRUE - Exames OK</span>";
} else {
    echo "<span class='erro'>❌ FALSE - Exames não OK</span>";
    
    // Diagnóstico detalhado
    $medicoTemResultado = !empty($medico) && !empty($medico['resultado']) && $medico['resultado'] !== 'pendente';
    $psicotecnicoTemResultado = !empty($psicotecnico) && !empty($psicotecnico['resultado']) && $psicotecnico['resultado'] !== 'pendente';
    
    $medicoApto = $medicoTemResultado && in_array($medico['resultado'] ?? '', ['apto', 'aprovado']);
    $psicotecnicoApto = $psicotecnicoTemResultado && in_array($psicotecnico['resultado'] ?? '', ['apto', 'aprovado']);
    
    echo "<ul>";
    echo "<li>Médico tem resultado: " . ($medicoTemResultado ? '✅' : '❌') . "</li>";
    echo "<li>Médico é apto: " . ($medicoApto ? '✅' : '❌') . " (resultado: " . ($medico['resultado'] ?? 'N/A') . ")</li>";
    echo "<li>Psicotécnico tem resultado: " . ($psicotecnicoTemResultado ? '✅' : '❌') . "</li>";
    echo "<li>Psicotécnico é apto: " . ($psicotecnicoApto ? '✅' : '❌') . " (resultado: " . ($psicotecnico['resultado'] ?? 'N/A') . ")</li>";
    echo "</ul>";
}
echo "</p>";

// =====================================================
// 5. VERIFICAÇÃO FINANCEIRA
// =====================================================
echo "<h2>5. Verificação Financeira</h2>";

$verificacaoFinanceira = FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno($alunoId);
$financeiroOK = $verificacaoFinanceira['liberado'];

// Buscar faturas para exibir
$faturas = $db->fetchAll("
    SELECT id, valor_total, data_vencimento, status
    FROM financeiro_faturas
    WHERE aluno_id = ?
    AND status != 'cancelada'
    ORDER BY data_vencimento ASC
", [$alunoId]);

echo "<p><strong>Validação via FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno():</strong> ";
if ($financeiroOK) {
    echo "<span class='ok'>✅ TRUE - Financeiro OK</span>";
} else {
    echo "<span class='erro'>❌ FALSE - Financeiro não OK</span>";
    echo "<p>Motivo: <strong>{$verificacaoFinanceira['motivo']}</strong></p>";
}
echo "</p>";

echo "<p>Status: <strong>{$verificacaoFinanceira['status']}</strong></p>";

if (!empty($faturas)) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Valor</th><th>Vencimento</th><th>Status</th></tr>";
    foreach ($faturas as $fatura) {
        $hoje = date('Y-m-d');
        $vencida = ($fatura['data_vencimento'] < $hoje && in_array($fatura['status'], ['aberta', 'parcial']));
        $rowClass = $vencida ? 'erro' : '';
        echo "<tr class='{$rowClass}'>";
        echo "<td>{$fatura['id']}</td>";
        echo "<td>R$ " . number_format($fatura['valor_total'], 2, ',', '.') . "</td>";
        echo "<td>{$fatura['data_vencimento']}</td>";
        echo "<td>{$fatura['status']}" . ($vencida ? ' ⚠️ VENCIDA' : '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='warning'>⚠️ Nenhuma fatura encontrada (não cancelada)</p>";
}

// =====================================================
// 6. SIMULAÇÃO DA QUERY DE CANDIDATOS
// =====================================================
echo "<h2>6. Simulação da Query de Candidatos</h2>";

if ($turmaNaoExiste || empty($turma['cfc_id'])) {
    echo "<p class='warning'>⚠️ Não é possível simular a query completa - turma não existe ou não tem CFC definido.</p>";
    echo "<p>Mas podemos verificar se o aluno passaria nos filtros básicos:</p>";
    
    // Verificar apenas se o aluno é 'ativo'
    if ($aluno['status'] !== 'ativo') {
        echo "<div style='background:#ffebee;padding:15px;border:2px solid #f44336;margin:10px 0;border-radius:5px;'>";
        echo "<p class='erro'><strong>❌ PROBLEMA CRÍTICO:</strong> O aluno tem status '{$aluno['status']}', mas a query exige 'ativo'.</p>";
        echo "<p>Por isso, o aluno <strong>NÃO passará</strong> no filtro da query inicial, independente de outros critérios.</p>";
        echo "<p><strong>SQL para corrigir (se apropriado):</strong></p>";
        echo "<pre style='background:#f5f5f5;padding:10px;border-radius:3px;'>";
        echo "UPDATE alunos SET status = 'ativo' WHERE id = 167;\n";
        echo "</pre>";
        echo "<p><strong>⚠️ ATENÇÃO:</strong> Verifique se é apropriado mudar o status do aluno de 'concluido' para 'ativo' antes de executar.</p>";
        echo "</div>";
    }
    
    $candidatos = [];
} else {
    $cfcIdTurma = (int)$turma['cfc_id'];
    
    $candidatos = $db->fetchAll("
        SELECT 
            a.id,
            a.nome,
            a.status as status_aluno,
            a.cfc_id as aluno_cfc_id,
            CASE 
                WHEN tm.id IS NOT NULL THEN 'matriculado'
                ELSE 'disponivel'
            END as status_matricula
        FROM alunos a
        JOIN cfcs c ON a.cfc_id = c.id
        LEFT JOIN turma_matriculas tm ON a.id = tm.aluno_id 
            AND tm.turma_id = ? 
            AND tm.status IN ('matriculado', 'cursando')
        WHERE a.id = ?
            AND a.status = 'ativo'
            AND a.cfc_id = ?
    ", [$turmaId, $alunoId, $cfcIdTurma]);
}

if (!$turmaNaoExiste && !empty($turma['cfc_id'])) {
    echo "<p><strong>Query executada:</strong></p>";
    echo "<pre>";
    echo "SELECT ... FROM alunos a ...\n";
    echo "WHERE a.id = {$alunoId}\n";
    echo "  AND a.status = 'ativo'  ← PROBLEMA: aluno tem status '{$aluno['status']}'\n";
    echo "  AND a.cfc_id = {$cfcIdTurma}\n";
    echo "</pre>";
}

if (empty($candidatos)) {
    echo "<p class='erro'>❌ Aluno 167 NÃO foi retornado pela query de candidatos brutos</p>";
    echo "<p><strong>Motivos possíveis:</strong></p>";
    echo "<ul>";
    if ($aluno['status'] !== 'ativo') {
        echo "<li class='erro'>❌ Status do aluno é '{$aluno['status']}' (esperado: 'ativo')</li>";
    }
    if ((int)$aluno['cfc_id'] !== $cfcIdTurma) {
        echo "<li class='erro'>❌ CFC do aluno ({$aluno['cfc_id']}) é diferente do CFC da turma ({$cfcIdTurma})</li>";
    }
    echo "</ul>";
} else {
    $candidato = $candidatos[0];
    echo "<p class='ok'>✅ Aluno 167 FOI retornado pela query</p>";
    echo "<table>";
    echo "<tr><th>Campo</th><th>Valor</th></tr>";
    echo "<tr><td>ID</td><td>{$candidato['id']}</td></tr>";
    echo "<tr><td>Nome</td><td>{$candidato['nome']}</td></tr>";
    echo "<tr><td>Status Aluno</td><td>{$candidato['status_aluno']}</td></tr>";
    echo "<tr><td>CFC ID</td><td>{$candidato['aluno_cfc_id']}</td></tr>";
    echo "<tr><td>Status Matrícula</td><td>{$candidato['status_matricula']}</td></tr>";
    echo "</table>";
}

// =====================================================
// 7. SIMULAÇÃO DA ELEGIBILIDADE
// =====================================================
echo "<h2>7. Simulação da Elegibilidade</h2>";

if (empty($candidatos)) {
    echo "<p class='erro'>❌ Não é possível calcular elegibilidade - aluno não passou na query inicial</p>";
} else {
    $candidato = $candidatos[0];
    $statusMatriculaOK = ($candidato['status_matricula'] === 'disponivel');
    $categoriaOK = true; // Sempre true por enquanto
    
    $elegivel = ($examesOK && $financeiroOK && $categoriaOK && $statusMatriculaOK);
    
    echo "<table>";
    echo "<tr><th>Critério</th><th>Valor</th><th>Status</th></tr>";
    echo "<tr><td>Exames OK</td><td>" . ($examesOK ? 'TRUE' : 'FALSE') . "</td><td>" . 
         ($examesOK ? "<span class='ok'>✅</span>" : "<span class='erro'>❌</span>") . "</td></tr>";
    echo "<tr><td>Financeiro OK</td><td>" . ($financeiroOK ? 'TRUE' : 'FALSE') . "</td><td>" . 
         ($financeiroOK ? "<span class='ok'>✅</span>" : "<span class='erro'>❌</span>") . "</td></tr>";
    echo "<tr><td>Categoria OK</td><td>TRUE</td><td><span class='ok'>✅</span></td></tr>";
    echo "<tr><td>Status Matrícula = 'disponivel'</td><td>{$candidato['status_matricula']}</td><td>" . 
         ($statusMatriculaOK ? "<span class='ok'>✅</span>" : "<span class='erro'>❌</span>") . "</td></tr>";
    echo "<tr><td><strong>ELEGÍVEL</strong></td><td><strong>" . ($elegivel ? 'TRUE' : 'FALSE') . "</strong></td><td>" . 
         ($elegivel ? "<span class='ok'>✅ SIM</span>" : "<span class='erro'>❌ NÃO</span>") . "</td></tr>";
    echo "</table>";
    
    if (!$elegivel) {
        echo "<p><strong>Motivos da exclusão:</strong></p>";
        echo "<ul>";
        if (!$examesOK) {
            echo "<li class='erro'>❌ Exames não estão OK</li>";
        }
        if (!$financeiroOK) {
            echo "<li class='erro'>❌ Financeiro não está OK: {$verificacaoFinanceira['motivo']}</li>";
        }
        if (!$statusMatriculaOK) {
            echo "<li class='erro'>❌ Aluno já está matriculado nesta turma (status_matricula = '{$candidato['status_matricula']}')</li>";
        }
        echo "</ul>";
    }
}

// =====================================================
// 8. RESUMO E CONCLUSÃO
// =====================================================
echo "<h2>8. Resumo e Conclusão</h2>";

echo "<table>";
echo "<tr><th>Verificação</th><th>Resultado</th></tr>";
echo "<tr><td>Aluno existe</td><td>" . ($aluno ? "<span class='ok'>✅ SIM</span>" : "<span class='erro'>❌ NÃO</span>") . "</td></tr>";
echo "<tr><td>Turma existe</td><td>" . ($turma ? "<span class='ok'>✅ SIM</span>" : "<span class='erro'>❌ NÃO</span>") . "</td></tr>";
echo "<tr><td>Aluno está ativo</td><td>" . ($aluno['status'] === 'ativo' ? "<span class='ok'>✅ SIM</span>" : "<span class='erro'>❌ NÃO ({$aluno['status']})</span>") . "</td></tr>";
echo "<tr><td>CFC compatível</td><td>" . ($cfcCompatible ? "<span class='ok'>✅ SIM</span>" : "<span class='erro'>❌ NÃO (Aluno: {$aluno['cfc_id']}, Turma: {$turma['cfc_id']})</span>") . "</td></tr>";
echo "<tr><td>Passa na query inicial</td><td>" . (!empty($candidatos) ? "<span class='ok'>✅ SIM</span>" : "<span class='erro'>❌ NÃO</span>") . "</td></tr>";
echo "<tr><td>Exames OK</td><td>" . ($examesOK ? "<span class='ok'>✅ SIM</span>" : "<span class='erro'>❌ NÃO</span>") . "</td></tr>";
echo "<tr><td>Financeiro OK</td><td>" . ($financeiroOK ? "<span class='ok'>✅ SIM</span>" : "<span class='erro'>❌ NÃO</span>") . "</td></tr>";
echo "<tr><td>Não está matriculado</td><td>" . (!$matriculaNaTurma ? "<span class='ok'>✅ SIM</span>" : "<span class='warning'>⚠️ JÁ ESTÁ MATRICULADO</span>") . "</td></tr>";

if (!empty($candidatos)) {
    $candidato = $candidatos[0];
    $statusMatriculaOK = ($candidato['status_matricula'] === 'disponivel');
    $categoriaOK = true;
    $elegivel = ($examesOK && $financeiroOK && $categoriaOK && $statusMatriculaOK);
    echo "<tr><td><strong>ELEGÍVEL PARA LISTA</strong></td><td>" . 
         ($elegivel ? "<span class='ok'><strong>✅ SIM</strong></span>" : "<span class='erro'><strong>❌ NÃO</strong></span>") . 
         "</td></tr>";
} else {
    echo "<tr><td><strong>ELEGÍVEL PARA LISTA</strong></td><td><span class='erro'><strong>❌ NÃO (não passou na query inicial)</strong></span></td></tr>";
}

echo "</table>";

echo "</div>"; // fecha container
echo "</body></html>";
?>

