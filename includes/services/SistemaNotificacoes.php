<?php
/**
 * Sistema de Notificações - CFC Bom Conselho
 * Gerencia notificações por e-mail e central de avisos
 * 
 * @author Sistema CFC
 * @version 1.0
 * @since 2024
 */

require_once __DIR__ . '/../database.php';

class SistemaNotificacoes {
    private $db;
    
    public function __construct() {
        $this->db = db();
    }
    
    /**
     * Enviar notificação de agendamento criado
     * @param int $aulaId ID da aula
     * @param array $dadosAula Dados da aula
     * @return bool Sucesso da operação
     */
    public function notificarAgendamentoCriado($aulaId, $dadosAula) {
        try {
            // Buscar dados completos da aula
            $aula = $this->buscarDadosCompletosAula($aulaId);
            if (!$aula) {
                return false;
            }
            
            // Notificar aluno
            $this->enviarEmailAluno($aula['aluno_email'], 'agendamento_criado', $aula);
            $this->registrarNotificacaoCentral($aula['aluno_id'], 'aluno', 'agendamento_criado', $aula);
            
            // Notificar instrutor
            $this->enviarEmailInstrutor($aula['instrutor_email'], 'agendamento_criado', $aula);
            $this->registrarNotificacaoCentral($aula['instrutor_id'], 'instrutor', 'agendamento_criado', $aula);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Erro ao enviar notificação de agendamento criado: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enviar notificação de agendamento alterado
     * @param int $aulaId ID da aula
     * @param array $dadosAnteriores Dados anteriores
     * @param array $dadosNovos Novos dados
     * @return bool Sucesso da operação
     */
    public function notificarAgendamentoAlterado($aulaId, $dadosAnteriores, $dadosNovos) {
        try {
            // Buscar dados completos da aula
            $aula = $this->buscarDadosCompletosAula($aulaId);
            if (!$aula) {
                return false;
            }
            
            $aula['dados_anteriores'] = $dadosAnteriores;
            $aula['dados_novos'] = $dadosNovos;
            
            // Notificar aluno
            $this->enviarEmailAluno($aula['aluno_email'], 'agendamento_alterado', $aula);
            $this->registrarNotificacaoCentral($aula['aluno_id'], 'aluno', 'agendamento_alterado', $aula);
            
            // Notificar instrutor
            $this->enviarEmailInstrutor($aula['instrutor_email'], 'agendamento_alterado', $aula);
            $this->registrarNotificacaoCentral($aula['instrutor_id'], 'instrutor', 'agendamento_alterado', $aula);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Erro ao enviar notificação de agendamento alterado: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enviar notificação de agendamento cancelado
     * @param int $aulaId ID da aula
     * @param array $dadosAula Dados da aula
     * @param string $motivo Motivo do cancelamento
     * @return bool Sucesso da operação
     */
    public function notificarAgendamentoCancelado($aulaId, $dadosAula, $motivo = '') {
        try {
            // Buscar dados completos da aula
            $aula = $this->buscarDadosCompletosAula($aulaId);
            if (!$aula) {
                return false;
            }
            
            $aula['motivo_cancelamento'] = $motivo;
            
            // Notificar aluno
            $this->enviarEmailAluno($aula['aluno_email'], 'agendamento_cancelado', $aula);
            $this->registrarNotificacaoCentral($aula['aluno_id'], 'aluno', 'agendamento_cancelado', $aula);
            
            // Notificar instrutor
            $this->enviarEmailInstrutor($aula['instrutor_email'], 'agendamento_cancelado', $aula);
            $this->registrarNotificacaoCentral($aula['instrutor_id'], 'instrutor', 'agendamento_cancelado', $aula);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Erro ao enviar notificação de agendamento cancelado: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enviar notificação de solicitação de reagendamento
     * @param int $aulaId ID da aula
     * @param array $dadosAula Dados da aula
     * @param string $justificativa Justificativa
     * @return bool Sucesso da operação
     */
    public function notificarSolicitacaoReagendamento($aulaId, $dadosAula, $justificativa = '') {
        try {
            // Buscar dados completos da aula
            $aula = $this->buscarDadosCompletosAula($aulaId);
            if (!$aula) {
                return false;
            }
            
            $aula['justificativa'] = $justificativa;
            
            // Notificar secretária/admin (quem aprova)
            $this->notificarSecretaria('solicitacao_reagendamento', $aula);
            
            // Registrar na central de avisos do aluno
            $this->registrarNotificacaoCentral($aula['aluno_id'], 'aluno', 'solicitacao_reagendamento', $aula);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Erro ao enviar notificação de solicitação de reagendamento: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enviar notificação de solicitação de cancelamento
     * @param int $aulaId ID da aula
     * @param array $dadosAula Dados da aula
     * @param string $justificativa Justificativa
     * @return bool Sucesso da operação
     */
    public function notificarSolicitacaoCancelamento($aulaId, $dadosAula, $justificativa = '') {
        try {
            // Buscar dados completos da aula
            $aula = $this->buscarDadosCompletosAula($aulaId);
            if (!$aula) {
                return false;
            }
            
            $aula['justificativa'] = $justificativa;
            
            // Notificar secretária/admin (quem aprova)
            $this->notificarSecretaria('solicitacao_cancelamento', $aula);
            
            // Registrar na central de avisos do aluno
            $this->registrarNotificacaoCentral($aula['aluno_id'], 'aluno', 'solicitacao_cancelamento', $aula);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Erro ao enviar notificação de solicitação de cancelamento: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enviar notificação de aprovação/negação de solicitação
     * @param int $aulaId ID da aula
     * @param string $tipoSolicitacao Tipo da solicitação
     * @param string $status Status da aprovação
     * @param string $motivo Motivo da decisão
     * @return bool Sucesso da operação
     */
    public function notificarAprovacaoSolicitacao($aulaId, $tipoSolicitacao, $status, $motivo = '') {
        try {
            // Buscar dados completos da aula
            $aula = $this->buscarDadosCompletosAula($aulaId);
            if (!$aula) {
                return false;
            }
            
            $aula['tipo_solicitacao'] = $tipoSolicitacao;
            $aula['status_aprovacao'] = $status;
            $aula['motivo_decisao'] = $motivo;
            
            // Notificar aluno
            $this->enviarEmailAluno($aula['aluno_email'], 'aprovacao_solicitacao', $aula);
            $this->registrarNotificacaoCentral($aula['aluno_id'], 'aluno', 'aprovacao_solicitacao', $aula);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Erro ao enviar notificação de aprovação: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Buscar dados completos da aula
     * @param int $aulaId ID da aula
     * @return array|null Dados da aula
     */
    private function buscarDadosCompletosAula($aulaId) {
        $sql = "SELECT a.*, 
                       al.nome as aluno_nome, al.email as aluno_email, al.telefone as aluno_telefone,
                       COALESCE(u.nome, i.nome) as instrutor_nome, 
                       COALESCE(u.email, i.email) as instrutor_email,
                       i.id as instrutor_id,
                       v.placa, v.modelo, v.marca,
                       c.nome as cfc_nome
                FROM aulas a
                JOIN alunos al ON a.aluno_id = al.id
                JOIN instrutores i ON a.instrutor_id = i.id
                LEFT JOIN usuarios u ON i.usuario_id = u.id
                LEFT JOIN veiculos v ON a.veiculo_id = v.id
                LEFT JOIN cfcs c ON a.cfc_id = c.id
                WHERE a.id = ?";
        
        return $this->db->fetch($sql, [$aulaId]);
    }
    
    /**
     * Enviar e-mail para aluno
     * @param string $email E-mail do aluno
     * @param string $tipo Tipo da notificação
     * @param array $dados Dados da aula
     * @return bool Sucesso da operação
     */
    private function enviarEmailAluno($email, $tipo, $dados) {
        if (empty($email)) {
            return false;
        }
        
        $assunto = $this->gerarAssuntoEmail($tipo, $dados);
        $corpo = $this->gerarCorpoEmailAluno($tipo, $dados);
        
        return $this->enviarEmail($email, $assunto, $corpo);
    }
    
    /**
     * Enviar e-mail para instrutor
     * @param string $email E-mail do instrutor
     * @param string $tipo Tipo da notificação
     * @param array $dados Dados da aula
     * @return bool Sucesso da operação
     */
    private function enviarEmailInstrutor($email, $tipo, $dados) {
        if (empty($email)) {
            return false;
        }
        
        $assunto = $this->gerarAssuntoEmail($tipo, $dados);
        $corpo = $this->gerarCorpoEmailInstrutor($tipo, $dados);
        
        return $this->enviarEmail($email, $assunto, $corpo);
    }
    
    /**
     * Notificar secretária/admin
     * @param string $tipo Tipo da notificação
     * @param array $dados Dados da aula
     * @return bool Sucesso da operação
     */
    private function notificarSecretaria($tipo, $dados) {
        // Buscar e-mails de secretárias e admins
        $sql = "SELECT email FROM usuarios WHERE tipo IN ('admin', 'secretaria') AND ativo = 1";
        $usuarios = $this->db->fetchAll($sql);
        
        $assunto = $this->gerarAssuntoEmail($tipo, $dados);
        $corpo = $this->gerarCorpoEmailSecretaria($tipo, $dados);
        
        $sucesso = true;
        foreach ($usuarios as $usuario) {
            if (!$this->enviarEmail($usuario['email'], $assunto, $corpo)) {
                $sucesso = false;
            }
        }
        
        return $sucesso;
    }
    
    /**
     * Gerar assunto do e-mail
     * @param string $tipo Tipo da notificação
     * @param array $dados Dados da aula
     * @return string Assunto do e-mail
     */
    private function gerarAssuntoEmail($tipo, $dados) {
        $cfcNome = $dados['cfc_nome'] ?? 'CFC Bom Conselho';
        $dataFormatada = date('d/m/Y', strtotime($dados['data_aula']));
        $horaFormatada = substr($dados['hora_inicio'], 0, 5);
        
        switch ($tipo) {
            case 'agendamento_criado':
                return "[{$cfcNome}] Nova aula agendada para {$dataFormatada} às {$horaFormatada}";
            case 'agendamento_alterado':
                return "[{$cfcNome}] Aula alterada para {$dataFormatada} às {$horaFormatada}";
            case 'agendamento_cancelado':
                return "[{$cfcNome}] Aula cancelada de {$dataFormatada} às {$horaFormatada}";
            case 'solicitacao_reagendamento':
                return "[{$cfcNome}] Solicitação de reagendamento - {$dataFormatada} às {$horaFormatada}";
            case 'solicitacao_cancelamento':
                return "[{$cfcNome}] Solicitação de cancelamento - {$dataFormatada} às {$horaFormatada}";
            case 'aprovacao_solicitacao':
                return "[{$cfcNome}] Solicitação {$dados['status_aprovacao']} - {$dataFormatada} às {$horaFormatada}";
            default:
                return "[{$cfcNome}] Notificação do Sistema";
        }
    }
    
    /**
     * Gerar corpo do e-mail para aluno
     * @param string $tipo Tipo da notificação
     * @param array $dados Dados da aula
     * @return string Corpo do e-mail
     */
    private function gerarCorpoEmailAluno($tipo, $dados) {
        $cfcNome = $dados['cfc_nome'] ?? 'CFC Bom Conselho';
        $alunoNome = $dados['aluno_nome'];
        $dataFormatada = date('d/m/Y', strtotime($dados['data_aula']));
        $horaFormatada = substr($dados['hora_inicio'], 0, 5);
        $tipoAula = ucfirst($dados['tipo_aula']);
        $instrutorNome = $dados['instrutor_nome'];
        
        $corpo = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Notificação - {$cfcNome}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f8fafc; }
        .info-box { background: white; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>{$cfcNome}</h1>
        </div>
        <div class='content'>
            <h2>Olá, {$alunoNome}!</h2>";
        
        switch ($tipo) {
            case 'agendamento_criado':
                $corpo .= "<p>Sua aula foi agendada com sucesso!</p>
                <div class='info-box'>
                    <h3>Detalhes da Aula</h3>
                    <p><strong>Data:</strong> {$dataFormatada}</p>
                    <p><strong>Horário:</strong> {$horaFormatada}</p>
                    <p><strong>Tipo:</strong> {$tipoAula}</p>
                    <p><strong>Instrutor:</strong> {$instrutorNome}</p>";
                if ($dados['veiculo_id']) {
                    $corpo .= "<p><strong>Veículo:</strong> {$dados['marca']} {$dados['modelo']} - {$dados['placa']}</p>";
                }
                $corpo .= "</div>";
                break;
                
            case 'agendamento_alterado':
                $corpo .= "<p>Sua aula foi alterada!</p>
                <div class='info-box'>
                    <h3>Novos Detalhes da Aula</h3>
                    <p><strong>Data:</strong> {$dataFormatada}</p>
                    <p><strong>Horário:</strong> {$horaFormatada}</p>
                    <p><strong>Tipo:</strong> {$tipoAula}</p>
                    <p><strong>Instrutor:</strong> {$instrutorNome}</p>";
                if ($dados['veiculo_id']) {
                    $corpo .= "<p><strong>Veículo:</strong> {$dados['marca']} {$dados['modelo']} - {$dados['placa']}</p>";
                }
                $corpo .= "</div>";
                break;
                
            case 'agendamento_cancelado':
                $corpo .= "<p>Sua aula foi cancelada.</p>
                <div class='info-box'>
                    <h3>Detalhes da Aula Cancelada</h3>
                    <p><strong>Data:</strong> {$dataFormatada}</p>
                    <p><strong>Horário:</strong> {$horaFormatada}</p>
                    <p><strong>Tipo:</strong> {$tipoAula}</p>
                    <p><strong>Instrutor:</strong> {$instrutorNome}</p>";
                if (!empty($dados['motivo_cancelamento'])) {
                    $corpo .= "<p><strong>Motivo:</strong> {$dados['motivo_cancelamento']}</p>";
                }
                $corpo .= "</div>";
                break;
                
            case 'aprovacao_solicitacao':
                $status = $dados['status_aprovacao'] === 'aprovado' ? 'aprovada' : 'negada';
                $corpo .= "<p>Sua solicitação foi {$status}.</p>
                <div class='info-box'>
                    <h3>Detalhes da Solicitação</h3>
                    <p><strong>Tipo:</strong> " . ucfirst($dados['tipo_solicitacao']) . "</p>
                    <p><strong>Data:</strong> {$dataFormatada}</p>
                    <p><strong>Horário:</strong> {$horaFormatada}</p>
                    <p><strong>Status:</strong> " . ucfirst($dados['status_aprovacao']) . "</p>";
                if (!empty($dados['motivo_decisao'])) {
                    $corpo .= "<p><strong>Motivo:</strong> {$dados['motivo_decisao']}</p>";
                }
                $corpo .= "</div>";
                break;
        }
        
        $corpo .= "</div>
        <div class='footer'>
            <p>Esta é uma mensagem automática do sistema {$cfcNome}.</p>
            <p>Para mais informações, entre em contato conosco.</p>
        </div>
    </div>
</body>
</html>";
        
        return $corpo;
    }
    
    /**
     * Gerar corpo do e-mail para instrutor
     * @param string $tipo Tipo da notificação
     * @param array $dados Dados da aula
     * @return string Corpo do e-mail
     */
    private function gerarCorpoEmailInstrutor($tipo, $dados) {
        $cfcNome = $dados['cfc_nome'] ?? 'CFC Bom Conselho';
        $instrutorNome = $dados['instrutor_nome'];
        $dataFormatada = date('d/m/Y', strtotime($dados['data_aula']));
        $horaFormatada = substr($dados['hora_inicio'], 0, 5);
        $tipoAula = ucfirst($dados['tipo_aula']);
        $alunoNome = $dados['aluno_nome'];
        
        $corpo = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Notificação - {$cfcNome}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f8fafc; }
        .info-box { background: white; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>{$cfcNome}</h1>
        </div>
        <div class='content'>
            <h2>Olá, {$instrutorNome}!</h2>";
        
        switch ($tipo) {
            case 'agendamento_criado':
                $corpo .= "<p>Uma nova aula foi agendada para você!</p>
                <div class='info-box'>
                    <h3>Detalhes da Aula</h3>
                    <p><strong>Data:</strong> {$dataFormatada}</p>
                    <p><strong>Horário:</strong> {$horaFormatada}</p>
                    <p><strong>Tipo:</strong> {$tipoAula}</p>
                    <p><strong>Aluno:</strong> {$alunoNome}</p>";
                if ($dados['veiculo_id']) {
                    $corpo .= "<p><strong>Veículo:</strong> {$dados['marca']} {$dados['modelo']} - {$dados['placa']}</p>";
                }
                $corpo .= "</div>";
                break;
                
            case 'agendamento_alterado':
                $corpo .= "<p>Uma aula foi alterada!</p>
                <div class='info-box'>
                    <h3>Novos Detalhes da Aula</h3>
                    <p><strong>Data:</strong> {$dataFormatada}</p>
                    <p><strong>Horário:</strong> {$horaFormatada}</p>
                    <p><strong>Tipo:</strong> {$tipoAula}</p>
                    <p><strong>Aluno:</strong> {$alunoNome}</p>";
                if ($dados['veiculo_id']) {
                    $corpo .= "<p><strong>Veículo:</strong> {$dados['marca']} {$dados['modelo']} - {$dados['placa']}</p>";
                }
                $corpo .= "</div>";
                break;
                
            case 'agendamento_cancelado':
                $corpo .= "<p>Uma aula foi cancelada.</p>
                <div class='info-box'>
                    <h3>Detalhes da Aula Cancelada</h3>
                    <p><strong>Data:</strong> {$dataFormatada}</p>
                    <p><strong>Horário:</strong> {$horaFormatada}</p>
                    <p><strong>Tipo:</strong> {$tipoAula}</p>
                    <p><strong>Aluno:</strong> {$alunoNome}</p>";
                if (!empty($dados['motivo_cancelamento'])) {
                    $corpo .= "<p><strong>Motivo:</strong> {$dados['motivo_cancelamento']}</p>";
                }
                $corpo .= "</div>";
                break;
        }
        
        $corpo .= "</div>
        <div class='footer'>
            <p>Esta é uma mensagem automática do sistema {$cfcNome}.</p>
            <p>Para mais informações, entre em contato conosco.</p>
        </div>
    </div>
</body>
</html>";
        
        return $corpo;
    }
    
    /**
     * Gerar corpo do e-mail para secretária
     * @param string $tipo Tipo da notificação
     * @param array $dados Dados da aula
     * @return string Corpo do e-mail
     */
    private function gerarCorpoEmailSecretaria($tipo, $dados) {
        $cfcNome = $dados['cfc_nome'] ?? 'CFC Bom Conselho';
        $dataFormatada = date('d/m/Y', strtotime($dados['data_aula']));
        $horaFormatada = substr($dados['hora_inicio'], 0, 5);
        $tipoAula = ucfirst($dados['tipo_aula']);
        $alunoNome = $dados['aluno_nome'];
        $instrutorNome = $dados['instrutor_nome'];
        
        $corpo = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Notificação - {$cfcNome}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f59e0b; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f8fafc; }
        .info-box { background: white; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>{$cfcNome}</h1>
            <h2>Ação Requerida</h2>
        </div>
        <div class='content'>
            <h2>Nova solicitação requer aprovação!</h2>";
        
        switch ($tipo) {
            case 'solicitacao_reagendamento':
                $corpo .= "<p>O aluno {$alunoNome} solicitou reagendamento de uma aula.</p>
                <div class='info-box'>
                    <h3>Detalhes da Solicitação</h3>
                    <p><strong>Aluno:</strong> {$alunoNome}</p>
                    <p><strong>Instrutor:</strong> {$instrutorNome}</p>
                    <p><strong>Data Atual:</strong> {$dataFormatada}</p>
                    <p><strong>Horário Atual:</strong> {$horaFormatada}</p>
                    <p><strong>Tipo:</strong> {$tipoAula}</p>";
                if (!empty($dados['justificativa'])) {
                    $corpo .= "<p><strong>Justificativa:</strong> {$dados['justificativa']}</p>";
                }
                $corpo .= "</div>";
                break;
                
            case 'solicitacao_cancelamento':
                $corpo .= "<p>O aluno {$alunoNome} solicitou cancelamento de uma aula.</p>
                <div class='info-box'>
                    <h3>Detalhes da Solicitação</h3>
                    <p><strong>Aluno:</strong> {$alunoNome}</p>
                    <p><strong>Instrutor:</strong> {$instrutorNome}</p>
                    <p><strong>Data:</strong> {$dataFormatada}</p>
                    <p><strong>Horário:</strong> {$horaFormatada}</p>
                    <p><strong>Tipo:</strong> {$tipoAula}</p>";
                if (!empty($dados['justificativa'])) {
                    $corpo .= "<p><strong>Justificativa:</strong> {$dados['justificativa']}</p>";
                }
                $corpo .= "</div>";
                break;
        }
        
        $corpo .= "<p><strong>Acesse o sistema para aprovar ou negar esta solicitação.</strong></p>
        </div>
        <div class='footer'>
            <p>Esta é uma mensagem automática do sistema {$cfcNome}.</p>
            <p>Para mais informações, entre em contato conosco.</p>
        </div>
    </div>
</body>
</html>";
        
        return $corpo;
    }
    
    /**
     * Enviar e-mail
     * @param string $email E-mail de destino
     * @param string $assunto Assunto do e-mail
     * @param string $corpo Corpo do e-mail
     * @return bool Sucesso da operação
     */
    private function enviarEmail($email, $assunto, $corpo) {
        // Por enquanto, apenas registrar no log
        // Em produção, implementar envio real de e-mail
        error_log("E-mail enviado para {$email}: {$assunto}");
        
        // Simular envio (sempre retorna true)
        return true;
        
        // Implementação real seria algo como:
        /*
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: noreply@cfcbomconselho.com',
            'Reply-To: contato@cfcbomconselho.com',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        return mail($email, $assunto, $corpo, implode("\r\n", $headers));
        */
    }
    
    /**
     * Registrar notificação na central de avisos
     * @param int $usuarioId ID do usuário
     * @param string $tipoUsuario Tipo do usuário
     * @param string $tipoNotificacao Tipo da notificação
     * @param array $dados Dados da notificação
     * @return bool Sucesso da operação
     */
    private function registrarNotificacaoCentral($usuarioId, $tipoUsuario, $tipoNotificacao, $dados) {
        try {
            $sql = "INSERT INTO notificacoes (usuario_id, tipo_usuario, tipo_notificacao, titulo, mensagem, dados, lida, criado_em) 
                    VALUES (?, ?, ?, ?, ?, ?, 0, NOW())";
            
            $titulo = $this->gerarTituloNotificacao($tipoNotificacao, $dados);
            $mensagem = $this->gerarMensagemNotificacao($tipoNotificacao, $dados);
            $dadosJson = json_encode($dados);
            
            return $this->db->query($sql, [$usuarioId, $tipoUsuario, $tipoNotificacao, $titulo, $mensagem, $dadosJson]);
            
        } catch (Exception $e) {
            error_log("Erro ao registrar notificação na central: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gerar título da notificação
     * @param string $tipo Tipo da notificação
     * @param array $dados Dados da aula
     * @return string Título da notificação
     */
    private function gerarTituloNotificacao($tipo, $dados) {
        $dataFormatada = date('d/m/Y', strtotime($dados['data_aula']));
        $horaFormatada = substr($dados['hora_inicio'], 0, 5);
        
        switch ($tipo) {
            case 'agendamento_criado':
                return "Nova aula agendada para {$dataFormatada} às {$horaFormatada}";
            case 'agendamento_alterado':
                return "Aula alterada para {$dataFormatada} às {$horaFormatada}";
            case 'agendamento_cancelado':
                return "Aula cancelada de {$dataFormatada} às {$horaFormatada}";
            case 'solicitacao_reagendamento':
                return "Solicitação de reagendamento - {$dataFormatada} às {$horaFormatada}";
            case 'solicitacao_cancelamento':
                return "Solicitação de cancelamento - {$dataFormatada} às {$horaFormatada}";
            case 'aprovacao_solicitacao':
                return "Solicitação {$dados['status_aprovacao']} - {$dataFormatada} às {$horaFormatada}";
            default:
                return "Notificação do sistema";
        }
    }
    
    /**
     * Gerar mensagem da notificação
     * @param string $tipo Tipo da notificação
     * @param array $dados Dados da aula
     * @return string Mensagem da notificação
     */
    private function gerarMensagemNotificacao($tipo, $dados) {
        $tipoAula = ucfirst($dados['tipo_aula']);
        $dataFormatada = date('d/m/Y', strtotime($dados['data_aula']));
        $horaFormatada = substr($dados['hora_inicio'], 0, 5);
        
        switch ($tipo) {
            case 'agendamento_criado':
                return "Sua aula de {$tipoAula} foi agendada para {$dataFormatada} às {$horaFormatada}.";
            case 'agendamento_alterado':
                return "Sua aula de {$tipoAula} foi alterada para {$dataFormatada} às {$horaFormatada}.";
            case 'agendamento_cancelado':
                return "Sua aula de {$tipoAula} de {$dataFormatada} às {$horaFormatada} foi cancelada.";
            case 'solicitacao_reagendamento':
                return "Sua solicitação de reagendamento foi enviada e está sendo analisada.";
            case 'solicitacao_cancelamento':
                return "Sua solicitação de cancelamento foi enviada e está sendo analisada.";
            case 'aprovacao_solicitacao':
                $status = $dados['status_aprovacao'] === 'aprovado' ? 'aprovada' : 'negada';
                return "Sua solicitação foi {$status}.";
            default:
                return "Você tem uma nova notificação.";
        }
    }
    
    /**
     * Buscar notificações não lidas do usuário
     * @param int $usuarioId ID do usuário
     * @param string $tipoUsuario Tipo do usuário
     * @return array Notificações não lidas
     */
    public function buscarNotificacoesNaoLidas($usuarioId, $tipoUsuario) {
        try {
            $sql = "SELECT * FROM notificacoes 
                    WHERE usuario_id = ? AND tipo_usuario = ? AND lida = 0 
                    ORDER BY criado_em DESC 
                    LIMIT 10";
            
            return $this->db->fetchAll($sql, [$usuarioId, $tipoUsuario]);
            
        } catch (Exception $e) {
            error_log("Erro ao buscar notificações: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Marcar notificação como lida
     * @param int $notificacaoId ID da notificação
     * @return bool Sucesso da operação
     */
    public function marcarComoLida($notificacaoId) {
        try {
            $sql = "UPDATE notificacoes SET lida = 1, lida_em = NOW() WHERE id = ?";
            return $this->db->query($sql, [$notificacaoId]);
            
        } catch (Exception $e) {
            error_log("Erro ao marcar notificação como lida: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Marcar todas as notificações do usuário como lidas
     * @param int $usuarioId ID do usuário
     * @param string $tipoUsuario Tipo do usuário
     * @return bool Sucesso da operação
     */
    public function marcarTodasComoLidas($usuarioId, $tipoUsuario) {
        try {
            $sql = "UPDATE notificacoes SET lida = 1, lida_em = NOW() 
                    WHERE usuario_id = ? AND tipo_usuario = ? AND lida = 0";
            return $this->db->query($sql, [$usuarioId, $tipoUsuario]);
            
        } catch (Exception $e) {
            error_log("Erro ao marcar todas as notificações como lidas: " . $e->getMessage());
            return false;
        }
    }
}
?>
