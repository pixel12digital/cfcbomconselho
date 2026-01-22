<?php
// =====================================================
// LOGOUT PARA ALUNOS - SISTEMA CFC
// =====================================================

require_once '../includes/config.php';
require_once '../includes/auth.php';

// Usar sistema unificado de logout
$auth = new Auth();
$auth->logout();

// Redirecionar para login principal com tipo aluno e mensagem de sucesso
header('Location: ../login.php?type=aluno&message=logout_success');
exit;
?>