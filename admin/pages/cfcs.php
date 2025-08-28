<?php
// Verificar se as variÃ¡veis estÃ£o definidas
if (!isset($cfcs)) $cfcs = [];
if (!isset($usuarios)) $usuarios = [];
if (!isset($mensagem)) $mensagem = '';
if (!isset($tipo_mensagem)) $tipo_mensagem = 'info';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-building me-2"></i>GestÃ£o de CFCs
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
        <button type="button" class="btn btn-info ms-2" onclick="testarCaminhosManual()" title="Testar caminhos da API">
            <i class="fas fa-cog me-1"></i>Testar API
        </button>
        <button type="button" class="btn btn-warning ms-2" onclick="limparCacheAPI()" title="Limpar cache da API">
            <i class="fas fa-broom me-1"></i>Limpar Cache
        </button>
        <button type="button" class="btn btn-success ms-2" onclick="testarAPICFC()" title="Testar API de CFCs">
            <i class="fas fa-vial me-1"></i>Testar API CFC
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
            <!-- SerÃ¡ preenchido via JavaScript -->
        </select>
    </div>
</div>

<!-- Tabela de CFCs -->
<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Lista de CFCs</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="tabelaCFCs">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>CNPJ</th>
                        <th>Cidade/UF</th>
                        <th>Telefone</th>
                        <th>ResponsÃ¡vel</th>
                        <th>Status</th>
                        <th>Alunos</th>
                        <th>AÃ§Ãµes</th>
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
                                    <span class="text-muted">NÃ£o informado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($cfc['telefone']): ?>
                                    <a href="tel:<?php echo htmlspecialchars($cfc['telefone']); ?>" class="text-decoration-none">
                                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($cfc['telefone']); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">NÃ£o informado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($cfc['responsavel_nome']): ?>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($cfc['responsavel_nome']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">NÃ£o definido</span>
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
                                    <!-- BotÃµes principais em linha -->
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
                                                title="Gerenciar instrutores, alunos e veÃ­culos">
                                            <i class="fas fa-cogs me-1"></i>Gerenciar
                                        </button>
                                    </div>
                                    
                                    <!-- BotÃµes secundÃ¡rios em linha -->
                                    <div class="action-buttons-secondary">
                                        <?php if ($cfc['ativo']): ?>
                                        <button type="button" class="btn btn-toggle action-btn" 
                                                onclick="desativarCFC(<?php echo $cfc['id']; ?>)" 
                                                title="Desativar CFC (nÃ£o poderÃ¡ operar)">
                                            <i class="fas fa-ban me-1"></i>Desativar
                                        </button>
                                        <?php else: ?>
                                        <button type="button" class="btn btn-schedule action-btn" 
                                                onclick="ativarCFC(<?php echo $cfc['id']; ?>)" 
                                                title="Reativar CFC para operaÃ§Ã£o">
                                            <i class="fas fa-check me-1"></i>Ativar
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- BotÃ£o de exclusÃ£o destacado -->
                                    <div class="action-buttons-danger">
                                        <button type="button" class="btn btn-delete action-btn" 
                                                onclick="excluirCFC(<?php echo $cfc['id']; ?>)" 
                                                title="âš ï¸ EXCLUIR CFC - Esta aÃ§Ã£o nÃ£o pode ser desfeita!">
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
    </div>
</div>

