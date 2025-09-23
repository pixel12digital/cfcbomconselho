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

// Verificar se o usuário está logado e tem permissão de admin ou instrutor
if (!isLoggedIn() || (!hasPermission('admin') && !hasPermission('instrutor'))) {
    header('Location: ../index.php');
    exit;
}

// Obter dados do usuário logado
$user = getCurrentUser();
$userType = $user['tipo'] ?? 'admin';
$userId = $user['id'] ?? null;
$isAdmin = hasPermission('admin');
$isInstrutor = hasPermission('instrutor');
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

// Processamento de formulários POST - DEVE VIR ANTES DE QUALQUER SAÍDA HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'veiculos') {
    // Processar formulário de veículos diretamente
    try {
        $acao = $_POST['acao'] ?? '';
        
        if ($acao === 'criar') {
            // Criar novo veículo
            $dados = [
                'cfc_id' => $_POST['cfc_id'] ?? null,
                'placa' => $_POST['placa'] ?? '',
                'marca' => $_POST['marca'] ?? '',
                'modelo' => $_POST['modelo'] ?? '',
                'ano' => $_POST['ano'] ?? null,
                'categoria_cnh' => $_POST['categoria_cnh'] ?? '',
                'cor' => $_POST['cor'] ?? null,
                'cod_seg_crv' => $_POST['cod_seg_crv'] ?? null,
                'chassi' => $_POST['chassi'] ?? null,
                'renavam' => $_POST['renavam'] ?? null,
                'combustivel' => $_POST['combustivel'] ?? null,
                'quilometragem' => $_POST['quilometragem'] ?? 0,
                'km_manutencao' => $_POST['km_manutencao'] ?? null,
                'data_aquisicao' => $_POST['data_aquisicao'] ?? null,
                'valor_aquisicao' => $_POST['valor_aquisicao'] ? str_replace(',', '.', str_replace('.', '', $_POST['valor_aquisicao'])) : null,
                'proxima_manutencao' => $_POST['proxima_manutencao'] ?? null,
                'disponivel' => $_POST['disponivel'] ?? 1,
                'observacoes' => $_POST['observacoes'] ?? null,
                'status' => $_POST['status'] ?? 'ativo',
                'ativo' => 1,
                'criado_em' => date('Y-m-d H:i:s')
            ];
            
            // Validar campos obrigatórios
            if (empty($dados['placa']) || empty($dados['marca']) || empty($dados['modelo']) || empty($dados['cfc_id'])) {
                throw new Exception('Placa, marca, modelo e CFC são obrigatórios');
            }
            
            // Verificar se a placa já existe
            $placaExistente = $db->fetch("SELECT id FROM veiculos WHERE placa = ?", [$dados['placa']]);
            if ($placaExistente) {
                throw new Exception('Placa já cadastrada no sistema');
            }
            
            $id = $db->insert('veiculos', $dados);
            
            if ($id) {
                header('Location: index.php?page=veiculos&msg=success&msg_text=' . urlencode('Veículo cadastrado com sucesso!'));
                exit;
            } else {
                throw new Exception('Erro ao cadastrar veículo');
            }
            
        } elseif ($acao === 'editar') {
            // Editar veículo existente
            $veiculo_id = $_POST['veiculo_id'] ?? 0;
            
            if (!$veiculo_id) {
                throw new Exception('ID do veículo não informado');
            }
            
            $dados = [
                'cfc_id' => $_POST['cfc_id'] ?? null,
                'placa' => $_POST['placa'] ?? '',
                'marca' => $_POST['marca'] ?? '',
                'modelo' => $_POST['modelo'] ?? '',
                'ano' => $_POST['ano'] ?? null,
                'categoria_cnh' => $_POST['categoria_cnh'] ?? '',
                'cor' => $_POST['cor'] ?? null,
                'cod_seg_crv' => $_POST['cod_seg_crv'] ?? null,
                'chassi' => $_POST['chassi'] ?? null,
                'renavam' => $_POST['renavam'] ?? null,
                'combustivel' => $_POST['combustivel'] ?? null,
                'quilometragem' => $_POST['quilometragem'] ?? 0,
                'km_manutencao' => $_POST['km_manutencao'] ?? null,
                'data_aquisicao' => $_POST['data_aquisicao'] ?? null,
                'valor_aquisicao' => $_POST['valor_aquisicao'] ? str_replace(',', '.', str_replace('.', '', $_POST['valor_aquisicao'])) : null,
                'proxima_manutencao' => $_POST['proxima_manutencao'] ?? null,
                'disponivel' => $_POST['disponivel'] ?? 1,
                'observacoes' => $_POST['observacoes'] ?? null,
                'status' => $_POST['status'] ?? 'ativo',
                'atualizado_em' => date('Y-m-d H:i:s')
            ];
            
            // Validar campos obrigatórios
            if (empty($dados['placa']) || empty($dados['marca']) || empty($dados['modelo']) || empty($dados['cfc_id'])) {
                throw new Exception('Placa, marca, modelo e CFC são obrigatórios');
            }
            
            // Verificar se a placa já existe em outro veículo
            $placaExistente = $db->fetch("SELECT id FROM veiculos WHERE placa = ? AND id != ?", [$dados['placa'], $veiculo_id]);
            if ($placaExistente) {
                throw new Exception('Placa já cadastrada em outro veículo');
            }
            
            $resultado = $db->update('veiculos', $dados, 'id = ?', [$veiculo_id]);
            
            if ($resultado) {
                header('Location: index.php?page=veiculos&msg=success&msg_text=' . urlencode('Veículo atualizado com sucesso!'));
                exit;
            } else {
                throw new Exception('Erro ao atualizar veículo');
            }
        }
        
    } catch (Exception $e) {
        header('Location: index.php?page=veiculos&msg=danger&msg_text=' . urlencode('Erro: ' . $e->getMessage()));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- CSP configurado para permitir fontes base64 e Font Awesome -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.google.com https://www.gstatic.com https://cdn.jsdelivr.net https://kit.fontawesome.com https://unpkg.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://kit.fontawesome.com; font-src 'self' data: blob: https://fonts.gstatic.com https://cdnjs.cloudflare.com https://kit.fontawesome.com https://cdn.jsdelivr.net; img-src 'self' data: https:; connect-src 'self' https://viacep.com.br https://cdn.jsdelivr.net https://unpkg.com; object-src 'none'; base-uri 'self';">
    <title>Dashboard Administrativo - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- CSS Principal -->
    <link href="assets/css/admin.css" rel="stylesheet">
    <link href="assets/css/modal-veiculos.css" rel="stylesheet">
    
    <!-- CSS dos Botões de Ação -->
    <link href="assets/css/action-buttons.css" rel="stylesheet">
    
    <!-- CSS do Menu com Ícones -->
    <link href="assets/css/sidebar-icons.css" rel="stylesheet">
    
    <!-- CSS Final do Menu -->
    <link href="assets/css/menu-flyout.css" rel="stylesheet">
    
    <!-- CSS de Correções para Sidebar -->
    <link href="assets/css/sidebar-fixes.css" rel="stylesheet">
    
    <!-- CSS da Topbar Unificada -->
    <link href="assets/css/topbar-unified.css" rel="stylesheet">
    
    <!-- CSS de Correções Críticas de Layout -->
    <link href="assets/css/layout-fixes.css" rel="stylesheet">
    
    <!-- CSS Adicional para Garantir Apenas Ícones -->
    <style>
        /* Garantir que ícones sejam visíveis */
        .admin-sidebar .nav-icon {
            display: flex !important;
            opacity: 1 !important;
            visibility: visible !important;
            color: #ecf0f1 !important;
            font-size: 18px !important;
            width: 24px !important;
            height: 24px !important;
            align-items: center !important;
            justify-content: center !important;
        }
        
        /* Ocultar textos mas manter ícones */
        .admin-sidebar .nav-text,
        .admin-sidebar .nav-badge,
        .admin-sidebar .nav-arrow {
            display: none !important;
            opacity: 0 !important;
            visibility: hidden !important;
        }
        
        /* Garantir que apenas ícones sejam visíveis */
        .admin-sidebar .nav-link,
        .admin-sidebar .nav-toggle {
            justify-content: center !important;
            align-items: center !important;
            padding: 12px !important;
        }
        
        /* Garantir que elementos de texto não apareçam */
        .admin-sidebar .nav-link > span:not(.nav-icon),
        .admin-sidebar .nav-toggle > span:not(.nav-icon) {
            display: none !important;
        }
        
        /* Garantir que flyouts apareçam ao lado */
        .admin-sidebar .nav-flyout {
            position: fixed !important;
            z-index: 1000 !important;
            background-color: #2c3e50 !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
            min-width: 200px !important;
            max-width: 250px !important;
            padding: 0 !important;
        }
        
        /* CORREÇÕES ESPECÍFICAS PARA TOPBAR - REMOVIDAS - AGORA NO CSS UNIFICADO */
        
        /* Garantir que flyouts mostrem apenas texto */
        .admin-sidebar .nav-flyout .flyout-title {
            color: #ecf0f1 !important;
            font-size: 16px !important;
            font-weight: 700 !important;
            padding: 12px 16px !important;
            background-color: rgba(255, 255, 255, 0.05) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2) !important;
            border-radius: 8px 8px 0 0 !important;
        }
        
        
        .admin-sidebar .nav-flyout .flyout-item {
            display: block !important;
            padding: 12px 16px !important;
            color: #ecf0f1 !important;
            text-decoration: none !important;
            font-size: 14px !important;
            font-weight: 500 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
        }
        
        .admin-sidebar .nav-flyout .flyout-item:hover {
            background-color: rgba(255, 255, 255, 0.1) !important;
            color: #ffffff !important;
        }
        
        .admin-sidebar .nav-flyout .flyout-item:last-child {
            border-bottom: none !important;
            border-radius: 0 0 8px 8px !important;
        }
        
        /* Ocultar ícones dos flyouts */
        .admin-sidebar .nav-flyout .flyout-icon {
            display: none !important;
        }
        
        /* Garantir que sidebar nunca expanda */
        .admin-sidebar {
            width: 70px !important;
            transition: none !important;
        }
        
        .admin-sidebar:hover {
            width: 70px !important;
        }
    </style>
    
    <!-- CSS Inline para Garantir Funcionamento em Produção -->
    <style>
        /* Estilos de expansão interna removidos - usando menu-flyout.css */
    </style>
    
    <!-- Font Awesome para ícones -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/logo.png">
