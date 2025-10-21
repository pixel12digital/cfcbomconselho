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

// Obter estatísticas de aulas para cada disciplina
$estatisticasDisciplinas = [];
foreach ($disciplinasSelecionadas as $disciplina) {
    $disciplinaId = $disciplina['disciplina_id'];
    
    // Buscar aulas agendadas para esta disciplina
    $aulasAgendadas = $db->fetch(
        "SELECT COUNT(*) as total FROM turma_aulas_agendadas WHERE turma_id = ? AND disciplina = ?",
        [$turmaId, $disciplinaId]
    );
    
    // Buscar aulas realizadas (status = 'realizada')
    $aulasRealizadas = $db->fetch(
        "SELECT COUNT(*) as total FROM turma_aulas_agendadas WHERE turma_id = ? AND disciplina = ? AND status = 'realizada'",
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
}

?>

<style>
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
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    transition: all 0.3s ease;
}

.disciplina-cadastrada-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
</style>

<!-- Cabeçalho -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div>
        <h3 style="margin: 0; color: #023A8D;">
            <i class="fas fa-info-circle me-2"></i>Detalhes da Turma
        </h3>
        <p style="margin: 5px 0 0 0; color: #666;">
            Informações completas sobre a turma teórica - Clique nos campos para editar
        </p>
    </div>
    <a href="?page=turmas-teoricas" class="btn-secondary">
        ← Voltar para Lista
    </a>
</div>

<!-- Informações Básicas -->
<div style="background: white; border-radius: 12px; padding: 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
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

<!-- Disciplinas -->
<div style="background: white; border-radius: 12px; padding: 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
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
                <div class="disciplina-cadastrada-card" data-disciplina-id="<?= $index ?>" data-disciplina-cadastrada="<?= isset($disciplina['disciplina_id']) ? $disciplina['disciplina_id'] : '0' ?>">
                    <div class="disciplina-info-display">
                        <div class="disciplina-nome-display">
                            <h6><?= htmlspecialchars($disciplina['nome_disciplina'] ?? $disciplina['nome_original'] ?? 'Disciplina não especificada') ?></h6>
                            
                            <!-- Estatísticas de Aulas -->
                            <?php 
                            $disciplinaId = $disciplina['disciplina_id'];
                            $stats = $estatisticasDisciplinas[$disciplinaId] ?? ['agendadas' => 0, 'realizadas' => 0, 'faltantes' => 0, 'obrigatorias' => 0];
                            ?>
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
                            <div class="detalhe-item">
                                <span class="detalhe-label">Aulas Padrão:</span>
                                <span class="detalhe-valor"><?= isset($disciplina['carga_horaria_padrao']) ? $disciplina['carga_horaria_padrao'] : '1' ?> aulas</span>
                            </div>
                        </div>
                        
                        <!-- Disciplinas são automáticas baseadas no tipo de curso - não editáveis -->
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

<!-- Estatísticas da Turma -->
<div class="estatisticas-container">
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
            <div class="stat-label">Carga Horária Restante</div>
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


<script>
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
// FUNÇÕES PRINCIPAIS - DISCIPLINAS
// ==========================================

