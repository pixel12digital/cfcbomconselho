-- Migration 034: Adicionar campo logo_path na tabela cfcs
-- Permite upload de logo por CFC para white-label PWA

ALTER TABLE `cfcs` 
ADD COLUMN `logo_path` VARCHAR(255) DEFAULT NULL COMMENT 'Caminho do arquivo de logo do CFC (para ícones PWA)' AFTER `email`;

-- Índice para performance (opcional, mas recomendado)
-- ALTER TABLE `cfcs` ADD INDEX `idx_logo_path` (`logo_path`);
