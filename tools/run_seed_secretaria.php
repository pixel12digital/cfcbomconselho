<?php

require_once __DIR__ . '/../app/autoload.php';
require_once __DIR__ . '/../app/Bootstrap.php';

use App\Config\Database;

$db = Database::getInstance()->getConnection();

echo "=== Criando usuário SECRETARIA ===\n\n";

try {
    // Criar usuário Secretaria (senha: secretaria123)
    $stmt = $db->prepare("
        INSERT INTO usuarios (cfc_id, nome, email, password, status, must_change_password) 
        VALUES (1, 'Secretaria', 'secretaria@cfc.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ativo', 1)
        ON DUPLICATE KEY UPDATE nome = VALUES(nome)
    ");
    $stmt->execute();
    
    // Buscar ID
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = 'secretaria@cfc.local'");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        // Associar role SECRETARIA
        $stmt = $db->prepare("
            INSERT INTO usuario_roles (usuario_id, role) 
            VALUES (?, 'SECRETARIA')
            ON DUPLICATE KEY UPDATE usuario_id = VALUES(usuario_id)
        ");
        $stmt->execute([$user['id']]);
        
        echo "✅ Usuário SECRETARIA criado com sucesso!\n";
        echo "   Email: secretaria@cfc.local\n";
        echo "   Senha: secretaria123\n";
        echo "   ⚠️  ALTERAR SENHA APÓS PRIMEIRO LOGIN!\n";
    } else {
        echo "❌ Erro ao criar usuário\n";
    }
} catch (\Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate') !== false) {
        echo "⚠️  Usuário já existe (atualizado)\n";
    } else {
        echo "❌ Erro: " . $e->getMessage() . "\n";
    }
}
