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

<!-- CSS específico para corrigir sobreposição -->
<style>
/* Estilos específicos para a página de usuários */
.user-table {
    margin-top: var(--spacing-lg);
}

.user-actions {
    display: flex;
    gap: var(--spacing-xs);
}

.user-actions .btn {
    padding: 4px 8px;
    font-size: 12px;
}

.user-badge {
    font-size: 11px;
    padding: 4px 6px;
}

/* CORREÇÃO CRÍTICA: Eliminar "tabela dentro de tabela" */
.card-header {
    display: block !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: flex-start !important;
    justify-content: center !important;
    padding: var(--spacing-lg) var(--spacing-xl) !important;
    background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%) !important;
    border-bottom: 1px solid var(--gray-200) !important;
    font-weight: var(--font-weight-semibold) !important;
    color: var(--gray-700) !important;
    border-radius: var(--border-radius-lg) var(--border-radius-lg) 0 0 !important;
    margin: 0 !important;
    width: 100% !important;
    box-sizing: border-box !important;
}

.card-header h3 {
    margin: 0 !important;
    padding: 0 !important;
    font-size: var(--font-size-xl) !important;
    font-weight: var(--font-weight-bold) !important;
    color: var(--gray-800) !important;
    line-height: 1.2 !important;
}

/* Garantir que card-header não herde estilos de tabela */
.card-header,
.card-header * {
    display: block !important;
    display: flex !important;
    box-sizing: border-box !important;
}

.card-header h3 {
    display: block !important;
}

/* Garantir que a tabela real tenha estilos corretos */
.table-container {
    overflow-x: auto;
    max-width: 100%;
    background: var(--white);
    border-radius: 0 0 var(--border-radius-lg) var(--border-radius-lg);
    box-shadow: none;
}

.table {
    width: 100%;
    min-width: 600px;
    table-layout: fixed;
    border-collapse: separate !important;
    border-spacing: 0 !important;
    margin: 0 !important;
    border: none !important;
}

.table th {
    background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%) !important;
    padding: 12px 8px !important;
    text-align: left !important;
    font-weight: var(--font-weight-semibold) !important;
    color: var(--gray-700) !important;
    border-bottom: 2px solid var(--gray-200) !important;
    border-top: none !important;
    border-left: none !important;
    border-right: none !important;
}

.table th:first-child {
    border-top-left-radius: 0 !important;
}

.table th:last-child {
    border-top-right-radius: 0 !important;
}

.table td {
    padding: 12px 8px !important;
    border-bottom: 1px solid var(--gray-200) !important;
    border-left: none !important;
    border-right: none !important;
    border-top: none !important;
    color: var(--gray-800) !important;
    vertical-align: middle !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
}

.table tbody tr:hover {
    background-color: var(--gray-50) !important;
}

.table tbody tr:last-child td {
    border-bottom: none !important;
}

.table tbody tr:last-child td:first-child {
    border-bottom-left-radius: var(--border-radius-lg) !important;
}

.table tbody tr:last-child td:last-child {
    border-bottom-right-radius: var(--border-radius-lg) !important;
}

/* Larguras específicas das colunas */
.table th:nth-child(1),
.table td:nth-child(1) {
    width: 35% !important;
    min-width: 150px !important;
}

.table th:nth-child(2),
.table td:nth-child(2) {
    width: 20% !important;
    min-width: 100px !important;
}

.table th:nth-child(3),
.table td:nth-child(3) {
    width: 15% !important;
    min-width: 80px !important;
}

.table th:nth-child(4),
.table td:nth-child(4) {
    width: 15% !important;
    min-width: 80px !important;
}

.table th:nth-child(5),
.table td:nth-child(5) {
    width: 15% !important;
    min-width: 100px !important;
}

/* Botões de ação na tabela */
.action-buttons-container {
    display: flex !important;
    gap: 8px !important;
    align-items: center !important;
    justify-content: center !important;
}

.action-btn {
    width: 32px !important;
    height: 24px !important;
    padding: 0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    border-radius: 4px !important;
    font-size: 12px !important;
    min-width: 32px !important;
    max-width: 32px !important;
}

