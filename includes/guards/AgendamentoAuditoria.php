<?php
/**
 * AgendamentoAuditoria - Sistema de auditoria para agendamentos
 * Registra todas as ações críticas: criar/alterar/cancelar/transferir
 * 
 * @author Sistema CFC
 * @version 1.0
 * @since 2024
 */

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../auth.php';

class AgendamentoAuditoria {
    private $db;
    private $auth;
    
    public function __construct() {
        $this->db = db();
        $this->auth = new Auth();
    }
    
    /**
     * Registrar criação de agendamento
     * @param int $aulaId ID da aula criada
     * @param array $dadosAula Dados da aula
     * @return bool Sucesso da operação
     */
    public function registrarCriacao($aulaId, $dadosAula) {
        try {
            $usuarioId = $this->auth->getCurrentUser()['id'] ?? 0;
            $ip = $this->obterIP();
            
            $dadosJson = json_encode([
                'aluno_id' => $dadosAula['aluno_id'],
                'instrutor_id' => $dadosAula['instrutor_id'],
                'tipo_aula' => $dadosAula['tipo_aula'],
                'data_aula' => $dadosAula['data_aula'],
                'hora_inicio' => $dadosAula['hora_inicio'],
                'hora_fim' => $dadosAula['hora_fim'],
                'veiculo_id' => $dadosAula['veiculo_id'] ?? null,
                'disciplina' => $dadosAula['disciplina'] ?? null,
                'observacoes' => $dadosAula['observacoes'] ?? null
            ]);
            
            $sql = "INSERT INTO logs (usuario_id, acao, tabela, registro_id, dados_anteriores, dados_novos, ip_address, criado_em) 
                    VALUES (?, 'CREATE', 'aulas', ?, NULL, ?, ?, NOW())";
            
            $resultado = $this->db->query($sql, [$usuarioId, $aulaId, $dadosJson, $ip]);
            
            if ($resultado) {
                error_log("Auditoria: Aula {$aulaId} criada pelo usuário {$usuarioId}");
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Erro ao registrar auditoria de criação: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registrar edição de agendamento
     * @param int $aulaId ID da aula editada
     * @param array $dadosAnteriores Dados anteriores da aula
     * @param array $dadosNovos Novos dados da aula
     * @return bool Sucesso da operação
     */
    public function registrarEdicao($aulaId, $dadosAnteriores, $dadosNovos) {
        try {
            $usuarioId = $this->auth->getCurrentUser()['id'] ?? 0;
            $ip = $this->obterIP();
            
            $dadosAnterioresJson = json_encode($dadosAnteriores);
            $dadosNovosJson = json_encode($dadosNovos);
            
            $sql = "INSERT INTO logs (usuario_id, acao, tabela, registro_id, dados_anteriores, dados_novos, ip_address, criado_em) 
                    VALUES (?, 'UPDATE', 'aulas', ?, ?, ?, ?, NOW())";
            
            $resultado = $this->db->query($sql, [$usuarioId, $aulaId, $dadosAnterioresJson, $dadosNovosJson, $ip]);
            
            if ($resultado) {
                error_log("Auditoria: Aula {$aulaId} editada pelo usuário {$usuarioId}");
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Erro ao registrar auditoria de edição: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registrar cancelamento de agendamento
     * @param int $aulaId ID da aula cancelada
     * @param array $dadosAula Dados da aula cancelada
     * @param string $motivo Motivo do cancelamento
     * @return bool Sucesso da operação
     */
    public function registrarCancelamento($aulaId, $dadosAula, $motivo = '') {
        try {
            $usuarioId = $this->auth->getCurrentUser()['id'] ?? 0;
            $ip = $this->obterIP();
            
            $dadosJson = json_encode([
                'aluno_id' => $dadosAula['aluno_id'],
                'instrutor_id' => $dadosAula['instrutor_id'],
                'tipo_aula' => $dadosAula['tipo_aula'],
                'data_aula' => $dadosAula['data_aula'],
                'hora_inicio' => $dadosAula['hora_inicio'],
                'hora_fim' => $dadosAula['hora_fim'],
                'motivo_cancelamento' => $motivo
            ]);
            
            $sql = "INSERT INTO logs (usuario_id, acao, tabela, registro_id, dados_anteriores, dados_novos, ip_address, criado_em) 
                    VALUES (?, 'CANCEL', 'aulas', ?, ?, ?, ?, NOW())";
            
            $resultado = $this->db->query($sql, [$usuarioId, $aulaId, $dadosJson, $dadosJson, $ip]);
            
            if ($resultado) {
                error_log("Auditoria: Aula {$aulaId} cancelada pelo usuário {$usuarioId}. Motivo: {$motivo}");
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Erro ao registrar auditoria de cancelamento: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registrar transferência de agendamento
     * @param int $aulaId ID da aula transferida
     * @param array $dadosAnteriores Dados anteriores da aula
     * @param array $dadosNovos Novos dados da aula
     * @param string $motivo Motivo da transferência
     * @return bool Sucesso da operação
     */
    public function registrarTransferencia($aulaId, $dadosAnteriores, $dadosNovos, $motivo = '') {
        try {
            $usuarioId = $this->auth->getCurrentUser()['id'] ?? 0;
            $ip = $this->obterIP();
            
            $dadosAnterioresJson = json_encode($dadosAnteriores);
            $dadosNovosJson = json_encode(array_merge($dadosNovos, ['motivo_transferencia' => $motivo]));
            
            $sql = "INSERT INTO logs (usuario_id, acao, tabela, registro_id, dados_anteriores, dados_novos, ip_address, criado_em) 
                    VALUES (?, 'TRANSFER', 'aulas', ?, ?, ?, ?, NOW())";
            
            $resultado = $this->db->query($sql, [$usuarioId, $aulaId, $dadosAnterioresJson, $dadosNovosJson, $ip]);
            
            if ($resultado) {
                error_log("Auditoria: Aula {$aulaId} transferida pelo usuário {$usuarioId}. Motivo: {$motivo}");
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Erro ao registrar auditoria de transferência: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registrar solicitação de reagendamento
     * @param int $aulaId ID da aula
     * @param array $dadosAula Dados da aula
     * @param string $justificativa Justificativa do reagendamento
     * @return bool Sucesso da operação
     */
    public function registrarSolicitacaoReagendamento($aulaId, $dadosAula, $justificativa = '') {
        try {
            $usuarioId = $this->auth->getCurrentUser()['id'] ?? 0;
            $ip = $this->obterIP();
            
            $dadosJson = json_encode([
                'aluno_id' => $dadosAula['aluno_id'],
                'instrutor_id' => $dadosAula['instrutor_id'],
                'tipo_aula' => $dadosAula['tipo_aula'],
                'data_aula' => $dadosAula['data_aula'],
                'hora_inicio' => $dadosAula['hora_inicio'],
                'hora_fim' => $dadosAula['hora_fim'],
                'justificativa_reagendamento' => $justificativa,
                'status_solicitacao' => 'pendente'
            ]);
            
            $sql = "INSERT INTO logs (usuario_id, acao, tabela, registro_id, dados_anteriores, dados_novos, ip_address, criado_em) 
                    VALUES (?, 'REQUEST_RESCHEDULE', 'aulas', ?, ?, ?, ?, NOW())";
            
            $resultado = $this->db->query($sql, [$usuarioId, $aulaId, $dadosJson, $dadosJson, $ip]);
            
            if ($resultado) {
                error_log("Auditoria: Solicitação de reagendamento para aula {$aulaId} pelo usuário {$usuarioId}. Justificativa: {$justificativa}");
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Erro ao registrar auditoria de solicitação de reagendamento: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registrar solicitação de cancelamento
     * @param int $aulaId ID da aula
     * @param array $dadosAula Dados da aula
     * @param string $justificativa Justificativa do cancelamento
     * @return bool Sucesso da operação
     */
    public function registrarSolicitacaoCancelamento($aulaId, $dadosAula, $justificativa = '') {
        try {
            $usuarioId = $this->auth->getCurrentUser()['id'] ?? 0;
            $ip = $this->obterIP();
            
            $dadosJson = json_encode([
                'aluno_id' => $dadosAula['aluno_id'],
                'instrutor_id' => $dadosAula['instrutor_id'],
                'tipo_aula' => $dadosAula['tipo_aula'],
                'data_aula' => $dadosAula['data_aula'],
                'hora_inicio' => $dadosAula['hora_inicio'],
                'hora_fim' => $dadosAula['hora_fim'],
                'justificativa_cancelamento' => $justificativa,
                'status_solicitacao' => 'pendente'
            ]);
            
            $sql = "INSERT INTO logs (usuario_id, acao, tabela, registro_id, dados_anteriores, dados_novos, ip_address, criado_em) 
                    VALUES (?, 'REQUEST_CANCEL', 'aulas', ?, ?, ?, ?, NOW())";
            
            $resultado = $this->db->query($sql, [$usuarioId, $aulaId, $dadosJson, $dadosJson, $ip]);
            
            if ($resultado) {
                error_log("Auditoria: Solicitação de cancelamento para aula {$aulaId} pelo usuário {$usuarioId}. Justificativa: {$justificativa}");
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Erro ao registrar auditoria de solicitação de cancelamento: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registrar aprovação/negação de solicitação
     * @param int $aulaId ID da aula
     * @param string $tipoSolicitacao Tipo da solicitação (reagendamento/cancelamento)
     * @param string $status Status da aprovação (aprovado/negado)
     * @param string $motivo Motivo da decisão
     * @return bool Sucesso da operação
     */
    public function registrarAprovacaoSolicitacao($aulaId, $tipoSolicitacao, $status, $motivo = '') {
        try {
            $usuarioId = $this->auth->getCurrentUser()['id'] ?? 0;
            $ip = $this->obterIP();
            
            $dadosJson = json_encode([
                'tipo_solicitacao' => $tipoSolicitacao,
                'status_aprovacao' => $status,
                'motivo_decisao' => $motivo,
                'aprovado_por' => $usuarioId
            ]);
            
            $acao = $status === 'aprovado' ? 'APPROVE_REQUEST' : 'DENY_REQUEST';
            
            $sql = "INSERT INTO logs (usuario_id, acao, tabela, registro_id, dados_anteriores, dados_novos, ip_address, criado_em) 
                    VALUES (?, ?, 'aulas', ?, ?, ?, ?, NOW())";
            
            $resultado = $this->db->query($sql, [$usuarioId, $acao, $aulaId, $dadosJson, $dadosJson, $ip]);
            
            if ($resultado) {
                error_log("Auditoria: Solicitação {$tipoSolicitacao} para aula {$aulaId} {$status} pelo usuário {$usuarioId}. Motivo: {$motivo}");
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Erro ao registrar auditoria de aprovação: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registrar ocorrência durante aula
     * @param int $aulaId ID da aula
     * @param array $dadosAula Dados da aula
     * @param string $tipoOcorrencia Tipo da ocorrência
     * @param string $descricao Descrição da ocorrência
     * @return bool Sucesso da operação
     */
    public function registrarOcorrencia($aulaId, $dadosAula, $tipoOcorrencia, $descricao = '') {
        try {
            $usuarioId = $this->auth->getCurrentUser()['id'] ?? 0;
            $ip = $this->obterIP();
            
            $dadosJson = json_encode([
                'aluno_id' => $dadosAula['aluno_id'],
                'instrutor_id' => $dadosAula['instrutor_id'],
                'tipo_aula' => $dadosAula['tipo_aula'],
                'data_aula' => $dadosAula['data_aula'],
                'hora_inicio' => $dadosAula['hora_inicio'],
                'hora_fim' => $dadosAula['hora_fim'],
                'tipo_ocorrencia' => $tipoOcorrencia,
                'descricao_ocorrencia' => $descricao
            ]);
            
            $sql = "INSERT INTO logs (usuario_id, acao, tabela, registro_id, dados_anteriores, dados_novos, ip_address, criado_em) 
                    VALUES (?, 'INCIDENT', 'aulas', ?, ?, ?, ?, NOW())";
            
            $resultado = $this->db->query($sql, [$usuarioId, $aulaId, $dadosJson, $dadosJson, $ip]);
            
            if ($resultado) {
                error_log("Auditoria: Ocorrência registrada para aula {$aulaId} pelo usuário {$usuarioId}. Tipo: {$tipoOcorrencia}");
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Erro ao registrar auditoria de ocorrência: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Buscar histórico de auditoria de uma aula
     * @param int $aulaId ID da aula
     * @return array Histórico de auditoria
     */
    public function buscarHistoricoAula($aulaId) {
        try {
            $sql = "SELECT l.*, u.nome as usuario_nome, u.email as usuario_email
                    FROM logs l
                    LEFT JOIN usuarios u ON l.usuario_id = u.id
                    WHERE l.tabela = 'aulas' AND l.registro_id = ?
                    ORDER BY l.criado_em DESC";
            
            $resultado = $this->db->fetchAll($sql, [$aulaId]);
            
            return $resultado ?: [];
            
        } catch (Exception $e) {
            error_log("Erro ao buscar histórico de auditoria: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Buscar auditoria por período
     * @param string $dataInicio Data de início
     * @param string $dataFim Data de fim
     * @param string $acao Ação específica (opcional)
     * @return array Histórico de auditoria
     */
    public function buscarAuditoriaPorPeriodo($dataInicio, $dataFim, $acao = null) {
        try {
            $sql = "SELECT l.*, u.nome as usuario_nome, u.email as usuario_email
                    FROM logs l
                    LEFT JOIN usuarios u ON l.usuario_id = u.id
                    WHERE l.tabela = 'aulas' 
                    AND DATE(l.criado_em) BETWEEN ? AND ?";
            
            $params = [$dataInicio, $dataFim];
            
            if ($acao) {
                $sql .= " AND l.acao = ?";
                $params[] = $acao;
            }
            
            $sql .= " ORDER BY l.criado_em DESC";
            
            $resultado = $this->db->fetchAll($sql, $params);
            
            return $resultado ?: [];
            
        } catch (Exception $e) {
            error_log("Erro ao buscar auditoria por período: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Buscar auditoria por usuário
     * @param int $usuarioId ID do usuário
     * @param string $dataInicio Data de início (opcional)
     * @param string $dataFim Data de fim (opcional)
     * @return array Histórico de auditoria
     */
    public function buscarAuditoriaPorUsuario($usuarioId, $dataInicio = null, $dataFim = null) {
        try {
            $sql = "SELECT l.*, u.nome as usuario_nome, u.email as usuario_email
                    FROM logs l
                    LEFT JOIN usuarios u ON l.usuario_id = u.id
                    WHERE l.tabela = 'aulas' AND l.usuario_id = ?";
            
            $params = [$usuarioId];
            
            if ($dataInicio && $dataFim) {
                $sql .= " AND DATE(l.criado_em) BETWEEN ? AND ?";
                $params[] = $dataInicio;
                $params[] = $dataFim;
            }
            
            $sql .= " ORDER BY l.criado_em DESC";
            
            $resultado = $this->db->fetchAll($sql, $params);
            
            return $resultado ?: [];
            
        } catch (Exception $e) {
            error_log("Erro ao buscar auditoria por usuário: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter estatísticas de auditoria
     * @param string $dataInicio Data de início
     * @param string $dataFim Data de fim
     * @return array Estatísticas de auditoria
     */
    public function obterEstatisticasAuditoria($dataInicio, $dataFim) {
        try {
            $estatisticas = [];
            
            // Total de ações
            $sql = "SELECT COUNT(*) as total FROM logs 
                    WHERE tabela = 'aulas' 
                    AND DATE(criado_em) BETWEEN ? AND ?";
            $resultado = $this->db->fetch($sql, [$dataInicio, $dataFim]);
            $estatisticas['total_acoes'] = $resultado['total'];
            
            // Ações por tipo
            $sql = "SELECT acao, COUNT(*) as total FROM logs 
                    WHERE tabela = 'aulas' 
                    AND DATE(criado_em) BETWEEN ? AND ?
                    GROUP BY acao
                    ORDER BY total DESC";
            $estatisticas['acoes_por_tipo'] = $this->db->fetchAll($sql, [$dataInicio, $dataFim]);
            
            // Ações por usuário
            $sql = "SELECT u.nome, u.email, COUNT(l.id) as total FROM logs l
                    LEFT JOIN usuarios u ON l.usuario_id = u.id
                    WHERE l.tabela = 'aulas' 
                    AND DATE(l.criado_em) BETWEEN ? AND ?
                    GROUP BY l.usuario_id, u.nome, u.email
                    ORDER BY total DESC";
            $estatisticas['acoes_por_usuario'] = $this->db->fetchAll($sql, [$dataInicio, $dataFim]);
            
            // Ações por dia
            $sql = "SELECT DATE(criado_em) as data, COUNT(*) as total FROM logs 
                    WHERE tabela = 'aulas' 
                    AND DATE(criado_em) BETWEEN ? AND ?
                    GROUP BY DATE(criado_em)
                    ORDER BY data DESC";
            $estatisticas['acoes_por_dia'] = $this->db->fetchAll($sql, [$dataInicio, $dataFim]);
            
            return $estatisticas;
            
        } catch (Exception $e) {
            error_log("Erro ao obter estatísticas de auditoria: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter IP do cliente
     * @return string IP do cliente
     */
    private function obterIP() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
?>
