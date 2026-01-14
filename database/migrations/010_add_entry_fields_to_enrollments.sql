-- Migration 010: Adicionar campos de entrada e saldo devedor na tabela enrollments

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Adicionar campos de entrada
ALTER TABLE `enrollments`
ADD COLUMN `entry_amount` decimal(10,2) DEFAULT NULL COMMENT 'Valor da entrada recebida',
ADD COLUMN `entry_payment_method` enum('dinheiro','pix','cartao','boleto') DEFAULT NULL COMMENT 'Forma de pagamento da entrada',
ADD COLUMN `entry_payment_date` date DEFAULT NULL COMMENT 'Data do pagamento da entrada',
ADD COLUMN `outstanding_amount` decimal(10,2) DEFAULT NULL COMMENT 'Saldo devedor (valor_final - entry_amount)';

-- Adicionar índices para melhor performance
ALTER TABLE `enrollments`
ADD KEY `entry_payment_date` (`entry_payment_date`),
ADD KEY `outstanding_amount` (`outstanding_amount`);

SET FOREIGN_KEY_CHECKS = 1;
