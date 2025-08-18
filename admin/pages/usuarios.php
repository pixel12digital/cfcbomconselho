<?php
// Verificar se as variáveis estão definidas
if (!isset($db)) {
    require_once '../includes/config.php';
    require_once '../includes/database.php';
    $db = Database::getInstance();
}

// Processar ações
$action = $_GET['action'] ?? 'list';
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create' || $action === 'edit') {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $tipo = $_POST['tipo'] ?? '';
        $status = $_POST['status'] ?? 'ativo';
        
        if (empty($nome) || empty($email) || empty($tipo)) {
            $message = 'Todos os campos obrigatórios devem ser preenchidos.';
            $messageType = 'danger';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'E-mail inválido.';
            $messageType = 'danger';
        } else {
            $data = [
                'nome' => $nome,
                'email' => $email,
                'tipo' => $tipo,
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($action === 'create') {
                // Verificar se e-mail já existe
                if ($db->exists('usuarios', ['email' => $email])) {
                    $message = 'Este e-mail já está cadastrado no sistema.';
                    $messageType = 'danger';
                } else {
                    // Gerar senha temporária
                    $senha_temp = generateTemporaryPassword();
                    $data['senha'] = password_hash($senha_temp, PASSWORD_DEFAULT);
                    $data['created_at'] = date('Y-m-d H:i:s');
                    
                    if ($db->insert('usuarios', $data)) {
                        $message = "Usuário criado com sucesso! Senha temporária: <strong>{$senha_temp}</strong>";
                        $messageType = 'success';
                        $action = 'list';
                    } else {
                        $message = 'Erro ao criar usuário.';
                        $messageType = 'danger';
                    }
                }
            } else {
                // Edição
                $id = $_POST['id'] ?? 0;
                if ($db->update('usuarios', $data, ['id' => $id])) {
                    $message = 'Usuário atualizado com sucesso!';
                    $messageType = 'success';
                    $action = 'list';
                } else {
                    $message = 'Erro ao atualizar usuário.';
                    $messageType = 'danger';
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        if ($id && $db->delete('usuarios', ['id' => $id])) {
            $message = 'Usuário excluído com sucesso!';
            $messageType = 'success';
        } else {
            $message = 'Erro ao excluir usuário.';
            $messageType = 'danger';
        }
        $action = 'list';
    }
}

// Função para gerar senha temporária
function generateTemporaryPassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}

// Carregar dados para edição
$usuario_edit = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $usuario_edit = $db->findById('usuarios', $id);
    if (!$usuario_edit) {
        $message = 'Usuário não encontrado.';
        $messageType = 'danger';
        $action = 'list';
    }
}

