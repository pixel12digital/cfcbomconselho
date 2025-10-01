<?php
// =====================================================
// PÁGINA TRABALHE CONOSCO - CFC BOM CONSELHO
// =====================================================

require_once 'includes/config.php';
require_once 'includes/database.php';

// Processar formulário se foi enviado
$mensagem_sucesso = '';
$mensagem_erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = db();
        
        // Validar dados
        $nome_completo = trim($_POST['nome_completo'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $categoria_cnh = trim($_POST['categoria_cnh'] ?? '');
        $escolaridade = trim($_POST['escolaridade'] ?? '');
        $endereco_rua = trim($_POST['endereco_rua'] ?? '');
        $cidade = trim($_POST['cidade'] ?? '');
        $estado = trim($_POST['estado'] ?? '');
        $cep = trim($_POST['cep'] ?? '');
        $pais = trim($_POST['pais'] ?? 'Brasil');
        $indicacoes = trim($_POST['indicacoes'] ?? '');
        $mensagem = trim($_POST['mensagem'] ?? '');
        $vaga_id = intval($_POST['vaga_id'] ?? 0);
        
        // Validações básicas
        if (empty($nome_completo) || empty($email)) {
            throw new Exception('Nome completo e e-mail são obrigatórios.');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('E-mail inválido.');
        }
        
        // Processar upload de arquivos
        $curriculo_arquivo = '';
        $foto_arquivo = '';
        
        // Criar diretório de uploads se não existir
        $upload_dir = 'uploads/candidatos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Upload do currículo
        if (isset($_FILES['curriculo']) && $_FILES['curriculo']['error'] === UPLOAD_ERR_OK) {
            $curriculo_info = pathinfo($_FILES['curriculo']['name']);
            $curriculo_ext = strtolower($curriculo_info['extension']);
            $allowed_extensions = ['pdf', 'doc', 'docx'];
            
            if (in_array($curriculo_ext, $allowed_extensions)) {
                $curriculo_filename = 'curriculo_' . time() . '_' . uniqid() . '.' . $curriculo_ext;
                $curriculo_path = $upload_dir . $curriculo_filename;
                
                if (move_uploaded_file($_FILES['curriculo']['tmp_name'], $curriculo_path)) {
                    $curriculo_arquivo = $curriculo_path;
                }
            }
        }
        
        // Upload da foto
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $foto_info = pathinfo($_FILES['foto']['name']);
            $foto_ext = strtolower($foto_info['extension']);
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($foto_ext, $allowed_extensions)) {
                $foto_filename = 'foto_' . time() . '_' . uniqid() . '.' . $foto_ext;
                $foto_path = $upload_dir . $foto_filename;
                
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $foto_path)) {
                    $foto_arquivo = $foto_path;
                }
            }
        }
        
        // Inserir candidato no banco
        $sql = "INSERT INTO candidatos (
            nome_completo, email, whatsapp, telefone, categoria_cnh, escolaridade,
            endereco_rua, cidade, estado, cep, pais, indicacoes, mensagem,
            curriculo_arquivo, foto_arquivo, vaga_id, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'novo')";
        
        $params = [
            $nome_completo, $email, $whatsapp, $telefone, $categoria_cnh, $escolaridade,
            $endereco_rua, $cidade, $estado, $cep, $pais, $indicacoes, $mensagem,
            $curriculo_arquivo, $foto_arquivo, $vaga_id
        ];
        
        $db->query($sql, $params);
        
        $mensagem_sucesso = 'Candidatura enviada com sucesso! Entraremos em contato em breve.';
        
    } catch (Exception $e) {
        $mensagem_erro = 'Erro ao enviar candidatura: ' . $e->getMessage();
    }
}

