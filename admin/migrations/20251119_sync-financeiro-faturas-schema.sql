-- =====================================================
-- MIGRAÇÃO INCREMENTAL: Sincronizar Schema financeiro_faturas
-- Versão: 1.0
-- Data: 2025-11-19
-- Autor: Sistema CFC Bom Conselho
-- 
-- OBJETIVO: Adicionar colunas faltantes na tabela financeiro_faturas
-- para alinhar com a estrutura documentada em 005-create-financeiro-faturas-structure.sql
-- 
-- IMPORTANTE: Este script pode ser executado múltiplas vezes sem erro,
-- pois verifica a existência das colunas antes de adicioná-las.
-- =====================================================

-- Adicionar coluna 'valor' se não existir
-- Posição: após 'titulo'
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'financeiro_faturas'
    AND COLUMN_NAME = 'valor'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE financeiro_faturas ADD COLUMN valor DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER titulo',
    'SELECT "Coluna valor já existe. Pulando..." AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar coluna 'descricao' se não existir
-- Posição: após 'titulo'
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'financeiro_faturas'
    AND COLUMN_NAME = 'descricao'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE financeiro_faturas ADD COLUMN descricao TEXT NULL AFTER titulo',
    'SELECT "Coluna descricao já existe. Pulando..." AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar coluna 'vencimento' se não existir
-- Posição: após 'data_vencimento'
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'financeiro_faturas'
    AND COLUMN_NAME = 'vencimento'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE financeiro_faturas ADD COLUMN vencimento DATE NULL AFTER data_vencimento',
    'SELECT "Coluna vencimento já existe. Pulando..." AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar coluna 'parcelas' se não existir
-- Posição: após 'forma_pagamento'
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'financeiro_faturas'
    AND COLUMN_NAME = 'parcelas'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE financeiro_faturas ADD COLUMN parcelas INT DEFAULT 1 AFTER forma_pagamento',
    'SELECT "Coluna parcelas já existe. Pulando..." AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificação final: listar colunas adicionadas
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'financeiro_faturas'
AND COLUMN_NAME IN ('valor', 'descricao', 'vencimento', 'parcelas')
ORDER BY ORDINAL_POSITION;

