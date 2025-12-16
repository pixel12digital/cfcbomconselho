<?php
/**
 * Página de entrada do PWA para Instrutores
 * Redireciona para login se não estiver autenticado
 * Redireciona para dashboard se estiver autenticado
 */

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Se não estiver logado, redirecionar para login
if (!isLoggedIn()) {
    header('Location: ../login.php?type=instrutor');
    exit;
}

// Verificar se o usuário é realmente um instrutor
$user = getCurrentUser();
if (!$user || $user['tipo'] !== 'instrutor') {
    // Se não for instrutor, fazer logout e redirecionar
    if (function_exists('logout')) {
        logout();
    } else {
        session_destroy();
    }
    header('Location: ../login.php?type=instrutor');
    exit;
}

// Se estiver logado como instrutor, redirecionar para dashboard
header('Location: dashboard.php');
exit;
