<?php
/**
 * API para retornar agendamentos e estatísticas de uma disciplina
 * Usado para atualização dinâmica sem reload da página
 */

// Configurar relatório de erros ANTES de qualquer output
error_reporting(E_ALL);
ini_set('display_errors', 0); // ✅ Desabilitado em produção
ini_set('log_errors', 1);

// Log inicial
error_log("disciplina-agendamentos.php: Arquivo iniciado");

// Limpar qualquer output anterior
while (ob_get_level() > 0) {
    ob_end_clean();
}

// Iniciar buffer de output limpo
ob_start();

// Incluir dependências
try {
    error_log("disciplina-agendamentos.php: Carregando dependências");
    require_once __DIR__ . '/../../includes/config.php';
    require_once __DIR__ . '/../../includes/database.php';
    require_once __DIR__ . '/../../includes/auth.php';
    error_log("disciplina-agendamentos.php: Dependências carregadas com sucesso");
} catch (Throwable $e) {
    error_log("disciplina-agendamentos.php: ERRO ao carregar dependências: " . $e->getMessage());
    ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'mensagem' => 'Erro ao carregar dependências: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Capturar qualquer output dos includes
$output = ob_get_clean();
if (!empty($output) && trim($output) !== '') {
    error_log("disciplina-agendamentos.php: Output inesperado dos includes: " . substr($output, 0, 200));
}

// Definir headers para JSON
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
}

// Verificar autenticação
if (!isLoggedIn()) {
    error_log("disciplina-agendamentos.php: Usuário não autenticado");
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'mensagem' => 'Não autenticado'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Obter parâmetros
    $turmaId = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 0;
    $disciplinaId = $_GET['disciplina_id'] ?? '';
    
    error_log("disciplina-agendamentos.php: Parâmetros - turma_id: $turmaId, disciplina_id: $disciplinaId");
    
    if (!$turmaId || !$disciplinaId) {
        error_log("disciplina-agendamentos.php: Parâmetros inválidos");
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'mensagem' => 'Parâmetros turma_id e disciplina_id são obrigatórios'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Obter instância do banco
    $db = Database::getInstance();
    error_log("disciplina-agendamentos.php: Database instance obtida");
    
    // Buscar agendamentos da disciplina
    $agendamentos = $db->fetchAll(
        "SELECT 
            id,
            nome_aula,
            data_aula,
            hora_inicio,
            hora_fim,
            instrutor_id,
            sala_id,
            duracao_minutos,
            status,
            observacoes,
            disciplina,
            (SELECT nome FROM instrutores WHERE id = turma_aulas_agendadas.instrutor_id) as instrutor_nome,
            (SELECT nome FROM salas WHERE id = turma_aulas_agendadas.sala_id) as sala_nome
        FROM turma_aulas_agendadas
        WHERE turma_id = ?
        AND disciplina = ?
        ORDER BY data_aula ASC, hora_inicio ASC",
        [$turmaId, $disciplinaId]
    );
    
    error_log("disciplina-agendamentos.php: Encontrados " . count($agendamentos) . " agendamentos");
    
    // Calcular estatísticas
    $stats = [
        'agendadas' => 0,
        'realizadas' => 0,
        'faltantes' => 0,
        'obrigatorias' => 0
    ];
    
    foreach ($agendamentos as $ag) {
        if ($ag['status'] === 'agendada' || $ag['status'] === 'reagendada') {
            $stats['agendadas']++;
        } elseif ($ag['status'] === 'realizada') {
            $stats['realizadas']++;
        }
    }
    
    error_log("disciplina-agendamentos.php: Stats calculados - agendadas: {$stats['agendadas']}, realizadas: {$stats['realizadas']}");
    
    // Buscar aulas obrigatórias da disciplina
    // Tentar buscar de diferentes formas para máxima compatibilidade
    try {
        // Buscar o tipo de curso da turma
        $turma = $db->fetch(
            "SELECT curso_tipo FROM turmas_teoricas WHERE id = ?",
            [$turmaId]
        );
        
        error_log("disciplina-agendamentos.php: Turma encontrada: " . ($turma ? 'sim' : 'não'));
        
        if ($turma && isset($turma['curso_tipo'])) {
            $tipoCurso = $turma['curso_tipo'];
            error_log("disciplina-agendamentos.php: Tipo de curso: $tipoCurso");
            
            // Buscar configuração da disciplina usando o código da disciplina
            $disciplinaConfig = $db->fetch(
                "SELECT d.*, dc.aulas_obrigatorias
                FROM disciplinas d
                LEFT JOIN disciplinas_configuracao dc ON d.id = dc.disciplina_id
                LEFT JOIN tipos_curso tc ON dc.tipo_curso_id = tc.id
                WHERE d.codigo = ?
                AND tc.codigo = ?
                LIMIT 1",
                [$disciplinaId, $tipoCurso]
            );
            
            if ($disciplinaConfig && isset($disciplinaConfig['aulas_obrigatorias'])) {
                $stats['obrigatorias'] = (int)$disciplinaConfig['aulas_obrigatorias'];
                error_log("disciplina-agendamentos.php: Aulas obrigatórias encontradas: " . $stats['obrigatorias']);
            } else {
                // Fallback: tentar buscar direto pela disciplina
                error_log("disciplina-agendamentos.php: Tentando buscar pela disciplina diretamente");
                $disciplinaSimples = $db->fetch(
                    "SELECT carga_horaria_padrao FROM disciplinas WHERE codigo = ?",
                    [$disciplinaId]
                );
                
                if ($disciplinaSimples && isset($disciplinaSimples['carga_horaria_padrao'])) {
                    // Calcular número de aulas (assumindo 50min por aula)
                    $cargaHoraria = (int)$disciplinaSimples['carga_horaria_padrao'];
                    $stats['obrigatorias'] = (int)ceil($cargaHoraria / 0.83); // 50min = 0.83h
                    error_log("disciplina-agendamentos.php: Calculado a partir da carga horária: " . $stats['obrigatorias']);
                } else {
                    error_log("disciplina-agendamentos.php: Não foi possível determinar aulas obrigatórias");
                }
            }
        }
    } catch (Exception $e) {
        error_log("disciplina-agendamentos.php: Erro ao buscar aulas obrigatórias: " . $e->getMessage());
        // Continuar sem aulas obrigatórias
    }
    
    $stats['faltantes'] = max(0, $stats['obrigatorias'] - $stats['agendadas'] - $stats['realizadas']);
    
    error_log("disciplina-agendamentos.php: Stats finais - faltantes: {$stats['faltantes']}");
    
    // Retornar dados
    $response = [
        'success' => true,
        'agendamentos' => $agendamentos,
        'stats' => $stats
    ];
    
    error_log("disciplina-agendamentos.php: Sucesso - retornando resposta");
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("disciplina-agendamentos.php: EXCEÇÃO - " . $e->getMessage());
    error_log("disciplina-agendamentos.php: Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'mensagem' => 'Erro ao buscar dados: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE);
}
?>
