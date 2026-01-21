<div class="page-header">
    <div class="page-header-content">
        <div>
            <h1><?= $isAluno ? 'Financeiro' : 'Consulta Financeira' ?></h1>
            <p class="text-muted"><?= $isAluno ? 'Sua situação financeira' : 'Visualize a situação financeira dos alunos' ?></p>
        </div>
    </div>
</div>

<?php if (!$isAluno): ?>
<!-- Busca de Aluno (apenas para perfis administrativos) -->
<div class="card" style="margin-bottom: var(--spacing-md);">
    <div class="card-body">
        <form method="GET" action="<?= base_path('financeiro') ?>">
            <div style="display: flex; gap: var(--spacing-md); align-items: flex-end;">
                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                    <label class="form-label" for="q">Buscar Aluno</label>
                    <input 
                        type="search" 
                        id="q" 
                        name="q" 
                        class="form-input" 
                        value="<?= htmlspecialchars($search) ?>" 
                        placeholder="Nome ou CPF do aluno..."
                    >
                </div>
                <button type="submit" class="btn btn-primary">Buscar</button>
                <?php if ($search): ?>
                <a href="<?= base_path('financeiro') ?>" class="btn btn-outline">Limpar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if (!$isAluno && !empty($students) && !$student): ?>
    <!-- Lista de resultados da busca -->
    <div class="card" style="margin-bottom: var(--spacing-md);">
        <div class="card-body">
            <h3 style="margin-bottom: var(--spacing-md);">Resultados da busca</h3>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Telefone</th>
                            <th style="width: 120px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['name']) ?></td>
                            <td><?= htmlspecialchars($s['cpf']) ?></td>
                            <td><?= htmlspecialchars($s['phone'] ?: '-') ?></td>
                            <td>
                                <a href="<?= base_path("financeiro?student_id={$s['id']}") ?>" class="btn btn-sm btn-primary">
                                    Ver Financeiro
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php elseif ($student): ?>
    <!-- Detalhes Financeiros do Aluno -->
    <div class="card" style="margin-bottom: var(--spacing-md);">
        <div class="card-header">
            <h2><?= htmlspecialchars($student['name']) ?></h2>
            <p class="text-muted">CPF: <?= htmlspecialchars($student['cpf']) ?></p>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-md);">
                <div>
                    <label class="form-label">Total Pago</label>
                    <div style="font-size: 1.5rem; font-weight: 600; color: #10b981;">
                        R$ <?= number_format($totalPaid, 2, ',', '.') ?>
                    </div>
                </div>
                <div>
                    <label class="form-label">Saldo Devedor</label>
                    <div style="font-size: 1.5rem; font-weight: 600; color: <?= $totalDebt > 0 ? '#ef4444' : '#10b981' ?>;">
                        R$ <?= number_format($totalDebt, 2, ',', '.') ?>
                    </div>
                </div>
                <div>
                    <label class="form-label">Status Geral</label>
                    <div>
                        <?php
                        $hasBlocked = false;
                        foreach ($enrollments as $enr) {
                            if ($enr['financial_status'] === 'bloqueado') {
                                $hasBlocked = true;
                                break;
                            }
                        }
                        ?>
                        <?php if ($hasBlocked): ?>
                            <span style="color: #ef4444; font-weight: 600; font-size: 1.1rem;">⚠️ BLOQUEADO</span>
                        <?php elseif ($totalDebt > 0): ?>
                            <span style="color: #f59e0b; font-weight: 600; font-size: 1.1rem;">⚠️ PENDENTE</span>
                        <?php else: ?>
                            <span style="color: #10b981; font-weight: 600; font-size: 1.1rem;">✅ EM DIA</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Matrículas -->
    <?php if (empty($enrollments)): ?>
        <div class="card">
            <div class="card-body text-center" style="padding: 40px 20px;">
                <p class="text-muted">Este aluno não possui matrículas cadastradas.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header">
                <h3>Matrículas</h3>
            </div>
            <div class="card-body">
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Serviço</th>
                                <th>Valor Total</th>
                                <th>Status Financeiro</th>
                                <th>Status</th>
                                <?php if (!$isAluno): ?>
                                <th style="width: 120px;">Ações</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($enrollments as $enr): ?>
                            <tr>
                                <td>
                                    <?php if ($isAluno): ?>
                                        <?= htmlspecialchars($enr['service_name'] ?? 'Matrícula') ?>
                                    <?php else: ?>
                                        <a href="<?= base_path("matriculas/{$enr['id']}") ?>" style="color: var(--color-primary); text-decoration: none;">
                                            <?= htmlspecialchars($enr['service_name'] ?? 'Matrícula') ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>R$ <?= number_format($enr['final_price'], 2, ',', '.') ?></td>
                                <td>
                                    <?php
                                    $statusConfig = [
                                        'em_dia' => ['label' => 'Em Dia', 'color' => '#10b981'],
                                        'pendente' => ['label' => 'Pendente', 'color' => '#f59e0b'],
                                        'bloqueado' => ['label' => 'Bloqueado', 'color' => '#ef4444']
                                    ];
                                    $status = $statusConfig[$enr['financial_status']] ?? ['label' => $enr['financial_status'], 'color' => '#666'];
                                    ?>
                                    <span style="color: <?= $status['color'] ?>; font-weight: 600;">
                                        <?= $status['label'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $enrStatusConfig = [
                                        'ativa' => ['label' => 'Ativa', 'color' => '#10b981'],
                                        'concluida' => ['label' => 'Concluída', 'color' => '#3b82f6'],
                                        'cancelada' => ['label' => 'Cancelada', 'color' => '#6b7280']
                                    ];
                                    $enrStatus = $enrStatusConfig[$enr['status']] ?? ['label' => $enr['status'], 'color' => '#666'];
                                    ?>
                                    <span style="color: <?= $enrStatus['color'] ?>; font-weight: 600;">
                                        <?= $enrStatus['label'] ?>
                                    </span>
                                </td>
                                <?php if (!$isAluno): ?>
                                <td>
                                    <a href="<?= base_path("matriculas/{$enr['id']}") ?>" class="btn btn-sm btn-outline">
                                        Ver Detalhes
                                    </a>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php else: ?>
    <?php if (!$isAluno && !$student && (!isset($students) || empty($students))): ?>
    <!-- Lista de Matrículas com Saldo Devedor -->
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3>Matrículas com Saldo Devedor<?= !empty($search) ? ' (Filtrado)' : '' ?></h3>
                <p class="text-muted" style="margin: 0; font-size: var(--font-size-sm);">
                    Total: <?= $pendingTotal ?> matrícula(s) com saldo devedor
                    <?php if (!empty($search)): ?>
                    <br>Filtro: "<?= htmlspecialchars($search) ?>"
                    <?php endif; ?>
                    <?php if (isset($pendingSyncableCount) && $pendingSyncableCount > 0): ?>
                    <br>Sincronizáveis: <?= $pendingSyncableCount ?>
                    <?php endif; ?>
                </p>
            </div>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <?php if (isset($pendingSyncableCount) && $pendingSyncableCount > 0): ?>
                <button type="button" class="btn btn-primary" id="btnSyncPendings" onclick="sincronizarPendentes()">
                    Sincronizar Pendentes desta Página
                </button>
                <?php else: ?>
                <button type="button" class="btn btn-primary" id="btnSyncPendings" disabled title="Sem cobranças para sincronizar">
                    Sincronizar Pendentes desta Página
                </button>
                <span style="font-size: var(--font-size-sm); color: var(--color-text-muted); margin-left: 0.5rem;">
                    Sem cobranças para sincronizar
                </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($pendingEnrollments)): ?>
            <div class="text-center" style="padding: 40px 20px;">
                <p class="text-muted">
                    <?php if (!empty($search)): ?>
                    Nenhum resultado encontrado com o termo "<?= htmlspecialchars($search) ?>".
                    <?php else: ?>
                    Nenhuma matrícula com saldo devedor encontrada.
                    <?php endif; ?>
                </p>
            </div>
            <?php else: ?>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>CPF</th>
                            <th>Serviço</th>
                            <th>Saldo Devedor</th>
                            <th>Vencimento</th>
                            <th>Status Financeiro</th>
                            <th>Cobrança</th>
                            <th>Status da Cobrança</th>
                            <th>Último Evento</th>
                            <th style="width: 220px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingEnrollments as $enr): ?>
                        <?php
                        $studentName = $enr['student_full_name'] ?: $enr['student_name'];
                        $cpfFormatted = \App\Helpers\ValidationHelper::formatCpf($enr['student_cpf'] ?? '');
                        
                        // Saldo devedor calculado
                        $outstandingAmount = floatval($enr['calculated_outstanding'] ?? $enr['outstanding_amount'] ?? ($enr['final_price'] - ($enr['entry_amount'] ?? 0)));
                        
                        // Data de vencimento
                        $dueDate = null;
                        $isOverdue = false;
                        if (!empty($enr['first_due_date']) && $enr['first_due_date'] !== '0000-00-00') {
                            $dueDate = date('d/m/Y', strtotime($enr['first_due_date']));
                            $isOverdue = strtotime($enr['first_due_date']) < time();
                        } elseif (!empty($enr['down_payment_due_date']) && $enr['down_payment_due_date'] !== '0000-00-00') {
                            $dueDate = date('d/m/Y', strtotime($enr['down_payment_due_date']));
                            $isOverdue = strtotime($enr['down_payment_due_date']) < time();
                        }
                        
                        // Status financeiro
                        $financialStatusConfig = [
                            'em_dia' => ['label' => 'Em Dia', 'color' => '#10b981'],
                            'pendente' => ['label' => 'Pendente', 'color' => '#f59e0b'],
                            'bloqueado' => ['label' => 'Bloqueado', 'color' => '#ef4444']
                        ];
                        $financialStatus = $financialStatusConfig[$enr['financial_status']] ?? ['label' => $enr['financial_status'], 'color' => '#666'];
                        
                        // Verificar se tem cobrança gerada
                        $hasCharge = !empty($enr['gateway_charge_id']) && $enr['gateway_charge_id'] !== '';
                        
                        // Status gateway (traduzir para português claro e não técnico)
                        $gatewayStatusRaw = $hasCharge ? ($enr['gateway_last_status'] ?? '-') : '-';
                        $gatewayStatus = '-';
                        if ($gatewayStatusRaw !== '-') {
                            $statusMap = [
                                'waiting' => 'Aguardando pagamento',
                                'up_to_date' => 'Em dia (sem parcelas vencidas)',
                                'paid' => 'Pago',
                                'paid_partial' => 'Parcialmente pago',
                                'settled' => 'Liquidado',
                                'canceled' => 'Cancelado',
                                'expired' => 'Expirado',
                                'error' => 'Erro',
                                'unpaid' => 'Não pago',
                                'pending' => 'Pendente',
                                'processing' => 'Processando',
                                'new' => 'Nova cobrança'
                            ];
                            $gatewayStatus = $statusMap[strtolower($gatewayStatusRaw)] ?? $gatewayStatusRaw;
                        }
                        $billingStatus = $enr['billing_status'] ?? 'draft';
                        
                        // Último evento
                        $lastEvent = !empty($enr['gateway_last_event_at']) 
                            ? date('d/m/Y H:i', strtotime($enr['gateway_last_event_at'])) 
                            : '-';
                        ?>
                        <tr id="enrollment-row-<?= $enr['id'] ?>" style="<?= $isOverdue ? 'background-color: #fef2f2;' : '' ?>">
                            <td><?= htmlspecialchars($studentName) ?></td>
                            <td><?= htmlspecialchars($cpfFormatted) ?></td>
                            <td>
                                <a href="<?= base_path("matriculas/{$enr['id']}") ?>" style="color: var(--color-primary); text-decoration: none;">
                                    <?= htmlspecialchars($enr['service_name'] ?? 'Matrícula') ?>
                                </a>
                            </td>
                            <td style="font-weight: 600; color: <?= $outstandingAmount > 0 ? '#ef4444' : '#10b981' ?>;">
                                R$ <?= number_format($outstandingAmount, 2, ',', '.') ?>
                            </td>
                            <td style="<?= $isOverdue ? 'color: #ef4444; font-weight: 600;' : '' ?>">
                                <?= $dueDate ?: '-' ?>
                                <?php if ($isOverdue): ?>
                                <span style="font-size: var(--font-size-xs); color: #ef4444;">(Vencida)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="color: <?= $financialStatus['color'] ?>; font-weight: 600;">
                                    <?= $financialStatus['label'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($hasCharge): ?>
                                <span style="color: #10b981; font-weight: 600; font-size: var(--font-size-sm);">
                                    ✓ Gerada
                                </span>
                                <?php else: ?>
                                <span style="color: var(--color-text-muted); font-size: var(--font-size-sm);">
                                    Não gerada
                                </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($hasCharge): ?>
                                <span style="font-size: var(--font-size-sm);">
                                    <?= htmlspecialchars($gatewayStatus) ?>
                                </span>
                                <?php else: ?>
                                <span style="font-size: var(--font-size-sm); color: var(--color-text-muted);">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: var(--font-size-sm); color: var(--color-text-muted);">
                                <?= htmlspecialchars($lastEvent) ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                                    <?php if ($hasCharge): ?>
                                        <?php if (!empty($enr['gateway_payment_url'])): ?>
                                        <a 
                                            href="<?= htmlspecialchars($enr['gateway_payment_url']) ?>" 
                                            target="_blank" 
                                            class="btn btn-sm btn-outline"
                                            title="Abrir cobrança"
                                        >
                                            Abrir Cobrança
                                        </a>
                                        <button 
                                            type="button" 
                                            class="btn btn-sm btn-primary" 
                                            onclick="imprimirBoleto('<?= htmlspecialchars($enr['gateway_payment_url'], ENT_QUOTES) ?>')"
                                            title="Imprimir boleto"
                                        >
                                            🖨️ Imprimir
                                        </button>
                                        <?php endif; ?>
                                        <button 
                                            type="button" 
                                            class="btn btn-sm btn-secondary" 
                                            onclick="sincronizarIndividual(<?= $enr['id'] ?>)"
                                            id="btn-sync-<?= $enr['id'] ?>"
                                        >
                                            Sincronizar
                                        </button>
                                    <?php else: ?>
                                        <a 
                                            href="<?= base_path("matriculas/{$enr['id']}") ?>" 
                                            class="btn btn-sm btn-primary"
                                            title="Gerar cobrança"
                                        >
                                            Gerar Cobrança
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginação -->
            <?php if ($pendingTotal > $pendingPerPage): ?>
            <?php
            $totalPages = ceil($pendingTotal / $pendingPerPage);
            $paginationParams = ['page' => $pendingPage];
            if (!empty($search)) {
                $paginationParams['q'] = $search;
            }
            ?>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: var(--spacing-md); padding-top: var(--spacing-md); border-top: 1px solid var(--color-border);">
                <div style="color: var(--color-text-muted); font-size: var(--font-size-sm);">
                    Página <?= $pendingPage ?> de <?= $totalPages ?>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <?php if ($pendingPage > 1): ?>
                    <?php
                    $prevParams = $paginationParams;
                    $prevParams['page'] = $pendingPage - 1;
                    ?>
                    <a href="<?= base_path("financeiro?" . http_build_query($prevParams)) ?>" class="btn btn-outline">
                        Anterior
                    </a>
                    <?php endif; ?>
                    <?php if ($pendingPage < $totalPages): ?>
                    <?php
                    $nextParams = $paginationParams;
                    $nextParams['page'] = $pendingPage + 1;
                    ?>
                    <a href="<?= base_path("financeiro?" . http_build_query($nextParams)) ?>" class="btn btn-outline">
                        Próxima
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php if (!$isAluno && !$student && empty($pendingEnrollments) && empty($search) && (empty($students) || !isset($students))): ?>
    <?php
    // Verificar se há dados para mostrar nos cards
    $hasCards = !empty($overdueStudents) || !empty($dueSoonStudents) || !empty($recentStudents);
    ?>
    
    <?php if ($hasCards): ?>
    <!-- Cards de Resumo -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--spacing-md); margin-bottom: var(--spacing-md);">
        
        <!-- Card A: Em Atraso -->
        <?php if (!empty($overdueStudents)): ?>
        <div class="card">
            <div class="card-header">
                <h3>Em Atraso</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <div style="max-height: 400px; overflow-y: auto;">
                    <table class="table" style="margin: 0;">
                        <thead style="position: sticky; top: 0; background: var(--color-bg); z-index: 1;">
                            <tr>
                                <th style="padding: var(--spacing-sm) var(--spacing-md); font-size: var(--font-size-sm);">Aluno</th>
                                <th style="padding: var(--spacing-sm) var(--spacing-md); font-size: var(--font-size-sm);">Valor</th>
                                <th style="padding: var(--spacing-sm) var(--spacing-md); font-size: var(--font-size-sm);">Vencimento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($overdueStudents as $stu): ?>
                            <?php
                            $displayName = $stu['full_name'] ?: $stu['name'];
                            $cpfFormatted = \App\Helpers\ValidationHelper::formatCpf($stu['cpf']);
                            $oldestDueDate = !empty($stu['oldest_due_date']) ? date('d/m/Y', strtotime($stu['oldest_due_date'])) : '-';
                            ?>
                            <tr style="cursor: pointer;" onclick="window.location.href='<?= base_path("financeiro?student_id={$stu['id']}") ?>'">
                                <td style="padding: var(--spacing-sm) var(--spacing-md);">
                                    <div style="font-weight: 500;"><?= htmlspecialchars($displayName) ?></div>
                                    <div style="font-size: var(--font-size-sm); color: var(--color-text-muted);"><?= htmlspecialchars($cpfFormatted) ?></div>
                                </td>
                                <td style="padding: var(--spacing-sm) var(--spacing-md); color: #ef4444; font-weight: 600;">
                                    R$ <?= number_format($stu['total_debt'], 2, ',', '.') ?>
                                </td>
                                <td style="padding: var(--spacing-sm) var(--spacing-md); font-size: var(--font-size-sm);">
                                    <?= htmlspecialchars($oldestDueDate) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Card B: Vencem em Breve (7 dias) -->
        <?php if (!empty($dueSoonStudents)): ?>
        <div class="card">
            <div class="card-header">
                <h3>Vencem em Breve (7 dias)</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <div style="max-height: 400px; overflow-y: auto;">
                    <table class="table" style="margin: 0;">
                        <thead style="position: sticky; top: 0; background: var(--color-bg); z-index: 1;">
                            <tr>
                                <th style="padding: var(--spacing-sm) var(--spacing-md); font-size: var(--font-size-sm);">Aluno</th>
                                <th style="padding: var(--spacing-sm) var(--spacing-md); font-size: var(--font-size-sm);">Valor</th>
                                <th style="padding: var(--spacing-sm) var(--spacing-md); font-size: var(--font-size-sm);">Vencimento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dueSoonStudents as $stu): ?>
                            <?php
                            $displayName = $stu['full_name'] ?: $stu['name'];
                            $cpfFormatted = \App\Helpers\ValidationHelper::formatCpf($stu['cpf']);
                            $nextDueDate = !empty($stu['next_due_date']) ? date('d/m/Y', strtotime($stu['next_due_date'])) : '-';
                            ?>
                            <tr style="cursor: pointer;" onclick="window.location.href='<?= base_path("financeiro?student_id={$stu['id']}") ?>'">
                                <td style="padding: var(--spacing-sm) var(--spacing-md);">
                                    <div style="font-weight: 500;"><?= htmlspecialchars($displayName) ?></div>
                                    <div style="font-size: var(--font-size-sm); color: var(--color-text-muted);"><?= htmlspecialchars($cpfFormatted) ?></div>
                                </td>
                                <td style="padding: var(--spacing-sm) var(--spacing-md); font-weight: 600;">
                                    R$ <?= number_format($stu['total_debt'], 2, ',', '.') ?>
                                </td>
                                <td style="padding: var(--spacing-sm) var(--spacing-md); font-size: var(--font-size-sm);">
                                    <?= htmlspecialchars($nextDueDate) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Card C: Recentes -->
        <?php if (!empty($recentStudents)): ?>
        <div class="card">
            <div class="card-header">
                <h3>Recentes</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <div style="max-height: 400px; overflow-y: auto;">
                    <table class="table" style="margin: 0;">
                        <thead style="position: sticky; top: 0; background: var(--color-bg); z-index: 1;">
                            <tr>
                                <th style="padding: var(--spacing-sm) var(--spacing-md); font-size: var(--font-size-sm);">Aluno</th>
                                <th style="padding: var(--spacing-sm) var(--spacing-md); font-size: var(--font-size-sm);">Última Consulta</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentStudents as $stu): ?>
                            <?php
                            $displayName = $stu['full_name'] ?: $stu['name'];
                            $cpfFormatted = \App\Helpers\ValidationHelper::formatCpf($stu['cpf']);
                            $lastViewed = !empty($stu['last_viewed_at']) ? date('d/m/Y H:i', strtotime($stu['last_viewed_at'])) : '-';
                            ?>
                            <tr style="cursor: pointer;" onclick="window.location.href='<?= base_path("financeiro?student_id={$stu['id']}") ?>'">
                                <td style="padding: var(--spacing-sm) var(--spacing-md);">
                                    <div style="font-weight: 500;"><?= htmlspecialchars($displayName) ?></div>
                                    <div style="font-size: var(--font-size-sm); color: var(--color-text-muted);"><?= htmlspecialchars($cpfFormatted) ?></div>
                                </td>
                                <td style="padding: var(--spacing-sm) var(--spacing-md); font-size: var(--font-size-sm); color: var(--color-text-muted);">
                                    <?= htmlspecialchars($lastViewed) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
    <?php else: ?>
    <div class="card">
        <div class="card-body text-center" style="padding: 60px 20px;">
            <p class="text-muted">Digite o nome ou CPF do aluno para consultar a situação financeira.</p>
        </div>
    </div>
    <?php endif; ?>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>

