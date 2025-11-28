<?php
/**
 * ============================================================================
 * SCRIPT DE IMPORTAÇÃO DE MUNICÍPIOS VIA CSV (PLANO B)
 * ============================================================================
 * 
 * PROPÓSITO:
 * Este script é o PLANO B para gerar admin/data/municipios_br.php quando
 * o servidor não tem acesso à internet ou a API do IBGE está instável.
 * 
 * COMO EXECUTAR:
 * 
 * 1. Via CLI (Terminal/PowerShell):
 *    cd c:\xampp\htdocs\cfc-bom-conselho
 *    php admin/data/importar_municipios_ibge.php
 * 
 * 2. Via Navegador:
 *    http://localhost/cfc-bom-conselho/admin/data/importar_municipios_ibge.php
 * 
 * REQUISITOS:
 * - Arquivo CSV com municípios do IBGE
 * - CSV deve estar em: admin/data/fontes/municipios_ibge.csv
 * 
 * ESTRUTURA DO CSV:
 * O arquivo CSV deve ter pelo menos as seguintes colunas:
 * - Coluna 1: Código IBGE (opcional, mas recomendado)
 * - Coluna 2: Nome do Município (OBRIGATÓRIO)
 * - Coluna 3: UF (sigla do estado, OBRIGATÓRIO)
 * 
 * Exemplo de linha:
 * 1100015,Alta Floresta D'Oeste,RO
 * 
 * ONDE OBTER O CSV:
 * 1. Acesse: https://www.ibge.gov.br/explica/codigos-dos-municipios.php
 * 2. Baixe a lista completa de municípios
 * 3. Salve como: admin/data/fontes/municipios_ibge.csv
 * 
 * VALIDAÇÕES:
 * - Verifica se arquivo CSV existe
 * - Valida estrutura mínima
 * - Compara contagens com valores esperados
 * - NÃO grava se houver dados incompletos
 * 
 * ============================================================================
 */

// Configurações
$caminhoCSV = __DIR__ . '/fontes/municipios_ibge.csv';
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

// Função para importar do CSV
function importarMunicipiosDoCSV($caminhoCSV) {
    global $valoresEsperados;
    
    $municipios = [];
    $erros = [];
    $linhaNum = 0;
    
    if (!file_exists($caminhoCSV)) {
        die("❌ ERRO: Arquivo CSV não encontrado: $caminhoCSV\n\n" .
            "INSTRUÇÕES:\n" .
            "1. Baixe o CSV do IBGE com todos os municípios\n" .
            "2. Crie o diretório: admin/data/fontes/\n" .
            "3. Salve o arquivo como: municipios_ibge.csv\n" .
            "4. Execute este script novamente\n");
    }
    
    $handle = fopen($caminhoCSV, 'r');
    if ($handle === false) {
        die("❌ ERRO: Não foi possível abrir o arquivo CSV\n");
    }
    
    // Detectar se tem cabeçalho (primeira linha)
    $primeiraLinha = fgetcsv($handle);
    $temCabecalho = false;
    
    // Verificar se primeira linha parece ser cabeçalho (contém palavras como "nome", "município", "uf")
    if ($primeiraLinha && is_array($primeiraLinha)) {
        $primeiraLinhaLower = array_map('strtolower', $primeiraLinha);
        $palavrasCabecalho = ['nome', 'municipio', 'município', 'uf', 'estado', 'codigo', 'código'];
        foreach ($primeiraLinhaLower as $coluna) {
            foreach ($palavrasCabecalho as $palavra) {
                if (strpos($coluna, $palavra) !== false) {
                    $temCabecalho = true;
                    break 2;
                }
            }
        }
    }
    
    // Se não tem cabeçalho, voltar ao início
    if (!$temCabecalho) {
        rewind($handle);
    }
    
    $linhaNum = $temCabecalho ? 1 : 0;
    
    while (($data = fgetcsv($handle)) !== false) {
        $linhaNum++;
        
        // Pular linhas vazias
        if (empty(array_filter($data))) {
            continue;
        }
        
        // Tentar diferentes formatos de CSV
        // Formato 1: codigo_ibge, nome, uf
        // Formato 2: nome, uf
        // Formato 3: uf, nome
        
        $codigoIBGE = '';
        $nomeMunicipio = '';
        $uf = '';
        
        if (count($data) >= 3) {
            // Assumir formato: codigo, nome, uf
            $codigoIBGE = trim($data[0] ?? '');
            $nomeMunicipio = trim($data[1] ?? '');
            $uf = strtoupper(trim($data[2] ?? ''));
        } elseif (count($data) >= 2) {
            // Assumir formato: nome, uf
            $nomeMunicipio = trim($data[0] ?? '');
            $uf = strtoupper(trim($data[1] ?? ''));
        }
        
        // Validações
        if (empty($uf) || empty($nomeMunicipio)) {
            if ($linhaNum <= 10) { // Só avisar nas primeiras linhas
                $erros[] = "Linha $linhaNum: Dados incompletos (ignorada)";
            }
            continue;
        }
        
        // Validar UF
        if (!isset($valoresEsperados[$uf])) {
            if ($linhaNum <= 10) {
                $erros[] = "Linha $linhaNum: UF inválida '$uf' (ignorada)";
            }
            continue;
        }
        
        // Organizar por UF
        if (!isset($municipios[$uf])) {
            $municipios[$uf] = [];
        }
        
        // Evitar duplicados
        if (!in_array($nomeMunicipio, $municipios[$uf])) {
            $municipios[$uf][] = $nomeMunicipio;
        }
    }
    
    fclose($handle);
    
    // Ordenar municípios dentro de cada UF
    foreach ($municipios as $uf => &$lista) {
        sort($lista);
    }
    
    return ['municipios' => $municipios, 'erros' => $erros];
}

