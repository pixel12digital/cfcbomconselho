-- =====================================================
-- SCRIPT SQL PARA SISTEMA DE TURMAS - CFC BOM CONSELHO
-- Baseado na análise do sistema eCondutor
-- =====================================================

-- 1. TABELA PRINCIPAL DE TURMAS
CREATE TABLE IF NOT EXISTS turmas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    instrutor_id INT NOT NULL,
    tipo_aula ENUM('teorica', 'pratica', 'mista') NOT NULL,
    categoria_cnh VARCHAR(10), -- Opcional, para turmas específicas
    data_inicio DATE,
    data_fim DATE,
    status ENUM('agendado', 'ativo', 'inativo', 'concluido') DEFAULT 'agendado',
    total_alunos INT DEFAULT 0,
    observacoes TEXT,
    cfc_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (instrutor_id) REFERENCES instrutores(id),
    FOREIGN KEY (cfc_id) REFERENCES cfcs(id),
    INDEX idx_status (status),
    INDEX idx_instrutor (instrutor_id),
    INDEX idx_periodo (data_inicio, data_fim),
    INDEX idx_tipo (tipo_aula),
    INDEX idx_cfc_status (cfc_id, status)
);

-- 2. TABELA DE AULAS DA TURMA
CREATE TABLE IF NOT EXISTS turma_aulas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    turma_id INT NOT NULL,
    ordem INT NOT NULL,
    nome_aula VARCHAR(100) NOT NULL,
    duracao_minutos INT DEFAULT 50,
    data_aula DATE,
    tipo_conteudo VARCHAR(50), -- Ex: 'legislacao', 'primeiros_socorros'
    status ENUM('pendente', 'agendada', 'concluida', 'cancelada') DEFAULT 'pendente',
    aula_id INT NULL, -- Referência à aula real quando agendada
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
    FOREIGN KEY (aula_id) REFERENCES aulas(id) ON DELETE SET NULL,
    INDEX idx_turma_ordem (turma_id, ordem),
    INDEX idx_data (data_aula),
    INDEX idx_status (status),
    UNIQUE KEY unique_turma_ordem (turma_id, ordem)
);

-- 3. TABELA DE ALUNOS MATRICULADOS NA TURMA
CREATE TABLE IF NOT EXISTS turma_alunos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    turma_id INT NOT NULL,
    aluno_id INT NOT NULL,
    status ENUM('matriculado', 'ativo', 'concluido', 'desistente') DEFAULT 'matriculado',
    data_matricula DATE DEFAULT (CURRENT_DATE),
    data_conclusao DATE NULL,
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_turma_aluno (turma_id, aluno_id),
    INDEX idx_status (status),
    INDEX idx_aluno_status (aluno_id, status)
);

-- 4. MODIFICAR TABELA AULAS PARA SUPORTAR TURMAS
ALTER TABLE aulas ADD COLUMN IF NOT EXISTS turma_id INT DEFAULT NULL;
ALTER TABLE aulas ADD COLUMN IF NOT EXISTS turma_aula_id INT DEFAULT NULL;
ALTER TABLE aulas ADD INDEX IF NOT EXISTS idx_turma (turma_id);
ALTER TABLE aulas ADD INDEX IF NOT EXISTS idx_turma_aula (turma_aula_id);

-- Adicionar foreign keys após criar as tabelas
-- ALTER TABLE aulas ADD FOREIGN KEY IF NOT EXISTS (turma_id) REFERENCES turmas(id) ON DELETE SET NULL;
-- ALTER TABLE aulas ADD FOREIGN KEY IF NOT EXISTS (turma_aula_id) REFERENCES turma_aulas(id) ON DELETE SET NULL;

-- 5. INSERIR DADOS DE EXEMPLO PARA TESTE
INSERT INTO turmas (nome, instrutor_id, tipo_aula, categoria_cnh, data_inicio, data_fim, status, cfc_id) VALUES
('Curso Teórico AB - Manhã', 1, 'teorica', 'AB', '2024-02-01', '2024-03-15', 'ativo', 1),
('Curso Teórico B - Noite', 2, 'teorica', 'B', '2024-02-05', '2024-03-20', 'ativo', 1),
('Aula Teste', 1, 'teorica', 'AB', '2024-01-22', '2024-01-22', 'agendado', 1);

