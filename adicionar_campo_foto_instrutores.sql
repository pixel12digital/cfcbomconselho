-- =====================================================
-- ADICIONAR CAMPO FOTO NA TABELA INSTRUTORES
-- =====================================================

-- Adicionar campo foto na tabela instrutores
ALTER TABLE instrutores 
ADD COLUMN IF NOT EXISTS foto VARCHAR(255) NULL COMMENT 'Caminho da foto do instrutor' 
AFTER observacoes;

-- Verificar se o campo foi adicionado corretamente
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'instrutores'
AND COLUMN_NAME = 'foto';

-- Mostrar estrutura atualizada da tabela
DESCRIBE instrutores;
