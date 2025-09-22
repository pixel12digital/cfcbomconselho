-- =====================================================
-- FASE 1 - ETAPA 1.1: ESTRUTURA DE BANCO + MIGRAÇÃO
-- Sistema de Turmas Teóricas - CFC Bom Conselho
-- =====================================================

-- BACKUP DE SEGURANÇA (executar antes de qualquer alteração)
-- CREATE TABLE turmas_backup AS SELECT * FROM turmas;
-- CREATE TABLE aulas_slots_backup AS SELECT * FROM aulas_slots;

-- =====================================================
-- 1. CRIAR TABELA DE PRESENÇAS
-- =====================================================

CREATE TABLE IF NOT EXISTS turma_presencas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    turma_id INT NOT NULL,
    turma_aula_id INT NOT NULL,
    aluno_id INT NOT NULL,
    presente BOOLEAN DEFAULT FALSE,
    observacao TEXT NULL,
    registrado_por INT NOT NULL,
    registrado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_aula_id) REFERENCES turma_aulas(id) ON DELETE CASCADE,
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id),
    
    UNIQUE KEY unique_presenca (turma_id, turma_aula_id, aluno_id),
    INDEX idx_turma_aula (turma_id, turma_aula_id),
    INDEX idx_aluno_frequencia (aluno_id, presente),
    INDEX idx_data_registro (registrado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. CRIAR TABELA DE DIÁRIO DE CLASSE
-- =====================================================

CREATE TABLE IF NOT EXISTS turma_diario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    turma_aula_id INT NOT NULL,
    conteudo_ministrado TEXT NOT NULL,
    anexos JSON NULL,
    observacoes TEXT NULL,
    criado_por INT NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_por INT NULL,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (turma_aula_id) REFERENCES turma_aulas(id) ON DELETE CASCADE,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id),
    FOREIGN KEY (atualizado_por) REFERENCES usuarios(id),
    
    UNIQUE KEY unique_diario_aula (turma_aula_id),
    INDEX idx_turma_aula_diario (turma_aula_id),
    INDEX idx_criado_por (criado_por),
    INDEX idx_data_criacao (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. ADICIONAR CAMPOS NA TABELA TURMAS
-- =====================================================

-- Adicionar capacidade máxima da turma
ALTER TABLE turmas ADD COLUMN IF NOT EXISTS capacidade_maxima INT DEFAULT 30;

-- Adicionar frequência mínima (em percentual)
ALTER TABLE turmas ADD COLUMN IF NOT EXISTS frequencia_minima DECIMAL(5,2) DEFAULT 75.00;

-- Adicionar sala/local da turma
ALTER TABLE turmas ADD COLUMN IF NOT EXISTS sala_local VARCHAR(100) NULL;

-- Adicionar link para aulas online
ALTER TABLE turmas ADD COLUMN IF NOT EXISTS link_online VARCHAR(255) NULL;

-- Adicionar comentários para os novos campos
ALTER TABLE turmas MODIFY COLUMN capacidade_maxima INT DEFAULT 30 COMMENT 'Capacidade máxima de alunos na turma';
ALTER TABLE turmas MODIFY COLUMN frequencia_minima DECIMAL(5,2) DEFAULT 75.00 COMMENT 'Frequência mínima em percentual para aprovação';
ALTER TABLE turmas MODIFY COLUMN sala_local VARCHAR(100) NULL COMMENT 'Sala ou local físico da turma';
ALTER TABLE turmas MODIFY COLUMN link_online VARCHAR(255) NULL COMMENT 'Link para aulas online (Zoom, Meet, etc.)';

-- =====================================================
-- 4. MIGRAÇÃO DE COMPATIBILIDADE EM AULAS_SLOTS
-- =====================================================

-- Adicionar campos de referência para turmas
ALTER TABLE aulas_slots ADD COLUMN IF NOT EXISTS turma_id INT NULL;
ALTER TABLE aulas_slots ADD COLUMN IF NOT EXISTS turma_aula_id INT NULL;

-- Adicionar comentários para os novos campos
ALTER TABLE aulas_slots MODIFY COLUMN turma_id INT NULL COMMENT 'Referência à turma (NULL para slots individuais)';
ALTER TABLE aulas_slots MODIFY COLUMN turma_aula_id INT NULL COMMENT 'Referência à aula da turma (NULL para slots individuais)';

-- =====================================================
-- 5. CRIAR ÍNDICES PARA PERFORMANCE
-- =====================================================

-- Índices para aulas_slots
CREATE INDEX IF NOT EXISTS idx_aulas_slots_turma ON aulas_slots(turma_id);
CREATE INDEX IF NOT EXISTS idx_aulas_slots_turma_aula ON aulas_slots(turma_aula_id);
CREATE INDEX IF NOT EXISTS idx_aulas_slots_turma_status ON aulas_slots(turma_id, status);

-- Índices para turmas
CREATE INDEX IF NOT EXISTS idx_turmas_capacidade ON turmas(capacidade_maxima);
CREATE INDEX IF NOT EXISTS idx_turmas_frequencia ON turmas(frequencia_minima);

-- =====================================================
-- 6. ADICIONAR FOREIGN KEYS (COM VALIDAÇÃO)
-- =====================================================

-- Verificar se as foreign keys já existem antes de criar
SET @fk_exists = (
    SELECT COUNT(*) 
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'aulas_slots' 
    AND CONSTRAINT_NAME = 'fk_aulas_slots_turma'
);

-- Adicionar foreign key para turma_id em aulas_slots
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE aulas_slots ADD CONSTRAINT fk_aulas_slots_turma FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE SET NULL',
    'SELECT "Foreign key fk_aulas_slots_turma já existe" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar se a foreign key para turma_aula_id já existe
SET @fk_exists = (
    SELECT COUNT(*) 
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'aulas_slots' 
    AND CONSTRAINT_NAME = 'fk_aulas_slots_turma_aula'
);

-- Adicionar foreign key para turma_aula_id em aulas_slots
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE aulas_slots ADD CONSTRAINT fk_aulas_slots_turma_aula FOREIGN KEY (turma_aula_id) REFERENCES turma_aulas(id) ON DELETE SET NULL',
    'SELECT "Foreign key fk_aulas_slots_turma_aula já existe" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 7. ATUALIZAR TRIGGERS EXISTENTES (SE NECESSÁRIO)
-- =====================================================

-- Verificar se o trigger existe e atualizar se necessário
DELIMITER //

DROP TRIGGER IF EXISTS tr_turma_alunos_insert//
DROP TRIGGER IF EXISTS tr_turma_alunos_update//
DROP TRIGGER IF EXISTS tr_turma_alunos_delete//

-- Recriar triggers com validação de capacidade
CREATE TRIGGER tr_turma_alunos_insert 
AFTER INSERT ON turma_alunos
FOR EACH ROW
BEGIN
    DECLARE capacidade_atual INT DEFAULT 0;
    DECLARE capacidade_maxima INT DEFAULT 0;
    
    -- Obter capacidade atual e máxima
    SELECT 
        COUNT(*),
        COALESCE(t.capacidade_maxima, 30)
    INTO capacidade_atual, capacidade_maxima
    FROM turma_alunos ta
    JOIN turmas t ON ta.turma_id = t.id
    WHERE ta.turma_id = NEW.turma_id 
    AND ta.status IN ('matriculado', 'ativo');
    
    -- Atualizar contador de alunos
    UPDATE turmas 
    SET total_alunos = capacidade_atual
    WHERE id = NEW.turma_id;
    
    -- Log de capacidade (opcional)
    IF capacidade_atual > capacidade_maxima THEN
        INSERT INTO logs (usuario_id, acao, tabela_afetada, registro_id, dados_novos, criado_em)
        VALUES (NEW.id, 'CAPACIDADE_EXCEDIDA', 'turmas', NEW.turma_id, 
                CONCAT('Capacidade atual: ', capacidade_atual, ', Máxima: ', capacidade_maxima), 
                NOW());
    END IF;
END//

CREATE TRIGGER tr_turma_alunos_update 
AFTER UPDATE ON turma_alunos
FOR EACH ROW
BEGIN
    DECLARE capacidade_atual INT DEFAULT 0;
    
    -- Atualizar contador de alunos
    SELECT COUNT(*) INTO capacidade_atual
    FROM turma_alunos 
    WHERE turma_id = NEW.turma_id AND status IN ('matriculado', 'ativo');
    
    UPDATE turmas 
    SET total_alunos = capacidade_atual
    WHERE id = NEW.turma_id;
END//

CREATE TRIGGER tr_turma_alunos_delete 
AFTER DELETE ON turma_alunos
FOR EACH ROW
BEGIN
    DECLARE capacidade_atual INT DEFAULT 0;
    
    -- Atualizar contador de alunos
    SELECT COUNT(*) INTO capacidade_atual
    FROM turma_alunos 
    WHERE turma_id = OLD.turma_id AND status IN ('matriculado', 'ativo');
    
    UPDATE turmas 
    SET total_alunos = capacidade_atual
    WHERE id = OLD.turma_id;
END//

DELIMITER ;

-- =====================================================
-- 8. CRIAR VIEWS PARA FACILITAR CONSULTAS
-- =====================================================

-- View para frequência de alunos por turma
CREATE OR REPLACE VIEW vw_frequencia_alunos AS
SELECT 
    tp.turma_id,
    tp.aluno_id,
    a.nome as aluno_nome,
    COUNT(tp.id) as total_aulas,
    COUNT(CASE WHEN tp.presente = TRUE THEN 1 END) as aulas_presentes,
    COUNT(CASE WHEN tp.presente = FALSE THEN 1 END) as aulas_ausentes,
    CASE 
        WHEN COUNT(tp.id) > 0 THEN 
            ROUND((COUNT(CASE WHEN tp.presente = TRUE THEN 1 END) / COUNT(tp.id)) * 100, 2)
        ELSE 0 
    END as percentual_frequencia,
    t.frequencia_minima,
    CASE 
        WHEN COUNT(tp.id) > 0 AND 
             (COUNT(CASE WHEN tp.presente = TRUE THEN 1 END) / COUNT(tp.id)) * 100 >= t.frequencia_minima 
        THEN 'APROVADO' 
        ELSE 'REPROVADO' 
    END as status_frequencia
FROM turma_presencas tp
JOIN alunos a ON tp.aluno_id = a.id
JOIN turmas t ON tp.turma_id = t.id
GROUP BY tp.turma_id, tp.aluno_id, a.nome, t.frequencia_minima;

-- View para resumo de turmas
CREATE OR REPLACE VIEW vw_turmas_resumo AS
SELECT 
    t.id,
    t.nome,
    t.capacidade_maxima,
    t.total_alunos,
    t.frequencia_minima,
    CASE 
        WHEN t.total_alunos >= t.capacidade_maxima THEN 'LOTADA'
        WHEN t.total_alunos >= (t.capacidade_maxima * 0.8) THEN 'QUASE_LOTADA'
        ELSE 'DISPONIVEL'
    END as status_capacidade,
    COUNT(ta.id) as total_aulas_programadas,
    COUNT(CASE WHEN ta.status = 'concluida' THEN 1 END) as aulas_concluidas,
    COUNT(td.id) as aulas_com_diario,
    t.data_inicio,
    t.data_fim,
    t.status
FROM turmas t
LEFT JOIN turma_aulas ta ON t.id = ta.turma_id
LEFT JOIN turma_diario td ON ta.id = td.turma_aula_id
GROUP BY t.id;

-- =====================================================
-- 9. INSERIR DADOS DE TESTE (OPCIONAL)
-- =====================================================

-- Inserir configurações padrão para turmas existentes
UPDATE turmas 
SET 
    capacidade_maxima = 30,
    frequencia_minima = 75.00,
    sala_local = 'Sala 1'
WHERE capacidade_maxima IS NULL;

-- =====================================================
-- 10. VALIDAÇÃO FINAL
-- =====================================================

-- Verificar se todas as tabelas foram criadas
SELECT 
    TABLE_NAME,
    TABLE_ROWS,
    CREATE_TIME
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN ('turma_presencas', 'turma_diario')
ORDER BY TABLE_NAME;

-- Verificar se os campos foram adicionados em turmas
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'turmas' 
AND COLUMN_NAME IN ('capacidade_maxima', 'frequencia_minima', 'sala_local', 'link_online')
ORDER BY COLUMN_NAME;

-- Verificar se os campos foram adicionados em aulas_slots
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_COMMENT
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'aulas_slots' 
AND COLUMN_NAME IN ('turma_id', 'turma_aula_id')
ORDER BY COLUMN_NAME;

-- Verificar foreign keys criadas
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN ('turma_presencas', 'turma_diario', 'aulas_slots')
AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME, CONSTRAINT_NAME;

-- =====================================================
-- SCRIPT CONCLUÍDO
-- =====================================================

SELECT 'ETAPA 1.1 CONCLUÍDA COM SUCESSO!' as status,
       NOW() as timestamp,
       'Estrutura de banco criada e migração realizada' as descricao;
