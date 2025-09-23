<?php
/**
 * AgendamentoPermissions - Sistema de permissões específico para agendamentos
 * Implementa as regras de permissão conforme checklist:
 * - Admin/Secretária: criar/editar/cancelar agendamentos teóricos e práticos
 * - Instrutor: cancelar ou transferir suas aulas (limites + motivos)
 * - Aluno: solicitar reagendamento/cancelamento (com política e aprovação)
 * 
 * @author Sistema CFC
 * @version 1.0
 * @since 2024
 */

require_once __DIR__ . '/../auth.php';

class AgendamentoPermissions {
    private $auth;
    
    public function __construct() {
        $this->auth = new Auth();
    }
    
    /**
     * Verificar se usuário pode criar agendamentos
     * Apenas Admin e Secretária podem criar agendamentos
     * @return array Resultado da verificação
     */
    public function podeCriarAgendamento() {
        $user = $this->auth->getCurrentUser();
        
        if (!$user) {
            return [
                'permitido' => false,
                'motivo' => 'Usuário não autenticado',
                'tipo' => 'nao_autenticado'
            ];
        }
        
        $tiposPermitidos = ['admin', 'secretaria'];
        
        if (!in_array($user['tipo'], $tiposPermitidos)) {
            return [
                'permitido' => false,
                'motivo' => 'Apenas administradores e secretárias podem criar agendamentos',
                'tipo' => 'permissao_negada',
                'usuario_tipo' => $user['tipo'],
                'tipos_permitidos' => $tiposPermitidos
            ];
        }
        
        return [
            'permitido' => true,
            'motivo' => 'Usuário autorizado a criar agendamentos',
            'tipo' => 'permissao_concedida',
            'usuario_tipo' => $user['tipo']
        ];
    }
    
    /**
     * Verificar se usuário pode editar agendamento
     * Admin e Secretária podem editar qualquer agendamento
     * Instrutor pode editar apenas suas próprias aulas
     * @param int $aulaId ID da aula
     * @return array Resultado da verificação
     */
    public function podeEditarAgendamento($aulaId) {
        $user = $this->auth->getCurrentUser();
        
        if (!$user) {
            return [
                'permitido' => false,
                'motivo' => 'Usuário não autenticado',
                'tipo' => 'nao_autenticado'
            ];
        }
        
        // Admin e Secretária podem editar qualquer agendamento
        if (in_array($user['tipo'], ['admin', 'secretaria'])) {
            return [
                'permitido' => true,
                'motivo' => 'Usuário autorizado a editar agendamentos',
                'tipo' => 'permissao_concedida',
                'usuario_tipo' => $user['tipo']
            ];
        }
        
        // Instrutor pode editar apenas suas próprias aulas
        if ($user['tipo'] === 'instrutor') {
            $db = db();
            
            // Buscar instrutor_id do usuário
            $instrutor = $db->fetch("SELECT id FROM instrutores WHERE usuario_id = ?", [$user['id']]);
            
            if (!$instrutor) {
                return [
                    'permitido' => false,
                    'motivo' => 'Instrutor não encontrado',
                    'tipo' => 'instrutor_nao_encontrado'
                ];
            }
            
            // Verificar se a aula pertence ao instrutor
            $aula = $db->fetch("SELECT instrutor_id FROM aulas WHERE id = ?", [$aulaId]);
            
            if (!$aula) {
                return [
                    'permitido' => false,
                    'motivo' => 'Aula não encontrada',
                    'tipo' => 'aula_nao_encontrada'
                ];
            }
            
            if ($aula['instrutor_id'] == $instrutor['id']) {
                return [
                    'permitido' => true,
                    'motivo' => 'Instrutor autorizado a editar sua própria aula',
                    'tipo' => 'permissao_concedida',
                    'usuario_tipo' => $user['tipo']
                ];
            } else {
                return [
                    'permitido' => false,
                    'motivo' => 'Instrutor só pode editar suas próprias aulas',
                    'tipo' => 'permissao_negada',
                    'usuario_tipo' => $user['tipo']
                ];
            }
        }
        
        return [
            'permitido' => false,
            'motivo' => 'Usuário não autorizado a editar agendamentos',
            'tipo' => 'permissao_negada',
            'usuario_tipo' => $user['tipo']
        ];
    }
    
