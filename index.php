<?php
/**
 * Front controller na raiz do projeto
 * Redireciona para public_html/index.php
 * 
 * Este arquivo é necessário porque o DocumentRoot do subdomínio
 * aponta para public_html/painel/ e não para public_html/painel/public_html/
 */

// Caminho para o index.php real
$publicHtmlPath = __DIR__ . '/public_html/index.php';

// Verificar se o arquivo existe
if (!file_exists($publicHtmlPath)) {
    http_response_code(500);
    die('Erro: Arquivo index.php não encontrado em public_html/');
}

// Incluir o index.php real (ele já define ROOT_PATH corretamente)
require_once $publicHtmlPath;
