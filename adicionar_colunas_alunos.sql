-- SQL para adicionar colunas de endereço na tabela alunos
-- Execute este SQL no phpMyAdmin ou no seu cliente MySQL

USE u342734079_cfcbomconselho;

-- Adicionar coluna bairro
ALTER TABLE alunos ADD COLUMN bairro VARCHAR(100) NULL AFTER endereco;

-- Adicionar coluna cidade
ALTER TABLE alunos ADD COLUMN cidade VARCHAR(100) NULL AFTER bairro;

-- Adicionar coluna estado
ALTER TABLE alunos ADD COLUMN estado VARCHAR(50) NULL AFTER cidade;

-- Adicionar coluna cep
ALTER TABLE alunos ADD COLUMN cep VARCHAR(20) NULL AFTER estado;

-- Adicionar coluna numero
ALTER TABLE alunos ADD COLUMN numero VARCHAR(20) NULL AFTER endereco;

-- Adicionar coluna observacoes
ALTER TABLE alunos ADD COLUMN observacoes TEXT NULL AFTER status;

-- Verificar a estrutura atualizada
DESCRIBE alunos;

-- Comentários sobre as colunas adicionadas:
-- bairro: Para armazenar o bairro do aluno
-- cidade: Para armazenar a cidade do aluno  
-- estado: Para armazenar o estado/UF do aluno
-- cep: Para armazenar o CEP do aluno
-- numero: Para armazenar o número do endereço
-- observacoes: Para armazenar observações adicionais sobre o aluno
