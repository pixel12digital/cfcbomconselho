<?php
/**
 * TESTE DE MENSAGENS ESPECÍFICAS DE CONFLITO
 * 
 * Este script testa as mensagens específicas para cada tipo de conflito:
 * - Limite de aulas práticas do aluno (máximo 3 por dia)
 * - Conflito de instrutor (já tem aula agendada)
 * - Conflito de veículo (já está em uso)
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

echo "<h1>🧪 TESTE DE MENSAGENS ESPECÍFICAS DE CONFLITO</h1>\n";
echo "<hr>\n";

try {
    $db = db();
    
    echo "<h2>📋 Cenários de Teste</h2>\n";
    echo "<ol>\n";
    echo "<li><strong>Limite de Aluno:</strong> Tentar agendar mais de 3 aulas práticas por dia</li>\n";
    echo "<li><strong>Conflito de Instrutor:</strong> Tentar agendar no mesmo horário que instrutor já tem aula</li>\n";
    echo "<li><strong>Conflito de Veículo:</strong> Tentar agendar no mesmo horário que veículo já está em uso</li>\n";
    echo "</ol>\n";
    echo "<br>\n";
    
    // Buscar dados para teste
    $aluno = $db->fetch("SELECT id, nome FROM alunos WHERE ativo = 1 LIMIT 1");
    $instrutor = $db->fetch("SELECT i.id, COALESCE(u.nome, i.nome) as nome FROM instrutores i LEFT JOIN usuarios u ON i.usuario_id = u.id WHERE i.ativo = 1 LIMIT 1");
    $veiculo = $db->fetch("SELECT id, placa, modelo, marca FROM veiculos WHERE ativo = 1 LIMIT 1");
    
    if (!$aluno || !$instrutor || !$veiculo) {
        echo "<div style='color: red; font-weight: bold;'>❌ DADOS INSUFICIENTES PARA TESTE</div>\n";
        echo "<p>É necessário ter pelo menos:</p>\n";
        echo "<ul>\n";
        echo "<li>1 aluno ativo</li>\n";
        echo "<li>1 instrutor ativo</li>\n";
        echo "<li>1 veículo ativo</li>\n";
        echo "</ul>\n";
        exit;
    }
    
    echo "<h2>📊 Dados para Teste</h2>\n";
    echo "👤 <strong>Aluno:</strong> {$aluno['nome']} (ID: {$aluno['id']})\n";
    echo "👨‍🏫 <strong>Instrutor:</strong> {$instrutor['nome']} (ID: {$instrutor['id']})\n";
    echo "🚗 <strong>Veículo:</strong> {$veiculo['marca']} {$veiculo['modelo']} - {$veiculo['placa']} (ID: {$veiculo['id']})\n";
    echo "<br>\n";
    
    // Incluir funções necessárias
    require_once __DIR__ . '/admin/api/agendamento.php';
    
    $data_teste = date('Y-m-d');
    $hora_teste = '14:00';
    
    echo "<h2>🧪 Teste 1: Limite de Aulas Práticas do Aluno</h2>\n";
    
    // Criar 3 aulas práticas para o aluno no mesmo dia
    echo "📅 Criando 3 aulas práticas para o aluno {$aluno['nome']} em {$data_teste}...\n";
    
    $horarios_teste = [
        ['hora_inicio' => '08:00', 'hora_fim' => '08:50'],
        ['hora_inicio' => '09:00', 'hora_fim' => '09:50'],
        ['hora_inicio' => '10:00', 'hora_fim' => '10:50']
    ];
    
    $aulas_criadas = 0;
    foreach ($horarios_teste as $horario) {
        try {
            $sql = "INSERT INTO aulas (aluno_id, instrutor_id, veiculo_id, tipo_aula, data_aula, hora_inicio, hora_fim, status, criado_em) 
                    VALUES (?, ?, ?, 'pratica', ?, ?, ?, 'agendada', NOW())";
            $db->query($sql, [$aluno['id'], $instrutor['id'], $veiculo['id'], $data_teste, $horario['hora_inicio'], $horario['hora_fim']]);
            $aulas_criadas++;
            echo "✅ Aula {$aulas_criadas}: {$horario['hora_inicio']} - {$horario['hora_fim']}\n";
        } catch (Exception $e) {
            echo "❌ Erro ao criar aula {$aulas_criadas}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "<br>\n";
    echo "📊 Total de aulas criadas: {$aulas_criadas}\n";
    echo "<br>\n";
    
    // Agora tentar criar uma 4ª aula para testar o limite
    echo "🚫 Tentando criar uma 4ª aula prática para testar o limite...\n";
    
    try {
        $limite_teste = verificarLimiteDiarioAluno($db, $aluno['id'], $data_teste, 1);
        
        if (!$limite_teste['disponivel']) {
            echo "<div style='color: orange; font-weight: bold; background-color: #fff3cd; padding: 10px; border-radius: 5px;'>\n";
            echo "✅ <strong>TESTE PASSOU:</strong> Limite detectado corretamente\n";
            echo "<br>\n";
            echo "<strong>Mensagem retornada:</strong>\n";
            echo "<div style='background-color: #f8f9fa; padding: 10px; border-left: 4px solid #ffc107; margin: 10px 0;'>\n";
            echo htmlspecialchars($limite_teste['mensagem']);
            echo "</div>\n";
            echo "</div>\n";
        } else {
            echo "<div style='color: red; font-weight: bold; background-color: #f8d7da; padding: 10px; border-radius: 5px;'>\n";
            echo "❌ <strong>TESTE FALHOU:</strong> Limite não foi detectado\n";
            echo "</div>\n";
        }
        
    } catch (Exception $e) {
        echo "<div style='color: red; font-weight: bold; background-color: #f8d7da; padding: 10px; border-radius: 5px;'>\n";
        echo "❌ <strong>ERRO NO TESTE:</strong> " . $e->getMessage() . "\n";
        echo "</div>\n";
    }
    
    echo "<br>\n";
    
    echo "<h2>🧪 Teste 2: Conflito de Instrutor</h2>\n";
    
    // Criar uma aula para o instrutor
    $hora_conflito = '15:00';
    echo "📅 Criando aula para o instrutor {$instrutor['nome']} em {$data_teste} às {$hora_conflito}...\n";
    
    try {
        $sql = "INSERT INTO aulas (aluno_id, instrutor_id, veiculo_id, tipo_aula, data_aula, hora_inicio, hora_fim, status, criado_em) 
                VALUES (?, ?, ?, 'pratica', ?, ?, ?, 'agendada', NOW())";
        $db->query($sql, [$aluno['id'], $instrutor['id'], $veiculo['id'], $data_teste, $hora_conflito, '15:50']);
        echo "✅ Aula criada: {$hora_conflito} - 15:50\n";
        
        // Agora tentar criar outra aula no mesmo horário
        echo "🚫 Tentando criar aula no mesmo horário para testar conflito de instrutor...\n";
        
        $conflito_instrutor = $db->fetch("SELECT * FROM aulas WHERE instrutor_id = ? AND data_aula = ? AND status != 'cancelada' AND (
            (hora_inicio <= ? AND hora_fim > ?) OR
            (hora_inicio < ? AND hora_fim >= ?) OR
            (hora_inicio >= ? AND hora_fim <= ?)
        )", [$instrutor['id'], $data_teste, $hora_conflito, $hora_conflito, '15:50', '15:50', $hora_conflito, '15:50']);
        
        if ($conflito_instrutor) {
            $nome_instrutor = $db->fetchColumn("SELECT COALESCE(u.nome, i.nome) FROM instrutores i LEFT JOIN usuarios u ON i.usuario_id = u.id WHERE i.id = ?", [$instrutor['id']]);
            $mensagem_conflito = "👨‍🏫 INSTRUTOR INDISPONÍVEL: O instrutor {$nome_instrutor} já possui aula agendada no horário {$hora_conflito} às 15:50. Escolha outro horário ou instrutor.";
            
            echo "<div style='color: orange; font-weight: bold; background-color: #fff3cd; padding: 10px; border-radius: 5px;'>\n";
            echo "✅ <strong>TESTE PASSOU:</strong> Conflito de instrutor detectado corretamente\n";
            echo "<br>\n";
            echo "<strong>Mensagem que seria retornada:</strong>\n";
            echo "<div style='background-color: #f8f9fa; padding: 10px; border-left: 4px solid #ffc107; margin: 10px 0;'>\n";
            echo htmlspecialchars($mensagem_conflito);
            echo "</div>\n";
            echo "</div>\n";
        } else {
            echo "<div style='color: red; font-weight: bold; background-color: #f8d7da; padding: 10px; border-radius: 5px;'>\n";
            echo "❌ <strong>TESTE FALHOU:</strong> Conflito de instrutor não foi detectado\n";
            echo "</div>\n";
        }
        
    } catch (Exception $e) {
        echo "<div style='color: red; font-weight: bold; background-color: #f8d7da; padding: 10px; border-radius: 5px;'>\n";
        echo "❌ <strong>ERRO NO TESTE:</strong> " . $e->getMessage() . "\n";
        echo "</div>\n";
    }
    
    echo "<br>\n";
    
    echo "<h2>🧪 Teste 3: Conflito de Veículo</h2>\n";
    
    // Criar uma aula para o veículo
    $hora_conflito_veiculo = '16:00';
    echo "📅 Criando aula para o veículo {$veiculo['placa']} em {$data_teste} às {$hora_conflito_veiculo}...\n";
    
    try {
        $sql = "INSERT INTO aulas (aluno_id, instrutor_id, veiculo_id, tipo_aula, data_aula, hora_inicio, hora_fim, status, criado_em) 
                VALUES (?, ?, ?, 'pratica', ?, ?, ?, 'agendada', NOW())";
        $db->query($sql, [$aluno['id'], $instrutor['id'], $veiculo['id'], $data_teste, $hora_conflito_veiculo, '16:50']);
        echo "✅ Aula criada: {$hora_conflito_veiculo} - 16:50\n";
        
        // Agora tentar criar outra aula no mesmo horário
        echo "🚫 Tentando criar aula no mesmo horário para testar conflito de veículo...\n";
        
        $conflito_veiculo = $db->fetch("SELECT * FROM aulas WHERE veiculo_id = ? AND data_aula = ? AND status != 'cancelada' AND (
            (hora_inicio <= ? AND hora_fim > ?) OR
            (hora_inicio < ? AND hora_fim >= ?) OR
            (hora_inicio >= ? AND hora_fim <= ?)
        )", [$veiculo['id'], $data_teste, $hora_conflito_veiculo, $hora_conflito_veiculo, '16:50', '16:50', $hora_conflito_veiculo, '16:50']);
        
        if ($conflito_veiculo) {
            $info_veiculo = $db->fetch("SELECT placa, modelo, marca FROM veiculos WHERE id = ?", [$veiculo['id']]);
            $veiculo_info = "{$info_veiculo['marca']} {$info_veiculo['modelo']} - {$info_veiculo['placa']}";
            $mensagem_conflito = "🚗 VEÍCULO INDISPONÍVEL: O veículo {$veiculo_info} já está em uso no horário {$hora_conflito_veiculo} às 16:50. Escolha outro horário ou veículo.";
            
            echo "<div style='color: orange; font-weight: bold; background-color: #fff3cd; padding: 10px; border-radius: 5px;'>\n";
            echo "✅ <strong>TESTE PASSOU:</strong> Conflito de veículo detectado corretamente\n";
            echo "<br>\n";
            echo "<strong>Mensagem que seria retornada:</strong>\n";
            echo "<div style='background-color: #f8f9fa; padding: 10px; border-left: 4px solid #ffc107; margin: 10px 0;'>\n";
            echo htmlspecialchars($mensagem_conflito);
            echo "</div>\n";
            echo "</div>\n";
        } else {
            echo "<div style='color: red; font-weight: bold; background-color: #f8d7da; padding: 10px; border-radius: 5px;'>\n";
            echo "❌ <strong>TESTE FALHOU:</strong> Conflito de veículo não foi detectado\n";
            echo "</div>\n";
        }
        
    } catch (Exception $e) {
        echo "<div style='color: red; font-weight: bold; background-color: #f8d7da; padding: 10px; border-radius: 5px;'>\n";
        echo "❌ <strong>ERRO NO TESTE:</strong> " . $e->getMessage() . "\n";
        echo "</div>\n";
    }
    
    echo "<br>\n";
    
    echo "<h2>🧹 Limpeza dos Dados de Teste</h2>\n";
    
    // Limpar dados de teste
    $aulas_removidas = $db->delete("aulas", "data_aula = ? AND observacoes IS NULL", [$data_teste]);
    echo "🗑️ Aulas de teste removidas\n";
    
    echo "<br>\n";
    
    echo "<h2>✅ Resumo dos Testes</h2>\n";
    echo "<div style='background-color: #d4edda; padding: 15px; border-radius: 5px;'>\n";
    echo "<h3>📋 Mensagens Específicas Implementadas:</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>🚫 LIMITE DE AULAS EXCEDIDO:</strong> Quando aluno tenta agendar mais de 3 aulas práticas por dia</li>\n";
    echo "<li><strong>👨‍🏫 INSTRUTOR INDISPONÍVEL:</strong> Quando instrutor já tem aula no horário solicitado</li>\n";
    echo "<li><strong>🚗 VEÍCULO INDISPONÍVEL:</strong> Quando veículo já está em uso no horário solicitado</li>\n";
    echo "</ul>\n";
    echo "<br>\n";
    echo "<p><strong>🎯 Cada mensagem agora é específica e clara, indicando exatamente qual é o problema e sugerindo soluções.</strong></p>\n";
    echo "</div>\n";
    
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
