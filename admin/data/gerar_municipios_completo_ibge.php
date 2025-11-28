<?php
/**
 * Script para gerar municipios_br.php completo usando API do IBGE
 * 
 * Este script consome a API oficial do IBGE e gera o arquivo municipios_br.php
 * com todos os ~5.570 municípios do Brasil organizados por UF.
 * 
 * Uso: php admin/data/gerar_municipios_completo_ibge.php
 * 
 * Fonte: https://servicodados.ibge.gov.br/api/v1/localidades/municipios
 */

echo "=== GERADOR DE MUNICÍPIOS COMPLETO (IBGE) ===\n\n";

// URL da API do IBGE
$url = 'https://servicodados.ibge.gov.br/api/v1/localidades/municipios';

echo "1. Buscando dados da API do IBGE...\n";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    die("Erro ao buscar dados da API do IBGE. HTTP Code: $httpCode\n");
}

$municipiosData = json_decode($response, true);

if (!$municipiosData || !is_array($municipiosData)) {
    die("Erro ao decodificar JSON da API do IBGE\n");
}

echo "   ✓ " . count($municipiosData) . " municípios encontrados\n\n";

// Organizar por UF
echo "2. Organizando municípios por UF...\n";
$municipiosPorUF = [];

foreach ($municipiosData as $municipio) {
    $uf = null;
    $nomeMunicipio = $municipio['nome'] ?? '';
    
    if (empty($nomeMunicipio)) {
        continue;
    }
    
    // Tentar diferentes caminhos na estrutura JSON da API do IBGE
    if (isset($municipio['microrregiao']['mesorregiao']['UF']['sigla'])) {
        $uf = $municipio['microrregiao']['mesorregiao']['UF']['sigla'];
    } elseif (isset($municipio['regiao-imediata']['regiao-intermediaria']['UF']['sigla'])) {
        $uf = $municipio['regiao-imediata']['regiao-intermediaria']['UF']['sigla'];
    } elseif (isset($municipio['microrregiao']['mesorregiao']['UF'])) {
        // Se UF for um objeto, pegar sigla
        if (is_array($municipio['microrregiao']['mesorregiao']['UF']) && isset($municipio['microrregiao']['mesorregiao']['UF']['sigla'])) {
            $uf = $municipio['microrregiao']['mesorregiao']['UF']['sigla'];
        }
    }
    
    // Se ainda não encontrou, buscar pela API específica do município
    if (!$uf && isset($municipio['id'])) {
        $uf = buscarUF($municipio['id']);
    }
    
    if (!$uf) {
        echo "   ⚠ Aviso: Não foi possível determinar UF para município: $nomeMunicipio (ID: " . ($municipio['id'] ?? 'N/A') . ")\n";
        // Debug: mostrar estrutura do primeiro município com problema
        if (count($municipiosPorUF) === 0) {
            echo "   Estrutura JSON recebida:\n";
            echo "   " . json_encode($municipio, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
        continue;
    }
    
    $uf = strtoupper(trim($uf));
    
    if (!isset($municipiosPorUF[$uf])) {
        $municipiosPorUF[$uf] = [];
    }
    
    // Evitar duplicados
    if (!in_array($nomeMunicipio, $municipiosPorUF[$uf])) {
        $municipiosPorUF[$uf][] = $nomeMunicipio;
    }
}

// Ordenar municípios dentro de cada UF
foreach ($municipiosPorUF as $uf => &$lista) {
    sort($lista);
}

// Ordenar UFs
ksort($municipiosPorUF);

echo "   ✓ Municípios organizados em " . count($municipiosPorUF) . " estados\n\n";

// Gerar arquivo PHP
echo "3. Gerando arquivo municipios_br.php...\n";

$output = "<?php\n";
$output .= "/**\n";
$output .= " * Fonte centralizada de municípios brasileiros por UF\n";
$output .= " * \n";
$output .= " * Este arquivo contém todos os municípios do Brasil organizados por estado (UF).\n";
$output .= " * Fonte: IBGE (2024) - Total: " . array_sum(array_map('count', $municipiosPorUF)) . " municípios\n";
$output .= " * Gerado automaticamente via API do IBGE em " . date('d/m/Y H:i:s') . "\n";
$output .= " * \n";
$output .= " * Uso: require_once __DIR__ . '/data/municipios_br.php';\n";
$output .= " *      \$municipios = getMunicipiosBrasil();\n";
$output .= " *      \$municipiosSC = \$municipios['SC'] ?? [];\n";
$output .= " * \n";
$output .= " * @return array Array associativo onde a chave é a UF e o valor é um array de municípios\n";
$output .= " */\n";
$output .= "function getMunicipiosBrasil() {\n";
$output .= "    return [\n";

foreach ($municipiosPorUF as $uf => $listaMunicipios) {
    $output .= "        '$uf' => [\n";
    
    // Formatar municípios (6 por linha para legibilidade)
    $chunks = array_chunk($listaMunicipios, 6);
    foreach ($chunks as $chunk) {
        $municipiosFormatados = array_map(function($m) {
            return "'" . addslashes($m) . "'";
        }, $chunk);
        $output .= "            " . implode(', ', $municipiosFormatados) . ",\n";
    }
    
    $output .= "        ],\n";
}

$output .= "    ];\n";
$output .= "}\n";
$output .= "\n";

$arquivoSaida = __DIR__ . '/municipios_br.php';
file_put_contents($arquivoSaida, $output);

echo "   ✓ Arquivo gerado: $arquivoSaida\n\n";

// Estatísticas
echo "4. Estatísticas:\n";
echo "   Total de municípios: " . array_sum(array_map('count', $municipiosPorUF)) . "\n";
echo "   Total de estados: " . count($municipiosPorUF) . "\n\n";

echo "Municípios por UF:\n";
foreach ($municipiosPorUF as $uf => $lista) {
    echo "   $uf: " . count($lista) . " municípios\n";
}

echo "\n=== CONCLUÍDO ===\n";

/**
 * Função auxiliar para buscar UF de um município pela API do IBGE
 */
function buscarUF($municipioId) {
    $url = "https://servicodados.ibge.gov.br/api/v1/localidades/municipios/$municipioId";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    if (isset($data['microrregiao']['mesorregiao']['UF']['sigla'])) {
        return $data['microrregiao']['mesorregiao']['UF']['sigla'];
    }
    return null;
}