/* =====================================================
   RESPONSIVIDADE MOBILE - TABELA DE USUÁRIOS
   ===================================================== */

/* FORÇAR RESPONSIVIDADE - CSS MAIS ESPECÍFICO */
@media screen and (max-width: 768px), screen and (max-width: 900px) {
    /* Container da tabela responsivo */
    .card .card-body .table-container {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch !important;
        border-radius: 8px !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
        max-width: 100% !important;
        width: 100% !important;
    }
    
    /* Tabela mobile - FORÇAR LARGURA */
    .card .card-body .table-container .table {
        min-width: 600px !important;
        width: 600px !important;
        font-size: 14px !important;
        table-layout: fixed !important;
    }
    
    /* Cabeçalho da tabela mobile */
    .card .card-body .table-container .table th {
        padding: 8px 6px !important;
        font-size: 12px !important;
        white-space: nowrap !important;
        width: auto !important;
    }
    
    /* Células da tabela mobile */
    .card .card-body .table-container .table td {
        padding: 8px 6px !important;
        font-size: 13px !important;
        white-space: nowrap !important;
        width: auto !important;
    }
    
    /* Ajustar larguras das colunas para mobile */
    .table th:nth-child(1),
    .table td:nth-child(1) {
        width: 30% !important;
        min-width: 120px !important;
    }
    
    .table th:nth-child(2),
    .table td:nth-child(2) {
        width: 25% !important;
        min-width: 100px !important;
    }
    
    .table th:nth-child(3),
    .table td:nth-child(3) {
        width: 15% !important;
        min-width: 70px !important;
    }
    
    .table th:nth-child(4),
    .table td:nth-child(4) {
        width: 15% !important;
        min-width: 70px !important;
    }
    
    .table th:nth-child(5),
    .table td:nth-child(5) {
        width: 15% !important;
        min-width: 80px !important;
    }
    
    /* Botões de ação mobile */
    .action-buttons-container {
        gap: 4px !important;
        flex-wrap: wrap !important;
    }
    
    .action-btn {
        width: 28px !important;
        height: 22px !important;
        font-size: 11px !important;
        min-width: 28px !important;
        max-width: 28px !important;
    }
    
    /* Card mobile */
    .card {
        margin: 0 !important;
        border-radius: var(--border-radius-lg) !important;
        box-shadow: var(--shadow-sm) !important;
    }
    
    .card-header {
        padding: var(--spacing-md) !important;
    }
    
    .card-header h3 {
        font-size: var(--font-size-lg) !important;
    }
    
    .card-body {
        padding: var(--spacing-md) !important;
    }
}

@media screen and (max-width: 480px), screen and (max-width: 600px) {
    /* Mobile pequeno - FORÇAR layout em cards */
    .card .card-body .table-container {
        display: none !important;
        overflow: visible !important;
    }
    
    .card .card-body .table-container .table {
        display: none !important;
    }
    
    /* Cards para mobile pequeno - FORÇAR VISIBILIDADE */
    .card .card-body .mobile-user-cards {
        display: block !important;
        width: 100% !important;
    }
    
    .mobile-user-card {
        background: var(--white) !important;
        border: 1px solid var(--gray-200) !important;
        border-radius: var(--border-radius-lg) !important;
        padding: var(--spacing-md) !important;
        margin-bottom: var(--spacing-md) !important;
        box-shadow: var(--shadow-sm) !important;
    }
    
    .mobile-user-card:last-child {
        margin-bottom: 0 !important;
    }
    
    .mobile-user-header {
        display: flex !important;
        justify-content: space-between !important;
        align-items: flex-start !important;
        margin-bottom: var(--spacing-sm) !important;
    }
    
    .mobile-user-name {
        font-weight: var(--font-weight-semibold) !important;
        color: var(--gray-800) !important;
        font-size: var(--font-size-md) !important;
        margin: 0 !important;
    }
    
    .mobile-user-badge {
        font-size: 11px !important;
        padding: 4px 8px !important;
        border-radius: var(--border-radius) !important;
    }
    
    .mobile-user-info {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: var(--spacing-sm) !important;
        margin-bottom: var(--spacing-sm) !important;
    }
    
    .mobile-user-field {
        display: flex !important;
        flex-direction: column !important;
    }
    
    .mobile-user-label {
        font-size: 11px !important;
        color: var(--gray-500) !important;
        font-weight: var(--font-weight-medium) !important;
        margin-bottom: 2px !important;
    }
    
    .mobile-user-value {
        font-size: 13px !important;
        color: var(--gray-700) !important;
        font-weight: var(--font-weight-normal) !important;
    }
    
    .mobile-user-actions {
        display: flex !important;
        gap: var(--spacing-xs) !important;
        justify-content: flex-end !important;
        margin-top: var(--spacing-sm) !important;
    }
    
    .mobile-user-actions .action-btn {
        width: 32px !important;
        height: 28px !important;
        font-size: 12px !important;
    }
}

