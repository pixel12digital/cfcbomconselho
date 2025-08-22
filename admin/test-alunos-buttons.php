<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste dos Botões de Alunos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/action-buttons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Teste dos Botões de Alunos</h1>
        
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Teste das Funções JavaScript</h5>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <button type="button" class="btn btn-edit action-btn" onclick="testarEditar()">
                            <i class="fas fa-edit me-1"></i>Testar Editar
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button type="button" class="btn btn-view action-btn" onclick="testarVisualizar()">
                            <i class="fas fa-eye me-1"></i>Testar Visualizar
                        </button>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <button type="button" class="btn btn-schedule action-btn" onclick="testarAgendar()">
                            <i class="fas fa-calendar-plus me-1"></i>Testar Agendar
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button type="button" class="btn btn-history action-btn" onclick="testarHistorico()">
                            <i class="fas fa-history me-1"></i>Testar Histórico
                        </button>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <button type="button" class="btn btn-toggle action-btn" onclick="testarDesativar()">
                            <i class="fas fa-ban me-1"></i>Testar Desativar
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button type="button" class="btn btn-delete action-btn" onclick="testarExcluir()">
                            <i class="fas fa-trash me-1"></i>Testar Excluir
                        </button>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <strong>Status:</strong> <span id="status">Aguardando teste...</span>
                </div>
                
                <div class="alert alert-warning">
                    <strong>Console:</strong> Abra o console do navegador (F12) para ver mensagens detalhadas
                </div>
            </div>
        </div>
    </div>

    <script>
        // Funções de teste
        function testarEditar() {
            console.log('Testando função editarAluno...');
            document.getElementById('status').textContent = 'Testando editar...';
            
            if (typeof editarAluno === 'function') {
                console.log('✅ Função editarAluno está definida');
                document.getElementById('status').textContent = '✅ Função editarAluno está funcionando';
            } else {
                console.error('❌ Função editarAluno não está definida');
                document.getElementById('status').textContent = '❌ Função editarAluno não está definida';
            }
        }
        
        function testarVisualizar() {
            console.log('Testando função visualizarAluno...');
            document.getElementById('status').textContent = 'Testando visualizar...';
            
            if (typeof visualizarAluno === 'function') {
                console.log('✅ Função visualizarAluno está definida');
                document.getElementById('status').textContent = '✅ Função visualizarAluno está funcionando';
            } else {
                console.error('❌ Função visualizarAluno não está definida');
                document.getElementById('status').textContent = '❌ Função visualizarAluno não está definida';
            }
        }
        
        function testarAgendar() {
            console.log('Testando função agendarAula...');
            document.getElementById('status').textContent = 'Testando agendar...';
            
            if (typeof agendarAula === 'function') {
                console.log('✅ Função agendarAula está definida');
                document.getElementById('status').textContent = '✅ Função agendarAula está funcionando';
            } else {
                console.error('❌ Função agendarAula não está definida');
                document.getElementById('status').textContent = '❌ Função agendarAula não está definida';
            }
        }
        
        function testarHistorico() {
            console.log('Testando função historicoAluno...');
            document.getElementById('status').textContent = 'Testando histórico...';
            
            if (typeof historicoAluno === 'function') {
                console.log('✅ Função historicoAluno está definida');
                document.getElementById('status').textContent = '✅ Função historicoAluno está funcionando';
            } else {
                console.error('❌ Função historicoAluno não está definida');
                document.getElementById('status').textContent = '❌ Função historicoAluno não está definida';
            }
        }
        
        function testarDesativar() {
            console.log('Testando função desativarAluno...');
            document.getElementById('status').textContent = 'Testando desativar...';
            
            if (typeof desativarAluno === 'function') {
                console.log('✅ Função desativarAluno está definida');
                document.getElementById('status').textContent = '✅ Função desativarAluno está funcionando';
            } else {
                console.error('❌ Função desativarAluno não está definida');
                document.getElementById('status').textContent = '❌ Função desativarAluno não está definida';
            }
        }
        
        function testarExcluir() {
            console.log('Testando função excluirAluno...');
            document.getElementById('status').textContent = 'Testando excluir...';
            
            if (typeof excluirAluno === 'function') {
                console.log('✅ Função excluirAluno está definida');
                document.getElementById('status').textContent = '✅ Função excluirAluno está funcionando';
            } else {
                console.error('❌ Função excluirAluno não está definida');
                document.getElementById('status').textContent = '❌ Função excluirAluno não está definida';
            }
        }
        
        // Verificar todas as funções ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== VERIFICAÇÃO DAS FUNÇÕES ===');
            console.log('editarAluno:', typeof editarAluno);
            console.log('visualizarAluno:', typeof visualizarAluno);
            console.log('agendarAula:', typeof agendarAula);
            console.log('historicoAluno:', typeof historicoAluno);
            console.log('desativarAluno:', typeof desativarAluno);
            console.log('excluirAluno:', typeof excluirAluno);
            console.log('==============================');
            
            document.getElementById('status').textContent = 'Página carregada. Clique nos botões para testar as funções.';
        });
    </script>
</body>
</html>
