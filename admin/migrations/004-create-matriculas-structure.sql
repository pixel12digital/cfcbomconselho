-- =====================================================
-- MIGRAÇÃO: Estrutura da Tabela Matrículas
-- Versão: 1.0
-- Data: 2025-01-27 (Fase 1)
-- Autor: Sistema CFC Bom Conselho
-- 
-- NOTA: Esta migration foi criada na Fase 1 para alinhar
-- o install.php com a estrutura real usada pelo sistema.
-- A tabela já existia em produção, mas não estava no install.php.
-- Baseada em: admin/api/matriculas.php
-- =====================================================

-- Tabela de Matrículas
CREATE TABLE IF NOT EXISTS matriculas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    categoria_cnh ENUM('A', 'B', 'C', 'D', 'E', 'AB', 'AC', 'AD', 'AE') NOT NULL,
    tipo_servico VARCHAR(100) NOT NULL,
    status ENUM('ativa', 'concluida', 'trancada', 'cancelada') DEFAULT 'ativa',
    data_inicio DATE NOT NULL,
    data_fim DATE DEFAULT NULL,
    valor_total DECIMAL(10, 2) DEFAULT NULL,
    forma_pagamento VARCHAR(50) DEFAULT NULL,
    observacoes TEXT DEFAULT NULL,
    
    -- Processo DETRAN (campos mencionados no código)
    renach VARCHAR(50) DEFAULT NULL,
    processo_numero VARCHAR(100) DEFAULT NULL,
    processo_numero_detran VARCHAR(100) DEFAULT NULL,
    processo_situacao VARCHAR(100) DEFAULT NULL,
    
    -- Status financeiro
    status_financeiro ENUM('regular', 'inadimplente', 'quitado') DEFAULT 'regular',
    
    -- Auditoria
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_aluno (aluno_id),
    INDEX idx_status (status),
    INDEX idx_categoria_tipo (categoria_cnh, tipo_servico),
    INDEX idx_status_financeiro (status_financeiro),
    
    -- Foreign Keys
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comentários nas colunas
ALTER TABLE matriculas 
    MODIFY COLUMN tipo_servico VARCHAR(100) 
    COMMENT 'Tipo de serviço: 1ª habilitação, adição, mudança, reciclagem, etc.';

