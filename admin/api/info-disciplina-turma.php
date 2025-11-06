<?php
/**
 * API para buscar informações sobre disciplina em uma turma
 * Retorna: total de aulas obrigatórias, agendadas e faltantes
 */

// Remover qualquer BOM ou whitespace antes do PHP tag
if (ob_get_level()) {
    ob_end_clean();
}

// Log de início para debug
error_log("info-disciplina-turma.php: Arquivo iniciado");

// Configurar relatório de erros
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não exibir erros na tela para não quebrar JSON
ini_set('log_errors', 1);

// Registrar handler de erros fatais
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        // Limpar qualquer output
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        
        $message = 'Erro fatal: ' . $error['message'] . ' em ' . $error['file'] . ' linha ' . $error['line'];
        error_log("info-disciplina-turma.php: " . $message);
        
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Erro interno do servidor'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
});

// Limpar qualquer saída anterior
while (ob_get_level()) {
    ob_end_clean();
}

// Iniciar buffer de saída IMEDIATAMENTE para capturar qualquer output
ob_start();

// Verificar se headers já foram enviados ANTES de incluir dependências
if (headers_sent($file, $line)) {
    ob_end_clean();
    error_log("info-disciplina-turma.php: Headers já foram enviados em {$file}:{$line} ANTES de incluir dependências");
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro: Headers já foram enviados'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Incluir dependências
$rootPath = dirname(__DIR__);

try {
    require_once $rootPath . '/includes/config.php';
    require_once $rootPath . '/includes/database.php';
    require_once $rootPath . '/includes/auth.php';
} catch (Throwable $e) {
    $output = ob_get_clean();
    error_log("info-disciplina-turma.php: Erro ao incluir dependências: " . $e->getMessage());
    error_log("info-disciplina-turma.php: Stack trace: " . $e->getTraceAsString());
    
    // Limpar qualquer output antes de enviar resposta
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro ao carregar dependências: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Capturar qualquer output dos includes
$output = ob_get_clean();

// Se houver output inesperado, logar mas não quebrar
if (!empty($output) && trim($output) !== '') {
    error_log("info-disciplina-turma.php: Output inesperado dos includes (" . strlen($output) . " bytes): " . substr($output, 0, 200));
}

// Verificar novamente se headers foram enviados após includes
if (headers_sent($file, $line)) {
    error_log("info-disciplina-turma.php: Headers foram enviados após includes em {$file}:{$line}");
    // Tentar continuar mesmo assim
}

// Definir headers (se ainda não foram enviados)
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
}

// Garantir que a sessão foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Reiniciar buffer para capturar qualquer output futuro
ob_start();

// Função para retornar JSON de forma segura
function returnJsonResponse($data, $httpCode = 200) {
    // Limpar qualquer output anterior
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $json = json_encode([
            'sucesso' => false,
            'mensagem' => 'Erro ao codificar JSON: ' . json_last_error_msg()
        ], JSON_UNESCAPED_UNICODE);
    }
    
    echo $json;
    exit;
}

// Verificar autenticação
if (!isLoggedIn()) {
    error_log("info-disciplina-turma.php: Usuário não autenticado");
    returnJsonResponse([
        'sucesso' => false,
        'mensagem' => 'Usuário não autenticado'
    ], 401);
}

