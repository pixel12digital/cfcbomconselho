<?php
// Script para verificar restrições de chave estrangeira
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = Database::getInstance();

echo "=== VERIFICAÇÃO DE RESTRIÇÕES DE CHAVE ESTRANGEIRA ===\n\n";

// Verificar todas as chaves estrangeiras que referenciam a tabela usuarios
echo "=== CHAVES ESTRANGEIRAS QUE REFERENCIAM USUARIOS ===\n";

try {
    $foreignKeys = $db->fetchAll("
        SELECT 
            TABLE_NAME,
            COLUMN_NAME,
            CONSTRAINT_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE REFERENCED_TABLE_NAME = 'usuarios'
        AND TABLE_SCHEMA = 'u342734079_cfcbomconselho'
    ");
    
    if (count($foreignKeys) > 0) {
        echo "✅ Chaves estrangeiras encontradas:\n";
        foreach ($foreignKeys as $fk) {
            echo "   - Tabela: {$fk['TABLE_NAME']}\n";
            echo "     Coluna: {$fk['COLUMN_NAME']}\n";
            echo "     Restrição: {$fk['CONSTRAINT_NAME']}\n";
            echo "     Referencia: {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n\n";
        }
    } else {
        echo "ℹ️  Nenhuma chave estrangeira encontrada\n\n";
    }
} catch (Exception $e) {
    echo "❌ Erro ao verificar chaves estrangeiras: " . $e->getMessage() . "\n\n";
}

// Verificar registros que referenciam o usuário ID=1
echo "=== REGISTROS QUE REFERENCIAM O USUÁRIO ID=1 ===\n";

$tabelasComReferencia = [
    'cfcs' => 'responsavel_id',
    'instrutores' => 'usuario_id',
    'sessoes' => 'usuario_id',
    'logs' => 'usuario_id'
];

foreach ($tabelasComReferencia as $tabela => $coluna) {
    try {
        $registros = $db->fetchAll("SELECT COUNT(*) as total FROM {$tabela} WHERE {$coluna} = 1");
        $total = $registros[0]['total'];
        
        if ($total > 0) {
            echo "❌ Tabela {$tabela}: {$total} registro(s) referenciando usuário ID=1\n";
            
            // Mostrar detalhes dos registros
            $detalhes = $db->fetchAll("SELECT * FROM {$tabela} WHERE {$coluna} = 1 LIMIT 5");
            foreach ($detalhes as $i => $registro) {
                echo "     Registro " . ($i + 1) . ": ID=" . $registro['id'];
                if (isset($registro['nome'])) echo ", Nome=" . $registro['nome'];
                echo "\n";
            }
            if ($total > 5) echo "     ... e mais " . ($total - 5) . " registro(s)\n";
        } else {
            echo "✅ Tabela {$tabela}: Nenhum registro referenciando usuário ID=1\n";
        }
        echo "\n";
        
    } catch (Exception $e) {
        echo "❌ Erro ao verificar tabela {$tabela}: " . $e->getMessage() . "\n\n";
    }
}

// Verificar restrições de exclusão em cascata
echo "=== VERIFICAÇÃO DE RESTRIÇÕES DE EXCLUSÃO ===\n";

try {
    $restricoes = $db->fetchAll("
        SELECT 
            CONSTRAINT_NAME,
            DELETE_RULE,
            UPDATE_RULE
        FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS 
        WHERE CONSTRAINT_SCHEMA = 'u342734079_cfcbomconselho'
        AND TABLE_NAME IN ('cfcs', 'instrutores', 'sessoes', 'logs')
    ");
    
    if (count($restricoes) > 0) {
        echo "✅ Restrições encontradas:\n";
        foreach ($restricoes as $restricao) {
            echo "   - {$restricao['CONSTRAINT_NAME']}: DELETE={$restricao['DELETE_RULE']}, UPDATE={$restricao['UPDATE_RULE']}\n";
        }
    } else {
        echo "ℹ️  Nenhuma restrição encontrada\n";
    }
} catch (Exception $e) {
    echo "❌ Erro ao verificar restrições: " . $e->getMessage() . "\n";
}

echo "\n=== TESTE DE EXCLUSÃO COM VERIFICAÇÃO DETALHADA ===\n";

try {
    // Tentar excluir e capturar o erro específico
    $sql = "DELETE FROM usuarios WHERE id = 1";
    echo "Executando: $sql\n";
    
    $result = $db->query($sql);
    echo "✅ Usuário excluído com sucesso!\n";
    
} catch (Exception $e) {
    echo "❌ Erro na exclusão: " . $e->getMessage() . "\n";
    
    // Verificar se é erro de chave estrangeira
    if (strpos($e->getMessage(), 'foreign key constraint') !== false || 
        strpos($e->getMessage(), 'Cannot delete') !== false ||
        $e->getCode() == 1451) {
        echo "🔍 Este é um erro de restrição de chave estrangeira!\n";
        echo "   Alguma tabela ainda tem registros que referenciam este usuário.\n";
    }
}

echo "\n=== FIM DA VERIFICAÇÃO ===\n";
?>
