-- =====================================================
-- MIGRAÇÃO: Estrutura da Tabela Financeiro Faturas
-- Versão: 1.0
-- Data: 2025-01-27 (Fase 1)
-- Autor: Sistema CFC Bom Conselho
-- 
-- NOTA: Esta migration foi criada na Fase 1 para alinhar
-- o install.php com a estrutura real usada pelo sistema.
-- A tabela já existia em produção, mas não estava no install.php.
-- Baseada em: admin/api/financeiro-faturas.php, admin/index.php, admin/pages/financeiro-faturas.php
-- 
-- IMPORTANTE: Há inconsistência no código - API usa 'vencimento' 
-- mas páginas usam 'data_vencimento'. Esta migration cria 'data_vencimento'
-- como oficial (baseado no uso em páginas e criação). API precisa ser 
-- corrigida em fase futura.
-- =====================================================

-- Tabela de Faturas (Receitas)
CREATE TABLE IF NOT EXISTS financeiro_faturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    matricula_id INT DEFAULT NULL,
    
    -- Dados da fatura
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT DEFAULT NULL,
    valor DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    valor_total DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    
    -- Vencimento e status
    data_vencimento DATE NOT NULL,
    vencimento DATE DEFAULT NULL,  -- Campo alternativo usado pela API (manter por compatibilidade)
    status ENUM('aberta', 'paga', 'vencida', 'parcial', 'cancelada') DEFAULT 'aberta',
    
    -- Pagamento
    forma_pagamento ENUM('avista', 'boleto', 'pix', 'cartao', 'transferencia', 'dinheiro') DEFAULT 'avista',
    parcelas INT DEFAULT 1,
    
    -- Observações e controle
    observacoes TEXT DEFAULT NULL,
    reteste BOOLEAN DEFAULT FALSE,  -- Flag para reteste (mencionado no plano)
    
    -- Auditoria
    criado_por INT DEFAULT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_aluno (aluno_id),
    INDEX idx_matricula (matricula_id),
    INDEX idx_status (status),
    INDEX idx_vencimento (data_vencimento),
    INDEX idx_status_vencimento (status, data_vencimento),
    
    -- Foreign Keys
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
    FOREIGN KEY (matricula_id) REFERENCES matriculas(id) ON DELETE SET NULL,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comentários
ALTER TABLE financeiro_faturas 
    MODIFY COLUMN data_vencimento DATE 
    COMMENT 'Campo oficial de vencimento (usado em páginas e criação)';
    
ALTER TABLE financeiro_faturas 
    MODIFY COLUMN vencimento DATE 
    COMMENT 'Campo alternativo (usado pela API - DEPRECATED, migrar para data_vencimento)';

