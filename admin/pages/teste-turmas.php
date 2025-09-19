<?php
/**
 * Página de Teste - Turmas
 * Para verificar se o menu está funcionando
 */

// Definir caminho base
$base_path = dirname(__DIR__);

// Forçar charset UTF-8 para evitar problemas de codificação
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

// Verificar se o usuário está logado e tem permissão de admin
if (!isLoggedIn() || !hasPermission('admin')) {
    header('Location: ../login.php');
    exit;
}

// Obter dados do usuário logado
$user = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Turmas - CFC Bom Conselho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <div class="alert alert-success">
                    <h4 class="alert-heading">
                        <i class="fas fa-check-circle me-2"></i>
                        Funcionalidade de Turmas Ativa!
                    </h4>
                    <p>O menu "Turmas" está funcionando corretamente.</p>
                    <hr>
                    <p class="mb-0">
                        <strong>Próximos passos:</strong>
                        <br>1. Execute o script SQL: <code>sistema_turmas.sql</code>
                        <br>2. Acesse a página completa: <a href="turmas.php" class="alert-link">Gestão de Turmas</a>
                    </p>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-graduation-cap me-2"></i>
                            Sistema de Turmas Implementado
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Funcionalidades Disponíveis:</h6>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item">
                                        <i class="fas fa-plus text-success me-2"></i>
                                        Criar Nova Turma
                                    </li>
                                    <li class="list-group-item">
                                        <i class="fas fa-list text-info me-2"></i>
                                        Gestão de Turmas
                                    </li>
                                    <li class="list-group-item">
                                        <i class="fas fa-filter text-warning me-2"></i>
                                        Filtros Avançados
                                    </li>
                                    <li class="list-group-item">
                                        <i class="fas fa-chart-bar text-primary me-2"></i>
                                        Estatísticas
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Arquivos Criados:</h6>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item">
                                        <code>admin/includes/turma_manager.php</code>
                                    </li>
                                    <li class="list-group-item">
                                        <code>admin/api/turmas.php</code>
                                    </li>
                                    <li class="list-group-item">
                                        <code>admin/pages/turmas.php</code>
                                    </li>
                                    <li class="list-group-item">
                                        <code>sistema_turmas.sql</code>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <a href="../index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>
                        Voltar ao Dashboard
                    </a>
                    <a href="turmas.php" class="btn btn-primary">
                        <i class="fas fa-graduation-cap me-2"></i>
                        Acessar Gestão de Turmas
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
