<?php
/**
 * Página de Despesas (Contas a Pagar)
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

// Obter estatísticas
try {
    $stats = [
        'total_despesas' => $db->count('despesas'),
        'despesas_pagas' => $db->count('despesas', 'pago = ?', [1]),
        'despesas_pendentes' => $db->count('despesas', 'pago = ?', [0]),
        'despesas_vencidas' => $db->count('despesas', 'pago = ? AND vencimento < ?', [0, date('Y-m-d')]),
        'valor_total_pago' => $db->fetchColumn("SELECT COALESCE(SUM(valor), 0) FROM despesas WHERE pago = 1"),
        'valor_total_pendente' => $db->fetchColumn("SELECT COALESCE(SUM(valor), 0) FROM despesas WHERE pago = 0")
    ];
} catch (Exception $e) {
    $stats = [
        'total_despesas' => 0,
        'despesas_pagas' => 0,
        'despesas_pendentes' => 0,
        'despesas_vencidas' => 0,
        'valor_total_pago' => 0,
        'valor_total_pendente' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Despesas - Sistema CFC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .status-pago { background-color: #d4edda; color: #155724; }
        .status-pendente { background-color: #fff3cd; color: #856404; }
        .status-vencido { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-receipt me-2"></i>Despesas (Contas a Pagar)</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaDespesa">
                        <i class="fas fa-plus me-1"></i>Nova Despesa
                    </button>
                </div>

                <!-- Cards de Estatísticas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="mb-0">Total de Despesas</h6>
                                    <h3 class="mb-0"><?php echo $stats['total_despesas']; ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-receipt fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card success">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="mb-0">Despesas Pagas</h6>
                                    <h3 class="mb-0"><?php echo $stats['despesas_pagas']; ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card warning">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="mb-0">Despesas Pendentes</h6>
                                    <h3 class="mb-0"><?php echo $stats['despesas_pendentes']; ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card info">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="mb-0">Valor Pendente</h6>
                                    <h3 class="mb-0">R$ <?php echo number_format($stats['valor_total_pendente'], 2, ',', '.'); ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-dollar-sign fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form id="formFiltros">
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="form-label">Categoria</label>
                                    <select class="form-select" name="categoria">
                                        <option value="">Todas</option>
                                        <option value="combustivel">Combustível</option>
                                        <option value="manutencao">Manutenção</option>
                                        <option value="aluguel">Aluguel</option>
                                        <option value="taxas">Taxas</option>
                                        <option value="salarios">Salários</option>
                                        <option value="outros">Outros</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="pago">
                                        <option value="">Todos</option>
                                        <option value="0">Pendente</option>
                                        <option value="1">Pago</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Vencimento De</label>
                                    <input type="date" class="form-control" name="vencimento_de">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Vencimento Até</label>
                                    <input type="date" class="form-control" name="vencimento_ate">
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="fas fa-search me-1"></i>Filtrar
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary ms-2" onclick="limparFiltros()">
                                        <i class="fas fa-times me-1"></i>Limpar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabela de Despesas -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="tabelaDespesas">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Título</th>
                                        <th>Fornecedor</th>
                                        <th>Categoria</th>
                                        <th>Valor</th>
                                        <th>Vencimento</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dados carregados via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                        <div id="paginacao" class="d-flex justify-content-center mt-3">
                            <!-- Paginação carregada via JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nova Despesa -->
    <div class="modal fade" id="modalNovaDespesa" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nova Despesa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formNovaDespesa">
                        <div class="row">
                            <div class="col-md-8">
                                <label class="form-label">Título</label>
                                <input type="text" class="form-control" name="titulo" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Categoria</label>
                                <select class="form-select" name="categoria" required>
                                    <option value="outros">Outros</option>
                                    <option value="combustivel">Combustível</option>
                                    <option value="manutencao">Manutenção</option>
                                    <option value="aluguel">Aluguel</option>
                                    <option value="taxas">Taxas</option>
                                    <option value="salarios">Salários</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Fornecedor</label>
                                <input type="text" class="form-control" name="fornecedor">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Valor</label>
                                <input type="number" class="form-control" name="valor" step="0.01" required>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Vencimento</label>
                                <input type="date" class="form-control" name="vencimento" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Método de Pagamento</label>
                                <select class="form-select" name="metodo">
                                    <option value="pix">PIX</option>
                                    <option value="boleto">Boleto</option>
                                    <option value="cartao">Cartão</option>
                                    <option value="dinheiro">Dinheiro</option>
                                    <option value="transferencia">Transferência</option>
                                    <option value="outro">Outro</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Anexo URL</label>
                                <input type="url" class="form-control" name="anexo_url">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Observações</label>
                                <input type="text" class="form-control" name="obs">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="criarDespesa()">Criar Despesa</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Marcar Pago -->
    <div class="modal fade" id="modalMarcarPago" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Marcar como Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formMarcarPago">
                        <input type="hidden" name="id">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Data do Pagamento</label>
                                <input type="date" class="form-control" name="data_pagamento" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Método</label>
                                <select class="form-select" name="metodo" required>
                                    <option value="pix">PIX</option>
                                    <option value="boleto">Boleto</option>
                                    <option value="cartao">Cartão</option>
                                    <option value="dinheiro">Dinheiro</option>
                                    <option value="transferencia">Transferência</option>
                                    <option value="outro">Outro</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <label class="form-label">Observações</label>
                                <textarea class="form-control" name="obs" rows="3"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="marcarComoPago()">Marcar como Pago</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let paginaAtual = 1;
        let filtrosAtuais = {};

        // Carregar dados iniciais
        document.addEventListener('DOMContentLoaded', function() {
            carregarDespesas();
            configurarEventos();
        });

        function configurarEventos() {
            // Filtros
            document.getElementById('formFiltros').addEventListener('submit', function(e) {
                e.preventDefault();
                paginaAtual = 1;
                filtrosAtuais = new FormData(this);
                carregarDespesas();
            });
        }

        function limparFiltros() {
            document.getElementById('formFiltros').reset();
            filtrosAtuais = {};
            paginaAtual = 1;
            carregarDespesas();
        }

        function carregarDespesas() {
            const params = new URLSearchParams();
            params.append('page', paginaAtual);
            params.append('limit', 20);

            // Adicionar filtros
            for (const [key, value] of filtrosAtuais.entries()) {
                if (value) params.append(key, value);
            }

            fetch(`../api/despesas.php?${params}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderizarDespesas(data.despesas);
                        renderizarPaginacao(data.pagination);
                    } else {
                        mostrarAlerta('Erro ao carregar despesas: ' + data.error, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    mostrarAlerta('Erro ao carregar despesas', 'danger');
                });
        }

        function renderizarDespesas(despesas) {
            const tbody = document.querySelector('#tabelaDespesas tbody');
            tbody.innerHTML = '';

            despesas.forEach(despesa => {
                const tr = document.createElement('tr');
                const status = despesa.pago ? 'pago' : (new Date(despesa.vencimento) < new Date() ? 'vencido' : 'pendente');
                const statusText = despesa.pago ? 'PAGO' : (new Date(despesa.vencimento) < new Date() ? 'VENCIDO' : 'PENDENTE');
                
                tr.innerHTML = `
                    <td>${despesa.titulo}</td>
                    <td>${despesa.fornecedor || 'N/A'}</td>
                    <td><span class="badge bg-secondary">${despesa.categoria.toUpperCase()}</span></td>
                    <td>R$ ${formatarValor(despesa.valor)}</td>
                    <td>${formatarData(despesa.vencimento)}</td>
                    <td><span class="status-badge status-${status}">${statusText}</span></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="editarDespesa(${despesa.id})" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            ${!despesa.pago ? `
                                <button class="btn btn-outline-success" onclick="abrirModalMarcarPago(${despesa.id})" title="Marcar Pago">
                                    <i class="fas fa-check"></i>
                                </button>
                            ` : ''}
                            ${!despesa.pago ? `
                                <button class="btn btn-outline-danger" onclick="excluirDespesa(${despesa.id})" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>
                            ` : ''}
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function renderizarPaginacao(pagination) {
            const container = document.getElementById('paginacao');
            container.innerHTML = '';

            if (pagination.pages <= 1) return;

            const ul = document.createElement('ul');
            ul.className = 'pagination';

            // Botão anterior
            if (pagination.page > 1) {
                const li = document.createElement('li');
                li.className = 'page-item';
                li.innerHTML = `<a class="page-link" href="#" onclick="irParaPagina(${pagination.page - 1})">Anterior</a>`;
                ul.appendChild(li);
            }

            // Páginas
            for (let i = 1; i <= pagination.pages; i++) {
                const li = document.createElement('li');
                li.className = `page-item ${i === pagination.page ? 'active' : ''}`;
                li.innerHTML = `<a class="page-link" href="#" onclick="irParaPagina(${i})">${i}</a>`;
                ul.appendChild(li);
            }

            // Botão próximo
            if (pagination.page < pagination.pages) {
                const li = document.createElement('li');
                li.className = 'page-item';
                li.innerHTML = `<a class="page-link" href="#" onclick="irParaPagina(${pagination.page + 1})">Próximo</a>`;
                ul.appendChild(li);
            }

            container.appendChild(ul);
        }

        function irParaPagina(pagina) {
            paginaAtual = pagina;
            carregarDespesas();
        }

        function criarDespesa() {
            const form = document.getElementById('formNovaDespesa');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            // Validar dados
            if (!data.titulo || !data.valor || !data.vencimento) {
                mostrarAlerta('Preencha todos os campos obrigatórios', 'warning');
                return;
            }

            fetch('../api/despesas.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarAlerta(data.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('modalNovaDespesa')).hide();
                    form.reset();
                    carregarDespesas();
                } else {
                    mostrarAlerta('Erro ao criar despesa: ' + data.error, 'danger');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarAlerta('Erro ao criar despesa', 'danger');
            });
        }

        function abrirModalMarcarPago(despesaId) {
            document.querySelector('input[name="id"]').value = despesaId;
            document.querySelector('input[name="data_pagamento"]').value = new Date().toISOString().split('T')[0];
            bootstrap.Modal.getInstance(document.getElementById('modalMarcarPago')).show();
        }

        function marcarComoPago() {
            const form = document.getElementById('formMarcarPago');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            const dadosAPI = {
                id: data.id,
                pago: 1,
                data_pagamento: data.data_pagamento,
                metodo: data.metodo,
                obs: data.obs
            };

            fetch(`../api/despesas.php?id=${data.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(dadosAPI)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarAlerta(data.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('modalMarcarPago')).hide();
                    form.reset();
                    carregarDespesas();
                } else {
                    mostrarAlerta('Erro ao marcar como pago: ' + data.error, 'danger');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarAlerta('Erro ao marcar como pago', 'danger');
            });
        }

        function editarDespesa(despesaId) {
            // Implementar edição de despesa
            mostrarAlerta('Funcionalidade de edição em desenvolvimento', 'info');
        }

        function excluirDespesa(despesaId) {
            if (!confirm('Tem certeza que deseja excluir esta despesa?')) return;

            fetch(`../api/despesas.php?id=${despesaId}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarAlerta(data.message, 'success');
                    carregarDespesas();
                } else {
                    mostrarAlerta('Erro ao excluir despesa: ' + data.error, 'danger');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarAlerta('Erro ao excluir despesa', 'danger');
            });
        }

        function formatarData(data) {
            if (!data) return 'N/A';
            return new Date(data).toLocaleDateString('pt-BR');
        }

        function formatarValor(valor) {
            return parseFloat(valor).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
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

