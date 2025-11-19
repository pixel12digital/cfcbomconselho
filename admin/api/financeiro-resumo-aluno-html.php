<?php
/**
 * API para obter resumo financeiro do aluno renderizado em HTML
 * Sistema CFC - Bom Conselho
 * 
 * Retorna HTML pronto para inserir no container do resumo financeiro
 */

header('Content-Type: text/html; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Responder a requisições OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir arquivos necessários
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../includes/FinanceiroService.php';

try {
    // Verificar se sistema financeiro está habilitado
    if (!defined('FINANCEIRO_ENABLED') || !FINANCEIRO_ENABLED) {
        echo '<div class="text-center text-danger small">Sistema financeiro desabilitado.</div>';
        exit;
    }
    
    // Verificar autenticação
    if (!isLoggedIn()) {
        echo '<div class="text-center text-danger small">Usuário não autenticado.</div>';
        exit;
    }
    
    // Verificar permissão (apenas admin e secretaria)
    $currentUser = getCurrentUser();
    if (!$currentUser || !in_array($currentUser['tipo'], ['admin', 'secretaria'])) {
        echo '<div class="text-center text-danger small">Acesso negado.</div>';
        exit;
    }
    
    // Obter aluno_id
    $alunoId = $_GET['aluno_id'] ?? null;
    
    if (!$alunoId || !is_numeric($alunoId)) {
        echo '<div class="text-center text-danger small">ID do aluno não fornecido ou inválido.</div>';
        exit;
    }
    
    // Calcular resumo financeiro
    $resumo = FinanceiroService::calcularResumoFinanceiroAluno((int)$alunoId);
    
    // Funções helper para formatação
    $formatarMoeda = function($valor) {
        return 'R$ ' . number_format($valor ?? 0, 2, ',', '.');
    };
    
    $formatarData = function($data) {
        if (!$data) return null;
        try {
            $d = new DateTime($data);
            return $d->format('d/m/Y');
        } catch (Exception $e) {
            return null;
        }
    };
    
    // Mapear status para labels e classes
    $statusMap = [
        'nao_lancado' => ['label' => 'Não lançado', 'class' => 'secondary'],
        'em_dia' => ['label' => 'Em dia', 'class' => 'success'],
        'em_aberto' => ['label' => 'Em aberto', 'class' => 'warning'],
        'parcial' => ['label' => 'Parcialmente pago', 'class' => 'info'],
        'inadimplente' => ['label' => 'Inadimplente', 'class' => 'danger']
    ];
    
    $statusInfo = $statusMap[$resumo['status_financeiro']] ?? $statusMap['nao_lancado'];
    
    // Renderizar HTML
    ob_start();
    ?>
    <?php if ($resumo['qtd_faturas'] === 0): ?>
        <div class="text-center">
            <span class="badge bg-<?php echo htmlspecialchars($statusInfo['class']); ?> mb-2"><?php echo htmlspecialchars($statusInfo['label']); ?></span>
            <p class="text-muted small mb-0" style="font-size: 0.85rem;">Não há faturas lançadas para este aluno.</p>
        </div>
    <?php else: ?>
        <div>
            <div class="text-center mb-2">
                <span class="badge bg-<?php echo htmlspecialchars($statusInfo['class']); ?>"><?php echo htmlspecialchars($statusInfo['label']); ?></span>
            </div>
            <div class="small" style="font-size: 0.85rem;">
                <p class="mb-1">
                    <strong>Contratado:</strong> <?php echo $formatarMoeda($resumo['total_contratado']); ?> • 
                    <strong>Pago:</strong> <?php echo $formatarMoeda($resumo['total_pago']); ?> • 
                    <strong>Saldo:</strong> <?php echo $formatarMoeda($resumo['saldo_aberto']); ?>
                </p>
                <?php if (!empty($resumo['proximo_vencimento'])): ?>
                    <p class="mb-1 text-muted">Próximo vencimento: <?php echo htmlspecialchars($formatarData($resumo['proximo_vencimento'])); ?></p>
                <?php endif; ?>
                <?php if (!empty($resumo['qtd_faturas_vencidas']) && $resumo['qtd_faturas_vencidas'] > 0): ?>
                    <p class="mb-0 text-danger" style="font-weight: 600;">Faturas vencidas: <?php echo (int)$resumo['qtd_faturas_vencidas']; ?></p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    <?php
    echo ob_get_clean();
    
} catch (Exception $e) {
    error_log("Erro em financeiro-resumo-aluno-html.php: " . $e->getMessage());
    echo '<div class="text-center text-danger small">Erro ao carregar resumo financeiro.</div>';
}

