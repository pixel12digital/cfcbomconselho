-- =====================================================
-- SCRIPT SQL COMPLETO E CORRIGIDO PARA O SISTEMA CFC
-- =====================================================

-- 1. CRIAR TABELA DE CONFIGURAÇÕES DE CATEGORIAS
CREATE TABLE IF NOT EXISTS configuracoes_categorias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    categoria VARCHAR(10) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    tipo ENUM('primeira_habilitacao', 'adicao', 'mudanca_categoria') NOT NULL,
    horas_teoricas INT DEFAULT 0,
    horas_praticas_total INT DEFAULT 0,
    horas_praticas_moto INT DEFAULT 0,
    horas_praticas_carro INT DEFAULT 0,
    horas_praticas_carga INT DEFAULT 0,
    horas_praticas_passageiros INT DEFAULT 0,
    horas_praticas_combinacao INT DEFAULT 0,
    observacoes TEXT,
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_categoria_ativo (categoria, ativo),
    INDEX idx_tipo (tipo),
    UNIQUE KEY unique_categoria_ativo (categoria, ativo)
);

-- 2. CRIAR TABELA DE SLOTS DE AULAS
CREATE TABLE IF NOT EXISTS aulas_slots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    aluno_id INT NOT NULL,
    tipo_aula ENUM('teorica', 'pratica') NOT NULL,
    tipo_veiculo ENUM('moto', 'carro', 'carga', 'passageiros', 'combinacao') NULL,
    status ENUM('pendente', 'agendada', 'concluida', 'cancelada') DEFAULT 'pendente',
    ordem INT NOT NULL,
    configuracao_id INT NOT NULL,
    aula_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
    FOREIGN KEY (configuracao_id) REFERENCES configuracoes_categorias(id),
    FOREIGN KEY (aula_id) REFERENCES aulas(id) ON DELETE SET NULL,
    
    INDEX idx_aluno_status (aluno_id, status),
    INDEX idx_tipo_aula (tipo_aula),
    INDEX idx_tipo_veiculo (tipo_veiculo),
    INDEX idx_ordem (ordem)
);

-- 3. CORRIGIR TABELA DE ALUNOS
-- Adicionar categorias faltantes
ALTER TABLE alunos MODIFY COLUMN categoria_cnh ENUM(
    'A', 'B', 'C', 'D', 'E', 
    'AB', 'AC', 'AD', 'AE', 
    'BC', 'BD', 'BE', 
    'CD', 'CE', 'DE', 
    'ACC'
) NOT NULL;

-- Adicionar campos faltantes
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS configuracao_categoria_id INT DEFAULT NULL;
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
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS telefone2 VARCHAR(20) DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS contato_emergencia VARCHAR(100) DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS telefone_emergencia VARCHAR(20) DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS rg_orgao VARCHAR(10) DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS rg_uf CHAR(2) DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS rg_data_emissao DATE DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS data_matricula DATE DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS data_conclusao DATE DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS numero_processo VARCHAR(50) DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS detran_numero VARCHAR(50) DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS valor_curso DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS forma_pagamento ENUM('a_vista', 'parcelado', 'financiado') DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS status_pagamento ENUM('pendente', 'pago', 'atrasado', 'cancelado') DEFAULT 'pendente';

-- Adicionar índices
ALTER TABLE alunos ADD INDEX IF NOT EXISTS idx_configuracao_categoria (configuracao_categoria_id);
ALTER TABLE alunos ADD INDEX IF NOT EXISTS idx_categoria (categoria_cnh);
ALTER TABLE alunos ADD INDEX IF NOT EXISTS idx_status (status);
ALTER TABLE alunos ADD INDEX IF NOT EXISTS idx_ativo (ativo);

-- 4. CORRIGIR TABELA DE AULAS
ALTER TABLE aulas ADD COLUMN IF NOT EXISTS slot_id INT DEFAULT NULL;
ALTER TABLE aulas ADD COLUMN IF NOT EXISTS tipo_veiculo ENUM('moto', 'carro', 'carga', 'passageiros', 'combinacao') DEFAULT NULL;
ALTER TABLE aulas ADD COLUMN IF NOT EXISTS observacoes TEXT DEFAULT NULL;
ALTER TABLE aulas ADD COLUMN IF NOT EXISTS nota DECIMAL(3,1) DEFAULT NULL;
ALTER TABLE aulas ADD COLUMN IF NOT EXISTS presenca BOOLEAN DEFAULT NULL;
ALTER TABLE aulas ADD COLUMN IF NOT EXISTS avaliacao TEXT DEFAULT NULL;

