<?php
/**
 * FinanceiroRulesService - Service centralizado para validações financeiras
 * 
 * Centraliza todas as regras que determinam se um aluno pode agendar aulas/exames
 * baseado na situação financeira.
 * 
 * Este service consolida a lógica que estava em:
 * - admin/pages/alunos.php (função atualizarResumoFinanceiroAluno - JavaScript)
 * - includes/guards/AgendamentoGuards.php (verificarSituacaoFinanceira)
 * 
 * Reutiliza a mesma lógica de cálculo do card "Situação Financeira" do modal de alunos.
 * 
 * @author Sistema CFC
 * @version 1.0
 * @since 2025-01-28
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';

class FinanceiroRulesService {
    private $db;
    
    public function __construct() {
        $this->db = db();
    }
    
    /**
     * Verifica se o aluno pode agendar aula/exame do ponto de vista financeiro
     * 
     * Reutiliza a mesma lógica do card "Situação Financeira" do modal de alunos:
     * - Prioridade 1: Se tem faturas vencidas → "Em atraso" (bloquear)
     * - Prioridade 2: Se tem faturas abertas com vencimento >= hoje → "Em aberto" (permitir)
     * - Prioridade 3: Se tem faturas pagas → "Quitado" (permitir)
     * - Default: "Não lançado" (bloquear)
     * 
     * @param int $alunoId ID do aluno
     * @return array ['ok' => bool, 'codigo' => string, 'mensagem' => string]
     */
    public function podeAgendar(int $alunoId): array {
        try {
            // Verificar se aluno existe
            $aluno = $this->db->fetch("SELECT id FROM alunos WHERE id = ?", [$alunoId]);
            if (!$aluno) {
                return [
                    'ok' => false,
                    'codigo' => 'ALUNO_NAO_ENCONTRADO',
                    'mensagem' => 'Aluno não encontrado'
                ];
            }
            
            // Buscar faturas do aluno
            // Tentar primeiro financeiro_faturas (tabela nova), depois faturas (tabela antiga)
            $faturas = $this->buscarFaturasAluno($alunoId);
            
            // Se não encontrou faturas, situação é "Não lançado"
            if (empty($faturas)) {
                return [
                    'ok' => false,
                    'codigo' => 'NAO_LANCADO',
                    'mensagem' => 'Financeiro não lançado para este aluno. Cadastre o plano financeiro antes de agendar aulas ou exames.'
                ];
            }
            
            // Calcular status seguindo a mesma prioridade do JavaScript
            $hoje = new DateTime('today');
            $status = $this->calcularStatusFinanceiro($faturas, $hoje);
            
            // Mapear status para regras de bloqueio
            switch ($status) {
                case 'em_atraso':
                    // Calcular valor total em atraso
                    $valorAtraso = $this->calcularValorAtraso($faturas, $hoje);
                    return [
                        'ok' => false,
                        'codigo' => 'INADIMPLENTE',
                        'mensagem' => "Agendamento bloqueado: aluno com parcelas em atraso (R$ " . 
                                     number_format($valorAtraso, 2, ',', '.') . 
                                     "). Regularize o financeiro para continuar."
                    ];
                    
                case 'em_aberto':
                case 'quitado':
                    return [
                        'ok' => true,
                        'codigo' => 'FINANCEIRO_EM_DIA',
                        'mensagem' => 'Financeiro em dia.'
                    ];
                    
                case 'nao_lancado':
                default:
                    return [
                        'ok' => false,
                        'codigo' => 'NAO_LANCADO',
                        'mensagem' => 'Financeiro não lançado para este aluno. Cadastre o plano financeiro antes de agendar aulas ou exames.'
                    ];
            }
            
        } catch (Exception $e) {
            error_log("Erro ao verificar situação financeira (aluno_id={$alunoId}): " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            return [
                'ok' => false,
                'codigo' => 'ERRO_VERIFICACAO',
                'mensagem' => 'Erro ao verificar situação financeira. Tente novamente ou contate o suporte.'
            ];
        }
    }
    
    /**
     * Busca faturas do aluno, tentando primeiro financeiro_faturas, depois faturas
     * 
     * @param int $alunoId ID do aluno
     * @return array Lista de faturas
     */
    private function buscarFaturasAluno(int $alunoId): array {
        // Verificar quais tabelas existem
        $temFinanceiroFaturas = $this->tabelaExiste('financeiro_faturas');
        $temFaturas = $this->tabelaExiste('faturas');
        
        if ($temFinanceiroFaturas) {
            // Usar financeiro_faturas (tabela nova)
            return $this->buscarFaturasFinanceiroFaturas($alunoId);
        } elseif ($temFaturas) {
            // Usar faturas (tabela antiga)
            return $this->buscarFaturasAntigas($alunoId);
        }
        
        // Nenhuma tabela encontrada
        return [];
    }
    
    /**
     * Busca faturas da tabela financeiro_faturas
     */
    private function buscarFaturasFinanceiroFaturas(int $alunoId): array {
        try {
            // Verificar quais colunas existem
            $colunasExistentes = $this->db->fetchAll("
                SELECT COLUMN_NAME 
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'financeiro_faturas'
                AND COLUMN_NAME IN ('data_vencimento', 'vencimento', 'status', 'valor_total')
            ");
            
            $colunas = array_column($colunasExistentes, 'COLUMN_NAME');
            $temDataVencimento = in_array('data_vencimento', $colunas);
            $temVencimento = in_array('vencimento', $colunas);
            $campoVencimento = $temDataVencimento ? 'data_vencimento' : ($temVencimento ? 'vencimento' : null);
            
            if (!$campoVencimento) {
                return [];
            }
            
            $faturas = $this->db->fetchAll("
                SELECT 
                    id,
                    aluno_id,
                    status,
                    valor_total,
                    {$campoVencimento} as vencimento
                FROM financeiro_faturas 
                WHERE aluno_id = ? 
                AND status != 'cancelada'
                ORDER BY {$campoVencimento} DESC
            ", [$alunoId]);
            
            return $faturas;
            
        } catch (Exception $e) {
            error_log("Erro ao buscar faturas de financeiro_faturas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Busca faturas da tabela faturas (antiga)
     */
    private function buscarFaturasAntigas(int $alunoId): array {
        try {
            $faturas = $this->db->fetchAll("
                SELECT 
                    id,
                    aluno_id,
                    status,
                    valor_liquido as valor_total,
                    vencimento
                FROM faturas 
                WHERE aluno_id = ? 
                AND status != 'cancelada'
                ORDER BY vencimento DESC
            ", [$alunoId]);
            
            return $faturas;
            
        } catch (Exception $e) {
            error_log("Erro ao buscar faturas de faturas (antiga): " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Verifica se uma tabela existe
     */
    private function tabelaExiste(string $tabela): bool {
        try {
            $resultado = $this->db->fetch("
                SELECT COUNT(*) as existe 
                FROM information_schema.tables 
                WHERE table_schema = DATABASE() 
                AND table_name = ?
            ", [$tabela]);
            
            return $resultado && (int)$resultado['existe'] > 0;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Calcula o status financeiro seguindo a mesma lógica do JavaScript
     * 
     * Prioridade:
     * 1. Se tem faturas vencidas → "em_atraso"
     * 2. Se tem faturas abertas com vencimento >= hoje → "em_aberto"
     * 3. Se tem faturas pagas → "quitado"
     * 4. Default → "nao_lancado"
     */
    private function calcularStatusFinanceiro(array $faturas, DateTime $hoje): string {
        // Prioridade 1: Verificar se há faturas vencidas ou abertas com vencimento < hoje
        $temVencida = false;
        foreach ($faturas as $fatura) {
            if ($fatura['status'] === 'vencida') {
                $temVencida = true;
                break;
            }
            if ($fatura['status'] === 'aberta' && !empty($fatura['vencimento'])) {
                $dataVencimento = new DateTime($fatura['vencimento']);
                $dataVencimento->setTime(0, 0, 0);
                if ($dataVencimento < $hoje) {
                    $temVencida = true;
                    break;
                }
            }
        }
        
        if ($temVencida) {
            return 'em_atraso';
        }
        
        // Prioridade 2: Verificar se há faturas abertas com vencimento >= hoje
        $temAberta = false;
        foreach ($faturas as $fatura) {
            if ($fatura['status'] === 'aberta' && !empty($fatura['vencimento'])) {
                $dataVencimento = new DateTime($fatura['vencimento']);
                $dataVencimento->setTime(0, 0, 0);
                if ($dataVencimento >= $hoje) {
                    $temAberta = true;
                    break;
                }
            }
        }
        
        if ($temAberta) {
            return 'em_aberto';
        }
        
        // Prioridade 3: Verificar se há faturas pagas
        $temPaga = false;
        foreach ($faturas as $fatura) {
            if ($fatura['status'] === 'paga') {
                $temPaga = true;
                break;
            }
        }
        
        if ($temPaga) {
            return 'quitado';
        }
        
        // Default: não lançado
        return 'nao_lancado';
    }
    
    /**
     * Calcula o valor total em atraso
     */
    private function calcularValorAtraso(array $faturas, DateTime $hoje): float {
        $valorTotal = 0.0;
        
        foreach ($faturas as $fatura) {
            if ($fatura['status'] === 'vencida') {
                $valorTotal += (float)($fatura['valor_total'] ?? 0);
            } elseif ($fatura['status'] === 'aberta' && !empty($fatura['vencimento'])) {
                $dataVencimento = new DateTime($fatura['vencimento']);
                $dataVencimento->setTime(0, 0, 0);
                if ($dataVencimento < $hoje) {
                    $valorTotal += (float)($fatura['valor_total'] ?? 0);
                }
            }
        }
        
        return $valorTotal;
    }
}

