<?php
/**
 * Teste de Validação de Exames para Matrícula em Turmas
 * 
 * Este script testa diferentes cenários de validação de exames
 * antes de permitir a matrícula de alunos em turmas.
 * 
 * @author Sistema CFC Bom Conselho
 * @version 1.0
 * @since 2024
 */

// Incluir dependências
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/turma_manager.php';

// Verificar se é ambiente de teste/desenvolvimento
if (!defined('TEST_ENVIRONMENT') && !isset($_GET['test'])) {
    die('Este script só pode ser executado em ambiente de teste.');
}

$db = Database::getInstance();
$turmaManager = new TurmaManager();

echo "<h1>🧪 Teste de Validação de Exames para Turmas</h1>";
echo "<hr>";

// Cenários de teste
$cenariosTeste = [
    [
        'nome' => 'Aluno com ambos exames aprovados',
        'exame_medico' => 'apto',
        'exame_psicologico' => 'apto',
        'esperado' => true
    ],
    [
        'nome' => 'Aluno com exame médico aprovado e psicológico aprovado (variação)',
        'exame_medico' => 'aprovado',
        'exame_psicologico' => 'aprovado',
        'esperado' => true
    ],
    [
        'nome' => 'Aluno com exame médico pendente',
        'exame_medico' => 'pendente',
        'exame_psicologico' => 'apto',
        'esperado' => false
    ],
    [
        'nome' => 'Aluno com exame psicológico pendente',
        'exame_medico' => 'apto',
        'exame_psicologico' => 'pendente',
        'esperado' => false
    ],
    [
        'nome' => 'Aluno com exame médico reprovado',
        'exame_medico' => 'inapto',
        'exame_psicologico' => 'apto',
        'esperado' => false
    ],
    [
        'nome' => 'Aluno com exame psicológico reprovado',
        'exame_medico' => 'apto',
        'exame_psicologico' => 'inapto',
        'esperado' => false
    ],
    [
        'nome' => 'Aluno com ambos exames não realizados',
        'exame_medico' => null,
        'exame_psicologico' => null,
        'esperado' => false
    ],
    [
        'nome' => 'Aluno com exame médico temporariamente inapto',
        'exame_medico' => 'inapto_temporario',
        'exame_psicologico' => 'apto',
        'esperado' => false
    ]
];

