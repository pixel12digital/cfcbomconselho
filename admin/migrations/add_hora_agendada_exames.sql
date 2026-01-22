-- Migration: Adicionar campo hora_agendada na tabela exames
-- Data: 2025-01-27
-- Descrição: Adiciona campo TIME para armazenar o horário do exame/prova agendado

-- Verificar se a coluna já existe antes de adicionar (compatibilidade MySQL 5.7+)
SET @col_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'exames'
    AND COLUMN_NAME = 'hora_agendada'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE exames ADD COLUMN hora_agendada TIME NULL AFTER data_agendada',
    'SELECT "Coluna hora_agendada já existe na tabela exames" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

