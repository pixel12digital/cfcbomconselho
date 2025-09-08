-- =====================================================
-- CORREÇÕES E MELHORIAS NO BANCO DE DADOS
-- =====================================================

-- 1. CORREÇÃO: Adicionar categorias faltantes na tabela alunos
-- A tabela atual só tem: A, B, C, D, E, AB, AC, AD, AE
-- Faltam: BC, BD, BE, CD, CE, DE, ACC

ALTER TABLE alunos MODIFY COLUMN categoria_cnh ENUM(
    'A', 'B', 'C', 'D', 'E', 
    'AB', 'AC', 'AD', 'AE', 
    'BC', 'BD', 'BE', 
    'CD', 'CE', 'DE', 
    'ACC'
) NOT NULL;

-- 2. CORREÇÃO: Adicionar campos faltantes na tabela alunos
-- Campos que existem no sistema atual mas não estão no script original

ALTER TABLE alunos ADD COLUMN IF NOT EXISTS numero VARCHAR(20) DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS bairro VARCHAR(100) DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS cidade VARCHAR(100) DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS estado CHAR(2) DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS cep VARCHAR(10) DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS naturalidade VARCHAR(100) DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS nacionalidade VARCHAR(100) DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS estado_civil ENUM('solteiro', 'casado', 'divorciado', 'viuvo', 'uniao_estavel') DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS profissao VARCHAR(100) DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS escolaridade ENUM('fundamental', 'medio', 'superior', 'pos_graduacao') DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS observacoes TEXT DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS ativo BOOLEAN DEFAULT TRUE;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- 3. CORREÇÃO: Adicionar campos faltantes na tabela veiculos
-- Campos que existem no sistema atual mas não estão no script original

ALTER TABLE veiculos ADD COLUMN IF NOT EXISTS tipo_veiculo ENUM('moto', 'carro', 'caminhao', 'onibus', 'carreta') DEFAULT NULL;

-- 4. CORREÇÃO: Adicionar campo slot_id na tabela aulas
ALTER TABLE aulas ADD COLUMN IF NOT EXISTS slot_id INT DEFAULT NULL;
ALTER TABLE aulas ADD INDEX IF NOT EXISTS idx_slot_id (slot_id);

-- 5. CORREÇÃO: Adicionar campo tipo_veiculo na tabela aulas
ALTER TABLE aulas ADD COLUMN IF NOT EXISTS tipo_veiculo ENUM('moto', 'carro', 'carga', 'passageiros', 'combinacao') DEFAULT NULL;
ALTER TABLE aulas ADD INDEX IF NOT EXISTS idx_tipo_veiculo (tipo_veiculo);

-- 6. CORREÇÃO: Adicionar campo configuracao_categoria_id na tabela alunos
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS configuracao_categoria_id INT DEFAULT NULL;
ALTER TABLE alunos ADD INDEX IF NOT EXISTS idx_configuracao_categoria (configuracao_categoria_id);

-- 7. CORREÇÃO: Adicionar foreign key para slot_id (será adicionada após criar aulas_slots)
-- Esta será adicionada no script aulas_slots.sql

-- 8. CORREÇÃO: Adicionar índices faltantes para performance
CREATE INDEX IF NOT EXISTS idx_alunos_categoria ON alunos(categoria_cnh);
CREATE INDEX IF NOT EXISTS idx_alunos_status ON alunos(status);
CREATE INDEX IF NOT EXISTS idx_alunos_ativo ON alunos(ativo);
CREATE INDEX IF NOT EXISTS idx_aulas_aluno_status ON aulas(aluno_id, status);
CREATE INDEX IF NOT EXISTS idx_aulas_instrutor ON aulas(instrutor_id);
CREATE INDEX IF NOT EXISTS idx_aulas_veiculo ON aulas(veiculo_id);

-- 9. CORREÇÃO: Adicionar campos de auditoria nas tabelas principais
ALTER TABLE cfcs ADD COLUMN IF NOT EXISTS criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE cfcs ADD COLUMN IF NOT EXISTS atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE instrutores ADD COLUMN IF NOT EXISTS atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- 10. CORREÇÃO: Adicionar campos faltantes na tabela usuarios
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS ultimo_login DATETIME DEFAULT NULL;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- 11. VERIFICAÇÃO: Verificar se todas as foreign keys estão corretas
-- As foreign keys existentes estão corretas, apenas adicionar as novas

