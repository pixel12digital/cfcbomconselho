<?php
/**
 * Template: Relatório de Presenças
 * Sistema de Turmas Teóricas - CFC Bom Conselho
 */

if (!$dadosRelatorio) return;

$presencas = $dadosRelatorio;
?>

<!-- Resumo -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="stats-card" style="background: #e8f5e8;">
            <div class="stats-number text-success"><?= count(array_filter($presencas, function($p) { return $p['presente']; })) ?></div>
            <div class="stats-label">Presenças</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stats-card" style="background: #fff3e0;">
            <div class="stats-number text-warning"><?= count(array_filter($presencas, function($p) { return !$p['presente']; })) ?></div>
            <div class="stats-label">Ausências</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stats-card" style="background: #e3f2fd;">
            <div class="stats-number text-primary"><?= count($presencas) ?></div>
            <div class="stats-label">Total de Registros</div>
        </div>
    </div>
</div>

<!-- Tabela de Presenças -->
<div class="table-responsive">
    <table class="table table-striped tabela-relatorio">
        <thead>
            <tr>
                <th>Aula</th>
                <th>Data</th>
                <th>Nome do Aluno</th>
                <th>CPF</th>
                <th>Presente</th>
                <th>Observação</th>
                <th>Registrado Por</th>
                <th>Data/Hora</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($presencas as $presenca): ?>
            <tr>
                <td>
                    <span class="badge bg-primary"><?= $presenca['ordem'] ?></span>
                    <?= htmlspecialchars($presenca['nome_aula']) ?>
                </td>
                <td><?= date('d/m/Y', strtotime($presenca['data_aula'])) ?></td>
                <td><?= htmlspecialchars($presenca['aluno_nome']) ?></td>
                <td><?= htmlspecialchars($presenca['aluno_cpf']) ?></td>
                <td>
                    <?php if ($presenca['presente']): ?>
                        <span class="badge bg-success">Presente</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Ausente</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($presenca['observacao'] ?? '') ?></td>
                <td><?= htmlspecialchars($presenca['registrado_por_nome'] ?? 'Sistema') ?></td>
                <td><?= date('d/m/Y H:i', strtotime($presenca['registrado_em'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (empty($presencas)): ?>
<div class="text-center py-5">
    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
    <h5 class="text-muted">Nenhuma presença registrada</h5>
    <p class="text-muted">Esta turma ainda não possui registros de presença.</p>
</div>
<?php endif; ?>
