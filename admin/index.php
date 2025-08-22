<?php
// Definir caminho base
$base_path = dirname(__DIR__);
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Verificar se o usuário está logado e tem permissão de admin
if (!isLoggedIn() || !hasPermission('admin')) {
    header('Location: ../index.php');
    exit;
}

// Obter dados do usuário logado
$user = getCurrentUser();
$db = Database::getInstance();

// Obter estatísticas para o dashboard
$stats = [
    'total_alunos' => $db->count('alunos'),
    'total_instrutores' => $db->count('instrutores'),
    'total_aulas' => $db->count('aulas'),
    'total_veiculos' => $db->count('veiculos'),
    'aulas_hoje' => $db->count('aulas', 'data_aula = ?', [date('Y-m-d')]),
    'aulas_semana' => $db->count('aulas', 'data_aula >= ?', [date('Y-m-d', strtotime('monday this week'))])
];

// Obter últimas atividades
try {
    $ultimas_atividades = $db->fetchAll("
        SELECT 'aluno' as tipo, nome, 'cadastrado' as acao, criado_em as data
        FROM alunos 
        ORDER BY criado_em DESC 
        LIMIT 5
        UNION ALL
        SELECT 'instrutor' as tipo, u.nome, 'cadastrado' as acao, i.criado_em as data
        FROM instrutores i
        JOIN usuarios u ON i.usuario_id = u.id
        ORDER BY i.criado_em DESC 
        LIMIT 5
        ORDER BY data DESC 
        LIMIT 10
    ");
} catch (Exception $e) {
    $ultimas_atividades = [];
    if (LOG_ENABLED) {
        error_log('Erro ao buscar últimas atividades: ' . $e->getMessage());
    }
}

$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? 'list';

// Definir constante para indicar que o roteamento está ativo
define('ADMIN_ROUTING', true);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrativo - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- CSS Principal -->
    <link href="assets/css/admin.css" rel="stylesheet">
    
    <!-- CSS dos Botões de Ação -->
    <link href="assets/css/action-buttons.css" rel="stylesheet">
    
    <!-- Font Awesome para ícones - via Google Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons" crossorigin="anonymous">
    <style>
        /* CSS inline para ícones básicos */
        .fas, .fa-solid { font-family: 'Material Icons'; font-weight: normal; font-style: normal; }
        .fa-edit:before { content: 'edit'; }
        .fa-eye:before { content: 'visibility'; }
        .fa-calendar-plus:before { content: 'event'; }
        .fa-history:before { content: 'history'; }
        .fa-ban:before { content: 'block'; }
        .fa-check:before { content: 'check'; }
        .fa-trash:before { content: 'delete'; }
        .fa-plus:before { content: 'add'; }
        .fa-filter:before { content: 'filter_list'; }
        .fa-search:before { content: 'search'; }
    </style>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/logo.png">
</head>
<body>
    <!-- Container Principal -->
    <div class="admin-container">
        
        <!-- Header Superior -->
        <header class="admin-header">
            <div class="header-content">
                <div class="logo">
                    <img src="../assets/logo.png" alt="<?php echo APP_NAME; ?>">
                    <span>Sistema CFC - Admin</span>
                </div>
                
                <div class="header-actions">
                    <div class="user-menu">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($user['nome'], 0, 1)); ?>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($user['nome']); ?></div>
                            <div class="user-role">Administrador</div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Sidebar de Navegação -->
        <nav class="admin-sidebar">
            <div class="sidebar-header">
                <div class="sidebar-title">Navegação</div>
                <div class="sidebar-subtitle">Sistema CFC</div>
            </div>
            
            <div class="nav-menu">
                <!-- Dashboard -->
                <div class="nav-item">
                    <a href="index.php" class="nav-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>">
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
                        <a href="index.php?page=usuarios&action=list" class="nav-sublink <?php echo $page === 'usuarios' ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i>
                            <span>Usuários</span>
                        </a>
                        <a href="index.php?page=cfcs&action=list" class="nav-sublink <?php echo $page === 'cfcs' ? 'active' : ''; ?>">
                            <i class="fas fa-building"></i>
                            <span>CFCs</span>
                        </a>
                        <a href="index.php?page=alunos&action=list" class="nav-sublink <?php echo $page === 'alunos' ? 'active' : ''; ?>">
                            <i class="fas fa-graduation-cap"></i>
                            <span>Alunos</span>
                            <div class="nav-badge"><?php echo $stats['total_alunos']; ?></div>
                        </a>
                        <a href="index.php?page=instrutores&action=list" class="nav-sublink <?php echo $page === 'instrutores' ? 'active' : ''; ?>">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span>Instrutores</span>
                            <div class="nav-badge"><?php echo $stats['total_instrutores']; ?></div>
                        </a>
                        <a href="index.php?page=veiculos&action=list" class="nav-sublink <?php echo $page === 'veiculos' ? 'active' : ''; ?>">
                            <i class="fas fa-car"></i>
                            <span>Veículos</span>
                            <div class="nav-badge"><?php echo $stats['total_veiculos']; ?></div>
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
                        <a href="index.php?page=agendamento" class="nav-sublink <?php echo $page === 'agendamento' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-plus"></i>
                            <span>Agendamento</span>
                            <div class="nav-badge"><?php echo $stats['total_aulas']; ?></div>
                        </a>
                        <a href="index.php?page=aulas&action=list" class="nav-sublink <?php echo $page === 'aulas' ? 'active' : ''; ?>">
                            <i class="fas fa-clock"></i>
                            <span>Aulas</span>
                        </a>
                        <a href="index.php?page=sessoes&action=list" class="nav-sublink <?php echo $page === 'sessoes' ? 'active' : ''; ?>">
                            <i class="fas fa-list-check"></i>
                            <span>Sessões</span>
                        </a>
                    </div>
                </div>
                
                <!-- Relatórios -->
                <div class="nav-item nav-group">
                    <div class="nav-link nav-toggle" data-group="relatorios">
                        <div class="nav-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div class="nav-text">Relatórios</div>
                        <div class="nav-arrow">
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                    <div class="nav-submenu" id="relatorios">
                        <a href="index.php?page=relatorios&action=alunos" class="nav-sublink <?php echo $page === 'relatorios' && $action === 'alunos' ? 'active' : ''; ?>">
                            <i class="fas fa-graduation-cap"></i>
                            <span>Relatório de Alunos</span>
                        </a>
                        <a href="index.php?page=relatorios&action=instrutores" class="nav-sublink <?php echo $page === 'relatorios' && $action === 'instrutores' ? 'active' : ''; ?>">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span>Relatório de Instrutores</span>
                        </a>
                        <a href="index.php?page=relatorios&action=aulas" class="nav-sublink <?php echo $page === 'relatorios' && $action === 'aulas' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-check"></i>
                            <span>Relatório de Aulas</span>
                        </a>
                        <a href="index.php?page=relatorios&action=financeiro" class="nav-sublink <?php echo $page === 'relatorios' && $action === 'financeiro' ? 'active' : ''; ?>">
                            <i class="fas fa-dollar-sign"></i>
                            <span>Relatório Financeiro</span>
                        </a>
                    </div>
                </div>
                
                <!-- Configurações -->
                <div class="nav-item nav-group">
                    <div class="nav-link nav-toggle" data-group="configuracoes">
                        <div class="nav-icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <div class="nav-text">Configurações</div>
                        <div class="nav-arrow">
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                    <div class="nav-submenu" id="configuracoes">
                        <a href="index.php?page=configuracoes&action=geral" class="nav-sublink <?php echo $page === 'configuracoes' ? 'active' : ''; ?>">
                            <i class="fas fa-sliders-h"></i>
                            <span>Configurações Gerais</span>
                        </a>
                        <a href="index.php?page=logs&action=list" class="nav-sublink <?php echo $page === 'logs' ? 'active' : ''; ?>">
                            <i class="fas fa-file-alt"></i>
                            <span>Logs do Sistema</span>
                        </a>
                        <a href="index.php?page=backup" class="nav-sublink <?php echo $page === 'backup' ? 'active' : ''; ?>">
                            <i class="fas fa-download"></i>
                            <span>Backup</span>
                        </a>
                    </div>
                </div>
                
                <!-- Ferramentas de Desenvolvimento -->
                <div class="nav-item nav-group">
                    <div class="nav-link nav-toggle" data-group="ferramentas">
                        <div class="nav-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div class="nav-text">Ferramentas</div>
                        <div class="nav-arrow">
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                    <div class="nav-submenu" id="ferramentas">
                        <a href="test-novas-regras-agendamento.php" class="nav-sublink" target="_blank">
                            <i class="fas fa-flask"></i>
                            <span>Teste Regras</span>
                            <div class="nav-badge">🧪</div>
                        </a>
                        <a href="teste-producao-completo.php" class="nav-sublink" target="_blank">
                            <i class="fas fa-vial"></i>
                            <span>Teste Simulado</span>
                            <div class="nav-badge">📋</div>
                        </a>
                        <a href="teste-producao-real.php" class="nav-sublink" target="_blank">
                            <i class="fas fa-rocket"></i>
                            <span>Teste Real</span>
                            <div class="nav-badge">🚀</div>
                        </a>
                    </div>
                </div>
                
                <!-- Sair -->
                <div class="nav-item">
                    <a href="../logout.php" class="nav-link">
                        <div class="nav-icon">
                            <i class="fas fa-sign-out-alt"></i>
                        </div>
                        <div class="nav-text">Sair</div>
                    </a>
                </div>
            </div>
        </nav>
        
        <!-- Conteúdo Principal -->
        <main class="admin-main">
            <?php
            // Carregar dados necessários baseado na página
            switch ($page) {
                case 'alunos':
                    $alunos = $db->fetchAll("
                        SELECT a.*, c.nome as cfc_nome,
                               (SELECT MAX(data_aula) FROM aulas WHERE aluno_id = a.id) as ultima_aula
                        FROM alunos a 
                        LEFT JOIN cfcs c ON a.cfc_id = c.id 
                        ORDER BY a.nome ASC
                    ");
                    $cfcs = $db->fetchAll("SELECT id, nome FROM cfcs ORDER BY nome");
                    break;
                    
                case 'instrutores':
                    $instrutores = $db->fetchAll("
                        SELECT i.*, u.nome as usuario_nome, c.nome as cfc_nome 
                        FROM instrutores i 
                        LEFT JOIN usuarios u ON i.usuario_id = u.id 
                        LEFT JOIN cfcs c ON i.cfc_id = c.id 
                        ORDER BY i.nome ASC
                    ");
                    $cfcs = $db->fetchAll("SELECT id, nome FROM cfcs ORDER BY nome");
                    break;
                    
                case 'cfcs':
                    $cfcs = $db->fetchAll("SELECT * FROM cfcs ORDER BY nome");
                    break;
                    
                case 'usuarios':
                    $usuarios = $db->fetchAll("SELECT * FROM usuarios ORDER BY nome");
                    break;
                    
                case 'veiculos':
                    $veiculos = $db->fetchAll("
                        SELECT v.*, c.nome as cfc_nome 
                        FROM veiculos v 
                        LEFT JOIN cfcs c ON v.cfc_id = c.id 
                        ORDER BY v.placa ASC
                    ");
                    $cfcs = $db->fetchAll("SELECT id, nome FROM cfcs ORDER BY nome");
                    break;
                    
                case 'agendamento':
                case 'agendar-aula':
                    // Buscar dados necessários para agendamento
                    $aluno_id = $_GET['aluno_id'] ?? null;
                    if ($aluno_id) {
                        $aluno = $db->findWhere('alunos', 'id = ?', [$aluno_id], '*', null, 1);
                        if ($aluno && is_array($aluno)) {
                            $aluno = $aluno[0];
                            $cfc = $db->findWhere('cfcs', 'id = ?', [$aluno['cfc_id']], '*', null, 1);
                            $cfc = $cfc && is_array($cfc) ? $cfc[0] : null;
                            $instrutores = $db->fetchAll("SELECT id, nome FROM instrutores WHERE ativo = 1 ORDER BY nome");
                            $veiculos = $db->fetchAll("SELECT id, placa, modelo FROM veiculos WHERE ativo = 1 ORDER BY placa");
                            $aulas_existentes = $db->fetchAll("
                                SELECT a.*, i.nome as instrutor_nome, v.placa as veiculo_placa
                                FROM aulas a
                                LEFT JOIN instrutores i ON a.instrutor_id = i.id
                                LEFT JOIN veiculos v ON a.veiculo_id = v.id
                                WHERE a.aluno_id = ? AND a.data_aula >= CURDATE()
                                ORDER BY a.data_aula ASC, a.hora_inicio ASC
                            ", [$aluno_id]);
                        }
                    }
                    break;
                    
                case 'historico-aluno':
                    // Buscar dados do aluno para histórico
                    $aluno_id = $_GET['id'] ?? null;
                    if ($aluno_id) {
                        $aluno = $db->findWhere('alunos', 'id = ?', [$aluno_id], '*', null, 1);
                        if ($aluno && is_array($aluno)) {
                            $aluno = $aluno[0];
                            $cfc = $db->findWhere('cfcs', 'id = ?', [$aluno['cfc_id']], '*', null, 1);
                            $cfc = $cfc && is_array($cfc) ? $cfc[0] : null;
                        }
                    }
                    break;
                    
                case 'historico-instrutor':
                    // Buscar dados do instrutor para histórico
                    $instrutor_id = $_GET['id'] ?? null;
                    if ($instrutor_id) {
                        $instrutor = $db->findWhere('instrutores', 'id = ?', [$instrutor_id], '*', null, 1);
                        if ($instrutor && is_array($instrutor)) {
                            $instrutor = $instrutor[0];
                            $cfc = $db->findWhere('cfcs', 'id = ?', [$instrutor['cfc_id']], '*', null, 1);
                            $cfc = $cfc && is_array($cfc) ? $cfc[0] : null;
                        }
                    }
                    break;
                    
                default:
                    // Para o dashboard, não precisamos carregar dados específicos
                    break;
            }
            
            // Carregar conteúdo dinâmico baseado na página e ação
            $content_file = "pages/{$page}.php";
            if (file_exists($content_file)) {
                include $content_file;
            } else {
                // Página padrão - Dashboard
                include 'pages/dashboard.php';
            }
            ?>
        </main>
        
    </div>
    
    <!-- JavaScript -->
    <script>
        // Sistema de navegação responsiva
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle sidebar em dispositivos móveis
            const sidebarToggle = document.querySelector('.sidebar-toggle');
            const sidebar = document.querySelector('.admin-sidebar');
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('open');
                });
            }
            
            // Fechar sidebar ao clicar fora em dispositivos móveis
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 1024) {
                    if (!sidebar.contains(e.target) && !e.target.closest('.sidebar-toggle')) {
                        sidebar.classList.remove('open');
                    }
                }
            });
            
            // Animações de entrada
            const animateElements = document.querySelectorAll('.stat-card, .card, .chart-section');
            animateElements.forEach((element, index) => {
                element.style.animationDelay = `${index * 0.1}s`;
                element.classList.add('animate-fade-in');
            });
            
            // Tooltips
            const tooltipElements = document.querySelectorAll('[data-tooltip]');
            tooltipElements.forEach(element => {
                element.classList.add('tooltip');
            });
            
            // Estados de carregamento
            const loadingElements = document.querySelectorAll('.loading');
            loadingElements.forEach(element => {
                element.classList.add('loading-state');
            });
        });
        
        // Função para mostrar notificações
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type}`;
            notification.innerHTML = `
                <div class="alert-content">
                    <div class="d-flex items-center gap-3">
                        <div class="notification-icon ${type}">
                            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : type === 'danger' ? 'times-circle' : 'info-circle'}"></i>
                        </div>
                        <div>${message}</div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Remover após 5 segundos
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }
        
        // Função para confirmar ações
        function confirmAction(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }
        
        // Função para formatar números
        function formatNumber(number) {
            return new Intl.NumberFormat('pt-BR').format(number);
        }
        
        // Função para formatar datas
        function formatDate(date) {
            return new Intl.DateTimeFormat('pt-BR').format(new Date(date));
        }
        
        // Função para formatar moeda
        function formatCurrency(amount) {
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }).format(amount);
        }
        
        // Sistema de menus dropdown
        document.addEventListener('DOMContentLoaded', function() {
            // Controle dos menus dropdown
            const navToggles = document.querySelectorAll('.nav-toggle');
            
            navToggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const group = this.getAttribute('data-group');
                    const submenu = document.getElementById(group);
                    const arrow = this.querySelector('.nav-arrow i');
                    
                    // Fechar outros submenus
                    document.querySelectorAll('.nav-submenu').forEach(menu => {
                        if (menu.id !== group) {
                            menu.classList.remove('open');
                            const otherArrow = menu.previousElementSibling.querySelector('.nav-arrow i');
                            if (otherArrow) {
                                otherArrow.classList.remove('fa-chevron-up');
                                otherArrow.classList.add('fa-chevron-down');
                            }
                        }
                    });
                    
                    // Toggle do submenu atual
                    submenu.classList.toggle('open');
                    
                    // Rotacionar seta
                    if (submenu.classList.contains('open')) {
                        arrow.classList.remove('fa-chevron-down');
                        arrow.classList.add('fa-chevron-up');
                    } else {
                        arrow.classList.remove('fa-chevron-up');
                        arrow.classList.add('fa-chevron-down');
                    }
                });
            });
            
            // Abrir submenu da página ativa
            const activeSubmenu = document.querySelector('.nav-sublink.active');
            if (activeSubmenu) {
                const submenu = activeSubmenu.closest('.nav-submenu');
                if (submenu) {
                    submenu.classList.add('open');
                    const toggle = submenu.previousElementSibling;
                    const arrow = toggle.querySelector('.nav-arrow i');
                    if (arrow) {
                        arrow.classList.remove('fa-chevron-down');
                        arrow.classList.add('fa-chevron-up');
                    }
                }
            }
        });
    </script>
    
    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
