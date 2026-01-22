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
    
    /* =====================================================
       MODAL CANCELAMENTO - PADRÃO CUSTOM-MODAL
       ===================================================== */
    
    /* Dialog específico para modal de cancelamento */
    #modalCancelar .custom-modal-dialog {
        width: min(700px, 90vw);
        max-width: 700px;
        height: auto;
        max-height: 90vh;
    }
    
    /* Content - container flex em coluna */
    #modalCancelar .custom-modal-content {
        width: 100%;
        height: auto;
        min-height: 0;
        max-height: 90vh;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.16);
        background: #ffffff;
        display: flex;
        flex-direction: column;
        align-items: stretch;
        overflow: hidden;
        position: relative;
    }
    
    /* Header - fixo */
    .cancelamento-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 24px;
        background-color: var(--cfc-surface, #FFFFFF);
        border-bottom: 1px solid var(--cfc-border-subtle, #E5E7EB);
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        color: var(--cfc-primary, #0F1E4A);
        min-height: 56px;
        flex: 0 0 auto;
        flex-shrink: 0;
    }
    
    .cancelamento-modal-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--cfc-primary, #0F1E4A);
    }
    
    .cancelamento-modal-title i {
        font-size: 1.1rem;
        color: #dc3545;
    }
    
    .cancelamento-modal-close {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--gray-300, #cbd5e1);
        border: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        opacity: 1;
        background-image: none;
        color: var(--gray-700, #334155);
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 1.4rem;
        line-height: 1;
    }
    
    .cancelamento-modal-close::after {
        content: "\00d7";
        font-size: 1.4rem;
        line-height: 1;
        color: var(--gray-700, #334155);
        font-weight: 300;
    }
    
    .cancelamento-modal-close:hover {
        background-color: var(--gray-400, #94a3b8);
        color: var(--gray-800, #1e293b);
    }
    
    .cancelamento-modal-close:focus-visible {
        outline: none;
        box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.4);
    }
    
    /* Body - rolável */
    .cancelamento-modal-body {
        display: flex;
        flex-direction: column;
        gap: 0;
        padding: 24px 32px;
        background-color: var(--cfc-surface, #FFFFFF);
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        scrollbar-width: thin;
        scrollbar-color: #94a3b8 #edf2f7;
        box-sizing: border-box;
    }
    
    /* Scrollbar customizada */
    .cancelamento-modal-body::-webkit-scrollbar {
        width: 8px;
    }
    
    .cancelamento-modal-body::-webkit-scrollbar-track {
        background: #edf2f7;
        border-radius: 4px;
    }
    
    .cancelamento-modal-body::-webkit-scrollbar-thumb {
        background: #94a3b8;
        border-radius: 4px;
    }
    
    .cancelamento-modal-body::-webkit-scrollbar-thumb:hover {
        background: #64748b;
    }
    
    /* Footer - fixo */
    .cancelamento-modal-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
        padding: 12px 24px;
        background-color: var(--cfc-surface-muted, #F3F4F6);
        border-top: 1px solid var(--cfc-border-subtle, #E5E7EB);
        border-bottom-left-radius: 12px;
        border-bottom-right-radius: 12px;
        flex: 0 0 auto;
        flex-shrink: 0;
        min-height: auto;
    }
    
    .cancelamento-modal-footer .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 0 18px;
        min-height: 40px;
        font-weight: 600;
        border-radius: 10px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        cursor: pointer;
    }
    
    .cancelamento-modal-footer .btn-outline-secondary {
        border: 1px solid var(--cfc-border-subtle, #E5E7EB);
        background: var(--cfc-surface, #FFFFFF);
        color: var(--gray-700, #334155);
    }
    
    .cancelamento-modal-footer .btn-outline-secondary:hover {
        background: var(--gray-100, #f1f5f9);
        color: var(--gray-800, #1e293b);
        border-color: var(--gray-300, #cbd5e1);
    }
    
    .cancelamento-modal-footer .btn-danger {
        background: #dc3545;
        color: #ffffff;
        border: none;
        min-height: 40px;
    }
    
    .cancelamento-modal-footer .btn-danger:hover {
        background: #c82333;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
    }
    
    /* Responsividade */
    @media (max-width: 768px) {
        #modalCancelar .custom-modal-dialog {
            width: 95vw;
            max-width: 95vw;
        }
        
        .cancelamento-modal-body {
            padding: 16px 20px;
        }
        
        .cancelamento-modal-header {
            padding: 12px 20px;
        }
        
        .cancelamento-modal-footer {
            padding: 12px 20px;
        }
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

<!-- Modal de Cancelamento - Padronizado -->
<div id="modalCancelar" class="custom-modal">
    <div class="custom-modal-dialog">
        <div class="custom-modal-content">
            <div class="modal-form-header cancelamento-modal-header">
                <h2 class="cancelamento-modal-title">
                    <i class="fas fa-calendar-times me-2"></i>Cancelar Aula
                </h2>
                <button type="button" class="btn-close cancelamento-modal-close" onclick="fecharModalCancelar()"></button>
            </div>
            
            <div class="modal-form-body cancelamento-modal-body">
                <div class="alert alert-warning d-flex align-items-start mb-4">
                    <i class="fas fa-exclamation-triangle me-2 mt-1"></i>
                    <div>
                        <strong>Atenção:</strong> Esta ação não pode ser desfeita. A aula será cancelada permanentemente.
                    </div>
                </div>
                
                <!-- Resumo da Aula -->
                <div class="aula-cancelamento-resumo mb-4 p-3 bg-light rounded">
                    <h6 class="text-primary mb-3 border-bottom pb-2" style="font-size: 0.95rem; font-weight: 600;">
                        <i class="fas fa-info-circle me-2"></i>Informações da Aula
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-2">
                                <strong><i class="fas fa-user me-1"></i>Aluno:</strong>
                                <span id="resumo-aluno" class="ms-2">-</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <strong><i class="fas fa-chalkboard-teacher me-1"></i>Instrutor:</strong>
                                <span id="resumo-instrutor" class="ms-2">-</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-2">
                                <strong><i class="fas fa-calendar me-1"></i>Data:</strong>
                                <span id="resumo-data" class="ms-2">-</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-2">
                                <strong><i class="fas fa-clock me-1"></i>Horário:</strong>
                                <span id="resumo-horario" class="ms-2">-</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-2">
                                <strong><i class="fas fa-graduation-cap me-1"></i>Tipo:</strong>
                                <span id="resumo-tipo" class="ms-2">-</span>
                            </div>
                        </div>
                        <div class="col-md-6" id="resumo-veiculo-container" style="display: none;">
                            <div class="mb-2">
                                <strong><i class="fas fa-car me-1"></i>Veículo:</strong>
                                <span id="resumo-veiculo" class="ms-2">-</span>
                            </div>
                        </div>
                        <div class="col-md-6" id="resumo-disciplina-container" style="display: none;">
                            <div class="mb-2">
                                <strong><i class="fas fa-book me-1"></i>Disciplina:</strong>
                                <span id="resumo-disciplina" class="ms-2">-</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Formulário de Cancelamento -->
                <form id="formCancelar">
                    <input type="hidden" id="aula_id_cancelar" name="aula_id">
                    
                    <div class="mb-3">
                        <label for="motivo_cancelamento" class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                            Motivo do Cancelamento <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="motivo_cancelamento" name="motivo_cancelamento" required style="padding: 0.5rem; font-size: 0.9rem;">
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
                        <label for="observacoes_cancelamento" class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                            Observações Adicionais
                        </label>
                        <textarea class="form-control" id="observacoes_cancelamento" name="observacoes_cancelamento" rows="3" 
                                  placeholder="Detalhes adicionais sobre o cancelamento..." style="font-size: 0.9rem;"></textarea>
                        <small class="text-muted" style="font-size: 0.8rem;">Informações complementares sobre o cancelamento (opcional)</small>
                    </div>
                </form>
            </div>
            
            <div class="modal-form-footer cancelamento-modal-footer">
                <button type="button" class="btn btn-outline-secondary" onclick="fecharModalCancelar()">
                    <i class="fas fa-times me-1"></i>Voltar
                </button>
                <button type="button" class="btn btn-danger" id="btnConfirmarCancelamento" onclick="confirmarCancelamento()">
                    <i class="fas fa-trash me-1"></i>Confirmar Cancelamento
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function cancelarAula(aulaId) {
        // Buscar dados da aula do card
        const aulaCard = document.querySelector(`[data-aula-id="${aulaId}"]`);
        if (!aulaCard) {
            console.error('Card da aula não encontrado');
            return;
        }
        
        // Extrair informações do card
        const alunoNome = aulaCard.querySelector('.aula-header h5')?.textContent?.trim() || '-';
        const dataHorario = aulaCard.querySelector('.aula-header p')?.textContent?.trim() || '';
        
        // Buscar instrutor (procurar por ícone de chalkboard-teacher)
        let instrutorNome = '-';
        const infoItems = aulaCard.querySelectorAll('.info-item');
        infoItems.forEach(item => {
            const icon = item.querySelector('.fa-chalkboard-teacher');
            if (icon) {
                const span = item.querySelector('span');
                if (span) {
                    instrutorNome = span.textContent.replace('Instrutor:', '').trim() || '-';
                }
            }
        });
        
        // Buscar tipo (procurar por ícone de graduation-cap)
        let tipoTexto = '-';
        infoItems.forEach(item => {
            const icon = item.querySelector('.fa-graduation-cap');
            if (icon) {
                const badge = item.querySelector('.badge');
                if (badge) {
                    tipoTexto = badge.textContent.trim() || '-';
                }
            }
        });
        
        // Buscar veículo (procurar por ícone de car)
        let veiculoTexto = '';
        infoItems.forEach(item => {
            const icon = item.querySelector('.fa-car');
            if (icon) {
                const span = item.querySelector('span');
                if (span) {
                    veiculoTexto = span.textContent.replace('Veículo:', '').trim() || '';
                }
            }
        });
        
        // Buscar disciplina (procurar por ícone de book)
        let disciplinaTexto = '';
        infoItems.forEach(item => {
            const icon = item.querySelector('.fa-book');
            if (icon) {
                const span = item.querySelector('span');
                if (span) {
                    disciplinaTexto = span.textContent.replace('Disciplina:', '').trim() || '';
                }
            }
        });
        
        // Extrair data e horário
        const dataMatch = dataHorario.match(/(\d{2}\/\d{2}\/\d{4})/);
        const horarioMatch = dataHorario.match(/(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})/);
        
        // Preencher resumo
        document.getElementById('resumo-aluno').textContent = alunoNome;
        document.getElementById('resumo-instrutor').textContent = instrutorNome;
        document.getElementById('resumo-data').textContent = dataMatch ? dataMatch[1] : '-';
        document.getElementById('resumo-horario').textContent = horarioMatch ? `${horarioMatch[1]} - ${horarioMatch[2]}` : '-';
        document.getElementById('resumo-tipo').textContent = tipoTexto;
        
        // Veículo (se existir)
        if (veiculoTexto) {
            document.getElementById('resumo-veiculo').textContent = veiculoTexto;
            document.getElementById('resumo-veiculo-container').style.display = 'block';
        } else {
            document.getElementById('resumo-veiculo-container').style.display = 'none';
        }
        
        // Disciplina (se existir)
        if (disciplinaTexto) {
            document.getElementById('resumo-disciplina').textContent = disciplinaTexto;
            document.getElementById('resumo-disciplina-container').style.display = 'block';
        } else {
            document.getElementById('resumo-disciplina-container').style.display = 'none';
        }
        
        // Definir ID da aula
        document.getElementById('aula_id_cancelar').value = aulaId;
        
        // Limpar formulário
        document.getElementById('motivo_cancelamento').value = '';
        document.getElementById('observacoes_cancelamento').value = '';
        
        // Abrir modal usando padrão custom-modal
        const modal = document.getElementById('modalCancelar');
        modal.setAttribute('data-opened', 'true');
    }
    
    function fecharModalCancelar() {
        const modal = document.getElementById('modalCancelar');
        modal.setAttribute('data-opened', 'false');
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
        // Usar caminho absoluto baseado na estrutura do projeto
        const apiUrl = window.location.pathname.includes('/admin/') 
            ? 'api/cancelar-aula.php' 
            : '../admin/api/cancelar-aula.php';
        
        // Ajustar nome do campo observacoes (o formulário usa observacoes_cancelamento)
        const observacoes = formData.get('observacoes_cancelamento') || formData.get('observacoes') || '';
        formData.set('observacoes', observacoes);
        
        console.log('Enviando requisição para:', apiUrl);
        console.log('Dados do formulário:', {
            aula_id: formData.get('aula_id'),
            motivo: formData.get('motivo_cancelamento'),
            observacoes: observacoes
        });
        
        fetch(apiUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Verificar se a resposta é JSON válido
            if (!response.ok) {
                // Se não for OK, tentar ler como texto primeiro
                return response.text().then(text => {
                    console.error('Erro na resposta:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Erro ao cancelar aula: ' + response.status + ' ' + response.statusText);
                    }
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Resposta do servidor:', data);
            
            // A API retorna 'success' (não 'sucesso')
            if (data.success) {
                // Sucesso
                mostrarMensagemSucesso('Aula cancelada com sucesso!', data.data || {});
                
                // Remover card da aula
                const aulaId = data.data?.aula_id || formData.get('aula_id');
                const card = document.querySelector(`[data-aula-id="${aulaId}"]`);
                if (card) {
                    card.remove();
                }
                
                // Fechar modal
                fecharModalCancelar();
                
                // Recarregar página após 2 segundos
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                // Erro
                const mensagemErro = data.message || data.mensagem || 'Erro desconhecido ao cancelar aula';
                mostrarMensagemErro('Erro ao cancelar aula: ' + mensagemErro);
                
                // Reativar botão
                btnConfirmar.innerHTML = textoOriginal;
                btnConfirmar.disabled = false;
            }
        })
        .catch(error => {
            console.error('Erro ao cancelar aula:', error);
            console.error('Stack trace:', error.stack);
            
            // Mensagem de erro mais específica
            let mensagemErro = 'Erro ao cancelar aula. Tente novamente.';
            if (error.message) {
                mensagemErro = error.message;
            }
            
            mostrarMensagemErro(mensagemErro);
            
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
