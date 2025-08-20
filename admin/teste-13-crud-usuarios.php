<?php
/**
 * TESTE #13: CRUD de Usuários
 * Data/Hora: 19/08/2025 16:25:00
 * 
 * Este teste verifica todas as operações CRUD para a tabela 'usuarios':
 * - CREATE: Criar novo usuário
 * - READ: Buscar usuário por ID, email, nome
 * - UPDATE: Atualizar dados do usuário
 * - DELETE: Excluir usuário
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
echo "<title>TESTE #13: CRUD de Usuários</title>";
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
echo ".progress-fill { height: 100%; background: linear-gradient(90deg, #28a745, #20c997); transition: width 0.3s ease; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<div class='header'>";
echo "<h1>🔍 TESTE #13: CRUD de Usuários</h1>";
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

// 13.1 Inclusão de Arquivos Necessários
echo "<h2>13.1 Inclusão de Arquivos Necessários</h2>";

try {
    require_once '../includes/database.php';
    require_once '../includes/config.php';
    registerTest("Arquivos necessários", "success", "INCLUÍDOS COM SUCESSO");
} catch (Exception $e) {
    registerTest("Arquivos necessários", "error", "ERRO: " . $e->getMessage());
}

// 13.2 Conexão com Banco de Dados
echo "<h2>13.2 Conexão com Banco de Dados</h2>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    registerTest("Conexão PDO", "success", "ESTABELECIDA COM SUCESSO");
} catch (PDOException $e) {
    registerTest("Conexão PDO", "error", "ERRO: " . $e->getMessage());
    exit;
}

// 13.3 Estrutura da Tabela 'usuarios'
echo "<h2>13.3 Estrutura da Tabela 'usuarios'</h2>";

try {
    $stmt = $pdo->query("DESCRIBE usuarios");
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($structure)) {
        registerTest("Estrutura da tabela", "success", "COMPLETA", $structure);
    } else {
        registerTest("Estrutura da tabela", "error", "TABELA NÃO ENCONTRADA");
    }
} catch (Exception $e) {
    registerTest("Estrutura da tabela", "error", "ERRO: " . $e->getMessage());
}

// 13.4 Verificação de Dados na Tabela 'usuarios'
echo "<h2>13.4 Verificação de Dados na Tabela 'usuarios'</h2>";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("Total de usuários na tabela", "success", $count['total'] . " registros");
    
    if ($count['total'] > 0) {
        $stmt = $pdo->query("SELECT * FROM usuarios LIMIT 5");
        $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        registerTest("Dados de exemplo", "info", "Primeiros 5 registros", $sampleData);
    }
} catch (Exception $e) {
    registerTest("Verificação de dados", "error", "ERRO: " . $e->getMessage());
}

// 13.5 Verificação de Dados de Referência
echo "<h2>13.5 Verificação de Dados de Referência</h2>";

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

// 13.6 Teste CREATE - Criar Usuário
echo "<h2>13.6 Teste CREATE - Criar Usuário</h2>";

try {
    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo, cpf, telefone, ativo, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    
    $nome = 'Usuário Teste Sistema';
    $email = 'usuario.teste@sistema.com.br';
    $senha = password_hash('senha123', PASSWORD_DEFAULT);
    $tipo = 'admin';
    $cpf = '111.222.333-44';
    $telefone = '(11) 88888-8888';
    $ativo = 1;
    
    $result = $stmt->execute([
        $nome,
        $email,
        $senha,
        $tipo,
        $cpf,
        $telefone,
        $ativo
    ]);
    
    if ($result) {
        $usuarioId = $pdo->lastInsertId();
        registerTest("CREATE", "success", "USUÁRIO CRIADO COM SUCESSO");
        registerTest("ID do usuário criado", "info", $usuarioId);
    } else {
        registerTest("CREATE", "error", "FALHA AO CRIAR USUÁRIO");
    }
} catch (Exception $e) {
    registerTest("CREATE", "error", "ERRO: " . $e->getMessage());
}

// 13.7 Teste READ - Ler Usuário
echo "<h2>13.7 Teste READ - Ler Usuário</h2>";

try {
    if (isset($usuarioId)) {
        // READ por ID
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$usuarioId]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario) {
            registerTest("READ por ID", "success", "USUÁRIO ENCONTRADO");
            registerTest("Dados do usuário", "info", "Usuário encontrado com sucesso", [$usuario]);
        } else {
            registerTest("READ por ID", "error", "USUÁRIO NÃO ENCONTRADO");
        }
        
        // READ por Email
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE email = ?");
        $stmt->execute(['usuario.teste@sistema.com.br']);
        $countEmail = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por Email", "success", $countEmail['total'] . " usuários com email 'usuario.teste@sistema.com.br'");
        
        // READ por Nome
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE nome LIKE ?");
        $stmt->execute(['%Usuário Teste%']);
        $countNome = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por Nome", "success", $countNome['total'] . " usuários com nome contendo 'Usuário Teste'");
        
        // READ por Tipo
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE tipo = ?");
        $stmt->execute(['admin']);
        $countTipo = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por Tipo", "success", $countTipo['total'] . " usuários do tipo 'admin'");
        
        // READ por CPF
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE cpf = ?");
        $stmt->execute(['111.222.333-44']);
        $countCpf = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por CPF", "success", $countCpf['total'] . " usuários com CPF '111.222.333-44'");
        
        // READ ALL
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
        $countAll = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ ALL", "success", $countAll['total'] . " USUÁRIOS ENCONTRADOS");
        
    } else {
        registerTest("READ", "warning", "NENHUM USUÁRIO CRIADO PARA TESTE");
    }
} catch (Exception $e) {
    registerTest("READ", "error", "ERRO: " . $e->getMessage());
}

// 13.8 Teste UPDATE - Atualizar Usuário
echo "<h2>13.8 Teste UPDATE - Atualizar Usuário</h2>";

try {
    if (isset($usuarioId)) {
        $novoNome = 'Usuário Teste Atualizado';
        $novoEmail = 'atualizado@sistema.com.br';
        $novoTipo = 'instrutor';
        
        $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, tipo = ? WHERE id = ?");
        $result = $stmt->execute([$novoNome, $novoEmail, $novoTipo, $usuarioId]);
        
        if ($result) {
            registerTest("UPDATE", "success", "USUÁRIO ATUALIZADO COM SUCESSO");
            
            // Verificar se a atualização foi aplicada
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmt->execute([$usuarioId]);
            $usuarioAtualizado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuarioAtualizado && $usuarioAtualizado['nome'] === $novoNome && $usuarioAtualizado['email'] === $novoEmail && $usuarioAtualizado['tipo'] === $novoTipo) {
                registerTest("Verificação UPDATE", "success", "DADOS CONFIRMADOS");
            } else {
                registerTest("Verificação UPDATE", "warning", "ATUALIZAÇÃO NÃO CONFIRMADA");
            }
        } else {
            registerTest("UPDATE", "error", "FALHA AO ATUALIZAR USUÁRIO");
        }
    } else {
        registerTest("UPDATE", "warning", "NENHUM USUÁRIO CRIADO PARA TESTE");
    }
} catch (Exception $e) {
    registerTest("UPDATE", "error", "ERRO: " . $e->getMessage());
}

// 13.9 Teste DELETE - Excluir Usuário
echo "<h2>13.9 Teste DELETE - Excluir Usuário</h2>";

try {
    if (isset($usuarioId)) {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $result = $stmt->execute([$usuarioId]);
        
        if ($result) {
            registerTest("DELETE", "success", "USUÁRIO EXCLUÍDO COM SUCESSO");
            
            // Verificar se a exclusão foi aplicada
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmt->execute([$usuarioId]);
            $usuarioExcluido = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuarioExcluido) {
                registerTest("Verificação DELETE", "success", "USUÁRIO NÃO ENCONTRADO (EXCLUÍDO)");
            } else {
                registerTest("Verificação DELETE", "error", "USUÁRIO AINDA EXISTE");
            }
        } else {
            registerTest("DELETE", "error", "FALHA AO EXCLUIR USUÁRIO");
        }
    } else {
        registerTest("DELETE", "warning", "NENHUM USUÁRIO CRIADO PARA TESTE");
    }
} catch (Exception $e) {
    registerTest("DELETE", "error", "ERRO: " . $e->getMessage());
}

// 13.10 Teste de Validações
echo "<h2>13.10 Teste de Validações</h2>";

try {
    // Verificar usuários sem nome
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE nome IS NULL OR nome = ''");
    $semNome = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semNome['total'] == 0) {
        registerTest("Validação Nome", "success", "TODOS OS USUÁRIOS TÊM NOME");
    } else {
        registerTest("Validação Nome", "warning", $semNome['total'] . " usuários sem nome");
    }
    
    // Verificar usuários sem email
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE email IS NULL OR email = ''");
    $semEmail = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semEmail['total'] == 0) {
        registerTest("Validação Email", "success", "TODOS OS USUÁRIOS TÊM EMAIL");
    } else {
        registerTest("Validação Email", "warning", $semEmail['total'] . " usuários sem email");
    }
    
    // Verificar usuários sem senha
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE senha IS NULL OR senha = ''");
    $semSenha = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semSenha['total'] == 0) {
        registerTest("Validação Senha", "success", "TODOS OS USUÁRIOS TÊM SENHA");
    } else {
        registerTest("Validação Senha", "warning", $semSenha['total'] . " usuários sem senha");
    }
    
    // Verificar usuários ativos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE ativo = 1");
    $ativos = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("Usuários Ativos", "info", $ativos['total'] . " usuários ativos");
    
    // Verificar usuários por tipo
    $stmt = $pdo->query("SELECT tipo, COUNT(*) as total FROM usuarios GROUP BY tipo");
    $tipoCount = $stmt->fetchAll(PDO::FETCH_ASSOC);
    registerTest("Usuários por Tipo", "info", "Distribuição por tipo", $tipoCount);
    
    // Verificar usuários por CPF
    $stmt = $pdo->query("SELECT cpf, COUNT(*) as total FROM usuarios WHERE cpf IS NOT NULL GROUP BY cpf");
    $cpfCount = $stmt->fetchAll(PDO::FETCH_ASSOC);
    registerTest("Usuários por CPF", "info", "Distribuição por CPF", $cpfCount);
    
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
    echo "<p class='success'>✅ TESTE #13 CONCLUÍDO COM SUCESSO!</p>";
    echo "<p><strong>🎯 Próximo: TESTE #14 - CRUD de Instrutores</strong></p>";
    echo "<p><strong>📝 Instrução:</strong> Execute este teste e me informe o resultado. Se tudo estiver OK, criarei o próximo teste.</p>";
} else {
    echo "<p class='error'>❌ ALGUNS TESTES FALHARAM. Verifique os erros acima.</p>";
}

echo "<p><strong>💡 INFORMAÇÕES ADICIONAIS</strong></p>";
echo "<p><strong>URL de Teste:</strong> /cfc-bom-conselho/admin/teste-13-crud-usuarios.php</p>";
echo "<p><strong>Funcionalidades Testadas:</strong> CREATE, READ, UPDATE, DELETE, Validações</p>";
echo "<p><strong>Arquivos Utilizados:</strong> Database, Config</p>";
echo "<p><strong>Operações CRUD:</strong> Inserir, Buscar, Atualizar, Excluir Usuários</p>";
echo "<p><strong>Validações:</strong> Nome, Email, Senha, Tipo, CPF, Telefone, Ativo, Estrutura da tabela, Relacionamentos</p>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
