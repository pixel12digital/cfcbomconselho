<?php
/**
 * Script de Verificação Pós-Migração CFC 36
 * 
 * Verifica se tudo está funcionando corretamente após a migração
 * 
 * USO: Acesse via navegador: admin/tools/verificar-pos-migracao-cfc-36.php
 */

header('Content-Type: text/html; charset=utf-8');

$rootPath = dirname(__DIR__, 2);
require_once $rootPath . '/includes/config.php';
require_once $rootPath . '/includes/database.php';
require_once __DIR__ . '/../includes/guards_exames.php';
require_once __DIR__ . '/../includes/FinanceiroAlunoHelper.php';

$cfcCanonico = 36;

echo "<h1>Verificação Pós-Migração CFC 36</h1>";
echo "<hr>";

try {
    $db = Database::getInstance();
    
    // =====================================================
    // 1. VERIFICAÇÃO DE INTEGRIDADE DO BANCO
    // =====================================================
    echo "<h2>1. Verificação de Integridade do Banco</h2>";
    
    $tabelas = ['alunos', 'turmas_teoricas', 'salas', 'instrutores', 'aulas', 'veiculos'];
    $todosOK = true;
    
    foreach ($tabelas as $tabela) {
        $countLegado = $db->count($tabela, 'cfc_id = ?', [1]);
        $countCanonico = $db->count($tabela, 'cfc_id = ?', [$cfcCanonico]);
        
        if ($countLegado > 0) {
            $todosOK = false;
            echo "<p style='color: red;'>❌ <strong>{$tabela}:</strong> Ainda existem {$countLegado} registro(s) com cfc_id = 1</p>";
        } else {
            echo "<p style='color: green;'>✅ <strong>{$tabela}:</strong> {$countCanonico} registro(s) com cfc_id = {$cfcCanonico}</p>";
        }
    }
    
    if ($todosOK) {
        echo "<p style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
        echo "<strong>✅ Integridade do banco: OK</strong> - Nenhum registro com cfc_id = 1 encontrado.";
        echo "</p>";
    }
    
    // =====================================================
    // 2. VERIFICAÇÃO DE ALUNOS E TURMAS
    // =====================================================
    echo "<h2>2. Verificação de Alunos e Turmas</h2>";
    
    // Verificar se há alunos do CFC canônico
    $alunosCfc36 = $db->fetchAll("
        SELECT id, nome, status, cfc_id
        FROM alunos
        WHERE cfc_id = ?
        ORDER BY id
        LIMIT 10
    ", [$cfcCanonico]);
    
    echo "<h3>Alunos do CFC Canônico (ID: {$cfcCanonico})</h3>";
    if (empty($alunosCfc36)) {
        echo "<p style='color: orange;'>⚠️ Nenhum aluno encontrado com cfc_id = {$cfcCanonico}</p>";
    } else {
        echo "<p>✅ Encontrados " . count($alunosCfc36) . " aluno(s) (mostrando até 10):</p>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Nome</th><th>Status</th><th>CFC ID</th></tr>";
        foreach ($alunosCfc36 as $aluno) {
            echo "<tr>";
            echo "<td>{$aluno['id']}</td>";
            echo "<td>{$aluno['nome']}</td>";
            echo "<td>{$aluno['status']}</td>";
            echo "<td>{$aluno['cfc_id']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Verificar turmas do CFC canônico
    $turmasCfc36 = $db->fetchAll("
        SELECT id, nome, cfc_id, curso_tipo, status
        FROM turmas_teoricas
        WHERE cfc_id = ?
        ORDER BY id
    ", [$cfcCanonico]);
    
    echo "<h3>Turmas do CFC Canônico (ID: {$cfcCanonico})</h3>";
    if (empty($turmasCfc36)) {
        echo "<p style='color: orange;'>⚠️ Nenhuma turma encontrada com cfc_id = {$cfcCanonico}</p>";
    } else {
        echo "<p>✅ Encontradas " . count($turmasCfc36) . " turma(s):</p>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Nome</th><th>CFC ID</th><th>Curso Tipo</th><th>Status</th></tr>";
        foreach ($turmasCfc36 as $turma) {
            echo "<tr>";
            echo "<td>{$turma['id']}</td>";
            echo "<td>{$turma['nome']}</td>";
            echo "<td>{$turma['cfc_id']}</td>";
            echo "<td>{$turma['curso_tipo']}</td>";
            echo "<td>{$turma['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // =====================================================
    // 3. VERIFICAÇÃO ESPECÍFICA DO ALUNO 167
    // =====================================================
    echo "<h2>3. Verificação do Aluno 167 (Charles)</h2>";
    
    $aluno167 = $db->fetch("
        SELECT id, nome, cfc_id, status
        FROM alunos
        WHERE id = 167
    ");
    
    if (!$aluno167) {
        echo "<p style='color: red;'>❌ Aluno 167 não encontrado!</p>";
    } else {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        echo "<tr><td>ID</td><td>{$aluno167['id']}</td></tr>";
        echo "<tr><td>Nome</td><td>{$aluno167['nome']}</td></tr>";
        echo "<tr><td>CFC ID</td><td><strong>{$aluno167['cfc_id']}</strong></td></tr>";
        echo "<tr><td>Status</td><td>{$aluno167['status']}</td></tr>";
        echo "</table>";
        
        if ((int)$aluno167['cfc_id'] === $cfcCanonico) {
            echo "<p style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
            echo "✅ Aluno 167 está com o CFC canônico correto ({$cfcCanonico})";
            echo "</p>";
        } else {
            echo "<p style='background: #fff3cd; padding: 10px; border: 1px solid #ffc107; border-radius: 5px;'>";
            echo "⚠️ Aluno 167 está com CFC ID {$aluno167['cfc_id']} (esperado: {$cfcCanonico})";
            echo "</p>";
        }
        
        // Verificar exames do aluno 167
        echo "<h3>Exames do Aluno 167</h3>";
        $exames167 = $db->fetchAll("
            SELECT tipo, status, resultado, data_resultado
            FROM exames
            WHERE aluno_id = 167
            ORDER BY tipo
        ");
        
        if (empty($exames167)) {
            echo "<p>ℹ️ Nenhum exame encontrado para o aluno 167</p>";
        } else {
            echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
            echo "<tr><th>Tipo</th><th>Status</th><th>Resultado</th><th>Data Resultado</th></tr>";
            foreach ($exames167 as $exame) {
                echo "<tr>";
                echo "<td>{$exame['tipo']}</td>";
                echo "<td>{$exame['status']}</td>";
                echo "<td>{$exame['resultado']}</td>";
                echo "<td>{$exame['data_resultado']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Verificar se exames estão OK para teóricas
            $examesOK = GuardsExames::alunoComExamesOkParaTeoricas(167);
            if ($examesOK) {
                echo "<p style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
                echo "✅ Exames OK para aulas teóricas";
                echo "</p>";
            } else {
                echo "<p style='background: #fff3cd; padding: 10px; border: 1px solid #ffc107; border-radius: 5px;'>";
                echo "⚠️ Exames não estão OK para aulas teóricas";
                echo "</p>";
            }
        }
        
        // Verificar financeiro do aluno 167
        echo "<h3>Financeiro do Aluno 167</h3>";
        try {
            $financeiro = FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno(167);
            echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
            echo "<tr><th>Campo</th><th>Valor</th></tr>";
            echo "<tr><td>Liberado</td><td>" . ($financeiro['liberado'] ? 'Sim' : 'Não') . "</td></tr>";
            echo "<tr><td>Status</td><td>{$financeiro['status']}</td></tr>";
            echo "<tr><td>Motivo</td><td>{$financeiro['motivo']}</td></tr>";
            echo "</table>";
            
            if ($financeiro['liberado']) {
                echo "<p style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
                echo "✅ Financeiro OK - Aluno pode avançar";
                echo "</p>";
            } else {
                echo "<p style='background: #fff3cd; padding: 10px; border: 1px solid #ffc107; border-radius: 5px;'>";
                echo "⚠️ Financeiro bloqueado: {$financeiro['motivo']}";
                echo "</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: orange;'>ℹ️ Não foi possível verificar financeiro: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    // =====================================================
    // 4. VERIFICAÇÃO DE COMPATIBILIDADE CFC TURMA/ALUNO
    // =====================================================
    echo "<h2>4. Verificação de Compatibilidade CFC Turma/Aluno</h2>";
    
    // Verificar se há alunos do CFC 36 que podem ser candidatos para turmas do CFC 36
    $turma16 = $db->fetch("
        SELECT id, nome, cfc_id
        FROM turmas_teoricas
        WHERE id = 16
    ");
    
    if ($turma16) {
        echo "<h3>Turma 16</h3>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        echo "<tr><td>ID</td><td>{$turma16['id']}</td></tr>";
        echo "<tr><td>Nome</td><td>{$turma16['nome']}</td></tr>";
        echo "<tr><td>CFC ID</td><td><strong>{$turma16['cfc_id']}</strong></td></tr>";
        echo "</table>";
        
        if ((int)$turma16['cfc_id'] === $cfcCanonico) {
            echo "<p style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
            echo "✅ Turma 16 está com o CFC canônico correto ({$cfcCanonico})";
            echo "</p>";
            
            // Verificar quantos alunos do CFC 36 estão ativos
            $alunosAtivosCfc36 = $db->count('alunos', 'cfc_id = ? AND status = ?', [$cfcCanonico, 'ativo']);
            echo "<p>📊 Alunos ativos do CFC {$cfcCanonico}: <strong>{$alunosAtivosCfc36}</strong></p>";
            
            // Verificar se aluno 167 pode ser candidato
            if ($aluno167 && (int)$aluno167['cfc_id'] === (int)$turma16['cfc_id']) {
                echo "<p style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
                echo "✅ Aluno 167 e Turma 16 têm o mesmo CFC - Compatível para matrícula";
                echo "</p>";
            } else {
                echo "<p style='background: #fff3cd; padding: 10px; border: 1px solid #ffc107; border-radius: 5px;'>";
                echo "⚠️ Aluno 167 e Turma 16 têm CFCs diferentes - Não compatível";
                echo "</p>";
            }
        } else {
            echo "<p style='background: #fff3cd; padding: 10px; border: 1px solid #ffc107; border-radius: 5px;'>";
            echo "⚠️ Turma 16 está com CFC ID {$turma16['cfc_id']} (esperado: {$cfcCanonico})";
            echo "</p>";
        }
    } else {
        echo "<p style='color: orange;'>ℹ️ Turma 16 não encontrada</p>";
    }
    
    // =====================================================
    // 5. RESUMO FINAL
    // =====================================================
    echo "<h2>5. Resumo Final</h2>";
    
    $resumo = [
        'Integridade do banco' => $todosOK ? '✅ OK' : '❌ Erro',
        'Alunos do CFC canônico' => count($alunosCfc36) > 0 ? '✅ ' . count($alunosCfc36) . ' encontrado(s)' : '⚠️ Nenhum',
        'Turmas do CFC canônico' => count($turmasCfc36) > 0 ? '✅ ' . count($turmasCfc36) . ' encontrada(s)' : '⚠️ Nenhuma',
        'Aluno 167 CFC correto' => ($aluno167 && (int)$aluno167['cfc_id'] === $cfcCanonico) ? '✅ OK' : '❌ Erro',
        'Turma 16 CFC correto' => ($turma16 && (int)$turma16['cfc_id'] === $cfcCanonico) ? '✅ OK' : '❌ Erro'
    ];
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Verificação</th><th>Status</th></tr>";
    foreach ($resumo as $verificacao => $status) {
        $style = strpos($status, '✅') !== false ? "background: #d4edda;" : (strpos($status, '⚠️') !== false ? "background: #fff3cd;" : "background: #f8d7da;");
        echo "<tr style='{$style}'>";
        echo "<td>{$verificacao}</td>";
        echo "<td>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    $tudoOK = array_reduce($resumo, function($carry, $item) {
        return $carry && (strpos($item, '✅') !== false);
    }, true);
    
    if ($tudoOK) {
        echo "<p style='background: #d4edda; padding: 20px; border: 2px solid #c3e6cb; border-radius: 5px; margin-top: 20px;'>";
        echo "<strong>✅ Todas as verificações passaram!</strong><br>";
        echo "A migração foi bem-sucedida e o sistema está funcionando corretamente.";
        echo "</p>";
    } else {
        echo "<p style='background: #fff3cd; padding: 20px; border: 2px solid #ffc107; border-radius: 5px; margin-top: 20px;'>";
        echo "<strong>⚠️ Algumas verificações falharam.</strong><br>";
        echo "Revise os itens acima e corrija se necessário.";
        echo "</p>";
    }
    
    echo "<hr>";
    echo "<p><strong>Próximos passos:</strong></p>";
    echo "<ol>";
    echo "<li>Teste o histórico do aluno 167: <a href='../index.php?page=historico-aluno&id=167' target='_blank'>Histórico Aluno 167</a></li>";
    echo "<li>Teste o modal de turmas teóricas: <a href='../index.php?page=turmas-teoricas&acao=detalhes&turma_id=16' target='_blank'>Turma 16</a></li>";
    echo "<li>Execute o checklist completo: <code>docs/CHECKLIST_TESTES_CFC_36.md</code></li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>ERRO:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

