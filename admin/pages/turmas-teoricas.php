<?php
/**
 * Página Principal de Gestão de Turmas Teóricas
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
            // Usar JavaScript para redirecionamento ao invés de header
            $redirectUrl = '?page=turmas-teoricas&acao=agendar&step=2&turma_id=' . $resultado['turma_id'] . '&sucesso=1';
            echo "<script>window.location.href = '$redirectUrl';</script>";
            exit;
        } else {
            $erro = $resultado['mensagem'];
        }
    } else {
        $erro = $rascunho['mensagem'];
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

<style>
/* CSS para o sistema de turmas teóricas */
.turma-wizard {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.wizard-header {
    background: linear-gradient(135deg, #023A8D 0%, #1a4fa0 100%);
    color: white;
    padding: 20px;
    text-align: center;
}

.wizard-steps {
    display: flex;
    justify-content: center;
    margin-top: 15px;
}

.wizard-step-btn {
    display: flex;
    align-items: center;
    margin: 0 10px;
    padding: 8px 16px;
    border-radius: 20px;
    background: rgba(255,255,255,0.2);
    font-size: 14px;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    color: inherit;
    text-decoration: none;
    outline: none;
}

.wizard-step-btn:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.wizard-step-btn.active {
    background: #F7931E;
    font-weight: bold;
    color: white;
}

.wizard-step-btn.active:hover {
    background: #e8831a;
}

.wizard-step-btn.completed {
    background: rgba(255,255,255,0.3);
}

.wizard-step-btn.completed:hover {
    background: rgba(255,255,255,0.4);
}

/* Manter compatibilidade com wizard-step antigo */
.wizard-step {
    display: flex;
    align-items: center;
    margin: 0 10px;
    padding: 8px 16px;
    border-radius: 20px;
    background: rgba(255,255,255,0.2);
    font-size: 14px;
    transition: all 0.3s ease;
}

.wizard-step.active {
    background: #F7931E;
    font-weight: bold;
}

.wizard-step.completed {
    background: rgba(255,255,255,0.3);
}

.wizard-content {
    padding: 30px;
}

.form-section {
    margin-bottom: 25px;
}

.form-section h4 {
    color: #023A8D;
    margin-bottom: 15px;
    border-bottom: 2px solid #F7931E;
    padding-bottom: 8px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #333;
}

.form-control {
    width: 100%;
    padding: 12px;
    border: 2px solid #e9ecef;
    border-radius: 6px;
    font-size: 16px;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    border-color: #023A8D;
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(2, 58, 141, 0.25);
}

.btn-primary {
    background: #023A8D;
    border: none;
    padding: 12px 30px;
    border-radius: 6px;
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: #1a4fa0;
    transform: translateY(-2px);
}

.btn-secondary {
    background: #6c757d;
    border: none;
    padding: 12px 30px;
    border-radius: 6px;
    color: white;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
    margin-right: 10px;
}

.btn-secondary:hover {
    background: #5a6268;
    color: white;
    text-decoration: none;
}

.btn-warning {
    background: #F7931E;
    border: none;
    padding: 12px 30px;
    border-radius: 6px;
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-warning:hover {
    background: #e8840d;
}

.radio-group {
    display: flex;
    gap: 20px;
    margin-top: 8px;
}

.radio-option {
    display: flex;
    align-items: center;
    gap: 8px;
}

.turma-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 20px;
    margin-bottom: 20px;
    border-left: 4px solid #023A8D;
}

.turma-card h5 {
    color: #023A8D;
    margin-bottom: 10px;
}

.turma-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 15px;
    font-size: 14px;
    color: #666;
}

.turma-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-criando { background: #fff3cd; color: #856404; }
.status-agendando { background: #cce5ff; color: #004085; }
.status-completa { background: #d4edda; color: #155724; }
.status-ativa { background: #d1ecf1; color: #0c5460; }
.status-concluida { background: #e2e3e5; color: #383d41; }

.progresso-disciplinas {
    margin-top: 20px;
}

.disciplina-item {
    display: flex;
    justify-content: between;
    align-items: center;
    padding: 10px;
    margin-bottom: 8px;
    background: #f8f9fa;
    border-radius: 6px;
    border-left: 4px solid #ddd;
}

.disciplina-item.completa {
    border-left-color: #28a745;
    background: #d4edda;
}

.disciplina-item.parcial {
    border-left-color: #ffc107;
    background: #fff3cd;
}

.disciplina-item.pendente {
    border-left-color: #dc3545;
    background: #f8d7da;
}

.alert {
    padding: 12px 20px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-info {
    background: #cce5ff;
    color: #004085;
    border: 1px solid #b6d4fe;
}

/* Responsivo */
@media (max-width: 768px) {
    .wizard-content {
        padding: 20px;
    }
    
    .wizard-steps {
        flex-direction: column;
        gap: 10px;
    }
    
    .radio-group {
        flex-direction: column;
        gap: 10px;
    }
    
    .turma-meta {
        flex-direction: column;
        gap: 8px;
    }
}
</style>

<div class="turma-wizard">
    <div class="wizard-header">
        <h2>📚 Sistema de Turmas Teóricas</h2>
        <div class="wizard-steps">
            <button type="button" class="wizard-step-btn <?= ($step == '1') ? 'active' : (($step > '1') ? 'completed' : '') ?>" onclick="navegarParaEtapa(1)" title="Ir para Dados Básicos">
                📝 1. Dados Básicos
            </button>
            <button type="button" class="wizard-step-btn <?= ($step == '2') ? 'active' : (($step > '2') ? 'completed' : '') ?>" onclick="navegarParaEtapa(2)" title="Ir para Agendamento">
                📅 2. Agendamento
            </button>
            <button type="button" class="wizard-step-btn <?= ($step == '3') ? 'active' : (($step > '3') ? 'completed' : '') ?>" onclick="navegarParaEtapa(3)" title="Ir para Carga Horária">
                ⏱️ 3. Carga Horária
            </button>
            <button type="button" class="wizard-step-btn <?= ($step == '4') ? 'active' : '' ?>" onclick="navegarParaEtapa(4)" title="Ir para Alunos">
                👥 4. Alunos
            </button>
        </div>
    </div>
    
    <div class="wizard-content">
        <?php if (isset($sucesso)): ?>
            <div class="alert alert-success">
                <strong>✅ Sucesso:</strong> <?= htmlspecialchars($sucesso) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger">
                <strong>❌ Erro:</strong> <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($acao === 'detalhes'): ?>
            <!-- DETALHES DA TURMA -->
            <?php include __DIR__ . '/turmas-teoricas-detalhes.php'; ?>
            
        <?php elseif ($acao === '' || $acao === 'listar'): ?>
            <!-- LISTA DE TURMAS -->
            <?php include __DIR__ . '/turmas-teoricas-lista.php'; ?>
            
        <?php elseif ($step === '1' || $acao === 'nova'): ?>
            <!-- STEP 1: CRIAÇÃO BÁSICA -->
            <form method="POST" action="?page=turmas-teoricas">
                <input type="hidden" name="acao" value="criar_basica">
                <input type="hidden" name="step" value="1">
                
                <div class="form-section">
                    <h4>📝 Informações Básicas da Turma</h4>
                    
                    <div class="form-group">
                        <label for="nome">Nome da Turma *</label>
                        <input type="text" 
                               id="nome" 
                               name="nome" 
                               class="form-control" 
                               placeholder="Ex: Turma A - Formação CNH B"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="sala_id">Sala *</label>
                        <div class="d-flex gap-2">
                            <select id="sala_id" name="sala_id" class="form-control" required>
                                <option value="">Selecione uma sala...</option>
                                <?php foreach ($salasDisponiveis as $sala): ?>
                                    <option value="<?= $sala['id'] ?>">
                                        <?= htmlspecialchars($sala['nome']) ?> 
                                        (Capacidade: <?= $sala['capacidade'] ?> alunos)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-outline-primary" onclick="abrirModalSalasInterno()" title="Gerenciar Salas">
                                <i class="fas fa-cog"></i>
                            </button>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            <?php echo count($salasDisponiveis); ?> sala(s) cadastrada(s) - 
                            <a href="#" onclick="abrirModalSalasInterno()" class="text-primary">Clique aqui para gerenciar</a>
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="curso_tipo">Tipo de Curso *</label>
                        <div class="d-flex">
                            <select id="curso_tipo" name="curso_tipo" class="form-control" required>
                                <option value="">Selecione o tipo de curso...</option>
                                <?php foreach ($cursosDisponiveis as $key => $nome): ?>
                                    <option value="<?= $key ?>"><?= htmlspecialchars($nome) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-outline-primary ms-2" onclick="abrirModalTiposCursoInterno()" title="Gerenciar tipos de curso">
                                <i class="fas fa-cog"></i>
                            </button>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            <?php echo count($cursosDisponiveis); ?> tipo(s) de curso cadastrado(s) - 
                            <a href="#" onclick="abrirModalTiposCursoInterno()" class="text-primary">Clique aqui para gerenciar</a>
                        </small>
                    </div>
                    
                    <!-- Seção de Disciplinas -->
                    <div class="form-group">
                        <label>Disciplinas do Curso</label>
                        <div class="d-flex">
                            <div class="alert alert-info flex-grow-1 mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Disciplinas disponíveis:</strong> As disciplinas serão carregadas automaticamente baseadas no tipo de curso selecionado.
                            </div>
                            <button type="button" class="btn btn-outline-secondary ms-2" onclick="abrirModalDisciplinasInterno()" title="Gerenciar disciplinas">
                                <i class="fas fa-book"></i> Disciplinas
                            </button>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-lightbulb me-1"></i>
                            Configure as disciplinas disponíveis para os cursos
                        </small>
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
                        Próxima Etapa: Agendamento →
                    </button>
                </div>
            </form>
            
        <?php elseif ($step === '2' || $acao === 'agendar'): ?>
            <!-- STEP 2: AGENDAMENTO DE AULAS -->
            <?php include __DIR__ . '/turmas-teoricas-step2.php'; ?>
            
        <?php elseif ($step === '3' || $acao === 'revisar'): ?>
            <!-- STEP 3: REVISÃO E CONTROLE DE CARGA HORÁRIA -->
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

<!-- Modal Gerenciar Salas -->
<div class="modal fade" id="modalGerenciarSalas" tabindex="-1" aria-labelledby="modalGerenciarSalasLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalGerenciarSalasLabel">
                    <i class="fas fa-door-open me-2"></i>Gerenciar Salas
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6>Salas Cadastradas</h6>
                    <button class="btn btn-primary btn-sm" onclick="abrirModalNovaSalaInterno()">
                        <i class="fas fa-plus me-1"></i>Nova Sala
                    </button>
                </div>
                <div id="lista-salas-modal">
                    <!-- Lista de salas será carregada via AJAX -->
                    <div class="text-center py-3">
                        <i class="fas fa-spinner fa-spin"></i> Carregando salas...
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Sala -->
<div class="modal fade" id="modalEditarSala" tabindex="-1" aria-labelledby="modalEditarSalaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarSalaLabel">
                    <i class="fas fa-edit me-2"></i>Editar Sala
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditarSala">
                <div class="modal-body">
                    <input type="hidden" id="editar_sala_id" name="id">
                    
                    <div class="mb-3">
                        <label for="editar_nome" class="form-label">Nome da Sala</label>
                        <input type="text" class="form-control" id="editar_nome" name="nome" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editar_capacidade" class="form-label">Capacidade</label>
                        <input type="number" class="form-control" id="editar_capacidade" name="capacidade" min="1" max="100" required>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="editar_ativa" name="ativa" value="1">
                            <label class="form-check-label" for="editar_ativa">
                                Sala ativa
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmar Exclusão -->
<div class="modal fade" id="modalConfirmarExclusao" tabindex="-1" aria-labelledby="modalConfirmarExclusaoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConfirmarExclusaoLabel">
                    <i class="fas fa-exclamation-triangle me-2 text-warning"></i>Confirmar Exclusão
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir a sala <strong id="nome_sala_exclusao"></strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-warning me-2"></i>
                    <strong>Atenção:</strong> Esta ação não pode ser desfeita. Se a sala estiver sendo usada em turmas teóricas, a exclusão será bloqueada.
                </div>
                <input type="hidden" id="excluir_sala_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" onclick="confirmarExclusao()">
                    <i class="fas fa-trash me-1"></i>Excluir Sala
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Gerenciar Tipos de Curso -->
<div class="modal fade" id="modalGerenciarTiposCurso" tabindex="-1" aria-labelledby="modalGerenciarTiposCursoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalGerenciarTiposCursoLabel">
                    <i class="fas fa-graduation-cap me-2"></i>Gerenciar Tipos de Curso
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6>Tipos de Curso Cadastrados</h6>
                    <button class="btn btn-primary btn-sm" onclick="abrirModalNovoTipoCurso()">
                        <i class="fas fa-plus me-1"></i>Novo Tipo de Curso
                    </button>
                </div>
                <div id="lista-tipos-curso-modal">
                    <!-- Lista de tipos de curso será carregada via AJAX -->
                    <div class="text-center py-3">
                        <i class="fas fa-spinner fa-spin"></i> Carregando tipos de curso...
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Tipo de Curso -->
<div class="modal fade" id="modalEditarTipoCurso" tabindex="-1" aria-labelledby="modalEditarTipoCursoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarTipoCursoLabel">
                    <i class="fas fa-edit me-2"></i>Editar Tipo de Curso
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditarTipoCurso">
                <div class="modal-body">
                    <input type="hidden" id="editar_tipo_curso_id" name="id">
                    
                    <div class="mb-3">
                        <label for="editar_codigo" class="form-label">Código do Curso</label>
                        <input type="text" class="form-control" id="editar_codigo" name="codigo" required>
                        <small class="text-muted">Ex: formacao_45h, reciclagem_infrator</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editar_nome_tipo" class="form-label">Nome do Curso</label>
                        <input type="text" class="form-control" id="editar_nome_tipo" name="nome" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editar_descricao_tipo" class="form-label">Descrição</label>
                        <textarea class="form-control" id="editar_descricao_tipo" name="descricao" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editar_carga_horaria" class="form-label">Carga Horária Total</label>
                        <input type="number" class="form-control" id="editar_carga_horaria" name="carga_horaria_total" min="1" max="200" required>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="editar_ativo_tipo" name="ativo" value="1">
                            <label class="form-check-label" for="editar_ativo_tipo">
                                Tipo de curso ativo
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmar Exclusão Tipo de Curso -->
<div class="modal fade" id="modalConfirmarExclusaoTipo" tabindex="-1" aria-labelledby="modalConfirmarExclusaoTipoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConfirmarExclusaoTipoLabel">
                    <i class="fas fa-exclamation-triangle me-2 text-warning"></i>Confirmar Exclusão
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir o tipo de curso <strong id="nome_tipo_exclusao"></strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-warning me-2"></i>
                    <strong>Atenção:</strong> Esta ação não pode ser desfeita. Se o tipo de curso estiver sendo usado em turmas teóricas, a exclusão será bloqueada.
                </div>
                <input type="hidden" id="excluir_tipo_curso_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" onclick="confirmarExclusaoTipoCurso()">
                    <i class="fas fa-trash me-1"></i>Excluir Tipo de Curso
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Novo Tipo de Curso -->
<div class="modal fade" id="modalNovoTipoCurso" tabindex="-1" aria-labelledby="modalNovoTipoCursoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNovoTipoCursoLabel">
                    <i class="fas fa-plus me-2"></i>Novo Tipo de Curso
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formNovoTipoCurso">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="novo_codigo" class="form-label">Código do Curso</label>
                        <input type="text" class="form-control" id="novo_codigo" name="codigo" required>
                        <small class="text-muted">Ex: formacao_45h, reciclagem_infrator</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="novo_nome_tipo" class="form-label">Nome do Curso</label>
                        <input type="text" class="form-control" id="novo_nome_tipo" name="nome" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="novo_descricao_tipo" class="form-label">Descrição</label>
                        <textarea class="form-control" id="novo_descricao_tipo" name="descricao" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="novo_carga_horaria" class="form-label">Carga Horária Total</label>
                        <input type="number" class="form-control" id="novo_carga_horaria" name="carga_horaria_total" min="1" max="200" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Criar Tipo de Curso
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Nova Sala -->
<div class="modal fade" id="modalNovaSalaInterno" tabindex="-1" aria-labelledby="modalNovaSalaInternoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNovaSalaInternoLabel">
                    <i class="fas fa-plus me-2"></i>Nova Sala
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formNovaSalaInterno">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nome_sala" class="form-label">Nome da Sala *</label>
                        <input type="text" class="form-control" id="nome_sala" name="nome" required placeholder="Ex: Sala 1, Laboratório">
                    </div>
                    
                    <div class="mb-3">
                        <label for="capacidade_sala" class="form-label">Capacidade *</label>
                        <input type="number" class="form-control" id="capacidade_sala" name="capacidade" min="1" max="100" value="30" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Equipamentos</label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="equipamentos[projetor]" id="projetor_sala">
                                    <label class="form-check-label" for="projetor_sala">Projetor</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="equipamentos[quadro]" id="quadro_sala">
                                    <label class="form-check-label" for="quadro_sala">Quadro</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="equipamentos[ar_condicionado]" id="ar_sala">
                                    <label class="form-check-label" for="ar_sala">Ar Condicionado</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="equipamentos[computadores]" id="computadores_sala">
                                    <label class="form-check-label" for="computadores_sala">Computadores</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="equipamentos[internet]" id="internet_sala">
                                    <label class="form-check-label" for="internet_sala">Internet</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="equipamentos[som]" id="som_sala">
                                    <label class="form-check-label" for="som_sala">Sistema de Som</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="salvarNovaSala()">
                        <i class="fas fa-save me-1"></i>Salvar Sala
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Gerenciar Disciplinas - Layout Otimizado -->
<div class="modal fade" id="modalGerenciarDisciplinas" tabindex="-1" aria-labelledby="modalGerenciarDisciplinasLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-fullscreen-lg-down">
        <div class="modal-content border-0 shadow-lg">
            <!-- Header com gradiente -->
            <div class="modal-header border-0" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white;">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-graduation-cap" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0 fw-bold" id="modalGerenciarDisciplinasLabel">
                            Gerenciar Disciplinas
                        </h5>
                        <small class="opacity-75">Gerencie as disciplinas do sistema</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Body com controles otimizados -->
            <div class="modal-body p-0">
                <!-- Barra de controles -->
                <div class="bg-light border-bottom p-3">
                    <div class="row g-3 align-items-center">
                        <!-- Busca -->
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" id="buscarDisciplinas" placeholder="Buscar disciplinas..." onkeyup="filtrarDisciplinas()">
                            </div>
                        </div>
                        
                        <!-- Filtros -->
                        <div class="col-md-3">
                            <select class="form-select" id="filtroStatus" onchange="filtrarDisciplinas()">
                                <option value="">Todos os status</option>
                                <option value="ativo">Ativo</option>
                                <option value="inativo">Inativo</option>
                            </select>
                        </div>
                        
                        <!-- Ordenação -->
                        <div class="col-md-3">
                            <select class="form-select" id="ordenarDisciplinas" onchange="filtrarDisciplinas()">
                                <option value="nome">Nome A-Z</option>
                                <option value="nome_desc">Nome Z-A</option>
                                <option value="carga">Carga horária</option>
                                <option value="codigo">Código</option>
                            </select>
                        </div>
                        
                        <!-- Botão Nova Disciplina -->
                        <div class="col-md-2">
                            <button type="button" class="btn btn-primary w-100" onclick="abrirModalNovaDisciplina()">
                                <i class="fas fa-plus me-1"></i>Nova
                            </button>
                        </div>
                    </div>
                    
                    <!-- Estatísticas -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex gap-3">
                                    <small class="text-muted">
                                        <i class="fas fa-book me-1"></i>
                                        <span id="totalDisciplinas">0</span> disciplinas
                                    </small>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        <span id="totalCargaHoraria">0</span> aulas total
                                    </small>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="limparFiltrosDisciplinas()">
                                        <i class="fas fa-times me-1"></i>Limpar Filtros
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de disciplinas -->
                <div class="p-3">
                    <div id="listaDisciplinas" class="row g-3">
                        <!-- Lista de disciplinas será carregada aqui -->
                    </div>
                    
                    <!-- Estado vazio com filtros -->
                    <div id="nenhumaDisciplinaEncontrada" class="text-center py-5" style="display: none;">
                        <div class="mb-3">
                            <i class="fas fa-search" style="font-size: 3rem; color: #6c757d;"></i>
                        </div>
                        <h6 class="text-muted">Nenhuma disciplina encontrada</h6>
                        <p class="text-muted">Tente ajustar os filtros de busca.</p>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="limparFiltrosDisciplinas()">
                            <i class="fas fa-times me-1"></i>Limpar Filtros
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Footer simplificado -->
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Fechar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nova Disciplina -->
<div class="modal fade" id="modalNovaDisciplina" tabindex="-1" aria-labelledby="modalNovaDisciplinaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNovaDisciplinaLabel">
                    <i class="fas fa-plus me-2"></i>Nova Disciplina
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formNovaDisciplina">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="codigo" class="form-label">Código *</label>
                        <input type="text" class="form-control" id="codigo" name="codigo" required>
                        <div class="form-text">Ex: legislacao_transito</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome *</label>
                        <input type="text" class="form-control" id="nome" name="nome" required>
                        <div class="form-text">Ex: Legislação de Trânsito</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="carga_horaria_padrao" class="form-label">Carga Horária Padrão</label>
                                <input type="number" class="form-control" id="carga_horaria_padrao" name="carga_horaria_padrao" value="1" min="1">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cor_hex" class="form-label">Cor</label>
                                <input type="color" class="form-control" id="cor_hex" name="cor_hex" value="#007bff">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="icone" class="form-label">Ícone</label>
                        <select class="form-control" id="icone" name="icone">
                            <option value="book">Livro</option>
                            <option value="gavel">Martelo</option>
                            <option value="shield-alt">Escudo</option>
                            <option value="first-aid">Primeiros Socorros</option>
                            <option value="leaf">Folha</option>
                            <option value="wrench">Chave</option>
                            <option value="car">Carro</option>
                            <option value="road">Estrada</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Salvar Disciplina
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Disciplina -->
<div class="modal fade" id="modalEditarDisciplina" tabindex="-1" aria-labelledby="modalEditarDisciplinaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarDisciplinaLabel">
                    <i class="fas fa-edit me-2"></i>Editar Disciplina
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditarDisciplina">
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_codigo" class="form-label">Código *</label>
                        <input type="text" class="form-control" id="edit_codigo" name="codigo" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_nome" class="form-label">Nome *</label>
                        <input type="text" class="form-control" id="edit_nome" name="nome" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="edit_descricao" name="descricao" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_carga_horaria_padrao" class="form-label">Carga Horária Padrão</label>
                                <input type="number" class="form-control" id="edit_carga_horaria_padrao" name="carga_horaria_padrao" min="1">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_cor_hex" class="form-label">Cor</label>
                                <input type="color" class="form-control" id="edit_cor_hex" name="cor_hex">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_icone" class="form-label">Ícone</label>
                        <select class="form-control" id="edit_icone" name="icone">
                            <option value="book">Livro</option>
                            <option value="gavel">Martelo</option>
                            <option value="shield-alt">Escudo</option>
                            <option value="first-aid">Primeiros Socorros</option>
                            <option value="leaf">Folha</option>
                            <option value="wrench">Chave</option>
                            <option value="car">Carro</option>
                            <option value="road">Estrada</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Atualizar Disciplina
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmar Exclusão de Disciplina -->
<div class="modal fade" id="modalConfirmarExclusaoDisciplina" tabindex="-1" aria-labelledby="modalConfirmarExclusaoDisciplinaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConfirmarExclusaoDisciplinaLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Exclusão
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir esta disciplina?</p>
                <p><strong>Esta ação não pode ser desfeita.</strong></p>
                <div id="detalhesDisciplinaExclusao"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmarExclusaoDisciplina">
                    <i class="fas fa-trash me-1"></i>Excluir
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Carregar salas quando modal abrir
document.getElementById('modalGerenciarSalas').addEventListener('show.bs.modal', function() {
    recarregarSalas();
});

// Função para abrir modal interno de gerenciamento de salas
function abrirModalSalasInterno() {
    const modal = new bootstrap.Modal(document.getElementById('modalGerenciarSalas'));
    modal.show();
}

// Função para recarregar lista de salas via AJAX
function recarregarSalas() {
    fetch('/cfc-bom-conselho/admin/api/salas-clean.php?acao=listar')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            // Verificar se a resposta é realmente JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Resposta não é JSON válido. Content-Type: ' + contentType);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Texto recebido:', text);
                    throw new Error('JSON inválido: ' + e.message);
                }
            });
        })
        .then(data => {
            if (data.sucesso) {
                const selectSala = document.getElementById('sala_id');
                const salasContainer = document.getElementById('lista-salas-modal');
                
                // Atualizar dropdown
                selectSala.innerHTML = '<option value="">Selecione uma sala...</option>';
                data.salas.forEach(sala => {
                    selectSala.innerHTML += `<option value="${sala.id}">${sala.nome} (Capacidade: ${sala.capacidade} alunos)</option>`;
                });
                
                // Atualizar lista no modal
                salasContainer.innerHTML = data.html;
                
                // Atualizar contador
                const smallText = document.querySelector('small.text-muted');
                if (smallText) {
                    smallText.innerHTML = `<i class="fas fa-info-circle me-1"></i>${data.salas.length} sala(s) cadastrada(s) - <a href="#" onclick="abrirModalSalasInterno()" class="text-primary">Clique aqui para gerenciar</a>`;
                }
            } else {
                console.error('Erro na resposta:', data.mensagem);
                document.getElementById('lista-salas-modal').innerHTML = '<div class="alert alert-danger">Erro ao carregar salas: ' + data.mensagem + '</div>';
            }
        })
        .catch(error => {
            console.error('Erro ao recarregar salas:', error);
            document.getElementById('lista-salas-modal').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Erro ao carregar salas:</strong> ${error.message || 'Erro de conexão'}
                    <br><small class="text-muted">Verifique se o servidor está funcionando corretamente.</small>
                </div>`;
        });
}

// Função para abrir modal de nova sala
function abrirModalNovaSalaInterno() {
    const modal = new bootstrap.Modal(document.getElementById('modalNovaSalaInterno'));
    modal.show();
}

// Função para editar sala
function editarSala(id, nome, capacidade, ativa) {
    document.getElementById('editar_sala_id').value = id;
    document.getElementById('editar_nome').value = nome;
    document.getElementById('editar_capacidade').value = capacidade;
    document.getElementById('editar_ativa').checked = ativa == 1;
    
    const modal = new bootstrap.Modal(document.getElementById('modalEditarSala'));
    modal.show();
}

// Função para excluir sala
function excluirSala(id, nome) {
    document.getElementById('excluir_sala_id').value = id;
    document.getElementById('nome_sala_exclusao').textContent = nome;
    
    const modal = new bootstrap.Modal(document.getElementById('modalConfirmarExclusao'));
    modal.show();
}

// Função para abrir modal interno de gerenciamento de tipos de curso
function abrirModalTiposCursoInterno() {
    const modal = new bootstrap.Modal(document.getElementById('modalGerenciarTiposCurso'));
    modal.show();
    recarregarTiposCurso();
}

// Função para recarregar lista de tipos de curso via AJAX
function recarregarTiposCurso() {
    fetch('/cfc-bom-conselho/admin/api/tipos-curso-clean.php?acao=listar')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            // Verificar se a resposta é realmente JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Resposta não é JSON válido. Content-Type: ' + contentType);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Texto recebido:', text);
                    throw new Error('JSON inválido: ' + e.message);
                }
            });
        })
        .then(data => {
            if (data.sucesso) {
                const selectCurso = document.getElementById('curso_tipo');
                const tiposContainer = document.getElementById('lista-tipos-curso-modal');
                
                // Atualizar dropdown
                selectCurso.innerHTML = '<option value="">Selecione o tipo de curso...</option>';
                data.tipos.forEach(tipo => {
                    selectCurso.innerHTML += `<option value="${tipo.codigo}">${tipo.nome}</option>`;
                });
                
                // Atualizar lista no modal
                tiposContainer.innerHTML = data.html;
                
                // Atualizar contador
                const smallText = document.querySelector('small.text-muted');
                if (smallText && smallText.textContent.includes('tipo(s) de curso')) {
                    smallText.innerHTML = `<i class="fas fa-info-circle me-1"></i>${data.tipos.length} tipo(s) de curso cadastrado(s) - <a href="#" onclick="abrirModalTiposCursoInterno()" class="text-primary">Clique aqui para gerenciar</a>`;
                }
            } else {
                console.error('Erro na resposta:', data.mensagem);
                document.getElementById('lista-tipos-curso-modal').innerHTML = '<div class="alert alert-danger">Erro ao carregar tipos de curso: ' + data.mensagem + '</div>';
            }
        })
        .catch(error => {
            console.error('Erro ao recarregar tipos de curso:', error);
            document.getElementById('lista-tipos-curso-modal').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Erro ao carregar tipos de curso:</strong> ${error.message || 'Erro de conexão'}
                    <br><small class="text-muted">Verifique se o servidor está funcionando corretamente.</small>
                </div>`;
        });
}

// Função para abrir modal de novo tipo de curso
function abrirModalNovoTipoCurso() {
    const modal = new bootstrap.Modal(document.getElementById('modalNovoTipoCurso'));
    modal.show();
}

// Função para editar tipo de curso
function editarTipoCurso(id, codigo, nome, descricao, carga_horaria_total, ativo) {
    document.getElementById('editar_tipo_curso_id').value = id;
    document.getElementById('editar_codigo').value = codigo;
    document.getElementById('editar_nome_tipo').value = nome;
    document.getElementById('editar_descricao_tipo').value = descricao;
    document.getElementById('editar_carga_horaria').value = carga_horaria_total;
    document.getElementById('editar_ativo_tipo').checked = ativo == 1;
    
    const modal = new bootstrap.Modal(document.getElementById('modalEditarTipoCurso'));
    modal.show();
}

// Função para excluir tipo de curso
function excluirTipoCurso(id, nome) {
    document.getElementById('excluir_tipo_curso_id').value = id;
    document.getElementById('nome_tipo_exclusao').textContent = nome;
    
    const modal = new bootstrap.Modal(document.getElementById('modalConfirmarExclusaoTipo'));
    modal.show();
}

// Função para confirmar exclusão de tipo de curso
function confirmarExclusaoTipoCurso() {
    const id = document.getElementById('excluir_tipo_curso_id').value;
    
    const formData = new FormData();
    formData.append('acao', 'excluir');
    formData.append('id', id);
    
    fetch('/cfc-bom-conselho/admin/api/tipos-curso-clean.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text().then(text => {
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Texto recebido:', text);
            throw new Error('JSON inválido: ' + e.message);
        }
    }))
    .then(data => {
        console.log('Resposta da API:', data);
        
        if (data.sucesso) {
            // Fechar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalConfirmarExclusaoTipo'));
            modal.hide();
            
            // Recarregar lista de tipos de curso
            recarregarTiposCurso();
            
            // Mostrar mensagem de sucesso
            showAlert('success', data.mensagem);
        } else {
            let mensagem = data.mensagem || 'Erro desconhecido';
            
            // Se houver informações de debug, adicionar ao console
            if (data.debug) {
                console.error('Debug da API:', data.debug);
                mensagem += ' (Verifique o console para mais detalhes)';
            }
            
            showAlert('danger', mensagem);
        }
    })
    .catch(error => {
        console.error('Erro ao excluir tipo de curso:', error);
        showAlert('danger', 'Erro ao excluir tipo de curso: ' + error.message);
    });
}