// Função para criar aluno de teste
function criarAlunoTeste($db, $nome, $exameMedico, $examePsicologico) {
    try {
        $alunoId = $db->insert('alunos', [
            'nome' => $nome,
            'cpf' => '000.000.000-' . rand(10, 99),
            'email' => strtolower(str_replace(' ', '', $nome)) . '@teste.com',
            'telefone' => '(87) 99999-' . rand(1000, 9999),
            'categoria_cnh' => 'B',
            'status' => 'ativo',
            'exame_medico' => $exameMedico,
            'exame_psicologico' => $examePsicologico,
            'data_exame_medico' => $exameMedico ? date('Y-m-d') : null,
            'data_exame_psicologico' => $examePsicologico ? date('Y-m-d') : null,
            'cfc_id' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return $alunoId;
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ Erro ao criar aluno teste: " . $e->getMessage() . "</div>";
        return false;
    }
}

// Função para criar turma de teste
function criarTurmaTeste($db, $turmaManager) {
    try {
        $dadosTurma = [
            'nome' => 'Turma Teste - Validação Exames',
            'instrutor_id' => 1, // Assumindo que existe instrutor com ID 1
            'tipo_aula' => 'teorica',
            'categoria_cnh' => 'B',
            'data_inicio' => date('Y-m-d'),
            'data_fim' => date('Y-m-d', strtotime('+30 days')),
            'status' => 'ativa',
            'cfc_id' => 1
        ];
        
        $resultado = $turmaManager->criarTurma($dadosTurma);
        if ($resultado['sucesso']) {
            return $resultado['turma_id'];
        }
        
        return false;
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ Erro ao criar turma teste: " . $e->getMessage() . "</div>";
        return false;
    }
}

// Função para limpar dados de teste
function limparDadosTeste($db, $alunosIds, $turmaId) {
    try {
        // Remover matrículas de teste
        foreach ($alunosIds as $alunoId) {
            $db->query("DELETE FROM turma_alunos WHERE aluno_id = ?", [$alunoId]);
        }
        
        // Remover alunos de teste
        foreach ($alunosIds as $alunoId) {
            $db->query("DELETE FROM alunos WHERE id = ?", [$alunoId]);
        }
        
        // Remover turma de teste
        if ($turmaId) {
            $db->query("DELETE FROM turma_aulas WHERE turma_id = ?", [$turmaId]);
            $db->query("DELETE FROM turmas WHERE id = ?", [$turmaId]);
        }
        
        echo "<div style='color: green;'>🧹 Dados de teste removidos com sucesso.</div>";
    } catch (Exception $e) {
        echo "<div style='color: orange;'>⚠️ Aviso: Erro ao limpar dados de teste: " . $e->getMessage() . "</div>";
    }
}

// Executar testes
echo "<h2>📋 Executando Testes...</h2>";

$turmaId = criarTurmaTeste($db, $turmaManager);
if (!$turmaId) {
    die("<div style='color: red;'>❌ Não foi possível criar turma de teste. Abortando.</div>");
}

echo "<div style='color: blue;'>📚 Turma de teste criada: ID $turmaId</div><br>";

$alunosIds = [];
$totalTestes = count($cenariosTeste);
$testesPassaram = 0;

foreach ($cenariosTeste as $index => $cenario) {
    echo "<h3>🧪 Teste " . ($index + 1) . ": {$cenario['nome']}</h3>";
    
    // Criar aluno para o cenário
    $nomeAluno = "Aluno Teste " . ($index + 1);
    $alunoId = criarAlunoTeste($db, $nomeAluno, $cenario['exame_medico'], $cenario['exame_psicologico']);
    
    if (!$alunoId) {
        echo "<div style='color: red;'>❌ Erro ao criar aluno. Pulando teste.</div><br>";
        continue;
    }
    
    $alunosIds[] = $alunoId;
    
    echo "<div style='color: blue;'>👤 Aluno criado: $nomeAluno (ID: $alunoId)</div>";
    echo "<div style='margin-left: 20px;'>";
    echo "• Exame médico: " . ($cenario['exame_medico'] ?: 'não realizado') . "<br>";
    echo "• Exame psicológico: " . ($cenario['exame_psicologico'] ?: 'não realizado') . "<br>";
    echo "</div>";
    
    // Testar matrícula
    $resultado = $turmaManager->matricularAluno($turmaId, $alunoId);
    
    $sucesso = $resultado['sucesso'];
    $esperado = $cenario['esperado'];
    
    echo "<div style='margin-left: 20px;'>";
    echo "<strong>Resultado:</strong> " . ($sucesso ? 'Permitiu matrícula' : 'Bloqueou matrícula') . "<br>";
    echo "<strong>Esperado:</strong> " . ($esperado ? 'Permitir matrícula' : 'Bloquear matrícula') . "<br>";
    
    if ($sucesso === $esperado) {
        echo "<div style='color: green;'>✅ TESTE PASSOU</div>";
        $testesPassaram++;
    } else {
        echo "<div style='color: red;'>❌ TESTE FALHOU</div>";
    }
    
    if (!$sucesso) {
        echo "<div style='background: #f8f9fa; padding: 10px; margin: 10px 0; border-left: 3px solid #007bff;'>";
        echo "<strong>Mensagem retornada:</strong><br>";
        echo nl2br(htmlspecialchars($resultado['mensagem']));
        echo "</div>";
    }
    
    echo "</div><hr>";
}

// Resumo dos testes
echo "<h2>📊 Resumo dos Testes</h2>";
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px;'>";
echo "<strong>Total de testes:</strong> $totalTestes<br>";
echo "<strong>Testes que passaram:</strong> $testesPassaram<br>";
echo "<strong>Testes que falharam:</strong> " . ($totalTestes - $testesPassaram) . "<br>";

$percentual = ($testesPassaram / $totalTestes) * 100;
$cor = $percentual == 100 ? 'green' : ($percentual >= 80 ? 'orange' : 'red');

echo "<strong style='color: $cor;'>Taxa de sucesso: " . number_format($percentual, 1) . "%</strong><br>";
echo "</div>";

if ($testesPassaram === $totalTestes) {
    echo "<div style='color: green; font-size: 18px; margin: 20px 0;'>🎉 Todos os testes passaram! A validação está funcionando corretamente.</div>";
} else {
    echo "<div style='color: red; font-size: 18px; margin: 20px 0;'>⚠️ Alguns testes falharam. Verifique a implementação.</div>";
}

// Testar também via API
echo "<h2>🌐 Testando via API</h2>";
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 8px;'>";
echo "<strong>Exemplo de uso da API:</strong><br><br>";
echo "<code>POST /admin/api/turmas.php</code><br>";
echo "<pre>";
echo json_encode([
    'acao' => 'matricular_aluno',
    'turma_id' => $turmaId,
    'aluno_id' => $alunosIds[0] ?? 'ID_DO_ALUNO'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "</pre>";
echo "</div>";

// Limpar dados de teste
echo "<h2>🧹 Limpeza</h2>";
limparDadosTeste($db, $alunosIds, $turmaId);

echo "<div style='color: green; font-size: 16px; margin: 20px 0;'>✅ Teste concluído!</div>";
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}

h1, h2, h3 {
    color: #333;
}

hr {
    border: none;
    height: 1px;
    background: #ddd;
    margin: 20px 0;
}

code {
    background: #f4f4f4;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
}

pre {
    background: #f4f4f4;
    padding: 15px;
    border-radius: 8px;
    overflow-x: auto;
    font-family: 'Courier New', monospace;
}
</style>
