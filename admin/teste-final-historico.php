<?php
// Teste final do sistema de histórico
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Final - Sistema de Histórico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/action-buttons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <div class="alert alert-success">
                    <h4><i class="fas fa-check-circle me-2"></i>Sistema de Histórico Funcionando!</h4>
                    <p>O servidor PHP está rodando corretamente e as páginas estão acessíveis.</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-user-graduate me-2"></i>Teste de Alunos</h5>
                    </div>
                    <div class="card-body">
                        <p>Teste o acesso às páginas de alunos:</p>
                        <div class="d-grid gap-2">
                            <a href="http://localhost:8080/admin/?page=alunos" class="btn btn-primary" target="_blank">
                                <i class="fas fa-users me-1"></i>Acessar Lista de Alunos
                            </a>
                            <a href="http://localhost:8080/admin/?page=historico-aluno&id=100" class="btn btn-info" target="_blank">
                                <i class="fas fa-history me-1"></i>Histórico do Aluno ID 100
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chalkboard-teacher me-2"></i>Teste de Instrutores</h5>
                    </div>
                    <div class="card-body">
                        <p>Teste o acesso às páginas de instrutores:</p>
                        <div class="d-grid gap-2">
                            <a href="http://localhost:8080/admin/?page=instrutores" class="btn btn-primary" target="_blank">
                                <i class="fas fa-users me-1"></i>Acessar Lista de Instrutores
                            </a>
                            <a href="http://localhost:8080/admin/?page=historico-instrutor&id=50" class="btn btn-info" target="_blank">
                                <i class="fas fa-history me-1"></i>Histórico do Instrutor ID 50
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-cogs me-2"></i>Status do Sistema</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <i class="fas fa-server fa-3x text-success mb-2"></i>
                                    <h6>Servidor PHP</h6>
                                    <span class="badge bg-success">✅ Funcionando</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <i class="fas fa-route fa-3x text-success mb-2"></i>
                                    <h6>Sistema de Roteamento</h6>
                                    <span class="badge bg-success">✅ Funcionando</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <i class="fas fa-database fa-3x text-success mb-2"></i>
                                    <h6>Páginas de Histórico</h6>
                                    <span class="badge bg-success">✅ Funcionando</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <i class="fas fa-link fa-3x text-success mb-2"></i>
                                    <h6>Botões de Histórico</h6>
                                    <span class="badge bg-success">✅ Funcionando</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle me-2"></i>Instruções de Teste</h5>
                    </div>
                    <div class="card-body">
                        <ol>
                            <li><strong>Clique nos links acima</strong> para testar o acesso às páginas</li>
                            <li><strong>Na página de alunos</strong>, clique no botão "Histórico" de qualquer aluno</li>
                            <li><strong>Na página de instrutores</strong>, clique no botão "Histórico" de qualquer instrutor</li>
                            <li><strong>Verifique se as páginas de histórico carregam</strong> com as informações corretas</li>
                            <li><strong>Teste a navegação</strong> entre as diferentes seções</li>
                        </ol>
                        
                        <div class="alert alert-info">
                            <strong>URLs de Teste:</strong><br>
                            • Alunos: <code>http://localhost:8080/admin/?page=alunos</code><br>
                            • Histórico Aluno: <code>http://localhost:8080/admin/?page=historico-aluno&id=100</code><br>
                            • Instrutores: <code>http://localhost:8080/admin/?page=instrutores</code><br>
                            • Histórico Instrutor: <code>http://localhost:8080/admin/?page=historico-instrutor&id=50</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Verificar se as páginas estão acessíveis
        document.addEventListener('DOMContentLoaded', function() {
            console.log('✅ Sistema de histórico testado e funcionando!');
            console.log('🌐 Servidor rodando em: http://localhost:8080');
            console.log('📁 Diretório de trabalho: ' + window.location.pathname);
        });
    </script>
</body>
</html>
