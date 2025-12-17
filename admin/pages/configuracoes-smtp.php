<?php
/**
 * Página de Configuração SMTP
 * 
 * Interface administrativa para configurar SMTP do sistema
 * Apenas acessível para administradores
 */

// Verificar se estamos sendo incluídos pelo sistema de roteamento do admin
if (!defined('ADMIN_ROUTING')) {
    require_once '../../includes/config.php';
    require_once '../../includes/database.php';
    require_once '../../includes/auth.php';
    
    // Verificar se usuário está logado
    if (!isLoggedIn()) {
        header('Location: ../../index.php');
        exit;
    }
}

// Verificar se é admin (obrigatório)
if (!isset($isAdmin) || !$isAdmin) {
    echo '<div class="alert alert-danger">Você não tem permissão para acessar esta página. Apenas administradores podem configurar SMTP.</div>';
    return;
}

require_once '../../includes/SMTPConfigService.php';

// Obter status atual
$status = SMTPConfigService::getStatus();
$config = SMTPConfigService::getConfig();
?>

<style>
.smtp-config-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 30px;
    max-width: 900px;
    margin: 0 auto;
}

.smtp-status-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.smtp-status-card.incomplete {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.smtp-status-card.error {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    color: #333;
}

.smtp-status-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 15px;
}

.smtp-status-title {
    font-size: 1.5rem;
    font-weight: bold;
    margin: 0;
}

.smtp-status-badge {
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: bold;
    background: rgba(255,255,255,0.2);
}

.smtp-status-info {
    font-size: 0.95rem;
    opacity: 0.9;
}

.smtp-status-last-test {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid rgba(255,255,255,0.3);
    font-size: 0.85rem;
}

.form-section {
    margin-bottom: 25px;
}

.form-section-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1A365D;
    margin-bottom: 15px;
    padding-bottom: 8px;
    border-bottom: 2px solid #e0e0e0;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
}

.form-group label .required {
    color: #d63031;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #1A365D;
    box-shadow: 0 0 0 3px rgba(26, 54, 93, 0.1);
}

.form-group .help-text {
    font-size: 0.85rem;
    color: #666;
    margin-top: 5px;
}

.password-wrapper {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    padding: 5px;
    font-size: 1.1rem;
}

.password-toggle:hover {
    color: #1A365D;
}

.btn-group {
    display: flex;
    gap: 10px;
    margin-top: 30px;
}

