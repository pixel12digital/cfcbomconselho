<?php
/**
 * Script para gerar seed completo de cidades do IBGE
 * 
 * Este script busca dados da API do IBGE e gera o arquivo SQL completo
 * com todas as cidades brasileiras (~5570)
 * 
 * Execute: php tools/generate_cities_seed.php
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

echo "=== GERADOR DE SEED COMPLETO - CIDADES IBGE ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar se estados existem
    $stmt = $db->query("SELECT id, uf FROM states ORDER BY uf");
    $states = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
    if (empty($states)) {
        throw new Exception("Estados não encontrados! Execute primeiro o seed 003_seed_states.sql");
    }
    
    echo "✓ " . count($states) . " estados encontrados no banco\n\n";
    
    // Criar mapa UF -> ID
    $stateMap = [];
    foreach ($states as $state) {
        $stateMap[$state['uf']] = $state['id'];
    }
    
    echo "Buscando dados do IBGE...\n";
    
    // Buscar todos os municípios da API do IBGE
    $url = 'https://servicodados.ibge.gov.br/api/v1/localidades/municipios';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$response) {
        throw new Exception("Erro ao buscar dados do IBGE. HTTP Code: {$httpCode}");
    }
    
    $municipios = json_decode($response, true);
    
    if (empty($municipios)) {
        throw new Exception("Nenhum município retornado da API do IBGE");
    }
    
    echo "✓ " . count($municipios) . " municípios encontrados na API do IBGE\n\n";
    
    // Agrupar por UF
    $citiesByState = [];
    foreach ($municipios as $municipio) {
        // Verificar estrutura da API
        if (!isset($municipio['microrregiao']['mesorregiao']['UF']['sigla'])) {
            // Tentar estrutura alternativa
            if (isset($municipio['microrregiao']['mesorregiao']['UF'])) {
                $uf = is_array($municipio['microrregiao']['mesorregiao']['UF']) 
                    ? ($municipio['microrregiao']['mesorregiao']['UF']['sigla'] ?? null)
                    : null;
            } else {
                continue; // Pular se não tiver estrutura válida
            }
        } else {
            $uf = $municipio['microrregiao']['mesorregiao']['UF']['sigla'];
        }
        
        if (empty($uf)) {
            continue; // Pular se UF estiver vazia
        }
        
        if (!isset($citiesByState[$uf])) {
            $citiesByState[$uf] = [];
        }
        $citiesByState[$uf][] = [
            'name' => $municipio['nome'] ?? '',
            'ibge_code' => $municipio['id'] ?? 0
        ];
    }
    
    // Gerar SQL
    echo "Gerando arquivo SQL...\n";
    
    $sqlContent = "-- Seed 004: Cidades IBGE Completo (~5570 municípios)\n";
    $sqlContent .= "-- Gerado automaticamente em " . date('Y-m-d H:i:s') . "\n";
    $sqlContent .= "-- Fonte: API IBGE (servicodados.ibge.gov.br)\n";
    $sqlContent .= "-- Idempotente: usa INSERT IGNORE para evitar duplicatas\n\n";
    
    // Ordenar por UF
    ksort($citiesByState);
    
    $totalCities = 0;
    $batchSize = 50; // Inserir em lotes de 50 para melhor performance
    
    foreach ($citiesByState as $uf => $cities) {
        if (!isset($stateMap[$uf])) {
            echo "⚠️  Aviso: UF '{$uf}' não encontrada no banco, pulando...\n";
            continue;
        }
        
        $stateId = $stateMap[$uf];
        $sqlContent .= "-- Cidades de {$uf} (" . count($cities) . " municípios)\n";
        $sqlContent .= "SET @{$uf}_id = (SELECT id FROM states WHERE uf = '{$uf}');\n\n";
        
        // Dividir em lotes
        $batches = array_chunk($cities, $batchSize);
        
        foreach ($batches as $batch) {
            $values = [];
            foreach ($batch as $city) {
                $name = addslashes($city['name']);
                $ibgeCode = (int)$city['ibge_code'];
                $values[] = "(@{$uf}_id, '{$name}', {$ibgeCode})";
            }
            
            $sqlContent .= "INSERT IGNORE INTO `cities` (`state_id`, `name`, `ibge_code`) VALUES\n";
            $sqlContent .= "  " . implode(",\n  ", $values) . ";\n\n";
            
            $totalCities += count($batch);
        }
    }
    
    // Salvar arquivo
    $outputFile = ROOT_PATH . '/database/seeds/004_seed_cities_ibge_full.sql';
    file_put_contents($outputFile, $sqlContent);
    
    echo "✓ Arquivo gerado: {$outputFile}\n";
    echo "✓ Total de cidades: {$totalCities}\n";
    echo "✓ Total de estados: " . count($citiesByState) . "\n\n";
    
    echo "✅ SEED COMPLETO GERADO COM SUCESSO!\n\n";
    echo "Próximos passos:\n";
    echo "1. Execute: php tools/run_seed_cities_full.php\n";
    echo "   OU\n";
    echo "2. Execute manualmente: mysql -u root cfc_db < database/seeds/004_seed_cities_ibge_full.sql\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
