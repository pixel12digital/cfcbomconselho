-- Script para adicionar campo "Cód. Seg. CRV" na tabela veiculos
-- Executar este script para atualizar a estrutura do banco de dados

USE cfc_sistema;

-- Adicionar campo cod_seg_crv na tabela veiculos
ALTER TABLE veiculos 
ADD COLUMN cod_seg_crv VARCHAR(50) NULL COMMENT 'Código de Segurança do CRV' 
AFTER renavam;

-- Verificar se a coluna foi adicionada corretamente
DESCRIBE veiculos;
