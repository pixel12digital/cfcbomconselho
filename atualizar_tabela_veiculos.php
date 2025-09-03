<?php
/**
 * Script para atualizar a tabela veiculos com os campos faltantes
 * Execute este arquivo uma vez para sincronizar a estrutura da tabela com o formulário
 */

// Configurações do banco de dados
$host = 'localhost';
$dbname = 'u342734079_cfcbomconselho';
$username = 'u342734079_cfcbomconselho';
$password = 'Cfc@2024';

try {
    // Conectar ao banco de dados
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Conectado ao banco de dados com sucesso!\n\n";
    
    // Verificar se os campos já existem
    $stmt = $pdo->query("DESCRIBE veiculos");
    $campos_existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📋 Campos existentes na tabela veiculos:\n";
    foreach ($campos_existentes as $campo) {
        echo "  - $campo\n";
    }
    echo "\n";
    
    // Lista de campos que precisam ser adicionados
    $campos_necessarios = [
        'cor' => "ADD COLUMN cor VARCHAR(50) NULL COMMENT 'Cor do veículo' AFTER categoria_cnh",
        'chassi' => "ADD COLUMN chassi VARCHAR(50) NULL COMMENT 'Número do chassi' AFTER cor",
        'renavam' => "ADD COLUMN renavam VARCHAR(20) NULL COMMENT 'Número do RENAVAM' AFTER chassi",
        'combustivel' => "ADD COLUMN combustivel ENUM('gasolina', 'etanol', 'flex', 'diesel', 'eletrico', 'hibrido') NULL COMMENT 'Tipo de combustível' AFTER renavam",
        'quilometragem' => "ADD COLUMN quilometragem INT NULL DEFAULT 0 COMMENT 'Quilometragem atual em km' AFTER combustivel",
        'km_manutencao' => "ADD COLUMN km_manutencao INT NULL COMMENT 'Quilometragem para próxima manutenção' AFTER quilometragem",
        'data_aquisicao' => "ADD COLUMN data_aquisicao DATE NULL COMMENT 'Data de aquisição do veículo' AFTER km_manutencao",
        'valor_aquisicao' => "ADD COLUMN valor_aquisicao DECIMAL(10,2) NULL COMMENT 'Valor de aquisição' AFTER data_aquisicao",
        'proxima_manutencao' => "ADD COLUMN proxima_manutencao DATE NULL COMMENT 'Data da próxima manutenção' AFTER valor_aquisicao",
        'disponivel' => "ADD COLUMN disponivel BOOLEAN DEFAULT TRUE COMMENT 'Disponibilidade do veículo' AFTER proxima_manutencao",
        'observacoes' => "ADD COLUMN observacoes TEXT NULL COMMENT 'Observações sobre o veículo' AFTER disponivel",
        'status' => "ADD COLUMN status ENUM('ativo', 'inativo', 'manutencao') DEFAULT 'ativo' COMMENT 'Status do veículo' AFTER observacoes",
        'atualizado_em' => "ADD COLUMN atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data de atualização' AFTER status"
    ];
    
    // Verificar quais campos precisam ser adicionados
    $campos_para_adicionar = [];
    foreach ($campos_necessarios as $campo => $sql) {
        if (!in_array($campo, $campos_existentes)) {
            $campos_para_adicionar[$campo] = $sql;
        }
    }
    
    if (empty($campos_para_adicionar)) {
        echo "✅ Todos os campos necessários já existem na tabela veiculos!\n";
        echo "A tabela está sincronizada com o formulário.\n";
    } else {
        echo "🔧 Campos que precisam ser adicionados:\n";
        foreach ($campos_para_adicionar as $campo => $sql) {
            echo "  - $campo\n";
        }
        echo "\n";
        
        // Executar ALTER TABLE
        $alter_sql = "ALTER TABLE veiculos " . implode(", ", $campos_para_adicionar);
        
        echo "🚀 Executando: $alter_sql\n\n";
        
        $pdo->exec($alter_sql);
        
        echo "✅ Tabela veiculos atualizada com sucesso!\n\n";
        
        // Adicionar índices para melhor performance
        echo "🔍 Adicionando índices para melhor performance...\n";
        
        $indices = [
            'idx_veiculos_placa' => 'CREATE INDEX idx_veiculos_placa ON veiculos(placa)',
            'idx_veiculos_status' => 'CREATE INDEX idx_veiculos_status ON veiculos(status)',
            'idx_veiculos_disponivel' => 'CREATE INDEX idx_veiculos_disponivel ON veiculos(disponivel)',
            'idx_veiculos_cfc' => 'CREATE INDEX idx_veiculos_cfc ON veiculos(cfc_id)',
            'idx_veiculos_categoria' => 'CREATE INDEX idx_veiculos_categoria ON veiculos(categoria_cnh)'
        ];
        
        foreach ($indices as $nome => $sql) {
            try {
                $pdo->exec($sql);
                echo "  ✅ Índice $nome criado\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                    echo "  ℹ️ Índice $nome já existe\n";
                } else {
                    echo "  ❌ Erro ao criar índice $nome: " . $e->getMessage() . "\n";
                }
            }
        }
        
        echo "\n✅ Processo concluído com sucesso!\n";
        echo "A tabela veiculos agora está sincronizada com o formulário.\n";
    }
    
    // Verificar estrutura final
    echo "\n📋 Estrutura final da tabela veiculos:\n";
    $stmt = $pdo->query("DESCRIBE veiculos");
    $campos_finais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($campos_finais as $campo) {
        echo "  - {$campo['Field']} ({$campo['Type']}) - {$campo['Comment']}\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Erro de conexão com o banco de dados: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

echo "\n🎉 Script concluído!\n";
?>
