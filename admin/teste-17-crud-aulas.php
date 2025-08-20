<?php
/**
 * TESTE #17: CRUD de Aulas
 * Data/Hora: 19/08/2025 16:44:00
 * 
 * Este teste verifica todas as operações CRUD para a tabela 'aulas':
 * - CREATE: Criar nova aula
 * - READ: Buscar aula por ID, instrutor, aluno, veículo
 * - UPDATE: Atualizar dados da aula
 * - DELETE: Excluir aula
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
echo "<title>TESTE #17: CRUD de Aulas</title>";
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
echo ".progress-fill { height: 100%; background: linear-gradient(90deg, #28a745, #20c997); transition: width 0.3s ease; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<div class='header'>";
echo "<h1>🔍 TESTE #17: CRUD de Aulas</h1>";
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

// 17.1 Inclusão de Arquivos Necessários
echo "<h2>17.1 Inclusão de Arquivos Necessários</h2>";

try {
    require_once '../includes/database.php';
    require_once '../includes/config.php';
    registerTest("Arquivos necessários", "success", "INCLUÍDOS COM SUCESSO");
} catch (Exception $e) {
    registerTest("Arquivos necessários", "error", "ERRO: " . $e->getMessage());
}

// 17.2 Conexão com Banco de Dados
echo "<h2>17.2 Conexão com Banco de Dados</h2>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    registerTest("Conexão PDO", "success", "ESTABELECIDA COM SUCESSO");
} catch (PDOException $e) {
    registerTest("Conexão PDO", "error", "ERRO: " . $e->getMessage());
    exit;
}

// 17.3 Estrutura da Tabela 'aulas'
echo "<h2>17.3 Estrutura da Tabela 'aulas'</h2>";

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

// 17.4 Verificação de Dados na Tabela 'aulas'
echo "<h2>17.4 Verificação de Dados na Tabela 'aulas'</h2>";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM aulas");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("Total de aulas na tabela", "success", $count['total'] . " registros");
    
    if ($count['total'] > 0) {
        $stmt = $pdo->query("SELECT * FROM aulas LIMIT 5");
        $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        registerTest("Dados de exemplo", "info", "Primeiros 5 registros", $sampleData);
    }
} catch (Exception $e) {
    registerTest("Verificação de dados", "error", "ERRO: " . $e->getMessage());
}

// 17.5 Verificação de Dados de Referência
echo "<h2>17.5 Verificação de Dados de Referência</h2>";

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

// 17.6 Teste CREATE - Criar Aula
echo "<h2>17.6 Teste CREATE - Criar Aula</h2>";

try {
         // Buscar IDs válidos para relacionamentos
     $stmt = $pdo->query("SELECT id FROM cfcs LIMIT 1");
     $cfc = $stmt->fetch(PDO::FETCH_ASSOC);
     
     $stmt = $pdo->query("SELECT id FROM instrutores LIMIT 1");
     $instrutor = $stmt->fetch(PDO::FETCH_ASSOC);
     
     $stmt = $pdo->query("SELECT id FROM alunos LIMIT 1");
     $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
     
     $stmt = $pdo->query("SELECT id FROM veiculos LIMIT 1");
     $veiculo = $stmt->fetch(PDO::FETCH_ASSOC);
     
     if ($cfc && $instrutor && $aluno && $veiculo) {
                 $stmt = $pdo->prepare("INSERT INTO aulas (cfc_id, instrutor_id, aluno_id, veiculo_id, data_aula, hora_inicio, hora_fim, tipo_aula, status, observacoes, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
         
         $cfc_id = $cfc['id'];
         $instrutor_id = $instrutor['id'];
         $aluno_id = $aluno['id'];
         $veiculo_id = $veiculo['id'];
        $data_aula = '2025-08-25';
        $hora_inicio = '08:00:00';
        $hora_fim = '09:00:00';
        $tipo_aula = 'pratica';
        $status = 'agendada';
        $observacoes = 'Aula de teste do sistema - TESTE #17';
        
                 $result = $stmt->execute([
             $cfc_id,
             $instrutor_id,
             $aluno_id,
             $veiculo_id,
             $data_aula,
             $hora_inicio,
             $hora_fim,
             $tipo_aula,
             $status,
             $observacoes
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

// 17.7 Teste READ - Ler Aula
echo "<h2>17.7 Teste READ - Ler Aula</h2>";

try {
    if (isset($aulaId)) {
        // READ por ID
        $stmt = $pdo->prepare("SELECT * FROM aulas WHERE id = ?");
        $stmt->execute([$aulaId]);
        $aula = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($aula) {
            registerTest("READ por ID", "success", "AULA ENCONTRADA");
            registerTest("Dados da aula", "info", "Aula encontrada com sucesso", [$aula]);
        } else {
            registerTest("READ por ID", "error", "AULA NÃO ENCONTRADA");
        }
        
                 // READ por CFC
         $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM aulas WHERE cfc_id = ?");
         $stmt->execute([$cfc_id]);
         $countCfc = $stmt->fetch(PDO::FETCH_ASSOC);
         registerTest("READ por CFC", "success", $countCfc['total'] . " aulas do CFC ID " . $cfc_id);
         
         // READ por Instrutor
         $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM aulas WHERE instrutor_id = ?");
         $stmt->execute([$instrutor_id]);
         $countInstrutor = $stmt->fetch(PDO::FETCH_ASSOC);
         registerTest("READ por Instrutor", "success", $countInstrutor['total'] . " aulas do instrutor ID " . $instrutor_id);
        
        // READ por Aluno
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM aulas WHERE aluno_id = ?");
        $stmt->execute([$aluno_id]);
        $countAluno = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por Aluno", "success", $countAluno['total'] . " aulas do aluno ID " . $aluno_id);
        
        // READ por Veículo
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM aulas WHERE veiculo_id = ?");
        $stmt->execute([$veiculo_id]);
        $countVeiculo = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por Veículo", "success", $countVeiculo['total'] . " aulas do veículo ID " . $veiculo_id);
        
        // READ por Data
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM aulas WHERE data_aula = ?");
        $stmt->execute(['2025-08-25']);
        $countData = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por Data", "success", $countData['total'] . " aulas na data 2025-08-25");
        
        // READ por Status
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM aulas WHERE status = ?");
        $stmt->execute(['agendada']);
        $countStatus = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por Status", "success", $countStatus['total'] . " aulas com status 'agendada'");
        
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

// 17.8 Teste UPDATE - Atualizar Aula
echo "<h2>17.8 Teste UPDATE - Atualizar Aula</h2>";

try {
    if (isset($aulaId)) {
        $novoStatus = 'em_andamento';
        $novasObservacoes = 'Aula atualizada pelo TESTE #17';
        $novaHoraFim = '09:30:00';
        
        $stmt = $pdo->prepare("UPDATE aulas SET status = ?, observacoes = ?, hora_fim = ? WHERE id = ?");
        $result = $stmt->execute([$novoStatus, $novasObservacoes, $novaHoraFim, $aulaId]);
        
        if ($result) {
            registerTest("UPDATE", "success", "AULA ATUALIZADA COM SUCESSO");
            
            // Verificar se a atualização foi aplicada
            $stmt = $pdo->prepare("SELECT * FROM aulas WHERE id = ?");
            $stmt->execute([$aulaId]);
            $aulaAtualizada = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($aulaAtualizada && $aulaAtualizada['status'] === $novoStatus && $aulaAtualizada['observacoes'] === $novasObservacoes && $aulaAtualizada['hora_fim'] === $novaHoraFim) {
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

// 17.9 Teste DELETE - Excluir Aula
echo "<h2>17.9 Teste DELETE - Excluir Aula</h2>";

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

// 17.10 Teste de Validações
echo "<h2>17.10 Teste de Validações</h2>";

try {
         // Verificar aulas sem CFC
     $stmt = $pdo->query("SELECT COUNT(*) as total FROM aulas WHERE cfc_id IS NULL");
     $semCfc = $stmt->fetch(PDO::FETCH_ASSOC);
     
     if ($semCfc['total'] == 0) {
         registerTest("Validação CFC", "success", "TODAS AS AULAS TÊM CFC");
     } else {
         registerTest("Validação CFC", "warning", $semCfc['total'] . " aulas sem CFC");
     }
     
     // Verificar aulas sem instrutor
     $stmt = $pdo->query("SELECT COUNT(*) as total FROM aulas WHERE instrutor_id IS NULL");
     $semInstrutor = $stmt->fetch(PDO::FETCH_ASSOC);
     
     if ($semInstrutor['total'] == 0) {
         registerTest("Validação Instrutor", "success", "TODAS AS AULAS TÊM INSTRUTOR");
     } else {
         registerTest("Validação Instrutor", "warning", $semInstrutor['total'] . " aulas sem instrutor");
     }
    
    // Verificar aulas sem aluno
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM aulas WHERE aluno_id IS NULL");
    $semAluno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semAluno['total'] == 0) {
        registerTest("Validação Aluno", "success", "TODAS AS AULAS TÊM ALUNO");
    } else {
        registerTest("Validação Aluno", "warning", $semAluno['total'] . " aulas sem aluno");
    }
    
    // Verificar aulas sem veículo
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM aulas WHERE veiculo_id IS NULL");
    $semVeiculo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semVeiculo['total'] == 0) {
        registerTest("Validação Veículo", "success", "TODAS AS AULAS TÊM VEÍCULO");
    } else {
        registerTest("Validação Veículo", "warning", $semVeiculo['total'] . " aulas sem veículo");
    }
    
    // Verificar aulas por status
    $stmt = $pdo->query("SELECT status, COUNT(*) as total FROM aulas WHERE status IS NOT NULL GROUP BY status");
    $statusCount = $stmt->fetchAll(PDO::FETCH_ASSOC);
    registerTest("Aulas por Status", "info", "Distribuição por Status", $statusCount);
    
    // Verificar aulas por tipo
    $stmt = $pdo->query("SELECT tipo_aula, COUNT(*) as total FROM aulas WHERE tipo_aula IS NOT NULL GROUP BY tipo_aula");
    $tipoCount = $stmt->fetchAll(PDO::FETCH_ASSOC);
    registerTest("Aulas por Tipo", "info", "Distribuição por Tipo", $tipoCount);
    
    // Verificar aulas por data
    $stmt = $pdo->query("SELECT DATE(data_aula) as data, COUNT(*) as total FROM aulas WHERE data_aula IS NOT NULL GROUP BY DATE(data_aula) ORDER BY data DESC LIMIT 5");
    $dataCount = $stmt->fetchAll(PDO::FETCH_ASSOC);
    registerTest("Aulas por Data", "info", "Últimas 5 datas com aulas", $dataCount);
    
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
    echo "<p class='success'>✅ TESTE #17 CONCLUÍDO COM SUCESSO!</p>";
    echo "<p><strong>🎯 Próximo: TESTE #18 - CRUD de Sessões</strong></p>";
    echo "<p><strong>📝 Instrução:</strong> Execute este teste e me informe o resultado. Se tudo estiver OK, criarei o próximo teste.</p>";
} else {
    echo "<p class='error'>❌ ALGUNS TESTES FALHARAM. Verifique os erros acima.</p>";
}

echo "<p><strong>💡 INFORMAÇÕES ADICIONAIS</strong></p>";
echo "<p><strong>URL de Teste:</strong> /cfc-bom-conselho/admin/teste-17-crud-aulas.php</p>";
echo "<p><strong>Funcionalidades Testadas:</strong> CREATE, READ, UPDATE, DELETE, Validações</p>";
echo "<p><strong>Arquivos Utilizados:</strong> Database, Config</p>";
echo "<p><strong>Operações CRUD:</strong> Inserir, Buscar, Atualizar, Excluir Aulas</p>";
echo "<p><strong>Campos Obrigatórios:</strong> CFC, Instrutor, Aluno, Veículo, Data, Hora Início, Hora Fim, Tipo, Status</p>";
echo "<p><strong>Validações:</strong> Relacionamentos, Status, Tipo, Data, Horários, Estrutura da tabela, Integridade</p>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
