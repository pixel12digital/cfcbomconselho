-- =====================================================
-- MIGRAÇÃO: Estrutura da Tabela Pagamentos
-- Versão: 1.0
-- Data: 2025-01-27 (Fase 1)
-- Autor: Sistema CFC Bom Conselho
-- 
-- NOTA: Esta migration foi criada na Fase 1 para alinhar
-- o install.php com a estrutura real usada pelo sistema.
-- A tabela já existia em produção, mas não estava no install.php.
-- Baseada em: admin/api/pagamentos.php
-- 
-- IMPORTANTE: A API relaciona com tabela 'faturas' antiga.
-- Esta migration mantém essa relação para não quebrar funcionalidade.
-- Migração para 'financeiro_faturas' deve ser feita em fase futura.
-- =====================================================

-- Tabela de Pagamentos (Baixas de Faturas)
CREATE TABLE IF NOT EXISTS pagamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fatura_id INT NOT NULL,  -- Relaciona com 'faturas' antiga (corrigir em fase futura para financeiro_faturas)
    
    -- Dados do pagamento
    data_pagamento DATE NOT NULL,
    valor_pago DECIMAL(10, 2) NOT NULL,
    metodo ENUM('pix', 'boleto', 'cartao', 'dinheiro', 'transferencia', 'outros') DEFAULT 'pix',
    
    -- Comprovante e observações
    comprovante_url VARCHAR(500) DEFAULT NULL,
    obs TEXT DEFAULT NULL,
    
    -- Auditoria
    criado_por INT DEFAULT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_fatura (fatura_id),
    INDEX idx_data_pagamento (data_pagamento),
    
    -- Foreign Keys
    -- NOTA: Relação com 'faturas' antiga mantida por compatibilidade
    -- FOREIGN KEY (fatura_id) REFERENCES faturas(id) ON DELETE CASCADE,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

