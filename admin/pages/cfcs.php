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
        <button type="button" class="btn btn-primary" onclick="abrirModalCFC()">
            <i class="fas fa-plus me-1"></i>Novo CFC
        </button>
        <button type="button" class="btn btn-info ms-2" onclick="testarCaminhosManual()" title="Testar caminhos da API">
            <i class="fas fa-cog me-1"></i>Testar API
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
                            <button class="btn btn-primary" onclick="abrirModalCFC()">
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
    </div>
</div>

<!-- Modal Customizado para Cadastro/Edição de CFC -->
<div id="modalCFC" class="custom-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 9999;">
    <div class="custom-modal-dialog" style="position: fixed; top: 2rem; left: 2rem; right: 2rem; bottom: 2rem; width: auto; height: auto; margin: 0; padding: 0; display: flex; align-items: center; justify-content: center;">
        <div class="custom-modal-content" style="width: 100%; height: 100%; max-width: 95vw; max-height: 95vh; background: white; border: none; border-radius: 0.5rem; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15); overflow: hidden; display: flex; flex-direction: column;">
            <form id="formCFC" onsubmit="return false;">
                <div class="modal-header" style="background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%); color: white; border-bottom: none; padding: 0.75rem 1.5rem; flex-shrink: 0;">
                    <h5 class="modal-title" id="modalTitle" style="color: white; font-weight: 600; font-size: 1.25rem; margin: 0;">
                        <i class="fas fa-building me-2"></i>Novo CFC
                    </h5>
                    <button type="button" class="btn-close" onclick="fecharModalCFC()" style="filter: invert(1); background: none; border: none; font-size: 1.25rem; color: white; opacity: 0.8; cursor: pointer;">&times;</button>
                </div>
                <div class="modal-body" style="overflow-y: auto; padding: 1rem; flex: 1; min-height: 0;">
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
                <div class="modal-footer" style="background-color: #f8f9fa; border-top: 1px solid #dee2e6; padding: 0.75rem 1.5rem; display: flex; justify-content: flex-end; gap: 1rem; flex-shrink: 0;">
                    <button type="button" class="btn btn-secondary" onclick="fecharModalCFC()" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSalvarCFC" onclick="salvarCFCDireto()" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
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
                <button type="button" class="btn btn-primary" id="btnEditarVisualizacao">
                    <i class="fas fa-edit me-1"></i>Editar CFC
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts específicos para CFCs -->
<script>
// DEFINIÇÕES GLOBAIS IMEDIATAS - GARANTIR QUE FUNCIONEM
console.log('🔧 Inicializando funções globais de CFCs...');

// Função para detectar o caminho correto da API
async function detectarCaminhoAPI() {
    // Se temos o caminho em cache, usar ele
    if (caminhoAPICache) {
        return caminhoAPICache;
    }
    
    // Se não temos cache, testar todos os caminhos
    caminhoAPICache = await testarCaminhosAPI();
    return caminhoAPICache;
}

