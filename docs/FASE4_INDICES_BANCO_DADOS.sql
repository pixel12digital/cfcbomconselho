-- =====================================================
-- FASE 4: ÍNDICES RECOMENDADOS PARA OTIMIZAÇÃO
-- Sistema CFC - Bom Conselho
-- Data: 2025-01-27
-- =====================================================
-- 
-- Este script cria índices recomendados para melhorar
-- significativamente a performance das queries mais usadas.
-- 
-- IMPORTANTE:
-- - Execute este script em ambiente de desenvolvimento primeiro
-- - Teste todas as funcionalidades após criar os índices
-- - Faça backup do banco antes de executar em produção
-- - Execute durante horário de baixo tráfego
-- 
-- =====================================================

-- =====================================================
-- 1. ÍNDICES PARA TABELA `aulas`
-- =====================================================
-- Esta tabela é uma das mais consultadas e precisa de índices
-- para queries por aluno_id, tipo_aula, status e data_aula

-- Índice composto para queries de progresso prático/teórico
-- Usado em: progresso_pratico.php, progresso_teorico.php
CREATE INDEX IF NOT EXISTS idx_aulas_aluno_tipo_status 
ON aulas(aluno_id, tipo_aula, status);

-- Índice composto para histórico ordenado por data
-- Usado em: historico_aluno.php, historico_aluno.php (API)
CREATE INDEX IF NOT EXISTS idx_aulas_aluno_tipo_data 
ON aulas(aluno_id, tipo_aula, data_aula DESC);

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
-- 2. ÍNDICES PARA TABELA `exames`
-- =====================================================
-- Usado em: exames.php (resumo), historico_aluno.php

-- Índice composto para resumo de exames por aluno
-- Usado em: exames.php?resumo=1
CREATE INDEX IF NOT EXISTS idx_exames_aluno_tipo_data 
ON exames(aluno_id, tipo, data_agendada DESC, data_resultado DESC);

-- Índice para tipo de exame (usado em filtros)
CREATE INDEX IF NOT EXISTS idx_exames_tipo 
ON exames(tipo);

-- Índice para status (usado em filtros)
CREATE INDEX IF NOT EXISTS idx_exames_status 
ON exames(status);

-- =====================================================
-- 3. ÍNDICES PARA TABELA `faturas`
-- =====================================================
-- Usado em: historico_aluno.php (API)

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
-- 4. ÍNDICES PARA TABELA `pagamentos`
-- =====================================================
-- Usado em: historico_aluno.php (API) - otimização N+1

-- Índice composto para buscar data_pagamento por fatura
-- Usado em: historico_aluno.php (subquery de pagamentos)
CREATE INDEX IF NOT EXISTS idx_pagamentos_fatura_data 
ON pagamentos(fatura_id, data_pagamento DESC);

-- Índice para fatura_id (usado em JOINs)
CREATE INDEX IF NOT EXISTS idx_pagamentos_fatura 
ON pagamentos(fatura_id);

-- =====================================================
-- 5. ÍNDICES PARA TABELA `matriculas`
-- =====================================================
-- Usado em: progresso_pratico.php, historico_aluno.php

-- Índice composto para buscar matrícula ativa por aluno
-- Usado em: progresso_pratico.php, historico_aluno.php
CREATE INDEX IF NOT EXISTS idx_matriculas_aluno_status_data 
ON matriculas(aluno_id, status, data_inicio DESC);

-- Índice para status (usado em filtros)
CREATE INDEX IF NOT EXISTS idx_matriculas_status 
ON matriculas(status);

-- =====================================================
-- 6. ÍNDICES PARA TABELA `turma_matriculas`
-- =====================================================
-- Usado em: progresso_teorico.php, historico_aluno.php

-- Índice composto para buscar matrícula teórica ativa
-- Usado em: progresso_teorico.php
CREATE INDEX IF NOT EXISTS idx_turma_matriculas_aluno_data 
ON turma_matriculas(aluno_id, data_matricula DESC);

-- Índice para turma_id (usado em JOINs)
CREATE INDEX IF NOT EXISTS idx_turma_matriculas_turma 
ON turma_matriculas(turma_id);

-- =====================================================
-- 7. ÍNDICES PARA TABELA `alunos`
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
-- VERIFICAÇÃO DE ÍNDICES EXISTENTES
-- =====================================================
-- Execute estas queries para verificar se os índices foram criados:

-- SELECT 
--     TABLE_NAME,
--     INDEX_NAME,
--     COLUMN_NAME,
--     SEQ_IN_INDEX
-- FROM 
--     INFORMATION_SCHEMA.STATISTICS
-- WHERE 
--     TABLE_SCHEMA = DATABASE()
--     AND TABLE_NAME IN ('aulas', 'exames', 'faturas', 'pagamentos', 'matriculas', 'turma_matriculas', 'alunos')
-- ORDER BY 
--     TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

-- =====================================================
-- NOTAS IMPORTANTES
-- =====================================================
-- 
-- 1. PERFORMANCE:
--    - Índices melhoram SELECT, mas podem tornar INSERT/UPDATE mais lentos
--    - Para tabelas com muitas escritas, considere criar índices apenas nas colunas mais consultadas
-- 
-- 2. MANUTENÇÃO:
--    - Execute ANALYZE TABLE após criar índices para atualizar estatísticas
--    - Monitore o uso de índices com EXPLAIN nas queries
-- 
-- 3. ESPAÇO EM DISCO:
--    - Índices ocupam espaço adicional no disco
--    - Para tabelas grandes, considere criar índices gradualmente
-- 
-- 4. TESTES RECOMENDADOS:
--    - Teste todas as funcionalidades após criar os índices
--    - Verifique se não há queries mais lentas após a criação
--    - Monitore o uso de CPU e memória do servidor
-- 
-- =====================================================

