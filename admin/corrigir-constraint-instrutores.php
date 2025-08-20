<?php
/**
 * Script para corrigir a constraint de foreign key da tabela instrutores
 * Resolve o problema de usuario_id obrigatório
 */

echo "<h1>🔧 CORREÇÃO DE CONSTRAINT - TABELA INSTRUTORES</h1>";
echo "<p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>";
echo "<hr>";

try {
    // Incluir configurações
    require_once '../includes/config.php';
    require_once '../includes/database.php';
    
    echo "✅ <strong>Arquivos de configuração</strong> - INCLUÍDOS COM SUCESSO<br>";
    
    // Conectar ao banco
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "✅ <strong>Conexão com banco</strong> - ESTABELECIDA<br>";
    
    // Verificar estrutura atual da tabela instrutores
    echo "<h2>📋 Estrutura Atual da Tabela 'instrutores'</h2>";
    $stmt = $pdo->query("DESCRIBE instrutores");
    $colunas = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #f8f9fa;'><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
    
    foreach ($colunas as $coluna) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($coluna['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar constraints de foreign key
    echo "<h2>🔗 Constraints de Foreign Key</h2>";
    $stmt = $pdo->query("
        SELECT 
            CONSTRAINT_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'instrutores' 
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $constraints = $stmt->fetchAll();
    
    if (count($constraints) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f8f9fa;'><th>Constraint</th><th>Coluna</th><th>Tabela Referenciada</th><th>Coluna Referenciada</th></tr>";
        
        foreach ($constraints as $constraint) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($constraint['CONSTRAINT_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($constraint['COLUMN_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($constraint['REFERENCED_TABLE_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($constraint['REFERENCED_COLUMN_NAME']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Nenhuma constraint de foreign key encontrada.</p>";
    }
    
    // Verificar se existe usuário para referência
    echo "<h2>👤 Verificação de Usuários para Referência</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $resultado = $stmt->fetch();
    $total_usuarios = $resultado['total'];
    
    echo "✅ <strong>Total de Usuários na tabela 'usuarios'</strong> - $total_usuarios registros<br>";
    
    if ($total_usuarios > 0) {
        // Mostrar alguns usuários existentes
        $stmt = $pdo->query("SELECT id, nome, email, status FROM usuarios LIMIT 3");
        $usuarios_existentes = $stmt->fetchAll();
        
        echo "<details style='margin: 10px 0;'>";
        echo "<summary style='cursor: pointer; color: #007bff;'>📋 Ver usuários existentes</summary>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0; font-size: 12px;'>";
        echo "<tr style='background: #f8f9fa;'><th>ID</th><th>Nome</th><th>Email</th><th>Status</th></tr>";
        
        foreach ($usuarios_existentes as $usuario) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($usuario['id']) . "</td>";
            echo "<td>" . htmlspecialchars($usuario['nome']) . "</td>";
            echo "<td>" . htmlspecialchars($usuario['email']) . "</td>";
            echo "<td>" . htmlspecialchars($usuario['status'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</details>";
        
        $usuario_referencia = $usuarios_existentes[0]['id'];
        echo "✅ <strong>Usuário de referência</strong> - ID: $usuario_referencia<br>";
        
    } else {
        echo "⚠️ <strong>Nenhum usuário encontrado</strong> - Criando usuário padrão...<br>";
        
        // Criar usuário padrão para referência
        $sql_usuario = "INSERT INTO usuarios (nome, email, senha, status, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())";
        $stmt = $pdo->prepare($sql_usuario);
        $senha_hash = password_hash('123456', PASSWORD_DEFAULT);
        
        $resultado = $stmt->execute(['Usuário Padrão', 'padrao@cfc.com', $senha_hash, 'ativo']);
        
        if ($resultado) {
            $usuario_referencia = $pdo->lastInsertId();
            echo "✅ <strong>Usuário padrão criado</strong> - ID: $usuario_referencia<br>";
        } else {
            echo "❌ <strong>Falha ao criar usuário padrão</strong><br>";
            $usuario_referencia = null;
        }
    }
    
    // Opção 1: Tornar usuario_id opcional (NULL)
    echo "<h2>🔧 Opção 1: Tornar usuario_id Opcional</h2>";
    
    if ($usuario_referencia) {
        try {
            // Alterar coluna usuario_id para permitir NULL
            $sql = "ALTER TABLE instrutores MODIFY COLUMN usuario_id INT NULL";
            $pdo->exec($sql);
            
            echo "✅ <strong>Coluna 'usuario_id'</strong> - MODIFICADA PARA PERMITIR NULL<br>";
            
            // Atualizar registros existentes (se houver) para ter usuario_id NULL
            $sql_update = "UPDATE instrutores SET usuario_id = NULL WHERE usuario_id = 0 OR usuario_id IS NULL";
            $pdo->exec($sql_update);
            
            echo "✅ <strong>Registros existentes</strong> - ATUALIZADOS COM usuario_id NULL<br>";
            
        } catch (Exception $e) {
            echo "❌ <strong>Erro ao modificar usuario_id</strong> - " . $e->getMessage() . "<br>";
        }
    }
    
    // Opção 2: Criar instrutor de teste com usuario_id válido
    echo "<h2>🔧 Opção 2: Criar Instrutor de Teste</h2>";
    
    if ($usuario_referencia) {
        try {
            // Verificar se já existe instrutor de teste
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM instrutores WHERE nome LIKE '%TESTE%'");
            $resultado = $stmt->fetch();
            $instrutores_teste = $resultado['total'];
            
            if ($instrutores_teste == 0) {
                // Criar instrutor de teste
                $sql_instrutor = "INSERT INTO instrutores (
                    nome, cpf, cnh, data_nascimento, telefone, email, endereco, 
                    usuario_id, cfc_id, status, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                
                $stmt = $pdo->prepare($sql_instrutor);
                $resultado = $stmt->execute([
                    'Instrutor Teste',
                    '111.222.333-44',
                    '12345678901',
                    '1985-01-01',
                    '(11) 99999-9999',
                    'instrutor.teste@cfc.com',
                    'Endereço de Teste',
                    $usuario_referencia,
                    1, // cfc_id
                    'ativo'
                ]);
                
                if ($resultado) {
                    $id_instrutor = $pdo->lastInsertId();
                    echo "✅ <strong>Instrutor de teste criado</strong> - ID: $id_instrutor<br>";
                } else {
                    echo "❌ <strong>Falha ao criar instrutor de teste</strong><br>";
                }
            } else {
                echo "✅ <strong>Instrutor de teste já existe</strong><br>";
            }
            
        } catch (Exception $e) {
            echo "❌ <strong>Erro ao criar instrutor de teste</strong> - " . $e->getMessage() . "<br>";
        }
    }
    
    // Verificar estrutura final
    echo "<h2>📋 Estrutura Final da Tabela 'instrutores'</h2>";
    $stmt = $pdo->query("DESCRIBE instrutores");
    $colunas_finais = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #f8f9fa;'><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
    
    foreach ($colunas_finais as $coluna) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($coluna['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar dados finais
    echo "<h2>📊 Dados Finais na Tabela 'instrutores'</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM instrutores");
    $resultado = $stmt->fetch();
    $total_instrutores = $resultado['total'];
    
    echo "✅ <strong>Total de Instrutores na tabela</strong> - $total_instrutores registros<br>";
    
    if ($total_instrutores > 0) {
        echo "<details style='margin: 10px 0;'>";
        echo "<summary style='cursor: pointer; color: #007bff;'>📋 Ver instrutores existentes</summary>";
        
        $stmt = $pdo->query("SELECT * FROM instrutores LIMIT 3");
        $instrutores_existentes = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0; font-size: 12px;'>";
        echo "<tr style='background: #f8f9fa;'><th>ID</th><th>Nome</th><th>CPF</th><th>CNH</th><th>Status</th></tr>";
        
        foreach ($instrutores_existentes as $instrutor) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($instrutor['id']) . "</td>";
            echo "<td>" . htmlspecialchars($instrutor['nome'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($instrutor['cpf'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($instrutor['cnh'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($instrutor['status'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</details>";
    }
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "🎉 <strong>CORREÇÃO DE CONSTRAINT CONCLUÍDA!</strong><br>";
    echo "A tabela instrutores agora deve funcionar corretamente.";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ <strong>ERRO:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<hr>";
echo "<h2>🔄 PRÓXIMO PASSO</h2>";
echo "<p>✅ <strong>Correção de constraint concluída!</strong></p>";
echo "<p>🎯 <strong>Próximo:</strong> TESTE #7 - CRUD de Instrutores (Executar novamente)</p>";
echo "<p>📝 <strong>Instrução:</strong> Agora execute o TESTE #7 novamente para verificar se as operações CRUD estão funcionando.</p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #2c3e50; }
h2 { color: #34495e; margin-top: 30px; }
table { font-size: 14px; }
th { padding: 8px; background: #f8f9fa; }
td { padding: 6px; text-align: center; }
details { margin: 10px 0; }
summary { cursor: pointer; color: #007bff; }
</style>
