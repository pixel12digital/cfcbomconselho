<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

echo "🔍 EXECUTANDO TESTES DE VALIDAÇÃO - ETAPA 1.1\n";
echo "============================================\n";

$db = Database::getInstance();
$testesPassaram = 0;
$totalTestes = 0;

// Teste 1: Verificar tabelas criadas
echo "TESTE 1: Verificação de tabelas criadas\n";
$totalTestes++;
$tabelas = $db->fetchAll("
    SELECT TABLE_NAME 
    FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME IN ('turmas', 'turma_aulas', 'turma_alunos', 'turma_presencas', 'turma_diario')
");

if (count($tabelas) === 5) {
    echo "✅ PASSOU - Todas as 5 tabelas foram criadas\n";
    $testesPassaram++;
} else {
    echo "❌ FALHOU - Apenas " . count($tabelas) . "/5 tabelas encontradas\n";
}

// Teste 2: Verificar campos em turmas
echo "\nTESTE 2: Verificação de campos em turmas\n";
$totalTestes++;
$camposTurmas = $db->fetchAll("
    SELECT COLUMN_NAME 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'turmas' 
    AND COLUMN_NAME IN ('capacidade_maxima', 'frequencia_minima', 'sala_local', 'link_online')
");

if (count($camposTurmas) === 4) {
    echo "✅ PASSOU - Todos os 4 campos foram adicionados\n";
    $testesPassaram++;
} else {
    echo "❌ FALHOU - Apenas " . count($camposTurmas) . "/4 campos encontrados\n";
}

// Teste 3: Verificar campos em aulas_slots
echo "\nTESTE 3: Verificação de campos em aulas_slots\n";
$totalTestes++;
$camposSlots = $db->fetchAll("
    SELECT COLUMN_NAME 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'aulas_slots' 
    AND COLUMN_NAME IN ('turma_id', 'turma_aula_id')
");

if (count($camposSlots) === 2) {
    echo "✅ PASSOU - Ambos os campos foram adicionados\n";
    $testesPassaram++;
} else {
    echo "❌ FALHOU - Apenas " . count($camposSlots) . "/2 campos encontrados\n";
}

// Teste 4: Verificar foreign keys
echo "\nTESTE 4: Verificação de foreign keys\n";
$totalTestes++;
$foreignKeys = $db->fetchAll("
    SELECT 
        TABLE_NAME,
        CONSTRAINT_NAME,
        REFERENCED_TABLE_NAME
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME IN ('turma_presencas', 'turma_diario', 'aulas_slots')
    AND REFERENCED_TABLE_NAME IS NOT NULL
");

if (count($foreignKeys) >= 8) {
    echo "✅ PASSOU - " . count($foreignKeys) . " foreign keys criadas\n";
    $testesPassaram++;
} else {
    echo "❌ FALHOU - Apenas " . count($foreignKeys) . " foreign keys encontradas\n";
}

// Teste 5: Verificar índices
echo "\nTESTE 5: Verificação de índices\n";
$totalTestes++;
$indices = $db->fetchAll("
    SELECT 
        TABLE_NAME,
        INDEX_NAME
    FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME IN ('turmas', 'turma_aulas', 'turma_alunos', 'turma_presencas', 'turma_diario', 'aulas_slots')
    AND INDEX_NAME NOT IN ('PRIMARY')
    GROUP BY TABLE_NAME, INDEX_NAME
");

if (count($indices) >= 15) {
    echo "✅ PASSOU - " . count($indices) . " índices criados\n";
    $testesPassaram++;
} else {
    echo "❌ FALHOU - Apenas " . count($indices) . " índices encontrados\n";
}

// Teste 6: Teste de integridade referencial
echo "\nTESTE 6: Teste de integridade referencial\n";
$totalTestes++;
try {
    // Tentar inserir dados de teste
    $db->query("INSERT INTO turmas (nome, instrutor_id, tipo_aula, cfc_id) VALUES ('Turma Teste', 1, 'teorica', 1)");
    $turmaId = $db->lastInsertId();
    
    $db->query("INSERT INTO turma_aulas (turma_id, ordem, nome_aula) VALUES (?, 1, 'Aula Teste')", [$turmaId]);
    $aulaId = $db->lastInsertId();
    
    $db->query("INSERT INTO turma_alunos (turma_id, aluno_id) VALUES (?, 1)", [$turmaId]);
    
    $db->query("INSERT INTO turma_presencas (turma_id, turma_aula_id, aluno_id, presente, registrado_por) VALUES (?, ?, 1, TRUE, 1)", [$turmaId, $aulaId]);
    
    $db->query("INSERT INTO turma_diario (turma_aula_id, conteudo_ministrado, criado_por) VALUES (?, 'Conteúdo teste', 1)", [$aulaId]);
    
    // Limpar dados de teste
    $db->query("DELETE FROM turma_diario WHERE turma_aula_id = ?", [$aulaId]);
    $db->query("DELETE FROM turma_presencas WHERE turma_id = ?", [$turmaId]);
    $db->query("DELETE FROM turma_alunos WHERE turma_id = ?", [$turmaId]);
    $db->query("DELETE FROM turma_aulas WHERE turma_id = ?", [$turmaId]);
    $db->query("DELETE FROM turmas WHERE id = ?", [$turmaId]);
    
    echo "✅ PASSOU - Integridade referencial funcionando\n";
    $testesPassaram++;
} catch (Exception $e) {
    echo "❌ FALHOU - Erro de integridade: " . $e->getMessage() . "\n";
}

// Teste 7: Verificar compatibilidade com slots individuais
echo "\nTESTE 7: Verificação de compatibilidade\n";
$totalTestes++;
try {
    $slotsIndividuais = $db->fetchAll("SELECT COUNT(*) as total FROM aulas_slots WHERE turma_id IS NULL");
    echo "✅ PASSOU - " . $slotsIndividuais[0]['total'] . " slots individuais mantidos\n";
    $testesPassaram++;
} catch (Exception $e) {
    echo "❌ FALHOU - Erro na verificação: " . $e->getMessage() . "\n";
}

// Relatório final
echo "\n============================================\n";
echo "📊 RELATÓRIO FINAL DE TESTES\n";
echo "============================================\n";
echo "✅ Testes que passaram: $testesPassaram\n";
echo "❌ Testes que falharam: " . ($totalTestes - $testesPassaram) . "\n";
echo "📈 Taxa de sucesso: " . round(($testesPassaram / $totalTestes) * 100, 2) . "%\n";

if ($testesPassaram === $totalTestes) {
    echo "\n🎉 TODOS OS TESTES PASSARAM!\n";
    echo "✅ ETAPA 1.1 VALIDADA COM SUCESSO\n";
} else {
    echo "\n⚠️  ALGUNS TESTES FALHARAM\n";
    echo "❌ ETAPA 1.1 PRECISA DE AJUSTES\n";
}

echo "\n🎯 PRÓXIMA ETAPA: 1.2 - API de Presença\n";
echo "📁 Arquivo: admin/api/turma-presencas.php\n";
echo "⏰ Estimativa: 2 dias\n";
?>
