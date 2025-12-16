<?php
/**
 * Script para gerar ícones PWA a partir do logo do CFC
 * Cria versões circulares nos tamanhos necessários (192x192, 512x512)
 */

// Caminhos
$logoOriginal = __DIR__ . '/../../assets/logo.png';
$outputDir = __DIR__ . '/../../pwa/icons/';

// Verificar se GD está disponível
if (!extension_loaded('gd')) {
    die("❌ Extensão GD não está disponível. Instale php-gd.\n");
}

// Verificar se logo existe
if (!file_exists($logoOriginal)) {
    die("❌ Logo não encontrado: $logoOriginal\n");
}

// Criar diretório se não existir
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

echo "🎨 Gerando ícones PWA a partir do logo do CFC...\n\n";

// Tamanhos necessários
$sizes = [
    ['size' => 192, 'name' => 'icon-192.png', 'maskable' => false],
    ['size' => 512, 'name' => 'icon-512.png', 'maskable' => false],
    ['size' => 512, 'name' => 'icon-512-maskable.png', 'maskable' => true],
    ['size' => 96, 'name' => 'icon-96.png', 'maskable' => false],
];

// Carregar logo original
$logo = imagecreatefrompng($logoOriginal);
if (!$logo) {
    die("❌ Erro ao carregar logo: $logoOriginal\n");
}

// Obter dimensões do logo
$logoWidth = imagesx($logo);
$logoHeight = imagesy($logo);

echo "📐 Logo original: {$logoWidth}x{$logoHeight}px\n\n";

// Função para criar ícone circular
function createCircularIcon($source, $size, $maskable = false) {
    // Criar imagem base (transparente)
    $icon = imagecreatetruecolor($size, $size);
    imagealphablending($icon, false);
    imagesavealpha($icon, true);
    
    // Fundo transparente
    $transparent = imagecolorallocatealpha($icon, 0, 0, 0, 127);
    imagefill($icon, 0, 0, $transparent);
    
    // Para maskable, deixar 20% de padding (safe zone)
    $padding = $maskable ? $size * 0.2 : 0;
    $drawSize = $size - ($padding * 2);
    $centerX = $size / 2;
    $centerY = $size / 2;
    $radius = $drawSize / 2;
    
    // Criar máscara circular
    $mask = imagecreatetruecolor($size, $size);
    imagealphablending($mask, false);
    imagesavealpha($mask, true);
    $transparentMask = imagecolorallocatealpha($mask, 0, 0, 0, 127);
    imagefill($mask, 0, 0, $transparentMask);
    
    // Desenhar círculo branco na máscara
    $white = imagecolorallocate($mask, 255, 255, 255);
    imagefilledellipse($mask, $centerX, $centerY, $drawSize, $drawSize, $white);
    
    // Redimensionar logo para caber no círculo
    $logoResized = imagecreatetruecolor($drawSize, $drawSize);
    imagealphablending($logoResized, false);
    imagesavealpha($logoResized, true);
    
    // Obter dimensões do logo original
    $logoWidth = imagesx($source);
    $logoHeight = imagesy($source);
    
    // Calcular escala para manter proporção
    $scale = min($drawSize / $logoWidth, $drawSize / $logoHeight);
    $newWidth = (int)($logoWidth * $scale);
    $newHeight = (int)($logoHeight * $scale);
    
    // Centralizar
    $offsetX = ($drawSize - $newWidth) / 2;
    $offsetY = ($drawSize - $newHeight) / 2;
    
    // Redimensionar logo
    imagecopyresampled(
        $logoResized, $source,
        0, 0, 0, 0,
        $newWidth, $newHeight, $logoWidth, $logoHeight
    );
    
    // Aplicar máscara circular
    imagealphablending($icon, true);
    for ($x = 0; $x < $size; $x++) {
        for ($y = 0; $y < $size; $y++) {
            $maskPixel = imagecolorat($mask, $x, $y);
            $maskAlpha = ($maskPixel >> 24) & 0xFF;
            
            if ($maskAlpha < 127) { // Dentro do círculo
                $logoX = $x - $padding - $offsetX;
                $logoY = $y - $padding - $offsetY;
                
                if ($logoX >= 0 && $logoX < $newWidth && $logoY >= 0 && $logoY < $newHeight) {
                    $logoPixel = imagecolorat($logoResized, $logoX, $logoY);
                    imagesetpixel($icon, $x, $y, $logoPixel);
                }
            }
        }
    }
    
    // Adicionar borda e sombra (simulação)
    // Borda branca sutil
    $borderColor = imagecolorallocatealpha($icon, 255, 255, 255, 50);
    imagefilledellipse($icon, $centerX, $centerY, $drawSize + 4, $drawSize + 4, $borderColor);
    
    // Limpar recursos
    imagedestroy($mask);
    imagedestroy($logoResized);
    
    return $icon;
}

// Gerar cada tamanho
foreach ($sizes as $config) {
    $size = $config['size'];
    $filename = $config['name'];
    $maskable = isset($config['maskable']) && $config['maskable'];
    
    echo "🖼️  Gerando {$filename} ({$size}x{$size}px)... ";
    
    $icon = createCircularIcon($logo, $size, $maskable);
    
    $outputPath = $outputDir . $filename;
    if (imagepng($icon, $outputPath, 9)) {
        echo "✅\n";
    } else {
        echo "❌ Erro ao salvar\n";
    }
    
    imagedestroy($icon);
}

// Limpar
imagedestroy($logo);

echo "\n✨ Ícones gerados com sucesso em: $outputDir\n";
echo "📝 Atualize os manifests para usar os novos ícones.\n";
