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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrativo - <?php echo APP_NAME; ?></title>
    
    <!-- CSS Principal -->
    <link href="assets/css/admin.css" rel="stylesheet">
    
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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
                <div class="nav-item">
                    <a href="index.php" class="nav-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>">
                        <div class="nav-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="nav-text">Dashboard</div>
                    </a>
                </div>
                
                <div class="nav-item">
                    <a href="index.php?page=usuarios&action=list" class="nav-link <?php echo $page === 'usuarios' ? 'active' : ''; ?>">
                        <div class="nav-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="nav-text">Usuários</div>
                    </a>
                </div>
                
                <div class="nav-item">
                    <a href="index.php?page=cfcs&action=list" class="nav-link <?php echo $page === 'cfcs' ? 'active' : ''; ?>">
                        <div class="nav-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="nav-text">CFCs</div>
                    </a>
                </div>
                
                <div class="nav-item">
                    <a href="index.php?page=alunos&action=list" class="nav-link <?php echo $page === 'alunos' ? 'active' : ''; ?>">
                        <div class="nav-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="nav-text">Alunos</div>
                        <div class="nav-badge"><?php echo $stats['total_alunos']; ?></div>
                    </a>
                </div>
                
                <div class="nav-item">
                    <a href="index.php?page=instrutores&action=list" class="nav-link <?php echo $page === 'instrutores' ? 'active' : ''; ?>">
                        <div class="nav-icon">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="nav-text">Instrutores</div>
                        <div class="nav-badge"><?php echo $stats['total_instrutores']; ?></div>
                    </a>
                </div>
                
                <div class="nav-item">
                    <a href="index.php?page=aulas&action=list" class="nav-link <?php echo $page === 'aulas' ? 'active' : ''; ?>">
                        <div class="nav-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="nav-text">Aulas</div>
                        <div class="nav-badge"><?php echo $stats['total_aulas']; ?></div>
                    </a>
                </div>
                
                <div class="nav-item">
                    <a href="index.php?page=veiculos&action=list" class="nav-link <?php echo $page === 'veiculos' ? 'active' : ''; ?>">
                        <div class="nav-icon">
                            <i class="fas fa-car"></i>
                        </div>
                        <div class="nav-text">Veículos</div>
                        <div class="nav-badge"><?php echo $stats['total_veiculos']; ?></div>
                    </a>
                </div>
                
                <div class="nav-item">
                    <a href="index.php?page=relatorios&action=alunos" class="nav-link <?php echo $page === 'relatorios' ? 'active' : ''; ?>">
                        <div class="nav-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div class="nav-text">Relatórios</div>
                    </a>
                </div>
                
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
    </script>
</body>
</html>
