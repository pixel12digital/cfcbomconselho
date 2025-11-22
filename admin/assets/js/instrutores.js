/**
 * JavaScript para gerenciamento de Instrutores
 * Sistema CFC - Bom Conselho
 */

// Cache para o caminho da API
let caminhoAPIInstrutoresCache = null;

// Função para converter data brasileira (dd/mm/aaaa) para ISO (aaaa-mm-dd) - CORRIGIDA
function converterDataBrasileiraParaISO(dataBrasileira) {
    if (!dataBrasileira || dataBrasileira.trim() === '') {
        return null;
    }
    
    // Verificar se já está no formato YYYY-MM-DD
    if (/^\d{4}-\d{2}-\d{2}$/.test(dataBrasileira)) {
        return dataBrasileira;
    }
    
    // Verificar formato dd/mm/aaaa
    const regex = /^(\d{2})\/(\d{2})\/(\d{4})$/;
    const match = dataBrasileira.match(regex);
    
    if (!match) return null;
    
    const dia = parseInt(match[1]);
    const mes = parseInt(match[2]);
    const ano = parseInt(match[3]);
    
    // Validar valores
    if (dia < 1 || dia > 31) return null;
    if (mes < 1 || mes > 12) return null;
    if (ano < 1900 || ano > 2100) return null;
    
    // Verificar se a data é válida
    const data = new Date(ano, mes - 1, dia);
    if (data.getDate() !== dia || data.getMonth() !== mes - 1 || data.getFullYear() !== ano) {
        return null;
    }
    
    // Retornar no formato ISO sem conversão de fuso horário
    return `${ano}-${mes.toString().padStart(2, '0')}-${dia.toString().padStart(2, '0')}`;
}

// Função para converter data de DD/MM/YYYY para YYYY-MM-DD (MySQL)
function converterDataParaMySQL(dataString) {
    if (!dataString || dataString.trim() === '') {
        return null; // Retorna null para campos vazios
    }
    
    // Verificar se já está no formato YYYY-MM-DD
    if (/^\d{4}-\d{2}-\d{2}$/.test(dataString)) {
        return dataString;
    }
    
    // Usar a função corrigida para conversão
    return converterDataBrasileiraParaISO(dataString);
}

// Função para converter data de YYYY-MM-DD para DD/MM/YYYY
function converterDataParaExibicao(dataString) {
    if (!dataString || dataString === '0000-00-00' || dataString.trim() === '') {
        return '';
    }
    
    try {
        // Verificar se está no formato YYYY-MM-DD
        const match = dataString.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (match) {
            const [, ano, mes, dia] = match;
            const dataFormatada = `${dia}/${mes}/${ano}`;
            console.log(`✅ Data convertida para exibição: ${dataString} → ${dataFormatada}`);
            return dataFormatada;
        }
        
        // Fallback para outras conversões usando Date
        const data = new Date(dataString);
        if (!isNaN(data.getTime())) {
            const dia = String(data.getDate()).padStart(2, '0');
            const mes = String(data.getMonth() + 1).padStart(2, '0');
            const ano = data.getFullYear();
            const dataFormatada = `${dia}/${mes}/${ano}`;
            console.log(`✅ Data convertida para exibição (fallback): ${dataString} → ${dataFormatada}`);
            return dataFormatada;
        } else {
            console.warn(`⚠️ Data inválida para conversão: ${dataString}`);
            return '';
        }
    } catch (e) {
        console.warn(`⚠️ Erro ao converter data: ${dataString}`, e);
        return '';
    }
}

/**
 * Carregar foto existente no preview
 */
