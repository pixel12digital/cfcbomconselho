-- =====================================================
-- MIGRAÇÃO: Estrutura para Sistema de Turmas Teóricas
-- Versão: 1.0
-- Data: 2024
-- Autor: Sistema CFC Bom Conselho
-- =====================================================

-- 1. TABELA DE SALAS
CREATE TABLE IF NOT EXISTS salas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    capacidade INT NOT NULL DEFAULT 30,
    equipamentos JSON DEFAULT NULL,
    ativa BOOLEAN DEFAULT TRUE,
    cfc_id INT NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_cfc_ativa (cfc_id, ativa),
    FOREIGN KEY (cfc_id) REFERENCES cfcs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir salas padrão
INSERT IGNORE INTO salas (nome, capacidade, cfc_id, equipamentos) VALUES 
('Sala 1', 30, 1, '{"projetor": true, "ar_condicionado": true, "quadro": true}'),
('Sala 2', 25, 1, '{"projetor": true, "ar_condicionado": false, "quadro": true}'),
('Sala 3', 35, 1, '{"projetor": true, "ar_condicionado": true, "quadro": true, "computadores": 10}');

-- 2. CONFIGURAÇÃO DE DISCIPLINAS POR CURSO
CREATE TABLE IF NOT EXISTS disciplinas_configuracao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    curso_tipo ENUM(
        'reciclagem_infrator',
        'formacao_45h', 
        'atualizacao',
        'formacao_acc_20h'
    ) NOT NULL,
    disciplina ENUM(
        'legislacao_transito',
        'primeiros_socorros', 
        'direcao_defensiva',
        'meio_ambiente_cidadania',
        'mecanica_basica'
    ) NOT NULL,
    nome_disciplina VARCHAR(100) NOT NULL,
    aulas_obrigatorias INT NOT NULL,
    ordem INT NOT NULL DEFAULT 1,
    cor_hex VARCHAR(7) DEFAULT '#007bff',
    icone VARCHAR(50) DEFAULT 'book',
    ativa BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_curso_disciplina (curso_tipo, disciplina),
    INDEX idx_curso_ordem (curso_tipo, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir configurações padrão das disciplinas
INSERT IGNORE INTO disciplinas_configuracao 
(curso_tipo, disciplina, nome_disciplina, aulas_obrigatorias, ordem, cor_hex, icone) VALUES

-- Curso de Formação 45h
('formacao_45h', 'legislacao_transito', 'Legislação de Trânsito', 18, 1, '#dc3545', 'gavel'),
('formacao_45h', 'direcao_defensiva', 'Direção Defensiva', 16, 2, '#28a745', 'shield-alt'),
('formacao_45h', 'primeiros_socorros', 'Primeiros Socorros', 4, 3, '#ffc107', 'first-aid'),
('formacao_45h', 'meio_ambiente_cidadania', 'Meio Ambiente e Cidadania', 4, 4, '#17a2b8', 'leaf'),
('formacao_45h', 'mecanica_basica', 'Mecânica Básica', 3, 5, '#6c757d', 'wrench'),

-- Curso ACC 20h
('formacao_acc_20h', 'legislacao_transito', 'Legislação de Trânsito', 8, 1, '#dc3545', 'gavel'),
('formacao_acc_20h', 'direcao_defensiva', 'Direção Defensiva', 8, 2, '#28a745', 'shield-alt'),
('formacao_acc_20h', 'primeiros_socorros', 'Primeiros Socorros', 2, 3, '#ffc107', 'first-aid'),
('formacao_acc_20h', 'meio_ambiente_cidadania', 'Meio Ambiente e Cidadania', 2, 4, '#17a2b8', 'leaf'),

-- Curso de Reciclagem
('reciclagem_infrator', 'legislacao_transito', 'Legislação de Trânsito', 15, 1, '#dc3545', 'gavel'),
('reciclagem_infrator', 'direcao_defensiva', 'Direção Defensiva', 15, 2, '#28a745', 'shield-alt'),

-- Curso de Atualização
('atualizacao', 'legislacao_transito', 'Legislação de Trânsito', 8, 1, '#dc3545', 'gavel'),
('atualizacao', 'direcao_defensiva', 'Direção Defensiva', 7, 2, '#28a745', 'shield-alt');

-- 3. TABELA PRINCIPAL DE TURMAS TEÓRICAS
CREATE TABLE IF NOT EXISTS turmas_teoricas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,
    sala_id INT NOT NULL,
    curso_tipo ENUM(
        'reciclagem_infrator',
        'formacao_45h', 
        'atualizacao',
        'formacao_acc_20h'
    ) NOT NULL,
    modalidade ENUM('online', 'presencial') NOT NULL DEFAULT 'presencial',
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    observacoes TEXT DEFAULT NULL,
    status ENUM('criando', 'agendando', 'completa', 'ativa', 'concluida', 'cancelada') DEFAULT 'criando',
    
    -- Controle de carga horária
    carga_horaria_total INT DEFAULT 0,
    carga_horaria_agendada INT DEFAULT 0,
    carga_horaria_realizada INT DEFAULT 0,
    
    -- Controle de alunos
    max_alunos INT DEFAULT 30,
    alunos_matriculados INT DEFAULT 0,
    
    -- Auditoria
    cfc_id INT NOT NULL,
    criado_por INT NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices para performance
    INDEX idx_sala_periodo (sala_id, data_inicio, data_fim),
    INDEX idx_curso_status (curso_tipo, status),
    INDEX idx_cfc_status (cfc_id, status),
    INDEX idx_datas (data_inicio, data_fim),
    
    FOREIGN KEY (sala_id) REFERENCES salas(id),
    FOREIGN KEY (cfc_id) REFERENCES cfcs(id) ON DELETE CASCADE,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. AULAS AGENDADAS DA TURMA
CREATE TABLE IF NOT EXISTS turma_aulas_agendadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    turma_id INT NOT NULL,
    disciplina ENUM(
        'legislacao_transito',
        'primeiros_socorros', 
        'direcao_defensiva',
        'meio_ambiente_cidadania',
        'mecanica_basica'
    ) NOT NULL,
    nome_aula VARCHAR(200) NOT NULL,
    instrutor_id INT NOT NULL,
    sala_id INT NOT NULL,
    data_aula DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    duracao_minutos INT DEFAULT 50,
    ordem_disciplina INT NOT NULL DEFAULT 1,
    ordem_global INT NOT NULL DEFAULT 1,
    status ENUM('agendada', 'realizada', 'cancelada') DEFAULT 'agendada',
    observacoes TEXT DEFAULT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices para detectar conflitos
    INDEX idx_instrutor_conflitos (instrutor_id, data_aula, hora_inicio, hora_fim),
    INDEX idx_sala_conflitos (sala_id, data_aula, hora_inicio, hora_fim),
    INDEX idx_turma_disciplina (turma_id, disciplina, ordem_disciplina),
    INDEX idx_cronologico (turma_id, data_aula, hora_inicio),
    
    FOREIGN KEY (turma_id) REFERENCES turmas_teoricas(id) ON DELETE CASCADE,
    FOREIGN KEY (instrutor_id) REFERENCES instrutores(id),
    FOREIGN KEY (sala_id) REFERENCES salas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. MATRÍCULAS EM TURMAS TEÓRICAS
CREATE TABLE IF NOT EXISTS turma_matriculas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    turma_id INT NOT NULL,
    aluno_id INT NOT NULL,
    data_matricula TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('matriculado', 'cursando', 'concluido', 'evadido', 'transferido') DEFAULT 'matriculado',
    exames_validados_em TIMESTAMP NULL DEFAULT NULL,
    frequencia_percentual DECIMAL(5,2) DEFAULT 0.00,
    observacoes TEXT DEFAULT NULL,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_turma_status (turma_id, status),
    INDEX idx_aluno_status (aluno_id, status),
    
    UNIQUE KEY unique_turma_aluno (turma_id, aluno_id),
    FOREIGN KEY (turma_id) REFERENCES turmas_teoricas(id) ON DELETE CASCADE,
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. PRESENÇAS NAS AULAS TEÓRICAS
CREATE TABLE IF NOT EXISTS turma_presencas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    turma_id INT NOT NULL,
    aula_id INT NOT NULL,
    aluno_id INT NOT NULL,
    presente BOOLEAN NOT NULL DEFAULT FALSE,
    justificativa TEXT DEFAULT NULL,
    registrado_por INT NOT NULL,
    registrado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_aula_aluno (aula_id, aluno_id),
    INDEX idx_turma_aluno (turma_id, aluno_id),
    
    UNIQUE KEY unique_aula_aluno (aula_id, aluno_id),
    FOREIGN KEY (turma_id) REFERENCES turmas_teoricas(id) ON DELETE CASCADE,
    FOREIGN KEY (aula_id) REFERENCES turma_aulas_agendadas(id) ON DELETE CASCADE,
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. LOG DE ALTERAÇÕES NAS TURMAS
CREATE TABLE IF NOT EXISTS turma_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    turma_id INT NOT NULL,
    acao ENUM('criada', 'editada', 'ativada', 'concluida', 'cancelada', 'aula_agendada', 'aula_cancelada') NOT NULL,
    descricao TEXT NOT NULL,
    dados_anteriores JSON DEFAULT NULL,
    dados_novos JSON DEFAULT NULL,
    usuario_id INT NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_turma_acao (turma_id, acao),
    INDEX idx_usuario_data (usuario_id, criado_em),
    
    FOREIGN KEY (turma_id) REFERENCES turmas_teoricas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TRIGGERS PARA MANTER INTEGRIDADE DOS DADOS
-- =====================================================

-- Trigger para atualizar contador de alunos matriculados
DELIMITER $$

CREATE TRIGGER after_turma_matricula_insert
AFTER INSERT ON turma_matriculas
FOR EACH ROW
BEGIN
    UPDATE turmas_teoricas 
    SET alunos_matriculados = (
        SELECT COUNT(*) FROM turma_matriculas 
        WHERE turma_id = NEW.turma_id AND status IN ('matriculado', 'cursando')
    )
    WHERE id = NEW.turma_id;
END$$

CREATE TRIGGER after_turma_matricula_update
AFTER UPDATE ON turma_matriculas
FOR EACH ROW
BEGIN
    UPDATE turmas_teoricas 
    SET alunos_matriculados = (
        SELECT COUNT(*) FROM turma_matriculas 
        WHERE turma_id = NEW.turma_id AND status IN ('matriculado', 'cursando')
    )
    WHERE id = NEW.turma_id;
END$$

CREATE TRIGGER after_turma_matricula_delete
AFTER DELETE ON turma_matriculas
FOR EACH ROW
BEGIN
    UPDATE turmas_teoricas 
    SET alunos_matriculados = (
        SELECT COUNT(*) FROM turma_matriculas 
        WHERE turma_id = OLD.turma_id AND status IN ('matriculado', 'cursando')
    )
    WHERE id = OLD.turma_id;
END$$

-- Trigger para atualizar carga horária agendada
CREATE TRIGGER after_aula_agendada_insert
AFTER INSERT ON turma_aulas_agendadas
FOR EACH ROW
BEGIN
    UPDATE turmas_teoricas 
    SET carga_horaria_agendada = (
        SELECT SUM(duracao_minutos) FROM turma_aulas_agendadas 
        WHERE turma_id = NEW.turma_id AND status = 'agendada'
    )
    WHERE id = NEW.turma_id;
END$$

CREATE TRIGGER after_aula_agendada_update
AFTER UPDATE ON turma_aulas_agendadas
FOR EACH ROW
BEGIN
    UPDATE turmas_teoricas 
    SET 
        carga_horaria_agendada = (
            SELECT SUM(duracao_minutos) FROM turma_aulas_agendadas 
            WHERE turma_id = NEW.turma_id AND status = 'agendada'
        ),
        carga_horaria_realizada = (
            SELECT SUM(duracao_minutos) FROM turma_aulas_agendadas 
            WHERE turma_id = NEW.turma_id AND status = 'realizada'
        )
    WHERE id = NEW.turma_id;
END$$

CREATE TRIGGER after_aula_agendada_delete
AFTER DELETE ON turma_aulas_agendadas
FOR EACH ROW
BEGIN
    UPDATE turmas_teoricas 
    SET carga_horaria_agendada = (
        SELECT COALESCE(SUM(duracao_minutos), 0) FROM turma_aulas_agendadas 
        WHERE turma_id = OLD.turma_id AND status = 'agendada'
    )
    WHERE id = OLD.turma_id;
END$$

DELIMITER ;

-- =====================================================
-- VIEWS ÚTEIS PARA CONSULTAS
-- =====================================================

-- View com informações completas das turmas
CREATE OR REPLACE VIEW view_turmas_completas AS
SELECT 
    tt.*,
    s.nome as sala_nome,
    s.capacidade as sala_capacidade,
    u.nome as criado_por_nome,
    c.nome as cfc_nome,
    CASE tt.curso_tipo
        WHEN 'formacao_45h' THEN 'Formação de Condutores - 45h'
        WHEN 'formacao_acc_20h' THEN 'Formação de Condutores - ACC 20h'
        WHEN 'reciclagem_infrator' THEN 'Reciclagem para Condutor Infrator'
        WHEN 'atualizacao' THEN 'Curso de Atualização'
    END as curso_nome,
    DATEDIFF(tt.data_fim, tt.data_inicio) + 1 as duracao_dias,
    ROUND((tt.carga_horaria_agendada / 60.0), 1) as horas_agendadas,
    ROUND((tt.carga_horaria_total / 60.0), 1) as horas_total,
    ROUND((tt.alunos_matriculados / tt.max_alunos * 100), 1) as ocupacao_percentual
FROM turmas_teoricas tt
LEFT JOIN salas s ON tt.sala_id = s.id
LEFT JOIN usuarios u ON tt.criado_por = u.id
LEFT JOIN cfcs c ON tt.cfc_id = c.id;

-- View com progresso das disciplinas por turma
CREATE OR REPLACE VIEW view_turma_progresso_disciplinas AS
SELECT 
    tt.id as turma_id,
    tt.nome as turma_nome,
    dc.disciplina,
    dc.nome_disciplina,
    dc.aulas_obrigatorias,
    COALESCE(COUNT(taa.id), 0) as aulas_agendadas,
    dc.aulas_obrigatorias - COALESCE(COUNT(taa.id), 0) as aulas_faltantes,
    CASE 
        WHEN COALESCE(COUNT(taa.id), 0) >= dc.aulas_obrigatorias THEN 'completa'
        WHEN COALESCE(COUNT(taa.id), 0) > 0 THEN 'parcial'
        ELSE 'pendente'
    END as status_disciplina
FROM turmas_teoricas tt
CROSS JOIN disciplinas_configuracao dc ON tt.curso_tipo = dc.curso_tipo
LEFT JOIN turma_aulas_agendadas taa ON tt.id = taa.turma_id AND dc.disciplina = taa.disciplina
WHERE dc.ativa = 1
GROUP BY tt.id, dc.disciplina, dc.nome_disciplina, dc.aulas_obrigatorias
ORDER BY tt.id, dc.ordem;

-- =====================================================
-- ATUALIZAR CARGA HORÁRIA TOTAL NAS TURMAS EXISTENTES
-- =====================================================

UPDATE turmas_teoricas tt
SET carga_horaria_total = (
    SELECT SUM(dc.aulas_obrigatorias * 50) 
    FROM disciplinas_configuracao dc 
    WHERE dc.curso_tipo = tt.curso_tipo AND dc.ativa = 1
);

-- =====================================================
-- MENSAGEM DE CONCLUSÃO
-- =====================================================

SELECT 'Migração concluída com sucesso! Sistema de Turmas Teóricas criado.' as status;