// Carregar disciplinas disponíveis em todos os selects
function carregarDisciplinasDisponiveis() {
    console.log('📚 [DISCIPLINAS] Carregando disciplinas disponíveis...');
    
    // Usar a mesma API do cadastro
    return fetch('/cfc-bom-conselho/admin/api/disciplinas-clean.php?acao=listar')
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
                        
                        // Carregar em todos os selects existentes (incluindo o de nova disciplina e formulários de edição)
                        const selects = document.querySelectorAll('.disciplina-item select, #nova_disciplina_select, .disciplina-edit-form select');
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
                        selecionarDisciplinasCadastradas();
                        
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
                    if (disciplinaId) {
                        atualizarDisciplinaDetalhes(parseInt(disciplinaId));
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
    console.log('🔄 [DISCIPLINA] Atualizando disciplina:', disciplinaId);
    
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
                fetch('/cfc-bom-conselho/admin/api/turmas-teoricas-inline.php', {
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
    
    fetch('/cfc-bom-conselho/admin/api/disciplinas-clean.php?acao=listar')
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
                        if (disciplina && disciplina.id && disciplina.nome) {
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
    
    return fetch('/cfc-bom-conselho/admin/api/disciplinas-clean.php?acao=listar')
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
                        if (disciplina && disciplina.id && disciplina.nome) {
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
    return fetch('/cfc-bom-conselho/admin/api/disciplinas-clean.php?acao=listar')
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
                            select = document.getElementById('nova_disciplina_select');
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
                                if (disciplina && disciplina.id && disciplina.nome) {
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
    const disciplinas = document.querySelectorAll('.disciplina-item select, .disciplina-card');
    let disciplinasSelecionadas = 0;
    
    disciplinas.forEach(element => {
        if (element.classList.contains('disciplina-card')) {
            // Para cards, verificar se tem disciplina cadastrada
            const disciplinaId = element.getAttribute('data-disciplina-cadastrada');
            if (disciplinaId && disciplinaId !== '0') {
                disciplinasSelecionadas++;
            }
        } else {
            // Para selects, verificar se tem valor selecionado
            if (element.value) {
                disciplinasSelecionadas++;
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
    fetch('/cfc-bom-conselho/admin/api/disciplinas-clean.php?acao=listar')
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
    setTimeout(testarDisciplinasCompletas, 2000);
    
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
    fetch('/cfc-bom-conselho/admin/api/disciplinas-clean.php?acao=listar')
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
    
    // Executar teste
    setTimeout(testarSistemaDisciplinas, 1000);
    
    // Carregar disciplinas usando método simples que sempre funciona
    console.log('🚀 [INIT] Usando método simples para carregar disciplinas...');
    carregarDisciplinasSimples();
    
    // Carregar disciplinas no select de nova disciplina
    setTimeout(() => {
        const novaDisciplinaSelect = document.getElementById('nova_disciplina_select');
        console.log('🔍 [NOVA] Verificando select de nova disciplina:', novaDisciplinaSelect);
        
        if (novaDisciplinaSelect) {
            console.log('📊 [NOVA] Opções atuais:', novaDisciplinaSelect.options.length);
            if (novaDisciplinaSelect.options.length <= 1) {
                console.log('🔄 [NOVA] Carregando disciplinas no select de nova disciplina');
                carregarDisciplinasNoSelect('nova');
            } else {
                console.log('✅ [NOVA] Disciplinas já carregadas');
            }
        } else {
            console.error('❌ [NOVA] Select de nova disciplina não encontrado');
        }
    }, 1000);
    
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
    
    // Adicionar eventos para TODOS os selects da página
    const todosSelects = document.querySelectorAll('select');
    console.log('🔍 [EVENTS] Adicionando eventos para', todosSelects.length, 'selects');
    
    todosSelects.forEach((select, index) => {
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
    
    // Verificação periódica para garantir que todos os selects têm todas as disciplinas
    setInterval(() => {
        const selects = document.querySelectorAll('select');
        let precisaRecarregar = false;
        
        selects.forEach(select => {
            if (select.options.length <= 2) {
                console.log('⚠️ [PERIODIC] Select com poucas opções detectado:', select.name || select.id);
                precisaRecarregar = true;
            }
        });
        
        if (precisaRecarregar) {
            console.log('🔄 [PERIODIC] Recarregando disciplinas...');
            carregarDisciplinasSimples();
        }
    }, 5000); // Verificar a cada 5 segundos
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
    fetch('/cfc-bom-conselho/admin/api/turmas-teoricas-inline.php', {
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
    fetch('/cfc-bom-conselho/admin/api/disciplinas-estaticas.php?action=listar')
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
    fetch('/cfc-bom-conselho/admin/api/turmas-teoricas-inline.php', {
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
    fetch('/cfc-bom-conselho/admin/api/disciplinas-estaticas.php?action=listar')
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
    fetch('/cfc-bom-conselho/admin/api/turmas-teoricas-inline.php', {
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
        fetch('/cfc-bom-conselho/admin/api/turmas-teoricas-inline.php', {
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
</script>
<!-- Cache fix: no-reload-<?= time() ?> -->
