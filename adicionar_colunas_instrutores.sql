-- Script para adicionar colunas que estão faltando na tabela instrutores
-- Execute este script no phpMyAdmin para corrigir a estrutura da tabela

USE u342734079_cfcbomconselho;

-- Adicionar colunas que estão faltando na tabela instrutores
ALTER TABLE instrutores 
ADD COLUMN IF NOT EXISTS nome VARCHAR(100) AFTER id,
ADD COLUMN IF NOT EXISTS cpf VARCHAR(14) AFTER nome,
ADD COLUMN IF NOT EXISTS cnh VARCHAR(20) AFTER cpf,
ADD COLUMN IF NOT EXISTS data_nascimento DATE AFTER cnh,
ADD COLUMN IF NOT EXISTS telefone VARCHAR(20) AFTER data_nascimento,
ADD COLUMN IF NOT EXISTS email VARCHAR(100) AFTER telefone,
ADD COLUMN IF NOT EXISTS endereco TEXT AFTER email,
ADD COLUMN IF NOT EXISTS cidade VARCHAR(100) AFTER endereco,
ADD COLUMN IF NOT EXISTS uf CHAR(2) AFTER cidade;

-- Adicionar comentários para as colunas
ALTER TABLE instrutores 
MODIFY COLUMN cidade VARCHAR(100) COMMENT 'Cidade do instrutor',
MODIFY COLUMN uf CHAR(2) COMMENT 'Estado (UF) do instrutor';

-- Verificar se as colunas foram criadas
DESCRIBE instrutores;

-- Atualizar registros existentes com dados do usuário relacionado
UPDATE instrutores i 
JOIN usuarios u ON i.usuario_id = u.id 
SET i.nome = u.nome,
    i.email = u.email
WHERE i.nome IS NULL OR i.nome = '';

-- Verificar o resultado
SELECT i.id, i.nome, i.email, i.cfc_id, u.nome as nome_usuario, c.nome as nome_cfc
FROM instrutores i 
LEFT JOIN usuarios u ON i.usuario_id = u.id 
LEFT JOIN cfcs c ON i.cfc_id = c.id;
