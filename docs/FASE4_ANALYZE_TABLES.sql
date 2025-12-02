-- =====================================================
-- FASE 4: ATUALIZAR ESTATÍSTICAS DAS TABELAS
-- Sistema CFC - Bom Conselho
-- =====================================================
-- 
-- Execute este script APÓS criar todos os índices
-- para atualizar as estatísticas do MySQL e garantir
-- que os índices sejam usados de forma otimizada
-- 
-- IMPORTANTE:
-- - Execute após criar todos os índices
-- - Pode levar alguns minutos dependendo do tamanho das tabelas
-- - Execute em horário de baixo tráfego
-- 
-- =====================================================

-- Atualizar estatísticas da tabela aulas
ANALYZE TABLE aulas;

-- Atualizar estatísticas da tabela exames
ANALYZE TABLE exames;

-- Atualizar estatísticas da tabela faturas
ANALYZE TABLE faturas;

-- Atualizar estatísticas da tabela pagamentos
ANALYZE TABLE pagamentos;

-- Atualizar estatísticas da tabela matriculas
ANALYZE TABLE matriculas;

-- Atualizar estatísticas da tabela turma_matriculas
ANALYZE TABLE turma_matriculas;

-- Atualizar estatísticas da tabela alunos
ANALYZE TABLE alunos;

-- =====================================================
-- VERIFICAÇÃO APÓS EXECUÇÃO
-- =====================================================
-- Verificar se as estatísticas foram atualizadas:

-- SELECT 
--     TABLE_NAME,
--     UPDATE_TIME,
--     TABLE_ROWS,
--     DATA_LENGTH,
--     INDEX_LENGTH
-- FROM 
--     INFORMATION_SCHEMA.TABLES
-- WHERE 
--     TABLE_SCHEMA = DATABASE()
--     AND TABLE_NAME IN ('aulas', 'exames', 'faturas', 'pagamentos', 'matriculas', 'turma_matriculas', 'alunos')
-- ORDER BY 
--     TABLE_NAME;

