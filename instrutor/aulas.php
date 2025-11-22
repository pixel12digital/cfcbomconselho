<?php
/**
 * Página de Listagem de Aulas do Instrutor
 * 
 * FASE 1 - Implementação: 2024
 * Arquivo: instrutor/aulas.php
 * 
 * Funcionalidades:
 * - Listar todas as aulas do instrutor (passadas, de hoje, futuras)
 * - Filtros por período (data inicial/final) e status
 * - Ações: visualizar detalhes, cancelar, transferir
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Verificar autenticação
$user = getCurrentUser();
if (!$user || $user['tipo'] !== 'instrutor') {
    $basePath = defined('BASE_PATH') ? BASE_PATH : '';
    header('Location: ' . $basePath . '/login.php');
    exit();
}

$db = db();

// Verificar se precisa trocar senha
try {
    $checkColumn = $db->fetch("SHOW COLUMNS FROM usuarios LIKE 'precisa_trocar_senha'");
    if ($checkColumn) {
        $usuarioCompleto = $db->fetch("SELECT precisa_trocar_senha FROM usuarios WHERE id = ?", [$user['id']]);
        if ($usuarioCompleto && isset($usuarioCompleto['precisa_trocar_senha']) && $usuarioCompleto['precisa_trocar_senha'] == 1) {
            $currentPage = basename($_SERVER['PHP_SELF']);
            if ($currentPage !== 'trocar-senha.php') {
                $basePath = defined('BASE_PATH') ? BASE_PATH : '';
                header('Location: ' . $basePath . '/instrutor/trocar-senha.php?forcado=1');
                exit();
            }
        }
    }
} catch (Exception $e) {
    // Continuar normalmente
}

// Buscar dados do instrutor
$instrutor = $db->fetch("
    SELECT i.*, u.nome as nome_usuario, u.email as email_usuario 
    FROM instrutores i 
    LEFT JOIN usuarios u ON i.usuario_id = u.id 
    WHERE i.usuario_id = ?
", [$user['id']]);

if (!$instrutor) {
    $instrutor = [
        'id' => null,
        'usuario_id' => $user['id'],
        'nome' => $user['nome'] ?? 'Instrutor',
        'nome_usuario' => $user['nome'] ?? 'Instrutor',
        'email_usuario' => $user['email'] ?? '',
        'credencial' => null,
        'cfc_id' => null
    ];
}

$instrutor['nome'] = $instrutor['nome'] ?? $instrutor['nome_usuario'] ?? $user['nome'] ?? 'Instrutor';
$instrutorId = $instrutor['id'] ?? null;

// Processar filtros
$dataInicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$dataFim = $_GET['data_fim'] ?? date('Y-m-d', strtotime('+30 days'));
$statusFiltro = $_GET['status'] ?? '';

// Validar datas
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataInicio) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataFim)) {
    $dataInicio = date('Y-m-d', strtotime('-30 days'));
    $dataFim = date('Y-m-d', strtotime('+30 days'));
}

// Buscar aulas
$aulas = [];
if ($instrutorId) {
    $sql = "
        SELECT a.*, 
               al.nome as aluno_nome, al.telefone as aluno_telefone,
               v.modelo as veiculo_modelo, v.placa as veiculo_placa
        FROM aulas a
        JOIN alunos al ON a.aluno_id = al.id
        LEFT JOIN veiculos v ON a.veiculo_id = v.id
        WHERE a.instrutor_id = ?
          AND a.data_aula >= ?
          AND a.data_aula <= ?
    ";
    
    $params = [$instrutorId, $dataInicio, $dataFim];
    
    if ($statusFiltro && in_array($statusFiltro, ['agendada', 'em_andamento', 'concluida', 'cancelada'])) {
        $sql .= " AND a.status = ?";
        $params[] = $statusFiltro;
    }
    
    $sql .= " ORDER BY a.data_aula DESC, a.hora_inicio DESC";
    
    $aulas = $db->fetchAll($sql, $params);
}

// Estatísticas
$stats = [
    'total' => count($aulas),
    'agendadas' => 0,
    'concluidas' => 0,
    'canceladas' => 0,
    'em_andamento' => 0
];

foreach ($aulas as $aula) {
    if (isset($stats[$aula['status']])) {
        $stats[$aula['status']]++;
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todas as Aulas - <?php echo htmlspecialchars($instrutor['nome']); ?></title>
    <link rel="stylesheet" href="../assets/css/mobile-first.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1>Todas as Aulas</h1>
                <div class="subtitle">Gerencie todas as suas aulas</div>
            </div>
            <a href="dashboard.php" style="color: white; text-decoration: none; padding: 8px 16px; background: rgba(255,255,255,0.2); border-radius: 8px;">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 20px 16px;">
        <!-- Filtros -->
        <div class="card" style="background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 20px;">
            <form method="GET" action="" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; align-items: end;">
                <div>
                    <label for="data_inicio" style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 14px;">Data Inicial</label>
                    <input 
                        type="date" 
                        id="data_inicio" 
                        name="data_inicio" 
                        value="<?php echo htmlspecialchars($dataInicio); ?>"
                        style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;"
                    >
                </div>
                <div>
                    <label for="data_fim" style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 14px;">Data Final</label>
                    <input 
                        type="date" 
                        id="data_fim" 
                        name="data_fim" 
                        value="<?php echo htmlspecialchars($dataFim); ?>"
                        style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;"
                    >
                </div>
                <div>
                    <label for="status" style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 14px;">Status</label>
                    <select 
                        id="status" 
                        name="status" 
                        style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;"
                    >
                        <option value="">Todos</option>
                        <option value="agendada" <?php echo $statusFiltro === 'agendada' ? 'selected' : ''; ?>>Agendada</option>
                        <option value="em_andamento" <?php echo $statusFiltro === 'em_andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                        <option value="concluida" <?php echo $statusFiltro === 'concluida' ? 'selected' : ''; ?>>Concluída</option>
                        <option value="cancelada" <?php echo $statusFiltro === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                    </select>
                </div>
                <div>
                    <button 
                        type="submit" 
                        style="width: 100%; padding: 8px 16px; background: #2563eb; color: white; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer;"
                    >
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>

        <!-- Estatísticas -->
        <div class="grid grid-4" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 20px;">
            <div class="stat-item" style="background: white; padding: 16px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 24px; font-weight: 700; color: #2563eb; margin-bottom: 4px;"><?php echo $stats['total']; ?></div>
                <div style="font-size: 12px; color: #64748b; text-transform: uppercase;">Total</div>
            </div>
            <div class="stat-item" style="background: white; padding: 16px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 24px; font-weight: 700; color: #10b981; margin-bottom: 4px;"><?php echo $stats['agendadas']; ?></div>
                <div style="font-size: 12px; color: #64748b; text-transform: uppercase;">Agendadas</div>
            </div>
            <div class="stat-item" style="background: white; padding: 16px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 24px; font-weight: 700; color: #059669; margin-bottom: 4px;"><?php echo $stats['concluidas']; ?></div>
                <div style="font-size: 12px; color: #64748b; text-transform: uppercase;">Concluídas</div>
            </div>
            <div class="stat-item" style="background: white; padding: 16px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 24px; font-weight: 700; color: #ef4444; margin-bottom: 4px;"><?php echo $stats['canceladas']; ?></div>
                <div style="font-size: 12px; color: #64748b; text-transform: uppercase;">Canceladas</div>
            </div>
        </div>

        <!-- Lista de Aulas -->
        <div class="card" style="background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 20px;">
            <?php if (empty($aulas)): ?>
            <div style="text-align: center; padding: 40px 20px;">
                <i class="fas fa-calendar-times" style="font-size: 48px; color: #cbd5e1; margin-bottom: 16px;"></i>
                <h3 style="color: #64748b; margin-bottom: 8px;">Nenhuma aula encontrada</h3>
                <p style="color: #94a3b8;">Não há aulas no período selecionado.</p>
            </div>
            <?php else: ?>
            <div class="aula-list" style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($aulas as $aula): ?>
                <div class="aula-item" style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; background: #f8fafc;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                <span style="padding: 4px 8px; background: <?php echo $aula['tipo_aula'] === 'teorica' ? '#3b82f6' : '#10b981'; ?>; color: white; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                    <?php echo ucfirst($aula['tipo_aula']); ?>
                                </span>
                                <span style="padding: 4px 8px; background: <?php 
                                    echo $aula['status'] === 'agendada' ? '#fbbf24' : 
                                        ($aula['status'] === 'concluida' ? '#10b981' : 
                                        ($aula['status'] === 'cancelada' ? '#ef4444' : '#3b82f6')); 
                                ?>; color: white; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                    <?php echo ucfirst($aula['status']); ?>
                                </span>
                            </div>
                            <div style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 4px;">
                                <?php echo htmlspecialchars($aula['aluno_nome']); ?>
                            </div>
                            <div style="font-size: 14px; color: #64748b;">
                                <i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($aula['data_aula'])); ?>
                                <i class="fas fa-clock" style="margin-left: 12px;"></i> <?php echo date('H:i', strtotime($aula['hora_inicio'])); ?> - <?php echo date('H:i', strtotime($aula['hora_fim'])); ?>
                            </div>
                            <?php if ($aula['veiculo_modelo']): ?>
                            <div style="font-size: 14px; color: #64748b; margin-top: 4px;">
                                <i class="fas fa-car"></i> <?php echo htmlspecialchars($aula['veiculo_modelo']); ?> - <?php echo htmlspecialchars($aula['veiculo_placa']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Ações -->
                    <?php if ($aula['status'] !== 'cancelada' && $aula['status'] !== 'concluida'): ?>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <?php if ($aula['tipo_aula'] === 'teorica'): ?>
                        <button 
                            class="btn btn-sm btn-primary" 
                            onclick="window.location.href='chamada.php?aula_id=<?php echo $aula['id']; ?>'"
                            style="padding: 6px 12px; background: #3b82f6; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;"
                        >
                            <i class="fas fa-clipboard-list"></i> Chamada
                        </button>
                        <button 
                            class="btn btn-sm btn-secondary" 
                            onclick="window.location.href='diario.php?aula_id=<?php echo $aula['id']; ?>'"
                            style="padding: 6px 12px; background: #64748b; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;"
                        >
                            <i class="fas fa-book"></i> Diário
                        </button>
                        <?php endif; ?>
                        <button 
                            class="btn btn-sm btn-warning solicitar-transferencia" 
                            data-aula-id="<?php echo $aula['id']; ?>"
                            data-data="<?php echo $aula['data_aula']; ?>"
                            data-hora="<?php echo $aula['hora_inicio']; ?>"
                            style="padding: 6px 12px; background: #f59e0b; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;"
                        >
                            <i class="fas fa-exchange-alt"></i> Transferir
                        </button>
                        <button 
                            class="btn btn-sm btn-danger cancelar-aula" 
                            data-aula-id="<?php echo $aula['id']; ?>"
                            data-data="<?php echo $aula['data_aula']; ?>"
                            data-hora="<?php echo $aula['hora_inicio']; ?>"
                            style="padding: 6px 12px; background: #ef4444; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;"
                        >
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal de Cancelamento/Transferência (reutilizado do dashboard) -->
    <div id="modalAcao" class="modal-overlay hidden" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div class="modal" style="background: white; border-radius: 8px; padding: 24px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto;">
            <div class="modal-header" style="margin-bottom: 20px;">
                <h3 class="modal-title" id="modalTitulo" style="font-size: 20px; font-weight: 600; color: #1e293b;">Cancelar Aula</h3>
            </div>
            <div class="modal-body">
                <form id="formAcao">
                    <input type="hidden" id="aulaId" name="aula_id">
                    <input type="hidden" id="tipoAcao" name="tipo_acao">
                    
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 14px;">Data da Aula</label>
                        <input type="text" id="dataAula" readonly style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; background: #f1f5f9;">
                    </div>
                    
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 14px;">Horário</label>
                        <input type="text" id="horaAula" readonly style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; background: #f1f5f9;">
                    </div>
                    
                    <div id="novaDataGroup" style="margin-bottom: 16px; display: none;">
                        <label style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 14px;">Nova Data</label>
                        <input type="date" id="novaData" name="nova_data" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                    </div>
                    
                    <div id="novaHoraGroup" style="margin-bottom: 16px; display: none;">
                        <label style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 14px;">Novo Horário</label>
                        <input type="time" id="novaHora" name="nova_hora" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                    </div>
                    
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 14px;">Motivo</label>
                        <select id="motivo" name="motivo" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px;">
                            <option value="">Selecione um motivo</option>
                            <option value="problema_saude">Problema de saúde</option>
                            <option value="imprevisto_pessoal">Imprevisto pessoal</option>
                            <option value="problema_veiculo">Problema com veículo</option>
                            <option value="ausencia_aluno">Ausência do aluno</option>
                            <option value="condicoes_climaticas">Condições climáticas</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 4px; font-weight: 600; font-size: 14px;">Justificativa <span style="color: #ef4444;">*</span></label>
                        <textarea 
                            id="justificativa" 
                            name="justificativa" 
                            required
                            placeholder="Descreva o motivo da ação..."
                            style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; min-height: 100px; resize: vertical;"
                        ></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 12px;">
                        <button 
                            type="button" 
                            onclick="fecharModal()" 
                            style="flex: 1; padding: 10px; background: #e2e8f0; color: #475569; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;"
                        >
                            Cancelar
                        </button>
                        <button 
                            type="button" 
                            onclick="enviarAcao()" 
                            style="flex: 1; padding: 10px; background: #2563eb; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;"
                        >
                            Confirmar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // FASE 1 - Reutilização: Mesmo código JavaScript do dashboard.php
        // Arquivo: instrutor/aulas.php (linha ~350)
        let modalAberto = false;

        function abrirModal(tipo, aulaId, data, hora) {
            const tipoNormalizado = tipo === 'cancelamento' ? 'cancelamento' : 
                                   tipo === 'transferencia' ? 'transferencia' : 
                                   tipo === 'cancelar' ? 'cancelamento' : 
                                   tipo === 'transferir' ? 'transferencia' : tipo;
            
            document.getElementById('tipoAcao').value = tipoNormalizado;
            document.getElementById('aulaId').value = aulaId;
            document.getElementById('dataAula').value = data;
            document.getElementById('horaAula').value = hora;
            
            const modal = document.getElementById('modalAcao');
            const titulo = document.getElementById('modalTitulo');
            const novaDataGroup = document.getElementById('novaDataGroup');
            const novaHoraGroup = document.getElementById('novaHoraGroup');
            
            if (tipoNormalizado === 'transferencia') {
                titulo.textContent = 'Solicitar Transferência';
                novaDataGroup.style.display = 'block';
                novaHoraGroup.style.display = 'block';
            } else {
                titulo.textContent = 'Cancelar Aula';
                novaDataGroup.style.display = 'none';
                novaHoraGroup.style.display = 'none';
            }
            
            modal.style.display = 'flex';
            modal.classList.remove('hidden');
            modalAberto = true;
        }

        function fecharModal() {
            document.getElementById('modalAcao').style.display = 'none';
            document.getElementById('modalAcao').classList.add('hidden');
            document.getElementById('formAcao').reset();
            modalAberto = false;
        }

        async function enviarAcao() {
            const form = document.getElementById('formAcao');
            const formData = new FormData(form);
            
            if (!formData.get('justificativa').trim()) {
                alert('Por favor, preencha a justificativa.');
                return;
            }

            try {
                const response = await fetch('../admin/api/instrutor-aulas.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        aula_id: formData.get('aula_id'),
                        tipo_acao: formData.get('tipo_acao'),
                        nova_data: formData.get('nova_data'),
                        nova_hora: formData.get('nova_hora'),
                        motivo: formData.get('motivo'),
                        justificativa: formData.get('justificativa')
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert('Ação realizada com sucesso!');
                    fecharModal();
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    alert(result.message || 'Erro ao realizar ação.');
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro de conexão. Tente novamente.');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.cancelar-aula').forEach(btn => {
                btn.addEventListener('click', function() {
                    const aulaId = this.dataset.aulaId;
                    const data = this.dataset.data;
                    const hora = this.dataset.hora;
                    abrirModal('cancelamento', aulaId, data, hora);
                });
            });

            document.querySelectorAll('.solicitar-transferencia').forEach(btn => {
                btn.addEventListener('click', function() {
                    const aulaId = this.dataset.aulaId;
                    const data = this.dataset.data;
                    const hora = this.dataset.hora;
                    abrirModal('transferencia', aulaId, data, hora);
                });
            });

            document.getElementById('modalAcao').addEventListener('click', function(e) {
                if (e.target === this) {
                    fecharModal();
                }
            });
        });
    </script>
</body>
</html>

