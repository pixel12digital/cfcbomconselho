<?php
/**
 * API para buscar instrutores reais do banco de dados
 * Retorna dados em formato JSON para uso em modais e formulários
 */

// Desabilitar completamente a exibição de erros
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Limpar qualquer output anterior
while (ob_get_level()) {
    ob_end_clean();
}

// Definir headers para JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Iniciar buffer de output
ob_start();

try {
    // Incluir dependências
    require_once __DIR__ . '/../../includes/config.php';
    require_once __DIR__ . '/../../includes/database.php';
    
    // Verificação básica de sessão
    session_start();
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Não autorizado');
    }
    
    // Conexão com banco
    $db = Database::getInstance();
    
    // CORREÇÃO (12/12/2025): Filtrar instrutores por CFC se turma_id for fornecido
    // Isso garante que apenas instrutores do mesmo CFC da turma sejam listados
    $turmaId = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 0;
    $cfcId = null;
    
    // #region agent log
    $logFile = __DIR__ . '/../../.cursor/debug.log';
    $logEntry = json_encode([
        'id' => 'log_' . time() . '_' . uniqid(),
        'timestamp' => time() * 1000,
        'location' => 'instrutores-real.php:40',
        'message' => 'API chamada - entrada',
        'data' => ['turma_id_param' => $turmaId, 'get_params' => $_GET],
        'sessionId' => 'debug-session',
        'runId' => 'run1',
        'hypothesisId' => 'B'
    ]) . "\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
    // #endregion
    
    if ($turmaId > 0) {
        // Buscar CFC da turma
        $turma = $db->fetch("SELECT cfc_id FROM turmas_teoricas WHERE id = ?", [$turmaId]);
        
        // #region agent log
        $logEntry = json_encode([
            'id' => 'log_' . time() . '_' . uniqid(),
            'timestamp' => time() * 1000,
            'location' => 'instrutores-real.php:47',
            'message' => 'Turma buscada do banco',
            'data' => ['turma_id' => $turmaId, 'turma_cfc_id' => $turma['cfc_id'] ?? null, 'turma_exists' => !empty($turma)],
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'A'
        ]) . "\n";
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
        // #endregion
        
        if ($turma && !empty($turma['cfc_id'])) {
            // Validar se o CFC existe e está ativo
            $cfc = $db->fetch("SELECT id, ativo FROM cfcs WHERE id = ?", [$turma['cfc_id']]);
            if ($cfc && $cfc['ativo']) {
                $cfcId = (int)$turma['cfc_id'];
            }
        }
    }
    
    // Se não tem CFC válido da turma, tentar usar CFC da sessão
    if (!$cfcId && isset($_SESSION['user'])) {
        $userCfcId = $_SESSION['user']['cfc_id'] ?? null;
        if ($userCfcId) {
            $cfc = $db->fetch("SELECT id, ativo FROM cfcs WHERE id = ?", [$userCfcId]);
            if ($cfc && $cfc['ativo']) {
                $cfcId = (int)$userCfcId;
            }
        }
    }
    
    // #region agent log
    $logEntry = json_encode([
        'id' => 'log_' . time() . '_' . uniqid(),
        'timestamp' => time() * 1000,
        'location' => 'instrutores-real.php:65',
        'message' => 'CFC determinado para filtro',
        'data' => ['cfc_id_final' => $cfcId, 'turma_id' => $turmaId],
        'sessionId' => 'debug-session',
        'runId' => 'run1',
        'hypothesisId' => 'A'
    ]) . "\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
    // #endregion
    
    // Montar query com filtro de CFC se disponível
    // CORREÇÃO (12/12/2025): Query robusta para lidar com diferentes tipos de campo ativo
    $whereClause = "(i.ativo = 1 OR i.ativo = TRUE OR (i.ativo IS NOT NULL AND i.ativo != 0))";
    $params = [];
    
    if ($cfcId) {
        $whereClause .= " AND i.cfc_id = ?";
        $params[] = $cfcId;
    }
    
    // #region agent log
    $logEntry = json_encode([
        'id' => 'log_' . time() . '_' . uniqid(),
        'timestamp' => time() * 1000,
        'location' => 'instrutores-real.php:71',
        'message' => 'Query SQL montada',
        'data' => ['where_clause' => $whereClause, 'params' => $params, 'cfc_id_filter' => $cfcId],
        'sessionId' => 'debug-session',
        'runId' => 'run1',
        'hypothesisId' => 'A,D'
    ]) . "\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
    // #endregion
    
    // Buscar instrutores ativos (filtrados por CFC se disponível)
    $instrutores = $db->fetchAll("
        SELECT 
            i.id,
            i.nome AS nome_instrutor,
            i.cpf,
            i.credencial,
            i.ativo,
            i.categoria_habilitacao,
            i.cfc_id,
            u.nome AS nome_usuario
        FROM instrutores i 
        LEFT JOIN usuarios u ON i.usuario_id = u.id 
        WHERE {$whereClause}
        ORDER BY COALESCE(i.nome, u.nome) ASC
    ", $params);
    
    // #region agent log
    // Buscar dados do instrutor Carlos para comparação (sem filtro de CFC)
    $carlosSemFiltro = $db->fetchAll("
        SELECT i.id, i.nome, i.ativo, i.cfc_id, i.usuario_id, u.nome as nome_usuario
        FROM instrutores i
        LEFT JOIN usuarios u ON i.usuario_id = u.id
        WHERE COALESCE(u.nome, i.nome) LIKE '%Carlos%' OR i.credencial LIKE '%TESTE_API%'
    ");
    $carlos = !empty($carlosSemFiltro) ? $carlosSemFiltro[0] : null;
    $logEntry = json_encode([
        'id' => 'log_' . time() . '_' . uniqid(),
        'timestamp' => time() * 1000,
        'location' => 'instrutores-real.php:95',
        'message' => 'Instrutores retornados da query',
        'data' => [
            'total_instrutores' => count($instrutores),
            'instrutor_ids' => array_column($instrutores, 'id'),
            'carlos_data' => $carlos ? ['id' => $carlos['id'], 'nome_usuario' => $carlos['nome_usuario'], 'nome_instrutor' => $carlos['nome'], 'nome_final' => $carlos['nome_usuario'] ?: $carlos['nome'], 'ativo' => $carlos['ativo'], 'cfc_id' => $carlos['cfc_id'], 'usuario_id' => $carlos['usuario_id']] : null,
            'carlos_in_result' => $carlos ? in_array($carlos['id'], array_column($instrutores, 'id')) : false,
            'todos_carlos' => array_map(function($c) {
                return ['id' => $c['id'], 'nome_usuario' => $c['nome_usuario'], 'nome_instrutor' => $c['nome'], 'cfc_id' => $c['cfc_id']];
            }, $carlosSemFiltro ?? [])
        ],
        'sessionId' => 'debug-session',
        'runId' => 'run1',
        'hypothesisId' => 'A,C,D'
    ]) . "\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
    // #endregion
    
    // Processar dados para garantir que temos nomes válidos (usando COALESCE como no PHP)
    $instrutoresProcessados = [];
    foreach ($instrutores as $instrutor) {
        // Priorizar nome do instrutor; usar nome do usuário apenas como fallback
        $nomeInstrutor = trim((string)($instrutor['nome_instrutor'] ?? ''));
        $nomeUsuario = trim((string)($instrutor['nome_usuario'] ?? ''));
        $nome = $nomeInstrutor !== '' ? $nomeInstrutor : ($nomeUsuario !== '' ? $nomeUsuario : 'Instrutor sem nome');
        
        // #region agent log
        if ($instrutor['id'] == 47 || stripos($nome, 'carlos') !== false || stripos($nome, 'teste') !== false) {
            $logEntry = json_encode([
                'id' => 'log_' . time() . '_' . uniqid(),
                'timestamp' => time() * 1000,
                'location' => 'instrutores-real.php:182',
                'message' => 'Processando instrutor (possivelmente Carlos)',
                'data' => [
                    'id' => $instrutor['id'],
                    'nome_usuario' => $instrutor['nome_usuario'],
                    'nome_instrutor' => $instrutor['nome_instrutor'],
                    'nome_final' => $nome,
                    'cfc_id' => $instrutor['cfc_id'] ?? null
                ],
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'E'
            ]) . "\n";
            @file_put_contents($logFile, $logEntry, FILE_APPEND);
        }
        // #endregion
        
        $instrutoresProcessados[] = [
            'id' => (int)$instrutor['id'],
            'nome' => $nome,
            'cpf' => $instrutor['cpf'] ?: '',
            'credencial' => $instrutor['credencial'] ?: '',
            'categoria_habilitacao' => $instrutor['categoria_habilitacao'] ?: null,
            'ativo' => $instrutor['ativo']
        ];
    }
    
    // #region agent log
    $logEntry = json_encode([
        'id' => 'log_' . time() . '_' . uniqid(),
        'timestamp' => time() * 1000,
        'location' => 'instrutores-real.php:205',
        'message' => 'Instrutores processados antes de retornar',
        'data' => [
            'total' => count($instrutoresProcessados),
            'instrutores' => array_map(function($i) {
                return ['id' => $i['id'], 'nome' => $i['nome']];
            }, $instrutoresProcessados)
        ],
        'sessionId' => 'debug-session',
        'runId' => 'run1',
        'hypothesisId' => 'E'
    ]) . "\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
    // #endregion
    
    // Se não houver instrutores, criar um padrão
    if (empty($instrutoresProcessados)) {
        $instrutoresProcessados = [
            [
                'id' => 1,
                'nome' => 'Instrutor Padrão',
                'cpf' => '000.000.000-00',
                'credencial' => '000000',
                'ativo' => 1
            ]
        ];
    }
    
    // Limpar buffer de output
    ob_end_clean();
    
    // Retornar resposta de sucesso
    echo json_encode([
        'success' => true,
        'instrutores' => $instrutoresProcessados,
        'total' => count($instrutoresProcessados)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Limpar buffer de output
    ob_end_clean();
    
    // Retornar erro
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor'
    ], JSON_UNESCAPED_UNICODE);
}
