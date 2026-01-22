-- =====================================================
-- MIGRAÇÃO: Adicionar coluna precisa_trocar_senha
-- Sistema: CFC Bom Conselho
-- Data: 2024
-- =====================================================

-- Verificar se a coluna já existe
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'usuarios'
  AND COLUMN_NAME = 'precisa_trocar_senha';

-- Se a coluna não existir, criar:
-- Execute este comando apenas se a query acima não retornar resultados
ALTER TABLE usuarios
  ADD COLUMN precisa_trocar_senha TINYINT(1) NOT NULL DEFAULT 0 
  COMMENT 'Flag que indica se o usuário precisa trocar a senha no próximo login (1 = sim, 0 = não)' 
  AFTER senha;

-- Verificar se as colunas primeiro_acesso e senha_temporaria existem
-- (usadas pelo CredentialManager para compatibilidade)
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'usuarios'
  AND COLUMN_NAME IN ('primeiro_acesso', 'senha_temporaria');

-- Se primeiro_acesso não existir, criar (opcional, para compatibilidade):
-- ALTER TABLE usuarios
--   ADD COLUMN primeiro_acesso TINYINT(1) NOT NULL DEFAULT 0 AFTER precisa_trocar_senha;

-- Se senha_temporaria não existir, criar (opcional, para compatibilidade):
-- ALTER TABLE usuarios
--   ADD COLUMN senha_temporaria TINYINT(1) NOT NULL DEFAULT 0 AFTER primeiro_acesso;

-- Verificação final: Listar todas as colunas relacionadas a senha
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'usuarios'
  AND COLUMN_NAME IN ('senha', 'precisa_trocar_senha', 'primeiro_acesso', 'senha_temporaria', 'senha_alterada_em')
ORDER BY ORDINAL_POSITION;

