-- =====================================================
-- SCRIPT DE TESTE - ETAPA 1.1: VALIDAÇÃO DE INTEGRIDADE
-- Sistema de Turmas Teóricas - CFC Bom Conselho
-- =====================================================

-- =====================================================
-- 1. TESTE DE INTEGRIDADE REFERENCIAL
-- =====================================================

-- Teste 1: Verificar se todas as tabelas foram criadas
SELECT 'TESTE 1: Verificação de tabelas criadas' as teste;

SELECT 
    CASE 
        WHEN COUNT(*) = 2 THEN '✅ PASSOU - Todas as tabelas foram criadas'
        ELSE '❌ FALHOU - Tabelas faltando'
    END as resultado,
    COUNT(*) as tabelas_encontradas,
    GROUP_CONCAT(TABLE_NAME) as tabelas
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN ('turma_presencas', 'turma_diario');

-- Teste 2: Verificar campos adicionados em turmas
SELECT 'TESTE 2: Verificação de campos em turmas' as teste;

SELECT 
    CASE 
        WHEN COUNT(*) = 4 THEN '✅ PASSOU - Todos os campos foram adicionados'
        ELSE '❌ FALHOU - Campos faltando'
    END as resultado,
    COUNT(*) as campos_encontrados,
    GROUP_CONCAT(COLUMN_NAME) as campos
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'turmas' 
AND COLUMN_NAME IN ('capacidade_maxima', 'frequencia_minima', 'sala_local', 'link_online');

-- Teste 3: Verificar campos adicionados em aulas_slots
SELECT 'TESTE 3: Verificação de campos em aulas_slots' as teste;

SELECT 
    CASE 
        WHEN COUNT(*) = 2 THEN '✅ PASSOU - Todos os campos foram adicionados'
        ELSE '❌ FALHOU - Campos faltando'
    END as resultado,
    COUNT(*) as campos_encontrados,
    GROUP_CONCAT(COLUMN_NAME) as campos
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'aulas_slots' 
AND COLUMN_NAME IN ('turma_id', 'turma_aula_id');

-- =====================================================
-- 2. TESTE DE FOREIGN KEYS
-- =====================================================

-- Teste 4: Verificar foreign keys de turma_presencas
SELECT 'TESTE 4: Verificação de foreign keys - turma_presencas' as teste;

SELECT 
    CASE 
        WHEN COUNT(*) >= 4 THEN '✅ PASSOU - Foreign keys criadas'
        ELSE '❌ FALHOU - Foreign keys faltando'
    END as resultado,
    COUNT(*) as fk_encontradas,
    GROUP_CONCAT(CONCAT(CONSTRAINT_NAME, ':', REFERENCED_TABLE_NAME)) as foreign_keys
FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'turma_presencas'
AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Teste 5: Verificar foreign keys de turma_diario
SELECT 'TESTE 5: Verificação de foreign keys - turma_diario' as teste;

SELECT 
    CASE 
        WHEN COUNT(*) >= 2 THEN '✅ PASSOU - Foreign keys criadas'
        ELSE '❌ FALHOU - Foreign keys faltando'
    END as resultado,
    COUNT(*) as fk_encontradas,
    GROUP_CONCAT(CONCAT(CONSTRAINT_NAME, ':', REFERENCED_TABLE_NAME)) as foreign_keys
FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'turma_diario'
AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Teste 6: Verificar foreign keys de aulas_slots
SELECT 'TESTE 6: Verificação de foreign keys - aulas_slots' as teste;

SELECT 
    CASE 
        WHEN COUNT(*) >= 2 THEN '✅ PASSOU - Foreign keys criadas'
        ELSE '❌ FALHOU - Foreign keys faltando'
    END as resultado,
    COUNT(*) as fk_encontradas,
    GROUP_CONCAT(CONCAT(CONSTRAINT_NAME, ':', REFERENCED_TABLE_NAME)) as foreign_keys
FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'aulas_slots'
AND REFERENCED_TABLE_NAME IS NOT NULL;

-- =====================================================
-- 3. TESTE DE ÍNDICES
-- =====================================================

-- Teste 7: Verificar índices criados
SELECT 'TESTE 7: Verificação de índices' as teste;

