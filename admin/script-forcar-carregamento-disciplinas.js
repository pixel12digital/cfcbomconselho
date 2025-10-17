// 🔧 Script para forçar carregamento de disciplinas no modal
// Execute este código no console da página: http://localhost/cfc-bom-conselho/admin/?page=turmas-teoricas&acao=nova&step=1

console.log('🔧 Script de forçar carregamento de disciplinas iniciado...');

// Função para forçar carregamento de disciplinas
function forcarCarregamentoDisciplinas() {
    console.log('🔄 Forçando carregamento de disciplinas...');
    
    // Verificar se o modal existe
    const modal = document.getElementById('modalGerenciarDisciplinas');
    if (!modal) {
        console.log('❌ Modal não encontrado. Tentando abrir...');
        
        // Tentar abrir o modal primeiro
        if (typeof abrirModalDisciplinasInterno === 'function') {
            abrirModalDisciplinasInterno();
            
            // Aguardar um pouco e tentar novamente
            setTimeout(() => {
                forcarCarregamentoDisciplinas();
            }, 1000);
        } else {
            console.error('❌ Função abrirModalDisciplinasInterno não encontrada');
        }
        return;
    }
    
    console.log('✅ Modal encontrado');
    
    // Verificar se o container de disciplinas existe
    const listaDisciplinas = document.getElementById('listaDisciplinas');
    if (!listaDisciplinas) {
        console.error('❌ Container listaDisciplinas não encontrado');
        console.log('🔍 Procurando por elementos similares...');
        const elementos = document.querySelectorAll('[id*="lista"], [id*="disciplina"]');
        console.log('Elementos encontrados:', elementos);
        return;
    }
    
    console.log('✅ Container listaDisciplinas encontrado');
    
    // Mostrar loading
    listaDisciplinas.innerHTML = `
        <div class="popup-loading-state show">
            <div class="popup-loading-spinner"></div>
            <div class="popup-loading-text">
                <h6>Carregando disciplinas...</h6>
                <p>Aguarde enquanto buscamos suas disciplinas</p>
            </div>
        </div>
    `;
    
    // Carregar disciplinas da API
    fetch('/cfc-bom-conselho/admin/api/disciplinas-clean.php?acao=listar')
        .then(response => {
            console.log('📡 Resposta da API:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text().then(text => {
                console.log('📄 Texto da resposta:', text.substring(0, 500));
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('❌ Erro ao fazer parse do JSON:', text.substring(0, 200));
                    throw new Error('JSON inválido: ' + e.message);
                }
            });
        })
        .then(data => {
            console.log('📊 Dados recebidos:', data);
            if (data.sucesso && data.disciplinas) {
                const disciplinas = data.disciplinas;
                console.log(`✅ ${disciplinas.length} disciplinas encontradas no banco`);
                
                // Atualizar contador
                const totalDisciplinas = document.getElementById('totalDisciplinas');
                if (totalDisciplinas) {
                    totalDisciplinas.textContent = disciplinas.length;
                    console.log('✅ Contador atualizado para:', disciplinas.length);
                } else {
                    console.error('❌ Elemento totalDisciplinas não encontrado');
                }
                
                // Gerar HTML das disciplinas
                let htmlDisciplinas = '';
                disciplinas.forEach(disciplina => {
                    const statusClass = disciplina.ativa == 1 ? 'active' : '';
                    const statusText = disciplina.ativa == 1 ? 'ATIVA' : 'INATIVA';
                    const statusColor = disciplina.ativa == 1 ? '#28a745' : '#6c757d';
                    
                    htmlDisciplinas += `
                        <div class="popup-item-card ${statusClass}">
                            <div class="popup-item-card-header">
                                <div class="popup-item-card-content">
                                    <h6 class="popup-item-card-title">${disciplina.nome}</h6>
                                    <div class="popup-item-card-code" style="background: ${statusColor}; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: bold;">
                                        ${statusText}
                                    </div>
                                    <div class="popup-item-card-description" style="margin-top: 0.5rem;">
                                        <div><strong>Código:</strong> ${disciplina.codigo}</div>
                                        <div><strong>Carga Horária:</strong> ${disciplina.carga_horaria_padrao || 0}h</div>
                                        <div><strong>Descrição:</strong> ${disciplina.descricao || 'Sem descrição'}</div>
                                    </div>
                                </div>
                                <div class="popup-item-card-actions">
                                    <button type="button" class="popup-item-card-menu" onclick="editarDisciplina(${disciplina.id})" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="popup-item-card-menu" onclick="confirmarExclusaoDisciplina(${disciplina.id}, '${disciplina.nome}')" title="Excluir" style="color: #dc3545;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                listaDisciplinas.innerHTML = htmlDisciplinas;
                console.log('✅ Disciplinas carregadas no modal com sucesso');
                
            } else {
                console.error('❌ Erro ao carregar disciplinas:', data.mensagem || 'Erro desconhecido');
                
                // Mostrar erro
                listaDisciplinas.innerHTML = `
                    <div class="popup-loading-state show">
                        <div class="popup-loading-text">
                            <h6 style="color: #dc3545;">Erro ao carregar disciplinas</h6>
                            <p>${data.mensagem || 'Erro desconhecido'}</p>
                            <button type="button" class="btn btn-primary btn-sm mt-2" onclick="forcarCarregamentoDisciplinas()">
                                Tentar novamente
                            </button>
                        </div>
                    </div>
                `;
                
                // Atualizar contador para 0
                const totalDisciplinas = document.getElementById('totalDisciplinas');
                if (totalDisciplinas) {
                    totalDisciplinas.textContent = '0';
                }
            }
        })
        .catch(error => {
            console.error('❌ Erro na requisição:', error);
            
            // Mostrar erro
            listaDisciplinas.innerHTML = `
                <div class="popup-loading-state show">
                    <div class="popup-loading-text">
                        <h6 style="color: #dc3545;">Erro de conexão</h6>
                        <p>Não foi possível carregar as disciplinas. Verifique sua conexão.</p>
                        <button type="button" class="btn btn-primary btn-sm mt-2" onclick="forcarCarregamentoDisciplinas()">
                            Tentar novamente
                        </button>
                    </div>
                </div>
            `;
            
            // Atualizar contador para 0
            const totalDisciplinas = document.getElementById('totalDisciplinas');
            if (totalDisciplinas) {
                totalDisciplinas.textContent = '0';
            }
        });
}

// Função para abrir modal e carregar disciplinas
function abrirModalECarregarDisciplinas() {
    console.log('🔧 Abrindo modal e carregando disciplinas...');
    
    // Verificar se a função existe
    if (typeof abrirModalDisciplinasInterno === 'function') {
        console.log('✅ Função encontrada, abrindo modal...');
        abrirModalDisciplinasInterno();
        
        // Aguardar um pouco e forçar carregamento
        setTimeout(() => {
            forcarCarregamentoDisciplinas();
        }, 1500);
    } else {
        console.error('❌ Função abrirModalDisciplinasInterno não encontrada');
    }
}

// Expor funções globalmente
window.forcarCarregamentoDisciplinas = forcarCarregamentoDisciplinas;
window.abrirModalECarregarDisciplinas = abrirModalECarregarDisciplinas;

console.log('✅ Script carregado! Use os comandos:');
console.log('  - abrirModalECarregarDisciplinas() - Abrir modal e carregar disciplinas');
console.log('  - forcarCarregamentoDisciplinas() - Forçar carregamento (se modal já estiver aberto)');
