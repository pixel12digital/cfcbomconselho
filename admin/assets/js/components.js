/**
 * COMPONENTES JAVASCRIPT PARA SISTEMA CFC
 * Baseado no sistema e-condutor para mesma experiência do usuário
 */

// =====================================================
// SISTEMA DE NOTIFICAÇÕES (SIMILAR AO ALERTIFY)
// =====================================================

class NotificationSystem {
    constructor() {
        this.init();
    }

    init() {
        // Criar container de notificações se não existir
        if (!document.getElementById('notification-container')) {
            const container = document.createElement('div');
            container.id = 'notification-container';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                max-width: 400px;
            `;
            document.body.appendChild(container);
        }
    }

    show(message, type = 'info', duration = 5000) {
        const container = document.getElementById('notification-container');
        const notification = document.createElement('div');
        
        const colors = {
            success: '#28a745',
            error: '#dc3545',
            warning: '#ffc107',
            info: '#17a2b8'
        };

        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };

        notification.style.cssText = `
            background: white;
            border-left: 4px solid ${colors[type]};
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-radius: 4px;
            padding: 15px 20px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #333;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            max-width: 400px;
        `;

        notification.innerHTML = `
            <span style="font-size: 18px;">${icons[type]}</span>
            <span style="flex: 1;">${message}</span>
            <button onclick="this.parentElement.remove()" style="background: none; border: none; cursor: pointer; font-size: 18px; color: #999;">×</button>
        `;

        container.appendChild(notification);

        // Animar entrada
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);

        // Auto-remover
        if (duration > 0) {
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.style.transform = 'translateX(100%)';
                    setTimeout(() => notification.remove(), 300);
                }
            }, duration);
        }

        return notification;
    }

    success(message, duration) {
        return this.show(message, 'success', duration);
    }

    error(message, duration) {
        return this.show(message, 'error', duration);
    }

    warning(message, duration) {
        return this.show(message, 'warning', duration);
    }

    info(message, duration) {
        return this.show(message, 'info', duration);
    }
}

// =====================================================
// SISTEMA DE MÁSCARAS DE INPUT (SIMILAR AO JQUERY MASK)
// =====================================================

class InputMask {
    constructor() {
        this.masks = {
            cpf: '000.000.000-00',
            cnpj: '00.000.000/0000-00',
            telefone: '(00) 00000-0000',
            cep: '00000-000',
            data: '00/00/0000',
            hora: '00:00',
            placa: 'AAA-0000',
            valor: '000.000.000,00'
        };
        this.init();
    }

    init() {
        // Aplicar máscaras automaticamente
        this.applyMasks();
        
        // Aplicar máscaras em elementos dinâmicos
        this.observeDOM();
    }

    applyMasks() {
        // CPF
        document.querySelectorAll('input[data-mask="cpf"], input[name*="cpf"]').forEach(input => {
            this.mask(input, this.masks.cpf);
        });

        // CNPJ
        document.querySelectorAll('input[data-mask="cnpj"], input[name*="cnpj"]').forEach(input => {
            this.mask(input, this.masks.cnpj);
        });

        // Telefone
        document.querySelectorAll('input[data-mask="telefone"], input[name*="telefone"], input[name*="celular"]').forEach(input => {
            this.mask(input, this.masks.telefone);
        });

        // CEP
        document.querySelectorAll('input[data-mask="cep"], input[name*="cep"]').forEach(input => {
            this.mask(input, this.masks.cep);
        });

        // Data - NÃO aplicar máscara em campos type="date" (HTML5 nativo)
        document.querySelectorAll('input[data-mask="data"], input[name*="data"]:not([type="date"])').forEach(input => {
            this.mask(input, this.masks.data);
        });

        // Hora
        document.querySelectorAll('input[data-mask="hora"], input[name*="hora"]').forEach(input => {
            this.mask(input, this.masks.hora);
        });

        // Placa - permitindo letras e números
        document.querySelectorAll('input[data-mask="placa"], input[name*="placa"]').forEach(input => {
            this.maskPlaca(input);
        });

        // Valor - formato brasileiro com ponto automático
        // Pular campos com data-skip-mask="true" para evitar conflitos com formatação customizada
        document.querySelectorAll('input[data-mask="valor"], input[name*="valor"], input[name*="preco"], input[name*="valor_aquisicao"]').forEach(input => {
            if (input.getAttribute('data-skip-mask') === 'true') {
                return; // Pular este campo
            }
            this.maskValor(input);
        });
    }

    mask(input, mask) {
        input.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            let result = '';
            let maskIndex = 0;
            let valueIndex = 0;

            while (maskIndex < mask.length && valueIndex < value.length) {
                if (mask[maskIndex] === '0') {
                    result += value[valueIndex];
                    valueIndex++;
                } else if (mask[maskIndex] === 'A') {
                    result += value[valueIndex].toUpperCase();
                    valueIndex++;
                } else {
                    result += mask[maskIndex];
                }
                maskIndex++;
            }

            e.target.value = result;
        });

        // Aplicar máscara ao carregar
        if (input.value) {
            input.dispatchEvent(new Event('input'));
        }
    }

    maskPlaca(input) {
        input.addEventListener('input', (e) => {
            let value = e.target.value.replace(/[^A-Za-z0-9]/g, '');
            let result = '';
            
            // Aplicar formato AAA-0000 permitindo letras e números
            for (let i = 0; i < value.length && i < 7; i++) {
                if (i === 3) {
                    result += '-';
                }
                result += value[i].toUpperCase();
            }

            e.target.value = result;
        });

        // Aplicar máscara ao carregar
        if (input.value) {
            input.dispatchEvent(new Event('input'));
        }
    }

    maskValor(input) {
        // Verificar se o campo deve ser ignorado
        if (input.getAttribute('data-skip-mask') === 'true') {
            return;
        }
        
        // Flag para prevenir loops
        let isFormatting = false;
        
        input.addEventListener('input', (e) => {
            // Prevenir loops recursivos
            if (isFormatting) {
                return;
            }
            
            isFormatting = true;
            
            try {
                let value = e.target.value.replace(/[^\d]/g, '');
                
                // Converter para número
                let number = parseInt(value) / 100;
                
                // Formatar como moeda brasileira
                let formatted = new Intl.NumberFormat('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(number);
                
                e.target.value = formatted;
            } finally {
                isFormatting = false;
            }
        });

        // Aplicar máscara ao carregar (apenas se houver valor)
        // Removido: não disparar evento 'input' automaticamente ao carregar
        // Isso pode causar loops quando há listeners de input registrados
        // A máscara será aplicada naturalmente quando o usuário interagir com o campo
    }

    observeDOM() {
        // Flag para prevenir reaplicação durante operações pesadas
        let isApplyingMasks = false;
        
        // Observer para elementos dinâmicos
        const observer = new MutationObserver((mutations) => {
            // Prevenir múltiplas execuções simultâneas
            if (isApplyingMasks) {
                return;
            }
            
            // Verificar se há mudanças relevantes (evitar reaplicar em mudanças triviais)
            let hasRelevantChanges = false;
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1) { // Element node
                            // Ignorar mudanças em tabelas de parcelas (evitar loop)
                            if (node.id === 'tabela-parcelas' || 
                                node.closest && node.closest('#tabela-parcelas') ||
                                (node.tagName && node.tagName.toLowerCase() === 'tr' && 
                                 node.closest && node.closest('#tabela-parcelas')) {
                                return; // Pular mudanças na tabela de parcelas
                            }
                            hasRelevantChanges = true;
                        }
                    });
                }
            });
            
            // Só aplicar máscaras se houver mudanças relevantes
            if (hasRelevantChanges) {
                isApplyingMasks = true;
                // Usar setTimeout para evitar bloquear o thread principal
                setTimeout(() => {
                    try {
                        this.applyMasks();
                    } finally {
                        isApplyingMasks = false;
                    }
                }, 0);
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
}

// =====================================================
// SISTEMA DE VALIDAÇÃO EM TEMPO REAL
// =====================================================

class FormValidator {
    constructor() {
        this.rules = {
            required: (value) => value.trim() !== '' ? null : 'Campo obrigatório',
            email: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) ? null : 'Email inválido',
            cpf: (value) => this.validateCPF(value) ? null : 'CPF inválido',
            cnpj: (value) => this.validateCNPJ(value) ? null : 'CNPJ inválido',
            telefone: (value) => value.replace(/\D/g, '').length >= 10 ? null : 'Telefone inválido',
            cep: (value) => value.replace(/\D/g, '').length === 8 ? null : 'CEP inválido',
            minLength: (value, min) => value.length >= min ? null : `Mínimo de ${min} caracteres`,
            maxLength: (value, max) => value.length <= max ? null : `Máximo de ${max} caracteres`
        };
        this.init();
    }

    init() {
        // Aplicar validação em formulários
        document.querySelectorAll('form[data-validate]').forEach(form => {
            this.validateForm(form);
        });

        // Validação em tempo real para inputs
        document.querySelectorAll('input[data-validate]').forEach(input => {
            this.validateInput(input);
        });
    }

    validateForm(form) {
        form.addEventListener('submit', (e) => {
            if (!this.isFormValid(form)) {
                e.preventDefault();
                notifications.error('Por favor, corrija os erros no formulário');
            }
        });
    }

    validateInput(input) {
        const rules = input.dataset.validate.split('|');
        
        input.addEventListener('blur', () => {
            this.showValidation(input, rules);
        });

        input.addEventListener('input', () => {
            this.hideValidation(input);
        });
    }

    showValidation(input, rules) {
        const value = input.value;
        let error = null;

        for (let rule of rules) {
            const [ruleName, param] = rule.split(':');
            if (this.rules[ruleName]) {
                error = this.rules[ruleName](value, param);
                if (error) break;
            }
        }

        if (error) {
            this.showError(input, error);
        } else {
            this.showSuccess(input);
        }
    }

    showError(input, message) {
        this.hideValidation(input);
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'validation-error';
        errorDiv.textContent = message;
        errorDiv.style.cssText = `
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            display: block;
        `;
        
        input.parentNode.appendChild(errorDiv);
        input.style.borderColor = '#dc3545';
    }

    showSuccess(input) {
        this.hideValidation(input);
        input.style.borderColor = '#28a745';
    }

    hideValidation(input) {
        const error = input.parentNode.querySelector('.validation-error');
        if (error) {
            error.remove();
        }
    }

    isFormValid(form) {
        let valid = true;
        const inputs = form.querySelectorAll('input[data-validate]');
        
        inputs.forEach(input => {
            const rules = input.dataset.validate.split('|');
            let inputValid = true;
            
            for (let rule of rules) {
                const [ruleName, param] = rule.split(':');
                if (this.rules[ruleName]) {
                    const error = this.rules[ruleName](input.value, param);
                    if (error) {
                        inputValid = false;
                        this.showError(input, error);
                    }
                }
            }
            
            if (!inputValid) valid = false;
        });
        
        return valid;
    }

    validateCPF(cpf) {
        cpf = cpf.replace(/\D/g, '');
        if (cpf.length !== 11) return false;
        
        // Verificar dígitos repetidos
        if (/^(\d)\1{10}$/.test(cpf)) return false;
        
        // Validar dígitos verificadores
        let sum = 0;
        for (let i = 0; i < 9; i++) {
            sum += parseInt(cpf[i]) * (10 - i);
        }
        let remainder = sum % 11;
        let digit1 = remainder < 2 ? 0 : 11 - remainder;
        
        sum = 0;
        for (let i = 0; i < 10; i++) {
            sum += parseInt(cpf[i]) * (11 - i);
        }
        remainder = sum % 11;
        let digit2 = remainder < 2 ? 0 : 11 - remainder;
        
        return parseInt(cpf[9]) === digit1 && parseInt(cpf[10]) === digit2;
    }

    validateCNPJ(cnpj) {
        cnpj = cnpj.replace(/\D/g, '');
        if (cnpj.length !== 14) return false;
        
        // Verificar dígitos repetidos
        if (/^(\d)\1{13}$/.test(cnpj)) return false;
        
        // Validar dígitos verificadores
        let sum = 0;
        let weight = 2;
        
        for (let i = 11; i >= 0; i--) {
            sum += parseInt(cnpj[i]) * weight;
            weight = weight === 9 ? 2 : weight + 1;
        }
        
        let remainder = sum % 11;
        let digit1 = remainder < 2 ? 0 : 11 - remainder;
        
        sum = 0;
        weight = 2;
        
        for (let i = 12; i >= 0; i--) {
            sum += parseInt(cnpj[i]) * weight;
            weight = weight === 9 ? 2 : weight + 1;
        }
        
        remainder = sum % 11;
        let digit2 = remainder < 2 ? 0 : 11 - remainder;
        
        return parseInt(cnpj[12]) === digit1 && parseInt(cnpj[13]) === digit2;
    }
}

// =====================================================
// SISTEMA DE MODAIS E OVERLAYS
// =====================================================

class ModalSystem {
    constructor() {
        this.activeModal = null;
        this.init();
    }

    init() {
        // Fechar modais ao clicar fora
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-overlay')) {
                this.close(e.target);
            }
        });

        // Fechar modais com ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.activeModal) {
                this.close(this.activeModal);
            }
        });
    }

    show(content, options = {}) {
        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.3s ease;
        `;

        const modalContent = document.createElement('div');
        modalContent.className = 'modal-content';
        modalContent.style.cssText = `
            background: white;
            border-radius: 8px;
            padding: 20px;
            max-width: ${options.width || '500px'};
            max-height: ${options.height || '80vh'};
            overflow-y: auto;
            transform: scale(0.8);
            transition: transform 0.3s ease;
            position: relative;
        `;

        if (options.title) {
            const title = document.createElement('h3');
            title.textContent = options.title;
            title.style.cssText = `
                margin: 0 0 20px 0;
                color: #333;
                font-size: 18px;
                font-weight: 600;
            `;
            modalContent.appendChild(title);
        }

        if (typeof content === 'string') {
            modalContent.innerHTML += content;
        } else {
            modalContent.appendChild(content);
        }

        if (options.closeButton !== false) {
            const closeBtn = document.createElement('button');
            closeBtn.innerHTML = '×';
            closeBtn.style.cssText = `
                position: absolute;
                top: 10px;
                right: 15px;
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #999;
                line-height: 1;
            `;
            closeBtn.onclick = () => this.close(modal);
            modalContent.appendChild(closeBtn);
        }

        modal.appendChild(modalContent);
        document.body.appendChild(modal);

        // Animar entrada
        setTimeout(() => {
            modal.style.opacity = '1';
            modalContent.style.transform = 'scale(1)';
        }, 10);

        this.activeModal = modal;
        return modal;
    }

    close(modal) {
        if (!modal) return;
        
        modal.style.opacity = '0';
        const content = modal.querySelector('.modal-content');
        content.style.transform = 'scale(0.8)';
        
        setTimeout(() => {
            if (modal.parentNode) {
                modal.parentNode.removeChild(modal);
            }
            if (this.activeModal === modal) {
                this.activeModal = null;
            }
        }, 300);
    }

    confirm(message, onConfirm, onCancel) {
        const content = `
            <div style="text-align: center; padding: 20px;">
                <p style="margin-bottom: 20px; font-size: 16px;">${message}</p>
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <button class="btn btn-secondary" onclick="this.closest('.modal-overlay').remove()">Cancelar</button>
                    <button class="btn btn-primary" onclick="this.closest('.modal-overlay').remove(); if(typeof onConfirm === 'function') onConfirm();">Confirmar</button>
                </div>
            </div>
        `;
        
        return this.show(content, { title: 'Confirmação', closeButton: false });
    }
}

// =====================================================
// SISTEMA DE LOADING E ESTADOS
// =====================================================

class LoadingSystem {
    constructor() {
        this.loadingStates = new Map();
        this.init();
    }

