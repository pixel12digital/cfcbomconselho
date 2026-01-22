-- Migration 030: Adicionar campos genéricos para rastreamento do gateway de pagamento
-- Campos para armazenar informações de rastreio da cobrança no gateway (Efí, Asaas, etc)

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Adicionar campos genéricos do gateway
ALTER TABLE `enrollments`
ADD COLUMN `gateway_provider` varchar(50) DEFAULT NULL COMMENT 'Provedor do gateway (efi, asaas, etc)',
ADD COLUMN `gateway_charge_id` varchar(255) DEFAULT NULL COMMENT 'ID da cobrança no gateway',
ADD COLUMN `gateway_last_status` varchar(50) DEFAULT NULL COMMENT 'Último status recebido do gateway',
ADD COLUMN `gateway_last_event_at` datetime DEFAULT NULL COMMENT 'Data/hora do último evento recebido do gateway';

-- Adicionar índices para melhor performance
ALTER TABLE `enrollments`
ADD KEY `gateway_provider` (`gateway_provider`),
ADD KEY `gateway_charge_id` (`gateway_charge_id`),
ADD KEY `gateway_last_event_at` (`gateway_last_event_at`);

SET FOREIGN_KEY_CHECKS = 1;
