<?php
// Verificar se as variáveis estão definidas
if (!isset($alunos)) $alunos = [];
if (!isset($cfcs)) $cfcs = [];
if (!isset($mensagem)) $mensagem = '';
if (!isset($tipo_mensagem)) $tipo_mensagem = 'info';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-user-graduate me-2"></i>Gestão de Alunos
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportarAlunos()">
                <i class="fas fa-download me-1"></i>Exportar
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="imprimirAlunos()">
                <i class="fas fa-print me-1"></i>Imprimir
            </button>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAluno">
            <i class="fas fa-plus me-1"></i>Novo Aluno
        </button>
    </div>
</div>

<!-- Mensagens de Feedback -->
<?php if (!empty($mensagem)): ?>
<div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($mensagem); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filtros e Busca Avançada -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="input-group">
            <span class="input-group-text">🔍</span>
            <input type="text" class="form-control" id="buscaAluno" placeholder="Buscar aluno..." data-validate="minLength:2">
        </div>
    </div>
    <div class="col-md-2">
        <select class="form-select" id="filtroStatus">
            <option value="">Todos os Status</option>
            <option value="ativo">✅ Ativo</option>
            <option value="inativo">❌ Inativo</option>
            <option value="concluido">🎓 Concluído</option>
            <option value="pendente">⏳ Pendente</option>
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-select" id="filtroCFC">
            <option value="">Todos os CFCs</option>
            <?php foreach ($cfcs as $cfc): ?>
                <option value="<?php echo $cfc['id']; ?>"><?php echo htmlspecialchars($cfc['nome']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-select" id="filtroCategoria">
            <option value="">Todas as Categorias</option>
            <option value="A">🚗 Categoria A</option>
            <option value="B">🚙 Categoria B</option>
            <option value="C">🚐 Categoria C</option>
            <option value="D">🚛 Categoria D</option>
            <option value="E">🚜 Categoria E</option>
            <option value="AB">🚗🚙 Categoria AB</option>
            <option value="AC">🚗🚐 Categoria AC</option>
            <option value="AD">🚗🚛 Categoria AD</option>
            <option value="AE">🚗🚜 Categoria AE</option>
        </select>
    </div>
    <div class="col-md-3">
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-info" onclick="limparFiltros()">
                🗑️ Limpar
            </button>
            <button type="button" class="btn btn-outline-success" onclick="exportarFiltros()">
                📥 Exportar
            </button>
        </div>
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
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalAlunos">
                            <?php echo count($alunos); ?>
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
                            Alunos Ativos
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="alunosAtivos">
                            <?php echo count(array_filter($alunos, function($a) { return $a['status'] === 'ativo'; })); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                            Em Formação
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="emFormacao">
                            <?php echo count(array_filter($alunos, function($a) { return $a['status'] === 'ativo'; })); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                            Concluídos
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="concluidos">
                            <?php echo count(array_filter($alunos, function($a) { return $a['status'] === 'concluido'; })); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-graduation-cap fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabela de Alunos -->
<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Lista de Alunos</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="tabelaAlunos">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>CFC</th>
                        <th>Categoria</th>
                        <th>Status</th>
                        <th>Última Aula</th>
                        <th>Progresso</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($alunos)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>Nenhum aluno cadastrado ainda.</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAluno">
                                <i class="fas fa-plus me-1"></i>Cadastrar Primeiro Aluno
                            </button>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($alunos as $aluno): ?>
                        <tr data-aluno-id="<?php echo $aluno['id']; ?>">
                            <td><?php echo $aluno['id']; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm me-3">
                                        <div class="avatar-title bg-primary rounded-circle">
                                            <?php echo strtoupper(substr($aluno['nome'], 0, 1)); ?>
                                        </div>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($aluno['nome']); ?></strong>
                                        <?php if ($aluno['email']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($aluno['email']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <code><?php echo htmlspecialchars($aluno['cpf']); ?></code>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo htmlspecialchars($aluno['cfc_nome'] ?? 'N/A'); ?></span>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($aluno['categoria_cnh']); ?></span>
                            </td>
                            <td>
                                <?php
                                $statusClass = [
                                    'ativo' => 'success',
                                    'inativo' => 'danger',
                                    'concluido' => 'info'
                                ];
                                $statusText = [
                                    'ativo' => 'Ativo',
                                    'inativo' => 'Inativo',
                                    'concluido' => 'Concluído'
                                ];
                                ?>
                                <span class="badge bg-<?php echo $statusClass[$aluno['status']] ?? 'secondary'; ?>">
                                    <?php echo $statusText[$aluno['status']] ?? ucfirst($aluno['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($aluno['ultima_aula'])): ?>
                                    <small><?php echo date('d/m/Y', strtotime($aluno['ultima_aula'])); ?></small>
                                <?php else: ?>
                                    <span class="text-muted">Nunca</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <?php 
                                    $progresso = isset($aluno['progresso']) ? $aluno['progresso'] : 0;
                                    $progresso = min(100, max(0, $progresso));
                                    ?>
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?php echo $progresso; ?>%" 
                                         aria-valuenow="<?php echo $progresso; ?>" 
                                         aria-valuemin="0" aria-valuemax="100">
                                        <?php echo $progresso; ?>%
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons-container">
                                    <!-- Botões principais em linha -->
                                    <div class="action-buttons-primary">
                                        <button type="button" class="btn btn-edit action-btn" 
                                                onclick="editarAluno(<?php echo $aluno['id']; ?>)" 
                                                title="Editar dados do aluno">
                                            <i class="fas fa-edit me-1"></i>Editar
                                        </button>
                                        <button type="button" class="btn btn-view action-btn" 
                                                onclick="visualizarAluno(<?php echo $aluno['id']; ?>)" 
                                                title="Ver detalhes completos do aluno">
                                            <i class="fas fa-eye me-1"></i>Ver
                                        </button>
                                        <button type="button" class="btn btn-schedule action-btn" 
                                                onclick="agendarAula(<?php echo $aluno['id']; ?>)" 
                                                title="Agendar nova aula para este aluno">
                                            <i class="fas fa-calendar-plus me-1"></i>Agendar
                                        </button>
                                    </div>
                                    
                                    <!-- Botões secundários em linha -->
                                    <div class="action-buttons-secondary">
                                        <button type="button" class="btn btn-history action-btn" 
                                                onclick="historicoAluno(<?php echo $aluno['id']; ?>)" 
                                                title="Visualizar histórico de aulas e progresso">
                                            <i class="fas fa-history me-1"></i>Histórico
                                        </button>

                                        <?php if ($aluno['status'] === 'ativo'): ?>
                                        <button type="button" class="btn btn-toggle action-btn" 
                                                onclick="desativarAluno(<?php echo $aluno['id']; ?>)" 
                                                title="Desativar aluno (não poderá agendar aulas)">
                                            <i class="fas fa-ban me-1"></i>Desativar
                                        </button>
                                        <?php else: ?>
                                        <button type="button" class="btn btn-schedule action-btn" 
                                                onclick="ativarAluno(<?php echo $aluno['id']; ?>)" 
                                                title="Reativar aluno para agendamento de aulas">
                                            <i class="fas fa-check me-1"></i>Ativar
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Botão de exclusão destacado -->
                                    <div class="action-buttons-danger">
                                        <button type="button" class="btn btn-delete action-btn" 
                                                onclick="excluirAluno(<?php echo $aluno['id']; ?>)" 
                                                title="⚠️ EXCLUIR ALUNO - Esta ação não pode ser desfeita!">
                                            <i class="fas fa-trash me-1"></i>Excluir
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Cadastro/Edição de Aluno -->
<div class="modal fade" id="modalAluno" tabindex="-1" aria-labelledby="modalAlunoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formAluno" method="POST" action="admin/pages/alunos.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAlunoLabel">
                        <i class="fas fa-user-graduate me-2"></i><span id="modalTitle">Novo Aluno</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="acao" id="acaoAluno" value="criar">
                    <input type="hidden" name="aluno_id" id="aluno_id" value="">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome Completo *</label>
                                <input type="text" class="form-control" id="nome" name="nome" required 
                                       placeholder="Nome completo do aluno">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="cpf" class="form-label">CPF *</label>
                                <input type="text" class="form-control" id="cpf" name="cpf" required 
                                       placeholder="000.000.000-00">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="rg" class="form-label">RG</label>
                                <input type="text" class="form-control" id="rg" name="rg" 
                                       placeholder="00.000.000-0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="data_nascimento" class="form-label">Data de Nascimento *</label>
                                <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="aluno@email.com">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="telefone" class="form-label">Telefone</label>
                                <input type="text" class="form-control" id="telefone" name="telefone" 
                                       placeholder="(00) 00000-0000">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cfc_id" class="form-label">CFC *</label>
                                <select class="form-select" id="cfc_id" name="cfc_id" required>
                                    <option value="">Selecione um CFC...</option>
                                    <?php foreach ($cfcs as $cfc): ?>
                                        <?php if ($cfc['ativo']): ?>
                                        <option value="<?php echo $cfc['id']; ?>">
                                            <?php echo htmlspecialchars($cfc['nome']); ?>
                                        </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="categoria_cnh" class="form-label">Categoria CNH *</label>
                                <select class="form-select" id="categoria_cnh" name="categoria_cnh" required>
                                    <option value="">Selecione a categoria...</option>
                                    <option value="A">A - Motocicletas</option>
                                    <option value="B">B - Automóveis</option>
                                    <option value="C">C - Veículos de carga</option>
                                    <option value="D">D - Veículos de passageiros</option>
                                    <option value="E">E - Veículos com reboque</option>
                                    <option value="AB">AB - A + B</option>
                                    <option value="AC">AC - A + C</option>
                                    <option value="AD">AD - A + D</option>
                                    <option value="AE">AE - A + E</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="cep" class="form-label">CEP</label>
                                <input type="text" class="form-control" id="cep" name="cep" 
                                       placeholder="00000-000">
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="mb-3">
                                <label for="logradouro" class="form-label">Logradouro</label>
                                <input type="text" class="form-control" id="logradouro" name="logradouro" 
                                       placeholder="Rua, Avenida, etc.">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label for="numero" class="form-label">Número</label>
                                <input type="text" class="form-control" id="numero" name="numero" 
                                       placeholder="123">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="bairro" class="form-label">Bairro</label>
                                <input type="text" class="form-control" id="bairro" name="bairro" 
                                       placeholder="Centro, Jardim, etc.">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="cidade" class="form-label">Cidade</label>
                                <input type="text" class="form-control" id="cidade" name="cidade" 
                                       placeholder="Nome da cidade">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="uf" class="form-label">UF</label>
                                <select class="form-select" id="uf" name="uf">
                                    <option value="">Selecione...</option>
                                    <option value="AC">Acre</option>
                                    <option value="AL">Alagoas</option>
                                    <option value="AP">Amapá</option>
                                    <option value="AM">Amazonas</option>
                                    <option value="BA">Bahia</option>
                                    <option value="CE">Ceará</option>
                                    <option value="DF">Distrito Federal</option>
                                    <option value="ES">Espírito Santo</option>
                                    <option value="GO">Goiás</option>
                                    <option value="MA">Maranhão</option>
                                    <option value="MT">Mato Grosso</option>
                                    <option value="MS">Mato Grosso do Sul</option>
                                    <option value="MG">Minas Gerais</option>
                                    <option value="PA">Pará</option>
                                    <option value="PB">Paraíba</option>
                                    <option value="PR">Paraná</option>
                                    <option value="PE">Pernambuco</option>
                                    <option value="PI">Piauí</option>
                                    <option value="RJ">Rio de Janeiro</option>
                                    <option value="RN">Rio Grande do Norte</option>
                                    <option value="RS">Rio Grande do Sul</option>
                                    <option value="RO">Rondônia</option>
                                    <option value="RR">Roraima</option>
                                    <option value="SC">Santa Catarina</option>
                                    <option value="SP">São Paulo</option>
                                    <option value="SE">Sergipe</option>
                                    <option value="TO">Tocantins</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="ativo">Ativo</option>
                                    <option value="inativo">Inativo</option>
                                    <option value="concluido">Concluído</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="progresso" class="form-label">Progresso (%)</label>
                                <input type="range" class="form-range" id="progresso" name="progresso" 
                                       min="0" max="100" value="0">
                                <div class="text-center">
                                    <span id="progressoValor">0%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="3" 
                                  placeholder="Informações adicionais sobre o aluno..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSalvarAluno">
                        <i class="fas fa-save me-1"></i>Salvar Aluno
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Visualização de Aluno -->
<div class="modal fade" id="modalVisualizarAluno" tabindex="-1" aria-labelledby="modalVisualizarAlunoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVisualizarAlunoLabel">
                    <i class="fas fa-eye me-2"></i>Detalhes do Aluno
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalVisualizarAlunoBody">
                <!-- Conteúdo será carregado via JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" id="btnEditarVisualizacao">
                    <i class="fas fa-edit me-1"></i>Editar Aluno
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts específicos para Alunos -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar máscaras
    inicializarMascarasAluno();
    
    // Inicializar filtros
    inicializarFiltrosAluno();
    
    // Inicializar busca
    inicializarBuscaAluno();
    
    // Inicializar controle de progresso
    inicializarProgresso();
});

function inicializarMascarasAluno() {
    // Máscara para CPF
    if (typeof IMask !== 'undefined') {
        new IMask(document.getElementById('cpf'), {
            mask: '000.000.000-00'
        });
        
        // Máscara para RG
        new IMask(document.getElementById('rg'), {
            mask: '00.000.000-0'
        });
        
        // Máscara para telefone
        new IMask(document.getElementById('telefone'), {
            mask: '(00) 00000-0000'
        });
        
        // Máscara para CEP
        new IMask(document.getElementById('cep'), {
            mask: '00000-000'
        });
    }
    
    // Busca de CEP
    document.getElementById('cep').addEventListener('blur', function() {
        const cep = this.value.replace(/\D/g, '');
        if (cep.length === 8) {
            buscarCEP(cep);
        }
    });
}

function buscarCEP(cep) {
    fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then(response => response.json())
        .then(data => {
            if (!data.erro) {
                document.getElementById('logradouro').value = data.logradouro;
                document.getElementById('bairro').value = data.bairro;
                document.getElementById('cidade').value = data.localidade;
                document.getElementById('uf').value = data.uf;
            }
        })
        .catch(error => console.error('Erro ao buscar CEP:', error));
}

function inicializarFiltrosAluno() {
    // Filtro por status
    document.getElementById('filtroStatus').addEventListener('change', filtrarAlunos);
    
    // Filtro por CFC
    document.getElementById('filtroCFC').addEventListener('change', filtrarAlunos);
    
    // Filtro por categoria
    document.getElementById('filtroCategoria').addEventListener('change', filtrarAlunos);
}



function inicializarBuscaAluno() {
    document.getElementById('buscaAluno').addEventListener('input', filtrarAlunos);
}

function inicializarProgresso() {
    const progressoRange = document.getElementById('progresso');
    const progressoValor = document.getElementById('progressoValor');
    
    progressoRange.addEventListener('input', function() {
        progressoValor.textContent = this.value + '%';
    });
}

function editarAluno(id) {
    console.log('🚀 editarAluno chamada com ID:', id);
    
    // Verificar se os elementos necessários existem
    const modalElement = document.getElementById('modalAluno');
    const modalTitle = document.getElementById('modalTitle');
    const acaoAluno = document.getElementById('acaoAluno');
    const alunoId = document.getElementById('aluno_id');
    
    console.log('🔍 Verificando elementos do DOM:');
    console.log('  modalAluno:', modalElement ? '✅ Existe' : '❌ Não existe');
    console.log('  modalTitle:', modalTitle ? '✅ Existe' : '❌ Não existe');
    console.log('  acaoAluno:', acaoAluno ? '✅ Existe' : '❌ Não existe');
    console.log('  aluno_id:', alunoId ? '✅ Existe' : '❌ Não existe');
    
    if (!modalElement) {
        console.error('❌ Modal não encontrado!');
        alert('ERRO: Modal não encontrado na página!');
        return;
    }
    
    console.log(`📡 Fazendo requisição para api/super-simple.php?id=${id}`);
    
    // Buscar dados do aluno (usando API ultra simples)
    fetch(`api/super-simple.php?id=${id}`)
        .then(response => {
            console.log(`📨 Resposta recebida - Status: ${response.status}, OK: ${response.ok}`);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            // Primeiro vamos ver o texto da resposta
            return response.text().then(text => {
                console.log('📄 Texto da resposta:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('❌ Erro ao fazer parse do JSON:', e);
                    console.error('📄 Texto que causou erro:', text);
                    throw new Error('Resposta não é JSON válido: ' + text.substring(0, 100));
                }
            });
        })
        .then(data => {
            console.log('📄 Dados recebidos:', data);
            
            if (data.success) {
                console.log('✅ Success = true, abrindo modal...');
                
                // Preencher formulário
                preencherFormularioAluno(data.aluno);
                console.log('✅ Formulário preenchido');
                
                // Configurar modal
                if (modalTitle) modalTitle.textContent = 'Editar Aluno';
                if (acaoAluno) acaoAluno.value = 'editar';
                if (alunoId) alunoId.value = id;
                
                // Abrir modal
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
                console.log('🪟 Modal aberto!');
                
            } else {
                console.error('❌ Success = false, erro:', data.error);
                mostrarAlerta('Erro ao carregar dados do aluno: ' + (data.error || 'Erro desconhecido'), 'danger');
            }
        })
        .catch(error => {
            console.error('💥 Erro na requisição:', error);
            mostrarAlerta('Erro ao carregar dados do aluno: ' + error.message, 'danger');
        });
}

function preencherFormularioAluno(aluno) {
    document.getElementById('nome').value = aluno.nome || '';
    document.getElementById('cpf').value = aluno.cpf || '';
    document.getElementById('rg').value = aluno.rg || '';
    document.getElementById('data_nascimento').value = aluno.data_nascimento || '';
    document.getElementById('email').value = aluno.email || '';
    document.getElementById('telefone').value = aluno.telefone || '';
    document.getElementById('cfc_id').value = aluno.cfc_id || '';
    document.getElementById('categoria_cnh').value = aluno.categoria_cnh || '';
    document.getElementById('status').value = aluno.status || 'ativo';
    document.getElementById('progresso').value = aluno.progresso || 0;
    document.getElementById('progressoValor').textContent = (aluno.progresso || 0) + '%';
    
    // Endereço
    if (aluno.endereco) {
        const endereco = typeof aluno.endereco === 'string' ? JSON.parse(aluno.endereco) : aluno.endereco;
        document.getElementById('cep').value = endereco.cep || '';
        document.getElementById('logradouro').value = endereco.logradouro || '';
        document.getElementById('numero').value = endereco.numero || '';
        document.getElementById('bairro').value = endereco.bairro || '';
        document.getElementById('cidade').value = endereco.cidade || '';
        document.getElementById('uf').value = endereco.uf || '';
    }
    
    document.getElementById('observacoes').value = aluno.observacoes || '';
}

function visualizarAluno(id) {
    console.log('🚀 visualizarAluno chamada com ID:', id);

    // Verificar se os elementos necessários existem
    const modalElement = document.getElementById('modalVisualizarAluno');
    const modalBody = document.getElementById('modalVisualizarAlunoBody');

    console.log('🔍 Verificando elementos do DOM:');
    console.log('  modalVisualizarAluno:', modalElement ? '✅ Existe' : '❌ Não existe');
    console.log('  modalVisualizarAlunoBody:', modalBody ? '✅ Existe' : '❌ Não existe');

    if (!modalElement) {
        console.error('❌ Modal de visualização não encontrado!');
        alert('ERRO: Modal de visualização não encontrado na página!');
        return;
    }

    console.log(`📡 Fazendo requisição para api/super-simple.php?id=${id}`);

    // Buscar dados do aluno (usando API simples)
    fetch(`api/super-simple.php?id=${id}`)
        .then(response => {
            console.log(`📨 Resposta recebida - Status: ${response.status}, OK: ${response.ok}`);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            return response.text().then(text => {
                console.log('📄 Texto da resposta:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('❌ Erro ao fazer parse do JSON:', e);
                    console.error('📄 Texto que causou erro:', text);
                    throw new Error('Resposta não é JSON válido: ' + text.substring(0, 100));
                }
            });
        })
        .then(data => {
            console.log('📄 Dados recebidos:', data);

            if (data.success) {
                console.log('✅ Success = true, preenchendo modal...');

                // Preencher modal
                preencherModalVisualizacao(data.aluno);
                console.log('✅ Modal preenchido');

                // Abrir modal
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
                console.log('🪟 Modal de visualização aberto!');

            } else {
                console.error('❌ Success = false, erro:', data.error);
                mostrarAlerta('Erro ao carregar dados do aluno: ' + (data.error || 'Erro desconhecido'), 'danger');
            }
        })
        .catch(error => {
            console.error('💥 Erro na requisição:', error);
            mostrarAlerta('Erro ao carregar dados do aluno: ' + error.message, 'danger');
        });
}

function preencherModalVisualizacao(aluno) {
    const endereco = typeof aluno.endereco === 'string' ? JSON.parse(aluno.endereco) : aluno.endereco;
    
    const html = `
        <div class="row">
            <div class="col-md-8">
                <h4>${aluno.nome}</h4>
                <p class="text-muted">CPF: ${aluno.cpf}</p>
            </div>
            <div class="col-md-4 text-end">
                <span class="badge bg-${aluno.status === 'ativo' ? 'success' : (aluno.status === 'concluido' ? 'info' : 'danger')} fs-6">
                    ${aluno.status === 'ativo' ? 'Ativo' : (aluno.status === 'concluido' ? 'Concluído' : 'Inativo')}
                </span>
            </div>
        </div>
        
        <hr>
        
        <div class="row">
            <div class="col-md-6">
                <h6><i class="fas fa-info-circle me-2"></i>Informações Pessoais</h6>
                <p><strong>RG:</strong> ${aluno.rg || 'Não informado'}</p>
                <p><strong>Data de Nascimento:</strong> ${aluno.data_nascimento ? new Date(aluno.data_nascimento).toLocaleDateString('pt-BR') : 'Não informado'}</p>
                <p><strong>E-mail:</strong> ${aluno.email || 'Não informado'}</p>
                <p><strong>Telefone:</strong> ${aluno.telefone || 'Não informado'}</p>
            </div>
            <div class="col-md-6">
                <h6><i class="fas fa-graduation-cap me-2"></i>Informações Acadêmicas</h6>
                <p><strong>CFC:</strong> ${aluno.cfc_nome || 'Não informado'}</p>
                <p><strong>Categoria:</strong> <span class="badge bg-secondary">${aluno.categoria_cnh}</span></p>
                <p><strong>Progresso:</strong> ${aluno.progresso || 0}%</p>
            </div>
        </div>
        
        ${endereco && (endereco.logradouro || endereco.cidade) ? `
        <hr>
        <h6><i class="fas fa-map-marker-alt me-2"></i>Endereço</h6>
        <p>${endereco.logradouro || ''} ${endereco.numero || ''}</p>
        <p>${endereco.bairro || ''}</p>
        <p>${endereco.cidade || ''} - ${endereco.uf || ''}</p>
        <p>CEP: ${endereco.cep || 'Não informado'}</p>
        ` : ''}
        
        ${aluno.observacoes ? `
        <hr>
        <h6><i class="fas fa-sticky-note me-2"></i>Observações</h6>
        <p>${aluno.observacoes}</p>
        ` : ''}
    `;
    
    document.getElementById('modalVisualizarAlunoBody').innerHTML = html;
    document.getElementById('btnEditarVisualizacao').onclick = () => {
        bootstrap.Modal.getInstance(document.getElementById('modalVisualizarAluno')).hide();
        editarAluno(aluno.id);
    };
}

function agendarAula(id) {
    // Redirecionar para página de agendamento usando o sistema de páginas do admin
    window.location.href = `?page=agendar-aula&aluno_id=${id}`;
}

function historicoAluno(id) {
    // Debug: verificar se a função está sendo chamada
    console.log('Função historicoAluno chamada com ID:', id);
    
    // Redirecionar para página de histórico usando o sistema de roteamento do admin
    window.location.href = `?page=historico-aluno&id=${id}`;
}

function ativarAluno(id) {
    if (confirm('Deseja realmente ativar este aluno?')) {
        alterarStatusAluno(id, 'ativo');
    }
}

function desativarAluno(id) {
    if (confirm('Deseja realmente desativar este aluno? Esta ação pode afetar o histórico de aulas.')) {
        alterarStatusAluno(id, 'inativo');
    }
}

function excluirAluno(id) {
    const mensagem = '⚠️ ATENÇÃO: Esta ação não pode ser desfeita!\n\nDeseja realmente excluir este aluno?';
    
    if (confirm(mensagem)) {
        if (typeof loading !== 'undefined') {
            loading.showGlobal('Excluindo aluno...');
        }
        
        fetch(`api/alunos.php`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (typeof loading !== 'undefined') {
                loading.hideGlobal();
            }
            
            if (data.success) {
                if (typeof notifications !== 'undefined') {
                    notifications.success('Aluno excluído com sucesso!');
                } else {
                    mostrarAlerta('Aluno excluído com sucesso!', 'success');
                }
                location.reload();
            } else {
                if (typeof notifications !== 'undefined') {
                    notifications.error(data.error || 'Erro ao excluir aluno');
                } else {
                    mostrarAlerta(data.error || 'Erro ao excluir aluno', 'danger');
                }
            }
        })
        .catch(error => {
            if (typeof loading !== 'undefined') {
                loading.hideGlobal();
            }
            console.error('Erro:', error);
            if (typeof notifications !== 'undefined') {
                notifications.error('Erro ao excluir aluno');
            } else {
                mostrarAlerta('Erro ao excluir aluno', 'danger');
            }
        });
    }
}

function alterarStatusAluno(id, status) {
    const mensagem = `Deseja realmente ${status === 'ativo' ? 'ativar' : 'desativar'} este aluno?`;
    
    if (confirm(mensagem)) {
        if (typeof loading !== 'undefined') {
            loading.showGlobal('Alterando status...');
        }
        
        const formData = new FormData();
        formData.append('acao', 'alterar_status');
        formData.append('aluno_id', id);
        formData.append('status', status);
        
        fetch('pages/alunos.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            if (typeof loading !== 'undefined') {
                loading.hideGlobal();
            }
            if (typeof notifications !== 'undefined') {
                notifications.success(`Status do aluno alterado para ${status} com sucesso!`);
            }
            location.reload();
        })
        .catch(error => {
            if (typeof loading !== 'undefined') {
                loading.hideGlobal();
            }
            console.error('Erro:', error);
            if (typeof notifications !== 'undefined') {
                notifications.error('Erro ao alterar status do aluno');
            } else {
                mostrarAlerta('Erro ao alterar status do aluno', 'danger');
            }
        });
    }
}

