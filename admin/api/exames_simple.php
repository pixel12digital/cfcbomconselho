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
function returnJson($data, $httpStatus = 200) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Se houver http_status no array de dados ou parâmetro, usar
    if (isset($data['http_status'])) {
        $httpStatus = $data['http_status'];
        unset($data['http_status']); // Remover do JSON
    }
    
    http_response_code($httpStatus);
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
                // =====================================================
                // INVESTIGAÇÃO: DE ONDE VEM O CAMPO TIPO
                // =====================================================
                // O campo 'tipo' vem do formulário HTML em admin/pages/exames.php
                // Campo hidden: input type="hidden" name="tipo" id="tipo_exame" value com $tipo
                // O valor de $tipo é normalizado em exames.php a partir da URL: $_GET['tipo']
                // Valores possíveis: 'medico', 'psicotecnico', 'teorico', 'pratico'
                // =====================================================
                
                // Agendar exame
                // Validar e normalizar tipo PRIMEIRO (antes de verificar required)
                $tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : '';
                $tiposValidos = ['medico', 'psicotecnico', 'teorico', 'pratico'];
                
                if (empty($tipo) || !in_array($tipo, $tiposValidos, true)) {
                    returnJson([
                        'success' => false,
                        'error' => 'Tipo de exame/prova inválido. Tipos permitidos: ' . implode(', ', $tiposValidos),
                        'codigo' => 'TIPO_INVALIDO',
                        'tipo_recebido' => $tipo
                    ]);
                }
                
                // Garantir que o tipo está em minúsculo e é um dos valores válidos
                $tipo = strtolower(trim($tipo));
                if (!in_array($tipo, $tiposValidos, true)) {
                    returnJson([
                        'success' => false,
                        'error' => 'Tipo de exame/prova inválido. Tipos permitidos: ' . implode(', ', $tiposValidos),
                        'codigo' => 'TIPO_INVALIDO',
                        'tipo_recebido' => $tipo
                    ]);
                }
                
                // Validar campos obrigatórios (exceto tipo, já validado)
                $required = ['aluno_id', 'data_agendada'];
                foreach ($required as $field) {
                    if (empty($_POST[$field])) {
                        returnJson([
                            'success' => false,
                            'error' => "Campo '$field' é obrigatório",
                            'codigo' => 'CAMPO_OBRIGATORIO'
                        ]);
                    }
                }
                
                // Validar aluno_id
                $alunoId = (int)$_POST['aluno_id'];
                $aluno = $db->fetch("SELECT id FROM alunos WHERE id = ?", [$alunoId]);
                if (!$aluno) {
                    returnJson([
                        'success' => false,
                        'error' => 'Aluno não encontrado',
                        'codigo' => 'ALUNO_NAO_ENCONTRADO'
                    ]);
                }
                
                // =====================================================
                // VERIFICAÇÃO DE BLOQUEIO FINANCEIRO
                // =====================================================
                // FUNÇÃO CENTRAL: FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno()
                // 
                // Esta é a mesma função usada no histórico do aluno.
                // Garante que a validação financeira seja consistente em ambos os lugares.
                // 
                // REGRA PARA EXAMES:
                // - Bloquear se não houver nenhuma fatura lançada
                // - Bloquear se existir qualquer fatura em atraso
                // - Permitir se houver pelo menos uma fatura PAGA e não houver faturas em atraso
                // - Faturas ABERTAS com vencimento futuro NÃO bloqueiam
                // =====================================================
                require_once __DIR__ . '/../includes/FinanceiroAlunoHelper.php';
                $verificacaoFinanceira = FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno($alunoId);
                
                error_log('[BLOQUEIO FINANCEIRO EXAMES] Aluno ' . $alunoId . 
                         ' - Liberado: ' . ($verificacaoFinanceira['liberado'] ? 'SIM' : 'NÃO') . 
                         ' - Status: ' . $verificacaoFinanceira['status'] . 
                         ' - Origem: Tela de Exames');
                
                if (!$verificacaoFinanceira['liberado']) {
                    returnJson([
                        'success' => false,
                        'error' => $verificacaoFinanceira['motivo'],
                        'codigo' => 'BLOQUEIO_FINANCEIRO',
                        'status_financeiro' => $verificacaoFinanceira['status'],
                        'http_status' => 403
                    ]);
                }
                
                // Preparar hora_agendada
                $horaAgendada = isset($_POST['hora_agendada']) ? trim($_POST['hora_agendada']) : null;
                if ($horaAgendada === '') {
                    $horaAgendada = null;
                }
                
                // =====================================================
                // VALIDAÇÃO: BLOQUEAR DATAS/HORÁRIOS RETROATIVOS
                // =====================================================
                // Validar que a data do exame não é anterior à data atual
                // Regra simples: comparar apenas a data (ignora hora por enquanto)
                $dataHoje = date('Y-m-d');
                $dataAgendada = $_POST['data_agendada'];
                
                if ($dataAgendada < $dataHoje) {
                    returnJson([
                        'success' => false,
                        'error' => 'Não é permitido agendar exame em data anterior à data atual.',
                        'codigo' => 'DATA_RETROATIVA',
                        'http_status' => 422
                    ]);
                }
                
                // Validação adicional: se tiver hora, verificar também o horário completo
                if ($horaAgendada) {
                    $agora = new DateTimeImmutable();
                    // Montar string de data+hora no formato correto
                    if (strlen($horaAgendada) === 5) { // Formato HH:MM
                        $dataExameStr = $dataAgendada . ' ' . $horaAgendada . ':00';
                    } else {
                        $dataExameStr = $dataAgendada . ' ' . $horaAgendada;
                    }
                    
                    $dataExame = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dataExameStr);
                    
                    if (!$dataExame) {
                        $dataExame = DateTimeImmutable::createFromFormat('Y-m-d H:i', $dataAgendada . ' ' . $horaAgendada);
                    }
                    
                    if ($dataExame && $dataExame < $agora) {
                        returnJson([
                            'success' => false,
                            'error' => 'Não é permitido agendar exame em data/horário já passados.',
                            'codigo' => 'DATA_RETROATIVA',
                            'http_status' => 422
                        ]);
                    }
                }
                
                // =====================================================
                // ARRAY DE DADOS PARA INSERT NA TABELA exames
                // =====================================================
                // Este é o array que será inserido no banco de dados
                // IMPORTANTE: O campo 'tipo' vem de $_POST['tipo'] validado acima
                // O valor é um dos: 'medico', 'psicotecnico', 'teorico', 'pratico'
                // =====================================================
                $exameData = [
                    'aluno_id' => $alunoId,
                    'tipo' => $tipo, // ✅ VALOR VALIDADO: 'medico', 'psicotecnico', 'teorico' ou 'pratico' (minúsculo)
                    'status' => 'agendado',
                    'resultado' => 'pendente',
                    'clinica_nome' => !empty($_POST['clinica_nome']) ? trim($_POST['clinica_nome']) : null,
                    'protocolo' => !empty($_POST['protocolo']) ? trim($_POST['protocolo']) : null,
                    'data_agendada' => $_POST['data_agendada'],
                    'observacoes' => !empty($_POST['observacoes']) ? trim($_POST['observacoes']) : null,
                    'criado_por' => $user['id']
                ];
                
                // Adicionar hora_agendada se não for null
                if ($horaAgendada !== null) {
                    $exameData['hora_agendada'] = $horaAgendada;
                }
                
                try {
                    $exameId = $db->insert('exames', $exameData);
                    
                    if ($exameId) {
                        returnJson([
                            'success' => true,
                            'message' => 'Exame agendado com sucesso',
                            'exame_id' => $exameId
                        ]);
                    } else {
                        returnJson([
                            'success' => false,
                            'error' => 'Erro ao agendar exame no banco de dados',
                            'codigo' => 'ERRO_INSERCAO'
                        ]);
                    }
                } catch (Exception $e) {
                    // Verificar se o erro é por coluna inexistente (hora_agendada)
                    $erroMsg = $e->getMessage();
                    if (strpos($erroMsg, 'hora_agendada') !== false || strpos($erroMsg, 'Unknown column') !== false) {
                        // Tentar novamente sem hora_agendada
                        unset($exameData['hora_agendada']);
                        
                        try {
                            $exameId = $db->insert('exames', $exameData);
                            if ($exameId) {
                                returnJson([
                                    'success' => true,
                                    'message' => 'Exame agendado com sucesso (sem horário - coluna não existe)',
                                    'exame_id' => $exameId,
                                    'aviso' => 'A coluna hora_agendada não existe na tabela. Execute a migration add_hora_agendada_exames.sql'
                                ]);
                            }
                        } catch (Exception $e2) {
                            // Erro na segunda tentativa
                        }
                    }
                    
                    returnJson([
                        'success' => false,
                        'error' => 'Erro ao salvar exame: ' . $erroMsg,
                        'codigo' => 'ERRO_BD',
                        'debug' => $erroMsg // Em DEV, incluir mensagem técnica
                    ]);
                }
                break;
                
            case 'update':
                // Atualizar exame
                $exameId = $_POST['exame_id'] ?? null;
                if (!$exameId) {
                    returnJson([
                        'success' => false,
                        'error' => 'ID do exame é obrigatório',
                        'codigo' => 'ID_OBRIGATORIO'
                    ]);
                }
                
                $updateData = [
                    'atualizado_por' => $user['id']
                ];
                
                $allowedFields = ['aluno_id', 'tipo', 'data_agendada', 'hora_agendada', 'clinica_nome', 'protocolo', 'observacoes', 'status', 'resultado', 'data_resultado'];
                foreach ($allowedFields as $field) {
                    if (isset($_POST[$field]) && $_POST[$field] !== '') {
                        $updateData[$field] = $_POST[$field];
                    }
                }
                
                // Validar tipo se estiver sendo alterado
                if (isset($updateData['tipo'])) {
                    $tipoUpdate = trim($updateData['tipo']);
                    $tiposValidos = ['medico', 'psicotecnico', 'teorico', 'pratico'];
                    
                    if (!in_array($tipoUpdate, $tiposValidos, true)) {
                        returnJson([
                            'success' => false,
                            'error' => 'Tipo de exame/prova inválido. Tipos permitidos: ' . implode(', ', $tiposValidos),
                            'codigo' => 'TIPO_INVALIDO'
                        ]);
                    }
                    
                    $updateData['tipo'] = $tipoUpdate;
                }
                
                // VALIDAÇÃO: Bloquear datas/horários retroativos (se data ou hora estiver sendo alterada)
                if (isset($updateData['data_agendada'])) {
                    $dataAgendadaUpdate = $updateData['data_agendada'];
                    $dataHoje = date('Y-m-d');
                    
                    // Validação simples: data não pode ser anterior à hoje
                    if ($dataAgendadaUpdate < $dataHoje) {
                        returnJson([
                            'success' => false,
                            'error' => 'Não é permitido agendar exame em data anterior à data atual.',
                            'codigo' => 'DATA_RETROATIVA',
                            'http_status' => 422
                        ]);
                    }
                    
                    // Se tiver hora, validar também o horário completo
                    $horaAgendadaUpdate = isset($updateData['hora_agendada']) ? trim($updateData['hora_agendada']) : null;
                    if ($horaAgendadaUpdate === '') {
                        $horaAgendadaUpdate = null;
                    }
                    
                    if ($horaAgendadaUpdate) {
                        $agora = new DateTimeImmutable();
                        $dataExameStr = $dataAgendadaUpdate . ' ' . ($horaAgendadaUpdate . (strlen($horaAgendadaUpdate) === 5 ? ':00' : ''));
                        $dataExame = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dataExameStr);
                        
                        if (!$dataExame) {
                            $dataExame = DateTimeImmutable::createFromFormat('Y-m-d H:i', $dataAgendadaUpdate . ' ' . $horaAgendadaUpdate);
                        }
                        
                        if ($dataExame && $dataExame < $agora) {
                            returnJson([
                                'success' => false,
                                'error' => 'Não é permitido agendar exames com data/horário já passados.',
                                'codigo' => 'DATA_RETROATIVA',
                                'http_status' => 422
                            ]);
                        }
                    }
                }
                
                // Se alterando resultado, definir status
                if (isset($updateData['resultado'])) {
                    // Resultados que indicam conclusão do exame/prova
                    if (in_array($updateData['resultado'], ['apto', 'inapto', 'aprovado', 'reprovado'])) {
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
    // Retornar JSON amigável em vez de HTTP 500 genérico
    returnJson([
        'success' => false,
        'error' => 'Erro interno ao processar requisição',
        'codigo' => 'ERRO_INTERNO',
        'debug' => $e->getMessage() // Em DEV, incluir mensagem técnica
    ]);
}
?>