function carregarFotoExistente(caminhoFoto) {
    console.log('📷 Função carregarFotoExistente chamada com:', caminhoFoto);
    console.log('📷 Tipo do parâmetro:', typeof caminhoFoto);
    
    if (caminhoFoto && caminhoFoto.trim() !== '') {
        console.log('📷 Buscando elementos do DOM...');
        const preview = document.getElementById('foto-preview');
        const container = document.getElementById('preview-container');
        const placeholder = document.getElementById('placeholder-foto');
        
        console.log('📷 Elementos encontrados:');
        console.log('📷 - preview:', preview);
        console.log('📷 - container:', container);
        console.log('📷 - placeholder:', placeholder);
        
        // Construir URL completa da foto
        let urlFoto;
        if (caminhoFoto.startsWith('http')) {
            urlFoto = caminhoFoto;
        } else {
            // Construir URL baseada no contexto atual
            const baseUrl = window.location.origin + window.location.pathname.split('/').slice(0, -2).join('/');
            urlFoto = `${baseUrl}/${caminhoFoto}`;
        }
        
        // Debug: Testar URL antes de usar
        console.log('🔍 Testando URL da foto:', urlFoto);
        console.log('🔍 Base URL:', window.location.origin);
        console.log('🔍 Pathname:', window.location.pathname);
        console.log('🔍 Pathname split:', window.location.pathname.split('/'));
        console.log('🔍 Pathname slice:', window.location.pathname.split('/').slice(0, -2));
        console.log('🔍 Pathname join:', window.location.pathname.split('/').slice(0, -2).join('/'));
        
        console.log('📷 URL da foto construída:', urlFoto);
        
        if (preview && container && placeholder) {
            preview.src = urlFoto;
            container.style.display = 'block';
            placeholder.style.display = 'none';
            
            console.log('📷 Elementos configurados - aguardando carregamento...');
            
            // Verificar se a imagem carregou
            preview.onload = function() {
                console.log('✅ Foto existente carregada com sucesso');
            };
            
            preview.onerror = function() {
                console.error('❌ Erro ao carregar foto:', urlFoto);
                // Se der erro, mostrar placeholder
                container.style.display = 'none';
                placeholder.style.display = 'block';
            };
        } else {
            console.error('❌ Elementos do DOM não encontrados!');
        }
        
    } else {
        console.log('📷 Caminho da foto vazio ou inválido');
        // Se não há foto, mostrar placeholder
        const container = document.getElementById('preview-container');
        const placeholder = document.getElementById('placeholder-foto');
        
        if (container && placeholder) {
            container.style.display = 'none';
            placeholder.style.display = 'block';
        }
        
        console.log('📷 Placeholder configurado');
    }
}

// Função para detectar o caminho correto da API
async function detectarCaminhoAPIInstrutores() {
    if (caminhoAPIInstrutoresCache) {
        return caminhoAPIInstrutoresCache;
    }
    
    const baseUrl = window.location.origin;
    const pathname = window.location.pathname;
    
    // Detectar caminho baseado na URL atual
    if (pathname.includes('/admin/')) {
        const basePath = pathname.substring(0, pathname.lastIndexOf('/admin/'));
        caminhoAPIInstrutoresCache = baseUrl + basePath + '/admin/api/instrutores.php';
    } else {
        caminhoAPIInstrutoresCache = baseUrl + '/admin/api/instrutores.php';
    }
    
    console.log('🌐 Caminho da API Instrutores detectado:', caminhoAPIInstrutoresCache);
    
    // Testar se a URL está acessível
    try {
        const testResponse = await fetch(caminhoAPIInstrutoresCache, {
            method: 'GET',
            credentials: 'include'  // Mudança importante: 'same-origin' para 'include'
        });
        console.log('✅ API Instrutores acessível:', testResponse.status);
    } catch (error) {
        console.warn('⚠️ API Instrutores pode não estar acessível:', error.message);
    }
    
    return caminhoAPIInstrutoresCache;
}

