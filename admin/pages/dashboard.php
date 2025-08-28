<?php
// Verificar se as variáveis estão definidas
if (!isset($stats)) $stats = [];
if (!isset($ultimas_atividades)) $ultimas_atividades = [];
?>

<!-- Header da Página -->
<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard Administrativo</h1>
        <p class="page-subtitle">Visão geral do sistema CFC</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="window.location.href='index.php?page=relatorios&action=alunos'">
            <i class="fas fa-chart-bar"></i>
            Ver Relatórios
        </button>
        <button class="btn btn-success" onclick="window.location.href='index.php?page=alunos&action=list'">
            <i class="fas fa-plus"></i>
            Nova Aula
        </button>
    </div>
</div>

<!-- Estatísticas Principais -->
<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-header">
            <div class="stat-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i>
                <span>+12%</span>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($stats['total_alunos'] ?? 0); ?></div>
        <div class="stat-label">Total de Alunos</div>
    </div>
    
    <div class="stat-card success">
        <div class="stat-header">
            <div class="stat-icon">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i>
                <span>+8%</span>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($stats['total_instrutores'] ?? 0); ?></div>
        <div class="stat-label">Total de Instrutores</div>
    </div>
    
    <div class="stat-card warning">
        <div class="stat-header">
            <div class="stat-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-change neutral">
                <i class="fas fa-minus"></i>
                <span>0%</span>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($stats['total_aulas'] ?? 0); ?></div>
        <div class="stat-label">Total de Aulas</div>
    </div>
    
    <div class="stat-card info">
        <div class="stat-header">
            <div class="stat-icon">
                <i class="fas fa-car"></i>
            </div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i>
                <span>+5%</span>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($stats['total_veiculos'] ?? 0); ?></div>
        <div class="stat-label">Total de Veículos</div>
    </div>
    
    <div class="stat-card success">
        <div class="stat-header">
            <div class="stat-icon">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i>
                <span>+15%</span>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($stats['aulas_hoje'] ?? 0); ?></div>
        <div class="stat-label">Aulas Hoje</div>
    </div>
    
    <div class="stat-card primary">
        <div class="stat-header">
            <div class="stat-icon">
                <i class="fas fa-calendar-week"></i>
            </div>
            <div class="stat-change positive">
                <i class="fas fa-arrow-up"></i>
                <span>+22%</span>
            </div>
        </div>
        <div class="stat-value"><?php echo number_format($stats['aulas_semana'] ?? 0); ?></div>
        <div class="stat-label">Aulas Esta Semana</div>
    </div>
</div>

<!-- Gráficos e Estatísticas -->
<div class="chart-section">
    <div class="chart-header">
        <h3 class="chart-title">Evolução de Matrículas</h3>
        <div class="chart-actions">
            <button class="btn btn-outline-primary btn-sm">
                <i class="fas fa-download"></i>
                Exportar
            </button>
            <button class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-cog"></i>
                Configurar
            </button>
        </div>
    </div>
    
    <div class="chart-container">
        <div class="chart-placeholder">
            <div class="chart-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="chart-text">Gráfico de Evolução</div>
            <div class="chart-subtext">Visualize o crescimento das matrículas ao longo do tempo</div>
        </div>
    </div>
</div>

<!-- Atividades Recentes -->
<div class="activities-section">
    <div class="activities-header">
        <h3 class="activities-title">Atividades Recentes</h3>
        <button class="btn btn-outline-primary btn-sm">
            <i class="fas fa-eye"></i>
            Ver Todas
        </button>
    </div>
    
    <div class="activities-list">
        <?php if (!empty($ultimas_atividades)): ?>
            <?php foreach ($ultimas_atividades as $atividade): ?>
                <div class="activity-item">
                    <div class="activity-icon <?php echo $atividade['tipo']; ?>">
                        <i class="fas fa-<?php echo $atividade['tipo'] === 'aluno' ? 'user-graduate' : 'chalkboard-teacher'; ?>"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">
                            <?php echo htmlspecialchars($atividade['nome']); ?> 
                            <span class="badge badge-primary"><?php echo ucfirst($atividade['tipo']); ?></span>
                        </div>
                        <div class="activity-description">
                            <?php echo ucfirst($atividade['acao']); ?> no sistema
                        </div>
                        <div class="activity-time">
                            <i class="fas fa-clock"></i>
                            <?php echo date('d/m/Y H:i', strtotime($atividade['data'])); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center p-5">
                <div class="text-light">
                    <i class="fas fa-info-circle fa-3x mb-3"></i>
                    <p>Nenhuma atividade recente encontrada</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Ações Rápidas -->
