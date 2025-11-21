<?php
/**
 * Script de Execução da Migração CFC 1 → 36
 * 
 * ⚠️ ATENÇÃO: Este script executa UPDATEs no banco de dados!
 * 
 * Execute apenas após:
 * 1. Backup completo do banco de dados
 * 2. Revisão das queries em docs/MIGRACAO_CFC_1_PARA_36.md
 * 3. Confirmação de que deseja prosseguir
 * 
 * USO: Acesse via navegador: admin/tools/executar-migracao-cfc-36.php?confirmar=1
 */

header('Content-Type: text/html; charset=utf-8');

$rootPath = dirname(__DIR__, 2);
require_once $rootPath . '/includes/config.php';
require_once $rootPath . '/includes/database.php';

$confirmar = isset($_GET['confirmar']) && $_GET['confirmar'] === '1';
$cfcCanonico = 36;

echo "<h1>Migração CFC 1 → 36</h1>";
echo "<hr>";

if (!$confirmar) {
    echo "<div style='background: #fff3cd; padding: 20px; border: 2px solid #ffc107; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>⚠️ ATENÇÃO</h2>";
    echo "<p><strong>Este script executará UPDATEs no banco de dados!</strong></p>";
    echo "<p>Antes de prosseguir, certifique-se de:</p>";
    echo "<ol>";
    echo "<li>✅ Backup completo do banco de dados realizado</li>";
    echo "<li>✅ Queries revisadas em <code>docs/MIGRACAO_CFC_1_PARA_36.md</code></li>";
    echo "<li>✅ CFC ID 36 existe na tabela <code>cfcs</code></li>";
    echo "</ol>";
    echo "<p><a href='?confirmar=1' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>Confirmar e Executar Migração</a></p>";
    echo "</div>";
    exit;
}