<!-- Modal Bootstrap para Cadastro/Edição de CFC -->
<div class="modal fade" id="modalCFC" tabindex="-1" aria-labelledby="modalCFCLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="formCFC" onsubmit="return false;">
                <div class="modal-header" style="background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%); color: white; border-bottom: none;">
                    <h5 class="modal-title" id="modalTitle" style="color: white; font-weight: 600; font-size: 1.25rem; margin: 0;">
                        <i class="fas fa-building me-2"></i>Novo CFC
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: invert(1);"></button>
                </div>
                <div class="modal-body" style="overflow-y: auto; padding: 1rem;">
                    <input type="hidden" name="acao" id="acaoCFC" value="criar">
                    <input type="hidden" name="cfc_id" id="cfc_id" value="">
                    
                    <div class="container-fluid" style="padding: 0;">
                        <!-- Seção 1: Informações Básicas -->
                        <div class="row mb-2">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem; margin-bottom: 0.5rem !important;">
                                    <i class="fas fa-building me-1"></i>Informações Básicas
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-1">
                                    <label for="nome" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Nome do CFC *</label>
                                    <input type="text" class="form-control" id="nome" name="nome" required 
                                           placeholder="Nome completo do Centro de Formação de Condutores" style="padding: 0.4rem; font-size: 0.85rem;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-1">
                                    <label for="cnpj" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">CNPJ *</label>
                                    <input type="text" class="form-control" id="cnpj" name="cnpj" required 
                                           placeholder="00.000.000/0000-00" style="padding: 0.4rem; font-size: 0.85rem;">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-2">
                            <div class="col-md-12">
                                <div class="mb-1">
                                    <label for="razao_social" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Razão Social</label>
                                    <input type="text" class="form-control" id="razao_social" name="razao_social" 
                                           placeholder="Razão social da empresa (opcional)" style="padding: 0.4rem; font-size: 0.85rem;">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Seção 2: Contato -->
                        <div class="row mb-2">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem; margin-bottom: 0.5rem !important;">
                                    <i class="fas fa-phone me-1"></i>Contato
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-1">
                                    <label for="email" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">E-mail</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           placeholder="contato@cfc.com.br" style="padding: 0.4rem; font-size: 0.85rem;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-1">
                                    <label for="telefone" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Telefone</label>
                                    <input type="text" class="form-control" id="telefone" name="telefone" 
                                           placeholder="(00) 00000-0000" style="padding: 0.4rem; font-size: 0.85rem;">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Seção 3: Endereço -->
                        <div class="row mb-2">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem; margin-bottom: 0.5rem !important;">
                                    <i class="fas fa-map-marker-alt me-1"></i>Endereço
                                </h6>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-1">
                                    <label for="cep" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">CEP</label>
                                    <input type="text" class="form-control" id="cep" name="cep" 
                                           placeholder="00000-000" style="padding: 0.4rem; font-size: 0.85rem;">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-1">
                                    <label for="endereco" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Endereço</label>
                                    <input type="text" class="form-control" id="endereco" name="endereco" 
                                           placeholder="Rua, Avenida, número, etc." style="padding: 0.4rem; font-size: 0.85rem;">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-1">
                                    <label for="bairro" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Bairro</label>
                                    <input type="text" class="form-control" id="bairro" name="bairro" 
                                           placeholder="Centro, Jardim, etc." style="padding: 0.4rem; font-size: 0.85rem;">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-1">
                                    <label for="cidade" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Cidade</label>
                                    <input type="text" class="form-control" id="cidade" name="cidade" 
                                           placeholder="Nome da cidade" style="padding: 0.4rem; font-size: 0.85rem;">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-1">
                                    <label for="uf" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">UF</label>
                                    <select class="form-select" id="uf" name="uf" style="padding: 0.4rem; font-size: 0.85rem;">
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
                                <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem; margin-bottom: 0.5rem !important;">
                                    <i class="fas fa-cog me-1"></i>Configurações
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-1">
                                    <label for="responsavel_id" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Responsável</label>
                                    <select class="form-select" id="responsavel_id" name="responsavel_id" style="padding: 0.4rem; font-size: 0.85rem;">
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
                                    <label for="ativo" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Status</label>
                                    <select class="form-select" id="ativo" name="ativo" style="padding: 0.4rem; font-size: 0.85rem;">
                                        <option value="1">Ativo</option>
                                        <option value="0">Inativo</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Seção 5: Observações -->
                        <div class="row">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem; margin-bottom: 0.5rem !important;">
                                    <i class="fas fa-sticky-note me-1"></i>Observações
                                </h6>
                                <div class="mb-1">
                                    <label for="observacoes" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Observações</label>
                                    <textarea class="form-control" id="observacoes" name="observacoes" rows="2" 
                                              placeholder="Informações adicionais sobre o CFC..." style="padding: 0.4rem; font-size: 0.85rem; resize: vertical;"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background-color: #f8f9fa; border-top: 1px solid #dee2e6; padding: 0.75rem 1.5rem;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSalvarCFC" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
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

