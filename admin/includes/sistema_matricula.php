<?php
/**
 * Sistema de Matrícula com Configurações Automáticas
 * 
 * Quando um aluno é cadastrado, o sistema automaticamente aplica
 * as configurações da categoria selecionada.
 */

// Incluir classe de configurações
require_once 'configuracoes_categorias.php';

class SistemaMatricula {
    
    /**
     * Processar matrícula de novo aluno
     */
    public static function processarMatricula($dadosAluno) {
        try {
            // Validar dados do aluno
            $erros = self::validarDadosAluno($dadosAluno);
            if (!empty($erros)) {
                return ['success' => false, 'errors' => $erros];
            }
            
            // Obter configuração da categoria
            $configuracao = ConfiguracoesCategorias::getConfiguracao($dadosAluno['categoria_cnh']);
            if (!$configuracao) {
                return ['success' => false, 'errors' => ['Categoria não encontrada nas configurações']];
            }
            
            // Iniciar transação
            db()->beginTransaction();
            
            try {
                // Inserir aluno
                $alunoId = db()->execute("
                    INSERT INTO alunos (
                        nome, cpf, rg, data_nascimento, categoria_cnh, cfc_id,
                        endereco, telefone, email, status, configuracao_categoria_id,
                        created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'ativo', ?, NOW(), NOW())
                ", [
                    $dadosAluno['nome'],
                    $dadosAluno['cpf'],
                    $dadosAluno['rg'] ?? null,
                    $dadosAluno['data_nascimento'] ?? null,
                    $dadosAluno['categoria_cnh'],
                    $dadosAluno['cfc_id'],
                    $dadosAluno['endereco'] ?? null,
                    $dadosAluno['telefone'] ?? null,
                    $dadosAluno['email'] ?? null,
                    $configuracao['id']
                ]);
                
                // Criar slots de aulas baseados na configuração
                self::criarSlotsAulas($alunoId, $configuracao);
                
                // Confirmar transação
                db()->commit();
                
                return [
                    'success' => true, 
                    'aluno_id' => $alunoId,
                    'configuracao' => $configuracao,
                    'message' => 'Aluno matriculado com sucesso!'
                ];
                
            } catch (Exception $e) {
                db()->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'errors' => ['Erro interno: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Criar slots de aulas baseados na configuração
     */
    private static function criarSlotsAulas($alunoId, $configuracao) {
        // Criar aulas teóricas (se houver)
        if ($configuracao['horas_teoricas'] > 0) {
            for ($i = 1; $i <= $configuracao['horas_teoricas']; $i++) {
                db()->execute("
                    INSERT INTO aulas_slots (
                        aluno_id, tipo_aula, tipo_veiculo, status, 
                        ordem, configuracao_id, created_at
                    ) VALUES (?, 'teorica', NULL, 'pendente', ?, ?, NOW())
                ", [$alunoId, $i, $configuracao['id']]);
            }
        }
        
        // Criar aulas práticas por tipo de veículo
        $tiposVeiculo = [
            'horas_praticas_moto' => 'moto',
            'horas_praticas_carro' => 'carro',
            'horas_praticas_carga' => 'carga',
            'horas_praticas_passageiros' => 'passageiros',
            'horas_praticas_combinacao' => 'combinacao'
        ];
        
        $ordem = 1;
        foreach ($tiposVeiculo as $campo => $tipoVeiculo) {
            $quantidade = $configuracao[$campo] ?? 0;
            
            for ($i = 1; $i <= $quantidade; $i++) {
                db()->execute("
                    INSERT INTO aulas_slots (
                        aluno_id, tipo_aula, tipo_veiculo, status, 
                        ordem, configuracao_id, created_at
                    ) VALUES (?, 'pratica', ?, 'pendente', ?, ?, NOW())
                ", [$alunoId, $tipoVeiculo, $ordem, $configuracao['id']]);
                $ordem++;
            }
        }
    }
    
    /**
     * Validar dados do aluno
     */
    private static function validarDadosAluno($dados) {
        $erros = [];
        
        if (empty($dados['nome'])) {
            $erros[] = 'Nome é obrigatório';
        }
        
        if (empty($dados['cpf'])) {
            $erros[] = 'CPF é obrigatório';
        } elseif (!self::validarCPF($dados['cpf'])) {
            $erros[] = 'CPF inválido';
        }
        
        if (empty($dados['categoria_cnh'])) {
            $erros[] = 'Categoria CNH é obrigatória';
        } elseif (!ConfiguracoesCategorias::categoriaExiste($dados['categoria_cnh'])) {
            $erros[] = 'Categoria não configurada no sistema';
        }
        
        if (empty($dados['cfc_id'])) {
            $erros[] = 'CFC é obrigatório';
        }
        
        // Verificar se CPF já existe
        $cpfExistente = db()->fetchColumn("
            SELECT COUNT(*) FROM alunos WHERE cpf = ?
        ", [$dados['cpf']]);
        
        if ($cpfExistente > 0) {
            $erros[] = 'CPF já cadastrado no sistema';
        }
        
        return $erros;
    }
    
    /**
     * Validar CPF
     */
    private static function validarCPF($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        if (strlen($cpf) != 11) return false;
        
        // Verificar se todos os dígitos são iguais
        if (preg_match('/(\d)\1{10}/', $cpf)) return false;
        
        // Validar dígitos verificadores
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) return false;
        }
        
        return true;
    }
    
    /**
     * Obter informações da matrícula
     */
    public static function getInfoMatricula($alunoId) {
        $aluno = db()->fetch("
            SELECT a.*, c.nome as cfc_nome, cc.*
            FROM alunos a
            LEFT JOIN cfcs c ON a.cfc_id = c.id
            LEFT JOIN configuracoes_categorias cc ON a.configuracao_categoria_id = cc.id
            WHERE a.id = ?
        ", [$alunoId]);
        
        if (!$aluno) return null;
        
        // Contar slots de aulas
        $slots = db()->fetchAll("
            SELECT tipo_aula, tipo_veiculo, status, COUNT(*) as quantidade
            FROM aulas_slots 
            WHERE aluno_id = ?
            GROUP BY tipo_aula, tipo_veiculo, status
        ", [$alunoId]);
        
        // Contar aulas agendadas/concluídas
        $aulas = db()->fetchAll("
            SELECT tipo_aula, tipo_veiculo, status, COUNT(*) as quantidade
            FROM aulas 
            WHERE aluno_id = ?
            GROUP BY tipo_aula, tipo_veiculo, status
        ", [$alunoId]);
        
        return [
            'aluno' => $aluno,
            'slots' => $slots,
            'aulas' => $aulas
        ];
    }
    
    /**
     * Verificar se pode agendar aula
     */
    public static function podeAgendarAula($alunoId, $tipoAula, $tipoVeiculo = null) {
        $info = self::getInfoMatricula($alunoId);
        if (!$info) return false;
        
        $configuracao = $info['aluno'];
        
        // Verificar limite de aulas teóricas
        if ($tipoAula === 'teorica') {
            $aulasTeoricas = array_sum(array_column(
                array_filter($info['aulas'], fn($a) => $a['tipo_aula'] === 'teorica'), 
                'quantidade'
            ));
            
            return $aulasTeoricas < $configuracao['horas_teoricas'];
        }
        
        // Verificar limite de aulas práticas por tipo de veículo
        if ($tipoAula === 'pratica' && $tipoVeiculo) {
            $campoVeiculo = 'horas_praticas_' . $tipoVeiculo;
            $limiteVeiculo = $configuracao[$campoVeiculo] ?? 0;
            
            if ($limiteVeiculo <= 0) return false;
            
            $aulasVeiculo = array_sum(array_column(
                array_filter($info['aulas'], fn($a) => 
                    $a['tipo_aula'] === 'pratica' && $a['tipo_veiculo'] === $tipoVeiculo
                ), 
                'quantidade'
            ));
            
            return $aulasVeiculo < $limiteVeiculo;
        }
        
        return false;
    }
    
    /**
     * Obter próximas aulas disponíveis para agendamento
     */
    public static function getProximasAulasDisponiveis($alunoId) {
        $info = self::getInfoMatricula($alunoId);
        if (!$info) return [];
        
        $configuracao = $info['aluno'];
        $aulasDisponiveis = [];
        
        // Verificar aulas teóricas
        if ($configuracao['horas_teoricas'] > 0) {
            $aulasTeoricas = array_sum(array_column(
                array_filter($info['aulas'], fn($a) => $a['tipo_aula'] === 'teorica'), 
                'quantidade'
            ));
            
            if ($aulasTeoricas < $configuracao['horas_teoricas']) {
                $aulasDisponiveis[] = [
                    'tipo_aula' => 'teorica',
                    'tipo_veiculo' => null,
                    'restantes' => $configuracao['horas_teoricas'] - $aulasTeoricas,
                    'total' => $configuracao['horas_teoricas']
                ];
            }
        }
        
        // Verificar aulas práticas por tipo de veículo
        $tiposVeiculo = ['moto', 'carro', 'carga', 'passageiros', 'combinacao'];
        
        foreach ($tiposVeiculo as $tipoVeiculo) {
            $campoVeiculo = 'horas_praticas_' . $tipoVeiculo;
            $limiteVeiculo = $configuracao[$campoVeiculo] ?? 0;
            
            if ($limiteVeiculo > 0) {
                $aulasVeiculo = array_sum(array_column(
                    array_filter($info['aulas'], fn($a) => 
                        $a['tipo_aula'] === 'pratica' && $a['tipo_veiculo'] === $tipoVeiculo
                    ), 
                    'quantidade'
                ));
                
                if ($aulasVeiculo < $limiteVeiculo) {
                    $aulasDisponiveis[] = [
                        'tipo_aula' => 'pratica',
                        'tipo_veiculo' => $tipoVeiculo,
                        'restantes' => $limiteVeiculo - $aulasVeiculo,
                        'total' => $limiteVeiculo
                    ];
                }
            }
        }
        
        return $aulasDisponiveis;
    }
    
    /**
     * Atualizar configuração de aluno existente
     */
    public static function atualizarConfiguracaoAluno($alunoId, $novaCategoria) {
        $configuracao = ConfiguracoesCategorias::getConfiguracao($novaCategoria);
        if (!$configuracao) {
            return ['success' => false, 'errors' => ['Categoria não encontrada']];
        }
        
        try {
            db()->beginTransaction();
            
            // Atualizar aluno
            db()->execute("
                UPDATE alunos 
                SET categoria_cnh = ?, configuracao_categoria_id = ?, updated_at = NOW()
                WHERE id = ?
            ", [$novaCategoria, $configuracao['id'], $alunoId]);
            
            // Remover slots antigos
            db()->execute("DELETE FROM aulas_slots WHERE aluno_id = ?", [$alunoId]);
            
            // Criar novos slots
            self::criarSlotsAulas($alunoId, $configuracao);
            
            db()->commit();
            
            return ['success' => true, 'message' => 'Configuração atualizada com sucesso!'];
            
        } catch (Exception $e) {
            db()->rollback();
            return ['success' => false, 'errors' => ['Erro ao atualizar: ' . $e->getMessage()]];
        }
    }
}
