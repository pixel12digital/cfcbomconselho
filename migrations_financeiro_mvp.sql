-- =====================================================
-- MIGRATIONS FINANCEIRO MVP - SISTEMA CFC
-- =====================================================

-- 1.1 Ajustes mínimos em matriculas
ALTER TABLE matriculas
  ADD COLUMN IF NOT EXISTS valor_total DECIMAL(10,2) DEFAULT 0 AFTER aluno_id,
  ADD COLUMN IF NOT EXISTS forma_pagamento ENUM('avista','parcelado') DEFAULT 'avista' AFTER valor_total,
  ADD COLUMN IF NOT EXISTS status_financeiro ENUM('regular','inadimplente') DEFAULT 'regular' AFTER forma_pagamento;

-- 1.2 Contas a Receber — faturas
CREATE TABLE IF NOT EXISTS faturas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  matricula_id INT NOT NULL,
  aluno_id INT NOT NULL,
  numero VARCHAR(30) UNIQUE,
  descricao VARCHAR(255),
  valor DECIMAL(10,2) NOT NULL,
  desconto DECIMAL(10,2) DEFAULT 0,
  acrescimo DECIMAL(10,2) DEFAULT 0,
  valor_liquido DECIMAL(10,2) NOT NULL DEFAULT 0,
  vencimento DATE NOT NULL,
  status ENUM('aberta','paga','cancelada','vencida','parcial') DEFAULT 'aberta',
  meio ENUM('pix','boleto','cartao','dinheiro','transferencia','outro') DEFAULT 'pix',
  asaas_charge_id VARCHAR(64) NULL,
  criado_por INT NOT NULL,
  criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_faturas_matricula (matricula_id),
  INDEX idx_faturas_aluno (aluno_id),
  INDEX idx_faturas_status (status),
  INDEX idx_faturas_vencimento (vencimento),
  FOREIGN KEY (matricula_id) REFERENCES matriculas(id),
  FOREIGN KEY (aluno_id) REFERENCES alunos(id)
);

-- 1.3 Baixas — pagamentos
CREATE TABLE IF NOT EXISTS pagamentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fatura_id INT NOT NULL,
  data_pagamento DATE NOT NULL,
  valor_pago DECIMAL(10,2) NOT NULL,
  metodo ENUM('pix','boleto','cartao','dinheiro','transferencia','outro') DEFAULT 'pix',
  comprovante_url VARCHAR(255) NULL,
  obs VARCHAR(255) NULL,
  asaas_payment_id VARCHAR(64) NULL,
  criado_por INT NOT NULL,
  criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_pag_fatura (fatura_id),
  FOREIGN KEY (fatura_id) REFERENCES faturas(id)
);

-- 1.4 Contas a Pagar — despesas
CREATE TABLE IF NOT EXISTS despesas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(120) NOT NULL,
  fornecedor VARCHAR(120) NULL,
  categoria ENUM('combustivel','manutencao','aluguel','taxas','salarios','outros') DEFAULT 'outros',
  valor DECIMAL(10,2) NOT NULL,
  vencimento DATE NOT NULL,
  pago TINYINT(1) DEFAULT 0,
  data_pagamento DATE NULL,
  metodo ENUM('pix','boleto','cartao','dinheiro','transferencia','outro') DEFAULT 'pix',
  anexo_url VARCHAR(255) NULL,
  obs VARCHAR(255) NULL,
  criado_por INT NOT NULL,
  criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_despesas_pago (pago),
  INDEX idx_despesas_venc (vencimento)
);

-- 1.5 Consentimento LGPD (mínimo)
ALTER TABLE alunos
  ADD COLUMN IF NOT EXISTS lgpd_consentido TINYINT(1) DEFAULT 0 AFTER observacoes,
  ADD COLUMN IF NOT EXISTS lgpd_consentido_em DATETIME NULL AFTER lgpd_consentido;