// Função para confirmar exclusão
function confirmarExclusao() {
    const id = document.getElementById('excluir_sala_id').value;
    
    const formData = new FormData();
    formData.append('acao', 'excluir');
    formData.append('id', id);
    
    fetch('/cfc-bom-conselho/admin/api/salas-clean.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text().then(text => {
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Texto recebido:', text);
            throw new Error('JSON inválido: ' + e.message);
        }
    }))
    .then(data => {
        console.log('Resposta da API:', data);
        
        if (data.sucesso) {
            // Fechar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalConfirmarExclusao'));
            modal.hide();
            
            // Recarregar lista de salas
            recarregarSalas();
            
            // Mostrar mensagem de sucesso
            showAlert('success', data.mensagem);
        } else {
            let mensagem = data.mensagem || 'Erro desconhecido';
            
            // Se houver informações de debug, adicionar ao console
            if (data.debug) {
                console.error('Debug da API:', data.debug);
                mensagem += ' (Verifique o console para mais detalhes)';
            }
            
            showAlert('danger', mensagem);
        }
    })
    .catch(error => {
        console.error('Erro ao excluir sala:', error);
        showAlert('danger', 'Erro ao excluir sala: ' + error.message);
    });
}

// Função para exibir alertas
function showAlert(type, message) {
    // Remover alertas existentes
    const existingAlerts = document.querySelectorAll('.alert-custom');
    existingAlerts.forEach(alert => alert.remove());
    
    // Criar novo alerta
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show alert-custom`;
    alertDiv.style.position = 'fixed';
    alertDiv.style.top = '20px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '9999';
    alertDiv.style.minWidth = '300px';
    
    const icon = type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-triangle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle';
    
    alertDiv.innerHTML = `
        <i class="fas fa-${icon} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto-remover após 5 segundos
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Função para salvar rascunho automaticamente
function salvarRascunho() {
    const form = document.getElementById('formTurmaBasica');
    if (!form) return;
    
    const formData = new FormData(form);
    formData.append('acao', 'salvar_rascunho');
    
    fetch('/cfc-bom-conselho/admin/pages/turmas-teoricas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text().then(text => {
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Texto recebido (não é JSON):', text);
            throw new Error('Resposta não é JSON válido: ' + e.message);
        }
    }))
    .then(data => {
        if (data.sucesso) {
            console.log('Rascunho salvo automaticamente');
        } else {
            console.error('Erro ao salvar rascunho:', data.mensagem);
            if (data.debug) {
                console.error('Debug info:', data.debug);
            }
        }
    })
    .catch(error => {
        console.error('Erro ao salvar rascunho:', error);
    });
}

