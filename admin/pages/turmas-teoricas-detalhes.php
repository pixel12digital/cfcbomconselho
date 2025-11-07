<?php
/**
 * Página de Detalhes da Turma Teórica
 * Exibe informações completas da turma selecionada
 */

// Verificar se há turma_id
if (!$turmaId) {
    echo '<div class="alert alert-danger">Turma não especificada.</div>';
    return;
}

// Obter dados da turma
$resultadoTurma = $turmaManager->obterTurma($turmaId);
if (!$resultadoTurma['sucesso']) {
    echo '<div class="alert alert-danger">Erro ao carregar turma: ' . $resultadoTurma['mensagem'] . '</div>';
    return;
}

$turma = $resultadoTurma['dados'];

$totalAulasObrigatorias = 0;
$totalAulasAgendadas = 0;

if (!empty($progressoDisciplinas) && is_array($progressoDisciplinas)) {
    foreach ($progressoDisciplinas as $disciplinaProgresso) {
        $totalAulasObrigatorias += (int)($disciplinaProgresso['aulas_obrigatorias'] ?? 0);
        $totalAulasAgendadas += (int)($disciplinaProgresso['aulas_agendadas'] ?? 0);
    }
}

if ($totalAulasObrigatorias <= 0) {
    $percentualAgendamento = $totalAulasAgendadas > 0 ? 100 : 0;
} else {
    $percentualAgendamento = (int)round(($totalAulasAgendadas / $totalAulasObrigatorias) * 100);
}
$percentualAgendamento = max(0, min(100, $percentualAgendamento));

$agendamentoStatus = [
    'label' => 'Pendente',
    'background' => '#f1f3f5',
    'textColor' => '#495057',
];

if ($percentualAgendamento >= 100) {
    $agendamentoStatus = [
        'label' => 'Concluída',
        'background' => '#e6f4ea',
        'textColor' => '#1e7d3c',
    ];
} elseif ($percentualAgendamento > 0) {
    $agendamentoStatus = [
        'label' => 'Em andamento',
        'background' => '#fff4e5',
        'textColor' => '#8a6100',
    ];
}

$agendamentoStatus['percent'] = $percentualAgendamento;

// Obter progresso das disciplinas
$progressoDisciplinas = $turmaManager->obterProgressoDisciplinas($turmaId);

// Obter aulas agendadas (excluindo canceladas)
try {
    $aulasAgendadas = $db->fetchAll(
        "SELECT * FROM turma_aulas_agendadas WHERE turma_id = ? AND status != 'cancelada' ORDER BY data_aula, hora_inicio",
        [$turmaId]
    );
} catch (Exception $e) {
    $aulasAgendadas = [];
}

// Calcular estatísticas
$totalAulas = count($aulasAgendadas);
$totalMinutos = array_sum(array_column($aulasAgendadas, 'duracao_minutos'));
$totalHoras = round($totalMinutos / 60, 1);

// Obter alunos matriculados (se a tabela existir)
try {
    $alunosMatriculados = $db->fetchAll(
        "SELECT COUNT(*) as total FROM turma_alunos WHERE turma_id = ?",
        [$turmaId]
    );
    $totalAlunos = $alunosMatriculados[0]['total'] ?? 0;
} catch (Exception $e) {
    $totalAlunos = 0;
}
?>

<!-- Cabeçalho -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div>
        <h3 style="margin: 0; color: var(--primary-dark); display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-sliders-h icon icon-24"></i>
            Gerenciar Turma
        </h3>
        <p style="margin: 5px 0 0 0; color: var(--gray-600);">
            Ajuste dados, alunos e calendário desta turma teórica.
        </p>
    </div>
    <a href="?page=turmas-teoricas" class="btn-secondary">
        ← Voltar para Lista
    </a>
</div>

