/**
 * PADRONIZAÇÕES GLOBAIS DE UI - SISTEMA CFC
 * Padrões consistentes para comportamento, validação e estados
 */

class UIPadronizacoes {
    constructor() {
        this.init();
    }

    init() {
        this.setupValidation();
        this.setupLoadingStates();
        this.setupTooltips();
        this.setupEmptyStates();
        this.setupButtonStates();
    }

    /**
     * Validação em tempo real para formulários críticos
     */
    setupValidation() {
        // Validação de CPF
        document.addEventListener('input', (e) => {
            if (e.target.name === 'cpf' || e.target.id === 'cpf') {
                this.validateCPF(e.target);
            }
            
            // Validação de telefone
            if (e.target.name === 'telefone' || e.target.id === 'telefone') {
                this.validateTelefone(e.target);
            }
            
            // Validação de e-mail
            if (e.target.type === 'email') {
                this.validateEmail(e.target);
            }
            
            // Validação de valores monetários
            if (e.target.name === 'valor' || e.target.name === 'valor_pago') {
                this.validateMonetary(e.target);
            }
        });
    }

    validateCPF(input) {
        const cpf = input.value.replace(/\D/g, '');
        const isValid = this.isValidCPF(cpf);
        
        input.classList.remove('is-valid', 'is-invalid');
        input.classList.add(isValid ? 'is-valid' : 'is-invalid');
        
        this.showValidationFeedback(input, isValid, 'CPF inválido');
    }

    validateTelefone(input) {
        const telefone = input.value.replace(/\D/g, '');
        const isValid = telefone.length >= 10 && telefone.length <= 11;
        
        input.classList.remove('is-valid', 'is-invalid');
        input.classList.add(isValid ? 'is-valid' : 'is-invalid');
        
        this.showValidationFeedback(input, isValid, 'Telefone deve ter 10 ou 11 dígitos');
    }

    validateEmail(input) {
        const email = input.value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const isValid = emailRegex.test(email) || email === '';
        
        input.classList.remove('is-valid', 'is-invalid');
        input.classList.add(isValid ? 'is-valid' : 'is-invalid');
        
        this.showValidationFeedback(input, isValid, 'E-mail inválido');
    }

    validateMonetary(input) {
        const valor = parseFloat(input.value.replace(/[^\d,]/g, '').replace(',', '.'));
        const isValid = !isNaN(valor) && valor > 0;
        
        input.classList.remove('is-valid', 'is-invalid');
        input.classList.add(isValid ? 'is-valid' : 'is-invalid');
        
        this.showValidationFeedback(input, isValid, 'Valor deve ser maior que zero');
    }

    showValidationFeedback(input, isValid, message) {
        // Remover feedback anterior
        const existingFeedback = input.parentNode.querySelector('.invalid-feedback, .valid-feedback');
        if (existingFeedback) {
            existingFeedback.remove();
        }

        // Adicionar novo feedback
        const feedback = document.createElement('div');
        feedback.className = isValid ? 'valid-feedback' : 'invalid-feedback';
        feedback.textContent = isValid ? '✓' : message;
        input.parentNode.appendChild(feedback);
    }

    isValidCPF(cpf) {
        if (cpf.length !== 11) return false;
        if (/^(\d)\1{10}$/.test(cpf)) return false;
        
        let sum = 0;
        for (let i = 0; i < 9; i++) {
            sum += parseInt(cpf.charAt(i)) * (10 - i);
        }
        let remainder = 11 - (sum % 11);
        if (remainder === 10 || remainder === 11) remainder = 0;
        if (remainder !== parseInt(cpf.charAt(9))) return false;
        
        sum = 0;
        for (let i = 0; i < 10; i++) {
            sum += parseInt(cpf.charAt(i)) * (11 - i);
        }
        remainder = 11 - (sum % 11);
        if (remainder === 10 || remainder === 11) remainder = 0;
        if (remainder !== parseInt(cpf.charAt(10))) return false;
        
        return true;
    }

    /**
     * Estados de loading padronizados
     */
    setupLoadingStates() {
        // Interceptar formulários para mostrar loading
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (form.tagName === 'FORM') {
                this.showFormLoading(form);
            }
        });

        // Interceptar botões de ação para mostrar loading
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-loading') || e.target.closest('.btn-loading')) {
                this.showButtonLoading(e.target);
            }
        });
    }

    showFormLoading(form) {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.classList.add('loading-state');
            submitBtn.disabled = true;
        }
    }

    showButtonLoading(button) {
        button.classList.add('loading-state');
        button.disabled = true;
        
        // Restaurar após 3 segundos (ou quando a operação terminar)
        setTimeout(() => {
            button.classList.remove('loading-state');
            button.disabled = false;
        }, 3000);
    }

    /**
     * Tooltips padronizados
     */
    setupTooltips() {
        document.querySelectorAll('[data-tooltip]').forEach(element => {
            element.classList.add('tooltip-custom');
        });
    }

    /**
     * Estados vazios padronizados
     */
    setupEmptyStates() {
        // Detectar tabelas vazias e mostrar empty state
        document.querySelectorAll('.table tbody').forEach(tbody => {
            if (tbody.children.length === 0 || 
                (tbody.children.length === 1 && tbody.children[0].textContent.trim() === '')) {
                this.showEmptyState(tbody);
            }
        });
    }

    showEmptyState(container) {
        const emptyState = document.createElement('div');
        emptyState.className = 'empty-state';
        emptyState.innerHTML = `
            <i class="fas fa-inbox"></i>
            <h3>Nenhum item encontrado</h3>
            <p>Não há dados para exibir no momento.</p>
        `;
        
        container.parentNode.replaceChild(emptyState, container);
    }

    /**
     * Estados de botões padronizados
     */
    setupButtonStates() {
        // Aplicar classes padronizadas baseadas no conteúdo
        document.querySelectorAll('.btn').forEach(btn => {
            const icon = btn.querySelector('i');
            if (icon) {
                if (icon.classList.contains('fa-edit') || icon.classList.contains('fa-pen')) {
                    btn.classList.add('btn-secondary-action');
                } else if (icon.classList.contains('fa-trash')) {
                    btn.classList.add('btn-danger-action');
                } else if (icon.classList.contains('fa-eye')) {
                    btn.classList.add('btn-secondary-action');
                } else if (icon.classList.contains('fa-plus')) {
                    btn.classList.add('btn-primary-action');
                } else if (icon.classList.contains('fa-save')) {
                    btn.classList.add('btn-success-action');
                }
            }
        });
    }

    /**
     * Aplicar máscaras padronizadas
     */
    applyMasks() {
        // Máscara de CPF
        document.querySelectorAll('input[name="cpf"], input[id="cpf"]').forEach(input => {
            input.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                e.target.value = value;
            });
        });

        // Máscara de telefone
        document.querySelectorAll('input[name="telefone"], input[id="telefone"]').forEach(input => {
            input.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length <= 10) {
                    value = value.replace(/(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{4})(\d)/, '$1-$2');
                } else {
                    value = value.replace(/(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{5})(\d)/, '$1-$2');
                }
                e.target.value = value;
            });
        });

        // Máscara de moeda
        document.querySelectorAll('input[name="valor"], input[name="valor_pago"]').forEach(input => {
            input.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                value = (parseInt(value) / 100).toLocaleString('pt-BR', {
                    style: 'currency',
                    currency: 'BRL'
                });
                e.target.value = value;
            });
        });
    }
}

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    new UIPadronizacoes();
});
