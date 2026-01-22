-- =====================================================
-- CORREÇÃO ESPECÍFICA: Duplicação ROBERIO SANTOS MACHADO
-- =====================================================
-- 
-- DIAGNÓSTICO:
-- - ID 21: roberiosantos981@gmail.com (16/09/2025 14:50) - MAIS RECENTE
-- - ID 31: 716.056.284-41@aluno.cfc (16/09/2025 13:10) - MAIS ANTIGO
-- 
-- ANÁLISE:
-- - Ambos são tipo "aluno" e estão ativos
-- - Nenhuma dependência (0 sessões, 0 logs, 0 CFCs, 0 instrutores)
-- - ID 21 corresponde ao registro em alunos (email real)
-- - ID 31 tem email gerado automaticamente (CPF@aluno.cfc)
-- 
-- DECISÃO: Manter ID 21, remover ID 31
-- =====================================================

-- =====================================================
-- PARTE 1: VERIFICAÇÃO FINAL DE DEPENDÊNCIAS
-- =====================================================

-- Verificar dependências do ID 31 (que será removido)
SELECT 'Sessões' as tipo, COUNT(*) as total FROM sessoes WHERE usuario_id = 31
UNION ALL
SELECT 'Logs' as tipo, COUNT(*) as total FROM logs WHERE usuario_id = 31
UNION ALL
SELECT 'CFCs (responsável)' as tipo, COUNT(*) as total FROM cfcs WHERE responsavel_id = 31
UNION ALL
SELECT 'Instrutores' as tipo, COUNT(*) as total FROM instrutores WHERE usuario_id = 31;

-- Se todos retornarem 0, pode prosseguir com a remoção

-- =====================================================
-- PARTE 2: REMOÇÃO DO REGISTRO DUPLICADO
-- =====================================================

-- ⚠️ ATENÇÃO: Execute apenas se todas as dependências forem 0
-- ⚠️ Faça backup do banco antes de executar

-- Remover o registro duplicado (ID 31)
DELETE FROM usuarios WHERE id = 31;

-- =====================================================
-- PARTE 3: VERIFICAÇÃO FINAL
-- =====================================================

-- Confirmar que apenas um registro do ROBERIO existe agora
SELECT 
    id,
    nome,
    email,
    tipo,
    ativo,
    criado_em
FROM usuarios
WHERE nome LIKE 'ROBERIO SANTOS MACHADO%'
ORDER BY id;

-- Deve retornar apenas 1 registro (ID 21)

-- Confirmar que não há mais duplicações de email
SELECT 
    email,
    COUNT(*) as total
FROM usuarios
GROUP BY email
HAVING COUNT(*) > 1;

-- Deve retornar 0 linhas (nenhuma duplicação)