<!-- Informações Básicas -->
<div id="edit-scope-basicas" style="background: white; border-radius: 12px; padding: 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h4 style="color: var(--primary-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
        <i class="fas fa-graduation-cap icon icon-24"></i>Informações Básicas
    </h4>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <div>
            <h5 style="color: var(--primary-dark); font-size: 1.5rem; margin-bottom: 10px;">
                <?= htmlspecialchars($turma['nome']) ?>
            </h5>
            <p style="color: var(--gray-600); margin-bottom: 15px;">
                <?= htmlspecialchars($turma['curso_nome'] ?? 'Curso não especificado') ?>
            </p>
            
            <div style="display: flex; flex-direction: column; gap: 4px; margin-bottom: 10px;">
                <span style="font-size: 0.85rem; font-weight: 600; color: var(--gray-600); text-transform: uppercase;">
                    Status do agendamento
                </span>
                <div style="display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <span style="background: <?= $agendamentoStatus['background'] ?>; color: <?= $agendamentoStatus['textColor'] ?>; padding: 6px 14px; border-radius: 999px; font-size: 0.95rem; font-weight: 600;">
                        <?= htmlspecialchars($agendamentoStatus['label']) ?> - <?= $agendamentoStatus['percent'] ?>% agendado
                    </span>
                </div>
                <small style="color: var(--gray-500); font-size: 0.75rem;">
                    Indicador baseado nas aulas agendadas em relação ao total obrigatório da turma.
                </small>
            </div>
        </div>
        
        <div style="display: grid; gap: 10px;">
            <div class="info-row">
                <i class="fas fa-building icon icon-20 icon-brand"></i>
                <span><strong>Sala:</strong> <?= htmlspecialchars($turma['sala_nome'] ?? 'Não definida') ?></span>
            </div>
            
            <div class="info-row">
                <i class="fas fa-calendar-alt icon icon-20 icon-brand"></i>
                <span><strong>Período:</strong> <?= date('d/m/Y', strtotime($turma['data_inicio'])) ?> - <?= date('d/m/Y', strtotime($turma['data_fim'])) ?></span>
            </div>
            
            <div class="info-row">
                <i class="fas fa-users icon icon-20 icon-brand"></i>
                <span><strong>Alunos:</strong> <?= $totalAlunos ?>/<?= $turma['max_alunos'] ?></span>
            </div>
            
            <div class="info-row">
                <i class="fas fa-clock icon icon-20 icon-brand"></i>
                <span><strong>Modalidade:</strong> <?= ucfirst($turma['modalidade']) ?></span>
            </div>
        </div>
    </div>
    
    <?php if (!empty($turma['observacoes'])): ?>
    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
        <h6 style="color: var(--primary-dark); margin-bottom: 10px;">Observações:</h6>
        <p style="color: var(--gray-600); margin: 0;"><?= nl2br(htmlspecialchars($turma['observacoes'])) ?></p>
    </div>
    <?php endif; ?>
</div>

<!-- Estatísticas -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px;">
    <div style="background: linear-gradient(135deg, #023A8D, #0056b3); color: white; padding: 20px; border-radius: 12px; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold;"><?= $totalAulas ?></div>
        <div style="font-size: 0.9rem; opacity: 0.9;">Aulas Agendadas</div>
    </div>
    
    <div style="background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 20px; border-radius: 12px; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold;"><?= $totalHoras ?>h</div>
        <div style="font-size: 0.9rem; opacity: 0.9;">Carga Horária Total</div>
    </div>
    
    <div style="background: linear-gradient(135deg, #F7931E, #ff8c00); color: white; padding: 20px; border-radius: 12px; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold;"><?= $turma['carga_horaria_total'] ?>h</div>
        <div style="font-size: 0.9rem; opacity: 0.9;">Carga Horária Obrigatória</div>
    </div>
    
    <div style="background: linear-gradient(135deg, #6f42c1, #8e44ad); color: white; padding: 20px; border-radius: 12px; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold;"><?= $totalAlunos ?></div>
        <div style="font-size: 0.9rem; opacity: 0.9;">Alunos Matriculados</div>
    </div>
</div>

<!-- Progresso das Disciplinas -->
<?php if (!empty($progressoDisciplinas)): ?>
<div style="background: white; border-radius: 12px; padding: 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h4 style="color: var(--primary-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
        <i class="fas fa-chart-line icon icon-24"></i>Progresso das Disciplinas
    </h4>
    
    <div style="display: grid; gap: 15px;">
        <?php foreach ($progressoDisciplinas as $disciplina): ?>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid var(--primary-dark);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h6 style="margin: 0; color: var(--primary-dark);"><?= htmlspecialchars($disciplina['nome_disciplina'] ?? $disciplina['disciplina']) ?></h6>
                <span style="font-size: 0.9rem; color: var(--gray-600);">
                    <?= $disciplina['aulas_agendadas'] ?? 0 ?>/<?= $disciplina['aulas_obrigatorias'] ?? 0 ?> aulas
                </span>
            </div>
            
            <!-- Barra de progresso -->
            <?php 
            $percentual = 0;
            if (!empty($disciplina['aulas_obrigatorias']) && $disciplina['aulas_obrigatorias'] > 0) {
                $percentual = round((($disciplina['aulas_agendadas'] ?? 0) / $disciplina['aulas_obrigatorias']) * 100);
            }
            ?>
            <div style="width: 100%; height: 8px; background: #e9ecef; border-radius: 4px; overflow: hidden;">
                <div style="width: <?= $percentual ?>%; height: 100%; background: linear-gradient(90deg, var(--primary-dark), #F7931E); transition: width 0.3s ease;"></div>
            </div>
            
            <div style="margin-top: 8px; font-size: 0.8rem; color: var(--gray-600);">
                <?= $percentual ?>% completo
                <?php if (!empty($disciplina['aulas_faltantes']) && $disciplina['aulas_faltantes'] > 0): ?>
                    - <span style="color: var(--danger-color);">Faltam <?= $disciplina['aulas_faltantes'] ?> aulas</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Aulas Agendadas -->
<?php if (!empty($aulasAgendadas)): ?>
<div style="background: white; border-radius: 12px; padding: 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h4 style="color: var(--primary-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
        <i class="fas fa-calendar-check icon icon-24"></i>Aulas Agendadas (<?= count($aulasAgendadas) ?>)
    </h4>
    
    <div style="display: grid; gap: 15px;">
        <?php foreach ($aulasAgendadas as $aula): ?>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid var(--success-color);">
            <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 10px;">
                <div style="flex: 1; min-width: 200px;">
                    <h6 style="margin: 0 0 5px 0; color: var(--primary-dark);">
                        <?= htmlspecialchars($aula['nome_aula']) ?>
                    </h6>
                    <div style="color: var(--gray-600); font-size: 0.9rem;">
                        <div><strong>Disciplina:</strong> <?= htmlspecialchars($aula['disciplina']) ?></div>
                        <div><strong>Data:</strong> <?= date('d/m/Y', strtotime($aula['data_aula'])) ?></div>
                        <div><strong>Horário:</strong> <?= $aula['hora_inicio'] ?> - <?= $aula['hora_fim'] ?></div>
                        <div><strong>Duração:</strong> <?= $aula['duracao_minutos'] ?> minutos</div>
                    </div>
                </div>
                
                <div style="text-align: right;">
                    <span style="background: <?= $aula['status'] === 'agendada' ? 'var(--primary-dark)' : ($aula['status'] === 'realizada' ? 'var(--success-color)' : 'var(--danger-color)') ?>; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">
                        <?= ucfirst($aula['status']) ?>
                    </span>
                </div>
            </div>
            
            <?php if (!empty($aula['observacoes'])): ?>
            <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #dee2e6;">
                <small style="color: var(--gray-600);"><strong>Observações:</strong> <?= htmlspecialchars($aula['observacoes']) ?></small>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Ações -->
<div style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h4 style="color: var(--primary-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
        <i class="fas fa-tools icon icon-24"></i>Ações Disponíveis
    </h4>
    
    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
        <?php if ($turma['status'] === 'criando' || $turma['status'] === 'agendando'): ?>
        <a href="?page=turmas-teoricas&acao=agendar&step=2&turma_id=<?= $turma['id'] ?>" class="btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
            <i class="fas fa-calendar-plus icon icon-20"></i>Continuar Agendamento
        </a>
        <?php endif; ?>
        
        <?php if ($turma['status'] === 'completa'): ?>
        <a href="?page=turmas-teoricas&acao=alunos&step=4&turma_id=<?= $turma['id'] ?>" class="btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
            <i class="fas fa-users icon icon-20"></i>Gerenciar Alunos
        </a>
        <?php endif; ?>
        
        <?php if ($turma['status'] === 'criando' || $turma['status'] === 'agendando'): ?>
        <a href="?page=turmas-teoricas&acao=editar&step=1&turma_id=<?= $turma['id'] ?>" class="btn-primary" style="background: var(--warning-color); border-color: var(--warning-color); display: inline-flex; align-items: center; gap: 8px;">
            <i class="fas fa-edit icon icon-20"></i>Editar Turma
        </a>
        <?php endif; ?>
        
        
        <button onclick="window.print()" class="btn-secondary" style="display: inline-flex; align-items: center; gap: 8px;">
            <i class="fas fa-print icon icon-20"></i>Imprimir Detalhes
        </button>
        
        <?php if (isset($isAdmin) && $isAdmin): ?>
        <button onclick="excluirTurmaCompleta(<?= $turma['id'] ?>, '<?= htmlspecialchars(addslashes($turma['nome'])) ?>')" 
                class="btn-danger" 
                style="background: var(--danger-color); color: #fff; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fas fa-trash-alt icon icon-20"></i>Excluir Turma Completamente
        </button>
        <?php endif; ?>
    </div>
</div>

<style>
.icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    font-size: 24px;
    color: inherit;
}

.icon-20 { font-size: 20px; }
.icon-24 { font-size: 24px; }

.icon-brand { color: var(--primary-dark); }
.icon-muted { color: var(--gray-500); }
.icon-success { color: var(--success-color); }
.icon-warning { color: var(--warning-color); }
.icon-danger { color: var(--danger-color); }

.info-row {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--gray-700);
    font-size: 0.95rem;
}

.info-row strong {
    color: var(--gray-800);
}

#edit-scope-basicas .inline-edit-target {
    position: relative;
}

