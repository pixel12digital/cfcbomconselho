<?php
/**
 * TESTE #23: Testes de Performance
 * Data/Hora: 19/08/2025 17:32:46
 * 
 * Este teste verifica a performance e otimização do sistema:
 * - Tempo de execução de consultas
 * - Uso de memória
 * - Otimização de índices
 * - Performance de JOINs
 * - Cache e otimizações
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
echo "<title>TESTE #23: Testes de Performance</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }";
echo ".container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo ".header { background: linear-gradient(135deg, #20c997 0%, #17a2b8 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; }";
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
echo ".progress-fill { height: 100%; background: linear-gradient(90deg, #20c997, #17a2b8); transition: width 0.3s ease; }";
echo ".performance-section { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 15px; margin-bottom: 15px; }";
echo ".metric { background: #e8f5e8; border: 1px solid #c3e6c3; border-radius: 6px; padding: 10px; margin: 5px 0; }";
echo ".metric strong { color: #155724; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<div class='header'>";
echo "<h1>🚀 TESTE #23: Testes de Performance</h1>";
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

// Função para medir tempo de execução
function measureExecutionTime($callback, $description = '') {
    $startTime = microtime(true);
    $startMemory = memory_get_usage();
    
    try {
        $result = $callback();
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $executionTime = round(($endTime - $startTime) * 1000, 2); // em milissegundos
        $memoryUsed = round(($endMemory - $startMemory) / 1024, 2); // em KB
        
        return [
            'success' => true,
            'execution_time' => $executionTime,
            'memory_used' => $memoryUsed,
            'result' => $result
        ];
    } catch (Exception $e) {
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $executionTime = round(($endTime - $startTime) * 1000, 2);
        $memoryUsed = round(($endMemory - $startMemory) / 1024, 2);
        
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'execution_time' => $executionTime,
            'memory_used' => $memoryUsed
        ];
    }
}

// 23.1 Inclusão de Arquivos Necessários
echo "<h2>23.1 Inclusão de Arquivos Necessários</h2>";

try {
    require_once '../includes/database.php';
    require_once '../includes/config.php';
    registerTest("Arquivos necessários", "success", "INCLUÍDOS COM SUCESSO");
} catch (Exception $e) {
    registerTest("Arquivos necessários", "error", "ERRO: " . $e->getMessage());
}

// 23.2 Conexão com Banco de Dados
echo "<h2>23.2 Conexão com Banco de Dados</h2>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    registerTest("Conexão PDO", "success", "ESTABELECIDA COM SUCESSO");
} catch (PDOException $e) {
    registerTest("Conexão PDO", "error", "ERRO: " . $e->getMessage());
    exit;
}

// 23.3 Teste 1: Performance de Consultas Simples
echo "<h2>23.3 Teste 1: Performance de Consultas Simples</h2>";

try {
    // Teste de SELECT simples
    $result = measureExecutionTime(function() use ($pdo) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM cfcs");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }, "SELECT COUNT(*) FROM cfcs");
    
    if ($result['success']) {
        $performance = "Tempo: {$result['execution_time']}ms | Memória: {$result['memory_used']}KB";
        registerTest("Consulta Simples - COUNT", "success", "EXECUTADA COM SUCESSO - {$performance}", [$result['result']]);
    } else {
        registerTest("Consulta Simples - COUNT", "error", "ERRO: " . $result['error']);
    }
    
    // Teste de SELECT com WHERE
    $result = measureExecutionTime(function() use ($pdo) {
        $stmt = $pdo->query("SELECT * FROM usuarios WHERE status = 'ativo'");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }, "SELECT * FROM usuarios WHERE status = 'ativo'");
    
    if ($result['success']) {
        $performance = "Tempo: {$result['execution_time']}ms | Memória: {$result['memory_used']}KB";
        registerTest("Consulta com WHERE", "success", "EXECUTADA COM SUCESSO - {$performance}", $result['result']);
    } else {
        registerTest("Consulta com WHERE", "error", "ERRO: " . $result['error']);
    }
    
} catch (Exception $e) {
    registerTest("Performance de Consultas Simples", "error", "ERRO: " . $e->getMessage());
}

// 23.4 Teste 2: Performance de JOINs
echo "<h2>23.4 Teste 2: Performance de JOINs</h2>";

try {
    // Teste de JOIN simples
    $result = measureExecutionTime(function() use ($pdo) {
        $stmt = $pdo->query("
            SELECT c.nome as cfc_nome, COUNT(a.id) as total_alunos
            FROM cfcs c
            LEFT JOIN alunos a ON c.id = a.cfc_id
            GROUP BY c.id, c.nome
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }, "JOIN CFCs com Alunos");
    
    if ($result['success']) {
        $performance = "Tempo: {$result['execution_time']}ms | Memória: {$result['memory_used']}KB";
        registerTest("JOIN Simples - CFCs + Alunos", "success", "EXECUTADO COM SUCESSO - {$performance}", $result['result']);
    } else {
        registerTest("JOIN Simples - CFCs + Alunos", "error", "ERRO: " . $result['error']);
    }
    
    // Teste de JOIN múltiplo
    $result = measureExecutionTime(function() use ($pdo) {
        $stmt = $pdo->query("
            SELECT 
                c.nome as cfc_nome,
                COUNT(DISTINCT i.id) as total_instrutores,
                COUNT(DISTINCT a.id) as total_alunos,
                COUNT(DISTINCT v.id) as total_veiculos
            FROM cfcs c
            LEFT JOIN instrutores i ON c.id = i.cfc_id
            LEFT JOIN alunos a ON c.id = a.cfc_id
            LEFT JOIN veiculos v ON c.id = v.cfc_id
            GROUP BY c.id, c.nome
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }, "JOIN Múltiplo - CFCs + Instrutores + Alunos + Veículos");
    
    if ($result['success']) {
        $performance = "Tempo: {$result['execution_time']}ms | Memória: {$result['memory_used']}KB";
        registerTest("JOIN Múltiplo - CFCs + Instrutores + Alunos + Veículos", "success", "EXECUTADO COM SUCESSO - {$performance}", $result['result']);
    } else {
        registerTest("JOIN Múltiplo - CFCs + Instrutores + Alunos + Veículos", "error", "ERRO: " . $result['error']);
    }
    
} catch (Exception $e) {
    registerTest("Performance de JOINs", "error", "ERRO: " . $e->getMessage());
}

// 23.5 Teste 3: Performance de Agregações
echo "<h2>23.5 Teste 3: Performance de Agregações</h2>";

try {
    // Teste de agregações simples
    $result = measureExecutionTime(function() use ($pdo) {
        $stmt = $pdo->query("
            SELECT 
                status,
                COUNT(*) as total,
                AVG(YEAR(CURDATE()) - YEAR(data_nascimento)) as idade_media
            FROM alunos
            GROUP BY status
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }, "Agregações - Alunos por Status");
    
    if ($result['success']) {
        $performance = "Tempo: {$result['execution_time']}ms | Memória: {$result['memory_used']}KB";
        registerTest("Agregações Simples - Alunos", "success", "EXECUTADAS COM SUCESSO - {$performance}", $result['result']);
    } else {
        registerTest("Agregações Simples - Alunos", "error", "ERRO: " . $result['error']);
    }
    
    // Teste de agregações complexas
    $result = measureExecutionTime(function() use ($pdo) {
        $stmt = $pdo->query("
            SELECT 
                c.nome as cfc_nome,
                COUNT(DISTINCT i.id) as instrutores,
                COUNT(DISTINCT a.id) as alunos,
                COUNT(DISTINCT v.id) as veiculos,
                SUM(CASE WHEN i.status = 'ativo' THEN 1 ELSE 0 END) as instrutores_ativos,
                SUM(CASE WHEN a.status = 'ativo' THEN 1 ELSE 0 END) as alunos_ativos,
                SUM(CASE WHEN v.status = 'ativo' THEN 1 ELSE 0 END) as veiculos_ativos
            FROM cfcs c
            LEFT JOIN instrutores i ON c.id = i.cfc_id
            LEFT JOIN alunos a ON c.id = a.cfc_id
            LEFT JOIN veiculos v ON c.id = v.cfc_id
            GROUP BY c.id, c.nome
            HAVING instrutores > 0 OR alunos > 0 OR veiculos > 0
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }, "Agregações Complexas - CFCs com Estatísticas");
    
    if ($result['success']) {
        $performance = "Tempo: {$result['execution_time']}ms | Memória: {$result['memory_used']}KB";
        registerTest("Agregações Complexas - CFCs", "success", "EXECUTADAS COM SUCESSO - {$performance}", $result['result']);
    } else {
        registerTest("Agregações Complexas - CFCs", "error", "ERRO: " . $result['error']);
    }
    
} catch (Exception $e) {
    registerTest("Performance de Agregações", "error", "ERRO: " . $e->getMessage());
}

// 23.6 Teste 4: Performance de Subconsultas
echo "<h2>23.6 Teste 4: Performance de Subconsultas</h2>";

try {
    // Teste de subconsulta EXISTS
    $result = measureExecutionTime(function() use ($pdo) {
        $stmt = $pdo->query("
            SELECT c.nome, c.cnpj
            FROM cfcs c
            WHERE EXISTS (
                SELECT 1 FROM alunos a WHERE a.cfc_id = c.id
            )
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }, "Subconsulta EXISTS - CFCs com Alunos");
    
    if ($result['success']) {
        $performance = "Tempo: {$result['execution_time']}ms | Memória: {$result['memory_used']}KB";
        registerTest("Subconsulta EXISTS", "success", "EXECUTADA COM SUCESSO - {$performance}", $result['result']);
    } else {
        registerTest("Subconsulta EXISTS", "error", "ERRO: " . $result['error']);
    }
    
    // Teste de subconsulta IN
    $result = measureExecutionTime(function() use ($pdo) {
        $stmt = $pdo->query("
            SELECT nome, cpf, status
            FROM instrutores
            WHERE cfc_id IN (
                SELECT id FROM cfcs WHERE status = 'ativo'
            )
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }, "Subconsulta IN - Instrutores de CFCs Ativos");
    
    if ($result['success']) {
        $performance = "Tempo: {$result['execution_time']}ms | Memória: {$result['memory_used']}KB";
        registerTest("Subconsulta IN", "success", "EXECUTADA COM SUCESSO - {$performance}", $result['result']);
    } else {
        registerTest("Subconsulta IN", "error", "ERRO: " . $result['error']);
    }
    
} catch (Exception $e) {
    registerTest("Performance de Subconsultas", "error", "ERRO: " . $e->getMessage());
}

// 23.7 Teste 5: Performance de Ordenação e Limite
echo "<h2>23.7 Teste 5: Performance de Ordenação e Limite</h2>";

try {
    // Teste de ORDER BY
    $result = measureExecutionTime(function() use ($pdo) {
        $stmt = $pdo->query("
            SELECT nome, cpf, status, criado_em
            FROM alunos
            ORDER BY criado_em DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }, "ORDER BY - Alunos por Data de Criação");
    
    if ($result['success']) {
        $performance = "Tempo: {$result['execution_time']}ms | Memória: {$result['memory_used']}KB";
        registerTest("ORDER BY - Alunos", "success", "EXECUTADO COM SUCESSO - {$performance}", array_slice($result['result'], 0, 5));
    } else {
        registerTest("ORDER BY - Alunos", "error", "ERRO: " . $result['error']);
    }
    
    // Teste de LIMIT
    $result = measureExecutionTime(function() use ($pdo) {
        $stmt = $pdo->query("
            SELECT nome, cpf, status
            FROM instrutores
            ORDER BY nome ASC
            LIMIT 10
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }, "LIMIT - Top 10 Instrutores");
    
    if ($result['success']) {
        $performance = "Tempo: {$result['execution_time']}ms | Memória: {$result['memory_used']}KB";
        registerTest("LIMIT - Top 10 Instrutores", "success", "EXECUTADO COM SUCESSO - {$performance}", $result['result']);
    } else {
        registerTest("LIMIT - Top 10 Instrutores", "error", "ERRO: " . $result['error']);
    }
    
} catch (Exception $e) {
    registerTest("Performance de Ordenação e Limite", "error", "ERRO: " . $e->getMessage());
}

// 23.8 Teste 6: Análise de Índices
echo "<h2>23.8 Teste 6: Análise de Índices</h2>";

try {
    // Verificar índices das tabelas principais
    $tables = ['cfcs', 'usuarios', 'instrutores', 'alunos', 'veiculos', 'aulas', 'sessoes', 'logs'];
    $indexesInfo = [];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW INDEX FROM " . $table);
        $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $indexesInfo[] = [
            'Tabela' => $table,
            'Total Índices' => count($indexes),
            'Índices' => implode(', ', array_unique(array_column($indexes, 'Key_name')))
        ];
    }
    
    registerTest("Análise de Índices", "success", "ÍNDICES ANALISADOS COM SUCESSO", $indexesInfo);
    
} catch (Exception $e) {
    registerTest("Análise de Índices", "error", "ERRO: " . $e->getMessage());
}

// 23.9 Teste 7: Performance de Múltiplas Consultas
echo "<h2>23.9 Teste 7: Performance de Múltiplas Consultas</h2>";

try {
    // Executar múltiplas consultas em sequência
    $queries = [
        "SELECT COUNT(*) as total FROM cfcs",
        "SELECT COUNT(*) as total FROM usuarios",
        "SELECT COUNT(*) as total FROM instrutores",
        "SELECT COUNT(*) as total FROM alunos",
        "SELECT COUNT(*) as total FROM veiculos"
    ];
    
    $totalTime = 0;
    $totalMemory = 0;
    $results = [];
    
    foreach ($queries as $index => $query) {
        $result = measureExecutionTime(function() use ($pdo, $query) {
            $stmt = $pdo->query($query);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }, "Consulta " . ($index + 1));
        
        if ($result['success']) {
            $totalTime += $result['execution_time'];
            $totalMemory += $result['memory_used'];
            $results[] = [
                'Consulta' => $query,
                'Tempo (ms)' => $result['execution_time'],
                'Memória (KB)' => $result['memory_used'],
                'Resultado' => $result['result']['total']
            ];
        }
    }
    
    $avgTime = round($totalTime / count($queries), 2);
    $avgMemory = round($totalMemory / count($queries), 2);
    
    registerTest("Múltiplas Consultas", "success", "EXECUTADAS COM SUCESSO - Tempo Médio: {$avgTime}ms | Memória Média: {$avgMemory}KB", $results);
    
} catch (Exception $e) {
    registerTest("Performance de Múltiplas Consultas", "error", "ERRO: " . $e->getMessage());
}

// 23.10 Teste 8: Resumo de Performance
echo "<h2>23.10 Teste 8: Resumo de Performance</h2>";

try {
    // Estatísticas gerais de performance
    $stats = [];
    
    // Verificar configurações do MySQL
    $stmt = $pdo->query("SHOW VARIABLES LIKE 'max_connections'");
    $maxConnections = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats[] = ['Configuração' => 'Max Connections', 'Valor' => $maxConnections['Value']];
    
    $stmt = $pdo->query("SHOW VARIABLES LIKE 'query_cache_size'");
    $queryCacheSize = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats[] = ['Configuração' => 'Query Cache Size', 'Valor' => $queryCacheSize['Value']];
    
    $stmt = $pdo->query("SHOW VARIABLES LIKE 'innodb_buffer_pool_size'");
    $innodbBufferPool = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats[] = ['Configuração' => 'InnoDB Buffer Pool', 'Valor' => $innodbBufferPool['Value']];
    
    // Status do servidor
    $stmt = $pdo->query("SHOW STATUS LIKE 'Threads_connected'");
    $threadsConnected = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats[] = ['Status' => 'Threads Conectados', 'Valor' => $threadsConnected['Value']];
    
    $stmt = $pdo->query("SHOW STATUS LIKE 'Queries'");
    $queries = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats[] = ['Status' => 'Total de Queries', 'Valor' => $queries['Value']];
    
    registerTest("Resumo de Performance", "success", "ESTATÍSTICAS COLETADAS COM SUCESSO", $stats);
    
} catch (Exception $e) {
    registerTest("Resumo de Performance", "error", "ERRO: " . $e->getMessage());
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
    echo "<p class='success'>✅ TESTE #23 CONCLUÍDO COM SUCESSO!</p>";
    echo "<p><strong>🎯 Próximo: TESTE #24 - Testes de Segurança</strong></p>";
    echo "<p><strong>📝 Instrução:</strong> Execute este teste e me informe o resultado. Se tudo estiver OK, criarei o próximo teste.</p>";
} else {
    echo "<p class='error'>❌ ALGUNS TESTES FALHARAM. Verifique os erros acima.</p>";
}

echo "<p><strong>💡 INFORMAÇÕES ADICIONAIS</strong></p>";
echo "<p><strong>URL de Teste:</strong> /cfc-bom-conselho/admin/teste-23-testes-performance.php</p>";
echo "<p><strong>Funcionalidades Testadas:</strong> Performance, Otimização, Consultas, JOINs, Agregações</p>";
echo "<p><strong>Arquivos Utilizados:</strong> Database, Config</p>";
echo "<p><strong>Métricas:</strong> Tempo de Execução, Uso de Memória, Índices, Configurações MySQL</p>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
