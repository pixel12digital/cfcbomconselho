-- Migration 025: Módulo de Curso Teórico
-- Implementa arquitetura curricular configurável para CFCs
-- Permite cadastro de disciplinas, cursos (templates), turmas, sessões, matrículas e presença

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ============================================
-- FASE 1: CONFIGURAÇÕES (Disciplinas e Cursos)
-- ============================================

-- Tabela de Disciplinas (configurável por CFC)
CREATE TABLE IF NOT EXISTS `theory_disciplines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cfc_id` int(11) NOT NULL DEFAULT 1,
  `name` varchar(255) NOT NULL COMMENT 'Nome da disciplina (ex: Legislação de Trânsito)',
  `default_minutes` int(11) DEFAULT NULL COMMENT 'Carga horária padrão em minutos (opcional)',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT 'Ordem de exibição',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cfc_id` (`cfc_id`),
  KEY `active` (`active`),
  KEY `sort_order` (`sort_order`),
  CONSTRAINT `theory_disciplines_ibfk_1` FOREIGN KEY (`cfc_id`) REFERENCES `cfcs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Cursos (templates curriculares)
CREATE TABLE IF NOT EXISTS `theory_courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cfc_id` int(11) NOT NULL DEFAULT 1,
  `name` varchar(255) NOT NULL COMMENT 'Nome do curso (ex: 1ª Habilitação – AB)',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cfc_id` (`cfc_id`),
  KEY `active` (`active`),
  CONSTRAINT `theory_courses_ibfk_1` FOREIGN KEY (`cfc_id`) REFERENCES `cfcs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Relação: Curso ↔ Disciplinas
