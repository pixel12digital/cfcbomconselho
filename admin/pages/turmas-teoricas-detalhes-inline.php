<?php
/**
 * Página de Detalhes da Turma Teórica com Edição Inline
 * Exibe informações completas da turma e permite edição direta nos campos
 */

// Obter turma_id da URL
$turmaId = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 0;

// Verificar se há turma_id
if (!$turmaId) {
    echo '<div class="alert alert-danger">Turma não especificada.</div>';
    return;
}

// Processar agendamento de aula via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'agendar_aula') {
    $dadosAula = [
        'turma_id' => isset($_POST['turma_id']) ? (int)$_POST['turma_id'] : 0,
        'disciplina' => $_POST['disciplina'] ?? '',
        'instrutor_id' => isset($_POST['instrutor_id']) ? (int)$_POST['instrutor_id'] : 0,
        'data_aula' => $_POST['data_aula'] ?? '',
        'hora_inicio' => $_POST['hora_inicio'] ?? '',
        'quantidade_aulas' => isset($_POST['quantidade_aulas']) ? (int)$_POST['quantidade_aulas'] : 1,
        'criado_por' => getCurrentUser()['id'] ?? 1
    ];
    
    // Detectar se é requisição AJAX
    $isAjax = (
        !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
    ) || (
        !empty($_SERVER['HTTP_ACCEPT']) && 
        strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
    ) || (
        isset($_POST['ajax']) && $_POST['ajax'] === 'true'
    );
    
    if ($isAjax && $dadosAula['turma_id'] && $dadosAula['disciplina']) {
        $resultado = $turmaManager->agendarAula($dadosAula);
        
        // Limpar qualquer output anterior
        if (ob_get_level()) {
            ob_clean();
        }
        header('Content-Type: application/json');
        echo json_encode($resultado);
        exit;
    }
}

// Processar remoção de aluno da turma
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'remover_aluno') {
    $alunoId = isset($_POST['aluno_id']) ? (int)$_POST['aluno_id'] : 0;

    if ($alunoId) {
        $resultado = $turmaManager->removerAluno($turmaId, $alunoId);
        
        // Detectar se é requisição AJAX (múltiplas formas)
        $isAjax = (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
        ) || (
            !empty($_SERVER['HTTP_ACCEPT']) && 
            strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
        ) || (
            isset($_POST['ajax']) && $_POST['ajax'] === 'true'
        );
        
        if ($isAjax) {
            // Limpar qualquer output anterior
            if (ob_get_level()) {
                ob_clean();
            }
            echo json_encode($resultado);
            exit;
        } else {
            // Se não for AJAX, mostrar mensagem na página
            if ($resultado['sucesso']) {
                echo '<div class="alert alert-success">' . $resultado['mensagem'] . '</div>';
            } else {
                echo '<div class="alert alert-danger">' . $resultado['mensagem'] . '</div>';
            }
            return;
        }
    } else {
        $resultado = ['sucesso' => false, 'mensagem' => '❌ ID do aluno inválido.'];
        
        $isAjax = (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
        );
        
        if ($isAjax) {
            if (ob_get_level()) {
                ob_clean();
            }
            echo json_encode($resultado);
            exit;
        }
    }
}

// Obter dados da turma
$resultadoTurma = $turmaManager->obterTurma($turmaId);
if (!$resultadoTurma['sucesso']) {
    echo '<div class="alert alert-danger">Erro ao carregar turma: ' . $resultadoTurma['mensagem'] . '</div>';
    return;
}

$turma = $resultadoTurma['dados'];

// Obter dados do usuário atual
$user = getCurrentUser();

// Obter tipos de curso disponíveis
$tiposCurso = [
    'formacao_45h' => 'Curso de Formação de Condutores - Permissão 45h',
    'formacao_acc_20h' => 'Curso de Formação de Condutores - ACC 20h',
    'reciclagem_infrator' => 'Curso de Reciclagem para Condutor Infrator',
    'atualizacao' => 'Curso de Atualização'
];

// Obter salas cadastradas usando o mesmo método da página de criação
$salasCadastradas = $turmaManager->obterSalasDisponiveis($user['cfc_id'] ?? 1);

// Função para obter nome da sala pelo ID
function obterNomeSala($salaId, $salasCadastradas) {
    foreach ($salasCadastradas as $sala) {
        if ($sala['id'] == $salaId) {
            return $sala['nome'];
        }
    }
    return 'Sala não encontrada';
}

// Gerar horários disponíveis (07:00 às 21:10, intervalos de 50min)
$horariosDisponiveis = [
    '07:00', '07:50', '08:40', '09:30', '10:20', '11:10',
    '13:00', '13:50', '14:40', '15:30', '16:20', '17:10', '18:00',
    '18:50', '19:40', '20:30', '21:10'
];

// Obter disciplinas do curso para agendamento
$disciplinasCurso = $turmaManager->obterDisciplinasParaAgendamento($turmaId);

// Obter instrutores disponíveis para agendamento (após ter $turma definido)
try {
    $cfcId = $turma['cfc_id'] ?? $user['cfc_id'] ?? 1;
    
    // Primeiro tentar buscar instrutores do CFC da turma com ativo = 1 ou ativo = true
    $instrutores = $db->fetchAll("
        SELECT i.id, 
               COALESCE(u.nome, i.nome, 'Instrutor sem nome') as nome,
               i.categoria_habilitacao 
        FROM instrutores i 
        LEFT JOIN usuarios u ON i.usuario_id = u.id 
        WHERE (i.ativo = 1 OR i.ativo = TRUE OR i.ativo IS NULL) AND i.cfc_id = ?
        ORDER BY COALESCE(u.nome, i.nome, '') ASC
    ", [$cfcId]);
    
    // Se não encontrou instrutores, buscar todos os ativos do CFC (sem filtro de ativo)
    if (empty($instrutores)) {
        $instrutores = $db->fetchAll("
            SELECT i.id, 
                   COALESCE(u.nome, i.nome, 'Instrutor sem nome') as nome,
                   i.categoria_habilitacao 
            FROM instrutores i 
            LEFT JOIN usuarios u ON i.usuario_id = u.id 
            WHERE i.cfc_id = ?
            ORDER BY COALESCE(u.nome, i.nome, '') ASC
        ", [$cfcId]);
    }
    
    // Se ainda não encontrou, buscar todos os ativos independente do CFC
    if (empty($instrutores)) {
        $instrutores = $db->fetchAll("
            SELECT i.id, 
                   COALESCE(u.nome, i.nome, 'Instrutor sem nome') as nome,
                   i.categoria_habilitacao 
            FROM instrutores i 
            LEFT JOIN usuarios u ON i.usuario_id = u.id 
            WHERE (i.ativo = 1 OR i.ativo = TRUE OR i.ativo IS NULL)
            ORDER BY COALESCE(u.nome, i.nome, '') ASC
        ");
    }
    
    // Último fallback: buscar TODOS os instrutores
    if (empty($instrutores)) {
        $instrutores = $db->fetchAll("
            SELECT i.id, 
                   COALESCE(u.nome, i.nome, 'Instrutor sem nome') as nome,
                   i.categoria_habilitacao 
            FROM instrutores i 
            LEFT JOIN usuarios u ON i.usuario_id = u.id 
            ORDER BY COALESCE(u.nome, i.nome, '') ASC
        ");
    }
    
    // Log para debug
    if (empty($instrutores)) {
        error_log("Nenhum instrutor encontrado no banco. CFC ID usado: " . $cfcId);
    } else {
        error_log("Instrutores encontrados: " . count($instrutores) . " para CFC ID: " . $cfcId);
    }
} catch (Exception $e) {
    error_log("Erro ao buscar instrutores: " . $e->getMessage());
    $instrutores = [];
}

// Obter progresso das disciplinas
$progressoDisciplinas = $turmaManager->obterProgressoDisciplinas($turmaId);

// Obter aulas agendadas
try {
    $aulasAgendadas = $db->fetchAll(
        "SELECT * FROM turma_aulas_agendadas WHERE turma_id = ? ORDER BY data_aula, hora_inicio",
        [$turmaId]
    );
} catch (Exception $e) {
    $aulasAgendadas = [];
}

// Calcular estatísticas
$totalAulas = count($aulasAgendadas);
$totalMinutosAgendados = array_sum(array_column($aulasAgendadas, 'duracao_minutos'));

// Calcular carga horária total do curso baseado nas disciplinas obrigatórias
try {
    $cargaHorariaTotalCurso = $db->fetch(
        "SELECT SUM(aulas_obrigatorias * 50) as total_minutos 
         FROM disciplinas_configuracao 
         WHERE curso_tipo = ? AND ativa = 1",
        [$turma['curso_tipo']]
    );
    $totalMinutosCurso = (int)($cargaHorariaTotalCurso['total_minutos'] ?? 0);
} catch (Exception $e) {
    $totalMinutosCurso = 0;
}

// Calcular carga horária restante (total do curso - já agendada)
$cargaHorariaRestante = max(0, $totalMinutosCurso - $totalMinutosAgendados);
$totalHoras = round($cargaHorariaRestante / 60, 1);

// Obter alunos matriculados (se a tabela existir)
try {
    $alunosMatriculados = $db->fetchAll(
        "SELECT COUNT(*) as total FROM turma_alunos WHERE turma_id = ?",
        [$turmaId]
    );
    $totalAlunos = $alunosMatriculados[0]['total'] ?? 0;
} catch (Exception $e) {
    $totalAlunos = 0;
}

// Obter disciplinas selecionadas
$disciplinasSelecionadas = $turmaManager->obterDisciplinasSelecionadas($turmaId);

// Obter alunos matriculados na turma
try {
    $alunosMatriculados = $db->fetchAll("
        SELECT 
            ta.*,
            a.nome,
            a.cpf,
            a.categoria_cnh,
            a.telefone,
            a.email,
            c.nome as cfc_nome
        FROM turma_alunos ta
        JOIN alunos a ON ta.aluno_id = a.id
        JOIN cfcs c ON a.cfc_id = c.id
        WHERE ta.turma_id = ? 
        ORDER BY ta.data_matricula DESC, a.nome
    ", [$turmaId]);
} catch (Exception $e) {
    $alunosMatriculados = [];
}

// Debug: Verificar disciplinas obtidas
echo "<!-- DEBUG: Total de disciplinas obtidas: " . count($disciplinasSelecionadas) . " -->";
echo "<!-- DEBUG: Turma ID: " . $turmaId . " -->";
echo "<!-- DEBUG: Curso tipo: " . ($turma['curso_tipo'] ?? 'N/A') . " -->";
foreach ($disciplinasSelecionadas as $index => $disciplina) {
    echo "<!-- DEBUG: Disciplina $index: " . var_export($disciplina, true) . " -->";
    echo "<!-- DEBUG: Disciplina $index ID: " . var_export($disciplina['disciplina_id'] ?? 'N/A', true) . " -->";
    echo "<!-- DEBUG: Disciplina $index Nome: " . var_export($disciplina['nome_disciplina'] ?? 'N/A', true) . " -->";
}

// Obter estatísticas de aulas para cada disciplina
$estatisticasDisciplinas = [];
$historicoAgendamentos = [];

foreach ($disciplinasSelecionadas as $disciplina) {
    $disciplinaId = $disciplina['disciplina_id'];
    
    // Buscar aulas agendadas para esta disciplina (excluindo canceladas)
    $aulasAgendadas = $db->fetch(
        "SELECT COUNT(*) as total FROM turma_aulas_agendadas WHERE turma_id = ? AND disciplina = ? AND status != 'cancelada'",
        [$turmaId, $disciplinaId]
    );
    
    // Buscar aulas realizadas (status = 'realizada')
    $aulasRealizadas = $db->fetch(
        "SELECT COUNT(*) as total FROM turma_aulas_agendadas WHERE turma_id = ? AND disciplina = ? AND status = 'realizada'",
        [$turmaId, $disciplinaId]
    );
    
    // Buscar histórico completo de agendamentos para esta disciplina
    // Ordenar por ordem_disciplina para garantir ordem correta e excluir canceladas do histórico
    $agendamentosDisciplina = $db->fetchAll(
        "SELECT 
            taa.*,
            COALESCE(u.nome, i.nome, 'Não informado') as instrutor_nome,
            COALESCE(s.nome, 'Não informada') as sala_nome
         FROM turma_aulas_agendadas taa
         LEFT JOIN instrutores i ON taa.instrutor_id = i.id
         LEFT JOIN usuarios u ON i.usuario_id = u.id
         LEFT JOIN salas s ON taa.sala_id = s.id
         WHERE taa.turma_id = ? 
         AND taa.disciplina = ? 
         AND taa.status != 'cancelada'
         ORDER BY taa.ordem_disciplina ASC, taa.data_aula ASC, taa.hora_inicio ASC",
        [$turmaId, $disciplinaId]
    );
    
    $totalAgendadas = $aulasAgendadas['total'] ?? 0;
    $totalRealizadas = $aulasRealizadas['total'] ?? 0;
    $totalObrigatorias = $disciplina['carga_horaria_padrao'] ?? 0;
    $totalFaltantes = max(0, $totalObrigatorias - $totalAgendadas);
    
    $estatisticasDisciplinas[$disciplinaId] = [
        'agendadas' => $totalAgendadas,
        'realizadas' => $totalRealizadas,
        'faltantes' => $totalFaltantes,
        'obrigatorias' => $totalObrigatorias
    ];
    
    $historicoAgendamentos[$disciplinaId] = $agendamentosDisciplina;
}

?>

<style>
/* ==========================================
   ESTILOS PARA CARDS DE ESTATÍSTICAS
   ========================================== */
.disciplina-stats-card {
    position: relative;
    cursor: pointer !important;
}

.disciplina-stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
}

.disciplina-stats-card:active {
    transform: translateY(-2px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2) !important;
}

/* ==========================================
   ESTILOS PARA EDIÇÃO INLINE
   ========================================== */
.inline-edit {
    position: relative;
    cursor: pointer;
    padding: 8px 12px;
    border-radius: 6px;
    transition: all 0.2s ease;
    border: 2px solid transparent;
    display: inline-block;
    min-width: 150px;
    word-wrap: break-word;
    overflow-wrap: break-word;
    white-space: normal;
    max-width: 100%;
    box-sizing: border-box;
}

.inline-edit:hover {
    background-color: #f8f9fa;
    border-color: transparent;
}

.inline-edit.editing {
    background-color: white;
    border-color: #023A8D;
    box-shadow: 0 0 0 3px rgba(2, 58, 141, 0.1);
    padding: 8px 12px;
}

.inline-edit input, 
.inline-edit select, 
.inline-edit textarea {
    border: none;
    background: transparent;
    width: 100%;
    font-size: inherit;
    font-weight: inherit;
    color: inherit;
    padding: 0;
    margin: 0;
    min-height: auto;
    line-height: inherit;
    font-family: inherit;
}

.inline-edit input:focus, 
.inline-edit select:focus, 
.inline-edit textarea:focus {
    outline: none;
    background: white;
    padding: 8px 12px;
    border-radius: 4px;
    box-shadow: 0 0 0 2px rgba(2, 58, 141, 0.2);
    border: 1px solid #023A8D;
    min-width: 200px;
    max-width: 100%;
    position: relative;
    z-index: 1060;
}

.edit-icon {
    position: absolute;
    top: 4px;
    right: 4px;
    opacity: 0.6;
    transition: opacity 0.3s ease;
    color: #023A8D;
    font-size: 12px;
    cursor: pointer;
    z-index: 1060;
}

.inline-edit:hover .edit-icon {
    opacity: 1;
    transform: scale(1.1);
}

/* ==========================================
   ESTILOS ESPECÍFICOS POR CAMPO
   ========================================== */
.inline-edit[data-field="nome"] {
    font-size: 1.5rem;
    font-weight: bold;
    color: #023A8D;
    min-width: 200px;
    max-width: 100%;
}

.inline-edit[data-field="curso_tipo"] {
    min-width: 400px !important;
    max-width: none !important;
    width: fit-content !important;
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
    white-space: nowrap !important;
    display: block !important;
    vertical-align: top !important;
    line-height: 1.4 !important;
    overflow: visible !important;
    text-overflow: unset !important;
}

.inline-edit[data-field="data_inicio"], 
.inline-edit[data-field="data_fim"] {
    font-family: monospace;
    background: #f8f9fa;
    border-radius: 4px;
    min-width: 120px;
    max-width: 100%;
    padding: 4px 8px;
    border: none;
}

.inline-edit[data-field="status"] {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
    text-transform: uppercase;
}

.inline-edit[data-field="modalidade"],
.inline-edit[data-field="sala_id"] {
    font-weight: 500;
}

.inline-edit[data-field="observacoes"] {
    font-style: italic;
    color: #666;
    min-width: 300px;
    max-width: 100%;
    word-wrap: break-word;
    overflow-wrap: break-word;
    white-space: pre-wrap;
}

/* ==========================================
   ESTILOS PARA DISCIPLINAS - REORGANIZADO
   ========================================== */

/* Títulos das seções */
.section-title {
    color: #023A8D;
    font-weight: 600;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e9ecef;
}

/* Seção de disciplinas cadastradas */
.disciplinas-cadastradas-section {
    margin-bottom: 30px;
}

/* Estatísticas de Aulas */
.aulas-stats-container {
    display: flex;
    gap: 12px;
    margin-top: 8px;
    flex-wrap: wrap;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 4px;
    background: #f8f9fa;
    padding: 4px 8px;
    border-radius: 6px;
    border: 1px solid #e9ecef;
    font-size: 0.85rem;
    min-width: fit-content;
}

.stat-label {
    color: #6c757d;
    font-weight: 500;
}

.stat-value {
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 4px;
    min-width: 20px;
    text-align: center;
}

.stat-agendadas {
    background: #e3f2fd;
    color: #1976d2;
    border: 1px solid #bbdefb;
}

.stat-realizadas {
    background: #e8f5e8;
    color: #2e7d32;
    border: 1px solid #c8e6c9;
}

.stat-faltantes {
    background: #fff3e0;
    color: #f57c00;
    border: 1px solid #ffcc02;
}

/* Estilos para botões de ação na tabela */
.btn-group .btn {
    min-width: 32px !important;
    min-height: 32px !important;
    padding: 6px 8px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    visibility: visible !important;
    opacity: 1 !important;
    border-width: 1px !important;
    font-size: 12px !important;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
    transition: all 0.2s ease !important;
    position: relative !important;
    z-index: 10 !important;
}

/* Sobrescrever qualquer CSS conflitante para ícones */
.btn-group .btn i,
.btn-group .btn i.fas,
.btn-group .btn i.fa-edit,
.btn-group .btn i.fa-times {
    font-family: "Font Awesome 6 Free" !important;
    font-weight: 900 !important;
    font-style: normal !important;
    font-variant: normal !important;
    text-rendering: auto !important;
    line-height: 1 !important;
    -webkit-font-smoothing: antialiased !important;
    -moz-osx-font-smoothing: grayscale !important;
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
    position: relative !important;
    z-index: 11 !important;
}

/* Tamanhos específicos para ícones */
.btn-group .btn i.fa-edit {
    font-size: 14px !important;
}

.btn-group .btn i.fa-times {
    font-size: 12px !important;
}

.btn-group .btn:hover {
    transform: scale(1.05) !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2) !important;
}

/* Estilos específicos para garantir visibilidade dos ícones */
.btn-group .btn-outline-primary {
    color: #0d6efd !important;
    border-color: #0d6efd !important;
    background-color: #ffffff !important;
    border-width: 2px !important;
    font-weight: bold !important;
    min-width: 32px !important;
    min-height: 32px !important;
    padding: 6px 8px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    visibility: visible !important;
    opacity: 1 !important;
    position: relative !important;
    z-index: 10 !important;
}

.btn-group .btn-outline-primary:hover {
    color: #ffffff !important;
    background-color: #0d6efd !important;
    border-color: #0d6efd !important;
}

.btn-group .btn-outline-primary i,
.btn-group .btn-outline-primary i.fas,
.btn-group .btn-outline-primary i.fa-edit {
    color: #0d6efd !important;
    font-size: 14px !important;
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
    font-weight: 900 !important;
    font-family: "Font Awesome 6 Free" !important;
    line-height: 1 !important;
    text-rendering: auto !important;
    -webkit-font-smoothing: antialiased !important;
    -moz-osx-font-smoothing: grayscale !important;
    position: relative !important;
    z-index: 11 !important;
}

.btn-group .btn-outline-primary:hover i,
.btn-group .btn-outline-primary:hover i.fas,
.btn-group .btn-outline-primary:hover i.fa-edit {
    color: #ffffff !important;
}

.btn-group .btn-outline-danger {
    color: #dc3545 !important;
    border-color: #dc3545 !important;
    background-color: #fff5f5 !important;
    border-width: 1px !important;
}

.btn-group .btn-outline-danger:hover {
    color: #fff !important;
    background-color: #dc3545 !important;
    border-color: #dc3545 !important;
}

.btn-group .btn-outline-danger i {
    color: #dc3545 !important;
    font-size: 12px !important;
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.btn-group .btn-outline-danger:hover i {
    color: #fff !important;
}

/* Responsividade para estatísticas */
@media (max-width: 768px) {
    .aulas-stats-container {
        gap: 8px;
        margin-top: 6px;
    }
    
    .stat-item {
        font-size: 0.8rem;
        padding: 3px 6px;
    }
    
    .stat-value {
        padding: 1px 4px;
        min-width: 18px;
    }
    
    .btn-group .btn {
        min-width: 28px !important;
        min-height: 28px !important;
        padding: 4px 6px !important;
    }
}

@media (max-width: 576px) {
    .aulas-stats-container {
        flex-direction: column;
        gap: 6px;
    }
    
    .stat-item {
        justify-content: space-between;
        width: 100%;
    }
}

.disciplina-cadastrada-card {
    background: #f8f9fa;
    border: none;
    border-left: 4px solid #023A8D;
    border-radius: 0;
    padding: 15px;
    margin-bottom: 10px;
    transition: all 0.3s ease;
}

.disciplina-cadastrada-card:hover {
    background: #e9ecef;
    border-left-width: 5px;
}

.disciplina-info-display {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}

.disciplina-nome-display h6 {
    margin: 0;
    color: #2c3e50;
    font-weight: 600;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #d4edda;
    color: #155724;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    margin-top: 5px;
}

.status-badge i {
    color: #28a745;
}

.disciplina-detalhes-display {
    display: flex;
    gap: 30px;
    flex: 1;
    justify-content: center;
}

.detalhe-item {
    text-align: center;
}

.detalhe-label {
    display: block;
    font-size: 0.8rem;
    color: #6c757d;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 5px;
}

.detalhe-valor {
    display: block;
    font-size: 1.2rem;
    font-weight: 700;
    color: #023A8D;
}

.disciplina-acoes {
    display: flex;
    gap: 10px;
}

/* Formulário de edição */
.disciplina-edit-form {
    background: #fff;
    border: 1px solid #007bff;
    border-radius: 8px;
    padding: 20px;
    margin-top: 15px;
}

.edit-form-header {
    margin-bottom: 15px;
}

.edit-form-header h6 {
    color: #007bff;
    margin: 0;
    font-weight: 600;
}

/* Seção para adicionar disciplinas */
.adicionar-disciplina-section {
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 20px;
}

.nova-disciplina-form {
    background: white;
    border-radius: 6px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

/* Responsividade */
@media (max-width: 768px) {
    .disciplina-info-display {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .disciplina-detalhes-display {
        flex-direction: column;
        gap: 15px;
        width: 100%;
    }
    
    .disciplina-acoes {
        width: 100%;
        justify-content: flex-end;
    }
}

/* Card principal da disciplina */
.disciplina-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    overflow: hidden;
}

.disciplina-card:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

/* Cabeçalho da disciplina */
.disciplina-header {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    padding: 16px 20px;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.disciplina-title {
    flex: 1;
}

.disciplina-nome {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 4px;
}

.disciplina-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #d4edda;
    color: #155724;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.disciplina-status i {
    color: #28a745;
}


/* Conteúdo da disciplina */
.disciplina-content {
    padding: 20px;
}

/* Inline layout for details + action on wider screens */
@media (min-width: 576px) {
    .disciplina-content {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    .disciplina-details {
        flex: 1;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        margin-bottom: 0;
    }
    .disciplina-actions {
        margin-left: auto;
    }
}

/* Mobile: stack items and make action button full width */
@media (max-width: 575.98px) {
    .disciplina-actions {
        margin-top: 12px;
        width: 100%;
    }
    .disciplina-actions .btn-edit-disciplina {
        width: 100%;
    }
}

.disciplina-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 16px;
}

/* Layout otimizado para desktop - detalhes da disciplina */
@media (min-width: 768px) {
    .disciplina-details {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 40px;
        margin-bottom: 20px;
    }
    
    .detail-item {
        flex: 1;
        text-align: center;
        padding: 16px;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }
    
    .detail-item label {
        font-size: 0.8rem;
        color: #6c757d;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
        display: block;
    }
    
    .horas-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #023A8D;
    }
    
    .aulas-count {
        font-size: 1.2rem;
        color: #495057;
        font-weight: 600;
    }
}

/* Estilos base para detail-item (mobile) */
.detail-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.detail-item label {
    font-size: 0.85rem;
    color: #6c757d;
    font-weight: 500;
    margin: 0;
}

.carga-horaria-display {
    display: flex;
    align-items: baseline;
    gap: 4px;
}

.horas-value {
    font-size: 1.2rem;
    font-weight: 600;
    color: #023A8D;
}

.horas-label {
    font-size: 0.9rem;
    color: #6c757d;
}

.aulas-count {
    font-size: 1rem;
    color: #495057;
    font-weight: 500;
}

/* Campos de edição - Layout otimizado para desktop */
.disciplina-edit-fields {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.edit-row {
    margin-bottom: 0;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group label {
    font-size: 0.85rem;
    color: #495057;
    font-weight: 600;
    margin: 0;
}

/* Layout em linha para desktop */
@media (min-width: 768px) {
    .disciplina-edit-fields {
        display: grid;
        grid-template-columns: 1fr 200px;
        gap: 20px;
        align-items: end;
    }
    
    .edit-row {
        margin-bottom: 0;
    }
    
    .form-group {
        margin-bottom: 0;
    }
    
    /* Primeira coluna - Disciplina */
    .edit-row:first-child {
        grid-column: 1;
    }
    
    /* Segunda coluna - Carga horária */
    .edit-row:last-child {
        grid-column: 2;
    }
    
    /* Campo de horas compacto */
    .input-group {
        width: 100%;
    }
    
    .disciplina-horas {
        width: 100%;
        text-align: center;
        font-weight: 600;
        font-size: 1rem;
    }
    
    .input-group-text {
        background: #023A8D;
        color: white;
        border: 1px solid #023A8D;
        font-weight: 600;
        padding: 0.5rem 0.75rem;
        min-width: 45px;
    }
    
    /* Select de disciplina melhorado */
    .form-select {
        border: 2px solid #023A8D;
        border-radius: 8px;
        padding: 12px 16px;
        font-size: 0.95rem;
        font-weight: 500;
        background-color: white;
        transition: all 0.2s ease;
    }
    
    .form-select:focus {
        border-color: #1a4ba8;
        box-shadow: 0 0 0 0.2rem rgba(2, 58, 141, 0.25);
        outline: none;
    }
    
    .form-select:hover {
        border-color: #1a4ba8;
    }
}

/* Layout para dispositivos móveis */
@media (max-width: 767px) {
    .disciplina-edit-fields {
        padding: 16px;
    }
    
    .edit-row {
        margin-bottom: 16px;
    }
    
    .edit-row:last-child {
        margin-bottom: 0;
    }
    
    .form-select {
        border: 2px solid #023A8D;
        border-radius: 8px;
        padding: 12px 16px;
        font-size: 0.95rem;
        font-weight: 500;
    }
    
    .disciplina-horas {
        width: 100%;
        text-align: center;
        font-weight: 600;
        font-size: 1rem;
    }
    
    .input-group-text {
        background: #023A8D;
        color: white;
        border: 1px solid #023A8D;
        font-weight: 600;
        padding: 0.5rem 0.75rem;
        min-width: 45px;
    }
}

/* Botões de ação */
.disciplina-actions {
    padding: 16px 20px;
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
    display: flex;
    justify-content: flex-end;
}

.btn-edit-disciplina {
    background: #023A8D;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 6px;
}

.btn-edit-disciplina:hover {
    background: #1a4ba8;
    transform: translateY(-1px);
}

.btn-edit-disciplina.btn-save-mode {
    background: linear-gradient(135deg, #28a745, #20c997);
    box-shadow: 0 2px 4px rgba(40, 167, 69, 0.2);
}

.btn-edit-disciplina.btn-save-mode:hover {
    background: linear-gradient(135deg, #218838, #1e7e34);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
}

/* Estilos para o campo vazio (quando não há disciplinas) */
.disciplina-item {
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    color: #6c757d;
}

.disciplina-row-layout {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 10px;
}

.disciplina-field-container {
    flex-grow: 1;
}

.disciplina-field-container .form-select {
    border: 1px solid #ced4da;
    border-radius: 6px;
    padding: 10px 12px;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    background-color: white;
}

.disciplina-field-container .form-select:focus {
    border-color: #023A8D;
    box-shadow: 0 0 0 0.2rem rgba(2, 58, 141, 0.25);
    outline: none;
}

.disciplina-horas {
    width: 80px;
    text-align: center;
}

.disciplina-info {
    font-size: 0.85rem;
    color: #6c757d;
    margin-top: 8px;
    font-style: italic;
}

/* ==========================================
   ESTILOS PARA CARDS DE ESTATÍSTICAS
   ========================================== */
.estatisticas-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 5px;
    color: #2c3e50;
}

.stat-label {
    font-size: 0.9rem;
    color: #6c757d;
    font-weight: 500;
}

/* Cores dos cards */
.stat-card-blue {
    border-left-color: #007bff;
}

.stat-card-blue .stat-icon {
    background: linear-gradient(135deg, #007bff, #0056b3);
}

.stat-card-green {
    border-left-color: #28a745;
}

.stat-card-green .stat-icon {
    background: linear-gradient(135deg, #28a745, #20c997);
}

.stat-card-orange {
    border-left-color: #F7931E;
}

.stat-card-orange .stat-icon {
    background: linear-gradient(135deg, #F7931E, #ff8c00);
}

.stat-card-purple {
    border-left-color: #6f42c1;
}

.stat-card-purple .stat-icon {
    background: linear-gradient(135deg, #6f42c1, #8e44ad);
}

/* ==========================================
   ESTILOS PARA STATUS E BADGES
   ========================================== */
/* Status e Badges - Organizados */
.status-badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.3s ease;
    letter-spacing: 0.5px;
}

.status-badge:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

/* Cores dos status */
.status-criando { 
    background: #fff3cd; 
    color: #856404; 
    border: 1px solid #ffeaa7;
}

.status-agendando { 
    background: #cce5ff; 
    color: #004085; 
    border: 1px solid #74c0fc;
}

.status-completa { 
    background: #d4edda; 
    color: #155724; 
    border: 1px solid #c3e6cb;
}

.status-ativa { 
    background: #d1ecf1; 
    color: #0c5460; 
    border: 1px solid #bee5eb;
}

.status-concluida { 
    background: #e2e3e5; 
    color: #383d41; 
    border: 1px solid #ced4da;
}

/* ==========================================
   ESTILOS PARA BOTÕES E AÇÕES
   ========================================== */
/* Botões - Estilos organizados */
.add-disciplina-btn {
    background: linear-gradient(135deg, #023A8D, #1a4ba8);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    cursor: pointer;
    margin-top: 15px;
    transition: all 0.3s ease;
    font-weight: 600;
    box-shadow: 0 2px 4px rgba(2, 58, 141, 0.2);
}

.add-disciplina-btn:hover {
    background: linear-gradient(135deg, #1a4ba8, #023A8D);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(2, 58, 141, 0.3);
}

.disciplina-edit-item {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
    position: relative;
    z-index: 1060;
}

.disciplina-edit-item:hover {
    border-color: #023A8D;
    box-shadow: 0 2px 8px rgba(2, 58, 141, 0.1);
}

.disciplina-edit-item.editing {
    border-color: #28a745 !important;
    box-shadow: 0 4px 12px rgba(40,167,69,0.2);
    background: #f8fff9;
}

/* ==========================================
   ESTILOS PARA EDIÇÃO DE CARGA HORÁRIA
   ========================================== */
.inline-edit-carga {
    cursor: pointer;
    padding: 2px 4px;
    border-radius: 3px;
    transition: all 0.2s ease;
}

.inline-edit-carga:hover {
    background: #e3f2fd;
    color: #1976d2;
}

.inline-edit-carga.editing {
    background: #e8f5e8;
    border: 1px solid #28a745;
    color: #155724;
}

/* ==========================================
   ESTILOS PARA SANFONA DE DISCIPLINAS
   ========================================== */

/* Card de disciplina com sanfona */
.disciplina-accordion {
    transition: all 0.3s ease;
    border-left: 4px solid #023A8D;
}

.disciplina-accordion:hover {
    box-shadow: 0 4px 16px rgba(2, 58, 141, 0.15);
    transform: translateY(-2px);
}

/* Cabeçalho clicável */
.disciplina-header-clickable {
    cursor: pointer;
    transition: all 0.3s ease;
    padding: 20px;
    border-radius: 8px;
}

.disciplina-header-clickable:hover {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
}

.disciplina-header-clickable:active {
    background: linear-gradient(135deg, #e9ecef, #dee2e6);
}

/* Chevron animado */
.disciplina-chevron {
    transition: transform 0.3s ease;
    color: #023A8D;
}

.disciplina-accordion.expanded .disciplina-chevron {
    transform: rotate(180deg);
}

/* Conteúdo da sanfona */
.disciplina-detalhes-content {
    border-top: 1px solid #dee2e6;
    background: #f8f9fa;
    border-radius: 0 0 8px 8px;
    overflow: hidden;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        max-height: 0;
    }
    to {
        opacity: 1;
        max-height: 1000px;
    }
}

/* Loading spinner */
.disciplina-loading {
    padding: 20px;
    text-align: center;
}

.spinner-border {
    width: 2rem;
    height: 2rem;
    border-width: 0.2em;
}

/* Tabela de aulas */
.aulas-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.aulas-table th {
    background: linear-gradient(135deg, #023A8D, #1a4ba8);
    color: white;
    padding: 12px 8px;
    font-weight: 600;
    font-size: 0.9rem;
    text-align: center;
    border: none;
}

.aulas-table td {
    padding: 12px 8px;
    border-bottom: 1px solid #e9ecef;
    font-size: 0.9rem;
    text-align: center;
    vertical-align: middle;
}

.aulas-table tbody tr:hover {
    background: #f8f9fa;
}

.aulas-table tbody tr:last-child td {
    border-bottom: none;
}

/* Status badges na tabela */
.status-badge-table {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-realizada {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.status-agendada {
    background: #cce5ff;
    color: #004085;
    border: 1px solid #74c0fc;
}

.status-cancelada {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Informações do instrutor */
.instrutor-info {
    text-align: left;
    font-size: 0.85rem;
}

.instrutor-nome {
    font-weight: 600;
    color: #023A8D;
    margin-bottom: 2px;
}

.instrutor-contato {
    color: #6c757d;
    font-size: 0.8rem;
}

/* Estatísticas da disciplina */
.disciplina-stats-summary {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.stat-card-mini {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
    border-left: 4px solid #023A8D;
    transition: all 0.3s ease;
}

.stat-card-mini:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.stat-number-mini {
    font-size: 1.5rem;
    font-weight: bold;
    color: #023A8D;
    margin-bottom: 5px;
}

.stat-label-mini {
    font-size: 0.8rem;
    color: #6c757d;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Progress bar */
.progress-container {
    margin-top: 15px;
}

.progress-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
    font-size: 0.9rem;
    font-weight: 600;
}

.progress-bar-custom {
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #023A8D, #1a4ba8);
    border-radius: 4px;
    transition: width 0.3s ease;
}

/* Responsividade para tabela */
@media (max-width: 768px) {
    .aulas-table {
        font-size: 0.8rem;
    }
    
    .aulas-table th,
    .aulas-table td {
        padding: 8px 4px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    
    .stat-card-mini {
        padding: 10px;
    }
    
    .stat-number-mini {
        font-size: 1.2rem;
    }
}

@media (max-width: 576px) {
    .aulas-table {
        font-size: 0.75rem;
    }
    
    .aulas-table th,
    .aulas-table td {
        padding: 6px 2px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .instrutor-info {
        font-size: 0.8rem;
    }
}

/* ==========================================
   RESPONSIVIDADE
   ========================================== */
@media (max-width: 768px) {
    .disciplina-row-layout {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }
    
    .inline-edit[data-field="curso_tipo"] {
        min-width: 100% !important;
        white-space: normal !important;
    }
    
    .disciplina-field-container {
        width: 100%;
    }
}

/* Estilos para o histórico de agendamentos */
.historico-agendamentos {
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-top: 10px;
}

.historico-agendamentos h6 {
    color: #023A8D;
    font-weight: 600;
    border-bottom: 2px solid #023A8D;
    padding-bottom: 8px;
    margin-bottom: 20px;
}

.historico-agendamentos .table {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.historico-agendamentos .table thead th {
    background: #023A8D;
    color: white;
    border: none;
    font-weight: 600;
    text-align: center;
    padding: 12px 8px;
}

.historico-agendamentos .table tbody td {
    text-align: center;
    vertical-align: middle;
    padding: 12px 8px;
    border-color: #e9ecef;
}

.historico-agendamentos .table tbody tr:hover {
    background-color: #f8f9fa;
}

.historico-agendamentos .badge {
    font-size: 0.75rem;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 500;
}

/* Responsividade da tabela */
@media (max-width: 768px) {
    .historico-agendamentos .table-responsive {
        font-size: 0.875rem;
    }
    
    .historico-agendamentos .table thead th,
    .historico-agendamentos .table tbody td {
        padding: 8px 4px;
    }
    
    .historico-agendamentos .badge {
        font-size: 0.7rem;
        padding: 4px 8px;
    }
}

/* ==========================================
   ESTILOS PARA TABELA DE ALUNOS MATRICULADOS
   ========================================== */
.alunos-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.alunos-table th {
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-right: 1px solid #dee2e6;
}

.alunos-table td {
    padding: 12px;
    border-bottom: 1px solid #dee2e6;
    border-right: 1px solid #dee2e6;
    transition: background-color 0.2s;
}

.alunos-table tr:hover {
    background-color: #f8f9fa;
}

.aluno-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.status-matriculado {
    background: #d4edda;
    color: #155724;
}

.status-cursando {
    background: #cce5ff;
    color: #004085;
}

.status-transferido {
    background: #fff3cd;
    color: #856404;
}

.status-concluido {
    background: #d1ecf1;
    color: #0c5460;
}

.action-buttons {
    display: flex;
    gap: 5px;
    justify-content: center;
}

.action-btn {
    border: none;
    padding: 6px 10px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.8rem;
    transition: all 0.2s;
}

.action-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.btn-view {
    background: #17a2b8;
    color: white;
}

.btn-edit {
    background: #ffc107;
    color: #212529;
}

.btn-delete {
    background: #dc3545;
    color: white;
}

</style>

<!-- Cabeçalho -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <h3 style="margin: 0; color: #023A8D;">
            <i class="fas fa-info-circle me-2"></i>Detalhes da Turma
        </h3>
        <p style="margin: 5px 0 0 0; color: #666;">
            Informações completas sobre a turma teórica - Clique nos campos para editar
        </p>
    </div>
    <div style="display: flex; gap: 10px;">
        <button onclick="abrirModalInserirAlunos()" class="btn-primary" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 8px; font-weight: 500; transition: all 0.3s;">
            <i class="fas fa-user-plus"></i>
            Inserir Alunos
        </button>
        <a href="?page=turmas-teoricas" class="btn-secondary">
            ← Voltar para Lista
        </a>
    </div>
</div>

<!-- Sistema de Abas -->
<style>
.tabs-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    margin-bottom: 20px;
    overflow: hidden;
}

.tabs-header {
    display: flex;
    background: #f8f9fa;
    border-bottom: 2px solid #e9ecef;
    overflow-x: auto;
}

.tab-button {
    padding: 14px 24px;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-size: 0.95rem;
    font-weight: 600;
    color: #6c757d;
    transition: all 0.3s ease;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 0 0 auto;
}

.tab-button:hover {
    background: #e9ecef;
    color: #023A8D;
}

.tab-button.active {
    color: #023A8D;
    background: white;
    border-bottom-color: #023A8D;
}

.tab-button i {
    font-size: 1rem;
}

.tab-content {
    display: none;
    padding: 0;
    animation: fadeIn 0.3s ease;
    background: transparent;
}

.tab-content.active {
    display: block;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 768px) {
    .tabs-header {
        flex-wrap: nowrap;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .tab-button {
        padding: 12px 16px;
        font-size: 0.85rem;
    }
}
</style>

<script>
// Definir showTab no escopo global ANTES dos botões para evitar erros
window.showTab = function(tabName) {
    // Esconder todos os conteúdos de abas
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(content => {
        content.classList.remove('active');
    });
    
    // Remover classe active de todos os botões
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.classList.remove('active');
    });
    
    // Se for a aba Calendário, garantir que está na primeira semana
    if (tabName === 'calendario') {
        const url = new URL(window.location);
        const semanaAtual = url.searchParams.get('semana_calendario');
        // Se não há parâmetro ou é inválido, forçar primeira semana (0)
        if (!semanaAtual || parseInt(semanaAtual) < 0) {
            url.searchParams.set('semana_calendario', '0');
            window.history.replaceState({}, '', url);
            // Se realmente não havia parâmetro, recarregar para garantir primeira semana
            if (!semanaAtual) {
                window.location.href = url.toString();
                return;
            }
        }
    }
    
    // Mostrar a aba selecionada
    const selectedTab = document.getElementById('tab-' + tabName);
    if (selectedTab) {
        selectedTab.classList.add('active');
    }
    
    // Ativar o botão correspondente
    const buttons = document.querySelectorAll('.tab-button');
    buttons.forEach(button => {
        if (button.onclick && button.onclick.toString().includes("'" + tabName + "'")) {
            button.classList.add('active');
        } else {
            // Método alternativo: verificar pelo texto do botão
            const buttonText = button.textContent.toLowerCase().trim();
            if (buttonText.includes(tabName.toLowerCase())) {
                button.classList.add('active');
            }
        }
    });
    
    // Salvar aba ativa no localStorage
    localStorage.setItem('turmaDetalhesAbaAtiva', tabName);
    localStorage.setItem('turma-tab-active', tabName);
};
</script>

<div class="tabs-container">
    <div class="tabs-header">
        <button class="tab-button active" onclick="showTab('resumo')">
            <i class="fas fa-home"></i>
            <span>Resumo</span>
        </button>
        <button class="tab-button" onclick="showTab('disciplinas')">
            <i class="fas fa-book"></i>
            <span>Disciplinas</span>
        </button>
        <button class="tab-button" onclick="showTab('alunos')">
            <i class="fas fa-users"></i>
            <span>Alunos</span>
        </button>
        <button class="tab-button" onclick="showTab('calendario')">
            <i class="fas fa-calendar-alt"></i>
            <span>Calendário</span>
        </button>
        <button class="tab-button" onclick="showTab('estatisticas')">
            <i class="fas fa-chart-bar"></i>
            <span>Estatísticas</span>
        </button>
    </div>

    <!-- Aba Resumo -->
    <div id="tab-resumo" class="tab-content active">
<!-- Informações Básicas -->
<div style="padding: 20px; margin-bottom: 20px;">
    <h4 style="color: #023A8D; margin-bottom: 20px;">
        <i class="fas fa-graduation-cap me-2"></i>Informações Básicas
    </h4>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <div>
            <h5 style="color: #023A8D; font-size: 1.5rem; margin-bottom: 10px;">
                        <span class="inline-edit" data-field="nome" data-type="text" data-value="<?= htmlspecialchars($turma['nome']) ?>">
                            <?= htmlspecialchars($turma['nome']) ?>
                            <i class="fas fa-edit edit-icon"></i>
                        </span>
            </h5>
            <p style="color: #666; margin-bottom: 15px;">
                <span class="inline-edit" data-field="curso_tipo" data-type="select" data-value="<?= htmlspecialchars($turma['curso_tipo'] ?? '') ?>">
                    <?= htmlspecialchars($tiposCurso[$turma['curso_tipo']] ?? 'Curso não especificado') ?>
                    <i class="fas fa-edit edit-icon"></i>
                </span>
            </p>
            
            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                <span class="status-badge status-<?= $turma['status'] ?> inline-edit" data-field="status" data-type="select" data-value="<?= $turma['status'] ?>">
                    <?= ucfirst($turma['status']) ?>
                    <i class="fas fa-edit edit-icon"></i>
                </span>
            </div>
        </div>
        
        <div style="display: grid; gap: 10px;">
            <div style="display: flex; align-items: center;">
                <i class="fas fa-building me-2" style="color: #023A8D; width: 20px;"></i>
                <span><strong>Sala:</strong> 
                    <span class="inline-edit" data-field="sala_id" data-type="select" data-value="<?= $turma['sala_id'] ?>">
                        <?= htmlspecialchars(obterNomeSala($turma['sala_id'], $salasCadastradas)) ?>
                        <i class="fas fa-edit edit-icon"></i>
                    </span>
                </span>
            </div>
            
            <div style="display: flex; align-items: center;">
                <i class="fas fa-calendar-alt me-2" style="color: #023A8D; width: 20px;"></i>
                <span><strong>Período:</strong> 
                    <span class="inline-edit" data-field="data_inicio" data-type="date" data-value="<?= $turma['data_inicio'] ?>">
                        <?= date('d/m/Y', strtotime($turma['data_inicio'])) ?>
                        <i class="fas fa-edit edit-icon"></i>
                    </span>
                    - 
                    <span class="inline-edit" data-field="data_fim" data-type="date" data-value="<?= $turma['data_fim'] ?>">
                        <?= date('d/m/Y', strtotime($turma['data_fim'])) ?>
                        <i class="fas fa-edit edit-icon"></i>
                    </span>
                </span>
            </div>
            
            <div style="display: flex; align-items: center;">
                <i class="fas fa-users me-2" style="color: #023A8D; width: 20px;"></i>
                <span><strong>Alunos:</strong> <?= $totalAlunos ?>/<?= $turma['max_alunos'] ?></span>
            </div>
            
            <div style="display: flex; align-items: center;">
                <i class="fas fa-clock me-2" style="color: #023A8D; width: 20px;"></i>
                <span><strong>Modalidade:</strong> 
                    <span class="inline-edit" data-field="modalidade" data-type="select" data-value="<?= $turma['modalidade'] ?>">
                        <?= ucfirst($turma['modalidade']) ?>
                        <i class="fas fa-edit edit-icon"></i>
                    </span>
                </span>
            </div>
        </div>
    </div>
    
    <?php if (!empty($turma['observacoes'])): ?>
    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
        <h6 style="color: #023A8D; margin-bottom: 10px;">Observações:</h6>
        <p style="color: #666; margin: 0;">
            <span class="inline-edit" data-field="observacoes" data-type="textarea" data-value="<?= htmlspecialchars($turma['observacoes']) ?>">
                <?= nl2br(htmlspecialchars($turma['observacoes'])) ?>
                <i class="fas fa-edit edit-icon"></i>
            </span>
        </p>
    </div>
    <?php endif; ?>
</div>

        <!-- Resumo Rápido -->
<?php
        // Calcular totais para resumo
$totalAulasObrigatorias = 0;
$totalAulasAgendadas = 0;
foreach ($estatisticasDisciplinas as $stats) {
    $totalAulasObrigatorias += $stats['obrigatorias'];
    $totalAulasAgendadas += $stats['agendadas'];
}
$percentualGeral = $totalAulasObrigatorias > 0 ? round(($totalAulasAgendadas / $totalAulasObrigatorias) * 100, 1) : 0;
        ?>
        <div style="background: #f8f9fa; padding: 20px; margin-top: 20px;">
            <h5 style="color: #023A8D; margin-bottom: 15px; display: flex; align-items: center;">
                <i class="fas fa-info-circle me-2"></i>Resumo da Turma
            </h5>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div style="text-align: center;">
                    <div style="font-size: 1.8rem; font-weight: bold; color: #023A8D; margin-bottom: 5px;"><?= $totalAulasAgendadas ?>/<?= $totalAulasObrigatorias ?></div>
                    <div style="font-size: 0.85rem; color: #666;">Aulas Agendadas</div>
            </div>
                <div style="text-align: center;">
                    <div style="font-size: 1.8rem; font-weight: bold; color: <?= $percentualGeral >= 100 ? '#28a745' : ($percentualGeral >= 75 ? '#ffc107' : '#dc3545') ?>; margin-bottom: 5px;"><?= $percentualGeral ?>%</div>
                    <div style="font-size: 0.85rem; color: #666;">Progresso Geral</div>
                    </div>
                <div style="text-align: center;">
                    <div style="font-size: 1.8rem; font-weight: bold; color: #6f42c1; margin-bottom: 5px;"><?= $totalAlunos ?></div>
                    <div style="font-size: 0.85rem; color: #666;">Alunos Matriculados</div>
                    </div>
                <div style="text-align: center;">
                    <div style="font-size: 1.8rem; font-weight: bold; color: #F7931E; margin-bottom: 5px;"><?= count($disciplinasSelecionadas) ?></div>
                    <div style="font-size: 0.85rem; color: #666;">Disciplinas</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Aba Disciplinas -->
    <div id="tab-disciplinas" class="tab-content">
<!-- Disciplinas -->
<div style="padding: 20px;">
    <h4 style="color: #023A8D; margin-bottom: 20px;">
        <i class="fas fa-graduation-cap me-2"></i>Disciplinas da Turma
    </h4>
    
    <!-- Disciplinas já cadastradas -->
    <?php if (!empty($disciplinasSelecionadas)): ?>
        <div class="disciplinas-cadastradas-section">
            <h5 class="section-title">
                <i class="fas fa-check-circle text-success me-2"></i>
                Disciplinas Cadastradas
            </h5>
            
            <?php foreach ($disciplinasSelecionadas as $index => $disciplina): ?>
                <?php 
                $disciplinaId = $disciplina['disciplina_id'];
                
                // Debug: Verificar o valor da disciplinaId
                echo "<!-- DEBUG: disciplinaId = " . var_export($disciplinaId, true) . " -->";
                echo "<!-- DEBUG: disciplinaId type = " . gettype($disciplinaId) . " -->";
                echo "<!-- DEBUG: disciplinaId empty = " . var_export(empty($disciplinaId), true) . " -->";
                echo "<!-- DEBUG: disciplinaId == 0 = " . var_export($disciplinaId == 0, true) . " -->";
                echo "<!-- DEBUG: disciplinaId === 0 = " . var_export($disciplinaId === 0, true) . " -->";
                
                // Pular apenas disciplinas com ID realmente inválido (0, null, vazio)
                if (empty($disciplinaId) || $disciplinaId == 0 || $disciplinaId == '0' || $disciplinaId == null) {
                    echo "<!-- Disciplina com ID inválido ignorada: " . var_export($disciplinaId, true) . " -->";
                    continue;
                }
                
                // Debug: Confirmar que a disciplina será processada
                echo "<!-- DEBUG: Processando disciplina ID: " . $disciplinaId . " -->";
                echo "<!-- DEBUG: (int)disciplinaId = " . (int)$disciplinaId . " -->";
                
                $stats = $estatisticasDisciplinas[$disciplinaId] ?? ['agendadas' => 0, 'realizadas' => 0, 'faltantes' => 0, 'obrigatorias' => 0];
                ?>
                <div class="disciplina-cadastrada-card disciplina-accordion" data-disciplina-id="<?= $disciplinaId ?>" data-turma-id="<?= $turmaId ?>">
                    <!-- Cabeçalho da Disciplina (Sempre Visível) -->
                    <div class="disciplina-header-clickable" onclick="console.log('🖱️ [ONCLICK] ===== CLIQUE DETECTADO ====='); console.log('🖱️ [ONCLICK] Disciplina clicada:', '<?= htmlspecialchars($disciplinaId) ?>'); console.log('🖱️ [ONCLICK] Chamando toggleSimples...'); toggleSimples('<?= htmlspecialchars($disciplinaId) ?>'); console.log('🖱️ [ONCLICK] ===== FIM DO CLIQUE =====');">
                        <div class="disciplina-info-display">
                            <div class="disciplina-nome-display">
                                <h6>
                                    <i class="fas fa-graduation-cap me-2" style="color: #023A8D;"></i>
                                    <?= htmlspecialchars($disciplina['nome_disciplina'] ?? $disciplina['nome_original'] ?? 'Disciplina não especificada') ?>
                                    <i class="fas fa-chevron-down disciplina-chevron ms-2" style="transition: transform 0.3s ease;"></i>
                                </h6>
                                
                                <!-- Estatísticas de Aulas -->
                                <div class="aulas-stats-container">
                                    <div class="stat-item">
                                        <span class="stat-label">Agendadas:</span>
                                        <span class="stat-value stat-agendadas"><?= $stats['agendadas'] ?></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">Realizadas:</span>
                                        <span class="stat-value stat-realizadas"><?= $stats['realizadas'] ?></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">Faltantes:</span>
                                        <span class="stat-value stat-faltantes"><?= $stats['faltantes'] ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="disciplina-detalhes-display">
                                <div class="detalhe-item">
                                    <span class="detalhe-label">Carga Horária:</span>
                                    <span class="detalhe-valor"><?= isset($disciplina['carga_horaria_padrao']) ? $disciplina['carga_horaria_padrao'] : '1' ?> horas</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Conteúdo Detalhado (Sanfona) -->
                    <div class="disciplina-detalhes-content" id="detalhes-disciplina-<?= $disciplinaId ?>" style="display: none;">
                        <div class="disciplina-detalhes-data" id="data-disciplina-<?= $disciplinaId ?>">
                            <?php 
                            $agendamentos = $historicoAgendamentos[$disciplinaId] ?? [];
                            ?>
                            
                            <!-- Seção: Histórico de Agendamentos -->
                            <?php if (!empty($agendamentos)): ?>
                                <div class="historico-agendamentos">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                        <h6 class="mb-0">
                                            <i class="fas fa-calendar-alt me-2" style="color: #023A8D;"></i>
                                            Histórico de Agendamentos - <?= htmlspecialchars($disciplina['nome_disciplina']) ?>
                                        </h6>
                                        <button type="button" 
                                                class="btn btn-primary btn-sm" 
                                                onclick="abrirModalAgendarAula('<?= $disciplinaId ?>', '<?= htmlspecialchars($disciplina['nome_disciplina'], ENT_QUOTES) ?>', '<?= htmlspecialchars($turma['data_inicio']) ?>', '<?= htmlspecialchars($turma['data_fim']) ?>')"
                                                style="display: inline-flex; align-items: center; gap: 6px;">
                                            <i class="fas fa-plus"></i>
                                            Agendar Nova Aula
                                        </button>
                                    </div>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead class="table-primary">
                                                <tr>
                                                    <th>Aula</th>
                                                    <th>Data</th>
                                                    <th>Horário</th>
                                                    <th>Instrutor</th>
                                                    <th>Sala</th>
                                                    <th>Duração</th>
                                                    <th>Status</th>
                                                    <th width="100">Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($agendamentos as $agendamento): ?>
                                                    <tr data-agendamento-id="<?= $agendamento['id'] ?>">
                                                        <td>
                                                            <strong><?= htmlspecialchars($agendamento['nome_aula']) ?></strong>
                                                        </td>
                                                        <td>
                                                            <?= date('d/m/Y', strtotime($agendamento['data_aula'])) ?>
                                                        </td>
                                                        <td>
                                                            <?= date('H:i', strtotime($agendamento['hora_inicio'])) ?> - 
                                                            <?= date('H:i', strtotime($agendamento['hora_fim'])) ?>
                                                        </td>
                                                        <td>
                                                            <?= htmlspecialchars($agendamento['instrutor_nome'] ?? 'Não informado') ?>
                                                        </td>
                                                        <td>
                                                            <?= htmlspecialchars($agendamento['sala_nome'] ?? 'Não informada') ?>
                                                        </td>
                                                        <td>
                                                            <?= $agendamento['duracao_minutos'] ?> min
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $statusClass = '';
                                                            $statusText = '';
                                                            switch ($agendamento['status']) {
                                                                case 'agendada':
                                                                    $statusClass = 'badge bg-warning';
                                                                    $statusText = 'Agendada';
                                                                    break;
                                                                case 'realizada':
                                                                    $statusClass = 'badge bg-success';
                                                                    $statusText = 'Realizada';
                                                                    break;
                                                                case 'cancelada':
                                                                    $statusClass = 'badge bg-danger';
                                                                    $statusText = 'Cancelada';
                                                                    break;
                                                                case 'reagendada':
                                                                    $statusClass = 'badge bg-info';
                                                                    $statusText = 'Reagendada';
                                                                    break;
                                                                default:
                                                                    $statusClass = 'badge bg-secondary';
                                                                    $statusText = ucfirst($agendamento['status']);
                                                            }
                                                            ?>
                                                            <span class="<?= $statusClass ?>"><?= $statusText ?></span>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <?php if ($agendamento['status'] === 'agendada'): ?>
                                                                    <button type="button" 
                                                                            class="btn btn-sm btn-outline-primary" 
                                                                            onclick="editarAgendamento(<?= $agendamento['id'] ?>, '<?= htmlspecialchars($agendamento['nome_aula']) ?>', '<?= $agendamento['data_aula'] ?>', '<?= $agendamento['hora_inicio'] ?>', '<?= $agendamento['hora_fim'] ?>', '<?= $agendamento['instrutor_id'] ?>', '<?= $agendamento['sala_id'] ?? '' ?>', '<?= $agendamento['duracao_minutos'] ?>', '<?= htmlspecialchars($agendamento['observacoes'] ?? '') ?>')"
                                                                            title="Editar agendamento"
                                                                            aria-label="Editar agendamento"
                                                                            style="min-width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-width: 2px; font-weight: 500; background: white; border-color: #0d6efd; color: #0d6efd;">
                                                                        <span style="font-size: 14px; font-weight: bold; color: #0d6efd;">✏</span>
                                                                    </button>
                                                                    <button type="button" 
                                                                            class="btn btn-sm btn-outline-danger" 
                                                                            onclick="cancelarAgendamento(<?= $agendamento['id'] ?>, '<?= htmlspecialchars($agendamento['nome_aula']) ?>')"
                                                                            title="Cancelar agendamento">
                                                                        <i class="fas fa-times"></i>
                                                                    </button>
                                                                <?php else: ?>
                                                                    <span class="text-muted small">Não editável</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="historico-agendamentos">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                        <h6 class="mb-0">
                                            <i class="fas fa-calendar-alt me-2" style="color: #023A8D;"></i>
                                            Histórico de Agendamentos - <?= htmlspecialchars($disciplina['nome_disciplina']) ?>
                                        </h6>
                                        <button type="button" 
                                                class="btn btn-primary btn-sm" 
                                                onclick="abrirModalAgendarAula('<?= $disciplinaId ?>', '<?= htmlspecialchars($disciplina['nome_disciplina'], ENT_QUOTES) ?>', '<?= htmlspecialchars($turma['data_inicio']) ?>', '<?= htmlspecialchars($turma['data_fim']) ?>')"
                                                style="display: inline-flex; align-items: center; gap: 6px;">
                                            <i class="fas fa-plus"></i>
                                            Agendar Nova Aula
                                        </button>
                                    </div>
                                    
                                    <div class="text-center py-4">
                                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                        <h6 class="text-muted">Nenhum agendamento encontrado</h6>
                                        <p class="text-muted small">Não há aulas agendadas para esta disciplina ainda.</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- Informação sobre disciplinas automáticas -->
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Disciplinas Automáticas:</strong> As disciplinas desta turma são definidas automaticamente pelo tipo de curso selecionado. Para alterar as disciplinas, altere o tipo de curso da turma nas informações básicas.
    </div>
    
</div>

    </div>

    <!-- Aba Alunos -->
    <div id="tab-alunos" class="tab-content">
        <!-- Alunos Matriculados -->
        <div style="padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h4 style="color: #023A8D; margin: 0;">
            <i class="fas fa-users me-2"></i>Alunos Matriculados
        </h4>
        <div style="display: flex; gap: 10px; align-items: center;">
            <span style="background: #e3f2fd; color: #1976d2; padding: 6px 12px; border-radius: 20px; font-size: 0.9rem; font-weight: 500;">
                <i class="fas fa-user-check me-1"></i>
                <?= count($alunosMatriculados) ?> aluno(s)
            </span>
            <button onclick="abrirModalInserirAlunos()" class="btn-primary" style="padding: 8px 16px; background: #28a745; color: white; border: none; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 6px; font-weight: 500; transition: all 0.3s; font-size: 0.9rem;">
                <i class="fas fa-user-plus"></i>
                Matricular Aluno
            </button>
        </div>
    </div>
    
    <?php if (!empty($alunosMatriculados)): ?>
        <div style="overflow-x: auto;">
            <table class="alunos-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>Categoria</th>
                        <th>CFC</th>
                        <th style="text-align: center;">Status</th>
                        <th>Data Matrícula</th>
                        <th style="text-align: center;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alunosMatriculados as $aluno): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div class="aluno-avatar">
                                        <?= strtoupper(substr($aluno['nome'], 0, 2)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: #2c3e50; margin-bottom: 2px;"><?= htmlspecialchars($aluno['nome']) ?></div>
                                        <?php if (!empty($aluno['email'])): ?>
                                            <div style="font-size: 0.8rem; color: #6c757d;">
                                                <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($aluno['email']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($aluno['telefone'])): ?>
                                            <div style="font-size: 0.8rem; color: #6c757d;">
                                                <i class="fas fa-phone me-1"></i><?= htmlspecialchars($aluno['telefone']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td style="font-family: monospace; font-size: 0.9rem;">
                                <?= htmlspecialchars($aluno['cpf']) ?>
                            </td>
                            <td>
                                <span style="background: #e8f5e8; color: #2e7d32; padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">
                                    <?= htmlspecialchars($aluno['categoria_cnh']) ?>
                                </span>
                            </td>
                            <td>
                                <span style="color: #495057; font-size: 0.9rem;">
                                    <?= htmlspecialchars($aluno['cfc_nome']) ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <?php
                                $statusClass = '';
                                $statusIcon = '';
                                $statusText = '';
                                
                                switch ($aluno['status']) {
                                    case 'matriculado':
                                        $statusClass = 'status-matriculado';
                                        $statusIcon = 'fas fa-user-check';
                                        $statusText = 'Matriculado';
                                        break;
                                    case 'cursando':
                                        $statusClass = 'status-cursando';
                                        $statusIcon = 'fas fa-graduation-cap';
                                        $statusText = 'Cursando';
                                        break;
                                    case 'evadido':
                                        $statusClass = 'status-matriculado';
                                        $statusIcon = 'fas fa-user-check';
                                        $statusText = 'Matriculado';
                                        break;
                                    case 'transferido':
                                        $statusClass = 'status-transferido';
                                        $statusIcon = 'fas fa-exchange-alt';
                                        $statusText = 'Transferido';
                                        break;
                                    case 'concluido':
                                        $statusClass = 'status-concluido';
                                        $statusIcon = 'fas fa-check-circle';
                                        $statusText = 'Concluído';
                                        break;
                                    default:
                                        $statusClass = 'status-badge';
                                        $statusIcon = 'fas fa-question-circle';
                                        $statusText = ucfirst($aluno['status']);
                                }
                                ?>
                                <span class="status-badge <?= $statusClass ?>">
                                    <i class="<?= $statusIcon ?>"></i>
                                    <?= $statusText ?>
                                </span>
                            </td>
                            <td style="font-size: 0.9rem; color: #6c757d;">
                                <i class="fas fa-calendar-alt me-1"></i>
                                <?= date('d/m/Y', strtotime($aluno['data_matricula'])) ?>
                                <div style="font-size: 0.8rem; color: #adb5bd;">
                                    <?= date('H:i', strtotime($aluno['data_matricula'])) ?>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <div class="action-buttons">
                                    <button onclick="removerMatricula(<?= $aluno['id'] ?>, '<?= htmlspecialchars($aluno['nome']) ?>')" class="action-btn btn-delete" title="Remover da Turma">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 40px 20px; color: #6c757d;">
            <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
            <h5 style="margin-bottom: 10px; color: #495057;">Nenhum aluno matriculado</h5>
            <p style="margin-bottom: 20px;">Esta turma ainda não possui alunos matriculados.</p>
            <button onclick="abrirModalInserirAlunos()" class="btn-primary" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-weight: 500; transition: all 0.3s;">
                <i class="fas fa-user-plus"></i>
                Matricular Primeiro Aluno
            </button>
        </div>
    <?php endif; ?>
</div>
    </div>

    <!-- Aba Calendário -->
    <div id="tab-calendario" class="tab-content">
        <?php
        // Calcular período da turma PRIMEIRO (antes de buscar aulas)
        $dataInicio = new DateTime($turma['data_inicio']);
        $dataFim = new DateTime($turma['data_fim']);
        
        // Obter primeiro domingo do período
        $primeiroDomingo = clone $dataInicio;
        $diaSemanaAtual = (int)$primeiroDomingo->format('w');
        if ($diaSemanaAtual > 0) {
            $primeiroDomingo->modify('-' . $diaSemanaAtual . ' days');
        }
        if ($primeiroDomingo < $dataInicio) {
            $primeiroDomingo = clone $dataInicio;
            $diaSemanaAtual = (int)$primeiroDomingo->format('w');
            if ($diaSemanaAtual != 0) {
                $diasParaProximoDomingo = 7 - $diaSemanaAtual;
                $primeiroDomingo->modify('+' . $diasParaProximoDomingo . ' days');
            }
            if ($primeiroDomingo > $dataFim) {
                $primeiroDomingo = clone $dataInicio;
            }
        }
        
        // Calcular semanas disponíveis
        $todasSemanasTmp = [];
        $semanaAtual = clone $primeiroDomingo;
        while ($semanaAtual <= $dataFim) {
            $semana = [];
            for ($dia = 0; $dia < 7; $dia++) {
                $diaSemana = clone $semanaAtual;
                $diaSemana->modify("+$dia days");
                if ($diaSemana <= $dataFim && $diaSemana >= $dataInicio) {
                    $semana[] = $diaSemana->format('Y-m-d');
                } else {
                    $semana[] = null;
                }
            }
            $todasSemanasTmp[] = [
                'dias' => $semana,
                'inicio' => $semana[0] ? new DateTime($semana[0]) : null,
                'indice' => count($todasSemanasTmp)
            ];
            $semanaAtual->modify('+7 days');
        }
        
        // Semana selecionada
        $semanaSelecionada = isset($_GET['semana_calendario']) && $_GET['semana_calendario'] !== '' ? (int)$_GET['semana_calendario'] : 0;
        if ($semanaSelecionada < 0 || $semanaSelecionada >= count($todasSemanasTmp)) {
            $semanaSelecionada = 0;
        }
        
        $semanaDisplay = $todasSemanasTmp[$semanaSelecionada] ?? $todasSemanasTmp[0];
        
        // Calcular datas da semana selecionada para filtrar
        $datasSemanaSelecionada = array_filter($semanaDisplay['dias'], function($d) { return $d !== null; });
        $dataInicioSemana = min($datasSemanaSelecionada);
        $dataFimSemana = max($datasSemanaSelecionada);
        
        // Buscar apenas aulas da semana selecionada
        try {
            $todasAulasCalendario = $db->fetchAll(
                "SELECT 
                    taa.*,
                    taa.disciplina as disciplina_id,
                    COALESCE(u.nome, i.nome, 'Não informado') as instrutor_nome,
                    COALESCE(s.nome, 'Não informada') as sala_nome
                 FROM turma_aulas_agendadas taa
                 LEFT JOIN instrutores i ON taa.instrutor_id = i.id
                 LEFT JOIN usuarios u ON i.usuario_id = u.id
                 LEFT JOIN salas s ON taa.sala_id = s.id
                 WHERE taa.turma_id = ? 
                 AND (taa.status IS NULL OR taa.status != 'cancelada')
                 AND taa.data_aula >= ?
                 AND taa.data_aula <= ?
                 ORDER BY taa.data_aula ASC, taa.hora_inicio ASC",
                [$turmaId, $dataInicioSemana, $dataFimSemana]
            );
            
            // Debug: verificar se encontrou aulas
            if (empty($todasAulasCalendario)) {
                // Tentar buscar sem filtro de status para debug
                $todasAulasDebug = $db->fetchAll(
                    "SELECT COUNT(*) as total, 
                            SUM(CASE WHEN status = 'cancelada' THEN 1 ELSE 0 END) as canceladas,
                            SUM(CASE WHEN status IS NULL OR status != 'cancelada' THEN 1 ELSE 0 END) as ativas
                     FROM turma_aulas_agendadas 
                     WHERE turma_id = ?",
                    [$turmaId]
                );
            }
        } catch (Exception $e) {
            error_log("Erro ao buscar aulas para calendário: " . $e->getMessage());
            $todasAulasCalendario = [];
        }
        
        // Adicionar nome da disciplina baseado nas disciplinas selecionadas
        $disciplinasMap = [];
        foreach ($disciplinasSelecionadas as $disc) {
            $disciplinasMap[$disc['disciplina_id']] = $disc['nome_disciplina'] ?? $disc['nome_original'] ?? 'Disciplina';
        }
        
        foreach ($todasAulasCalendario as &$aula) {
            $aula['nome_disciplina'] = $disciplinasMap[$aula['disciplina_id']] ?? 'Disciplina';
        }
        
        // Organizar aulas por data e disciplina
        $aulasPorData = [];
        $ultimaAulaPorDisciplina = [];
        
        foreach ($todasAulasCalendario as $aula) {
            $data = $aula['data_aula'];
            
            // Verificar se data está vazia ou nula
            if (empty($data)) {
                error_log("Aula sem data encontrada: ID " . ($aula['id'] ?? 'N/A'));
                continue;
            }
            
            // Normalizar formato de data (pode ser Y-m-d ou Y/m/d)
            if (strpos($data, '/') !== false) {
                $dataParts = explode('/', $data);
                if (count($dataParts) == 3) {
                    // Assumir formato d/m/Y ou Y/m/d baseado no tamanho do primeiro segmento
                    if (strlen($dataParts[2]) == 4) {
                        // Formato d/m/Y ou Y/m/d
                        if (strlen($dataParts[0]) == 4) {
                            // Y/m/d
                            $data = $dataParts[0] . '-' . str_pad($dataParts[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($dataParts[2], 2, '0', STR_PAD_LEFT);
                        } else {
                            // d/m/Y
                            $data = $dataParts[2] . '-' . str_pad($dataParts[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($dataParts[0], 2, '0', STR_PAD_LEFT);
                        }
                    } else {
                        // Formato desconhecido, tentar converter como d/m/Y
                        $data = $dataParts[2] . '-' . str_pad($dataParts[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($dataParts[0], 2, '0', STR_PAD_LEFT);
                    }
                }
            } else {
                // Já está em formato Y-m-d, garantir que está correto
                $dataParts = explode('-', $data);
                if (count($dataParts) == 3) {
                    $data = $dataParts[0] . '-' . str_pad($dataParts[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($dataParts[2], 2, '0', STR_PAD_LEFT);
                }
            }
            
            $disciplinaId = $aula['disciplina_id'] ?? null;
            
            if ($disciplinaId === null) {
                error_log("Aula sem disciplina_id: ID " . ($aula['id'] ?? 'N/A'));
                continue;
            }
            
            if (!isset($aulasPorData[$data])) {
                $aulasPorData[$data] = [];
            }
            if (!isset($aulasPorData[$data][$disciplinaId])) {
                $aulasPorData[$data][$disciplinaId] = [];
            }
            $aulasPorData[$data][$disciplinaId][] = $aula;
            
            // Identificar última aula por disciplina (para destacar)
            if (!isset($ultimaAulaPorDisciplina[$disciplinaId]) || 
                strtotime($aula['data_aula'] . ' ' . $aula['hora_fim']) > strtotime($ultimaAulaPorDisciplina[$disciplinaId]['data_aula'] . ' ' . $ultimaAulaPorDisciplina[$disciplinaId]['hora_fim'])) {
                $ultimaAulaPorDisciplina[$disciplinaId] = $aula;
            }
        }
        
        // Definir cores por disciplina
        $coresDisciplinas = [
            'legislacao_transito' => '#FFC107',
            'direcao_defensiva' => '#28a745',
            'primeiros_socorros' => '#dc3545',
            'meio_ambiente_cidadania' => '#17a2b8',
            'mecanica_basica' => '#6f42c1'
        ];
        
        // Calcular horários dinamicamente baseado nas aulas agendadas
        // Encontrar a menor hora e maior hora das aulas para criar timeline
        $horaMinima = null;
        $horaMaxima = null;
        
        foreach ($todasAulasCalendario as $aula) {
            // Normalizar formato de hora (pode ser HH:MM ou HH:MM:SS)
            $horaInicioStr = $aula['hora_inicio'];
            $horaFimStr = $aula['hora_fim'];
            
            if (strlen($horaInicioStr) == 8) {
                $horaInicioStr = substr($horaInicioStr, 0, 5);
            }
            if (strlen($horaFimStr) == 8) {
                $horaFimStr = substr($horaFimStr, 0, 5);
            }
            
            // Converter para minutos
            list($horaInicio, $minInicio) = explode(':', $horaInicioStr);
            list($horaFim, $minFim) = explode(':', $horaFimStr);
            
            $horaMinMinutos = (int)$horaInicio * 60 + (int)$minInicio;
            $horaMaxMinutos = (int)$horaFim * 60 + (int)$minFim;
            
            if ($horaMinima === null || $horaMinMinutos < $horaMinima) {
                $horaMinima = $horaMinMinutos;
            }
            if ($horaMaxima === null || $horaMaxMinutos > $horaMaxima) {
                $horaMaxima = $horaMaxMinutos;
            }
        }
        
        // Sempre mostrar timeline completa (Manhã, Tarde, Noite) para facilitar agendamento
        // Independente de ter aulas, mostrar todo o período útil
        if ($horaMinima === null || count($todasAulasCalendario) == 0) {
            // Sem aulas: timeline completa mas compacta
            $horaMinima = 6 * 60; // Sempre começar às 06:00 (Manhã)
            $horaMaxima = 23 * 60; // Sempre terminar às 23:00 (Noite)
        } else {
            // Com aulas: sempre mostrar timeline completa, mas ajustar range se necessário
            // Sempre incluir todos os períodos (Manhã, Tarde, Noite)
            $horaMinima = 6 * 60; // Sempre começar às 06:00 para mostrar Manhã
            $horaMaxima = 23 * 60; // Sempre terminar às 23:00 para mostrar Noite completa
            
            // Mas garantir que se há aulas fora deste range, expandir
            foreach ($todasAulasCalendario as $aula) {
                $horaInicioStr = $aula['hora_inicio'];
                $horaFimStr = $aula['hora_fim'];
                
                if (strlen($horaInicioStr) == 8) {
                    $horaInicioStr = substr($horaInicioStr, 0, 5);
                }
                if (strlen($horaFimStr) == 8) {
                    $horaFimStr = substr($horaFimStr, 0, 5);
                }
                
                list($horaInicio, $minInicio) = explode(':', $horaInicioStr);
                list($horaFim, $minFim) = explode(':', $horaFimStr);
                
                $horaMinMinutos = (int)$horaInicio * 60 + (int)$minInicio;
                $horaMaxMinutos = (int)$horaFim * 60 + (int)$minFim;
                
                // Não reduzir $horaMinima abaixo de 6:00 nem aumentar $horaMaxima acima de 23:00
                // Mas garantir que todas as aulas estejam visíveis
                if ($horaMinMinutos < 6 * 60) {
                    $horaMinima = min(6 * 60, $horaMinMinutos - 30);
                }
                if ($horaMaxMinutos > 23 * 60) {
                    $horaMaxima = max(23 * 60, $horaMaxMinutos); // Sem margem extra - terminar exatamente quando necessário
                }
            }
        }
        
        // Definir períodos (Manhã, Tarde, Noite)
        $periodos = [
            'Manhã' => ['inicio' => 6 * 60, 'fim' => 12 * 60, 'colapsado' => false],
            'Tarde' => ['inicio' => 12 * 60, 'fim' => 18 * 60, 'colapsado' => false],
            'Noite' => ['inicio' => 18 * 60, 'fim' => 23 * 60, 'colapsado' => false]
        ];
        
        // SIMPLIFICADO: NUNCA colapsar períodos automaticamente
        // Isso estava causando problemas de renderização
        // Períodos sempre expandidos por padrão - usuário pode colapsar manualmente se quiser
        foreach ($periodos as $nomePeriodo => &$periodo) {
            $periodo['colapsado'] = false;
        }
        
        // Gerar timeline com intervalos de 30 minutos (mais granular que 50min fixo)
        $horariosTimeline = [];
        $horaAtual = $horaMinima;
        while ($horaAtual <= $horaMaxima) {
            $horas = floor($horaAtual / 60);
            $minutos = $horaAtual % 60;
            $horariosTimeline[] = sprintf('%02d:%02d', $horas, $minutos);
            $horaAtual += 30; // Intervalos de 30 minutos
        }
        
        // Manter array de horários para compatibilidade (será usado apenas como referência)
        $horarios = $horariosTimeline;
        
        // Usar as semanas já calculadas acima
        $todasSemanas = $todasSemanasTmp;
        
        // JavaScript para armazenar semanas disponíveis
        $semanasJson = json_encode(array_map(function($s) {
            return [
                'dias' => $s['dias'],
                'inicio' => $s['inicio'] ? $s['inicio']->format('Y-m-d') : null,
                'indice' => $s['indice']
            ];
        }, $todasSemanas));
        ?>
        
        <div style="padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                <h4 style="color: #023A8D; margin: 0; display: flex; align-items: center;">
                    <i class="fas fa-calendar-alt me-2"></i>Calendário de Agendamentos
                </h4>
                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <!-- Navegação de Semana -->
                    <div style="display: flex; align-items: center; gap: 8px; background: #f8f9fa; padding: 6px 12px; border-radius: 6px;">
                        <button id="btn-semana-anterior" onclick="mudarSemana(-1)" style="background: white; border: 1px solid #ddd; border-radius: 4px; padding: 6px 10px; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#e9ecef'" onmouseout="this.style.background='white'">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <span id="info-semana-atual" style="font-size: 0.9rem; font-weight: 600; color: #023A8D; min-width: 220px; text-align: center;">
                            <?php 
                            $semanaDisplay = $todasSemanas[$semanaSelecionada] ?? $todasSemanas[0];
                            $totalSemanas = count($todasSemanas);
                            if ($semanaDisplay['inicio']):
                                // Semana vai de domingo (dias[0]) a sábado (dias[6])
                                $inicioSemana = $semanaDisplay['inicio'];
                                $fimSemana = clone $inicioSemana;
                                $fimSemana->modify('+6 days'); // Domingo + 6 dias = Sábado
                                echo 'Semana ' . ($semanaSelecionada + 1) . ' de ' . $totalSemanas . '<br>';
                                echo '<small style="font-size: 0.8rem; opacity: 0.9;">' . $inicioSemana->format('d/m') . ' - ' . $fimSemana->format('d/m/Y') . '</small>';
                            else:
                                echo 'Carregando...';
                            endif;
                            ?>
                        </span>
                        <button id="btn-semana-proxima" onclick="mudarSemana(1)" style="background: white; border: 1px solid #ddd; border-radius: 4px; padding: 6px 10px; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#e9ecef'" onmouseout="this.style.background='white'">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <select id="filtro-disciplina-calendario" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 0.9rem;" onchange="filtrarCalendario()">
                        <option value="">Todas as disciplinas</option>
                        <?php foreach ($disciplinasSelecionadas as $disc): ?>
                            <option value="<?= $disc['disciplina_id'] ?>"><?= htmlspecialchars($disc['nome_disciplina'] ?? $disc['nome_original'] ?? 'Disciplina') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <input type="hidden" id="semana-atual-indice" value="<?= $semanaSelecionada ?>">
            <input type="hidden" id="semanas-disponiveis" value='<?= htmlspecialchars($semanasJson) ?>'>
            
            <!-- Dados para JavaScript (todas as aulas para atualização dinâmica) -->
            <script type="application/json" id="dados-calendario">
            <?= json_encode([
                'aulasPorData' => $aulasPorData,
                'ultimaAulaPorDisciplina' => array_map(function($a) {
                    return ['id' => $a['id'], 'disciplina_id' => $a['disciplina_id']];
                }, $ultimaAulaPorDisciplina),
                'coresDisciplinas' => $coresDisciplinas,
                'disciplinasMap' => $disciplinasMap,
                'horarios' => $horarios,
                'horaMinima' => $horaMinima,
                'horaMaxima' => $horaMaxima
            ], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
            </script>
            
            <!-- Debug Info (Temporário) -->
            <?php 
            $totalAulas = count($todasAulasCalendario ?? []);
            
            // Buscar total de aulas no período completo (para estatísticas)
            try {
                $totalAulasPeriodo = $db->fetch(
                    "SELECT COUNT(*) as total FROM turma_aulas_agendadas 
                     WHERE turma_id = ? 
                     AND (status IS NULL OR status != 'cancelada')
                     AND data_aula >= ? AND data_aula <= ?",
                    [$turmaId, $turma['data_inicio'], $turma['data_fim']]
                );
                $totalGeral = $totalAulasPeriodo['total'] ?? 0;
            } catch (Exception $e) {
                $totalGeral = $totalAulas;
            }
            
            // Coletar informações de debug sobre as datas
            $debugDatas = [];
            $debugDatasDisponiveis = array_keys($aulasPorData ?? []);
            foreach ($semanaDisplay['dias'] as $idx => $data) {
                if ($data) {
                    $aulasDia = 0;
                    // Buscar aulas deste dia (tentar formatos diferentes)
                    $formatosData = [$data];
                    if (strpos($data, '-') !== false) {
                        $parts = explode('-', $data);
                        $formatosData[] = $parts[2] . '/' . $parts[1] . '/' . $parts[0]; // d/m/Y
                        $formatosData[] = $parts[0] . '/' . $parts[1] . '/' . $parts[2]; // Y/m/d
                    }
                    
                    foreach ($formatosData as $dataBusca) {
                        if (isset($aulasPorData[$dataBusca])) {
                            foreach ($aulasPorData[$dataBusca] as $discId => $aulas) {
                                $aulasDia += count($aulas);
                            }
                        }
                    }
                    
                    // Se ainda não encontrou, buscar em todas as datas e comparar
                    if ($aulasDia == 0 && !empty($aulasPorData)) {
                        foreach ($aulasPorData as $dataKey => $disciplinas) {
                            // Normalizar ambas as datas para comparação
                            $dataNormalizada = $data;
                            $dataKeyNormalizada = $dataKey;
                            
                            // Normalizar data da semana
                            if (strpos($dataNormalizada, '-') !== false) {
                                $parts = explode('-', $dataNormalizada);
                                $dataNormalizada = $parts[0] . '-' . str_pad($parts[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($parts[2], 2, '0', STR_PAD_LEFT);
                            }
                            
                            // Normalizar data key
                            if (strpos($dataKey, '/') !== false) {
                                $parts = explode('/', $dataKey);
                                if (count($parts) == 3) {
                                    if (strlen($parts[2]) == 4) {
                                        if (strlen($parts[0]) == 4) {
                                            $dataKeyNormalizada = $parts[0] . '-' . str_pad($parts[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($parts[2], 2, '0', STR_PAD_LEFT);
                                        } else {
                                            $dataKeyNormalizada = $parts[2] . '-' . str_pad($parts[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                                        }
                                    }
                                }
                            } else {
                                $parts = explode('-', $dataKey);
                                if (count($parts) == 3) {
                                    $dataKeyNormalizada = $parts[0] . '-' . str_pad($parts[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($parts[2], 2, '0', STR_PAD_LEFT);
                                }
                            }
                            
                            if ($dataNormalizada == $dataKeyNormalizada) {
                                foreach ($disciplinas as $discId => $aulas) {
                                    $aulasDia += count($aulas);
                                }
                            }
                        }
                    }
                    
                    $debugDatas[] = "$data: $aulasDia aulas";
                }
            }
            
            if ($totalAulas == 0): 
                // Verificar se há aulas no banco para esta turma
                try {
                    $checkAulas = $db->fetch(
                        "SELECT COUNT(*) as total FROM turma_aulas_agendadas WHERE turma_id = ?",
                        [$turmaId]
                    );
                    $totalNoBanco = $checkAulas['total'] ?? 0;
                } catch (Exception $e) {
                    $totalNoBanco = '?';
                }
            ?>
            <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                <strong style="color: #856404;">ℹ️ Aviso:</strong>
                <span style="color: #856404;">Nenhuma aula agendada encontrada para esta turma.</span>
                <br><small style="color: #856404;">
                    Total no banco: <?= $totalNoBanco ?> | 
                    Exibidas: <?= $totalAulas ?> | 
                    Datas organizadas: <?= count($aulasPorData ?? []) ?>
                    <?php if (isset($todasAulasDebug) && !empty($todasAulasDebug)): ?>
                        <br>Debug DB: Total=<?= $todasAulasDebug[0]['total'] ?? 0 ?>, Canceladas=<?= $todasAulasDebug[0]['canceladas'] ?? 0 ?>, Ativas=<?= $todasAulasDebug[0]['ativas'] ?? 0 ?>
                    <?php endif; ?>
                </small>
            </div>
            <?php else: ?>
            <div style="background: #d1ecf1; border: 1px solid #17a2b8; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <strong style="color: #0c5460;">✅ <?= $totalAulas ?> aulas nesta semana</strong>
                        <?php if ($totalGeral > 0): ?>
                            <small style="color: #6c757d;"> (Total no período: <?= $totalGeral ?>)</small>
                        <?php endif; ?>
                        <br><small style="color: #0c5460;">
                            Semana: <?= $semanaDisplay['inicio'] ? $semanaDisplay['inicio']->format('d/m/Y') : 'N/A' ?> | 
                            <?php if ($totalAulas > 0): ?>
                                Horários: <?= sprintf('%02d:%02d', floor($horaMinima/60), $horaMinima%60) ?> - <?= sprintf('%02d:%02d', floor($horaMaxima/60), $horaMaxima%60) ?> |
                            <?php endif; ?>
                            Período da semana: <?= $dataInicioSemana ?> até <?= $dataFimSemana ?>
                        </small>
                    </div>
                    <details style="flex: 1; min-width: 300px;">
                        <summary style="cursor: pointer; color: #0c5460; font-weight: 600;">🔍 Ver detalhes de debug</summary>
                        <div style="background: white; padding: 10px; margin-top: 10px; border-radius: 4px; font-size: 0.85rem; max-height: 200px; overflow-y: auto;">
                            <strong>Aulas por dia da semana:</strong><br>
                            <?php if (empty($debugDatas)): ?>
                                Nenhuma aula encontrada para os dias desta semana.
                            <?php else: ?>
                                <?php foreach ($debugDatas as $info): ?>
                                    • <?= $info ?><br>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <br>
                            <strong>Datas disponíveis em aulasPorData (<?= count($debugDatasDisponiveis) ?>):</strong><br>
                            <?php if (empty($debugDatasDisponiveis)): ?>
                                Nenhuma data organizada.
                            <?php else: ?>
                                <?php foreach (array_slice($debugDatasDisponiveis, 0, 20) as $dataKey): ?>
                                    • <?= $dataKey ?><br>
                                <?php endforeach; ?>
                                <?php if (count($debugDatasDisponiveis) > 20): ?>
                                    ... e mais <?= count($debugDatasDisponiveis) - 20 ?> datas
                                <?php endif; ?>
                            <?php endif; ?>
                            <br>
                            <strong>Primeiras 3 aulas desta semana:</strong><br>
                            <?php if (empty($todasAulasCalendario)): ?>
                                Nenhuma aula nesta semana.
                            <?php else: ?>
                                <?php foreach (array_slice($todasAulasCalendario ?? [], 0, 3) as $aula): ?>
                                    • ID <?= $aula['id'] ?? 'N/A' ?>: <?= $aula['data_aula'] ?? 'N/A' ?> às <?= substr($aula['hora_inicio'] ?? 'N/A', 0, 5) ?> - <?= $aula['nome_aula'] ?? 'N/A' ?><br>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <br>
                            <strong>Filtro aplicado:</strong><br>
                            • Semana selecionada: <?= $semanaSelecionada + 1 ?> de <?= count($todasSemanas) ?><br>
                            • Datas da semana: <?= $dataInicioSemana ?> até <?= $dataFimSemana ?><br>
                            • Aulas encontradas para este período: <?= $totalAulas ?>
                        </div>
                    </details>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Legenda -->
            <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 20px; display: flex; gap: 20px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="width: 20px; height: 20px; background: #e9ecef; border: 2px dashed #adb5bd; border-radius: 4px;"></div>
                    <span style="font-size: 0.85rem;">Disponível</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="width: 20px; height: 20px; background: #023A8D; border-radius: 4px;"></div>
                    <span style="font-size: 0.85rem;">Aula agendada</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="width: 20px; height: 20px; background: #28a745; border: 2px solid #fff; box-shadow: 0 0 0 2px #28a745; border-radius: 4px;"></div>
                    <span style="font-size: 0.85rem;">Última aula (por disciplina)</span>
                </div>
            </div>
            
            <!-- Calendário Estilo Timeline (Google Calendar) -->
            <style>
            .timeline-calendar {
                position: relative;
                background: white;
                border-radius: 8px;
                overflow: hidden;
            }
            
            .timeline-header {
                display: grid;
                grid-template-columns: 80px repeat(7, 1fr);
                background: #023A8D;
                color: white;
                border-bottom: 2px solid #0056b3;
            }
            
            .timeline-time-column {
                background: #f8f9fa;
                border-right: 2px solid #dee2e6;
                position: sticky;
                left: 0;
                z-index: 10;
            }
            
            .timeline-day-header {
                padding: 12px;
                text-align: center;
                font-weight: 600;
                font-size: 0.9rem;
                border-right: 1px solid rgba(255, 255, 255, 0.2);
            }
            
            .timeline-day-header:last-child {
                border-right: none;
            }
            
            .timeline-day-header .dia-nome {
                display: block;
                font-size: 0.95rem;
                margin-bottom: 4px;
            }
            
            .timeline-day-header .dia-data {
                display: block;
                font-size: 0.75rem;
                opacity: 0.9;
            }
            
            .timeline-body {
                display: grid;
                grid-template-columns: 80px repeat(7, 1fr);
                position: relative;
                align-items: start; /* Garantir alinhamento no topo */
                background: white;
                border: 1px solid #e9ecef;
            }
            
            .timeline-hours {
                position: sticky;
                left: 0;
                z-index: 5;
                background: #f8f9fa;
                border-right: 2px solid #dee2e6;
                display: flex;
                flex-direction: column;
            }
            
            /* Container para linhas horizontais na coluna de horários */
            .timeline-hours::after {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                width: 100%;
                /* Linhas horizontais: finas a cada 30 min, espessas nas horas inteiras */
                background-image: 
                    /* Linhas finas a cada 30 minutos (meio-horas) */
                    repeating-linear-gradient(
                        to bottom,
                        transparent,
                        transparent 59px,
                        #e9ecef 59px,
                        #e9ecef 60px
                    ),
                    /* Linhas espessas nas horas inteiras (sobrepõe as finas) */
                    repeating-linear-gradient(
                        to bottom,
                        transparent,
                        transparent 119px,
                        #dee2e6 119px,
                        #dee2e6 120px
                    );
                pointer-events: none;
                z-index: 1;
            }
            
            
            .timeline-day-column {
                position: relative;
                /* Remover flex-direction para permitir posicionamento absoluto dos slots */
            }
            
            .timeline-hour-marker {
                position: relative;
                /* Removido border-bottom - linhas agora vêm do background da coluna */
                height: 60px; /* Altura fixa de 30 minutos = 60px (2px por minuto) */
                display: flex;
                align-items: flex-start;
                box-sizing: border-box;
                z-index: 2; /* Acima das linhas do background */
            }
            
            .timeline-hour-marker.hora-inteira {
                /* Linha mais espessa vem do ::before da coluna */
            }
            
            .timeline-hour-label {
                position: absolute;
                top: -8px;
                left: 8px;
                font-size: 0.75rem;
                color: #6c757d;
                font-weight: 600;
                background: #f8f9fa;
                padding: 0 4px;
            }
            
            .timeline-period-label {
                position: absolute;
                top: 4px;
                right: 8px;
                font-size: 0.65rem;
                color: #adb5bd;
                text-transform: uppercase;
                font-weight: 500;
            }
            
            .timeline-day-column {
                position: relative;
                border-right: 1px solid #e9ecef;
                background: white;
                /* A altura será definida inline via style */
                overflow: visible;
            }
            
            /* Grade padrão: linhas horizontais nas colunas de dias */
            .timeline-day-column::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                width: 100%;
                /* Linhas horizontais: finas a cada 30 min, espessas nas horas inteiras */
                background-image: 
                    /* Linhas finas a cada 30 minutos (meio-horas) - 1px */
                    repeating-linear-gradient(
                        to bottom,
                        transparent,
                        transparent 59px,
                        #e9ecef 59px,
                        #e9ecef 60px
                    ),
                    /* Linhas espessas nas horas inteiras - 2px */
                    repeating-linear-gradient(
                        to bottom,
                        transparent,
                        transparent 119px,
                        #dee2e6 119px,
                        #dee2e6 120px
                    );
                pointer-events: none;
                z-index: 0;
            }
            
            .timeline-day-column:last-child {
                border-right: none;
            }
            
            .timeline-slot {
                position: absolute;
                left: 0;
                right: 0;
                cursor: pointer;
                transition: all 0.2s;
                border-radius: 4px;
                padding: 4px 8px;
                font-size: 0.75rem;
                z-index: 10; /* Acima das linhas da grade */
                box-sizing: border-box;
                margin: 0 1px; /* Pequena margem para não sobrepor as bordas verticais */
            }
            
            .timeline-slot.vazio {
                background: rgba(248, 249, 250, 0.5);
                border: 1px dashed rgba(173, 181, 189, 0.4);
                transition: all 0.2s ease;
            }
            
            .timeline-slot.vazio:hover {
                background: rgba(2, 58, 141, 0.08);
                border-color: rgba(2, 58, 141, 0.5);
                border-style: solid;
                border-width: 1px;
                box-shadow: inset 0 0 0 1px rgba(2, 58, 141, 0.1);
            }
            
            .timeline-slot.vazio::before {
                content: '+';
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                color: #adb5bd;
                font-size: 1.2rem;
                font-weight: bold;
                opacity: 0;
                transition: opacity 0.2s ease;
            }
            
            .timeline-slot.vazio:hover::before {
                opacity: 0.5;
            }
            
            .timeline-slot.aula {
                color: white;
                font-weight: 600;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                overflow: visible;
                text-overflow: ellipsis;
                white-space: normal;
                word-wrap: break-word;
                display: block;
                min-height: 40px;
            }
            
            .timeline-slot.aula.ultima {
                border: 2px solid white;
                box-shadow: 0 0 0 2px currentColor, 0 2px 8px rgba(0,0,0,0.15);
            }
            
            .timeline-slot.aula:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            }
            
            .timeline-periodo-colapsado {
                height: 40px !important;
                overflow: hidden;
                position: relative;
            }
            
            .timeline-periodo-colapsado::after {
                content: '...';
                position: absolute;
                bottom: 5px;
                left: 50%;
                transform: translateX(-50%);
                color: #6c757d;
                font-size: 0.8rem;
                background: white;
                padding: 0 10px;
            }
            
            .timeline-toggle-periodo {
                position: sticky;
                left: 0;
                z-index: 15;
                background: #f8f9fa;
                border-bottom: 2px solid #dee2e6;
                padding: 8px 12px;
                cursor: pointer;
                transition: background 0.2s;
                font-size: 0.85rem;
                font-weight: 600;
                color: #023A8D;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            
            .timeline-toggle-periodo:hover {
                background: #e9ecef;
            }
            
            .timeline-toggle-periodo .periodo-info {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .timeline-toggle-periodo .periodo-badge {
                background: #023A8D;
                color: white;
                padding: 2px 8px;
                border-radius: 12px;
                font-size: 0.7rem;
            }
            
            /* Classe para colapsar período sem quebrar layout */
            .periodo-colapsado {
                max-height: 0 !important;
                overflow: hidden !important;
                opacity: 0;
                transition: max-height 0.3s ease, opacity 0.3s ease;
                pointer-events: none;
            }
            
            .periodo-expandido {
                max-height: none !important;
                opacity: 1;
                transition: max-height 0.3s ease, opacity 0.3s ease;
                pointer-events: auto;
            }
            </style>
            
            <div style="overflow-x: auto;" id="calendario-container">
                <div class="timeline-calendar" id="timeline-calendario">
                    <?php
                    $semanaDisplay = $todasSemanas[$semanaSelecionada] ?? $todasSemanas[0];
                    $diasSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
                    
                    // Calcular altura total da timeline (baseado em minutos)
                    // IMPORTANTE: Verificar TODAS as aulas para encontrar o range real
                    // E garantir que usamos SEMPRE o mesmo range para coluna de horários e posicionamento das aulas
                    $horaMinimaReal = $horaMinima; // Começar com o mínimo definido (6:00 ou menor das aulas)
                    $horaMaximaReal = $horaMaxima; // Começar com o máximo definido (23:00 ou maior das aulas)
                    
                    foreach ($todasAulasCalendario as $aula) {
                        // Normalizar hora início
                        $horaInicioStr = $aula['hora_inicio'];
                        $horaFimStr = $aula['hora_fim'];
                        if (strlen($horaInicioStr) == 8) {
                            $horaInicioStr = substr($horaInicioStr, 0, 5);
                        }
                        if (strlen($horaFimStr) == 8) {
                            $horaFimStr = substr($horaFimStr, 0, 5);
                        }
                        
                        list($horaInicio, $minInicio) = explode(':', $horaInicioStr);
                        list($horaFim, $minFim) = explode(':', $horaFimStr);
                        
                        $horaInicioMinutos = (int)$horaInicio * 60 + (int)$minInicio;
                        $horaFimMinutos = (int)$horaFim * 60 + (int)$minFim;
                        
                        // Atualizar range real
                        if ($horaInicioMinutos < $horaMinimaReal) {
                            $horaMinimaReal = max(6 * 60, $horaInicioMinutos - 30); // Não ir antes de 6:00
                        }
                        if ($horaFimMinutos > $horaMaximaReal) {
                            $horaMaximaReal = $horaFimMinutos; // Sem margem - terminar exatamente quando a última aula termina
                        }
                    }
                    
                    // Se não há aulas ou a última aula termina antes de 23:00, terminar em 23:00
                    // Se há aulas após 23:00, estender para mostrar todas
                    if (empty($todasAulasCalendario) || $horaMaximaReal <= 23 * 60) {
                        $horaMaximaFinal = 23 * 60; // Terminar exatamente em 23:00 se não houver aulas depois
                    } else {
                        $horaMaximaFinal = $horaMaximaReal; // Estender se houver aulas após 23:00
                    }
                    $horaMinimaFinal = min($horaMinima, $horaMinimaReal); // Usar o menor entre o padrão e o real
                    
                    // CRÍTICO: Atualizar as variáveis GLOBAIS para serem usadas em TODOS os cálculos
                    // Isso garante que coluna de horários e posicionamento das aulas usem o mesmo range
                    $horaMinimaUsar = $horaMinimaFinal;
                    $horaMaximaUsar = $horaMaximaFinal;
                    
                    // SOBRESCREVER $horaMinima e $horaMaxima para garantir consistência
                    $horaMinima = $horaMinimaFinal;
                    $horaMaxima = $horaMaximaFinal;
                    
                    // Calcular altura total: do início até exatamente 23:00 (ou mais se houver aulas depois)
                    $alturaTotalMinutos = $horaMaximaFinal - $horaMinimaFinal;
                    $alturaTotalPx = $alturaTotalMinutos * 2; // 2px por minuto
                    
                    // GARANTIR que altura termine exatamente em 23:00 quando não há aulas depois
                    if (empty($todasAulasCalendario) || $horaMaximaReal <= 23 * 60) {
                        // Altura = (23:00 - horaMinima) * 2px
                        $alturaTotalPx = (23 * 60 - $horaMinimaFinal) * 2;
                    }
                    
                    // Debug detalhado
                    echo "<!-- DEBUG Timeline: horaMinima=$horaMinimaFinal (" . sprintf('%02d:%02d', floor($horaMinimaFinal/60), $horaMinimaFinal%60) . "), horaMaxima=$horaMaximaFinal (" . sprintf('%02d:%02d', floor($horaMaximaFinal/60), $horaMaximaFinal%60) . "), alturaTotal=$alturaTotalPx px, totalAulas=" . count($todasAulasCalendario) . " -->";
                    ?>
                    
                    <!-- Cabeçalho -->
                    <div class="timeline-header">
                        <div class="timeline-time-column" style="padding: 12px; text-align: center; font-weight: 600; border-right: 2px solid rgba(255, 255, 255, 0.3);">
                            Horário
                        </div>
                        <?php foreach ($semanaDisplay['dias'] as $idx => $data): ?>
                            <div class="timeline-day-header" data-dia-semana="<?= $idx ?>">
                                <?php if ($data): 
                                    $diaFormatado = new DateTime($data);
                                ?>
                                    <span class="dia-nome"><?= $diasSemana[$idx] ?></span>
                                    <span class="dia-data data-display"><?= $diaFormatado->format('d/m') ?></span>
                                <?php else: ?>
                                    <span class="dia-nome" style="opacity: 0.5;"><?= $diasSemana[$idx] ?></span>
                                    <span class="dia-data data-display">-</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Corpo da Timeline -->
                    <div class="timeline-body" style="min-height: <?= $alturaTotalPx ?>px; height: <?= $alturaTotalPx ?>px;">
                        <!-- Coluna de Horários -->
                        <div class="timeline-hours" id="timeline-hours-column" style="min-height: <?= $alturaTotalPx ?>px; height: <?= $alturaTotalPx ?>px;">
                            <?php
                            // Renderizar períodos com possibilidade de colapsar
                            $periodoRenderizado = '';
                            // Usar $horaMinima e $horaMaxima que já foram atualizados acima
                            $horaAtual = $horaMinima;
                            // Sempre renderizar até 23:00 para manter a grade completa
                            $horaMaximaLimite = 23 * 60;
                            
                            while ($horaAtual <= $horaMaximaLimite):
                                $horas = floor($horaAtual / 60);
                                $minutos = $horaAtual % 60;
                                
                                // Se chegou exatamente em 23:00, renderizar e parar
                                if ($horaAtual == 23 * 60) {
                                    $horaTexto = "23:00";
                                    $ehHoraInteira = true;
                                    $periodo = 'Noite';
                                    $ehInicioPeriodo = false;
                                    
                                    // Renderizar linha de 23:00
                                    ?>
                                    <div class="timeline-hour-marker hora-inteira" 
                                         style="height: 60px; flex-shrink: 0;"
                                         data-hora-minutos="<?= 23 * 60 ?>"
                                         data-hora-texto="23:00"
                                         data-periodo="noite">
                                        <span class="timeline-hour-label">23:00</span>
                                    </div>
                                    <?php
                                    break;
                                }
                                
                                $horaTexto = sprintf('%02d:%02d', $horas, $minutos);
                                $ehHoraInteira = ($minutos == 0);
                                
                                // Determinar período
                                $periodo = '';
                                if ($horas < 12) {
                                    $periodo = 'Manhã';
                                } elseif ($horas < 18) {
                                    $periodo = 'Tarde';
                                } else {
                                    $periodo = 'Noite';
                                }
                                
                                // Verificar se é início de um novo período
                                $ehInicioPeriodo = ($periodo != $periodoRenderizado && $ehHoraInteira);
                                
                                // Renderizar toggle de período no início de cada período
                                $periodoInfo = isset($periodos[$periodo]) && is_array($periodos[$periodo]) ? $periodos[$periodo] : null;
                                
                                if ($ehInicioPeriodo && $periodoInfo) {
                                    // Contar aulas neste período
                                    $periodoInicioMin = isset($periodoInfo['inicio']) ? $periodoInfo['inicio'] : 0;
                                    $periodoFimMin = isset($periodoInfo['fim']) ? $periodoInfo['fim'] : 0;
                                    $aulasNoPeriodo = 0;
                                    
                                    foreach ($todasAulasCalendario as $aula) {
                                        $horaInicioStr = $aula['hora_inicio'];
                                        if (strlen($horaInicioStr) == 8) {
                                            $horaInicioStr = substr($horaInicioStr, 0, 5);
                                        }
                                        list($horaInicio, $minInicio) = explode(':', $horaInicioStr);
                                        $horaMinAula = (int)$horaInicio * 60 + (int)$minInicio;
                                        
                                        if ($horaMinAula >= $periodoInicioMin && $horaMinAula < $periodoFimMin) {
                                            $aulasNoPeriodo++;
                                        }
                                    }
                                    
                                    // Renderizar toggle apenas se período está vazio (para colapsar)
                                    if ($aulasNoPeriodo == 0) {
                                        $periodoAlturaColapsado = 40;
                                        ?>
                                        <div class="timeline-toggle-periodo" 
                                             onclick="togglePeriodo('<?= strtolower($periodo) ?>')"
                                             data-periodo="<?= strtolower($periodo) ?>"
                                             style="height: <?= $periodoAlturaColapsado ?>px;">
                                            <div class="periodo-info">
                                                <i class="fas fa-chevron-right periodo-icon" 
                                                   id="icon-<?= strtolower($periodo) ?>" 
                                                   style="font-size: 0.7rem; transition: transform 0.2s;"></i>
                                                <span><?= $periodo ?></span>
                                            </div>
                                            <span class="periodo-badge" style="background: #6c757d;">Sem aulas - Clique para expandir</span>
                                        </div>
                                        <?php
                                        // Marcar período como colapsado inicialmente
                                        echo "<script>document.addEventListener('DOMContentLoaded', function() { togglePeriodo('" . strtolower($periodo) . "'); });</script>";
                                    }
                                }
                                
                                // Renderizar marcador de hora normal
                                // Cada slot de 30 minutos = 60px (2px por minuto)
                                $altura = 60;
                            ?>
                            <div class="timeline-hour-marker <?= $ehHoraInteira ? 'hora-inteira' : '' ?>" 
                                 style="height: <?= $altura ?>px; flex-shrink: 0;"
                                 data-hora-minutos="<?= $horaAtual ?>"
                                 data-hora-texto="<?= $horaTexto ?>"
                                 data-periodo="<?= strtolower($periodo) ?>">
                                <?php if ($ehHoraInteira): ?>
                                    <span class="timeline-hour-label"><?= $horaTexto ?></span>
                                    <?php if ($ehInicioPeriodo): ?>
                                        <span class="timeline-period-label"><?= $periodo ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <?php
                                if ($ehInicioPeriodo) {
                                    $periodoRenderizado = $periodo;
                                }
                                
                                // Avançar 30 minutos
                                $horaAtual += 30;
                                
                                // Se passou de 23:00, parar o loop
                                if ($horaAtual > 23 * 60) {
                                    break;
                                }
                            endwhile;
                            ?>
                        </div>
                        
                        <!-- Colunas dos Dias -->
                        <?php foreach ($semanaDisplay['dias'] as $diaIdx => $data): ?>
                            <div class="timeline-day-column" data-dia-semana="<?= $diaIdx ?>" data-data="<?= $data ?>" style="position: relative; min-height: <?= $alturaTotalPx ?>px; height: <?= $alturaTotalPx ?>px;" id="dia-col-<?= $diaIdx ?>">
                                <?php if ($data): 
                                    // Normalizar formato de data para busca (garantir Y-m-d)
                                    $dataBusca = $data;
                                    
                                    // Buscar aulas deste dia - tentar todas as variações de data
                                    $aulasDoDia = [];
                                    
                                    // Normalizar data da semana para comparação (garantir formato Y-m-d)
                                    $dataNormalizadaBusca = $dataBusca;
                                    if (strpos($dataNormalizadaBusca, '-') !== false) {
                                        $parts = explode('-', $dataNormalizadaBusca);
                                        if (count($parts) == 3) {
                                            $dataNormalizadaBusca = $parts[0] . '-' . str_pad($parts[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($parts[2], 2, '0', STR_PAD_LEFT);
                                        }
                                    } else {
                                        // Se não está em formato Y-m-d, converter
                                        $dt = DateTime::createFromFormat('d/m/Y', $dataBusca);
                                        if ($dt) {
                                            $dataNormalizadaBusca = $dt->format('Y-m-d');
                                        }
                                    }
                                    
                                    // MÉTODO ALTERNATIVO: Buscar diretamente de $todasAulasCalendario se aulasPorData falhar
                                    $aulasEncontradasPorData = false;
                                    
                                    // Função auxiliar para normalizar qualquer formato de data
                                    $normalizarData = function($data) {
                                        if (empty($data)) return null;
                                        
                                        // Se já está em formato Y-m-d normalizado
                                        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
                                            return $data;
                                        }
                                        
                                        // Se está em formato Y-m-d não normalizado
                                        if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $data)) {
                                            $parts = explode('-', $data);
                                            return $parts[0] . '-' . str_pad($parts[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($parts[2], 2, '0', STR_PAD_LEFT);
                                        }
                                        
                                        // Se está em formato com /
                                        if (strpos($data, '/') !== false) {
                                            $parts = explode('/', $data);
                                            if (count($parts) == 3) {
                                                // Determinar formato: Y/m/d ou d/m/Y
                                                if (strlen($parts[2]) == 4) {
                                                    // Formato d/m/Y
                                                    return $parts[2] . '-' . str_pad($parts[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                                                } elseif (strlen($parts[0]) == 4) {
                                                    // Formato Y/m/d
                                                    return $parts[0] . '-' . str_pad($parts[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($parts[2], 2, '0', STR_PAD_LEFT);
                                                }
                                            }
                                        }
                                        
                                        return null;
                                    };
                                    
                                    // Buscar em todas as chaves de aulasPorData
                                    foreach ($aulasPorData as $dataKey => $disciplinas) {
                                        $dataKeyNormalizada = $normalizarData($dataKey);
                                        
                                        if ($dataKeyNormalizada && $dataKeyNormalizada == $dataNormalizadaBusca) {
                                            $aulasEncontradasPorData = true;
                                            foreach ($disciplinas as $discId => $aulas) {
                                                foreach ($aulas as $aula) {
                                                    // Verificar se a aula realmente pertence a este dia
                                                    $aulaDataNormalizada = $normalizarData($aula['data_aula'] ?? '');
                                                    if ($aulaDataNormalizada && $aulaDataNormalizada == $dataNormalizadaBusca) {
                                                        $aulasDoDia[] = $aula;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    
                                    // SE não encontrou em aulasPorData, buscar diretamente de $todasAulasCalendario
                                    if (!$aulasEncontradasPorData && !empty($todasAulasCalendario)) {
                                        foreach ($todasAulasCalendario as $aula) {
                                            $aulaDataNormalizada = $normalizarData($aula['data_aula'] ?? '');
                                            if ($aulaDataNormalizada && $aulaDataNormalizada == $dataNormalizadaBusca) {
                                                $aulasDoDia[] = $aula;
                                            }
                                        }
                                    }
                                    
                                    // Debug detalhado - sempre exibir para diagnóstico
                                    $debugDia = "<!-- Debug Dia $dataBusca (normalizada: $dataNormalizadaBusca): " . count($aulasDoDia) . " aulas encontradas. ";
                                    if (count($aulasDoDia) > 0) {
                                        $debugDia .= "Primeira: ID " . ($aulasDoDia[0]['id'] ?? 'N/A') . " às " . ($aulasDoDia[0]['hora_inicio'] ?? 'N/A') . " - " . ($aulasDoDia[0]['nome_aula'] ?? 'N/A');
                                        $debugDia .= " | Data da primeira: " . ($aulasDoDia[0]['data_aula'] ?? 'N/A');
                                        // Verificar cálculos de posição
                                        if (!empty($aulasDoDia[0]['hora_inicio'])) {
                                            $horaInicioTeste = $aulasDoDia[0]['hora_inicio'];
                                            if (strlen($horaInicioTeste) == 8) {
                                                $horaInicioTeste = substr($horaInicioTeste, 0, 5);
                                            }
                                            $parts = explode(':', $horaInicioTeste);
                                            $inicioMinutos = (int)($parts[0] ?? 0) * 60 + (int)($parts[1] ?? 0);
                                            $topCalculado = ($inicioMinutos - $horaMinima) * 2;
                                            $debugDia .= " | Calculo top: ($inicioMinutos - $horaMinima) * 2 = $topCalculado px";
                                        }
                                    } else {
                                        $debugDia .= " NENHUMA AULA ENCONTRADA! Verificando aulasPorData...";
                                        $debugDia .= " Chaves disponíveis (" . count($aulasPorData ?? []) . "): " . implode(', ', array_slice(array_keys($aulasPorData ?? []), 0, 10));
                                        // Tentar buscar diretamente
                                        $debugDia .= " | Buscando diretamente em todasAulasCalendario...";
                                        $encontradasDiretas = 0;
                                        foreach ($todasAulasCalendario as $aulaTeste) {
                                            $aulaDataTeste = $normalizarData($aulaTeste['data_aula'] ?? '');
                                            if ($aulaDataTeste && $aulaDataTeste == $dataNormalizadaBusca) {
                                                $encontradasDiretas++;
                                            }
                                        }
                                        $debugDia .= " Encontradas diretamente: $encontradasDiretas";
                                    }
                                    $debugDia .= " -->";
                                    // Sempre exibir debug
                                    echo $debugDia;
                                    
                                    // Ordenar aulas por horário
                                    usort($aulasDoDia, function($a, $b) {
                                        return strcmp($a['hora_inicio'], $b['hora_inicio']);
                                    });
                                    
                                    // Calcular posições das aulas e slots vazios
                                    $eventos = [];
                                    foreach ($aulasDoDia as $aula) {
                                        // Converter hora_inicio e hora_fim para minutos (formato pode ser HH:MM ou HH:MM:SS)
                                        $horaInicioStr = $aula['hora_inicio'];
                                        $horaFimStr = $aula['hora_fim'];
                                        
                                        // Normalizar formato (remover segundos se presente)
                                        if (strlen($horaInicioStr) == 8) {
                                            $horaInicioStr = substr($horaInicioStr, 0, 5);
                                        }
                                        if (strlen($horaFimStr) == 8) {
                                            $horaFimStr = substr($horaFimStr, 0, 5);
                                        }
                                        
                                        // Converter para minutos desde meia-noite
                                        // Garantir que explode funciona mesmo se houver segundos
                                        $horaInicioParts = explode(':', $horaInicioStr);
                                        $horaFimParts = explode(':', $horaFimStr);
                                        
                                        $horaInicio = (int)($horaInicioParts[0] ?? 0);
                                        $minInicio = (int)($horaInicioParts[1] ?? 0);
                                        $horaFim = (int)($horaFimParts[0] ?? 0);
                                        $minFim = (int)($horaFimParts[1] ?? 0);
                                        
                                        $inicioMinutos = $horaInicio * 60 + $minInicio;
                                        $fimMinutos = $horaFim * 60 + $minFim;
                                        
                                        // Validar horários
                                        if ($inicioMinutos < 0 || $inicioMinutos > 1440 || $fimMinutos < 0 || $fimMinutos > 1440) {
                                            error_log("Horário inválido para aula ID {$aula['id']}: $horaInicioStr - $horaFimStr");
                                            continue;
                                        }
                                        
                                        $eventos[] = [
                                            'tipo' => 'aula',
                                            'inicio' => $inicioMinutos,
                                            'fim' => $fimMinutos,
                                            'aula' => $aula
                                        ];
                                    }
                                    
                                    // Ordenar eventos por início
                                    usort($eventos, function($a, $b) {
                                        return $a['inicio'] - $b['inicio'];
                                    });
                                    
                                    // Renderizar aulas e slots vazios
                                    // Usar $horaMinima que já foi atualizado acima para garantir consistência
                                    $ultimoFim = $horaMinima;
                                    
                                    // Se não há eventos, criar slots vazios por período (mais organizados)
                                    if (empty($eventos)) {
                                        // Criar slots vazios para cada período (manhã, tarde, noite)
                                        foreach ($periodos as $nomePeriodo => $periodoInfo) {
                                            // Garantir que $periodoInfo é um array
                                            if (!is_array($periodoInfo)) {
                                                continue;
                                            }
                                            $periodoInicio = isset($periodoInfo['inicio']) ? $periodoInfo['inicio'] : 0;
                                            $periodoFim = isset($periodoInfo['fim']) ? $periodoInfo['fim'] : 0;
                                            
                                            // Ajustar para o range visível
                                            if ($periodoInicio < $horaMinima) $periodoInicio = $horaMinima;
                                            if ($periodoFim > $horaMaxima) $periodoFim = $horaMaxima;
                                            if ($periodoInicio >= $periodoFim) continue;
                                            
                                            $top = ($periodoInicio - $horaMinima) * 2;
                                            $altura = ($periodoFim - $periodoInicio) * 2;
                                            $horaSlot = sprintf('%02d:%02d', floor($periodoInicio / 60), $periodoInicio % 60);
                                            
                                            // REMOVIDO: displayStyle baseado em colapso - períodos sempre visíveis inicialmente
                                            // O JavaScript controlará o colapso visualmente se o usuário clicar
                                    ?>
                                    <div class="timeline-slot vazio periodo-slot" 
                                         data-periodo="<?= strtolower($nomePeriodo) ?>"
                                         data-periodo-inicio="<?= $periodoInicio ?>"
                                         data-periodo-fim="<?= $periodoFim ?>"
                                         style="top: <?= $top ?>px; height: <?= $altura ?>px; position: absolute; left: 2px; right: 2px; <?= $displayStyle ?>"
                                         onclick="agendarNoSlot('<?= $data ?>', '<?= $horaSlot ?>')"
                                         data-data="<?= $data ?>"
                                         data-hora-inicio="<?= $horaSlot ?>"
                                         title="Clique para agendar aula em <?= $nomePeriodo ?>">
                                        <?php if ($altura > 60 && (!isset($periodoInfo['colapsado']) || !$periodoInfo['colapsado'])): ?>
                                        <div style="display: flex; align-items: center; justify-content: center; height: 100%; padding: 10px; pointer-events: none;">
                                            <span style="color: #adb5bd; font-size: 0.7rem; text-align: center;"><?= $nomePeriodo ?> - Clique para agendar</span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php
                                        }
                                    } else {
                                        // Renderizar cada evento
                                        foreach ($eventos as $evento) {
                                            // Determinar período desta aula
                                            $periodoAula = '';
                                            if ($evento['inicio'] < 12 * 60) {
                                                $periodoAula = 'manhã';
                                            } elseif ($evento['inicio'] < 18 * 60) {
                                                $periodoAula = 'tarde';
                                            } else {
                                                $periodoAula = 'noite';
                                            }
                                            $periodoAulaInfo = isset($periodos[ucfirst($periodoAula)]) && is_array($periodos[ucfirst($periodoAula)]) ? $periodos[ucfirst($periodoAula)] : null;
                                            // CRÍTICO: Períodos com aulas NUNCA devem ser escondidos, mesmo que marcados como colapsados
                                            // Se há aulas no período, ele não pode estar colapsado
                                            $periodoColapsado = false; // Sempre false para períodos com aulas
                                            
                                            // Slot vazio antes da aula (se houver)
                                            if ($evento['inicio'] > $ultimoFim && $evento['inicio'] - $ultimoFim >= 30) {
                                            $slotInicio = $ultimoFim;
                                            $slotFim = $evento['inicio'];
                                            $top = ($slotInicio - $horaMinima) * 2;
                                            $altura = ($slotFim - $slotInicio) * 2;
                                            $horaSlot = sprintf('%02d:%02d', floor($slotInicio / 60), $slotInicio % 60);
                                            
                                            // Determinar período do slot vazio
                                            $periodoSlot = '';
                                            if ($slotInicio < 12 * 60) {
                                                $periodoSlot = 'manhã';
                                            } elseif ($slotInicio < 18 * 60) {
                                                $periodoSlot = 'tarde';
                                            } else {
                                                $periodoSlot = 'noite';
                                            }
                                            // REMOVIDO: Verificação de colapso para slots vazios
                                            // Slots sempre visíveis
                                    ?>
                                    <div class="timeline-slot vazio" 
                                         data-periodo="<?= $periodoSlot ?>"
                                         style="top: <?= $top ?>px; height: <?= $altura ?>px; position: absolute; left: 2px; right: 2px;"
                                         onclick="agendarNoSlot('<?= $data ?>', '<?= $horaSlot ?>')"
                                         data-data="<?= $data ?>"
                                         data-hora-inicio="<?= $horaSlot ?>"
                                         title="Clique para agendar aula a partir de <?= $horaSlot ?>">
                                    </div>
                                    <?php
                                        }
                                        
                                        // Aula
                                        $aula = $evento['aula'];
                                        $ehUltima = isset($ultimaAulaPorDisciplina[$aula['disciplina_id']]) && 
                                                   $ultimaAulaPorDisciplina[$aula['disciplina_id']]['id'] == $aula['id'];
                                        $corDisciplina = $coresDisciplinas[$aula['disciplina_id']] ?? '#023A8D';
                                        $nomeDisciplina = $disciplinasMap[$aula['disciplina_id']] ?? 'Disciplina';
                                        
                                        // Calcular posição e altura (2px por minuto)
                                        // CRÍTICO: Usar $horaMinima que já foi atualizado acima para garantir alinhamento
                                        // Calcular top baseado no início do evento
                                        $top = ($evento['inicio'] - $horaMinima) * 2;
                                        
                                        // Calcular altura baseado na duração (2px por minuto)
                                        $duracaoMinutos = $evento['fim'] - $evento['inicio'];
                                        $altura = max(40, $duracaoMinutos * 2); // Mínimo de 40px para visibilidade
                                        
                                        // Validar que top não seja negativo (aula antes do início da timeline)
                                        if ($top < 0) {
                                            echo "<!-- AVISO: Aula antes de horaMinima! top={$top}px, inicio={$evento['inicio']}min (" . sprintf('%02d:%02d', floor($evento['inicio']/60), $evento['inicio']%60) . "), horaMinima={$horaMinima}min (" . sprintf('%02d:%02d', floor($horaMinima/60), $horaMinima%60) . "). Ajustando para 0. -->";
                                            $top = 0;
                                        }
                                        
                                        // Garantir que a altura não ultrapasse o limite da timeline
                                        $maxTop = ($horaMaxima - $horaMinima) * 2;
                                        if ($top + $altura > $maxTop) {
                                            $altura = max(40, $maxTop - $top);
                                        }
                                        
                                        // Debug da posição para diagnóstico
                                        echo "<!-- AULA ID {$aula['id']}: top={$top}px, altura={$altura}px, inicio={$evento['inicio']}min (" . sprintf('%02d:%02d', floor($evento['inicio']/60), $evento['inicio']%60) . "), fim={$evento['fim']}min (" . sprintf('%02d:%02d', floor($evento['fim']/60), $evento['fim']%60) . "), duracao={$duracaoMinutos}min, horaMinima={$horaMinima}min (" . sprintf('%02d:%02d', floor($horaMinima/60), $horaMinima%60) . ") -->";
                                        
                                        $horaInicioStr = $aula['hora_inicio'];
                                        $horaFimStr = $aula['hora_fim'];
                                        if (strlen($horaInicioStr) == 8) {
                                            $horaInicioStr = substr($horaInicioStr, 0, 5);
                                        }
                                        if (strlen($horaFimStr) == 8) {
                                            $horaFimStr = substr($horaFimStr, 0, 5);
                                        }
                                    ?>
                                    <div class="timeline-slot aula <?= $ehUltima ? 'ultima' : '' ?>"
                                         data-periodo="<?= $periodoAula ?>"
                                         style="top: <?= $top ?>px; height: <?= $altura ?>px; background: <?= $corDisciplina ?>; position: absolute; left: 2px; right: 2px; z-index: 10; border: 1px solid rgba(255,255,255,0.3); overflow: hidden; box-sizing: border-box; display: block !important;"
                                         onclick="verDetalhesAula(<?= $aula['id'] ?>)"
                                         data-aula-id="<?= $aula['id'] ?>"
                                         data-disciplina-id="<?= $aula['disciplina_id'] ?>"
                                         data-inicio-minutos="<?= $evento['inicio'] ?>"
                                         data-fim-minutos="<?= $evento['fim'] ?>"
                                         data-top-original="<?= $top ?>"
                                         title="<?= htmlspecialchars($nomeDisciplina) ?> - <?= htmlspecialchars($aula['nome_aula']) ?> (<?= $horaInicioStr ?> - <?= $horaFimStr ?>)">
                                        <div style="font-weight: 600; margin-bottom: 2px; font-size: 0.8rem;">
                                            <?= htmlspecialchars($nomeDisciplina) ?>
                                        </div>
                                        <div style="opacity: 0.95; font-size: 0.7rem;">
                                            <?= htmlspecialchars($aula['nome_aula']) ?>
                                        </div>
                                        <div style="font-size: 0.65rem; opacity: 0.9; margin-top: 2px;">
                                            <?= $horaInicioStr ?> - <?= $horaFimStr ?>
                                        </div>
                                        <?php if ($ehUltima): ?>
                                            <div style="font-size: 0.6rem; margin-top: 2px; opacity: 0.95;">
                                                <i class="fas fa-flag"></i> Última
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php
                                            $ultimoFim = $evento['fim'];
                                        }
                                    }
                                    
                                    // Slot vazio final (se houver espaço significativo após última aula)
                                    // Só criar se houver pelo menos 1 hora de espaço
                                    if ($ultimoFim < $horaMaxima && $horaMaxima - $ultimoFim >= 60) {
                                        $top = ($ultimoFim - $horaMinima) * 2;
                                        $altura = ($horaMaxima - $ultimoFim) * 2;
                                        $horaSlot = sprintf('%02d:%02d', floor($ultimoFim / 60), $ultimoFim % 60);
                                        
                                        // Determinar período do slot final
                                        $periodoSlotFinal = '';
                                        if ($ultimoFim < 12 * 60) {
                                            $periodoSlotFinal = 'manhã';
                                        } elseif ($ultimoFim < 18 * 60) {
                                            $periodoSlotFinal = 'tarde';
                                        } else {
                                            $periodoSlotFinal = 'noite';
                                        }
                                        // REMOVIDO: Verificação de colapso para slot final
                                    ?>
                                    <div class="timeline-slot vazio" 
                                         data-periodo="<?= $periodoSlotFinal ?>"
                                         style="top: <?= $top ?>px; height: <?= $altura ?>px; position: absolute; left: 2px; right: 2px;"
                                         onclick="agendarNoSlot('<?= $data ?>', '<?= $horaSlot ?>')"
                                         data-data="<?= $data ?>"
                                         data-hora-inicio="<?= $horaSlot ?>"
                                         title="Clique para agendar aula a partir de <?= $horaSlot ?>">
                                    </div>
                                    <?php
                                    }
                                    
                                    // Adicionar slots vazios clicáveis para horários comuns apenas em períodos expandidos
                                    // Horários comuns por período
                                    $horariosComuns = [
                                        'manhã' => [7 * 60, 8 * 60, 9 * 60, 10 * 60, 11 * 60],
                                        'tarde' => [13 * 60, 14 * 60, 15 * 60, 16 * 60, 17 * 60],
                                        'noite' => [18 * 60, 19 * 60, 20 * 60, 21 * 60]
                                    ];
                                    
                                    foreach ($horariosComuns as $nomePeriodo => $horas) {
                                        // Criar slots sempre, independente de colapso
                                        // O colapso é apenas visual e controlado pelo JavaScript
                                        
                                        foreach ($horas as $horaMin) {
                                            if ($horaMin < $horaMinima || $horaMin >= $horaMaxima) {
                                                continue;
                                            }
                                            
                                            // Verificar se já há aula neste horário
                                            $jaTemAula = false;
                                            foreach ($eventos as $evento) {
                                                if ($evento['inicio'] <= $horaMin && $evento['fim'] > $horaMin) {
                                                    $jaTemAula = true;
                                                    break;
                                                }
                                                if (abs($evento['inicio'] - $horaMin) < 30) {
                                                    $jaTemAula = true;
                                                    break;
                                                }
                                            }
                                            
                                            // Verificar se está em intervalo vazio
                                            $jaCoberto = false;
                                            if (!empty($eventos)) {
                                                foreach ($eventos as $idx => $evento) {
                                                    if ($idx == 0 && $horaMin < $evento['inicio']) {
                                                        $jaCoberto = true;
                                                        break;
                                                    }
                                                    if ($idx > 0 && $horaMin > $eventos[$idx - 1]['fim'] && $horaMin < $evento['inicio']) {
                                                        $jaCoberto = true;
                                                        break;
                                                    }
                                                }
                                                if (!$jaCoberto && $horaMin > end($eventos)['fim']) {
                                                    $jaCoberto = true;
                                                }
                                            }
                                            
                                            if (!$jaTemAula && !$jaCoberto) {
                                                $top = ($horaMin - $horaMinima) * 2;
                                                $altura = 60;
                                                $horaSlot = sprintf('%02d:%02d', floor($horaMin / 60), $horaMin % 60);
                                                ?>
                                                <div class="timeline-slot vazio" 
                                                     style="top: <?= $top ?>px; height: <?= $altura ?>px; min-height: 60px; position: relative;"
                                                     onclick="agendarNoSlot('<?= $data ?>', '<?= $horaSlot ?>')"
                                                     data-data="<?= $data ?>"
                                                     data-hora-inicio="<?= $horaSlot ?>"
                                                     data-periodo="<?= $nomePeriodo ?>"
                                                     title="Clique para agendar aula às <?= $horaSlot ?>">
                                                </div>
                                                <?php
                                            }
                                        }
                                    }
                                    ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Aba Estatísticas -->
    <div id="tab-estatisticas" class="tab-content">
        <!-- Estatísticas Gerais da Turma -->
        <div class="estatisticas-container" style="margin-bottom: 25px;">
    <div class="stat-card stat-card-blue">
        <div class="stat-icon">
            <i class="fas fa-calendar-alt"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?= $totalAulas ?></div>
            <div class="stat-label">Aulas Agendadas</div>
        </div>
    </div>
    
    <div class="stat-card stat-card-green">
        <div class="stat-icon">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?= $totalHoras ?>h</div>
                    <div class="stat-label">Carga Horária Total</div>
        </div>
    </div>
    
    <div class="stat-card stat-card-orange">
        <div class="stat-icon">
            <i class="fas fa-graduation-cap"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?= round($totalMinutosCurso / 60, 1) ?>h</div>
            <div class="stat-label">Carga Horária Obrigatória</div>
        </div>
    </div>
    
    <div class="stat-card stat-card-purple">
        <div class="stat-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?= $totalAlunos ?></div>
            <div class="stat-label">Alunos Matriculados</div>
        </div>
    </div>
</div>

        <!-- Progresso das Disciplinas -->
        <?php
        // Calcular progresso geral
        $totalAulasObrigatorias = 0;
        $totalAulasAgendadas = 0;
        $totalAulasRealizadas = 0;

        foreach ($estatisticasDisciplinas as $stats) {
            $totalAulasObrigatorias += $stats['obrigatorias'];
            $totalAulasAgendadas += $stats['agendadas'];
            $totalAulasRealizadas += $stats['realizadas'];
        }

        $percentualGeral = $totalAulasObrigatorias > 0 ? round(($totalAulasAgendadas / $totalAulasObrigatorias) * 100, 1) : 0;
        $corProgresso = $percentualGeral >= 100 ? '#28a745' : ($percentualGeral >= 75 ? '#ffc107' : '#dc3545');
        ?>

        <div style="padding: 20px;">
            <h4 style="color: #023A8D; margin-bottom: 15px; display: flex; align-items: center;">
                <i class="fas fa-chart-line me-2"></i>Progresso das Disciplinas
            </h4>
            
            <!-- Progresso Geral -->
            <div id="progresso-geral-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; padding: 15px; margin-bottom: 20px; color: white;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <div style="flex: 1; min-width: 200px;">
                        <div style="display: flex; align-items: baseline; gap: 12px;">
                            <span id="percentual-geral" style="font-size: 2.5rem; font-weight: bold; line-height: 1;"><?= $percentualGeral ?>%</span>
                            <span id="total-aulas-texto" style="opacity: 0.95; font-size: 1rem; line-height: 1.2;">
                                <?= $totalAulasAgendadas ?> de <?= $totalAulasObrigatorias ?> aulas agendadas
                            </span>
                        </div>
                    </div>
                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <div style="background: rgba(255,255,255,0.25); padding: 8px 15px; border-radius: 6px;">
                            <div id="total-realizadas" style="font-size: 1.3rem; font-weight: bold; line-height: 1.2;"><?= $totalAulasRealizadas ?></div>
                            <div style="font-size: 0.8rem; opacity: 0.95;">Realizadas</div>
                        </div>
                        <div style="background: rgba(255,255,255,0.25); padding: 8px 15px; border-radius: 6px;">
                            <div id="total-faltantes" style="font-size: 1.3rem; font-weight: bold; line-height: 1.2;"><?= ($totalAulasObrigatorias - $totalAulasAgendadas) ?></div>
                            <div style="font-size: 0.8rem; opacity: 0.95;">Faltantes</div>
                        </div>
                    </div>
                </div>
                <div style="background: rgba(255,255,255,0.25); height: 8px; border-radius: 4px; margin-top: 15px; overflow: hidden;">
                    <div id="barra-progresso-geral" style="background: white; height: 100%; width: <?= $percentualGeral ?>%; transition: width 0.3s ease; border-radius: 4px;"></div>
                </div>
            </div>
            
            <!-- Lista de Progresso por Disciplina (Otimizada) -->
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <?php foreach ($disciplinasSelecionadas as $disciplina): 
                    $disciplinaId = $disciplina['disciplina_id'];
                    $stats = $estatisticasDisciplinas[$disciplinaId] ?? ['agendadas' => 0, 'realizadas' => 0, 'faltantes' => 0, 'obrigatorias' => 0];
                    
                    $percentualDisciplina = $stats['obrigatorias'] > 0 ? round(($stats['agendadas'] / $stats['obrigatorias']) * 100, 1) : 0;
                    
                    // Definir cor baseada no progresso
                    if ($percentualDisciplina >= 100) {
                        $corBarra = '#28a745';
                        $statusIcon = '✓';
                    } elseif ($percentualDisciplina >= 75) {
                        $corBarra = '#ffc107';
                        $statusIcon = '⚠';
                    } elseif ($stats['agendadas'] > 0) {
                        $corBarra = '#17a2b8';
                        $statusIcon = '…';
                    } else {
                        $corBarra = '#dc3545';
                        $statusIcon = '○';
                    }
                    
                    $nomeDisciplina = htmlspecialchars($disciplina['nome_disciplina'] ?? $disciplina['nome_original'] ?? 'Disciplina');
                ?>
                <div style="background: #f8f9fa; padding: 12px; border-left: 4px solid <?= $corBarra ?>; margin-bottom: 8px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 1.2rem; color: <?= $corBarra ?>; font-weight: bold;"><?= $statusIcon ?></span>
                                <span style="font-weight: 600; color: #023A8D; font-size: 0.95rem;"><?= $nomeDisciplina ?></span>
                            </div>
                        </div>
                        <div style="display: flex; gap: 15px; align-items: center; font-size: 0.85rem;">
                            <span style="color: #023A8D; font-weight: 600;"><?= $stats['agendadas'] ?>/<?= $stats['obrigatorias'] ?></span>
                            <span style="color: <?= $corBarra ?>; font-weight: bold; font-size: 1rem;"><?= $percentualDisciplina ?>%</span>
                        </div>
                    </div>
                    <div style="background: #e9ecef; height: 6px; overflow: hidden;">
                        <div style="background: <?= $corBarra ?>; height: 100%; width: <?= min($percentualDisciplina, 100) ?>%; transition: width 0.3s ease;"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 8px; font-size: 0.75rem; color: #666;">
                        <span>Agendadas: <strong style="color: #023A8D;"><?= $stats['agendadas'] ?></strong></span>
                        <span>Realizadas: <strong style="color: #28a745;"><?= $stats['realizadas'] ?></strong></span>
                        <span>Faltantes: <strong style="color: #dc3545;"><?= $stats['faltantes'] ?></strong></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript para Calendário -->
<script>
function filtrarCalendario() {
    const filtro = document.getElementById('filtro-disciplina-calendario').value;
    const aulas = document.querySelectorAll('.timeline-slot.aula');
    const slots = document.querySelectorAll('.timeline-slot.vazio');
    
    // Se não há filtro, mostrar tudo
    if (!filtro) {
        aulas.forEach(aula => aula.style.display = '');
        slots.forEach(slot => slot.style.display = '');
        return;
    }
    
    // Filtrar aulas
    aulas.forEach(aula => {
        const disciplinaId = aula.getAttribute('data-disciplina-id');
        if (disciplinaId === filtro) {
            aula.style.display = '';
        } else {
            aula.style.display = 'none';
        }
    });
    
    // Para slots vazios, manter sempre visíveis para poder agendar
}

function agendarNoSlot(data, hora) {
    // Converter data para formato do modal (Y-m-d)
    const dataFormatada = data;
    
    // Buscar disciplina que precisa de mais aulas (priorizar a que tem mais faltantes)
    const disciplinas = <?= json_encode(array_map(function($d) use ($estatisticasDisciplinas) {
        $id = $d['disciplina_id'];
        $stats = $estatisticasDisciplinas[$id] ?? [];
        return [
            'id' => $id,
            'nome' => $d['nome_disciplina'] ?? $d['nome_original'] ?? 'Disciplina',
            'faltantes' => $stats['faltantes'] ?? 0
        ];
    }, $disciplinasSelecionadas)) ?>;
    
    // Ordenar por faltantes (maior primeiro) e pegar a primeira
    disciplinas.sort((a, b) => b.faltantes - a.faltantes);
    const disciplinaSelecionada = disciplinas[0];
    
    if (disciplinaSelecionada && disciplinaSelecionada.faltantes > 0) {
        // Abrir modal de agendamento com dados pré-preenchidos
        abrirModalAgendarAula(
            disciplinaSelecionada.id,
            disciplinaSelecionada.nome,
            '<?= $turma['data_inicio'] ?>',
            '<?= $turma['data_fim'] ?>'
        );
        
        // Aguardar o modal abrir e preencher campos
        setTimeout(() => {
            const dataInput = document.querySelector('#modalAgendarAula input[name="data_aula"], #modalAgendarAula input[id*="data"]');
            const horaInput = document.querySelector('#modalAgendarAula input[name="hora_inicio"], #modalAgendarAula input[id*="hora"]');
            
            if (dataInput) {
                dataInput.value = dataFormatada;
                dataInput.dispatchEvent(new Event('change'));
            }
            if (horaInput) {
                horaInput.value = hora;
                horaInput.dispatchEvent(new Event('change'));
            }
        }, 300);
    } else {
        alert('Todas as disciplinas já têm todas as aulas agendadas!');
    }
}

function verDetalhesAula(aulaId) {
    // Buscar detalhes da aula e mostrar em um tooltip ou modal pequeno
    // Por enquanto, apenas um alert simples
    // TODO: Implementar modal de detalhes mais completo
    const aulaElement = document.querySelector(`[data-aula-id="${aulaId}"]`);
    if (aulaElement) {
        alert('Clique na aba "Disciplinas" para ver e editar detalhes completos da aula.');
    }
}

function mudarSemana(direcao) {
    const semanasJson = document.getElementById('semanas-disponiveis').value;
    const semanas = JSON.parse(semanasJson);
    const indiceAtual = parseInt(document.getElementById('semana-atual-indice').value);
    const novoIndice = indiceAtual + direcao;
    
    if (novoIndice < 0 || novoIndice >= semanas.length) {
        return; // Não permitir ir além dos limites
    }
    
    // Atualizar índice
    document.getElementById('semana-atual-indice').value = novoIndice;
    
    // Atualizar cabeçalho da semana
    const semana = semanas[novoIndice];
    if (semana.inicio) {
        const inicio = new Date(semana.inicio + 'T00:00:00');
        const fim = new Date(semana.inicio + 'T00:00:00');
        fim.setDate(fim.getDate() + 6); // Domingo + 6 dias = Sábado
        
        const formatarData = (data) => {
            const dia = String(data.getDate()).padStart(2, '0');
            const mes = String(data.getMonth() + 1).padStart(2, '0');
            return dia + '/' + mes;
        };
        
        const formatarDataCompleta = (data) => {
            const dia = String(data.getDate()).padStart(2, '0');
            const mes = String(data.getMonth() + 1).padStart(2, '0');
            const ano = data.getFullYear();
            return dia + '/' + mes + '/' + ano;
        };
        
        const totalSemanas = semanas.length;
        const semanaNumero = novoIndice + 1;
        document.getElementById('info-semana-atual').innerHTML = 
            'Semana ' + semanaNumero + ' de ' + totalSemanas + '<br>' +
            '<small style="font-size: 0.8rem; opacity: 0.9;">' + formatarData(inicio) + ' - ' + formatarDataCompleta(fim) + '</small>';
        
        // Atualizar datas nos cabeçalhos das colunas
        const diasSemana = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta'];
        semana.dias.forEach((data, idx) => {
            const th = document.querySelector(`th[data-dia-semana="${idx}"]`);
            if (th && data) {
                const dataObj = new Date(data + 'T00:00:00');
                const small = th.querySelector('.data-display');
                if (small) {
                    small.textContent = formatarData(dataObj);
                }
            } else if (th) {
                const small = th.querySelector('.data-display');
                if (small) {
                    small.textContent = '-';
                }
            }
        });
        
        // Atualizar conteúdo do calendário via AJAX para evitar recarregar página
        atualizarCalendarioSemana(novoIndice);
    }
    
    // Atualizar estado dos botões
    document.getElementById('btn-semana-anterior').disabled = novoIndice === 0;
    document.getElementById('btn-semana-proxima').disabled = novoIndice === semanas.length - 1;
}

function atualizarCalendarioSemana(novoIndice) {
    // Recarregar a página com o novo índice para buscar apenas aulas da semana selecionada
    const url = new URL(window.location);
    url.searchParams.set('semana_calendario', novoIndice);
    window.location.href = url.toString();
    return;
    
    // Código abaixo não será executado (recarrega a página)
    /*
    const semanasJson = document.getElementById('semanas-disponiveis').value;
    const semanas = JSON.parse(semanasJson);
    const semana = semanas[novoIndice];
    
    if (!semana) return;
    
    // Obter dados das aulas já carregados (passados do PHP via script JSON)
    const dadosScript = document.getElementById('dados-calendario');
    if (!dadosScript) {
        console.error('Dados do calendário não encontrados');
        return;
    }
    
    const dados = JSON.parse(dadosScript.textContent);
    const aulasPorData = dados.aulasPorData || {};
    const ultimaAulaPorDisciplina = dados.ultimaAulaPorDisciplina || {};
    const coresDisciplinas = dados.coresDisciplinas || {};
    const disciplinasMap = dados.disciplinasMap || {};
    const horarios = dados.horarios || [];
    */
    
    // Atualizar cabeçalhos das colunas
    const diasSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    semana.dias.forEach((data, idx) => {
        const th = document.querySelector(`th[data-dia-semana="${idx}"]`);
        if (th) {
            if (data) {
                const dataObj = new Date(data + 'T00:00:00');
                const formatarData = (d) => {
                    const dia = String(d.getDate()).padStart(2, '0');
                    const mes = String(d.getMonth() + 1).padStart(2, '0');
                    return dia + '/' + mes;
                };
                th.style.background = '#023A8D';
                th.style.color = 'white';
                const small = th.querySelector('.data-display');
                if (small) small.textContent = formatarData(dataObj);
            } else {
                th.style.background = '#e9ecef';
                th.style.color = '#6c757d';
                const small = th.querySelector('.data-display');
                if (small) small.textContent = '-';
            }
        }
    });
    
    // Atualizar corpo da tabela
    const tbody = document.querySelector('#tab-calendario tbody');
    if (!tbody) return;
    
    // Limpar e reconstruir
    tbody.innerHTML = '';
    
    let periodoAtual = '';
    horarios.forEach(hora => {
        const horaObj = new Date('2000-01-01T' + hora + ':00');
        const periodo = horaObj.getHours() < 12 ? 'Manhã' : (horaObj.getHours() < 18 ? 'Tarde' : 'Noite');
        const mostrarPeriodo = periodo !== periodoAtual;
        periodoAtual = periodo;
        
        const horaFimObj = new Date(horaObj);
        horaFimObj.setMinutes(horaFimObj.getMinutes() + 50);
        const horaFim = String(horaFimObj.getHours()).padStart(2, '0') + ':' + String(horaFimObj.getMinutes()).padStart(2, '0');
        
        const tr = document.createElement('tr');
        
        // Coluna de horário
        const tdHorario = document.createElement('td');
        tdHorario.style.cssText = 'background: #f8f9fa; padding: 8px; text-align: center; font-weight: 600; font-size: 0.85rem; position: sticky; left: 0; z-index: 5; border-right: 2px solid #dee2e6;';
        if (mostrarPeriodo) {
            tdHorario.innerHTML = `<div style="font-size: 0.7rem; color: #6c757d; margin-bottom: 4px;">${periodo}</div><div>${hora} - ${horaFim}</div>`;
        } else {
            tdHorario.innerHTML = `<div>${hora} - ${horaFim}</div>`;
        }
        tr.appendChild(tdHorario);
        
        // Colunas dos dias
        semana.dias.forEach((data, diaIdx) => {
            const td = document.createElement('td');
            td.style.cssText = 'padding: 4px; vertical-align: top;';
            
            if (!data) {
                tr.appendChild(td);
                return;
            }
            
            let temAula = false;
            let aulasNoSlot = [];
            
            if (aulasPorData[data]) {
                Object.keys(aulasPorData[data]).forEach(discId => {
                    aulasPorData[data][discId].forEach(aula => {
                        // Comparar apenas horários (usar base de referência para comparar tempo)
                        const horaSlotMin = hora.split(':').map(Number);
                        const horaSlotMinutos = horaSlotMin[0] * 60 + horaSlotMin[1];
                        
                        const aulaInicioMin = aula.hora_inicio.split(':').map(Number);
                        const aulaInicioMinutos = aulaInicioMin[0] * 60 + aulaInicioMin[1];
                        
                        const aulaFimMin = aula.hora_fim.split(':').map(Number);
                        const aulaFimMinutos = aulaFimMin[0] * 60 + aulaFimMin[1];
                        
                        const horaSlotFimMinutos = horaSlotMinutos + 50;
                        
                        // Verificar sobreposição: slot conflita se começa durante a aula ou a aula começa durante o slot
                        if ((horaSlotMinutos >= aulaInicioMinutos && horaSlotMinutos < aulaFimMinutos) ||
                            (horaSlotMinutos < aulaInicioMinutos && horaSlotFimMinutos > aulaInicioMinutos)) {
                            temAula = true;
                            aulasNoSlot.push(aula);
                        }
                    });
                });
            }
            
            if (temAula && aulasNoSlot.length > 0) {
                const aula = aulasNoSlot[0];
                const ehUltima = ultimaAulaPorDisciplina[aula.disciplina_id]?.id === aula.id;
                const corDisciplina = coresDisciplinas[aula.disciplina_id] || '#023A8D';
                const nomeDisciplina = disciplinasMap[aula.disciplina_id] || 'Disciplina';
                
                const divAula = document.createElement('div');
                divAula.className = 'calendario-aula';
                divAula.setAttribute('data-aula-id', aula.id);
                divAula.setAttribute('data-disciplina-id', aula.disciplina_id);
                divAula.style.cssText = `background: ${corDisciplina}; color: white; padding: 6px; border-radius: 4px; font-size: 0.75rem; cursor: pointer; ${ehUltima ? 'border: 2px solid #fff; box-shadow: 0 0 0 2px ' + corDisciplina + ';' : ''}`;
                divAula.onclick = () => verDetalhesAula(aula.id);
                divAula.title = nomeDisciplina + ' - ' + aula.nome_aula;
                
                divAula.innerHTML = `
                    <div style="font-weight: 600; margin-bottom: 2px;">${nomeDisciplina.substring(0, 15)}</div>
                    <div style="opacity: 0.9; font-size: 0.7rem;">${aula.nome_aula.substring(0, 20)}</div>
                    ${ehUltima ? '<div style="font-size: 0.65rem; margin-top: 2px; opacity: 0.95;"><i class="fas fa-flag"></i> Última</div>' : ''}
                `;
                
                td.appendChild(divAula);
            } else {
                const divSlot = document.createElement('div');
                divSlot.className = 'calendario-slot-vazio';
                divSlot.setAttribute('data-data', data);
                divSlot.setAttribute('data-hora', hora);
                divSlot.style.cssText = 'background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 4px; padding: 20px 8px; text-align: center; cursor: pointer; transition: all 0.2s; min-height: 60px; display: flex; align-items: center; justify-content: center;';
                divSlot.onmouseover = function() {
                    this.style.background = '#e9ecef';
                    this.style.borderColor = '#023A8D';
                };
                divSlot.onmouseout = function() {
                    this.style.background = '#f8f9fa';
                    this.style.borderColor = '#dee2e6';
                };
                divSlot.onclick = () => agendarNoSlot(data, hora);
                divSlot.title = 'Clique para agendar aula neste horário';
                divSlot.innerHTML = '<span style="color: #adb5bd; font-size: 0.7rem;">Disponível</span>';
                
                td.appendChild(divSlot);
            }
            
            tr.appendChild(td);
        });
        
        tbody.appendChild(tr);
    });
}

// Inicializar ao carregar
document.addEventListener('DOMContentLoaded', function() {
    // Garantir que a primeira aba mostrada seja a primeira semana do calendário
    const url = new URL(window.location);
    const semanaCalendario = url.searchParams.get('semana_calendario');
    
    // Se estiver na aba Calendário e não houver parâmetro ou for inválido, garantir primeira semana
    const abaAtiva = localStorage.getItem('turmaDetalhesAbaAtiva') || 'resumo';
    if (abaAtiva === 'calendario' && (!semanaCalendario || parseInt(semanaCalendario) < 0)) {
        url.searchParams.set('semana_calendario', '0');
        window.history.replaceState({}, '', url);
    }
    
    const semanasJsonElement = document.getElementById('semanas-disponiveis');
    if (!semanasJsonElement) return;
    
    const semanasJson = semanasJsonElement.value;
    if (!semanasJson) return;
    
    const semanas = JSON.parse(semanasJson);
    const indiceAtual = parseInt(document.getElementById('semana-atual-indice').value) || 0;
    
    // Atualizar estado dos botões
    const btnAnterior = document.getElementById('btn-semana-anterior');
    const btnProximo = document.getElementById('btn-semana-proxima');
    
    if (btnAnterior) {
        btnAnterior.disabled = indiceAtual === 0;
        if (indiceAtual === 0) {
            btnAnterior.style.opacity = '0.5';
            btnAnterior.style.cursor = 'not-allowed';
        } else {
            btnAnterior.style.opacity = '1';
            btnAnterior.style.cursor = 'pointer';
        }
    }
    if (btnProximo) {
        btnProximo.disabled = indiceAtual === semanas.length - 1;
        if (indiceAtual === semanas.length - 1) {
            btnProximo.style.opacity = '0.5';
            btnProximo.style.cursor = 'not-allowed';
        } else {
            btnProximo.style.opacity = '1';
            btnProximo.style.cursor = 'pointer';
        }
    }
});

// Função para expandir/colapsar períodos
window.togglePeriodo = function(periodoNome) {
    const periodo = periodoNome.toLowerCase();
    console.log('togglePeriodo chamado para:', periodo);
    
    // Buscar o toggle - pode estar em qualquer lugar
    const toggleDivs = document.querySelectorAll(`.timeline-toggle-periodo[data-periodo="${periodo}"]`);
    const toggleDiv = toggleDivs[0];
    const icon = document.getElementById('icon-' + periodo);
    
    if (!toggleDiv) {
        console.warn('Toggle não encontrado para período:', periodo);
        console.log('Toggles disponíveis:', document.querySelectorAll('.timeline-toggle-periodo'));
        return;
    }
    
    // Verificar estado atual pelo ícone - se está apontando para direita (0deg ou vazio), está colapsado
    const iconTransform = icon ? icon.style.transform : '';
    const estaColapsado = !iconTransform || iconTransform === '' || iconTransform === 'rotate(0deg)' || iconTransform === 'none';
    
    console.log('Toggle período:', periodo, 'Estado atual (colapsado):', estaColapsado);
    
    // Atualizar ícone
    if (icon) {
        icon.style.transform = estaColapsado ? 'rotate(90deg)' : 'rotate(0deg)';
    }
    
    // Encontrar todos os elementos do período usando o atributo data-periodo
    // IMPORTANTE: Buscar em todas as colunas (horas e dias) e também nos toggles de período
    const markers = document.querySelectorAll(`.timeline-hour-marker[data-periodo="${periodo}"]`);
    const slotsVazios = document.querySelectorAll(`.timeline-slot[data-periodo="${periodo}"], .periodo-slot[data-periodo="${periodo}"]`);
    const aulas = document.querySelectorAll(`.timeline-slot.aula[data-periodo="${periodo}"]`);
    const periodoColapsadoDiv = document.querySelectorAll(`.timeline-periodo-colapsado[data-periodo="${periodo}"]`);
    
    console.log('Elementos encontrados:', {
        markers: markers.length,
        slotsVazios: slotsVazios.length,
        aulas: aulas.length,
        periodoColapsado: periodoColapsadoDiv.length
    });
    
    // IMPORTANTE: Separar aulas dos outros elementos
    // Aulas SEMPRE devem ser visíveis, mesmo se período está colapsado
    const elementosParaColapsar = [...markers, ...slotsVazios];
    
    // Função auxiliar para calcular offset de períodos colapsados
    const calcularOffsetPeriodosColapsados = function() {
        let offsetTotal = 0;
        const periodosOrdem = ['manhã', 'tarde', 'noite'];
        const duracaoPeriodos = {
            'manhã': 6 * 60 * 2, // 6 horas * 60 min * 2px = 720px
            'tarde': 6 * 60 * 2, // 6 horas * 60 min * 2px = 720px
            'noite': 5 * 60 * 2  // 5 horas * 60 min * 2px = 600px (até 23:00)
        };
        
        for (let i = 0; i < periodosOrdem.length; i++) {
            const p = periodosOrdem[i];
            const toggle = document.querySelector(`.timeline-toggle-periodo[data-periodo="${p}"]`);
            const icon = document.getElementById('icon-' + p);
            const iconTransform = icon ? icon.style.transform : '';
            const estaColapsado = !iconTransform || iconTransform === '' || iconTransform === 'rotate(0deg)' || iconTransform === 'none';
            
            if (toggle && estaColapsado && p !== periodo) {
                // Este período está colapsado, adicionar ao offset
                offsetTotal += duracaoPeriodos[p] - 40; // 40px é a altura do toggle quando colapsado
            }
        }
        
        return offsetTotal;
    };
    
    // Função para ajustar posição de todas as aulas baseado em períodos colapsados
    const ajustarPosicaoAulas = function() {
        const todasAulas = document.querySelectorAll('.timeline-slot.aula');
        const periodosOrdem = ['manhã', 'tarde', 'noite'];
        const inicioPeriodos = {
            'manhã': 6 * 60,    // 06:00
            'tarde': 12 * 60,   // 12:00
            'noite': 18 * 60    // 18:00
        };
        
        const fimPeriodos = {
            'manhã': 12 * 60,   // 12:00
            'tarde': 18 * 60,   // 18:00
            'noite': 23 * 60    // 23:00
        };
        
        todasAulas.forEach(aula => {
            const periodoAula = aula.getAttribute('data-periodo');
            // Obter top original do atributo data-top-original, senão do style atual
            const topOriginal = parseFloat(aula.getAttribute('data-top-original')) || parseFloat(aula.style.top) || 0;
            
            // Salvar top original se ainda não estiver salvo
            if (!aula.getAttribute('data-top-original')) {
                aula.setAttribute('data-top-original', topOriginal);
            }
            
            // Calcular offset baseado em períodos anteriores colapsados
            let offset = 0;
            for (let i = 0; i < periodosOrdem.length; i++) {
                const p = periodosOrdem[i];
                if (p === periodoAula) break; // Parar quando chegar no período da aula
                
                const toggle = document.querySelector(`.timeline-toggle-periodo[data-periodo="${p}"]`);
                const icon = document.getElementById('icon-' + p);
                const iconTransform = icon ? icon.style.transform : '';
                const estaColapsado = !iconTransform || iconTransform === '' || iconTransform === 'rotate(0deg)' || iconTransform === 'none';
                
                if (toggle && estaColapsado) {
                    // Este período anterior está colapsado
                    // Calcular altura do período quando expandido
                    const duracaoPeriodo = fimPeriodos[p] - inicioPeriodos[p];
                    const alturaPeriodoExpandido = duracaoPeriodo * 2; // 2px por minuto
                    const alturaPeriodoColapsado = 40; // altura do toggle quando colapsado
                    const alturaEconomizada = alturaPeriodoExpandido - alturaPeriodoColapsado;
                    
                    offset += alturaEconomizada;
                }
            }
            
            // Aplicar offset ao top original
            const topAjustado = Math.max(0, topOriginal - offset);
            aula.style.top = topAjustado + 'px';
            
            console.log(`Aula ${aula.getAttribute('data-aula-id')} (${periodoAula}): topOriginal=${topOriginal}px, offset=${offset}px, topAjustado=${topAjustado}px`);
        });
    };
    
    if (estaColapsado) {
        // Expandir: mostrar todos os elementos do período (exceto aulas que já estão visíveis)
        console.log('Expandindo período:', periodo);
        elementosParaColapsar.forEach(el => {
            el.classList.remove('periodo-colapsado');
            el.classList.add('periodo-expandido');
            if (el.style) {
                el.style.display = '';
                el.style.visibility = 'visible';
                el.style.maxHeight = '';
                el.style.opacity = '';
            }
        });
        // Garantir que aulas também estão visíveis
        aulas.forEach(el => {
            el.classList.remove('periodo-colapsado');
            el.classList.add('periodo-expandido');
            if (el.style) {
                el.style.display = 'block';
                el.style.visibility = 'visible';
            }
        });
        // Esconder div de período colapsado
        periodoColapsadoDiv.forEach(el => {
            if (el.style) {
                el.style.display = 'none';
            }
        });
    } else {
        // Colapsar: esconder elementos vazios e marcadores, mas MANTER aulas visíveis
        console.log('Colapsando período:', periodo);
        elementosParaColapsar.forEach(el => {
            el.classList.add('periodo-colapsado');
            el.classList.remove('periodo-expandido');
            if (el.style) {
                el.style.display = 'none';
                el.style.visibility = 'hidden';
            }
        });
        
        // CRÍTICO: GARANTIR que aulas permanecem visíveis mesmo com período colapsado
        aulas.forEach(el => {
            el.classList.remove('periodo-colapsado');
            el.classList.add('periodo-expandido');
            if (el.style) {
                el.style.display = 'block';
                el.style.visibility = 'visible';
                el.style.maxHeight = 'none';
                el.style.opacity = '1';
            }
        });
        
        // Mostrar div de período colapsado
        periodoColapsadoDiv.forEach(el => {
            if (el.style) {
                el.style.display = 'block';
            }
        });
    }
    
    // IMPORTANTE: Ajustar posição de todas as aulas após colapsar/expandir
    setTimeout(() => {
        ajustarPosicaoAulas();
    }, 100);
    
    console.log('Ação concluída para período:', periodo);
};
</script>

<!-- JavaScript para Sistema de Abas -->
<script>
// A função showTab já foi definida antes dos botões para garantir disponibilidade global
// Se por algum motivo ainda não estiver definida, definir aqui como fallback
if (typeof window.showTab !== 'function') {
    window.showTab = function(tabName) {
        // Esconder todos os conteúdos de abas
        const tabContents = document.querySelectorAll('.tab-content');
        tabContents.forEach(content => {
            content.classList.remove('active');
        });
        
        // Remover classe active de todos os botões
        const tabButtons = document.querySelectorAll('.tab-button');
        tabButtons.forEach(button => {
            button.classList.remove('active');
        });
        
        // Se for a aba Calendário, garantir que está na primeira semana
        if (tabName === 'calendario') {
            const url = new URL(window.location);
            const semanaAtual = url.searchParams.get('semana_calendario');
            // Se não há parâmetro ou é inválido, forçar primeira semana (0)
            if (!semanaAtual || parseInt(semanaAtual) < 0) {
                url.searchParams.set('semana_calendario', '0');
                window.history.replaceState({}, '', url);
                // Se realmente não havia parâmetro, recarregar para garantir primeira semana
                if (!semanaAtual) {
                    window.location.href = url.toString();
                    return;
                }
            }
        }
        
        // Mostrar a aba selecionada
        const selectedTab = document.getElementById('tab-' + tabName);
        if (selectedTab) {
            selectedTab.classList.add('active');
        }
        
        // Ativar o botão correspondente
        const buttons = document.querySelectorAll('.tab-button');
        buttons.forEach(button => {
            if (button.onclick && button.onclick.toString().includes("'" + tabName + "'")) {
                button.classList.add('active');
            } else {
                // Método alternativo: verificar pelo texto do botão
                const buttonText = button.textContent.toLowerCase().trim();
                if (buttonText.includes(tabName.toLowerCase())) {
                    button.classList.add('active');
                }
            }
        });
        
        // Salvar aba ativa no localStorage
        localStorage.setItem('turmaDetalhesAbaAtiva', tabName);
        localStorage.setItem('turma-tab-active', tabName);
    };
}

// Salvar a aba ativa no localStorage e garantir primeira semana do calendário
document.addEventListener('DOMContentLoaded', function() {
    // Verificar se precisa garantir primeira semana do calendário
    const url = new URL(window.location);
    const semanaCalendario = url.searchParams.get('semana_calendario');
    const abaAtiva = localStorage.getItem('turmaDetalhesAbaAtiva');
    
    // Se estiver acessando a aba Calendário pela primeira vez (sem parâmetro), garantir primeira semana
    if ((abaAtiva === 'calendario' || !abaAtiva) && (!semanaCalendario || semanaCalendario === '')) {
        url.searchParams.set('semana_calendario', '0');
        // Usar replaceState para não recarregar se já está na página
        window.history.replaceState({}, '', url);
    }
    
    const savedTab = localStorage.getItem('turmaDetalhesAbaAtiva') || localStorage.getItem('turma-tab-active');
    if (savedTab) {
        showTab(savedTab);
    }
    
    // Salvar quando uma aba for clicada e garantir primeira semana para calendário
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        const originalOnclick = button.getAttribute('onclick');
        if (originalOnclick) {
            button.addEventListener('click', function() {
                // Se for a aba calendário e não há parâmetro de semana, garantir primeira semana
                if (originalOnclick.includes('calendario')) {
                    const url = new URL(window.location);
                    if (!url.searchParams.get('semana_calendario')) {
                        url.searchParams.set('semana_calendario', '0');
                        window.history.replaceState({}, '', url);
                    }
                }
                const match = originalOnclick.match(/'([^']+)'/);
                if (match && match[1]) {
                    const tabName = match[1];
                    localStorage.setItem('turma-tab-active', tabName);
                }
            });
        }
    });
});
</script>

<!-- Modal para Inserir Alunos -->
<div id="modalInserirAlunos" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="max-width: 800px; max-height: 90vh;">
        <div class="modal-header">
            <h3>
                <i class="fas fa-user-plus"></i>
                Matricular Alunos na Turma
            </h3>
            <button type="button" class="btn-close" onclick="fecharModalInserirAlunos()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Critério de Seleção:</strong> Apenas alunos com exames médico e psicotécnico aprovados serão exibidos.
            </div>
            
            <div id="loadingAlunos" style="display: none; text-align: center; padding: 20px;">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p>Carregando alunos aptos...</p>
            </div>
            
            <div id="listaAlunosAptos">
                <!-- Lista de alunos será carregada aqui -->
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="fecharModalInserirAlunos()">
                <i class="fas fa-times"></i>
                Fechar
            </button>
        </div>
    </div>
</div>

<!-- Modal para Agendar Nova Aula -->
<div id="modalAgendarAula" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="max-width: 600px; max-height: 90vh;">
        <div class="modal-header">
            <h3>
                <i class="fas fa-calendar-plus"></i>
                📅 Agendar Nova Aula
            </h3>
            <button type="button" class="btn-close" onclick="fecharModalAgendarAula()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <form id="formAgendarAulaModal">
                <input type="hidden" name="acao" value="agendar_aula">
                <input type="hidden" name="ajax" value="true">
                <input type="hidden" name="turma_id" id="modal_turma_id" value="<?= $turmaId ?>">
                <input type="hidden" name="disciplina" id="modal_disciplina_id">
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="modal_disciplina_nome" style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">
                        Disciplina *
                    </label>
                    <input type="text" 
                           id="modal_disciplina_nome" 
                           class="form-control" 
                           readonly 
                           style="background-color: #f8f9fa; cursor: not-allowed;">
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="modal_instrutor_id" style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">
                        Instrutor *
                    </label>
                    <select id="modal_instrutor_id" name="instrutor_id" class="form-control" required>
                        <option value="">Selecione um instrutor...</option>
                        <?php if (!empty($instrutores)): ?>
                            <?php foreach ($instrutores as $instrutor): ?>
                                <option value="<?= (int)$instrutor['id'] ?>">
                                    <?= htmlspecialchars($instrutor['nome'] ?? 'Instrutor sem nome') ?>
                                    <?php if (!empty($instrutor['categoria_habilitacao'])): ?>
                                        - <?= htmlspecialchars($instrutor['categoria_habilitacao']) ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>Nenhum instrutor disponível</option>
                        <?php endif; ?>
                    </select>
                    <?php if (empty($instrutores)): ?>
                        <small class="text-danger" style="display: block; margin-top: 5px;">
                            <i class="fas fa-exclamation-triangle"></i> Nenhum instrutor ativo encontrado. Verifique o cadastro de instrutores.
                        </small>
                    <?php endif; ?>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label for="modal_data_aula" style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">
                            Data da Aula *
                        </label>
                        <input type="date" 
                               id="modal_data_aula" 
                               name="data_aula" 
                               class="form-control" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="modal_hora_inicio" style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">
                            Horário de Início *
                        </label>
                        <input type="time"
                               id="modal_hora_inicio"
                               name="hora_inicio"
                               class="form-control"
                               placeholder="HH:MM"
                               step="60"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="modal_quantidade_aulas" style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">
                            Qtd Aulas *
                        </label>
                        <select id="modal_quantidade_aulas" name="quantidade_aulas" class="form-control" required>
                            <option value="1">1 aula</option>
                            <option value="2" selected>2 aulas</option>
                            <option value="3">3 aulas</option>
                            <option value="4">4 aulas</option>
                            <option value="5">5 aulas</option>
                        </select>
                    </div>
                </div>
                
                <!-- Preview do horário -->
                <div id="previewHorarioModal" style="background: #e7f3ff; padding: 15px; border-radius: 8px; margin-bottom: 15px; display: none;">
                    <strong>🕐 Preview do Agendamento:</strong>
                    <div id="previewContentModal" style="margin-top: 8px; font-family: monospace;"></div>
                </div>
                
                <!-- Alerta de conflitos -->
                <div id="alertaConflitosModal" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 15px; display: none;">
                    <strong>⚠️ Conflito Detectado:</strong>
                    <div id="conflitosContentModal" style="margin-top: 8px;"></div>
                </div>
                
                <!-- Mensagem de erro/sucesso -->
                <div id="mensagemAgendamento" style="display: none; padding: 12px; border-radius: 6px; margin-bottom: 15px;"></div>
            </form>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="fecharModalAgendarAula()">
                <i class="fas fa-times"></i>
                Cancelar
            </button>
            <button type="button" id="btnVerificarDisponibilidade" class="btn btn-outline-secondary" onclick="verificarDisponibilidadeModal()">
                <i class="fas fa-search"></i>
                🔍 Verificar Disponibilidade
            </button>
            <button type="button" id="btnAgendarAula" class="btn btn-primary" onclick="enviarAgendamentoModal()" disabled>
                <i class="fas fa-plus"></i>
                ➕ Agendar Aula(s)
            </button>
        </div>
    </div>
</div>


<script>
// ==========================================
// VERIFICAÇÃO E FALLBACK PARA ÍCONES
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    // Verificar se FontAwesome está carregado
    const testIcon = document.createElement('i');
    testIcon.className = 'fas fa-edit';
    testIcon.style.position = 'absolute';
    testIcon.style.left = '-9999px';
    document.body.appendChild(testIcon);
    
    const computedStyle = window.getComputedStyle(testIcon);
    const fontFamily = computedStyle.getPropertyValue('font-family');
    
    // Se FontAwesome não estiver carregado, mostrar fallback
    if (!fontFamily.includes('Font Awesome')) {
        console.log('FontAwesome não detectado, usando fallback Unicode');
        const editButtons = document.querySelectorAll('.btn-outline-primary i.fa-edit');
        editButtons.forEach(function(icon) {
            const fallback = icon.nextElementSibling;
            if (fallback && fallback.tagName === 'SPAN') {
                icon.style.display = 'none';
                fallback.style.display = 'inline';
            }
        });
    }
    
    document.body.removeChild(testIcon);
});

/**
 * ==========================================
 * SISTEMA DE DISCIPLINAS - PÁGINA DE DETALHES
 * Sistema CFC Bom Conselho
 * Versão: <?= time() ?>
 * ==========================================
 */

// ==========================================
// VARIÁVEIS GLOBAIS
// ==========================================
if (typeof contadorDisciplinasDetalhes === 'undefined') { var contadorDisciplinasDetalhes = 1; }
if (typeof disciplinasDisponiveis === 'undefined') { var disciplinasDisponiveis = []; }
if (typeof originalValues === 'undefined') { var originalValues = {}; }
if (typeof autoSaveFlags === 'undefined') { var autoSaveFlags = {}; }

// ==========================================
// FUNÇÃO DE DETECÇÃO DE CAMINHO BASE
// ==========================================
function getBasePath() {
    // Detectar automaticamente o caminho base baseado na URL atual
    const currentPath = window.location.pathname;
    
    console.log('🔧 [DEBUG] getBasePath - currentPath:', currentPath);
    
    if (currentPath.includes('/cfc-bom-conselho/')) {
        console.log('🔧 [DEBUG] getBasePath - retornando /cfc-bom-conselho');
        return '/cfc-bom-conselho';
    } else if (currentPath.includes('/admin/')) {
        console.log('🔧 [DEBUG] getBasePath - retornando string vazia');
        return '';
    } else {
        // Fallback: tentar detectar baseado no host
        const host = window.location.host;
        console.log('🔧 [DEBUG] getBasePath - host:', host);
        
        if (host.includes('localhost') || host.includes('127.0.0.1')) {
            console.log('🔧 [DEBUG] getBasePath - retornando string vazia (localhost)');
            return '';
        } else {
            console.log('🔧 [DEBUG] getBasePath - retornando /cfc-bom-conselho (produção)');
            return '/cfc-bom-conselho';
        }
    }
}

// Constante para o caminho base das APIs
const API_BASE_PATH = getBasePath();
console.log('🔧 [CONFIG] Caminho base detectado:', API_BASE_PATH);

// ==========================================
// FUNÇÕES PRINCIPAIS - DISCIPLINAS
// ==========================================

// Carregar disciplinas disponíveis em todos os selects
function carregarDisciplinasDisponiveis() {
    console.log('📚 [DISCIPLINAS] Carregando disciplinas disponíveis...');
    
    // Usar a mesma API do cadastro
    return fetch(API_BASE_PATH + '/admin/api/disciplinas-clean.php?acao=listar')
        .then(response => {
            console.log('📡 [API] Resposta recebida:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.text();
        })
        .then(text => {
            console.log('📄 [API] Resposta bruta:', text);
            
            try {
                const data = JSON.parse(text);
                console.log('📊 [API] Dados parseados:', data);
                
                // Validação robusta - mesma estrutura do cadastro
                if (data && data.sucesso && data.disciplinas) {
                    if (Array.isArray(data.disciplinas)) {
                        console.log('✅ [API] Disciplinas é um array válido com', data.disciplinas.length, 'itens');
                        
                        // Carregar em todos os selects existentes (formulários de edição)
                        const selects = document.querySelectorAll('.disciplina-item select, .disciplina-edit-form select');
                        console.log('🔍 [SELECTS] Encontrados selects:', selects.length);
                        
                        selects.forEach((select, index) => {
                            console.log(`📝 [SELECT ${index}] Processando select...`);
                            
                            // Limpar opções existentes
                            select.innerHTML = '<option value="">Selecione a disciplina...</option>';
                            
                            // Adicionar disciplinas uma por uma - mesma estrutura do cadastro
                            data.disciplinas.forEach((disciplina, discIndex) => {
                                if (disciplina && disciplina.id && disciplina.nome) {
                                    const option = document.createElement('option');
                                    option.value = disciplina.id;
                                    option.textContent = disciplina.nome;
                                    option.dataset.aulas = disciplina.carga_horaria_padrao || 10;
                                    option.dataset.cor = '#007bff';
                                    select.appendChild(option);
                                } else {
                                    console.warn(`⚠️ [SELECT ${index}] Item inválido:`, disciplina);
                                }
                            });
                            
                            console.log(`✅ [SELECT ${index}] Disciplinas adicionadas:`, data.disciplinas.length);
                        });
                        
                        // Selecionar disciplinas já cadastradas
                        // TEMPORARIAMENTE DESABILITADO PARA TESTE
                        // selecionarDisciplinasCadastradas();
                        
                        console.log('✅ [DISCIPLINAS] Disciplinas carregadas com sucesso:', data.disciplinas.length);
                        return data.disciplinas;
                    } else {
                        console.error('❌ [API] Disciplinas não é um array:', typeof data.disciplinas, data.disciplinas);
                        throw new Error('Disciplinas não é um array');
                    }
                } else {
                    console.error('❌ [API] Estrutura de dados inválida:', data);
                    throw new Error('Estrutura de dados inválida');
                }
            } catch (parseError) {
                console.error('❌ [API] Erro ao fazer parse do JSON:', parseError);
                console.error('❌ [API] Texto recebido:', text);
                throw parseError;
            }
        })
        .catch(error => {
            console.error('❌ [DISCIPLINAS] Erro na requisição:', error);
            throw error;
        });
}

// Selecionar disciplinas já cadastradas
function selecionarDisciplinasCadastradas() {
    console.log('🎯 [DISCIPLINAS] Selecionando disciplinas cadastradas...');
    
    // Aguardar um pouco para garantir que as opções foram carregadas
    setTimeout(() => {
        const disciplinaItems = document.querySelectorAll('.disciplina-item[data-disciplina-cadastrada]');
        console.log('🔍 [DISCIPLINAS] Encontrados', disciplinaItems.length, 'itens de disciplina');
        
        disciplinaItems.forEach((item, index) => {
            const disciplinaIdCadastrada = item.getAttribute('data-disciplina-cadastrada');
            const select = item.querySelector('select');
            
            console.log(`🔍 [ITEM ${index}] Disciplina ID cadastrada:`, disciplinaIdCadastrada);
            console.log(`🔍 [ITEM ${index}] Select encontrado:`, !!select);
            console.log(`🔍 [ITEM ${index}] Opções no select:`, select ? select.options.length : 0);
            
            // Pular itens com ID inválido
            if (!disciplinaIdCadastrada || disciplinaIdCadastrada === '0' || disciplinaIdCadastrada === 'null') {
                console.log(`⚠️ [ITEM ${index}] ID inválido ignorado:`, disciplinaIdCadastrada);
                return;
            }
            
            if (select && disciplinaIdCadastrada && disciplinaIdCadastrada !== '0') {
                // Verificar se a opção existe
                const optionExists = Array.from(select.options).some(option => option.value === disciplinaIdCadastrada);
                console.log(`🔍 [ITEM ${index}] Opção existe:`, optionExists);
                
                if (optionExists) {
                    // Selecionar a disciplina cadastrada
                    select.value = disciplinaIdCadastrada;
                    console.log(`✅ [ITEM ${index}] Disciplina selecionada:`, disciplinaIdCadastrada);
                    
                    // Atualizar display da disciplina - usar o índice correto do data-disciplina-id
                    const disciplinaId = item.getAttribute('data-disciplina-id');
                    if (disciplinaId && disciplinaId !== '0' && disciplinaId !== 'null' && disciplinaId !== 'undefined') {
                        const disciplinaIdInt = parseInt(disciplinaId);
                        if (!isNaN(disciplinaIdInt) && disciplinaIdInt > 0) {
                            atualizarDisciplinaDetalhes(disciplinaIdInt);
                        }
                    }
                } else {
                    console.warn(`⚠️ [ITEM ${index}] Opção não encontrada para disciplina:`, disciplinaIdCadastrada);
                    console.warn(`⚠️ [ITEM ${index}] Opções disponíveis:`, Array.from(select.options).map(opt => opt.value));
                }
            }
        });
        
        // Atualizar contador
        atualizarContadorDisciplinasDetalhes();
    }, 500); // Aumentar o tempo para 500ms para garantir que as opções foram carregadas
}

// Atualizar disciplina (igual ao cadastro)
function atualizarDisciplinaDetalhes(disciplinaId) {
    console.log('🔄 [DISCIPLINA] Atualizando disciplina:', disciplinaId, 'tipo:', typeof disciplinaId);
    
    // Garantir que disciplinaId é um número válido
    disciplinaId = parseInt(disciplinaId);
    if (isNaN(disciplinaId) || disciplinaId <= 0) {
        console.error('❌ [DISCIPLINA] ID da disciplina inválido:', disciplinaId, 'tipo:', typeof disciplinaId);
        console.error('❌ [DISCIPLINA] Stack trace:', new Error().stack);
        return;
    }
    
    const disciplinaSelect = document.querySelector(`[data-disciplina-id="${disciplinaId}"] select`);
    if (!disciplinaSelect) return;
    
    const disciplinaItem = disciplinaSelect.closest('.disciplina-item');
    if (!disciplinaItem) {
        console.warn('⚠️ [DISCIPLINA] Item de disciplina não encontrado para:', disciplinaId);
        return;
    }
    
    const selectedOption = disciplinaSelect.options[disciplinaSelect.selectedIndex];
    const infoElement = disciplinaItem.querySelector('.disciplina-info');
    const horasInput = disciplinaItem.querySelector('.disciplina-horas');
    const horasGroup = disciplinaItem.querySelector('.input-group');
    const horasLabel = disciplinaItem.querySelector('.disciplina-info');
    
    console.log('🔍 [DISCIPLINA] Elementos encontrados:', {
        disciplinaSelect: !!disciplinaSelect,
        disciplinaItem: !!disciplinaItem,
        selectedOption: !!selectedOption,
        infoElement: !!infoElement,
        horasInput: !!horasInput,
        horasGroup: !!horasGroup,
        horasLabel: !!horasLabel
    });
    
    if (selectedOption.value) {
        const aulas = selectedOption.dataset.aulas || '0';
        const cor = selectedOption.dataset.cor || '#007bff';
        
        // Mostrar informações
        if (infoElement) {
            infoElement.style.display = 'block';
            const aulasElement = infoElement.querySelector('.aulas-obrigatorias');
            if (aulasElement) {
                aulasElement.textContent = aulas;
            }
        }
        
        // Mostrar campo de horas e configurar valor padrão
        if (horasInput && horasGroup && horasLabel) {
            horasInput.value = aulas;
            horasInput.style.display = 'block';
            horasGroup.style.display = 'flex';
            horasLabel.style.display = 'block';
        }
        
        // Mostrar botão de excluir para todas as disciplinas (não apenas ID 0)
        const deleteBtn = disciplinaItem.querySelector('.disciplina-delete-btn');
        if (deleteBtn) {
            deleteBtn.style.display = 'flex';
        }
        
        // Aplicar cor da disciplina
        disciplinaItem.style.borderLeft = '4px solid ' + cor;
        
        console.log('✅ [DISCIPLINA] Disciplina selecionada:', selectedOption.textContent, '(' + aulas + ' aulas padrão)');

        // Auto-save: salva imediatamente a seleção desta disciplina (somente em itens dinâmicos)
        try {
            if (!autoSaveFlags[disciplinaId]) {
                autoSaveFlags[disciplinaId] = true; // evita múltiplos envios
                const cargaToSave = (horasInput && horasInput.value) ? horasInput.value : aulas;
                console.log('💾 [AUTO-SAVE DISCIPLINA] Enviando add_disciplina:', { disciplinaIdSelecionada: selectedOption.value, cargaToSave });
                fetch(API_BASE_PATH + '/admin/api/turmas-teoricas-inline.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'add_disciplina',
                        turma_id: <?= $turmaId ?>,
                        disciplina_id: selectedOption.value,
                        carga_horaria: cargaToSave
                    })
                })
                .then(response => {
                    if (!response.ok) { throw new Error(`HTTP ${response.status}: ${response.statusText}`); }
                    return response.text();
                })
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            showFeedback('Disciplina adicionada e salva automaticamente!', 'success');
                            // Não recarregar página - manter interface atual
                        } else {
                            autoSaveFlags[disciplinaId] = false;
                            showFeedback('Erro ao salvar disciplina: ' + (data.message || 'Tente novamente'), 'error');
                        }
                    } catch (e) {
                        autoSaveFlags[disciplinaId] = false;
                        console.error('❌ [AUTO-SAVE DISCIPLINA] Resposta inválida:', text);
                        showFeedback('Erro: Resposta inválida do servidor', 'error');
                    }
                })
                .catch(err => {
                    autoSaveFlags[disciplinaId] = false;
                    console.error('❌ [AUTO-SAVE DISCIPLINA] Erro:', err);
                    showFeedback('Erro ao salvar disciplina: ' + err.message, 'error');
                });
            }
        } catch (e) {
            autoSaveFlags[disciplinaId] = false;
            console.error('❌ [AUTO-SAVE DISCIPLINA] Exceção:', e);
        }
    } else {
        // Esconder informações
        if (infoElement) {
            infoElement.style.display = 'none';
        }
        
        // Esconder campo de horas
        if (horasInput && horasGroup && horasLabel) {
            horasInput.style.display = 'none';
            horasGroup.style.display = 'none';
            horasLabel.style.display = 'none';
            horasInput.value = '';
        }
        
        // Esconder botão de excluir para disciplina principal (ID 0) quando não há seleção
        if (disciplinaId === 0) {
            const deleteBtn = disciplinaItem.querySelector('.disciplina-delete-btn');
            if (deleteBtn) {
                deleteBtn.style.display = 'none';
            }
        }
        
        disciplinaItem.style.borderLeft = '';
    }
    
    // Atualizar contador
    atualizarContadorDisciplinasDetalhes();
}

// Adicionar disciplina adicional
function adicionarDisciplinaDetalhes() {
    console.log('➕ [DISCIPLINA] Adicionando disciplina adicional...');
    
    const container = document.getElementById('disciplinas-container-detalhes');
    if (!container) {
        console.error('❌ [DISCIPLINA] Container não encontrado!');
        return;
    }
    
    const disciplinaHtml = `
        <div class="disciplina-item border rounded p-3 mb-3" data-disciplina-id="${contadorDisciplinasDetalhes}">
            <div class="d-flex align-items-center gap-3 disciplina-row-layout">
                <div class="flex-grow-1 disciplina-field-container">
                    <select class="form-select" name="disciplina_${contadorDisciplinasDetalhes}" onchange="atualizarDisciplinaDetalhes(${contadorDisciplinasDetalhes})">
                        <option value="">Selecione a disciplina...</option>
                    </select>
                </div>
                <div class="flex-shrink-0">
                </div>
            </div>
            
            <!-- Campos ocultos para informações adicionais -->
            <div style="display: none;">
                <div class="input-group mt-2">
                    <input type="number" class="form-control disciplina-horas" 
                           name="disciplina_horas_${contadorDisciplinasDetalhes}" 
                           placeholder="Horas" 
                           min="1" 
                           max="50">
                    <span class="input-group-text">h</span>
                </div>
                <small class="text-muted disciplina-info">
                    <span class="aulas-obrigatorias"></span> aulas (padrão)
                </small>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', disciplinaHtml);
    
    // Carregar disciplinas no novo select com fallback robusto
    carregarDisciplinasNoSelect(contadorDisciplinasDetalhes);
    setTimeout(() => {
        const selectNovo = document.querySelector(`[data-disciplina-id="${contadorDisciplinasDetalhes}"] select`);
        if (selectNovo && selectNovo.options.length <= 1) {
            console.warn('⚠️ [DISCIPLINAS] Recarregando opções do select (fallback)');
            carregarDisciplinasNoSelect(contadorDisciplinasDetalhes);
        }
    }, 800);
    
    contadorDisciplinasDetalhes++;
    atualizarContadorDisciplinasDetalhes();
    
    console.log('✅ [DISCIPLINA] Disciplina adicional adicionada');
}

// Função SIMPLES e DIRETA para carregar disciplinas - SEMPRE funciona
function carregarDisciplinasSimples() {
    console.log('🚀 [SIMPLES] Carregando disciplinas de forma simples e direta...');
    
    fetch(API_BASE_PATH + '/admin/api/disciplinas-clean.php?acao=listar')
        .then(response => response.json())
        .then(data => {
            if (data && data.sucesso && data.disciplinas) {
                console.log('✅ [SIMPLES] Dados recebidos:', data.disciplinas.length, 'disciplinas');
                
                // Buscar TODOS os selects
                const selects = document.querySelectorAll('select');
                console.log('🔍 [SIMPLES] Encontrados', selects.length, 'selects');
                
                selects.forEach((select, index) => {
                    console.log(`🔍 [SIMPLES] Processando select ${index + 1}:`, select.name || select.id);
                    
                    // Limpar e adicionar disciplinas
                    select.innerHTML = '<option value="">Selecione a disciplina...</option>';
                    
                    data.disciplinas.forEach(disciplina => {
                        if (disciplina && disciplina.id && disciplina.nome && disciplina.id !== '0' && disciplina.id !== 0) {
                            const option = document.createElement('option');
                            option.value = disciplina.id;
                            option.textContent = disciplina.nome;
                            select.appendChild(option);
                        }
                    });
                    
                    console.log(`✅ [SIMPLES] Select ${index + 1} populado com ${data.disciplinas.length} disciplinas`);
                });
                
                console.log('✅ [SIMPLES] TODOS os selects foram populados!');
            } else {
                console.error('❌ [SIMPLES] Dados inválidos:', data);
            }
        })
        .catch(error => {
            console.error('❌ [SIMPLES] Erro:', error);
        });
}

// Função que FORÇA o carregamento de todas as disciplinas em TODOS os selects
function forcarCarregamentoDisciplinas() {
    console.log('🚀 [FORÇA] Carregando TODAS as disciplinas em TODOS os selects...');
    
    return fetch(API_BASE_PATH + '/admin/api/disciplinas-clean.php?acao=listar')
        .then(response => {
            console.log('📡 [FORÇA] Resposta da API:', response.status);
            return response.json();
        })
        .then(data => {
            if (data && data.sucesso && data.disciplinas) {
                console.log('✅ [FORÇA] Dados recebidos:', data.disciplinas.length, 'disciplinas');
                console.log('📋 [FORÇA] Disciplinas:', data.disciplinas.map(d => d.nome));
                
                // Buscar TODOS os selects na página
                const allSelects = document.querySelectorAll('select');
                console.log('🔍 [FORÇA] Encontrados', allSelects.length, 'selects na página');
                
                allSelects.forEach((select, index) => {
                    console.log(`🔍 [FORÇA] Processando select ${index + 1}:`, {
                        name: select.name,
                        id: select.id,
                        className: select.className,
                        currentOptions: select.options.length
                    });
                    
                    // Limpar opções existentes
                    select.innerHTML = '<option value="">Selecione a disciplina...</option>';
                    
                    // Adicionar TODAS as disciplinas
                    data.disciplinas.forEach((disciplina, discIndex) => {
                        if (disciplina && disciplina.id && disciplina.nome && disciplina.id !== '0' && disciplina.id !== 0) {
                            const option = document.createElement('option');
                            option.value = disciplina.id;
                            option.textContent = disciplina.nome;
                            option.dataset.aulas = disciplina.carga_horaria_padrao || 10;
                            option.dataset.cor = '#007bff';
                            select.appendChild(option);
                            console.log(`📝 [FORÇA] Adicionada disciplina ${discIndex + 1}: ${disciplina.nome} (ID: ${disciplina.id})`);
                        }
                    });
                    
                    console.log(`✅ [FORÇA] Select ${index + 1} populado com ${data.disciplinas.length} disciplinas`);
                });
                
                return data.disciplinas;
            } else {
                throw new Error('Dados inválidos da API: ' + JSON.stringify(data));
            }
        })
        .catch(error => {
            console.error('❌ [FORÇA] Erro ao carregar disciplinas:', error);
            throw error;
        });
}

// Função de fallback para carregar disciplinas quando a função principal falha
function carregarDisciplinasFallback(disciplinaId) {
    console.log('🔄 [FALLBACK] Usando método de força para:', disciplinaId);
    return forcarCarregamentoDisciplinas();
}

// Carregar disciplinas em um select específico
function carregarDisciplinasNoSelect(disciplinaId) {
    console.log('📚 [DISCIPLINA] Carregando disciplinas para select:', disciplinaId);
    
    // Usar a mesma API do cadastro
    return fetch(API_BASE_PATH + '/admin/api/disciplinas-clean.php?acao=listar')
        .then(response => {
            console.log('📡 [API] Resposta recebida:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.text();
        })
        .then(text => {
            console.log('📄 [API] Resposta bruta:', text);
            
            try {
                const data = JSON.parse(text);
                console.log('📊 [API] Dados parseados:', data);
                
                // Validação robusta - mesma estrutura do cadastro
                if (data && data.sucesso && data.disciplinas) {
                    if (Array.isArray(data.disciplinas)) {
                        console.log('✅ [API] Disciplinas é um array válido com', data.disciplinas.length, 'itens');
                        
                        let select;
                        if (disciplinaId === 'nova') {
                            // Elemento não existe nesta página - pular
                            console.log('ℹ️ [SELECT] Elemento nova_disciplina_select não existe nesta página');
                            return Promise.resolve([]);
                        } else if (disciplinaId === '0' || disciplinaId === 0 || disciplinaId === 'null' || disciplinaId === 'undefined') {
                            // ID inválido - pular
                            console.log('ℹ️ [SELECT] ID de disciplina inválido ignorado:', disciplinaId);
                            return Promise.resolve([]);
                        } else {
                            // Buscar o select de forma mais robusta com múltiplos fallbacks
                            console.log('🔍 [SELECT] Buscando select para disciplina:', disciplinaId);
                            
                            // Tentar diferentes estratégias de busca
                            const selectors = [
                                // 1. Formulário de edição específico
                                `.disciplina-edit-form select[name="disciplina_edit_${disciplinaId}"]`,
                                // 2. Qualquer select no card da disciplina
                                `[data-disciplina-id="${disciplinaId}"] select`,
                                // 3. Select dentro de qualquer formulário de edição
                                `[data-disciplina-id="${disciplinaId}"] .disciplina-edit-form select`,
                                // 4. Select com name específico
                                `select[name="disciplina_edit_${disciplinaId}"]`,
                                // 5. Qualquer select que contenha o ID da disciplina
                                `select[name*="${disciplinaId}"]`
                            ];
                            
                            for (let i = 0; i < selectors.length; i++) {
                                select = document.querySelector(selectors[i]);
                                if (select) {
                                    console.log(`✅ [SELECT] Encontrado com seletor ${i + 1}:`, selectors[i]);
                                    break;
                                } else {
                                    console.log(`❌ [SELECT] Seletor ${i + 1} falhou:`, selectors[i]);
                                }
                            }
                            
                            // Se ainda não encontrou, buscar por qualquer select visível
                            if (!select) {
                                console.log('🔍 [SELECT] Tentando busca por qualquer select visível...');
                                const allSelects = document.querySelectorAll('select');
                                for (let s of allSelects) {
                                    if (s.style.display !== 'none' && s.offsetParent !== null) {
                                        console.log('🔍 [SELECT] Select visível encontrado:', s.name, s.className);
                                        // Verificar se está relacionado à disciplina atual
                                        const parentCard = s.closest('[data-disciplina-id]');
                                        if (parentCard && parentCard.getAttribute('data-disciplina-id') === disciplinaId.toString()) {
                                            select = s;
                                            console.log('✅ [SELECT] Select relacionado encontrado!');
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                        
                        if (select) {
                            console.log('✅ [SELECT] Select encontrado, adicionando disciplinas...');
                            
                            // Limpar opções existentes
                            select.innerHTML = '<option value="">Selecione a disciplina...</option>';
                            
                            // Adicionar disciplinas uma por uma - mesma estrutura do cadastro
                            data.disciplinas.forEach((disciplina, index) => {
                                if (disciplina && disciplina.id && disciplina.nome && disciplina.id !== '0' && disciplina.id !== 0) {
                                    const option = document.createElement('option');
                                    option.value = disciplina.id;
                                    option.textContent = disciplina.nome;
                                    option.dataset.aulas = disciplina.carga_horaria_padrao || 10;
                                    option.dataset.cor = '#007bff';
                                    select.appendChild(option);
                                    console.log(`📝 [DISCIPLINA ${index + 1}] Adicionada: ${disciplina.nome}`);
                                } else {
                                    console.warn('⚠️ [DISCIPLINA] Item inválido:', disciplina);
                                }
                            });
                            
                            console.log('✅ [SELECT] Disciplinas adicionadas com sucesso');
                            return Promise.resolve(data.disciplinas);
                        } else {
                            console.error('❌ [SELECT] Select não encontrado para disciplina:', disciplinaId);
                            console.log('🔄 [SELECT] Tentando método de fallback...');
                            return carregarDisciplinasFallback(disciplinaId);
                        }
                    } else {
                        console.error('❌ [API] Disciplinas não é um array:', typeof data.disciplinas, data.disciplinas);
                        return Promise.reject('Disciplinas não é um array');
                    }
                } else {
                    console.error('❌ [API] Estrutura de dados inválida:', data);
                    return Promise.reject('Estrutura de dados inválida');
                }
            } catch (parseError) {
                console.error('❌ [API] Erro ao fazer parse do JSON:', parseError);
                console.error('❌ [API] Texto recebido:', text);
                return Promise.reject(parseError);
            }
        })
        .catch(error => {
            console.error('❌ [DISCIPLINA] Erro na requisição:', error);
            return Promise.reject(error);
        });
}


// Atualizar contador de disciplinas
function atualizarContadorDisciplinasDetalhes() {
    const disciplinas = document.querySelectorAll('.disciplina-item select, .disciplina-card, .disciplina-accordion');
    let disciplinasSelecionadas = 0;
    
    disciplinas.forEach(element => {
        if (element.classList.contains('disciplina-card') || element.classList.contains('disciplina-accordion')) {
            // Para cards, verificar se tem disciplina cadastrada
            const disciplinaId = element.getAttribute('data-disciplina-cadastrada') || element.getAttribute('data-disciplina-id');
            if (disciplinaId && disciplinaId !== '0' && disciplinaId !== 'null' && disciplinaId !== 'undefined') {
                const disciplinaIdInt = parseInt(disciplinaId);
                if (!isNaN(disciplinaIdInt) && disciplinaIdInt > 0) {
                    disciplinasSelecionadas++;
                }
            }
        } else {
            // Para selects, verificar se tem valor selecionado
            if (element.value && element.value !== '0' && element.value !== 'null' && element.value !== 'undefined') {
                const valueInt = parseInt(element.value);
                if (!isNaN(valueInt) && valueInt > 0) {
                    disciplinasSelecionadas++;
                }
            }
        }
    });
    
    const contador = document.getElementById('contador-disciplinas-detalhes');
    if (contador) {
        contador.textContent = disciplinasSelecionadas;
    }
    
    console.log('📊 [CONTADOR] Disciplinas selecionadas:', disciplinasSelecionadas);
    
    // Mostrar mensagem se não há disciplinas
    if (disciplinasSelecionadas === 0) {
        console.log('ℹ️ [CONTADOR] Nenhuma disciplina selecionada');
    }
}

// Alternar entre visualização e edição de disciplina
function toggleEditDisciplina(disciplinaId) {
    console.log('🔄 [EDIT] Alternando modo de edição para disciplina:', disciplinaId);
    
    const disciplinaCard = document.querySelector(`[data-disciplina-id="${disciplinaId}"]`);
    if (!disciplinaCard) return;
    
    const editFields = disciplinaCard.querySelector('.disciplina-edit-fields');
    const editButton = disciplinaCard.querySelector('.btn-edit-disciplina');
    
    if (editFields.style.display === 'none' || !editFields.style.display) {
        // Mostrar campos de edição
        editFields.style.display = 'block';
        editButton.innerHTML = '<i class="fas fa-save"></i> Salvar';
        editButton.classList.add('btn-save-mode');
        
        // Carregar disciplinas no select se ainda não foram carregadas
        const select = editFields.querySelector('select');
        if (select && select.options.length <= 1) {
            carregarDisciplinasNoSelect(disciplinaId);
        }
        
        console.log('✅ [EDIT] Modo de edição ativado');
    } else {
        // Ocultar campos de edição
        editFields.style.display = 'none';
        editButton.innerHTML = '<i class="fas fa-edit"></i> Editar';
        editButton.classList.remove('btn-save-mode');
        
        console.log('✅ [EDIT] Modo de visualização ativado');
    }
}

// Funções de edição de disciplinas removidas - disciplinas são automáticas baseadas no tipo de curso

// Funções de edição de disciplinas removidas - disciplinas são automáticas baseadas no tipo de curso

// Funções de edição de disciplinas removidas - disciplinas são automáticas baseadas no tipo de curso

// Funções de adição de disciplinas removidas - disciplinas são automáticas baseadas no tipo de curso

// Funções de adição de disciplinas removidas - disciplinas são automáticas baseadas no tipo de curso

// Funções de adição de disciplinas removidas - disciplinas são automáticas baseadas no tipo de curso

// ==========================================
// SISTEMA DE RELATÓRIO DETALHADO DE DISCIPLINAS
// ==========================================

// Função de teste simples
function testeSimples(disciplinaId) {
    console.log('🧪 [TESTE] Função testeSimples chamada com:', disciplinaId);
    alert('Teste funcionando! ID: ' + disciplinaId);
}

// Função SIMPLES para alternar sanfona
function toggleSimples(disciplinaId) {
    console.log('🔄 [SIMPLES] ===== INÍCIO DA FUNÇÃO =====');
    console.log('🔄 [SIMPLES] Alternando disciplina:', disciplinaId);
    console.log('🔄 [SIMPLES] Tipo do ID:', typeof disciplinaId);
    
    // Verificar se o ID é válido
    if (!disciplinaId) {
        console.error('❌ [SIMPLES] ID da disciplina é vazio ou nulo');
        return;
    }
    
    console.log('🔍 [SIMPLES] Buscando elementos...');
    const disciplinaCard = document.querySelector(`[data-disciplina-id="${disciplinaId}"]`);
    const detalhesContent = document.getElementById(`detalhes-disciplina-${disciplinaId}`);
    
    console.log('🔍 [SIMPLES] Card encontrado:', !!disciplinaCard);
    console.log('🔍 [SIMPLES] Conteúdo encontrado:', !!detalhesContent);
    
    if (!disciplinaCard) {
        console.error('❌ [SIMPLES] Card da disciplina não encontrado para ID:', disciplinaId);
        console.error('❌ [SIMPLES] Tentando buscar todos os cards...');
        const todosCards = document.querySelectorAll('[data-disciplina-id]');
        console.log('❌ [SIMPLES] Total de cards encontrados:', todosCards.length);
        todosCards.forEach((card, index) => {
            console.log(`❌ [SIMPLES] Card ${index + 1}: data-disciplina-id="${card.getAttribute('data-disciplina-id')}"`);
        });
        return;
    }
    
    if (!detalhesContent) {
        console.error('❌ [SIMPLES] Conteúdo da sanfona não encontrado para ID:', disciplinaId);
        return;
    }
    
    console.log('✅ [SIMPLES] Elementos encontrados, verificando estado...');
    const isExpanded = disciplinaCard.classList.contains('expanded');
    console.log('🔍 [SIMPLES] Sanfona expandida:', isExpanded);
    
    if (isExpanded) {
        // Fechar
        console.log('🔽 [SIMPLES] Fechando sanfona...');
        disciplinaCard.classList.remove('expanded');
        detalhesContent.style.display = 'none';
        console.log('✅ [SIMPLES] Sanfona fechada');
    } else {
        // Abrir
        console.log('🔼 [SIMPLES] Abrindo sanfona...');
        disciplinaCard.classList.add('expanded');
        detalhesContent.style.display = 'block';
        
        // Mostrar conteúdo simples
        const dataElement = document.getElementById(`data-disciplina-${disciplinaId}`);
        console.log('🔍 [SIMPLES] Elemento de dados encontrado:', !!dataElement);
        
        if (dataElement) {
            console.log('✅ [SIMPLES] Dados já carregados via PHP para disciplina:', disciplinaId);
        } else {
            console.error('❌ [SIMPLES] Elemento de dados não encontrado');
        }
        
        console.log('✅ [SIMPLES] Sanfona aberta');
    }
    
    console.log('🔄 [SIMPLES] ===== FIM DA FUNÇÃO =====');
}

/**
 * Alternar exibição dos detalhes da disciplina (sanfona)
 * @param {number} disciplinaId - ID da disciplina
 */
function toggleDisciplinaDetalhes(disciplinaId) {
    console.log('🔄 [SANFONA] Alternando disciplina:', disciplinaId, 'tipo:', typeof disciplinaId);
    console.log('🔄 [SANFONA] Função chamada com sucesso!');
    
    // Garantir que disciplinaId é válido (aceita tanto números quanto strings)
    const disciplinaIdInt = parseInt(disciplinaId);
    const isNumericId = !isNaN(disciplinaIdInt) && disciplinaIdInt > 0;
    const isStringId = typeof disciplinaId === 'string' && disciplinaId.trim().length > 0 && disciplinaId !== '0';
    
    if (!isNumericId && !isStringId) {
        console.error('❌ [SANFONA] ID da disciplina inválido:', disciplinaId, 'tipo:', typeof disciplinaId);
        console.error('❌ [SANFONA] Stack trace:', new Error().stack);
        return;
    }
    
    const disciplinaCard = document.querySelector(`[data-disciplina-id="${disciplinaId}"]`);
    const detalhesContent = document.getElementById(`detalhes-disciplina-${disciplinaId}`);
    
    if (!disciplinaCard || !detalhesContent) {
        console.error('❌ [SANFONA] Elementos não encontrados para disciplina:', disciplinaId);
        console.error('❌ [SANFONA] Card encontrado:', !!disciplinaCard);
        console.error('❌ [SANFONA] Conteúdo encontrado:', !!detalhesContent);
        return;
    }
    
    const chevron = disciplinaCard.querySelector('.disciplina-chevron');
    
    const isExpanded = disciplinaCard.classList.contains('expanded');
    
    if (isExpanded) {
        // Fechar sanfona
        disciplinaCard.classList.remove('expanded');
        detalhesContent.style.display = 'none';
        console.log('✅ [SANFONA] Sanfona fechada para disciplina:', disciplinaId);
    } else {
        // Abrir sanfona
        disciplinaCard.classList.add('expanded');
        detalhesContent.style.display = 'block';
        
        // Dados já estão carregados via PHP, não precisa carregar via AJAX
        console.log('✅ [SANFONA] Dados já carregados via PHP para disciplina:', disciplinaId);
        
        console.log('✅ [SANFONA] Sanfona aberta para disciplina:', disciplinaId);
    }
}

/**
 * Carregar detalhes completos da disciplina
 * @param {number} disciplinaId - ID da disciplina
 */
function carregarDetalhesDisciplina(disciplinaId) {
    console.log('📊 [DETALHES] Carregando detalhes da disciplina:', disciplinaId, 'tipo:', typeof disciplinaId);
    console.log('📊 [DETALHES] Função carregarDetalhesDisciplina chamada!');
    
    // Garantir que disciplinaId é válido (aceita tanto números quanto strings)
    const disciplinaIdInt = parseInt(disciplinaId);
    const isNumericId = !isNaN(disciplinaIdInt) && disciplinaIdInt > 0;
    const isStringId = typeof disciplinaId === 'string' && disciplinaId.trim().length > 0 && disciplinaId !== '0';
    
    if (!isNumericId && !isStringId) {
        console.error('❌ [DETALHES] ID da disciplina inválido:', disciplinaId, 'tipo:', typeof disciplinaId);
        console.error('❌ [DETALHES] Stack trace:', new Error().stack);
        return;
    }
    
    const disciplinaCard = document.querySelector(`[data-disciplina-id="${disciplinaId}"]`);
    if (!disciplinaCard) {
        console.error('❌ [DETALHES] Card da disciplina não encontrado:', disciplinaId);
        return;
    }
    
    const turmaId = disciplinaCard.getAttribute('data-turma-id');
    const loadingElement = document.getElementById(`loading-disciplina-${disciplinaId}`);
    const dataElement = document.getElementById(`data-disciplina-${disciplinaId}`);
    
    if (!turmaId) {
        console.error('❌ [DETALHES] ID da turma não encontrado');
        return;
    }
    
    // Mostrar loading
    if (loadingElement) loadingElement.style.display = 'block';
    if (dataElement) dataElement.style.display = 'none';
    
    // Buscar dados da API
    const apiUrl = `${API_BASE_PATH}/admin/api/relatorio-disciplinas.php?acao=aulas_disciplina&turma_id=${turmaId}&disciplina_id=${disciplinaId}`;
    console.log('🌐 [API] Fazendo requisição para:', apiUrl);
    console.log('🌐 [API] Parâmetros:', { turmaId, disciplinaId, tipoDisciplinaId: typeof disciplinaId });
    
    fetch(apiUrl)
        .then(response => {
            console.log('📡 [API] Resposta recebida:', response.status, response.statusText);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.text().then(text => {
                console.log('📄 [API] Resposta bruta:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('❌ [API] Erro ao fazer parse do JSON:', e);
                    console.error('❌ [API] Texto recebido:', text);
                    throw new Error('Resposta da API não é JSON válido');
                }
            });
        })
        .then(data => {
            console.log('📊 [API] Dados parseados:', data);
            
            if (data.success) {
                console.log('✅ [API] Sucesso! Renderizando detalhes...');
                renderizarDetalhesDisciplina(disciplinaId, data);
            } else {
                console.error('❌ [API] API retornou erro:', data.message);
                mostrarErroDetalhes(disciplinaId, data.message || 'Erro desconhecido');
            }
        })
        .catch(error => {
            console.error('❌ [API] Erro na requisição:', error);
            console.error('❌ [API] Stack trace:', error.stack);
            mostrarErroDetalhes(disciplinaId, error.message);
        })
        .finally(() => {
            console.log('🏁 [API] Finalizando carregamento...');
            // Esconder loading
            if (loadingElement) loadingElement.style.display = 'none';
            if (dataElement) dataElement.style.display = 'block';
        });
}

/**
 * Renderizar detalhes da disciplina na interface
 * @param {number} disciplinaId - ID da disciplina
 * @param {Object} data - Dados da disciplina
 */
function renderizarDetalhesDisciplina(disciplinaId, data) {
    console.log('🎨 [RENDER] Renderizando detalhes para disciplina:', disciplinaId, 'tipo:', typeof disciplinaId);
    
    // Garantir que disciplinaId é válido (aceita tanto números quanto strings)
    const disciplinaIdInt = parseInt(disciplinaId);
    const isNumericId = !isNaN(disciplinaIdInt) && disciplinaIdInt > 0;
    const isStringId = typeof disciplinaId === 'string' && disciplinaId.trim().length > 0 && disciplinaId !== '0';
    
    if (!isNumericId && !isStringId) {
        console.error('❌ [RENDER] ID da disciplina inválido:', disciplinaId, 'tipo:', typeof disciplinaId);
        console.error('❌ [RENDER] Stack trace:', new Error().stack);
        return;
    }
    
    const dataElement = document.getElementById(`data-disciplina-${disciplinaId}`);
    if (!dataElement) {
        console.error('❌ [RENDER] Elemento de dados não encontrado para disciplina:', disciplinaId);
        return;
    }
    
    const { disciplina, aulas, estatisticas } = data;
    
    // Criar HTML dos detalhes
    let html = `
        <div class="disciplina-stats-summary">
            <h6 style="color: #023A8D; margin-bottom: 15px;">
                <i class="fas fa-chart-bar me-2"></i>Estatísticas da Disciplina
            </h6>
            
            <div class="stats-grid">
                <div class="stat-card-mini">
                    <div class="stat-number-mini">${estatisticas.total_aulas}</div>
                    <div class="stat-label-mini">Total de Aulas</div>
                </div>
                <div class="stat-card-mini">
                    <div class="stat-number-mini">${estatisticas.aulas_realizadas}</div>
                    <div class="stat-label-mini">Realizadas</div>
                </div>
                <div class="stat-card-mini">
                    <div class="stat-number-mini">${estatisticas.aulas_agendadas}</div>
                    <div class="stat-label-mini">Agendadas</div>
                </div>
                <div class="stat-card-mini">
                    <div class="stat-number-mini">${estatisticas.total_horas}h</div>
                    <div class="stat-label-mini">Horas Totais</div>
                </div>
            </div>
            
            <div class="progress-container">
                <div class="progress-label">
                    <span>Progresso da Disciplina</span>
                    <span>${estatisticas.total_horas}h / ${estatisticas.carga_obrigatoria}h</span>
                </div>
                <div class="progress-bar-custom">
                    <div class="progress-fill" style="width: ${Math.min(100, (estatisticas.total_horas / estatisticas.carga_obrigatoria) * 100)}%"></div>
                </div>
            </div>
        </div>
    `;
    
    // Adicionar tabela de aulas se houver aulas
    if (aulas && aulas.length > 0) {
        html += `
            <div style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
                <h6 style="color: #023A8D; margin-bottom: 15px;">
                    <i class="fas fa-calendar-alt me-2"></i>Aulas Agendadas (${aulas.length})
                </h6>
                
                <div style="overflow-x: auto;">
                    <table class="aulas-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Dia</th>
                                <th>Horário</th>
                                <th>Duração</th>
                                <th>Status</th>
                                <th>Sala</th>
                                <th>Instrutor</th>
                                <th>Observações</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        aulas.forEach(aula => {
            html += `
                <tr>
                    <td style="font-weight: 600;">${aula.data_formatada}</td>
                    <td style="color: #6c757d;">${aula.dia_semana}</td>
                    <td>
                        <strong>${aula.hora_inicio}</strong><br>
                        <small style="color: #6c757d;">até ${aula.hora_fim}</small>
                    </td>
                    <td style="font-weight: 600; color: #023A8D;">${aula.duracao_horas}h</td>
                    <td>
                        <span class="status-badge-table status-${aula.status}">${aula.status_formatado}</span>
                    </td>
                    <td style="font-weight: 500;">${aula.sala_nome}</td>
                    <td class="instrutor-info">
                        <div class="instrutor-nome">${aula.instrutor_nome}</div>
                        ${aula.instrutor_telefone ? `<div class="instrutor-contato">📞 ${aula.instrutor_telefone}</div>` : ''}
                        ${aula.instrutor_email ? `<div class="instrutor-contato">✉️ ${aula.instrutor_email}</div>` : ''}
                    </td>
                    <td style="font-style: italic; color: #6c757d; max-width: 200px; word-wrap: break-word;">
                        ${aula.observacoes || '-'}
                    </td>
                </tr>
            `;
        });
        
        html += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    } else {
        html += `
            <div style="background: white; border-radius: 8px; padding: 40px; text-align: center; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
                <i class="fas fa-calendar-times" style="font-size: 3rem; color: #6c757d; margin-bottom: 15px;"></i>
                <h6 style="color: #6c757d; margin-bottom: 10px;">Nenhuma aula agendada</h6>
                <p style="color: #6c757d; margin: 0;">Esta disciplina ainda não possui aulas agendadas.</p>
            </div>
        `;
    }
    
    // Inserir HTML
    dataElement.innerHTML = html;
    
    console.log('✅ [RENDER] Detalhes renderizados com sucesso');
}

/**
 * Mostrar erro ao carregar detalhes
 * @param {number} disciplinaId - ID da disciplina
 * @param {string} errorMessage - Mensagem de erro
 */
function mostrarErroDetalhes(disciplinaId, errorMessage) {
    console.error('❌ [ERRO] Mostrando erro para disciplina:', disciplinaId, 'tipo:', typeof disciplinaId, errorMessage);
    
    // Garantir que disciplinaId é válido (aceita tanto números quanto strings)
    const disciplinaIdInt = parseInt(disciplinaId);
    const isNumericId = !isNaN(disciplinaIdInt) && disciplinaIdInt > 0;
    const isStringId = typeof disciplinaId === 'string' && disciplinaId.trim().length > 0 && disciplinaId !== '0';
    
    if (!isNumericId && !isStringId) {
        console.error('❌ [ERRO] ID da disciplina inválido:', disciplinaId, 'tipo:', typeof disciplinaId);
        console.error('❌ [ERRO] Stack trace:', new Error().stack);
        return;
    }
    
    const dataElement = document.getElementById(`data-disciplina-${disciplinaId}`);
    if (!dataElement) {
        console.error('❌ [ERRO] Elemento de dados não encontrado para disciplina:', disciplinaId);
        return;
    }
    
    dataElement.innerHTML = `
        <div style="background: white; border-radius: 8px; padding: 40px; text-align: center; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
            <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #dc3545; margin-bottom: 15px;"></i>
            <h6 style="color: #dc3545; margin-bottom: 10px;">Erro ao carregar detalhes</h6>
            <p style="color: #6c757d; margin: 0;">${errorMessage}</p>
            <button onclick="carregarDetalhesDisciplina('${disciplinaId}')" 
                    style="margin-top: 15px; padding: 8px 16px; background: #023A8D; color: white; border: none; border-radius: 6px; cursor: pointer;">
                <i class="fas fa-redo me-2"></i>Tentar Novamente
            </button>
        </div>
    `;
}

// ==========================================
// SISTEMA DE EDIÇÃO INLINE
// ==========================================

// Função de teste específica para verificar disciplinas
function testarDisciplinasCompletas() {
    console.log('🧪 [TESTE-COMPLETO] Verificando se todas as disciplinas estão carregadas...');
    
    const selects = document.querySelectorAll('select');
    console.log('🔍 [TESTE-COMPLETO] Selects encontrados:', selects.length);
    
    selects.forEach((select, index) => {
        const opcoes = Array.from(select.options).map(opt => opt.textContent);
        console.log(`📋 [TESTE-COMPLETO] Select ${index + 1} (${select.name || select.id}):`, {
            totalOpcoes: select.options.length,
            disciplinas: opcoes
        });
        
        // Verificar se tem pelo menos 6 disciplinas (excluindo o placeholder)
        if (select.options.length < 7) {
            console.warn(`⚠️ [TESTE-COMPLETO] Select ${index + 1} tem poucas opções!`);
        } else {
            console.log(`✅ [TESTE-COMPLETO] Select ${index + 1} tem opções suficientes!`);
        }
    });
    
    // Testar API diretamente
    fetch(API_BASE_PATH + '/admin/api/disciplinas-clean.php?acao=listar')
        .then(response => response.json())
        .then(data => {
            console.log('📊 [TESTE-COMPLETO] API retornou:', data.disciplinas ? data.disciplinas.length : 0, 'disciplinas');
            if (data.disciplinas) {
                console.log('📋 [TESTE-COMPLETO] Disciplinas da API:', data.disciplinas.map(d => d.nome));
            }
        })
        .catch(error => {
            console.error('❌ [TESTE-COMPLETO] Erro na API:', error);
        });
}

// Função de teste para verificar se tudo está funcionando
function testarSistemaDisciplinas() {
    console.log('🧪 [TESTE] Iniciando teste do sistema de disciplinas...');
    
    // Testar se a função está definida
    console.log('🔍 [TESTE] Função editarDisciplinaCadastrada:', typeof editarDisciplinaCadastrada);
    console.log('🔍 [TESTE] Função carregarDisciplinasNoSelect:', typeof carregarDisciplinasNoSelect);
    console.log('🔍 [TESTE] Função carregarDisciplinasFallback:', typeof carregarDisciplinasFallback);
    console.log('🔍 [TESTE] Função forcarCarregamentoDisciplinas:', typeof forcarCarregamentoDisciplinas);
    
    // Executar teste completo após 2 segundos
    // TEMPORARIAMENTE DESABILITADO PARA TESTE
    // setTimeout(testarDisciplinasCompletas, 2000);
    
    // Testar se há selects na página
    const selects = document.querySelectorAll('select');
    console.log('🔍 [TESTE] Selects encontrados na página:', selects.length);
    
    selects.forEach((select, index) => {
        console.log(`🔍 [TESTE] Select ${index + 1}:`, {
            name: select.name,
            className: select.className,
            id: select.id,
            options: select.options.length
        });
    });
    
    // Testar API
    fetch(API_BASE_PATH + '/admin/api/disciplinas-clean.php?acao=listar')
        .then(response => response.json())
        .then(data => {
            console.log('✅ [TESTE] API funcionando:', data.disciplinas ? data.disciplinas.length : 0, 'disciplinas');
        })
        .catch(error => {
            console.error('❌ [TESTE] Erro na API:', error);
        });
}

// Inicializar sistema
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 [SISTEMA] Inicializando página de detalhes da turma...');
    console.log('🚀 [SISTEMA] DOM carregado completamente!');
    
    // Debug imediato - verificar elementos
    console.log('🔍 [SISTEMA] Verificando elementos de disciplina...');
    const disciplinaCards = document.querySelectorAll('.disciplina-accordion');
    console.log('🔍 [SISTEMA] Cards encontrados:', disciplinaCards.length);
    
    disciplinaCards.forEach((card, index) => {
        const disciplinaId = card.getAttribute('data-disciplina-id');
        const turmaId = card.getAttribute('data-turma-id');
        console.log(`📋 [SISTEMA] Card ${index + 1}: disciplinaId="${disciplinaId}", turmaId="${turmaId}"`);
        
        // Verificar onclick
        const clickableElement = card.querySelector('.disciplina-header-clickable');
        if (clickableElement) {
            console.log(`✅ [SISTEMA] Card ${index + 1}: Elemento clicável encontrado`);
            console.log(`🔗 [SISTEMA] Card ${index + 1}: onclick="${clickableElement.getAttribute('onclick')}"`);
        } else {
            console.error(`❌ [SISTEMA] Card ${index + 1}: Elemento clicável NÃO encontrado`);
        }
    });
    
    // Executar teste
    // TEMPORARIAMENTE DESABILITADO PARA TESTE
    // setTimeout(testarSistemaDisciplinas, 1000);
    
    // Carregar disciplinas usando método simples que sempre funciona
    console.log('🚀 [INIT] Usando método simples para carregar disciplinas...');
    // TEMPORARIAMENTE DESABILITADO PARA TESTE
    // carregarDisciplinasSimples();
    
    // Verificar se há selects de disciplina na página atual
    // TEMPORARIAMENTE DESABILITADO PARA TESTE
    /*
    setTimeout(() => {
        const disciplinaSelects = document.querySelectorAll('.disciplina-item select, .disciplina-edit-form select');
        console.log('🔍 [SELECTS] Verificando selects de disciplina encontrados:', disciplinaSelects.length);
        
        if (disciplinaSelects.length > 0) {
            console.log('📊 [SELECTS] Carregando disciplinas nos selects existentes');
            disciplinaSelects.forEach((select, index) => {
                if (select.options.length <= 1) {
                    console.log(`🔄 [SELECT ${index}] Carregando disciplinas`);
                    carregarDisciplinasSimples();
                }
            });
        } else {
            console.log('ℹ️ [SELECTS] Nenhum select de disciplina encontrado na página atual');
        }
    }, 1000);
    */
    
    // Configurar elementos editáveis
    const editElements = document.querySelectorAll('.inline-edit');
    editElements.forEach(element => {
        element.addEventListener('click', function(e) {
            if (e.target.classList.contains('save-btn') || e.target.classList.contains('cancel-btn')) {
                return;
            }
            startEdit(this);
        });
    });
    
    // Configurar eventos específicos para os ícones de edição
    const editIcons = document.querySelectorAll('.edit-icon');
    editIcons.forEach(icon => {
        icon.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const parentElement = this.closest('.inline-edit');
            if (parentElement) {
                startEdit(parentElement);
            }
        });
    });
    
    // Atualizar contador inicial
    atualizarContadorDisciplinasDetalhes();
    
    // Adicionar eventos apenas para selects de disciplina (se existirem)
    const disciplinaSelects = document.querySelectorAll('.disciplina-item select, .disciplina-edit-form select');
    console.log('🔍 [EVENTS] Adicionando eventos para', disciplinaSelects.length, 'selects de disciplina');
    
    disciplinaSelects.forEach((select, index) => {
        console.log(`🔍 [EVENTS] Configurando select ${index + 1}:`, select.name || select.id);
        
        // Evento de clique
        select.addEventListener('click', function() {
            console.log('🖱️ [SELECT] Select clicado:', this.name || this.id);
            // Verificar se tem poucas opções e recarregar se necessário
            if (this.options.length <= 2) {
                console.log('🔄 [SELECT] Poucas opções detectadas, recarregando...');
                carregarDisciplinasSimples();
            }
        });
        
        // Evento de foco
        select.addEventListener('focus', function() {
            console.log('🎯 [SELECT] Select focado:', this.name || this.id);
            // Verificar se tem poucas opções e recarregar se necessário
            if (this.options.length <= 2) {
                console.log('🔄 [SELECT] Poucas opções detectadas no foco, recarregando...');
                carregarDisciplinasSimples();
            }
        });
        
        // Evento de mudança
        select.addEventListener('change', function() {
            console.log('🔄 [SELECT] Select alterado:', this.name || this.id, 'valor:', this.value);
        });
    });
    
    console.log('✅ [SISTEMA] Página inicializada com sucesso');
    
    // Debug: Verificar se os elementos de sanfona foram criados
    setTimeout(() => {
        const disciplinaCards = document.querySelectorAll('.disciplina-accordion');
        console.log('🔍 [DEBUG] Cards de disciplina encontrados:', disciplinaCards.length);
        
        // Monitorar mudanças nos elementos
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList') {
                    mutation.removedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE && 
                            (node.classList.contains('disciplina-accordion') || 
                             node.querySelector('.disciplina-accordion'))) {
                            console.error('🚨 [MONITOR] Elemento de disciplina REMOVIDO:', node);
                            console.error('🚨 [MONITOR] Stack trace:', new Error().stack);
                        }
                    });
                }
            });
        });
        
        // Observar mudanças no container de disciplinas
        const disciplinasContainer = document.querySelector('.disciplinas-cadastradas-section');
        if (disciplinasContainer) {
            observer.observe(disciplinasContainer, { 
                childList: true, 
                subtree: true 
            });
            console.log('👁️ [MONITOR] Observador de mutações ativado');
        }
        
        disciplinaCards.forEach((card, index) => {
            const disciplinaId = card.getAttribute('data-disciplina-id');
            
            console.log(`🔍 [DEBUG] Card ${index + 1}: disciplinaId = "${disciplinaId}" (tipo: ${typeof disciplinaId})`);
            
            // Pular apenas IDs realmente inválidos (null, undefined, string vazia, '0')
            if (!disciplinaId || disciplinaId === '0' || disciplinaId === 'null' || disciplinaId === 'undefined' || disciplinaId.trim() === '') {
                console.log(`⚠️ [DEBUG] Card ${index + 1}: ID realmente inválido ignorado (${disciplinaId})`);
                // Remover elemento com ID inválido
                card.remove();
                return;
            }
            
            // Verificar se o ID é válido (aceita tanto números quanto strings não vazias)
            const disciplinaIdInt = parseInt(disciplinaId);
            const isNumericId = !isNaN(disciplinaIdInt) && disciplinaIdInt > 0;
            const isStringId = typeof disciplinaId === 'string' && disciplinaId.trim().length > 0 && disciplinaId !== '0';
            
            if (!isNumericId && !isStringId) {
                console.log(`⚠️ [DEBUG] Card ${index + 1}: ID não é válido (${disciplinaId})`);
                card.remove();
                return;
            }
            
            const detalhesContent = document.getElementById(`detalhes-disciplina-${disciplinaId}`);
            console.log(`✅ [DEBUG] Card ${index + 1}: VÁLIDO`, {
                disciplinaId: disciplinaId,
                disciplinaIdInt: disciplinaIdInt,
                temConteudo: !!detalhesContent,
                temChevron: !!card.querySelector('.disciplina-chevron')
            });
        });
    }, 2000);
    
    // Verificação periódica apenas para selects de disciplina (se existirem)
    // TEMPORARIAMENTE DESABILITADO PARA TESTE
    /*
    if (disciplinaSelects.length > 0) {
        setInterval(() => {
            let precisaRecarregar = false;
            
            disciplinaSelects.forEach(select => {
                if (select.options.length <= 2) {
                    console.log('⚠️ [PERIODIC] Select de disciplina com poucas opções detectado:', select.name || select.id);
                    precisaRecarregar = true;
                }
            });
            
            if (precisaRecarregar) {
                console.log('🔄 [PERIODIC] Recarregando disciplinas...');
                carregarDisciplinasSimples();
            }
        }, 5000); // Verificar a cada 5 segundos
    }
    */
});

/**
 * Iniciar edição inline de um campo
 * @param {HTMLElement} element - Elemento a ser editado
 */
function startEdit(element) {
    if (element.classList.contains('editing')) return;
    
    const field = element.dataset.field;
    const type = element.dataset.type;
    const value = element.dataset.value;
    
    console.log(`✏️ [EDIT] Iniciando edição do campo: ${field}`);
    
    // Salvar valor original
    originalValues[field] = value;
    
    // Adicionar classe de edição
    element.classList.add('editing');
    
    // Criar input baseado no tipo
    let input = createInputByType(type, value, field);
    
    // Aplicar estilos específicos do campo
    applyFieldStyles(input, field);
    
    // Substituir conteúdo
    element.innerHTML = '';
    element.appendChild(input);
    
    // Configurar eventos
    setupInputEvents(input, element);
    
    // Focar no input
    input.focus();
    if (type === 'text' || type === 'textarea') {
        input.select();
    }
}

/**
 * Criar input baseado no tipo de campo
 * @param {string} type - Tipo do campo (text, select, date, textarea)
 * @param {string} value - Valor atual
 * @param {string} field - Nome do campo
 * @returns {HTMLElement} Elemento input criado
 */
function createInputByType(type, value, field) {
    let input;
    
    switch (type) {
        case 'textarea':
            input = document.createElement('textarea');
            input.value = value;
            input.rows = 3;
            break;
        case 'select':
            input = document.createElement('select');
            addSelectOptions(input, field);
            input.value = value;
            break;
        case 'date':
            input = document.createElement('input');
            input.type = 'date';
            input.value = value;
            break;
        default:
            input = document.createElement('input');
            input.type = 'text';
            input.value = value;
            break;
    }
    
    return input;
}

// Funções auxiliares para melhor comportamento
function applyFieldStyles(input, field) {
    switch(field) {
        case 'nome':
            input.style.fontSize = '1.5rem';
            input.style.fontWeight = 'bold';
            input.style.color = '#023A8D';
            break;
        case 'curso_nome':
            input.style.color = '#666';
            input.style.fontSize = '1rem';
            break;
        case 'data_inicio':
        case 'data_fim':
            input.style.fontFamily = 'monospace';
            input.style.background = '#f8f9fa';
            input.style.borderRadius = '4px';
            input.style.padding = '4px 8px';
            break;
        case 'status':
            input.style.padding = '4px 12px';
            input.style.borderRadius = '20px';
            input.style.fontSize = '0.9rem';
            input.style.fontWeight = '600';
            input.style.textTransform = 'uppercase';
            break;
        case 'modalidade':
        case 'sala_id':
            input.style.fontWeight = '500';
            break;
        case 'observacoes':
            input.style.fontStyle = 'italic';
            input.style.color = '#666';
            break;
    }
}

function setupInputEvents(input, element) {
    // Evento de teclado para salvamento automático
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            saveFieldAutomatically(element, input.value);
        } else if (e.key === 'Escape') {
            e.preventDefault();
            cancelEditAutomatically(element);
        }
    });
    
    // Evento de blur para salvar ao sair do campo
    input.addEventListener('blur', function() {
        setTimeout(() => {
            if (!element.contains(document.activeElement)) {
                saveFieldAutomatically(element, input.value);
            }
        }, 100);
    });
    
    // Evento de input para feedback visual
    input.addEventListener('input', function() {
        element.style.borderColor = '#28a745';
    });
}

function addSelectOptions(select, field) {
    const options = {
        'status': [
            { value: 'criando', text: 'Criando' },
            { value: 'agendando', text: 'Agendando' },
            { value: 'completa', text: 'Completa' },
            { value: 'ativa', text: 'Ativa' },
            { value: 'concluida', text: 'Concluída' }
        ],
        'modalidade': [
            { value: 'presencial', text: 'Presencial' },
            { value: 'online', text: 'Online' },
            { value: 'hibrida', text: 'Híbrida' }
        ],
        'sala_id': <?= json_encode(array_map(function($sala) {
            return ['value' => $sala['id'], 'text' => $sala['nome']];
        }, $salasCadastradas)) ?>,
        'curso_tipo': [
            { value: 'formacao_45h', text: 'Curso de Formação de Condutores - Permissão 45h' },
            { value: 'formacao_acc_20h', text: 'Curso de Formação de Condutores - ACC 20h' },
            { value: 'reciclagem_infrator', text: 'Curso de Reciclagem para Condutor Infrator' },
            { value: 'atualizacao', text: 'Curso de Atualização' }
        ]
    };
    
    if (options[field]) {
        options[field].forEach(option => {
            const optionElement = document.createElement('option');
            optionElement.value = option.value;
            optionElement.textContent = option.text;
            select.appendChild(optionElement);
        });
    }
}

// Funções de salvamento automático (botões removidos)
function saveFieldAutomatically(element, newValue) {
    const field = element.dataset.field;
    
    console.log(`💾 [AUTO-SAVE] Salvando campo ${field} com valor: ${newValue}`);
    
    // Enviar para o servidor
    fetch(API_BASE_PATH + '/admin/api/turmas-teoricas-inline.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update_field',
            turma_id: <?= $turmaId ?>,
            field: field,
            value: newValue
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.text();
    })
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                // Atualizar valor no dataset
                element.dataset.value = newValue;
                
                // Restaurar visualização
                restoreView(element, newValue);
                
                // Mostrar feedback discreto
                showFeedback('Campo atualizado!', 'success');
            } else {
                showFeedback('Erro ao atualizar campo: ' + data.message, 'error');
            }
        } catch (e) {
            console.error('❌ [AUTO-SAVE] Resposta não é JSON válido:', text);
            showFeedback('Erro: Resposta inválida do servidor', 'error');
        }
    })
    .catch(error => {
        console.error('❌ [AUTO-SAVE] Erro:', error);
        showFeedback('Erro ao atualizar campo: ' + error.message, 'error');
    });
}

function cancelEditAutomatically(element) {
    const field = element.dataset.field;
    const originalValue = originalValues[field];
    
    console.log(`❌ [CANCEL] Cancelando edição do campo ${field}`);
    
    // Restaurar visualização
    restoreView(element, originalValue);
}

// Funções antigas removidas - agora usa salvamento automático

// Função restoreView melhorada com transição suave
function restoreView(element, value) {
    const field = element.dataset.field;
    const type = element.dataset.type;
    
    console.log(`🔄 [RESTORE] Restaurando campo ${field} com valor: ${value}`);
    
    // Remover classe de edição
    element.classList.remove('editing');
    
    // Restaurar conteúdo baseado no tipo
    let displayValue = value;
    if (type === 'date' && value) {
        displayValue = new Date(value + 'T00:00:00').toLocaleDateString('pt-BR');
    } else if (type === 'select') {
        displayValue = getSelectDisplayValue(field, value);
        console.log(`🔄 [RESTORE] Campo select ${field}: ${value} → ${displayValue}`);
    }
    
    // Restaurar HTML (sem botões - salvamento automático)
    element.innerHTML = displayValue + 
        '<i class="fas fa-edit edit-icon"></i>';
    
    // Aplicar estilos específicos do campo APÓS restaurar o HTML
    setTimeout(() => {
        applyDisplayStyles(element, field);
        console.log(`✅ [RESTORE] Campo ${field} restaurado com sucesso`);
    }, 10);
}

// Aplicar estilos específicos para exibição
function applyDisplayStyles(element, field) {
    // Remover estilos inline que podem ter sido aplicados
    element.style.borderColor = '';
    element.style.border = 'none';
    
    // Estilos gerais para evitar texto cortado
    element.style.wordWrap = 'break-word';
    element.style.overflowWrap = 'break-word';
    element.style.whiteSpace = 'normal';
    element.style.maxWidth = '100%';
    element.style.boxSizing = 'border-box';
    
    // Aplicar estilos específicos do campo
    switch(field) {
        case 'nome':
            element.style.fontSize = '1.5rem';
            element.style.fontWeight = 'bold';
            element.style.color = '#023A8D';
            element.style.border = 'none';
            element.style.minWidth = '200px';
            break;
        case 'curso_tipo':
            element.style.color = '#666';
            element.style.fontSize = '1rem';
            element.style.border = 'none';
            element.style.minWidth = '400px';
            element.style.maxWidth = 'none';
            element.style.width = 'fit-content';
            element.style.wordWrap = 'break-word';
            element.style.overflowWrap = 'break-word';
            element.style.whiteSpace = 'nowrap';
            element.style.display = 'block';
            element.style.verticalAlign = 'top';
            element.style.lineHeight = '1.4';
            element.style.overflow = 'visible';
            element.style.textOverflow = 'unset';
            element.style.textAlign = 'left';
            element.style.padding = '0';
            element.style.margin = '0';
            console.log(`🎨 [STYLES] Aplicando estilos para curso_tipo`);
            break;
        case 'data_inicio':
        case 'data_fim':
            element.style.fontFamily = 'monospace';
            element.style.background = '#f8f9fa';
            element.style.borderRadius = '4px';
            element.style.padding = '4px 8px';
            element.style.border = 'none';
            element.style.minWidth = '120px';
            element.style.maxWidth = '100%';
            break;
        case 'status':
            element.style.padding = '4px 12px';
            element.style.borderRadius = '20px';
            element.style.fontSize = '0.9rem';
            element.style.fontWeight = '600';
            element.style.textTransform = 'uppercase';
            element.style.border = 'none';
            element.style.minWidth = '100px';
            element.style.maxWidth = '100%';
            break;
        case 'modalidade':
        case 'sala_id':
            element.style.fontWeight = '500';
            element.style.border = 'none';
            element.style.minWidth = '120px';
            element.style.maxWidth = '100%';
            break;
        case 'observacoes':
            element.style.fontStyle = 'italic';
            element.style.color = '#666';
            element.style.border = 'none';
            element.style.minWidth = '300px';
            element.style.maxWidth = '100%';
            element.style.whiteSpace = 'pre-wrap';
            element.style.wordWrap = 'break-word';
            element.style.overflowWrap = 'break-word';
            break;
    }
}

function getSelectDisplayValue(field, value) {
    const options = {
        'status': {
            'criando': 'Criando',
            'agendando': 'Agendando',
            'completa': 'Completa',
            'ativa': 'Ativa',
            'concluida': 'Concluída'
        },
        'modalidade': {
            'presencial': 'Presencial',
            'online': 'Online',
            'hibrida': 'Híbrida'
        },
        'curso_tipo': {
            'formacao_45h': 'Curso de Formação de Condutores - Permissão 45h',
            'formacao_acc_20h': 'Curso de Formação de Condutores - ACC 20h',
            'reciclagem_infrator': 'Curso de Reciclagem para Condutor Infrator',
            'atualizacao': 'Curso de Atualização'
        },
        'sala_id': <?= json_encode(array_column($salasCadastradas, 'nome', 'id')) ?>
    };
    
    return options[field] && options[field][value] ? options[field][value] : value;
}

function showFeedback(message, type) {
    const feedback = document.createElement('div');
    feedback.className = `alert alert-${type === 'success' ? 'success' : 'danger'}`;
    feedback.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    feedback.textContent = message;
    
    document.body.appendChild(feedback);
    
    setTimeout(() => {
        feedback.remove();
    }, 3000);
}

// Função para iniciar edição de disciplina
function startEditDisciplina(element) {
    console.log('✏️ [EDIT-DISCIPLINA] Iniciando edição de disciplina');
    
    // Adicionar classe de edição
    element.classList.add('editing');
    
    // Buscar disciplinas disponíveis
    fetch(API_BASE_PATH + '/admin/api/disciplinas-estaticas.php?action=listar')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.disciplinas) {
                showDisciplinaEditModal(element, data.disciplinas);
            } else {
                showFeedback('Erro ao carregar disciplinas disponíveis', 'error');
            }
        })
        .catch(error => {
            console.error('❌ [EDIT-DISCIPLINA] Erro:', error);
            showFeedback('Erro ao carregar disciplinas: ' + error.message, 'error');
            element.classList.remove('editing');
        });
}

// Função para mostrar modal de edição de disciplina
function showDisciplinaEditModal(element, disciplinas) {
    const disciplinaId = element.dataset.disciplinaId;
    const disciplinaAtual = element.querySelector('strong').textContent;
    
    // Criar modal
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
    `;
    
    modal.id = 'disciplina-edit-modal';
    modal.setAttribute('data-disciplina-id-atual', disciplinaId);
    
    modal.innerHTML = `
        <div style="background: white; border-radius: 12px; padding: 25px; max-width: 500px; width: 90%; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
            <h4 style="color: #023A8D; margin-bottom: 20px;">
                <i class="fas fa-edit me-2"></i>Editar Disciplina
            </h4>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Disciplina Atual:</label>
                <div style="background: #f8f9fa; padding: 10px; border-radius: 6px; color: #666;">
                    ${disciplinaAtual}
                </div>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Nova Disciplina:</label>
                <select id="nova-disciplina-select" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                    <option value="">Selecione uma disciplina...</option>
                    ${disciplinas.map(d => `<option value="${d.id}">${d.nome}</option>`).join('')}
                </select>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Carga Horária:</label>
                <input type="number" id="nova-carga-horaria" min="1" max="200" 
                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;"
                       placeholder="Digite a carga horária">
            </div>
            
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button onclick="cancelEditDisciplina()" style="padding: 10px 20px; border: 1px solid #ddd; background: white; border-radius: 6px; cursor: pointer;">
                    Cancelar
                </button>
                <button onclick="confirmEditDisciplina()" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 6px; cursor: pointer;">
                    <i class="fas fa-save me-2"></i>Salvar
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Focar no select
    setTimeout(() => {
        document.getElementById('nova-disciplina-select').focus();
    }, 100);
}

// Função para confirmar edição de disciplina
function confirmEditDisciplina() {
    const modal = document.getElementById('disciplina-edit-modal');
    if (!modal) return;
    
    const disciplinaIdAtual = modal.getAttribute('data-disciplina-id-atual');
    if (!disciplinaIdAtual) {
        showFeedback('Erro: ID da disciplina atual não encontrado', 'error');
        return;
    }
    
    saveEditDisciplina(disciplinaIdAtual);
}

// Função para salvar edição de disciplina
function saveEditDisciplina(disciplinaIdAtual) {
    const novaDisciplinaId = document.getElementById('nova-disciplina-select').value;
    const novaCargaHoraria = document.getElementById('nova-carga-horaria').value;
    
    if (!novaDisciplinaId) {
        showFeedback('Selecione uma nova disciplina', 'error');
        return;
    }
    
    if (!novaCargaHoraria || novaCargaHoraria < 1) {
        showFeedback('Digite uma carga horária válida', 'error');
        return;
    }
    
    console.log('💾 [EDIT-DISCIPLINA] Salvando:', {
        disciplinaIdAtual,
        novaDisciplinaId,
        novaCargaHoraria
    });
    
    // Enviar para API
    fetch(API_BASE_PATH + '/admin/api/turmas-teoricas-inline.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update_disciplina',
            turma_id: <?= $turmaId ?>,
            disciplina_id_atual: disciplinaIdAtual,
            disciplina_id_nova: novaDisciplinaId,
            carga_horaria: novaCargaHoraria
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.text();
    })
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                showFeedback('Disciplina atualizada com sucesso!', 'success');
                cancelEditDisciplina();
                // Não recarregar página - manter interface atual
            } else {
                showFeedback('Erro ao atualizar disciplina: ' + data.message, 'error');
            }
        } catch (e) {
            console.error('❌ [EDIT-DISCIPLINA] Resposta não é JSON válido:', text);
            showFeedback('Erro: Resposta inválida do servidor', 'error');
        }
    })
    .catch(error => {
        console.error('❌ [EDIT-DISCIPLINA] Erro:', error);
        showFeedback('Erro ao atualizar disciplina: ' + error.message, 'error');
    });
}

// Função para cancelar edição de disciplina
function cancelEditDisciplina() {
    // Remover modal
    const modal = document.querySelector('div[style*="position: fixed"]');
    if (modal) {
        modal.remove();
    }
    
    // Remover classe de edição de todos os elementos
    document.querySelectorAll('.disciplina-edit-item.editing').forEach(el => {
        el.classList.remove('editing');
    });
}

// Funções para disciplinas - corrigidas
function addDisciplina() {
    console.log('➕ [DISCIPLINA] Adicionando nova disciplina...');
    
    // Verificar se há disciplinas disponíveis primeiro
    fetch(API_BASE_PATH + '/admin/api/disciplinas-estaticas.php?action=listar')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.disciplinas) {
                // Criar modal simples para seleção
                let options = '';
                data.disciplinas.forEach(disciplina => {
                    options += `<option value="${disciplina.id}">${disciplina.nome}</option>`;
                });
                
                const disciplinaId = prompt('Digite o ID da disciplina (ou veja as opções no console):');
                const cargaHoraria = prompt('Digite a carga horária:');
                
                console.log('Disciplinas disponíveis:', data.disciplinas);
                
                if (disciplinaId && cargaHoraria) {
                    addDisciplinaToTurma(disciplinaId, cargaHoraria);
                }
            } else {
                console.error('❌ [DISCIPLINA] Erro ao carregar disciplinas:', data);
                alert('Erro ao carregar disciplinas disponíveis');
            }
        })
        .catch(error => {
            console.error('❌ [DISCIPLINA] Erro na requisição:', error);
            alert('Erro ao carregar disciplinas');
        });
}

function addDisciplinaToTurma(disciplinaId, cargaHoraria) {
    console.log('➕ [DISCIPLINA] Adicionando disciplina:', disciplinaId, 'com carga:', cargaHoraria);
    
    // Enviar para API
    fetch(API_BASE_PATH + '/admin/api/turmas-teoricas-inline.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add_disciplina',
            turma_id: <?= $turmaId ?>,
            disciplina_id: disciplinaId,
            carga_horaria: cargaHoraria
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.text();
    })
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                showFeedback('Disciplina adicionada com sucesso!', 'success');
                // Não recarregar página - manter interface atual
            } else {
                showFeedback('Erro ao adicionar disciplina: ' + data.message, 'error');
            }
        } catch (e) {
            console.error('❌ [DISCIPLINA] Resposta não é JSON válido:', text);
            showFeedback('Erro: Resposta inválida do servidor', 'error');
        }
    })
    .catch(error => {
        console.error('❌ [DISCIPLINA] Erro:', error);
        showFeedback('Erro ao adicionar disciplina: ' + error.message, 'error');
    });
}

function removeDisciplina(disciplinaId) {
    if (confirm('Tem certeza que deseja remover esta disciplina?')) {
        console.log('🗑️ [DISCIPLINA] Removendo disciplina:', disciplinaId);
        
        // Enviar para API
        fetch(API_BASE_PATH + '/admin/api/turmas-teoricas-inline.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'remove_disciplina',
                turma_id: <?= $turmaId ?>,
                disciplina_id: disciplinaId
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.text();
        })
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    showFeedback('Disciplina removida com sucesso!', 'success');
                    // Remover elemento do DOM
                    const disciplinaItem = document.querySelector(`[data-disciplina-id="${disciplinaId}"]`);
                    if (disciplinaItem) {
                        disciplinaItem.remove();
                    }
                } else {
                    showFeedback('Erro ao remover disciplina: ' + data.message, 'error');
                }
            } catch (e) {
                console.error('❌ [DISCIPLINA] Resposta não é JSON válido:', text);
                showFeedback('Erro: Resposta inválida do servidor', 'error');
            }
        })
        .catch(error => {
            console.error('❌ [DISCIPLINA] Erro:', error);
            showFeedback('Erro ao remover disciplina: ' + error.message, 'error');
        });
    }
}

// ===== SISTEMA DE EDIÇÃO DE AGENDAMENTOS =====

// Modal de edição de agendamento
function editarAgendamento(id, nomeAula, dataAula, horaInicio, horaFim, instrutorId, salaId, duracao, observacoes) {
    window.currentEditAgendamentoId = id;
    // Criar modal dinamicamente se não existir
    let modal = document.getElementById('modalEditarAgendamento');
    if (!modal) {
        modal = criarModalEdicao();
        document.body.appendChild(modal);
        // Garantir que os listeners do campo de hora sejam anexados
        setTimeout(() => {
            anexarAutoCalculoHoraFim();
        }, 0);
    }
    
    // Buscar dados completos do agendamento
    fetch(`api/agendamento-detalhes.php?id=${id}`)
        .then(response => {
            console.log('🔧 [DEBUG] Resposta agendamento-detalhes:', response.status);
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const agendamento = data.agendamento;
                
                // Preencher campos do modal com dados reais
                document.getElementById('editAgendamentoId').value = agendamento.id;
                document.getElementById('editNomeAula').value = agendamento.nome_aula;
                document.getElementById('editDataAula').value = agendamento.data_aula;
                document.getElementById('editDuracao').value = agendamento.duracao_minutos || 50;
                document.getElementById('editDuracaoDisplay').textContent = (agendamento.duracao_minutos || 50) + ' min';
                document.getElementById('editObservacoes').value = agendamento.observacoes || '';
                
                // Carregar horário no input incluindo o horário do agendamento
                carregarHorariosDisponiveis(agendamento.hora_inicio).then((horarioAjustado) => {
                    const inputHoraInicio = document.getElementById('editHoraInicio');
                    if (inputHoraInicio) {
                        // Usar o horário ajustado se disponível, senão normalizar o original
                        const horaInicio = horarioAjustado || (agendamento.hora_inicio.length === 8 ? agendamento.hora_inicio.substring(0, 5) : agendamento.hora_inicio);
                        if (horaInicio) {
                            inputHoraInicio.value = horaInicio;
                            // Calcular hora de fim automaticamente
                            setTimeout(() => {
                                calcularHoraFimAuto();
                                anexarAutoCalculoHoraFim();
                            }, 100);
                        }
                    }
                });
                
                console.log('✅ [DEBUG] Dados do agendamento carregados:', agendamento);
                
                // Carregar selects com os valores corretos
                carregarDadosSelects(agendamento.instrutor_id, agendamento.sala_id);
            } else {
                console.error('❌ [DEBUG] Erro ao carregar dados do agendamento:', data.message);
                // Tentar API de fallback
                return fetch(`api/agendamento-detalhes-fallback.php?id=${id}`);
            }
        })
        .then(response => {
            if (response) {
                return response.json();
            }
        })
        .then(data => {
            if (data && data.success) {
                const agendamento = data.agendamento;
                
                // Preencher campos do modal com dados de fallback
                document.getElementById('editAgendamentoId').value = agendamento.id;
                document.getElementById('editNomeAula').value = agendamento.nome_aula;
                document.getElementById('editDataAula').value = agendamento.data_aula;
                document.getElementById('editDuracao').value = agendamento.duracao_minutos;
                document.getElementById('editDuracaoDisplay').textContent = agendamento.duracao_minutos + ' min';
                document.getElementById('editObservacoes').value = agendamento.observacoes || '';
                
                // Carregar horário no input incluindo o horário do agendamento
                carregarHorariosDisponiveis(agendamento.hora_inicio).then((horarioAjustado) => {
                    const inputHoraInicio = document.getElementById('editHoraInicio');
                    if (inputHoraInicio) {
                        // Usar o horário ajustado se disponível, senão normalizar o original
                        const horaInicio = horarioAjustado || (agendamento.hora_inicio.length === 8 ? agendamento.hora_inicio.substring(0, 5) : agendamento.hora_inicio);
                        if (horaInicio) {
                            inputHoraInicio.value = horaInicio;
                            setTimeout(() => {
                                calcularHoraFimAuto();
                                anexarAutoCalculoHoraFim();
                            }, 100);
                        }
                    }
                });
                
                console.log('✅ [DEBUG] Dados de fallback carregados:', agendamento);
                
                // Carregar selects com os valores corretos
                carregarDadosSelects(agendamento.instrutor_id, agendamento.sala_id);
            } else {
                // Usar dados passados como parâmetro como último fallback
                document.getElementById('editAgendamentoId').value = id;
                document.getElementById('editNomeAula').value = nomeAula;
                document.getElementById('editDataAula').value = dataAula;
                document.getElementById('editDuracao').value = duracao;
                document.getElementById('editDuracaoDisplay').textContent = duracao + ' min';
                document.getElementById('editObservacoes').value = observacoes || '';
                
                // Carregar horário no input incluindo o horário passado
                carregarHorariosDisponiveis(horaInicio).then((horarioAjustado) => {
                    const inputHoraInicio = document.getElementById('editHoraInicio');
                    if (inputHoraInicio) {
                        // Usar o horário ajustado se disponível, senão normalizar o original
                        const horaInicioParaUsar = horarioAjustado || (horaInicio.length === 8 ? horaInicio.substring(0, 5) : horaInicio);
                        if (horaInicioParaUsar) {
                            inputHoraInicio.value = horaInicioParaUsar;
                            setTimeout(() => {
                                calcularHoraFimAuto();
                                anexarAutoCalculoHoraFim();
                            }, 100);
                        }
                    }
                });
                
                console.log('⚠️ [DEBUG] Usando dados passados como parâmetro');
                carregarDadosSelects(instrutorId, salaId);
            }
        })
        .catch(error => {
            console.error('❌ [DEBUG] Erro ao buscar dados do agendamento:', error);
            // Usar dados passados como parâmetro como último fallback
            document.getElementById('editAgendamentoId').value = id;
            document.getElementById('editNomeAula').value = nomeAula;
            document.getElementById('editDataAula').value = dataAula;
            document.getElementById('editDuracao').value = duracao;
            document.getElementById('editDuracaoDisplay').textContent = duracao + ' min';
            document.getElementById('editObservacoes').value = observacoes || '';
            
            // Carregar horário no input incluindo o horário passado
            carregarHorariosDisponiveis(horaInicio).then(() => {
                const inputHoraInicio = document.getElementById('editHoraInicio');
                if (inputHoraInicio && horaInicio) {
                    // Normalizar horário se necessário
                    const horaNormalizada = horaInicio.length === 8 ? horaInicio.substring(0, 5) : horaInicio;
                    inputHoraInicio.value = horaNormalizada;
                    setTimeout(() => {
                        calcularHoraFimAuto();
                        anexarAutoCalculoHoraFim();
                    }, 100);
                }
            });
            
            console.log('⚠️ [DEBUG] Usando dados passados como parâmetro (catch)');
            carregarDadosSelects(instrutorId, salaId);
        });
    
    // Mostrar modal seguindo o padrão do sistema
    modal.classList.add('show');
    modal.style.display = 'flex';
    
    // Adicionar animação de fade-in
    setTimeout(() => {
        modal.classList.add('popup-fade-in');
    }, 10);
}

// Cancelar agendamento
function cancelarAgendamento(id, nomeAula) {
    console.log('🔧 [DEBUG] Iniciando cancelamento:', { id, nomeAula });
    
    if (confirm(`Tem certeza que deseja cancelar o agendamento "${nomeAula}"?`)) {
        const url = getBasePath() + '/admin/api/turmas-teoricas.php';
        const data = {
            acao: 'cancelar_aula',
            aula_id: id
        };
        
        console.log('🔧 [DEBUG] URL:', url);
        console.log('🔧 [DEBUG] Dados:', data);
        
        // Encontrar a linha da tabela antes de fazer a requisição
        const linhaAgendamento = document.querySelector(`tr[data-agendamento-id="${id}"]`);
        
        fetch(url, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            console.log('🔧 [DEBUG] Status da resposta:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('🔧 [DEBUG] Resposta bruta:', text);
            try {
                const data = JSON.parse(text);
                console.log('🔧 [DEBUG] Dados parseados:', data);
                if (data.sucesso) {
                    // Remover a linha da tabela sem recarregar a página
                    if (linhaAgendamento) {
                        // Salvar referências antes de remover
                        const tbody = linhaAgendamento.parentElement;
                        const container = tbody?.closest('.historico-agendamentos');
                        
                        // Adicionar animação de fade out antes de remover
                        linhaAgendamento.style.transition = 'opacity 0.3s ease-out';
                        linhaAgendamento.style.opacity = '0';
                        
                        setTimeout(() => {
                            linhaAgendamento.remove();
                            console.log('✅ [DEBUG] Linha removida da tabela');
                            
                            // Verificar se a tabela ficou vazia e mostrar mensagem
                            if (tbody && tbody.querySelectorAll('tr').length === 0) {
                                if (container) {
                                    const table = container.querySelector('.table-responsive');
                                    if (table) {
                                        table.remove();
                                        container.insertAdjacentHTML('beforeend', `
                                            <div class="text-center py-4">
                                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                                <h6 class="text-muted">Nenhum agendamento encontrado</h6>
                                                <p class="text-muted small">Não há aulas agendadas para esta disciplina ainda.</p>
                                            </div>
                                        `);
                                    }
                                }
                            }
                        }, 300);
                    }
                    
                    // Atualizar estatísticas após cancelamento
                    setTimeout(() => {
                        atualizarEstatisticasTurma();
                    }, 400);
                    
                    showFeedback('✅ Agendamento cancelado com sucesso!', 'success');
                } else {
                    showFeedback('❌ Erro ao cancelar agendamento: ' + (data.mensagem || data.message || 'Erro desconhecido'), 'error');
                }
            } catch (e) {
                console.error('❌ [AGENDAMENTO] Resposta não é JSON válido:', text);
                showFeedback('❌ Erro: Resposta inválida do servidor', 'error');
            }
        })
        .catch(error => {
            console.error('❌ [AGENDAMENTO] Erro:', error);
            showFeedback('❌ Erro ao cancelar agendamento: ' + error.message, 'error');
        });
    }
}

// Salvar edição de agendamento
function salvarEdicaoAgendamento() {
    const form = document.getElementById('formEditarAgendamento');
    const formData = new FormData(form);
    
    // Garantir cálculo automático da hora fim antes de coletar dados
    if (typeof calcularHoraFimAuto === 'function') {
        calcularHoraFimAuto();
    }

    // Validar campos obrigatórios (hora_fim é calculada automaticamente)
    const camposObrigatorios = ['nome_aula', 'data_aula', 'hora_inicio', 'instrutor_id'];
    for (let campo of camposObrigatorios) {
        if (!formData.get(campo)) {
            showFeedback(`Campo obrigatório: ${campo.replace('_', ' ')}`, 'error');
            return;
        }
    }
    
    // Validar data não pode ser no passado
    const dataAula = new Date(formData.get('data_aula'));
    const hoje = new Date();
    hoje.setHours(0, 0, 0, 0);
    
    if (dataAula < hoje) {
        showFeedback('A data da aula não pode ser no passado', 'error');
        return;
    }
    
    // Validar horários
    const horaInicio = formData.get('hora_inicio');
    const horaFim = formData.get('hora_fim');
    
    if (horaInicio && horaFim && horaFim <= horaInicio) {
        showFeedback('A hora de fim deve ser posterior à hora de início', 'error');
        return;
    }
    
    // Converter FormData para objeto
    const data = {};
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    // Garantir que observações seja incluído
    const observacoes = document.getElementById('editObservacoes');
    if (observacoes) {
        data.observacoes = observacoes.value;
    }
    
    data.acao = 'editar_aula';
    data.aula_id = document.getElementById('editAgendamentoId').value;
    
    // Debug: mostrar dados que serão enviados
    console.log('🔧 [DEBUG] Dados a serem enviados:', data);
    
    // Mostrar loading no botão
    const btnSalvar = document.querySelector('#modalEditarAgendamento .popup-save-button');
    let restaurarBtn = null;
    if (btnSalvar) {
        restaurarBtn = mostrarLoading(btnSalvar);
    }
    
    fetch(getBasePath() + '/admin/api/turmas-teoricas.php', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.text())
    .then(text => {
        if (restaurarBtn) restaurarBtn(); // Restaurar botão
        try {
            const data = JSON.parse(text);
            if (data.sucesso || data.success) {
                showFeedback(data.mensagem || 'Agendamento editado com sucesso!', 'success');
                // Atualizar linha da tabela (sem recarregar)
                try {
                    const row = document.querySelector(`[data-agendamento-id="${window.currentEditAgendamentoId}"]`);
                    if (row) {
                        const nome = document.getElementById('editNomeAula')?.value || '';
                        const dataAula = document.getElementById('editDataAula')?.value || '';
                        const horaInicio = document.getElementById('editHoraInicio')?.value || '';
                        const horaFim = document.getElementById('editHoraFim')?.value || '';
                        const instrutorSel = document.getElementById('editInstrutor');
                        const instrutorNome = instrutorSel && instrutorSel.options[instrutorSel.selectedIndex] ? instrutorSel.options[instrutorSel.selectedIndex].text : '';
                        const salaSel = document.getElementById('editSala');
                        const salaNome = salaSel && salaSel.options[salaSel.selectedIndex] ? salaSel.options[salaSel.selectedIndex].text : '';
                        
                        const cells = row.querySelectorAll('td');
                        if (cells.length >= 7) {
                            cells[0].innerHTML = `<strong>${nome}</strong>`;
                            if (dataAula) {
                                const dataBR = new Date(dataAula + 'T00:00:00').toLocaleDateString('pt-BR');
                                cells[1].textContent = dataBR;
                            }
                            cells[2].textContent = `${horaInicio?.slice(0,5)} - ${horaFim?.slice(0,5)}`;
                            cells[3].textContent = instrutorNome || cells[3].textContent;
                            cells[4].textContent = salaNome || cells[4].textContent;
                            cells[5].textContent = '50 min';
                        }
                    }
                } catch (e) { console.error('Erro ao atualizar linha editada:', e); }
                // Fechar modal sem recarregar
                fecharModalEdicao();
            } else {
                showFeedback('Erro ao editar agendamento: ' + (data.mensagem || data.message || data.error || 'Erro desconhecido'), 'error');
            }
        } catch (e) {
            console.error('❌ [AGENDAMENTO] Resposta não é JSON válido:', text);
            showFeedback('Erro: Resposta inválida do servidor', 'error');
        }
    })
    .catch(error => {
        if (restaurarBtn) restaurarBtn(); // Restaurar botão em caso de erro
        console.error('❌ [AGENDAMENTO] Erro:', error);
        showFeedback('Erro ao editar agendamento: ' + (error?.message || 'Erro desconhecido'), 'error');
    });
}

// Criar modal de edição dinamicamente seguindo o padrão do sistema
function criarModalEdicao() {
    const modal = document.createElement('div');
    modal.id = 'modalEditarAgendamento';
    modal.className = 'popup-modal';
    modal.innerHTML = `
        <div class="popup-modal-wrapper">
            <div class="popup-modal-header">
                <div class="header-content">
                    <div class="header-icon">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div class="header-text">
                        <h5>Editar Agendamento</h5>
                        <small>Modifique os detalhes do agendamento selecionado</small>
                    </div>
                </div>
                <button type="button" class="popup-modal-close" onclick="fecharModalEdicao()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="popup-modal-content">
                <form id="formEditarAgendamento">
                    <input type="hidden" id="editAgendamentoId" name="aula_id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="editNomeAula" class="form-label fw-semibold">
                                Nome da Aula <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="editNomeAula" name="nome_aula" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editDataAula" class="form-label fw-semibold">
                                Data da Aula <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control" id="editDataAula" name="data_aula" required>
                        </div>
                    </div>
                    
                    <div class="row g-3 mt-3">
                        <div class="col-md-4">
                            <label for="editHoraInicio" class="form-label fw-semibold">
                                Hora Início <span class="text-danger">*</span>
                            </label>
                            <input type="time" 
                                   class="form-control" 
                                   id="editHoraInicio" 
                                   name="hora_inicio" 
                                   placeholder="HH:MM"
                                   step="60"
                                   required>
                        </div>
                        <div class="col-md-4">
                            <label for="editHoraFim" class="form-label fw-semibold">
                                Hora Fim
                            </label>
                            <div class="form-control-plaintext bg-light border rounded p-2">
                                <i class="fas fa-clock me-2 text-primary"></i>
                                <strong id="editHoraFimDisplay">--:--</strong>
                                <small class="text-muted ms-2">(calculada automaticamente)</small>
                            </div>
                            <input type="hidden" id="editHoraFim" name="hora_fim">
                        </div>
                        <div class="col-md-4">
                            <label for="editDuracaoDisplay" class="form-label fw-semibold">
                                Duração (min)
                            </label>
                            <div class="form-control-plaintext bg-light border rounded p-2">
                                <i class="fas fa-hourglass-half me-2 text-primary"></i>
                                <strong id="editDuracaoDisplay">50 min</strong>
                            </div>
                            <input type="hidden" id="editDuracao" name="duracao" value="50">
                        </div>
                    </div>
                    
                    <div class="row g-3 mt-3">
                        <div class="col-md-6">
                            <label for="editInstrutor" class="form-label fw-semibold">
                                Instrutor <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="editInstrutor" name="instrutor_id" required>
                                <option value="">Selecione um instrutor</option>
                                <!-- Será preenchido via AJAX -->
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="editSala" class="form-label fw-semibold">Sala</label>
                            <select class="form-select" id="editSala" name="sala_id">
                                <option value="">Selecione uma sala</option>
                                <!-- Será preenchido via AJAX -->
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <label for="editObservacoes" class="form-label fw-semibold">Observações</label>
                        <textarea class="form-control" id="editObservacoes" name="observacoes" rows="3" placeholder="Digite observações adicionais sobre o agendamento..."></textarea>
                    </div>
                </form>
            </div>
            
            <div class="popup-modal-footer">
                <div class="popup-footer-info">
                    <small>
                        <i class="fas fa-info-circle"></i>
                        Campos marcados com * são obrigatórios
                    </small>
                </div>
                <div class="popup-footer-actions">
                    <button type="button" class="popup-secondary-button" onclick="fecharModalEdicao()">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                    <button type="button" class="popup-save-button" onclick="salvarEdicaoAgendamento()">
                        <i class="fas fa-save"></i>
                        Salvar Alterações
                    </button>
                </div>
            </div>
        </div>
    `;
    
    return modal;
}

// Função para fechar o modal de edição
function fecharModalEdicao() {
    const modal = document.getElementById('modalEditarAgendamento');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.remove();
        }, 300);
    }
}

// Função para carregar dados dos selects
function carregarDadosSelects(instrutorId = null, salaId = null) {
    console.log('🔧 [DEBUG] Carregando dados dos selects...');
    
    // Carregar instrutores
    fetch('api/instrutores-real.php')
        .then(response => {
            console.log('🔧 [DEBUG] Resposta instrutores:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('🔧 [DEBUG] Dados instrutores:', data);
            if (data.success) {
                const selectInstrutor = document.getElementById('editInstrutor');
                if (selectInstrutor) {
                    selectInstrutor.innerHTML = '<option value="">Selecione um instrutor</option>';
                    data.instrutores.forEach(instrutor => {
                        const option = document.createElement('option');
                        option.value = instrutor.id;
                        option.textContent = instrutor.nome;
                        selectInstrutor.appendChild(option);
                    });
                    
                    // Definir valor selecionado se fornecido
                    if (instrutorId) {
                        selectInstrutor.value = instrutorId;
                        console.log('✅ [DEBUG] Instrutor selecionado:', instrutorId);
                    }
                    
                    console.log('✅ [DEBUG] Instrutores carregados:', data.instrutores.length);
                } else {
                    console.log('❌ [DEBUG] Select instrutor não encontrado');
                }
            } else {
                console.log('❌ [DEBUG] Erro ao carregar instrutores:', data.message);
            }
        })
        .catch(error => {
            console.error('❌ [DEBUG] Erro ao carregar instrutores:', error);
        });
    
    // Carregar salas
    fetch('api/salas-real.php')
        .then(response => {
            console.log('🔧 [DEBUG] Resposta salas:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('🔧 [DEBUG] Dados salas:', data);
            if (data.success) {
                const selectSala = document.getElementById('editSala');
                if (selectSala) {
                    selectSala.innerHTML = '<option value="">Selecione uma sala</option>';
                    data.salas.forEach(sala => {
                        const option = document.createElement('option');
                        option.value = sala.id;
                        option.textContent = sala.nome;
                        selectSala.appendChild(option);
                    });
                    
                    // Definir valor selecionado se fornecido
                    if (salaId) {
                        selectSala.value = salaId;
                        console.log('✅ [DEBUG] Sala selecionada:', salaId);
                    }
                    
                    console.log('✅ [DEBUG] Salas carregadas:', data.salas.length);
                } else {
                    console.log('❌ [DEBUG] Select sala não encontrado');
                }
            } else {
                console.log('❌ [DEBUG] Erro ao carregar salas:', data.message);
            }
        })
        .catch(error => {
            console.error('❌ [DEBUG] Erro ao carregar salas:', error);
        });
}

// Carregar horários disponíveis (simplificado para input time)
function carregarHorariosDisponiveis(horarioCustomizado = null) {
    return new Promise((resolve, reject) => {
        // Normalizar horário customizado para formato HH:MM (remover segundos se houver)
        let horarioCustomizadoNormalizado = null;
        if (horarioCustomizado) {
            // Se o horário tem segundos (HH:MM:SS), converter para HH:MM
            if (horarioCustomizado.length === 8 && horarioCustomizado.includes(':')) {
                horarioCustomizadoNormalizado = horarioCustomizado.substring(0, 5);
            } else {
                horarioCustomizadoNormalizado = horarioCustomizado;
            }
        }
        
        const inputHoraInicio = document.getElementById('editHoraInicio');
        if (inputHoraInicio) {
            // Se houver horário customizado, definir o valor
            if (horarioCustomizadoNormalizado) {
                inputHoraInicio.value = horarioCustomizadoNormalizado;
            }
            
            // Adicionar listener para calcular hora de fim automaticamente
            if (!inputHoraInicio.hasAttribute('data-listener-added')) {
                inputHoraInicio.addEventListener('change', calcularHoraFimAuto);
                inputHoraInicio.addEventListener('input', calcularHoraFimAuto);
                inputHoraInicio.setAttribute('data-listener-added', 'true');
            }
            
            // Calcular hora de fim automaticamente se houver valor
            if (horarioCustomizadoNormalizado) {
                setTimeout(() => calcularHoraFimAuto(), 100);
            }
            
            // Retornar o horário customizado normalizado (se houver)
            resolve(horarioCustomizadoNormalizado || null);
        } else {
            reject('Input hora_inicio não encontrado');
        }
    });
}

// Calcular hora de fim automaticamente (hora início + duração)
function calcularHoraFimAuto() {
    const inputHoraInicio = document.getElementById('editHoraInicio');
    const inputHoraFim = document.getElementById('editHoraFim');
    const displayHoraFim = document.getElementById('editHoraFimDisplay');
    const editDuracao = document.getElementById('editDuracao');
    
    if (inputHoraInicio && inputHoraFim && displayHoraFim) {
        const horaInicio = inputHoraInicio.value;
        
        if (horaInicio) {
            // Obter duração do campo ou usar 50 como padrão
            const duracaoMinutos = editDuracao && editDuracao.value ? parseInt(editDuracao.value) : 50;
            
            // Calcular hora de fim (hora início + duração)
            const [horas, minutos] = horaInicio.split(':');
            const dataInicio = new Date();
            dataInicio.setHours(parseInt(horas), parseInt(minutos), 0, 0);
            
            const dataFim = new Date(dataInicio.getTime() + duracaoMinutos * 60000);
            const horaFim = dataFim.toTimeString().slice(0, 5); // Formato HH:MM
            
            // Atualizar input hidden e display
            inputHoraFim.value = horaFim;
            displayHoraFim.textContent = horaFim;
            
            console.log('✅ [HORÁRIO] Hora fim calculada:', horaFim, '- Duração:', duracaoMinutos, 'minutos');
        } else {
            inputHoraFim.value = '';
            displayHoraFim.textContent = '--:--';
        }
    }
}

// Anexar listeners para calcular a hora de fim automaticamente
function anexarAutoCalculoHoraFim() {
    const campoInicio = document.getElementById('editHoraInicio');
    if (!campoInicio) return;
    
    const handler = () => {
        console.log('🔄 [HORÁRIO] Evento disparado, calculando hora fim...');
        calcularHoraFimAuto();
    };
    
    // Remover listeners antigos
    campoInicio.removeEventListener('change', handler);
    campoInicio.removeEventListener('blur', handler);
    campoInicio.removeEventListener('input', handler);
    
    // Adicionar novos listeners
    campoInicio.addEventListener('change', handler);
    campoInicio.addEventListener('blur', handler);
    campoInicio.addEventListener('input', handler);
    
    console.log('✅ [HORÁRIO] Listeners anexados para cálculo automático');
}

// Validação automática de horários
function validarHorarios() {
    const horaInicio = document.getElementById('editHoraInicio');
    const inputHoraFim = document.getElementById('editHoraFim');
    
    if (horaInicio && inputHoraFim) {
        // Calcular hora de fim automaticamente quando hora início mudar
        horaInicio.addEventListener('change', calcularHoraFimAuto);
    }
}

// Funções antigas removidas - usando calcularHoraFimAuto() agora

// Melhorar feedback visual
function mostrarLoading(button) {
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Processando...';
    button.disabled = true;
    
    return function() {
        button.innerHTML = originalText;
        button.disabled = false;
    };
}

// Carregar dados para os selects quando o modal for aberto
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 [DEBUG] Iniciando carregamento de dados...');
    
    // Carregar instrutores
    console.log('🔧 [DEBUG] Carregando instrutores...');
    fetch('api/instrutores-real.php')
        .then(response => {
            console.log('🔧 [DEBUG] Resposta instrutores:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('🔧 [DEBUG] Dados instrutores:', data);
            if (data.success) {
                const selectInstrutor = document.getElementById('editInstrutor');
                if (selectInstrutor) {
                    selectInstrutor.innerHTML = '<option value="">Selecione um instrutor</option>';
                    data.instrutores.forEach(instrutor => {
                        const option = document.createElement('option');
                        option.value = instrutor.id;
                        option.textContent = instrutor.nome;
                        selectInstrutor.appendChild(option);
                    });
                    console.log('✅ [DEBUG] Instrutores carregados:', data.instrutores.length);
                } else {
                    console.log('❌ [DEBUG] Select instrutor não encontrado');
                }
            } else {
                console.log('❌ [DEBUG] Erro ao carregar instrutores:', data.message);
            }
        })
        .catch(error => {
            console.error('❌ [DEBUG] Erro ao carregar instrutores:', error);
        });
    
    // Carregar salas
    console.log('🔧 [DEBUG] Carregando salas...');
    fetch('api/salas-real.php')
        .then(response => {
            console.log('🔧 [DEBUG] Resposta salas:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('🔧 [DEBUG] Dados salas:', data);
            if (data.success) {
                const selectSala = document.getElementById('editSala');
                if (selectSala) {
                    selectSala.innerHTML = '<option value="">Selecione uma sala</option>';
                    data.salas.forEach(sala => {
                        const option = document.createElement('option');
                        option.value = sala.id;
                        option.textContent = sala.nome;
                        selectSala.appendChild(option);
                    });
                    console.log('✅ [DEBUG] Salas carregadas:', data.salas.length);
                } else {
                    console.log('❌ [DEBUG] Select sala não encontrado');
                }
            } else {
                console.log('❌ [DEBUG] Erro ao carregar salas:', data.message);
            }
        })
        .catch(error => {
            console.error('❌ [DEBUG] Erro ao carregar salas:', error);
        });
    
    // Configurar validações
    validarHorarios();
});

// Função para abrir modal de inserir alunos
function abrirModalInserirAlunos() {
    const turmaId = <?= $turmaId ?>;
    
    // Verificar se o modal existe antes de tentar acessá-lo
    const modal = document.getElementById('modalInserirAlunos');
    if (!modal) {
        console.error('Modal modalInserirAlunos não encontrado');
        mostrarMensagem('Erro: Modal não encontrado', 'error');
        return;
    }
    
    // Mostrar modal
    modal.style.display = 'flex';
    
    // Carregar alunos aptos
    carregarAlunosAptos(turmaId);
}

// Função para fechar modal
function fecharModalInserirAlunos() {
    const modal = document.getElementById('modalInserirAlunos');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Função para carregar alunos aptos
function carregarAlunosAptos(turmaId) {
    const container = document.getElementById('listaAlunosAptos');
    const loading = document.getElementById('loadingAlunos');
    
    // Mostrar loading
    loading.style.display = 'block';
    container.innerHTML = '';
    
    // Fazer requisição para buscar alunos aptos
    fetch('api/alunos-aptos-turma-simples.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin', // Incluir cookies de sessão
        body: JSON.stringify({
            turma_id: turmaId
        })
    })
    .then(response => {
        console.log('Resposta da API:', response.status, response.statusText);
        return response.json();
    })
    .then(data => {
        loading.style.display = 'none';
        console.log('Dados recebidos:', data);
        
        if (data.sucesso) {
            exibirAlunosAptos(data.alunos, turmaId, data.debug_info);
        } else {
            container.innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    ${data.mensagem || 'Erro ao carregar alunos aptos'}
                    ${data.debug ? '<br><small>Debug: ' + JSON.stringify(data.debug) + '</small>' : ''}
                </div>
            `;
        }
    })
    .catch(error => {
        loading.style.display = 'none';
        console.error('Erro na requisição:', error);
        container.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                Erro ao carregar alunos: ${error.message}
                <br><small>Verifique o console para mais detalhes</small>
            </div>
        `;
    });
}

// Função para exibir alunos aptos
function exibirAlunosAptos(alunos, turmaId, debugInfo = null) {
    const container = document.getElementById('listaAlunosAptos');
    
    if (alunos.length === 0) {
        let debugHtml = '';
        if (debugInfo) {
            debugHtml = `
                <div class="alert alert-warning mt-2">
                    <i class="fas fa-bug"></i>
                    <strong>Debug Info:</strong><br>
                    CFC da Turma: ${debugInfo.turma_cfc_id}<br>
                    CFC da Sessão: ${debugInfo.session_cfc_id}<br>
                    CFCs coincidem: ${debugInfo.cfc_ids_match ? 'Sim' : 'Não'}<br>
                </div>
            `;
        }
        
        container.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Nenhum aluno encontrado com exames médico e psicotécnico aprovados.
                ${debugHtml}
            </div>
        `;
        return;
    }
    
    let html = `
        <div class="alunos-grid">
            ${alunos.map(aluno => `
                <div class="aluno-card" data-aluno-id="${aluno.id}">
                    <div class="aluno-info">
                        <h4>${aluno.nome}</h4>
                        <p><strong>CPF:</strong> ${aluno.cpf}</p>
                        <p><strong>Categoria:</strong> ${aluno.categoria_cnh}</p>
                        <p><strong>CFC:</strong> ${aluno.cfc_nome}</p>
                    </div>
                    <div class="exames-status">
                        <div class="exame-status apto">
                            <i class="fas fa-user-md"></i>
                            <span>Médico: Apto</span>
                        </div>
                        <div class="exame-status apto">
                            <i class="fas fa-brain"></i>
                            <span>Psicotécnico: Apto</span>
                        </div>
                    </div>
                    <div class="aluno-actions">
                        <button class="btn btn-success btn-sm" onclick="matricularAluno(${aluno.id}, ${turmaId})">
                            <i class="fas fa-user-plus"></i>
                            Matricular
                        </button>
                    </div>
                </div>
            `).join('')}
        </div>
    `;
    
    container.innerHTML = html;
}

// Função para matricular aluno
function matricularAluno(alunoId, turmaId) {
    if (!confirm('Deseja realmente matricular este aluno na turma?')) {
        return;
    }
    
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Matriculando...';
    button.disabled = true;
    
    fetch('api/matricular-aluno-turma.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin', // Incluir cookies de sessão
        body: JSON.stringify({
            aluno_id: alunoId,
            turma_id: turmaId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            // Remover aluno da lista
            const alunoCard = document.querySelector(`[data-aluno-id="${alunoId}"]`);
            if (alunoCard) {
                alunoCard.remove();
            }
            
            // Mostrar mensagem de sucesso
            mostrarMensagem('success', data.mensagem);
            
            // Atualizar contador de alunos na página principal
            atualizarContadorAlunos();
            
        } else {
            mostrarMensagem('error', data.mensagem);
        }
    })
    .catch(error => {
        mostrarMensagem('error', 'Erro ao matricular aluno: ' + error.message);
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Função para mostrar mensagens
function mostrarMensagem(mensagem, tipo) {
    const alertClass = tipo === 'success' ? 'alert-success' : 
                     tipo === 'error' ? 'alert-danger' : 
                     tipo === 'info' ? 'alert-info' : 'alert-warning';
    const icon = tipo === 'success' ? 'fa-check-circle' : 
                tipo === 'error' ? 'fa-exclamation-circle' : 
                tipo === 'info' ? 'fa-info-circle' : 'fa-exclamation-triangle';
    
    const mensagemDiv = document.createElement('div');
    mensagemDiv.className = `alert ${alertClass} alert-dismissible fade show`;
    mensagemDiv.innerHTML = `
        <i class="fas ${icon}"></i>
        ${mensagem}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Inserir no topo da página principal
    const mainContent = document.querySelector('.main-content') || document.querySelector('.container-fluid') || document.body;
    mainContent.insertBefore(mensagemDiv, mainContent.firstChild);
    
    // Remover após 5 segundos
    setTimeout(() => {
        if (mensagemDiv.parentNode) {
            mensagemDiv.remove();
        }
    }, 5000);
}

// Função para atualizar contador de alunos na página principal
function atualizarContadorAlunos() {
    // Recarregar a página para atualizar os dados
    setTimeout(() => {
        window.location.reload();
    }, 2000);
}

// Funções para ações dos alunos matriculados
function visualizarAluno(alunoId) {
    // Redirecionar para página de detalhes do aluno
    window.open(`?page=alunos&acao=detalhes&id=${alunoId}`, '_blank');
}

function editarMatricula(matriculaId) {
    // Implementar modal de edição de matrícula
    mostrarMensagem('Funcionalidade de edição de matrícula será implementada em breve.', 'info');
}

function removerMatricula(matriculaId, nomeAluno) {
    if (confirm(`Tem certeza que deseja remover a matrícula de ${nomeAluno} desta turma?\n\nEsta ação irá desvincular completamente o aluno da turma e ele ficará disponível para matrícula em outras turmas.`)) {
        // Mostrar indicador de carregamento
        mostrarMensagem('Removendo aluno da turma...', 'info');
        
        // Criar dados para enviar
        const formData = new FormData();
        formData.append('acao', 'remover_aluno');
        formData.append('aluno_id', matriculaId);
        formData.append('ajax', 'true');
        
        // Fazer requisição AJAX
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                // Mostrar mensagem de sucesso
                mostrarMensagem(data.mensagem, 'success');
                
                // Remover a linha da tabela
                const linha = document.querySelector(`button[onclick*="${matriculaId}"]`).closest('tr');
                if (linha) {
                    linha.remove();
                }
                
                // Atualizar contador de alunos se existir
                const contadorAlunos = document.querySelector('.btn-primary');
                if (contadorAlunos) {
                    const textoAtual = contadorAlunos.textContent;
                    const numeroAtual = parseInt(textoAtual.match(/\d+/)[0]);
                    contadorAlunos.textContent = textoAtual.replace(/\d+/, numeroAtual - 1);
                }
            } else {
                // Mostrar mensagem de erro
                mostrarMensagem(data.mensagem, 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarMensagem('Erro ao remover aluno. Tente novamente.', 'error');
        });
    }
}

function enviarAgendamentoModal() {
    const form = document.getElementById('formAgendarAulaModal');
    const formData = new FormData(form);
    
    // Validar campos
    if (!formData.get('instrutor_id') || !formData.get('data_aula') || !formData.get('hora_inicio')) {
        mostrarMensagemModal('Preencha todos os campos obrigatórios.', 'error');
        return;
    }
    
    const btnAgendar = document.getElementById('btnAgendarAula');
    btnAgendar.disabled = true;
    btnAgendar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agendando...';
    
    // Enviar via AJAX
    fetch(getBasePath() + '/admin/api/turmas-teoricas.php', {
        method: 'POST',
        body: new URLSearchParams(Object.fromEntries(formData)),
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(async data => {
        if (data.sucesso) {
            mostrarMensagemModal('✅ ' + data.mensagem, 'success');
            
            // Buscar os agendamentos criados para renderizar na tabela
            const ids = (data.aulas_agendadas || []).join(',');
            const disciplinaId = formData.get('disciplina');
            if (ids) {
                try {
                    const res = await fetch(getBasePath() + '/admin/api/agendamentos-por-ids.php?ids=' + ids);
                    const json = await res.json();
                    if (json.success) {
                        inserirAgendamentosNaTabela(disciplinaId, json.data);
                    }
                } catch (e) {
                    console.error('Erro ao buscar agendamentos recém-criados:', e);
                }
            }
            
            // Fechar modal e resetar botão
            fecharModalAgendarAula();
            btnAgendar.disabled = false;
            btnAgendar.innerHTML = '<i class="fas fa-plus"></i> ➕ Agendar Aula(s)';
        } else {
            mostrarMensagemModal('❌ ' + (data.mensagem || 'Erro ao agendar aula. Tente novamente.'), 'error');
            btnAgendar.disabled = false;
            btnAgendar.innerHTML = '<i class="fas fa-plus"></i> ➕ Agendar Aula(s)';
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mostrarMensagemModal('❌ Erro ao processar agendamento. Tente novamente.', 'error');
        btnAgendar.disabled = false;
        btnAgendar.innerHTML = '<i class="fas fa-plus"></i> ➕ Agendar Aula(s)';
    });
}

</script>

<style>
/* Estilos para o modal de inserir alunos */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.modal-content {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    max-width: 800px;
    width: 90%;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f8f9fa;
}

.modal-header h3 {
    margin: 0;
    color: #333;
    font-size: 18px;
    font-weight: 600;
}

.btn-close {
    background: none;
    border: none;
    font-size: 18px;
    color: #6c757d;
    cursor: pointer;
    padding: 5px;
    border-radius: 4px;
    transition: all 0.3s;
}

.btn-close:hover {
    background: #e9ecef;
    color: #495057;
}

.modal-body {
    padding: 20px;
    flex: 1;
    overflow-y: auto;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #e5e7eb;
    background: #f8f9fa;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
}

/* Responsividade para modal de agendamento */
@media (max-width: 768px) {
    .modal-content {
        width: 95%;
        max-width: 95%;
    }
    
    .modal-footer {
        flex-direction: column-reverse;
    }
    
    .modal-footer .btn {
        width: 100%;
    }
    
    #modalAgendarAula .modal-body > div[style*="grid"] {
        grid-template-columns: 1fr !important;
    }
}

/* Grid de alunos */
.alunos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.aluno-card {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 15px;
    background: white;
    transition: all 0.3s;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.aluno-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
}

.aluno-info h4 {
    margin: 0 0 10px 0;
    color: #333;
    font-size: 16px;
    font-weight: 600;
}

.aluno-info p {
    margin: 5px 0;
    color: #666;
    font-size: 14px;
}

.exames-status {
    margin: 15px 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.exame-status {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 10px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 500;
}

.exame-status.apto {
    background: #d4edda;
    color: #155724;
}

.exame-status.inapto {
    background: #f8d7da;
    color: #721c24;
}

.aluno-actions {
    margin-top: 15px;
    text-align: right;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #218838;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

/* Responsividade */
@media (max-width: 768px) {
    .modal-content {
        width: 95%;
        margin: 10px;
    }
    
    .alunos-grid {
        grid-template-columns: 1fr;
    }
    
    .modal-header,
    .modal-body,
    .modal-footer {
        padding: 15px;
    }
}

/* Alertas */
.alert {
    padding: 12px 16px;
    border-radius: 4px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.alert-info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.alert-warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

/* Melhorar visibilidade dos botões de ação */
.btn-group .btn-outline-primary {
    border-width: 2px !important;
    font-weight: 500 !important;
    min-width: 32px !important;
    height: 32px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    transition: all 0.2s ease !important;
    background: white !important;
    border-color: #0d6efd !important;
    color: #0d6efd !important;
}

.btn-group .btn-outline-primary:hover {
    background-color: #0d6efd !important;
    border-color: #0d6efd !important;
    color: white !important;
    transform: scale(1.05) !important;
}

.btn-group .btn-outline-primary:hover span {
    color: white !important;
}

/* Melhorar contraste do botão de cancelar */
.btn-group .btn-outline-danger {
    border-width: 2px;
    font-weight: 500;
    min-width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-group .btn-outline-danger:hover {
    background-color: #dc3545;
    border-color: #dc3545;
    color: white;
    transform: scale(1.05);
}

/* Adicionar tooltip melhorado */
.btn-group .btn[title] {
    position: relative;
}

.btn-group .btn[title]:hover::after {
    content: attr(title);
    position: absolute;
    bottom: -35px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    z-index: 1000;
    pointer-events: none;
}
</style>

<!-- Script simples para garantir visibilidade do ícone -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 [DEBUG] Página carregada, verificando botões de edição...');
    
    // Garantir que todos os botões de edição tenham ícone visível
    const editButtons = document.querySelectorAll('.btn-outline-primary');
    console.log('🔧 [DEBUG] Encontrados', editButtons.length, 'botões de edição');
    
    editButtons.forEach((button, index) => {
        const span = button.querySelector('span');
        if (span) {
            console.log('✅ [DEBUG] Botão', index + 1, 'tem ícone:', span.textContent);
        } else {
            console.log('❌ [DEBUG] Botão', index + 1, 'NÃO tem ícone');
        }
    });
});
</script>

<!-- JavaScript para Modal de Agendamento -->
<script>
// Variáveis globais para o modal
let turmaIdModal = <?= $turmaId ?>;
let dataInicioModal = '';
let dataFimModal = '';

// Função para abrir o modal de agendamento
function abrirModalAgendarAula(disciplinaId, disciplinaNome, dataInicio, dataFim) {
    try {
        turmaIdModal = <?= $turmaId ?>;
        dataInicioModal = dataInicio;
        dataFimModal = dataFim;
        
        // Preencher os campos do modal com verificação de existência
        const modalDisciplinaId = document.getElementById('modal_disciplina_id');
        const modalDisciplinaNome = document.getElementById('modal_disciplina_nome');
        const modalDataAula = document.getElementById('modal_data_aula');
        const modalInstrutorId = document.getElementById('modal_instrutor_id');
        const modalHoraInicio = document.getElementById('modal_hora_inicio');
        const modalQuantidadeAulas = document.getElementById('modal_quantidade_aulas');
        const previewHorario = document.getElementById('previewHorarioModal');
        const alertaConflitos = document.getElementById('alertaConflitosModal');
        const mensagemAgendamento = document.getElementById('mensagemAgendamento');
        const btnAgendar = document.getElementById('btnAgendarAula');
        const modal = document.getElementById('modalAgendarAula');
        
        if (!modal) {
            console.error('Modal não encontrado!');
            alert('Erro: Modal de agendamento não encontrado. Recarregue a página.');
            return;
        }
        
        // Preencher campos se existirem
        if (modalDisciplinaId) modalDisciplinaId.value = disciplinaId;
        if (modalDisciplinaNome) modalDisciplinaNome.value = disciplinaNome;
        if (modalDataAula) {
            // Definir limites de data baseado no período da turma
            modalDataAula.min = dataInicio;
            modalDataAula.max = dataFim;
            modalDataAula.value = '';
            
            // Log para debug
            console.log('🔧 [DEBUG] Limites do calendário configurados:', {
                min: dataInicio,
                max: dataFim,
                dataInicioFormatada: new Date(dataInicio + 'T00:00:00').toLocaleDateString('pt-BR'),
                dataFimFormatada: new Date(dataFim + 'T00:00:00').toLocaleDateString('pt-BR')
            });
            
            // Aviso se a data fim está muito próxima
            const hoje = new Date();
            const dataFimDate = new Date(dataFim + 'T00:00:00');
            const diasRestantes = Math.ceil((dataFimDate - hoje) / (1000 * 60 * 60 * 24));
            
            if (diasRestantes <= 7 && diasRestantes > 0) {
                console.warn('⚠️ [AVISO] Restam apenas', diasRestantes, 'dias no período da turma');
            }
        }
        
        // Limpar campos e mensagens
        if (modalInstrutorId) modalInstrutorId.value = '';
        if (modalHoraInicio) modalHoraInicio.value = '';
        if (modalQuantidadeAulas) modalQuantidadeAulas.value = '2';
        if (previewHorario) previewHorario.style.display = 'none';
        if (alertaConflitos) alertaConflitos.style.display = 'none';
        if (mensagemAgendamento) mensagemAgendamento.style.display = 'none';
        if (btnAgendar) btnAgendar.disabled = true;
        
        // Mostrar modal
        modal.style.display = 'flex';
    } catch (error) {
        console.error('Erro ao abrir modal:', error);
        alert('Erro ao abrir modal de agendamento. Verifique o console para mais detalhes.');
    }
}

// Função para fechar o modal
function fecharModalAgendarAula() {
    document.getElementById('modalAgendarAula').style.display = 'none';
}

// Fechar modal ao clicar fora dele
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modalAgendarAula');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalAgendarAula();
            }
        });
    }
});

// Atualizar preview quando campos mudarem
function atualizarPreviewModal() {
    const disciplinaNome = document.getElementById('modal_disciplina_nome').value;
    const dataAula = document.getElementById('modal_data_aula').value;
    const horaInicio = document.getElementById('modal_hora_inicio').value;
    const qtdAulas = parseInt(document.getElementById('modal_quantidade_aulas').value);
    
    if (dataAula && horaInicio && qtdAulas) {
        const data = new Date(dataAula + 'T00:00:00');
        const dataFormatada = data.toLocaleDateString('pt-BR');
        
        // Calcular horários das aulas
        let horariosPreview = [];
        for (let i = 0; i < qtdAulas; i++) {
            const [horas, minutos] = horaInicio.split(':').map(Number);
            const inicioMinutos = (horas * 60) + minutos + (i * 50);
            const fimMinutos = inicioMinutos + 50;
            
            const horaInicioAula = String(Math.floor(inicioMinutos / 60)).padStart(2, '0') + ':' + 
                                 String(inicioMinutos % 60).padStart(2, '0');
            const horaFimAula = String(Math.floor(fimMinutos / 60)).padStart(2, '0') + ':' + 
                               String(fimMinutos % 60).padStart(2, '0');
            
            horariosPreview.push(`${horaInicioAula} - ${horaFimAula}`);
        }
        
        document.getElementById('previewContentModal').innerHTML = `
            <strong>${disciplinaNome}</strong><br>
            📅 ${dataFormatada}<br>
            🕐 ${horariosPreview.join(', ')}<br>
            🎓 ${qtdAulas} aula(s) de 50 minutos cada
        `;
        document.getElementById('previewHorarioModal').style.display = 'block';
    } else {
        document.getElementById('previewHorarioModal').style.display = 'none';
    }
}

// Event listeners para atualizar preview
document.addEventListener('DOMContentLoaded', function() {
    const dataAula = document.getElementById('modal_data_aula');
    const horaInicio = document.getElementById('modal_hora_inicio');
    const qtdAulas = document.getElementById('modal_quantidade_aulas');
    const instrutorId = document.getElementById('modal_instrutor_id');
    
    if (dataAula) {
        dataAula.addEventListener('change', function() {
            atualizarPreviewModal();
            // Verificar disponibilidade automaticamente se todos os campos estiverem preenchidos
            verificarDisponibilidadeAuto();
        });
    }
    
    if (horaInicio) {
        horaInicio.addEventListener('change', function() {
            atualizarPreviewModal();
            verificarDisponibilidadeAuto();
        });
    }
    
    if (qtdAulas) {
        qtdAulas.addEventListener('change', function() {
            atualizarPreviewModal();
            verificarDisponibilidadeAuto();
        });
    }
    
    if (instrutorId) {
        instrutorId.addEventListener('change', function() {
            verificarDisponibilidadeAuto();
        });
    }
});

// Verificação automática de disponibilidade (sem mostrar mensagem, apenas habilitar/desabilitar botão)
let timeoutVerificacao = null;
function verificarDisponibilidadeAuto() {
    // Cancelar verificação anterior se ainda estiver aguardando
    if (timeoutVerificacao) {
        clearTimeout(timeoutVerificacao);
    }
    
    // Aguardar 500ms após o usuário parar de digitar
    timeoutVerificacao = setTimeout(() => {
        const instrutor = document.getElementById('modal_instrutor_id')?.value;
        const dataAula = document.getElementById('modal_data_aula')?.value;
        const horaInicio = document.getElementById('modal_hora_inicio')?.value;
        const disciplinaId = document.getElementById('modal_disciplina_id')?.value;
        const quantidadeAulas = document.getElementById('modal_quantidade_aulas')?.value || 1;
        
        const btnAgendar = document.getElementById('btnAgendarAula');
        
        // Se todos os campos obrigatórios estão preenchidos, verificar
        if (instrutor && dataAula && horaInicio && disciplinaId) {
            const params = new URLSearchParams({
                acao: 'verificar_conflitos',
                turma_id: turmaIdModal,
                disciplina: disciplinaId,
                instrutor_id: instrutor,
                data_aula: dataAula,
                hora_inicio: horaInicio,
                quantidade_aulas: quantidadeAulas
            });
            
            fetch(getBasePath() + '/admin/api/turmas-teoricas.php?' + params.toString(), {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (btnAgendar) {
                    if (data.sucesso && data.disponivel) {
                        btnAgendar.disabled = false;
                    } else {
                        btnAgendar.disabled = true;
                    }
                }
            })
            .catch(error => {
                console.error('Erro na verificação automática:', error);
                if (btnAgendar) btnAgendar.disabled = true;
            });
        } else {
            // Se campos não estão completos, desabilitar botão
            if (btnAgendar) btnAgendar.disabled = true;
        }
    }, 500);
}

// Função para verificar disponibilidade
function verificarDisponibilidadeModal() {
    const instrutor = document.getElementById('modal_instrutor_id').value;
    const dataAula = document.getElementById('modal_data_aula').value;
    const horaInicio = document.getElementById('modal_hora_inicio').value;
    const disciplinaId = document.getElementById('modal_disciplina_id').value;
    const quantidadeAulas = document.getElementById('modal_quantidade_aulas').value || 1;
    
    if (!instrutor || !dataAula || !horaInicio || !disciplinaId) {
        mostrarMensagemModal('❌ Preencha todos os campos obrigatórios antes de verificar conflitos.', 'error');
        return;
    }
    
    const btnVerificar = document.getElementById('btnVerificarDisponibilidade');
    const btnAgendar = document.getElementById('btnAgendarAula');
    const alertaConflitos = document.getElementById('alertaConflitosModal');
    
    btnVerificar.disabled = true;
    btnVerificar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
    btnAgendar.disabled = true;
    
    // Limpar mensagens anteriores
    if (alertaConflitos) alertaConflitos.style.display = 'none';
    mostrarMensagemModal('', '');
    
    // Construir URL com parâmetros
    const params = new URLSearchParams({
        acao: 'verificar_conflitos',
        turma_id: turmaIdModal,
        disciplina: disciplinaId,
        instrutor_id: instrutor,
        data_aula: dataAula,
        hora_inicio: horaInicio,
        quantidade_aulas: quantidadeAulas
    });
    
    console.log('🔧 [DEBUG] Verificando disponibilidade:', params.toString());
    
    // Chamar API real de verificação
    fetch(getBasePath() + '/admin/api/turmas-teoricas.php?' + params.toString(), {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        console.log('🔧 [DEBUG] Status da resposta:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('🔧 [DEBUG] Resultado da verificação:', data);
        
        btnVerificar.disabled = false;
        btnVerificar.innerHTML = '<i class="fas fa-search"></i> 🔍 Verificar Disponibilidade';
        
        if (data.sucesso && data.disponivel) {
            // Horário disponível
            if (alertaConflitos) alertaConflitos.style.display = 'none';
            btnAgendar.disabled = false;
            mostrarMensagemModal('✅ ' + (data.mensagem || 'Horário disponível! Você pode agendar as aulas.'), 'success');
        } else {
            // Há conflitos
            if (alertaConflitos) {
                let mensagemConflitos = '<strong>⚠️ Conflitos detectados:</strong><ul style="margin: 10px 0; padding-left: 20px;">';
                
                if (data.detalhes && data.detalhes.length > 0) {
                    data.detalhes.forEach(conflito => {
                        mensagemConflitos += '<li>' + conflito + '</li>';
                    });
                } else if (data.conflitos && data.conflitos.length > 0) {
                    data.conflitos.forEach(conflito => {
                        mensagemConflitos += '<li>' + conflito.mensagem + '</li>';
                    });
                } else {
                    mensagemConflitos += '<li>' + (data.mensagem || 'Conflito detectado') + '</li>';
                }
                
                mensagemConflitos += '</ul>';
                alertaConflitos.innerHTML = mensagemConflitos;
                alertaConflitos.style.display = 'block';
            }
            
            btnAgendar.disabled = true;
            mostrarMensagemModal('❌ ' + (data.mensagem || 'Conflito de horário detectado. Verifique os detalhes acima.'), 'error');
        }
    })
    .catch(error => {
        console.error('❌ [DEBUG] Erro ao verificar disponibilidade:', error);
        btnVerificar.disabled = false;
        btnVerificar.innerHTML = '<i class="fas fa-search"></i> 🔍 Verificar Disponibilidade';
        btnAgendar.disabled = true;
        mostrarMensagemModal('❌ Erro ao verificar disponibilidade. Tente novamente.', 'error');
    });
}

// Função para enviar agendamento
function enviarAgendamentoModal() {
    const form = document.getElementById('formAgendarAulaModal');
    const formData = new FormData(form);
    
    // Validar campos
    if (!formData.get('instrutor_id') || !formData.get('data_aula') || !formData.get('hora_inicio')) {
        mostrarMensagemModal('❌ Preencha todos os campos obrigatórios.', 'error');
        return;
    }
    
    // Verificar disponibilidade antes de agendar (última verificação)
    const instrutor = formData.get('instrutor_id');
    const dataAula = formData.get('data_aula');
    const horaInicio = formData.get('hora_inicio');
    const disciplinaId = formData.get('disciplina');
    const quantidadeAulas = formData.get('quantidade_aulas') || 1;
    
    const btnAgendar = document.getElementById('btnAgendarAula');
    btnAgendar.disabled = true;
    btnAgendar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
    
    // Primeiro verificar conflitos antes de agendar
    const params = new URLSearchParams({
        acao: 'verificar_conflitos',
        turma_id: turmaIdModal,
        disciplina: disciplinaId,
        instrutor_id: instrutor,
        data_aula: dataAula,
        hora_inicio: horaInicio,
        quantidade_aulas: quantidadeAulas
    });
    
    // Verificar disponibilidade antes de agendar
    fetch(getBasePath() + '/admin/api/turmas-teoricas.php?' + params.toString(), {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(verificacao => {
        console.log('🔧 [DEBUG] Verificação antes de agendar:', verificacao);
        
        if (!verificacao.sucesso || !verificacao.disponivel) {
            // Há conflitos, não permitir agendamento
            btnAgendar.disabled = true;
            btnAgendar.innerHTML = '<i class="fas fa-plus"></i> ➕ Agendar Aula(s)';
            
            const alertaConflitos = document.getElementById('alertaConflitosModal');
            if (alertaConflitos) {
                let mensagemConflitos = '<strong>⚠️ Conflitos detectados:</strong><ul style="margin: 10px 0; padding-left: 20px;">';
                
                if (verificacao.detalhes && verificacao.detalhes.length > 0) {
                    verificacao.detalhes.forEach(conflito => {
                        mensagemConflitos += '<li>' + conflito + '</li>';
                    });
                } else if (verificacao.conflitos && verificacao.conflitos.length > 0) {
                    verificacao.conflitos.forEach(conflito => {
                        mensagemConflitos += '<li>' + conflito.mensagem + '</li>';
                    });
                } else {
                    mensagemConflitos += '<li>' + (verificacao.mensagem || 'Conflito detectado') + '</li>';
                }
                
                mensagemConflitos += '</ul>';
                alertaConflitos.innerHTML = mensagemConflitos;
                alertaConflitos.style.display = 'block';
            }
            
            mostrarMensagemModal('❌ ' + (verificacao.mensagem || 'Não é possível agendar devido a conflitos de horário. Verifique os detalhes acima.'), 'error');
            return;
        }
        
        // Se passou na verificação, proceder com o agendamento
        btnAgendar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agendando...';
        
        // Enviar via AJAX
        fetch(getBasePath() + '/admin/api/turmas-teoricas.php', {
            method: 'POST',
            body: new URLSearchParams(Object.fromEntries(formData)),
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            console.log('🔧 [DEBUG] Status da resposta:', response.status);
            return response.json();
        })
        .then(async data => {
            console.log('🔧 [DEBUG] Dados recebidos do servidor:', data);
            
            if (data.sucesso) {
                mostrarMensagemModal('✅ ' + data.mensagem, 'success');
                
                // Buscar os agendamentos criados para renderizar na tabela
                const ids = (data.aulas_agendadas || []).join(',');
                const disciplinaId = formData.get('disciplina');
                
                console.log('🔧 [DEBUG] IDs retornados:', ids);
                console.log('🔧 [DEBUG] Disciplina ID:', disciplinaId);
                
                // Sempre recarregar do servidor para garantir ordem correta e evitar duplicatas
                if (disciplinaId) {
                    console.log('🔄 [DEBUG] Recarregando agendamentos da disciplina após agendamento bem-sucedido');
                    recarregarAgendamentosDisciplina(disciplinaId);
                    
                    // Atualizar estatísticas após um pequeno delay para garantir que o recarregamento terminou
                    setTimeout(() => {
                        atualizarEstatisticasTurma();
                    }, 800);
                } else {
                    console.warn('⚠️ [DEBUG] Disciplina ID não disponível, não é possível recarregar agendamentos');
                    // Mesmo sem disciplinaId, atualizar estatísticas gerais
                    setTimeout(() => {
                        atualizarEstatisticasTurma();
                    }, 500);
                }
                
                // Fechar modal e resetar botão
                setTimeout(() => {
                    fecharModalAgendarAula();
                }, 500);
                btnAgendar.disabled = false;
                btnAgendar.innerHTML = '<i class="fas fa-plus"></i> ➕ Agendar Aula(s)';
            } else {
                mostrarMensagemModal('❌ ' + (data.mensagem || 'Erro ao agendar aula. Tente novamente.'), 'error');
                btnAgendar.disabled = false;
                btnAgendar.innerHTML = '<i class="fas fa-plus"></i> ➕ Agendar Aula(s)';
            }
        })
        .catch(error => {
            console.error('❌ [DEBUG] Erro ao enviar agendamento:', error);
            mostrarMensagemModal('❌ Erro ao processar agendamento. Tente novamente.', 'error');
            btnAgendar.disabled = false;
            btnAgendar.innerHTML = '<i class="fas fa-plus"></i> ➕ Agendar Aula(s)';
        });
    });
}

// Inserir novas linhas na tabela da disciplina sem recarregar
function inserirAgendamentosNaTabela(disciplinaId, agendamentos) {
    console.log('🔧 [DEBUG] Inserindo agendamentos na tabela:', { disciplinaId, agendamentos });
    
    // Tentar encontrar o container da disciplina de múltiplas formas
    let container = document.getElementById('detalhes-disciplina-' + disciplinaId);
    if (!container) {
        container = document.getElementById('data-disciplina-' + disciplinaId);
    }
    if (!container) {
        console.error('❌ [DEBUG] Container da disciplina não encontrado:', disciplinaId);
        // Como fallback, recarregar do servidor
        recarregarAgendamentosDisciplina(disciplinaId);
        return;
    }
    
    let tbody = container.querySelector('table tbody');
    
    // Se não houver tbody, pode ser que não haja agendamentos ainda
    if (!tbody) {
        // Verificar se há uma seção de "nenhum agendamento" e substituir
        const historicoSection = container.querySelector('.historico-agendamentos');
        if (historicoSection) {
            // Criar estrutura da tabela
            const tableHTML = `
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-primary">
                            <tr>
                                <th>Aula</th>
                                <th>Data</th>
                                <th>Horário</th>
                                <th>Instrutor</th>
                                <th>Sala</th>
                                <th>Duração</th>
                                <th>Status</th>
                                <th width="100">Ações</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            `;
            // Substituir a seção vazia pela tabela
            const emptySection = historicoSection.querySelector('.text-center');
            if (emptySection) {
                emptySection.remove();
            }
            historicoSection.insertAdjacentHTML('beforeend', tableHTML);
            tbody = historicoSection.querySelector('table tbody');
        } else {
            console.error('❌ [DEBUG] Não foi possível encontrar ou criar tbody');
            recarregarAgendamentosDisciplina(disciplinaId);
            return;
        }
    }
    
    if (!tbody) {
        console.error('❌ [DEBUG] Tbody ainda não encontrado após tentativas');
        recarregarAgendamentosDisciplina(disciplinaId);
        return;
    }
    
    // Verificar agendamentos existentes para evitar duplicatas
    const agendamentosExistentes = Array.from(tbody.querySelectorAll('tr')).map(tr => 
        tr.getAttribute('data-agendamento-id')
    );
    
    // Filtrar apenas agendamentos que ainda não existem
    const novosAgendamentos = agendamentos.filter(aula => 
        !agendamentosExistentes.includes(String(aula.id))
    );
    
    console.log('🔧 [DEBUG] Agendamentos novos a inserir:', novosAgendamentos.length);
    
    if (novosAgendamentos.length === 0) {
        console.log('⚠️ [DEBUG] Todos os agendamentos já existem na tabela, recarregando do servidor para garantir ordem correta');
        recarregarAgendamentosDisciplina(disciplinaId);
        return;
    }
    
    // Ordenar agendamentos para inserir na ordem correta (ordem_disciplina ASC, data_aula ASC, hora_inicio ASC)
    novosAgendamentos.sort((a, b) => {
        // Ordenar por ordem_disciplina
        const ordemA = a.ordem_disciplina || 0;
        const ordemB = b.ordem_disciplina || 0;
        if (ordemA !== ordemB) return ordemA - ordemB;
        
        // Se ordem igual, ordenar por data_aula
        const dataA = new Date(a.data_aula + 'T00:00:00').getTime();
        const dataB = new Date(b.data_aula + 'T00:00:00').getTime();
        if (dataA !== dataB) return dataA - dataB;
        
        // Se data igual, ordenar por hora_inicio
        const horaA = a.hora_inicio || '00:00:00';
        const horaB = b.hora_inicio || '00:00:00';
        return horaA.localeCompare(horaB);
    });
    
    // Inserir cada agendamento na posição correta
    novosAgendamentos.forEach(aula => {
        // Verificar se já existe na tabela antes de inserir
        if (tbody.querySelector(`tr[data-agendamento-id="${aula.id}"]`)) {
            console.log('⚠️ [DEBUG] Agendamento já existe, pulando:', aula.id);
            return;
        }
        
        const tr = document.createElement('tr');
        tr.setAttribute('data-agendamento-id', aula.id);
        
        // Formatar data
        const dataBR = aula.data_aula ? new Date(aula.data_aula + 'T00:00:00').toLocaleDateString('pt-BR') : 'N/A';
        
        // Formatar horário
        const horaInicio = aula.hora_inicio ? (aula.hora_inicio.length >= 5 ? aula.hora_inicio.substring(0, 5) : aula.hora_inicio) : '--:--';
        const horaFim = aula.hora_fim ? (aula.hora_fim.length >= 5 ? aula.hora_fim.substring(0, 5) : aula.hora_fim) : '--:--';
        
        tr.innerHTML = `
            <td><strong>${aula.nome_aula || 'Aula'}</strong></td>
            <td>${dataBR}</td>
            <td>${horaInicio} - ${horaFim}</td>
            <td>${aula.instrutor_nome || 'Não informado'}</td>
            <td>${aula.sala_nome || 'Não informada'}</td>
            <td>${aula.duracao_minutos || 50} min</td>
            <td><span class="badge bg-warning">Agendada</span></td>
            <td>
                <div class="btn-group" role="group">
                    <button type="button" 
                            class="btn btn-sm btn-outline-primary" 
                            onclick="editarAgendamento(${aula.id}, '${(aula.nome_aula || '').replace(/'/g, "\\'")}', '${aula.data_aula}', '${horaInicio}', '${horaFim}', '${aula.instrutor_id || ''}', '${aula.sala_id || ''}', '${aula.duracao_minutos || 50}', '${(aula.observacoes || '').replace(/'/g, "\\'")}')"
                            title="Editar agendamento">
                        <span style="font-size: 14px; font-weight: bold;">✏</span>
                    </button>
                    <button type="button" 
                            class="btn btn-sm btn-outline-danger" 
                            onclick="cancelarAgendamento(${aula.id}, '${(aula.nome_aula || '').replace(/'/g, "\\'")}')"
                            title="Cancelar agendamento">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </td>
        `;
        
        // Encontrar posição correta para inserir (ordem crescente: ordem_disciplina, data_aula, hora_inicio)
        const rows = Array.from(tbody.querySelectorAll('tr'));
        let posicaoInsercao = null;
        
        const ordemAula = aula.ordem_disciplina || 0;
        const dataAula = new Date(aula.data_aula + 'T00:00:00').getTime();
        const horaAula = aula.hora_inicio || '00:00:00';
        
        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const ordemRow = parseInt(row.querySelector('td')?.dataset?.ordem || row.getAttribute('data-ordem') || '0');
            const dataRowStr = row.querySelector('td:nth-child(2)')?.textContent;
            const horaRowStr = row.querySelector('td:nth-child(3)')?.textContent?.split(' - ')[0] || '00:00';
            
            // Se não conseguir obter dados da linha existente, usar atributos data
            const dataRow = dataRowStr ? new Date(dataRowStr.split('/').reverse().join('-') + 'T00:00:00').getTime() : 0;
            
            // Comparar
            if (ordemAula < ordemRow || 
                (ordemAula === ordemRow && dataAula < dataRow) ||
                (ordemAula === ordemRow && dataAula === dataRow && horaAula < horaRowStr)) {
                posicaoInsercao = row;
                break;
            }
        }
        
        // Inserir na posição correta ou no final se não encontrou posição
        if (posicaoInsercao) {
            tbody.insertBefore(tr, posicaoInsercao);
        } else {
            tbody.appendChild(tr);
        }
        
        console.log('✅ [DEBUG] Agendamento inserido na tabela:', aula.id, '- Posição:', posicaoInsercao ? 'antes de ' + posicaoInsercao.getAttribute('data-agendamento-id') : 'final');
    });
    
    console.log('✅ [DEBUG] Total de agendamentos inseridos:', novosAgendamentos.length);
}

// Função para mostrar mensagens no modal
function mostrarMensagemModal(mensagem, tipo) {
    const divMensagem = document.getElementById('mensagemAgendamento');
    divMensagem.textContent = mensagem;
    divMensagem.style.display = 'block';
    
    if (tipo === 'success') {
        divMensagem.style.backgroundColor = '#d4edda';
        divMensagem.style.color = '#155724';
        divMensagem.style.border = '1px solid #c3e6cb';
    } else {
        divMensagem.style.backgroundColor = '#f8d7da';
        divMensagem.style.color = '#721c24';
        divMensagem.style.border = '1px solid #f5c6cb';
    }
    
    // Ocultar após 5 segundos
    setTimeout(() => {
        divMensagem.style.display = 'none';
    }, 5000);
}

// Função para recarregar agendamentos da disciplina do servidor
function recarregarAgendamentosDisciplina(disciplinaId) {
    console.log('🔄 [DEBUG] Recarregando agendamentos da disciplina do servidor:', disciplinaId);
    
    const urlParams = new URLSearchParams(window.location.search);
    const turmaId = urlParams.get('turma_id') || <?= $turmaId ?>;
    
    fetch(getBasePath() + '/admin/api/listar-agendamentos-turma.php?turma_id=' + turmaId + '&disciplina=' + encodeURIComponent(disciplinaId))
        .then(response => response.json())
        .then(data => {
            console.log('🔧 [DEBUG] Agendamentos carregados do servidor:', data);
            if (data.success && data.agendamentos) {
                console.log('✅ [DEBUG] Total de agendamentos encontrados:', data.agendamentos.length);
                // Recriar toda a seção de histórico
                atualizarSecaoHistorico(disciplinaId, data.agendamentos);
                
                // Atualizar estatísticas após atualizar histórico (para garantir sincronização)
                setTimeout(() => {
                    atualizarEstatisticasTurma();
                }, 300);
            } else {
                console.error('❌ [DEBUG] Erro ao carregar agendamentos:', data);
            }
        })
        .catch(error => {
            console.error('❌ [DEBUG] Erro ao buscar agendamentos:', error);
        });
}

// Função para fazer scroll até uma disciplina específica e expandir
function scrollParaDisciplina(disciplinaId) {
    console.log('📍 [DEBUG] Navegando para disciplina:', disciplinaId);
    
    if (!disciplinaId) {
        console.error('❌ [DEBUG] ID da disciplina não fornecido');
        return;
    }
    
    // Primeiro, encontrar o card da disciplina na seção "Disciplinas da Turma" (não o card de estatísticas)
    // O card da disciplina tem a classe "disciplina-cadastrada-card" e também tem data-disciplina-id
    let disciplinaCard = document.querySelector(`.disciplina-cadastrada-card[data-disciplina-id="${disciplinaId}"]`);
    
    // Se não encontrou, tentar busca alternativa (pode estar em uma estrutura diferente)
    if (!disciplinaCard) {
        console.warn('⚠️ [DEBUG] Card da disciplina não encontrado com seletor padrão, tentando alternativas...');
        
        // Tentar buscar pelo ID do container de detalhes
        const detalhesContent = document.getElementById(`detalhes-disciplina-${disciplinaId}`);
        if (detalhesContent) {
            disciplinaCard = detalhesContent.closest('.disciplina-cadastrada-card');
        }
        
        // Se ainda não encontrou, buscar por qualquer elemento com data-disciplina-id que não seja o card de estatísticas
        if (!disciplinaCard) {
            const todosCards = document.querySelectorAll(`[data-disciplina-id="${disciplinaId}"]`);
            for (let card of todosCards) {
                // Se não é o card de estatísticas (stats-card), usar este
                if (!card.id || !card.id.startsWith('stats-card-')) {
                    disciplinaCard = card;
                    console.log('✅ [DEBUG] Card encontrado via busca alternativa');
                    break;
                }
            }
        }
    }
    
    if (!disciplinaCard) {
        console.error('❌ [DEBUG] Disciplina não encontrada após todas as tentativas:', disciplinaId);
        // Mostrar mensagem amigável ao usuário
        alert('Disciplina não encontrada na página. Por favor, recarregue a página.');
        return;
    }
    
    console.log('✅ [DEBUG] Card da disciplina encontrado:', disciplinaCard);
    fazerScrollParaCard(disciplinaCard, disciplinaId);
}

// Função auxiliar para fazer scroll até um card específico
function fazerScrollParaCard(card, disciplinaId) {
    // Calcular posição com offset para melhor visualização
    const cardPosition = card.getBoundingClientRect().top + window.pageYOffset;
    const offsetPosition = cardPosition - 100; // 100px do topo para melhor visualização
    
    // Scroll suave até o card da disciplina
    window.scrollTo({
        top: offsetPosition,
        behavior: 'smooth'
    });
    
    // Aguardar um pouco e expandir a disciplina
    setTimeout(() => {
        // Verificar se já está expandida
        const detalhesContent = document.getElementById(`detalhes-disciplina-${disciplinaId}`);
        if (detalhesContent) {
            if (detalhesContent.style.display === 'none' || getComputedStyle(detalhesContent).display === 'none') {
                // Expandir se estiver fechada
                console.log('📂 [DEBUG] Expandindo disciplina:', disciplinaId);
                toggleSimples(disciplinaId);
            }
        }
        
        // Adicionar destaque visual temporário no card da disciplina
        const originalTransition = card.style.transition;
        const originalBoxShadow = card.style.boxShadow;
        const originalBorder = card.style.border;
        
        card.style.transition = 'all 0.3s ease';
        card.style.boxShadow = '0 4px 20px rgba(2, 58, 141, 0.4)';
        card.style.border = '3px solid #023A8D';
        card.style.borderRadius = '12px';
        
        setTimeout(() => {
            card.style.boxShadow = originalBoxShadow || '';
            card.style.border = originalBorder || '';
            card.style.transition = originalTransition || '';
            card.style.borderRadius = '';
        }, 2500);
        
        // Também adicionar destaque breve no card de estatísticas correspondente (feedback visual)
        const statsCard = document.getElementById(`stats-card-${disciplinaId}`);
        if (statsCard) {
            const originalStatsTransition = statsCard.style.transition;
            const originalStatsBoxShadow = statsCard.style.boxShadow;
            const originalStatsTransform = statsCard.style.transform;
            
            statsCard.style.transition = 'all 0.3s ease';
            statsCard.style.boxShadow = '0 4px 12px rgba(2, 58, 141, 0.4)';
            statsCard.style.transform = 'scale(1.03)';
            
            setTimeout(() => {
                statsCard.style.boxShadow = originalStatsBoxShadow || '';
                statsCard.style.transform = originalStatsTransform || '';
                statsCard.style.transition = originalStatsTransition || '';
            }, 1000);
        }
    }, 400);
}

// Função para encontrar e fazer scroll até a primeira disciplina incompleta
function scrollParaPrimeiraDisciplinaIncompleta() {
    // Procurar por cards de estatísticas que indicam disciplina incompleta (não verde)
    const cardsStats = document.querySelectorAll('.disciplina-stats-card');
    
    for (let card of cardsStats) {
        // Verificar se o card não está completo (verde)
        const style = window.getComputedStyle(card);
        const bgColor = style.backgroundColor;
        
        // Se não for verde (#d4edda), é uma disciplina incompleta
        if (!bgColor.includes('rgb(212, 237, 218)')) {
            // Extrair disciplina ID do onclick
            const onclick = card.getAttribute('onclick');
            const match = onclick?.match(/scrollParaDisciplina\('([^']+)'\)/);
            if (match && match[1]) {
                const disciplinaId = match[1];
                console.log('🎯 Encontrada primeira disciplina incompleta:', disciplinaId);
                
                // Aguardar um pouco para garantir que a página carregou
                setTimeout(() => {
                    scrollParaDisciplina(disciplinaId);
                }, 500);
                return;
            }
        }
    }
    
    // Se não encontrou disciplina incompleta, scroll até a primeira disciplina
    if (cardsStats.length > 0) {
        const primeiroCard = cardsStats[0];
        const onclick = primeiroCard.getAttribute('onclick');
        const match = onclick?.match(/scrollParaDisciplina\('([^']+)'\)/);
        if (match && match[1]) {
            setTimeout(() => {
                scrollParaDisciplina(match[1]);
            }, 500);
        }
    }
}

// Função para atualizar estatísticas da turma dinamicamente
function atualizarEstatisticasTurma() {
    const urlParams = new URLSearchParams(window.location.search);
    const turmaId = urlParams.get('turma_id') || <?= $turmaId ?>;
    
    console.log('📊 [DEBUG] Atualizando estatísticas da turma:', turmaId);
    
    fetch(getBasePath() + '/admin/api/estatisticas-turma.php?turma_id=' + turmaId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('✅ [DEBUG] Estatísticas recebidas:', data);
                
                // Atualizar progresso geral
                atualizarProgressoGeral(data.estatisticas_gerais);
                
                // Atualizar cada disciplina
                if (data.disciplinas) {
                    Object.keys(data.disciplinas).forEach(disciplinaId => {
                        atualizarCardDisciplina(disciplinaId, data.disciplinas[disciplinaId]);
                    });
                }
            } else {
                console.error('❌ [DEBUG] Erro ao buscar estatísticas:', data.message);
            }
        })
        .catch(error => {
            console.error('❌ [DEBUG] Erro ao atualizar estatísticas:', error);
        });
}

// Função para atualizar o card de progresso geral
function atualizarProgressoGeral(stats) {
    // Atualizar percentual
    const percentualEl = document.getElementById('percentual-geral');
    if (percentualEl) {
        percentualEl.textContent = stats.percentual_geral + '%';
    }
    
    // Atualizar texto de total de aulas
    const totalAulasTexto = document.getElementById('total-aulas-texto');
    if (totalAulasTexto) {
        totalAulasTexto.textContent = `${stats.total_aulas_agendadas} de ${stats.total_aulas_obrigatorias} aulas agendadas`;
    }
    
    // Atualizar total realizadas
    const totalRealizadas = document.getElementById('total-realizadas');
    if (totalRealizadas) {
        totalRealizadas.textContent = stats.total_aulas_realizadas;
    }
    
    // Atualizar total faltantes
    const totalFaltantes = document.getElementById('total-faltantes');
    if (totalFaltantes) {
        totalFaltantes.textContent = stats.total_faltantes;
    }
    
    // Atualizar barra de progresso
    const barraProgresso = document.getElementById('barra-progresso-geral');
    if (barraProgresso) {
        barraProgresso.style.width = stats.percentual_geral + '%';
    }
}

// Função para atualizar card de uma disciplina específica
function atualizarCardDisciplina(disciplinaId, stats) {
    const card = document.getElementById('stats-card-' + disciplinaId);
    if (!card) {
        console.warn('⚠️ [DEBUG] Card não encontrado para disciplina:', disciplinaId);
        return;
    }
    
    console.log('📊 [DEBUG] Atualizando card da disciplina:', disciplinaId, stats);
    
    // Calcular percentual
    const percentual = stats.percentual || 0;
    
    // Atualizar valores dentro do card
    const agendadasEl = card.querySelector('.stat-agendadas-valor');
    const realizadasEl = card.querySelector('.stat-realizadas-valor');
    const faltantesEl = card.querySelector('.stat-faltantes-valor');
    const percentualEl = card.querySelector('.stat-percentual-valor');
    const barraProgresso = card.querySelector('.stat-progresso-barra');
    const resumoEl = card.querySelector('.stat-resumo');
    
    if (agendadasEl) agendadasEl.textContent = stats.agendadas;
    if (realizadasEl) realizadasEl.textContent = stats.realizadas;
    if (faltantesEl) faltantesEl.textContent = stats.faltantes;
    if (percentualEl) {
        percentualEl.textContent = percentual + '%';
        // Atualizar cor baseada no novo percentual
        atualizarCoresCardDisciplina(card, percentual, stats);
    }
    if (barraProgresso) {
        barraProgresso.style.width = Math.min(percentual, 100) + '%';
    }
    if (resumoEl) {
        resumoEl.textContent = `${stats.agendadas}/${stats.obrigatorias} aulas (faltam ${stats.faltantes})`;
    }
}

// Função para atualizar cores do card baseado no progresso
function atualizarCoresCardDisciplina(card, percentual, stats) {
    let corCard, bgCard, icon, status;
    
    if (percentual >= 100) {
        corCard = '#28a745';
        bgCard = '#d4edda';
        icon = 'fa-check-circle';
        status = 'Completo';
    } else if (percentual >= 75) {
        corCard = '#ffc107';
        bgCard = '#fff3cd';
        icon = 'fa-exclamation-triangle';
        status = 'Quase completo';
    } else if (stats.agendadas > 0) {
        corCard = '#ffc107';
        bgCard = '#fff3cd';
        icon = 'fa-clock';
        status = 'Em progresso';
    } else {
        corCard = '#dc3545';
        bgCard = '#f8d7da';
        icon = 'fa-times-circle';
        status = 'Não iniciado';
    }
    
    // Atualizar estilos do card
    card.style.background = bgCard;
    card.style.borderLeftColor = corCard;
    
    // Atualizar ícone e status
    const statusDiv = card.querySelector('.stat-status');
    if (statusDiv) {
        statusDiv.innerHTML = `<i class="fas ${icon}"></i> <span style="font-weight: 500;">${status}</span>`;
        statusDiv.style.color = corCard;
    }
    
    // Atualizar cor do percentual
    const percentualEl = card.querySelector('.stat-percentual-valor');
    if (percentualEl) {
        percentualEl.style.color = corCard;
    }
    
    // Atualizar cor da barra de progresso
    const barraProgresso = card.querySelector('.stat-progresso-barra');
    if (barraProgresso) {
        barraProgresso.style.background = corCard;
    }
}

// Verificar se veio do redirecionamento de "Continuar Agendamento"
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const sucesso = urlParams.get('sucesso');
    
    // Se veio com sucesso=1 (criação de turma), fazer scroll até disciplinas
    if (sucesso === '1') {
        console.log('✅ Detectado redirecionamento de criação/agendamento, fazendo scroll automático...');
        setTimeout(() => {
            scrollParaPrimeiraDisciplinaIncompleta();
        }, 1000);
    }
});

// Atualizar seção completa do histórico
function atualizarSecaoHistorico(disciplinaId, agendamentos) {
    const container = document.getElementById('detalhes-disciplina-' + disciplinaId) || 
                     document.getElementById('data-disciplina-' + disciplinaId);
    if (!container) {
        console.error('❌ [DEBUG] Container não encontrado para disciplina:', disciplinaId);
        return;
    }
    
    const historicoSection = container.querySelector('.historico-agendamentos');
    if (!historicoSection) {
        console.error('❌ [DEBUG] Seção de histórico não encontrada');
        return;
    }
    
    // Remover tabela existente
    const oldTable = historicoSection.querySelector('.table-responsive');
    if (oldTable) oldTable.remove();
    
    // Remover seção vazia se existir
    const emptySection = historicoSection.querySelector('.text-center');
    if (emptySection) emptySection.remove();
    
    // Criar nova tabela com todos os agendamentos
    if (agendamentos.length > 0) {
        let tbodyHTML = '';
        agendamentos.forEach(aula => {
            const dataBR = aula.data_aula ? new Date(aula.data_aula + 'T00:00:00').toLocaleDateString('pt-BR') : 'N/A';
            const horaInicio = aula.hora_inicio ? (aula.hora_inicio.length >= 5 ? aula.hora_inicio.substring(0, 5) : aula.hora_inicio) : '--:--';
            const horaFim = aula.hora_fim ? (aula.hora_fim.length >= 5 ? aula.hora_fim.substring(0, 5) : aula.hora_fim) : '--:--';
            
            tbodyHTML += `
                <tr data-agendamento-id="${aula.id}">
                    <td><strong>${aula.nome_aula || 'Aula'}</strong></td>
                    <td>${dataBR}</td>
                    <td>${horaInicio} - ${horaFim}</td>
                    <td>${aula.instrutor_nome || 'Não informado'}</td>
                    <td>${aula.sala_nome || 'Não informada'}</td>
                    <td>${aula.duracao_minutos || 50} min</td>
                    <td><span class="badge bg-warning">Agendada</span></td>
                    <td>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="editarAgendamento(${aula.id}, '${(aula.nome_aula || '').replace(/'/g, "\\'")}', '${aula.data_aula}', '${horaInicio}', '${horaFim}', '${aula.instrutor_id || ''}', '${aula.sala_id || ''}', '${aula.duracao_minutos || 50}', '')" title="Editar agendamento">
                                <span style="font-size: 14px; font-weight: bold;">✏</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="cancelarAgendamento(${aula.id}, '${(aula.nome_aula || '').replace(/'/g, "\\'")}')" title="Cancelar agendamento">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
        
        const tableHTML = `
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-primary">
                        <tr>
                            <th>Aula</th>
                            <th>Data</th>
                            <th>Horário</th>
                            <th>Instrutor</th>
                            <th>Sala</th>
                            <th>Duração</th>
                            <th>Status</th>
                            <th width="100">Ações</th>
                        </tr>
                    </thead>
                    <tbody>${tbodyHTML}</tbody>
                </table>
            </div>
        `;
        historicoSection.insertAdjacentHTML('beforeend', tableHTML);
        console.log('✅ [DEBUG] Tabela atualizada com', agendamentos.length, 'agendamentos');
    } else {
        // Se não há agendamentos, mostrar mensagem vazia
        historicoSection.insertAdjacentHTML('beforeend', `
            <div class="text-center py-4">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">Nenhum agendamento encontrado</h6>
                <p class="text-muted small">Não há aulas agendadas para esta disciplina ainda.</p>
            </div>
        `);
    }
}
</script>

```
</rewr