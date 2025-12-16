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
 * 
 * AÇÕES SUPORTADAS ATUALMENTE:
 * - cancelamento: Cancela uma aula prática (requer justificativa)
 * - transferencia: Transfere uma aula prática para outra data/hora (requer justificativa, nova_data, nova_hora)
 * 
 * AÇÕES QUE SERÃO ADICIONADAS (Tarefa 2.2 - Fase 2):
 * - iniciar: Inicia uma aula prática (status 'agendada' → 'em_andamento', registra inicio_at e km_inicial)
 * - finalizar: Finaliza uma aula prática (status 'em_andamento' → 'concluida', registra fim_at e km_final)
 * 
 * NOTA: Requer migration 999-add-campos-km-timestamps-aulas.sql para colunas:
 * - km_inicial INT NULL
 * - km_final INT NULL  
 * - inicio_at TIMESTAMP NULL
 * - fim_at TIMESTAMP NULL
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

    // FASE 2 - Correção: Usar função centralizada getCurrentInstrutorId()
    // Arquivo: admin/api/instrutor-aulas.php (linha ~61)
    // Mesma lógica, mas agora usando função reutilizável
    $instrutorId = getCurrentInstrutorId($user['id']);
    if (!$instrutorId) {
        // Log detalhado para diagnóstico
        if (defined('LOG_ENABLED') && LOG_ENABLED) {
            error_log(sprintf(
                '[INSTRUTOR_AULAS_API] Instrutor não encontrado - usuario_id=%d, tipo=%s, email=%s, timestamp=%s, ip=%s',
                $user['id'],
                $user['tipo'] ?? 'não definido',
                $user['email'] ?? 'não definido',
                date('Y-m-d H:i:s'),
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ));
        }
        returnJsonError('Instrutor não encontrado. Verifique seu cadastro.', 404);
    }

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
                returnJsonError('Tipo de ação é obrigatório');
            }

            $aulaId = (int)$input['aula_id'];
            $tipoAcao = $input['tipo_acao']; // 'cancelamento', 'transferencia', 'iniciar' ou 'finalizar'
            $justificativa = isset($input['justificativa']) ? trim($input['justificativa']) : null;
            $motivo = $input['motivo'] ?? null;
            $novaData = $input['nova_data'] ?? null;
            $novaHora = $input['nova_hora'] ?? null;

            // Validar tipo de ação
            if (!in_array($tipoAcao, ['cancelamento', 'transferencia', 'iniciar', 'finalizar'])) {
                returnJsonError('Tipo de ação inválido. Use "cancelamento", "transferencia", "iniciar" ou "finalizar"');
            }
            
            // Justificativa é obrigatória apenas para cancelamento e transferência
            if (in_array($tipoAcao, ['cancelamento', 'transferencia']) && empty($justificativa)) {
                returnJsonError('Justificativa é obrigatória para ' . $tipoAcao);
            }

            // VALIDAÇÃO CRÍTICA: Verificar se a aula pertence ao instrutor logado
            // Para cancelamento/transferência: excluir canceladas
            // Para iniciar/finalizar: pode estar em qualquer status (será validado depois)
            $whereStatus = "a.status != 'cancelada'";
            if (in_array($tipoAcao, ['iniciar', 'finalizar'])) {
                // Para iniciar/finalizar, permitir buscar mesmo se estiver cancelada (validação de status será feita depois)
                $whereStatus = "1=1"; // Não filtrar por status aqui
            }
            
            $aula = $db->fetch("
                SELECT a.*, 
                       al.nome as aluno_nome, al.telefone as aluno_telefone,
                       v.modelo as veiculo_modelo, v.placa as veiculo_placa
                FROM aulas a
                JOIN alunos al ON a.aluno_id = al.id
                LEFT JOIN veiculos v ON a.veiculo_id = v.id
                WHERE a.id = ? AND a.instrutor_id = ? AND $whereStatus
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

            // Validações de status específicas por tipo de ação
            if ($tipoAcao === 'cancelamento' || $tipoAcao === 'transferencia') {
                // Para cancelamento e transferência, validar status como antes
                if ($aula['status'] === 'concluida') {
                    returnJsonError('Aula já foi concluída e não pode ser alterada');
                }
                if ($aula['status'] === 'em_andamento') {
                    returnJsonError('Aula em andamento não pode ser alterada');
                }
            } elseif ($tipoAcao === 'iniciar') {
                // Para iniciar, a aula deve estar agendada
                if ($aula['status'] !== 'agendada') {
                    returnJsonError('Apenas aulas agendadas podem ser iniciadas');
                }
            } elseif ($tipoAcao === 'finalizar') {
                // Para finalizar, a aula deve estar em andamento
                if ($aula['status'] !== 'em_andamento') {
                    returnJsonError('Apenas aulas em andamento podem ser finalizadas');
                }
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

            } else if ($tipoAcao === 'iniciar') {
                // TAREFA 2.2 - Iniciar aula prática
                // Validar que é aula prática (KM só para práticas)
                if (!isset($aula['tipo_aula']) || $aula['tipo_aula'] !== 'pratica') {
                    returnJsonError('Apenas aulas práticas podem ser iniciadas');
                }

                // Verificar se colunas necessárias existem (proteção contra migration não aplicada)
                $checkColumns = $db->fetchAll("SHOW COLUMNS FROM aulas WHERE Field IN ('inicio_at', 'km_inicial')");
                $columnsFound = array_map(function($col) {
                    return $col['Field'];
                }, $checkColumns);
                
                if (!in_array('inicio_at', $columnsFound) || !in_array('km_inicial', $columnsFound)) {
                    returnJsonError('Estrutura do banco incompleta. Execute a migration: admin/migrations/999-add-campos-km-timestamps-aulas.sql', 500);
                }

                // Exigir km_inicial para aulas práticas
                if (empty($input['km_inicial']) || !is_numeric($input['km_inicial'])) {
                    returnJsonError('KM inicial é obrigatório para aulas práticas');
                }

                $kmInicial = (int)$input['km_inicial'];
                if ($kmInicial < 0) {
                    returnJsonError('KM inicial deve ser um número positivo ou zero');
                }

                // Preparar observações (append de log)
                $observacoesAtualizadas = ($aula['observacoes'] ?? '') . "\n\n[INICIADA POR INSTRUTOR] " . date('d/m/Y H:i:s') . "\nKM Inicial: " . $kmInicial . " km";
                
                // Atualizar: status, inicio_at (timestamp real), km_inicial
                // Anti-bug: WHERE com status='agendada' para evitar clique duplo/race condition
                $result = $db->query("
                    UPDATE aulas 
                    SET status = 'em_andamento', 
                        inicio_at = NOW(),
                        km_inicial = ?,
                        observacoes = ?,
                        atualizado_em = NOW()
                    WHERE id = ? 
                      AND instrutor_id = ? 
                      AND status = 'agendada'
                ", [$kmInicial, $observacoesAtualizadas, $aulaId, $instrutorId]);

                if (!$result) {
                    returnJsonError('Erro ao iniciar aula. Verifique se a aula ainda está agendada.', 500);
                }

                // Verificar se realmente atualizou (proteção contra status já alterado)
                $aulaAtualizada = $db->fetch("SELECT id, status FROM aulas WHERE id = ?", [$aulaId]);
                if (!$aulaAtualizada || $aulaAtualizada['status'] !== 'em_andamento') {
                    returnJsonError('Aula não pôde ser iniciada. Status atual: ' . ($aulaAtualizada['status'] ?? 'desconhecido'), 409);
                }

                // Log de auditoria
                if (defined('LOG_ENABLED') && LOG_ENABLED) {
                    error_log(sprintf(
                        '[INSTRUTOR_INICIAR_AULA] instrutor_id=%d, usuario_id=%d, aula_id=%d, km_inicial=%d, timestamp=%s, ip=%s',
                        $instrutorId,
                        $user['id'],
                        $aulaId,
                        $kmInicial,
                        date('Y-m-d H:i:s'),
                        $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
                    ));
                }

                returnJsonSuccess([
                    'aula_id' => $aulaId,
                    'acao' => 'iniciar',
                    'status' => 'em_andamento',
                    'km_inicial' => $kmInicial,
                    'inicio_at' => date('Y-m-d H:i:s')
                ], 'Aula iniciada com sucesso');

            } else if ($tipoAcao === 'finalizar') {
                // TAREFA 2.2 - Finalizar aula prática
                // Validar que é aula prática (KM só para práticas)
                if (!isset($aula['tipo_aula']) || $aula['tipo_aula'] !== 'pratica') {
                    returnJsonError('Apenas aulas práticas podem ser finalizadas');
                }

                // Verificar se colunas necessárias existem (proteção contra migration não aplicada)
                $checkColumns = $db->fetchAll("SHOW COLUMNS FROM aulas WHERE Field IN ('fim_at', 'km_final', 'km_inicial')");
                $columnsFound = array_map(function($col) {
                    return $col['Field'];
                }, $checkColumns);
                
                if (!in_array('fim_at', $columnsFound) || !in_array('km_final', $columnsFound) || !in_array('km_inicial', $columnsFound)) {
                    returnJsonError('Estrutura do banco incompleta. Execute a migration: admin/migrations/999-add-campos-km-timestamps-aulas.sql', 500);
                }

                // Exigir km_final para aulas práticas
                if (empty($input['km_final']) || !is_numeric($input['km_final'])) {
                    returnJsonError('KM final é obrigatório para aulas práticas');
                }

                $kmFinal = (int)$input['km_final'];
                if ($kmFinal < 0) {
                    returnJsonError('KM final deve ser um número positivo ou zero');
                }

                // Validar km_final >= km_inicial (se km_inicial existir)
                if (isset($aula['km_inicial']) && $aula['km_inicial'] !== null) {
                    if ($kmFinal < $aula['km_inicial']) {
                        returnJsonError('KM final (' . $kmFinal . ' km) não pode ser menor que KM inicial (' . $aula['km_inicial'] . ' km)');
                    }
                }

                // Preparar observações (append de log)
                $observacoesAtualizadas = ($aula['observacoes'] ?? '') . "\n\n[FINALIZADA POR INSTRUTOR] " . date('d/m/Y H:i:s') . "\nKM Final: " . $kmFinal . " km";
                if (isset($aula['km_inicial']) && $aula['km_inicial'] !== null) {
                    $kmRodados = $kmFinal - $aula['km_inicial'];
                    $observacoesAtualizadas .= " (Rodados: " . $kmRodados . " km)";
                }
                
                // Atualizar: status, fim_at (timestamp real), km_final
                // Anti-bug: WHERE com status='em_andamento' para evitar clique duplo/race condition
                $result = $db->query("
                    UPDATE aulas 
                    SET status = 'concluida', 
                        fim_at = NOW(),
                        km_final = ?,
                        observacoes = ?,
                        atualizado_em = NOW()
                    WHERE id = ? 
                      AND instrutor_id = ? 
                      AND status = 'em_andamento'
                ", [$kmFinal, $observacoesAtualizadas, $aulaId, $instrutorId]);

                if (!$result) {
                    returnJsonError('Erro ao finalizar aula. Verifique se a aula ainda está em andamento.', 500);
                }

                // Verificar se realmente atualizou (proteção contra status já alterado)
                $aulaAtualizada = $db->fetch("SELECT id, status FROM aulas WHERE id = ?", [$aulaId]);
                if (!$aulaAtualizada || $aulaAtualizada['status'] !== 'concluida') {
                    returnJsonError('Aula não pôde ser finalizada. Status atual: ' . ($aulaAtualizada['status'] ?? 'desconhecido'), 409);
                }

                // Log de auditoria
                if (defined('LOG_ENABLED') && LOG_ENABLED) {
                    error_log(sprintf(
                        '[INSTRUTOR_FINALIZAR_AULA] instrutor_id=%d, usuario_id=%d, aula_id=%d, km_final=%d, km_inicial=%s, timestamp=%s, ip=%s',
                        $instrutorId,
                        $user['id'],
                        $aulaId,
                        $kmFinal,
                        $aula['km_inicial'] ?? 'NULL',
                        date('Y-m-d H:i:s'),
                        $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
                    ));
                }

                returnJsonSuccess([
                    'aula_id' => $aulaId,
                    'acao' => 'finalizar',
                    'status' => 'concluida',
                    'km_final' => $kmFinal,
                    'km_inicial' => $aula['km_inicial'] ?? null,
                    'fim_at' => date('Y-m-d H:i:s')
                ], 'Aula finalizada com sucesso');
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

