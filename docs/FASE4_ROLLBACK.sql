-- =====================================================
-- FASE 4: SCRIPT DE ROLLBACK (REMOVER ÍNDICES)
-- Sistema CFC - Bom Conselho
-- =====================================================
-- 
-- Execute este script APENAS se houver problemas após
-- criar os índices e precisar removê-los
-- 
-- IMPORTANTE:
-- - Use apenas em caso de problemas graves
-- - Faça backup antes de executar
-- - Execute em horário de baixo tráfego
-- 
-- =====================================================

-- =====================================================
-- REMOVER ÍNDICES DE PRIORIDADE ALTA
-- =====================================================

-- Remover índices da tabela aulas
DROP INDEX IF EXISTS idx_aulas_aluno_tipo_status ON aulas;
DROP INDEX IF EXISTS idx_aulas_aluno_tipo_data ON aulas;

-- Remover índices da tabela pagamentos
DROP INDEX IF EXISTS idx_pagamentos_fatura_data ON pagamentos;

-- Remover índices da tabela exames
DROP INDEX IF EXISTS idx_exames_aluno_tipo_data ON exames;

-- =====================================================
-- REMOVER ÍNDICES DE PRIORIDADE MÉDIA
-- =====================================================

-- Remover índices da tabela faturas
DROP INDEX IF EXISTS idx_faturas_aluno_vencimento ON faturas;
DROP INDEX IF EXISTS idx_faturas_status ON faturas;
DROP INDEX IF EXISTS idx_faturas_matricula ON faturas;

-- Remover índices da tabela matriculas
DROP INDEX IF EXISTS idx_matriculas_aluno_status_data ON matriculas;
DROP INDEX IF EXISTS idx_matriculas_status ON matriculas;

-- Remover índices da tabela turma_matriculas
DROP INDEX IF EXISTS idx_turma_matriculas_aluno_data ON turma_matriculas;
DROP INDEX IF EXISTS idx_turma_matriculas_turma ON turma_matriculas;

-- =====================================================
-- REMOVER ÍNDICES COMPLEMENTARES
-- =====================================================

-- Remover índices adicionais da tabela aulas
DROP INDEX IF EXISTS idx_aulas_status ON aulas;
DROP INDEX IF EXISTS idx_aulas_data_aula ON aulas;
DROP INDEX IF EXISTS idx_aulas_instrutor_data ON aulas;

-- Remover índices adicionais da tabela exames
DROP INDEX IF EXISTS idx_exames_tipo ON exames;
DROP INDEX IF EXISTS idx_exames_status ON exames;

-- Remover índices adicionais da tabela pagamentos
DROP INDEX IF EXISTS idx_pagamentos_fatura ON pagamentos;

-- Remover índices da tabela alunos
DROP INDEX IF EXISTS idx_alunos_cfc ON alunos;
DROP INDEX IF EXISTS idx_alunos_status ON alunos;
DROP INDEX IF EXISTS idx_alunos_cfc_status ON alunos;

-- =====================================================
-- VERIFICAÇÃO APÓS ROLLBACK
-- =====================================================
-- Execute esta query para verificar se os índices foram removidos:

-- SELECT 
--     TABLE_NAME,
--     INDEX_NAME
-- FROM 
--     INFORMATION_SCHEMA.STATISTICS
-- WHERE 
--     TABLE_SCHEMA = DATABASE()
--     AND TABLE_NAME IN ('aulas', 'exames', 'faturas', 'pagamentos', 'matriculas', 'turma_matriculas', 'alunos')
--     AND INDEX_NAME LIKE 'idx_%'
-- ORDER BY 
--     TABLE_NAME, INDEX_NAME;

