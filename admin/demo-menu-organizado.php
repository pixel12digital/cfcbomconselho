<?php
// Demonstração do Menu Organizado com Subitens
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo - Menu Organizado com Subitens</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .demo-container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .feature-card { margin-bottom: 2rem; }
        .code-example { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 1rem; margin: 1rem 0; }
        .highlight { background-color: #fff3cd; padding: 0.5rem; border-radius: 0.25rem; }
    </style>
</head>
<body>
    <div class="demo-container">
        <div class="text-center mb-5">
            <h1 class="display-4 text-primary">
                <i class="fas fa-sitemap me-3"></i>
                Menu Organizado com Subitens
            </h1>
            <p class="lead text-muted">Sistema de navegação otimizado para o painel administrativo</p>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card feature-card">
                    <div class="card-header bg-primary text-white">
                        <h4><i class="fas fa-star me-2"></i>Principais Melhorias</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fas fa-layer-group text-success me-2"></i>Organização por Categorias</h5>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success me-2"></i>Cadastros agrupados</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Operacional separado</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Relatórios organizados</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Configurações centralizadas</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fas fa-magic text-info me-2"></i>Funcionalidades</h5>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success me-2"></i>Menus dropdown</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Animações suaves</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Responsivo</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Navegação intuitiva</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card feature-card">
                    <div class="card-header bg-info text-white">
                        <h4><i class="fas fa-code me-2"></i>Estrutura do Menu</h4>
                    </div>
                    <div class="card-body">
                        <div class="code-example">
                            <h6 class="text-primary">📁 Cadastros</h6>
                            <ul class="list-unstyled ms-3">
                                <li><i class="fas fa-users me-2"></i>Usuários</li>
                                <li><i class="fas fa-building me-2"></i>CFCs</li>
                                <li><i class="fas fa-graduation-cap me-2"></i>Alunos</li>
                                <li><i class="fas fa-chalkboard-teacher me-2"></i>Instrutores</li>
                                <li><i class="fas fa-car me-2"></i>Veículos</li>
                            </ul>
                        </div>
                        
                        <div class="code-example">
                            <h6 class="text-success">📅 Operacional</h6>
                            <ul class="list-unstyled ms-3">
                                <li><i class="fas fa-calendar-plus me-2"></i>Agendamento</li>
                                <li><i class="fas fa-clock me-2"></i>Aulas</li>
                                <li><i class="fas fa-list-check me-2"></i>Sessões</li>
                            </ul>
                        </div>
                        
                        <div class="code-example">
                            <h6 class="text-warning">📊 Relatórios</h6>
                            <ul class="list-unstyled ms-3">
                                <li><i class="fas fa-graduation-cap me-2"></i>Relatório de Alunos</li>
                                <li><i class="fas fa-chalkboard-teacher me-2"></i>Relatório de Instrutores</li>
                                <li><i class="fas fa-calendar-check me-2"></i>Relatório de Aulas</li>
                                <li><i class="fas fa-dollar-sign me-2"></i>Relatório Financeiro</li>
                            </ul>
                        </div>
                        
                        <div class="code-example">
                            <h6 class="text-secondary">⚙️ Configurações</h6>
                            <ul class="list-unstyled ms-3">
                                <li><i class="fas fa-sliders-h me-2"></i>Configurações Gerais</li>
                                <li><i class="fas fa-file-alt me-2"></i>Logs do Sistema</li>
                                <li><i class="fas fa-download me-2"></i>Backup</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card feature-card">
                    <div class="card-header bg-success text-white">
                        <h4><i class="fas fa-rocket me-2"></i>Benefícios</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success">
                            <h6><i class="fas fa-search me-2"></i>Navegação Mais Rápida</h6>
                            <small>Usuários encontram o que precisam em menos cliques</small>
                        </div>
                        
                        <div class="alert alert-info">
                            <h6><i class="fas fa-brain me-2"></i>Organização Lógica</h6>
                            <small>Agrupamento por funcionalidade relacionada</small>
                        </div>
                        
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-mobile-alt me-2"></i>Melhor UX</h6>
                            <small>Interface mais limpa e profissional</small>
                        </div>
                        
                        <div class="alert alert-primary">
                            <h6><i class="fas fa-cogs me-2"></i>Manutenibilidade</h6>
                            <small>Código mais organizado e fácil de manter</small>
                        </div>
                    </div>
                </div>

                <div class="card feature-card">
                    <div class="card-header bg-warning text-dark">
                        <h4><i class="fas fa-lightbulb me-2"></i>Como Usar</h4>
                    </div>
                    <div class="card-body">
                        <ol class="list-unstyled">
                            <li class="mb-2">
                                <span class="badge bg-primary me-2">1</span>
                                Clique no título da categoria
                            </li>
                            <li class="mb-2">
                                <span class="badge bg-primary me-2">2</span>
                                O submenu expande automaticamente
                            </li>
                            <li class="mb-2">
                                <span class="badge bg-primary me-2">3</span>
                                Clique no item desejado
                            </li>
                            <li class="mb-2">
                                <span class="badge bg-primary me-2">4</span>
                                O submenu permanece aberto para a página ativa
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="card feature-card">
            <div class="card-header bg-dark text-white">
                <h4><i class="fas fa-code me-2"></i>Implementação Técnica</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary">Arquivos Modificados:</h6>
                        <ul class="list-unstyled">
                            <li><code>admin/index.php</code> - Estrutura HTML do menu</li>
                            <li><code>admin/assets/css/sidebar-dropdown.css</code> - Estilos dos dropdowns</li>
                            <li><code>admin/assets/css/admin.css</code> - Importação dos novos estilos</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-success">Funcionalidades JavaScript:</h6>
                        <ul class="list-unstyled">
                            <li>Toggle automático dos submenus</li>
                            <li>Rotacionamento das setas</li>
                            <li>Abertura automática da página ativa</li>
                            <li>Fechamento de outros submenus</li>
                        </ul>
                    </div>
                </div>
                
                <div class="highlight mt-3">
                    <strong>💡 Dica:</strong> O sistema automaticamente detecta qual página está ativa e mantém o submenu correspondente aberto, facilitando a navegação.
                </div>
            </div>
        </div>

        <div class="text-center mt-5">
            <a href="index.php" class="btn btn-primary btn-lg me-3">
                <i class="fas fa-arrow-left me-2"></i>Voltar ao Painel
            </a>
            <a href="teste-final-historico.php" class="btn btn-success btn-lg">
                <i class="fas fa-play me-2"></i>Testar Sistema
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
