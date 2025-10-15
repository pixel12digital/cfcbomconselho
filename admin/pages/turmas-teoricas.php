<?php
/**
 * Página Principal de Gestão de Turmas Teóricas
 * Sistema com wizard em 4 etapas
 * 
 * @author Sistema CFC Bom Conselho
 * @version 1.0
 * @since 2024
 */

// Definir caminho base
$base_path = dirname(dirname(__DIR__));

// Forçar charset UTF-8 para evitar problemas de codificação
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}

// Incluir arquivos necessários
require_once $base_path . '/includes/config.php';
require_once $base_path . '/includes/database.php';
require_once $base_path . '/includes/auth.php';

// Verificar se o usuário está logado e tem permissão de admin ou instrutor
if (!isLoggedIn() || (!hasPermission('admin') && !hasPermission('instrutor'))) {
    echo '<div class="alert alert-danger">Você não tem permissão para acessar esta página.</div>';
    return;
}

// Obter dados do usuário logado e verificar permissões
$user = getCurrentUser();
$userType = $user['tipo'] ?? 'admin';
$userId = $user['id'] ?? null;
$isAdmin = hasPermission('admin');
$isInstrutor = hasPermission('instrutor');

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

// Processar ativação de turma
if ($acao === 'ativar_turma' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $turmaIdAtivar = $_POST['turma_id'] ?? $turmaId;
    
    if ($turmaIdAtivar) {
        $resultado = $turmaManager->ativarTurma($turmaIdAtivar);
        
        if ($resultado['sucesso']) {
            $sucesso = $resultado['mensagem'];
            // Recarregar dados da turma após ativação
            $resultadoTurma = $turmaManager->obterTurma($turmaIdAtivar);
            if ($resultadoTurma['sucesso']) {
                $turmaAtual = $resultadoTurma['dados'];
            }
        } else {
            $erro = $resultado['mensagem'];
        }
    } else {
        $erro = 'ID da turma é obrigatório para ativação';
    }
}

// Processar edição de turma
if ($acao === 'editar_turma' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dadosTurma = [
        'nome' => $_POST['nome'] ?? '',
        'sala_id' => $_POST['sala_id'] ?? '',
        'curso_tipo' => $_POST['curso_tipo'] ?? '',
        'modalidade' => $_POST['modalidade'] ?? 'presencial',
        'data_inicio' => $_POST['data_inicio'] ?? '',
        'data_fim' => $_POST['data_fim'] ?? '',
        'observacoes' => $_POST['observacoes'] ?? '',
        'max_alunos' => $_POST['max_alunos'] ?? 30,
    ];
    
    $resultado = $turmaManager->atualizarTurma($turmaId, $dadosTurma);
    
    if ($resultado['sucesso']) {
        $sucesso = $resultado['mensagem'];
        // Recarregar dados da turma após edição
        $resultadoTurma = $turmaManager->obterTurma($turmaId);
        if ($resultadoTurma['sucesso']) {
            $turmaAtual = $resultadoTurma['dados'];
        }
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
        
        // Se a ação é "ativar", garantir que estamos na etapa 1 para mostrar os dados
        if ($acao === 'ativar') {
            $step = '1'; // Forçar para etapa 1 para mostrar os dados básicos
        }
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
    pointer-events: auto;
    z-index: 10;
    position: relative;
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
            
        <?php elseif ($step === '1' || $acao === 'nova' || $acao === 'ativar' || $acao === 'editar'): ?>
            <!-- STEP 1: CRIAÇÃO BÁSICA -->
            <form method="POST" action="?page=turmas-teoricas">
                <?php if ($acao === 'ativar'): ?>
                    <input type="hidden" name="acao" value="ativar_turma">
                    <input type="hidden" name="turma_id" value="<?= $turmaId ?>">
                <?php elseif ($acao === 'editar'): ?>
                    <input type="hidden" name="acao" value="editar_turma">
                    <input type="hidden" name="turma_id" value="<?= $turmaId ?>">
                <?php else: ?>
                    <input type="hidden" name="acao" value="criar_basica">
                    <input type="hidden" name="step" value="1">
                <?php endif; ?>
                
                <div class="form-section">
                    <h4>📝 Informações Básicas da Turma</h4>
                    
                    <div class="form-group">
                        <label for="nome">Nome da Turma *</label>
                        <input type="text" 
                               id="nome" 
                               name="nome" 
                               class="form-control" 
                               placeholder="Ex: Turma A - Formação CNH B"
                               value="<?= ($acao === 'ativar' || $acao === 'editar') && $turmaAtual ? htmlspecialchars($turmaAtual['nome']) : '' ?>"
                               <?= $acao === 'ativar' ? 'readonly' : '' ?>
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="sala_id">Sala *</label>
                        <div class="d-flex gap-2">
                            <select id="sala_id" name="sala_id" class="form-control" <?= $acao === 'ativar' ? 'disabled' : '' ?> required>
                                <option value="">Selecione uma sala...</option>
                                <?php foreach ($salasDisponiveis as $sala): ?>
                                    <option value="<?= $sala['id'] ?>" <?= (($acao === 'ativar' || $acao === 'editar') && $turmaAtual && $turmaAtual['sala_id'] == $sala['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sala['nome']) ?> 
                                        (Capacidade: <?= $sala['capacidade'] ?> alunos)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($acao === 'ativar'): ?>
                                <input type="hidden" name="sala_id" value="<?= $turmaAtual['sala_id'] ?? '' ?>">
                            <?php endif; ?>
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
                            <select id="curso_tipo" name="curso_tipo" class="form-control" <?= $acao === 'ativar' ? 'disabled' : '' ?> required>
                                <option value="">Selecione o tipo de curso...</option>
                                <?php foreach ($cursosDisponiveis as $key => $nome): ?>
                                    <option value="<?= $key ?>" <?= (($acao === 'ativar' || $acao === 'editar') && $turmaAtual && $turmaAtual['curso_tipo'] == $key) ? 'selected' : '' ?>><?= htmlspecialchars($nome) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($acao === 'ativar'): ?>
                                <input type="hidden" name="curso_tipo" value="<?= $turmaAtual['curso_tipo'] ?? '' ?>">
                            <?php endif; ?>
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
                        <label>
                            <i class="fas fa-book me-1"></i>Disciplinas do Curso
                            <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="abrirModalDisciplinasInterno()" title="Gerenciar Disciplinas">
                                <i class="fas fa-cog"></i>
                            </button>
                        </label>
                        <div class="mb-2">
                            <!-- Campo fixo de disciplina -->
                            <div class="disciplina-item border rounded p-3 mb-3" data-disciplina-id="0">
                                <div class="d-flex align-items-center gap-3 disciplina-row-layout">
                                    <div class="flex-grow-1 disciplina-field-container">
                                        <select class="form-select" name="disciplina_0" id="disciplina_principal" onchange="atualizarDisciplina(0)">
                                            <option value="">Selecione a disciplina...</option>
                                        </select>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <button type="button" class="btn btn-outline-danger btn-sm disciplina-delete-btn" onclick="removerDisciplina(0)" title="Remover disciplina" style="display: none;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Campos ocultos para informações adicionais -->
                                <div style="display: none;">
                                    <div class="input-group">
                                        <input type="number" class="form-control disciplina-horas" 
                                               name="disciplina_horas_0" 
                                               placeholder="Horas" 
                                               min="1" 
                                               max="50"
                                               onchange="atualizarPreview()">
                                        <span class="input-group-text">h</span>
                                    </div>
                                    <small class="text-muted disciplina-info">
                                        <span class="aulas-obrigatorias"></span> aulas (padrão)
                                    </small>
                                </div>
                            </div>
                            
                            <!-- Container para disciplinas adicionais -->
                            <div id="disciplinas-container">
                                <!-- Disciplinas adicionais serão carregadas aqui -->
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
                    <?php if ($acao === 'ativar'): ?>
                        <a href="?page=turmas-teoricas" class="btn-secondary">
                            ← Voltar à Lista
                        </a>
                        <button type="submit" class="btn-primary">
                            🎯 Ativar Turma
                        </button>
                    <?php elseif ($acao === 'editar'): ?>
                        <a href="?page=turmas-teoricas" class="btn-secondary">
                            ← Voltar à Lista
                        </a>
                        <button type="submit" class="btn-primary">
                            💾 Salvar Alterações
                        </button>
                    <?php else: ?>
                        <a href="?page=turmas-teoricas" class="btn-secondary">
                            ← Cancelar
                        </a>
                        <button type="submit" class="btn-primary">
                            Próxima Etapa: Agendamento →
                        </button>
                    <?php endif; ?>
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
            <div class="d-flex align-items-center gap-3 disciplina-row-layout">
                <div class="flex-grow-1 disciplina-field-container">
                    <select class="form-select" name="disciplina_${contadorDisciplinas}" onchange="atualizarDisciplina(${contadorDisciplinas})">
                        <option value="">Selecione a disciplina...</option>
                    </select>
                </div>
                <div class="flex-shrink-0">
                    <button type="button" class="btn btn-outline-danger btn-sm disciplina-delete-btn" onclick="removerDisciplina(${contadorDisciplinas})" title="Remover disciplina">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            
            <!-- Campos ocultos para informações adicionais -->
            <div style="display: none;">
                <div class="input-group">
                    <input type="number" class="form-control disciplina-horas" 
                           name="disciplina_horas_${contadorDisciplinas}" 
                           placeholder="Horas" 
                           min="1" 
                           max="50"
                           onchange="atualizarPreview()">
                    <span class="input-group-text">h</span>
                </div>
                <small class="text-muted disciplina-info">
                    <span class="aulas-obrigatorias"></span> aulas (padrão)
                </small>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', disciplinaHtml);
    
    // Aguardar um pouco para o DOM ser atualizado e depois carregar disciplinas
    setTimeout(() => {
        carregarDisciplinas(contadorDisciplinas);
        
        // Fallback: se não carregou, tentar novamente com dados globais
        setTimeout(() => {
            const select = document.querySelector(`select[name="disciplina_${contadorDisciplinas}"]`);
            if (select && select.options.length <= 1) {
                if (disciplinasDisponiveis && disciplinasDisponiveis.length > 0) {
                    // Converter dados globais para formato da API
                    const disciplinasFormatadas = disciplinasDisponiveis.map(d => ({
                        id: d.value,
                        nome: d.text,
                        carga_horaria_padrao: d.aulas,
                        cor_hex: d.cor
                    }));
                    carregarDisciplinasEmSelect(select, disciplinasFormatadas);
                } else {
                    carregarDisciplinas(contadorDisciplinas);
                }
            }
        }, 500);
    }, 100);
}

function carregarDisciplinas(disciplinaId) {
    const cursoSelect = document.getElementById('curso_tipo');
    const disciplinaSelect = document.querySelector(`select[name="disciplina_${disciplinaId}"]`);
    
    if (!cursoSelect || !disciplinaSelect) {
        console.warn(`⚠️ Elementos não encontrados para disciplina ${disciplinaId}`);
        return;
    }
    
    const cursoTipo = cursoSelect.value;
    
    // Limpar opções anteriores
    disciplinaSelect.innerHTML = '<option value="">Carregando disciplinas...</option>';
    
    // Carregar disciplinas diretamente da API
    fetch('/cfc-bom-conselho/admin/api/disciplinas-clean.php?acao=listar')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Erro na requisição:', text.substring(0, 200));
                    throw new Error('JSON inválido: ' + e.message);
                }
            });
        })
        .then(data => {
            if (data.sucesso && data.disciplinas) {
                // Limpar opções e adicionar placeholder
                disciplinaSelect.innerHTML = '<option value="">Selecione a disciplina...</option>';
                
                // Adicionar disciplinas disponíveis
                data.disciplinas.forEach(disciplina => {
                    const option = document.createElement('option');
                    option.value = disciplina.id;
                    option.textContent = disciplina.nome;
                    option.dataset.aulas = disciplina.carga_horaria_padrao || 10;
                    option.dataset.cor = '#007bff'; // Cor padrão
                    disciplinaSelect.appendChild(option);
                });
                
                console.log(`✅ Disciplinas carregadas para curso ${cursoTipo}:`, data.disciplinas.length);
                
                // Forçar atualização visual do select
                setTimeout(() => {
                    forcarAtualizacaoSelect(disciplinaSelect);
                    
                    // Se ainda não funcionou, recriar o select completamente
                    setTimeout(() => {
                        const newSelect = recriarSelect(disciplinaSelect);
                        if (newSelect) {
                            // Atualizar referência para o novo select
                            disciplinaSelect = newSelect;
                        }
                    }, 200);
                }, 100);
                
                // Atualizar variável global para compatibilidade
                disciplinasDisponiveis = data.disciplinas.map(d => ({
                    value: d.id,
                    text: d.nome,
                    aulas: d.carga_horaria_padrao || 10,
                    cor: '#007bff'
                }));
                
            } else {
                disciplinaSelect.innerHTML = '<option value="">Erro ao carregar disciplinas</option>';
                console.error('❌ Erro ao carregar disciplinas:', data.mensagem || 'Erro desconhecido');
            }
        })
        .catch(error => {
            disciplinaSelect.innerHTML = '<option value="">Erro ao carregar disciplinas</option>';
            console.error('❌ Erro na requisição de disciplinas:', error);
        });
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
        
        // Mostrar botão de excluir no campo fixo quando disciplina for selecionada
        if (disciplinaId === 0) {
            const deleteBtn = disciplinaSelect.closest('.disciplina-item').querySelector('.disciplina-delete-btn');
            if (deleteBtn) {
                deleteBtn.style.display = 'flex';
            }
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
        
        // Esconder botão de excluir no campo fixo quando disciplina for desmarcada
        if (disciplinaId === 0) {
            const deleteBtn = disciplinaSelect.closest('.disciplina-item').querySelector('.disciplina-delete-btn');
            if (deleteBtn) {
                deleteBtn.style.display = 'none';
            }
        }
        
        const disciplinaItem = disciplinaSelect.closest('.disciplina-item');
        disciplinaItem.style.borderLeft = '';
    }
}

