<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Redirecionamento - Histórico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">🧪 Teste de Redirecionamento - Páginas de Histórico</h1>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Teste Histórico de Aluno</h5>
                    </div>
                    <div class="card-body">
                        <p>Testando redirecionamento para histórico de aluno ID 102:</p>
                        <button class="btn btn-primary" onclick="testarHistoricoAluno()">
                            📊 Abrir Histórico Aluno ID 102
                        </button>
                        <hr>
                        <p><strong>URL esperada:</strong> <code>?page=historico-aluno&id=102</code></p>
                        <p><strong>Status:</strong> <span id="status-aluno" class="badge bg-secondary">Não testado</span></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Teste Histórico de Instrutor</h5>
                    </div>
                    <div class="card-body">
                        <p>Testando redirecionamento para histórico de instrutor ID 1:</p>
                        <button class="btn btn-success" onclick="testarHistoricoInstrutor()">
                            📊 Abrir Histórico Instrutor ID 1
                        </button>
                        <hr>
                        <p><strong>URL esperada:</strong> <code>?page=historico-instrutor&id=1</code></p>
                        <p><strong>Status:</strong> <span id="status-instrutor" class="badge bg-secondary">Não testado</span></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>🔍 Verificação de Arquivos</h5>
                    </div>
                    <div class="card-body">
                        <div id="verificacao-arquivos">
                            <p>Verificando arquivos necessários...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>📋 Log de Testes</h5>
                    </div>
                    <div class="card-body">
                        <div id="log-testes" class="border p-3 bg-light" style="max-height: 300px; overflow-y: auto;">
                            <p class="text-muted">Log de testes aparecerá aqui...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Função para adicionar log
        function adicionarLog(mensagem, tipo = 'info') {
            const logDiv = document.getElementById('log-testes');
            const timestamp = new Date().toLocaleTimeString();
            const logEntry = document.createElement('div');
            logEntry.className = `mb-2 p-2 border-start border-${tipo === 'success' ? 'success' : tipo === 'error' ? 'danger' : 'info'} border-3`;
            logEntry.innerHTML = `<strong>[${timestamp}]</strong> ${mensagem}`;
            logDiv.appendChild(logEntry);
            logDiv.scrollTop = logDiv.scrollHeight;
        }

        // Função para testar histórico de aluno
        function testarHistoricoAluno() {
            adicionarLog('🧪 Testando redirecionamento para histórico de aluno...', 'info');
            
            // Simular o redirecionamento
            const url = `?page=historico-aluno&id=102`;
            adicionarLog(`📍 URL gerada: ${url}`, 'info');
            
            // Verificar se a página existe
            fetch(url)
                .then(response => {
                    if (response.ok) {
                        document.getElementById('status-aluno').className = 'badge bg-success';
                        document.getElementById('status-aluno').textContent = '✅ Funcionando';
                        adicionarLog('✅ Redirecionamento para histórico de aluno funcionando!', 'success');
                    } else {
                        document.getElementById('status-aluno').className = 'badge bg-danger';
                        document.getElementById('status-aluno').textContent = '❌ Erro ' + response.status;
                        adicionarLog(`❌ Erro ${response.status}: ${response.statusText}`, 'error');
                    }
                })
                .catch(error => {
                    document.getElementById('status-aluno').className = 'badge bg-danger';
                    document.getElementById('status-aluno').textContent = '❌ Erro';
                    adicionarLog(`❌ Erro na requisição: ${error.message}`, 'error');
                });
        }

        // Função para testar histórico de instrutor
        function testarHistoricoInstrutor() {
            adicionarLog('🧪 Testando redirecionamento para histórico de instrutor...', 'info');
            
            // Simular o redirecionamento
            const url = `?page=historico-instrutor&id=1`;
            adicionarLog(`📍 URL gerada: ${url}`, 'info');
            
            // Verificar se a página existe
            fetch(url)
                .then(response => {
                    if (response.ok) {
                        document.getElementById('status-instrutor').className = 'badge bg-success';
                        document.getElementById('status-instrutor').textContent = '✅ Funcionando';
                        adicionarLog('✅ Redirecionamento para histórico de instrutor funcionando!', 'success');
                    } else {
                        document.getElementById('status-instrutor').className = 'badge bg-danger';
                        document.getElementById('status-instrutor').textContent = '❌ Erro ' + response.status;
                        adicionarLog(`❌ Erro ${response.status}: ${response.statusText}`, 'error');
                    }
                })
                .catch(error => {
                    document.getElementById('status-instrutor').className = 'badge bg-danger';
                    document.getElementById('status-instrutor').textContent = '❌ Erro';
                    adicionarLog(`❌ Erro na requisição: ${error.message}`, 'error');
                });
        }

        // Verificar arquivos na inicialização
        window.addEventListener('DOMContentLoaded', function() {
            adicionarLog('🚀 Iniciando verificação de arquivos...', 'info');
            
            // Verificar se os arquivos de histórico existem
            const arquivos = [
                'pages/historico-aluno.php',
                'pages/historico-instrutor.php',
                'api/historico.php'
            ];
            
            let arquivosExistentes = 0;
            
            arquivos.forEach(arquivo => {
                fetch(arquivo)
                    .then(response => {
                        if (response.ok) {
                            arquivosExistentes++;
                            adicionarLog(`✅ ${arquivo} - OK`, 'success');
                        } else {
                            adicionarLog(`❌ ${arquivo} - Não encontrado (${response.status})`, 'error');
                        }
                        
                        // Atualizar contador
                        if (arquivosExistentes === arquivos.length) {
                            adicionarLog(`📊 Verificação concluída: ${arquivosExistentes}/${arquivos.length} arquivos encontrados`, 'info');
                        }
                    })
                    .catch(error => {
                        adicionarLog(`❌ ${arquivo} - Erro: ${error.message}`, 'error');
                    });
            });
            
            // Verificar sistema de roteamento
            adicionarLog('🔍 Verificando sistema de roteamento do admin...', 'info');
            fetch('index.php?page=dashboard')
                .then(response => {
                    if (response.ok) {
                        adicionarLog('✅ Sistema de roteamento funcionando', 'success');
                    } else {
                        adicionarLog(`❌ Sistema de roteamento com erro: ${response.status}`, 'error');
                    }
                })
                .catch(error => {
                    adicionarLog(`❌ Erro no sistema de roteamento: ${error.message}`, 'error');
                });
        });
    </script>
</body>
</html>
