<?php
/**
 * Guards de Exames e Inadimplência
 * Sistema de verificação de bloqueios para aulas teóricas
 * 
 * NOTA SOBRE CFC:
 * - Esta classe trabalha apenas com aluno_id, independente de CFC
 * - Não há dependência de CFC canônico ou valores fixos
 * - CFC canônico do CFC Bom Conselho é ID 36 (documentado em docs/)
 * - Migração CFC 1 → 36 é SEMPRE manual, via script documentado
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
     * Verificar se aluno tem exames OK para aulas teóricas
     * FUNÇÃO CENTRALIZADA - Usa a mesma lógica do histórico do aluno
     * 
     * Critério:
     * - Exame médico: status 'concluido' E resultado em ['apto', 'aprovado'] E tem resultado lançado
     * - Exame psicotécnico: status 'concluido' E resultado em ['apto', 'aprovado'] E tem resultado lançado
     * 
     * Compatibilidade: 'aprovado' é tratado como equivalente a 'apto' (valores antigos)
     * 
     * @param int $alunoId
     * @return bool
     */
    public static function alunoComExamesOkParaTeoricas($alunoId) {
        $db = db();
        
        // Buscar exames do aluno (buscar todos, não apenas concluídos, para verificar tem_resultado)
        $exames = $db->fetchAll("
            SELECT tipo, status, resultado, data_resultado
            FROM exames 
            WHERE aluno_id = ? 
            ORDER BY tipo, data_agendada DESC
        ", [$alunoId]);
        
        $medico = null;
        $psicotecnico = null;
        
        // Pegar o exame mais recente de cada tipo
        foreach ($exames as $exame) {
            if ($exame['tipo'] === 'medico' && !$medico) {
                $medico = $exame;
            } elseif ($exame['tipo'] === 'psicotecnico' && !$psicotecnico) {
                $psicotecnico = $exame;
            }
        }
        
        // Função helper para verificar se tem resultado lançado (mesma lógica do histórico)
        $temResultado = function($exame) {
            if (!$exame) return false;
            $resultado = $exame['resultado'] ?? null;
            $dataResultado = $exame['data_resultado'] ?? null;
            
            // Considera que tem resultado se:
            // 1. Campo resultado não está vazio/null e não é 'pendente' e está em valores válidos
            // 2. OU existe data_resultado preenchida
            if (!empty($resultado) && $resultado !== 'pendente' && 
                in_array($resultado, ['apto', 'inapto', 'inapto_temporario', 'aprovado', 'reprovado'])) {
                return true;
            } elseif (!empty($dataResultado)) {
                return true;
            }
            return false;
        };
        
        // Verificar se ambos têm resultado lançado
        $medicoTemResultado = $temResultado($medico);
        $psicotecnicoTemResultado = $temResultado($psicotecnico);
        
        // Verificar se resultados são aptos/aprovados (compatibilidade com valores antigos)
        $medicoApto = false;
        $psicotecnicoApto = false;
        
        if ($medicoTemResultado) {
            $resultadoMedico = $medico['resultado'] ?? '';
            $medicoApto = in_array($resultadoMedico, ['apto', 'aprovado']);
        }
        
        if ($psicotecnicoTemResultado) {
            $resultadoPsicotecnico = $psicotecnico['resultado'] ?? '';
            $psicotecnicoApto = in_array($resultadoPsicotecnico, ['apto', 'aprovado']);
        }
        
        // Exames OK = ambos têm resultado lançado E ambos são aptos/aprovados
        $examesOK = $medicoTemResultado && $medicoApto && 
                   $psicotecnicoTemResultado && $psicotecnicoApto;
        
        // Log para debug
        error_log("[GUARDS EXAMES] Aluno {$alunoId} - medico_tem_resultado=" . ($medicoTemResultado ? 'true' : 'false') . 
                 ", medico_apto=" . ($medicoApto ? 'true' : 'false') . 
                 ", psicotecnico_tem_resultado=" . ($psicotecnicoTemResultado ? 'true' : 'false') . 
                 ", psicotecnico_apto=" . ($psicotecnicoApto ? 'true' : 'false') . 
                 ", exames_ok=" . ($examesOK ? 'true' : 'false'));
        
        return $examesOK;
    }
    
    /**
     * Verificar se exames médico e psicotécnico estão OK
     * DEPRECATED: Use alunoComExamesOkParaTeoricas() para garantir consistência
     * Mantido para compatibilidade com código existente
     */
    public static function verificarExamesOK($alunoId) {
        // Usar função centralizada
        return self::alunoComExamesOkParaTeoricas($alunoId);
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
            'exames_ok' => self::alunoComExamesOkParaTeoricas($alunoId)
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
