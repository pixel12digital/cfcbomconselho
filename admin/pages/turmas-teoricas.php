<?php
/**
 * Página Principal de Gestão de Turmas Teóricas
 * Sistema com wizard em 4 etapas
 * 
 * @author Sistema CFC Bom Conselho
 * @version 1.0
 * @since 2024
 */

// Forçar charset UTF-8 para evitar problemas de codificação
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}

// Incluir arquivos necessários usando caminho relativo confiável
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar se o usuário está logado e tem permissão de admin ou instrutor
if (!isLoggedIn() || (!hasPermission('admin') && !hasPermission('instrutor'))) {
    echo '<div class="alert alert-danger">Você não tem permissão para acessar esta página.</div>';
    exit;
}

// Obter dados do usuário logado e verificar permissões
$user = getCurrentUser();
$userType = $user['tipo'] ?? 'admin';
$userId = $user['id'] ?? null;
$isAdmin = hasPermission('admin');
$isInstrutor = hasPermission('instrutor');

// Definir instância do banco de dados
$db = Database::getInstance();

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
        SELECT i.*, 
               COALESCE(u.nome, i.nome) as nome,
               COALESCE(u.email, i.email) as email,
               COALESCE(u.telefone, i.telefone) as telefone,
               CASE 
                   WHEN i.categorias_json IS NOT NULL AND i.categorias_json != '' AND i.categorias_json != '[]' THEN 
                       REPLACE(REPLACE(REPLACE(i.categorias_json, '[', ''), ']', ''), '\"', '')
                   WHEN i.categoria_habilitacao IS NOT NULL AND i.categoria_habilitacao != '' THEN 
                       i.categoria_habilitacao
                   ELSE 'Sem categoria'
               END as categoria_habilitacao
        FROM instrutores i
        LEFT JOIN usuarios u ON i.usuario_id = u.id
        WHERE i.ativo = 1
        ORDER BY COALESCE(u.nome, i.nome)
    ");
} catch (Exception $e) {
    $instrutores = [];
}

// Processar ações
$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';
$step = $_GET['step'] ?? $_POST['step'] ?? '1';
$turmaId = $_GET['turma_id'] ?? $_POST['turma_id'] ?? null;

// Verificar se é requisição AJAX
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

// Se for AJAX e for para carregar calendário, retornar apenas o conteúdo necessário
if ($isAjax && $acao === 'detalhes' && isset($_GET['semana_calendario'])) {
    // Iniciar buffer de output
    ob_start();
    
    // Incluir apenas o arquivo de detalhes inline que gerará o calendário
    include __DIR__ . '/turmas-teoricas-detalhes-inline.php';
    
    // Capturar output
    $html = ob_get_clean();
    
    // Retornar HTML completo - JavaScript vai extrair apenas o #tab-calendario
    echo $html;
    exit;
}

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
            // Redirecionar para detalhes ao invés de step2
            $redirectUrl = '?page=turmas-teoricas&acao=detalhes&turma_id=' . $resultado['turma_id'] . '&sucesso=1';
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

<!-- CSS de Referência para Modais Padronizados -->
<link href="assets/css/popup-reference.css" rel="stylesheet">

<style>
/* Otimizações específicas para o modal de salas */
#modalGerenciarSalas .popup-modal-wrapper {
    max-height: 90vh;
    height: 90vh;
}

#modalGerenciarSalas .popup-modal-content {
    max-height: calc(90vh - 200px);
    overflow-y: auto;
    padding: 1rem 1.5rem;
}

#modalGerenciarSalas .popup-items-grid {
    max-height: calc(90vh - 350px);
    overflow-y: auto;
    padding-right: 0.5rem;
}

#modalGerenciarSalas .popup-item-card {
    min-height: 180px;
    max-height: 200px;
}

#modalGerenciarSalas #formulario-nova-sala {
    max-height: calc(90vh - 200px);
    overflow-y: auto;
}

#modalGerenciarSalas #formulario-nova-sala .form-control {
    font-size: 0.9rem;
    padding: 0.5rem 0.75rem;
}

#modalGerenciarSalas #formulario-nova-sala .form-check {
    margin-bottom: 0.5rem;
}

/* Garantir que o modal de edição de disciplinas apareça na frente */
#modalEditarDisciplina {
    z-index: 9999 !important;
}

#modalEditarDisciplina .modal-dialog {
    z-index: 10000 !important;
}

#modalEditarDisciplina .modal-content {
    z-index: 10001 !important;
}

/* Garantir que o backdrop também tenha z-index alto */
.modal-backdrop {
    z-index: 9998 !important;
}

#modalGerenciarSalas #formulario-nova-sala .form-check-label {
    font-size: 0.85rem;
}

/* Scrollbar personalizada para o modal */
#modalGerenciarSalas .popup-modal-content::-webkit-scrollbar,
#modalGerenciarSalas .popup-items-grid::-webkit-scrollbar {
    width: 6px;
}

#modalGerenciarSalas .popup-modal-content::-webkit-scrollbar-track,
#modalGerenciarSalas .popup-items-grid::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

#modalGerenciarSalas .popup-modal-content::-webkit-scrollbar-thumb,
#modalGerenciarSalas .popup-items-grid::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

#modalGerenciarSalas .popup-modal-content::-webkit-scrollbar-thumb:hover,
#modalGerenciarSalas .popup-items-grid::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Otimização do layout da seção header */
#modalGerenciarSalas .popup-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    gap: 1rem;
}

#modalGerenciarSalas .popup-section-header .popup-section-title {
    flex: 1;
}

#modalGerenciarSalas .popup-section-header .popup-stats-item {
    flex-shrink: 0;
    margin: 0 !important;
}

#modalGerenciarSalas .popup-section-header .popup-primary-button {
    flex-shrink: 0;
}

/* Otimizações específicas para o modal de disciplinas */
#modalGerenciarDisciplinas {
    z-index: 1055;
}

/* Correção do z-index para o modal Editar Sala aparecer na frente do modal Gerenciar Salas */
#modalEditarSala {
    z-index: 1060 !important;
}

#modalGerenciarDisciplinas .popup-modal-wrapper {
    max-height: 90vh;
    height: 90vh;
}

/* Garantir que não haja backdrop indesejado */
#modalGerenciarDisciplinas::before {
    display: none !important;
}

/* Remover qualquer backdrop do Bootstrap */
.modal-backdrop {
    display: none !important;
    opacity: 0 !important;
    visibility: hidden !important;
}

/* Garantir que o body não tenha classes de modal */
body.modal-open {
    overflow: auto !important;
    padding-right: 0 !important;
}

/* Remover qualquer elemento com background escuro */
*[style*="background-color: rgba(0, 0, 0"] {
    display: none !important;
}

*[style*="background: rgba(0, 0, 0"] {
    display: none !important;
}

/* Garantir que o modal de disciplinas não tenha backdrop */
#modalGerenciarDisciplinas {
    background-color: transparent !important;
    background: transparent !important;
}

#modalGerenciarDisciplinas::before,
#modalGerenciarDisciplinas::after {
    display: none !important;
    content: none !important;
}

/* Sobrescrever o background escuro do popup-modal para disciplinas */
#modalGerenciarDisciplinas.popup-modal {
    background-color: transparent !important;
    background: transparent !important;
}

/* Remover qualquer pseudo-elemento que possa criar background escuro */
#modalGerenciarDisciplinas.popup-modal::before {
    display: none !important;
    content: none !important;
    background: none !important;
    background-color: transparent !important;
}

/* CSS específico para modal de disciplinas customizado */
.modal-disciplinas-custom {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background-color: transparent !important;
    background: transparent !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    z-index: 1055 !important;
    padding: 2rem !important;
    overflow: hidden !important;
}

.modal-disciplinas-custom::before,
.modal-disciplinas-custom::after {
    display: none !important;
    content: none !important;
    background: none !important;
    background-color: transparent !important;
}

/* Sobrescrever completamente o popup-modal para disciplinas */
.modal-disciplinas-custom.popup-modal {
    background-color: transparent !important;
    background: transparent !important;
}

/* Garantir que o wrapper tenha o background branco (padrão) */
.modal-disciplinas-custom .popup-modal-wrapper {
    background: #fff !important;
    border-radius: 16px !important;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2) !important;
    display: grid !important;
    grid-template-rows: auto 1fr auto !important; /* igual ao padrão */
    overflow: hidden !important; /* bordas arredondadas visíveis */
    position: relative !important;
    max-width: 95vw !important;
    width: 95vw !important;
    max-height: calc(100vh - 4rem) !important; /* igual ao padrão */
}

/* Garantir que o conteúdo tenha scroll interno */
.modal-disciplinas-custom .popup-modal-content {
    overflow-y: auto !important;
    max-height: calc(100vh - 300px) !important;
}

/* CORREÇÃO DEFINITIVA - Sobrescrever o popup-reference.css */
#modalGerenciarDisciplinas.modal-disciplinas-custom {
    background-color: transparent !important;
    background: transparent !important;
    background-image: none !important;
}

/* ELIMINAR COMPLETAMENTE OS PSEUDO-ELEMENTOS */
#modalGerenciarDisciplinas.modal-disciplinas-custom::before,
#modalGerenciarDisciplinas.modal-disciplinas-custom::after {
    display: none !important;
    content: none !important;
    background: none !important;
    background-color: transparent !important;
    opacity: 0 !important;
    visibility: hidden !important;
    width: 0 !important;
    height: 0 !important;
    position: absolute !important;
    top: -9999px !important;
    left: -9999px !important;
}

/* Garantir que não haja herança de background escuro */
#modalGerenciarDisciplinas.modal-disciplinas-custom,
#modalGerenciarDisciplinas.modal-disciplinas-custom * {
    background-color: transparent !important;
}

/* Exceto o wrapper que deve ser branco */
#modalGerenciarDisciplinas.modal-disciplinas-custom .popup-modal-wrapper {
    background: #fff !important;
    background-color: #fff !important;
}

/* Garantir que o botão X seja clicável */
#modalGerenciarDisciplinas .popup-modal-close {
    cursor: pointer !important;
    pointer-events: auto !important;
    z-index: 1060 !important;
    position: relative !important;
    background: transparent !important;
    border: none !important;
    padding: 8px !important;
    color: #666 !important;
    font-size: 18px !important;
    line-height: 1 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 32px !important;
    height: 32px !important;
    border-radius: 50% !important;
    transition: all 0.2s ease !important;
}

#modalGerenciarDisciplinas .popup-modal-close:hover {
    background-color: rgba(0, 0, 0, 0.1) !important;
    color: #333 !important;
    transform: scale(1.1) !important;
}

#modalGerenciarDisciplinas .popup-modal-close:active {
    transform: scale(0.95) !important;
}

/* Garantir que o botão Fechar seja clicável */
#modalGerenciarDisciplinas .popup-secondary-button {
    cursor: pointer !important;
    pointer-events: auto !important;
    z-index: 1060 !important;
    position: relative !important;
    background: #6c757d !important;
    border: 1px solid #6c757d !important;
    color: white !important;
    padding: 8px 16px !important;
    border-radius: 6px !important;
    font-size: 14px !important;
    transition: all 0.2s ease !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
}

#modalGerenciarDisciplinas .popup-secondary-button:hover {
    background: #5a6268 !important;
    border-color: #5a6268 !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
}

#modalGerenciarDisciplinas .popup-secondary-button:active {
    transform: translateY(0) !important;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1) !important;
}

/* Header do modal de disciplinas com padding reduzido em 20% */
.modal-disciplinas-custom .popup-modal-header {
    padding: 1.2rem 2rem !important;
    min-height: auto !important;
    max-height: none !important;
}

.modal-disciplinas-custom .popup-modal-header .header-content {
    align-items: center !important;
    height: 100% !important;
}

.modal-disciplinas-custom .popup-modal-header .header-icon {
    margin-right: 1rem !important;
}

.modal-disciplinas-custom .popup-modal-header .header-text h5 {
    margin-bottom: 0.25rem !important;
}

.modal-disciplinas-custom .popup-modal-header .header-text small {
    opacity: 0.75 !important;
}

/* SOLUÇÃO RADICAL - Remover qualquer elemento com background escuro */
*[style*="background-color: rgba(0, 0, 0"] {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
}

*[style*="background: rgba(0, 0, 0"] {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
}

#modalGerenciarDisciplinas .popup-modal-content {
    max-height: none;
    overflow-y: visible;
    padding: 0.5rem 2rem 1.5rem 2rem;
}

#modalGerenciarDisciplinas .popup-items-grid {
    max-height: none;
    overflow-y: visible;
    padding-right: 0.5rem;
}

#modalGerenciarDisciplinas .popup-item-card {
    min-height: 180px;
    max-height: 200px;
}

#modalGerenciarDisciplinas #formulario-nova-disciplina {
    max-height: none;
    overflow-y: visible;
}

#modalGerenciarDisciplinas #formulario-nova-disciplina .form-control {
    font-size: 0.9rem;
    padding: 0.5rem 0.75rem;
}

/* Ajustar footer do modal de disciplinas */
#modalGerenciarDisciplinas .popup-modal-footer {
    padding: 1rem 2rem !important;
    min-height: auto !important;
}

/* Reduzir espaçamento da barra de busca */
#modalGerenciarDisciplinas .popup-search-container {
    margin-bottom: 1rem !important;
}

/* Reduzir espaçamento da seção de disciplinas */
#modalGerenciarDisciplinas .popup-section-header {
    margin-bottom: 1rem !important;
}

/* Scrollbar personalizada apenas para o modal pai */
#modalGerenciarDisciplinas::-webkit-scrollbar {
    width: 8px;
}

#modalGerenciarDisciplinas::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

#modalGerenciarDisciplinas::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

#modalGerenciarDisciplinas::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* CSS removido - agora usando sistema popup customizado */

/* Responsividade para mobile */
@media (max-width: 767.98px) {
    #modalGerenciarSalas .popup-modal-wrapper {
        max-height: 100vh;
        height: 100vh;
    }
    
    #modalGerenciarSalas .popup-modal-content {
        max-height: calc(100vh - 150px);
        padding: 1rem;
    }
    
    #modalGerenciarSalas .popup-items-grid {
        max-height: calc(100vh - 300px);
    }
    
    #modalGerenciarSalas .popup-item-card {
        min-height: 150px;
        max-height: 170px;
    }
    
    /* Layout mobile para header */
    #modalGerenciarSalas .popup-section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    #modalGerenciarSalas .popup-section-header .popup-stats-item {
        align-self: flex-end;
    }
    
    #modalGerenciarSalas .popup-section-header .popup-primary-button {
        width: 100%;
        justify-content: center;
    }
    
    /* Responsividade para modal de disciplinas */
    #modalGerenciarDisciplinas .popup-modal-wrapper {
        min-height: 100vh;
        margin: 0 !important;
    }
    
    #modalGerenciarDisciplinas .popup-modal-content {
        max-height: none;
        padding: 0.25rem 1rem 1rem 1rem;
    }
    
    #modalGerenciarDisciplinas .popup-modal-header {
        padding: 0.8rem 1.5rem !important;
    }
    
    #modalGerenciarDisciplinas .popup-modal-footer {
        padding: 0.75rem 1rem !important;
    }
    
    #modalGerenciarDisciplinas .popup-items-grid {
        max-height: none;
    }
    
    #modalGerenciarDisciplinas .popup-item-card {
        min-height: 150px;
        max-height: 170px;
    }
    
    #modalGerenciarDisciplinas .popup-section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    #modalGerenciarDisciplinas .popup-section-header .popup-stats-item {
        align-self: flex-end;
    }
    
    #modalGerenciarDisciplinas .popup-section-header .popup-primary-button {
        width: 100%;
        justify-content: center;
    }
}
</style>

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
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 80px;
}

.wizard-header h2 {
    margin: 0;
    font-size: 1.875rem;
    font-weight: 700;
}

/* CSS do wizard-steps removido - não é mais necessário */


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
        <h2 class="d-flex align-items-center text-white">
            <i class="fas fa-graduation-cap me-2" aria-hidden="true"></i>
            Gestão de Turmas
        </h2>
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
            <!-- Página de Detalhes com Edição Inline -->
            <?php include __DIR__ . '/turmas-teoricas-detalhes-inline.php'; ?>
            
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
                            <select id="curso_tipo" name="curso_tipo" class="form-control" <?= $acao === 'ativar' ? 'disabled' : '' ?> onchange="atualizarTotalHorasRegressivo()" required>
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
                            <?php echo count($cursosDisponiveis); ?> curso(s) cadastrado(s) - 
                            <a href="#" onclick="abrirModalTiposCursoInterno()" class="text-primary">Clique aqui para gerenciar</a>
                        </small>
                    </div>
                    
                    <!-- Seção de Disciplinas Automáticas -->
                    <div class="form-group">
                        <label>
                            <i class="fas fa-book me-1"></i>Disciplinas do Curso
                            <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="abrirModalDisciplinasInterno()" title="Configurar Disciplinas dos Cursos">
                                <i class="fas fa-cog"></i>
                            </button>
                        </label>
                        <div class="mb-2">
                            <!-- Container para disciplinas automáticas -->
                            <div id="disciplinas-automaticas-container">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Disciplinas Automáticas:</strong> As disciplinas serão carregadas automaticamente quando você selecionar o tipo de curso.
                                </div>
                                <div id="disciplinas-lista" class="mt-3">
                                    <!-- Disciplinas serão carregadas automaticamente aqui -->
                                </div>
                            </div>
                            
                            <!-- Botão para recarregar disciplinas (opcional) -->
                            <button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="recarregarDisciplinasAutomaticas()" style="display: none;" id="btn-recarregar-disciplinas">
                                <i class="fas fa-sync me-1"></i>Recarregar Disciplinas
                            </button>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            <span id="contador-disciplinas">0</span> disciplina(s) configurada(s) automaticamente
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
                                   value="<?= (($acao === 'ativar' || $acao === 'editar') && $turmaAtual && $turmaAtual['data_inicio']) ? $turmaAtual['data_inicio'] : '' ?>"
                                   required>
                        </div>
                        
                        <div class="form-group" style="flex: 1;">
                            <label for="data_fim">Data de Término *</label>
                            <input type="date" 
                                   id="data_fim" 
                                   name="data_fim" 
                                   class="form-control"
                                   min="<?= date('Y-m-d') ?>"
                                   value="<?= (($acao === 'ativar' || $acao === 'editar') && $turmaAtual && $turmaAtual['data_fim']) ? $turmaAtual['data_fim'] : '' ?>"
                                   required>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h4>⚙️ Configurações Adicionais</h4>
                    
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
            
        <?php elseif ($step === '4' || $acao === 'alunos'): ?>
            <!-- STEP 4: INSERÇÃO DE ALUNOS -->
            <?php include __DIR__ . '/turmas-teoricas-step4.php'; ?>
            
        <?php endif; ?>
    </div>
</div>

<script>
// Função global para detectar o path base automaticamente
function getBasePath() {
    return window.location.pathname.includes('/cfc-bom-conselho/') ? '/cfc-bom-conselho' : '';
}

// Error handler global para capturar erros de atualizarDisciplina
window.addEventListener('error', function(event) {
    if (event.message && event.message.includes('Cannot read properties of undefined') && 
        event.message.includes('reading \'value\'')) {
        console.warn('⚠️ [ERROR HANDLER] Erro capturado e tratado:', event.message);
        console.warn('⚠️ [ERROR HANDLER] Arquivo:', event.filename);
        console.warn('⚠️ [ERROR HANDLER] Linha:', event.lineno);
        // Prevenir que o erro seja exibido no console
        event.preventDefault();
        return true;
    }
});

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

// Função específica para carregar disciplinas em novos selects (não afetada pela flag de controle)
function carregarDisciplinasNovoSelect(disciplinaId) {
    console.log('🔄 [NOVO SELECT] Carregando disciplinas para disciplina ' + disciplinaId);
    
    const select = document.querySelector('select[name="disciplina_' + disciplinaId + '"]');
    if (!select) {
        console.error('❌ [NOVO SELECT] Select não encontrado para disciplina ' + disciplinaId);
        return;
    }
    
    // Limpar select
    select.innerHTML = '<option value="">Carregando disciplinas...</option>';
    
    // Carregar disciplinas diretamente da API
    fetch(getBasePath() + '/admin/api/disciplinas-clean.php?acao=listar')
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('❌ [NOVO SELECT] Erro na requisição:', text.substring(0, 200));
                    throw new Error('JSON inválido: ' + e.message);
                }
            });
        })
        .then(data => {
            if (data.sucesso && data.disciplinas) {
                // Limpar opções e adicionar placeholder
                select.innerHTML = '<option value="">Selecione a disciplina...</option>';
                
                // Adicionar disciplinas disponíveis
                data.disciplinas.forEach(disciplina => {
                    const option = document.createElement('option');
                    option.value = disciplina.id;
                    option.textContent = disciplina.nome;
                    option.dataset.aulas = disciplina.carga_horaria_padrao || 10;
                    option.dataset.cor = '#007bff'; // Cor padrão
                    select.appendChild(option);
                });
                
                console.log('✅ [NOVO SELECT] Disciplinas carregadas para disciplina ' + disciplinaId + ':', data.disciplinas.length);
                
            } else {
                select.innerHTML = '<option value="">Erro ao carregar disciplinas</option>';
                console.error('❌ [NOVO SELECT] Erro ao carregar disciplinas:', data.mensagem || 'Erro desconhecido');
            }
        })
        .catch(error => {
            select.innerHTML = '<option value="">Erro ao carregar disciplinas</option>';
            console.error('❌ [NOVO SELECT] Erro na requisição de disciplinas:', error);
        });
}

function adicionarDisciplina() {
    console.log('🎯 Função adicionarDisciplina chamada!');
    
    // Verificar se estamos na página correta (não na página de detalhes)
    const urlParams = new URLSearchParams(window.location.search);
    const acao = urlParams.get('acao');
    const step = urlParams.get('step');
    
    if (acao === 'detalhes') {
        console.log('⚠️ [ADICIONAR] Função chamada na página de detalhes - ignorando');
        return;
    }
    
    // Se estamos na página de agendamento (step=2), não executar esta função
    if (step === '2' || acao === 'agendar') {
        console.log('✅ [ADICIONAR] Página de agendamento detectada - função adicionarDisciplina não deve ser executada aqui');
        return;
    }
    
    // Validação apenas para página de criação de turma (step=1)
    const cursoSelect = document.getElementById('curso_tipo');
    if (!cursoSelect || !cursoSelect.value) {
        alert('⚠️ Selecione primeiro o tipo de curso!');
        if (cursoSelect) {
            cursoSelect.focus();
        }
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
                           onchange="atualizarTotalHorasRegressivo()">
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
        console.log('🔄 Carregando disciplinas para nova disciplina ' + contadorDisciplinas);
        // Usar a nova função específica para novos selects
        carregarDisciplinasNovoSelect(contadorDisciplinas);
    }, 100);
}

function carregarDisciplinas(disciplinaId) {
    // Evitar múltiplos carregamentos simultâneos
    if (carregamentoDisciplinasEmAndamento) {
        console.log('⏳ [DISCIPLINAS] Carregamento já em andamento, ignorando...');
        return;
    }
    
    carregamentoDisciplinasEmAndamento = true;
    
    const cursoSelect = document.getElementById('curso_tipo');
    const disciplinaSelect = document.querySelector('select[name="disciplina_' + disciplinaId + '"]');
    
    if (!cursoSelect || !disciplinaSelect) {
        console.warn('⚠️ Elementos não encontrados para disciplina ' + disciplinaId);
        carregamentoDisciplinasEmAndamento = false;
        return;
    }
    
    const cursoTipo = cursoSelect.value;
    
    // Limpar opções anteriores
    disciplinaSelect.innerHTML = '<option value="">Carregando disciplinas...</option>';
    
    // Carregar disciplinas diretamente da API
    fetch(getBasePath() + '/admin/api/disciplinas-clean.php?acao=listar')
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
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
                
                console.log('✅ Disciplinas carregadas para curso ' + cursoTipo + ':', data.disciplinas.length);
                
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
        })
        .finally(() => {
            // Liberar flag após carregamento
            carregamentoDisciplinasEmAndamento = false;
        });
}

