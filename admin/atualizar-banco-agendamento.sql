-- =====================================================
-- ATUALIZAÇÃO DO BANCO PARA SISTEMA DE AGENDAMENTO
-- =====================================================

USE cfc_sistema;

-- Adicionar campo veiculo_id na tabela aulas (se não existir)
ALTER TABLE aulas ADD COLUMN IF NOT EXISTS veiculo_id INT;
ALTER TABLE aulas ADD COLUMN IF NOT EXISTS atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Adicionar foreign key para veiculo_id (se não existir)
-- Primeiro verificar se a constraint já existe
SET @constraint_name = (
    SELECT CONSTRAINT_NAME 
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = 'cfc_sistema' 
    AND TABLE_NAME = 'aulas' 
    AND COLUMN_NAME = 'veiculo_id'
    AND REFERENCED_TABLE_NAME = 'veiculos'
);

SET @sql = IF(@constraint_name IS NULL, 
    'ALTER TABLE aulas ADD CONSTRAINT fk_aulas_veiculo FOREIGN KEY (veiculo_id) REFERENCES veiculos(id)',
    'SELECT "Foreign key já existe" as status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Atualizar estrutura da tabela logs se necessário
ALTER TABLE logs MODIFY COLUMN IF EXISTS tabela_afetada VARCHAR(50);
ALTER TABLE logs MODIFY COLUMN IF EXISTS dados_anteriores TEXT;
ALTER TABLE logs MODIFY COLUMN IF EXISTS dados_novos TEXT;
ALTER TABLE logs MODIFY COLUMN IF EXISTS ip_address VARCHAR(45);

-- Renomear colunas se existirem com nomes antigos
SET @sql = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'cfc_sistema' AND TABLE_NAME = 'logs' AND COLUMN_NAME = 'tabela_afetada'),
    'ALTER TABLE logs CHANGE COLUMN tabela_afetada tabela VARCHAR(50)',
    'SELECT "Coluna tabela_afetada não existe" as status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'cfc_sistema' AND TABLE_NAME = 'logs' AND COLUMN_NAME = 'dados_anteriores'),
    'ALTER TABLE logs DROP COLUMN dados_anteriores',
    'SELECT "Coluna dados_anteriores não existe" as status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'cfc_sistema' AND TABLE_NAME = 'logs' AND COLUMN_NAME = 'dados_novos'),
    'ALTER TABLE logs DROP COLUMN dados_novos',
    'SELECT "Coluna dados_novos não existe" as status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'cfc_sistema' AND TABLE_NAME = 'logs' AND COLUMN_NAME = 'ip_address'),
    'ALTER TABLE logs CHANGE COLUMN ip_address ip VARCHAR(45)',
    'SELECT "Coluna ip_address não existe" as status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar coluna dados se não existir
ALTER TABLE logs ADD COLUMN IF NOT EXISTS dados TEXT;

-- Verificar se há dados de teste para o sistema funcionar
SELECT COUNT(*) as total_usuarios FROM usuarios;
SELECT COUNT(*) as total_cfcs FROM cfcs;
SELECT COUNT(*) as total_alunos FROM alunos;
SELECT COUNT(*) as total_instrutores FROM instrutores;
SELECT COUNT(*) as total_veiculos FROM veiculos;

-- Inserir dados de teste se não existirem
INSERT IGNORE INTO usuarios (nome, email, senha, tipo, cpf, telefone) VALUES 
('Instrutor Teste', 'instrutor@cfc.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'instrutor', '111.111.111-11', '(11) 88888-8888');

INSERT IGNORE INTO alunos (nome, cpf, rg, data_nascimento, endereco, telefone, email, cfc_id, categoria_cnh, status) VALUES 
('Aluno Teste', '222.222.222-22', '12.345.678-9', '1990-01-01', 'Rua Teste, 456', '(11) 77777-7777', 'aluno@teste.com', 1, 'B', 'ativo');

INSERT IGNORE INTO instrutores (usuario_id, cfc_id, credencial, categoria_habilitacao) VALUES 
(2, 1, 'INSTR001', 'A, B, C, D, E');

INSERT IGNORE INTO veiculos (cfc_id, placa, modelo, marca, ano, categoria_cnh, ativo) VALUES 
(1, 'ABC-1234', 'Gol', 'Volkswagen', 2020, 'B', 1);

-- Verificar estrutura final
DESCRIBE aulas;
DESCRIBE logs;

SELECT 'Banco atualizado com sucesso para o sistema de agendamento!' as status;
