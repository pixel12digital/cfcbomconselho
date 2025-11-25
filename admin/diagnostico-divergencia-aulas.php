<?php
/**
 * FASE 2 - GRADE DO CURSO - Diagnóstico de Divergência de Aulas
 * Compara a quantidade de aulas entre o painel admin e o painel do aluno
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Verificar autenticação
if (!isLoggedIn() || !hasPermission('admin')) {
    die('Acesso negado. Apenas administradores podem acessar este diagnóstico.');
}

$db = db();

// Parâmetros
$alunoId = $_GET['aluno_id'] ?? 167; // Charles por padrão
$turmaId = $_GET['turma_id'] ?? 16; // Turma A por padrão

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Diagnóstico - Divergência de Aulas</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { padding: 20px; background: #f5f5f5; }
        .card { margin-bottom: 20px; }
        .diff { background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin: 10px 0; }
        .match { background: #d1e7dd; padding: 10px; border-left: 4px solid #198754; margin: 10px 0; }
        table { font-size: 0.9rem; }
        .badge { font-size: 0.85rem; }
    </style>
</head>
<body>
<div class='container-fluid'>
    <h1 class='mb-4'>🔍 Diagnóstico - Divergência de Aulas</h1>
    <p class='text-muted'>Comparando dados entre Admin e Aluno para: Aluno ID {$alunoId}, Turma ID {$turmaId}</p>";

// 1. Buscar dados da turma
$turma = $db->fetch("
    SELECT id, nome, curso_tipo, data_inicio, data_fim
    FROM turmas_teoricas
    WHERE id = ?
", [$turmaId]);

if (!$turma) {
    die("<div class='alert alert-danger'>Turma não encontrada!</div></body></html>");
}

echo "<div class='card'>
    <div class='card-header bg-primary text-white'>
        <h5>📋 Dados da Turma</h5>
    </div>
    <div class='card-body'>
        <p><strong>Nome:</strong> {$turma['nome']}</p>
        <p><strong>Curso:</strong> {$turma['curso_tipo']}</p>
        <p><strong>Período:</strong> " . date('d/m/Y', strtotime($turma['data_inicio'])) . " a " . date('d/m/Y', strtotime($turma['data_fim'])) . "</p>
    </div>
</div>";

// 2. Contar aulas no ADMIN (sem filtro de data)
$totalAulasAdmin = $db->fetch("
    SELECT COUNT(*) as total
    FROM turma_aulas_agendadas
    WHERE turma_id = ? AND status != 'cancelada'
", [$turmaId]);
$totalAulasAdmin = (int)($totalAulasAdmin['total'] ?? 0);

// 3. Contar aulas por disciplina no ADMIN
$aulasPorDisciplinaAdmin = $db->fetchAll("
    SELECT 
        disciplina,
        COUNT(*) as total
    FROM turma_aulas_agendadas
    WHERE turma_id = ? AND status != 'cancelada'
    GROUP BY disciplina
    ORDER BY disciplina
", [$turmaId]);

// 4. Buscar disciplinas configuradas
$disciplinasConfig = $db->fetchAll("
    SELECT 
        disciplina,
        nome_disciplina,
        aulas_obrigatorias,
        ordem
    FROM disciplinas_configuracao
    WHERE curso_tipo = ? AND ativa = 1
    ORDER BY ordem ASC
", [$turma['curso_tipo']]);

// 5. Simular contagem do ALUNO (com filtro de período - próximos 30 dias)
$dataInicio = date('Y-m-d');
$dataFim = date('Y-m-d', strtotime('+30 days'));

$totalAulasAluno = $db->fetch("
    SELECT COUNT(*) as total
    FROM turma_aulas_agendadas
    WHERE turma_id = ?
    AND status IN ('agendada', 'realizada')
    AND data_aula >= ?
    AND data_aula <= ?
", [$turmaId, $dataInicio, $dataFim]);
$totalAulasAluno = (int)($totalAulasAluno['total'] ?? 0);

// 6. Contar aulas por disciplina no ALUNO (com filtro de período)
$aulasPorDisciplinaAluno = $db->fetchAll("
    SELECT 
        disciplina,
        COUNT(*) as total
    FROM turma_aulas_agendadas
    WHERE turma_id = ?
    AND status IN ('agendada', 'realizada')
    AND data_aula >= ?
    AND data_aula <= ?
    GROUP BY disciplina
    ORDER BY disciplina
", [$turmaId, $dataInicio, $dataFim]);

// 7. Criar mapa para comparação
$mapaDisciplinas = [];
foreach ($disciplinasConfig as $disc) {
    $mapaDisciplinas[$disc['disciplina']] = [
        'nome' => $disc['nome_disciplina'],
        'obrigatorias' => (int)$disc['aulas_obrigatorias'],
        'admin_agendadas' => 0,
        'aluno_periodo' => 0
    ];
}

foreach ($aulasPorDisciplinaAdmin as $item) {
    if (isset($mapaDisciplinas[$item['disciplina']])) {
        $mapaDisciplinas[$item['disciplina']]['admin_agendadas'] = (int)$item['total'];
    }
}

foreach ($aulasPorDisciplinaAluno as $item) {
    if (isset($mapaDisciplinas[$item['disciplina']])) {
        $mapaDisciplinas[$item['disciplina']]['aluno_periodo'] = (int)$item['total'];
    }
}

// 8. Exibir comparação
echo "<div class='card'>
    <div class='card-header bg-info text-white'>
        <h5>📊 Comparação Geral</h5>
    </div>
    <div class='card-body'>
        <table class='table table-bordered'>
            <thead>
                <tr>
                    <th>Fonte</th>
                    <th>Total de Aulas</th>
                    <th>Observação</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Admin (todas as aulas)</strong></td>
                    <td><span class='badge bg-primary'>{$totalAulasAdmin}</span></td>
                    <td>Sem filtro de data, apenas status != 'cancelada'</td>
                </tr>
                <tr>
                    <td><strong>Aluno (próximos 30 dias)</strong></td>
                    <td><span class='badge bg-warning'>{$totalAulasAluno}</span></td>
                    <td>Filtrado por período (próximos 30 dias) + status IN ('agendada', 'realizada')</td>
                </tr>
                <tr class='" . ($totalAulasAdmin == $totalAulasAluno ? 'table-success' : 'table-warning') . "'>
                    <td><strong>Diferença</strong></td>
                    <td><span class='badge bg-" . ($totalAulasAdmin == $totalAulasAluno ? 'success' : 'warning') . "'>{$totalAulasAdmin} - {$totalAulasAluno} = " . ($totalAulasAdmin - $totalAulasAluno) . "</span></td>
                    <td>" . ($totalAulasAdmin == $totalAulasAluno ? '✅ Valores iguais' : '⚠️ Divergência encontrada') . "</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>";

// 9. Comparação por disciplina
echo "<div class='card'>
    <div class='card-header bg-success text-white'>
        <h5>📚 Comparação por Disciplina</h5>
    </div>
    <div class='card-body'>
        <table class='table table-bordered table-sm'>
            <thead>
                <tr>
                    <th>Disciplina</th>
                    <th class='text-center'>Obrigatórias<br><small>(Config)</small></th>
                    <th class='text-center'>Admin<br><small>(Agendadas)</small></th>
                    <th class='text-center'>Aluno<br><small>(Próximos 30d)</small></th>
                    <th class='text-center'>Status</th>
                </tr>
            </thead>
            <tbody>";

foreach ($mapaDisciplinas as $discKey => $disc) {
    $obrigatorias = $disc['obrigatorias'];
    $adminAgendadas = $disc['admin_agendadas'];
    $alunoPeriodo = $disc['aluno_periodo'];
    
    $statusClass = 'table-success';
    $statusText = '✅ OK';
    $observacao = '';
    
    if ($adminAgendadas != $obrigatorias) {
        $statusClass = 'table-warning';
        $statusText = '⚠️ Divergência';
        $observacao = "Admin mostra {$adminAgendadas} agendadas, mas config exige {$obrigatorias} obrigatórias";
    }
    
    if ($alunoPeriodo < $adminAgendadas && $alunoPeriodo < $obrigatorias) {
        $statusClass = 'table-info';
        $statusText = 'ℹ️ Filtro período';
        $observacao = "Aluno vê menos por causa do filtro de período (próximos 30 dias)";
    }
    
    echo "<tr class='{$statusClass}'>
        <td><strong>{$disc['nome']}</strong></td>
        <td class='text-center'>{$obrigatorias}</td>
        <td class='text-center'>{$adminAgendadas}</td>
        <td class='text-center'>{$alunoPeriodo}</td>
        <td class='text-center'><span class='badge bg-" . ($statusClass == 'table-success' ? 'success' : ($statusClass == 'table-warning' ? 'warning' : 'info')) . "'>{$statusText}</span><br><small>{$observacao}</small></td>
    </tr>";
}

echo "</tbody>
        </table>
    </div>
</div>";

// 10. Verificar Grade do Curso
echo "<div class='card'>
    <div class='card-header bg-warning text-dark'>
        <h5>🎓 Grade do Curso (Como aparece para o aluno)</h5>
    </div>
    <div class='card-body'>
        <p class='text-muted'>A Grade do Curso deve mostrar <strong>aulas_obrigatorias</strong> da config como total, não o total agendado.</p>
        <table class='table table-bordered table-sm'>
            <thead>
                <tr>
                    <th>Disciplina</th>
                    <th class='text-center'>Total (Obrigatórias)</th>
                    <th class='text-center'>Total (Agendadas)</th>
                    <th class='text-center'>Qual deve usar?</th>
                </tr>
            </thead>
            <tbody>";

foreach ($mapaDisciplinas as $discKey => $disc) {
    $deveUsar = $disc['obrigatorias'] > 0 ? $disc['obrigatorias'] : $disc['admin_agendadas'];
    $correto = ($disc['obrigatorias'] > 0 && $disc['obrigatorias'] == $deveUsar) ? '✅' : '⚠️';
    
    echo "<tr>
        <td><strong>{$disc['nome']}</strong></td>
        <td class='text-center'>{$disc['obrigatorias']}</td>
        <td class='text-center'>{$disc['admin_agendadas']}</td>
        <td class='text-center'><strong>{$deveUsar}</strong> {$correto}</td>
    </tr>";
}

echo "</tbody>
        </table>
    </div>
</div>";

// 11. Verificar presenças do aluno
// FASE 2 - GRADE DO CURSO - Corrigir query de presenças (mesma abordagem de aluno/aulas.php)
try {
    // Primeiro, buscar todas as presenças do aluno na turma
    $presencas = $db->fetchAll("
        SELECT aula_id
        FROM turma_presencas
        WHERE aluno_id = ? 
        AND turma_id = ? 
        AND presente = 1
    ", [$alunoId, $turmaId]);
    
    $mapaPresencas = [];
    if (!empty($presencas)) {
        // Extrair IDs das aulas
        $aulaIds = array_column($presencas, 'aula_id');
        
        // Buscar disciplinas dessas aulas
        if (!empty($aulaIds)) {
            $placeholders = implode(',', array_fill(0, count($aulaIds), '?'));
            $aulasComPresenca = $db->fetchAll("
                SELECT 
                    disciplina,
                    COUNT(*) as aulas_com_presenca
                FROM turma_aulas_agendadas
                WHERE id IN ($placeholders)
                AND turma_id = ?
                AND status != 'cancelada'
                GROUP BY disciplina
            ", array_merge($aulaIds, [$turmaId]));
            
            foreach ($aulasComPresenca as $item) {
                $mapaPresencas[$item['disciplina']] = (int)$item['aulas_com_presenca'];
            }
        }
    }
} catch (Exception $e) {
    error_log("Erro ao buscar presenças do aluno: " . $e->getMessage());
    $mapaPresencas = [];
}

// $mapaPresencas já foi preenchido acima

echo "<div class='card'>
    <div class='card-header bg-secondary text-white'>
        <h5>✅ Presenças do Aluno</h5>
    </div>
    <div class='card-body'>
        <table class='table table-bordered table-sm'>
            <thead>
                <tr>
                    <th>Disciplina</th>
                    <th class='text-center'>Aulas com Presença</th>
                    <th class='text-center'>Total Obrigatório</th>
                    <th class='text-center'>Progresso</th>
                </tr>
            </thead>
            <tbody>";

foreach ($mapaDisciplinas as $discKey => $disc) {
    $presencas = $mapaPresencas[$discKey] ?? 0;
    $total = $disc['obrigatorias'] > 0 ? $disc['obrigatorias'] : $disc['admin_agendadas'];
    $percentual = $total > 0 ? round(($presencas / $total) * 100) : 0;
    
    echo "<tr>
        <td><strong>{$disc['nome']}</strong></td>
        <td class='text-center'>{$presencas}</td>
        <td class='text-center'>{$total}</td>
        <td class='text-center'>
            <div class='progress' style='height: 20px;'>
                <div class='progress-bar' role='progressbar' style='width: {$percentual}%'>{$percentual}%</div>
            </div>
        </td>
    </tr>";
}

echo "</tbody>
        </table>
    </div>
</div>";

echo "</div>
<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";

