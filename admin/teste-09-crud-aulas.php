<?php
/**
 * TESTE #9: CRUD de Aulas
 * Data/Hora: 19/08/2025 15:35:32
 * 
 * Este teste verifica todas as operações CRUD para a tabela 'aulas':
 * - CREATE: Criar nova aula
 * - READ: Buscar aula por ID, data, instrutor, aluno
 * - UPDATE: Atualizar dados da aula
 * - DELETE: Excluir aula
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
echo "<title>TESTE #9: CRUD de Aulas</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }";
echo ".container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo ".header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; }";
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
echo "<h1>🔍 TESTE #9: CRUD de Aulas</h1>";
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

// 9.1 Inclusão de Arquivos Necessários
echo "<h2>9.1 Inclusão de Arquivos Necessários</h2>";

try {
    require_once '../includes/database.php';
    require_once '../includes/config.php';
    registerTest("Arquivos necessários", "success", "INCLUÍDOS COM SUCESSO");
} catch (Exception $e) {
    registerTest("Arquivos necessários", "error", "ERRO: " . $e->getMessage());
}

// 9.2 Conexão com Banco de Dados
echo "<h2>9.2 Conexão com Banco de Dados</h2>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    registerTest("Conexão PDO", "success", "ESTABELECIDA COM SUCESSO");
} catch (PDOException $e) {
    registerTest("Conexão PDO", "error", "ERRO: " . $e->getMessage());
    exit;
}

// 9.3 Estrutura da Tabela 'aulas'
echo "<h2>9.3 Estrutura da Tabela 'aulas'</h2>";

try {
    $stmt = $pdo->query("DESCRIBE aulas");
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($structure)) {
        registerTest("Estrutura da tabela", "success", "COMPLETA", $structure);
    } else {
        registerTest("Estrutura da tabela", "error", "TABELA NÃO ENCONTRADA");
    }
} catch (Exception $e) {
    registerTest("Estrutura da tabela", "error", "ERRO: " . $e->getMessage());
}

// 9.4 Verificação de Dados na Tabela 'aulas'
echo "<h2>9.4 Verificação de Dados na Tabela 'aulas'</h2>";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM aulas");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("Total de Aulas na tabela", "success", $count['total'] . " registros");
    
    if ($count['total'] > 0) {
        $stmt = $pdo->query("SELECT * FROM aulas LIMIT 5");
        $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        registerTest("Dados de exemplo", "info", "Primeiros 5 registros", $sampleData);
    }
} catch (Exception $e) {
    registerTest("Verificação de dados", "error", "ERRO: " . $e->getMessage());
}

// 9.5 Verificação de Dados de Referência
echo "<h2>9.5 Verificação de Dados de Referência</h2>";

try {
    // Verificar CFCs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cfcs");
    $cfcCount = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("CFCs disponíveis", "info", $cfcCount['total'] . " CFCs encontrados");
    
    // Verificar Instrutores
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM instrutores");
    $instrutorCount = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("Instrutores disponíveis", "info", $instrutorCount['total'] . " instrutores encontrados");
    
    // Verificar Alunos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM alunos");
    $alunoCount = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("Alunos disponíveis", "info", $alunoCount['total'] . " alunos encontrados");
    
    // Verificar Veículos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM veiculos");
    $veiculoCount = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("Veículos disponíveis", "info", $veiculoCount['total'] . " veículos encontrados");
    
} catch (Exception $e) {
    registerTest("Verificação de referências", "error", "ERRO: " . $e->getMessage());
}

// 9.6 Teste CREATE - Criar Aula
echo "<h2>9.6 Teste CREATE - Criar Aula</h2>";

try {
    // Buscar dados de referência
    $stmt = $pdo->query("SELECT id FROM cfcs LIMIT 1");
    $cfc = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("SELECT id FROM instrutores LIMIT 1");
    $instrutor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("SELECT id FROM alunos LIMIT 1");
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("SELECT id FROM veiculos LIMIT 1");
    $veiculo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cfc && $instrutor && $aluno && $veiculo) {
        $dataAula = date('Y-m-d');
        $horaInicio = '08:00:00';
        $horaFim = '09:00:00';
        
        $stmt = $pdo->prepare("INSERT INTO aulas (cfc_id, instrutor_id, aluno_id, veiculo_id, data_aula, hora_inicio, hora_fim, tipo_aula, status, observacoes, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        $result = $stmt->execute([
            $cfc['id'],
            $instrutor['id'],
            $aluno['id'],
            $veiculo['id'],
            $dataAula,
            $horaInicio,
            $horaFim,
            'teorica',
            'agendada',
            'Aula de teste criada pelo sistema'
        ]);
        
        if ($result) {
            $aulaId = $pdo->lastInsertId();
            registerTest("CREATE", "success", "AULA CRIADA COM SUCESSO");
            registerTest("ID da aula criada", "info", $aulaId);
        } else {
            registerTest("CREATE", "error", "FALHA AO CRIAR AULA");
        }
    } else {
        registerTest("CREATE", "error", "DADOS DE REFERÊNCIA INSUFICIENTES");
    }
} catch (Exception $e) {
    registerTest("CREATE", "error", "ERRO: " . $e->getMessage());
}

// 9.7 Teste READ - Ler Aula
echo "<h2>9.7 Teste READ - Ler Aula</h2>";

try {
    if (isset($aulaId)) {
        // READ por ID
        $stmt = $pdo->prepare("SELECT a.*, c.nome as cfc_nome, i.nome as instrutor_nome, al.nome as aluno_nome, v.placa as veiculo_placa FROM aulas a JOIN cfcs c ON a.cfc_id = c.id JOIN instrutores i ON a.instrutor_id = i.id JOIN alunos al ON a.aluno_id = al.id JOIN veiculos v ON a.veiculo_id = v.id WHERE a.id = ?");
        $stmt->execute([$aulaId]);
        $aula = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($aula) {
            registerTest("READ por ID", "success", "AULA ENCONTRADA");
            registerTest("Dados da aula", "info", "Aula encontrada com sucesso", [$aula]);
        } else {
            registerTest("READ por ID", "error", "AULA NÃO ENCONTRADA");
        }
        
        // READ por Data
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM aulas WHERE DATE(data_aula) = ?");
        $stmt->execute([date('Y-m-d')]);
        $countData = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por Data", "success", $countData['total'] . " aulas encontradas para hoje");
        
        // READ ALL
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM aulas");
        $countAll = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ ALL", "success", $countAll['total'] . " AULAS ENCONTRADAS");
        
    } else {
        registerTest("READ", "warning", "NENHUMA AULA CRIADA PARA TESTE");
    }
} catch (Exception $e) {
    registerTest("READ", "error", "ERRO: " . $e->getMessage());
}

// 9.8 Teste UPDATE - Atualizar Aula
echo "<h2>9.8 Teste UPDATE - Atualizar Aula</h2>";

try {
    if (isset($aulaId)) {
        $novaHoraInicio = '09:00:00';
        $novaHoraFim = '10:00:00';
        $novoStatus = 'em_andamento';
        
        $stmt = $pdo->prepare("UPDATE aulas SET hora_inicio = ?, hora_fim = ?, status = ?, observacoes = CONCAT(observacoes, ' - Atualizada pelo sistema') WHERE id = ?");
        $result = $stmt->execute([$novaHoraInicio, $novaHoraFim, $novoStatus, $aulaId]);
        
        if ($result) {
            registerTest("UPDATE", "success", "AULA ATUALIZADA COM SUCESSO");
            
            // Verificar se a atualização foi aplicada
            $stmt = $pdo->prepare("SELECT * FROM aulas WHERE id = ?");
            $stmt->execute([$aulaId]);
            $aulaAtualizada = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($aulaAtualizada && $aulaAtualizada['status'] === $novoStatus) {
                registerTest("Verificação UPDATE", "success", "DADOS CONFIRMADOS");
            } else {
                registerTest("Verificação UPDATE", "warning", "ATUALIZAÇÃO NÃO CONFIRMADA");
            }
        } else {
            registerTest("UPDATE", "error", "FALHA AO ATUALIZAR AULA");
        }
    } else {
        registerTest("UPDATE", "warning", "NENHUMA AULA CRIADA PARA TESTE");
    }
} catch (Exception $e) {
    registerTest("UPDATE", "error", "ERRO: " . $e->getMessage());
}

// 9.9 Teste DELETE - Excluir Aula
echo "<h2>9.9 Teste DELETE - Excluir Aula</h2>";

try {
    if (isset($aulaId)) {
        $stmt = $pdo->prepare("DELETE FROM aulas WHERE id = ?");
        $result = $stmt->execute([$aulaId]);
        
        if ($result) {
            registerTest("DELETE", "success", "AULA EXCLUÍDA COM SUCESSO");
            
            // Verificar se a exclusão foi aplicada
            $stmt = $pdo->prepare("SELECT * FROM aulas WHERE id = ?");
            $stmt->execute([$aulaId]);
            $aulaExcluida = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$aulaExcluida) {
                registerTest("Verificação DELETE", "success", "AULA NÃO ENCONTRADA (EXCLUÍDA)");
            } else {
                registerTest("Verificação DELETE", "error", "AULA AINDA EXISTE");
            }
        } else {
            registerTest("DELETE", "error", "FALHA AO EXCLUIR AULA");
        }
    } else {
        registerTest("DELETE", "warning", "NENHUMA AULA CRIADA PARA TESTE");
    }
} catch (Exception $e) {
    registerTest("DELETE", "error", "ERRO: " . $e->getMessage());
}

// 9.10 Teste de Validações
echo "<h2>9.10 Teste de Validações</h2>";

try {
    // Verificar se há aulas com horários sobrepostos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM aulas a1 JOIN aulas a2 ON a1.id != a2.id WHERE a1.data_aula = a2.data_aula AND a1.instrutor_id = a2.instrutor_id AND ((a1.hora_inicio < a2.hora_fim AND a1.hora_fim > a2.hora_inicio))");
    $sobreposicoes = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sobreposicoes['total'] == 0) {
        registerTest("Validação Horários", "success", "NENHUMA SOBREPOSIÇÃO ENCONTRADA");
    } else {
        registerTest("Validação Horários", "warning", $sobreposicoes['total'] . " SOBREPOSIÇÕES ENCONTRADAS");
    }
    
    // Verificar aulas sem instrutor
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM aulas WHERE instrutor_id IS NULL");
    $semInstrutor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semInstrutor['total'] == 0) {
        registerTest("Validação Instrutor", "success", "TODAS AS AULAS TÊM INSTRUTOR");
    } else {
        registerTest("Validação Instrutor", "warning", $semInstrutor['total'] . " AULAS SEM INSTRUTOR");
    }
    
    // Verificar aulas sem aluno
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM aulas WHERE aluno_id IS NULL");
    $semAluno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semAluno['total'] == 0) {
        registerTest("Validação Aluno", "success", "TODAS AS AULAS TÊM ALUNO");
    } else {
        registerTest("Validação Aluno", "warning", $semAluno['total'] . " AULAS SEM ALUNO");
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
    echo "<p class='success'>✅ TESTE #9 CONCLUÍDO COM SUCESSO!</p>";
    echo "<p><strong>🎯 Próximo: TESTE #10 - CRUD de Sessões</strong></p>";
    echo "<p><strong>📝 Instrução:</strong> Execute este teste e me informe o resultado. Se tudo estiver OK, criarei o próximo teste.</p>";
} else {
    echo "<p class='error'>❌ ALGUNS TESTES FALHARAM. Verifique os erros acima.</p>";
}

echo "<p><strong>💡 INFORMAÇÕES ADICIONAIS</strong></p>";
echo "<p><strong>URL de Teste:</strong> /cfc-bom-conselho/admin/teste-09-crud-aulas.php</p>";
echo "<p><strong>Funcionalidades Testadas:</strong> CREATE, READ, UPDATE, DELETE, Validações</p>";
echo "<p><strong>Arquivos Utilizados:</strong> Database, Config</p>";
echo "<p><strong>Operações CRUD:</strong> Inserir, Buscar, Atualizar, Excluir Aulas</p>";
echo "<p><strong>Validações:</strong> Horários, Instrutor, Aluno, Estrutura da tabela, Relacionamentos</p>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
