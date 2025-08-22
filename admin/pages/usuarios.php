<?php
// Verificar se as variáveis estão definidas
$action = $_GET['action'] ?? 'list';
$db = Database::getInstance();

// Buscar usuários se for listagem
$usuarios = [];
if ($action === 'list') {
    try {
        $usuarios = $db->fetchAll("SELECT * FROM usuarios ORDER BY nome");
    } catch (Exception $e) {
        $usuarios = [];
        if (LOG_ENABLED) {
            error_log('Erro ao buscar usuários: ' . $e->getMessage());
        }
    }
}
?>

<!-- Header da Página -->
<div class="page-header">
    <div>
        <h1 class="page-title">Gerenciar Usuários</h1>
        <p class="page-subtitle">Cadastro e gerenciamento de usuários do sistema</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="showCreateUserModal()">
            <i class="fas fa-plus"></i>
            Novo Usuário
        </button>
        <button class="btn btn-outline-primary" onclick="exportUsers()">
            <i class="fas fa-download"></i>
            Exportar
        </button>
    </div>
</div>

<?php if ($action === 'list'): ?>
    <!-- Lista de Usuários -->
    <div class="card">
        <div class="card-header">
            <h3>Usuários Cadastrados</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($usuarios)): ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>E-mail</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th>Criado em</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex items-center gap-3">
                                            <div class="user-avatar">
                                                <?php echo strtoupper(substr($usuario['nome'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="font-weight-semibold"><?php echo htmlspecialchars($usuario['nome']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $usuario['tipo'] === 'admin' ? 'danger' : 'primary'; ?>">
                                            <?php echo ucfirst($usuario['tipo']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $usuario['ativo'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $usuario['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($usuario['criado_em'])); ?></td>
                                    <td>
                                        <div class="action-buttons-container">
                                            <!-- Botão de edição -->
                                            <button class="btn btn-edit action-btn" onclick="editUser(<?php echo $usuario['id']; ?>)" 
                                                    title="Editar dados do usuário">
                                                <i class="fas fa-edit me-1"></i>Editar
                                            </button>
                                            
                                            <!-- Botão de exclusão destacado -->
                                            <button class="btn btn-delete action-btn" onclick="deleteUser(<?php echo $usuario['id']; ?>)" 
                                                    title="⚠️ EXCLUIR USUÁRIO - Esta ação não pode ser desfeita!">
                                                <i class="fas fa-trash me-1"></i>Excluir
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center p-5">
                    <div class="text-light">
                        <i class="fas fa-users fa-3x mb-3"></i>
                        <p>Nenhum usuário cadastrado</p>
                        <button class="btn btn-primary" onclick="showCreateUserModal()">
                            <i class="fas fa-plus"></i>
                            Cadastrar Primeiro Usuário
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Modal de Criação/Edição de Usuário -->
<div id="userModal" class="modal-overlay" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="userModalTitle">Novo Usuário</h3>
            <button class="modal-close" onclick="closeUserModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="userForm">
                <input type="hidden" id="userId" name="id">
                
                <div class="form-group">
                    <label for="userName" class="form-label">Nome Completo</label>
                    <input type="text" id="userName" name="nome" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="userEmail" class="form-label">E-mail</label>
                    <input type="email" id="userEmail" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="userType" class="form-label">Tipo de Usuário</label>
                    <select id="userType" name="tipo" class="form-control" required>
                        <option value="">Selecione...</option>
                        <option value="admin">Administrador</option>
                        <option value="instrutor">Instrutor</option>
                        <option value="aluno">Aluno</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="userPassword" class="form-label">Senha</label>
                    <input type="password" id="userPassword" name="senha" class="form-control" required>
                    <div class="form-text">Mínimo 6 caracteres</div>
                </div>
                
                <div class="form-group">
                    <label for="userConfirmPassword" class="form-label">Confirmar Senha</label>
                    <input type="password" id="userConfirmPassword" name="confirmar_senha" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" id="userActive" name="ativo" checked>
                        Usuário Ativo
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeUserModal()">Cancelar</button>
            <button type="button" class="btn btn-primary" onclick="saveUser()">Salvar</button>
        </div>
    </div>
</div>

<!-- Scripts específicos da página -->
<script>
// Variáveis globais
let currentUser = null;
let isEditMode = false;

// Mostrar modal de criação
function showCreateUserModal() {
    isEditMode = false;
    currentUser = null;
    
    document.getElementById('userModalTitle').textContent = 'Novo Usuário';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    
    // Mostrar campo de senha
    document.getElementById('userPassword').required = true;
    document.getElementById('userConfirmPassword').required = true;
    
    document.getElementById('userModal').style.display = 'flex';
}

// Mostrar modal de edição
function editUser(userId) {
    isEditMode = true;
    
    // Buscar dados do usuário
    const loadingEl = document.querySelector('.card-body');
    if (loadingEl) {
        loadingEl.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Carregando...</span></div><p class="mt-2">Carregando dados do usuário...</p></div>';
    }
    
    // Buscar dados reais da API
    fetch(`../api/usuarios.php?id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentUser = data.data;
                
                // Preencher formulário
                document.getElementById('userModalTitle').textContent = 'Editar Usuário';
                document.getElementById('userId').value = currentUser.id;
                document.getElementById('userName').value = currentUser.nome;
                document.getElementById('userEmail').value = currentUser.email;
                document.getElementById('userType').value = currentUser.tipo;
                document.getElementById('userActive').checked = currentUser.ativo;
                
                // Senha não obrigatória na edição
                document.getElementById('userPassword').required = false;
                document.getElementById('userConfirmPassword').required = false;
                
                document.getElementById('userModal').style.display = 'flex';
            } else {
                showNotification(data.error || 'Erro ao carregar usuário', 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showNotification('Erro ao carregar usuário. Tente novamente.', 'error');
        })
        .finally(() => {
            // Restaurar conteúdo da página
            if (loadingEl) {
                window.location.reload();
            }
        });
}

// Fechar modal
function closeUserModal() {
    document.getElementById('userModal').style.display = 'none';
    document.getElementById('userForm').reset();
    currentUser = null;
}

// Salvar usuário
function saveUser() {
    const form = document.getElementById('userForm');
    const formData = new FormData(form);
    
    // Validações básicas
    if (!formData.get('nome').trim()) {
        showNotification('Nome é obrigatório', 'error');
        return;
    }
    
    if (!formData.get('email').trim()) {
        showNotification('E-mail é obrigatório', 'error');
        return;
    }
    
    if (!formData.get('tipo')) {
        showNotification('Tipo de usuário é obrigatório', 'error');
        return;
    }
    
    if (!isEditMode) {
        if (!formData.get('senha')) {
            showNotification('Senha é obrigatória', 'error');
            return;
        }
        
        if (formData.get('senha').length < 6) {
            showNotification('Senha deve ter pelo menos 6 caracteres', 'error');
            return;
        }
        
        if (formData.get('senha') !== formData.get('confirmar_senha')) {
            showNotification('Senhas não conferem', 'error');
            return;
        }
    }
    
    // Preparar dados para envio
    const userData = {
        nome: formData.get('nome').trim(),
        email: formData.get('email').trim(),
        tipo: formData.get('tipo'),
        ativo: formData.get('ativo') ? true : false
    };
    
    if (!isEditMode || formData.get('senha')) {
        userData.senha = formData.get('senha');
    }
    
    if (isEditMode) {
        userData.id = formData.get('id');
    }
    
    // Mostrar loading
    const loadingEl = document.querySelector('.card-body');
    if (loadingEl) {
        loadingEl.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Carregando...</span></div><p class="mt-2">Salvando usuário...</p></div>';
    }
    
    // Fazer requisição para a API
    const url = isEditMode ? '../api/usuarios.php' : '../api/usuarios.php';
    const method = isEditMode ? 'PUT' : 'POST';
    
    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(userData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message || 'Usuário salvo com sucesso!', 'success');
            closeUserModal();
            
            // Recarregar página para mostrar dados atualizados
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showNotification(data.error || 'Erro ao salvar usuário', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showNotification('Erro ao salvar usuário. Tente novamente.', 'error');
    })
    .finally(() => {
        // Restaurar conteúdo da página
        if (loadingEl) {
            window.location.reload();
        }
    });
}

// Excluir usuário
function deleteUser(userId) {
    if (confirm('Tem certeza que deseja excluir este usuário?')) {
        // Mostrar loading
        const loadingEl = document.querySelector('.card-body');
        if (loadingEl) {
            loadingEl.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Carregando...</span></div><p class="mt-2">Excluindo usuário...</p></div>';
        }
        
        // Fazer requisição para a API
        fetch(`../api/usuarios.php?id=${userId}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message || 'Usuário excluído com sucesso!', 'success');
                
                // Recarregar página
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showNotification(data.error || 'Erro ao excluir usuário', 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showNotification('Erro ao excluir usuário. Tente novamente.', 'error');
        })
        .finally(() => {
            // Restaurar conteúdo da página
            if (loadingEl) {
                window.location.reload();
            }
        });
    }
}

// Exportar usuários
function exportUsers() {
    // Mostrar loading
    const loadingEl = document.querySelector('.card-body');
    if (loadingEl) {
        loadingEl.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Carregando...</span></div><p class="mt-2">Preparando exportação...</p></div>';
    }
    
    // Buscar dados reais da API
    fetch('../api/usuarios.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Criar CSV
                let csv = 'Nome,E-mail,Tipo,Status,Criado em\n';
                data.data.forEach(usuario => {
                    csv += `"${usuario.nome}","${usuario.email}","${usuario.tipo}","${usuario.ativo ? 'Ativo' : 'Inativo}","${usuario.criado_em}"\n`;
                });
                
                // Download do arquivo
                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = 'usuarios.csv';
                link.click();
                
                showNotification('Exportação concluída!', 'success');
            } else {
                showNotification(data.error || 'Erro ao exportar usuários', 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showNotification('Erro ao exportar usuários. Tente novamente.', 'error');
        })
        .finally(() => {
            // Restaurar conteúdo da página
            if (loadingEl) {
                window.location.reload();
            }
        });
}

// Inicializar quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    // Adicionar estilos para avatar do usuário
    const style = document.createElement('style');
    style.textContent = `
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 16px;
        }
        
        .font-weight-semibold {
            font-weight: var(--font-weight-semibold);
        }
    `;
    document.head.appendChild(style);
});
</script>