-- 12. CORREÇÃO: Adicionar campos de endereço estruturado na tabela alunos
-- Se o sistema usa endereço como JSON, manter compatibilidade
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS endereco_json JSON DEFAULT NULL;

-- 13. CORREÇÃO: Adicionar campos de contato adicional
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS telefone2 VARCHAR(20) DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS contato_emergencia VARCHAR(100) DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS telefone_emergencia VARCHAR(20) DEFAULT NULL;

-- 14. CORREÇÃO: Adicionar campos de documentação
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS rg_orgao VARCHAR(10) DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS rg_uf CHAR(2) DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS rg_data_emissao DATE DEFAULT NULL;

-- 15. CORREÇÃO: Adicionar campos de acompanhamento
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS data_matricula DATE DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS data_conclusao DATE DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS numero_processo VARCHAR(50) DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS detran_numero VARCHAR(50) DEFAULT NULL;

-- 16. CORREÇÃO: Adicionar campos de pagamento (se necessário)
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS valor_curso DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS forma_pagamento ENUM('a_vista', 'parcelado', 'financiado') DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS status_pagamento ENUM('pendente', 'pago', 'atrasado', 'cancelado') DEFAULT 'pendente';

-- 17. CORREÇÃO: Adicionar campos de instrutor
ALTER TABLE instrutores ADD COLUMN IF NOT EXISTS data_nascimento DATE DEFAULT NULL;
ALTER TABLE instrutores ADD COLUMN IF NOT EXISTS endereco TEXT DEFAULT NULL;
ALTER TABLE instrutores ADD COLUMN IF NOT EXISTS telefone VARCHAR(20) DEFAULT NULL;
ALTER TABLE instrutores ADD COLUMN IF NOT EXISTS observacoes TEXT DEFAULT NULL;

-- 18. CORREÇÃO: Adicionar campos de veículo
ALTER TABLE veiculos ADD COLUMN IF NOT EXISTS renavam VARCHAR(20) DEFAULT NULL;
ALTER TABLE veiculos ADD COLUMN IF NOT EXISTS chassi VARCHAR(50) DEFAULT NULL;
ALTER TABLE veiculos ADD COLUMN IF NOT EXISTS combustivel ENUM('gasolina', 'etanol', 'flex', 'diesel', 'eletrico', 'hibrido') DEFAULT NULL;
ALTER TABLE veiculos ADD COLUMN IF NOT EXISTS quilometragem INT DEFAULT 0;
ALTER TABLE veiculos ADD COLUMN IF NOT EXISTS proxima_manutencao DATE DEFAULT NULL;
ALTER TABLE veiculos ADD COLUMN IF NOT EXISTS valor_aquisicao DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE veiculos ADD COLUMN IF NOT EXISTS data_aquisicao DATE DEFAULT NULL;

-- 19. CORREÇÃO: Adicionar campos de aula
ALTER TABLE aulas ADD COLUMN IF NOT EXISTS observacoes TEXT DEFAULT NULL;
ALTER TABLE aulas ADD COLUMN IF NOT EXISTS nota DECIMAL(3,1) DEFAULT NULL;
ALTER TABLE aulas ADD COLUMN IF NOT EXISTS presenca BOOLEAN DEFAULT NULL;
ALTER TABLE aulas ADD COLUMN IF NOT EXISTS avaliacao TEXT DEFAULT NULL;

-- 20. CORREÇÃO: Adicionar campos de CFC
ALTER TABLE cfcs ADD COLUMN IF NOT EXISTS razao_social VARCHAR(200) DEFAULT NULL;
ALTER TABLE cfcs ADD COLUMN IF NOT EXISTS bairro VARCHAR(100) DEFAULT NULL;
ALTER TABLE cfcs ADD COLUMN IF NOT EXISTS cidade VARCHAR(100) DEFAULT NULL;
ALTER TABLE cfcs ADD COLUMN IF NOT EXISTS uf CHAR(2) DEFAULT NULL;
ALTER TABLE cfcs ADD COLUMN IF NOT EXISTS cep VARCHAR(10) DEFAULT NULL;
ALTER TABLE cfcs ADD COLUMN IF NOT EXISTS observacoes TEXT DEFAULT NULL;

-- 21. VERIFICAÇÃO FINAL: Verificar se todas as tabelas têm os campos necessários
-- Esta query pode ser executada para verificar a estrutura atual:
-- DESCRIBE alunos;
-- DESCRIBE aulas;
-- DESCRIBE veiculos;
-- DESCRIBE instrutores;
-- DESCRIBE cfcs;
-- DESCRIBE usuarios;
