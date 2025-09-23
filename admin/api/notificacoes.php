<?php
/**
 * API para gerenciar notificações
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/services/SistemaNotificacoes.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

function returnJsonSuccess($data = null, $message = 'Sucesso') {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

function returnJsonError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    $user = getCurrentUser();
    if (!$user) {
        returnJsonError('Usuário não autenticado', 401);
    }

    $db = db();
    $notificacoes = new SistemaNotificacoes();

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Buscar notificações do usuário
            $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 20;
            $apenas_nao_lidas = isset($_GET['nao_lidas']) && $_GET['nao_lidas'] === 'true';
            
            $sql = "SELECT n.*, 
                           CASE 
                               WHEN n.tipo_usuario = 'aluno' THEN a.nome
                               WHEN n.tipo_usuario = 'instrutor' THEN i.nome
                               WHEN n.tipo_usuario = 'admin' THEN u.nome
                               WHEN n.tipo_usuario = 'secretaria' THEN u.nome
                           END as nome_usuario
                    FROM notificacoes n
                    LEFT JOIN alunos a ON n.usuario_id = a.id AND n.tipo_usuario = 'aluno'
                    LEFT JOIN instrutores i ON n.usuario_id = i.id AND n.tipo_usuario = 'instrutor'
                    LEFT JOIN usuarios u ON n.usuario_id = u.id AND n.tipo_usuario IN ('admin', 'secretaria')
                    WHERE n.usuario_id = ? AND n.tipo_usuario = ?";
            
            $params = [$user['id'], $user['tipo']];
            
            if ($apenas_nao_lidas) {
                $sql .= " AND n.lida = FALSE";
            }
            
            $sql .= " ORDER BY n.criado_em DESC LIMIT ?";
            $params[] = $limite;
            
            $notificacoes_lista = $db->fetchAll($sql, $params);
            
            returnJsonSuccess($notificacoes_lista, 'Notificações carregadas');
            break;

        case 'POST':
            // Marcar notificação como lida
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['notificacao_id'])) {
                returnJsonError('ID da notificação é obrigatório');
            }
            
            $notificacao_id = (int)$input['notificacao_id'];
            
            // Verificar se a notificação pertence ao usuário
            $notificacao = $db->fetch(
                "SELECT * FROM notificacoes WHERE id = ? AND usuario_id = ? AND tipo_usuario = ?",
                [$notificacao_id, $user['id'], $user['tipo']]
            );
            
            if (!$notificacao) {
                returnJsonError('Notificação não encontrada', 404);
            }
            
            // Marcar como lida
            $result = $db->query(
                "UPDATE notificacoes SET lida = TRUE, lida_em = NOW() WHERE id = ?",
                [$notificacao_id]
            );
            
            if ($result) {
                returnJsonSuccess(null, 'Notificação marcada como lida');
            } else {
                returnJsonError('Erro ao marcar notificação como lida', 500);
            }
            break;

        case 'PUT':
            // Marcar todas as notificações como lidas
            $result = $db->query(
                "UPDATE notificacoes SET lida = TRUE, lida_em = NOW() 
                 WHERE usuario_id = ? AND tipo_usuario = ? AND lida = FALSE",
                [$user['id'], $user['tipo']]
            );
            
            if ($result) {
                returnJsonSuccess(null, 'Todas as notificações foram marcadas como lidas');
            } else {
                returnJsonError('Erro ao marcar notificações como lidas', 500);
            }
            break;

        case 'DELETE':
            // Deletar notificação
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['notificacao_id'])) {
                returnJsonError('ID da notificação é obrigatório');
            }
            
            $notificacao_id = (int)$input['notificacao_id'];
            
            // Verificar se a notificação pertence ao usuário
            $notificacao = $db->fetch(
                "SELECT * FROM notificacoes WHERE id = ? AND usuario_id = ? AND tipo_usuario = ?",
                [$notificacao_id, $user['id'], $user['tipo']]
            );
            
            if (!$notificacao) {
                returnJsonError('Notificação não encontrada', 404);
            }
            
            // Deletar notificação
            $result = $db->query("DELETE FROM notificacoes WHERE id = ?", [$notificacao_id]);
            
            if ($result) {
                returnJsonSuccess(null, 'Notificação deletada');
            } else {
                returnJsonError('Erro ao deletar notificação', 500);
            }
            break;

        default:
            returnJsonError('Método não permitido', 405);
    }

} catch (Exception $e) {
    error_log("Erro na API de notificações: " . $e->getMessage());
    returnJsonError('Erro interno do servidor: ' . $e->getMessage(), 500);
}
?>