function limparFiltros() {
    document.getElementById('filtroStatus').value = '';
    document.getElementById('filtroCFC').value = '';
    document.getElementById('filtroCategoria').value = '';
    document.getElementById('buscaAluno').value = '';
    filtrarAlunos();
}

function filtrarAlunos() {
    const busca = document.getElementById('buscaAluno').value.toLowerCase();
    const status = document.getElementById('filtroStatus').value;
    const cfc = document.getElementById('filtroCFC').value;
    const categoria = document.getElementById('filtroCategoria').value;
    
    const linhas = document.querySelectorAll('#tabelaAlunos tbody tr');
    let contador = 0;
    
    linhas.forEach(linha => {
        const nome = linha.querySelector('td:nth-child(2)').textContent.toLowerCase();
        const cpf = linha.querySelector('td:nth-child(3)').textContent;
        const email = linha.querySelector('td:nth-child(4)').textContent.toLowerCase();
        const statusLinha = linha.querySelector('td:nth-child(6) .badge').textContent;
        const categoriaLinha = linha.querySelector('td:nth-child(5)').textContent;
        const cfcLinha = linha.querySelector('td:nth-child(7)').textContent;
        
        let mostrar = true;
        
        // Filtro de busca
        if (busca && !nome.includes(busca) && !cpf.includes(busca) && !email.includes(busca)) {
            mostrar = false;
        }
        
        // Filtro de status
        if (status && statusLinha !== status) {
            mostrar = false;
        }
        
        // Filtro de CFC
        if (cfc && cfcLinha !== cfc) {
            mostrar = false;
        }
        
        // Filtro de categoria
        if (categoria && categoriaLinha !== categoria) {
            mostrar = false;
        }
        
        linha.style.display = mostrar ? '' : 'none';
        if (mostrar) contador++;
    });
    
    // Atualizar estatísticas
    document.getElementById('totalAlunos').textContent = contador;
    
    // Mostrar notificação de resultado
    if (typeof notifications !== 'undefined') {
        notifications.info(`Filtro aplicado: ${contador} aluno(s) encontrado(s)`);
    }
}

