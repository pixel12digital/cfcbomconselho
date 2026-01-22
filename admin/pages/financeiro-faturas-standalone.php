<?php
/**
 * Página de Faturas (Receitas) - Template
 * Sistema CFC - Bom Conselho
 */

// Verificar se sistema financeiro está habilitado
if (!defined('FINANCEIRO_ENABLED') || !FINANCEIRO_ENABLED) {
    echo '<div class="alert alert-danger">Módulo financeiro não está habilitado.</div>';
    return;
}

// Verificar permissões (as variáveis já estão disponíveis do admin/index.php)
if (!$isAdmin && $user['tipo'] !== 'secretaria') {
    echo '<div class="alert alert-danger">Você não tem permissão para acessar esta página.</div>';
    return;
}

// Obter estatísticas
try {
    $stats = [
        'total_faturas' => $db->count('financeiro_faturas'),
        'faturas_abertas' => $db->count('financeiro_faturas', 'status = ?', ['aberta']),
        'faturas_pagas' => $db->count('financeiro_faturas', 'status = ?', ['paga']),
        'faturas_vencidas' => $db->count('financeiro_faturas', 'status = ?', ['vencida']),
        'valor_total_aberto' => $db->fetchColumn("SELECT COALESCE(SUM(valor_total), 0) FROM financeiro_faturas WHERE status IN ('aberta', 'vencida')"),
        'valor_total_pago' => $db->fetchColumn("SELECT COALESCE(SUM(valor_total), 0) FROM financeiro_faturas WHERE status = 'paga'")
    ];
} catch (Exception $e) {
    $stats = [
        'total_faturas' => 0,
        'faturas_abertas' => 0,
        'faturas_pagas' => 0,
        'faturas_vencidas' => 0,
        'valor_total_aberto' => 0,
        'valor_total_pago' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faturas - Sistema CFC</title>
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
        .status-aberta { background-color: #fff3cd; color: #856404; }
        .status-paga { background-color: #d4edda; color: #155724; }
        .status-vencida { background-color: #f8d7da; color: #721c24; }
        .status-parcial { background-color: #d1ecf1; color: #0c5460; }
        .status-cancelada { background-color: #e2e3e5; color: #383d41; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="fas fa-file-invoice me-2"></i>Faturas (Receitas)</h2>
                        <?php if (isset($_GET['aluno_id']) && !empty($_GET['aluno_id'])): ?>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="../pages/alunos.php" class="text-decoration-none">
                                        <i class="fas fa-graduation-cap me-1"></i>Alunos
                                    </a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="../pages/historico-aluno.php?id=<?php echo $_GET['aluno_id']; ?>" class="text-decoration-none">
                                        <i class="fas fa-history me-1"></i>Histórico do Aluno
                                    </a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">
                                    <i class="fas fa-dollar-sign me-1"></i>Faturas
                                </li>
                            </ol>
                        </nav>
                        <?php endif; ?>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaFatura">
                        <i class="fas fa-plus me-1"></i>Nova Fatura
                    </button>
                </div>

                <!-- Cards de Estatísticas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="mb-0">Total de Faturas</h6>
                                    <h3 class="mb-0"><?php echo $stats['total_faturas']; ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-file-invoice fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card success">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="mb-0">Faturas Pagas</h6>
                                    <h3 class="mb-0"><?php echo $stats['faturas_pagas']; ?></h3>
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
                                    <h6 class="mb-0">Faturas Vencidas</h6>
                                    <h3 class="mb-0"><?php echo $stats['faturas_vencidas']; ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card info">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="mb-0">Valor em Aberto</h6>
                                    <h3 class="mb-0">R$ <?php echo number_format($stats['valor_total_aberto'], 2, ',', '.'); ?></h3>
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
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="">Todos</option>
                                        <option value="aberta">Aberta</option>
                                        <option value="paga">Paga</option>
                                        <option value="vencida">Vencida</option>
                                        <option value="parcial">Parcial</option>
                                        <option value="cancelada">Cancelada</option>
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
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-outline-primary">
                                            <i class="fas fa-search me-1"></i>Filtrar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabela de Faturas -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="tabelaFaturas">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Número</th>
                                        <th>Aluno</th>
                                        <th>Descrição</th>
                                        <th>Vencimento</th>
                                        <th>Valor</th>
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

    <!-- Modal Nova Fatura -->
    <div class="modal fade" id="modalNovaFatura" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nova Fatura</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formNovaFatura">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Aluno</label>
                                <select class="form-select" name="aluno_id" required>
                                    <option value="">Selecione um aluno</option>
                                    <!-- Carregado via JavaScript -->
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Matrícula</label>
                                <select class="form-select" name="matricula_id" required>
                                    <option value="">Selecione uma matrícula</option>
                                    <!-- Carregado via JavaScript -->
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-8">
                                <label class="form-label">Descrição</label>
                                <input type="text" class="form-control" name="descricao" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Vencimento</label>
                                <input type="date" class="form-control" name="vencimento" required>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <label class="form-label">Valor</label>
                                <input type="number" class="form-control" name="valor" step="0.01" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Desconto</label>
                                <input type="number" class="form-control" name="desconto" step="0.01" value="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Acréscimo</label>
                                <input type="number" class="form-control" name="acrescimo" step="0.01" value="0">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Meio de Pagamento</label>
                                <select class="form-select" name="meio">
                                    <option value="pix">PIX</option>
                                    <option value="boleto">Boleto</option>
                                    <option value="cartao">Cartão</option>
                                    <option value="dinheiro">Dinheiro</option>
                                    <option value="transferencia">Transferência</option>
                                    <option value="outro">Outro</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipo</label>
                                <select class="form-select" name="tipo_fatura" id="tipoFatura">
                                    <option value="unica">Fatura Única</option>
                                    <option value="parcelas">Parcelas</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3" id="camposParcelas" style="display: none;">
                            <div class="col-md-6">
                                <label class="form-label">Número de Parcelas</label>
                                <input type="number" class="form-control" name="parcelas" min="2" max="12">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Intervalo (dias)</label>
                                <input type="number" class="form-control" name="intervalo_dias" value="30" min="1">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="criarFatura()">Criar Fatura</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Pagamento -->
    <div class="modal fade" id="modalPagamento" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Registrar Pagamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formPagamento">
                        <input type="hidden" name="fatura_id">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Data do Pagamento</label>
                                <input type="date" class="form-control" name="data_pagamento" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Valor Pago</label>
                                <input type="number" class="form-control" name="valor_pago" step="0.01" required>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Método</label>
                                <select class="form-select" name="metodo">
                                    <option value="pix">PIX</option>
                                    <option value="boleto">Boleto</option>
                                    <option value="cartao">Cartão</option>
                                    <option value="dinheiro">Dinheiro</option>
                                    <option value="transferencia">Transferência</option>
                                    <option value="outro">Outro</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Comprovante URL</label>
                                <input type="url" class="form-control" name="comprovante_url">
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
                    <button type="button" class="btn btn-success" onclick="registrarPagamento()">Registrar Pagamento</button>
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
            carregarFaturas();
            carregarAlunos();
            configurarEventos();
        });

        function configurarEventos() {
            // Filtros
            document.getElementById('formFiltros').addEventListener('submit', function(e) {
                e.preventDefault();
                paginaAtual = 1;
                filtrosAtuais = new FormData(this);
                carregarFaturas();
            });

            // Tipo de fatura
            document.getElementById('tipoFatura').addEventListener('change', function() {
                const camposParcelas = document.getElementById('camposParcelas');
                if (this.value === 'parcelas') {
                    camposParcelas.style.display = 'block';
                } else {
                    camposParcelas.style.display = 'none';
                }
            });

            // Aluno selecionado
            document.querySelector('select[name="aluno_id"]').addEventListener('change', function() {
                carregarMatriculas(this.value);
            });
        }

        function carregarFaturas() {
            const params = new URLSearchParams();
            params.append('page', paginaAtual);
            params.append('limit', 20);

            // Adicionar filtros
            for (const [key, value] of filtrosAtuais.entries()) {
                if (value) params.append(key, value);
            }

            fetch(`../api/faturas.php?${params}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderizarFaturas(data.faturas);
                        renderizarPaginacao(data.pagination);
                    } else {
                        mostrarAlerta('Erro ao carregar faturas: ' + data.error, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    mostrarAlerta('Erro ao carregar faturas', 'danger');
                });
        }

        function renderizarFaturas(faturas) {
            const tbody = document.querySelector('#tabelaFaturas tbody');
            tbody.innerHTML = '';

            faturas.forEach(fatura => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${fatura.numero || 'N/A'}</td>
                    <td>${fatura.aluno_nome}</td>
                    <td>${fatura.descricao}</td>
                    <td>${formatarData(fatura.vencimento)}</td>
                    <td>R$ ${formatarValor(fatura.valor_liquido)}</td>
                    <td><span class="status-badge status-${fatura.status}">${fatura.status.toUpperCase()}</span></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="verFatura(${fatura.id})" title="Ver">
                                <i class="fas fa-eye"></i>
                            </button>
                            ${fatura.status !== 'paga' && fatura.status !== 'cancelada' ? `
                                <button class="btn btn-outline-success" onclick="abrirModalPagamento(${fatura.id})" title="Pagamento">
                                    <i class="fas fa-money-bill"></i>
                                </button>
                            ` : ''}
                            ${fatura.status === 'aberta' ? `
                                <button class="btn btn-outline-danger" onclick="cancelarFatura(${fatura.id})" title="Cancelar">
                                    <i class="fas fa-times"></i>
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
            carregarFaturas();
        }

        function carregarAlunos() {
            fetch('../api/alunos.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.querySelector('select[name="aluno_id"]');
                        select.innerHTML = '<option value="">Selecione um aluno</option>';
                        data.alunos.forEach(aluno => {
                            const option = document.createElement('option');
                            option.value = aluno.id;
                            option.textContent = `${aluno.nome} (${aluno.cpf})`;
                            select.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Erro ao carregar alunos:', error));
        }

        function carregarMatriculas(alunoId) {
            if (!alunoId) return;

            fetch(`../api/matriculas.php?aluno_id=${alunoId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.querySelector('select[name="matricula_id"]');
                        select.innerHTML = '<option value="">Selecione uma matrícula</option>';
                        data.matriculas.forEach(matricula => {
                            const option = document.createElement('option');
                            option.value = matricula.id;
                            option.textContent = `${matricula.categoria_cnh} - ${matricula.tipo_servico}`;
                            select.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Erro ao carregar matrículas:', error));
        }

        function criarFatura() {
            const form = document.getElementById('formNovaFatura');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            // Validar dados
            if (!data.aluno_id || !data.matricula_id || !data.descricao || !data.valor || !data.vencimento) {
                mostrarAlerta('Preencha todos os campos obrigatórios', 'warning');
                return;
            }

            // Preparar dados para API
            const dadosAPI = {
                matricula_id: data.matricula_id,
                aluno_id: data.aluno_id,
                descricao: data.descricao,
                valor: parseFloat(data.valor),
                desconto: parseFloat(data.desconto || 0),
                acrescimo: parseFloat(data.acrescimo || 0),
                vencimento: data.vencimento,
                meio: data.meio
            };

            if (data.tipo_fatura === 'parcelas') {
                dadosAPI.valor_total = dadosAPI.valor;
                dadosAPI.parcelas = parseInt(data.parcelas);
                dadosAPI.primeiro_vencimento = data.vencimento;
                dadosAPI.intervalo_dias = parseInt(data.intervalo_dias);
            }

            fetch('../api/faturas.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(dadosAPI)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarAlerta(data.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById('modalNovaFatura')).hide();
                    form.reset();
                    carregarFaturas();
                } else {
                    mostrarAlerta('Erro ao criar fatura: ' + data.error, 'danger');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarAlerta('Erro ao criar fatura', 'danger');
            });
        }

        function abrirModalPagamento(faturaId) {
            document.querySelector('input[name="fatura_id"]').value = faturaId;
            document.querySelector('input[name="data_pagamento"]').value = new Date().toISOString().split('T')[0];
            bootstrap.Modal.getInstance(document.getElementById('modalPagamento')).show();
        }

        function registrarPagamento() {
            const form = document.getElementById('formPagamento');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            fetch('../api/pagamentos.php', {
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
                    bootstrap.Modal.getInstance(document.getElementById('modalPagamento')).hide();
                    form.reset();
                    carregarFaturas();
                } else {
                    mostrarAlerta('Erro ao registrar pagamento: ' + data.error, 'danger');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarAlerta('Erro ao registrar pagamento', 'danger');
            });
        }

        function cancelarFatura(faturaId) {
            if (!confirm('Tem certeza que deseja cancelar esta fatura?')) return;

            fetch(`../api/faturas.php?id=${faturaId}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarAlerta(data.message, 'success');
                    carregarFaturas();
                } else {
                    mostrarAlerta('Erro ao cancelar fatura: ' + data.error, 'danger');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarAlerta('Erro ao cancelar fatura', 'danger');
            });
        }

        function verFatura(faturaId) {
            // Implementar visualização detalhada da fatura
            window.open(`../api/faturas.php?id=${faturaId}`, '_blank');
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