<script>
// Autocomplete para busca
(function() {
    const searchInput = document.getElementById('q');
    if (!searchInput) return;
    
    let autocompleteTimeout;
    let autocompleteDropdown = null;
    
    function createAutocompleteDropdown() {
        if (autocompleteDropdown) return autocompleteDropdown;
        
        autocompleteDropdown = document.createElement('div');
        autocompleteDropdown.id = 'autocomplete-dropdown';
        autocompleteDropdown.style.cssText = 'position: absolute; top: 100%; left: 0; right: 0; background: var(--color-bg); border: 1px solid var(--color-border); border-radius: var(--radius-md); box-shadow: var(--shadow-lg); max-height: 300px; overflow-y: auto; z-index: 1000; margin-top: 4px; display: none;';
        
        const formGroup = searchInput.closest('.form-group');
        if (formGroup) {
            formGroup.style.position = 'relative';
            formGroup.appendChild(autocompleteDropdown);
        }
        
        return autocompleteDropdown;
    }
    
    function showAutocomplete(results) {
        const dropdown = createAutocompleteDropdown();
        dropdown.innerHTML = '';
        
        if (results.length === 0) {
            dropdown.innerHTML = '<div style="padding: var(--spacing-md); text-align: center; color: var(--color-text-muted);">Nenhum resultado encontrado</div>';
            dropdown.style.display = 'block';
            return;
        }
        
        results.forEach(item => {
            const div = document.createElement('div');
            div.style.cssText = 'padding: var(--spacing-sm) var(--spacing-md); cursor: pointer; border-bottom: 1px solid var(--color-border);';
            div.onmouseover = function() { this.style.backgroundColor = 'var(--color-bg-light)'; };
            div.onmouseout = function() { this.style.backgroundColor = 'transparent'; };
            div.onclick = function() {
                window.location.href = '<?= base_path("financeiro") ?>?student_id=' + item.id;
            };
            
            div.innerHTML = `
                <div style="font-weight: 500;">${escapeHtml(item.name)}</div>
                <div style="font-size: var(--font-size-sm); color: var(--color-text-muted);">${escapeHtml(item.cpf)}</div>
            `;
            
            dropdown.appendChild(div);
        });
        
        dropdown.style.display = 'block';
    }
    
    function hideAutocomplete() {
        if (autocompleteDropdown) {
            autocompleteDropdown.style.display = 'none';
        }
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        clearTimeout(autocompleteTimeout);
        
        if (query.length < 2) {
            hideAutocomplete();
            return;
        }
        
        autocompleteTimeout = setTimeout(function() {
            fetch('<?= base_path("api/financeiro/autocomplete") ?>?q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    showAutocomplete(data);
                })
                .catch(error => {
                    console.error('Erro no autocomplete:', error);
                    hideAutocomplete();
                });
        }, 300);
    });
    
    searchInput.addEventListener('blur', function() {
        // Delay para permitir cliques no dropdown
        setTimeout(hideAutocomplete, 200);
    });
    
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideAutocomplete();
        }
    });
})();

