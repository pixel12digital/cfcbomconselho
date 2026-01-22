<?php
/**
 * Página de Relatórios Financeiros
 * Sistema CFC - Bom Conselho
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar se sistema financeiro está habilitado
if (!defined('FINANCEIRO_ENABLED') || !FINANCEIRO_ENABLED) {
    header('Location: /cfc-bom-conselho/admin/index.php');
    exit;
}

// Verificar autenticação e permissão
if (!isLoggedIn()) {
    header('Location: /cfc-bom-conselho/admin/index.php');
    exit;
}

$currentUser = getCurrentUser();
if (!$currentUser || !in_array($currentUser['tipo'], ['admin', 'secretaria'])) {
    header('Location: /cfc-bom-conselho/admin/index.php');
    exit;
}

$db = Database::getInstance();

// Obter período padrão (últimos 30 dias)
$dataInicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$dataFim = $_GET['data_fim'] ?? date('Y-m-d');

// Obter estatísticas do período
try {
    $stats = [
        'receitas_total' => $db->fetchColumn("SELECT COALESCE(SUM(valor_liquido), 0) FROM faturas WHERE status = 'paga' AND DATE(data_pagamento) BETWEEN ? AND ?", [$dataInicio, $dataFim]),
        'receitas_abertas' => $db->fetchColumn("SELECT COALESCE(SUM(valor_liquido), 0) FROM faturas WHERE status IN ('aberta', 'parcial', 'vencida') AND vencimento BETWEEN ? AND ?", [$dataInicio, $dataFim]),
        'despesas_pagas' => $db->fetchColumn("SELECT COALESCE(SUM(valor), 0) FROM despesas WHERE pago = 1 AND DATE(data_pagamento) BETWEEN ? AND ?", [$dataInicio, $dataFim]),
        'despesas_pendentes' => $db->fetchColumn("SELECT COALESCE(SUM(valor), 0) FROM despesas WHERE pago = 0 AND vencimento BETWEEN ? AND ?", [$dataInicio, $dataFim]),
        'faturas_vencidas' => $db->count('faturas', 'status = ? AND vencimento < ?', ['vencida', date('Y-m-d')]),
        'inadimplencia_valor' => $db->fetchColumn("SELECT COALESCE(SUM(valor_liquido), 0) FROM faturas WHERE status = 'vencida' AND vencimento < ?", [date('Y-m-d')])
    ];
} catch (Exception $e) {
    $stats = [
        'receitas_total' => 0,
        'receitas_abertas' => 0,
        'despesas_pagas' => 0,
        'despesas_pendentes' => 0,
        'faturas_vencidas' => 0,
        'inadimplencia_valor' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios Financeiros - Sistema CFC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        .stats-card.success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        .stats-card.warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .stats-card.info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 2rem;
        }
        .tab-content {
            padding-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-chart-line me-2"></i>Relatórios Financeiros</h2>
                    <div class="btn-group">
                        <?php if ($stats['receitas_total'] > 0 || $stats['despesas_pagas'] > 0): ?>
                        <button class="btn btn-outline-success" onclick="exportarCSV()" title="Exportar dados para CSV">
                            <i class="fas fa-file-csv me-1"></i>Exportar CSV
                        </button>
                        <?php else: ?>
                        <button class="btn btn-outline-secondary" disabled title="Nenhum dado disponível para exportação">
                            <i class="fas fa-file-csv me-1"></i>Exportar CSV
                        </button>
                        <?php endif; ?>
                        
                        <button class="btn btn-outline-primary" onclick="imprimirRelatorio()" title="Imprimir relatório">
                            <i class="fas fa-print me-1"></i>Imprimir
                        </button>
                    </div>
                </div>

                <!-- Filtros de Período -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form id="formFiltros" method="GET">
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="form-label">Data Início</label>
                                    <input type="date" class="form-control" name="data_inicio" value="<?php echo $dataInicio; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Data Fim</label>
                                    <input type="date" class="form-control" name="data_fim" value="<?php echo $dataFim; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search me-1"></i>Filtrar
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid">
                                        <button type="button" class="btn btn-outline-secondary" onclick="definirPeriodo('hoje')">
                                            Hoje
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Cards de Resumo -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card success">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="mb-0">Receitas Pagas</h6>
                                    <h3 class="mb-0">R$ <?php echo number_format($stats['receitas_total'], 2, ',', '.'); ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-arrow-up fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card warning">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="mb-0">Receitas em Aberto</h6>
                                    <h3 class="mb-0">R$ <?php echo number_format($stats['receitas_abertas'], 2, ',', '.'); ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="mb-0">Despesas Pagas</h6>
                                    <h3 class="mb-0">R$ <?php echo number_format($stats['despesas_pagas'], 2, ',', '.'); ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-arrow-down fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card info">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="mb-0">Fluxo de Caixa</h6>
                                    <h3 class="mb-0">R$ <?php echo number_format($stats['receitas_total'] - $stats['despesas_pagas'], 2, ',', '.'); ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-balance-scale fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Abas de Relatórios -->
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" id="relatoriosTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="receitas-tab" data-bs-toggle="tab" data-bs-target="#receitas" type="button" role="tab">
                                    <i class="fas fa-arrow-up me-1"></i>Receitas
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="despesas-tab" data-bs-toggle="tab" data-bs-target="#despesas" type="button" role="tab">
                                    <i class="fas fa-arrow-down me-1"></i>Despesas
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="fluxo-tab" data-bs-toggle="tab" data-bs-target="#fluxo" type="button" role="tab">
                                    <i class="fas fa-balance-scale me-1"></i>Fluxo de Caixa
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="inadimplencia-tab" data-bs-toggle="tab" data-bs-target="#inadimplencia" type="button" role="tab">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Inadimplência
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="relatoriosTabsContent">
                            <!-- Aba Receitas -->
                            <div class="tab-pane fade show active" id="receitas" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="chart-container">
                                            <canvas id="chartReceitas"></canvas>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <h5>Resumo de Receitas</h5>
                                        <table class="table table-sm">
                                            <tr>
                                                <td>Total Recebido:</td>
                                                <td class="text-end"><strong>R$ <?php echo number_format($stats['receitas_total'], 2, ',', '.'); ?></strong></td>
                                            </tr>
                                            <tr>
                                                <td>Em Aberto:</td>
                                                <td class="text-end">R$ <?php echo number_format($stats['receitas_abertas'], 2, ',', '.'); ?></td>
                                            </tr>
                                            <tr>
                                                <td>Vencidas:</td>
                                                <td class="text-end text-danger"><?php echo $stats['faturas_vencidas']; ?> faturas</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Aba Despesas -->
                            <div class="tab-pane fade" id="despesas" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="chart-container">
                                            <canvas id="chartDespesas"></canvas>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <h5>Resumo de Despesas</h5>
                                        <table class="table table-sm">
                                            <tr>
                                                <td>Total Pago:</td>
                                                <td class="text-end"><strong>R$ <?php echo number_format($stats['despesas_pagas'], 2, ',', '.'); ?></strong></td>
                                            </tr>
                                            <tr>
                                                <td>Pendente:</td>
                                                <td class="text-end">R$ <?php echo number_format($stats['despesas_pendentes'], 2, ',', '.'); ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Aba Fluxo de Caixa -->
                            <div class="tab-pane fade" id="fluxo" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="chart-container">
                                            <canvas id="chartFluxo"></canvas>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <h5>Fluxo de Caixa</h5>
                                        <table class="table table-sm">
                                            <tr>
                                                <td>Receitas:</td>
                                                <td class="text-end text-success">R$ <?php echo number_format($stats['receitas_total'], 2, ',', '.'); ?></td>
                                            </tr>
                                            <tr>
                                                <td>Despesas:</td>
                                                <td class="text-end text-danger">R$ <?php echo number_format($stats['despesas_pagas'], 2, ',', '.'); ?></td>
                                            </tr>
                                            <tr class="table-active">
                                                <td><strong>Saldo:</strong></td>
                                                <td class="text-end"><strong>R$ <?php echo number_format($stats['receitas_total'] - $stats['despesas_pagas'], 2, ',', '.'); ?></strong></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Aba Inadimplência -->
                            <div class="tab-pane fade" id="inadimplencia" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="chart-container">
                                            <canvas id="chartInadimplencia"></canvas>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <h5>Inadimplência</h5>
                                        <table class="table table-sm">
                                            <tr>
                                                <td>Faturas Vencidas:</td>
                                                <td class="text-end text-danger"><strong><?php echo $stats['faturas_vencidas']; ?></strong></td>
                                            </tr>
                                            <tr>
                                                <td>Valor em Atraso:</td>
                                                <td class="text-end text-danger"><strong>R$ <?php echo number_format($stats['inadimplencia_valor'], 2, ',', '.'); ?></strong></td>
                                            </tr>
                                        </table>
                                        <div class="alert alert-warning">
                                            <small>
                                                <i class="fas fa-info-circle me-1"></i>
                                                Faturas vencidas há mais de 30 dias são consideradas inadimplentes.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Carregar gráficos quando a página estiver pronta
        document.addEventListener('DOMContentLoaded', function() {
            carregarGraficos();
        });

        function carregarGraficos() {
            // Gráfico de Receitas
            const ctxReceitas = document.getElementById('chartReceitas').getContext('2d');
            new Chart(ctxReceitas, {
                type: 'doughnut',
                data: {
                    labels: ['Recebidas', 'Em Aberto'],
                    datasets: [{
                        data: [<?php echo $stats['receitas_total']; ?>, <?php echo $stats['receitas_abertas']; ?>],
                        backgroundColor: ['#28a745', '#ffc107'],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Gráfico de Despesas
            const ctxDespesas = document.getElementById('chartDespesas').getContext('2d');
            new Chart(ctxDespesas, {
                type: 'bar',
                data: {
                    labels: ['Pagas', 'Pendentes'],
                    datasets: [{
                        label: 'Valor (R$)',
                        data: [<?php echo $stats['despesas_pagas']; ?>, <?php echo $stats['despesas_pendentes']; ?>],
                        backgroundColor: ['#dc3545', '#fd7e14'],
                        borderWidth: 1
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

            // Gráfico de Fluxo de Caixa
            const ctxFluxo = document.getElementById('chartFluxo').getContext('2d');
            new Chart(ctxFluxo, {
                type: 'line',
                data: {
                    labels: ['Receitas', 'Despesas', 'Saldo'],
                    datasets: [{
                        label: 'Valor (R$)',
                        data: [<?php echo $stats['receitas_total']; ?>, <?php echo $stats['despesas_pagas']; ?>, <?php echo $stats['receitas_total'] - $stats['despesas_pagas']; ?>],
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        fill: true,
                        tension: 0.4
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

            // Gráfico de Inadimplência
            const ctxInadimplencia = document.getElementById('chartInadimplencia').getContext('2d');
            new Chart(ctxInadimplencia, {
                type: 'pie',
                data: {
                    labels: ['Em Dia', 'Vencidas'],
                    datasets: [{
                        data: [<?php echo max(0, $stats['receitas_total'] - $stats['inadimplencia_valor']); ?>, <?php echo $stats['inadimplencia_valor']; ?>],
                        backgroundColor: ['#28a745', '#dc3545'],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        function definirPeriodo(periodo) {
            const hoje = new Date();
            let dataInicio, dataFim;

            switch(periodo) {
                case 'hoje':
                    dataInicio = dataFim = hoje.toISOString().split('T')[0];
                    break;
                case 'semana':
                    dataInicio = new Date(hoje.setDate(hoje.getDate() - 7)).toISOString().split('T')[0];
                    dataFim = new Date().toISOString().split('T')[0];
                    break;
                case 'mes':
                    dataInicio = new Date(hoje.setMonth(hoje.getMonth() - 1)).toISOString().split('T')[0];
                    dataFim = new Date().toISOString().split('T')[0];
                    break;
                case 'ano':
                    dataInicio = new Date(hoje.setFullYear(hoje.getFullYear() - 1)).toISOString().split('T')[0];
                    dataFim = new Date().toISOString().split('T')[0];
                    break;
            }

            document.querySelector('input[name="data_inicio"]').value = dataInicio;
            document.querySelector('input[name="data_fim"]').value = dataFim;
        }

        function exportarCSV() {
            const dataInicio = document.querySelector('input[name="data_inicio"]').value;
            const dataFim = document.querySelector('input[name="data_fim"]').value;
            
            // Implementar exportação CSV
            mostrarAlerta('Funcionalidade de exportação CSV em desenvolvimento', 'info');
        }

        function imprimirRelatorio() {
            window.print();
        }

        function mostrarAlerta(mensagem, tipo) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${tipo} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                ${mensagem}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);

            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 5000);
        }
    </script>
</body>
</html>

