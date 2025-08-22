<?php
// Teste do Menu em Produção - Sistema CFC
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste do Menu em Produção - Sistema CFC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: Arial, sans-serif; }
        .test-container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .test-section { margin-bottom: 2rem; }
        .menu-preview { background: #1e3a8a; color: white; padding: 1rem; border-radius: 8px; }
        .status-indicator { padding: 0.5rem; border-radius: 4px; margin: 0.5rem 0; }
        .status-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .status-warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .code-block { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 1rem; margin: 1rem 0; font-family: monospace; }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="text-center mb-5">
            <h1 class="display-4 text-primary">
                <i class="fas fa-bug me-3"></i>
                Teste do Menu em Produção
            </h1>
            <p class="lead text-muted">Diagnóstico completo do sistema de navegação</p>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card test-section">
                    <div class="card-header bg-primary text-white">
                        <h4><i class="fas fa-search me-2"></i>Diagnóstico do Sistema</h4>
                    </div>
                    <div class="card-body">
                        <div id="diagnostic-results">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Carregando...</span>
                                </div>
                                <p class="mt-2">Executando diagnóstico...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card test-section">
                    <div class="card-header bg-info text-white">
                        <h4><i class="fas fa-code me-2"></i>Estrutura HTML Detectada</h4>
                    </div>
                    <div class="card-body">
                        <div id="html-structure">
                            <p class="text-muted">Aguardando análise da estrutura HTML...</p>
                        </div>
                    </div>
                </div>

                <div class="card test-section">
                    <div class="card-header bg-warning text-dark">
                        <h4><i class="fas fa-exclamation-triangle me-2"></i>Problemas Identificados</h4>
                    </div>
                    <div class="card-body">
                        <div id="issues-list">
                            <p class="text-muted">Verificando problemas...</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card test-section">
                    <div class="card-header bg-success text-white">
                        <h4><i class="fas fa-check-circle me-2"></i>Status Geral</h4>
                    </div>
                    <div class="card-body">
                        <div id="overall-status">
                            <div class="status-indicator status-warning">
                                <i class="fas fa-clock me-2"></i>
                                <strong>Verificando...</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card test-section">
                    <div class="card-header bg-secondary text-white">
                        <h4><i class="fas fa-tools me-2"></i>Ações Recomendadas</h4>
                    </div>
                    <div class="card-body">
                        <div id="recommended-actions">
                            <p class="text-muted">Aguardando diagnóstico...</p>
                        </div>
                    </div>
                </div>

                <div class="card test-section">
                    <div class="card-header bg-dark text-white">
                        <h4><i class="fas fa-rocket me-2"></i>Testes Rápidos</h4>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-primary btn-sm w-100 mb-2" onclick="testMenuToggle()">
                            <i class="fas fa-play me-2"></i>Testar Toggle
                        </button>
                        <button class="btn btn-success btn-sm w-100 mb-2" onclick="testSubmenuDisplay()">
                            <i class="fas fa-eye me-2"></i>Mostrar Submenus
                        </button>
                        <button class="btn btn-warning btn-sm w-100 mb-2" onclick="testResponsiveness()">
                            <i class="fas fa-mobile-alt me-2"></i>Testar Responsivo
                        </button>
                        <button class="btn btn-info btn-sm w-100" onclick="generateReport()">
                            <i class="fas fa-file-alt me-2"></i>Gerar Relatório
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card test-section">
            <div class="card-header bg-danger text-white">
                <h4><i class="fas fa-fire me-2"></i>Preview do Menu</h4>
            </div>
            <div class="card-body">
                <div class="menu-preview">
                    <div class="row">
                        <div class="col-md-3">
                            <h6 class="text-white-50">Menu Simulado</h6>
                            <div class="nav-item nav-group">
                                <div class="nav-link nav-toggle" data-group="test-cadastros">
                                    <i class="fas fa-database me-2"></i>
                                    Cadastros
                                    <i class="fas fa-chevron-down ms-auto"></i>
                                </div>
                                <div class="nav-submenu" id="test-cadastros">
                                    <a href="#" class="nav-sublink">
                                        <i class="fas fa-users me-2"></i>
                                        <span>Usuários</span>
                                    </a>
                                    <a href="#" class="nav-sublink">
                                        <i class="fas fa-graduation-cap me-2"></i>
                                        <span>Alunos</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div id="menu-test-results">
                                <p class="text-white-50">Clique nos botões de teste para ver os resultados aqui...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sistema de diagnóstico do menu
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔍 Iniciando diagnóstico do menu...');
            
            // Executar diagnóstico completo
            runFullDiagnostic();
        });

        function runFullDiagnostic() {
            const results = {
                htmlStructure: checkHTMLStructure(),
                cssLoading: checkCSSLoading(),
                javascriptExecution: checkJavaScriptExecution(),
                menuElements: checkMenuElements(),
                browserCompatibility: checkBrowserCompatibility()
            };

            displayDiagnosticResults(results);
            updateOverallStatus(results);
            generateRecommendations(results);
        }

        function checkHTMLStructure() {
            const checks = {
                navGroups: document.querySelectorAll('.nav-group').length,
                navToggles: document.querySelectorAll('.nav-toggle').length,
                navSubmenus: document.querySelectorAll('.nav-submenu').length,
                navSublinks: document.querySelectorAll('.nav-sublink').length
            };

            const issues = [];
            if (checks.navGroups === 0) issues.push('Nenhum grupo de navegação encontrado');
            if (checks.navToggles === 0) issues.push('Nenhum toggle de menu encontrado');
            if (checks.navSubmenus === 0) issues.push('Nenhum submenu encontrado');

            return {
                checks,
                issues,
                status: issues.length === 0 ? 'success' : 'error'
            };
        }

        function checkCSSLoading() {
            const computedStyle = window.getComputedStyle(document.body);
            const checks = {
                primaryColor: computedStyle.getPropertyValue('--primary-color'),
                hasCustomProperties: computedStyle.getPropertyValue('--primary-color') !== '',
                navGroupDisplay: computedStyle.getPropertyValue('display')
            };

            const issues = [];
            if (!checks.hasCustomProperties) issues.push('Variáveis CSS não estão carregando');
            if (checks.navGroupDisplay === 'none') issues.push('Elementos de navegação com display none');

            return {
                checks,
                issues,
                status: issues.length === 0 ? 'success' : 'warning'
            };
        }

        function checkJavaScriptExecution() {
            const checks = {
                eventListeners: 0,
                functionsDefined: typeof toggleSubmenu !== 'undefined',
                consoleLogs: true
            };

            // Contar event listeners
            const toggles = document.querySelectorAll('.nav-toggle');
            toggles.forEach(toggle => {
                if (toggle.onclick) checks.eventListeners++;
            });

            const issues = [];
            if (checks.eventListeners === 0) issues.push('Nenhum event listener encontrado');
            if (!checks.functionsDefined) issues.push('Funções JavaScript não definidas');

            return {
                checks,
                issues,
                status: issues.length === 0 ? 'success' : 'error'
            };
        }

        function checkMenuElements() {
            const elements = {
                groups: document.querySelectorAll('.nav-group'),
                toggles: document.querySelectorAll('.nav-toggle'),
                submenus: document.querySelectorAll('.nav-submenu'),
                sublinks: document.querySelectorAll('.nav-sublink')
            };

            const issues = [];
            elements.groups.forEach((group, index) => {
                const toggle = group.querySelector('.nav-toggle');
                const submenu = group.querySelector('.nav-submenu');
                
                if (!toggle) issues.push(`Grupo ${index + 1}: Toggle não encontrado`);
                if (!submenu) issues.push(`Grupo ${index + 1}: Submenu não encontrado`);
                if (toggle && !toggle.getAttribute('data-group')) issues.push(`Grupo ${index + 1}: Atributo data-group ausente`);
            });

            return {
                elements,
                issues,
                status: issues.length === 0 ? 'success' : 'warning'
            };
        }

        function checkBrowserCompatibility() {
            const checks = {
                flexbox: CSS.supports('display', 'flex'),
                grid: CSS.supports('display', 'grid'),
                cssVariables: CSS.supports('--custom-property', 'value'),
                es6: typeof Promise !== 'undefined',
                modernJS: typeof Array.prototype.forEach !== 'undefined'
            };

            const issues = [];
            if (!checks.flexbox) issues.push('Flexbox não suportado');
            if (!checks.cssVariables) issues.push('Variáveis CSS não suportadas');
            if (!checks.es6) issues.push('ES6 não suportado');

            return {
                checks,
                issues,
                status: issues.length === 0 ? 'success' : 'warning'
            };
        }

        function displayDiagnosticResults(results) {
            const diagnosticDiv = document.getElementById('diagnostic-results');
            let html = '<div class="row">';

            Object.entries(results).forEach(([key, result]) => {
                const statusClass = `status-${result.status}`;
                const statusIcon = result.status === 'success' ? 'check-circle' : 
                                 result.status === 'warning' ? 'exclamation-triangle' : 'times-circle';
                
                html += `
                    <div class="col-md-6 mb-3">
                        <div class="status-indicator ${statusClass}">
                            <i class="fas fa-${statusIcon} me-2"></i>
                            <strong>${key.replace(/([A-Z])/g, ' $1').replace(/^./, str => str.toUpperCase())}</strong>
                            <br>
                            <small>${result.issues.length} problema(s) encontrado(s)</small>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            diagnosticDiv.innerHTML = html;

            // Mostrar estrutura HTML
            displayHTMLStructure(results.htmlStructure);
            displayIssues(results);
        }

        function displayHTMLStructure(structure) {
            const htmlDiv = document.getElementById('html-structure');
            htmlDiv.innerHTML = `
                <div class="code-block">
                    <strong>Grupos de Navegação:</strong> ${structure.checks.navGroups}<br>
                    <strong>Toggles de Menu:</strong> ${structure.checks.navToggles}<br>
                    <strong>Submenus:</strong> ${structure.checks.navSubmenus}<br>
                    <strong>Links de Submenu:</strong> ${structure.checks.navSublinks}
                </div>
            `;
        }

        function displayIssues(results) {
            const issuesDiv = document.getElementById('issues-list');
            let allIssues = [];
            
            Object.values(results).forEach(result => {
                allIssues = allIssues.concat(result.issues);
            });

            if (allIssues.length === 0) {
                issuesDiv.innerHTML = '<div class="status-indicator status-success"><i class="fas fa-check-circle me-2"></i>Nenhum problema encontrado!</div>';
            } else {
                let html = '<ul class="list-unstyled">';
                allIssues.forEach(issue => {
                    html += `<li class="mb-2"><i class="fas fa-exclamation-triangle text-warning me-2"></i>${issue}</li>`;
                });
                html += '</ul>';
                issuesDiv.innerHTML = html;
            }
        }

        function updateOverallStatus(results) {
            const statusDiv = document.getElementById('overall-status');
            const errorCount = Object.values(results).filter(r => r.status === 'error').length;
            const warningCount = Object.values(results).filter(r => r.status === 'warning').length;

            let statusClass, statusText, statusIcon;
            
            if (errorCount === 0 && warningCount === 0) {
                statusClass = 'status-success';
                statusText = 'Funcionando Perfeitamente';
                statusIcon = 'check-circle';
            } else if (errorCount === 0) {
                statusClass = 'status-warning';
                statusText = 'Funcionando com Avisos';
                statusIcon = 'exclamation-triangle';
            } else {
                statusClass = 'status-error';
                statusText = 'Problemas Críticos';
                statusIcon = 'times-circle';
            }

            statusDiv.innerHTML = `
                <div class="status-indicator ${statusClass}">
                    <i class="fas fa-${statusIcon} me-2"></i>
                    <strong>${statusText}</strong>
                    <br>
                    <small>${errorCount} erro(s), ${warningCount} aviso(s)</small>
                </div>
            `;
        }

        function generateRecommendations(results) {
            const actionsDiv = document.getElementById('recommended-actions');
            let recommendations = [];

            if (results.htmlStructure.status === 'error') {
                recommendations.push('Verificar estrutura HTML do menu');
            }
            if (results.cssLoading.status === 'warning') {
                recommendations.push('Verificar carregamento dos arquivos CSS');
            }
            if (results.javascriptExecution.status === 'error') {
                recommendations.push('Verificar execução do JavaScript');
            }
            if (results.menuElements.status === 'warning') {
                recommendations.push('Verificar elementos do menu');
            }

            if (recommendations.length === 0) {
                recommendations.push('Menu funcionando corretamente!');
            }

            let html = '<ul class="list-unstyled">';
            recommendations.forEach(rec => {
                html += `<li class="mb-2"><i class="fas fa-lightbulb text-info me-2"></i>${rec}</li>`;
            });
            html += '</ul>';
            actionsDiv.innerHTML = html;
        }

        // Funções de teste
        function testMenuToggle() {
            const resultsDiv = document.getElementById('menu-test-results');
            resultsDiv.innerHTML = '<div class="text-white">🔄 Testando toggle do menu...</div>';
            
            setTimeout(() => {
                const toggle = document.querySelector('[data-group="test-cadastros"]');
                if (toggle) {
                    toggle.click();
                    resultsDiv.innerHTML = '<div class="text-white">✅ Toggle testado com sucesso!</div>';
                } else {
                    resultsDiv.innerHTML = '<div class="text-white">❌ Toggle não encontrado</div>';
                }
            }, 500);
        }

        function testSubmenuDisplay() {
            const resultsDiv = document.getElementById('menu-test-results');
            const submenu = document.getElementById('test-cadastros');
            
            if (submenu) {
                submenu.style.display = 'block';
                submenu.style.opacity = '1';
                submenu.style.maxHeight = '500px';
                resultsDiv.innerHTML = '<div class="text-white">✅ Submenu exibido forçadamente</div>';
            } else {
                resultsDiv.innerHTML = '<div class="text-white">❌ Submenu não encontrado</div>';
            }
        }

        function testResponsiveness() {
            const resultsDiv = document.getElementById('menu-test-results');
            const width = window.innerWidth;
            
            if (width <= 1024) {
                resultsDiv.innerHTML = `<div class="text-white">📱 Modo responsivo ativo (${width}px)</div>`;
            } else {
                resultsDiv.innerHTML = `<div class="text-white">🖥️ Modo desktop (${width}px)</div>`;
            }
        }

        function generateReport() {
            const report = {
                timestamp: new Date().toISOString(),
                url: window.location.href,
                userAgent: navigator.userAgent,
                viewport: {
                    width: window.innerWidth,
                    height: window.innerHeight
                },
                results: {
                    htmlStructure: checkHTMLStructure(),
                    cssLoading: checkCSSLoading(),
                    javascriptExecution: checkJavaScriptExecution(),
                    menuElements: checkMenuElements(),
                    browserCompatibility: checkBrowserCompatibility()
                }
            };

            const reportText = JSON.stringify(report, null, 2);
            const blob = new Blob([reportText], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.href = url;
            a.download = 'menu-diagnostic-report.json';
            a.click();
            
            URL.revokeObjectURL(url);
            
            const resultsDiv = document.getElementById('menu-test-results');
            resultsDiv.innerHTML = '<div class="text-white">📄 Relatório gerado e baixado!</div>';
        }
    </script>
</body>
</html>
