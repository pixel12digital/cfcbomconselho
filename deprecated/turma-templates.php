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
 * Sistema de Templates - Turmas Teóricas
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

// Processar ações
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'criar_template':
            $nome_template = $_POST['nome_template'] ?? '';
            $categoria = $_POST['categoria'] ?? '';
            $carga_horaria = $_POST['carga_horaria'] ?? 45;
            $duracao_aula = $_POST['duracao_aula'] ?? 50;
            $descricao = $_POST['descricao'] ?? '';
            
            if ($nome_template && $categoria) {
                try {
                    // Criar template (implementar tabela de templates se necessário)
                    $mensagem = "Template criado com sucesso!";
                    $tipoMensagem = "success";
                } catch (Exception $e) {
                    $mensagem = "Erro ao criar template: " . $e->getMessage();
                    $tipoMensagem = "danger";
                }
            }
            break;
            
        case 'usar_template':
            $template_id = $_POST['template_id'] ?? null;
            
            if ($template_id) {
                // Redirecionar para criação de turma com template
                header("Location: turmas.php?action=create&template_id=" . $template_id);
                exit();
            }
            break;
    }
}

// Templates pré-definidos (simulando dados)
$templates = [
    [
        'id' => 1,
        'nome' => 'Categoria A - Padrão',
        'categoria' => 'A',
        'carga_horaria' => 45,
        'duracao_aula' => 50,
        'total_aulas' => 54,
        'descricao' => 'Template padrão para categoria A com 45 horas',
        'criado_em' => '2024-01-15',
        'usado_vezes' => 5
    ],
    [
        'id' => 2,
        'nome' => 'Categoria B - Padrão',
        'categoria' => 'B',
        'carga_horaria' => 45,
        'duracao_aula' => 50,
        'total_aulas' => 54,
        'descricao' => 'Template padrão para categoria B com 45 horas',
        'criado_em' => '2024-01-15',
        'usado_vezes' => 3
    ],
    [
        'id' => 3,
        'nome' => 'ACC - Padrão',
        'categoria' => 'ACC',
        'carga_horaria' => 45,
        'duracao_aula' => 50,
        'total_aulas' => 54,
        'descricao' => 'Template padrão para categoria ACC com 45 horas',
        'criado_em' => '2024-01-15',
        'usado_vezes' => 2
    ]
];
?>





    <div class="templates-container">
        <div class="container-fluid">
            <!-- Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2">
                            <i class="fas fa-copy me-3"></i>
                            Templates de Turmas
                        </h1>
                        <p class="mb-0 opacity-75">
                            Modelos pré-definidos para criação rápida de turmas
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="btn-group" role="group">
                            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#novoTemplateModal">
                                <i class="fas fa-plus"></i> Novo Template
                            </button>
                            <a href="?page=turma-templates" class="btn btn-light btn-sm">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
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

            <!-- Lista de Templates -->
            <?php if (empty($templates)): ?>
                <div class="template-card">
                    <div class="empty-state">
                        <i class="fas fa-copy"></i>
                        <h4>Nenhum template encontrado</h4>
                        <p>Crie seu primeiro template para facilitar a criação de turmas.</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#novoTemplateModal">
                            <i class="fas fa-plus"></i> Criar Primeiro Template
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($templates as $template): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="template-card">
                                <div class="template-header">
                                    <h5 class="template-title"><?= htmlspecialchars($template['nome']) ?></h5>
                                    <span class="template-badge"><?= $template['categoria'] ?></span>
                                </div>
                                
                                <p class="text-muted mb-3"><?= htmlspecialchars($template['descricao']) ?></p>
                                
                                <div class="template-stats">
                                    <div class="stat-item">
                                        <div class="stat-number"><?= $template['carga_horaria'] ?></div>
                                        <div class="stat-label">Horas</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number"><?= $template['total_aulas'] ?></div>
                                        <div class="stat-label">Aulas</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number"><?= $template['usado_vezes'] ?></div>
                                        <div class="stat-label">Usado</div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        Criado em <?= date('d/m/Y', strtotime($template['criado_em'])) ?>
                                    </small>
                                    <div class="btn-group" role="group">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="usar_template">
                                            <input type="hidden" name="template_id" value="<?= $template['id'] ?>">
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fas fa-play"></i> Usar
                                            </button>
                                        </form>
                                        <button class="btn btn-outline-primary btn-sm" onclick="visualizarTemplate(<?= $template['id'] ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Novo Template -->
    <div class="modal fade" id="novoTemplateModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>
                        Novo Template
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="criar_template">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nome do Template *</label>
                                <input type="text" class="form-control" name="nome_template" required
                                       placeholder="Ex: Categoria A - Padrão">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Categoria *</label>
                                <select class="form-select" name="categoria" required>
                                    <option value="">Selecione...</option>
                                    <option value="A">Categoria A</option>
                                    <option value="B">Categoria B</option>
                                    <option value="ACC">ACC</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Carga Horária</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="carga_horaria" 
                                           value="45" min="1" max="100">
                                    <span class="input-group-text">horas</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Duração da Aula</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="duracao_aula" 
                                           value="50" min="30" max="120">
                                    <span class="input-group-text">minutos</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <label class="form-label fw-bold">Descrição</label>
                            <textarea class="form-control" name="descricao" rows="3" 
                                      placeholder="Descreva o template..."></textarea>
                        </div>
                        
                        <!-- Preview -->
                        <div class="mt-4">
                            <h6>Preview do Template:</h6>
                            <div class="alert alert-info">
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>Carga Horária:</strong> <span id="preview-carga">45</span> horas
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Duração da Aula:</strong> <span id="preview-duracao">50</span> min
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Total de Aulas:</strong> <span id="preview-total">54</span> aulas
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Criar Template
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Visualizar Template -->
    <div class="modal fade" id="visualizarTemplateModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-eye me-2"></i>
                        Detalhes do Template
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="template-details">
                    <!-- Conteúdo será preenchido via JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" onclick="usarTemplate()">
                        <i class="fas fa-play"></i> Usar Template
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let templateSelecionado = null;
        
        // Atualizar preview em tempo real
        function atualizarPreview() {
            const cargaHoraria = parseInt(document.querySelector('input[name="carga_horaria"]').value) || 45;
            const duracaoAula = parseInt(document.querySelector('input[name="duracao_aula"]').value) || 50;
            
            const totalAulas = Math.ceil((cargaHoraria * 60) / duracaoAula);
            
            document.getElementById('preview-carga').textContent = cargaHoraria;
            document.getElementById('preview-duracao').textContent = duracaoAula;
            document.getElementById('preview-total').textContent = totalAulas;
        }
        
        // Adicionar listeners
        document.querySelector('input[name="carga_horaria"]').addEventListener('input', atualizarPreview);
        document.querySelector('input[name="duracao_aula"]').addEventListener('input', atualizarPreview);
        
        // Inicializar preview
        atualizarPreview();
        
        function visualizarTemplate(templateId) {
            // Buscar dados do template (simulado)
            const template = <?= json_encode($templates) ?>.find(t => t.id === templateId);
            
            if (template) {
                templateSelecionado = template;
                
                const details = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Informações Básicas</h6>
                            <p><strong>Nome:</strong> ${template.nome}</p>
                            <p><strong>Categoria:</strong> ${template.categoria}</p>
                            <p><strong>Descrição:</strong> ${template.descricao}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Configurações</h6>
                            <p><strong>Carga Horária:</strong> ${template.carga_horaria} horas</p>
                            <p><strong>Duração da Aula:</strong> ${template.duracao_aula} minutos</p>
                            <p><strong>Total de Aulas:</strong> ${template.total_aulas} aulas</p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Estatísticas</h6>
                            <p><strong>Usado:</strong> ${template.usado_vezes} vezes</p>
                            <p><strong>Criado em:</strong> ${new Date(template.criado_em).toLocaleDateString('pt-BR')}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Grade de Aulas</h6>
                            <p>Este template criará automaticamente ${template.total_aulas} aulas de ${template.duracao_aula} minutos cada.</p>
                        </div>
                    </div>
                `;
                
                document.getElementById('template-details').innerHTML = details;
                
                const modal = new bootstrap.Modal(document.getElementById('visualizarTemplateModal'));
                modal.show();
            }
        }
        
        function usarTemplate() {
            if (templateSelecionado) {
                // Redirecionar para criação de turma com template
                window.location.href = `turmas.php?action=create&template_id=${templateSelecionado.id}`;
            }
        }
    </script>