CREATE TABLE IF NOT EXISTS `theory_course_disciplines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `discipline_id` int(11) NOT NULL,
  `minutes` int(11) DEFAULT NULL COMMENT 'Carga horária específica para este curso (sobrescreve default_minutes)',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT 'Ordem da disciplina no curso',
  `required` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Disciplina obrigatória (1) ou opcional (0)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_discipline` (`course_id`, `discipline_id`),
  KEY `course_id` (`course_id`),
  KEY `discipline_id` (`discipline_id`),
  KEY `sort_order` (`sort_order`),
  CONSTRAINT `theory_course_disciplines_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `theory_courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `theory_course_disciplines_ibfk_2` FOREIGN KEY (`discipline_id`) REFERENCES `theory_disciplines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- FASE 2: OPERAÇÃO (Turmas e Sessões)
-- ============================================

-- Tabela de Turmas Teóricas
CREATE TABLE IF NOT EXISTS `theory_classes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cfc_id` int(11) NOT NULL DEFAULT 1,
  `course_id` int(11) NOT NULL COMMENT 'Template de curso',
  `instructor_id` int(11) NOT NULL COMMENT 'Instrutor que ministra a turma',
  `name` varchar(255) DEFAULT NULL COMMENT 'Nome/código da turma (opcional)',
  `start_date` date DEFAULT NULL COMMENT 'Data de início da turma',
  `status` enum('scheduled','in_progress','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `created_by` int(11) DEFAULT NULL COMMENT 'Usuário que criou a turma',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cfc_id` (`cfc_id`),
  KEY `course_id` (`course_id`),
  KEY `instructor_id` (`instructor_id`),
  KEY `status` (`status`),
  KEY `start_date` (`start_date`),
  CONSTRAINT `theory_classes_ibfk_1` FOREIGN KEY (`cfc_id`) REFERENCES `cfcs` (`id`),
  CONSTRAINT `theory_classes_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `theory_courses` (`id`),
  CONSTRAINT `theory_classes_ibfk_3` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`),
  CONSTRAINT `theory_classes_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Sessões (encontros/aulas por disciplina)
CREATE TABLE IF NOT EXISTS `theory_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL COMMENT 'Turma à qual a sessão pertence',
  `discipline_id` int(11) NOT NULL COMMENT 'Disciplina da sessão',
  `lesson_id` int(11) DEFAULT NULL COMMENT 'Registro na tabela lessons (integração com agenda)',
  `starts_at` datetime NOT NULL COMMENT 'Data/hora de início',
  `ends_at` datetime NOT NULL COMMENT 'Data/hora de término',
  `location` varchar(255) DEFAULT NULL COMMENT 'Local da sessão (opcional)',
  `status` enum('scheduled','done','canceled') NOT NULL DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL COMMENT 'Usuário que criou a sessão',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `class_id` (`class_id`),
  KEY `discipline_id` (`discipline_id`),
  KEY `lesson_id` (`lesson_id`),
  KEY `starts_at` (`starts_at`),
  KEY `status` (`status`),
  CONSTRAINT `theory_sessions_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `theory_classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `theory_sessions_ibfk_2` FOREIGN KEY (`discipline_id`) REFERENCES `theory_disciplines` (`id`),
  CONSTRAINT `theory_sessions_ibfk_3` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE SET NULL,
  CONSTRAINT `theory_sessions_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- FASE 3: MATRÍCULA E PRESENÇA
-- ============================================

-- Tabela de Matrículas na Turma (vínculo aluno ↔ turma)
CREATE TABLE IF NOT EXISTS `theory_enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL COMMENT 'Turma teórica',
  `student_id` int(11) NOT NULL COMMENT 'Aluno matriculado',
  `enrollment_id` int(11) DEFAULT NULL COMMENT 'Matrícula principal (opcional, para rastreabilidade)',
  `status` enum('active','completed','dropped') NOT NULL DEFAULT 'active',
  `enrolled_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de matrícula na turma',
  `completed_at` timestamp NULL DEFAULT NULL COMMENT 'Data de conclusão do curso teórico',
  `created_by` int(11) DEFAULT NULL COMMENT 'Usuário que matriculou',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `class_student` (`class_id`, `student_id`),
  KEY `class_id` (`class_id`),
  KEY `student_id` (`student_id`),
  KEY `enrollment_id` (`enrollment_id`),
  KEY `status` (`status`),
  CONSTRAINT `theory_enrollments_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `theory_classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `theory_enrollments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `theory_enrollments_ibfk_3` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `theory_enrollments_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Presença por Sessão
CREATE TABLE IF NOT EXISTS `theory_attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL COMMENT 'Sessão teórica',
  `student_id` int(11) NOT NULL COMMENT 'Aluno',
  `status` enum('present','absent','justified','makeup') NOT NULL DEFAULT 'absent',
  `notes` text DEFAULT NULL COMMENT 'Observações (admin/instrutor)',
  `marked_by` int(11) DEFAULT NULL COMMENT 'Usuário que marcou a presença',
  `marked_at` timestamp NULL DEFAULT NULL COMMENT 'Data/hora em que a presença foi marcada',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_student` (`session_id`, `student_id`),
  KEY `session_id` (`session_id`),
  KEY `student_id` (`student_id`),
  KEY `status` (`status`),
  KEY `marked_by` (`marked_by`),
  CONSTRAINT `theory_attendance_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `theory_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `theory_attendance_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `theory_attendance_ibfk_3` FOREIGN KEY (`marked_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INTEGRAÇÃO COM LESSONS (Agenda)
-- ============================================

-- Adicionar 'teoria' ao ENUM type em lessons
ALTER TABLE `lessons` 
MODIFY COLUMN `type` enum('pratica','teoria') NOT NULL DEFAULT 'pratica';

-- Tornar vehicle_id opcional (NULL para aulas teóricas)
ALTER TABLE `lessons` 
MODIFY COLUMN `vehicle_id` int(11) DEFAULT NULL COMMENT 'Veículo (obrigatório para aulas práticas, NULL para teóricas)';

-- Adicionar campo para vincular lesson a theory_session (opcional, para rastreabilidade reversa)
ALTER TABLE `lessons`
ADD COLUMN `theory_session_id` int(11) DEFAULT NULL COMMENT 'Sessão teórica relacionada (se type=teoria)',
ADD KEY `theory_session_id` (`theory_session_id`),
ADD CONSTRAINT `lessons_ibfk_theory_session` FOREIGN KEY (`theory_session_id`) REFERENCES `theory_sessions` (`id`) ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS = 1;