// Função para testar múltiplos caminhos da API
async function testarCaminhosAPI() {
    const baseUrl = window.location.origin;
    const pathname = window.location.pathname;
    
    // Lista de possíveis caminhos para testar
    const caminhos = [
        baseUrl + '/admin/api/cfcs.php',
        baseUrl + '/api/cfcs.php',
        baseUrl + pathname.replace('/admin/index.php', '') + '/admin/api/cfcs.php',
        baseUrl + pathname.replace('/admin/index.php', '') + '/api/cfcs.php',
        'admin/api/cfcs.php',
        'api/cfcs.php',
        '../admin/api/cfcs.php',
        '../api/cfcs.php'
    ];
    
    console.log('🧪 Testando caminhos da API...');
    
    for (const caminho of caminhos) {
        try {
            console.log(`🔍 Testando: ${caminho}`);
            const response = await fetch(caminho, {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            if (response.ok) {
                console.log(`✅ Caminho funcionando: ${caminho}`);
                return caminho;
            } else {
                console.log(`❌ Caminho falhou (${response.status}): ${caminho}`);
            }
        } catch (error) {
            console.log(`❌ Caminho com erro: ${caminho} - ${error.message}`);
        }
    }
    
    // Se nenhum caminho funcionar, usar o padrão
    console.log('⚠️ Nenhum caminho funcionou, usando padrão');
    return baseUrl + '/admin/api/cfcs.php';
}

// Cache para o caminho da API
let caminhoAPICache = null;

// Função para fazer requisições com caminho automático
async function fetchAPI(endpoint, options = {}) {
    // Se não temos o caminho em cache, testar todos os caminhos
    if (!caminhoAPICache) {
        console.log('🔍 Primeira requisição - testando caminhos da API...');
        caminhoAPICache = await testarCaminhosAPI();
    }
    
    const url = caminhoAPICache + endpoint;
    console.log('🌐 Fazendo requisição para:', url);
    
    try {
        const response = await fetch(url, {
            ...options,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                ...options.headers
            }
        });
        
        if (!response.ok) {
            // Se der erro 404, pode ser que o caminho mudou, testar novamente
            if (response.status === 404) {
                console.log('🔄 Erro 404 - testando caminhos novamente...');
                caminhoAPICache = await testarCaminhosAPI();
                const novaUrl = caminhoAPICache + endpoint;
                console.log('🔄 Tentando nova URL:', novaUrl);
                
                const novaResponse = await fetch(novaUrl, {
                    ...options,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        ...options.headers
                    }
                });
                
                if (!novaResponse.ok) {
                    throw new Error(`HTTP ${novaResponse.status}: ${novaResponse.statusText}`);
                }
                
                return novaResponse;
            }
            
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return response;
    } catch (error) {
        console.error('❌ Erro na requisição:', error);
        throw error;
    }
}

// Função excluirCFC - definição global imediata
window.excluirCFC = function(id) {
    console.log('🚀 excluirCFC chamada globalmente com ID:', id);
    
    if (typeof verificarRegistrosVinculados === 'function') {
        verificarRegistrosVinculados(id).then(hasVinculados => {
            if (hasVinculados) {
                const mensagemCascata = '⚠️ ATENÇÃO: Este CFC possui registros vinculados!\n\n' +
                    'Opções:\n' +
                    '1. Exclusão em cascata: Remove o CFC e TODOS os registros vinculados\n' +
                    '2. Cancelar: Mantém o CFC e os registros\n\n' +
                    'Deseja continuar com exclusão em cascata?';
                
                if (confirm(mensagemCascata)) {
                    if (typeof excluirCFCCascata === 'function') {
                        excluirCFCCascata(id);
                    } else {
                        alert('Função de exclusão em cascata não disponível');
                    }
                }
            } else {
                const mensagem = '⚠️ ATENÇÃO: Esta ação não pode ser desfeita!\n\nDeseja realmente excluir este CFC?';
                if (confirm(mensagem)) {
                    if (typeof excluirCFCNormal === 'function') {
                        excluirCFCNormal(id);
                    } else {
                        alert('Função de exclusão normal não disponível');
                    }
                }
            }
        });
    } else {
        console.error('❌ verificarRegistrosVinculados não disponível');
        alert('Erro: Função de verificação não disponível');
    }
};

// Função editarCFC - definição global imediata
window.editarCFC = function(id) {
    console.log('🚀 editarCFC chamada globalmente com ID:', id);
    if (typeof editarCFCInterno === 'function') {
        editarCFCInterno(id);
    } else {
        alert('Função de edição não disponível');
    }
};

// Função visualizarCFC - definição global imediata
window.visualizarCFC = function(id) {
    console.log('🚀 visualizarCFC chamada globalmente com ID:', id);
    if (typeof visualizarCFCInterno === 'function') {
        visualizarCFCInterno(id);
    } else {
        alert('Função de visualização não disponível');
    }
};

// Função gerenciarCFC - definição global imediata
window.gerenciarCFC = function(id) {
    console.log('🚀 gerenciarCFC chamada globalmente com ID:', id);
    window.location.href = `pages/gerenciar-cfc.php?id=${id}`;
};

// Função ativarCFC - definição global imediata
window.ativarCFC = function(id) {
    console.log('🚀 ativarCFC chamada globalmente com ID:', id);
    if (confirm('Deseja realmente ativar este CFC?')) {
        if (typeof alterarStatusCFC === 'function') {
            alterarStatusCFC(id, 1);
        } else {
            alert('Função de ativação não disponível');
        }
    }
};

// Função desativarCFC - definição global imediata
window.desativarCFC = function(id) {
    console.log('🚀 desativarCFC chamada globalmente com ID:', id);
    if (confirm('Deseja realmente desativar este CFC? Esta ação pode afetar alunos e instrutores vinculados.')) {
        if (typeof alterarStatusCFC === 'function') {
            alterarStatusCFC(id, 0);
        } else {
            alert('Função de desativação não disponível');
        }
    }
};

// Função abrirModalCFC - definição global imediata
window.abrirModalCFC = function() {
    console.log('🚀 abrirModalCFC chamada globalmente');
    const modal = document.getElementById('modalCFC');
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        
        // Aplicar responsividade
        setTimeout(() => {
            if (typeof ajustarModalResponsivo === 'function') {
                ajustarModalResponsivo();
            }
        }, 10);
        
        console.log('✅ Modal customizado aberto!');
    } else {
        console.error('❌ Modal não encontrado!');
        alert('Erro: Modal não encontrado na página!');
    }
};

// Função fecharModalCFC - definição global imediata
window.fecharModalCFC = function() {
    console.log('🚪 fecharModalCFC chamada globalmente');
    const modal = document.getElementById('modalCFC');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        console.log('✅ Modal customizado fechado!');
    }
};

