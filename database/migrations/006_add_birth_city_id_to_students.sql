-- Migration 006: Adicionar birth_city_id na tabela students
-- Substitui campo varchar birth_city por FK para cities (padronização IBGE)

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Adicionar birth_city_id na tabela students
ALTER TABLE `students`
  ADD COLUMN `birth_city_id` int(11) DEFAULT NULL AFTER `birth_state_uf`,
  ADD KEY `birth_city_id` (`birth_city_id`),
  ADD CONSTRAINT `students_ibfk_birth_city` FOREIGN KEY (`birth_city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL;

-- NOTA: O campo birth_city (varchar) será mantido temporariamente para compatibilidade
-- mas não deve ser mais usado. Em futura migration, pode ser removido após migração de dados.

SET FOREIGN_KEY_CHECKS = 1;
