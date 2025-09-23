<?php
// Verificar se as variáveis estão definidas
if (!isset($cfcs)) $cfcs = [];
if (!isset($usuarios)) $usuarios = [];
if (!isset($mensagem)) $mensagem = '';
if (!isset($tipo_mensagem)) $tipo_mensagem = 'info';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-building me-2"></i>Gestão de CFCs
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportarCFCs()">
                <i class="fas fa-download me-1"></i>Exportar
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="imprimirCFCs()">
                <i class="fas fa-print me-1"></i>Imprimir
            </button>
        </div>
        <button type="button" class="btn btn-primary" onclick="abrirModalCFC('criar')">
            <i class="fas fa-plus me-1"></i>Novo CFC
        </button>
    </div>
</div>

<!-- Mensagens de Feedback -->
<?php if (!empty($mensagem)): ?>
<div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($mensagem); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filtros e Busca -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
            <input type="text" class="form-control" id="buscaCFC" placeholder="Buscar CFC por nome, CNPJ ou cidade...">
        </div>
    </div>
    <div class="col-md-3">
        <select class="form-select" id="filtroStatus">
            <option value="">Todos os Status</option>
            <option value="ativo">Ativo</option>
            <option value="inativo">Inativo</option>
        </select>
    </div>
    <div class="col-md-3">
        <select class="form-select" id="filtroCidade">
            <option value="">Todas as Cidades</option>
            <!-- Será preenchido via JavaScript -->
        </select>
    </div>
</div>

