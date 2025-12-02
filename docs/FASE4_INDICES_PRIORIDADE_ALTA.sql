-- =====================================================
-- FASE 4: ÍNDICES DE PRIORIDADE ALTA
-- Sistema CFC - Bom Conselho
-- =====================================================
-- 
-- Execute este script PRIMEIRO - contém os índices
-- que terão maior impacto imediato na performance
-- 
-- IMPORTANTE:
-- - Execute em horário de baixo tráfego
-- - Faça backup antes de executar
-- - Monitore o sistema durante a execução
-- 
-- =====================================================

-- =====================================================
-- GRUPO 1: ÍNDICES PARA TABELA `aulas` (PRIORIDADE ALTA)
-- =====================================================
-- Impacto: Queries de progresso prático/teórico e histórico
-- Benefício esperado: Redução de 70-90% no tempo de execução

-- Índice composto para queries de progresso prático/teórico
-- Usado em: progresso_pratico.php, progresso_teorico.php
CREATE INDEX IF NOT EXISTS idx_aulas_aluno_tipo_status 
ON aulas(aluno_id, tipo_aula, status);

-- Índice composto para histórico ordenado por data
-- Usado em: historico_aluno.php, historico_aluno.php (API)
CREATE INDEX IF NOT EXISTS idx_aulas_aluno_tipo_data 
ON aulas(aluno_id, tipo_aula, data_aula DESC);

-- =====================================================
-- GRUPO 2: ÍNDICES PARA TABELA `pagamentos` (PRIORIDADE ALTA)
-- =====================================================
-- Impacto: Eliminação de N+1 em faturas
-- Benefício esperado: Redução de 90%+ no tempo de execução

-- Índice composto para buscar data_pagamento por fatura
-- Usado em: historico_aluno.php (subquery de pagamentos)
CREATE INDEX IF NOT EXISTS idx_pagamentos_fatura_data 
ON pagamentos(fatura_id, data_pagamento DESC);

-- =====================================================
-- GRUPO 3: ÍNDICES PARA TABELA `exames` (PRIORIDADE ALTA)
-- =====================================================
-- Impacto: Resumo de exames do aluno
-- Benefício esperado: Redução de 60-75% no tempo de execução

-- Índice composto para resumo de exames por aluno
-- Usado em: exames.php?resumo=1
CREATE INDEX IF NOT EXISTS idx_exames_aluno_tipo_data 
ON exames(aluno_id, tipo, data_agendada DESC, data_resultado DESC);

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
--     AND TABLE_NAME IN ('aulas', 'exames', 'pagamentos')
--     AND INDEX_NAME LIKE 'idx_%'
-- ORDER BY 
--     TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

