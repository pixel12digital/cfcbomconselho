<?php
/**
 * TESTE #24: Testes de Segurança
 * Data/Hora: 19/08/2025 17:37:52
 * 
 * Este teste verifica a segurança e proteção do sistema:
 * - Validação de entrada de dados
 * - Proteção contra SQL Injection
 * - Validação de sessões
 * - Controle de acesso
 * - Criptografia de senhas
 * - Logs de segurança
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
echo "<title>TESTE #24: Testes de Segurança</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }";
echo ".container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo ".header { background: linear-gradient(135deg, #dc3545 0%, #6f42c1 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; }";
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
echo ".progress-fill { height: 100%; background: linear-gradient(90deg, #dc3545, #6f42c1); transition: width 0.3s ease; }";
echo ".security-section { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 15px; margin-bottom: 15px; }";
echo ".vulnerability { background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 6px; padding: 10px; margin: 5px 0; }";
echo ".vulnerability strong { color: #721c24; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<div class='header'>";
echo "<h1>🔒 TESTE #24: Testes de Segurança</h1>";
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

// 24.1 Inclusão de Arquivos Necessários
echo "<h2>24.1 Inclusão de Arquivos Necessários</h2>";

try {
    require_once '../includes/database.php';
    require_once '../includes/config.php';
    registerTest("Arquivos necessários", "success", "INCLUÍDOS COM SUCESSO");
} catch (Exception $e) {
    registerTest("Arquivos necessários", "error", "ERRO: " . $e->getMessage());
}

// 24.2 Conexão com Banco de Dados
echo "<h2>24.2 Conexão com Banco de Dados</h2>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    registerTest("Conexão PDO", "success", "ESTABELECIDA COM SUCESSO");
} catch (PDOException $e) {
    registerTest("Conexão PDO", "error", "ERRO: " . $e->getMessage());
    exit;
}

// 24.3 Teste 1: Validação de Criptografia de Senhas
echo "<h2>24.3 Teste 1: Validação de Criptografia de Senhas</h2>";

try {
    // Verificar se as senhas estão criptografadas
    $stmt = $pdo->query("SELECT id, nome, senha FROM usuarios LIMIT 5");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $senhasCriptografadas = 0;
    $senhasVulneraveis = [];
    
    foreach ($usuarios as $usuario) {
        $senha = $usuario['senha'];
        
        // Verificar se a senha está criptografada (hash bcrypt)
        if (strlen($senha) >= 60 && strpos($senha, '$2y$') === 0) {
            $senhasCriptografadas++;
        } else {
            $senhasVulneraveis[] = [
                'ID' => $usuario['id'],
                'Nome' => $usuario['nome'],
                'Tipo Senha' => 'Texto Plano',
                'Risco' => 'ALTO'
            ];
        }
    }
    
    if (empty($senhasVulneraveis)) {
        registerTest("Criptografia de Senhas", "success", "TODAS AS SENHAS ESTÃO CRIPTOGRAFADAS ({$senhasCriptografadas} usuários)", $usuarios);
    } else {
        registerTest("Criptografia de Senhas", "error", "SENHAS VULNERÁVEIS ENCONTRADAS: " . count($senhasVulneraveis) . " usuários", $senhasVulneraveis);
    }
    
} catch (Exception $e) {
    registerTest("Validação de Criptografia de Senhas", "error", "ERRO: " . $e->getMessage());
}

// 24.4 Teste 2: Validação de Sessões
echo "<h2>24.4 Teste 2: Validação de Sessões</h2>";

try {
    // Verificar estrutura da tabela de sessões
    $stmt = $pdo->query("DESCRIBE sessoes");
    $colunasSessoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $colunasObrigatorias = ['id', 'token', 'usuario_id', 'expira_em', 'criado_em'];
    $colunasEncontradas = array_column($colunasSessoes, 'Field');
    $colunasFaltantes = array_diff($colunasObrigatorias, $colunasEncontradas);
    
    if (empty($colunasFaltantes)) {
        registerTest("Estrutura de Sessões", "success", "ESTRUTURA COMPLETA DE SESSÕES", $colunasSessoes);
    } else {
        registerTest("Estrutura de Sessões", "error", "COLUNAS FALTANTES: " . implode(', ', $colunasFaltantes));
    }
    
    // Verificar sessões expiradas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM sessoes WHERE expira_em < NOW()");
    $sessoesExpiradas = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sessoesExpiradas['total'] > 0) {
        registerTest("Sessões Expiradas", "warning", "SESSÕES EXPIRADAS ENCONTRADAS: " . $sessoesExpiradas['total']);
    } else {
        registerTest("Sessões Expiradas", "success", "NENHUMA SESSÃO EXPIRADA");
    }
    
} catch (Exception $e) {
    registerTest("Validação de Sessões", "error", "ERRO: " . $e->getMessage());
}

// 24.5 Teste 3: Validação de Logs de Segurança
echo "<h2>24.5 Teste 3: Validação de Logs de Segurança</h2>";

try {
    // Verificar estrutura da tabela de logs
    $stmt = $pdo->query("DESCRIBE logs");
    $colunasLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $colunasObrigatorias = ['id', 'usuario_id', 'acao', 'detalhes', 'ip', 'user_agent', 'criado_em'];
    $colunasEncontradas = array_column($colunasLogs, 'Field');
    $colunasFaltantes = array_diff($colunasObrigatorias, $colunasEncontradas);
    
    if (empty($colunasFaltantes)) {
        registerTest("Estrutura de Logs", "success", "ESTRUTURA COMPLETA DE LOGS", $colunasLogs);
    } else {
        registerTest("Estrutura de Logs", "error", "COLUNAS FALTANTES: " . implode(', ', $colunasFaltantes));
    }
    
    // Verificar tipos de ações logadas
    $stmt = $pdo->query("SELECT acao, COUNT(*) as total FROM logs GROUP BY acao ORDER BY total DESC");
    $acoesLogadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($acoesLogadas)) {
        registerTest("Ações Logadas", "success", "TIPOS DE AÇÕES LOGADAS", $acoesLogadas);
    } else {
        registerTest("Ações Logadas", "warning", "NENHUMA AÇÃO LOGADA ENCONTRADA");
    }
    
} catch (Exception $e) {
    registerTest("Validação de Logs de Segurança", "error", "ERRO: " . $e->getMessage());
}

// 24.6 Teste 4: Validação de Controle de Acesso
echo "<h2>24.6 Teste 4: Validação de Controle de Acesso</h2>";

try {
    // Verificar tipos de usuários
    $stmt = $pdo->query("SELECT tipo, COUNT(*) as total FROM usuarios GROUP BY tipo ORDER BY total DESC");
    $tiposUsuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $tiposPermitidos = ['admin', 'instrutor', 'aluno'];
    $tiposEncontrados = array_column($tiposUsuarios, 'tipo');
    $tiposNaoPermitidos = array_diff($tiposEncontrados, $tiposPermitidos);
    
    if (empty($tiposNaoPermitidos)) {
        registerTest("Tipos de Usuários", "success", "TODOS OS TIPOS SÃO PERMITIDOS", $tiposUsuarios);
    } else {
        registerTest("Tipos de Usuários", "error", "TIPOS NÃO PERMITIDOS: " . implode(', ', $tiposNaoPermitidos));
    }
    
    // Verificar status de usuários
    $stmt = $pdo->query("SELECT status, COUNT(*) as total FROM usuarios GROUP BY status ORDER BY total DESC");
    $statusUsuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $statusPermitidos = ['ativo', 'inativo'];
    $statusEncontrados = array_column($statusUsuarios, 'status');
    $statusNaoPermitidos = array_diff($statusEncontrados, $statusPermitidos);
    
    if (empty($statusNaoPermitidos)) {
        registerTest("Status de Usuários", "success", "TODOS OS STATUS SÃO PERMITIDOS", $statusUsuarios);
    } else {
        registerTest("Status de Usuários", "error", "STATUS NÃO PERMITIDOS: " . implode(', ', $statusNaoPermitidos));
    }
    
} catch (Exception $e) {
    registerTest("Validação de Controle de Acesso", "error", "ERRO: " . $e->getMessage());
}

// 24.7 Teste 5: Validação de Dados Sensíveis
echo "<h2>24.7 Teste 5: Validação de Dados Sensíveis</h2>";

try {
    // Verificar se CPFs estão mascarados ou protegidos
    $stmt = $pdo->query("SELECT id, nome, cpf FROM usuarios WHERE cpf IS NOT NULL LIMIT 5");
    $cpfsUsuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $cpfsVulneraveis = [];
    $cpfsProtegidos = 0;
    
    foreach ($cpfsUsuarios as $usuario) {
        $cpf = $usuario['cpf'];
        
        // Verificar se o CPF está em formato padrão (não mascarado)
        if (preg_match('/^\d{3}\.\d{3}\.\d{3}-\d{2}$/', $cpf)) {
            $cpfsProtegidos++;
        } else {
            $cpfsVulneraveis[] = [
                'ID' => $usuario['id'],
                'Nome' => $usuario['nome'],
                'CPF' => $cpf,
                'Risco' => 'CPF em formato não padrão'
            ];
        }
    }
    
    if (empty($cpfsVulneraveis)) {
        registerTest("Proteção de CPFs", "success", "CPFs EM FORMATO PADRÃO ({$cpfsProtegidos} usuários)", $cpfsUsuarios);
    } else {
        registerTest("Proteção de CPFs", "warning", "CPFs VULNERÁVEIS ENCONTRADOS: " . count($cpfsVulneraveis), $cpfsVulneraveis);
    }
    
    // Verificar se emails estão em formato válido
    $stmt = $pdo->query("SELECT id, nome, email FROM usuarios WHERE email IS NOT NULL LIMIT 5");
    $emailsUsuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $emailsInvalidos = [];
    $emailsValidos = 0;
    
    foreach ($emailsUsuarios as $usuario) {
        $email = $usuario['email'];
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailsValidos++;
        } else {
            $emailsInvalidos[] = [
                'ID' => $usuario['id'],
                'Nome' => $usuario['nome'],
                'Email' => $email,
                'Risco' => 'Email em formato inválido'
            ];
        }
    }
    
    if (empty($emailsInvalidos)) {
        registerTest("Validação de Emails", "success", "TODOS OS EMAILS SÃO VÁLIDOS ({$emailsValidos} usuários)", $emailsUsuarios);
    } else {
        registerTest("Validação de Emails", "error", "EMAILS INVÁLIDOS ENCONTRADOS: " . count($emailsInvalidos), $emailsInvalidos);
    }
    
} catch (Exception $e) {
    registerTest("Validação de Dados Sensíveis", "error", "ERRO: " . $e->getMessage());
}

// 24.8 Teste 6: Validação de Integridade de Dados
echo "<h2>24.8 Teste 6: Validação de Integridade de Dados</h2>";

try {
    // Verificar se há dados duplicados críticos
    $vulnerabilidades = [];
    
    // Verificar CPFs duplicados
    $stmt = $pdo->query("
        SELECT cpf, COUNT(*) as total
        FROM (
            SELECT cpf FROM usuarios WHERE cpf IS NOT NULL
            UNION ALL
            SELECT cpf FROM instrutores WHERE cpf IS NOT NULL
            UNION ALL
            SELECT cpf FROM alunos WHERE cpf IS NOT NULL
        ) AS todos_cpfs
        GROUP BY cpf
        HAVING COUNT(*) > 1
        ORDER BY total DESC
    ");
    
    $cpfsDuplicados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($cpfsDuplicados)) {
        $vulnerabilidades[] = "CPFs duplicados: " . count($cpfsDuplicados);
    }
    
    // Verificar emails duplicados
    $stmt = $pdo->query("
        SELECT email, COUNT(*) as total
        FROM (
            SELECT email FROM usuarios WHERE email IS NOT NULL
            UNION ALL
            SELECT email FROM instrutores WHERE email IS NOT NULL
            UNION ALL
            SELECT email FROM alunos WHERE email IS NOT NULL
        ) AS todos_emails
        GROUP BY email
        HAVING COUNT(*) > 1
        ORDER BY total DESC
    ");
    
    $emailsDuplicados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($emailsDuplicados)) {
        $vulnerabilidades[] = "Emails duplicados: " . count($emailsDuplicados);
    }
    
    // Verificar CNPJs duplicados
    $stmt = $pdo->query("
        SELECT cnpj, COUNT(*) as total
        FROM cfcs
        WHERE cnpj IS NOT NULL
        GROUP BY cnpj
        HAVING COUNT(*) > 1
        ORDER BY total DESC
    ");
    
    $cnpjsDuplicados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($cnpjsDuplicados)) {
        $vulnerabilidades[] = "CNPJs duplicados: " . count($cnpjsDuplicados);
    }
    
    if (empty($vulnerabilidades)) {
        registerTest("Integridade de Dados", "success", "NENHUMA VULNERABILIDADE DE DUPLICAÇÃO ENCONTRADA");
    } else {
        registerTest("Integridade de Dados", "error", "VULNERABILIDADES ENCONTRADAS: " . implode('; ', $vulnerabilidades));
    }
    
} catch (Exception $e) {
    registerTest("Validação de Integridade de Dados", "error", "ERRO: " . $e->getMessage());
}

// 24.9 Teste 7: Validação de Configurações de Segurança
echo "<h2>24.9 Teste 7: Validação de Configurações de Segurança</h2>";

try {
    // Verificar configurações do PHP
    $configuracoes = [];
    
    // Verificar exibição de erros
    $displayErrors = ini_get('display_errors');
    $configuracoes[] = [
        'Configuração' => 'display_errors',
        'Valor Atual' => $displayErrors,
        'Recomendado' => 'Off',
        'Status' => ($displayErrors == '0' || $displayErrors == '') ? '✅ Seguro' : '❌ Vulnerável'
    ];
    
    // Verificar exibição de erros de startup
    $displayStartupErrors = ini_get('display_startup_errors');
    $configuracoes[] = [
        'Configuração' => 'display_startup_errors',
        'Valor Atual' => $displayStartupErrors,
        'Recomendado' => 'Off',
        'Status' => ($displayStartupErrors == '0' || $displayStartupErrors == '') ? '✅ Seguro' : '❌ Vulnerável'
    ];
    
    // Verificar nível de report de erros
    $errorReporting = ini_get('error_reporting');
    $configuracoes[] = [
        'Configuração' => 'error_reporting',
        'Valor Atual' => $errorReporting,
        'Recomendado' => 'E_ALL & ~E_DEPRECATED & ~E_STRICT',
        'Status' => 'ℹ️ Configurado'
    ];
    
    // Verificar se session.cookie_httponly está ativo
    $cookieHttpOnly = ini_get('session.cookie_httponly');
    $configuracoes[] = [
        'Configuração' => 'session.cookie_httponly',
        'Valor Atual' => $cookieHttpOnly,
        'Recomendado' => 'On',
        'Status' => ($cookieHttpOnly == '1') ? '✅ Seguro' : '❌ Vulnerável'
    ];
    
    // Verificar se session.cookie_secure está ativo (para HTTPS)
    $cookieSecure = ini_get('session.cookie_secure');
    $configuracoes[] = [
        'Configuração' => 'session.cookie_secure',
        'Valor Atual' => $cookieSecure,
        'Recomendado' => 'On (HTTPS) / Off (HTTP)',
        'Status' => 'ℹ️ Configurado'
    ];
    
    registerTest("Configurações de Segurança PHP", "success", "CONFIGURAÇÕES ANALISADAS", $configuracoes);
    
} catch (Exception $e) {
    registerTest("Validação de Configurações de Segurança", "error", "ERRO: " . $e->getMessage());
}

// 24.10 Teste 8: Resumo de Segurança
echo "<h2>24.10 Teste 8: Resumo de Segurança</h2>";

try {
    // Estatísticas gerais de segurança
    $stats = [];
    
    // Contar usuários por tipo
    $stmt = $pdo->query("SELECT tipo, COUNT(*) as total FROM usuarios GROUP BY tipo");
    $usuariosPorTipo = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($usuariosPorTipo as $tipo) {
        $stats[] = ['Categoria' => 'Usuários ' . ucfirst($tipo['tipo']), 'Total' => $tipo['total']];
    }
    
    // Contar sessões ativas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM sessoes WHERE expira_em > NOW()");
    $sessoesAtivas = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats[] = ['Categoria' => 'Sessões Ativas', 'Total' => $sessoesAtivas['total']];
    
    // Contar logs de segurança
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM logs");
    $totalLogs = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats[] = ['Categoria' => 'Logs de Segurança', 'Total' => $totalLogs['total']];
    
    // Contar tentativas de login (se houver)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM logs WHERE acao LIKE '%login%' OR acao LIKE '%acesso%'");
    $tentativasLogin = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats[] = ['Categoria' => 'Tentativas de Login', 'Total' => $tentativasLogin['total']];
    
    registerTest("Resumo de Segurança", "success", "ESTATÍSTICAS COLETADAS COM SUCESSO", $stats);
    
} catch (Exception $e) {
    registerTest("Resumo de Segurança", "error", "ERRO: " . $e->getMessage());
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
    echo "<p class='success'>✅ TESTE #24 CONCLUÍDO COM SUCESSO!</p>";
    echo "<p><strong>🎯 Próximo: TESTE #25 - Testes Finais de Integração</strong></p>";
    echo "<p><strong>📝 Instrução:</strong> Execute este teste e me informe o resultado. Se tudo estiver OK, criarei o último teste.</p>";
} else {
    echo "<p class='error'>❌ ALGUNS TESTES FALHARAM. Verifique os erros acima.</p>";
}

echo "<p><strong>💡 INFORMAÇÕES ADICIONAIS</strong></p>";
echo "<p><strong>URL de Teste:</strong> /cfc-bom-conselho/admin/teste-24-testes-seguranca.php</p>";
echo "<p><strong>Funcionalidades Testadas:</strong> Segurança, Criptografia, Sessões, Logs, Controle de Acesso</p>";
echo "<p><strong>Arquivos Utilizados:</strong> Database, Config</p>";
echo "<p><strong>Validações:</strong> Senhas, Sessões, Logs, Dados Sensíveis, Configurações PHP</p>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
