<?php
/**
 * SCRIPT PARA EXCLUSÃO DE ALUNOS DE TESTE
 * 
 * Este script remove os alunos de teste (IDs: 113, 127, 128) e todos os dados relacionados:
 * - Aulas agendadas
 * - Slots de aulas
 * - Logs de auditoria
 * - Usuários associados (se existirem)
 * 
 * ATENÇÃO: Este script é irreversível!
 */

// Configurações de segurança
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir arquivos necessários
require_once 'includes/config.php';
require_once 'includes/database.php';

// IDs dos alunos de teste para exclusão
$alunos_teste_ids = [113, 127, 128];

echo "<h1>🗑️ Script de Exclusão de Alunos de Teste</h1>\n";
echo "<p><strong>IDs dos alunos a serem excluídos:</strong> " . implode(', ', $alunos_teste_ids) . "</p>\n";

try {
    $db = Database::getInstance();
    
    echo "<h2>📊 Verificação Prévia</h2>\n";
    
    // Verificar se os alunos existem
    foreach ($alunos_teste_ids as $aluno_id) {
        $aluno = $db->fetch("SELECT id, nome, cpf FROM alunos WHERE id = ?", [$aluno_id]);
        if ($aluno) {
            echo "✅ Aluno encontrado: ID {$aluno_id} - {$aluno['nome']} (CPF: {$aluno['cpf']})<br>\n";
        } else {
            echo "❌ Aluno não encontrado: ID {$aluno_id}<br>\n";
        }
    }
    
    echo "<h2>🔍 Verificação de Dependências</h2>\n";
    
    // Verificar aulas vinculadas
    foreach ($alunos_teste_ids as $aluno_id) {
        $aulas_count = $db->count('aulas', 'aluno_id = ?', [$aluno_id]);
        echo "📚 Aulas vinculadas ao aluno {$aluno_id}: {$aulas_count}<br>\n";
        
        if ($aulas_count > 0) {
            $aulas = $db->fetchAll("SELECT id, data_aula, hora_inicio, tipo_aula, status FROM aulas WHERE aluno_id = ?", [$aluno_id]);
            foreach ($aulas as $aula) {
                echo "&nbsp;&nbsp;&nbsp;• Aula ID {$aula['id']}: {$aula['data_aula']} {$aula['hora_inicio']} - {$aula['tipo_aula']} ({$aula['status']})<br>\n";
            }
        }
    }
    
    // Verificar slots de aulas
    foreach ($alunos_teste_ids as $aluno_id) {
        $slots_count = $db->count('aulas_slots', 'aluno_id = ?', [$aluno_id]);
        echo "🎯 Slots de aulas vinculados ao aluno {$aluno_id}: {$slots_count}<br>\n";
    }
    
    // Verificar logs de auditoria
    foreach ($alunos_teste_ids as $aluno_id) {
        $logs_count = $db->count('logs', 'registro_id = ? AND tabela = "alunos"', [$aluno_id]);
        echo "📝 Logs de auditoria para aluno {$aluno_id}: {$logs_count}<br>\n";
    }
    
    echo "<h2>⚠️ Confirmação de Exclusão</h2>\n";
    echo "<p><strong>ATENÇÃO:</strong> Esta operação é irreversível!</p>\n";
    echo "<p>Os seguintes dados serão excluídos:</p>\n";
    echo "<ul>\n";
    echo "<li>Todos os registros da tabela 'aulas' vinculados aos alunos</li>\n";
    echo "<li>Todos os registros da tabela 'aulas_slots' vinculados aos alunos</li>\n";
    echo "<li>Todos os logs de auditoria relacionados aos alunos</li>\n";
    echo "<li>Os próprios registros dos alunos</li>\n";
    echo "</ul>\n";
    
    // Verificar se deve executar a exclusão
    $executar_exclusao = isset($_GET['confirmar']) && $_GET['confirmar'] === 'sim';
    
    if (!$executar_exclusao) {
        echo "<p><a href='?confirmar=sim' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚨 CONFIRMAR EXCLUSÃO</a></p>\n";
        echo "<p><a href='index.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>❌ Cancelar</a></p>\n";
        exit;
    }
    
    echo "<h2>🗑️ Executando Exclusão</h2>\n";
    
    $db->beginTransaction();
    
    try {
        foreach ($alunos_teste_ids as $aluno_id) {
            echo "<h3>Excluindo aluno ID {$aluno_id}</h3>\n";
            
            // 1. Excluir aulas vinculadas
            $aulas_count = $db->count('aulas', 'aluno_id = ?', [$aluno_id]);
            if ($aulas_count > 0) {
                $result = $db->query("DELETE FROM aulas WHERE aluno_id = ?", [$aluno_id]);
                echo "✅ Excluídas {$aulas_count} aulas<br>\n";
            }
            
            // 2. Excluir slots de aulas vinculados
            $slots_count = $db->count('aulas_slots', 'aluno_id = ?', [$aluno_id]);
            if ($slots_count > 0) {
                $result = $db->query("DELETE FROM aulas_slots WHERE aluno_id = ?", [$aluno_id]);
                echo "✅ Excluídos {$slots_count} slots de aulas<br>\n";
            }
            
            // 3. Excluir logs de auditoria
            $logs_count = $db->count('logs', 'registro_id = ? AND tabela = "alunos"', [$aluno_id]);
            if ($logs_count > 0) {
                $result = $db->query("DELETE FROM logs WHERE registro_id = ? AND tabela = 'alunos'", [$aluno_id]);
                echo "✅ Excluídos {$logs_count} logs de auditoria<br>\n";
            }
            
            // 4. Excluir o próprio aluno
            $result = $db->query("DELETE FROM alunos WHERE id = ?", [$aluno_id]);
            if ($result) {
                echo "✅ Aluno ID {$aluno_id} excluído com sucesso<br>\n";
            } else {
                echo "❌ Erro ao excluir aluno ID {$aluno_id}<br>\n";
            }
            
            echo "<br>\n";
        }
        
        $db->commit();
        
        echo "<h2>✅ Exclusão Concluída com Sucesso!</h2>\n";
        echo "<p>Todos os alunos de teste e dados relacionados foram excluídos.</p>\n";
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
