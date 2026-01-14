<div class="page-header">
    <div>
        <h1>Editar Matrícula</h1>
        <p class="text-muted">Aluno: <?= htmlspecialchars($enrollment['student_name']) ?></p>
    </div>
    <a href="<?= base_path("alunos/{$enrollment['student_id']}?tab=matricula") ?>" class="btn btn-outline">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Voltar
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= base_path("matriculas/{$enrollment['id']}/atualizar") ?>" id="enrollmentForm">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="form-group">
                <label class="form-label">Serviço</label>
                <input 
                    type="text" 
                    class="form-input" 
                    value="<?= htmlspecialchars($enrollment['service_name']) ?>" 
                    readonly
                    style="background-color: var(--color-bg-light);"
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="base_price_display">Preço Base</label>
                <input 
                    type="text" 
                    id="base_price_display" 
                    class="form-input" 
                    value="R$ <?= number_format($enrollment['base_price'], 2, ',', '.') ?>" 
                    readonly
                    style="background-color: var(--color-bg-light);"
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="discount_value">Desconto (R$)</label>
                <input 
                    type="number" 
                    id="discount_value" 
                    name="discount_value" 
                    class="form-input" 
                    value="<?= number_format($enrollment['discount_value'], 2, '.', '') ?>" 
                    step="0.01"
                    min="0"
                    onchange="calculateFinal()"
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="extra_value">Acréscimo (R$)</label>
                <input 
                    type="number" 
                    id="extra_value" 
                    name="extra_value" 
                    class="form-input" 
                    value="<?= number_format($enrollment['extra_value'], 2, '.', '') ?>" 
                    step="0.01"
                    min="0"
                    onchange="calculateFinal()"
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="final_price_display">Valor Final</label>
                <input 
                    type="text" 
                    id="final_price_display" 
                    class="form-input" 
                    value="R$ <?= number_format($enrollment['final_price'], 2, ',', '.') ?>" 
                    readonly
                    style="background-color: var(--color-bg-light); font-weight: var(--font-weight-semibold); font-size: var(--font-size-lg);"
                >
                <input type="hidden" id="final_price" name="final_price" value="<?= $enrollment['final_price'] ?>">
            </div>

            <!-- Seção Entrada (Edição) -->
            <div style="margin-top: 1.5rem; padding: 1rem; background: var(--color-bg-light); border: 1px solid var(--color-border); border-radius: var(--border-radius);">
                <h3 style="margin-top: 0; margin-bottom: 1rem; font-size: var(--font-size-md); font-weight: var(--font-weight-semibold);">Entrada (Opcional)</h3>
                
                <div class="form-group">
                    <label class="form-label" for="entry_amount">Valor da Entrada (R$)</label>
                    <input 
                        type="number" 
                        id="entry_amount" 
                        name="entry_amount" 
                        class="form-input" 
                        step="0.01"
                        min="0"
                        value="<?= !empty($enrollment['entry_amount']) ? number_format($enrollment['entry_amount'], 2, '.', '') : '' ?>"
                        placeholder="0.00"
                        onchange="calculateOutstanding()"
                    >
                    <small class="text-muted">Deixe em branco se não houver entrada</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="entry_payment_method">Forma de Pagamento da Entrada</label>
                    <select id="entry_payment_method" name="entry_payment_method" class="form-select">
                        <option value="">Selecione (se houver entrada)</option>
                        <option value="dinheiro" <?= ($enrollment['entry_payment_method'] ?? '') === 'dinheiro' ? 'selected' : '' ?>>Dinheiro</option>
                        <option value="pix" <?= ($enrollment['entry_payment_method'] ?? '') === 'pix' ? 'selected' : '' ?>>PIX</option>
                        <option value="cartao" <?= ($enrollment['entry_payment_method'] ?? '') === 'cartao' ? 'selected' : '' ?>>Cartão</option>
                        <option value="boleto" <?= ($enrollment['entry_payment_method'] ?? '') === 'boleto' ? 'selected' : '' ?>>Boleto</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="entry_payment_date">Data da Entrada</label>
                    <input 
                        type="date" 
                        id="entry_payment_date" 
                        name="entry_payment_date" 
                        class="form-input"
                        value="<?= !empty($enrollment['entry_payment_date']) ? $enrollment['entry_payment_date'] : date('Y-m-d') ?>"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="outstanding_amount_display">Saldo Devedor</label>
                    <input 
                        type="text" 
                        id="outstanding_amount_display" 
                        class="form-input" 
                        value="R$ <?= number_format($enrollment['outstanding_amount'] ?? $enrollment['final_price'], 2, ',', '.') ?>" 
                        readonly
                        style="background-color: var(--color-bg); font-weight: var(--font-weight-semibold); font-size: var(--font-size-md); color: var(--color-primary);"
                    >
                    <input type="hidden" id="outstanding_amount" name="outstanding_amount" value="<?= $enrollment['outstanding_amount'] ?? $enrollment['final_price'] ?>">
                    <small class="text-muted">Valor que será cobrado no Asaas</small>
                </div>
            </div>

            <!-- Seção Entrada e Saldo Devedor (Exibição) -->
            <?php if (!empty($enrollment['entry_amount']) || !empty($enrollment['outstanding_amount'])): ?>
            <div style="margin-top: 1.5rem; padding: 1rem; background: var(--color-bg-light); border: 1px solid var(--color-border); border-radius: var(--border-radius);">
                <h3 style="margin-top: 0; margin-bottom: 1rem; font-size: var(--font-size-md); font-weight: var(--font-weight-semibold);">Entrada e Saldo Devedor</h3>
                
                <?php if (!empty($enrollment['entry_amount'])): ?>
                <div class="form-group">
                    <label class="form-label">Valor da Entrada</label>
                    <input 
                        type="text" 
                        class="form-input" 
                        value="R$ <?= number_format($enrollment['entry_amount'], 2, ',', '.') ?>" 
                        readonly
                        style="background-color: var(--color-bg);"
                    >
                </div>
                <div class="form-group">
                    <label class="form-label">Forma de Pagamento da Entrada</label>
                    <input 
                        type="text" 
                        class="form-input" 
                        value="<?= 
                            $enrollment['entry_payment_method'] === 'dinheiro' ? 'Dinheiro' : 
                            ($enrollment['entry_payment_method'] === 'pix' ? 'PIX' : 
                            ($enrollment['entry_payment_method'] === 'cartao' ? 'Cartão' : 
                            ($enrollment['entry_payment_method'] === 'boleto' ? 'Boleto' : 'N/A'))) 
                        ?>" 
                        readonly
                        style="background-color: var(--color-bg);"
                    >
                </div>
                <div class="form-group">
                    <label class="form-label">Data da Entrada</label>
                    <input 
                        type="text" 
                        class="form-input" 
                        value="<?= !empty($enrollment['entry_payment_date']) ? date('d/m/Y', strtotime($enrollment['entry_payment_date'])) : '' ?>" 
                        readonly
                        style="background-color: var(--color-bg);"
                    >
                </div>
                <?php endif; ?>

                <?php if (!empty($enrollment['outstanding_amount'])): ?>
                <div class="form-group">
                    <label class="form-label">Saldo Devedor</label>
                    <input 
                        type="text" 
                        class="form-input" 
                        value="R$ <?= number_format($enrollment['outstanding_amount'], 2, ',', '.') ?>" 
                        readonly
                        style="background-color: var(--color-bg); font-weight: var(--font-weight-semibold); font-size: var(--font-size-md); color: var(--color-primary);"
                    >
                    <small class="text-muted">Valor que será cobrado no Asaas</small>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label" for="payment_method">Forma de Pagamento *</label>
                <select id="payment_method" name="payment_method" class="form-select" required>
                    <option value="pix" <?= $enrollment['payment_method'] === 'pix' ? 'selected' : '' ?>>PIX</option>
                    <option value="boleto" <?= $enrollment['payment_method'] === 'boleto' ? 'selected' : '' ?>>Boleto</option>
                    <option value="cartao" <?= $enrollment['payment_method'] === 'cartao' ? 'selected' : '' ?>>Cartão</option>
                    <option value="entrada_parcelas" <?= $enrollment['payment_method'] === 'entrada_parcelas' ? 'selected' : '' ?>>Entrada + Parcelas</option>
                </select>
            </div>

            <!-- Seção Condições de Pagamento (Exibição) -->
            <?php if (!empty($enrollment['installments']) || !empty($enrollment['down_payment_amount'])): ?>
            <div style="margin-top: 1.5rem; padding: 1rem; background: var(--color-bg-light); border: 1px solid var(--color-border); border-radius: var(--border-radius);">
                <h3 style="margin-top: 0; margin-bottom: 1rem; font-size: var(--font-size-md); font-weight: var(--font-weight-semibold);">Condições de Pagamento</h3>
                
                <?php if (!empty($enrollment['installments'])): ?>
                <div class="form-group">
                    <label class="form-label">Parcelas</label>
                    <input 
                        type="text" 
                        class="form-input" 
                        value="<?= $enrollment['installments'] ?>x" 
                        readonly
                        style="background-color: var(--color-bg);"
                    >
                </div>
                <?php endif; ?>

                <?php if (!empty($enrollment['down_payment_amount'])): ?>
                <div class="form-group">
                    <label class="form-label">Valor Entrada</label>
                    <input 
                        type="text" 
                        class="form-input" 
                        value="R$ <?= number_format($enrollment['down_payment_amount'], 2, ',', '.') ?>" 
                        readonly
                        style="background-color: var(--color-bg);"
                    >
                </div>
                <div class="form-group">
                    <label class="form-label">Vencimento Entrada</label>
                    <input 
                        type="text" 
                        class="form-input" 
                        value="<?= !empty($enrollment['down_payment_due_date']) ? date('d/m/Y', strtotime($enrollment['down_payment_due_date'])) : '' ?>" 
                        readonly
                        style="background-color: var(--color-bg);"
                    >
                </div>
                <?php endif; ?>

                <?php if (!empty($enrollment['first_due_date'])): ?>
                <div class="form-group">
                    <label class="form-label">Vencimento 1ª Parcela</label>
                    <input 
                        type="text" 
                        class="form-input" 
                        value="<?= date('d/m/Y', strtotime($enrollment['first_due_date'])) ?>" 
                        readonly
                        style="background-color: var(--color-bg);"
                    >
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label">Status Cobrança Asaas</label>
                    <input 
                        type="text" 
                        class="form-input" 
                        value="<?= 
                            $enrollment['billing_status'] === 'draft' ? 'Rascunho' : 
                            ($enrollment['billing_status'] === 'ready' ? 'Pronto' : 
                            ($enrollment['billing_status'] === 'generated' ? 'Gerado' : 'Erro')) 
                        ?>" 
                        readonly
                        style="background-color: var(--color-bg);"
                    >
                </div>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label" for="financial_status">Status Financeiro *</label>
                <select id="financial_status" name="financial_status" class="form-select" required>
                    <option value="em_dia" <?= $enrollment['financial_status'] === 'em_dia' ? 'selected' : '' ?>>Em Dia</option>
                    <option value="pendente" <?= $enrollment['financial_status'] === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                    <option value="bloqueado" <?= $enrollment['financial_status'] === 'bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="status">Status da Matrícula *</label>
                <select id="status" name="status" class="form-select" required>
                    <option value="ativa" <?= $enrollment['status'] === 'ativa' ? 'selected' : '' ?>>Ativa</option>
                    <option value="concluida" <?= $enrollment['status'] === 'concluida' ? 'selected' : '' ?>>Concluída</option>
                    <option value="cancelada" <?= $enrollment['status'] === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                </select>
            </div>

            <!-- Seção Processo DETRAN (Colapsável) -->
            <div class="form-section-collapsible" style="margin-top: 2rem; margin-bottom: 1rem;">
                <button type="button" class="form-section-toggle" onclick="toggleDetranSection()" style="width: 100%; text-align: left; padding: 0.75rem; background: var(--color-bg-light); border: 1px solid var(--color-border); border-radius: var(--border-radius); cursor: pointer; display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-weight: var(--font-weight-semibold);">Processo DETRAN</span>
                    <svg id="detranToggleIcon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="transition: transform 0.2s;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="detranSection" style="display: none; padding: 1rem; background: var(--color-bg-light); border: 1px solid var(--color-border); border-top: none; border-radius: 0 0 var(--border-radius) var(--border-radius);">
                    <div class="form-group">
                        <label class="form-label" for="renach">RENACH</label>
                        <input 
                            type="text" 
                            id="renach" 
                            name="renach" 
                            class="form-input" 
                            maxlength="20"
                            value="<?= htmlspecialchars($enrollment['renach'] ?? '') ?>"
                            placeholder="Ex: ABC123456"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="numero_processo">Número do Processo</label>
                        <input 
                            type="text" 
                            id="numero_processo" 
                            name="numero_processo" 
                            class="form-input" 
                            maxlength="50"
                            value="<?= htmlspecialchars($enrollment['numero_processo'] ?? '') ?>"
                            placeholder="Ex: 12345/2024"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="detran_protocolo">Protocolo DETRAN</label>
                        <input 
                            type="text" 
                            id="detran_protocolo" 
                            name="detran_protocolo" 
                            class="form-input" 
                            maxlength="50"
                            value="<?= htmlspecialchars($enrollment['detran_protocolo'] ?? '') ?>"
                            placeholder="Ex: PROTO-123456"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="situacao_processo">Situação do Processo</label>
                        <select id="situacao_processo" name="situacao_processo" class="form-select">
                            <option value="nao_iniciado" <?= ($enrollment['situacao_processo'] ?? 'nao_iniciado') === 'nao_iniciado' ? 'selected' : '' ?>>Não Iniciado</option>
                            <option value="em_andamento" <?= ($enrollment['situacao_processo'] ?? '') === 'em_andamento' ? 'selected' : '' ?>>Em Andamento</option>
                            <option value="pendente" <?= ($enrollment['situacao_processo'] ?? '') === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                            <option value="concluido" <?= ($enrollment['situacao_processo'] ?? '') === 'concluido' ? 'selected' : '' ?>>Concluído</option>
                            <option value="cancelado" <?= ($enrollment['situacao_processo'] ?? '') === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    Atualizar Matrícula
                </button>
                <?php if (!empty($enrollment['installments']) && ($enrollment['billing_status'] === 'draft' || $enrollment['billing_status'] === 'ready')): ?>
                <button type="button" class="btn btn-secondary" id="btnGerarCobranca" onclick="gerarCobrancaAsaas()" style="margin-left: 0.5rem;">
                    Gerar Cobrança Asaas
                </button>
                <?php endif; ?>
                <a href="<?= base_path("alunos/{$enrollment['student_id']}?tab=matricula") ?>" class="btn btn-outline">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
