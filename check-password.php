<?php
// =====================================================
// VERIFICAR E CORRIGIR SENHA DO USUÁRIO ADMIN
// =====================================================

require_once 'includes/config.php';
require_once 'includes/database.php';

echo "<h1>Verificação de Senha - Usuário Admin</h1>";

try {
    $db = db();
    
    // Verificar usuário admin
    $admin = $db->fetch("SELECT id, nome, email, senha, tipo, ativo FROM usuarios WHERE email = 'admin@cfc.com'");
    
    if ($admin) {
        echo "<h3>Usuário Admin Encontrado:</h3>";
        echo "<ul>";
        echo "<li>ID: " . $admin['id'] . "</li>";
        echo "<li>Nome: " . $admin['nome'] . "</li>";
        echo "<li>Email: " . $admin['email'] . "</li>";
        echo "<li>Tipo: " . $admin['tipo'] . "</li>";
        echo "<li>Ativo: " . ($admin['ativo'] ? 'Sim' : 'Não') . "</li>";
        echo "<li>Hash da Senha: " . substr($admin['senha'], 0, 50) . "...</li>";
        echo "</ul>";
        
        // Testar diferentes senhas
        $senhas = ['password', 'admin123', 'admin', '123456', 'senha'];
        echo "<h3>Testando Senhas:</h3>";
        
        foreach ($senhas as $senha) {
            if (password_verify($senha, $admin['senha'])) {
                echo "<p>✅ <strong>Senha correta encontrada:</strong> '$senha'</p>";
                break;
            } else {
                echo "<p>❌ Senha '$senha' não confere</p>";
            }
        }
        
        // Se nenhuma senha funcionar, criar uma nova
        $senhaFuncionando = false;
        foreach ($senhas as $senha) {
            if (password_verify($senha, $admin['senha'])) {
                $senhaFuncionando = true;
                break;
            }
        }
        
        if (!$senhaFuncionando) {
            echo "<h3>🔧 Corrigindo Senha:</h3>";
            
            // Criar nova senha
            $novaSenha = 'admin123';
            $novoHash = password_hash($novaSenha, PASSWORD_DEFAULT);
            
            // Atualizar no banco
            $sql = "UPDATE usuarios SET senha = :senha WHERE id = :id";
            $db->query($sql, ['senha' => $novoHash, 'id' => $admin['id']]);
            
            echo "<p>✅ Senha atualizada com sucesso!</p>";
            echo "<p><strong>Nova senha:</strong> $novaSenha</p>";
            echo "<p><strong>Novo hash:</strong> " . substr($novoHash, 0, 50) . "...</p>";
            
            // Verificar se funcionou
            $adminAtualizado = $db->fetch("SELECT senha FROM usuarios WHERE id = :id", ['id' => $admin['id']]);
            if (password_verify($novaSenha, $adminAtualizado['senha'])) {
                echo "<p>✅ <strong>Verificação:</strong> Nova senha funcionando!</p>";
            } else {
                echo "<p>❌ <strong>Erro:</strong> Nova senha não funcionou</p>";
            }
        }
        
    } else {
        echo "<p>❌ Usuário admin não encontrado</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='test-login.php'>← Testar Login Novamente</a></p>";
echo "<p><a href='index.php'>← Ir para o Login</a></p>";
?>
