<?php
/**
 * SCRIPT PARA EXCLUSÃO DE AGENDAMENTOS DE TESTE
 * 
 * Este script remove APENAS os agendamentos (aulas) dos alunos de teste:
 * - ID 111 (Roberio)
 * - ID 112 (Jefferson)
 * 
 * MANTÉM os cadastros dos alunos e usuários intactos.
 * Remove apenas dados relacionados a agendamentos para limpar o histórico.
 */

// Configurações de segurança
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir arquivos necessários
require_once 'includes/config.php';
require_once 'includes/database.php';

// IDs dos alunos para exclusão de agendamentos
$alunos_agendamentos_ids = [111, 112];

echo "<h1>🗑️ Script de Exclusão de Agendamentos de Teste</h1>\n";
echo "<p><strong>IDs dos alunos:</strong> " . implode(', ', $alunos_agendamentos_ids) . "</p>\n";
echo "<p><strong>Objetivo:</strong> Excluir apenas agendamentos, mantendo cadastros dos alunos</p>\n";

try {
    $db = Database::getInstance();
    
    echo "<h2>📊 Verificação Prévia</h2>\n";
    
    // Verificar se os alunos existem
    foreach ($alunos_agendamentos_ids as $aluno_id) {
        $aluno = $db->fetch("SELECT id, nome, cpf FROM alunos WHERE id = ?", [$aluno_id]);
        if ($aluno) {
            echo "✅ Aluno encontrado: ID {$aluno_id} - {$aluno['nome']} (CPF: {$aluno['cpf']})<br>\n";
        } else {
            echo "❌ Aluno não encontrado: ID {$aluno_id}<br>\n";
        }
    }
    
    echo "<h2>🔍 Verificação de Agendamentos</h2>\n";
    
    // Verificar aulas vinculadas
    foreach ($alunos_agendamentos_ids as $aluno_id) {
        $aulas_count = $db->count('aulas', 'aluno_id = ?', [$aluno_id]);
        echo "📚 Aulas agendadas para aluno {$aluno_id}: {$aulas_count}<br>\n";
        
        if ($aulas_count > 0) {
            $aulas = $db->fetchAll("
                SELECT a.id, a.data_aula, a.hora_inicio, a.hora_fim, a.tipo_aula, a.status, a.observacoes,
                       i.credencial as instrutor_credencial,
                       v.placa as veiculo_placa
                FROM aulas a
                LEFT JOIN instrutores i ON a.instrutor_id = i.id
                LEFT JOIN veiculos v ON a.veiculo_id = v.id
                WHERE a.aluno_id = ?
                ORDER BY a.data_aula, a.hora_inicio
            ", [$aluno_id]);
            
            foreach ($aulas as $aula) {
                $veiculo_info = $aula['veiculo_placa'] ? " - Veículo: {$aula['veiculo_placa']}" : "";
                echo "&nbsp;&nbsp;&nbsp;• Aula ID {$aula['id']}: {$aula['data_aula']} {$aula['hora_inicio']}-{$aula['hora_fim']} - {$aula['tipo_aula']} ({$aula['status']}) - Instrutor: {$aula['instrutor_credencial']}{$veiculo_info}<br>\n";
                if ($aula['observacoes']) {
                    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Observações: {$aula['observacoes']}<br>\n";
                }
            }
        }
    }
    
    // Verificar slots de aulas vinculados
    foreach ($alunos_agendamentos_ids as $aluno_id) {
        $slots_count = $db->count('aulas_slots', 'aluno_id = ?', [$aluno_id]);
        echo "🎯 Slots de aulas para aluno {$aluno_id}: {$slots_count}<br>\n";
        
        if ($slots_count > 0) {
            $slots = $db->fetchAll("SELECT id, tipo_aula, status, ordem, aula_id FROM aulas_slots WHERE aluno_id = ? ORDER BY ordem", [$aluno_id]);
            foreach ($slots as $slot) {
                $aula_link = $slot['aula_id'] ? " (Vinculado à aula ID {$slot['aula_id']})" : " (Não vinculado)";
                echo "&nbsp;&nbsp;&nbsp;• Slot ID {$slot['id']}: {$slot['tipo_aula']} - Ordem {$slot['ordem']} ({$slot['status']}){$aula_link}<br>\n";
            }
        }
    }
    
    // Verificar logs de auditoria relacionados às aulas
    foreach ($alunos_agendamentos_ids as $aluno_id) {
        $logs_count = $db->count('logs', 'tabela = "aulas" AND JSON_EXTRACT(dados_novos, "$.aluno_id") = ?', [$aluno_id]);
        echo "📝 Logs de auditoria para aulas do aluno {$aluno_id}: {$logs_count}<br>\n";
    }
    
    echo "<h2>⚠️ Confirmação de Exclusão</h2>\n";
    echo "<p><strong>ATENÇÃO:</strong> Esta operação é irreversível!</p>\n";
    echo "<p><strong>O que será excluído:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>✅ Todos os registros da tabela 'aulas' vinculados aos alunos</li>\n";
    echo "<li>✅ Todos os registros da tabela 'aulas_slots' vinculados aos alunos</li>\n";
    echo "<li>✅ Logs de auditoria relacionados às aulas dos alunos</li>\n";
    echo "</ul>\n";
    echo "<p><strong>O que será MANTIDO:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>✅ Cadastros dos alunos (tabela 'alunos')</li>\n";
    echo "<li>✅ Usuários associados (se existirem)</li>\n";
    echo "<li>✅ Outros dados não relacionados a agendamentos</li>\n";
    echo "</ul>\n";
    
    // Verificar se deve executar a exclusão
    $executar_exclusao = isset($_GET['confirmar']) && $_GET['confirmar'] === 'sim';
    
    if (!$executar_exclusao) {
        echo "<p><a href='?confirmar=sim' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚨 CONFIRMAR EXCLUSÃO DE AGENDAMENTOS</a></p>\n";
        echo "<p><a href='index.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>❌ Cancelar</a></p>\n";
        exit;
    }
    
    echo "<h2>🗑️ Executando Exclusão de Agendamentos</h2>\n";
    
    $db->beginTransaction();
    
    try {
        foreach ($alunos_agendamentos_ids as $aluno_id) {
            echo "<h3>Excluindo agendamentos do aluno ID {$aluno_id}</h3>\n";
            
            // 1. Excluir aulas vinculadas
            $aulas_count = $db->count('aulas', 'aluno_id = ?', [$aluno_id]);
            if ($aulas_count > 0) {
                // Primeiro, obter IDs das aulas para excluir logs relacionados
                $aulas_ids = $db->fetchAll("SELECT id FROM aulas WHERE aluno_id = ?", [$aluno_id]);
                $aulas_ids_array = array_column($aulas_ids, 'id');
                
                // Excluir logs de auditoria das aulas
                if (!empty($aulas_ids_array)) {
                    $placeholders = str_repeat('?,', count($aulas_ids_array) - 1) . '?';
                    $logs_aulas_count = $db->count('logs', "tabela = 'aulas' AND registro_id IN ($placeholders)", $aulas_ids_array);
                    if ($logs_aulas_count > 0) {
                        $db->query("DELETE FROM logs WHERE tabela = 'aulas' AND registro_id IN ($placeholders)", $aulas_ids_array);
                        echo "✅ Excluídos {$logs_aulas_count} logs de auditoria das aulas<br>\n";
                    }
                }
                
                // Excluir as aulas
                $result = $db->query("DELETE FROM aulas WHERE aluno_id = ?", [$aluno_id]);
                echo "✅ Excluídas {$aulas_count} aulas<br>\n";
            }
            
            // 2. Excluir slots de aulas vinculados
            $slots_count = $db->count('aulas_slots', 'aluno_id = ?', [$aluno_id]);
            if ($slots_count > 0) {
                $result = $db->query("DELETE FROM aulas_slots WHERE aluno_id = ?", [$aluno_id]);
                echo "✅ Excluídos {$slots_count} slots de aulas<br>\n";
            }
            
            // 3. Excluir logs de auditoria relacionados ao aluno (apenas aulas)
            $logs_count = $db->count('logs', 'tabela = "aulas" AND JSON_EXTRACT(dados_novos, "$.aluno_id") = ?', [$aluno_id]);
            if ($logs_count > 0) {
                $result = $db->query('DELETE FROM logs WHERE tabela = "aulas" AND JSON_EXTRACT(dados_novos, "$.aluno_id") = ?', [$aluno_id]);
                echo "✅ Excluídos {$logs_count} logs de auditoria relacionados<br>\n";
            }
            
            echo "✅ Agendamentos do aluno ID {$aluno_id} excluídos com sucesso<br>\n";
            echo "<br>\n";
        }
        
        $db->commit();
        
        echo "<h2>✅ Exclusão de Agendamentos Concluída!</h2>\n";
        echo "<p>🎉 Todos os agendamentos de teste foram excluídos com sucesso!</p>\n";
        echo "<p>👥 Os cadastros dos alunos foram mantidos intactos.</p>\n";
        echo "<p>📚 O histórico de agendamentos foi limpo.</p>\n";
        echo "<p><a href='index.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Voltar ao Sistema</a></p>\n";
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Erro durante a exclusão</h2>\n";
    echo "<p><strong>Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p><a href='index.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Voltar ao Sistema</a></p>\n";
}

echo "<hr>\n";
echo "<p><small>Script executado em: " . date('d/m/Y H:i:s') . "</small></p>\n";
?>
