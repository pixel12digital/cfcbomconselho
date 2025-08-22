<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug das Funções de Alunos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/action-buttons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Debug das Funções de Alunos</h1>
        
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Verificação das Funções JavaScript</h5>
                
                <div class="alert alert-info">
                    <strong>Instruções:</strong> 
                    <ol>
                        <li>Abra o console do navegador (F12)</li>
                        <li>Clique em "Verificar Funções"</li>
                        <li>Verifique as mensagens no console</li>
                    </ol>
                </div>
                
                <button type="button" class="btn btn-primary" onclick="verificarFuncoes()">
                    🔍 Verificar Funções
                </button>
                
                <hr>
                
                <div id="resultado"></div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-body">
                <h5 class="card-title">Teste de Botões</h5>
                
                <div class="row">
                    <div class="col-md-3">
                        <button type="button" class="btn btn-edit action-btn" onclick="testarBotao('editar')">
                            <i class="fas fa-edit me-1"></i>Editar
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-view action-btn" onclick="testarBotao('visualizar')">
                            <i class="fas fa-eye me-1"></i>Ver
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-schedule action-btn" onclick="testarBotao('agendar')">
                            <i class="fas fa-calendar-plus me-1"></i>Agendar
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-delete action-btn" onclick="testarBotao('excluir')">
                            <i class="fas fa-trash me-1"></i>Excluir
                        </button>
                    </div>
                </div>
                
                <div class="mt-3">
                    <div class="alert alert-warning">
                        <strong>Status dos Botões:</strong> <span id="statusBotao">Aguardando teste...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Função para verificar se as funções estão definidas
        function verificarFuncoes() {
            console.log('=== VERIFICAÇÃO DAS FUNÇÕES ===');
            
            const funcoes = [
                'editarAluno',
                'visualizarAluno', 
                'agendarAula',
                'historicoAluno',
                'desativarAluno',
                'excluirAluno'
            ];
            
            let resultado = '<h6>Resultado da Verificação:</h6><ul>';
            let todasDefinidas = true;
            
            funcoes.forEach(funcao => {
                const estaDefinida = typeof window[funcao] === 'function';
                const status = estaDefinida ? '✅' : '❌';
                const texto = estaDefinida ? 'Definida' : 'NÃO DEFINIDA';
                
                console.log(`${status} ${funcao}: ${texto}`);
                resultado += `<li>${status} <strong>${funcao}</strong>: ${texto}</li>`;
                
                if (!estaDefinida) {
                    todasDefinidas = false;
                }
            });
            
            resultado += '</ul>';
            
            if (todasDefinidas) {
                resultado += '<div class="alert alert-success">✅ Todas as funções estão definidas!</div>';
            } else {
                resultado += '<div class="alert alert-danger">❌ Algumas funções não estão definidas!</div>';
            }
            
            document.getElementById('resultado').innerHTML = resultado;
            
            // Verificar se há erros no console
            console.log('=== VERIFICAÇÃO DE ERROS ===');
            console.log('Verifique se há erros JavaScript no console acima');
        }
        
        // Função para testar os botões
        function testarBotao(tipo) {
            const status = document.getElementById('statusBotao');
            status.textContent = `Testando botão: ${tipo}`;
            
            console.log(`Testando botão: ${tipo}`);
            
            // Verificar se o CSS está sendo aplicado
            const botao = event.target.closest('button');
            if (botao) {
                const estilos = window.getComputedStyle(botao);
                console.log(`Estilos do botão ${tipo}:`, {
                    backgroundColor: estilos.backgroundColor,
                    color: estilos.color,
                    padding: estilos.padding,
                    borderRadius: estilos.borderRadius
                });
            }
            
            // Simular ação do botão
            setTimeout(() => {
                status.textContent = `Botão ${tipo} testado com sucesso!`;
                status.className = 'alert alert-success';
            }, 1000);
        }
        
        // Verificar ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Página de debug carregada');
            console.log('Verifique se há erros JavaScript no console');
            
            // Verificar se o CSS foi carregado
            const testButton = document.querySelector('.btn-edit');
            if (testButton) {
                const estilos = window.getComputedStyle(testButton);
                console.log('CSS carregado:', {
                    backgroundColor: estilos.backgroundColor,
                    color: estilos.color
                });
            }
        });
        
        // Capturar erros JavaScript
        window.addEventListener('error', function(e) {
            console.error('Erro JavaScript capturado:', e.error);
            document.getElementById('resultado').innerHTML += `
                <div class="alert alert-danger">
                    <strong>Erro JavaScript:</strong> ${e.error.message}
                </div>
            `;
        });
    </script>
</body>
</html>