// Função para fazer requisições à API
async function fetchAPIInstrutores(endpoint = '', options = {}) {
    const baseApiUrl = await detectarCaminhoAPIInstrutores();
    const url = baseApiUrl + endpoint;
    
    console.log('📡 Fazendo requisição para:', url);
    console.log('📡 Método:', options.method || 'GET');
    console.log('📡 Opções:', options);
    
    // Não definir Content-Type se for FormData (deixar o browser definir automaticamente)
    const isFormData = options.body instanceof FormData;
    
    const defaultOptions = {
        headers: isFormData ? {
            'X-Requested-With': 'XMLHttpRequest'
        } : {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'include'  // Mudança importante: 'same-origin' para 'include'
    };
    
    const mergedOptions = {
        ...defaultOptions,
        ...options,
        headers: {
            ...defaultOptions.headers,
            ...options.headers
        }
    };
    
    try {
        console.log('📡 Iniciando fetch...');
        const response = await fetch(url, mergedOptions);
        console.log('📡 Resposta recebida:', response.status, response.statusText);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('❌ Resposta não OK:', response.status, errorText);
            
            // Tentar fazer parse do erro se for JSON
            try {
                const errorData = JSON.parse(errorText);
                throw new Error(`HTTP ${response.status}: ${response.statusText} - ${errorData.error || errorText}`);
            } catch (parseError) {
                // Se não for JSON, usar o texto como está
                throw new Error(`HTTP ${response.status}: ${response.statusText} - ${errorText}`);
            }
        }
        
        console.log('✅ Requisição bem-sucedida');
        return response;
    } catch (error) {
        console.error('❌ Erro na requisição:', error);
        console.error('❌ URL tentada:', url);
        console.error('❌ Opções:', mergedOptions);
        
        // Verificar se é erro de rede
        if (error.name === 'TypeError' && error.message.includes('fetch')) {
            throw new Error(`Erro de conectividade: ${error.message}`);
        }
        
        throw error;
    }
}

// FUNÇÕES DE MODAL REMOVIDAS - Agora controladas exclusivamente por instrutores-page.js
// As funções window.abrirModalInstrutor e window.fecharModalInstrutor foram removidas
// para evitar conflito com instrutores-page.js que tem versões mais completas.
// Se precisar abrir/fechar modal de instrutor, use as funções de instrutores-page.js
// ou chame diretamente: novoInstrutor(), editarInstrutor(id), fecharModalInstrutor()

