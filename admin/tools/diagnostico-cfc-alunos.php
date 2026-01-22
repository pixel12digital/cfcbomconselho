<?php
/**
 * Script de Diagnóstico - CFC dos Alunos
 * 
 * Este script lista todos os alunos e identifica aqueles cujo cfc_id
 * seja diferente do CFC canônico.
 * 
 * USO: Acesse via navegador: admin/tools/diagnostico-cfc-alunos.php?cfc_canonico=36
 * 
 * Parâmetro opcional: cfc_canonico (padrão: 36 - CFC canônico do CFC Bom Conselho)
 */

header('Content-Type: text/html; charset=utf-8');

$rootPath = dirname(__DIR__, 2);
require_once $rootPath . '/includes/config.php';
require_once $rootPath . '/includes/database.php';

// CFC Canônico do CFC Bom Conselho neste ambiente
$cfcCanonico = isset($_GET['cfc_canonico']) ? (int)$_GET['cfc_canonico'] : 36;

try {
    $db = Database::getInstance();
    
    echo "<h1>Diagnóstico - CFC dos Alunos</h1>";
    echo "<hr>";
    
    echo "<p><strong>CFC Canônico do CFC Bom Conselho:</strong> <span style='color: blue; font-size: 1.2em; font-weight: bold;'>{$cfcCanonico}</span></p>";
    echo "<p style='background: #d1ecf1; padding: 10px; border: 1px solid #bee5eb; border-radius: 5px;'>";
    echo "<strong>ℹ️ IMPORTANTE:</strong> O CFC ID 1 é legado e deve ser migrado para 36. Neste ambiente, o CFC Bom Conselho é representado pelo CFC ID 36.";
    echo "</p>";
    echo "<p><small>Para alterar (não recomendado), use: ?cfc_canonico=X</small></p>";
    echo "<hr>";
    
    // Buscar dados do CFC canônico
    $cfcCanonicoData = $db->fetch("
        SELECT id, nome, cnpj
        FROM cfcs 
        WHERE id = ?
    ", [$cfcCanonico]);
    
    if ($cfcCanonicoData) {
        echo "<p><strong>CFC Canônico:</strong> {$cfcCanonicoData['nome']} (ID: {$cfcCanonicoData['id']}, CNPJ: {$cfcCanonicoData['cnpj']})</p>";
    } else {
        echo "<p style='color: red;'><strong>ATENÇÃO:</strong> CFC canônico ID {$cfcCanonico} não encontrado!</p>";
    }
    
    // Buscar todos os alunos
    echo "<h2>1. Todos os Alunos (ordenados por CFC)</h2>";
    $alunos = $db->fetchAll("
        SELECT 
            a.id, 
            a.nome, 
            a.status, 
            a.cfc_id,
            c.nome as cfc_nome,
            CASE 
                WHEN a.cfc_id = ? THEN '✅ CORRETO'
                ELSE '⚠️ DIFERENTE'
            END as status_cfc
        FROM alunos a
        LEFT JOIN cfcs c ON a.cfc_id = c.id
        ORDER BY a.cfc_id, a.nome
    ", [$cfcCanonico]);
    
    echo "<p><strong>Total de alunos:</strong> " . count($alunos) . "</p>";
    
    // Agrupar por CFC
    $alunosPorCfc = [];
    $alunosDiferentes = [];
    
    foreach ($alunos as $aluno) {
        $cfcId = (int)$aluno['cfc_id'];
        if (!isset($alunosPorCfc[$cfcId])) {
            $alunosPorCfc[$cfcId] = [
                'cfc_id' => $cfcId,
                'cfc_nome' => $aluno['cfc_nome'] ?? 'CFC não encontrado',
                'total' => 0,
                'ativos' => 0,
                'inativos' => 0,
                'alunos' => []
            ];
        }
        
        $alunosPorCfc[$cfcId]['total']++;
        if ($aluno['status'] === 'ativo') {
            $alunosPorCfc[$cfcId]['ativos']++;
        } else {
            $alunosPorCfc[$cfcId]['inativos']++;
        }
        $alunosPorCfc[$cfcId]['alunos'][] = $aluno;
        
        if ($cfcId !== $cfcCanonico) {
            $alunosDiferentes[] = $aluno;
        }
    }
    
    // Tabela resumida por CFC
    echo "<h3>Resumo por CFC</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>CFC ID</th><th>CFC Nome</th><th>Total Alunos</th><th>Ativos</th><th>Inativos</th><th>Status</th>";
    echo "</tr>";
    
    foreach ($alunosPorCfc as $cfcId => $dados) {
        $isCanonico = ($cfcId === $cfcCanonico);
        $style = $isCanonico ? "background: #d4edda;" : "background: #fff3cd;";
        $status = $isCanonico ? "✅ CANÔNICO" : "⚠️ DIFERENTE";
        
        echo "<tr style='{$style}'>";
        echo "<td><strong>{$cfcId}</strong></td>";
        echo "<td>{$dados['cfc_nome']}</td>";
        echo "<td>{$dados['total']}</td>";
        echo "<td>{$dados['ativos']}</td>";
        echo "<td>{$dados['inativos']}</td>";
        echo "<td><strong>{$status}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Lista detalhada de alunos com CFC diferente
    if (!empty($alunosDiferentes)) {
        echo "<h2>2. Alunos com CFC Diferente do Canônico</h2>";
        echo "<p style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; border-radius: 5px;'>";
        echo "<strong>⚠️ ATENÇÃO:</strong> Encontrados <strong>" . count($alunosDiferentes) . "</strong> aluno(s) com CFC diferente do canônico.";
        echo "</p>";
        
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Nome</th><th>Status</th><th>CFC Atual (ID)</th><th>CFC Atual (Nome)</th><th>CFC Esperado</th>";
        echo "</tr>";
        
        foreach ($alunosDiferentes as $aluno) {
            echo "<tr>";
            echo "<td>{$aluno['id']}</td>";
            echo "<td>{$aluno['nome']}</td>";
            echo "<td>{$aluno['status']}</td>";
            echo "<td>{$aluno['cfc_id']}</td>";
            echo "<td>{$aluno['cfc_nome']}</td>";
            echo "<td><strong style='color: blue;'>{$cfcCanonico}</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Gerar SQL de migração sugerido
        echo "<h2>3. SQL de Migração Sugerido</h2>";
        echo "<p style='background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb; border-radius: 5px;'>";
        echo "<strong>⚠️ IMPORTANTE:</strong> Revise este SQL antes de executar!";
        echo "</p>";
        
        echo "<pre style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; border-radius: 5px; overflow-x: auto;'>";
        echo "-- SQL de migração para atualizar alunos com CFC diferente do canônico\n";
        echo "-- CFC Canônico: {$cfcCanonico}\n";
        echo "-- Data: " . date('Y-m-d H:i:s') . "\n\n";
        
        // Agrupar por CFC atual para gerar UPDATEs mais eficientes
        $cfcsParaMigrar = [];
        foreach ($alunosDiferentes as $aluno) {
            $cfcAtual = (int)$aluno['cfc_id'];
            if (!isset($cfcsParaMigrar[$cfcAtual])) {
                $cfcsParaMigrar[$cfcAtual] = [];
            }
            $cfcsParaMigrar[$cfcAtual][] = (int)$aluno['id'];
        }
        
        foreach ($cfcsParaMigrar as $cfcAtual => $ids) {
            $idsStr = implode(', ', $ids);
            echo "-- Migrar alunos do CFC {$cfcAtual} para CFC {$cfcCanonico}\n";
            echo "UPDATE alunos\n";
            echo "SET cfc_id = {$cfcCanonico}\n";
            echo "WHERE id IN ({$idsStr});\n\n";
        }
        
        echo "-- Verificação após migração:\n";
        echo "SELECT COUNT(*) as total_diferentes\n";
        echo "FROM alunos\n";
        echo "WHERE cfc_id != {$cfcCanonico} AND status = 'ativo';\n";
        echo "</pre>";
        
    } else {
        echo "<h2>2. Alunos com CFC Diferente do Canônico</h2>";
        echo "<p style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
        echo "<strong>✅ ÓTIMO!</strong> Todos os alunos estão com o CFC canônico ({$cfcCanonico}).";
        echo "</p>";
    }
    
    // Lista completa de alunos (opcional, pode ser muito grande)
    if (isset($_GET['mostrar_todos']) && $_GET['mostrar_todos'] === '1') {
        echo "<h2>4. Lista Completa de Alunos</h2>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; font-size: 0.9em;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Nome</th><th>Status</th><th>CFC ID</th><th>CFC Nome</th><th>Status CFC</th>";
        echo "</tr>";
        
        foreach ($alunos as $aluno) {
            $isDiferente = ((int)$aluno['cfc_id'] !== $cfcCanonico);
            $style = $isDiferente ? "background: #fff3cd;" : "";
            
            echo "<tr style='{$style}'>";
            echo "<td>{$aluno['id']}</td>";
            echo "<td>{$aluno['nome']}</td>";
            echo "<td>{$aluno['status']}</td>";
            echo "<td>{$aluno['cfc_id']}</td>";
            echo "<td>{$aluno['cfc_nome']}</td>";
            echo "<td>{$aluno['status_cfc']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p><a href='?cfc_canonico={$cfcCanonico}&mostrar_todos=1'>Mostrar lista completa de alunos</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>ERRO:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

