<?php
/**
 * DEPRECATED - Este arquivo foi substituído pela funcionalidade integrada em turmas.php
 * 
 * Funcionalidade equivalente disponível em:
 * - Gestão de Turmas: /admin/pages/turmas.php
 * 
 * Este arquivo será removido em versão futura.
 * Por favor, use a nova interface unificada de turmas.
 */

/**
 * Dashboard de Turmas Teóricas
 * Sistema de Turmas Teóricas - CFC Bom Conselho
 * 
 * @author Sistema CFC Bom Conselho
 * @version 1.0
 * @since 2024
 */

// Obter dados do usuário (já definidos no template principal)
if (function_exists('getCurrentUser')) {
    $user = getCurrentUser();
    $userType = $user['tipo'] ?? 'admin';
    $userId = $user['id'] ?? null;
} else {
    // Fallback se não estiver no contexto do template
    $userType = 'admin';
    $userId = 1;
}

// Verificar permissões
$canView = ($userType === 'admin' || $userType === 'instrutor');
if (!$canView) {
    echo '<div class="alert alert-danger">Acesso negado. Apenas administradores e instrutores podem acessar esta página.</div>';
    return;
}
?>

<style>
    .stats-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        border-left: 5px solid #00A651;
        transition: transform 0.3s ease;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
    }
    
    .stats-card.success {
        border-left-color: #28a745;
    }
    
    .stats-card.warning {
        border-left-color: #ffc107;
    }
    
    .stats-card.info {
        border-left-color: #17a2b8;
    }
    
    .stats-card.danger {
        border-left-color: #dc3545;
    }
    
    .stats-number {
        font-size: 2.5rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 5px;
    }
    
    .stats-label {
        color: #6c757d;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .stats-icon {
        font-size: 3rem;
        opacity: 0.3;
        position: absolute;
        right: 20px;
        top: 20px;
    }
    
    .chart-container {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .recent-activity {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    
    .activity-item {
        padding: 15px 0;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        align-items: center;
    }
    
    .activity-item:last-child {
        border-bottom: none;
    }
    
    .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        font-size: 1.2rem;
    }
    
    .activity-icon.success {
        background: #d4edda;
        color: #155724;
    }
    
    .activity-icon.warning {
        background: #fff3cd;
        color: #856404;
    }
    
    .activity-icon.info {
        background: #d1ecf1;
        color: #0c5460;
    }
    
    .quick-actions {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .quick-action-btn {
        background: linear-gradient(135deg, #00A651 0%, #007A3D 100%);
        border: none;
        border-radius: 10px;
        padding: 15px 25px;
        color: white;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        margin: 5px;
        transition: all 0.3s ease;
    }
    
    .quick-action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,166,81,0.3);
        color: white;
    }
    
    .quick-action-btn i {
        margin-right: 10px;
        font-size: 1.2rem;
    }
</style>

<!-- Header -->
<div class="page-header bg-primary text-white p-4 rounded mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="mb-2">
                <i class="fas fa-tachometer-alt me-3"></i>
                Dashboard - Turmas Teóricas
            </h1>
            <p class="mb-0 opacity-75">
                Visão geral do sistema de turmas teóricas
            </p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group" role="group">
                <a href="?page=turmas" class="btn btn-light btn-sm">
                    <i class="fas fa-list"></i> Lista de Turmas
                </a>
                <a href="?page=turma-calendario" class="btn btn-light btn-sm">
                    <i class="fas fa-calendar-alt"></i> Calendário
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Estatísticas Principais -->
<div class="row">
    <div class="col-md-3">
        <div class="stats-card success">
            <div class="stats-number"><?= $stats_turmas['total_turmas'] ?? 0 ?></div>
            <div class="stats-label">Total de Turmas</div>
            <i class="fas fa-graduation-cap stats-icon"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card info">
            <div class="stats-number"><?= $stats_turmas['turmas_ativas'] ?? 0 ?></div>
            <div class="stats-label">Turmas Ativas</div>
            <i class="fas fa-play-circle stats-icon"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card warning">
            <div class="stats-number"><?= $stats_turmas['turmas_agendadas'] ?? 0 ?></div>
            <div class="stats-label">Turmas Agendadas</div>
            <i class="fas fa-clock stats-icon"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card danger">
            <div class="stats-number"><?= $stats_turmas['total_alunos_matriculados'] ?? 0 ?></div>
            <div class="stats-label">Alunos Matriculados</div>
            <i class="fas fa-users stats-icon"></i>
        </div>
    </div>
</div>

<!-- Ações Rápidas -->
<div class="quick-actions">
    <h5 class="mb-3">
        <i class="fas fa-bolt text-primary"></i>
        Ações Rápidas
    </h5>
    <div class="d-flex flex-wrap">
        <a href="?page=turmas&action=create" class="quick-action-btn">
            <i class="fas fa-plus"></i>
            Nova Turma
        </a>
        <a href="?page=turma-calendario" class="quick-action-btn">
            <i class="fas fa-calendar-alt"></i>
            Ver Calendário
        </a>
        <a href="?page=turma-matriculas" class="quick-action-btn">
            <i class="fas fa-user-plus"></i>
            Gerenciar Matrículas
        </a>
        <a href="?page=turma-relatorios" class="quick-action-btn">
            <i class="fas fa-chart-bar"></i>
            Relatórios
        </a>
    </div>
</div>

<div class="row">
    <!-- Gráfico de Status das Turmas -->
    <div class="col-md-8">
        <div class="chart-container">
            <h5 class="mb-3">
                <i class="fas fa-chart-pie text-primary"></i>
                Status das Turmas
            </h5>
            <canvas id="statusChart" width="400" height="200"></canvas>
        </div>
    </div>

    <!-- Atividades Recentes -->
    <div class="col-md-4">
        <div class="recent-activity">
            <h5 class="mb-3">
                <i class="fas fa-history text-primary"></i>
                Atividades Recentes
            </h5>
            
            <?php
            // Buscar atividades recentes
            try {
                $atividades = $db->fetchAll("
                    SELECT 'turma' as tipo, t.nome, t.status, t.criado_em as data
                    FROM turmas t
                    WHERE t.tipo_aula = 'teorica'
                    ORDER BY t.criado_em DESC
                    LIMIT 5
                ");
            } catch (Exception $e) {
                $atividades = [];
            }
            ?>
            
            <?php if (empty($atividades)): ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>Nenhuma atividade recente</p>
                </div>
            <?php else: ?>
                <?php foreach ($atividades as $atividade): ?>
                    <div class="activity-item">
                        <div class="activity-icon <?= $atividade['status'] === 'ativa' ? 'success' : ($atividade['status'] === 'agendada' ? 'warning' : 'info') ?>">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold"><?= htmlspecialchars($atividade['nome']) ?></div>
                            <small class="text-muted">
                                <?= ucfirst($atividade['status']) ?> • 
                                <?= date('d/m/Y', strtotime($atividade['data'])) ?>
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Gráfico de Status das Turmas
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('statusChart');
    if (ctx) {
        const statusChart = new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Ativas', 'Agendadas', 'Concluídas'],
                datasets: [{
                    data: [
                        <?= $stats_turmas['turmas_ativas'] ?? 0 ?>,
                        <?= $stats_turmas['turmas_agendadas'] ?? 0 ?>,
                        <?= $stats_turmas['turmas_concluidas'] ?? 0 ?>
                    ],
                    backgroundColor: [
                        '#28a745',
                        '#ffc107',
                        '#17a2b8'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    }
});
</script>
