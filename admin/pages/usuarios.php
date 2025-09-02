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
        <button class="btn btn-outline-secondary" id="btnTeste" style="margin-left: 10px;">
            <i class="fas fa-bug"></i>
            Teste Modal
        </button>
        <button class="btn btn-outline-warning" id="btnTesteEventos" style="margin-left: 10px;">
            <i class="fas fa-mouse-pointer"></i>
            Teste Eventos
        </button>
        <button class="btn btn-outline-danger" id="btnDebugModal" style="margin-left: 10px;">
            <i class="fas fa-bug"></i>
            Debug Modal
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
                                            <button class="btn btn-edit action-btn btn-editar-usuario" 
                                                    data-user-id="<?php echo $usuario['id']; ?>"
                                                    title="Editar dados do usuário">
                                                <i class="fas fa-edit me-1"></i>Editar
                                            </button>
                                            
                                            <!-- Botão de exclusão destacado -->
                                            <button class="btn btn-delete action-btn btn-excluir-usuario" 
                                                    data-user-id="<?php echo $usuario['id']; ?>"
                                                    title="ATENCAO: EXCLUIR USUARIO - Esta acao nao pode ser desfeita!">
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
<div id="userModal" class="modal-overlay">
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
// Verificar se as funções estão sendo definidas
console.log('Iniciando carregamento da pagina de usuarios...');

// Verificar se o modal existe
(function() {
    const modal = document.getElementById('userModal');
    if (modal) {
        console.log('Modal de usuário encontrado e pronto para uso');
    } else {
        console.warn('Modal de usuário não encontrado');
    }
})();

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
    
    // Mostrar campo de senha
    document.getElementById('userPassword').required = true;
    document.getElementById('userConfirmPassword').required = true;
    
    // Mostrar modal
    const modal = document.getElementById('userModal');
    modal.classList.add('show');
    
    console.log('Modal aberto com sucesso!');
}

// Garantir que a função esteja disponível globalmente
window.showCreateUserModal = showCreateUserModal;

// Mostrar modal de edição
function editUser(userId) {
    console.log('Funcao editUser chamada para usuario ID: ' + userId);
    isEditMode = true;
    
    // Buscar dados do usuário
    const loadingEl = document.querySelector('.card-body');
    if (loadingEl) {
        loadingEl.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Carregando...</span></div><p class="mt-2">Carregando dados do usuario...</p></div>';
    }
    
    console.log('Buscando dados do usuario na API...');
    
    // Buscar dados reais da API
    fetch('api/usuarios.php?id=' + userId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentUser = data.data;
                
                // Preencher formulário
                document.getElementById('userModalTitle').textContent = 'Editar Usuario';
                document.getElementById('userId').value = currentUser.id;
                document.getElementById('userName').value = currentUser.nome;
                document.getElementById('userEmail').value = currentUser.email;
                document.getElementById('userType').value = currentUser.tipo;
                document.getElementById('userActive').checked = currentUser.ativo;
                
                // Senha não obrigatória na edição
                document.getElementById('userPassword').required = false;
                document.getElementById('userConfirmPassword').required = false;
                
                // Mostrar modal
                const modal = document.getElementById('userModal');
                modal.classList.add('show');
            } else {
                showNotification(data.error || 'Erro ao carregar usuario', 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showNotification('Erro ao carregar usuario. Tente novamente.', 'error');
        })
        .finally(() => {
            // Restaurar conteúdo da página apenas se houver erro
            if (loadingEl && !currentUser) {
                console.log('Erro na edição - restaurando página...');
                window.location.reload();
            } else {
                console.log('Edição carregada com sucesso - modal permanecerá aberto');
                // Restaurar conteúdo da página sem recarregar
                if (loadingEl) {
                    loadingEl.innerHTML = '';
                }
            }
        });
}

// Garantir que a função esteja disponível globalmente
window.editUser = editUser;

// Fechar modal
function closeUserModal() {
    console.log('Fechando modal...');
    const modal = document.getElementById('userModal');
    modal.classList.remove('show');
    document.getElementById('userForm').reset();
    currentUser = null;
    console.log('Modal fechado com sucesso!');
}

