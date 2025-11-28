<?php
/**
 * ============================================================================
 * API PARA RETORNAR MUNICÍPIOS BRASILEIROS POR UF
 * ============================================================================
 * 
 * FONTE OFICIAL DE DADOS:
 * Esta API usa EXCLUSIVAMENTE admin/data/municipios_br.php como fonte de dados.
 * 
 * O arquivo municipios_br.php é gerado pelos scripts:
 * - admin/data/gerar_municipios_alternativo.php (via API IBGE)
 * - admin/data/importar_municipios_ibge.php (via CSV local)
 * 
 * Ambos os scripts geram o mesmo formato, garantindo que esta API funcione
 * independentemente da fonte de geração (API ou CSV).
 * 
 * ENDPOINT:
 * GET admin/api/municipios.php?uf={SIGLA_ESTADO}
 * 
 * EXEMPLO:
 * GET admin/api/municipios.php?uf=PE
 * 
 * RESPOSTA:
 * {
 *   "success": true,
 *   "uf": "PE",
 *   "total": 185,
 *   "municipios": ["Abreu e Lima", "Afogados da Ingazeira", ...]
 * }
 * 
 * ============================================================================
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