// Função para carregar dados da turma
function carregarRascunho() {
    const rascunho = <?php echo json_encode($rascunhoCarregado); ?>;
    const turmaAtual = <?php echo json_encode($turmaAtual); ?>;
    
    console.log('=== DEBUG: Carregamento de Dados ===');
    console.log('rascunho:', rascunho);
    console.log('turmaAtual:', turmaAtual);
    
    // Usar turmaAtual se disponível, senão usar rascunho
    const dados = turmaAtual || rascunho;
    
    console.log('dados a serem carregados:', dados);
    
    if (dados) {
        console.log('Carregando dados nos campos...');
        
        // Preencher campos com dados da turma
        if (dados.nome) {
            const nomeElement = document.getElementById('nome');
            if (nomeElement) {
                nomeElement.value = dados.nome;
                console.log('✅ Nome carregado:', dados.nome);
            } else {
                console.log('❌ Elemento nome não encontrado');
            }
        }
        
        if (dados.sala_id) {
            const salaElement = document.getElementById('sala_id');
            if (salaElement) {
                salaElement.value = dados.sala_id;
                console.log('✅ Sala ID carregado:', dados.sala_id);
            } else {
                console.log('❌ Elemento sala_id não encontrado');
            }
        }
        
        if (dados.curso_tipo) {
            const cursoElement = document.getElementById('curso_tipo');
            if (cursoElement) {
                cursoElement.value = dados.curso_tipo;
                console.log('✅ Curso tipo carregado:', dados.curso_tipo);
            } else {
                console.log('❌ Elemento curso_tipo não encontrado');
            }
        }
        
        if (dados.modalidade) {
            const radioModalidade = document.querySelector(`input[name="modalidade"][value="${dados.modalidade}"]`);
            if (radioModalidade) {
                radioModalidade.checked = true;
                console.log('✅ Modalidade carregada:', dados.modalidade);
            } else {
                console.log('❌ Radio modalidade não encontrado para valor:', dados.modalidade);
            }
        }
        
        if (dados.data_inicio) {
            const dataInicioElement = document.getElementById('data_inicio');
            if (dataInicioElement) {
                dataInicioElement.value = dados.data_inicio;
                console.log('✅ Data início carregada:', dados.data_inicio);
            } else {
                console.log('❌ Elemento data_inicio não encontrado');
            }
        }
        
        if (dados.data_fim) {
            const dataFimElement = document.getElementById('data_fim');
            if (dataFimElement) {
                dataFimElement.value = dados.data_fim;
                console.log('✅ Data fim carregada:', dados.data_fim);
            } else {
                console.log('❌ Elemento data_fim não encontrado');
            }
        }
        
        if (dados.observacoes) {
            const observacoesElement = document.getElementById('observacoes');
            if (observacoesElement) {
                observacoesElement.value = dados.observacoes;
                console.log('✅ Observações carregadas:', dados.observacoes);
            } else {
                console.log('❌ Elemento observacoes não encontrado');
            }
        }
        
        if (dados.max_alunos) {
            const maxAlunosElement = document.getElementById('max_alunos');
            if (maxAlunosElement) {
                maxAlunosElement.value = dados.max_alunos;
                console.log('✅ Max alunos carregado:', dados.max_alunos);
            } else {
                console.log('❌ Elemento max_alunos não encontrado');
            }
        }
        
        console.log('✅ Dados da turma carregados automaticamente');
    } else {
        console.log('❌ Nenhum dado de turma para carregar');
    }
}

