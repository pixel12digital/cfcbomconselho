-- Adicionar tipo 'aluno' ao ENUM da tabela usuarios
-- Este script atualiza o campo 'tipo' para incluir a opção 'aluno'

ALTER TABLE usuarios MODIFY COLUMN tipo ENUM('admin','instrutor','secretaria','aluno') NOT NULL DEFAULT 'secretaria';

-- Verificar se a alteração foi aplicada
-- SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'tipo';
