<?php
/**
 * TESTE SIMPLES DA API DE AGENDAMENTO
 * 
 * Este script testa se a API de agendamento está funcionando corretamente
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

echo "<h1>🧪 TESTE DA API DE AGENDAMENTO</h1>\n";
echo "<hr>\n";

try {
    $db = db();
    
    echo "<h2>1. Testando conexão com banco de dados...</h2>\n";
    $teste_conexao = $db->fetchColumn("SELECT 1");
    echo "✅ Conexão com banco: OK\n";
    echo "<br>\n";
    
    echo "<h2>2. Verificando dados necessários...</h2>\n";
    
    // Verificar se existem alunos
    $total_alunos = $db->fetchColumn("SELECT COUNT(*) FROM alunos WHERE ativo = 1");
    echo "📊 Total de alunos ativos: {$total_alunos}\n";
    
    // Verificar se existem instrutores
    $total_instrutores = $db->fetchColumn("SELECT COUNT(*) FROM instrutores WHERE ativo = 1");
    echo "📊 Total de instrutores ativos: {$total_instrutores}\n";
    
    // Verificar se existem veículos
    $total_veiculos = $db->fetchColumn("SELECT COUNT(*) FROM veiculos WHERE ativo = 1");
    echo "📊 Total de veículos ativos: {$total_veiculos}\n";
    
    // Verificar se existem aulas
    $total_aulas = $db->fetchColumn("SELECT COUNT(*) FROM aulas");
    echo "📊 Total de aulas no sistema: {$total_aulas}\n";
    
    echo "<br>\n";
    
    if ($total_alunos == 0 || $total_instrutores == 0 || $total_veiculos == 0) {
        echo "<div style='color: red; font-weight: bold;'>❌ DADOS INSUFICIENTES PARA TESTE</div>\n";
        echo "<p>É necessário ter pelo menos:</p>\n";
        echo "<ul>\n";
        echo "<li>1 aluno ativo</li>\n";
        echo "<li>1 instrutor ativo</li>\n";
        echo "<li>1 veículo ativo</li>\n";
        echo "</ul>\n";
    } else {
        echo "<div style='color: green; font-weight: bold;'>✅ DADOS SUFICIENTES PARA TESTE</div>\n";
        echo "<br>\n";
        
        echo "<h2>3. Testando função calcularHorariosAulas...</h2>\n";
        
        // Simular dados de teste
        $hora_inicio = '14:00';
        $tipo_agendamento = 'unica';
        $posicao_intervalo = 'depois';
        
        echo "🕐 Testando com: {$hora_inicio}, {$tipo_agendamento}, {$posicao_intervalo}\n";
        
        // Incluir a função calcularHorariosAulas
        require_once __DIR__ . '/admin/api/agendamento.php';
        
        try {
            $horarios = calcularHorariosAulas($hora_inicio, $tipo_agendamento, $posicao_intervalo);
            echo "✅ Função calcularHorariosAulas: OK\n";
            echo "📅 Horários calculados: " . json_encode($horarios) . "\n";
        } catch (Exception $e) {
            echo "❌ Erro na função calcularHorariosAulas: " . $e->getMessage() . "\n";
        }
        
        echo "<br>\n";
        
        echo "<h2>4. Testando função verificarLimiteDiarioAluno...</h2>\n";
        
        // Pegar um aluno para teste
        $aluno_teste = $db->fetch("SELECT id FROM alunos WHERE ativo = 1 LIMIT 1");
        if ($aluno_teste) {
            $aluno_id = $aluno_teste['id'];
            $data_teste = date('Y-m-d');
            
            echo "👤 Testando com aluno ID: {$aluno_id}, data: {$data_teste}\n";
            
            try {
                $limite = verificarLimiteDiarioAluno($db, $aluno_id, $data_teste, 1);
                echo "✅ Função verificarLimiteDiarioAluno: OK\n";
                echo "📊 Resultado: " . json_encode($limite) . "\n";
            } catch (Exception $e) {
                echo "❌ Erro na função verificarLimiteDiarioAluno: " . $e->getMessage() . "\n";
            }
        }
        
        echo "<br>\n";
        
        echo "<h2>5. Testando busca de aulas...</h2>\n";
        
        try {
            $aulas = $db->fetchAll("
                SELECT a.*, 
                       al.nome as aluno_nome,
                       COALESCE(u.nome, i.nome) as instrutor_nome,
                       v.placa, v.modelo, v.marca
                FROM aulas a
                JOIN alunos al ON a.aluno_id = al.id
                JOIN instrutores i ON a.instrutor_id = i.id
                LEFT JOIN usuarios u ON i.usuario_id = u.id
                LEFT JOIN veiculos v ON a.veiculo_id = v.id
                WHERE a.data_aula >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                  AND a.data_aula <= DATE_ADD(CURDATE(), INTERVAL 6 MONTH)
                ORDER BY a.data_aula, a.hora_inicio
                LIMIT 5
            ");
            
            echo "✅ Query de busca de aulas: OK\n";
            echo "📊 Aulas encontradas: " . count($aulas) . "\n";
            
            if (count($aulas) > 0) {
                echo "📅 Primeira aula: " . json_encode($aulas[0]) . "\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Erro na query de busca de aulas: " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "<h2>❌ ERRO GERAL</h2>\n";
    echo "<div style='color: red; background-color: #f8d7da; padding: 10px; border-radius: 5px;'>\n";
    echo "<strong>Erro:</strong> " . $e->getMessage() . "\n";
    echo "<br><br>\n";
    echo "<strong>Stack trace:</strong>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
    echo "</div>\n";
}

echo "<hr>\n";
echo "<p><strong>Teste executado em:</strong> " . date('d/m/Y H:i:s') . "</p>\n";
echo "<p><strong>Sistema CFC - Bom Conselho</strong></p>\n";
?>
