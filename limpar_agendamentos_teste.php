<?php
/**
 * SCRIPT DE LIMPEZA - AGENDAMENTOS DE TESTE
 * 
 * Este script remove todos os agendamentos de teste dos alunos ID 112 e 111
 * 
 * @author Sistema CFC
 * @version 1.0
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

echo "<h1>🧹 LIMPEZA DE AGENDAMENTOS DE TESTE</h1>\n";
echo "<hr>\n";

try {
    $db = db();
    
    // IDs dos alunos para limpeza
    $alunos_teste = [112, 111];
    
    echo "<h2>📋 Alunos para limpeza:</h2>\n";
    foreach ($alunos_teste as $aluno_id) {
        echo "• Aluno ID: {$aluno_id}\n";
    }
    echo "<br>\n";
    
    // 1. Verificar agendamentos existentes
    echo "<h2>1. Verificando agendamentos existentes...</h2>\n";
    
    $total_agendamentos = 0;
    foreach ($alunos_teste as $aluno_id) {
        $count = $db->fetchColumn("SELECT COUNT(*) FROM aulas WHERE aluno_id = ?", [$aluno_id]);
        $total_agendamentos += $count;
        
        echo "✓ Aluno ID {$aluno_id}: {$count} agendamentos encontrados\n";
        
        if ($count > 0) {
            // Mostrar detalhes dos agendamentos
            $agendamentos = $db->fetchAll("SELECT id, data_aula, hora_inicio, hora_fim, tipo_aula, status, observacoes 
                                          FROM aulas 
                                          WHERE aluno_id = ? 
                                          ORDER BY data_aula, hora_inicio", [$aluno_id]);
            
            echo "  📅 Detalhes dos agendamentos:\n";
            foreach ($agendamentos as $agendamento) {
                $obs = $agendamento['observacoes'] ? " ({$agendamento['observacoes']})" : "";
                echo "    - ID {$agendamento['id']}: {$agendamento['data_aula']} {$agendamento['hora_inicio']}-{$agendamento['hora_fim']} ({$agendamento['tipo_aula']}) - {$agendamento['status']}{$obs}\n";
            }
        }
    }
    
    echo "<br>\n";
    echo "<strong>Total de agendamentos encontrados: {$total_agendamentos}</strong>\n";
    echo "<br>\n";
    
    if ($total_agendamentos === 0) {
        echo "<div style='color: green; font-weight: bold;'>✅ Nenhum agendamento de teste encontrado!</div>\n";
        echo "<br>\n";
    } else {
        // 2. Confirmar exclusão
        echo "<h2>2. Confirmação de exclusão</h2>\n";
        echo "<div style='background-color: #fff3cd; padding: 10px; border: 1px solid #ffeaa7; border-radius: 5px;'>\n";
        echo "<strong>⚠️ ATENÇÃO:</strong> Esta operação irá excluir <strong>{$total_agendamentos} agendamentos</strong> dos alunos de teste.\n";
        echo "<br><br>\n";
        echo "<strong>Alunos afetados:</strong>\n";
        foreach ($alunos_teste as $aluno_id) {
            echo "• Aluno ID: {$aluno_id}\n";
        }
        echo "</div>\n";
        echo "<br>\n";
        
        // 3. Executar exclusão
        echo "<h2>3. Executando exclusão...</h2>\n";
        
        $agendamentos_excluidos = 0;
        foreach ($alunos_teste as $aluno_id) {
            // Buscar agendamentos para log
            $agendamentos = $db->fetchAll("SELECT * FROM aulas WHERE aluno_id = ?", [$aluno_id]);
            
            // Excluir agendamentos
            $resultado = $db->delete("aulas", "aluno_id = ?", [$aluno_id]);
            
            if ($resultado) {
                $count_excluidos = count($agendamentos);
                $agendamentos_excluidos += $count_excluidos;
                echo "✅ Aluno ID {$aluno_id}: {$count_excluidos} agendamentos excluídos\n";
                
                // Log detalhado
                foreach ($agendamentos as $agendamento) {
                    $obs = $agendamento['observacoes'] ? " ({$agendamento['observacoes']})" : "";
                    echo "  🗑️ Excluído: ID {$agendamento['id']} - {$agendamento['data_aula']} {$agendamento['hora_inicio']}-{$agendamento['hora_fim']} ({$agendamento['tipo_aula']}){$obs}\n";
                }
            } else {
                echo "❌ Erro ao excluir agendamentos do aluno ID {$aluno_id}\n";
            }
        }
        
        echo "<br>\n";
        echo "<div style='color: green; font-weight: bold;'>✅ Total de agendamentos excluídos: {$agendamentos_excluidos}</div>\n";
        echo "<br>\n";
        
        // 4. Verificação final
        echo "<h2>4. Verificação final...</h2>\n";
        
        $total_restante = 0;
        foreach ($alunos_teste as $aluno_id) {
            $count_restante = $db->fetchColumn("SELECT COUNT(*) FROM aulas WHERE aluno_id = ?", [$aluno_id]);
            $total_restante += $count_restante;
            
            if ($count_restante === 0) {
                echo "✅ Aluno ID {$aluno_id}: Nenhum agendamento restante\n";
            } else {
                echo "⚠️ Aluno ID {$aluno_id}: {$count_restante} agendamentos ainda existem\n";
            }
        }
        
        if ($total_restante === 0) {
            echo "<br>\n";
            echo "<div style='color: green; font-weight: bold; background-color: #d4edda; padding: 10px; border-radius: 5px;'>\n";
            echo "🎉 LIMPEZA CONCLUÍDA COM SUCESSO!\n";
            echo "<br>\n";
            echo "Todos os agendamentos de teste foram removidos.\n";
            echo "</div>\n";
        } else {
            echo "<br>\n";
            echo "<div style='color: orange; font-weight: bold; background-color: #fff3cd; padding: 10px; border-radius: 5px;'>\n";
            echo "⚠️ ATENÇÃO: Ainda existem {$total_restante} agendamentos.\n";
            echo "<br>\n";
            echo "Verifique se há agendamentos que não são de teste.\n";
            echo "</div>\n";
        }
    }
    
    // 5. Estatísticas gerais
    echo "<h2>5. Estatísticas gerais do sistema</h2>\n";
    
    $total_geral = $db->fetchColumn("SELECT COUNT(*) FROM aulas");
    $status_aulas = $db->fetchAll("SELECT status, COUNT(*) as total FROM aulas GROUP BY status");
    
    echo "📊 <strong>Total de agendamentos no sistema:</strong> {$total_geral}\n";
    echo "<br>\n";
    echo "📈 <strong>Por status:</strong>\n";
    foreach ($status_aulas as $status) {
        echo "  • {$status['status']}: {$status['total']}\n";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ ERRO</h2>\n";
    echo "<div style='color: red; background-color: #f8d7da; padding: 10px; border-radius: 5px;'>\n";
    echo "<strong>Erro:</strong> " . $e->getMessage() . "\n";
    echo "<br><br>\n";
    echo "<strong>Verifique:</strong>\n";
    echo "<ul>\n";
    echo "<li>Se o banco de dados está configurado corretamente</li>\n";
    echo "<li>Se as tabelas existem</li>\n";
    echo "<li>Se há permissões de escrita</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
}

echo "<hr>\n";
echo "<p><strong>Script executado em:</strong> " . date('d/m/Y H:i:s') . "</p>\n";
echo "<p><strong>Sistema CFC - Bom Conselho</strong></p>\n";
?>
