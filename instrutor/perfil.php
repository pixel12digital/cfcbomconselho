<?php
/**
 * Página de Perfil do Instrutor
 * Permite ao instrutor editar seus próprios dados básicos
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Verificar autenticação
$user = getCurrentUser();
if (!$user || $user['tipo'] !== 'instrutor') {
    header('Location: /cfc-bom-conselho/login.php');
    exit();
}

$db = db();

// Verificar se precisa trocar senha
try {
    $checkColumn = $db->fetch("SHOW COLUMNS FROM usuarios LIKE 'precisa_trocar_senha'");
    if ($checkColumn) {
        $usuarioCompleto = $db->fetch("SELECT precisa_trocar_senha FROM usuarios WHERE id = ?", [$user['id']]);
        if ($usuarioCompleto && isset($usuarioCompleto['precisa_trocar_senha']) && $usuarioCompleto['precisa_trocar_senha'] == 1) {
            header('Location: /cfc-bom-conselho/instrutor/trocar-senha.php?forcado=1');
            exit();
        }
    }
} catch (Exception $e) {
    // Continuar normalmente
}

// Buscar dados completos do usuário
$usuarioCompleto = $db->fetch("
    SELECT u.*, i.cfc_id, i.credencial, c.nome as cfc_nome
    FROM usuarios u
    LEFT JOIN instrutores i ON i.usuario_id = u.id
    LEFT JOIN cfcs c ON c.id = i.cfc_id
    WHERE u.id = ?
", [$user['id']]);

if (!$usuarioCompleto) {
    header('Location: /cfc-bom-conselho/instrutor/dashboard.php');
    exit();
}

// Buscar dados do instrutor
$instrutor = $db->fetch("SELECT * FROM instrutores WHERE usuario_id = ?", [$user['id']]);
if (!$instrutor) {
    $instrutor = [
        'id' => null,
        'usuario_id' => $user['id'],
        'cfc_id' => null,
        'credencial' => null
    ];
}

$success = '';
$error = '';

// Processar atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    
    // Validações
    if (empty($nome)) {
        $error = 'Nome é obrigatório.';
    } elseif (empty($email)) {
        $error = 'E-mail é obrigatório.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'E-mail inválido.';
    } else {
        // Verificar se email já existe em outro usuário
        $emailExistente = $db->fetch("SELECT id FROM usuarios WHERE email = ? AND id != ?", [$email, $user['id']]);
        if ($emailExistente) {
            $error = 'Este e-mail já está em uso por outro usuário.';
        } else {
            // Atualizar dados
            try {
                $updateFields = ['nome = ?', 'email = ?', 'atualizado_em = NOW()'];
                $updateValues = [$nome, $email];
                
                // Adicionar telefone se fornecido
                if (!empty($telefone)) {
                    $updateFields[] = 'telefone = ?';
                    $updateValues[] = $telefone;
                }
                
                $updateValues[] = $user['id'];
                $updateQuery = 'UPDATE usuarios SET ' . implode(', ', $updateFields) . ' WHERE id = ?';
                
                $db->query($updateQuery, $updateValues);
                
                // Atualizar sessão
                $_SESSION['user_nome'] = $nome;
                $_SESSION['user_email'] = $email;
                
                // Recarregar dados
                $usuarioCompleto = $db->fetch("
                    SELECT u.*, i.cfc_id, i.credencial, c.nome as cfc_nome
                    FROM usuarios u
                    LEFT JOIN instrutores i ON i.usuario_id = u.id
                    LEFT JOIN cfcs c ON c.id = i.cfc_id
                    WHERE u.id = ?
                ", [$user['id']]);
                
                $success = 'Perfil atualizado com sucesso!';
            } catch (Exception $e) {
                $error = 'Erro ao atualizar perfil: ' . $e->getMessage();
                if (defined('LOG_ENABLED') && LOG_ENABLED) {
                    error_log('Erro ao atualizar perfil do instrutor: ' . $e->getMessage());
                }
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - <?php echo htmlspecialchars($usuarioCompleto['nome']); ?></title>
    <link rel="stylesheet" href="../assets/css/mobile-first.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1>Meu Perfil</h1>
                <div class="subtitle">Edite suas informações pessoais</div>
            </div>
            <a href="dashboard.php" style="color: white; text-decoration: none; padding: 8px 16px; background: rgba(255,255,255,0.2); border-radius: 8px;">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <div class="container" style="max-width: 800px; margin: 0 auto; padding: 20px 16px;">
        <!-- Mensagens -->
        <?php if ($success): ?>
        <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <!-- Formulário -->
        <div class="card" style="background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 24px;">
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_profile">
                
                <!-- Nome -->
                <div style="margin-bottom: 20px;">
                    <label for="nome" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">
                        Nome Completo <span style="color: #e74c3c;">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="nome" 
                        name="nome" 
                        value="<?php echo htmlspecialchars($usuarioCompleto['nome'] ?? ''); ?>" 
                        required
                        style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px;"
                    >
                </div>

                <!-- Email -->
                <div style="margin-bottom: 20px;">
                    <label for="email" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">
                        E-mail <span style="color: #e74c3c;">*</span>
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?php echo htmlspecialchars($usuarioCompleto['email'] ?? ''); ?>" 
                        required
                        style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px;"
                    >
                </div>

                <!-- Telefone -->
                <div style="margin-bottom: 20px;">
                    <label for="telefone" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">
                        Telefone/Celular
                    </label>
                    <input 
                        type="tel" 
                        id="telefone" 
                        name="telefone" 
                        value="<?php echo htmlspecialchars($usuarioCompleto['telefone'] ?? ''); ?>" 
                        style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px;"
                    >
                </div>

                <!-- Campos somente leitura -->
                <hr style="margin: 24px 0; border: none; border-top: 1px solid #eee;">
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">
                        CPF
                    </label>
                    <input 
                        type="text" 
                        value="<?php echo htmlspecialchars($usuarioCompleto['cpf'] ?? 'Não informado'); ?>" 
                        readonly
                        style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; background: #f5f5f5; color: #666;"
                    >
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">
                        CFC Vinculado
                    </label>
                    <input 
                        type="text" 
                        value="<?php echo htmlspecialchars($usuarioCompleto['cfc_nome'] ?? 'Não vinculado'); ?>" 
                        readonly
                        style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; background: #f5f5f5; color: #666;"
                    >
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">
                        Tipo de Usuário
                    </label>
                    <input 
                        type="text" 
                        value="Instrutor" 
                        readonly
                        style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; background: #f5f5f5; color: #666;"
                    >
                </div>

                <!-- Botões -->
                <div style="display: flex; gap: 12px; margin-top: 32px;">
                    <button 
                        type="submit" 
                        style="flex: 1; padding: 12px 24px; background: #2563eb; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer;"
                    >
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                    <a 
                        href="dashboard.php" 
                        style="padding: 12px 24px; background: #f0f0f0; color: #333; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; text-decoration: none; display: flex; align-items: center; justify-content: center;"
                    >
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

