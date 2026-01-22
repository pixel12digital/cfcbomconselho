<?php
// Verificar se a página está sendo acessada através do sistema de roteamento
if (!defined('ADMIN_ROUTING') && !isset($veiculo)) {
    // Se não estiver sendo acessada via roteamento, redirecionar
    header('Location: ../index.php?page=agendar-manutencao&veiculo_id=' . ($_GET['veiculo_id'] ?? ''));
    exit;
}

// Verificação de variáveis (sem debug visual)

// Verificar se os dados necessários estão disponíveis
if (!isset($veiculo) || !isset($cfcs)) {
    echo '<div class="alert alert-danger">Erro: Dados não carregados. <a href="?page=veiculos">Voltar para Veículos</a></div>';
    return;
}
?>

<style>
    /* CSS específico para a página de agendamento de manutenção */
    .maintenance-container {
        max-width: 100%;
        padding: 0;
    }
    
    .vehicle-info {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
        color: white;
        padding: 20px;
        border-radius: 15px;
        margin-bottom: 30px;
    }
    
    .form-section {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .maintenance-history {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 15px;
        border-left: 4px solid #ff6b6b;
    }
    
    .maintenance-card {
        background: white;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 10px;
        border-left: 3px solid #ff6b6b;
    }
    
    .btn-maintenance {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
        border: none;
        color: white;
        padding: 12px 30px;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-maintenance:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
        color: white;
    }
    
    .form-control:focus {
        border-color: #ff6b6b;
        box-shadow: 0 0 0 0.2rem rgba(255, 107, 107, 0.25);
    }
    
    .form-select:focus {
        border-color: #ff6b6b;
        box-shadow: 0 0 0 0.2rem rgba(255, 107, 107, 0.25);
    }
</style>

<div class="maintenance-container">
    <!-- Informações do Veículo -->
    <div class="vehicle-info">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h3 class="mb-2">
                    <i class="fas fa-car me-2"></i>
                    <?php echo htmlspecialchars($veiculo['marca'] . ' ' . $veiculo['modelo']); ?>
                </h3>
                <p class="mb-1">
                    <strong>Placa:</strong> <?php echo htmlspecialchars($veiculo['placa']); ?> | 
                    <strong>Ano:</strong> <?php echo $veiculo['ano']; ?> | 
                    <strong>Categoria:</strong> <?php echo htmlspecialchars($veiculo['categoria_cnh']); ?>
                </p>
                <p class="mb-0">
                    <strong>CFC:</strong> <?php echo htmlspecialchars($veiculo['cfc_nome'] ?? 'N/A'); ?>
                </p>
            </div>
            <div class="col-md-4 text-end">
                <div class="vehicle-status">
                    <?php
                    $statusClass = [
                        'ativo' => 'success',
                        'inativo' => 'danger',
                        'manutencao' => 'warning'
                    ];
                    $statusText = [
                        'ativo' => 'Ativo',
                        'inativo' => 'Inativo',
                        'manutencao' => 'Em Manutenção'
                    ];
                    ?>
                    <span class="badge bg-<?php echo $statusClass[$veiculo['status']] ?? 'secondary'; ?> fs-6">
                        <?php echo $statusText[$veiculo['status']] ?? ucfirst($veiculo['status']); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulário de Agendamento de Manutenção -->
    <div class="form-section">
        <h4 class="mb-4">
            <i class="fas fa-tools me-2"></i>Agendar Manutenção
        </h4>
        
        <form id="formManutencao" method="POST" action="api/manutencao.php">
            <input type="hidden" name="acao" value="agendar">
            <input type="hidden" name="veiculo_id" value="<?php echo $veiculo['id']; ?>">
            <input type="hidden" name="manutencao_id" value="">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="tipo_manutencao" class="form-label">
                            <i class="fas fa-wrench me-1"></i>Tipo de Manutenção *
                        </label>
                        <select class="form-select" id="tipo_manutencao" name="tipo_manutencao" required>
                            <option value="">Selecione o tipo...</option>
                            <option value="preventiva">Manutenção Preventiva</option>
                            <option value="corretiva">Manutenção Corretiva</option>
                            <option value="revisao">Revisão Geral</option>
                            <option value="troca_oleo">Troca de Óleo</option>
                            <option value="pneus">Troca de Pneus</option>
                            <option value="freios">Sistema de Freios</option>
                            <option value="bateria">Bateria</option>
                            <option value="ar_condicionado">Ar Condicionado</option>
                            <option value="outros">Outros</option>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="data_manutencao" class="form-label">
                            <i class="fas fa-calendar me-1"></i>Data da Manutenção *
                        </label>
                        <input type="date" class="form-control" id="data_manutencao" name="data_manutencao" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="hora_inicio" class="form-label">
                            <i class="fas fa-clock me-1"></i>Hora de Início *
                        </label>
                        <input type="time" class="form-control" id="hora_inicio" name="hora_inicio" required>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="hora_fim" class="form-label">
                            <i class="fas fa-clock me-1"></i>Hora de Término *
                        </label>
                        <input type="time" class="form-control" id="hora_fim" name="hora_fim" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="quilometragem_atual" class="form-label">
                            <i class="fas fa-tachometer-alt me-1"></i>Quilometragem Atual
                        </label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="quilometragem_atual" name="quilometragem_atual" 
                                   min="0" value="<?php echo $veiculo['quilometragem'] ?? ''; ?>">
                            <span class="input-group-text">km</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="custo_estimado" class="form-label">
                            <i class="fas fa-dollar-sign me-1"></i>Custo Estimado
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" class="form-control" id="custo_estimado" name="custo_estimado" 
                                   placeholder="0,00">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="observacoes" class="form-label">
                    <i class="fas fa-sticky-note me-1"></i>Observações
                </label>
                <textarea class="form-control" id="observacoes" name="observacoes" rows="3" 
                          placeholder="Descreva os problemas encontrados, peças que precisam ser trocadas, etc..."></textarea>
            </div>
            
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="alterar_status" name="alterar_status" value="1" checked>
                    <label class="form-check-label" for="alterar_status">
                        Alterar status do veículo para "Em Manutenção" durante o período
                    </label>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="?page=veiculos" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Voltar para Veículos
                </a>
                <button type="submit" class="btn btn-maintenance">
                    <i class="fas fa-tools me-1"></i>Agendar Manutenção
                </button>
            </div>
        </form>
    </div>

    <!-- Histórico de Manutenções -->
    <div class="maintenance-history">
        <h5 class="mb-3">
            <i class="fas fa-history me-2"></i>Histórico de Manutenções
        </h5>
        
        <div id="historicoManutencoes">
            <div class="text-center text-muted py-4">
                <i class="fas fa-tools fa-3x mb-3"></i>
                <p>Carregando histórico de manutenções...</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Definir data mínima como hoje
    const hoje = new Date().toISOString().split('T')[0];
    document.getElementById('data_manutencao').min = hoje;
    
    // Carregar histórico de manutenções
    carregarHistoricoManutencoes();
    
    // Configurar máscara para custo estimado
    if (typeof IMask !== 'undefined') {
        new IMask(document.getElementById('custo_estimado'), {
            mask: Number,
            scale: 2,
            thousandsSeparator: '.',
            padFractionalZeros: false,
            radix: ',',
            mapToRadix: ['.'],
            normalizeZeros: true,
            min: 0,
            max: 999999.99
        });
    }
    
    // Validação do formulário
    document.getElementById('formManutencao').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const horaInicio = document.getElementById('hora_inicio').value;
        const horaFim = document.getElementById('hora_fim').value;
        
        if (horaInicio && horaFim && horaInicio >= horaFim) {
            alert('A hora de término deve ser posterior à hora de início.');
            return;
        }
        
        // Enviar formulário
        enviarManutencao();
    });
});

