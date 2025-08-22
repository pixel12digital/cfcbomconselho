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
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCFC">
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
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCFC">
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
                                <?php 
                                $endereco = json_decode($cfc['endereco'], true);
                                if ($endereco && isset($endereco['cidade'])) {
                                    echo htmlspecialchars($endereco['cidade']) . '/' . htmlspecialchars($endereco['uf']);
                                } else {
                                    echo '<span class="text-muted">Não informado</span>';
                                }
                                ?>
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

<!-- Modal para Cadastro/Edição de CFC -->
<div class="modal fade" id="modalCFC" tabindex="-1" aria-labelledby="modalCFCLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formCFC" method="POST" action="admin/pages/cfcs.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCFCLabel">
                        <i class="fas fa-building me-2"></i><span id="modalTitle">Novo CFC</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="acao" id="acaoCFC" value="criar">
                    <input type="hidden" name="cfc_id" id="cfc_id" value="">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome do CFC *</label>
                                <input type="text" class="form-control" id="nome" name="nome" required 
                                       placeholder="Nome completo do Centro de Formação de Condutores">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="cnpj" class="form-label">CNPJ *</label>
                                <input type="text" class="form-control" id="cnpj" name="cnpj" required 
                                       placeholder="00.000.000/0000-00">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="contato@cfc.com.br">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="telefone" class="form-label">Telefone</label>
                                <input type="text" class="form-control" id="telefone" name="telefone" 
                                       placeholder="(00) 00000-0000">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="cep" class="form-label">CEP</label>
                                <input type="text" class="form-control" id="cep" name="cep" 
                                       placeholder="00000-000">
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="mb-3">
                                <label for="logradouro" class="form-label">Logradouro</label>
                                <input type="text" class="form-control" id="logradouro" name="logradouro" 
                                       placeholder="Rua, Avenida, etc.">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label for="numero" class="form-label">Número</label>
                                <input type="text" class="form-control" id="numero" name="numero" 
                                       placeholder="123">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="bairro" class="form-label">Bairro</label>
                                <input type="text" class="form-control" id="bairro" name="bairro" 
                                       placeholder="Centro, Jardim, etc.">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="cidade" class="form-label">Cidade</label>
                                <input type="text" class="form-control" id="cidade" name="cidade" 
                                       placeholder="Nome da cidade">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
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
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
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
                            <div class="mb-3">
                                <label for="ativo" class="form-label">Status</label>
                                <select class="form-select" id="ativo" name="ativo">
                                    <option value="1">Ativo</option>
                                    <option value="0">Inativo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="3" 
                                  placeholder="Informações adicionais sobre o CFC..."></textarea>
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
                <button type="button" class="btn btn-primary" id="btnEditarVisualizacao">
                    <i class="fas fa-edit me-1"></i>Editar CFC
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts específicos para CFCs -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar máscaras
    inicializarMascarasCFC();
    
    // Inicializar filtros
    inicializarFiltrosCFC();
    
    // Inicializar busca
    inicializarBuscaCFC();
    
    // Handler para o formulário de CFC
    document.getElementById('formCFC').addEventListener('submit', function(e) {
        e.preventDefault();
        salvarCFC();
    });
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
                document.getElementById('logradouro').value = data.logradouro;
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

function editarCFC(id) {
    // Buscar dados do CFC
    fetch(`../api/cfcs.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                preencherFormularioCFC(data.data);
                document.getElementById('modalTitle').textContent = 'Editar CFC';
                document.getElementById('acaoCFC').value = 'editar';
                document.getElementById('cfc_id').value = id;
                
                const modal = new bootstrap.Modal(document.getElementById('modalCFC'));
                modal.show();
            } else {
                mostrarAlerta('Erro ao carregar dados do CFC', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarAlerta('Erro ao carregar dados do CFC', 'danger');
        });
}

function preencherFormularioCFC(cfc) {
    document.getElementById('nome').value = cfc.nome || '';
    document.getElementById('cnpj').value = cfc.cnpj || '';
    document.getElementById('email').value = cfc.email || '';
    document.getElementById('telefone').value = cfc.telefone || '';
    
    // Endereço
    if (cfc.endereco) {
        const endereco = typeof cfc.endereco === 'string' ? JSON.parse(cfc.endereco) : cfc.endereco;
        document.getElementById('cep').value = endereco.cep || '';
        document.getElementById('logradouro').value = endereco.logradouro || '';
        document.getElementById('numero').value = endereco.numero || '';
        document.getElementById('bairro').value = endereco.bairro || '';
        document.getElementById('cidade').value = endereco.cidade || '';
        document.getElementById('uf').value = endereco.uf || '';
    }
    
    document.getElementById('responsavel_id').value = cfc.responsavel_id || '';
    document.getElementById('ativo').value = cfc.ativo ? '1' : '0';
    document.getElementById('observacoes').value = cfc.observacoes || '';
}

function visualizarCFC(id) {
    fetch(`../api/cfcs.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                preencherModalVisualizacao(data.data);
                const modal = new bootstrap.Modal(document.getElementById('modalVisualizarCFC'));
                modal.show();
            } else {
                mostrarAlerta('Erro ao carregar dados do CFC', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarAlerta('Erro ao carregar dados do CFC', 'danger');
        });
}

