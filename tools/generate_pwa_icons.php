<?php
/**
 * Gerador de Ícones PWA Mínimos
 * Cria ícones 192x192 e 512x512 com texto "CFC" em fundo azul
 * 
 * Requisitos: PHP com extensão GD habilitada
 * Uso: php tools/generate_pwa_icons.php
 */

// Verificar se GD está disponível
if (!extension_loaded('gd')) {
    die("ERRO: Extensão GD não está habilitada no PHP.\n");
}

// Diretório de destino
$iconsDir = __DIR__ . '/../public_html/icons';
if (!is_dir($iconsDir)) {
    mkdir($iconsDir, 0755, true);
}

// Cores do tema
$backgroundColor = [2, 58, 141]; // #023A8D (azul do header)
$textColor = [255, 255, 255]; // Branco

// Tamanhos dos ícones
$sizes = [
    ['size' => 192, 'filename' => 'icon-192x192.png'],
    ['size' => 512, 'filename' => 'icon-512x512.png']
];

foreach ($sizes as $icon) {
    $size = $icon['size'];
    $filename = $icon['filename'];
    $filepath = $iconsDir . '/' . $filename;
    
    // Criar imagem
    $img = imagecreatetruecolor($size, $size);
    
    // Definir cores
    $bgColor = imagecolorallocate($img, $backgroundColor[0], $backgroundColor[1], $backgroundColor[2]);
    $txtColor = imagecolorallocate($img, $textColor[0], $textColor[1], $textColor[2]);
    
    // Preencher fundo
    imagefill($img, 0, 0, $bgColor);
    
    // Calcular tamanho da fonte (proporcional ao tamanho do ícone)
    $fontSize = $size * 0.3; // 30% do tamanho
    $font = 5; // Fonte built-in do GD (pode ser substituída por TTF)
    
    // Texto "CFC"
    $text = 'CFC';
    
    // Calcular posição centralizada
    $textWidth = imagefontwidth($font) * strlen($text);
    $textHeight = imagefontheight($font);
    $x = ($size - $textWidth) / 2;
    $y = ($size - $textHeight) / 2;
    
    // Desenhar texto (usando fonte built-in - simples mas funcional)
    imagestring($img, $font, $x, $y, $text, $txtColor);
    
    // Salvar imagem
    if (imagepng($img, $filepath)) {
        echo "✅ Ícone criado: {$filename} ({$size}x{$size})\n";
    } else {
        echo "❌ Erro ao criar: {$filename}\n";
    }
    
    imagedestroy($img);
}

echo "\n✅ Ícones PWA criados com sucesso em: {$iconsDir}\n";
echo "📝 Nota: Para ícones de melhor qualidade, substitua por arte profissional.\n";
