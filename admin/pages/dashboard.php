<?php
// Verificar se as variáveis estão definidas
if (!isset($stats)) $stats = [];
if (!isset($ultimas_atividades)) $ultimas_atividades = [];
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                <i class="fas fa-print me-1"></i>Imprimir
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportarDashboard()">
                <i class="fas fa-download me-1"></i>Exportar
            </button>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
            <i class="fas fa-calendar me-1"></i>Período
        </button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="#" onclick="filtrarPeriodo('hoje')">Hoje</a></li>
            <li><a class="dropdown-item" href="#" onclick="filtrarPeriodo('semana')">Esta Semana</a></li>
            <li><a class="dropdown-item" href="#" onclick="filtrarPeriodo('mes')">Este Mês</a></li>
            <li><a class="dropdown-item" href="#" onclick="filtrarPeriodo('ano')">Este Ano</a></li>
        </ul>
    </div>
</div>

<!-- Cards de Estatísticas -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total de Alunos
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($stats['total_alunos'] ?? 0); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-graduate fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Total de Instrutores
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($stats['total_instrutores'] ?? 0); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Aulas Hoje
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($stats['aulas_hoje'] ?? 0); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Total de Veículos
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($stats['total_veiculos'] ?? 0); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-car fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos e Análises -->
<div class="row mb-4">
    <!-- Gráfico de Aulas por Semana -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-chart-line me-1"></i>Aulas por Semana
                </h6>
                <div class="dropdown no-arrow">
                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in">
                        <a class="dropdown-item" href="#" onclick="atualizarGraficoAulas()">Atualizar</a>
                        <a class="dropdown-item" href="#" onclick="exportarGrafico()">Exportar</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <canvas id="graficoAulas" width="100%" height="40"></canvas>
            </div>
        </div>
    </div>

    <!-- Gráfico de Distribuição de Alunos -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-chart-pie me-1"></i>Status dos Alunos
                </h6>
            </div>
            <div class="card-body">
                <canvas id="graficoStatusAlunos" width="100%" height="40"></canvas>
                <div class="mt-4 text-center small">
                    <span class="mr-2">
                        <i class="fas fa-circle text-success"></i> Ativos
                    </span>
                    <span class="mr-2">
                        <i class="fas fa-circle text-warning"></i> Em Curso
                    </span>
                    <span class="mr-2">
                        <i class="fas fa-circle text-danger"></i> Inativos
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Últimas Atividades e Ações Rápidas -->
<div class="row">
    <!-- Últimas Atividades -->
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-history me-1"></i>Últimas Atividades
                </h6>
            </div>
            <div class="card-body">
                <?php if (!empty($ultimas_atividades)): ?>
                    <div class="timeline">
                        <?php foreach ($ultimas_atividades as $atividade): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">
                                        <?php echo ucfirst($atividade['tipo']); ?> 
                                        <strong><?php echo htmlspecialchars($atividade['nome']); ?></strong>
                                    </h6>
                                    <p class="timeline-text">
                                        <?php echo ucfirst($atividade['acao']); ?> no sistema
                                    </p>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($atividade['data'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-info-circle fa-3x mb-3"></i>
                        <p>Nenhuma atividade recente encontrada.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Ações Rápidas -->
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-bolt me-1"></i>Ações Rápidas
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="index.php?page=alunos&action=create" class="btn btn-primary btn-sm">
                        <i class="fas fa-user-plus me-1"></i>Novo Aluno
                    </a>
                    <a href="index.php?page=instrutores&action=create" class="btn btn-success btn-sm">
                        <i class="fas fa-chalkboard-teacher me-1"></i>Novo Instrutor
                    </a>
                    <a href="index.php?page=aulas&action=create" class="btn btn-info btn-sm">
                        <i class="fas fa-calendar-plus me-1"></i>Nova Aula
                    </a>
                    <a href="index.php?page=veiculos&action=create" class="btn btn-warning btn-sm">
                        <i class="fas fa-car me-1"></i>Novo Veículo
                    </a>
                    <a href="index.php?page=relatorios&action=alunos" class="btn btn-secondary btn-sm">
                        <i class="fas fa-chart-bar me-1"></i>Relatório de Alunos
                    </a>
                </div>
                
                <hr>
                
                <h6 class="font-weight-bold text-primary mb-3">
                    <i class="fas fa-tasks me-1"></i>Tarefas Pendentes
                </h6>
                
                <div class="task-list">
                    <div class="task-item d-flex align-items-center mb-2">
                        <input type="checkbox" class="form-check-input me-2" id="task1">
                        <label for="task1" class="form-check-label">Revisar cadastros pendentes</label>
                    </div>
                    <div class="task-item d-flex align-items-center mb-2">
                        <input type="checkbox" class="form-check-input me-2" id="task2">
                        <label for="task2" class="form-check-label">Verificar agendamentos da semana</label>
                    </div>
                    <div class="task-item d-flex align-items-center mb-2">
                        <input type="checkbox" class="form-check-input me-2" id="task3">
                        <label for="task3" class="form-check-label">Gerar relatório mensal</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notificações do Sistema -->
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-bell me-1"></i>Notificações do Sistema
                </h6>
            </div>
            <div class="card-body">
                <div id="notificacoes-container">
                    <!-- As notificações serão carregadas via JavaScript -->
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                        <p>Carregando notificações...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Inicializar gráficos quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    inicializarGraficos();
    carregarNotificacoes();
});

function inicializarGraficos() {
    // Gráfico de Aulas por Semana
    const ctxAulas = document.getElementById('graficoAulas').getContext('2d');
    new Chart(ctxAulas, {
        type: 'line',
        data: {
            labels: ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'],
            datasets: [{
                label: 'Aulas Agendadas',
                data: [12, 19, 15, 25, 22, 18, 10],
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Gráfico de Status dos Alunos
    const ctxStatus = document.getElementById('graficoStatusAlunos').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: ['Ativos', 'Em Curso', 'Inativos'],
            datasets: [{
                data: [65, 25, 10],
                backgroundColor: [
                    'rgb(40, 167, 69)',
                    'rgb(255, 193, 7)',
                    'rgb(220, 53, 69)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

function carregarNotificacoes() {
    // Simular carregamento de notificações
    setTimeout(() => {
        const container = document.getElementById('notificacoes-container');
        container.innerHTML = `
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Bem-vindo ao sistema!</strong> O dashboard foi configurado com sucesso.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Atenção:</strong> Verifique os cadastros pendentes de aprovação.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Sucesso:</strong> Sistema funcionando perfeitamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
    }, 1000);
}

function filtrarPeriodo(periodo) {
    // Implementar filtro por período
    console.log('Filtrando por período:', periodo);
    // Aqui você pode implementar a lógica de filtro
}

function exportarDashboard() {
    // Implementar exportação do dashboard
    alert('Funcionalidade de exportação será implementada em breve!');
}

function atualizarGraficoAulas() {
    // Implementar atualização do gráfico
    console.log('Atualizando gráfico de aulas...');
}

function exportarGrafico() {
    // Implementar exportação do gráfico
    alert('Funcionalidade de exportação do gráfico será implementada em breve!');
}
</script>
