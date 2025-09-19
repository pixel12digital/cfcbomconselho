<?php
/**
 * Página de Gestão de Turmas
 * Baseada na análise do sistema eCondutor
 * 
 * @author Sistema CFC Bom Conselho
 * @version 1.0
 * @since 2024
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

// Incluir dependências
require_once __DIR__ . '/../includes/turma_manager.php';

$turmaManager = new TurmaManager();

// Buscar instrutores para o dropdown
$db = Database::getInstance();
$instrutores = $db->fetchAll("
    SELECT id, nome, email 
    FROM instrutores 
    WHERE ativo = 1 
    ORDER BY nome ASC
");

// Buscar estatísticas
$stats = $turmaManager->obterEstatisticas($user['cfc_id'] ?? 1);
$estatisticas = $stats['sucesso'] ? $stats['dados'] : [];

// Processar filtros
$filtros = [
    'busca' => $_GET['busca'] ?? '',
    'data_inicio' => $_GET['data_inicio'] ?? '',
    'data_fim' => $_GET['data_fim'] ?? '',
    'status' => $_GET['status'] ?? '',
    'tipo_aula' => $_GET['tipo_aula'] ?? '',
    'cfc_id' => $_SESSION['cfc_id'] ?? 1,
    'limite' => (int)($_GET['limite'] ?? 10),
    'pagina' => (int)($_GET['pagina'] ?? 0)
];

$resultado = $turmaManager->listarTurmas($filtros);
$turmas = $resultado['sucesso'] ? $resultado['dados'] : [];
$totalTurmas = $resultado['sucesso'] ? $resultado['total'] : 0;

// Calcular paginação
$totalPaginas = ceil($totalTurmas / $filtros['limite']);
$paginaAtual = $filtros['pagina'] + 1;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Turmas - CFC Bom Conselho</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .page-header {
            background: linear-gradient(135deg, #00A651 0%, #007A3D 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            border-left: 4px solid #00A651;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: #00A651;
        }
        
        .filters-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .turmas-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table th {
            background-color: #00A651;
            color: white;
            border: none;
            font-weight: 600;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-ativo { background-color: #d4edda; color: #155724; }
        .status-agendado { background-color: #fff3cd; color: #856404; }
        .status-inativo { background-color: #f8d7da; color: #721c24; }
        .status-concluido { background-color: #d1ecf1; color: #0c5460; }
        
        .btn-action {
            padding: 0.375rem 0.75rem;
            margin: 0 0.125rem;
            border-radius: 5px;
        }
        
        .pagination {
            justify-content: center;
            margin-top: 2rem;
        }
        
        .page-link {
            color: #00A651;
            border-color: #00A651;
        }
        
        .page-link:hover {
            background-color: #00A651;
            border-color: #00A651;
            color: white;
        }
        
        .page-item.active .page-link {
            background-color: #00A651;
            border-color: #00A651;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0">
                        <i class="fas fa-graduation-cap me-3"></i>
                        Gestão de Turmas
                    </h1>
                    <p class="mb-0 opacity-75">Gerencie turmas teóricas e práticas do CFC</p>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-light me-2" onclick="abrirModalNovaTurma()">
                        <i class="fas fa-plus me-2"></i>
                        Nova Turma
                    </button>
                    <button class="btn btn-outline-light" onclick="exportarTurmas()">
                        <i class="fas fa-download me-2"></i>
                        Exportar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Estatísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo $estatisticas['total_turmas'] ?? 0; ?></div>
                            <div class="text-muted">Total de Turmas</div>
                        </div>
                        <i class="fas fa-graduation-cap fa-2x text-muted"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo $estatisticas['turmas_ativas'] ?? 0; ?></div>
                            <div class="text-muted">Turmas Ativas</div>
                        </div>
                        <i class="fas fa-play-circle fa-2x text-success"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo $estatisticas['turmas_agendadas'] ?? 0; ?></div>
                            <div class="text-muted">Agendadas</div>
                        </div>
                        <i class="fas fa-calendar fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo $estatisticas['total_alunos_matriculados'] ?? 0; ?></div>
                            <div class="text-muted">Alunos Matriculados</div>
                        </div>
                        <i class="fas fa-users fa-2x text-info"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-section">
            <form method="GET" id="filtros-form">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="busca" class="form-label">Pesquisa</label>
                        <input type="text" class="form-control" id="busca" name="busca" 
                               placeholder="Pesquisa por nome da turma ou instrutor"
                               value="<?php echo htmlspecialchars($filtros['busca']); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="data_inicio" class="form-label">Início do Curso</label>
                        <input type="date" class="form-control" id="data_inicio" name="data_inicio"
                               value="<?php echo htmlspecialchars($filtros['data_inicio']); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="data_fim" class="form-label">Final do Curso</label>
                        <input type="date" class="form-control" id="data_fim" name="data_fim"
                               value="<?php echo htmlspecialchars($filtros['data_fim']); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="status" class="form-label">Situação</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Todos</option>
                            <option value="ativo" <?php echo $filtros['status'] === 'ativo' ? 'selected' : ''; ?>>Ativos</option>
                            <option value="agendado" <?php echo $filtros['status'] === 'agendado' ? 'selected' : ''; ?>>Agendados</option>
                            <option value="inativo" <?php echo $filtros['status'] === 'inativo' ? 'selected' : ''; ?>>Inativos</option>
                            <option value="concluido" <?php echo $filtros['status'] === 'concluido' ? 'selected' : ''; ?>>Concluídos</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="limite" class="form-label">Mostrar</label>
                        <select class="form-select" id="limite" name="limite">
                            <option value="10" <?php echo $filtros['limite'] === 10 ? 'selected' : ''; ?>>10</option>
                            <option value="25" <?php echo $filtros['limite'] === 25 ? 'selected' : ''; ?>>25</option>
                            <option value="50" <?php echo $filtros['limite'] === 50 ? 'selected' : ''; ?>>50</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search me-2"></i>
                            Pesquisar
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="limparFiltros()">
                            <i class="fas fa-times me-2"></i>
                            Limpar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabela de Turmas -->
        <div class="turmas-table">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Nome da Turma</th>
                            <th>Instrutor</th>
                            <th>Início</th>
                            <th>Final</th>
                            <th>Alunos</th>
                            <th>Situação</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($turmas)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-search fa-2x text-muted mb-3"></i>
                                    <div class="text-muted">Nenhuma turma encontrada</div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($turmas as $turma): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($turma['nome']); ?></div>
                                        <small class="text-muted"><?php echo ucfirst($turma['tipo_aula']); ?></small>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($turma['instrutor_nome']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($turma['instrutor_email']); ?></small>
                                    </td>
                                    <td><?php echo $turma['data_inicio'] ? date('d/m/Y', strtotime($turma['data_inicio'])) : '-'; ?></td>
                                    <td><?php echo $turma['data_fim'] ? date('d/m/Y', strtotime($turma['data_fim'])) : '-'; ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $turma['total_alunos']; ?></span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $turma['status']; ?>">
                                            <?php echo ucfirst($turma['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary btn-action" 
                                                onclick="editarTurma(<?php echo $turma['id']; ?>)"
                                                title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-info btn-action" 
                                                onclick="verDetalhes(<?php echo $turma['id']; ?>)"
                                                title="Ver Detalhes">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger btn-action" 
                                                onclick="excluirTurma(<?php echo $turma['id']; ?>)"
                                                title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Paginação -->
        <?php if ($totalPaginas > 1): ?>
            <nav aria-label="Paginação">
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                        <li class="page-item <?php echo $i === $paginaAtual ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i - 1])); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <!-- Modal de Nova Turma -->
    <div class="modal fade" id="modalNovaTurma" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-graduation-cap me-2"></i>
                        Criar uma nova turma teórica
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formNovaTurma">
                        <!-- Dados Básicos -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nome_turma" class="form-label">Nome da turma *</label>
                                <input type="text" class="form-control" id="nome_turma" name="nome_turma" required>
                            </div>
                            <div class="col-md-6">
                                <label for="instrutor_id" class="form-label">Instrutor *</label>
                                <select class="form-select" id="instrutor_id" name="instrutor_id" required>
                                    <option value="">Selecione</option>
                                    <?php foreach ($instrutores as $instrutor): ?>
                                        <option value="<?php echo $instrutor['id']; ?>">
                                            <?php echo htmlspecialchars($instrutor['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="tipo_aula" class="form-label">Tipo de aula *</label>
                                <select class="form-select" id="tipo_aula" name="tipo_aula" required>
                                    <option value="">Selecione</option>
                                    <option value="teorica">Teórica</option>
                                    <option value="pratica">Prática</option>
                                    <option value="mista">Mista</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="categoria_cnh" class="form-label">Categoria CNH</label>
                                <select class="form-select" id="categoria_cnh" name="categoria_cnh">
                                    <option value="">Selecione</option>
                                    <option value="A">A - Motocicletas</option>
                                    <option value="B">B - Automóveis</option>
                                    <option value="AB">AB - Motocicletas + Automóveis</option>
                                    <option value="C">C - Veículos de Carga</option>
                                    <option value="D">D - Veículos de Passageiros</option>
                                    <option value="E">E - Combinação de Veículos</option>
                                </select>
                            </div>
                        </div>

                        <!-- Aulas da Turma -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">Aulas da Turma</h6>
                                <button type="button" class="btn btn-sm btn-success" onclick="adicionarAula()">
                                    <i class="fas fa-plus me-1"></i>
                                    Adicionar Aula
                                </button>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-sm" id="tabelaAulas">
                                    <thead>
                                        <tr>
                                            <th width="5%">N°</th>
                                            <th width="40%">Nome da aula</th>
                                            <th width="15%">Minutos</th>
                                            <th width="25%">Dia da aula</th>
                                            <th width="15%">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyAulas">
                                        <!-- Aulas serão adicionadas dinamicamente -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-arrow-left me-2"></i>
                        Voltar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="salvarTurma()">
                        <i class="fas fa-save me-2"></i>
                        Salvar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        let contadorAulas = 0;
        
        // Abrir modal de nova turma
        function abrirModalNovaTurma() {
            const modal = new bootstrap.Modal(document.getElementById('modalNovaTurma'));
            modal.show();
            
            // Limpar formulário
            document.getElementById('formNovaTurma').reset();
            document.getElementById('tbodyAulas').innerHTML = '';
            contadorAulas = 0;
        }
        
        // Adicionar aula à tabela
        function adicionarAula() {
            contadorAulas++;
            const tbody = document.getElementById('tbodyAulas');
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${contadorAulas}</td>
                <td>
                    <input type="text" class="form-control form-control-sm" 
                           name="aulas[${contadorAulas}][nome_aula]" required>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm" 
                           name="aulas[${contadorAulas}][duracao_minutos]" 
                           value="50" min="1" max="120">
                </td>
                <td>
                    <input type="date" class="form-control form-control-sm" 
                           name="aulas[${contadorAulas}][data_aula]">
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger" 
                            onclick="removerAula(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            
            tbody.appendChild(row);
        }
        
        // Remover aula da tabela
        function removerAula(button) {
            button.closest('tr').remove();
            renumerarAulas();
        }
        
        // Renumerar aulas
        function renumerarAulas() {
            const rows = document.querySelectorAll('#tbodyAulas tr');
            rows.forEach((row, index) => {
                row.cells[0].textContent = index + 1;
            });
            contadorAulas = rows.length;
        }
        
        // Salvar turma
        async function salvarTurma() {
            const form = document.getElementById('formNovaTurma');
            const formData = new FormData(form);
            
            // Converter FormData para objeto
            const dados = {};
            for (let [key, value] of formData.entries()) {
                if (key.startsWith('aulas[')) {
                    const match = key.match(/aulas\[(\d+)\]\[(\w+)\]/);
                    if (match) {
                        const index = match[1];
                        const field = match[2];
                        
                        if (!dados.aulas) dados.aulas = [];
                        if (!dados.aulas[index]) dados.aulas[index] = {};
                        dados.aulas[index][field] = value;
                    }
                } else {
                    dados[key] = value;
                }
            }
            
            // Filtrar aulas vazias
            if (dados.aulas) {
                dados.aulas = dados.aulas.filter(aula => aula.nome_aula && aula.nome_aula.trim() !== '');
            }
            
            try {
                const response = await fetch('/admin/api/turmas.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(dados)
                });
                
                const resultado = await response.json();
                
                if (resultado.sucesso) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: resultado.mensagem
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: resultado.mensagem
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Erro ao salvar turma: ' + error.message
                });
            }
        }
        
        // Editar turma
        function editarTurma(id) {
            // Implementar edição
            console.log('Editar turma:', id);
        }
        
        // Ver detalhes
        function verDetalhes(id) {
            // Implementar visualização de detalhes
            console.log('Ver detalhes:', id);
        }
        
        // Excluir turma
        async function excluirTurma(id) {
            const result = await Swal.fire({
                title: 'Tem certeza?',
                text: 'Esta ação não pode ser desfeita!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            });
            
            if (result.isConfirmed) {
                try {
                    const response = await fetch(`/admin/api/turmas.php?id=${id}`, {
                        method: 'DELETE'
                    });
                    
                    const resultado = await response.json();
                    
                    if (resultado.sucesso) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Excluído!',
                            text: resultado.mensagem
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro!',
                            text: resultado.mensagem
                        });
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: 'Erro ao excluir turma: ' + error.message
                    });
                }
            }
        }
        
        // Limpar filtros
        function limparFiltros() {
            document.getElementById('filtros-form').reset();
            window.location.href = window.location.pathname;
        }
        
        // Exportar turmas
        function exportarTurmas() {
            // Implementar exportação
            console.log('Exportar turmas');
        }
        
        // Adicionar primeira aula automaticamente
        document.addEventListener('DOMContentLoaded', function() {
            // Adicionar uma aula inicial quando o modal abrir
            const modal = document.getElementById('modalNovaTurma');
            modal.addEventListener('shown.bs.modal', function() {
                if (contadorAulas === 0) {
                    adicionarAula();
                }
            });
        });
    </script>
</body>
</html>