function carregarHistoricoManutencoes() {
    const veiculoId = <?php echo $veiculo['id']; ?>;
    
    fetch(`api/manutencao.php?veiculo_id=${veiculoId}`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('historicoManutencoes');
            
            if (data.success && data.data && data.data.length > 0) {
                let html = '';
                data.data.forEach(manutencao => {
                    const dataFormatada = new Date(manutencao.data_manutencao).toLocaleDateString('pt-BR');
                    const statusClass = manutencao.status === 'concluida' ? 'success' : 
                                      manutencao.status === 'em_andamento' ? 'warning' : 'secondary';
                    const statusText = manutencao.status === 'concluida' ? 'Concluída' : 
                                     manutencao.status === 'em_andamento' ? 'Em Andamento' : 'Agendada';
                    
                    // Determinar quais botões mostrar baseado no status
                    let botoesAcao = '';
                    if (manutencao.status === 'agendada') {
                        botoesAcao = `
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-primary" onclick="editarManutencao(${manutencao.id})" title="Editar manutenção">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-outline-info" onclick="visualizarManutencao(${manutencao.id})" title="Ver detalhes">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger" onclick="excluirManutencao(${manutencao.id})" title="Excluir manutenção">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        `;
                    } else if (manutencao.status === 'em_andamento') {
                        botoesAcao = `
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-info" onclick="visualizarManutencao(${manutencao.id})" title="Ver detalhes">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-outline-success" onclick="concluirManutencao(${manutencao.id})" title="Concluir manutenção">
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>
                        `;
                    } else {
                        botoesAcao = `
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-info" onclick="visualizarManutencao(${manutencao.id})" title="Ver detalhes">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        `;
                    }
                    
                    html += `
                        <div class="maintenance-card">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <strong>${manutencao.tipo_manutencao}</strong>
                                    <br><small class="text-muted">${dataFormatada}</small>
                                </div>
                                <div class="col-md-2">
                                    <span class="badge bg-${statusClass}">${statusText}</span>
                                </div>
                                <div class="col-md-2">
                                    ${manutencao.custo_real ? `R$ ${parseFloat(manutencao.custo_real).toLocaleString('pt-BR', {minimumFractionDigits: 2})}` : 
                                      manutencao.custo_estimado ? `R$ ${parseFloat(manutencao.custo_estimado).toLocaleString('pt-BR', {minimumFractionDigits: 2})}` : '-'}
                                </div>
                                <div class="col-md-4">
                                    ${manutencao.observacoes ? `<small class="text-muted">${manutencao.observacoes.substring(0, 60)}${manutencao.observacoes.length > 60 ? '...' : ''}</small>` : ''}
                                </div>
                                <div class="col-md-2 text-end">
                                    ${botoesAcao}
                                </div>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-tools fa-3x mb-3"></i>
                        <p>Nenhuma manutenção registrada para este veículo.</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Erro ao carregar histórico:', error);
            document.getElementById('historicoManutencoes').innerHTML = `
                <div class="text-center text-danger py-4">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                    <p>Erro ao carregar histórico de manutenções.</p>
                </div>
            `;
        });
}

function enviarManutencao() {
    const formData = new FormData(document.getElementById('formManutencao'));
    const acao = formData.get('acao');
    const manutencaoId = formData.get('manutencao_id');
    
    // Determinar método HTTP baseado na ação
    const method = acao === 'editar' ? 'PUT' : 'POST';
    const url = acao === 'editar' ? `api/manutencao.php?id=${manutencaoId}` : 'api/manutencao.php';
    
    fetch(url, {
        method: method,
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const mensagem = acao === 'editar' ? 'Manutenção atualizada com sucesso!' : 'Manutenção agendada com sucesso!';
            alert(mensagem);
            
            // Recarregar histórico
            carregarHistoricoManutencoes();
            
            // Limpar formulário e resetar para modo criação
            document.getElementById('formManutencao').reset();
            document.querySelector('input[name="acao"]').value = 'agendar';
            document.querySelector('input[name="manutencao_id"]').value = '';
            document.querySelector('.form-section h4').innerHTML = '<i class="fas fa-tools me-2"></i>Agendar Manutenção';
            document.querySelector('button[type="submit"]').innerHTML = '<i class="fas fa-tools me-1"></i>Agendar Manutenção';
            
        } else {
            const mensagemErro = acao === 'editar' ? 'Erro ao atualizar manutenção: ' : 'Erro ao agendar manutenção: ';
            alert(mensagemErro + (data.error || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        const mensagemErro = acao === 'editar' ? 'Erro ao atualizar manutenção: ' : 'Erro ao agendar manutenção: ';
        alert(mensagemErro + error.message);
    });
}

// Funções para ações dos botões de manutenção
function editarManutencao(id) {
    // Buscar dados da manutenção
    fetch(`api/manutencao.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const manutencao = data.data;
                
                // Preencher formulário com dados da manutenção
                document.getElementById('tipo_manutencao').value = manutencao.tipo_manutencao;
                document.getElementById('data_manutencao').value = manutencao.data_manutencao;
                document.getElementById('hora_inicio').value = manutencao.hora_inicio.substring(0, 5);
                document.getElementById('hora_fim').value = manutencao.hora_fim.substring(0, 5);
                document.getElementById('quilometragem_atual').value = manutencao.quilometragem_atual || '';
                document.getElementById('custo_estimado').value = manutencao.custo_estimado || '';
                document.getElementById('observacoes').value = manutencao.observacoes || '';
                
                // Alterar ação do formulário para edição
                document.querySelector('input[name="acao"]').value = 'editar';
                document.querySelector('input[name="manutencao_id"]').value = id;
                
                // Alterar título e botão
                document.querySelector('.form-section h4').innerHTML = '<i class="fas fa-edit me-2"></i>Editar Manutenção';
                document.querySelector('button[type="submit"]').innerHTML = '<i class="fas fa-save me-1"></i>Salvar Alterações';
                
                // Scroll para o formulário
                document.querySelector('.form-section').scrollIntoView({ behavior: 'smooth' });
                
            } else {
                alert('Erro ao carregar dados da manutenção: ' + (data.error || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao carregar dados da manutenção: ' + error.message);
        });
}

function visualizarManutencao(id) {
    // Buscar dados da manutenção
    fetch(`api/manutencao.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const manutencao = data.data;
                
                // Criar modal de visualização
                const modalHtml = `
                    <div class="modal fade" id="modalVisualizarManutencao" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        <i class="fas fa-eye me-2"></i>Detalhes da Manutenção
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-info-circle me-2"></i>Informações Básicas</h6>
                                            <p><strong>Tipo:</strong> ${manutencao.tipo_manutencao}</p>
                                            <p><strong>Data:</strong> ${new Date(manutencao.data_manutencao).toLocaleDateString('pt-BR')}</p>
                                            <p><strong>Horário:</strong> ${manutencao.hora_inicio.substring(0, 5)} - ${manutencao.hora_fim.substring(0, 5)}</p>
                                            <p><strong>Status:</strong> <span class="badge bg-${manutencao.status === 'concluida' ? 'success' : manutencao.status === 'em_andamento' ? 'warning' : 'secondary'}">${manutencao.status === 'concluida' ? 'Concluída' : manutencao.status === 'em_andamento' ? 'Em Andamento' : 'Agendada'}</span></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-car me-2"></i>Veículo</h6>
                                            <p><strong>Veículo:</strong> ${manutencao.marca} ${manutencao.modelo}</p>
                                            <p><strong>Placa:</strong> ${manutencao.placa}</p>
                                            <p><strong>CFC:</strong> ${manutencao.cfc_nome || 'N/A'}</p>
                                        </div>
                                    </div>
                                    ${manutencao.quilometragem_atual ? `
                                    <hr>
                                    <h6><i class="fas fa-tachometer-alt me-2"></i>Quilometragem</h6>
                                    <p><strong>Quilometragem Atual:</strong> ${manutencao.quilometragem_atual} km</p>
                                    ` : ''}
                                    ${manutencao.custo_estimado || manutencao.custo_real ? `
                                    <hr>
                                    <h6><i class="fas fa-dollar-sign me-2"></i>Custos</h6>
                                    ${manutencao.custo_estimado ? `<p><strong>Custo Estimado:</strong> R$ ${parseFloat(manutencao.custo_estimado).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>` : ''}
                                    ${manutencao.custo_real ? `<p><strong>Custo Real:</strong> R$ ${parseFloat(manutencao.custo_real).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>` : ''}
                                    ` : ''}
                                    ${manutencao.observacoes ? `
                                    <hr>
                                    <h6><i class="fas fa-sticky-note me-2"></i>Observações</h6>
                                    <p>${manutencao.observacoes}</p>
                                    ` : ''}
                                    ${manutencao.observacoes_finais ? `
                                    <hr>
                                    <h6><i class="fas fa-clipboard-check me-2"></i>Observações Finais</h6>
                                    <p>${manutencao.observacoes_finais}</p>
                                    ` : ''}
                                    <hr>
                                    <h6><i class="fas fa-clock me-2"></i>Datas</h6>
                                    <p><strong>Criado em:</strong> ${new Date(manutencao.created_at).toLocaleString('pt-BR')}</p>
                                    ${manutencao.updated_at ? `<p><strong>Atualizado em:</strong> ${new Date(manutencao.updated_at).toLocaleString('pt-BR')}</p>` : ''}
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                    ${manutencao.status === 'agendada' ? `
                                    <button type="button" class="btn btn-primary" onclick="editarManutencao(${manutencao.id}); bootstrap.Modal.getInstance(document.getElementById('modalVisualizarManutencao')).hide();">
                                        <i class="fas fa-edit me-1"></i>Editar
                                    </button>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Remover modal anterior se existir
                const modalAnterior = document.getElementById('modalVisualizarManutencao');
                if (modalAnterior) {
                    modalAnterior.remove();
                }
                
                // Adicionar modal ao DOM
                document.body.insertAdjacentHTML('beforeend', modalHtml);
                
                // Mostrar modal
                const modal = new bootstrap.Modal(document.getElementById('modalVisualizarManutencao'));
                modal.show();
                
            } else {
                alert('Erro ao carregar dados da manutenção: ' + (data.error || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao carregar dados da manutenção: ' + error.message);
        });
}

function excluirManutencao(id) {
    if (!confirm('⚠️ ATENÇÃO: Esta ação não pode ser desfeita!\n\nDeseja realmente excluir esta manutenção?')) {
        return;
    }
    
    // Usar POST com _method=DELETE para compatibilidade
    const formData = new FormData();
    formData.append('_method', 'DELETE');
    formData.append('id', id);
    
    fetch('api/manutencao.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Manutenção excluída com sucesso!');
            carregarHistoricoManutencoes();
        } else {
            alert('Erro ao excluir manutenção: ' + (data.error || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao excluir manutenção: ' + error.message);
    });
}

function concluirManutencao(id) {
    const custoReal = prompt('Informe o custo real da manutenção (opcional):');
    const observacoesFinais = prompt('Observações finais (opcional):');
    
    const formData = new FormData();
    formData.append('acao', 'concluir');
    formData.append('id', id);
    if (custoReal) formData.append('custo_real', custoReal);
    if (observacoesFinais) formData.append('observacoes_finais', observacoesFinais);
    
    fetch('api/manutencao.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Manutenção concluída com sucesso!');
            carregarHistoricoManutencoes();
        } else {
            alert('Erro ao concluir manutenção: ' + (data.error || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao concluir manutenção: ' + error.message);
    });
}
</script>
