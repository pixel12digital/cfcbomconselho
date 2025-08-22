<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Botão Editar Aluno</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/action-buttons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .debug-log {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-top: 1rem;
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            max-height: 400px;
            overflow-y: auto;
        }
        .debug-error { color: #dc3545; }
        .debug-success { color: #198754; }
        .debug-info { color: #0d6efd; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1>🔧 Debug - Botão Editar Aluno</h1>
        
        <div class="alert alert-warning">
            <h5><i class="fas fa-exclamation-triangle me-2"></i>Problema Reportado:</h5>
            <p class="mb-0">Clico no botão "Editar" do "Aluno Duplicado" (ID: 102) e não acontece nada.</p>
        </div>
        
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Teste da Função editarAluno</h5>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="testeId" class="form-label">ID do Aluno para Teste:</label>
                        <input type="number" class="form-control" id="testeId" value="102" min="1">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="button" class="btn btn-primary" onclick="testarEditarAluno()">
                            <i class="fas fa-bug me-1"></i>Testar Função
                        </button>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-12">
                        <h6>Teste Direto dos Botões:</h6>
                        <div class="action-buttons-container">
                            <div class="action-buttons-primary">
                                <button type="button" class="btn btn-edit action-btn" 
                                        onclick="editarAluno(102)" 
                                        title="Editar Aluno ID 102">
                                    <i class="fas fa-edit me-1"></i>Editar ID 102
                                </button>
                                <button type="button" class="btn btn-edit action-btn" 
                                        onclick="editarAluno(100)" 
                                        title="Editar Aluno ID 100">
                                    <i class="fas fa-edit me-1"></i>Editar ID 100
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="debug-log" id="debugLog"></div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-body">
                <h5 class="card-title">Verificações Automáticas</h5>
                <div id="verificacoes"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Configurar captura de logs
        const debugLog = document.getElementById('debugLog');
        
        function addLog(message, type = 'info') {
            const timestamp = new Date().toLocaleTimeString();
            const logEntry = document.createElement('div');
            logEntry.className = `debug-${type}`;
            logEntry.innerHTML = `[${timestamp}] ${message}`;
            debugLog.appendChild(logEntry);
            debugLog.scrollTop = debugLog.scrollHeight;
        }
        
        // Interceptar console.log, console.error, etc.
        const originalConsoleLog = console.log;
        const originalConsoleError = console.error;
        
        console.log = function(...args) {
            addLog('LOG: ' + args.join(' '), 'info');
            originalConsoleLog.apply(console, args);
        };
        
        console.error = function(...args) {
            addLog('ERROR: ' + args.join(' '), 'error');
            originalConsoleError.apply(console, args);
        };
        
        // Definir função editarAluno com debug
        function editarAluno(id) {
            addLog(`🔍 Iniciando editarAluno(${id})`, 'info');
            
            // Verificar se o ID é válido
            if (!id || isNaN(id)) {
                addLog(`❌ ID inválido: ${id}`, 'error');
                return;
            }
            
            addLog(`📡 Fazendo requisição para: api/alunos.php?id=${id}`, 'info');
            
            fetch(`api/alunos.php?id=${id}`)
                .then(response => {
                    addLog(`📨 Resposta recebida - Status: ${response.status}`, response.ok ? 'success' : 'error');
                    return response.json();
                })
                .then(data => {
                    addLog(`📄 Dados recebidos: ${JSON.stringify(data)}`, 'info');
                    
                    if (data.success) {
                        addLog(`✅ Dados do aluno carregados com sucesso`, 'success');
                        
                        // Verificar se o modal existe
                        const modal = document.getElementById('modalAluno');
                        if (modal) {
                            addLog(`✅ Modal encontrado`, 'success');
                            // Simular preenchimento do formulário
                            addLog(`📝 Simulando preenchimento do formulário...`, 'info');
                            
                            // Simular abertura do modal
                            addLog(`🪟 Simulando abertura do modal...`, 'success');
                            alert(`✅ Sucesso! Modal seria aberto para editar:\nNome: ${data.aluno.nome}\nCPF: ${data.aluno.cpf}`);
                        } else {
                            addLog(`❌ Modal 'modalAluno' não encontrado no DOM`, 'error');
                        }
                    } else {
                        addLog(`❌ Erro nos dados: ${data.error || 'Erro desconhecido'}`, 'error');
                    }
                })
                .catch(error => {
                    addLog(`💥 Erro na requisição: ${error.message}`, 'error');
                    console.error('Erro:', error);
                });
        }
        
        function testarEditarAluno() {
            const id = document.getElementById('testeId').value;
            addLog(`\n🚀 === TESTE MANUAL INICIADO ===`, 'info');
            editarAluno(parseInt(id));
        }
        
        // Verificações automáticas
        function verificarAmbiente() {
            const verificacoes = document.getElementById('verificacoes');
            let html = '<h6>Resultados das Verificações:</h6><ul class="list-group">';
            
            // Verificar se jQuery está carregado
            const jqueryOk = typeof $ !== 'undefined';
            html += `<li class="list-group-item ${jqueryOk ? 'list-group-item-success' : 'list-group-item-danger'}">
                ${jqueryOk ? '✅' : '❌'} jQuery: ${jqueryOk ? 'Carregado' : 'Não carregado'}
            </li>`;
            
            // Verificar se Bootstrap está carregado
            const bootstrapOk = typeof bootstrap !== 'undefined';
            html += `<li class="list-group-item ${bootstrapOk ? 'list-group-item-success' : 'list-group-item-danger'}">
                ${bootstrapOk ? '✅' : '❌'} Bootstrap: ${bootstrapOk ? 'Carregado' : 'Não carregado'}
            </li>`;
            
            // Verificar se a função editarAluno está definida
            const funcaoOk = typeof editarAluno === 'function';
            html += `<li class="list-group-item ${funcaoOk ? 'list-group-item-success' : 'list-group-item-danger'}">
                ${funcaoOk ? '✅' : '❌'} Função editarAluno: ${funcaoOk ? 'Definida' : 'Não definida'}
            </li>`;
            
            // Verificar se o modal existe (simulado)
            html += `<li class="list-group-item list-group-item-warning">
                ⚠️ Modal 'modalAluno': Não verificável nesta página de debug
            </li>`;
            
            html += '</ul>';
            verificacoes.innerHTML = html;
        }
        
        // Executar verificações ao carregar
        document.addEventListener('DOMContentLoaded', function() {
            addLog('🔧 Debug iniciado', 'info');
            addLog('📋 Página de debug carregada', 'info');
            verificarAmbiente();
        });
        
        // Interceptar erros globais
        window.addEventListener('error', function(e) {
            addLog(`💥 Erro JavaScript global: ${e.error.message} (${e.filename}:${e.lineno})`, 'error');
        });
    </script>
</body>
</html>
