-- =====================================================
-- Migration: Tabela smtp_settings
-- Sistema: CFC Bom Conselho
-- Data: 2025-01-XX
-- Descrição: Cria tabela para armazenar configurações SMTP do painel admin
-- =====================================================

-- Criar tabela smtp_settings
CREATE TABLE IF NOT EXISTS smtp_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    host VARCHAR(255) NOT NULL COMMENT 'Host SMTP (ex: smtp.hostinger.com)',
    port INT NOT NULL DEFAULT 587 COMMENT 'Porta SMTP (587 para TLS, 465 para SSL)',
    user VARCHAR(255) NOT NULL COMMENT 'Usuário/e-mail SMTP',
    pass_encrypted TEXT NOT NULL COMMENT 'Senha SMTP criptografada',
    encryption_mode ENUM('tls', 'ssl', 'none') DEFAULT 'tls' COMMENT 'Modo de criptografia',
    from_name VARCHAR(255) NULL COMMENT 'Nome do remetente (opcional)',
    from_email VARCHAR(255) NULL COMMENT 'E-mail "from" (opcional, se diferente do user)',
    enabled BOOLEAN DEFAULT TRUE COMMENT 'Se configuração está ativa',
    last_test_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Data/hora do último teste',
    last_test_status ENUM('ok', 'error') NULL COMMENT 'Status do último teste',
    last_test_message VARCHAR(500) NULL COMMENT 'Mensagem do último teste',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data/hora da última atualização',
    updated_by INT NULL COMMENT 'ID do usuário que atualizou',
    
    -- Índices
    INDEX idx_enabled (enabled),
    INDEX idx_updated_at (updated_at),
    
    -- Foreign key (opcional, para auditoria)
    FOREIGN KEY (updated_by) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Configurações SMTP configuráveis pelo painel admin';

-- Notas:
-- 1. A tabela terá apenas UMA linha (singleton)
-- 2. pass_encrypted: senha será criptografada antes de salvar
-- 3. Se enabled=FALSE, o sistema usa fallback para config.php
-- 4. last_test_*: usado para exibir status no painel
