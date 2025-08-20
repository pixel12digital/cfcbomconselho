<?php
/**
 * TESTE #22: Validações de Integridade
 * Data/Hora: 19/08/2025 17:25:19
 * 
 * Este teste verifica a integridade dos dados e relacionamentos:
 * - Chaves estrangeiras válidas
 * - Dados consistentes entre tabelas
 * - Regras de negócio aplicadas
 * - Validações de unicidade
 * - Verificações de integridade referencial
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
echo "<title>TESTE #22: Validações de Integridade</title>";
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
echo ".progress-fill { height: 100%; background: linear-gradient(90deg, #fd7e14, #e83e8c); transition: width 0.3s ease; }";
echo ".integrity-section { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 15px; margin-bottom: 15px; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<div class='header'>";
echo "<h1>🔍 TESTE #22: Validações de Integridade</h1>";
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

// 22.1 Inclusão de Arquivos Necessários
echo "<h2>22.1 Inclusão de Arquivos Necessários</h2>";

try {
    require_once '../includes/database.php';
    require_once '../includes/config.php';
    registerTest("Arquivos necessários", "success", "INCLUÍDOS COM SUCESSO");
} catch (Exception $e) {
    registerTest("Arquivos necessários", "error", "ERRO: " . $e->getMessage());
}

// 22.2 Conexão com Banco de Dados
echo "<h2>22.2 Conexão com Banco de Dados</h2>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    registerTest("Conexão PDO", "success", "ESTABELECIDA COM SUCESSO");
} catch (PDOException $e) {
    registerTest("Conexão PDO", "error", "ERRO: " . $e->getMessage());
    exit;
}

// 22.3 Validação 1: Integridade Referencial - CFCs
echo "<h2>22.3 Validação 1: Integridade Referencial - CFCs</h2>";

try {
    // Verificar se todos os CFCs referenciados existem
    $stmt = $pdo->query("
        SELECT DISTINCT cfc_id, COUNT(*) as total_referencias
        FROM (
            SELECT cfc_id FROM alunos
            UNION ALL
            SELECT cfc_id FROM instrutores
            UNION ALL
            SELECT cfc_id FROM veiculos
            UNION ALL
            SELECT cfc_id FROM aulas
        ) AS todas_referencias
        GROUP BY cfc_id
        ORDER BY cfc_id
    ");
    
    $cfcReferencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($cfcReferencias)) {
        $cfcIds = array_column($cfcReferencias, 'cfc_id');
        $placeholders = str_repeat('?,', count($cfcIds) - 1) . '?';
        
        $stmt = $pdo->prepare("SELECT id, nome FROM cfcs WHERE id IN ($placeholders)");
        $stmt->execute($cfcIds);
        $cfcsExistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $cfcIdsExistentes = array_column($cfcsExistentes, 'id');
        $cfcIdsReferenciados = array_column($cfcReferencias, 'cfc_id');
        
        $cfcsInexistentes = array_diff($cfcIdsReferenciados, $cfcIdsExistentes);
        
        if (empty($cfcsInexistentes)) {
            registerTest("Integridade Referencial CFCs", "success", "TODOS OS CFCs REFERENCIADOS EXISTEM", $cfcReferencias);
        } else {
            registerTest("Integridade Referencial CFCs", "error", "CFCs INEXISTENTES REFERENCIADOS: " . implode(', ', $cfcsInexistentes));
        }
    } else {
        registerTest("Integridade Referencial CFCs", "warning", "NENHUMA REFERÊNCIA A CFCs ENCONTRADA");
    }
    
} catch (Exception $e) {
    registerTest("Integridade Referencial CFCs", "error", "ERRO: " . $e->getMessage());
}

// 22.4 Validação 2: Integridade Referencial - Usuários
echo "<h2>22.4 Validação 2: Integridade Referencial - Usuários</h2>";

try {
    // Verificar se todos os usuários referenciados existem
    $stmt = $pdo->query("
        SELECT DISTINCT usuario_id, COUNT(*) as total_referencias
        FROM (
            SELECT usuario_id FROM instrutores WHERE usuario_id IS NOT NULL
            UNION ALL
            SELECT usuario_id FROM sessoes
            UNION ALL
            SELECT usuario_id FROM logs WHERE usuario_id IS NOT NULL
        ) AS todas_referencias
        GROUP BY usuario_id
        ORDER BY usuario_id
    ");
    
    $usuarioReferencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($usuarioReferencias)) {
        $usuarioIds = array_column($usuarioReferencias, 'usuario_id');
        $placeholders = str_repeat('?,', count($usuarioIds) - 1) . '?';
        
        $stmt = $pdo->prepare("SELECT id, nome, email FROM usuarios WHERE id IN ($placeholders)");
        $stmt->execute($usuarioIds);
        $usuariosExistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $usuarioIdsExistentes = array_column($usuariosExistentes, 'id');
        $usuarioIdsReferenciados = array_column($usuarioReferencias, 'usuario_id');
        
        $usuariosInexistentes = array_diff($usuarioIdsReferenciados, $usuarioIdsExistentes);
        
        if (empty($usuariosInexistentes)) {
            registerTest("Integridade Referencial Usuários", "success", "TODOS OS USUÁRIOS REFERENCIADOS EXISTEM", $usuarioReferencias);
        } else {
            registerTest("Integridade Referencial Usuários", "error", "USUÁRIOS INEXISTENTES REFERENCIADOS: " . implode(', ', $usuariosInexistentes));
        }
    } else {
        registerTest("Integridade Referencial Usuários", "warning", "NENHUMA REFERÊNCIA A USUÁRIOS ENCONTRADA");
    }
    
} catch (Exception $e) {
    registerTest("Integridade Referencial Usuários", "error", "ERRO: " . $e->getMessage());
}

// 22.5 Validação 3: Integridade Referencial - Instrutores
echo "<h2>22.5 Validação 3: Integridade Referencial - Instrutores</h2>";

try {
    // Verificar se todos os instrutores referenciados existem
    $stmt = $pdo->query("
        SELECT DISTINCT instrutor_id, COUNT(*) as total_referencias
        FROM aulas
        WHERE instrutor_id IS NOT NULL
        GROUP BY instrutor_id
        ORDER BY instrutor_id
    ");
    
    $instrutorReferencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($instrutorReferencias)) {
        $instrutorIds = array_column($instrutorReferencias, 'instrutor_id');
        $placeholders = str_repeat('?,', count($instrutorIds) - 1) . '?';
        
        $stmt = $pdo->prepare("SELECT id, nome, cpf FROM instrutores WHERE id IN ($placeholders)");
        $stmt->execute($instrutorIds);
        $instrutoresExistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $instrutorIdsExistentes = array_column($instrutoresExistentes, 'id');
        $instrutorIdsReferenciados = array_column($instrutorReferencias, 'instrutor_id');
        
        $instrutoresInexistentes = array_diff($instrutorIdsReferenciados, $instrutorIdsExistentes);
        
        if (empty($instrutoresInexistentes)) {
            registerTest("Integridade Referencial Instrutores", "success", "TODOS OS INSTRUTORES REFERENCIADOS EXISTEM", $instrutorReferencias);
        } else {
            registerTest("Integridade Referencial Instrutores", "error", "INSTRUTORES INEXISTENTES REFERENCIADOS: " . implode(', ', $instrutoresInexistentes));
        }
    } else {
        registerTest("Integridade Referencial Instrutores", "warning", "NENHUMA REFERÊNCIA A INSTRUTORES ENCONTRADA");
    }
    
} catch (Exception $e) {
    registerTest("Integridade Referencial Instrutores", "error", "ERRO: " . $e->getMessage());
}

// 22.6 Validação 4: Integridade Referencial - Alunos
echo "<h2>22.6 Validação 4: Integridade Referencial - Alunos</h2>";

try {
    // Verificar se todos os alunos referenciados existem
    $stmt = $pdo->query("
        SELECT DISTINCT aluno_id, COUNT(*) as total_referencias
        FROM aulas
        WHERE aluno_id IS NOT NULL
        GROUP BY aluno_id
        ORDER BY aluno_id
    ");
    
    $alunoReferencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($alunoReferencias)) {
        $alunoIds = array_column($alunoReferencias, 'aluno_id');
        $placeholders = str_repeat('?,', count($alunoIds) - 1) . '?';
        
        $stmt = $pdo->prepare("SELECT id, nome, cpf FROM alunos WHERE id IN ($placeholders)");
        $stmt->execute($alunoIds);
        $alunosExistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $alunoIdsExistentes = array_column($alunosExistentes, 'id');
        $alunoIdsReferenciados = array_column($alunoReferencias, 'aluno_id');
        
        $alunosInexistentes = array_diff($alunoIdsReferenciados, $alunoIdsExistentes);
        
        if (empty($alunosInexistentes)) {
            registerTest("Integridade Referencial Alunos", "success", "TODOS OS ALUNOS REFERENCIADOS EXISTEM", $alunoReferencias);
        } else {
            registerTest("Integridade Referencial Alunos", "error", "ALUNOS INEXISTENTES REFERENCIADOS: " . implode(', ', $alunosInexistentes));
        }
    } else {
        registerTest("Integridade Referencial Alunos", "warning", "NENHUMA REFERÊNCIA A ALUNOS ENCONTRADA");
    }
    
} catch (Exception $e) {
    registerTest("Integridade Referencial Alunos", "error", "ERRO: " . $e->getMessage());
}

// 22.7 Validação 5: Integridade Referencial - Veículos
echo "<h2>22.7 Validação 5: Integridade Referencial - Veículos</h2>";

try {
    // Verificar se todos os veículos referenciados existem
    $stmt = $pdo->query("
        SELECT DISTINCT veiculo_id, COUNT(*) as total_referencias
        FROM aulas
        WHERE veiculo_id IS NOT NULL
        GROUP BY veiculo_id
        ORDER BY veiculo_id
    ");
    
    $veiculoReferencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($veiculoReferencias)) {
        $veiculoIds = array_column($veiculoReferencias, 'veiculo_id');
        $placeholders = str_repeat('?,', count($veiculoIds) - 1) . '?';
        
        $stmt = $pdo->prepare("SELECT id, placa, modelo FROM veiculos WHERE id IN ($placeholders)");
        $stmt->execute($veiculoIds);
        $veiculosExistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $veiculoIdsExistentes = array_column($veiculosExistentes, 'id');
        $veiculoIdsReferenciados = array_column($veiculoReferencias, 'veiculo_id');
        
        $veiculosInexistentes = array_diff($veiculoIdsReferenciados, $veiculoIdsExistentes);
        
        if (empty($veiculosInexistentes)) {
            registerTest("Integridade Referencial Veículos", "success", "TODOS OS VEÍCULOS REFERENCIADOS EXISTEM", $veiculoReferencias);
        } else {
            registerTest("Integridade Referencial Veículos", "error", "VEÍCULOS INEXISTENTES REFERENCIADOS: " . implode(', ', $veiculosInexistentes));
        }
    } else {
        registerTest("Integridade Referencial Veículos", "warning", "NENHUMA REFERÊNCIA A VEÍCULOS ENCONTRADA");
    }
    
} catch (Exception $e) {
    registerTest("Integridade Referencial Veículos", "error", "ERRO: " . $e->getMessage());
}

// 22.8 Validação 6: Unicidade de CPFs
echo "<h2>22.8 Validação 6: Unicidade de CPFs</h2>";

try {
    // Verificar CPFs duplicados em alunos
    $stmt = $pdo->query("
        SELECT cpf, COUNT(*) as total
        FROM alunos
        WHERE cpf IS NOT NULL
        GROUP BY cpf
        HAVING COUNT(*) > 1
        ORDER BY total DESC
    ");
    
    $cpfsDuplicadosAlunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Verificar CPFs duplicados em instrutores
    $stmt = $pdo->query("
        SELECT cpf, COUNT(*) as total
        FROM instrutores
        WHERE cpf IS NOT NULL
        GROUP BY cpf
        HAVING COUNT(*) > 1
        ORDER BY total DESC
    ");
    
    $cpfsDuplicadosInstrutores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Verificar CPFs duplicados em usuários
    $stmt = $pdo->query("
        SELECT cpf, COUNT(*) as total
        FROM usuarios
        WHERE cpf IS NOT NULL
        GROUP BY cpf
        HAVING COUNT(*) > 1
        ORDER BY total DESC
    ");
    
    $cpfsDuplicadosUsuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalDuplicados = count($cpfsDuplicadosAlunos) + count($cpfsDuplicadosInstrutores) + count($cpfsDuplicadosUsuarios);
    
    if ($totalDuplicados == 0) {
        registerTest("Unicidade de CPFs", "success", "TODOS OS CPFs SÃO ÚNICOS");
    } else {
        $mensagem = "CPFs DUPLICADOS ENCONTRADOS: ";
        if (!empty($cpfsDuplicadosAlunos)) $mensagem .= "Alunos(" . count($cpfsDuplicadosAlunos) . ") ";
        if (!empty($cpfsDuplicadosInstrutores)) $mensagem .= "Instrutores(" . count($cpfsDuplicadosInstrutores) . ") ";
        if (!empty($cpfsDuplicadosUsuarios)) $mensagem .= "Usuários(" . count($cpfsDuplicadosUsuarios) . ")";
        
        registerTest("Unicidade de CPFs", "error", $mensagem);
        
        if (!empty($cpfsDuplicadosAlunos)) {
            registerTest("CPFs Duplicados - Alunos", "info", "Detalhes dos CPFs duplicados", $cpfsDuplicadosAlunos);
        }
        if (!empty($cpfsDuplicadosInstrutores)) {
            registerTest("CPFs Duplicados - Instrutores", "info", "Detalhes dos CPFs duplicados", $cpfsDuplicadosInstrutores);
        }
        if (!empty($cpfsDuplicadosUsuarios)) {
            registerTest("CPFs Duplicados - Usuários", "info", "Detalhes dos CPFs duplicados", $cpfsDuplicadosUsuarios);
        }
    }
    
} catch (Exception $e) {
    registerTest("Unicidade de CPFs", "error", "ERRO: " . $e->getMessage());
}

// 22.9 Validação 7: Unicidade de Emails
echo "<h2>22.9 Validação 7: Unicidade de Emails</h2>";

try {
    // Verificar emails duplicados em usuários
    $stmt = $pdo->query("
        SELECT email, COUNT(*) as total
        FROM usuarios
        WHERE email IS NOT NULL
        GROUP BY email
        HAVING COUNT(*) > 1
        ORDER BY total DESC
    ");
    
    $emailsDuplicadosUsuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Verificar emails duplicados em instrutores
    $stmt = $pdo->query("
        SELECT email, COUNT(*) as total
        FROM instrutores
        WHERE email IS NOT NULL
        GROUP BY email
        HAVING COUNT(*) > 1
        ORDER BY total DESC
    ");
    
    $emailsDuplicadosInstrutores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Verificar emails duplicados em alunos
    $stmt = $pdo->query("
        SELECT email, COUNT(*) as total
        FROM alunos
        WHERE email IS NOT NULL
        GROUP BY email
        HAVING COUNT(*) > 1
        ORDER BY total DESC
    ");
    
    $emailsDuplicadosAlunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalDuplicados = count($emailsDuplicadosUsuarios) + count($emailsDuplicadosInstrutores) + count($emailsDuplicadosAlunos);
    
    if ($totalDuplicados == 0) {
        registerTest("Unicidade de Emails", "success", "TODOS OS EMAILS SÃO ÚNICOS");
    } else {
        $mensagem = "EMAILS DUPLICADOS ENCONTRADOS: ";
        if (!empty($emailsDuplicadosUsuarios)) $mensagem .= "Usuários(" . count($emailsDuplicadosUsuarios) . ") ";
        if (!empty($emailsDuplicadosInstrutores)) $mensagem .= "Instrutores(" . count($emailsDuplicadosInstrutores) . ") ";
        if (!empty($emailsDuplicadosAlunos)) $mensagem .= "Alunos(" . count($emailsDuplicadosAlunos) . ")";
        
        registerTest("Unicidade de Emails", "error", $mensagem);
    }
    
} catch (Exception $e) {
    registerTest("Unicidade de Emails", "error", "ERRO: " . $e->getMessage());
}

// 22.10 Validação 8: Unicidade de CNPJs e Placas
echo "<h2>22.10 Validação 8: Unicidade de CNPJs e Placas</h2>";

try {
    // Verificar CNPJs duplicados em CFCs
    $stmt = $pdo->query("
        SELECT cnpj, COUNT(*) as total
        FROM cfcs
        WHERE cnpj IS NOT NULL
        GROUP BY cnpj
        HAVING COUNT(*) > 1
        ORDER BY total DESC
    ");
    
    $cnpjsDuplicados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Verificar placas duplicadas em veículos
    $stmt = $pdo->query("
        SELECT placa, COUNT(*) as total
        FROM veiculos
        WHERE placa IS NOT NULL
        GROUP BY placa
        HAVING COUNT(*) > 1
        ORDER BY total DESC
    ");
    
    $placasDuplicadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalDuplicados = count($cnpjsDuplicados) + count($placasDuplicadas);
    
    if ($totalDuplicados == 0) {
        registerTest("Unicidade de CNPJs e Placas", "success", "TODOS OS CNPJs E PLACAS SÃO ÚNICOS");
    } else {
        $mensagem = "DUPLICADOS ENCONTRADOS: ";
        if (!empty($cnpjsDuplicados)) $mensagem .= "CNPJs(" . count($cnpjsDuplicados) . ") ";
        if (!empty($placasDuplicadas)) $mensagem .= "Placas(" . count($placasDuplicadas) . ")";
        
        registerTest("Unicidade de CNPJs e Placas", "error", $mensagem);
    }
    
} catch (Exception $e) {
    registerTest("Unicidade de CNPJs e Placas", "error", "ERRO: " . $e->getMessage());
}

// 22.11 Validação 9: Consistência de Status
echo "<h2>22.11 Validação 9: Consistência de Status</h2>";

try {
    // Verificar se há registros com status inválidos
    $statusInvalidos = [];
    
    // Verificar status de alunos
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as total
        FROM alunos
        WHERE status NOT IN ('ativo', 'inativo', 'concluido')
        GROUP BY status
    ");
    
    $statusInvalidosAlunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($statusInvalidosAlunos)) {
        $statusInvalidos[] = "Alunos: " . implode(', ', array_column($statusInvalidosAlunos, 'status'));
    }
    
    // Verificar status de aulas
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as total
        FROM aulas
        WHERE status NOT IN ('agendada', 'em_andamento', 'concluida', 'cancelada')
        GROUP BY status
    ");
    
    $statusInvalidosAulas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($statusInvalidosAulas)) {
        $statusInvalidos[] = "Aulas: " . implode(', ', array_column($statusInvalidosAulas, 'status'));
    }
    
    // Verificar status de usuários
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as total
        FROM usuarios
        WHERE status NOT IN ('ativo', 'inativo')
        GROUP BY status
    ");
    
    $statusInvalidosUsuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($statusInvalidosUsuarios)) {
        $statusInvalidos[] = "Usuários: " . implode(', ', array_column($statusInvalidosUsuarios, 'status'));
    }
    
    if (empty($statusInvalidos)) {
        registerTest("Consistência de Status", "success", "TODOS OS STATUS SÃO VÁLIDOS");
    } else {
        registerTest("Consistência de Status", "error", "STATUS INVÁLIDOS ENCONTRADOS: " . implode('; ', $statusInvalidos));
    }
    
} catch (Exception $e) {
    registerTest("Consistência de Status", "error", "ERRO: " . $e->getMessage());
}

// 22.12 Validação 10: Resumo da Integridade
echo "<h2>22.12 Validação 10: Resumo da Integridade</h2>";

try {
    // Contar total de registros em cada tabela
    $tables = ['cfcs', 'usuarios', 'instrutores', 'alunos', 'veiculos', 'aulas', 'sessoes', 'logs'];
    $tableCounts = [];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM " . $table);
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        $tableCounts[$table] = $count['total'];
    }
    
    // Verificar se há registros órfãos
    $registrosOrfaos = [];
    
    // Verificar alunos sem CFC
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM alunos WHERE cfc_id NOT IN (SELECT id FROM cfcs)");
    $alunosOrfaos = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($alunosOrfaos['total'] > 0) {
        $registrosOrfaos[] = "Alunos sem CFC: " . $alunosOrfaos['total'];
    }
    
    // Verificar instrutores sem CFC
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM instrutores WHERE cfc_id NOT IN (SELECT id FROM cfcs)");
    $instrutoresOrfaos = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($instrutoresOrfaos['total'] > 0) {
        $registrosOrfaos[] = "Instrutores sem CFC: " . $instrutoresOrfaos['total'];
    }
    
    // Verificar veículos sem CFC
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM veiculos WHERE cfc_id NOT IN (SELECT id FROM cfcs)");
    $veiculosOrfaos = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($veiculosOrfaos['total'] > 0) {
        $registrosOrfaos[] = "Veículos sem CFC: " . $veiculosOrfaos['total'];
    }
    
    if (empty($registrosOrfaos)) {
        registerTest("Resumo da Integridade", "success", "NENHUM REGISTRO ÓRFÃO ENCONTRADO", [
            ['Tabela' => 'CFCs', 'Total' => $tableCounts['cfcs']],
            ['Tabela' => 'Usuários', 'Total' => $tableCounts['usuarios']],
            ['Tabela' => 'Instrutores', 'Total' => $tableCounts['instrutores']],
            ['Tabela' => 'Alunos', 'Total' => $tableCounts['alunos']],
            ['Tabela' => 'Veículos', 'Total' => $tableCounts['veiculos']],
            ['Tabela' => 'Aulas', 'Total' => $tableCounts['aulas']],
            ['Tabela' => 'Sessões', 'Total' => $tableCounts['sessoes']],
            ['Tabela' => 'Logs', 'Total' => $tableCounts['logs']]
        ]);
    } else {
        registerTest("Resumo da Integridade", "warning", "REGISTROS ÓRFÃOS ENCONTRADOS: " . implode('; ', $registrosOrfaos));
    }
    
} catch (Exception $e) {
    registerTest("Resumo da Integridade", "error", "ERRO: " . $e->getMessage());
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
    echo "<p class='success'>✅ TESTE #22 CONCLUÍDO COM SUCESSO!</p>";
    echo "<p><strong>🎯 Próximo: TESTE #23 - Testes de Performance</strong></p>";
    echo "<p><strong>📝 Instrução:</strong> Execute este teste e me informe o resultado. Se tudo estiver OK, criarei o próximo teste.</p>";
} else {
    echo "<p class='error'>❌ ALGUNS TESTES FALHARAM. Verifique os erros acima.</p>";
}

echo "<p><strong>💡 INFORMAÇÕES ADICIONAIS</strong></p>";
echo "<p><strong>URL de Teste:</strong> /cfc-bom-conselho/admin/teste-22-validacoes-integridade.php</p>";
echo "<p><strong>Funcionalidades Testadas:</strong> Integridade Referencial, Unicidade, Consistência de Dados</p>";
echo "<p><strong>Arquivos Utilizados:</strong> Database, Config</p>";
echo "<p><strong>Validações:</strong> Chaves Estrangeiras, CPFs, Emails, CNPJs, Placas, Status, Registros Órfãos</p>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