// Função para gerar arquivo PHP (mesma do script principal)
function gerarArquivoPHP($municipiosPorUF, $arquivoSaida) {
    $total = array_sum(array_map('count', $municipiosPorUF));
    
    $output = "<?php\n";
    $output .= "/**\n";
    $output .= " * Fonte centralizada de municípios brasileiros por UF\n";
    $output .= " * \n";
    $output .= " * Este arquivo contém todos os municípios do Brasil organizados por estado (UF).\n";
    $output .= " * Fonte: IBGE (2024) - Total: $total municípios\n";
    $output .= " * Gerado automaticamente via CSV do IBGE em " . date('d/m/Y H:i:s') . "\n";
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
echo "║  IMPORTADOR DE MUNICÍPIOS VIA CSV (PLANO B)                   ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Fase 1: Importar do CSV
echo "FASE 1: Importando municípios do CSV...\n";
echo str_repeat("-", 60) . "\n";
echo "  Arquivo CSV: $caminhoCSV\n";

$resultado = importarMunicipiosDoCSV($caminhoCSV);
$municipiosPorUF = $resultado['municipios'];
$errosImportacao = $resultado['erros'];

if (!empty($errosImportacao) && count($errosImportacao) <= 10) {
    echo "\n  Avisos durante importação:\n";
    foreach ($errosImportacao as $erro) {
        echo "    - $erro\n";
    }
}

echo "  ✓ Importação concluída\n\n";

// Fase 2: Validações
echo "FASE 2: Validações...\n";
echo str_repeat("-", 60) . "\n";

$erros = [];
$avisos = [];
$total = array_sum(array_map('count', $municipiosPorUF));
$totalEsperado = array_sum($valoresEsperados);

// Verificar estados faltando
$estadosEsperados = array_keys($valoresEsperados);
$estadosEncontrados = array_keys($municipiosPorUF);
$estadosFaltando = array_diff($estadosEsperados, $estadosEncontrados);

if (!empty($estadosFaltando)) {
    foreach ($estadosFaltando as $uf) {
        $erros[] = "$uf: Estado não encontrado no CSV";
    }
}

// Verificar quantidades por UF
foreach ($valoresEsperados as $uf => $esperado) {
    $encontrado = isset($municipiosPorUF[$uf]) ? count($municipiosPorUF[$uf]) : 0;
    if ($encontrado < $esperado) {
        $diferenca = $esperado - $encontrado;
        $avisos[] = "$uf: $encontrado municípios (esperado: $esperado, faltam: $diferenca)";
    }
}

// Verificar total
if ($total < ($totalEsperado * 0.95)) {
    $erros[] = "Total muito baixo: $total (esperado: ~$totalEsperado)";
}

// Exibir resumo
echo "\nRESUMO:\n";
echo "  Total de estados encontrados: " . count($municipiosPorUF) . " / " . count($valoresEsperados) . "\n";
echo "  Total de municípios: $total\n";
echo "  Erros: " . count($erros) . "\n";
echo "  Avisos: " . count($avisos) . "\n\n";

// Tabela
echo "TABELA DE MUNICÍPIOS POR UF:\n";
echo str_repeat("-", 60) . "\n";
printf("  %-5s | %-12s | %-12s | %s\n", "UF", "Encontrado", "Esperado", "Status");
echo str_repeat("-", 60) . "\n";

foreach ($valoresEsperados as $uf => $esperado) {
    $encontrado = isset($municipiosPorUF[$uf]) ? count($municipiosPorUF[$uf]) : 0;
    $status = ($encontrado >= $esperado) ? "✓ OK" : (($encontrado > 0) ? "⚠ BAIXO" : "❌ FALTOU");
    printf("  %-5s | %-12d | %-12d | %s\n", $uf, $encontrado, $esperado, $status);
}

echo str_repeat("-", 60) . "\n\n";

// Fase 3: Decisão de gravação
if (!empty($erros)) {
    echo "❌ ERROS CRÍTICOS:\n";
    foreach ($erros as $erro) {
        echo "   - $erro\n";
    }
    echo "\n⚠️  Arquivo NÃO será atualizado devido aos erros acima.\n";
    exit(1);
}

if (!empty($avisos)) {
    echo "⚠️  AVISOS:\n";
    foreach ($avisos as $aviso) {
        echo "   - $aviso\n";
    }
    echo "\n";
}

// Fase 4: Gravação
echo "FASE 3: Gerando arquivo...\n";
echo str_repeat("-", 60) . "\n";

// Backup
if (file_exists($arquivoSaida)) {
    if (copy($arquivoSaida, $arquivoBackup)) {
        echo "  ✓ Backup criado\n";
    }
}

// Gerar
if (gerarArquivoPHP($municipiosPorUF, $arquivoSaida)) {
    echo "  ✓ Arquivo gerado: municipios_br.php\n";
    echo "  ✓ Total: $total municípios\n";
} else {
    echo "  ❌ ERRO ao gravar arquivo\n";
    exit(1);
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  ✓ CONCLUÍDO COM SUCESSO                                       ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";