// Garantir que a função esteja disponível globalmente
window.closeUserModal = closeUserModal;

// Salvar usuário
function saveUser() {
    console.log('Funcao saveUser chamada!');
    const form = document.getElementById('userForm');
    const formData = new FormData(form);
    
    // Validações básicas
    if (!formData.get('nome').trim()) {
        showNotification('Nome e obrigatorio', 'error');
        return;
    }
    
    if (!formData.get('email').trim()) {
        showNotification('E-mail e obrigatorio', 'error');
        return;
    }
    
    if (!formData.get('tipo')) {
        showNotification('Tipo de usuario e obrigatorio', 'error');
        return;
    }
    
    if (!isEditMode) {
        if (!formData.get('senha')) {
            showNotification('Senha e obrigatoria', 'error');
            return;
        }
        
        if (formData.get('senha').length < 6) {
            showNotification('Senha deve ter pelo menos 6 caracteres', 'error');
            return;
        }
        
        if (formData.get('senha') !== formData.get('confirmar_senha')) {
            showNotification('Senhas nao conferem', 'error');
            return;
        }
    }
    
    console.log('Validacoes passaram, preparando dados...');
    
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
    const url = isEditMode ? 'api/usuarios.php' : 'api/usuarios.php';
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
            setTimeout(function() {
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

// Garantir que a função esteja disponível globalmente
window.saveUser = saveUser;

// Excluir usuário
function deleteUser(userId) {
    console.log('Funcao deleteUser chamada para usuario ID: ' + userId);
    
    if (!userId || userId === '' || userId === 0) {
        console.error('ID de usuario invalido:', userId);
        showNotification('ID de usuário inválido', 'error');
        return;
    }
    
    if (confirm('⚠️ ATENÇÃO!\n\nTem certeza que deseja excluir este usuário?\n\nEsta ação NÃO pode ser desfeita!')) {
        console.log('Confirmacao recebida, excluindo usuario ID:', userId);
        
        // Mostrar loading
        const loadingEl = document.querySelector('.card-body');
        if (loadingEl) {
            loadingEl.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Carregando...</span></div><p class="mt-2">Excluindo usuario...</p></div>';
        }
        
        // URL da API
        const apiUrl = 'api/usuarios.php?id=' + encodeURIComponent(userId);
        console.log('Fazendo requisicao DELETE para:', apiUrl);
        
        // Fazer requisição para a API
        fetch(apiUrl, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(response => {
            console.log('Resposta recebida. Status:', response.status);
            
            // Verificar se a resposta é válida
            if (!response.ok) {
                throw new Error(`HTTP Error: ${response.status} - ${response.statusText}`);
            }
            
            // Verificar se o content-type é JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Resposta não é JSON válido');
            }
            
            return response.json();
        })
        .then(data => {
            console.log('Dados recebidos da API:', data);
            
            if (data.success) {
                console.log('Usuario excluido com sucesso');
                showNotification(data.message || 'Usuário excluído com sucesso!', 'success');
                
                // Recarregar página após sucesso
                setTimeout(function() {
                    console.log('Recarregando pagina...');
                    window.location.reload();
                }, 1500);
            } else {
                console.error('Erro retornado pela API:', data);
                let errorMessage = data.error || 'Erro desconhecido ao excluir usuário';
                
                // Melhorar mensagens de erro baseadas no código
                switch (data.code) {
                    case 'NOT_LOGGED_IN':
                        errorMessage = 'Sessão expirada. Faça login novamente.';
                        setTimeout(() => window.location.href = 'index.php', 2000);
                        break;
                    case 'NOT_ADMIN':
                        errorMessage = 'Acesso negado. Apenas administradores podem excluir usuários.';
                        break;
                    case 'USER_NOT_FOUND':
                        errorMessage = 'Usuário não encontrado.';
                        break;
                    case 'SELF_DELETE':
                        errorMessage = 'Você não pode excluir o próprio usuário.';
                        break;
                    case 'HAS_CFCS':
                        errorMessage = 'Este usuário possui CFCs vinculados. Remova os vínculos antes de excluir.';
                        break;
                }
                
                showNotification(errorMessage, 'error');
            }
        })
        .catch(error => {
            console.error('Erro na requisicao:', error);
            
            let errorMessage = 'Erro de conexão ao excluir usuário.';
            
            if (error.message.includes('HTTP Error: 401')) {
                errorMessage = 'Sessão expirada. Faça login novamente.';
                setTimeout(() => window.location.href = 'index.php', 2000);
            } else if (error.message.includes('HTTP Error: 403')) {
                errorMessage = 'Acesso negado. Você não tem permissão para esta ação.';
            } else if (error.message.includes('HTTP Error: 404')) {
                errorMessage = 'Usuário não encontrado.';
            } else if (error.message.includes('HTTP Error: 500')) {
                errorMessage = 'Erro interno do servidor. Tente novamente.';
            } else if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
                errorMessage = 'Erro de conexão. Verifique sua internet e tente novamente.';
            }
            
            showNotification(errorMessage, 'error');
        })
        .finally(() => {
            console.log('Finalizando operacao de exclusao');
            
            // Restaurar conteúdo da página se ainda estiver em loading
            if (loadingEl && loadingEl.innerHTML.includes('Excluindo usuario')) {
                setTimeout(() => {
                    console.log('Recarregando pagina no finally...');
                    window.location.reload();
                }, 2000);
            }
        });
    } else {
        console.log('Exclusao cancelada pelo usuario');
    }
}

// Garantir que a função esteja disponível globalmente
window.deleteUser = deleteUser;

// Exportar usuários
function exportUsers() {
    console.log('Funcao exportUsers chamada!');
    
    // Mostrar loading
    const loadingEl = document.querySelector('.card-body');
    if (loadingEl) {
        loadingEl.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Carregando...</span></div><p class="mt-2">Preparando exportacao...</p></div>';
    }
    
    console.log('Buscando dados dos usuarios na API...');
    
    // Buscar dados reais da API
    fetch('api/usuarios.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Criar CSV
                let csv = 'Nome,E-mail,Tipo,Status,Criado em\n';
                data.data.forEach(usuario => {
                    csv += '"' + usuario.nome + '","' + usuario.email + '","' + usuario.tipo + '","' + (usuario.ativo ? 'Ativo' : 'Inativo') + '","' + usuario.criado_em + '"\n';
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

// Garantir que a função esteja disponível globalmente
window.exportUsers = exportUsers;

// Função para mostrar notificações
function showNotification(message, type = 'info') {
    console.log('Mostrando notificacao: ' + message + ' (tipo: ' + type + ')');
    
    // Criar elemento de notificação
    const notification = document.createElement('div');
    notification.className = 'alert alert-' + type + ' alert-dismissible fade show';
    notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 500px;';
    
    notification.innerHTML = message + '<button type="button" class="btn-close" onclick="this.parentElement.remove()">x</button>';
    
    // Adicionar ao body
    document.body.appendChild(notification);
    
    // Remover automaticamente após 5 segundos
    setTimeout(function() {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
    
    console.log('Notificacao criada e exibida!');
}

// Garantir que a função esteja disponível globalmente
window.showNotification = showNotification;

// Inicializar quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM carregado, verificando funcoes...');
    
    // Verificar se o modal está disponível
    const modal = document.getElementById('userModal');
    if (modal) {
        console.log('Modal de usuário disponível e pronto para uso');
    } else {
        console.warn('Modal de usuário não encontrado');
    }
    
    // Verificar se as funções estão definidas
    if (typeof showCreateUserModal === 'function') {
        console.log('Funcao showCreateUserModal esta disponivel');
    } else {
        console.error('Funcao showCreateUserModal NAO esta disponivel');
    }
    
    if (typeof editUser === 'function') {
        console.log('Funcao editUser esta disponivel');
    } else {
        console.error('Funcao editUser NAO esta disponivel');
    }
    
    if (typeof deleteUser === 'function') {
        console.log('Funcao deleteUser esta disponivel');
    } else {
        console.error('Funcao deleteUser NAO esta disponivel');
    }
    
    // Configurar event listeners para botões de exclusão
    const deleteButtons = document.querySelectorAll('.btn-excluir-usuario');
    console.log('Encontrados ' + deleteButtons.length + ' botoes de exclusao');
    
    deleteButtons.forEach(function(button, index) {
        const userId = button.getAttribute('data-user-id');
        console.log('Configurando botao de exclusao ' + (index + 1) + ' para usuario ID: ' + userId);
        
        // Adicionar event listener
        button.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            
            const userIdFromButton = this.getAttribute('data-user-id');
            console.log('Botao de exclusao clicado para usuario ID: ' + userIdFromButton);
            
            if (typeof deleteUser === 'function') {
                deleteUser(userIdFromButton);
            } else {
                console.error('Funcao deleteUser nao esta disponivel!');
                showNotification('Erro: Função de exclusão não está disponível. Recarregue a página.', 'error');
            }
        });
    });
    
    // Configurar event listeners para botões de edição
    const editButtons = document.querySelectorAll('.btn-editar-usuario');
    console.log('Encontrados ' + editButtons.length + ' botoes de edicao');
    
    editButtons.forEach(function(button, index) {
        const userId = button.getAttribute('data-user-id');
        console.log('Configurando botao de edicao ' + (index + 1) + ' para usuario ID: ' + userId);
        
        // Adicionar event listener
        button.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            
            const userIdFromButton = this.getAttribute('data-user-id');
            console.log('Botao de edicao clicado para usuario ID: ' + userIdFromButton);
            
            if (typeof editUser === 'function') {
                editUser(userIdFromButton);
            } else {
                console.error('Funcao editUser nao esta disponivel!');
                showNotification('Erro: Função de edição não está disponível. Recarregue a página.', 'error');
            }
        });
    });
    
    // Adicionar event listeners para os botões
    const novoUsuarioBtn = document.getElementById('btnNovoUsuario');
    if (novoUsuarioBtn) {
        console.log('Adicionando event listener para botao Novo Usuario');
        console.log('Botao encontrado:', novoUsuarioBtn);
        console.log('Botao ID:', novoUsuarioBtn.id);
        console.log('Botao HTML:', novoUsuarioBtn.outerHTML);
        
        novoUsuarioBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Botao Novo Usuario clicado via event listener');
            console.log('Evento:', e);
            console.log('Target:', e.target);
            
            if (typeof showCreateUserModal === 'function') {
                console.log('Chamando showCreateUserModal...');
                showCreateUserModal();
            } else {
                console.error('Funcao showCreateUserModal ainda nao esta disponivel');
                alert('Erro: Funcao nao disponivel. Tente recarregar a pagina.');
            }
        });
        
        console.log('Event listener adicionado com sucesso ao botao Novo Usuario');
    } else {
        console.error('Botao Novo Usuario NAO encontrado!');
        console.log('Procurando por botao com ID btnNovoUsuario...');
        const todosBotoes = document.querySelectorAll('button');
        console.log('Total de botoes encontrados:', todosBotoes.length);
        todosBotoes.forEach((btn, index) => {
            console.log('Botao ' + index + ':', btn.id, btn.textContent.trim());
        });
    }

    const btnExportar = document.getElementById('btnExportar');
    if (btnExportar) {
        console.log('Adicionando event listener para botao Exportar');
        btnExportar.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Botao Exportar clicado via event listener');
            if (typeof exportUsers === 'function') {
                exportUsers();
            } else {
                console.error('Funcao exportUsers ainda nao esta disponivel');
                alert('Erro: Funcao nao disponivel. Tente recarregar a pagina.');
            }
        });
    }

    const btnTeste = document.getElementById('btnTeste');
    if (btnTeste) {
        console.log('Adicionando event listener para botao Teste Modal');
        btnTeste.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Botao Teste Modal clicado via event listener');
            
            // Testar especificamente o modal
            console.log('Testando abertura do modal...');
            if (typeof showCreateUserModal === 'function') {
                showCreateUserModal();
                console.log('showCreateUserModal executado com sucesso');
                
                // Verificar se o modal está visível
                setTimeout(function() {
                    const modal = document.getElementById('userModal');
                    if (modal) {
                        console.log('Modal encontrado:', modal);
                        console.log('Modal display:', modal.style.display);
                        console.log('Modal visibility:', modal.style.visibility);
                        console.log('Modal opacity:', modal.style.opacity);
                        console.log('Modal offsetHeight:', modal.offsetHeight);
                        console.log('Modal offsetWidth:', modal.offsetWidth);
                        
                        if (modal.style.display === 'flex' || modal.style.display === 'block') {
                            console.log('Modal deve estar visível!');
                        } else {
                            console.log('Modal NAO esta visivel!');
                        }
                    } else {
                        console.error('Modal NAO encontrado!');
                    }
                }, 100);
            } else {
                console.error('showCreateUserModal NAO disponivel');
            }
        });
    }

    const btnTesteEventos = document.getElementById('btnTesteEventos');
    if (btnTesteEventos) {
        console.log('Adicionando event listener para botao Teste Eventos');
        btnTesteEventos.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Botao Teste Eventos clicado via event listener');
            alert('Teste de eventos funcionando!');
        });
    }
    
    const btnDebugModal = document.getElementById('btnDebugModal');
    if (btnDebugModal) {
        console.log('Adicionando event listener para botao Debug Modal');
        btnDebugModal.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Botao Debug Modal clicado via event listener');
            
            const modal = document.getElementById('userModal');
            if (modal) {
                console.log('=== DEBUG COMPLETO DO MODAL ===');
                console.log('Modal elemento:', modal);
                console.log('Modal classes:', modal.className);
                console.log('Modal tem classe show:', modal.classList.contains('show'));
                
                const styles = window.getComputedStyle(modal);
                console.log('Modal CSS computado:');
                console.log('- display:', styles.display);
                console.log('- visibility:', styles.visibility);
                console.log('- opacity:', styles.opacity);
                console.log('- z-index:', styles.zIndex);
                console.log('- pointer-events:', styles.pointerEvents);
                
                // Forçar abertura do modal para teste
                console.log('Forçando abertura do modal para teste...');
                modal.classList.add('show');
                
                setTimeout(function() {
                    console.log('Modal após forçar abertura:');
                    console.log('Classes:', modal.className);
                    console.log('Tem show:', modal.classList.contains('show'));
                    
                    const newStyles = window.getComputedStyle(modal);
                    console.log('Novos estilos:');
                    console.log('- display:', newStyles.display);
                    console.log('- visibility:', newStyles.visibility);
                    console.log('- opacity:', newStyles.opacity);
                    
                    // Verificar se está realmente visível
                    if (newStyles.display === 'flex' && newStyles.visibility === 'visible') {
                        console.log('✅ Modal está visível!');
                        alert('Modal aberto! Agora teste se ele fecha automaticamente.');
                    } else {
                        console.log('❌ Modal ainda não está visível!');
                    }
                }, 100);
            } else {
                console.error('Modal não encontrado!');
            }
        });
    }
    
    // Adicionar event listeners para botões de ação na tabela
    const btnEditarUsuarios = document.querySelectorAll('.btn-editar-usuario');
    btnEditarUsuarios.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const userId = this.getAttribute('data-user-id');
            console.log('Botao Editar clicado para usuario ID: ' + userId);
            if (typeof editUser === 'function') {
                editUser(userId);
            } else {
                console.error('Funcao editUser ainda nao esta disponivel');
                alert('Erro: Funcao nao disponivel. Tente recarregar a pagina.');
            }
        });
    });
    
    const btnExcluirUsuarios = document.querySelectorAll('.btn-excluir-usuario');
    btnExcluirUsuarios.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const userId = this.getAttribute('data-user-id');
            console.log('Botao Excluir clicado para usuario ID: ' + userId);
            if (typeof deleteUser === 'function') {
                deleteUser(userId);
            } else {
                console.error('Funcao deleteUser ainda nao esta disponivel');
                alert('Erro: Funcao nao disponivel. Tente recarregar a pagina.');
            }
        });
    });
    
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
        
        /* Estilos específicos para o modal */
        #userModal {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            background-color: rgba(0, 0, 0, 0.8) !important;
            z-index: 999999 !important;
            display: none !important;
            align-items: center !important;
            justify-content: center !important;
            visibility: hidden !important;
            opacity: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            pointer-events: none !important;
            transition: all 0.3s ease !important;
        }
        
        #userModal.show {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            pointer-events: auto !important;
        }
        
        #userModal .modal {
            background: white !important;
            border-radius: 8px !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5) !important;
            max-width: 500px !important;
            width: 90% !important;
            max-height: 90vh !important;
            overflow-y: auto !important;
            position: relative !important;
            margin: 20px !important;
            z-index: 1000000 !important;
            pointer-events: auto !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        #userModal .modal-header {
            padding: 20px !important;
            border-bottom: 1px solid #e5e7eb !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            background: white !important;
            color: #000 !important;
        }
        
        #userModal .modal-body {
            padding: 20px !important;
            background: white !important;
            color: #000 !important;
        }
        
        #userModal .modal-footer {
            padding: 20px !important;
            border-top: 1px solid #e5e7eb !important;
            display: flex !important;
            gap: 10px !important;
            justify-content: flex-end !important;
            background: white !important;
            color: #000 !important;
        }
        
        #userModal .form-group {
            margin-bottom: 15px !important;
        }
        
        #userModal .form-label {
            display: block !important;
            margin-bottom: 5px !important;
            font-weight: 500 !important;
            color: #000 !important;
        }
        
        #userModal .form-control {
            width: 100% !important;
            padding: 8px 12px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 4px !important;
            font-size: 14px !important;
            background: white !important;
            color: #000 !important;
        }
        
        #userModal .btn {
            padding: 8px 16px !important;
            border-radius: 4px !important;
            cursor: pointer !important;
            font-size: 14px !important;
        }
        
        #userModal .btn-primary {
            background: #3b82f6 !important;
            color: white !important;
            border: none !important;
        }
        
        #userModal .btn-secondary {
            background: #f9fafb !important;
            color: #374151 !important;
            border: 1px solid #d1d5db !important;
        }
        
        #userModal .modal-close {
            background: none !important;
            border: none !important;
            font-size: 20px !important;
            cursor: pointer !important;
            color: #6b7280 !important;
            padding: 5px !important;
            border-radius: 4px !important;
        }
        
        /* Garantir que o título seja visível */
        #userModal .modal-title {
            color: #000 !important;
            font-weight: bold !important;
            font-size: 18px !important;
        }
        
        /* Garantir que o texto de ajuda seja visível */
        #userModal .form-text {
            color: #6b7280 !important;
            font-size: 12px !important;
        }
        
        /* Forçar visibilidade de todos os elementos filhos */
        #userModal.show * {
            visibility: visible !important;
            opacity: 1 !important;
        }
    `;
    document.head.appendChild(style);
    
    console.log('Pagina de usuarios inicializada com sucesso!');
});

// Verificação adicional após carregamento completo
window.addEventListener('load', function() {
    console.log('Página completamente carregada');
    console.log('Verificação final das funções:');
    console.log('- showCreateUserModal:', typeof showCreateUserModal);
    console.log('- editUser:', typeof editUser);
    console.log('- deleteUser:', typeof deleteUser);
    console.log('- closeUserModal:', typeof closeUserModal);
    console.log('- saveUser:', typeof saveUser);
    
    // Verificar se todas as funções estão disponíveis
    const funcoes = ['showCreateUserModal', 'editUser', 'deleteUser', 'closeUserModal', 'saveUser', 'exportUsers', 'showNotification'];
    const funcoesFaltando = funcoes.filter(f => typeof window[f] !== 'function');
    
    if (funcoesFaltando.length > 0) {
        console.error('Funções faltando:', funcoesFaltando);
        alert('Atenção: As seguintes funções não estão funcionando: ' + funcoesFaltando.join(', ') + '. Tente recarregar a página.');
    } else {
        console.log('Todas as funções estão disponíveis!');
    }
    
    // 🛡️ SISTEMA DE PROTEÇÃO CONTRA FECHAMENTO AUTOMÁTICO
    console.log('🔒 Iniciando sistema de proteção do modal...');
    
    // Proteger a função closeUserModal original
    const originalCloseUserModal = window.closeUserModal;
    
         // Substituir por versão protegida
     window.closeUserModal = function() {
         console.log('🔒 Tentativa de fechar modal interceptada!');
         console.log('🔒 Stack trace:', new Error().stack);
         
         // Verificar se é uma chamada legítima (do usuário)
         const caller = arguments.callee.caller;
         if (caller && caller.name === 'saveUser') {
             console.log('✅ Fechamento legítimo - salvando usuário');
             return originalCloseUserModal();
         }
         
         // Verificar se é uma chamada direta do usuário (clique no botão)
         if (event && event.target && event.target.onclick) {
             console.log('✅ Fechamento legítimo - clique do usuário');
             return originalCloseUserModal();
         }
         
         // Verificar se é uma chamada do HTML (onclick)
         const stack = new Error().stack;
         if (stack.includes('onclick') || stack.includes('HTMLButtonElement')) {
             console.log('✅ Fechamento legítimo - onclick do HTML');
             return originalCloseUserModal();
         }
         
         // Bloquear fechamentos automáticos
         console.log('🚫 Fechamento automático bloqueado!');
         console.log('🚫 Modal permanecerá aberto');
         
         // Forçar modal a permanecer aberto
         const modal = document.getElementById('userModal');
         if (modal) {
             modal.classList.add('show');
             modal.style.display = 'flex';
             modal.style.visibility = 'visible';
             modal.style.opacity = '1';
             modal.style.pointerEvents = 'auto';
         }
         
         return false;
     };
    
         // Proteger contra remoção da classe 'show' apenas se não for fechamento manual
     const modal = document.getElementById('userModal');
     if (modal) {
         // Monitorar mudanças na classe
         const observer = new MutationObserver(function(mutations) {
             mutations.forEach(function(mutation) {
                 if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                     if (!modal.classList.contains('show')) {
                         // Verificar se é fechamento manual
                         const stack = new Error().stack;
                         if (stack.includes('onclick') || stack.includes('HTMLButtonElement') || stack.includes('closeUserModal')) {
                             console.log('✅ Fechamento manual detectado - permitindo');
                             return;
                         }
                         
                         console.log('🚫 Tentativa de remover classe "show" detectada!');
                         console.log('🚫 Restaurando classe "show"');
                         modal.classList.add('show');
                     }
                 }
             });
         });
         
         observer.observe(modal, {
             attributes: true,
             attributeFilter: ['class']
         });
         
         console.log('🔒 Observer configurado para proteger classe "show"');
     }
     
     // Proteger contra mudanças de estilo apenas se não for fechamento manual
     let modalProtectionInterval = setInterval(function() {
         const modal = document.getElementById('userModal');
         if (modal && modal.classList.contains('show')) {
             // Verificar se modal ainda está visível
             const styles = window.getComputedStyle(modal);
             if (styles.display !== 'flex' || styles.visibility !== 'visible') {
                 // Verificar se é fechamento manual
                 const stack = new Error().stack;
                 if (stack.includes('onclick') || stack.includes('HTMLButtonElement') || stack.includes('closeUserModal')) {
                     console.log('✅ Fechamento manual detectado - permitindo');
                     return;
                 }
                 
                 console.log('🚫 Modal perdeu visibilidade - restaurando...');
                 modal.style.display = 'flex';
                 modal.style.visibility = 'visible';
                 modal.style.opacity = '1';
                 modal.style.pointerEvents = 'auto';
             }
         }
     }, 1000);
    
    console.log('🔒 Sistema de proteção ativado!');
    console.log('🔒 Modal não fechará automaticamente');
});

// Timeout adicional para garantir que as funções sejam definidas
setTimeout(function() {
    console.log('Verificação de timeout das funções:');
    const funcoes = ['showCreateUserModal', 'editUser', 'deleteUser', 'closeUserModal', 'saveUser', 'exportUsers', 'showNotification'];
    funcoes.forEach(f => {
        if (typeof window[f] === 'function') {
            console.log(f + ': Disponível');
        } else {
            console.error(f + ': NAO disponivel');
        }
    });
}, 2000);
</script>
