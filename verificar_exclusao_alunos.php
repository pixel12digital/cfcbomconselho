<?php
/**
 * SCRIPT DE VERIFICAÇÃO PÓS-EXCLUSÃO
 * 
 * Verifica se os alunos de teste (IDs: 113, 127, 128) foram excluídos com sucesso
 * e se não há dados órfãos relacionados.
 */

// Configurações
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir arquivos necessários
require_once 'includes/config.php';
require_once 'includes/database.php';

// IDs dos alunos de teste que deveriam ter sido excluídos
$alunos_teste_ids = [113, 127, 128];

echo "<h1>🔍 Verificação Pós-Exclusão</h1>\n";
echo "<p><strong>Verificando exclusão dos alunos de teste:</strong> " . implode(', ', $alunos_teste_ids) . "</p>\n";
echo "<p><strong>Data/Hora da verificação:</strong> " . date('d/m/Y H:i:s') . "</p>\n";

try {
    $db = Database::getInstance();
    
    echo "<h2>📊 Status da Exclusão</h2>\n";
    
    // Verificar se os alunos ainda existem
    $alunos_existentes = [];
    foreach ($alunos_teste_ids as $aluno_id) {
        $aluno = $db->fetch("SELECT id, nome, cpf FROM alunos WHERE id = ?", [$aluno_id]);
        if ($aluno) {
            $alunos_existentes[] = $aluno;
            echo "❌ <strong>ALUNO AINDA EXISTE:</strong> ID {$aluno_id} - {$aluno['nome']} (CPF: {$aluno['cpf']})<br>\n";
        } else {
            echo "✅ <strong>ALUNO EXCLUÍDO:</strong> ID {$aluno_id}<br>\n";
        }
    }
    
    if (empty($alunos_existentes)) {
        echo "<h3>🎉 SUCESSO: Todos os alunos de teste foram excluídos!</h3>\n";
    } else {
        echo "<h3>⚠️ ATENÇÃO: Alguns alunos ainda existem no banco!</h3>\n";
    }
    
    echo "<h2>🔍 Verificação de Dados Órfãos</h2>\n";
    
    // Verificar aulas órfãs
    $aulas_orfas = $db->fetchAll("SELECT COUNT(*) as total FROM aulas WHERE aluno_id IN (" . implode(',', $alunos_teste_ids) . ")");
    $total_aulas_orfas = $aulas_orfas[0]['total'] ?? 0;
    
    if ($total_aulas_orfas > 0) {
        echo "❌ <strong>AULAS ÓRFÃS ENCONTRADAS:</strong> {$total_aulas_orfas} aulas ainda referenciam os alunos excluídos<br>\n";
        
        $aulas_detalhes = $db->fetchAll("SELECT id, aluno_id, data_aula, hora_inicio, tipo_aula, status FROM aulas WHERE aluno_id IN (" . implode(',', $alunos_teste_ids) . ")");
        foreach ($aulas_detalhes as $aula) {
            echo "&nbsp;&nbsp;&nbsp;• Aula ID {$aula['id']}: Aluno {$aula['aluno_id']} - {$aula['data_aula']} {$aula['hora_inicio']} ({$aula['tipo_aula']}, {$aula['status']})<br>\n";
        }
    } else {
        echo "✅ <strong>AULAS ÓRFÃS:</strong> Nenhuma aula órfã encontrada<br>\n";
    }
    
    // Verificar slots órfãos
    $slots_orfos = $db->fetchAll("SELECT COUNT(*) as total FROM aulas_slots WHERE aluno_id IN (" . implode(',', $alunos_teste_ids) . ")");
    $total_slots_orfos = $slots_orfos[0]['total'] ?? 0;
    
    if ($total_slots_orfos > 0) {
        echo "❌ <strong>SLOTS ÓRFÃOS ENCONTRADOS:</strong> {$total_slots_orfos} slots ainda referenciam os alunos excluídos<br>\n";
    } else {
        echo "✅ <strong>SLOTS ÓRFÃOS:</strong> Nenhum slot órfão encontrado<br>\n";
    }
    
    // Verificar logs órfãos
    $logs_orfos = $db->fetchAll("SELECT COUNT(*) as total FROM logs WHERE registro_id IN (" . implode(',', $alunos_teste_ids) . ") AND tabela = 'alunos'");
    $total_logs_orfos = $logs_orfos[0]['total'] ?? 0;
    
    if ($total_logs_orfos > 0) {
        echo "❌ <strong>LOGS ÓRFÃOS ENCONTRADOS:</strong> {$total_logs_orfos} logs ainda referenciam os alunos excluídos<br>\n";
    } else {
        echo "✅ <strong>LOGS ÓRFÃOS:</strong> Nenhum log órfão encontrado<br>\n";
    }
    
    echo "<h2>📈 Estatísticas Atuais do Sistema</h2>\n";
    
    // Estatísticas gerais
    $total_alunos = $db->count('alunos');
    $total_aulas = $db->count('aulas');
    $total_slots = $db->count('aulas_slots');
    $total_logs_alunos = $db->count('logs', 'tabela = "alunos"');
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>Item</th><th>Quantidade</th></tr>\n";
    echo "<tr><td>Total de Alunos</td><td>{$total_alunos}</td></tr>\n";
    echo "<tr><td>Total de Aulas</td><td>{$total_aulas}</td></tr>\n";
    echo "<tr><td>Total de Slots</td><td>{$total_slots}</td></tr>\n";
    echo "<tr><td>Total de Logs (Alunos)</td><td>{$total_logs_alunos}</td></tr>\n";
    echo "</table>\n";
    
    echo "<h2>🎯 Resumo Final</h2>\n";
    
    if (empty($alunos_existentes) && $total_aulas_orfas == 0 && $total_slots_orfos == 0 && $total_logs_orfos == 0) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; border: 1px solid #c3e6cb;'>\n";
        echo "<h3>✅ EXCLUSÃO COMPLETA E BEM-SUCEDIDA!</h3>\n";
        echo "<p>🎉 Todos os alunos de teste foram excluídos com sucesso!</p>\n";
        echo "<p>🧹 Não há dados órfãos relacionados.</p>\n";
        echo "<p>✨ O banco de dados está limpo e consistente.</p>\n";
        echo "</div>\n";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb;'>\n";
        echo "<h3>⚠️ EXCLUSÃO INCOMPLETA!</h3>\n";
        echo "<p>❌ Ainda existem dados relacionados aos alunos de teste.</p>\n";
        echo "<p>🔧 Recomenda-se executar novamente o script de exclusão.</p>\n";
        echo "</div>\n";
    }
    
    echo "<p><a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Voltar ao Sistema</a></p>\n";
    
} catch (Exception $e) {
    echo "<h2>❌ Erro durante a verificação</h2>\n";
    echo "<p><strong>Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p><a href='index.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Voltar ao Sistema</a></p>\n";
}

echo "<hr>\n";
echo "<p><small>Verificação executada em: " . date('d/m/Y H:i:s') . "</small></p>\n";
?>