// Função ajustarModalResponsivo - definição global imediata
window.ajustarModalResponsivo = function() {
    const modalDialog = document.querySelector('#modalCFC .custom-modal-dialog');
    if (modalDialog) {
        if (window.innerWidth <= 768) {
            // Mobile - ocupar quase toda a tela
            modalDialog.style.cssText = `
                position: fixed !important;
                top: 0.5rem !important;
                left: 0.5rem !important;
                right: 0.5rem !important;
                bottom: 0.5rem !important;
                width: auto !important;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            `;
        } else if (window.innerWidth <= 1200) {
            // Tablet - margens menores
            modalDialog.style.cssText = `
                position: fixed !important;
                top: 1rem !important;
                left: 1rem !important;
                right: 1rem !important;
                bottom: 1rem !important;
                width: auto !important;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            `;
        } else {
            // Desktop - margens padrão
            modalDialog.style.cssText = `
                position: fixed !important;
                top: 2rem !important;
                left: 2rem !important;
                right: 2rem !important;
                bottom: 2rem !important;
                width: auto !important;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            `;
        }
    }
};

console.log('✅ Funções globais de CFCs inicializadas!');

// Função para teste manual dos caminhos da API
async function testarCaminhosManual() {
    console.log('🧪 Teste manual dos caminhos da API...');
    
    // Limpar cache para forçar novo teste
    caminhoAPICache = null;
    
    try {
        const caminho = await testarCaminhosAPI();
        alert(`✅ Caminho da API detectado: ${caminho}`);
        
        // Testar uma requisição real
        const response = await fetch(caminho, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        if (response.ok) {
            alert(`✅ API funcionando! Status: ${response.status}`);
        } else {
            alert(`⚠️ API respondeu mas com erro: ${response.status}`);
        }
    } catch (error) {
        alert(`❌ Erro ao testar API: ${error.message}`);
    }
}

document.addEventListener('DOMContentLoaded', async function() {
    // Testar caminhos da API primeiro
    console.log('🚀 Inicializando sistema de CFCs...');
    try {
        caminhoAPICache = await testarCaminhosAPI();
        console.log('✅ Caminho da API detectado:', caminhoAPICache);
    } catch (error) {
        console.error('❌ Erro ao detectar caminho da API:', error);
    }
    
    // Inicializar máscaras
    inicializarMascarasCFC();
    
    // Inicializar filtros
    inicializarFiltrosCFC();
    
    // Inicializar busca
    inicializarBuscaCFC();
    
    // Handler para o formulário de CFC
    const formCFC = document.getElementById('formCFC');
    if (formCFC) {
        formCFC.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            try {
                salvarCFC();
            } catch (error) {
                console.error('Erro no submit do formulário:', error);
                mostrarAlerta('Erro interno. Tente novamente.', 'danger');
            }
            return false;
        });
    }
    
    // Handler adicional para o botão de salvar
    const btnSalvarCFC = document.getElementById('btnSalvarCFC');
    if (btnSalvarCFC) {
        console.log('🔍 Botão de salvar encontrado, adicionando event listener...');
        
        // Remover event listeners existentes
        btnSalvarCFC.replaceWith(btnSalvarCFC.cloneNode(true));
        const newBtnSalvarCFC = document.getElementById('btnSalvarCFC');
        
        // Adicionar novo event listener
        newBtnSalvarCFC.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('🔘 Botão clicado!');
            
            try {
                if (typeof salvarCFC === 'function') {
                    console.log('✅ Função salvarCFC disponível, chamando...');
                    salvarCFC();
                } else {
                    console.error('❌ Função salvarCFC não está disponível');
                    alert('Erro: Função de salvar não está disponível');
                }
            } catch (error) {
                console.error('❌ Erro no clique do botão salvar:', error);
                alert('Erro interno: ' + error.message);
            }
            return false;
        });
        
        console.log('✅ Event listener adicionado ao botão de salvar');
    } else {
        console.error('❌ Botão de salvar não encontrado');
    }
});

function inicializarMascarasCFC() {
    // Máscara para CNPJ
    if (typeof IMask !== 'undefined') {
        new IMask(document.getElementById('cnpj'), {
            mask: '00.000.000/0000-00'
        });
        
        // Máscara para telefone
        new IMask(document.getElementById('telefone'), {
            mask: '(00) 00000-0000'
        });
        
        // Máscara para CEP
        new IMask(document.getElementById('cep'), {
            mask: '00000-000'
        });
    }
    
    // Busca de CEP
    document.getElementById('cep').addEventListener('blur', function() {
        const cep = this.value.replace(/\D/g, '');
        if (cep.length === 8) {
            buscarCEP(cep);
        }
    });
}

function buscarCEP(cep) {
    fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then(response => response.json())
        .then(data => {
            if (!data.erro) {
                document.getElementById('endereco').value = data.logradouro;
                document.getElementById('bairro').value = data.bairro;
                document.getElementById('cidade').value = data.localidade;
                document.getElementById('uf').value = data.uf;
            }
        })
        .catch(error => console.error('Erro ao buscar CEP:', error));
}

function inicializarFiltrosCFC() {
    // Filtro por status
    document.getElementById('filtroStatus').addEventListener('change', filtrarCFCs);
    
    // Filtro por cidade
    document.getElementById('filtroCidade').addEventListener('change', filtrarCFCs);
    
    // Preencher cidades únicas
    preencherCidadesUnicas();
}

