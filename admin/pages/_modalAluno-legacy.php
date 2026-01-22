<!-- Modal Customizado para Cadastro/Edição de Aluno -->
<!-- BACKUP - Este arquivo contém o código original do modal antes da simplificação -->
<!-- Não incluir este arquivo em lugar nenhum - apenas para referência -->

<div id="modalAluno" class="custom-modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="custom-modal-dialog">
        <div class="custom-modal-content">
            <form id="formAluno" method="POST" class="aluno-modal-form">
                <div class="aluno-modal-header modal-header">
                    <h2 class="aluno-modal-title" id="modalTitle">
                        <i class="fas fa-user-graduate me-2" aria-hidden="true"></i>
                        <span>Novo Aluno</span>
                    </h2>
                    <button type="button" class="btn-close aluno-modal-close" onclick="fecharModalAluno()" aria-label="Fechar modal"></button>
                </div>
                <input type="hidden" name="acao" id="acaoAluno" value="criar">
                <input type="hidden" name="aluno_id" id="aluno_id_hidden" value="">

                <div class="aluno-modal-tabs">
                    <ul class="nav nav-tabs aluno-tabs" id="alunoTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="dados-tab" data-bs-toggle="tab" data-bs-target="#dados" type="button" role="tab">
                                <i class="fas fa-user"></i>
                                <span>Dados</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="matricula-tab" data-bs-toggle="tab" data-bs-target="#matricula" type="button" role="tab">
                                <i class="fas fa-graduation-cap"></i>
                                <span>Matrícula</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" id="financeiro-tab-container" style="display: none;">
                            <button class="nav-link" id="financeiro-tab" data-bs-toggle="tab" data-bs-target="#financeiro" type="button" role="tab">
                                <i class="fas fa-dollar-sign"></i>
                                <span>Financeiro</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" id="documentos-tab-container" style="display: none;">
                            <button class="nav-link" id="documentos-tab" data-bs-toggle="tab" data-bs-target="#documentos" type="button" role="tab">
                                <i class="fas fa-file-alt"></i>
                                <span>Documentos</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="agenda-tab" data-bs-toggle="tab" data-bs-target="#agenda" type="button" role="tab">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Agenda</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="teorico-tab" data-bs-toggle="tab" data-bs-target="#teorico" type="button" role="tab">
                                <i class="fas fa-chalkboard-teacher"></i>
                                <span>Teórico</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="historico-tab" data-bs-toggle="tab" data-bs-target="#historico" type="button" role="tab">
                                <i class="fas fa-history"></i>
                                <span>Histórico</span>
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="aluno-modal-body modal-body">
                    <div class="aluno-modal-panel">
                    <div class="tab-content aluno-tab-content" id="alunoTabsContent">
                        <!-- Conteúdo completo das abas removido para simplificação -->
                        <!-- Ver arquivo original alunos.php para conteúdo completo -->
                    </div>
                </div>
                </div>
                <div class="aluno-modal-footer modal-footer">
                    <button type="button" class="btn aluno-btn-cancelar" onclick="fecharModalAluno()">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary aluno-btn-salvar" id="btnSalvarAluno">
                        <i class="fas fa-save me-1"></i>Salvar Aluno
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