<!-- Tabela de CFCs -->
<div class="card shadow">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Lista de CFCs</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive table-container">
            <table class="table table-striped table-hover" id="tabelaCFCs">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>CNPJ</th>
                        <th>Cidade/UF</th>
                        <th>Telefone</th>
                        <th>Responsável</th>
                        <th>Status</th>
                        <th>Alunos</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cfcs)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>Nenhum CFC cadastrado ainda.</p>
                            <button class="btn btn-primary" onclick="abrirModalCFC('criar')">
                                <i class="fas fa-plus me-1"></i>Cadastrar Primeiro CFC
                            </button>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($cfcs as $cfc): ?>
                        <tr data-cfc-id="<?php echo $cfc['id']; ?>">
                            <td><?php echo $cfc['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($cfc['nome']); ?></strong>
                                <?php if ($cfc['email']): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($cfc['email']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code><?php echo htmlspecialchars($cfc['cnpj']); ?></code>
                            </td>
                            <td>
                                <?php if ($cfc['cidade'] && $cfc['uf']): ?>
                                    <?php echo htmlspecialchars($cfc['cidade']) . '/' . htmlspecialchars($cfc['uf']); ?>
                                <?php else: ?>
                                    <span class="text-muted">Não informado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($cfc['telefone']): ?>
                                    <a href="tel:<?php echo htmlspecialchars($cfc['telefone']); ?>" class="text-decoration-none">
                                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($cfc['telefone']); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">Não informado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($cfc['responsavel_nome']): ?>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($cfc['responsavel_nome']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Não definido</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($cfc['ativo']): ?>
                                    <span class="badge bg-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-primary"><?php echo $cfc['total_alunos'] ?? 0; ?></span>
                            </td>
                            <td>
                                <div class="action-buttons-container">
                                    <!-- Botões principais em linha -->
                                    <div class="action-buttons-primary">
                                        <button type="button" class="btn btn-edit action-btn" 
                                                onclick="editarCFC(<?php echo $cfc['id']; ?>)" 
                                                title="Editar dados do CFC">
                                            <i class="fas fa-edit me-1"></i>Editar
                                        </button>
                                        <button type="button" class="btn btn-view action-btn" 
                                                onclick="visualizarCFC(<?php echo $cfc['id']; ?>)" 
                                                title="Ver detalhes completos do CFC">
                                            <i class="fas fa-eye me-1"></i>Ver
                                        </button>
                                        <button type="button" class="btn btn-manage action-btn" 
                                                onclick="gerenciarCFC(<?php echo $cfc['id']; ?>)" 
                                                title="Gerenciar instrutores, alunos e veículos">
                                            <i class="fas fa-cogs me-1"></i>Gerenciar
                                        </button>
                                    </div>
                                    
                                    <!-- Botões secundários em linha -->
                                    <div class="action-buttons-secondary">
                                        <?php if ($cfc['ativo']): ?>
                                        <button type="button" class="btn btn-toggle action-btn" 
                                                onclick="desativarCFC(<?php echo $cfc['id']; ?>)" 
                                                title="Desativar CFC (não poderá operar)">
                                            <i class="fas fa-ban me-1"></i>Desativar
                                        </button>
                                        <?php else: ?>
                                        <button type="button" class="btn btn-schedule action-btn" 
                                                onclick="ativarCFC(<?php echo $cfc['id']; ?>)" 
                                                title="Reativar CFC para operação">
                                            <i class="fas fa-check me-1"></i>Ativar
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Botão de exclusão destacado -->
                                    <div class="action-buttons-danger">
                                        <button type="button" class="btn btn-delete action-btn" 
                                                onclick="excluirCFC(<?php echo $cfc['id']; ?>)" 
                                                title="⚠️ EXCLUIR CFC - Esta ação não pode ser desfeita!">
                                            <i class="fas fa-trash me-1"></i>Excluir
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Layout em cards para mobile -->
        <div class="mobile-cfc-cards" style="display: none;">
            <?php if (!empty($cfcs)): ?>
                <?php foreach ($cfcs as $cfc): ?>
                <div class="mobile-cfc-card" data-cfc-id="<?php echo $cfc['id']; ?>">
                    <div class="mobile-cfc-header">
                        <div class="mobile-cfc-title">
                            <strong><?php echo htmlspecialchars($cfc['nome']); ?></strong>
                            <span class="mobile-cfc-id">#<?php echo $cfc['id']; ?></span>
                        </div>
                        <div class="mobile-cfc-status">
                            <?php if ($cfc['ativo']): ?>
                                <span class="badge bg-success">ATIVO</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">INATIVO</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mobile-cfc-body">
                        <div class="mobile-cfc-field">
                            <span class="mobile-cfc-label">CNPJ</span>
                            <span class="mobile-cfc-value"><?php echo htmlspecialchars($cfc['cnpj']); ?></span>
                        </div>
                        
                        <?php if ($cfc['email']): ?>
                        <div class="mobile-cfc-field">
                            <span class="mobile-cfc-label">Email</span>
                            <span class="mobile-cfc-value"><?php echo htmlspecialchars($cfc['email']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($cfc['cidade'] && $cfc['uf']): ?>
                        <div class="mobile-cfc-field">
                            <span class="mobile-cfc-label">Cidade/UF</span>
                            <span class="mobile-cfc-value"><?php echo htmlspecialchars($cfc['cidade']) . '/' . htmlspecialchars($cfc['uf']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($cfc['telefone']): ?>
                        <div class="mobile-cfc-field">
                            <span class="mobile-cfc-label">Telefone</span>
                            <span class="mobile-cfc-value"><?php echo htmlspecialchars($cfc['telefone']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($cfc['responsavel']): ?>
                        <div class="mobile-cfc-field">
                            <span class="mobile-cfc-label">Responsável</span>
                            <span class="mobile-cfc-value"><?php echo htmlspecialchars($cfc['responsavel']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mobile-cfc-field">
                            <span class="mobile-cfc-label">Alunos</span>
                            <span class="mobile-cfc-value"><?php echo isset($cfc['total_alunos']) ? $cfc['total_alunos'] : '0'; ?></span>
                        </div>
                    </div>
                    
                    <div class="mobile-cfc-actions">
                        <button type="button" class="btn btn-sm btn-primary" onclick="editarCFC(<?php echo $cfc['id']; ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-info" onclick="visualizarCFC(<?php echo $cfc['id']; ?>)">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-warning" onclick="gerenciarCFC(<?php echo $cfc['id']; ?>)">
                            <i class="fas fa-cogs"></i>
                        </button>
                        <?php if ($cfc['ativo']): ?>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="desativarCFC(<?php echo $cfc['id']; ?>)">
                            <i class="fas fa-ban"></i>
                        </button>
                        <?php else: ?>
                        <button type="button" class="btn btn-sm btn-success" onclick="ativarCFC(<?php echo $cfc['id']; ?>)">
                            <i class="fas fa-check"></i>
                        </button>
                        <?php endif; ?>
                        <button type="button" class="btn btn-sm btn-danger" onclick="excluirCFC(<?php echo $cfc['id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Bootstrap para Cadastro/Edição de CFC -->
<div class="modal fade" id="modalCFC" tabindex="-1" aria-labelledby="modalCFCLabel" aria-hidden="true">
    <div class="modal-dialog modal-custom-cfc">
        <div class="modal-content">
            <form id="formCFC" onsubmit="return false;">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fas fa-building me-2"></i>Novo CFC
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="acao" id="acaoCFC" value="criar">
                    <input type="hidden" name="cfc_id" id="cfc_id" value="">
                    
                    <div class="container-fluid">
                        <!-- Seção 1: Informações Básicas -->
                        <div class="row mb-2">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-1 mb-2">
                                    <i class="fas fa-building me-1"></i>Informações Básicas
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-1">
                                    <label for="nome" class="form-label">Nome do CFC *</label>
                                    <input type="text" class="form-control" id="nome" name="nome" required 
                                           placeholder="Nome completo do Centro de Formação de Condutores">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-1">
                                    <label for="cnpj" class="form-label">CNPJ *</label>
                                    <input type="text" class="form-control" id="cnpj" name="cnpj" required 
                                           placeholder="00.000.000/0000-00">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-2">
                            <div class="col-md-12">
                                <div class="mb-1">
                                    <label for="razao_social" class="form-label">Razão Social</label>
                                    <input type="text" class="form-control" id="razao_social" name="razao_social" 
                                           placeholder="Razão social da empresa (opcional)">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Seção 2: Contato -->
                        <div class="row mb-2">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-1 mb-2">
                                    <i class="fas fa-phone me-1"></i>Contato
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-1">
                                    <label for="email" class="form-label">E-mail</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           placeholder="contato@cfc.com.br">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-1">
                                    <label for="telefone" class="form-label">Telefone</label>
                                    <input type="text" class="form-control" id="telefone" name="telefone" 
                                           placeholder="(00) 00000-0000">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Seção 3: Endereço -->
                        <div class="row mb-2">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-1 mb-2">
                                    <i class="fas fa-map-marker-alt me-1"></i>Endereço
                                </h6>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-1">
                                    <label for="cep" class="form-label">CEP</label>
                                    <input type="text" class="form-control" id="cep" name="cep" 
                                           placeholder="00000-000">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-1">
                                    <label for="endereco" class="form-label">Endereço</label>
                                    <input type="text" class="form-control" id="endereco" name="endereco" 
                                           placeholder="Rua, Avenida, número, etc.">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-1">
                                    <label for="bairro" class="form-label">Bairro</label>
                                    <input type="text" class="form-control" id="bairro" name="bairro" 
                                           placeholder="Centro, Jardim, etc.">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-1">
                                    <label for="cidade" class="form-label">Cidade</label>
                                    <input type="text" class="form-control" id="cidade" name="cidade" 
                                           placeholder="Nome da cidade">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-1">
                                    <label for="uf" class="form-label">UF</label>
                                    <select class="form-select" id="uf" name="uf">
                                        <option value="">Selecione...</option>
                                        <option value="AC">Acre</option>
                                        <option value="AL">Alagoas</option>
                                        <option value="AP">Amapá</option>
                                        <option value="AM">Amazonas</option>
                                        <option value="BA">Bahia</option>
                                        <option value="CE">Ceará</option>
                                        <option value="DF">Distrito Federal</option>
                                        <option value="ES">Espírito Santo</option>
                                        <option value="GO">Goiás</option>
                                        <option value="MA">Maranhão</option>
                                        <option value="MT">Mato Grosso</option>
                                        <option value="MS">Mato Grosso do Sul</option>
                                        <option value="MG">Minas Gerais</option>
                                        <option value="PA">Pará</option>
                                        <option value="PB">Paraíba</option>
                                        <option value="PR">Paraná</option>
                                        <option value="PE">Pernambuco</option>
                                        <option value="PI">Piauí</option>
                                        <option value="RJ">Rio de Janeiro</option>
                                        <option value="RN">Rio Grande do Norte</option>
                                        <option value="RS">Rio Grande do Sul</option>
                                        <option value="RO">Rondônia</option>
                                        <option value="RR">Roraima</option>
                                        <option value="SC">Santa Catarina</option>
                                        <option value="SP">São Paulo</option>
                                        <option value="SE">Sergipe</option>
                                        <option value="TO">Tocantins</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Seção 4: Configurações -->
                        <div class="row mb-2">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-1 mb-2">
                                    <i class="fas fa-cog me-1"></i>Configurações
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-1">
                                    <label for="responsavel_id" class="form-label">Responsável</label>
                                    <select class="form-select" id="responsavel_id" name="responsavel_id">
                                        <option value="">Selecione um usuário...</option>
                                        <?php foreach ($usuarios as $usuario): ?>
                                            <?php if ($usuario['tipo'] === 'admin' || $usuario['tipo'] === 'instrutor'): ?>
                                            <option value="<?php echo $usuario['id']; ?>">
                                                <?php echo htmlspecialchars($usuario['nome']); ?> 
                                                (<?php echo ucfirst($usuario['tipo']); ?>)
                                            </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-1">
                                    <label for="ativo" class="form-label">Status</label>
                                    <select class="form-select" id="ativo" name="ativo">
                                        <option value="1">Ativo</option>
                                        <option value="0">Inativo</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Seção 5: Observações -->
                        <div class="row">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-1 mb-2">
                                    <i class="fas fa-sticky-note me-1"></i>Observações
                                </h6>
                                <div class="mb-1">
                                    <label for="observacoes" class="form-label">Observações</label>
                                    <textarea class="form-control" id="observacoes" name="observacoes" rows="3" 
                                              placeholder="Informações adicionais sobre o CFC..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSalvarCFC">
                        <i class="fas fa-save me-1"></i>Salvar CFC
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Visualização de CFC -->
<div class="modal fade" id="modalVisualizarCFC" tabindex="-1" aria-labelledby="modalVisualizarCFCLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVisualizarCFCLabel">
                    <i class="fas fa-eye me-2"></i>Detalhes do CFC
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalVisualizarCFCBody">
                <!-- Conteúdo será carregado via JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" id="btnEditarVisualizacao" onclick="editarCFCDaVisualizacao()">
                    <i class="fas fa-edit me-1"></i>Editar CFC
                </button>
            </div>
        </div>
    </div>
</div>

<!-- CSS específico para CFCs -->
<link href="assets/css/cfcs.css" rel="stylesheet">

<!-- JavaScript específico para CFCs -->
<script src="assets/js/cfcs.js"></script>

<!-- Scripts específicos para CFCs (adicionais) -->
<script>
// Funcionalidades adicionais específicas da página
console.log('🔧 Carregando funcionalidades específicas da página CFCs...');

// Função para teste manual dos caminhos da API
async function testarCaminhosManual() {
    console.log('🧪 Teste manual dos caminhos da API...');
    
    try {
        // Testar a função do arquivo cfcs.js
        if (typeof detectarCaminhoAPI === 'function') {
            const caminho = await detectarCaminhoAPI();
            alert('✅ Caminho da API detectado: ' + caminho);
            
            // Testar uma requisição real
            const response = await fetch(caminho, {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            if (response.ok) {
                alert('✅ API funcionando! Status: ' + response.status);
            } else {
                alert('⚠️ API respondeu mas com erro: ' + response.status);
            }
        } else {
            alert('❌ Função detectarCaminhoAPI não encontrada!');
        }
    } catch (error) {
        alert('❌ Erro ao testar API: ' + error.message);
    }
}

// Função para limpar cache da API
function limparCacheAPI() {
    console.log('🧹 Limpando cache da API...');
    if (typeof window.caminhoAPICache !== 'undefined') {
        window.caminhoAPICache = null;
    }
    alert('✅ Cache da API limpo!');
}

// Funcionalidades adicionais para exportar e imprimir
function exportarCFCs() {
    console.log('📊 Exportando CFCs...');
    alert('Funcionalidade de exportação será implementada em breve.');
}

function imprimirCFCs() {
    window.print();
}

console.log('✅ Funcionalidades específicas da página carregadas!');
</script>

<!-- CSS Inline CRÍTICO - Deve ser carregado DEPOIS do Bootstrap -->
<style>
/* SOLUÇÃO AGGRESSIVA: CSS com MÁXIMA ESPECIFICIDADE para modal de CFC */
/* Sobrescrever TODOS os possíveis seletores do Bootstrap */
.modal#modalCFC .modal-dialog,
#modalCFC .modal-dialog,
.modal#modalCFC .modal-dialog.modal-custom-cfc,
#modalCFC .modal-dialog.modal-custom-cfc,
.modal.show#modalCFC .modal-dialog,
.modal.fade#modalCFC .modal-dialog,
.modal.show#modalCFC .modal-dialog.modal-custom-cfc,
.modal.fade#modalCFC .modal-dialog.modal-custom-cfc {
    max-width: 1200px !important;
    width: 1200px !important;
    margin: 2rem auto !important;
    position: relative !important;
    left: auto !important;
    right: auto !important;
    transform: none !important;
    box-sizing: border-box !important;
}

/* Responsividade otimizada - MÁXIMA ESPECIFICIDADE */
@media (max-width: 1400px) {
    .modal#modalCFC .modal-dialog,
    #modalCFC .modal-dialog,
    .modal#modalCFC .modal-dialog.modal-custom-cfc,
    #modalCFC .modal-dialog.modal-custom-cfc,
    .modal.show#modalCFC .modal-dialog,
    .modal.fade#modalCFC .modal-dialog,
    .modal.show#modalCFC .modal-dialog.modal-custom-cfc,
    .modal.fade#modalCFC .modal-dialog.modal-custom-cfc {
        max-width: 95vw !important;
        width: 95vw !important;
        margin: 1.5rem auto !important;
    }
}

@media (max-width: 1200px) {
    .modal#modalCFC .modal-dialog,
    #modalCFC .modal-dialog,
    .modal#modalCFC .modal-dialog.modal-custom-cfc,
    #modalCFC .modal-dialog.modal-custom-cfc,
    .modal.show#modalCFC .modal-dialog,
    .modal.fade#modalCFC .modal-dialog,
    .modal.show#modalCFC .modal-dialog.modal-custom-cfc,
    .modal.fade#modalCFC .modal-dialog.modal-custom-cfc {
        max-width: 90vw !important;
        width: 90vw !important;
        margin: 1rem auto !important;
    }
}

@media (max-width: 768px) {
    .modal#modalCFC .modal-dialog,
    #modalCFC .modal-dialog,
    .modal#modalCFC .modal-dialog.modal-custom-cfc,
    #modalCFC .modal-dialog.modal-custom-cfc,
    .modal.show#modalCFC .modal-dialog,
    .modal.fade#modalCFC .modal-dialog,
    .modal.show#modalCFC .modal-dialog.modal-custom-cfc,
    .modal.fade#modalCFC .modal-dialog.modal-custom-cfc {
        max-width: 95vw !important;
        width: 95vw !important;
        margin: 0.5rem auto !important;
    }
}

@media (max-width: 576px) {
    .modal#modalCFC .modal-dialog,
    #modalCFC .modal-dialog,
    .modal#modalCFC .modal-dialog.modal-custom-cfc,
    #modalCFC .modal-dialog.modal-custom-cfc,
    .modal.show#modalCFC .modal-dialog,
    .modal.fade#modalCFC .modal-dialog,
    .modal.show#modalCFC .modal-dialog.modal-custom-cfc,
    .modal.fade#modalCFC .modal-dialog.modal-custom-cfc {
        max-width: 98vw !important;
        width: 98vw !important;
        margin: 0.25rem auto !important;
    }
}

/* Garantir que o modal não seja cortado */
#modalCFC {
    overflow: visible !important;
    z-index: 1055 !important;
}

#modalCFC .modal-dialog {
    overflow: visible !important;
    z-index: 1056 !important;
}

#modalCFC .modal-content {
    overflow: visible !important;
    z-index: 1057 !important;
    /* CRÍTICO: Garantir que o conteúdo do modal seja renderizado corretamente */
    position: relative !important;
    display: flex !important;
    flex-direction: column !important;
    height: auto !important;
    min-height: 0 !important;
}