-- 6. INSERIR AULAS DE EXEMPLO PARA AS TURMAS
INSERT INTO turma_aulas (turma_id, ordem, nome_aula, duracao_minutos, tipo_conteudo) VALUES
-- Turma 1: Curso Teórico AB - Manhã
(1, 1, 'Legislação de Trânsito - Parte 1', 50, 'legislacao'),
(1, 2, 'Legislação de Trânsito - Parte 2', 50, 'legislacao'),
(1, 3, 'Primeiros Socorros', 50, 'primeiros_socorros'),
(1, 4, 'Meio Ambiente e Cidadania', 50, 'meio_ambiente'),
(1, 5, 'Direção Defensiva', 50, 'direcao_defensiva'),
(1, 6, 'Mecânica Básica', 50, 'mecanica_basica'),

-- Turma 2: Curso Teórico B - Noite
(2, 1, 'Legislação de Trânsito', 50, 'legislacao'),
(2, 2, 'Primeiros Socorros', 50, 'primeiros_socorros'),
(2, 3, 'Meio Ambiente', 50, 'meio_ambiente'),
(2, 4, 'Direção Defensiva', 50, 'direcao_defensiva'),
(2, 5, 'Mecânica Básica', 50, 'mecanica_basica'),

-- Turma 3: Aula Teste
(3, 1, 'Aula Teste', 50, 'teste');

-- 7. ATUALIZAR CONTADOR DE ALUNOS NAS TURMAS
UPDATE turmas SET total_alunos = (
    SELECT COUNT(*) FROM turma_alunos ta 
    WHERE ta.turma_id = turmas.id AND ta.status IN ('matriculado', 'ativo')
);

-- 8. CRIAR VIEWS PARA FACILITAR CONSULTAS
CREATE VIEW IF NOT EXISTS vw_turmas_completa AS
SELECT 
    t.id,
    t.nome,
    t.tipo_aula,
    t.categoria_cnh,
    t.data_inicio,
    t.data_fim,
    t.status,
    t.total_alunos,
    t.observacoes,
    t.created_at,
    i.nome as instrutor_nome,
    i.email as instrutor_email,
    c.nome as cfc_nome,
    COUNT(ta.id) as total_aulas,
    COUNT(CASE WHEN ta.status = 'concluida' THEN 1 END) as aulas_concluidas
FROM turmas t
LEFT JOIN instrutores i ON t.instrutor_id = i.id
LEFT JOIN cfcs c ON t.cfc_id = c.id
LEFT JOIN turma_aulas ta ON t.id = ta.turma_id
GROUP BY t.id;

-- 9. CRIAR TRIGGERS PARA MANTER INTEGRIDADE
DELIMITER //

CREATE TRIGGER IF NOT EXISTS tr_turma_alunos_insert 
AFTER INSERT ON turma_alunos
FOR EACH ROW
BEGIN
    UPDATE turmas 
    SET total_alunos = (
        SELECT COUNT(*) FROM turma_alunos 
        WHERE turma_id = NEW.turma_id AND status IN ('matriculado', 'ativo')
    )
    WHERE id = NEW.turma_id;
END//

CREATE TRIGGER IF NOT EXISTS tr_turma_alunos_update 
AFTER UPDATE ON turma_alunos
FOR EACH ROW
BEGIN
    UPDATE turmas 
    SET total_alunos = (
        SELECT COUNT(*) FROM turma_alunos 
        WHERE turma_id = NEW.turma_id AND status IN ('matriculado', 'ativo')
    )
    WHERE id = NEW.turma_id;
END//

CREATE TRIGGER IF NOT EXISTS tr_turma_alunos_delete 
AFTER DELETE ON turma_alunos
FOR EACH ROW
BEGIN
    UPDATE turmas 
    SET total_alunos = (
        SELECT COUNT(*) FROM turma_alunos 
        WHERE turma_id = OLD.turma_id AND status IN ('matriculado', 'ativo')
    )
    WHERE id = OLD.turma_id;
END//

DELIMITER ;

-- 10. COMENTÁRIOS E DOCUMENTAÇÃO
-- Este script cria a estrutura completa para o sistema de turmas
-- Baseado na análise do sistema eCondutor
-- 
-- Funcionalidades implementadas:
-- - Criação e gestão de turmas
-- - Configuração de aulas por turma
-- - Matrícula de alunos em turmas
-- - Controle de status e progresso
-- - Integração com sistema de aulas existente
--
-- Para executar este script:
-- 1. Fazer backup do banco atual
-- 2. Executar em ambiente de teste primeiro
-- 3. Verificar integridade dos dados
-- 4. Executar em produção