function preencherCidadesUnicas() {
    const cidades = new Set();
    const linhas = document.querySelectorAll('#tabelaCFCs tbody tr');
    
    linhas.forEach(linha => {
        const cidade = linha.querySelector('td:nth-child(4)').textContent.split('/')[0].trim();
        if (cidade && cidade !== 'Não informado') {
            cidades.add(cidade);
        }
    });
    
    const selectCidade = document.getElementById('filtroCidade');
    cidades.forEach(cidade => {
        const option = document.createElement('option');
        option.value = cidade;
        option.textContent = cidade;
        selectCidade.appendChild(option);
    });
}

function filtrarCFCs() {
    const status = document.getElementById('filtroStatus').value;
    const cidade = document.getElementById('filtroCidade').value;
    const busca = document.getElementById('buscaCFC').value.toLowerCase();
    
    const linhas = document.querySelectorAll('#tabelaCFCs tbody tr');
    
    linhas.forEach(linha => {
        let mostrar = true;
        
        // Filtro por status
        if (status && linha.querySelector('td:nth-child(7) .badge').textContent !== status) {
            mostrar = false;
        }
        
        // Filtro por cidade
        if (cidade) {
            const cidadeLinha = linha.querySelector('td:nth-child(4)').textContent.split('/')[0].trim();
            if (cidadeLinha !== cidade) {
                mostrar = false;
            }
        }
        
        // Filtro por busca
        if (busca) {
            const texto = linha.textContent.toLowerCase();
            if (!texto.includes(busca)) {
                mostrar = false;
            }
        }
        
        linha.style.display = mostrar ? '' : 'none';
    });
}

function inicializarBuscaCFC() {
    document.getElementById('buscaCFC').addEventListener('input', filtrarCFCs);
}

function editarCFCInterno(id) {
    console.log('🚀 editarCFCInterno chamada com ID:', id);
    
    // Verificar se os elementos necessários existem
    const modalElement = document.getElementById('modalCFC');
    const modalTitle = document.getElementById('modalTitle');
    const acaoCFC = document.getElementById('acaoCFC');
    const cfcId = document.getElementById('cfc_id');
    
    console.log('🔍 Verificando elementos do DOM:');
    console.log('  modalCFC:', modalElement ? '✅ Existe' : '❌ Não existe');
    console.log('  modalTitle:', modalTitle ? '✅ Existe' : '❌ Não existe');
    console.log('  acaoCFC:', acaoCFC ? '✅ Existe' : '❌ Não existe');
    console.log('  cfc_id:', cfcId ? '✅ Existe' : '❌ Não existe');
    
    if (!modalElement) {
        console.error('❌ Modal não encontrado!');
        alert('ERRO: Modal não encontrado na página!');
        return;
    }
    
            console.log(`📡 Fazendo requisição para /admin/api/cfcs.php?id=${id}`);
        
        // Buscar dados do CFC
        fetchAPI(`?id=${id}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
        .then(response => {
            console.log(`📨 Resposta recebida - Status: ${response.status}, OK: ${response.ok}`);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('📄 Dados recebidos:', data);
            
            if (data.success) {
                console.log('✅ Success = true, abrindo modal...');
                
                // Preencher formulário
                preencherFormularioCFC(data.data);
                console.log('✅ Formulário preenchido');
                
                // Configurar modal
                if (modalTitle) modalTitle.textContent = 'Editar CFC';
                if (acaoCFC) acaoCFC.value = 'editar';
                if (cfcId) cfcId.value = id;
                
                // Abrir modal customizado
                abrirModalCFC();
                console.log('🪟 Modal customizado aberto!');
                
            } else {
                console.error('❌ Success = false, erro:', data.error);
                mostrarAlerta('Erro ao carregar dados do CFC: ' + (data.error || 'Erro desconhecido'), 'danger');
            }
        })
        .catch(error => {
            console.error('💥 Erro na requisição:', error);
            if (error.message.includes('401')) {
                mostrarAlerta('Sessão expirada. Faça login novamente.', 'warning');
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 2000);
            } else {
                mostrarAlerta('Erro ao carregar dados do CFC: ' + error.message, 'danger');
            }
        });
}

// Garantir que a função esteja disponível globalmente
window.editarCFC = editarCFC;

function preencherFormularioCFC(cfc) {
    document.getElementById('nome').value = cfc.nome || '';
    document.getElementById('cnpj').value = cfc.cnpj || '';
    document.getElementById('razao_social').value = cfc.razao_social || '';
    document.getElementById('email').value = cfc.email || '';
    document.getElementById('telefone').value = cfc.telefone || '';
    
    // Endereço
    document.getElementById('cep').value = cfc.cep || '';
    document.getElementById('endereco').value = cfc.endereco || '';
    document.getElementById('bairro').value = cfc.bairro || '';
    document.getElementById('cidade').value = cfc.cidade || '';
    document.getElementById('uf').value = cfc.uf || '';
    
    document.getElementById('responsavel_id').value = cfc.responsavel_id || '';
    document.getElementById('ativo').value = cfc.ativo ? '1' : '0';
    document.getElementById('observacoes').value = cfc.observacoes || '';
}

function visualizarCFCInterno(id) {
    fetchAPI(`?id=${id}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                preencherModalVisualizacao(data.data);
                const modal = new bootstrap.Modal(document.getElementById('modalVisualizarCFC'));
                modal.show();
            } else {
                mostrarAlerta(data.error || 'Erro ao carregar dados do CFC', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            if (error.message.includes('401')) {
                mostrarAlerta('Sessão expirada. Faça login novamente.', 'warning');
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 2000);
            } else {
                mostrarAlerta('Erro ao carregar dados do CFC: ' + error.message, 'danger');
            }
        });
}

