<?php
/**
 * Script para gerar ícones PWA circulares a partir do logo do CFC
 * Cria versões circulares (any) e maskable com fundo azul #1A365D
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

echo "🎨 Gerando ícones PWA circulares a partir do logo do CFC...\n\n";

// Carregar logo original
$logo = imagecreatefrompng($logoOriginal);
if (!$logo) {
    die("❌ Erro ao carregar logo: $logoOriginal\n");
}

// Obter dimensões do logo
$logoWidth = imagesx($logo);
$logoHeight = imagesy($logo);

echo "📐 Logo original: {$logoWidth}x{$logoHeight}px\n\n";

// Função para criar ícone circular (any purpose)
function createCircularIcon($source, $size) {
    // Criar imagem com fundo branco sólido (NÃO transparente)
    $icon = imagecreatetruecolor($size, $size);
    
    // Desabilitar alpha blending para fundo sólido
    imagealphablending($icon, false);
    
    // Preencher TODO o fundo com branco sólido primeiro
    $white = imagecolorallocate($icon, 255, 255, 255);
    imagefill($icon, 0, 0, $white);
    
    // Agora habilitar alpha blending para o logo
    imagealphablending($icon, true);
    
    // Calcular tamanho do círculo (deixar margem de 5% para borda)
    $circleSize = $size * 0.9;
    $centerX = $size / 2;
    $centerY = $size / 2;
    $radius = $circleSize / 2;
    
    // Criar círculo branco (sobre o fundo já branco, mas garante forma circular)
    imagefilledellipse($icon, $centerX, $centerY, $circleSize, $circleSize, $white);
    
    // Redimensionar logo para caber dentro do círculo (logo maior: 78-85% do diâmetro)
    $logoArea = $circleSize * 0.82; // 82% do círculo para o logo (maior que antes)
    $logoWidth = imagesx($source);
    $logoHeight = imagesy($source);
    
    // Calcular escala para manter proporção
    $scale = min($logoArea / $logoWidth, $logoArea / $logoHeight);
    $newWidth = (int)($logoWidth * $scale);
    $newHeight = (int)($logoHeight * $scale);
    
    // Centralizar logo
    $offsetX = $centerX - ($newWidth / 2);
    $offsetY = $centerY - ($newHeight / 2);
    
    // Redimensionar logo
    $logoResized = imagecreatetruecolor($newWidth, $newHeight);
    imagealphablending($logoResized, false);
    imagesavealpha($logoResized, true);
    $transparentLogo = imagecolorallocatealpha($logoResized, 0, 0, 0, 127);
    imagefill($logoResized, 0, 0, $transparentLogo);
    
    imagecopyresampled(
        $logoResized, $source,
        0, 0, 0, 0,
        $newWidth, $newHeight, $logoWidth, $logoHeight
    );
    
    // Aplicar logo sobre o círculo azul
    imagealphablending($icon, true);
    imagecopy($icon, $logoResized, (int)$offsetX, (int)$offsetY, 0, 0, $newWidth, $newHeight);
    
    imagedestroy($logoResized);
    
    return $icon;
}

// Função para criar ícone maskable (fundo azul #1A365D sólido, logo com margem grande)
function createMaskableIcon($source, $size) {
    // Criar imagem com fundo azul #1A365D sólido
    $icon = imagecreatetruecolor($size, $size);
    $blue = imagecolorallocate($icon, 26, 54, 93); // #1A365D
    imagefill($icon, 0, 0, $blue);
    
    // Safe zone: 80% do tamanho (deixar 20% de margem para máscara do Android)
    $safeZone = $size * 0.8;
    $centerX = $size / 2;
    $centerY = $size / 2;
    
    // Redimensionar logo para caber na safe zone
    $logoWidth = imagesx($source);
    $logoHeight = imagesy($source);
    
    // Calcular escala (usar 80% da safe zone para o logo, deixando margem)
    $logoArea = $safeZone * 0.8;
    $scale = min($logoArea / $logoWidth, $logoArea / $logoHeight);
    $newWidth = (int)($logoWidth * $scale);
    $newHeight = (int)($logoHeight * $scale);
    
    // Centralizar logo
    $offsetX = $centerX - ($newWidth / 2);
    $offsetY = $centerY - ($newHeight / 2);
    
    // Redimensionar logo
    $logoResized = imagecreatetruecolor($newWidth, $newHeight);
    imagealphablending($logoResized, false);
    imagesavealpha($logoResized, true);
    $transparentLogo = imagecolorallocatealpha($logoResized, 0, 0, 0, 127);
    imagefill($logoResized, 0, 0, $transparentLogo);
    
    imagecopyresampled(
        $logoResized, $source,
        0, 0, 0, 0,
        $newWidth, $newHeight, $logoWidth, $logoHeight
    );
    
    // Aplicar logo sobre fundo azul
    imagealphablending($icon, true);
    imagecopy($icon, $logoResized, (int)$offsetX, (int)$offsetY, 0, 0, $newWidth, $newHeight);
    
    imagedestroy($logoResized);
    
    return $icon;
}

// Gerar ícones (versão v2 com logo maior)
$icons = [
    ['size' => 192, 'name' => 'cfc-192-circle-v2.png', 'type' => 'circular'],
    ['size' => 512, 'name' => 'cfc-512-circle-v2.png', 'type' => 'circular'],
    ['size' => 192, 'name' => 'cfc-192-maskable-v2.png', 'type' => 'maskable'],
    ['size' => 512, 'name' => 'cfc-512-maskable-v2.png', 'type' => 'maskable'],
];

foreach ($icons as $config) {
    $size = $config['size'];
    $filename = $config['name'];
    $type = $config['type'];
    
    echo "🖼️  Gerando {$filename} ({$size}x{$size}px, {$type})... ";
    
    if ($type === 'circular') {
        $icon = createCircularIcon($logo, $size);
    } else {
        $icon = createMaskableIcon($logo, $size);
    }
    
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
