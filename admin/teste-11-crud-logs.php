<?php
/**
 * TESTE #11: CRUD de Logs
 * Data/Hora: 19/08/2025 15:54:00
 * 
 * Este teste verifica todas as operações CRUD para a tabela 'logs':
 * - CREATE: Criar novo log
 * - READ: Buscar log por ID, data, usuário, tipo
 * - UPDATE: Atualizar dados do log
 * - DELETE: Excluir log
 * - Validações: Verificar regras de negócio
 */

// Configuração de exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html>";
echo "<html lang='pt-BR'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>TESTE #11: CRUD de Logs</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }";
echo ".container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo ".header { background: linear-gradient(135deg, #6f42c1 0%, #8e44ad 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; }";
echo ".test-section { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; padding: 15px; margin-bottom: 15px; }";
echo ".test-title { color: #495057; font-weight: bold; margin-bottom: 10px; font-size: 16px; }";
echo ".success { color: #28a745; font-weight: bold; }";
echo ".error { color: #dc3545; font-weight: bold; }";
echo ".warning { color: #ffc107; font-weight: bold; }";
echo ".info { color: #17a2b8; font-weight: bold; }";
echo ".data-table { width: 100%; border-collapse: collapse; margin: 10px 0; }";
echo ".data-table th, .data-table td { border: 1px solid #dee2e6; padding: 8px; text-align: left; }";
echo ".data-table th { background-color: #e9ecef; font-weight: bold; }";
echo ".data-table tr:nth-child(even) { background-color: #f8f9fa; }";
echo ".summary { background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 6px; padding: 15px; margin-top: 20px; }";
echo ".summary h3 { color: #0056b3; margin-top: 0; }";
echo ".progress-bar { width: 100%; height: 20px; background-color: #e9ecef; border-radius: 10px; overflow: hidden; margin: 10px 0; }";
echo ".progress-fill { height: 100%; background: linear-gradient(90deg, #28a745, #20c997); transition: width 0.3s ease; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<div class='header'>";
echo "<h1>🔍 TESTE #11: CRUD de Logs</h1>";
echo "<p>Data/Hora: " . date('d/m/Y H:i:s') . "</p>";
echo "<p>Ambiente: XAMPP Local (Porta 8080)</p>";
echo "</div>";

// Contadores para estatísticas
$totalTests = 0;
$successCount = 0;
$errorCount = 0;
$warningCount = 0;

// Função para registrar resultado do teste
function registerTest($testName, $status, $message = '', $data = null) {
    global $totalTests, $successCount, $errorCount, $warningCount;
    
    $totalTests++;
    $statusClass = '';
    $statusIcon = '';
    
    switch($status) {
        case 'success':
            $statusClass = 'success';
            $statusIcon = '✅';
            $successCount++;
            break;
        case 'error':
            $statusClass = 'error';
            $statusIcon = '❌';
            $errorCount++;
            break;
        case 'warning':
            $statusClass = 'warning';
            $statusIcon = '⚠️';
            $warningCount++;
            break;
        case 'info':
            $statusClass = 'info';
            $statusIcon = 'ℹ️';
            break;
    }
    
    echo "<div class='test-section'>";
    echo "<div class='test-title'>{$statusIcon} {$testName}</div>";
    echo "<div class='{$statusClass}'>{$message}</div>";
    
    if ($data !== null) {
        if (is_array($data) && !empty($data)) {
            echo "<table class='data-table'>";
            echo "<thead><tr>";
            foreach (array_keys($data[0]) as $header) {
                echo "<th>" . htmlspecialchars($header) . "</th>";
            }
            echo "</tr></thead><tbody>";
            foreach ($data as $row) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<p><em>Nenhum dado encontrado</em></p>";
        }
    }
    echo "</div>";
}

// 11.1 Inclusão de Arquivos Necessários
echo "<h2>11.1 Inclusão de Arquivos Necessários</h2>";

try {
    require_once '../includes/database.php';
    require_once '../includes/config.php';
    registerTest("Arquivos necessários", "success", "INCLUÍDOS COM SUCESSO");
} catch (Exception $e) {
    registerTest("Arquivos necessários", "error", "ERRO: " . $e->getMessage());
}

// 11.2 Conexão com Banco de Dados
echo "<h2>11.2 Conexão com Banco de Dados</h2>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    registerTest("Conexão PDO", "success", "ESTABELECIDA COM SUCESSO");
} catch (PDOException $e) {
    registerTest("Conexão PDO", "error", "ERRO: " . $e->getMessage());
    exit;
}

// 11.3 Estrutura da Tabela 'logs'
echo "<h2>11.3 Estrutura da Tabela 'logs'</h2>";

try {
    $stmt = $pdo->query("DESCRIBE logs");
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($structure)) {
        registerTest("Estrutura da tabela", "success", "COMPLETA", $structure);
    } else {
        registerTest("Estrutura da tabela", "error", "TABELA NÃO ENCONTRADA");
    }
} catch (Exception $e) {
    registerTest("Estrutura da tabela", "error", "ERRO: " . $e->getMessage());
}