.action-btn i {
    margin: 0 !important;
    font-size: 12px !important;
}

/* Responsividade da tabela */
@media (max-width: 1200px) {
    .table {
        min-width: 500px !important;
    }
    
    .table th,
    .table td {
        padding: 8px 6px !important;
        font-size: 14px !important;
    }
}

@media (max-width: 768px) {
    .table {
        min-width: 400px !important;
    }
    
    .table th,
    .table td {
        padding: 6px 4px !important;
        font-size: 12px !important;
    }
    
    .badge {
        font-size: 10px !important;
        padding: 4px 6px !important;
    }
    
    .action-btn {
        width: 28px !important;
        height: 20px !important;
        min-width: 28px !important;
        max-width: 28px !important;
    }
    
    .action-btn i {
        font-size: 10px !important;
    }
}
</style>

<!-- Header da Página -->
<div class="page-header-management">
    <div class="header-content">
        <h1 class="page-title">Gerenciar Usuários</h1>
        <p class="page-subtitle">Cadastro e gerenciamento de usuários do sistema</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" id="btnNovoUsuario" title="Novo Usuário">
            <i class="fas fa-plus"></i>
        </button>
        <button class="btn btn-outline-primary" id="btnExportar" title="Exportar Dados">
            <i class="fas fa-download"></i>
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
                                        <div class="font-weight-semibold"><?php echo htmlspecialchars($usuario['nome']); ?></div>
                                    </td>
                                    <td>
                                        <?php 
                                        $tipoDisplay = [
                                            'admin' => ['text' => 'Administrador', 'class' => 'danger'],
                                            'secretaria' => ['text' => 'Atendente CFC', 'class' => 'primary'],
                                            'instrutor' => ['text' => 'Instrutor', 'class' => 'warning'],
                                            'aluno' => ['text' => 'Aluno', 'class' => 'info']
                                        ];
                                        $tipoInfo = $tipoDisplay[$usuario['tipo']] ?? ['text' => ucfirst($usuario['tipo']), 'class' => 'secondary'];
                                        ?>
                                        <span class="badge badge-<?php echo $tipoInfo['class']; ?>">
                                            <?php echo $tipoInfo['text']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $usuario['ativo'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $usuario['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($usuario['criado_em'])); ?></td>
                                    <td>
                                        <div class="action-buttons-container">
                                            <!-- Botão de edição -->
                                            <button class="btn btn-edit action-btn btn-editar-usuario" 
                                                    data-user-id="<?php echo $usuario['id']; ?>"
                                                    title="Editar dados do usuário">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <!-- Botão de redefinir senha -->
                                            <button class="btn btn-warning action-btn btn-redefinir-senha" 
                                                    data-user-id="<?php echo $usuario['id']; ?>"
                                                    data-user-name="<?php echo htmlspecialchars($usuario['nome']); ?>"
                                                    data-user-email="<?php echo htmlspecialchars($usuario['email']); ?>"
                                                    title="Redefinir senha do usuário">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            
                                            <!-- Botão de exclusão destacado -->
                                            <button class="btn btn-delete action-btn btn-excluir-usuario" 
                                                    data-user-id="<?php echo $usuario['id']; ?>"
                                                    title="ATENCAO: EXCLUIR USUARIO - Esta acao nao pode ser desfeita!">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Layout Mobile em Cards (oculto por padrão) -->
                <div class="mobile-user-cards" style="display: none;">
                    <?php foreach ($usuarios as $usuario): ?>
                        <div class="mobile-user-card">
                            <div class="mobile-user-header">
                                <h4 class="mobile-user-name"><?php echo htmlspecialchars($usuario['nome']); ?></h4>
                                <?php 
                                $tipoDisplay = [
                                    'admin' => ['text' => 'Admin', 'class' => 'danger'],
                                    'secretaria' => ['text' => 'CFC', 'class' => 'primary'],
                                    'instrutor' => ['text' => 'Instrutor', 'class' => 'warning'],
                                    'aluno' => ['text' => 'Aluno', 'class' => 'info']
                                ];
                                $tipoInfo = $tipoDisplay[$usuario['tipo']] ?? ['text' => ucfirst($usuario['tipo']), 'class' => 'secondary'];
                                ?>
                                <span class="badge badge-<?php echo $tipoInfo['class']; ?> mobile-user-badge">
                                    <?php echo $tipoInfo['text']; ?>
                                </span>
                            </div>
                            
                            <div class="mobile-user-info">
                                <div class="mobile-user-field">
                                    <span class="mobile-user-label">E-mail</span>
                                    <span class="mobile-user-value"><?php echo htmlspecialchars($usuario['email']); ?></span>
                                </div>
                                <div class="mobile-user-field">
                                    <span class="mobile-user-label">Status</span>
                                    <span class="mobile-user-value">
                                        <span class="badge badge-<?php echo $usuario['ativo'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $usuario['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                        </span>
                                    </span>
                                </div>
                                <div class="mobile-user-field">
                                    <span class="mobile-user-label">Criado em</span>
                                    <span class="mobile-user-value"><?php echo date('d/m/Y', strtotime($usuario['criado_em'])); ?></span>
                                </div>
                                <div class="mobile-user-field">
                                    <span class="mobile-user-label">Último acesso</span>
                                    <span class="mobile-user-value">
                                        <?php echo isset($usuario['ultimo_acesso']) && $usuario['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_acesso'])) : 'Nunca'; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="mobile-user-actions">
                                <button class="btn btn-edit action-btn btn-editar-usuario" 
                                        data-user-id="<?php echo $usuario['id']; ?>"
                                        title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-warning action-btn btn-redefinir-senha" 
                                        data-user-id="<?php echo $usuario['id']; ?>"
                                        data-user-name="<?php echo htmlspecialchars($usuario['nome']); ?>"
                                        data-user-email="<?php echo htmlspecialchars($usuario['email']); ?>"
                                        title="Redefinir Senha">
                                    <i class="fas fa-key"></i>
                                </button>
                                <button class="btn btn-danger action-btn btn-excluir-usuario" 
                                        data-user-id="<?php echo $usuario['id']; ?>"
                                        title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
                        <option value="secretaria">Atendente CFC</option>
                        <option value="instrutor">Instrutor</option>
                        <option value="aluno">Aluno</option>
                    </select>
                    <div class="form-text">
                        <strong>Administrador:</strong> Acesso total incluindo configurações<br>
                        <strong>Atendente CFC:</strong> Pode fazer tudo menos configurações<br>
                        <strong>Instrutor:</strong> Pode alterar/cancelar aulas mas não adicionar<br>
                        <strong>Aluno:</strong> Pode visualizar apenas suas informações
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Sistema de Credenciais Automáticas</strong><br>
                        • Senha temporária será gerada automaticamente<br>
                        • Credenciais serão exibidas na tela após criação<br>
                        • Usuário receberá credenciais por email<br>
                        • Senha deve ser alterada no primeiro acesso
                    </div>
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

