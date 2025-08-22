<?php
// Teste simples para verificar se o botão de histórico está funcionando
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Botão Histórico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/action-buttons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Teste do Botão de Histórico</h1>
        
        <div class="row">
            <div class="col-md-6">
                <h3>Teste Aluno</h3>
                <div class="action-buttons-container">
                    <div class="action-buttons-secondary">
                        <button type="button" class="btn btn-history action-btn" 
                                onclick="historicoAluno(123)" 
                                title="Visualizar histórico de aulas e progresso">
                            <i class="fas fa-history me-1"></i>Histórico Aluno
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <h3>Teste Instrutor</h3>
                <div class="action-buttons-container">
                    <div class="action-buttons-secondary">
                        <button type="button" class="btn btn-history action-btn" 
                                onclick="historicoInstrutor(456)" 
                                title="Visualizar histórico de aulas e desempenho">
                            <i class="fas fa-history me-1"></i>Histórico Instrutor
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <h3>Status do Teste</h3>
            <div id="status" class="alert alert-info">
                Clique nos botões acima para testar...
            </div>
        </div>
        
        <div class="mt-4">
            <h3>Console Log</h3>
            <div id="console" class="bg-dark text-light p-3" style="height: 200px; overflow-y: auto; font-family: monospace;">
                Console será exibido aqui...
            </div>
        </div>
    </div>

    <script>
        // Funções de teste
        function historicoAluno(id) {
            console.log('Função historicoAluno chamada com ID:', id);
            document.getElementById('status').innerHTML = `<strong>✅ Sucesso!</strong> Função historicoAluno chamada com ID: ${id}`;
            document.getElementById('status').className = 'alert alert-success';
            
            // Simular redirecionamento
            setTimeout(() => {
                document.getElementById('status').innerHTML = `<strong>🔄 Redirecionando...</strong> Para: ?page=historico-aluno&id=${id}`;
                document.getElementById('status').className = 'alert alert-warning';
            }, 1000);
        }
        
        function historicoInstrutor(id) {
            console.log('Função historicoInstrutor chamada com ID:', id);
            document.getElementById('status').innerHTML = `<strong>✅ Sucesso!</strong> Função historicoInstrutor chamada com ID: ${id}`;
            document.getElementById('status').className = 'alert alert-success';
            
            // Simular redirecionamento
            setTimeout(() => {
                document.getElementById('status').innerHTML = `<strong>🔄 Redirecionando...</strong> Para: ?page=historico-instrutor&id=${id}`;
                document.getElementById('status').className = 'alert alert-warning';
            }, 1000);
        }
        
        // Capturar console.log
        const originalLog = console.log;
        console.log = function(...args) {
            originalLog.apply(console, args);
            const consoleDiv = document.getElementById('console');
            const timestamp = new Date().toLocaleTimeString();
            consoleDiv.innerHTML += `<div>[${timestamp}] ${args.join(' ')}</div>`;
            consoleDiv.scrollTop = consoleDiv.scrollHeight;
        };
        
        // Teste automático
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Página de teste carregada');
            console.log('Funções disponíveis: historicoAluno(id), historicoInstrutor(id)');
        });
    </script>
</body>
</html>
