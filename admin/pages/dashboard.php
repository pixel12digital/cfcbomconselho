<?php
// Verificar se as variáveis estão definidas
if (!isset($stats)) $stats = [];
if (!isset($ultimas_atividades)) $ultimas_atividades = [];

// Obter dados adicionais para os módulos do dashboard
try {
    // Dados para módulo de fases
    $fases_data = [
        'cadastro' => $db->count('alunos', 'status = ?', ['ativo']),
        'confirmacao' => $db->count('alunos', 'status = ? AND operacoes LIKE ?', ['ativo', '%"confirmacao":true%']),
        'exames_aptidao' => $db->count('alunos', 'status = ? AND operacoes LIKE ?', ['ativo', '%"exames_aptidao":true%']),
        'curso_teorico' => $db->count('alunos', 'status = ? AND operacoes LIKE ?', ['ativo', '%"curso_teorico":true%']),
        'aulas_praticas' => $db->count('alunos', 'status = ? AND operacoes LIKE ?', ['ativo', '%"aulas_praticas":true%']),
        'prova_pratica' => $db->count('alunos', 'status = ? AND operacoes LIKE ?', ['ativo', '%"prova_pratica":true%']),
        'cnh' => $db->count('alunos', 'status = ? AND operacoes LIKE ?', ['ativo', '%"cnh":true%']),
        'cnh_retirada' => $db->count('alunos', 'status = ? AND operacoes LIKE ?', ['ativo', '%"cnh_retirada":true%'])
    ];
    
    // Dados para módulo de volume de vendas
    $volume_data = [];
    for ($i = 1; $i <= 12; $i++) {
        $month_start = date('Y-m-01', mktime(0, 0, 0, $i, 1, date('Y')));
        $month_end = date('Y-m-t', mktime(0, 0, 0, $i, 1, date('Y')));
        $volume_data[date('M', mktime(0, 0, 0, $i, 1, date('Y')))] = $db->count('alunos', 'criado_em BETWEEN ? AND ?', [$month_start, $month_end]);
    }
    
    // Dados para ocupação da agenda
    $ocupacao_data = [
        'total_aulas_mes' => $db->count('aulas', 'MONTH(data_aula) = ? AND YEAR(data_aula) = ?', [date('n'), date('Y')]),
        'total_instrutores' => $db->count('instrutores', 'ativo = ?', [1]),
        'total_veiculos' => $db->count('veiculos', 'ativo = ?', [1])
    ];
    
} catch (Exception $e) {
    $fases_data = [
        'cadastro' => 0, 'confirmacao' => 0, 'exames_aptidao' => 0, 'curso_teorico' => 0,
        'aulas_praticas' => 0, 'prova_pratica' => 0, 'cnh' => 0, 'cnh_retirada' => 0
    ];
    $volume_data = array_fill_keys(['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'], 0);
    $ocupacao_data = ['total_aulas_mes' => 0, 'total_instrutores' => 0, 'total_veiculos' => 0];
}
?>

<!-- Header Compacto da Página -->
<div class="page-header-compact">
    <h1 class="page-title-compact">Dashboard Administrativo</h1>
    <p class="page-subtitle-compact">Visão geral do sistema CFC - Indicadores por módulos</p>
</div>

<!-- Ações Rápidas Compactas -->
<div class="quick-actions-compact">
    <div class="actions-grid-compact">
        <a href="index.php?page=alunos&action=create" class="action-card-compact">
            <div class="action-icon-compact">
                <i class="fas fa-user-plus"></i>
            </div>
            <div class="action-label-compact">Cadastrar Aluno</div>
        </a>
        
        <a href="index.php?page=agendamento" class="action-card-compact">
            <div class="action-icon-compact">
                <i class="fas fa-calendar-plus"></i>
            </div>
            <div class="action-label-compact">Agendar Aula</div>
        </a>
        
        <a href="index.php?page=relatorios&action=alunos" class="action-card-compact">
            <div class="action-icon-compact">
                <i class="fas fa-chart-bar"></i>
            </div>
            <div class="action-label-compact">Gerar Relatório</div>
        </a>
        
        <a href="index.php?page=veiculos&action=create" class="action-card-compact">
            <div class="action-icon-compact">
                <i class="fas fa-car"></i>
            </div>
            <div class="action-label-compact">Novo Veículo</div>
        </a>
        
        <a href="index.php?page=instrutores&action=create" class="action-card-compact">
            <div class="action-icon-compact">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="action-label-compact">Novo Instrutor</div>
        </a>
        
        <a href="index.php?page=usuarios&action=create" class="action-card-compact">
            <div class="action-icon-compact">
                <i class="fas fa-user-cog"></i>
            </div>
            <div class="action-label-compact">Novo Usuário</div>
        </a>
    </div>
