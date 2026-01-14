<?php
/**
 * Script de Migração: Cidade Texto -> FK (city_id / birth_city_id)
 * 
 * Migra dados antigos de city/birth_city (varchar) para city_id/birth_city_id (FK)
 * Apenas quando houver match exato: UF + nome da cidade
 * 
 * Execute: php tools/migrate_city_text_to_fk.php
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

echo "=== MIGRAÇÃO: Cidade Texto -> FK ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar banco
    $stmt = $db->query("SELECT DATABASE() as current_db");
    $currentDb = $stmt->fetch();
    $runtimeDb = $currentDb['current_db'] ?? 'N/A';
    
    echo "📍 Banco em uso: '{$runtimeDb}'\n\n";
    
    // Contar registros que precisam migração
    $stmt = $db->query("
        SELECT COUNT(*) as total 
        FROM students 
        WHERE (city IS NOT NULL AND city != '' AND city_id IS NULL AND state_uf IS NOT NULL)
           OR (birth_city IS NOT NULL AND birth_city != '' AND birth_city_id IS NULL AND birth_state_uf IS NOT NULL)
    ");
    $totalToMigrate = $stmt->fetch()['total'];
    
    echo "📊 Registros que precisam migração: {$totalToMigrate}\n\n";
    
    if ($totalToMigrate == 0) {
        echo "✅ Nenhum registro precisa de migração!\n";
        exit(0);
    }
    
    // Buscar alunos que precisam migração de city (endereço)
    $stmt = $db->query("
        SELECT id, state_uf, city, city_id
        FROM students
        WHERE city IS NOT NULL 
          AND city != '' 
          AND city_id IS NULL 
          AND state_uf IS NOT NULL
          AND state_uf != ''
    ");
    $studentsAddress = $stmt->fetchAll();
    
    // Buscar alunos que precisam migração de birth_city
    $stmt = $db->query("
        SELECT id, birth_state_uf, birth_city, birth_city_id
        FROM students
        WHERE birth_city IS NOT NULL 
          AND birth_city != '' 
          AND birth_city_id IS NULL 
          AND birth_state_uf IS NOT NULL
          AND birth_state_uf != ''
    ");
    $studentsBirth = $stmt->fetchAll();
    
    $migratedAddress = 0;
    $migratedBirth = 0;
    $pendingAddress = [];
    $pendingBirth = [];
    
    echo "🔄 Migrando cidade de endereço...\n";
    
    // Migrar city (endereço)
    foreach ($studentsAddress as $student) {
        $uf = strtoupper(trim($student['state_uf']));
        $cityText = trim($student['city']);
        
        // Buscar cidade por UF + nome (case-insensitive)
        $stmt = $db->prepare("
            SELECT c.id 
            FROM cities c
            INNER JOIN states s ON c.state_id = s.id
            WHERE s.uf = ? 
              AND LOWER(TRIM(c.name)) = LOWER(?)
            LIMIT 1
        ");
        $stmt->execute([$uf, $cityText]);
        $city = $stmt->fetch();
        
        if ($city) {
            // Match encontrado - atualizar
            $updateStmt = $db->prepare("UPDATE students SET city_id = ? WHERE id = ?");
            $updateStmt->execute([$city['id'], $student['id']]);
            $migratedAddress++;
            echo "   ✓ Aluno #{$student['id']}: '{$cityText}' ({$uf}) -> city_id {$city['id']}\n";
        } else {
            // Sem match - adicionar à lista de pendências
            $pendingAddress[] = [
                'student_id' => $student['id'],
                'uf' => $uf,
                'city_text' => $cityText
            ];
            echo "   ⚠ Aluno #{$student['id']}: '{$cityText}' ({$uf}) - SEM MATCH\n";
        }
    }
    
    echo "\n🔄 Migrando cidade de nascimento...\n";
    
    // Migrar birth_city
    foreach ($studentsBirth as $student) {
        $uf = strtoupper(trim($student['birth_state_uf']));
        $cityText = trim($student['birth_city']);
        
        // Buscar cidade por UF + nome (case-insensitive)
        $stmt = $db->prepare("
            SELECT c.id 
            FROM cities c
            INNER JOIN states s ON c.state_id = s.id
            WHERE s.uf = ? 
              AND LOWER(TRIM(c.name)) = LOWER(?)
            LIMIT 1
        ");
        $stmt->execute([$uf, $cityText]);
        $city = $stmt->fetch();
        
        if ($city) {
            // Match encontrado - atualizar
            $updateStmt = $db->prepare("UPDATE students SET birth_city_id = ? WHERE id = ?");
            $updateStmt->execute([$city['id'], $student['id']]);
            $migratedBirth++;
            echo "   ✓ Aluno #{$student['id']}: '{$cityText}' ({$uf}) -> birth_city_id {$city['id']}\n";
        } else {
            // Sem match - adicionar à lista de pendências
            $pendingBirth[] = [
                'student_id' => $student['id'],
                'uf' => $uf,
                'city_text' => $cityText
            ];
            echo "   ⚠ Aluno #{$student['id']}: '{$cityText}' ({$uf}) - SEM MATCH\n";
        }
    }
    
    // Salvar pendências em CSV
    $csvFile = ROOT_PATH . '/storage/migration_pending_cities_' . date('Y-m-d_His') . '.csv';
    $csvDir = dirname($csvFile);
    
    if (!is_dir($csvDir)) {
        mkdir($csvDir, 0755, true);
    }
    
    if (!empty($pendingAddress) || !empty($pendingBirth)) {
        $fp = fopen($csvFile, 'w');
        
        // Header
        fputcsv($fp, ['Tipo', 'Aluno ID', 'UF', 'Cidade (texto)']);
        
        // Pendências de endereço
        foreach ($pendingAddress as $pending) {
            fputcsv($fp, ['endereco', $pending['student_id'], $pending['uf'], $pending['city_text']]);
        }
        
        // Pendências de nascimento
        foreach ($pendingBirth as $pending) {
            fputcsv($fp, ['nascimento', $pending['student_id'], $pending['uf'], $pending['city_text']]);
        }
        
        fclose($fp);
        
        echo "\n📄 Pendências salvas em: {$csvFile}\n";
    }
    
    // Resumo final
    echo "\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "  RESUMO DA MIGRAÇÃO\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    echo "✅ Cidade de Endereço:\n";
    echo "   Migrados: {$migratedAddress}\n";
    echo "   Pendentes: " . count($pendingAddress) . "\n\n";
    
    echo "✅ Cidade de Nascimento:\n";
    echo "   Migrados: {$migratedBirth}\n";
    echo "   Pendentes: " . count($pendingBirth) . "\n\n";
    
    $totalMigrated = $migratedAddress + $migratedBirth;
    $totalPending = count($pendingAddress) + count($pendingBirth);
    
    echo "📊 TOTAL:\n";
    echo "   Migrados: {$totalMigrated}\n";
    echo "   Pendentes: {$totalPending}\n\n";
    
    if ($totalPending > 0) {
        echo "⚠️  ATENÇÃO: {$totalPending} registro(s) não puderam ser migrados automaticamente.\n";
        echo "   Verifique o arquivo CSV para revisão manual.\n\n";
    }
    
    echo "✅ MIGRAÇÃO CONCLUÍDA!\n";
    
} catch (Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    if ($e->getCode() > 0) {
        echo "Código: " . $e->getCode() . "\n";
    }
    exit(1);
}
