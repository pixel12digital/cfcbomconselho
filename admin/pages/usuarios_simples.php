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
        <button class="btn btn-primary" id="btnNovoUsuario">
            <i class="fas fa-plus"></i>
            Novo Usuário
        </button>
        <button class="btn btn-outline-primary" id="btnExportar">
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
                                    <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
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
                                        <button class="btn btn-edit action-btn" onclick="editUser(<?php echo $usuario['id']; ?>)" 
                                                title="Editar dados do usuário">
                                            <i class="fas fa-edit me-1"></i>Editar
                                        </button>
                                        
                                        <button class="btn btn-delete action-btn" onclick="deleteUser(<?php echo $usuario['id']; ?>)" 
                                                title="Excluir usuário">
                                            <i class="fas fa-trash me-1"></i>Excluir
                                        </button>
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
                    <label for="userType" class="form-label">Função</label>
                    <select id="userType" name="tipo" class="form-control" required>
                        <option value="">Selecione...</option>
                        <option value="admin">Administrador</option>
                        <option value="recepcao">Recepção</option>
                        <option value="instrutor_pratico">Instrutor prático</option>
                        <option value="instrutor_teorico">Instrutor teórico</option>
                        <option value="diretor_geral">Diretor geral</option>
                        <option value="diretor_ensino">Diretor de ensino</option>
                        <option value="tecnico_informatica">Técnico em informática</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="userPassword" class="form-label">Senha</label>
                    <input type="password" id="userPassword" name="senha" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="userConfirmPassword" class="form-label">Confirmar Senha</label>
                    <input type="password" id="userConfirmPassword" name="confirmar_senha" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>
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
console.log('Iniciando carregamento da pagina de usuarios...');

// Variáveis globais
let currentUser = null;
let isEditMode = false;

// Mostrar modal de criação
function showCreateUserModal() {
    console.log('Funcao showCreateUserModal chamada!');
    isEditMode = false;
    currentUser = null;
    
    document.getElementById('userModalTitle').textContent = 'Novo Usuario';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    
    document.getElementById('userModal').style.display = 'flex';
    console.log('Modal aberto com sucesso!');
}

// Fechar modal
function closeUserModal() {
    console.log('Fechando modal...');
    document.getElementById('userModal').style.display = 'none';
    document.getElementById('userForm').reset();
    currentUser = null;
    console.log('Modal fechado com sucesso!');
}

// Salvar usuário
function saveUser() {
    console.log('Funcao saveUser chamada!');
    const form = document.getElementById('userForm');
    const formData = new FormData(form);
    
    // Validações básicas
    if (!formData.get('nome').trim()) {
        alert('Nome e obrigatorio');
        return;
    }
    
    if (!formData.get('email').trim()) {
        alert('E-mail e obrigatorio');
        return;
    }
    
    if (!formData.get('tipo')) {
        alert('Tipo de usuario e obrigatorio');
        return;
    }
    
    if (!formData.get('senha')) {
        alert('Senha e obrigatoria');
        return;
    }
    
    if (formData.get('senha').length < 6) {
        alert('Senha deve ter pelo menos 6 caracteres');
        return;
    }
    
    if (formData.get('senha') !== formData.get('confirmar_senha')) {
        alert('Senhas nao conferem');
        return;
    }
    
    console.log('Validacoes passaram!');
    alert('Usuario salvo com sucesso! (simulado)');
    closeUserModal();
}

// Editar usuário
function editUser(userId) {
    console.log('Funcao editUser chamada para usuario ID: ' + userId);
    alert('Funcao de edicao chamada para usuario ID: ' + userId);
}

// Excluir usuário
function deleteUser(userId) {
    console.log('Funcao deleteUser chamada para usuario ID: ' + userId);
    if (confirm('Tem certeza que deseja excluir este usuario?')) {
        alert('Usuario excluido com sucesso! (simulado)');
    }
}

// Exportar usuários
function exportUsers() {
    console.log('Funcao exportUsers chamada!');
    alert('Exportacao iniciada! (simulado)');
}

// Garantir que as funções estejam disponíveis globalmente
window.showCreateUserModal = showCreateUserModal;
window.closeUserModal = closeUserModal;
window.saveUser = saveUser;
window.editUser = editUser;
window.deleteUser = deleteUser;
window.exportUsers = exportUsers;

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM carregado, configurando event listeners...');
    
    const btnNovoUsuario = document.getElementById('btnNovoUsuario');
    if (btnNovoUsuario) {
        btnNovoUsuario.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Botao Novo Usuario clicado');
            showCreateUserModal();
        });
    }
    
    const btnExportar = document.getElementById('btnExportar');
    if (btnExportar) {
        btnExportar.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Botao Exportar clicado');
            exportUsers();
        });
    }
    
    console.log('Event listeners configurados!');
});

console.log('Script de usuarios carregado!');
</script>
