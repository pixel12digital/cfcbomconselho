<?php
/**
 * Script para verificar balanceamento de blocos if/elseif/else/endif
 */

$file = __DIR__ . '/../app/Views/financeiro/index.php';
$content = file_get_contents($file);
$lines = explode("\n", $content);

$stack = [];
$lineNum = 0;

foreach ($lines as $line) {
    $lineNum++;
    
    // Verificar if
    if (preg_match('/^\s*<\?php\s+if\s+/', $line)) {
        $stack[] = ['type' => 'if', 'line' => $lineNum, 'content' => trim($line)];
    }
    // Verificar elseif
    elseif (preg_match('/^\s*<\?php\s+elseif\s+/', $line)) {
        if (empty($stack)) {
            echo "ERRO: elseif na linha $lineNum sem if correspondente\n";
        }
    }
    // Verificar else
    elseif (preg_match('/^\s*<\?php\s+else\s*:/', $line)) {
        if (empty($stack)) {
            echo "ERRO: else na linha $lineNum sem if correspondente\n";
        }
    }
    // Verificar endif
    elseif (preg_match('/^\s*<\?php\s+endif\s*;/', $line)) {
        if (empty($stack)) {
            echo "ERRO: endif na linha $lineNum sem if correspondente\n";
        } else {
            $opened = array_pop($stack);
            echo "OK: endif linha $lineNum fecha {$opened['type']} linha {$opened['line']}\n";
        }
    }
}

if (!empty($stack)) {
    echo "\nERRO: Blocos não fechados:\n";
    foreach ($stack as $item) {
        echo "  - {$item['type']} na linha {$item['line']}: {$item['content']}\n";
    }
} else {
    echo "\n✓ Todos os blocos estão balanceados!\n";
}