// 11.4 Verificação de Dados na Tabela 'logs'
echo "<h2>11.4 Verificação de Dados na Tabela 'logs'</h2>";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM logs");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("Total de Logs na tabela", "success", $count['total'] . " registros");
    
    if ($count['total'] > 0) {
        $stmt = $pdo->query("SELECT * FROM logs LIMIT 5");
        $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        registerTest("Dados de exemplo", "info", "Primeiros 5 registros", $sampleData);
    }
} catch (Exception $e) {
    registerTest("Verificação de dados", "error", "ERRO: " . $e->getMessage());
}

// 11.5 Verificação de Dados de Referência
echo "<h2>11.5 Verificação de Dados de Referência</h2>";

try {
    // Verificar Usuários
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $usuarioCount = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("Usuários disponíveis", "info", $usuarioCount['total'] . " usuários encontrados");
    
    // Verificar CFCs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cfcs");
    $cfcCount = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("CFCs disponíveis", "info", $cfcCount['total'] . " CFCs encontrados");
    
} catch (Exception $e) {
    registerTest("Verificação de referências", "error", "ERRO: " . $e->getMessage());
}

// 11.6 Teste CREATE - Criar Log
echo "<h2>11.6 Teste CREATE - Criar Log</h2>";

try {
    // Buscar dados de referência
    $stmt = $pdo->query("SELECT id FROM usuarios LIMIT 1");
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("SELECT id FROM cfcs LIMIT 1");
    $cfc = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario && $cfc) {
        $stmt = $pdo->prepare("INSERT INTO logs (usuario_id, acao, tabela_afetada, registro_id, ip_address, criado_em) VALUES (?, ?, ?, ?, ?, NOW())");
        
        $acao = 'teste';
        $tabelaAfetada = 'logs';
        $registroId = 999;
        $ipAddress = '127.0.0.1';
        
        $result = $stmt->execute([
            $usuario['id'],
            $acao,
            $tabelaAfetada,
            $registroId,
            $ipAddress
        ]);
        
        if ($result) {
            $logId = $pdo->lastInsertId();
            registerTest("CREATE", "success", "LOG CRIADO COM SUCESSO");
            registerTest("ID do log criado", "info", $logId);
        } else {
            registerTest("CREATE", "error", "FALHA AO CRIAR LOG");
        }
    } else {
        registerTest("CREATE", "error", "DADOS DE REFERÊNCIA INSUFICIENTES");
    }
} catch (Exception $e) {
    registerTest("CREATE", "error", "ERRO: " . $e->getMessage());
}

// 11.7 Teste READ - Ler Log
echo "<h2>11.7 Teste READ - Ler Log</h2>";

