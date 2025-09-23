<?php
/**
 * Dashboard do Instrutor - Mobile First
 * Interface focada em usabilidade móvel
 * IMPORTANTE: Instrutor NÃO pode criar agendamentos, apenas cancelar/transferir
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/services/SistemaNotificacoes.php';

// Verificar autenticação
$user = getCurrentUser();
if (!$user || $user['tipo'] !== 'instrutor') {
    header('Location: /login.php');
    exit();
}

$db = db();
$notificacoes = new SistemaNotificacoes();

// Buscar dados do instrutor
$instrutor = $db->fetch("SELECT * FROM instrutores WHERE id = ?", [$user['id']]);

// Buscar aulas do dia
$hoje = date('Y-m-d');
$aulasHoje = $db->fetchAll("
    SELECT a.*, 
           al.nome as aluno_nome, al.telefone as aluno_telefone,
           v.modelo as veiculo_modelo, v.placa as veiculo_placa
    FROM aulas a
    JOIN alunos al ON a.aluno_id = al.id
    LEFT JOIN veiculos v ON a.veiculo_id = v.id
    WHERE a.instrutor_id = ? 
      AND a.data_aula = ?
      AND a.status != 'cancelada'
    ORDER BY a.hora_inicio ASC
", [$user['id'], $hoje]);

// Buscar próximas aulas (próximos 7 dias)
$proximasAulas = $db->fetchAll("
    SELECT a.*, 
           al.nome as aluno_nome, al.telefone as aluno_telefone,
           v.modelo as veiculo_modelo, v.placa as veiculo_placa
    FROM aulas a
    JOIN alunos al ON a.aluno_id = al.id
    LEFT JOIN veiculos v ON a.veiculo_id = v.id
    WHERE a.instrutor_id = ? 
      AND a.data_aula > ?
      AND a.data_aula <= DATE_ADD(?, INTERVAL 7 DAY)
      AND a.status != 'cancelada'
    ORDER BY a.data_aula ASC, a.hora_inicio ASC
    LIMIT 10
", [$user['id'], $hoje, $hoje]);

// Buscar notificações não lidas
$notificacoesNaoLidas = $notificacoes->buscarNotificacoesNaoLidas($user['id'], 'instrutor');

// Buscar turmas teóricas do dia
$turmasTeoricas = $db->fetchAll("
    SELECT a.*, 
           al.nome as aluno_nome,
           COUNT(*) as total_alunos
    FROM aulas a
    JOIN alunos al ON a.aluno_id = al.id
    WHERE a.instrutor_id = ? 
      AND a.data_aula = ?
      AND a.tipo_aula = 'teorica'
      AND a.status != 'cancelada'
    GROUP BY a.id
    ORDER BY a.hora_inicio ASC
", [$user['id'], $hoje]);

// Estatísticas do dia
$statsHoje = $db->fetch("
    SELECT 
        COUNT(*) as total_aulas,
        SUM(CASE WHEN status = 'agendada' THEN 1 ELSE 0 END) as agendadas,
        SUM(CASE WHEN status = 'confirmada' THEN 1 ELSE 0 END) as confirmadas,
        SUM(CASE WHEN status = 'concluida' THEN 1 ELSE 0 END) as concluidas
    FROM aulas 
    WHERE instrutor_id = ? AND data_aula = ?
", [$user['id'], $hoje]);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($instrutor['nome']); ?></title>
    <link rel="stylesheet" href="../assets/css/mobile-first.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>Olá, <?php echo htmlspecialchars($instrutor['nome']); ?>!</h1>
        <div class="subtitle">Gerencie suas aulas e turmas</div>
    </div>

    <div class="container">
        <!-- Notificações -->
        <?php if (!empty($notificacoesNaoLidas)): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-bell"></i>
                    Notificações
                </h2>
                <span class="badge badge-info"><?php echo count($notificacoesNaoLidas); ?></span>
            </div>
            <div class="notificacoes-list">
                <?php foreach ($notificacoesNaoLidas as $notificacao): ?>
                <div class="notificacao-item" data-id="<?php echo $notificacao['id']; ?>">
                    <div class="notificacao-content">
                        <h4><?php echo htmlspecialchars($notificacao['titulo']); ?></h4>
                        <p><?php echo htmlspecialchars($notificacao['mensagem']); ?></p>
                        <small><?php echo date('d/m/Y H:i', strtotime($notificacao['criado_em'])); ?></small>
                    </div>
                    <button class="btn btn-sm btn-outline marcar-lida" data-id="<?php echo $notificacao['id']; ?>">
                        <i class="fas fa-check"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Estatísticas do Dia -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-chart-bar"></i>
                    Hoje - <?php echo date('d/m/Y'); ?>
                </h2>
            </div>
            <div class="grid grid-2">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $statsHoje['total_aulas']; ?></div>
                    <div class="stat-label">Total de Aulas</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $statsHoje['confirmadas']; ?></div>
                    <div class="stat-label">Confirmadas</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $statsHoje['concluidas']; ?></div>
                    <div class="stat-label">Concluídas</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $statsHoje['agendadas']; ?></div>
                    <div class="stat-label">Pendentes</div>
                </div>
            </div>
        </div>

        <!-- Aulas de Hoje -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-calendar-day"></i>
                    Aulas de Hoje
                </h2>
            </div>
            
            <?php if (empty($aulasHoje)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <h3 class="empty-state-title">Nenhuma aula hoje</h3>
                <p class="empty-state-text">Você não possui aulas agendadas para hoje.</p>
            </div>
            <?php else: ?>
            <div class="aula-list">
                <?php foreach ($aulasHoje as $aula): ?>
                <div class="aula-item" data-aula-id="<?php echo $aula['id']; ?>">
                    <div class="aula-item-header">
                        <div>
                            <div class="aula-tipo <?php echo $aula['tipo_aula']; ?>">
                                <?php echo ucfirst($aula['tipo_aula']); ?>
                            </div>
                            <div class="aula-data">
                                <?php echo date('H:i', strtotime($aula['hora_inicio'])); ?> - 
                                <?php echo date('H:i', strtotime($aula['hora_fim'])); ?>
                            </div>
                        </div>
                        <div class="aula-status <?php echo $aula['status']; ?>">
                            <?php echo ucfirst($aula['status']); ?>
                        </div>
                    </div>
                    
                    <div class="aula-detalhes">
                        <div class="aula-detalhe">
                            <i class="fas fa-user aula-detalhe-icon"></i>
                            <?php echo htmlspecialchars($aula['aluno_nome']); ?>
                        </div>
                        <?php if ($aula['aluno_telefone']): ?>
                        <div class="aula-detalhe">
                            <i class="fas fa-phone aula-detalhe-icon"></i>
                            <a href="tel:<?php echo htmlspecialchars($aula['aluno_telefone']); ?>">
                                <?php echo htmlspecialchars($aula['aluno_telefone']); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        <?php if ($aula['veiculo_modelo']): ?>
                        <div class="aula-detalhe">
                            <i class="fas fa-car aula-detalhe-icon"></i>
                            <?php echo htmlspecialchars($aula['veiculo_modelo']); ?> - <?php echo htmlspecialchars($aula['veiculo_placa']); ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($aula['observacoes']): ?>
                        <div class="aula-detalhe">
                            <i class="fas fa-sticky-note aula-detalhe-icon"></i>
                            <?php echo htmlspecialchars($aula['observacoes']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="aula-actions">
                        <?php if ($aula['tipo_aula'] === 'teorica'): ?>
                        <button class="btn btn-sm btn-primary fazer-chamada" 
                                data-aula-id="<?php echo $aula['id']; ?>">
                            <i class="fas fa-clipboard-list"></i>
                            Chamada
                        </button>
                        <button class="btn btn-sm btn-secondary fazer-diario" 
                                data-aula-id="<?php echo $aula['id']; ?>">
                            <i class="fas fa-book"></i>
                            Diário
                        </button>
                        <?php endif; ?>
                        
                        <button class="btn btn-sm btn-warning solicitar-transferencia" 
                                data-aula-id="<?php echo $aula['id']; ?>"
                                data-data="<?php echo $aula['data_aula']; ?>"
                                data-hora="<?php echo $aula['hora_inicio']; ?>">
                            <i class="fas fa-exchange-alt"></i>
                            Transferir
                        </button>
                        
                        <button class="btn btn-sm btn-danger cancelar-aula" 
                                data-aula-id="<?php echo $aula['id']; ?>"
                                data-data="<?php echo $aula['data_aula']; ?>"
                                data-hora="<?php echo $aula['hora_inicio']; ?>">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Próximas Aulas -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-calendar-alt"></i>
                    Próximas Aulas (7 dias)
                </h2>
            </div>
            
            <?php if (empty($proximasAulas)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <h3 class="empty-state-title">Nenhuma aula agendada</h3>
                <p class="empty-state-text">Você não possui aulas agendadas para os próximos 7 dias.</p>
            </div>
            <?php else: ?>
            <div class="aula-list">
                <?php foreach ($proximasAulas as $aula): ?>
                <div class="aula-item" data-aula-id="<?php echo $aula['id']; ?>">
                    <div class="aula-item-header">
                        <div>
                            <div class="aula-tipo <?php echo $aula['tipo_aula']; ?>">
                                <?php echo ucfirst($aula['tipo_aula']); ?>
                            </div>
                            <div class="aula-data">
                                <?php echo date('d/m/Y', strtotime($aula['data_aula'])); ?>
                            </div>
                            <div class="aula-hora">
                                <?php echo date('H:i', strtotime($aula['hora_inicio'])); ?> - 
                                <?php echo date('H:i', strtotime($aula['hora_fim'])); ?>
                            </div>
                        </div>
                        <div class="aula-status <?php echo $aula['status']; ?>">
                            <?php echo ucfirst($aula['status']); ?>
                        </div>
                    </div>
                    
                    <div class="aula-detalhes">
                        <div class="aula-detalhe">
                            <i class="fas fa-user aula-detalhe-icon"></i>
                            <?php echo htmlspecialchars($aula['aluno_nome']); ?>
                        </div>
                        <?php if ($aula['veiculo_modelo']): ?>
                        <div class="aula-detalhe">
                            <i class="fas fa-car aula-detalhe-icon"></i>
                            <?php echo htmlspecialchars($aula['veiculo_modelo']); ?> - <?php echo htmlspecialchars($aula['veiculo_placa']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="aula-actions">
                        <button class="btn btn-sm btn-warning solicitar-transferencia" 
                                data-aula-id="<?php echo $aula['id']; ?>"
                                data-data="<?php echo $aula['data_aula']; ?>"
                                data-hora="<?php echo $aula['hora_inicio']; ?>">
                            <i class="fas fa-exchange-alt"></i>
                            Transferir
                        </button>
                        
                        <button class="btn btn-sm btn-danger cancelar-aula" 
                                data-aula-id="<?php echo $aula['id']; ?>"
                                data-data="<?php echo $aula['data_aula']; ?>"
                                data-hora="<?php echo $aula['hora_inicio']; ?>">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Ações Rápidas -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-bolt"></i>
                    Ações Rápidas
                </h2>
            </div>
            <div class="grid grid-2">
                <button class="btn btn-primary btn-full" onclick="verTodasAulas()">
                    <i class="fas fa-list"></i>
                    Ver Todas as Aulas
                </button>
                <button class="btn btn-secondary btn-full" onclick="verNotificacoes()">
                    <i class="fas fa-bell"></i>
                    Central de Avisos
                </button>
                <button class="btn btn-outline btn-full" onclick="registrarOcorrencia()">
                    <i class="fas fa-exclamation-triangle"></i>
                    Registrar Ocorrência
                </button>
                <button class="btn btn-outline btn-full" onclick="contatarSecretaria()">
                    <i class="fas fa-phone"></i>
                    Contatar Secretária
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Cancelamento/Transferência -->
    <div id="modalAcao" class="modal-overlay hidden">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitulo">Cancelar Aula</h3>
            </div>
            <div class="modal-body">
                <form id="formAcao">
                    <input type="hidden" id="aulaId" name="aula_id">
                    <input type="hidden" id="tipoAcao" name="tipo_acao">
                    
                    <div class="form-group">
                        <label class="form-label">Data da Aula</label>
                        <input type="text" id="dataAula" class="form-input" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Horário</label>
                        <input type="text" id="horaAula" class="form-input" readonly>
                    </div>
                    
                    <div class="form-group" id="novaDataGroup" style="display: none;">
                        <label class="form-label">Nova Data</label>
                        <input type="date" id="novaData" name="nova_data" class="form-input">
                    </div>
                    
                    <div class="form-group" id="novaHoraGroup" style="display: none;">
                        <label class="form-label">Novo Horário</label>
                        <input type="time" id="novaHora" name="nova_hora" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Motivo</label>
                        <select id="motivo" name="motivo" class="form-select">
                            <option value="">Selecione um motivo</option>
                            <option value="problema_saude">Problema de saúde</option>
                            <option value="imprevisto_pessoal">Imprevisto pessoal</option>
                            <option value="problema_veiculo">Problema com veículo</option>
                            <option value="ausencia_aluno">Ausência do aluno</option>
                            <option value="condicoes_climaticas">Condições climáticas</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Justificativa *</label>
                        <textarea id="justificativa" name="justificativa" class="form-textarea" 
                                  placeholder="Descreva o motivo da ação..." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Política:</strong> Ações devem ser feitas com no mínimo 24 horas de antecedência.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="fecharModal()">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="enviarAcao()">Confirmar</button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer"></div>

    <script>
        // Variáveis globais
        let modalAberto = false;

        // Funções de navegação
        function verTodasAulas() {
            window.location.href = 'aulas.php';
        }

        function verNotificacoes() {
            window.location.href = 'notificacoes.php';
        }

        function registrarOcorrencia() {
            window.location.href = 'ocorrencias.php';
        }

        function contatarSecretaria() {
            window.location.href = 'contato.php';
        }

        // Funções do modal
        function abrirModal(tipo, aulaId, data, hora) {
            document.getElementById('tipoAcao').value = tipo;
            document.getElementById('aulaId').value = aulaId;
            document.getElementById('dataAula').value = data;
            document.getElementById('horaAula').value = hora;
            
            const modal = document.getElementById('modalAcao');
            const titulo = document.getElementById('modalTitulo');
            const novaDataGroup = document.getElementById('novaDataGroup');
            const novaHoraGroup = document.getElementById('novaHoraGroup');
            
            if (tipo === 'transferencia') {
                titulo.textContent = 'Solicitar Transferência';
                novaDataGroup.style.display = 'block';
                novaHoraGroup.style.display = 'block';
            } else {
                titulo.textContent = 'Cancelar Aula';
                novaDataGroup.style.display = 'none';
                novaHoraGroup.style.display = 'none';
            }
            
            modal.classList.remove('hidden');
            modalAberto = true;
        }

        function fecharModal() {
            document.getElementById('modalAcao').classList.add('hidden');
            document.getElementById('formAcao').reset();
            modalAberto = false;
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Botões de cancelamento
            document.querySelectorAll('.cancelar-aula').forEach(btn => {
                btn.addEventListener('click', function() {
                    const aulaId = this.dataset.aulaId;
                    const data = this.dataset.data;
                    const hora = this.dataset.hora;
                    abrirModal('cancelamento', aulaId, data, hora);
                });
            });

            // Botões de transferência
            document.querySelectorAll('.solicitar-transferencia').forEach(btn => {
                btn.addEventListener('click', function() {
                    const aulaId = this.dataset.aulaId;
                    const data = this.dataset.data;
                    const hora = this.dataset.hora;
                    abrirModal('transferencia', aulaId, data, hora);
                });
            });

            // Botões de chamada e diário
            document.querySelectorAll('.fazer-chamada').forEach(btn => {
                btn.addEventListener('click', function() {
                    const aulaId = this.dataset.aulaId;
                    window.location.href = `chamada.php?aula_id=${aulaId}`;
                });
            });

            document.querySelectorAll('.fazer-diario').forEach(btn => {
                btn.addEventListener('click', function() {
                    const aulaId = this.dataset.aulaId;
                    window.location.href = `diario.php?aula_id=${aulaId}`;
                });
            });

            // Botões de marcar notificação como lida
            document.querySelectorAll('.marcar-lida').forEach(btn => {
                btn.addEventListener('click', function() {
                    const notificacaoId = this.dataset.id;
                    marcarNotificacaoComoLida(notificacaoId);
                });
            });

            // Fechar modal ao clicar fora
            document.getElementById('modalAcao').addEventListener('click', function(e) {
                if (e.target === this) {
                    fecharModal();
                }
            });
        });

        // Função para enviar ação
        async function enviarAcao() {
            const form = document.getElementById('formAcao');
            const formData = new FormData(form);
            
            // Validação básica
            if (!formData.get('justificativa').trim()) {
                mostrarToast('Por favor, preencha a justificativa.', 'error');
                return;
            }

            try {
                const response = await fetch('../admin/api/solicitacoes.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        aula_id: formData.get('aula_id'),
                        tipo_solicitacao: formData.get('tipo_acao'),
                        nova_data: formData.get('nova_data'),
                        nova_hora: formData.get('nova_hora'),
                        motivo: formData.get('motivo'),
                        justificativa: formData.get('justificativa')
                    })
                });

                const result = await response.json();

                if (result.success) {
                    mostrarToast('Solicitação enviada com sucesso!', 'success');
                    fecharModal();
                    // Recarregar a página após um breve delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    mostrarToast(result.message || 'Erro ao enviar solicitação.', 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                mostrarToast('Erro de conexão. Tente novamente.', 'error');
            }
        }

        // Função para marcar notificação como lida
        async function marcarNotificacaoComoLida(notificacaoId) {
            try {
                const response = await fetch('../admin/api/notificacoes.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        notificacao_id: notificacaoId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // Remover a notificação da interface
                    const notificacaoItem = document.querySelector(`[data-id="${notificacaoId}"]`);
                    if (notificacaoItem) {
                        notificacaoItem.remove();
                    }
                    
                    // Atualizar contador de notificações
                    const badge = document.querySelector('.badge');
                    if (badge) {
                        const count = parseInt(badge.textContent) - 1;
                        if (count > 0) {
                            badge.textContent = count;
                        } else {
                            badge.parentElement.parentElement.remove();
                        }
                    }
                }
            } catch (error) {
                console.error('Erro:', error);
            }
        }

        // Função para mostrar toast
        function mostrarToast(mensagem, tipo = 'info') {
            const toastContainer = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast toast-${tipo}`;
            
            toast.innerHTML = `
                <div class="toast-header">
                    <i class="fas fa-${tipo === 'success' ? 'check-circle' : tipo === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                    <span class="toast-title">${tipo === 'success' ? 'Sucesso' : tipo === 'error' ? 'Erro' : 'Informação'}</span>
                </div>
                <div class="toast-message">${mensagem}</div>
            `;
            
            toastContainer.appendChild(toast);
            
            // Remover toast após 5 segundos
            setTimeout(() => {
                toast.remove();
            }, 5000);
        }

        // Prevenir envio do formulário com Enter
        document.getElementById('formAcao').addEventListener('submit', function(e) {
            e.preventDefault();
        });
    </script>

    <style>
        /* Estilos específicos para o dashboard do instrutor */
        .notificacao-item {
            display: flex;
            align-items: center;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 8px;
            background: #f8fafc;
        }

        .notificacao-content {
            flex: 1;
        }

        .notificacao-content h4 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
            color: #1e293b;
        }

        .notificacao-content p {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 4px;
        }

        .notificacao-content small {
            font-size: 11px;
            color: #94a3b8;
        }

        .stat-item {
            text-align: center;
            padding: 16px;
            background: #f8fafc;
            border-radius: 8px;
        }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #2563eb;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 500;
        }

        .aula-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
            flex-wrap: wrap;
        }

        .aula-actions .btn {
            flex: 1;
            min-width: 120px;
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }

        .alert i {
            font-size: 16px;
        }

        @media (max-width: 480px) {
            .aula-actions {
                flex-direction: column;
            }
            
            .aula-actions .btn {
                width: 100%;
                min-width: auto;
            }
        }
    </style>
</body>
</html>