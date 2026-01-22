<?php
/**
 * Interface de Relatórios - Turmas Teóricas
 * Sistema de Turmas Teóricas - CFC Bom Conselho
 * ETAPA 1.5: Relatórios e Exportações
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
$canView = ($userType === 'admin' || $userType === 'instrutor');

if (!$canView) {
    header('Location: /admin/pages/turmas.php');
    exit();
}

// Parâmetros da URL
$turmaId = $_GET['turma_id'] ?? null;
$tipo = $_GET['tipo'] ?? 'frequencia';

// Buscar turmas disponíveis
$turmas = $db->fetchAll("
    SELECT 
        t.*,
        i.nome as instrutor_nome,
        c.nome as cfc_nome,
        COUNT(ta.id) as total_alunos
    FROM turmas t
    LEFT JOIN instrutores i ON t.instrutor_id = i.id
    LEFT JOIN cfcs c ON t.cfc_id = c.id
    LEFT JOIN turma_alunos ta ON t.id = ta.turma_id
    WHERE t.tipo_aula = 'teorica'
    GROUP BY t.id
    ORDER BY t.data_inicio DESC
");

// Se especificou turma, buscar dados
$turmaSelecionada = null;
$dadosRelatorio = null;

if ($turmaId) {
    $turmaSelecionada = $db->fetch("
        SELECT 
            t.*,
            i.nome as instrutor_nome,
            c.nome as cfc_nome
        FROM turmas t
        LEFT JOIN instrutores i ON t.instrutor_id = i.id
        LEFT JOIN cfcs c ON t.cfc_id = c.id
        WHERE t.id = ?
    ", [$turmaId]);
    
    if ($turmaSelecionada) {
        // Buscar dados do relatório via API
        $_GET = ['tipo' => $tipo, 'turma_id' => $turmaId];
        ob_start();
        include __DIR__ . '/../api/turma-relatorios.php';
        $output = ob_get_clean();
        $response = json_decode($output, true);
        
        if ($response && $response['success']) {
            $dadosRelatorio = $response['data'];
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Turmas Teóricas</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        .relatorios-container {
            background: #f8f9fa;
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .relatorios-header {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            padding: 20px;
        }
        
        .relatorios-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .filtros-container {
            background: #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .relatorio-content {
            min-height: 400px;
        }
        
        .stats-card {
            text-align: center;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .stats-number {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stats-label {
            color: #6c757d;
            font-size: 0.9em;
        }
        
        .frequencia-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 500;
        }
        
        .frequencia-badge.alto {
            background: #d4edda;
            color: #155724;
        }
        
        .frequencia-badge.medio {
            background: #fff3cd;
            color: #856404;
        }
        
        .frequencia-badge.baixo {
            background: #f8d7da;
            color: #721c24;
        }
        
        .btn-export {
            margin: 5px;
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
        
        .tabela-relatorio {
            font-size: 0.9em;
        }
        
        .tabela-relatorio th {
            background: #f8f9fa;
            font-weight: 600;
            border-top: none;
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
        
        .ata-content {
            font-family: 'Times New Roman', serif;
            line-height: 1.6;
        }
        
        .ata-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .ata-section {
            margin-bottom: 25px;
        }
        
        .ata-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        .ata-table th,
        .ata-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        
        .ata-table th {
            background: #f0f0f0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="relatorios-container">
        <div class="container-fluid">
            <!-- Header dos Relatórios -->
            <div class="relatorios-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-1">
                            <i class="fas fa-chart-bar text-primary"></i>
                            Relatórios e Exportações
                        </h2>
                        <p class="text-muted mb-0">
                            Sistema de Turmas Teóricas - CFC Bom Conselho
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <!-- Links Contextuais -->
                        <div class="btn-group" role="group">
                            <?php if ($turmaSelecionada): ?>
                                <?php
                                // Buscar primeira aula da turma para deep links
                                $firstAula = $db->fetch("
                                    SELECT id FROM turma_aulas 
                                    WHERE turma_id = ? 
                                    ORDER BY data_aula ASC, hora_inicio ASC 
                                    LIMIT 1
                                ", [$turmaSelecionada['id']]);
                                $firstAulaId = $firstAula['id'] ?? null;
                                ?>
                                
                                <?php if ($firstAulaId): ?>
                                    <a href="turma-chamada.php?turma_id=<?= $turmaSelecionada['id'] ?>&aula_id=<?= $firstAulaId ?>" 
                                       class="btn btn-outline-primary btn-sm" title="Ir para Chamada">
                                        <i class="fas fa-clipboard-check"></i> Chamada
                                    </a>
                                    <a href="turma-diario.php?turma_id=<?= $turmaSelecionada['id'] ?>&aula_id=<?= $firstAulaId ?>" 
                                       class="btn btn-outline-info btn-sm" title="Ir para Diário">
                                        <i class="fas fa-book-open"></i> Diário
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                            <a href="turmas.php" class="btn btn-outline-secondary btn-sm" title="Voltar para Gestão de Turmas">
                                <i class="fas fa-arrow-left"></i> Voltar
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="filtros-container">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Turma:</label>
                        <select class="form-select" id="turmaSelector" onchange="trocarTurma()">
                            <option value="">Selecione uma turma...</option>
                            <?php foreach ($turmas as $turma): ?>
                            <option value="<?= $turma['id'] ?>" <?= $turma['id'] == $turmaId ? 'selected' : '' ?>>
                                <?= htmlspecialchars($turma['nome']) ?> 
                                (<?= date('d/m/Y', strtotime($turma['data_inicio'])) ?> - <?= date('d/m/Y', strtotime($turma['data_fim'])) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Tipo de Relatório:</label>
                        <select class="form-select" id="tipoSelector" onchange="trocarTipo()">
                            <option value="frequencia" <?= $tipo === 'frequencia' ? 'selected' : '' ?>>Frequência</option>
                            <option value="ata" <?= $tipo === 'ata' ? 'selected' : '' ?>>Ata da Turma</option>
                            <option value="presencas" <?= $tipo === 'presencas' ? 'selected' : '' ?>>Presenças</option>
                            <option value="matriculas" <?= $tipo === 'matriculas' ? 'selected' : '' ?>>Matrículas</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Exportar:</label>
                        <div class="btn-group w-100" role="group">
                            <button class="btn btn-outline-success btn-sm" onclick="exportarCSV()" <?= !$turmaId ? 'disabled' : '' ?>>
                                <i class="fas fa-file-csv"></i> CSV
                            </button>
                            <button class="btn btn-outline-danger btn-sm" onclick="exportarPDF()" <?= !$turmaId ? 'disabled' : '' ?>>
                                <i class="fas fa-file-pdf"></i> PDF
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" onclick="gerarRelatorio()" <?= !$turmaId ? 'disabled' : '' ?>>
                            <i class="fas fa-sync"></i> Gerar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Conteúdo do Relatório -->
            <?php if ($turmaSelecionada && $dadosRelatorio): ?>
            <div class="relatorios-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-<?= $tipo === 'frequencia' ? 'chart-line' : ($tipo === 'ata' ? 'file-alt' : 'table') ?>"></i>
                        <?= ucfirst($tipo) ?> - <?= htmlspecialchars($turmaSelecionada['nome']) ?>
                    </h5>
                </div>
                <div class="card-body relatorio-content">
                    <?php if ($tipo === 'frequencia'): ?>
                        <?php include 'relatorio-frequencia.php'; ?>
                    <?php elseif ($tipo === 'ata'): ?>
                        <?php include 'relatorio-ata.php'; ?>
                    <?php elseif ($tipo === 'presencas'): ?>
                        <?php include 'relatorio-presencas.php'; ?>
                    <?php elseif ($tipo === 'matriculas'): ?>
                        <?php include 'relatorio-matriculas.php'; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="relatorios-card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Selecione uma turma para gerar o relatório</h5>
                    <p class="text-muted">Escolha uma turma e o tipo de relatório desejado</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Variáveis globais
        let turmaId = <?= $turmaId ?: 'null' ?>;
        let tipo = '<?= $tipo ?>';

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

        // Função para trocar turma
        function trocarTurma() {
            const novoTurmaId = document.getElementById('turmaSelector').value;
            if (novoTurmaId != turmaId) {
                window.location.href = `?turma_id=${novoTurmaId}&tipo=${tipo}`;
            }
        }

        // Função para trocar tipo
        function trocarTipo() {
            const novoTipo = document.getElementById('tipoSelector').value;
            if (novoTipo != tipo) {
                window.location.href = `?turma_id=${turmaId}&tipo=${novoTipo}`;
            }
        }

        // Função para gerar relatório
        function gerarRelatorio() {
            if (!turmaId) {
                mostrarToast('Selecione uma turma primeiro', 'error');
                return;
            }
            
            window.location.reload();
        }

        // Função para exportar CSV
        function exportarCSV() {
            if (!turmaId) {
                mostrarToast('Selecione uma turma primeiro', 'error');
                return;
            }
            
            const dados = {
                tipo: 'export_csv',
                turma_id: turmaId,
                dados: {
                    tipo: tipo
                }
            };
            
            fetch('/admin/api/turma-relatorios.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(dados)
            })
            .then(response => {
                if (response.headers.get('content-type')?.includes('text/csv')) {
                    return response.blob();
                } else {
                    return response.json();
                }
            })
            .then(data => {
                if (data instanceof Blob) {
                    // Download do CSV
                    const url = window.URL.createObjectURL(data);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `relatorio_${tipo}_turma_${turmaId}.csv`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                    mostrarToast('CSV exportado com sucesso!');
                } else {
                    mostrarToast('Erro ao exportar CSV: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarToast('Erro de conexão. Tente novamente.', 'error');
            });
        }

        // Função para exportar PDF
        function exportarPDF() {
            if (!turmaId) {
                mostrarToast('Selecione uma turma primeiro', 'error');
                return;
            }
            
            const dados = {
                tipo: 'export_pdf',
                turma_id: turmaId,
                dados: {
                    tipo: tipo
                }
            };
            
            fetch('/admin/api/turma-relatorios.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(dados)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarToast('PDF preparado com sucesso!');
                    // Em produção, aqui seria o download do PDF
                    console.log('Dados para PDF:', data.data);
                } else {
                    mostrarToast('Erro ao exportar PDF: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarToast('Erro de conexão. Tente novamente.', 'error');
            });
        }

        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Interface de relatórios carregada');
            console.log('Turma ID:', turmaId);
            console.log('Tipo:', tipo);
        });
    </script>
</body>
</html>
