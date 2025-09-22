<?php
/**
 * Script de Teste - ETAPA 1.2: API de Presença
 * Sistema de Turmas Teóricas - CFC Bom Conselho
 */

require_once 'includes/config.php';
require_once 'includes/database.php';

echo "🧪 INICIANDO TESTES - ETAPA 1.2: API DE PRESENÇA\n";
echo "================================================\n";

$db = Database::getInstance();

try {
    // 1. Criar dados de teste
    echo "1. Criando dados de teste...\n";
    $dadosTeste = criarDadosTeste($db);
    
    if (!$dadosTeste['success']) {
        throw new Exception($dadosTeste['message']);
    }
    
    echo "✅ Dados de teste criados com sucesso\n";
    echo "   - Turma ID: " . $dadosTeste['turma_id'] . "\n";
    echo "   - Aula ID: " . $dadosTeste['aula_id'] . "\n";
    echo "   - Alunos: " . count($dadosTeste['alunos']) . "\n";
    
    // 2. Testar API de presenças
    echo "\n2. Testando API de presenças...\n";
    $testesPresenca = testarAPIPresencas($dadosTeste);
    
    // 3. Testar API de frequência
    echo "\n3. Testando API de frequência...\n";
    $testesFrequencia = testarAPIFrequencia($dadosTeste);
    
    // 4. Relatório final
    echo "\n================================================\n";
    echo "📊 RELATÓRIO FINAL DE TESTES\n";
    echo "================================================\n";
    
    $totalTestes = $testesPresenca['total'] + $testesFrequencia['total'];
    $testesPassaram = $testesPresenca['passaram'] + $testesFrequencia['passaram'];
    
    echo "✅ Testes que passaram: $testesPassaram\n";
    echo "❌ Testes que falharam: " . ($totalTestes - $testesPassaram) . "\n";
    echo "📈 Taxa de sucesso: " . round(($testesPassaram / $totalTestes) * 100, 2) . "%\n";
    
    if ($testesPassaram === $totalTestes) {
        echo "\n🎉 TODOS OS TESTES PASSARAM!\n";
        echo "✅ ETAPA 1.2 VALIDADA COM SUCESSO\n";
    } else {
        echo "\n⚠️  ALGUNS TESTES FALHARAM\n";
        echo "❌ ETAPA 1.2 PRECISA DE AJUSTES\n";
    }
    
    // 5. Limpar dados de teste
    echo "\n5. Limpando dados de teste...\n";
    limparDadosTeste($db, $dadosTeste);
    echo "✅ Dados de teste removidos\n";
    
} catch (Exception $e) {
    echo "❌ ERRO CRÍTICO: " . $e->getMessage() . "\n";
    echo "📞 Contate o suporte técnico\n";
    exit(1);
}

/**
 * Criar dados de teste
 */
