-- Migration 023: Tabela de Solicitações de Reagendamento
-- Sistema de solicitações de reagendamento por alunos (sem chat, apenas solicitação controlada)

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Tabela de Solicitações de Reagendamento
CREATE TABLE IF NOT EXISTS `reschedule_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lesson_id` int(11) NOT NULL COMMENT 'Aula relacionada',
  `student_id` int(11) NOT NULL COMMENT 'Aluno solicitante',
  `user_id` int(11) DEFAULT NULL COMMENT 'Usuário logado do aluno (para auditoria)',
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending' COMMENT 'Status da solicitação',
  `reason` varchar(50) DEFAULT NULL COMMENT 'Motivo: imprevisto, trabalho, saude, outro',
  `message` text DEFAULT NULL COMMENT 'Mensagem opcional do aluno',
  `resolved_by_user_id` int(11) DEFAULT NULL COMMENT 'Usuário que resolveu (admin/secretaria)',
  `resolved_at` timestamp NULL DEFAULT NULL COMMENT 'Data/hora da resolução',
  `resolution_note` text DEFAULT NULL COMMENT 'Nota opcional da resolução',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `lesson_id` (`lesson_id`),
  KEY `student_id` (`student_id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  KEY `resolved_by_user_id` (`resolved_by_user_id`),
  KEY `idx_student_lesson_status` (`student_id`, `lesson_id`, `status`),
  CONSTRAINT `reschedule_requests_ibfk_1` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reschedule_requests_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reschedule_requests_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reschedule_requests_ibfk_4` FOREIGN KEY (`resolved_by_user_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
