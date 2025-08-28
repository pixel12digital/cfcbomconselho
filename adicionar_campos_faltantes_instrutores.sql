-- Script para adicionar campos faltantes na tabela instrutores
-- Execute este script no phpMyAdmin ou via linha de comando

USE u342734079_cfcbomconselho;

-- Adicionar campo tipo_carga
ALTER TABLE instrutores 
ADD COLUMN tipo_carga VARCHAR(100) NULL COMMENT 'Tipo de carga que o instrutor pode transportar' 
AFTER categoria_habilitacao;

-- Adicionar campo validade_credencial
ALTER TABLE instrutores 
ADD COLUMN validade_credencial DATE NULL COMMENT 'Data de validade da credencial do instrutor' 
AFTER tipo_carga;

-- Adicionar campo observacoes
ALTER TABLE instrutores 
ADD COLUMN observacoes TEXT NULL COMMENT 'Observações e notas sobre o instrutor' 
AFTER validade_credencial;

-- Verificar se os campos foram adicionados
DESCRIBE instrutores;

-- Mostrar estrutura atualizada
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'u342734079_cfcbomconselho' 
AND TABLE_NAME = 'instrutores'
ORDER BY ORDINAL_POSITION;
