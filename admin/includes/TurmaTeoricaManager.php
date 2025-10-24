<?php
/**
 * Classe para Gerenciamento de Turmas Teóricas
 * Sistema completo com wizard em 4 etapas
 * 
 * @author Sistema CFC Bom Conselho
 * @version 1.0
 * @since 2024
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';

class TurmaTeoricaManager {
    private $db;
    
    // Configurações padrão
    const DURACAO_AULA_MINUTOS = 50;
    const MAX_AULAS_POR_DIA = 5;
    const CAPACIDADE_DEFAULT_SALA = 30;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Salvar dados temporários da turma (rascunho)
     */
    public function salvarRascunho($dados, $step = 1) {
        try {
            // Log dos dados recebidos para debug
            error_log("DEBUG - Dados recebidos para salvar rascunho: " . json_encode($dados));
            
            $this->verificarECriarTabelas();
            
            // Validar dados mínimos
            if (empty($dados['cfc_id']) || empty($dados['criado_por'])) {
                throw new Exception('CFC ID e Usuário são obrigatórios para salvar rascunho');
            }
            
            // Verificar se já existe um rascunho
            $rascunhoExistente = $this->db->fetch("
                SELECT id FROM turmas_teoricas 
                WHERE status = 'rascunho' 
                AND cfc_id = ? 
                AND criado_por = ?
                ORDER BY criado_em DESC 
                LIMIT 1
            ", [$dados['cfc_id'], $dados['criado_por']]);
            
            $dadosRascunho = [
                'nome' => $dados['nome'] ?? '',
                'sala_id' => !empty($dados['sala_id']) ? (int)$dados['sala_id'] : null,
                'curso_tipo' => $dados['curso_tipo'] ?? '',
                'modalidade' => $dados['modalidade'] ?? 'presencial',
                'data_inicio' => !empty($dados['data_inicio']) ? $dados['data_inicio'] : null,
                'data_fim' => !empty($dados['data_fim']) ? $dados['data_fim'] : null,
                'observacoes' => $dados['observacoes'] ?? null,
                'status' => 'rascunho',
                'carga_horaria_total' => $this->calcularCargaHorariaCurso($dados['curso_tipo'] ?? ''),
                'max_alunos' => (int)($dados['max_alunos'] ?? self::CAPACIDADE_DEFAULT_SALA),
                'cfc_id' => (int)$dados['cfc_id'],
                'criado_por' => (int)$dados['criado_por']
            ];
            
            // Log dos dados preparados
            error_log("DEBUG - Dados preparados para inserção: " . json_encode($dadosRascunho));
            
            if ($rascunhoExistente) {
                // Atualizar rascunho existente
                $turmaId = $rascunhoExistente['id'];
                $this->db->update('turmas_teoricas', $dadosRascunho, 'id = ?', [$turmaId]);
                error_log("DEBUG - Rascunho atualizado com ID: " . $turmaId);
            } else {
                // Criar novo rascunho
                $turmaId = $this->db->insert('turmas_teoricas', $dadosRascunho);
                error_log("DEBUG - Novo rascunho criado com ID: " . $turmaId);
            }
            
            return [
                'sucesso' => true,
                'turma_id' => $turmaId,
                'mensagem' => 'Dados salvos automaticamente'
            ];
            
        } catch (Exception $e) {
            error_log("ERRO ao salvar rascunho: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao salvar rascunho: ' . $e->getMessage(),
                'debug' => [
                    'dados_recebidos' => $dados,
                    'erro_completo' => $e->getMessage(),
                    'arquivo' => $e->getFile(),
                    'linha' => $e->getLine()
                ]
            ];
        }
    }
    
    /**
     * Carregar dados do rascunho
     */
    public function carregarRascunho($cfcId, $usuarioId) {
        try {
            $rascunho = $this->db->fetch("
                SELECT * FROM turmas_teoricas 
                WHERE status = 'rascunho' 
                AND cfc_id = ? 
                AND criado_por = ?
                ORDER BY atualizado_em DESC 
                LIMIT 1
            ", [$cfcId, $usuarioId]);
            
            if ($rascunho) {
                return [
                    'sucesso' => true,
                    'dados' => $rascunho
                ];
            } else {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Nenhum rascunho encontrado'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao carregar rascunho: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Finalizar turma (converter rascunho em turma ativa)
     */
    public function finalizarTurma($turmaId, $dados) {
        try {
            $this->db->beginTransaction();
            
            // Atualizar status para 'criando'
            $this->db->update('turmas_teoricas', 
                ['status' => 'criando'], 
                'id = ?', 
                [$turmaId]
            );
            
            $this->db->commit();
            
            return [
                'sucesso' => true,
                'turma_id' => $turmaId,
                'mensagem' => 'Turma finalizada com sucesso!'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao finalizar turma: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verificar e criar tabelas necessárias se não existirem
     */
    private function verificarECriarTabelas() {
        try {
            // Verificar se tabela turmas_teoricas existe
            $tabelaExiste = $this->db->fetch("SHOW TABLES LIKE 'turmas_teoricas'");
            if (!$tabelaExiste) {
                $this->criarTabelaTurmasTeoricas();
            } else {
                // Verificar se precisa adicionar campos
                $this->verificarECriarCamposTurmasTeoricas();
            }
            
            // Verificar se tabela turma_aulas_agendadas existe
            $tabelaExiste = $this->db->fetch("SHOW TABLES LIKE 'turma_aulas_agendadas'");
            if (!$tabelaExiste) {
                $this->criarTabelaTurmaAulasAgendadas();
            }
            
            // Verificar se tabela turma_logs existe
            $tabelaExiste = $this->db->fetch("SHOW TABLES LIKE 'turma_logs'");
            if (!$tabelaExiste) {
                $this->criarTabelaTurmaLogs();
            }
            
        } catch (Exception $e) {
            error_log("Erro ao verificar/criar tabelas: " . $e->getMessage());
        }
    }
    
    /**
     * Verificar e adicionar campos necessários na tabela turmas_teoricas
     */
    private function verificarECriarCamposTurmasTeoricas() {
        try {
            // Verificar se campo step_atual existe
            $campoExiste = $this->db->fetch("SHOW COLUMNS FROM turmas_teoricas LIKE 'step_atual'");
            if (!$campoExiste) {
                $this->db->query("ALTER TABLE turmas_teoricas ADD COLUMN step_atual INT DEFAULT 1 AFTER max_alunos");
            }
            
            // Verificar se status 'rascunho' existe no ENUM
            $statusEnum = $this->db->fetch("SHOW COLUMNS FROM turmas_teoricas WHERE Field = 'status'");
            if ($statusEnum && strpos($statusEnum['Type'], 'rascunho') === false) {
                $this->db->query("ALTER TABLE turmas_teoricas MODIFY COLUMN status ENUM('rascunho', 'criando', 'agendando', 'completa', 'ativa', 'finalizada', 'cancelada') DEFAULT 'rascunho'");
            }
            
        } catch (Exception $e) {
            error_log("Erro ao verificar/criar campos da tabela turmas_teoricas: " . $e->getMessage());
        }
    }
    
    /**
     * Criar tabela turmas_teoricas
     */
    private function criarTabelaTurmasTeoricas() {
        $sql = "
            CREATE TABLE turmas_teoricas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(200) NOT NULL,
                sala_id INT NOT NULL,
                curso_tipo VARCHAR(50) NOT NULL,
                modalidade ENUM('presencial', 'online', 'hibrida') DEFAULT 'presencial',
                data_inicio DATE NOT NULL,
                data_fim DATE NOT NULL,
                observacoes TEXT,
                status ENUM('rascunho', 'criando', 'agendando', 'completa', 'ativa', 'finalizada', 'cancelada') DEFAULT 'rascunho',
                carga_horaria_total INT NOT NULL DEFAULT 0,
                max_alunos INT NOT NULL DEFAULT 30,
                step_atual INT DEFAULT 1,
                cfc_id INT NOT NULL,
                criado_por INT NOT NULL,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_cfc_status (cfc_id, status),
                INDEX idx_sala_datas (sala_id, data_inicio, data_fim),
                INDEX idx_curso_tipo (curso_tipo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";
        
        $this->db->query($sql);
    }
    
    /**
     * Criar tabela turma_aulas_agendadas
     */
    private function criarTabelaTurmaAulasAgendadas() {
        $sql = "
            CREATE TABLE turma_aulas_agendadas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                turma_id INT NOT NULL,
                disciplina VARCHAR(100) NOT NULL,
                nome_aula VARCHAR(200) NOT NULL,
                instrutor_id INT,
                sala_id INT,
                data_aula DATE NOT NULL,
                hora_inicio TIME NOT NULL,
                hora_fim TIME NOT NULL,
                duracao_minutos INT NOT NULL DEFAULT 50,
                ordem_disciplina INT NOT NULL DEFAULT 1,
                ordem_global INT NOT NULL DEFAULT 1,
                status ENUM('agendada', 'realizada', 'cancelada', 'reagendada') DEFAULT 'agendada',
                observacoes TEXT,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_turma (turma_id),
                INDEX idx_instrutor_data (instrutor_id, data_aula),
                INDEX idx_sala_data (sala_id, data_aula),
                INDEX idx_disciplina (disciplina),
                FOREIGN KEY (turma_id) REFERENCES turmas_teoricas(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";
        
        $this->db->query($sql);
    }
    
    /**
     * Criar tabela turma_logs
     */
    private function criarTabelaTurmaLogs() {
        $sql = "
            CREATE TABLE turma_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                turma_id INT NOT NULL,
                acao VARCHAR(50) NOT NULL,
                descricao TEXT,
                dados_anteriores JSON,
                dados_novos JSON,
                usuario_id INT NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_turma (turma_id),
                INDEX idx_acao (acao),
                INDEX idx_usuario (usuario_id),
                INDEX idx_data (criado_em),
                FOREIGN KEY (turma_id) REFERENCES turmas_teoricas(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";
        
        $this->db->query($sql);
    }
    
    /**
     * ETAPA 1: Criar turma básica
     * @param array $dados Dados da turma
     * @return array Resultado da operação
     */
    public function criarTurmaBasica($dados) {
        try {
            // Verificar se a tabela turmas_teoricas existe, se não, criar
            $this->verificarECriarTabelas();
            
            // Validar dados obrigatórios
            $validacao = $this->validarDadosBasicos($dados);
            if (!$validacao['sucesso']) {
                return $validacao;
            }
            
            // Verificar conflito de sala no período (apenas se não for edição)
            if (!isset($dados['turma_id']) || !$dados['turma_id']) {
                $conflitoSala = $this->verificarConflitoSala(
                    $dados['sala_id'], 
                    $dados['data_inicio'], 
                    $dados['data_fim']
                );
                
                if (!$conflitoSala['disponivel']) {
                    return [
                        'sucesso' => false,
                        'mensagem' => $conflitoSala['mensagem'],
                        'tipo_erro' => 'conflito_sala'
                    ];
                }
            }
            
            $this->db->beginTransaction();
            
            // Calcular carga horária total baseada no curso
            $cargaHoraria = $this->calcularCargaHorariaCurso($dados['curso_tipo']);
            
            // Inserir turma
            $turmaId = $this->db->insert('turmas_teoricas', [
                'nome' => $dados['nome'],
                'sala_id' => $dados['sala_id'],
                'curso_tipo' => $dados['curso_tipo'],
                'modalidade' => $dados['modalidade'] ?? 'presencial',
                'data_inicio' => $dados['data_inicio'],
                'data_fim' => $dados['data_fim'],
                'observacoes' => $dados['observacoes'] ?? null,
                'status' => 'criando',
                'carga_horaria_total' => $cargaHoraria,
                'max_alunos' => $dados['max_alunos'] ?? self::CAPACIDADE_DEFAULT_SALA,
                'cfc_id' => $dados['cfc_id'],
                'criado_por' => $dados['criado_por']
            ]);
            
            // Log da criação (se a tabela existir)
            try {
                $this->registrarLog($turmaId, 'criada', 'Turma teórica criada', null, $dados, $dados['criado_por']);
            } catch (Exception $e) {
                // Log não é crítico, continuar
                error_log("Erro ao registrar log: " . $e->getMessage());
            }
            
            $this->db->commit();
            
            return [
                'sucesso' => true,
                'turma_id' => $turmaId,
                'mensagem' => '✅ Turma criada com sucesso! Prossiga para o agendamento das aulas.',
                'proxima_etapa' => 'agendamento'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            // Log do erro para debug
            error_log("Erro ao criar turma básica: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao criar turma: ' . $e->getMessage(),
                'tipo_erro' => 'erro_sistema'
            ];
        }
    }
    
    /**
     * ETAPA 2: Agendar aula na turma
     * @param array $dados Dados do agendamento
     * @return array Resultado da operação
     */
    public function agendarAula($dados) {
        try {
            // Validar dados da aula
            $validacao = $this->validarDadosAula($dados);
            if (!$validacao['sucesso']) {
                return $validacao;
            }
            
            // Verificar se turma existe e está no status correto
            $resultadoTurma = $this->obterTurma($dados['turma_id']);
            if (!$resultadoTurma['sucesso'] || !isset($resultadoTurma['dados']['status']) || !in_array($resultadoTurma['dados']['status'], ['criando', 'agendando'])) {
                return [
                    'sucesso' => false,
                    'mensagem' => '❌ Turma não encontrada ou não está disponível para agendamento.',
                    'tipo_erro' => 'turma_indisponivel'
                ];
            }
            
            // Verificar conflitos de horário
            $conflitos = $this->verificarConflitosHorario($dados, $resultadoTurma['dados']);
            if (!$conflitos['disponivel']) {
                return [
                    'sucesso' => false,
                    'mensagem' => $conflitos['mensagem'],
                    'tipo_erro' => 'conflito_horario',
                    'detalhes' => $conflitos['detalhes']
                ];
            }
            
            $this->db->beginTransaction();
            
            // Calcular ordem da disciplina
            $ordemDisciplina = $this->obterProximaOrdemDisciplina($dados['turma_id'], $dados['disciplina']);
            $ordemGlobal = $this->obterProximaOrdemGlobal($dados['turma_id']);
            
            // Agendar as aulas (pode ser mais de uma no mesmo dia)
            $aulasAgendadas = [];
            $qtdAulas = (int)$dados['quantidade_aulas'];
            
            for ($i = 0; $i < $qtdAulas; $i++) {
                $horaInicio = $this->calcularHorarioAula($dados['hora_inicio'], $i);
                $horaFim = $this->calcularHorarioFim($horaInicio);
                
                $aulaId = $this->db->insert('turma_aulas_agendadas', [
                    'turma_id' => $dados['turma_id'],
                    'disciplina' => $dados['disciplina'],
                    'nome_aula' => $this->gerarNomeAula($dados['disciplina'], $ordemDisciplina + $i),
                    'instrutor_id' => $dados['instrutor_id'],
                    'sala_id' => $resultadoTurma['dados']['sala_id'],
                    'data_aula' => $dados['data_aula'],
                    'hora_inicio' => $horaInicio,
                    'hora_fim' => $horaFim,
                    'duracao_minutos' => self::DURACAO_AULA_MINUTOS,
                    'ordem_disciplina' => $ordemDisciplina + $i,
                    'ordem_global' => $ordemGlobal + $i,
                    'status' => 'agendada'
                ]);
                
                $aulasAgendadas[] = $aulaId;
            }
            
            // Atualizar status da turma para 'agendando' se for a primeira aula
            if ($resultadoTurma['dados']['status'] === 'criando') {
                $this->db->update('turmas_teoricas', 
                    ['status' => 'agendando'], 
                    'id = ?', 
                    [$dados['turma_id']]
                );
            }
            
            // Log do agendamento
            $this->registrarLog(
                $dados['turma_id'], 
                'aula_agendada', 
                "Agendadas {$qtdAulas} aula(s) de {$dados['disciplina']}", 
                null, 
                $dados, 
                $dados['criado_por'] ?? 1
            );
            
            $this->db->commit();
            
            return [
                'sucesso' => true,
                'aulas_agendadas' => $aulasAgendadas,
                'mensagem' => "✅ {$qtdAulas} aula(s) agendada(s) com sucesso!",
                'progresso' => $this->obterProgressoDisciplinas($dados['turma_id'])
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao agendar aula: ' . $e->getMessage(),
                'tipo_erro' => 'erro_sistema'
            ];
        }
    }
    
    /**
     * ETAPA 3: Verificar se turma está completa (todas as disciplinas agendadas)
     * @param int $turmaId ID da turma
     * @return array Status da completude
     */
    public function verificarTurmaCompleta($turmaId) {
        try {
            $progresso = $this->obterProgressoDisciplinas($turmaId);
            $todasCompletas = true;
            $disciplinasPendentes = [];
            
            foreach ($progresso as $disciplina) {
                if ($disciplina['aulas_faltantes'] > 0) {
                    $todasCompletas = false;
                    $disciplinasPendentes[] = [
                        'nome' => $disciplina['nome_disciplina'],
                        'faltantes' => $disciplina['aulas_faltantes']
                    ];
                }
            }
            
            if ($todasCompletas) {
                // Atualizar status para 'completa'
                $this->db->update('turmas_teoricas', 
                    ['status' => 'completa'], 
                    'id = ?', 
                    [$turmaId]
                );
                
                return [
                    'completa' => true,
                    'mensagem' => '🎉 Turma completa! Todas as disciplinas foram agendadas. Agora você pode ativar a turma.',
                    'proxima_etapa' => 'ativar_turma'
                ];
            }
            
            return [
                'completa' => false,
                'mensagem' => '⚠️ Turma incompleta. Ainda há disciplinas pendentes.',
                'disciplinas_pendentes' => $disciplinasPendentes,
                'progresso' => $progresso
            ];
            
        } catch (Exception $e) {
            return [
                'completa' => false,
                'mensagem' => 'Erro ao verificar completude: ' . $e->getMessage(),
                'tipo_erro' => 'erro_sistema'
            ];
        }
    }
    
    /**
     * ETAPA 4: Matricular aluno na turma (com validação de exames)
     * @param int $turmaId ID da turma
     * @param int $alunoId ID do aluno
     * @return array Resultado da operação
     */
    public function matricularAluno($turmaId, $alunoId) {
        try {
            // Incluir sistema de guards para validação de exames
            require_once __DIR__ . '/../../includes/guards/AgendamentoGuards.php';
            $guards = new AgendamentoGuards();
            
            // VALIDAÇÃO 1: Verificar se turma está ativa ou completa
            $resultadoTurma = $this->obterTurma($turmaId);
            if (!$resultadoTurma['sucesso'] || !isset($resultadoTurma['dados']['status']) || !in_array($resultadoTurma['dados']['status'], ['completa', 'ativa'])) {
                return [
                    'sucesso' => false,
                    'mensagem' => '📋 A turma deve estar ativa ou ter todas as aulas agendadas para matricular alunos.',
                    'tipo_erro' => 'turma_incompleta'
                ];
            }
            
            // VALIDAÇÃO 2: Verificar se exames estão aprovados
            $validacaoExames = $guards->verificarExamesOK($alunoId);
            if (!$validacaoExames['permitido']) {
                return [
                    'sucesso' => false,
                    'mensagem' => $this->formatarMensagemExame($validacaoExames),
                    'tipo_erro' => 'exames_pendentes',
                    'detalhes' => $validacaoExames['detalhes'] ?? null
                ];
            }
            
            // VALIDAÇÃO 3: Verificar vagas disponíveis
            if ($resultadoTurma['dados']['alunos_matriculados'] >= $resultadoTurma['dados']['max_alunos']) {
                return [
                    'sucesso' => false,
                    'mensagem' => "📚 Turma lotada! Não há vagas disponíveis (máximo: {$resultadoTurma['dados']['max_alunos']} alunos).",
                    'tipo_erro' => 'turma_lotada'
                ];
            }
            
            // VALIDAÇÃO 4: Verificar se aluno já está matriculado
            $matriculaExistente = $this->db->fetch(
                "SELECT id FROM turma_matriculas WHERE turma_id = ? AND aluno_id = ?",
                [$turmaId, $alunoId]
            );
            
            if ($matriculaExistente) {
                return [
                    'sucesso' => false,
                    'mensagem' => '📚 Este aluno já está matriculado nesta turma.',
                    'tipo_erro' => 'ja_matriculado'
                ];
            }
            
            // Matricular aluno
            $this->db->insert('turma_matriculas', [
                'turma_id' => $turmaId,
                'aluno_id' => $alunoId,
                'status' => 'matriculado',
                'exames_validados_em' => date('Y-m-d H:i:s')
            ]);
            
            // Log da matrícula
            $this->registrarLog(
                $turmaId, 
                'aluno_matriculado', 
                "Aluno ID {$alunoId} matriculado na turma", 
                null, 
                ['aluno_id' => $alunoId], 
                $_SESSION['user_id'] ?? 1
            );
            
            return [
                'sucesso' => true,
                'mensagem' => '✅ Aluno matriculado com sucesso na turma!',
                'vagas_restantes' => $resultadoTurma['dados']['max_alunos'] - $resultadoTurma['dados']['alunos_matriculados'] - 1
            ];
            
        } catch (Exception $e) {
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao matricular aluno: ' . $e->getMessage(),
                'tipo_erro' => 'erro_sistema'
            ];
        }
    }
    
    /**
     * Listar turmas com filtros
     */
    public function listarTurmas($filtros = []) {
        try {
            $this->verificarECriarTabelas();
            
            $sql = "SELECT 
                        tt.*,
                        s.nome as sala_nome,
                        tc.nome as curso_nome,
                        COUNT(taa.id) as aulas_agendadas,
                        SUM(taa.duracao_minutos) as minutos_agendados,
                        (SELECT COUNT(*) FROM turma_alunos tal WHERE tal.turma_id = tt.id) as alunos_matriculados
                    FROM turmas_teoricas tt
                    LEFT JOIN salas s ON tt.sala_id = s.id
                    LEFT JOIN tipos_curso tc ON tt.curso_tipo = tc.codigo
                    LEFT JOIN turma_aulas_agendadas taa ON tt.id = taa.turma_id
                    WHERE 1=1";
            
            $params = [];
            
            // Aplicar filtros
            if (!empty($filtros['busca'])) {
                $sql .= " AND (tt.nome LIKE ? OR tc.nome LIKE ? OR s.nome LIKE ?)";
                $busca = "%{$filtros['busca']}%";
                $params = array_merge($params, [$busca, $busca, $busca]);
            }
            
            if (!empty($filtros['status'])) {
                $sql .= " AND tt.status = ?";
                $params[] = $filtros['status'];
            }
            
            if (!empty($filtros['curso_tipo'])) {
                $sql .= " AND tt.curso_tipo = ?";
                $params[] = $filtros['curso_tipo'];
            }
            
            if (!empty($filtros['cfc_id'])) {
                $sql .= " AND tt.cfc_id = ?";
                $params[] = $filtros['cfc_id'];
            }
            
            $sql .= " GROUP BY tt.id ORDER BY tt.criado_em DESC";
            
            $turmas = $this->db->fetchAll($sql, $params);
            
            return [
                'sucesso' => true,
                'dados' => $turmas,
                'total' => count($turmas)
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao listar turmas: " . $e->getMessage());
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao listar turmas: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obter dados completos de uma turma
     */
    public function obterTurma($turmaId) {
        try {
            $this->verificarECriarTabelas();
            
            // Buscar turma diretamente da tabela
            $turma = $this->db->fetch(
                "SELECT * FROM turmas_teoricas WHERE id = ?", 
                [$turmaId]
            );
            
            if ($turma) {
                // Buscar informações adicionais se necessário
                try {
                    $sala = $this->db->fetch("SELECT * FROM salas WHERE id = ?", [$turma['sala_id']]);
                    if ($sala) {
                        $turma['sala_nome'] = $sala['nome'];
                    }
                } catch (Exception $e) {
                    // Sala não encontrada, continuar
                }
                
                // Buscar nome do tipo de curso
                try {
                    $tipoCurso = $this->db->fetch("SELECT * FROM tipos_curso WHERE codigo = ?", [$turma['curso_tipo']]);
                    if ($tipoCurso) {
                        $turma['curso_nome'] = $tipoCurso['nome'];
                    } else {
                        // Se não encontrar na tabela tipos_curso, usar o código como nome
                        $turma['curso_nome'] = $turma['curso_tipo'];
                    }
                } catch (Exception $e) {
                    // Tabela tipos_curso não existe, usar o código como nome
                    $turma['curso_nome'] = $turma['curso_tipo'];
                }
                
                return [
                    'sucesso' => true,
                    'dados' => $turma
                ];
            } else {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Turma não encontrada'
                ];
            }
        } catch (Exception $e) {
            error_log("Erro ao obter turma: " . $e->getMessage());
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao buscar turma: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obter progresso das disciplinas de uma turma
     */
    public function obterProgressoDisciplinas($turmaId) {
        try {
            $this->verificarECriarTabelas();
            
            // Primeiro, tentar obter disciplinas selecionadas pelo usuário
            $disciplinasSelecionadas = $this->obterDisciplinasSelecionadas($turmaId);
            
            if (!empty($disciplinasSelecionadas)) {
                // Se há disciplinas selecionadas, formatar para o progresso
                $progresso = [];
                foreach ($disciplinasSelecionadas as $disc) {
                    // Buscar aulas agendadas para esta disciplina
                    $aulasAgendadas = $this->db->fetchAll(
                        "SELECT COUNT(*) as total FROM turma_aulas_agendadas WHERE turma_id = ? AND disciplina = ?",
                        [$turmaId, $disc['disciplina_id']]
                    );
                    
                    $aulasAgendadasCount = $aulasAgendadas[0]['total'] ?? 0;
                    $aulasObrigatorias = $disc['carga_horaria_padrao'];
                    $aulasFaltantes = max(0, $aulasObrigatorias - $aulasAgendadasCount);
                    
                    // Determinar status da disciplina
                    $statusDisciplina = 'pendente';
                    if ($aulasAgendadasCount >= $aulasObrigatorias) {
                        $statusDisciplina = 'completa';
                    } elseif ($aulasAgendadasCount > 0) {
                        $statusDisciplina = 'parcial';
                    }
                    
                    $progresso[] = [
                        'disciplina' => $disc['disciplina_id'],
                        'nome_disciplina' => $disc['nome_disciplina'],
                        'aulas_obrigatorias' => $aulasObrigatorias,
                        'aulas_agendadas' => $aulasAgendadasCount,
                        'aulas_faltantes' => $aulasFaltantes,
                        'status_disciplina' => $statusDisciplina,
                        'cor_hex' => $disc['cor_hex'],
                        'ordem' => $disc['ordem']
                    ];
                }
                return $progresso;
            }
            
            // Se não há disciplinas selecionadas, buscar aulas agendadas
            $aulas = $this->db->fetchAll(
                "SELECT * FROM turma_aulas_agendadas WHERE turma_id = ? ORDER BY ordem_global",
                [$turmaId]
            );
            
            // Se não há aulas agendadas, retornar disciplinas padrão baseadas no curso
            if (empty($aulas)) {
                // Buscar tipo de curso da turma
                $turma = $this->db->fetch("SELECT curso_tipo FROM turmas_teoricas WHERE id = ?", [$turmaId]);
                
                if ($turma) {
                    $disciplinas = $this->obterDisciplinasCurso($turma['curso_tipo']);
                    return $disciplinas;
                }
            }
            
            return $aulas;
        } catch (Exception $e) {
            error_log("Erro ao obter progresso das disciplinas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Marcar turma como concluída
     * @param int $turmaId ID da turma
     * @return array Resultado da operação
     */
    public function marcarTurmaConcluida($turmaId) {
        try {
            $this->db->beginTransaction();
            
            // Verificar se a turma existe e está em status válido
            $turma = $this->obterTurma($turmaId);
            if (!$turma['sucesso']) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Turma não encontrada'
                ];
            }
            
            // Atualizar status para 'finalizada'
            $this->db->update('turmas_teoricas', 
                ['status' => 'finalizada'], 
                'id = ?', 
                [$turmaId]
            );
            
            // Registrar log
            $this->registrarLog(
                $turmaId, 
                'turma_concluida', 
                'Turma marcada como concluída', 
                null, 
                [], 
                $_SESSION['user_id'] ?? 1
            );
            
            $this->db->commit();
            
            return [
                'sucesso' => true,
                'mensagem' => '✅ Turma marcada como concluída com sucesso!'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Erro ao marcar turma como concluída: " . $e->getMessage());
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao marcar turma como concluída: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Ativar turma (marcar como ativa quando está completa)
     * @param int $turmaId ID da turma
     * @return array Resultado da operação
     */
    public function ativarTurma($turmaId) {
        try {
            $this->db->beginTransaction();
            
            // Verificar se a turma existe e está em status 'completa'
            $turma = $this->obterTurma($turmaId);
            if (!$turma['sucesso']) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Turma não encontrada'
                ];
            }
            
            $dadosTurma = $turma['dados'];
            
            if ($dadosTurma['status'] !== 'completa') {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Turma deve ter todas as disciplinas agendadas antes de ser ativada'
                ];
            }
            
            // Atualizar status para 'ativa'
            $this->db->update('turmas_teoricas', 
                ['status' => 'ativa'], 
                'id = ?', 
                [$turmaId]
            );
            
            // Registrar log
            $this->registrarLog(
                $turmaId, 
                'turma_ativada', 
                'Turma ativada e pronta para receber alunos', 
                null, 
                [], 
                $_SESSION['user_id'] ?? 1
            );
            
            $this->db->commit();
            
            return [
                'sucesso' => true,
                'mensagem' => '✅ Turma ativada com sucesso! Agora está disponível para matrículas e aulas.'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Erro ao ativar turma: " . $e->getMessage());
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao ativar turma: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verificar e marcar turmas que deveriam estar concluídas
     * @return array Resultado da operação
     */
    public function verificarTurmasParaConcluir() {
        try {
            // Buscar turmas que estão com status 'ativa' e data_fim já passou
            $turmasVencidas = $this->db->fetchAll(
                "SELECT id, nome, data_fim FROM turmas_teoricas 
                 WHERE status = 'ativa' AND data_fim < CURDATE()"
            );
            
            $turmasConcluidas = [];
            
            foreach ($turmasVencidas as $turma) {
                $resultado = $this->marcarTurmaConcluida($turma['id']);
                if ($resultado['sucesso']) {
                    $turmasConcluidas[] = $turma['nome'];
                }
            }
            
            return [
                'sucesso' => true,
                'turmas_concluidas' => $turmasConcluidas,
                'total' => count($turmasConcluidas)
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao verificar turmas para concluir: " . $e->getMessage());
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao verificar turmas: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obter opções de cursos disponíveis
     */
    public function obterCursosDisponiveis() {
        return [
            'formacao_45h' => 'Curso de formação de condutores - Permissão 45h',
            'formacao_acc_20h' => 'Curso de formação de condutores - ACC 20h',
            'reciclagem_infrator' => 'Curso de reciclagem para condutor infrator',
            'atualizacao' => 'Curso de atualização'
        ];
    }
    
    /**
     * Obter salas disponíveis
     */
    public function obterSalasDisponiveis($cfcId = null) {
        try {
            // Verificar se a tabela salas existe
            $tabelaExiste = $this->db->fetch("SHOW TABLES LIKE 'salas'");
            if (!$tabelaExiste) {
                // Se a tabela não existe, retornar array vazio
                return [];
            }
            
            $sql = "SELECT * FROM salas WHERE ativa = 1";
            $params = [];
            
            if ($cfcId) {
                $sql .= " AND cfc_id = ?";
                $params[] = $cfcId;
            }
            
            $sql .= " ORDER BY nome";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            // Log do erro para debug
            error_log("Erro ao obter salas disponíveis: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter disciplinas de um curso
     */
    public function obterDisciplinasCurso($cursoTipo) {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM disciplinas_configuracao WHERE curso_tipo = ? AND ativa = 1 ORDER BY ordem",
                [$cursoTipo]
            );
        } catch (Exception $e) {
            return [];
        }
    }
    
    // ==============================================
    // MÉTODOS PRIVADOS DE APOIO
    // ==============================================
    
    private function validarDadosBasicos($dados) {
        $erros = [];
        
        if (empty($dados['nome'])) {
            $erros[] = 'Nome da turma é obrigatório';
        }
        
        if (empty($dados['sala_id'])) {
            $erros[] = 'Sala é obrigatória';
        }
        
        if (empty($dados['curso_tipo'])) {
            $erros[] = 'Tipo de curso é obrigatório';
        }
        
        if (empty($dados['data_inicio']) || empty($dados['data_fim'])) {
            $erros[] = 'Período da turma é obrigatório';
        }
        
        if (!empty($dados['data_inicio']) && !empty($dados['data_fim'])) {
            if (strtotime($dados['data_inicio']) > strtotime($dados['data_fim'])) {
                $erros[] = 'Data de início deve ser anterior à data final';
            }
        }
        
        if (!empty($erros)) {
            return [
                'sucesso' => false,
                'mensagem' => 'Dados inválidos: ' . implode(', ', $erros),
                'erros' => $erros
            ];
        }
        
        return ['sucesso' => true];
    }
    
    private function validarDadosAula($dados) {
        $erros = [];
        
        if (empty($dados['turma_id'])) {
            $erros[] = 'ID da turma é obrigatório';
        }
        
        if (empty($dados['disciplina'])) {
            $erros[] = 'Disciplina é obrigatória';
        }
        
        if (empty($dados['instrutor_id'])) {
            $erros[] = 'Instrutor é obrigatório';
        }
        
        if (empty($dados['data_aula'])) {
            $erros[] = 'Data da aula é obrigatória';
        }
        
        if (empty($dados['hora_inicio'])) {
            $erros[] = 'Horário de início é obrigatório';
        }
        
        $qtdAulas = (int)($dados['quantidade_aulas'] ?? 1);
        if ($qtdAulas < 1 || $qtdAulas > self::MAX_AULAS_POR_DIA) {
            $erros[] = 'Quantidade de aulas deve ser entre 1 e ' . self::MAX_AULAS_POR_DIA;
        }
        
        if (!empty($erros)) {
            return [
                'sucesso' => false,
                'mensagem' => 'Dados inválidos: ' . implode(', ', $erros),
                'erros' => $erros
            ];
        }
        
        return ['sucesso' => true];
    }
    
    private function verificarConflitoSala($salaId, $dataInicio, $dataFim, $turmaIdExcluir = null) {
        try {
            // Verificar se a tabela turmas_teoricas existe
            $tabelaExiste = $this->db->fetch("SHOW TABLES LIKE 'turmas_teoricas'");
            if (!$tabelaExiste) {
                // Se a tabela não existe, retornar disponível (não há conflitos)
                return ['disponivel' => true];
            }
            
            $sql = "SELECT COUNT(*) as conflitos FROM turmas_teoricas 
                    WHERE sala_id = ? 
                    AND status IN ('criando', 'agendando', 'completa', 'ativa')
                    AND (
                        (data_inicio <= ? AND data_fim >= ?) OR
                        (data_inicio <= ? AND data_fim >= ?) OR
                        (data_inicio >= ? AND data_fim <= ?)
                    )";
            
            $params = [$salaId, $dataInicio, $dataInicio, $dataFim, $dataFim, $dataInicio, $dataFim];
            
            if ($turmaIdExcluir) {
                $sql .= " AND id != ?";
                $params[] = $turmaIdExcluir;
            }
            
            $resultado = $this->db->fetch($sql, $params);
            
            if ($resultado && $resultado['conflitos'] > 0) {
                return [
                    'disponivel' => false,
                    'mensagem' => '❌ A sala selecionada já está ocupada por outra turma no período informado.'
                ];
            }
            
            return ['disponivel' => true];
            
        } catch (Exception $e) {
            // Log do erro para debug
            error_log("Erro na verificação de conflito de sala: " . $e->getMessage());
            
            // Em caso de erro, retornar disponível para não bloquear o sistema
            return [
                'disponivel' => true,
                'mensagem' => 'Verificação de disponibilidade temporariamente indisponível'
            ];
        }
    }
    
    private function verificarConflitosHorario($dados, $turma) {
        try {
            $conflitos = [];
            $qtdAulas = (int)$dados['quantidade_aulas'];
            
            // 1. Verificar se não excede a carga horária da disciplina
            $validacaoCargaHoraria = $this->verificarCargaHorariaDisciplina($dados['turma_id'], $dados['disciplina'], $qtdAulas);
            if (!$validacaoCargaHoraria['disponivel']) {
                return $validacaoCargaHoraria;
            }
            
            for ($i = 0; $i < $qtdAulas; $i++) {
                $horaInicio = $this->calcularHorarioAula($dados['hora_inicio'], $i);
                $horaFim = $this->calcularHorarioFim($horaInicio);
                
                // Verificar conflito de instrutor
                $conflitoInstrutor = $this->db->fetch("
                    SELECT COUNT(*) as conflitos 
                    FROM turma_aulas_agendadas 
                    WHERE instrutor_id = ? 
                    AND data_aula = ? 
                    AND status = 'agendada'
                    AND (
                        (hora_inicio < ? AND hora_fim > ?) OR
                        (hora_inicio < ? AND hora_fim > ?) OR
                        (hora_inicio >= ? AND hora_fim <= ?)
                    )
                ", [$dados['instrutor_id'], $dados['data_aula'], $horaInicio, $horaInicio, $horaFim, $horaFim, $horaInicio, $horaFim]);
                
                if ($conflitoInstrutor['conflitos'] > 0) {
                    $nomeInstrutor = $this->obterNomeInstrutor($dados['instrutor_id']);
                    $conflitos[] = "👨‍🏫 INSTRUTOR INDISPONÍVEL: O instrutor {$nomeInstrutor} já possui aula agendada no horário {$horaInicio} às {$horaFim}. Escolha outro horário ou instrutor.";
                }
                
                // Verificar conflito de sala
                $conflitoSala = $this->db->fetch("
                    SELECT COUNT(*) as conflitos 
                    FROM turma_aulas_agendadas 
                    WHERE sala_id = ? 
                    AND data_aula = ? 
                    AND status = 'agendada'
                    AND (
                        (hora_inicio < ? AND hora_fim > ?) OR
                        (hora_inicio < ? AND hora_fim > ?) OR
                        (hora_inicio >= ? AND hora_fim <= ?)
                    )
                ", [$turma['sala_id'], $dados['data_aula'], $horaInicio, $horaInicio, $horaFim, $horaFim, $horaInicio, $horaFim]);
                
                if ($conflitoSala['conflitos'] > 0) {
                    $nomeSala = $this->obterNomeSala($turma['sala_id']);
                    $conflitos[] = "🏢 SALA INDISPONÍVEL: A sala {$nomeSala} já está ocupada no horário {$horaInicio} às {$horaFim}. Escolha outro horário ou sala.";
                }
            }
            
            if (!empty($conflitos)) {
                return [
                    'disponivel' => false,
                    'mensagem' => '❌ Conflito de horário detectado: ' . implode(' | ', $conflitos),
                    'detalhes' => $conflitos,
                    'tipo_erro' => 'conflito_horario'
                ];
            }
            
            return ['disponivel' => true];
            
        } catch (Exception $e) {
            return [
                'disponivel' => false,
                'mensagem' => 'Erro ao verificar conflitos: ' . $e->getMessage(),
                'tipo_erro' => 'erro_sistema'
            ];
        }
    }
    
    /**
     * Verificar se não excede a carga horária da disciplina
     * @param int $turmaId ID da turma
     * @param string $disciplina Disciplina
     * @param int $qtdAulasNovas Quantidade de aulas a agendar
     * @return array Resultado da validação
     */
    private function verificarCargaHorariaDisciplina($turmaId, $disciplina, $qtdAulasNovas) {
        try {
            // Obter carga horária máxima da disciplina para esta turma
            $cargaMaxima = $this->db->fetch("
                SELECT dc.aulas_obrigatorias, tt.curso_tipo
                FROM disciplinas_configuracao dc
                INNER JOIN turmas_teoricas tt ON tt.id = ?
                WHERE dc.curso_tipo = tt.curso_tipo 
                AND dc.disciplina = ?
                AND dc.ativa = 1
            ", [$turmaId, $disciplina]);
            
            if (!$cargaMaxima) {
                return [
                    'disponivel' => false,
                    'mensagem' => "❌ CARGA HORÁRIA INVÁLIDA: Disciplina '{$disciplina}' não encontrada na configuração do curso.",
                    'tipo_erro' => 'disciplina_nao_encontrada'
                ];
            }
            
            $cargaMaximaAulas = (int)$cargaMaxima['aulas_obrigatorias'];
            
            // Contar aulas já agendadas para esta disciplina nesta turma
            $aulasAgendadas = $this->db->fetch("
                SELECT COUNT(*) as total
                FROM turma_aulas_agendadas 
                WHERE turma_id = ? 
                AND disciplina = ? 
                AND status IN ('agendada', 'realizada')
            ", [$turmaId, $disciplina]);
            
            $totalAgendadas = (int)$aulasAgendadas['total'];
            $totalAposAgendamento = $totalAgendadas + $qtdAulasNovas;
            
            if ($totalAposAgendamento > $cargaMaximaAulas) {
                $nomeDisciplina = $this->obterNomeDisciplina($disciplina);
                return [
                    'disponivel' => false,
                    'mensagem' => "❌ CARGA HORÁRIA EXCEDIDA: A disciplina '{$nomeDisciplina}' possui carga horária máxima de {$cargaMaximaAulas} aulas. Já foram agendadas {$totalAgendadas} aulas e você está tentando agendar mais {$qtdAulasNovas} aulas. Máximo permitido: {$cargaMaximaAulas} aulas.",
                    'tipo_erro' => 'carga_horaria_excedida',
                    'detalhes' => [
                        'disciplina' => $nomeDisciplina,
                        'carga_maxima' => $cargaMaximaAulas,
                        'aulas_agendadas' => $totalAgendadas,
                        'aulas_tentando_agendar' => $qtdAulasNovas,
                        'total_apos_agendamento' => $totalAposAgendamento
                    ]
                ];
            }
            
            return ['disponivel' => true];
            
        } catch (Exception $e) {
            return [
                'disponivel' => false,
                'mensagem' => 'Erro ao verificar carga horária: ' . $e->getMessage(),
                'tipo_erro' => 'erro_sistema'
            ];
        }
    }
    
    /**
     * Obter nome do instrutor
     * @param int $instrutorId ID do instrutor
     * @return string Nome do instrutor
     */
    private function obterNomeInstrutor($instrutorId) {
        try {
            $resultado = $this->db->fetch("
                SELECT COALESCE(u.nome, i.nome, 'Instrutor ID ' . ?) as nome
                FROM instrutores i 
                LEFT JOIN usuarios u ON i.usuario_id = u.id 
                WHERE i.id = ?
            ", [$instrutorId, $instrutorId]);
            
            return $resultado['nome'] ?? "Instrutor ID {$instrutorId}";
        } catch (Exception $e) {
            return "Instrutor ID {$instrutorId}";
        }
    }
    
    /**
     * Obter nome da sala
     * @param int $salaId ID da sala
     * @return string Nome da sala
     */
    private function obterNomeSala($salaId) {
        try {
            $resultado = $this->db->fetch("
                SELECT COALESCE(nome, 'Sala ID ' . ?) as nome
                FROM salas 
                WHERE id = ?
            ", [$salaId, $salaId]);
            
            return $resultado['nome'] ?? "Sala ID {$salaId}";
        } catch (Exception $e) {
            return "Sala ID {$salaId}";
        }
    }
    
    /**
     * Obter nome da disciplina
     * @param string $disciplina Código da disciplina
     * @return string Nome da disciplina
     */
    private function obterNomeDisciplina($disciplina) {
        $nomes = [
            'legislacao_transito' => 'Legislação de Trânsito',
            'primeiros_socorros' => 'Primeiros Socorros',
            'direcao_defensiva' => 'Direção Defensiva',
            'meio_ambiente_cidadania' => 'Meio Ambiente e Cidadania',
            'mecanica_basica' => 'Mecânica Básica'
        ];
        
        return $nomes[$disciplina] ?? ucfirst(str_replace('_', ' ', $disciplina));
    }

    private function calcularCargaHorariaCurso($cursoTipo) {
        try {
            $resultado = $this->db->fetch(
                "SELECT SUM(aulas_obrigatorias * 50) as total_minutos 
                 FROM disciplinas_configuracao 
                 WHERE curso_tipo = ? AND ativa = 1",
                [$cursoTipo]
            );
            
            return (int)($resultado['total_minutos'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function calcularHorarioAula($horarioInicial, $indiceAula) {
        $timestamp = strtotime($horarioInicial) + ($indiceAula * self::DURACAO_AULA_MINUTOS * 60);
        return date('H:i', $timestamp);
    }
    
    private function calcularHorarioFim($horarioInicio) {
        $timestamp = strtotime($horarioInicio) + (self::DURACAO_AULA_MINUTOS * 60);
        return date('H:i', $timestamp);
    }
    
    private function obterProximaOrdemDisciplina($turmaId, $disciplina) {
        try {
            $resultado = $this->db->fetch(
                "SELECT COALESCE(MAX(ordem_disciplina), 0) + 1 as proxima_ordem 
                 FROM turma_aulas_agendadas 
                 WHERE turma_id = ? AND disciplina = ?",
                [$turmaId, $disciplina]
            );
            
            return (int)$resultado['proxima_ordem'];
        } catch (Exception $e) {
            return 1;
        }
    }
    
    private function obterProximaOrdemGlobal($turmaId) {
        try {
            $resultado = $this->db->fetch(
                "SELECT COALESCE(MAX(ordem_global), 0) + 1 as proxima_ordem 
                 FROM turma_aulas_agendadas 
                 WHERE turma_id = ?",
                [$turmaId]
            );
            
            return (int)$resultado['proxima_ordem'];
        } catch (Exception $e) {
            return 1;
        }
    }
    
    private function gerarNomeAula($disciplina, $ordem) {
        $nomes = [
            'legislacao_transito' => 'Legislação de Trânsito',
            'primeiros_socorros' => 'Primeiros Socorros',
            'direcao_defensiva' => 'Direção Defensiva',
            'meio_ambiente_cidadania' => 'Meio Ambiente e Cidadania',
            'mecanica_basica' => 'Mecânica Básica'
        ];
        
        $nomeDisciplina = $nomes[$disciplina] ?? ucfirst(str_replace('_', ' ', $disciplina));
        return "{$nomeDisciplina} - Aula {$ordem}";
    }
    
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
    
    private function registrarLog($turmaId, $acao, $descricao, $dadosAnteriores, $dadosNovos, $usuarioId) {
        try {
            $this->db->insert('turma_log', [
                'turma_id' => $turmaId,
                'acao' => $acao,
                'descricao' => $descricao,
                'dados_anteriores' => $dadosAnteriores ? json_encode($dadosAnteriores) : null,
                'dados_novos' => json_encode($dadosNovos),
                'usuario_id' => $usuarioId,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);
        } catch (Exception $e) {
            // Log silencioso - não interromper o fluxo principal
            error_log("Erro ao registrar log da turma: " . $e->getMessage());
        }
    }
    
    // ==============================================
    // MÉTODOS PARA DISCIPLINAS SELECIONADAS PELO USUÁRIO
    // ==============================================
    
    /**
     * Salvar disciplinas selecionadas pelo usuário para uma turma
     */
    public function salvarDisciplinasSelecionadas($turmaId, $disciplinas) {
        try {
            // Primeiro, remover disciplinas existentes
            $this->db->execute("DELETE FROM turmas_disciplinas WHERE turma_id = ?", [$turmaId]);
            
            // Inserir novas disciplinas
            $ordem = 1;
            foreach ($disciplinas as $disciplina) {
                $this->db->execute(
                    "INSERT INTO turmas_disciplinas (turma_id, disciplina_id, nome_disciplina, carga_horaria_padrao, cor_hex, ordem) VALUES (?, ?, ?, ?, ?, ?)",
                    [
                        $turmaId,
                        $disciplina['id'],
                        $disciplina['nome'],
                        $disciplina['carga_horaria_padrao'] ?? 10,
                        $disciplina['cor_hex'] ?? '#007bff',
                        $ordem++
                    ]
                );
            }
            
            return [
                'sucesso' => true,
                'mensagem' => 'Disciplinas salvas com sucesso',
                'total' => count($disciplinas)
            ];
            
        } catch (Exception $e) {
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao salvar disciplinas: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obter disciplinas selecionadas pelo usuário para uma turma
     */
    public function obterDisciplinasSelecionadas($turmaId) {
        try {
            // Obter o tipo de curso da turma
            $turma = $this->db->fetch(
                "SELECT curso_tipo FROM turmas_teoricas WHERE id = ?",
                [$turmaId]
            );
            
            if (!$turma) {
                return [];
            }
            
            // Obter todas as disciplinas configuradas para este tipo de curso
            $disciplinas = $this->db->fetchAll(
                "SELECT 
                    disciplina as disciplina_id,
                    nome_disciplina,
                    aulas_obrigatorias as carga_horaria_padrao,
                    ordem,
                    cor_hex
                 FROM disciplinas_configuracao 
                 WHERE curso_tipo = ? AND ativa = 1
                 ORDER BY ordem",
                [$turma['curso_tipo']]
            );
            
            // Debug: Log das disciplinas encontradas
            error_log("DEBUG: Disciplinas encontradas para curso_tipo '{$turma['curso_tipo']}': " . json_encode($disciplinas));
            
            // Filtrar disciplinas com ID válido (não nulo, não vazio, não zero)
            $disciplinasValidas = array_filter($disciplinas, function($disciplina) {
                $id = $disciplina['disciplina_id'];
                $isValid = !empty($id) && $id !== 0 && $id !== '0' && $id !== null;
                if (!$isValid) {
                    error_log("DEBUG: Disciplina filtrada (ID inválido): " . json_encode($disciplina));
                }
                return $isValid;
            });
            
            $resultado = array_values($disciplinasValidas);
            error_log("DEBUG: Disciplinas válidas retornadas: " . json_encode($resultado));
            
            return $resultado;
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Obter disciplinas para agendamento (combinando selecionadas pelo usuário e configuração padrão)
     */
    public function obterDisciplinasParaAgendamento($turmaId) {
        try {
            // Primeiro, tentar obter disciplinas selecionadas pelo usuário
            $disciplinasSelecionadas = $this->obterDisciplinasSelecionadas($turmaId);
            
            if (!empty($disciplinasSelecionadas)) {
                // Se há disciplinas selecionadas pelo usuário, usar elas
                return array_map(function($disc) {
                    return [
                        'disciplina' => $disc['disciplina_id'],
                        'nome_disciplina' => $disc['nome_disciplina'],
                        'aulas_obrigatorias' => $disc['carga_horaria_padrao'],
                        'cor_hex' => $disc['cor_hex'],
                        'ordem' => $disc['ordem']
                    ];
                }, $disciplinasSelecionadas);
            }
            
            // Se não há disciplinas selecionadas, usar configuração padrão
            $turma = $this->obterTurma($turmaId);
            if ($turma['sucesso']) {
                return $this->obterDisciplinasCurso($turma['dados']['curso_tipo']);
            }
            
            return [];
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Carregar disciplinas automaticamente baseadas no tipo de curso selecionado
     * Este método substitui a seleção manual de disciplinas
     */
    public function carregarDisciplinasAutomaticas($cursoTipo) {
        try {
            $this->verificarECriarTabelas();
            
            // Buscar disciplinas configuradas para este tipo de curso
            $disciplinas = $this->db->fetchAll(
                "SELECT 
                    disciplina,
                    nome_disciplina,
                    aulas_obrigatorias,
                    ordem,
                    cor_hex,
                    icone
                 FROM disciplinas_configuracao 
                 WHERE curso_tipo = ? AND ativa = 1
                 ORDER BY ordem",
                [$cursoTipo]
            );
            
            return [
                'sucesso' => true,
                'disciplinas' => $disciplinas,
                'total' => count($disciplinas)
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao carregar disciplinas automáticas: " . $e->getMessage());
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao carregar disciplinas: ' . $e->getMessage(),
                'disciplinas' => []
            ];
        }
    }
    
    /**
     * Salvar disciplinas automaticamente para uma turma baseadas no curso
     * Remove a necessidade de seleção manual
     */
    public function salvarDisciplinasAutomaticas($turmaId, $cursoTipo) {
        try {
            $this->verificarECriarTabelas();
            
            // Obter disciplinas configuradas para o curso
            $disciplinasConfiguracao = $this->carregarDisciplinasAutomaticas($cursoTipo);
            
            if (!$disciplinasConfiguracao['sucesso'] || empty($disciplinasConfiguracao['disciplinas'])) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Nenhuma disciplina configurada para este tipo de curso'
                ];
            }
            
            // Limpar disciplinas existentes da turma (se houver)
            $this->db->query("DELETE FROM turma_disciplinas WHERE turma_id = ?", [$turmaId]);
            
            // Inserir disciplinas automaticamente
            $disciplinas = [];
            foreach ($disciplinasConfiguracao['disciplinas'] as $disciplina) {
                $disciplinas[] = [
                    'turma_id' => $turmaId,
                    'disciplina_id' => $disciplina['disciplina'],
                    'nome_disciplina' => $disciplina['nome_disciplina'],
                    'carga_horaria_padrao' => $disciplina['aulas_obrigatorias'],
                    'carga_horaria_personalizada' => $disciplina['aulas_obrigatorias'],
                    'ordem' => $disciplina['ordem'],
                    'cor_hex' => $disciplina['cor_hex'],
                    'icone' => $disciplina['icone']
                ];
            }
            
            // Inserir todas as disciplinas
            foreach ($disciplinas as $disciplina) {
                $this->db->query(
                    "INSERT INTO turma_disciplinas 
                     (turma_id, disciplina_id, nome_disciplina, carga_horaria_padrao, 
                      carga_horaria_personalizada, ordem, cor_hex, icone, criado_em) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                    [
                        $disciplina['turma_id'],
                        $disciplina['disciplina_id'],
                        $disciplina['nome_disciplina'],
                        $disciplina['carga_horaria_padrao'],
                        $disciplina['carga_horaria_personalizada'],
                        $disciplina['ordem'],
                        $disciplina['cor_hex'],
                        $disciplina['icone']
                    ]
                );
            }
            
            return [
                'sucesso' => true,
                'mensagem' => 'Disciplinas carregadas automaticamente com sucesso',
                'total' => count($disciplinas)
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao salvar disciplinas automáticas: " . $e->getMessage());
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao salvar disciplinas: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Remover aluno da turma
     * @param int $turmaId ID da turma
     * @param int $alunoId ID do aluno
     * @return array Resultado da operação
     */
    public function removerAluno($turmaId, $alunoId) {
        try {
            $this->db->beginTransaction();
            
            // Verificar se a matrícula existe
            $matricula = $this->db->fetch(
                "SELECT * FROM turma_alunos WHERE turma_id = ? AND aluno_id = ?",
                [$turmaId, $alunoId]
            );
            
            if (!$matricula) {
                return [
                    'sucesso' => false,
                    'mensagem' => '❌ Aluno não encontrado nesta turma.'
                ];
            }
            
            // Remover todas as presenças do aluno nas aulas desta turma
            $this->db->execute(
                "DELETE FROM turma_presencas WHERE turma_id = ? AND aluno_id = ?",
                [$turmaId, $alunoId]
            );
            
            // Remover a matrícula
            $this->db->execute(
                "DELETE FROM turma_alunos WHERE turma_id = ? AND aluno_id = ?",
                [$turmaId, $alunoId]
            );
            
            // Log da remoção
            $this->registrarLog(
                $turmaId, 
                'aluno_removido', 
                "Aluno ID {$alunoId} removido da turma", 
                null, 
                ['aluno_id' => $alunoId], 
                $_SESSION['user_id'] ?? 1
            );
            
            $this->db->commit();
            
            return [
                'sucesso' => true,
                'mensagem' => '✅ Aluno removido da turma com sucesso!'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao remover aluno: ' . $e->getMessage()
            ];
        }
    }
}
?>
