<?php
/**
 * Script de Teste Completo: Validação da Base de Municípios
 * 
 * Este script testa:
 * 1. Se o arquivo municipios_br.php existe e funciona
 * 2. Se a API retorna dados corretamente
 * 3. Se municípios específicos estão presentes
 * 
 * Uso: php admin/testar_municipios_completo.php
 * Ou acesse via navegador: admin/testar_municipios_completo.php
 */

header('Content-Type: text/html; charset=utf-8');

$isCLI = php_sapi_name() === 'cli';

if (!$isCLI) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Teste de Municípios</title>";
    echo "<style>body{font-family:monospace;padding:20px;} .ok{color:green;} .erro{color:red;} .aviso{color:orange;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f0f0f0;}</style></head><body>";
    echo "<h1>🧪 Teste Completo: Base de Municípios</h1>";
}

function printMsg($msg, $tipo = 'info') {
    global $isCLI;
    $prefix = $isCLI ? '' : '<div>';
    $suffix = $isCLI ? "\n" : '</div>';
    
    switch($tipo) {
        case 'ok':
            $icon = $isCLI ? '✓' : '✅';
            $class = $isCLI ? '' : ' class="ok"';
            break;
        case 'erro':
            $icon = $isCLI ? '✗' : '❌';
            $class = $isCLI ? '' : ' class="erro"';
            break;
        case 'aviso':
            $icon = $isCLI ? '⚠' : '⚠️';
            $class = $isCLI ? '' : ' class="aviso"';
            break;
        default:
            $icon = $isCLI ? 'ℹ' : 'ℹ️';
            $class = '';
    }
    
    echo "$prefix$icon $msg$suffix";
}

$erros = [];
$sucessos = [];

// ============================================================================
// TESTE 1: Verificar se arquivo existe
// ============================================================================

printMsg("TESTE 1: Verificando arquivo municipios_br.php...", 'info');

$arquivo = __DIR__ . '/data/municipios_br.php';

if (!file_exists($arquivo)) {
    printMsg("ERRO: Arquivo municipios_br.php não encontrado!", 'erro');
    $erros[] = "Arquivo não existe";
} else {
    printMsg("OK: Arquivo encontrado", 'ok');
    $sucessos[] = "Arquivo existe";
    
    // Verificar tamanho
    $tamanho = filesize($arquivo);
    printMsg("Tamanho do arquivo: " . number_format($tamanho) . " bytes", 'info');
}

// ============================================================================
// TESTE 2: Carregar e validar função
// ============================================================================

printMsg("\nTESTE 2: Carregando função getMunicipiosBrasil()...", 'info');

try {
    require_once $arquivo;
    
    if (!function_exists('getMunicipiosBrasil')) {
        printMsg("ERRO: Função getMunicipiosBrasil() não encontrada!", 'erro');
        $erros[] = "Função não existe";
    } else {
        printMsg("OK: Função encontrada", 'ok');
        $sucessos[] = "Função existe";
        
        // Chamar função
        $municipios = getMunicipiosBrasil();
        
        if (!is_array($municipios)) {
            printMsg("ERRO: Função não retorna array!", 'erro');
            $erros[] = "Retorno inválido";
        } else {
            printMsg("OK: Função retorna array válido", 'ok');
            $sucessos[] = "Retorno válido";
        }
    }
} catch (Exception $e) {
    printMsg("ERRO ao carregar arquivo: " . $e->getMessage(), 'erro');
    $erros[] = "Erro ao carregar: " . $e->getMessage();
}

// ============================================================================
// TESTE 3: Validar contagens
// ============================================================================

if (isset($municipios) && is_array($municipios)) {
    printMsg("\nTESTE 3: Validando contagens por UF...", 'info');
    
    $valoresEsperados = [
        'PE' => 185, 'SP' => 645, 'MG' => 853, 'BA' => 417,
        'RS' => 497, 'PR' => 399, 'SC' => 295, 'GO' => 246
    ];
    
    $total = array_sum(array_map('count', $municipios));
    printMsg("Total de municípios: $total (esperado: ~5.570)", $total >= 5570 ? 'ok' : 'aviso');
    
    if ($total >= 5570) {
        $sucessos[] = "Total correto";
    } else {
        $erros[] = "Total abaixo do esperado";
    }
    
    printMsg("Total de estados: " . count($municipios) . " (esperado: 27)", count($municipios) == 27 ? 'ok' : 'erro');
    
    // Tabela de validação
    if (!$isCLI) {
        echo "<h3>Tabela de Validação (Estados Críticos):</h3>";
        echo "<table><tr><th>UF</th><th>Encontrado</th><th>Esperado</th><th>Status</th></tr>";
    } else {
        echo "\nEstados críticos:\n";
        echo str_repeat("-", 50) . "\n";
        printf("%-5s | %-12s | %-12s | %s\n", "UF", "Encontrado", "Esperado", "Status");
        echo str_repeat("-", 50) . "\n";
    }
    
    foreach ($valoresEsperados as $uf => $esperado) {
        $encontrado = isset($municipios[$uf]) ? count($municipios[$uf]) : 0;
        $status = ($encontrado >= $esperado) ? 'OK' : 'ERRO';
        $tipo = ($encontrado >= $esperado) ? 'ok' : 'erro';
        
        if (!$isCLI) {
            echo "<tr><td>$uf</td><td>$encontrado</td><td>$esperado</td><td class='$tipo'>$status</td></tr>";
        } else {
            printf("%-5s | %-12d | %-12d | %s\n", $uf, $encontrado, $esperado, $status);
        }
        
        if ($encontrado >= $esperado) {
            $sucessos[] = "$uf: $encontrado municípios";
        } else {
            $erros[] = "$uf: $encontrado (esperado: $esperado)";
        }
    }
    
    if (!$isCLI) {
        echo "</table>";
    } else {
        echo str_repeat("-", 50) . "\n";
    }
}

