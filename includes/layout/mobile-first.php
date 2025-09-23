<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0d6efd">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="CFC Bom Conselho">
    
    <title><?php echo $pageTitle ?? 'CFC Bom Conselho'; ?></title>
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="../pwa/manifest.json">
    <link rel="apple-touch-icon" href="../pwa/icons/icon-192.png">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS Mobile-First Customizado -->
    <link rel="stylesheet" href="/assets/css/mobile-first.css">
    
    <!-- CSS específico da página -->
    <?php if (isset($pageCSS)): ?>
        <link rel="stylesheet" href="<?php echo $pageCSS; ?>">
    <?php endif; ?>
</head>
<body class="mobile-first">
    <!-- Header -->
    <header class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="<?php echo $homeUrl ?? '/admin/index.php'; ?>">
                <i class="fas fa-graduation-cap me-2"></i>
                CFC Bom Conselho
            </a>
            
            <!-- Botão de filtros (mobile) -->
            <button class="btn btn-outline-light d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#filtrosOffcanvas">
                <i class="fas fa-filter"></i>
            </button>
            
            <!-- Menu desktop -->
            <div class="d-none d-lg-block">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars($user['nome'] ?? 'Usuário'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/admin/perfil.php"><i class="fas fa-user me-2"></i>Perfil</a></li>
                            <li><a class="dropdown-item" href="/admin/configuracoes.php"><i class="fas fa-cog me-2"></i>Configurações</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Sair</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Conteúdo Principal -->
    <main class="main-content">
        <div class="container-fluid">
            <?php if (isset($pageContent)): ?>
                <?php echo $pageContent; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Bottom Navigation (Mobile Only) -->
    <nav class="bottom-nav d-lg-none fixed-bottom bg-white border-top">
        <div class="container-fluid">
            <div class="row text-center">
                <?php 
                $userType = $user['tipo'] ?? 'admin';
                $navItems = [];
                
                switch ($userType) {
                    case 'admin':
                    case 'secretaria':
                        $navItems = [
                            ['icon' => 'fas fa-home', 'label' => 'Início', 'url' => '/admin/index.php'],
                            ['icon' => 'fas fa-users', 'label' => 'Alunos', 'url' => '/admin/alunos.php'],
                            ['icon' => 'fas fa-calendar-alt', 'label' => 'Agenda', 'url' => '/admin/pages/agendamento-moderno.php'],
                            ['icon' => 'fas fa-chart-line', 'label' => 'Financeiro', 'url' => '/admin/financeiro.php']
                        ];
                        break;
                    case 'instrutor':
                        $navItems = [
                            ['icon' => 'fas fa-home', 'label' => 'Início', 'url' => '/instrutor/dashboard.php'],
                            ['icon' => 'fas fa-calendar-day', 'label' => 'Minhas Aulas', 'url' => '/instrutor/aulas.php'],
                            ['icon' => 'fas fa-chalkboard-teacher', 'label' => 'Turmas', 'url' => '/instrutor/turmas.php'],
                            ['icon' => 'fas fa-exclamation-triangle', 'label' => 'Ocorrências', 'url' => '/instrutor/ocorrencias.php']
                        ];
                        break;
                    case 'aluno':
                        $navItems = [
                            ['icon' => 'fas fa-home', 'label' => 'Início', 'url' => '/aluno/dashboard.php'],
                            ['icon' => 'fas fa-calendar-alt', 'label' => 'Minhas Aulas', 'url' => '/aluno/aulas.php'],
                            ['icon' => 'fas fa-credit-card', 'label' => 'Financeiro', 'url' => '/aluno/financeiro.php'],
                            ['icon' => 'fas fa-headset', 'label' => 'Suporte', 'url' => '/aluno/suporte.php']
                        ];
                        break;
                }
                
                foreach ($navItems as $index => $item): 
                    $isActive = (strpos($_SERVER['REQUEST_URI'], $item['url']) !== false) ? 'active' : '';
                ?>
                <div class="col-3">
                    <a href="<?php echo $item['url']; ?>" class="nav-link <?php echo $isActive; ?>">
                        <i class="<?php echo $item['icon']; ?> fs-5"></i>
                        <div class="nav-label"><?php echo $item['label']; ?></div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </nav>

    <!-- Offcanvas para Filtros (Mobile) -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="filtrosOffcanvas">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Filtros</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <div id="filtrosContent">
                <!-- Conteúdo dos filtros será inserido aqui via JavaScript -->
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>

    <!-- Loading Overlay -->
    <div class="loading-overlay d-none" id="loadingOverlay">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Carregando...</span>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('../pwa/sw.js')
                    .then(registration => {
                        console.log('SW registered: ', registration);
                    })
                    .catch(registrationError => {
                        console.log('SW registration failed: ', registrationError);
                    });
            });
        }
    </script>
    
    <!-- JavaScript Mobile-First -->
    <script src="/assets/js/mobile-first.js"></script>
    
    <!-- JavaScript específico da página -->
    <?php if (isset($pageJS)): ?>
        <script src="<?php echo $pageJS; ?>"></script>
    <?php endif; ?>
</body>
</html>
