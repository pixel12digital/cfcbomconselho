-- Migration 035: Adicionar campos de endereço estruturado na tabela cfcs
-- Permite armazenar endereço do CFC de forma estruturada (logradouro, número, complemento, etc.)

ALTER TABLE `cfcs` 
ADD COLUMN `endereco_logradouro` VARCHAR(255) DEFAULT NULL COMMENT 'Logradouro do endereço do CFC' AFTER `endereco`,
ADD COLUMN `endereco_numero` VARCHAR(20) DEFAULT NULL COMMENT 'Número do endereço do CFC' AFTER `endereco_logradouro`,
ADD COLUMN `endereco_complemento` VARCHAR(150) DEFAULT NULL COMMENT 'Complemento do endereço do CFC' AFTER `endereco_numero`,
ADD COLUMN `endereco_bairro` VARCHAR(120) DEFAULT NULL COMMENT 'Bairro do endereço do CFC' AFTER `endereco_complemento`,
ADD COLUMN `endereco_cidade` VARCHAR(120) DEFAULT NULL COMMENT 'Cidade do endereço do CFC' AFTER `endereco_bairro`,
ADD COLUMN `endereco_uf` CHAR(2) DEFAULT NULL COMMENT 'UF do endereço do CFC' AFTER `endereco_cidade`,
ADD COLUMN `endereco_cep` VARCHAR(10) DEFAULT NULL COMMENT 'CEP do endereço do CFC' AFTER `endereco_uf`;
