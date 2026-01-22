-- =====================================================
-- SCRIPT DE VERIFICAÇÃO E CORREÇÃO DE SENHA
-- Usuário: carlosteste@teste.com.br
-- Senha: Los@ngo#081081
-- =====================================================
-- 
-- INSTRUÇÕES:
-- 1. Abra o phpMyAdmin
-- 2. Selecione o banco de dados do CFC
-- 3. Vá na aba "SQL"
-- 4. Cole este script completo
-- 5. Execute
--
-- =====================================================

-- 1. VERIFICAR SE O USUÁRIO EXISTE
SELECT 
    id,
    nome,
    email,
    tipo,
    ativo,
    LENGTH(senha) as comprimento_hash,
    LEFT(senha, 10) as hash_preview,
    CASE 
        WHEN senha LIKE '$2y$%' OR senha LIKE '$2a$%' OR senha LIKE '$2b$%' THEN '✅ Bcrypt (correto)'
        ELSE '❌ Não é Bcrypt (PROBLEMA!)'
    END as formato_hash
FROM usuarios 
WHERE email = 'carlosteste@teste.com.br';

-- =====================================================
-- 2. GERAR NOVO HASH PARA A SENHA
-- =====================================================
-- NOTA: Você precisa gerar o hash usando PHP primeiro
-- Execute este código PHP em um arquivo temporário:
--
-- <?php
-- $senha = 'Los@ngo#081081';
-- $hash = password_hash($senha, PASSWORD_DEFAULT);
-- echo $hash;
-- ?>
--
-- OU use este hash pré-gerado (pode variar, mas funciona):
-- =====================================================

-- 3. ATUALIZAR SENHA COM HASH CORRETO
-- IMPORTANTE: Substitua o hash abaixo pelo hash gerado pelo PHP
-- O hash deve começar com $2y$ e ter 60 caracteres

-- Descomente a linha abaixo e substitua o hash após testar:
-- UPDATE usuarios 
-- SET senha = '$2y$10$SEU_HASH_AQUI' 
-- WHERE email = 'carlosteste@teste.com.br';

-- =====================================================
-- 4. VERIFICAR APÓS ATUALIZAÇÃO
-- =====================================================
-- Execute novamente a query do passo 1 para verificar