</head>
<body>
    <!-- Container Principal -->
    <div class="admin-container">
        
        <!-- Topbar Completa - STICKY/FIXED -->
        <div class="topbar" id="main-topbar">
            <!-- Logo -->
            <a href="?page=dashboard" class="topbar-logo">
                <div class="topbar-logo-icon">
                    <i class="fas fa-car"></i>
                </div>
                <div class="topbar-logo-text">CFC Bom Conselho</div>
            </a>
            
            <!-- Busca Global -->
            <div class="topbar-search">
                <div class="search-input-wrapper">
                    <input 
                        type="text" 
                        class="search-input" 
                        placeholder="Pesquisar por nome, CPF, matrícula, telefone..."
                        autocomplete="off"
                        aria-label="Busca global"
                    >
                    <i class="fas fa-search search-icon"></i>
                    <div class="search-results" id="search-results" role="listbox" aria-label="Resultados da pesquisa"></div>
                </div>
            </div>
            
            <!-- Notificações e Perfil (Direita) -->
            <div class="topbar-right">
                <!-- Notificações -->
                <div class="topbar-notifications">
                    <button class="notification-icon" aria-label="Notificações">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge hidden" id="notification-badge">0</span>
                    </button>
                    <div class="notification-dropdown" id="notification-dropdown">
                        <div class="notification-header">
                            <h3 class="notification-title">Notificações</h3>
                        </div>
                        <div class="notification-list" id="notification-list">
                            <div class="search-loading">Carregando notificações...</div>
                        </div>
                        <div class="notification-footer">
                            <div class="notification-actions">
                                <button class="notification-btn" id="mark-all-read">Marcar todas como lidas</button>
                                <a href="?page=notifications" class="notification-btn">Ver todas</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Perfil do Usuário -->
                <div class="topbar-profile">
                    <button class="profile-button" id="profile-button" aria-label="Perfil do usuário">
                        <div class="profile-avatar" id="profile-avatar"><?php echo strtoupper(substr($user['nome'], 0, 1)); ?></div>
                        <div class="profile-info">
                            <div class="profile-name" id="profile-name"><?php echo htmlspecialchars($user['nome']); ?></div>
                            <div class="profile-role" id="profile-role">Administrador</div>
                        </div>
                        <i class="fas fa-chevron-down profile-dropdown-icon"></i>
                    </button>
                    <div class="profile-dropdown" id="profile-dropdown">
                        <a href="?page=profile" class="profile-dropdown-item">
                            <i class="fas fa-user profile-dropdown-icon-item"></i>
                            Meu Perfil
                        </a>
                        <a href="?page=change-password" class="profile-dropdown-item">
                            <i class="fas fa-key profile-dropdown-icon-item"></i>
                            Trocar senha
                        </a>
                        <a href="logout.php" class="profile-dropdown-item logout">
                            <i class="fas fa-sign-out-alt profile-dropdown-icon-item"></i>
                            Sair
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar de Navegação -->
        <nav class="admin-sidebar">
            <div class="sidebar-header">
                <div class="sidebar-title">Navegação</div>
                <div class="sidebar-subtitle">Sistema CFC</div>
            </div>
            
            <div class="nav-menu">
                <!-- Dashboard -->
                <div class="nav-item">
                    <a href="index.php" class="nav-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>" title="Dashboard">
                        <div class="nav-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="nav-text">Dashboard</div>
                    </a>
                </div>
                
                <!-- Cadastros -->
                <?php if ($isAdmin || $user['tipo'] === 'secretaria'): ?>
                <div class="nav-item nav-group">
                    <div class="nav-link nav-toggle" data-group="cadastros" title="Cadastros">
                        <div class="nav-icon">
                            <i class="fas fa-database"></i>
                        </div>
                        <div class="nav-text">Cadastros</div>
                        <div class="nav-arrow">
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                    <div class="nav-submenu" id="cadastros">
                        <?php if ($isAdmin): ?>
                        <a href="index.php?page=usuarios&action=list" class="nav-sublink <?php echo $page === 'usuarios' ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i>
                            <span>Usuários</span>
                        </a>
                        <a href="index.php?page=cfcs&action=list" class="nav-sublink <?php echo $page === 'cfcs' ? 'active' : ''; ?>">
                            <i class="fas fa-building"></i>
                            <span>CFCs</span>
                        </a>
                        <?php endif; ?>
                        <a href="pages/alunos.php" class="nav-sublink <?php echo $page === 'alunos' ? 'active' : ''; ?>">
                            <i class="fas fa-graduation-cap"></i>
                            <span>Alunos</span>
                            <div class="nav-badge"><?php echo $stats['total_alunos']; ?></div>
                        </a>
                        <a href="pages/instrutores.php" class="nav-sublink <?php echo $page === 'instrutores' ? 'active' : ''; ?>">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span>Instrutores</span>
                            <div class="nav-badge"><?php echo $stats['total_instrutores']; ?></div>
                        </a>
                        <a href="pages/veiculos.php" class="nav-sublink <?php echo $page === 'veiculos' ? 'active' : ''; ?>">
                            <i class="fas fa-car"></i>
                            <span>Veículos</span>
                            <div class="nav-badge"><?php echo $stats['total_veiculos']; ?></div>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Operacional -->
                <div class="nav-item nav-group">
                    <div class="nav-link nav-toggle" data-group="operacional" title="Operacional">
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
                            <i class="fas fa-calendar-alt"></i>
                            <span>Agendamento</span>
                            <div class="nav-badge"><?php echo $stats['total_aulas']; ?></div>
                        </a>
                    </div>
                </div>
                
                <!-- Gestão de Turmas -->
                <div class="nav-item">
                    <a href="?page=turmas" class="nav-link <?php echo $page === 'turmas' ? 'active' : ''; ?>" title="Gestão de Turmas">
                        <div class="nav-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="nav-text">Gestão de Turmas</div>
                    </a>
                </div>
                
                <!-- Financeiro -->
                <?php if (defined('FINANCEIRO_ENABLED') && FINANCEIRO_ENABLED && ($isAdmin || $user['tipo'] === 'secretaria')): ?>
                <div class="nav-item nav-group">
                    <div class="nav-link nav-toggle" data-group="financeiro" title="Financeiro">
                        <div class="nav-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="nav-text">Financeiro</div>
                        <div class="nav-arrow">
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                    <div class="nav-submenu" id="financeiro">
                        <a href="?page=financeiro-faturas" class="nav-sublink <?php echo $page === 'financeiro-faturas' ? 'active' : ''; ?>">
                            <i class="fas fa-file-invoice"></i>
                            <span>Faturas (Receitas)</span>
                        </a>
                        <a href="?page=financeiro-despesas" class="nav-sublink <?php echo $page === 'financeiro-despesas' ? 'active' : ''; ?>">
                            <i class="fas fa-receipt"></i>
                            <span>Despesas (Pagamentos)</span>
                        </a>
                        <a href="?page=financeiro-relatorios" class="nav-sublink <?php echo $page === 'financeiro-relatorios' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-line"></i>
                            <span>Relatórios</span>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Relatórios Gerais -->
                <?php if ($isAdmin || $user['tipo'] === 'secretaria'): ?>
                <div class="nav-item nav-group">
                    <div class="nav-link nav-toggle" data-group="relatorios" title="Relatórios Gerais">
                        <div class="nav-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div class="nav-text">Relatórios Gerais</div>
                        <div class="nav-arrow">
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                    <div class="nav-submenu" id="relatorios">
                        <a href="pages/relatorio-matriculas.php" class="nav-sublink">
                            <i class="fas fa-graduation-cap"></i>
                            <span>Relatório de Matrículas</span>
                        </a>
                        <a href="pages/relatorio-frequencia.php" class="nav-sublink">
                            <i class="fas fa-calendar-check"></i>
                            <span>Relatório de Frequência</span>
                        </a>
                        <a href="pages/relatorio-presencas.php" class="nav-sublink">
                            <i class="fas fa-user-check"></i>
                            <span>Relatório de Presenças</span>
                        </a>
                        <a href="pages/relatorio-ata.php" class="nav-sublink">
                            <i class="fas fa-file-alt"></i>
                            <span>Relatório de ATA</span>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Configurações -->
                <?php if ($isAdmin): ?>
                <div class="nav-item nav-group">
                    <div class="nav-link nav-toggle" data-group="configuracoes" title="Configurações">
                        <div class="nav-icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <div class="nav-text">Configurações</div>
                        <div class="nav-arrow">
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                    <div class="nav-submenu" id="configuracoes">
                        <a href="index.php?page=configuracoes-categorias" class="nav-sublink <?php echo $page === 'configuracoes-categorias' ? 'active' : ''; ?>">
                            <i class="fas fa-layer-group"></i>
                            <span>Categorias de Habilitação</span>
                        </a>
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
                <?php endif; ?>
                
                <!-- Ferramentas de Desenvolvimento -->
                <?php if ($isAdmin): ?>
                <div class="nav-item nav-group">
                    <div class="nav-link nav-toggle" data-group="ferramentas" title="Ferramentas">
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
                <?php endif; ?>
                
                <!-- Sair -->
                <div class="nav-item">
                    <a href="../logout.php" class="nav-link" title="Sair">
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
                        // SOLUÇÃO DEFINITIVA v3.0 - Forçar eliminação de duplicatas
                        $alunosRaw = $db->fetchAll("
                            SELECT a.id, a.nome, a.cpf, a.rg, a.data_nascimento, a.endereco, a.telefone, a.email, a.cfc_id, a.categoria_cnh, a.status, a.criado_em, a.operacoes
                            FROM alunos a 
                            ORDER BY a.nome ASC
                        ");
                        
                        // FORÇAR eliminação de duplicatas por ID
                        $alunos = [];
                        $idsProcessados = [];
                        foreach ($alunosRaw as $aluno) {
                            if (!in_array($aluno['id'], $idsProcessados)) {
                                $alunos[] = $aluno;
                                $idsProcessados[] = $aluno['id'];
                            }
                        }
                        
                        // Adicionar campos necessários e decodificar operações
                        for ($i = 0; $i < count($alunos); $i++) {
                            $alunos[$i]['cfc_nome'] = 'CFC BOM CONSELHO';
                            $alunos[$i]['ultima_aula'] = null;
                            
                            // Decodificar operações
                            if (!empty($alunos[$i]['operacoes'])) {
                                $alunos[$i]['operacoes'] = json_decode($alunos[$i]['operacoes'], true);
                            } else {
                                $alunos[$i]['operacoes'] = [];
                            }
                        }
                    } catch (Exception $e) {
                        // Log do erro para debug
                        error_log("ERRO na query principal de alunos: " . $e->getMessage());
                        
                        // Query mais simples como fallback
                        try {
                            $alunos = $db->fetchAll("SELECT DISTINCT * FROM alunos ORDER BY nome ASC");
                            // Decodificar operações para cada aluno no fallback também
                            for ($i = 0; $i < count($alunos); $i++) {
                                if (!empty($alunos[$i]['operacoes'])) {
                                    $alunos[$i]['operacoes'] = json_decode($alunos[$i]['operacoes'], true);
                                } else {
                                    $alunos[$i]['operacoes'] = [];
                                }
                            }
                        } catch (Exception $e2) {
                            error_log("ERRO no fallback de alunos: " . $e2->getMessage());
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
                    // Verificar se é edição de aula
                    if ($action === 'edit') {
                        $content_file = "pages/editar-aula.php";
                        break;
                    }
                    
                    // Verificar se é listagem de aulas
                    if ($action === 'list') {
                        // Buscar todas as aulas para listagem
                        try {
                            $aulas_lista = $db->fetchAll("
                                SELECT a.*, 
                                       al.nome as aluno_nome,
                                       i.nome as instrutor_nome,
                                       v.placa as veiculo_placa,
                                       v.modelo as veiculo_modelo,
                                       c.nome as cfc_nome
                                FROM aulas a
                                LEFT JOIN alunos al ON a.aluno_id = al.id
                                LEFT JOIN instrutores i ON a.instrutor_id = i.id
                                LEFT JOIN veiculos v ON a.veiculo_id = v.id
                                LEFT JOIN cfcs c ON a.cfc_id = c.id
                                WHERE a.data_aula >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                                ORDER BY a.data_aula DESC, a.hora_inicio DESC
                                LIMIT 100
                            ");
                        } catch (Exception $e) {
                            $aulas_lista = [];
                        }
                        break;
                    }
                    
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
                    
                case 'agendar-manutencao':
                    // Buscar dados necessários para agendamento de manutenção
                    $veiculo_id = $_GET['veiculo_id'] ?? null;
                    $veiculo = null;
                    $cfcs = [];
                    
                    if ($veiculo_id) {
                        try {
                            $veiculo = $db->fetch("
                                SELECT v.*, c.nome as cfc_nome 
                                FROM veiculos v 
                                LEFT JOIN cfcs c ON v.cfc_id = c.id 
                                WHERE v.id = ?
                            ", [$veiculo_id]);
                            
                            if (!$veiculo) {
                                throw new Exception('Veículo não encontrado');
                            }
                        } catch (Exception $e) {
                            $veiculo = null;
                        }
                    }
                    
                    try {
                        $cfcs = $db->fetchAll("SELECT id, nome, ativo FROM cfcs WHERE ativo = 1 ORDER BY nome");
                    } catch (Exception $e) {
                        $cfcs = [];
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
                    
                // === CASES PARA MÓDULO FINANCEIRO ===
                case 'financeiro-faturas':
                    // Carregar dados para página de faturas
                    try {
                        // Buscar alunos para filtros
                        $alunos = $db->fetchAll("SELECT id, nome, cpf FROM alunos ORDER BY nome");
                    } catch (Exception $e) {
                        $alunos = [];
                    }
                    break;
                    
                case 'financeiro-despesas':
                    // Carregar dados para página de despesas
                    try {
                        // Dados específicos de despesas podem ser carregados aqui
                    } catch (Exception $e) {
                        // Tratar erro se necessário
                    }
                    break;
                    
                case 'financeiro-relatorios':
                    // Carregar dados para página de relatórios
                    try {
                        // Dados específicos de relatórios podem ser carregados aqui
                    } catch (Exception $e) {
                        // Tratar erro se necessário
                    }
                    break;

                // === CASES PARA TURMAS TEÓRICAS ===
                case 'turmas':
                // Casos legados removidos - funcionalidade migrada para turmas.php
                    // Carregar dados básicos para todas as páginas de turmas
                    try {
                        $turmas = $db->fetchAll("
                            SELECT t.*, i.nome as instrutor_nome, c.nome as cfc_nome,
                                   COUNT(ta.id) as total_alunos_matriculados
                            FROM turmas t
                            LEFT JOIN instrutores i ON t.instrutor_id = i.id
                            LEFT JOIN cfcs c ON t.cfc_id = c.id
                            LEFT JOIN turma_alunos ta ON t.id = ta.turma_id
                            WHERE t.tipo_aula = 'teorica'
                            GROUP BY t.id
                            ORDER BY t.data_inicio DESC
                        ");
                    } catch (Exception $e) {
                        $turmas = [];
                    }
                    
                    try {
                        $instrutores = $db->fetchAll("
                            SELECT i.id, i.usuario_id, u.nome, u.email, i.categoria_habilitacao
                            FROM instrutores i
                            JOIN usuarios u ON i.usuario_id = u.id
                            WHERE i.ativo = 1
                            ORDER BY u.nome ASC
                        ");
                    } catch (Exception $e) {
                        $instrutores = [];
                    }
                    
                    try {
                        $alunos = $db->fetchAll("
                            SELECT a.id, a.nome, a.cpf, a.email, a.telefone, a.categoria_cnh
                            FROM alunos a
                            WHERE a.status = 'ativo'
                            ORDER BY a.nome ASC
                        ");
                    } catch (Exception $e) {
                        $alunos = [];
                    }
                    
                    // Dados específicos por página
                    switch ($page) {
                        // Casos legados removidos - funcionalidade migrada para turmas.php
                    }
                    break;
                    
                default:
                    // Para o dashboard, não precisamos carregar dados específicos
                    break;
            }
            
            // Carregar conteúdo dinâmico baseado na página e ação
            if ($page === 'agendar-aula' && $action === 'list') {
                // Página específica para listagem de aulas
                $content_file = "pages/listar-aulas.php";
            } elseif ($page === 'agendar-aula' && $action === 'edit') {
                // Debug: Verificar roteamento de edição
                error_log("DEBUG: Roteamento para edição - ID: " . ($_GET['edit'] ?? 'não fornecido'));
                error_log("DEBUG: Parâmetros GET: " . print_r($_GET, true));
                error_log("DEBUG: Arquivo a ser carregado: pages/editar-aula.php");
                
                // Debug: Verificar sessão antes de carregar a página
                error_log("DEBUG: Session ID antes de carregar editar-aula: " . session_id());
                error_log("DEBUG: User ID antes de carregar editar-aula: " . ($_SESSION['user_id'] ?? 'não definido'));
                error_log("DEBUG: User Type antes de carregar editar-aula: " . ($_SESSION['user_type'] ?? 'não definido'));
                
                // Verificar se o arquivo existe
                if (!file_exists("pages/editar-aula.php")) {
                    error_log("ERRO: Arquivo pages/editar-aula.php não encontrado!");
                    echo '<div class="alert alert-danger">Erro: Arquivo de edição não encontrado.</div>';
                    return;
                }
                
                error_log("DEBUG: Arquivo pages/editar-aula.php encontrado, carregando...");
                // Página específica para edição de aulas
                $content_file = "pages/editar-aula.php";
                
                // Debug: Verificar sessão depois de definir o arquivo
                error_log("DEBUG: Session ID depois de definir arquivo: " . session_id());
                error_log("DEBUG: User ID depois de definir arquivo: " . ($_SESSION['user_id'] ?? 'não definido'));
                error_log("DEBUG: User Type depois de definir arquivo: " . ($_SESSION['user_type'] ?? 'não definido'));
            } elseif ($page === 'turmas') {
                // Páginas de turmas teóricas
                $content_file = "pages/{$page}.php";
            } else {
                $content_file = "pages/{$page}.php";
            }
            
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
        
        // Scripts de expansão interna removidos - usando menu-flyout.js
    </script>
    
    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- IMask para máscaras de input -->
    <script src="https://unpkg.com/imask@6.4.3/dist/imask.min.js"></script>
    
    <!-- Font Awesome já carregado no head -->
    
    <!-- JavaScript Principal do Admin -->
    <script src="assets/js/config.js"></script>
    <script src="assets/js/admin.js"></script>
    <script src="assets/js/menu-flyout.js"></script>
    <script src="assets/js/topbar-unified.js"></script>
    <script src="assets/js/components.js"></script>
    
    <!-- JavaScript das Funcionalidades Específicas -->
    <?php if ($page === 'cfcs'): ?>
        <script src="assets/js/cfcs.js"></script>
    <?php endif; ?>
    
    <?php if ($page === 'instrutores'): ?>
        <script src="assets/js/instrutores.js"></script>
        <!-- instrutores-page.js é carregado diretamente na página -->
    <?php endif; ?>
    
    <?php if ($page === 'alunos'): ?>
        <!-- <script src="assets/js/alunos.js"></script> -->
        <!-- alunos.js removido para evitar conflito com código inline -->
    <?php endif; ?>
</body>
</html>