.btn {
    padding: 12px 30px;
    border: none;
    border-radius: 4px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-primary {
    background: #1A365D;
    color: white;
}

.btn-primary:hover {
    background: #2d4a6b;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #218838;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.alert {
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.smtp-footer-note {
    margin-top: 30px;
    padding: 15px;
    background: #f8f9fa;
    border-left: 4px solid #1A365D;
    border-radius: 4px;
    font-size: 0.9rem;
    color: #666;
}

.loading-spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #1A365D;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-right: 8px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<div class="smtp-config-container">
    <h1 style="color: #1A365D; margin-bottom: 30px;">
        <i class="fas fa-envelope me-2"></i>
        Configurações de E-mail (SMTP)
    </h1>
    
    <!-- Status Card -->
    <div class="smtp-status-card <?php echo $status['status'] === 'incompleto' ? 'incomplete' : ($status['status'] === 'error' ? 'error' : ''); ?>">
        <div class="smtp-status-header">
            <h2 class="smtp-status-title">Status SMTP</h2>
            <span class="smtp-status-badge">
                <?php 
                switch($status['status']) {
                    case 'configurado':
                        echo '✅ Configurado';
                        break;
                    case 'error':
                        echo '⚠️ Erro no teste';
                        break;
                    default:
                        echo '❌ Não configurado';
                }
                ?>
            </span>
        </div>
        <div class="smtp-status-info">
            <?php if ($status['configured']): ?>
                <p><strong>Host:</strong> <?php echo htmlspecialchars($status['host']); ?></p>
                <p><strong>Usuário:</strong> <?php echo htmlspecialchars($status['user']); ?></p>
            <?php else: ?>
                <p>Configure o SMTP abaixo para habilitar o envio de e-mails do sistema.</p>
            <?php endif; ?>
            
            <?php if ($status['last_test_at']): ?>
                <div class="smtp-status-last-test">
                    <strong>Último teste:</strong> 
                    <?php echo date('d/m/Y H:i', strtotime($status['last_test_at'])); ?>
                    - 
                    <?php if ($status['last_test_status'] === 'ok'): ?>
                        <span style="color: #90EE90;">✅ Sucesso</span>
                    <?php else: ?>
                        <span style="color: #FFB6C1;">❌ Falhou</span>
                        <?php if ($status['last_test_message']): ?>
                            <br><small><?php echo htmlspecialchars($status['last_test_message']); ?></small>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Mensagens -->
    <div id="alert-container"></div>
    
    <!-- Formulário -->
    <form id="smtp-config-form">
        <div class="form-section">
            <h3 class="form-section-title">Configurações Básicas</h3>
            
            <div class="form-group">
                <label for="host">
                    Host SMTP <span class="required">*</span>
                </label>
                <input 
                    type="text" 
                    id="host" 
                    name="host" 
                    value="<?php echo htmlspecialchars($config['host'] ?? ''); ?>"
                    placeholder="smtp.hostinger.com"
                    required
                >
                <div class="help-text">Servidor SMTP do seu provedor de e-mail</div>
            </div>
            
            <div class="form-group">
                <label for="port">
                    Porta <span class="required">*</span>
                </label>
                <input 
                    type="number" 
                    id="port" 
                    name="port" 
                    value="<?php echo htmlspecialchars($config['port'] ?? '587'); ?>"
                    min="1" 
                    max="65535"
                    required
                >
                <div class="help-text">Porta SMTP (587 para TLS, 465 para SSL)</div>
            </div>
            
            <div class="form-group">
                <label for="encryption_mode">
                    Criptografia <span class="required">*</span>
                </label>
                <select id="encryption_mode" name="encryption_mode" required>
                    <option value="tls" <?php echo ($config['encryption_mode'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>
                        TLS (porta 587)
                    </option>
                    <option value="ssl" <?php echo ($config['encryption_mode'] ?? '') === 'ssl' ? 'selected' : ''; ?>>
                        SSL (porta 465)
                    </option>
                    <option value="none" <?php echo ($config['encryption_mode'] ?? '') === 'none' ? 'selected' : ''; ?>>
                        Nenhuma
                    </option>
                </select>
                <div class="help-text">Tipo de criptografia usada pela conexão SMTP</div>
            </div>
        </div>
        
        <div class="form-section">
            <h3 class="form-section-title">Credenciais</h3>
            
            <div class="form-group">
                <label for="user">
                    E-mail / Usuário <span class="required">*</span>
                </label>
                <input 
                    type="email" 
                    id="user" 
                    name="user" 
                    value="<?php echo htmlspecialchars($config['user'] ?? ''); ?>"
                    placeholder="seu_email@seudominio.com.br"
                    required
                >
                <div class="help-text">E-mail utilizado para autenticação SMTP</div>
            </div>
            
            <div class="form-group">
                <label for="pass">
                    Senha SMTP <span class="required"><?php echo $config ? '' : '*'; ?></span>
                </label>
                <div class="password-wrapper">
                    <input 
                        type="password" 
                        id="pass" 
                        name="pass" 
                        placeholder="<?php echo $config ? '•••••••• (deixe vazio para manter atual)' : 'Digite a senha SMTP'; ?>"
                        <?php echo $config ? '' : 'required'; ?>
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="password-toggle-icon"></i>
                    </button>
                </div>
                <div class="help-text">
                    <?php if ($config): ?>
                        Deixe vazio para manter a senha atual. Digite nova senha apenas se desejar alterar.
                    <?php else: ?>
                        Senha do e-mail ou senha de aplicativo SMTP
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h3 class="form-section-title">Remetente (Opcional)</h3>
            
            <div class="form-group">
                <label for="from_name">
                    Nome do Remetente
                </label>
                <input 
                    type="text" 
                    id="from_name" 
                    name="from_name" 
                    value="<?php echo htmlspecialchars($config['from_name'] ?? ''); ?>"
                    placeholder="CFC Bom Conselho"
                >
                <div class="help-text">Nome exibido como remetente nos e-mails</div>
            </div>
            
            <div class="form-group">
                <label for="from_email">
                    E-mail "From" (se diferente do usuário)
                </label>
                <input 
                    type="email" 
                    id="from_email" 
                    name="from_email" 
                    value="<?php echo htmlspecialchars($config['from_email'] ?? ''); ?>"
                    placeholder="noreply@cfcbomconselho.com.br"
                >
                <div class="help-text">E-mail exibido como remetente (se diferente do usuário SMTP)</div>
            </div>
        </div>
        
        <div class="btn-group">
            <button type="submit" class="btn btn-primary" id="btn-save">
                <i class="fas fa-save me-2"></i>
                Salvar Configurações
            </button>
            <button type="button" class="btn btn-success" id="btn-test" onclick="testSMTP()">
                <i class="fas fa-paper-plane me-2"></i>
                Testar Envio
            </button>
        </div>
    </form>
    
    <!-- Aviso no rodapé -->
    <div class="smtp-footer-note">
        <strong>⚠️ Importante:</strong> Essas configurações afetam o envio de e-mails de recuperação de senha e notificações do sistema. 
        Certifique-se de testar o envio após configurar.
    </div>
</div>

<script>
let currentConfig = <?php echo json_encode($config); ?>;
let isSaving = false;
let isTesting = false;

// Carregar configurações ao carregar página
document.addEventListener('DOMContentLoaded', function() {
    // Sincronizar porta com criptografia
    document.getElementById('encryption_mode').addEventListener('change', function() {
        const portInput = document.getElementById('port');
        if (this.value === 'ssl') {
            portInput.value = '465';
        } else if (this.value === 'tls') {
            portInput.value = '587';
        }
    });
    
    // Submit do formulário
    document.getElementById('smtp-config-form').addEventListener('submit', function(e) {
        e.preventDefault();
        saveConfig();
    });
});

function togglePassword() {
    const passInput = document.getElementById('pass');
    const toggleIcon = document.getElementById('password-toggle-icon');
    
    if (passInput.type === 'password') {
        passInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

function showAlert(message, type = 'info') {
    const container = document.getElementById('alert-container');
    const alert = document.createElement('div');
    alert.className = 'alert alert-' + type;
    alert.innerHTML = message;
    container.innerHTML = '';
    container.appendChild(alert);
    
    // Scroll para o topo
    window.scrollTo({ top: 0, behavior: 'smooth' });
    
    // Remover após 5 segundos
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

function saveConfig() {
    if (isSaving) return;
    
    const form = document.getElementById('smtp-config-form');
    const formData = new FormData(form);
    const data = {
        action: 'save',
        host: formData.get('host'),
        port: parseInt(formData.get('port')),
        user: formData.get('user'),
        pass: formData.get('pass'),
        encryption_mode: formData.get('encryption_mode'),
        from_name: formData.get('from_name') || null,
        from_email: formData.get('from_email') || null
    };
    
    // Remover senha se vazia (manter atual)
    if (!data.pass) {
        delete data.pass;
    }
    
    isSaving = true;
    const btnSave = document.getElementById('btn-save');
    btnSave.disabled = true;
    btnSave.innerHTML = '<span class="loading-spinner"></span> Salvando...';
    
    fetch('api/smtp-config.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        isSaving = false;
        btnSave.disabled = false;
        btnSave.innerHTML = '<i class="fas fa-save me-2"></i> Salvar Configurações';
        
        if (result.success) {
            showAlert('<strong>✅ Sucesso!</strong> ' + result.message, 'success');
            // Recarregar página após 1.5 segundos para atualizar status
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showAlert('<strong>❌ Erro:</strong> ' + result.message, 'danger');
        }
    })
    .catch(error => {
        isSaving = false;
        btnSave.disabled = false;
        btnSave.innerHTML = '<i class="fas fa-save me-2"></i> Salvar Configurações';
        showAlert('<strong>❌ Erro:</strong> Erro ao salvar configurações. Tente novamente.', 'danger');
        console.error('Erro:', error);
    });
}

function testSMTP() {
    if (isTesting) return;
    
    // Obter e-mail do usuário logado ou pedir
    const testEmail = prompt('Digite o e-mail para enviar o teste:', '<?php echo htmlspecialchars($user['email'] ?? ''); ?>');
    
    if (!testEmail) {
        return;
    }
    
    if (!testEmail.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
        showAlert('<strong>❌ Erro:</strong> E-mail inválido.', 'danger');
        return;
    }
    
    isTesting = true;
    const btnTest = document.getElementById('btn-test');
    btnTest.disabled = true;
    btnTest.innerHTML = '<span class="loading-spinner"></span> Testando...';
    
    fetch('api/smtp-config.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'test',
            test_email: testEmail
        })
    })
    .then(response => response.json())
    .then(result => {
        isTesting = false;
        btnTest.disabled = false;
        btnTest.innerHTML = '<i class="fas fa-paper-plane me-2"></i> Testar Envio';
        
        if (result.success) {
            showAlert('<strong>✅ Sucesso!</strong> ' + result.message, 'success');
            // Recarregar após 2 segundos para atualizar status do teste
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            showAlert('<strong>❌ Erro:</strong> ' + result.message, 'danger');
        }
    })
    .catch(error => {
        isTesting = false;
        btnTest.disabled = false;
        btnTest.innerHTML = '<i class="fas fa-paper-plane me-2"></i> Testar Envio';
        showAlert('<strong>❌ Erro:</strong> Erro ao testar envio. Tente novamente.', 'danger');
        console.error('Erro:', error);
    });
}
</script>
