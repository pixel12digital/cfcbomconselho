-- Migration 002: Tabelas Fase 1 (Serviços, Alunos, Matrículas, Etapas)

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Tabela de Serviços
CREATE TABLE IF NOT EXISTS `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cfc_id` int(11) NOT NULL DEFAULT 1,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `base_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_methods_json` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cfc_id` (`cfc_id`),
  KEY `is_active` (`is_active`),
  KEY `deleted_at` (`deleted_at`),
  CONSTRAINT `services_ibfk_1` FOREIGN KEY (`cfc_id`) REFERENCES `cfcs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Alunos
CREATE TABLE IF NOT EXISTS `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cfc_id` int(11) NOT NULL DEFAULT 1,
  `name` varchar(255) NOT NULL,
  `cpf` varchar(14) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `status` enum('lead','matriculado','em_andamento','concluido','cancelado') NOT NULL DEFAULT 'lead',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cfc_cpf` (`cfc_id`, `cpf`),
  KEY `cfc_id` (`cfc_id`),
  KEY `status` (`status`),
  KEY `name` (`name`),
  KEY `cpf` (`cpf`),
  KEY `phone` (`phone`),
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`cfc_id`) REFERENCES `cfcs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Matrículas
CREATE TABLE IF NOT EXISTS `enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cfc_id` int(11) NOT NULL DEFAULT 1,
  `student_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `discount_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `extra_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `final_price` decimal(10,2) NOT NULL,
  `payment_method` enum('pix','boleto','cartao') NOT NULL,
  `financial_status` enum('em_dia','pendente','bloqueado') NOT NULL DEFAULT 'em_dia',
  `status` enum('ativa','concluida','cancelada') NOT NULL DEFAULT 'ativa',
  `created_by_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cfc_id` (`cfc_id`),
  KEY `student_id` (`student_id`),
  KEY `service_id` (`service_id`),
  KEY `financial_status` (`financial_status`),
  KEY `status` (`status`),
  CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`cfc_id`) REFERENCES `cfcs` (`id`),
  CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `enrollments_ibfk_3` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
  CONSTRAINT `enrollments_ibfk_4` FOREIGN KEY (`created_by_user_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Etapas (Catálogo)
CREATE TABLE IF NOT EXISTS `steps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `order` (`order`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Etapas do Aluno (Instância por matrícula)
CREATE TABLE IF NOT EXISTS `student_steps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enrollment_id` int(11) NOT NULL,
  `step_id` int(11) NOT NULL,
  `status` enum('pendente','concluida') NOT NULL DEFAULT 'pendente',
  `source` enum('cfc','aluno') DEFAULT NULL,
  `validated_by_user_id` int(11) DEFAULT NULL,
  `validated_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `enrollment_step` (`enrollment_id`, `step_id`),
  KEY `enrollment_id` (`enrollment_id`),
  KEY `step_id` (`step_id`),
  KEY `status` (`status`),
  CONSTRAINT `student_steps_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_steps_ibfk_2` FOREIGN KEY (`step_id`) REFERENCES `steps` (`id`),
  CONSTRAINT `student_steps_ibfk_3` FOREIGN KEY (`validated_by_user_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
