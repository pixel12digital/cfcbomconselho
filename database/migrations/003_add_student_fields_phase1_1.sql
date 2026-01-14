-- Migration 003: Adicionar campos completos ao cadastro de alunos (Fase 1.1)
-- IMPORTANTE: Execute esta migration apenas uma vez. Se precisar reexecutar, verifique se as colunas já existem.

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Adicionar novos campos à tabela students
-- Dados pessoais
ALTER TABLE `students`
  ADD COLUMN `full_name` varchar(255) DEFAULT NULL AFTER `name`,
  ADD COLUMN `birth_date` date DEFAULT NULL AFTER `full_name`,
  ADD COLUMN `remunerated_activity` tinyint(1) NOT NULL DEFAULT 0 AFTER `birth_date`,
  ADD COLUMN `marital_status` varchar(50) DEFAULT NULL AFTER `remunerated_activity`,
  ADD COLUMN `profession` varchar(255) DEFAULT NULL AFTER `marital_status`,
  ADD COLUMN `education_level` varchar(100) DEFAULT NULL AFTER `profession`,
  ADD COLUMN `nationality` varchar(100) DEFAULT NULL AFTER `education_level`,
  ADD COLUMN `birth_state_uf` char(2) DEFAULT NULL AFTER `nationality`,
  ADD COLUMN `birth_city` varchar(255) DEFAULT NULL AFTER `birth_state_uf`;

-- Documentos
ALTER TABLE `students`
  ADD COLUMN `rg_number` varchar(20) DEFAULT NULL AFTER `cpf`,
  ADD COLUMN `rg_issuer` varchar(50) DEFAULT NULL AFTER `rg_number`,
  ADD COLUMN `rg_uf` char(2) DEFAULT NULL AFTER `rg_issuer`,
  ADD COLUMN `rg_issue_date` date DEFAULT NULL AFTER `rg_uf`;

-- Contato
ALTER TABLE `students`
  ADD COLUMN `phone_primary` varchar(20) DEFAULT NULL AFTER `phone`,
  ADD COLUMN `phone_secondary` varchar(20) DEFAULT NULL AFTER `phone_primary`;

-- Emergência
ALTER TABLE `students`
  ADD COLUMN `emergency_contact_name` varchar(255) DEFAULT NULL AFTER `email`,
  ADD COLUMN `emergency_contact_phone` varchar(20) DEFAULT NULL AFTER `emergency_contact_name`;

-- Endereço
ALTER TABLE `students`
  ADD COLUMN `cep` varchar(10) DEFAULT NULL AFTER `emergency_contact_phone`,
  ADD COLUMN `street` varchar(255) DEFAULT NULL AFTER `cep`,
  ADD COLUMN `number` varchar(20) DEFAULT NULL AFTER `street`,
  ADD COLUMN `complement` varchar(255) DEFAULT NULL AFTER `number`,
  ADD COLUMN `neighborhood` varchar(255) DEFAULT NULL AFTER `complement`,
  ADD COLUMN `city` varchar(255) DEFAULT NULL AFTER `neighborhood`,
  ADD COLUMN `state_uf` char(2) DEFAULT NULL AFTER `city`;

-- Foto
ALTER TABLE `students`
  ADD COLUMN `photo_path` varchar(500) DEFAULT NULL AFTER `notes`;

-- Atualizar name para full_name se necessário (migração de dados)
UPDATE `students` SET `full_name` = `name` WHERE `full_name` IS NULL AND `name` IS NOT NULL;

-- Migrar phone para phone_primary se necessário
UPDATE `students` SET `phone_primary` = `phone` WHERE `phone_primary` IS NULL AND `phone` IS NOT NULL;

-- Adicionar índices
ALTER TABLE `students`
  ADD INDEX `idx_birth_date` (`birth_date`),
  ADD INDEX `idx_phone_primary` (`phone_primary`),
  ADD INDEX `idx_cep` (`cep`),
  ADD INDEX `idx_city` (`city`),
  ADD INDEX `idx_state_uf` (`state_uf`);

SET FOREIGN_KEY_CHECKS = 1;
