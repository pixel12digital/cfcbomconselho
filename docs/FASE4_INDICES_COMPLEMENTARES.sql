-- =====================================================
-- FASE 4: ÍNDICES COMPLEMENTARES (PRIORIDADE BAIXA)
-- Sistema CFC - Bom Conselho
-- =====================================================
-- 
-- Execute este script APÓS confirmar que todos os índices
-- de prioridade ALTA e MÉDIA funcionaram bem
-- 
-- Estes índices são otimizações adicionais para melhorar
-- performance em casos específicos e relatórios
-- 
-- =====================================================

-- =====================================================
-- ÍNDICES ADICIONAIS PARA TABELA `aulas`
-- =====================================================

-- Índice para filtro por status (usado em várias queries)
CREATE INDEX IF NOT EXISTS idx_aulas_status 
ON aulas(status);

-- Índice para data_aula (usado em ORDER BY e filtros de data)
CREATE INDEX IF NOT EXISTS idx_aulas_data_aula 
ON aulas(data_aula);

-- Índice composto para instrutor e data (usado em relatórios)
CREATE INDEX IF NOT EXISTS idx_aulas_instrutor_data 
ON aulas(instrutor_id, data_aula DESC);

-- =====================================================
-- ÍNDICES ADICIONAIS PARA TABELA `exames`
-- =====================================================

-- Índice para tipo de exame (usado em filtros)
CREATE INDEX IF NOT EXISTS idx_exames_tipo 
ON exames(tipo);

-- Índice para status (usado em filtros)
CREATE INDEX IF NOT EXISTS idx_exames_status 
ON exames(status);

-- =====================================================
-- ÍNDICES ADICIONAIS PARA TABELA `pagamentos`
-- =====================================================

-- Índice para fatura_id (usado em JOINs)
CREATE INDEX IF NOT EXISTS idx_pagamentos_fatura 
ON pagamentos(fatura_id);

-- =====================================================
-- ÍNDICES PARA TABELA `alunos`
-- =====================================================
-- Usado em: várias queries de busca e listagem

-- Índice para cfc_id (usado em filtros por CFC)
CREATE INDEX IF NOT EXISTS idx_alunos_cfc 
ON alunos(cfc_id);

-- Índice para status (usado em filtros)
CREATE INDEX IF NOT EXISTS idx_alunos_status 
ON alunos(status);

-- Índice composto para busca por CFC e status
CREATE INDEX IF NOT EXISTS idx_alunos_cfc_status 
ON alunos(cfc_id, status);

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
--     AND TABLE_NAME IN ('aulas', 'exames', 'pagamentos', 'alunos')
--     AND INDEX_NAME LIKE 'idx_%'
-- ORDER BY 
--     TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

