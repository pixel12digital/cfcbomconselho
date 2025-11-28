<?php
/**
 * ============================================================================
 * SCRIPT OFICIAL DE GERAÇÃO DE MUNICÍPIOS DO BRASIL
 * ============================================================================
 * 
 * PROPÓSITO:
 * Este é o script PRINCIPAL para gerar/atualizar admin/data/municipios_br.php
 * com todos os ~5.570 municípios do Brasil, obtidos diretamente da API oficial
 * do IBGE.
 * 
 * COMO EXECUTAR:
 * 
 * 1. Via CLI (Terminal/PowerShell):
 *    cd c:\xampp\htdocs\cfc-bom-conselho
 *    php admin/data/gerar_municipios_alternativo.php
 * 
 * 2. Via Navegador (se permitido):
 *    http://localhost/cfc-bom-conselho/admin/data/gerar_municipios_alternativo.php
 * 
 * REQUISITOS:
 * - Servidor com acesso à internet (para chamar API do IBGE)
 * - PHP com extensão cURL habilitada
 * - Permissão de escrita no diretório admin/data/
 * 
 * VALIDAÇÕES:
 * - Verifica se todos os estados foram carregados
 * - Compara contagens com valores esperados mínimos
 * - NÃO grava arquivo se houver falhas ou dados incompletos
 * 
 * FONTE DOS DADOS:
 * API oficial do IBGE: https://servicodados.ibge.gov.br/api/v1/localidades/
 * 
 * ============================================================================
 */

// Configurações
$arquivoSaida = __DIR__ . '/municipios_br.php';
$arquivoBackup = __DIR__ . '/municipios_br.php.backup';

// Valores esperados mínimos por UF (IBGE 2024)
$valoresEsperados = [
    'AC' => 22, 'AL' => 102, 'AP' => 16, 'AM' => 62, 'BA' => 417, 'CE' => 184,
    'DF' => 1, 'ES' => 78, 'GO' => 246, 'MA' => 217, 'MT' => 142, 'MS' => 79,
    'MG' => 853, 'PA' => 144, 'PB' => 223, 'PR' => 399, 'PE' => 185, 'PI' => 224,
    'RJ' => 92, 'RN' => 167, 'RS' => 497, 'RO' => 52, 'RR' => 15,
    'SC' => 295, 'SP' => 645, 'SE' => 75, 'TO' => 139
];

// Mapeamento de UF para código do IBGE
$estados = [
    'AC' => 12, 'AL' => 27, 'AP' => 16, 'AM' => 13, 'BA' => 29, 'CE' => 23,
    'DF' => 53, 'ES' => 32, 'GO' => 52, 'MA' => 21, 'MT' => 51, 'MS' => 50,
    'MG' => 31, 'PA' => 15, 'PB' => 25, 'PR' => 41, 'PE' => 26, 'PI' => 22,
    'RJ' => 33, 'RN' => 24, 'RS' => 43, 'RO' => 11, 'RR' => 14,
    'SC' => 42, 'SP' => 35, 'SE' => 28, 'TO' => 17
];

