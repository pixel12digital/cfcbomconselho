<?php
/**
 * API para consultar Progresso Prático do Aluno
 * Sistema CFC - Bom Conselho
 * 
 * Retorna o status e estatísticas das aulas práticas do aluno
 * 
 * Estrutura de dados:
 * - Tabela principal: `aulas`
 * - Campo de tipo: `tipo_aula ENUM('teorica', 'pratica')` - aulas práticas = 'pratica'
 * - Status possíveis: 'agendada', 'em_andamento', 'concluida', 'cancelada'
 * - Campos relevantes: aluno_id, data_aula, status, instrutor_id
 * 
 * TODO: Integrar com fonte oficial de aulas contratadas (tabela aulas_slots ou similar)
 * quando disponível. Por enquanto, usa total_realizadas + total_agendadas como estimativa.
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
        // OTIMIZADO FASE 2: Calcular todas as estatísticas via SQL agregado ao invés de iterar em PHP
        // Isso elimina a necessidade de buscar até 500 registros e processar em PHP
        $estatisticas = $db->fetch("
            SELECT 
                COUNT(CASE WHEN status = 'concluida' THEN 1 END) as total_realizadas,
                COUNT(CASE WHEN status IN ('agendada', 'em_andamento') THEN 1 END) as total_agendadas,
                COUNT(CASE WHEN status != 'cancelada' THEN 1 END) as total_nao_canceladas,
                MIN(CASE WHEN status != 'cancelada' THEN data_aula END) as primeira_aula,
                MAX(CASE WHEN status != 'cancelada' THEN data_aula END) as ultima_aula
            FROM aulas
            WHERE aluno_id = ? 
            AND tipo_aula = 'pratica'
        ", [$alunoId]);
        
        // Buscar aulas contratadas da matrícula ativa (se disponível)
        $matriculaAtiva = $db->fetch("
            SELECT aulas_praticas_contratadas
            FROM matriculas
            WHERE aluno_id = ? 
            AND status = 'ativa'
            ORDER BY data_inicio DESC
            LIMIT 1
        ", [$alunoId]);
        
        $totalRealizadas = (int)($estatisticas['total_realizadas'] ?? 0);
        $totalAgendadas = (int)($estatisticas['total_agendadas'] ?? 0);
        $totalNaoCanceladas = (int)($estatisticas['total_nao_canceladas'] ?? 0);
        $primeiraAula = $estatisticas['primeira_aula'] ?? null;
        $ultimaAula = $estatisticas['ultima_aula'] ?? null;
        
        // Usar aulas contratadas da matrícula se disponível, senão usar total não canceladas
        $totalContratadas = null;
        if ($matriculaAtiva && isset($matriculaAtiva['aulas_praticas_contratadas']) && $matriculaAtiva['aulas_praticas_contratadas'] > 0) {
            $totalContratadas = (int)$matriculaAtiva['aulas_praticas_contratadas'];
        } else {
            // Fallback: usar total não canceladas como estimativa
            $totalContratadas = $totalNaoCanceladas;
        }
        
        // Se não houver aulas, retornar null
        if ($totalNaoCanceladas === 0) {
            echo json_encode([
                'success' => true,
                'progresso' => null
            ]);
            return;
        }
        
        // Calcular percentual concluído
        $percentualConcluido = 0;
        if ($totalContratadas > 0) {
            $percentualConcluido = round(($totalRealizadas / $totalContratadas) * 100);
        }
        
        // Determinar status consolidado
        $status = 'nao_iniciado';
        
        if ($totalContratadas > 0 && $totalRealizadas >= $totalContratadas) {
            $status = 'concluido';
        } elseif ($totalRealizadas > 0 || $totalAgendadas > 0) {
            $status = 'em_andamento';
        }
        
        // Formatar resposta
        $progresso = [
            'status' => $status,
            'total_contratadas' => $totalContratadas,
            'total_realizadas' => $totalRealizadas,
            'total_agendadas' => $totalAgendadas,
            'percentual_concluido' => $percentualConcluido,
            'primeira_aula' => $primeiraAula,
            'ultima_aula' => $ultimaAula
        ];
        
        echo json_encode([
            'success' => true,
            'progresso' => $progresso
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao buscar progresso prático: ' . $e->getMessage()
        ]);
    }
}

