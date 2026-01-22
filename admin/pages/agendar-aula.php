<?php
// Verificar se a página está sendo acessada através do sistema de roteamento
if (!defined('ADMIN_ROUTING') && !isset($aluno)) {
    // Se não estiver sendo acessada via roteamento, redirecionar
    header('Location: ../index.php?page=agendar-aula&aluno_id=' . ($_GET['aluno_id'] ?? ''));
    exit;
}

// Verificar se os dados necessários estão disponíveis
if (!isset($aluno)) {
    echo '<div class="alert alert-danger">Erro: Dados não carregados. <a href="?page=alunos">Voltar para Alunos</a></div>';
    return;
}

$aluno_id = $_GET['aluno_id'] ?? $aluno['id'] ?? null;
if (!$aluno_id) {
    echo '<div class="alert alert-danger">Erro: ID do aluno não informado. <a href="?page=alunos">Voltar para Alunos</a></div>';
    return;
}
?>

<!-- CSS específico da página -->
<link rel="stylesheet" href="assets/css/agendar-aula.css">

<!-- Cabeçalho da Página -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-calendar me-2"></i>Agendar Aula - <?php echo htmlspecialchars($aluno['nome']); ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?page=alunos" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Voltar para Alunos
        </a>
    </div>
</div>

<!-- Informações do Aluno -->
<div class="student-info">
    <div class="row">
        <div class="col-md-8">
            <h4><?php echo htmlspecialchars($aluno['nome']); ?></h4>
            <p class="mb-1"><strong>CPF:</strong> <?php echo htmlspecialchars($aluno['cpf'] ?? 'N/A'); ?></p>
            <p class="mb-1"><strong>CFC:</strong> <?php echo htmlspecialchars($cfc ? $cfc['nome'] : 'N/A'); ?></p>
            <p class="mb-0">
                <strong>Status:</strong> 
                <span class="badge bg-<?php echo ($aluno['status'] ?? 'ativo') === 'ativo' ? 'success' : 'warning'; ?>">
                    <?php echo ucfirst($aluno['status'] ?? 'ativo'); ?>
                </span>
                <?php if (!empty($aluno['categoria_cnh'])): ?>
                    <span class="badge bg-info ms-2">Categoria: <?php echo htmlspecialchars($aluno['categoria_cnh']); ?></span>
                <?php endif; ?>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <div class="text-white-50">
                <i class="fas fa-user fa-3x"></i>
            </div>
        </div>
    </div>
</div>

<!-- Bloco 1: Tipo de Agendamento -->
<div class="form-section">
    <h5 class="mb-3"><i class="fas fa-calendar me-2"></i>Tipo de Agendamento</h5>
    
    <div class="mb-4">
        <label class="form-label fw-bold">Quantidade de Aulas:</label>
        <div class="d-flex gap-3 flex-wrap">
            <div class="form-check custom-radio">
                <input class="form-check-input" type="radio" name="tipo_agendamento" id="aula_unica" value="unica" checked>
                <label class="form-check-label" for="aula_unica">
                    <div class="radio-text">
                        <strong>1 Aula</strong>
                        <small>50 minutos</small>
                    </div>
                </label>
            </div>
            <div class="form-check custom-radio">
                <input class="form-check-input" type="radio" name="tipo_agendamento" id="duas_aulas" value="duas">
                <label class="form-check-label" for="duas_aulas">
                    <div class="radio-text">
                        <strong>2 Aulas</strong>
                        <small>1h 40min</small>
                    </div>
                </label>
            </div>
            <div class="form-check custom-radio">
                <input class="form-check-input" type="radio" name="tipo_agendamento" id="tres_aulas" value="tres">
                <label class="form-check-label" for="tres_aulas">
                    <div class="radio-text">
                        <strong>3 Aulas</strong>
                        <small>2h 30min</small>
                    </div>
                </label>
            </div>
        </div>
        
        <!-- Opções para 3 aulas -->
        <div id="opcoesTresAulas" class="mt-3" style="display: none;">
            <label class="form-label fw-bold">Posição do Intervalo:</label>
            <div class="d-flex gap-3 flex-wrap">
                <div class="form-check custom-radio">
                    <input class="form-check-input" type="radio" name="posicao_intervalo" id="intervalo_depois" value="depois" checked>
                    <label class="form-check-label" for="intervalo_depois">
                        <div class="radio-text">
                            <strong>2 consecutivas + intervalo + 1 aula</strong>
                            <small>Primeiro bloco, depois intervalo</small>
                        </div>
                    </label>
                </div>
                <div class="form-check custom-radio">
                    <input class="form-check-input" type="radio" name="posicao_intervalo" id="intervalo_antes" value="antes">
                    <label class="form-check-label" for="intervalo_antes">
                        <div class="radio-text">
                            <strong>1 aula + intervalo + 2 consecutivas</strong>
                            <small>Primeira aula, depois intervalo</small>
                        </div>
                    </label>
                </div>
            </div>
        </div>
        
        <small class="form-text text-muted mt-2 d-block">
            <i class="fas fa-info-circle me-1"></i>
            <strong>2 aulas:</strong> Consecutivas (1h 40min) | <strong>3 aulas:</strong> Escolha a posição do intervalo de 30min (2h 30min total)
        </small>
    </div>
