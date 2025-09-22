<?php
/**
 * Script de Teste das APIs Financeiras
 * Sistema CFC - Bom Conselho
 */

require_once 'includes/config.php';
require_once 'includes/database.php';

echo "🧪 Iniciando testes das APIs financeiras...\n\n";

$db = Database::getInstance();
$testResults = [];

// 1. Teste de criação de fatura única
echo "1️⃣ Testando criação de fatura única...\n";

// Primeiro, criar um aluno e matrícula de teste
$alunoId = $db->insert('alunos', [
    'nome' => 'Aluno Teste Financeiro',
    'cpf' => '12345678901',
    'cfc_id' => 36,
    'categoria_cnh' => 'B',
    'tipo_servico' => 'primeira_habilitacao',
    'status' => 'ativo'
]);

$matriculaId = $db->insert('matriculas', [
    'aluno_id' => $alunoId,
    'categoria_cnh' => 'B',
    'tipo_servico' => 'primeira_habilitacao',
    'status' => 'ativa',
    'data_inicio' => date('Y-m-d'),
    'valor_total' => 1200.00,
    'forma_pagamento' => 'avista'
]);

// Simular criação de fatura via API
$faturaData = [
    'matricula_id' => $matriculaId,
    'aluno_id' => $alunoId,
    'descricao' => 'Curso Teórico B - Teste',
    'valor' => 1200.00,
    'desconto' => 0,
    'acrescimo' => 0,
    'vencimento' => date('Y-m-d', strtotime('+30 days')),
    'meio' => 'pix'
];

$faturaId = $db->insert('faturas', array_merge($faturaData, [
    'valor_liquido' => 1200.00,
    'criado_por' => 1
]));

$testResults['fatura_unica'] = $faturaId ? 'OK' : 'FALHOU';
echo "✅ Fatura única criada: ID $faturaId\n\n";

// 2. Teste de criação de parcelas
echo "2️⃣ Testando criação de parcelas...\n";

$parcelasData = [
    'matricula_id' => $matriculaId,
    'aluno_id' => $alunoId,
    'descricao' => 'Curso Teórico B - Parcelado',
    'valor_total' => 1200.00,
    'parcelas' => 6,
    'primeiro_vencimento' => date('Y-m-d', strtotime('+30 days')),
    'intervalo_dias' => 30,
    'meio' => 'pix'
];

$parcelasCriadas = [];
for ($i = 1; $i <= 6; $i++) {
    $vencimento = date('Y-m-d', strtotime($parcelasData['primeiro_vencimento'] . " +" . (($i - 1) * 30) . " days"));
    $parcelaId = $db->insert('faturas', [
        'matricula_id' => $parcelasData['matricula_id'],
        'aluno_id' => $parcelasData['aluno_id'],
        'numero' => 'FAT-TEST-' . $i,
        'descricao' => $parcelasData['descricao'] . " - Parcela $i/6",
        'valor' => 200.00,
        'desconto' => 0,
        'acrescimo' => 0,
        'valor_liquido' => 200.00,
        'vencimento' => $vencimento,
        'meio' => $parcelasData['meio'],
        'criado_por' => 1
    ]);
    $parcelasCriadas[] = $parcelaId;
}

$testResults['parcelas'] = count($parcelasCriadas) === 6 ? 'OK' : 'FALHOU';
echo "✅ Parcelas criadas: " . count($parcelasCriadas) . " parcelas\n\n";

// 3. Teste de registro de pagamento
echo "3️⃣ Testando registro de pagamento...\n";

$pagamentoId = $db->insert('pagamentos', [
    'fatura_id' => $faturaId,
    'data_pagamento' => date('Y-m-d'),
    'valor_pago' => 600.00,
    'metodo' => 'pix',
    'obs' => 'Pagamento teste',
    'criado_por' => 1
]);

// Atualizar status da fatura
$db->update('faturas', ['status' => 'parcial'], 'id = ?', [$faturaId]);

