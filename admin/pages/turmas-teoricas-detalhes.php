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

// Obter progresso das disciplinas
$progressoDisciplinas = $turmaManager->obterProgressoDisciplinas($turmaId);

// Obter aulas agendadas
try {
    $aulasAgendadas = $db->fetchAll(
        "SELECT * FROM turma_aulas_agendadas WHERE turma_id = ? ORDER BY data_aula, hora_inicio",
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
        <h3 style="margin: 0; color: #023A8D;">
            <i class="fas fa-info-circle me-2"></i>Detalhes da Turma
        </h3>
        <p style="margin: 5px 0 0 0; color: #666;">
            Informações completas sobre a turma teórica
        </p>
    </div>
    <a href="?page=turmas-teoricas" class="btn-secondary">
        ← Voltar para Lista
    </a>
</div>

<!-- Informações Básicas -->
<div style="background: white; border-radius: 12px; padding: 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h4 style="color: #023A8D; margin-bottom: 20px; display: flex; align-items: center;">
        <i class="fas fa-graduation-cap me-2"></i>Informações Básicas
    </h4>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <div>
            <h5 style="color: #023A8D; font-size: 1.5rem; margin-bottom: 10px;">
                <?= htmlspecialchars($turma['nome']) ?>
            </h5>
            <p style="color: #666; margin-bottom: 15px;">
                <?= htmlspecialchars($turma['curso_nome'] ?? 'Curso não especificado') ?>
            </p>
            
            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                <span style="background: <?= $turma['status'] === 'ativa' ? '#28a745' : ($turma['status'] === 'completa' ? '#007bff' : '#ffc107') ?>; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.9rem; font-weight: 600; text-transform: uppercase;">
                    <?= ucfirst($turma['status']) ?>
                </span>
            </div>
        </div>
        
        <div style="display: grid; gap: 10px;">
            <div style="display: flex; align-items: center;">
                <i class="fas fa-building me-2" style="color: #023A8D; width: 20px;"></i>
                <span><strong>Sala:</strong> <?= htmlspecialchars($turma['sala_nome'] ?? 'Não definida') ?></span>
            </div>
            
            <div style="display: flex; align-items: center;">
                <i class="fas fa-calendar-alt me-2" style="color: #023A8D; width: 20px;"></i>
                <span><strong>Período:</strong> <?= date('d/m/Y', strtotime($turma['data_inicio'])) ?> - <?= date('d/m/Y', strtotime($turma['data_fim'])) ?></span>
            </div>
            
            <div style="display: flex; align-items: center;">
                <i class="fas fa-users me-2" style="color: #023A8D; width: 20px;"></i>
                <span><strong>Alunos:</strong> <?= $totalAlunos ?>/<?= $turma['max_alunos'] ?></span>
            </div>
            
            <div style="display: flex; align-items: center;">
                <i class="fas fa-clock me-2" style="color: #023A8D; width: 20px;"></i>
                <span><strong>Modalidade:</strong> <?= ucfirst($turma['modalidade']) ?></span>
            </div>
        </div>
    </div>
    
    <?php if (!empty($turma['observacoes'])): ?>
    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
        <h6 style="color: #023A8D; margin-bottom: 10px;">Observações:</h6>
        <p style="color: #666; margin: 0;"><?= nl2br(htmlspecialchars($turma['observacoes'])) ?></p>
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
    <h4 style="color: #023A8D; margin-bottom: 20px; display: flex; align-items: center;">
        <i class="fas fa-chart-line me-2"></i>Progresso das Disciplinas
    </h4>
    
    <div style="display: grid; gap: 15px;">
        <?php foreach ($progressoDisciplinas as $disciplina): ?>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #023A8D;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h6 style="margin: 0; color: #023A8D;"><?= htmlspecialchars($disciplina['nome_disciplina'] ?? $disciplina['disciplina']) ?></h6>
                <span style="font-size: 0.9rem; color: #666;">
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
                <div style="width: <?= $percentual ?>%; height: 100%; background: linear-gradient(90deg, #023A8D, #F7931E); transition: width 0.3s ease;"></div>
            </div>
            
            <div style="margin-top: 8px; font-size: 0.8rem; color: #666;">
                <?= $percentual ?>% completo
                <?php if (!empty($disciplina['aulas_faltantes']) && $disciplina['aulas_faltantes'] > 0): ?>
                    - <span style="color: #dc3545;">Faltam <?= $disciplina['aulas_faltantes'] ?> aulas</span>
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
    <h4 style="color: #023A8D; margin-bottom: 20px; display: flex; align-items: center;">
        <i class="fas fa-calendar-check me-2"></i>Aulas Agendadas (<?= count($aulasAgendadas) ?>)
    </h4>
    
    <div style="display: grid; gap: 15px;">
        <?php foreach ($aulasAgendadas as $aula): ?>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745;">
            <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 10px;">
                <div style="flex: 1; min-width: 200px;">
                    <h6 style="margin: 0 0 5px 0; color: #023A8D;">
                        <?= htmlspecialchars($aula['nome_aula']) ?>
                    </h6>
                    <div style="color: #666; font-size: 0.9rem;">
                        <div><strong>Disciplina:</strong> <?= htmlspecialchars($aula['disciplina']) ?></div>
                        <div><strong>Data:</strong> <?= date('d/m/Y', strtotime($aula['data_aula'])) ?></div>
                        <div><strong>Horário:</strong> <?= $aula['hora_inicio'] ?> - <?= $aula['hora_fim'] ?></div>
                        <div><strong>Duração:</strong> <?= $aula['duracao_minutos'] ?> minutos</div>
                    </div>
                </div>
                
                <div style="text-align: right;">
                    <span style="background: <?= $aula['status'] === 'agendada' ? '#007bff' : ($aula['status'] === 'realizada' ? '#28a745' : '#dc3545') ?>; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">
                        <?= ucfirst($aula['status']) ?>
                    </span>
                </div>
            </div>
            
            <?php if (!empty($aula['observacoes'])): ?>
            <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #dee2e6;">
                <small style="color: #666;"><strong>Observações:</strong> <?= htmlspecialchars($aula['observacoes']) ?></small>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Ações -->
<div style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h4 style="color: #023A8D; margin-bottom: 20px;">Ações Disponíveis</h4>
    
    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
        <?php if ($turma['status'] === 'criando' || $turma['status'] === 'agendando'): ?>
        <a href="?page=turmas-teoricas&acao=agendar&step=2&turma_id=<?= $turma['id'] ?>" class="btn-primary">
            <i class="fas fa-calendar-plus me-2"></i>Continuar Agendamento
        </a>
        <?php endif; ?>
        
        <?php if ($turma['status'] === 'completa'): ?>
        <a href="?page=turmas-teoricas&acao=alunos&step=4&turma_id=<?= $turma['id'] ?>" class="btn-primary">
            <i class="fas fa-users me-2"></i>Gerenciar Alunos
        </a>
        <?php endif; ?>
        
        <?php if ($turma['status'] === 'criando' || $turma['status'] === 'agendando'): ?>
        <a href="?page=turmas-teoricas&acao=editar&step=1&turma_id=<?= $turma['id'] ?>" class="btn-primary" style="background: #F7931E;">
            <i class="fas fa-edit me-2"></i>Editar Turma
        </a>
        <?php endif; ?>
        
        
        <button onclick="window.print()" class="btn-secondary">
            <i class="fas fa-print me-2"></i>Imprimir Detalhes
        </button>
    </div>
</div>

<style>
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

<!-- CSS específico para corrigir z-index dos modais -->
<link rel="stylesheet" href="assets/css/fix-modal-zindex.css">

<!-- Script específico para corrigir ícones de edição sobre modais -->
<script src="assets/js/fix-modal-icons.js"></script>