// Garantir que a função esteja disponível globalmente
window.visualizarCFC = visualizarCFC;

function preencherModalVisualizacao(cfc) {
    const html = `
        <div class="row">
            <div class="col-md-8">
                <h4>${cfc.nome}</h4>
                <p class="text-muted">CNPJ: ${cfc.cnpj}</p>
                ${cfc.razao_social && cfc.razao_social !== cfc.nome ? `<p class="text-muted">Razão Social: ${cfc.razao_social}</p>` : ''}
            </div>
            <div class="col-md-4 text-end">
                <span class="badge bg-${cfc.ativo ? 'success' : 'danger'} fs-6">
                    ${cfc.ativo ? 'Ativo' : 'Inativo'}
                </span>
            </div>
        </div>
        
        <hr>
        
        <div class="row">
            <div class="col-md-6">
                <h6><i class="fas fa-envelope me-2"></i>Contato</h6>
                <p><strong>E-mail:</strong> ${cfc.email || 'Não informado'}</p>
                <p><strong>Telefone:</strong> ${cfc.telefone || 'Não informado'}</p>
            </div>
            <div class="col-md-6">
                <h6><i class="fas fa-map-marker-alt me-2"></i>Endereço</h6>
                <p>${cfc.endereco || ''}</p>
                <p>${cfc.bairro || ''}</p>
                <p>${cfc.cidade || ''} - ${cfc.uf || ''}</p>
                <p>CEP: ${cfc.cep || 'Não informado'}</p>
            </div>
        </div>
        
        ${cfc.observacoes ? `
        <hr>
        <h6><i class="fas fa-sticky-note me-2"></i>Observações</h6>
        <p>${cfc.observacoes}</p>
        ` : ''}
    `;
    
    document.getElementById('modalVisualizarCFCBody').innerHTML = html;
    document.getElementById('btnEditarVisualizacao').onclick = () => {
        bootstrap.Modal.getInstance(document.getElementById('modalVisualizarCFC')).hide();
        editarCFC(cfc.id);
    };
}

function gerenciarCFC(id) {
    // Redirecionar para página de gerenciamento específica do CFC
    window.location.href = `pages/gerenciar-cfc.php?id=${id}`;
}

// Garantir que a função esteja disponível globalmente
window.gerenciarCFC = gerenciarCFC;

function ativarCFC(id) {
    if (confirm('Deseja realmente ativar este CFC?')) {
        alterarStatusCFC(id, 1);
    }
}

// Garantir que a função esteja disponível globalmente
window.ativarCFC = ativarCFC;

function desativarCFC(id) {
    if (confirm('Deseja realmente desativar este CFC? Esta ação pode afetar alunos e instrutores vinculados.')) {
        alterarStatusCFC(id, 0);
    }
}

// Garantir que a função esteja disponível globalmente
window.desativarCFC = desativarCFC;

function alterarStatusCFC(id, status) {
    if (confirm('Deseja realmente alterar o status deste CFC?')) {
        // Fazer requisição para a API
        fetchAPI(``, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                id: id,
                ativo: status === 1
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta('Status do CFC alterado com sucesso!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                mostrarAlerta(data.error || 'Erro ao alterar status do CFC', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarAlerta('Erro ao alterar status do CFC', 'danger');
        });
    }
}

