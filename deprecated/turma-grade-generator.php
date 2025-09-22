<?php
/**
 * Gerador Automático de Grade - Turmas Teóricas
 * Sistema de Turmas Teóricas - CFC Bom Conselho
 * 
 * @author Sistema CFC Bom Conselho
 * @version 1.0
 * @since 2024
 */

// Incluir dependências






$db = Database::getInstance();
$user = getCurrentUser();
$userType = $user['tipo'] ?? 'admin';
$userId = $user['id'] ?? null;

// Verificar permissões
$canView = ($userType === 'admin' || $userType === 'instrutor');
if (!$canView) {
    header('Location: /admin/pages/turmas.php');
    exit();
}

$turma_id = $_GET['turma_id'] ?? null;
$turma = null;

if ($turma_id) {
    try {
        $turma = $db->fetch("
            SELECT t.*, i.nome as instrutor_nome, c.nome as cfc_nome
            FROM turmas t
            LEFT JOIN instrutores i ON t.instrutor_id = i.id
            LEFT JOIN cfcs c ON t.cfc_id = c.id
            WHERE t.id = ?
        ", [$turma_id]);
    } catch (Exception $e) {
        $turma = null;
    }
}
?>





    <div class="grade-container">
        <div class="container-fluid">
            <!-- Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2">
                            <i class="fas fa-calendar-plus me-3"></i>
                            Gerador Automático de Grade
                        </h1>
                        <p class="mb-0 opacity-75">
                            Crie automaticamente a grade de aulas para sua turma
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="btn-group" role="group">
                            <a href="?page=turma-grade-generator" class="btn btn-light btn-sm">
                                <i class="fas fa-list"></i> Lista de Turmas
                            </a>
                            <?php if ($turma): ?>
                                <a href="?page=turma-grade-generator" class="btn btn-light btn-sm">
                                    <i class="fas fa-clipboard-check"></i> Chamada
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Seleção de Turma -->
            <?php if (!$turma): ?>
                <div class="grade-card">
                    <h5 class="mb-3">
                        <i class="fas fa-search text-primary"></i>
                        Selecionar Turma
                    </h5>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Turma:</label>
                            <select class="form-select" id="selecionarTurma">
                                <option value="">Selecione uma turma...</option>
                                <?php foreach ($turmas as $t): ?>
                                    <option value="<?= $t['id'] ?>">
                                        <?= htmlspecialchars($t['nome']) ?> 
                                        (<?= date('d/m/Y', strtotime($t['data_inicio'])) ?> - 
                                         <?= date('d/m/Y', strtotime($t['data_fim'])) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button class="btn btn-primary" onclick="carregarTurma()">
                                <i class="fas fa-arrow-right"></i> Carregar Turma
                            </button>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Informações da Turma -->
                <div class="grade-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="mb-2"><?= htmlspecialchars($turma['nome']) ?></h5>
                            <p class="text-muted mb-0">
                                <i class="fas fa-calendar me-2"></i>
                                <?= date('d/m/Y', strtotime($turma['data_inicio'])) ?> - 
                                <?= date('d/m/Y', strtotime($turma['data_fim'])) ?>
                                <span class="ms-3">
                                    <i class="fas fa-user me-2"></i>
                                    <?= htmlspecialchars($turma['instrutor_nome'] ?? 'Não definido') ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="?page=turma-grade-generator" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-arrow-left"></i> Trocar Turma
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Configurações do Gerador -->
                <div class="grade-card">
                    <h5 class="mb-3">
                        <i class="fas fa-cogs text-primary"></i>
                        Configurações da Grade
                    </h5>
                    
                    <form id="gradeForm">
                        <input type="hidden" id="turmaId" value="<?= $turma['id'] ?>">
                        
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Carga Horária Total</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="cargaHoraria" 
                                           value="<?= $turma['carga_horaria'] ?? 45 ?>" min="1" max="100">
                                    <span class="input-group-text">horas</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Duração da Aula</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="duracaoAula" 
                                           value="50" min="30" max="120">
                                    <span class="input-group-text">minutos</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Máximo de Aulas por Dia</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="maxAulasDia" 
                                           value="5" min="1" max="10">
                                    <span class="input-group-text">aulas</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Horário de Início</label>
                                <input type="time" class="form-control" id="horarioInicio" value="08:00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Horário de Fim</label>
                                <input type="time" class="form-control" id="horarioFim" value="18:00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Dias da Semana</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="segunda" checked>
                                    <label class="form-check-label" for="segunda">Segunda</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="2" id="terca" checked>
                                    <label class="form-check-label" for="terca">Terça</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="3" id="quarta" checked>
                                    <label class="form-check-label" for="quarta">Quarta</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="4" id="quinta" checked>
                                    <label class="form-check-label" for="quinta">Quinta</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="5" id="sexta" checked>
                                    <label class="form-check-label" for="sexta">Sexta</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Data de Início</label>
                                <input type="date" class="form-control" id="dataInicio" 
                                       value="<?= $turma['data_inicio'] ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Data de Fim</label>
                                <input type="date" class="form-control" id="dataFim" 
                                       value="<?= $turma['data_fim'] ?>">
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <button type="button" class="btn btn-primary btn-lg" onclick="gerarGrade()">
                                <i class="fas fa-magic"></i> Gerar Grade Automaticamente
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Preview da Grade -->
                <div class="preview-card">
                    <h5 class="mb-3">
                        <i class="fas fa-eye text-primary"></i>
                        Preview da Grade
                    </h5>
                    
                    <div id="gradePreview" class="grade-preview">
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-calendar-plus fa-3x mb-3"></i>
                            <p>Configure os parâmetros acima e clique em "Gerar Grade" para ver o preview</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner-border mb-3" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <h5>Gerando Grade...</h5>
            <p class="text-muted">Aguarde enquanto criamos sua grade de aulas</p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function carregarTurma() {
            const turmaId = document.getElementById('selecionarTurma').value;
            if (turmaId) {
                window.location.href = `turma-grade-generator.php?turma_id=${turmaId}`;
            } else {
                alert('Selecione uma turma primeiro');
            }
        }
        
        function gerarGrade() {
            const formData = {
                turma_id: document.getElementById('turmaId').value,
                carga_horaria: parseInt(document.getElementById('cargaHoraria').value),
                duracao_aula: parseInt(document.getElementById('duracaoAula').value),
                max_aulas_dia: parseInt(document.getElementById('maxAulasDia').value),
                horario_inicio: document.getElementById('horarioInicio').value,
                horario_fim: document.getElementById('horarioFim').value,
                data_inicio: document.getElementById('dataInicio').value,
                data_fim: document.getElementById('dataFim').value,
                dias_semana: getDiasSemanaSelecionados()
            };
            
            // Validar dados
            if (!formData.data_inicio || !formData.data_fim) {
                alert('Selecione as datas de início e fim');
                return;
            }
            
            if (formData.dias_semana.length === 0) {
                alert('Selecione pelo menos um dia da semana');
                return;
            }
            
            // Mostrar loading
            document.getElementById('loadingOverlay').style.display = 'flex';
            
            // Fazer requisição
            fetch('api/turma-grade-generator.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loadingOverlay').style.display = 'none';
                
                if (data.status === 'success') {
                    mostrarPreview(data.data.aulas_criadas);
                    alert('Grade gerada com sucesso!');
                } else {
                    alert('Erro: ' + data.message);
                }
            })
            .catch(error => {
                document.getElementById('loadingOverlay').style.display = 'none';
                console.error('Erro:', error);
                alert('Erro ao gerar grade');
            });
        }
        
        function getDiasSemanaSelecionados() {
            const dias = [];
            const checkboxes = document.querySelectorAll('input[type="checkbox"]:checked');
            checkboxes.forEach(checkbox => {
                dias.push(parseInt(checkbox.value));
            });
            return dias;
        }
        
        function mostrarPreview(aulas) {
            const preview = document.getElementById('gradePreview');
            
            if (aulas.length === 0) {
                preview.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                        <p>Nenhuma aula foi gerada. Verifique as configurações.</p>
                    </div>
                `;
                return;
            }
            
            let html = `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Total de ${aulas.length} aulas geradas</h6>
                    <button class="btn btn-success btn-sm" onclick="salvarGrade()">
                        <i class="fas fa-save"></i> Salvar Grade
                    </button>
                </div>
            `;
            
            aulas.forEach(aula => {
                const dataFormatada = new Date(aula.data_aula).toLocaleDateString('pt-BR');
                html += `
                    <div class="aula-item">
                        <div class="aula-header">
                            <span class="aula-numero">Aula ${aula.ordem}</span>
                            <span class="aula-data">${dataFormatada}</span>
                        </div>
                        <div class="aula-horario">
                            ${aula.hora_inicio} - ${aula.hora_fim}
                        </div>
                    </div>
                `;
            });
            
            preview.innerHTML = html;
        }
        
        function salvarGrade() {
            // A grade já foi salva no backend quando foi gerada
            alert('Grade salva com sucesso!');
            window.location.href = `turmas.php`;
        }
        
        // Atualizar preview em tempo real quando os parâmetros mudarem
        function atualizarPreview() {
            const cargaHoraria = parseInt(document.getElementById('cargaHoraria').value) || 45;
            const duracaoAula = parseInt(document.getElementById('duracaoAula').value) || 50;
            const totalAulas = Math.ceil((cargaHoraria * 60) / duracaoAula);
            
            // Mostrar cálculo básico
            const preview = document.getElementById('gradePreview');
            preview.innerHTML = `
                <div class="alert alert-info">
                    <h6><i class="fas fa-calculator me-2"></i>Cálculo da Grade</h6>
                    <p class="mb-1"><strong>Carga Horária:</strong> ${cargaHoraria} horas</p>
                    <p class="mb-1"><strong>Duração da Aula:</strong> ${duracaoAula} minutos</p>
                    <p class="mb-0"><strong>Total de Aulas:</strong> ${totalAulas} aulas</p>
                </div>
            `;
        }
        
        // Adicionar listeners para atualização em tempo real
        document.getElementById('cargaHoraria').addEventListener('input', atualizarPreview);
        document.getElementById('duracaoAula').addEventListener('input', atualizarPreview);
        
        // Inicializar preview
        if (document.getElementById('turmaId')) {
            atualizarPreview();
        }
    </script>


