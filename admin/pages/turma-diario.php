<?php
/**
 * Interface de Diário de Classe - Turmas Teóricas
 * Sistema de Turmas Teóricas - CFC Bom Conselho
 * ETAPA 1.4: Diário de Classe
 * 
 * @author Sistema CFC Bom Conselho
 * @version 1.0
 * @since 2024
 */

// Incluir dependências
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar autenticação
if (!isLoggedIn()) {
    header('Location: /login.php');
    exit();
}

$db = Database::getInstance();
$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'] ?? 'aluno';

// Verificar permissões
$canEdit = ($userType === 'admin' || $userType === 'instrutor');

// Parâmetros da URL
$turmaId = $_GET['turma_id'] ?? null;
$aulaId = $_GET['aula_id'] ?? null;

if (!$turmaId) {
    header('Location: /admin/pages/turmas.php');
    exit();
}

// Buscar dados da turma
$turma = $db->fetch("
    SELECT 
        t.*,
        i.nome as instrutor_nome,
        c.nome as cfc_nome
    FROM turmas t
    LEFT JOIN instrutores i ON t.instrutor_id = i.id
    LEFT JOIN cfcs c ON t.cfc_id = c.id
    WHERE t.id = ?
", [$turmaId]);

if (!$turma) {
    header('Location: /admin/pages/turmas.php');
    exit();
}

// Verificar se usuário tem permissão para esta turma
if ($userType === 'instrutor' && $turma['instrutor_id'] != $userId) {
    $canEdit = false;
}

// Buscar aulas da turma
$aulas = $db->fetchAll("
    SELECT 
        ta.*,
        td.id as diario_id,
        td.conteudo_ministrado,
        td.observacoes,
        td.created_at as diario_criado_em,
        td.updated_at as diario_atualizado_em
    FROM turma_aulas ta
    LEFT JOIN turma_diario td ON ta.id = td.turma_aula_id
    WHERE ta.turma_id = ?
    ORDER BY ta.ordem ASC
", [$turmaId]);

// Se não especificou aula, usar a primeira
if (!$aulaId && !empty($aulas)) {
    $aulaId = $aulas[0]['id'];
}

// Buscar dados da aula atual
$aulaAtual = null;
$diarioAtual = null;
if ($aulaId) {
    foreach ($aulas as $aula) {
        if ($aula['id'] == $aulaId) {
            $aulaAtual = $aula;
            if ($aula['diario_id']) {
                $diarioAtual = [
                    'id' => $aula['diario_id'],
                    'conteudo_ministrado' => $aula['conteudo_ministrado'],
                    'observacoes' => $aula['observacoes'],
                    'created_at' => $aula['diario_criado_em'],
                    'updated_at' => $aula['diario_atualizado_em']
                ];
            }
            break;
        }
    }
}

// Buscar anexos do diário atual
$anexosAtual = [];
if ($diarioAtual) {
    $diarioCompleto = $db->fetch("
        SELECT anexos FROM turma_diario WHERE id = ?
    ", [$diarioAtual['id']]);
    
    if ($diarioCompleto && $diarioCompleto['anexos']) {
        $anexosAtual = json_decode($diarioCompleto['anexos'], true) ?: [];
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diário de Classe - <?= htmlspecialchars($turma['nome']) ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        .diario-container {
            background: #f8f9fa;
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .diario-header {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            padding: 20px;
        }
        
        .diario-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .aula-selector {
            background: #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .conteudo-editor {
            min-height: 300px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
        }
        
        .anexos-container {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        
        .anexos-container:hover {
            border-color: #007bff;
            background: #f8f9ff;
        }
        
        .anexos-container.dragover {
            border-color: #007bff;
            background: #e3f2fd;
        }
        
        .anexo-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 10px;
            margin: 5px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .anexo-item:hover {
            background: #e9ecef;
        }
        
        .btn-anexo {
            margin: 2px;
        }
        
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
        
        .status-turma {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 500;
        }
        
        .status-turma.ativo {
            background: #d4edda;
            color: #155724;
        }
        
        .status-turma.agendado {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-turma.encerrado {
            background: #f8d7da;
            color: #721c24;
        }
        
        .auditoria-info {
            font-size: 0.8em;
            color: #6c757d;
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .btn-salvar {
            position: sticky;
            bottom: 20px;
            z-index: 1000;
        }
        
        .conteudo-preview {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            min-height: 200px;
            white-space: pre-wrap;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
        }
        
        .anexo-preview {
            max-width: 100px;
            max-height: 100px;
            border-radius: 5px;
            margin: 5px;
        }
        
        .anexo-link {
            color: #007bff;
            text-decoration: none;
        }
        
        .anexo-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="diario-container">
        <div class="container-fluid">
            <!-- Header do Diário -->
            <div class="diario-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-1">
                            <i class="fas fa-book-open text-primary"></i>
                            Diário de Classe - <?= htmlspecialchars($turma['nome']) ?>
                        </h2>
                        <p class="text-muted mb-2">
                            <i class="fas fa-user-tie"></i> <?= htmlspecialchars($turma['instrutor_nome']) ?> |
                            <i class="fas fa-building"></i> <?= htmlspecialchars($turma['cfc_nome']) ?> |
                            <span class="status-turma <?= $turma['status'] ?>"><?= ucfirst($turma['status']) ?></span>
                        </p>
                        <?php if ($aulaAtual): ?>
                        <p class="text-muted mb-0">
                            <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($aulaAtual['data_aula'])) ?> |
                            <i class="fas fa-clock"></i> <?= $aulaAtual['duracao_minutos'] ?> min |
                            <i class="fas fa-book"></i> <?= htmlspecialchars($aulaAtual['nome_aula']) ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-end">
                        <!-- Links Contextuais -->
                        <div class="btn-group" role="group">
                            <a href="turma-chamada.php?turma_id=<?= $turmaId ?>&aula_id=<?= $aulaId ?>" 
                               class="btn btn-outline-primary btn-sm" title="Ir para Chamada desta aula">
                                <i class="fas fa-clipboard-check"></i> Chamada
                            </a>
                            <?php if ($userType === 'admin'): ?>
                                <a href="turma-relatorios.php?turma_id=<?= $turmaId ?>" 
                                   class="btn btn-outline-success btn-sm" title="Relatórios da turma">
                                    <i class="fas fa-chart-bar"></i> Relatórios
                                </a>
                            <?php endif; ?>
                            <a href="turmas.php" class="btn btn-outline-secondary btn-sm" title="Voltar para Gestão de Turmas">
                                <i class="fas fa-arrow-left"></i> Voltar
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Seletor de Aulas -->
            <?php if (count($aulas) > 1): ?>
            <div class="aula-selector">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Aula:</label>
                        <select class="form-select" id="aulaSelector" onchange="trocarAula()">
                            <?php foreach ($aulas as $aula): ?>
                            <option value="<?= $aula['id'] ?>" <?= $aula['id'] == $aulaId ? 'selected' : '' ?>>
                                Aula <?= $aula['ordem'] ?> - <?= htmlspecialchars($aula['nome_aula']) ?>
                                (<?= date('d/m/Y', strtotime($aula['data_aula'])) ?>)
                                <?= $aula['diario_id'] ? '📝' : '📄' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div class="btn-group" role="group">
                            <button class="btn btn-outline-primary btn-sm" onclick="navegarAula('anterior')">
                                <i class="fas fa-chevron-left"></i> Anterior
                            </button>
                            <button class="btn btn-outline-primary btn-sm" onclick="navegarAula('proxima')">
                                Próxima <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Conteúdo Principal -->
            <div class="row">
                <div class="col-md-8">
                    <!-- Editor de Conteúdo -->
                    <div class="diario-card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-edit"></i> Conteúdo Ministrado
                                <?php if (!$canEdit): ?>
                                <span class="badge bg-warning ms-2">Somente Leitura</span>
                                <?php endif; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($canEdit): ?>
                            <textarea class="form-control conteudo-editor" id="conteudoEditor" 
                                      placeholder="Descreva o conteúdo ministrado nesta aula..."><?= htmlspecialchars($diarioAtual['conteudo_ministrado'] ?? '') ?></textarea>
                            <?php else: ?>
                            <div class="conteudo-preview">
                                <?= htmlspecialchars($diarioAtual['conteudo_ministrado'] ?? 'Nenhum conteúdo registrado para esta aula.') ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Campo de Observações -->
                    <div class="diario-card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-sticky-note"></i> Observações Gerais
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($canEdit): ?>
                            <textarea class="form-control" id="observacoesEditor" rows="3"
                                      placeholder="Observações adicionais sobre a aula..."><?= htmlspecialchars($diarioAtual['observacoes'] ?? '') ?></textarea>
                            <?php else: ?>
                            <div class="conteudo-preview">
                                <?= htmlspecialchars($diarioAtual['observacoes'] ?? 'Nenhuma observação registrada.') ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Anexos -->
                    <div class="diario-card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-paperclip"></i> Anexos
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($canEdit): ?>
                            <!-- Upload de Anexos -->
                            <div class="anexos-container" id="anexosContainer" 
                                 ondrop="dropHandler(event)" ondragover="dragOverHandler(event)" ondragleave="dragLeaveHandler(event)">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-3">
                                    Arraste arquivos aqui ou clique para selecionar
                                </p>
                                <input type="file" id="fileInput" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.txt" 
                                       style="display: none;" onchange="handleFileSelect(event)">
                                <button class="btn btn-outline-primary" onclick="document.getElementById('fileInput').click()">
                                    <i class="fas fa-plus"></i> Adicionar Anexos
                                </button>
                            </div>

                            <!-- Lista de Anexos -->
                            <div id="anexosList" class="mt-3">
                                <?php foreach ($anexosAtual as $anexo): ?>
                                <div class="anexo-item" data-anexo-id="<?= $anexo['id'] ?>">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-file-<?= $anexo['tipo'] === 'pdf' ? 'pdf' : ($anexo['tipo'] === 'image' ? 'image' : 'alt') ?> me-2"></i>
                                        <span><?= htmlspecialchars($anexo['nome']) ?></span>
                                        <small class="text-muted ms-2">(<?= $anexo['tamanho'] ?>)</small>
                                    </div>
                                    <div>
                                        <button class="btn btn-sm btn-outline-danger btn-anexo" onclick="removerAnexo('<?= $anexo['id'] ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <!-- Visualização de Anexos -->
                            <div id="anexosList">
                                <?php if (empty($anexosAtual)): ?>
                                <p class="text-muted">Nenhum anexo registrado para esta aula.</p>
                                <?php else: ?>
                                    <?php foreach ($anexosAtual as $anexo): ?>
                                    <div class="anexo-item">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-file-<?= $anexo['tipo'] === 'pdf' ? 'pdf' : ($anexo['tipo'] === 'image' ? 'image' : 'alt') ?> me-2"></i>
                                            <a href="<?= htmlspecialchars($anexo['url']) ?>" target="_blank" class="anexo-link">
                                                <?= htmlspecialchars($anexo['nome']) ?>
                                            </a>
                                            <small class="text-muted ms-2">(<?= $anexo['tamanho'] ?>)</small>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Informações da Aula -->
                    <div class="diario-card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle"></i> Informações da Aula
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($aulaAtual): ?>
                            <div class="mb-3">
                                <strong>Nome da Aula:</strong><br>
                                <?= htmlspecialchars($aulaAtual['nome_aula']) ?>
                            </div>
                            <div class="mb-3">
                                <strong>Data:</strong><br>
                                <?= date('d/m/Y', strtotime($aulaAtual['data_aula'])) ?>
                            </div>
                            <div class="mb-3">
                                <strong>Duração:</strong><br>
                                <?= $aulaAtual['duracao_minutos'] ?> minutos
                            </div>
                            <div class="mb-3">
                                <strong>Tipo de Conteúdo:</strong><br>
                                <span class="badge bg-primary"><?= ucfirst($aulaAtual['tipo_conteudo']) ?></span>
                            </div>
                            <div class="mb-3">
                                <strong>Status:</strong><br>
                                <span class="badge bg-<?= $aulaAtual['status'] === 'concluida' ? 'success' : 'warning' ?>">
                                    <?= ucfirst($aulaAtual['status']) ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Auditoria -->
                    <?php if ($diarioAtual): ?>
                    <div class="diario-card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-history"></i> Histórico
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="auditoria-info">
                                <strong>Criado em:</strong><br>
                                <?= date('d/m/Y H:i', strtotime($diarioAtual['created_at'])) ?>
                            </div>
                            <?php if ($diarioAtual['updated_at']): ?>
                            <div class="auditoria-info">
                                <strong>Última atualização:</strong><br>
                                <?= date('d/m/Y H:i', strtotime($diarioAtual['updated_at'])) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Ações -->
                    <?php if ($canEdit): ?>
                    <div class="diario-card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-tools"></i> Ações
                            </h5>
                        </div>
                        <div class="card-body">
                            <button class="btn btn-primary w-100 mb-2" onclick="salvarDiario()">
                                <i class="fas fa-save"></i> Salvar Diário
                            </button>
                            <?php if ($diarioAtual): ?>
                            <button class="btn btn-outline-danger w-100" onclick="excluirDiario()">
                                <i class="fas fa-trash"></i> Excluir Diário
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Variáveis globais
        let turmaId = <?= $turmaId ?>;
        let aulaId = <?= $aulaId ?>;
        let canEdit = <?= $canEdit ? 'true' : 'false' ?>;
        let alteracoesPendentes = false;
        let anexos = <?= json_encode($anexosAtual) ?>;

        // Função para mostrar toast
        function mostrarToast(mensagem, tipo = 'success') {
            const toastContainer = document.getElementById('toastContainer');
            const toastId = 'toast-' + Date.now();
            
            const toastHtml = `
                <div class="toast" id="${toastId}" role="alert">
                    <div class="toast-header">
                        <i class="fas fa-${tipo === 'success' ? 'check-circle text-success' : 'exclamation-triangle text-warning'} me-2"></i>
                        <strong class="me-auto">Sistema</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                    </div>
                    <div class="toast-body">${mensagem}</div>
                </div>
            `;
            
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            
            const toast = new bootstrap.Toast(document.getElementById(toastId));
            toast.show();
            
            // Remover toast após 5 segundos
            setTimeout(() => {
                const toastElement = document.getElementById(toastId);
                if (toastElement) {
                    toastElement.remove();
                }
            }, 5000);
        }

        // Função para salvar diário
        function salvarDiario() {
            if (!canEdit) {
                mostrarToast('Você não tem permissão para editar o diário', 'error');
                return;
            }

            const conteudo = document.getElementById('conteudoEditor').value;
            const observacoes = document.getElementById('observacoesEditor').value;

            if (!conteudo.trim()) {
                mostrarToast('Conteúdo ministrado é obrigatório', 'error');
                return;
            }

            const dados = {
                turma_id: turmaId,
                turma_aula_id: aulaId,
                conteudo_ministrado: conteudo,
                observacoes: observacoes,
                anexos: anexos
            };

            const url = <?= $diarioAtual ? "'/admin/api/turma-diario.php?id=" . $diarioAtual['id'] . "'" : "'/admin/api/turma-diario.php'" ?>;
            const method = <?= $diarioAtual ? "'PUT'" : "'POST'" ?>;

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(dados)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarToast('Diário salvo com sucesso!');
                    alteracoesPendentes = false;
                    // Recarregar página para mostrar dados atualizados
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    mostrarToast('Erro ao salvar diário: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarToast('Erro de conexão. Tente novamente.', 'error');
            });
        }

        // Função para excluir diário
        function excluirDiario() {
            if (!canEdit) {
                mostrarToast('Você não tem permissão para excluir o diário', 'error');
                return;
            }

            if (!confirm('Tem certeza que deseja excluir este diário? Esta ação não pode ser desfeita.')) {
                return;
            }

            fetch(`/admin/api/turma-diario.php?id=<?= $diarioAtual['id'] ?>`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarToast('Diário excluído com sucesso!');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    mostrarToast('Erro ao excluir diário: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarToast('Erro de conexão. Tente novamente.', 'error');
            });
        }

        // Função para trocar de aula
        function trocarAula() {
            const novoAulaId = document.getElementById('aulaSelector').value;
            if (novoAulaId != aulaId) {
                window.location.href = `?turma_id=${turmaId}&aula_id=${novoAulaId}`;
            }
        }

        // Função para navegar entre aulas
        function navegarAula(direcao) {
            const selector = document.getElementById('aulaSelector');
            const opcoes = Array.from(selector.options);
            const indiceAtual = opcoes.findIndex(opcao => opcao.value == aulaId);
            
            let novoIndice;
            if (direcao === 'anterior') {
                novoIndice = indiceAtual - 1;
            } else {
                novoIndice = indiceAtual + 1;
            }
            
            if (novoIndice >= 0 && novoIndice < opcoes.length) {
                selector.value = opcoes[novoIndice].value;
                trocarAula();
            }
        }

        // Funções para upload de anexos
        function dragOverHandler(ev) {
            ev.preventDefault();
            ev.currentTarget.classList.add('dragover');
        }

        function dragLeaveHandler(ev) {
            ev.currentTarget.classList.remove('dragover');
        }

        function dropHandler(ev) {
            ev.preventDefault();
            ev.currentTarget.classList.remove('dragover');
            
            const files = ev.dataTransfer.files;
            handleFiles(files);
        }

        function handleFileSelect(event) {
            const files = event.target.files;
            handleFiles(files);
        }

        function handleFiles(files) {
            for (let file of files) {
                if (file.size > 10 * 1024 * 1024) { // 10MB
                    mostrarToast(`Arquivo ${file.name} é muito grande (máximo 10MB)`, 'error');
                    continue;
                }

                const anexo = {
                    id: Date.now() + Math.random(),
                    nome: file.name,
                    tamanho: formatFileSize(file.size),
                    tipo: getFileType(file.name),
                    arquivo: file
                };

                anexos.push(anexo);
                adicionarAnexoNaLista(anexo);
            }
        }

        function adicionarAnexoNaLista(anexo) {
            const anexosList = document.getElementById('anexosList');
            const anexoItem = document.createElement('div');
            anexoItem.className = 'anexo-item';
            anexoItem.dataset.anexoId = anexo.id;
            
            anexoItem.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-file-${anexo.tipo === 'pdf' ? 'pdf' : (anexo.tipo === 'image' ? 'image' : 'alt')} me-2"></i>
                    <span>${anexo.nome}</span>
                    <small class="text-muted ms-2">(${anexo.tamanho})</small>
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-danger btn-anexo" onclick="removerAnexo('${anexo.id}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            
            anexosList.appendChild(anexoItem);
        }

        function removerAnexo(anexoId) {
            anexos = anexos.filter(anexo => anexo.id != anexoId);
            const anexoItem = document.querySelector(`[data-anexo-id="${anexoId}"]`);
            if (anexoItem) {
                anexoItem.remove();
            }
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function getFileType(filename) {
            const ext = filename.split('.').pop().toLowerCase();
            if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) return 'image';
            if (ext === 'pdf') return 'pdf';
            return 'document';
        }

        // Detectar alterações
        document.addEventListener('DOMContentLoaded', function() {
            if (canEdit) {
                const conteudoEditor = document.getElementById('conteudoEditor');
                const observacoesEditor = document.getElementById('observacoesEditor');
                
                conteudoEditor.addEventListener('input', () => alteracoesPendentes = true);
                observacoesEditor.addEventListener('input', () => alteracoesPendentes = true);
            }
        });

        // Avisar sobre alterações não salvas
        window.addEventListener('beforeunload', function(e) {
            if (alteracoesPendentes) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Interface de diário carregada');
            console.log('Turma ID:', turmaId);
            console.log('Aula ID:', aulaId);
            console.log('Pode editar:', canEdit);
            console.log('Anexos:', anexos);
        });
    </script>
</body>
</html>