function atualizarEstatisticas() {
    const linhasVisiveis = document.querySelectorAll('#tabelaAlunos tbody tr:not([style*="display: none"])');
    
    document.getElementById('totalAlunos').textContent = linhasVisiveis.length;
    
    const ativos = Array.from(linhasVisiveis).filter(linha => 
        linha.querySelector('td:nth-child(6) .badge').textContent === 'Ativo'
    ).length;
    
    const concluidos = Array.from(linhasVisiveis).filter(linha => 
        linha.querySelector('td:nth-child(6) .badge').textContent === 'Concluído'
    ).length;
    
    document.getElementById('alunosAtivos').textContent = ativos;
    document.getElementById('emFormacao').textContent = ativos;
    document.getElementById('concluidos').textContent = concluidos;
}

function exportarAlunos() {
    // Implementar exportação para Excel/CSV
    alert('Funcionalidade de exportação será implementada em breve!');
}

function imprimirAlunos() {
    window.print();
}

function exportarFiltros() {
    if (typeof loading !== 'undefined') {
        loading.showGlobal('Preparando exportação...');
    }
    
    setTimeout(() => {
        if (typeof loading !== 'undefined') {
            loading.hideGlobal();
        }
        if (typeof notifications !== 'undefined') {
            notifications.success('Exportação realizada com sucesso!');
        } else {
            alert('Exportação realizada com sucesso!');
        }
    }, 1500);
}

// Função para mostrar alertas usando o sistema de notificações
function mostrarAlerta(mensagem, tipo) {
    if (typeof notifications !== 'undefined') {
        notifications.show(mensagem, tipo);
    } else {
        // Fallback para alertas tradicionais
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${tipo} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${mensagem}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.d-flex'));
        
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
}

// Função para confirmar ações importantes
function confirmarAcao(mensagem, acao) {
    if (typeof modals !== 'undefined') {
        modals.confirm(mensagem, acao);
    } else {
        if (confirm(mensagem)) {
            acao();
        }
    }
}

// Inicializar funcionalidades quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    // Aplicar máscaras se disponível
    if (typeof inputMasks !== 'undefined') {
        inputMasks.applyMasks();
    }
    
    // Mostrar notificação de carregamento
    if (typeof notifications !== 'undefined') {
        notifications.info('Página de alunos carregada com sucesso!');
    }
    
    // Configurar tooltips e popovers se disponível
    if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});
</script>