function removerDisciplina(disciplinaId) {
    const disciplinaItem = document.querySelector(`[data-disciplina-id="${disciplinaId}"]`);
    if (disciplinaItem) {
        // Se for o campo fixo (ID 0), apenas limpar a seleção
        if (disciplinaId === 0) {
            const select = disciplinaItem.querySelector('select');
            if (select) {
                select.value = '';
                select.innerHTML = '<option value="">Selecione a disciplina...</option>';
            }
            console.log(`🗑️ Campo fixo de disciplina limpo`);
        } else {
            // Para disciplinas adicionais, remover o elemento
            disciplinaItem.remove();
            console.log(`🗑️ Disciplina ${disciplinaId} removida`);
        }
        atualizarPreview();
    }
}

function atualizarPreview() {
    // Incluir tanto o campo fixo quanto as disciplinas adicionais
    const disciplinas = document.querySelectorAll('.disciplina-item');
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

// Recarregar disciplinas quando curso mudar (segunda instância)
document.addEventListener('DOMContentLoaded', function() {
    console.log('📄 DOM carregado - segunda instância!');
    
    // Carregar disciplinas disponíveis imediatamente
    carregarDisciplinasDisponiveis();
    
    const cursoSelect = document.getElementById('curso_tipo');
    if (cursoSelect) {
        cursoSelect.addEventListener('change', function() {
            console.log('🎯 Curso selecionado (segunda instância):', this.value);
            
            // Limpar disciplinas existentes quando curso mudar
            const container = document.getElementById('disciplinas-container');
            if (container) {
                container.innerHTML = '';
                contadorDisciplinas = 0;
            }
            
            // Carregar disciplinas no campo fixo
            carregarDisciplinas(0);
        });
        
        // Se já houver um curso selecionado, carregar disciplinas
        if (cursoSelect.value) {
            console.log('🔄 Curso já selecionado (segunda instância), carregando disciplinas...');
            setTimeout(() => carregarDisciplinas(0), 500);
        } else {
            // Se não há curso selecionado, carregar disciplinas mesmo assim
            console.log('🔄 Nenhum curso selecionado (segunda instância), carregando disciplinas disponíveis...');
            setTimeout(() => carregarDisciplinas(0), 1500);
        }
    }
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<style>
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

/* ==========================================
   ESTILOS RESPONSIVOS PARA CAMPO DE DISCIPLINA
   ========================================== */

/* Layout flexível para o campo de disciplina */
.disciplina-row-layout {
    width: 100%;
    min-height: 48px;
}

.disciplina-field-container {
    min-width: 0; /* Permite que o campo encolha */
}

.disciplina-field-container .form-select {
    width: 100%;
    min-height: 48px;
    font-size: 1rem;
    padding: 0.75rem 1rem;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.disciplina-field-container .form-select:focus {
    border-color: #023A8D;
    box-shadow: 0 0 0 0.2rem rgba(2, 58, 141, 0.25);
}

.disciplina-delete-btn {
    min-width: 48px;
    height: 48px;
    padding: 0.5rem;
    border-radius: 0.375rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.15s ease-in-out;
}

.disciplina-delete-btn:hover {
    background-color: #dc3545;
    border-color: #dc3545;
    color: white;
    transform: scale(1.05);
}

.disciplina-delete-btn:active {
    transform: scale(0.95);
}

/* Responsividade para mobile */
@media (max-width: 767.98px) {
    .disciplina-row-layout {
        gap: 0.75rem !important;
        flex-direction: column;
        align-items: stretch !important;
    }
    
    .disciplina-field-container {
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    .disciplina-delete-btn {
        align-self: flex-end;
        min-width: 44px;
        height: 44px;
    }
    
    .disciplina-field-container .form-select {
        min-height: 44px;
        font-size: 16px; /* Evita zoom no iOS */
    }
}

/* Responsividade para tablet */
@media (min-width: 768px) and (max-width: 991.98px) {
    .disciplina-row-layout {
        gap: 1rem;
    }
    
    .disciplina-delete-btn {
        min-width: 46px;
        height: 46px;
    }
    
    .disciplina-field-container .form-select {
        min-height: 46px;
    }
}

/* Responsividade para desktop */
@media (min-width: 992px) {
    .disciplina-row-layout {
        gap: 1.25rem;
    }
    
    .disciplina-delete-btn {
        min-width: 48px;
        height: 48px;
    }
    
    .disciplina-field-container .form-select {
        min-height: 48px;
        font-size: 1rem;
    }
}

/* Melhorias visuais para o campo de disciplina */
.disciplina-field-container .form-select {
    background-color: #fff;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m1 6 7 7 7-7'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 16px 12px;
    appearance: none;
}

.disciplina-field-container .form-select:focus {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23023A8D' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m1 6 7 7 7-7'/%3e%3c/svg%3e");
}

/* Animação suave para mudanças de estado */
.disciplina-item {
    transition: all 0.2s ease-in-out;
}

.disciplina-item:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transform: translateY(-1px);
}

/* ==========================================
   ESTILOS DO MODAL GERENCIAR DISCIPLINAS
   ========================================== */

/* Modal responsivo com largura otimizada */
#modalGerenciarDisciplinas {
    z-index: 1055;
}

#modalGerenciarDisciplinas .modal-dialog {
    max-width: 1200px;
    width: 90vw;
    height: 85vh;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
}

#modalGerenciarDisciplinas .modal-content {
    height: 100%;
    display: flex;
    flex-direction: column;
    border-radius: 15px;
    overflow: hidden;
}

#modalGerenciarDisciplinas .modal-header {
    flex-shrink: 0;
    border-bottom: 1px solid #dee2e6;
}

/* ÚNICO scroll fica aqui - corpo do modal */
#modalGerenciarDisciplinas .modal-body {
    flex: 1;
    overflow-y: auto;
    overscroll-behavior: contain;
    scrollbar-gutter: stable;
    padding: 1.5rem;
}

#modalGerenciarDisciplinas .modal-footer {
    flex-shrink: 0;
    border-top: 1px solid #dee2e6;
}

/* Responsividade para mobile */
@media (max-width: 768px) {
    #modalGerenciarDisciplinas .modal-dialog {
        width: 100vw;
        height: 100vh;
        max-width: none;
        margin: 0;
        border-radius: 0;
    }
    
    #modalGerenciarDisciplinas .modal-content {
        border-radius: 0;
        height: 100vh;
    }
    
    #modalGerenciarDisciplinas .modal-body {
        padding: 1rem;
    }
}

/* Grid responsivo para disciplinas - SEM scroll interno */
.disciplinas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
    gap: 1.5rem;
    overflow: visible !important;
    max-height: none !important;
    height: auto !important;
}

/* Garantir que nenhum wrapper interno tenha scroll */
#modalGerenciarDisciplinas .disciplinas-grid,
#modalGerenciarDisciplinas #listaDisciplinas,
#modalGerenciarDisciplinas .panel,
#modalGerenciarDisciplinas .cards-wrapper,
#modalGerenciarDisciplinas .disciplinas-panel,
#modalGerenciarDisciplinas .suas-disciplinas {
    overflow: visible !important;
    max-height: none !important;
    height: auto !important;
    box-shadow: none;
    border: 0;
    padding: 0;
}

@media (min-width: 1200px) {
    .disciplinas-grid {
        grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
        gap: 2rem;
    }
}

@media (min-width: 1920px) {
    .disciplinas-grid {
        grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
        gap: 2.5rem;
    }
}

#modalGerenciarDisciplinas .modal-content {
    height: auto !important;
    max-height: none !important;
    border-radius: 15px;
    border: none;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    background-color: white !important;
    position: relative !important;
    z-index: 1056 !important;
}

/* Garantir que o backdrop funcione */
#modalGerenciarDisciplinas::before {
    content: '' !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background-color: rgba(0, 0, 0, 0.5) !important;
    z-index: 1054 !important;
    display: none !important;
}

#modalGerenciarDisciplinas.show::before {
    display: block !important;
}

/* Garantir que nenhum wrapper interno tenha scroll */
#modalGerenciarDisciplinas .modal-body .panel,
#modalGerenciarDisciplinas .modal-body .cards-wrapper,
#modalGerenciarDisciplinas .modal-body .disciplinas-panel,
#modalGerenciarDisciplinas .modal-body .suas-disciplinas {
    overflow: visible !important;
    max-height: none !important;
    height: auto !important;
    box-shadow: none;
    border: 0;
    padding: 0;
}

/* Correção ULTRA ESPECÍFICA para remover qualquer scroll interno */
#modalGerenciarDisciplinas .modal-body * {
    overflow-x: visible !important;
}

#modalGerenciarDisciplinas .modal-body *:not(.modal-body) {
    overflow-y: visible !important;
}

/* ==========================================
   CORREÇÃO DEFINITIVA - SCROLL ÚNICO
   ========================================== */

/* Sistema de Modal Singleton - CORREÇÃO REAL */
#modal-root {
    position: relative;
    z-index: 10000;
}

#modal-root .modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    z-index: 9999;
    backdrop-filter: blur(2px);
}

#modal-root .modal-wrapper {
    position: fixed;
    inset: 0;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.25rem !important;
}

#modal-root .modal {
  width: min(95vw, 1300px);
  max-height: min(90vh, 900px);
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
  display: grid;
  grid-template-rows: auto 1fr auto;
  overflow: hidden;
  position: relative;
}

