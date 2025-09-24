<?php
// Verificar se estamos sendo incluídos pelo sistema de roteamento do admin
if (!defined('ADMIN_ROUTING')) {
    // Se acessado diretamente, redirecionar para o sistema de roteamento
    header('Location: ../index.php?page=instrutores');
    exit;
}

$pageTitle = 'Gestão de Instrutores';
?>

<!-- Incluir CSS do modal -->
<link rel="stylesheet" href="assets/css/modal-instrutores.css">

<style>
/* CSS Responsivo para Instrutores */
@media (max-width: 768px) {
    /* Header responsivo */
    .d-flex.justify-content-between {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 1rem;
    }
    
    .d-flex.justify-content-between h1 {
        text-align: center;
        margin-bottom: 0.5rem !important;
    }
    
    .d-flex.justify-content-between .btn {
        width: 100%;
        justify-content: center;
    }
    
    /* Filtros responsivos */
    .card-body .row {
        margin: 0 !important;
    }
    
    .card-body .col-md-3 {
        margin-bottom: 1rem;
    }
    
    /* Estatísticas responsivas */
    .row.mb-4 .col-md-3 {
        margin-bottom: 1rem;
    }
    
    /* Esconder tabela no mobile */
    .table-responsive {
        display: none !important;
    }
    
    /* Mostrar cards no mobile - FORÇAR EXIBIÇÃO */
    .mobile-instrutor-cards {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    /* Forçar exibição dos cards mobile */
    #mobileInstrutorCards {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        min-height: 200px !important;
    }
    
    /* Garantir que cards individuais sejam visíveis */
    .mobile-instrutor-card {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        margin-bottom: 1rem !important;
    }
}

@media (min-width: 769px) {
    /* Esconder cards no desktop */
    .mobile-instrutor-cards {
        display: none !important;
    }
    
    #mobileInstrutorCards {
        display: none !important;
    }
}

/* Cards mobile */
.mobile-instrutor-cards {
    display: none;
}

.mobile-instrutor-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.mobile-instrutor-header {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
}

.mobile-instrutor-avatar {
    margin-right: 1rem;
}

.mobile-instrutor-info {
    flex: 1;
}

.mobile-instrutor-title {
    font-weight: bold;
    font-size: 1.1rem;
    margin-bottom: 0.25rem;
}

.mobile-instrutor-email {
    color: #6c757d;
    font-size: 0.9rem;
}

.mobile-instrutor-status {
    margin-left: auto;
}

.mobile-instrutor-body {
    margin-bottom: 1rem;
}

.mobile-instrutor-field {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    padding: 0.25rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.mobile-instrutor-label {
    font-weight: 500;
    color: #6c757d;
    font-size: 0.9rem;
}

.mobile-instrutor-value {
    font-size: 0.9rem;
}

.mobile-instrutor-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
    flex-wrap: wrap;
}

.mobile-instrutor-actions .btn {
    width: 40px;
    height: 40px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    border-radius: 0.375rem;
}
</style>

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
                    
                    <!-- Cards Mobile -->
                    <div class="mobile-instrutor-cards" id="mobileInstrutorCards">
                        <!-- Preencher via JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Customizado para Cadastro/Edição de Instrutor -->