// Adicionar eventos aos formulários
document.addEventListener('DOMContentLoaded', function() {
    // Carregar rascunho se existir
    carregarRascunho();
    
    // Salvamento automático a cada 30 segundos
    setInterval(salvarRascunho, 30000);
    
    // Salvamento automático quando o usuário sai de um campo
    const campos = ['nome', 'sala_id', 'curso_tipo', 'modalidade', 'data_inicio', 'data_fim', 'observacoes', 'max_alunos'];
    campos.forEach(campo => {
        const elemento = document.getElementById(campo);
        if (elemento) {
            elemento.addEventListener('blur', salvarRascunho);
            elemento.addEventListener('change', salvarRascunho);
        }
    });
    // Formulário de edição de sala
    const formEditarSala = document.getElementById('formEditarSala');
    if (formEditarSala) {
        formEditarSala.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('acao', 'editar');
            
            fetch('/cfc-bom-conselho/admin/api/salas-clean.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Texto recebido:', text);
                    throw new Error('JSON inválido: ' + e.message);
                }
            }))
            .then(data => {
                if (data.sucesso) {
                    // Fechar modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarSala'));
                    modal.hide();
                    
                    // Recarregar lista de salas
                    recarregarSalas();
                    
                    // Mostrar mensagem de sucesso
                    showAlert('success', data.mensagem);
                } else {
                    showAlert('danger', data.mensagem);
                }
            })
            .catch(error => {
                console.error('Erro ao editar sala:', error);
                showAlert('danger', 'Erro ao editar sala: ' + error.message);
            });
        });
    }
    
    // Formulário de edição de tipo de curso
    const formEditarTipoCurso = document.getElementById('formEditarTipoCurso');
    if (formEditarTipoCurso) {
        formEditarTipoCurso.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('acao', 'editar');
            
            fetch('/cfc-bom-conselho/admin/api/tipos-curso-clean.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Texto recebido:', text);
                    throw new Error('JSON inválido: ' + e.message);
                }
            }))
            .then(data => {
                if (data.sucesso) {
                    // Fechar modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarTipoCurso'));
                    modal.hide();
                    
                    // Recarregar lista de tipos de curso
                    recarregarTiposCurso();
                    
                    // Mostrar mensagem de sucesso
                    showAlert('success', data.mensagem);
                } else {
                    showAlert('danger', data.mensagem);
                }
            })
            .catch(error => {
                console.error('Erro ao editar tipo de curso:', error);
                showAlert('danger', 'Erro ao editar tipo de curso: ' + error.message);
            });
        });
    }
    
    // Formulário de novo tipo de curso
    const formNovoTipoCurso = document.getElementById('formNovoTipoCurso');
    if (formNovoTipoCurso) {
        formNovoTipoCurso.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('acao', 'criar');
            
            fetch('/cfc-bom-conselho/admin/api/tipos-curso-clean.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Texto recebido:', text);
                    throw new Error('JSON inválido: ' + e.message);
                }
            }))
            .then(data => {
                if (data.sucesso) {
                    // Fechar modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalNovoTipoCurso'));
                    modal.hide();
                    
                    // Limpar formulário
                    this.reset();
                    
                    // Recarregar lista de tipos de curso
                    recarregarTiposCurso();
                    
                    // Mostrar mensagem de sucesso
                    showAlert('success', data.mensagem);
                } else {
                    showAlert('danger', data.mensagem);
                }
            })
            .catch(error => {
                console.error('Erro ao criar tipo de curso:', error);
                showAlert('danger', 'Erro ao criar tipo de curso: ' + error.message);
            });
        });
    }
});

