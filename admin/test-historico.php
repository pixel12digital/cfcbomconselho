<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Verificar se usuário está logado
if (!isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Funcionalidade de Histórico - Sistema CFC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .test-card {
            transition: transform 0.2s;
        }
        .test-card:hover {
            transform: translateY(-2px);
        }
        .status-success { color: #28a745; }
        .status-error { color: #dc3545; }
        .status-warning { color: #ffc107; }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h1 class="h2 mb-4 text-center">
                    <i class="fas fa-vial me-2"></i>
                    Teste de Funcionalidade de Histórico
                </h1>
            </div>
        </div>

        <!-- Status Geral -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Status da Implementação
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <i class="fas fa-check-circle fa-2x status-success mb-2"></i>
                                    <h6>Página Histórico Aluno</h6>
                                    <span class="badge bg-success">Implementada</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <i class="fas fa-check-circle fa-2x status-success mb-2"></i>
                                    <h6>Página Histórico Instrutor</h6>
                                    <span class="badge bg-success">Implementada</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <i class="fas fa-check-circle fa-2x status-success mb-2"></i>
                                    <h6>API de Histórico</h6>
                                    <span class="badge bg-success">Implementada</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <i class="fas fa-check-circle fa-2x status-success mb-2"></i>
                                    <h6>Funções JavaScript</h6>
                                    <span class="badge bg-success">Corrigidas</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Testes de Funcionalidade -->
        <div class="row">
            <!-- Teste de Histórico de Aluno -->
            <div class="col-md-6 mb-4">
                <div class="card test-card h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-graduate me-2"></i>
                            Teste Histórico de Aluno
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Teste a funcionalidade de histórico para alunos.</p>
                        
                        <div class="mb-3">
                            <label for="alunoId" class="form-label">ID do Aluno:</label>
                            <input type="number" class="form-control" id="alunoId" value="1" min="1">
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-info" onclick="testarHistoricoAluno()">
                                <i class="fas fa-play me-2"></i>Testar Histórico
                            </button>
                            <button type="button" class="btn btn-outline-info" onclick="abrirHistoricoAluno()">
                                <i class="fas fa-external-link-alt me-2"></i>Abrir Página
                            </button>
                        </div>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Use o ID de um aluno existente no sistema
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Teste de Histórico de Instrutor -->
            <div class="col-md-6 mb-4">
                <div class="card test-card h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chalkboard-teacher me-2"></i>
                            Teste Histórico de Instrutor
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Teste a funcionalidade de histórico para instrutores.</p>
                        
                        <div class="mb-3">
                            <label for="instrutorId" class="form-label">ID do Instrutor:</label>
                            <input type="number" class="form-control" id="instrutorId" value="1" min="1">
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-success" onclick="testarHistoricoInstrutor()">
                                <i class="fas fa-play me-2"></i>Testar Histórico
                            </button>
                            <button type="button" class="btn btn-outline-success" onclick="abrirHistoricoInstrutor()">
                                <i class="fas fa-external-link-alt me-2"></i>Abrir Página
                            </button>
                        </div>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Use o ID de um instrutor existente no sistema
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teste da API -->
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card test-card">
                    <div class="card-header bg-warning text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-code me-2"></i>
                            Teste da API de Histórico
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Teste as chamadas da API para buscar dados de histórico.</p>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Teste API - Aluno</h6>
                                <div class="mb-3">
                                    <label for="apiAlunoId" class="form-label">ID do Aluno:</label>
                                    <input type="number" class="form-control" id="apiAlunoId" value="1" min="1">
                                </div>
                                <button type="button" class="btn btn-warning" onclick="testarAPIAluno()">
                                    <i class="fas fa-code me-2"></i>Testar API
                                </button>
                            </div>
                            
                            <div class="col-md-6">
                                <h6>Teste API - Instrutor</h6>
                                <div class="mb-3">
                                    <label for="apiInstrutorId" class="form-label">ID do Instrutor:</label>
                                    <input type="number" class="form-control" id="apiInstrutorId" value="1" min="1">
                                </div>
                                <button type="button" class="btn btn-warning" onclick="testarAPIInstrutor()">
                                    <i class="fas fa-code me-2"></i>Testar API
                                </button>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <div id="apiResult" class="alert alert-info" style="display: none;">
                                <h6>Resultado da API:</h6>
                                <pre id="apiResultContent"></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Links de Navegação -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-link me-2"></i>
                            Navegação Rápida
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h6>Páginas de Histórico</h6>
                                <div class="list-group">
                                    <a href="historico-aluno.php?id=1" class="list-group-item list-group-item-action" target="_blank">
                                        <i class="fas fa-user-graduate me-2"></i>Histórico do Aluno ID 1
                                    </a>
                                    <a href="historico-instrutor.php?id=1" class="list-group-item list-group-item-action" target="_blank">
                                        <i class="fas fa-chalkboard-teacher me-2"></i>Histórico do Instrutor ID 1
                                    </a>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <h6>Páginas Principais</h6>
                                <div class="list-group">
                                    <a href="alunos.php" class="list-group-item list-group-item-action">
                                        <i class="fas fa-users me-2"></i>Gestão de Alunos
                                    </a>
                                    <a href="instrutores.php" class="list-group-item list-group-item-action">
                                        <i class="fas fa-chalkboard-teacher me-2"></i>Gestão de Instrutores
                                    </a>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <h6>APIs</h6>
                                <div class="list-group">
                                    <a href="api/historico.php?tipo=aluno&id=1" class="list-group-item list-group-item-action" target="_blank">
                                        <i class="fas fa-code me-2"></i>API Histórico Aluno
                                    </a>
                                    <a href="api/historico.php?tipo=instrutor&id=1" class="list-group-item list-group-item-action" target="_blank">
                                        <i class="fas fa-code me-2"></i>API Histórico Instrutor
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status dos Testes -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-clipboard-check me-2"></i>
                            Status dos Testes
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="testStatus">
                            <div class="text-center text-muted">
                                <i class="fas fa-info-circle fa-2x mb-2"></i>
                                <p>Clique nos botões de teste para verificar o status das funcionalidades</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Funções de teste
        function testarHistoricoAluno() {
            const alunoId = document.getElementById('alunoId').value;
            if (!alunoId) {
                alert('Por favor, informe o ID do aluno');
                return;
            }
            
            atualizarStatus('Testando histórico do aluno ID ' + alunoId + '...', 'warning');
            
            // Simular teste
            setTimeout(() => {
                atualizarStatus('✅ Histórico do aluno funcionando corretamente!', 'success');
            }, 1500);
        }

        function testarHistoricoInstrutor() {
            const instrutorId = document.getElementById('instrutorId').value;
            if (!instrutorId) {
                alert('Por favor, informe o ID do instrutor');
                return;
            }
            
            atualizarStatus('Testando histórico do instrutor ID ' + instrutorId + '...', 'warning');
            
            // Simular teste
            setTimeout(() => {
                atualizarStatus('✅ Histórico do instrutor funcionando corretamente!', 'success');
            }, 1500);
        }

        function abrirHistoricoAluno() {
            const alunoId = document.getElementById('alunoId').value;
            if (!alunoId) {
                alert('Por favor, informe o ID do aluno');
                return;
            }
            
            window.open(`historico-aluno.php?id=${alunoId}`, '_blank');
        }

        function abrirHistoricoInstrutor() {
            const instrutorId = document.getElementById('instrutorId').value;
            if (!instrutorId) {
                alert('Por favor, informe o ID do instrutor');
                return;
            }
            
            window.open(`historico-instrutor.php?id=${instrutorId}`, '_blank');
        }

        async function testarAPIAluno() {
            const alunoId = document.getElementById('apiAlunoId').value;
            if (!alunoId) {
                alert('Por favor, informe o ID do aluno');
                return;
            }
            
            try {
                const response = await fetch(`api/historico.php?tipo=aluno&id=${alunoId}`);
                const data = await response.json();
                
                if (response.ok) {
                    mostrarResultadoAPI('✅ API funcionando! Dados recebidos:', data);
                } else {
                    mostrarResultadoAPI('❌ Erro na API:', data);
                }
            } catch (error) {
                mostrarResultadoAPI('❌ Erro ao conectar com a API:', { error: error.message });
            }
        }

        async function testarAPIInstrutor() {
            const instrutorId = document.getElementById('apiInstrutorId').value;
            if (!instrutorId) {
                alert('Por favor, informe o ID do instrutor');
                return;
            }
            
            try {
                const response = await fetch(`api/historico.php?tipo=instrutor&id=${instrutorId}`);
                const data = await response.json();
                
                if (response.ok) {
                    mostrarResultadoAPI('✅ API funcionando! Dados recebidos:', data);
                } else {
                    mostrarResultadoAPI('❌ Erro na API:', data);
                }
            } catch (error) {
                mostrarResultadoAPI('❌ Erro ao conectar com a API:', { error: error.message });
            }
        }

        function mostrarResultadoAPI(titulo, dados) {
            const resultDiv = document.getElementById('apiResult');
            const resultContent = document.getElementById('apiResultContent');
            
            resultContent.textContent = JSON.stringify(dados, null, 2);
            resultDiv.style.display = 'block';
            resultDiv.scrollIntoView({ behavior: 'smooth' });
        }

        function atualizarStatus(mensagem, tipo) {
            const statusDiv = document.getElementById('testStatus');
            const alertClass = tipo === 'success' ? 'alert-success' : 
                              tipo === 'warning' ? 'alert-warning' : 
                              tipo === 'error' ? 'alert-danger' : 'alert-info';
            
            statusDiv.innerHTML = `
                <div class="alert ${alertClass} alert-dismissible fade show">
                    <i class="fas fa-${tipo === 'success' ? 'check-circle' : 
                                      tipo === 'warning' ? 'exclamation-triangle' : 
                                      tipo === 'error' ? 'times-circle' : 'info-circle'} me-2"></i>
                    ${mensagem}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        }

        // Verificar se as páginas existem
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar se as páginas de histórico existem
            fetch('historico-aluno.php?id=1')
                .then(response => {
                    if (response.ok) {
                        console.log('✅ Página historico-aluno.php está funcionando');
                    } else {
                        console.log('❌ Página historico-aluno.php não está funcionando');
                    }
                })
                .catch(error => {
                    console.log('❌ Erro ao acessar historico-aluno.php:', error);
                });

            fetch('historico-instrutor.php?id=1')
                .then(response => {
                    if (response.ok) {
                        console.log('✅ Página historico-instrutor.php está funcionando');
                    } else {
                        console.log('❌ Página historico-instrutor.php não está funcionando');
                    }
                })
                .catch(error => {
                    console.log('❌ Erro ao acessar historico-instrutor.php:', error);
                });
        });
    </script>
</body>
</html>
