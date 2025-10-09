<?php
/**
 * API Simplificada para Exames - Funcional
 */

// Configurar relatório de erros
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/exames_simple_errors.log');

// Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Função para retornar JSON
function returnJson($data) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Incluir arquivos necessários
    require_once __DIR__ . '/../../includes/config.php';
    require_once __DIR__ . '/../../includes/database.php';
    require_once __DIR__ . '/../../includes/auth.php';
    
    // Verificar autenticação
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id']) || !isLoggedIn()) {
        http_response_code(401);
        returnJson(['error' => 'Não autenticado']);
    }
    
    $user = getCurrentUser();
    $db = db();
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // Buscar exame específico
        $exameId = $_GET['id'] ?? null;
        if ($exameId) {
            $exame = $db->fetch("SELECT * FROM exames WHERE id = ?", [$exameId]);
            if ($exame) {
                returnJson([
                    'success' => true,
                    'exame' => $exame
                ]);
            } else {
                returnJson(['error' => 'Exame não encontrado']);
            }
        } else {
            returnJson(['error' => 'ID do exame é obrigatório']);
        }
    } elseif ($method === 'POST') {
        $action = $_POST['action'] ?? 'create';
        
        switch ($action) {
            case 'create':
                // Agendar exame
                $required = ['aluno_id', 'tipo', 'data_agendada'];
                foreach ($required as $field) {
                    if (empty($_POST[$field])) {
                        returnJson(['error' => "Campo '$field' é obrigatório"]);
                    }
                }
                
                $exameData = [
                    'aluno_id' => $_POST['aluno_id'],
                    'tipo' => $_POST['tipo'],
                    'status' => 'agendado',
                    'resultado' => 'pendente',
                    'clinica_nome' => $_POST['clinica_nome'] ?? null,
                    'protocolo' => $_POST['protocolo'] ?? null,
                    'data_agendada' => $_POST['data_agendada'],
                    'observacoes' => $_POST['observacoes'] ?? null,
                    'criado_por' => $user['id']
                ];
                
                $exameId = $db->insert('exames', $exameData);
                
                if ($exameId) {
                    returnJson([
                        'success' => true,
                        'message' => 'Exame agendado com sucesso',
                        'exame_id' => $exameId
                    ]);
                } else {
                    returnJson(['error' => 'Erro ao agendar exame']);
                }
                break;
                
            case 'update':
                // Atualizar exame
                $exameId = $_POST['exame_id'] ?? null;
                if (!$exameId) {
                    returnJson(['error' => 'ID do exame é obrigatório']);
                }
                
                $updateData = [
                    'atualizado_por' => $user['id']
                ];
                
                $allowedFields = ['aluno_id', 'tipo', 'data_agendada', 'clinica_nome', 'protocolo', 'observacoes', 'status', 'resultado', 'data_resultado'];
                foreach ($allowedFields as $field) {
                    if (isset($_POST[$field]) && $_POST[$field] !== '') {
                        $updateData[$field] = $_POST[$field];
                    }
                }
                
                // Se alterando resultado, definir status
                if (isset($updateData['resultado'])) {
                    if (in_array($updateData['resultado'], ['apto', 'inapto'])) {
                        $updateData['status'] = 'concluido';
                        if (empty($updateData['data_resultado'])) {
                            $updateData['data_resultado'] = date('Y-m-d');
                        }
                    } elseif ($updateData['resultado'] === 'inapto_temporario') {
                        $updateData['status'] = 'pendente';
                        if (empty($updateData['data_resultado'])) {
                            $updateData['data_resultado'] = date('Y-m-d');
                        }
                    } elseif ($updateData['resultado'] === 'pendente') {
                        $updateData['status'] = 'agendado';
                        $updateData['data_resultado'] = null;
                    }
                }
                
                $success = $db->update('exames', $updateData, 'id = ?', [$exameId]);
                
                if ($success) {
                    returnJson([
                        'success' => true,
                        'message' => 'Exame atualizado com sucesso'
                    ]);
                } else {
                    returnJson(['error' => 'Erro ao atualizar exame']);
                }
                break;
                
            case 'cancel':
                // Cancelar exame
                $exameId = $_POST['exame_id'] ?? null;
                if (!$exameId) {
                    returnJson(['error' => 'ID do exame é obrigatório']);
                }
                
                $success = $db->update('exames', [
                    'status' => 'cancelado',
                    'atualizado_por' => $user['id']
                ], 'id = ?', [$exameId]);
                
                if ($success) {
                    returnJson([
                        'success' => true,
                        'message' => 'Exame cancelado com sucesso'
                    ]);
                } else {
                    returnJson(['error' => 'Erro ao cancelar exame']);
                }
                break;
                
            case 'delete':
                // Excluir exame (apenas admin)
                if ($user['tipo'] !== 'admin') {
                    returnJson(['error' => 'Apenas administradores podem excluir exames']);
                }
                
                $exameId = $_POST['exame_id'] ?? null;
                if (!$exameId) {
                    returnJson(['error' => 'ID do exame é obrigatório']);
                }
                
                $success = $db->delete('exames', 'id = ?', [$exameId]);
                
                if ($success) {
                    returnJson([
                        'success' => true,
                        'message' => 'Exame excluído com sucesso'
                    ]);
                } else {
                    returnJson(['error' => 'Erro ao excluir exame']);
                }
                break;
                
            default:
                returnJson(['error' => 'Ação não reconhecida']);
        }
    } else {
        returnJson(['error' => 'Método não permitido']);
    }
    
} catch (Exception $e) {
    error_log('[EXAMES SIMPLE] Erro: ' . $e->getMessage());
    http_response_code(500);
    returnJson(['error' => 'Erro interno: ' . $e->getMessage()]);
}
?>
