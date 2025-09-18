<?php
/**
 * SCRIPT DE VERIFICAÇÃO PÓS-EXCLUSÃO DE AGENDAMENTOS
 * 
 * Verifica se apenas os agendamentos dos alunos 111 e 112 foram excluídos,
 * mantendo os cadastros dos alunos intactos.
 */

// Configurações
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir arquivos necessários
require_once 'includes/config.php';
require_once 'includes/database.php';

// IDs dos alunos para verificação
$alunos_ids = [111, 112];

echo "<h1>🔍 Verificação Pós-Exclusão de Agendamentos</h1>\n";
echo "<p><strong>Verificando exclusão de agendamentos dos alunos:</strong> " . implode(', ', $alunos_ids) . "</p>\n";
echo "<p><strong>Data/Hora da verificação:</strong> " . date('d/m/Y H:i:s') . "</p>\n";

try {
    $db = Database::getInstance();
    
    echo "<h2>👥 Status dos Cadastros dos Alunos</h2>\n";
    
    // Verificar se os alunos ainda existem (devem existir)
    $alunos_mantidos = 0;
    foreach ($alunos_ids as $aluno_id) {
        $aluno = $db->fetch("SELECT id, nome, cpf, categoria_cnh, status, criado_em FROM alunos WHERE id = ?", [$aluno_id]);
        if ($aluno) {
            $alunos_mantidos++;
            echo "✅ <strong>ALUNO MANTIDO:</strong> ID {$aluno_id} - {$aluno['nome']} (CPF: {$aluno['cpf']}, Status: {$aluno['status']})<br>\n";
        } else {
            echo "❌ <strong>ALUNO NÃO ENCONTRADO:</strong> ID {$aluno_id}<br>\n";
        }
    }
    
    echo "<h2>📚 Status dos Agendamentos</h2>\n";
    
    // Verificar aulas restantes
    $aulas_restantes = $db->count('aulas', 'aluno_id IN (' . implode(',', $alunos_ids) . ')');
    if ($aulas_restantes > 0) {
        echo "❌ <strong>AULAS RESTANTES:</strong> {$aulas_restantes} aulas ainda existem<br>\n";
        
        $aulas_detalhes = $db->fetchAll("
            SELECT a.id, a.aluno_id, a.data_aula, a.hora_inicio, a.tipo_aula, a.status,
                   al.nome as aluno_nome
            FROM aulas a
            JOIN alunos al ON a.aluno_id = al.id
            WHERE a.aluno_id IN (" . implode(',', $alunos_ids) . ")
            ORDER BY a.aluno_id, a.data_aula
        ");
        
        foreach ($aulas_detalhes as $aula) {
            echo "&nbsp;&nbsp;&nbsp;• Aula ID {$aula['id']}: {$aula['data_aula']} {$aula['hora_inicio']} - {$aula['tipo_aula']} ({$aula['status']})<br>\n";
        }
    } else {
        echo "✅ <strong>AULAS:</strong> Nenhuma aula restante encontrada<br>\n";
    }
    
    // Verificar slots restantes
    $slots_restantes = $db->count('aulas_slots', 'aluno_id IN (' . implode(',', $alunos_ids) . ')');
    if ($slots_restantes > 0) {
        echo "❌ <strong>SLOTS RESTANTES:</strong> {$slots_restantes} slots ainda existem<br>\n";
    } else {
        echo "✅ <strong>SLOTS:</strong> Nenhum slot restante encontrado<br>\n";
    }
    
    // Verificar logs restantes
    $logs_restantes = $db->count('logs', 'tabela = "aulas" AND JSON_EXTRACT(dados_novos, "$.aluno_id") IN (' . implode(',', $alunos_ids) . ')');
    if ($logs_restantes > 0) {
        echo "❌ <strong>LOGS RESTANTES:</strong> {$logs_restantes} logs ainda existem<br>\n";
    } else {
        echo "✅ <strong>LOGS:</strong> Nenhum log restante encontrado<br>\n";
    }
    
    echo "<h2>📈 Estatísticas Atuais</h2>\n";
    
    // Estatísticas gerais
    $total_alunos = $db->count('alunos');
    $total_aulas = $db->count('aulas');
    $total_slots = $db->count('aulas_slots');
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>Item</th><th>Quantidade</th></tr>\n";
    echo "<tr><td>Total de Alunos</td><td>{$total_alunos}</td></tr>\n";
    echo "<tr><td>Total de Aulas</td><td>{$total_aulas}</td></tr>\n";
    echo "<tr><td>Total de Slots</td><td>{$total_slots}</td></tr>\n";
    echo "<tr><td>Alunos Verificados (111, 112)</td><td>{$alunos_mantidos}</td></tr>\n";
    echo "<tr><td>Aulas dos Alunos Verificados</td><td>{$aulas_restantes}</td></tr>\n";
    echo "<tr><td>Slots dos Alunos Verificados</td><td>{$slots_restantes}</td></tr>\n";
    echo "</table>\n";
    
    echo "<h2>🎯 Resumo Final</h2>\n";
    
    if ($alunos_mantidos == count($alunos_ids) && $aulas_restantes == 0 && $slots_restantes == 0 && $logs_restantes == 0) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; border: 1px solid #c3e6cb;'>\n";
        echo "<h3>✅ EXCLUSÃO DE AGENDAMENTOS COMPLETA E BEM-SUCEDIDA!</h3>\n";
        echo "<p>🎉 Todos os agendamentos de teste foram excluídos com sucesso!</p>\n";
        echo "<p>👥 Os cadastros dos alunos foram mantidos intactos.</p>\n";
        echo "<p>📚 O histórico de agendamentos foi limpo.</p>\n";
        echo "<p>✨ O banco de dados está consistente.</p>\n";
        echo "</div>\n";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb;'>\n";
        echo "<h3>⚠️ EXCLUSÃO INCOMPLETA!</h3>\n";
        if ($alunos_mantidos < count($alunos_ids)) {
            echo "<p>❌ Alguns alunos foram excluídos (não deveriam).</p>\n";
        }
        if ($aulas_restantes > 0) {
            echo "<p>❌ Ainda existem aulas relacionadas aos alunos.</p>\n";
        }
        if ($slots_restantes > 0) {
            echo "<p>❌ Ainda existem slots relacionados aos alunos.</p>\n";
        }
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
