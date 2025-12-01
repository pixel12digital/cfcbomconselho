<?php
/**
 * FinanceiroService - Service centralizado para cálculos e resumos financeiros
 * 
 * Centraliza funções de cálculo de resumos financeiros do aluno baseado em
 * financeiro_faturas + pagamentos.
 * 
 * USO:
 * - Modal de Detalhes do Aluno (card "Situação Financeira")
 * - Aba Matrícula do modal Editar Aluno (bloco "Resumo Financeiro do Aluno")
 * 
 * IMPORTANTE: Esta função NÃO persiste dados em alunos ou matriculas.
 * Apenas calcula e retorna resumo baseado nas tabelas financeiro_faturas e pagamentos.
 * 
 * @author Sistema CFC
 * @version 1.0
 * @since 2025-01-XX
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';

class FinanceiroService {
    
    /**
     * Calcula o resumo financeiro do aluno com base em financeiro_faturas + pagamentos.
     * 
     * Esta função é usada em:
     * - Modal de Detalhes do Aluno (card "Situação Financeira")
     * - Aba Matrícula do modal Editar Aluno (bloco "Resumo Financeiro do Aluno")
     * 
     * @param int $alunoId ID do aluno
     * @return array{
     *   total_contratado: float,
     *   total_pago: float,
     *   saldo_aberto: float,
     *   qtd_faturas: int,
     *   qtd_faturas_vencidas: int,
     *   proximo_vencimento: ?string, // YYYY-MM-DD ou null
     *   ultimo_pagamento: ?string,   // YYYY-MM-DD HH:ii:ss ou null
     *   status_financeiro: string    // 'nao_lancado', 'em_dia', 'em_aberto', 'parcial', 'inadimplente'
     * }
     */
    public static function calcularResumoFinanceiroAluno(int $alunoId): array {
        $db = Database::getInstance();
        $hoje = date('Y-m-d');
        
        try {
            // Buscar todas as faturas não canceladas do aluno (limitado para performance)
            $faturas = $db->fetchAll("
                SELECT 
                    id,
                    valor_total,
                    data_vencimento,
                    status
                FROM financeiro_faturas
                WHERE aluno_id = ?
                AND status != 'cancelada'
                ORDER BY data_vencimento ASC
                LIMIT 500
            ", [$alunoId]);
            
            // Calcular total contratado (soma de valor_total de todas as faturas não canceladas)
            $totalContratado = 0.0;
            $qtdFaturas = count($faturas);
            $qtdFaturasVencidas = 0;
            $proximoVencimento = null;
            $faturaIds = [];
            
            foreach ($faturas as $fatura) {
                $valorTotal = (float)($fatura['valor_total'] ?? 0);
                $totalContratado += $valorTotal;
                $faturaIds[] = $fatura['id'];
                
                // Verificar se está vencida (status em 'aberta' ou 'parcial' e data_vencimento < hoje)
                $status = $fatura['status'] ?? '';
                $dataVencimento = $fatura['data_vencimento'] ?? null;
                
                if (in_array($status, ['aberta', 'parcial']) && $dataVencimento && $dataVencimento < $hoje) {
                    $qtdFaturasVencidas++;
                }
                
                // Próximo vencimento: menor data_vencimento dentre faturas com status em ('aberta', 'parcial') e data_vencimento >= hoje
                if (in_array($status, ['aberta', 'parcial']) && $dataVencimento && $dataVencimento >= $hoje) {
                    if ($proximoVencimento === null || $dataVencimento < $proximoVencimento) {
                        $proximoVencimento = $dataVencimento;
                    }
                }
            }
            
            // Calcular total pago (soma dos pagamentos associados às faturas desse aluno)
            $totalPago = 0.0;
            $ultimoPagamento = null;
            
            if (!empty($faturaIds)) {
                // Limitar número de faturas para evitar queries muito grandes
                $faturaIdsLimitados = array_slice($faturaIds, 0, 500);
                $placeholders = implode(',', array_fill(0, count($faturaIdsLimitados), '?'));
                $pagamentos = $db->fetchAll("
                    SELECT 
                        valor_pago,
                        data_pagamento,
                        criado_em
                    FROM pagamentos
                    WHERE fatura_id IN ($placeholders)
                    ORDER BY data_pagamento DESC, criado_em DESC
                    LIMIT 1000
                ", $faturaIdsLimitados);
                
                foreach ($pagamentos as $pagamento) {
                    $totalPago += (float)($pagamento['valor_pago'] ?? 0);
                    
                    // Último pagamento: maior data_pagamento
                    if ($ultimoPagamento === null) {
                        $ultimoPagamento = $pagamento['data_pagamento'] ?? null;
                        if ($ultimoPagamento && isset($pagamento['criado_em'])) {
                            // Se tiver criado_em, usar para ter hora completa
                            $ultimoPagamento = $pagamento['criado_em'];
                        }
                    }
                }
            }
            
            // Calcular saldo em aberto (nunca negativo)
            $saldoAberto = max(0, $totalContratado - $totalPago);
            
            // Calcular status_financeiro
            $statusFinanceiro = self::calcularStatusFinanceiro(
                $qtdFaturas,
                $saldoAberto,
                $qtdFaturasVencidas,
                $totalPago
            );
            
            return [
                'total_contratado' => round($totalContratado, 2),
                'total_pago' => round($totalPago, 2),
                'saldo_aberto' => round($saldoAberto, 2),
                'qtd_faturas' => $qtdFaturas,
                'qtd_faturas_vencidas' => $qtdFaturasVencidas,
                'proximo_vencimento' => $proximoVencimento,
                'ultimo_pagamento' => $ultimoPagamento,
                'status_financeiro' => $statusFinanceiro
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao calcular resumo financeiro (aluno_id={$alunoId}): " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Retornar valores padrão em caso de erro
            return [
                'total_contratado' => 0.0,
                'total_pago' => 0.0,
                'saldo_aberto' => 0.0,
                'qtd_faturas' => 0,
                'qtd_faturas_vencidas' => 0,
                'proximo_vencimento' => null,
                'ultimo_pagamento' => null,
                'status_financeiro' => 'nao_lancado'
            ];
        }
    }
    
    /**
     * Calcula o status financeiro baseado nas regras de negócio
     * 
     * Regras:
     * - se qtd_faturas == 0 → 'nao_lancado'
     * - senão se saldo_aberto <= 0 e qtd_faturas > 0 → 'em_dia'
     * - senão se qtd_faturas_vencidas > 0 → 'inadimplente'
     * - senão se total_pago > 0 e saldo_aberto > 0 → 'parcial'
     * - senão → 'em_aberto'
     */
    private static function calcularStatusFinanceiro(
        int $qtdFaturas,
        float $saldoAberto,
        int $qtdFaturasVencidas,
        float $totalPago
    ): string {
        if ($qtdFaturas == 0) {
            return 'nao_lancado';
        }
        
        if ($saldoAberto <= 0 && $qtdFaturas > 0) {
            return 'em_dia';
        }
        
        if ($qtdFaturasVencidas > 0) {
            return 'inadimplente';
        }
        
        if ($totalPago > 0 && $saldoAberto > 0) {
            return 'parcial';
        }
        
        return 'em_aberto';
    }
}