    /**
     * Verificar se usuário pode cancelar agendamento
     * Admin e Secretária podem cancelar qualquer agendamento
     * Instrutor pode cancelar apenas suas próprias aulas (com políticas)
     * @param int $aulaId ID da aula
     * @return array Resultado da verificação
     */
    public function podeCancelarAgendamento($aulaId) {
        $user = $this->auth->getCurrentUser();
        
        if (!$user) {
            return [
                'permitido' => false,
                'motivo' => 'Usuário não autenticado',
                'tipo' => 'nao_autenticado'
            ];
        }
        
        // Admin e Secretária podem cancelar qualquer agendamento
        if (in_array($user['tipo'], ['admin', 'secretaria'])) {
            return [
                'permitido' => true,
                'motivo' => 'Usuário autorizado a cancelar agendamentos',
                'tipo' => 'permissao_concedida',
                'usuario_tipo' => $user['tipo']
            ];
        }
        
        // Instrutor pode cancelar apenas suas próprias aulas
        if ($user['tipo'] === 'instrutor') {
            $db = db();
            
            // Buscar instrutor_id do usuário
            $instrutor = $db->fetch("SELECT id FROM instrutores WHERE usuario_id = ?", [$user['id']]);
            
            if (!$instrutor) {
                return [
                    'permitido' => false,
                    'motivo' => 'Instrutor não encontrado',
                    'tipo' => 'instrutor_nao_encontrado'
                ];
            }
            
            // Verificar se a aula pertence ao instrutor
            $aula = $db->fetch("SELECT instrutor_id, data_aula, hora_inicio FROM aulas WHERE id = ?", [$aulaId]);
            
            if (!$aula) {
                return [
                    'permitido' => false,
                    'motivo' => 'Aula não encontrada',
                    'tipo' => 'aula_nao_encontrada'
                ];
            }
            
            if ($aula['instrutor_id'] == $instrutor['id']) {
                // Verificar política de cancelamento (24h de antecedência)
                $politicaCancelamento = $this->verificarPoliticaCancelamento($aula['data_aula'], $aula['hora_inicio']);
                
                if (!$politicaCancelamento['permitido']) {
                    return [
                        'permitido' => false,
                        'motivo' => $politicaCancelamento['motivo'],
                        'tipo' => 'politica_cancelamento',
                        'usuario_tipo' => $user['tipo']
                    ];
                }
                
                return [
                    'permitido' => true,
                    'motivo' => 'Instrutor autorizado a cancelar sua própria aula',
                    'tipo' => 'permissao_concedida',
                    'usuario_tipo' => $user['tipo']
                ];
            } else {
                return [
                    'permitido' => false,
                    'motivo' => 'Instrutor só pode cancelar suas próprias aulas',
                    'tipo' => 'permissao_negada',
                    'usuario_tipo' => $user['tipo']
                ];
            }
        }
        
        return [
            'permitido' => false,
            'motivo' => 'Usuário não autorizado a cancelar agendamentos',
            'tipo' => 'permissao_negada',
            'usuario_tipo' => $user['tipo']
        ];
    }
    