<!-- Modal de Redefinição de Senha -->
<div id="resetPasswordModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Redefinir Senha</h3>
            <button class="modal-close" onclick="closeResetPasswordModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Atenção!</strong> Esta ação irá gerar uma nova senha temporária para o usuário.
            </div>
            
            <div class="user-info">
                <h4>Dados do Usuário:</h4>
                <p><strong>Nome:</strong> <span id="resetUserName"></span></p>
                <p><strong>E-mail:</strong> <span id="resetUserEmail"></span></p>
            </div>
            
            <div class="form-group">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>O que acontecerá:</strong><br>
                    • Uma nova senha temporária será gerada automaticamente<br>
                    • As credenciais serão exibidas na tela após a redefinição<br>
                    • O usuário receberá as novas credenciais por e-mail<br>
                    • A senha anterior será invalidada imediatamente<br>
                    • O usuário deve alterar a senha no próximo acesso
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    <input type="checkbox" id="confirmResetPassword" required>
                    Confirmo que desejo redefinir a senha deste usuário
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeResetPasswordModal()">Cancelar</button>
            <button type="button" class="btn btn-warning" id="confirmResetBtn" onclick="confirmResetPassword()" disabled>
                <i class="fas fa-key"></i>
                Redefinir Senha
            </button>
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
    
    // Senha não é mais necessária - sistema gera automaticamente
    // document.getElementById('userPassword').required = true;
    // document.getElementById('userConfirmPassword').required = true;
    
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
                
                // Senha não é mais necessária - sistema gera automaticamente
                // document.getElementById('userPassword').required = false;
                // document.getElementById('userConfirmPassword').required = false;
                
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
    
    // Validação de senha removida - sistema gera automaticamente
    // if (!isEditMode) {
    //     if (!formData.get('senha')) {
    //         showNotification('Senha e obrigatoria', 'error');
    //         return;
    //     }
    //     
    //     if (formData.get('senha').length < 6) {
    //         showNotification('Senha deve ter pelo menos 6 caracteres', 'error');
    //         return;
    //     }
    //     
    //     if (formData.get('senha') !== formData.get('confirmar_senha')) {
    //         showNotification('Senhas nao conferem', 'error');
    //         return;
    //     }
    // }
    
    console.log('Validacoes passaram, preparando dados...');
    
    // Preparar dados para envio (senha removida - sistema gera automaticamente)
    const userData = {
        nome: formData.get('nome').trim(),
        email: formData.get('email').trim(),
        tipo: formData.get('tipo'),
        ativo: formData.get('ativo') ? true : false
    };
    
    // Senha não é mais necessária - sistema gera automaticamente
    // if (!isEditMode || formData.get('senha')) {
    //     userData.senha = formData.get('senha');
    // }
    
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
            
            // Se foram criadas credenciais, exibir na tela
            if (data.credentials) {
                console.log('🔐 Credenciais recebidas:', data.credentials);
                const credentials = data.credentials;
                
                // Exibir credenciais em modal de alerta primeiro
                const credentialsText = `
🔐 CREDENCIAIS CRIADAS COM SUCESSO!

📧 Email: ${credentials.email}
🔑 Senha Temporária: ${credentials.senha_temporaria}

⚠️ IMPORTANTE:
• Esta é uma senha temporária
• O usuário deve alterar no primeiro acesso
• Guarde estas informações em local seguro

Clique em "OK" para abrir a página completa de credenciais.
                `;
                
                if (confirm(credentialsText)) {
                    const credentialsUrl = `credenciais_criadas.php?credentials=${btoa(JSON.stringify(credentials))}`;
                    window.open(credentialsUrl, '_blank');
                }
            }
            
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

