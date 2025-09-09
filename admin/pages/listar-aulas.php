<?php
// Verificar se a página está sendo acessada através do sistema de roteamento
if (!defined('ADMIN_ROUTING') && !isset($aulas_lista)) {
    // Se não estiver sendo acessada via roteamento, redirecionar
    header('Location: ../index.php?page=agendar-aula&action=list');
    exit;
}

// Verificar se os dados necessários estão disponíveis
if (!isset($aulas_lista)) {
    echo '<div class="alert alert-danger">Erro: Dados não carregados. <a href="?page=dashboard">Voltar para Dashboard</a></div>';
    return;
}
?>

<style>
    .aulas-container {
        max-width: 100%;
        padding: 0;
    }
    
    .aula-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .aula-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .aula-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        position: relative;
    }
    
    .aula-body {
        padding: 20px;
    }
    
    .status-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8em;
        font-weight: 600;
    }
    
    .info-item {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .info-item i {
        width: 20px;
        margin-right: 10px;
        color: #6c757d;
    }
    
    .action-buttons {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }
    
    .btn-action {
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 0.9em;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn-cancelar {
        background: #dc3545;
        color: white;
        border: none;
    }
    
    .btn-cancelar:hover {
        background: #c82333;
        transform: translateY(-1px);
    }
    
    .btn-reagendar {
        background: #ffc107;
        color: #212529;
        border: none;
    }
    
    .btn-reagendar:hover {
        background: #e0a800;
        transform: translateY(-1px);
    }
    
    .btn-detalhes {
        background: #17a2b8;
        color: white;
        border: none;
    }
    
    .btn-detalhes:hover {
        background: #138496;
        transform: translateY(-1px);
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 20px;
        opacity: 0.5;
    }
</style>

<!-- Cabeçalho da Página -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-clock me-2"></i>Lista de Aulas
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?page=agendamento" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Nova Aula
        </a>
    </div>
</div>

<!-- Lista de Aulas -->
<div class="aulas-container">
    <?php if (empty($aulas_lista)): ?>
        <div class="empty-state">
            <i class="fas fa-calendar-times"></i>
            <h3>Nenhuma aula encontrada</h3>
            <p>Não há aulas agendadas nos últimos 7 dias.</p>
            <a href="?page=agendamento" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Agendar Nova Aula
            </a>
        </div>
    <?php else: ?>
        <?php foreach ($aulas_lista as $aula): ?>
            <div class="aula-card" data-aula-id="<?php echo $aula['id']; ?>">
                <div class="aula-header">
                    <div class="status-badge bg-<?php 
                        echo $aula['status'] === 'agendada' ? 'success' : 
                            ($aula['status'] === 'cancelada' ? 'danger' : 
                            ($aula['status'] === 'concluida' ? 'info' : 'warning')); 
                    ?>">
                        <?php echo ucfirst($aula['status']); ?>
                    </div>
                    
                    <h5 class="mb-1"><?php echo htmlspecialchars($aula['aluno_nome']); ?></h5>
                    <p class="mb-0 opacity-75">
                        <i class="fas fa-calendar me-1"></i>
                        <?php echo date('d/m/Y', strtotime($aula['data_aula'])); ?>
                        <i class="fas fa-clock ms-3 me-1"></i>
                        <?php echo date('H:i', strtotime($aula['hora_inicio'])); ?> - <?php echo date('H:i', strtotime($aula['hora_fim'])); ?>
                    </p>
                </div>
                
                <div class="aula-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <i class="fas fa-chalkboard-teacher"></i>
                                <span><strong>Instrutor:</strong> <?php echo htmlspecialchars($aula['instrutor_nome']); ?></span>
                            </div>
                            
                            <div class="info-item">
                                <i class="fas fa-graduation-cap"></i>
                                <span><strong>Tipo:</strong> 
                                    <span class="badge bg-<?php echo $aula['tipo_aula'] === 'teorica' ? 'info' : 'primary'; ?>">
                                        <?php echo ucfirst($aula['tipo_aula']); ?>
                                    </span>
                                </span>
                            </div>
                            
                            <?php if ($aula['disciplina']): ?>
                            <div class="info-item">
                                <i class="fas fa-book"></i>
                                <span><strong>Disciplina:</strong> <?php echo htmlspecialchars($aula['disciplina']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6">
                            <?php if ($aula['veiculo_placa']): ?>
                            <div class="info-item">
                                <i class="fas fa-car"></i>
                                <span><strong>Veículo:</strong> <?php echo htmlspecialchars($aula['veiculo_placa'] . ' - ' . $aula['veiculo_modelo']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="info-item">
                                <i class="fas fa-building"></i>
                                <span><strong>CFC:</strong> <?php echo htmlspecialchars($aula['cfc_nome']); ?></span>
                            </div>
                            
                            <?php if ($aula['observacoes']): ?>
                            <div class="info-item">
                                <i class="fas fa-sticky-note"></i>
                                <span><strong>Observações:</strong> <?php echo htmlspecialchars(substr($aula['observacoes'], 0, 100)) . (strlen($aula['observacoes']) > 100 ? '...' : ''); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($aula['status'] === 'agendada'): ?>
                    <div class="action-buttons">
                        <button class="btn btn-action btn-detalhes" onclick="verDetalhes(<?php echo $aula['id']; ?>)">
                            <i class="fas fa-eye me-1"></i>Ver Detalhes
                        </button>
                        <button class="btn btn-action btn-reagendar" onclick="reagendarAula(<?php echo $aula['id']; ?>)">
                            <i class="fas fa-calendar-alt me-1"></i>Reagendar
                        </button>
                        <button class="btn btn-action btn-cancelar" onclick="cancelarAula(<?php echo $aula['id']; ?>)">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal de Cancelamento -->
<div class="modal fade" id="modalCancelar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Cancelar Aula
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Atenção:</strong> Esta ação não pode ser desfeita. A aula será cancelada permanentemente.
                </div>
                
                <form id="formCancelar">
                    <input type="hidden" id="aula_id_cancelar" name="aula_id">
                    
                    <div class="mb-3">
                        <label for="motivo_cancelamento" class="form-label">Motivo do Cancelamento *</label>
                        <select class="form-select" id="motivo_cancelamento" name="motivo_cancelamento" required>
                            <option value="">Selecione o motivo...</option>
                            <option value="aluno_ausente">Aluno ausente</option>
                            <option value="instrutor_indisponivel">Instrutor indisponível</option>
                            <option value="veiculo_manutencao">Veículo em manutenção</option>
                            <option value="condicoes_climaticas">Condições climáticas</option>
                            <option value="problema_tecnico">Problema técnico</option>
                            <option value="solicitacao_aluno">Solicitação do aluno</option>
                            <option value="outros">Outros</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacoes_cancelamento" class="form-label">Observações Adicionais</label>
                        <textarea class="form-control" id="observacoes_cancelamento" name="observacoes_cancelamento" rows="3" placeholder="Detalhes adicionais sobre o cancelamento..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-danger" onclick="confirmarCancelamento()">
                    <i class="fas fa-trash me-1"></i>Confirmar Cancelamento
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function cancelarAula(aulaId) {
        document.getElementById('aula_id_cancelar').value = aulaId;
        const modal = new bootstrap.Modal(document.getElementById('modalCancelar'));
        modal.show();
    }
    
    function confirmarCancelamento() {
        const form = document.getElementById('formCancelar');
        const formData = new FormData(form);
        
        // Validar motivo
        if (!formData.get('motivo_cancelamento')) {
            alert('Por favor, selecione o motivo do cancelamento.');
            return;
        }
        
        // Mostrar loading
        const btnConfirmar = document.querySelector('#modalCancelar .btn-danger');
        const textoOriginal = btnConfirmar.innerHTML;
        btnConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Cancelando...';
        btnConfirmar.disabled = true;
        
        // Enviar requisição
        fetch('../api/cancelar-aula.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                // Sucesso
                mostrarMensagemSucesso('Aula cancelada com sucesso!', data.dados);
                
                // Remover card da aula
                const card = document.querySelector(`[data-aula-id="${data.dados.aula_id}"]`);
                if (card) {
                    card.remove();
                }
                
                // Fechar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalCancelar'));
                modal.hide();
                
                // Recarregar página após 2 segundos
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                // Erro
                mostrarMensagemErro('Erro ao cancelar aula: ' + data.mensagem);
                
                // Reativar botão
                btnConfirmar.innerHTML = textoOriginal;
                btnConfirmar.disabled = false;
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarMensagemErro('Erro ao cancelar aula. Tente novamente.');
            
            // Reativar botão
            btnConfirmar.innerHTML = textoOriginal;
            btnConfirmar.disabled = false;
        });
    }
    
    function reagendarAula(aulaId) {
        // Implementar reagendamento
        alert('Funcionalidade de reagendamento será implementada em breve.');
    }
    
    function verDetalhes(aulaId) {
        // Implementar visualização de detalhes
        alert('Funcionalidade de detalhes será implementada em breve.');
    }
    
    function mostrarMensagemSucesso(mensagem, dados) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success alert-dismissible fade show mt-3';
        alertDiv.innerHTML = `
            <h5><i class="fas fa-check-circle me-2"></i>${mensagem}</h5>
            <hr>
            <p><strong>Aluno:</strong> ${dados.aluno}</p>
            <p><strong>Instrutor:</strong> ${dados.instrutor}</p>
            <p><strong>Data:</strong> ${dados.data}</p>
            <p><strong>Hora:</strong> ${dados.hora}</p>
            <p><strong>Tipo:</strong> ${dados.tipo}</p>
            <p><strong>Motivo:</strong> ${dados.motivo}</p>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.querySelector('.aulas-container').insertBefore(alertDiv, document.querySelector('.aulas-container').firstChild);
    }
    
    function mostrarMensagemErro(mensagem) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
        alertDiv.innerHTML = `
            <h5><i class="fas fa-exclamation-triangle me-2"></i>Erro</h5>
            <p>${mensagem}</p>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.querySelector('.aulas-container').insertBefore(alertDiv, document.querySelector('.aulas-container').firstChild);
    }
</script>