/* CRÍTICO: Garantir que o footer fique dentro do modal-content */
#modalCFC .modal-footer {
    position: relative !important;
    margin-top: auto !important;
    border-top: 1px solid #dee2e6 !important;
    background-color: #f8f9fa !important;
    padding: 1rem !important;
    /* Garantir que o footer não escape do modal */
    width: 100% !important;
    box-sizing: border-box !important;
}

/* Garantir que o modal-body tenha altura adequada */
#modalCFC .modal-body {
    flex: 1 1 auto !important;
    overflow-y: auto !important;
    max-height: 70vh !important;
}

/* CRÍTICO: Forçar estrutura flexbox para o modal-content */
#modalCFC .modal-content {
    display: flex !important;
    flex-direction: column !important;
    height: auto !important;
    min-height: 0 !important;
    /* Garantir que o conteúdo seja renderizado corretamente */
    position: relative !important;
    overflow: hidden !important;
}

/* CRÍTICO: Garantir que o header tenha altura fixa */
#modalCFC .modal-header {
    flex-shrink: 0 !important;
    position: relative !important;
    z-index: 3 !important;
}

/* CRÍTICO: Garantir que o body seja flexível */
#modalCFC .modal-body {
    flex: 1 1 auto !important;
    overflow-y: auto !important;
    max-height: 70vh !important;
    position: relative !important;
    z-index: 1 !important;
}

