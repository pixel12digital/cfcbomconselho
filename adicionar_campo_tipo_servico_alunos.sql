-- Script para adicionar campo tipo_servico na tabela alunos
-- Execute este script no banco de dados para suportar a nova funcionalidade

-- Adicionar coluna tipo_servico na tabela alunos
ALTER TABLE alunos 
ADD COLUMN tipo_servico VARCHAR(50) NOT NULL DEFAULT 'primeira_habilitacao' 
COMMENT 'Tipo de serviço: primeira_habilitacao, adicao, mudanca' 
AFTER categoria_cnh;

-- Atualizar registros existentes baseado na categoria_cnh
UPDATE alunos SET tipo_servico = 'primeira_habilitacao' 
WHERE categoria_cnh IN ('A', 'B', 'AB', 'ACC');

UPDATE alunos SET tipo_servico = 'adicao' 
WHERE categoria_cnh IN ('C', 'D', 'E');

UPDATE alunos SET tipo_servico = 'mudanca' 
WHERE categoria_cnh IN ('AC', 'AD', 'AE', 'BC', 'BD', 'BE', 'CD', 'CE', 'DE');

-- Adicionar índice para melhor performance
CREATE INDEX idx_alunos_tipo_servico ON alunos(tipo_servico);

-- Verificar os dados atualizados
SELECT tipo_servico, categoria_cnh, COUNT(*) as quantidade 
FROM alunos 
GROUP BY tipo_servico, categoria_cnh 
ORDER BY tipo_servico, categoria_cnh;
