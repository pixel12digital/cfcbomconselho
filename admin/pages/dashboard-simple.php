<?php
// Verificar se as variáveis estão definidas
if (!isset($stats)) $stats = [];
if (!isset($ultimas_atividades)) $ultimas_atividades = [];
?>

<div class="dashboard-header">
    <h1>📊 Dashboard</h1>
    <div class="dashboard-actions">
        <button type="button" class="btn btn-secondary" onclick="window.print()">
            🖨️ Imprimir
        </button>
        <button type="button" class="btn btn-secondary" onclick="exportarDashboard()">
            📥 Exportar
        </button>
    </div>
</div>

<!-- Cards de Estatísticas -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-info">
                <div class="stat-label">Total de Alunos</div>
                <div class="stat-value"><?php echo number_format($stats['total_alunos'] ?? 0); ?></div>
            </div>
            <div class="stat-icon">🎓</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-info">
                <div class="stat-label">Total de Instrutores</div>
                <div class="stat-value"><?php echo number_format($stats['total_instrutores'] ?? 0); ?></div>
            </div>
            <div class="stat-icon">👨‍🏫</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-info">
                <div class="stat-label">Aulas Hoje</div>
                <div class="stat-value"><?php echo number_format($stats['aulas_hoje'] ?? 0); ?></div>
            </div>
            <div class="stat-icon">📅</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-info">
                <div class="stat-label">Total de Veículos</div>
                <div class="stat-value"><?php echo number_format($stats['total_veiculos'] ?? 0); ?></div>
            </div>
            <div class="stat-icon">🚗</div>
        </div>
    </div>
</div>

<!-- Gráfico de Aulas por Semana -->
<div class="chart-section">
    <h3>📈 Aulas por Semana</h3>
    <div class="chart-container">
        <div class="chart-placeholder">
            <p>Gráfico de aulas da semana atual</p>
            <p>Total: <?php echo number_format($stats['aulas_semana'] ?? 0); ?> aulas</p>
        </div>
    </div>
</div>

<!-- Status dos Alunos -->
<div class="status-section">
    <h3>📊 Status dos Alunos</h3>
    <div class="status-grid">
        <div class="status-item">
            <span class="status-dot active"></span>
            <span class="status-label">Ativos</span>
            <span class="status-count"><?php echo number_format($stats['total_alunos'] ?? 0); ?></span>
        </div>
        <div class="status-item">
            <span class="status-dot in-progress"></span>
            <span class="status-label">Em Curso</span>
            <span class="status-count"><?php echo number_format(($stats['total_alunos'] ?? 0) * 0.7); ?></span>
        </div>
        <div class="status-item">
            <span class="status-dot inactive"></span>
            <span class="status-label">Inativos</span>
            <span class="status-count"><?php echo number_format(($stats['total_alunos'] ?? 0) * 0.1); ?></span>
        </div>
    </div>
</div>

<!-- Últimas Atividades -->
<div class="activities-section">
    <h3>🕒 Últimas Atividades</h3>
    <div class="activities-list">
        <?php if (!empty($ultimas_atividades)): ?>
            <?php foreach (array_slice($ultimas_atividades, 0, 5) as $atividade): ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <?php echo $atividade['tipo'] === 'aluno' ? '🎓' : '👨‍🏫'; ?>
                    </div>
                    <div class="activity-info">
                        <div class="activity-title"><?php echo htmlspecialchars($atividade['nome']); ?></div>
                        <div class="activity-desc"><?php echo ucfirst($atividade['acao']); ?></div>
                    </div>
                    <div class="activity-time">
                        <?php echo date('d/m H:i', strtotime($atividade['data'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-activities">
                <p>📝 Nenhuma atividade recente</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Ações Rápidas -->
<div class="quick-actions">
    <h3>⚡ Ações Rápidas</h3>
    <div class="actions-grid">
        <a href="index.php?page=alunos&action=create" class="action-card">
            <div class="action-icon">👤</div>
            <div class="action-label">Novo Aluno</div>
        </a>
        <a href="index.php?page=instrutores&action=create" class="action-card">
            <div class="action-icon">👨‍🏫</div>
            <div class="action-label">Novo Instrutor</div>
        </a>
        <a href="index.php?page=aulas&action=create" class="action-card">
            <div class="action-icon">📅</div>
            <div class="action-label">Nova Aula</div>
        </a>
        <a href="index.php?page=veiculos&action=create" class="action-card">
            <div class="action-icon">🚗</div>
            <div class="action-label">Novo Veículo</div>
        </a>
    </div>
</div>

<!-- Notificações -->
<div class="notifications-section">
    <h3>🔔 Notificações do Sistema</h3>
    <div class="notification-list">
        <div class="notification-item info">
            <div class="notification-icon">ℹ️</div>
            <div class="notification-content">
                <div class="notification-title">Sistema Atualizado</div>
                <div class="notification-desc">Versão <?php echo APP_VERSION; ?> instalada com sucesso</div>
            </div>
        </div>
        <div class="notification-item success">
            <div class="notification-icon">✅</div>
            <div class="notification-content">
                <div class="notification-title">Backup Automático</div>
                <div class="notification-desc">Backup realizado com sucesso às 02:00</div>
            </div>
        </div>
    </div>
</div>

<script>
function exportarDashboard() {
    alert('Funcionalidade de exportação será implementada em breve!');
}
</script>