#modal-root .modal-header {
  padding: 0.5rem 0.375rem !important;
  border-bottom: 1px solid #e9ecef;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-shrink: 0;
}

/* ÚNICO scroll fica aqui - corpo do modal */
#modal-root .modal-content {
  overflow-y: auto !important;
  overscroll-behavior: contain;
  scrollbar-gutter: stable;
  padding: 0.5rem 0.375rem !important;
  flex: 1;
}

#modal-root .modal-footer {
  padding: 0.5rem 0.375rem !important;
  border-top: 1px solid #e9ecef;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
  flex-shrink: 0;
}

/* Remover QUALQUER scroll/limite dentro do content */
#modal-root .modal-content *{
  max-height: none !important;
}
#modal-root .disciplinas-panel,
#modal-root .cards-wrapper,
#modal-root .panel,
#modal-root .ps,                  /* PerfectScrollbar, se houver */
#modal-root [class*="overflow-"], /* utilitários de overflow */
#modal-root [class*="max-h"]{
  overflow: visible !important;
  height: auto !important;
  max-height: none !important;
  box-shadow: none; border: 0; padding: 0;
}

/* FORÇAR padding reduzido - sobrescrever qualquer outro estilo */
#modal-root .modal .modal-header,
#modal-root .modal .modal-content,
#modal-root .modal .modal-footer {
  padding: 0.5rem 0.375rem !important;
}

/* Garantir que elementos internos não tenham padding extra */
#modal-root .modal-content > * {
  margin-left: 0 !important;
  margin-right: 0 !important;
}

/* Responsividade - padding ainda menor em mobile */
@media (max-width: 768px) {
    #modal-root .modal-wrapper {
        padding: 0.125rem !important;
    }
    
    #modal-root .modal {
        width: 100vw;
        max-height: 100vh;
        border-radius: 0;
    }
    
    #modal-root .modal-header,
    #modal-root .modal-content,
    #modal-root .modal-footer {
        padding: 0.375rem 0.25rem !important;
    }
}

/* Desktop - Layout amplo e melhorado */
@media (min-width: 992px) {
    html body #modalGerenciarDisciplinas.show {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 !important;
    }
    
    html body #modalGerenciarDisciplinas .modal-dialog {
        margin: 0 !important;
        max-height: calc(100vh - 4rem) !important;
        position: relative !important;
        top: 0 !important;
        left: 0 !important;
        transform: none !important;
    }
    
    html body #modalGerenciarDisciplinas .modal-dialog,
    html body .modal#modalGerenciarDisciplinas .modal-dialog,
    html body .modal.fade#modalGerenciarDisciplinas .modal-dialog,
    html body .modal.show#modalGerenciarDisciplinas .modal-dialog,
    html body .modal.modal-disciplinas#modalGerenciarDisciplinas .modal-dialog {
        margin: 2rem auto !important;
        max-width: 95vw !important;
        width: 95vw !important;
        position: relative !important;
        left: 0 !important;
        right: auto !important;
        top: 0 !important;
        bottom: auto !important;
        transform: none !important;
        flex: none !important;
    }
    
    .disciplinas-grid {
        display: grid !important;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)) !important;
        gap: 2rem !important;
        padding: 1rem 0 !important;
        overflow: visible !important;
        max-height: none !important;
        height: auto !important;
    }
}

/* Melhorias nos cards das disciplinas */
.disciplina-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    border-radius: 15px !important;
    overflow: hidden !important;
    background: white !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
}

.disciplina-card:hover {
    transform: translateY(-4px) !important;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

.disciplina-card .card-header {
    border-radius: 15px 15px 0 0 !important;
    position: relative !important;
}

.disciplina-card .card-body {
    background: white !important;
}

.disciplina-card .card-footer {
    background: #fafbfc !important;
    border-radius: 0 0 15px 15px !important;
}

/* Animações suaves */
.disciplina-card * {
    transition: all 0.2s ease !important;
}

/* Melhorias nos botões */
.disciplina-card .btn {
    border-radius: 8px !important;
    font-weight: 500 !important;
    transition: all 0.2s ease !important;
}

.disciplina-card .btn:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
}

/* Badges melhorados */
.disciplina-card .badge {
    font-weight: 500 !important;
    letter-spacing: 0.5px !important;
}

/* Ícones melhorados */
.disciplina-card .fas {
    transition: all 0.2s ease !important;
}

.disciplina-card:hover .fas {
    transform: scale(1.1) !important;
}

/* Tablet */
@media (min-width: 768px) and (max-width: 991.98px) {
    html body #modalGerenciarDisciplinas .modal-dialog {
        margin: 2rem auto !important;
        max-width: calc(100vw - 4rem) !important;
        width: calc(100vw - 4rem) !important;
    }
    
    .disciplinas-grid {
        display: grid !important;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)) !important;
        gap: 1.25rem !important;
        overflow: visible !important;
        max-height: none !important;
        height: auto !important;
    }
}

/* Mobile */
@media (max-width: 767.98px) {
    html body #modalGerenciarDisciplinas .modal-dialog {
        margin: 10px !important;
        max-width: calc(100% - 20px) !important;
        width: calc(100% - 20px) !important;
    }
    
    #modalGerenciarDisciplinas .modal-body {
        padding: 20px !important;
    }
    
    #modalGerenciarDisciplinas .modal-header {
        padding: 20px !important;
    }
    
    #modalGerenciarDisciplinas .modal-footer {
        padding: 15px 20px !important;
    }
    
    .disciplinas-grid {
        display: grid !important;
        grid-template-columns: 1fr !important;
        gap: 1rem !important;
        overflow: visible !important;
        max-height: none !important;
        height: auto !important;
    }
    
    .disciplina-card {
        min-width: 100% !important;
        width: 100% !important;
    }
    
    #modalGerenciarDisciplinas .form-floating > .form-control,
    #modalGerenciarDisciplinas .form-floating > .form-select {
        height: calc(5rem + 2px) !important;
        padding: 1.5rem 0.75rem 1rem 0.75rem !important;
        font-size: 1rem !important;
    }
    
    #modalGerenciarDisciplinas .form-floating > label {
        padding: 1.5rem 0.75rem 0.5rem 0.75rem !important;
        font-size: 1rem !important;
        margin-bottom: 0.25rem !important;
    }
    
    #modalGerenciarDisciplinas .btn {
        padding: 0.5rem 1rem !important;
        font-size: 0.9rem !important;
    }
}

/* Campos floating - Corrigido para evitar corte de texto */
#modalGerenciarDisciplinas .form-floating {
    margin-bottom: 0.75rem !important;
}

#modalGerenciarDisciplinas .form-floating > .form-control,
#modalGerenciarDisciplinas .form-floating > .form-select {
    height: calc(6rem + 2px) !important;
    padding: 1.5rem 1rem 1.5rem 1rem !important;
    font-size: 1rem !important;
    line-height: 1.4 !important;
    display: flex !important;
    align-items: center !important;
    vertical-align: middle !important;
}

#modalGerenciarDisciplinas .form-floating > label {
    padding: 1.5rem 1rem 0.5rem 1rem !important;
    font-size: 0.9rem !important;
    margin-bottom: 0.5rem !important;
    transition: transform 0.2s ease-in-out !important;
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    z-index: 2 !important;
}

#modalGerenciarDisciplinas .form-floating > .form-control:focus ~ label,
#modalGerenciarDisciplinas .form-floating > .form-select:focus ~ label {
    transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem) !important;
    color: #007bff !important;
}

#modalGerenciarDisciplinas .form-floating > .form-control:not(:placeholder-shown) ~ label,
#modalGerenciarDisciplinas .form-floating > .form-select:not([value=""]) ~ label {
    transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem) !important;
    color: #6c757d !important;
}

/* Correção ULTRA ESPECÍFICA para campos proporcionais */
#modalGerenciarDisciplinas .form-floating > .form-select {
    padding: 1.2rem 1rem 0.5rem 1rem !important;
    text-align: left !important;
    vertical-align: middle !important;
    line-height: 1.3 !important;
    height: auto !important;
    min-height: 3.5rem !important;
    max-height: 4rem !important;
}

#modalGerenciarDisciplinas .form-floating > .form-control {
    padding: 1.2rem 1rem 0.5rem 1rem !important;
    text-align: left !important;
    vertical-align: middle !important;
    line-height: 1.3 !important;
    height: auto !important;
    min-height: 3.5rem !important;
    max-height: 4rem !important;
}

/* Forçar posicionamento do texto */
#modalGerenciarDisciplinas .form-floating > .form-select option {
    padding: 0.5rem !important;
    line-height: 1.4 !important;
}

/* Correção específica para o texto cortado */
#modalGerenciarDisciplinas .form-floating > .form-select:not([multiple]) {
    background-position: right 0.75rem center !important;
    background-size: 16px 12px !important;
    padding-right: 2.5rem !important;
}

/* Cards de disciplinas */
.disciplina-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 1.5rem;
    min-height: 240px;
    min-width: 320px;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.disciplina-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
    border-color: #007bff;
}

/* Card modificado */
.disciplina-card.disciplina-modificada {
    border-color: #ffc107;
    box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
}

/* Card nova disciplina */
.disciplina-card.disciplina-nova {
    border-color: #28a745;
    border-width: 2px !important;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    animation: pulse-green 2s infinite;
}

@keyframes pulse-green {
    0% {
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }
    50% {
        box-shadow: 0 0 0 0.4rem rgba(40, 167, 69, 0.4);
    }
    100% {
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }
}

/* Campos editáveis nos cards */
.disciplina-card .form-control {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 0.75rem;
    font-size: 0.95rem;
    width: 100%;
    min-height: 44px;
    line-height: 1.4;
}

.disciplina-card .form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.disciplina-card .form-control-sm {
    font-size: 0.85rem;
    padding: 0.5rem 0.75rem;
    min-height: 40px;
    width: 100%;
}

.disciplina-card textarea.form-control {
    resize: vertical;
    min-height: 88px;
    width: 100%;
    line-height: 1.4;
}

@media (max-width: 767.98px) {
    .disciplina-card {
        min-height: 200px;
        padding: 1rem;
        min-width: 100%;
    }
    
    .disciplina-card .form-control {
        min-height: 48px;
        font-size: 1rem;
    }
    
    .disciplina-card textarea.form-control {
        min-height: 100px;
    }
}

/* Estrutura do card */
.disciplina-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.disciplina-card-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: #212529;
    margin: 0;
    line-height: 1.4;
    margin-bottom: 0.5rem;
}

.disciplina-card-codigo {
    font-size: 0.85rem;
    color: #6c757d;
    font-family: 'Courier New', monospace;
    background: #f8f9fa;
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    margin-top: 0.5rem;
    width: 100%;
    line-height: 1.4;
}