// Sincronização em lote
function sincronizarPendentes() {
    const btn = document.getElementById('btnSyncPendings');
    
    if (btn.disabled) {
        alert('Sem cobranças para sincronizar nesta página.');
        return;
    }
    
    const page = <?= $pendingPage ?>;
    const perPage = <?= $pendingPerPage ?>;
    const search = '<?= htmlspecialchars($search ?? '', ENT_QUOTES) ?>';
    
    if (!confirm('Deseja sincronizar todas as cobranças pendentes desta página?\n\nIsso irá consultar o status atual na EFI para cada matrícula.')) {
        return;
    }
    
    btn.disabled = true;
    btn.textContent = 'Sincronizando...';
    
    fetch('<?= base_path('api/payments/sync-pendings') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            page: page,
            per_page: perPage,
            search: search
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.ok) {
            let message = `Sincronização concluída!\n\n`;
            message += `- Total processado: ${data.total}\n`;
            message += `- Sincronizadas com sucesso: ${data.synced}\n`;
            
            if (data.errors && data.errors.length > 0) {
                message += `- Erros: ${data.errors.length}\n`;
            }
            
            alert(message);
            
            // Recarregar página para atualizar status
            window.location.reload();
        } else {
            alert('Não foi possível sincronizar: ' + (data.message || 'Ocorreu um erro desconhecido. Por favor, tente novamente.'));
            btn.disabled = false;
            btn.textContent = 'Sincronizar Pendentes desta Página';
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Não foi possível comunicar com o servidor. Por favor, tente novamente.');
        btn.disabled = false;
        btn.textContent = 'Sincronizar Pendentes desta Página';
    });
}

