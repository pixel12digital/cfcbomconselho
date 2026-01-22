<?php
/**
 * Template: Relatório de Frequência
 * Sistema de Turmas Teóricas - CFC Bom Conselho
 */

if (!$dadosRelatorio) return;

$turma = $dadosRelatorio['turma'];
$frequencias = $dadosRelatorio['frequencias'];
$estatisticas = $dadosRelatorio['estatisticas_gerais'];
?>

<!-- Estatísticas Gerais -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card" style="background: #e3f2fd;">
            <div class="stats-number text-primary"><?= $estatisticas['total_alunos'] ?></div>
            <div class="stats-label">Total de Alunos</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: #e8f5e8;">
            <div class="stats-number text-success"><?= $estatisticas['aprovados'] ?></div>
            <div class="stats-label">Aprovados</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: #fff3e0;">
            <div class="stats-number text-warning"><?= $estatisticas['reprovados'] ?></div>
            <div class="stats-label">Reprovados</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: #f3e5f5;">
            <div class="stats-number text-info"><?= $estatisticas['frequencia_media'] ?>%</div>
            <div class="stats-label">Frequência Média</div>
        </div>
    </div>
</div>

<!-- Informações da Turma -->
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-info">
            <h6 class="mb-2"><i class="fas fa-info-circle"></i> Informações da Turma</h6>
            <div class="row">
                <div class="col-md-3">
                    <strong>Instrutor:</strong> <?= htmlspecialchars($turma['instrutor_nome']) ?>
                </div>
                <div class="col-md-3">
                    <strong>Período:</strong> <?= date('d/m/Y', strtotime($turma['data_inicio'])) ?> a <?= date('d/m/Y', strtotime($turma['data_fim'])) ?>
                </div>
                <div class="col-md-3">
                    <strong>Capacidade:</strong> <?= $turma['capacidade_maxima'] ?> alunos
                </div>
                <div class="col-md-3">
                    <strong>Freq. Mínima:</strong> <?= $turma['frequencia_minima'] ?>%
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabela de Frequência -->
<div class="table-responsive">
    <table class="table table-striped tabela-relatorio">
        <thead>
            <tr>
                <th>Nome do Aluno</th>
                <th>CPF</th>
                <th>Categoria</th>
                <th>Status</th>
                <th>Total Aulas</th>
                <th>Presentes</th>
                <th>Ausentes</th>
                <th>Frequência</th>
                <th>Situação</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($frequencias as $freq): ?>
            <?php 
            $aluno = $freq['aluno'];
            $stats = $freq['estatisticas'];
            $classe = 'baixo';
            if ($stats['percentual_frequencia'] >= $turma['frequencia_minima']) {
                $classe = 'alto';
            } elseif ($stats['percentual_frequencia'] >= ($turma['frequencia_minima'] - 10)) {
                $classe = 'medio';
            }
            ?>
            <tr>
                <td><?= htmlspecialchars($aluno['nome']) ?></td>
                <td><?= htmlspecialchars($aluno['cpf']) ?></td>
                <td><span class="badge bg-primary"><?= $aluno['categoria_cnh'] ?></span></td>
                <td><span class="badge bg-<?= $aluno['status_matricula'] === 'ativo' ? 'success' : 'secondary' ?>"><?= ucfirst($aluno['status_matricula']) ?></span></td>
                <td><?= $stats['total_aulas'] ?></td>
                <td><span class="text-success"><?= $stats['presentes'] ?></span></td>
                <td><span class="text-danger"><?= $stats['ausentes'] ?></span></td>
                <td>
                    <span class="frequencia-badge <?= $classe ?>">
                        <?= $stats['percentual_frequencia'] ?>%
                    </span>
                </td>
                <td>
                    <?php if ($stats['aprovado_frequencia']): ?>
                        <span class="badge bg-success">Aprovado</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Reprovado</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Resumo -->
<div class="row mt-4">
    <div class="col-12">
        <div class="alert alert-<?= $estatisticas['frequencia_media'] >= $turma['frequencia_minima'] ? 'success' : 'warning' ?>">
            <h6 class="mb-2">
                <i class="fas fa-<?= $estatisticas['frequencia_media'] >= $turma['frequencia_minima'] ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                Resumo da Turma
            </h6>
            <p class="mb-0">
                <strong>Frequência média:</strong> <?= $estatisticas['frequencia_media'] ?>% 
                (<?= $estatisticas['frequencia_media'] >= $turma['frequencia_minima'] ? 'Acima' : 'Abaixo' ?> do mínimo de <?= $turma['frequencia_minima'] ?>%)
                | <strong>Aprovados:</strong> <?= $estatisticas['aprovados'] ?>/<?= $estatisticas['total_alunos'] ?> alunos
                | <strong>Taxa de aprovação:</strong> <?= $estatisticas['total_alunos'] > 0 ? round(($estatisticas['aprovados'] / $estatisticas['total_alunos']) * 100, 2) : 0 ?>%
            </p>
        </div>
    </div>
</div>
