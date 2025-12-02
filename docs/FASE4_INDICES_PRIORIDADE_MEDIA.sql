-- =====================================================
-- FASE 4: ÍNDICES DE PRIORIDADE MÉDIA
-- Sistema CFC - Bom Conselho
-- =====================================================
-- 
-- Execute este script APÓS confirmar que os índices
-- de prioridade ALTA funcionaram bem
-- 
-- IMPORTANTE:
-- - Execute apenas após validar os índices de prioridade ALTA
-- - Continue monitorando o sistema durante a execução
-- 
-- =====================================================

-- =====================================================
-- GRUPO 4: ÍNDICES PARA TABELA `faturas` (PRIORIDADE MÉDIA)
-- =====================================================
-- Impacto: Histórico de faturas por aluno
-- Benefício esperado: Redução de 50-70% no tempo de execução

-- Índice composto para histórico de faturas por aluno
-- Usado em: historico_aluno.php (query de faturas)
CREATE INDEX IF NOT EXISTS idx_faturas_aluno_vencimento 
ON faturas(aluno_id, vencimento DESC, criado_em DESC);

-- Índice para status (usado em filtros)
CREATE INDEX IF NOT EXISTS idx_faturas_status 
ON faturas(status);

-- Índice para matricula_id (usado em JOINs)
CREATE INDEX IF NOT EXISTS idx_faturas_matricula 
ON faturas(matricula_id);

-- =====================================================
-- GRUPO 5: ÍNDICES PARA TABELA `matriculas` (PRIORIDADE MÉDIA)
-- =====================================================
-- Impacto: Busca de matrícula ativa por aluno
-- Benefício esperado: Redução de 40-60% no tempo de execução

-- Índice composto para buscar matrícula ativa por aluno
-- Usado em: progresso_pratico.php, historico_aluno.php
CREATE INDEX IF NOT EXISTS idx_matriculas_aluno_status_data 
ON matriculas(aluno_id, status, data_inicio DESC);

-- Índice para status (usado em filtros)
CREATE INDEX IF NOT EXISTS idx_matriculas_status 
ON matriculas(status);

-- =====================================================
-- GRUPO 6: ÍNDICES PARA TABELA `turma_matriculas` (PRIORIDADE MÉDIA)
-- =====================================================
-- Impacto: Busca de matrícula teórica ativa
-- Benefício esperado: Redução de 40-60% no tempo de execução

-- Índice composto para buscar matrícula teórica ativa
-- Usado em: progresso_teorico.php
CREATE INDEX IF NOT EXISTS idx_turma_matriculas_aluno_data 
ON turma_matriculas(aluno_id, data_matricula DESC);

-- Índice para turma_id (usado em JOINs)
CREATE INDEX IF NOT EXISTS idx_turma_matriculas_turma 
ON turma_matriculas(turma_id);

-- =====================================================
-- VERIFICAÇÃO APÓS EXECUÇÃO
-- =====================================================
-- Execute esta query para verificar se os índices foram criados:

-- SELECT 
--     TABLE_NAME,
--     INDEX_NAME,
--     COLUMN_NAME,
--     SEQ_IN_INDEX
-- FROM 
--     INFORMATION_SCHEMA.STATISTICS
-- WHERE 
--     TABLE_SCHEMA = DATABASE()
--     AND TABLE_NAME IN ('faturas', 'matriculas', 'turma_matriculas')
--     AND INDEX_NAME LIKE 'idx_%'
-- ORDER BY 
--     TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

