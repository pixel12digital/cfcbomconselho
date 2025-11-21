<?php
/**
 * Script de Diagnóstico - CFC Canônico da Turma 16
 * 
 * Este script confirma qual é o CFC canônico (oficial) do CFC Bom Conselho
 * baseado na turma 16.
 * 
 * USO: Acesse via navegador: admin/tools/diagnostico-cfc-turma-16.php
 */

header('Content-Type: text/html; charset=utf-8');

$rootPath = dirname(__DIR__, 2);
require_once $rootPath . '/includes/config.php';
require_once $rootPath . '/includes/database.php';

try {
    $db = Database::getInstance();
    
    echo "<h1>Diagnóstico - CFC Canônico da Turma 16</h1>";
    echo "<hr>";
    
    // Passo 1: Buscar turma 16
    echo "<h2>1. Dados da Turma 16</h2>";
    $turma = $db->fetch("
        SELECT id, nome, cfc_id, curso_tipo, data_inicio, data_fim
        FROM turmas_teoricas 
        WHERE id = 16
    ");
    
    if (!$turma) {
        echo "<p style='color: red;'><strong>ERRO:</strong> Turma 16 não encontrada!</p>";
        exit;
    }
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Valor</th></tr>";
    echo "<tr><td>ID</td><td>{$turma['id']}</td></tr>";
    echo "<tr><td>Nome</td><td>{$turma['nome']}</td></tr>";
    echo "<tr><td><strong>CFC ID</strong></td><td><strong style='color: blue;'>{$turma['cfc_id']}</strong></td></tr>";
    echo "<tr><td>Curso Tipo</td><td>{$turma['curso_tipo']}</td></tr>";
    echo "<tr><td>Data Início</td><td>{$turma['data_inicio']}</td></tr>";
    echo "<tr><td>Data Fim</td><td>{$turma['data_fim']}</td></tr>";
    echo "</table>";
    
    $cfcCanonico = (int)$turma['cfc_id'];
    
    // CFC Canônico do CFC Bom Conselho neste ambiente
    $cfcCanonicoBomConselho = 36;
    
    // Verificar se a turma está com CFC correto
    $turmaCfcCorreto = ((int)$turma['cfc_id'] === $cfcCanonicoBomConselho);
    
    if ($turmaCfcCorreto) {
        echo "<p style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
        echo "<strong>✅ CFC CORRETO: {$turma['cfc_id']}</strong><br>";
        echo "A turma está com o CFC canônico do CFC Bom Conselho (ID: {$cfcCanonicoBomConselho}).";
        echo "</p>";
    } else {
        echo "<p style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; border-radius: 5px;'>";
        echo "<strong>⚠️ ATENÇÃO: CFC DIVERGENTE</strong><br>";
        echo "A turma está com CFC ID <strong>{$turma['cfc_id']}</strong>, mas o CFC canônico do CFC Bom Conselho é <strong>{$cfcCanonicoBomConselho}</strong>.<br>";
        echo "Esta turma precisa ser migrada para o CFC {$cfcCanonicoBomConselho}.";
        echo "</p>";
    }
    
    echo "<p style='background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb; border-radius: 5px;'>";
    echo "<strong>ℹ️ CFC CANÔNICO DO CFC BOM CONSELHO:</strong> <span style='color: blue; font-size: 1.2em; font-weight: bold;'>{$cfcCanonicoBomConselho}</span><br>";
    echo "O CFC ID 1 é legado e deve ser migrado para 36.";
    echo "</p>";
    
    // Passo 2: Buscar dados do CFC canônico (36)
    echo "<h2>2. Dados do CFC Canônico (ID: {$cfcCanonicoBomConselho})</h2>";
    $cfc = $db->fetch("
        SELECT id, nome, cnpj, telefone, email, ativo
        FROM cfcs 
        WHERE id = ?
    ", [$cfcCanonicoBomConselho]);
    
    if (!$cfc) {
        echo "<p style='color: red;'><strong>ERRO:</strong> CFC ID {$cfcCanonicoBomConselho} não encontrado!</p>";
        echo "<p style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; border-radius: 5px;'>";
        echo "<strong>⚠️ ATENÇÃO:</strong> O CFC canônico (ID: {$cfcCanonicoBomConselho}) não existe na tabela <code>cfcs</code>. ";
        echo "É necessário criar ou verificar se o ID está correto.";
        echo "</p>";
    } else {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        echo "<tr><td>ID</td><td>{$cfc['id']}</td></tr>";
        echo "<tr><td>Nome</td><td><strong>{$cfc['nome']}</strong></td></tr>";
        echo "<tr><td>CNPJ</td><td>{$cfc['cnpj']}</td></tr>";
        echo "<tr><td>Telefone</td><td>{$cfc['telefone']}</td></tr>";
        echo "<tr><td>Email</td><td>{$cfc['email']}</td></tr>";
        echo "<tr><td>Ativo</td><td>" . ($cfc['ativo'] ? 'Sim' : 'Não') . "</td></tr>";
        echo "</table>";
    }
    
    // Passo 3: Listar todos os CFCs para referência
    echo "<h2>3. Todos os CFCs Cadastrados</h2>";
    $cfcs = $db->fetchAll("
        SELECT id, nome, cnpj, ativo
        FROM cfcs 
        ORDER BY id
    ");
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Nome</th><th>CNPJ</th><th>Ativo</th><th>Status</th></tr>";
    $cfcCanonicoBomConselho = 36;
    foreach ($cfcs as $c) {
        $isCanonico = ((int)$c['id'] === $cfcCanonicoBomConselho);
        $isLegado = ((int)$c['id'] === 1);
        $style = $isCanonico ? "background: #d4edda; font-weight: bold;" : ($isLegado ? "background: #f8d7da; color: #721c24;" : "");
        $status = $isCanonico ? "✅ CANÔNICO" : ($isLegado ? "⚠️ LEGADO (migrar para 36)" : "");
        echo "<tr style='{$style}'>";
        echo "<td>{$c['id']}</td>";
        echo "<td>{$c['nome']}</td>";
        echo "<td>{$c['cnpj']}</td>";
        echo "<td>" . ($c['ativo'] ? 'Sim' : 'Não') . "</td>";
        echo "<td><strong>{$status}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar outras turmas com CFC divergente
    echo "<h2>4. Outras Turmas com CFC Divergente</h2>";
    $turmasDivergentes = $db->fetchAll("
        SELECT id, nome, cfc_id
        FROM turmas_teoricas 
        WHERE cfc_id != {$cfcCanonicoBomConselho}
        ORDER BY cfc_id, id
    ");
    
    if (!empty($turmasDivergentes)) {
        echo "<p style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; border-radius: 5px;'>";
        echo "<strong>⚠️ ATENÇÃO:</strong> Encontradas <strong>" . count($turmasDivergentes) . "</strong> turma(s) com CFC diferente do canônico ({$cfcCanonicoBomConselho}).";
        echo "</p>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Nome</th><th>CFC Atual</th><th>CFC Esperado</th></tr>";
        foreach ($turmasDivergentes as $t) {
            echo "<tr>";
            echo "<td>{$t['id']}</td>";
            echo "<td>{$t['nome']}</td>";
            echo "<td>{$t['cfc_id']}</td>";
            echo "<td><strong style='color: blue;'>{$cfcCanonicoBomConselho}</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
        echo "<strong>✅ ÓTIMO!</strong> Todas as turmas estão com o CFC canônico ({$cfcCanonicoBomConselho}).";
        echo "</p>";
    }
    
    echo "<hr>";
    echo "<p><strong>Próximos passos:</strong></p>";
    echo "<ol>";
    echo "<li>O CFC canônico do CFC Bom Conselho é <strong>{$cfcCanonicoBomConselho}</strong> (não mais 1).</li>";
    echo "<li>Execute o script de diagnóstico completo: <code>diagnostico-cfc-alunos.php?cfc_canonico={$cfcCanonicoBomConselho}</code></li>";
    echo "<li>Revise o SQL de migração em <code>docs/MIGRACAO_CFC_1_PARA_36.md</code>.</li>";
    echo "<li>Execute a migração manualmente após revisão.</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>ERRO:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