function preencherModalVisualizacao(cfc) {
    const endereco = typeof cfc.endereco === 'string' ? JSON.parse(cfc.endereco) : cfc.endereco;
    
    const html = `
        <div class="row">
            <div class="col-md-8">
                <h4>${cfc.nome}</h4>
                <p class="text-muted">CNPJ: ${cfc.cnpj}</p>
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
                <p>${endereco?.logradouro || ''} ${endereco?.numero || ''}</p>
                <p>${endereco?.bairro || ''}</p>
                <p>${endereco?.cidade || ''} - ${endereco?.uf || ''}</p>
                <p>CEP: ${endereco?.cep || 'Não informado'}</p>
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
    window.location.href = `admin/pages/gerenciar-cfc.php?id=${id}`;
}

function ativarCFC(id) {
    if (confirm('Deseja realmente ativar este CFC?')) {
        alterarStatusCFC(id, 1);
    }
}

function desativarCFC(id) {
    if (confirm('Deseja realmente desativar este CFC? Esta ação pode afetar alunos e instrutores vinculados.')) {
        alterarStatusCFC(id, 0);
    }
}

function alterarStatusCFC(id, status) {
    if (confirm('Deseja realmente alterar o status deste CFC?')) {
        // Fazer requisição para a API
        fetch(`../api/cfcs.php`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
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
    const form = document.getElementById('formCFC');
    const formData = new FormData(form);
    
    // Validações básicas
    if (!formData.get('nome').trim()) {
        mostrarAlerta('Nome do CFC é obrigatório', 'danger');
        return;
    }
    
    if (!formData.get('cnpj').trim()) {
        mostrarAlerta('CNPJ é obrigatório', 'danger');
        return;
    }
    
    if (!formData.get('cidade').trim()) {
        mostrarAlerta('Cidade é obrigatória', 'danger');
        return;
    }
    
    if (!formData.get('uf')) {
        mostrarAlerta('UF é obrigatória', 'danger');
        return;
    }
    
    // Preparar dados para envio
    const cfcData = {
        nome: formData.get('nome').trim(),
        cnpj: formData.get('cnpj').trim(),
        razao_social: formData.get('nome').trim(),
        email: formData.get('email').trim(),
        telefone: formData.get('telefone').trim(),
        cep: formData.get('cep').trim(),
        endereco: formData.get('logradouro').trim() + ' ' + formData.get('numero').trim(),
        bairro: formData.get('bairro').trim(),
        cidade: formData.get('cidade').trim(),
        uf: formData.get('uf'),
        responsavel: formData.get('responsavel').trim(),
        ativo: true
    };
    
    const acao = formData.get('acao');
    const cfc_id = formData.get('cfc_id');
    
    if (acao === 'editar' && cfc_id) {
        cfcData.id = cfc_id;
    }
    
    // Mostrar loading
    const btnSalvar = document.getElementById('btnSalvarCFC');
    const originalText = btnSalvar.innerHTML;
    btnSalvar.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Salvando...';
    btnSalvar.disabled = true;
    
    // Fazer requisição para a API
    const url = '../api/cfcs.php';
    const method = acao === 'editar' ? 'PUT' : 'POST';
    
    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(cfcData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarAlerta(data.message || 'CFC salvo com sucesso!', 'success');
            
            // Fechar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalCFC'));
            modal.hide();
            
            // Limpar formulário
            form.reset();
            
            // Recarregar página para mostrar dados atualizados
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            mostrarAlerta(data.error || 'Erro ao salvar CFC', 'danger');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mostrarAlerta('Erro ao salvar CFC. Tente novamente.', 'danger');
    })
    .finally(() => {
        // Restaurar botão
        btnSalvar.innerHTML = originalText;
        btnSalvar.disabled = false;
    });
}

function excluirCFC(id) {
    const mensagem = '⚠️ ATENÇÃO: Esta ação não pode ser desfeita!\n\nDeseja realmente excluir este CFC?';
    
    if (confirm(mensagem)) {
        fetch(`../api/cfcs.php`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta('CFC excluído com sucesso!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                mostrarAlerta(data.error || 'Erro ao excluir CFC', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarAlerta('Erro ao excluir CFC', 'danger');
        });
    }
}

function exportarCFCs() {
    // Buscar dados reais da API
    fetch('../api/cfcs.php')
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
            console.error('Erro:', error);
            mostrarAlerta('Erro ao exportar CFCs. Tente novamente.', 'danger');
        });
}

function imprimirCFCs() {
    window.print();
}

// Função para mostrar alertas
function mostrarAlerta(mensagem, tipo) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${tipo} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${mensagem}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.d-flex'));
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>
