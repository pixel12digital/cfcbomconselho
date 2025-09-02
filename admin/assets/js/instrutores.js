/**
 * JavaScript para gerenciamento de Instrutores
 * Sistema CFC - Bom Conselho
 */

// Cache para o caminho da API
let caminhoAPIInstrutoresCache = null;

// Função para converter data de DD/MM/YYYY para YYYY-MM-DD (MySQL)
function converterDataParaMySQL(dataString) {
    if (!dataString || dataString.trim() === '') {
        return null; // Retorna null para campos vazios
    }
    
    // Verificar se já está no formato YYYY-MM-DD
    if (/^\d{4}-\d{2}-\d{2}$/.test(dataString)) {
        return dataString;
    }
    
    // Converter de DD/MM/YYYY para YYYY-MM-DD
    const match = dataString.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
    if (match) {
        const [, dia, mes, ano] = match;
        const dataMySQL = `${ano}-${mes.padStart(2, '0')}-${dia.padStart(2, '0')}`;
        console.log(`✅ Data convertida: ${dataString} → ${dataMySQL}`);
        return dataMySQL;
    }
    
    console.warn(`⚠️ Formato de data inválido: ${dataString}`);
    return null;
}

// Função para converter data de YYYY-MM-DD para DD/MM/YYYY
function converterDataParaExibicao(dataString) {
    if (!dataString || dataString === '0000-00-00' || dataString.trim() === '') {
        return '';
    }
    
    try {
        const data = new Date(dataString);
        if (!isNaN(data.getTime())) {
            const dia = String(data.getDate()).padStart(2, '0');
            const mes = String(data.getMonth() + 1).padStart(2, '0');
            const ano = data.getFullYear();
            const dataFormatada = `${dia}/${mes}/${ano}`;
            console.log(`✅ Data convertida para exibição: ${dataString} → ${dataFormatada}`);
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
    
    const defaultOptions = {
        headers: {
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
            throw new Error(`HTTP ${response.status}: ${response.statusText} - ${errorText}`);
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

// Função para abrir modal de instrutor
window.abrirModalInstrutor = function() {
    console.log('🚀 Abrindo modal de instrutor...');
    
    const modal = document.getElementById('modalInstrutor');
    if (!modal) {
        console.error('❌ Modal não encontrado!');
        alert('Erro: Modal não encontrado na página!');
        return;
    }
    
    // Limpar formulário
    const form = document.getElementById('formInstrutor');
    if (form) {
        form.reset();
        document.getElementById('acaoInstrutor').value = 'novo';
        document.getElementById('instrutor_id').value = '';
        document.getElementById('modalTitle').textContent = 'Novo Instrutor';
    }
    
    // Mostrar modal
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    console.log('✅ Modal aberto com sucesso!');
};

// Função para fechar modal de instrutor
window.fecharModalInstrutor = function() {
    console.log('🚪 Fechando modal de instrutor...');
    
    const modal = document.getElementById('modalInstrutor');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        console.log('✅ Modal fechado!');
    }
};

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
        
        if (categoriasSelecionadas.length === 0) {
            alert('Categoria de habilitação é obrigatória');
            return;
        }
        
        // Preparar dados
        const instrutorData = {
            nome: formData.get('nome').trim(),
            email: formData.get('email').trim(),
            telefone: formData.get('telefone').trim(),
            credencial: formData.get('credencial').trim(),
            categoria_habilitacao: categoriasSelecionadas,
            dias_semana: diasSelecionados,
            cfc_id: formData.get('cfc_id') || null,
            usuario_id: formData.get('usuario_id') || null,
            ativo: formData.get('ativo') === '1',
            // Campos adicionais
            cpf: formData.get('cpf') || '',
            cnh: formData.get('cnh') || '',
            data_nascimento: converterDataParaMySQL(formData.get('data_nascimento') || ''),
            horario_inicio: formData.get('horario_inicio') || '',
            horario_fim: formData.get('horario_fim') || '',
            endereco: formData.get('endereco') || '',
            cidade: formData.get('cidade') || '',
            uf: formData.get('uf') || '',
            tipo_carga: formData.get('tipo_carga') || '',
            validade_credencial: converterDataParaMySQL(formData.get('validade_credencial') || ''),
            observacoes: formData.get('observacoes') || ''
        };
        
        const acao = formData.get('acao');
        const instrutor_id = formData.get('instrutor_id');
        
        console.log('🔍 Debug - Campo acao:', acao);
        console.log('🔍 Debug - Campo instrutor_id:', instrutor_id);
        console.log('🔍 Debug - Tipo de acao:', typeof acao);
        console.log('🔍 Debug - Tipo de instrutor_id:', typeof instrutor_id);
        
        if (acao === 'editar' && instrutor_id) {
            instrutorData.id = instrutor_id;
            console.log('✅ Modo edição detectado - ID:', instrutor_id);
        } else {
            console.log('⚠️ Modo criação detectado ou ID não encontrado');
        }
        
        console.log('📋 Dados do instrutor para salvar:', instrutorData);
        
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
                    body: JSON.stringify(instrutorData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message || 'Instrutor salvo com sucesso!');
                    fecharModalInstrutor();
                    
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

// Função para editar instrutor
window.editarInstrutor = async function(id) {
    console.log('✏️ Editando instrutor ID:', id);
    
    try {
        const response = await fetchAPIInstrutores(`?id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const instrutor = data.data;
            
            // Abrir modal primeiro
            abrirModalInstrutor();
            
            // Configurar modal para edição APÓS abrir o modal
            document.getElementById('modalTitle').textContent = 'Editar Instrutor';
            document.getElementById('acaoInstrutor').value = 'editar';
            document.getElementById('instrutor_id').value = id;
            
            console.log('✅ Modal configurado para edição - ID:', id);
            console.log('✅ Campo acao definido como:', document.getElementById('acaoInstrutor').value);
            console.log('✅ Campo instrutor_id definido como:', document.getElementById('instrutor_id').value);
            
            // Aguardar o modal estar visível antes de preencher
            setTimeout(async () => {
                try {
                    // Primeiro, carregar os selects se necessário
                    if (typeof carregarUsuariosComRetry === 'function') {
                        console.log('🔄 Carregando usuários...');
                        await carregarUsuariosComRetry();
                    }
                    
                    if (typeof carregarCFCsComRetry === 'function') {
                        console.log('🔄 Carregando CFCs...');
                        await carregarCFCsComRetry();
                    }
                    
                                         // Preencher formulário
                     const nomeField = document.getElementById('nome');
                     const emailField = document.getElementById('email');
                     const telefoneField = document.getElementById('telefone');
                     const credencialField = document.getElementById('credencial');
                     const cfcField = document.getElementById('cfc_id');
                     const ativoField = document.getElementById('ativo');
                     const usuarioField = document.getElementById('usuario_id');
                     
                     // Campos adicionais que estavam faltando
                     const cpfField = document.getElementById('cpf');
                     const cnhField = document.getElementById('cnh');
                     const dataNascimentoField = document.getElementById('data_nascimento');
                     const horarioInicioField = document.getElementById('horario_inicio');
                     const horarioFimField = document.getElementById('horario_fim');
                     const enderecoField = document.getElementById('endereco');
                     const cidadeField = document.getElementById('cidade');
                     const ufField = document.getElementById('uf');
                     const tipoCargaField = document.getElementById('tipo_carga');
                     const validadeCredencialField = document.getElementById('validade_credencial');
                     const observacoesField = document.getElementById('observacoes');
                     
                     if (nomeField) nomeField.value = instrutor.nome || '';
                     if (emailField) emailField.value = instrutor.email || '';
                     if (telefoneField) telefoneField.value = instrutor.telefone || '';
                     if (credencialField) credencialField.value = instrutor.credencial || '';
                     if (cfcField) cfcField.value = instrutor.cfc_id || '';
                     if (ativoField) ativoField.value = instrutor.ativo ? '1' : '0';
                     
                     // Preencher campos adicionais
                     if (cpfField) cpfField.value = instrutor.cpf || '';
                     if (cnhField) cnhField.value = instrutor.cnh || '';
                                           if (dataNascimentoField) {
                          const dataFormatada = converterDataParaExibicao(instrutor.data_nascimento);
                          dataNascimentoField.value = dataFormatada;
                          if (dataFormatada) {
                              console.log(`✅ Data de nascimento preenchida: ${dataFormatada}`);
                          } else {
                              console.warn('⚠️ Data de nascimento vazia ou inválida:', instrutor.data_nascimento);
                          }
                      }
                     
                     // Preencher horários (converter de HH:MM:SS para HH:MM)
                     if (horarioInicioField && instrutor.horario_inicio) {
                         horarioInicioField.value = instrutor.horario_inicio.substring(0, 5);
                     }
                     if (horarioFimField && instrutor.horario_fim) {
                         horarioFimField.value = instrutor.horario_fim.substring(0, 5);
                     }
                     
                     // Preencher campos de endereço
                     if (enderecoField) enderecoField.value = instrutor.endereco || '';
                     if (cidadeField) cidadeField.value = instrutor.cidade || '';
                     if (ufField) ufField.value = instrutor.uf || '';
                     
                     // Preencher campos específicos do instrutor
                     if (tipoCargaField) tipoCargaField.value = instrutor.tipo_carga || '';
                                           if (validadeCredencialField) {
                          const dataFormatada = converterDataParaExibicao(instrutor.validade_credencial);
                          validadeCredencialField.value = dataFormatada;
                          if (dataFormatada) {
                              console.log(`✅ Validade da credencial preenchida: ${dataFormatada}`);
                          } else {
                              console.warn('⚠️ Validade da credencial vazia ou inválida:', instrutor.validade_credencial);
                          }
                      }
                     if (observacoesField) observacoesField.value = instrutor.observacoes || '';
                     
                                           // Preencher categorias de habilitação (checkboxes)
                      if (instrutor.categorias_json) {
                          let categorias = [];
                          try {
                              // Tentar parsear como JSON primeiro
                              if (typeof instrutor.categorias_json === 'string') {
                                  if (instrutor.categorias_json.trim() === '') {
                                      categorias = [];
                                  } else {
                                      try {
                                          categorias = JSON.parse(instrutor.categorias_json);
                                      } catch (e) {
                                          console.warn('⚠️ Erro ao parsear categorias_json:', e);
                                          categorias = [];
                                      }
                                  }
                              } else if (Array.isArray(instrutor.categorias_json)) {
                                  categorias = instrutor.categorias_json;
                              } else {
                                  categorias = [];
                              }
                          } catch (e) {
                              console.warn('⚠️ Erro ao processar categorias_json:', e);
                              categorias = [];
                          }
                          
                          console.log('🔍 Categorias processadas (categorias_json):', categorias);
                          
                          if (Array.isArray(categorias) && categorias.length > 0) {
                              categorias.forEach(categoria => {
                                  const checkbox = document.querySelector(`input[name="categorias[]"][value="${categoria}"]`);
                                  if (checkbox) {
                                      checkbox.checked = true;
                                      console.log(`✅ Categoria marcada: ${categoria}`);
                                  } else {
                                      console.warn(`⚠️ Checkbox para categoria "${categoria}" não encontrado`);
                                  }
                              });
                          }
                      } else if (instrutor.categoria_habilitacao) {
                          // Fallback para o campo antigo
                          let categorias = [];
                          try {
                              if (typeof instrutor.categoria_habilitacao === 'string') {
                                  if (instrutor.categoria_habilitacao.trim() === '') {
                                      categorias = [];
                                  } else {
                                      try {
                                          categorias = JSON.parse(instrutor.categoria_habilitacao);
                                      } catch (e) {
                                          categorias = instrutor.categoria_habilitacao.split(',').map(cat => cat.trim()).filter(cat => cat !== '');
                                      }
                                  }
                              } else if (Array.isArray(instrutor.categoria_habilitacao)) {
                                  categorias = instrutor.categoria_habilitacao;
                              } else {
                                  categorias = [];
                              }
                          } catch (e) {
                              console.warn('⚠️ Erro ao processar categoria_habilitacao:', e);
                              categorias = [];
                          }
                          
                          console.log('🔍 Categorias processadas (categoria_habilitacao):', categorias);
                          
                          if (Array.isArray(categorias) && categorias.length > 0) {
                              categorias.forEach(categoria => {
                                  const checkbox = document.querySelector(`input[name="categorias[]"][value="${categoria}"]`);
                                  if (checkbox) {
                                      checkbox.checked = true;
                                      console.log(`✅ Categoria marcada: ${categoria}`);
                                  } else {
                                      console.warn(`⚠️ Checkbox para categoria "${categoria}" não encontrado`);
                                  }
                              });
                          }
                      }
                     
                     // Preencher dias da semana (checkboxes)
                     if (instrutor.dias_semana) {
                         let dias = [];
                         try {
                             // Tentar parsear como JSON primeiro
                             if (typeof instrutor.dias_semana === 'string') {
                                 if (instrutor.dias_semana.trim() === '') {
                                     dias = [];
                                 } else {
                                     try {
                                         dias = JSON.parse(instrutor.dias_semana);
                                     } catch (e) {
                                         // Se não for JSON, tentar split por vírgula
                                         dias = instrutor.dias_semana.split(',').map(dia => dia.trim()).filter(dia => dia !== '');
                                     }
                                 }
                             } else if (Array.isArray(instrutor.dias_semana)) {
                                 dias = instrutor.dias_semana;
                             } else {
                                 dias = [];
                             }
                         } catch (e) {
                             console.warn('⚠️ Erro ao processar dias_semana:', e);
                             dias = [];
                         }
                         
                         console.log('🔍 Dias da semana processados:', dias);
                         
                         if (Array.isArray(dias) && dias.length > 0) {
                             dias.forEach(dia => {
                                 const checkbox = document.querySelector(`input[name="dias_semana[]"][value="${dia}"]`);
                                 if (checkbox) {
                                     checkbox.checked = true;
                                     console.log(`✅ Dia marcado: ${dia}`);
                                 } else {
                                     console.warn(`⚠️ Checkbox para dia "${dia}" não encontrado`);
                                 }
                             });
                         }
                     }
                    
                                         // Preencher usuário com verificação adicional
                     if (usuarioField && instrutor.usuario_id) {
                         const usuarioId = parseInt(instrutor.usuario_id);
                         console.log(`🔍 Tentando preencher usuário ID: ${usuarioId}`);
                         console.log(`🔍 Campo usuário encontrado:`, usuarioField);
                         console.log(`🔍 Opções disponíveis:`, Array.from(usuarioField.options).map(opt => ({value: opt.value, text: opt.textContent})));
                         
                         // Aguardar um pouco mais para garantir que as opções foram carregadas
                         setTimeout(() => {
                             const usuarioOption = usuarioField.querySelector(`option[value="${usuarioId}"]`);
                             if (usuarioOption) {
                                 usuarioField.value = usuarioId;
                                 console.log(`✅ Usuário preenchido: ${usuarioOption.textContent}`);
                             } else {
                                 console.warn(`⚠️ Opção de usuário ${usuarioId} não encontrada`);
                                 console.log('🔍 Opções disponíveis:', Array.from(usuarioField.options).map(opt => ({value: opt.value, text: opt.textContent})));
                                 
                                 // Tentar encontrar por texto também
                                 const options = Array.from(usuarioField.options);
                                 const matchingOption = options.find(opt => opt.textContent.includes(instrutor.nome));
                                 if (matchingOption) {
                                     usuarioField.value = matchingOption.value;
                                     console.log(`✅ Usuário preenchido por nome: ${matchingOption.textContent}`);
                                 }
                             }
                         }, 300); // Aumentei o tempo para 300ms
                     } else {
                         console.warn('⚠️ Campo usuário não encontrado ou usuario_id não definido');
                         console.log('🔍 usuarioField:', usuarioField);
                         console.log('🔍 instrutor.usuario_id:', instrutor.usuario_id);
                     }
                    
                    console.log('✅ Formulário preenchido com sucesso!');
                } catch (error) {
                    console.error('❌ Erro ao preencher formulário:', error);
                }
            }, 100);
        } else {
            alert('Erro ao carregar dados do instrutor: ' + (data.error || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error('❌ Erro ao editar instrutor:', error);
        alert('Erro ao carregar dados do instrutor: ' + error.message);
    }
};

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
    
    // Event listeners para o modal
    const modal = document.getElementById('modalInstrutor');
    if (modal) {
        // Fechar modal ao clicar fora
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                fecharModalInstrutor();
            }
        });
    }
    
    // Event listener para o formulário
    const form = document.getElementById('formInstrutor');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            salvarInstrutor();
        });
    }
    
    // Event listener para o botão de salvar
    const btnSalvar = document.getElementById('btnSalvarInstrutor');
    if (btnSalvar) {
        btnSalvar.addEventListener('click', function(e) {
            e.preventDefault();
            salvarInstrutor();
        });
    }
    
    // Event listener para ESC fechar modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('modalInstrutor');
            if (modal && modal.style.display === 'block') {
                fecharModalInstrutor();
            }
        }
    });
    
    console.log('✅ Sistema de instrutores inicializado!');
});

console.log('📋 Arquivo instrutores.js carregado!');
