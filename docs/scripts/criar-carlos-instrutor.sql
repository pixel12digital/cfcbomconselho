-- =====================================================
-- CRIAR USUÁRIO CARLOS COMO INSTRUTOR
-- =====================================================
-- 
-- Este script cria o usuário Carlos da Silva como instrutor
-- com a senha já hashada (bcrypt)
--
-- INSTRUÇÕES:
-- 1. Abra o phpMyAdmin do banco remoto
-- 2. Selecione o banco de dados do CFC
-- 3. Vá na aba "SQL"
-- 4. Cole e execute este comando
--
-- =====================================================

-- 1. VERIFICAR ESTRUTURA DA TABELA (execute primeiro)
SHOW COLUMNS FROM usuarios;

-- 2. VERIFICAR SE JÁ EXISTE (opcional)
SELECT 
    id,
    nome,
    email,
    tipo,
    -- Tente ambos os nomes de coluna:
    COALESCE(ativo, status) as status_ativo,
    COALESCE(criado_em, created_at) as data_criacao
FROM usuarios 
WHERE email = 'carlosteste@teste.com.br';

-- =====================================================
-- 3. CRIAR USUÁRIO CARLOS COMO INSTRUTOR
-- =====================================================

-- ESTRUTURA CONFIRMADA:
-- A tabela tem: ativo (tinyint), criado_em (timestamp), status (varchar), created_at (timestamp)
-- O sistema PHP usa: ativo e criado_em
-- Vamos usar os campos que o sistema PHP espera

INSERT INTO usuarios (
    nome,
    email,
    senha,
    tipo,
    ativo,
    status,
    criado_em,
    created_at
) VALUES (
    'Carlos da Silva',
    'carlosteste@teste.com.br',
    '$2y$10$MvcFqEUxPp4FXB7WhXA8nuCAnTzGCH5y57By3Ol2s7Xxy3t1Rzmg6',
    'instrutor',
    1,
    'ativo',
    NOW(),
    NOW()
);

-- =====================================================
-- 4. VERIFICAR APÓS CRIAÇÃO
-- =====================================================

SELECT 
    id,
    nome,
    email,
    tipo,
    ativo,
    status,
    LENGTH(senha) as comprimento_hash,
    LEFT(senha, 10) as hash_preview,
    CASE 
        WHEN senha LIKE '$2y$%' OR senha LIKE '$2a$%' OR senha LIKE '$2b$%' THEN '✅ Bcrypt (correto)'
        ELSE '❌ Não é Bcrypt'
    END as formato_hash,
    CASE 
        WHEN tipo = 'instrutor' THEN '✅ Tipo correto!'
        ELSE '❌ Tipo incorreto'
    END as status_tipo,
    criado_em,
    created_at
FROM usuarios 
WHERE email = 'carlosteste@teste.com.br';