// Função para salvar nova sala via AJAX
function salvarNovaSala() {
    const form = document.getElementById('formNovaSalaInterno');
    const formData = new FormData(form);
    formData.append('acao', 'criar');
    
    fetch('/cfc-bom-conselho/admin/api/salas-clean.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.sucesso) {
            alert('Sala criada com sucesso!');
            form.reset();
            bootstrap.Modal.getInstance(document.getElementById('modalNovaSalaInterno')).hide();
            recarregarSalas();
        } else {
            alert('Erro: ' + data.mensagem);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao salvar sala');
    });
}

// ==========================================
// FUNÇÕES PARA GERENCIAMENTO DE DISCIPLINAS
// ==========================================

// Carregar disciplinas quando modal abrir
document.getElementById('modalGerenciarDisciplinas').addEventListener('show.bs.modal', function() {
    recarregarDisciplinas();
});

// Função para abrir modal de gerenciamento de disciplinas
function abrirModalDisciplinasInterno() {
    const modal = new bootstrap.Modal(document.getElementById('modalGerenciarDisciplinas'));
    modal.show();
}

// Variável global para armazenar disciplinas
let disciplinasOriginais = [];

// Função para recarregar lista de disciplinas via AJAX
function recarregarDisciplinas() {
    fetch('/cfc-bom-conselho/admin/api/disciplinas-clean.php?acao=listar')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Resposta não é JSON válido. Content-Type: ' + contentType);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Texto recebido:', text);
                    throw new Error('JSON inválido: ' + e.message);
                }
            });
        })
        .then(data => {
            if (data.sucesso) {
                disciplinasOriginais = data.disciplinas;
                document.getElementById('listaDisciplinas').innerHTML = data.html;
                atualizarEstatisticas();
                console.log('Disciplinas carregadas:', data.total);
            } else {
                showAlert('danger', 'Erro ao carregar disciplinas: ' + data.mensagem);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar disciplinas:', error);
            showAlert('danger', 'Erro ao carregar disciplinas: ' + error.message + '. Verifique o servidor.');
        });
}

