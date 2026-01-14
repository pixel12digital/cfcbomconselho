<?php
/**
 * Script para executar seed completo de cidades do IBGE
 * 
 * Execute via linha de comando: php tools/run_seed_cities_full.php
 * 
 * Este script executa o seed completo com todas as cidades do IBGE (~5570)
 * É idempotente: pode ser executado múltiplas vezes sem duplicar registros
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

echo "=== EXECUTANDO SEED COMPLETO - CIDADES IBGE ===\n\n";

// Verificar conexão com banco de dados
try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar banco
    $stmt = $db->query("SELECT DATABASE() as current_db");
    $currentDb = $stmt->fetch();
    $runtimeDb = $currentDb['current_db'] ?? 'N/A';
    
    echo "📍 Banco em uso: '{$runtimeDb}'\n";
    
    if ($runtimeDb !== 'cfc_db') {
        echo "⚠️  AVISO: Banco diferente de 'cfc_db'. Continuando mesmo assim...\n";
    }
    echo "\n";
    
    // Verificar se estados existem
    $stmt = $db->query("SELECT COUNT(*) as total FROM states");
    $statesCount = $stmt->fetch()['total'];
    
    if ($statesCount < 27) {
        echo "❌ ERRO: Estados não encontrados ou incompletos ({$statesCount}/27)\n";
        echo "   Execute primeiro: php tools/run_phase1_2_migrations.php\n";
        exit(1);
    }
    
    echo "✓ {$statesCount} estados encontrados no banco\n\n";
    
    // Verificar se arquivo existe
    $seedFile = ROOT_PATH . '/database/seeds/004_seed_cities_ibge_full.sql';
    
    if (!file_exists($seedFile)) {
        echo "❌ ERRO: Arquivo de seed não encontrado: {$seedFile}\n";
        echo "   Execute primeiro: php tools/generate_cities_seed.php\n";
        exit(1);
    }
    
    $fileSize = filesize($seedFile);
    echo "✓ Arquivo encontrado: " . number_format($fileSize / 1024, 2) . " KB\n\n";
    
    // Contar cidades atuais
    $stmt = $db->query("SELECT COUNT(*) as total FROM cities");
    $citiesBefore = $stmt->fetch()['total'];
    echo "📍 Cidades antes: {$citiesBefore}\n\n";
    
    // Executar seed
    echo "Executando seed completo...\n";
    echo "⏳ Isso pode levar alguns minutos devido ao volume de dados...\n\n";
    
    $startTime = microtime(true);
    
    // Ler e executar SQL
    $sql = file_get_contents($seedFile);
    
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
    $lastProgress = 0;
    
    foreach ($statements as $index => $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        try {
            $db->exec($statement);
            $executed++;
            
            // Mostrar progresso a cada 100 comandos
            if ($executed % 100 === 0) {
                $progress = round(($executed / count($statements)) * 100);
                echo "   Progresso: {$progress}% ({$executed}/" . count($statements) . " comandos)\n";
            }
        } catch (\PDOException $e) {
            $errorMsg = $e->getMessage();
            // Ignorar erros de duplicação (INSERT IGNORE)
            if (strpos($errorMsg, 'Duplicate') !== false || 
                strpos($errorMsg, 'already exists') !== false ||
                strpos($errorMsg, '1062') !== false) {
                // Silenciar - é esperado com INSERT IGNORE
                $executed++;
            } else {
                $errors[] = "Comando #{$index}: " . substr($statement, 0, 100) . "...\n      Erro: " . $errorMsg;
            }
        } catch (\Exception $e) {
            $errors[] = "Comando #{$index}: " . substr($statement, 0, 100) . "...\n      Erro: " . $e->getMessage();
        }
    }
    
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    echo "\n✓ Seed executado ({$executed} comando(s) processado(s) em {$duration}s)\n\n";
    
    if (count($errors) > 0) {
        echo "⚠️  Total de erros: " . count($errors) . "\n";
        foreach (array_slice($errors, 0, 10) as $error) {
            echo "   " . $error . "\n";
        }
        if (count($errors) > 10) {
            echo "   ... e mais " . (count($errors) - 10) . " erro(s)\n";
        }
        echo "\n";
    }
    
    // Contar cidades após
    $stmt = $db->query("SELECT COUNT(*) as total FROM cities");
    $citiesAfter = $stmt->fetch()['total'];
    $citiesAdded = $citiesAfter - $citiesBefore;
    
    echo "📍 Cidades após: {$citiesAfter}\n";
    echo "📍 Cidades adicionadas: {$citiesAdded}\n\n";
    
    // Validação
    echo "Verificando validação...\n";
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM states");
    $statesCount = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM cities");
    $citiesCount = $stmt->fetch()['total'];
    
    echo "   ✓ Estados: {$statesCount} (esperado: 27)\n";
    echo "   ✓ Cidades: {$citiesCount} (esperado: ~5570)\n\n";
    
    if ($statesCount == 27 && $citiesCount >= 5500) {
        echo "✅ SEED COMPLETO EXECUTADO COM SUCESSO!\n\n";
        echo "Próximos passos:\n";
        echo "1. Acesse o sistema e faça login\n";
        echo "2. Teste criar/editar um aluno em /alunos\n";
        echo "3. Selecione uma UF e verifique se todas as cidades aparecem\n";
    } else {
        echo "⚠️  Validação: Alguns valores não estão no esperado\n";
        echo "   Verifique os erros acima se houver\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    if ($e->getCode() > 0) {
        echo "Código: " . $e->getCode() . "\n";
    }
    exit(1);
}
