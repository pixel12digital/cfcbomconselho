// =====================================================
// CORREÇÕES EMERGENCIAS PARA MODAL TRAVADO
// Adicionar este script ANTES do fechamento da tag </script> principal
// =====================================================

(function() {
    'use strict';
    
    console.log('🔧 [CORREÇÕES] Carregando correções emergenciais...');
    
    // =====================================================
    // CORREÇÃO 1: Garantir que valores sejam visíveis após preenchimento
    // =====================================================
    const forcarVisibilidadeValores = function() {
        const campos = ['editDisciplinaNome', 'editDataAula', 'editHoraInicio', 'editInstrutor', 'editSala'];
        
        campos.forEach(id => {
            const campo = document.getElementById(id);
            if (campo && campo.value) {
                // Forçar estilos para garantir visibilidade
                campo.style.cssText += `
                    color: #333 !important;
                    -webkit-text-fill-color: #333 !important;
                    opacity: 1 !important;
                    visibility: visible !important;
                    background-color: ${campo.hasAttribute('readonly') ? '#f8f9fa' : 'white'} !important;
                `;
                
                // Para campos readonly, garantir que value seja aplicado corretamente
                if (campo.hasAttribute('readonly')) {
                    const valor = campo.value;
                    campo.removeAttribute('readonly');
                    campo.value = valor;
                    campo.setAttribute('readonly', 'readonly');
                    campo.setAttribute('value', valor);
                }
                
                console.log(`✅ [CORREÇÃO] ${id} forçado a ser visível:`, campo.value);
            }
        });
    };
    
    // =====================================================
    // CORREÇÃO 2: Interceptar preenchimento de campos e garantir visibilidade
    // =====================================================
    const interceptarPreenchimento = function() {
        // Interceptar função preencherCampo se existir
        if (window.preencherCampo) {
            const originalPreencherCampo = window.preencherCampo;
            window.preencherCampo = function(campo, valor, id) {
                const resultado = originalPreencherCampo.apply(this, arguments);
                
                // Após preencher, forçar visibilidade
                setTimeout(() => {
                    if (campo && campo.value) {
                        campo.style.cssText += `
                            color: #333 !important;
                            -webkit-text-fill-color: #333 !important;
                            opacity: 1 !important;
                        `;
                    }
                }, 10);
                
                return resultado;
            };
        }
        
        // Interceptar atribuição direta de value
        const campos = ['editDisciplinaNome', 'editDataAula', 'editHoraInicio'];
        campos.forEach(id => {
            const campo = document.getElementById(id);
            if (campo) {
                // Criar propriedade customizada para interceptar mudanças
                let valorAtual = campo.value;
                Object.defineProperty(campo, 'value', {
                    get: function() {
                        return valorAtual;
                    },
                    set: function(novoValor) {
                        valorAtual = novoValor;
                        // Forçar visibilidade ao definir valor
                        this.style.cssText += `
                            color: #333 !important;
                            -webkit-text-fill-color: #333 !important;
                            opacity: 1 !important;
                        `;
                        // Também definir no atributo
                        this.setAttribute('value', novoValor);
                    },
                    configurable: true
                });
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
                btn.style.cssText += `
                    pointer-events: auto !important;
                    cursor: pointer !important;
                    z-index: 99999999 !important;
                `;
                
                // Garantir que onclick funcione mesmo se event listeners falharem
                if (!btn.hasAttribute('onclick')) {
                    if (id.includes('Fechar') || id.includes('Cancelar') || id.includes('Emergencia')) {
                        btn.setAttribute('onclick', `
                            if(typeof window.fecharModalEmergencia === 'function') {
                                window.fecharModalEmergencia();
                            } else if(typeof fecharModalEdicao === 'function') {
                                fecharModalEdicao();
                            } else {
                                const m = document.getElementById('modalEditarAgendamento');
                                if(m) { m.style.display='none'; m.remove(); document.body.style.overflow='auto'; }
                            }
                            return false;
                        `);
                    }
                }
                
                // Adicionar listener na fase de captura (mais alta prioridade)
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (id.includes('Fechar') || id.includes('Cancelar') || id.includes('Emergencia')) {
                        if(typeof window.fecharModalEmergencia === 'function') {
                            window.fecharModalEmergencia();
                        } else if(typeof fecharModalEdicao === 'function') {
                            fecharModalEdicao();
                        }
                    }
                }, true);
            }
        });
    };
    
    // =====================================================
    // CORREÇÃO 4: Remover overlays bloqueadores
    // =====================================================
    const removerOverlaysBloqueadores = function() {
        // Encontrar todos os overlays
        const overlays = document.querySelectorAll('[style*="position: fixed"][style*="z-index"]');
        overlays.forEach(overlay => {
            const style = window.getComputedStyle(overlay);
            const zIndex = parseInt(style.zIndex) || 0;
            
            // Se não é o modal principal e tem z-index alto, pode estar bloqueando
            if (overlay.id !== 'modalEditarAgendamento' && 
                zIndex >= 9999 && 
                overlay.id !== 'btnEmergenciaFechar1' && 
                overlay.id !== 'btnEmergenciaFechar2') {
                
                // Verificar se está bloqueando cliques
                if (style.pointerEvents !== 'none' && 
                    !overlay.classList.contains('modal-content')) {
                    
                    // Criar "buraco" para cliques passarem
                    overlay.style.pointerEvents = 'none';
                    
                    // Mas permitir cliques nos filhos
                    const filhos = overlay.querySelectorAll('*');
                    filhos.forEach(filho => {
                        filho.style.pointerEvents = 'auto';
                    });
                    
                    console.log('🔧 [CORREÇÃO] Overlay bloqueador ajustado:', overlay.id || overlay.className);
                }
            }
        });
    };
    
    // =====================================================
    // CORREÇÃO 5: Verificação periódica de valores
    // =====================================================
    const verificarValoresPeriodicamente = function() {
        setInterval(() => {
            const modal = document.getElementById('modalEditarAgendamento');
            if (!modal) return;
            
            const style = window.getComputedStyle(modal);
            if (style.display === 'none') return;
            
            // Verificar se campos têm valores mas não estão visíveis
            const campos = {
                'editDisciplinaNome': document.getElementById('editDisciplinaNome'),
                'editDataAula': document.getElementById('editDataAula'),
                'editHoraInicio': document.getElementById('editHoraInicio')
            };
            
            Object.keys(campos).forEach(id => {
                const campo = campos[id];
                if (campo && campo.value) {
                    const estilo = window.getComputedStyle(campo);
                    
                    // Se tem valor mas não está visível, corrigir
                    if (estilo.opacity === '0' || estilo.visibility === 'hidden' || 
                        estilo.color === estilo.backgroundColor) {
                        console.warn(`⚠️ [CORREÇÃO] ${id} tem valor mas não está visível - corrigindo...`);
                        forcarVisibilidadeValores();
                    }
                }
            });
        }, 1000);
    };
    
    // =====================================================
    // CORREÇÃO 6: Função de emergência melhorada
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
        document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        
        // Recarregar página após 100ms
        setTimeout(() => {
            const turmaId = new URLSearchParams(window.location.search).get('turma_id') || '13';
            window.location.href = `?page=turmas-teoricas&acao=detalhes&turma_id=${turmaId}&semana_calendario=0`;
        }, 100);
    };
    
    // =====================================================
    // INICIALIZAÇÃO
    // =====================================================
    
    // Executar quando DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(inicializar, 500);
        });
    } else {
        setTimeout(inicializar, 500);
    }
    
    function inicializar() {
        console.log('🔧 [CORREÇÕES] Inicializando correções...');
        
        // Executar correções imediatamente
        interceptarPreenchimento();
        garantirBotoesClicaveis();
        removerOverlaysBloqueadores();
        verificarValoresPeriodicamente();
        
        // Observar quando modal for criado
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.id === 'modalEditarAgendamento' || 
                        (node.querySelector && node.querySelector('#modalEditarAgendamento'))) {
                        console.log('🔧 [CORREÇÕES] Modal detectado - aplicando correções...');
                        
                        setTimeout(() => {
                            forcarVisibilidadeValores();
                            garantirBotoesClicaveis();
                            removerOverlaysBloqueadores();
                        }, 100);
                    }
                });
            });
        });
        
        observer.observe(document.body, { childList: true, subtree: true });
        
        // Executar após um delay para pegar modal já existente
        setTimeout(() => {
            forcarVisibilidadeValores();
            garantirBotoesClicaveis();
            removerOverlaysBloqueadores();
        }, 1000);
    }
    
    console.log('✅ [CORREÇÕES] Sistema de correções carregado!');
})();

