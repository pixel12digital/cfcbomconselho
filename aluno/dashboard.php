<?php
/**
 * Dashboard do Aluno - Mobile First + PWA
 * Interface focada em usabilidade móvel
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/services/SistemaNotificacoes.php';

// Verificar autenticação específica para aluno
if (!isset($_SESSION['aluno_id']) || $_SESSION['user_type'] !== 'aluno') {
    header('Location: /login.php?type=aluno');
    exit();
}

$db = db();
$notificacoes = new SistemaNotificacoes();

// Buscar dados do aluno - primeiro na tabela usuarios, depois na tabela alunos
$aluno = $db->fetch("SELECT * FROM usuarios WHERE id = ? AND tipo = 'aluno'", [$_SESSION['aluno_id']]);

if (!$aluno) {
    // Se não encontrar na tabela usuarios, buscar na tabela alunos
    $aluno = $db->fetch("SELECT * FROM alunos WHERE id = ?", [$_SESSION['aluno_id']]);
}

if (!$aluno) {
    header('Location: /login.php?type=aluno');
    exit();
}

// Buscar próximas aulas (próximos 14 dias) - se as tabelas existirem
$proximasAulas = [];
try {
    $proximasAulas = $db->fetchAll("
        SELECT a.*, 
               i.nome as instrutor_nome,
               v.modelo as veiculo_modelo, v.placa as veiculo_placa
        FROM aulas a
        JOIN instrutores i ON a.instrutor_id = i.id
        LEFT JOIN veiculos v ON a.veiculo_id = v.id
        WHERE a.aluno_id = ?
          AND a.data_aula >= CURDATE() 
          AND a.data_aula <= DATE_ADD(CURDATE(), INTERVAL 14 DAY)
          AND a.status != 'cancelada'
        ORDER BY a.data_aula ASC, a.hora_inicio ASC
        LIMIT 10
    ", [$_SESSION['aluno_id']]);
} catch (Exception $e) {
    // Tabelas não existem ou erro na query
    error_log("[DASHBOARD ALUNO] Erro ao buscar aulas: " . $e->getMessage());
    $proximasAulas = [];
}

// Buscar notificações não lidas
$notificacoesNaoLidas = $notificacoes->buscarNotificacoesNaoLidas($_SESSION['aluno_id'], 'aluno');

// Buscar status dos exames (se a tabela existir)
$exames = [];
try {
    $exames = $db->fetchAll("
        SELECT tipo, status, data_exame
        FROM exames 
        WHERE aluno_id = ? 
        ORDER BY data_exame DESC
    ", [$_SESSION['aluno_id']]);
} catch (Exception $e) {
    // Tabela exames não existe ou erro na query
    error_log("[DASHBOARD ALUNO] Erro ao buscar exames: " . $e->getMessage());
    $exames = [];
}

// Verificar guardas de negócio
$guardaExames = true;
$guardaFinanceiro = true;

foreach ($exames as $exame) {
    if (in_array($exame['tipo'], ['medico', 'psicologico']) && $exame['status'] !== 'aprovado') {
        $guardaExames = false;
        break;
    }
}

// Configurar variáveis para o layout
$pageTitle = 'Dashboard - ' . htmlspecialchars($aluno['nome']);
$homeUrl = '/aluno/dashboard.php';

// Incluir layout mobile-first
ob_start();
?>
<!-- Conteúdo do Dashboard -->
<div class="row">
    <div class="col-12">
        <div class="card card-mobile mb-mobile">
            <div class="card-header">
                <h2 class="card-title fs-mobile-2">
                    <i class="fas fa-user-graduate me-2"></i>
                    Olá, <?php echo htmlspecialchars($aluno['nome']); ?>!
                </h2>
                <p class="card-subtitle text-muted mb-0">Acompanhe suas aulas e progresso</p>
            </div>
        </div>
    </div>
</div>
<!-- Notificações -->
<?php if (!empty($notificacoesNaoLidas)): ?>
<div class="row">
    <div class="col-12">
        <div class="card card-mobile mb-mobile">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title fs-mobile-3 mb-0">
                    <i class="fas fa-bell me-2"></i>
                    Notificações
                </h3>
                <span class="badge bg-primary"><?php echo count($notificacoesNaoLidas); ?></span>
            </div>
            <div class="card-body p-0">
                <?php foreach ($notificacoesNaoLidas as $notificacao): ?>
                <div class="border-bottom p-3" data-id="<?php echo $notificacao['id']; ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1"><?php echo htmlspecialchars($notificacao['titulo']); ?></h6>
                            <p class="text-muted mb-1 fs-mobile-6"><?php echo htmlspecialchars($notificacao['mensagem']); ?></p>
                            <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($notificacao['criado_em'])); ?></small>
                        </div>
                        <button class="btn btn-sm btn-outline-primary marcar-lida" data-id="<?php echo $notificacao['id']; ?>">
                            <i class="fas fa-check"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

        <!-- Status do Processo -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-route"></i>
                    Seu Progresso
                </h2>
            </div>
            <div class="progresso-timeline">
                <div class="timeline-item <?php echo $guardaExames ? 'completed' : 'pending'; ?>">
                    <div class="timeline-icon">
                        <i class="fas fa-stethoscope"></i>
                    </div>
                    <div class="timeline-content">
                        <h4>Exames Médico e Psicológico</h4>
                        <p><?php echo $guardaExames ? 'Aprovados' : 'Pendentes'; ?></p>
                    </div>
                </div>
                
                <div class="timeline-item <?php echo $guardaExames ? 'completed' : 'disabled'; ?>">
                    <div class="timeline-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="timeline-content">
                        <h4>Aulas Teóricas</h4>
                        <p><?php echo $guardaExames ? 'Liberadas' : 'Bloqueadas'; ?></p>
                    </div>
                </div>
                
                <div class="timeline-item disabled">
                    <div class="timeline-icon">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="timeline-content">
                        <h4>Aulas Práticas</h4>
                        <p>Após prova teórica</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Próximas Aulas -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-calendar-alt"></i>
                    Próximas Aulas
                </h2>
            </div>
            
            <?php if (empty($proximasAulas)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <h3 class="empty-state-title">Nenhuma aula agendada</h3>
                <p class="empty-state-text">Você não possui aulas agendadas para os próximos 14 dias.</p>
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
                            <?php echo htmlspecialchars($aula['instrutor_nome']); ?>
                        </div>
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
                        <button class="btn btn-sm btn-outline solicitar-reagendamento" 
                                data-aula-id="<?php echo $aula['id']; ?>"
                                data-data="<?php echo $aula['data_aula']; ?>"
                                data-hora="<?php echo $aula['hora_inicio']; ?>">
                            <i class="fas fa-calendar-alt"></i>
                            Reagendar
                        </button>
                        <button class="btn btn-sm btn-danger solicitar-cancelamento" 
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
                <button class="btn btn-outline btn-full" onclick="verFinanceiro()">
                    <i class="fas fa-credit-card"></i>
                    Financeiro
                </button>
                <button class="btn btn-outline btn-full" onclick="contatarCFC()">
                    <i class="fas fa-phone"></i>
                    Contatar CFC
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Solicitação -->
    <div id="modalSolicitacao" class="modal-overlay hidden">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitulo">Solicitar Reagendamento</h3>
            </div>
            <div class="modal-body">
                <form id="formSolicitacao">
                    <input type="hidden" id="aulaId" name="aula_id">
                    <input type="hidden" id="tipoSolicitacao" name="tipo_solicitacao">
                    
                    <div class="form-group">
                        <label class="form-label">Data Atual</label>
                        <input type="text" id="dataAtual" class="form-input" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Horário Atual</label>
                        <input type="text" id="horaAtual" class="form-input" readonly>
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
                            <option value="imprevisto_pessoal">Imprevisto pessoal</option>
                            <option value="problema_saude">Problema de saúde</option>
                            <option value="compromisso_trabalho">Compromisso de trabalho</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Justificativa *</label>
                        <textarea id="justificativa" name="justificativa" class="form-textarea" 
                                  placeholder="Descreva o motivo da solicitação..." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Política:</strong> Solicitações devem ser feitas com no mínimo 24 horas de antecedência.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="fecharModal()">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="enviarSolicitacao()">Enviar Solicitação</button>
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

        function verFinanceiro() {
            window.location.href = 'financeiro.php';
        }

        function contatarCFC() {
            window.location.href = 'contato.php';
        }

        // Funções do modal
        function abrirModal(tipo, aulaId, data, hora) {
            document.getElementById('tipoSolicitacao').value = tipo;
            document.getElementById('aulaId').value = aulaId;
            document.getElementById('dataAtual').value = data;
            document.getElementById('horaAtual').value = hora;
            
            const modal = document.getElementById('modalSolicitacao');
            const titulo = document.getElementById('modalTitulo');
            const novaDataGroup = document.getElementById('novaDataGroup');
            const novaHoraGroup = document.getElementById('novaHoraGroup');
            
            if (tipo === 'reagendamento') {
                titulo.textContent = 'Solicitar Reagendamento';
                novaDataGroup.style.display = 'block';
                novaHoraGroup.style.display = 'block';
            } else {
                titulo.textContent = 'Solicitar Cancelamento';
                novaDataGroup.style.display = 'none';
                novaHoraGroup.style.display = 'none';
            }
            
            modal.classList.remove('hidden');
            modalAberto = true;
        }

        function fecharModal() {
            document.getElementById('modalSolicitacao').classList.add('hidden');
            document.getElementById('formSolicitacao').reset();
            modalAberto = false;
        }

        // Event listeners para botões de ação
        document.addEventListener('DOMContentLoaded', function() {
            // Botões de reagendamento
            document.querySelectorAll('.solicitar-reagendamento').forEach(btn => {
                btn.addEventListener('click', function() {
                    const aulaId = this.dataset.aulaId;
                    const data = this.dataset.data;
                    const hora = this.dataset.hora;
                    abrirModal('reagendamento', aulaId, data, hora);
                });
            });

            // Botões de cancelamento
            document.querySelectorAll('.solicitar-cancelamento').forEach(btn => {
                btn.addEventListener('click', function() {
                    const aulaId = this.dataset.aulaId;
                    const data = this.dataset.data;
                    const hora = this.dataset.hora;
                    abrirModal('cancelamento', aulaId, data, hora);
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
            document.getElementById('modalSolicitacao').addEventListener('click', function(e) {
                if (e.target === this) {
                    fecharModal();
                }
            });
        });

        // Função para enviar solicitação
        async function enviarSolicitacao() {
            const form = document.getElementById('formSolicitacao');
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
                        tipo_solicitacao: formData.get('tipo_solicitacao'),
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
        document.getElementById('formSolicitacao').addEventListener('submit', function(e) {
            e.preventDefault();
        });
    </script>

    <style>
        /* Estilos específicos para o dashboard do aluno */
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

        .progresso-timeline {
            position: relative;
        }

        .timeline-item {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            position: relative;
        }

        .timeline-item:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 20px;
            top: 40px;
            width: 2px;
            height: 20px;
            background: #e2e8f0;
        }

        .timeline-item.completed::after {
            background: #059669;
        }

        .timeline-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
            font-size: 16px;
            color: white;
            background: #e2e8f0;
        }

        .timeline-item.completed .timeline-icon {
            background: #059669;
        }

        .timeline-item.disabled .timeline-icon {
            background: #f1f5f9;
            color: #94a3b8;
        }

        .timeline-content h4 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
            color: #1e293b;
        }

        .timeline-item.disabled .timeline-content h4 {
            color: #94a3b8;
        }

        .timeline-content p {
            font-size: 12px;
            color: #64748b;
        }

        .timeline-item.disabled .timeline-content p {
            color: #94a3b8;
        }

        .aula-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .aula-actions .btn {
            flex: 1;
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
            }
        }
    </style>
</body>
</html>