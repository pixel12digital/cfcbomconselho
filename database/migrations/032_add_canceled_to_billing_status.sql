-- Migration 032: Adicionar 'canceled' ao ENUM de billing_status

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Adicionar 'canceled' ao ENUM de billing_status
ALTER TABLE `enrollments` 
MODIFY COLUMN `billing_status` enum('draft','ready','generated','error','canceled') NOT NULL DEFAULT 'draft' COMMENT 'Status da geração de cobrança no gateway de pagamento';

SET FOREIGN_KEY_CHECKS = 1;
