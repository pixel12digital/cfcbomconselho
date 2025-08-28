<?php
// Definir caminho base
$base_path = dirname(__DIR__);

// Forçar charset UTF-8 para evitar problemas de codificação
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}

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
try {
    $stats = [
        'total_alunos' => $db->count('alunos'),
        'total_instrutores' => $db->count('instrutores'),
        'total_aulas' => $db->count('aulas'),
        'total_veiculos' => $db->count('veiculos'),
        'aulas_hoje' => $db->count('aulas', 'data_aula = ?', [date('Y-m-d')]),
        'aulas_semana' => $db->count('aulas', 'data_aula >= ?', [date('Y-m-d', strtotime('monday this week'))])
    ];
} catch (Exception $e) {
    $stats = [
        'total_alunos' => 0,
        'total_instrutores' => 0,
        'total_aulas' => 0,
        'total_veiculos' => 0,
        'aulas_hoje' => 0,
        'aulas_semana' => 0
    ];
}

// Obter últimas atividades
try {
    $ultimas_atividades = $db->fetchAll("
        (SELECT 'aluno' as tipo, nome, 'cadastrado' as acao, criado_em as data
        FROM alunos 
        ORDER BY criado_em DESC 
        LIMIT 5)
        UNION ALL
        (SELECT 'instrutor' as tipo, u.nome, 'cadastrado' as acao, i.criado_em as data
        FROM instrutores i
        JOIN usuarios u ON i.usuario_id = u.id
        ORDER BY i.criado_em DESC 
        LIMIT 5)
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
    
    <!-- CSS Inline para Garantir Funcionamento em Produção -->
    <style>
        /* Estilos críticos para o menu dropdown */
        .nav-group { position: relative; }
        .nav-toggle { cursor: pointer; user-select: none; position: relative; }
        .nav-toggle:hover { background-color: rgba(255, 255, 255, 0.15) !important; }
        
        .nav-arrow { margin-left: auto; transition: transform 0.3s ease; }
        .nav-arrow i { font-size: 12px; opacity: 0.8; }
        
        .nav-submenu {
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
            opacity: 0;
            background-color: rgba(0, 0, 0, 0.1);
            margin: 0 1rem;
            border-radius: 8px;
            display: none;
            transform: translateY(-10px);
        }
        
        .nav-submenu.open {
            max-height: 500px;
            opacity: 1;
            display: block !important;
            transform: translateY(0);
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
    
    <!-- Font Awesome para ícones -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
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
                        <a href="index.php?page=agendar-aula&action=list" class="nav-sublink <?php echo $page === 'agendar-aula' ? 'active' : ''; ?>">
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
            // Inicializar variáveis padrão
            $alunos = [];
            $instrutores = [];
            $cfcs = [];
            $usuarios = [];
            $veiculos = [];
            
            // Carregar dados necessários baseado na página
            switch ($page) {
                case 'alunos':
                    try {
                        $alunos = $db->fetchAll("
                            SELECT a.id, a.nome, a.cpf, a.rg, a.data_nascimento, a.endereco, a.telefone, a.email, a.cfc_id, a.categoria_cnh, a.status, a.criado_em,
                                   c.nome as cfc_nome,
                                   NULL as ultima_aula
                            FROM alunos a 
                            LEFT JOIN cfcs c ON a.cfc_id = c.id 
                            ORDER BY a.nome ASC
                        ");
                    } catch (Exception $e) {
                        // Query mais simples como fallback
                        try {
                            $alunos = $db->fetchAll("SELECT * FROM alunos ORDER BY nome ASC");
                        } catch (Exception $e2) {
                            $alunos = [];
                        }
                    }
                    try {
                        $cfcs = $db->fetchAll("SELECT id, nome, ativo FROM cfcs WHERE ativo = 1 ORDER BY nome");
                    } catch (Exception $e) {
                        $cfcs = [];
                    }
                    break;
                    
                case 'instrutores':
                    try {
                        // Query mais simples primeiro para testar
                        $instrutores = $db->fetchAll("
                            SELECT i.id, i.usuario_id, i.cfc_id, i.credencial, i.categoria_habilitacao, i.ativo, i.criado_em,
                                   u.nome, u.email, c.nome as cfc_nome,
                                   0 as total_aulas, 0 as aulas_hoje, 1 as disponivel
                            FROM instrutores i 
                            LEFT JOIN usuarios u ON i.usuario_id = u.id 
                            LEFT JOIN cfcs c ON i.cfc_id = c.id 
                            ORDER BY u.nome ASC
                        ");
                    } catch (Exception $e) {
                        // Se ainda houver erro, usar query básica
                        try {
                            $instrutores = $db->fetchAll("SELECT * FROM instrutores ORDER BY id ASC");
                        } catch (Exception $e2) {
                            $instrutores = [];
                        }
                    }
                    try {
                        $cfcs = $db->fetchAll("SELECT id, nome, ativo FROM cfcs WHERE ativo = 1 ORDER BY nome");
                    } catch (Exception $e) {
                        $cfcs = [];
                    }
                    try {
                        $usuarios = $db->fetchAll("SELECT * FROM usuarios WHERE tipo IN ('instrutor', 'admin') ORDER BY nome");
                    } catch (Exception $e) {
                        $usuarios = [];
                    }
                    break;
                    
                case 'cfcs':
                    try {
                        $cfcs = $db->fetchAll("
                            SELECT c.id, c.nome, c.cnpj, c.endereco, c.bairro, c.cidade, c.uf, c.cep, c.telefone, c.email, c.responsavel_id, c.ativo, c.criado_em,
                                   u.nome as responsavel_nome,
                                   0 as total_alunos
                            FROM cfcs c 
                            LEFT JOIN usuarios u ON c.responsavel_id = u.id 
                            ORDER BY c.nome
                        ");
                    } catch (Exception $e) {
                        // Query mais simples como fallback
                        try {
                            $cfcs = $db->fetchAll("SELECT * FROM cfcs ORDER BY nome");
                        } catch (Exception $e2) {
                            $cfcs = [];
                        }
                    }
                    break;
                    
                case 'usuarios':
                    try {
                        $usuarios = $db->fetchAll("SELECT id, nome, email, tipo, cpf, telefone, ativo, criado_em FROM usuarios ORDER BY nome");
                    } catch (Exception $e) {
                        // Query mais simples como fallback
                        try {
                            $usuarios = $db->fetchAll("SELECT * FROM usuarios ORDER BY nome");
                        } catch (Exception $e2) {
                            $usuarios = [];
                        }
                    }
                    break;
                    
                case 'veiculos':
                    try {
                        $veiculos = $db->fetchAll("
                            SELECT v.id, v.cfc_id, v.placa, v.modelo, v.marca, v.ano, v.categoria_cnh, v.ativo, v.criado_em,
                                   c.nome as cfc_nome 
                            FROM veiculos v 
                            LEFT JOIN cfcs c ON v.cfc_id = c.id 
                            ORDER BY v.placa ASC
                        ");
                    } catch (Exception $e) {
                        // Query mais simples como fallback
                        try {
                            $veiculos = $db->fetchAll("SELECT * FROM veiculos ORDER BY placa ASC");
                        } catch (Exception $e2) {
                            $veiculos = [];
                        }
                    }
                    try {
                        $cfcs = $db->fetchAll("SELECT id, nome, ativo FROM cfcs WHERE ativo = 1 ORDER BY nome");
                    } catch (Exception $e) {
                        $cfcs = [];
                    }
                    break;
                    
                case 'agendamento':
                case 'agendar-aula':
                    // Buscar dados necessários para agendamento
                    $aluno_id = $_GET['aluno_id'] ?? null;
                    $aluno = null;
                    $cfc = null;
                    $instrutores = [];
                    $veiculos = [];
                    $aulas_existentes = [];
                    
                    if ($aluno_id) {
                        try {
                            $aluno = $db->findWhere('alunos', 'id = ?', [$aluno_id], '*', null, 1);
                            if ($aluno && is_array($aluno)) {
                                $aluno = $aluno[0];
                                try {
                                    $cfc = $db->findWhere('cfcs', 'id = ?', [$aluno['cfc_id']], '*', null, 1);
                                    $cfc = $cfc && is_array($cfc) ? $cfc[0] : null;
                                } catch (Exception $e) {
                                    $cfc = null;
                                }
                            }
                            try {
                                $instrutores = $db->fetchAll("SELECT id, nome FROM instrutores WHERE ativo = 1 ORDER BY nome");
                            } catch (Exception $e) {
                                $instrutores = [];
                            }
                            try {
                                $veiculos = $db->fetchAll("SELECT id, placa, modelo FROM veiculos WHERE ativo = 1 ORDER BY placa");
                            } catch (Exception $e) {
                                $veiculos = [];
                            }
                            try {
                                $aulas_existentes = $db->fetchAll("
                                    SELECT a.*, i.nome as instrutor_nome, v.placa as veiculo_placa
                                    FROM aulas a
                                    LEFT JOIN instrutores i ON a.instrutor_id = i.id
                                    LEFT JOIN veiculos v ON a.veiculo_id = v.id
                                    WHERE a.aluno_id = ? AND a.data_aula >= ?
                                    ORDER BY a.data_aula ASC, a.hora_inicio ASC
                                ", [$aluno_id, date('Y-m-d')]);
                            } catch (Exception $e) {
                                $aulas_existentes = [];
                            }
                        } catch (Exception $e) {
                            $aluno = null;
                            $cfc = null;
                            $instrutores = [];
                            $veiculos = [];
                            $aulas_existentes = [];
                        }
                    }
                    break;
                    
                case 'historico-aluno':
                    // Buscar dados do aluno para histórico
                    $aluno_id = $_GET['id'] ?? null;
                    $aluno = null;
                    $cfc = null;
                    
                    if ($aluno_id) {
                        try {
                            $aluno = $db->findWhere('alunos', 'id = ?', [$aluno_id], '*', null, 1);
                            if ($aluno && is_array($aluno)) {
                                $aluno = $aluno[0];
                                try {
                                    $cfc = $db->findWhere('cfcs', 'id = ?', [$aluno['cfc_id']], '*', null, 1);
                                    $cfc = $cfc && is_array($cfc) ? $cfc[0] : null;
                                } catch (Exception $e) {
                                    $cfc = null;
                                }
                            } else {
                                $aluno = null;
                                $cfc = null;
                            }
                        } catch (Exception $e) {
                            $aluno = null;
                            $cfc = null;
                        }
                    }
                    break;
                    
                case 'historico-instrutor':
                    // Buscar dados do instrutor para histórico
                    $instrutor_id = $_GET['id'] ?? null;
                    $instrutor = null;
                    $cfc = null;
                    
                    if ($instrutor_id) {
                        try {
                            $instrutor = $db->findWhere('instrutores', 'id = ?', [$instrutor_id], '*', null, 1);
                            if ($instrutor && is_array($instrutor)) {
                                $instrutor = $instrutor[0];
                                try {
                                    $cfc = $db->findWhere('cfcs', 'id = ?', [$instrutor['cfc_id']], '*', null, 1);
                                    $cfc = $cfc && is_array($cfc) ? $cfc[0] : null;
                                } catch (Exception $e) {
                                    $cfc = null;
                                }
                            } else {
                                $instrutor = null;
                                $cfc = null;
                            }
                        } catch (Exception $e) {
                            $instrutor = null;
                            $cfc = null;
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
        
        // Sistema de menus dropdown otimizado para produção
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Iniciando sistema de menus dropdown...');
            
            // Controle dos menus dropdown
            const navToggles = document.querySelectorAll('.nav-toggle');
            console.log(`📋 Encontrados ${navToggles.length} toggles de menu`);
            
            if (navToggles.length === 0) {
                console.warn('⚠️ Nenhum toggle de menu encontrado. Verificando estrutura...');
                // Fallback: tentar encontrar elementos por classe
                const fallbackToggles = document.querySelectorAll('[data-group]');
                if (fallbackToggles.length > 0) {
                    console.log(`🔄 Usando fallback: ${fallbackToggles.length} elementos encontrados`);
                    fallbackToggles.forEach(toggle => {
                        toggle.classList.add('nav-toggle');
                        toggle.style.cursor = 'pointer';
                    });
                }
            }
            
            // Função para alternar submenu
            function toggleSubmenu(toggleElement) {
                const group = toggleElement.getAttribute('data-group');
                const submenu = document.getElementById(group);
                const arrow = toggleElement.querySelector('.nav-arrow i, .fa-chevron-down, .fa-chevron-up');
                
                if (!submenu) {
                    console.error(`❌ Submenu não encontrado para o grupo: ${group}`);
                    return;
                }
                
                console.log(`🔄 Alternando submenu: ${group}`);
                
                // Fechar outros submenus (comportamento accordion)
                document.querySelectorAll('.nav-submenu').forEach(menu => {
                    if (menu.id !== group) {
                        menu.classList.remove('open');
                        const otherToggle = menu.previousElementSibling;
                        if (otherToggle) {
                            const otherArrow = otherToggle.querySelector('.nav-arrow i, .fa-chevron-down, .fa-chevron-up');
                            if (otherArrow) {
                                otherArrow.classList.remove('fa-chevron-up');
                                otherArrow.classList.add('fa-chevron-down');
                                otherArrow.style.transform = 'rotate(0deg)';
                            }
                        }
                    }
                });
                
                // Toggle do submenu atual
                const isOpen = submenu.classList.contains('open');
                submenu.classList.toggle('open');
                
                // Aplicar estilos baseado no estado
                if (submenu.classList.contains('open')) {
                    submenu.style.display = 'block';
                    submenu.style.visibility = 'visible';
                    submenu.style.opacity = '1';
                    submenu.style.maxHeight = '500px';
                    submenu.style.overflow = 'visible';
                } else {
                    submenu.style.display = 'none';
                    submenu.style.visibility = 'hidden';
                    submenu.style.opacity = '0';
                    submenu.style.maxHeight = '0';
                    submenu.style.overflow = 'hidden';
                }
                
                // Rotacionar seta com animação suave
                if (arrow) {
                    if (submenu.classList.contains('open')) {
                        arrow.classList.remove('fa-chevron-down');
                        arrow.classList.add('fa-chevron-up');
                        arrow.style.transform = 'rotate(180deg)';
                        arrow.style.transition = 'transform 0.3s ease';
                    } else {
                        arrow.classList.remove('fa-chevron-up');
                        arrow.classList.add('fa-chevron-down');
                        arrow.style.transform = 'rotate(0deg)';
                        arrow.style.transition = 'transform 0.3s ease';
                    }
                }
                
                console.log(`✅ Submenu ${group} ${isOpen ? 'fechado' : 'aberto'}`);
            }
            
            // Adicionar event listeners
            navToggles.forEach((toggle, index) => {
                console.log(`🔗 Adicionando listener para toggle ${index + 1}: ${toggle.getAttribute('data-group')}`);
                
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log(`🖱️ Clique no toggle: ${this.getAttribute('data-group')}`);
                    toggleSubmenu(this);
                });
                
                // Adicionar listener de teclado para acessibilidade
                toggle.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        console.log(`⌨️ Tecla pressionada no toggle: ${this.getAttribute('data-group')}`);
                        toggleSubmenu(this);
                    }
                });
                
                // Adicionar hover effect para melhor UX
                toggle.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
                });
                
                toggle.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = '';
                });
            });
            
            // Abrir submenu da página ativa automaticamente (se houver)
            setTimeout(() => {
                const activeSubmenu = document.querySelector('.nav-sublink.active');
                if (activeSubmenu) {
                    const submenu = activeSubmenu.closest('.nav-submenu');
                    if (submenu) {
                        console.log(`🎯 Abrindo submenu da página ativa: ${submenu.id}`);
                        submenu.classList.add('open');
                        submenu.style.display = 'block';
                        submenu.style.visibility = 'visible';
                        submenu.style.opacity = '1';
                        submenu.style.maxHeight = '500px';
                        submenu.style.overflow = 'visible';
                        
                        const toggle = submenu.previousElementSibling;
                        if (toggle) {
                            const arrow = toggle.querySelector('.nav-arrow i, .fa-chevron-down, .fa-chevron-up');
                            if (arrow) {
                                arrow.classList.remove('fa-chevron-down');
                                arrow.classList.add('fa-chevron-up');
                                arrow.style.transform = 'rotate(180deg)';
                            }
                        }
                    }
                }
            }, 100);
            
            // Garantir que todos os submenus comecem fechados por padrão
            document.querySelectorAll('.nav-submenu').forEach(submenu => {
                submenu.classList.remove('open');
                submenu.style.display = 'none';
                submenu.style.visibility = 'hidden';
                submenu.style.opacity = '0';
                submenu.style.maxHeight = '0';
                submenu.style.overflow = 'hidden';
            });
            
            // Garantir que todas as setas comecem apontando para baixo
            document.querySelectorAll('.nav-arrow i').forEach(arrow => {
                arrow.classList.remove('fa-chevron-up');
                arrow.classList.add('fa-chevron-down');
                arrow.style.transform = 'rotate(0deg)';
            });
            
            console.log('✅ Sistema de menus dropdown inicializado com sucesso!');
            console.log('📋 Menu configurado para funcionar como accordion - apenas um grupo aberto por vez');
        });
    </script>
    
    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- JavaScript Principal do Admin -->
    <script src="assets/js/config.js"></script>
    <script src="assets/js/admin.js"></script>
    <script src="assets/js/components.js"></script>
    
    <!-- JavaScript das Funcionalidades Específicas -->
    <script src="assets/js/cfcs.js"></script>
    <script src="assets/js/instrutores.js"></script>
    <script src="assets/js/instrutores-page.js"></script>
    <script src="assets/js/alunos.js"></script>
</body>
</html>
