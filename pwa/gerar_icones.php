<?php
/**
 * Gerador de ícones PWA simples
 * Cria ícones básicos para o CFC Bom Conselho
 */

// Função para criar ícone PNG simples
function criarIconePWA($tamanho, $arquivo) {
    // Criar imagem
    $imagem = imagecreatetruecolor($tamanho, $tamanho);
    
    // Cores
    $azul = imagecolorallocate($imagem, 13, 110, 253); // #0d6efd
    $branco = imagecolorallocate($imagem, 255, 255, 255);
    $cinza = imagecolorallocate($imagem, 108, 117, 125);
    
    // Fundo branco
    imagefill($imagem, 0, 0, $branco);
    
    // Desenhar círculo azul
    $raio = $tamanho * 0.4;
    $centro = $tamanho / 2;
    imagefilledellipse($imagem, $centro, $centro, $raio * 2, $raio * 2, $azul);
    
    // Desenhar "CFC" no centro
    $fonte = 5; // Tamanho da fonte
    if ($tamanho >= 512) $fonte = 5;
    elseif ($tamanho >= 256) $fonte = 4;
    elseif ($tamanho >= 128) $fonte = 3;
    else $fonte = 2;
    
    $texto = 'CFC';
    $bbox = imagettfbbox($fonte, 0, __DIR__ . '/arial.ttf', $texto);
    $larguraTexto = $bbox[4] - $bbox[0];
    $alturaTexto = $bbox[1] - $bbox[5];
    
    $x = ($tamanho - $larguraTexto) / 2;
    $y = ($tamanho + $alturaTexto) / 2;
    
    // Usar fonte padrão se não tiver arial
    imagestring($imagem, $fonte, $x, $y - $alturaTexto, $texto, $branco);
    
    // Salvar
    imagepng($imagem, $arquivo);
    imagedestroy($imagem);
}

// Criar ícones
criarIconePWA(192, __DIR__ . '/icons/icon-192.png');
criarIconePWA(512, __DIR__ . '/icons/icon-512.png');

echo "Ícones PWA criados com sucesso!\n";
?>
