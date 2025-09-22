-- =====================================================
-- TABELAS FINANCEIRAS OTIMIZADAS - SISTEMA CFC MVP
-- Estrutura enxuta e profissional para produção
-- =====================================================

-- Tabela de Faturas (Receitas)
CREATE TABLE IF NOT EXISTS financeiro_faturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    matricula_id INT NULL,
    titulo VARCHAR(255) NOT NULL,
    valor_total DECIMAL(10,2) NOT NULL,
    status ENUM('aberta','paga','cancelada','vencida') DEFAULT 'aberta',
    vencimento DATE NOT NULL,
    forma_pagamento ENUM('avista','parcelado','pix','boleto','cartao','dinheiro','transferencia') DEFAULT 'avista',
    parcelas INT DEFAULT 1,
    observacoes TEXT NULL,
    asaas_charge_id VARCHAR(64) NULL,
    criado_por INT NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_faturas_aluno (aluno_id),
    INDEX idx_faturas_matricula (matricula_id),
    INDEX idx_faturas_status (status),
    INDEX idx_faturas_vencimento (vencimento),
    INDEX idx_faturas_criado (criado_em),
    
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
    FOREIGN KEY (matricula_id) REFERENCES matriculas(id) ON DELETE SET NULL,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Itens de Fatura (opcional - para parcelas detalhadas)
CREATE TABLE IF NOT EXISTS financeiro_faturas_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fatura_id INT NOT NULL,
    parcela_numero INT NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    vencimento DATE NOT NULL,
    status ENUM('aberta','paga','cancelada','vencida') DEFAULT 'aberta',
    data_pagamento DATE NULL,
    valor_pago DECIMAL(10,2) DEFAULT 0,
    observacoes TEXT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_itens_fatura (fatura_id),
    INDEX idx_itens_status (status),
    INDEX idx_itens_vencimento (vencimento),
    
    FOREIGN KEY (fatura_id) REFERENCES financeiro_faturas(id) ON DELETE CASCADE,
    UNIQUE KEY uk_fatura_parcela (fatura_id, parcela_numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Pagamentos (Despesas)
CREATE TABLE IF NOT EXISTS financeiro_pagamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fornecedor VARCHAR(255) NOT NULL,
    descricao VARCHAR(255) NULL,
    categoria ENUM('combustivel','manutencao','aluguel','taxas','salarios','material','outros') DEFAULT 'outros',
    valor DECIMAL(10,2) NOT NULL,
    status ENUM('paga','pendente','vencida') DEFAULT 'pendente',
    vencimento DATE NOT NULL,
    forma_pagamento ENUM('pix','boleto','cartao','dinheiro','transferencia') DEFAULT 'pix',
    data_pagamento DATE NULL,
    observacoes TEXT NULL,
    comprovante_url VARCHAR(255) NULL,
    criado_por INT NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_pagamentos_status (status),
    INDEX idx_pagamentos_categoria (categoria),
    INDEX idx_pagamentos_vencimento (vencimento),
    INDEX idx_pagamentos_criado (criado_em),
    
    FOREIGN KEY (criado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Configurações Financeiras
CREATE TABLE IF NOT EXISTS financeiro_configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT NOT NULL,
    descricao VARCHAR(255) NULL,
    tipo ENUM('string','number','boolean','json') DEFAULT 'string',
    atualizado_por INT NOT NULL,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (atualizado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir configurações padrão
INSERT INTO financeiro_configuracoes (chave, valor, descricao, tipo, atualizado_por) VALUES
('dias_inadimplencia', '30', 'Dias para considerar inadimplência', 'number', 1),
('asaas_enabled', 'false', 'Integração Asaas habilitada', 'boolean', 1),
('asaas_api_key', '', 'Chave da API Asaas', 'string', 1),
('asaas_webhook_token', '', 'Token do webhook Asaas', 'string', 1);

-- Atualizar tabela alunos com campo de inadimplência
ALTER TABLE alunos 
ADD COLUMN IF NOT EXISTS inadimplente TINYINT(1) DEFAULT 0 AFTER status_financeiro,
ADD COLUMN IF NOT EXISTS inadimplente_desde DATE NULL AFTER inadimplente;

-- Índice para performance
CREATE INDEX IF NOT EXISTS idx_alunos_inadimplente ON alunos(inadimplente);
