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
        // Buscar todas as aulas práticas do aluno (limitado para performance)
        // Usar índices em aluno_id e tipo_aula para melhor performance
        $aulas = $db->fetchAll("
            SELECT 
                id,
                status,
                data_aula
            FROM aulas
            WHERE aluno_id = ? 
            AND tipo_aula = 'pratica'
            AND status != 'cancelada'
            ORDER BY data_aula ASC
            LIMIT 500
        ", [$alunoId]);
        
        if (empty($aulas)) {
            // Nenhuma aula prática encontrada
            echo json_encode([
                'success' => true,
                'progresso' => null
            ]);
            return;
        }
        
        // Calcular estatísticas
        $totalRealizadas = 0;
        $totalAgendadas = 0;
        $datas = [];
        
        foreach ($aulas as $aula) {
            $status = strtolower($aula['status']);
            
            if ($status === 'concluida') {
                $totalRealizadas++;
            } elseif (in_array($status, ['agendada', 'em_andamento'])) {
                $totalAgendadas++;
            }
            
            if ($aula['data_aula']) {
                $datas[] = $aula['data_aula'];
            }
        }
        
        // TODO: Integrar com fonte oficial de aulas contratadas (aulas_slots ou similar)
        // Por enquanto, usa total_realizadas + total_agendadas como estimativa
        $totalContratadas = $totalRealizadas + $totalAgendadas;
        
        // Calcular percentual concluído
        $percentualConcluido = 0;
        if ($totalContratadas > 0) {
            $percentualConcluido = round(($totalRealizadas / $totalContratadas) * 100);
        }
        
        // Primeira e última aula
        $primeiraAula = !empty($datas) ? min($datas) : null;
        $ultimaAula = !empty($datas) ? max($datas) : null;
        
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

