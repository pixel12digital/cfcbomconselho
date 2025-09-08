<?php
/**
 * Sistema de Controle de Limite de Aulas
 * 
 * Impede o agendamento de aulas além do limite configurado
 * para cada categoria de habilitação.
 */

require_once 'sistema_matricula.php';
require_once 'configuracoes_categorias.php';

class ControleLimiteAulas {
    
    /**
     * Verificar se pode agendar aula antes do agendamento
     */
    public static function verificarLimiteAgendamento($alunoId, $tipoAula, $tipoVeiculo = null) {
        $info = SistemaMatricula::getInfoMatricula($alunoId);
        if (!$info) {
            return ['pode_agendar' => false, 'motivo' => 'Aluno não encontrado'];
        }
        
        $configuracao = $info['aluno'];
        
        // Verificar aulas teóricas
        if ($tipoAula === 'teorica') {
            $aulasTeoricas = self::contarAulasPorTipo($info['aulas'], 'teorica');
            
            if ($aulasTeoricas >= $configuracao['horas_teoricas']) {
                return [
                    'pode_agendar' => false, 
                    'motivo' => 'Limite de aulas teóricas atingido',
                    'limite' => $configuracao['horas_teoricas'],
                    'atual' => $aulasTeoricas
                ];
            }
            
            return [
                'pode_agendar' => true,
                'limite' => $configuracao['horas_teoricas'],
                'atual' => $aulasTeoricas,
                'restantes' => $configuracao['horas_teoricas'] - $aulasTeoricas
            ];
        }
        
        // Verificar aulas práticas por tipo de veículo
        if ($tipoAula === 'pratica' && $tipoVeiculo) {
            $campoVeiculo = 'horas_praticas_' . $tipoVeiculo;
            $limiteVeiculo = $configuracao[$campoVeiculo] ?? 0;
            
            if ($limiteVeiculo <= 0) {
                return [
                    'pode_agendar' => false,
                    'motivo' => 'Tipo de veículo não configurado para esta categoria'
                ];
            }
            
            $aulasVeiculo = self::contarAulasPorTipoVeiculo($info['aulas'], 'pratica', $tipoVeiculo);
            
            if ($aulasVeiculo >= $limiteVeiculo) {
                return [
                    'pode_agendar' => false,
                    'motivo' => "Limite de aulas práticas de {$tipoVeiculo} atingido",
                    'limite' => $limiteVeiculo,
                    'atual' => $aulasVeiculo
                ];
            }
            
            return [
                'pode_agendar' => true,
                'limite' => $limiteVeiculo,
                'atual' => $aulasVeiculo,
                'restantes' => $limiteVeiculo - $aulasVeiculo
            ];
        }
        
        return ['pode_agendar' => false, 'motivo' => 'Parâmetros inválidos'];
    }
    
    /**
     * Contar aulas por tipo
     */
    private static function contarAulasPorTipo($aulas, $tipoAula) {
        return array_sum(array_column(
            array_filter($aulas, fn($a) => $a['tipo_aula'] === $tipoAula),
            'quantidade'
        ));
    }
    
    /**
     * Contar aulas por tipo de veículo
     */
    private static function contarAulasPorTipoVeiculo($aulas, $tipoAula, $tipoVeiculo) {
        return array_sum(array_column(
            array_filter($aulas, fn($a) => 
                $a['tipo_aula'] === $tipoAula && $a['tipo_veiculo'] === $tipoVeiculo
            ),
            'quantidade'
        ));
    }
    
    /**
     * Obter resumo de limites para um aluno
     */
    public static function getResumoLimites($alunoId) {
        $info = SistemaMatricula::getInfoMatricula($alunoId);
        if (!$info) return null;
        
        $configuracao = $info['aluno'];
        $resumo = [
            'aluno' => $info['aluno'],
            'teoricas' => null,
            'praticas' => []
        ];
        
        // Aulas teóricas
        if ($configuracao['horas_teoricas'] > 0) {
            $aulasTeoricas = self::contarAulasPorTipo($info['aulas'], 'teorica');
            $resumo['teoricas'] = [
                'limite' => $configuracao['horas_teoricas'],
                'atual' => $aulasTeoricas,
                'restantes' => max(0, $configuracao['horas_teoricas'] - $aulasTeoricas),
                'percentual' => min(100, ($aulasTeoricas / $configuracao['horas_teoricas']) * 100)
            ];
        }
        
        // Aulas práticas por tipo de veículo
        $tiposVeiculo = ['moto', 'carro', 'carga', 'passageiros', 'combinacao'];
        
        foreach ($tiposVeiculo as $tipoVeiculo) {
            $campoVeiculo = 'horas_praticas_' . $tipoVeiculo;
            $limiteVeiculo = $configuracao[$campoVeiculo] ?? 0;
            
            if ($limiteVeiculo > 0) {
                $aulasVeiculo = self::contarAulasPorTipoVeiculo($info['aulas'], 'pratica', $tipoVeiculo);
                $resumo['praticas'][$tipoVeiculo] = [
                    'limite' => $limiteVeiculo,
                    'atual' => $aulasVeiculo,
                    'restantes' => max(0, $limiteVeiculo - $aulasVeiculo),
                    'percentual' => min(100, ($aulasVeiculo / $limiteVeiculo) * 100)
                ];
            }
        }
        
        return $resumo;
    }
    