.disciplina-card-actions {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.disciplina-card-menu {
    width: 36px;
    height: 36px;
    border: none;
    background: #f8f9fa;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-left: auto;
}

.disciplina-card-menu:hover {
    background: #e9ecef;
}

.disciplina-card-content {
    flex: 1;
}

.disciplina-card-stats {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.disciplina-card-aulas {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.9rem;
    font-weight: 500;
}

.disciplina-card-descricao {
    font-size: 0.85rem;
    color: #6c757d;
    line-height: 1.4;
}

/* Cores das disciplinas */
.disciplina-card[data-cor="green"] {
    border-left: 4px solid #28a745;
}

.disciplina-card[data-cor="green"] .disciplina-card-aulas {
    color: #28a745;
}

.disciplina-card[data-cor="red"] {
    border-left: 4px solid #dc3545;
}

.disciplina-card[data-cor="red"] .disciplina-card-aulas {
    color: #dc3545;
}

.disciplina-card[data-cor="blue"] {
    border-left: 4px solid #007bff;
}

.disciplina-card[data-cor="blue"] .disciplina-card-aulas {
    color: #007bff;
}

.disciplina-card[data-cor="orange"] {
    border-left: 4px solid #fd7e14;
}

.disciplina-card[data-cor="orange"] .disciplina-card-aulas {
    color: #fd7e14;
}

.disciplina-card[data-cor="purple"] {
    border-left: 4px solid #6f42c1;
}

.disciplina-card[data-cor="purple"] .disciplina-card-aulas {
    color: #6f42c1;
}

/* Chips de filtro */
.filtro-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #e9ecef;
    color: #495057;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    border: 1px solid #dee2e6;
    cursor: pointer;
    transition: all 0.2s ease;
}

.filtro-chip:hover {
    background: #dee2e6;
    border-color: #adb5bd;
}

.filtro-chip.active {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.filtro-chip .remove-chip {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: inherit;
    font-size: 0.7rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.filtro-chip .remove-chip:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* Estados do modal */
.modal-loading {
    opacity: 0.7;
    pointer-events: none;
}

/* Acessibilidade */
.btn-close:focus {
    box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.5);
}

/* Mobile optimizations */
@media (max-width: 767.98px) {
    .modal-header {
        padding: 1rem;
    }
    
    .modal-footer {
        padding: 1rem;
    }
    
    .modal-footer .btn {
        min-height: 44px;
        font-size: 1rem;
        padding: 0.75rem 1rem;
    }
    
    .disciplina-card {
        padding: 1rem;
    }
    
    .disciplina-card-title {
        font-size: 1rem;
    }
    
    .disciplina-card-aulas {
        font-size: 0.9rem;
    }
    
    .disciplina-card-descricao {
        font-size: 0.85rem;
    }
    
    .filtro-chip {
        font-size: 0.75rem;
        padding: 0.2rem 0.6rem;
    }
    
    /* Barra de busca/filtros colapsável no mobile */
    .mobile-filters-collapsible {
        display: none;
    }
    
    .mobile-filters-toggle {
        display: block;
        width: 100%;
        margin-bottom: 1rem;
    }
    
    .mobile-filters-collapsible.show {
        display: block;
    }
    
    /* Botões maiores no mobile */
    .btn {
        min-height: 44px;
        padding: 0.75rem 1rem;
    }
    
    .form-control, .form-select {
        min-height: 44px;
        font-size: 1rem;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .disciplina-card {
        background: #2d3748;
        border-color: #4a5568;
        color: #e2e8f0;
    }
    
    .disciplina-card-title {
        color: #f7fafc;
    }
    
    .disciplina-card-codigo {
        background: #4a5568;
        color: #cbd5e0;
    }
    
    .disciplina-card-menu {
        background: #4a5568;
        color: #cbd5e0;
    }
    
    .disciplina-card-menu:hover {
        background: #718096;
    }
    
    .filtro-chip {
        background: #4a5568;
        color: #cbd5e0;
        border-color: #718096;
    }
    
    .filtro-chip:hover {
        background: #718096;
    }
}
</style>

<script>
// ==========================================
// FUNÇÕES GLOBAIS PARA NAVEGAÇÃO
// ==========================================

/**
 * Navegar para uma etapa específica
 * @param {number} etapa - Número da etapa (1, 2, 3, 4)
 */
function navegarParaEtapa(etapa) {
    console.log('🎯 Navegando para etapa:', etapa);
    
    // Verificar se há turma_id na URL
    const urlParams = new URLSearchParams(window.location.search);
    const turmaId = urlParams.get('turma_id');
    const acao = urlParams.get('acao');
    
    console.log('📋 Parâmetros atuais:', { turmaId, acao, etapa });
    
    if (!turmaId && etapa > 1) {
        // Se não há turma_id e está tentando ir para etapa > 1
        console.log('⚠️ Tentativa de navegar para etapa', etapa, 'sem turma_id');
        showAlert('warning', 'Você precisa criar uma turma primeiro antes de navegar para outras etapas.');
        return;
    }
    
    // Determinar a ação baseada na etapa
    let novaAcao = '';
    switch(etapa) {
        case 1:
            // Se há turma_id, usar 'editar' para manter os dados, senão 'nova'
            novaAcao = turmaId ? 'editar' : 'nova';
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
            novaAcao = turmaId ? 'editar' : 'nova';
    }
    
    // Construir nova URL
    let novaUrl = `?page=turmas-teoricas&acao=${novaAcao}&step=${etapa}`;
    
    if (turmaId) {
        novaUrl += `&turma_id=${turmaId}`;
    }
    
    console.log('🔗 Navegando para:', novaUrl);
    
    // Navegar diretamente
    window.location.href = novaUrl;
}

/**
 * Função para exibir alertas
 */
function showAlert(type, message) {
    // Remover alertas existentes
    const existingAlerts = document.querySelectorAll('.alert-custom');
    existingAlerts.forEach(alert => alert.remove());
    
    // Criar novo alerta
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-custom alert-dismissible fade show`;
    alertDiv.style.position = 'fixed';
    alertDiv.style.top = '20px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '9999';
    alertDiv.style.minWidth = '300px';
    
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto-remover após 5 segundos
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Sistema de Disciplinas Dinâmicas (baseado na lógica do cadastro de alunos)
console.log('🚀 Sistema de disciplinas dinâmicas carregado! v3.0 - ' + new Date().toISOString());

// Verificar se as variáveis já foram declaradas para evitar conflitos
if (typeof contadorDisciplinas === 'undefined') {
    var contadorDisciplinas = 0;
}
if (typeof disciplinasDisponiveis === 'undefined') {
    var disciplinasDisponiveis = [];
}

// Carregar disciplinas do banco de dados
function carregarDisciplinasDisponiveis() {
    console.log('🔄 Carregando disciplinas disponíveis...');
    
    fetch('/cfc-bom-conselho/admin/api/disciplinas-clean.php?acao=listar')
        .then(response => {
            console.log('📡 Resposta da API recebida:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text().then(text => {
                console.log('📄 Texto da resposta:', text.substring(0, 500));
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('❌ Erro ao fazer parse do JSON:', text.substring(0, 200));
                    throw new Error('JSON inválido: ' + e.message);
                }
            });
        })
        .then(data => {
            console.log('📊 Dados recebidos:', data);
            if (data.sucesso && data.disciplinas) {
                disciplinasDisponiveis = data.disciplinas.map(d => ({
                    value: d.id,
                    text: d.nome,
                    aulas: d.carga_horaria_padrao || 10,
                    cor: '#007bff'
                }));
                atualizarContadorDisciplinas();
                console.log('✅ Disciplinas carregadas:', disciplinasDisponiveis.length);
                
                // Carregar disciplinas no campo fixo se um curso estiver selecionado
                const cursoSelect = document.getElementById('curso_tipo');
                if (cursoSelect && cursoSelect.value) {
                    carregarDisciplinas(0);
                }
            } else {
                console.error('❌ Erro ao carregar disciplinas:', data.mensagem || 'Erro desconhecido');
                disciplinasDisponiveis = [];
            }
        })
        .catch(error => {
            console.error('❌ Erro na requisição de disciplinas:', error);
            disciplinasDisponiveis = [];
        });
}

// Atualizar contador de disciplinas
function atualizarContadorDisciplinas() {
    const contador = document.getElementById('contador-disciplinas');
    if (contador) {
        contador.textContent = disciplinasDisponiveis.length;
    }
}

// Função para forçar atualização visual do select
function forcarAtualizacaoSelect(selectElement) {
    if (!selectElement) return;
    
    console.log('🔄 Forçando atualização visual do select...');
    
    // Método 1: Remover e recriar o select
    const parent = selectElement.parentNode;
    const newSelect = selectElement.cloneNode(true);
    
    // Método 2: Toggle display para forçar reflow
    selectElement.style.display = 'none';
    selectElement.offsetHeight; // Force reflow
    selectElement.style.display = 'block';
    
    // Método 3: Dispatch multiple events
    selectElement.dispatchEvent(new Event('change', { bubbles: true }));
    selectElement.dispatchEvent(new Event('input', { bubbles: true }));
    selectElement.dispatchEvent(new Event('focus', { bubbles: true }));
    selectElement.dispatchEvent(new Event('blur', { bubbles: true }));
    
    // Método 4: Force repaint with style changes
    selectElement.style.transform = 'translateZ(0)';
    setTimeout(() => {
        selectElement.style.transform = '';
    }, 100);
    
    console.log('✅ Atualização visual forçada aplicada');
}

// Função para recriar completamente o select
function recriarSelect(selectElement) {
    if (!selectElement) return;
    
    console.log('🔄 Recriando select completamente...');
    
    const parent = selectElement.parentNode;
    const name = selectElement.name;
    const id = selectElement.id;
    const className = selectElement.className;
    const options = Array.from(selectElement.options).map(option => ({
        value: option.value,
        text: option.textContent,
        selected: option.selected
    }));
    
    // Remover select antigo
    parent.removeChild(selectElement);
    
    // Criar novo select
    const newSelect = document.createElement('select');
    newSelect.name = name;
    newSelect.id = id;
    newSelect.className = className;
    newSelect.onchange = selectElement.onchange;
    
    // Adicionar options
    options.forEach(optionData => {
        const option = document.createElement('option');
        option.value = optionData.value;
        option.textContent = optionData.text;
        option.selected = optionData.selected;
        newSelect.appendChild(option);
    });
    
    // Inserir novo select
    parent.appendChild(newSelect);
    
    console.log('✅ Select recriado com sucesso');
    return newSelect;
}


function carregarDisciplinasNoSelect(disciplinas) {
    
    const select = document.querySelector('select[name="disciplina_0"]');
    if (!select) {
        console.error('❌ Select não encontrado');
        return;
    }
    
    
    // Limpar select
    select.innerHTML = '';
    
    // Adicionar opção padrão
    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = 'Selecione a disciplina...';
    select.appendChild(defaultOption);
    
    // Adicionar disciplinas
    disciplinas.forEach((disciplina, index) => {
        const option = document.createElement('option');
        option.value = disciplina.id;
        option.textContent = disciplina.nome;
        option.dataset.aulas = disciplina.carga_horaria_padrao || 10;
        option.dataset.cor = disciplina.cor_hex || '#007bff';
        select.appendChild(option);
    });
    
    
    // Forçar atualização visual
    select.style.display = 'none';
    select.offsetHeight;
    select.style.display = 'block';
    
}

function carregarDisciplinasEmSelect(selectElement, disciplinas) {
    if (!selectElement || !disciplinas) {
        console.error('❌ Select ou disciplinas não fornecidos');
        return;
    }
    
    
    // Limpar select
    selectElement.innerHTML = '';
    
    // Adicionar opção padrão
    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = 'Selecione a disciplina...';
    selectElement.appendChild(defaultOption);
    
    // Adicionar disciplinas
    disciplinas.forEach((disciplina, index) => {
        const option = document.createElement('option');
        option.value = disciplina.id;
        option.textContent = disciplina.nome;
        option.dataset.aulas = disciplina.carga_horaria_padrao || 10;
        option.dataset.cor = disciplina.cor_hex || '#007bff';
        selectElement.appendChild(option);
    });
    
    
    // Forçar atualização visual
    selectElement.style.display = 'none';
    selectElement.offsetHeight;
    selectElement.style.display = 'block';
}

function carregarDisciplinasEmTodosSelects() {
    
    // Buscar todas as disciplinas da API
    fetch('/cfc-bom-conselho/admin/api/disciplinas-clean.php?acao=listar')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('❌ Erro ao fazer parse do JSON:', e.message);
                    throw new Error('JSON inválido: ' + e.message);
                }
            });
        })
        .then(data => {
            if (data.sucesso && data.disciplinas) {
                
                // Encontrar todos os selects de disciplinas
                const selects = document.querySelectorAll('select[name^="disciplina_"]');
                
                selects.forEach((select, index) => {
                    carregarDisciplinasEmSelect(select, data.disciplinas);
                });
                
                
            } else {
                console.error('❌ Erro ao carregar disciplinas:', data.mensagem || 'Erro desconhecido');
            }
        })
        .catch(error => {
            console.error('❌ Erro na requisição:', error);
        });
}


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
            <div class="d-flex align-items-center gap-3 disciplina-row-layout">
                <div class="flex-grow-1 disciplina-field-container">
                    <select class="form-select" name="disciplina_${contadorDisciplinas}" onchange="atualizarDisciplina(${contadorDisciplinas})">
                        <option value="">Selecione a disciplina...</option>
                    </select>
                </div>
                <div class="flex-shrink-0">
                    <button type="button" class="btn btn-outline-danger btn-sm disciplina-delete-btn" onclick="removerDisciplina(${contadorDisciplinas})" title="Remover disciplina">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            
            <!-- Campos ocultos para informações adicionais -->
            <div style="display: none;">
                <div class="input-group">
                    <input type="number" class="form-control disciplina-horas" 
                           name="disciplina_horas_${contadorDisciplinas}" 
                           placeholder="Horas" 
                           min="1" 
                           max="50"
                           onchange="atualizarPreview()">
                    <span class="input-group-text">h</span>
                </div>
                <small class="text-muted disciplina-info">
                    <span class="aulas-obrigatorias"></span> aulas (padrão)
                </small>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', disciplinaHtml);
    
    // Aguardar um pouco para o DOM ser atualizado e depois carregar disciplinas
    setTimeout(() => {
        carregarDisciplinas(contadorDisciplinas);
        
        // Fallback: se não carregou, tentar novamente com dados globais
        setTimeout(() => {
            const select = document.querySelector(`select[name="disciplina_${contadorDisciplinas}"]`);
            if (select && select.options.length <= 1) {
                if (disciplinasDisponiveis && disciplinasDisponiveis.length > 0) {
                    // Converter dados globais para formato da API
                    const disciplinasFormatadas = disciplinasDisponiveis.map(d => ({
                        id: d.value,
                        nome: d.text,
                        carga_horaria_padrao: d.aulas,
                        cor_hex: d.cor
                    }));
                    carregarDisciplinasEmSelect(select, disciplinasFormatadas);
                } else {
                    carregarDisciplinas(contadorDisciplinas);
                }
            }
        }, 500);
    }, 100);
}

function carregarDisciplinas(disciplinaId) {
    const cursoSelect = document.getElementById('curso_tipo');
    const disciplinaSelect = document.querySelector(`select[name="disciplina_${disciplinaId}"]`);
    
    if (!cursoSelect || !disciplinaSelect) {
        console.warn(`⚠️ Elementos não encontrados para disciplina ${disciplinaId}`);
        return;
    }
    
    const cursoTipo = cursoSelect.value;
    
    // Limpar opções anteriores
    disciplinaSelect.innerHTML = '<option value="">Carregando disciplinas...</option>';
    
    // Carregar disciplinas diretamente da API
    fetch('/cfc-bom-conselho/admin/api/disciplinas-clean.php?acao=listar')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Erro na requisição:', text.substring(0, 200));
                    throw new Error('JSON inválido: ' + e.message);
                }
            });
        })
        .then(data => {
            if (data.sucesso && data.disciplinas) {
                // Limpar opções e adicionar placeholder
                disciplinaSelect.innerHTML = '<option value="">Selecione a disciplina...</option>';
                
                // Adicionar disciplinas disponíveis
                data.disciplinas.forEach(disciplina => {
                    const option = document.createElement('option');
                    option.value = disciplina.id;
                    option.textContent = disciplina.nome;
                    option.dataset.aulas = disciplina.carga_horaria_padrao || 10;
                    option.dataset.cor = '#007bff'; // Cor padrão
                    disciplinaSelect.appendChild(option);
                });
                
                console.log(`✅ Disciplinas carregadas para curso ${cursoTipo}:`, data.disciplinas.length);
                
                // Forçar atualização visual do select
                setTimeout(() => {
                    forcarAtualizacaoSelect(disciplinaSelect);
                    
                    // Se ainda não funcionou, recriar o select completamente
                    setTimeout(() => {
                        const newSelect = recriarSelect(disciplinaSelect);
                        if (newSelect) {
                            // Atualizar referência para o novo select
                            disciplinaSelect = newSelect;
                        }
                    }, 200);
                }, 100);
                
                // Atualizar variável global para compatibilidade
                disciplinasDisponiveis = data.disciplinas.map(d => ({
                    value: d.id,
                    text: d.nome,
                    aulas: d.carga_horaria_padrao || 10,
                    cor: '#007bff'
                }));
                
            } else {
                disciplinaSelect.innerHTML = '<option value="">Erro ao carregar disciplinas</option>';
                console.error('❌ Erro ao carregar disciplinas:', data.mensagem || 'Erro desconhecido');
            }
        })
        .catch(error => {
            disciplinaSelect.innerHTML = '<option value="">Erro ao carregar disciplinas</option>';
            console.error('❌ Erro na requisição de disciplinas:', error);
        });
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
        
        // Mostrar botão de excluir no campo fixo quando disciplina for selecionada
        if (disciplinaId === 0) {
            const deleteBtn = disciplinaSelect.closest('.disciplina-item').querySelector('.disciplina-delete-btn');
            if (deleteBtn) {
                deleteBtn.style.display = 'flex';
            }
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
        
        // Esconder botão de excluir no campo fixo quando disciplina for desmarcada
        if (disciplinaId === 0) {
            const deleteBtn = disciplinaSelect.closest('.disciplina-item').querySelector('.disciplina-delete-btn');
            if (deleteBtn) {
                deleteBtn.style.display = 'none';
            }
        }
        
        const disciplinaItem = disciplinaSelect.closest('.disciplina-item');
        disciplinaItem.style.borderLeft = '';
    }
    
    atualizarPreview();
}

function removerDisciplina(disciplinaId) {
    const disciplinaItem = document.querySelector(`[data-disciplina-id="${disciplinaId}"]`);
    if (disciplinaItem) {
        // Se for o campo fixo (ID 0), apenas limpar a seleção
        if (disciplinaId === 0) {
            const select = disciplinaItem.querySelector('select');
            if (select) {
                select.value = '';
                select.innerHTML = '<option value="">Selecione a disciplina...</option>';
            }
            console.log(`🗑️ Campo fixo de disciplina limpo`);
        } else {
            // Para disciplinas adicionais, remover o elemento
            disciplinaItem.remove();
            console.log(`🗑️ Disciplina ${disciplinaId} removida`);
        }
        atualizarPreview();
    }
}

function atualizarPreview() {
    // Incluir tanto o campo fixo quanto as disciplinas adicionais
    const disciplinas = document.querySelectorAll('.disciplina-item');
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
    
    // Carregar disciplinas disponíveis imediatamente
    carregarDisciplinasDisponiveis();
    
    // Carregar disciplinas diretamente no select principal
    carregarDisciplinasDoBanco();
    
    const cursoSelect = document.getElementById('curso_tipo');
    if (cursoSelect) {
        cursoSelect.addEventListener('change', function() {
            console.log('🎯 Curso selecionado:', this.value);
            
            // Limpar disciplinas existentes quando curso mudar
            const container = document.getElementById('disciplinas-container');
            if (container) {
                container.innerHTML = '';
                contadorDisciplinas = 0;
            }
            
            // Carregar disciplinas no campo fixo
            carregarDisciplinas(0);
        });
        
        // Se já houver um curso selecionado, carregar disciplinas
        if (cursoSelect.value) {
            console.log('🔄 Curso já selecionado, carregando disciplinas...');
            setTimeout(() => carregarDisciplinas(0), 500);
        } else {
            // Se não há curso selecionado, carregar disciplinas mesmo assim
            console.log('🔄 Nenhum curso selecionado, carregando disciplinas disponíveis...');
            setTimeout(() => carregarDisciplinas(0), 1000);
        }
    }
});

// Função para carregar disciplinas diretamente do banco
function carregarDisciplinasDoBanco() {
    console.log('🔄 Carregando disciplinas diretamente do banco...');
    
    fetch('/cfc-bom-conselho/admin/api/disciplinas-clean.php?acao=listar')
        .then(response => {
            console.log('📡 Resposta da API:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('❌ Erro ao fazer parse do JSON:', e.message);
                    throw new Error('JSON inválido: ' + e.message);
                }
            });
        })
        .then(data => {
            console.log('📊 Dados recebidos do banco:', data);
            if (data.sucesso && data.disciplinas) {
                console.log(`✅ ${data.disciplinas.length} disciplinas encontradas no banco`);
                
                // Carregar no select principal
                const select = document.querySelector('select[name="disciplina_0"]');
                if (select) {
                    carregarDisciplinasNoSelect(data.disciplinas);
                }
                
                // Atualizar variável global
                disciplinasDisponiveis = data.disciplinas.map(d => ({
                    value: d.id,
                    text: d.nome,
                    aulas: d.carga_horaria_padrao || 10,
                    cor: d.cor_hex || '#007bff'
                }));
                
                // Atualizar contador
                atualizarContadorDisciplinas();
                
            } else {
                console.error('❌ Erro ao carregar disciplinas do banco:', data.mensagem || 'Erro desconhecido');
            }
        })
        .catch(error => {
            console.error('❌ Erro na requisição do banco:', error);
        });
}
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


// Função para abrir modal de gerenciar disciplinas usando sistema singleton
function abrirModalDisciplinasInterno() {
    console.log('🔧 Abrindo modal de disciplinas com sistema singleton...');
    
    // Verificar se já existe modal aberto
    if (document.body.dataset.singletonModalOpen === '1') {
        console.log('⚠️ Modal singleton já está aberto, apenas atualizando conteúdo');
        return;
    }
    
    // Usar sistema singleton para abrir modal
    window.openModal(function() {
        const modal = criarModalDisciplinas();
        
        // Carregar disciplinas após o modal estar montado
        setTimeout(() => {
            carregarDisciplinas();
        }, 100);
        
        return modal;
    });
}

// Função para criar o HTML do modal de disciplinas
function criarModalDisciplinas() {
    const modal = document.createElement('div');
    modal.className = 'modal';
    
    modal.innerHTML = `
        <div class="modal-header" style="background: linear-gradient(135deg, #023A8D 0%, #1e5bb8 100%); color: white;">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <i class="fas fa-graduation-cap" style="font-size: 1.8rem; color: #F7931E;"></i>
                </div>
                <div>
                    <h5 class="modal-title mb-1 fw-bold">Gerenciar Disciplinas</h5>
                    <small class="opacity-75">Configure e organize as disciplinas do curso</small>
                </div>
            </div>
            <button type="button" class="modal-close text-white" onclick="window.closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-content">
            <!-- Barra de busca simplificada -->
            <div class="mb-4">
                <div class="position-relative">
                    <input type="text" class="form-control form-control-lg border-0 shadow-sm" id="buscarDisciplinas" 
                           placeholder="Buscar disciplinas..." onkeyup="filtrarDisciplinas()" 
                           style="padding-left: 3rem; background-color: white;">
                    <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                </div>
            </div>
            
            <!-- Estatística simplificada -->
            <div class="mb-4">
                <div class="d-flex align-items-center justify-content-center">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 2.5rem; height: 2.5rem; background: linear-gradient(135deg, #023A8D, #1e5bb8);">
                                <i class="fas fa-book text-white" style="font-size: 1rem;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 fw-bold text-dark">Total de Disciplinas: <span id="totalDisciplinas" class="text-primary">0</span></h6>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Botão Nova Disciplina -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="mb-1 fw-semibold text-dark">Suas Disciplinas</h6>
                    <small class="text-muted">Gerencie e organize as disciplinas do curso</small>
                </div>
                <button type="button" class="btn btn-primary btn-lg shadow-sm" onclick="abrirModalNovaDisciplina()" 
                        style="background: linear-gradient(135deg, #023A8D 0%, #1e5bb8 100%); border: none; border-radius: 10px;">
                    <i class="fas fa-plus me-2"></i>Nova Disciplina
                </button>
            </div>
            
                       <!-- Grid de disciplinas -->
                       <div id="listaDisciplinas" class="disciplinas-grid">
                           <!-- Lista de disciplinas será carregada aqui -->
                       </div>
            
            <!-- Estados -->
            <div id="carregandoDisciplinas" class="text-center py-5" style="display: none;">
                <div class="d-flex flex-column align-items-center">
                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <h6 class="text-primary mb-2">Carregando disciplinas...</h6>
                    <p class="text-muted mb-0">Aguarde enquanto buscamos suas disciplinas</p>
                </div>
            </div>
            
            <div id="nenhumaDisciplinaEncontrada" class="text-center py-5" style="display: none;">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-5">
                        <div class="mb-4">
                            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto" 
                                 style="width: 5rem; height: 5rem; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                                <i class="fas fa-book-open text-muted" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <h5 class="text-dark mb-3">Nenhuma disciplina encontrada</h5>
                        <p class="text-muted mb-4">Crie sua primeira disciplina ou ajuste os filtros de busca para encontrar o que procura.</p>
                        <div class="d-flex gap-2 justify-content-center">
                            <button type="button" class="btn btn-primary" onclick="abrirModalNovaDisciplina()">
                                <i class="fas fa-plus me-2"></i>Criar Disciplina
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="limparFiltrosDisciplinas()">
                                <i class="fas fa-times me-2"></i>Limpar Filtros
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="erroCarregarDisciplinas" class="text-center py-5" style="display: none;">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-5">
                        <div class="mb-4">
                            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto" 
                                 style="width: 5rem; height: 5rem; background: linear-gradient(135deg, #ffe6e6 0%, #ffcccc 100%);">
                                <i class="fas fa-exclamation-triangle text-danger" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <h5 class="text-danger mb-3">Erro ao carregar disciplinas</h5>
                        <p class="text-muted mb-4">Ocorreu um erro inesperado. Verifique sua conexão e tente novamente.</p>
                        <button type="button" class="btn btn-outline-danger" onclick="carregarDisciplinas()">
                            <i class="fas fa-redo me-2"></i>Tentar Novamente
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <div class="d-flex justify-content-between align-items-center w-100">
                <div class="d-flex align-items-center">
                    <small class="text-muted me-3">
                        <i class="fas fa-info-circle me-1"></i>
                        As alterações são salvas automaticamente
                    </small>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.closeModal()">
                        <i class="fas fa-times me-1"></i>Fechar
                    </button>
                    <button type="button" class="btn btn-primary shadow-sm" onclick="console.log('🔍 Botão Salvar Alterações clicado'); salvarAlteracoesDisciplinas();" 
                            style="background: linear-gradient(135deg, #023A8D 0%, #1e5bb8 100%); border: none; border-radius: 8px;">
                        <i class="fas fa-save me-1"></i>Salvar Alterações
                    </button>
                </div>
            </div>
        </div>
    `;
    
    return modal;
}

// Funções auxiliares para o modal de disciplinas
function filtrarDisciplinas() {
    const modalRoot = document.querySelector('#modal-root .modal');
    if (!modalRoot) return;
    
    const termoBusca = modalRoot.querySelector('#buscarDisciplinas')?.value.toLowerCase() || '';
    const statusFiltro = modalRoot.querySelector('#filtroStatus')?.value || '';
    const ordenacao = modalRoot.querySelector('#ordenarDisciplinas')?.value || 'nome';
    
    // Implementar filtros aqui
    console.log('🔍 Filtrando disciplinas:', { termoBusca, statusFiltro, ordenacao });
}

function limparFiltrosDisciplinas() {
    const modalRoot = document.querySelector('#modal-root .modal');
    if (!modalRoot) return;
    
    const buscarInput = modalRoot.querySelector('#buscarDisciplinas');
    const statusSelect = modalRoot.querySelector('#filtroStatus');
    const ordenarSelect = modalRoot.querySelector('#ordenarDisciplinas');
    
    if (buscarInput) buscarInput.value = '';
    if (statusSelect) statusSelect.value = '';
    if (ordenarSelect) ordenarSelect.value = 'nome';
    
    filtrarDisciplinas();
}

function abrirModalNovaDisciplina() {
    console.log('➕ Criando nova disciplina dentro do modal...');
    
    // Criar um novo card de disciplina em branco
    const container = document.getElementById('listaDisciplinas');
    if (!container) {
        console.error('❌ Container de disciplinas não encontrado');
        return;
    }
    
    // Gerar ID temporário único
    const novoId = 'temp_' + Date.now();
    
    // Criar HTML do novo card
    const novoCardHtml = `
        <div class="disciplina-card disciplina-nova" data-id="${novoId}" style="border-color: #28a745; border-width: 2px;">
            <!-- Menu kebab acima do campo -->
            <div class="dropdown mb-2">
                <button class="disciplina-card-menu" data-bs-toggle="dropdown">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#" onclick="salvarNovaDisciplina('${novoId}')">
                        <i class="fas fa-save me-2"></i>Salvar
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="cancelarNovaDisciplina('${novoId}')">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </a></li>
                </ul>
            </div>
            
            <!-- Título -->
            <input type="text" class="form-control disciplina-nome-editavel mb-3" 
                   value="" 
                   data-field="nome" 
                   data-id="${novoId}"
                   placeholder="Nome da disciplina"
                   style="border-color: #28a745;">
            
            <!-- Slug -->
            <input type="text" class="form-control form-control-sm disciplina-codigo-editavel mb-3" 
                   value="" 
                   data-field="codigo" 
                   data-id="${novoId}"
                   placeholder="Código da disciplina"
                   style="border-color: #28a745;">
            
            <!-- Linha: aulas + status -->
            <div class="disciplina-card-stats">
                <div class="disciplina-card-aulas">
                    <span class="inline-block w-2.5 h-2.5 rounded-full" style="background-color: #28a745;"></span>
                    <input type="number" class="form-control form-control-sm disciplina-aulas-editavel d-inline-block" 
                           value="10" 
                           data-field="carga_horaria_padrao" 
                           data-id="${novoId}"
                           style="width: 80px; display: inline-block !important; border-color: #28a745;"
                           min="0" max="999">
                    <span class="ms-1">aulas</span>
                </div>
                <span class="badge bg-success">NOVA</span>
            </div>
            
            <!-- Descrição -->
            <textarea class="form-control disciplina-descricao-editavel" 
                      data-field="descricao" 
                      data-id="${novoId}"
                      rows="3" 
                      placeholder="Descrição da disciplina"
                      style="border-color: #28a745;"></textarea>
        </div>
    `;
    
    // Adicionar o novo card no início da lista
    container.insertAdjacentHTML('afterbegin', novoCardHtml);
    
    // Focar no campo de nome
    const nomeInput = container.querySelector(`input[data-id="${novoId}"]`);
    if (nomeInput) {
        nomeInput.focus();
        nomeInput.select();
    }
    
    // Atualizar contador
    atualizarContadorDisciplinas();
    
    console.log('✅ Nova disciplina criada com sucesso');
}

function salvarNovaDisciplina(disciplinaId) {
    console.log('💾 Salvando nova disciplina:', disciplinaId);
    
    const card = document.querySelector(`[data-id="${disciplinaId}"]`);
    if (!card) {
        console.error('❌ Card não encontrado');
        return;
    }
    
    // Coletar dados do formulário
    const nome = card.querySelector('input[data-field="nome"]').value.trim();
    const codigo = card.querySelector('input[data-field="codigo"]').value.trim();
    const cargaHoraria = card.querySelector('input[data-field="carga_horaria_padrao"]').value;
    const descricao = card.querySelector('textarea[data-field="descricao"]').value.trim();
    
    // Validações básicas
    if (!nome) {
        alert('Por favor, preencha o nome da disciplina.');
        card.querySelector('input[data-field="nome"]').focus();
        return;
    }
    
    if (!codigo) {
        alert('Por favor, preencha o código da disciplina.');
        card.querySelector('input[data-field="codigo"]').focus();
        return;
    }
    
    // Preparar dados para envio
    const formData = new FormData();
    formData.append('acao', 'criar');
    formData.append('nome', nome);
    formData.append('codigo', codigo);
    formData.append('carga_horaria_padrao', cargaHoraria);
    formData.append('descricao', descricao);
    formData.append('ativa', '1');
    formData.append('cor_hex', '#28a745');
    
    // Enviar para API
    fetch('/cfc-bom-conselho/admin/api/disciplinas-clean.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            console.log('✅ Disciplina criada com sucesso:', data);
            
            // Remover classe de nova disciplina
            card.classList.remove('disciplina-nova');
            card.style.borderColor = '';
            card.style.borderWidth = '';
            
            // Atualizar ID temporário para o ID real
            card.setAttribute('data-id', data.disciplina.id);
            
            // Atualizar menu com opções normais
            const menu = card.querySelector('.dropdown-menu');
            menu.innerHTML = `
                <li><a class="dropdown-item" href="#" onclick="salvarDisciplina(${data.disciplina.id})">
                    <i class="fas fa-save me-2"></i>Salvar
                </a></li>
                <li><a class="dropdown-item" href="#" onclick="duplicarDisciplina(${data.disciplina.id})">
                    <i class="fas fa-copy me-2"></i>Duplicar
                </a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="#" onclick="confirmarExclusaoDisciplina(${data.disciplina.id})">
                    <i class="fas fa-trash me-2"></i>Excluir
                </a></li>
            `;
            
            // Atualizar badge para "ATIVA"
            const badge = card.querySelector('.badge');
            badge.textContent = 'ATIVA';
            badge.className = 'badge bg-success';
            
            // Atualizar contador
            atualizarContadorDisciplinas();
            
            alert('Disciplina criada com sucesso!');
        } else {
            console.error('❌ Erro ao criar disciplina:', data.mensagem);
            alert('Erro ao criar disciplina: ' + data.mensagem);
        }
    })
    .catch(error => {
        console.error('❌ Erro na requisição:', error);
        alert('Erro ao salvar disciplina. Tente novamente.');
    });
}

function cancelarNovaDisciplina(disciplinaId) {
    console.log('❌ Cancelando nova disciplina:', disciplinaId);
    
    const card = document.querySelector(`[data-id="${disciplinaId}"]`);
    if (card) {
        card.remove();
        atualizarContadorDisciplinas();
        console.log('✅ Nova disciplina cancelada');
    }
}

function salvarAlteracoesDisciplinas() {
    console.log('💾 Salvando todas as alterações das disciplinas...');
    
    // Coletar todas as disciplinas modificadas
    const disciplinasModificadas = [];
    const cards = document.querySelectorAll('#listaDisciplinas .disciplina-card');
    
    cards.forEach(card => {
        const disciplinaId = card.getAttribute('data-id');
        if (!disciplinaId || disciplinaId.startsWith('temp_')) {
            return; // Pular disciplinas temporárias (novas não salvas)
        }
        
        const nome = card.querySelector('input[data-field="nome"]')?.value?.trim();
        const codigo = card.querySelector('input[data-field="codigo"]')?.value?.trim();
        const cargaHoraria = card.querySelector('input[data-field="carga_horaria_padrao"]')?.value;
        const descricao = card.querySelector('textarea[data-field="descricao"]')?.value?.trim();
        
        if (nome && codigo) {
            disciplinasModificadas.push({
                id: disciplinaId,
                nome: nome,
                codigo: codigo,
                carga_horaria_padrao: cargaHoraria || 10,
                descricao: descricao || ''
            });
        }
    });
    
    if (disciplinasModificadas.length === 0) {
        console.log('ℹ️ Nenhuma disciplina modificada para salvar');
        // Fechar modal mesmo sem alterações
        window.closeModal();
        return;
    }
    
    console.log(`💾 Salvando ${disciplinasModificadas.length} disciplinas...`);
    
    // Salvar cada disciplina
    const promises = disciplinasModificadas.map(disciplina => {
        const formData = new FormData();
        formData.append('acao', 'atualizar');
        formData.append('id', disciplina.id);
        formData.append('nome', disciplina.nome);
        formData.append('codigo', disciplina.codigo);
        formData.append('carga_horaria_padrao', disciplina.carga_horaria_padrao);
        formData.append('descricao', disciplina.descricao);
        formData.append('ativa', '1');
        
        return fetch('/cfc-bom-conselho/admin/api/disciplinas-clean.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                console.log(`✅ Disciplina ${disciplina.nome} salva com sucesso`);
                return { sucesso: true, disciplina: disciplina.nome };
            } else {
                console.error(`❌ Erro ao salvar disciplina ${disciplina.nome}:`, data.mensagem);
                return { sucesso: false, disciplina: disciplina.nome, erro: data.mensagem };
            }
        })
        .catch(error => {
            console.error(`❌ Erro na requisição para disciplina ${disciplina.nome}:`, error);
            return { sucesso: false, disciplina: disciplina.nome, erro: error.message };
        });
    });
    
    // Aguardar todas as operações
    Promise.all(promises)
    .then(resultados => {
        const sucessos = resultados.filter(r => r.sucesso).length;
        const erros = resultados.filter(r => !r.sucesso);
        
        if (erros.length === 0) {
            console.log(`✅ Todas as ${sucessos} disciplinas foram salvas com sucesso!`);
            alert(`✅ Todas as ${sucessos} disciplinas foram salvas com sucesso!`);
        } else {
            console.warn(`⚠️ ${sucessos} disciplinas salvas, ${erros.length} com erro`);
            const nomesComErro = erros.map(e => e.disciplina).join(', ');
            alert(`⚠️ ${sucessos} disciplinas salvas com sucesso!\nErro em: ${nomesComErro}`);
        }
        
        // Fechar modal após salvar
        window.closeModal();
    })
    .catch(error => {
        console.error('❌ Erro geral ao salvar disciplinas:', error);
        alert('❌ Erro ao salvar disciplinas. Tente novamente.');
    });
}

// Variável global para armazenar disciplinas
let disciplinasOriginais = [];

// Função para carregar disciplinas no modal
function carregarDisciplinas() {
    // Buscar elementos dentro do modal singleton
    const modalRoot = document.querySelector('#modal-root .modal');
    if (!modalRoot) {
        console.log('🔧 Modal não está aberto ainda, aguardando...');
        return;
    }
    
    const carregando = modalRoot.querySelector('#carregandoDisciplinas');
    const erro = modalRoot.querySelector('#erroCarregarDisciplinas');
    const nenhumaEncontrada = modalRoot.querySelector('#nenhumaDisciplinaEncontrada');
    const container = modalRoot.querySelector('#listaDisciplinas');
    
    // Verificar se os elementos existem
    if (!carregando || !erro || !nenhumaEncontrada || !container) {
        console.error('❌ Elementos do DOM não encontrados no modal singleton');
        return;
    }
    
    // Mostrar estado de carregamento
    carregando.style.display = 'block';
    erro.style.display = 'none';
    nenhumaEncontrada.style.display = 'none';
    container.innerHTML = '';
    
    // Carregar disciplinas da API real
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
                    console.error('Texto recebido:', text.substring(0, 200));
                    throw new Error('JSON inválido: ' + e.message);
                }
            });
        })
        .then(data => {
            carregando.style.display = 'none';
            
            if (data.sucesso) {
                disciplinasOriginais = data.disciplinas || [];
                renderizarDisciplinas(disciplinasOriginais);
                atualizarEstatisticas(disciplinasOriginais);
                console.log('✅ Disciplinas carregadas:', disciplinasOriginais.length);
            } else {
                erro.style.display = 'block';
                console.error('❌ Erro ao carregar disciplinas:', data.mensagem);
            }
        })
        .catch(error => {
            carregando.style.display = 'none';
            erro.style.display = 'block';
            console.error('❌ Erro ao carregar disciplinas:', error);
        });
}

// Função para recarregar lista de disciplinas via AJAX (compatibilidade)
function recarregarDisciplinas() {
    carregarDisciplinas();
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
    const carregando = document.getElementById('carregandoDisciplinas');
    const erro = document.getElementById('erroCarregarDisciplinas');
    
    // Esconder estados
    carregando.style.display = 'none';
    erro.style.display = 'none';
    
    if (disciplinas.length === 0) {
        container.innerHTML = '';
        nenhumaEncontrada.style.display = 'block';
        return;
    }
    
    nenhumaEncontrada.style.display = 'none';
    
    let html = '';
    disciplinas.forEach(disciplina => {
        const statusText = disciplina.ativa == 1 ? 'Ativa' : 'Inativa';
        const statusClass = disciplina.ativa == 1 ? 'success' : 'secondary';
        const corClass = getCorClass(disciplina.cor_hex);
        
        html += `
            <div class="disciplina-card" data-cor="${corClass}" data-id="${disciplina.id}">
                <!-- Menu kebab acima do campo -->
                <div class="dropdown mb-2">
                    <button class="disciplina-card-menu" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#" onclick="salvarDisciplina(${disciplina.id})">
                            <i class="fas fa-save me-2"></i>Salvar
                        </a></li>
                        <li><a class="dropdown-item" href="#" onclick="duplicarDisciplina(${disciplina.id})">
                            <i class="fas fa-copy me-2"></i>Duplicar
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="#" onclick="confirmarExclusaoDisciplina(${disciplina.id})">
                            <i class="fas fa-trash me-2"></i>Excluir
                        </a></li>
                    </ul>
                </div>
                
                <!-- Título -->
                <input type="text" class="form-control disciplina-nome-editavel mb-3" 
                       value="${disciplina.nome}" 
                       data-field="nome" 
                       data-id="${disciplina.id}"
                       placeholder="Nome da disciplina">
                
                <!-- Slug -->
                <input type="text" class="form-control form-control-sm disciplina-codigo-editavel mb-3" 
                       value="${disciplina.codigo}" 
                       data-field="codigo" 
                       data-id="${disciplina.id}"
                       placeholder="Código da disciplina">
                
                <!-- Linha: aulas + status -->
                <div class="disciplina-card-stats">
                    <div class="disciplina-card-aulas">
                        <span class="inline-block w-2.5 h-2.5 rounded-full" style="background-color: ${disciplina.cor_hex || '#007bff'}"></span>
                        <input type="number" class="form-control form-control-sm disciplina-aulas-editavel d-inline-block" 
                               value="${disciplina.carga_horaria_padrao || 0}" 
                               data-field="carga_horaria_padrao" 
                               data-id="${disciplina.id}"
                               style="width: 80px; display: inline-block !important;"
                               min="0" max="999">
                        <span class="ms-1">aulas</span>
                    </div>
                    <span class="badge bg-${statusClass}">${statusText}</span>
                </div>
                
                <!-- Descrição -->
                <textarea class="form-control disciplina-descricao-editavel" 
                          data-field="descricao" 
                          data-id="${disciplina.id}"
                          rows="3" 
                          placeholder="Descrição da disciplina">${disciplina.descricao || ''}</textarea>
            </div>`;
    });
    
    container.innerHTML = html;
    
    // Adicionar event listeners para campos editáveis
    adicionarEventListenersCamposEditaveis();
}

// Função para determinar classe de cor baseada no hex
function getCorClass(hexColor) {
    const colorMap = {
        '#28a745': 'green',
        '#dc3545': 'red', 
        '#007bff': 'blue',
        '#fd7e14': 'orange',
        '#6f42c1': 'purple'
    };
    
    return colorMap[hexColor] || 'blue';
}

// Função para adicionar event listeners aos campos editáveis
function adicionarEventListenersCamposEditaveis() {
    // Event listeners para campos de texto
    document.querySelectorAll('.disciplina-nome-editavel, .disciplina-codigo-editavel').forEach(campo => {
        campo.addEventListener('input', function() {
        const card = this.closest('.disciplina-card');
        if (card && card.classList) {
            card.classList.add('disciplina-modificada');
        }
        });
    });
    
    // Event listeners para campo de número (aulas)
    document.querySelectorAll('.disciplina-aulas-editavel').forEach(campo => {
        campo.addEventListener('change', function() {
        const card = this.closest('.disciplina-card');
        if (card && card.classList) {
            card.classList.add('disciplina-modificada');
        }
        });
    });
    
    // Event listeners para textarea (descrição)
    document.querySelectorAll('.disciplina-descricao-editavel').forEach(campo => {
        campo.addEventListener('input', function() {
        const card = this.closest('.disciplina-card');
        if (card && card.classList) {
            card.classList.add('disciplina-modificada');
        }
        });
    });
}

// Função para salvar disciplina individual
function salvarDisciplina(id) {
    const card = document.querySelector(`[data-id="${id}"]`);
    const campos = card.querySelectorAll('[data-field]');
    
    const dados = {
        id: id
    };
    
    campos.forEach(campo => {
        const field = campo.getAttribute('data-field');
        const value = campo.value;
        dados[field] = value;
    });
    
    console.log('💾 Salvando disciplina:', dados);
    
    // Aqui você pode implementar a chamada AJAX para salvar
    // Por enquanto, apenas remove a classe de modificado
    if (card && card.classList) {
        card.classList.remove('disciplina-modificada');
    }
    
    // Mostrar feedback visual
    const originalBg = card.style.backgroundColor;
    card.style.backgroundColor = '#d4edda';
    setTimeout(() => {
        card.style.backgroundColor = originalBg;
    }, 1000);
}

// Função para atualizar estatísticas
function atualizarEstatisticas(disciplinas = disciplinasOriginais) {
    const total = disciplinas.length;
    const totalCarga = disciplinas.reduce((sum, d) => sum + parseInt(d.carga_horaria_padrao || 0), 0);
    const totalHoras = disciplinas.reduce((sum, d) => sum + (parseInt(d.carga_horaria_padrao || 0) * 1), 0); // Assumindo 1h por aula
    
    // Verificar se os elementos existem antes de atualizar
    const totalDisciplinasEl = document.getElementById('totalDisciplinas');
    const totalCargaHorariaEl = document.getElementById('totalCargaHoraria');
    const totalHorasEl = document.getElementById('totalHoras');
    
    if (totalDisciplinasEl) {
        totalDisciplinasEl.textContent = total;
    }
    if (totalCargaHorariaEl) {
        totalCargaHorariaEl.textContent = totalCarga;
    }
    if (totalHorasEl) {
        totalHorasEl.textContent = totalHoras;
    }
    
    console.log('📊 Estatísticas atualizadas:', { total, totalCarga, totalHoras });
}

// Funções simplificadas para o modal de disciplinas

// ==========================================
// FUNÇÕES ADICIONAIS
// ==========================================

function duplicarDisciplina(id) {
    // Implementar duplicação
    console.log('Duplicar disciplina:', id);
    showAlert('Funcionalidade de duplicação será implementada em breve.', 'info');
}

function arquivarDisciplina(id) {
    // Implementar arquivamento
    console.log('Arquivar disciplina:', id);
    showAlert('Funcionalidade de arquivamento será implementada em breve.', 'info');
}


// Funções simplificadas para mobile

// Função para limpar filtros
function limparFiltrosDisciplinas() {
    document.getElementById('buscarDisciplinas').value = '';
    document.getElementById('filtroStatus').value = '';
    document.getElementById('ordenarDisciplinas').value = 'nome';
    filtrarDisciplinas();
}

// ==========================================
// BUSCA COM DEBOUNCE E ACESSIBILIDADE
// ==========================================

let debounceTimer;

function filtrarDisciplinas() {
    const termoBusca = document.getElementById('buscarDisciplinas').value.toLowerCase();
    const filtroStatus = document.getElementById('filtroStatus').value;
    const ordenacao = document.getElementById('ordenarDisciplinas').value;
    
    let disciplinasFiltradas = [...disciplinasOriginais];
    
    // Filtro por busca
    if (termoBusca) {
        disciplinasFiltradas = disciplinasFiltradas.filter(d => 
            d.nome.toLowerCase().includes(termoBusca) ||
            d.codigo.toLowerCase().includes(termoBusca) ||
            d.descricao.toLowerCase().includes(termoBusca)
        );
    }
    
    // Filtro por status
    if (filtroStatus) {
        disciplinasFiltradas = disciplinasFiltradas.filter(d => 
            (filtroStatus === 'ativo' && d.ativa == 1) ||
            (filtroStatus === 'inativo' && d.ativa == 0)
        );
    }
    
    // Ordenação
    disciplinasFiltradas.sort((a, b) => {
        switch (ordenacao) {
            case 'nome_desc':
                return b.nome.localeCompare(a.nome);
            case 'carga':
                return parseInt(b.carga_horaria_padrao) - parseInt(a.carga_horaria_padrao);
            case 'recentes':
                return new Date(b.created_at || 0) - new Date(a.created_at || 0);
            default: // nome
                return a.nome.localeCompare(b.nome);
        }
    });
    
    // Renderizar disciplinas filtradas
    renderizarDisciplinas(disciplinasFiltradas);
    atualizarEstatisticas(disciplinasFiltradas);
}

// Debounce para busca
function debounceFiltrarDisciplinas() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(filtrarDisciplinas, 300);
}

// Atalhos de teclado
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K para focar na busca
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const modal = document.getElementById('modalGerenciarDisciplinas');
        if (modal && modal.classList && modal.classList.contains('show')) {
            document.getElementById('buscarDisciplinas').focus();
        }
    }
    
    // Esc para fechar modal
    if (e.key === 'Escape') {
        const modal = document.getElementById('modalGerenciarDisciplinas');
        if (modal && modal.classList && modal.classList.contains('show')) {
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }
        }
    }
});

// ==========================================
// INICIALIZAÇÃO DO MODAL
// ==========================================

function inicializarModalDisciplinas() {
    const modal = document.getElementById('modalGerenciarDisciplinas');
    
    // Event listeners para busca com debounce
    const campoBusca = document.getElementById('buscarDisciplinas');
    if (campoBusca) {
        campoBusca.addEventListener('input', debounceFiltrarDisciplinas);
        campoBusca.addEventListener('keyup', debounceFiltrarDisciplinas);
    }
    
    // Event listeners para filtros
    const filtroStatus = document.getElementById('filtroStatus');
    const ordenarDisciplinas = document.getElementById('ordenarDisciplinas');
    
    if (filtroStatus) {
        filtroStatus.addEventListener('change', filtrarDisciplinas);
    }
    
    if (ordenarDisciplinas) {
        ordenarDisciplinas.addEventListener('change', filtrarDisciplinas);
    }
    
    // Foco inicial no campo de busca quando modal abrir
    modal.addEventListener('shown.bs.modal', function() {
        campoBusca.focus();
        // Mostrar atalho de teclado
        const searchShortcut = document.getElementById('searchShortcut');
        if (searchShortcut && searchShortcut.classList) {
            searchShortcut.classList.remove('d-none');
        }
    });
    
    modal.addEventListener('hidden.bs.modal', function() {
        // Esconder atalho de teclado
        const searchShortcut = document.getElementById('searchShortcut');
        if (searchShortcut && searchShortcut.classList) {
            searchShortcut.classList.add('d-none');
        }
        // Limpar seleções
        disciplinasSelecionadas.clear();
        atualizarAcoesMultiplas();
    });
}

// Função para visualizar disciplina (placeholder)
function visualizarDisciplina(id) {
    console.log('Visualizar disciplina:', id);
    // Implementar visualização detalhada se necessário
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
        if (botao && botao.classList) {
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
    console.log('🚀 DOM carregado - inicializando navegação...');
    atualizarNavegacao();
    carregarDisciplinasDisponiveis();
    
    // Debug: verificar se os botões existem
    const botoes = document.querySelectorAll('.wizard-step-btn');
    console.log('🔍 Botões encontrados:', botoes.length);
    
    botoes.forEach((botao, index) => {
        console.log(`Botão ${index + 1}:`, botao.textContent.trim(), 'onclick:', botao.onclick);
        
        // Adicionar evento de clique alternativo
        botao.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('🖱️ Clique detectado no botão:', index + 1);
            
            // Extrair número da etapa do texto do botão
            const texto = botao.textContent.trim();
            const match = texto.match(/(\d+)\./);
            if (match) {
                const etapa = parseInt(match[1]);
                console.log('🎯 Navegando para etapa via evento:', etapa);
                navegarParaEtapa(etapa);
            }
        });
    });
});

// Função de teste para debug

// ==========================================
// FUNÇÃO DE DEBUG PARA SCROLL ÚNICO
// ==========================================

/**
 * Função para verificar elementos com overflow no modal
 * Execute no console: debugScrollModal()
 */
function debugScrollModal() {
    console.log('🔍 Verificando elementos com overflow no modal...');
    
    const modal = document.querySelector('#modal-root .modal');
    if (!modal) {
        console.log('❌ Modal #modal-root .modal não encontrado');
        return;
    }
    
    const elementosComOverflow = [...modal.querySelectorAll('*')]
        .filter(el => ['auto','scroll','hidden','clip'].includes(getComputedStyle(el).overflowY))
        .map(el => ({
            el,
            tag: el.tagName.toLowerCase(),
            id: el.id,
            cls: el.className,
            overflowY: getComputedStyle(el).overflowY,
            maxH: getComputedStyle(el).maxHeight,
            h: getComputedStyle(el).height
        }));
    
    console.table(elementosComOverflow);
    
    if (elementosComOverflow.length === 1 && elementosComOverflow[0].cls.includes('modal-body')) {
        console.log('✅ PERFEITO! Apenas o modal-body tem overflow');
    } else {
        console.log('❌ PROBLEMA! Múltiplos elementos com overflow:', elementosComOverflow.length);
        elementosComOverflow.forEach((el, index) => {
            console.log(`${index + 1}. ${el.tag}.${el.cls} - overflowY: ${el.overflowY}, maxH: ${el.maxH}, h: ${el.h}`);
        });
    }
    
    return elementosComOverflow;
}

/**
 * Função para forçar correção imediata (para validar)
 * Execute no console: forcarCorrecaoScroll()
 */
function forcarCorrecaoScroll() {
    console.log('🔧 Forçando correção imediata do scroll...');
    
    const modal = document.querySelector('#modal-root .modal');
    if (!modal) {
        console.log('❌ Modal #modal-root .modal não encontrado');
        return;
    }
    
    [...modal.querySelectorAll('*')].forEach(el => {
        const cs = getComputedStyle(el);
        if (el.closest('.modal-body') && !el.classList.contains('modal-body') &&
            ['auto','scroll','hidden','clip'].includes(cs.overflowY)) {
            console.log('🔧 Corrigindo elemento:', el.tagName, el.className);
            el.style.setProperty('overflow','visible','important');
            el.style.setProperty('max-height','none','important');
            el.style.setProperty('height','auto','important');
        }
    });
    
    console.log('✅ Correção forçada aplicada!');
}

/**
 * Função para remover PerfectScrollbar (se houver)
 * Execute no console: removerPerfectScrollbar()
 */
function removerPerfectScrollbar() {
    console.log('🔧 Removendo PerfectScrollbar...');
    
    const modal = document.querySelector('#modal-root .modal');
    if (!modal) {
        console.log('❌ Modal #modal-root .modal não encontrado');
        return;
    }
    
    modal.querySelectorAll('.ps, .ps--active-y').forEach(el => {
        console.log('🔧 Removendo PerfectScrollbar de:', el.className);
        el.classList.remove('ps','ps--active-y');
        el.style.removeProperty('overflow');
        el.style.removeProperty('max-height');
        el.style.removeProperty('height');
    });
    
    console.log('✅ PerfectScrollbar removido!');
}

// Disponibilizar funções globalmente
window.debugScrollModal = debugScrollModal;
window.forcarCorrecaoScroll = forcarCorrecaoScroll;
window.removerPerfectScrollbar = removerPerfectScrollbar;

// ==========================================
// SISTEMA DE MODAL SINGLETON
// ==========================================

window.SingletonModalSystem = {
    open: function(render) {
        if (document.body.dataset.singletonModalOpen === '1') {
            console.log('⚠️ Modal singleton já está aberto, apenas atualizando conteúdo');
            this.update(render);
            return;
        }
        
        const root = document.getElementById('modal-root');
        if (!root) {
            console.log('❌ Modal root não encontrado');
            return;
        }
        
        root.innerHTML = '';
        
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop';
        backdrop.onclick = () => this.close();
        
        const wrapper = document.createElement('div');
        wrapper.className = 'modal-wrapper';
        
        const modalContent = render();
        if (modalContent) {
            wrapper.appendChild(modalContent);
        }
        
        root.appendChild(backdrop);
        root.appendChild(wrapper);
        
        document.body.dataset.singletonModalOpen = '1';
        document.body.style.overflow = 'hidden';
        
        document.addEventListener('keydown', this.handleEscape);
        this.setupFocusTrap(wrapper);
        
        console.log('✅ Modal singleton aberto');
    },
    
    update: function(render) {
        const wrapper = document.querySelector('#modal-root .modal-wrapper');
        if (wrapper) {
            wrapper.innerHTML = '';
            const modalContent = render();
            if (modalContent) {
                wrapper.appendChild(modalContent);
                this.setupFocusTrap(wrapper);
            }
        }
    },
    
    close: function() {
        const root = document.getElementById('modal-root');
        if (root) {
            root.innerHTML = '';
        }
        
        delete document.body.dataset.singletonModalOpen;
        document.body.style.overflow = '';
        document.removeEventListener('keydown', this.handleEscape);
        
        console.log('✅ Modal singleton fechado');
    },
    
    handleEscape: function(event) {
        if (event.key === 'Escape') {
            window.SingletonModalSystem.close();
        }
    },
    
    setupFocusTrap: function(wrapper) {
        const focusableElements = wrapper.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        
        if (focusableElements.length === 0) return;
        
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        wrapper.addEventListener('keydown', function(event) {
            if (event.key === 'Tab') {
                if (event.shiftKey) {
                    if (document.activeElement === firstElement) {
                        event.preventDefault();
                        lastElement.focus();
                    }
                } else {
                    if (document.activeElement === lastElement) {
                        event.preventDefault();
                        firstElement.focus();
                    }
                }
            }
        });
        
        setTimeout(() => firstElement.focus(), 100);
    }
};

window.openModal = function(render) {
    window.SingletonModalSystem.open(render);
};

window.closeModal = function() {
    window.SingletonModalSystem.close();
};
</script>

<!-- Modal Root -->
<div id="modal-root"></div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
