<?php
// Página de gerenciamento de instrutores - VERSÃO CORRIGIDA
// Os includes já são feitos pelo admin/index.php
// Apenas verificar se as funções estão disponíveis
if (!function_exists('isLoggedIn') || !function_exists('hasPermission')) {
    die('Funções de autenticação não disponíveis');
}

$pageTitle = 'Gestão de Instrutores';
?>

<!-- Incluir CSS do modal -->
<link rel="stylesheet" href="assets/css/modal-instrutores.css">

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Gestão de Instrutores</h1>
                <button class="btn btn-primary" onclick="abrirModalInstrutor()">
                    <i class="fas fa-plus"></i> Novo Instrutor
                </button>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="filtroStatus" class="form-label">Status</label>
                            <select id="filtroStatus" class="form-select" onchange="filtrarInstrutores()">
                                <option value="">Todos</option>
                                <option value="1">Ativo</option>
                                <option value="0">Inativo</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filtroCFC" class="form-label">CFC</label>
                            <select id="filtroCFC" class="form-select" onchange="filtrarInstrutores()">
                                <option value="">Todos</option>
                                <!-- Preencher com CFCs -->
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filtroCategoria" class="form-label">Categoria</label>
                            <select id="filtroCategoria" class="form-select" onchange="filtrarInstrutores()">
                                <option value="">Todas</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                                <option value="E">E</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="buscaInstrutor" class="form-label">Buscar</label>
                            <input type="text" id="buscaInstrutor" class="form-control" placeholder="Nome ou credencial..." oninput="filtrarInstrutores()">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <button class="btn btn-outline-secondary" onclick="limparFiltros()">
                                <i class="fas fa-times"></i> Limpar Filtros
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estatísticas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0" id="totalInstrutores">0</h4>
                                    <small>Total de Instrutores</small>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0" id="instrutoresAtivos">0</h4>
                                    <small>Instrutores Ativos</small>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabela de Instrutores -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Lista de Instrutores</h5>
                        <div>
                            <button class="btn btn-outline-success btn-sm" onclick="exportarInstrutores()">
                                <i class="fas fa-download"></i> Exportar
                            </button>
                            <button class="btn btn-outline-info btn-sm" onclick="imprimirInstrutores()">
                                <i class="fas fa-print"></i> Imprimir
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tabelaInstrutores" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>CFC</th>
                                    <th>Credencial</th>
                                    <th>Categorias</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Preencher via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Customizado para Cadastro/Edição de Instrutor -->