// Carregar lista de usuários
$usuarios = [];
if ($action === 'list') {
    $usuarios = $db->query("
        SELECT u.*, c.nome as cfc_nome 
        FROM usuarios u 
        LEFT JOIN cfcs c ON u.cfc_id = c.id 
        ORDER BY u.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}
?>

<?php if ($action === 'list'): ?>
    <!-- Lista de Usuários -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-users me-2"></i>Gestão de Usuários
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="index.php?page=usuarios&action=create" class="btn btn-primary">
                <i class="fas fa-user-plus me-1"></i>Novo Usuário
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filtros e Busca -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <label for="filtro-tipo" class="form-label">Filtrar por Tipo</label>
                    <select class="form-select" id="filtro-tipo">
                        <option value="">Todos os tipos</option>
                        <option value="admin">Administrador</option>
                        <option value="instructor">Instrutor</option>
                        <option value="secretaria">Secretaria</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="filtro-status" class="form-label">Filtrar por Status</label>
                    <select class="form-select" id="filtro-status">
                        <option value="">Todos os status</option>
                        <option value="ativo">Ativo</option>
                        <option value="inativo">Inativo</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="busca-usuario" class="form-label">Buscar</label>
                    <input type="text" class="form-control" id="busca-usuario" placeholder="Nome ou e-mail...">
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Usuários -->
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list me-1"></i>Lista de Usuários
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="tabela-usuarios">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Tipo</th>
                            <th>CFC</th>
                            <th>Status</th>
                            <th>Criado em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?php echo $usuario['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($usuario['nome']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo getTipoBadgeClass($usuario['tipo']); ?>">
                                        <?php echo ucfirst($usuario['tipo']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($usuario['cfc_nome'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $usuario['status'] === 'ativo' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($usuario['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($usuario['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="index.php?page=usuarios&action=edit&id=<?php echo $usuario['id']; ?>" 
                                           class="btn btn-outline-primary" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-warning" 
                                                onclick="resetarSenha(<?php echo $usuario['id']; ?>)" title="Resetar Senha">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="excluirUsuario(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['nome']); ?>')" 
                                                title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($action === 'create' || $action === 'edit'): ?>
    <!-- Formulário de Usuário -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-<?php echo $action === 'create' ? 'user-plus' : 'user-edit'; ?> me-2"></i>
            <?php echo $action === 'create' ? 'Novo Usuário' : 'Editar Usuário'; ?>
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="index.php?page=usuarios&action=list" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Voltar
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-user me-1"></i>Dados do Usuário
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="index.php?page=usuarios&action=<?php echo $action; ?>" id="form-usuario">
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="id" value="<?php echo $usuario_edit['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nome" class="form-label">Nome Completo *</label>
                                <input type="text" class="form-control" id="nome" name="nome" 
                                       value="<?php echo htmlspecialchars($usuario_edit['nome'] ?? ''); ?>" 
                                       required maxlength="100">
                                <div class="invalid-feedback">
                                    Nome é obrigatório.
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">E-mail *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($usuario_edit['email'] ?? ''); ?>" 
                                       required maxlength="100">
                                <div class="invalid-feedback">
                                    E-mail válido é obrigatório.
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tipo" class="form-label">Tipo de Usuário *</label>
                                <select class="form-select" id="tipo" name="tipo" required>
                                    <option value="">Selecione o tipo</option>
                                    <option value="admin" <?php echo ($usuario_edit['tipo'] ?? '') === 'admin' ? 'selected' : ''; ?>>
                                        Administrador
                                    </option>
                                    <option value="instructor" <?php echo ($usuario_edit['tipo'] ?? '') === 'instructor' ? 'selected' : ''; ?>>
                                        Instrutor
                                    </option>
                                    <option value="secretaria" <?php echo ($usuario_edit['tipo'] ?? '') === 'secretaria' ? 'selected' : ''; ?>>
                                        Secretaria
                                    </option>
                                </select>
                                <div class="invalid-feedback">
                                    Tipo de usuário é obrigatório.
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="ativo" <?php echo ($usuario_edit['status'] ?? 'ativo') === 'ativo' ? 'selected' : ''; ?>>
                                        Ativo
                                    </option>
                                    <option value="inativo" <?php echo ($usuario_edit['status'] ?? 'ativo') === 'inativo' ? 'selected' : ''; ?>>
                                        Inativo
                                    </option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cfc_id" class="form-label">CFC (Opcional)</label>
                                <select class="form-select" id="cfc_id" name="cfc_id">
                                    <option value="">Selecione o CFC</option>
                                    <?php
                                    $cfcs = $db->query("SELECT id, nome FROM cfcs WHERE status = 'ativo' ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($cfcs as $cfc):
                                        $selected = ($usuario_edit['cfc_id'] ?? '') == $cfc['id'] ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo $cfc['id']; ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($cfc['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="telefone" class="form-label">Telefone</label>
                                <input type="tel" class="form-control" id="telefone" name="telefone" 
                                       value="<?php echo htmlspecialchars($usuario_edit['telefone'] ?? ''); ?>" 
                                       maxlength="15" placeholder="(00) 00000-0000">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="3" 
                                      maxlength="500"><?php echo htmlspecialchars($usuario_edit['observacoes'] ?? ''); ?></textarea>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between">
                            <a href="index.php?page=usuarios&action=list" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-<?php echo $action === 'create' ? 'save' : 'check'; ?> me-1"></i>
                                <?php echo $action === 'create' ? 'Criar Usuário' : 'Atualizar Usuário'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle me-1"></i>Informações
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-lightbulb me-1"></i>Dicas:</h6>
                        <ul class="mb-0 small">
                            <li>O nome deve ser completo e real</li>
                            <li>Use e-mail válido e único</li>
                            <li>Escolha o tipo adequado de usuário</li>
                            <li>CFC é opcional para administradores</li>
                        </ul>
                    </div>
                    
                    <?php if ($action === 'create'): ?>
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle me-1"></i>Atenção:</h6>
                            <p class="mb-0 small">Uma senha temporária será gerada automaticamente e exibida após a criação do usuário.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
// Funções JavaScript para a página de usuários
function excluirUsuario(id, nome) {
    if (confirm(`Tem certeza que deseja excluir o usuário "${nome}"? Esta ação não pode ser desfeita.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'index.php?page=usuarios&action=delete';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'id';
        input.value = id;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}

function resetarSenha(id) {
    if (confirm('Deseja resetar a senha deste usuário? Uma nova senha temporária será gerada.')) {
        // Implementar reset de senha via AJAX
        fetch('../api/resetar-senha.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Senha resetada com sucesso! Nova senha: ${data.nova_senha}`);
            } else {
                alert('Erro ao resetar senha: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao resetar senha. Tente novamente.');
        });
    }
}

// Filtros da tabela
document.addEventListener('DOMContentLoaded', function() {
    const filtroTipo = document.getElementById('filtro-tipo');
    const filtroStatus = document.getElementById('filtro-status');
    const buscaUsuario = document.getElementById('busca-usuario');
    const tabela = document.getElementById('tabela-usuarios');
    
    if (filtroTipo && filtroStatus && buscaUsuario && tabela) {
        [filtroTipo, filtroStatus, buscaUsuario].forEach(element => {
            element.addEventListener('change', filtrarTabela);
            element.addEventListener('keyup', filtrarTabela);
        });
    }
});

function filtrarTabela() {
    const filtroTipo = document.getElementById('filtro-tipo').value.toLowerCase();
    const filtroStatus = document.getElementById('filtro-status').value.toLowerCase();
    const buscaUsuario = document.getElementById('busca-usuario').value.toLowerCase();
    const tabela = document.getElementById('tabela-usuarios');
    
    const linhas = tabela.querySelectorAll('tbody tr');
    
    linhas.forEach(linha => {
        const tipo = linha.cells[3].textContent.toLowerCase();
        const status = linha.cells[5].textContent.toLowerCase();
        const nome = linha.cells[1].textContent.toLowerCase();
        const email = linha.cells[2].textContent.toLowerCase();
        
        const matchTipo = !filtroTipo || tipo.includes(filtroTipo);
        const matchStatus = !filtroStatus || status.includes(filtroStatus);
        const matchBusca = !buscaUsuario || nome.includes(buscaUsuario) || email.includes(buscaUsuario);
        
        linha.style.display = matchTipo && matchStatus && matchBusca ? '' : 'none';
    });
}

// Validação do formulário
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-usuario');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    }
});

function validateForm(form) {
    let isValid = true;
    
    // Validar campos obrigatórios
    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
        }
    });
    
    // Validar e-mail
    const emailField = form.querySelector('#email');
    if (emailField && emailField.value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(emailField.value)) {
            emailField.classList.add('is-invalid');
            isValid = false;
        }
    }
    
    return isValid;
}

// Máscara para telefone
document.addEventListener('DOMContentLoaded', function() {
    const telefoneField = document.getElementById('telefone');
    if (telefoneField) {
        telefoneField.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
                value = value.replace(/(\d)(\d{4})$/, '$1-$2');
                e.target.value = value;
            }
        });
    }
});
</script>

<?php
// Função auxiliar para classes de badge
function getTipoBadgeClass($tipo) {
    $classes = [
        'admin' => 'danger',
        'instructor' => 'primary',
        'secretaria' => 'info'
    ];
    return $classes[$tipo] ?? 'secondary';
}
?>
