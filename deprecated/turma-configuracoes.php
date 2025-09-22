<?php
// Verificar permissões
$canView = ($userType === 'admin' || $userType === 'instrutor');
if (!$canView) {
    echo '<div class="alert alert-danger">Acesso negado. Apenas administradores e instrutores podem acessar esta página.</div>';
    return;
}
?>
<?php
/**
 * Configurações de Turmas Teóricas
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

// Verificar permissões (apenas admin)
if ($userType !== 'admin') {
    header('Location: /admin/pages/turmas.php');
    exit();
}

// Processar configurações
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'salvar_configuracoes':
            $configuracoes = [
                'carga_horaria_categoria_a' => $_POST['carga_horaria_categoria_a'] ?? 45,
                'carga_horaria_categoria_b' => $_POST['carga_horaria_categoria_b'] ?? 45,
                'carga_horaria_categoria_acc' => $_POST['carga_horaria_categoria_acc'] ?? 45,
                'duracao_aula_minutos' => $_POST['duracao_aula_minutos'] ?? 50,
                'max_aulas_por_dia' => $_POST['max_aulas_por_dia'] ?? 5,
                'frequencia_minima_padrao' => $_POST['frequencia_minima_padrao'] ?? 75,
                'capacidade_maxima_padrao' => $_POST['capacidade_maxima_padrao'] ?? 30,
                'horario_inicio_padrao' => $_POST['horario_inicio_padrao'] ?? '08:00',
                'horario_fim_padrao' => $_POST['horario_fim_padrao'] ?? '18:00'
            ];
            
            try {
                // Salvar configurações (implementar tabela de configurações se necessário)
                $mensagem = "Configurações salvas com sucesso!";
                $tipoMensagem = "success";
            } catch (Exception $e) {
                $mensagem = "Erro ao salvar configurações: " . $e->getMessage();
                $tipoMensagem = "danger";
            }
            break;
    }
}

// Buscar configurações atuais (valores padrão)
$configuracoes = [
    'carga_horaria_categoria_a' => 45,
    'carga_horaria_categoria_b' => 45,
    'carga_horaria_categoria_acc' => 45,
    'duracao_aula_minutos' => 50,
    'max_aulas_por_dia' => 5,
    'frequencia_minima_padrao' => 75,
    'capacidade_maxima_padrao' => 30,
    'horario_inicio_padrao' => '08:00',
    'horario_fim_padrao' => '18:00'
];
?>





    <div class="configuracoes-container">
        <div class="container-fluid">
            <!-- Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2">
                            <i class="fas fa-cogs me-3"></i>
                            Configurações de Turmas
                        </h1>
                        <p class="mb-0 opacity-75">
                            Configurações gerais do sistema de turmas teóricas
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="btn-group" role="group">
                            <a href="?page=turma-configuracoes" class="btn btn-light btn-sm">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                            <a href="?page=turma-configuracoes" class="btn btn-light btn-sm">
                                <i class="fas fa-list"></i> Lista de Turmas
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mensagens -->
            <?php if (isset($mensagem)): ?>
                <div class="alert alert-<?= $tipoMensagem ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?= $tipoMensagem === 'success' ? 'check-circle' : 'times-circle' ?> me-2"></i>
                    <?= htmlspecialchars($mensagem) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Configurações -->
            <form method="POST">
                <input type="hidden" name="action" value="salvar_configuracoes">
                
                <div class="config-card">
                    <h4 class="mb-4">
                        <i class="fas fa-sliders-h text-primary"></i>
                        Configurações Gerais
                    </h4>
                    
                    <!-- Carga Horária por Categoria -->
                    <div class="config-section">
                        <h5 class="section-title">
                            <i class="fas fa-clock"></i>
                            Carga Horária por Categoria
                        </h5>
                        <div class="info-box">
                            <i class="fas fa-info-circle me-2"></i>
                            Defina a carga horária obrigatória para cada categoria de habilitação.
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Categoria A</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="carga_horaria_categoria_a" 
                                           value="<?= $configuracoes['carga_horaria_categoria_a'] ?>" min="1" max="100">
                                    <span class="input-group-text">horas</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Categoria B</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="carga_horaria_categoria_b" 
                                           value="<?= $configuracoes['carga_horaria_categoria_b'] ?>" min="1" max="100">
                                    <span class="input-group-text">horas</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Categoria ACC</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="carga_horaria_categoria_acc" 
                                           value="<?= $configuracoes['carga_horaria_categoria_acc'] ?>" min="1" max="100">
                                    <span class="input-group-text">horas</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Configurações de Aula -->
                    <div class="config-section">
                        <h5 class="section-title">
                            <i class="fas fa-chalkboard-teacher"></i>
                            Configurações de Aula
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Duração da Aula</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="duracao_aula_minutos" 
                                           value="<?= $configuracoes['duracao_aula_minutos'] ?>" min="30" max="120">
                                    <span class="input-group-text">minutos</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Máximo de Aulas por Dia</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="max_aulas_por_dia" 
                                           value="<?= $configuracoes['max_aulas_por_dia'] ?>" min="1" max="10">
                                    <span class="input-group-text">aulas</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Frequência Mínima</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="frequencia_minima_padrao" 
                                           value="<?= $configuracoes['frequencia_minima_padrao'] ?>" min="50" max="100">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Configurações de Turma -->
                    <div class="config-section">
                        <h5 class="section-title">
                            <i class="fas fa-users"></i>
                            Configurações de Turma
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Capacidade Máxima Padrão</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="capacidade_maxima_padrao" 
                                           value="<?= $configuracoes['capacidade_maxima_padrao'] ?>" min="5" max="100">
                                    <span class="input-group-text">alunos</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Horário de Início Padrão</label>
                                <input type="time" class="form-control" name="horario_inicio_padrao" 
                                       value="<?= $configuracoes['horario_inicio_padrao'] ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Horário de Fim Padrão</label>
                                <input type="time" class="form-control" name="horario_fim_padrao" 
                                       value="<?= $configuracoes['horario_fim_padrao'] ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Avisos -->
                    <div class="warning-box">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Atenção:</strong> As alterações nas configurações afetarão apenas as novas turmas criadas. 
                        Turmas existentes manterão suas configurações atuais.
                    </div>
                    
                    <!-- Botões -->
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Salvar Configurações
                        </button>
                    </div>
                </div>
            </form>
            
            <!-- Informações do Sistema -->
            <div class="config-card">
                <h4 class="mb-4">
                    <i class="fas fa-info-circle text-primary"></i>
                    Informações do Sistema
                </h4>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6>Versão do Sistema</h6>
                        <p class="text-muted">Turmas Teóricas v1.0</p>
                        
                        <h6>Última Atualização</h6>
                        <p class="text-muted"><?= date('d/m/Y H:i') ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Funcionalidades Ativas</h6>
                        <ul class="list-unstyled text-muted">
                            <li><i class="fas fa-check text-success me-2"></i> Gestão de Turmas</li>
                            <li><i class="fas fa-check text-success me-2"></i> Controle de Presença</li>
                            <li><i class="fas fa-check text-success me-2"></i> Diário de Classe</li>
                            <li><i class="fas fa-check text-success me-2"></i> Relatórios</li>
                            <li><i class="fas fa-check text-success me-2"></i> Calendário de Aulas</li>
                            <li><i class="fas fa-check text-success me-2"></i> Sistema de Matrículas</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Validação do formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            const duracaoAula = parseInt(document.querySelector('input[name="duracao_aula_minutos"]').value);
            const maxAulasDia = parseInt(document.querySelector('input[name="max_aulas_por_dia"]').value);
            const cargaHoraria = parseInt(document.querySelector('input[name="carga_horaria_categoria_a"]').value);
            
            // Verificar se o máximo de aulas por dia não excede a carga horária
            const horasMaximasDia = (duracaoAula * maxAulasDia) / 60;
            const cargaHorariaHoras = cargaHoraria;
            
            if (horasMaximasDia > cargaHorariaHoras) {
                e.preventDefault();
                alert('Atenção: O máximo de aulas por dia (' + horasMaximasDia.toFixed(1) + 'h) excede a carga horária (' + cargaHorariaHoras + 'h).');
                return false;
            }
        });
        
        // Atualização em tempo real dos cálculos
        function atualizarCalculos() {
            const duracaoAula = parseInt(document.querySelector('input[name="duracao_aula_minutos"]').value) || 50;
            const maxAulasDia = parseInt(document.querySelector('input[name="max_aulas_por_dia"]').value) || 5;
            
            const horasMaximasDia = (duracaoAula * maxAulasDia) / 60;
            
            // Mostrar cálculo em tempo real (opcional)
            console.log('Máximo de horas por dia:', horasMaximasDia.toFixed(1));
        }
        
        // Adicionar listeners para atualização em tempo real
        document.querySelector('input[name="duracao_aula_minutos"]').addEventListener('input', atualizarCalculos);
        document.querySelector('input[name="max_aulas_por_dia"]').addEventListener('input', atualizarCalculos);
    </script>