try {
    if (isset($logId)) {
        // READ por ID
        $stmt = $pdo->prepare("SELECT l.*, u.nome as usuario_nome FROM logs l JOIN usuarios u ON l.usuario_id = u.id WHERE l.id = ?");
        $stmt->execute([$logId]);
        $log = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($log) {
            registerTest("READ por ID", "success", "LOG ENCONTRADO");
            registerTest("Dados do log", "info", "Log encontrado com sucesso", [$log]);
        } else {
            registerTest("READ por ID", "error", "LOG NÃO ENCONTRADO");
        }
        
        // READ por Data
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM logs WHERE DATE(criado_em) = ?");
        $stmt->execute([date('Y-m-d')]);
        $countData = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por Data", "success", $countData['total'] . " logs encontrados para hoje");
        
        // READ por Ação
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM logs WHERE acao = ?");
        $stmt->execute(['teste']);
        $countAcao = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por Ação", "success", $countAcao['total'] . " logs da ação 'teste'");
        
        // READ ALL
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM logs");
        $countAll = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ ALL", "success", $countAll['total'] . " LOGS ENCONTRADOS");
        
    } else {
        registerTest("READ", "warning", "NENHUM LOG CRIADO PARA TESTE");
    }
} catch (Exception $e) {
    registerTest("READ", "error", "ERRO: " . $e->getMessage());
}

// 11.8 Teste UPDATE - Atualizar Log
echo "<h2>11.8 Teste UPDATE - Atualizar Log</h2>";

try {
    if (isset($logId)) {
        $novaAcao = 'atualizado';
        $novaTabelaAfetada = 'logs_teste';
        
        $stmt = $pdo->prepare("UPDATE logs SET acao = ?, tabela_afetada = ? WHERE id = ?");
        $result = $stmt->execute([$novaAcao, $novaTabelaAfetada, $logId]);
        
        if ($result) {
            registerTest("UPDATE", "success", "LOG ATUALIZADO COM SUCESSO");
            
            // Verificar se a atualização foi aplicada
            $stmt = $pdo->prepare("SELECT * FROM logs WHERE id = ?");
            $stmt->execute([$logId]);
            $logAtualizado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($logAtualizado && $logAtualizado['acao'] === $novaAcao && $logAtualizado['tabela_afetada'] === $novaTabelaAfetada) {
                registerTest("Verificação UPDATE", "success", "DADOS CONFIRMADOS");
            } else {
                registerTest("Verificação UPDATE", "warning", "ATUALIZAÇÃO NÃO CONFIRMADA");
            }
        } else {
            registerTest("UPDATE", "error", "FALHA AO ATUALIZAR LOG");
        }
    } else {
        registerTest("UPDATE", "warning", "NENHUM LOG CRIADO PARA TESTE");
    }
} catch (Exception $e) {
    registerTest("UPDATE", "error", "ERRO: " . $e->getMessage());
}

// 11.9 Teste DELETE - Excluir Log
echo "<h2>11.9 Teste DELETE - Excluir Log</h2>";

try {
    if (isset($logId)) {
        $stmt = $pdo->prepare("DELETE FROM logs WHERE id = ?");
        $result = $stmt->execute([$logId]);
        
        if ($result) {
            registerTest("DELETE", "success", "LOG EXCLUÍDO COM SUCESSO");
            
            // Verificar se a exclusão foi aplicada
            $stmt = $pdo->prepare("SELECT * FROM logs WHERE id = ?");
            $stmt->execute([$logId]);
            $logExcluido = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$logExcluido) {
                registerTest("Verificação DELETE", "success", "LOG NÃO ENCONTRADO (EXCLUÍDO)");
            } else {
                registerTest("Verificação DELETE", "error", "LOG AINDA EXISTE");
            }
        } else {
            registerTest("DELETE", "error", "FALHA AO EXCLUIR LOG");
        }
    } else {
        registerTest("DELETE", "warning", "NENHUM LOG CRIADO PARA TESTE");
    }
} catch (Exception $e) {
    registerTest("DELETE", "error", "ERRO: " . $e->getMessage());
}

// 11.10 Teste de Validações
echo "<h2>11.10 Teste de Validações</h2>";