function atualizarDisciplina(disciplinaId) {
    const disciplinaSelect = document.querySelector('select[name="disciplina_' + disciplinaId + '"]');
    const infoElement = document.querySelector('[data-disciplina-id="' + disciplinaId + '"] .disciplina-info');
    const aulasElement = infoElement?.querySelector('.aulas-obrigatorias');
    const horasInput = document.querySelector('input[name="disciplina_horas_' + disciplinaId + '"]');
    const horasGroup = horasInput?.closest('.input-group');
    const horasLabel = horasGroup?.querySelector('.input-group-text');
    
    console.log('🔍 [ATUALIZAR] Elementos encontrados:');
    console.log('  - disciplinaSelect:', !!disciplinaSelect);
    console.log('  - infoElement:', !!infoElement);
    
    if (!disciplinaSelect) {
        console.warn('⚠️ [ATUALIZAR] Select não encontrado para disciplina', disciplinaId);
        return;
    }
    
    if (!infoElement) {
        console.warn('⚠️ [ATUALIZAR] Info element não encontrado para disciplina', disciplinaId);
        return;
    }
    
    const selectedIndex = disciplinaSelect.selectedIndex;
    console.log('📊 [ATUALIZAR] Selected index:', selectedIndex, 'Total options:', disciplinaSelect.options.length);
    
    if (selectedIndex < 0 || selectedIndex >= disciplinaSelect.options.length) {
        console.warn('⚠️ [ATUALIZAR] Selected index inválido');
        return;
    }
    
    const selectedOption = disciplinaSelect.options[selectedIndex];
    console.log('🎯 [ATUALIZAR] Selected option:', selectedOption);
    
    if (!selectedOption) {
        console.warn('⚠️ [ATUALIZAR] Selected option é null/undefined');
        return;
    }
    
    if (selectedOption.value && selectedOption.value !== '') {
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
        disciplinaItem.style.borderLeft = '4px solid ' + cor;
        
        console.log('✅ Disciplina selecionada: ' + selectedOption.textContent + ' (' + aulas + ' aulas padrão)');
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
    
    // Atualizar contador regressivo após mudança na disciplina
    atualizarTotalHorasRegressivo();
}

function removerDisciplina(disciplinaId) {
    const disciplinaItem = document.querySelector('[data-disciplina-id="' + disciplinaId + '"]');
    if (disciplinaItem) {
        // Se for o campo fixo (ID 0), apenas limpar a seleção
        if (disciplinaId === 0) {
            const select = disciplinaItem.querySelector('select');
            if (select) {
                select.value = '';
                // Repovoar o select com as disciplinas disponíveis
                repovoarSelectDisciplinas(select);
            }
            console.log('🗑️ Campo fixo de disciplina limpo e repovoado');
        } else {
            // Para disciplinas adicionais, remover o elemento
            disciplinaItem.remove();
            console.log('🗑️ Disciplina ' + disciplinaId + ' removida');
        }
        // Atualizar contador regressivo após remoção
        atualizarTotalHorasRegressivo();
    }
}

function atualizarPreview() {
    console.log('🔄 Atualizando preview com contador regressivo...');
    
    // Usar a nova função de contador regressivo
    atualizarTotalHorasRegressivo();
    
    // Manter logs para compatibilidade
    const disciplinas = document.querySelectorAll('.disciplina-item');
    let disciplinasSelecionadas = 0;
    
    disciplinas.forEach(item => {
        const select = item.querySelector('select');
        if (select && select.value) {
            disciplinasSelecionadas++;
        }
    });
    
    console.log('📊 Preview atualizado - Disciplinas selecionadas: ' + disciplinasSelecionadas);
}

// Variável global para armazenar o total do banco
let totalHorasBanco = 0;
let atualizacaoEmAndamento = false; // Flag para evitar múltiplas execuções simultâneas

        // Função completa para contador regressivo - CORRIGIDA
        function atualizarTotalHorasRegressivo() {
            // Verificar se estamos na página correta (etapa 1)
            const urlParams = new URLSearchParams(window.location.search);
            const step = urlParams.get('step');
            const acao = urlParams.get('acao');
            
            // Só executar na etapa 1 (nova turma)
            if (step !== '1' && acao !== 'nova') {
                console.log('⏳ [PÁGINA PRINCIPAL] Função não executada - não é etapa 1');
                return;
            }
            
            // Evitar múltiplas execuções simultâneas
            if (atualizacaoEmAndamento) {
                console.log('⏳ [PÁGINA PRINCIPAL] Atualização já em andamento, ignorando...');
                return;
            }
            
            atualizacaoEmAndamento = true;
            console.log('🔄 [PÁGINA PRINCIPAL] atualizarTotalHorasRegressivo EXECUTADA');
            
            try {
                const cursoSelect = document.getElementById('curso_tipo');
                const totalHorasElement = document.getElementById('total-horas-disciplinas');
                
                if (!cursoSelect || !totalHorasElement) {
                    console.error('❌ [PÁGINA PRINCIPAL] Elementos não encontrados');
                    atualizacaoEmAndamento = false;
                    return;
                }
                
                const tipoCurso = cursoSelect.value;
                if (!tipoCurso) {
                    totalHorasElement.textContent = '0';
                    atualizacaoEmAndamento = false;
                    return;
                }
                
                const cargasHorarias = {
                    'formacao_45h': 45,
                    'formacao_acc_20h': 20,
                    'reciclagem_infrator': 30,
                    'atualizacao': 15
                };
                
                const cargaHorariaTotal = cargasHorarias[tipoCurso] || 0;
                let horasUtilizadas = 0;
                
                // Buscar TODAS as disciplinas, incluindo o campo fixo e os adicionais
                const disciplinas = document.querySelectorAll('.disciplina-item');
                console.log('🔍 [PÁGINA PRINCIPAL] Encontradas ' + disciplinas.length + ' disciplinas');
                
                disciplinas.forEach(function(item, index) {
                    const select = item.querySelector('select');
                    if (select && select.value) {
                        const selectedOption = select.options[select.selectedIndex];
                        const horasDisciplina = parseInt(selectedOption.dataset.aulas) || 0;
                        horasUtilizadas += horasDisciplina;
                        console.log('📊 [PÁGINA PRINCIPAL] Disciplina ' + index + ': ' + selectedOption.textContent + ' (' + horasDisciplina + 'h)');
                    }
                });
                
                const horasRestantes = Math.max(0, cargaHorariaTotal - horasUtilizadas);
                totalHorasElement.textContent = horasRestantes;
                
                console.log('📊 [PÁGINA PRINCIPAL] Total: ' + cargaHorariaTotal + 'h - Utilizadas: ' + horasUtilizadas + 'h = Restantes: ' + horasRestantes + 'h');
                
            } catch (error) {
                console.error('❌ [PÁGINA PRINCIPAL] Erro na função atualizarTotalHorasRegressivo:', error);
            } finally {
                // Liberar flag após um pequeno delay para evitar oscilações
                setTimeout(() => {
                    atualizacaoEmAndamento = false;
                }, 100);
            }
        }

// Garantir que a função seja global
window.atualizarTotalHorasRegressivo = atualizarTotalHorasRegressivo;

// Função de teste imediata
window.testeFuncaoPrincipal = function() {
    console.log('🧪 Testando função principal...');
    try {
        console.log('🔍 Função existe:', typeof atualizarTotalHorasRegressivo);
        if (typeof atualizarTotalHorasRegressivo === 'function') {
            console.log('✅ Função encontrada, executando...');
            atualizarTotalHorasRegressivo();
            console.log('✅ Função executada com sucesso!');
        } else {
            console.error('❌ Função não encontrada!');
        }
    } catch (error) {
        console.error('❌ Erro na função principal:', error);
        console.error('❌ Stack:', error.stack);
    }
};

// Função para coletar disciplinas selecionadas
function coletarDisciplinasSelecionadas() {
    const disciplinas = [];
    const disciplinaItems = document.querySelectorAll('.disciplina-item');
    
    disciplinaItems.forEach(function(item) {
        const select = item.querySelector('select');
        if (select && select.value) {
            const selectedOption = select.options[select.selectedIndex];
            disciplinas.push({
                id: select.value,
                nome: selectedOption.textContent,
                carga_horaria_padrao: parseInt(selectedOption.dataset.aulas) || 10,
                cor_hex: selectedOption.dataset.cor || '#007bff'
            });
        }
    });
    
    return disciplinas;
}

// Função para salvar disciplinas selecionadas
function salvarDisciplinasSelecionadas(turmaId) {
    const disciplinas = coletarDisciplinasSelecionadas();
    
    if (disciplinas.length === 0) {
        console.log('⚠️ Nenhuma disciplina selecionada para salvar');
        return Promise.resolve();
    }
    
    console.log('💾 Salvando disciplinas selecionadas:', disciplinas);
    
    return fetch(getBasePath() + '/admin/api/turmas-teoricas.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            acao: 'salvar_disciplinas',
            turma_id: turmaId,
            disciplinas: disciplinas
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            console.log('✅ Disciplinas salvas com sucesso:', data.total);
        } else {
            console.error('❌ Erro ao salvar disciplinas:', data.mensagem);
        }
        return data;
    })
    .catch(error => {
        console.error('❌ Erro na requisição:', error);
        return { sucesso: false, mensagem: error.message };
    });
}

// Modificar a função de criação de turma para incluir salvamento automático de disciplinas
function criarTurmaComDisciplinas() {
    console.log('🎯 Criando turma com disciplinas automáticas...');
    
    // Coletar dados do formulário
    const formData = new FormData(document.getElementById('formTurmaTeorica'));
    formData.append('acao', 'criar_basica');
    
    // Criar turma primeiro
    fetch(getBasePath() + '/admin/api/turmas-teoricas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            console.log('✅ Turma criada:', data.turma_id);
            
            // Obter tipo de curso selecionado
            const cursoTipo = document.getElementById('curso_tipo').value;
            
            if (cursoTipo) {
                // Salvar disciplinas automaticamente baseadas no curso
                return salvarDisciplinasAutomaticas(data.turma_id, cursoTipo).then(() => data.turma_id);
            } else {
                console.warn('⚠️ Nenhum tipo de curso selecionado, prosseguindo sem disciplinas');
                return data.turma_id;
            }
        } else {
            throw new Error(data.mensagem);
        }
    })
    .then(turmaId => {
        if (turmaId) {
            console.log('🎯 Redirecionando para etapa 2 com turma_id:', turmaId);
            window.location.href = `?page=turmas-teoricas&acao=detalhes&turma_id=${turmaId}&sucesso=1`;
        } else {
            console.error('❌ ID da turma não encontrado para redirecionamento');
        }
    })
    .catch(error => {
        console.error('❌ Erro ao criar turma:', error);
        alert('Erro ao criar turma: ' + error.message);
    });
}

// Função para salvar disciplinas automaticamente
function salvarDisciplinasAutomaticas(turmaId, cursoTipo) {
    console.log('💾 Salvando disciplinas automaticamente para turma:', turmaId, 'curso:', cursoTipo);
    
    const formData = new FormData();
    formData.append('acao', 'salvar_disciplinas_automaticas');
    formData.append('turma_id', turmaId);
    formData.append('curso_tipo', cursoTipo);
    
    return fetch(getBasePath() + '/admin/api/disciplinas-automaticas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            console.log('✅ Disciplinas salvas automaticamente:', data.total, 'disciplinas');
            return data;
        } else {
            console.error('❌ Erro ao salvar disciplinas:', data.mensagem);
            throw new Error(data.mensagem);
        }
    })
    .catch(error => {
        console.error('❌ Erro na requisição de disciplinas:', error);
        throw error;
    });
}

// Função para simular seleção de disciplina
window.simularSelecaoDisciplina = function() {
    console.log('🧪 Simulando seleção de disciplina...');
    try {
        // Simular chamada da função atualizarDisciplina
        console.log('🔄 Chamando atualizarDisciplina(0)...');
        if (typeof atualizarDisciplina === 'function') {
            atualizarDisciplina(0);
        } else {
            console.error('❌ Função atualizarDisciplina não encontrada!');
        }
    } catch (error) {
        console.error('❌ Erro ao simular seleção:', error);
    }
};

// Forçar execução da função quando a página carrega
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 [PÁGINA PRINCIPAL] DOM carregado - executando atualizarTotalHorasRegressivo...');
    setTimeout(function() {
        console.log('⏰ [PÁGINA PRINCIPAL] Executando função após 1 segundo...');
        atualizarTotalHorasRegressivo();
    }, 1000);
});

// Funções de teste duplicadas removidas

// Função para obter carga horária total do curso
function obterCargaHorariaCurso(tipoCurso) {
    const cargasHorarias = {
        'formacao_45h': 45,
        'formacao_acc_20h': 20,
        'reciclagem_infrator': 30,
        'atualizacao': 15
    };
    
    const cargaHoraria = cargasHorarias[tipoCurso] || 0;
    console.log('📊 Carga horária do curso ' + tipoCurso + ': ' + cargaHoraria + 'h');
    return cargaHoraria;
}

// Função duplicada removida - usando a versão simplificada acima

// Função de debug para testar manualmente
window.testarContadorRegressivo = function() {
    console.log('🧪 Testando contador regressivo manualmente...');
    console.log('🔍 Função existe:', typeof atualizarTotalHorasRegressivo);
    console.log('🔍 Elementos:');
    console.log('  - curso_tipo:', document.getElementById('curso_tipo'));
    console.log('  - total-horas-disciplinas:', document.getElementById('total-horas-disciplinas'));
    console.log('  - disciplina-items:', document.querySelectorAll('.disciplina-item').length);
    
    if (typeof atualizarTotalHorasRegressivo === 'function') {
        atualizarTotalHorasRegressivo();
    } else {
        console.error('❌ Função atualizarTotalHorasRegressivo não está definida!');
    }
};

// Função para testar se a função atualizarDisciplina está sendo chamada
window.testarAtualizarDisciplina = function(disciplinaId) {
    console.log('🧪 Testando atualizarDisciplina para disciplina:', disciplinaId);
    
    const select = document.querySelector('select[name="disciplina_' + disciplinaId + '"]');
    if (select) {
        console.log('✅ Select encontrado:', select);
        console.log('🔍 Valor atual:', select.value);
        
        // Simular seleção
        if (select.options.length > 1) {
            select.value = select.options[1].value;
            console.log('🔄 Valor alterado para:', select.value);
            
            // Disparar evento change
            select.dispatchEvent(new Event('change', { bubbles: true }));
            console.log('✅ Evento change disparado');
        }
    } else {
        console.error('❌ Select não encontrado para disciplina:', disciplinaId);
    }
};

// Função para forçar atualização do contador
window.forcarAtualizacaoContador = function() {
    console.log('🔄 Forçando atualização do contador...');
    
    // Selecionar um curso se não estiver selecionado
    const cursoSelect = document.getElementById('curso_tipo');
    if (cursoSelect && !cursoSelect.value) {
        cursoSelect.value = 'formacao_45h';
        console.log('✅ Curso selecionado: formacao_45h');
    }
    
    // Chamar a função diretamente
    if (typeof atualizarTotalHorasRegressivo === 'function') {
        atualizarTotalHorasRegressivo();
    } else {
        console.error('❌ Função não está definida!');
    }
};

// Nova função para carregar total de horas do banco
function carregarTotalHorasDoBanco() {
    console.log('🔄 Carregando total de horas do banco de dados...');
    
    // Verificar se estamos na página correta (etapa 1)
    const urlParams = new URLSearchParams(window.location.search);
    const step = urlParams.get('step');
    const acao = urlParams.get('acao');
    
    // Só executar na etapa 1 (nova turma)
    if (step !== '1' && acao !== 'nova') {
        console.log('⏳ [TOTAL HORAS] Função não executada - não é etapa 1');
        return;
    }
    
    console.log('📡 Fazendo requisição para API...');
    
    fetch(getBasePath() + '/admin/api/disciplinas-clean.php?acao=listar')
        .then(response => {
            console.log('📡 Resposta da API: ' + response.status + ' ' + response.statusText);
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('📊 Dados recebidos da API:', data);
            
            if (data.sucesso && data.disciplinas) {
                let totalHorasBanco = 0;
                console.log('📋 Processando ' + data.disciplinas.length + ' disciplinas...');
                
                data.disciplinas.forEach((disciplina, index) => {
                    const horas = parseInt(disciplina.carga_horaria_padrao) || 0;
                    totalHorasBanco += horas;
                    console.log('  ' + (index + 1) + '. ' + disciplina.nome + ': ' + horas + 'h');
                });
                
                console.log('📊 Total de horas do banco: ' + totalHorasBanco + 'h');
                
                // Armazenar o total do banco na variável global
                window.totalHorasBanco = totalHorasBanco;
                
                const totalHorasElement = document.getElementById('total-horas-disciplinas');
                console.log('🎯 Elemento total-horas-disciplinas encontrado: ' + (totalHorasElement ? '✅' : '❌'));
                
                if (totalHorasElement) {
                    const valorAnterior = totalHorasElement.textContent;
                    totalHorasElement.textContent = totalHorasBanco;
                    console.log('✅ Total atualizado: "' + valorAnterior + '" → "' + totalHorasBanco + 'h"');
                    
                    // Forçar re-render se necessário
                    totalHorasElement.style.display = 'none';
                    totalHorasElement.offsetHeight;
                    totalHorasElement.style.display = '';
                } else {
                    console.error('❌ Elemento #total-horas-disciplinas não encontrado!');
                    
                    // Tentar encontrar elementos similares
                    const alternativas = document.querySelectorAll('[id*="total"], [id*="horas"], .text-primary strong');
                    console.log('🔍 Encontrados ' + alternativas.length + ' elementos alternativos:', alternativas);
                }
            } else {
                console.warn('⚠️ API retornou dados inválidos:', data);
            }
        })
        .catch(error => {
            console.error('❌ Erro ao carregar total do banco:', error);
            console.error('📡 Verifique se a API está funcionando em:', getBasePath() + '/admin/api/disciplinas-clean.php?acao=listar');
        });
}

// Função global para forçar atualização do total (pode ser chamada manualmente)
window.atualizarTotalHoras = function() {
    console.log('🔧 Função global atualizarTotalHoras() chamada');
    carregarTotalHorasDoBanco();
};

// Função para verificar se o total está correto
window.verificarTotalHoras = function() {
    console.log('🔍 Verificando total de horas...');
    const elemento = document.getElementById('total-horas-disciplinas');
    if (elemento) {
        console.log('📊 Total atual na interface: "' + elemento.textContent + '"');
        if (elemento.textContent === '0' || elemento.textContent === '0h') {
            console.log('⚠️ Total está zerado, forçando atualização...');
            carregarTotalHorasDoBanco();
        }
    } else {
        console.log('❌ Elemento não encontrado');
    }
};

// Função global para forçar carregamento de disciplinas
window.forcarCarregamentoDisciplinas = function() {
    console.log('🔧 Forçando carregamento de disciplinas...');
    carregarDisciplinasDisponiveis();
};

// Função global para testar repovoamento
window.testarRepovoamento = function() {
    console.log('🧪 Testando repovoamento do select...');
    const select = document.querySelector('select[name="disciplina_0"]');
    if (select) {
        repovoarSelectDisciplinas(select);
    } else {
        console.error('❌ Select principal não encontrado');
    }
};

// Função global para testar contador regressivo
window.testarContadorRegressivo = function() {
    console.log('🧪 Testando contador regressivo...');
    console.log('🔧 Função atualizarTotalHorasRegressivo existe:', typeof atualizarTotalHorasRegressivo === 'function');
    
    if (typeof atualizarTotalHorasRegressivo === 'function') {
        const resultado = atualizarTotalHorasRegressivo();
        console.log('📊 Resultado do teste:', resultado);
        
        const cursoSelect = document.getElementById('curso_tipo');
        const totalHorasElement = document.getElementById('total-horas-disciplinas');
        
        console.log('🔍 Elementos encontrados:');
        console.log('- cursoSelect:', cursoSelect ? cursoSelect.value : 'NÃO ENCONTRADO');
        console.log('- totalHorasElement:', totalHorasElement ? totalHorasElement.textContent : 'NÃO ENCONTRADO');
        
        return resultado;
    } else {
        console.error('❌ Função atualizarTotalHorasRegressivo não encontrada!');
        return null;
    }
};


// Função global para forçar atualização do contador - CORRIGIDA
window.forcarAtualizacaoContador = function() {
    console.log('🔧 Forçando atualização do contador regressivo...');
    
    // Executar apenas uma vez para evitar conflitos
    setTimeout(() => atualizarTotalHorasRegressivo(), 200);
    
    console.log('✅ Atualização programada!');
};

// Função para repovoar select após limpeza
function repovoarSelectDisciplinas(selectElement) {
    if (!selectElement) {
        console.error('❌ Elemento select não fornecido');
        return;
    }
    
    console.log('🔄 Repovoando select de disciplinas...');
    
    // Limpar opções existentes (exceto placeholder)
    selectElement.innerHTML = '<option value="">Selecione a disciplina...</option>';
    
    // Se há disciplinas em cache, usar elas
    if (disciplinasDisponiveis && disciplinasDisponiveis.length > 0) {
        disciplinasDisponiveis.forEach(disciplina => {
            const option = document.createElement('option');
            option.value = disciplina.value;
            option.textContent = disciplina.text;
            option.dataset.aulas = disciplina.aulas;
            option.dataset.cor = disciplina.cor;
            selectElement.appendChild(option);
        });
        console.log('✅ Select repovoado com ' + disciplinasDisponiveis.length + ' disciplinas do cache');
    } else {
        // Se não há cache, carregar do banco
        console.log('🔄 Cache vazio, carregando disciplinas do banco...');
        fetch(getBasePath() + '/admin/api/disciplinas-clean.php?acao=listar')
            .then(response => response.json())
            .then(data => {
                if (data.sucesso && data.disciplinas) {
                    data.disciplinas.forEach(disciplina => {
                        const option = document.createElement('option');
                        option.value = disciplina.id;
                        option.textContent = disciplina.nome;
                        option.dataset.aulas = disciplina.carga_horaria_padrao || 10;
                        option.dataset.cor = '#007bff';
                        selectElement.appendChild(option);
                    });
                    console.log('✅ Select repovoado com ' + data.disciplinas.length + ' disciplinas do banco');
                }
            })
            .catch(error => {
                console.error('❌ Erro ao repovoar select:', error);
            });
    }
}

// Recarregar disciplinas quando curso mudar (segunda instância)
document.addEventListener('DOMContentLoaded', function() {
    console.log('📄 DOM carregado - segunda instância!');
    
    // Carregar disciplinas disponíveis imediatamente
    carregarDisciplinasDisponiveis();
    
    // Carregar total de horas do banco se não houver disciplinas na interface - CORRIGIDO
    setTimeout(() => {
        console.log('🔄 Executando carregarTotalHorasDoBanco...');
        carregarTotalHorasDoBanco();
        
        // Atualizar contador regressivo inicial
        setTimeout(() => {
            console.log('🔄 Executando atualizarTotalHorasRegressivo inicial...');
            atualizarTotalHorasRegressivo();
        }, 500);
    }, 1000);
    
    const cursoSelect = document.getElementById('curso_tipo');
    if (cursoSelect) {
        cursoSelect.addEventListener('change', function() {
            console.log('🎯 Curso selecionado (segunda instância):', this.value);
            
            // Carregar disciplinas automaticamente quando curso mudar
            if (this.value) {
                carregarDisciplinasAutomaticas(this.value);
            } else {
                limparDisciplinasAutomaticas();
            }
            
            // Atualizar total de horas com contador regressivo
            atualizarTotalHorasRegressivo();
            
            // Carregar disciplinas no campo fixo
            carregarDisciplinas(0);
        });
        
        // Se já houver um curso selecionado, carregar disciplinas - CORRIGIDO
        if (cursoSelect.value) {
            console.log('🔄 Curso já selecionado (segunda instância), carregando disciplinas...');
            setTimeout(() => carregarDisciplinas(0), 500);
        } else {
            // Se não há curso selecionado, carregar disciplinas mesmo assim
            console.log('🔄 Nenhum curso selecionado (segunda instância), carregando disciplinas disponíveis...');
            setTimeout(() => carregarDisciplinas(0), 1000);
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
    z-index: 99999 !important;
}

#modalGerenciarDisciplinas .modal-dialog {
    max-width: 1200px;
    width: 90vw;
    height: 85vh;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    z-index: 99999 !important;
    position: relative !important;
}

#modalGerenciarDisciplinas .modal-content {
    height: 100%;
    display: flex;
    flex-direction: column;
    border-radius: 15px;
    overflow: hidden;
    z-index: 99999 !important;
    position: relative !important;
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
    background-color: rgba(0, 0, 0, 0.7) !important;
    backdrop-filter: blur(3px) !important;
    z-index: 99998 !important;
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

/* Estilos para edição inline */
.editable-field {
    transition: all 0.2s ease;
    border-radius: 4px;
    padding: 0.25rem;
}

.editable-field:hover {
    background-color: #f8f9fa !important;
    cursor: pointer;
}

.editable-field.editing {
    background-color: #e3f2fd !important;
    border: 1px solid #2196f3;
}

.popup-item-card-menu {
    transition: all 0.2s ease;
}

.popup-item-card-menu:hover {
    transform: scale(1.1);
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
 * Navegar para uma etapa específica - FUNÇÃO DESABILITADA
 * @param {number} etapa - Número da etapa (1, 2, 3, 4)
 */
function navegarParaEtapa(etapa) {
    console.log('⚠️ Função navegarParaEtapa desabilitada - wizard removido');
    return;
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
        case 4:
            novaAcao = 'alunos';
            break;
        default:
            novaAcao = turmaId ? 'editar' : 'nova';
    }
    
    // Construir nova URL
    let novaUrl = '?page=turmas-teoricas&acao=' + novaAcao + '&step=' + etapa;
    
    if (turmaId) {
        novaUrl += '&turma_id=' + turmaId;
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
    alertDiv.className = 'alert alert-' + type + ' alert-custom alert-dismissible fade show';
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
// Flag para evitar múltiplos carregamentos simultâneos de disciplinas
if (typeof carregamentoDisciplinasEmAndamento === 'undefined') {
    var carregamentoDisciplinasEmAndamento = false;
}

// Carregar disciplinas do banco de dados
function carregarDisciplinasDisponiveis() {
    console.log('🔄 Carregando disciplinas disponíveis...');
    
    return fetch(getBasePath() + '/admin/api/disciplinas-clean.php?acao=listar')
        .then(response => {
            console.log('📡 Resposta da API recebida:', response.status);
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
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
                
                // Carregar disciplinas no campo fixo
                carregarDisciplinasNoSelectPrincipal(data.disciplinas);
                
                return data.disciplinas; // Retornar as disciplinas para uso posterior
                
            } else {
                console.error('❌ Erro ao carregar disciplinas:', data.mensagem || 'Erro desconhecido');
                disciplinasDisponiveis = [];
                throw new Error(data.mensagem || 'Erro ao carregar disciplinas');
            }
        })
        .catch(error => {
            console.error('❌ Erro na requisição de disciplinas:', error);
            disciplinasDisponiveis = [];
            throw error; // Re-throw para que o .then() seja executado
        });
}

// Nova função para carregar disciplinas no select principal
function carregarDisciplinasNoSelectPrincipal(disciplinas) {
    console.log('🔄 Carregando disciplinas no select principal...');
    
    // Verificar se estamos na página correta (etapa 1)
    const urlParams = new URLSearchParams(window.location.search);
    const step = urlParams.get('step');
    const acao = urlParams.get('acao');
    
    // Só executar na etapa 1 (nova turma)
    if (step !== '1' && acao !== 'nova') {
        console.log('⏳ [SELECT PRINCIPAL] Função não executada - não é etapa 1');
        return;
    }
    
    const select = document.querySelector('select[name="disciplina_0"]');
    if (!select) {
        console.error('❌ Select principal não encontrado');
        return;
    }
    
    // Limpar opções
    select.innerHTML = '<option value="">Selecione a disciplina...</option>';
    
    // Adicionar disciplinas
    disciplinas.forEach(disciplina => {
        const option = document.createElement('option');
        option.value = disciplina.id;
        option.textContent = disciplina.nome;
        option.dataset.aulas = disciplina.carga_horaria_padrao || 10;
        option.dataset.cor = '#007bff';
        select.appendChild(option);
    });
    
    console.log('✅ ' + disciplinas.length + ' disciplinas carregadas no select principal');
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
    if (!selectElement) {
        console.warn('⚠️ recriarSelect: Elemento não fornecido');
        return null;
    }
    
    // Verificar se o elemento ainda existe no DOM
    if (!document.contains(selectElement)) {
        console.warn('⚠️ recriarSelect: Elemento não existe mais no DOM');
        return null;
    }
    
    console.log('🔄 Recriando select completamente...');
    
    const parent = selectElement.parentNode;
    if (!parent) {
        console.error('❌ Parent element não encontrado para recriar select');
        console.error('❌ Elemento:', selectElement);
        console.error('❌ Elemento existe no DOM:', document.contains(selectElement));
        return null;
    }
    
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
    fetch(getBasePath() + '/admin/api/disciplinas-clean.php?acao=listar')
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
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
    
    // Verificar se estamos na página correta (não na página de detalhes)
    const urlParams = new URLSearchParams(window.location.search);
    const acao = urlParams.get('acao');
    const step = urlParams.get('step');
    
    if (acao === 'detalhes') {
        console.log('⚠️ [ADICIONAR] Função chamada na página de detalhes - ignorando');
        return;
    }
    
    // Se estamos na página de agendamento (step=2), não executar esta função
    if (step === '2' || acao === 'agendar') {
        console.log('✅ [ADICIONAR] Página de agendamento detectada - função adicionarDisciplina não deve ser executada aqui');
        return;
    }
    
    // Validação apenas para página de criação de turma (step=1)
    const cursoSelect = document.getElementById('curso_tipo');
    if (!cursoSelect || !cursoSelect.value) {
        alert('⚠️ Selecione primeiro o tipo de curso!');
        if (cursoSelect) {
            cursoSelect.focus();
        }
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
                           onchange="atualizarTotalHorasRegressivo()">
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
        console.log('🔄 Carregando disciplinas para nova disciplina ' + contadorDisciplinas);
        // Usar a nova função específica para novos selects
        carregarDisciplinasNovoSelect(contadorDisciplinas);
    }, 100);
}

function carregarDisciplinas(disciplinaId) {
    // Evitar múltiplos carregamentos simultâneos
    if (carregamentoDisciplinasEmAndamento) {
        console.log('⏳ [DISCIPLINAS] Carregamento já em andamento, ignorando...');
        return;
    }
    
    carregamentoDisciplinasEmAndamento = true;
    
    const cursoSelect = document.getElementById('curso_tipo');
    const disciplinaSelect = document.querySelector('select[name="disciplina_' + disciplinaId + '"]');
    
    if (!cursoSelect || !disciplinaSelect) {
        console.warn('⚠️ Elementos não encontrados para disciplina ' + disciplinaId);
        carregamentoDisciplinasEmAndamento = false;
        return;
    }
    
    const cursoTipo = cursoSelect.value;
    
    // Limpar opções anteriores
    disciplinaSelect.innerHTML = '<option value="">Carregando disciplinas...</option>';
    
    // Carregar disciplinas diretamente da API
    fetch(getBasePath() + '/admin/api/disciplinas-clean.php?acao=listar')
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
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
                
                console.log('✅ Disciplinas carregadas para curso ' + cursoTipo + ':', data.disciplinas.length);
                
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
        })
        .finally(() => {
            // Liberar flag após carregamento
            carregamentoDisciplinasEmAndamento = false;
        });
}

