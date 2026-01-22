<?php
/**
 * Script temporário para criar a coluna precisa_trocar_senha na tabela usuarios
 * 
 * Executar via navegador: http://localhost/cfc-bom-conselho/admin/criar-coluna-precisa-trocar-senha.php
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Verificar autenticação
$user = getCurrentUser();
if (!$user || !canManageUsers()) {
    die('Acesso negado. Apenas administradores e secretárias podem executar este script.');
}

header('Content-Type: text/html; charset=utf-8');

$db = db();

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Criar Coluna precisa_trocar_senha</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Criar Coluna precisa_trocar_senha</h1>";

try {
    // 1. Verificar se a coluna já existe
    echo "<div class='info'>1. Verificando se a coluna precisa_trocar_senha já existe...</div>";
    $checkColumn = $db->fetch("SHOW COLUMNS FROM usuarios LIKE 'precisa_trocar_senha'");
    
    if ($checkColumn) {
        echo "<div class='success'>✅ A coluna precisa_trocar_senha já existe na tabela usuarios.</div>";
        echo "<div class='info'>Estrutura da coluna:</div>";
        echo "<pre>";
        print_r($checkColumn);
        echo "</pre>";
    } else {
        echo "<div class='warning'>⚠️ A coluna precisa_trocar_senha não existe. Criando...</div>";
        
        // 2. Criar a coluna
        $sql = "ALTER TABLE usuarios 
                ADD COLUMN precisa_trocar_senha TINYINT(1) NOT NULL DEFAULT 0 
                COMMENT 'Flag para forçar troca de senha no próximo login' 
                AFTER senha";
        
        try {
            $result = $db->query($sql);
            
            if ($result) {
                echo "<div class='success'>✅ Coluna precisa_trocar_senha criada com sucesso!</div>";
                
                // Verificar novamente
                $checkColumn = $db->fetch("SHOW COLUMNS FROM usuarios LIKE 'precisa_trocar_senha'");
                if ($checkColumn) {
                    echo "<div class='info'>Estrutura da coluna criada:</div>";
                    echo "<pre>";
                    print_r($checkColumn);
                    echo "</pre>";
                }
            } else {
                echo "<div class='error'>❌ Erro ao criar a coluna. Verifique os logs do sistema.</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Erro ao executar ALTER TABLE: " . htmlspecialchars($e->getMessage()) . "</div>";
            error_log("Erro ao criar coluna precisa_trocar_senha: " . $e->getMessage());
        }
    }
    
    // 3. Verificar quantos usuários têm a flag ativada
    echo "<div class='info'>2. Verificando usuários com precisa_trocar_senha = 1...</div>";
    try {
        $usuariosComFlag = $db->fetchAll("SELECT id, nome, email, tipo, precisa_trocar_senha FROM usuarios WHERE precisa_trocar_senha = 1");
        $total = count($usuariosComFlag);
        
        if ($total > 0) {
            echo "<div class='warning'>⚠️ Encontrados {$total} usuário(s) com precisa_trocar_senha = 1:</div>";
            echo "<ul>";
            foreach ($usuariosComFlag as $u) {
                echo "<li>ID: {$u['id']}, Nome: " . htmlspecialchars($u['nome']) . ", Email: " . htmlspecialchars($u['email']) . ", Tipo: {$u['tipo']}</li>";
            }
            echo "</ul>";
        } else {
            echo "<div class='success'>✅ Nenhum usuário com precisa_trocar_senha = 1 (todos podem fazer login normalmente).</div>";
        }
    } catch (Exception $e) {
        echo "<div class='warning'>⚠️ Não foi possível verificar usuários (coluna pode não existir ainda): " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    // 4. Instruções
    echo "<div class='info'>";
    echo "<h3>📋 Próximos Passos:</h3>";
    echo "<ol>";
    echo "<li>Agora você pode usar o botão 'Senha' no painel admin para redefinir a senha do Carlos da Silva.</li>";
    echo "<li>O sistema irá automaticamente definir precisa_trocar_senha = 1 após a redefinição.</li>";
    echo "<li>O usuário será forçado a trocar a senha no primeiro login.</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</div>";
    error_log("Erro em admin/criar-coluna-precisa-trocar-senha.php: " . $e->getMessage());
}

echo "</div></body></html>";
?>