// Sincronização individual
function sincronizarIndividual(enrollmentId) {
    const btn = document.getElementById('btn-sync-' + enrollmentId);
    
    if (!confirm('Deseja sincronizar o status desta cobrança com a EFI?')) {
        return;
    }
    
    btn.disabled = true;
    btn.textContent = 'Sincronizando...';
    
    fetch('<?= base_path('api/payments/sync') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            enrollment_id: enrollmentId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.ok) {
            const statusMap = {
                'waiting': 'Aguardando pagamento',
                'paid': 'Pago',
                'settled': 'Liquidado',
                'canceled': 'Cancelado',
                'expired': 'Expirado',
                'error': 'Erro',
                'unpaid': 'Não pago',
                'pending': 'Pendente',
                'processing': 'Processando',
                'new': 'Novo'
            };
            const statusTraduzido = data.status ? (statusMap[data.status.toLowerCase()] || data.status) : 'Não disponível';
            alert('Cobrança sincronizada com sucesso!\n\nStatus: ' + statusTraduzido);
            window.location.reload();
        } else {
            alert('Não foi possível sincronizar: ' + (data.message || 'Ocorreu um erro desconhecido. Por favor, tente novamente.'));
            btn.disabled = false;
            btn.textContent = 'Sincronizar';
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Não foi possível comunicar com o servidor. Por favor, tente novamente.');
        btn.disabled = false;
        btn.textContent = 'Sincronizar';
    });
}

// Imprimir boleto
function imprimirBoleto(paymentUrl) {
    // Abrir o link do boleto em nova janela
    const printWindow = window.open(paymentUrl, '_blank');
    
    if (!printWindow) {
        alert('Erro ao abrir o boleto. Verifique se os pop-ups estão bloqueados.');
        return;
    }
    
    // Aguardar a página carregar e então abrir diálogo de impressão
    printWindow.addEventListener('load', function() {
        // Pequeno delay para garantir que a página carregou completamente
        setTimeout(function() {
            printWindow.print();
        }, 500);
    });
    
    // Fallback: se a página já estiver carregada
    if (printWindow.document.readyState === 'complete') {
        setTimeout(function() {
            printWindow.print();
        }, 500);
    }
}
</script>