try {
    $db = Database::getInstance();
    
    // =====================================================
    // 1. DIAGNÓSTICO INICIAL
    // =====================================================
    echo "<h2>1. Diagnóstico Inicial</h2>";
    
    $tabelas = [
        'alunos' => "SELECT cfc_id, COUNT(*) AS total FROM alunos GROUP BY cfc_id ORDER BY cfc_id",
        'turmas_teoricas' => "SELECT cfc_id, COUNT(*) AS total FROM turmas_teoricas GROUP BY cfc_id ORDER BY cfc_id",
        'salas' => "SELECT cfc_id, COUNT(*) AS total FROM salas GROUP BY cfc_id ORDER BY cfc_id",
        'instrutores' => "SELECT cfc_id, COUNT(*) AS total FROM instrutores GROUP BY cfc_id ORDER BY cfc_id",
        'aulas' => "SELECT cfc_id, COUNT(*) AS total FROM aulas GROUP BY cfc_id ORDER BY cfc_id",
        'veiculos' => "SELECT cfc_id, COUNT(*) AS total FROM veiculos GROUP BY cfc_id ORDER BY cfc_id"
    ];
    
    $diagnostico = [];
    foreach ($tabelas as $tabela => $query) {
        $resultados = $db->fetchAll($query);
        $diagnostico[$tabela] = $resultados;
        
        echo "<h3>{$tabela}</h3>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>CFC ID</th><th>Total</th></tr>";
        foreach ($resultados as $r) {
            $isLegado = ((int)$r['cfc_id'] === 1);
            $isCanonico = ((int)$r['cfc_id'] === $cfcCanonico);
            $style = $isLegado ? "background: #f8d7da;" : ($isCanonico ? "background: #d4edda;" : "");
            echo "<tr style='{$style}'>";
            echo "<td>{$r['cfc_id']}</td>";
            echo "<td>{$r['total']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Verificar se CFC 36 existe
    echo "<h3>Verificação CFC Canônico (ID: {$cfcCanonico})</h3>";
    $cfc36 = $db->fetch("SELECT id, nome, cnpj FROM cfcs WHERE id = ?", [$cfcCanonico]);
    if (!$cfc36) {
        echo "<p style='color: red;'><strong>ERRO CRÍTICO:</strong> CFC ID {$cfcCanonico} não existe na tabela cfcs!</p>";
        echo "<p>Não é possível prosseguir com a migração. Crie o CFC 36 primeiro.</p>";
        exit;
    } else {
        echo "<p style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
        echo "<strong>✅ CFC Canônico encontrado:</strong> {$cfc36['nome']} (ID: {$cfc36['id']}, CNPJ: {$cfc36['cnpj']})";
        echo "</p>";
    }
    
    // Contar registros com cfc_id = 1
    $totalParaMigrar = 0;
    foreach ($tabelas as $tabela => $query) {
        $count = $db->count($tabela, 'cfc_id = ?', [1]);
        if ($count > 0) {
            $totalParaMigrar += $count;
            echo "<p><strong>{$tabela}:</strong> {$count} registro(s) com cfc_id = 1</p>";
        }
    }
    
    if ($totalParaMigrar === 0) {
        echo "<p style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
        echo "<strong>✅ Nenhum registro para migrar!</strong> Todos os registros já estão com o CFC canônico ({$cfcCanonico}).";
        echo "</p>";
        exit;
    }
    
    echo "<p style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; border-radius: 5px;'>";
    echo "<strong>Total de registros para migrar:</strong> {$totalParaMigrar}";
    echo "</p>";
    
    // =====================================================
    // 2. EXECUTAR MIGRAÇÃO
    // =====================================================
    echo "<h2>2. Executando Migração</h2>";
    
    $migracoes = [
        'alunos' => "UPDATE alunos SET cfc_id = {$cfcCanonico} WHERE cfc_id = 1",
        'turmas_teoricas' => "UPDATE turmas_teoricas SET cfc_id = {$cfcCanonico} WHERE cfc_id = 1",
        'salas' => "UPDATE salas SET cfc_id = {$cfcCanonico} WHERE cfc_id = 1",
        'instrutores' => "UPDATE instrutores SET cfc_id = {$cfcCanonico} WHERE cfc_id = 1",
        'aulas' => "UPDATE aulas SET cfc_id = {$cfcCanonico} WHERE cfc_id = 1",
        'veiculos' => "UPDATE veiculos SET cfc_id = {$cfcCanonico} WHERE cfc_id = 1"
    ];
    
    $resultadosMigracao = [];
    
    foreach ($migracoes as $tabela => $sql) {
        echo "<h3>Migrando {$tabela}...</h3>";
        
        try {
            // Contar antes
            $antes = $db->count($tabela, 'cfc_id = ?', [1]);
            
            if ($antes > 0) {
                // Executar UPDATE
                $db->query($sql);
                
                // Contar depois
                $depois = $db->count($tabela, 'cfc_id = ?', [1]);
                $migrados = $antes - $depois;
                
                $resultadosMigracao[$tabela] = [
                    'antes' => $antes,
                    'depois' => $depois,
                    'migrados' => $migrados,
                    'sucesso' => true
                ];
                
                echo "<p style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
                echo "✅ <strong>Sucesso:</strong> {$migrados} registro(s) migrado(s)";
                echo "</p>";
            } else {
                $resultadosMigracao[$tabela] = [
                    'antes' => 0,
                    'depois' => 0,
                    'migrados' => 0,
                    'sucesso' => true
                ];
                echo "<p>ℹ️ Nenhum registro para migrar nesta tabela.</p>";
            }
        } catch (Exception $e) {
            $resultadosMigracao[$tabela] = [
                'sucesso' => false,
                'erro' => $e->getMessage()
            ];
            echo "<p style='color: red;'><strong>ERRO:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    // =====================================================
    // 3. VERIFICAÇÃO PÓS-MIGRAÇÃO
    // =====================================================
    echo "<h2>3. Verificação Pós-Migração</h2>";
    
    $verificacao = [];
    $temErros = false;
    
    foreach ($tabelas as $tabela => $query) {
        $count = $db->count($tabela, 'cfc_id = ?', [1]);
        $verificacao[$tabela] = $count;
        
        if ($count > 0) {
            $temErros = true;
            echo "<p style='color: red;'><strong>⚠️ {$tabela}:</strong> Ainda existem {$count} registro(s) com cfc_id = 1</p>";
        } else {
            echo "<p style='color: green;'><strong>✅ {$tabela}:</strong> Nenhum registro com cfc_id = 1</p>";
        }
    }
    
    // Verificar distribuição final
    echo "<h3>Distribuição Final por CFC</h3>";
    foreach ($tabelas as $tabela => $query) {
        $resultados = $db->fetchAll($query);
        echo "<h4>{$tabela}</h4>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>CFC ID</th><th>Total</th></tr>";
        foreach ($resultados as $r) {
            $isCanonico = ((int)$r['cfc_id'] === $cfcCanonico);
            $style = $isCanonico ? "background: #d4edda;" : "";
            echo "<tr style='{$style}'>";
            echo "<td>{$r['cfc_id']}</td>";
            echo "<td>{$r['total']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // =====================================================
    // 4. RESUMO FINAL
    // =====================================================
    echo "<h2>4. Resumo Final</h2>";
    
    if ($temErros) {
        echo "<p style='background: #f8d7da; padding: 15px; border: 1px solid #dc3545; border-radius: 5px;'>";
        echo "<strong>⚠️ ATENÇÃO:</strong> Ainda existem registros com cfc_id = 1. Revise os erros acima.";
        echo "</p>";
    } else {
        echo "<p style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
        echo "<strong>✅ Migração concluída com sucesso!</strong>";
        echo "</p>";
    }
    
    echo "<h3>Estatísticas</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>Tabela</th><th>Registros Migrados</th><th>Status</th></tr>";
    foreach ($resultadosMigracao as $tabela => $resultado) {
        if ($resultado['sucesso']) {
            echo "<tr>";
            echo "<td>{$tabela}</td>";
            echo "<td>{$resultado['migrados']}</td>";
            echo "<td>✅ Sucesso</td>";
            echo "</tr>";
        } else {
            echo "<tr style='background: #f8d7da;'>";
            echo "<td>{$tabela}</td>";
            echo "<td>-</td>";
            echo "<td>❌ Erro: " . htmlspecialchars($resultado['erro']) . "</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<p><strong>Próximos passos:</strong></p>";
    echo "<ol>";
    echo "<li>Execute o checklist de testes: <code>docs/CHECKLIST_TESTES_CFC_36.md</code></li>";
    echo "<li>Verifique o histórico do aluno 167: <code>admin/index.php?page=historico-aluno&id=167</code></li>";
    echo "<li>Teste o modal de turmas teóricas: <code>admin/index.php?page=turmas-teoricas&acao=detalhes&turma_id=16</code></li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>ERRO CRÍTICO:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