    /**
     * Verificar se usuário pode transferir agendamento
     * Admin e Secretária podem transferir qualquer agendamento
     * Instrutor pode transferir apenas suas próprias aulas (com políticas)
     * @param int $aulaId ID da aula
     * @return array Resultado da verificação
     */
    public function podeTransferirAgendamento($aulaId) {
        $user = $this->auth->getCurrentUser();
        
        if (!$user) {
            return [
                'permitido' => false,
                'motivo' => 'Usuário não autenticado',
                'tipo' => 'nao_autenticado'
            ];
        }
        
        // Admin e Secretária podem transferir qualquer agendamento
        if (in_array($user['tipo'], ['admin', 'secretaria'])) {
            return [
                'permitido' => true,
                'motivo' => 'Usuário autorizado a transferir agendamentos',
                'tipo' => 'permissao_concedida',
                'usuario_tipo' => $user['tipo']
            ];
        }
        
        // Instrutor pode transferir apenas suas próprias aulas
        if ($user['tipo'] === 'instrutor') {
            $db = db();
            
            // Buscar instrutor_id do usuário
            $instrutor = $db->fetch("SELECT id FROM instrutores WHERE usuario_id = ?", [$user['id']]);
            
            if (!$instrutor) {
                return [
                    'permitido' => false,
                    'motivo' => 'Instrutor não encontrado',
                    'tipo' => 'instrutor_nao_encontrado'
                ];
            }
            
            // Verificar se a aula pertence ao instrutor
            $aula = $db->fetch("SELECT instrutor_id, data_aula, hora_inicio FROM aulas WHERE id = ?", [$aulaId]);
            
            if (!$aula) {
                return [
                    'permitido' => false,
                    'motivo' => 'Aula não encontrada',
                    'tipo' => 'aula_nao_encontrada'
                ];
            }
            
            if ($aula['instrutor_id'] == $instrutor['id']) {
                // Verificar política de transferência (24h de antecedência)
                $politicaTransferencia = $this->verificarPoliticaTransferencia($aula['data_aula'], $aula['hora_inicio']);
                
                if (!$politicaTransferencia['permitido']) {
                    return [
                        'permitido' => false,
                        'motivo' => $politicaTransferencia['motivo'],
                        'tipo' => 'politica_transferencia',
                        'usuario_tipo' => $user['tipo']
                    ];
                }
                
                return [
                    'permitido' => true,
                    'motivo' => 'Instrutor autorizado a transferir sua própria aula',
                    'tipo' => 'permissao_concedida',
                    'usuario_tipo' => $user['tipo']
                ];
            } else {
                return [
                    'permitido' => false,
                    'motivo' => 'Instrutor só pode transferir suas próprias aulas',
                    'tipo' => 'permissao_negada',
                    'usuario_tipo' => $user['tipo']
                ];
            }
        }
        
        return [
            'permitido' => false,
            'motivo' => 'Usuário não autorizado a transferir agendamentos',
            'tipo' => 'permissao_negada',
            'usuario_tipo' => $user['tipo']
        ];
    }
    
    /**
     * Verificar se aluno pode solicitar reagendamento
     * Alunos podem solicitar reagendamento (não efetivar diretamente)
     * @param int $aulaId ID da aula
     * @return array Resultado da verificação
     */
    public function podeSolicitarReagendamento($aulaId) {
        $user = $this->auth->getCurrentUser();
        
        if (!$user) {
            return [
                'permitido' => false,
                'motivo' => 'Usuário não autenticado',
                'tipo' => 'nao_autenticado'
            ];
        }
        
        // Apenas alunos podem solicitar reagendamento
        if ($user['tipo'] !== 'aluno') {
            return [
                'permitido' => false,
                'motivo' => 'Apenas alunos podem solicitar reagendamento',
                'tipo' => 'permissao_negada',
                'usuario_tipo' => $user['tipo']
            ];
        }
        
        $db = db();
        
        // Buscar aluno_id do usuário
        $aluno = $db->fetch("SELECT id FROM alunos WHERE usuario_id = ?", [$user['id']]);
        
        if (!$aluno) {
            return [
                'permitido' => false,
                'motivo' => 'Aluno não encontrado',
                'tipo' => 'aluno_nao_encontrado'
            ];
        }
        
        // Verificar se a aula pertence ao aluno
        $aula = $db->fetch("SELECT aluno_id, data_aula, hora_inicio FROM aulas WHERE id = ?", [$aulaId]);
        
        if (!$aula) {
            return [
                'permitido' => false,
                'motivo' => 'Aula não encontrada',
                'tipo' => 'aula_nao_encontrada'
            ];
        }
        
        if ($aula['aluno_id'] == $aluno['id']) {
            // Verificar política de reagendamento (24h de antecedência)
            $politicaReagendamento = $this->verificarPoliticaReagendamento($aula['data_aula'], $aula['hora_inicio']);
            
            if (!$politicaReagendamento['permitido']) {
                return [
                    'permitido' => false,
                    'motivo' => $politicaReagendamento['motivo'],
                    'tipo' => 'politica_reagendamento',
                    'usuario_tipo' => $user['tipo']
                ];
            }
            
            return [
                'permitido' => true,
                'motivo' => 'Aluno autorizado a solicitar reagendamento',
                'tipo' => 'permissao_concedida',
                'usuario_tipo' => $user['tipo']
            ];
        } else {
            return [
                'permitido' => false,
                'motivo' => 'Aluno só pode solicitar reagendamento de suas próprias aulas',
                'tipo' => 'permissao_negada',
                'usuario_tipo' => $user['tipo']
            ];
        }
    }
    
