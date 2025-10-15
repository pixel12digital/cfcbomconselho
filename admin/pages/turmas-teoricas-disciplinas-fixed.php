<?php
/**
 * Página Principal de Gestão de Turmas Teóricas - Versão com Disciplinas Dinâmicas
 * Sistema com wizard em 4 etapas
 * 
 * @author Sistema CFC Bom Conselho
 * @version 1.0
 * @since 2024
 */

// Verificar permissões
if (!$isAdmin && !$isInstrutor) {
    echo '<div class="alert alert-danger">Você não tem permissão para acessar esta página.</div>';
    return;
}

// Incluir dependências específicas
require_once __DIR__ . '/../includes/TurmaTeoricaManager.php';

// Instanciar o gerenciador
$turmaManager = new TurmaTeoricaManager();

// Obter dados para os dropdowns
$cursosDisponiveis = $turmaManager->obterCursosDisponiveis();
$salasDisponiveis = $turmaManager->obterSalasDisponiveis($user['cfc_id'] ?? 1);

// Buscar instrutores
try {
    $instrutores = $db->fetchAll("
        SELECT i.id, u.nome, i.categoria_habilitacao 
        FROM instrutores i 
        LEFT JOIN usuarios u ON i.usuario_id = u.id 
        WHERE i.ativo = 1 AND i.cfc_id = ? 
        ORDER BY u.nome
    ", [$user['cfc_id'] ?? 1]);
} catch (Exception $e) {
    $instrutores = [];
}

// Processar ações
$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';
$step = $_GET['step'] ?? $_POST['step'] ?? '1';
$turmaId = $_GET['turma_id'] ?? $_POST['turma_id'] ?? null;

// Verificar se é requisição AJAX
$isAjax = isset($_GET['ajax']) || isset($_POST['acao']) && strpos($_POST['acao'], 'ajax') !== false;

// Processar salvamento automático (rascunho)
if ($acao === 'salvar_rascunho' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dadosTurma = [
        'nome' => $_POST['nome'] ?? '',
        'sala_id' => $_POST['sala_id'] ?? '',
        'curso_tipo' => $_POST['curso_tipo'] ?? '',
        'modalidade' => $_POST['modalidade'] ?? 'presencial',
        'data_inicio' => $_POST['data_inicio'] ?? '',
        'data_fim' => $_POST['data_fim'] ?? '',
        'observacoes' => $_POST['observacoes'] ?? '',
        'max_alunos' => $_POST['max_alunos'] ?? 30,
        'cfc_id' => $isAdmin ? ($user['cfc_id'] ?? 1) : $user['cfc_id'],
        'criado_por' => $user['id']
    ];
    
    $resultado = $turmaManager->salvarRascunho($dadosTurma, 1);
    
    if ($resultado['sucesso']) {
        // Retornar JSON para AJAX
        header('Content-Type: application/json');
        echo json_encode($resultado);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode($resultado);
        exit;
    }
}

// Processar criação da turma básica (Step 1)
if ($acao === 'criar_basica' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dadosTurma = [
        'nome' => $_POST['nome'] ?? '',
        'sala_id' => $_POST['sala_id'] ?? '',
        'curso_tipo' => $_POST['curso_tipo'] ?? '',
        'modalidade' => $_POST['modalidade'] ?? 'presencial',
        'data_inicio' => $_POST['data_inicio'] ?? '',
        'data_fim' => $_POST['data_fim'] ?? '',
        'observacoes' => $_POST['observacoes'] ?? '',
        'max_alunos' => $_POST['max_alunos'] ?? 30,
        'cfc_id' => $isAdmin ? ($user['cfc_id'] ?? 1) : $user['cfc_id'],
        'criado_por' => $user['id']
    ];
    
    // Primeiro salvar como rascunho
    $rascunho = $turmaManager->salvarRascunho($dadosTurma, 1);
    
    if ($rascunho['sucesso']) {
        // Depois finalizar a turma
        $resultado = $turmaManager->finalizarTurma($rascunho['turma_id'], $dadosTurma);
        
        if ($resultado['sucesso']) {
            // Redirecionar para a próxima etapa
            header('Location: ?page=turmas-teoricas&acao=nova&step=2&turma_id=' . $rascunho['turma_id']);
            exit;
        } else {
            $erro = $resultado['mensagem'];
        }
    } else {
        $erro = $rascunho['mensagem'];
    }
}

// Buscar turma existente se estiver editando
$turmaAtual = null;
$progressoAtual = null;
if ($turmaId) {
    $resultadoTurma = $turmaManager->obterTurma($turmaId);
    if ($resultadoTurma['sucesso']) {
        $turmaAtual = $resultadoTurma['dados'];
        $progressoAtual = $turmaManager->obterProgressoDisciplinas($turmaId);
    } else {
        $erro = $resultadoTurma['mensagem'];
    }
}

// Verificar se há mensagem de sucesso
if (isset($_GET['sucesso'])) {
    if ($_GET['sucesso'] == '1') {
        $sucesso = 'Turma criada com sucesso! Agora agende as aulas das disciplinas.';
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-chalkboard-teacher me-2 text-primary"></i>
                        Turmas Teóricas
                    </h1>
                    <p class="text-muted mb-0">Gerencie as turmas teóricas do CFC</p>
                </div>
                <a href="?page=turmas-teoricas&acao=nova" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Nova Turma
                </a>
            </div>

            <!-- Alertas -->
            <?php if (isset($sucesso)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $sucesso; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($erro)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $erro; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Wizard de Criação de Turma -->
            <?php if ($acao === 'nova'): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-plus me-2"></i>Nova Turma Teórica
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Progress Steps -->
                        <div class="progress-steps mb-4">
                            <div class="step <?php echo $step >= 1 ? 'active' : ''; ?>">
                                <div class="step-number">1</div>
                                <div class="step-label">Dados Básicos</div>
                            </div>
                            <div class="step <?php echo $step >= 2 ? 'active' : ''; ?>">
                                <div class="step-number">2</div>
                                <div class="step-label">Disciplinas</div>
                            </div>
                            <div class="step <?php echo $step >= 3 ? 'active' : ''; ?>">
                                <div class="step-number">3</div>
                                <div class="step-label">Agendamento</div>
                            </div>
                            <div class="step <?php echo $step >= 4 ? 'active' : ''; ?>">
                                <div class="step-number">4</div>
                                <div class="step-label">Finalização</div>
                            </div>
                        </div>

                        <!-- Step 1: Dados Básicos -->
                        <?php if ($step == '1'): ?>
                            <form method="POST" action="?page=turmas-teoricas&acao=criar_basica">
                                <div class="form-section">
                                    <h4>📋 Informações Básicas</h4>
                                    
                                    <div class="form-group">
                                        <label for="nome">Nome da Turma *</label>
                                        <input type="text" 
                                               id="nome" 
                                               name="nome" 
                                               class="form-control" 
                                               placeholder="Ex: Turma A - Primeira Habilitação" 
                                               required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="sala_id">Sala *</label>
                                        <select id="sala_id" name="sala_id" class="form-select" required>
                                            <option value="">Selecione uma sala...</option>
                                            <?php foreach ($salasDisponiveis as $sala): ?>
                                                <option value="<?php echo $sala['id']; ?>">
                                                    <?php echo htmlspecialchars($sala['nome']); ?> 
                                                    (<?php echo $sala['capacidade']; ?> alunos)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            <?php echo count($salasDisponiveis); ?> sala(s) cadastrada(s) - 
                                            <a href="#" onclick="abrirModalSalasInterno()" class="text-primary">Clique aqui para gerenciar</a>
                                        </small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="curso_tipo">Tipo de Curso *</label>
                                        <select id="curso_tipo" name="curso_tipo" class="form-select" required>
                                            <option value="">Selecione o tipo de curso...</option>
                                            <?php foreach ($cursosDisponiveis as $curso): ?>
                                                <option value="<?php echo $curso['tipo']; ?>">
                                                    <?php echo htmlspecialchars($curso['nome']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            <?php echo count($cursosDisponiveis); ?> tipo(s) de curso cadastrado(s) - 
                                            <a href="#" onclick="abrirModalTiposCursoInterno()" class="text-primary">Clique aqui para gerenciar</a>
                                        </small>
                                    </div>
                                    
                                    <!-- Seção de Disciplinas -->
                                    <div class="form-group">
                                        <label>
                                            <i class="fas fa-book me-1"></i>Disciplinas do Curso
                                            <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="abrirModalDisciplinasInterno()" title="Gerenciar Disciplinas">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                        </label>
                                        <div class="mb-2">
                                            <div id="disciplinas-container">
                                                <!-- Disciplinas selecionadas serão carregadas aqui -->
                                            </div>
                                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="adicionarDisciplina()" style="font-size: 0.8rem;">
                                                <i class="fas fa-plus me-1"></i>Adicionar Disciplina
                                            </button>
                                        </div>
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            <span id="contador-disciplinas">0</span> disciplina(s) cadastrada(s) - 
                                            <a href="#" onclick="abrirModalDisciplinasInterno()" class="text-primary">Clique aqui para gerenciar</a>
                                        </small>
                                        <div class="mt-2">
                                            <small class="text-primary">
                                                <i class="fas fa-clock me-1"></i>
                                                Total de horas: <strong id="total-horas-disciplinas">0</strong>h
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Modalidade *</label>
                                        <div class="radio-group">
                                            <div class="radio-option">
                                                <input type="radio" id="presencial" name="modalidade" value="presencial" checked>
                                                <label for="presencial">🏢 Presencial</label>
                                            </div>
                                            <div class="radio-option">
                                                <input type="radio" id="online" name="modalidade" value="online">
                                                <label for="online">💻 Online</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-section">
                                    <h4>📅 Período da Turma</h4>
                                    
                                    <div style="display: flex; gap: 20px;">
                                        <div class="form-group" style="flex: 1;">
                                            <label for="data_inicio">Data de Início *</label>
                                            <input type="date" 
                                                   id="data_inicio" 
                                                   name="data_inicio" 
                                                   class="form-control" 
                                                   min="<?= date('Y-m-d') ?>"
                                                   required>
                                        </div>
                                        
                                        <div class="form-group" style="flex: 1;">
                                            <label for="data_fim">Data de Término *</label>
                                            <input type="date" 
                                                   id="data_fim" 
                                                   name="data_fim" 
                                                   class="form-control"
                                                   min="<?= date('Y-m-d') ?>"
                                                   required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-section">
                                    <h4>⚙️ Configurações Adicionais</h4>
                                    
                                    <div class="form-group">
                                        <label for="max_alunos">Máximo de Alunos</label>
                                        <input type="number" 
                                               id="max_alunos" 
                                               name="max_alunos" 
                                               class="form-control" 
                                               value="30" 
                                               min="5" 
                                               max="50">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="observacoes">Observações</label>
                                        <textarea id="observacoes" 
                                                  name="observacoes" 
                                                  class="form-control" 
                                                  rows="3" 
                                                  placeholder="Observações adicionais sobre a turma..."></textarea>
                                    </div>
                                </div>
                                
                                <div style="text-align: right; margin-top: 30px;">
                                    <a href="?page=turmas-teoricas" class="btn-secondary">
                                        ← Cancelar
                                    </a>
                                    <button type="submit" class="btn-primary">
                                        Próximo: Disciplinas →
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para Gerenciamento de Disciplinas -->
<div class="modal fade" id="modalDisciplinasInterno" tabindex="-1" aria-labelledby="modalDisciplinasInternoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDisciplinasInternoLabel">
                    <i class="fas fa-book me-2"></i>Gerenciar Disciplinas
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="conteudo-disciplinas">
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                        <p>Carregando disciplinas...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.progress-steps {
    display: flex;
    justify-content: space-between;
    margin-bottom: 2rem;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    position: relative;
}

.step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 20px;
    left: 60%;
    right: -40%;
    height: 2px;
    background-color: #e9ecef;
    z-index: 1;
}

.step.active:not(:last-child)::after {
    background-color: #007bff;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-bottom: 0.5rem;
    position: relative;
    z-index: 2;
}

.step.active .step-number {
    background-color: #007bff;
    color: white;
}

.step-label {
    font-size: 0.875rem;
    color: #6c757d;
    text-align: center;
}

.step.active .step-label {
    color: #007bff;
    font-weight: 600;
}

.form-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    background-color: #f8f9fa;
}

.form-section h4 {
    margin-bottom: 1rem;
    color: #495057;
    font-size: 1.1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    display: block;
}

.radio-group {
    display: flex;
    gap: 1rem;
}

.radio-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.radio-option input[type="radio"] {
    margin: 0;
}

.radio-option label {
    margin: 0;
    font-weight: normal;
    cursor: pointer;
}

.btn-primary, .btn-secondary {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 0.375rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background-color: #007bff;
    color: white;
}

.btn-primary:hover {
    background-color: #0056b3;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
    margin-right: 1rem;
}

.btn-secondary:hover {
    background-color: #545b62;
}

.disciplina-item {
    background-color: white;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.disciplina-item .row {
    align-items: center;
}

.disciplina-info {
    font-size: 0.875rem;
    color: #6c757d;
}

@media (max-width: 768px) {
    .progress-steps {
        flex-direction: column;
        gap: 1rem;
    }
    
    .step:not(:last-child)::after {
        display: none;
    }
    
    .form-section {
        padding: 1rem;
    }
    
    .radio-group {
        flex-direction: column;
    }
}
</style>

<script>
// Sistema de Disciplinas Dinâmicas (baseado na lógica do cadastro de alunos)
console.log('🚀 Sistema de disciplinas dinâmicas carregado! v3.0 - ' + new Date().toISOString());
let contadorDisciplinas = 0;
let disciplinasDisponiveis = [];

// Carregar disciplinas do banco de dados
function carregarDisciplinasDisponiveis() {
    fetch('admin/api/disciplinas.php?action=listar')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                disciplinasDisponiveis = data.disciplinas;
                atualizarContadorDisciplinas();
                console.log('✅ Disciplinas carregadas:', disciplinasDisponiveis.length);
            } else {
                console.error('❌ Erro ao carregar disciplinas:', data.error);
            }
        })
        .catch(error => {
            console.error('❌ Erro na requisição:', error);
        });
}

// Atualizar contador de disciplinas
function atualizarContadorDisciplinas() {
    const contador = document.getElementById('contador-disciplinas');
    if (contador) {
        contador.textContent = disciplinasDisponiveis.length;
    }
}

function adicionarDisciplina() {
    console.log('🎯 Função adicionarDisciplina chamada!');
    
    contadorDisciplinas++;
    const container = document.getElementById('disciplinas-container');
    
    if (!container) {
        console.error('❌ Container de disciplinas não encontrado!');
        alert('ERRO: Container de disciplinas não encontrado!');
        return;
    }
    
    const disciplinaHtml = `
        <div class="disciplina-item border rounded p-3 mb-3" data-disciplina-id="${contadorDisciplinas}">
            <div class="row align-items-center">
                <div class="col-lg-7 col-md-6">
                    <select class="form-select" name="disciplina_${contadorDisciplinas}" onchange="atualizarDisciplina(${contadorDisciplinas})">
                        <option value="">Selecione a disciplina...</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-3">
                    <div class="input-group">
                        <input type="number" class="form-control disciplina-horas" 
                               name="disciplina_horas_${contadorDisciplinas}" 
                               placeholder="Horas" 
                               min="1" 
                               max="50"
                               onchange="atualizarPreview()">
                        <span class="input-group-text">h</span>
                    </div>
                </div>
                <div class="col-lg-2 col-md-2">
                    <small class="text-muted disciplina-info">
                        <span class="carga-horaria-padrao"></span>h (padrão)
                    </small>
                </div>
                <div class="col-lg-1 col-md-1">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removerDisciplina(${contadorDisciplinas})" title="Remover disciplina">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', disciplinaHtml);
    carregarDisciplinas(contadorDisciplinas);
}

function carregarDisciplinas(disciplinaId) {
    const disciplinaSelect = document.querySelector(`select[name="disciplina_${disciplinaId}"]`);
    
    if (!disciplinaSelect) {
        console.warn(`⚠️ Select de disciplina não encontrado para ID ${disciplinaId}`);
        return;
    }
    
    // Limpar opções anteriores
    disciplinaSelect.innerHTML = '<option value="">Selecione a disciplina...</option>';
    
    // Adicionar opções disponíveis do banco de dados
    disciplinasDisponiveis.forEach(disciplina => {
        const option = document.createElement('option');
        option.value = disciplina.id;
        option.textContent = disciplina.nome;
        option.dataset.carga_horaria = disciplina.carga_horaria;
        option.dataset.descricao = disciplina.descricao || '';
        disciplinaSelect.appendChild(option);
    });
    
    console.log(`✅ Disciplinas carregadas para disciplina ${disciplinaId}:`, disciplinasDisponiveis.length);
}

function atualizarDisciplina(disciplinaId) {
    const disciplinaSelect = document.querySelector(`select[name="disciplina_${disciplinaId}"]`);
    const infoElement = document.querySelector(`[data-disciplina-id="${disciplinaId}"] .disciplina-info`);
    const cargaHorariaElement = infoElement?.querySelector('.carga-horaria-padrao');
    const horasInput = document.querySelector(`input[name="disciplina_horas_${disciplinaId}"]`);
    
    if (!disciplinaSelect || !infoElement) return;
    
    const selectedOption = disciplinaSelect.options[disciplinaSelect.selectedIndex];
    
    if (selectedOption.value) {
        const cargaHoraria = selectedOption.dataset.carga_horaria;
        const descricao = selectedOption.dataset.descricao;
        
        cargaHorariaElement.textContent = cargaHoraria;
        infoElement.style.display = 'block';
        
        // Definir valor padrão no campo de horas
        if (horasInput) {
            horasInput.value = cargaHoraria;
        }
        
        console.log(`✅ Disciplina selecionada: ${selectedOption.textContent} (${cargaHoraria}h padrão)`);
    } else {
        infoElement.style.display = 'none';
        
        // Limpar campo de horas
        if (horasInput) {
            horasInput.value = '';
        }
    }
    
    atualizarPreview();
}

function removerDisciplina(disciplinaId) {
    const disciplinaItem = document.querySelector(`[data-disciplina-id="${disciplinaId}"]`);
    if (disciplinaItem) {
        disciplinaItem.remove();
        console.log(`🗑️ Disciplina ${disciplinaId} removida`);
        atualizarPreview();
    }
}

function atualizarPreview() {
    const disciplinas = document.querySelectorAll('#disciplinas-container .disciplina-item');
    let totalHoras = 0;
    let disciplinasComHoras = [];
    
    disciplinas.forEach(item => {
        const select = item.querySelector('select');
        const horasInput = item.querySelector('.disciplina-horas');
        
        if (select && select.value && horasInput && horasInput.value) {
            const selectedOption = select.options[select.selectedIndex];
            const horas = parseInt(horasInput.value) || 0;
            
            totalHoras += horas;
            disciplinasComHoras.push({
                nome: selectedOption.textContent,
                horas: horas
            });
        }
    });
    
    // Atualizar indicador de total de horas se existir
    const totalHorasElement = document.getElementById('total-horas-disciplinas');
    if (totalHorasElement) {
        totalHorasElement.textContent = totalHoras;
    }
    
    console.log(`📊 Total de horas calculado: ${totalHoras}h`, disciplinasComHoras);
}

// Abrir modal de disciplinas
function abrirModalDisciplinasInterno() {
    const modal = new bootstrap.Modal(document.getElementById('modalDisciplinasInterno'));
    
    // Carregar conteúdo do modal
    fetch('admin/pages/configuracoes-disciplinas.php')
        .then(response => response.text())
        .then(html => {
            document.getElementById('conteudo-disciplinas').innerHTML = html;
            modal.show();
        })
        .catch(error => {
            console.error('Erro ao carregar modal:', error);
            document.getElementById('conteudo-disciplinas').innerHTML = 
                '<div class="alert alert-danger">Erro ao carregar disciplinas</div>';
            modal.show();
        });
}

// Carregar disciplinas quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    carregarDisciplinasDisponiveis();
});
</script>