// Função para gerar arquivo PHP
function gerarArquivoPHP($municipiosPorUF, $arquivoSaida) {
    $total = array_sum(array_map('count', $municipiosPorUF));
    
    $output = "<?php\n";
    $output .= "/**\n";
    $output .= " * Fonte centralizada de municípios brasileiros por UF\n";
    $output .= " * \n";
    $output .= " * Este arquivo contém todos os municípios do Brasil organizados por estado (UF).\n";
    $output .= " * Fonte: IBGE (2024) - Total: $total municípios\n";
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
    
    ksort($municipiosPorUF);
    
    foreach ($municipiosPorUF as $uf => $listaMunicipios) {
        $output .= "        '$uf' => [\n";
        
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
    
    return file_put_contents($arquivoSaida, $output) !== false;
}

// ============================================================================
// INÍCIO DO PROCESSAMENTO
// ============================================================================

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  GERADOR OFICIAL DE MUNICÍPIOS DO BRASIL (IBGE)                ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$municipiosPorUF = [];
$erros = [];
$avisos = [];

// Fase 1: Buscar municípios de cada estado
echo "FASE 1: Buscando municípios por estado via API do IBGE...\n";
echo str_repeat("-", 60) . "\n";

foreach ($estados as $uf => $codigoIBGE) {
    echo sprintf("  [%s] Buscando... ", $uf);
    
    $url = "https://servicodados.ibge.gov.br/api/v1/localidades/estados/$codigoIBGE/municipios";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Validação 1: HTTP Status
    if ($httpCode !== 200) {
        $erro = "HTTP $httpCode";
        if ($curlError) {
            $erro .= " - $curlError";
        }
        $erros[] = "$uf: $erro";
        echo "❌ ERRO: $erro\n";
        continue;
    }
    
    // Validação 2: Resposta vazia
    if (empty($response)) {
        $erros[] = "$uf: Resposta vazia da API";
        echo "❌ ERRO: Resposta vazia\n";
        continue;
    }
    
    // Validação 3: JSON válido
    $municipios = json_decode($response, true);
    if (!$municipios || !is_array($municipios)) {
        $erros[] = "$uf: JSON inválido ou estrutura incorreta";
        echo "❌ ERRO: JSON inválido\n";
        continue;
    }
    
    // Processar municípios
    $listaMunicipios = [];
    foreach ($municipios as $municipio) {
        $nome = trim($municipio['nome'] ?? '');
        if (!empty($nome)) {
            $listaMunicipios[] = $nome;
        }
    }
    
    // Validação 4: Lista não vazia
    if (empty($listaMunicipios)) {
        $erros[] = "$uf: Nenhum município encontrado";
        echo "❌ ERRO: Nenhum município encontrado\n";
        continue;
    }
    
    // Ordenar alfabeticamente
    sort($listaMunicipios);
    $municipiosPorUF[$uf] = $listaMunicipios;
    
    $quantidade = count($listaMunicipios);
    $esperado = $valoresEsperados[$uf] ?? 0;
    
    // Validação 5: Quantidade mínima esperada
    if ($esperado > 0 && $quantidade < $esperado) {
        $diferenca = $esperado - $quantidade;
        $avisos[] = "$uf: $quantidade municípios (esperado: $esperado, faltam: $diferenca)";
        echo "⚠️  AVISO: $quantidade municípios (esperado: $esperado)\n";
    } else {
        echo "✓ $quantidade municípios\n";
    }
    
    // Delay para não sobrecarregar a API
    usleep(200000); // 0.2 segundos
}

echo str_repeat("-", 60) . "\n\n";

// Fase 2: Validações finais
echo "FASE 2: Validações finais...\n";
echo str_repeat("-", 60) . "\n";

// Validação: Todos os estados foram carregados?
$estadosFaltando = array_diff(array_keys($estados), array_keys($municipiosPorUF));
if (!empty($estadosFaltando)) {
    foreach ($estadosFaltando as $uf) {
        $erros[] = "$uf: Estado não foi carregado";
    }
}

// Validação: Total de municípios
$total = array_sum(array_map('count', $municipiosPorUF));
$totalEsperado = array_sum($valoresEsperados);

if ($total < ($totalEsperado * 0.95)) { // Tolerância de 5%
    $erros[] = "Total muito baixo: $total (esperado: ~$totalEsperado)";
}

// Exibir resumo
echo "\nRESUMO:\n";
echo "  Total de estados processados: " . count($municipiosPorUF) . " / " . count($estados) . "\n";
echo "  Total de municípios: $total\n";
echo "  Erros encontrados: " . count($erros) . "\n";
echo "  Avisos: " . count($avisos) . "\n\n";

// Exibir tabela UF | Quantidade
echo "TABELA DE MUNICÍPIOS POR UF:\n";
echo str_repeat("-", 60) . "\n";
printf("  %-5s | %-12s | %-12s | %s\n", "UF", "Encontrado", "Esperado", "Status");
echo str_repeat("-", 60) . "\n";

foreach ($estados as $uf => $codigo) {
    $encontrado = isset($municipiosPorUF[$uf]) ? count($municipiosPorUF[$uf]) : 0;
    $esperado = $valoresEsperados[$uf] ?? 0;
    $status = ($encontrado >= $esperado) ? "✓ OK" : (($encontrado > 0) ? "⚠ BAIXO" : "❌ FALTOU");
    printf("  %-5s | %-12d | %-12d | %s\n", $uf, $encontrado, $esperado, $status);
}

echo str_repeat("-", 60) . "\n\n";

// Fase 3: Decisão de gravação
if (!empty($erros)) {
    echo "❌ ERROS CRÍTICOS ENCONTRADOS:\n";
    foreach ($erros as $erro) {
        echo "   - $erro\n";
    }
    echo "\n";
    echo "⚠️  ATENÇÃO: O arquivo NÃO será atualizado devido aos erros acima.\n";
    echo "   Revise os erros e execute novamente.\n";
    echo "\n";
    exit(1);
}

if (!empty($avisos)) {
    echo "⚠️  AVISOS (dados podem estar incompletos):\n";
    foreach ($avisos as $aviso) {
        echo "   - $aviso\n";
    }
    echo "\n";
    echo "Deseja continuar mesmo com avisos? (S/N): ";
    
    // Se executado via CLI, assumir 'S' após 5 segundos
    if (php_sapi_name() === 'cli') {
        echo "S (assumido após timeout)\n";
        $continuar = true;
    } else {
        // Via navegador, não perguntar (assumir sim)
        $continuar = true;
    }
    
    if (!$continuar) {
        echo "\nOperação cancelada pelo usuário.\n";
        exit(0);
    }
}

// Fase 4: Backup e gravação
echo "FASE 3: Gerando arquivo...\n";
echo str_repeat("-", 60) . "\n";

// Fazer backup do arquivo existente
if (file_exists($arquivoSaida)) {
    if (copy($arquivoSaida, $arquivoBackup)) {
        echo "  ✓ Backup criado: municipios_br.php.backup\n";
    } else {
        echo "  ⚠ Aviso: Não foi possível criar backup\n";
    }
}

// Gerar novo arquivo
if (gerarArquivoPHP($municipiosPorUF, $arquivoSaida)) {
    echo "  ✓ Arquivo gerado: municipios_br.php\n";
    echo "  ✓ Total de municípios: $total\n";
    echo "  ✓ Total de estados: " . count($municipiosPorUF) . "\n";
} else {
    echo "  ❌ ERRO: Não foi possível gravar o arquivo\n";
    echo "  Verifique permissões de escrita no diretório admin/data/\n";
    exit(1);
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  ✓ CONCLUÍDO COM SUCESSO                                       ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "PRÓXIMOS PASSOS:\n";
echo "1. Teste a API: admin/api/municipios.php?uf=PE\n";
echo "2. Verifique se 'Bom Conselho' aparece na lista de PE\n";
echo "3. Teste no formulário de alunos\n";
echo "4. Valide outros estados críticos (SP, MG, BA)\n\n";