<div id="modalInstrutor" class="custom-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 9999; overflow: auto;">
    <div class="custom-modal-dialog" style="position: relative; width: 95%; max-width: 1200px; margin: 20px auto; background: white; border-radius: 0.5rem; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15); overflow: hidden; display: block;">
        <form id="formInstrutor" enctype="multipart/form-data" onsubmit="return false;">
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
                    
                    <!-- Seção 0: Foto do Instrutor -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem; margin-bottom: 0.5rem !important;">
                                <i class="fas fa-camera me-1"></i>Foto do Instrutor
                            </h6>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-2">
                                <label for="foto" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Foto (Opcional)</label>
                                <input type="file" class="form-control" id="foto" name="foto" accept="image/*" 
                                       style="padding: 0.4rem; font-size: 0.85rem;" onchange="previewFoto(this)">
                                <small class="text-muted" style="font-size: 0.75rem;">📷 JPG, PNG, GIF até 2MB</small>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="text-center">
                                <div id="preview-container" style="display: none;">
                                    <img id="foto-preview" src="" alt="Preview da foto" 
                                         style="max-width: 150px; max-height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid #dee2e6;">
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removerFoto()">
                                            <i class="fas fa-trash"></i> Remover
                                        </button>
                                    </div>
                                </div>
                                <div id="placeholder-foto" class="text-muted" style="font-size: 0.8rem;">
                                    <i class="fas fa-user-circle fa-3x"></i><br>
                                    Nenhuma foto selecionada
                                </div>
                            </div>
                        </div>
                    </div>
                    
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
                                     <input type="text" class="form-control" id="data_nascimento" name="data_nascimento" required 
                                            placeholder="dd/mm/aaaa" style="padding: 0.4rem; font-size: 0.85rem;">
                                     <small class="text-muted" style="font-size: 0.75rem;">💡 Digite dd/mm/aaaa OU clique no ícone do calendário</small>
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
                         
                         <!-- Seção: Dados de Acesso -->
                         <div class="row mb-2">
                             <div class="col-12">
                                 <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem; margin-bottom: 0.3rem !important;">
                                     <i class="fas fa-user me-1"></i>Dados de Acesso
                                 </h6>
                             </div>
                                                           <div class="col-md-6">
                                  <div class="mb-1">
                                      <label for="usuario_id" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Usuário (Opcional)</label>
                                      <select id="usuario_id" name="usuario_id" class="form-select" style="padding: 0.4rem; font-size: 0.85rem;">
                                          <option value="">Selecione um usuário (opcional)</option>
                                          <!-- Preencher com usuários existentes -->
                                      </select>
                                      <small class="form-text text-muted">Associar a um usuário do sistema (opcional)</small>
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
                         
                         <!-- Seção: Dados do Instrutor -->
                         <div class="row mb-2">
                             <div class="col-12">
                                 <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem; margin-bottom: 0.3rem !important;">
                                     <i class="fas fa-id-card me-1"></i>Dados do Instrutor
                                 </h6>
                             </div>
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
                                     <div class="col-md-1 col-sm-2 col-3">
                                         <div class="form-check">
                                             <input class="form-check-input" type="checkbox" name="dias_semana[]" value="segunda" id="segunda" style="margin-top: 0.2rem;">
                                             <label class="form-check-label" for="segunda" style="font-size: 0.8rem;">Seg</label>
                                         </div>
                                     </div>
                                     <div class="col-md-1 col-sm-2 col-3">
                                         <div class="form-check">
                                             <input class="form-check-input" type="checkbox" name="dias_semana[]" value="terca" id="terca" style="margin-top: 0.2rem;">
                                             <label class="form-check-label" for="terca" style="font-size: 0.8rem;">Ter</label>
                                         </div>
                                     </div>
                                     <div class="col-md-1 col-sm-2 col-3">
                                         <div class="form-check">
                                             <input class="form-check-input" type="checkbox" name="dias_semana[]" value="quarta" id="quarta" style="margin-top: 0.2rem;">
                                             <label class="form-check-label" for="quarta" style="font-size: 0.8rem;">Qua</label>
                                         </div>
                                     </div>
                                     <div class="col-md-1 col-sm-2 col-3">
                                         <div class="form-check">
                                             <input class="form-check-input" type="checkbox" name="dias_semana[]" value="quinta" id="quinta" style="margin-top: 0.2rem;">
                                             <label class="form-check-label" for="quinta" style="font-size: 0.8rem;">Qui</label>
                                         </div>
                                     </div>
                                     <div class="col-md-1 col-sm-2 col-3">
                                         <div class="form-check">
                                             <input class="form-check-input" type="checkbox" name="dias_semana[]" value="sexta" id="sexta" style="margin-top: 0.2rem;">
                                             <label class="form-check-label" for="sexta" style="font-size: 0.8rem;">Sex</label>
                                         </div>
                                     </div>
                                     <div class="col-md-1 col-sm-2 col-3">
                                         <div class="form-check">
                                             <input class="form-check-input" type="checkbox" name="dias_semana[]" value="sabado" id="sabado" style="margin-top: 0.2rem;">
                                             <label class="form-check-label" for="sabado" style="font-size: 0.8rem;">Sáb</label>
                                         </div>
                                     </div>
                                     <div class="col-md-1 col-sm-2 col-3">
                                         <div class="form-check">
                                             <input class="form-check-input" type="checkbox" name="dias_semana[]" value="domingo" id="domingo" style="margin-top: 0.2rem;">
                                             <label class="form-check-label" for="domingo" style="font-size: 0.8rem;">Dom</label>
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
                             <div class="col-md-6">
                                 <div class="mb-1">
                                     <label for="validade_credencial" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Validade da Habilitação</label>
                                     <input type="text" class="form-control" id="validade_credencial" name="validade_credencial" 
                                            placeholder="dd/mm/aaaa" style="padding: 0.4rem; font-size: 0.85rem;">
                                     <small class="text-muted" style="font-size: 0.75rem;">💡 Digite dd/mm/aaaa OU clique no calendário</small>
                                 </div>
                             </div>
                             <div class="col-md-6">
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
                    <button type="submit" class="btn btn-primary" id="btnSalvarInstrutor" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                        <i class="fas fa-save me-1"></i>Salvar Instrutor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript da página de instrutores - Carregado externamente para garantir ordem correta -->
<script src="assets/js/instrutores-page.js"></script>

<script>
// Garantir que o modal não abra automaticamente
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modalInstrutor');
    if (modal) {
        // Forçar fechamento do modal se estiver aberto
        modal.style.setProperty('display', 'none', 'important');
        modal.classList.remove('show');
        console.log('✅ Modal de instrutor fechado automaticamente');
    }
});
</script>
<!-- instrutores.js já é carregado no index.php, não precisa carregar novamente -->

<!-- Fim da página de instrutores -->
