-- =====================================================
-- MIGRAÇÃO: Estrutura da Tabela Financeiro Pagamentos (Despesas)
-- Versão: 1.0
-- Data: 2025-01-27 (Fase 1)
-- Autor: Sistema CFC Bom Conselho
-- 
-- NOTA: Esta migration foi criada na Fase 1 para alinhar
-- o install.php com a estrutura real usada pelo sistema.
-- A tabela já existia em produção, mas não estava no install.php.
-- Baseada em: admin/api/financeiro-despesas.php
-- 
-- IMPORTANTE: A API usa 'financeiro_pagamentos' para despesas,
-- não 'financeiro_despesas'. Mantida nomenclatura conforme código.
-- =====================================================

-- Tabela de Despesas (Financeiro Pagamentos)
CREATE TABLE IF NOT EXISTS financeiro_pagamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Dados da despesa
    fornecedor VARCHAR(200) NOT NULL,
    descricao TEXT DEFAULT NULL,
    categoria ENUM('combustivel', 'manutencao', 'salarios', 'aluguel', 'energia', 'agua', 'telefone', 'internet', 'outros') DEFAULT 'outros',
    valor DECIMAL(10, 2) NOT NULL,
    
    -- Status e vencimento
    status ENUM('pendente', 'pago', 'cancelado') DEFAULT 'pendente',
    vencimento DATE NOT NULL,
    data_pagamento DATE DEFAULT NULL,
    
    -- Forma de pagamento
    forma_pagamento ENUM('pix', 'boleto', 'cartao', 'dinheiro', 'transferencia') DEFAULT 'pix',
    
    -- Comprovante e observações
    comprovante_url VARCHAR(500) DEFAULT NULL,
    observacoes TEXT DEFAULT NULL,
    
    -- Auditoria
    criado_por INT DEFAULT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_status (status),
    INDEX idx_vencimento (vencimento),
    INDEX idx_categoria (categoria),
    INDEX idx_status_vencimento (status, vencimento),
    
    -- Foreign Keys
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comentários
ALTER TABLE financeiro_pagamentos 
    MODIFY COLUMN categoria ENUM('combustivel', 'manutencao', 'salarios', 'aluguel', 'energia', 'agua', 'telefone', 'internet', 'outros')
    COMMENT 'Categoria da despesa';