/* CRÍTICO: Garantir que o footer fique no final */
#modalCFC .modal-footer {
    flex-shrink: 0 !important;
    position: relative !important;
    z-index: 3 !important;
    margin-top: auto !important;
    border-top: 1px solid #dee2e6 !important;
    background-color: #f8f9fa !important;
    padding: 1rem !important;
    width: 100% !important;
    box-sizing: border-box !important;
    /* Garantir que o footer não escape do modal */
    bottom: 0 !important;
    left: 0 !important;
    right: 0 !important;
}

/* CRÍTICO: Sobrescrever qualquer CSS do Bootstrap que possa estar causando o problema */
#modalCFC .modal-content,
#modalCFC .modal-content * {
    box-sizing: border-box !important;
}

/* CRÍTICO: Forçar que o footer seja renderizado dentro do modal-content */
#modalCFC .modal-content .modal-footer {
    position: relative !important;
    float: none !important;
    clear: both !important;
    display: block !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 1rem !important;
    border-top: 1px solid #dee2e6 !important;
    background-color: #f8f9fa !important;
}

/* CRÍTICO: Garantir que o modal-dialog tenha altura adequada */
#modalCFC .modal-dialog {
    height: auto !important;
    min-height: 0 !important;
    display: flex !important;
    flex-direction: column !important;
}

