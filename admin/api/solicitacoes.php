<?php
/**
 * API para solicitações de reagendamento/cancelamento do aluno
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
            // Buscar solicitações do aluno
            if ($user['tipo'] !== 'aluno') {
                returnJsonError('Acesso negado', 403);
            }
            
            $status = isset($_GET['status']) ? $_GET['status'] : null;
            
            $sql = "SELECT s.*, a.tipo_aula, a.data_aula as data_original, a.hora_inicio as hora_original,
                           i.nome as instrutor_nome, v.modelo as veiculo_modelo
                    FROM solicitacoes_aluno s
                    JOIN aulas a ON s.aula_id = a.id
                    LEFT JOIN instrutores i ON a.instrutor_id = i.id
                    LEFT JOIN veiculos v ON a.veiculo_id = v.id
                    WHERE s.aluno_id = ?";
            
            $params = [$user['id']];
            
            if ($status) {
                $sql .= " AND s.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY s.criado_em DESC";
            
            $solicitacoes = $db->fetchAll($sql, $params);
            
            returnJsonSuccess($solicitacoes, 'Solicitações carregadas');
            break;

        case 'POST':
            // Criar nova solicitação
            if ($user['tipo'] !== 'aluno') {
                returnJsonError('Acesso negado', 403);
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['aula_id']) || !isset($input['tipo_solicitacao']) || !isset($input['justificativa'])) {
                returnJsonError('Campos obrigatórios: aula_id, tipo_solicitacao, justificativa');
            }
            
            $aula_id = (int)$input['aula_id'];
            $tipo_solicitacao = $input['tipo_solicitacao'];
            $justificativa = $input['justificativa'];
            $nova_data = isset($input['nova_data']) ? $input['nova_data'] : null;
            $nova_hora = isset($input['nova_hora']) ? $input['nova_hora'] : null;
            $motivo = isset($input['motivo']) ? $input['motivo'] : null;
            
            // Verificar se a aula pertence ao aluno
            $aula = $db->fetch(
                "SELECT * FROM aulas WHERE id = ? AND aluno_id = ? AND status = 'agendada'",
                [$aula_id, $user['id']]
            );
            
            if (!$aula) {
                returnJsonError('Aula não encontrada ou não pertence ao aluno', 404);
            }
            
            // Verificar se já existe uma solicitação pendente para esta aula
            $solicitacao_existente = $db->fetch(
                "SELECT * FROM solicitacoes_aluno WHERE aula_id = ? AND status = 'pendente'",
                [$aula_id]
            );
            
            if ($solicitacao_existente) {
                returnJsonError('Já existe uma solicitação pendente para esta aula', 409);
            }
            
            // Verificar política de antecedência (24 horas)
            $data_hora_aula = strtotime("{$aula['data_aula']} {$aula['hora_inicio']}");
            if (($data_hora_aula - time()) < (24 * 3600)) {
                returnJsonError('Solicitações só podem ser feitas com no mínimo 24 horas de antecedência', 400);
            }
            
            // Inserir solicitação
            $sql = "INSERT INTO solicitacoes_aluno 
                    (aluno_id, aula_id, tipo_solicitacao, data_aula_original, hora_inicio_original, 
                     nova_data, nova_hora, motivo, justificativa, status, criado_em)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente', NOW())";
            
            $params = [
                $user['id'],
                $aula_id,
                $tipo_solicitacao,
                $aula['data_aula'],
                $aula['hora_inicio'],
                $nova_data,
                $nova_hora,
                $motivo,
                $justificativa
            ];
            
            $result = $db->query($sql, $params);
            
            if ($result) {
                $solicitacao_id = $db->lastInsertId();
                
                // Notificar secretária/admin
                $secretarias = $db->fetchAll(
                    "SELECT id FROM usuarios WHERE tipo IN ('admin', 'secretaria')"
                );
                
                foreach ($secretarias as $secretaria) {
                    $notificacoes->enviarNotificacao(
                        $secretaria['id'],
                        'secretaria',
                        'solicitacao_aluno',
                        "Nova solicitação de {$tipo_solicitacao} do aluno",
                        [
                            'solicitacao_id' => $solicitacao_id,
                            'aula_id' => $aula_id,
                            'tipo_solicitacao' => $tipo_solicitacao,
                            'aluno_id' => $user['id'],
                            'data_original' => $aula['data_aula'],
                            'hora_original' => $aula['hora_inicio'],
                            'nova_data' => $nova_data,
                            'nova_hora' => $nova_hora,
                            'justificativa' => $justificativa
                        ]
                    );
                }
                
                returnJsonSuccess(['solicitacao_id' => $solicitacao_id], 'Solicitação criada com sucesso');
            } else {
                returnJsonError('Erro ao criar solicitação', 500);
            }
            break;

        case 'PUT':
            // Processar solicitação (apenas admin/secretária)
            if (!in_array($user['tipo'], ['admin', 'secretaria'])) {
                returnJsonError('Acesso negado', 403);
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['solicitacao_id']) || !isset($input['acao'])) {
                returnJsonError('Campos obrigatórios: solicitacao_id, acao');
            }
            
            $solicitacao_id = (int)$input['solicitacao_id'];
            $acao = $input['acao']; // 'aprovar' ou 'negar'
            $motivo_decisao = isset($input['motivo_decisao']) ? $input['motivo_decisao'] : null;
            
            // Buscar solicitação
            $solicitacao = $db->fetch(
                "SELECT s.*, a.* FROM solicitacoes_aluno s
                 JOIN aulas a ON s.aula_id = a.id
                 WHERE s.id = ? AND s.status = 'pendente'",
                [$solicitacao_id]
            );
            
            if (!$solicitacao) {
                returnJsonError('Solicitação não encontrada ou já processada', 404);
            }
            
            $novo_status = $acao === 'aprovar' ? 'aprovado' : 'negado';
            
            // Atualizar solicitação
            $sql = "UPDATE solicitacoes_aluno 
                    SET status = ?, aprovado_por = ?, motivo_decisao = ?, processado_em = NOW()
                    WHERE id = ?";
            
            $result = $db->query($sql, [$novo_status, $user['id'], $motivo_decisao, $solicitacao_id]);
            
            if ($result) {
                if ($acao === 'aprovar') {
                    // Processar aprovação
                    if ($solicitacao['tipo_solicitacao'] === 'reagendamento') {
                        // Atualizar aula com nova data/hora
                        $db->query(
                            "UPDATE aulas SET data_aula = ?, hora_inicio = ?, hora_fim = ?, atualizado_em = NOW()
                             WHERE id = ?",
                            [$solicitacao['nova_data'], $solicitacao['nova_hora'], 
                             date('H:i:s', strtotime($solicitacao['nova_hora']) + 3000), $solicitacao['aula_id']]
                        );
                    } else if ($solicitacao['tipo_solicitacao'] === 'cancelamento') {
                        // Cancelar aula
                        $db->query(
                            "UPDATE aulas SET status = 'cancelada', atualizado_em = NOW() WHERE id = ?",
                            [$solicitacao['aula_id']]
                        );
                    }
                }
                
                // Notificar aluno
                $notificacoes->enviarNotificacao(
                    $solicitacao['aluno_id'],
                    'aluno',
                    'solicitacao_processada',
                    "Sua solicitação de {$solicitacao['tipo_solicitacao']} foi {$novo_status}",
                    [
                        'solicitacao_id' => $solicitacao_id,
                        'aula_id' => $solicitacao['aula_id'],
                        'tipo_solicitacao' => $solicitacao['tipo_solicitacao'],
                        'status' => $novo_status,
                        'motivo_decisao' => $motivo_decisao
                    ]
                );
                
                returnJsonSuccess(null, "Solicitação {$novo_status} com sucesso");
            } else {
                returnJsonError('Erro ao processar solicitação', 500);
            }
            break;

        default:
            returnJsonError('Método não permitido', 405);
    }

} catch (Exception $e) {
    error_log("Erro na API de solicitações: " . $e->getMessage());
    returnJsonError('Erro interno do servidor: ' . $e->getMessage(), 500);
}
?>
