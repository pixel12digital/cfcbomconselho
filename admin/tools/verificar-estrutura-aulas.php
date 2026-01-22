<?php
/**
 * Script temporário para verificar estrutura da tabela 'aulas'
 * Execute via navegador ou linha de comando
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';

$db = Database::getInstance();

echo "=== Estrutura da Tabela 'aulas' ===\n\n";

try {
    // Buscar estrutura da tabela
    $columns = $db->fetchAll("SHOW COLUMNS FROM aulas");
    
    echo "Colunas encontradas:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-30s %-20s %-10s %-10s\n", "Campo", "Tipo", "Null", "Default");
    echo str_repeat("-", 80) . "\n";
    
    $camposEncontrados = [];
    
    foreach ($columns as $col) {
        $field = $col['Field'];
        $type = $col['Type'];
        $null = $col['Null'];
        $default = $col['Default'] ?? 'NULL';
        
        printf("%-30s %-20s %-10s %-10s\n", $field, $type, $null, $default);
        $camposEncontrados[] = strtolower($field);
    }
    
    echo str_repeat("-", 80) . "\n\n";
    
    // Verificar campos específicos solicitados
    echo "=== Verificação de Campos Específicos ===\n\n";
    
    $camposVerificar = [
        'km_inicial',
        'km_final',
        'inicio_at',
        'fim_at',
        'observacoes'
    ];
    
    foreach ($camposVerificar as $campo) {
        $existe = in_array(strtolower($campo), $camposEncontrados);
        
        if ($existe) {
            // Buscar detalhes do campo
            $coluna = array_filter($columns, function($col) use ($campo) {
                return strtolower($col['Field']) === strtolower($campo);
            });
            $coluna = reset($coluna);
            
            echo "✓ {$campo}: EXISTE (Tipo: {$coluna['Type']}, Null: {$coluna['Null']}, Default: " . ($coluna['Default'] ?? 'NULL') . ")\n";
        } else {
            // Verificar se existe com nome similar
            $similares = array_filter($camposEncontrados, function($c) use ($campo) {
                return stripos($c, $campo) !== false || stripos($campo, $c) !== false;
            });
            
            if (!empty($similares)) {
                echo "✗ {$campo}: NÃO EXISTE (mas encontrado similar: " . implode(', ', $similares) . ")\n";
            } else {
                echo "✗ {$campo}: NÃO EXISTE\n";
            }
        }
    }
    
    echo "\n";
    echo "=== Total de colunas: " . count($columns) . " ===\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
?>
