<?php
/**
 * Página Principal de Gestão de Turmas Teóricas - VERSÃO CORRIGIDA
 * Sistema com wizard em 4 etapas
 * 
 * @author Sistema CFC Bom Conselho
 * @version 2.0
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
    $dadosRascunho = [
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
    
    $resultado = $turmaManager->salvarRascunho($dadosRascunho);
    
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($resultado);
        exit;
    }
}

// Processar criação de turma (Step 1)
if ($acao === 'criar_turma' && $_SERVER['REQUEST_METHOD'] === 'POST') {
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
    
    $resultado = $turmaManager->criarTurma($dadosTurma);
    
    if ($resultado['sucesso']) {
        // Usar JavaScript para redirecionamento ao invés de header
        $redirectUrl = '?page=turmas-teoricas&acao=agendar&step=2&turma_id=' . $resultado['turma_id'] . '&sucesso=1';
        echo "<script>window.location.href = '$redirectUrl';</script>";
        exit;
    } else {
        $erro = $resultado['mensagem'];
    }
}

// Processar agendamento de aula (Step 2)
if ($acao === 'agendar_aula' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dadosAula = [
        'turma_id' => $_POST['turma_id'] ?? '',
        'disciplina' => $_POST['disciplina'] ?? '',
        'instrutor_id' => $_POST['instrutor_id'] ?? '',
        'data_aula' => $_POST['data_aula'] ?? '',
        'hora_inicio' => $_POST['hora_inicio'] ?? '',
        'quantidade_aulas' => $_POST['quantidade_aulas'] ?? 1,
        'criado_por' => $user['id']
    ];
    
    $resultado = $turmaManager->agendarAula($dadosAula);
    
    if ($resultado['sucesso']) {
        $sucesso = $resultado['mensagem'];
        $progressoAtual = $resultado['progresso'] ?? [];
    } else {
        $erro = $resultado['mensagem'];
    }
}

// Obter dados da turma se estiver editando
$turmaAtual = null;
$progressoAtual = [];
$rascunhoCarregado = null;

if ($turmaId) {
    $resultadoTurma = $turmaManager->obterTurma($turmaId);
    if ($resultadoTurma['sucesso']) {
        $turmaAtual = $resultadoTurma['dados'];
        $progressoAtual = $turmaManager->obterProgressoDisciplinas($turmaId);
    } else {
        $erro = $resultadoTurma['mensagem'];
    }
} else {
    // Tentar carregar rascunho se não há turma específica
    $rascunho = $turmaManager->carregarRascunho(
        $isAdmin ? ($user['cfc_id'] ?? 1) : $user['cfc_id'],
        $user['id']
    );
    
    if ($rascunho['sucesso']) {
        $rascunhoCarregado = $rascunho['dados'];
        $turmaId = $rascunhoCarregado['id'];
        $turmaAtual = $rascunhoCarregado;
    }
}

// Verificar se há mensagem de sucesso
if (isset($_GET['sucesso'])) {
    if ($_GET['sucesso'] == '1') {
        $sucesso = 'Turma criada com sucesso! Agora agende as aulas das disciplinas.';
    }
}
?>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Meta tags para evitar cache -->
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

<style>
/* CSS para o sistema de turmas teóricas */
.turma-wizard {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.wizard-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    text-align: center;
}

.wizard-body {
    padding: 2rem;
}

.step-indicator {
    display: flex;
    justify-content: center;
    margin-bottom: 2rem;
}

.step {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 1rem;
    font-weight: bold;
    position: relative;
}

.step.active {
    background: #007bff;
    color: white;
}

.step.completed {
    background: #28a745;
    color: white;
}

.step.inactive {
    background: #e9ecef;
    color: #6c757d;
}

.step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 100%;
    width: 2rem;
    height: 2px;
    background: #dee2e6;
    transform: translateY(-50%);
}

.step.completed:not(:last-child)::after {
    background: #28a745;
}

.form-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border-left: 4px solid #007bff;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
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

.progresso-disciplinas {
    margin-top: 1rem;
}

