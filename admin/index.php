<?php
// Definir caminho base
$base_path = dirname(__DIR__);
if (!file_exists($base_path . '/includes/config.php')) {
    // Se não encontrar, usar o diretório atual
    $base_path = getcwd();
}
require_once $base_path . '/includes/config.php';
require_once $base_path . '/includes/database.php';
require_once $base_path . '/includes/auth.php';

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
    'total_alunos' => $db->dbCount('alunos'),
    'total_instrutores' => $db->dbCount('instrutores'),
    'total_aulas' => $db->dbCount('aulas'),
    'total_veiculos' => $db->dbCount('veiculos'),
    'aulas_hoje' => $db->dbCount('aulas', ['data' => date('Y-m-d')]),
    'aulas_semana' => $db->dbCount('aulas', ['data >=' => date('Y-m-d', strtotime('monday this week'))])
];

// Obter últimas atividades
$ultimas_atividades = $db->query("
    SELECT 'aluno' as tipo, nome, 'cadastrado' as acao, created_at as data
    FROM alunos 
    ORDER BY created_at DESC 
    LIMIT 5
    UNION ALL
    SELECT 'instrutor' as tipo, nome, 'cadastrado' as acao, created_at as data
    FROM instrutores 
    ORDER BY created_at DESC 
    LIMIT 5
    ORDER BY data DESC 
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? 'list';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrativo - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-car me-2"></i>
                <?php echo APP_NAME; ?> - Admin
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>" href="index.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-users me-1"></i>Usuários
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="index.php?page=usuarios&action=list">Listar Usuários</a></li>
                            <li><a class="dropdown-item" href="index.php?page=usuarios&action=create">Novo Usuário</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-building me-1"></i>CFCs
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="index.php?page=cfcs&action=list">Listar CFCs</a></li>
                            <li><a class="dropdown-item" href="index.php?page=cfcs&action=create">Novo CFC</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-graduate me-1"></i>Alunos
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="index.php?page=alunos&action=list">Listar Alunos</a></li>
                            <li><a class="dropdown-item" href="index.php?page=alunos&action=create">Novo Aluno</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-chalkboard-teacher me-1"></i>Instrutores
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="index.php?page=instrutores&action=list">Listar Instrutores</a></li>
                            <li><a class="dropdown-item" href="index.php?page=instrutores&action=create">Novo Instrutor</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-calendar-alt me-1"></i>Aulas
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="index.php?page=aulas&action=list">Listar Aulas</a></li>
                            <li><a class="dropdown-item" href="index.php?page=aulas&action=create">Nova Aula</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-car me-1"></i>Veículos
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="index.php?page=veiculos&action=list">Listar Veículos</a></li>
                            <li><a class="dropdown-item" href="index.php?page=veiculos&action=create">Novo Veículo</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-chart-bar me-1"></i>Relatórios
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="index.php?page=relatorios&action=alunos">Relatório de Alunos</a></li>
                            <li><a class="dropdown-item" href="index.php?page=relatorios&action=aulas">Relatório de Aulas</a></li>
                            <li><a class="dropdown-item" href="index.php?page=relatorios&action=financeiro">Relatório Financeiro</a></li>
                        </ul>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($user['nome']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="index.php?page=perfil&action=edit">
                                <i class="fas fa-user-edit me-1"></i>Editar Perfil
                            </a></li>
                            <li><a class="dropdown-item" href="index.php?page=configuracoes&action=edit">
                                <i class="fas fa-cog me-1"></i>Configurações
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i>Sair
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid mt-5 pt-3">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>" href="index.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page === 'usuarios' ? 'active' : ''; ?>" href="index.php?page=usuarios&action=list">
                                <i class="fas fa-users me-2"></i>Usuários
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page === 'cfcs' ? 'active' : ''; ?>" href="index.php?page=cfcs&action=list">
                                <i class="fas fa-building me-2"></i>CFCs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page === 'alunos' ? 'active' : ''; ?>" href="index.php?page=alunos&action=list">
                                <i class="fas fa-user-graduate me-2"></i>Alunos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page === 'instrutores' ? 'active' : ''; ?>" href="index.php?page=instrutores&action=list">
                                <i class="fas fa-chalkboard-teacher me-2"></i>Instrutores
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page === 'aulas' ? 'active' : ''; ?>" href="index.php?page=aulas&action=list">
                                <i class="fas fa-calendar-alt me-2"></i>Aulas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page === 'veiculos' ? 'active' : ''; ?>" href="index.php?page=veiculos&action=list">
                                <i class="fas fa-car me-2"></i>Veículos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $page === 'relatorios' ? 'active' : ''; ?>" href="index.php?page=relatorios&action=alunos">
                                <i class="fas fa-chart-bar me-2"></i>Relatórios
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content Area -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
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
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/admin.js"></script>
</body>
</html>
