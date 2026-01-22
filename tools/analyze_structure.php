<?php
/**
 * Script para analisar estrutura condicional do arquivo
 */

$file = __DIR__ . '/../app/Views/financeiro/index.php';
$lines = file($file);

$stack = [];
$lineNum = 0;

echo "=== ANÁLISE DE ESTRUTURA CONDICIONAL ===\n\n";

foreach ($lines as $line) {
    $lineNum++;
    $trimmed = trim($line);
    
    // Verificar if
    if (preg_match('/^\s*<\?php\s+if\s+/', $trimmed)) {
        $stack[] = ['type' => 'if', 'line' => $lineNum, 'content' => substr($trimmed, 0, 80)];
        echo "Linha $lineNum: IF aberto - " . substr($trimmed, 0, 80) . "\n";
    }
    // Verificar elseif
    elseif (preg_match('/^\s*<\?php\s+elseif\s+/', $trimmed)) {
        echo "Linha $lineNum: ELSEIF - " . substr($trimmed, 0, 80) . "\n";
        if (empty($stack)) {
            echo "  ⚠️ ERRO: elseif sem if correspondente!\n";
        }
    }
    // Verificar else
    elseif (preg_match('/^\s*<\?php\s+else\s*:/', $trimmed)) {
        echo "Linha $lineNum: ELSE - " . substr($trimmed, 0, 80) . "\n";
        if (empty($stack)) {
            echo "  ⚠️ ERRO: else sem if correspondente!\n";
        }
    }
    // Verificar endif
    elseif (preg_match('/^\s*<\?php\s+endif\s*;/', $trimmed)) {
        if (empty($stack)) {
            echo "Linha $lineNum: ⚠️ ERRO: endif sem if correspondente!\n";
        } else {
            $opened = array_pop($stack);
            echo "Linha $lineNum: ENDIF fecha {$opened['type']} da linha {$opened['line']}\n";
        }
    }
}

echo "\n=== RESULTADO FINAL ===\n";
if (!empty($stack)) {
    echo "⚠️ ERRO: Blocos não fechados:\n";
    foreach ($stack as $item) {
        echo "  - {$item['type']} na linha {$item['line']}: {$item['content']}\n";
    }
} else {
    echo "✓ Todos os blocos estão balanceados!\n";
}
