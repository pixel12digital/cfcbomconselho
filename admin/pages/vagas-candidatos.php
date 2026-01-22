<?php
// =====================================================
// PÁGINA DE GERENCIAMENTO DE VAGAS E CANDIDATOS
// =====================================================

require_once '../../includes/config.php';
require_once '../../includes/database.php';

// Verificar se o usuário está logado como admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$db = db();
$mensagem = '';
$tipo_mensagem = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    try {
        switch ($acao) {
            case 'criar_vaga':
                $titulo = trim($_POST['titulo'] ?? '');
                $descricao = trim($_POST['descricao'] ?? '');
                $requisitos = trim($_POST['requisitos'] ?? '');
                $beneficios = trim($_POST['beneficios'] ?? '');
                $salario = trim($_POST['salario'] ?? '');
                $carga_horaria = trim($_POST['carga_horaria'] ?? '');
                $turno = trim($_POST['turno'] ?? '');
                $localizacao = trim($_POST['localizacao'] ?? 'Bom Conselho - PE');
                $status = $_POST['status'] ?? 'ativa';
                
                if (empty($titulo)) {
                    throw new Exception('Título da vaga é obrigatório.');
                }
                
                $sql = "INSERT INTO vagas (titulo, descricao, requisitos, beneficios, salario, carga_horaria, turno, localizacao, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $db->query($sql, [$titulo, $descricao, $requisitos, $beneficios, $salario, $carga_horaria, $turno, $localizacao, $status]);
                
                $mensagem = 'Vaga criada com sucesso!';
                $tipo_mensagem = 'success';
                break;
                
            case 'editar_vaga':
                $id = intval($_POST['id'] ?? 0);
                $titulo = trim($_POST['titulo'] ?? '');
                $descricao = trim($_POST['descricao'] ?? '');
                $requisitos = trim($_POST['requisitos'] ?? '');
                $beneficios = trim($_POST['beneficios'] ?? '');
                $salario = trim($_POST['salario'] ?? '');
                $carga_horaria = trim($_POST['carga_horaria'] ?? '');
                $turno = trim($_POST['turno'] ?? '');
                $localizacao = trim($_POST['localizacao'] ?? 'Bom Conselho - PE');
                $status = $_POST['status'] ?? 'ativa';
                
                if (empty($titulo)) {
                    throw new Exception('Título da vaga é obrigatório.');
                }
                
                $sql = "UPDATE vagas SET titulo = ?, descricao = ?, requisitos = ?, beneficios = ?, salario = ?, carga_horaria = ?, turno = ?, localizacao = ?, status = ? WHERE id = ?";
                $db->query($sql, [$titulo, $descricao, $requisitos, $beneficios, $salario, $carga_horaria, $turno, $localizacao, $status, $id]);
                
                $mensagem = 'Vaga atualizada com sucesso!';
                $tipo_mensagem = 'success';
                break;
                
            case 'excluir_vaga':
                $id = intval($_POST['id'] ?? 0);
                $sql = "DELETE FROM vagas WHERE id = ?";
                $db->query($sql, [$id]);
                
                $mensagem = 'Vaga excluída com sucesso!';
                $tipo_mensagem = 'success';
                break;
                
            case 'atualizar_status_candidato':
                $id = intval($_POST['id'] ?? 0);
                $status = $_POST['status'] ?? '';
                $observacoes = trim($_POST['observacoes'] ?? '');
                
                $sql = "UPDATE candidatos SET status = ?, observacoes = ? WHERE id = ?";
                $db->query($sql, [$status, $observacoes, $id]);
                
                $mensagem = 'Status do candidato atualizado com sucesso!';
                $tipo_mensagem = 'success';
                break;
        }
    } catch (Exception $e) {
        $mensagem = 'Erro: ' . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

// Buscar dados
$vagas = $db->fetchAll("SELECT * FROM vagas ORDER BY data_publicacao DESC");
$candidatos = $db->fetchAll("SELECT c.*, v.titulo as vaga_titulo FROM candidatos c LEFT JOIN vagas v ON c.vaga_id = v.id ORDER BY c.data_candidatura DESC");

// Buscar estatísticas
$stats = [
    'total_vagas' => $db->fetch("SELECT COUNT(*) as total FROM vagas")['total'],
    'vagas_ativas' => $db->fetch("SELECT COUNT(*) as total FROM vagas WHERE status = 'ativa'")['total'],
    'total_candidatos' => $db->fetch("SELECT COUNT(*) as total FROM candidatos")['total'],
    'candidatos_novos' => $db->fetch("SELECT COUNT(*) as total FROM candidatos WHERE status = 'novo'")['total']
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vagas e Candidatos - Admin CFC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --logo-blue: #1a365d;
            --logo-yellow: #fbbf24;
        }
        
        .stats-card {
            background: linear-gradient(135deg, var(--logo-blue), #2d3748);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .stats-card h3 {
            font-size: 2rem;
            margin: 0;
        }
        
        .stats-card p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .card-header {
            background: var(--logo-blue);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            font-weight: 600;
        }
        
        .btn-primary {
            background: var(--logo-blue);
            border-color: var(--logo-blue);
        }
        
        .btn-primary:hover {
            background: #2d3748;
            border-color: #2d3748;
        }
        
        .btn-warning {
            background: var(--logo-yellow);
            border-color: var(--logo-yellow);
            color: var(--logo-blue);
        }
        
        .btn-warning:hover {
            background: #f59e0b;
            border-color: #f59e0b;
            color: var(--logo-blue);
        }
        
        .status-badge {
            font-size: 0.8rem;
            padding: 4px 8px;
            border-radius: 15px;
        }
        
        .status-novo { background: #e3f2fd; color: #1976d2; }
        .status-em_analise { background: #fff3e0; color: #f57c00; }
        .status-aprovado { background: #e8f5e8; color: #388e3c; }
        .status-rejeitado { background: #ffebee; color: #d32f2f; }
        .status-contratado { background: #f3e5f5; color: #7b1fa2; }
        
        .vaga-ativa { background: #e8f5e8; color: #388e3c; }
        .vaga-inativa { background: #ffebee; color: #d32f2f; }
        .vaga-pausada { background: #fff3e0; color: #f57c00; }
        
        .table th {
            background: #f8f9fa;
            border-top: none;
            font-weight: 600;
            color: var(--logo-blue);
        }
        
        .modal-header {
            background: var(--logo-blue);
            color: white;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--logo-blue);
        }
        
        .nav-tabs .nav-link {
            color: var(--logo-blue);
            font-weight: 500;
        }
        
        .nav-tabs .nav-link.active {
            background: var(--logo-blue);
            color: white;
            border-color: var(--logo-blue);
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">
                    <i class="fas fa-briefcase"></i> Gerenciamento de Vagas e Candidatos
                </h1>
                
                <?php if ($mensagem): ?>
                    <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show">
                        <?php echo htmlspecialchars($mensagem); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Estatísticas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h3><?php echo $stats['total_vagas']; ?></h3>
                            <p>Total de Vagas</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h3><?php echo $stats['vagas_ativas']; ?></h3>
                            <p>Vagas Ativas</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h3><?php echo $stats['total_candidatos']; ?></h3>
                            <p>Total de Candidatos</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h3><?php echo $stats['candidatos_novos']; ?></h3>
                            <p>Candidatos Novos</p>
                        </div>
                    </div>
                </div>
                
                <!-- Tabs -->
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="vagas-tab" data-bs-toggle="tab" data-bs-target="#vagas" type="button" role="tab">
                            <i class="fas fa-briefcase"></i> Vagas
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="candidatos-tab" data-bs-toggle="tab" data-bs-target="#candidatos" type="button" role="tab">
                            <i class="fas fa-users"></i> Candidatos
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="myTabContent">
                    <!-- Tab Vagas -->
                    <div class="tab-pane fade show active" id="vagas" role="tabpanel">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Gerenciar Vagas</span>
                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalVaga">
                                    <i class="fas fa-plus"></i> Nova Vaga
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Título</th>
                                                <th>Status</th>
                                                <th>Data Publicação</th>
                                                <th>Candidatos</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($vagas as $vaga): ?>
                                                <?php
                                                $candidatos_vaga = $db->fetch("SELECT COUNT(*) as total FROM candidatos WHERE vaga_id = ?", [$vaga['id']])['total'];
                                                ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($vaga['titulo']); ?></strong>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($vaga['carga_horaria']); ?> - <?php echo htmlspecialchars($vaga['turno']); ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="status-badge vaga-<?php echo $vaga['status']; ?>">
                                                            <?php echo ucfirst($vaga['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('d/m/Y', strtotime($vaga['data_publicacao'])); ?></td>
                                                    <td>
                                                        <span class="badge bg-primary"><?php echo $candidatos_vaga; ?></span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="editarVaga(<?php echo htmlspecialchars(json_encode($vaga)); ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="excluirVaga(<?php echo $vaga['id']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab Candidatos -->
                    <div class="tab-pane fade" id="candidatos" role="tabpanel">
                        <div class="card">
                            <div class="card-header">
                                Gerenciar Candidatos
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Nome</th>
                                                <th>E-mail</th>
                                                <th>Vaga</th>
                                                <th>Status</th>
                                                <th>Data</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($candidatos as $candidato): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($candidato['nome_completo']); ?></strong>
                                                        <?php if ($candidato['whatsapp']): ?>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars($candidato['whatsapp']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($candidato['email']); ?></td>
                                                    <td>
                                                        <?php if ($candidato['vaga_titulo']): ?>
                                                            <?php echo htmlspecialchars($candidato['vaga_titulo']); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">Não especificada</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $candidato['status']; ?>">
                                                            <?php echo ucfirst(str_replace('_', ' ', $candidato['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('d/m/Y', strtotime($candidato['data_candidatura'])); ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="verCandidato(<?php echo htmlspecialchars(json_encode($candidato)); ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-warning" onclick="editarStatusCandidato(<?php echo htmlspecialchars(json_encode($candidato)); ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Vaga -->
    <div class="modal fade" id="modalVaga" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalVagaTitle">Nova Vaga</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formVaga">
                    <div class="modal-body">
                        <input type="hidden" name="acao" value="criar_vaga" id="acaoVaga">
                        <input type="hidden" name="id" id="vagaId">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Título *</label>
                                    <input type="text" class="form-control" name="titulo" id="vagaTitulo" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status" id="vagaStatus">
                                        <option value="ativa">Ativa</option>
                                        <option value="inativa">Inativa</option>
                                        <option value="pausada">Pausada</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Descrição</label>
                            <textarea class="form-control" name="descricao" id="vagaDescricao" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Requisitos</label>
                            <textarea class="form-control" name="requisitos" id="vagaRequisitos" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Benefícios</label>
                            <textarea class="form-control" name="beneficios" id="vagaBeneficios" rows="2"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Salário</label>
                                    <input type="text" class="form-control" name="salario" id="vagaSalario">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Carga Horária</label>
                                    <input type="text" class="form-control" name="carga_horaria" id="vagaCargaHoraria">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Turno</label>
                                    <input type="text" class="form-control" name="turno" id="vagaTurno">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Localização</label>
                            <input type="text" class="form-control" name="localizacao" id="vagaLocalizacao" value="Bom Conselho - PE">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Candidato -->
    <div class="modal fade" id="modalCandidato" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalhes do Candidato</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="candidatoDetalhes">
                    <!-- Conteúdo será preenchido via JavaScript -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Status Candidato -->
    <div class="modal fade" id="modalStatusCandidato" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Atualizar Status</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="acao" value="atualizar_status_candidato">
                        <input type="hidden" name="id" id="candidatoId">
                        
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="candidatoStatus">
                                <option value="novo">Novo</option>
                                <option value="em_analise">Em Análise</option>
                                <option value="aprovado">Aprovado</option>
                                <option value="rejeitado">Rejeitado</option>
                                <option value="contratado">Contratado</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Observações</label>
                            <textarea class="form-control" name="observacoes" id="candidatoObservacoes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Atualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarVaga(vaga) {
            document.getElementById('modalVagaTitle').textContent = 'Editar Vaga';
            document.getElementById('acaoVaga').value = 'editar_vaga';
            document.getElementById('vagaId').value = vaga.id;
            document.getElementById('vagaTitulo').value = vaga.titulo;
            document.getElementById('vagaDescricao').value = vaga.descricao || '';
            document.getElementById('vagaRequisitos').value = vaga.requisitos || '';
            document.getElementById('vagaBeneficios').value = vaga.beneficios || '';
            document.getElementById('vagaSalario').value = vaga.salario || '';
            document.getElementById('vagaCargaHoraria').value = vaga.carga_horaria || '';
            document.getElementById('vagaTurno').value = vaga.turno || '';
            document.getElementById('vagaLocalizacao').value = vaga.localizacao || 'Bom Conselho - PE';
            document.getElementById('vagaStatus').value = vaga.status;
            
            new bootstrap.Modal(document.getElementById('modalVaga')).show();
        }
        
        function excluirVaga(id) {
            if (confirm('Tem certeza que deseja excluir esta vaga?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="acao" value="excluir_vaga">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function verCandidato(candidato) {
            const detalhes = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Informações Pessoais</h6>
                        <p><strong>Nome:</strong> ${candidato.nome_completo}</p>
                        <p><strong>E-mail:</strong> ${candidato.email}</p>
                        <p><strong>WhatsApp:</strong> ${candidato.whatsapp || 'Não informado'}</p>
                        <p><strong>Telefone:</strong> ${candidato.telefone || 'Não informado'}</p>
                        <p><strong>CNH:</strong> ${candidato.categoria_cnh || 'Não informado'}</p>
                        <p><strong>Escolaridade:</strong> ${candidato.escolaridade || 'Não informado'}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Endereço</h6>
                        <p><strong>Rua:</strong> ${candidato.endereco_rua || 'Não informado'}</p>
                        <p><strong>Cidade:</strong> ${candidato.cidade || 'Não informado'}</p>
                        <p><strong>Estado:</strong> ${candidato.estado || 'Não informado'}</p>
                        <p><strong>CEP:</strong> ${candidato.cep || 'Não informado'}</p>
                        <p><strong>País:</strong> ${candidato.pais || 'Brasil'}</p>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-12">
                        <h6>Informações Adicionais</h6>
                        <p><strong>Vaga de Interesse:</strong> ${candidato.vaga_titulo || 'Não especificada'}</p>
                        <p><strong>Indicações:</strong> ${candidato.indicacoes || 'Nenhuma'}</p>
                        <p><strong>Mensagem:</strong> ${candidato.mensagem || 'Nenhuma'}</p>
                        <p><strong>Status:</strong> <span class="status-badge status-${candidato.status}">${candidato.status.replace('_', ' ')}</span></p>
                        <p><strong>Data da Candidatura:</strong> ${new Date(candidato.data_candidatura).toLocaleDateString('pt-BR')}</p>
                        ${candidato.observacoes ? `<p><strong>Observações:</strong> ${candidato.observacoes}</p>` : ''}
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-12">
                        <h6>Arquivos</h6>
                        <p><strong>Currículo:</strong> ${candidato.curriculo_arquivo ? `<a href="${candidato.curriculo_arquivo}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i> Baixar</a>` : 'Não enviado'}</p>
                        <p><strong>Foto:</strong> ${candidato.foto_arquivo ? `<a href="${candidato.foto_arquivo}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-image"></i> Ver</a>` : 'Não enviada'}</p>
                    </div>
                </div>
            `;
            
            document.getElementById('candidatoDetalhes').innerHTML = detalhes;
            new bootstrap.Modal(document.getElementById('modalCandidato')).show();
        }
        
        function editarStatusCandidato(candidato) {
            document.getElementById('candidatoId').value = candidato.id;
            document.getElementById('candidatoStatus').value = candidato.status;
            document.getElementById('candidatoObservacoes').value = candidato.observacoes || '';
            
            new bootstrap.Modal(document.getElementById('modalStatusCandidato')).show();
        }
        
        // Reset modal quando fechado
        document.getElementById('modalVaga').addEventListener('hidden.bs.modal', function() {
            document.getElementById('modalVagaTitle').textContent = 'Nova Vaga';
            document.getElementById('acaoVaga').value = 'criar_vaga';
            document.getElementById('formVaga').reset();
            document.getElementById('vagaId').value = '';
        });
    </script>
</body>
</html>
