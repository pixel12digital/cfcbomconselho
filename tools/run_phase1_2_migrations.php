<?php
/**
 * Script para executar migrations e seeds da Fase 1.2 (Padronização UF/Cidades)
 * Execute via linha de comando: php tools/run_phase1_2_migrations.php
 * Ou acesse via navegador: http://localhost/cfc-v.1/public_html/tools/run_phase1_2_migrations.php
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

// Autoload
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
} else {
    require_once APP_PATH . '/autoload.php';
}

// Carregar variáveis de ambiente
use App\Config\Env;
Env::load();

use App\Config\Database;

echo "=== FASE 1.2 - Executando Migrations e Seeds (UF/Cidades) ===\n\n";

// Verificar conexão com banco de dados
try {
    $db = Database::getInstance()->getConnection();
    
    // CONFIRMAÇÃO EXPLÍCITA DO BANCO DE DADOS
    echo "\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "  CONFIRMAÇÃO DO BANCO DE DADOS ANTES DA EXECUÇÃO\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    // 1. Banco configurado (vindo do .env ou config)
    $dbNameFromEnv = $_ENV['DB_NAME'] ?? null;
    $dbNameFromConfig = 'cfc_db'; // fallback padrão da classe Database
    $configuredDb = $dbNameFromEnv ?? $dbNameFromConfig;
    
    echo "📍 BANCO CONFIGURADO (vindo do .env ou config):\n";
    echo "   └─ Valor: '{$configuredDb}'\n";
    if ($dbNameFromEnv) {
        echo "   └─ Origem: Variável de ambiente DB_NAME\n";
    } else {
        echo "   └─ Origem: Valor padrão da classe Database (fallback)\n";
    }
    echo "\n";
    
    // 2. Banco em uso no runtime (SELECT DATABASE())
    $stmt = $db->query("SELECT DATABASE() as current_db");
    $currentDb = $stmt->fetch();
    $runtimeDb = $currentDb['current_db'] ?? 'N/A';
    
    echo "🔍 BANCO EM USO NO RUNTIME (SELECT DATABASE()):\n";
    echo "   └─ Valor: '{$runtimeDb}'\n";
    echo "\n";
    
    // 3. Verificação de conformidade
    $expectedDb = 'cfc_db';
    echo "✅ VERIFICAÇÃO DE CONFORMIDADE:\n";
    echo "   └─ Banco esperado: '{$expectedDb}'\n";
    echo "   └─ Banco configurado: '{$configuredDb}' " . ($configuredDb === $expectedDb ? '✅' : '❌') . "\n";
    echo "   └─ Banco em uso: '{$runtimeDb}' " . ($runtimeDb === $expectedDb ? '✅' : '❌') . "\n";
    echo "\n";
    
    // 4. Validação final
    $isConfiguredCorrect = ($configuredDb === $expectedDb);
    $isRuntimeCorrect = ($runtimeDb === $expectedDb);
    
    if (!$isConfiguredCorrect || !$isRuntimeCorrect) {
        echo "❌ ERRO: Banco de dados não está configurado corretamente!\n\n";
        if (!$isConfiguredCorrect) {
            echo "   • O banco configurado ('{$configuredDb}') não é '{$expectedDb}'\n";
            echo "   • Verifique a variável DB_NAME no arquivo .env\n";
        }
        if (!$isRuntimeCorrect) {
            echo "   • O banco em uso ('{$runtimeDb}') não é '{$expectedDb}'\n";
            echo "   • A conexão pode estar usando um banco diferente do configurado\n";
        }
        echo "\n⚠️  EXECUÇÃO ABORTADA por segurança.\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";
        exit(1);
    }
    
    echo "✅ CONFIRMAÇÃO: Ambos os bancos estão corretos!\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    
    // Verificar se é PDO
    if ($db instanceof \PDO) {
        echo "   ✅ Conexão PDO estabelecida com sucesso\n";
        $dsn = $db->getAttribute(\PDO::ATTR_CONNECTION_STATUS);
        echo "   Status: " . ($dsn ?: 'Conectado') . "\n";
    } else {
        echo "   ⚠️  AVISO: Conexão não é PDO\n";
    }
    echo "\n";
    
} catch (\Exception $e) {
    echo "❌ ERRO ao conectar ao banco de dados: " . $e->getMessage() . "\n";
    exit(1);
}

function executeSQLFile($db, $filePath, $description) {
    if (!file_exists($filePath)) {
        throw new Exception("Arquivo não encontrado: {$filePath}");
    }
    
    echo "Executando: {$description}...\n";
    $sql = file_get_contents($filePath);
    
    // Remover comentários de linha (-- comentário)
    $sql = preg_replace('/--.*$/m', '', $sql);
    
    // Dividir em comandos individuais
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && strlen(trim($stmt)) > 0;
        }
    );
    
    $executed = 0;
    $errors = [];
    
    foreach ($statements as $index => $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        try {
            $result = $db->exec($statement);
            $executed++;
            
            // Detectar tipo de comando e mostrar feedback
            if (preg_match('/CREATE\s+TABLE\s+(IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?/i', $statement, $matches)) {
                $tableName = $matches[2] ?? 'tabela';
                echo "   ✓ Tabela '{$tableName}' criada/verificada\n";
            } elseif (preg_match('/ALTER\s+TABLE\s+`?(\w+)`?/i', $statement, $matches)) {
                $tableName = $matches[1] ?? 'tabela';
                echo "   ✓ Tabela '{$tableName}' alterada\n";
            } elseif (preg_match('/INSERT\s+(IGNORE\s+)?INTO\s+`?(\w+)`?/i', $statement, $matches)) {
                $tableName = $matches[2] ?? 'tabela';
                // Contar quantos registros foram inseridos (aproximado)
                if (preg_match_all('/\([^)]+\)/', $statement, $valueMatches)) {
                    $count = count($valueMatches[0]);
                    echo "   ✓ {$count} registro(s) inserido(s) em '{$tableName}'\n";
                } else {
                    echo "   ✓ Registro(s) inserido(s) em '{$tableName}'\n";
                }
            } elseif (preg_match('/^SET\s+/i', $statement)) {
                // Comandos SET são silenciosos, mas executados
            }
        } catch (\PDOException $e) {
            $errorMsg = $e->getMessage();
            // Ignorar erros de duplicação (INSERT IGNORE)
            if (strpos($errorMsg, 'Duplicate') !== false || 
                strpos($errorMsg, 'already exists') !== false ||
                strpos($errorMsg, '1062') !== false) {
                // Silenciar - é esperado com INSERT IGNORE
            } else {
                $errors[] = "Comando #{$index}: " . substr($statement, 0, 100) . "...\n      Erro: " . $errorMsg;
                echo "   ⚠ Erro: " . $errorMsg . "\n";
            }
        } catch (\Exception $e) {
            $errors[] = "Comando #{$index}: " . substr($statement, 0, 100) . "...\n      Erro: " . $e->getMessage();
            echo "   ⚠ Erro: " . $e->getMessage() . "\n";
        }
    }
    
    if (count($errors) > 0) {
        echo "\n   ⚠ Total de erros: " . count($errors) . "\n";
        foreach ($errors as $error) {
            echo "   " . $error . "\n";
        }
    }
    
    echo "   ✓ {$description} executado ({$executed} comando(s) processado(s))\n\n";
    return $executed;
}

try {
    // 1. Migration 004: Criar tabelas states e cities
    executeSQLFile(
        $db, 
        ROOT_PATH . '/database/migrations/004_create_states_cities_tables.sql',
        'Migration 004 - Tabelas states e cities'
    );
    
    // 2. Migration 005: Adicionar city_id em students
    executeSQLFile(
        $db, 
        ROOT_PATH . '/database/migrations/005_add_city_id_to_students.sql',
        'Migration 005 - Adicionar city_id em students'
    );
    
    // 3. Seed 003: Popular estados
    executeSQLFile(
        $db, 
        ROOT_PATH . '/database/seeds/003_seed_states.sql',
        'Seed 003 - Estados brasileiros'
    );
    
    // 4. Seed 004: Popular cidades (completo ou amostra)
    $seedFullPath = ROOT_PATH . '/database/seeds/004_seed_cities_ibge_full.sql';
    $seedSamplePath = ROOT_PATH . '/database/seeds/004_seed_cities_sample.sql';
    
    if (file_exists($seedFullPath)) {
        echo "ℹ️  Seed completo encontrado. Para executar, use: php tools/run_seed_cities_full.php\n";
        echo "   (Isso pode levar alguns minutos devido ao volume de dados)\n\n";
        echo "   Executando seed de amostra por padrão...\n";
        executeSQLFile(
            $db, 
            $seedSamplePath,
            'Seed 004 - Cidades (amostra)'
        );
    } else {
        executeSQLFile(
            $db, 
            $seedSamplePath,
            'Seed 004 - Cidades (amostra)'
        );
    }
    
    // Verificar tabelas criadas
    echo "Verificando tabelas criadas...\n";
    $tables = ['states', 'cities'];
    $allOk = true;
    
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SHOW TABLES LIKE '{$table}'");
            if ($stmt->rowCount() > 0) {
                // Contar registros
                $countStmt = $db->query("SELECT COUNT(*) as total FROM {$table}");
                $count = $countStmt->fetch()['total'];
                echo "   ✓ Tabela '{$table}' existe ({$count} registros)\n";
            } else {
                echo "   ✗ Tabela '{$table}' NÃO existe\n";
                $allOk = false;
            }
        } catch (PDOException $e) {
            echo "   ✗ Erro ao verificar '{$table}': " . $e->getMessage() . "\n";
            $allOk = false;
        }
    }
    
    // Verificar coluna city_id em students
    echo "\nVerificando alterações em students...\n";
    try {
        $stmt = $db->query("SHOW COLUMNS FROM students LIKE 'city_id'");
        if ($stmt->rowCount() > 0) {
            echo "   ✓ Coluna 'city_id' existe em students\n";
        } else {
            echo "   ✗ Coluna 'city_id' NÃO existe em students\n";
            $allOk = false;
        }
    } catch (PDOException $e) {
        echo "   ✗ Erro ao verificar coluna: " . $e->getMessage() . "\n";
        $allOk = false;
    }
    
    if ($allOk) {
        echo "\n✅ FASE 1.2 CONFIGURADA COM SUCESSO!\n";
        echo "\nPróximos passos:\n";
        echo "1. Acesse o sistema e faça login\n";
        echo "2. Teste criar/editar um aluno em /alunos\n";
        echo "3. Verifique se o select de UF e Cidade funciona corretamente\n";
        echo "4. (Opcional) Importe todas as cidades do IBGE expandindo o seed 004\n";
    } else {
        echo "\n⚠️ Algumas verificações falharam. Verifique os erros acima.\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    if ($e->getCode() > 0) {
        echo "Código: " . $e->getCode() . "\n";
    }
    exit(1);
}
