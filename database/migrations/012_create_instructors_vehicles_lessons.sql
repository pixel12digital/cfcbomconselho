-- Migration 012: Tabelas de Instrutores, Veículos e Aulas (Agenda)

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Tabela de Instrutores
CREATE TABLE IF NOT EXISTS `instructors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cfc_id` int(11) NOT NULL DEFAULT 1,
  `user_id` int(11) DEFAULT NULL COMMENT 'Vinculado a um usuário do sistema (opcional)',
  `name` varchar(255) NOT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL COMMENT 'Número da CNH',
  `license_category` varchar(10) DEFAULT NULL COMMENT 'Categoria da CNH (A, B, C, etc.)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cfc_id` (`cfc_id`),
  KEY `user_id` (`user_id`),
  KEY `is_active` (`is_active`),
  CONSTRAINT `instructors_ibfk_1` FOREIGN KEY (`cfc_id`) REFERENCES `cfcs` (`id`),
  CONSTRAINT `instructors_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Veículos
CREATE TABLE IF NOT EXISTS `vehicles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cfc_id` int(11) NOT NULL DEFAULT 1,
  `plate` varchar(10) NOT NULL COMMENT 'Placa do veículo',
  `brand` varchar(100) DEFAULT NULL COMMENT 'Marca',
  `model` varchar(100) DEFAULT NULL COMMENT 'Modelo',
  `year` int(4) DEFAULT NULL COMMENT 'Ano',
  `color` varchar(50) DEFAULT NULL COMMENT 'Cor',
  `category` varchar(10) DEFAULT NULL COMMENT 'Categoria (A, B, C, etc.)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cfc_plate` (`cfc_id`, `plate`),
  KEY `cfc_id` (`cfc_id`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Aulas
CREATE TABLE IF NOT EXISTS `lessons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cfc_id` int(11) NOT NULL DEFAULT 1,
  `student_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL COMMENT 'Veículo obrigatório para aulas práticas',
  `type` enum('pratica') NOT NULL DEFAULT 'pratica',
  `status` enum('agendada','em_andamento','concluida','cancelada','no_show') NOT NULL DEFAULT 'agendada',
  `scheduled_date` date NOT NULL,
  `scheduled_time` time NOT NULL,
  `duration_minutes` int(11) NOT NULL DEFAULT 50 COMMENT 'Duração em minutos',
  `started_at` timestamp NULL DEFAULT NULL COMMENT 'Quando a aula foi iniciada',
  `completed_at` timestamp NULL DEFAULT NULL COMMENT 'Quando a aula foi concluída',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL COMMENT 'Usuário que criou o agendamento',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cfc_id` (`cfc_id`),
  KEY `student_id` (`student_id`),
  KEY `enrollment_id` (`enrollment_id`),
  KEY `instructor_id` (`instructor_id`),
  KEY `vehicle_id` (`vehicle_id`),
  KEY `scheduled_date` (`scheduled_date`),
  KEY `status` (`status`),
  KEY `type` (`type`),
  CONSTRAINT `lessons_ibfk_1` FOREIGN KEY (`cfc_id`) REFERENCES `cfcs` (`id`),
  CONSTRAINT `lessons_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `lessons_ibfk_3` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`),
  CONSTRAINT `lessons_ibfk_4` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`),
  CONSTRAINT `lessons_ibfk_5` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lessons_ibfk_6` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
