<?php
/**
 * Teste Direto da API de Municípios
 * 
 * Simula requisições HTTP GET para a API
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Teste API Municípios</title>";
echo "<style>body{font-family:monospace;padding:20px;} .ok{color:green;} .erro{color:red;} pre{background:#f5f5f5;padding:10px;border:1px solid #ddd;overflow:auto;}</style></head><body>";
echo "<h1>🧪 Teste Direto da API</h1>";

// Estados para testar
$estados = ['PE', 'SP', 'MG', 'BA', 'RS'];

foreach ($estados as $uf) {
    echo "<h2>Testando: $uf</h2>";
    
    // Simular GET
    $_GET['uf'] = $uf;
    
    // Capturar saída da API
    ob_start();
    try {
        include __DIR__ . '/api/municipios.php';
        $output = ob_get_clean();
        
        $json = json_decode($output, true);
        
        if ($json && isset($json['success']) && $json['success']) {
            echo "<p class='ok'>✅ API retornou sucesso</p>";
            echo "<p><strong>UF:</strong> {$json['uf']}</p>";
            echo "<p><strong>Total:</strong> {$json['total']} municípios</p>";
            
            // Verificar se "Bom Conselho" está na lista (para PE)
            if ($uf === 'PE') {
                $temBomConselho = in_array('Bom Conselho', $json['municipios']);
                if ($temBomConselho) {
                    echo "<p class='ok'>✅ 'Bom Conselho' encontrado na lista!</p>";
                } else {
                    echo "<p class='erro'>❌ 'Bom Conselho' NÃO encontrado na lista!</p>";
                }
            }
            
            // Mostrar primeiros 10 municípios
            echo "<p><strong>Primeiros 10 municípios:</strong></p>";
            echo "<ul>";
            foreach (array_slice($json['municipios'], 0, 10) as $municipio) {
                echo "<li>$municipio</li>";
            }
            echo "</ul>";
            
            // Verificar se "Bom Conselho" está na lista completa
            if ($uf === 'PE') {
                $posicao = array_search('Bom Conselho', $json['municipios']);
                if ($posicao !== false) {
                    echo "<p class='ok'>✅ 'Bom Conselho' está na posição " . ($posicao + 1) . " de {$json['total']}</p>";
                }
            }
            
        } else {
            echo "<p class='erro'>❌ API retornou erro</p>";
            echo "<pre>" . htmlspecialchars($output) . "</pre>";
        }
        
    } catch (Exception $e) {
        ob_end_clean();
        echo "<p class='erro'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<hr>";
}

echo "<h2>🔗 Links para Teste no Navegador:</h2>";
echo "<ul>";
echo "<li><a href='api/municipios.php?uf=PE' target='_blank'>API: PE (Pernambuco)</a></li>";
echo "<li><a href='api/municipios.php?uf=SP' target='_blank'>API: SP (São Paulo)</a></li>";
echo "<li><a href='api/municipios.php?uf=MG' target='_blank'>API: MG (Minas Gerais)</a></li>";
echo "</ul>";

echo "</body></html>";
?>