function salvarCFC() {
    console.log('🚀 Função salvarCFC chamada!');
    
    try {
        const form = document.getElementById('formCFC');
        if (!form) {
            console.error('❌ Formulário não encontrado');
            alert('Erro: Formulário não encontrado');
            return;
        }
        
        console.log('✅ Formulário encontrado');
        const formData = new FormData(form);
        
        // Validações básicas
        if (!formData.get('nome').trim()) {
            console.log('❌ Nome do CFC é obrigatório');
            alert('Nome do CFC é obrigatório');
            return;
        }
        
        if (!formData.get('cnpj').trim()) {
            console.log('❌ CNPJ é obrigatório');
            alert('CNPJ é obrigatório');
            return;
        }
        
        if (!formData.get('cidade').trim()) {
            console.log('❌ Cidade é obrigatória');
            alert('Cidade é obrigatória');
            return;
        }
        
        if (!formData.get('uf')) {
            console.log('❌ UF é obrigatória');
            alert('UF é obrigatória');
            return;
        }
        
        console.log('✅ Validações passaram');
        
        // Preparar dados para envio
        const cfcData = {
            nome: formData.get('nome').trim(),
            cnpj: formData.get('cnpj').trim(),
            razao_social: formData.get('razao_social').trim() || formData.get('nome').trim(),
            email: formData.get('email').trim(),
            telefone: formData.get('telefone').trim(),
            cep: formData.get('cep').trim(),
            endereco: formData.get('endereco').trim(),
            bairro: formData.get('bairro').trim(),
            cidade: formData.get('cidade').trim(),
            uf: formData.get('uf'),
            responsavel_id: formData.get('responsavel_id') || null,
            ativo: formData.get('ativo') === '1',
            observacoes: formData.get('observacoes').trim()
        };
        
        console.log('📋 Dados preparados:', cfcData);
        
        const acao = formData.get('acao');
        const cfc_id = formData.get('cfc_id');
        
        if (acao === 'editar' && cfc_id) {
            cfcData.id = cfc_id;
        }
        
        // Mostrar loading
        const btnSalvar = document.getElementById('btnSalvarCFC');
        if (btnSalvar) {
            const originalText = btnSalvar.innerHTML;
            btnSalvar.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Salvando...';
            btnSalvar.disabled = true;
            
            console.log('🔄 Fazendo requisição para a API...');
            
                    // Fazer requisição para a API
        const url = await detectarCaminhoAPI();
        const method = acao === 'editar' ? 'PUT' : 'POST';
            
            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify(cfcData)
            })
            .then(response => {
                console.log('📡 Resposta recebida:', response);
                return response.json();
            })
            .then(data => {
                console.log('📋 Dados da resposta:', data);
                if (data.success) {
                    console.log('✅ CFC salvo com sucesso!');
                    alert(data.message || 'CFC salvo com sucesso!');
                    
                    // Fechar modal customizado
                    if (typeof fecharModalCFC === 'function') {
                        fecharModalCFC();
                    }
                    
                    // Limpar formulário
                    form.reset();
                    
                    // Recarregar página para mostrar dados atualizados
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    console.log('❌ Erro ao salvar:', data.error);
                    alert(data.error || 'Erro ao salvar CFC');
                }
            })
            .catch(error => {
                console.error('❌ Erro na requisição:', error);
                alert('Erro ao salvar CFC. Tente novamente.');
            })
            .finally(() => {
                // Restaurar botão
                btnSalvar.innerHTML = originalText;
                btnSalvar.disabled = false;
            });
        } else {
            console.error('❌ Botão de salvar não encontrado');
            alert('Erro: Botão de salvar não encontrado');
        }
        
    } catch (error) {
        console.error('❌ Erro na função salvarCFC:', error);
        alert('Erro interno: ' + error.message);
    }
}

// Função para salvar CFC (chamada diretamente pelo botão)
function salvarCFCDireto() {
    console.log('🚀 Função salvarCFCDireto chamada!');
    
    try {
        const form = document.getElementById('formCFC');
        if (!form) {
            console.error('❌ Formulário não encontrado');
            alert('Erro: Formulário não encontrado');
            return;
        }
        
        console.log('✅ Formulário encontrado');
        const formData = new FormData(form);
        
        // Validações básicas
        if (!formData.get('nome').trim()) {
            console.log('❌ Nome do CFC é obrigatório');
            alert('Nome do CFC é obrigatório');
            return;
        }
        
        if (!formData.get('cnpj').trim()) {
            console.log('❌ CNPJ é obrigatório');
            alert('CNPJ é obrigatório');
            return;
        }
        
        if (!formData.get('cidade').trim()) {
            console.log('❌ Cidade é obrigatória');
            alert('Cidade é obrigatória');
            return;
        }
        
        if (!formData.get('uf')) {
            console.log('❌ UF é obrigatória');
            alert('UF é obrigatória');
            return;
        }
        
        console.log('✅ Validações passaram');
        
        // Preparar dados para envio
        const cfcData = {
            nome: formData.get('nome').trim(),
            cnpj: formData.get('cnpj').trim(),
            razao_social: formData.get('razao_social').trim() || formData.get('nome').trim(),
            email: formData.get('email').trim(),
            telefone: formData.get('telefone').trim(),
            cep: formData.get('cep').trim(),
            endereco: formData.get('endereco').trim(),
            bairro: formData.get('bairro').trim(),
            cidade: formData.get('cidade').trim(),
            uf: formData.get('uf'),
            responsavel_id: formData.get('responsavel_id') || null,
            ativo: formData.get('ativo') === '1',
            observacoes: formData.get('observacoes').trim()
        };
        
        console.log('📋 Dados preparados:', cfcData);
        
        const acao = formData.get('acao');
        const cfc_id = formData.get('cfc_id');
        
        if (acao === 'editar' && cfc_id) {
            cfcData.id = cfc_id;
        }
        
        // Mostrar loading
        const btnSalvar = document.getElementById('btnSalvarCFC');
        if (btnSalvar) {
            const originalText = btnSalvar.innerHTML;
            btnSalvar.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Salvando...';
            btnSalvar.disabled = true;
            
            console.log('🔄 Fazendo requisição para a API...');
            
                    // Fazer requisição para a API
        const url = await detectarCaminhoAPI();
        const method = acao === 'editar' ? 'PUT' : 'POST';
            
            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify(cfcData)
            })
            .then(response => {
                console.log('📡 Resposta recebida:', response);
                return response.json();
            })
            .then(data => {
                console.log('📋 Dados da resposta:', data);
                if (data.success) {
                    console.log('✅ CFC salvo com sucesso!');
                    alert(data.message || 'CFC salvo com sucesso!');
                    
                    // Fechar modal customizado
                    if (typeof fecharModalCFC === 'function') {
                        fecharModalCFC();
                    }
                    
                    // Limpar formulário
                    form.reset();
                    
                    // Recarregar página para mostrar dados atualizados
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    console.log('❌ Erro ao salvar:', data.error);
                    alert(data.error || 'Erro ao salvar CFC');
                }
            })
            .catch(error => {
                console.error('❌ Erro na requisição:', error);
                alert('Erro ao salvar CFC. Tente novamente.');
            })
            .finally(() => {
                // Restaurar botão
                btnSalvar.innerHTML = originalText;
                btnSalvar.disabled = false;
            });
        } else {
            console.error('❌ Botão de salvar não encontrado');
            alert('Erro: Botão de salvar não encontrado');
        }
        
    } catch (error) {
        console.error('❌ Erro na função salvarCFCDireto:', error);
        alert('Erro interno: ' + error.message);
    }
}