// Função wrapper para compatibilidade (delega para instrutores-page.js se disponível)
// IMPORTANTE: NÃO chama novoInstrutor() para evitar loop infinito
window.abrirModalInstrutor = async function() {
    console.log('⚠️ [instrutores.js] window.abrirModalInstrutor chamada - usando função base');
    
    // Se a função base existir (de instrutores-page.js), use ela diretamente
    // NÃO chama novoInstrutor() para evitar loop infinito
    if (typeof window.abrirModalInstrutorBase === 'function') {
        console.log('✅ Usando window.abrirModalInstrutorBase()');
        window.abrirModalInstrutorBase();
        return;
    }
    
    // Fallback: apenas abrir modal básico se função base não existir
    console.log('⚠️ Função base não encontrada, usando fallback básico');
    const modal = document.getElementById('modalInstrutor');
    if (modal) {
        modal.style.display = 'block';
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
};

// REMOVIDO: window.fecharModalInstrutor
// Esta função agora está EXCLUSIVAMENTE em instrutores-page.js
// Não deve haver nenhuma definição aqui para evitar conflitos

// Função para salvar instrutor
window.salvarInstrutor = async function() {
    console.log('💾 Salvando instrutor...');
    
    try {
        const form = document.getElementById('formInstrutor');
        if (!form) {
            throw new Error('Formulário não encontrado');
        }
        
        const formData = new FormData(form);
        
        // Capturar checkboxes de categorias de habilitação
        const categoriasCheckboxes = document.querySelectorAll('input[name="categorias[]"]:checked');
        const categoriasSelecionadas = Array.from(categoriasCheckboxes).map(cb => cb.value);
        console.log('🔍 Categorias selecionadas:', categoriasSelecionadas);
        
        // Capturar checkboxes de dias da semana
        const diasCheckboxes = document.querySelectorAll('input[name="dias_semana[]"]:checked');
        const diasSelecionados = Array.from(diasCheckboxes).map(cb => cb.value);
        console.log('🔍 Dias selecionados:', diasSelecionados);
        
        // Validações
        if (!formData.get('nome').trim()) {
            alert('Nome do instrutor é obrigatório');
            return;
        }
        
        if (!formData.get('credencial').trim()) {
            alert('Credencial é obrigatória');
            return;
        }
        
        if (!formData.get('cfc_id')) {
            alert('CFC é obrigatório');
            return;
        }
        
        if (categoriasSelecionadas.length === 0) {
            alert('Categoria de habilitação é obrigatória');
            return;
        }
        
        // Preparar dados usando FormData para suportar upload de arquivos
        const dadosEnvio = new FormData();
        
        // Adicionar campos básicos
        dadosEnvio.append('nome', formData.get('nome').trim());
        dadosEnvio.append('email', formData.get('email').trim());
        dadosEnvio.append('telefone', formData.get('telefone').trim());
        dadosEnvio.append('credencial', formData.get('credencial').trim());
        dadosEnvio.append('cfc_id', formData.get('cfc_id') || '');
        dadosEnvio.append('usuario_id', formData.get('usuario_id') || '');
        dadosEnvio.append('ativo', formData.get('ativo') === '1' ? '1' : '0');
        
        // Adicionar campos adicionais
        dadosEnvio.append('cpf', formData.get('cpf') || '');
        dadosEnvio.append('cnh', formData.get('cnh') || '');
        dadosEnvio.append('data_nascimento', converterDataParaMySQL(formData.get('data_nascimento') || ''));
        dadosEnvio.append('horario_inicio', formData.get('horario_inicio') || '');
        dadosEnvio.append('horario_fim', formData.get('horario_fim') || '');
        dadosEnvio.append('endereco', formData.get('endereco') || '');
        dadosEnvio.append('cidade', formData.get('cidade') || '');
        dadosEnvio.append('uf', formData.get('uf') || '');
        dadosEnvio.append('tipo_carga', formData.get('tipo_carga') || '');
        dadosEnvio.append('validade_credencial', converterDataParaMySQL(formData.get('validade_credencial') || ''));
        dadosEnvio.append('observacoes', formData.get('observacoes') || '');
        
        // Adicionar categorias e dias da semana
        categoriasSelecionadas.forEach(categoria => {
            dadosEnvio.append('categoria_habilitacao[]', categoria);
        });
        diasSelecionados.forEach(dia => {
            dadosEnvio.append('dias_semana[]', dia);
        });
        
        // Adicionar foto se houver
        const fotoInput = document.getElementById('foto');
        if (fotoInput && fotoInput.files && fotoInput.files[0]) {
            dadosEnvio.append('foto', fotoInput.files[0]);
            console.log('📷 Foto adicionada ao FormData:', fotoInput.files[0].name);
        }
        
        const acao = formData.get('acao');
        const instrutor_id = formData.get('instrutor_id');
        
        console.log('🔍 Debug - Campo acao:', acao);
        console.log('🔍 Debug - Campo instrutor_id:', instrutor_id);
        console.log('🔍 Debug - Tipo de acao:', typeof acao);
        console.log('🔍 Debug - Tipo de instrutor_id:', typeof instrutor_id);
        
        if (acao === 'editar' && instrutor_id) {
            dadosEnvio.append('id', instrutor_id);
            console.log('✅ Modo edição detectado - ID:', instrutor_id);
        } else {
            console.log('⚠️ Modo criação detectado ou ID não encontrado');
        }
        
        console.log('📋 FormData preparado para envio');
        
        // Debug adicional para verificar campos específicos
        console.log('🔍 Debug - usuario_id:', formData.get('usuario_id'));
        console.log('🔍 Debug - cfc_id:', formData.get('cfc_id'));
        console.log('🔍 Debug - nome:', formData.get('nome'));
        console.log('🔍 Debug - credencial:', formData.get('credencial'));
        console.log('🔍 Debug - acao:', formData.get('acao'));
        console.log('🔍 Debug - instrutor_id:', formData.get('instrutor_id'));
        
        // Mostrar loading no botão
        const btnSalvar = document.getElementById('btnSalvarInstrutor');
        if (btnSalvar) {
            const originalText = btnSalvar.innerHTML;
            btnSalvar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';
            btnSalvar.disabled = true;
            
            try {
                const method = acao === 'editar' ? 'PUT' : 'POST';
                const endpoint = acao === 'editar' ? `?id=${instrutor_id}` : '';
                
                const response = await fetchAPIInstrutores(endpoint, {
                    method: method,
                    body: dadosEnvio
                });
                
                // Verificar se a resposta é válida antes de tentar fazer parse
                const responseText = await response.text();
                console.log('📡 Resposta bruta da API:', responseText);
                
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('❌ Erro ao fazer parse da resposta JSON:', parseError);
                    console.error('❌ Resposta recebida:', responseText);
                    throw new Error('Resposta inválida do servidor: ' + responseText.substring(0, 100));
                }
                
                if (data.success) {
                    alert(data.message || 'Instrutor salvo com sucesso!');
                    // Fechar modal - função está em instrutores-page.js
                    // Não chamar aqui para evitar conflito
                    
                    // Recarregar página
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert(data.error || 'Erro ao salvar instrutor');
                }
            } catch (error) {
                console.error('❌ Erro ao salvar:', error);
                alert('Erro ao salvar instrutor: ' + error.message);
            } finally {
                btnSalvar.innerHTML = originalText;
                btnSalvar.disabled = false;
            }
        }
        
    } catch (error) {
        console.error('❌ Erro na função salvarInstrutor:', error);
        alert('Erro interno: ' + error.message);
    }
};