/* CRÍTICO: Garantir que o modal-content ocupe toda a altura disponível */
#modalCFC .modal-dialog .modal-content {
    height: auto !important;
    min-height: 0 !important;
    display: flex !important;
    flex-direction: column !important;
}

/* Sobrescrever Bootstrap completamente */
.modal-dialog.modal-custom-cfc {
    max-width: 1200px !important;
    width: 1200px !important;
    margin: 2rem auto !important;
    position: relative !important;
    left: auto !important;
    right: auto !important;
    transform: none !important;
    top: auto !important;
    bottom: auto !important;
}

/* Forçar largura em todos os contextos */
.modal .modal-dialog.modal-custom-cfc,
.modal.show .modal-dialog.modal-custom-cfc,
.modal.fade .modal-dialog.modal-custom-cfc {
    max-width: 1200px !important;
    width: 1200px !important;
    margin: 2rem auto !important;
    position: relative !important;
    left: auto !important;
    right: auto !important;
    transform: none !important;
}

/* Responsividade para modal customizado - MÁXIMA ESPECIFICIDADE */
@media (max-width: 1400px) {
    .modal-dialog.modal-custom-cfc,
    .modal.show .modal-dialog.modal-custom-cfc,
    .modal.fade .modal-dialog.modal-custom-cfc,
    .modal.show .modal-dialog.modal-custom-cfc.show,
    .modal.fade .modal-dialog.modal-custom-cfc.fade {
        max-width: 95vw !important;
        width: 95vw !important;
        margin: 1.5rem auto !important;
    }
}