    /**
     * Validar agendamento antes de salvar
     */
    public static function validarAgendamento($dadosAgendamento) {
        $alunoId = $dadosAgendamento['aluno_id'] ?? null;
        $tipoAula = $dadosAgendamento['tipo_aula'] ?? null;
        $tipoVeiculo = $dadosAgendamento['tipo_veiculo'] ?? null;
        
        if (!$alunoId || !$tipoAula) {
            return ['valido' => false, 'erro' => 'Dados obrigatórios não fornecidos'];
        }
        
        $verificacao = self::verificarLimiteAgendamento($alunoId, $tipoAula, $tipoVeiculo);
        
        if (!$verificacao['pode_agendar']) {
            return [
                'valido' => false, 
                'erro' => $verificacao['motivo'],
                'detalhes' => $verificacao
            ];
        }
        
        return [
            'valido' => true,
            'detalhes' => $verificacao
        ];
    }
    
    /**
     * Obter próximas aulas disponíveis para agendamento
     */
    public static function getProximasAulasDisponiveis($alunoId) {
        $resumo = self::getResumoLimites($alunoId);
        if (!$resumo) return [];
        
        $aulasDisponiveis = [];
        
        // Aulas teóricas disponíveis
        if ($resumo['teoricas'] && $resumo['teoricas']['restantes'] > 0) {
            $aulasDisponiveis[] = [
                'tipo_aula' => 'teorica',
                'tipo_veiculo' => null,
                'restantes' => $resumo['teoricas']['restantes'],
                'total' => $resumo['teoricas']['limite'],
                'atual' => $resumo['teoricas']['atual']
            ];
        }
        
        // Aulas práticas disponíveis
        foreach ($resumo['praticas'] as $tipoVeiculo => $dados) {
            if ($dados['restantes'] > 0) {
                $aulasDisponiveis[] = [
                    'tipo_aula' => 'pratica',
                    'tipo_veiculo' => $tipoVeiculo,
                    'restantes' => $dados['restantes'],
                    'total' => $dados['limite'],
                    'atual' => $dados['atual']
                ];
            }
        }
        
        return $aulasDisponiveis;
    }
    
    /**
     * Verificar se aluno completou todas as aulas necessárias
     */
    public static function verificarCompletude($alunoId) {
        $resumo = self::getResumoLimites($alunoId);
        if (!$resumo) return false;
        
        // Verificar aulas teóricas
        if ($resumo['teoricas'] && $resumo['teoricas']['restantes'] > 0) {
            return false;
        }
        
        // Verificar aulas práticas
        foreach ($resumo['praticas'] as $dados) {
            if ($dados['restantes'] > 0) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Obter progresso geral do aluno
     */
    public static function getProgressoGeral($alunoId) {
        $resumo = self::getResumoLimites($alunoId);
        if (!$resumo) return null;
        
        $totalNecessarias = 0;
        $totalConcluidas = 0;
        
        // Contar teóricas
        if ($resumo['teoricas']) {
            $totalNecessarias += $resumo['teoricas']['limite'];
            $totalConcluidas += $resumo['teoricas']['atual'];
        }
        
        // Contar práticas
        foreach ($resumo['praticas'] as $dados) {
            $totalNecessarias += $dados['limite'];
            $totalConcluidas += $dados['atual'];
        }
        
        if ($totalNecessarias == 0) return 100;
        
        return [
            'percentual' => min(100, ($totalConcluidas / $totalNecessarias) * 100),
            'total_necessarias' => $totalNecessarias,
            'total_concluidas' => $totalConcluidas,
            'total_restantes' => max(0, $totalNecessarias - $totalConcluidas),
            'completo' => $totalConcluidas >= $totalNecessarias
        ];
    }
    
    /**
     * Gerar relatório de limites para um aluno
     */
    public static function gerarRelatorioLimites($alunoId) {
        $resumo = self::getResumoLimites($alunoId);
        $progresso = self::getProgressoGeral($alunoId);
        
        if (!$resumo || !$progresso) return null;
        
        return [
            'aluno' => $resumo['aluno'],
            'progresso_geral' => $progresso,
            'teoricas' => $resumo['teoricas'],
            'praticas' => $resumo['praticas'],
            'completo' => $progresso['completo'],
            'gerado_em' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Verificar se pode cancelar aula
     */
    public static function podeCancelarAula($aulaId) {
        $aula = db()->fetch("
            SELECT a.*, al.categoria_cnh, cc.*
            FROM aulas a
            LEFT JOIN alunos al ON a.aluno_id = al.id
            LEFT JOIN configuracoes_categorias cc ON al.configuracao_categoria_id = cc.id
            WHERE a.id = ?
        ", [$aulaId]);
        
        if (!$aula) return false;
        
        // Só pode cancelar se estiver agendada
        return $aula['status'] === 'agendada';
    }
    
    /**
     * Cancelar aula e liberar slot
     */
    public static function cancelarAula($aulaId) {
        try {
            db()->beginTransaction();
            
            // Buscar aula
            $aula = db()->fetch("SELECT * FROM aulas WHERE id = ?", [$aulaId]);
            if (!$aula) {
                throw new Exception('Aula não encontrada');
            }
            
            if ($aula['status'] !== 'agendada') {
                throw new Exception('Apenas aulas agendadas podem ser canceladas');
            }
            
            // Atualizar status da aula
            db()->execute("
                UPDATE aulas 
                SET status = 'cancelada', updated_at = NOW() 
                WHERE id = ?
            ", [$aulaId]);
            
            // Liberar slot se existir
            if ($aula['slot_id']) {
                db()->execute("
                    UPDATE aulas_slots 
                    SET status = 'pendente', aula_id = NULL, updated_at = NOW() 
                    WHERE id = ?
                ", [$aula['slot_id']]);
            }
            
            db()->commit();
            
            return ['success' => true, 'message' => 'Aula cancelada com sucesso'];
            
        } catch (Exception $e) {
            db()->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
