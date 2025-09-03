<?php
// Script para atualizar a tabela veiculos com campos faltantes
require_once 'includes/config.php';
require_once 'includes/database.php';

echo "🔍 Verificando estrutura da tabela veiculos...\n";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar campos existentes
    $stmt = $pdo->query("DESCRIBE veiculos");
    $campos_existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "✅ Campos existentes: " . implode(', ', $campos_existentes) . "\n";
    
    // Campos que devem existir
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
    
    $campos_faltantes = [];
    foreach ($campos_necessarios as $campo => $sql) {
        if (!in_array($campo, $campos_existentes)) {
            $campos_faltantes[$campo] = $sql;
        }
    }
    
    if (empty($campos_faltantes)) {
        echo "✅ Todos os campos necessários já existem na tabela!\n";
    } else {
        echo "⚠️ Campos faltantes encontrados: " . implode(', ', array_keys($campos_faltantes)) . "\n";
        echo "🔧 Adicionando campos faltantes...\n";
        
        foreach ($campos_faltantes as $campo => $sql) {
            try {
                $pdo->exec("ALTER TABLE veiculos $sql");
                echo "✅ Campo '$campo' adicionado com sucesso!\n";
            } catch (PDOException $e) {
                echo "❌ Erro ao adicionar campo '$campo': " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Verificar e adicionar índices
    echo "\n🔍 Verificando índices...\n";
    
    $stmt = $pdo->query("SHOW INDEX FROM veiculos");
    $indices_existentes = $stmt->fetchAll(PDO::FETCH_COLUMN, 2); // Nome do índice
    
    $indices_necessarios = [
        'idx_veiculos_placa' => 'CREATE INDEX idx_veiculos_placa ON veiculos(placa)',
        'idx_veiculos_status' => 'CREATE INDEX idx_veiculos_status ON veiculos(status)',
        'idx_veiculos_disponivel' => 'CREATE INDEX idx_veiculos_disponivel ON veiculos(disponivel)',
        'idx_veiculos_cfc' => 'CREATE INDEX idx_veiculos_cfc ON veiculos(cfc_id)',
        'idx_veiculos_categoria' => 'CREATE INDEX idx_veiculos_categoria ON veiculos(categoria_cnh)'
    ];
    
    $indices_faltantes = [];
    foreach ($indices_necessarios as $indice => $sql) {
        if (!in_array($indice, $indices_existentes)) {
            $indices_faltantes[$indice] = $sql;
        }
    }
    
    if (empty($indices_faltantes)) {
        echo "✅ Todos os índices necessários já existem!\n";
    } else {
        echo "⚠️ Índices faltantes encontrados: " . implode(', ', array_keys($indices_faltantes)) . "\n";
        echo "🔧 Adicionando índices...\n";
        
        foreach ($indices_faltantes as $indice => $sql) {
            try {
                $pdo->exec($sql);
                echo "✅ Índice '$indice' adicionado com sucesso!\n";
            } catch (PDOException $e) {
                echo "❌ Erro ao adicionar índice '$indice': " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n🎉 Atualização da tabela veiculos concluída!\n";
    
} catch (PDOException $e) {
    echo "❌ Erro de conexão com o banco de dados: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>