const basePrice = <?= $enrollment['base_price'] ?>;

function calculateFinal() {
    const discount = parseFloat(document.getElementById('discount_value').value || 0);
    const extra = parseFloat(document.getElementById('extra_value').value || 0);
    
    const final = Math.max(0, basePrice - discount + extra);
    
    document.getElementById('final_price').value = final;
    document.getElementById('final_price_display').value = 'R$ ' + final.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    
    // Recalcular saldo devedor quando valor final mudar
    calculateOutstanding();
}

function calculateOutstanding() {
    const finalPrice = parseFloat(document.getElementById('final_price').value || 0);
    const entryAmount = parseFloat(document.getElementById('entry_amount').value || 0);
    
    // Validar entrada
    if (entryAmount < 0) {
        alert('O valor da entrada não pode ser negativo.');
        document.getElementById('entry_amount').value = '';
        calculateOutstanding();
        return;
    }
    
    if (entryAmount >= finalPrice && finalPrice > 0) {
        alert('O valor da entrada deve ser menor que o valor final da matrícula.');
        document.getElementById('entry_amount').value = '';
        calculateOutstanding();
        return;
    }
    
    const outstanding = Math.max(0, finalPrice - entryAmount);
    
    document.getElementById('outstanding_amount').value = outstanding;
    document.getElementById('outstanding_amount_display').value = 'R$ ' + outstanding.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    
    // Se houver entrada, tornar obrigatórios os campos de entrada
    const entryPaymentMethod = document.getElementById('entry_payment_method');
    const entryPaymentDate = document.getElementById('entry_payment_date');
    
    if (entryAmount > 0) {
        entryPaymentMethod.setAttribute('required', 'required');
        entryPaymentDate.setAttribute('required', 'required');
    } else {
        entryPaymentMethod.removeAttribute('required');
        entryPaymentDate.removeAttribute('required');
    }
}

