-- =====================================================
-- Migration: Tabela password_resets
-- Sistema: CFC Bom Conselho
-- Data: 2025-01-XX
-- Descrição: Cria tabela para tokens de recuperação de senha
-- =====================================================

-- Criar tabela password_resets
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(100) NOT NULL COMMENT 'Email ou CPF do usuário que solicitou recuperação',
    token_hash VARCHAR(64) NOT NULL COMMENT 'Hash SHA256 do token (não armazenar token em texto puro)',
    type ENUM('admin', 'secretaria', 'instrutor', 'aluno') NOT NULL COMMENT 'Tipo do usuário (apenas para auditoria/UI, não para permissão)',
    ip VARCHAR(45) NOT NULL COMMENT 'IP de onde foi solicitado o reset',
    expires_at TIMESTAMP NOT NULL COMMENT 'Data/hora de expiração do token (30 minutos após criação)',
    used_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Data/hora em que o token foi usado (NULL = não usado)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Data/hora de criação do token',
    
    -- Índices para performance e queries
    INDEX idx_token_hash (token_hash),
    INDEX idx_login (login),
    INDEX idx_expires_at (expires_at),
    INDEX idx_login_type (login, type),
    
    -- Índice composto para rate limiting
    INDEX idx_login_ip_created (login, ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tabela para armazenar tokens de recuperação de senha';

-- Notas:
-- 1. token_hash: sempre armazenar SHA256 do token, nunca o token em texto puro
-- 2. expires_at: tokens expiram em 30 minutos
-- 3. used_at: quando preenchido, token não pode ser reutilizado
-- 4. Rate limiting: verificar última solicitação por login+ip nos últimos 5 minutos