#edit-scope-basicas .inline-edit-icon,
#edit-scope-basicas .edit-icon {
    opacity: 0 !important;
    visibility: hidden !important;
    transition: opacity 0.18s ease, visibility 0.18s ease;
    color: var(--primary-dark);
}

#edit-scope-basicas .inline-edit-target:hover .inline-edit-icon,
#edit-scope-basicas .inline-edit-target:hover .edit-icon,
#edit-scope-basicas .inline-edit-target:focus-within .inline-edit-icon,
#edit-scope-basicas .inline-edit-target:focus-within .edit-icon,
#edit-scope-basicas .inline-edit-target.show-inline-icon .inline-edit-icon,
#edit-scope-basicas .inline-edit-target.show-inline-icon .edit-icon {
    opacity: 1 !important;
    visibility: visible !important;
}

@media print {
    .btn-secondary, .btn-primary {
        display: none !important;
    }
    
    body {
        background: white !important;
    }
    
    div[style*="box-shadow"] {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const scope = document.getElementById('edit-scope-basicas');
    if (!scope) {
        return;
    }

    const iconSelectors = 'i.fa-edit, i.fa-pen, i.fa-pencil, i.fa-pencil-alt';
    const editIcons = scope.querySelectorAll(iconSelectors);
    const trackedTargets = new Set();

    editIcons.forEach(icon => {
        icon.classList.add('inline-edit-icon');
        icon.setAttribute('aria-hidden', 'true');

        const target = icon.closest('.inline-edit, [data-field], .info-row, span, div');
        if (!target) {
            return;
        }

        target.classList.add('inline-edit-target');
        trackedTargets.add(target);
    });

    trackedTargets.forEach(target => {
        const showIcon = () => target.classList.add('show-inline-icon');
        const hideIcon = () => target.classList.remove('show-inline-icon');

        target.addEventListener('mouseenter', showIcon);
        target.addEventListener('mouseleave', hideIcon);
        target.addEventListener('focusin', showIcon);
        target.addEventListener('focusout', hideIcon);
        target.addEventListener('touchstart', () => {
            showIcon();
            if (target.__inlineIconTimer) {
                clearTimeout(target.__inlineIconTimer);
            }
            target.__inlineIconTimer = setTimeout(() => {
                hideIcon();
            }, 1500);
        }, { passive: true });
    });
});
</script>