function atualizarDisciplina(disciplinaId) {
    const disciplinaSelect = document.querySelector('select[name="disciplina_' + disciplinaId + '"]');
    const infoElement = document.querySelector('[data-disciplina-id="' + disciplinaId + '"] .disciplina-info');
    const aulasElement = infoElement?.querySelector('.aulas-obrigatorias');
    const horasInput = document.querySelector('input[name="disciplina_horas_' + disciplinaId + '"]');
    const horasGroup = horasInput?.closest('.input-group');
    const horasLabel = horasGroup?.querySelector('.input-group-text');
    
    console.log('🔍 [ATUALIZAR] Elementos encontrados:');
    console.log('  - disciplinaSelect:', !!disciplinaSelect);
    console.log('  - infoElement:', !!infoElement);
    
    if (!disciplinaSelect) {
        console.warn('⚠️ [ATUALIZAR] Select não encontrado para disciplina', disciplinaId);
        return;
    }
    
    if (!infoElement) {
        console.warn('⚠️ [ATUALIZAR] Info element não encontrado para disciplina', disciplinaId);
        return;
    }
    
    const selectedIndex = disciplinaSelect.selectedIndex;
    console.log('📊 [ATUALIZAR] Selected index:', selectedIndex, 'Total options:', disciplinaSelect.options.length);
    
    if (selectedIndex < 0 || selectedIndex >= disciplinaSelect.options.length) {
        console.warn('⚠️ [ATUALIZAR] Selected index inválido');
        return;
    }
    
    const selectedOption = disciplinaSelect.options[selectedIndex];
    console.log('🎯 [ATUALIZAR] Selected option:', selectedOption);
    
    if (!selectedOption) {
        console.warn('⚠️ [ATUALIZAR] Selected option é null/undefined');
        return;
    }
    
    if (selectedOption.value && selectedOption.value !== '') {
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
        disciplinaItem.style.borderLeft = '4px solid ' + cor;
        
        console.log('✅ Disciplina selecionada: ' + selectedOption.textContent + ' (' + aulas + ' aulas padrão)');
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
    
    // Atualizar contador regressivo após mudança na disciplina
    atualizarTotalHorasRegressivo();
}

function removerDisciplina(disciplinaId) {
    const disciplinaItem = document.querySelector('[data-disciplina-id="' + disciplinaId + '"]');
    if (disciplinaItem) {
        // Se for o campo fixo (ID 0), apenas limpar a seleção
        if (disciplinaId === 0) {
            const select = disciplinaItem.querySelector('select');
            if (select) {
                select.value = '';
                // Repovoar o select com as disciplinas disponíveis
                repovoarSelectDisciplinas(select);
            }
            console.log('🗑️ Campo fixo de disciplina limpo e repovoado');
        } else {
            // Para disciplinas adicionais, remover o elemento
            disciplinaItem.remove();
            console.log('🗑️ Disciplina ' + disciplinaId + ' removida');
        }
        // Atualizar contador regressivo após remoção
        atualizarTotalHorasRegressivo();
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
    
    console.log('📊 Total de horas calculado: ' + totalHoras + 'h', disciplinasComHoras);
}

// Recarregar disciplinas quando curso mudar
document.addEventListener('DOMContentLoaded', function() {
    console.log('📄 DOM carregado - sistema pronto!');
    
    // Carregar disciplinas disponíveis imediatamente
    carregarDisciplinasDisponiveis();
    
    // Carregar disciplinas diretamente no select principal
    carregarDisciplinasDoBanco();
    
    // Carregar disciplinas automáticas se houver curso selecionado
    setTimeout(() => {
        const cursoSelect = document.getElementById('curso_tipo');
        if (cursoSelect && cursoSelect.value) {
            console.log('🔄 Carregando disciplinas automáticas para curso pré-selecionado:', cursoSelect.value);
            carregarDisciplinasAutomaticas(cursoSelect.value);
        }
    }, 1000);
    
    // Atualizar contador regressivo no carregamento
    setTimeout(() => {
        console.log('🔄 Atualizando contador regressivo no carregamento da página...');
        atualizarTotalHorasRegressivo();
    }, 1500);
    
    const cursoSelect = document.getElementById('curso_tipo');
    if (cursoSelect) {
        cursoSelect.addEventListener('change', function() {
            console.log('🎯 Curso selecionado:', this.value);
            
            // Carregar disciplinas automaticamente quando curso mudar
            if (this.value) {
                carregarDisciplinasAutomaticas(this.value);
            } else {
                limparDisciplinasAutomaticas();
            }
            
            // Atualizar total de horas com contador regressivo
            atualizarTotalHorasRegressivo();
            
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
    
    fetch(getBasePath() + '/admin/api/disciplinas-clean.php?acao=listar')
        .then(response => {
            console.log('📡 Resposta da API:', response.status);
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
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
                console.log('✅ ' + data.disciplinas.length + ' disciplinas encontradas no banco');
                
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

<!-- Modal Gerenciar Salas - Padrão -->
<div class="popup-modal" id="modalGerenciarSalas" style="display: none;">
    <div class="popup-modal-wrapper">
        
        <!-- HEADER -->
        <div class="popup-modal-header">
            <div class="header-content">
                <div class="header-icon">
                    <i class="fas fa-door-open"></i>
                </div>
                <div class="header-text">
                    <h5>Gerenciar Salas</h5>
                    <small>Configure e organize as salas de aula disponíveis</small>
                </div>
            </div>
            <button type="button" class="popup-modal-close" onclick="fecharModalSalas()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- CONTEÚDO -->
        <div class="popup-modal-content">
            
            <!-- Seção Otimizada - Título, Estatísticas e Botão na mesma linha -->
            <div class="popup-section-header">
                <div class="popup-section-title">
                    <h6>Salas Cadastradas</h6>
                    <small>Gerencie e organize as salas de aula do CFC</small>
                </div>
                <div class="popup-stats-item" style="margin: 0;">
                    <div class="popup-stats-icon">
                        <div class="icon-circle">
                            <i class="fas fa-door-open"></i>
                        </div>
                    </div>
                    <div class="popup-stats-text">
                        <h6 style="margin: 0;">Total: <span class="stats-number" id="total-salas">0</span></h6>
                    </div>
                </div>
                <button type="button" class="popup-primary-button" onclick="abrirModalNovaSalaInterno()">
                    <i class="fas fa-plus"></i>
                    Nova Sala
                </button>
            </div>
            
            <!-- Conteúdo Principal - Lista de Salas -->
            <div id="conteudo-principal">
                <!-- Grid de Salas -->
                <div class="popup-items-grid" id="lista-salas-modal">
                    <!-- Lista de salas será carregada via AJAX -->
                    <div class="popup-loading-state show">
                        <div class="popup-loading-spinner"></div>
                        <div class="popup-loading-text">
                            <h6>Carregando salas...</h6>
                            <p>Aguarde enquanto buscamos suas salas cadastradas</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Formulário Nova Sala (oculto inicialmente) -->
            <div id="formulario-nova-sala" style="display: none;">
                <div class="popup-section-header">
                    <div class="popup-section-title">
                        <h6>Nova Sala</h6>
                        <small>Preencha os dados da nova sala de aula</small>
                    </div>
                    <button type="button" class="popup-secondary-button" onclick="voltarParaLista()">
                        <i class="fas fa-arrow-left"></i>
                        Voltar
                    </button>
                </div>
                
                <form id="formNovaSalaIntegrado" class="mt-3">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nome_sala_integrado" class="form-label">Nome da Sala *</label>
                                <input type="text" class="form-control" id="nome_sala_integrado" name="nome" required placeholder="Ex: Sala 1, Laboratório">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="capacidade_sala_integrado" class="form-label">Capacidade *</label>
                                <input type="number" class="form-control" id="capacidade_sala_integrado" name="capacidade" min="1" max="100" value="30" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Equipamentos</label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="equipamentos[projetor]" id="projetor_sala_integrado">
                                    <label class="form-check-label" for="projetor_sala_integrado">Projetor</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="equipamentos[quadro]" id="quadro_sala_integrado">
                                    <label class="form-check-label" for="quadro_sala_integrado">Quadro</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="equipamentos[ar_condicionado]" id="ar_sala_integrado">
                                    <label class="form-check-label" for="ar_sala_integrado">Ar Condicionado</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="equipamentos[computadores]" id="computadores_sala_integrado">
                                    <label class="form-check-label" for="computadores_sala_integrado">Computadores</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="equipamentos[internet]" id="internet_sala_integrado">
                                    <label class="form-check-label" for="internet_sala_integrado">Internet</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="equipamentos[som]" id="som_sala_integrado">
                                    <label class="form-check-label" for="som_sala_integrado">Sistema de Som</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="button" class="popup-secondary-button" onclick="voltarParaLista()">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </button>
                        <button type="submit" class="popup-save-button">
                            <i class="fas fa-save"></i>
                            Salvar Sala
                        </button>
                    </div>
                </form>
            </div>
            
        </div>
        
        <!-- FOOTER -->
        <div class="popup-modal-footer">
            <div class="popup-footer-info">
                <small>
                    <i class="fas fa-info-circle"></i>
                    As alterações são salvas automaticamente
                </small>
            </div>
            <div class="popup-footer-actions">
                <button type="button" class="popup-secondary-button" onclick="fecharModalSalas()">
                    <i class="fas fa-times"></i>
                    Fechar
                </button>
            </div>
        </div>
        
    </div>
</div>

<!-- Modal Editar Sala - Layout Padrão -->
<div class="popup-modal" id="modalEditarSala" style="display: none; z-index: 1060 !important;">
    <div class="popup-modal-wrapper" style="max-width: 500px; width: 90vw;">
        
        <!-- HEADER -->
        <div class="popup-modal-header">
            <div class="header-content">
                <div class="header-icon">
                    <i class="fas fa-edit"></i>
                </div>
                <div class="header-text">
                    <h5>Editar Sala</h5>
                    <small>Modifique os dados da sala de aula</small>
                </div>
            </div>
            <button type="button" class="popup-modal-close" onclick="fecharModalEditarSala()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- CONTEÚDO -->
        <div class="popup-modal-content">
            <form id="formEditarSala">
                <input type="hidden" id="editar_sala_id" name="id">
                
                <div class="mb-3">
                    <label for="editar_nome" class="form-label">Nome da Sala *</label>
                    <input type="text" class="form-control" id="editar_nome" name="nome" required 
                           placeholder="Ex: Sala 1, Laboratório, Auditório">
                </div>
                
                <div class="mb-3">
                    <label for="editar_capacidade" class="form-label">Capacidade *</label>
                    <input type="number" class="form-control" id="editar_capacidade" name="capacidade" 
                           min="1" max="100" required>
                    <div class="form-text">Número máximo de alunos que a sala comporta</div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="editar_ativa" name="ativa" value="1">
                        <label class="form-check-label" for="editar_ativa">
                            Sala ativa (disponível para uso)
                        </label>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- FOOTER -->
        <div class="popup-modal-footer">
            <div class="popup-footer-info">
                <small>
                    <i class="fas fa-info-circle"></i>
                    As alterações serão salvas imediatamente
                </small>
            </div>
            <div class="popup-footer-actions">
                <button type="button" class="popup-secondary-button" onclick="fecharModalEditarSala()">
                    <i class="fas fa-times"></i>
                    Cancelar
                </button>
                <button type="button" class="popup-save-button" onclick="salvarEdicaoSala()">
                    <i class="fas fa-save"></i>
                    Salvar Alterações
                </button>
            </div>
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

<!-- Modal Gerenciar Tipos de Curso - Padrão -->
<div class="popup-modal" id="modalGerenciarTiposCurso" style="display: none;">
    <div class="popup-modal-wrapper">
        
        <!-- HEADER -->
        <div class="popup-modal-header">
            <div class="header-content">
                <div class="header-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="header-text">
                    <h5>Gerenciar Cursos</h5>
                    <small>Configure e organize os cursos disponíveis</small>
                </div>
            </div>
            <button type="button" class="popup-modal-close" onclick="fecharModalTiposCurso()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- CONTEÚDO -->
        <div class="popup-modal-content">
            
            <!-- Seção Otimizada - Título, Estatísticas e Botão na mesma linha -->
            <div class="popup-section-header">
                <div class="popup-section-title">
                    <h6>Cursos Cadastrados</h6>
                    <small>Gerencie e organize os cursos do CFC</small>
                </div>
                <div class="popup-stats-item" style="margin: 0;">
                    <div class="popup-stats-icon">
                        <div class="icon-circle">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                    </div>
                    <div class="popup-stats-text">
                        <h6 style="margin: 0;">Total: <span class="stats-number" id="total-tipos-curso">0</span></h6>
                    </div>
                </div>
                <button type="button" class="popup-primary-button" onclick="abrirFormularioNovoTipoCurso()">
                    <i class="fas fa-plus"></i>
                    Novo Curso
                </button>
            </div>
            
            <!-- Conteúdo Principal - Lista de Tipos de Curso -->
            <div id="conteudo-principal-tipos">
                <!-- Grid de Tipos de Curso -->
                <div class="popup-items-grid" id="lista-tipos-curso-modal">
                    <!-- Lista de tipos de curso será carregada via AJAX -->
                    <div class="popup-loading-state show">
                        <div class="popup-loading-spinner"></div>
                        <div class="popup-loading-text">
                            <h6>Carregando cursos...</h6>
                            <p>Aguarde enquanto buscamos os cursos cadastrados</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Formulário Novo Tipo de Curso (oculto inicialmente) -->
            <div id="formulario-novo-tipo-curso" style="display: none;">
                <div class="popup-section-header">
                <div class="popup-section-title">
                    <h6>Novo Curso</h6>
                    <small>Preencha os dados do novo curso</small>
                </div>
                    <button type="button" class="popup-secondary-button" onclick="voltarParaListaTipos()">
                        <i class="fas fa-arrow-left"></i>
                        Voltar
                    </button>
                </div>
                
                <form id="formNovoTipoCursoIntegrado" class="mt-3">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="codigo_tipo_integrado" class="form-label">Código do Curso *</label>
                                <input type="text" class="form-control" id="codigo_tipo_integrado" name="codigo" required placeholder="Ex: formacao_45h, reciclagem_infrator">
                                <small class="text-muted">Use apenas letras, números e underscore</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nome_tipo_integrado" class="form-label">Nome do Curso *</label>
                                <input type="text" class="form-control" id="nome_tipo_integrado" name="nome" required placeholder="Ex: Formação de Condutores">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descricao_tipo_integrado" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao_tipo_integrado" name="descricao" rows="3" placeholder="Descrição detalhada do curso"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="carga_horaria_integrado" class="form-label">Carga Horária Total *</label>
                                <input type="number" class="form-control" id="carga_horaria_integrado" name="carga_horaria_total" min="1" max="200" value="45" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="ativo_tipo_integrado" name="ativo" value="1" checked>
                                    <label class="form-check-label" for="ativo_tipo_integrado">
                                        Tipo de curso ativo
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="button" class="popup-secondary-button" onclick="voltarParaListaTipos()">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </button>
                        <button type="submit" class="popup-save-button">
                            <i class="fas fa-save"></i>
                            Salvar Curso
                        </button>
                    </div>
                </form>
            </div>
            
        </div>
        
        <!-- FOOTER -->
        <div class="popup-modal-footer">
            <div class="popup-footer-info">
                <small>
                    <i class="fas fa-info-circle"></i>
                    As alterações são salvas automaticamente
                </small>
            </div>
            <div class="popup-footer-actions">
                <button type="button" class="popup-secondary-button" onclick="fecharModalTiposCurso()">
                    <i class="fas fa-times"></i>
                    Fechar
                </button>
            </div>
        </div>
        
    </div>
</div>

<!-- Modal Editar Tipo de Curso - Padrão Popup -->
<div class="popup-modal" id="modalEditarTipoCurso" style="display: none;">
    <div class="popup-modal-wrapper">
        
        <!-- HEADER -->
        <div class="popup-modal-header">
            <div class="header-content">
                <div class="header-icon">
                    <i class="fas fa-edit"></i>
                </div>
                <div class="header-text">
                    <h5>Editar Tipo de Curso</h5>
                    <small>Modifique as informações do curso selecionado</small>
                </div>
            </div>
            <button type="button" class="popup-modal-close" onclick="fecharModalEditarTipoCurso()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- CONTEÚDO -->
        <div class="popup-modal-content">
            <form id="formEditarTipoCurso">
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
                    <input type="number" class="form-control" id="editar_carga_horaria" name="carga_horaria_total" min="1" max="200" required onchange="atualizarAuditoriaCargaHoraria()">
                    <div id="auditoria-carga-horaria" class="mt-2" style="display: none;">
                        <div class="alert alert-info mb-2">
                            <i class="fas fa-calculator me-2"></i>
                            <strong>Auditoria de Carga Horária:</strong>
                            <div class="mt-1">
                                <span id="carga-total-curso">0h</span> (Total do Curso) - 
                                <span id="carga-disciplinas-selecionadas">0h</span> (Disciplinas Selecionadas) = 
                                <span id="carga-restante" class="fw-bold">0h</span> (Restante)
                            </div>
                        </div>
                        <div id="alerta-carga-horaria" class="alert alert-warning" style="display: none;">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Atenção:</strong> A soma das disciplinas selecionadas não corresponde à carga horária total do curso!
                        </div>
                        <div id="sucesso-carga-horaria" class="alert alert-success" style="display: none;">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Perfeito!</strong> A carga horária está correta e balanceada.
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="editar_ativo_tipo" name="ativo" value="1">
                        <label class="form-check-label" for="editar_ativo_tipo">
                            Tipo de curso ativo
                        </label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Disciplinas do Curso</label>
                    <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto; background-color: #f8f9fa;">
                        <div class="form-check mb-2">
                            <input class="form-check-input disciplina-checkbox" type="checkbox" value="1" id="disciplina_1" name="disciplinas[]" data-carga-horaria="18" checked onchange="atualizarAuditoriaCargaHoraria()">
                            <label class="form-check-label" for="disciplina_1">
                                <strong>Legislação de Trânsito</strong>
                                <small class="text-muted ms-2">(18h)</small>
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input disciplina-checkbox" type="checkbox" value="2" id="disciplina_2" name="disciplinas[]" data-carga-horaria="16" checked onchange="atualizarAuditoriaCargaHoraria()">
                            <label class="form-check-label" for="disciplina_2">
                                <strong>Direção Defensiva</strong>
                                <small class="text-muted ms-2">(16h)</small>
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input disciplina-checkbox" type="checkbox" value="3" id="disciplina_3" name="disciplinas[]" data-carga-horaria="4" checked onchange="atualizarAuditoriaCargaHoraria()">
                            <label class="form-check-label" for="disciplina_3">
                                <strong>Primeiros Socorros</strong>
                                <small class="text-muted ms-2">(4h)</small>
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input disciplina-checkbox" type="checkbox" value="4" id="disciplina_4" name="disciplinas[]" data-carga-horaria="4" checked onchange="atualizarAuditoriaCargaHoraria()">
                            <label class="form-check-label" for="disciplina_4">
                                <strong>Meio Ambiente e Cidadania</strong>
                                <small class="text-muted ms-2">(4h)</small>
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input disciplina-checkbox" type="checkbox" value="5" id="disciplina_5" name="disciplinas[]" data-carga-horaria="3" onchange="atualizarAuditoriaCargaHoraria()">
                            <label class="form-check-label" for="disciplina_5">
                                <strong>Mecânica Básica</strong>
                                <small class="text-muted ms-2">(3h)</small>
                            </label>
                        </div>
                    </div>
                    <small class="text-muted">Clique nas disciplinas que fazem parte deste curso.</small>
                </div>
                
                <style>
                /* Estilos para os checkboxes de disciplinas */
                .disciplina-checkbox {
                    transform: scale(1.3);
                    margin-right: 10px;
                }
                
                .form-check-label {
                    cursor: pointer;
                    padding-left: 5px;
                    transition: all 0.2s ease;
                }
                
                .form-check-label:hover {
                    background-color: rgba(2, 58, 141, 0.05);
                    border-radius: 4px;
                    padding: 4px 8px;
                    margin: -4px -8px;
                }
                
                #disciplinas-container .form-check {
                    border-bottom: 1px solid #e9ecef;
                    padding: 10px 0;
                    margin-bottom: 0;
                    transition: all 0.2s ease;
                }
                
                #disciplinas-container .form-check:last-child {
                    border-bottom: none;
                }
                
                #disciplinas-container .form-check:hover {
                    background-color: rgba(2, 58, 141, 0.02);
                    border-radius: 6px;
                    margin: 0 -12px;
                    padding: 10px 12px;
                }
                
                .form-check-input:checked {
                    background-color: #023A8D;
                    border-color: #023A8D;
                }
                
                /* Estilos para auditoria de carga horária */
                #auditoria-carga-horaria {
                    transition: all 0.3s ease;
                }
                
                #auditoria-carga-horaria .alert {
                    border: none;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                
                #auditoria-carga-horaria .alert-info {
                    background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
                    border-left: 4px solid #023A8D;
                }
                
                #auditoria-carga-horaria .alert-warning {
                    background: linear-gradient(135deg, #fff3e0 0%, #fce4ec 100%);
                    border-left: 4px solid #ff9800;
                }
                
                #auditoria-carga-horaria .alert-success {
                    background: linear-gradient(135deg, #e8f5e8 0%, #f1f8e9 100%);
                    border-left: 4px solid #4caf50;
                }
                
                #carga-restante {
                    font-size: 1.1em;
                    padding: 2px 6px;
                    border-radius: 4px;
                    background: rgba(255,255,255,0.7);
                }
                
                #carga-restante.text-success {
                    background: rgba(76, 175, 80, 0.1);
                }
                
                #carga-restante.text-warning {
                    background: rgba(255, 152, 0, 0.1);
                }
                
                #carga-restante.text-danger {
                    background: rgba(244, 67, 54, 0.1);
                }
                
                /* CORREÇÃO DO PROBLEMA DE Z-INDEX DO MODAL */
                /* Garantir que quando o modal estiver aberto, elementos da página subjacente não fiquem visíveis */
                body.modal-open {
                    overflow: hidden !important;
                }
                
                /* Forçar z-index baixo para elementos editáveis quando modal estiver aberto */
                body.modal-open .editable-field,
                body.modal-open [data-field],
                body.modal-open .fa-edit,
                body.modal-open .fa-pencil,
                body.modal-open .fa-pencil-alt,
                body.modal-open .btn-edit,
                body.modal-open .edit-icon,
                body.modal-open .popup-item-card-menu,
                body.modal-open .popup-item-card-actions,
                body.modal-open .action-icon-btn,
                body.modal-open .action-buttons-compact,
                body.modal-open .btn-action,
                body.modal-open .btn-sm,
                body.modal-open .btn-outline-secondary,
                body.modal-open .btn-outline-primary,
                body.modal-open .btn-outline-warning,
                body.modal-open .btn-outline-danger,
                body.modal-open .btn-outline-success,
                body.modal-open .btn-outline-info {
                    z-index: 1 !important;
                    position: relative !important;
                }
                
                /* Garantir que o modal e seus elementos tenham z-index máximo */
                #modalGerenciarDisciplinas,
                #modalGerenciarDisciplinas * {
                    z-index: 99999 !important;
                }
                
                #modalGerenciarDisciplinas .modal-backdrop,
                #modalGerenciarDisciplinas::before {
                    z-index: 99998 !important;
                }
                
                /* CORREÇÃO ESPECÍFICA: Forçar z-index baixo para TODOS os elementos da página quando modal estiver aberto */
                body.modal-open *:not(#modalGerenciarDisciplinas):not(#modalGerenciarDisciplinas *) {
                    z-index: 1 !important;
                    position: relative !important;
                }
                
                /* CORREÇÃO ULTRA ESPECÍFICA: Forçar z-index baixo para ícones de edição específicos */
                body.modal-open i.fas.fa-edit,
                body.modal-open i.fas.fa-edit::before,
                body.modal-open i.fas.fa-edit::after,
                body.modal-open .edit-icon,
                body.modal-open .edit-icon::before,
                body.modal-open .edit-icon::after,
                body.modal-open .fa-edit,
                body.modal-open .fa-edit::before,
                body.modal-open .fa-edit::after {
                    z-index: 1 !important;
                    position: relative !important;
                }
                
                /* CORREÇÃO ESPECÍFICA: Forçar z-index baixo para elementos com classe específica */
                body.modal-open [class*="edit"],
                body.modal-open [class*="action"],
                body.modal-open [class*="btn"]:not(#modalGerenciarDisciplinas *),
                body.modal-open [class*="icon"]:not(#modalGerenciarDisciplinas *) {
                    z-index: 1 !important;
                    position: relative !important;
                }
                
                /* CORREÇÃO RADICAL: Esconder completamente os ícones de edição quando modal estiver aberto */
                body.modal-open i.fas.fa-edit:not(#modalGerenciarDisciplinas *),
                body.modal-open .edit-icon:not(#modalGerenciarDisciplinas *),
                body.modal-open .fa-edit:not(#modalGerenciarDisciplinas *) {
                    display: none !important;
                    visibility: hidden !important;
                    opacity: 0 !important;
                    z-index: -1 !important;
                    position: absolute !important;
                    left: -9999px !important;
                    top: -9999px !important;
                }
                
                /* Garantir que elementos específicos da página fiquem atrás do modal */
                body.modal-open .container,
                body.modal-open .row,
                body.modal-open .col,
                body.modal-open .card,
                body.modal-open .card-body,
                body.modal-open .card-header,
                body.modal-open .card-footer,
                body.modal-open .table,
                body.modal-open .table-responsive,
                body.modal-open .btn,
                body.modal-open .form-control,
                body.modal-open .form-select,
                body.modal-open .form-group,
                body.modal-open .form-label,
                body.modal-open .alert,
                body.modal-open .badge,
                body.modal-open .dropdown,
                body.modal-open .dropdown-menu,
                body.modal-open .dropdown-item,
                body.modal-open .nav,
                body.modal-open .nav-item,
                body.modal-open .nav-link,
                body.modal-open .navbar,
                body.modal-open .navbar-brand,
                body.modal-open .navbar-nav,
                body.modal-open .navbar-toggler,
                body.modal-open .sidebar,
                body.modal-open .main-content,
                body.modal-open .content-wrapper {
                    z-index: 1 !important;
                    position: relative !important;
                }
                </style>
            </form>
        </div>
        
        <!-- CSS específico para corrigir z-index dos modais -->
        <link rel="stylesheet" href="assets/css/fix-modal-zindex.css">
        
        <!-- Script específico para corrigir ícones de edição sobre modais -->
        <!-- TEMPORARIAMENTE DESABILITADO - Causava loop infinito e travamento -->
        <!-- <script src="assets/js/fix-modal-icons.js"></script> -->
        
        <!-- FOOTER -->
        <div class="popup-modal-footer">
            <div class="popup-footer-info">
                <small>
                    <i class="fas fa-info-circle"></i>
                    As alterações são salvas automaticamente
                </small>
            </div>
            <div class="popup-footer-actions">
                <button type="button" class="popup-secondary-button" onclick="fecharModalEditarTipoCurso()">
                    <i class="fas fa-times"></i>
                    Cancelar
                </button>
                <button type="button" class="popup-save-button" onclick="salvarEdicaoTipoCurso()">
                    <i class="fas fa-save"></i>
                    Salvar Alterações
                </button>
            </div>
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



<!-- Modal Editar Disciplina - Template Padrão -->
<div class="popup-modal" id="modalEditarDisciplina" style="display: none; z-index: 9999 !important;">
    <div class="popup-modal-wrapper" style="max-width: 600px; width: 90vw;">
        
        <!-- HEADER -->
        <div class="popup-modal-header">
            <div class="header-content">
                <div class="header-icon">
                    <i class="fas fa-edit"></i>
                </div>
                <div class="header-text">
                    <h5>Editar Disciplina</h5>
                    <small>Modifique os dados da disciplina</small>
                </div>
            </div>
            <button type="button" class="popup-modal-close" onclick="fecharModalEditarDisciplina()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- CONTEÚDO -->
        <div class="popup-modal-content">
            <form id="formEditarDisciplina">
                <input type="hidden" id="edit_id" name="id">
                
                <div class="mb-3">
                    <label for="edit_codigo" class="form-label">Código *</label>
                    <input type="text" class="form-control" id="edit_codigo" name="codigo" required 
                           placeholder="Ex: direcao_defensiva, legislacao_transito">
                    <div class="form-text">Use apenas letras, números e underscore</div>
                </div>
                
                <div class="mb-3">
                    <label for="edit_nome" class="form-label">Nome *</label>
                    <input type="text" class="form-control" id="edit_nome" name="nome" required 
                           placeholder="Ex: Direção Defensiva">
                </div>
                
                <div class="mb-3">
                    <label for="edit_descricao" class="form-label">Descrição</label>
                    <textarea class="form-control" id="edit_descricao" name="descricao" rows="3" 
                              placeholder="Descrição detalhada da disciplina"></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="edit_carga_horaria_padrao" class="form-label">Carga Horária Padrão</label>
                            <input type="number" class="form-control" id="edit_carga_horaria_padrao" 
                                   name="carga_horaria_padrao" min="1" max="200">
                            <div class="form-text">Horas padrão para esta disciplina</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="edit_cor_hex" class="form-label">Cor</label>
                            <input type="color" class="form-control" id="edit_cor_hex" name="cor_hex" 
                                   style="height: 38px;">
                            <div class="form-text">Cor de identificação da disciplina</div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="edit_icone" class="form-label">Ícone</label>
                    <select class="form-control" id="edit_icone" name="icone">
                        <option value="book">📚 Livro</option>
                        <option value="gavel">⚖️ Martelo</option>
                        <option value="shield-alt">🛡️ Escudo</option>
                        <option value="first-aid">🚑 Primeiros Socorros</option>
                        <option value="leaf">🍃 Folha</option>
                        <option value="wrench">🔧 Chave</option>
                        <option value="car">🚗 Carro</option>
                        <option value="road">🛣️ Estrada</option>
                    </select>
                    <div class="form-text">Ícone de identificação da disciplina</div>
                </div>
            </form>
        </div>
        
        <!-- FOOTER -->
        <div class="popup-modal-footer">
            <div class="popup-footer-info">
                <small>
                    <i class="fas fa-info-circle"></i>
                    As alterações são salvas automaticamente
                </small>
            </div>
            <div class="popup-footer-actions">
                <button type="button" class="popup-secondary-button" onclick="fecharModalEditarDisciplina()">
                    <i class="fas fa-times"></i>
                    Cancelar
                </button>
                <button type="submit" form="formEditarDisciplina" class="popup-save-button">
                    <i class="fas fa-save"></i>
                    Salvar Alterações
                </button>
            </div>
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
// Função para abrir modal de gerenciamento de salas (padrão)
function abrirModalSalasInterno() {
    console.log('🔧 Tentando abrir modal de salas...');
    const popup = document.getElementById('modalGerenciarSalas');
    if (popup) {
        console.log('✅ Modal encontrado, abrindo...');
        popup.style.display = 'flex';
        popup.classList.add('show', 'popup-fade-in');
        document.body.style.overflow = 'hidden';
        recarregarSalas();
    } else {
        console.error('❌ Modal modalGerenciarSalas não encontrado');
    }
}

// Função para fechar modal de gerenciamento de salas (padrão)
function fecharModalSalas() {
    const popup = document.getElementById('modalGerenciarSalas');
    if (popup) {
        popup.classList.remove('show');
        popup.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Event listeners para fechar modal com ESC e backdrop
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modalSalas = document.getElementById('modalGerenciarSalas');
        const modalTipos = document.getElementById('modalGerenciarTiposCurso');
        const modalDisciplinas = document.getElementById('modalGerenciarDisciplinas');
        const modalEditarTipo = document.getElementById('modalEditarTipoCurso');
        const modalEditarSala = document.getElementById('modalEditarSala');
        
        if (modalSalas && modalSalas.classList.contains('show')) {
            fecharModalSalas();
        } else if (modalTipos && modalTipos.classList.contains('show')) {
            fecharModalTiposCurso();
        } else if (modalDisciplinas && modalDisciplinas.classList.contains('show')) {
            fecharModalDisciplinas();
        } else if (modalEditarTipo && modalEditarTipo.classList.contains('show')) {
            fecharModalEditarTipoCurso();
        } else if (modalEditarSala && modalEditarSala.classList.contains('show')) {
            fecharModalEditarSala();
        } else {
            // Se nenhum modal específico estiver aberto, limpar tudo
            limparModaisAntigos();
        }
    }
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('popup-modal')) {
        // Verificar qual modal está aberto
        const modalSalas = document.getElementById('modalGerenciarSalas');
        const modalTipos = document.getElementById('modalGerenciarTiposCurso');
        const modalDisciplinas = document.getElementById('modalGerenciarDisciplinas');
        const modalEditarTipo = document.getElementById('modalEditarTipoCurso');
        
        if (modalSalas && modalSalas.classList.contains('show')) {
            fecharModalSalas();
        } else if (modalTipos && modalTipos.classList.contains('show')) {
            fecharModalTiposCurso();
        } else if (modalDisciplinas && modalDisciplinas.classList.contains('show')) {
            fecharModalDisciplinas();
        } else if (modalEditarTipo && modalEditarTipo.classList.contains('show')) {
            fecharModalEditarTipoCurso();
        } else {
            // Se nenhum modal específico estiver aberto, limpar tudo
            limparModaisAntigos();
        }
    }
});

// Função para recarregar lista de salas via AJAX
function recarregarSalas() {
    console.log('🔄 Iniciando carregamento de salas...');
    
    // Mostrar loading state
    const salasContainer = document.getElementById('lista-salas-modal');
    if (!salasContainer) {
        console.error('❌ Container lista-salas-modal não encontrado');
        return;
    }
    
    salasContainer.innerHTML = `
        <div class="popup-loading-state show">
            <div class="popup-loading-spinner"></div>
            <div class="popup-loading-text">
                <h6>Carregando salas...</h6>
                <p>Aguarde enquanto buscamos suas salas cadastradas</p>
            </div>
        </div>
    `;
    
    console.log('📡 Fazendo requisição para API...');
    fetch(getBasePath() + '/admin/api/salas-clean.php?acao=listar')
        .then(response => {
            console.log('📥 Resposta recebida:', response.status, response.statusText);
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            // Verificar se a resposta é realmente JSON
            const contentType = response.headers.get('content-type');
            console.log('📄 Content-Type:', contentType);
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Resposta não é JSON válido. Content-Type: ' + contentType);
            }
            return response.text().then(text => {
                console.log('📝 Texto recebido:', text.substring(0, 200) + '...');
                try {
                    const data = JSON.parse(text);
                    console.log('✅ JSON parseado com sucesso:', data);
                    return data;
                } catch (e) {
                    console.error('❌ Erro ao parsear JSON:', e);
                    console.error('📝 Texto completo:', text);
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
                    selectSala.innerHTML += '<option value="' + sala.id + '">' + sala.nome + ' (Capacidade: ' + sala.capacidade + ' alunos)</option>';
                });
                
                // Atualizar contador de salas no modal
                const totalSalas = document.getElementById('total-salas');
                if (totalSalas) {
                    totalSalas.textContent = data.salas.length;
                }
                
                // Atualizar lista no modal com o novo padrão
                if (data.salas.length === 0) {
                    salasContainer.innerHTML = `
                        <div class="popup-empty-state show">
                            <div class="empty-icon">
                                <i class="fas fa-door-open"></i>
                            </div>
                            <h5>Nenhuma sala encontrada</h5>
                            <p>Crie sua primeira sala de aula para começar</p>
                            <button type="button" class="popup-primary-button" onclick="abrirModalNovaSalaInterno()">
                                <i class="fas fa-plus"></i>
                                Criar Primeira Sala
                            </button>
                        </div>
                    `;
                } else {
                    // Converter HTML das salas para o novo padrão
                    let htmlSalas = '';
                    data.salas.forEach(sala => {
                        const statusClass = sala.ativa == 1 ? 'active' : '';
                        const statusText = sala.ativa == 1 ? 'ATIVA' : 'INATIVA';
                        const statusColor = sala.ativa == 1 ? '#28a745' : '#6c757d';
                        
                        htmlSalas += `
                            <div class="popup-item-card ${statusClass}">
                                <div class="popup-item-card-header">
                                    <div class="popup-item-card-content">
                                        <h6 class="popup-item-card-title">${sala.nome}</h6>
                                        <div class="popup-item-card-code" style="background: ${statusColor}; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: bold;">
                                            ${statusText}
                                        </div>
                                        <div class="popup-item-card-description" style="margin-top: 0.5rem;">
                                            <i class="fas fa-users" style="color: #6c757d; margin-right: 0.5rem;"></i>
                                            Capacidade: ${sala.capacidade} alunos
                                        </div>
                                    </div>
                                    <div class="popup-item-card-actions">
                                        <button type="button" class="popup-item-card-menu" onclick="editarSala(${sala.id}, '${sala.nome}', ${sala.capacidade}, ${sala.ativa})" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="popup-item-card-menu" onclick="confirmarExclusaoSala(${sala.id}, '${sala.nome}')" title="Excluir" style="color: #dc3545;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    
                    salasContainer.innerHTML = htmlSalas;
                }
                
                // Atualizar contador na página principal
                const smallText = document.querySelector('small.text-muted');
                if (smallText) {
                    smallText.innerHTML = '<i class="fas fa-info-circle me-1"></i>' + data.salas.length + ' sala(s) cadastrada(s) - <a href="#" onclick="abrirModalSalasInterno()" class="text-primary">Clique aqui para gerenciar</a>';
                }
            } else {
                console.error('Erro na resposta:', data.mensagem);
                salasContainer.innerHTML = `
                    <div class="popup-error-state show">
                        <div class="error-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h5>Erro ao carregar salas</h5>
                        <p>${data.mensagem}</p>
                        <button type="button" class="popup-secondary-button" onclick="recarregarSalas()">
                            <i class="fas fa-redo"></i>
                            Tentar Novamente
                        </button>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Erro ao recarregar salas:', error);
            salasContainer.innerHTML = `
                <div class="popup-error-state show">
                    <div class="error-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h5>Erro de conexão</h5>
                    <p>${error.message || 'Não foi possível conectar ao servidor'}</p>
                    <button type="button" class="popup-secondary-button" onclick="recarregarSalas()">
                        <i class="fas fa-redo"></i>
                        Tentar Novamente
                    </button>
                </div>
            `;
        });
}

// Função para abrir formulário de nova sala (integrado)
function abrirModalNovaSalaInterno() {
    console.log('🔧 Abrindo formulário Nova Sala integrado...');
    
    // Esconder conteúdo principal
    const conteudoPrincipal = document.getElementById('conteudo-principal');
    const formularioNovaSala = document.getElementById('formulario-nova-sala');
    
    if (conteudoPrincipal && formularioNovaSala) {
        conteudoPrincipal.style.display = 'none';
        formularioNovaSala.style.display = 'block';
        
        // Limpar formulário
        document.getElementById('formNovaSalaIntegrado').reset();
        document.getElementById('capacidade_sala_integrado').value = '30';
        
        // Focar no primeiro campo
        document.getElementById('nome_sala_integrado').focus();
    } else {
        console.error('❌ Elementos do formulário não encontrados');
    }
}

// Função para voltar para a lista de salas
function voltarParaLista() {
    console.log('🔧 Voltando para lista de salas...');
    
    const conteudoPrincipal = document.getElementById('conteudo-principal');
    const formularioNovaSala = document.getElementById('formulario-nova-sala');
    
    if (conteudoPrincipal && formularioNovaSala) {
        formularioNovaSala.style.display = 'none';
        conteudoPrincipal.style.display = 'block';
    }
}

// Event listener para o formulário principal de turmas
document.addEventListener('DOMContentLoaded', function() {
    const formTurmaTeorica = document.getElementById('formTurmaTeorica');
    if (formTurmaTeorica) {
        formTurmaTeorica.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('🎯 Formulário de turma submetido - chamando criarTurmaComDisciplinas');
            criarTurmaComDisciplinas();
        });
    }
});

// Event listener para o formulário integrado
document.addEventListener('DOMContentLoaded', function() {
    const formNovaSalaIntegrado = document.getElementById('formNovaSalaIntegrado');
    if (formNovaSalaIntegrado) {
        formNovaSalaIntegrado.addEventListener('submit', function(e) {
            e.preventDefault();
            salvarNovaSalaIntegrada();
        });
    }
});

// Função para salvar nova sala (integrada)
function salvarNovaSalaIntegrada() {
    console.log('💾 Salvando nova sala integrada...');
    
    const form = document.getElementById('formNovaSalaIntegrado');
    const formData = new FormData(form);
    
    // Adicionar equipamentos
    const equipamentos = {};
    const checkboxes = form.querySelectorAll('input[name^="equipamentos"]:checked');
    checkboxes.forEach(checkbox => {
        const key = checkbox.name.replace('equipamentos[', '').replace(']', '');
        equipamentos[key] = true;
    });
    formData.set('equipamentos', JSON.stringify(equipamentos));
    
        fetch(getBasePath() + '/admin/api/salas-clean.php?acao=criar', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            console.log('✅ Sala criada com sucesso!');
            
            // Voltar para a lista
            voltarParaLista();
            
            // Recarregar salas
            recarregarSalas();
            
            // Mostrar mensagem de sucesso
            const salasContainer = document.getElementById('lista-salas-modal');
            salasContainer.innerHTML = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Sucesso!</strong> Sala "${data.sala.nome}" criada com sucesso!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // Remover mensagem após 3 segundos
            setTimeout(() => {
                recarregarSalas();
            }, 3000);
        } else {
            console.error('❌ Erro ao criar sala:', data.mensagem);
            alert('Erro ao criar sala: ' + data.mensagem);
        }
    })
    .catch(error => {
        console.error('❌ Erro na requisição:', error);
        alert('Erro ao salvar sala: ' + error.message);
    });
}

