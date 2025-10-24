<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Links Úteis - CFC Bom Conselho</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/J6MdG4Ck5lYhP4nG1N+2VxFfZxV0aYf2b0h2g0jT6e1zYgV3DkZ0x7vGKMg2YxZ1Fk9dQfXiw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* Cores Principais - Alinhadas ao Logo */
        :root {
            --primary-color: #1a365d;        /* Azul escuro do logo */
            --secondary-color: #f7b731;      /* Amarelo do logo */
            --secondary-gradient: linear-gradient(135deg, #FFFC03 0%, #FCCE1C 100%); /* Gradiente amarelo */
            --accent-color: #2d3748;         /* Azul médio */
            --success-color: #38a169;        /* Verde do logo */
            --warning-color: #f7b731;        /* Amarelo do logo */
            --warning-gradient: linear-gradient(135deg, #FFFC03 0%, #FCCE1C 100%); /* Gradiente amarelo */
            --danger-color: #e53e3e;         /* Vermelho do logo */
            --light-color: #f7fafc;          /* Branco suave */
            --dark-color: #1a365d;           /* Azul escuro */
            --logo-blue: #1a365d;            /* Azul principal do logo */
            --logo-green: #38a169;           /* Verde do logo */
            --logo-yellow: #f7b731;          /* Amarelo do logo */
            --logo-yellow-gradient: linear-gradient(135deg, #FFFC03 0%, #FCCE1C 100%); /* Gradiente amarelo */
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
            background: var(--logo-yellow-gradient, linear-gradient(135deg, #FFFC03 0%, #FCCE1C 100%));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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
            background: var(--logo-yellow-gradient, linear-gradient(135deg, #FFFC03 0%, #FCCE1C 100%));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .login-btn {
            background: var(--logo-yellow-gradient, linear-gradient(135deg, #FFFC03 0%, #FCCE1C 100%));
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
            border-left-color: #FFFC03;
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
            color: #333 !important;
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        /* Estilos específicos para textos de contato */
        .contact-info .contact-item span {
            color: #333 !important;
            font-weight: 500;
            font-size: 1rem;
        }
        
        /* Estilos específicos para textos de contato no footer */
        .footer .contact-info .contact-item span {
            color: #e8e8e8 !important;
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .footer .contact-column .contact-item span {
            color: #e8e8e8 !important;
            font-weight: 500;
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
            color: #ccc !important;
            font-size: 0.9rem;
            position: relative;
            z-index: 1;
            font-weight: 400;
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
        /* Padrão ícone apenas */
        .floating-btn {
            width: 56px !important;
            height: 56px !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            text-decoration: none !important;
            color: white !important;
            font-size: 28px !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15) !important;
            transition: all 0.3s ease !important;
            background: #25d366 !important;
            border: none !important;
            position: relative !important;
            overflow: hidden !important;
        }
        .floating-btn:hover { transform: translateY(-2px) !important; box-shadow: 0 4px 12px rgba(0,0,0,0.2) !important; }
        .floating-btn.whatsapp { background: #25d366 !important; }
        .floating-btn.whatsapp:hover { background: #20c05a !important; }
        .floating-btn.call { background: var(--logo-yellow) !important; color: var(--logo-blue) !important; font-size: 22px !important; }
        .floating-btn.call:hover { filter: brightness(0.95) !important; }
        
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
                        <a href="https://online8.detran.pe.gov.br/Habilitacao/ReforOnLine_BIO/startuser.aspx" target="_blank" class="link-button">Clique Aqui para Acessar</a>
                    </div>
                    
                    <div class="link-item">
                        <h3 class="link-title">Conta Pilotar</h3>
                        <p class="link-description">Página de login para acesso à conta digital no site Pilotar.app.</p>
                        <a href="https://share.google/jthYBKLXUXa548F8u" target="_blank" class="link-button">Clique Aqui para Acessar</a>
                    </div>
                    
                    <div class="link-item">
                        <h3 class="link-title">Portal Super Prati</h3>
                        <p class="link-description">Portal oficial do sistema SuperPrati para administração e gestão de CFCs.</p>
                        <a href="https://admin-pe.superprati.co/Login.aspx?ReturnUrl=%2f" target="_blank" class="link-button">Clique Aqui para Acessar</a>
                    </div>
                    
                    <div class="link-item">
                        <h3 class="link-title">Onboarding</h3>
                        <p class="link-description">Sistema oficial do DETRAN para processos de habilitação e renovação.</p>
                        <a href="https://admin.certfy.tech/Login.aspx?ReturnUrl=%2f" target="_blank" class="link-button">Clique Aqui para Acessar</a>
                    </div>
                    
                    <div class="link-item">
                        <h3 class="link-title">Consulta de Processos</h3>
                        <p class="link-description">Consulta de processos de habilitação e renovação no DETRAN-PE.</p>
                        <a href="https://www.detran.pe.gov.br/consulta-processos" target="_blank" class="link-button">Clique Aqui para Acessar</a>
                    </div>
                    
                    <div class="link-item">
                        <h3 class="link-title">Portal do CFC</h3>
                        <p class="link-description">Portal oficial para Centros de Formação de Condutores.</p>
                        <a href="https://www.detran.pe.gov.br/portal-cfc" target="_blank" class="link-button">Clique Aqui para Acessar</a>
                    </div>
                    
                    <div class="link-item">
                        <h3 class="link-title">Exame Teórico</h3>
                        <p class="link-description">Acompanhe o seu CFC de qualquer lugar.<br><br></p>
                        <a href="https://exame-teorico.superprati.co/gestor/login" target="_blank" class="link-button">Clique Aqui para Acessar</a>
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

    <!-- Botões Flutuantes - Padrão Ícone Apenas -->
    <div class="floating-buttons" id="floating-buttons">
        <a href="https://wa.me/5587981450308" class="floating-btn whatsapp" target="_blank" rel="noopener" aria-label="WhatsApp">
            <svg viewBox="0 0 32 32" width="26" height="26" fill="#ffffff" aria-hidden="true">
                <path d="M19.11 17.43c-.3-.16-1.76-.87-2.03-.97-.27-.1-.47-.16-.67.16-.2.32-.77.97-.95 1.17-.18.2-.35.23-.65.08-.3-.16-1.25-.46-2.38-1.47-.88-.78-1.48-1.73-1.65-2.03-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.53.15-.18.2-.3.3-.5.1-.2.05-.37-.03-.53-.08-.16-.67-1.62-.92-2.22-.24-.58-.5-.5-.67-.5-.17 0-.37-.02-.57-.02-.2 0-.53.08-.81.37-.27.3-1.06 1.03-1.06 2.51s1.09 2.91 1.24 3.11c.15.2 2.14 3.27 5.19 4.6.73.32 1.3.51 1.74.65.73.23 1.39.2 1.92.12.59-.09 1.76-.72 2.01-1.41.25-.7.25-1.29.17-1.41-.07-.12-.27-.2-.57-.35z"/>
                <path d="M27.1 4.9C24.2 2 20.3.5 16.2.5 7.9.5 1.2 7.2 1.2 15.5c0 2.6.7 5.1 2.1 7.3L1 31l8.4-2.2c2.2 1.2 4.7 1.8 7.3 1.8 8.3 0 15-6.7 15-15 0-4.1-1.6-8-4.6-10.9zM16.7 27.6c-2.3 0-4.6-.6-6.6-1.8l-.5-.3-5 1.3 1.3-4.9-.3-.5c-1.3-2.1-1.9-4.4-1.9-6.8 0-7.2 5.9-13.1 13.1-13.1 3.5 0 6.8 1.4 9.2 3.8 2.4 2.4 3.8 5.7 3.8 9.2-.1 7.2-6 13.1-13.5 13.1z"/>
            </svg>
        </a>
        <a href="tel:+5587981450308" class="floating-btn call" aria-label="Ligar Agora">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true">
                <path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1C12.4 21 3 11.6 3 1c0-.55.45-1 1-1h2.5c.55 0 1 .45 1 1 0 1.24.2 2.45.57 3.57.11.35.03.74-.24 1.02l-2.21 2.2z"/>
            </svg>
        </a>
        <button class="scroll-top" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" aria-label="Voltar ao topo">
            <span class="scroll-icon"></span>
        </button>
    </div>
</body>
</html>