.disciplina-item {
    background: white;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.disciplina-item.completa {
    border-left: 4px solid #28a745;
}

.disciplina-item.parcial {
    border-left: 4px solid #ffc107;
}

.disciplina-item.pendente {
    border-left: 4px solid #dc3545;
}

/* Melhorar tamanho dos campos no desktop */
@media (min-width: 992px) {
    .disciplina-item .form-select {
        font-size: 1rem;
        padding: 0.75rem 1rem;
    }
    
    .disciplina-item .form-control {
        font-size: 1rem;
        padding: 0.75rem 1rem;
    }
    
    .disciplina-item .input-group-text {
        font-size: 1rem;
        padding: 0.75rem 0.5rem;
    }
}
</style>

<div class="turma-wizard">
    <div class="wizard-header">
        <h2><i class="fas fa-graduation-cap me-2"></i>Sistema de Turmas Teóricas</h2>
        <p class="mb-0">Gerencie suas turmas teóricas de forma organizada e eficiente</p>
    </div>
    
    <div class="wizard-body">
        <!-- Indicador de Etapas -->
        <div class="step-indicator">
            <div class="step <?= $step == '1' ? 'active' : ($step > '1' ? 'completed' : 'inactive') ?>">1</div>
            <div class="step <?= $step == '2' ? 'active' : ($step > '2' ? 'completed' : 'inactive') ?>">2</div>
            <div class="step <?= $step == '3' ? 'active' : ($step > '3' ? 'completed' : 'inactive') ?>">3</div>
            <div class="step <?= $step == '4' ? 'active' : ($step > '4' ? 'completed' : 'inactive') ?>">4</div>
        </div>
        
        <!-- Mensagens -->
        <?php if (isset($sucesso)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($sucesso) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($erro) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($step === '1' || $acao === 'nova'): ?>
            <!-- STEP 1: CRIAÇÃO DA TURMA -->
            <form method="POST" action="?page=turmas-teoricas&acao=criar_turma&step=1">
                <input type="hidden" name="acao" value="criar_turma">
                <input type="hidden" name="step" value="1">
                
                <div class="form-section">
                    <h4>📝 Informações Básicas da Turma</h4>
                    
                    <div class="form-group">
                        <label for="nome">Nome da Turma *</label>
                        <input type="text" id="nome" name="nome" class="form-control" 
                               placeholder="Ex: Turma A - Manhã" 
                               value="<?= htmlspecialchars($turmaAtual['nome'] ?? $rascunhoCarregado['nome'] ?? '') ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="sala_id">Sala *</label>
                        <select id="sala_id" name="sala_id" class="form-control" required>
                            <option value="">Selecione uma sala...</option>
                            <?php foreach ($salasDisponiveis as $sala): ?>
                                <option value="<?= $sala['id'] ?>" 
                                        <?= ($turmaAtual['sala_id'] ?? $rascunhoCarregado['sala_id'] ?? '') == $sala['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sala['nome']) ?> (Capacidade: <?= $sala['capacidade'] ?> alunos)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">
                            <?php echo count($salasDisponiveis); ?> sala(s) cadastrada(s) - 
                            <a href="#" onclick="abrirModalSalasInterno()" class="text-primary">Clique aqui para gerenciar</a>
                        </small>
                    </div>
                </div>
                
                <div class="form-section">
                    <h4>🎓 Configuração do Curso</h4>
                    
                    <div class="form-group">
                        <label for="curso_tipo">Tipo de Curso *</label>
                        <select id="curso_tipo" name="curso_tipo" class="form-control" required>
                            <option value="">Selecione o tipo de curso...</option>
                            <?php foreach ($cursosDisponiveis as $curso): ?>
                                <option value="<?= $curso['tipo'] ?>" 
                                        <?= ($turmaAtual['curso_tipo'] ?? $rascunhoCarregado['curso_tipo'] ?? '') == $curso['tipo'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($curso['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">
                            <?php echo count($cursosDisponiveis); ?> tipo(s) de curso cadastrado(s) - 
                            <a href="#" onclick="abrirModalTiposCursoInterno()" class="text-primary">Clique aqui para gerenciar</a>
                        </small>
                    </div>
                    
                    <!-- Seção de Disciplinas -->
                    <div class="form-group">
                        <label>
                            <i class="fas fa-book me-1"></i>Disciplinas do Curso
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
                            <i class="fas fa-lightbulb me-1"></i>
                            Selecione as disciplinas que serão ministradas nesta turma
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
                                <input type="radio" id="presencial" name="modalidade" value="presencial" 
                                       <?= ($turmaAtual['modalidade'] ?? $rascunhoCarregado['modalidade'] ?? 'presencial') == 'presencial' ? 'checked' : '' ?>>
                                <label for="presencial">🏢 Presencial</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="online" name="modalidade" value="online"
                                       <?= ($turmaAtual['modalidade'] ?? $rascunhoCarregado['modalidade'] ?? '') == 'online' ? 'checked' : '' ?>>
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
                                   value="<?= $turmaAtual['data_inicio'] ?? $rascunhoCarregado['data_inicio'] ?? '' ?>"
                                   required>
                        </div>
                        
                        <div class="form-group" style="flex: 1;">
                            <label for="data_fim">Data de Fim *</label>
                            <input type="date" 
                                   id="data_fim" 
                                   name="data_fim" 
                                   class="form-control" 
                                   min="<?= date('Y-m-d') ?>"
                                   value="<?= $turmaAtual['data_fim'] ?? $rascunhoCarregado['data_fim'] ?? '' ?>"
                                   required>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h4>⚙️ Configurações Adicionais</h4>
                    
                    <div class="form-group">
                        <label for="max_alunos">Máximo de Alunos</label>
                        <input type="number" id="max_alunos" name="max_alunos" class="form-control" 
                               min="1" max="50" value="<?= $turmaAtual['max_alunos'] ?? $rascunhoCarregado['max_alunos'] ?? 30 ?>">
                        <small class="text-muted">Número máximo de alunos que podem ser matriculados nesta turma</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="observacoes">Observações</label>
                        <textarea id="observacoes" name="observacoes" class="form-control" rows="3" 
                                  placeholder="Observações adicionais sobre a turma..."><?= htmlspecialchars($turmaAtual['observacoes'] ?? $rascunhoCarregado['observacoes'] ?? '') ?></textarea>
                    </div>
                </div>
                
                <div class="text-end">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>Criar Turma e Continuar
                    </button>
                </div>
            </form>
            
        <?php elseif ($step === '2' || $acao === 'agendar'): ?>
            <!-- STEP 2: AGENDAMENTO DE AULAS -->
            <?php include __DIR__ . '/turmas-teoricas-step2.php'; ?>
            
        <?php elseif ($step === '3' || $acao === 'validar'): ?>
            <!-- STEP 3: VALIDAÇÃO DE CARGA HORÁRIA -->
            <?php include __DIR__ . '/turmas-teoricas-step3.php'; ?>
            
        <?php elseif ($step === '4' || $acao === 'alunos'): ?>
            <!-- STEP 4: INSERÇÃO DE ALUNOS -->
            <?php include __DIR__ . '/turmas-teoricas-step4.php'; ?>
            
        <?php endif; ?>
    </div>
</div>

<script>
// JavaScript para validações e UX
document.addEventListener('DOMContentLoaded', function() {
    // Validação de datas
    const dataInicio = document.getElementById('data_inicio');
    const dataFim = document.getElementById('data_fim');
    
    if (dataInicio && dataFim) {
        dataInicio.addEventListener('change', function() {
            dataFim.min = this.value;
            if (dataFim.value && dataFim.value < this.value) {
                dataFim.value = this.value;
            }
        });
    }
    
    // Preview da modalidade
    const radioPresencial = document.getElementById('presencial');
    const radioOnline = document.getElementById('online');
    
    if (radioPresencial && radioOnline) {
        function updateModalidadePreview() {
            const salaGroup = document.getElementById('sala_id').closest('.form-group');
            if (radioOnline.checked) {
                salaGroup.style.opacity = '0.5';
                salaGroup.querySelector('label').innerHTML = 'Sala * <small>(será usada como referência)</small>';
            } else {
                salaGroup.style.opacity = '1';
                salaGroup.querySelector('label').innerHTML = 'Sala *';
            }
        }
        
        radioPresencial.addEventListener('change', updateModalidadePreview);
        radioOnline.addEventListener('change', updateModalidadePreview);
    }
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Sistema de Disciplinas Dinâmicas (baseado na lógica do cadastro de alunos)
console.log('🚀 Sistema de disciplinas dinâmicas carregado! v2.0 - ' + new Date().toISOString());
let contadorDisciplinas = 0;

// Disciplinas disponíveis por tipo de curso
const disciplinasPorCurso = {
    'formacao_45h': [
        { value: 'legislacao_transito', text: '📋 Legislação de Trânsito', aulas: 18, cor: '#dc3545' },
        { value: 'direcao_defensiva', text: '🛡️ Direção Defensiva', aulas: 16, cor: '#28a745' },
        { value: 'primeiros_socorros', text: '🚑 Primeiros Socorros', aulas: 4, cor: '#ffc107' },
        { value: 'meio_ambiente_cidadania', text: '🌱 Meio Ambiente e Cidadania', aulas: 4, cor: '#17a2b8' },
        { value: 'mecanica_basica', text: '🔧 Mecânica Básica', aulas: 3, cor: '#6c757d' }
    ],
    'formacao_acc_20h': [
        { value: 'legislacao_transito', text: '📋 Legislação de Trânsito', aulas: 8, cor: '#dc3545' },
        { value: 'direcao_defensiva', text: '🛡️ Direção Defensiva', aulas: 8, cor: '#28a745' },
        { value: 'primeiros_socorros', text: '🚑 Primeiros Socorros', aulas: 2, cor: '#ffc107' },
        { value: 'meio_ambiente_cidadania', text: '🌱 Meio Ambiente e Cidadania', aulas: 2, cor: '#17a2b8' }
    ],
    'reciclagem_infrator': [
        { value: 'legislacao_transito', text: '📋 Legislação de Trânsito', aulas: 15, cor: '#dc3545' },
        { value: 'direcao_defensiva', text: '🛡️ Direção Defensiva', aulas: 15, cor: '#28a745' }
    ],
    'atualizacao': [
        { value: 'legislacao_transito', text: '📋 Legislação de Trânsito', aulas: 8, cor: '#dc3545' },
        { value: 'direcao_defensiva', text: '🛡️ Direção Defensiva', aulas: 7, cor: '#28a745' }
    ]
};

function adicionarDisciplina() {
    console.log('🎯 Função adicionarDisciplina chamada!');
    const cursoSelect = document.getElementById('curso_tipo');
    if (!cursoSelect || !cursoSelect.value) {
        alert('⚠️ Selecione primeiro o tipo de curso!');
        cursoSelect.focus();
        return;
    }
    
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
                               onchange="atualizarPreview()"
                               style="display: none;">
                        <span class="input-group-text" style="display: none;">h</span>
                    </div>
                </div>
                <div class="col-lg-2 col-md-2">
                    <small class="text-muted disciplina-info" style="display: none;">
                        <span class="aulas-obrigatorias"></span> aulas (padrão)
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
    const cursoSelect = document.getElementById('curso_tipo');
    const disciplinaSelect = document.querySelector(`select[name="disciplina_${disciplinaId}"]`);
    
    if (!cursoSelect || !disciplinaSelect) {
        console.warn(`⚠️ Elementos não encontrados para disciplina ${disciplinaId}`);
        return;
    }
    
    const cursoTipo = cursoSelect.value;
    const disciplinas = disciplinasPorCurso[cursoTipo] || [];
    
    // Limpar opções anteriores
    disciplinaSelect.innerHTML = '<option value="">Selecione a disciplina...</option>';
    
    // Adicionar opções disponíveis
    disciplinas.forEach(disciplina => {
        const option = document.createElement('option');
        option.value = disciplina.value;
        option.textContent = disciplina.text;
        option.dataset.aulas = disciplina.aulas;
        option.dataset.cor = disciplina.cor;
        disciplinaSelect.appendChild(option);
    });
    
    console.log(`✅ Disciplinas carregadas para curso ${cursoTipo}:`, disciplinas.length);
}

function atualizarDisciplina(disciplinaId) {
    const disciplinaSelect = document.querySelector(`select[name="disciplina_${disciplinaId}"]`);
    const infoElement = document.querySelector(`[data-disciplina-id="${disciplinaId}"] .disciplina-info`);
    const aulasElement = infoElement?.querySelector('.aulas-obrigatorias');
    const horasInput = document.querySelector(`input[name="disciplina_horas_${disciplinaId}"]`);
    const horasGroup = horasInput?.closest('.input-group');
    const horasLabel = horasGroup?.querySelector('.input-group-text');
    
    if (!disciplinaSelect || !infoElement) return;
    
    const selectedOption = disciplinaSelect.options[disciplinaSelect.selectedIndex];
    
    if (selectedOption.value) {
        const aulas = selectedOption.dataset.aulas;
        const cor = selectedOption.dataset.cor;
        
        aulasElement.textContent = aulas;
        infoElement.style.display = 'block';
        
        // Mostrar campo de horas e configurar valor padrão
        if (horasInput && horasGroup && horasLabel) {
            horasInput.value = aulas; // Definir valor padrão
            horasInput.style.display = 'block';
            horasGroup.style.display = 'flex';
            horasLabel.style.display = 'inline-block';
        }
        
        // Aplicar cor da disciplina
        const disciplinaItem = disciplinaSelect.closest('.disciplina-item');
        disciplinaItem.style.borderLeft = `4px solid ${cor}`;
        
        console.log(`✅ Disciplina selecionada: ${selectedOption.textContent} (${aulas} aulas padrão)`);
    } else {
        infoElement.style.display = 'none';
        
        // Esconder campo de horas
        if (horasInput && horasGroup && horasLabel) {
            horasInput.style.display = 'none';
            horasGroup.style.display = 'none';
            horasLabel.style.display = 'none';
            horasInput.value = '';
        }
        
        const disciplinaItem = disciplinaSelect.closest('.disciplina-item');
        disciplinaItem.style.borderLeft = '';
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
            const cor = selectedOption.dataset.cor;
            
            totalHoras += horas;
            disciplinasComHoras.push({
                nome: selectedOption.textContent,
                horas: horas,
                cor: cor
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

// Recarregar disciplinas quando curso mudar
document.addEventListener('DOMContentLoaded', function() {
    console.log('📄 DOM carregado - sistema pronto!');
    const cursoSelect = document.getElementById('curso_tipo');
    if (cursoSelect) {
        cursoSelect.addEventListener('change', function() {
            // Limpar disciplinas existentes quando curso mudar
            const container = document.getElementById('disciplinas-container');
            if (container) {
                container.innerHTML = '';
                contadorDisciplinas = 0;
            }
        });
    }
});
</script>

