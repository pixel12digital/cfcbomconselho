<?php
// =====================================================
// PÁGINA INSTITUCIONAL - CFC BOM CONSELHO
// VERSÃO 3.0 - PÁGINA PRINCIPAL DO SITE
// =====================================================

require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CFC Bom Conselho - Centro de Formação de Condutores</title>
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
        }
        
        /* Header Principal */
        .main-header {
            background: var(--logo-blue);
            color: white;
            padding: 20px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .main-header .container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        /* Logo */
        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo-image {
            width: 90px !important;
            height: 90px !important;
            object-fit: contain !important;
        }
        
        .logo-text {
            font-size: 18px;
            font-weight: bold;
            color: white;
        }
        
        .logo-subtitle {
            font-size: 12px;
            color: #ccc;
            margin-top: 2px;
        }
        
        /* Navegação */
        .nav-menu {
            display: flex;
            list-style: none;
            gap: 30px;
        }
        
        .nav-menu a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 10px 0;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .nav-menu a:hover,
        .nav-menu a.active {
            border-bottom-color: #f39c12;
            color: #f39c12;
        }
        
        /* Botão de Login */
        .login-btn {
            background: var(--logo-yellow);
            color: var(--logo-blue);
            padding: 12px 20px;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(247, 183, 49, 0.3);
            white-space: nowrap;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(247, 183, 49, 0.4);
            text-decoration: none;
            color: var(--logo-blue);
            background: #f6ad55;
        }
        
        /* Hero Section */
        .hero {
            background-image: url('assets/img/banner-home.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 500px;
            width: 100%;
        }
        
        /* Seção de Navegação */
        .navigation-section {
            background: #f8f9fa;
            padding: 40px 0;
            position: relative;
            z-index: 1;
        }
        
        .nav-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .nav-item {
            display: flex;
            justify-content: center;
            align-items: center;
            transition: transform 0.3s ease;
        }
        
        .nav-item:hover {
            transform: translateY(-5px);
        }
        
        .nav-image {
            max-width: 100%;
            height: auto;
            cursor: pointer;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
            transition: all 0.3s ease;
        }
        
        .nav-link {
            display: block;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            text-decoration: none;
        }
        
        .nav-link:hover .nav-image {
            filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.2));
            transform: translateY(-5px);
        }
        
        /* Responsividade */
        /* Seção de Contato e Unidades - Duas Colunas */
        .units-contact-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 60px;
            padding: 0 20px;
        }
        
        .contact-form-column,
        .units-info-column {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .contact-form-column {
            border-left: 5px solid var(--logo-green);
        }
        
        .units-info-column {
            border-left: 5px solid var(--logo-blue);
        }
        
        .contact-form-container h4,
        .units-info-container h4 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--logo-blue);
            margin-bottom: 15px;
            text-align: center;
        }
        
        .contact-form-container p,
        .units-info-container p {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 1rem;
        }
        
        /* Formulário de Contato */
        .contact-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-weight: 600;
            color: var(--logo-blue);
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 15px;
            border: 2px solid #e1e8ed;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--logo-green);
            background: white;
            box-shadow: 0 0 0 3px rgba(56, 161, 105, 0.1);
        }
        
        .form-group select {
            cursor: pointer;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .submit-button {
            background: linear-gradient(135deg, var(--logo-yellow) 0%, #f6ad55 100%);
            color: var(--logo-blue);
            padding: 18px 30px;
            border: none;
            border-radius: 15px;
            font-weight: 700;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(247, 183, 49, 0.3);
            margin-top: 10px;
        }
        
        .submit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(247, 183, 49, 0.4);
        }
        
        /* Informações Centralizadas */
        .centralized-info {
            margin-bottom: 30px;
        }
        
        .info-highlight {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 25px;
            background: linear-gradient(135deg, var(--logo-blue) 0%, var(--accent-color) 100%);
            border-radius: 15px;
            margin-bottom: 20px;
            color: white;
            box-shadow: 0 8px 25px rgba(26, 54, 93, 0.3);
        }
        
        .highlight-icon {
            font-size: 2.5rem;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            color: white;
        }
        
        .highlight-content h5 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .highlight-content p {
            margin: 0;
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .coverage-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            border-left: 4px solid var(--logo-green);
        }
        
        .coverage-info h5 {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--logo-blue);
            margin-bottom: 15px;
        }
        
        .cities-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .city-tag {
            background: var(--logo-green);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: transform 0.3s ease;
        }
        
        .city-tag:hover {
            transform: scale(1.05);
        }
        
        .contact-info {
            margin-bottom: 30px;
        }
        
        .contact-info h5 {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--logo-blue);
            margin-bottom: 15px;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #e1e8ed;
        }
        
        .contact-item:last-child {
            border-bottom: none;
        }
        
        .contact-icon {
            font-size: 1.2rem;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--logo-yellow);
            border-radius: 50%;
            color: var(--logo-blue);
            position: relative;
        }
        
        /* Ícones Geométricos Profissionais */
        .contact-icon.phone::before {
            content: '';
            position: absolute;
            width: 12px;
            height: 8px;
            border: 2px solid var(--logo-blue);
            border-radius: 3px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .contact-icon.phone::after {
            content: '';
            position: absolute;
            width: 4px;
            height: 4px;
            background: var(--logo-blue);
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            margin-top: -2px;
            margin-left: 6px;
        }
        
        .contact-icon.whatsapp::before {
            content: '';
            position: absolute;
            width: 14px;
            height: 14px;
            border: 2px solid var(--logo-blue);
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .contact-icon.whatsapp::after {
            content: '';
            position: absolute;
            width: 6px;
            height: 6px;
            background: var(--logo-blue);
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            margin-top: 2px;
            margin-left: 2px;
        }
        
        .contact-icon.email::before {
            content: '';
            position: absolute;
            width: 14px;
            height: 10px;
            border: 2px solid var(--logo-blue);
            border-radius: 2px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .contact-icon.email::after {
            content: '';
            position: absolute;
            width: 0;
            height: 0;
            border-left: 7px solid transparent;
            border-right: 7px solid transparent;
            border-top: 5px solid var(--logo-blue);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            margin-top: -3px;
        }
        
        .contact-icon.location::before {
            content: '';
            position: absolute;
            width: 0;
            height: 0;
            border-left: 6px solid transparent;
            border-right: 6px solid transparent;
            border-bottom: 12px solid var(--logo-blue);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            margin-top: -2px;
        }
        
        .contact-icon.location::after {
            content: '';
            position: absolute;
            width: 8px;
            height: 8px;
            background: var(--logo-blue);
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            margin-top: 4px;
        }
        
        .services-highlight h5 {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--logo-blue);
            margin-bottom: 15px;
        }
        
        .services-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .service-tag {
            background: var(--logo-green);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: transform 0.3s ease;
        }
        
        .service-tag:hover {
            transform: scale(1.05);
        }
        
        /* Seção de Serviços Oferecidos */
        .services-offered {
            margin: 50px 0;
            text-align: center;
        }
        
        .services-offered h4 {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 30px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .services-tags-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .services-tags-grid .service-tag {
            background: rgba(255, 255, 255, 0.95);
            color: var(--logo-blue);
            padding: 12px 20px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 2px solid transparent;
        }
        
        .services-tags-grid .service-tag:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            border-color: var(--logo-yellow);
            background: white;
        }
        
        @media (max-width: 768px) {
            .nav-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 15px;
            }
            
            .units-contact-section {
                grid-template-columns: 1fr;
                gap: 30px;
                margin-top: 40px;
                padding: 0 10px;
            }
            
            .contact-form-column,
            .units-info-column {
                padding: 30px 20px;
            }
            
            .contact-form-container h4,
            .units-info-container h4 {
                font-size: 1.5rem;
            }
            
            .form-group input,
            .form-group select,
            .form-group textarea {
                padding: 12px;
            }
            
            .submit-button {
                padding: 15px 25px;
                font-size: 1rem;
            }
            
            .unit-summary-item {
                padding: 15px;
            }
            
            .summary-icon {
                font-size: 1.5rem;
                width: 40px;
                height: 40px;
            }
            
            .info-highlight {
                padding: 20px;
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .highlight-icon {
                font-size: 2rem;
                width: 50px;
                height: 50px;
            }
            
            .coverage-info {
                padding: 15px;
            }
            
            .cities-list {
                justify-content: center;
            }
            
            .services-list {
                justify-content: center;
            }
            
            .services-offered h4 {
                font-size: 1.3rem;
            }
            
            .services-tags-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 12px;
            }
            
            .services-tags-grid .service-tag {
                padding: 10px 15px;
                font-size: 0.85rem;
            }
            
            .contact-icon {
                width: 25px;
                height: 25px;
            }
            
            .contact-icon.phone::before {
                width: 10px;
                height: 6px;
            }
            
            .contact-icon.phone::after {
                width: 3px;
                height: 3px;
                margin-left: 5px;
            }
            
            .contact-icon.whatsapp::before {
                width: 12px;
                height: 12px;
            }
            
            .contact-icon.whatsapp::after {
                width: 5px;
                height: 5px;
            }
            
            .contact-icon.email::before {
                width: 12px;
                height: 8px;
            }
            
            .contact-icon.email::after {
                border-left: 6px solid transparent;
                border-right: 6px solid transparent;
                border-top: 4px solid var(--logo-blue);
            }
            
            .contact-icon.location::before {
                border-left: 5px solid transparent;
                border-right: 5px solid transparent;
                border-bottom: 10px solid var(--logo-blue);
            }
            
            .contact-icon.location::after {
                width: 6px;
                height: 6px;
            }
        }
        
        @media (max-width: 480px) {
            .nav-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
        }
        
        /* Seção de Apresentação */
        .presentation-section {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            padding: 80px 0;
            position: relative;
        }
        
        .presentation-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, #e9ecef, transparent);
        }
        
        .presentation-content {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .text-content {
            text-align: center;
        }
        
        .main-title {
            font-size: 36px;
            font-weight: 800;
            color: var(--logo-blue);
            margin-bottom: 20px;
            line-height: 1.2;
        }
        
        .subtitle {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--logo-yellow);
            margin-bottom: 60px;
            position: relative;
        }
        
        .subtitle::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, var(--logo-yellow), var(--logo-red));
            border-radius: 2px;
        }
        
        .text-blocks {
            display: flex;
            flex-direction: column;
            gap: 40px;
            text-align: left;
        }
        
        .text-block {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            border-left: 4px solid var(--logo-green);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .text-block:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
        }
        
        .lead-text {
            font-size: 1.3rem;
            line-height: 1.6;
            color: #2c3e50;
            margin: 0;
            font-weight: 500;
        }
        
        .text-block p {
            font-size: 1.1rem;
            line-height: 1.7;
            color: #34495e;
            margin: 0 0 20px 0;
        }
        
        .text-block p:last-child {
            margin-bottom: 0;
        }
        
        .categories {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .category-badge {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            padding: 12px 20px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 1.1rem;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
            transition: transform 0.3s ease;
        }
        
        .category-badge:hover {
            transform: scale(1.05);
        }
        
        .highlight-block {
            background: linear-gradient(135deg, var(--logo-green) 0%, #2f855a 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(56, 161, 105, 0.3);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .highlight-block::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: shimmer 3s ease-in-out infinite;
        }
        
        @keyframes shimmer {
            0%, 100% { transform: translateX(-100%) translateY(-100%) rotate(30deg); }
            50% { transform: translateX(100%) translateY(100%) rotate(30deg); }
        }
        
        .highlight-block p {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
            position: relative;
            z-index: 2;
        }
        
        .call-to-action {
            background: linear-gradient(135deg, var(--logo-blue) 0%, var(--accent-color) 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(26, 54, 93, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .call-to-action::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            animation: slide 2s ease-in-out infinite;
        }
        
        @keyframes slide {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .cta-text {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 25px;
            position: relative;
            z-index: 2;
        }
        
        .cta-button {
            background: linear-gradient(135deg, var(--logo-yellow) 0%, #f6ad55 100%);
            color: var(--logo-blue);
            padding: 18px 35px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1rem;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 6px 20px rgba(247, 183, 49, 0.4);
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
        }
        
        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(247, 183, 49, 0.5);
            text-decoration: none;
            color: var(--logo-blue);
        }
        
        .cta-icon {
            font-size: 1.2rem;
        }
        
        /* Responsividade para a seção de apresentação */
        @media (max-width: 768px) {
            .main-title {
                font-size: 28px;
            }
            
            .subtitle {
                font-size: 1.2rem;
            }
            
            .text-blocks {
                gap: 25px;
            }
            
            .text-block {
                padding: 20px;
            }
            
            .lead-text {
                font-size: 1.1rem;
            }
            
            .text-block p {
                font-size: 1rem;
            }
            
            .categories {
                gap: 10px;
            }
            
            .category-badge {
                padding: 10px 16px;
                font-size: 1rem;
            }
        }
        
        /* Seção de Estrutura */
        .structure-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            padding: 80px 0;
            position: relative;
        }
        
        .structure-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, #e9ecef, transparent);
        }
        
        .structure-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .structure-image {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .main-image {
            max-width: 100%;
            height: auto;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .main-image:hover {
            transform: scale(1.02);
        }
        
        .structure-text {
            padding-left: 20px;
        }
        
        .structure-title {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 15px;
            line-height: 1.2;
        }
        
        .structure-subtitle {
            font-size: 1.3rem;
            font-weight: 600;
            color: #f39c12;
            margin-bottom: 40px;
            position: relative;
        }
        
        .structure-subtitle::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, #f39c12, #e67e22);
            border-radius: 2px;
        }
        
        /* Accordion Styles */
        .accordion {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .accordion-item {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }
        
        .accordion-item:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }
        
        .accordion-header {
            padding: 20px 25px;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
            border-bottom: 1px solid transparent;
        }
        
        .accordion-header:hover {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        
        .accordion-header h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .professional-icon {
            color: #f39c12;
            font-size: 1.2rem;
            font-weight: bold;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(243, 156, 18, 0.1);
            border-radius: 50%;
            flex-shrink: 0;
        }
        
        .accordion-icon {
            font-size: 1.5rem;
            font-weight: bold;
            color: #f39c12;
            transition: transform 0.3s ease;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(243, 156, 18, 0.1);
            border-radius: 50%;
        }
        
        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, padding 0.3s ease;
            background: white;
        }
        
        .accordion-content p {
            padding: 0 25px 25px 25px;
            margin: 0;
            font-size: 1rem;
            line-height: 1.6;
            color: #34495e;
        }
        
        /* Accordion Active State */
        .accordion-item.active .accordion-content {
            max-height: 200px;
            padding-top: 0;
        }
        
        .accordion-item.active .accordion-icon {
            transform: rotate(45deg);
            background: rgba(243, 156, 18, 0.2);
        }
        
        .accordion-item.active .accordion-header {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
        }
        
        .accordion-item.active .accordion-header h4 {
            color: white;
        }
        
        .accordion-item.active .professional-icon {
            color: white;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .accordion-item.active .accordion-icon {
            color: white;
            background: rgba(255, 255, 255, 0.2);
        }
        
        /* Responsividade para a seção de estrutura */
        @media (max-width: 768px) {
            .structure-content {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            
            .structure-text {
                padding-left: 0;
            }
            
            .structure-title {
                font-size: 1.6rem;
                text-align: center;
            }
        
            .structure-subtitle {
                font-size: 1.1rem;
                text-align: center;
            }
            
            .structure-subtitle::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .accordion-header {
                padding: 15px 20px;
            }
            
            .accordion-header h4 {
                font-size: 1rem;
            }
            
            .accordion-content p {
                padding: 0 20px 20px 20px;
                font-size: 0.95rem;
            }
        }
        
        /* Seção de Serviços */
        .services-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 80px 0;
            position: relative;
            overflow: hidden;
        }
        
        .services-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }
        
        .services-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            text-align: center;
            position: relative;
            z-index: 2;
        }
        
        .services-title {
            font-size: 3.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 20px;
            line-height: 1.2;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        
        .services-subtitle {
            font-size: 1.4rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 60px;
            position: relative;
        }
        
        .services-subtitle::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(90deg, #f39c12, #e67e22);
            border-radius: 2px;
        }
        
        /* Grid de Cards Animados */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }
        
        .service-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
        }
        
        .service-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        
        .card-icon {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .icon-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            position: relative;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .icon-text {
            font-size: 1.2rem;
            font-weight: bold;
            color: white;
        }
        
        .card-content h4 {
            font-size: 1.4rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .card-content p {
            font-size: 1rem;
            line-height: 1.6;
            color: #34495e;
            margin: 0;
        }
        
        .card-glow {
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(243, 156, 18, 0.1) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .service-card:hover .card-glow {
            opacity: 1;
        }
        
        /* Highlight Section */
        .services-highlight {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .highlight-content {
            display: flex;
            align-items: center;
            gap: 30px;
            position: relative;
            z-index: 2;
        }
        
        .highlight-icon {
            font-size: 3rem;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
        
        .highlight-text h4 {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            margin-bottom: 10px;
        }
        
        .highlight-text p {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
            margin: 0;
        }
        
        .highlight-animation {
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            animation: slideHighlight 3s ease-in-out infinite;
        }
        
        @keyframes slideHighlight {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        /* CTA Section */
        .services-cta {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .cta-content h4 {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 15px;
        }
        
        .cta-content p {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 30px;
        }
        
        .cta-button {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
            padding: 20px 40px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.2rem;
            display: inline-flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 10px 30px rgba(243, 156, 18, 0.4);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(243, 156, 18, 0.6);
            text-decoration: none;
            color: white;
        }
        
        .button-icon {
            font-size: 1.3rem;
        }
        
        .button-ripple {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .cta-button:hover .button-ripple {
            width: 300px;
            height: 300px;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .services-title {
                font-size: 2.5rem;
            }
            
            .services-subtitle {
                font-size: 1.1rem;
            }
            
            .services-grid {
                grid-template-columns: 1fr;
            gap: 20px;
            }
            
            .service-card {
                padding: 25px;
            }
            
            .highlight-content {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            
            .highlight-icon {
                font-size: 2.5rem;
            }
            
            .highlight-text h4 {
                font-size: 1.5rem;
            }
            
            .cta-content h4 {
                font-size: 1.6rem;
            }
            
            .cta-button {
                padding: 15px 30px;
                font-size: 1.1rem;
            }
        }
        
        /* Seção Frota */
        .fleet-section {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            padding: 80px 0;
            position: relative;
            overflow: hidden;
        }
        
        .fleet-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="dots" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23dots)"/></svg>');
            opacity: 0.3;
        }
        
        .fleet-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            text-align: center;
            position: relative;
            z-index: 2;
        }
        
        .fleet-title {
            font-size: 3.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 20px;
            line-height: 1.2;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        
        .fleet-subtitle {
            font-size: 1.4rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 60px;
            position: relative;
        }
        
        .fleet-subtitle::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(90deg, #f39c12, #e67e22);
            border-radius: 2px;
        }
        
        /* Galeria de Frota */
        .fleet-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 60px;
        }
        
        .gallery-item {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .gallery-item:hover {
            transform: translateY(-15px) scale(1.05);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
        }
        
        .gallery-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: transform 0.4s ease;
        }
        
        .gallery-item:hover .gallery-image {
            transform: scale(1.1);
        }
        
        .gallery-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(243, 156, 18, 0.8) 0%, rgba(230, 126, 34, 0.8) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .gallery-item:hover .gallery-overlay {
            opacity: 1;
        }
        
        .gallery-icon {
            font-size: 2.5rem;
            color: white;
            transform: scale(0);
            transition: transform 0.3s ease;
        }
        
        .gallery-item:hover .gallery-icon {
            transform: scale(1);
        }
        
        /* Cards de Informação */
        .fleet-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .info-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
        }
        
        .info-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        
        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #f39c12, #e67e22);
        }
        
        .info-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px auto;
            animation: float 3s ease-in-out infinite;
            position: relative;
        }
        
        .info-icon::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .info-icon.vehicle::before {
            width: 40px;
            height: 20px;
            background: var(--logo-red);
            border-radius: 8px 8px 4px 4px;
        }
        
        .info-icon.vehicle::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 8px;
            height: 8px;
            background: var(--logo-blue);
            border-radius: 50%;
            margin-top: -2px;
        }
        
        .info-icon.shield::before {
            width: 0;
            height: 0;
            border-left: 20px solid transparent;
            border-right: 20px solid transparent;
            border-bottom: 30px solid var(--logo-blue);
            border-radius: 4px 4px 0 0;
        }
        
        .info-icon.shield::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 30px;
            height: 20px;
            background: var(--logo-blue);
            border-radius: 0 0 4px 4px;
            margin-top: 5px;
        }
        
        .info-icon.wrench::before {
            width: 30px;
            height: 4px;
            background: var(--logo-yellow);
            border-radius: 2px;
            transform: translate(-50%, -50%) rotate(45deg);
        }
        
        .info-icon.wrench::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 8px;
            height: 8px;
            background: var(--logo-yellow);
            border-radius: 50%;
            margin-top: -8px;
            margin-left: 8px;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .info-card h4 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .info-card p {
            font-size: 1rem;
            line-height: 1.6;
            color: #34495e;
            margin: 0;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .fleet-title {
                font-size: 2.5rem;
            }
            
            .fleet-subtitle {
                font-size: 1.1rem;
            }
            
            .fleet-gallery {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            }
            
            .gallery-image {
                height: 150px;
            }
            
            .fleet-info {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .info-card {
                padding: 25px;
            }
            
            .info-icon {
                width: 50px;
                height: 50px;
            }
            
            .info-icon.vehicle::before {
                width: 35px;
                height: 18px;
            }
            
            .info-icon.vehicle::after {
                width: 6px;
                height: 6px;
            }
            
            .info-icon.shield::before {
                border-left: 15px solid transparent;
                border-right: 15px solid transparent;
                border-bottom: 25px solid #3498db;
            }
            
            .info-icon.shield::after {
                width: 25px;
                height: 15px;
            }
            
            .info-icon.wrench::before {
                width: 25px;
                height: 3px;
            }
            
            .info-icon.wrench::after {
                width: 6px;
                height: 6px;
            }
            
            .info-card h4 {
                font-size: 1.3rem;
            }
        }
        
        @media (max-width: 480px) {
            .fleet-gallery {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            
            .gallery-image {
                height: 120px;
            }
        }
        
        /* Seção Unidades */
        .units-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            padding: 80px 0;
            position: relative;
        }
        
        .units-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, #e9ecef, transparent);
        }
        
        .units-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            text-align: center;
        }
        
        .units-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 15px;
            line-height: 1.2;
        }
        
        .units-subtitle {
            font-size: 1.1rem;
            font-weight: 600;
            color: #f39c12;
            margin-bottom: 30px;
            position: relative;
        }
        
        .units-subtitle::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(90deg, #f39c12, #e67e22);
            border-radius: 2px;
        }
        
        .units-intro {
            max-width: 800px;
            margin: 0 auto 60px auto;
            text-align: center;
        }
        
        .units-intro p {
            font-size: 1.2rem;
            line-height: 1.7;
            color: #34495e;
            margin-bottom: 20px;
        }
        
        .units-intro p:last-child {
            margin-bottom: 0;
        }
        
        /* Grid de Unidades */
        .units-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 40px;
            margin-bottom: 60px;
        }
        
        .unit-card {
            background: white;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
        }
        
        .unit-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.15);
        }
        
        .unit-image {
            position: relative;
            height: auto;
            overflow: hidden;
        }
        
        .unit-img {
            width: 100%;
            height: auto;
            object-fit: contain;
            transition: transform 0.4s ease;
        }
        
        .unit-card:hover .unit-img {
            transform: scale(1.05);
        }
        
        .unit-overlay {
            position: absolute;
            top: 20px;
            right: 20px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .unit-card:hover .unit-overlay {
            opacity: 1;
        }
        
        .unit-badge {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.9rem;
            box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3);
        }
        
        .unit-info {
            padding: 30px;
            text-align: left;
        }
        
        .unit-info h4 {
            font-size: 1.4rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 12px;
            text-align: center;
        }
        
        .unit-description {
            font-size: 1.1rem;
            line-height: 1.6;
            color: #34495e;
            margin-bottom: 25px;
            text-align: center;
        }
        
        .unit-features {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 15px;
            background: rgba(52, 152, 219, 0.1);
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .feature-item:hover {
            background: rgba(52, 152, 219, 0.2);
            transform: translateX(5px);
        }
        
        .feature-icon {
            font-size: 1rem;
            font-weight: bold;
            color: #f39c12;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(243, 156, 18, 0.1);
            border-radius: 50%;
            flex-shrink: 0;
        }
        
        .feature-item span:last-child {
            font-weight: 600;
            color: #2c3e50;
        }
        
        /* CTA das Unidades */
        .units-cta {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 50px;
            border-radius: 25px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .units-cta::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            animation: slideUnits 3s ease-in-out infinite;
        }
        
        @keyframes slideUnits {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .units-cta h4 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 15px;
            position: relative;
            z-index: 2;
        }
        
        .units-cta p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.9;
            position: relative;
            z-index: 2;
        }
        
        .units-button {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
            padding: 18px 35px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1rem;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 8px 25px rgba(243, 156, 18, 0.4);
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
        }
        
        .units-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(243, 156, 18, 0.6);
            text-decoration: none;
            color: white;
        }
        
        .button-icon {
            font-size: 1.2rem;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .units-title {
                font-size: 2rem;
            }
            
            .units-subtitle {
                font-size: 1rem;
            }
            
            .units-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .unit-card {
                margin: 0 10px;
            }
            
            .unit-image {
                height: auto;
            }
            
            .unit-info {
                padding: 25px;
            }
            
            .unit-info h4 {
                font-size: 1.2rem;
            }
            
            .unit-description {
                font-size: 1rem;
            }
            
            .units-cta {
                padding: 40px 30px;
            }
            
            .units-cta h4 {
                font-size: 1.4rem;
            }
            
            .units-cta p {
                font-size: 1rem;
            }
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
            text-decoration: none;
            color: white;
        }
        
        .btn-secondary {
            background: transparent;
            color: white;
            padding: 15px 30px;
            border: 2px solid white;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: white;
            color: #667eea;
            text-decoration: none;
        }
        
        /* Seções de Conteúdo */
        .content-section {
            padding: 80px 0;
        }
        
        .content-section:nth-child(even) {
            background: #f8f9fa;
        }
        
        .content-section .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .section-title {
            text-align: center;
            font-size: 36px;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        
        .section-subtitle {
            text-align: center;
            font-size: 18px;
            color: #7f8c8d;
            margin-bottom: 50px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Cards de Serviços */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }
        
        .service-card {
            background: white;
            padding: 40px 30px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 1px solid #e1e5e9;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .service-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
            color: white;
        }
        
        .service-card h3 {
            font-size: 24px;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        
        .service-card p {
            color: #7f8c8d;
            line-height: 1.6;
        }
        
        
        /* Responsivo */
        @media (max-width: 768px) {
            .header-info .container {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .main-header .container {
                flex-direction: column;
                gap: 20px;
            }
            
            .nav-menu {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .login-btn {
                padding: 10px 16px;
                font-size: 13px;
            }
            
            .logo-image {
                width: 70px !important;
                height: 70px !important;
            }
            
            .logo-text {
                font-size: 16px;
            }
            
            .logo-subtitle {
                font-size: 10px;
            }
            
            .hero h1 {
                font-size: 32px;
            }
            
            .hero p {
                font-size: 18px;
            }
            
            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .section-title {
                font-size: 28px;
            }
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
            width: 8px;
            height: 6px;
            border: 1.5px solid var(--logo-blue);
            border-radius: 2px;
        }
        
        .contact-item .contact-icon.phone::after {
            width: 3px;
            height: 3px;
            background: var(--logo-blue);
            border-radius: 50%;
            margin-top: -1px;
            margin-left: 4px;
        }
        
        .contact-item .contact-icon.email::before {
            width: 10px;
            height: 7px;
            border: 1.5px solid var(--logo-blue);
            border-radius: 1px;
        }
        
        .contact-item .contact-icon.email::after {
            width: 0;
            height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-top: 3px solid var(--logo-blue);
            margin-top: -2px;
        }
        
        .contact-item .contact-icon.location::before {
            width: 0;
            height: 0;
            border-left: 4px solid transparent;
            border-right: 4px solid transparent;
            border-bottom: 8px solid var(--logo-blue);
            margin-top: -1px;
        }
        
        .contact-item .contact-icon.location::after {
            width: 6px;
            height: 6px;
            background: var(--logo-blue);
            border-radius: 50%;
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
        
        .scroll-icon {
            width: 0;
            height: 0;
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-bottom: 12px solid var(--logo-yellow);
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
        
        .whatsapp-button,
        .call-button {
            width: auto !important;
            height: auto !important;
            max-width: none !important;
            max-height: none !important;
            margin: 0 !important;
            padding: 15px 25px !important;
            border: none !important;
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            white-space: nowrap !important;
        }
        
        .scroll-top {
            width: 50px !important;
            height: 50px !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
        }
        
        /* Seção Formação Completa */
        .formacao-completa-section {
            padding: 80px 0;
            background: linear-gradient(135deg, var(--light-color) 0%, #e2e8f0 100%);
        }
        
        .formacao-content {
            text-align: center;
        }
        
        .formacao-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--logo-blue);
            margin-bottom: 3rem;
            text-align: center;
        }
        
        .formacao-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .formacao-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            text-align: center;
            border: 2px solid transparent;
        }
        
        .formacao-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            border-color: var(--logo-green);
        }
        
        .formacao-card .card-icon {
            margin-bottom: 1.5rem;
        }
        
        .formacao-card .icon-circle {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--logo-green) 0%, #2f855a 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            box-shadow: 0 8px 20px rgba(56, 161, 105, 0.3);
        }
        
        .formacao-card .icon-text {
            font-size: 2rem;
            color: white;
        }
        
        .formacao-card h4 {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--logo-blue);
            margin-bottom: 1rem;
        }
        
        .formacao-card p {
            color: #666;
            line-height: 1.6;
            font-size: 1rem;
        }
        
        /* Responsividade para Formação Completa */
        @media (max-width: 768px) {
            .formacao-title {
                font-size: 2rem;
                margin-bottom: 2rem;
            }
            
            .formacao-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .formacao-card {
                padding: 1.5rem;
            }
            
            .formacao-card .icon-circle {
                width: 60px;
                height: 60px;
            }
            
            .formacao-card .icon-text {
                font-size: 1.5rem;
            }
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
                    <li><a href="#home" class="active">HOME</a></li>
                    <li><a href="#formacao-completa">SOBRE</a></li>
                    <li><a href="#servicos">SERVIÇOS</a></li>
                    <li><a href="#trabalhe">TRABALHE CONOSCO</a></li>
                    <li><a href="#contato">CONTATO</a></li>
                </ul>
            </nav>
            
            <a href="login.php" class="login-btn" title="Acessar Sistema">
                🔑 Entrar
            </a>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <!-- Apenas a imagem do banner, sem texto adicional -->
    </section>

    <!-- Seção de Navegação -->
    <section class="navigation-section">
        <div class="container">
            <div class="nav-grid">
                <div class="nav-item">
                    <a href="#formacao-completa" class="nav-link">
                        <img src="assets/img/historia.png" alt="História" class="nav-image">
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#contato" class="nav-link">
                        <img src="assets/img/matricula.png" alt="Matrícula" class="nav-image">
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#estrutura" class="nav-link">
                        <img src="assets/img/estrutura.png" alt="Estrutura" class="nav-image">
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#frota" class="nav-link">
                        <img src="assets/img/frota.png" alt="Frota" class="nav-image">
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#servicos" class="nav-link">
                        <img src="assets/img/reonovacao.png" alt="Renovação CNH" class="nav-image">
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#contato" class="nav-link">
                        <img src="assets/img/unidades.png" alt="Unidades" class="nav-image">
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Seção Formação Completa -->
    <section class="formacao-completa-section" id="formacao-completa">
        <div class="container">
            <div class="formacao-content">
                <h2 class="formacao-title">Tudo que você precisa para uma formação completa</h2>
                <div class="formacao-grid">
                    <div class="formacao-card">
                        <div class="card-icon">
                            <div class="icon-circle">
                                <span class="icon-text">📚</span>
                            </div>
                        </div>
                        <div class="card-content">
                            <h4>Aulas Teóricas</h4>
                            <p>Conteúdo completo sobre legislação de trânsito, direção defensiva e primeiros socorros.</p>
                        </div>
                    </div>
                    <div class="formacao-card">
                        <div class="card-icon">
                            <div class="icon-circle">
                                <span class="icon-text">🚗</span>
                            </div>
                        </div>
                        <div class="card-content">
                            <h4>Aulas Práticas</h4>
                            <p>Treinamento prático com instrutores qualificados em veículos modernos e seguros.</p>
                        </div>
                    </div>
                    <div class="formacao-card">
                        <div class="card-icon">
                            <div class="icon-circle">
                                <span class="icon-text">📋</span>
                            </div>
                        </div>
                        <div class="card-content">
                            <h4>Documentação</h4>
                            <p>Orientamos todo o processo de documentação para sua habilitação.</p>
                        </div>
                    </div>
                    <div class="formacao-card">
                        <div class="card-icon">
                            <div class="icon-circle">
                                <span class="icon-text">🎯</span>
                            </div>
                        </div>
                        <div class="card-content">
                            <h4>Simulados</h4>
                            <p>Testes práticos para prepará-lo para os exames oficiais do DETRAN.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Seção Apresentação -->
    <section class="presentation-section">
        <div class="container">
            <div class="presentation-content">
                <div class="text-content">
                    <h2 class="main-title">Formando Motoristas Responsáveis</h2>
                    <h3 class="subtitle">Conquiste Sua Liberdade no Trânsito</h3>
                    
                    <div class="text-blocks">
                        <div class="text-block">
                            <p class="lead-text">Na <strong>Auto Escola Bom Conselho</strong>, nosso compromisso é mais do que ensinar a dirigir — é formar motoristas conscientes e preparados para enfrentar os desafios do trânsito com segurança.</p>
                </div>
                
                        <div class="text-block">
                            <p>Com anos de experiência e uma equipe de instrutores qualificados, oferecemos treinamento de alta qualidade para todas as categorias de habilitação:</p>
                            <div class="categories">
                                <span class="category-badge">A</span>
                                <span class="category-badge">B</span>
                                <span class="category-badge">C</span>
                                <span class="category-badge">D</span>
                                <span class="category-badge">E</span>
                            </div>
                </div>
                
                        <div class="highlight-block">
                            <p>Além disso, disponibilizamos condições facilitadas, como <strong>parcelamento em até 18x</strong> e um ambiente de aprendizado acolhedor.</p>
                </div>
                        
                        <div class="text-block">
                            <p>Seja para iniciar sua jornada no volante ou para aprimorar suas habilidades, estamos prontos para ajudar você a conquistar a tão desejada CNH.</p>
                        </div>
                        
                        <div class="call-to-action">
                            <p class="cta-text">Faça já sua matrícula e dê o primeiro passo rumo à liberdade e segurança no trânsito.</p>
                            <a href="#contato" class="cta-button">
                                <span class="cta-icon">📞</span>
                                Fale Conosco Agora
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Seção Estrutura -->
    <section class="structure-section">
        <div class="container">
            <div class="structure-content">
                <div class="structure-image">
                    <img src="assets/img/cfc-bom-conselho.png" alt="CFC Bom Conselho" class="main-image">
                </div>
                
                <div class="structure-text">
                    <h2 class="structure-title">Tudo que você precisa para uma formação completa</h2>
                    <h3 class="structure-subtitle">Estrutura de Qualidade para Sua Formação</h3>
                    
                    <div class="accordion">
                        <div class="accordion-item">
                            <div class="accordion-header">
                                <h4><span class="professional-icon">●</span> Veículos Modernos e Bem-Mantidos</h4>
                                <span class="accordion-icon">+</span>
                            </div>
                            <div class="accordion-content">
                                <p>Nossa frota de veículos é constantemente atualizada e revisada para garantir a máxima segurança e conforto durante as aulas práticas.</p>
                            </div>
                </div>
                
                        <div class="accordion-item">
                            <div class="accordion-header">
                                <h4><span class="professional-icon">●</span> Sala de Aula Confortável e Tecnológica</h4>
                                <span class="accordion-icon">+</span>
                            </div>
                            <div class="accordion-content">
                                <p>Oferecemos um ambiente de aprendizado agradável, com salas de aula climatizadas, equipadas com recursos audiovisuais modernos que facilitam a compreensão das aulas teóricas.</p>
                            </div>
                </div>
                
                        <div class="accordion-item">
                            <div class="accordion-header">
                                <h4><span class="professional-icon">●</span> Simulador de Direção</h4>
                                <span class="accordion-icon">+</span>
                            </div>
                            <div class="accordion-content">
                                <p>Antes de ir para as ruas, você tem a oportunidade de praticar em nossos simuladores de última geração, proporcionando uma experiência realista e segura.</p>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <div class="accordion-header">
                                <h4><span class="professional-icon">●</span> Instrutores Qualificados</h4>
                                <span class="accordion-icon">+</span>
                            </div>
                            <div class="accordion-content">
                                <p>Contamos com uma equipe de instrutores experientes e certificados, que estão sempre prontos para oferecer o melhor em treinamento teórico e prático.</p>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <div class="accordion-header">
                                <h4><span class="professional-icon">●</span> Horários Flexíveis</h4>
                                <span class="accordion-icon">+</span>
                            </div>
                            <div class="accordion-content">
                                <p>Entendemos as suas necessidades e oferecemos uma ampla variedade de horários para as aulas, facilitando a conciliação com suas outras atividades diárias.</p>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <div class="accordion-header">
                                <h4><span class="professional-icon">●</span> Ambiente Acolhedor e Seguro</h4>
                                <span class="accordion-icon">+</span>
                            </div>
                            <div class="accordion-content">
                                <p>Nossa autoescola é projetada para ser um espaço onde você se sinta seguro e bem acolhido, com toda a estrutura necessária para focar no aprendizado.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Seção Serviços -->
    <section class="services-section" id="servicos">
        <div class="container">
            <div class="services-content">
                <h2 class="services-title">Serviços Completos para Sua CNH</h2>
                <h3 class="services-subtitle">Desde a Primeira Habilitação até a Renovação e Reciclagem</h3>
            
            <div class="services-grid">
                    <div class="service-card animated-card">
                        <div class="card-icon">
                            <div class="icon-circle">
                                <span class="icon-text">CNH</span>
                            </div>
                        </div>
                        <div class="card-content">
                            <h4>Primeira Habilitação</h4>
                            <p>Oferecemos tudo o que você precisa para conquistar sua primeira carteira de habilitação, com cursos práticos, teóricos e serviços personalizados.</p>
                        </div>
                        <div class="card-glow"></div>
                </div>
                
                    <div class="service-card animated-card">
                        <div class="card-icon">
                            <div class="icon-circle">
                                <span class="icon-text">🔄</span>
                            </div>
                        </div>
                        <div class="card-content">
                            <h4>Renovação</h4>
                            <p>Processo simplificado para renovação da sua CNH, com toda a documentação necessária e acompanhamento completo.</p>
                        </div>
                        <div class="card-glow"></div>
                </div>
                
                    <div class="service-card animated-card">
                        <div class="card-icon">
                            <div class="icon-circle">
                                <span class="icon-text">📚</span>
                            </div>
                        </div>
                        <div class="card-content">
                            <h4>Reciclagem</h4>
                            <p>Cursos de reciclagem para condutores infratores, com metodologia atualizada e instrutores especializados.</p>
                        </div>
                        <div class="card-glow"></div>
                    </div>
                </div>
                
                <div class="services-highlight">
                    <div class="highlight-content">
                        <div class="highlight-icon">💰</div>
                        <div class="highlight-text">
                            <h4>Condições Facilitadas</h4>
                            <p>Parcelamento em até <strong>18x</strong> e ambiente de aprendizado acolhedor para sua comodidade.</p>
                        </div>
                    </div>
                    <div class="highlight-animation"></div>
                </div>
                
                   <div class="services-offered">
                       <h4>Serviços Atendidos</h4>
                       <div class="services-tags-grid">
                           <span class="service-tag">Primeira Habilitação</span>
                           <span class="service-tag">Categorias A, B, C, D, E</span>
                           <span class="service-tag">Adição/Alteração</span>
                           <span class="service-tag">Renovação CNH</span>
                           <span class="service-tag">Reciclagem</span>
                           <span class="service-tag">Aulas Teóricas</span>
                           <span class="service-tag">Aulas Práticas</span>
                           <span class="service-tag">Simulados</span>
                           <span class="service-tag">Exames</span>
                           <span class="service-tag">Psicotécnico</span>
                           <span class="service-tag">Documentação</span>
                           <span class="service-tag">Consultoria</span>
                       </div>
                </div>
                
                   <div class="services-cta">
                       <div class="cta-content">
                           <h4>Pronto para Começar?</h4>
                           <p>Faça já sua matrícula e dê o primeiro passo rumo à liberdade e segurança no trânsito.</p>
                           <a href="#contato" class="cta-button">
                               <span class="button-icon">📞</span>
                               <span class="button-text">Fale Conosco Agora</span>
                               <div class="button-ripple"></div>
                           </a>
                       </div>
                   </div>
            </div>
        </div>
    </section>

    <!-- Seção Frota -->
    <section class="fleet-section" id="frota">
        <div class="container">
            <div class="fleet-content">
                <h2 class="fleet-title">Frota Moderna e Pátio Completo</h2>
                <h3 class="fleet-subtitle">Treinamento Prático com Veículos Atualizados em um Ambiente Seguro</h3>
                
                <div class="fleet-gallery">
                    <div class="gallery-item" data-image="1">
                        <img src="assets/img/frota01.jpg" alt="Veículo da Frota 1" class="gallery-image">
                        <div class="gallery-overlay">
                            <div class="gallery-icon">🔍</div>
                        </div>
                    </div>
                    
                    <div class="gallery-item" data-image="2">
                        <img src="assets/img/frota02.jpg" alt="Veículo da Frota 2" class="gallery-image">
                        <div class="gallery-overlay">
                            <div class="gallery-icon">🔍</div>
                        </div>
                    </div>
                    
                    <div class="gallery-item" data-image="3">
                        <img src="assets/img/frota03.jpg" alt="Veículo da Frota 3" class="gallery-image">
                        <div class="gallery-overlay">
                            <div class="gallery-icon">🔍</div>
                        </div>
                    </div>
                    
                    <div class="gallery-item" data-image="4">
                        <img src="assets/img/frota04.jpg" alt="Veículo da Frota 4" class="gallery-image">
                        <div class="gallery-overlay">
                            <div class="gallery-icon">🔍</div>
                        </div>
                    </div>
                    
                    <div class="gallery-item" data-image="5">
                        <img src="assets/img/frota05.jpg" alt="Veículo da Frota 5" class="gallery-image">
                        <div class="gallery-overlay">
                            <div class="gallery-icon">🔍</div>
                        </div>
                    </div>
                    
                    <div class="gallery-item" data-image="6">
                        <img src="assets/img/frota06.jpg" alt="Veículo da Frota 6" class="gallery-image">
                        <div class="gallery-overlay">
                            <div class="gallery-icon">🔍</div>
                        </div>
                    </div>
                    
                    <div class="gallery-item" data-image="7">
                        <img src="assets/img/frota07.jpg" alt="Veículo da Frota 7" class="gallery-image">
                        <div class="gallery-overlay">
                            <div class="gallery-icon">🔍</div>
                        </div>
                    </div>
                    
                    <div class="gallery-item" data-image="8">
                        <img src="assets/img/frota08.jpg" alt="Veículo da Frota 8" class="gallery-image">
                        <div class="gallery-overlay">
                            <div class="gallery-icon">🔍</div>
                        </div>
                    </div>
                </div>
                
                <div class="fleet-info">
                    <div class="info-card">
                        <div class="info-icon vehicle"></div>
                        <h4>Veículos Modernos</h4>
                        <p>Frota constantemente atualizada com os mais recentes modelos e tecnologias de segurança.</p>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-icon shield"></div>
                        <h4>Ambiente Seguro</h4>
                        <p>Pátio amplo e seguro, projetado para proporcionar o melhor ambiente de aprendizado.</p>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-icon wrench"></div>
                        <h4>Manutenção Regular</h4>
                        <p>Todos os veículos passam por manutenção preventiva e revisões periódicas.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Seção Unidades -->
    <section class="units-section" id="unidades">
        <div class="container">
            <div class="units-content">
                <h2 class="units-title">Estamos Prontos para Atender Você</h2>
                <h3 class="units-subtitle">Com a Melhor Formação para Motoristas em Pernambuco</h3>
                
                <div class="units-intro">
                    <p>Conheça Nossas Unidades: <strong>Garanhuns e Canhotinho</strong></p>
                    <p>Contamos com duas unidades de formação de condutores: a moderna unidade em Garanhuns, que tem mais de 50 anos de tradição e excelência no ensino e nossa renomada unidade em Canhotinho.</p>
                </div>
                
                <div class="units-grid">
                    <div class="unit-card">
                        <div class="unit-image">
                            <img src="assets/img/CFC-Garanhuns.png" alt="CFC Garanhuns" class="unit-img">
                            <div class="unit-overlay">
                                <div class="unit-badge">50+ Anos</div>
                            </div>
                        </div>
                        <div class="unit-info">
                            <h4>CFC Garanhuns</h4>
                            <p class="unit-description">Unidade principal com mais de 50 anos de tradição e excelência no ensino. Estrutura moderna e completa para sua formação.</p>
                            <div class="unit-features">
                                <div class="feature-item">
                                    <span class="feature-icon">●</span>
                                    <span>Estrutura Moderna</span>
                                </div>
                                <div class="feature-item">
                                    <span class="feature-icon">●</span>
                                    <span>Instrutores Experientes</span>
                                </div>
                                <div class="feature-item">
                                    <span class="feature-icon">●</span>
                                    <span>Frota Atualizada</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="unit-card">
                        <div class="unit-image">
                            <img src="assets/img/cfc-canhotinho.png" alt="CFC Canhotinho" class="unit-img">
                            <div class="unit-overlay">
                                <div class="unit-badge">Renomada</div>
                            </div>
                        </div>
                        <div class="unit-info">
                            <h4>CFC Canhotinho</h4>
                            <p class="unit-description">Nossa renomada unidade em Canhotinho, oferecendo qualidade e excelência no ensino para toda a região.</p>
                            <div class="unit-features">
                                <div class="feature-item">
                                    <span class="feature-icon">●</span>
                                    <span>Qualidade Reconhecida</span>
                                </div>
                                <div class="feature-item">
                                    <span class="feature-icon">●</span>
                                    <span>Metodologia Diferenciada</span>
                                </div>
                                <div class="feature-item">
                                    <span class="feature-icon">●</span>
                                    <span>Excelência no Ensino</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                   <div class="units-contact-section">
                       <div class="contact-form-column">
                           <div class="contact-form-container">
                               <h4>Entre em Contato</h4>
                               <p>Preencha o formulário e entraremos em contato em breve!</p>
                               
                               <form class="contact-form" onsubmit="sendToWhatsApp(event)">
                                   <div class="form-group">
                                       <label for="nome">Nome Completo *</label>
                                       <input type="text" id="nome" name="nome" required>
                                   </div>
                                   
                                   <div class="form-group">
                                       <label for="telefone">Telefone/WhatsApp *</label>
                                       <input type="tel" id="telefone" name="telefone" required>
                                   </div>
                                   
                                   <div class="form-group">
                                       <label for="email">E-mail</label>
                                       <input type="email" id="email" name="email">
                                   </div>
                                   
                                   <div class="form-group">
                                       <label for="servico">Tipo de Serviço *</label>
                                       <select id="servico" name="servico" required>
                                           <option value="">Selecione o serviço</option>
                                           <option value="primeira-habilitacao">Primeira Habilitação</option>
                                           <option value="categoria-a">Categoria A (Moto)</option>
                                           <option value="categoria-b">Categoria B (Carro)</option>
                                           <option value="categoria-c">Categoria C (Caminhão)</option>
                                           <option value="categoria-d">Categoria D (Ônibus)</option>
                                           <option value="categoria-e">Categoria E (Carreta)</option>
                                           <option value="adicao-categoria">Adição de Categoria</option>
                                           <option value="alteracao-categoria">Alteração de Categoria</option>
                                           <option value="aula-avulsa">Aula Avulsa</option>
                                           <option value="renovacao-cnh">Renovação CNH</option>
                                           <option value="reciclagem">Reciclagem</option>
                                           <option value="simulados">Simulados</option>
                                       </select>
                                   </div>
                                   
                                   <div class="form-group">
                                       <label for="cidade">Cidade *</label>
                                       <input type="text" id="cidade" name="cidade" placeholder="Ex: Bom Conselho, Garanhuns, etc." required>
                                   </div>
                                   
                                   <div class="form-group">
                                       <label for="mensagem">Mensagem</label>
                                       <textarea id="mensagem" name="mensagem" rows="4" placeholder="Conte-nos mais sobre suas necessidades..."></textarea>
                                   </div>
                                   
                                   <button type="submit" class="submit-button">
                                       <span class="button-icon">📱</span>
                                       <span class="button-text">Enviar via WhatsApp</span>
                                   </button>
                               </form>
                           </div>
                       </div>
                       
                       <div class="units-info-column">
                           <div class="units-info-container">
                               <h4>Entre em Contato</h4>
                               <p>Estamos prontos para atendê-lo com excelência!</p>
                               
                               <div class="centralized-info">
                                   <div class="info-highlight">
                                       <div class="highlight-icon">🏢</div>
                                       <div class="highlight-content">
                                           <h5>CFC Bom Conselho</h5>
                                           <p>Sua auto escola de confiança</p>
                                       </div>
                                   </div>
                                   
                                   <div class="coverage-info">
                                       <h5>Atendemos toda a região:</h5>
                                       <div class="cities-list">
                                           <span class="city-tag">Bom Conselho</span>
                                           <span class="city-tag">Garanhuns</span>
                                           <span class="city-tag">Canhotinho</span>
                                           <span class="city-tag">Palmeirina</span>
                                           <span class="city-tag">Correntes</span>
                                           <span class="city-tag">Lajedo</span>
                                           <span class="city-tag">São Bento</span>
                                           <span class="city-tag">Calçado</span>
                                           <span class="city-tag">E outras cidades</span>
                                       </div>
                                   </div>
                               </div>
                               
                               <div class="contact-info">
                                   <h5>Informações de Contato</h5>
                                   <div class="contact-item">
                                       <span class="contact-icon phone"></span>
                                       <span>Telefone: (87) 98145-0308</span>
                                   </div>
                                   <div class="contact-item">
                                       <span class="contact-icon whatsapp"></span>
                                       <span>WhatsApp: (87) 98145-0308</span>
                                   </div>
                                   <div class="contact-item">
                                       <span class="contact-icon email"></span>
                                       <span>E-mail: contato@cfcbomconselho.com.br</span>
                                   </div>
                                   <div class="contact-item">
                                       <span class="contact-icon location"></span>
                                       <span>Endereço: Bom Conselho - PE</span>
                                   </div>
                               </div>
                               
                           </div>
                       </div>
                </div>
            </div>
        </div>
    </section>

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
                        <li><a href="#home">Home</a></li>
                        <li><a href="#servicos">Serviços</a></li>
                        <li><a href="#trabalhe-conosco">Trabalhe Conosco</a></li>
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

    <script>
        // Função para enviar formulário para WhatsApp
        function sendToWhatsApp(event) {
            event.preventDefault();
            
            const nome = document.getElementById('nome').value;
            const telefone = document.getElementById('telefone').value;
            const email = document.getElementById('email').value;
            const servico = document.getElementById('servico').value;
            const cidade = document.getElementById('cidade').value;
            const mensagem = document.getElementById('mensagem').value;
            
            // Montar mensagem para WhatsApp
            let whatsappMessage = `*CFC Bom Conselho - Novo Contato*\n\n`;
            whatsappMessage += `*Nome:* ${nome}\n`;
            whatsappMessage += `*Telefone:* ${telefone}\n`;
            if (email) whatsappMessage += `*E-mail:* ${email}\n`;
            whatsappMessage += `*Serviço:* ${servico}\n`;
            whatsappMessage += `*Cidade:* ${cidade}\n`;
            if (mensagem) whatsappMessage += `*Mensagem:* ${mensagem}\n`;
            
            // Número do WhatsApp (87) 98145-0308
            const whatsappNumber = '5587981450308';
            const encodedMessage = encodeURIComponent(whatsappMessage);
            const whatsappURL = `https://wa.me/${whatsappNumber}?text=${encodedMessage}`;
            
            // Abrir WhatsApp
            window.open(whatsappURL, '_blank');
        }
        
        // Smooth scrolling para links âncora
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Destacar item ativo no menu
        window.addEventListener('scroll', function() {
            const sections = document.querySelectorAll('section[id]');
            const navLinks = document.querySelectorAll('.nav-menu a');
            
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (scrollY >= (sectionTop - 200)) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                }
            });
        });
        
        // Accordion functionality
        const accordionItems = document.querySelectorAll('.accordion-item');
        
        accordionItems.forEach(item => {
            const header = item.querySelector('.accordion-header');
            
            header.addEventListener('click', function() {
                const isActive = item.classList.contains('active');
                
                // Close all accordion items
                accordionItems.forEach(accordionItem => {
                    accordionItem.classList.remove('active');
                });
                
                // Open clicked item if it wasn't active
                if (!isActive) {
                    item.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>