    init() {
        // Criar overlay de loading global
        const globalLoading = document.createElement('div');
        globalLoading.id = 'global-loading';
        globalLoading.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.9);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        `;
        
        globalLoading.innerHTML = `
            <div style="text-align: center;">
                <div class="spinner" style="
                    width: 50px;
                    height: 50px;
                    border: 4px solid #f3f3f3;
                    border-top: 4px solid #007bff;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                    margin: 0 auto 20px;
                "></div>
                <p style="color: #666; font-size: 16px;">Carregando...</p>
            </div>
        `;
        
        document.body.appendChild(globalLoading);
        
        // Adicionar CSS para spinner
        if (!document.querySelector('#loading-styles')) {
            const style = document.createElement('style');
            style.id = 'loading-styles';
            style.textContent = `
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        }
    }

    showGlobal(message = 'Carregando...') {
        const loading = document.getElementById('global-loading');
        if (loading) {
            loading.querySelector('p').textContent = message;
            loading.style.display = 'flex';
        }
    }

    hideGlobal() {
        const loading = document.getElementById('global-loading');
        if (loading) {
            loading.style.display = 'none';
        }
    }

    showButton(button, text = 'Carregando...') {
        const originalText = button.innerHTML;
        const originalDisabled = button.disabled;
        
        button.disabled = true;
        button.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            ${text}
        `;
        
        this.loadingStates.set(button, { originalText, originalDisabled });
        
        return button;
    }

    hideButton(button) {
        const state = this.loadingStates.get(button);
        if (state) {
            button.disabled = state.originalDisabled;
            button.innerHTML = state.originalText;
            this.loadingStates.delete(button);
        }
    }

    showElement(element, text = 'Carregando...') {
        const originalContent = element.innerHTML;
        element.innerHTML = `
            <div style="text-align: center; padding: 20px;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <p class="mt-2">${text}</p>
            </div>
        `;
        
        this.loadingStates.set(element, { originalContent });
        return element;
    }

    hideElement(element) {
        const state = this.loadingStates.get(element);
        if (state) {
            element.innerHTML = state.originalContent;
            this.loadingStates.delete(element);
        }
    }
}

// =====================================================
// INICIALIZAÇÃO DOS SISTEMAS
// =====================================================

// Instanciar sistemas quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    // Sistema de notificações global
    window.notifications = new NotificationSystem();
    
    // Sistema de máscaras
    window.inputMasks = new InputMask();
    
    // Sistema de validação
    window.formValidator = new FormValidator();
    
    // Sistema de modais
    window.modals = new ModalSystem();
    
    // Sistema de loading
    window.loading = new LoadingSystem();
    
    // Log de inicialização
    console.log('✅ Sistemas CFC inicializados com sucesso!');
});

// =====================================================
// FUNÇÕES UTILITÁRIAS GLOBAIS
// =====================================================

// Formatar CPF
window.formatCPF = (cpf) => {
    cpf = cpf.replace(/\D/g, '');
    return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
};

// Formatar CNPJ
window.formatCNPJ = (cnpj) => {
    cnpj = cnpj.replace(/\D/g, '');
    return cnpj.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
};

// Formatar telefone
window.formatTelefone = (telefone) => {
    telefone = telefone.replace(/\D/g, '');
    if (telefone.length === 11) {
        return telefone.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    } else if (telefone.length === 10) {
        return telefone.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
    }
    return telefone;
};

// Formatar CEP
window.formatCEP = (cep) => {
    cep = cep.replace(/\D/g, '');
    return cep.replace(/(\d{5})(\d{3})/, '$1-$2');
};

// Formatar valor monetário
window.formatMoney = (value) => {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
};

// Formatar data
window.formatDate = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return d.toLocaleDateString('pt-BR');
};

// Formatar data e hora
window.formatDateTime = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return d.toLocaleString('pt-BR');
};

// Debounce para otimizar performance
window.debounce = (func, wait) => {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

// Throttle para otimizar performance
window.throttle = (func, limit) => {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
};
