-- Migration 009: Adicionar campos de plano de pagamento e preparação Asaas

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Atualizar enum de payment_method para incluir entrada_parcelas
ALTER TABLE `enrollments` 
MODIFY COLUMN `payment_method` enum('pix','boleto','cartao','entrada_parcelas') NOT NULL;

-- Adicionar campos de parcelamento
ALTER TABLE `enrollments`
ADD COLUMN `installments` int(11) DEFAULT NULL COMMENT 'Número de parcelas (1-12)',
ADD COLUMN `down_payment_amount` decimal(10,2) DEFAULT NULL COMMENT 'Valor da entrada (quando entrada_parcelas)',
ADD COLUMN `down_payment_due_date` date DEFAULT NULL COMMENT 'Data de vencimento da entrada',
ADD COLUMN `first_due_date` date DEFAULT NULL COMMENT 'Data de vencimento da primeira parcela',
ADD COLUMN `billing_status` enum('draft','ready','generated','error') NOT NULL DEFAULT 'draft' COMMENT 'Status da geração de cobrança Asaas';

-- Adicionar índices para melhor performance
ALTER TABLE `enrollments`
ADD KEY `billing_status` (`billing_status`),
ADD KEY `first_due_date` (`first_due_date`);

SET FOREIGN_KEY_CHECKS = 1;
