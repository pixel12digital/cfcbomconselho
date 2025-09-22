<?php
/**
 * Script de Teste Manual - ETAPA 1.3: Interface de Chamada
 * Sistema de Turmas Teóricas - CFC Bom Conselho
 * 
 * Este script cria dados de teste para validar a interface de chamada
 * seguindo o roteiro de testes manuais fornecido.
 */

require_once 'includes/config.php';
require_once 'includes/database.php';

echo "🧪 CRIANDO DADOS DE TESTE - ETAPA 1.3: INTERFACE DE CHAMADA\n";
echo "============================================================\n";

$db = Database::getInstance();

try {
    // 1. Buscar dados básicos existentes
    echo "1. Buscando dados básicos...\n";
    $usuario = $db->fetch("SELECT id FROM usuarios LIMIT 1");
    $instrutor = $db->fetch("SELECT id FROM instrutores LIMIT 1");
    $cfc = $db->fetch("SELECT id FROM cfcs LIMIT 1");
    
    if (!$usuario || !$instrutor || !$cfc) {
        throw new Exception("Dados básicos não encontrados");
    }
    
    echo "   ✅ Usuário ID: " . $usuario['id'] . "\n";
    echo "   ✅ Instrutor ID: " . $instrutor['id'] . "\n";
    echo "   ✅ CFC ID: " . $cfc['id'] . "\n";
    
    // 2. Criar turma de teste conforme roteiro
    echo "\n2. Criando turma de teste...\n";
    $turmaId = $db->insert('turmas', [
        'nome' => 'Teórico AB – Turno Noite',
        'instrutor_id' => $instrutor['id'],
        'tipo_aula' => 'teorica',
        'categoria_cnh' => 'AB',
        'data_inicio' => date('Y-m-d'),
        'data_fim' => date('Y-m-d', strtotime('+30 days')),
        'status' => 'ativo',
        'capacidade_maxima' => 25,
        'frequencia_minima' => 90.00,
        'sala_local' => 'Sala 1',
        'cfc_id' => $cfc['id']
    ]);
    
    echo "   ✅ Turma criada (ID: $turmaId)\n";
    
    // 3. Criar aulas de teste
    echo "\n3. Criando aulas de teste...\n";
    $aulas = [];
    
    // Aula de hoje (19:00-21:30 = 3 slots de 50min)
    $aulaId1 = $db->insert('turma_aulas', [
        'turma_id' => $turmaId,
        'ordem' => 1,
        'nome_aula' => 'Legislação de Trânsito - Parte 1',
        'duracao_minutos' => 50,
        'data_aula' => date('Y-m-d'),
        'tipo_conteudo' => 'legislacao',
        'status' => 'agendada'
    ]);
    $aulas[] = $aulaId1;
    
    $aulaId2 = $db->insert('turma_aulas', [
        'turma_id' => $turmaId,
        'ordem' => 2,
        'nome_aula' => 'Legislação de Trânsito - Parte 2',
        'duracao_minutos' => 50,
        'data_aula' => date('Y-m-d'),
        'tipo_conteudo' => 'legislacao',
        'status' => 'agendada'
    ]);
    $aulas[] = $aulaId2;
    
    $aulaId3 = $db->insert('turma_aulas', [
        'turma_id' => $turmaId,
        'ordem' => 3,
        'nome_aula' => 'Legislação de Trânsito - Parte 3',
        'duracao_minutos' => 50,
        'data_aula' => date('Y-m-d'),
        'tipo_conteudo' => 'legislacao',
        'status' => 'agendada'
    ]);
    $aulas[] = $aulaId3;
    
    echo "   ✅ 3 aulas criadas (IDs: " . implode(', ', $aulas) . ")\n";
    
    // 4. Criar alunos de teste (10-15 alunos)
    echo "\n4. Criando alunos de teste...\n";
    $alunos = [];
    
    for ($i = 1; $i <= 12; $i++) {
        $alunoId = $db->insert('alunos', [
            'nome' => "Aluno Teste Chamada $i",
            'cpf' => "000.000." . str_pad(time() % 1000, 3, '0', STR_PAD_LEFT) . "_$i",
            'categoria_cnh' => 'AB',
            'cfc_id' => $cfc['id'],
            'status' => 'ativo'
        ]);
        
        // Matricular na turma
        $db->insert('turma_alunos', [
            'turma_id' => $turmaId,
            'aluno_id' => $alunoId,
            'status' => $i <= 10 ? 'ativo' : 'matriculado'
        ]);
        
        $alunos[] = $alunoId;
    }
    
    echo "   ✅ 12 alunos criados e matriculados\n";
    
    // 5. Criar histórico de presenças para alguns alunos (para testar frequência)
    echo "\n5. Criando histórico de presenças...\n";
    
    // Simular algumas aulas anteriores com presenças
    $aulasAnteriores = [
        ['nome' => 'Aula Anterior 1', 'data' => date('Y-m-d', strtotime('-3 days'))],
        ['nome' => 'Aula Anterior 2', 'data' => date('Y-m-d', strtotime('-2 days'))],
        ['nome' => 'Aula Anterior 3', 'data' => date('Y-m-d', strtotime('-1 day'))]
    ];
    
    foreach ($aulasAnteriores as $index => $aulaAnterior) {
        $aulaAnteriorId = $db->insert('turma_aulas', [
            'turma_id' => $turmaId,
            'ordem' => $index + 1,
            'nome_aula' => $aulaAnterior['nome'],
            'duracao_minutos' => 50,
            'data_aula' => $aulaAnterior['data'],
            'tipo_conteudo' => 'legislacao',
            'status' => 'concluida'
        ]);
        
        // Marcar presenças para alguns alunos (simular histórico)
        foreach ($alunos as $alunoIndex => $alunoId) {
            $presente = true;
            
            // Alunos 1-2: sempre presentes (frequência alta)
            if ($alunoIndex < 2) {
                $presente = true;
            }
            // Alunos 3-4: às vezes ausentes (frequência média)
            elseif ($alunoIndex < 4) {
                $presente = ($index + $alunoIndex) % 2 == 0;
            }
            // Alunos 5-6: frequentemente ausentes (frequência baixa)
            elseif ($alunoIndex < 6) {
                $presente = $index == 0; // só presente na primeira aula
            }
            // Resto: presença normal
            else {
                $presente = ($index + $alunoIndex) % 3 != 0;
            }
            
            $db->insert('turma_presencas', [
                'turma_id' => $turmaId,
                'turma_aula_id' => $aulaAnteriorId,
                'aluno_id' => $alunoId,
                'presente' => $presente ? 1 : 0,
                'observacao' => $presente ? 'Presente' : 'Falta',
                'registrado_por' => $usuario['id']
            ]);
        }
    }
    
    echo "   ✅ Histórico de presenças criado (3 aulas anteriores)\n";
    
    // 6. Criar algumas presenças para a aula atual (para testar edição)
    echo "\n6. Criando presenças iniciais para aula atual...\n";
    
    // Marcar presença para alguns alunos na primeira aula de hoje
    for ($i = 0; $i < 5; $i++) {
        $db->insert('turma_presencas', [
            'turma_id' => $turmaId,
            'turma_aula_id' => $aulaId1,
            'aluno_id' => $alunos[$i],
            'presente' => 1,
            'observacao' => 'Presente',
            'registrado_por' => $usuario['id']
        ]);
    }
    
    echo "   ✅ 5 presenças iniciais criadas\n";
    
    // 7. Relatório final
    echo "\n============================================================\n";
    echo "📊 DADOS DE TESTE CRIADOS COM SUCESSO\n";
    echo "============================================================\n";
    echo "✅ Turma: Teórico AB – Turno Noite (ID: $turmaId)\n";
    echo "   - Capacidade: 25 alunos\n";
    echo "   - Frequência mínima: 90%\n";
    echo "   - Status: Ativo\n";
    echo "   - Período: " . date('d/m/Y') . " a " . date('d/m/Y', strtotime('+30 days')) . "\n";
    echo "\n✅ Aulas criadas:\n";
    echo "   - Aula 1: Legislação de Trânsito - Parte 1 (ID: $aulaId1)\n";
    echo "   - Aula 2: Legislação de Trânsito - Parte 2 (ID: $aulaId2)\n";
    echo "   - Aula 3: Legislação de Trânsito - Parte 3 (ID: $aulaId3)\n";
    echo "   - 3 aulas anteriores com histórico de presenças\n";
    echo "\n✅ Alunos matriculados: 12\n";
    echo "   - 10 alunos com status 'ativo'\n";
    echo "   - 2 alunos com status 'matriculado'\n";
    echo "   - Histórico de frequência variado para testar badges\n";
    echo "\n✅ Presenças iniciais:\n";
    echo "   - 5 alunos já marcados como presentes na primeira aula\n";
    echo "   - Histórico de 3 aulas anteriores para cálculo de frequência\n";
    
    echo "\n🎯 PRÓXIMOS PASSOS PARA TESTE:\n";
    echo "1. Acesse: /admin/pages/turma-chamada.php?turma_id=$turmaId&aula_id=$aulaId1\n";
    echo "2. Teste marcação individual de presença\n";
    echo "3. Teste marcação em lote\n";
    echo "4. Teste edição/correção de presenças\n";
    echo "5. Teste navegação entre aulas\n";
    echo "6. Verifique cálculos de frequência\n";
    echo "7. Teste validações de regra\n";
    echo "8. Verifique UX e feedbacks\n";
    
    echo "\n📋 CHECKLIST DE TESTES:\n";
    echo "□ Abertura da tela carrega corretamente\n";
    echo "□ Lista de alunos exibe nome + status\n";
    echo "□ Check de presença funciona\n";
    echo "□ Campo observação disponível\n";
    echo "□ Indicador de % frequência por aluno\n";
    echo "□ Indicador de % frequência da turma\n";
    echo "□ Marcação individual salva corretamente\n";
    echo "□ Feedback visual (toast/sucesso)\n";
    echo "□ % do aluno atualiza em tempo real\n";
    echo "□ Média da turma atualiza em tempo real\n";
    echo "□ Marcação em lote funciona\n";
    echo "□ Sem duplicidade em lote\n";
    echo "□ Ausências refletidas corretamente\n";
    echo "□ Edição de presença funciona\n";
    echo "□ Auditoria registrada\n";
    echo "□ Navegação entre aulas funciona\n";
    echo "□ Dados corretos por aula\n";
    echo "□ Sem vazamento entre aulas\n";
    echo "□ Validações de regra funcionam\n";
    echo "□ Bloqueios funcionam\n";
    echo "□ UX com feedbacks claros\n";
    echo "□ Performance aceitável\n";
    echo "□ Acessibilidade básica\n";
    
    echo "\n🔗 URLs PARA TESTE:\n";
    echo "Interface de chamada: /admin/pages/turma-chamada.php?turma_id=$turmaId&aula_id=$aulaId1\n";
    echo "API de presenças: /admin/api/turma-presencas.php?turma_id=$turmaId&aula_id=$aulaId1\n";
    echo "API de frequência: /admin/api/turma-frequencia.php?turma_id=$turmaId\n";
    
} catch (Exception $e) {
    echo "❌ ERRO CRÍTICO: " . $e->getMessage() . "\n";
    echo "📞 Contate o suporte técnico\n";
    exit(1);
}

echo "\n🎉 DADOS DE TESTE CRIADOS COM SUCESSO!\n";
echo "📝 Execute os testes manuais conforme o roteiro fornecido\n";
?>