<div id="modalInstrutor" class="custom-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 9999; overflow: auto;">
    <div class="custom-modal-dialog" style="position: relative; width: 95%; max-width: 1200px; margin: 20px auto; background: white; border-radius: 0.5rem; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15); overflow: hidden; display: block;">
        <form id="formInstrutor" onsubmit="return false;">
            <div class="modal-header" style="background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%); color: white; border-bottom: none; padding: 0.75rem 1.5rem; flex-shrink: 0;">
                <h5 class="modal-title" id="modalTitle" style="color: white; font-weight: 600; font-size: 1.25rem; margin: 0;">
                    <i class="fas fa-user-tie me-2"></i>Novo Instrutor
                </h5>
                <button type="button" class="btn-close" onclick="fecharModalInstrutor()" style="filter: invert(1); background: none; border: none; font-size: 1.25rem; color: white; opacity: 0.8; cursor: pointer;">&times;</button>
            </div>
            <div class="modal-body" style="overflow-y: auto; padding: 1rem; max-height: 70vh;">
                <input type="hidden" name="acao" id="acaoInstrutor" value="novo">
                <input type="hidden" name="instrutor_id" id="instrutor_id" value="">
                
                <div class="container-fluid" style="padding: 0;">
                                                 <!-- Seção 1: Informações Básicas -->
                         <div class="row mb-2">
                             <div class="col-12">
                                 <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem; margin-bottom: 0.5rem !important;">
                                     <i class="fas fa-user-tie me-1"></i>Informações Básicas
                                 </h6>
                             </div>
                             <div class="col-md-4">
                                 <div class="mb-1">
                                     <label for="nome" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Nome Completo *</label>
                                     <input type="text" class="form-control" id="nome" name="nome" required 
                                            placeholder="Nome completo" style="padding: 0.4rem; font-size: 0.85rem;">
                                 </div>
                             </div>
                             <div class="col-md-4">
                                 <div class="mb-1">
                                     <label for="cpf" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">CPF *</label>
                                     <input type="text" class="form-control" id="cpf" name="cpf" required 
                                            placeholder="000.000.000-00" style="padding: 0.4rem; font-size: 0.85rem;">
                                 </div>
                             </div>
                             <div class="col-md-4">
                                 <div class="mb-1">
                                     <label for="cnh" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">CNH *</label>
                                     <input type="text" class="form-control" id="cnh" name="cnh" required 
                                            placeholder="Número da CNH" style="padding: 0.4rem; font-size: 0.85rem;">
                                 </div>
                             </div>
                         </div>
                         
                         <div class="row mb-2">
                             <div class="col-md-4">
                                 <div class="mb-1">
                                     <label for="data_nascimento" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Data Nascimento *</label>
                                     <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" required 
                                            style="padding: 0.4rem; font-size: 0.85rem;">
                                 </div>
                             </div>
                             <div class="col-md-4">
                                 <div class="mb-1">
                                     <label for="email" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Email *</label>
                                     <input type="email" class="form-control" id="email" name="email" required 
                                            placeholder="email@exemplo.com" style="padding: 0.4rem; font-size: 0.85rem;">
                                 </div>
                             </div>
                             <div class="col-md-4">
                                 <div class="mb-1">
                                     <label for="telefone" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Telefone *</label>
                                     <input type="text" class="form-control" id="telefone" name="telefone" required 
                                            placeholder="(00) 00000-0000" style="padding: 0.4rem; font-size: 0.85rem;">
                                 </div>
                             </div>
                         </div>
                         
                         <div class="row mb-2">
                             <div class="col-md-6">
                                 <div class="mb-1">
                                     <label for="usuario_id" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Usuário *</label>
                                     <select id="usuario_id" name="usuario_id" class="form-select" required style="padding: 0.4rem; font-size: 0.85rem;">
                                         <option value="">Selecione um usuário</option>
                                         <!-- Preencher com usuários -->
                                     </select>
                                 </div>
                             </div>
                             <div class="col-md-6">
                                 <div class="mb-1">
                                     <label for="cfc_id" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">CFC *</label>
                                     <select id="cfc_id" name="cfc_id" class="form-select" required style="padding: 0.4rem; font-size: 0.85rem;">
                                         <option value="">Selecione um CFC</option>
                                         <!-- Preencher com CFCs -->
                                     </select>
                                 </div>
                             </div>
                         </div>
                        
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <div class="mb-1">
                                    <label for="credencial" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Credencial *</label>
                                    <input type="text" class="form-control" id="credencial" name="credencial" required 
                                           placeholder="Número da credencial" style="padding: 0.4rem; font-size: 0.85rem;">
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
                        
                        <!-- Seção 2: Categorias de Habilitação -->
                        <div class="row mb-1">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-1 mb-1" style="font-size: 0.9rem; margin-bottom: 0.3rem !important;">
                                    <i class="fas fa-car me-1"></i>Categorias de Habilitação *
                                </h6>
                            </div>
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="categorias[]" value="A" id="catA" style="margin-top: 0.2rem;">
                                            <label class="form-check-label" for="catA" style="font-size: 0.85rem;">A</label>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="categorias[]" value="B" id="catB" style="margin-top: 0.2rem;">
                                            <label class="form-check-label" for="catB" style="font-size: 0.85rem;">B</label>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="categorias[]" value="C" id="catC" style="margin-top: 0.2rem;">
                                            <label class="form-check-label" for="catC" style="font-size: 0.85rem;">C</label>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="categorias[]" value="D" id="catD" style="margin-top: 0.2rem;">
                                            <label class="form-check-label" for="catD" style="font-size: 0.85rem;">D</label>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="categorias[]" value="E" id="catE" style="margin-top: 0.2rem;">
                                            <label class="form-check-label" for="catE" style="font-size: 0.85rem;">E</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        

                         
                         <!-- Seção 3: Horários Disponíveis -->
                         <div class="row mb-1">
                             <div class="col-12">
                                 <h6 class="text-primary border-bottom pb-1 mb-1" style="font-size: 0.8rem; margin-bottom: 0.3rem !important;">
                                     <i class="fas fa-clock me-1"></i>Horários Disponíveis
                                 </h6>
                             </div>
                             <div class="col-md-12">
                                 <div class="row">
                                     <div class="col-md-2">
                                         <div class="form-check">
                                             <input class="form-check-input" type="checkbox" name="dias_semana[]" value="segunda" id="segunda" style="margin-top: 0.2rem;">
                                             <label class="form-check-label" for="segunda" style="font-size: 0.85rem;">Segunda</label>
                                         </div>
                                     </div>
                                     <div class="col-md-2">
                                         <div class="form-check">
                                             <input class="form-check-input" type="checkbox" name="dias_semana[]" value="terca" id="terca" style="margin-top: 0.2rem;">
                                             <label class="form-check-label" for="terca" style="font-size: 0.85rem;">Terça</label>
                                         </div>
                                     </div>
                                     <div class="col-md-2">
                                         <div class="form-check">
                                             <input class="form-check-input" type="checkbox" name="dias_semana[]" value="quarta" id="quarta" style="margin-top: 0.2rem;">
                                             <label class="form-check-label" for="quarta" style="font-size: 0.85rem;">Quarta</label>
                                         </div>
                                     </div>
                                     <div class="col-md-2">
                                         <div class="form-check">
                                             <input class="form-check-input" type="checkbox" name="dias_semana[]" value="quinta" id="quinta" style="margin-top: 0.2rem;">
                                             <label class="form-check-label" for="quinta" style="font-size: 0.85rem;">Quinta</label>
                                         </div>
                                     </div>
                                     <div class="col-md-2">
                                         <div class="form-check">
                                             <input class="form-check-input" type="checkbox" name="dias_semana[]" value="sexta" id="sexta" style="margin-top: 0.2rem;">
                                             <label class="form-check-label" for="sexta" style="font-size: 0.85rem;">Sexta</label>
                                         </div>
                                     </div>
                                     <div class="col-md-2">
                                         <div class="form-check">
                                             <input class="form-check-input" type="checkbox" name="dias_semana[]" value="sabado" id="sabado" style="margin-top: 0.2rem;">
                                             <label class="form-check-label" for="sabado" style="font-size: 0.85rem;">Sábado</label>
                                         </div>
                                     </div>
                                 </div>
                             </div>
                         </div>
                         
                         <div class="row mb-1">
                             <div class="col-md-6">
                                 <div class="mb-1">
                                     <label for="horario_inicio" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Horário Início</label>
                                     <input type="time" class="form-control" id="horario_inicio" name="horario_inicio" 
                                            style="padding: 0.3rem; font-size: 0.85rem;">
                                 </div>
                             </div>
                             <div class="col-md-6">
                                 <div class="mb-1">
                                     <label for="horario_fim" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Horário Fim</label>
                                     <input type="time" class="form-control" id="horario_fim" name="horario_fim" 
                                            style="padding: 0.3rem; font-size: 0.85rem;">
                                 </div>
                             </div>
                         </div>
                        
                                                                                                 <!-- Seção 4: Endereço -->
                         <div class="row mb-1">
                             <div class="col-12">
                                 <h6 class="text-primary border-bottom pb-1 mb-1" style="font-size: 0.9rem; margin-bottom: 0.3rem !important;">
                                     <i class="fas fa-map-marker-alt me-1"></i>Endereço
                                 </h6>
                             </div>
                            <div class="col-md-12">
                                                                 <div class="mb-1">
                                     <label for="endereco" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Endereço</label>
                                     <input type="text" class="form-control" id="endereco" name="endereco" 
                                            placeholder="Rua, Avenida, número, etc." style="padding: 0.3rem; font-size: 0.85rem;">
                                 </div>
                            </div>
                        </div>
                        
                                                 <div class="row mb-1">
                             <div class="col-md-6">
                                 <div class="mb-1">
                                     <label for="cidade" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Cidade</label>
                                     <input type="text" class="form-control" id="cidade" name="cidade" 
                                            placeholder="Nome da cidade" style="padding: 0.3rem; font-size: 0.85rem;">
                                 </div>
                             </div>
                             <div class="col-md-6">
                                 <div class="mb-1">
                                     <label for="uf" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">UF</label>
                                     <select class="form-select" id="uf" name="uf" style="padding: 0.3rem; font-size: 0.85rem;">
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
                         
                                                   <!-- Seção 5: Especialidades e Observações -->
                          <div class="row mb-1">
                              <div class="col-12">
                                  <h6 class="text-primary border-bottom pb-1 mb-1" style="font-size: 0.9rem; margin-bottom: 0.3rem !important;">
                                      <i class="fas fa-star me-1"></i>Especialidades e Observações
                                  </h6>
                              </div>
                             <div class="col-md-4">
                                 <div class="mb-1">
                                     <label for="tipo_carga" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Tipo de Carga</label>
                                     <select class="form-select" id="tipo_carga" name="tipo_carga" style="padding: 0.4rem; font-size: 0.85rem;">
                                         <option value="">Selecione...</option>
                                         <option value="perigosa">Carga Perigosa</option>
                                         <option value="granel">Carga Granel</option>
                                         <option value="frigorificada">Carga Frigorificada</option>
                                         <option value="contenores">Contêineres</option>
                                         <option value="veiculos">Transporte de Veículos</option>
                                         <option value="outros">Outros</option>
                                     </select>
                                 </div>
                             </div>
                             <div class="col-md-4">
                                 <div class="mb-1">
                                     <label for="validade_credencial" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Validade Credencial</label>
                                     <input type="date" class="form-control" id="validade_credencial" name="validade_credencial" 
                                            style="padding: 0.4rem; font-size: 0.85rem;">
                                 </div>
                             </div>
                                                           <div class="col-md-4">
                                  <div class="mb-1">
                                      <label for="observacoes" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Observações</label>
                                      <textarea class="form-control" id="observacoes" name="observacoes" rows="3" 
                                                placeholder="Observações..." style="padding: 0.3rem; font-size: 0.85rem; resize: vertical;"></textarea>
                                  </div>
                              </div>
                         </div>
                         

                    </div>
                </div>
                <div class="modal-footer" style="background-color: #f8f9fa; border-top: 1px solid #dee2e6; padding: 0.75rem 1.5rem; display: flex; justify-content: flex-end; gap: 1rem; flex-shrink: 0;">
                    <button type="button" class="btn btn-secondary" onclick="fecharModalInstrutor()" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSalvarInstrutor" onclick="salvarInstrutor()" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                        <i class="fas fa-save me-1"></i>Salvar Instrutor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Funções JavaScript com URLs CORRIGIDAS
