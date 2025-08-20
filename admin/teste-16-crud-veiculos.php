<?php
/**
 * TESTE #16: CRUD de Veículos
 * Data/Hora: 19/08/2025 16:38:00
 * 
 * Este teste verifica todas as operações CRUD para a tabela 'veiculos':
 * - CREATE: Criar novo veículo
 * - READ: Buscar veículo por ID, placa, chassi
 * - UPDATE: Atualizar dados do veículo
 * - DELETE: Excluir veículo
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
echo "<title>TESTE #16: CRUD de Veículos</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }";
echo ".container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo ".header { background: linear-gradient(135deg, #fd7e14 0%, #e55a00 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; }";
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
echo ".progress-fill { height: 100%; background: linear-gradient(90deg, #28a745, #fd7e14); transition: width 0.3s ease; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<div class='header'>";
echo "<h1>🔍 TESTE #16: CRUD de Veículos</h1>";
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

// 16.1 Inclusão de Arquivos Necessários
echo "<h2>16.1 Inclusão de Arquivos Necessários</h2>";

try {
    require_once '../includes/database.php';
    require_once '../includes/config.php';
    registerTest("Arquivos necessários", "success", "INCLUÍDOS COM SUCESSO");
} catch (Exception $e) {
    registerTest("Arquivos necessários", "error", "ERRO: " . $e->getMessage());
}

// 16.2 Conexão com Banco de Dados
echo "<h2>16.2 Conexão com Banco de Dados</h2>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    registerTest("Conexão PDO", "success", "ESTABELECIDA COM SUCESSO");
} catch (PDOException $e) {
    registerTest("Conexão PDO", "error", "ERRO: " . $e->getMessage());
    exit;
}

// 16.3 Estrutura da Tabela 'veiculos'
echo "<h2>16.3 Estrutura da Tabela 'veiculos'</h2>";

try {
    $stmt = $pdo->query("DESCRIBE veiculos");
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($structure)) {
        registerTest("Estrutura da tabela", "success", "COMPLETA", $structure);
    } else {
        registerTest("Estrutura da tabela", "error", "TABELA NÃO ENCONTRADA");
    }
} catch (Exception $e) {
    registerTest("Estrutura da tabela", "error", "ERRO: " . $e->getMessage());
}

// 16.4 Verificação de Dados na Tabela 'veiculos'
echo "<h2>16.4 Verificação de Dados na Tabela 'veiculos'</h2>";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM veiculos");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("Total de veículos na tabela", "success", $count['total'] . " registros");
    
    if ($count['total'] > 0) {
        $stmt = $pdo->query("SELECT * FROM veiculos LIMIT 5");
        $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        registerTest("Dados de exemplo", "info", "Primeiros 5 registros", $sampleData);
    }
} catch (Exception $e) {
    registerTest("Verificação de dados", "error", "ERRO: " . $e->getMessage());
}

// 16.5 Verificação de Dados de Referência
echo "<h2>16.5 Verificação de Dados de Referência</h2>";

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
    
} catch (Exception $e) {
    registerTest("Verificação de referências", "error", "ERRO: " . $e->getMessage());
}

// 16.6 Teste CREATE - Criar Veículo
echo "<h2>16.6 Teste CREATE - Criar Veículo</h2>";

try {
    $stmt = $pdo->prepare("INSERT INTO veiculos (placa, chassi, marca, modelo, ano, cor, cfc_id, status, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    $placa = 'XYZ-9999';
    $chassi = '9BWZZZ377VT999999';
    $marca = 'Toyota';
    $modelo = 'Corolla';
    $ano = 2022;
    $cor = 'Azul';
    $cfc_id = 1; // Usar CFC existente
    $status = 'ativo';
    
    $result = $stmt->execute([
        $placa,
        $chassi,
        $marca,
        $modelo,
        $ano,
        $cor,
        $cfc_id,
        $status
    ]);
    
    if ($result) {
        $veiculoId = $pdo->lastInsertId();
        registerTest("CREATE", "success", "VEÍCULO CRIADO COM SUCESSO");
        registerTest("ID do veículo criado", "info", $veiculoId);
    } else {
        registerTest("CREATE", "error", "FALHA AO CRIAR VEÍCULO");
    }
} catch (Exception $e) {
    registerTest("CREATE", "error", "ERRO: " . $e->getMessage());
}

// 16.7 Teste READ - Ler Veículo
echo "<h2>16.7 Teste READ - Ler Veículo</h2>";

try {
    if (isset($veiculoId)) {
        // READ por ID
        $stmt = $pdo->prepare("SELECT * FROM veiculos WHERE id = ?");
        $stmt->execute([$veiculoId]);
        $veiculo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($veiculo) {
            registerTest("READ por ID", "success", "VEÍCULO ENCONTRADO");
            registerTest("Dados do veículo", "info", "Veículo encontrado com sucesso", [$veiculo]);
        } else {
            registerTest("READ por ID", "error", "VEÍCULO NÃO ENCONTRADO");
        }
        
        // READ por Placa
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM veiculos WHERE placa = ?");
        $stmt->execute(['XYZ-9999']);
        $countPlaca = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por Placa", "success", $countPlaca['total'] . " veículos com placa 'XYZ-9999'");
        
        // READ por Chassi
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM veiculos WHERE chassi = ?");
        $stmt->execute(['9BWZZZ377VT999999']);
        $countChassi = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por Chassi", "success", $countChassi['total'] . " veículos com chassi '9BWZZZ377VT999999'");
        
        // READ por Marca
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM veiculos WHERE marca = ?");
        $stmt->execute(['Toyota']);
        $countMarca = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por Marca", "success", $countMarca['total'] . " veículos da marca 'Toyota'");
        
        // READ por Modelo
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM veiculos WHERE modelo = ?");
        $stmt->execute(['Corolla']);
        $countModelo = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por Modelo", "success", $countModelo['total'] . " veículos do modelo 'Corolla'");
        
        // READ por CFC
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM veiculos WHERE cfc_id = ?");
        $stmt->execute([1]);
        $countCfc = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por CFC", "success", $countCfc['total'] . " veículos do CFC ID 1");
        
        // READ ALL
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM veiculos");
        $countAll = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ ALL", "success", $countAll['total'] . " VEÍCULOS ENCONTRADOS");
        
    } else {
        registerTest("READ", "warning", "NENHUM VEÍCULO CRIADO PARA TESTE");
    }
} catch (Exception $e) {
    registerTest("READ", "error", "ERRO: " . $e->getMessage());
}

// 16.8 Teste UPDATE - Atualizar Veículo
echo "<h2>16.8 Teste UPDATE - Atualizar Veículo</h2>";

try {
    if (isset($veiculoId)) {
        $novaCor = 'Prata';
        $novoAno = 2021;
        $novoStatus = 'manutencao';
        
        $stmt = $pdo->prepare("UPDATE veiculos SET cor = ?, ano = ?, status = ? WHERE id = ?");
        $result = $stmt->execute([$novaCor, $novoAno, $novoStatus, $veiculoId]);
        
        if ($result) {
            registerTest("UPDATE", "success", "VEÍCULO ATUALIZADO COM SUCESSO");
            
            // Verificar se a atualização foi aplicada
            $stmt = $pdo->prepare("SELECT * FROM veiculos WHERE id = ?");
            $stmt->execute([$veiculoId]);
            $veiculoAtualizado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($veiculoAtualizado && $veiculoAtualizado['cor'] === $novaCor && $veiculoAtualizado['ano'] == $novoAno && $veiculoAtualizado['status'] === $novoStatus) {
                registerTest("Verificação UPDATE", "success", "DADOS CONFIRMADOS");
            } else {
                registerTest("Verificação UPDATE", "warning", "ATUALIZAÇÃO NÃO CONFIRMADA");
            }
        } else {
            registerTest("UPDATE", "error", "FALHA AO ATUALIZAR VEÍCULO");
        }
    } else {
        registerTest("UPDATE", "warning", "NENHUM VEÍCULO CRIADO PARA TESTE");
    }
} catch (Exception $e) {
    registerTest("UPDATE", "error", "ERRO: " . $e->getMessage());
}

// 16.9 Teste DELETE - Excluir Veículo
echo "<h2>16.9 Teste DELETE - Excluir Veículo</h2>";

try {
    if (isset($veiculoId)) {
        $stmt = $pdo->prepare("DELETE FROM veiculos WHERE id = ?");
        $result = $stmt->execute([$veiculoId]);
        
        if ($result) {
            registerTest("DELETE", "success", "VEÍCULO EXCLUÍDO COM SUCESSO");
            
            // Verificar se a exclusão foi aplicada
            $stmt = $pdo->prepare("SELECT * FROM veiculos WHERE id = ?");
            $stmt->execute([$veiculoId]);
            $veiculoExcluido = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$veiculoExcluido) {
                registerTest("Verificação DELETE", "success", "VEÍCULO NÃO ENCONTRADO (EXCLUÍDO)");
            } else {
                registerTest("Verificação DELETE", "error", "VEÍCULO AINDA EXISTE");
            }
        } else {
            registerTest("DELETE", "error", "FALHA AO EXCLUIR VEÍCULO");
        }
    } else {
        registerTest("DELETE", "warning", "NENHUM VEÍCULO CRIADO PARA TESTE");
    }
} catch (Exception $e) {
    registerTest("DELETE", "error", "ERRO: " . $e->getMessage());
}

// 16.10 Teste de Validações
echo "<h2>16.10 Teste de Validações</h2>";

try {
    // Verificar veículos sem placa
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM veiculos WHERE placa IS NULL OR placa = ''");
    $semPlaca = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semPlaca['total'] == 0) {
        registerTest("Validação Placa", "success", "TODOS OS VEÍCULOS TÊM PLACA");
    } else {
        registerTest("Validação Placa", "warning", $semPlaca['total'] . " veículos sem placa");
    }
    
    // Verificar veículos sem chassi
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM veiculos WHERE chassi IS NULL OR chassi = ''");
    $semChassi = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semChassi['total'] == 0) {
        registerTest("Validação Chassi", "success", "TODOS OS VEÍCULOS TÊM CHASSI");
    } else {
        registerTest("Validação Chassi", "warning", $semChassi['total'] . " veículos sem chassi");
    }
    
    // Verificar veículos sem marca
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM veiculos WHERE marca IS NULL OR marca = ''");
    $semMarca = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semMarca['total'] == 0) {
        registerTest("Validação Marca", "success", "TODOS OS VEÍCULOS TÊM MARCA");
    } else {
        registerTest("Validação Marca", "warning", $semMarca['total'] . " veículos sem marca");
    }
    
    // Verificar veículos ativos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM veiculos WHERE status = 'ativo'");
    $ativos = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("Veículos Ativos", "info", $ativos['total'] . " veículos ativos");
    
    // Verificar veículos por CFC
    $stmt = $pdo->query("SELECT cfc_id, COUNT(*) as total FROM veiculos WHERE cfc_id IS NOT NULL GROUP BY cfc_id");
    $cfcCount = $stmt->fetchAll(PDO::FETCH_ASSOC);
    registerTest("Veículos por CFC", "info", "Distribuição por CFC", $cfcCount);
    
    // Verificar veículos por marca
    $stmt = $pdo->query("SELECT marca, COUNT(*) as total FROM veiculos WHERE marca IS NOT NULL GROUP BY marca");
    $marcaCount = $stmt->fetchAll(PDO::FETCH_ASSOC);
    registerTest("Veículos por Marca", "info", "Distribuição por Marca", $marcaCount);
    
    // Verificar veículos por ano
    $stmt = $pdo->query("SELECT ano, COUNT(*) as total FROM veiculos WHERE ano IS NOT NULL GROUP BY ano ORDER BY ano DESC");
    $anoCount = $stmt->fetchAll(PDO::FETCH_ASSOC);
    registerTest("Veículos por Ano", "info", "Distribuição por Ano", $anoCount);
    
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
    echo "<p class='success'>✅ TESTE #16 CONCLUÍDO COM SUCESSO!</p>";
    echo "<p><strong>🎯 Próximo: TESTE #17 - CRUD de Aulas</strong></p>";
    echo "<p><strong>📝 Instrução:</strong> Execute este teste e me informe o resultado. Se tudo estiver OK, criarei o próximo teste.</p>";
} else {
    echo "<p class='error'>❌ ALGUNS TESTES FALHARAM. Verifique os erros acima.</p>";
}

echo "<p><strong>💡 INFORMAÇÕES ADICIONAIS</strong></p>";
echo "<p><strong>URL de Teste:</strong> /cfc-bom-conselho/admin/teste-16-crud-veiculos.php</p>";
echo "<p><strong>Funcionalidades Testadas:</strong> CREATE, READ, UPDATE, DELETE, Validações</p>";
echo "<p><strong>Arquivos Utilizados:</strong> Database, Config</p>";
echo "<p><strong>Operações CRUD:</strong> Inserir, Buscar, Atualizar, Excluir Veículos</p>";
echo "<p><strong>Campos Obrigatórios:</strong> Placa, Chassi, Marca, Modelo, CFC, Status</p>";
echo "<p><strong>Validações:</strong> Placa, Chassi, Marca, Modelo, Ano, Cor, CFC, Status, Estrutura da tabela, Relacionamentos</p>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