// Variáveis globais para redefinição de senha
let resetPasswordUser = null;

// Mostrar modal de redefinição de senha
function showResetPasswordModal(userId, userName, userEmail) {
    console.log('Função showResetPasswordModal chamada para usuário ID: ' + userId);
    
    resetPasswordUser = {
        id: userId,
        name: userName,
        email: userEmail
    };
    
    // Preencher dados do usuário no modal
    document.getElementById('resetUserName').textContent = userName;
    document.getElementById('resetUserEmail').textContent = userEmail;
    
    // Resetar checkbox e botão
    document.getElementById('confirmResetPassword').checked = false;
    document.getElementById('confirmResetBtn').disabled = true;
    
    // Mostrar modal
    const modal = document.getElementById('resetPasswordModal');
    modal.classList.add('show');
    
    console.log('Modal de redefinição de senha aberto com sucesso!');
}

// Garantir que a função esteja disponível globalmente
window.showResetPasswordModal = showResetPasswordModal;

// Fechar modal de redefinição de senha
function closeResetPasswordModal() {
    console.log('Fechando modal de redefinição de senha...');
    const modal = document.getElementById('resetPasswordModal');
    modal.classList.remove('show');
    
    // Resetar dados
    resetPasswordUser = null;
    document.getElementById('confirmResetPassword').checked = false;
    document.getElementById('confirmResetBtn').disabled = true;
    
    console.log('Modal de redefinição de senha fechado com sucesso!');
}

