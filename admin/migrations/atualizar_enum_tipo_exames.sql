-- Migration: Atualizar ENUM do campo tipo na tabela exames
-- Data: 2025-01-27
-- Descrição: Adiciona 'teorico' e 'pratico' ao ENUM do campo tipo
-- IMPORTANTE: Execute este script ANTES de tentar corrigir os exames vazios

-- Verificar estrutura atual
SELECT COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'exames'
AND COLUMN_NAME = 'tipo';

-- Atualizar ENUM para incluir 'teorico' e 'pratico'
ALTER TABLE exames 
MODIFY COLUMN tipo ENUM('medico', 'psicotecnico', 'teorico', 'pratico') NOT NULL;

-- Verificar estrutura atualizada
SELECT COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'exames'
AND COLUMN_NAME = 'tipo';

-- Agora você pode executar a correção dos exames vazios
-- UPDATE exames 
-- SET tipo = 'teorico' 
-- WHERE COALESCE(TRIM(tipo), '') = '' OR tipo IS NULL;

