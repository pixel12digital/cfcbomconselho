<?php
/**
 * API para gerenciamento de aulas por instrutores
 * Permite cancelamento e transferência de aulas
 * 
 * FASE 1 - Implementação: 2024
 * Arquivo: admin/api/instrutor-aulas.php
 * 
 * Segurança:
 * - Apenas usuários com tipo = 'instrutor' podem usar
 * - Valida se a aula pertence ao instrutor logado (aulas.instrutor_id = instrutor_atual)
 * - Registra motivo/justificativa em log de ações
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

function returnJsonSuccess($data = null, $message = 'Sucesso') {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

function returnJsonError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

try {
    // Verificar método OPTIONS (CORS)
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    // Verificar autenticação
    $user = getCurrentUser();
    if (!$user) {
        returnJsonError('Usuário não autenticado', 401);
    }

    // VALIDAÇÃO CRÍTICA: Apenas instrutores podem usar esta API
    if ($user['tipo'] !== 'instrutor') {
        returnJsonError('Acesso negado. Apenas instrutores podem usar esta API.', 403);
    }

    $db = db();

    // Buscar dados do instrutor na tabela instrutores
    $instrutor = $db->fetch("SELECT id FROM instrutores WHERE usuario_id = ?", [$user['id']]);
    if (!$instrutor) {
        returnJsonError('Instrutor não encontrado. Verifique seu cadastro.', 404);
    }
    $instrutorId = $instrutor['id'];

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            // Processar cancelamento ou transferência
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }

            // Validações obrigatórias
            if (empty($input['aula_id'])) {
                returnJsonError('ID da aula é obrigatório');
            }
            if (empty($input['tipo_acao'])) {
                returnJsonError('Tipo de ação é obrigatório (cancelamento ou transferencia)');
            }
            if (empty($input['justificativa'])) {
                returnJsonError('Justificativa é obrigatória');
            }

            $aulaId = (int)$input['aula_id'];
            $tipoAcao = $input['tipo_acao']; // 'cancelamento' ou 'transferencia'
            $justificativa = trim($input['justificativa']);
            $motivo = $input['motivo'] ?? null;
            $novaData = $input['nova_data'] ?? null;
            $novaHora = $input['nova_hora'] ?? null;

            // Validar tipo de ação
            if (!in_array($tipoAcao, ['cancelamento', 'transferencia'])) {
                returnJsonError('Tipo de ação inválido. Use "cancelamento" ou "transferencia"');
            }

            // VALIDAÇÃO CRÍTICA: Verificar se a aula pertence ao instrutor logado
            $aula = $db->fetch("
                SELECT a.*, 
                       al.nome as aluno_nome, al.telefone as aluno_telefone,
                       v.modelo as veiculo_modelo, v.placa as veiculo_placa
                FROM aulas a
                JOIN alunos al ON a.aluno_id = al.id
                LEFT JOIN veiculos v ON a.veiculo_id = v.id
                WHERE a.id = ? AND a.instrutor_id = ? AND a.status != 'cancelada'
            ", [$aulaId, $instrutorId]);

            if (!$aula) {
                returnJsonError('Aula não encontrada ou não pertence a você', 404);
            }

            // Validações específicas por tipo de ação
            if ($tipoAcao === 'transferencia') {
                if (empty($novaData)) {
                    returnJsonError('Nova data é obrigatória para transferência');
                }
                if (empty($novaHora)) {
                    returnJsonError('Novo horário é obrigatório para transferência');
                }

                // Validar formato de data
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $novaData)) {
                    returnJsonError('Formato de data inválido. Use YYYY-MM-DD');
                }

                // Validar que nova data não é no passado
                $dataNova = strtotime($novaData);
                $hoje = strtotime(date('Y-m-d'));
                if ($dataNova < $hoje) {
                    returnJsonError('A nova data não pode ser no passado');
                }

                // Validar conflito de horário (verificar se instrutor já tem aula no mesmo horário)
                $conflito = $db->fetch("
                    SELECT id FROM aulas 
                    WHERE instrutor_id = ? 
                      AND data_aula = ? 
                      AND hora_inicio = ? 
                      AND id != ? 
                      AND status != 'cancelada'
                ", [$instrutorId, $novaData, $novaHora, $aulaId]);

                if ($conflito) {
                    returnJsonError('Você já possui uma aula agendada neste horário');
                }
            }

            // Verificar se aula pode ser cancelada/transferida (regras de negócio)
            $dataAula = strtotime($aula['data_aula']);
            $horaAula = strtotime($aula['hora_inicio']);
            $agora = time();
            $tempoAteAula = ($dataAula + $horaAula) - $agora;
            $horasAteAula = $tempoAteAula / 3600;

            // Regra: mínimo 2 horas de antecedência
            if ($horasAteAula < 2 && $horasAteAula > 0) {
                returnJsonError('Ação só pode ser realizada com pelo menos 2 horas de antecedência');
            }

            // Verificar status da aula
            if ($aula['status'] === 'concluida') {
                returnJsonError('Aula já foi concluída e não pode ser alterada');
            }
            if ($aula['status'] === 'em_andamento') {
                returnJsonError('Aula em andamento não pode ser alterada');
            }

            // Processar ação
            if ($tipoAcao === 'cancelamento') {
                // Cancelar a aula
                $observacoesAtualizadas = ($aula['observacoes'] ?? '') . "\n\n[CANCELADA POR INSTRUTOR] " . date('d/m/Y H:i:s') . "\nMotivo: " . ($motivo ?? 'Não informado') . "\nJustificativa: " . $justificativa;
                
                $result = $db->query("
                    UPDATE aulas 
                    SET status = 'cancelada', 
                        observacoes = ?,
                        atualizado_em = NOW()
                    WHERE id = ? AND instrutor_id = ?
                ", [$observacoesAtualizadas, $aulaId, $instrutorId]);

                if (!$result) {
                    returnJsonError('Erro ao cancelar aula', 500);
                }

                // Log de auditoria
                if (defined('LOG_ENABLED') && LOG_ENABLED) {
                    error_log(sprintf(
                        '[INSTRUTOR_CANCELAR_AULA] instrutor_id=%d, usuario_id=%d, aula_id=%d, motivo=%s, timestamp=%s, ip=%s',
                        $instrutorId,
                        $user['id'],
                        $aulaId,
                        $motivo ?? 'não informado',
                        date('Y-m-d H:i:s'),
                        $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
                    ));
                }

                returnJsonSuccess([
                    'aula_id' => $aulaId,
                    'acao' => 'cancelamento',
                    'status' => 'cancelada'
                ], 'Aula cancelada com sucesso');

            } else if ($tipoAcao === 'transferencia') {
                // Transferir aula (atualizar data/hora)
                $observacoesAtualizadas = ($aula['observacoes'] ?? '') . "\n\n[TRANSFERIDA POR INSTRUTOR] " . date('d/m/Y H:i:s') . "\nData original: " . $aula['data_aula'] . " " . $aula['hora_inicio'] . "\nNova data: " . $novaData . " " . $novaHora . "\nMotivo: " . ($motivo ?? 'Não informado') . "\nJustificativa: " . $justificativa;
                
                $result = $db->query("
                    UPDATE aulas 
                    SET data_aula = ?,
                        hora_inicio = ?,
                        observacoes = ?,
                        atualizado_em = NOW()
                    WHERE id = ? AND instrutor_id = ?
                ", [$novaData, $novaHora, $observacoesAtualizadas, $aulaId, $instrutorId]);

                if (!$result) {
                    returnJsonError('Erro ao transferir aula', 500);
                }

                // Log de auditoria
                if (defined('LOG_ENABLED') && LOG_ENABLED) {
                    error_log(sprintf(
                        '[INSTRUTOR_TRANSFERIR_AULA] instrutor_id=%d, usuario_id=%d, aula_id=%d, data_original=%s, data_nova=%s, motivo=%s, timestamp=%s, ip=%s',
                        $instrutorId,
                        $user['id'],
                        $aulaId,
                        $aula['data_aula'] . ' ' . $aula['hora_inicio'],
                        $novaData . ' ' . $novaHora,
                        $motivo ?? 'não informado',
                        date('Y-m-d H:i:s'),
                        $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
                    ));
                }

                returnJsonSuccess([
                    'aula_id' => $aulaId,
                    'acao' => 'transferencia',
                    'data_original' => $aula['data_aula'],
                    'hora_original' => $aula['hora_inicio'],
                    'data_nova' => $novaData,
                    'hora_nova' => $novaHora
                ], 'Aula transferida com sucesso');
            }

            break;

        case 'GET':
            // Listar aulas do instrutor (opcional, para uso futuro)
            $dataInicio = $_GET['data_inicio'] ?? date('Y-m-d');
            $dataFim = $_GET['data_fim'] ?? date('Y-m-d', strtotime('+30 days'));
            $status = $_GET['status'] ?? null;

            $sql = "
                SELECT a.*, 
                       al.nome as aluno_nome, al.telefone as aluno_telefone,
                       v.modelo as veiculo_modelo, v.placa as veiculo_placa
                FROM aulas a
                JOIN alunos al ON a.aluno_id = al.id
                LEFT JOIN veiculos v ON a.veiculo_id = v.id
                WHERE a.instrutor_id = ?
                  AND a.data_aula >= ?
                  AND a.data_aula <= ?
            ";

            $params = [$instrutorId, $dataInicio, $dataFim];

            if ($status) {
                $sql .= " AND a.status = ?";
                $params[] = $status;
            }

            $sql .= " ORDER BY a.data_aula ASC, a.hora_inicio ASC";

            $aulas = $db->fetchAll($sql, $params);

            returnJsonSuccess($aulas, 'Aulas carregadas');
            break;

        default:
            returnJsonError('Método não permitido', 405);
    }

} catch (Exception $e) {
    error_log('Erro na API instrutor-aulas: ' . $e->getMessage());
    returnJsonError('Erro interno: ' . (DEBUG_MODE ? $e->getMessage() : 'Tente novamente mais tarde'), 500);
}
?>