</div>

<!-- Sistema de Módulos de Indicadores -->
<div class="dashboard-modules">
    <!-- Navegação por Abas -->
    <div class="modules-nav">
        <div class="nav-tabs">
            <button class="nav-tab active" data-module="overview">
                <i class="fas fa-chart-line"></i>
                <span>Visão Geral</span>
            </button>
            <button class="nav-tab" data-module="fases">
                <i class="fas fa-route"></i>
                <span>Fases</span>
            </button>
            <button class="nav-tab" data-module="volume">
                <i class="fas fa-chart-bar"></i>
                <span>Volume</span>
            </button>
            <button class="nav-tab" data-module="financeiro">
                <i class="fas fa-dollar-sign"></i>
                <span>Financeiro</span>
            </button>
            <button class="nav-tab" data-module="agenda">
                <i class="fas fa-calendar-alt"></i>
                <span>Agenda</span>
            </button>
            <button class="nav-tab" data-module="exames">
                <i class="fas fa-clipboard-check"></i>
                <span>Exames</span>
            </button>
            <button class="nav-tab" data-module="prazos">
                <i class="fas fa-clock"></i>
                <span>Prazos</span>
            </button>
        </div>
    </div>

    <!-- Conteúdo dos Módulos -->
    <div class="modules-content">
        
        <!-- Módulo: Visão Geral -->
        <div class="module-content active" id="module-overview">
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
        </div>
        
        <!-- Módulo: Indicadores por Fases -->
        <div class="module-content" id="module-fases">
            <div class="module-header">
                <h3 class="module-title">Indicadores por Fases do Processo</h3>
                <p class="module-subtitle">Acompanhamento do progresso dos alunos em cada etapa</p>
            </div>
            
            <div class="fases-grid">
                <div class="fase-card">
                    <div class="fase-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="fase-content">
                        <div class="fase-value"><?php echo number_format($fases_data['cadastro']); ?></div>
                        <div class="fase-label">Cadastro</div>
                        <div class="fase-progress">
                            <div class="progress-bar" style="width: 100%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="fase-card">
                    <div class="fase-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="fase-content">
                        <div class="fase-value"><?php echo number_format($fases_data['confirmacao']); ?></div>
                        <div class="fase-label">Confirmação de Dados</div>
                        <div class="fase-progress">
                            <div class="progress-bar" style="width: 85%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="fase-card">
                    <div class="fase-icon">
                        <i class="fas fa-stethoscope"></i>
                    </div>
                    <div class="fase-content">
                        <div class="fase-value"><?php echo number_format($fases_data['exames_aptidao']); ?></div>
                        <div class="fase-label">Exames de Aptidão</div>
                        <div class="fase-progress">
                            <div class="progress-bar" style="width: 70%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="fase-card">
                    <div class="fase-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="fase-content">
                        <div class="fase-value"><?php echo number_format($fases_data['curso_teorico']); ?></div>
                        <div class="fase-label">Curso Teórico</div>
                        <div class="fase-progress">
                            <div class="progress-bar" style="width: 60%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="fase-card">
                    <div class="fase-icon">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="fase-content">
                        <div class="fase-value"><?php echo number_format($fases_data['aulas_praticas']); ?></div>
                        <div class="fase-label">Aulas Práticas</div>
                        <div class="fase-progress">
                            <div class="progress-bar" style="width: 45%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="fase-card">
                    <div class="fase-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="fase-content">
                        <div class="fase-value"><?php echo number_format($fases_data['prova_pratica']); ?></div>
                        <div class="fase-label">Prova Prática</div>
                        <div class="fase-progress">
                            <div class="progress-bar" style="width: 30%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="fase-card">
                    <div class="fase-icon">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div class="fase-content">
                        <div class="fase-value"><?php echo number_format($fases_data['cnh']); ?></div>
                        <div class="fase-label">CNH</div>
                        <div class="fase-progress">
                            <div class="progress-bar" style="width: 20%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="fase-card">
                    <div class="fase-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="fase-content">
                        <div class="fase-value"><?php echo number_format($fases_data['cnh_retirada']); ?></div>
                        <div class="fase-label">CNH Retirada</div>
                        <div class="fase-progress">
                            <div class="progress-bar" style="width: 15%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <!-- Módulo: Volume de Vendas/Matrículas -->
        <div class="module-content" id="module-volume">
            <div class="module-header">
                <h3 class="module-title">Volume de Matrículas</h3>
                <p class="module-subtitle">Comparativo mensal de matrículas realizadas</p>
            </div>
            
            <div class="volume-chart">
                <div class="chart-container">
                    <div class="chart-placeholder">
                        <div class="chart-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div class="chart-text">Gráfico de Volume Mensal</div>
                        <div class="chart-subtext">Visualize o crescimento das matrículas ao longo do ano</div>
                    </div>
                </div>
                
                <div class="volume-stats">
                    <?php foreach ($volume_data as $mes => $valor): ?>
                    <div class="volume-item">
                        <div class="volume-month"><?php echo $mes; ?></div>
                        <div class="volume-bar">
                            <div class="volume-fill" style="width: <?php echo min(100, ($valor / max(array_values($volume_data))) * 100); ?>%"></div>
                        </div>
                        <div class="volume-value"><?php echo number_format($valor); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Módulo: Receitas x Despesas -->
        <div class="module-content" id="module-financeiro">
            <div class="module-header">
                <h3 class="module-title">Receitas x Despesas</h3>
                <p class="module-subtitle">Visão financeira do CFC</p>
            </div>
            
            <div class="financeiro-grid">
                <div class="financeiro-card receitas">
                    <div class="financeiro-icon">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="financeiro-content">
                        <div class="financeiro-value">R$ 45.000</div>
                        <div class="financeiro-label">Contas a Receber</div>
                        <div class="financeiro-change positive">+12% este mês</div>
                    </div>
                </div>
                
                <div class="financeiro-card despesas">
                    <div class="financeiro-icon">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div class="financeiro-content">
                        <div class="financeiro-value">R$ 28.500</div>
                        <div class="financeiro-label">Contas a Pagar</div>
                        <div class="financeiro-change negative">+5% este mês</div>
                    </div>
                </div>
                
                <div class="financeiro-card saldo">
                    <div class="financeiro-icon">
                        <i class="fas fa-balance-scale"></i>
                    </div>
                    <div class="financeiro-content">
                        <div class="financeiro-value">R$ 16.500</div>
                        <div class="financeiro-label">Saldo Previsto</div>
                        <div class="financeiro-change positive">+18% este mês</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Módulo: Ocupação da Agenda -->
        <div class="module-content" id="module-agenda">
            <div class="module-header">
                <h3 class="module-title">Ocupação da Agenda</h3>
                <p class="module-subtitle">Análise de utilização de recursos</p>
            </div>
            
            <div class="agenda-grid">
                <div class="agenda-card">
                    <div class="agenda-header">
                        <h4>Por Instrutor</h4>
                        <span class="agenda-percentage">75%</span>
                    </div>
                    <div class="agenda-progress">
                        <div class="progress-bar" style="width: 75%"></div>
                    </div>
                    <div class="agenda-stats">
                        <span><?php echo $ocupacao_data['total_instrutores']; ?> instrutores ativos</span>
                    </div>
                </div>
                
                <div class="agenda-card">
                    <div class="agenda-header">
                        <h4>Por Veículo</h4>
                        <span class="agenda-percentage">68%</span>
                    </div>
                    <div class="agenda-progress">
                        <div class="progress-bar" style="width: 68%"></div>
                    </div>
                    <div class="agenda-stats">
                        <span><?php echo $ocupacao_data['total_veiculos']; ?> veículos disponíveis</span>
                    </div>
                </div>
                
                <div class="agenda-card">
                    <div class="agenda-header">
                        <h4>Este Mês</h4>
                        <span class="agenda-percentage">82%</span>
                    </div>
                    <div class="agenda-progress">
                        <div class="progress-bar" style="width: 82%"></div>
                    </div>
                    <div class="agenda-stats">
                        <span><?php echo $ocupacao_data['total_aulas_mes']; ?> aulas agendadas</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Módulo: Gestão de Exames -->
        <div class="module-content" id="module-exames">
            <div class="module-header">
                <h3 class="module-title">Gestão de Exames</h3>
                <p class="module-subtitle">Performance nos exames teóricos e práticos</p>
            </div>
            
            <div class="exames-grid">
                <div class="exame-card">
                    <div class="exame-header">
                        <h4>Exames Teóricos</h4>
                        <div class="exame-icon">
                            <i class="fas fa-book-open"></i>
                        </div>
                    </div>
                    <div class="exame-stats">
                        <div class="exame-stat">
                            <span class="exame-label">Realizados:</span>
                            <span class="exame-value">156</span>
                        </div>
                        <div class="exame-stat">
                            <span class="exame-label">Aprovados:</span>
                            <span class="exame-value success">142</span>
                        </div>
                        <div class="exame-stat">
                            <span class="exame-label">Reprovados:</span>
                            <span class="exame-value danger">14</span>
                        </div>
                        <div class="exame-stat">
                            <span class="exame-label">Taxa Aprovação:</span>
                            <span class="exame-value success">91%</span>
                        </div>
                    </div>
                </div>
                
                <div class="exame-card">
                    <div class="exame-header">
                        <h4>Exames Práticos</h4>
                        <div class="exame-icon">
                            <i class="fas fa-car-side"></i>
                        </div>
                    </div>
                    <div class="exame-stats">
                        <div class="exame-stat">
                            <span class="exame-label">Realizados:</span>
                            <span class="exame-value">89</span>
                        </div>
                        <div class="exame-stat">
                            <span class="exame-label">Aprovados:</span>
                            <span class="exame-value success">78</span>
                        </div>
                        <div class="exame-stat">
                            <span class="exame-label">Reprovados:</span>
                            <span class="exame-value danger">11</span>
                        </div>
                        <div class="exame-stat">
                            <span class="exame-label">Taxa Aprovação:</span>
                            <span class="exame-value success">88%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Módulo: Prazos Médios -->
        <div class="module-content" id="module-prazos">
            <div class="module-header">
                <h3 class="module-title">Prazos Médios</h3>
                <p class="module-subtitle">Tempo médio de conclusão por fase</p>
            </div>
            
            <div class="prazos-grid">
                <div class="prazo-card">
                    <div class="prazo-phase">Cadastro → Confirmação</div>
                    <div class="prazo-days">3 dias</div>
                    <div class="prazo-bar">
                        <div class="progress-bar" style="width: 20%"></div>
                    </div>
                </div>
                
                <div class="prazo-card">
                    <div class="prazo-phase">Confirmação → Exames</div>
                    <div class="prazo-days">7 dias</div>
                    <div class="prazo-bar">
                        <div class="progress-bar" style="width: 40%"></div>
                    </div>
                </div>
                
                <div class="prazo-card">
                    <div class="prazo-phase">Exames → Curso Teórico</div>
                    <div class="prazo-days">15 dias</div>
                    <div class="prazo-bar">
                        <div class="progress-bar" style="width: 60%"></div>
                    </div>
                </div>
                
                <div class="prazo-card">
                    <div class="prazo-phase">Teórico → Aulas Práticas</div>
                    <div class="prazo-days">30 dias</div>
                    <div class="prazo-bar">
                        <div class="progress-bar" style="width: 80%"></div>
                    </div>
                </div>
                
                <div class="prazo-card">
                    <div class="prazo-phase">Práticas → Prova</div>
                    <div class="prazo-days">45 dias</div>
                    <div class="prazo-bar">
                        <div class="progress-bar" style="width: 90%"></div>
                    </div>
                </div>
                
                <div class="prazo-card">
                    <div class="prazo-phase">Prova → CNH</div>
                    <div class="prazo-days">60 dias</div>
                    <div class="prazo-bar">
                        <div class="progress-bar" style="width: 100%"></div>
                    </div>
                </div>
            </div>
            
            <div class="prazo-total">
                <div class="prazo-total-card">
                    <div class="prazo-total-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="prazo-total-content">
                        <div class="prazo-total-value">160 dias</div>
                        <div class="prazo-total-label">Tempo médio total de conclusão</div>
                    </div>
                </div>
            </div>
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