// Garantir que a função esteja disponível globalmente
window.closeResetPasswordModal = closeResetPasswordModal;

// Confirmar redefinição de senha
function confirmResetPassword() {
    console.log('Função confirmResetPassword chamada');
    
    if (!resetPasswordUser) {
        showNotification('Erro: Dados do usuário não encontrados', 'error');
        return;
    }
    
    if (!document.getElementById('confirmResetPassword').checked) {
        showNotification('Você deve confirmar a redefinição de senha', 'error');
        return;
    }
    
    console.log('Confirmando redefinição de senha para usuário ID: ' + resetPasswordUser.id);
    
    // Mostrar loading
    const loadingEl = document.querySelector('.card-body');
    if (loadingEl) {
        loadingEl.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Carregando...</span></div><p class="mt-2">Redefinindo senha...</p></div>';
    }
    
    // Fazer requisição para a API
    fetch('api/usuarios.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'reset_password',
            user_id: resetPasswordUser.id
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message || 'Senha redefinida com sucesso!', 'success');
            closeResetPasswordModal();
            
            // Se foram criadas credenciais, exibir na tela
            if (data.credentials) {
                console.log('🔐 Credenciais recebidas:', data.credentials);
                const credentials = data.credentials;
                
                // Exibir credenciais em modal de alerta primeiro
                const credentialsText = `
🔐 SENHA REDEFINIDA COM SUCESSO!

📧 Email: ${credentials.email}
🔑 Nova Senha Temporária: ${credentials.senha_temporaria}

⚠️ IMPORTANTE:
• Esta é uma nova senha temporária
• A senha anterior foi invalidada
• O usuário deve alterar no próximo acesso
• Guarde estas informações em local seguro

Clique em "OK" para abrir a página completa de credenciais.
                `;
                
                if (confirm(credentialsText)) {
                    const credentialsUrl = `credenciais_criadas.php?credentials=${btoa(JSON.stringify(credentials))}`;
                    window.open(credentialsUrl, '_blank');
                }
            }
            
            // Recarregar página para mostrar dados atualizados
            setTimeout(function() {
                window.location.reload();
            }, 1500);
        } else {
            showNotification(data.error || 'Erro ao redefinir senha', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showNotification('Erro ao redefinir senha. Tente novamente.', 'error');
    })
    .finally(() => {
        // Restaurar conteúdo da página
        if (loadingEl) {
            window.location.reload();
        }
    });
}