-- Adicionar índices
ALTER TABLE aulas ADD INDEX IF NOT EXISTS idx_slot_id (slot_id);
ALTER TABLE aulas ADD INDEX IF NOT EXISTS idx_tipo_veiculo (tipo_veiculo);
ALTER TABLE aulas ADD INDEX IF NOT EXISTS idx_aluno_status (aluno_id, status);
ALTER TABLE aulas ADD INDEX IF NOT EXISTS idx_instrutor (instrutor_id);
ALTER TABLE aulas ADD INDEX IF NOT EXISTS idx_veiculo (veiculo_id);

-- 5. CORRIGIR TABELA DE VEÍCULOS
ALTER TABLE veiculos ADD COLUMN IF NOT EXISTS tipo_veiculo ENUM('moto', 'carro', 'caminhao', 'onibus', 'carreta') DEFAULT NULL;
ALTER TABLE veiculos ADD COLUMN IF NOT EXISTS renavam VARCHAR(20) DEFAULT NULL;
ALTER TABLE veiculos ADD COLUMN IF NOT EXISTS chassi VARCHAR(50) DEFAULT NULL;
ALTER TABLE veiculos ADD COLUMN IF NOT EXISTS combustivel ENUM('gasolina', 'etanol', 'flex', 'diesel', 'eletrico', 'hibrido') DEFAULT NULL;
ALTER TABLE veiculos ADD COLUMN IF NOT EXISTS quilometragem INT DEFAULT 0;
ALTER TABLE veiculos ADD COLUMN IF NOT EXISTS proxima_manutencao DATE DEFAULT NULL;
ALTER TABLE veiculos ADD COLUMN IF NOT EXISTS valor_aquisicao DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE veiculos ADD COLUMN IF NOT EXISTS data_aquisicao DATE DEFAULT NULL;

-- Adicionar índices
ALTER TABLE veiculos ADD INDEX IF NOT EXISTS idx_tipo_veiculo (tipo_veiculo);

-- 6. CORRIGIR TABELA DE INSTRUTORES
ALTER TABLE instrutores ADD COLUMN IF NOT EXISTS data_nascimento DATE DEFAULT NULL;
ALTER TABLE instrutores ADD COLUMN IF NOT EXISTS endereco TEXT DEFAULT NULL;
ALTER TABLE instrutores ADD COLUMN IF NOT EXISTS telefone VARCHAR(20) DEFAULT NULL;
ALTER TABLE instrutores ADD COLUMN IF NOT EXISTS observacoes TEXT DEFAULT NULL;
ALTER TABLE instrutores ADD COLUMN IF NOT EXISTS atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- 7. CORRIGIR TABELA DE CFCs
ALTER TABLE cfcs ADD COLUMN IF NOT EXISTS razao_social VARCHAR(200) DEFAULT NULL;
ALTER TABLE cfcs ADD COLUMN IF NOT EXISTS bairro VARCHAR(100) DEFAULT NULL;
ALTER TABLE cfcs ADD COLUMN IF NOT EXISTS cidade VARCHAR(100) DEFAULT NULL;
ALTER TABLE cfcs ADD COLUMN IF NOT EXISTS uf CHAR(2) DEFAULT NULL;
ALTER TABLE cfcs ADD COLUMN IF NOT EXISTS cep VARCHAR(10) DEFAULT NULL;
ALTER TABLE cfcs ADD COLUMN IF NOT EXISTS observacoes TEXT DEFAULT NULL;
ALTER TABLE cfcs ADD COLUMN IF NOT EXISTS criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE cfcs ADD COLUMN IF NOT EXISTS atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- 8. CORRIGIR TABELA DE USUÁRIOS
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS ultimo_login DATETIME DEFAULT NULL;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- 9. ADICIONAR FOREIGN KEYS
-- Foreign key para slot_id (será adicionada após criar aulas_slots)
ALTER TABLE aulas ADD FOREIGN KEY IF NOT EXISTS (slot_id) REFERENCES aulas_slots(id) ON DELETE SET NULL;

