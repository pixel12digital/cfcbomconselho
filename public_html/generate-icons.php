<?php
/**
 * Gerador de Ícones PWA - Acesse via browser: /generate-icons.php
 * Cria ícones 192x192 e 512x512 com texto "CFC" em fundo azul
 */

// Verificar se GD está disponível
if (!extension_loaded('gd')) {
    die("ERRO: Extensão GD não está habilitada no PHP.");
}

// Diretório de destino
$iconsDir = __DIR__ . '/icons';
if (!is_dir($iconsDir)) {
    mkdir($iconsDir, 0755, true);
}

// Cores do tema
$backgroundColor = [2, 58, 141]; // #023A8D
$textColor = [255, 255, 255]; // Branco

// Tamanhos
$sizes = [
    ['size' => 192, 'filename' => 'icon-192x192.png'],
    ['size' => 512, 'filename' => 'icon-512x512.png']
];

$results = [];

foreach ($sizes as $icon) {
    $size = $icon['size'];
    $filename = $icon['filename'];
    $filepath = $iconsDir . '/' . $filename;
    
    // Criar imagem
    $img = imagecreatetruecolor($size, $size);
    
    // Cores
    $bgColor = imagecolorallocate($img, $backgroundColor[0], $backgroundColor[1], $backgroundColor[2]);
    $txtColor = imagecolorallocate($img, $textColor[0], $textColor[1], $textColor[2]);
    
    // Preencher fundo
    imagefill($img, 0, 0, $bgColor);
    
    // Texto "CFC" - usar fonte maior para melhor visibilidade
    $text = 'CFC';
    $font = 5; // Fonte built-in
    
    // Calcular posição centralizada
    $textWidth = imagefontwidth($font) * strlen($text);
    $textHeight = imagefontheight($font);
    $x = ($size - $textWidth) / 2;
    $y = ($size - $textHeight) / 2;
    
    // Desenhar texto
    imagestring($img, $font, $x, $y, $text, $txtColor);
    
    // Salvar
    if (imagepng($img, $filepath)) {
        $results[] = "✅ {$filename} ({$size}x{$size}) criado";
    } else {
        $results[] = "❌ Erro ao criar {$filename}";
    }
    
    imagedestroy($img);
}

// Output
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ícones PWA Gerados</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 20px; border-radius: 8px; max-width: 600px; margin: 0 auto; }
        h1 { color: #023A8D; }
        .result { padding: 10px; margin: 5px 0; background: #f0f0f0; border-radius: 4px; }
        .success { color: #28a745; }
        .info { margin-top: 20px; padding: 15px; background: #e7f3ff; border-left: 4px solid #023A8D; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Ícones PWA Gerados</h1>
        <?php foreach ($results as $result): ?>
            <div class="result"><?= htmlspecialchars($result) ?></div>
        <?php endforeach; ?>
        
        <div class="info">
            <strong>Próximos passos:</strong>
            <ul>
                <li>Os ícones foram criados em: <code>public_html/icons/</code></li>
                <li>Você pode substituí-los por arte profissional quando disponível</li>
                <li>Este arquivo pode ser removido após gerar os ícones</li>
            </ul>
        </div>
        
        <p><a href="/">← Voltar</a></p>
    </div>
</body>
</html>