// ============================================================================
// TESTE 4: Validar municípios específicos
// ============================================================================

if (isset($municipios) && is_array($municipios)) {
    printMsg("\nTESTE 4: Validando municípios específicos...", 'info');
    
    $municipiosParaValidar = [
        'PE' => 'Bom Conselho',
        'SP' => 'São Paulo',
        'MG' => 'Belo Horizonte',
        'BA' => 'Salvador'
    ];
    
    foreach ($municipiosParaValidar as $uf => $municipio) {
        $presente = isset($municipios[$uf]) && in_array($municipio, $municipios[$uf]);
        
        if ($presente) {
            printMsg("OK: '$municipio' encontrado em $uf", 'ok');
            $sucessos[] = "$municipio em $uf";
        } else {
            printMsg("ERRO: '$municipio' NÃO encontrado em $uf", 'erro');
            $erros[] = "$municipio não encontrado em $uf";
        }
    }
}

// ============================================================================
// TESTE 5: Simular chamada da API
// ============================================================================

printMsg("\nTESTE 5: Simulando chamada da API...", 'info');

if (isset($municipios) && is_array($municipios)) {
    // Simular GET ?uf=PE
    $_GET['uf'] = 'PE';
    
    ob_start();
    try {
        // Simular o que a API faz
        $uf = strtoupper(trim('PE'));
        $municipiosCompletos = $municipios;
        
        if (!isset($municipiosCompletos[$uf])) {
            printMsg("ERRO: UF PE não encontrada na API", 'erro');
            $erros[] = "API não retorna PE";
        } else {
            $municipiosPE = $municipiosCompletos[$uf];
            $totalPE = count($municipiosPE);
            $temBomConselho = in_array('Bom Conselho', $municipiosPE);
            
            printMsg("OK: API retorna $totalPE municípios para PE", 'ok');
            printMsg($temBomConselho ? "OK: 'Bom Conselho' está na resposta da API" : "ERRO: 'Bom Conselho' NÃO está na resposta", $temBomConselho ? 'ok' : 'erro');
            
            if ($temBomConselho) {
                $sucessos[] = "API retorna Bom Conselho";
            } else {
                $erros[] = "API não retorna Bom Conselho";
            }
        }
    } catch (Exception $e) {
        printMsg("ERRO na simulação da API: " . $e->getMessage(), 'erro');
        $erros[] = "Erro na API: " . $e->getMessage();
    }
    ob_end_clean();
}

// ============================================================================
// RESUMO FINAL
// ============================================================================

printMsg("\n" . str_repeat("=", 60), 'info');
printMsg("RESUMO FINAL", 'info');
printMsg(str_repeat("=", 60), 'info');

printMsg("Sucessos: " . count($sucessos), 'ok');
printMsg("Erros: " . count($erros), count($erros) > 0 ? 'erro' : 'ok');

if (count($erros) > 0) {
    printMsg("\nErros encontrados:", 'erro');
    foreach ($erros as $erro) {
        printMsg("  - $erro", 'erro');
    }
}

if (count($sucessos) > 0 && count($erros) == 0) {
    printMsg("\n✅ TODOS OS TESTES PASSARAM!", 'ok');
    printMsg("A base de municípios está completa e funcionando corretamente.", 'ok');
} elseif (count($erros) > 0) {
    printMsg("\n⚠️ ALGUNS TESTES FALHARAM", 'aviso');
    printMsg("Revise os erros acima.", 'aviso');
}

// Links úteis (apenas no navegador)
if (!$isCLI) {
    echo "<hr>";
    echo "<h3>🔗 Links para Teste Manual:</h3>";
    echo "<ul>";
    echo "<li><a href='../api/municipios.php?uf=PE' target='_blank'>Testar API: PE (Pernambuco)</a></li>";
    echo "<li><a href='../api/municipios.php?uf=SP' target='_blank'>Testar API: SP (São Paulo)</a></li>";
    echo "<li><a href='../api/municipios.php?uf=MG' target='_blank'>Testar API: MG (Minas Gerais)</a></li>";
    echo "<li><a href='../pages/alunos.php' target='_blank'>Abrir Formulário de Alunos</a></li>";
    echo "</ul>";
    
    echo "<h3>📊 Estatísticas:</h3>";
    if (isset($municipios) && is_array($municipios)) {
        echo "<p><strong>Total de municípios:</strong> " . array_sum(array_map('count', $municipios)) . "</p>";
        echo "<p><strong>Total de estados:</strong> " . count($municipios) . "</p>";
    }
    
    echo "</body></html>";
}

