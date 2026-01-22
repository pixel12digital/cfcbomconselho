<?php
/**
 * API para buscar dados específicos de um agendamento
 * Retorna dados em formato JSON para edição no modal
 */

// Configurar relatório de erros ANTES de qualquer output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Log inicial para confirmar que o arquivo está sendo executado
error_log("agendamento-detalhes.php: Arquivo iniciado - REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A'));
error_log("agendamento-detalhes.php: __DIR__ = " . __DIR__);
error_log("agendamento-detalhes.php: dirname(__DIR__) = " . dirname(__DIR__));

// Limpar qualquer output anterior (BOM, whitespace, etc.)
while (ob_get_level() > 0) {
    ob_end_clean();
}

// Iniciar buffer de output limpo
ob_start();

// Incluir dependências - usar caminho absoluto como outros arquivos da API
try {
    error_log("agendamento-detalhes.php: Tentando carregar config.php");
    require_once __DIR__ . '/../../includes/config.php';
    error_log("agendamento-detalhes.php: config.php carregado com sucesso");
    
    error_log("agendamento-detalhes.php: Tentando carregar database.php");
    require_once __DIR__ . '/../../includes/database.php';
    error_log("agendamento-detalhes.php: database.php carregado com sucesso");
    
    error_log("agendamento-detalhes.php: Tentando carregar auth.php");
    require_once __DIR__ . '/../../includes/auth.php';
    error_log("agendamento-detalhes.php: auth.php carregado com sucesso");
} catch (Throwable $e) {
    error_log("agendamento-detalhes.php: ERRO ao carregar dependências: " . $e->getMessage());
    error_log("agendamento-detalhes.php: Stack trace: " . $e->getTraceAsString());
    ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao carregar dependências: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Capturar qualquer output dos includes
$output = ob_get_clean();
if (!empty($output) && trim($output) !== '') {
    error_log("agendamento-detalhes.php: Output inesperado dos includes: " . substr($output, 0, 200));
}

// Definir headers para JSON
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
}

// Verificar autenticação
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Usuário não autenticado'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Obter ID do agendamento
    $agendamento_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    error_log("agendamento-detalhes.php: Requisição recebida para ID: " . $agendamento_id);
    
    if (!$agendamento_id) {
        error_log("agendamento-detalhes.php: ID não fornecido ou inválido");
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID do agendamento não fornecido'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Buscar dados do agendamento no banco
    try {
        $db = Database::getInstance();
        error_log("agendamento-detalhes.php: Database instance obtida");
    } catch (Exception $e) {
        error_log("agendamento-detalhes.php: Erro ao obter Database instance: " . $e->getMessage());
        throw $e;
    }
    
    try {
        $agendamento = $db->fetch(
            "SELECT 
                taa.*,
                taa.disciplina,
                COALESCE(u.nome, i.nome, 'Não informado') as instrutor_nome,
                COALESCE(s.nome, 'Não informada') as sala_nome
             FROM turma_aulas_agendadas taa
             LEFT JOIN instrutores i ON taa.instrutor_id = i.id
             LEFT JOIN usuarios u ON i.usuario_id = u.id
             LEFT JOIN salas s ON taa.sala_id = s.id
             WHERE taa.id = ?",
            [$agendamento_id]
        );
        
        error_log("agendamento-detalhes.php: Query executada. Resultado: " . ($agendamento ? 'encontrado' : 'não encontrado'));
        
        // fetch() retorna false quando não encontra resultados
        if ($agendamento === false || empty($agendamento)) {
            error_log("agendamento-detalhes.php: Agendamento ID {$agendamento_id} não encontrado no banco");
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Agendamento não encontrado'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        error_log("agendamento-detalhes.php: Agendamento encontrado: ID=" . ($agendamento['id'] ?? 'N/A') . ", Disciplina=" . ($agendamento['disciplina'] ?? 'N/A'));
    } catch (Exception $e) {
        error_log("agendamento-detalhes.php: Erro ao executar query: " . $e->getMessage());
        error_log("agendamento-detalhes.php: Stack trace: " . $e->getTraceAsString());
        throw $e;
    }
    
    // Calcular duração em minutos
    try {
        $horaInicio = $agendamento['hora_inicio'] ?? '00:00:00';
        $horaFim = $agendamento['hora_fim'] ?? '00:00:00';
        
        // Normalizar formato de hora (remover segundos se presente)
        if (strlen($horaInicio) == 8) {
            $horaInicio = substr($horaInicio, 0, 5);
        }
        if (strlen($horaFim) == 8) {
            $horaFim = substr($horaFim, 0, 5);
        }
        
        // Calcular duração com tratamento de erro
        $horaInicioParts = explode(':', $horaInicio);
        $horaFimParts = explode(':', $horaFim);
        
        if (count($horaInicioParts) >= 2 && count($horaFimParts) >= 2) {
            $hInicio = (int)($horaInicioParts[0] ?? 0);
            $mInicio = (int)($horaInicioParts[1] ?? 0);
            $hFim = (int)($horaFimParts[0] ?? 0);
            $mFim = (int)($horaFimParts[1] ?? 0);
            
            $inicioMinutos = $hInicio * 60 + $mInicio;
            $fimMinutos = $hFim * 60 + $mFim;
            $duracaoMinutos = max(50, $fimMinutos - $inicioMinutos); // Mínimo 50 minutos
        } else {
            error_log("agendamento-detalhes.php: Formato de hora inválido. Inicio: '$horaInicio', Fim: '$horaFim'");
            $duracaoMinutos = 50; // Valor padrão
        }
    } catch (Exception $e) {
        error_log("agendamento-detalhes.php: Erro ao calcular duração: " . $e->getMessage());
        $duracaoMinutos = 50; // Valor padrão em caso de erro
    }
    
    // Preparar dados para retorno
    $dados = [
        'id' => (int)$agendamento['id'],
        'nome_aula' => $agendamento['nome_aula'] ?? '',
        'data_aula' => $agendamento['data_aula'] ?? '',
        'hora_inicio' => $horaInicio,
        'hora_fim' => $horaFim,
        'duracao_minutos' => $duracaoMinutos,
        'instrutor_id' => $agendamento['instrutor_id'] ?? null,
        'instrutor_nome' => $agendamento['instrutor_nome'] ?? 'Não informado',
        'sala_id' => $agendamento['sala_id'] ?? null,
        'sala_nome' => $agendamento['sala_nome'] ?? 'Não informada',
        'observacoes' => $agendamento['observacoes'] ?? '',
        'status' => $agendamento['status'] ?? 'agendada',
        'disciplina' => $agendamento['disciplina'] ?? ''
    ];
    
    // Retornar resposta de sucesso
    error_log("agendamento-detalhes.php: Preparando resposta JSON para ID {$agendamento_id}");
    
    // Limpar qualquer output anterior antes de enviar JSON
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
    }
    
    $json = json_encode([
        'success' => true,
        'agendamento' => $dados
    ], JSON_UNESCAPED_UNICODE);
    
    if ($json === false) {
        error_log("agendamento-detalhes.php: Erro ao codificar JSON: " . json_last_error_msg());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao codificar resposta JSON'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    error_log("agendamento-detalhes.php: Resposta JSON enviada com sucesso");
    echo $json;
    
} catch (PDOException $e) {
    error_log("agendamento-detalhes.php: Erro PDO: " . $e->getMessage());
    error_log("agendamento-detalhes.php: Código do erro: " . $e->getCode());
    error_log("agendamento-detalhes.php: Stack trace: " . $e->getTraceAsString());
    
    // Limpar qualquer output anterior
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Retornar erro
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar agendamento no banco de dados: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("agendamento-detalhes.php: Erro geral: " . $e->getMessage());
    error_log("agendamento-detalhes.php: Stack trace: " . $e->getTraceAsString());
    
    // Limpar qualquer output anterior
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Retornar erro
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar agendamento: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}