function excluirCFC(id) {
    console.log('Função excluirCFC chamada com ID:', id);
    console.log('Evento recebido:', event);
    
    // Verificar se há registros vinculados primeiro
    verificarRegistrosVinculados(id).then(hasVinculados => {
        if (hasVinculados) {
            // Perguntar se deseja exclusão em cascata
            const mensagemCascata = '⚠️ ATENÇÃO: Este CFC possui registros vinculados!\n\n' +
                'Opções:\n' +
                '1. Exclusão em cascata: Remove o CFC e TODOS os registros vinculados\n' +
                '2. Cancelar: Mantém o CFC e os registros\n\n' +
                'Deseja continuar com exclusão em cascata?';
            
            if (confirm(mensagemCascata)) {
                excluirCFCCascata(id);
            }
        } else {
            // Exclusão normal
            const mensagem = '⚠️ ATENÇÃO: Esta ação não pode ser desfeita!\n\nDeseja realmente excluir este CFC?';
            if (confirm(mensagem)) {
                excluirCFCNormal(id);
            }
        }
    });
}

// Garantir que a função esteja disponível globalmente
window.excluirCFC = excluirCFC;

// Fallback para excluirCFC
if (typeof window.excluirCFC !== 'function') {
    console.warn('Função excluirCFC não encontrada, criando fallback...');
    window.excluirCFC = function(id) {
        console.log('Usando função fallback excluirCFC para ID: ' + id);
        alert('Função de exclusão não está funcionando. Tente recarregar a página.');
    };
}

