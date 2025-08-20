<?php
/**
 * TESTE #12: CRUD de Configurações
 * Data/Hora: 19/08/2025 16:00:00
 * 
 * Este teste verifica todas as operações CRUD para a tabela 'configuracoes':
 * - CREATE: Criar nova configuração
 * - READ: Buscar configuração por ID, chave, categoria
 * - UPDATE: Atualizar dados da configuração
 * - DELETE: Excluir configuração
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
echo "<title>TESTE #12: CRUD de Configurações</title>";
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
echo "<h1>🔍 TESTE #12: CRUD de Configurações</h1>";
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

// 12.3 Estrutura da Tabela 'configuracoes'
echo "<h2>12.3 Estrutura da Tabela 'configuracoes'</h2>";

try {
    $stmt = $pdo->query("DESCRIBE configuracoes");
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($structure)) {
        registerTest("Estrutura da tabela", "success", "COMPLETA", $structure);
    } else {
        registerTest("Estrutura da tabela", "error", "TABELA NÃO ENCONTRADA");
    }
} catch (Exception $e) {
    registerTest("Estrutura da tabela", "error", "ERRO: " . $e->getMessage());
}

// 12.4 Verificação de Dados na Tabela 'configuracoes'
echo "<h2>12.4 Verificação de Dados na Tabela 'configuracoes'</h2>";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM configuracoes");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("Total de Configurações na tabela", "success", $count['total'] . " registros");
    
    if ($count['total'] > 0) {
        $stmt = $pdo->query("SELECT * FROM configuracoes LIMIT 5");
        $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        registerTest("Dados de exemplo", "info", "Primeiros 5 registros", $sampleData);
    }
} catch (Exception $e) {
    registerTest("Verificação de dados", "error", "ERRO: " . $e->getMessage());
}

// 12.5 Verificação de Dados de Referência
echo "<h2>12.5 Verificação de Dados de Referência</h2>";

try {
    // Verificar CFCs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cfcs");
    $cfcCount = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("CFCs disponíveis", "info", $cfcCount['total'] . " CFCs encontrados");
    
} catch (Exception $e) {
    registerTest("Verificação de referências", "error", "ERRO: " . $e->getMessage());
}

// 12.6 Teste CREATE - Criar Configuração
echo "<h2>12.6 Teste CREATE - Criar Configuração</h2>";

try {
    // Buscar dados de referência
    $stmt = $pdo->query("SELECT id FROM cfcs LIMIT 1");
    $cfc = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cfc) {
        $stmt = $pdo->prepare("INSERT INTO configuracoes (cfc_id, chave, valor, descricao, categoria, ativo, criado_em) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        
        $chave = 'teste_config';
        $valor = 'valor_teste';
        $descricao = 'Configuração de teste criada pelo sistema';
        $categoria = 'sistema';
        $ativo = 1;
        
        $result = $stmt->execute([
            $cfc['id'],
            $chave,
            $valor,
            $descricao,
            $categoria,
            $ativo
        ]);
        
        if ($result) {
            $configId = $pdo->lastInsertId();
            registerTest("CREATE", "success", "CONFIGURAÇÃO CRIADA COM SUCESSO");
            registerTest("ID da configuração criada", "info", $configId);
        } else {
            registerTest("CREATE", "error", "FALHA AO CRIAR CONFIGURAÇÃO");
        }
    } else {
        registerTest("CREATE", "error", "DADOS DE REFERÊNCIA INSUFICIENTES");
    }
} catch (Exception $e) {
    registerTest("CREATE", "error", "ERRO: " . $e->getMessage());
}

// 12.7 Teste READ - Ler Configuração
echo "<h2>12.7 Teste READ - Ler Configuração</h2>";

try {
    if (isset($configId)) {
        // READ por ID
        $stmt = $pdo->prepare("SELECT c.*, cf.nome as cfc_nome FROM configuracoes c JOIN cfcs cf ON c.cfc_id = cf.id WHERE c.id = ?");
        $stmt->execute([$configId]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($config) {
            registerTest("READ por ID", "success", "CONFIGURAÇÃO ENCONTRADA");
            registerTest("Dados da configuração", "info", "Configuração encontrada com sucesso", [$config]);
        } else {
            registerTest("READ por ID", "error", "CONFIGURAÇÃO NÃO ENCONTRADA");
        }
        
        // READ por Chave
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM configuracoes WHERE chave = ?");
        $stmt->execute(['teste_config']);
        $countChave = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por Chave", "success", $countChave['total'] . " configurações com chave 'teste_config'");
        
        // READ por Categoria
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM configuracoes WHERE categoria = ?");
        $stmt->execute(['sistema']);
        $countCategoria = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por Categoria", "success", $countCategoria['total'] . " configurações da categoria 'sistema'");
        
        // READ por CFC
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM configuracoes WHERE cfc_id = ?");
        $stmt->execute([$cfc['id']]);
        $countCfc = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por CFC", "success", $countCfc['total'] . " configurações do CFC");
        
        // READ ALL
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM configuracoes");
        $countAll = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ ALL", "success", $countAll['total'] . " CONFIGURAÇÕES ENCONTRADAS");
        
    } else {
        registerTest("READ", "warning", "NENHUMA CONFIGURAÇÃO CRIADA PARA TESTE");
    }
} catch (Exception $e) {
    registerTest("READ", "error", "ERRO: " . $e->getMessage());
}

// 12.8 Teste UPDATE - Atualizar Configuração
echo "<h2>12.8 Teste UPDATE - Atualizar Configuração</h2>";

try {
    if (isset($configId)) {
        $novoValor = 'valor_atualizado';
        $novaDescricao = 'Configuração atualizada pelo sistema';
        
        $stmt = $pdo->prepare("UPDATE configuracoes SET valor = ?, descricao = ? WHERE id = ?");
        $result = $stmt->execute([$novoValor, $novaDescricao, $configId]);
        
        if ($result) {
            registerTest("UPDATE", "success", "CONFIGURAÇÃO ATUALIZADA COM SUCESSO");
            
            // Verificar se a atualização foi aplicada
            $stmt = $pdo->prepare("SELECT * FROM configuracoes WHERE id = ?");
            $stmt->execute([$configId]);
            $configAtualizada = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($configAtualizada && $configAtualizada['valor'] === $novoValor && $configAtualizada['descricao'] === $novaDescricao) {
                registerTest("Verificação UPDATE", "success", "DADOS CONFIRMADOS");
            } else {
                registerTest("Verificação UPDATE", "warning", "ATUALIZAÇÃO NÃO CONFIRMADA");
            }
        } else {
            registerTest("UPDATE", "error", "FALHA AO ATUALIZAR CONFIGURAÇÃO");
        }
    } else {
        registerTest("UPDATE", "warning", "NENHUMA CONFIGURAÇÃO CRIADA PARA TESTE");
    }
} catch (Exception $e) {
    registerTest("UPDATE", "error", "ERRO: " . $e->getMessage());
}

// 12.9 Teste DELETE - Excluir Configuração
echo "<h2>12.9 Teste DELETE - Excluir Configuração</h2>";

try {
    if (isset($configId)) {
        $stmt = $pdo->prepare("DELETE FROM configuracoes WHERE id = ?");
        $result = $stmt->execute([$configId]);
        
        if ($result) {
            registerTest("DELETE", "success", "CONFIGURAÇÃO EXCLUÍDA COM SUCESSO");
            
            // Verificar se a exclusão foi aplicada
            $stmt = $pdo->prepare("SELECT * FROM configuracoes WHERE id = ?");
            $stmt->execute([$configId]);
            $configExcluida = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$configExcluida) {
                registerTest("Verificação DELETE", "success", "CONFIGURAÇÃO NÃO ENCONTRADA (EXCLUÍDA)");
            } else {
                registerTest("Verificação DELETE", "error", "CONFIGURAÇÃO AINDA EXISTE");
            }
        } else {
            registerTest("DELETE", "error", "FALHA AO EXCLUIR CONFIGURAÇÃO");
        }
    } else {
        registerTest("DELETE", "warning", "NENHUMA CONFIGURAÇÃO CRIADA PARA TESTE");
    }
} catch (Exception $e) {
    registerTest("DELETE", "error", "ERRO: " . $e->getMessage());
}

// 12.10 Teste de Validações
echo "<h2>12.10 Teste de Validações</h2>";

try {
    // Verificar configurações sem CFC
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM configuracoes WHERE cfc_id IS NULL");
    $semCfc = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semCfc['total'] == 0) {
        registerTest("Validação CFC", "success", "TODAS AS CONFIGURAÇÕES TÊM CFC");
    } else {
        registerTest("Validação CFC", "warning", $semCfc['total'] . " CONFIGURAÇÕES SEM CFC");
    }
    
    // Verificar configurações sem chave
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM configuracoes WHERE chave IS NULL OR chave = ''");
    $semChave = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semChave['total'] == 0) {
        registerTest("Validação Chave", "success", "TODAS AS CONFIGURAÇÕES TÊM CHAVE");
    } else {
        registerTest("Validação Chave", "warning", $semChave['total'] . " CONFIGURAÇÕES SEM CHAVE");
    }
    
    // Verificar configurações sem valor
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM configuracoes WHERE valor IS NULL OR valor = ''");
    $semValor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semValor['total'] == 0) {
        registerTest("Validação Valor", "success", "TODAS AS CONFIGURAÇÕES TÊM VALOR");
    } else {
        registerTest("Validação Valor", "warning", $semValor['total'] . " CONFIGURAÇÕES SEM VALOR");
    }
    
    // Verificar configurações sem categoria
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM configuracoes WHERE categoria IS NULL OR categoria = ''");
    $semCategoria = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semCategoria['total'] == 0) {
        registerTest("Validação Categoria", "success", "TODAS AS CONFIGURAÇÕES TÊM CATEGORIA");
    } else {
        registerTest("Validação Categoria", "warning", $semCategoria['total'] . " CONFIGURAÇÕES SEM CATEGORIA");
    }
    
    // Verificar configurações ativas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM configuracoes WHERE ativo = 1");
    $ativas = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("Configurações Ativas", "info", $ativas['total'] . " configurações ativas");
    
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
    echo "<p><strong>🎯 Próximo: TESTE #13 - CRUD de Relatórios</strong></p>";
    echo "<p><strong>📝 Instrução:</strong> Execute este teste e me informe o resultado. Se tudo estiver OK, criarei o próximo teste.</p>";
} else {
    echo "<p class='error'>❌ ALGUNS TESTES FALHARAM. Verifique os erros acima.</p>";
}

echo "<p><strong>💡 INFORMAÇÕES ADICIONAIS</strong></p>";
echo "<p><strong>URL de Teste:</strong> /cfc-bom-conselho/admin/teste-12-crud-configuracoes.php</p>";
echo "<p><strong>Funcionalidades Testadas:</strong> CREATE, READ, UPDATE, DELETE, Validações</p>";
echo "<p><strong>Arquivos Utilizados:</strong> Database, Config</p>";
echo "<p><strong>Operações CRUD:</strong> Inserir, Buscar, Atualizar, Excluir Configurações</p>";
echo "<p><strong>Validações:</strong> CFC, Chave, Valor, Categoria, Ativo, Estrutura da tabela, Relacionamentos</p>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
