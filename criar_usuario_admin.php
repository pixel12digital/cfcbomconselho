<?php
// Script para criar usuário admin se não existir
require_once 'includes/config.php';
require_once 'includes/database.php';

echo "<h2>🔍 Verificando usuário admin...</h2>";

try {
    $db = Database::getInstance();
    
    // Verificar se existe usuário admin
    $admin = $db->fetch("SELECT id, nome, email, tipo FROM usuarios WHERE tipo = 'admin' LIMIT 1");
    
    if ($admin) {
        echo "<p style='color: green;'>✅ Usuário admin encontrado:</p>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> {$admin['id']}</li>";
        echo "<li><strong>Nome:</strong> {$admin['nome']}</li>";
        echo "<li><strong>Email:</strong> {$admin['email']}</li>";
        echo "<li><strong>Tipo:</strong> {$admin['tipo']}</li>";
        echo "</ul>";
        echo "<p><strong>🔑 Use este usuário para fazer login no sistema!</strong></p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Nenhum usuário admin encontrado. Criando um...</p>";
        
        // Criar usuário admin padrão
        $senha_hash = password_hash('admin123', PASSWORD_DEFAULT);
        
        $result = $db->insert('usuarios', [
            'nome' => 'Administrador',
            'email' => 'admin@cfc.com',
            'senha' => $senha_hash,
            'tipo' => 'admin',
            'ativo' => true,
            'criado_em' => date('Y-m-d H:i:s')
        ]);
        
        if ($result) {
            echo "<p style='color: green;'>✅ Usuário admin criado com sucesso!</p>";
            echo "<p><strong>🔑 Credenciais de acesso:</strong></p>";
            echo "<ul>";
            echo "<li><strong>Email:</strong> admin@cfc.com</li>";
            echo "<li><strong>Senha:</strong> admin123</li>";
            echo "</ul>";
            echo "<p><strong>⚠️ IMPORTANTE: Altere a senha após o primeiro login!</strong></p>";
        } else {
            echo "<p style='color: red;'>❌ Erro ao criar usuário admin</p>";
        }
    }
    
    // Mostrar todos os usuários
    echo "<hr>";
    echo "<h3>👥 Todos os usuários no sistema:</h3>";
    $usuarios = $db->fetchAll("SELECT id, nome, email, tipo, ativo FROM usuarios ORDER BY tipo, nome");
    
    if ($usuarios) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th><th>Nome</th><th>Email</th><th>Tipo</th><th>Status</th>";
        echo "</tr>";
        
        foreach ($usuarios as $usuario) {
            $status = $usuario['ativo'] ? 'Ativo' : 'Inativo';
            $statusColor = $usuario['ativo'] ? 'green' : 'red';
            echo "<tr>";
            echo "<td>{$usuario['id']}</td>";
            echo "<td>{$usuario['nome']}</td>";
            echo "<td>{$usuario['email']}</td>";
            echo "<td>{$usuario['tipo']}</td>";
            echo "<td style='color: {$statusColor};'>{$status}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Nenhum usuário encontrado.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>📋 Próximos passos:</strong></p>";
echo "<ol>";
echo "<li>Faça login no sistema com as credenciais acima</li>";
echo "<li>Acesse: <a href='admin/'>http://localhost:8080/cfc-bom-conselho/admin/</a></li>";
echo "<li>Teste as APIs novamente</li>";
echo "</ol>";
?>