// REMOVIDO: window.editarInstrutor
// Esta função agora está EXCLUSIVAMENTE em instrutores-page.js
// Não deve haver nenhuma definição aqui para evitar conflitos

// Função para alterar status do instrutor
async function alterarStatusInstrutor(id, status) {
    const acao = status ? 'ativar' : 'desativar';
    const mensagem = `Tem certeza que deseja ${acao} este instrutor?`;
    
    if (!confirm(mensagem)) {
        return;
    }
    
    try {
        const response = await fetchAPIInstrutores(`?id=${id}`, {
            method: 'PUT',
            body: JSON.stringify({ ativo: status })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(`Instrutor ${acao}do com sucesso!`);
            
            // Recarregar página
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alert(`Erro ao ${acao} instrutor: ` + (data.error || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error(`❌ Erro ao ${acao} instrutor:`, error);
        alert(`Erro ao ${acao} instrutor: ` + error.message);
    }
}

// Função para excluir instrutor
window.excluirInstrutor = async function(id) {
    console.log('🗑️ Excluindo instrutor ID:', id);
    
    if (!confirm('⚠️ ATENÇÃO: Esta ação não pode ser desfeita!\n\nDeseja realmente excluir este instrutor?')) {
        return;
    }
    
    try {
        const response = await fetchAPIInstrutores(`?id=${id}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message || 'Instrutor excluído com sucesso!');
            
            // Recarregar página
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alert('Erro ao excluir instrutor: ' + (data.error || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error('❌ Erro ao excluir instrutor:', error);
        alert('Erro ao excluir instrutor: ' + error.message);
    }
};

// Função para ativar instrutor
window.ativarInstrutor = async function(id) {
    await alterarStatusInstrutor(id, 1);
};

// Função para desativar instrutor
window.desativarInstrutor = async function(id) {
    await alterarStatusInstrutor(id, 0);
};

// Função para alterar status do instrutor (duplicada - remover se já existe)
async function alterarStatusInstrutor(id, status) {
    const acao = status ? 'ativar' : 'desativar';
    const mensagem = `Tem certeza que deseja ${acao} este instrutor?`;
    
    if (!confirm(mensagem)) {
        return;
    }
    
    try {
        const response = await fetchAPIInstrutores(`?id=${id}`, {
            method: 'PUT',
            body: JSON.stringify({ ativo: status })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(`Instrutor ${acao}do com sucesso!`);
            
            // Recarregar página
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alert(`Erro ao ${acao} instrutor: ` + (data.error || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error(`❌ Erro ao ${acao} instrutor:`, error);
        alert(`Erro ao ${acao} instrutor: ` + error.message);
    }
}

// Função para excluir instrutor (duplicada - remover)
window.excluirInstrutor = async function(id) {
    console.log('🗑️ Excluindo instrutor ID:', id);
    
    if (!confirm('⚠️ ATENÇÃO: Esta ação não pode ser desfeita!\n\nDeseja realmente excluir este instrutor?')) {
        return;
    }
    
    try {
        const response = await fetchAPIInstrutores(`?id=${id}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message || 'Instrutor excluído com sucesso!');
            
            // Recarregar página
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alert('Erro ao excluir instrutor: ' + (data.error || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error('❌ Erro ao excluir instrutor:', error);
        alert('Erro ao excluir instrutor: ' + error.message);
    }
};

// Função para ativar instrutor
window.ativarInstrutor = async function(id) {
    await alterarStatusInstrutor(id, 1);
};

// Função para desativar instrutor
window.desativarInstrutor = async function(id) {
    await alterarStatusInstrutor(id, 0);
};

// Inicialização quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando sistema de instrutores...');
    
    // Event listeners para o modal - REMOVIDOS
    // Os event listeners agora são registrados EXCLUSIVAMENTE em instrutores-page.js
    // para evitar conflitos e loops infinitos
    
    console.log('✅ Sistema de instrutores inicializado!');
    console.log('ℹ️ Event listeners do modal agora são gerenciados por instrutores-page.js');
});

console.log('📋 Arquivo instrutores.js carregado!');

// Função para ativar/desativar instrutor
window.ativarInstrutor = async function(id) {
    await alterarStatusInstrutor(id, 1);
};

window.desativarInstrutor = async function(id) {
    await alterarStatusInstrutor(id, 0);
};

async function alterarStatusInstrutor(id, status) {
    const acao = status ? 'ativar' : 'desativar';
    const mensagem = status ? 'Deseja realmente ativar este instrutor?' : 'Deseja realmente desativar este instrutor?';
    
    if (!confirm(mensagem)) {
        return;
    }
    
    try {
        const response = await fetchAPIInstrutores(`?id=${id}`, {
            method: 'PUT',
            body: JSON.stringify({ ativo: status })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(`Instrutor ${acao}do com sucesso!`);
            
            // Recarregar página
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alert(`Erro ao ${acao} instrutor: ` + (data.error || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error(`❌ Erro ao ${acao} instrutor:`, error);
        alert(`Erro ao ${acao} instrutor: ` + error.message);
    }
}

// Inicialização quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando sistema de instrutores...');
    
    // Event listeners para o modal - REMOVIDOS
    // Os event listeners agora são registrados EXCLUSIVAMENTE em instrutores-page.js
    // para evitar conflitos e loops infinitos
    // const modal = document.getElementById('modalInstrutor');
    // if (modal) {
    //     modal.addEventListener('click', function(e) {
    //         if (e.target === modal) {
    //             fecharModalInstrutor(); // ❌ Causava loop infinito
    //         }
    //     });
    // }
    
    // Event listener para o formulário
    const form = document.getElementById('formInstrutor');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            salvarInstrutor();
        });
    }
    
    // Event listener para o botão de salvar (removido para evitar dupla execução)
    // const btnSalvar = document.getElementById('btnSalvarInstrutor');
    // if (btnSalvar) {
    //     btnSalvar.addEventListener('click', function(e) {
    //         e.preventDefault();
    //         salvarInstrutor();
    //     });
    // }
    
    // Event listener para ESC fechar modal - REMOVIDO
    // O listener de ESC agora é registrado EXCLUSIVAMENTE em instrutores-page.js
    // para evitar loops infinitos
    // document.addEventListener('keydown', function(e) {
    //     if (e.key === 'Escape') {
    //         const modal = document.getElementById('modalInstrutor');
    //         if (modal && modal.style.display === 'block') {
    //             fecharModalInstrutor(); // ❌ Causava loop infinito
    //         }
    //     }
    // });
    
    console.log('✅ Sistema de instrutores inicializado!');
    console.log('ℹ️ Event listeners do modal agora são gerenciados por instrutores-page.js');
});

console.log('📋 Arquivo instrutores.js carregado!');