function criarDadosTeste($db) {
    try {
        $db->beginTransaction();
        
        // Criar turma de teste
        $turmaId = $db->insert('turmas', [
            'nome' => 'Turma Teste API',
            'instrutor_id' => 1,
            'tipo_aula' => 'teorica',
            'categoria_cnh' => 'AB',
            'data_inicio' => date('Y-m-d'),
            'data_fim' => date('Y-m-d', strtotime('+30 days')),
            'status' => 'ativo',
            'capacidade_maxima' => 30,
            'frequencia_minima' => 75.00,
            'sala_local' => 'Sala 1',
            'cfc_id' => 1
        ]);
        
        // Criar aula de teste
        $aulaId = $db->insert('turma_aulas', [
            'turma_id' => $turmaId,
            'ordem' => 1,
            'nome_aula' => 'Aula Teste - Legislação',
            'duracao_minutos' => 50,
            'data_aula' => date('Y-m-d'),
            'tipo_conteudo' => 'legislacao',
            'status' => 'agendada'
        ]);
        
        // Criar alunos de teste
        $alunos = [];
        for ($i = 1; $i <= 3; $i++) {
            $alunoId = $db->insert('alunos', [
                'nome' => "Aluno Teste $i",
                'cpf' => "000.000.00$i",
                'categoria_cnh' => 'AB',
                'cfc_id' => 1,
                'status' => 'ativo'
            ]);
            
            // Matricular aluno na turma
            $db->insert('turma_alunos', [
                'turma_id' => $turmaId,
                'aluno_id' => $alunoId,
                'status' => 'matriculado'
            ]);
            
            $alunos[] = [
                'id' => $alunoId,
                'nome' => "Aluno Teste $i",
                'cpf' => "000.000.00$i"
            ];
        }
        
        $db->commit();
        
        return [
            'success' => true,
            'turma_id' => $turmaId,
            'aula_id' => $aulaId,
            'alunos' => $alunos
        ];
        
    } catch (Exception $e) {
        $db->rollback();
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Testar API de presenças
 */
function testarAPIPresencas($dadosTeste) {
    $testes = [
        'total' => 0,
        'passaram' => 0,
        'falharam' => 0,
        'detalhes' => []
    ];
    
    // Teste 1: Marcar presença individual
    echo "   Teste 1: Marcar presença individual...\n";
    $testes['total']++;
    
    $presenca = [
        'turma_id' => $dadosTeste['turma_id'],
        'turma_aula_id' => $dadosTeste['aula_id'],
        'aluno_id' => $dadosTeste['alunos'][0]['id'],
        'presente' => true,
        'observacao' => 'Teste de presença'
    ];
    
    $resultado = simularAPICall('POST', 'admin/api/turma-presencas.php', $presenca);
    
    if ($resultado['success']) {
        echo "   ✅ PASSOU\n";
        $testes['passaram']++;
    } else {
        echo "   ❌ FALHOU: " . $resultado['message'] . "\n";
        $testes['falharam']++;
    }
    
    // Teste 2: Marcar presenças em lote
    echo "   Teste 2: Marcar presenças em lote...\n";
    $testes['total']++;
    
    $presencasLote = [
        'turma_id' => $dadosTeste['turma_id'],
        'turma_aula_id' => $dadosTeste['aula_id'],
        'presencas' => [
            [
                'aluno_id' => $dadosTeste['alunos'][1]['id'],
                'presente' => true,
                'observacao' => 'Presente'
            ],
            [
                'aluno_id' => $dadosTeste['alunos'][2]['id'],
                'presente' => false,
                'observacao' => 'Falta justificada'
            ]
        ]
    ];
    
    $resultado = simularAPICall('POST', 'admin/api/turma-presencas.php', $presencasLote);
    
    if ($resultado['success']) {
        echo "   ✅ PASSOU\n";
        $testes['passaram']++;
    } else {
        echo "   ❌ FALHOU: " . $resultado['message'] . "\n";
        $testes['falharam']++;
    }
    
    // Teste 3: Buscar presenças da aula
    echo "   Teste 3: Buscar presenças da aula...\n";
    $testes['total']++;
    
    $resultado = simularAPICall('GET', 'admin/api/turma-presencas.php', null, [
        'turma_id' => $dadosTeste['turma_id'],
        'aula_id' => $dadosTeste['aula_id']
    ]);
    
    if ($resultado['success'] && count($resultado['data']) >= 3) {
        echo "   ✅ PASSOU - " . count($resultado['data']) . " presenças encontradas\n";
        $testes['passaram']++;
    } else {
        echo "   ❌ FALHOU: " . ($resultado['message'] ?? 'Dados não encontrados') . "\n";
        $testes['falharam']++;
    }
    
    // Teste 4: Atualizar presença
    echo "   Teste 4: Atualizar presença...\n";
    $testes['total']++;
    
    // Primeiro, buscar uma presença para atualizar
    $presencas = simularAPICall('GET', 'admin/api/turma-presencas.php', null, [
        'turma_id' => $dadosTeste['turma_id'],
        'aula_id' => $dadosTeste['aula_id']
    ]);
    
    if ($presencas['success'] && !empty($presencas['data'])) {
        $presencaId = $presencas['data'][0]['id'];
        $dadosAtualizacao = [
            'presente' => false,
            'observacao' => 'Presença atualizada'
        ];
        
        $resultado = simularAPICall('PUT', "admin/api/turma-presencas.php?id=$presencaId", $dadosAtualizacao);
        
        if ($resultado['success']) {
            echo "   ✅ PASSOU\n";
            $testes['passaram']++;
        } else {
            echo "   ❌ FALHOU: " . $resultado['message'] . "\n";
            $testes['falharam']++;
        }
    } else {
        echo "   ❌ FALHOU: Não foi possível encontrar presença para atualizar\n";
        $testes['falharam']++;
    }
    
    return $testes;
}

/**
 * Testar API de frequência
 */
function testarAPIFrequencia($dadosTeste) {
    $testes = [
        'total' => 0,
        'passaram' => 0,
        'falharam' => 0,
        'detalhes' => []
    ];
    
    // Teste 1: Calcular frequência de um aluno
    echo "   Teste 1: Calcular frequência de um aluno...\n";
    $testes['total']++;
    
    $resultado = simularAPICall('GET', 'admin/api/turma-frequencia.php', null, [
        'aluno_id' => $dadosTeste['alunos'][0]['id'],
        'turma_id' => $dadosTeste['turma_id']
    ]);
    
    if ($resultado['success'] && isset($resultado['data']['estatisticas'])) {
        $freq = $resultado['data']['estatisticas']['percentual_frequencia'];
        echo "   ✅ PASSOU - Frequência: $freq%\n";
        $testes['passaram']++;
    } else {
        echo "   ❌ FALHOU: " . ($resultado['message'] ?? 'Dados não encontrados') . "\n";
        $testes['falharam']++;
    }
    
    // Teste 2: Calcular frequência da turma
    echo "   Teste 2: Calcular frequência da turma...\n";
    $testes['total']++;
    
    $resultado = simularAPICall('GET', 'admin/api/turma-frequencia.php', null, [
        'turma_id' => $dadosTeste['turma_id']
    ]);
    
    if ($resultado['success'] && isset($resultado['data']['estatisticas_gerais'])) {
        $stats = $resultado['data']['estatisticas_gerais'];
        echo "   ✅ PASSOU - Total alunos: " . $stats['total_alunos'] . ", Frequência média: " . $stats['frequencia_media'] . "%\n";
        $testes['passaram']++;
    } else {
        echo "   ❌ FALHOU: " . ($resultado['message'] ?? 'Dados não encontrados') . "\n";
        $testes['falharam']++;
    }
    
    // Teste 3: Listar frequências
    echo "   Teste 3: Listar frequências...\n";
    $testes['total']++;
    
    $resultado = simularAPICall('GET', 'admin/api/turma-frequencia.php', null);
    
    if ($resultado['success'] && is_array($resultado['data'])) {
        echo "   ✅ PASSOU - " . count($resultado['data']) . " turmas encontradas\n";
        $testes['passaram']++;
    } else {
        echo "   ❌ FALHOU: " . ($resultado['message'] ?? 'Dados não encontrados') . "\n";
        $testes['falharam']++;
    }
    
    return $testes;
}

/**
 * Simular chamada de API
 */
function simularAPICall($method, $endpoint, $data = null, $params = []) {
    // Simular variáveis de ambiente
    $_SERVER['REQUEST_METHOD'] = $method;
    $_GET = $params;
    $_SESSION['user_id'] = 1;
    $_SESSION['user_type'] = 'admin';
    
    // Capturar output
    ob_start();
    
    try {
        // Incluir o arquivo da API
        if ($method === 'POST' || $method === 'PUT') {
            // Simular input JSON
            $GLOBALS['json_input'] = json_encode($data);
        }
        
        include $endpoint;
        
        $output = ob_get_clean();
        return json_decode($output, true);
        
    } catch (Exception $e) {
        ob_end_clean();
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Limpar dados de teste
 */
function limparDadosTeste($db, $dadosTeste) {
    try {
        $db->beginTransaction();
        
        // Excluir presenças
        $db->query("DELETE FROM turma_presencas WHERE turma_id = ?", [$dadosTeste['turma_id']]);
        
        // Excluir matrículas
        $db->query("DELETE FROM turma_alunos WHERE turma_id = ?", [$dadosTeste['turma_id']]);
        
        // Excluir aulas
        $db->query("DELETE FROM turma_aulas WHERE turma_id = ?", [$dadosTeste['turma_id']]);
        
        // Excluir turma
        $db->query("DELETE FROM turmas WHERE id = ?", [$dadosTeste['turma_id']]);
        
        // Excluir alunos de teste
        foreach ($dadosTeste['alunos'] as $aluno) {
            $db->query("DELETE FROM alunos WHERE id = ?", [$aluno['id']]);
        }
        
        $db->commit();
        
    } catch (Exception $e) {
        $db->rollback();
        echo "⚠️  Erro ao limpar dados de teste: " . $e->getMessage() . "\n";
    }
}

echo "\n🎯 PRÓXIMA ETAPA: 1.3 - Interface de Chamada\n";
echo "📁 Arquivo: admin/pages/turma-chamada.php\n";
echo "⏰ Estimativa: 3 dias\n";
?>
