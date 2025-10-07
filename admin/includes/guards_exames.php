<?php
/**
 * Guards de Exames e Inadimplência
 * Sistema de verificação de bloqueios para aulas teóricas
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';

class GuardsExames {
    
    /**
     * Verificar se aluno pode prosseguir com aulas teóricas
     */
    public static function verificarBloqueioTeorica($alunoId) {
        $db = db();
        
        // 1. Verificar se exames estão OK
        $examesOK = self::verificarExamesOK($alunoId);
        
        // 2. Verificar inadimplência (se flag estiver ativa)
        $inadimplenciaOK = self::verificarInadimplencia($alunoId);
        
        // 3. Determinar se pode prosseguir
        $podeProsseguir = $examesOK && $inadimplenciaOK['pode_prosseguir'];
        
        return [
            'pode_prosseguir' => $podeProsseguir,
            'exames_ok' => $examesOK,
            'inadimplencia_ok' => $inadimplenciaOK['pode_prosseguir'],
            'motivos_bloqueio' => array_merge(
                $examesOK ? [] : ['Exames médico e psicotécnico não concluídos'],
                $inadimplenciaOK['pode_prosseguir'] ? [] : [$inadimplenciaOK['motivo']]
            )
        ];
    }
    
    /**
     * Verificar se exames médico e psicotécnico estão OK
     */
    public static function verificarExamesOK($alunoId) {
        $db = db();
        
        // Buscar exames do aluno
        $exames = $db->fetchAll("
            SELECT tipo, status, resultado 
            FROM exames 
            WHERE aluno_id = ? AND status = 'concluido'
        ", [$alunoId]);
        
        $medico = null;
        $psicotecnico = null;
        
        foreach ($exames as $exame) {
            if ($exame['tipo'] === 'medico') {
                $medico = $exame;
            } elseif ($exame['tipo'] === 'psicotecnico') {
                $psicotecnico = $exame;
            }
        }
        
        // Exames OK = ambos aptos (inapto_temporario é considerado não apto para aulas teóricas)
        return ($medico && $medico['resultado'] === 'apto') && 
               ($psicotecnico && $psicotecnico['resultado'] === 'apto');
    }
    
    /**
     * Verificar inadimplência do aluno
     */
    public static function verificarInadimplencia($alunoId) {
        // Flag de bloqueio (simular como true por padrão)
        $bloquearTeoricaInadimplente = true; // TODO: Buscar de configurações
        
        if (!$bloquearTeoricaInadimplente) {
            return [
                'pode_prosseguir' => true,
                'motivo' => null,
                'inadimplente' => false
            ];
        }
        
        $db = db();
        
        // Verificar se aluno tem faturas em aberto/vencidas
        $faturasAbertas = $db->count('financeiro_faturas', 
            'aluno_id = ? AND status IN ("em_aberto", "vencida")', 
            [$alunoId]
        );
        
        $inadimplente = $faturasAbertas > 0;
        
        return [
            'pode_prosseguir' => !$inadimplente,
            'motivo' => $inadimplente ? 'Aluno inadimplente - regularize o financeiro' : null,
            'inadimplente' => $inadimplente,
            'faturas_abertas' => $faturasAbertas
        ];
    }
    
    /**
     * Obter status detalhado dos exames
     */
    public static function getStatusExames($alunoId) {
        $db = db();
        
        $exames = $db->fetchAll("
            SELECT * FROM exames 
            WHERE aluno_id = ? 
            ORDER BY tipo, data_agendada DESC
        ", [$alunoId]);
        
        $medico = null;
        $psicotecnico = null;
        
        foreach ($exames as $exame) {
            if ($exame['tipo'] === 'medico' && !$medico) {
                $medico = $exame;
            } elseif ($exame['tipo'] === 'psicotecnico' && !$psicotecnico) {
                $psicotecnico = $exame;
            }
        }
        
        return [
            'medico' => $medico,
            'psicotecnico' => $psicotecnico,
            'exames_ok' => self::verificarExamesOK($alunoId)
        ];
    }
    
    /**
     * Verificar se pode agendar aula teórica
     */
    public static function podeAgendarTeorica($alunoId) {
        $bloqueio = self::verificarBloqueioTeorica($alunoId);
        
        return [
            'pode_agendar' => $bloqueio['pode_prosseguir'],
            'motivo' => $bloqueio['pode_prosseguir'] ? null : implode(', ', $bloqueio['motivos_bloqueio'])
        ];
    }
}
?>
