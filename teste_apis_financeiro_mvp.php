<?php
/**
 * Teste das APIs Financeiras MVP
 * Sistema CFC - Bom Conselho
 */

require_once 'includes/config.php';
require_once 'includes/database.php';

echo "🧪 Testando APIs Financeiras MVP...\n\n";

$db = Database::getInstance();

// 1. Teste de criação de fatura
echo "1️⃣ Testando criação de fatura...\n";

// Buscar um aluno existente
$aluno = $db->fetch("SELECT id, nome FROM alunos LIMIT 1");
if (!$aluno) {
    echo "❌ Nenhum aluno encontrado para teste\n";
    exit;
}

echo "✅ Aluno encontrado: {$aluno['nome']} (ID: {$aluno['id']})\n";

// Simular criação de fatura
$faturaData = [
    'aluno_id' => $aluno['id'],
    'titulo' => 'Curso Teórico B - Teste MVP',
    'valor_total' => 1200.00,
    'status' => 'aberta',
    'vencimento' => date('Y-m-d', strtotime('+30 days')),
    'forma_pagamento' => 'pix',
    'parcelas' => 1,
    'observacoes' => 'Fatura de teste MVP',
    'criado_por' => 1
];

try {
    $faturaId = $db->insert('financeiro_faturas', $faturaData);
    echo "✅ Fatura criada com ID: $faturaId\n";
} catch (Exception $e) {
    echo "❌ Erro ao criar fatura: " . $e->getMessage() . "\n";
}

// 2. Teste de criação de despesa
echo "\n2️⃣ Testando criação de despesa...\n";

$despesaData = [
    'fornecedor' => 'Posto ABC',
    'descricao' => 'Combustível para veículos',
    'categoria' => 'combustivel',
    'valor' => 300.00,
    'status' => 'pendente',
    'vencimento' => date('Y-m-d', strtotime('+15 days')),
    'forma_pagamento' => 'pix',
    'observacoes' => 'Despesa de teste MVP',
    'criado_por' => 1
];

try {
    $despesaId = $db->insert('financeiro_pagamentos', $despesaData);
    echo "✅ Despesa criada com ID: $despesaId\n";
} catch (Exception $e) {
    echo "❌ Erro ao criar despesa: " . $e->getMessage() . "\n";
}

// 3. Teste de listagem
echo "\n3️⃣ Testando listagem...\n";

try {
    $faturas = $db->fetchAll("SELECT COUNT(*) as total FROM financeiro_faturas");
    $despesas = $db->fetchAll("SELECT COUNT(*) as total FROM financeiro_pagamentos");
    
    echo "✅ Faturas cadastradas: " . $faturas[0]['total'] . "\n";
    echo "✅ Despesas cadastradas: " . $despesas[0]['total'] . "\n";
} catch (Exception $e) {
    echo "❌ Erro ao listar: " . $e->getMessage() . "\n";
}

// 4. Teste de configurações
echo "\n4️⃣ Testando configurações...\n";

try {
    $configs = $db->fetchAll("SELECT chave, valor FROM financeiro_configuracoes");
    echo "✅ Configurações encontradas:\n";
    foreach ($configs as $config) {
        echo "   - {$config['chave']}: {$config['valor']}\n";
    }
} catch (Exception $e) {
    echo "❌ Erro ao buscar configurações: " . $e->getMessage() . "\n";
}

// 5. Teste de inadimplência
echo "\n5️⃣ Testando inadimplência...\n";

try {
    // Marcar fatura como vencida para teste
    $db->update('financeiro_faturas', [
        'vencimento' => date('Y-m-d', strtotime('-35 days')),
        'status' => 'vencida'
    ], 'id = ?', [$faturaId]);
    
    // Verificar se aluno foi marcado como inadimplente
    $alunoInadimplente = $db->fetch("SELECT inadimplente, inadimplente_desde FROM alunos WHERE id = ?", [$aluno['id']]);
    
    if ($alunoInadimplente['inadimplente']) {
        echo "✅ Aluno marcado como inadimplente desde: {$alunoInadimplente['inadimplente_desde']}\n";
    } else {
        echo "⚠️ Aluno não foi marcado como inadimplente\n";
    }
} catch (Exception $e) {
    echo "❌ Erro ao testar inadimplência: " . $e->getMessage() . "\n";
}

echo "\n🎉 Testes das APIs concluídos!\n";

// Limpeza dos dados de teste
echo "\n🧹 Limpando dados de teste...\n";
try {
    $db->delete('financeiro_faturas', 'observacoes = ?', ['Fatura de teste MVP']);
    $db->delete('financeiro_pagamentos', 'observacoes = ?', ['Despesa de teste MVP']);
    echo "✅ Dados de teste removidos\n";
} catch (Exception $e) {
    echo "❌ Erro na limpeza: " . $e->getMessage() . "\n";
}
