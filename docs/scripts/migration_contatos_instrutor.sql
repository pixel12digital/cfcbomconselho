-- =====================================================
-- MIGRAÇÃO: Tabela de Contatos/Tickets do Instrutor
-- FASE 2 - Implementação: 2024
-- Arquivo: docs/scripts/migration_contatos_instrutor.sql
-- =====================================================

-- Verificar se a tabela já existe antes de criar
-- Se já existir, apenas adicionar colunas faltantes se necessário

CREATE TABLE IF NOT EXISTS contatos_instrutor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instrutor_id INT NOT NULL COMMENT 'ID do instrutor (tabela instrutores)',
    usuario_id INT NOT NULL COMMENT 'ID do usuário que enviou (tabela usuarios)',
    assunto VARCHAR(255) NOT NULL COMMENT 'Assunto da mensagem',
    mensagem TEXT NOT NULL COMMENT 'Conteúdo da mensagem',
    aula_id INT NULL COMMENT 'ID da aula relacionada (opcional)',
    status ENUM('aberto', 'em_atendimento', 'respondido', 'fechado') DEFAULT 'aberto',
    resposta TEXT NULL COMMENT 'Resposta da secretaria/admin',
    respondido_por INT NULL COMMENT 'ID do usuário que respondeu',
    respondido_em DATETIME NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_instrutor (instrutor_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_aula (aula_id),
    INDEX idx_status (status),
    INDEX idx_criado (criado_em),
    
    -- Foreign Keys
    FOREIGN KEY (instrutor_id) REFERENCES instrutores(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (aula_id) REFERENCES aulas(id) ON DELETE SET NULL,
    FOREIGN KEY (respondido_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Mensagens de contato enviadas por instrutores para a secretaria';

-- Verificar se a tabela foi criada com sucesso
SELECT 'Tabela contatos_instrutor criada com sucesso!' AS Status;