// Buscar vagas ativas
try {
    $db = db();
    $vagas = $db->fetchAll("SELECT * FROM vagas WHERE status = 'ativa' ORDER BY titulo");
} catch (Exception $e) {
    $vagas = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trabalhe Conosco - CFC Bom Conselho</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="assets/css/mobile-first.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --logo-blue: #1a365d;
            --logo-yellow: #fbbf24;
            --text-dark: #2d3748;
            --text-light: #718096;
            --bg-light: #f7fafc;
            --border-color: #e2e8f0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        
        /* Header Info */
        .header-info {
            background: #2d3748;
            color: white;
            padding: 8px 0;
            font-size: 14px;
        }
        
        .header-info .container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: center;
            gap: 30px;
            padding: 0 20px;
        }
        
        .header-info .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .header-info .info-item span:first-child {
            font-size: 16px;
        }
        
        /* Header Principal */
        .main-header {
            background: var(--logo-blue);
            color: white;
            padding: 20px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .main-header .container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            position: relative;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logo-image {
            width: 90px !important;
            height: auto;
            max-width: 90px;
        }
        
        .logo-text {
            font-size: 18px;
            font-weight: 700;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1px;
            line-height: 1.2;
        }
        
        .logo-subtitle {
            font-size: 12px;
            color: #e2e8f0;
            font-weight: 400;
            text-transform: none;
            letter-spacing: 0.5px;
        }
        
        .nav-menu {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 30px;
        }
        
        .nav-menu li a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: color 0.3s ease;
            padding: 8px 0;
        }
        
        .nav-menu li a:hover,
        .nav-menu li a.active {
            color: var(--logo-yellow);
        }
        
        .arrow-btn {
            background: var(--logo-yellow);
            color: var(--logo-blue);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 16px;
        }
        
        .arrow-btn:hover {
            background: #f59e0b;
            transform: translateY(-2px);
        }
        
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            padding: 8px;
        }
        
        /* Menu Mobile */
        .mobile-menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }
        
        .mobile-menu {
            position: fixed;
            top: 0;
            right: -300px;
            width: 300px;
            height: 100%;
            background: var(--logo-blue);
            z-index: 1000;
            transition: right 0.3s ease;
            overflow-y: auto;
        }
        
        .mobile-menu.active {
            right: 0;
        }
        
        .mobile-menu-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .mobile-menu-close {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
        }
        
        .mobile-nav-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .mobile-nav-link {
            display: block;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: background 0.3s ease;
        }
        
        .mobile-nav-link:hover,
        .mobile-nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: var(--logo-yellow);
        }
        
        .mobile-menu-nav {
            padding: 20px 0;
        }
        
        .mobile-menu-nav a {
            display: block;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: background 0.3s ease;
            font-weight: 500;
        }
        
        .mobile-menu-nav a:hover,
        .mobile-menu-nav a.active {
            background: rgba(255, 255, 255, 0.1);
            color: var(--logo-yellow);
        }
        
        .mobile-menu-contact {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 20px;
        }
        
        .mobile-menu-contact .contact-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            color: white;
        }
        
        .mobile-menu-contact .contact-item i {
            color: var(--logo-yellow);
            width: 20px;
        }
        
        .mobile-menu-contact .contact-item a {
            color: white;
            text-decoration: none;
            padding: 0;
            border: none;
        }
        
        .mobile-menu-contact .contact-item a:hover {
            color: var(--logo-yellow);
            background: none;
        }
        
        /* Botões Flutuantes */
        .floating-buttons {
            position: fixed !important;
            bottom: 20px !important;
            right: 20px !important;
            z-index: 1000 !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 15px !important;
        }
        
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
        
        .floating-btn:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2) !important;
            background: #20c05a !important;
        }
        
        .floating-btn:active {
            transform: translateY(0) !important;
        }
        
        .floating-btn.whatsapp {
            background: #25d366 !important;
        }
        
        .floating-btn.whatsapp:hover {
            background: #20c05a !important;
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
        
        .footer-logo {
            max-width: 200px;
        }
        
        .footer-logo-image {
            width: 100%;
            height: auto;
            max-width: 180px;
            filter: brightness(1.1);
        }
        
        .footer-slogan {
            margin-top: 20px;
        }
        
        .slogan-banner {
            background: var(--logo-yellow);
            color: var(--logo-blue);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
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
        
        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
        }
        
        .contact-icon {
            width: 20px;
            height: 20px;
            display: inline-block;
            position: relative;
            background: var(--logo-yellow);
            border-radius: 50%;
            margin-right: 10px;
            flex-shrink: 0;
        }
        
        /* Ícones Geométricos Profissionais */
        .contact-icon.phone::before {
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
        
        .contact-icon.phone::after {
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
        
        .contact-icon.email::before {
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
        
        .contact-icon.email::after {
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
        
        .contact-icon.location::before {
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
        
        .contact-icon.location::after {
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
        
        .footer-divider {
            height: 1px;
            background: #333;
            margin: 40px 0 20px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .footer-bottom {
            text-align: center;
            color: #ccc !important;
            font-size: 0.9rem;
            position: relative;
            z-index: 1;
        }
        
        /* Responsividade */
        @media (max-width: 992px) {
            .header-info .container {
                flex-direction: column;
                gap: 8px;
                text-align: center;
                padding: 8px 15px;
            }
            
            .main-header .container {
                flex-direction: column;
                gap: 15px;
                text-align: center;
                padding: 15px;
            }
            
            .nav-menu {
                gap: 20px;
            }
            
            .footer-content {
                grid-template-columns: 1fr 1fr;
                gap: 30px;
            }
        }
        
        @media (max-width: 768px) {
            .header-info {
                display: none;
            }
            
            .main-header {
                padding: 12px 0;
                min-height: 60px;
            }
            
            .main-header .container {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
            
            .nav-menu {
                display: none;
            }
            
            .mobile-menu-toggle {
                display: block;
            }
            
            /* Ajustar posição dos botões flutuantes no mobile */
            .floating-buttons {
                bottom: 15px !important;
                right: 15px !important;
            }
            
            .footer {
                padding: 40px 0 15px;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                gap: 25px;
                text-align: center;
                padding: 0 15px;
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
            
            .footer-column h4 {
                font-size: 1.1rem;
                margin-bottom: 15px;
            }
            
            .footer-column p,
            .footer-column li {
                font-size: 14px;
            }
        }
        
        @media (max-width: 576px) {
            .footer {
                padding: 30px 0 15px;
            }
            
            .footer-content {
                padding: 0 10px;
                gap: 20px;
            }
            
            .footer-logo {
                max-width: 120px;
            }
            
            .footer-logo-image {
                max-width: 110px;
            }
            
            .footer-column h4 {
                font-size: 1rem;
                margin-bottom: 12px;
            }
            
            .footer-column p,
            .footer-column li {
                font-size: 13px;
            }
        }
        
        .header {
            background: linear-gradient(135deg, var(--logo-blue), #2d3748);
            color: white;
            padding: 20px 0;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
        }
        
        .header p {
            margin: 10px 0 0 0;
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .form-section {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }
        
        .section-title {
            color: var(--logo-blue);
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: var(--logo-yellow);
            border-radius: 2px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
            display: block;
        }
        
        .form-control {
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--logo-yellow);
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.1);
            outline: none;
        }
        
        .form-select {
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-select:focus {
            border-color: var(--logo-yellow);
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.1);
            outline: none;
        }
        
        .file-upload {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        
        .file-upload input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-upload-label {
            display: block;
            padding: 15px 20px;
            background: var(--logo-yellow);
            color: var(--logo-blue);
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid var(--logo-yellow);
        }
        
        .file-upload-label:hover {
            background: #f59e0b;
            border-color: #f59e0b;
        }
        
        .file-info {
            margin-top: 10px;
            font-size: 0.9rem;
            color: var(--text-light);
            font-style: italic;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--logo-blue), #2d3748);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(26, 54, 93, 0.3);
        }
        
        .alert {
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 25px;
            border: none;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -15px;
        }
        
        .col-md-6 {
            flex: 0 0 50%;
            padding: 0 15px;
        }
        
        .col-12 {
            flex: 0 0 100%;
            padding: 0 15px;
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }
            
            .container {
                padding: 20px 15px;
            }
            
            .form-section {
                padding: 25px 20px;
            }
            
            .col-md-6 {
                flex: 0 0 100%;
            }
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            color: var(--logo-blue);
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: var(--logo-yellow);
        }
        
        .back-link i {
            margin-right: 8px;
        }
        
        /* Estilos para seção de vagas */
        .no-vagas {
            text-align: center;
            padding: 40px 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 2px dashed #dee2e6;
        }
        
        .no-vagas-icon {
            font-size: 4rem;
            color: var(--logo-yellow);
            margin-bottom: 20px;
        }
        
        .no-vagas h3 {
            color: var(--logo-blue);
            margin-bottom: 15px;
            font-size: 1.5rem;
        }
        
        .no-vagas p {
            color: var(--text-light);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .vagas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .vaga-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .vaga-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .vaga-card.selected {
            border-color: var(--logo-yellow);
            box-shadow: 0 8px 25px rgba(251, 191, 36, 0.3);
            transform: translateY(-5px);
        }
        
        .vaga-card.selected::before {
            content: '✓ Selecionada';
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--logo-yellow);
            color: var(--logo-blue);
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: 600;
            z-index: 1;
        }
        
        .vaga-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .vaga-header h4 {
            color: var(--logo-blue);
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0;
            flex: 1;
        }
        
        .vaga-status {
            background: #e8f5e8;
            color: #388e3c;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .vaga-details {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .vaga-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-light);
            font-size: 0.95rem;
        }
        
        .vaga-info i {
            color: var(--logo-yellow);
            width: 16px;
            text-align: center;
        }
        
        .vaga-description,
        .vaga-requirements,
        .vaga-benefits {
            margin-bottom: 15px;
        }
        
        .vaga-description p,
        .vaga-requirements p,
        .vaga-benefits p {
            margin: 5px 0 0 0;
            color: var(--text-light);
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        .vaga-requirements h5,
        .vaga-benefits h5 {
            color: var(--logo-blue);
            font-size: 0.95rem;
            font-weight: 600;
            margin: 0 0 5px 0;
        }
        
        .vaga-salary {
            background: var(--logo-yellow);
            color: var(--logo-blue);
            padding: 10px 15px;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            margin-top: 15px;
        }
        
        @media (max-width: 768px) {
            .vagas-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .vaga-card {
                padding: 20px;
            }
            
            .vaga-header {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            
            .no-vagas {
                padding: 30px 15px;
            }
            
            .no-vagas-icon {
                font-size: 3rem;
            }
            
            .no-vagas h3 {
                font-size: 1.3rem;
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
                    <li><a href="index.php">HOME</a></li>
                    <li><a href="index.php#formacao-completa">SOBRE</a></li>
                    <li><a href="index.php#servicos">SERVIÇOS</a></li>
                    <li><a href="trabalhe-conosco.php" class="active">TRABALHE CONOSCO</a></li>
                    <li><a href="index.php#contato">CONTATO</a></li>
                </ul>
            </nav>
            
            <a href="#footer" class="login-btn arrow-btn" title="Ir para o rodapé">
                <i class="fas fa-arrow-down"></i>
            </a>
            
            <!-- Botão Hambúrguer Mobile -->
            <button class="mobile-menu-toggle" aria-label="Abrir menu" aria-expanded="false">
                <i class="fas fa-bars"></i>
                <span class="menu-icon-fallback">☰</span>
            </button>
        </div>
    </header>

    <!-- Menu Mobile Off-Canvas -->
    <div class="mobile-menu-overlay" id="mobile-menu-overlay"></div>
    <div class="mobile-menu" id="mobile-menu">
        <div class="mobile-menu-header">
            <div class="logo-text" style="color: white; font-size: 18px;">CFC Bom Conselho</div>
            <button class="mobile-menu-close" aria-label="Fechar menu">
                <i class="fas fa-times"></i>
                <span class="close-icon-fallback">✕</span>
            </button>
        </div>
        <nav class="mobile-menu-nav">
            <a href="index.php">HOME</a>
            <a href="index.php#formacao-completa">SOBRE</a>
            <a href="index.php#servicos">SERVIÇOS</a>
            <a href="trabalhe-conosco.php" class="active">TRABALHE CONOSCO</a>
            <a href="index.php#contato">CONTATO</a>
            
            <!-- Informações de Contato -->
            <div class="mobile-menu-contact">
                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <a href="tel:+5587981450308">(87) 98145-0308</a>
                </div>
                <div class="contact-item">
                    <i class="fab fa-whatsapp"></i>
                    <a href="https://wa.me/5587981450308" target="_blank">WhatsApp</a>
                </div>
                <div class="contact-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>R. Ângela Pessoa Lucena, 248<br>Bom Conselho, PE</span>
                </div>
            </div>
            
            <a href="#footer" class="arrow-btn" style="background: var(--logo-yellow); color: var(--logo-blue); margin: 20px; border-radius: 25px; text-align: center; padding: 12px 16px;">
                <i class="fas fa-arrow-down"></i>
            </a>
        </nav>
    </div>
    
    <div class="container">
        <a href="index.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Voltar ao site
        </a>
        
        <?php if ($mensagem_sucesso): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($mensagem_sucesso); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($mensagem_erro): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($mensagem_erro); ?>
            </div>
        <?php endif; ?>
        
        <!-- Seção de Vagas Disponíveis -->
        <div class="form-section">
            <h2 class="section-title">Oportunidades Disponíveis</h2>
            
            <?php if (empty($vagas)): ?>
                <div class="no-vagas">
                    <div class="no-vagas-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h3>Deixe sua intenção de trabalhar conosco</h3>
                    <p>No momento não temos vagas abertas, mas quando surgir uma oportunidade compatível com seu perfil, entraremos em contato. Preencha o formulário abaixo para se cadastrar em nosso banco de talentos.</p>
                </div>
            <?php else: ?>
                <div class="vagas-grid">
                    <?php foreach ($vagas as $vaga): ?>
                        <div class="vaga-card" data-vaga-id="<?php echo $vaga['id']; ?>">
                            <div class="vaga-header">
                                <h4><?php echo htmlspecialchars($vaga['titulo']); ?></h4>
                                <span class="vaga-status ativa">Vaga Ativa</span>
                            </div>
                            <div class="vaga-details">
                                <div class="vaga-info">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo htmlspecialchars($vaga['carga_horaria']); ?></span>
                                </div>
                                <div class="vaga-info">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($vaga['localizacao']); ?></span>
                                </div>
                                <div class="vaga-info">
                                    <i class="fas fa-calendar"></i>
                                    <span><?php echo htmlspecialchars($vaga['turno']); ?></span>
                                </div>
                            </div>
                            <?php if ($vaga['descricao']): ?>
                                <div class="vaga-description">
                                    <p><?php echo htmlspecialchars($vaga['descricao']); ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if ($vaga['requisitos']): ?>
                                <div class="vaga-requirements">
                                    <h5>Requisitos:</h5>
                                    <p><?php echo htmlspecialchars($vaga['requisitos']); ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if ($vaga['beneficios']): ?>
                                <div class="vaga-benefits">
                                    <h5>Benefícios:</h5>
                                    <p><?php echo htmlspecialchars($vaga['beneficios']); ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if ($vaga['salario']): ?>
                                <div class="vaga-salary">
                                    <strong>Salário: <?php echo htmlspecialchars($vaga['salario']); ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="form-section">
            <h2 class="section-title">Faça parte da nossa equipe</h2>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="nome_completo">Nome Completo *</label>
                            <input type="text" class="form-control" id="nome_completo" name="nome_completo" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="email">Endereço de E-mail *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="whatsapp">WhatsApp</label>
                            <input type="text" class="form-control" id="whatsapp" name="whatsapp" placeholder="(87) 99999-9999">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="telefone">Telefone</label>
                            <input type="text" class="form-control" id="telefone" name="telefone" placeholder="(87) 99999-9999">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="categoria_cnh">Categoria da CNH</label>
                            <select class="form-select" id="categoria_cnh" name="categoria_cnh">
                                <option value="">Selecione uma categoria</option>
                                <option value="A">A - Motocicleta</option>
                                <option value="B">B - Carro</option>
                                <option value="AB">AB - Carro e Motocicleta</option>
                                <option value="C">C - Caminhão</option>
                                <option value="D">D - Ônibus</option>
                                <option value="E">E - Carreta</option>
                                <option value="Nenhuma">Não possuo CNH</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="escolaridade">Escolaridade</label>
                            <select class="form-select" id="escolaridade" name="escolaridade">
                                <option value="">Selecione sua escolaridade</option>
                                <option value="Ensino Fundamental">Ensino Fundamental</option>
                                <option value="Ensino Médio">Ensino Médio</option>
                                <option value="Ensino Superior">Ensino Superior</option>
                                <option value="Pós-graduação">Pós-graduação</option>
                                <option value="Mestrado">Mestrado</option>
                                <option value="Doutorado">Doutorado</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="endereco_rua">Nome da Rua</label>
                    <input type="text" class="form-control" id="endereco_rua" name="endereco_rua" placeholder="Ex. 42 Wallaby Way">
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label" for="cidade">Cidade</label>
                            <input type="text" class="form-control" id="cidade" name="cidade" placeholder="Ex. Cidade de São Paulo">
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label" for="estado">Estado/Província</label>
                            <input type="text" class="form-control" id="estado" name="estado" placeholder="Ex. São Paulo">
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label" for="cep">CEP / Código Postal</label>
                            <input type="text" class="form-control" id="cep" name="cep" placeholder="Ex. 2000">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="pais">País</label>
                    <input type="text" class="form-control" id="pais" name="pais" value="Brasil">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="vaga_id">Área de Interesse</label>
                    <select class="form-select" id="vaga_id" name="vaga_id">
                        <option value="">Selecione uma área de interesse (opcional)</option>
                        <option value="servicos_gerais">Serviços Gerais</option>
                        <option value="tecnico_informatica">Técnico em Informática (TI)</option>
                        <option value="diretor_ensino">Diretor de Ensino</option>
                        <option value="diretor_geral">Diretor Geral</option>
                        <option value="instrutor_teorico">Instrutor Teórico</option>
                        <option value="instrutor_pratico">Instrutor Prático</option>
                        <option value="recepcionista">Recepcionista</option>
                        <option value="administrativo">Administrativo</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="indicacoes">Tem indicações? Se sim, coloque o nome e o telefone</label>
                    <textarea class="form-control" id="indicacoes" name="indicacoes" rows="3" placeholder="Nome da pessoa que indicou e telefone de contato"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="mensagem">Mensagem</label>
                    <textarea class="form-control" id="mensagem" name="mensagem" rows="4" placeholder="Conte-nos um pouco sobre você e por que gostaria de trabalhar conosco"></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Currículo</label>
                            <div class="file-upload">
                                <input type="file" id="curriculo" name="curriculo" accept=".pdf,.doc,.docx">
                                <label for="curriculo" class="file-upload-label">
                                    <i class="fas fa-file-upload"></i> Selecione Arquivo
                                </label>
                            </div>
                            <div class="file-info">Formatos aceitos: PDF, DOC, DOCX</div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Foto</label>
                            <div class="file-upload">
                                <input type="file" id="foto" name="foto" accept=".jpg,.jpeg,.png,.gif">
                                <label for="foto" class="file-upload-label">
                                    <i class="fas fa-camera"></i> Selecione Arquivo
                                </label>
                            </div>
                            <div class="file-info">Formatos aceitos: JPG, PNG, GIF</div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Enviar Candidatura
                </button>
            </form>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="footer" id="footer">
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
                        <li><a href="index.php#home">Home</a></li>
                        <li><a href="index.php#servicos">Serviços</a></li>
                        <li><a href="trabalhe-conosco.php">Trabalhe Conosco</a></li>
                        <li><a href="links-uteis.php">Links Úteis</a></li>
                        <li><a href="login.php?type=aluno">Portal do Aluno</a></li>
                        <li><a href="login.php?type=admin">Portal do CFC</a></li>
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
    
    <script>
        // Atualizar texto dos botões de upload
        document.getElementById('curriculo').addEventListener('change', function(e) {
            const label = e.target.nextElementSibling;
            if (e.target.files.length > 0) {
                label.innerHTML = '<i class="fas fa-file-check"></i> ' + e.target.files[0].name;
                label.style.background = '#10b981';
                label.style.color = 'white';
            } else {
                label.innerHTML = '<i class="fas fa-file-upload"></i> Selecione Arquivo';
                label.style.background = '';
                label.style.color = '';
            }
        });
        
        document.getElementById('foto').addEventListener('change', function(e) {
            const label = e.target.nextElementSibling;
            if (e.target.files.length > 0) {
                label.innerHTML = '<i class="fas fa-image"></i> ' + e.target.files[0].name;
                label.style.background = '#10b981';
                label.style.color = 'white';
            } else {
                label.innerHTML = '<i class="fas fa-camera"></i> Selecione Arquivo';
                label.style.background = '';
                label.style.color = '';
            }
        });
        
        // Máscaras para telefone
        function aplicarMascaraTelefone(input) {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length <= 11) {
                    if (value.length <= 2) {
                        value = value;
                    } else if (value.length <= 7) {
                        value = `(${value.slice(0, 2)}) ${value.slice(2)}`;
                    } else {
                        value = `(${value.slice(0, 2)}) ${value.slice(2, 7)}-${value.slice(7)}`;
                    }
                }
                e.target.value = value;
            });
        }
        
        aplicarMascaraTelefone(document.getElementById('whatsapp'));
        aplicarMascaraTelefone(document.getElementById('telefone'));
        
        // Máscara para CEP
        document.getElementById('cep').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 8) {
                if (value.length <= 5) {
                    value = value;
                } else {
                    value = `${value.slice(0, 5)}-${value.slice(5)}`;
                }
            }
            e.target.value = value;
        });
        
        // Menu Mobile
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        const mobileMenu = document.querySelector('.mobile-menu');
        const mobileMenuOverlay = document.querySelector('.mobile-menu-overlay');
        const mobileMenuClose = document.querySelector('.mobile-menu-close');
        
        function openMobileMenu() {
            mobileMenu.classList.add('active');
            mobileMenuOverlay.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function closeMobileMenu() {
            mobileMenu.classList.remove('active');
            mobileMenuOverlay.style.display = 'none';
            document.body.style.overflow = '';
        }
        
        if (mobileMenuToggle) {
            mobileMenuToggle.addEventListener('click', openMobileMenu);
        }
        
        if (mobileMenuClose) {
            mobileMenuClose.addEventListener('click', closeMobileMenu);
        }
        
        if (mobileMenuOverlay) {
            mobileMenuOverlay.addEventListener('click', closeMobileMenu);
        }
        
        // Fechar menu ao clicar em um link
        const mobileNavLinks = document.querySelectorAll('.mobile-menu-nav a');
        mobileNavLinks.forEach(link => {
            link.addEventListener('click', closeMobileMenu);
        });
        
        // Remover funcionalidade de seleção de cards (não há vagas ativas)
    </script>

    <!-- Floating Button WhatsApp -->
    <div class="floating-buttons" id="floating-buttons">
        <a href="https://wa.me/5587981450308" class="floating-btn whatsapp" target="_blank" rel="noopener" aria-label="WhatsApp">
            <i class="fab fa-whatsapp"></i>
        </a>
    </div>
</body>
</html>
