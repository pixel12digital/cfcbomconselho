<?php
/**
 * Gerador de Ícones PWA - Sistema CFC Bom Conselho
 * Gera todos os ícones necessários para o PWA
 */

// Configurações
$iconSizes = [16, 32, 72, 96, 128, 144, 152, 192, 384, 512];
$sourceIcon = 'icon-source.png'; // Ícone fonte (512x512)
$outputDir = 'icons/';

// Verificar se o diretório de saída existe
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Verificar se o ícone fonte existe
if (!file_exists($sourceIcon)) {
    echo "❌ Ícone fonte não encontrado: $sourceIcon\n";
    echo "📝 Crie um ícone 512x512 PNG e salve como '$sourceIcon'\n";
    exit(1);
}

echo "🚀 Gerando ícones PWA...\n";

// Carregar imagem fonte
$sourceImage = imagecreatefrompng($sourceIcon);
if (!$sourceImage) {
    echo "❌ Erro ao carregar ícone fonte\n";
    exit(1);
}

$generated = 0;
$errors = 0;

foreach ($iconSizes as $size) {
    $outputFile = $outputDir . "icon-{$size}.png";
    
    try {
        // Criar nova imagem
        $newImage = imagecreatetruecolor($size, $size);
        
        // Manter transparência
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
        imagefill($newImage, 0, 0, $transparent);
        
        // Redimensionar
        imagecopyresampled(
            $newImage, $sourceImage,
            0, 0, 0, 0,
            $size, $size,
            imagesx($sourceImage), imagesy($sourceImage)
        );
        
        // Salvar
        if (imagepng($newImage, $outputFile)) {
            echo "✅ Gerado: $outputFile ({$size}x{$size})\n";
            $generated++;
        } else {
            echo "❌ Erro ao salvar: $outputFile\n";
            $errors++;
        }
        
        imagedestroy($newImage);
        
    } catch (Exception $e) {
        echo "❌ Erro ao gerar $outputFile: " . $e->getMessage() . "\n";
        $errors++;
    }
}

// Gerar ícones maskable (com padding)
$maskableSizes = [192, 512];
foreach ($maskableSizes as $size) {
    $outputFile = $outputDir . "icon-{$size}-maskable.png";
    
    try {
        // Tamanho com padding (80% do tamanho original)
        $paddedSize = intval($size * 0.8);
        $padding = intval(($size - $paddedSize) / 2);
        
        // Criar nova imagem
        $newImage = imagecreatetruecolor($size, $size);
        
        // Fundo transparente
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
        imagefill($newImage, 0, 0, $transparent);
        
        // Redimensionar e centralizar
        imagecopyresampled(
            $newImage, $sourceImage,
            $padding, $padding, 0, 0,
            $paddedSize, $paddedSize,
            imagesx($sourceImage), imagesy($sourceImage)
        );
        
        // Salvar
        if (imagepng($newImage, $outputFile)) {
            echo "✅ Gerado (maskable): $outputFile ({$size}x{$size})\n";
            $generated++;
        } else {
            echo "❌ Erro ao salvar: $outputFile\n";
            $errors++;
        }
        
        imagedestroy($newImage);
        
    } catch (Exception $e) {
        echo "❌ Erro ao gerar $outputFile: " . $e->getMessage() . "\n";
        $errors++;
    }
}

// Limpar
imagedestroy($sourceImage);

echo "\n📊 Resumo:\n";
echo "✅ Ícones gerados: $generated\n";
echo "❌ Erros: $errors\n";

if ($errors === 0) {
    echo "\n🎉 Todos os ícones foram gerados com sucesso!\n";
    echo "📱 O PWA está pronto para instalação.\n";
} else {
    echo "\n⚠️ Alguns ícones não foram gerados. Verifique os erros acima.\n";
}

// Gerar HTML de teste
$testHtml = generateTestHtml();
file_put_contents('test-icons.html', $testHtml);
echo "🧪 Arquivo de teste criado: test-icons.html\n";

function generateTestHtml() {
    return '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Ícones PWA</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .icon-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .icon-item { text-align: center; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .icon-item img { max-width: 100%; height: auto; }
        .icon-item h3 { margin: 10px 0 5px 0; }
        .icon-item p { margin: 0; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <h1>🧪 Teste de Ícones PWA</h1>
    <div class="icon-grid">
        <div class="icon-item">
            <img src="icons/icon-16.png" alt="16x16">
            <h3>16x16</h3>
            <p>Favicon</p>
        </div>
        <div class="icon-item">
            <img src="icons/icon-32.png" alt="32x32">
            <h3>32x32</h3>
            <p>Favicon</p>
        </div>
        <div class="icon-item">
            <img src="icons/icon-72.png" alt="72x72">
            <h3>72x72</h3>
            <p>Android</p>
        </div>
        <div class="icon-item">
            <img src="icons/icon-96.png" alt="96x96">
            <h3>96x96</h3>
            <p>Android</p>
        </div>
        <div class="icon-item">
            <img src="icons/icon-128.png" alt="128x128">
            <h3>128x128</h3>
            <p>Chrome</p>
        </div>
        <div class="icon-item">
            <img src="icons/icon-144.png" alt="144x144">
            <h3>144x144</h3>
            <p>Windows</p>
        </div>
        <div class="icon-item">
            <img src="icons/icon-152.png" alt="152x152">
            <h3>152x152</h3>
            <p>iOS</p>
        </div>
        <div class="icon-item">
            <img src="icons/icon-192.png" alt="192x192">
            <h3>192x192</h3>
            <p>Android</p>
        </div>
        <div class="icon-item">
            <img src="icons/icon-192-maskable.png" alt="192x192 Maskable">
            <h3>192x192 Maskable</h3>
            <p>Android (com padding)</p>
        </div>
        <div class="icon-item">
            <img src="icons/icon-384.png" alt="384x384">
            <h3>384x384</h3>
            <p>Android</p>
        </div>
        <div class="icon-item">
            <img src="icons/icon-512.png" alt="512x512">
            <h3>512x512</h3>
            <p>Android</p>
        </div>
        <div class="icon-item">
            <img src="icons/icon-512-maskable.png" alt="512x512 Maskable">
            <h3>512x512 Maskable</h3>
            <p>Android (com padding)</p>
        </div>
    </div>
</body>
</html>';
}
?>
