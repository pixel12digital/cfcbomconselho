-- =====================================================
-- SCRIPT DE DIAGNÓSTICO E CORREÇÃO DE DUPLICAÇÃO
-- Sistema CFC - Usuários Duplicados
-- =====================================================
-- 
-- INSTRUÇÕES:
-- 1. Execute primeiro as queries de DIAGNÓSTICO
-- 2. Analise os resultados
-- 3. Execute as queries de CORREÇÃO apenas se necessário
-- 4. Execute a query de PREVENÇÃO no final
--
-- =====================================================

-- =====================================================
-- PARTE 1: DIAGNÓSTICO
-- =====================================================

-- 1.1 Buscar registros do ROBERIO na tabela de usuários
SELECT 
    id,
    nome,
    email,
    tipo,
    ativo,
    criado_em,
    atualizado_em
FROM usuarios
WHERE nome LIKE 'ROBERIO SANTOS MACHADO%'
ORDER BY id;

-- 1.2 Verificar se esse e outros emails estão duplicados na tabela de usuários
SELECT 
    id,
    nome,
    email,
    tipo,
    ativo,
    criado_em
FROM usuarios
WHERE email IN (
    SELECT email 
    FROM usuarios 
    GROUP BY email 
    HAVING COUNT(*) > 1
)
ORDER BY email, id;

-- 1.3 Verificar se existe aluno com esse nome
SELECT 
    id,
    nome,
    cpf,
    status,
    email
FROM alunos
WHERE nome LIKE 'ROBERIO SANTOS MACHADO%'
ORDER BY id;

-- 1.4 Verificar dependências dos registros duplicados (substitua ID1 e ID2 pelos IDs encontrados)
-- IMPORTANTE: Execute esta query para CADA ID duplicado encontrado
-- Exemplo: Se encontrou IDs 123 e 456, execute duas vezes substituindo os valores

-- Para ID1 (substitua 123 pelo primeiro ID):
SELECT 'Sessões' as tipo, COUNT(*) as total FROM sessoes WHERE usuario_id = 123
UNION ALL
SELECT 'Logs' as tipo, COUNT(*) as total FROM logs WHERE usuario_id = 123
UNION ALL
SELECT 'CFCs (responsável)' as tipo, COUNT(*) as total FROM cfcs WHERE responsavel_id = 123
UNION ALL
SELECT 'Instrutores' as tipo, COUNT(*) as total FROM instrutores WHERE usuario_id = 123;

-- Para ID2 (substitua 456 pelo segundo ID):
SELECT 'Sessões' as tipo, COUNT(*) as total FROM sessoes WHERE usuario_id = 456
UNION ALL
SELECT 'Logs' as tipo, COUNT(*) as total FROM logs WHERE usuario_id = 456
UNION ALL
SELECT 'CFCs (responsável)' as tipo, COUNT(*) as total FROM cfcs WHERE responsavel_id = 456
UNION ALL
SELECT 'Instrutores' as tipo, COUNT(*) as total FROM instrutores WHERE usuario_id = 456;

-- =====================================================
-- PARTE 2: CORREÇÃO (EXECUTE APENAS APÓS DIAGNÓSTICO)
-- =====================================================

-- ⚠️ ATENÇÃO: Antes de executar, confirme:
-- 1. Qual registro deve ser mantido (geralmente o mais recente ou com mais dados)
-- 2. Se há dependências que precisam ser migradas
-- 3. Faça backup do banco antes de executar DELETE

-- 2.1 Migrar dependências do registro duplicado para o registro principal
-- (Execute apenas se houver dependências e substitua os IDs)

-- Exemplo: Migrar sessões (substitua ID_DUPLICADO e ID_PRINCIPAL)
-- UPDATE sessoes SET usuario_id = ID_PRINCIPAL WHERE usuario_id = ID_DUPLICADO;

-- Exemplo: Migrar logs (substitua ID_DUPLICADO e ID_PRINCIPAL)
-- UPDATE logs SET usuario_id = ID_PRINCIPAL WHERE usuario_id = ID_DUPLICADO;

-- 2.2 Remover o registro duplicado
-- (Substitua ID_DUPLICADO pelo ID do registro que será removido)
-- DELETE FROM usuarios WHERE id = ID_DUPLICADO;

-- =====================================================
-- PARTE 3: PREVENÇÃO
-- =====================================================

-- 3.1 Verificar se já existe constraint UNIQUE no email
SHOW INDEX FROM usuarios WHERE Column_name = 'email';

-- 3.2 Adicionar constraint UNIQUE no email (se não existir)
-- ⚠️ ATENÇÃO: Esta query pode falhar se já existirem emails duplicados
-- Resolva todas as duplicações antes de executar

ALTER TABLE usuarios
ADD UNIQUE KEY usuarios_email_unique (email);

-- =====================================================
-- PARTE 4: VERIFICAÇÃO FINAL
-- =====================================================

-- 4.1 Confirmar que não há mais duplicações
SELECT 
    email,
    COUNT(*) as total
FROM usuarios
GROUP BY email
HAVING COUNT(*) > 1;

-- Se esta query retornar 0 linhas, não há mais duplicações!

-- 4.2 Confirmar que o ROBERIO aparece apenas uma vez
SELECT 
    id,
    nome,
    email,
    tipo,
    ativo
FROM usuarios
WHERE nome LIKE 'ROBERIO SANTOS MACHADO%'
ORDER BY id;

