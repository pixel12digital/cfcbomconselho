<?php
/**
 * Página de entrada do PWA para Alunos
 * Redireciona para login se não estiver autenticado
 * Redireciona para dashboard se estiver autenticado
 */

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Se não estiver logado, redirecionar para login
if (!isLoggedIn()) {
    header('Location: ../login.php?type=aluno');
    exit;
}

// Verificar se o usuário é realmente um aluno
$user = getCurrentUser();
if (!$user || $user['tipo'] !== 'aluno') {
    // Se não for aluno, fazer logout e redirecionar
    if (function_exists('logout')) {
        logout();
    } else {
        session_destroy();
    }
    header('Location: ../login.php?type=aluno');
    exit;
}

// Se estiver logado como aluno, redirecionar para dashboard
header('Location: dashboard.php');
exit;
