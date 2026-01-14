-- Migration 005: Adicionar city_id na tabela students (Fase 1.2)
-- Adiciona referência à tabela cities para padronizar cidades

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Adicionar city_id na tabela students
ALTER TABLE `students`
  ADD COLUMN `city_id` int(11) DEFAULT NULL AFTER `city`,
  ADD KEY `city_id` (`city_id`),
  ADD CONSTRAINT `students_ibfk_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS = 1;
