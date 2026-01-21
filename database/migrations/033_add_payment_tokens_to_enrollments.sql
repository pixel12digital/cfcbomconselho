-- Migration 033: Adicionar campos para persistir PIX copia-e-cola e linha digitável do boleto
-- Data: 2024
-- Objetivo: Persistir dados essenciais de pagamento (PIX copia-e-cola e linha digitável) para reduzir dependência de consultas externas

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Adicionar colunas para PIX copia-e-cola e linha digitável do boleto
ALTER TABLE `enrollments`
  ADD COLUMN `gateway_pix_code` TEXT NULL COMMENT 'PIX copia-e-cola (quando aplicável)' AFTER `gateway_payment_url`,
  ADD COLUMN `gateway_barcode` VARCHAR(255) NULL COMMENT 'Linha digitável do boleto (quando aplicável)' AFTER `gateway_pix_code`;

SET FOREIGN_KEY_CHECKS = 1;
