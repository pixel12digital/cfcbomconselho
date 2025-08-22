<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Simples - Redirecionamento Histórico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>🧪 Teste Simples - Redirecionamento</h1>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Histórico de Aluno</h5>
                    </div>
                    <div class="card-body">
                        <p>Teste usando sistema de roteamento:</p>
                        <a href="?page=historico-aluno&id=102" class="btn btn-primary">
                            📊 Histórico Aluno ID 102
                        </a>
                        <hr>
                        <p><strong>URL:</strong> <code>?page=historico-aluno&id=102</code></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Histórico de Instrutor</h5>
                    </div>
                    <div class="card-body">
                        <p>Teste usando sistema de roteamento:</p>
                        <a href="?page=historico-instrutor&id=1" class="btn btn-success">
                            📊 Histórico Instrutor ID 1
                        </a>
                        <hr>
                        <p><strong>URL:</strong> <code>?page=historico-instrutor&id=1</code></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>📋 Instruções de Teste</h5>
                    </div>
                    <div class="card-body">
                        <ol>
                            <li>Clique nos botões acima para testar o redirecionamento</li>
                            <li>As páginas devem carregar corretamente dentro do sistema admin</li>
                            <li>Se houver erro 404, verifique o sistema de roteamento</li>
                            <li>Se houver erro de variáveis, verifique as páginas de histórico</li>
                        </ol>
                        
                        <div class="alert alert-info">
                            <strong>Status:</strong> 
                            <span id="status-teste" class="badge bg-secondary">Aguardando teste</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <a href="index.php" class="btn btn-outline-secondary">
                    ← Voltar ao Dashboard
                </a>
            </div>
        </div>
    </div>

    <script>
        // Verificar se as páginas estão funcionando
        window.addEventListener('DOMContentLoaded', function() {
            const statusElement = document.getElementById('status-teste');
            
            // Testar se o sistema de roteamento está funcionando
            fetch('?page=dashboard')
                .then(response => {
                    if (response.ok) {
                        statusElement.className = 'badge bg-success';
                        statusElement.textContent = '✅ Sistema funcionando';
                    } else {
                        statusElement.className = 'badge bg-danger';
                        statusElement.textContent = '❌ Erro no sistema';
                    }
                })
                .catch(error => {
                    statusElement.className = 'badge bg-danger';
                    statusElement.textContent = '❌ Erro de conexão';
                });
        });
    </script>
</body>
</html>