// Função para editar sala
function editarSala(id, nome, capacidade, ativa) {
    document.getElementById('editar_sala_id').value = id;
    document.getElementById('editar_nome').value = nome;
    document.getElementById('editar_capacidade').value = capacidade;
    document.getElementById('editar_ativa').checked = ativa == 1;
    
    // Abrir modal popup
    const modal = document.getElementById('modalEditarSala');
    modal.style.display = 'flex';
    modal.classList.add('show', 'popup-fade-in');
    document.body.style.overflow = 'hidden';
}

// Função para fechar modal de editar sala
function fecharModalEditarSala() {
    const modal = document.getElementById('modalEditarSala');
    if (modal) {
        modal.classList.remove('show');
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Função para salvar edição de sala
function salvarEdicaoSala() {
    const formData = new FormData(document.getElementById('formEditarSala'));
    formData.append('acao', 'editar');
    
        fetch(getBasePath() + '/admin/api/salas-clean.php', {
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
            fecharModalEditarSala();
            
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
}

// Função para confirmar exclusão de sala
function confirmarExclusaoSala(id, nome) {
    console.log('🗑️ Confirmando exclusão da sala:', nome, 'ID:', id);
    
    if (confirm('Tem certeza que deseja excluir a sala "' + nome + '"?\n\nEsta ação não pode ser desfeita.')) {
        excluirSala(id, nome);
    }
}

// Função para excluir sala
function excluirSala(id, nome) {
    console.log('🗑️ Excluindo sala:', nome, 'ID:', id);
    
    // Mostrar loading
    const salasContainer = document.getElementById('lista-salas-modal');
    salasContainer.innerHTML = `
        <div class="popup-loading-state show">
            <div class="popup-loading-spinner"></div>
            <div class="popup-loading-text">
                <h6>Excluindo sala...</h6>
                <p>Aguarde enquanto processamos a exclusão</p>
            </div>
        </div>
    `;
    
    fetch(getBasePath() + '/admin/api/salas-clean.php?acao=excluir', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            console.log('✅ Sala excluída com sucesso!');
            
            // Mostrar mensagem de sucesso
            salasContainer.innerHTML = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Sucesso!</strong> Sala "${nome}" foi excluída com sucesso!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // Recarregar salas após 2 segundos
            setTimeout(() => {
                recarregarSalas();
            }, 2000);
        } else {
            console.error('❌ Erro ao excluir sala:', data.mensagem);
            salasContainer.innerHTML = `
                <div class="popup-error-state show">
                    <div class="error-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h5>Erro ao excluir sala</h5>
                    <p>${data.mensagem}</p>
                    <button type="button" class="popup-secondary-button" onclick="recarregarSalas()">
                        <i class="fas fa-redo"></i>
                        Voltar à Lista
                    </button>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('❌ Erro na requisição:', error);
        salasContainer.innerHTML = `
            <div class="popup-error-state show">
                <div class="error-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h5>Erro de conexão</h5>
                <p>Não foi possível conectar ao servidor</p>
                <button type="button" class="popup-secondary-button" onclick="recarregarSalas()">
                    <i class="fas fa-redo"></i>
                    Tentar Novamente
                </button>
            </div>
        `;
    });
}

// Função para confirmar exclusão (versão antiga - removida)
// function confirmarExclusao() {
//     const id = document.getElementById('excluir_sala_id').value;
//     const nome = document.getElementById('nome_sala_exclusao').textContent;
//     const modal = new bootstrap.Modal(document.getElementById('modalConfirmarExclusao'));
//     modal.show();
// }

// Função para abrir modal de gerenciamento de cursos (padrão)
function abrirModalTiposCursoInterno() {
    console.log('🔧 Tentando abrir modal de cursos...');
    const popup = document.getElementById('modalGerenciarTiposCurso');
    if (popup) {
        console.log('✅ Modal encontrado, abrindo...');
        popup.style.display = 'flex';
        popup.classList.add('show', 'popup-fade-in');
        document.body.style.overflow = 'hidden';
        recarregarTiposCurso();
    } else {
        console.error('❌ Modal modalGerenciarTiposCurso não encontrado');
    }
}

// Função para fechar modal de gerenciamento de cursos (padrão)
function fecharModalTiposCurso() {
    console.log('🔧 Fechando modal de cursos...');
    const popup = document.getElementById('modalGerenciarTiposCurso');
    if (popup) {
        popup.classList.remove('show');
        popup.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Função para abrir formulário de novo curso (integrado)
function abrirFormularioNovoTipoCurso() {
    console.log('🔧 Abrindo formulário Novo Curso integrado...');
    
    // Esconder conteúdo principal
    const conteudoPrincipal = document.getElementById('conteudo-principal-tipos');
    const formularioNovoTipo = document.getElementById('formulario-novo-tipo-curso');
    
    if (conteudoPrincipal && formularioNovoTipo) {
        conteudoPrincipal.style.display = 'none';
        formularioNovoTipo.style.display = 'block';
        
        // Limpar formulário
        document.getElementById('formNovoTipoCursoIntegrado').reset();
        document.getElementById('carga_horaria_integrado').value = '45';
        document.getElementById('ativo_tipo_integrado').checked = true;
        
        // Focar no primeiro campo
        document.getElementById('codigo_tipo_integrado').focus();
    } else {
        console.error('❌ Elementos do formulário não encontrados');
    }
}

// Função para voltar para a lista de cursos
function voltarParaListaTipos() {
    console.log('🔧 Voltando para lista de cursos...');
    
    const conteudoPrincipal = document.getElementById('conteudo-principal-tipos');
    const formularioNovoTipo = document.getElementById('formulario-novo-tipo-curso');
    
    if (conteudoPrincipal && formularioNovoTipo) {
        formularioNovoTipo.style.display = 'none';
        conteudoPrincipal.style.display = 'block';
    }
}

// Função para recarregar lista de cursos via AJAX
function recarregarTiposCurso() {
    console.log('🔄 Iniciando carregamento de cursos...');
    
    // Mostrar loading state
    const tiposContainer = document.getElementById('lista-tipos-curso-modal');
    if (!tiposContainer) {
        console.error('❌ Container lista-tipos-curso-modal não encontrado');
        return;
    }
    
    tiposContainer.innerHTML = `
        <div class="popup-loading-state show">
            <div class="popup-loading-spinner"></div>
                        <div class="popup-loading-text">
                            <h6>Carregando cursos...</h6>
                            <p>Aguarde enquanto buscamos os cursos cadastrados</p>
                        </div>
        </div>
    `;
    
    console.log('📡 Fazendo requisição para API...');
    fetch(getBasePath() + '/admin/api/tipos-curso-clean.php?acao=listar')
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
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
            console.log('✅ Dados recebidos:', data);
            if (data.sucesso) {
                const selectCurso = document.getElementById('curso_tipo');
                const tiposContainer = document.getElementById('lista-tipos-curso-modal');
                
                // Atualizar contador de tipos no modal
                const totalTipos = document.getElementById('total-tipos-curso');
                if (totalTipos) {
                    totalTipos.textContent = data.tipos.length;
                }
                
                // Atualizar dropdown
                if (selectCurso) {
                    selectCurso.innerHTML = '<option value="">Selecione o tipo de curso...</option>';
                    data.tipos.forEach(tipo => {
                        selectCurso.innerHTML += '<option value="' + tipo.codigo + '">' + tipo.nome + '</option>';
                    });
                }
                
                // Atualizar lista no modal com o novo padrão
                if (data.tipos.length === 0) {
                    tiposContainer.innerHTML = `
                        <div class="popup-empty-state show">
                            <div class="empty-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <h5>Nenhum curso encontrado</h5>
                            <p>Crie seu primeiro curso para começar</p>
                            <button type="button" class="popup-primary-button" onclick="abrirFormularioNovoTipoCurso()">
                                <i class="fas fa-plus"></i>
                                Criar Primeiro Curso
                            </button>
                        </div>
                    `;
                } else {
                    // Converter HTML dos tipos para o novo padrão
                    let htmlTipos = '';
                    data.tipos.forEach(tipo => {
                        const statusClass = tipo.ativo == 1 ? 'active' : '';
                        const statusText = tipo.ativo == 1 ? 'ATIVO' : 'INATIVO';
                        const statusColor = tipo.ativo == 1 ? '#28a745' : '#6c757d';
                        
                        htmlTipos += `
                            <div class="popup-item-card ${statusClass}">
                                <div class="popup-item-card-header">
                                    <div class="popup-item-card-content">
                                        <h6 class="popup-item-card-title">${tipo.nome}</h6>
                                        <div class="popup-item-card-code" style="background: ${statusColor}; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: bold;">
                                            ${statusText}
                                        </div>
                                        <div class="popup-item-card-description" style="margin-top: 0.5rem;">
                                            <div><strong>Código:</strong> ${tipo.codigo}</div>
                                            <div><strong>Carga Horária:</strong> ${tipo.carga_horaria_total} horas</div>
                                            ${tipo.descricao ? '<div><strong>Descrição:</strong> ' + tipo.descricao + '</div>' : ''}
                                        </div>
                                    </div>
                                    <div class="popup-item-card-actions">
                                        <button type="button" class="popup-item-card-menu" onclick="editarTipoCurso(${tipo.id}, '${tipo.codigo}', '${tipo.nome}', '${tipo.descricao || ''}', ${tipo.carga_horaria_total}, ${tipo.ativo})" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="popup-item-card-menu" onclick="confirmarExclusaoTipoCurso(${tipo.id}, '${tipo.nome}')" title="Excluir" style="color: #dc3545;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    
                    tiposContainer.innerHTML = htmlTipos;
                }
                
                // Atualizar contador na página principal
                const smallText = document.querySelector('small.text-muted');
                if (smallText && smallText.textContent.includes('curso(s) cadastrado(s)')) {
                    smallText.innerHTML = '<i class="fas fa-info-circle me-1"></i>' + data.tipos.length + ' curso(s) cadastrado(s) - <a href="#" onclick="abrirModalTiposCursoInterno()" class="text-primary">Clique aqui para gerenciar</a>';
                }
            } else {
                console.error('❌ Erro na resposta:', data.mensagem);
                tiposContainer.innerHTML = `
                    <div class="popup-error-state show">
                        <div class="error-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h5>Erro ao carregar cursos</h5>
                        <p>${data.mensagem}</p>
                        <button type="button" class="popup-secondary-button" onclick="recarregarTiposCurso()">
                            <i class="fas fa-redo"></i>
                            Tentar Novamente
                        </button>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('❌ Erro ao recarregar cursos:', error);
            tiposContainer.innerHTML = `
                <div class="popup-error-state show">
                    <div class="error-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h5>Erro de conexão</h5>
                    <p>${error.message || 'Não foi possível conectar ao servidor'}</p>
                    <button type="button" class="popup-secondary-button" onclick="recarregarTiposCurso()">
                        <i class="fas fa-redo"></i>
                        Tentar Novamente
                    </button>
                </div>
            `;
        });
}

// Event listener para o formulário integrado de tipos de curso
document.addEventListener('DOMContentLoaded', function() {
    const formNovoTipoCursoIntegrado = document.getElementById('formNovoTipoCursoIntegrado');
    if (formNovoTipoCursoIntegrado) {
        formNovoTipoCursoIntegrado.addEventListener('submit', function(e) {
            e.preventDefault();
            salvarNovoTipoCursoIntegrado();
        });
    }
});

// Função para salvar novo curso (integrada)
function salvarNovoTipoCursoIntegrado() {
    console.log('💾 Salvando novo curso integrado...');
    
    const form = document.getElementById('formNovoTipoCursoIntegrado');
    const formData = new FormData(form);
    
    fetch(getBasePath() + '/admin/api/tipos-curso-clean.php?acao=criar', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            console.log('✅ Curso criado com sucesso!');
            
            // Voltar para a lista
            voltarParaListaTipos();
            
            // Recarregar tipos
            recarregarTiposCurso();
            
            // Mostrar mensagem de sucesso
            const tiposContainer = document.getElementById('lista-tipos-curso-modal');
            tiposContainer.innerHTML = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Sucesso!</strong> Curso "${data.tipo.nome}" criado com sucesso!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // Remover mensagem após 3 segundos
            setTimeout(() => {
                recarregarTiposCurso();
            }, 3000);
        } else {
            console.error('❌ Erro ao criar curso:', data.mensagem);
            alert('Erro ao criar curso: ' + data.mensagem);
        }
    })
    .catch(error => {
        console.error('❌ Erro na requisição:', error);
        alert('Erro ao salvar curso: ' + error.message);
    });
}

// Função para confirmar exclusão de curso
function confirmarExclusaoTipoCurso(id, nome) {
    console.log('🗑️ Confirmando exclusão do curso:', nome, 'ID:', id);
    
    if (confirm('Tem certeza que deseja excluir o curso "' + nome + '"?\n\nEsta ação não pode ser desfeita.')) {
        excluirTipoCurso(id, nome);
    }
}

// Função para excluir curso
function excluirTipoCurso(id, nome) {
    console.log('🗑️ Excluindo curso:', nome, 'ID:', id);
    
    // Mostrar loading
    const tiposContainer = document.getElementById('lista-tipos-curso-modal');
    tiposContainer.innerHTML = `
        <div class="popup-loading-state show">
            <div class="popup-loading-spinner"></div>
            <div class="popup-loading-text">
                <h6>Excluindo curso...</h6>
                <p>Aguarde enquanto processamos a exclusão</p>
            </div>
        </div>
    `;
    
    fetch(getBasePath() + '/admin/api/tipos-curso-clean.php?acao=excluir', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            console.log('✅ Curso excluído com sucesso!');
            
            // Mostrar mensagem de sucesso
            tiposContainer.innerHTML = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Sucesso!</strong> Curso "${nome}" foi excluído com sucesso!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // Recarregar tipos após 2 segundos
            setTimeout(() => {
                recarregarTiposCurso();
            }, 2000);
        } else {
            console.error('❌ Erro ao excluir curso:', data.mensagem);
            tiposContainer.innerHTML = `
                <div class="popup-error-state show">
                    <div class="error-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h5>Erro ao excluir curso</h5>
                    <p>${data.mensagem}</p>
                    <button type="button" class="popup-secondary-button" onclick="recarregarTiposCurso()">
                        <i class="fas fa-redo"></i>
                        Voltar à Lista
                    </button>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('❌ Erro na requisição:', error);
        tiposContainer.innerHTML = `
            <div class="popup-error-state show">
                <div class="error-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h5>Erro de conexão</h5>
                <p>Não foi possível conectar ao servidor</p>
                <button type="button" class="popup-secondary-button" onclick="recarregarTiposCurso()">
                    <i class="fas fa-redo"></i>
                    Tentar Novamente
                </button>
            </div>
        `;
    });
}

// Função para editar tipo de curso - VERSÃO SIMPLIFICADA
function editarTipoCurso(id, codigo, nome, descricao, carga_horaria_total, ativo) {
    // Preencher campos do formulário
    document.getElementById('editar_tipo_curso_id').value = id;
    document.getElementById('editar_codigo').value = codigo;
    document.getElementById('editar_nome_tipo').value = nome;
    document.getElementById('editar_descricao_tipo').value = descricao;
    document.getElementById('editar_carga_horaria').value = carga_horaria_total;
    document.getElementById('editar_ativo_tipo').checked = ativo == 1;
    
    // Carregar disciplinas salvas do banco
    carregarDisciplinasSalvas(codigo);
    
    // Atualizar auditoria de carga horária após carregar os dados
    setTimeout(() => {
        atualizarAuditoriaCargaHoraria();
    }, 100);
    
    // Abrir modal
    const popup = document.getElementById('modalEditarTipoCurso');
    if (popup) {
        popup.style.display = 'flex';
        popup.classList.add('show', 'popup-fade-in');
        document.body.style.overflow = 'hidden';
    }
}

// Função para carregar disciplinas salvas do banco
function carregarDisciplinasSalvas(codigoCurso) {
    // Primeiro, desmarcar todas as disciplinas
    document.querySelectorAll('.disciplina-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Buscar disciplinas salvas no banco
    fetch(`./api/disciplinas-curso.php?acao=buscar&codigo=${encodeURIComponent(codigoCurso)}`)
        .then(response => response.json())
        .then(data => {
            if (data.sucesso && data.disciplinas_selecionadas) {
                // Marcar disciplinas salvas
                data.disciplinas_selecionadas.forEach(disciplinaId => {
                    const checkbox = document.getElementById(`disciplina_${disciplinaId}`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
                console.log('✅ Disciplinas carregadas do banco:', data.disciplinas_selecionadas);
                
                // Atualizar auditoria após carregar disciplinas
                atualizarAuditoriaCargaHoraria();
            } else {
                // Se não houver disciplinas salvas, usar configuração padrão
                configurarDisciplinasPorCurso(codigoCurso);
                console.log('⚠️ Usando configuração padrão para curso:', codigoCurso);
                
                // Atualizar auditoria após configurar disciplinas padrão
                atualizarAuditoriaCargaHoraria();
            }
        })
        .catch(error => {
            console.error('❌ Erro ao carregar disciplinas:', error);
            // Em caso de erro, usar configuração padrão
            configurarDisciplinasPorCurso(codigoCurso);
            
            // Atualizar auditoria após configurar disciplinas padrão
            atualizarAuditoriaCargaHoraria();
        });
}

// Função simples para configurar disciplinas
function configurarDisciplinasPorCurso(codigoCurso) {
    // Desmarcar todas as disciplinas primeiro
    document.querySelectorAll('.disciplina-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Mapear códigos de curso para disciplinas
    const disciplinasPorCurso = {
        'formacao_45h': [1, 2, 3, 4, 5], // Todas as disciplinas
        'formacao_acc_20h': [1, 2, 3, 4], // Sem mecânica básica
        'reciclagem_infrator': [1, 2], // Apenas legislação e direção defensiva
        'atualizacao': [1] // Apenas legislação
    };
    
    const disciplinasSelecionadas = disciplinasPorCurso[codigoCurso] || [];
    
    // Marcar disciplinas selecionadas
    disciplinasSelecionadas.forEach(disciplinaId => {
        const checkbox = document.getElementById(`disciplina_${disciplinaId}`);
        if (checkbox) {
            checkbox.checked = true;
        }
    });
    
    // Atualizar auditoria após configurar disciplinas
    atualizarAuditoriaCargaHoraria();
}

// Função para fechar modal de edição de tipo de curso
function fecharModalEditarTipoCurso() {
    const popup = document.getElementById('modalEditarTipoCurso');
    if (popup) {
        popup.classList.remove('show');
        popup.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Função para atualizar auditoria de carga horária
function atualizarAuditoriaCargaHoraria() {
    console.log('🔄 Atualizando auditoria de carga horária...');
    
    // Obter carga horária total do curso
    const cargaHorariaTotal = parseInt(document.getElementById('editar_carga_horaria').value) || 0;
    
    // Calcular carga horária das disciplinas selecionadas
    const disciplinasSelecionadas = document.querySelectorAll('.disciplina-checkbox:checked');
    let cargaHorariaDisciplinas = 0;
    
    disciplinasSelecionadas.forEach(checkbox => {
        const cargaHoraria = parseInt(checkbox.getAttribute('data-carga-horaria')) || 0;
        cargaHorariaDisciplinas += cargaHoraria;
    });
    
    // Calcular carga horária restante
    const cargaHorariaRestante = cargaHorariaTotal - cargaHorariaDisciplinas;
    
    // Atualizar elementos da interface
    const auditoriaDiv = document.getElementById('auditoria-carga-horaria');
    const cargaTotalElement = document.getElementById('carga-total-curso');
    const cargaDisciplinasElement = document.getElementById('carga-disciplinas-selecionadas');
    const cargaRestanteElement = document.getElementById('carga-restante');
    const alertaElement = document.getElementById('alerta-carga-horaria');
    const sucessoElement = document.getElementById('sucesso-carga-horaria');
    
    if (cargaHorariaTotal > 0) {
        // Mostrar seção de auditoria
        auditoriaDiv.style.display = 'block';
        
        // Atualizar valores
        cargaTotalElement.textContent = cargaHorariaTotal + 'h';
        cargaDisciplinasElement.textContent = cargaHorariaDisciplinas + 'h';
        cargaRestanteElement.textContent = cargaHorariaRestante + 'h';
        
        // Verificar se está balanceado
        if (cargaHorariaRestante === 0) {
            // Perfeito - carga horária balanceada
            alertaElement.style.display = 'none';
            sucessoElement.style.display = 'block';
            cargaRestanteElement.className = 'fw-bold text-success';
        } else if (cargaHorariaRestante > 0) {
            // Sobra carga horária
            alertaElement.style.display = 'block';
            sucessoElement.style.display = 'none';
            cargaRestanteElement.className = 'fw-bold text-warning';
            alertaElement.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i><strong>Atenção:</strong> Ainda restam ' + cargaHorariaRestante + 'h não atribuídas às disciplinas!';
        } else {
            // Excede carga horária
            alertaElement.style.display = 'block';
            sucessoElement.style.display = 'none';
            cargaRestanteElement.className = 'fw-bold text-danger';
            alertaElement.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i><strong>Atenção:</strong> As disciplinas selecionadas excedem a carga horária total em ' + Math.abs(cargaHorariaRestante) + 'h!';
        }
    } else {
        // Ocultar seção de auditoria se não há carga horária definida
        auditoriaDiv.style.display = 'none';
    }
    
    console.log('📊 Auditoria atualizada:', {
        total: cargaHorariaTotal,
        disciplinas: cargaHorariaDisciplinas,
        restante: cargaHorariaRestante
    });
}

// Função de teste para auditoria de carga horária
window.testarAuditoriaCargaHoraria = function() {
    console.log('🧪 Testando auditoria de carga horária...');
    
    // Simular dados de teste
    const cargaHorariaInput = document.getElementById('editar_carga_horaria');
    if (cargaHorariaInput) {
        cargaHorariaInput.value = '45';
        console.log('✅ Carga horária definida para 45h');
        
        // Simular seleção de disciplinas
        const disciplinas = document.querySelectorAll('.disciplina-checkbox');
        disciplinas.forEach((checkbox, index) => {
            if (index < 4) { // Selecionar as primeiras 4 disciplinas
                checkbox.checked = true;
            } else {
                checkbox.checked = false;
            }
        });
        console.log('✅ Disciplinas configuradas para teste');
        
        // Executar auditoria
        atualizarAuditoriaCargaHoraria();
        console.log('✅ Auditoria executada');
    } else {
        console.error('❌ Modal de editar tipo de curso não está aberto');
    }
};

// Função para salvar edição de tipo de curso
function salvarEdicaoTipoCurso() {
    // Verificar auditoria de carga horária antes de salvar
    const cargaHorariaTotal = parseInt(document.getElementById('editar_carga_horaria').value) || 0;
    const disciplinasSelecionadas = document.querySelectorAll('.disciplina-checkbox:checked');
    let cargaHorariaDisciplinas = 0;
    
    disciplinasSelecionadas.forEach(checkbox => {
        const cargaHoraria = parseInt(checkbox.getAttribute('data-carga-horaria')) || 0;
        cargaHorariaDisciplinas += cargaHoraria;
    });
    
    const cargaHorariaRestante = cargaHorariaTotal - cargaHorariaDisciplinas;
    
    // Avisar se há inconsistência na carga horária
    if (cargaHorariaTotal > 0 && cargaHorariaRestante !== 0) {
        const mensagem = cargaHorariaRestante > 0 
            ? `Ainda restam ${cargaHorariaRestante}h não atribuídas às disciplinas. Deseja continuar mesmo assim?`
            : `As disciplinas selecionadas excedem a carga horária total em ${Math.abs(cargaHorariaRestante)}h. Deseja continuar mesmo assim?`;
            
        if (!confirm(mensagem)) {
            return; // Cancelar salvamento
        }
    }
    
    const form = document.getElementById('formEditarTipoCurso');
    const formData = new FormData(form);
    formData.append('acao', 'editar');
    
    // Adicionar disciplinas selecionadas (checkboxes marcados)
    const disciplinasIds = Array.from(disciplinasSelecionadas)
        .map(checkbox => checkbox.value);
    formData.append('disciplinas', JSON.stringify(disciplinasIds));
    
    fetch(getBasePath() + '/admin/api/tipos-curso-clean.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            // Fechar modal
            fecharModalEditarTipoCurso();
            
            // Recarregar lista de tipos de curso
            recarregarTiposCurso();
            
            // Mostrar feedback de sucesso
            showAlert('success', data.mensagem);
        } else {
            showAlert('danger', data.mensagem);
        }
    })
    .catch(error => {
        console.error('Erro ao editar tipo de curso:', error);
        showAlert('danger', 'Erro ao editar tipo de curso: ' + error.message);
    });
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
    
    fetch(getBasePath() + '/admin/api/tipos-curso-clean.php', {
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
    
        fetch(getBasePath() + '/admin/api/salas-clean.php', {
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
    alertDiv.className = 'alert alert-' + type + ' alert-dismissible fade show alert-custom';
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
    const disciplinasExistentes = <?php echo json_encode($turmaManager->obterDisciplinasSelecionadas($turmaId)); ?>;
    
    console.log('=== DEBUG: Carregamento de Dados ===');
    console.log('rascunho:', rascunho);
    console.log('turmaAtual:', turmaAtual);
    console.log('disciplinasExistentes:', disciplinasExistentes);
    
    // Usar turmaAtual se disponível, senão usar rascunho
    const dados = turmaAtual || rascunho;
    
    console.log('dados a serem carregados:', dados);
    console.log('🔍 [DEBUG] Verificando dados específicos:');
    console.log('  - dados.data_inicio:', dados?.data_inicio);
    console.log('  - dados.data_fim:', dados?.data_fim);
    console.log('  - dados.nome:', dados?.nome);
    console.log('  - dados.sala_id:', dados?.sala_id);
    
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
        
        // Carregar disciplinas existentes se estivermos editando
        if (dados.id && disciplinasExistentes && disciplinasExistentes.length > 0) {
            console.log('🔄 Carregando disciplinas existentes para edição...');
            console.log('📊 Disciplinas a carregar:', disciplinasExistentes);
            
            // Aguardar mais tempo para o DOM estar pronto e depois carregar disciplinas
            setTimeout(() => {
                carregarDisciplinasExistentes(disciplinasExistentes);
            }, 1500);
        } else {
            console.log('ℹ️ Não há disciplinas para carregar ou não estamos editando');
            console.log('📊 dados.id:', dados.id);
            console.log('📊 disciplinasExistentes:', disciplinasExistentes);
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
            const radioModalidade = document.querySelector('input[name="modalidade"][value="' + dados.modalidade + '"]');
            if (radioModalidade) {
                radioModalidade.checked = true;
                console.log('✅ Modalidade carregada:', dados.modalidade);
            } else {
                console.log('❌ Radio modalidade não encontrado para valor:', dados.modalidade);
            }
        }
        
        if (dados.data_inicio) {
            console.log('🔄 [DATA] Tentando carregar data_inicio:', dados.data_inicio);
            const dataInicioElement = document.getElementById('data_inicio');
            console.log('🔍 [DATA] Elemento data_inicio encontrado:', !!dataInicioElement);
            
            if (dataInicioElement) {
                // Para campos input type="date", usar formato YYYY-MM-DD
                console.log('🎯 [DATA] Definindo valor do campo data_inicio:', dados.data_inicio);
                dataInicioElement.value = dados.data_inicio;
                console.log('✅ [DATA] Data início carregada:', dados.data_inicio);
                console.log('🔍 [DATA] Valor atual do campo:', dataInicioElement.value);
            } else {
                console.log('❌ [DATA] Elemento data_inicio não encontrado');
            }
        } else {
            console.log('❌ [DATA] dados.data_inicio está vazio ou undefined');
        }
        
        if (dados.data_fim) {
            console.log('🔄 [DATA] Tentando carregar data_fim:', dados.data_fim);
            const dataFimElement = document.getElementById('data_fim');
            console.log('🔍 [DATA] Elemento data_fim encontrado:', !!dataFimElement);
            
            if (dataFimElement) {
                // Para campos input type="date", usar formato YYYY-MM-DD
                console.log('🎯 [DATA] Definindo valor do campo data_fim:', dados.data_fim);
                dataFimElement.value = dados.data_fim;
                console.log('✅ [DATA] Data fim carregada:', dados.data_fim);
                console.log('🔍 [DATA] Valor atual do campo:', dataFimElement.value);
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

// Função para carregar disciplinas existentes no modo de edição
function carregarDisciplinasExistentes(disciplinasExistentes) {
    console.log('🔄 [EDITAR] Carregando disciplinas existentes:', disciplinasExistentes);
    
    // Verificar se estamos na página correta (não na página de detalhes)
    const urlParams = new URLSearchParams(window.location.search);
    const acao = urlParams.get('acao');
    const step = urlParams.get('step');
    
    if (acao === 'detalhes') {
        console.log('⚠️ [EDITAR] Função carregarDisciplinasExistentes chamada na página de detalhes - ignorando');
        return;
    }

    // Não executar na página de agendamento (step=2)
    if (step === '2' || acao === 'agendar') {
        console.log('⚠️ [EDITAR] Página de agendamento detectada (step=2). Ignorando carregarDisciplinasExistentes.');
        return;
    }
    
    if (!disciplinasExistentes || disciplinasExistentes.length === 0) {
        console.log('ℹ️ [EDITAR] Nenhuma disciplina existente para carregar');
        return;
    }
    
    // Limpar disciplinas existentes no container
    const container = document.getElementById('disciplinas-container');
    if (container) {
        console.log('🧹 [EDITAR] Limpando container de disciplinas');
        container.innerHTML = '';
    }
    
    // Resetar contador
    contadorDisciplinas = 0;
    console.log('🔄 [EDITAR] Contador resetado para 0');
    
    // Carregar disciplinas disponíveis primeiro
    console.log('📚 [EDITAR] Carregando disciplinas disponíveis...');
    carregarDisciplinasDisponiveis().then(() => {
        console.log('✅ [EDITAR] Disciplinas disponíveis carregadas, iniciando carregamento das existentes...');
        
        // Primeiro, carregar o campo fixo (disciplina_0) se não estiver vazio
        const selectPrincipal = document.querySelector('select[name="disciplina_0"]');
        if (selectPrincipal) {
            console.log('🎯 [EDITAR] Carregando disciplina no campo fixo...');
            
            // Carregar disciplinas no select principal
            carregarDisciplinas(0);
            
            // Aguardar um pouco e depois selecionar a primeira disciplina
            setTimeout(() => {
                if (disciplinasExistentes.length > 0) {
                    selectPrincipal.value = disciplinasExistentes[0].disciplina_id;
                    console.log(`✅ [EDITAR] Disciplina principal selecionada: ${disciplinasExistentes[0].nome_disciplina}`);
                }
            }, 800);
        }
        
        // Agora adicionar as disciplinas restantes (se houver mais de 1)
        if (disciplinasExistentes.length > 1) {
            disciplinasExistentes.slice(1).forEach((disciplina, index) => {
                console.log(`🔄 [EDITAR] Processando disciplina ${index + 2}/${disciplinasExistentes.length}:`, disciplina);
                
                // Aguardar um pouco antes de adicionar cada disciplina
                setTimeout(() => {
                    // Adicionar disciplina
                    adicionarDisciplina();
                    const disciplinaId = contadorDisciplinas - 1;
                    console.log(`➕ [EDITAR] Disciplina ${index + 2} adicionada ao DOM com ID ${disciplinaId}`);
                    
                    // Aguardar um pouco mais para o DOM ser atualizado
                    setTimeout(() => {
                        const select = document.querySelector(`select[name="disciplina_${disciplinaId}"]`);
                        
                        if (select) {
                            console.log(`🎯 [EDITAR] Selecionando disciplina ${disciplinaId}: ${disciplina.nome_disciplina}`);
                            
                            // Aguardar as opções serem carregadas
                            if (select.options.length <= 1) {
                                console.log('⏳ [EDITAR] Aguardando opções serem carregadas...');
                                setTimeout(() => {
                                    select.value = disciplina.disciplina_id;
                                    console.log(`✅ [EDITAR] Disciplina ${disciplina.nome_disciplina} selecionada`);
                                }, 500);
                            } else {
                                select.value = disciplina.disciplina_id;
                                console.log(`✅ [EDITAR] Disciplina ${disciplina.nome_disciplina} selecionada`);
                            }
                            
                        } else {
                            console.error(`❌ [EDITAR] Select não encontrado para disciplina ${disciplinaId}`);
                        }
                    }, 300);
                    
                }, 600 * (index + 1)); // Delay progressivo para evitar conflitos
            });
        }
        
        // Atualizar total de horas após carregar todas as disciplinas
        const totalDelay = 2000 + (disciplinasExistentes.length * 600);
        setTimeout(() => {
            console.log('📊 [EDITAR] Atualizando total de horas...');
            atualizarTotalHorasRegressivo();
        }, totalDelay);
    }).catch(error => {
        console.error('❌ [EDITAR] Erro ao carregar disciplinas disponíveis:', error);
    });
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
    // Formulário de edição de sala - agora usa sistema popup customizado
    
    // Formulário de edição de tipo de curso
    const formEditarTipoCurso = document.getElementById('formEditarTipoCurso');
    if (formEditarTipoCurso) {
        formEditarTipoCurso.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('acao', 'editar');
            
            fetch(getBasePath() + '/admin/api/tipos-curso-clean.php', {
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
            
            fetch(getBasePath() + '/admin/api/tipos-curso-clean.php', {
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
    
        fetch(getBasePath() + '/admin/api/salas-clean.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
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


// Função para limpar sistemas de modal antigos
function limparModaisAntigos() {
    console.log('🧹 Limpando modais antigos...');
    
    // Remover modal-root se existir
    const modalRoot = document.getElementById('modal-root');
    if (modalRoot) {
        modalRoot.remove();
    }
    
    // Remover qualquer backdrop do Bootstrap
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    
    // Remover classes de modal do body
    document.body.classList.remove('modal-open');
    // NÃO resetar os estilos aqui - deixar para gerenciarEstilosBody()
    // document.body.style.overflow = '';
    // document.body.style.paddingRight = '';
    
    // Remover qualquer overlay escuro
    const overlays = document.querySelectorAll('.modal, .popup-overlay, .dark-overlay');
    overlays.forEach(overlay => {
        if (!overlay.id || !overlay.id.includes('modalGerenciar')) {
            overlay.remove();
        }
    });
    
    // Remover qualquer elemento com background escuro
    const darkElements = document.querySelectorAll('[style*="background-color: rgba(0, 0, 0"], [style*="background: rgba(0, 0, 0"], [style*="background-color: #000"], [style*="background: #000"]');
    darkElements.forEach(element => {
        if (!element.id || !element.id.includes('modalGerenciar')) {
            element.remove();
        }
    });
    
    // Forçar remoção de qualquer backdrop
    const allElements = document.querySelectorAll('*');
    allElements.forEach(element => {
        if (element.style && element.style.backgroundColor === 'rgba(0, 0, 0, 0.5)') {
            if (!element.id || !element.id.includes('modalGerenciar')) {
                element.remove();
            }
        }
    });
}

// Função para eliminar película escura de forma agressiva
function eliminarPeliculaEscura() {
    console.log('🔥 Eliminando película escura de forma agressiva...');
    
    // Remover todos os pseudo-elementos ::before e ::after com background escuro
    const style = document.createElement('style');
    style.textContent = `
        *::before, *::after {
            background-color: transparent !important;
            background: transparent !important;
        }
        
        #modalGerenciarDisciplinas::before,
        #modalGerenciarDisciplinas::after {
            display: none !important;
            content: none !important;
            background: none !important;
            background-color: transparent !important;
            opacity: 0 !important;
            visibility: hidden !important;
            width: 0 !important;
            height: 0 !important;
        }
        
        .modal-disciplinas-custom::before,
        .modal-disciplinas-custom::after {
            display: none !important;
            content: none !important;
            background: none !important;
            background-color: transparent !important;
            opacity: 0 !important;
            visibility: hidden !important;
            width: 0 !important;
            height: 0 !important;
        }
    `;
    document.head.appendChild(style);
    
    // Forçar remoção de elementos com background escuro
    const elementosEscuros = document.querySelectorAll('*');
    elementosEscuros.forEach(elemento => {
        const estilo = window.getComputedStyle(elemento, '::before');
        if (estilo.backgroundColor === 'rgba(0, 0, 0, 0.5)' || estilo.backgroundColor === 'rgba(0, 0, 0, 0.3)') {
            if (!elemento.id || !elemento.id.includes('modalGerenciar')) {
                elemento.style.display = 'none';
            }
        }
    });
}

// Função para monitorar e limpar backdrops continuamente
function monitorarBackdrops() {
    setInterval(() => {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());
        
        // Remover elementos com background escuro
        const darkElements = document.querySelectorAll('[style*="background-color: rgba(0, 0, 0"], [style*="background: rgba(0, 0, 0"]');
        darkElements.forEach(element => {
            if (!element.id || !element.id.includes('modalGerenciar')) {
                element.remove();
            }
        });
    }, 100);
}

// Variáveis para controlar o modal
let modalDisciplinasAbrindo = false;
let modalDisciplinasCriado = false;
let modalDisciplinasAberto = false;

// Função para abrir modal de gerenciar disciplinas (VERSÃO SIMPLIFICADA)
function abrirModalDisciplinasInterno() {
    console.log('🔧 [DEBUG] Abrindo modal de disciplinas...');
    console.log('🔧 [DEBUG] Estado atual - Abrindo:', modalDisciplinasAbrindo, 'Aberto:', modalDisciplinasAberto);
    console.log('🔧 [DEBUG] Função chamada - timestamp:', new Date().toISOString());
    
    // TESTE SIMPLES: Mostrar alert primeiro
    // alert('🔧 Modal de disciplinas será aberto!'); // Removido para teste
    
    // Evitar múltiplas chamadas apenas se estiver sendo aberto
    if (modalDisciplinasAbrindo) {
        console.log('⚠️ [DEBUG] Modal já está sendo aberto, ignorando...');
        return;
    }
    
    modalDisciplinasAbrindo = true;
    
    // Limpar modais antigos
    limparModaisAntigos();
    
    // Verificar se o modal já existe
    let modal = document.getElementById('modalGerenciarDisciplinas');
    
    // Se o modal existe mas está fechado, remover completamente
    if (modal && !modalDisciplinasAberto) {
        console.log('🧹 [DEBUG] Removendo modal antigo fechado...');
        modal.remove();
        modal = null;
        modalDisciplinasCriado = false;
    }
    
    if (!modal) {
        console.log('🔧 [DEBUG] Criando modal...');
        try {
            modal = criarModalDisciplinas();
            console.log('✅ [DEBUG] Modal criado:', modal);
            document.body.appendChild(modal);
            console.log('✅ [DEBUG] Modal adicionado ao body');
            modalDisciplinasCriado = true;
        } catch (error) {
            console.error('❌ [DEBUG] Erro ao criar modal:', error);
            return;
        }
    }
    
    // Abrir o modal
    if (modal) {
        console.log('✅ [DEBUG] Abrindo modal...');
        console.log('🔧 [DEBUG] Modal antes da abertura:', modal);
        
        // Resetar completamente os estilos do modal antes de abrir
        modal.style.cssText = '';
        modal.className = 'modal-disciplinas-custom';
        console.log('🔧 [DEBUG] Estilos resetados');
        
        // Aplicar estilos de abertura
        modal.style.display = 'flex';
        modal.style.alignItems = 'center';
        modal.style.justifyContent = 'center';
        modal.classList.add('show');
        console.log('🔧 [DEBUG] Estilos de abertura aplicados');
        
        // Bloquear scroll do body usando função centralizada
        gerenciarEstilosBody('bloquear');
        console.log('🔧 [DEBUG] Body bloqueado');
        
        // Adicionar classe modal-open ao body para corrigir z-index
        document.body.classList.add('modal-open');
        console.log('🔧 [DEBUG] Classe modal-open adicionada ao body');
        
        modalDisciplinasAberto = true;
        console.log('🔧 [DEBUG] Variável modalDisciplinasAberto = true');
        
        // Carregar disciplinas com delay para garantir que o modal esteja pronto
        console.log('🔧 [DEBUG] Chamando carregarDisciplinasModal() com delay...');
        
        // Função para verificar se o modal está pronto
        function verificarModalPronto() {
            const modal = document.getElementById('modalGerenciarDisciplinas');
            const lista = document.getElementById('listaDisciplinas');
            
            if (modal && lista) {
                console.log('✅ [DEBUG] Modal e listaDisciplinas encontrados, carregando...');
                carregarDisciplinasModal();
                console.log('✅ [DEBUG] carregarDisciplinasModal() chamada com sucesso');
            } else {
                console.log('🔧 [DEBUG] Modal ainda não está pronto, aguardando...');
                setTimeout(verificarModalPronto, 200);
            }
        }
        
        // Iniciar verificação
        setTimeout(verificarModalPronto, 500);
        
        // Modal configurado - botões já têm onclick direto no HTML
        
        // Configurar os botões de fechar após criar o modal
        console.log('🔧 [DEBUG] Chamando configurarBotoesFecharModalDisciplinas()...');
        configurarBotoesFecharModalDisciplinas();
        console.log('✅ [DEBUG] configurarBotoesFecharModalDisciplinas() chamada com sucesso');
    }
    
    modalDisciplinasAbrindo = false;
    console.log('🔧 [DEBUG] Modal aberto - Estado final - Abrindo:', modalDisciplinasAbrindo, 'Aberto:', modalDisciplinasAberto);
}

// Função duplicada removida - usando a versão consolidada abaixo

// Função para testar e configurar os botões de fechar
function configurarBotoesFecharModalDisciplinas() {
    console.log('🔧 [CONFIG] Configurando botões de fechar do modal de disciplinas...');
    
    // Aguardar um pouco para garantir que o modal foi criado
    setTimeout(() => {
        console.log('🔧 [CONFIG] Iniciando configuração após timeout...');
        
        // Configurar botão X
        const botaoX = document.querySelector('#modalGerenciarDisciplinas .popup-modal-close');
        console.log('🔧 [CONFIG] Botão X encontrado?', botaoX);
        
        if (botaoX) {
            console.log('✅ [CONFIG] Botão X encontrado');
            console.log('🔧 [CONFIG] Botão X onclick atual:', botaoX.onclick);
            
            // Remover todos os event listeners anteriores
            const botaoXClone = botaoX.cloneNode(true);
            botaoX.parentNode.replaceChild(botaoXClone, botaoX);
            
            // Garantir que o botão seja clicável
            botaoXClone.style.pointerEvents = 'auto';
            botaoXClone.style.cursor = 'pointer';
            botaoXClone.style.zIndex = '1060';
            
            // Adicionar onclick direto
            botaoXClone.onclick = function(e) {
                console.log('🔧 [CONFIG] Botão X clicado!');
                e.preventDefault();
                e.stopPropagation();
                fecharModalDisciplinas();
                return false;
            };
            
            // Adicionar também addEventListener como backup
            botaoXClone.addEventListener('click', function(e) {
                console.log('🔧 [CONFIG] Botão X clicado via addEventListener!');
                e.preventDefault();
                e.stopPropagation();
                fecharModalDisciplinas();
            });
            
            console.log('✅ [CONFIG] Botão X configurado');
        } else {
            console.error('❌ [CONFIG] Botão X não encontrado');
        }
        
        // Configurar botão Fechar (apenas o do footer, não os de voltar)
        const botoesFechar = document.querySelectorAll('#modalGerenciarDisciplinas .popup-secondary-button');
        console.log('🔧 [CONFIG] Botões Fechar encontrados:', botoesFechar.length);
        
        // Procurar pelo botão que tem o texto "Fechar" ou "× Fechar"
        botoesFechar.forEach((botao, index) => {
            console.log('🔧 [CONFIG] Botão ' + index + ':', botao.textContent.trim());
            
            // Verificar se é o botão de fechar (não o de voltar)
            const textoBotao = botao.textContent.trim();
            if (textoBotao.includes('Fechar') && !textoBotao.includes('Voltar')) {
                console.log('✅ [CONFIG] Botão Fechar encontrado (índice ' + index + ')');
                
                // Remover todos os event listeners anteriores
                const botaoClone = botao.cloneNode(true);
                botao.parentNode.replaceChild(botaoClone, botao);
                
                // Garantir que o botão seja clicável
                botaoClone.style.pointerEvents = 'auto';
                botaoClone.style.cursor = 'pointer';
                botaoClone.style.zIndex = '1060';
                
                // Adicionar onclick direto
                botaoClone.onclick = function(e) {
                    console.log('🔧 [CONFIG] Botão Fechar clicado!');
                    e.preventDefault();
                    e.stopPropagation();
                    fecharModalDisciplinas();
                    return false;
                };
                
                // Adicionar também addEventListener como backup
                botaoClone.addEventListener('click', function(e) {
                    console.log('🔧 [CONFIG] Botão Fechar clicado via addEventListener!');
                    e.preventDefault();
                    e.stopPropagation();
                    fecharModalDisciplinas();
                });
                
                console.log('✅ [CONFIG] Botão Fechar configurado');
            }
        });
        
        console.log('🔧 [CONFIG] Configuração concluída');
    }, 200);
}

// Função para testar os botões de fechar (para debug)
function testarBotoesFecharModalDisciplinas() {
    console.log('🧪 [TESTE] Testando botões de fechar do modal de disciplinas...');
    
    // Verificar se o modal existe
    const modal = document.getElementById('modalGerenciarDisciplinas');
    if (!modal) {
        console.error('❌ [TESTE] Modal não encontrado');
        alert('Modal não encontrado!');
        return;
    }
    
    // Verificar botão X
    const botaoX = document.querySelector('#modalGerenciarDisciplinas .popup-modal-close');
    if (botaoX) {
        console.log('✅ [TESTE] Botão X encontrado');
        console.log('🔧 [TESTE] Botão X onclick:', botaoX.onclick);
        console.log('🔧 [TESTE] Botão X pointer-events:', botaoX.style.pointerEvents);
        console.log('🔧 [TESTE] Botão X z-index:', botaoX.style.zIndex);
    } else {
        console.error('❌ [TESTE] Botão X não encontrado');
    }
    
    // Verificar botão Fechar
    const botaoFechar = document.querySelector('#modalGerenciarDisciplinas .popup-secondary-button');
    if (botaoFechar) {
        console.log('✅ [TESTE] Botão Fechar encontrado');
        console.log('🔧 [TESTE] Botão Fechar onclick:', botaoFechar.onclick);
        console.log('🔧 [TESTE] Botão Fechar pointer-events:', botaoFechar.style.pointerEvents);
        console.log('🔧 [TESTE] Botão Fechar z-index:', botaoFechar.style.zIndex);
    } else {
        console.error('❌ [TESTE] Botão Fechar não encontrado');
    }
    
    // Testar função de fechamento
    console.log('🔧 [TESTE] Função fecharModalDisciplinas existe?', typeof fecharModalDisciplinas);
    
    if (typeof fecharModalDisciplinas === 'function') {
        console.log('✅ [TESTE] Função de fechamento está disponível');
        // Testar fechamento
        console.log('🧪 [TESTE] Testando fechamento do modal...');
        fecharModalDisciplinas();
    } else {
        console.error('❌ [TESTE] Função de fechamento não existe!');
    }
}

// Função para abrir formulário de nova disciplina (integrado)
function abrirFormularioNovaDisciplina() {
    console.log('🔧 Abrindo formulário Nova Disciplina integrado...');
    
    // Esconder conteúdo principal
    const conteudoPrincipal = document.getElementById('conteudo-principal-disciplinas');
    const formularioNovaDisciplina = document.getElementById('formulario-nova-disciplina');
    
    if (conteudoPrincipal && formularioNovaDisciplina) {
        conteudoPrincipal.style.display = 'none';
        formularioNovaDisciplina.style.display = 'block';
        
        // Limpar formulário
        document.getElementById('formNovaDisciplinaIntegrado').reset();
        document.getElementById('carga_horaria_disciplina_integrado').value = '20';
        document.getElementById('cor_disciplina_integrado').value = '#023A8D';
        
        // Focar no primeiro campo
        document.getElementById('codigo_disciplina_integrado').focus();
    } else {
        console.error('❌ Elementos do formulário não encontrados');
    }
}

// Função para voltar para a lista de disciplinas
function voltarParaListaDisciplinas() {
    console.log('🔧 Voltando para lista de disciplinas...');
    
    const conteudoPrincipal = document.getElementById('conteudo-principal-disciplinas');
    const formularioNovaDisciplina = document.getElementById('formulario-nova-disciplina');
    
    if (conteudoPrincipal && formularioNovaDisciplina) {
        formularioNovaDisciplina.style.display = 'none';
        conteudoPrincipal.style.display = 'block';
    }
}

// Função para salvar nova disciplina
function salvarNovaDisciplina(event) {
    event.preventDefault();
    console.log('💾 Salvando nova disciplina...');
    
    // Coletar dados do formulário
    const formDataOriginal = new FormData(event.target);
    const dados = {
        codigo: formDataOriginal.get('codigo'),
        nome: formDataOriginal.get('nome'),
        descricao: formDataOriginal.get('descricao'),
        carga_horaria_padrao: formDataOriginal.get('carga_horaria_padrao'),
        cor_hex: formDataOriginal.get('cor_hex'),
        ativa: 1
    };
    
    console.log('📊 Dados da disciplina:', dados);
    
    // Validar dados obrigatórios
    if (!dados.codigo || !dados.nome) {
        alert('Por favor, preencha os campos obrigatórios (Código e Nome).');
        return;
    }
    
    // Desabilitar botão de salvar
    const btnSalvar = document.getElementById('btnSalvarDisciplina');
    const textoOriginal = btnSalvar.innerHTML;
    btnSalvar.disabled = true;
    btnSalvar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
    
    // Enviar para API (usando FormData para compatibilidade com $_POST)
    const formData = new FormData();
    formData.append('acao', 'criar');
    formData.append('codigo', dados.codigo);
    formData.append('nome', dados.nome);
    formData.append('descricao', dados.descricao);
    formData.append('carga_horaria_padrao', dados.carga_horaria_padrao);
    formData.append('cor_hex', dados.cor_hex);
    formData.append('icone', 'book'); // Valor padrão
    formData.append('ativa', '1');
    
    fetch(getBasePath() + '/admin/api/disciplinas-clean.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('📡 Resposta da API:', response.status);
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.text().then(text => {
            console.log('📄 Texto da resposta:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('❌ Erro ao fazer parse do JSON:', text);
                throw new Error('JSON inválido: ' + e.message);
            }
        });
    })
    .then(data => {
        console.log('📊 Dados recebidos:', data);
        
        if (data.sucesso) {
            console.log('✅ Disciplina salva com sucesso!');
            
            // Mostrar mensagem de sucesso
            alert('Disciplina "' + dados.nome + '" criada com sucesso!');
            
            // Voltar para a lista
            voltarParaListaDisciplinas();
            
            // Recarregar lista de disciplinas
            carregarDisciplinasModal();
            
            // Atualizar seletor de disciplinas no formulário principal
            atualizarSeletorDisciplinas();
            
        } else {
            console.error('❌ Erro ao salvar disciplina:', data.mensagem);
            alert('Erro ao salvar disciplina: ' + (data.mensagem || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('❌ Erro na requisição:', error);
        alert('Erro de conexão: ' + error.message);
    })
    .finally(() => {
        // Reabilitar botão
        btnSalvar.disabled = false;
        btnSalvar.innerHTML = textoOriginal;
    });
}

// Função para atualizar seletor de disciplinas no formulário principal
function atualizarSeletorDisciplinas() {
    console.log('🔄 Atualizando seletor de disciplinas no formulário principal...');
    
    // Buscar todos os selects de disciplinas no formulário principal
    const selectsDisciplinas = document.querySelectorAll('select[name^="disciplina_"]');
    
    if (selectsDisciplinas.length === 0) {
        console.log('⚠️ Nenhum seletor de disciplinas encontrado no formulário principal');
        return;
    }
    
    console.log('📋 Encontrados ' + selectsDisciplinas.length + ' seletores de disciplinas');
    
    // Carregar disciplinas da API
    fetch(getBasePath() + '/admin/api/disciplinas-clean.php?acao=listar')
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('JSON inválido: ' + e.message);
                }
            });
        })
        .then(data => {
            if (data.sucesso && data.disciplinas) {
                console.log('✅ ' + data.disciplinas.length + ' disciplinas carregadas para atualizar seletores');
                
                // Atualizar cada seletor
                selectsDisciplinas.forEach((select, index) => {
                    // Salvar valor atual se houver
                    const valorAtual = select.value;
                    
                    // Limpar opções
                    select.innerHTML = '<option value="">Selecione a disciplina...</option>';
                    
                    // Adicionar disciplinas
                    data.disciplinas.forEach(disciplina => {
                        const option = document.createElement('option');
                        option.value = disciplina.id;
                        option.textContent = disciplina.nome;
                        option.dataset.aulas = disciplina.carga_horaria_padrao || 10;
                        option.dataset.cor = disciplina.cor_hex || '#007bff';
                        select.appendChild(option);
                    });
                    
                    // Restaurar valor anterior se ainda existir
                    if (valorAtual && select.querySelector('option[value="' + valorAtual + '"]')) {
                        select.value = valorAtual;
                    }
                    
                    console.log('✅ Seletor ' + (index + 1) + ' atualizado com ' + data.disciplinas.length + ' disciplinas');
                });
                
                console.log('✅ Todos os seletores de disciplinas foram atualizados');
                
            } else {
                console.error('❌ Erro ao carregar disciplinas para atualizar seletores:', data.mensagem);
            }
        })
        .catch(error => {
            console.error('❌ Erro ao atualizar seletores de disciplinas:', error);
        });
}

// Event listener para o formulário integrado de disciplinas
document.addEventListener('DOMContentLoaded', function() {
    // Event listener removido - usando onsubmit no HTML
    console.log('✅ [DOM] DOM carregado - sistema pronto!');
    
    // Event listener global para fechar modal de disciplinas
    document.addEventListener('click', function(e) {
        // Botão X do header
        if (e.target.closest('.popup-modal-close') && e.target.closest('#modalGerenciarDisciplinas')) {
            console.log('🔧 [GLOBAL] Botão X clicado via event listener global');
            e.preventDefault();
            e.stopPropagation();
            const modal = document.getElementById('modalGerenciarDisciplinas');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                modalDisciplinasAberto = false;
                console.log('✅ [GLOBAL] Modal fechado via X');
            }
        }
        
        // Ícone X
        if (e.target.classList.contains('fa-times') && e.target.closest('#modalGerenciarDisciplinas')) {
            console.log('🔧 [GLOBAL] Ícone X clicado via event listener global');
            e.preventDefault();
            e.stopPropagation();
            const modal = document.getElementById('modalGerenciarDisciplinas');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                modalDisciplinasAberto = false;
                console.log('✅ [GLOBAL] Modal fechado via ícone X');
            }
        }
        
        // Botão "Fechar" do footer
        if (e.target.closest('.popup-secondary-button') && e.target.closest('#modalGerenciarDisciplinas')) {
            console.log('🔧 [GLOBAL] Botão Fechar clicado via event listener global');
            e.preventDefault();
            e.stopPropagation();
            const modal = document.getElementById('modalGerenciarDisciplinas');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                modalDisciplinasAberto = false;
                console.log('✅ [GLOBAL] Modal fechado via Fechar');
            }
        }
    });
});

// Função para salvar nova disciplina (integrada)
function salvarNovaDisciplinaIntegrada(event) {
    if (event) {
        event.preventDefault();
    }
    console.log('💾 Salvando nova disciplina integrada...');
    
    const form = document.getElementById('formNovaDisciplinaIntegrado');
    const formData = new FormData(form);
    formData.append('acao', 'criar');
    
    console.log('📤 Dados a serem enviados:', Object.fromEntries(formData));
    
    // Enviar para API
    fetch(getBasePath() + '/admin/api/disciplinas-clean.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('📡 Resposta da API:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('📄 Dados da API:', data);
        if (data.sucesso) {
            showAlert('success', data.mensagem);
            // Voltar para a lista
            voltarParaListaDisciplinas();
            // Recarregar disciplinas
            carregarDisciplinasModal();
        } else {
            showAlert('danger', 'Erro: ' + (data.mensagem || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('❌ Erro ao salvar disciplina:', error);
        showAlert('danger', 'Erro ao salvar disciplina: ' + error.message);
    });
}

// Função para abrir modal de nova disciplina (versão antiga - mantida para compatibilidade)
function abrirModalNovaDisciplina() {
    // Redirecionar para o formulário integrado
    abrirFormularioNovaDisciplina();
}

// Função para carregar disciplinas no modal (renomeada para evitar conflitos)
function carregarDisciplinasModal() {
    console.log('🔄 Carregando disciplinas do banco de dados...');
    
    const listaDisciplinas = document.getElementById('listaDisciplinas');
    if (!listaDisciplinas) {
        console.error('❌ Container listaDisciplinas não encontrado');
        return;
    }
    
    console.log('✅ Elemento listaDisciplinas encontrado:', listaDisciplinas);
    
    // Mostrar loading
    listaDisciplinas.innerHTML = `
        <div class="popup-loading-state show">
            <div class="popup-loading-spinner"></div>
            <div class="popup-loading-text">
                <h6>Carregando disciplinas...</h6>
                <p>Aguarde enquanto buscamos suas disciplinas</p>
            </div>
        </div>
    `;
    
    // Carregar disciplinas reais da API
    fetch(getBasePath() + '/admin/api/disciplinas-clean.php?acao=listar')
        .then(response => {
            console.log('📡 Resposta da API recebida:', response.status);
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
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
                const disciplinas = data.disciplinas;
                console.log('✅ ' + disciplinas.length + ' disciplinas encontradas no banco');
                
                // Atualizar contador
                const totalDisciplinas = document.getElementById('totalDisciplinas');
                if (totalDisciplinas) {
                    totalDisciplinas.textContent = disciplinas.length;
                }
                
                // Gerar HTML das disciplinas
                let htmlDisciplinas = '';
                disciplinas.forEach(disciplina => {
                    const statusClass = disciplina.ativa == 1 ? 'active' : '';
                    const statusText = disciplina.ativa == 1 ? 'ATIVA' : 'INATIVA';
                    const statusColor = disciplina.ativa == 1 ? '#28a745' : '#6c757d';
                    
                    htmlDisciplinas += `
                        <div class="popup-item-card ${statusClass}" data-disciplina-id="${disciplina.id}">
                            <div class="popup-item-card-header">
                                <div class="popup-item-card-content">
                                    <h6 class="popup-item-card-title editable-field" data-field="nome" data-disciplina-id="${disciplina.id}" style="cursor: pointer; padding: 0.25rem; border-radius: 4px; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f8f9fa'" onmouseout="this.style.backgroundColor='transparent'" onclick="iniciarEdicaoInline('${disciplina.id}', 'nome', '${disciplina.nome.replace(/'/g, "\\'")}')">
                                        ${disciplina.nome}
                                    </h6>
                                    <div class="popup-item-card-code" style="background: ${statusColor}; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: bold;">
                                        ${statusText}
                                    </div>
                                    <div class="popup-item-card-description" style="margin-top: 0.5rem;">
                                        <div>
                                            <strong>Código:</strong> 
                                            <span class="editable-field" data-field="codigo" data-disciplina-id="${disciplina.id}" style="cursor: pointer; padding: 0.25rem; border-radius: 4px; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f8f9fa'" onmouseout="this.style.backgroundColor='transparent'" onclick="iniciarEdicaoInline('${disciplina.id}', 'codigo', '${disciplina.codigo.replace(/'/g, "\\'")}')">${disciplina.codigo}</span>
                                        </div>
                                        <div>
                                            <strong>Carga Horária:</strong> 
                                            <span class="editable-field" data-field="carga_horaria_padrao" data-disciplina-id="${disciplina.id}" style="cursor: pointer; padding: 0.25rem; border-radius: 4px; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f8f9fa'" onmouseout="this.style.backgroundColor='transparent'" onclick="iniciarEdicaoInline('${disciplina.id}', 'carga_horaria_padrao', '${disciplina.carga_horaria_padrao || 0}')">${disciplina.carga_horaria_padrao || 0}h</span>
                                        </div>
                                        <div>
                                            <strong>Descrição:</strong> 
                                            <span class="editable-field" data-field="descricao" data-disciplina-id="${disciplina.id}" style="cursor: pointer; padding: 0.25rem; border-radius: 4px; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f8f9fa'" onmouseout="this.style.backgroundColor='transparent'" onclick="iniciarEdicaoInline('${disciplina.id}', 'descricao', '${(disciplina.descricao || 'Sem descrição').replace(/'/g, "\\'")}')">${disciplina.descricao || 'Sem descrição'}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="popup-item-card-actions">
                                    <button type="button" class="popup-item-card-menu" onclick="salvarDisciplinaInline(${disciplina.id})" title="Salvar" style="color: #28a745; display: none;" id="btn-salvar-${disciplina.id}">
                                        <i class="fas fa-save"></i>
                                    </button>
                                    <button type="button" class="popup-item-card-menu" onclick="cancelarEdicaoInline(${disciplina.id})" title="Cancelar" style="color: #6c757d; display: none;" id="btn-cancelar-${disciplina.id}">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <button type="button" class="popup-item-card-menu" onclick="confirmarExclusaoDisciplina(${disciplina.id}, '${disciplina.nome}')" title="Excluir" style="color: #dc3545;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                listaDisciplinas.innerHTML = htmlDisciplinas;
                console.log('✅ Disciplinas carregadas no modal com sucesso');
                
            } else {
                console.error('❌ Erro ao carregar disciplinas:', data.mensagem || 'Erro desconhecido');
                
                // Mostrar erro
                listaDisciplinas.innerHTML = `
                    <div class="popup-loading-state show">
                        <div class="popup-loading-text">
                            <h6 style="color: #dc3545;">Erro ao carregar disciplinas</h6>
                            <p>${data.mensagem || 'Erro desconhecido'}</p>
                            <button type="button" class="btn btn-primary btn-sm mt-2" onclick="carregarDisciplinasModal()">
                                Tentar novamente
                            </button>
                        </div>
                    </div>
                `;
                
                // Atualizar contador para 0
                const totalDisciplinas = document.getElementById('totalDisciplinas');
                if (totalDisciplinas) {
                    totalDisciplinas.textContent = '0';
                }
            }
        })
        .catch(error => {
            console.error('❌ Erro na requisição:', error);
            
            // Mostrar erro
            listaDisciplinas.innerHTML = `
                <div class="popup-loading-state show">
                    <div class="popup-loading-text">
                        <h6 style="color: #dc3545;">Erro de conexão</h6>
                        <p>Não foi possível carregar as disciplinas. Verifique sua conexão.</p>
                        <button type="button" class="btn btn-primary btn-sm mt-2" onclick="carregarDisciplinasModal()">
                            Tentar novamente
                        </button>
                    </div>
                </div>
            `;
            
            // Atualizar contador para 0
            const totalDisciplinas = document.getElementById('totalDisciplinas');
            if (totalDisciplinas) {
                totalDisciplinas.textContent = '0';
            }
        });
}

// Função para confirmar exclusão de disciplina
function confirmarExclusaoDisciplina(id, nome) {
    console.log('🗑️ Confirmando exclusão da disciplina:', nome, 'ID:', id);
    
    if (confirm('Tem certeza que deseja excluir a disciplina "' + nome + '"?\n\nEsta ação não pode ser desfeita.')) {
        excluirDisciplina(id, nome);
    }
}

// Função para excluir disciplina
function excluirDisciplina(id, nome) {
    console.log('🗑️ Excluindo disciplina:', nome, 'ID:', id);
    
    // Simular exclusão
    alert('Disciplina "' + nome + '" seria excluída aqui!');
    
    // Recarregar lista
    carregarDisciplinasModal();
}

// Função para editar disciplina (versão simples)
function editarDisciplina(id) {
    console.log('✏️ Editando disciplina ID:', id);
    
    // Buscar dados da disciplina
    fetch(getBasePath() + '/admin/api/disciplinas-clean.php?acao=listar')
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                const disciplina = data.disciplinas.find(d => d.id == id);
                if (disciplina) {
                    // Mostrar dados da disciplina em um prompt para edição
                    const novoNome = prompt('Editar nome da disciplina:', disciplina.nome);
                    if (novoNome && novoNome !== disciplina.nome) {
                        // Aqui você pode implementar a edição via API
                        console.log('📝 Nome alterado para:', novoNome);
                        alert('Disciplina "' + disciplina.nome + '" seria editada para "' + novoNome + '"');
                    }
                } else {
                    alert('Disciplina não encontrada!');
                }
            } else {
                alert('Erro ao carregar dados da disciplina!');
            }
        })
        .catch(error => {
            console.error('❌ Erro ao editar disciplina:', error);
            alert('Erro ao carregar dados da disciplina!');
        });
}

// Função para salvar alterações de disciplinas
// Função removida - usando a versão funcional abaixo

// Função de teste para o botão X
function testarBotaoX() {
    console.log('🔧 [TESTE] Testando botão X...');
    const botaoX = document.querySelector('#modalGerenciarDisciplinas .popup-modal-close');
    if (botaoX) {
        console.log('✅ [TESTE] Botão X encontrado');
        
        // Garantir que o botão seja clicável
        botaoX.style.pointerEvents = 'auto';
        botaoX.style.cursor = 'pointer';
        botaoX.style.zIndex = '1060';
        botaoX.style.position = 'relative';
        botaoX.style.backgroundColor = 'transparent';
        botaoX.style.border = 'none';
        
        // Testar se a função existe
        console.log('🔧 [TESTE] Função fecharModalDisciplinas existe?', typeof fecharModalDisciplinas);
        
        // Adicionar onclick direto
        botaoX.onclick = function(e) {
            console.log('🔧 [TESTE] Botão X clicado via onclick!');
            e.preventDefault();
            e.stopPropagation();
            if (typeof fecharModalDisciplinas === 'function') {
                fecharModalDisciplinas();
            } else {
                console.error('❌ [TESTE] Função fecharModalDisciplinas não existe!');
            }
            return false;
        };
        
        console.log('✅ [TESTE] Botão X configurado');
        
    } else {
        console.error('❌ [TESTE] Botão X não encontrado');
    }
}

// Função para aplicar estilos diretamente no header
function aplicarEstilosHeader() {
    console.log('🔧 [ESTILOS] Aplicando estilos diretamente no header...');
    const header = document.querySelector('#modalGerenciarDisciplinas .popup-modal-header');
    if (header) {
        console.log('✅ [ESTILOS] Header encontrado, aplicando padding reduzido...');
        header.style.padding = '1.2rem 2rem';
        header.style.minHeight = 'auto';
        header.style.maxHeight = 'none';
        
        const headerContent = header.querySelector('.header-content');
        if (headerContent) {
            headerContent.style.alignItems = 'center';
            headerContent.style.height = '100%';
        }
        
        const headerIcon = header.querySelector('.header-icon');
        if (headerIcon) {
            headerIcon.style.marginRight = '1rem';
        }
        
        const headerText = header.querySelector('.header-text h5');
        if (headerText) {
            headerText.style.marginBottom = '0.25rem';
        }
        
        const headerSubtext = header.querySelector('.header-text small');
        if (headerSubtext) {
            headerSubtext.style.opacity = '0.75';
        }
        
        console.log('✅ [ESTILOS] Estilos aplicados com sucesso!');
        
        // Aplicar estilos no conteúdo também
        const content = document.querySelector('#modalGerenciarDisciplinas .popup-modal-content');
        if (content) {
            content.style.padding = '0.5rem 2rem 1.5rem 2rem';
        }
        
        const searchContainer = document.querySelector('#modalGerenciarDisciplinas .popup-search-container');
        if (searchContainer) {
            searchContainer.style.marginBottom = '1rem';
        }
        
        const sectionHeader = document.querySelector('#modalGerenciarDisciplinas .popup-section-header');
        if (sectionHeader) {
            sectionHeader.style.marginBottom = '1rem';
        }
        
        console.log('✅ [ESTILOS] Estilos de conteúdo aplicados!');
    } else {
        console.error('❌ [ESTILOS] Header não encontrado');
    }
}

// Função para limpar filtros de disciplinas
function limparFiltrosDisciplinas() {
    console.log('🧹 Limpando filtros de disciplinas...');
    const buscarInput = document.getElementById('buscarDisciplinas');
    if (buscarInput) {
        buscarInput.value = '';
        carregarDisciplinasModal();
    }
}

// Função para configurar o botão X
function configurarBotaoX() {
    console.log('🔧 [CONFIG] Configurando botão X...');
    const botaoX = document.querySelector('#modalGerenciarDisciplinas .popup-modal-close');
    if (botaoX) {
        console.log('✅ [CONFIG] Botão X encontrado');
        
        // Garantir que o botão seja clicável
        botaoX.style.pointerEvents = 'auto';
        botaoX.style.cursor = 'pointer';
        botaoX.style.zIndex = '1060';
        
        // Adicionar onclick direto
        botaoX.onclick = function(e) {
            console.log('🔧 [CONFIG] Botão X clicado!');
            e.preventDefault();
            e.stopPropagation();
            fecharModalDisciplinas();
            return false;
        };
        
        console.log('✅ [CONFIG] Botão X configurado');
    } else {
        console.error('❌ [CONFIG] Botão X não encontrado');
    }
    
    // Configurar também o botão "Fechar" do footer
    const botaoFechar = document.querySelector('#modalGerenciarDisciplinas .popup-secondary-button');
    if (botaoFechar) {
        console.log('✅ [CONFIG] Botão Fechar encontrado');
        botaoFechar.onclick = function(e) {
            console.log('🔧 [CONFIG] Botão Fechar clicado!');
            e.preventDefault();
            e.stopPropagation();
            fecharModalDisciplinas();
            return false;
        };
        console.log('✅ [CONFIG] Botão Fechar configurado');
    } else {
        console.error('❌ [CONFIG] Botão Fechar não encontrado');
    }
}

// Função para testar botões de fechar
function testarBotoesFechar() {
    console.log('🔧 [TESTE] Testando botões de fechar...');
    
    // Testar botão X
    const botaoX = document.querySelector('#modalGerenciarDisciplinas .popup-modal-close');
    if (botaoX) {
        console.log('✅ [TESTE] Botão X encontrado');
        botaoX.onclick = function() {
            console.log('🔧 [TESTE] Botão X clicado!');
            const modal = document.getElementById('modalGerenciarDisciplinas');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                modalDisciplinasAberto = false;
                console.log('✅ [TESTE] Modal fechado via X');
            }
        };
    } else {
        console.error('❌ [TESTE] Botão X não encontrado');
    }
    
    // Testar botão Fechar
    const botaoFechar = document.querySelector('#modalGerenciarDisciplinas .popup-secondary-button');
    if (botaoFechar) {
        console.log('✅ [TESTE] Botão Fechar encontrado');
        botaoFechar.onclick = function() {
            console.log('🔧 [TESTE] Botão Fechar clicado!');
            const modal = document.getElementById('modalGerenciarDisciplinas');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                modalDisciplinasAberto = false;
                console.log('✅ [TESTE] Modal fechado via Fechar');
            }
        };
    } else {
        console.error('❌ [TESTE] Botão Fechar não encontrado');
    }
}

// Função para fechar modal corretamente
function fecharModalCorretamente() {
    console.log('🔧 [FECHAR] Fechando modal corretamente...');
    
    const modal = document.getElementById('modalGerenciarDisciplinas');
    if (modal) {
        console.log('✅ [FECHAR] Modal encontrado');
        
        // Fechar o modal
        modal.style.display = 'none';
        modal.classList.remove('show', 'popup-fade-in');
        
        // Restaurar scroll do body
        document.body.style.overflow = 'auto';
        document.body.style.position = 'static';
        document.body.style.width = 'auto';
        
        console.log('✅ [FECHAR] Modal fechado');
    } else {
        console.error('❌ [FECHAR] Modal não encontrado');
    }
    
    // RESETAR TODAS AS VARIÁVEIS DE CONTROLE
    modalDisciplinasAbrindo = false;
    modalDisciplinasCriado = false;
    modalDisciplinasAberto = false;
    
    console.log('✅ [FECHAR] Variáveis resetadas - modal pode ser reaberto');
}

// Função para fechar modal diretamente
function fecharModalDireto() {
    console.log('🔧 [DIRETO] Fechando modal diretamente...');
    alert('Função fecharModalDireto chamada!');
    
    const modal = document.getElementById('modalGerenciarDisciplinas');
    if (modal) {
        console.log('✅ [DIRETO] Modal encontrado, fechando...');
        modal.style.display = 'none';
        modal.classList.remove('show');
        
        // Restaurar scroll do body
        document.body.style.overflow = 'auto';
        document.body.style.position = 'static';
        document.body.style.width = 'auto';
        
        // Resetar variáveis
        modalDisciplinasAbrindo = false;
        modalDisciplinasCriado = false;
        modalDisciplinasAberto = false;
        
        console.log('✅ [DIRETO] Modal fechado com sucesso');
        alert('Modal fechado com sucesso!');
    } else {
        console.error('❌ [DIRETO] Modal não encontrado');
        alert('Modal não encontrado!');
    }
}

// Função para fechar modal (FUNCIONANDO)
function fecharModalDisciplinas() {
    console.log('🔧 [FECHAR] Fechando modal de disciplinas...');
    
    const modal = document.getElementById('modalGerenciarDisciplinas');
    if (modal) {
        console.log('✅ [FECHAR] Modal encontrado, fechando...');
        console.log('🔧 [FECHAR] Display atual:', modal.style.display);
        console.log('🔧 [FECHAR] Classes atuais:', modal.className);
        
        // PRIMEIRO: Restaurar scroll do body ANTES de fechar o modal
        gerenciarEstilosBody('restaurar');
        
        // Remover classe modal-open do body para restaurar z-index normal
        document.body.classList.remove('modal-open');
        
        // SEGUNDO: Fechar o modal com CSS mais específico
        modal.style.setProperty('display', 'none', 'important');
        modal.style.setProperty('visibility', 'hidden', 'important');
        modal.style.setProperty('opacity', '0', 'important');
        modal.style.setProperty('pointer-events', 'none', 'important');
        
        // Remover todas as classes que podem manter o modal visível
        modal.classList.remove('show', 'active', 'visible', 'open');
        modal.className = 'modal-disciplinas-custom';
        
        // TERCEIRO: Resetar variáveis
        modalDisciplinasAbrindo = false;
        modalDisciplinasCriado = false;
        modalDisciplinasAberto = false;
        
        console.log('🔧 [FECHAR] Display após fechar:', modal.style.display);
        console.log('🔧 [FECHAR] Classes após fechar:', modal.className);
        console.log('🔧 [FECHAR] Estado das variáveis - Abrindo:', modalDisciplinasAbrindo, 'Criado:', modalDisciplinasCriado, 'Aberto:', modalDisciplinasAberto);
        
        // Verificar se funcionou
        const computedStyle = window.getComputedStyle(modal);
        console.log('🔍 [FECHAR] Display computado:', computedStyle.display);
        console.log('🔍 [FECHAR] Visibility computado:', computedStyle.visibility);
        
        // Verificar se o body foi restaurado
        const bodyComputed = window.getComputedStyle(document.body);
        console.log('🔍 [BODY] Overflow computado:', bodyComputed.overflow);
        console.log('🔍 [BODY] Position computado:', bodyComputed.position);
        
        // QUARTO: Forçar repaint do body
        document.body.offsetHeight; // Trigger reflow
        
        console.log('✅ [FECHAR] Modal fechado com sucesso');
    } else {
        console.error('❌ [FECHAR] Modal não encontrado');
    }
}

// Tornar a função globalmente acessível
window.fecharModalDisciplinas = fecharModalDisciplinas;

// Log de teste para verificar se o script está carregando
console.log('✅ [SCRIPT] Script de turmas-teoricas.php carregado!');
console.log('✅ [SCRIPT] Função fecharModalDisciplinas disponível:', typeof window.fecharModalDisciplinas);

// Função para filtrar disciplinas
function filtrarDisciplinas() {
    console.log('🔍 Filtrando disciplinas...');
    // Implementar filtro aqui
}

// Função para criar o HTML do modal de disciplinas (padrão)
function criarModalDisciplinas() {
    const modal = document.createElement('div');
    modal.className = 'modal-disciplinas-custom';
    modal.id = 'modalGerenciarDisciplinas';
    modal.style.display = 'none';
    modal.style.position = 'fixed';
    modal.style.top = '0';
    modal.style.left = '0';
    modal.style.width = '100vw';
    modal.style.height = '100vh';
    modal.style.backgroundColor = 'transparent';
    modal.style.zIndex = '1055';
    modal.style.padding = '2rem';
    
    modal.innerHTML = `
        <div class="popup-modal-wrapper">
            
            <!-- HEADER -->
            <div class="popup-modal-header">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="header-text">
                        <h5>Gerenciar Disciplinas</h5>
                        <small>Configure e organize as disciplinas do curso</small>
                    </div>
                </div>
                <button type="button" class="popup-modal-close" onclick="fecharModalDisciplinas()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- CONTEÚDO -->
            <div class="popup-modal-content">
                
                <!-- Barra de Busca -->
                <div class="popup-search-container">
                    <div class="popup-search-wrapper">
                        <input type="text" class="popup-search-input" id="buscarDisciplinas" placeholder="Buscar disciplinas..." onkeyup="filtrarDisciplinas()">
                        <i class="fas fa-search popup-search-icon"></i>
                    </div>
                </div>
                
                <!-- Seção Otimizada - Título, Estatísticas e Botão na mesma linha -->
                <div class="popup-section-header">
                    <div class="popup-section-title">
                        <h6>Suas Disciplinas</h6>
                        <small>Gerencie e organize as disciplinas do curso</small>
                    </div>
                    <div class="popup-stats-item" style="margin: 0;">
                        <div class="popup-stats-icon">
                            <div class="icon-circle">
                                <i class="fas fa-book"></i>
                            </div>
                        </div>
                        <div class="popup-stats-text">
                            <h6 style="margin: 0;">Total: <span class="stats-number" id="totalDisciplinas">0</span></h6>
                        </div>
                    </div>
                    <button type="button" class="popup-primary-button" onclick="abrirFormularioNovaDisciplina()">
                        <i class="fas fa-plus"></i>
                        Nova Disciplina
                    </button>
                </div>
                
                <!-- Conteúdo Principal - Lista de Disciplinas -->
                <div id="conteudo-principal-disciplinas">
                    <!-- Grid de Disciplinas -->
                    <div class="popup-items-grid" id="listaDisciplinas">
                        <!-- Lista de disciplinas será carregada aqui -->
                    </div>
                </div>
                
                <!-- Formulário Nova Disciplina (oculto inicialmente) -->
                <div id="formulario-nova-disciplina" style="display: none;">
                    <div class="popup-section-header">
                        <div class="popup-section-title">
                            <h6>Nova Disciplina</h6>
                            <small>Preencha os dados da nova disciplina</small>
                        </div>
                        <button type="button" class="popup-secondary-button" onclick="voltarParaListaDisciplinas()">
                            <i class="fas fa-arrow-left"></i>
                            Voltar
                        </button>
                    </div>
                    
                    <form id="formNovaDisciplinaIntegrado" class="mt-3" onsubmit="salvarNovaDisciplinaIntegrada(event)">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="codigo_disciplina_integrado" class="form-label">Código *</label>
                                    <input type="text" class="form-control" id="codigo_disciplina_integrado" name="codigo" required placeholder="Ex: direcao_defensiva">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nome_disciplina_integrado" class="form-label">Nome *</label>
                                    <input type="text" class="form-control" id="nome_disciplina_integrado" name="nome" required placeholder="Ex: Direção Defensiva">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descricao_disciplina_integrado" class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricao_disciplina_integrado" name="descricao" rows="3" placeholder="Descrição detalhada da disciplina"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="carga_horaria_disciplina_integrado" class="form-label">Carga Horária Padrão</label>
                                    <input type="number" class="form-control" id="carga_horaria_disciplina_integrado" name="carga_horaria_padrao" min="1" value="20">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cor_disciplina_integrado" class="form-label">Cor</label>
                                    <input type="color" class="form-control" id="cor_disciplina_integrado" name="cor_hex" value="#023A8D">
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2 justify-content-end">
                            <button type="button" class="popup-secondary-button" onclick="voltarParaListaDisciplinas()">
                                <i class="fas fa-times"></i>
                                Cancelar
                            </button>
                            <button type="submit" class="popup-save-button" id="btnSalvarDisciplina">
                                <i class="fas fa-save"></i>
                                Salvar Disciplina
                            </button>
                        </div>
                    </form>
                </div>
                
            </div>
            
            <!-- FOOTER -->
            <div class="popup-modal-footer">
                <div class="popup-footer-info">
                    <small>
                        <i class="fas fa-info-circle"></i>
                        As alterações são salvas automaticamente
                    </small>
                </div>
                <div class="popup-footer-actions">
                    <button type="button" class="popup-secondary-button" onclick="fecharModalDisciplinas()">
                        <i class="fas fa-times"></i>
                        Fechar
                    </button>
                    <button type="button" class="popup-save-button" onclick="salvarAlteracoesDisciplinas()">
                        <i class="fas fa-save"></i>
                        Salvar Alterações
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
    const nomeInput = container.querySelector('input[data-id="' + novoId + '"]');
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
    
    const card = document.querySelector('[data-id="' + disciplinaId + '"]');
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
    fetch(getBasePath() + '/admin/api/disciplinas-clean.php', {
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
    
    const card = document.querySelector('[data-id="' + disciplinaId + '"]');
    if (card) {
        card.remove();
        atualizarContadorDisciplinas();
        console.log('✅ Nova disciplina cancelada');
    }
}

function salvarAlteracoesDisciplinas() {
    console.log('💾 Salvando todas as alterações das disciplinas...');
    
    // Coletar todas as disciplinas do modal
    const disciplinasModificadas = [];
    const cards = document.querySelectorAll('#listaDisciplinas .disciplina-card');
    
    console.log('🔍 Encontrados ' + cards.length + ' cards de disciplinas');
    
    cards.forEach((card, index) => {
        const disciplinaId = card.getAttribute('data-id');
        console.log('📋 Processando card ' + (index + 1) + ', ID: ' + disciplinaId);
        
        if (!disciplinaId || disciplinaId.startsWith('temp_')) {
            console.log('⏭️ Pular disciplina temporária: ' + disciplinaId);
            return; // Pular disciplinas temporárias (novas não salvas)
        }
        
        // Buscar dados dos elementos de exibição (não input)
        const nomeElement = card.querySelector('h6[data-field="nome"]');
        const codigoElement = card.querySelector('span[data-field="codigo"]');
        const cargaElement = card.querySelector('span[data-field="carga_horaria_padrao"]');
        const descricaoElement = card.querySelector('p[data-field="descricao"]');
        
        const nome = nomeElement ? nomeElement.textContent.trim() : '';
        const codigo = codigoElement ? codigoElement.textContent.trim() : '';
        const cargaHoraria = cargaElement ? cargaElement.textContent.trim().replace('h', '') : '10';
        const descricao = descricaoElement ? descricaoElement.textContent.trim() : '';
        
        console.log('📝 Dados coletados - Nome: "' + nome + '", Código: "' + codigo + '", Carga: "' + cargaHoraria + '"');
        
        if (nome && codigo) {
            disciplinasModificadas.push({
                id: disciplinaId,
                nome: nome,
                codigo: codigo,
                carga_horaria_padrao: cargaHoraria,
                descricao: descricao
            });
            console.log('✅ Disciplina "' + nome + '" adicionada à lista de modificadas');
        } else {
            console.log('⚠️ Disciplina ' + (index + 1) + ' ignorada - dados incompletos');
        }
    });
    
    if (disciplinasModificadas.length === 0) {
        console.log('ℹ️ Nenhuma disciplina modificada para salvar');
        alert('ℹ️ Nenhuma alteração detectada para salvar.');
        // Fechar modal mesmo sem alterações
        window.closeModal();
        return;
    }
    
    console.log('💾 Salvando ' + disciplinasModificadas.length + ' disciplinas...');
    alert('💾 Salvando ' + disciplinasModificadas.length + ' disciplinas...');
    
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
        
        console.log('📤 Enviando dados da disciplina "' + disciplina.nome + '" para API');
        
        return fetch(getBasePath() + '/admin/api/disciplinas-clean.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                console.log('✅ Disciplina ' + disciplina.nome + ' salva com sucesso');
                return { sucesso: true, disciplina: disciplina.nome };
            } else {
                console.error('❌ Erro ao salvar disciplina ' + disciplina.nome + ':', data.mensagem);
                return { sucesso: false, disciplina: disciplina.nome, erro: data.mensagem };
            }
        })
        .catch(error => {
            console.error('❌ Erro na requisição para disciplina ' + disciplina.nome + ':', error);
            return { sucesso: false, disciplina: disciplina.nome, erro: error.message };
        });
    });
    
    // Aguardar todas as operações
    Promise.all(promises)
    .then(resultados => {
        const sucessos = resultados.filter(r => r.sucesso).length;
        const erros = resultados.filter(r => !r.sucesso);
        
        if (erros.length === 0) {
            console.log('✅ Todas as ' + sucessos + ' disciplinas foram salvas com sucesso!');
            alert('✅ Todas as ' + sucessos + ' disciplinas foram salvas com sucesso!');
        } else {
            console.warn('⚠️ ' + sucessos + ' disciplinas salvas, ' + erros.length + ' com erro');
            const nomesComErro = erros.map(e => e.disciplina).join(', ');
            alert('⚠️ ' + sucessos + ' disciplinas salvas com sucesso!\nErro em: ' + nomesComErro);
        }
        
        // Fechar modal após salvar
        console.log('🚪 Fechando modal...');
        window.closeModal();
    })
    .catch(error => {
        console.error('❌ Erro geral ao salvar disciplinas:', error);
        alert('❌ Erro ao salvar disciplinas. Tente novamente.');
    });
}

// Variável global para armazenar disciplinas
let disciplinasOriginais = [];

// Função duplicada removida - usando a versão principal acima

// Função para recarregar lista de disciplinas via AJAX (compatibilidade)
function recarregarDisciplinas() {
    // Não recarregar tudo para evitar conflitos com edição inline
    console.log('🔄 Recarregamento de disciplinas desabilitado durante edição inline');
    // carregarDisciplinasModal(); // Comentado para evitar conflitos
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
    const card = document.querySelector('[data-id="' + id + '"]');
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


// Função para criar modal de editar disciplina dinamicamente
function criarModalEditarDisciplina() {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'modalEditarDisciplina';
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('aria-labelledby', 'modalEditarDisciplinaLabel');
    modal.setAttribute('aria-hidden', 'true');
    
    modal.innerHTML = `
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
                            <i class="fas fa-save me-1"></i>Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;
    
    return modal;
}

// Variável para armazenar dados originais durante edição
let dadosOriginais = {};

// Função para iniciar edição inline
function iniciarEdicaoInline(disciplinaId, campo, valorAtual) {
    console.log('✏️ Iniciando edição inline: ' + campo + ' = ' + valorAtual);
    
    const elemento = document.querySelector('[data-disciplina-id="' + disciplinaId + '"][data-field="' + campo + '"]');
    if (!elemento) {
        console.error('❌ Elemento não encontrado para edição');
        return;
    }
    
    // Usar valor atual do DOM se disponível (pode ter sido atualizado por uma edição anterior)
    const valorAtualDoDOM = elemento.textContent.trim();
    const valorParaEdicao = valorAtualDoDOM || valorAtual;
    
    console.log('🔍 Valor para edição: "' + valorParaEdicao + '" (DOM: "' + valorAtualDoDOM + '", Original: "' + valorAtual + '")');
    
    // Salvar dados originais
    if (!dadosOriginais[disciplinaId]) {
        dadosOriginais[disciplinaId] = {};
    }
    dadosOriginais[disciplinaId][campo] = valorParaEdicao;
    
    // Criar input baseado no tipo de campo
    let input;
    if (campo === 'carga_horaria_padrao') {
        input = document.createElement('input');
        input.type = 'number';
        input.min = '1';
        input.max = '200';
        input.value = valorParaEdicao.toString().replace('h', '');
        input.className = 'form-control form-control-sm';
        input.style.width = '80px';
        input.style.display = 'inline-block';
    } else if (campo === 'descricao') {
        input = document.createElement('textarea');
        input.rows = 2;
        input.value = valorParaEdicao === 'Sem descrição' ? '' : valorParaEdicao;
        input.className = 'form-control form-control-sm';
        input.style.width = '200px';
        input.style.display = 'inline-block';
    } else {
        input = document.createElement('input');
        input.type = 'text';
        input.value = valorParaEdicao;
        input.className = 'form-control form-control-sm';
        input.style.width = campo === 'codigo' ? '120px' : '150px';
        input.style.display = 'inline-block';
    }
    
    // Adicionar eventos
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && campo !== 'descricao') {
            salvarCampoInline(disciplinaId, campo, input.value);
        } else if (e.key === 'Escape') {
            cancelarEdicaoInline(disciplinaId);
        }
    });
    
    input.addEventListener('blur', function() {
        salvarCampoInline(disciplinaId, campo, input.value);
    });
    
    // Substituir elemento pelo input
    elemento.style.display = 'none';
    elemento.parentNode.insertBefore(input, elemento.nextSibling);
    input.focus();
    input.select();
    
    // Mostrar botões de ação
    mostrarBotoesEdicao(disciplinaId);
}

// Função para salvar campo específico
function salvarCampoInline(disciplinaId, campo, novoValor) {
    console.log('💾 Salvando campo ' + campo + ': ' + novoValor);
    
    // Validar dados
    if (campo === 'nome' && !novoValor.trim()) {
        showAlert('danger', 'Nome da disciplina é obrigatório');
        return;
    }
    
    if (campo === 'codigo' && !novoValor.trim()) {
        showAlert('danger', 'Código da disciplina é obrigatório');
        return;
    }
    
    if (campo === 'carga_horaria_padrao' && (!novoValor || parseInt(novoValor) < 1)) {
        showAlert('danger', 'Carga horária deve ser maior que 0');
        return;
    }
    
    // Coletar todos os dados atuais da disciplina (incluindo o campo editado)
    const formData = new FormData();
    formData.append('acao', 'editar');
    formData.append('id', disciplinaId);
    
    // Coletar todos os campos atuais
    const campos = ['nome', 'codigo', 'carga_horaria_padrao', 'descricao'];
    campos.forEach(campoNome => {
        let valor = '';
        
        if (campoNome === campo) {
            // Usar o novo valor para o campo editado
            valor = novoValor.trim();
        } else {
            // Buscar o valor atual do campo no DOM
            const elemento = document.querySelector('[data-disciplina-id="' + disciplinaId + '"][data-field="' + campoNome + '"]');
            if (elemento) {
                valor = elemento.textContent.trim();
                // Limpar formatação (ex: remover 'h' da carga horária)
                if (campoNome === 'carga_horaria_padrao') {
                    valor = valor.replace('h', '');
                }
                // Limpar "Sem descrição"
                if (campoNome === 'descricao' && valor === 'Sem descrição') {
                    valor = '';
                }
            }
        }
        
        formData.append(campoNome, valor);
        console.log('📝 Campo ' + campoNome + ': "' + valor + '"');
    });
    
    console.log('📤 Enviando dados para API - ID: ' + disciplinaId + ', Campo editado: ' + campo);
    
    // Enviar para API
    fetch(getBasePath() + '/admin/api/disciplinas-clean.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('📡 Resposta da API:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('📄 Dados da API:', data);
        if (data.sucesso) {
            // Atualizar exibição
            atualizarExibicaoCampo(disciplinaId, campo, novoValor);
            showAlert('success', data.mensagem);
            // Não recarregar tudo para evitar conflitos
            console.log('✅ Campo salvo com sucesso, exibição atualizada');
        } else {
            console.error('❌ Erro da API:', data);
            showAlert('danger', 'Erro: ' + (data.mensagem || 'Erro desconhecido'));
            // Restaurar valor original
            cancelarEdicaoInline(disciplinaId);
        }
    })
    .catch(error => {
        console.error('❌ Erro ao salvar disciplina:', error);
        showAlert('danger', 'Erro ao salvar disciplina: ' + error.message);
        cancelarEdicaoInline(disciplinaId);
    });
}

// Função para atualizar exibição do campo
function atualizarExibicaoCampo(disciplinaId, campo, novoValor) {
    console.log('🔄 Atualizando exibição do campo ' + campo + ' para valor: "' + novoValor + '"');
    
    const elemento = document.querySelector('[data-disciplina-id="' + disciplinaId + '"][data-field="' + campo + '"]');
    if (!elemento) {
        console.error('❌ Elemento não encontrado: [data-disciplina-id="' + disciplinaId + '"][data-field="' + campo + '"]');
        return;
    }
    
    const input = elemento.parentNode.querySelector('input, textarea');
    console.log('🔍 Input encontrado:', input ? 'Sim' : 'Não');
    
    if (input) {
        // Remover input
        input.remove();
        console.log('🗑️ Input removido');
        
        // Restaurar elemento original
        elemento.style.display = 'inline';
        
        // Atualizar valor com formatação adequada
        let valorExibido = '';
        if (campo === 'carga_horaria_padrao') {
            valorExibido = novoValor + 'h';
        } else if (campo === 'descricao' && (!novoValor || novoValor.trim() === '')) {
            valorExibido = 'Sem descrição';
        } else {
            valorExibido = novoValor || '';
        }
        
        elemento.textContent = valorExibido;
        console.log('✅ Valor atualizado no DOM: "' + valorExibido + '"');
        
        // Atualizar dados originais para futuras edições
        if (!dadosOriginais[disciplinaId]) {
            dadosOriginais[disciplinaId] = {};
        }
        dadosOriginais[disciplinaId][campo] = valorExibido;
        
        // Ocultar botões de ação
        ocultarBotoesEdicao(disciplinaId);
        console.log('✅ Exibição atualizada com sucesso');
    } else {
        console.error('❌ Input não encontrado para atualização');
    }
}

// Função para mostrar botões de edição
function mostrarBotoesEdicao(disciplinaId) {
    const btnSalvar = document.getElementById('btn-salvar-' + disciplinaId);
    const btnCancelar = document.getElementById('btn-cancelar-' + disciplinaId);
    
    if (btnSalvar) btnSalvar.style.display = 'inline-block';
    if (btnCancelar) btnCancelar.style.display = 'inline-block';
}

// Função para ocultar botões de edição
function ocultarBotoesEdicao(disciplinaId) {
    const btnSalvar = document.getElementById('btn-salvar-' + disciplinaId);
    const btnCancelar = document.getElementById('btn-cancelar-' + disciplinaId);
    
    if (btnSalvar) btnSalvar.style.display = 'none';
    if (btnCancelar) btnCancelar.style.display = 'none';
}

// Função para salvar disciplina completa (botão salvar)
function salvarDisciplinaInline(disciplinaId) {
    console.log('💾 Salvando disciplina completa: ' + disciplinaId);
    
    // Coletar todos os campos editados usando FormData
    const formData = new FormData();
    formData.append('acao', 'editar');
    formData.append('id', disciplinaId);
    
    let camposEditados = 0;
    const campos = ['nome', 'codigo', 'carga_horaria_padrao', 'descricao'];
    campos.forEach(campo => {
        const elemento = document.querySelector('[data-disciplina-id="' + disciplinaId + '"][data-field="' + campo + '"]');
        const input = elemento?.parentNode?.querySelector('input, textarea');
        
        if (input) {
            formData.append(campo, input.value.trim());
            camposEditados++;
        }
    });
    
    if (camposEditados === 0) {
        showAlert('warning', 'Nenhuma alteração foi feita');
        return;
    }
    
    // Enviar para API
    fetch(getBasePath() + '/admin/api/disciplinas-clean.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            // Atualizar todos os campos
            campos.forEach(campo => {
                const elemento = document.querySelector('[data-disciplina-id="' + disciplinaId + '"][data-field="' + campo + '"]');
                const input = elemento?.parentNode?.querySelector('input, textarea');
                
                if (input) {
                    const novoValor = input.value.trim();
                    input.remove();
                    elemento.style.display = 'inline';
                    
                    if (campo === 'carga_horaria_padrao') {
                        elemento.textContent = novoValor + 'h';
                    } else {
                        elemento.textContent = novoValor || (campo === 'descricao' ? 'Sem descrição' : '');
                    }
                }
            });
            
            showAlert('success', data.mensagem);
            ocultarBotoesEdicao(disciplinaId);
            // Não recarregar tudo para evitar conflitos
            console.log('✅ Disciplina salva com sucesso, exibição atualizada');
        } else {
            showAlert('danger', 'Erro: ' + data.mensagem);
        }
    })
    .catch(error => {
        console.error('Erro ao salvar disciplina:', error);
        showAlert('danger', 'Erro ao salvar disciplina');
    });
}

// Função para cancelar edição
function cancelarEdicaoInline(disciplinaId) {
    console.log('❌ Cancelando edição: ' + disciplinaId);
    
    const campos = ['nome', 'codigo', 'carga_horaria_padrao', 'descricao'];
    campos.forEach(campo => {
        const elemento = document.querySelector('[data-disciplina-id="' + disciplinaId + '"][data-field="' + campo + '"]');
        const input = elemento?.parentNode?.querySelector('input, textarea');
        
        if (input) {
            // Restaurar valor original
            const valorOriginal = dadosOriginais[disciplinaId]?.[campo] || '';
            input.remove();
            elemento.style.display = 'inline';
            elemento.textContent = valorOriginal;
        }
    });
    
    ocultarBotoesEdicao(disciplinaId);
    
    // Limpar dados originais
    if (dadosOriginais[disciplinaId]) {
        delete dadosOriginais[disciplinaId];
    }
}

// Função para editar disciplina (modal - mantida para compatibilidade)
function editarDisciplina(id) {
    console.log('✏️ Editando disciplina ID:', id);
    
    // Verificar se o modal de edição existe
    let modalEditar = document.getElementById('modalEditarDisciplina');
    
    // Se o modal não existir, criar um modal de edição dinâmico
    if (!modalEditar) {
        console.log('🔧 Modal de edição não encontrado, criando modal dinâmico...');
        modalEditar = criarModalEditarDisciplina();
        document.body.appendChild(modalEditar);
    }
    
    const editId = document.getElementById('edit_id');
    const editCodigo = document.getElementById('edit_codigo');
    
    if (!editId || !editCodigo) {
        console.error('❌ Elementos de edição não encontrados após criar modal');
        alert('Erro ao criar formulário de edição.');
        return;
    }
    
    // Buscar dados da disciplina
    fetch(getBasePath() + '/admin/api/disciplinas-clean.php?acao=listar')
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                const disciplina = data.disciplinas.find(d => d.id == id);
                if (disciplina) {
                    // Preencher formulário de edição
                    editId.value = disciplina.id;
                    editCodigo.value = disciplina.codigo;
                    
                    const editNome = document.getElementById('edit_nome');
                    const editDescricao = document.getElementById('edit_descricao');
                    const editCargaHoraria = document.getElementById('edit_carga_horaria_padrao');
                    const editCor = document.getElementById('edit_cor_hex');
                    const editIcone = document.getElementById('edit_icone');
                    
                    if (editNome) editNome.value = disciplina.nome;
                    if (editDescricao) editDescricao.value = disciplina.descricao || '';
                    if (editCargaHoraria) editCargaHoraria.value = disciplina.carga_horaria_padrao;
                    if (editCor) editCor.value = disciplina.cor_hex;
                    if (editIcone) editIcone.value = disciplina.icone;
                    
                    // Abrir modal
                    const modal = new bootstrap.Modal(document.getElementById('modalEditarDisciplina'));
                    modal.show();
                } else {
                    showAlert('danger', 'Disciplina não encontrada');
                }
            }
        })
        .catch(error => {
            console.error('Erro ao buscar disciplina:', error);
            showAlert('danger', 'Erro ao buscar dados da disciplina');
        });
}

// Função para excluir disciplina
function excluirDisciplina(id) {
    // Buscar dados da disciplina para exibir no modal de confirmação
    fetch(getBasePath() + '/admin/api/disciplinas-clean.php?acao=listar')
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
    
    fetch(getBasePath() + '/admin/api/disciplinas-clean.php', {
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
    
    fetch(getBasePath() + '/admin/api/disciplinas-clean.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            showAlert('success', data.mensagem);
            recarregarDisciplinas();
            
            // Fechar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarDisciplina'));
            modal.hide();
        } else {
            showAlert('danger', 'Erro: ' + data.mensagem);
        }
    })
    .catch(error => {
        console.error('Erro ao editar disciplina:', error);
        showAlert('danger', 'Erro ao editar disciplina: ' + error.message);
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
        const botao = document.querySelector('button[onclick="navegarParaEtapa(' + i + ')"]');
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
    
    // Verificar se há parâmetro de edição de curso
    const urlParams = new URLSearchParams(window.location.search);
    const editarCursoId = urlParams.get('editar_curso');
    const editarDisciplinaId = urlParams.get('editar_disciplina');
    
    if (editarCursoId) {
        console.log('🎯 Parâmetro editar_curso detectado:', editarCursoId);
        // Aguardar um pouco para garantir que tudo esteja carregado
        setTimeout(() => {
            // Buscar dados do curso via API
            fetch(getBasePath() + '/admin/api/tipos-curso-clean.php?acao=listar')
                .then(response => response.json())
                .then(data => {
                    if (data.sucesso && data.dados) {
                        const curso = data.dados.find(c => c.id == editarCursoId);
                        if (curso) {
                            console.log('📝 Abrindo modal de edição para curso:', curso.nome);
                            editarTipoCurso(
                                curso.id,
                                curso.codigo,
                                curso.nome,
                                curso.descricao,
                                curso.carga_horaria_total,
                                curso.ativo
                            );
                        } else {
                            console.error('❌ Curso não encontrado:', editarCursoId);
                        }
                    }
                })
                .catch(error => {
                    console.error('❌ Erro ao buscar dados do curso:', error);
                });
        }, 500);
    }
    
    if (editarDisciplinaId) {
        console.log('🎯 Parâmetro editar_disciplina detectado:', editarDisciplinaId);
        // Aguardar um pouco para garantir que tudo esteja carregado
        setTimeout(() => {
            // Buscar dados da disciplina via API
            fetch(getBasePath() + '/admin/api/disciplinas-clean.php?acao=listar')
                .then(response => response.json())
                .then(data => {
                    if (data.sucesso && data.disciplinas) {
                        const disciplina = data.disciplinas.find(d => d.id == editarDisciplinaId);
                        if (disciplina) {
                            console.log('📝 Abrindo modal de edição para disciplina:', disciplina.nome);
                            editarDisciplina(disciplina.id);
                        } else {
                            console.error('❌ Disciplina não encontrada:', editarDisciplinaId);
                        }
                    }
                })
                .catch(error => {
                    console.error('❌ Erro ao buscar dados da disciplina:', error);
                });
        }, 500);
    }
    
    // Debug: verificar se os botões existem
    const botoes = document.querySelectorAll('.wizard-step-btn');
    console.log('🔍 Botões encontrados:', botoes.length);
    
    botoes.forEach((botao, index) => {
        console.log('Botão ' + (index + 1) + ':', botao.textContent.trim(), 'onclick:', botao.onclick);
        
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
            console.log((index + 1) + '. ' + el.tag + '.' + el.cls + ' - overflowY: ' + el.overflowY + ', maxH: ' + el.maxH + ', h: ' + el.h);
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

<!-- JavaScript das Funções do Modal - Carregamento Garantido -->
<script>
// ==========================================
// FUNÇÕES DO MODAL DE DISCIPLINAS - VERSÃO SIMPLIFICADA
// ==========================================

// Variáveis já declaradas no script principal - não redeclarar

// Função fecharModalDisciplinas já existe no script principal - não duplicar

// Função centralizada para gerenciar estilos do body
function gerenciarEstilosBody(acao) {
    console.log('🔧 [BODY] Gerenciando estilos do body:', acao);
    
    if (acao === 'bloquear') {
        // Bloquear scroll do body
        document.body.style.setProperty('overflow', 'hidden', 'important');
        document.body.style.setProperty('position', 'fixed', 'important');
        document.body.style.setProperty('width', '100%', 'important');
        document.body.style.setProperty('height', '100%', 'important');
        document.documentElement.style.setProperty('overflow', 'hidden', 'important');
        
        console.log('✅ [BODY] Body bloqueado');
    } else if (acao === 'restaurar') {
        // Restaurar scroll do body
        document.body.style.setProperty('overflow', 'auto', 'important');
        document.body.style.setProperty('position', 'static', 'important');
        document.body.style.setProperty('width', 'auto', 'important');
        document.body.style.setProperty('height', 'auto', 'important');
        document.documentElement.style.setProperty('overflow', 'auto', 'important');
        
        console.log('✅ [BODY] Body restaurado');
    }
    
    // Verificar se funcionou
    const bodyComputed = window.getComputedStyle(document.body);
    console.log('🔍 [BODY] Verificação - Overflow:', bodyComputed.overflow, 'Position:', bodyComputed.position);
}

// Função criarModalDisciplinas já existe no script principal - não duplicar

// Função abrirModalDisciplinasInterno já existe no script principal - não duplicar

// Tornar as funções globalmente acessíveis
window.fecharModalDisciplinas = fecharModalDisciplinas;
window.criarModalDisciplinas = criarModalDisciplinas;
window.abrirModalDisciplinasInterno = abrirModalDisciplinasInterno;
window.gerenciarEstilosBody = gerenciarEstilosBody;
window.carregarDisciplinasModal = carregarDisciplinasModal;
window.editarDisciplina = editarDisciplina;

// Configurar event listeners para os botões do modal
function configurarBotoesModal() {
    console.log('🔧 [CONFIG] Configurando botões do modal...');
    
    // Configurar botão X
    const botaoX = document.querySelector('#modalGerenciarDisciplinas .popup-modal-close');
    if (botaoX) {
        console.log('✅ [CONFIG] Botão X encontrado');
        
        // Remover event listeners existentes
        botaoX.onclick = null;
        botaoX.removeEventListener('click', fecharModalDisciplinas);
        
        // Adicionar novo event listener
        botaoX.addEventListener('click', function(e) {
            console.log('🔧 [CLICK] Botão X clicado!');
            e.preventDefault();
            e.stopPropagation();
            fecharModalDisciplinas();
        });
        
        console.log('✅ [CONFIG] Botão X configurado');
    } else {
        console.error('❌ [CONFIG] Botão X não encontrado');
    }
    
    // Configurar botão Fechar
    const botaoFechar = document.querySelector('#modalGerenciarDisciplinas .popup-secondary-button');
    if (botaoFechar) {
        console.log('✅ [CONFIG] Botão Fechar encontrado');
        
        // Remover event listeners existentes
        botaoFechar.onclick = null;
        botaoFechar.removeEventListener('click', fecharModalDisciplinas);
        
        // Adicionar novo event listener
        botaoFechar.addEventListener('click', function(e) {
            console.log('🔧 [CLICK] Botão Fechar clicado!');
            e.preventDefault();
            e.stopPropagation();
            fecharModalDisciplinas();
        });
        
        console.log('✅ [CONFIG] Botão Fechar configurado');
    } else {
        console.error('❌ [CONFIG] Botão Fechar não encontrado');
    }
}

// Função abrirModalDisciplinasInterno já existe - não redefinir

// Log de teste para verificar se o script está carregando
console.log('✅ [SCRIPT] Script de turmas-teoricas.php carregado!');
console.log('✅ [SCRIPT] Função fecharModalDisciplinas disponível:', typeof window.fecharModalDisciplinas);
console.log('✅ [SCRIPT] Função criarModalDisciplinas disponível:', typeof window.criarModalDisciplinas);
console.log('✅ [SCRIPT] Função abrirModalDisciplinasInterno disponível:', typeof window.abrirModalDisciplinasInterno);
console.log('✅ [SCRIPT] Função carregarDisciplinasModal disponível:', typeof window.carregarDisciplinasModal);

// ==========================================
// FUNÇÕES PARA CARREGAMENTO AUTOMÁTICO DE DISCIPLINAS
// ==========================================

/**
 * Carregar disciplinas automaticamente baseadas no tipo de curso selecionado
 */
function carregarDisciplinasAutomaticas(cursoTipo) {
    console.log('🔄 Carregando disciplinas automaticamente para curso:', cursoTipo);
    
    // Mostrar loading
    const disciplinasLista = document.getElementById('disciplinas-lista');
    if (disciplinasLista) {
        disciplinasLista.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-spinner fa-spin me-2"></i>
                Carregando disciplinas...
            </div>
        `;
    }
    
    // Fazer requisição para a API
    fetch(`${getBasePath()}/admin/api/disciplinas-automaticas.php?acao=carregar_disciplinas&curso_tipo=${encodeURIComponent(cursoTipo)}`)
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                console.log('✅ Disciplinas carregadas automaticamente:', data.disciplinas);
                
                // Verificar se há disciplinas ou se é uma mensagem amigável
                if (data.sem_disciplinas) {
                    console.log('ℹ️ Curso sem disciplinas configuradas:', data.mensagem);
                    mostrarMensagemAmigavel(data.mensagem);
                } else {
                    exibirDisciplinasAutomaticas(data.disciplinas);
                    atualizarContadorDisciplinasAutomaticas(data.total);
                    atualizarTotalHorasAutomaticas(data.disciplinas);
                    
                    // Esconder alerta de info e mostrar botão de recarregar
                    const alertInfo = document.querySelector('#disciplinas-automaticas-container .alert-info');
                    if (alertInfo) {
                        alertInfo.style.display = 'none';
                    }
                    const btnRecarregar = document.getElementById('btn-recarregar-disciplinas');
                    if (btnRecarregar) {
                        btnRecarregar.style.display = 'inline-block';
                    }
                }
            } else {
                console.error('❌ Erro ao carregar disciplinas:', data.mensagem);
                mostrarErroDisciplinas(data.mensagem);
            }
        })
        .catch(error => {
            console.error('❌ Erro na requisição:', error);
            mostrarErroDisciplinas('Erro de conexão ao carregar disciplinas');
        });
}

/**
 * Exibir disciplinas carregadas automaticamente
 */
function exibirDisciplinasAutomaticas(disciplinas) {
    const disciplinasLista = document.getElementById('disciplinas-lista');
    if (!disciplinasLista) return;
    
    if (disciplinas.length === 0) {
        disciplinasLista.innerHTML = `
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Nenhuma disciplina configurada para este tipo de curso.
            </div>
        `;
        return;
    }
    
    let html = '<div class="row">';
    
    disciplinas.forEach((disciplina, index) => {
        html += `
            <div class="col-md-6 mb-3">
                <div class="card disciplina-card-automatica" style="border-left: 4px solid ${disciplina.cor}">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="card-title mb-1" style="color: ${disciplina.cor}">
                                    <i class="fas fa-${disciplina.icone} me-2"></i>
                                    ${disciplina.text}
                                </h6>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    ${disciplina.aulas} aulas obrigatórias
                                </small>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="badge" style="background-color: ${disciplina.cor}">
                                    ${disciplina.aulas}h
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    disciplinasLista.innerHTML = html;
}

/**
 * Limpar disciplinas automáticas
 */
function limparDisciplinasAutomaticas() {
    console.log('🧹 Limpando disciplinas automáticas');
    
    const disciplinasLista = document.getElementById('disciplinas-lista');
    if (disciplinasLista) {
        disciplinasLista.innerHTML = '';
    }
    
    atualizarContadorDisciplinasAutomaticas(0);
    atualizarTotalHorasAutomaticas([]);
    
    // Mostrar alerta de info novamente
    const alertInfo = document.querySelector('#disciplinas-automaticas-container .alert-info');
    if (alertInfo) {
        alertInfo.style.display = 'block';
    }
    const btnRecarregar = document.getElementById('btn-recarregar-disciplinas');
    if (btnRecarregar) {
        btnRecarregar.style.display = 'none';
    }
}

/**
 * Recarregar disciplinas automáticas
 */
function recarregarDisciplinasAutomaticas() {
    const cursoSelect = document.getElementById('curso_tipo');
    if (cursoSelect && cursoSelect.value) {
        carregarDisciplinasAutomaticas(cursoSelect.value);
    }
}

/**
 * Atualizar contador de disciplinas automáticas
 */
function atualizarContadorDisciplinasAutomaticas(total) {
    const contador = document.getElementById('contador-disciplinas');
    if (contador) {
        contador.textContent = total;
    }
}

/**
 * Atualizar total de horas automáticas
 */
function atualizarTotalHorasAutomaticas(disciplinas) {
    const totalHoras = disciplinas.reduce((total, disciplina) => total + parseInt(disciplina.aulas), 0);
    const totalHorasElement = document.getElementById('total-horas-disciplinas');
    if (totalHorasElement) {
        totalHorasElement.textContent = totalHoras;
    }
}

/**
 * Mostrar erro ao carregar disciplinas
 */
function mostrarErroDisciplinas(mensagem) {
    const disciplinasLista = document.getElementById('disciplinas-lista');
    if (disciplinasLista) {
        disciplinasLista.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong>Erro:</strong> ${mensagem}
            </div>
        `;
    }
}

/**
 * Mostrar mensagem amigável quando não há disciplinas
 */
function mostrarMensagemAmigavel(mensagem) {
    const disciplinasLista = document.getElementById('disciplinas-lista');
    if (disciplinasLista) {
        disciplinasLista.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Informação:</strong> ${mensagem}
            </div>
        `;
    }
    
    // Atualizar contadores para zero
    atualizarContadorDisciplinasAutomaticas(0);
    atualizarTotalHorasAutomaticas([]);
    
    // Mostrar botão de recarregar
    const btnRecarregar = document.getElementById('btn-recarregar-disciplinas');
    if (btnRecarregar) {
        btnRecarregar.style.display = 'inline-block';
    }
}

// Tornar funções globalmente acessíveis
window.carregarDisciplinasAutomaticas = carregarDisciplinasAutomaticas;
window.limparDisciplinasAutomaticas = limparDisciplinasAutomaticas;
window.recarregarDisciplinasAutomaticas = recarregarDisciplinasAutomaticas;
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
