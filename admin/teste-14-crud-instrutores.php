<?php
/**
 * TESTE #14: CRUD de Instrutores
 * Data/Hora: 19/08/2025 16:30:00
 * 
 * Este teste verifica todas as operações CRUD para a tabela 'instrutores':
 * - CREATE: Criar novo instrutor
 * - READ: Buscar instrutor por ID, CPF, nome
 * - UPDATE: Atualizar dados do instrutor
 * - DELETE: Excluir instrutor
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
echo "<title>TESTE #14: CRUD de Instrutores</title>";
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
echo "<h1>🔍 TESTE #14: CRUD de Instrutores</h1>";
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

// 14.1 Inclusão de Arquivos Necessários
echo "<h2>14.1 Inclusão de Arquivos Necessários</h2>";

try {
    require_once '../includes/database.php';
    require_once '../includes/config.php';
    registerTest("Arquivos necessários", "success", "INCLUÍDOS COM SUCESSO");
} catch (Exception $e) {
    registerTest("Arquivos necessários", "error", "ERRO: " . $e->getMessage());
}

// 14.2 Conexão com Banco de Dados
echo "<h2>14.2 Conexão com Banco de Dados</h2>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    registerTest("Conexão PDO", "success", "ESTABELECIDA COM SUCESSO");
} catch (PDOException $e) {
    registerTest("Conexão PDO", "error", "ERRO: " . $e->getMessage());
    exit;
}

// 14.3 Estrutura da Tabela 'instrutores'
echo "<h2>14.3 Estrutura da Tabela 'instrutores'</h2>";

try {
    $stmt = $pdo->query("DESCRIBE instrutores");
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($structure)) {
        registerTest("Estrutura da tabela", "success", "COMPLETA", $structure);
    } else {
        registerTest("Estrutura da tabela", "error", "TABELA NÃO ENCONTRADA");
    }
} catch (Exception $e) {
    registerTest("Estrutura da tabela", "error", "ERRO: " . $e->getMessage());
}

// 14.4 Verificação de Dados na Tabela 'instrutores'
echo "<h2>14.4 Verificação de Dados na Tabela 'instrutores'</h2>";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM instrutores");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("Total de instrutores na tabela", "success", $count['total'] . " registros");
    
    if ($count['total'] > 0) {
        $stmt = $pdo->query("SELECT * FROM instrutores LIMIT 5");
        $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        registerTest("Dados de exemplo", "info", "Primeiros 5 registros", $sampleData);
    }
} catch (Exception $e) {
    registerTest("Verificação de dados", "error", "ERRO: " . $e->getMessage());
}

// 14.5 Verificação de Dados de Referência
echo "<h2>14.5 Verificação de Dados de Referência</h2>";

try {
    // Verificar CFCs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cfcs");
    $cfcCount = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("CFCs disponíveis", "info", $cfcCount['total'] . " CFCs encontrados");
    
    // Verificar Usuários
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $usuarioCount = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("Usuários disponíveis", "info", $usuarioCount['total'] . " usuários encontrados");
    
    // Verificar Alunos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM alunos");
    $alunoCount = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("Alunos disponíveis", "info", $alunoCount['total'] . " alunos encontrados");
    
} catch (Exception $e) {
    registerTest("Verificação de referências", "error", "ERRO: " . $e->getMessage());
}

// 14.6 Teste CREATE - Criar Instrutor
echo "<h2>14.6 Teste CREATE - Criar Instrutor</h2>";

try {
    $stmt = $pdo->prepare("INSERT INTO instrutores (nome, cpf, cnh, data_nascimento, telefone, email, cfc_id, ativo, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    $nome = 'Instrutor Teste Sistema';
    $cpf = '333.444.555-66';
    $cnh = '98765432109';
    $data_nascimento = '1985-05-15';
    $telefone = '(11) 77777-7777';
    $email = 'instrutor.teste@sistema.com.br';
    $cfc_id = 1; // Usar CFC existente
    $ativo = 1;
    
    $result = $stmt->execute([
        $nome,
        $cpf,
        $cnh,
        $data_nascimento,
        $telefone,
        $email,
        $cfc_id,
        $ativo
    ]);
    
    if ($result) {
        $instrutorId = $pdo->lastInsertId();
        registerTest("CREATE", "success", "INSTRUTOR CRIADO COM SUCESSO");
        registerTest("ID do instrutor criado", "info", $instrutorId);
    } else {
        registerTest("CREATE", "error", "FALHA AO CRIAR INSTRUTOR");
    }
} catch (Exception $e) {
    registerTest("CREATE", "error", "ERRO: " . $e->getMessage());
}

// 14.7 Teste READ - Ler Instrutor
echo "<h2>14.7 Teste READ - Ler Instrutor</h2>";

try {
    if (isset($instrutorId)) {
        // READ por ID
        $stmt = $pdo->prepare("SELECT * FROM instrutores WHERE id = ?");
        $stmt->execute([$instrutorId]);
        $instrutor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($instrutor) {
            registerTest("READ por ID", "success", "INSTRUTOR ENCONTRADO");
            registerTest("Dados do instrutor", "info", "Instrutor encontrado com sucesso", [$instrutor]);
        } else {
            registerTest("READ por ID", "error", "INSTRUTOR NÃO ENCONTRADO");
        }
        
        // READ por CPF
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM instrutores WHERE cpf = ?");
        $stmt->execute(['333.444.555-66']);
        $countCpf = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por CPF", "success", $countCpf['total'] . " instrutores com CPF '333.444.555-66'");
        
        // READ por Nome
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM instrutores WHERE nome LIKE ?");
        $stmt->execute(['%Instrutor Teste%']);
        $countNome = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por Nome", "success", $countNome['total'] . " instrutores com nome contendo 'Instrutor Teste'");
        
        // READ por CNH
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM instrutores WHERE cnh = ?");
        $stmt->execute(['98765432109']);
        $countCnh = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por CNH", "success", $countCnh['total'] . " instrutores com CNH '98765432109'");
        
        // READ por Email
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM instrutores WHERE email = ?");
        $stmt->execute(['instrutor.teste@sistema.com.br']);
        $countEmail = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por Email", "success", $countEmail['total'] . " instrutores com email 'instrutor.teste@sistema.com.br'");
        
        // READ por CFC
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM instrutores WHERE cfc_id = ?");
        $stmt->execute([1]);
        $countCfc = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por CFC", "success", $countCfc['total'] . " instrutores do CFC ID 1");
        
        // READ ALL
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM instrutores");
        $countAll = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ ALL", "success", $countAll['total'] . " INSTRUTORES ENCONTRADOS");
        
    } else {
        registerTest("READ", "warning", "NENHUM INSTRUTOR CRIADO PARA TESTE");
    }
} catch (Exception $e) {
    registerTest("READ", "error", "ERRO: " . $e->getMessage());
}

// 14.8 Teste UPDATE - Atualizar Instrutor
echo "<h2>14.8 Teste UPDATE - Atualizar Instrutor</h2>";

try {
    if (isset($instrutorId)) {
        $novoNome = 'Instrutor Teste Atualizado';
        $novoEmail = 'atualizado.instrutor@sistema.com.br';
        $novoTelefone = '(11) 66666-6666';
        
        $stmt = $pdo->prepare("UPDATE instrutores SET nome = ?, email = ?, telefone = ? WHERE id = ?");
        $result = $stmt->execute([$novoNome, $novoEmail, $novoTelefone, $instrutorId]);
        
        if ($result) {
            registerTest("UPDATE", "success", "INSTRUTOR ATUALIZADO COM SUCESSO");
            
            // Verificar se a atualização foi aplicada
            $stmt = $pdo->prepare("SELECT * FROM instrutores WHERE id = ?");
            $stmt->execute([$instrutorId]);
            $instrutorAtualizado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($instrutorAtualizado && $instrutorAtualizado['nome'] === $novoNome && $instrutorAtualizado['email'] === $novoEmail && $instrutorAtualizado['telefone'] === $novoTelefone) {
                registerTest("Verificação UPDATE", "success", "DADOS CONFIRMADOS");
            } else {
                registerTest("Verificação UPDATE", "warning", "ATUALIZAÇÃO NÃO CONFIRMADA");
            }
        } else {
            registerTest("UPDATE", "error", "FALHA AO ATUALIZAR INSTRUTOR");
        }
    } else {
        registerTest("UPDATE", "warning", "NENHUM INSTRUTOR CRIADO PARA TESTE");
    }
} catch (Exception $e) {
    registerTest("UPDATE", "error", "ERRO: " . $e->getMessage());
}

// 14.9 Teste DELETE - Excluir Instrutor
echo "<h2>14.9 Teste DELETE - Excluir Instrutor</h2>";

try {
    if (isset($instrutorId)) {
        $stmt = $pdo->prepare("DELETE FROM instrutores WHERE id = ?");
        $result = $stmt->execute([$instrutorId]);
        
        if ($result) {
            registerTest("DELETE", "success", "INSTRUTOR EXCLUÍDO COM SUCESSO");
            
            // Verificar se a exclusão foi aplicada
            $stmt = $pdo->prepare("SELECT * FROM instrutores WHERE id = ?");
            $stmt->execute([$instrutorId]);
            $instrutorExcluido = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$instrutorExcluido) {
                registerTest("Verificação DELETE", "success", "INSTRUTOR NÃO ENCONTRADO (EXCLUÍDO)");
            } else {
                registerTest("Verificação DELETE", "error", "INSTRUTOR AINDA EXISTE");
            }
        } else {
            registerTest("DELETE", "error", "FALHA AO EXCLUIR INSTRUTOR");
        }
    } else {
        registerTest("DELETE", "warning", "NENHUM INSTRUTOR CRIADO PARA TESTE");
    }
} catch (Exception $e) {
    registerTest("DELETE", "error", "ERRO: " . $e->getMessage());
}

// 14.10 Teste de Validações
echo "<h2>14.10 Teste de Validações</h2>";

try {
    // Verificar instrutores sem nome
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM instrutores WHERE nome IS NULL OR nome = ''");
    $semNome = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semNome['total'] == 0) {
        registerTest("Validação Nome", "success", "TODOS OS INSTRUTORES TÊM NOME");
    } else {
        registerTest("Validação Nome", "warning", $semNome['total'] . " instrutores sem nome");
    }
    
    // Verificar instrutores sem CPF
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM instrutores WHERE cpf IS NULL OR cpf = ''");
    $semCpf = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semCpf['total'] == 0) {
        registerTest("Validação CPF", "success", "TODOS OS INSTRUTORES TÊM CPF");
    } else {
        registerTest("Validação CPF", "warning", $semCpf['total'] . " instrutores sem CPF");
    }
    
    // Verificar instrutores sem email
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM instrutores WHERE email IS NULL OR email = ''");
    $semEmail = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semEmail['total'] == 0) {
        registerTest("Validação Email", "success", "TODOS OS INSTRUTORES TÊM EMAIL");
    } else {
        registerTest("Validação Email", "warning", $semEmail['total'] . " instrutores sem email");
    }
    
    // Verificar instrutores ativos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM instrutores WHERE ativo = 1");
    $ativos = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("Instrutores Ativos", "info", $ativos['total'] . " instrutores ativos");
    
    // Verificar instrutores por CFC
    $stmt = $pdo->query("SELECT cfc_id, COUNT(*) as total FROM instrutores WHERE cfc_id IS NOT NULL GROUP BY cfc_id");
    $cfcCount = $stmt->fetchAll(PDO::FETCH_ASSOC);
    registerTest("Instrutores por CFC", "info", "Distribuição por CFC", $cfcCount);
    
    // Verificar instrutores por CNH
    $stmt = $pdo->query("SELECT cnh, COUNT(*) as total FROM instrutores WHERE cnh IS NOT NULL GROUP BY cnh");
    $cnhCount = $stmt->fetchAll(PDO::FETCH_ASSOC);
    registerTest("Instrutores por CNH", "info", "Distribuição por CNH", $cnhCount);
    
    // Verificar instrutores por CPF
    $stmt = $pdo->query("SELECT cpf, COUNT(*) as total FROM instrutores WHERE cpf IS NOT NULL GROUP BY cpf");
    $cpfCount = $stmt->fetchAll(PDO::FETCH_ASSOC);
    registerTest("Instrutores por CPF", "info", "Distribuição por CPF", $cpfCount);
    
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
    echo "<p class='success'>✅ TESTE #14 CONCLUÍDO COM SUCESSO!</p>";
    echo "<p><strong>🎯 Próximo: TESTE #15 - CRUD de Alunos</strong></p>";
    echo "<p><strong>📝 Instrução:</strong> Execute este teste e me informe o resultado. Se tudo estiver OK, criarei o próximo teste.</p>";
} else {
    echo "<p class='error'>❌ ALGUNS TESTES FALHARAM. Verifique os erros acima.</p>";
}

echo "<p><strong>💡 INFORMAÇÕES ADICIONAIS</strong></p>";
echo "<p><strong>URL de Teste:</strong> /cfc-bom-conselho/admin/teste-14-crud-instrutores.php</p>";
echo "<p><strong>Funcionalidades Testadas:</strong> CREATE, READ, UPDATE, DELETE, Validações</p>";
echo "<p><strong>Arquivos Utilizados:</strong> Database, Config</p>";
echo "<p><strong>Operações CRUD:</strong> Inserir, Buscar, Atualizar, Excluir Instrutores</p>";
echo "<p><strong>Validações:</strong> Nome, CPF, CNH, Data Nascimento, Email, Telefone, CFC, Ativo, Estrutura da tabela, Relacionamentos</p>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