<!-- Scripts específicos do dashboard -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sistema de navegação entre módulos
    const navTabs = document.querySelectorAll('.nav-tab');
    const moduleContents = document.querySelectorAll('.module-content');
    
    navTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetModule = this.getAttribute('data-module');
            
            // Remover classe active de todas as abas
            navTabs.forEach(t => t.classList.remove('active'));
            // Adicionar classe active na aba clicada
            this.classList.add('active');
            
            // Ocultar todos os módulos
            moduleContents.forEach(content => {
                content.classList.remove('active');
            });
            
            // Mostrar o módulo selecionado
            const targetContent = document.getElementById(`module-${targetModule}`);
            if (targetContent) {
                targetContent.classList.add('active');
                
                // Animar entrada do módulo
                targetContent.style.opacity = '0';
                targetContent.style.transform = 'translateY(20px)';
                targetContent.style.transition = 'all 0.3s ease';
                
                setTimeout(() => {
                    targetContent.style.opacity = '1';
                    targetContent.style.transform = 'translateY(0)';
                }, 100);
            }
        });
    });
    
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
    
    // Animações para cards de fases
    const faseCards = document.querySelectorAll('.fase-card');
    faseCards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '0';
            card.style.transform = 'translateX(-20px)';
            card.style.transition = 'all 0.4s ease';
            
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateX(0)';
            }, 100);
        }, index * 150);
    });
    
    // Animações para barras de progresso
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        bar.style.transition = 'width 1.5s ease-in-out';
        
        setTimeout(() => {
            bar.style.width = width;
        }, 500);
    });
});
</script>
