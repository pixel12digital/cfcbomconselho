<?php
/**
 * SCRIPT DE QA VISUAL/FUNCIONAL - SISTEMA CFC
 * Teste automatizado das funcionalidades principais
 */

require_once 'includes/config.php';
require_once 'includes/database.php';

echo "🧪 INICIANDO CHECKLIST DE QA VISUAL/FUNCIONAL\n";
echo "==============================================\n\n";

$db = Database::getInstance();
$testResults = [];
$totalTests = 0;
$passedTests = 0;

// Função para registrar resultado de teste
function recordTest($testName, $passed, $details = '') {
    global $testResults, $totalTests, $passedTests;
    
    $totalTests++;
    if ($passed) $passedTests++;
    
    $status = $passed ? '✅ PASS' : '❌ FAIL';
    $testResults[] = [
        'test' => $testName,
        'status' => $status,
        'details' => $details
    ];
    
    echo "$status $testName\n";
    if ($details) echo "   $details\n";
}

// 1. Teste de Menu e Permissões
echo "1️⃣ TESTANDO MENU E PERMISSÕES\n";
echo "-----------------------------\n";

// Verificar se arquivos legados foram removidos
$legacyFiles = [
    'admin/pages/turma-dashboard.php',
    'admin/pages/turma-calendario.php', 
    'admin/pages/turma-matriculas.php',
    'admin/pages/turma-configuracoes.php',
    'admin/pages/turma-templates.php',
    'admin/pages/turma-grade-generator.php'
];

foreach ($legacyFiles as $file) {
    $exists = file_exists($file);
    recordTest("Arquivo legado removido: " . basename($file), !$exists, 
        $exists ? "Arquivo ainda existe" : "Arquivo movido para deprecated/");
}

// Verificar se arquivos principais existem
$mainFiles = [
    'admin/index.php',
    'admin/pages/alunos.php',
    'admin/pages/turmas.php',
    'admin/pages/financeiro-faturas.php',
    'admin/pages/financeiro-despesas.php',
    'admin/pages/financeiro-relatorios.php'
];

foreach ($mainFiles as $file) {
    $exists = file_exists($file);
    recordTest("Arquivo principal existe: " . basename($file), $exists,
        $exists ? "Arquivo encontrado" : "Arquivo não encontrado");
}

// 2. Teste de Banco de Dados
echo "\n2️⃣ TESTANDO BANCO DE DADOS\n";
echo "---------------------------\n";

try {
    // Verificar tabelas financeiras
    $tables = ['matriculas', 'faturas', 'pagamentos', 'despesas'];
    foreach ($tables as $table) {
        $exists = $db->fetch("SHOW TABLES LIKE '$table'");
        recordTest("Tabela existe: $table", !empty($exists), 
            $exists ? "Tabela encontrada" : "Tabela não encontrada");
    }
    
    // Verificar colunas financeiras na tabela matriculas
    $columns = $db->fetchAll("SHOW COLUMNS FROM matriculas");
    $hasFinancialColumns = false;
    foreach ($columns as $column) {
        if (in_array($column['Field'], ['valor_total', 'forma_pagamento', 'status_financeiro'])) {
            $hasFinancialColumns = true;
            break;
        }
    }
    recordTest("Colunas financeiras em matriculas", $hasFinancialColumns,
        $hasFinancialColumns ? "Colunas encontradas" : "Colunas não encontradas");
    
} catch (Exception $e) {
    recordTest("Conexão com banco de dados", false, $e->getMessage());
}

// 3. Teste de APIs
echo "\n3️⃣ TESTANDO APIs\n";
echo "----------------\n";

$apiFiles = [
    'admin/api/faturas.php',
    'admin/api/pagamentos.php', 
    'admin/api/despesas.php',
    'admin/api/lgpd.php'
];

foreach ($apiFiles as $api) {
    $exists = file_exists($api);
    recordTest("API existe: " . basename($api), $exists,
        $exists ? "API encontrada" : "API não encontrada");
}

// 4. Teste de CSS/JS Padronizações
echo "\n4️⃣ TESTANDO PADRONIZAÇÕES CSS/JS\n";
echo "----------------------------------\n";

