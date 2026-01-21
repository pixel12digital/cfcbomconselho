<?php

namespace App\Helpers;

/**
 * Gerador de Ícones PWA a partir de logo do CFC
 */
class PwaIconGenerator
{
    /**
     * Gera ícones PWA (192x192 e 512x512) a partir de uma imagem de logo
     * 
     * @param string $sourcePath Caminho completo do arquivo de logo original
     * @param int $cfcId ID do CFC (para criar diretório específico)
     * @return array|false Array com paths dos ícones gerados ou false em caso de erro
     */
    public static function generateIcons($sourcePath, $cfcId)
    {
        // Verificar se GD está disponível
        if (!extension_loaded('gd')) {
            return false;
        }

        // Verificar se arquivo existe
        if (!file_exists($sourcePath)) {
            return false;
        }

        // Detectar tipo de imagem
        $imageInfo = getimagesize($sourcePath);
        if ($imageInfo === false) {
            return false;
        }

        $mimeType = $imageInfo['mime'];
        
        // Carregar imagem original
        $sourceImage = null;
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case 'image/webp':
                $sourceImage = imagecreatefromwebp($sourcePath);
                break;
            default:
                return false;
        }

        if (!$sourceImage) {
            return false;
        }

        // Criar diretório para ícones do CFC
        $rootPath = dirname(__DIR__, 2);
        $iconsDir = $rootPath . '/public_html/icons/' . $cfcId;
        if (!is_dir($iconsDir)) {
            mkdir($iconsDir, 0755, true);
        }

        $sizes = [192, 512];
        $generatedIcons = [];

        foreach ($sizes as $size) {
            // Criar imagem redimensionada (mantendo proporção e centralizando)
            $icon = imagecreatetruecolor($size, $size);
            
            // Fundo branco sólido (ícones PWA precisam de fundo sólido)
            $white = imagecolorallocate($icon, 255, 255, 255);
            imagefill($icon, 0, 0, $white);

            // Obter dimensões da imagem original
            $sourceWidth = imagesx($sourceImage);
            $sourceHeight = imagesy($sourceImage);

            // Calcular dimensões para manter proporção (com padding de 10%)
            $padding = 0.1; // 10% de padding
            $maxWidth = $size * (1 - 2 * $padding);
            $maxHeight = $size * (1 - 2 * $padding);

            // Calcular escala
            $scale = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight);
            $newWidth = (int)($sourceWidth * $scale);
            $newHeight = (int)($sourceHeight * $scale);

            // Centralizar
            $x = (int)(($size - $newWidth) / 2);
            $y = (int)(($size - $newHeight) / 2);

            // Redimensionar e copiar
            imagealphablending($icon, true);
            imagecopyresampled(
                $icon, $sourceImage,
                $x, $y, 0, 0,
                $newWidth, $newHeight,
                $sourceWidth, $sourceHeight
            );

            // Salvar ícone
            $filename = "icon-{$size}x{$size}.png";
            $filepath = $iconsDir . '/' . $filename;
            
            if (imagepng($icon, $filepath)) {
                $generatedIcons[$size] = "icons/{$cfcId}/{$filename}";
            }

            imagedestroy($icon);
        }

        imagedestroy($sourceImage);

        // Retornar false se nenhum ícone foi gerado
        if (empty($generatedIcons)) {
            return false;
        }

        return $generatedIcons;
    }

    /**
     * Remove ícones PWA de um CFC
     * 
     * @param int $cfcId ID do CFC
     * @return bool
     */
    public static function removeIcons($cfcId)
    {
        $rootPath = dirname(__DIR__, 2);
        $iconsDir = $rootPath . '/public_html/icons/' . $cfcId;
        
        if (is_dir($iconsDir)) {
            $files = glob($iconsDir . '/*.png');
            foreach ($files as $file) {
                @unlink($file);
            }
            @rmdir($iconsDir);
            return true;
        }

        return false;
    }

    /**
     * Verifica se ícones PWA existem para um CFC
     * 
     * @param int $cfcId ID do CFC
     * @return bool
     */
    public static function iconsExist($cfcId)
    {
        $rootPath = dirname(__DIR__, 2);
        $icon192 = $rootPath . '/public_html/icons/' . $cfcId . '/icon-192x192.png';
        $icon512 = $rootPath . '/public_html/icons/' . $cfcId . '/icon-512x512.png';
        
        return file_exists($icon192) && file_exists($icon512);
    }
}