// Função para filtrar disciplinas
function filtrarDisciplinas() {
    const busca = document.getElementById('buscarDisciplinas').value.toLowerCase();
    const statusFiltro = document.getElementById('filtroStatus').value;
    const ordenacao = document.getElementById('ordenarDisciplinas').value;
    
    let disciplinasFiltradas = disciplinasOriginais.filter(disciplina => {
        // Filtro por busca
        const matchBusca = !busca || 
            disciplina.nome.toLowerCase().includes(busca) ||
            disciplina.codigo.toLowerCase().includes(busca) ||
            disciplina.descricao.toLowerCase().includes(busca);
        
        // Filtro por status
        const matchStatus = !statusFiltro || 
            (statusFiltro === 'ativo' && disciplina.ativa == 1) ||
            (statusFiltro === 'inativo' && disciplina.ativa == 0);
        
        return matchBusca && matchStatus;
    });
    
    // Ordenação
    disciplinasFiltradas.sort((a, b) => {
        switch (ordenacao) {
            case 'nome':
                return a.nome.localeCompare(b.nome);
            case 'nome_desc':
                return b.nome.localeCompare(a.nome);
            case 'carga':
                return b.carga_horaria_padrao - a.carga_horaria_padrao;
            case 'codigo':
                return a.codigo.localeCompare(b.codigo);
            default:
                return 0;
        }
    });
    
    // Renderizar disciplinas filtradas
    renderizarDisciplinas(disciplinasFiltradas);
    atualizarEstatisticas(disciplinasFiltradas);
}