// Garantir que a função esteja disponível globalmente
window.confirmResetPassword = confirmResetPassword;

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
    
    // Configurar event listeners para botões de redefinição de senha
    const resetPasswordButtons = document.querySelectorAll('.btn-redefinir-senha');
    console.log('Encontrados ' + resetPasswordButtons.length + ' botoes de redefinir senha');
    
    resetPasswordButtons.forEach(function(button, index) {
        const userId = button.getAttribute('data-user-id');
        const userName = button.getAttribute('data-user-name');
        const userEmail = button.getAttribute('data-user-email');
        console.log('Configurando botao de redefinir senha ' + (index + 1) + ' para usuario ID: ' + userId);
        
        // Adicionar event listener
        button.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            
            const userIdFromButton = this.getAttribute('data-user-id');
            const userNameFromButton = this.getAttribute('data-user-name');
            const userEmailFromButton = this.getAttribute('data-user-email');
            console.log('Botao de redefinir senha clicado para usuario ID: ' + userIdFromButton);
            
            if (typeof showResetPasswordModal === 'function') {
                showResetPasswordModal(userIdFromButton, userNameFromButton, userEmailFromButton);
            } else {
                console.error('Funcao showResetPasswordModal nao esta disponivel!');
                showNotification('Erro: Função de redefinição de senha não está disponível. Recarregue a página.', 'error');
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
        
        /* Estilos para o modal de redefinição de senha */
        #resetPasswordModal {
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
        
        #resetPasswordModal.show {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            pointer-events: auto !important;
        }
        
        #resetPasswordModal .modal {
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
        
        #resetPasswordModal .user-info {
            background: var(--gray-50) !important;
            padding: 15px !important;
            border-radius: 6px !important;
            margin-bottom: 15px !important;
            border-left: 4px solid var(--primary-color) !important;
        }
        
        #resetPasswordModal .user-info h4 {
            margin: 0 0 10px 0 !important;
            color: var(--gray-800) !important;
            font-size: 16px !important;
        }
        
        #resetPasswordModal .user-info p {
            margin: 5px 0 !important;
            color: var(--gray-700) !important;
            font-size: 14px !important;
        }
        
        #resetPasswordModal .btn-warning {
            background: #f59e0b !important;
            color: white !important;
            border: none !important;
        }
        
        #resetPasswordModal .btn-warning:hover {
            background: #d97706 !important;
        }
        
        #resetPasswordModal .btn-warning:disabled {
            background: #d1d5db !important;
            color: #9ca3af !important;
            cursor: not-allowed !important;
        }
    `;
    document.head.appendChild(style);
    
    console.log('Pagina de usuarios inicializada com sucesso!');
    
    // Função para alternar entre tabela e cards mobile
    function toggleMobileLayout() {
        const viewportWidth = window.innerWidth;
        const isMobile = viewportWidth <= 600; // Aumentar threshold
        const tableContainer = document.querySelector('.table-container');
        const mobileCards = document.querySelector('.mobile-user-cards');
        
        
        if (isMobile && mobileCards) {
            // Mobile pequeno - mostrar cards
            if (tableContainer) {
                tableContainer.style.display = 'none';
            }
            mobileCards.style.display = 'block';
        } else {
            // Desktop/tablet - mostrar tabela
            if (tableContainer) {
                tableContainer.style.display = 'block';
            }
            if (mobileCards) {
                mobileCards.style.display = 'none';
            }
        }
    }
    
    // Executar na inicialização
    toggleMobileLayout();
    
    // Executar no resize
    window.addEventListener('resize', toggleMobileLayout);
    
    // Configurar event listener para checkbox de confirmação
    const confirmCheckbox = document.getElementById('confirmResetPassword');
    const confirmBtn = document.getElementById('confirmResetBtn');
    
    if (confirmCheckbox && confirmBtn) {
        confirmCheckbox.addEventListener('change', function() {
            confirmBtn.disabled = !this.checked;
        });
    }
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
    const funcoes = ['showCreateUserModal', 'editUser', 'deleteUser', 'closeUserModal', 'saveUser', 'exportUsers', 'showNotification', 'showResetPasswordModal', 'closeResetPasswordModal', 'confirmResetPassword'];
    const funcoesFaltando = funcoes.filter(f => typeof window[f] !== 'function');
    
    if (funcoesFaltando.length > 0) {
        console.error('Funções faltando:', funcoesFaltando);
        alert('Atenção: As seguintes funções não estão funcionando: ' + funcoesFaltando.join(', ') + '. Tente recarregar a página.');
    } else {
             console.log('Todas as funções estão disponíveis!');
 }
});

// Timeout adicional para garantir que as funções sejam definidas
setTimeout(function() {
    console.log('Verificação de timeout das funções:');
    const funcoes = ['showCreateUserModal', 'editUser', 'deleteUser', 'closeUserModal', 'saveUser', 'exportUsers', 'showNotification', 'showResetPasswordModal', 'closeResetPasswordModal', 'confirmResetPassword'];
    funcoes.forEach(f => {
        if (typeof window[f] === 'function') {
            console.log(f + ': Disponível');
        } else {
            console.error(f + ': NAO disponivel');
        }
    });
}, 2000);
</script>
