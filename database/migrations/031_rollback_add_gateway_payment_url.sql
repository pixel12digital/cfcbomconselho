-- Rollback Migration 031: Remover campo gateway_payment_url
-- ATENÇÃO: Este script remove a coluna gateway_payment_url da tabela enrollments
-- Execute apenas se necessário reverter a migration 031

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Verificar se a coluna existe antes de remover
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'enrollments'
    AND COLUMN_NAME = 'gateway_payment_url'
);

-- Remover coluna apenas se existir
SET @sql = IF(@col_exists > 0,
    'ALTER TABLE `enrollments` DROP COLUMN `gateway_payment_url`',
    'SELECT ''Coluna gateway_payment_url não existe'' AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
