<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Links Úteis - CFC Bom Conselho</title>
    <style>
        /* Cores Principais - Alinhadas ao Logo */
        :root {
            --primary-color: #1a365d;        /* Azul escuro do logo */
            --secondary-color: #f7b731;      /* Amarelo do logo */
            --accent-color: #2d3748;         /* Azul médio */
            --success-color: #38a169;        /* Verde do logo */
            --warning-color: #f7b731;        /* Amarelo do logo */
            --danger-color: #e53e3e;         /* Vermelho do logo */
            --light-color: #f7fafc;          /* Branco suave */
            --dark-color: #1a365d;           /* Azul escuro */
            --logo-blue: #1a365d;            /* Azul principal do logo */
            --logo-green: #38a169;           /* Verde do logo */
            --logo-yellow: #f7b731;          /* Amarelo do logo */
            --logo-red: #e53e3e;             /* Vermelho do logo */
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header Superior - Informações de Contato */
        .header-info {
            background: #fff;
            border-bottom: 1px solid #e1e5e9;
            padding: 8px 0;
            font-size: 14px;
        }
        
        .header-info .container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        
        .header-info .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
        }
        
        .header-info .info-item i {
            font-size: 16px;
            color: var(--logo-yellow, #f7b731);
        }
        
        .main-header {
            background: var(--logo-blue, #1a365d);
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .main-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo-image {
            width: 60px;
            height: 60px;
            border-radius: 50%;
        }
        
        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
        }
        
        .logo-subtitle {
            font-size: 0.9rem;
            color: #e2e8f0;
        }
        
        .nav-menu {
            display: flex;
            list-style: none;
            gap: 30px;
        }
        
        .nav-menu a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .nav-menu a:hover,
        .nav-menu a.active {
            color: var(--logo-yellow, #f7b731);
        }
        
        .login-btn {
            background: var(--logo-yellow, #f7b731);
            color: var(--logo-blue, #1a365d);
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .login-btn:hover {
            background: #f6ad55;
            transform: translateY(-2px);
        }
        
        /* Main Content */
        .main-content {
            padding: 60px 0;
            min-height: 60vh;
        }
        
        .page-title {
            text-align: center;
            font-size: 2.5rem;
            color: var(--logo-blue, #1a365d);
            margin-bottom: 3rem;
            font-weight: 700;
        }
        
        .links-section {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }
        
        .section-title {
            font-size: 1.8rem;
            color: var(--logo-blue, #1a365d);
            margin-bottom: 2rem;
            text-align: center;
            font-weight: 600;
        }
        
        .links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .link-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid var(--logo-green, #38a169);
            transition: all 0.3s ease;
        }
        
        .link-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-left-color: var(--logo-yellow, #f7b731);
        }
        
        .link-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--logo-blue, #1a365d);
            margin-bottom: 10px;
        }
        
        .link-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .link-button {
            background: var(--logo-green, #38a169);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .link-button:hover {
            background: #2f855a;
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }
        
        /* Footer */
        .footer {
            background: #1a1a1a;
            color: white;
            padding: 60px 0 20px;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="20" cy="20" r="1" fill="rgba(255,255,255,0.02)"/><circle cx="80" cy="40" r="1" fill="rgba(255,255,255,0.02)"/><circle cx="40" cy="80" r="1" fill="rgba(255,255,255,0.02)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
            z-index: 1;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: 2fr 1fr 1.5fr 1fr;
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            position: relative;
            z-index: 1;
        }
        
        .footer-column h4 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: white;
        }
        
        /* Coluna Logo */
        .logo-column {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .footer-logo {
            max-width: 200px;
        }
        
        .footer-logo-image {
            width: 100%;
            height: auto;
            max-width: 180px;
            filter: brightness(1.1);
        }
        
        .slogan-banner {
            background: var(--logo-blue);
            padding: 12px 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(26, 54, 93, 0.3);
        }
        
        .slogan-banner span {
            font-size: 0.9rem;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Coluna Institucional */
        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-links li {
            margin-bottom: 12px;
        }
        
        .footer-links a {
            color: #ccc;
            text-decoration: none;
            font-size: 0.95rem;
            transition: color 0.3s ease;
        }
        
        .footer-links a:hover {
            color: var(--logo-yellow);
        }
        
        /* Coluna Contato */
        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #ccc;
            font-size: 0.95rem;
        }
        
        .contact-item .contact-icon {
            width: 20px;
            height: 20px;
            background: var(--logo-yellow);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .contact-item .contact-icon.phone::before {
            content: '';
            position: absolute;
            width: 8px;
            height: 6px;
            border: 1.5px solid var(--logo-blue);
            border-radius: 2px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .contact-item .contact-icon.phone::after {
            content: '';
            position: absolute;
            width: 3px;
            height: 3px;
            background: var(--logo-blue);
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            margin-top: -1px;
            margin-left: 4px;
        }
        
        .contact-item .contact-icon.email::before {
            content: '';
            position: absolute;
            width: 10px;
            height: 7px;
            border: 1.5px solid var(--logo-blue);
            border-radius: 1px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .contact-item .contact-icon.email::after {
            content: '';
            position: absolute;
            width: 0;
            height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-top: 3px solid var(--logo-blue);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            margin-top: -2px;
        }
        
        .contact-item .contact-icon.location::before {
            content: '';
            position: absolute;
            width: 0;
            height: 0;
            border-left: 4px solid transparent;
            border-right: 4px solid transparent;
            border-bottom: 8px solid var(--logo-blue);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            margin-top: -1px;
        }
        
        .contact-item .contact-icon.location::after {
            content: '';
            position: absolute;
            width: 6px;
            height: 6px;
            background: var(--logo-blue);
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            margin-top: 3px;
        }
        
        /* Coluna Redes Sociais */
        .social-links {
            display: flex;
            gap: 15px;
        }
        
        .social-link {
            width: 45px;
            height: 45px;
            background: var(--logo-yellow);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: transform 0.3s ease;
            box-shadow: 0 4px 15px rgba(247, 183, 49, 0.3);
        }
        
        .social-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(247, 183, 49, 0.4);
        }
        
        .social-icon {
            width: 20px;
            height: 20px;
            position: relative;
        }
        
        .instagram-icon::before {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            border: 2px solid var(--logo-blue);
            border-radius: 4px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .instagram-icon::after {
            content: '';
            position: absolute;
            width: 6px;
            height: 6px;
            background: var(--logo-blue);
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .facebook-icon::before {
            content: 'f';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-family: Arial, sans-serif;
            font-weight: bold;
            font-size: 16px;
            color: var(--logo-blue);
        }
        
        /* Linha Separadora */
        .footer-divider {
            height: 1px;
            background: #333;
            margin: 40px 0 20px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Footer Bottom */
        .footer-bottom {
            text-align: center;
            color: #999;
            font-size: 0.9rem;
            position: relative;
            z-index: 1;
        }
        
        /* Botões Flutuantes */
        .floating-buttons {
            position: fixed !important;
            bottom: 20px !important;
            right: 20px !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 15px !important;
            z-index: 2147483647 !important;
            pointer-events: auto !important;
            isolation: isolate !important;
        }
        
        .whatsapp-button {
            background: #25D366 !important;
            color: white !important;
            padding: 15px 25px !important;
            border-radius: 25px !important;
            text-decoration: none !important;
            font-weight: 700 !important;
            font-size: 0.9rem !important;
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.4) !important;
            transition: all 0.3s ease !important;
            white-space: nowrap !important;
            z-index: 2147483646 !important;
            position: relative !important;
            pointer-events: auto !important;
            isolation: isolate !important;
        }
        
        .whatsapp-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 211, 102, 0.5);
            text-decoration: none;
            color: white;
        }
        
        .call-button {
            background: var(--logo-yellow) !important;
            color: var(--logo-blue) !important;
            padding: 15px 25px !important;
            border-radius: 25px !important;
            text-decoration: none !important;
            font-weight: 700 !important;
            font-size: 0.9rem !important;
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            box-shadow: 0 6px 20px rgba(247, 183, 49, 0.4) !important;
            transition: all 0.3s ease !important;
            white-space: nowrap !important;
            z-index: 2147483645 !important;
            position: relative !important;
            pointer-events: auto !important;
            isolation: isolate !important;
        }
        
        .call-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(247, 183, 49, 0.5);
            text-decoration: none;
            color: var(--logo-blue);
        }
        
        .scroll-top {
            width: 50px !important;
            height: 50px !important;
            background: white !important;
            border: none !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2) !important;
            transition: all 0.3s ease !important;
            z-index: 2147483644 !important;
            position: relative !important;
            pointer-events: auto !important;
            isolation: isolate !important;
        }
        
        .scroll-top:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }
        
        .call-icon {
            width: 16px;
            height: 16px;
            position: relative;
        }
        
        .call-icon.phone::before {
            content: '';
            position: absolute;
            width: 10px;
            height: 6px;
            border: 2px solid var(--logo-blue);
            border-radius: 2px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .call-icon.phone::after {
            content: '';
            position: absolute;
            width: 3px;
            height: 3px;
            background: var(--logo-blue);
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            margin-top: -1px;
            margin-left: 4px;
        }
        
        .scroll-icon {
            width: 0;
            height: 0;
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-bottom: 12px solid var(--logo-yellow);
        }
        
        /* Regra de Prioridade Absoluta para Botões Flutuantes */
        .floating-buttons,
        .floating-buttons *,
        .whatsapp-button,
        .call-button,
        .scroll-top {
            z-index: 2147483647 !important;
            position: relative !important;
            pointer-events: auto !important;
        }
        
        .whatsapp-button {
            z-index: 2147483646 !important;
        }
        
        .call-button {
            z-index: 2147483645 !important;
        }
        
        .scroll-top {
            z-index: 2147483644 !important;
        }
        
        /* CSS Adicional para Garantir Comportamento Flutuante */
        .floating-buttons {
            position: fixed !important;
            bottom: 20px !important;
            right: 20px !important;
            width: auto !important;
            height: auto !important;
            max-width: none !important;
            max-height: none !important;
            margin: 0 !important;
            padding: 0 !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 15px !important;
            z-index: 2147483647 !important;
            pointer-events: auto !important;
            isolation: isolate !important;
        }
        
        .whatsapp-button {
            background: #25D366 !important;
            color: white !important;
            padding: 15px 25px !important;
            border-radius: 25px !important;
            text-decoration: none !important;
            font-weight: 700 !important;
            font-size: 0.9rem !important;
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.4) !important;
            transition: all 0.3s ease !important;
            white-space: nowrap !important;
            z-index: 2147483646 !important;
            position: relative !important;
            pointer-events: auto !important;
            isolation: isolate !important;
            margin: 0 !important;
            border: none !important;
        }
        
        .call-button {
            background: var(--logo-yellow) !important;
            color: var(--logo-blue) !important;
            padding: 15px 25px !important;
            border-radius: 25px !important;
            text-decoration: none !important;
            font-weight: 700 !important;
            font-size: 0.9rem !important;
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            box-shadow: 0 6px 20px rgba(247, 183, 49, 0.4) !important;
            transition: all 0.3s ease !important;
            white-space: nowrap !important;
            z-index: 2147483645 !important;
            position: relative !important;
            pointer-events: auto !important;
            isolation: isolate !important;
            margin: 0 !important;
            border: none !important;
        }
        
        .scroll-top {
            width: 50px !important;
            height: 50px !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
        }
        
        /* Responsividade Footer */
        @media (max-width: 768px) {
            .footer-content {
                grid-template-columns: 1fr;
                gap: 30px;
                text-align: center;
            }
            
            .footer-logo {
                max-width: 150px;
                margin: 0 auto;
            }
            
            .footer-logo-image {
                max-width: 140px;
            }
            
            .social-links {
                justify-content: center;
            }
            
            .floating-buttons {
                bottom: 15px;
                right: 15px;
            }
            
            .call-button {
                padding: 12px 20px;
                font-size: 0.85rem;
            }
            
            .whatsapp-button {
                padding: 12px 20px;
                font-size: 0.85rem;
            }
            
            .scroll-top {
                width: 45px;
                height: 45px;
            }
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .links-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .floating-buttons {
                bottom: 15px;
                right: 15px;
            }
            
            .whatsapp-button,
            .call-button {
                padding: 12px 20px;
                font-size: 0.85rem;
            }
            
            .scroll-top {
                width: 45px;
                height: 45px;
            }
        }
        
        /* CSS ULTRA ESPECÍFICO PARA FORÇAR CORES */
        .footer .footer-content .contact-column .contact-info .contact-item .contact-icon {
            background: var(--logo-yellow) !important;
            background-color: var(--logo-yellow) !important;
        }
        
        .footer .footer-content .social-column .social-links .social-link {
            background: var(--logo-yellow) !important;
            background-color: var(--logo-yellow) !important;
        }
        
        .footer .footer-content .logo-column .footer-slogan .slogan-banner {
            background: var(--logo-blue) !important;
            background-color: var(--logo-blue) !important;
        }
        
        .floating-buttons .scroll-top {
            background: white !important;
            background-color: white !important;
        }
        
        .floating-buttons .scroll-top .scroll-icon {
            border-bottom-color: var(--logo-yellow) !important;
        }
        
        /* CSS ADICIONAL COM MÁXIMA ESPECIFICIDADE */
        body .footer .contact-icon {
            background: var(--logo-yellow) !important;
            background-color: var(--logo-yellow) !important;
        }
        
        body .footer .social-link {
            background: var(--logo-yellow) !important;
            background-color: var(--logo-yellow) !important;
        }
        
        body .footer .slogan-banner {
            background: var(--logo-blue) !important;
            background-color: var(--logo-blue) !important;
        }
        
        body .scroll-top {
            background: white !important;
            background-color: white !important;
        }
        
        body .scroll-icon {
            border-bottom-color: var(--logo-yellow) !important;
        }
        
        .whatsapp-icon {
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <!-- Header Superior - Informações de Contato -->
    <div class="header-info">
        <div class="container">
            <div class="info-item">
                <span>📞</span>
                <span>(87) 98145-0308</span>
            </div>
            <div class="info-item">
                <span>📍</span>
                <span>R. Ângela Pessoa Lucena, 248 - Bom Conselho, PE, 55330-000</span>
            </div>
        </div>
    </div>

    <!-- Header Principal -->
    <header class="main-header">
        <div class="container">
            <div class="logo-section">
                <img src="assets/logo.png" alt="CFC Bom Conselho" class="logo-image">
                <div>
                    <div class="logo-text">CFC BOM CONSELHO</div>
                    <div class="logo-subtitle">Centro de Formação de Condutores</div>
                </div>
            </div>
            
            <nav>
                <ul class="nav-menu">
                    <li><a href="index.php">HOME</a></li>
                    <li><a href="index.php#formacao-completa">SOBRE</a></li>
                    <li><a href="index.php#servicos">SERVIÇOS</a></li>
                    <li><a href="index.php#trabalhe">TRABALHE CONOSCO</a></li>
                    <li><a href="index.php#contato">CONTATO</a></li>
                </ul>
            </nav>
            
            <a href="login.php" class="login-btn" title="Acessar Sistema">
                🔑 Entrar
            </a>
        </div>
    </header>

    <!-- Conteúdo Principal -->
    <main class="main-content">
        <div class="container">
            <h1 class="page-title">Central de Acessos Rápidos</h1>
            
            <div class="links-section">
                <div class="links-grid">
                    <div class="link-item">
                        <h3 class="link-title">DETRAN Pernambuco</h3>
                        <p class="link-description">Página principal do DETRAN de Pernambuco.</p>
                        <a href="https://www.detran.pe.gov.br/" target="_blank" class="link-button">Clique Aqui para Acessar</a>
                    </div>
                    
                    <div class="link-item">
                        <h3 class="link-title">Autenticação DETRAN-PE</h3>
                        <p class="link-description">Página de autenticação para serviços de habilitação do DETRAN Pernambuco.</p>
                        <a href="https://www.detran.pe.gov.br/autenticacao" target="_blank" class="link-button">Clique Aqui para Acessar</a>
                    </div>
                    
                    <div class="link-item">
                        <h3 class="link-title">Conta Digital</h3>
                        <p class="link-description">Página de login para acesso à conta digital no site Pilota.app.</p>
                        <a href="https://pilota.app/" target="_blank" class="link-button">Clique Aqui para Acessar</a>
                    </div>
                    
                    <div class="link-item">
                        <h3 class="link-title">SuperPrati Admin</h3>
                        <p class="link-description">Página de administração para login no sistema SuperPrati, especificamente para a localidade de Pernambuco.</p>
                        <a href="https://admin.superprati.com.br/" target="_blank" class="link-button">Clique Aqui para Acessar</a>
                    </div>
                    
                    <div class="link-item">
                        <h3 class="link-title">SuperPrati Instrutor</h3>
                        <p class="link-description">Página de recursos e suporte para instrutores.</p>
                        <a href="https://instrutor.superprati.com.br/" target="_blank" class="link-button">Clique Aqui para Acessar</a>
                    </div>
                    
                    <div class="link-item">
                        <h3 class="link-title">Exame Teórico SuperPrati</h3>
                        <p class="link-description">Página de login para gestores do sistema de exames teóricos da SuperPrati.</p>
                        <a href="https://exame.superprati.com.br/" target="_blank" class="link-button">Clique Aqui para Acessar</a>
                    </div>
                    
                    <div class="link-item">
                        <h3 class="link-title">Onboarding Digital</h3>
                        <p class="link-description">Página de login para o sistema Onboarding Digital.</p>
                        <a href="https://onboarding.digital/" target="_blank" class="link-button">Clique Aqui para Acessar</a>
                    </div>
                    
                    <div class="link-item">
                        <h3 class="link-title">Onboarding Admin</h3>
                        <p class="link-description">Página de login para o sistema onboarding admin.</p>
                        <a href="https://admin.onboarding.digital/" target="_blank" class="link-button">Clique Aqui para Acessar</a>
                    </div>
                    
                    <div class="link-item">
                        <h3 class="link-title">New Driver</h3>
                        <p class="link-description">Página inicial do aplicativo New Driver, relacionada a serviços de condução ou educação para motoristas.</p>
                        <a href="https://newdriver.com.br/" target="_blank" class="link-button">Clique Aqui para Acessar</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <!-- Coluna 1: Logo -->
                <div class="footer-column logo-column">
                    <div class="footer-logo">
                        <img src="assets/logo.png" alt="CFC Bom Conselho" class="footer-logo-image">
                    </div>
                    <div class="footer-slogan">
                        <div class="slogan-banner">
                            <span>NO TRÂNSITO SOMOS TODOS RESPONSÁVEIS</span>
                        </div>
                    </div>
                </div>
                
                <!-- Coluna 2: Institucional -->
                <div class="footer-column institutional-column">
                    <h4>Institucional</h4>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="index.php#servicos">Serviços</a></li>
                        <li><a href="index.php#trabalhe-conosco">Trabalhe Conosco</a></li>
                        <li><a href="links-uteis.php">Links Úteis</a></li>
                        <li><a href="#portal-aluno">Portal do Aluno</a></li>
                        <li><a href="#portal-cfc">Portal do CFC</a></li>
                    </ul>
                </div>
                
                <!-- Coluna 3: Contato -->
                <div class="footer-column contact-column">
                    <h4>Contato</h4>
                    <div class="contact-info">
                        <div class="contact-item">
                            <span class="contact-icon phone"></span>
                            <span>(87) 98145-0308</span>
                        </div>
                        <div class="contact-item">
                            <span class="contact-icon email"></span>
                            <span>contato@cfcbomconselho.com.br</span>
                        </div>
                        <div class="contact-item">
                            <span class="contact-icon location"></span>
                            <span>R. Ângela Pessoa Lucena, 248 - Bom Conselho, PE, 55330-000</span>
                        </div>
                    </div>
                </div>
                
                <!-- Coluna 4: Redes Sociais -->
                <div class="footer-column social-column">
                    <h4>Seguir nas redes sociais</h4>
                    <div class="social-links">
                        <a href="https://www.instagram.com/cfcbomconselho/" target="_blank" class="social-link instagram">
                            <span class="social-icon instagram-icon"></span>
                        </a>
                        <a href="https://www.facebook.com/profile.php?id=100067658005033&mibextid=qi2Omg&rdid=CfIReIMPkCEYOooj&share_url=https%3A%2F%2Fwww.facebook.com%2Fshare%2FoR2kqg5HAq2p4wzT%2F%3Fmibextid%3Dqi2Omg#" target="_blank" class="social-link facebook">
                            <span class="social-icon facebook-icon"></span>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Linha separadora -->
            <div class="footer-divider"></div>
            
            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <p>&copy; 2024 CFC Bom Conselho | Todos os direitos Reservados | Criado por Pixel12Digital</p>
            </div>
        </div>
    </footer>

    <!-- Botões Flutuantes - Fora do Footer -->
    <div class="floating-buttons">
        <a href="https://wa.me/5587981450308?text=Olá! Gostaria de saber mais sobre os serviços do CFC Bom Conselho." target="_blank" class="whatsapp-button">
            <span class="whatsapp-icon">📱</span>
            <span class="whatsapp-text">WhatsApp</span>
        </a>
        <a href="tel:+5587981450308" class="call-button">
            <span class="call-icon phone"></span>
            <span class="call-text">Ligar Agora</span>
        </a>
        <button class="scroll-top" onclick="window.scrollTo({top: 0, behavior: 'smooth'})">
            <span class="scroll-icon"></span>
        </button>
    </div>
</body>
</html>
