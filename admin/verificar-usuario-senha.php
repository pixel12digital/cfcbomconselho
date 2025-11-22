<?php
/**
 * Script de Verificação e Correção de Senha de Usuário
 * 
 * Este script verifica se o usuário existe e se a senha está correta.
 * Se necessário, atualiza a senha com hash bcrypt correto.
 * 
 * SEGURANÇA: Este script deve ser removido após uso em produção!
 */

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Verificar se é admin ou secretaria (segurança)
// Secretaria também pode gerenciar usuários, então pode usar este script
if (!isLoggedIn() || !canManageUsers()) {
    die('Acesso negado. Apenas administradores e atendentes podem usar este script.');
}

$email = 'carlosteste@teste.com.br';
$senhaPlana = 'Los@ngo#081081';

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Verificação de Usuário e Senha</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        .info { background: #e7f3ff; padding: 15px; border-left: 4px solid #007bff; margin: 15px 0; border-radius: 5px; }
        .success { background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 15px 0; border-radius: 5px; }
        .error { background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 15px 0; border-radius: 5px; }
        .warning { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0; border-radius: 5px; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #007bff; color: white; }
        tr:hover { background: #f5f5f5; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 Verificação de Usuário e Senha</h1>";

try {
    $db = db();
    
    echo "<div class='info'>
            <strong>📧 Email:</strong> {$email}<br>
            <strong>🔑 Senha (plana):</strong> {$senhaPlana}
          </div>";
    
    // 1. Buscar usuário no banco
    echo "<h2>1. Buscando usuário no banco de dados...</h2>";
    $usuario = $db->fetch("SELECT id, nome, email, tipo, ativo, senha, LENGTH(senha) as senha_length FROM usuarios WHERE email = ?", [$email]);
    
    if (!$usuario) {
        echo "<div class='error'>
                <strong>❌ Usuário não encontrado!</strong><br>
                Não existe nenhum usuário com o email: <strong>{$email}</strong>
              </div>";
        echo "<p><a href='index.php?page=usuarios' class='btn'>Voltar para Usuários</a></p>";
        echo "</div></body></html>";
        exit;
    }
    
    echo "<div class='success'>
            <strong>✅ Usuário encontrado!</strong>
          </div>";
    
    echo "<table>
            <tr><th>Campo</th><th>Valor</th></tr>
            <tr><td>ID</td><td>{$usuario['id']}</td></tr>
            <tr><td>Nome</td><td>{$usuario['nome']}</td></tr>
            <tr><td>Email</td><td>{$usuario['email']}</td></tr>
            <tr><td>Tipo</td><td>{$usuario['tipo']}</td></tr>
            <tr><td>Ativo</td><td>" . ($usuario['ativo'] ? '✅ Sim' : '❌ Não') . "</td></tr>
            <tr><td>Comprimento da Senha (hash)</td><td>{$usuario['senha_length']} caracteres</td></tr>
          </table>";
    
    // 2. Verificar formato do hash
    echo "<h2>2. Verificando formato do hash da senha...</h2>";
    $senhaHash = $usuario['senha'];
    $isBcrypt = (strpos($senhaHash, '$2y$') === 0 || strpos($senhaHash, '$2a$') === 0 || strpos($senhaHash, '$2b$') === 0);
    
    if ($isBcrypt) {
        $hashPreview = substr($senhaHash, 0, 7);
        echo "<div class='success'>";
        echo "<strong>✅ Hash está no formato bcrypt correto!</strong><br>";
        echo "Formato: " . htmlspecialchars($hashPreview) . "...";
        echo "</div>";
    } else {
        $hashPreview = substr($senhaHash, 0, 20);
        echo "<div class='warning'>";
        echo "<strong>⚠️ Hash NÃO está no formato bcrypt!</strong><br>";
        echo "Formato atual: " . htmlspecialchars($hashPreview) . "...<br>";
        echo "<strong>Isso pode ser o problema!</strong>";
        echo "</div>";
    }
    
    // 3. Testar senha atual
    echo "<h2>3. Testando senha fornecida...</h2>";
    $senhaValida = password_verify($senhaPlana, $senhaHash);
    
    if ($senhaValida) {
        echo "<div class='success'>
                <strong>✅ Senha está CORRETA!</strong><br>
                A senha fornecida corresponde ao hash armazenado no banco.
              </div>";
    } else {
        echo "<div class='error'>
                <strong>❌ Senha está INCORRETA!</strong><br>
                A senha fornecida NÃO corresponde ao hash armazenado no banco.
              </div>";
        
        // 4. Oferecer correção
        if (isset($_POST['corrigir_senha'])) {
            echo "<h2>4. Corrigindo senha...</h2>";
            
            $novoHash = password_hash($senhaPlana, PASSWORD_DEFAULT);
            
            try {
                $db->query("UPDATE usuarios SET senha = ? WHERE id = ?", [$novoHash, $usuario['id']]);
                
                echo "<div class='success'>
                        <strong>✅ Senha atualizada com sucesso!</strong><br>
                        Novo hash gerado e armazenado no banco de dados.
                      </div>";
                
                // Testar novamente
                $senhaValidaApos = password_verify($senhaPlana, $novoHash);
                if ($senhaValidaApos) {
                    echo "<div class='success'>
                            <strong>✅ Confirmação: Senha agora está funcionando corretamente!</strong>
                          </div>";
                }
                
            } catch (Exception $e) {
                echo "<div class='error'>
                        <strong>❌ Erro ao atualizar senha:</strong><br>
                        " . htmlspecialchars($e->getMessage()) . "
                      </div>";
            }
        } else {
            echo "<h2>4. Correção disponível</h2>";
            echo "<div class='warning'>
                    <strong>⚠️ A senha não está funcionando.</strong><br>
                    Você pode corrigir atualizando o hash da senha no banco de dados.
                  </div>";
            
            echo "<form method='POST' style='margin: 20px 0;'>
                    <input type='hidden' name='corrigir_senha' value='1'>
                    <button type='submit' class='btn btn-danger' onclick='return confirm(\"Tem certeza que deseja atualizar a senha deste usuário?\")'>
                        🔧 Corrigir Senha no Banco
                    </button>
                  </form>";
        }
    }
    
    // 5. Informações adicionais
    echo "<h2>5. Informações adicionais</h2>";
    echo "<div class='info'>
            <strong>📝 Notas:</strong><br>
            • O sistema usa <code>password_verify()</code> para validar senhas<br>
            • Senhas devem estar hashadas com <code>password_hash()</code> usando <code>PASSWORD_DEFAULT</code> (bcrypt)<br>
            • Hash bcrypt sempre começa com <code>\$2y\$</code>, <code>\$2a\$</code> ou <code>\$2b\$</code><br>
            • Hash bcrypt tem sempre 60 caracteres<br>
            • Se a senha foi modificada manualmente no banco, pode não estar no formato correto
          </div>";
    
    // 6. Gerar novo hash para referência
    echo "<h2>6. Hash de referência</h2>";
    $hashReferencia = password_hash($senhaPlana, PASSWORD_DEFAULT);
    echo "<div class='info'>
            <strong>Hash gerado para a senha fornecida:</strong><br>
            <pre>" . htmlspecialchars($hashReferencia) . "</pre>
            <small>Este é o formato que deveria estar no banco de dados.</small>
          </div>";
    
    echo "<p><a href='index.php?page=usuarios' class='btn'>Voltar para Usuários</a></p>";
    
} catch (Exception $e) {
    echo "<div class='error'>
            <strong>❌ Erro:</strong><br>
            " . htmlspecialchars($e->getMessage()) . "
          </div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</div></body></html>";
?>