@media (max-width: 1200px) {
    .modal-dialog.modal-custom-cfc,
    .modal.show .modal-dialog.modal-custom-cfc,
    .modal.fade .modal-dialog.modal-custom-cfc,
    .modal.show .modal-dialog.modal-custom-cfc.show,
    .modal.fade .modal-dialog.modal-custom-cfc.fade {
        max-width: 90vw !important;
        width: 90vw !important;
        margin: 1rem auto !important;
    }
}

@media (max-width: 768px) {
    .modal-dialog.modal-custom-cfc,
    .modal.show .modal-dialog.modal-custom-cfc,
    .modal.fade .modal-dialog.modal-custom-cfc,
    .modal.show .modal-dialog.modal-custom-cfc.show,
    .modal.fade .modal-dialog.modal-custom-cfc.fade {
        max-width: 95vw !important;
        width: 95vw !important;
        margin: 0.5rem auto !important;
    }
}

@media (max-width: 576px) {
    .modal-dialog.modal-custom-cfc,
    .modal.show .modal-dialog.modal-custom-cfc,
    .modal.fade .modal-dialog.modal-custom-cfc,
    .modal.show .modal-dialog.modal-custom-cfc.show,
    .modal.fade .modal-dialog.modal-custom-cfc.fade {
        max-width: 98vw !important;
        width: 98vw !important;
        margin: 0.25rem auto !important;
    }
}

/* CRÍTICO: Garantir que o modal seja exibido corretamente em todos os navegadores */
.modal.show .modal-dialog {
    transform: none !important;
}

.modal.fade .modal-dialog {
    transition: none !important;
}

        /* CRÍTICO: Remover completamente o backdrop do modal */
        .modal-backdrop {
            display: none !important;
            opacity: 0 !important;
            visibility: hidden !important;
            pointer-events: none !important;
        }
        
        /* CRÍTICO: Garantir que o modal não tenha backdrop */
        #modalCFC {
            background: transparent !important;
        }
        
        #modalCFC::before,
        #modalCFC::after {
            display: none !important;
        }

/* CRÍTICO: Forçar estrutura correta do modal */
#modalCFC .modal-content > * {
    position: relative !important;
    z-index: 1 !important;
}

/* Garantir que os botões do footer sejam visíveis */
#modalCFC .modal-footer .btn {
    position: relative !important;
    z-index: 2 !important;
}

/* CRÍTICO: Sobrescrever QUALQUER regra do Bootstrap que possa estar interferindo */
.modal#modalCFC,
#modalCFC {
    width: auto !important;
    max-width: none !important;
    min-width: auto !important;
}

