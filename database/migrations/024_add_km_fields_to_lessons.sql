-- Migration 024: Adicionar campos de quilometragem e observação do instrutor na tabela lessons

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Adicionar campos de quilometragem e observação do instrutor
ALTER TABLE `lessons`
  ADD COLUMN `km_start` int(11) DEFAULT NULL COMMENT 'Quilometragem inicial do veículo ao iniciar a aula',
  ADD COLUMN `km_end` int(11) DEFAULT NULL COMMENT 'Quilometragem final do veículo ao concluir a aula',
  ADD COLUMN `instructor_notes` text DEFAULT NULL COMMENT 'Observações do instrutor sobre a aula';

SET FOREIGN_KEY_CHECKS = 1;