// Função para renderizar disciplinas
function renderizarDisciplinas(disciplinas) {
    const container = document.getElementById('listaDisciplinas');
    const nenhumaEncontrada = document.getElementById('nenhumaDisciplinaEncontrada');
    
    if (disciplinas.length === 0) {
        container.innerHTML = '';
        nenhumaEncontrada.style.display = 'block';
        return;
    }
    
    nenhumaEncontrada.style.display = 'none';
    
    let html = '';
    disciplinas.forEach(disciplina => {
        const statusText = disciplina.ativa == 1 ? 'ATIVO' : 'INATIVO';
        
        html += `
        <div class="col-lg-4 col-md-6 col-12 mb-3">
            <div class="card h-100 shadow-sm border-0 disciplina-card" style="transition: all 0.3s ease; cursor: pointer;" onclick="visualizarDisciplina(${disciplina.id})">
                <div class="card-header border-0 p-3" style="background: linear-gradient(135deg, ${disciplina.cor_hex} 0%, ${disciplina.cor_hex}dd 100%); color: white; position: relative;">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 fw-bold">
                                <i class="fas fa-${disciplina.icone} me-2"></i>
                                ${disciplina.nome}
                            </h6>
                            <small class="opacity-75">${disciplina.codigo}</small>
                        </div>
                        <span class="badge rounded-pill px-2 py-1" style="background-color: rgba(255,255,255,0.25); color: white; font-size: 0.7rem;">
                            ${statusText}
                        </span>
                    </div>
                    <div class="position-absolute top-0 end-0 me-3 mt-2">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light rounded-circle p-1" style="width: 28px; height: 28px; padding: 0;" data-bs-toggle="dropdown" onclick="event.stopPropagation();">
                                <i class="fas fa-ellipsis-v" style="font-size: 0.8rem;"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#" onclick="event.stopPropagation(); editarDisciplina(${disciplina.id});"><i class="fas fa-edit me-2"></i>Editar</a></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="event.stopPropagation(); excluirDisciplina(${disciplina.id});"><i class="fas fa-trash me-2"></i>Excluir</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2 mb-3">
                        <div class="col-12">
                            <div class="d-flex align-items-center text-muted">
                                <i class="fas fa-clock me-2" style="color: ${disciplina.cor_hex};"></i>
                                <span class="fw-semibold">${disciplina.carga_horaria_padrao} aulas</span>
                            </div>
                        </div>
                    </div>
                    <div class="disciplina-descricao">
                        <small class="text-muted lh-sm">${disciplina.descricao}</small>
                    </div>
                </div>
                <div class="card-footer border-0 bg-transparent p-3 pt-0">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="event.stopPropagation(); editarDisciplina(${disciplina.id});">
                            <i class="fas fa-edit me-1"></i> Editar Disciplina
                        </button>
                    </div>
                </div>
            </div>
        </div>`;
    });
    
    container.innerHTML = html;
}

// Função para atualizar estatísticas
function atualizarEstatisticas(disciplinas = disciplinasOriginais) {
    const total = disciplinas.length;
    const totalCarga = disciplinas.reduce((sum, d) => sum + parseInt(d.carga_horaria_padrao), 0);
    
    document.getElementById('totalDisciplinas').textContent = total;
    document.getElementById('totalCargaHoraria').textContent = totalCarga;
}

// Função para limpar filtros
function limparFiltrosDisciplinas() {
    document.getElementById('buscarDisciplinas').value = '';
    document.getElementById('filtroStatus').value = '';
    document.getElementById('ordenarDisciplinas').value = 'nome';
    filtrarDisciplinas();
}

// Função para visualizar disciplina (placeholder)
function visualizarDisciplina(id) {
    console.log('Visualizar disciplina:', id);
    // Implementar visualização detalhada se necessário
}

// Função para abrir modal de nova disciplina
function abrirModalNovaDisciplina() {
    const modal = new bootstrap.Modal(document.getElementById('modalNovaDisciplina'));
    modal.show();
    
    // Limpar formulário
    document.getElementById('formNovaDisciplina').reset();
}

