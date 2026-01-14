-- Migration 008: Adicionar campos DETRAN na tabela enrollments
-- 
-- Motivo: Dados como RENACH, número do processo e protocolo são do PROCESSO/MATRÍCULA,
-- não do cadastro do aluno. Um aluno pode ter múltiplas matrículas ao longo do tempo.

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Adicionar campos DETRAN na tabela enrollments
ALTER TABLE `enrollments`
  ADD COLUMN `renach` VARCHAR(20) NULL DEFAULT NULL AFTER `status`,
  ADD COLUMN `detran_protocolo` VARCHAR(50) NULL DEFAULT NULL AFTER `renach`,
  ADD COLUMN `numero_processo` VARCHAR(50) NULL DEFAULT NULL AFTER `detran_protocolo`,
  ADD COLUMN `situacao_processo` ENUM('nao_iniciado','em_andamento','pendente','concluido','cancelado') 
      NOT NULL DEFAULT 'nao_iniciado' AFTER `numero_processo`;

-- Adicionar índices para melhorar performance de buscas
ALTER TABLE `enrollments`
  ADD INDEX `idx_renach` (`renach`),
  ADD INDEX `idx_detran_protocolo` (`detran_protocolo`),
  ADD INDEX `idx_numero_processo` (`numero_processo`),
  ADD INDEX `idx_situacao_processo` (`situacao_processo`);

SET FOREIGN_KEY_CHECKS = 1;
