-- =====================================================
-- FASE 4: SCRIPT DE VERIFICAÇÃO DE ÍNDICES
-- Sistema CFC - Bom Conselho
-- =====================================================
-- 
-- Execute este script para verificar se todos os índices
-- foram criados corretamente
-- 
-- =====================================================

-- Verificar todos os índices criados nas tabelas principais
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ', ') AS COLUMNS,
    COUNT(*) AS COLUMN_COUNT
FROM 
    INFORMATION_SCHEMA.STATISTICS
WHERE 
    TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME IN ('aulas', 'exames', 'faturas', 'pagamentos', 'matriculas', 'turma_matriculas', 'alunos')
    AND INDEX_NAME LIKE 'idx_%'
GROUP BY 
    TABLE_NAME, INDEX_NAME
ORDER BY 
    TABLE_NAME, INDEX_NAME;

-- =====================================================
-- VERIFICAÇÃO ESPECÍFICA POR TABELA
-- =====================================================

-- Verificar índices da tabela aulas
SELECT 
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX,
    NON_UNIQUE,
    INDEX_TYPE
FROM 
    INFORMATION_SCHEMA.STATISTICS
WHERE 
    TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'aulas'
    AND INDEX_NAME LIKE 'idx_%'
ORDER BY 
    INDEX_NAME, SEQ_IN_INDEX;

-- Verificar índices da tabela exames
SELECT 
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX,
    NON_UNIQUE,
    INDEX_TYPE
FROM 
    INFORMATION_SCHEMA.STATISTICS
WHERE 
    TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'exames'
    AND INDEX_NAME LIKE 'idx_%'
ORDER BY 
    INDEX_NAME, SEQ_IN_INDEX;

-- Verificar índices da tabela pagamentos
SELECT 
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX,
    NON_UNIQUE,
    INDEX_TYPE
FROM 
    INFORMATION_SCHEMA.STATISTICS
WHERE 
    TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'pagamentos'
    AND INDEX_NAME LIKE 'idx_%'
ORDER BY 
    INDEX_NAME, SEQ_IN_INDEX;

-- Verificar índices da tabela faturas
SELECT 
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX,
    NON_UNIQUE,
    INDEX_TYPE
FROM 
    INFORMATION_SCHEMA.STATISTICS
WHERE 
    TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'faturas'
    AND INDEX_NAME LIKE 'idx_%'
ORDER BY 
    INDEX_NAME, SEQ_IN_INDEX;

-- Verificar índices da tabela matriculas
SELECT 
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX,
    NON_UNIQUE,
    INDEX_TYPE
FROM 
    INFORMATION_SCHEMA.STATISTICS
WHERE 
    TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'matriculas'
    AND INDEX_NAME LIKE 'idx_%'
ORDER BY 
    INDEX_NAME, SEQ_IN_INDEX;

-- Verificar índices da tabela turma_matriculas
SELECT 
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX,
    NON_UNIQUE,
    INDEX_TYPE
FROM 
    INFORMATION_SCHEMA.STATISTICS
WHERE 
    TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'turma_matriculas'
    AND INDEX_NAME LIKE 'idx_%'
ORDER BY 
    INDEX_NAME, SEQ_IN_INDEX;

-- Verificar índices da tabela alunos
SELECT 
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX,
    NON_UNIQUE,
    INDEX_TYPE
FROM 
    INFORMATION_SCHEMA.STATISTICS
WHERE 
    TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'alunos'
    AND INDEX_NAME LIKE 'idx_%'
ORDER BY 
    INDEX_NAME, SEQ_IN_INDEX;

-- =====================================================
-- VERIFICAR USO DE ÍNDICES EM QUERIES ESPECÍFICAS
-- =====================================================
-- Execute EXPLAIN nas queries críticas para verificar
-- se os índices estão sendo usados:

-- Exemplo 1: Query de progresso prático
-- EXPLAIN SELECT 
--     COUNT(CASE WHEN status = 'concluida' THEN 1 END) as total_realizadas
-- FROM aulas
-- WHERE aluno_id = 170 AND tipo_aula = 'pratica';

-- Exemplo 2: Query de histórico de faturas
-- EXPLAIN SELECT 
--     f.*, p.data_pagamento
-- FROM faturas f
-- LEFT JOIN (
--     SELECT fatura_id, MAX(data_pagamento) as data_pagamento
--     FROM pagamentos
--     GROUP BY fatura_id
-- ) p ON f.id = p.fatura_id
-- WHERE f.aluno_id = 170
-- ORDER BY f.vencimento DESC;

-- Exemplo 3: Query de resumo de exames
-- EXPLAIN SELECT *
-- FROM exames
-- WHERE aluno_id = 170
-- ORDER BY tipo, data_agendada DESC, data_resultado DESC
-- LIMIT 10;

