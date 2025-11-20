<?php
/**
 * API para retornar municípios brasileiros por UF
 * Fonte centralizada de dados de municípios do Brasil
 * 
 * Endpoint: GET admin/api/municipios.php?uf=SC
 * Retorna: JSON com lista de municípios do estado solicitado
 */

header('Content-Type: application/json; charset=utf-8');

// Carregar fonte de dados centralizada
require_once __DIR__ . '/../data/municipios_br.php';

// Obter UF da requisição
$uf = isset($_GET['uf']) ? strtoupper(trim($_GET['uf'])) : '';

if (empty($uf)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Parâmetro UF é obrigatório'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Obter lista completa de municípios
$municipiosCompletos = getMunicipiosBrasil();

// Verificar se a UF existe
if (!isset($municipiosCompletos[$uf])) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => "UF '{$uf}' não encontrada ou não possui municípios cadastrados"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Retornar municípios da UF solicitada (ordenados alfabeticamente)
$municipios = $municipiosCompletos[$uf];
sort($municipios);

echo json_encode([
    'success' => true,
    'uf' => $uf,
    'total' => count($municipios),
    'municipios' => $municipios
], JSON_UNESCAPED_UNICODE);

