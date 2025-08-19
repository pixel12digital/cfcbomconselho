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
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-outline-primary" onclick="editUser(<?php echo $usuario['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(<?php echo $usuario['id']; ?>)">
                                                <i class="fas fa-trash"></i>
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
    loading.show(document.querySelector('.card-body'), 'Carregando dados do usuário...');
    
    // Simular busca (substituir por chamada AJAX real)
    setTimeout(() => {
        loading.hide(document.querySelector('.card-body'));
        
        // Dados simulados
        currentUser = {
            id: userId,
            nome: 'Usuário Exemplo',
            email: 'usuario@exemplo.com',
            tipo: 'instrutor',
            ativo: true
        };
        
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
    }, 1000);
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
        notifications.error('Nome é obrigatório');
        return;
    }
    
    if (!formData.get('email').trim()) {
        notifications.error('E-mail é obrigatório');
        return;
    }
    
    if (!validation.validateEmail(formData.get('email'))) {
        notifications.error('E-mail inválido');
        return;
    }
    
    if (!formData.get('tipo')) {
        notifications.error('Tipo de usuário é obrigatório');
        return;
    }
    
    if (!isEditMode) {
        if (!formData.get('senha')) {
            notifications.error('Senha é obrigatória');
            return;
        }
        
        if (formData.get('senha').length < 6) {
            notifications.error('Senha deve ter pelo menos 6 caracteres');
            return;
        }
        
        if (formData.get('senha') !== formData.get('confirmar_senha')) {
            notifications.error('Senhas não conferem');
            return;
        }
    }
    
    // Simular salvamento
    loading.showGlobal('Salvando usuário...');
    
    setTimeout(() => {
        loading.hideGlobal();
        
        if (isEditMode) {
            notifications.success('Usuário atualizado com sucesso!');
        } else {
            notifications.success('Usuário criado com sucesso!');
        }
        
        closeUserModal();
        
        // Recarregar página para mostrar dados atualizados
        setTimeout(() => {
            window.location.reload();
        }, 1500);
    }, 2000);
}

// Excluir usuário
function deleteUser(userId) {
    confirm('Tem certeza que deseja excluir este usuário?', (confirmed) => {
        if (confirmed) {
            loading.showGlobal('Excluindo usuário...');
            
            setTimeout(() => {
                loading.hideGlobal();
                notifications.success('Usuário excluído com sucesso!');
                
                // Recarregar página
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            }, 1000);
        }
    });
}

// Exportar usuários
function exportUsers() {
    loading.showGlobal('Preparando exportação...');
    
    setTimeout(() => {
        loading.hideGlobal();
        notifications.success('Exportação concluída!');
        
        // Simular download
        const link = document.createElement('a');
        link.href = 'data:text/csv;charset=utf-8,Usuários do Sistema';
        link.download = 'usuarios.csv';
        link.click();
    }, 2000);
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