function abrirModalInstrutor() {
    document.getElementById('modalTitle').textContent = 'Novo Instrutor';
    document.getElementById('acaoInstrutor').value = 'novo';
    document.getElementById('instrutor_id').value = '';
    document.getElementById('formInstrutor').reset();
    
    const modal = document.getElementById('modalInstrutor');
    modal.style.display = 'block';
    modal.classList.add('show');
    
    // Garantir que o modal seja visível
    setTimeout(() => {
        modal.scrollTop = 0;
        const modalDialog = modal.querySelector('.custom-modal-dialog');
        if (modalDialog) {
            modalDialog.style.opacity = '1';
            modalDialog.style.transform = 'translateY(0)';
        }
    }, 100);
}

function fecharModalInstrutor() {
    const modal = document.getElementById('modalInstrutor');
    modal.classList.remove('show');
    
    // Animar o fechamento
    const modalDialog = modal.querySelector('.custom-modal-dialog');
    if (modalDialog) {
        modalDialog.style.opacity = '0';
        modalDialog.style.transform = 'translateY(-20px)';
    }
    
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

function editarInstrutor(id) {
    // Buscar dados do instrutor
    fetch(`/cfc-bom-conselho/admin/api/instrutores.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                preencherFormularioInstrutor(data.data);
                document.getElementById('modalTitle').textContent = 'Editar Instrutor';
                document.getElementById('acaoInstrutor').value = 'editar';
                document.getElementById('instrutor_id').value = id;
                
                abrirModalInstrutor();
            } else {
                mostrarAlerta('Erro ao carregar dados do instrutor', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarAlerta('Erro ao carregar dados do instrutor', 'danger');
        });
}

function preencherFormularioInstrutor(instrutor) {
    // Preencher campos do formulário
    document.getElementById('nome').value = instrutor.nome || instrutor.nome_usuario || '';
    document.getElementById('cpf').value = instrutor.cpf || '';
    document.getElementById('cnh').value = instrutor.cnh || '';
    document.getElementById('data_nascimento').value = instrutor.data_nascimento || '';
    document.getElementById('email').value = instrutor.email || '';
    document.getElementById('usuario_id').value = instrutor.usuario_id || '';
    document.getElementById('cfc_id').value = instrutor.cfc_id || '';
    document.getElementById('credencial').value = instrutor.credencial || '';
    document.getElementById('telefone').value = instrutor.telefone || '';
    document.getElementById('endereco').value = instrutor.endereco || '';
    document.getElementById('cidade').value = instrutor.cidade || '';
    document.getElementById('uf').value = instrutor.uf || '';
    document.getElementById('ativo').value = instrutor.ativo ? '1' : '0';
    document.getElementById('tipo_carga').value = instrutor.tipo_carga || '';
    document.getElementById('validade_credencial').value = instrutor.validade_credencial || '';
    document.getElementById('observacoes').value = instrutor.observacoes || '';
    
    // Limpar checkboxes primeiro
    document.querySelectorAll('input[name="categorias[]"]').forEach(cb => cb.checked = false);
    document.querySelectorAll('input[name="dias_semana[]"]').forEach(cb => cb.checked = false);
    
    // Marcar categorias selecionadas
    if (instrutor.categoria_habilitacao) {
        const categorias = instrutor.categoria_habilitacao.split(',');
        categorias.forEach(cat => {
            const checkbox = document.querySelector(`input[name="categorias[]"][value="${cat.trim()}"]`);
            if (checkbox) checkbox.checked = true;
        });
    }
    
    // Marcar dias da semana selecionados
    if (instrutor.dias_semana) {
        const dias = instrutor.dias_semana.split(',');
        dias.forEach(dia => {
            const checkbox = document.querySelector(`input[name="dias_semana[]"][value="${dia.trim()}"]`);
            if (checkbox) checkbox.checked = true;
        });
    }
    
    // Preencher horários
    if (instrutor.horario_inicio) {
        document.getElementById('horario_inicio').value = instrutor.horario_inicio;
    }
    if (instrutor.horario_fim) {
        document.getElementById('horario_fim').value = instrutor.horario_fim;
    }
}

function excluirInstrutor(id) {
    if (confirm('Tem certeza que deseja excluir este instrutor?')) {
        fetch(`/cfc-bom-conselho/admin/api/instrutores.php?id=${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta('Instrutor excluído com sucesso!', 'success');
                carregarInstrutores(); // Recarregar tabela
            } else {
                mostrarAlerta(data.error || 'Erro ao excluir instrutor', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarAlerta('Erro ao excluir instrutor', 'danger');
        });
    }
}

function salvarInstrutor() {
    const form = document.getElementById('formInstrutor');
    const formData = new FormData(form);
    
    // Validações básicas
    if (!formData.get('nome').trim() || !formData.get('cpf').trim() || !formData.get('cnh').trim() || 
        !formData.get('data_nascimento') || !formData.get('email').trim() || !formData.get('usuario_id') || 
        !formData.get('cfc_id') || !formData.get('credencial').trim()) {
        mostrarAlerta('Preencha todos os campos obrigatórios', 'warning');
        return;
    }
    
    // Preparar dados para envio
    const categoriasSelecionadas = formData.getAll('categorias[]');
    if (categoriasSelecionadas.length === 0) {
        mostrarAlerta('Selecione pelo menos uma categoria de habilitação', 'warning');
        return;
    }
    
    const instrutorData = {
        nome: formData.get('nome').trim(),
        cpf: formData.get('cpf').trim(),
        cnh: formData.get('cnh').trim(),
        data_nascimento: formData.get('data_nascimento'),
        email: formData.get('email').trim(),
        usuario_id: formData.get('usuario_id'),
        cfc_id: formData.get('cfc_id'),
        credencial: formData.get('credencial').trim(),
        categoria_habilitacao: categoriasSelecionadas.join(','),
        telefone: formData.get('telefone') || '',
        endereco: formData.get('endereco') || '',
        cidade: formData.get('cidade') || '',
        uf: formData.get('uf') || '',
        ativo: formData.get('ativo') === '1',
        tipo_carga: formData.get('tipo_carga') || '',
        validade_credencial: formData.get('validade_credencial') || '',
        observacoes: formData.get('observacoes') || '',
        dias_semana: formData.getAll('dias_semana[]').join(','),
        horario_inicio: formData.get('horario_inicio') || '',
        horario_fim: formData.get('horario_fim') || ''
    };
    
    const acao = formData.get('acao');
    const instrutor_id = formData.get('instrutor_id');
    
    if (acao === 'editar' && instrutor_id) {
        instrutorData.id = instrutor_id;
    }
    
    // Mostrar loading
    const btnSalvar = document.getElementById('btnSalvarInstrutor');
    const originalText = btnSalvar.innerHTML;
    btnSalvar.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Salvando...';
    btnSalvar.disabled = true;
    
    // Fazer requisição para a API - URL CORRIGIDA
    const url = '/cfc-bom-conselho/admin/api/instrutores.php';
    const method = acao === 'editar' ? 'PUT' : 'POST';
    
    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(instrutorData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarAlerta(data.message || 'Instrutor salvo com sucesso!', 'success');
            
            // Fechar modal
            fecharModalInstrutor();
            
            // Limpar formulário
            form.reset();
            
            // Recarregar página para mostrar dados atualizados
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            mostrarAlerta(data.error || 'Erro ao salvar instrutor', 'danger');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mostrarAlerta('Erro ao salvar instrutor. Tente novamente.', 'danger');
    })
    .finally(() => {
        // Restaurar botão
        btnSalvar.innerHTML = originalText;
        btnSalvar.disabled = false;
    });
}