-- Foreign key para configuracao_categoria_id
ALTER TABLE alunos ADD FOREIGN KEY IF NOT EXISTS (configuracao_categoria_id) REFERENCES configuracoes_categorias(id);

-- 10. INSERIR CONFIGURAÇÕES PADRÃO
INSERT INTO configuracoes_categorias (
    categoria, nome, tipo, horas_teoricas, horas_praticas_total,
    horas_praticas_moto, horas_praticas_carro, horas_praticas_carga,
    horas_praticas_passageiros, horas_praticas_combinacao, observacoes
) VALUES 
-- Primeira Habilitação
('A', 'Motocicletas', 'primeira_habilitacao', 45, 20, 20, 0, 0, 0, 0, 'Configuração padrão - Motocicletas'),
('B', 'Automóveis', 'primeira_habilitacao', 45, 20, 0, 20, 0, 0, 0, 'Configuração padrão - Automóveis'),
('AB', 'Motocicletas + Automóveis', 'primeira_habilitacao', 45, 40, 20, 20, 0, 0, 0, 'Configuração padrão - Motocicletas + Automóveis'),
('ACC', 'Autorização Ciclomotores', 'primeira_habilitacao', 20, 5, 5, 0, 0, 0, 0, 'Configuração padrão - Ciclomotores'),

-- Adição de Categoria
('C', 'Veículos de Carga', 'adicao', 0, 20, 0, 0, 20, 0, 0, 'Configuração padrão - Veículos de Carga'),
('D', 'Veículos de Passageiros', 'adicao', 0, 20, 0, 0, 0, 20, 0, 'Configuração padrão - Veículos de Passageiros'),
('E', 'Combinação de Veículos', 'adicao', 0, 20, 0, 0, 0, 0, 20, 'Configuração padrão - Combinação de Veículos'),

-- Mudança de Categoria
('AC', 'Motocicletas + Carga', 'mudanca_categoria', 0, 40, 20, 0, 20, 0, 0, 'Configuração padrão - Motocicletas + Carga'),
('AD', 'Motocicletas + Passageiros', 'mudanca_categoria', 0, 40, 20, 0, 0, 20, 0, 'Configuração padrão - Motocicletas + Passageiros'),
('AE', 'Motocicletas + Combinação', 'mudanca_categoria', 0, 40, 20, 0, 0, 0, 20, 'Configuração padrão - Motocicletas + Combinação'),
('BC', 'Automóveis + Carga', 'mudanca_categoria', 0, 40, 0, 20, 20, 0, 0, 'Configuração padrão - Automóveis + Carga'),
('BD', 'Automóveis + Passageiros', 'mudanca_categoria', 0, 40, 0, 20, 0, 20, 0, 'Configuração padrão - Automóveis + Passageiros'),
('BE', 'Automóveis + Combinação', 'mudanca_categoria', 0, 40, 0, 20, 0, 0, 20, 'Configuração padrão - Automóveis + Combinação'),
('CD', 'Carga + Passageiros', 'mudanca_categoria', 0, 40, 0, 0, 20, 20, 0, 'Configuração padrão - Carga + Passageiros'),
('CE', 'Carga + Combinação', 'mudanca_categoria', 0, 40, 0, 0, 20, 0, 20, 'Configuração padrão - Carga + Combinação'),
('DE', 'Passageiros + Combinação', 'mudanca_categoria', 0, 40, 0, 0, 0, 20, 20, 'Configuração padrão - Passageiros + Combinação');

-- 11. VERIFICAÇÃO FINAL
-- Executar estas queries para verificar se tudo está correto:
-- SHOW TABLES;
-- DESCRIBE configuracoes_categorias;
-- DESCRIBE aulas_slots;
-- DESCRIBE alunos;
-- DESCRIBE aulas;
-- DESCRIBE veiculos;
-- DESCRIBE instrutores;
-- DESCRIBE cfcs;
-- DESCRIBE usuarios;

-- 12. TESTE DE INTEGRIDADE
-- Verificar se as foreign keys estão funcionando:
-- SELECT COUNT(*) FROM configuracoes_categorias WHERE ativo = 1;
-- SELECT COUNT(*) FROM aulas_slots;
-- SELECT COUNT(*) FROM alunos WHERE configuracao_categoria_id IS NOT NULL;
