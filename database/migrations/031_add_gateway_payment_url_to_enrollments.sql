-- Migration 031: Adicionar campo para armazenar URL de pagamento do gateway
-- Data: 2024
-- Objetivo: Persistir link de pagamento (PIX QR Code ou Boleto) retornado pela EFI

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Verificar se a coluna já existe antes de adicionar
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'enrollments'
    AND COLUMN_NAME = 'gateway_payment_url'
);

-- Adicionar coluna apenas se não existir
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `enrollments`
     ADD COLUMN `gateway_payment_url` TEXT DEFAULT NULL 
     COMMENT ''URL de pagamento (PIX QR Code ou Boleto) retornada pelo gateway'' AFTER `gateway_last_event_at`',
    'SELECT ''Coluna gateway_payment_url já existe'' AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
