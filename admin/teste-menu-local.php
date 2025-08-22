<?php
// Teste Local do Menu - Sistema CFC
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Local do Menu - Sistema CFC</title>
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
        
        /* Simulação do menu real */
        .admin-sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%);
            color: white;
            padding: 1rem;
            border-radius: 8px;
        }
        
        .nav-item { margin-bottom: 0.5rem; }
        .nav-link { 
            display: flex; 
            align-items: center; 
            padding: 0.75rem 1rem; 
            color: rgba(255, 255, 255, 0.8); 
            text-decoration: none; 
            border-radius: 8px; 
            transition: all 0.3s ease; 
        }
        .nav-link:hover { background-color: rgba(255, 255, 255, 0.1); color: white; }
        .nav-link.active { background-color: #0ea5e9; color: white; }
        
        .nav-icon { margin-right: 0.75rem; }
        .nav-text { flex: 1; }
        .nav-badge { 
            background-color: #0ea5e9; 
            color: white; 
            padding: 0.25rem 0.5rem; 
            border-radius: 10px; 
            font-size: 0.75rem; 
        }
        
        /* Estilos do dropdown */
        .nav-group { position: relative; }
        .nav-toggle { cursor: pointer; user-select: none; position: relative; }
        .nav-toggle:hover { background-color: rgba(255, 255, 255, 0.15) !important; }
        
        .nav-arrow { margin-left: auto; transition: transform 0.3s ease; }
        .nav-arrow i { font-size: 12px; opacity: 0.8; }
        
        .nav-submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, opacity 0.3s ease;
            opacity: 0;
            background-color: rgba(0, 0, 0, 0.1);
            margin: 0 1rem;
            border-radius: 8px;
            display: none;
        }
        
        .nav-submenu.open {
            max-height: 500px;
            opacity: 1;
            display: block !important;
        }
        
        .nav-sublink {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.5rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 0.875rem;
            margin: 2px 0.5rem;
            position: relative;
        }
        
        .nav-sublink:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            transform: translateX(5px);
        }
        
        .nav-sublink.active {
            background-color: #0ea5e9;
            color: #ffffff;
            font-weight: 600;
        }
        
        .nav-sublink i {
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            opacity: 0.9;
        }
        
        .nav-sublink span {
            flex: 1;
            font-weight: 500;
        }
        
        .nav-sublink .nav-badge {
            background-color: #0ea5e9;
            color: #ffffff;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
            min-width: 20px;
            text-align: center;
        }
        
        /* Animações */
        @keyframes slideDown {
            from { transform: scaleY(0); opacity: 0; }
            to { transform: scaleY(1); opacity: 1; }
        }
        
        .nav-submenu { animation: slideDown 0.3s ease-out; }
        
        /* Responsividade */
        @media (max-width: 1024px) {
            .nav-submenu { margin: 0 0.5rem; }
            .nav-sublink { padding: 0.5rem 1rem; font-size: 0.75rem; }
            .nav-sublink i { width: 14px; height: 14px; font-size: 0.75rem; }
        }
        
        /* Estados especiais */
        .nav-group:hover .nav-toggle { background-color: rgba(255, 255, 255, 0.1); }
        .nav-submenu .nav-sublink:first-child { margin-top: 0.5rem; }
        .nav-submenu .nav-sublink:last-child { margin-bottom: 0.5rem; }
        
        /* Indicadores visuais */
        .nav-group.has-active .nav-toggle { background-color: rgba(255, 255, 255, 0.15); color: #ffffff; }
        .nav-group.has-active .nav-toggle .nav-icon { color: #0ea5e9; }
        
        /* Acessibilidade */
        .nav-toggle:focus { outline: 2px solid #0ea5e9; outline-offset: 2px; }
        .nav-sublink:focus { outline: 2px solid #0ea5e9; outline-offset: 2px; }
        
        /* Páginas ativas */
        .nav-sublink.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 60%;
            background-color: #ffffff;
            border-radius: 0 2px 2px 0;
        }
        
        /* Hover effects */
        .nav-sublink:hover i { transform: scale(1.1); transition: transform 0.2s ease; }
        .nav-toggle:hover .nav-arrow i { transform: scale(1.1); transition: transform 0.2s ease; }
        
        /* Correções para produção */
        .nav-submenu.open { display: block !important; visibility: visible !important; opacity: 1 !important; }
        .nav-arrow i.fa-chevron-up { transform: rotate(180deg); }
        .nav-arrow i.fa-chevron-down { transform: rotate(0deg); }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="text-center mb-5">
            <h1 class="display-4 text-primary">
                <i class="fas fa-bug me-3"></i>
                Teste Local do Menu
            </h1>
            <p class="lead text-muted">Verificação completa do sistema de navegação local</p>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card test-section">
                    <div class="card-header bg-primary text-white">
                        <h4><i class="fas fa-search me-2"></i>Diagnóstico Local</h4>
                    </div>
                    <div class="card-body">
                        <div id="diagnostic-results">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Carregando...</span>
                                </div>
                                <p class="mt-2">Executando diagnóstico local...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card test-section">
                    <div class="card-header bg-info text-white">
                        <h4><i class="fas fa-code me-2"></i>Estrutura HTML Local</h4>
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
                        <h4><i class="fas fa-check-circle me-2"></i>Status Local</h4>
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
                        <button class="btn btn-info btn-sm w-100" onclick="testCSSConflicts()">
                            <i class="fas fa-palette me-2"></i>Testar CSS
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card test-section">
            <div class="card-header bg-danger text-white">
                <h4><i class="fas fa-fire me-2"></i>Menu Simulado Local</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="admin-sidebar">
                            <div class="sidebar-header">
                                <div class="sidebar-title">Navegação</div>
                                <div class="sidebar-subtitle">Sistema CFC</div>
                            </div>
                            
                            <div class="nav-menu">
                                <!-- Dashboard -->
                                <div class="nav-item">
                                    <a href="#" class="nav-link active">
                                        <div class="nav-icon">
                                            <i class="fas fa-chart-line"></i>
                                        </div>
                                        <div class="nav-text">Dashboard</div>
                                    </a>
                                </div>
                                
                                <!-- Cadastros -->
                                <div class="nav-item nav-group">
                                    <div class="nav-link nav-toggle" data-group="cadastros">
                                        <div class="nav-icon">
                                            <i class="fas fa-database"></i>
                                        </div>
                                        <div class="nav-text">Cadastros</div>
                                        <div class="nav-arrow">
                                            <i class="fas fa-chevron-down"></i>
                                        </div>
                                    </div>
                                    <div class="nav-submenu" id="cadastros">
                                        <a href="#" class="nav-sublink">
                                            <i class="fas fa-users"></i>
                                            <span>Usuários</span>
                                        </a>
                                        <a href="#" class="nav-sublink">
                                            <i class="fas fa-graduation-cap"></i>
                                            <span>Alunos</span>
                                            <div class="nav-badge">2</div>
                                        </a>
                                        <a href="#" class="nav-sublink">
                                            <i class="fas fa-chalkboard-teacher"></i>
                                            <span>Instrutores</span>
                                            <div class="nav-badge">2</div>
                                        </a>
                                    </div>
                                </div>
                                
                                <!-- Operacional -->
                                <div class="nav-item nav-group">
                                    <div class="nav-link nav-toggle" data-group="operacional">
                                        <div class="nav-icon">
                                            <i class="fas fa-calendar-alt"></i>
                                        </div>
                                        <div class="nav-text">Operacional</div>
                                        <div class="nav-arrow">
                                            <i class="fas fa-chevron-down"></i>
                                        </div>
                                    </div>
                                    <div class="nav-submenu" id="operacional">
                                        <a href="#" class="nav-sublink">
                                            <i class="fas fa-calendar-plus"></i>
                                            <span>Agendamento</span>
                                        </a>
                                        <a href="#" class="nav-sublink">
                                            <i class="fas fa-clock"></i>
                                            <span>Aulas</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div id="menu-test-results">
                            <p class="text-muted">Clique nos botões de teste para ver os resultados aqui...</p>
                        </div>
                        
                        <div class="mt-3">
                            <h6>Testes de Funcionalidade:</h6>
                            <ul>
                                <li><strong>Toggle dos Menus:</strong> Clique em "Cadastros" ou "Operacional"</li>
                                <li><strong>Hover Effects:</strong> Passe o mouse sobre os itens</li>
                                <li><strong>Responsividade:</strong> Redimensione a janela</li>
                                <li><strong>Animações:</strong> Observe as transições suaves</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sistema de diagnóstico local do menu
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔍 Iniciando diagnóstico local do menu...');
            
            // Executar diagnóstico completo
            runLocalDiagnostic();
        });

        function runLocalDiagnostic() {
            const results = {
                htmlStructure: checkLocalHTMLStructure(),
                cssLoading: checkLocalCSSLoading(),
                javascriptExecution: checkLocalJavaScriptExecution(),
                menuElements: checkLocalMenuElements(),
                cssConflicts: checkLocalCSSConflicts()
            };

            displayLocalDiagnosticResults(results);
            updateLocalOverallStatus(results);
            generateLocalRecommendations(results);
        }

        function checkLocalHTMLStructure() {
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

        function checkLocalCSSLoading() {
            const computedStyle = window.getComputedStyle(document.body);
            const checks = {
                hasCustomProperties: computedStyle.getPropertyValue('--primary-color') !== '',
                navGroupDisplay: computedStyle.getPropertyValue('display'),
                flexboxSupport: CSS.supports('display', 'flex'),
                transitionSupport: CSS.supports('transition', 'all 0.3s ease')
            };

            const issues = [];
            if (!checks.flexboxSupport) issues.push('Flexbox não suportado pelo navegador');
            if (!checks.transitionSupport) issues.push('Transições CSS não suportadas');

            return {
                checks,
                issues,
                status: issues.length === 0 ? 'success' : 'warning'
            };
        }

        function checkLocalJavaScriptExecution() {
            const checks = {
                eventListeners: 0,
                functionsDefined: typeof toggleSubmenu !== 'undefined',
                consoleLogs: true,
                domReady: true
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

        function checkLocalMenuElements() {
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

        function checkLocalCSSConflicts() {
            const checks = {
                conflictingStyles: 0,
                importantDeclarations: 0,
                specificityIssues: 0
            };

            // Verificar estilos conflitantes
            const allStyles = document.styleSheets;
            let conflicts = [];

            try {
                for (let sheet of allStyles) {
                    if (sheet.href && sheet.href.includes('admin')) {
                        const rules = sheet.cssRules || sheet.rules;
                        if (rules) {
                            for (let rule of rules) {
                                if (rule.selectorText && rule.selectorText.includes('.nav-')) {
                                    if (rule.style.getPropertyValue('display') === 'none') {
                                        conflicts.push(`Display none encontrado em: ${rule.selectorText}`);
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (e) {
                console.warn('Não foi possível verificar todos os estilos:', e);
            }

            const issues = conflicts.length > 0 ? conflicts : [];
            return {
                checks: { conflicts: conflicts.length },
                issues,
                status: issues.length === 0 ? 'success' : 'warning'
            };
        }

        function displayLocalDiagnosticResults(results) {
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
            displayLocalHTMLStructure(results.htmlStructure);
            displayLocalIssues(results);
        }

        function displayLocalHTMLStructure(structure) {
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

        function displayLocalIssues(results) {
            const issuesDiv = document.getElementById('issues-list');
            let allIssues = [];
            
            Object.values(results).forEach(result => {
                allIssues = allIssues.concat(result.issues);
            });

            if (allIssues.length === 0) {
                issuesDiv.innerHTML = '<div class="status-indicator status-success"><i class="fas fa-check-circle me-2"></i>Nenhum problema encontrado localmente!</div>';
            } else {
                let html = '<ul class="list-unstyled">';
                allIssues.forEach(issue => {
                    html += `<li class="mb-2"><i class="fas fa-exclamation-triangle text-warning me-2"></i>${issue}</li>`;
                });
                html += '</ul>';
                issuesDiv.innerHTML = html;
            }
        }

        function updateLocalOverallStatus(results) {
            const statusDiv = document.getElementById('overall-status');
            const errorCount = Object.values(results).filter(r => r.status === 'error').length;
            const warningCount = Object.values(results).filter(r => r.status === 'warning').length;

            let statusClass, statusText, statusIcon;
            
            if (errorCount === 0 && warningCount === 0) {
                statusClass = 'status-success';
                statusText = 'Funcionando Perfeitamente Localmente';
                statusIcon = 'check-circle';
            } else if (errorCount === 0) {
                statusClass = 'status-warning';
                statusText = 'Funcionando com Avisos Locais';
                statusIcon = 'exclamation-triangle';
            } else {
                statusClass = 'status-error';
                statusText = 'Problemas Críticos Locais';
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

        function generateLocalRecommendations(results) {
            const actionsDiv = document.getElementById('recommended-actions');
            let recommendations = [];

            if (results.htmlStructure.status === 'error') {
                recommendations.push('Verificar estrutura HTML do menu local');
            }
            if (results.cssLoading.status === 'warning') {
                recommendations.push('Verificar suporte do navegador local');
            }
            if (results.javascriptExecution.status === 'error') {
                recommendations.push('Verificar execução do JavaScript local');
            }
            if (results.cssConflicts.status === 'warning') {
                recommendations.push('Verificar conflitos de CSS locais');
            }

            if (recommendations.length === 0) {
                recommendations.push('Menu funcionando perfeitamente localmente!');
            }

            let html = '<ul class="list-unstyled">';
            recommendations.forEach(rec => {
                html += `<li class="mb-2"><i class="fas fa-lightbulb text-info me-2"></i>${rec}</li>`;
            });
            html += '</ul>';
            actionsDiv.innerHTML = html;
        }

        // Funções de teste local
        function testMenuToggle() {
            const resultsDiv = document.getElementById('menu-test-results');
            resultsDiv.innerHTML = '<div class="alert alert-info">🔄 Testando toggle do menu local...</div>';
            
            setTimeout(() => {
                const toggle = document.querySelector('[data-group="cadastros"]');
                if (toggle) {
                    toggle.click();
                    resultsDiv.innerHTML = '<div class="alert alert-success">✅ Toggle testado com sucesso localmente!</div>';
                } else {
                    resultsDiv.innerHTML = '<div class="alert alert-danger">❌ Toggle não encontrado localmente</div>';
                }
            }, 500);
        }

        function testSubmenuDisplay() {
            const resultsDiv = document.getElementById('menu-test-results');
            const submenu = document.getElementById('cadastros');
            
            if (submenu) {
                submenu.style.display = 'block';
                submenu.style.opacity = '1';
                submenu.style.maxHeight = '500px';
                resultsDiv.innerHTML = '<div class="alert alert-success">✅ Submenu exibido forçadamente localmente!</div>';
            } else {
                resultsDiv.innerHTML = '<div class="alert alert-danger">❌ Submenu não encontrado localmente</div>';
            }
        }

        function testResponsiveness() {
            const resultsDiv = document.getElementById('menu-test-results');
            const width = window.innerWidth;
            
            if (width <= 1024) {
                resultsDiv.innerHTML = `<div class="alert alert-warning">📱 Modo responsivo ativo localmente (${width}px)</div>`;
            } else {
                resultsDiv.innerHTML = `<div class="alert alert-info">🖥️ Modo desktop local (${width}px)</div>`;
            }
        }

        function testCSSConflicts() {
            const resultsDiv = document.getElementById('menu-test-results');
            resultsDiv.innerHTML = '<div class="alert alert-info">🔍 Verificando conflitos de CSS locais...</div>';
            
            setTimeout(() => {
                const conflicts = checkLocalCSSConflicts();
                if (conflicts.issues.length === 0) {
                    resultsDiv.innerHTML = '<div class="alert alert-success">✅ Nenhum conflito de CSS encontrado localmente!</div>';
                } else {
                    resultsDiv.innerHTML = `<div class="alert alert-warning">⚠️ ${conflicts.issues.length} conflito(s) de CSS encontrado(s) localmente</div>`;
                }
            }, 500);
        }

        // Sistema de menus dropdown local
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Iniciando sistema de menus dropdown local...');
            
            // Controle dos menus dropdown
            const navToggles = document.querySelectorAll('.nav-toggle');
            console.log(`📋 Encontrados ${navToggles.length} toggles de menu local`);
            
            // Função para alternar submenu
            function toggleSubmenu(toggleElement) {
                const group = toggleElement.getAttribute('data-group');
                const submenu = document.getElementById(group);
                const arrow = toggleElement.querySelector('.nav-arrow i');
                
                if (!submenu) {
                    console.error(`❌ Submenu não encontrado para o grupo: ${group}`);
                    return;
                }
                
                console.log(`🔄 Alternando submenu local: ${group}`);
                
                // Fechar outros submenus
                document.querySelectorAll('.nav-submenu').forEach(menu => {
                    if (menu.id !== group) {
                        menu.classList.remove('open');
                        const otherToggle = menu.previousElementSibling;
                        if (otherToggle) {
                            const otherArrow = otherToggle.querySelector('.nav-arrow i');
                            if (otherArrow) {
                                otherArrow.classList.remove('fa-chevron-up');
                                otherArrow.classList.add('fa-chevron-down');
                            }
                        }
                    }
                });
                
                // Toggle do submenu atual
                const isOpen = submenu.classList.contains('open');
                submenu.classList.toggle('open');
                
                // Rotacionar seta
                if (arrow) {
                    if (submenu.classList.contains('open')) {
                        arrow.classList.remove('fa-chevron-down');
                        arrow.classList.add('fa-chevron-up');
                    } else {
                        arrow.classList.remove('fa-chevron-up');
                        arrow.classList.add('fa-chevron-down');
                    }
                }
                
                console.log(`✅ Submenu local ${group} ${isOpen ? 'fechado' : 'aberto'}`);
            }
            
            // Adicionar event listeners
            navToggles.forEach((toggle, index) => {
                console.log(`🔗 Adicionando listener local para toggle ${index + 1}: ${toggle.getAttribute('data-group')}`);
                
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log(`🖱️ Clique local no toggle: ${this.getAttribute('data-group')}`);
                    toggleSubmenu(this);
                });
            });
            
            console.log('✅ Sistema de menus dropdown local inicializado com sucesso!');
        });
    </script>
</body>
</html>
