-- Migration 022: Tabela de Notificações (Fase 1 - Feed interno)
-- Sistema básico de notificações in-app para alunos

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Tabela de Notificações
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'Destinatário da notificação',
  `type` varchar(50) NOT NULL COMMENT 'Tipo: lesson_scheduled, lesson_rescheduled, lesson_canceled, step_updated, financial_pending',
  `title` varchar(255) NOT NULL COMMENT 'Título curto da notificação',
  `body` text DEFAULT NULL COMMENT 'Corpo/mensagem da notificação',
  `link` varchar(255) DEFAULT NULL COMMENT 'Rota interna opcional (ex: /agenda/123, /financeiro)',
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Marcada como lida',
  `read_at` timestamp NULL DEFAULT NULL COMMENT 'Data/hora da leitura',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `is_read` (`is_read`),
  KEY `created_at` (`created_at`),
  KEY `idx_user_read_created` (`user_id`, `is_read`, `created_at`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