function mostrarAlerta(mensagem, tipo) {
    // Criar alerta personalizado
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${tipo} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 10000; min-width: 300px;';
    alertDiv.innerHTML = `
        ${mensagem}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto-remover após 5 segundos
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

function filtrarInstrutores() {
    const status = document.getElementById('filtroStatus').value;
    const cfc = document.getElementById('filtroCFC').value;
    const categoria = document.getElementById('filtroCategoria').value;
    const busca = document.getElementById('buscaInstrutor').value.toLowerCase();
    
    // Implementar filtros aqui
    console.log('Filtrando:', { status, cfc, categoria, busca });
}

function limparFiltros() {
    document.getElementById('filtroStatus').value = '';
    document.getElementById('filtroCFC').value = '';
    document.getElementById('filtroCategoria').value = '';
    document.getElementById('buscaInstrutor').value = '';
    
    // Recarregar todos os instrutores
    carregarInstrutores();
}

function exportarInstrutores() {
    // Implementar exportação para CSV/Excel
    mostrarAlerta('Funcionalidade de exportação será implementada em breve!', 'info');
}

function imprimirInstrutores() {
    // Implementar impressão
    mostrarAlerta('Funcionalidade de impressão será implementada em breve!', 'info');
}

// Inicializar página
document.addEventListener('DOMContentLoaded', function() {
    // Carregar dados iniciais
    carregarInstrutores();
    carregarCFCs();
    carregarUsuarios();
    
    // Adicionar listener para fechar modal ao clicar fora
    const modal = document.getElementById('modalInstrutor');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                fecharModalInstrutor();
            }
        });
        
        // Adicionar listener para tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.style.display === 'block') {
                fecharModalInstrutor();
            }
        });
    }
});

function carregarInstrutores() {
    // Carregar instrutores para a tabela
    fetch('/cfc-bom-conselho/admin/api/instrutores.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                preencherTabelaInstrutores(data.data);
                atualizarEstatisticas(data.data);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar instrutores:', error);
        });
}

function preencherTabelaInstrutores(instrutores) {
    const tbody = document.querySelector('#tabelaInstrutores tbody');
    tbody.innerHTML = '';
    
    instrutores.forEach(instrutor => {
        const row = document.createElement('tr');
        
        // Usar o nome correto (nome_usuario se nome estiver vazio)
        const nomeExibicao = instrutor.nome || instrutor.nome_usuario || 'N/A';
        const cfcExibicao = instrutor.cfc_nome || 'N/A';
        
        row.innerHTML = `
            <td>
                <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                        <span class="text-white fw-bold">${nomeExibicao.charAt(0).toUpperCase()}</span>
                    </div>
                    ${nomeExibicao}
                </div>
            </td>
            <td>${instrutor.email || 'N/A'}</td>
            <td>${cfcExibicao}</td>
            <td>${instrutor.credencial || 'N/A'}</td>
            <td>
                <span class="badge bg-info">${instrutor.categoria_habilitacao || 'N/A'}</span>
            </td>
            <td>
                <span class="badge ${instrutor.ativo ? 'bg-success' : 'bg-danger'}">
                    ${instrutor.ativo ? 'ATIVO' : 'INATIVO'}
                </span>
            </td>
            <td>
                <div class="btn-group-vertical btn-group-sm">
                    <button class="btn btn-primary btn-sm" onclick="editarInstrutor(${instrutor.id})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="excluirInstrutor(${instrutor.id})" title="Excluir">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function atualizarEstatisticas(instrutores) {
    const total = instrutores.length;
    const ativos = instrutores.filter(i => i.ativo).length;
    
    document.getElementById('totalInstrutores').textContent = total;
    document.getElementById('instrutoresAtivos').textContent = ativos;
}