// Função para verificar se há registros vinculados
async function verificarRegistrosVinculados(id) {
    try {
        const response = await fetchAPI(`?id=${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        });
        
        if (response.status === 400) {
            const data = await response.json();
            return data.details && (data.details.instrutores > 0 || data.details.alunos > 0 || 
                                   data.details.veiculos > 0 || data.details.aulas > 0);
        }
        return false;
    } catch (error) {
        console.error('Erro ao verificar registros vinculados:', error);
        return false;
    }
}

// Função para exclusão normal (sem registros vinculados)
function excluirCFCNormal(id) {
    console.log('Exclusão normal do CFC ID:', id);
    executarExclusao(id, false);
}

// Função para exclusão em cascata
function excluirCFCCascata(id) {
    console.log('Exclusão em cascata do CFC ID:', id);
    executarExclusao(id, true);
}

// Função principal de exclusão
function executarExclusao(id, cascade = false) {
    // Mostrar loading - usar o botão correto
    let btnExcluir;
    if (event && event.target) {
        btnExcluir = event.target;
    } else {
        // Fallback: procurar o botão pelo ID
        btnExcluir = document.querySelector(`button[onclick*="excluirCFC(${id})"]`);
    }
    
    if (!btnExcluir) {
        console.error('❌ Botão de exclusão não encontrado');
        mostrarAlerta('Erro: Botão não encontrado', 'danger');
        return;
    }
    
    console.log('Botão encontrado:', btnExcluir);
    
    const originalText = btnExcluir.innerHTML;
    btnExcluir.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Excluindo...';
    btnExcluir.disabled = true;
    
            const url = cascade ? 
                `${await detectarCaminhoAPI()}?id=${id}&cascade=true` :
        `${await detectarCaminhoAPI()}?id=${id}`;
    
    console.log('Fazendo requisição DELETE para:', url);
    
    fetch(url, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
        .then(response => {
            console.log('Resposta recebida:', response);
            console.log('Status:', response.status);
            console.log('Status Text:', response.statusText);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Dados recebidos:', data);
            
            if (data.success) {
                let mensagem = 'CFC excluído com sucesso!';
                
                // Se foi exclusão em cascata, mostrar detalhes
                if (data.details) {
                    const detalhes = [];
                    if (data.details.instrutores_removidos > 0) detalhes.push(`${data.details.instrutores_removidos} instrutor(es)`);
                    if (data.details.alunos_removidos > 0) detalhes.push(`${data.details.alunos_removidos} aluno(s)`);
                    if (data.details.veiculos_removidos > 0) detalhes.push(`${data.details.veiculos_removidos} veículo(s)`);
                    if (data.details.aulas_removidas > 0) detalhes.push(`${data.details.aulas_removidas} aula(s)`);
                    
                    if (detalhes.length > 0) {
                        mensagem += `\n\nRegistros removidos em cascata:\n• ${detalhes.join('\n• ')}`;
                    }
                }
                
                mostrarAlerta(mensagem, 'success');
                setTimeout(() => {
                    location.reload();
                }, 3000); // Mais tempo para ler a mensagem
            } else {
                mostrarAlerta(data.error || 'Erro ao excluir CFC', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro capturado:', error);
            console.error('Mensagem de erro:', error.message);
            
            if (error.message.includes('401')) {
                mostrarAlerta('Sessão expirada. Faça login novamente.', 'warning');
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 2000);
            } else if (error.message.includes('400')) {
                console.log('Erro 400 - tentando obter detalhes...');
                // Tentar extrair detalhes do erro se disponível
                fetchAPI(`?id=${id}`, {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Detalhes do erro 400:', data);
                    if (data.error) {
                        mostrarAlerta(data.error, 'warning');
                    } else {
                        mostrarAlerta('Não é possível excluir este CFC. Verifique se há registros vinculados.', 'warning');
                    }
                })
                .catch(fetchError => {
                    console.error('Erro ao obter detalhes:', fetchError);
                    mostrarAlerta('Não é possível excluir este CFC. Verifique se há registros vinculados.', 'warning');
                });
            } else {
                mostrarAlerta('Erro ao excluir CFC: ' + error.message, 'danger');
            }
        })
        .finally(() => {
            console.log('Finalizando operação de exclusão');
            // Restaurar botão
            if (btnExcluir) {
                btnExcluir.innerHTML = originalText;
                btnExcluir.disabled = false;
            }
        });
}

function exportarCFCs() {
    // Buscar dados reais da API
    fetchAPI(``, {
        credentials: 'same-origin'
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Criar CSV
                let csv = 'Nome,CNPJ,Cidade,UF,Telefone,Email,Status\n';
                data.data.forEach(cfc => {
                    csv += `"${cfc.nome}","${cfc.cnpj}","${cfc.cidade}","${cfc.uf}","${cfc.telefone || ''}","${cfc.email || ''}","${cfc.ativo ? 'Ativo' : 'Inativo'}"\n`;
                });
                
                // Download do arquivo
                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = 'cfcs.csv';
                link.click();
                
                mostrarAlerta('Exportação concluída!', 'success');
            } else {
                mostrarAlerta(data.error || 'Erro ao exportar CFCs', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('Erro ao exportar CFCs. Tente novamente.', 'danger');
        });
}

function imprimirCFCs() {
    window.print();
}

// FUNÇÕES PARA MODAL CUSTOMIZADO - REMOVIDAS (já definidas globalmente)
// As funções abrirModalCFC e fecharModalCFC estão definidas globalmente no início do script

// Fechar modal ao clicar fora dele
document.addEventListener('click', function(e) {
    const modal = document.getElementById('modalCFC');
    if (e.target === modal) {
        fecharModalCFC();
    }
});

// Fechar modal com ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('modalCFC');
        if (modal && modal.style.display === 'block') {
            fecharModalCFC();
        }
    }
});

// Função ajustarModalResponsivo - REMOVIDA (já definida globalmente)

// Aplicar responsividade no resize da janela
window.addEventListener('resize', function() {
    if (document.getElementById('modalCFC').style.display === 'block') {
        ajustarModalResponsivo();
    }
});

// Função para mostrar alertas
function mostrarAlerta(mensagem, tipo) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${tipo} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${mensagem}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Tentar encontrar um local apropriado para inserir o alerta
    const container = document.querySelector('.container-fluid') || 
                     document.querySelector('.admin-main') || 
                     document.querySelector('main') || 
                     document.body;
    
    const firstElement = container.querySelector('.d-flex') || 
                        container.querySelector('.card') || 
                        container.querySelector('h1') ||
                        container.firstElementChild;
    
    if (firstElement) {
        container.insertBefore(alertDiv, firstElement);
    } else {
        container.appendChild(alertDiv);
    }
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Debug: Verificar se a função está disponível
console.log('🔍 Verificando função salvarCFC...');
console.log('🕐 Timestamp: ' + new Date().toISOString());
console.log('📁 Arquivo: pages/cfcs.php - VERSÃO ATUALIZADA');

if (typeof salvarCFC === 'function') {
    console.log('✅ Função salvarCFC está disponível');
} else {
    console.log('❌ Função salvarCFC NÃO está disponível');
}

// Debug: Verificar se o botão existe
setTimeout(() => {
    const btn = document.getElementById('btnSalvarCFC');
    if (btn) {
        console.log('✅ Botão btnSalvarCFC encontrado no DOM');
        console.log('🔧 Botão HTML:', btn.outerHTML);
    } else {
        console.log('❌ Botão btnSalvarCFC NÃO encontrado no DOM');
    }
}, 1000);

console.log('📋 Script de CFCs carregado completamente');
</script>
