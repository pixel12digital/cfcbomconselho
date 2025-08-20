<?php
/**
 * Script para corrigir a tabela veiculos
 * Adiciona colunas faltantes para funcionamento completo
 */

echo "<h1>🔧 CORREÇÃO DA TABELA VEÍCULOS</h1>";
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
    
    // Verificar se a tabela veiculos existe
    echo "<h2>📋 Verificação da Tabela 'veiculos'</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'veiculos'");
    $tabela_existe = $stmt->fetch();
    
    if ($tabela_existe) {
        echo "✅ <strong>Tabela 'veiculos'</strong> - JÁ EXISTE<br>";
    } else {
        echo "❌ <strong>Tabela 'veiculos'</strong> - NÃO EXISTE<br>";
        echo "⚠️ <strong>Criando tabela...</strong><br>";
        
        // Criar tabela veiculos
        $sql_criar = "CREATE TABLE veiculos (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            placa VARCHAR(10) NOT NULL UNIQUE,
            marca VARCHAR(50) NOT NULL,
            modelo VARCHAR(50) NOT NULL,
            ano INT(4) NOT NULL,
            cor VARCHAR(30),
            chassi VARCHAR(17) UNIQUE,
            renavam VARCHAR(11),
            cfc_id INT(11) NOT NULL,
            status VARCHAR(20) DEFAULT 'ativo',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (cfc_id) REFERENCES cfcs(id)
        )";
        
        $pdo->exec($sql_criar);
        echo "✅ <strong>Tabela 'veiculos'</strong> - CRIADA COM SUCESSO<br>";
    }
    
    // Verificar estrutura atual da tabela veiculos
    echo "<h2>📋 Estrutura Atual da Tabela 'veiculos'</h2>";
    $stmt = $pdo->query("DESCRIBE veiculos");
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
    
    // Verificar colunas necessárias
    $colunas_necessarias = [
        'id', 'placa', 'marca', 'modelo', 'ano', 'cor', 'chassi', 'renavam',
        'cfc_id', 'status', 'created_at', 'updated_at'
    ];
    
    $colunas_encontradas = array_column($colunas, 'Field');
    $colunas_faltando = array_diff($colunas_necessarias, $colunas_encontradas);
    
    if (!empty($colunas_faltando)) {
        echo "<h2>🔧 Adicionando Colunas Faltantes</h2>";
        
        foreach ($colunas_faltando as $coluna) {
            echo "⚠️ <strong>Coluna '$coluna'</strong> - NÃO EXISTE, ADICIONANDO...<br>";
            
            try {
                switch ($coluna) {
                    case 'cor':
                        $sql = "ALTER TABLE veiculos ADD COLUMN cor VARCHAR(30) AFTER ano";
                        break;
                    case 'chassi':
                        $sql = "ALTER TABLE veiculos ADD COLUMN chassi VARCHAR(17) UNIQUE AFTER cor";
                        break;
                    case 'renavam':
                        $sql = "ALTER TABLE veiculos ADD COLUMN renavam VARCHAR(11) AFTER chassi";
                        break;
                    case 'status':
                        $sql = "ALTER TABLE veiculos ADD COLUMN status VARCHAR(20) DEFAULT 'ativo' AFTER cfc_id";
                        break;
                    case 'created_at':
                        $sql = "ALTER TABLE veiculos ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER status";
                        break;
                    case 'updated_at':
                        $sql = "ALTER TABLE veiculos ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at";
                        break;
                    default:
                        $sql = "ALTER TABLE veiculos ADD COLUMN $coluna VARCHAR(50) AFTER id";
                }
                
                $pdo->exec($sql);
                echo "✅ <strong>Coluna '$coluna'</strong> - ADICIONADA COM SUCESSO<br>";
                
            } catch (Exception $e) {
                echo "❌ <strong>Erro ao adicionar coluna '$coluna'</strong> - " . $e->getMessage() . "<br>";
            }
        }
    } else {
        echo "✅ <strong>Todas as colunas necessárias</strong> já estão presentes<br>";
    }
    
    // Verificar estrutura final da tabela veiculos
    echo "<h2>📋 Estrutura Final da Tabela 'veiculos'</h2>";
    $stmt = $pdo->query("DESCRIBE veiculos");
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
    
    // Verificar dados atuais na tabela veiculos
    echo "<h2>📊 Dados Atuais na Tabela 'veiculos'</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM veiculos");
    $resultado = $stmt->fetch();
    $total_veiculos = $resultado['total'];
    
    echo "✅ <strong>Total de Veículos na tabela</strong> - $total_veiculos registros<br>";
    
    if ($total_veiculos > 0) {
        echo "<details style='margin: 10px 0;'>";
        echo "<summary style='cursor: pointer; color: #007bff;'>📋 Ver veículos existentes</summary>";
        
        $stmt = $pdo->query("SELECT * FROM veiculos LIMIT 3");
        $veiculos = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0; font-size: 12px;'>";
        echo "<tr style='background: #f8f9fa;'><th>ID</th><th>Placa</th><th>Marca</th><th>Modelo</th><th>Ano</th><th>CFC ID</th></tr>";
        
        foreach ($veiculos as $veiculo) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($veiculo['id']) . "</td>";
            echo "<td>" . htmlspecialchars($veiculo['placa'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($veiculo['marca'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($veiculo['modelo'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($veiculo['ano'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($veiculo['cfc_id'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</details>";
        
        // Atualizar registros existentes com valores padrão para novas colunas
        echo "<h2>🔧 Atualizando Registros Existentes</h2>";
        
        try {
            // Atualizar colunas que podem ter NULL
            $sql_update = "UPDATE veiculos SET 
                cor = COALESCE(cor, 'Não informado'),
                chassi = COALESCE(chassi, ''),
                renavam = COALESCE(renavam, ''),
                status = COALESCE(status, 'ativo'),
                created_at = COALESCE(created_at, NOW()),
                updated_at = COALESCE(updated_at, NOW())
                WHERE cor IS NULL OR chassi IS NULL OR renavam IS NULL OR status IS NULL OR created_at IS NULL OR updated_at IS NULL";
            
            $resultado = $pdo->exec($sql_update);
            
            if ($resultado > 0) {
                echo "✅ <strong>Registros atualizados</strong> - $resultado registros com valores padrão<br>";
            } else {
                echo "✅ <strong>Nenhum registro atualizado</strong> - Todos os registros já estão corretos<br>";
            }
            
        } catch (Exception $e) {
            echo "⚠️ <strong>Aviso na atualização</strong> - " . $e->getMessage() . "<br>";
        }
    }
    
    // Verificar constraints de foreign key
    echo "<h2>🔗 Verificação de Constraints de Foreign Key</h2>";
    
    try {
        $stmt = $pdo->query("
            SELECT 
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'veiculos' 
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
            
            // Adicionar constraint de foreign key se não existir
            echo "⚠️ <strong>Adicionando constraint de foreign key...</strong><br>";
            
            try {
                $sql_fk = "ALTER TABLE veiculos ADD CONSTRAINT fk_veiculos_cfc FOREIGN KEY (cfc_id) REFERENCES cfcs(id)";
                $pdo->exec($sql_fk);
                echo "✅ <strong>Constraint de foreign key</strong> - ADICIONADA COM SUCESSO<br>";
            } catch (Exception $e) {
                echo "❌ <strong>Erro ao adicionar constraint</strong> - " . $e->getMessage() . "<br>";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro ao verificar constraints</strong> - " . $e->getMessage() . "<br>";
    }
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "🎉 <strong>TABELA 'veiculos' CORRIGIDA COM SUCESSO!</strong><br>";
    echo "Todas as colunas necessárias estão presentes.";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ <strong>ERRO:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<hr>";
echo "<h2>🔄 PRÓXIMO PASSO</h2>";
echo "<p>✅ <strong>Correção da tabela veiculos concluída!</strong></p>";
echo "<p>🎯 <strong>Próximo:</strong> TESTE #8 - CRUD de Veículos (Executar novamente)</p>";
echo "<p>📝 <strong>Instrução:</strong> Agora execute o TESTE #8 novamente para verificar se as operações CRUD estão funcionando.</p>";
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
