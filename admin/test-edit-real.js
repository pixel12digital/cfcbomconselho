// Teste específico da função editarAluno
console.log('🔧 Iniciando teste da função editarAluno...');

// Função editarAluno com logs detalhados
function editarAlunoDebug(id) {
    console.log(`🚀 editarAluno(${id}) chamada`);
    
    // Verificar se os elementos necessários existem
    const modalElement = document.getElementById('modalAluno');
    const modalTitle = document.getElementById('modalTitle');
    const acaoAluno = document.getElementById('acaoAluno');
    const alunoId = document.getElementById('aluno_id');
    
    console.log('🔍 Verificando elementos do DOM:');
    console.log('  modalAluno:', modalElement ? '✅ Existe' : '❌ Não existe');
    console.log('  modalTitle:', modalTitle ? '✅ Existe' : '❌ Não existe');
    console.log('  acaoAluno:', acaoAluno ? '✅ Existe' : '❌ Não existe');
    console.log('  aluno_id:', alunoId ? '✅ Existe' : '❌ Não existe');
    
    if (!modalElement) {
        console.error('❌ Modal não encontrado! Abortando...');
        return;
    }
    
    console.log(`📡 Fazendo fetch para: api/alunos.php?id=${id}`);
    
    // Fazer a requisição
    fetch(`api/alunos.php?id=${id}`)
        .then(response => {
            console.log(`📨 Resposta recebida:`, response);
            console.log(`   Status: ${response.status}`);
            console.log(`   OK: ${response.ok}`);
            console.log(`   Headers:`, response.headers);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return response.json();
        })
        .then(data => {
            console.log('📄 Dados recebidos:', data);
            
            if (data.success) {
                console.log('✅ Success = true, processando dados...');
                console.log('👤 Dados do aluno:', data.aluno);
                
                // Tentar preencher o formulário
                try {
                    console.log('📝 Preenchendo formulário...');
                    preencherFormularioAluno(data.aluno);
                    console.log('✅ Formulário preenchido');
                } catch (e) {
                    console.error('❌ Erro ao preencher formulário:', e);
                }
                
                // Configurar modal
                if (modalTitle) {
                    modalTitle.textContent = 'Editar Aluno';
                    console.log('✅ Título do modal alterado');
                }
                
                if (acaoAluno) {
                    acaoAluno.value = 'editar';
                    console.log('✅ Ação definida como "editar"');
                }
                
                if (alunoId) {
                    alunoId.value = id;
                    console.log(`✅ ID do aluno definido como ${id}`);
                }
                
                // Tentar abrir o modal
                try {
                    console.log('🪟 Tentando abrir modal...');
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show();
                    console.log('✅ Modal aberto com sucesso!');
                } catch (e) {
                    console.error('❌ Erro ao abrir modal:', e);
                }
                
            } else {
                console.error('❌ Success = false');
                console.error('   Error:', data.error);
                
                if (typeof mostrarAlerta === 'function') {
                    mostrarAlerta('Erro ao carregar dados do aluno: ' + (data.error || 'Erro desconhecido'), 'danger');
                } else {
                    alert('Erro ao carregar dados do aluno: ' + (data.error || 'Erro desconhecido'));
                }
            }
        })
        .catch(error => {
            console.error('💥 Erro na requisição:', error);
            console.error('   Message:', error.message);
            console.error('   Stack:', error.stack);
            
            if (typeof mostrarAlerta === 'function') {
                mostrarAlerta('Erro ao carregar dados do aluno: ' + error.message, 'danger');
            } else {
                alert('Erro ao carregar dados do aluno: ' + error.message);
            }
        });
}

// Sobrescrever a função original se existir
if (typeof editarAluno === 'function') {
    console.log('🔄 Substituindo função editarAluno original');
    window.editarAlunoOriginal = editarAluno;
}

window.editarAluno = editarAlunoDebug;
console.log('✅ Função editarAluno com debug instalada');

// Teste automático
console.log('🧪 Executando teste automático em 2 segundos...');
setTimeout(() => {
    console.log('🚀 Teste automático: editarAluno(102)');
    editarAlunoDebug(102);
}, 2000);