try {
    // Usar Database::getInstance() em vez de db() para garantir que a conexão seja inicializada
    $db = Database::getInstance();
    
    // Obter parâmetros
    $turmaId = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 0;
    $disciplinaIdRaw = isset($_GET['disciplina']) ? trim($_GET['disciplina']) : '';
    
    // Extrair apenas o nome da disciplina (antes dos dois pontos, se houver)
    // Formato pode ser: "legislacao_transito" ou "legislacao_transito:1"
    // Precisamos apenas de "legislacao_transito"
    $disciplinaId = $disciplinaIdRaw;
    if (strpos($disciplinaIdRaw, ':') !== false) {
        $parts = explode(':', $disciplinaIdRaw, 2);
        $disciplinaId = trim($parts[0]);
        error_log("info-disciplina-turma.php: Extraído nome da disciplina de '{$disciplinaIdRaw}' para '{$disciplinaId}'");
    }
    
    if (!$turmaId || !$disciplinaId) {
        returnJsonResponse([
            'sucesso' => false,
            'mensagem' => 'Parâmetros turma_id e disciplina são obrigatórios'
        ], 400);
    }
    
    error_log("info-disciplina-turma.php: Buscando informações para turma_id={$turmaId}, disciplina={$disciplinaId}");
    
    // Buscar informações da turma para obter o curso_tipo
    try {
        $turma = $db->fetch(
            "SELECT curso_tipo FROM turmas_teoricas WHERE id = ?",
            [$turmaId]
        );
        
        // fetch() retorna false quando não encontra resultados
        if ($turma === false || empty($turma) || !isset($turma['curso_tipo'])) {
            error_log("info-disciplina-turma.php: Turma não encontrada ou sem curso_tipo. turma_id={$turmaId}");
            returnJsonResponse([
                'sucesso' => false,
                'mensagem' => 'Turma não encontrada ou sem tipo de curso definido'
            ], 404);
        }
    } catch (Exception $e) {
        error_log("info-disciplina-turma.php: Erro ao buscar turma: " . $e->getMessage());
        returnJsonResponse([
            'sucesso' => false,
            'mensagem' => 'Erro ao buscar informações da turma: ' . $e->getMessage()
        ], 500);
    }
    
    $cursoTipo = $turma['curso_tipo'];
    error_log("info-disciplina-turma.php: Curso tipo encontrado: {$cursoTipo}");
    
    // Buscar total de aulas obrigatórias da disciplina
    // Tenta múltiplas fontes: disciplinas_configuracao, ou valores padrão baseados na disciplina
    $totalObrigatorias = 0;
    $nomeDisciplina = '';
    
    // Primeiro, tentar buscar da tabela disciplinas_configuracao
    try {
        $tabelaExiste = $db->fetch("SHOW TABLES LIKE 'disciplinas_configuracao'");
        if ($tabelaExiste !== false) {
            $disciplinaConfig = $db->fetch(
                "SELECT aulas_obrigatorias, nome_disciplina 
                 FROM disciplinas_configuracao 
                 WHERE curso_tipo = ? AND disciplina = ? AND ativa = 1
                 LIMIT 1",
                [$cursoTipo, $disciplinaId]
            );
            
            if ($disciplinaConfig !== false && !empty($disciplinaConfig)) {
                $totalObrigatorias = (int)($disciplinaConfig['aulas_obrigatorias'] ?? 0);
                $nomeDisciplina = $disciplinaConfig['nome_disciplina'] ?? '';
            }
        }
    } catch (Exception $e1) {
        // Tabela não existe ou erro, continuar
        error_log("info-disciplina-turma.php: Erro ao buscar disciplinas_configuracao: " . $e1->getMessage());
    }
    
    // Se não encontrou na configuração, tentar buscar da tabela disciplinas
    if ($totalObrigatorias == 0) {
        try {
            $disciplina = $db->fetch(
                "SELECT carga_horaria_padrao, nome 
                 FROM disciplinas 
                 WHERE codigo = ? AND ativa = 1
                 LIMIT 1",
                [$disciplinaId]
            );
            
            if ($disciplina !== false && !empty($disciplina)) {
                // Converter carga horária para número de aulas (assumindo 50 min por aula)
                $totalObrigatorias = (int)ceil(($disciplina['carga_horaria_padrao'] ?? 0) / 50);
                $nomeDisciplina = $disciplina['nome'] ?? '';
            }
        } catch (Exception $e2) {
            error_log("info-disciplina-turma.php: Erro ao buscar disciplina: " . $e2->getMessage());
        }
    }
    
    // Valores padrão se ainda não encontrou
    if ($totalObrigatorias == 0) {
        $valoresPadrao = [
            'legislacao_transito' => 18,
            'direcao_defensiva' => 16,
            'primeiros_socorros' => 4,
            'meio_ambiente_cidadania' => 4,
            'mecanica_basica' => 3
        ];
        
        $nomesPadrao = [
            'legislacao_transito' => 'Legislação de Trânsito',
            'direcao_defensiva' => 'Direção Defensiva',
            'primeiros_socorros' => 'Primeiros Socorros',
            'meio_ambiente_cidadania' => 'Meio Ambiente e Cidadania',
            'mecanica_basica' => 'Mecânica Básica'
        ];
        
        $totalObrigatorias = $valoresPadrao[$disciplinaId] ?? 0;
        $nomeDisciplina = $nomesPadrao[$disciplinaId] ?? '';
    }
    
    // Buscar total de aulas agendadas (excluindo canceladas)
    // A busca deve considerar tanto "legislacao_transito" quanto "legislacao_transito:1"
    // Usamos LIKE para buscar disciplinas que começam com o nome da disciplina
    $totalAgendadas = 0;
    
    try {
        $tabelaExiste = $db->fetch("SHOW TABLES LIKE 'turma_aulas_agendadas'");
        if ($tabelaExiste !== false) {
            $aulasAgendadas = $db->fetch(
                "SELECT COUNT(*) as total 
                 FROM turma_aulas_agendadas 
                 WHERE turma_id = ? 
                 AND (disciplina = ? OR disciplina LIKE ?) 
                 AND (status IS NULL OR status != 'cancelada')",
                [$turmaId, $disciplinaId, $disciplinaId . ':%']
            );
            
            if ($aulasAgendadas !== false && isset($aulasAgendadas['total'])) {
                $totalAgendadas = (int)$aulasAgendadas['total'];
            }
        }
    } catch (Exception $e3) {
        // Tabela não existe ou erro, continuar com 0
        error_log("info-disciplina-turma.php: Erro ao buscar turma_aulas_agendadas: " . $e3->getMessage());
    }
    
    // Calcular aulas faltantes
    $totalFaltantes = max(0, $totalObrigatorias - $totalAgendadas);
    
    // Sempre retornar sucesso, mesmo se não encontrou dados
    // A função no frontend já trata isso corretamente
    $resultado = [
        'sucesso' => true,
        'dados' => [
            'total_obrigatorias' => (int)$totalObrigatorias,
            'total_agendadas' => (int)$totalAgendadas,
            'total_faltantes' => (int)$totalFaltantes,
            'nome_disciplina' => $nomeDisciplina ?: ''
        ]
    ];
    
    error_log("info-disciplina-turma.php: Retornando resultado - obrigatorias:{$totalObrigatorias}, agendadas:{$totalAgendadas}, faltantes:{$totalFaltantes}");
    returnJsonResponse($resultado, 200);
    
} catch (PDOException $e) {
    error_log("info-disciplina-turma.php: Erro PDO: " . $e->getMessage());
    error_log("info-disciplina-turma.php: Código do erro: " . $e->getCode());
    error_log("info-disciplina-turma.php: Stack trace: " . $e->getTraceAsString());
    
    returnJsonResponse([
        'sucesso' => false,
        'mensagem' => 'Erro ao buscar informações no banco de dados: ' . $e->getMessage()
    ], 500);
    
} catch (Exception $e) {
    error_log("info-disciplina-turma.php: Erro geral: " . $e->getMessage());
    error_log("info-disciplina-turma.php: Stack trace: " . $e->getTraceAsString());
    
    returnJsonResponse([
        'sucesso' => false,
        'mensagem' => 'Erro ao buscar informações: ' . $e->getMessage()
    ], 500);
}