<div class="quick-actions">
    <div class="quick-actions-header">
        <h3 class="quick-actions-title">Ações Rápidas</h3>
    </div>
    
    <div class="actions-grid">
        <a href="index.php?page=alunos&action=create" class="action-card">
            <div class="action-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <div class="action-label">Novo Aluno</div>
        </a>
        
        <a href="index.php?page=instrutores&action=create" class="action-card">
            <div class="action-icon">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="action-label">Novo Instrutor</div>
        </a>
        
        <a href="index.php?page=alunos&action=list" class="action-card">
            <div class="action-icon">
                <i class="fas fa-calendar-plus"></i>
            </div>
            <div class="action-label">Nova Aula</div>
        </a>
        
        <a href="index.php?page=veiculos&action=create" class="action-card">
            <div class="action-icon">
                <i class="fas fa-car"></i>
            </div>
            <div class="action-label">Novo Veículo</div>
        </a>
        
        <a href="index.php?page=relatorios&action=alunos" class="action-card">
            <div class="action-icon">
                <i class="fas fa-chart-bar"></i>
            </div>
            <div class="action-label">Relatórios</div>
        </a>
        
        <a href="index.php?page=usuarios&action=create" class="action-card">
            <div class="action-icon">
                <i class="fas fa-user-cog"></i>
            </div>
            <div class="action-label">Novo Usuário</div>
        </a>
    </div>
</div>

<!-- Notificações do Sistema -->
<div class="notifications-section">
    <div class="notifications-header">
        <h3 class="notifications-title">Notificações do Sistema</h3>
        <button class="btn btn-outline-primary btn-sm">
            <i class="fas fa-bell"></i>
            Marcar como Lidas
        </button>
    </div>
    
    <div class="notification-item">
        <div class="notification-icon info">
            <i class="fas fa-info-circle"></i>
        </div>
        <div class="notification-content">
            <div class="notification-title">Sistema Atualizado</div>
            <div class="notification-description">O sistema foi atualizado para a versão mais recente</div>
            <div class="notification-time">Há 2 horas</div>
        </div>
    </div>
    
    <div class="notification-item">
        <div class="notification-icon success">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="notification-content">
            <div class="notification-title">Backup Realizado</div>
            <div class="notification-description">Backup automático do banco de dados concluído com sucesso</div>
            <div class="notification-time">Há 6 horas</div>
        </div>
    </div>
    
    <div class="notification-item">
        <div class="notification-icon warning">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="notification-content">
            <div class="notification-title">Manutenção Programada</div>
            <div class="notification-description">Manutenção programada para domingo às 02:00</div>
            <div class="notification-time">Há 1 dia</div>
        </div>
    </div>
</div>

<!-- Scripts específicos do dashboard -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animações de entrada para os cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.5s ease';
            
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        }, index * 100);
    });
    
    // Contador animado para as estatísticas
    const statValues = document.querySelectorAll('.stat-value');
    statValues.forEach(statValue => {
        const finalValue = parseInt(statValue.textContent.replace(/\D/g, ''));
        const duration = 2000; // 2 segundos
        const increment = finalValue / (duration / 16); // 60 FPS
        let currentValue = 0;
        
        const timer = setInterval(() => {
            currentValue += increment;
            if (currentValue >= finalValue) {
                currentValue = finalValue;
                clearInterval(timer);
            }
            statValue.textContent = Math.floor(currentValue).toLocaleString('pt-BR');
        }, 16);
    });
    
    // Hover effects para action cards
    const actionCards = document.querySelectorAll('.action-card');
    actionCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Tooltips para badges
    const badges = document.querySelectorAll('.badge');
    badges.forEach(badge => {
        badge.setAttribute('data-tooltip', badge.textContent);
    });
});
</script>
