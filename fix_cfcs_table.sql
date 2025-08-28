-- Script para corrigir problemas na estrutura do banco de dados
-- Este script resolve os problemas que estavam causando o erro 500 ao excluir CFCs

USE cfc_sistema;

-- 1. Adicionar campo observacoes na tabela cfcs se não existir
ALTER TABLE cfcs 
ADD COLUMN IF NOT EXISTS observacoes TEXT AFTER ativo;

-- 2. Corrigir estrutura da tabela logs para corresponder ao código
-- Renomear coluna 'tabela' para 'tabela_afetada' se existir
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'cfc_sistema' 
     AND TABLE_NAME = 'logs' 
     AND COLUMN_NAME = 'tabela') > 0,
    'ALTER TABLE logs CHANGE COLUMN tabela tabela_afetada VARCHAR(50);',
    'SELECT "Coluna tabela não existe, pulando alteração";'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar colunas que podem estar faltando na tabela logs
ALTER TABLE logs 
ADD COLUMN IF NOT EXISTS dados_anteriores TEXT AFTER registro_id,
ADD COLUMN IF NOT EXISTS dados_novos TEXT AFTER dados_anteriores;

-- Renomear coluna 'ip' para 'ip_address' se existir
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'cfc_sistema' 
     AND TABLE_NAME = 'logs' 
     AND COLUMN_NAME = 'ip') > 0,
    'ALTER TABLE logs CHANGE COLUMN ip ip_address VARCHAR(45);',
    'SELECT "Coluna ip não existe, pulando alteração";'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Verificar se todas as tabelas existem
SHOW TABLES;

-- 4. Verificar estrutura das tabelas principais
DESCRIBE cfcs;
DESCRIBE logs;

-- 5. Verificar se há dados de teste que podem estar causando problemas
SELECT 'CFCs cadastrados:' as info, COUNT(*) as total FROM cfcs;
SELECT 'Alunos vinculados:' as info, COUNT(*) as total FROM alunos;
SELECT 'Instrutores vinculados:' as info, COUNT(*) as total FROM instrutores;
SELECT 'Veículos vinculados:' as info, COUNT(*) as total FROM veiculos;
SELECT 'Aulas vinculadas:' as info, COUNT(*) as total FROM aulas;
