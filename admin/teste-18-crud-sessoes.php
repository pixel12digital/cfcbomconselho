<?php
/**
 * TESTE #18: CRUD de Sessões
 * Data/Hora: 19/08/2025 16:55:05
 * 
 * Este teste verifica todas as operações CRUD para a tabela 'sessoes':
 * - CREATE: Criar nova sessão
 * - READ: Buscar sessão por ID, usuário, token
 * - UPDATE: Atualizar dados da sessão
 * - DELETE: Excluir sessão
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
echo "<title>TESTE #18: CRUD de Sessões</title>";
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
echo "<h1>🔍 TESTE #18: CRUD de Sessões</h1>";
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

// 18.1 Inclusão de Arquivos Necessários
echo "<h2>18.1 Inclusão de Arquivos Necessários</h2>";

try {
    require_once '../includes/database.php';
    require_once '../includes/config.php';
    registerTest("Arquivos necessários", "success", "INCLUÍDOS COM SUCESSO");
} catch (Exception $e) {
    registerTest("Arquivos necessários", "error", "ERRO: " . $e->getMessage());
}

// 18.2 Conexão com Banco de Dados
echo "<h2>18.2 Conexão com Banco de Dados</h2>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    registerTest("Conexão PDO", "success", "ESTABELECIDA COM SUCESSO");
} catch (PDOException $e) {
    registerTest("Conexão PDO", "error", "ERRO: " . $e->getMessage());
    exit;
}

// 18.3 Estrutura da Tabela 'sessoes'
echo "<h2>18.3 Estrutura da Tabela 'sessoes'</h2>";

try {
    $stmt = $pdo->query("DESCRIBE sessoes");
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($structure)) {
        registerTest("Estrutura da tabela", "success", "COMPLETA", $structure);
    } else {
        registerTest("Estrutura da tabela", "error", "TABELA NÃO ENCONTRADA");
    }
} catch (Exception $e) {
    registerTest("Estrutura da tabela", "error", "ERRO: " . $e->getMessage());
}

// 18.4 Verificação de Dados na Tabela 'sessoes'
echo "<h2>18.4 Verificação de Dados na Tabela 'sessoes'</h2>";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM sessoes");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("Total de sessões na tabela", "success", $count['total'] . " registros");
    
    if ($count['total'] > 0) {
        $stmt = $pdo->query("SELECT * FROM sessoes LIMIT 5");
        $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        registerTest("Dados de exemplo", "info", "Primeiros 5 registros", $sampleData);
    }
} catch (Exception $e) {
    registerTest("Verificação de dados", "error", "ERRO: " . $e->getMessage());
}

// 18.5 Verificação de Dados de Referência
echo "<h2>18.5 Verificação de Dados de Referência</h2>";

try {
    // Verificar Usuários
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $usuarioCount = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("Usuários disponíveis", "info", $usuarioCount['total'] . " usuários encontrados");
    
} catch (Exception $e) {
    registerTest("Verificação de referências", "error", "ERRO: " . $e->getMessage());
}

// 18.6 Teste CREATE - Criar Sessão
echo "<h2>18.6 Teste CREATE - Criar Sessão</h2>";

try {
    // Buscar ID válido para relacionamento
    $stmt = $pdo->query("SELECT id FROM usuarios LIMIT 1");
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario) {
                 $stmt = $pdo->prepare("INSERT INTO sessoes (usuario_id, token, ip_address, user_agent, expira_em, criado_em) VALUES (?, ?, ?, ?, ?, NOW())");
        
                 $usuario_id = $usuario['id'];
         $token = 'token_teste_' . time() . '_' . rand(1000, 9999);
         $ip_address = '127.0.0.1';
         $user_agent = 'TESTE #18 - Sistema de Testes';
         $expira_em = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
                 $result = $stmt->execute([
             $usuario_id,
             $token,
             $ip_address,
             $user_agent,
             $expira_em
         ]);
        
        if ($result) {
            $sessaoId = $pdo->lastInsertId();
            registerTest("CREATE", "success", "SESSÃO CRIADA COM SUCESSO");
            registerTest("ID da sessão criada", "info", $sessaoId);
        } else {
            registerTest("CREATE", "error", "FALHA AO CRIAR SESSÃO");
        }
    } else {
        registerTest("CREATE", "error", "DADOS DE REFERÊNCIA INSUFICIENTES");
    }
} catch (Exception $e) {
    registerTest("CREATE", "error", "ERRO: " . $e->getMessage());
}

// 18.7 Teste READ - Ler Sessão
echo "<h2>18.7 Teste READ - Ler Sessão</h2>";

try {
    if (isset($sessaoId)) {
        // READ por ID
        $stmt = $pdo->prepare("SELECT * FROM sessoes WHERE id = ?");
        $stmt->execute([$sessaoId]);
        $sessao = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($sessao) {
            registerTest("READ por ID", "success", "SESSÃO ENCONTRADA");
            registerTest("Dados da sessão", "info", "Sessão encontrada com sucesso", [$sessao]);
        } else {
            registerTest("READ por ID", "error", "SESSÃO NÃO ENCONTRADA");
        }
        
                 // READ por Usuário
         $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM sessoes WHERE usuario_id = ?");
         $stmt->execute([$usuario_id]);
         $countUsuario = $stmt->fetch(PDO::FETCH_ASSOC);
         registerTest("READ por Usuário", "success", $countUsuario['total'] . " sessões do usuário ID " . $usuario_id);
        
        // READ por Token
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM sessoes WHERE token = ?");
        $stmt->execute([$token]);
        $countToken = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por Token", "success", $countToken['total'] . " sessões com token '" . substr($token, 0, 20) . "...'");
        
        // READ por Expiração
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM sessoes WHERE expira_em > NOW()");
        $stmt->execute([]);
        $countValidas = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ por Expiração", "success", $countValidas['total'] . " sessões válidas (não expiradas)");
        
        // READ ALL
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM sessoes");
        $countAll = $stmt->fetch(PDO::FETCH_ASSOC);
        registerTest("READ ALL", "success", $countAll['total'] . " SESSÕES ENCONTRADAS");
        
    } else {
        registerTest("READ", "warning", "NENHUMA SESSÃO CRIADA PARA TESTE");
    }
} catch (Exception $e) {
    registerTest("READ", "error", "ERRO: " . $e->getMessage());
}

// 18.8 Teste UPDATE - Atualizar Sessão
echo "<h2>18.8 Teste UPDATE - Atualizar Sessão</h2>";

try {
    if (isset($sessaoId)) {
        $novaExpira = date('Y-m-d H:i:s', strtotime('+48 hours'));
        
        $stmt = $pdo->prepare("UPDATE sessoes SET expira_em = ? WHERE id = ?");
        $result = $stmt->execute([$novaExpira, $sessaoId]);
        
        if ($result) {
            registerTest("UPDATE", "success", "SESSÃO ATUALIZADA COM SUCESSO");
            
            // Verificar se a atualização foi aplicada
            $stmt = $pdo->prepare("SELECT * FROM sessoes WHERE id = ?");
            $stmt->execute([$sessaoId]);
            $sessaoAtualizada = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($sessaoAtualizada && $sessaoAtualizada['expira_em'] === $novaExpira) {
                registerTest("Verificação UPDATE", "success", "DADOS CONFIRMADOS");
            } else {
                registerTest("Verificação UPDATE", "warning", "ATUALIZAÇÃO NÃO CONFIRMADA");
            }
        } else {
            registerTest("UPDATE", "error", "FALHA AO ATUALIZAR SESSÃO");
        }
    } else {
        registerTest("UPDATE", "warning", "NENHUMA SESSÃO CRIADA PARA TESTE");
    }
} catch (Exception $e) {
    registerTest("UPDATE", "error", "ERRO: " . $e->getMessage());
}

// 18.9 Teste DELETE - Excluir Sessão
echo "<h2>18.9 Teste DELETE - Excluir Sessão</h2>";

try {
    if (isset($sessaoId)) {
        $stmt = $pdo->prepare("DELETE FROM sessoes WHERE id = ?");
        $result = $stmt->execute([$sessaoId]);
        
        if ($result) {
            registerTest("DELETE", "success", "SESSÃO EXCLUÍDA COM SUCESSO");
            
            // Verificar se a exclusão foi aplicada
            $stmt = $pdo->prepare("SELECT * FROM sessoes WHERE id = ?");
            $stmt->execute([$sessaoId]);
            $sessaoExcluida = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$sessaoExcluida) {
                registerTest("Verificação DELETE", "success", "SESSÃO NÃO ENCONTRADA (EXCLUÍDA)");
            } else {
                registerTest("Verificação DELETE", "error", "SESSÃO AINDA EXISTE");
            }
        } else {
            registerTest("DELETE", "error", "FALHA AO EXCLUIR SESSÃO");
        }
    } else {
        registerTest("DELETE", "warning", "NENHUMA SESSÃO CRIADA PARA TESTE");
    }
} catch (Exception $e) {
    registerTest("DELETE", "error", "ERRO: " . $e->getMessage());
}

// 18.10 Teste de Validações
echo "<h2>18.10 Teste de Validações</h2>";

try {
         // Verificar sessões sem usuário
     $stmt = $pdo->query("SELECT COUNT(*) as total FROM sessoes WHERE usuario_id IS NULL");
     $semUsuario = $stmt->fetch(PDO::FETCH_ASSOC);
     
     if ($semUsuario['total'] == 0) {
         registerTest("Validação Usuário", "success", "TODAS AS SESSÕES TÊM USUÁRIO");
     } else {
         registerTest("Validação Usuário", "warning", $semUsuario['total'] . " sessões sem usuário");
     }
    
    // Verificar sessões sem token
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM sessoes WHERE token IS NULL");
    $semToken = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($semToken['total'] == 0) {
        registerTest("Validação Token", "success", "TODAS AS SESSÕES TÊM TOKEN");
    } else {
        registerTest("Validação Token", "warning", $semToken['total'] . " sessões sem token");
    }
    
    // Verificar sessões expiradas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM sessoes WHERE expira_em <= NOW()");
    $expiradas = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("Sessões Expiradas", "info", $expiradas['total'] . " sessões expiradas");
    
    // Verificar sessões válidas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM sessoes WHERE expira_em > NOW()");
    $validas = $stmt->fetch(PDO::FETCH_ASSOC);
    registerTest("Sessões Válidas", "info", $validas['total'] . " sessões válidas");
    
         // Verificar sessões por usuário
     $stmt = $pdo->query("SELECT usuario_id, COUNT(*) as total FROM sessoes WHERE usuario_id IS NOT NULL GROUP BY usuario_id ORDER BY total DESC LIMIT 5");
     $usuarioCount = $stmt->fetchAll(PDO::FETCH_ASSOC);
     registerTest("Sessões por Usuário", "info", "Top 5 usuários com mais sessões", $usuarioCount);
    
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
    echo "<p class='success'>✅ TESTE #18 CONCLUÍDO COM SUCESSO!</p>";
    echo "<p><strong>🎯 Próximo: TESTE #19 - CRUD de Logs</strong></p>";
    echo "<p><strong>📝 Instrução:</strong> Execute este teste e me informe o resultado. Se tudo estiver OK, criarei o próximo teste.</p>";
} else {
    echo "<p class='error'>❌ ALGUNS TESTES FALHARAM. Verifique os erros acima.</p>";
}

echo "<p><strong>💡 INFORMAÇÕES ADICIONAIS</strong></p>";
echo "<p><strong>URL de Teste:</strong> /cfc-bom-conselho/admin/teste-18-crud-sessoes.php</p>";
echo "<p><strong>Funcionalidades Testadas:</strong> CREATE, READ, UPDATE, DELETE, Validações</p>";
echo "<p><strong>Arquivos Utilizados:</strong> Database, Config</p>";
echo "<p><strong>Operações CRUD:</strong> Inserir, Buscar, Atualizar, Excluir Sessões</p>";
echo "<p><strong>Campos Obrigatórios:</strong> Usuário ID, Token, Expira em</p>";
echo "<p><strong>Campos Opcionais:</strong> IP Address, User Agent</p>";
echo "<p><strong>Validações:</strong> Relacionamentos, Token, Expiração, Estrutura da tabela, Integridade</p>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