$cssFiles = [
    'admin/assets/css/padronizacoes.css',
    'admin/assets/css/admin.css'
];

foreach ($cssFiles as $css) {
    $exists = file_exists($css);
    recordTest("CSS existe: " . basename($css), $exists,
        $exists ? "Arquivo encontrado" : "Arquivo não encontrado");
}

$jsFiles = [
    'admin/assets/js/padronizacoes.js'
];

foreach ($jsFiles as $js) {
    $exists = file_exists($js);
    recordTest("JS existe: " . basename($js), $exists,
        $exists ? "Arquivo encontrado" : "Arquivo não encontrado");
}

// 5. Teste de Deep Links
echo "\n5️⃣ TESTANDO DEEP LINKS\n";
echo "----------------------\n";

// Verificar se função abrirFinanceiroAluno existe em alunos.php
$alunosContent = file_get_contents('admin/pages/alunos.php');
$hasFinancialFunction = strpos($alunosContent, 'abrirFinanceiroAluno') !== false;
recordTest("Função abrirFinanceiroAluno em alunos.php", $hasFinancialFunction,
    $hasFinancialFunction ? "Função encontrada" : "Função não encontrada");

// Verificar se breadcrumb existe em financeiro-faturas.php
$faturasContent = file_get_contents('admin/pages/financeiro-faturas.php');
$hasBreadcrumb = strpos($faturasContent, 'breadcrumb') !== false;
recordTest("Breadcrumb em financeiro-faturas.php", $hasBreadcrumb,
    $hasBreadcrumb ? "Breadcrumb encontrado" : "Breadcrumb não encontrado");

// 6. Teste de Feature Flags
echo "\n6️⃣ TESTANDO FEATURE FLAGS\n";
echo "-------------------------\n";

$configContent = file_get_contents('includes/config.php');
$hasFinanceiroFlag = strpos($configContent, 'FINANCEIRO_ENABLED') !== false;
recordTest("Feature flag FINANCEIRO_ENABLED", $hasFinanceiroFlag,
    $hasFinanceiroFlag ? "Flag encontrada" : "Flag não encontrada");

// 7. Teste de Estados de UX
echo "\n7️⃣ TESTANDO ESTADOS DE UX\n";
echo "-------------------------\n";

// Verificar se classes de loading existem
$padronizacoesContent = file_get_contents('admin/assets/css/padronizacoes.css');
$hasLoadingStates = strpos($padronizacoesContent, 'loading-state') !== false;
recordTest("Estados de loading CSS", $hasLoadingStates,
    $hasLoadingStates ? "Classes encontradas" : "Classes não encontradas");

// Verificar se classes de empty state existem
$hasEmptyStates = strpos($padronizacoesContent, 'empty-state') !== false;
recordTest("Estados vazios CSS", $hasEmptyStates,
    $hasEmptyStates ? "Classes encontradas" : "Classes não encontradas");

// Verificar se validação em tempo real existe
$padronizacoesJS = file_get_contents('admin/assets/js/padronizacoes.js');
$hasValidation = strpos($padronizacoesJS, 'validateCPF') !== false;
recordTest("Validação em tempo real JS", $hasValidation,
    $hasValidation ? "Funções encontradas" : "Funções não encontradas");

// Resumo Final
echo "\n📊 RESUMO FINAL\n";
echo "===============\n";
echo "Total de testes: $totalTests\n";
echo "Testes aprovados: $passedTests\n";
echo "Taxa de sucesso: " . round(($passedTests / $totalTests) * 100, 2) . "%\n\n";

if ($passedTests === $totalTests) {
    echo "🎉 TODOS OS TESTES PASSARAM!\n";
    echo "Sistema pronto para produção.\n";
} else {
    echo "⚠️ ALGUNS TESTES FALHARAM\n";
    echo "Verifique os itens marcados com ❌ FAIL\n";
}

echo "\n📋 DETALHES DOS TESTES:\n";
echo "======================\n";
foreach ($testResults as $result) {
    echo $result['status'] . " " . $result['test'] . "\n";
    if ($result['details']) {
        echo "   " . $result['details'] . "\n";
    }
}

echo "\n✅ CHECKLIST DE QA CONCLUÍDO\n";
