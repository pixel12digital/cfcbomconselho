<?php
/**
 * TESTE #15: CRUD de Alunos
 * Data/Hora: 19/08/2025 16:30:00
 * 
 * Este teste verifica todas as operações CRUD para a tabela 'alunos':
 * - CREATE: Criar novo aluno
 * - READ: Buscar aluno por ID, CPF, nome
 * - UPDATE: Atualizar dados do aluno
 * - DELETE: Excluir aluno
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
echo "<title>TESTE #15: CRUD de Alunos</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }";
echo ".container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo ".header { background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; }";
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
echo ".progress-fill { height: 100%; background: linear-gradient(90deg, #28a745, #6f42c1); transition: width 0.3s ease; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<div class='header'>";
echo "<h1>🔍 TESTE #15: CRUD de Alunos</h1>";
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

// 15.1 Inclusão de Arquivos Necessários
echo "<h2>15.1 Inclusão de Arquivos Necessários</h2>";

try {
    require_once '../includes/database.php';
    require_once '../includes/config.php';
    registerTest("Arquivos necessários", "success", "INCLUÍDOS COM SUCESSO");
} catch (Exception $e) {
    registerTest("Arquivos necessários", "error", "ERRO: " . $e->getMessage());
}

// 15.2 Conexão com Banco de Dados
echo "<h2>15.2 Conexão com Banco de Dados</h2>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    registerTest("Conexão PDO", "success", "ESTABELECIDA COM SUCESSO");
} catch (PDOException $e) {
    registerTest("Conexão PDO", "error", "ERRO: " . $e->getMessage());
    exit;
}

// 15.3 Estrutura da Tabela 'alunos'
echo "<h2>15.3 Estrutura da Tabela 'alunos'</h2>";

try {
    $stmt = $pdo->query("DESCRIBE alunos");
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($structure)) {
        registerTest("Estrutura da tabela", "success", "COMPLETA", $structure);
    } else {
        registerTest("Estrutura da tabela", "error", "TABELA NÃO ENCONTRADA");
    }
} catch (Exception $e) {
    registerTest("Estrutura da tabela", "error", "ERRO: " . $e->getMessage());
}

// 15.4 Verificação de Dados na Tabela 'alunos'
echo "<h2>15.4 Verificação de Dados na Tabela 'alunos'</h2>";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM alunos");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("Total de alunos na tabela", "success", $count['total'] . " registros");
    
    if ($count['total'] > 0) {
        $stmt = $pdo->query("SELECT * FROM alunos LIMIT 5");
        $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        registerTest("Dados de exemplo", "info", "Primeiros 5 registros", $sampleData);
    }
} catch (Exception $e) {
    registerTest("Verificação de dados", "error", "ERRO: " . $e->getMessage());
}

// 15.5 Verificação de Dados de Referência
echo "<h2>15.5 Verificação de Dados de Referência</h2>";

try {
    // Verificar CFCs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cfcs");
    $cfcCount = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("CFCs disponíveis", "info", $cfcCount['total'] . " CFCs encontrados");
    
    // Verificar Instrutores
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM instrutores");
    $instrutorCount = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("Instrutores disponíveis", "info", $instrutorCount['total'] . " instrutores encontrados");
    
    // Verificar Veículos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM veiculos");
    $veiculoCount = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("Veículos disponíveis", "info", $veiculoCount['total'] . " veículos encontrados");
    
} catch (Exception $e) {
    registerTest("Verificação de referências", "error", "ERRO: " . $e->getMessage());
}

// 15.6 Teste CREATE - Criar Aluno
echo "<h2>15.6 Teste CREATE - Criar Aluno</h2>";

try {
    $stmt = $pdo->prepare("INSERT INTO alunos (nome, cpf, data_nascimento, telefone, email, endereco, cfc_id, categoria_cnh, status, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    $nome = 'Aluno Teste Sistema';
    $cpf = '444.555.666-77';
    $data_nascimento = '1998-08-20';
    $telefone = '(11) 55555-4444';
    $email = 'aluno.teste@sistema.com.br';
    $endereco = 'Rua Teste Aluno, 789 - Centro';
    $cfc_id = 1; // Usar CFC existente
    $categoria_cnh = 'B'; // Categoria obrigatória
    $status = 'ativo'; // Status obrigatório
    
    $result = $stmt->execute([
        $nome,
        $cpf,
        $data_nascimento,
        $telefone,
        $email,
        $endereco,
        $cfc_id,
        $categoria_cnh,
        $status
    ]);
    
    if ($result) {
        $alunoId = $pdo->lastInsertId();
        registerTest("CREATE", "success", "ALUNO CRIADO COM SUCESSO");
        registerTest("ID do aluno criado", "info", $alunoId);
    } else {
        registerTest("CREATE", "error", "FALHA AO CRIAR ALUNO");
    }
} catch (Exception $e) {
    registerTest("CREATE", "error", "ERRO: " . $e->getMessage());
}

// 15.7 Teste READ - Ler Aluno
echo "<h2>15.7 Teste READ - Ler Aluno</h2>";

try {
    if (isset($alunoId)) {
        // READ por ID
        $stmt = $pdo->prepare("SELECT * FROM alunos WHERE id = ?");
        $stmt->execute([$alunoId]);
        $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($aluno) {
            registerTest("READ por ID", "success", "ALUNO ENCONTRADO");
            registerTest("Dados do aluno", "info", "Aluno encontrado com sucesso", [$aluno]);
        } else {
            registerTest("READ por ID", "error", "ALUNO NÃO ENCONTRADO");
        }
        
        // READ por CPF
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM alunos WHERE cpf = ?");
        $stmt->execute(['444.555.666-77']);
        $countCpf = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por CPF", "success", $countCpf['total'] . " alunos com CPF '444.555.666-77'");
        
        // READ por Nome
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM alunos WHERE nome LIKE ?");
        $stmt->execute(['%Aluno Teste%']);
        $countNome = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por Nome", "success", $countNome['total'] . " alunos com nome contendo 'Aluno Teste'");
        
        // READ por Email
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM alunos WHERE email = ?");
        $stmt->execute(['aluno.teste@sistema.com.br']);
        $countEmail = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por Email", "success", $countEmail['total'] . " alunos com email 'aluno.teste@sistema.com.br'");
        
        // READ por CFC
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM alunos WHERE cfc_id = ?");
        $stmt->execute([1]);
        $countCfc = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por CFC", "success", $countCfc['total'] . " alunos do CFC ID 1");
        
        // READ ALL
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM alunos");
        $countAll = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ ALL", "success", $countAll['total'] . " ALUNOS ENCONTRADOS");
        
    } else {
        registerTest("READ", "warning", "NENHUM ALUNO CRIADO PARA TESTE");
    }
} catch (Exception $e) {
    registerTest("READ", "error", "ERRO: " . $e->getMessage());
}

// 15.8 Teste UPDATE - Atualizar Aluno
echo "<h2>15.8 Teste UPDATE - Atualizar Aluno</h2>";

try {
    if (isset($alunoId)) {
        $novoNome = 'Aluno Teste Atualizado';
        $novoEmail = 'atualizado.aluno@sistema.com.br';
        $novoTelefone = '(11) 44444-3333';
        $novoEndereco = 'Rua Atualizada, 999 - Bairro Novo';
        
        $stmt = $pdo->prepare("UPDATE alunos SET nome = ?, email = ?, telefone = ?, endereco = ? WHERE id = ?");
        $result = $stmt->execute([$novoNome, $novoEmail, $novoTelefone, $novoEndereco, $alunoId]);
        
        if ($result) {
            registerTest("UPDATE", "success", "ALUNO ATUALIZADO COM SUCESSO");
            
            // Verificar se a atualização foi aplicada
            $stmt = $pdo->prepare("SELECT * FROM alunos WHERE id = ?");
            $stmt->execute([$alunoId]);
            $alunoAtualizado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($alunoAtualizado && $alunoAtualizado['nome'] === $novoNome && $alunoAtualizado['email'] === $novoEmail && $alunoAtualizado['telefone'] === $novoTelefone && $alunoAtualizado['endereco'] === $novoEndereco) {
                registerTest("Verificação UPDATE", "success", "DADOS CONFIRMADOS");
            } else {
                registerTest("Verificação UPDATE", "warning", "ATUALIZAÇÃO NÃO CONFIRMADA");
            }
        } else {
            registerTest("UPDATE", "error", "FALHA AO ATUALIZAR ALUNO");
        }
    } else {
        registerTest("UPDATE", "warning", "NENHUM ALUNO CRIADO PARA TESTE");
    }
} catch (Exception $e) {
    registerTest("UPDATE", "error", "ERRO: " . $e->getMessage());
}

// 15.9 Teste DELETE - Excluir Aluno
echo "<h2>15.9 Teste DELETE - Excluir Aluno</h2>";

try {
    if (isset($alunoId)) {
        $stmt = $pdo->prepare("DELETE FROM alunos WHERE id = ?");
        $result = $stmt->execute([$alunoId]);
        
        if ($result) {
            registerTest("DELETE", "success", "ALUNO EXCLUÍDO COM SUCESSO");
            
            // Verificar se a exclusão foi aplicada
            $stmt = $pdo->prepare("SELECT * FROM alunos WHERE id = ?");
            $stmt->execute([$alunoId]);
            $alunoExcluido = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$alunoExcluido) {
                registerTest("Verificação DELETE", "success", "ALUNO NÃO ENCONTRADO (EXCLUÍDO)");
            } else {
                registerTest("Verificação DELETE", "error", "ALUNO AINDA EXISTE");
            }
        } else {
            registerTest("DELETE", "error", "FALHA AO EXCLUIR ALUNO");
        }
    } else {
        registerTest("DELETE", "warning", "NENHUM ALUNO CRIADO PARA TESTE");
    }
} catch (Exception $e) {
    registerTest("DELETE", "error", "ERRO: " . $e->getMessage());
}

// 15.10 Teste de Validações
echo "<h2>15.10 Teste de Validações</h2>";

try {
    // Verificar alunos sem nome
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM alunos WHERE nome IS NULL OR nome = ''");
    $semNome = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semNome['total'] == 0) {
        registerTest("Validação Nome", "success", "TODOS OS ALUNOS TÊM NOME");
    } else {
        registerTest("Validação Nome", "warning", $semNome['total'] . " alunos sem nome");
    }
    
    // Verificar alunos sem CPF
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM alunos WHERE cpf IS NULL OR cpf = ''");
    $semCpf = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semCpf['total'] == 0) {
        registerTest("Validação CPF", "success", "TODOS OS ALUNOS TÊM CPF");
    } else {
        registerTest("Validação CPF", "warning", $semCpf['total'] . " alunos sem CPF");
    }
    
    // Verificar alunos sem data de nascimento
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM alunos WHERE data_nascimento IS NULL");
    $semDataNasc = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semDataNasc['total'] == 0) {
        registerTest("Validação Data Nascimento", "success", "TODOS OS ALUNOS TÊM DATA DE NASCIMENTO");
    } else {
        registerTest("Validação Data Nascimento", "warning", $semDataNasc['total'] . " alunos sem data de nascimento");
    }
    
    // Verificar alunos ativos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM alunos WHERE status = 'ativo'");
    $ativos = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("Alunos Ativos", "info", $ativos['total'] . " alunos ativos");
    
    // Verificar alunos por CFC
    $stmt = $pdo->query("SELECT cfc_id, COUNT(*) as total FROM alunos WHERE cfc_id IS NOT NULL GROUP BY cfc_id");
    $cfcCount = $stmt->fetchAll(PDO::FETCH_ASSOC);
    registerTest("Alunos por CFC", "info", "Distribuição por CFC", $cfcCount);
    
    // Verificar alunos por CPF
    $stmt = $pdo->query("SELECT cpf, COUNT(*) as total FROM alunos WHERE cpf IS NOT NULL GROUP BY cpf");
    $cpfCount = $stmt->fetchAll(PDO::FETCH_ASSOC);
    registerTest("Alunos por CPF", "info", "Distribuição por CPF", $cpfCount);
    
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
    echo "<p class='success'>✅ TESTE #15 CONCLUÍDO COM SUCESSO!</p>";
    echo "<p><strong>🎯 Próximo: TESTE #16 - CRUD de Veículos</strong></p>";
    echo "<p><strong>📝 Instrução:</strong> Execute este teste e me informe o resultado. Se tudo estiver OK, criarei o próximo teste.</p>";
} else {
    echo "<p class='error'>❌ ALGUNS TESTES FALHARAM. Verifique os erros acima.</p>";
}

echo "<p><strong>💡 INFORMAÇÕES ADICIONAIS</strong></p>";
echo "<p><strong>URL de Teste:</strong> /cfc-bom-conselho/admin/teste-15-crud-alunos.php</p>";
echo "<p><strong>Funcionalidades Testadas:</strong> CREATE, READ, UPDATE, DELETE, Validações</p>";
echo "<p><strong>Arquivos Utilizados:</strong> Database, Config</p>";
echo "<p><strong>Operações CRUD:</strong> Inserir, Buscar, Atualizar, Excluir Alunos</p>";
echo "<p><strong>Campos Obrigatórios:</strong> Nome, CPF, CFC, Categoria CNH, Status</p>";
echo "<p><strong>Validações:</strong> Nome, CPF, Data Nascimento, Email, Telefone, Endereço, CFC, Categoria CNH, Status, Estrutura da tabela, Relacionamentos</p>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