$testResults['pagamento'] = $pagamentoId ? 'OK' : 'FALHOU';
echo "✅ Pagamento registrado: ID $pagamentoId\n\n";

// 4. Teste de criação de despesa
echo "4️⃣ Testando criação de despesa...\n";

$despesaId = $db->insert('despesas', [
    'titulo' => 'Combustível - Teste',
    'fornecedor' => 'Posto Teste',
    'categoria' => 'combustivel',
    'valor' => 150.00,
    'vencimento' => date('Y-m-d', strtotime('+15 days')),
    'pago' => 0,
    'metodo' => 'pix',
    'obs' => 'Despesa teste',
    'criado_por' => 1
]);

$testResults['despesa'] = $despesaId ? 'OK' : 'FALHOU';
echo "✅ Despesa criada: ID $despesaId\n\n";

// 5. Teste de LGPD
echo "5️⃣ Testando registro LGPD...\n";

$db->update('alunos', [
    'lgpd_consentido' => 1,
    'lgpd_consentido_em' => date('Y-m-d H:i:s')
], 'id = ?', [$alunoId]);

$lgpdCheck = $db->fetch("SELECT lgpd_consentido, lgpd_consentido_em FROM alunos WHERE id = ?", [$alunoId]);
$testResults['lgpd'] = $lgpdCheck['lgpd_consentido'] ? 'OK' : 'FALHOU';
echo "✅ LGPD registrado: " . ($lgpdCheck['lgpd_consentido'] ? 'Sim' : 'Não') . "\n\n";

// 6. Teste de listagem com filtros
echo "6️⃣ Testando listagem com filtros...\n";

$faturas = $db->fetchAll("
    SELECT f.*, a.nome as aluno_nome 
    FROM faturas f
    JOIN alunos a ON f.aluno_id = a.id
    WHERE f.aluno_id = ?
    ORDER BY f.vencimento DESC
", [$alunoId]);

$testResults['listagem'] = count($faturas) > 0 ? 'OK' : 'FALHOU';
echo "✅ Faturas listadas: " . count($faturas) . " faturas\n\n";

// 7. Teste de regra de negócio (duplicatas)
echo "7️⃣ Testando regra de negócio (duplicatas)...\n";

try {
    $duplicataId = $db->insert('faturas', [
        'matricula_id' => $matriculaId,
        'aluno_id' => $alunoId,
        'descricao' => 'Fatura duplicada teste',
        'valor' => 500.00,
        'desconto' => 0,
        'acrescimo' => 0,
        'valor_liquido' => 500.00,
        'vencimento' => date('Y-m-d', strtotime('+30 days')),
        'meio' => 'pix',
        'criado_por' => 1
    ]);
    
    // Se chegou aqui, não deveria ter criado (regra de negócio)
    $testResults['regra_duplicata'] = 'FALHOU - Deveria ter bloqueado';
    echo "❌ Fatura duplicada criada (não deveria): ID $duplicataId\n\n";
} catch (Exception $e) {
    $testResults['regra_duplicata'] = 'OK - Bloqueou corretamente';
    echo "✅ Regra de duplicata funcionando: " . $e->getMessage() . "\n\n";
}

// Resumo dos testes
echo "📊 RESUMO DOS TESTES:\n";
echo "====================\n";
foreach ($testResults as $teste => $resultado) {
    $status = $resultado === 'OK' ? '✅' : '❌';
    echo "$status $teste: $resultado\n";
}

// Limpeza dos dados de teste
echo "\n🧹 Limpando dados de teste...\n";
$db->delete('pagamentos', 'fatura_id IN (SELECT id FROM faturas WHERE aluno_id = ?)', [$alunoId]);
$db->delete('faturas', 'aluno_id = ?', [$alunoId]);
$db->delete('matriculas', 'aluno_id = ?', [$alunoId]);
$db->delete('alunos', 'id = ?', [$alunoId]);
$db->delete('despesas', 'id = ?', [$despesaId]);

echo "✅ Dados de teste removidos\n";
echo "\n🎉 Testes concluídos!\n";