function carregarCFCs() {
    console.log('🔍 Iniciando carregamento de CFCs...');
    
    // Carregar CFCs para o select
    fetch('/cfc-bom-conselho/admin/api/cfcs.php')
        .then(response => {
            console.log('📡 Resposta da API CFCs:', response.status, response.statusText);
            return response.json();
        })
        .then(data => {
            console.log('📊 Dados recebidos da API CFCs:', data);
            
            if (data.success) {
                const selectCFC = document.getElementById('cfc_id');
                const filtroCFC = document.getElementById('filtroCFC');
                
                console.log('🎯 Select CFC encontrado:', selectCFC);
                console.log('🎯 Filtro CFC encontrado:', filtroCFC);
                
                if (selectCFC) {
                    selectCFC.innerHTML = '<option value="">Selecione um CFC</option>';
                    
                    data.data.forEach(cfc => {
                        const option = document.createElement('option');
                        option.value = cfc.id;
                        option.textContent = cfc.nome;
                        selectCFC.appendChild(option);
                        console.log('✅ CFC adicionado:', cfc.nome);
                    });
                }
                
                // Também preencher o filtro
                if (filtroCFC) {
                    filtroCFC.innerHTML = '<option value="">Todos</option>';
                    data.data.forEach(cfc => {
                        const option = document.createElement('option');
                        option.value = cfc.id;
                        option.textContent = cfc.nome;
                        filtroCFC.appendChild(option);
                    });
                }
                
                console.log('✅ CFCs carregados com sucesso!');
            } else {
                console.error('❌ Erro na API CFCs:', data.error);
            }
        })
        .catch(error => {
            console.error('❌ Erro ao carregar CFCs:', error);
        });
}