// Função para editar disciplina
function editarDisciplina(id) {
    // Buscar dados da disciplina
    fetch('/cfc-bom-conselho/admin/api/disciplinas-clean.php?acao=listar')
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                const disciplina = data.disciplinas.find(d => d.id == id);
                if (disciplina) {
                    // Preencher formulário de edição
                    document.getElementById('edit_id').value = disciplina.id;
                    document.getElementById('edit_codigo').value = disciplina.codigo;
                    document.getElementById('edit_nome').value = disciplina.nome;
                    document.getElementById('edit_descricao').value = disciplina.descricao || '';
                    document.getElementById('edit_carga_horaria_padrao').value = disciplina.carga_horaria_padrao;
                    document.getElementById('edit_cor_hex').value = disciplina.cor_hex;
                    document.getElementById('edit_icone').value = disciplina.icone;
                    
                    // Abrir modal
                    const modal = new bootstrap.Modal(document.getElementById('modalEditarDisciplina'));
                    modal.show();
                } else {
                    showAlert('Disciplina não encontrada', 'danger');
                }
            }
        })
        .catch(error => {
            console.error('Erro ao buscar disciplina:', error);
            showAlert('Erro ao buscar dados da disciplina', 'danger');
        });
}

// Função para excluir disciplina
function excluirDisciplina(id) {
    // Buscar dados da disciplina para exibir no modal de confirmação
    fetch('/cfc-bom-conselho/admin/api/disciplinas-clean.php?acao=listar')
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                const disciplina = data.disciplinas.find(d => d.id == id);
                if (disciplina) {
                    // Preencher detalhes no modal de confirmação
                    document.getElementById('detalhesDisciplinaExclusao').innerHTML = `
                        <div class="alert alert-warning">
                            <strong>Disciplina:</strong> ${disciplina.nome}<br>
                            <strong>Código:</strong> ${disciplina.codigo}
                        </div>
                    `;
                    
                    // Armazenar ID para exclusão
                    document.getElementById('confirmarExclusaoDisciplina').onclick = function() {
                        confirmarExclusaoDisciplina(id);
                    };
                    
                    // Abrir modal
                    const modal = new bootstrap.Modal(document.getElementById('modalConfirmarExclusaoDisciplina'));
                    modal.show();
                } else {
                    showAlert('Disciplina não encontrada', 'danger');
                }
            }
        })
        .catch(error => {
            console.error('Erro ao buscar disciplina:', error);
            showAlert('Erro ao buscar dados da disciplina', 'danger');
        });
}

// Função para confirmar exclusão de disciplina
function confirmarExclusaoDisciplina(id) {
    const formData = new FormData();
    formData.append('acao', 'excluir');
    formData.append('id', id);
    
    fetch('/cfc-bom-conselho/admin/api/disciplinas-clean.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            showAlert(data.mensagem, 'success');
            recarregarDisciplinas();
            
            // Fechar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalConfirmarExclusaoDisciplina'));
            modal.hide();
        } else {
            showAlert('Erro: ' + data.mensagem, 'danger');
        }
    })
    .catch(error => {
        console.error('Erro ao excluir disciplina:', error);
        showAlert('Erro ao excluir disciplina: ' + error.message, 'danger');
    });
}

// Event listener para formulário de nova disciplina
document.getElementById('formNovaDisciplina').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('acao', 'criar');
    
    fetch('/cfc-bom-conselho/admin/api/disciplinas-clean.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            showAlert(data.mensagem, 'success');
            recarregarDisciplinas();
            
            // Fechar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalNovaDisciplina'));
            modal.hide();
            
            // Limpar formulário
            this.reset();
        } else {
            showAlert('Erro: ' + data.mensagem, 'danger');
        }
    })
    .catch(error => {
        console.error('Erro ao criar disciplina:', error);
        showAlert('Erro ao criar disciplina: ' + error.message, 'danger');
    });
});

// Event listener para formulário de editar disciplina
document.getElementById('formEditarDisciplina').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('acao', 'editar');
    
    fetch('/cfc-bom-conselho/admin/api/disciplinas-clean.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            showAlert(data.mensagem, 'success');
            recarregarDisciplinas();
            
            // Fechar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarDisciplina'));
            modal.hide();
        } else {
            showAlert('Erro: ' + data.mensagem, 'danger');
        }
    })
    .catch(error => {
        console.error('Erro ao editar disciplina:', error);
        showAlert('Erro ao editar disciplina: ' + error.message, 'danger');
    });
});

// ==========================================
// FUNÇÕES PARA NAVEGAÇÃO ENTRE ETAPAS
// ==========================================

/**
 * Navegar para uma etapa específica
 * @param {number} etapa - Número da etapa (1, 2, 3, 4)
 */
function navegarParaEtapa(etapa) {
    // Verificar se há turma_id na URL
    const urlParams = new URLSearchParams(window.location.search);
    const turmaId = urlParams.get('turma_id');
    const acao = urlParams.get('acao');
    
    if (!turmaId && etapa > 1) {
        // Se não há turma_id e está tentando ir para etapa > 1
        showAlert('warning', 'Você precisa criar uma turma primeiro antes de navegar para outras etapas.');
        return;
    }
    
    // Determinar a ação baseada na etapa
    let novaAcao = '';
    switch(etapa) {
        case 1:
            novaAcao = 'nova';
            break;
        case 2:
            novaAcao = 'agendar';
            break;
        case 3:
            novaAcao = 'revisar';
            break;
        case 4:
            novaAcao = 'alunos';
            break;
        default:
            novaAcao = 'nova';
    }
    
    // Construir nova URL
    let novaUrl = `?page=turmas-teoricas&acao=${novaAcao}&step=${etapa}`;
    
    if (turmaId) {
        novaUrl += `&turma_id=${turmaId}`;
    }
    
    // Verificar se há dados não salvos antes de navegar
    if (etapa > 1 && !turmaId) {
        // Salvar rascunho antes de navegar se estiver na etapa 1
        salvarRascunho().then(() => {
            window.location.href = novaUrl;
        }).catch(() => {
            // Se não conseguir salvar, navegar mesmo assim
            window.location.href = novaUrl;
        });
    } else {
        // Navegar diretamente
        window.location.href = novaUrl;
    }
}

/**
 * Verificar se a etapa pode ser acessada
 * @param {number} etapa - Número da etapa
 * @returns {boolean} - Se a etapa pode ser acessada
 */
function podeAcessarEtapa(etapa) {
    const urlParams = new URLSearchParams(window.location.search);
    const turmaId = urlParams.get('turma_id');
    
    // Etapa 1 sempre pode ser acessada
    if (etapa === 1) {
        return true;
    }
    
    // Outras etapas precisam de turma_id
    return !!turmaId;
}

/**
 * Atualizar estado dos botões de navegação
 */
function atualizarNavegacao() {
    const urlParams = new URLSearchParams(window.location.search);
    const stepAtual = parseInt(urlParams.get('step') || '1');
    
    // Atualizar classes dos botões
    for (let i = 1; i <= 4; i++) {
        const botao = document.querySelector(`button[onclick="navegarParaEtapa(${i})"]`);
        if (botao) {
            // Remover classes antigas
            botao.classList.remove('active', 'completed');
            
            if (i === stepAtual) {
                botao.classList.add('active');
            } else if (i < stepAtual) {
                botao.classList.add('completed');
            }
            
            // Habilitar/desabilitar botão baseado na disponibilidade
            if (podeAcessarEtapa(i)) {
                botao.disabled = false;
                botao.style.opacity = '1';
            } else {
                botao.disabled = true;
                botao.style.opacity = '0.5';
            }
        }
    }
}

// Atualizar navegação quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    atualizarNavegacao();
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
