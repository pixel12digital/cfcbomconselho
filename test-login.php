<?php
// =====================================================
// TESTE SIMPLES DE LOGIN - SISTEMA CFC
// =====================================================

require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

echo "<h1>Teste de Login - Sistema CFC</h1>";

try {
    // Testar conexão com banco
    $db = db();
    echo "<p>✅ Conexão com banco: OK</p>";
    
    // Verificar se usuário admin existe
    $admin = $db->fetch("SELECT id, nome, email, tipo, ativo FROM usuarios WHERE email = 'admin@cfc.com'");
    
    if ($admin) {
        echo "<p>✅ Usuário admin encontrado:</p>";
        echo "<ul>";
        echo "<li>ID: " . $admin['id'] . "</li>";
        echo "<li>Nome: " . $admin['nome'] . "</li>";
        echo "<li>Email: " . $admin['email'] . "</li>";
        echo "<li>Tipo: " . $admin['tipo'] . "</li>";
        echo "<li>Ativo: " . ($admin['ativo'] ? 'Sim' : 'Não') . "</li>";
        echo "</ul>";
        
        // Testar autenticação
        $auth = new Auth();
        $result = $auth->login('admin@cfc.com', 'password');
        
        if ($result['success']) {
            echo "<p>✅ Login bem-sucedido!</p>";
            echo "<p>Mensagem: " . $result['message'] . "</p>";
            
            // Verificar sessão
            if (isset($_SESSION['user_id'])) {
                echo "<p>✅ Sessão criada: " . $_SESSION['user_id'] . "</p>";
            } else {
                echo "<p>⚠️ Sessão não foi criada</p>";
            }
        } else {
            echo "<p>❌ Login falhou: " . $result['message'] . "</p>";
        }
        
    } else {
        echo "<p>❌ Usuário admin não encontrado</p>";
    }
    
    // Verificar tabelas
    $tables = ['usuarios', 'cfcs', 'alunos', 'instrutores', 'veiculos', 'logs', 'sessoes'];
    echo "<h3>Verificação de Tabelas:</h3>";
    
    foreach ($tables as $table) {
        try {
            $result = $db->query("SHOW TABLES LIKE '$table'");
            if ($result->rowCount() > 0) {
                echo "<p>✅ Tabela <strong>$table</strong>: Existe</p>";
            } else {
                echo "<p>❌ Tabela <strong>$table</strong>: Não existe</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Erro ao verificar tabela <strong>$table</strong>: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Voltar para o Login</a></p>";
?>
