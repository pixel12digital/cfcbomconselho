-- =====================================================
-- CORRIGIR TIPO DE USUÁRIO - Carlos da Silva
-- =====================================================
-- 
-- PROBLEMA: O usuário está como 'aluno' mas deveria ser 'instrutor'
-- 
-- INSTRUÇÕES:
-- 1. Abra o phpMyAdmin do banco remoto
-- 2. Selecione o banco de dados do CFC
-- 3. Vá na aba "SQL"
-- 4. Cole e execute este comando
--
-- =====================================================

-- 1. VERIFICAR TIPO ATUAL
SELECT 
    id,
    nome,
    email,
    tipo,
    ativo
FROM usuarios 
WHERE email = 'carlosteste@teste.com.br';

-- =====================================================
-- 2. CORRIGIR TIPO PARA 'instrutor'
-- =====================================================

UPDATE usuarios 
SET tipo = 'instrutor' 
WHERE email = 'carlosteste@teste.com.br';

-- =====================================================
-- 3. VERIFICAR APÓS CORREÇÃO
-- =====================================================

SELECT 
    id,
    nome,
    email,
    tipo,
    ativo,
    CASE 
        WHEN tipo = 'instrutor' THEN '✅ Tipo correto!'
        ELSE '❌ Tipo ainda incorreto'
    END as status_tipo
FROM usuarios 
WHERE email = 'carlosteste@teste.com.br';

