// =====================================================
// CORREÇÕES EMERGENCIAS PARA MODAL TRAVADO
// Sistema de correções em tempo real que funciona independentemente
// =====================================================

(function() {
    'use strict';
    
    console.log('🔧 [MODAL-FIX] Carregando sistema de correções emergenciais...');
    
    // =====================================================
    // FUNÇÃO DE DIAGNÓSTICO COMPLETO
    // =====================================================
    window.diagnosticarModal = function() {
        console.group('🔍 [DIAGNÓSTICO] Estado Completo do Modal');
        
        const modal = document.getElementById('modalEditarAgendamento');
        if (!modal) {
            console.error('❌ Modal não encontrado no DOM');
            console.groupEnd();
            return;
        }
        
        // 1. Verificar visibilidade do modal
        const modalStyle = window.getComputedStyle(modal);
        console.log('📊 Modal:', {
            display: modalStyle.display,
            visibility: modalStyle.visibility,
            opacity: modalStyle.opacity,
            zIndex: modalStyle.zIndex,
            pointerEvents: modalStyle.pointerEvents,
            position: modalStyle.position
        });
        
        // 2. Verificar todos os campos do formulário
        const campos = {
            'editDisciplinaNome': document.getElementById('editDisciplinaNome'),
            'editNomeAula': document.getElementById('editNomeAula'), // Campo alternativo
            'editDisciplinaId': document.getElementById('editDisciplinaId'),
            'editDataAula': document.getElementById('editDataAula'),
            'editHoraInicio': document.getElementById('editHoraInicio'),
            'editInstrutor': document.getElementById('editInstrutor'),
            'editSala': document.getElementById('editSala'),
            'editQuantidadeAulas': document.getElementById('editQuantidadeAulas'),
            'editObservacoes': document.getElementById('editObservacoes')
        };
        
        console.group('📝 Campos do Formulário:');
        Object.keys(campos).forEach(id => {
            const campo = campos[id];
            if (!campo) {
                console.error(`❌ ${id}: NÃO ENCONTRADO`);
                return;
            }
            
            const estilo = window.getComputedStyle(campo);
            const rect = campo.getBoundingClientRect();
            
            console.log(`${id}:`, {
                existe: true,
                valor: campo.value,
                valorAtributo: campo.getAttribute('value'),
                display: estilo.display,
                visibility: estilo.visibility,
                opacity: estilo.opacity,
                color: estilo.color,
                backgroundColor: estilo.backgroundColor,
                zIndex: estilo.zIndex,
                pointerEvents: estilo.pointerEvents,
                width: rect.width,
                height: rect.height,
                visivelNaTela: rect.width > 0 && rect.height > 0 && rect.top >= 0,
                tipo: campo.tagName,
                readonly: campo.hasAttribute('readonly'),
                disabled: campo.disabled
            });
        });
        console.groupEnd();
        
        // 3. Verificar botões
        const botoes = {
            'btnFecharModalEdicao': document.getElementById('btnFecharModalEdicao'),
            'btnCancelarModalEdicao': document.getElementById('btnCancelarModalEdicao'),
            'btnEmergenciaFechar1': document.getElementById('btnEmergenciaFechar1'),
            'btnEmergenciaFechar2': document.getElementById('btnEmergenciaFechar2')
        };
        
        console.group('🔘 Botões:');
        Object.keys(botoes).forEach(id => {
            const btn = botoes[id];
            if (!btn) {
                console.error(`❌ ${id}: NÃO ENCONTRADO`);
                return;
            }
            
            const estilo = window.getComputedStyle(btn);
            const rect = btn.getBoundingClientRect();
            
            console.log(`${id}:`, {
                existe: true,
                display: estilo.display,
                visibility: estilo.visibility,
                opacity: estilo.opacity,
                zIndex: estilo.zIndex,
                pointerEvents: estilo.pointerEvents,
                cursor: estilo.cursor,
                visivelNaTela: rect.width > 0 && rect.height > 0 && rect.top >= 0,
                onclick: btn.onclick ? 'DEFINIDO' : 'NÃO DEFINIDO',
                hasAttributeOnclick: btn.hasAttribute('onclick')
            });
        });
        console.groupEnd();
        
        console.groupEnd();
    };
    
    // =====================================================
    // FUNÇÃO DE EMERGÊNCIA PARA FECHAR MODAL
    // =====================================================
    window.fecharModalEmergencia = function() {
        console.log('🚨 [EMERGÊNCIA] Fechando modal forçadamente...');
        
        // Remover TODOS os modais
        const modais = document.querySelectorAll('#modalEditarAgendamento, .modal-overlay');
        modais.forEach(modal => {
            modal.style.cssText = `
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
                pointer-events: none !important;
                z-index: -1 !important;
            `;
            if (modal.parentNode) {
                modal.parentNode.removeChild(modal);
            }
        });
        
        // Restaurar body
        document.body.style.overflow = 'auto';
        document.body.style.paddingRight = '';
        document.body.classList.remove('modal-open', 'modal-unlocked-view');
        
        // Remover backdrops
        document.querySelectorAll('.modal-backdrop').forEach(b => {
            if (b.parentNode) b.parentNode.removeChild(b);
        });
        
        // NÃO recarregar página - apenas fechar modal
    };
    
    // =====================================================
    // CORREÇÃO 1: Forçar visibilidade dos valores
    // =====================================================
    const forcarVisibilidadeValores = function() {
        const campos = ['editDisciplinaNome', 'editNomeAula', 'editDataAula', 'editHoraInicio', 'editInstrutor', 'editSala'];
        
        campos.forEach(id => {
            const campo = document.getElementById(id);
            if (campo && campo.value) {
                // Forçar estilos para garantir visibilidade
                const bgColor = campo.hasAttribute('readonly') ? '#f8f9fa' : 'white';
                campo.style.setProperty('color', '#333', 'important');
                campo.style.setProperty('-webkit-text-fill-color', '#333', 'important');
                campo.style.setProperty('opacity', '1', 'important');
                campo.style.setProperty('visibility', 'visible', 'important');
                campo.style.setProperty('background-color', bgColor, 'important');
                
                // Para campos readonly, garantir que value seja aplicado corretamente
                if (campo.hasAttribute('readonly')) {
                    const valor = campo.value;
                    campo.removeAttribute('readonly');
                    campo.value = valor;
                    campo.setAttribute('readonly', 'readonly');
                    campo.setAttribute('value', valor);
                }
            }
        });
    };
    
    // =====================================================
    // CORREÇÃO 2: Interceptar preenchimento de campos
    // =====================================================
    const interceptarPreenchimento = function() {
        // Monitorar mudanças nos campos
        const campos = ['editDisciplinaNome', 'editNomeAula', 'editDataAula', 'editHoraInicio', 'editInstrutor', 'editSala'];
        
        campos.forEach(id => {
            const campo = document.getElementById(id);
            if (campo) {
                // Observar mudanças no value usando MutationObserver
                const observer = new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                            setTimeout(() => forcarVisibilidadeValores(), 10);
                        }
                    });
                });
                
                observer.observe(campo, {
                    attributes: true,
                    attributeFilter: ['value']
                });
                
                // Também observar mudanças no value diretamente
                let valorAnterior = campo.value;
                setInterval(() => {
                    if (campo.value !== valorAnterior) {
                        valorAnterior = campo.value;
                        if (campo.value) {
                            setTimeout(() => forcarVisibilidadeValores(), 10);
                        }
                    }
                }, 100);
            }
        });
    };
    
    // =====================================================
    // CORREÇÃO 3: Garantir que botões sejam clicáveis
    // =====================================================
    const garantirBotoesClicaveis = function() {
        const botaoIds = ['btnFecharModalEdicao', 'btnCancelarModalEdicao', 'btnEmergenciaFechar1', 'btnEmergenciaFechar2'];
        
        botaoIds.forEach(id => {
            const btn = document.getElementById(id);
            if (btn) {
                // Forçar estilos
                btn.style.setProperty('pointer-events', 'auto', 'important');
                btn.style.setProperty('cursor', 'pointer', 'important');
                btn.style.setProperty('z-index', '99999999', 'important');
                
                // Garantir onclick inline
                if (!btn.hasAttribute('onclick')) {
                    const onclickCode = `
                        if(typeof fecharModalEdicao === 'function') {
                            fecharModalEdicao();
                        } else {
                            const m = document.getElementById('modalEditarAgendamento');
                            if(m) { m.style.display='none'; m.remove(); document.body.style.overflow='auto'; }
                        }
                        return false;
                    `;
                    btn.setAttribute('onclick', onclickCode);
                }
                
                // Adicionar listener na fase de captura (alta prioridade)
                const handler = function(e) {
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    if (typeof fecharModalEdicao === 'function') {
                        fecharModalEdicao();
                    }
                };
                
                btn.addEventListener('click', handler, true);
                btn.addEventListener('mousedown', handler, true);
            }
        });
    };
    
    // =====================================================
    // CORREÇÃO 4: Remover overlays bloqueadores
    // =====================================================
    const removerOverlaysBloqueadores = function() {
        const overlays = document.querySelectorAll('[style*="position: fixed"]');
        overlays.forEach(overlay => {
            const style = window.getComputedStyle(overlay);
            const zIndex = parseInt(style.zIndex) || 0;
            
            // Se não é o modal principal e tem z-index alto, pode estar bloqueando
            if (overlay.id !== 'modalEditarAgendamento' && 
                zIndex >= 9999 && 
                !overlay.id.includes('Emergencia') &&
                !overlay.classList.contains('modal-content')) {
                
                // Verificar se está bloqueando cliques
                if (style.pointerEvents !== 'none') {
                    overlay.style.setProperty('pointer-events', 'none', 'important');
                    
                    // Permitir cliques nos filhos
                    const filhos = overlay.querySelectorAll('*');
                    filhos.forEach(filho => {
                        filho.style.setProperty('pointer-events', 'auto', 'important');
                    });
                }
            }
        });
    };
    
    // =====================================================
    // CORREÇÃO 5: Verificação periódica
    // =====================================================
    const verificarPeriodicamente = function() {
        setInterval(() => {
            const modal = document.getElementById('modalEditarAgendamento');
            if (!modal) return;
            
            const style = window.getComputedStyle(modal);
            if (style.display === 'none') return;
            
            // Verificar e corrigir campos
            forcarVisibilidadeValores();
            garantirBotoesClicaveis();
            removerOverlaysBloqueadores();
        }, 1000);
    };
    
    // =====================================================
    // ATALHO DE TECLADO GLOBAL
    // =====================================================
    document.addEventListener('keydown', function(e) {
        // Ctrl+Alt+F = Fechar modal (sem recarregar)
        if (e.ctrlKey && e.altKey && (e.key === 'F' || e.key === 'f')) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            if (typeof fecharModalEdicao === 'function') {
                fecharModalEdicao();
            } else if (typeof window.fecharModalEmergencia === 'function') {
                window.fecharModalEmergencia();
            }
        }
    }, true);
    
    // =====================================================
    // INICIALIZAÇÃO
    // =====================================================
    function inicializar() {
        console.log('🔧 [MODAL-FIX] Inicializando correções...');
        
        // Executar correções imediatamente
        interceptarPreenchimento();
        garantirBotoesClicaveis();
        removerOverlaysBloqueadores();
        verificarPeriodicamente();
        
        // Observar quando modal for criado
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if ((node.nodeType === 1 && node.id === 'modalEditarAgendamento') || 
                        (node.querySelector && node.querySelector('#modalEditarAgendamento'))) {
                        console.log('🔧 [MODAL-FIX] Modal detectado - aplicando correções...');
                        
                        setTimeout(() => {
                            forcarVisibilidadeValores();
                            garantirBotoesClicaveis();
                            removerOverlaysBloqueadores();
                            
                            // Executar diagnóstico automaticamente
                            if (typeof window.diagnosticarModal === 'function') {
                                setTimeout(() => window.diagnosticarModal(), 500);
                            }
                        }, 100);
                    }
                });
            });
        });
        
        observer.observe(document.body, { childList: true, subtree: true });
        
        // Executar após delay para pegar modal já existente
        setTimeout(() => {
            const modal = document.getElementById('modalEditarAgendamento');
            if (modal) {
                forcarVisibilidadeValores();
                garantirBotoesClicaveis();
                removerOverlaysBloqueadores();
            }
        }, 1000);
    }
    
    // Aguardar DOM estar pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializar);
    } else {
        inicializar();
    }
    
    console.log('✅ [MODAL-FIX] Sistema de correções carregado!');
    console.log('💡 Use window.diagnosticarModal() para diagnóstico completo');
    console.log('💡 Use Ctrl+Alt+F para fechar modal em emergência');
})();

