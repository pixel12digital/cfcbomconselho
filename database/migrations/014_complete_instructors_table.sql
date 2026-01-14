-- Migration 014: Completar tabela de instrutores com campos essenciais

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Adicionar campos faltantes na tabela instructors
ALTER TABLE `instructors` 
ADD COLUMN `photo_path` varchar(255) DEFAULT NULL COMMENT 'Caminho da foto do instrutor' AFTER `user_id`,
ADD COLUMN `birth_date` date DEFAULT NULL COMMENT 'Data de nascimento' AFTER `cpf`,
ADD COLUMN `credential_number` varchar(50) DEFAULT NULL COMMENT 'Número da credencial do instrutor' AFTER `license_category`,
ADD COLUMN `credential_expiry_date` date DEFAULT NULL COMMENT 'Data de validade da credencial' AFTER `credential_number`,
ADD COLUMN `license_categories` varchar(50) DEFAULT NULL COMMENT 'Categorias de habilitação (ex: AB, BCD)' AFTER `credential_expiry_date`,
ADD COLUMN `cep` varchar(10) DEFAULT NULL COMMENT 'CEP' AFTER `notes`,
ADD COLUMN `address_street` varchar(255) DEFAULT NULL COMMENT 'Logradouro' AFTER `cep`,
ADD COLUMN `address_number` varchar(20) DEFAULT NULL COMMENT 'Número' AFTER `address_street`,
ADD COLUMN `address_complement` varchar(100) DEFAULT NULL COMMENT 'Complemento' AFTER `address_number`,
ADD COLUMN `address_neighborhood` varchar(100) DEFAULT NULL COMMENT 'Bairro' AFTER `address_complement`,
ADD COLUMN `address_city_id` int(11) DEFAULT NULL COMMENT 'Cidade (FK)' AFTER `address_neighborhood`,
ADD COLUMN `address_state_id` int(11) DEFAULT NULL COMMENT 'UF (FK)' AFTER `address_city_id`;

-- Adicionar índices
ALTER TABLE `instructors`
ADD KEY `credential_expiry_date` (`credential_expiry_date`),
ADD KEY `is_active_credential` (`is_active`, `credential_expiry_date`),
ADD KEY `address_city_id` (`address_city_id`),
ADD KEY `address_state_id` (`address_state_id`);

-- Adicionar foreign keys para endereço
ALTER TABLE `instructors`
ADD CONSTRAINT `instructors_ibfk_3` FOREIGN KEY (`address_city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `instructors_ibfk_4` FOREIGN KEY (`address_state_id`) REFERENCES `states` (`id`) ON DELETE SET NULL;

-- Tabela de disponibilidade de horários do instrutor
CREATE TABLE IF NOT EXISTS `instructor_availability` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instructor_id` int(11) NOT NULL,
  `day_of_week` tinyint(1) NOT NULL COMMENT '0=Domingo, 1=Segunda, ..., 6=Sábado',
  `start_time` time NOT NULL COMMENT 'Horário de início',
  `end_time` time NOT NULL COMMENT 'Horário de fim',
  `is_available` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Disponível neste dia',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instructor_day` (`instructor_id`, `day_of_week`),
  KEY `instructor_id` (`instructor_id`),
  KEY `day_of_week` (`day_of_week`),
  CONSTRAINT `instructor_availability_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
