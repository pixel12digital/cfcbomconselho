<?php
/**
 * AgendamentoController - Controlador para o sistema de agendamento
 * Responsável por gerenciar aulas, verificar disponibilidade e validar conflitos
 * 
 * @author Sistema CFC
 * @version 1.0
 * @since 2024
 */

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../auth.php';

class AgendamentoController {
    private $db;
    private $auth;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->auth = new Auth();
    }
    
    /**
     * Criar nova aula
     * @param array $dados Dados da aula
     * @return array Resultado da operação
     */
    public function criarAula($dados) {
        try {
            // Validar dados obrigatórios
            $validacao = $this->validarDadosAula($dados);
            if (!$validacao['sucesso']) {
                return $validacao;
            }
            
            // Verificar disponibilidade
            $disponibilidade = $this->verificarDisponibilidade($dados);
            if (!$disponibilidade['disponivel']) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Conflito de horário detectado: ' . $disponibilidade['motivo'],
                    'tipo' => 'erro'
                ];
            }
            
            // Preparar dados para inserção
            $sql = "INSERT INTO aulas (aluno_id, instrutor_id, cfc_id, veiculo_id, tipo_aula, data_aula, 
                    hora_inicio, hora_fim, status, observacoes, criado_em) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $params = [
                $dados['aluno_id'],
                $dados['instrutor_id'],
                $dados['cfc_id'],
                $dados['veiculo_id'] ?? null,
                $dados['tipo_aula'],
                $dados['data_aula'],
                $dados['hora_inicio'],
                $dados['hora_fim'],
                'agendada',
                $dados['observacoes'] ?? ''
            ];
            
            $stmt = $this->db->prepare($sql);
            $resultado = $stmt->execute($params);
            
            if ($resultado) {
                $aulaId = $this->db->lastInsertId();
                
                // Log da operação
                $this->logOperacao('criar_aula', $aulaId, $dados);
                
                // Enviar notificação de confirmação
                $this->enviarNotificacaoConfirmacao($aulaId, $dados);
                
                return [
                    'sucesso' => true,
                    'mensagem' => 'Aula agendada com sucesso!',
                    'aula_id' => $aulaId,
                    'tipo' => 'sucesso'
                ];
            } else {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Erro ao criar aula. Tente novamente.',
                    'tipo' => 'erro'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Erro ao criar aula: " . $e->getMessage());
            return [
                'sucesso' => false,
                'mensagem' => 'Erro interno do sistema. Contate o suporte.',
                'tipo' => 'erro'
            ];
        }
    }
    
    /**
     * Atualizar aula existente
     * @param int $aulaId ID da aula
     * @param array $dados Novos dados da aula
     * @return array Resultado da operação
     */
    public function atualizarAula($aulaId, $dados) {
        try {
            // Verificar se a aula existe
            $aulaExistente = $this->buscarAula($aulaId);
            if (!$aulaExistente) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Aula não encontrada.',
                    'tipo' => 'erro'
                ];
            }
            
            // Validar dados
            $validacao = $this->validarDadosAula($dados);
            if (!$validacao['sucesso']) {
                return $validacao;
            }
            
            // Verificar disponibilidade (excluindo a aula atual)
            $disponibilidade = $this->verificarDisponibilidade($dados, $aulaId);
            if (!$disponibilidade['disponivel']) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Conflito de horário detectado: ' . $disponibilidade['motivo'],
                    'tipo' => 'erro'
                ];
            }
            
            // Atualizar aula
            $sql = "UPDATE aulas SET 
                    aluno_id = ?, instrutor_id = ?, cfc_id = ?, veiculo_id = ?, tipo_aula = ?, 
                    data_aula = ?, hora_inicio = ?, hora_fim = ?, 
                    observacoes = ?, atualizado_em = NOW() 
                    WHERE id = ?";
            
            $params = [
                $dados['aluno_id'],
                $dados['instrutor_id'],
                $dados['cfc_id'],
                $dados['veiculo_id'] ?? null,
                $dados['tipo_aula'],
                $dados['data_aula'],
                $dados['hora_inicio'],
                $dados['hora_fim'],
                $dados['observacoes'] ?? '',
                $aulaId
            ];
            
            $stmt = $this->db->prepare($sql);
            $resultado = $stmt->execute($params);
            
            if ($resultado) {
                // Log da operação
                $this->logOperacao('atualizar_aula', $aulaId, $dados);
                
                // Enviar notificação de alteração
                $this->enviarNotificacaoAlteracao($aulaId, $dados);
                
                return [
                    'sucesso' => true,
                    'mensagem' => 'Aula atualizada com sucesso!',
                    'tipo' => 'sucesso'
                ];
            } else {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Erro ao atualizar aula. Tente novamente.',
                    'tipo' => 'erro'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Erro ao atualizar aula: " . $e->getMessage());
            return [
                'sucesso' => false,
                'mensagem' => 'Erro interno do sistema. Contate o suporte.',
                'tipo' => 'erro'
            ];
        }
    }
    
    /**
     * Excluir aula
     * @param int $aulaId ID da aula
     * @return array Resultado da operação
     */
    public function excluirAula($aulaId) {
        try {
            // Verificar se a aula existe
            $aulaExistente = $this->buscarAula($aulaId);
            if (!$aulaExistente) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Aula não encontrada.',
                    'tipo' => 'erro'
                ];
            }
            
            // Verificar se pode ser excluída (não pode estar em andamento ou concluída)
            if (in_array($aulaExistente['status'], ['em_andamento', 'concluida'])) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Não é possível excluir uma aula em andamento ou concluída.',
                    'tipo' => 'erro'
                ];
            }
            
            // Excluir aula
            $sql = "DELETE FROM aulas WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $resultado = $stmt->execute([$aulaId]);
            
            if ($resultado) {
                // Log da operação
                $this->logOperacao('excluir_aula', $aulaId, $aulaExistente);
                
                // Enviar notificação de cancelamento
                $this->enviarNotificacaoCancelamento($aulaExistente);
                
                return [
                    'sucesso' => true,
                    'mensagem' => 'Aula excluída com sucesso!',
                    'tipo' => 'sucesso'
                ];
            } else {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Erro ao excluir aula. Tente novamente.',
                    'tipo' => 'erro'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Erro ao excluir aula: " . $e->getMessage());
            return [
                'sucesso' => false,
                'mensagem' => 'Erro interno do sistema. Contate o suporte.',
                'tipo' => 'erro'
            ];
        }
    }
    
    /**
     * Buscar aula por ID
     * @param int $aulaId ID da aula
     * @return array|null Dados da aula ou null se não encontrada
     */
    public function buscarAula($aulaId) {
        try {
            $sql = "SELECT a.*, 
                           al.nome as aluno_nome, al.email as aluno_email,
                           i.nome as instrutor_nome, i.email as instrutor_email,
                           c.nome as cfc_nome,
                           v.placa as veiculo_placa, v.modelo as veiculo_modelo, v.marca as veiculo_marca
                    FROM aulas a
                    JOIN alunos al ON a.aluno_id = al.id
                    JOIN instrutores i ON a.instrutor_id = i.id
                    JOIN cfcs c ON a.cfc_id = c.id
                    LEFT JOIN veiculos v ON a.veiculo_id = v.id
                    WHERE a.id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$aulaId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erro ao buscar aula: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Listar aulas com filtros
     * @param array $filtros Filtros de busca
     * @return array Lista de aulas
     */
    public function listarAulas($filtros = []) {
        try {
            $sql = "SELECT a.*, 
                           al.nome as aluno_nome, al.email as aluno_email,
                           i.nome as instrutor_nome, i.email as instrutor_email,
                           c.nome as cfc_nome,
                           v.placa as veiculo_placa, v.modelo as veiculo_modelo, v.marca as veiculo_marca
                    FROM aulas a
                    JOIN alunos al ON a.aluno_id = al.id
                    JOIN instrutores i ON a.instrutor_id = i.id
                    JOIN cfcs c ON a.cfc_id = c.id
                    LEFT JOIN veiculos v ON a.veiculo_id = v.id
                    WHERE 1=1";
            
            $params = [];
            
            // Aplicar filtros
            if (!empty($filtros['data_inicio'])) {
                $sql .= " AND a.data_aula >= ?";
                $params[] = $filtros['data_inicio'];
            }
            
            if (!empty($filtros['data_fim'])) {
                $sql .= " AND a.data_aula <= ?";
                $params[] = $filtros['data_fim'];
            }
            
            if (!empty($filtros['instrutor_id'])) {
                $sql .= " AND a.instrutor_id = ?";
                $params[] = $filtros['instrutor_id'];
            }
            
            if (!empty($filtros['aluno_id'])) {
                $sql .= " AND a.aluno_id = ?";
                $params[] = $filtros['aluno_id'];
            }
            
            if (!empty($filtros['tipo_aula'])) {
                $sql .= " AND a.tipo_aula = ?";
                $params[] = $filtros['tipo_aula'];
            }
            
            if (!empty($filtros['status'])) {
                $sql .= " AND a.status = ?";
                $params[] = $filtros['status'];
            }
            
            $sql .= " ORDER BY a.data_aula ASC, a.hora_inicio ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erro ao listar aulas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Verificar disponibilidade para agendamento
     * @param array $dados Dados da aula
     * @param int $aulaIdExcluir ID da aula a ser excluída da verificação (para edição)
     * @return array Resultado da verificação
     */
    public function verificarDisponibilidade($dados, $aulaIdExcluir = null) {
        try {
            $data = $dados['data_aula'];
            $horaInicio = $dados['hora_inicio'];
            $horaFim = $dados['hora_fim'];
            $instrutorId = $dados['instrutor_id'];
            $veiculoId = $dados['veiculo_id'] ?? null;
            
            // 1. Verificar duração da aula (deve ser exatamente 50 minutos)
            if (!$this->verificarDuracaoAula($horaInicio, $horaFim)) {
                return [
                    'disponivel' => false,
                    'motivo' => 'A aula deve ter exatamente 50 minutos de duração',
                    'tipo' => 'duracao'
                ];
            }
            
            // 2. Verificar limite diário de aulas do instrutor
            $limiteDiario = $this->verificarLimiteDiarioInstrutor($instrutorId, $data, $aulaIdExcluir);
            if (!$limiteDiario['disponivel']) {
                return $limiteDiario;
            }
            
            // 3. Verificar padrão de aulas e intervalos
            $padraoAulas = $this->verificarPadraoAulasInstrutor($instrutorId, $data, $horaInicio, $aulaIdExcluir);
            if (!$padraoAulas['disponivel']) {
                return $padraoAulas;
            }
            
            // 4. Verificar conflitos de instrutor
            $sqlInstrutor = "SELECT COUNT(*) as total FROM aulas 
                            WHERE instrutor_id = ? 
                            AND data_aula = ? 
                            AND status != 'cancelada'
                            AND ((hora_inicio <= ? AND hora_fim > ?) 
                                 OR (hora_inicio < ? AND hora_fim >= ?)
                                 OR (hora_inicio >= ? AND hora_fim <= ?))";
            
            $paramsInstrutor = [
                $instrutorId, $data, 
                $horaInicio, $horaInicio, 
                $horaFim, $horaFim, 
                $horaInicio, $horaFim
            ];
            
            if ($aulaIdExcluir) {
                $sqlInstrutor .= " AND id != ?";
                $paramsInstrutor[] = $aulaIdExcluir;
            }
            
            $stmtInstrutor = $this->db->query($sqlInstrutor, $paramsInstrutor);
            $conflitoInstrutor = $stmtInstrutor->fetch(PDO::FETCH_ASSOC);
            
            if ($conflitoInstrutor['total'] > 0) {
                return [
                    'disponivel' => false,
                    'motivo' => 'Instrutor já possui aula agendada neste horário',
                    'tipo' => 'instrutor'
                ];
            }
            
            // 5. Verificar conflitos de veículo (se especificado)
            if ($veiculoId) {
                $sqlVeiculo = "SELECT COUNT(*) as total FROM aulas 
                              WHERE veiculo_id = ? 
                              AND data_aula = ? 
                              AND status != 'cancelada'
                              AND ((hora_inicio <= ? AND hora_fim > ?) 
                                   OR (hora_inicio < ? AND hora_fim >= ?)
                                   OR (hora_inicio >= ? AND hora_fim <= ?))";
                
                $paramsVeiculo = [
                    $veiculoId, $data, 
                    $horaInicio, $horaInicio, 
                    $horaFim, $horaFim, 
                    $horaInicio, $horaFim
                ];
                
                if ($aulaIdExcluir) {
                    $sqlVeiculo .= " AND id != ?";
                    $paramsVeiculo[] = $aulaIdExcluir;
                }
                
                $stmtVeiculo = $this->db->query($sqlVeiculo, $paramsVeiculo);
                $conflitoVeiculo = $stmtVeiculo->fetch(PDO::FETCH_ASSOC);
                
                if ($conflitoVeiculo['total'] > 0) {
                    return [
                        'disponivel' => false,
                        'motivo' => 'Veículo já possui aula agendada neste horário',
                        'tipo' => 'veiculo'
                    ];
                }
            }
            
            // 6. Verificar se o horário está dentro do horário de funcionamento
            if (!$this->verificarHorarioFuncionamento($horaInicio, $horaFim)) {
                return [
                    'disponivel' => false,
                    'motivo' => 'Horário fora do período de funcionamento (7h às 22h)',
                    'tipo' => 'horario'
                ];
            }
            
            return [
                'disponivel' => true,
                'motivo' => 'Horário disponível para agendamento',
                'tipo' => 'disponivel'
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao verificar disponibilidade: " . $e->getMessage());
            return [
                'disponivel' => false,
                'motivo' => 'Erro ao verificar disponibilidade',
                'tipo' => 'erro'
            ];
        }
    }
    
    /**
     * Obter estatísticas de agendamento
     * @param array $filtros Filtros para as estatísticas
     * @return array Estatísticas
     */
    public function obterEstatisticas($filtros = []) {
        try {
            $estatisticas = [];
            
            // Total de aulas
            $sqlTotal = "SELECT COUNT(*) as total FROM aulas WHERE 1=1";
            $paramsTotal = [];
            
            if (!empty($filtros['data_inicio'])) {
                $sqlTotal .= " AND data_aula >= ?";
                $paramsTotal[] = $filtros['data_inicio'];
            }
            
            if (!empty($filtros['data_fim'])) {
                $sqlTotal .= " AND data_aula <= ?";
                $paramsTotal[] = $filtros['data_fim'];
            }
            
            $stmtTotal = $this->db->prepare($sqlTotal);
            $stmtTotal->execute($paramsTotal);
            $estatisticas['total_aulas'] = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Aulas por status
            $sqlStatus = "SELECT status, COUNT(*) as total FROM aulas WHERE 1=1";
            $paramsStatus = [];
            
            if (!empty($filtros['data_inicio'])) {
                $sqlStatus .= " AND data_aula >= ?";
                $paramsStatus[] = $filtros['data_inicio'];
            }
            
            if (!empty($filtros['data_fim'])) {
                $sqlStatus .= " AND data_aula <= ?";
                $paramsStatus[] = $filtros['data_fim'];
            }
            
            $sqlStatus .= " GROUP BY status";
            
            $stmtStatus = $this->db->prepare($sqlStatus);
            $stmtStatus->execute($paramsStatus);
            $estatisticas['por_status'] = $stmtStatus->fetchAll(PDO::FETCH_ASSOC);
            
            // Aulas por tipo
            $sqlTipo = "SELECT tipo_aula, COUNT(*) as total FROM aulas WHERE 1=1";
            $paramsTipo = [];
            
            if (!empty($filtros['data_inicio'])) {
                $sqlTipo .= " AND data_aula >= ?";
                $paramsTipo[] = $filtros['data_inicio'];
            }
            
            if (!empty($filtros['data_fim'])) {
                $sqlTipo .= " AND data_aula <= ?";
                $paramsTipo[] = $filtros['data_fim'];
            }
            
            $sqlTipo .= " GROUP BY tipo_aula";
            
            $stmtTipo = $this->db->prepare($sqlTipo);
            $stmtTipo->execute($paramsTipo);
            $estatisticas['por_tipo'] = $stmtTipo->fetchAll(PDO::FETCH_ASSOC);
            
            // Aulas da semana atual
            $inicioSemana = date('Y-m-d', strtotime('monday this week'));
            $fimSemana = date('Y-m-d', strtotime('sunday this week'));
            
            $sqlSemana = "SELECT COUNT(*) as total FROM aulas 
                          WHERE data_aula BETWEEN ? AND ?";
            $stmtSemana = $this->db->prepare($sqlSemana);
            $stmtSemana->execute([$inicioSemana, $fimSemana]);
            $estatisticas['aulas_semana'] = $stmtSemana->fetch(PDO::FETCH_ASSOC)['total'];
            
            return $estatisticas;
            
        } catch (Exception $e) {
            error_log("Erro ao obter estatísticas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Validar dados da aula
     * @param array $dados Dados a serem validados
     * @return array Resultado da validação
     */
    private function validarDadosAula($dados) {
        $erros = [];
        
        // Campos obrigatórios (hora_fim não é mais obrigatório, será calculada automaticamente)
        $camposObrigatorios = ['aluno_id', 'instrutor_id', 'cfc_id', 'tipo_aula', 'data_aula', 'hora_inicio'];
        
        foreach ($camposObrigatorios as $campo) {
            if (empty($dados[$campo])) {
                $erros[] = "Campo '$campo' é obrigatório";
            }
        }
        
        if (!empty($erros)) {
            return [
                'sucesso' => false,
                'mensagem' => 'Dados inválidos: ' . implode(', ', $erros),
                'erros' => $erros,
                'tipo' => 'erro'
            ];
        }
        
        // Calcular hora_fim automaticamente se não fornecida (50 minutos de duração)
        if (empty($dados['hora_fim'])) {
            $horaInicio = strtotime($dados['hora_inicio']);
            $horaFim = $horaInicio + (50 * 60); // 50 minutos em segundos
            $dados['hora_fim'] = date('H:i:s', $horaFim);
        }
        
        // Validar data
        $dataAula = strtotime($dados['data_aula']);
        $hoje = strtotime(date('Y-m-d'));
        
        if ($dataAula < $hoje) {
            $erros[] = "Data da aula não pode ser no passado";
        }
        
        // Validar horários
        $horaInicio = strtotime($dados['hora_inicio']);
        $horaFim = strtotime($dados['hora_fim']);
        
        if ($horaInicio >= $horaFim) {
            $erros[] = "Hora de início deve ser menor que hora de fim";
        }
        
        // Validar duração (deve ser exatamente 50 minutos)
        $duracao = ($horaFim - $horaInicio) / 60; // Duração em minutos
        if ($duracao != 50) {
            $erros[] = "A aula deve ter exatamente 50 minutos de duração";
        }
        
        // Validar tipo de aula
        $tiposValidos = ['teorica', 'pratica'];
        if (!in_array($dados['tipo_aula'], $tiposValidos)) {
            $erros[] = "Tipo de aula inválido";
        }
        
        if (!empty($erros)) {
            return [
                'sucesso' => false,
                'mensagem' => 'Dados inválidos: ' . implode(', ', $erros),
                'erros' => $erros,
                'tipo' => 'erro'
            ];
        }
        
        return ['sucesso' => true];
    }
    
    /**
     * Verificar duração da aula (deve ser exatamente 50 minutos)
     * @param string $horaInicio Hora de início
     * @param string $horaFim Hora de fim
     * @return bool True se a duração for exatamente 50 minutos
     */
    private function verificarDuracaoAula($horaInicio, $horaFim) {
        $inicio = strtotime($horaInicio);
        $fim = strtotime($horaFim);
        $duracao = ($fim - $inicio) / 60; // Duração em minutos
        
        return $duracao == 50; // Exatamente 50 minutos
    }
    
    /**
     * Verificar limite diário de aulas do instrutor (máximo 3 por dia)
     * @param int $instrutorId ID do instrutor
     * @param string $data Data da aula
     * @param int $aulaIdExcluir ID da aula a ser excluída da contagem
     * @return array Resultado da verificação
     */
    private function verificarLimiteDiarioInstrutor($instrutorId, $data, $aulaIdExcluir = null) {
        try {
            // Buscar informações do instrutor incluindo horário de trabalho
            $sqlInstrutor = "SELECT i.*, u.nome FROM instrutores i LEFT JOIN usuarios u ON i.usuario_id = u.id WHERE i.id = ?";
            $stmtInstrutor = $this->db->query($sqlInstrutor, [$instrutorId]);
            $instrutor = $stmtInstrutor->fetch(PDO::FETCH_ASSOC);
            
            if (!$instrutor) {
                return [
                    'disponivel' => false,
                    'motivo' => 'Instrutor não encontrado',
                    'tipo' => 'instrutor_nao_encontrado'
                ];
            }
            
            // Verificar se o instrutor tem horário de trabalho configurado
            $horario_inicio = $instrutor['horario_inicio'] ?? '08:00';
            $horario_fim = $instrutor['horario_fim'] ?? '18:00';
            
            // Converter horários para minutos para facilitar cálculos
            $inicio_minutos = $this->horaParaMinutos($horario_inicio);
            $fim_minutos = $this->horaParaMinutos($horario_fim);
            $duracao_total_minutos = $fim_minutos - $inicio_minutos;
            
            // Calcular quantas aulas de 50 minutos cabem no horário de trabalho
            $max_aulas_possiveis = floor($duracao_total_minutos / 50);
            
            // Buscar aulas já agendadas para o dia
            $sql = "SELECT COUNT(*) as total FROM aulas 
                    WHERE instrutor_id = ? 
                    AND data_aula = ? 
                    AND status != 'cancelada'";
            
            $params = [$instrutorId, $data];
            
            if ($aulaIdExcluir) {
                $sql .= " AND id != ?";
                $params[] = $aulaIdExcluir;
            }
            
            $stmt = $this->db->query($sql, $params);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalAulas = $resultado['total'];
            
            if ($totalAulas >= $max_aulas_possiveis) {
                return [
                    'disponivel' => false,
                    'motivo' => "Instrutor já possui {$totalAulas} aulas agendadas para este dia. Máximo possível dentro do horário de trabalho ({$horario_inicio} às {$horario_fim}): {$max_aulas_possiveis} aulas.",
                    'tipo' => 'limite_diario',
                    'horario_trabalho' => "{$horario_inicio} às {$horario_fim}",
                    'max_aulas' => $max_aulas_possiveis
                ];
            }
            
            return [
                'disponivel' => true,
                'horario_trabalho' => "{$horario_inicio} às {$horario_fim}",
                'max_aulas' => $max_aulas_possiveis,
                'aulas_restantes' => $max_aulas_possiveis - $totalAulas
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao verificar limite diário: " . $e->getMessage());
            return [
                'disponivel' => false,
                'motivo' => 'Erro ao verificar limite diário de aulas',
                'tipo' => 'erro'
            ];
        }
    }
    
    /**
     * Verificar padrão de aulas e intervalos do instrutor
     * @param int $instrutorId ID do instrutor
     * @param string $data Data da aula
     * @param string $horaInicio Hora de início da nova aula
     * @param int $aulaIdExcluir ID da aula a ser excluída da verificação
     * @return array Resultado da verificação
     */
    private function verificarPadraoAulasInstrutor($instrutorId, $data, $horaInicio, $aulaIdExcluir = null) {
        try {
            // Buscar todas as aulas do instrutor na data
            $sql = "SELECT hora_inicio, hora_fim FROM aulas 
                    WHERE instrutor_id = ? 
                    AND data_aula = ? 
                    AND status != 'cancelada'
                    ORDER BY hora_inicio ASC";
            
            $params = [$instrutorId, $data];
            
            if ($aulaIdExcluir) {
                $sql .= " AND id != ?";
                $params[] = $aulaIdExcluir;
            }
            
            $stmt = $this->db->query($sql, $params);
            $aulasExistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($aulasExistentes)) {
                return ['disponivel' => true]; // Primeira aula do dia
            }
            
            // Converter horários para minutos desde meia-noite
            $horarios = [];
            foreach ($aulasExistentes as $aula) {
                $horarios[] = [
                    'inicio' => $this->horaParaMinutos($aula['hora_inicio']),
                    'fim' => $this->horaParaMinutos($aula['hora_fim'])
                ];
            }
            
            $novaAulaInicio = $this->horaParaMinutos($horaInicio);
            $novaAulaFim = $novaAulaInicio + 50; // 50 minutos de duração
            
            // Verificar se a nova aula respeita os padrões
            if (!$this->verificarPadraoAulas($horarios, $novaAulaInicio, $novaAulaFim)) {
                return [
                    'disponivel' => false,
                    'motivo' => 'A nova aula não respeita o padrão de aulas e intervalos. ' .
                                'Padrão: 2 aulas consecutivas + 30 min intervalo + 1 aula, ' .
                                'ou 1 aula + 30 min intervalo + 2 aulas consecutivas',
                    'tipo' => 'padrao_aulas'
                ];
            }
            
            return ['disponivel' => true];
            
        } catch (Exception $e) {
            error_log("Erro ao verificar padrão de aulas: " . $e->getMessage());
            return [
                'disponivel' => false,
                'motivo' => 'Erro ao verificar padrão de aulas',
                'tipo' => 'erro'
            ];
        }
    }
    
    /**
     * Verificar se a nova aula respeita o padrão de aulas e intervalos
     * @param array $horarios Array de horários existentes
     * @param int $novaInicio Início da nova aula em minutos
     * @param int $novaFim Fim da nova aula em minutos
     * @return bool True se respeita o padrão
     */
    private function verificarPadraoAulas($horarios, $novaInicio, $novaFim) {
        // Adicionar a nova aula aos horários existentes
        $todosHorarios = array_merge($horarios, [['inicio' => $novaInicio, 'fim' => $novaFim]]);
        
        // Ordenar por horário de início
        usort($todosHorarios, function($a, $b) {
            return $a['inicio'] - $b['inicio'];
        });
        
        // Verificar se há mais de 3 aulas
        if (count($todosHorarios) > 3) {
            return false;
        }
        
        // Se há apenas 1 aula, é válido
        if (count($todosHorarios) == 1) {
            return true;
        }
        
        // Se há 2 aulas, verificar se são consecutivas
        if (count($todosHorarios) == 2) {
            $aula1 = $todosHorarios[0];
            $aula2 = $todosHorarios[1];
            
            // Verificar se são consecutivas (sem intervalo)
            if ($aula1['fim'] == $aula2['inicio']) {
                return true;
            }
            
            // Verificar se há intervalo de 30 minutos
            if (($aula2['inicio'] - $aula1['fim']) == 30) {
                return true;
            }
            
            return false;
        }
        
        // Se há 3 aulas, verificar o padrão
        if (count($todosHorarios) == 3) {
            $aula1 = $todosHorarios[0];
            $aula2 = $todosHorarios[1];
            $aula3 = $todosHorarios[2];
            
            // Padrão 1: 2 consecutivas + 30 min + 1
            if ($aula1['fim'] == $aula2['inicio'] && 
                ($aula3['inicio'] - $aula2['fim']) == 30) {
                return true;
            }
            
            // Padrão 2: 1 + 30 min + 2 consecutivas
            if (($aula2['inicio'] - $aula1['fim']) == 30 && 
                $aula2['fim'] == $aula3['inicio']) {
                return true;
            }
            
            return false;
        }
        
        return false;
    }
    
    /**
     * Converter hora (HH:MM) para minutos desde meia-noite
     * @param string $hora Hora no formato HH:MM
     * @return int Minutos desde meia-noite
     */
    private function horaParaMinutos($hora) {
        $partes = explode(':', $hora);
        return (int)$partes[0] * 60 + (int)$partes[1];
    }
    
    /**
     * Verificar horário de funcionamento
     * @param string $horaInicio Hora de início
     * @param string $horaFim Hora de fim
     * @return bool True se dentro do horário de funcionamento
     */
    private function verificarHorarioFuncionamento($horaInicio, $horaFim) {
        $horaInicioInt = (int) str_replace(':', '', $horaInicio);
        $horaFimInt = (int) str_replace(':', '', $horaFim);
        
        // Horário de funcionamento: 7h às 22h
        $horaMinima = 700; // 7:00
        $horaMaxima = 2200; // 22:00
        
        return $horaInicioInt >= $horaMinima && $horaFimInt <= $horaMaxima;
    }
    
    /**
     * Log de operações
     * @param string $acao Ação realizada
     * @param int $aulaId ID da aula
     * @param array $dados Dados da operação
     */
    private function logOperacao($acao, $aulaId, $dados) {
        try {
            $usuarioId = $this->auth->getUserId() ?? 0;
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            
            $sql = "INSERT INTO logs (usuario_id, acao, tabela, registro_id, dados, ip, criado_em) 
                    VALUES (?, ?, 'aulas', ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $usuarioId,
                $acao,
                $aulaId,
                json_encode($dados),
                $ip
            ]);
            
        } catch (Exception $e) {
            error_log("Erro ao registrar log: " . $e->getMessage());
        }
    }
    
    /**
     * Enviar notificação de confirmação
     * @param int $aulaId ID da aula
     * @param array $dados Dados da aula
     */
    private function enviarNotificacaoConfirmacao($aulaId, $dados) {
        // TODO: Implementar envio de e-mail de confirmação
        // Por enquanto, apenas log
        error_log("Notificação de confirmação enviada para aula ID: $aulaId");
    }
    
    /**
     * Enviar notificação de alteração
     * @param int $aulaId ID da aula
     * @param array $dados Dados da aula
     */
    private function enviarNotificacaoAlteracao($aulaId, $dados) {
        // TODO: Implementar envio de e-mail de alteração
        // Por enquanto, apenas log
        error_log("Notificação de alteração enviada para aula ID: $aulaId");
    }
    
    /**
     * Enviar notificação de cancelamento
     * @param array $dados Dados da aula cancelada
     */
    private function enviarNotificacaoCancelamento($dados) {
        // TODO: Implementar envio de e-mail de cancelamento
        // Por enquanto, apenas log
        error_log("Notificação de cancelamento enviada para aula ID: " . $dados['id']);
    }
}
?>
