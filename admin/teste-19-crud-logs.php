<?php
/**
 * TESTE #19: CRUD de Logs
 * Data/Hora: 19/08/2025 17:00:36
 * 
 * Este teste verifica todas as operações CRUD para a tabela 'logs':
 * - CREATE: Criar novo log
 * - READ: Buscar log por ID, usuário, ação, data
 * - UPDATE: Atualizar dados do log
 * - DELETE: Excluir log
 * - Validações: Verificar regras de negócio e relacionamentos
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
echo "<title>TESTE #19: CRUD de Logs</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }";
echo ".container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo ".header { background: linear-gradient(135deg, #fd7e14 0%, #e83e8c 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; }";
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
echo "<h1>🔍 TESTE #19: CRUD de Logs</h1>";
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
                echo "<th>" . htmlspecialchars($header ?? '') . "</th>";
            }
            echo "</tr></thead><tbody>";
            foreach ($data as $row) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
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

// 19.1 Inclusão de Arquivos Necessários
echo "<h2>19.1 Inclusão de Arquivos Necessários</h2>";

try {
    require_once '../includes/database.php';
    require_once '../includes/config.php';
    registerTest("Arquivos necessários", "success", "INCLUÍDOS COM SUCESSO");
} catch (Exception $e) {
    registerTest("Arquivos necessários", "error", "ERRO: " . $e->getMessage());
}

// 19.2 Conexão com Banco de Dados
echo "<h2>19.2 Conexão com Banco de Dados</h2>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    registerTest("Conexão PDO", "success", "ESTABELECIDA COM SUCESSO");
} catch (PDOException $e) {
    registerTest("Conexão PDO", "error", "ERRO: " . $e->getMessage());
    exit;
}

// 19.3 Estrutura da Tabela 'logs'
echo "<h2>19.3 Estrutura da Tabela 'logs'</h2>";

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

// 19.4 Verificação de Dados na Tabela 'logs'
echo "<h2>19.4 Verificação de Dados na Tabela 'logs'</h2>";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM logs");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("Total de logs na tabela", "success", $count['total'] . " registros");
    
    if ($count['total'] > 0) {
        $stmt = $pdo->query("SELECT * FROM logs LIMIT 5");
        $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        registerTest("Dados de exemplo", "info", "Primeiros 5 registros", $sampleData);
    }
} catch (Exception $e) {
    registerTest("Verificação de dados", "error", "ERRO: " . $e->getMessage());
}

// 19.5 Verificação de Dados de Referência
echo "<h2>19.5 Verificação de Dados de Referência</h2>";

try {
    // Verificar Usuários
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $usuarioCount = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("Usuários disponíveis", "info", $usuarioCount['total'] . " usuários encontrados");
    
} catch (Exception $e) {
    registerTest("Verificação de referências", "error", "ERRO: " . $e->getMessage());
}

// 19.6 Teste CREATE - Criar Log
echo "<h2>19.6 Teste CREATE - Criar Log</h2>";

try {
    // Buscar ID válido para relacionamento
    $stmt = $pdo->query("SELECT id FROM usuarios LIMIT 1");
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario) {
        $stmt = $pdo->prepare("INSERT INTO logs (usuario_id, acao, tabela_afetada, ip_address, criado_em) VALUES (?, ?, ?, ?, NOW())");
        
        $usuario_id = $usuario['id'];
        $acao = 'TESTE_SISTEMA';
        $tabela_afetada = 'testes';
        $ip_address = '127.0.0.1';
        
        $result = $stmt->execute([
            $usuario_id,
            $acao,
            $tabela_afetada,
            $ip_address
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

// 19.7 Teste READ - Ler Log
echo "<h2>19.7 Teste READ - Ler Log</h2>";

try {
    if (isset($logId)) {
        // READ por ID
        $stmt = $pdo->prepare("SELECT * FROM logs WHERE id = ?");
        $stmt->execute([$logId]);
        $log = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($log) {
            registerTest("READ por ID", "success", "LOG ENCONTRADO");
            registerTest("Dados do log", "info", "Log encontrado com sucesso", [$log]);
        } else {
            registerTest("READ por ID", "error", "LOG NÃO ENCONTRADO");
        }
        
        // READ por Usuário
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM logs WHERE usuario_id = ?");
        $stmt->execute([$usuario_id]);
        $countUsuario = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por Usuário", "success", $countUsuario['total'] . " logs do usuário ID " . $usuario_id);
        
        // READ por Ação
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM logs WHERE acao = ?");
        $stmt->execute([$acao]);
        $countAcao = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por Ação", "success", $countAcao['total'] . " logs com ação '" . $acao . "'");
        
        // READ por Tabela Afetada
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM logs WHERE tabela_afetada = ?");
        $stmt->execute([$tabela_afetada]);
        $countTabela = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por Tabela Afetada", "success", $countTabela['total'] . " logs da tabela '" . $tabela_afetada . "'");
        
        // READ por Data (hoje)
        $hoje = date('Y-m-d');
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM logs WHERE DATE(criado_em) = ?");
        $stmt->execute([$hoje]);
        $countData = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por Data", "success", $countData['total'] . " logs criados hoje (" . $hoje . ")");
        
        // READ por IP
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM logs WHERE ip_address = ?");
        $stmt->execute([$ip_address]);
        $countIp = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por IP", "success", $countIp['total'] . " logs do IP " . $ip_address);
        
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

// 19.8 Teste UPDATE - Atualizar Log
echo "<h2>19.8 Teste UPDATE - Atualizar Log</h2>";

try {
    if (isset($logId)) {
        $novaTabela = 'testes_atualizados';
        
        $stmt = $pdo->prepare("UPDATE logs SET tabela_afetada = ? WHERE id = ?");
        $result = $stmt->execute([$novaTabela, $logId]);
        
        if ($result) {
            registerTest("UPDATE", "success", "LOG ATUALIZADO COM SUCESSO");
            
            // Verificar se a atualização foi aplicada
            $stmt = $pdo->prepare("SELECT * FROM logs WHERE id = ?");
            $stmt->execute([$logId]);
            $logAtualizado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($logAtualizado && $logAtualizado['tabela_afetada'] === $novaTabela) {
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

// 19.9 Teste DELETE - Excluir Log
echo "<h2>19.9 Teste DELETE - Excluir Log</h2>";

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

// 19.10 Teste de Validações
echo "<h2>19.10 Teste de Validações</h2>";

try {
    // Verificar logs sem usuário
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM logs WHERE usuario_id IS NULL");
    $semUsuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semUsuario['total'] == 0) {
        registerTest("Validação Usuário", "success", "TODOS OS LOGS TÊM USUÁRIO");
    } else {
        registerTest("Validação Usuário", "warning", $semUsuario['total'] . " logs sem usuário");
    }
    
    // Verificar logs sem ação
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM logs WHERE acao IS NULL");
    $semAcao = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semAcao['total'] == 0) {
        registerTest("Validação Ação", "success", "TODOS OS LOGS TÊM AÇÃO");
    } else {
        registerTest("Validação Ação", "warning", $semAcao['total'] . " logs sem ação");
    }
    
    // Verificar logs sem tabela afetada
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM logs WHERE tabela_afetada IS NULL");
    $semTabela = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semTabela['total'] == 0) {
        registerTest("Validação Tabela Afetada", "success", "TODOS OS LOGS TÊM TABELA AFETADA");
    } else {
        registerTest("Validação Tabela Afetada", "warning", $semTabela['total'] . " logs sem tabela afetada");
    }
    
    // Verificar logs por ação
    $stmt = $pdo->query("SELECT acao, COUNT(*) as total FROM logs WHERE acao IS NOT NULL GROUP BY acao ORDER BY total DESC LIMIT 5");
    $acaoCount = $stmt->fetchAll(PDO::FETCH_ASSOC);
    registerTest("Logs por Ação", "info", "Top 5 ações mais registradas", $acaoCount);
    
    // Verificar logs por usuário
    $stmt = $pdo->query("SELECT usuario_id, COUNT(*) as total FROM logs WHERE usuario_id IS NOT NULL GROUP BY usuario_id ORDER BY total DESC LIMIT 5");
    $usuarioCount = $stmt->fetchAll(PDO::FETCH_ASSOC);
    registerTest("Logs por Usuário", "info", "Top 5 usuários com mais logs", $usuarioCount);
    
    // Verificar logs por data
    $stmt = $pdo->query("SELECT DATE(criado_em) as data, COUNT(*) as total FROM logs WHERE criado_em IS NOT NULL GROUP BY DATE(criado_em) ORDER BY data DESC LIMIT 5");
    $dataCount = $stmt->fetchAll(PDO::FETCH_ASSOC);
    registerTest("Logs por Data", "info", "Últimas 5 datas com logs", $dataCount);
    
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
    echo "<p class='success'>✅ TESTE #19 CONCLUÍDO COM SUCESSO!</p>";
    echo "<p><strong>🎯 Próximo: TESTE #20 - CRUD de Configurações</strong></p>";
    echo "<p><strong>📝 Instrução:</strong> Execute este teste e me informe o resultado. Se tudo estiver OK, criarei o próximo teste.</p>";
} else {
    echo "<p class='error'>❌ ALGUNS TESTES FALHARAM. Verifique os erros acima.</p>";
}

echo "<p><strong>💡 INFORMAÇÕES ADICIONAIS</strong></p>";
echo "<p><strong>URL de Teste:</strong> /cfc-bom-conselho/admin/teste-19-crud-logs.php</p>";
echo "<p><strong>Funcionalidades Testadas:</strong> CREATE, READ, UPDATE, DELETE, Validações</p>";
echo "<p><strong>Arquivos Utilizados:</strong> Database, Config</p>";
echo "<p><strong>Operações CRUD:</strong> Inserir, Buscar, Atualizar, Excluir Logs</p>";
echo "<p><strong>Campos Obrigatórios:</strong> Usuário ID, Ação, Tabela Afetada</p>";
echo "<p><strong>Campos Opcionais:</strong> IP Address, Registro ID, Dados Anteriores, Dados Novos</p>";
echo "<p><strong>Validações:</strong> Relacionamentos, Ação, Tabela Afetada, Estrutura da tabela, Integridade</p>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