/* CRÍTICO: Garantir que o modal-dialog tenha a largura correta em TODOS os contextos */
.modal#modalCFC .modal-dialog,
#modalCFC .modal-dialog,
.modal#modalCFC .modal-dialog.modal-custom-cfc,
#modalCFC .modal-dialog.modal-custom-cfc,
.modal.show#modalCFC .modal-dialog,
.modal.fade#modalCFC .modal-dialog,
.modal.show#modalCFC .modal-dialog.modal-custom-cfc,
.modal.fade#modalCFC .modal-dialog.modal-custom-cfc,
.modal.show#modalCFC .modal-dialog.modal-custom-cfc.show,
.modal.fade#modalCFC .modal-dialog.modal-custom-cfc.fade,
.modal.show#modalCFC .modal-dialog.modal-custom-cfc.show.fade,
.modal.fade#modalCFC .modal-dialog.modal-custom-cfc.fade.show {
    width: 1200px !important;
    max-width: 1200px !important;
    min-width: 1200px !important;
    margin: 2rem auto !important;
    position: relative !important;
    left: auto !important;
    right: auto !important;
    transform: none !important;
    box-sizing: border-box !important;
    flex: none !important;
    flex-basis: 1200px !important;
    flex-grow: 0 !important;
    flex-shrink: 0 !important;
}

/* CRÍTICO: Sobrescrever qualquer regra de responsividade do Bootstrap */
@media (min-width: 576px) {
    .modal#modalCFC .modal-dialog,
    #modalCFC .modal-dialog,
    .modal#modalCFC .modal-dialog.modal-custom-cfc,
    #modalCFC .modal-dialog.modal-custom-cfc,
    .modal.show#modalCFC .modal-dialog,
    .modal.fade#modalCFC .modal-dialog,
    .modal.show#modalCFC .modal-dialog.modal-custom-cfc,
    .modal.fade#modalCFC .modal-dialog.modal-custom-cfc {
        width: 1200px !important;
        max-width: 1200px !important;
        min-width: 1200px !important;
    }
}

@media (min-width: 768px) {
    .modal#modalCFC .modal-dialog,
    #modalCFC .modal-dialog,
    .modal#modalCFC .modal-dialog.modal-custom-cfc,
    #modalCFC .modal-dialog.modal-custom-cfc,
    .modal.show#modalCFC .modal-dialog,
    .modal.fade#modalCFC .modal-dialog,
    .modal.show#modalCFC .modal-dialog.modal-custom-cfc,
    .modal.fade#modalCFC .modal-dialog.modal-custom-cfc {
        width: 1200px !important;
        max-width: 1200px !important;
        min-width: 1200px !important;
    }
}

@media (min-width: 992px) {
    .modal#modalCFC .modal-dialog,
    #modalCFC .modal-dialog,
    .modal#modalCFC .modal-dialog.modal-custom-cfc,
    #modalCFC .modal-dialog.modal-custom-cfc,
    .modal.show#modalCFC .modal-dialog,
    .modal.fade#modalCFC .modal-dialog,
    .modal.show#modalCFC .modal-dialog.modal-custom-cfc,
    .modal.fade#modalCFC .modal-dialog.modal-custom-cfc {
        width: 1200px !important;
        max-width: 1200px !important;
        min-width: 1200px !important;
    }
}

@media (min-width: 1200px) {
    .modal#modalCFC .modal-dialog,
    #modalCFC .modal-dialog,
    .modal#modalCFC .modal-dialog.modal-custom-cfc,
    #modalCFC .modal-dialog.modal-custom-cfc,
    .modal.show#modalCFC .modal-dialog,
    .modal.fade#modalCFC .modal-dialog,
    .modal.show#modalCFC .modal-dialog.modal-custom-cfc,
    .modal.fade#modalCFC .modal-dialog.modal-custom-cfc {
        width: 1200px !important;
        max-width: 1200px !important;
        min-width: 1200px !important;
    }
}

@media (min-width: 1400px) {
    .modal#modalCFC .modal-dialog,
    #modalCFC .modal-dialog,
    .modal#modalCFC .modal-dialog.modal-custom-cfc,
    #modalCFC .modal-dialog.modal-custom-cfc,
    .modal.show#modalCFC .modal-dialog,
    .modal.fade#modalCFC .modal-dialog,
    .modal.show#modalCFC .modal-dialog.modal-custom-cfc,
    .modal.fade#modalCFC .modal-dialog.modal-custom-cfc {
        width: 1200px !important;
        max-width: 1200px !important;
        min-width: 1200px !important;
    }
}

/* =====================================================
   AJUSTES PARA DESKTOP - CFCs
   ===================================================== */