document.getElementById('enrollmentForm')?.addEventListener('submit', function(e) {
    calculateFinal();
    calculateOutstanding();
    
    // Validar entrada antes de submeter
    const entryAmount = parseFloat(document.getElementById('entry_amount').value || 0);
    const finalPrice = parseFloat(document.getElementById('final_price').value || 0);
    
    if (entryAmount > 0) {
        const entryPaymentMethod = document.getElementById('entry_payment_method').value;
        const entryPaymentDate = document.getElementById('entry_payment_date').value;
        
        if (!entryPaymentMethod) {
            e.preventDefault();
            alert('Se houver entrada, a forma de pagamento da entrada é obrigatória.');
            return false;
        }
        
        if (!entryPaymentDate) {
            e.preventDefault();
            alert('Se houver entrada, a data da entrada é obrigatória.');
            return false;
        }
        
        if (entryAmount >= finalPrice) {
            e.preventDefault();
            alert('O valor da entrada deve ser menor que o valor final da matrícula.');
            return false;
        }
    }
});

function toggleDetranSection() {
    const section = document.getElementById('detranSection');
    const icon = document.getElementById('detranToggleIcon');
    const isVisible = section.style.display !== 'none';
    
    section.style.display = isVisible ? 'none' : 'block';
    icon.style.transform = isVisible ? 'rotate(0deg)' : 'rotate(180deg)';
}

