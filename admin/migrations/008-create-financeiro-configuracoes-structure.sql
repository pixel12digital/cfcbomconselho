-- =====================================================
-- MIGRAÇÃO: Estrutura da Tabela Financeiro Configurações
-- Versão: 1.0
-- Data: 2025-01-28 (Fase 2)
-- Autor: Sistema CFC Bom Conselho
-- 
-- NOTA: Tabela para armazenar configurações do módulo financeiro
-- Baseada em: admin/api/financeiro-faturas.php:336, admin/api/financeiro-relatorios.php:134
-- =====================================================

-- Tabela de Configurações Financeiras
CREATE TABLE IF NOT EXISTS financeiro_configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) NOT NULL UNIQUE,
    valor VARCHAR(255) NOT NULL,
    descricao VARCHAR(255) DEFAULT NULL,
    tipo ENUM('texto', 'numero', 'booleano', 'data') DEFAULT 'texto',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_chave (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir configuração padrão
INSERT IGNORE INTO financeiro_configuracoes (chave, valor, descricao, tipo) VALUES
('dias_inadimplencia', '30', 'Número de dias após vencimento para considerar inadimplente', 'numero');

-- Comentários
ALTER TABLE financeiro_configuracoes 
    MODIFY COLUMN chave VARCHAR(100) 
    COMMENT 'Chave única da configuração (ex: dias_inadimplencia)';
    
ALTER TABLE financeiro_configuracoes 
    MODIFY COLUMN valor VARCHAR(255) 
    COMMENT 'Valor da configuração (armazenado como string, converter conforme tipo)';