    /**
     * Verificar se aluno pode solicitar cancelamento
     * Alunos podem solicitar cancelamento (não efetivar diretamente)
     * @param int $aulaId ID da aula
     * @return array Resultado da verificação
     */
    public function podeSolicitarCancelamento($aulaId) {
        $user = $this->auth->getCurrentUser();
        
        if (!$user) {
            return [
                'permitido' => false,
                'motivo' => 'Usuário não autenticado',
                'tipo' => 'nao_autenticado'
            ];
        }
        
        // Apenas alunos podem solicitar cancelamento
        if ($user['tipo'] !== 'aluno') {
            return [
                'permitido' => false,
                'motivo' => 'Apenas alunos podem solicitar cancelamento',
                'tipo' => 'permissao_negada',
                'usuario_tipo' => $user['tipo']
            ];
        }
        
        $db = db();
        
        // Buscar aluno_id do usuário
        $aluno = $db->fetch("SELECT id FROM alunos WHERE usuario_id = ?", [$user['id']]);
        
        if (!$aluno) {
            return [
                'permitido' => false,
                'motivo' => 'Aluno não encontrado',
                'tipo' => 'aluno_nao_encontrado'
            ];
        }
        
        // Verificar se a aula pertence ao aluno
        $aula = $db->fetch("SELECT aluno_id, data_aula, hora_inicio FROM aulas WHERE id = ?", [$aulaId]);
        
        if (!$aula) {
            return [
                'permitido' => false,
                'motivo' => 'Aula não encontrada',
                'tipo' => 'aula_nao_encontrada'
            ];
        }
        
        if ($aula['aluno_id'] == $aluno['id']) {
            // Verificar política de cancelamento (24h de antecedência)
            $politicaCancelamento = $this->verificarPoliticaCancelamento($aula['data_aula'], $aula['hora_inicio']);
            
            if (!$politicaCancelamento['permitido']) {
                return [
                    'permitido' => false,
                    'motivo' => $politicaCancelamento['motivo'],
                    'tipo' => 'politica_cancelamento',
                    'usuario_tipo' => $user['tipo']
                ];
            }
            
            return [
                'permitido' => true,
                'motivo' => 'Aluno autorizado a solicitar cancelamento',
                'tipo' => 'permissao_concedida',
                'usuario_tipo' => $user['tipo']
            ];
        } else {
            return [
                'permitido' => false,
                'motivo' => 'Aluno só pode solicitar cancelamento de suas próprias aulas',
                'tipo' => 'permissao_negada',
                'usuario_tipo' => $user['tipo']
            ];
        }
    }
    
