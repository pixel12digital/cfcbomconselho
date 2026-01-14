-- Migration 015: Adicionar campos de cancelamento na tabela lessons

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Adicionar campos de cancelamento
ALTER TABLE `lessons` 
ADD COLUMN `canceled_at` timestamp NULL DEFAULT NULL COMMENT 'Data/hora do cancelamento' AFTER `completed_at`,
ADD COLUMN `canceled_by` int(11) DEFAULT NULL COMMENT 'Usuário que cancelou a aula' AFTER `canceled_at`,
ADD COLUMN `cancel_reason` text DEFAULT NULL COMMENT 'Motivo do cancelamento' AFTER `canceled_by`;

-- Adicionar índices
ALTER TABLE `lessons`
ADD KEY `canceled_at` (`canceled_at`),
ADD KEY `canceled_by` (`canceled_by`);

-- Adicionar foreign key
ALTER TABLE `lessons`
ADD CONSTRAINT `lessons_ibfk_canceled_by` FOREIGN KEY (`canceled_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS = 1;
