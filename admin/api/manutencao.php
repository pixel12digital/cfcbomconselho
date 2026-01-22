<?php
/**
 * API para gerenciamento de manutenções de veículos
 * Sistema CFC - Bom Conselho
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
    
    // Get method and action
    $method = $_SERVER['REQUEST_METHOD'];
    // If it's a POST request, but _method is set to PUT or DELETE, override the method
    if ($method === 'POST' && isset($_POST['_method'])) {
        $method = strtoupper($_POST['_method']);
        error_log('DEBUG API - Method overridden to: ' . $method);
    }
    
    $acao = $_POST['acao'] ?? $_GET['acao'] ?? '';
    
    // Debug: Log da requisição
    error_log('DEBUG API - Method: ' . $method . ', Action: ' . $acao . ', GET: ' . json_encode($_GET) . ', POST: ' . json_encode($_POST));
    
    // Verificar autenticação (temporariamente desabilitado para debug)
    // if (!AuthService::isAuthenticated()) {
    //     throw new Exception('Usuário não autenticado');
    // }
    
    switch ($method) {
        case 'GET':
            handleGet($db);
            break;
        case 'POST':
            handlePost($db);
            break;
        case 'PUT': // This case will now be hit if _method was 'PUT'
            handlePut($db);
            break;
        case 'DELETE':
            handleDelete($db);
            break;
        default:
            throw new Exception('Método não permitido');
    }
    
} catch (Exception $e) {
    // Log do erro para debug
    error_log('ERRO API Manutenção: ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE);
}

function handleGet($db) {
    $veiculoId = $_GET['veiculo_id'] ?? null;
    $id = $_GET['id'] ?? null;
    
    // Debug: Log dos parâmetros GET
    error_log('DEBUG handleGet - veiculoId: ' . $veiculoId . ', id: ' . $id);
    
    if ($id) {
        // Buscar manutenção específica
        $manutencao = $db->fetch("
            SELECT m.*, v.marca, v.modelo, v.placa, c.nome as cfc_nome
            FROM manutencoes m
            LEFT JOIN veiculos v ON m.veiculo_id = v.id
            LEFT JOIN cfcs c ON v.cfc_id = c.id
            WHERE m.id = ?
        ", [$id]);
        
        // Debug: Log do resultado
        error_log('DEBUG handleGet - Resultado da busca: ' . json_encode($manutencao));
        
        if ($manutencao) {
            echo json_encode([
                'success' => true,
                'data' => $manutencao
            ]);
        } else {
            throw new Exception('Manutenção não encontrada');
        }
    } elseif ($veiculoId) {
        // Buscar manutenções de um veículo
        $manutencoes = $db->fetchAll("
            SELECT m.*, v.marca, v.modelo, v.placa, c.nome as cfc_nome
            FROM manutencoes m
            LEFT JOIN veiculos v ON m.veiculo_id = v.id
            LEFT JOIN cfcs c ON v.cfc_id = c.id
            WHERE m.veiculo_id = ?
            ORDER BY m.data_manutencao DESC, m.hora_inicio DESC
        ", [$veiculoId]);
        
        echo json_encode([
            'success' => true,
            'data' => $manutencoes
        ]);
    } else {
        // Buscar todas as manutenções
        $manutencoes = $db->fetchAll("
            SELECT m.*, v.marca, v.modelo, v.placa, c.nome as cfc_nome
            FROM manutencoes m
            LEFT JOIN veiculos v ON m.veiculo_id = v.id
            LEFT JOIN cfcs c ON v.cfc_id = c.id
            ORDER BY m.data_manutencao DESC, m.hora_inicio DESC
        ");
        
        echo json_encode([
            'success' => true,
            'data' => $manutencoes
        ]);
    }
}

function handlePost($db) {
    $acao = $_POST['acao'] ?? '';
    
    switch ($acao) {
        case 'agendar':
            agendarManutencao($db);
            break;
        case 'editar':
            editarManutencao($db);
            break;
        case 'concluir':
            concluirManutencao($db);
            break;
        default:
            throw new Exception('Ação não especificada');
    }
}

function agendarManutencao($db) {
    // Validar dados obrigatórios
    $camposObrigatorios = ['veiculo_id', 'tipo_manutencao', 'data_manutencao', 'hora_inicio', 'hora_fim'];
    foreach ($camposObrigatorios as $campo) {
        if (empty($_POST[$campo])) {
            throw new Exception("Campo obrigatório: $campo");
        }
    }
    
    $veiculoId = (int)$_POST['veiculo_id'];
    $tipoManutencao = trim($_POST['tipo_manutencao']);
    $dataManutencao = $_POST['data_manutencao'];
    $horaInicio = $_POST['hora_inicio'];
    $horaFim = $_POST['hora_fim'];
    $quilometragemAtual = !empty($_POST['quilometragem_atual']) ? (int)$_POST['quilometragem_atual'] : null;
    $custoEstimado = !empty($_POST['custo_estimado']) ? str_replace(['.', ','], ['', '.'], $_POST['custo_estimado']) : null;
    $observacoes = trim($_POST['observacoes'] ?? '');
    $alterarStatus = isset($_POST['alterar_status']) && $_POST['alterar_status'] === '1';
    
    // Verificar se o veículo existe
    $veiculo = $db->fetch("SELECT * FROM veiculos WHERE id = ?", [$veiculoId]);
    if (!$veiculo) {
        throw new Exception('Veículo não encontrado');
    }
    
    // Validar datas e horários
    $dataAtual = date('Y-m-d');
    if ($dataManutencao < $dataAtual) {
        throw new Exception('A data da manutenção não pode ser anterior à data atual');
    }
    
    if ($horaInicio >= $horaFim) {
        throw new Exception('A hora de término deve ser posterior à hora de início');
    }
    
    // Iniciar transação
    $db->beginTransaction();
    
    try {
        // Inserir manutenção
        $manutencaoData = [
            'veiculo_id' => $veiculoId,
            'tipo_manutencao' => $tipoManutencao,
            'data_manutencao' => $dataManutencao,
            'hora_inicio' => $horaInicio,
            'hora_fim' => $horaFim,
            'quilometragem_atual' => $quilometragemAtual,
            'custo_estimado' => $custoEstimado,
            'observacoes' => $observacoes,
            'status' => 'agendada'
        ];
        
        $manutencaoId = $db->insert('manutencoes', $manutencaoData);
        
        // Alterar status do veículo se solicitado
        if ($alterarStatus) {
            $db->update('veiculos', ['status' => 'manutencao'], 'id = ?', [$veiculoId]);
        }
        
        // Atualizar quilometragem do veículo se informada
        if ($quilometragemAtual) {
            $db->update('veiculos', ['quilometragem' => $quilometragemAtual], 'id = ?', [$veiculoId]);
        }
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Manutenção agendada com sucesso!',
            'data' => ['id' => $manutencaoId]
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}

function editarManutencao($db) {
    // Debug: Log dos dados recebidos
    error_log('DEBUG editarManutencao - POST data: ' . json_encode($_POST));
    
    // Validar dados obrigatórios (mais flexível)
    if (empty($_POST['manutencao_id'])) {
        throw new Exception("ID da manutenção é obrigatório");
    }
    
    $manutencaoId = (int)$_POST['manutencao_id'];
    $tipoManutencao = trim($_POST['tipo_manutencao'] ?? '');
    $dataManutencao = $_POST['data_manutencao'] ?? '';
    $horaInicio = $_POST['hora_inicio'] ?? '';
    $horaFim = $_POST['hora_fim'] ?? '';
    $quilometragemAtual = !empty($_POST['quilometragem_atual']) ? (int)$_POST['quilometragem_atual'] : null;
    $custoEstimado = !empty($_POST['custo_estimado']) ? str_replace(['.', ','], ['', '.'], $_POST['custo_estimado']) : null;
    $observacoes = trim($_POST['observacoes'] ?? '');
    $alterarStatus = isset($_POST['alterar_status']) && $_POST['alterar_status'] === '1';
    
    // Debug: Log dos dados processados
    error_log('DEBUG editarManutencao - Dados processados: ' . json_encode([
        'manutencaoId' => $manutencaoId,
        'tipoManutencao' => $tipoManutencao,
        'dataManutencao' => $dataManutencao,
        'horaInicio' => $horaInicio,
        'horaFim' => $horaFim,
        'quilometragemAtual' => $quilometragemAtual,
        'custoEstimado' => $custoEstimado,
        'observacoes' => $observacoes,
        'alterarStatus' => $alterarStatus
    ]));
    
    // Verificar se a manutenção existe
    $manutencao = $db->fetch("SELECT * FROM manutencoes WHERE id = ?", [$manutencaoId]);
    if (!$manutencao) {
        throw new Exception('Manutenção não encontrada');
    }
    
    // Verificar se pode ser editada (apenas agendadas)
    if ($manutencao['status'] !== 'agendada') {
        throw new Exception('Apenas manutenções agendadas podem ser editadas');
    }
    
    // Validar datas e horários
    $dataAtual = date('Y-m-d');
    if ($dataManutencao < $dataAtual) {
        throw new Exception('A data da manutenção não pode ser anterior à data atual');
    }
    
    if ($horaInicio >= $horaFim) {
        throw new Exception('A hora de término deve ser posterior à hora de início');
    }
    
    // Iniciar transação
    $db->beginTransaction();
    
    try {
        // Atualizar manutenção
        $manutencaoData = [
            'tipo_manutencao' => $tipoManutencao,
            'data_manutencao' => $dataManutencao,
            'hora_inicio' => $horaInicio,
            'hora_fim' => $horaFim,
            'quilometragem_atual' => $quilometragemAtual,
            'custo_estimado' => $custoEstimado,
            'observacoes' => $observacoes
        ];
        
        // Debug: Log dos dados para update
        error_log('DEBUG editarManutencao - Dados para update: ' . json_encode($manutencaoData));
        
        $db->update('manutencoes', $manutencaoData, 'id = ?', [$manutencaoId]);
        
        // Alterar status do veículo se solicitado
        if ($alterarStatus) {
            $db->update('veiculos', ['status' => 'manutencao'], 'id = ?', [$manutencao['veiculo_id']]);
        }
        
        // Atualizar quilometragem do veículo se informada
        if ($quilometragemAtual) {
            $db->update('veiculos', ['quilometragem' => $quilometragemAtual], 'id = ?', [$manutencao['veiculo_id']]);
        }
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Manutenção atualizada com sucesso!',
            'data' => ['id' => $manutencaoId]
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}

function concluirManutencao($db) {
    $id = (int)($_POST['id'] ?? 0);
    $custoReal = !empty($_POST['custo_real']) ? str_replace(['.', ','], ['', '.'], $_POST['custo_real']) : null;
    $observacoesFinais = trim($_POST['observacoes_finais'] ?? '');
    
    if (!$id) {
        throw new Exception('ID da manutenção não informado');
    }
    
    // Buscar manutenção
    $manutencao = $db->fetch("SELECT * FROM manutencoes WHERE id = ?", [$id]);
    if (!$manutencao) {
        throw new Exception('Manutenção não encontrada');
    }
    
    if ($manutencao['status'] === 'concluida') {
        throw new Exception('Manutenção já foi concluída');
    }
    
    // Iniciar transação
    $db->beginTransaction();
    
    try {
        // Atualizar manutenção
        $db->update('manutencoes', [
            'status' => 'concluida',
            'custo_real' => $custoReal,
            'observacoes_finais' => $observacoesFinais,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$id]);
        
        // Verificar se há outras manutenções pendentes para o veículo
        $manutencoesPendentes = $db->fetch("
            SELECT COUNT(*) as total 
            FROM manutencoes 
            WHERE veiculo_id = ? AND status IN ('agendada', 'em_andamento')
        ", [$manutencao['veiculo_id']]);
        
        // Se não há manutenções pendentes, voltar veículo para ativo
        if ($manutencoesPendentes['total'] == 0) {
            $db->update('veiculos', ['status' => 'ativo'], 'id = ?', [$manutencao['veiculo_id']]);
        }
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Manutenção concluída com sucesso!'
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}

function handlePut($db) {
    // Debug: Log dos dados recebidos
    error_log('DEBUG handlePut - POST data: ' . json_encode($_POST));
    
    // Para PUT requests simulados via POST com _method, $_POST já está populado
    // Então podemos chamar diretamente editarManutencao
    editarManutencao($db);
}

function handleDelete($db) {
    // Debug: Log dos dados recebidos
    error_log('DEBUG handleDelete - POST data: ' . json_encode($_POST));
    
    // Para DELETE requests simulados via POST com _method, usar $_POST
    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    
    if (!$id) {
        throw new Exception('ID da manutenção não informado');
    }
    
    // Buscar manutenção
    $manutencao = $db->fetch("SELECT * FROM manutencoes WHERE id = ?", [$id]);
    if (!$manutencao) {
        throw new Exception('Manutenção não encontrada');
    }
    
    // Verificar se pode ser excluída (apenas agendadas)
    if ($manutencao['status'] !== 'agendada') {
        throw new Exception('Apenas manutenções agendadas podem ser excluídas');
    }
    
    // Excluir manutenção
    $db->delete('manutencoes', 'id = ?', [$id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Manutenção excluída com sucesso!'
    ], JSON_UNESCAPED_UNICODE);
}
?>
