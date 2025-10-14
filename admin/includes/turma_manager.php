<?php
/**
 * Classe para Gerenciamento de Turmas
 * Baseada na análise do sistema eCondutor
 * 
 * @author Sistema CFC Bom Conselho
 * @version 1.0
 * @since 2024
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';

class TurmaManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Criar nova turma
     * @param array $dados Dados da turma
     * @return array Resultado da operação
     */
    public function criarTurma($dados) {
        try {
            // Validar dados obrigatórios
            $validacao = $this->validarDadosTurma($dados);
            if (!$validacao['sucesso']) {
                return $validacao;
            }
            
            $this->db->beginTransaction();
            
            // Inserir turma
            $turmaId = $this->db->insert('turmas', [
                'nome' => $dados['nome'],
                'instrutor_id' => $dados['instrutor_id'],
                'tipo_aula' => $dados['tipo_aula'],
                'categoria_cnh' => $dados['categoria_cnh'] ?? null,
                'data_inicio' => $dados['data_inicio'] ?? null,
                'data_fim' => $dados['data_fim'] ?? null,
                'status' => $dados['status'] ?? 'agendado',
                'observacoes' => $dados['observacoes'] ?? null,
                'cfc_id' => $dados['cfc_id']
            ]);
            
            // Inserir aulas da turma
            if (!empty($dados['aulas'])) {
                foreach ($dados['aulas'] as $index => $aula) {
                    $this->db->insert('turma_aulas', [
                        'turma_id' => $turmaId,
                        'ordem' => $index + 1,
                        'nome_aula' => $aula['nome_aula'],
                        'duracao_minutos' => $aula['duracao_minutos'] ?? 50,
                        'tipo_conteudo' => $aula['tipo_conteudo'] ?? null,
                        'data_aula' => $aula['data_aula'] ?? null
                    ]);
                }
            }
            
            $this->db->commit();
            
            return [
                'sucesso' => true,
                'turma_id' => $turmaId,
                'mensagem' => 'Turma criada com sucesso!'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao criar turma: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Listar turmas com filtros
     * @param array $filtros Filtros de busca
     * @return array Lista de turmas
     */
    public function listarTurmas($filtros = []) {
        try {
            $sql = "SELECT 
                        t.*,
                        i.nome as instrutor_nome,
                        i.email as instrutor_email,
                        c.nome as cfc_nome,
                        COUNT(ta.id) as total_aulas,
                        COUNT(CASE WHEN ta.status = 'concluida' THEN 1 END) as aulas_concluidas
                    FROM turmas t
                    LEFT JOIN instrutores i ON t.instrutor_id = i.id
                    LEFT JOIN cfcs c ON t.cfc_id = c.id
                    LEFT JOIN turma_aulas ta ON t.id = ta.turma_id
                    WHERE 1=1";
            
            $params = [];
            
            // Aplicar filtros
            if (!empty($filtros['busca'])) {
                $sql .= " AND (t.nome LIKE ? OR i.nome LIKE ?)";
                $params[] = '%' . $filtros['busca'] . '%';
                $params[] = '%' . $filtros['busca'] . '%';
            }
            
            if (!empty($filtros['data_inicio'])) {
                $sql .= " AND t.data_inicio >= ?";
                $params[] = $filtros['data_inicio'];
            }
            
            if (!empty($filtros['data_fim'])) {
                $sql .= " AND t.data_fim <= ?";
                $params[] = $filtros['data_fim'];
            }
            
            if (!empty($filtros['status'])) {
                $sql .= " AND t.status = ?";
                $params[] = $filtros['status'];
            }
            
            if (!empty($filtros['tipo_aula'])) {
                $sql .= " AND t.tipo_aula = ?";
                $params[] = $filtros['tipo_aula'];
            }
            
            if (!empty($filtros['cfc_id'])) {
                $sql .= " AND t.cfc_id = ?";
                $params[] = $filtros['cfc_id'];
            }
            
            $sql .= " GROUP BY t.id ORDER BY t.created_at DESC";
            
            // Paginação
            if (!empty($filtros['limite'])) {
                $offset = ($filtros['pagina'] ?? 0) * $filtros['limite'];
                $sql .= " LIMIT ? OFFSET ?";
                $params[] = $filtros['limite'];
                $params[] = $offset;
            }
            
            $this->db->query($sql, $params);
            $turmas = $this->db->fetchAll($sql, $params);
            
            return [
                'sucesso' => true,
                'dados' => $turmas,
                'total' => $this->contarTurmas($filtros)
            ];
            
        } catch (Exception $e) {
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao listar turmas: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Buscar turma por ID
     * @param int $turmaId ID da turma
     * @return array Dados da turma
     */
    public function buscarTurma($turmaId) {
        try {
            $sql = "SELECT 
                        t.*,
                        i.nome as instrutor_nome,
                        i.email as instrutor_email,
                        c.nome as cfc_nome
                    FROM turmas t
                    LEFT JOIN instrutores i ON t.instrutor_id = i.id
                    LEFT JOIN cfcs c ON t.cfc_id = c.id
                    WHERE t.id = ?";
            
            $turma = $this->db->fetch($sql, [$turmaId]);
            
            if ($turma) {
                // Buscar aulas da turma
                $turma['aulas'] = $this->buscarAulasTurma($turmaId);
                
                // Buscar alunos matriculados
                $turma['alunos'] = $this->buscarAlunosTurma($turmaId);
                
                // Contar total de alunos
                $turma['total_alunos'] = count($turma['alunos']);
            }
            
            return [
                'sucesso' => true,
                'dados' => $turma
            ];
            
        } catch (Exception $e) {
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao buscar turma: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Atualizar turma
     * @param int $turmaId ID da turma
     * @param array $dados Novos dados
     * @return array Resultado da operação
     */
    public function atualizarTurma($turmaId, $dados) {
        try {
            // Validar dados
            $validacao = $this->validarDadosTurma($dados, $turmaId);
            if (!$validacao['sucesso']) {
                return $validacao;
            }
            
            $this->db->beginTransaction();
            
            // Atualizar turma
            $this->db->update('turmas', [
                'nome' => $dados['nome'],
                'instrutor_id' => $dados['instrutor_id'],
                'tipo_aula' => $dados['tipo_aula'],
                'categoria_cnh' => $dados['categoria_cnh'] ?? null,
                'data_inicio' => $dados['data_inicio'] ?? null,
                'data_fim' => $dados['data_fim'] ?? null,
                'status' => $dados['status'] ?? 'agendado',
                'observacoes' => $dados['observacoes'] ?? null
            ], 'id = ?', [$turmaId]);
            
            // Atualizar aulas se fornecidas
            if (isset($dados['aulas'])) {
                // Remover aulas existentes
                $this->db->delete('turma_aulas', 'turma_id = ?', [$turmaId]);
                
                // Inserir novas aulas
                foreach ($dados['aulas'] as $index => $aula) {
                    $this->db->insert('turma_aulas', [
                        'turma_id' => $turmaId,
                        'ordem' => $index + 1,
                        'nome_aula' => $aula['nome_aula'],
                        'duracao_minutos' => $aula['duracao_minutos'] ?? 50,
                        'tipo_conteudo' => $aula['tipo_conteudo'] ?? null,
                        'data_aula' => $aula['data_aula'] ?? null
                    ]);
                }
            }
            
            $this->db->commit();
            
            return [
                'sucesso' => true,
                'mensagem' => 'Turma atualizada com sucesso!'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao atualizar turma: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Excluir turma
     * @param int $turmaId ID da turma
     * @return array Resultado da operação
     */
    public function excluirTurma($turmaId) {
        try {
            // Verificar se há alunos matriculados
            $alunos = $this->buscarAlunosTurma($turmaId);
            if (!empty($alunos)) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Não é possível excluir turma com alunos matriculados'
                ];
            }
            
            // Verificar se há aulas agendadas
            $aulasAgendadas = $this->db->fetchAll(
                "SELECT COUNT(*) as total FROM turma_aulas WHERE turma_id = ? AND status = 'agendada'",
                [$turmaId]
            );
            
            if ($aulasAgendadas[0]['total'] > 0) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Não é possível excluir turma com aulas agendadas'
                ];
            }
            
            $this->db->beginTransaction();
            
            // Excluir aulas da turma
            $this->db->delete('turma_aulas', 'turma_id = ?', [$turmaId]);
            
            // Excluir turma
            $this->db->delete('turmas', 'id = ?', [$turmaId]);
            
            $this->db->commit();
            
            return [
                'sucesso' => true,
                'mensagem' => 'Turma excluída com sucesso!'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao excluir turma: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Matricular aluno na turma
     * @param int $turmaId ID da turma
     * @param int $alunoId ID do aluno
     * @return array Resultado da operação
     */
    public function matricularAluno($turmaId, $alunoId) {
        try {
            // Incluir sistema de guards para validação de exames
            require_once __DIR__ . '/../../includes/guards/AgendamentoGuards.php';
            $guards = new AgendamentoGuards();
            
            // VALIDAÇÃO 1: Verificar se exames estão aprovados
            $validacaoExames = $guards->verificarExamesOK($alunoId);
            if (!$validacaoExames['permitido']) {
                // Mensagens amigáveis baseadas no tipo de erro
                $mensagem = $this->formatarMensagemExame($validacaoExames);
                
                return [
                    'sucesso' => false,
                    'mensagem' => $mensagem,
                    'tipo_erro' => 'exames_pendentes',
                    'detalhes' => $validacaoExames['detalhes'] ?? null
                ];
            }
            
            // VALIDAÇÃO 2: Verificar se aluno já está matriculado
            $matriculaExistente = $this->db->fetch(
                "SELECT id FROM turma_alunos WHERE turma_id = ? AND aluno_id = ?",
                [$turmaId, $alunoId]
            );
            
            if ($matriculaExistente) {
                return [
                    'sucesso' => false,
                    'mensagem' => '📚 Este aluno já está matriculado nesta turma.',
                    'tipo_erro' => 'ja_matriculado'
                ];
            }
            
            // VALIDAÇÃO 3: Verificar se turma está ativa
            $turma = $this->db->fetch("SELECT status, nome FROM turmas WHERE id = ?", [$turmaId]);
            if (!$turma) {
                return [
                    'sucesso' => false,
                    'mensagem' => '❌ Turma não encontrada.',
                    'tipo_erro' => 'turma_nao_encontrada'
                ];
            }
            
            if ($turma['status'] !== 'ativo' && $turma['status'] !== 'ativa') {
                return [
                    'sucesso' => false,
                    'mensagem' => "📋 A turma \"{$turma['nome']}\" não está ativa para novas matrículas.",
                    'tipo_erro' => 'turma_inativa'
                ];
            }
            
            // Matricular aluno
            $this->db->insert('turma_alunos', [
                'turma_id' => $turmaId,
                'aluno_id' => $alunoId,
                'status' => 'matriculado',
                'data_matricula' => date('Y-m-d')
            ]);
            
            return [
                'sucesso' => true,
                'mensagem' => '✅ Aluno matriculado com sucesso na turma!'
            ];
            
        } catch (Exception $e) {
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao matricular aluno: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Formattar mensagem amigável para erros de exames
     * @param array $validacaoExames Resultado da validação dos exames
     * @return string Mensagem formatada
     */
    private function formatarMensagemExame($validacaoExames) {
        $tipo = $validacaoExames['tipo'] ?? '';
        $detalhes = $validacaoExames['detalhes'] ?? [];
        
        switch ($tipo) {
            case 'aluno_nao_encontrado':
                return '❌ Aluno não encontrado no sistema.';
                
            case 'exames_nao_aprovados':
                $mensagens = ['🩺 Para matricular o aluno na turma, é necessário que os exames estejam aprovados:'];
                
                if (isset($detalhes['exame_medico'])) {
                    $statusMedico = $detalhes['exame_medico'];
                    if (empty($statusMedico) || $statusMedico === 'pendente') {
                        $mensagens[] = '• Exame médico: Ainda não realizado';
                    } elseif ($statusMedico === 'inapto' || $statusMedico === 'inapto_temporario') {
                        $mensagens[] = '• Exame médico: Reprovado (' . $statusMedico . ')';
                    } else {
                        $mensagens[] = '• Exame médico: ' . ucfirst($statusMedico);
                    }
                }
                
                if (isset($detalhes['exame_psicologico'])) {
                    $statusPsico = $detalhes['exame_psicologico'];
                    if (empty($statusPsico) || $statusPsico === 'pendente') {
                        $mensagens[] = '• Exame psicológico: Ainda não realizado';
                    } elseif ($statusPsico === 'inapto' || $statusPsico === 'inapto_temporario') {
                        $mensagens[] = '• Exame psicológico: Reprovado (' . $statusPsico . ')';
                    } else {
                        $mensagens[] = '• Exame psicológico: ' . ucfirst($statusPsico);
                    }
                }
                
                $mensagens[] = '';
                $mensagens[] = '💡 Providencie a aprovação dos exames pendentes antes de matricular o aluno na turma.';
                
                return implode("\n", $mensagens);
                
            case 'erro_sistema':
                return '⚠️ Erro temporário ao verificar os exames. Tente novamente em alguns instantes.';
                
            default:
                return '❌ ' . ($validacaoExames['motivo'] ?? 'Não foi possível matricular o aluno na turma.');
        }
    }
    
    /**
     * Buscar aulas de uma turma
     * @param int $turmaId ID da turma
     * @return array Lista de aulas
     */
    private function buscarAulasTurma($turmaId) {
        try {
            $sql = "SELECT * FROM turma_aulas WHERE turma_id = ? ORDER BY ordem ASC";
            return $this->db->fetchAll($sql, [$turmaId]);
        } catch (Exception $e) {
            // Se a tabela não existir, retornar array vazio
            return [];
        }
    }
    
    /**
     * Buscar alunos de uma turma
     * @param int $turmaId ID da turma
     * @return array Lista de alunos
     */
    private function buscarAlunosTurma($turmaId) {
        try {
            $sql = "SELECT 
                        ta.*,
                        a.nome as aluno_nome,
                        a.email as aluno_email,
                        a.telefone as aluno_telefone
                    FROM turma_alunos ta
                    LEFT JOIN alunos a ON ta.aluno_id = a.id
                    WHERE ta.turma_id = ?
                    ORDER BY ta.data_matricula ASC";
            
            return $this->db->fetchAll($sql, [$turmaId]);
        } catch (Exception $e) {
            // Se a tabela não existir, retornar array vazio
            return [];
        }
    }
    
    /**
     * Contar total de turmas com filtros
     * @param array $filtros Filtros de busca
     * @return int Total de turmas
     */
    private function contarTurmas($filtros) {
        $sql = "SELECT COUNT(DISTINCT t.id) as total FROM turmas t
                LEFT JOIN instrutores i ON t.instrutor_id = i.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filtros['busca'])) {
            $sql .= " AND (t.nome LIKE ? OR i.nome LIKE ?)";
            $params[] = '%' . $filtros['busca'] . '%';
            $params[] = '%' . $filtros['busca'] . '%';
        }
        
        if (!empty($filtros['data_inicio'])) {
            $sql .= " AND t.data_inicio >= ?";
            $params[] = $filtros['data_inicio'];
        }
        
        if (!empty($filtros['data_fim'])) {
            $sql .= " AND t.data_fim <= ?";
            $params[] = $filtros['data_fim'];
        }
        
        if (!empty($filtros['status'])) {
            $sql .= " AND t.status = ?";
            $params[] = $filtros['status'];
        }
        
        if (!empty($filtros['cfc_id'])) {
            $sql .= " AND t.cfc_id = ?";
            $params[] = $filtros['cfc_id'];
        }
        
        $this->db->query($sql, $params);
        $resultado = $this->db->fetch($sql, $params);
        
        return $resultado['total'];
    }
    
    /**
     * Validar dados da turma
     * @param array $dados Dados para validar
     * @param int $turmaId ID da turma (para atualização)
     * @return array Resultado da validação
     */
    private function validarDadosTurma($dados, $turmaId = null) {
        $erros = [];
        
        // Validar campos obrigatórios
        if (empty($dados['nome'])) {
            $erros[] = 'Nome da turma é obrigatório';
        }
        
        if (empty($dados['instrutor_id'])) {
            $erros[] = 'Instrutor é obrigatório';
        }
        
        if (empty($dados['tipo_aula'])) {
            $erros[] = 'Tipo de aula é obrigatório';
        }
        
        if (empty($dados['cfc_id'])) {
            $erros[] = 'CFC é obrigatório';
        }
        
        // Validar se instrutor existe
        if (!empty($dados['instrutor_id'])) {
            $instrutor = $this->db->fetch(
                "SELECT id FROM instrutores WHERE id = ? AND ativo = 1",
                [$dados['instrutor_id']]
            );
            
            if (!$instrutor) {
                $erros[] = 'Instrutor não encontrado ou inativo';
            }
        }
        
        // Validar datas
        if (!empty($dados['data_inicio']) && !empty($dados['data_fim'])) {
            if (strtotime($dados['data_inicio']) > strtotime($dados['data_fim'])) {
                $erros[] = 'Data de início deve ser anterior à data final';
            }
        }
        
        // Validar aulas
        if (!empty($dados['aulas'])) {
            foreach ($dados['aulas'] as $index => $aula) {
                if (empty($aula['nome_aula'])) {
                    $erros[] = "Nome da aula " . ($index + 1) . " é obrigatório";
                }
                
                if (!empty($aula['duracao_minutos']) && $aula['duracao_minutos'] <= 0) {
                    $erros[] = "Duração da aula " . ($index + 1) . " deve ser maior que zero";
                }
            }
        }
        
        if (!empty($erros)) {
            return [
                'sucesso' => false,
                'mensagem' => 'Dados inválidos',
                'erros' => $erros
            ];
        }
        
        return ['sucesso' => true];
    }
    
    /**
     * Obter estatísticas das turmas
     * @param int $cfcId ID do CFC
     * @return array Estatísticas
     */
    public function obterEstatisticas($cfcId = null) {
        try {
            $where = $cfcId ? "WHERE t.cfc_id = ?" : "";
            $params = $cfcId ? [$cfcId] : [];
            
            $sql = "SELECT 
                        COUNT(*) as total_turmas,
                        COUNT(CASE WHEN status = 'ativo' THEN 1 END) as turmas_ativas,
                        COUNT(CASE WHEN status = 'agendado' THEN 1 END) as turmas_agendadas,
                        COUNT(CASE WHEN status = 'concluido' THEN 1 END) as turmas_concluidas,
                        SUM(total_alunos) as total_alunos_matriculados
                    FROM turmas t $where";
            
            $this->db->query($sql, $params);
            $stats = $this->db->fetch($sql, $params);
            
            return [
                'sucesso' => true,
                'dados' => $stats
            ];
            
        } catch (Exception $e) {
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao obter estatísticas: ' . $e->getMessage()
            ];
        }
    }
}
?>