/* Otimizações para desktop */
@media screen and (min-width: 769px) {
    .card .card-body .table-container {
        overflow-x: visible !important;
        border-radius: 8px !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
    }
    
    .card .card-body .table-container .table {
        width: 100% !important;
        font-size: 14px !important;
        table-layout: auto !important;
    }
    
    .card .card-body .table-container .table th,
    .card .card-body .table-container .table td {
        padding: 12px 8px !important;
        white-space: nowrap !important;
        vertical-align: middle !important;
    }
    
    .action-buttons {
        display: flex !important;
        flex-direction: row !important;
        gap: 5px !important;
        flex-wrap: wrap !important;
    }
    
    .action-buttons .btn {
        font-size: 12px !important;
        padding: 6px 10px !important;
        white-space: nowrap !important;
    }
}

/* =====================================================
   RESPONSIVIDADE PARA MOBILE - CFCs
   ===================================================== */

/* Media queries para responsividade */
@media screen and (max-width: 768px), screen and (max-width: 900px) {
    .card .card-body .table-container {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch !important;
        border-radius: 8px !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
        max-width: 100% !important;
        width: 100% !important;
    }
    
    .card .card-body .table-container .table {
        min-width: 800px !important;
        width: 800px !important;
        font-size: 14px !important;
        table-layout: fixed !important;
    }
    
    .card .card-body .table-container .table th,
    .card .card-body .table-container .table td {
        padding: 8px 6px !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }
    
    .action-buttons {
        flex-direction: column !important;
        gap: 5px !important;
    }
    
    .action-buttons .btn {
        font-size: 12px !important;
        padding: 4px 8px !important;
    }
}

@media screen and (max-width: 480px), screen and (max-width: 600px) {
    .card .card-body .table-container {
        display: none !important;
        overflow: visible !important;
    }
    
    .card .card-body .table-container .table {
        display: none !important;
    }
    
    .card .card-body .mobile-cfc-cards {
        display: block !important;
        width: 100% !important;
    }
    
    .mobile-cfc-card {
        background: #fff !important;
        border: 1px solid #dee2e6 !important;
        border-radius: 8px !important;
        margin-bottom: 15px !important;
        padding: 15px !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
    }
    
    .mobile-cfc-header {
        display: flex !important;
        justify-content: space-between !important;
        align-items: flex-start !important;
        margin-bottom: 12px !important;
        padding-bottom: 10px !important;
        border-bottom: 1px solid #e9ecef !important;
    }
    
    .mobile-cfc-title {
        flex: 1 !important;
    }
    
    .mobile-cfc-title strong {
        font-size: 16px !important;
        color: #333 !important;
        display: block !important;
        margin-bottom: 2px !important;
    }
    
    .mobile-cfc-id {
        font-size: 12px !important;
        color: #6c757d !important;
        font-weight: normal !important;
    }
    
    .mobile-cfc-status {
        margin-left: 10px !important;
    }
    
    .mobile-cfc-body {
        margin-bottom: 15px !important;
    }
    
    .mobile-cfc-field {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        margin-bottom: 8px !important;
        padding: 4px 0 !important;
    }
    
    .mobile-cfc-label {
        font-weight: 600 !important;
        color: #495057 !important;
        font-size: 13px !important;
        min-width: 80px !important;
    }
    
    .mobile-cfc-value {
        color: #6c757d !important;
        font-size: 13px !important;
        text-align: right !important;
        flex: 1 !important;
        word-break: break-word !important;
    }
    
    .mobile-cfc-actions {
        display: flex !important;
        gap: 8px !important;
        justify-content: center !important;
        padding-top: 10px !important;
        border-top: 1px solid #e9ecef !important;
    }
    
    .mobile-cfc-actions .btn {
        flex: 1 !important;
        max-width: 50px !important;
        padding: 8px !important;
        font-size: 12px !important;
    }
}
</style>

<script>
// =====================================================
// RESPONSIVIDADE PARA MOBILE - CFCs
// =====================================================

function toggleMobileLayoutCFCs() {
    const viewportWidth = window.innerWidth;
    const isMobile = viewportWidth <= 600;
    const tableContainer = document.querySelector('.table-container');
    const mobileCards = document.querySelector('.mobile-cfc-cards');
    
    if (isMobile && mobileCards) {
        // Mobile pequeno - mostrar cards
        if (tableContainer) {
            tableContainer.style.display = 'none';
        }
        mobileCards.style.display = 'block';
    } else {
        // Desktop/tablet - mostrar tabela
        if (tableContainer) {
            tableContainer.style.display = 'block';
        }
        if (mobileCards) {
            mobileCards.style.display = 'none';
        }
    }
}

// Executar na inicialização e no resize
window.addEventListener('resize', toggleMobileLayoutCFCs);
toggleMobileLayoutCFCs(); // Chamada inicial

</script>