// Expandir seção DETRAN se houver dados preenchidos
document.addEventListener('DOMContentLoaded', function() {
    const renach = document.getElementById('renach')?.value || '';
    const numeroProcesso = document.getElementById('numero_processo')?.value || '';
    const detranProtocolo = document.getElementById('detran_protocolo')?.value || '';
    const situacaoProcesso = document.getElementById('situacao_processo')?.value || 'nao_iniciado';
    
    if (renach || numeroProcesso || detranProtocolo || situacaoProcesso !== 'nao_iniciado') {
        toggleDetranSection();
    }
});

function gerarCobrancaAsaas() {
    const enrollmentId = <?= $enrollment['id'] ?>;
    const btn = document.getElementById('btnGerarCobranca');
    const outstandingAmount = <?= $enrollment['outstanding_amount'] ?? $enrollment['final_price'] ?>;
    const installments = <?= $enrollment['installments'] ?? 1 ?>;
    const entryAmount = <?= $enrollment['entry_amount'] ?? 0 ?>;
    
    let message = 'Deseja gerar a cobrança no Asaas?\n\n';
    message += 'Valores que serão cobrados:\n';
    if (entryAmount > 0) {
        message += `- Entrada já recebida: R$ ${entryAmount.toLocaleString('pt-BR', {minimumFractionDigits: 2})}\n`;
    }
    message += `- Saldo devedor: R$ ${outstandingAmount.toLocaleString('pt-BR', {minimumFractionDigits: 2})}\n`;
    message += `- Parcelas: ${installments}x\n`;
    message += `- Valor por parcela: R$ ${(outstandingAmount / installments).toLocaleString('pt-BR', {minimumFractionDigits: 2})}\n\n`;
    message += 'Nota: O Asaas gerará cobranças apenas sobre o saldo devedor (valor final - entrada).';
    
    if (!confirm(message)) {
        return;
    }
    
    // Desabilitar botão durante processamento
    btn.disabled = true;
    btn.textContent = 'Gerando...';
    
    // TODO: Implementar chamada AJAX para endpoint de geração de cobrança
    // IMPORTANTE: Usar outstanding_amount ao invés de final_price
    // - outstanding_amount = valor que será cobrado no Asaas
    // - entry_amount = valor já recebido (não deve ser cobrado novamente)
    // - Parcelas = installments
    // - Valor da parcela = outstanding_amount / installments
    alert('Funcionalidade de geração de cobrança Asaas será implementada em breve.\n\nA matrícula está preparada com:\n- Método: <?= htmlspecialchars($enrollment['payment_method']) ?>\n- Parcelas: <?= $enrollment['installments'] ?? 'N/A' ?>\n- Saldo devedor: R$ <?= number_format($enrollment['outstanding_amount'] ?? $enrollment['final_price'], 2, ',', '.') ?>\n- Status: <?= $enrollment['billing_status'] ?? 'draft' ?>');
    
    btn.disabled = false;
    btn.textContent = 'Gerar Cobrança Asaas';
}
</script>
