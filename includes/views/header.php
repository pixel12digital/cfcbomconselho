<?php
/**
 * Header - Cabeçalho das páginas
 * Inclui meta tags, CSS e navegação básica
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Sistema CFC'; ?></title>
    
    <!-- CSS Principal -->
    <link rel="stylesheet" href="assets/css/admin.css">
    
    <!-- CSS específico da página -->
    <?php if (isset($page_css)): ?>
        <link rel="stylesheet" href="assets/css/<?php echo $page_css; ?>">
    <?php endif; ?>
    
    <!-- Meta tags -->
    <meta name="description" content="Sistema de Gestão para CFCs">
    <meta name="author" content="Sistema CFC">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
</head>
<body>
    <header class="main-header">
        <div class="header-content">
            <div class="logo">
                <h1>🚗 Sistema CFC</h1>
            </div>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <nav class="main-nav">
                    <ul>
                        <li><a href="index.php">Dashboard</a></li>
                        <li><a href="usuarios.php">Usuários</a></li>
                        <li><a href="cfcs.php">CFCs</a></li>
                        <li><a href="alunos.php">Alunos</a></li>
                        <li><a href="instrutores.php">Instrutores</a></li>
                        <li><a href="veiculos.php">Veículos</a></li>
                        <li><a href="agendamento.php">Agendamento</a></li>
                    </ul>
                </nav>
                
                <div class="user-menu">
                    <span>Olá, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="logout.php" class="btn-logout">Sair</a>
                </div>
            <?php endif; ?>
        </div>
    </header>
    
    <main class="main-content">
        <?php if (isset($page_title)): ?>
            <div class="page-header">
                <h2><?php echo htmlspecialchars($page_title); ?></h2>
            </div>
        <?php endif; ?>
