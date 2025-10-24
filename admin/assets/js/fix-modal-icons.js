/**
 * Script específico para corrigir problema de ícones de edição aparecendo sobre modais
 * Força o escondimento de ícones de edição quando modais estão abertos
 */

// Função para esconder ícones de edição quando modal estiver aberto
function esconderIconesEdicao() {
    console.log('🔧 Escondendo ícones de edição...');
    
    // Selecionar todos os ícones de edição que não estão dentro de modais
    const iconesEdicao = document.querySelectorAll('i.fas.fa-edit:not([id*="modal"]):not([class*="modal"]):not([class*="popup"])');
    const iconesPencil = document.querySelectorAll('i.fas.fa-pencil:not([id*="modal"]):not([class*="modal"]):not([class*="popup"])');
    const iconesPencilAlt = document.querySelectorAll('i.fas.fa-pencil-alt:not([id*="modal"]):not([class*="modal"]):not([class*="popup"])');
    const editIcons = document.querySelectorAll('.edit-icon:not([id*="modal"]):not([class*="modal"]):not([class*="popup"])');
    
    // Combinar todos os seletores
    const todosIcones = [...iconesEdicao, ...iconesPencil, ...iconesPencilAlt, ...editIcons];
    
    todosIcones.forEach(icon => {
        // Verificar se o ícone não está dentro de um modal
        if (!icon.closest('[id*="modal"]') && !icon.closest('[class*="modal"]') && !icon.closest('[class*="popup"]')) {
            icon.style.cssText = `
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
                z-index: -1 !important;
                position: absolute !important;
                left: -9999px !important;
                top: -9999px !important;
            `;
            console.log('✅ Ícone escondido:', icon);
        }
    });
    
    console.log(`🔧 Total de ícones escondidos: ${todosIcones.length}`);
}

// Função para restaurar ícones de edição quando modal for fechado
function restaurarIconesEdicao() {
    console.log('🔧 Restaurando ícones de edição...');
    
    // Selecionar todos os ícones de edição que foram escondidos
    const todosIcones = document.querySelectorAll('i.fas.fa-edit, i.fas.fa-pencil, i.fas.fa-pencil-alt, .edit-icon');
    
    todosIcones.forEach(icon => {
        // Verificar se o ícone não está dentro de um modal
        if (!icon.closest('[id*="modal"]') && !icon.closest('[class*="modal"]') && !icon.closest('[class*="popup"]')) {
            icon.style.cssText = '';
            console.log('✅ Ícone restaurado:', icon);
        }
    });
    
    console.log(`🔧 Total de ícones restaurados: ${todosIcones.length}`);
}

// Função para verificar se há modais abertos
function verificarModaisAbertos() {
    const modais = document.querySelectorAll('[id*="modal"], [class*="modal"], [class*="popup"]');
    let modalAberto = false;
    
    modais.forEach(modal => {
        const style = window.getComputedStyle(modal);
        if (style.display !== 'none' && style.visibility !== 'hidden' && style.opacity !== '0') {
            modalAberto = true;
        }
    });
    
    return modalAberto;
}

// Observer para detectar mudanças no DOM (modais sendo abertos/fechados)
const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        if (mutation.type === 'childList') {
            // Verificar se um modal foi adicionado ou removido
            const modalAberto = verificarModaisAbertos();
            
            if (modalAberto) {
                setTimeout(esconderIconesEdicao, 100); // Delay para garantir que o modal foi renderizado
            } else {
                setTimeout(restaurarIconesEdicao, 100);
            }
        }
    });
});

// Observer para detectar mudanças nos atributos dos modais
const attributeObserver = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        if (mutation.type === 'attributes' && (mutation.attributeName === 'style' || mutation.attributeName === 'class')) {
            const modalAberto = verificarModaisAbertos();
            
            if (modalAberto) {
                setTimeout(esconderIconesEdicao, 100);
            } else {
                setTimeout(restaurarIconesEdicao, 100);
            }
        }
    });
});

// Função para inicializar o sistema
function inicializarCorrecaoIcones() {
    console.log('🔧 Inicializando correção de ícones de edição...');
    
    // Iniciar observadores
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Observar mudanças nos atributos dos modais
    document.querySelectorAll('[id*="modal"], [class*="modal"], [class*="popup"]').forEach(modal => {
        attributeObserver.observe(modal, {
            attributes: true,
            attributeFilter: ['style', 'class']
        });
    });
    
    // Verificar estado inicial
    if (verificarModaisAbertos()) {
        esconderIconesEdicao();
    }
    
    console.log('✅ Sistema de correção de ícones inicializado');
}

// Função para forçar correção imediata
function forcarCorrecaoIcones() {
    console.log('🔧 Forçando correção de ícones...');
    esconderIconesEdicao();
}

// Inicializar quando o DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarCorrecaoIcones);
} else {
    inicializarCorrecaoIcones();
}

// Tornar funções globalmente acessíveis
window.esconderIconesEdicao = esconderIconesEdicao;
window.restaurarIconesEdicao = restaurarIconesEdicao;
window.forcarCorrecaoIcones = forcarCorrecaoIcones;