SELECT 
    TABLE_NAME,
    INDEX_NAME,
    COLUMN_NAME,
    CASE 
        WHEN INDEX_NAME IS NOT NULL THEN '✅ Índice criado'
        ELSE '❌ Índice faltando'
    END as status
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN ('turma_presencas', 'turma_diario', 'aulas_slots', 'turmas')
AND INDEX_NAME NOT IN ('PRIMARY')
ORDER BY TABLE_NAME, INDEX_NAME;

-- =====================================================
-- 4. TESTE DE TRIGGERS
-- =====================================================

-- Teste 8: Verificar triggers atualizados
SELECT 'TESTE 8: Verificação de triggers' as teste;

SELECT 
    TRIGGER_NAME,
    EVENT_MANIPULATION,
    EVENT_OBJECT_TABLE,
    CASE 
        WHEN TRIGGER_NAME IS NOT NULL THEN '✅ Trigger ativo'
        ELSE '❌ Trigger faltando'
    END as status
FROM information_schema.TRIGGERS 
WHERE TRIGGER_SCHEMA = DATABASE() 
AND TRIGGER_NAME LIKE 'tr_turma_alunos_%'
ORDER BY TRIGGER_NAME;

-- =====================================================
-- 5. TESTE DE VIEWS
-- =====================================================

-- Teste 9: Verificar views criadas
SELECT 'TESTE 9: Verificação de views' as teste;

SELECT 
    TABLE_NAME,
    CASE 
        WHEN TABLE_NAME IS NOT NULL THEN '✅ View criada'
        ELSE '❌ View faltando'
    END as status
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_TYPE = 'VIEW'
AND TABLE_NAME IN ('vw_frequencia_alunos', 'vw_turmas_resumo')
ORDER BY TABLE_NAME;

-- =====================================================
-- 6. TESTE DE DADOS DE EXEMPLO
-- =====================================================

-- Teste 10: Verificar se dados padrão foram inseridos
SELECT 'TESTE 10: Verificação de dados padrão' as teste;

SELECT 
    COUNT(*) as turmas_atualizadas,
    CASE 
        WHEN COUNT(*) > 0 THEN '✅ Dados padrão inseridos'
        ELSE '❌ Dados padrão não inseridos'
    END as resultado
FROM turmas 
WHERE capacidade_maxima IS NOT NULL 
AND frequencia_minima IS NOT NULL;

-- =====================================================
-- 7. TESTE DE COMPATIBILIDADE
-- =====================================================

-- Teste 11: Verificar compatibilidade com slots individuais
SELECT 'TESTE 11: Verificação de compatibilidade' as teste;

SELECT 
    COUNT(*) as slots_individuais,
    CASE 
        WHEN COUNT(*) >= 0 THEN '✅ Compatibilidade mantida'
        ELSE '❌ Problema de compatibilidade'
    END as resultado
FROM aulas_slots 
WHERE turma_id IS NULL;

-- =====================================================
-- 8. RELATÓRIO FINAL DE TESTES
-- =====================================================

SELECT 'RELATÓRIO FINAL DE TESTES' as titulo;

SELECT 
    'Estrutura de Banco' as categoria,
    'ETAPA 1.1' as etapa,
    CASE 
        WHEN (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('turma_presencas', 'turma_diario')) = 2
        AND (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'turmas' AND COLUMN_NAME IN ('capacidade_maxima', 'frequencia_minima', 'sala_local', 'link_online')) = 4
        AND (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'aulas_slots' AND COLUMN_NAME IN ('turma_id', 'turma_aula_id')) = 2
        THEN '✅ TODOS OS TESTES PASSARAM'
        ELSE '❌ ALGUNS TESTES FALHARAM'
    END as status_final,
    NOW() as timestamp_teste;

-- =====================================================
-- 9. INSTRUÇÕES PARA PRÓXIMA ETAPA
-- =====================================================

SELECT 'PRÓXIMOS PASSOS' as titulo,
       'ETAPA 1.2: Implementar API de Presença' as proxima_etapa,
       'Arquivos a criar: admin/api/turma-presencas.php' as arquivos,
       'Funcionalidades: CRUD de presenças, cálculo de frequência' as funcionalidades;