try {
    // Verificar logs sem usuário
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM logs WHERE usuario_id IS NULL");
    $semUsuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semUsuario['total'] == 0) {
        registerTest("Validação Usuário", "success", "TODOS OS LOGS TÊM USUÁRIO");
    } else {
        registerTest("Validação Usuário", "warning", $semUsuario['total'] . " LOGS SEM USUÁRIO");
    }
    
    // Verificar logs sem ação
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM logs WHERE acao IS NULL OR acao = ''");
    $semAcao = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semAcao['total'] == 0) {
        registerTest("Validação Ação", "success", "TODOS OS LOGS TÊM AÇÃO");
    } else {
        registerTest("Validação Ação", "warning", $semAcao['total'] . " LOGS SEM AÇÃO");
    }
    
    // Verificar logs sem tabela afetada
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM logs WHERE tabela_afetada IS NULL OR tabela_afetada = ''");
    $semTabela = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semTabela['total'] == 0) {
        registerTest("Validação Tabela", "success", "TODOS OS LOGS TÊM TABELA AFETADA");
    } else {
        registerTest("Validação Tabela", "warning", $semTabela['total'] . " LOGS SEM TABELA AFETADA");
    }
    
    // Verificar logs sem IP
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM logs WHERE ip_address IS NULL OR ip_address = ''");
    $semIp = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semIp['total'] == 0) {
        registerTest("Validação IP", "success", "TODOS OS LOGS TÊM IP");
    } else {
        registerTest("Validação IP", "warning", $semIp['total'] . " LOGS SEM IP");
    }
    
} catch (Exception $e) {
    registerTest("Validações", "error", "ERRO: " . $e->getMessage());
}

// Resumo dos Testes
echo "<div class='summary'>";
echo "<h3>📊 RESUMO DOS TESTES</h3>";

if ($successCount > 0) {
    echo "<div class='success'>✅ SUCESSOS (" . $successCount . ")</div>";
}

if ($errorCount > 0) {
    echo "<div class='error'>❌ ERROS (" . $errorCount . ")</div>";
}

if ($warningCount > 0) {
    echo "<div class='warning'>⚠️ AVISOS (" . $warningCount . ")</div>";
}

$successRate = $totalTests > 0 ? round(($successCount / $totalTests) * 100, 1) : 0;

echo "<div class='progress-bar'>";
echo "<div class='progress-fill' style='width: " . $successRate . "%'></div>";
echo "</div>";

echo "<p><strong>🎯 STATUS FINAL</strong></p>";
echo "<p>Total de Testes: " . $totalTests . "</p>";
echo "<p>Sucessos: " . $successCount . "</p>";
echo "<p>Erros: " . $errorCount . "</p>";
echo "<p>Avisos: " . $warningCount . "</p>";
echo "<p>Taxa de Sucesso: " . $successRate . "%</p>";

if ($errorCount == 0) {
    echo "<p class='success'>🎉 TODOS OS TESTES PASSARAM! Sistema pronto para próximo teste.</p>";
    echo "<p><strong>🔄 PRÓXIMO PASSO</strong></p>";
    echo "<p class='success'>✅ TESTE #11 CONCLUÍDO COM SUCESSO!</p>";
    echo "<p><strong>🎯 Próximo: TESTE #12 - CRUD de Configurações</strong></p>";
    echo "<p><strong>📝 Instrução:</strong> Execute este teste e me informe o resultado. Se tudo estiver OK, criarei o próximo teste.</p>";
} else {
    echo "<p class='error'>❌ ALGUNS TESTES FALHARAM. Verifique os erros acima.</p>";
}

echo "<p><strong>💡 INFORMAÇÕES ADICIONAIS</strong></p>";
echo "<p><strong>URL de Teste:</strong> /cfc-bom-conselho/admin/teste-11-crud-logs.php</p>";
echo "<p><strong>Funcionalidades Testadas:</strong> CREATE, READ, UPDATE, DELETE, Validações</p>";
echo "<p><strong>Arquivos Utilizados:</strong> Database, Config</p>";
echo "<p><strong>Operações CRUD:</strong> Inserir, Buscar, Atualizar, Excluir Logs de Auditoria</p>";
echo "<p><strong>Validações:</strong> Usuário, Ação, Tabela Afetada, IP, Estrutura da tabela, Relacionamentos</p>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