function carregarUsuarios() {
    console.log('🔍 Iniciando carregamento de usuários...');
    
    // Carregar usuários para o select
    fetch('/cfc-bom-conselho/admin/api/usuarios.php')
        .then(response => {
            console.log('📡 Resposta da API Usuários:', response.status, response.statusText);
            return response.json();
        })
        .then(data => {
            console.log('📊 Dados recebidos da API Usuários:', data);
            
            if (data.success) {
                const selectUsuario = document.getElementById('usuario_id');
                console.log('🎯 Select Usuário encontrado:', selectUsuario);
                
                if (selectUsuario) {
                    selectUsuario.innerHTML = '<option value="">Selecione um usuário</option>';
                    
                    data.data.forEach(usuario => {
                        const option = document.createElement('option');
                        option.value = usuario.id;
                        option.textContent = `${usuario.nome} (${usuario.email})`;
                        selectUsuario.appendChild(option);
                        console.log('✅ Usuário adicionado:', usuario.nome);
                    });
                    
                    console.log('✅ Usuários carregados com sucesso!');
                } else {
                    console.error('❌ Select de usuário não encontrado!');
                }
            } else {
                console.error('❌ Erro na API Usuários:', data.error);
            }
        })
        .catch(error => {
            console.error('❌ Erro ao carregar usuários:', error);
        });
}
</script>

<!-- Fim da página de instrutores -->
