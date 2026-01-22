-- =====================================================
-- MIGRAÇÃO INCREMENTAL: Adicionar coluna data_vencimento
-- Versão: 1.0
-- Data: 2025-11-19
-- Autor: Sistema CFC Bom Conselho
-- 
-- OBJETIVO: Adicionar a coluna data_vencimento na tabela financeiro_faturas
-- para alinhar com o código que usa data_vencimento como coluna oficial.
-- 
-- CONTEXTO: O código em admin/index.php?action=create está tentando inserir
-- em data_vencimento, mas a tabela não possui essa coluna, gerando erro:
-- "Unknown column 'data_vencimento' in 'INSERT INTO'"
-- 
-- IMPORTANTE: Execute este script manualmente no banco (phpMyAdmin/CLI)
-- antes de testar novamente a criação de faturas.
-- =====================================================

-- Verificar se a coluna já existe antes de adicionar
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'financeiro_faturas'
    AND COLUMN_NAME = 'data_vencimento'
);

-- Se não existir, adicionar como NULL primeiro (mais seguro)
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE financeiro_faturas ADD COLUMN data_vencimento DATE NULL AFTER valor_total',
    'SELECT "Coluna data_vencimento já existe. Pulando..." AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar se existe coluna 'vencimento' antiga com dados
SET @vencimento_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'financeiro_faturas'
    AND COLUMN_NAME = 'vencimento'
);

-- Se existir coluna 'vencimento' e 'data_vencimento' foi criada, migrar dados
SET @sql = IF(@vencimento_exists > 0 AND @col_exists = 0,
    'UPDATE financeiro_faturas SET data_vencimento = vencimento WHERE data_vencimento IS NULL AND vencimento IS NOT NULL',
    'SELECT "Nenhuma migração de dados necessária." AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Modificar para NOT NULL (apenas se a coluna foi criada agora)
-- Nota: Se já houver registros com NULL, este comando pode falhar.
-- Nesse caso, você precisará preencher os NULLs manualmente antes de executar.
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE financeiro_faturas MODIFY COLUMN data_vencimento DATE NOT NULL',
    'SELECT "Coluna data_vencimento já existe. Não modificando para NOT NULL automaticamente." AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificação final: mostrar estrutura da coluna
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_TYPE,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'financeiro_faturas'
AND COLUMN_NAME = 'data_vencimento';

