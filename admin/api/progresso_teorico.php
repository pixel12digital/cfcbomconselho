<?php
/**
 * API para consultar Progresso Teórico do Aluno
 * Sistema CFC - Bom Conselho
 * 
 * Retorna o status e frequência da matrícula teórica mais recente do aluno
 */

header('Content-Type: application/json; charset=utf-8');
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

try {
    $db = Database::getInstance();
    
    // Verificar autenticação
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
        exit;
    }
    
    // FECHAR SESSÃO APÓS AUTENTICAÇÃO para evitar bloqueio de sessão em requisições simultâneas
    // Isso permite que múltiplas requisições AJAX sejam processadas em paralelo
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        handleGet($db);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro interno: ' . $e->getMessage()]);
}

/**
 * Processar requisições GET
 */
function handleGet($db) {
    $alunoId = $_GET['aluno_id'] ?? null;
    
    if (!$alunoId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Parâmetro aluno_id é obrigatório']);
        return;
    }
    
    // Validar que aluno_id é um número
    if (!is_numeric($alunoId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'aluno_id deve ser um número']);
        return;
    }
    
    $alunoId = (int)$alunoId;
    
    try {
        // Buscar a matrícula teórica mais recente do aluno com dados da turma
        // Usar índice em aluno_id para melhor performance
        $matricula = $db->fetch("
            SELECT 
                tm.status,
                tm.frequencia_percentual,
                tm.data_matricula,
                tm.exames_validados_em,
                tm.turma_id,
                t.nome AS turma_nome
            FROM turma_matriculas tm
            INNER JOIN turmas_teoricas t ON tm.turma_id = t.id
            WHERE tm.aluno_id = ?
            ORDER BY tm.data_matricula DESC, tm.id DESC
            LIMIT 1
        ", [$alunoId]);
        
        if (!$matricula) {
            // Nenhuma matrícula teórica encontrada
            echo json_encode([
                'success' => true,
                'progresso' => null
            ]);
            return;
        }
        
        // Formatar resposta
        $progresso = [
            'status' => $matricula['status'],
            'frequencia_percentual' => $matricula['frequencia_percentual'] !== null 
                ? (float)$matricula['frequencia_percentual'] 
                : null,
            'data_matricula' => $matricula['data_matricula'],
            'exames_validados_em' => $matricula['exames_validados_em'],
            'turma_id' => (int)$matricula['turma_id'],
            'turma_nome' => $matricula['turma_nome']
        ];
        
        echo json_encode([
            'success' => true,
            'progresso' => $progresso
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao buscar progresso teórico: ' . $e->getMessage()
        ]);
    }
}

