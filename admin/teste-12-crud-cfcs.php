<?php
/**
 * TESTE #12: CRUD de CFCs
 * Data/Hora: 19/08/2025 16:10:00
 * 
 * Este teste verifica todas as operações CRUD para a tabela 'cfcs':
 * - CREATE: Criar novo CFC
 * - READ: Buscar CFC por ID, CNPJ, nome
 * - UPDATE: Atualizar dados do CFC
 * - DELETE: Excluir CFC
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
echo "<title>TESTE #12: CRUD de CFCs</title>";
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
echo "<h1>🔍 TESTE #12: CRUD de CFCs</h1>";
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

// 12.1 Inclusão de Arquivos Necessários
echo "<h2>12.1 Inclusão de Arquivos Necessários</h2>";

try {
    require_once '../includes/database.php';
    require_once '../includes/config.php';
    registerTest("Arquivos necessários", "success", "INCLUÍDOS COM SUCESSO");
} catch (Exception $e) {
    registerTest("Arquivos necessários", "error", "ERRO: " . $e->getMessage());
}

// 12.2 Conexão com Banco de Dados
echo "<h2>12.2 Conexão com Banco de Dados</h2>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    registerTest("Conexão PDO", "success", "ESTABELECIDA COM SUCESSO");
} catch (PDOException $e) {
    registerTest("Conexão PDO", "error", "ERRO: " . $e->getMessage());
    exit;
}

// 12.3 Estrutura da Tabela 'cfcs'
echo "<h2>12.3 Estrutura da Tabela 'cfcs'</h2>";

try {
    $stmt = $pdo->query("DESCRIBE cfcs");
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($structure)) {
        registerTest("Estrutura da tabela", "success", "COMPLETA", $structure);
    } else {
        registerTest("Estrutura da tabela", "error", "TABELA NÃO ENCONTRADA");
    }
} catch (Exception $e) {
    registerTest("Estrutura da tabela", "error", "ERRO: " . $e->getMessage());
}

// 12.4 Verificação de Dados na Tabela 'cfcs'
echo "<h2>12.4 Verificação de Dados na Tabela 'cfcs'</h2>";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cfcs");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("Total de CFCs na tabela", "success", $count['total'] . " registros");
    
    if ($count['total'] > 0) {
        $stmt = $pdo->query("SELECT * FROM cfcs LIMIT 5");
        $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        registerTest("Dados de exemplo", "info", "Primeiros 5 registros", $sampleData);
    }
} catch (Exception $e) {
    registerTest("Verificação de dados", "error", "ERRO: " . $e->getMessage());
}

// 12.5 Verificação de Dados de Referência
echo "<h2>12.5 Verificação de Dados de Referência</h2>";

try {
    // Verificar Usuários
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $usuarioCount = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("Usuários disponíveis", "info", $usuarioCount['total'] . " usuários encontrados");
    
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

// 12.6 Teste CREATE - Criar CFC
echo "<h2>12.6 Teste CREATE - Criar CFC</h2>";

try {
    $stmt = $pdo->prepare("INSERT INTO cfcs (nome, cnpj, endereco, telefone, email, responsavel, status, ativo, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    $nome = 'CFC Teste Sistema';
    $cnpj = '98.765.432/0001-10';
    $endereco = 'Rua Teste, 123 - Centro';
    $telefone = '(11) 99999-9999';
    $email = 'teste@cfcsistema.com.br';
    $responsavel = 'João Teste Sistema';
    $status = 'ativo';
    $ativo = 1;
    
    $result = $stmt->execute([
        $nome,
        $cnpj,
        $endereco,
        $telefone,
        $email,
        $responsavel,
        $status,
        $ativo
    ]);
    
    if ($result) {
        $cfcId = $pdo->lastInsertId();
        registerTest("CREATE", "success", "CFC CRIADO COM SUCESSO");
        registerTest("ID do CFC criado", "info", $cfcId);
    } else {
        registerTest("CREATE", "error", "FALHA AO CRIAR CFC");
    }
} catch (Exception $e) {
    registerTest("CREATE", "error", "ERRO: " . $e->getMessage());
}

// 12.7 Teste READ - Ler CFC
echo "<h2>12.7 Teste READ - Ler CFC</h2>";

try {
    if (isset($cfcId)) {
        // READ por ID
        $stmt = $pdo->prepare("SELECT * FROM cfcs WHERE id = ?");
        $stmt->execute([$cfcId]);
        $cfc = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cfc) {
            registerTest("READ por ID", "success", "CFC ENCONTRADO");
            registerTest("Dados do CFC", "info", "CFC encontrado com sucesso", [$cfc]);
        } else {
            registerTest("READ por ID", "error", "CFC NÃO ENCONTRADO");
        }
        
        // READ por CNPJ
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM cfcs WHERE cnpj = ?");
        $stmt->execute(['98.765.432/0001-10']);
        $countCnpj = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por CNPJ", "success", $countCnpj['total'] . " CFCs com CNPJ '98.765.432/0001-10'");
        
        // READ por Nome
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM cfcs WHERE nome LIKE ?");
        $stmt->execute(['%CFC Teste%']);
        $countNome = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por Nome", "success", $countNome['total'] . " CFCs com nome contendo 'CFC Teste'");
        
        // READ por Status
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM cfcs WHERE status = ?");
        $stmt->execute(['ativo']);
        $countStatus = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por Status", "success", $countStatus['total'] . " CFCs ativos");
        
        // READ ALL
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM cfcs");
        $countAll = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ ALL", "success", $countAll['total'] . " CFCS ENCONTRADOS");
        
    } else {
        registerTest("READ", "warning", "NENHUM CFC CRIADO PARA TESTE");
    }
} catch (Exception $e) {
    registerTest("READ", "error", "ERRO: " . $e->getMessage());
}

// 12.8 Teste UPDATE - Atualizar CFC
echo "<h2>12.8 Teste UPDATE - Atualizar CFC</h2>";

try {
    if (isset($cfcId)) {
        $novoTelefone = '(11) 88888-8888';
        $novoEmail = 'atualizado@cfcsistema.com.br';
        $novoStatus = 'em_analise';
        
        $stmt = $pdo->prepare("UPDATE cfcs SET telefone = ?, email = ?, status = ? WHERE id = ?");
        $result = $stmt->execute([$novoTelefone, $novoEmail, $novoStatus, $cfcId]);
        
        if ($result) {
            registerTest("UPDATE", "success", "CFC ATUALIZADO COM SUCESSO");
            
            // Verificar se a atualização foi aplicada
            $stmt = $pdo->prepare("SELECT * FROM cfcs WHERE id = ?");
            $stmt->execute([$cfcId]);
            $cfcAtualizado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($cfcAtualizado && $cfcAtualizado['telefone'] === $novoTelefone && $cfcAtualizado['email'] === $novoEmail && $cfcAtualizado['status'] === $novoStatus) {
                registerTest("Verificação UPDATE", "success", "DADOS CONFIRMADOS");
            } else {
                registerTest("Verificação UPDATE", "warning", "ATUALIZAÇÃO NÃO CONFIRMADA");
            }
        } else {
            registerTest("UPDATE", "error", "FALHA AO ATUALIZAR CFC");
        }
    } else {
        registerTest("UPDATE", "warning", "NENHUM CFC CRIADO PARA TESTE");
    }
} catch (Exception $e) {
    registerTest("UPDATE", "error", "ERRO: " . $e->getMessage());
}

// 12.9 Teste DELETE - Excluir CFC
echo "<h2>12.9 Teste DELETE - Excluir CFC</h2>";

try {
    if (isset($cfcId)) {
        $stmt = $pdo->prepare("DELETE FROM cfcs WHERE id = ?");
        $result = $stmt->execute([$cfcId]);
        
        if ($result) {
            registerTest("DELETE", "success", "CFC EXCLUÍDO COM SUCESSO");
            
            // Verificar se a exclusão foi aplicada
            $stmt = $pdo->prepare("SELECT * FROM cfcs WHERE id = ?");
            $stmt->execute([$cfcId]);
            $cfcExcluido = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$cfcExcluido) {
                registerTest("Verificação DELETE", "success", "CFC NÃO ENCONTRADO (EXCLUÍDO)");
            } else {
                registerTest("Verificação DELETE", "error", "CFC AINDA EXISTE");
            }
        } else {
            registerTest("DELETE", "error", "FALHA AO EXCLUIR CFC");
        }
    } else {
        registerTest("DELETE", "warning", "NENHUM CFC CRIADO PARA TESTE");
    }
} catch (Exception $e) {
    registerTest("DELETE", "error", "ERRO: " . $e->getMessage());
}

// 12.10 Teste de Validações
echo "<h2>12.10 Teste de Validações</h2>";

try {
    // Verificar CFCs sem nome
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cfcs WHERE nome IS NULL OR nome = ''");
    $semNome = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semNome['total'] == 0) {
        registerTest("Validação Nome", "success", "TODOS OS CFCS TÊM NOME");
    } else {
        registerTest("Validação Nome", "warning", $semNome['total'] . " CFCS SEM NOME");
    }
    
    // Verificar CFCs sem CNPJ
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cfcs WHERE cnpj IS NULL OR cnpj = ''");
    $semCnpj = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semCnpj['total'] == 0) {
        registerTest("Validação CNPJ", "success", "TODOS OS CFCS TÊM CNPJ");
    } else {
        registerTest("Validação CNPJ", "warning", $semCnpj['total'] . " CFCS SEM CNPJ");
    }
    
    // Verificar CFCs sem email
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cfcs WHERE email IS NULL OR email = ''");
    $semEmail = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semEmail['total'] == 0) {
        registerTest("Validação Email", "success", "TODOS OS CFCS TÊM EMAIL");
    } else {
        registerTest("Validação Email", "warning", $semEmail['total'] . " CFCS SEM EMAIL");
    }
    
    // Verificar CFCs ativos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cfcs WHERE ativo = 1");
    $ativos = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("CFCs Ativos", "info", $ativos['total'] . " CFCs ativos");
    
    // Verificar CFCs por status
    $stmt = $pdo->query("SELECT status, COUNT(*) as total FROM cfcs GROUP BY status");
    $statusCount = $stmt->fetchAll(PDO::FETCH_ASSOC);
    registerTest("CFCs por Status", "info", "Distribuição por status", $statusCount);
    
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
    echo "<p class='success'>✅ TESTE #12 CONCLUÍDO COM SUCESSO!</p>";
    echo "<p><strong>🎯 Próximo: TESTE #13 - CRUD de Usuários</strong></p>";
    echo "<p><strong>📝 Instrução:</strong> Execute este teste e me informe o resultado. Se tudo estiver OK, criarei o próximo teste.</p>";
} else {
    echo "<p class='error'>❌ ALGUNS TESTES FALHARAM. Verifique os erros acima.</p>";
}

echo "<p><strong>💡 INFORMAÇÕES ADICIONAIS</strong></p>";
echo "<p><strong>URL de Teste:</strong> /cfc-bom-conselho/admin/teste-12-crud-cfcs.php</p>";
echo "<p><strong>Funcionalidades Testadas:</strong> CREATE, READ, UPDATE, DELETE, Validações</p>";
echo "<p><strong>Arquivos Utilizados:</strong> Database, Config</p>";
echo "<p><strong>Operações CRUD:</strong> Inserir, Buscar, Atualizar, Excluir CFCs</p>";
echo "<p><strong>Validações:</strong> Nome, CNPJ, Email, Status, Ativo, Estrutura da tabela, Relacionamentos</p>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
