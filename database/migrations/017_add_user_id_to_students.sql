-- Migration 017: Adicionar user_id em students para vincular aluno a usuário

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Adicionar campo user_id em students
ALTER TABLE `students` 
ADD COLUMN `user_id` int(11) DEFAULT NULL COMMENT 'Vinculado a um usuário do sistema (opcional)' AFTER `cfc_id`,
ADD KEY `user_id` (`user_id`),
ADD CONSTRAINT `students_ibfk_user` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS = 1;