</div>

<!-- Bloco 2: Lista de Dias com Slots -->
<div class="form-section">
    <h5 class="mb-3"><i class="fas fa-calendar-alt me-2"></i>Selecione um dia disponível</h5>
    
    <!-- Estado de Loading -->
    <div id="loading-slots" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Carregando...</span>
        </div>
        <p class="mt-3 text-muted">Buscando horários disponíveis...</p>
    </div>
    
    <!-- Mensagem quando não há slots -->
    <div id="mensagem-sem-slots" class="alert alert-info" style="display: none;">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Nenhum horário disponível</strong>
        <p class="mb-0 mt-2">Não há horários disponíveis nos próximos 14 dias para o tipo de agendamento selecionado. Tente outro tipo de agendamento.</p>
    </div>
    
    <!-- Container de Dias -->
    <div id="container-dias" class="dias-container">
        <!-- Dias serão renderizados aqui via JavaScript -->
    </div>
</div>

<!-- Bloco 3: Resumo + Ações -->
<div class="form-section" id="resumo-agendamento" style="display: none;">
    <h5 class="mb-3"><i class="fas fa-clipboard-check me-2"></i>Resumo do Agendamento</h5>
    
    <div class="resumo-content">
        <div class="resumo-item">
            <strong><i class="fas fa-user me-2"></i>Aluno:</strong>
            <span id="resumo-aluno"><?php echo htmlspecialchars($aluno['nome']); ?></span>
        </div>
        <div class="resumo-item">
            <strong><i class="fas fa-calendar me-2"></i>Data:</strong>
            <span id="resumo-data">--</span>
        </div>
        <div class="resumo-item">
            <strong><i class="fas fa-clock me-2"></i>Horário:</strong>
            <span id="resumo-horario">--</span>
        </div>
        <div class="resumo-item">
            <strong><i class="fas fa-user-tie me-2"></i>Instrutor:</strong>
            <span id="resumo-instrutor">--</span>
        </div>
        <div class="resumo-item">
            <strong><i class="fas fa-car me-2"></i>Veículo:</strong>
            <span id="resumo-veiculo">--</span>
        </div>
        <div class="resumo-item">
            <strong><i class="fas fa-list me-2"></i>Tipo:</strong>
            <span id="resumo-tipo">--</span>
        </div>
    </div>
    
    <div class="d-flex gap-2 mt-4">
        <button type="button" class="btn btn-primary" id="btn-confirmar-agendamento">
            <i class="fas fa-check-circle me-1"></i>Confirmar Agendamento
        </button>
        <button type="button" class="btn btn-secondary" id="btn-cancelar-agendamento">
            <i class="fas fa-times me-1"></i>Cancelar
        </button>
    </div>
</div>

<!-- Mensagens de Sucesso/Erro -->
<div id="mensagens-container"></div>

<!-- JavaScript específico da página -->
<script src="assets/js/agendar-aula.js"></script>

<script>
// Passar dados do aluno para o JavaScript
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.agendarAulaApp !== 'undefined') {
        window.agendarAulaApp.init({
            alunoId: <?php echo (int)$aluno_id; ?>,
            alunoNome: <?php echo json_encode($aluno['nome']); ?>,
            alunoCpf: <?php echo json_encode($aluno['cpf'] ?? ''); ?>,
            categoriaCnh: <?php echo json_encode($aluno['categoria_cnh'] ?? ''); ?>
        });
    }
});
</script>
