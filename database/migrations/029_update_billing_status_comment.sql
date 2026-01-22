-- Migration 029: Atualizar comentário do campo billing_status para ser genérico
-- Remove referência específica ao Asaas, tornando o campo genérico para qualquer gateway

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Atualizar comentário do campo billing_status
ALTER TABLE `enrollments`
MODIFY COLUMN `billing_status` enum('draft','ready','generated','error') NOT NULL DEFAULT 'draft' 
COMMENT 'Status da geração de cobrança no gateway de pagamento';

SET FOREIGN_KEY_CHECKS = 1;
