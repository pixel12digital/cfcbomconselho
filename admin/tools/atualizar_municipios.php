<?php
/**
 * ============================================================================
 * PAINEL INTERNO: ATUALIZAÇÃO DE MUNICÍPIOS
 * ============================================================================
 * 
 * Este painel facilita a atualização da base de municípios sem precisar
 * usar linha de comando.
 * 
 * ACESSO:
 * - Requer autenticação de administrador
 * - URL: admin/tools/atualizar_municipios.php
 * 
 * FUNCIONALIDADES:
 * - Atualizar via API do IBGE (servidor com internet)
 * - Atualizar via CSV local (servidor sem internet)
 * - Visualizar estatísticas atuais
 * - Verificar integridade da base
 * 
 * ============================================================================
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

// Verificar autenticação
$user = getCurrentUser();
if (!$user || $user['tipo'] !== 'admin') {
    die('Acesso negado. Apenas administradores podem acessar este painel.');
}

// Valores esperados
$valoresEsperados = [
    'AC' => 22, 'AL' => 102, 'AP' => 16, 'AM' => 62, 'BA' => 417, 'CE' => 184,
    'DF' => 1, 'ES' => 78, 'GO' => 246, 'MA' => 217, 'MT' => 142, 'MS' => 79,
    'MG' => 853, 'PA' => 144, 'PB' => 223, 'PR' => 399, 'PE' => 185, 'PI' => 224,
    'RJ' => 92, 'RN' => 167, 'RS' => 497, 'RO' => 52, 'RR' => 15,
    'SC' => 295, 'SP' => 645, 'SE' => 75, 'TO' => 139
];

// Função para obter estatísticas atuais
function obterEstatisticasAtuais() {
    global $valoresEsperados;
    
    $arquivo = __DIR__ . '/../data/municipios_br.php';
    
    if (!file_exists($arquivo)) {
        return ['existe' => false, 'erro' => 'Arquivo municipios_br.php não encontrado'];
    }
    
    require_once $arquivo;
    
    if (!function_exists('getMunicipiosBrasil')) {
        return ['existe' => false, 'erro' => 'Função getMunicipiosBrasil() não encontrada'];
    }
    
    $municipios = getMunicipiosBrasil();
    $estatisticas = [];
    $total = 0;
    
    foreach ($valoresEsperados as $uf => $esperado) {
        $encontrado = isset($municipios[$uf]) ? count($municipios[$uf]) : 0;
        $total += $encontrado;
        $status = ($encontrado >= $esperado) ? 'ok' : (($encontrado > 0) ? 'baixo' : 'faltou');
        
        $estatisticas[$uf] = [
            'encontrado' => $encontrado,
            'esperado' => $esperado,
            'status' => $status
        ];
    }
    
    return [
        'existe' => true,
        'total' => $total,
        'estatisticas' => $estatisticas,
        'ultimaModificacao' => filemtime($arquivo)
    ];
}

// Processar ações
$acao = $_GET['acao'] ?? 'visualizar';
$mensagem = '';
$tipoMensagem = '';

if ($acao === 'atualizar_api' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Executar script via API
    $script = __DIR__ . '/../data/gerar_municipios_alternativo.php';
    
    if (!file_exists($script)) {
        $mensagem = 'Script de geração não encontrado';
        $tipoMensagem = 'danger';
    } else {
        // Capturar output do script
        ob_start();
        include $script;
        $output = ob_get_clean();
        
        // Verificar se houve sucesso (procura por "CONCLUÍDO COM SUCESSO")
        if (strpos($output, 'CONCLUÍDO COM SUCESSO') !== false) {
            $mensagem = 'Base de municípios atualizada com sucesso via API do IBGE!';
            $tipoMensagem = 'success';
        } else {
            $mensagem = 'Erro ao atualizar. Verifique os logs abaixo.';
            $tipoMensagem = 'danger';
        }
    }
}

if ($acao === 'atualizar_csv' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Executar script via CSV
    $script = __DIR__ . '/../data/importar_municipios_ibge.php';
    
    if (!file_exists($script)) {
        $mensagem = 'Script de importação não encontrado';
        $tipoMensagem = 'danger';
    } else {
        ob_start();
        include $script;
        $output = ob_get_clean();
        
        if (strpos($output, 'CONCLUÍDO COM SUCESSO') !== false) {
            $mensagem = 'Base de municípios atualizada com sucesso via CSV!';
            $tipoMensagem = 'success';
        } else {
            $mensagem = 'Erro ao atualizar. Verifique se o arquivo CSV existe em admin/data/fontes/municipios_ibge.csv';
            $tipoMensagem = 'danger';
        }
    }
}

$estatisticas = obterEstatisticasAtuais();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualizar Municípios - CFC Bom Conselho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .status-ok { color: #28a745; }
        .status-baixo { color: #ffc107; }
        .status-faltou { color: #dc3545; }
        .card-stat { border-left: 4px solid #007bff; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h2><i class="fas fa-city me-2"></i>Atualizar Base de Municípios</h2>
                <p class="text-muted">Gerencie a base de municípios do Brasil (~5.570 municípios)</p>
                <hr>
            </div>
        </div>

        <?php if ($mensagem): ?>
        <div class="alert alert-<?= $tipoMensagem ?> alert-dismissible fade show">
            <?= htmlspecialchars($mensagem) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Estatísticas Atuais -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-stat">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Estatísticas Atuais</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!$estatisticas['existe']): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?= htmlspecialchars($estatisticas['erro'] ?? 'Arquivo não encontrado') ?>
                            </div>
                        <?php else: ?>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <strong>Total de Municípios:</strong> <?= number_format($estatisticas['total']) ?>
                                </div>
                                <div class="col-md-4">
                                    <strong>Total de Estados:</strong> <?= count($estatisticas['estatisticas']) ?>
                                </div>
                                <div class="col-md-4">
                                    <strong>Última Atualização:</strong> 
                                    <?= date('d/m/Y H:i:s', $estatisticas['ultimaModificacao']) ?>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>UF</th>
                                            <th>Encontrado</th>
                                            <th>Esperado</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($estatisticas['estatisticas'] as $uf => $stat): ?>
                                        <tr>
                                            <td><strong><?= $uf ?></strong></td>
                                            <td><?= $stat['encontrado'] ?></td>
                                            <td><?= $stat['esperado'] ?></td>
                                            <td>
                                                <?php if ($stat['status'] === 'ok'): ?>
                                                    <span class="status-ok"><i class="fas fa-check-circle"></i> OK</span>
                                                <?php elseif ($stat['status'] === 'baixo'): ?>
                                                    <span class="status-baixo"><i class="fas fa-exclamation-triangle"></i> BAIXO</span>
                                                <?php else: ?>
                                                    <span class="status-faltou"><i class="fas fa-times-circle"></i> FALTOU</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Opções de Atualização -->
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-globe me-2"></i>Opção 1: Atualizar via API do IBGE</h5>
                    </div>
                    <div class="card-body">
                        <p>Use esta opção se o servidor tem acesso à internet.</p>
                        <p class="text-muted small">
                            <i class="fas fa-info-circle me-1"></i>
                            O script buscará todos os municípios diretamente da API oficial do IBGE.
                        </p>
                        <form method="POST" action="?acao=atualizar_api">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-sync me-2"></i>Atualizar via API
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-file-csv me-2"></i>Opção 2: Atualizar via CSV Local</h5>
                    </div>
                    <div class="card-body">
                        <p>Use esta opção se o servidor não tem internet ou a API está instável.</p>
                        <p class="text-muted small">
                            <i class="fas fa-info-circle me-1"></i>
                            Requer arquivo CSV em: <code>admin/data/fontes/municipios_ibge.csv</code>
                        </p>
                        <form method="POST" action="?acao=atualizar_csv">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-upload me-2"></i>Atualizar via CSV
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Links Úteis -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-link me-2"></i>Links Úteis</h5>
                    </div>
                    <div class="card-body">
                        <ul>
                            <li>
                                <a href="../api/municipios.php?uf=PE" target="_blank">
                                    Testar API: PE (Pernambuco)
                                </a>
                            </li>
                            <li>
                                <a href="../api/municipios.php?uf=SP" target="_blank">
                                    Testar API: SP (São Paulo)
                                </a>
                            </li>
                            <li>
                                <a href="https://www.ibge.gov.br/explica/codigos-dos-municipios.php" target="_blank">
                                    IBGE - Códigos dos Municípios (fonte oficial)
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

