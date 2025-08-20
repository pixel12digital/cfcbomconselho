<?php
/**
 * Script para corrigir a estrutura da tabela instrutores
 * Adiciona as colunas necessárias que estão faltando
 */

echo "<h1>🔧 CORREÇÃO DA TABELA INSTRUTORES</h1>";
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
    
    // Verificar se a tabela instrutores existe
    echo "<h2>📋 Verificação da Tabela 'instrutores'</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'instrutores'");
    $tabela_existe = $stmt->fetch();
    
    if (!$tabela_existe) {
        echo "⚠️ <strong>Tabela 'instrutores'</strong> - NÃO EXISTE, CRIANDO...<br>";
        
        // Criar tabela instrutores completa
        $sql_criar = "CREATE TABLE instrutores (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            cpf VARCHAR(14) UNIQUE NOT NULL,
            cnh VARCHAR(20) UNIQUE NOT NULL,
            data_nascimento DATE NOT NULL,
            telefone VARCHAR(20),
            email VARCHAR(100),
            endereco TEXT,
            cfc_id INT NOT NULL,
            status VARCHAR(20) DEFAULT 'ativo',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (cfc_id) REFERENCES cfcs(id)
        )";
        
        $pdo->exec($sql_criar);
        echo "✅ <strong>Tabela 'instrutores'</strong> - CRIADA COM SUCESSO<br>";
        
    } else {
        echo "✅ <strong>Tabela 'instrutores'</strong> - JÁ EXISTE<br>";
        
        // Verificar estrutura atual da tabela
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
        
        // Verificar se as colunas necessárias já existem
        $colunas_existentes = array_column($colunas, 'Field');
        
        // Verificar se a coluna 'nome' existe
        if (in_array('nome', $colunas_existentes)) {
            echo "✅ <strong>Coluna 'nome'</strong> - JÁ EXISTE<br>";
        } else {
            echo "⚠️ <strong>Coluna 'nome'</strong> - NÃO EXISTE, ADICIONANDO...<br>";
            
            // Adicionar coluna 'nome'
            $sql = "ALTER TABLE instrutores ADD COLUMN nome VARCHAR(100) NOT NULL AFTER id";
            $pdo->exec($sql);
            
            echo "✅ <strong>Coluna 'nome'</strong> - ADICIONADA COM SUCESSO<br>";
        }
        
        // Verificar se a coluna 'cpf' existe
        if (in_array('cpf', $colunas_existentes)) {
            echo "✅ <strong>Coluna 'cpf'</strong> - JÁ EXISTE<br>";
        } else {
            echo "⚠️ <strong>Coluna 'cpf'</strong> - NÃO EXISTE, ADICIONANDO...<br>";
            
            // Adicionar coluna 'cpf'
            $sql = "ALTER TABLE instrutores ADD COLUMN cpf VARCHAR(14) UNIQUE NOT NULL AFTER nome";
            $pdo->exec($sql);
            
            echo "✅ <strong>Coluna 'cpf'</strong> - ADICIONADA COM SUCESSO<br>";
        }
        
        // Verificar se a coluna 'cnh' existe
        if (in_array('cnh', $colunas_existentes)) {
            echo "✅ <strong>Coluna 'cnh'</strong> - JÁ EXISTE<br>";
        } else {
            echo "⚠️ <strong>Coluna 'cnh'</strong> - NÃO EXISTE, ADICIONANDO...<br>";
            
            // Adicionar coluna 'cnh'
            $sql = "ALTER TABLE instrutores ADD COLUMN cnh VARCHAR(20) UNIQUE NOT NULL AFTER cpf";
            $pdo->exec($sql);
            
            echo "✅ <strong>Coluna 'cnh'</strong> - ADICIONADA COM SUCESSO<br>";
        }
        
        // Verificar se a coluna 'data_nascimento' existe
        if (in_array('data_nascimento', $colunas_existentes)) {
            echo "✅ <strong>Coluna 'data_nascimento'</strong> - JÁ EXISTE<br>";
        } else {
            echo "⚠️ <strong>Coluna 'data_nascimento'</strong> - NÃO EXISTE, ADICIONANDO...<br>";
            
            // Adicionar coluna 'data_nascimento'
            $sql = "ALTER TABLE instrutores ADD COLUMN data_nascimento DATE NOT NULL AFTER cnh";
            $pdo->exec($sql);
            
            echo "✅ <strong>Coluna 'data_nascimento'</strong> - ADICIONADA COM SUCESSO<br>";
        }
        
        // Verificar se a coluna 'telefone' existe
        if (in_array('telefone', $colunas_existentes)) {
            echo "✅ <strong>Coluna 'telefone'</strong> - JÁ EXISTE<br>";
        } else {
            echo "⚠️ <strong>Coluna 'telefone'</strong> - NÃO EXISTE, ADICIONANDO...<br>";
            
            // Adicionar coluna 'telefone'
            $sql = "ALTER TABLE instrutores ADD COLUMN telefone VARCHAR(20) AFTER data_nascimento";
            $pdo->exec($sql);
            
            echo "✅ <strong>Coluna 'telefone'</strong> - ADICIONADA COM SUCESSO<br>";
        }
        
        // Verificar se a coluna 'email' existe
        if (in_array('email', $colunas_existentes)) {
            echo "✅ <strong>Coluna 'email'</strong> - JÁ EXISTE<br>";
        } else {
            echo "⚠️ <strong>Coluna 'email'</strong> - NÃO EXISTE, ADICIONANDO...<br>";
            
            // Adicionar coluna 'email'
            $sql = "ALTER TABLE instrutores ADD COLUMN email VARCHAR(100) AFTER telefone";
            $pdo->exec($sql);
            
            echo "✅ <strong>Coluna 'email'</strong> - ADICIONADA COM SUCESSO<br>";
        }
        
        // Verificar se a coluna 'endereco' existe
        if (in_array('endereco', $colunas_existentes)) {
            echo "✅ <strong>Coluna 'endereco'</strong> - JÁ EXISTE<br>";
        } else {
            echo "⚠️ <strong>Coluna 'endereco'</strong> - NÃO EXISTE, ADICIONANDO...<br>";
            
            // Adicionar coluna 'endereco'
            $sql = "ALTER TABLE instrutores ADD COLUMN endereco TEXT AFTER email";
            $pdo->exec($sql);
            
            echo "✅ <strong>Coluna 'endereco'</strong> - ADICIONADA COM SUCESSO<br>";
        }
        
        // Verificar se a coluna 'status' existe
        if (in_array('status', $colunas_existentes)) {
            echo "✅ <strong>Coluna 'status'</strong> - JÁ EXISTE<br>";
        } else {
            echo "⚠️ <strong>Coluna 'status'</strong> - NÃO EXISTE, ADICIONANDO...<br>";
            
            // Adicionar coluna 'status'
            $sql = "ALTER TABLE instrutores ADD COLUMN status VARCHAR(20) DEFAULT 'ativo' AFTER cfc_id";
            $pdo->exec($sql);
            
            echo "✅ <strong>Coluna 'status'</strong> - ADICIONADA COM SUCESSO<br>";
        }
        
        // Verificar se a coluna 'created_at' existe
        if (in_array('created_at', $colunas_existentes)) {
            echo "✅ <strong>Coluna 'created_at'</strong> - JÁ EXISTE<br>";
        } else {
            echo "⚠️ <strong>Coluna 'created_at'</strong> - NÃO EXISTE, ADICIONANDO...<br>";
            
            // Adicionar coluna 'created_at'
            $sql = "ALTER TABLE instrutores ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER status";
            $pdo->exec($sql);
            
            echo "✅ <strong>Coluna 'created_at'</strong> - ADICIONADA COM SUCESSO<br>";
        }
        
        // Verificar se a coluna 'updated_at' existe
        if (in_array('updated_at', $colunas_existentes)) {
            echo "✅ <strong>Coluna 'updated_at'</strong> - JÁ EXISTE<br>";
        } else {
            echo "⚠️ <strong>Coluna 'updated_at'</strong> - NÃO EXISTE, ADICIONANDO...<br>";
            
            // Adicionar coluna 'updated_at'
            $sql = "ALTER TABLE instrutores ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at";
            $pdo->exec($sql);
            
            echo "✅ <strong>Coluna 'updated_at'</strong> - ADICIONADA COM SUCESSO<br>";
        }
        
        // Verificar se a coluna 'cfc_id' existe
        if (in_array('cfc_id', $colunas_existentes)) {
            echo "✅ <strong>Coluna 'cfc_id'</strong> - JÁ EXISTE<br>";
        } else {
            echo "⚠️ <strong>Coluna 'cfc_id'</strong> - NÃO EXISTE, ADICIONANDO...<br>";
            
            // Adicionar coluna 'cfc_id'
            $sql = "ALTER TABLE instrutores ADD COLUMN cfc_id INT NOT NULL AFTER endereco";
            $pdo->exec($sql);
            
            echo "✅ <strong>Coluna 'cfc_id'</strong> - ADICIONADA COM SUCESSO<br>";
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
        
        // Verificar se todas as colunas necessárias estão presentes
        $colunas_necessarias = ['id', 'nome', 'cpf', 'cnh', 'data_nascimento', 'telefone', 'email', 'endereco', 'cfc_id', 'status', 'created_at', 'updated_at'];
        $colunas_finais_nomes = array_column($colunas_finais, 'Field');
        
        $colunas_faltando = array_diff($colunas_necessarias, $colunas_finais_nomes);
        
        if (empty($colunas_faltando)) {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "🎉 <strong>TABELA 'instrutores' CORRIGIDA COM SUCESSO!</strong><br>";
            echo "Todas as colunas necessárias estão presentes.";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "⚠️ <strong>ATENÇÃO:</strong> Ainda faltam colunas: " . implode(', ', $colunas_faltando);
            echo "</div>";
        }
    }
    
    // Verificar dados existentes
    echo "<h2>📊 Dados Atuais na Tabela 'instrutores'</h2>";
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
    } else {
        echo "<p>Nenhum instrutor encontrado na tabela.</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ <strong>ERRO:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<hr>";
echo "<h2>🔄 PRÓXIMO PASSO</h2>";
echo "<p>✅ <strong>Correção da tabela instrutores concluída!</strong></p>";
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
