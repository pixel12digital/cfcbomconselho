<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste CSS - Admin</title>
    <link href="assets/css/admin.css" rel="stylesheet">
    <style>
        /* CSS de emergência */
        .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding: 1.5rem; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; border-radius: 10px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #e9ecef; }
        .stat-content { display: flex; justify-content: space-between; align-items: center; }
        .stat-info { flex: 1; }
        .stat-label { color: #6c757d; font-size: 0.9rem; text-transform: uppercase; margin-bottom: 0.5rem; }
        .stat-value { font-size: 2rem; font-weight: bold; color: #007bff; }
        .stat-icon { font-size: 3rem; opacity: 0.7; }
        .chart-section, .status-section, .activities-section, .quick-actions, .notifications-section { background: white; border-radius: 10px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #e9ecef; }
        .status-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; }
        .status-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; }
        .status-dot { width: 12px; height: 12px; border-radius: 50%; }
        .status-dot.active { background-color: #28a745; }
        .status-dot.in-progress { background-color: #ffc107; }
        .status-dot.inactive { background-color: #dc3545; }
        .status-count { font-weight: bold; color: #007bff; }
        .actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem; }
        .action-card { display: flex; flex-direction: column; align-items: center; padding: 1.5rem; background: #f8f9fa; border-radius: 8px; text-decoration: none; color: #333; transition: all 0.3s; }
        .action-card:hover { background: #e9ecef; transform: translateY(-2px); text-decoration: none; color: #333; }
        .action-icon { font-size: 2rem; margin-bottom: 0.5rem; }
        .action-label { font-weight: 500; text-align: center; }
        .btn { display: inline-block; padding: 0.5rem 1rem; border: none; border-radius: 5px; text-decoration: none; text-align: center; cursor: pointer; transition: all 0.3s; font-weight: 500; }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-success { background-color: #28a745; color: white; }
        .btn-warning { background-color: #ffc107; color: #212529; }
        .btn-secondary { background-color: #6c757d; color: white; }
        .btn:hover { transform: translateY(-1px); }
        .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
        .activities-list { max-height: 300px; overflow-y: auto; }
        .activity-item { display: flex; align-items: center; gap: 1rem; padding: 1rem; border-bottom: 1px solid #e9ecef; }
        .activity-item:last-child { border-bottom: none; }
        .activity-icon { font-size: 1.5rem; width: 40px; text-align: center; }
        .activity-info { flex: 1; }
        .activity-title { font-weight: 600; color: #333; }
        .activity-desc { color: #6c757d; font-size: 0.9rem; }
        .activity-time { color: #6c757d; font-size: 0.8rem; }
        .no-activities { text-align: center; color: #6c757d; padding: 2rem; }
        .notification-list { margin-top: 1rem; }
        .notification-item { display: flex; align-items: flex-start; gap: 1rem; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .notification-item.info { background-color: #d1ecf1; border-left: 4px solid #17a2b8; }
        .notification-item.success { background-color: #d4edda; border-left: 4px solid #28a745; }
        .notification-icon { font-size: 1.5rem; margin-top: 0.2rem; }
        .notification-title { font-weight: 600; color: #333; margin-bottom: 0.2rem; }
        .notification-desc { color: #6c757d; font-size: 0.9rem; }
        .chart-container { background: #f8f9fa; border-radius: 8px; padding: 2rem; text-align: center; }
        .chart-placeholder { color: #6c757d; }
        .chart-placeholder p { margin: 0.5rem 0; }
    </style>
</head>
<body style="background-color: #f8f9fa; padding: 2rem;">
    <h1>🧪 Teste CSS - Admin</h1>
    
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <h1>📊 Dashboard</h1>
        <div class="dashboard-actions">
            <button type="button" class="btn btn-secondary" onclick="window.print()">
                🖨️ Imprimir
            </button>
            <button type="button" class="btn btn-secondary" onclick="alert('Exportar!')">
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
                    <div class="stat-value">150</div>
                </div>
                <div class="stat-icon">🎓</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-info">
                    <div class="stat-label">Total de Instrutores</div>
                    <div class="stat-value">25</div>
                </div>
                <div class="stat-icon">👨‍🏫</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-info">
                    <div class="stat-label">Aulas Hoje</div>
                    <div class="stat-value">12</div>
                </div>
                <div class="stat-icon">📅</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-info">
                    <div class="stat-label">Total de Veículos</div>
                    <div class="stat-value">18</div>
                </div>
                <div class="stat-icon">🚗</div>
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
                <span class="status-count">120</span>
            </div>
            <div class="status-item">
                <span class="status-dot in-progress"></span>
                <span class="status-label">Em Curso</span>
                <span class="status-count">25</span>
            </div>
            <div class="status-item">
                <span class="status-dot inactive"></span>
                <span class="status-label">Inativos</span>
                <span class="status-count">5</span>
            </div>
        </div>
    </div>

    <!-- Ações Rápidas -->
    <div class="quick-actions">
        <h3>⚡ Ações Rápidas</h3>
        <div class="actions-grid">
            <a href="#" class="action-card">
                <div class="action-icon">👤</div>
                <div class="action-label">Novo Aluno</div>
            </a>
            <a href="#" class="action-card">
                <div class="action-icon">👨‍🏫</div>
                <div class="action-label">Novo Instrutor</div>
            </a>
            <a href="#" class="action-card">
                <div class="action-icon">📅</div>
                <div class="action-label">Nova Aula</div>
            </a>
            <a href="#" class="action-card">
                <div class="action-icon">🚗</div>
                <div class="action-label">Novo Veículo</div>
            </a>
        </div>
    </div>

    <p><a href="index.php">← Voltar para o Painel Admin</a></p>
</body>
</html>