    /**
     * Verificar política de cancelamento (24h de antecedência)
     * @param string $dataAula Data da aula
     * @param string $horaInicio Hora de início
     * @return array Resultado da verificação
     */
    private function verificarPoliticaCancelamento($dataAula, $horaInicio) {
        try {
            $dataHoraAula = strtotime($dataAula . ' ' . $horaInicio);
            $agora = time();
            $diferencaHoras = ($dataHoraAula - $agora) / 3600; // Diferença em horas
            
            $limiteHoras = 24; // Configurável
            
            if ($diferencaHoras < $limiteHoras) {
                return [
                    'permitido' => false,
                    'motivo' => "Cancelamento permitido apenas com {$limiteHoras}h de antecedência. Restam " . 
                               number_format($diferencaHoras, 1) . " horas para a aula.",
                    'tipo' => 'politica_cancelamento',
                    'horas_restantes' => $diferencaHoras,
                    'limite_horas' => $limiteHoras
                ];
            }
            
            return [
                'permitido' => true,
                'motivo' => 'Cancelamento dentro da política permitida',
                'tipo' => 'politica_ok',
                'horas_restantes' => $diferencaHoras,
                'limite_horas' => $limiteHoras
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao verificar política de cancelamento: " . $e->getMessage());
            return [
                'permitido' => false,
                'motivo' => 'Erro ao verificar política de cancelamento',
                'tipo' => 'erro_sistema'
            ];
        }
    }
    
    /**
     * Verificar política de transferência (24h de antecedência)
     * @param string $dataAula Data da aula
     * @param string $horaInicio Hora de início
     * @return array Resultado da verificação
     */
    private function verificarPoliticaTransferencia($dataAula, $horaInicio) {
        try {
            $dataHoraAula = strtotime($dataAula . ' ' . $horaInicio);
            $agora = time();
            $diferencaHoras = ($dataHoraAula - $agora) / 3600; // Diferença em horas
            
            $limiteHoras = 24; // Configurável
            
            if ($diferencaHoras < $limiteHoras) {
                return [
                    'permitido' => false,
                    'motivo' => "Transferência permitida apenas com {$limiteHoras}h de antecedência. Restam " . 
                               number_format($diferencaHoras, 1) . " horas para a aula.",
                    'tipo' => 'politica_transferencia',
                    'horas_restantes' => $diferencaHoras,
                    'limite_horas' => $limiteHoras
                ];
            }
            
            return [
                'permitido' => true,
                'motivo' => 'Transferência dentro da política permitida',
                'tipo' => 'politica_ok',
                'horas_restantes' => $diferencaHoras,
                'limite_horas' => $limiteHoras
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao verificar política de transferência: " . $e->getMessage());
            return [
                'permitido' => false,
                'motivo' => 'Erro ao verificar política de transferência',
                'tipo' => 'erro_sistema'
            ];
        }
    }
    
    /**
     * Verificar política de reagendamento (24h de antecedência)
     * @param string $dataAula Data da aula
     * @param string $horaInicio Hora de início
     * @return array Resultado da verificação
     */
    private function verificarPoliticaReagendamento($dataAula, $horaInicio) {
        try {
            $dataHoraAula = strtotime($dataAula . ' ' . $horaInicio);
            $agora = time();
            $diferencaHoras = ($dataHoraAula - $agora) / 3600; // Diferença em horas
            
            $limiteHoras = 24; // Configurável
            
            if ($diferencaHoras < $limiteHoras) {
                return [
                    'permitido' => false,
                    'motivo' => "Reagendamento permitido apenas com {$limiteHoras}h de antecedência. Restam " . 
                               number_format($diferencaHoras, 1) . " horas para a aula.",
                    'tipo' => 'politica_reagendamento',
                    'horas_restantes' => $diferencaHoras,
                    'limite_horas' => $limiteHoras
                ];
            }
            
            return [
                'permitido' => true,
                'motivo' => 'Reagendamento dentro da política permitida',
                'tipo' => 'politica_ok',
                'horas_restantes' => $diferencaHoras,
                'limite_horas' => $limiteHoras
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao verificar política de reagendamento: " . $e->getMessage());
            return [
                'permitido' => false,
                'motivo' => 'Erro ao verificar política de reagendamento',
                'tipo' => 'erro_sistema'
            ];
        }
    }
    
    /**
     * Verificar se usuário pode visualizar agendamento
     * @param int $aulaId ID da aula
     * @return array Resultado da verificação
     */
    public function podeVisualizarAgendamento($aulaId) {
        $user = $this->auth->getCurrentUser();
        
        if (!$user) {
            return [
                'permitido' => false,
                'motivo' => 'Usuário não autenticado',
                'tipo' => 'nao_autenticado'
            ];
        }
        
        // Admin e Secretária podem visualizar qualquer agendamento
        if (in_array($user['tipo'], ['admin', 'secretaria'])) {
            return [
                'permitido' => true,
                'motivo' => 'Usuário autorizado a visualizar agendamentos',
                'tipo' => 'permissao_concedida',
                'usuario_tipo' => $user['tipo']
            ];
        }
        
        $db = db();
        
        // Instrutor pode visualizar apenas suas próprias aulas
        if ($user['tipo'] === 'instrutor') {
            $instrutor = $db->fetch("SELECT id FROM instrutores WHERE usuario_id = ?", [$user['id']]);
            
            if (!$instrutor) {
                return [
                    'permitido' => false,
                    'motivo' => 'Instrutor não encontrado',
                    'tipo' => 'instrutor_nao_encontrado'
                ];
            }
            
            $aula = $db->fetch("SELECT instrutor_id FROM aulas WHERE id = ?", [$aulaId]);
            
            if (!$aula) {
                return [
                    'permitido' => false,
                    'motivo' => 'Aula não encontrada',
                    'tipo' => 'aula_nao_encontrada'
                ];
            }
            
            if ($aula['instrutor_id'] == $instrutor['id']) {
                return [
                    'permitido' => true,
                    'motivo' => 'Instrutor autorizado a visualizar sua própria aula',
                    'tipo' => 'permissao_concedida',
                    'usuario_tipo' => $user['tipo']
                ];
            } else {
                return [
                    'permitido' => false,
                    'motivo' => 'Instrutor só pode visualizar suas próprias aulas',
                    'tipo' => 'permissao_negada',
                    'usuario_tipo' => $user['tipo']
                ];
            }
        }
        
        // Aluno pode visualizar apenas suas próprias aulas
        if ($user['tipo'] === 'aluno') {
            $aluno = $db->fetch("SELECT id FROM alunos WHERE usuario_id = ?", [$user['id']]);
            
            if (!$aluno) {
                return [
                    'permitido' => false,
                    'motivo' => 'Aluno não encontrado',
                    'tipo' => 'aluno_nao_encontrado'
                ];
            }
            
            $aula = $db->fetch("SELECT aluno_id FROM aulas WHERE id = ?", [$aulaId]);
            
            if (!$aula) {
                return [
                    'permitido' => false,
                    'motivo' => 'Aula não encontrada',
                    'tipo' => 'aula_nao_encontrada'
                ];
            }
            
            if ($aula['aluno_id'] == $aluno['id']) {
                return [
                    'permitido' => true,
                    'motivo' => 'Aluno autorizado a visualizar sua própria aula',
                    'tipo' => 'permissao_concedida',
                    'usuario_tipo' => $user['tipo']
                ];
            } else {
                return [
                    'permitido' => false,
                    'motivo' => 'Aluno só pode visualizar suas próprias aulas',
                    'tipo' => 'permissao_negada',
                    'usuario_tipo' => $user['tipo']
                ];
            }
        }
        
        return [
            'permitido' => false,
            'motivo' => 'Usuário não autorizado a visualizar agendamentos',
            'tipo' => 'permissao_negada',
            'usuario_tipo' => $user['tipo']
        ];
    }
    
    /**
     * Obter permissões do usuário atual para agendamentos
     * @return array Permissões do usuário
     */
    public function obterPermissoesUsuario() {
        $user = $this->auth->getCurrentUser();
        
        if (!$user) {
            return [
                'criar' => false,
                'editar' => false,
                'cancelar' => false,
                'transferir' => false,
                'solicitar_reagendamento' => false,
                'solicitar_cancelamento' => false,
                'visualizar' => false,
                'usuario_tipo' => null
            ];
        }
        
        $permissoes = [
            'criar' => $this->podeCriarAgendamento()['permitido'],
            'editar' => false, // Será verificado por aula específica
            'cancelar' => false, // Será verificado por aula específica
            'transferir' => false, // Será verificado por aula específica
            'solicitar_reagendamento' => false, // Será verificado por aula específica
            'solicitar_cancelamento' => false, // Será verificado por aula específica
            'visualizar' => false, // Será verificado por aula específica
            'usuario_tipo' => $user['tipo']
        ];
        
        return $permissoes;
    }
}
?>