<!-- CSS específico para corrigir z-index dos modais -->
<link rel="stylesheet" href="assets/css/fix-modal-zindex.css">

<!-- Script específico para corrigir ícones de edição sobre modais -->
<!-- TEMPORARIAMENTE DESABILITADO - Causava loop infinito e travamento -->
<!-- <script src="assets/js/fix-modal-icons.js"></script> -->

<!-- Script para exclusão de turma -->
<script>
/**
 * Excluir turma completamente (apenas para administradores)
 * Exclui a turma e todos os dados relacionados (agendamentos, alunos, etc.)
 */
function excluirTurmaCompleta(turmaId, nomeTurma) {
    // Confirmação com detalhes
    const mensagem = `ATENÇÃO: Esta ação é IRREVERSÍVEL!\n\n` +
                     `Você está prestes a excluir COMPLETAMENTE a turma:\n` +
                     `"${nomeTurma}"\n\n` +
                     `Isso irá excluir:\n` +
                     `• A turma em si\n` +
                     `• Todas as aulas agendadas\n` +
                     `• Todas as matrículas de alunos\n` +
                     `• Todos os registros relacionados\n\n` +
                     `Tem certeza que deseja continuar?`;
    
    if (!confirm(mensagem)) {
        return;
    }
    
    // Segunda confirmação para garantir
    if (!confirm('ÚLTIMA CONFIRMAÇÃO!\n\nEsta ação não pode ser desfeita. Deseja realmente excluir esta turma?')) {
        return;
    }
    
    // Mostrar loading
    const btnExcluir = event.target.closest('button');
    const textoOriginal = btnExcluir.innerHTML;
    btnExcluir.disabled = true;
    btnExcluir.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Excluindo...';
    
    // Fazer requisição para API
    fetch('api/turmas-teoricas.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            acao: 'excluir',
            turma_id: turmaId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            // Sucesso - mostrar mensagem e redirecionar
            alert(data.mensagem);
            window.location.href = '?page=turmas-teoricas';
        } else {
            // Erro - restaurar botão e mostrar mensagem
            btnExcluir.disabled = false;
            btnExcluir.innerHTML = textoOriginal;
            alert('Erro: ' + data.mensagem);
        }
    })
    .catch(error => {
        // Erro de rede - restaurar botão e mostrar mensagem
        btnExcluir.disabled = false;
        btnExcluir.innerHTML = textoOriginal;
        console.error('Erro ao excluir turma:', error);
        alert('Erro ao excluir turma. Verifique sua conexão e tente novamente.');
    });
}
</script>