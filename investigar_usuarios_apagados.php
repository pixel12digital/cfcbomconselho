<?php
// =====================================================
// INVESTIGAÇÃO: USUÁRIOS SENDO APAGADOS AUTOMATICAMENTE
// =====================================================

// Habilitar exibição de erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🚨 INVESTIGAÇÃO: USUÁRIOS SENDO APAGADOS AUTOMATICAMENTE</h1>";
echo "<p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>";

// Incluir arquivos necessários
require_once './includes/config.php';
require_once './includes/database.php';

$db = Database::getInstance();

try {
    echo "<hr>";
    echo "<h2>🔍 1. VERIFICAÇÃO ATUAL DOS USUÁRIOS</h2>";
    
    // Contar usuários atualmente
    $totalUsuarios = $db->count('usuarios');
    echo "<p><strong>Total de usuários atualmente:</strong> <span style='color: blue; font-weight: bold;'>$totalUsuarios</span></p>";
    
    // Listar todos os usuários
    $usuarios = $db->fetchAll("SELECT * FROM usuarios ORDER BY id");
    echo "<h3>📋 Lista de Usuários Atuais:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 12px;'>";
    echo "<tr><th>ID</th><th>Nome</th><th>Email</th><th>Tipo</th><th>Status</th><th>Criado em</th><th>Atualizado em</th></tr>";
    
    foreach ($usuarios as $usuario) {
        $statusColor = $usuario['ativo'] ? 'green' : 'red';
        $statusText = $usuario['ativo'] ? 'ATIVO' : 'INATIVO';
        
        echo "<tr>";
        echo "<td>{$usuario['id']}</td>";
        echo "<td>{$usuario['nome']}</td>";
        echo "<td>{$usuario['email']}</td>";
        echo "<td>{$usuario['tipo']}</td>";
        echo "<td style='color: $statusColor; font-weight: bold;'>$statusText</td>";
        echo "<td>{$usuario['criado_em']}</td>";
        echo "<td>" . ($usuario['atualizado_em'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<h2>🔍 2. VERIFICAÇÃO DE TRIGGERS NO BANCO</h2>";
    
    // Verificar se há triggers que podem estar excluindo usuários
    try {
        $triggers = $db->fetchAll("SHOW TRIGGERS");
        
        if (empty($triggers)) {
            echo "<p style='color: green;'>✅ <strong>Nenhum trigger encontrado</strong> - Não há triggers que possam estar causando exclusões automáticas</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ <strong>Triggers encontrados:</strong></p>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 12px;'>";
            echo "<tr><th>Trigger</th><th>Event</th><th>Tabela</th><th>Statement</th></tr>";
            
            foreach ($triggers as $trigger) {
                $isDangerous = stripos($trigger['Statement'], 'DELETE') !== false || 
                               stripos($trigger['Statement'], 'DROP') !== false ||
                               stripos($trigger['Statement'], 'usuarios') !== false;
                
                $rowColor = $isDangerous ? '#ffebee' : '';
                
                echo "<tr style='background-color: $rowColor;'>";
                echo "<td>{$trigger['Trigger']}</td>";
                echo "<td>{$trigger['Event']}</td>";
                echo "<td>{$trigger['Table']}</td>";
                echo "<td style='font-family: monospace; font-size: 10px;'>" . htmlspecialchars($trigger['Statement']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            if (array_filter($triggers, function($t) { 
                return stripos($t['Statement'], 'usuarios') !== false; 
            })) {
                echo "<p style='color: red;'>🚨 <strong>ATENÇÃO:</strong> Encontrados triggers que afetam a tabela 'usuarios'!</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ <strong>Não foi possível verificar triggers:</strong> " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
    echo "<h2>🔍 3. VERIFICAÇÃO DE FOREIGN KEYS</h2>";
    
    // Verificar constraints que podem causar exclusão automática
    try {
        $foreignKeys = $db->fetchAll("
            SELECT 
                TABLE_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME,
                DELETE_RULE,
                UPDATE_RULE
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE REFERENCED_TABLE_NAME = 'usuarios'
            AND REFERENCED_TABLE_SCHEMA = DATABASE()
        ");
        
        if (empty($foreignKeys)) {
            echo "<p style='color: green;'>✅ <strong>Nenhuma foreign key referenciando 'usuarios'</strong> - Não há exclusões automáticas por relacionamento</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ <strong>Foreign keys encontradas referenciando 'usuarios':</strong></p>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 12px;'>";
            echo "<tr><th>Tabela</th><th>Coluna</th><th>Referência</th><th>Regra DELETE</th><th>Regra UPDATE</th><th>Risco</th></tr>";
            
            foreach ($foreignKeys as $fk) {
                $isDangerous = $fk['DELETE_RULE'] === 'CASCADE';
                $rowColor = $isDangerous ? '#ffebee' : '';
                $risco = $isDangerous ? '🚨 ALTO' : '✅ BAIXO';
                
                echo "<tr style='background-color: $rowColor;'>";
                echo "<td>{$fk['TABLE_NAME']}</td>";
                echo "<td>{$fk['COLUMN_NAME']}</td>";
                echo "<td>{$fk['REFERENCED_COLUMN_NAME']}</td>";
                echo "<td>{$fk['DELETE_RULE']}</td>";
                echo "<td>{$fk['UPDATE_RULE']}</td>";
                echo "<td style='font-weight: bold;'>$risco</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            if (array_filter($foreignKeys, function($fk) { 
                return $fk['DELETE_RULE'] === 'CASCADE'; 
            })) {
                echo "<p style='color: red;'>🚨 <strong>ATENÇÃO:</strong> Encontradas foreign keys com DELETE CASCADE que podem estar excluindo usuários automaticamente!</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ <strong>Não foi possível verificar foreign keys:</strong> " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
    echo "<h2>🔍 4. VERIFICAÇÃO DE LOGS DO SISTEMA</h2>";
    
    // Verificar se existe tabela de logs
    $tabelas = $db->fetchAll("SHOW TABLES LIKE '%log%'");
    
    if (empty($tabelas)) {
        echo "<p style='color: orange;'>⚠️ <strong>Nenhuma tabela de logs encontrada</strong> - Não é possível verificar histórico de exclusões</p>";
    } else {
        echo "<p style='color: green;'>✅ <strong>Tabelas de logs encontradas:</strong></p>";
        
        foreach ($tabelas as $tabela) {
            $nomeTabela = array_values($tabela)[0];
            echo "<h4>📋 Tabela: $nomeTabela</h4>";
            
            try {
                $logs = $db->fetchAll("SELECT * FROM `$nomeTabela` WHERE acao LIKE '%delete%' OR acao LIKE '%excluir%' OR acao LIKE '%remove%' ORDER BY data DESC LIMIT 10");
                
                if (empty($logs)) {
                    echo "<p style='color: green;'>✅ Nenhuma ação de exclusão encontrada nos logs</p>";
                } else {
                    echo "<p style='color: red;'>🚨 <strong>Ações de exclusão encontradas nos logs:</strong></p>";
                    echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 12px;'>";
                    
                    // Pegar colunas da primeira linha
                    if (!empty($logs)) {
                        $colunas = array_keys($logs[0]);
                        echo "<tr>";
                        foreach ($colunas as $coluna) {
                            echo "<th>$coluna</th>";
                        }
                        echo "</tr>";
                        
                        foreach ($logs as $log) {
                            echo "<tr>";
                            foreach ($colunas as $coluna) {
                                echo "<td>" . htmlspecialchars($log[$coluna]) . "</td>";
                            }
                            echo "</tr>";
                        }
                    }
                    echo "</table>";
                }
            } catch (Exception $e) {
                echo "<p style='color: orange;'>⚠️ Erro ao verificar logs: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    echo "<hr>";
    echo "<h2>🔍 5. VERIFICAÇÃO DE CRON JOBS</h2>";
    
    // Verificar se há arquivos de cron ou agendamento
    $cronFiles = [
        './cron.php',
        './cron_jobs.php',
        './scheduler.php',
        './cleanup.php',
        './maintenance.php'
    ];
    
    echo "<h4>📁 Verificando arquivos de cron:</h4>";
    foreach ($cronFiles as $cronFile) {
        if (file_exists($cronFile)) {
            echo "<p style='color: red;'>🚨 <strong>ARQUIVO DE CRON ENCONTRADO:</strong> $cronFile</p>";
            
            // Verificar conteúdo do arquivo
            $content = file_get_contents($cronFile);
            if (stripos($content, 'usuarios') !== false || stripos($content, 'delete') !== false) {
                echo "<p style='color: red;'>🚨 <strong>ATENÇÃO:</strong> Este arquivo contém referências a usuários ou exclusões!</p>";
            }
        } else {
            echo "<p style='color: green;'>✅ $cronFile - Não encontrado</p>";
        }
    }
    
    echo "<hr>";
    echo "<h2>🔍 6. VERIFICAÇÃO DE FUNÇÕES DE EXCLUSÃO NO CÓDIGO</h2>";
    
    // Verificar arquivos PHP que podem conter funções de exclusão
    $phpFiles = [
        './admin/api/usuarios.php',
        './admin/api/instrutores.php',
        './includes/database.php'
    ];
    
    echo "<h4>📁 Verificando arquivos PHP:</h4>";
    foreach ($phpFiles as $phpFile) {
        if (file_exists($phpFile)) {
            echo "<h5>🔍 $phpFile:</h5>";
            
            $content = file_get_contents($phpFile);
            
            // Verificar funções de exclusão
            if (preg_match_all('/function\s+(\w*delete\w*|\w*excluir\w*|\w*remove\w*)/i', $content, $matches)) {
                echo "<p style='color: orange;'>⚠️ <strong>Funções de exclusão encontradas:</strong></p>";
                foreach ($matches[1] as $match) {
                    echo "<p style='color: red;'>🚨 $match</p>";
                }
            } else {
                echo "<p style='color: green;'>✅ Nenhuma função de exclusão encontrada</p>";
            }
            
            // Verificar queries DELETE
            if (preg_match_all('/DELETE\s+FROM\s+(\w+)/i', $content, $matches)) {
                echo "<p style='color: orange;'>⚠️ <strong>Queries DELETE encontradas:</strong></p>";
                foreach ($matches[1] as $match) {
                    $isDangerous = stripos($match, 'usuarios') !== false;
                    $color = $isDangerous ? 'red' : 'orange';
                    $icon = $isDangerous ? '🚨' : '⚠️';
                    echo "<p style='color: $color;'>$icon DELETE FROM $match</p>";
                }
            } else {
                echo "<p style='color: green;'>✅ Nenhuma query DELETE encontrada</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ $phpFile - Arquivo não encontrado</p>";
        }
    }
    
    echo "<hr>";
    echo "<h2>🔍 7. VERIFICAÇÃO DE PROCESSOS AUTOMÁTICOS</h2>";
    
    // Verificar se há processos em execução
    if (function_exists('shell_exec')) {
        $processes = shell_exec('ps aux | grep -i php | grep -v grep');
        if ($processes) {
            echo "<p style='color: orange;'>⚠️ <strong>Processos PHP em execução:</strong></p>";
            echo "<pre>" . htmlspecialchars($processes) . "</pre>";
        } else {
            echo "<p style='color: green;'>✅ Nenhum processo PHP em execução</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ <strong>Função shell_exec não disponível</strong> - Não é possível verificar processos</p>";
    }
    
    echo "<hr>";
    echo "<h2>🔍 8. RECOMENDAÇÕES DE SEGURANÇA</h2>";
    
    echo "<div style='background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px;'>";
    echo "<h3>🛡️ MEDIDAS IMEDIATAS:</h3>";
    echo "<ol>";
    echo "<li><strong>Implementar Soft Delete:</strong> Marcar usuários como inativos em vez de excluí-los</li>";
    echo "<li><strong>Log de Auditoria:</strong> Registrar todas as ações de exclusão</li>";
    echo "<li><strong>Confirmação Obrigatória:</strong> Exigir confirmação antes de excluir usuários</li>";
    echo "<li><strong>Backup Automático:</strong> Fazer backup antes de qualquer exclusão</li>";
    echo "<li><strong>Monitoramento:</strong> Alertas quando usuários são excluídos</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<hr>";
    echo "<h2>🔍 9. RESUMO DA INVESTIGAÇÃO</h2>";
    
    echo "<div style='background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>";
    echo "<h3>📊 STATUS ATUAL:</h3>";
    echo "<ul>";
    echo "<li><strong>Total de usuários:</strong> $totalUsuarios</li>";
    echo "<li><strong>Triggers perigosos:</strong> " . (isset($triggers) && array_filter($triggers, function($t) { return stripos($t['Statement'], 'usuarios') !== false; }) ? 'SIM' : 'NÃO') . "</li>";
    echo "<li><strong>Foreign keys CASCADE:</strong> " . (isset($foreignKeys) && array_filter($foreignKeys, function($fk) { return $fk['DELETE_RULE'] === 'CASCADE'; }) ? 'SIM' : 'NÃO') . "</li>";
    echo "<li><strong>Logs de exclusão:</strong> " . (isset($logs) && !empty($logs) ? 'ENCONTRADOS' : 'NÃO ENCONTRADOS') . "</li>";
    echo "<li><strong>Arquivos de cron:</strong> " . (array_filter($cronFiles, 'file_exists') ? 'ENCONTRADOS' : 'NÃO ENCONTRADOS') . "</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ <strong>ERRO CRÍTICO:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Stack trace:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><strong>🔍 Investigação concluída em:</strong> " . date('d/m/Y H:i:s') . "</p>";
echo "<p><strong>💡 Próximo passo:</strong> Analisar os resultados e implementar as correções necessárias</p>";
?>
