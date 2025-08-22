<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Simples - Funções de Alunos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/action-buttons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Teste Simples das Funções de Alunos</h1>
        
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Verificação das Funções</h5>
                
                <div class="row">
                    <div class="col-md-4">
                        <button type="button" class="btn btn-edit action-btn" onclick="testarEditar()">
                            <i class="fas fa-edit me-1"></i>Testar Editar
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-view action-btn" onclick="testarVisualizar()">
                            <i class="fas fa-eye me-1"></i>Testar Visualizar
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-delete action-btn" onclick="testarExcluir()">
                            <i class="fas fa-trash me-1"></i>Testar Excluir
                        </button>
                    </div>
                </div>
                
                <hr>
                
                <div id="resultado"></div>
            </div>
        </div>
    </div>

    <script>
        // Definir as funções que deveriam estar na página de alunos
        function editarAluno(id) {
            console.log('Função editarAluno chamada com ID:', id);
            alert('Função editarAluno funcionando! ID: ' + id);
        }
        
        function visualizarAluno(id) {
            console.log('Função visualizarAluno chamada com ID:', id);
            alert('Função visualizarAluno funcionando! ID: ' + id);
        }
        
        function excluirAluno(id) {
            console.log('Função excluirAluno chamada com ID:', id);
            if (confirm('Deseja realmente excluir o aluno com ID ' + id + '?')) {
                alert('Aluno excluído! ID: ' + id);
            }
        }
        
        // Funções de teste
        function testarEditar() {
            console.log('Testando editarAluno...');
            if (typeof editarAluno === 'function') {
                editarAluno(123);
                document.getElementById('resultado').innerHTML = '<div class="alert alert-success">✅ Função editarAluno funcionando!</div>';
            } else {
                document.getElementById('resultado').innerHTML = '<div class="alert alert-danger">❌ Função editarAluno não está definida!</div>';
            }
        }
        
        function testarVisualizar() {
            console.log('Testando visualizarAluno...');
            if (typeof visualizarAluno === 'function') {
                visualizarAluno(123);
                document.getElementById('resultado').innerHTML = '<div class="alert alert-success">✅ Função visualizarAluno funcionando!</div>';
            } else {
                document.getElementById('resultado').innerHTML = '<div class="alert alert-danger">❌ Função visualizarAluno não está definida!</div>';
            }
        }
        
        function testarExcluir() {
            console.log('Testando excluirAluno...');
            if (typeof excluirAluno === 'function') {
                excluirAluno(123);
                document.getElementById('resultado').innerHTML = '<div class="alert alert-success">✅ Função excluirAluno funcionando!</div>';
            } else {
                document.getElementById('resultado').innerHTML = '<div class="alert alert-danger">❌ Função excluirAluno não está definida!</div>';
            }
        }
        
        // Verificar ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== VERIFICAÇÃO DAS FUNÇÕES ===');
            console.log('editarAluno:', typeof editarAluno);
            console.log('visualizarAluno:', typeof visualizarAluno);
            console.log('excluirAluno:', typeof excluirAluno);
            console.log('==============================');
            
            document.getElementById('resultado').innerHTML = '<div class="alert alert-info">Página carregada. Clique nos botões para testar as funções.</div>';
        });
    </script>
</body>
</html>
