<?php
/**
 * TESTE #21: CRUD de Relatórios
 * Data/Hora: 19/08/2025 17:19:26
 * 
 * Este teste verifica funcionalidades de relatórios usando as tabelas existentes:
 * - Relatórios de Alunos por CFC
 * - Relatórios de Aulas por Período
 * - Relatórios de Instrutores
 * - Relatórios de Veículos
 * - Estatísticas Gerais do Sistema
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
echo "<title>TESTE #21: CRUD de Relatórios</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }";
echo ".container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo ".header { background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; }";
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
echo ".progress-fill { height: 100%; background: linear-gradient(90deg, #6f42c1, #e83e8c); transition: width 0.3s ease; }";
echo ".report-section { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 15px; margin-bottom: 15px; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<div class='header'>";
echo "<h1>🔍 TESTE #21: CRUD de Relatórios</h1>";
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

// 21.1 Inclusão de Arquivos Necessários
echo "<h2>21.1 Inclusão de Arquivos Necessários</h2>";

try {
    require_once '../includes/database.php';
    require_once '../includes/config.php';
    registerTest("Arquivos necessários", "success", "INCLUÍDOS COM SUCESSO");
} catch (Exception $e) {
    registerTest("Arquivos necessários", "error", "ERRO: " . $e->getMessage());
}

// 21.2 Conexão com Banco de Dados
echo "<h2>21.2 Conexão com Banco de Dados</h2>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    registerTest("Conexão PDO", "success", "ESTABELECIDA COM SUCESSO");
} catch (PDOException $e) {
    registerTest("Conexão PDO", "error", "ERRO: " . $e->getMessage());
    exit;
}

// 21.3 Verificação de Dados Disponíveis
echo "<h2>21.3 Verificação de Dados Disponíveis</h2>";

try {
    // Verificar total de registros em cada tabela
    $tables = ['cfcs', 'usuarios', 'instrutores', 'alunos', 'veiculos', 'aulas', 'sessoes', 'logs'];
    $tableCounts = [];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM " . $table);
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        $tableCounts[$table] = $count['total'];
    }
    
    registerTest("Contagem de registros", "success", "DADOS VERIFICADOS COM SUCESSO", [
        ['Tabela' => 'CFCs', 'Total' => $tableCounts['cfcs']],
        ['Tabela' => 'Usuários', 'Total' => $tableCounts['usuarios']],
        ['Tabela' => 'Instrutores', 'Total' => $tableCounts['instrutores']],
        ['Tabela' => 'Alunos', 'Total' => $tableCounts['alunos']],
        ['Tabela' => 'Veículos', 'Total' => $tableCounts['veiculos']],
        ['Tabela' => 'Aulas', 'Total' => $tableCounts['aulas']],
        ['Tabela' => 'Sessões', 'Total' => $tableCounts['sessoes']],
        ['Tabela' => 'Logs', 'Total' => $tableCounts['logs']]
    ]);
    
} catch (Exception $e) {
    registerTest("Verificação de dados", "error", "ERRO: " . $e->getMessage());
}

// 21.4 Relatório 1: Alunos por CFC
echo "<h2>21.4 Relatório 1: Alunos por CFC</h2>";

try {
    $stmt = $pdo->query("
        SELECT 
            c.nome as cfc_nome,
            COUNT(a.id) as total_alunos,
            SUM(CASE WHEN a.status = 'ativo' THEN 1 ELSE 0 END) as alunos_ativos,
            SUM(CASE WHEN a.status = 'inativo' THEN 1 ELSE 0 END) as alunos_inativos,
            SUM(CASE WHEN a.status = 'concluido' THEN 1 ELSE 0 END) as alunos_concluidos
        FROM cfcs c
        LEFT JOIN alunos a ON c.id = a.cfc_id
        GROUP BY c.id, c.nome
        ORDER BY total_alunos DESC
    ");
    
    $alunosPorCfc = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($alunosPorCfc)) {
        registerTest("Relatório Alunos por CFC", "success", "RELATÓRIO GERADO COM SUCESSO", $alunosPorCfc);
    } else {
        registerTest("Relatório Alunos por CFC", "warning", "NENHUM DADO ENCONTRADO");
    }
    
} catch (Exception $e) {
    registerTest("Relatório Alunos por CFC", "error", "ERRO: " . $e->getMessage());
}

// 21.5 Relatório 2: Aulas por Período
echo "<h2>21.5 Relatório 2: Aulas por Período</h2>";

try {
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(data_aula, '%Y-%m') as periodo,
            COUNT(*) as total_aulas,
            SUM(CASE WHEN status = 'agendada' THEN 1 ELSE 0 END) as agendadas,
            SUM(CASE WHEN status = 'em_andamento' THEN 1 ELSE 0 END) as em_andamento,
            SUM(CASE WHEN status = 'concluida' THEN 1 ELSE 0 END) as concluidas,
            SUM(CASE WHEN status = 'cancelada' THEN 1 ELSE 0 END) as canceladas,
            SUM(CASE WHEN tipo_aula = 'teorica' THEN 1 ELSE 0 END) as teoricas,
            SUM(CASE WHEN tipo_aula = 'pratica' THEN 1 ELSE 0 END) as praticas
        FROM aulas
        WHERE data_aula >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(data_aula, '%Y-%m')
        ORDER BY periodo DESC
    ");
    
    $aulasPorPeriodo = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($aulasPorPeriodo)) {
        registerTest("Relatório Aulas por Período", "success", "RELATÓRIO GERADO COM SUCESSO", $aulasPorPeriodo);
    } else {
        registerTest("Relatório Aulas por Período", "warning", "NENHUM DADO ENCONTRADO");
    }
    
} catch (Exception $e) {
    registerTest("Relatório Aulas por Período", "error", "ERRO: " . $e->getMessage());
}

// 21.6 Relatório 3: Instrutores por CFC
echo "<h2>21.6 Relatório 3: Instrutores por CFC</h2>";

try {
    $stmt = $pdo->query("
        SELECT 
            c.nome as cfc_nome,
            COUNT(i.id) as total_instrutores,
            SUM(CASE WHEN i.status = 'ativo' THEN 1 ELSE 0 END) as instrutores_ativos,
            SUM(CASE WHEN i.status = 'inativo' THEN 1 ELSE 0 END) as instrutores_inativos,
            GROUP_CONCAT(DISTINCT i.categoria_habilitacao SEPARATOR ', ') as categorias
        FROM cfcs c
        LEFT JOIN instrutores i ON c.id = i.cfc_id
        GROUP BY c.id, c.nome
        ORDER BY total_instrutores DESC
    ");
    
    $instrutoresPorCfc = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($instrutoresPorCfc)) {
        registerTest("Relatório Instrutores por CFC", "success", "RELATÓRIO GERADO COM SUCESSO", $instrutoresPorCfc);
    } else {
        registerTest("Relatório Instrutores por CFC", "warning", "NENHUM DADO ENCONTRADO");
    }
    
} catch (Exception $e) {
    registerTest("Relatório Instrutores por CFC", "error", "ERRO: " . $e->getMessage());
}

// 21.7 Relatório 4: Veículos por CFC
echo "<h2>21.7 Relatório 4: Veículos por CFC</h2>";

try {
    $stmt = $pdo->query("
        SELECT 
            c.nome as cfc_nome,
            COUNT(v.id) as total_veiculos,
            SUM(CASE WHEN v.status = 'ativo' THEN 1 ELSE 0 END) as veiculos_ativos,
            SUM(CASE WHEN v.status = 'inativo' THEN 1 ELSE 0 END) as veiculos_inativos,
            GROUP_CONCAT(DISTINCT v.categoria_cnh SEPARATOR ', ') as categorias_cnh
        FROM cfcs c
        LEFT JOIN veiculos v ON c.id = v.cfc_id
        GROUP BY c.id, c.nome
        ORDER BY total_veiculos DESC
    ");
    
    $veiculosPorCfc = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($veiculosPorCfc)) {
        registerTest("Relatório Veículos por CFC", "success", "RELATÓRIO GERADO COM SUCESSO", $veiculosPorCfc);
    } else {
        registerTest("Relatório Veículos por CFC", "warning", "NENHUM DADO ENCONTRADO");
    }
    
} catch (Exception $e) {
    registerTest("Relatório Veículos por CFC", "error", "ERRO: " . $e->getMessage());
}

// 21.8 Relatório 5: Estatísticas de Usuários
echo "<h2>21.8 Relatório 5: Estatísticas de Usuários</h2>";

try {
    $stmt = $pdo->query("
        SELECT 
            tipo,
            COUNT(*) as total,
            SUM(CASE WHEN status = 'ativo' THEN 1 ELSE 0 END) as ativos,
            SUM(CASE WHEN status = 'inativo' THEN 1 ELSE 0 END) as inativos,
            MAX(ultimo_login) as ultimo_acesso
        FROM usuarios
        GROUP BY tipo
        ORDER BY total DESC
    ");
    
    $estatisticasUsuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($estatisticasUsuarios)) {
        registerTest("Estatísticas de Usuários", "success", "RELATÓRIO GERADO COM SUCESSO", $estatisticasUsuarios);
    } else {
        registerTest("Estatísticas de Usuários", "warning", "NENHUM DADO ENCONTRADO");
    }
    
} catch (Exception $e) {
    registerTest("Estatísticas de Usuários", "error", "ERRO: " . $e->getMessage());
}

// 21.9 Relatório 6: Logs de Atividade
echo "<h2>21.9 Relatório 6: Logs de Atividade</h2>";

try {
    $stmt = $pdo->query("
        SELECT 
            acao,
            COUNT(*) as total,
            COUNT(DISTINCT usuario_id) as usuarios_unicos,
            COUNT(DISTINCT DATE(criado_em)) as dias_ativos,
            MAX(criado_em) as ultima_acao
        FROM logs
        WHERE criado_em >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY acao
        ORDER BY total DESC
        LIMIT 10
    ");
    
    $logsAtividade = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($logsAtividade)) {
        registerTest("Logs de Atividade", "success", "RELATÓRIO GERADO COM SUCESSO", $logsAtividade);
    } else {
        registerTest("Logs de Atividade", "warning", "NENHUM DADO ENCONTRADO");
    }
    
} catch (Exception $e) {
    registerTest("Logs de Atividade", "error", "ERRO: " . $e->getMessage());
}

// 21.10 Relatório 7: Resumo Geral do Sistema
echo "<h2>21.10 Relatório 7: Resumo Geral do Sistema</h2>";

try {
    // Estatísticas gerais
    $stats = [];
    
    // Total de CFCs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cfcs WHERE status = 'ativo'");
    $cfcsAtivos = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats[] = ['Métrica' => 'CFCs Ativos', 'Valor' => $cfcsAtivos['total']];
    
    // Total de usuários ativos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE status = 'ativo'");
    $usuariosAtivos = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats[] = ['Métrica' => 'Usuários Ativos', 'Valor' => $usuariosAtivos['total']];
    
    // Total de instrutores ativos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM instrutores WHERE status = 'ativo'");
    $instrutoresAtivos = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats[] = ['Métrica' => 'Instrutores Ativos', 'Valor' => $instrutoresAtivos['total']];
    
    // Total de alunos ativos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM alunos WHERE status = 'ativo'");
    $alunosAtivos = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats[] = ['Métrica' => 'Alunos Ativos', 'Valor' => $alunosAtivos['total']];
    
    // Total de veículos ativos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM veiculos WHERE status = 'ativo'");
    $veiculosAtivos = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats[] = ['Métrica' => 'Veículos Ativos', 'Valor' => $veiculosAtivos['total']];
    
    // Total de aulas este mês
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM aulas WHERE MONTH(data_aula) = MONTH(CURDATE()) AND YEAR(data_aula) = YEAR(CURDATE())");
    $aulasMes = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats[] = ['Métrica' => 'Aulas Este Mês', 'Valor' => $aulasMes['total']];
    
    // Total de sessões hoje
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM sessoes WHERE DATE(criado_em) = CURDATE()");
    $sessoesHoje = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats[] = ['Métrica' => 'Sessões Hoje', 'Valor' => $sessoesHoje['total']];
    
    registerTest("Resumo Geral do Sistema", "success", "RELATÓRIO GERADO COM SUCESSO", $stats);
    
} catch (Exception $e) {
    registerTest("Resumo Geral do Sistema", "error", "ERRO: " . $e->getMessage());
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
    echo "<p class='success'>✅ TESTE #21 CONCLUÍDO COM SUCESSO!</p>";
    echo "<p><strong>🎯 Próximo: TESTE #22 - Validações de Integridade</strong></p>";
    echo "<p><strong>📝 Instrução:</strong> Execute este teste e me informe o resultado. Se tudo estiver OK, criarei o próximo teste.</p>";
} else {
    echo "<p class='error'>❌ ALGUNS TESTES FALHARAM. Verifique os erros acima.</p>";
}

echo "<p><strong>💡 INFORMAÇÕES ADICIONAIS</strong></p>";
echo "<p><strong>URL de Teste:</strong> /cfc-bom-conselho/admin/teste-21-crud-relatorios.php</p>";
echo "<p><strong>Funcionalidades Testadas:</strong> Relatórios, Estatísticas, Consultas Complexas</p>";
echo "<p><strong>Arquivos Utilizados:</strong> Database, Config</p>";
echo "<p><strong>Tipos de Relatórios:</strong> Alunos por CFC, Aulas por Período, Instrutores, Veículos, Usuários, Logs, Resumo Geral</p>";
echo "<p><strong>Validações:</strong> Consultas SQL, Agregações, Filtros, Ordenação, Estrutura dos dados</p>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
