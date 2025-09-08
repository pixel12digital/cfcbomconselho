-- Script SQL para criar tabela de configurações de categorias
-- Este script deve ser executado no banco de dados

CREATE TABLE IF NOT EXISTS configuracoes_categorias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    categoria VARCHAR(10) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    tipo ENUM('primeira_habilitacao', 'adicao', 'combinada') NOT NULL,
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

-- Inserir configurações padrão
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

-- Categorias Combinadas
('AC', 'Motocicletas + Carga', 'combinada', 0, 40, 20, 0, 20, 0, 0, 'Configuração padrão - Motocicletas + Carga'),
('AD', 'Motocicletas + Passageiros', 'combinada', 0, 40, 20, 0, 0, 20, 0, 'Configuração padrão - Motocicletas + Passageiros'),
('AE', 'Motocicletas + Combinação', 'combinada', 0, 40, 20, 0, 0, 0, 20, 'Configuração padrão - Motocicletas + Combinação'),
('BC', 'Automóveis + Carga', 'combinada', 0, 40, 0, 20, 20, 0, 0, 'Configuração padrão - Automóveis + Carga'),
('BD', 'Automóveis + Passageiros', 'combinada', 0, 40, 0, 20, 0, 20, 0, 'Configuração padrão - Automóveis + Passageiros'),
('BE', 'Automóveis + Combinação', 'combinada', 0, 40, 0, 20, 0, 0, 20, 'Configuração padrão - Automóveis + Combinação'),
('CD', 'Carga + Passageiros', 'combinada', 0, 40, 0, 0, 20, 20, 0, 'Configuração padrão - Carga + Passageiros'),
('CE', 'Carga + Combinação', 'combinada', 0, 40, 0, 0, 20, 0, 20, 'Configuração padrão - Carga + Combinação'),
('DE', 'Passageiros + Combinação', 'combinada', 0, 40, 0, 0, 0, 20, 20, 'Configuração padrão - Passageiros + Combinação');

-- Adicionar campo na tabela de alunos para referenciar configuração
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS configuracao_categoria_id INT DEFAULT NULL;
ALTER TABLE alunos ADD INDEX IF NOT EXISTS idx_configuracao_categoria (configuracao_categoria_id);

-- Adicionar campo na tabela de aulas para tipo de veículo
ALTER TABLE aulas ADD COLUMN IF NOT EXISTS tipo_veiculo ENUM('moto', 'carro', 'carga', 'passageiros', 'combinacao') DEFAULT NULL;
ALTER TABLE aulas ADD INDEX IF NOT EXISTS idx_tipo_veiculo (tipo_veiculo);
