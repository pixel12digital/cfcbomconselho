-- =====================================================
-- SCRIPT PARA ADICIONAR CAMPOS DE NATURALIDADE E NACIONALIDADE
-- Sistema CFC - Tabela Alunos
-- =====================================================

USE cfc_sistema;

-- Verificar se as colunas já existem antes de adicionar
SET @sql = '';

-- Verificar se a coluna naturalidade já existe
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'cfc_sistema' 
  AND TABLE_NAME = 'alunos' 
  AND COLUMN_NAME = 'naturalidade';

-- Se não existir, adicionar a coluna naturalidade
IF @col_exists = 0 THEN
    SET @sql = 'ALTER TABLE alunos ADD COLUMN naturalidade VARCHAR(100) NULL COMMENT "Cidade e UF de nascimento do aluno"';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
    SELECT 'Coluna naturalidade adicionada com sucesso!' as resultado;
ELSE
    SELECT 'Coluna naturalidade já existe!' as resultado;
END IF;

-- Verificar se a coluna nacionalidade já existe
SELECT COUNT(*) INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'cfc_sistema' 
  AND TABLE_NAME = 'alunos' 
  AND COLUMN_NAME = 'nacionalidade';

-- Se não existir, adicionar a coluna nacionalidade
IF @col_exists = 0 THEN
    SET @sql = 'ALTER TABLE alunos ADD COLUMN nacionalidade VARCHAR(50) NULL DEFAULT "Brasileira" COMMENT "Nacionalidade do aluno"';
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
    SELECT 'Coluna nacionalidade adicionada com sucesso!' as resultado;
ELSE
    SELECT 'Coluna nacionalidade já existe!' as resultado;
END IF;

-- Mostrar a estrutura atualizada da tabela
DESCRIBE alunos;

-- Adicionar índices para melhorar performance nas consultas
CREATE INDEX IF NOT EXISTS idx_alunos_naturalidade ON alunos(naturalidade);
CREATE INDEX IF NOT EXISTS idx_alunos_nacionalidade ON alunos(nacionalidade);

SELECT 'Script executado com sucesso! Campos de naturalidade e nacionalidade adicionados à tabela alunos.' as status;
