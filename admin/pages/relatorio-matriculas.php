<?php
/**
 * Template: Relatório de Matrículas
 * Sistema de Turmas Teóricas - CFC Bom Conselho
 */

if (!$dadosRelatorio) return;

$matriculas = $dadosRelatorio;
?>

<!-- Resumo -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card" style="background: #e8f5e8;">
            <div class="stats-number text-success"><?= count(array_filter($matriculas, function($m) { return $m['status'] === 'ativo'; })) ?></div>
            <div class="stats-label">Ativos</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: #e3f2fd;">
            <div class="stats-number text-primary"><?= count(array_filter($matriculas, function($m) { return $m['status'] === 'matriculado'; })) ?></div>
            <div class="stats-label">Matriculados</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: #fff3e0;">
            <div class="stats-number text-warning"><?= count(array_filter($matriculas, function($m) { return $m['status'] === 'concluido'; })) ?></div>
            <div class="stats-label">Concluídos</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card" style="background: #f3e5f5;">
            <div class="stats-number text-info"><?= count($matriculas) ?></div>
            <div class="stats-label">Total</div>
        </div>
    </div>
</div>

<!-- Tabela de Matrículas -->
<div class="table-responsive">
    <table class="table table-striped tabela-relatorio">
        <thead>
            <tr>
                <th>Nome do Aluno</th>
                <th>CPF</th>
                <th>Categoria</th>
                <th>Status</th>
                <th>Data Matrícula</th>
                <th>Data Conclusão</th>
                <th>Turma</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($matriculas as $matricula): ?>
            <tr>
                <td><?= htmlspecialchars($matricula['aluno_nome']) ?></td>
                <td><?= htmlspecialchars($matricula['aluno_cpf']) ?></td>
                <td><span class="badge bg-primary"><?= $matricula['categoria_cnh'] ?></span></td>
                <td>
                    <?php
                    $badgeClass = 'secondary';
                    switch ($matricula['status']) {
                        case 'ativo':
                            $badgeClass = 'success';
                            break;
                        case 'matriculado':
                            $badgeClass = 'primary';
                            break;
                        case 'concluido':
                            $badgeClass = 'info';
                            break;
                        case 'cancelado':
                            $badgeClass = 'danger';
                            break;
                    }
                    ?>
                    <span class="badge bg-<?= $badgeClass ?>"><?= ucfirst($matricula['status']) ?></span>
                </td>
                <td><?= date('d/m/Y', strtotime($matricula['data_matricula'])) ?></td>
                <td>
                    <?= $matricula['data_conclusao'] ? date('d/m/Y', strtotime($matricula['data_conclusao'])) : '-' ?>
                </td>
                <td><?= htmlspecialchars($matricula['turma_nome']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (empty($matriculas)): ?>
<div class="text-center py-5">
    <i class="fas fa-users fa-3x text-muted mb-3"></i>
    <h5 class="text-muted">Nenhuma matrícula encontrada</h5>
    <p class="text-muted">Esta turma ainda não possui alunos matriculados.</p>
</div>
<?php endif; ?>
