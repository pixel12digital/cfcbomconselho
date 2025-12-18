-- ============================================
-- QUERIES PARA INVESTIGAR TOKEN DE RESET
-- Token: 676f711eef98d00d96218e326f147ac79a02a2c6dc4e2775538e7e5548bc9fdd
-- ============================================

-- 1. VERIFICAR SE TOKEN EXISTE NO BANCO
-- Hash SHA256 do token
SELECT 
    id,
    login,
    type,
    created_at,
    expires_at,
    used_at,
    TIMESTAMPDIFF(MINUTE, UTC_TIMESTAMP(), expires_at) as minutos_restantes,
    CASE 
        WHEN used_at IS NOT NULL THEN 'JÁ USADO'
        WHEN expires_at < UTC_TIMESTAMP() THEN 'EXPIRADO'
        ELSE 'VÁLIDO'
    END as status
FROM password_resets 
WHERE token_hash = SHA2('676f711eef98d00d96218e326f147ac79a02a2c6dc4e2775538e7e5548bc9fdd', 256)
LIMIT 1;

-- 2. VERIFICAR TIMEZONE DO MYSQL
SELECT 
    NOW() as mysql_now_local,
    UTC_TIMESTAMP() as mysql_now_utc,
    @@session.time_zone as timezone_session,
    @@global.time_zone as timezone_global;

-- 3. SE O TOKEN EXISTIR, BUSCAR O USUÁRIO
-- (Substitua 'LOGIN_DO_TOKEN' pelo valor retornado na query 1)
-- Para aluno, pode ser CPF ou email
SELECT 
    id,
    email,
    cpf,
    tipo,
    ativo,
    LEFT(senha, 20) as senha_hash_preview,
    LENGTH(senha) as senha_len
FROM usuarios 
WHERE tipo = 'aluno' 
AND (
    email = 'LOGIN_DO_TOKEN' 
    OR REPLACE(REPLACE(cpf, '.', ''), '-', '') = 'LOGIN_DO_TOKEN'
)
AND ativo = 1
LIMIT 1;

-- 4. VERIFICAR SCHEMA DA COLUNA SENHA
SHOW COLUMNS FROM usuarios WHERE Field = 'senha';

-- 5. VERIFICAR ÚLTIMOS TOKENS GERADOS (para contexto)
SELECT 
    id,
    login,
    type,
    created_at,
    expires_at,
    used_at,
    TIMESTAMPDIFF(MINUTE, UTC_TIMESTAMP(), expires_at) as minutos_restantes
FROM password_resets 
ORDER BY created_at DESC 
LIMIT 10;

-- 6. VERIFICAR SE HÁ TOKENS EXPIRADOS NÃO USADOS
SELECT 
    COUNT(*) as total_expirados_nao_usados
FROM password_resets 
WHERE expires_at < UTC_TIMESTAMP() 
AND used_at IS NULL;

-- 7. VERIFICAR SE HÁ TOKENS VÁLIDOS PARA O MESMO LOGIN
-- (Substitua 'LOGIN_DO_TOKEN' pelo valor retornado na query 1)
SELECT 
    id,
    login,
    type,
    created_at,
    expires_at,
    used_at
FROM password_resets 
WHERE login = 'LOGIN_DO_TOKEN'
AND expires_at > UTC_TIMESTAMP() 
AND used_at IS NULL
ORDER BY created_at DESC;
