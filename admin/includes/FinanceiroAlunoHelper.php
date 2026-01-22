<?php
/**
 * FinanceiroAlunoHelper - Helper para verificação de permissão financeira do aluno
 * 
 * REGRA GLOBAL DE BLOQUEIO PELO FINANCEIRO:
 * Nenhum avanço operacional (aulas, provas, exames etc.) pode acontecer 
 * se o financeiro do aluno não estiver OK.
 * 
 * Esta função centraliza a verificação de permissão financeira para:
 * - Agendamento de exames (médico, psicotécnico)
 * - Agendamento de aulas (futuro)
 * - Agendamento de provas (futuro)
 * 
 * REGRA ESPECÍFICA PARA EXAMES:
 * Bloquear se:
 * - Não houver nenhuma fatura lançada para o aluno
 * - Existir qualquer fatura em atraso (vencida)
 * 
 * Permitir se:
 * - Houver pelo menos uma fatura PAGA (ex.: Entrada)
 * - As demais estiverem ABERTAS mas com vencimento futuro (sem atraso)
 * 
 * Em outras palavras: ABERTA e ainda não vencida NÃO deve bloquear.
 * Só bloqueia por financeiro se tiver atraso ou nenhum lançamento.
 * 
 * @author Sistema CFC
 * @version 2.0
 * @since 2025-01-XX
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';

// Incluir FinanceiroService se existir
$financeiroServicePath = __DIR__ . '/FinanceiroService.php';
if (file_exists($financeiroServicePath)) {
    require_once $financeiroServicePath;
}

class FinanceiroAlunoHelper {
    
    /**
     * =====================================================
     * FUNÇÃO CENTRAL DE VALIDAÇÃO FINANCEIRA PARA EXAMES
     * =====================================================
     * 
     * Esta é a ÚNICA função usada para validar permissão financeira
     * para agendamento de exames (médico e psicotécnico).
     * 
     * ARQUIVOS QUE USAM ESTA FUNÇÃO:
     * - admin/api/exames_simple.php (backend - validação antes de inserir)
     * - admin/pages/historico-aluno.php (frontend - marca botões como bloqueados)
     * 
     * REGRA ESPECÍFICA PARA EXAMES:
     * - Bloquear se não houver nenhuma fatura lançada
     * - Bloquear se existir qualquer fatura em atraso (vencida)
     * - Permitir se houver pelo menos uma fatura PAGA e não houver faturas em atraso
     * - Faturas ABERTAS com vencimento futuro NÃO bloqueiam
     * 
     * CASOS DE BLOQUEIO:
     * 1. Sem matrícula ativa → BLOQUEIA
     * 2. Sem faturas lançadas → BLOQUEIA (NAO_LANCADO)
     * 3. Faturas em atraso → BLOQUEIA (EM_ATRASO)
     * 4. Sem nenhuma fatura paga → BLOQUEIA
     * 
     * CASOS DE LIBERAÇÃO:
     * 1. Tem pelo menos uma fatura PAGA + não há faturas em atraso → LIBERA
     * 2. Faturas ABERTAS com vencimento futuro são permitidas
     * 
     * @param int $alunoId ID do aluno
     * @return array{
     *   liberado: bool,           // true se pode avançar, false se bloqueado
     *   status: string,            // 'EM_DIA', 'EM_ATRASO', 'NAO_LANCADO', 'EM_ABERTO', 'PARCIAL', 'INADIMPLENTE'
     *   motivo: string             // mensagem amigável pronta para exibir no front
     * }
     */
    public static function verificarPermissaoFinanceiraAluno(int $alunoId): array {
        $db = Database::getInstance();
        $hoje = date('Y-m-d');
        
        try {
            // 1. Verificar se existe matrícula ativa
            $matriculaAtiva = $db->fetch("
                SELECT id, aluno_id, status, data_inicio
                FROM matriculas
                WHERE aluno_id = ? AND status = 'ativa'
                ORDER BY data_inicio DESC
                LIMIT 1
            ", [$alunoId]);
            
            if (!$matriculaAtiva) {
                return [
                    'liberado' => false,
                    'status' => 'SEM_MATRICULA',
                    'motivo' => 'Não é possível avançar: aluno não possui matrícula ativa.'
                ];
            }
            
            // 2. Buscar todas as faturas não canceladas do aluno
            // Reutilizar a mesma lógica do FinanceiroService
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
            ", [$alunoId]);
            
            $qtdFaturas = count($faturas);
            
            // 3. Se não houver nenhuma fatura → BLOQUEIA
            if ($qtdFaturas == 0) {
                return [
                    'liberado' => false,
                    'status' => 'NAO_LANCADO',
                    'motivo' => 'Não é possível avançar: ainda não existem faturas lançadas para este aluno.'
                ];
            }
            
            // 4. Calcular total pago
            $faturaIds = array_column($faturas, 'id');
            $totalPago = 0.0;
            
            if (!empty($faturaIds)) {
                $placeholders = implode(',', array_fill(0, count($faturaIds), '?'));
                $pagamentos = $db->fetchAll("
                    SELECT valor_pago
                    FROM pagamentos
                    WHERE fatura_id IN ($placeholders)
                ", $faturaIds);
                
                foreach ($pagamentos as $pagamento) {
                    $totalPago += (float)($pagamento['valor_pago'] ?? 0);
                }
            }
            
            // 5. Calcular total contratado e saldo aberto
            $totalContratado = 0.0;
            $qtdFaturasVencidas = 0;
            
            foreach ($faturas as $fatura) {
                $valorTotal = (float)($fatura['valor_total'] ?? 0);
                $totalContratado += $valorTotal;
                
                // Verificar se está vencida (status em 'aberta' ou 'parcial' e data_vencimento < hoje)
                $status = $fatura['status'] ?? '';
                $dataVencimento = $fatura['data_vencimento'] ?? null;
                
                if (in_array($status, ['aberta', 'parcial']) && $dataVencimento && $dataVencimento < $hoje) {
                    $qtdFaturasVencidas++;
                }
            }
            
            $saldoAberto = max(0, $totalContratado - $totalPago);
            
            // 6. Verificar se há faturas em atraso → BLOQUEIA
            if ($qtdFaturasVencidas > 0) {
                return [
                    'liberado' => false,
                    'status' => 'EM_ATRASO',
                    'motivo' => 'Não é possível avançar: existem faturas em atraso para este aluno.'
                ];
            }
            
            // 7. REGRA ESPECÍFICA PARA EXAMES:
            // Permitir se houver pelo menos uma fatura PAGA e não houver faturas em atraso
            // Faturas ABERTAS com vencimento futuro NÃO devem bloquear
            
            // Verificar se existe pelo menos uma fatura PAGA
            $temFaturaPaga = false;
            foreach ($faturas as $fatura) {
                $statusFatura = $fatura['status'] ?? '';
                // Considerar 'paga' ou verificar se total pago >= valor total
                if ($statusFatura === 'paga') {
                    $temFaturaPaga = true;
                    break;
                }
            }
            
            // Se não encontrou pelo status, verificar pelo total pago
            if (!$temFaturaPaga && $totalPago > 0) {
                // Verificar se alguma fatura foi totalmente paga
                foreach ($faturas as $fatura) {
                    $faturaId = $fatura['id'];
                    $valorFatura = (float)($fatura['valor_total'] ?? 0);
                    
                    // Buscar total pago desta fatura
                    $pagoFatura = $db->fetchColumn("
                        SELECT COALESCE(SUM(valor_pago), 0) 
                        FROM pagamentos 
                        WHERE fatura_id = ?
                    ", [$faturaId]);
                    
                    if ($pagoFatura >= $valorFatura && $valorFatura > 0) {
                        $temFaturaPaga = true;
                        break;
                    }
                }
            }
            
            // 8. Aplicar regra de liberação para exames
            // Liberar se: tem pelo menos uma fatura paga E não há faturas em atraso
            $liberado = $temFaturaPaga && ($qtdFaturasVencidas == 0);
            
            // 9. Determinar status para exibição (usar lógica do FinanceiroService)
            $statusFinanceiro = self::calcularStatusFinanceiro(
                $qtdFaturas,
                $saldoAberto,
                $qtdFaturasVencidas,
                $totalPago
            );
            
            // 10. Mapear status para formato padronizado
            $statusMapeado = self::mapearStatusFinanceiro($statusFinanceiro);
            
            // 11. Gerar motivo baseado na regra de exames
            if (!$liberado) {
                if (!$temFaturaPaga) {
                    $motivo = 'Não é possível avançar: ainda não existem faturas pagas para este aluno.';
                } else {
                    $motivo = 'Não é possível avançar: existem faturas em atraso para este aluno.';
                }
            } else {
                $motivo = 'Situação financeira OK. Aluno liberado para agendar exames.';
            }
            
            error_log('[VALIDACAO FINANCEIRA EXAMES] Aluno ' . $alunoId . ' - Liberado: ' . ($liberado ? 'SIM' : 'NÃO') . 
                     ' - Tem fatura paga: ' . ($temFaturaPaga ? 'SIM' : 'NÃO') . 
                     ' - Faturas vencidas: ' . $qtdFaturasVencidas . 
                     ' - Status: ' . $statusMapeado);
            
            return [
                'liberado' => $liberado,
                'status' => $statusMapeado,
                'motivo' => $motivo
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao verificar permissão financeira (aluno_id={$alunoId}): " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Em caso de erro, bloquear por segurança
            return [
                'liberado' => false,
                'status' => 'ERRO',
                'motivo' => 'Erro ao verificar situação financeira. Entre em contato com o suporte.'
            ];
        }
    }
    
    /**
     * Calcula o status financeiro baseado nas regras de negócio
     * (Reutiliza a mesma lógica do FinanceiroService)
     * 
     * @param int $qtdFaturas
     * @param float $saldoAberto
     * @param int $qtdFaturasVencidas
     * @param float $totalPago
     * @return string Status financeiro: 'nao_lancado', 'em_dia', 'inadimplente', 'parcial', 'em_aberto'
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
    
    /**
     * Mapeia status financeiro interno para formato padronizado
     * 
     * @param string $statusFinanceiro Status do FinanceiroService
     * @return string Status padronizado
     */
    private static function mapearStatusFinanceiro(string $statusFinanceiro): string {
        $mapa = [
            'nao_lancado' => 'NAO_LANCADO',
            'em_dia' => 'EM_DIA',
            'inadimplente' => 'EM_ATRASO',
            'parcial' => 'EM_ABERTO',
            'em_aberto' => 'EM_ABERTO'
        ];
        
        return $mapa[$statusFinanceiro] ?? 'EM_ABERTO';
    }
    
    /**
     * Gera mensagem de motivo baseado no status financeiro
     * 
     * @param string $statusFinanceiro
     * @param bool $liberado
     * @return string
     */
    private static function gerarMotivoBloqueio(string $statusFinanceiro, bool $liberado): string {
        if ($liberado) {
            return 'Situação financeira em dia. Aluno liberado para avançar.';
        }
        
        $motivos = [
            'nao_lancado' => 'Não é possível avançar: ainda não existem faturas lançadas para este aluno.',
            'inadimplente' => 'Não é possível avançar: existem faturas em atraso para este aluno.',
            'parcial' => 'Não é possível avançar: existem faturas pendentes de pagamento para este aluno.',
            'em_aberto' => 'Não é possível avançar: existem faturas em aberto para este aluno.'
        ];
        
        return $motivos[$statusFinanceiro] ?? 'Não é possível avançar: situação financeira não regularizada.';
    }
}

