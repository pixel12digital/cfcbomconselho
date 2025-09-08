-- =====================================================
-- SCRIPT PARA ADICIONAR CAMPOS DE NATURALIDADE E NACIONALIDADE
-- Sistema CFC - Tabela Alunos
-- =====================================================

USE cfc_sistema;

-- Adicionar coluna naturalidade (cidade - UF)
ALTER TABLE alunos 
ADD COLUMN IF NOT EXISTS naturalidade VARCHAR(100) NULL 
COMMENT 'Cidade e UF de nascimento do aluno';

-- Adicionar coluna nacionalidade
ALTER TABLE alunos 
ADD COLUMN IF NOT EXISTS nacionalidade VARCHAR(50) NULL DEFAULT 'Brasileira' 
COMMENT 'Nacionalidade do aluno';

-- Mostrar a estrutura atualizada da tabela
DESCRIBE alunos;

-- Adicionar índices para melhorar performance nas consultas
CREATE INDEX IF NOT EXISTS idx_alunos_naturalidade ON alunos(naturalidade);
CREATE INDEX IF NOT EXISTS idx_alunos_nacionalidade ON alunos(nacionalidade);

SELECT 'Script executado com sucesso! Campos de naturalidade e nacionalidade adicionados à tabela alunos.' as status;
