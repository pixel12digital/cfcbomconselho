-- =====================================================
-- MIGRAÇÃO: Tabela de Contatos/Tickets do Aluno
-- FASE 4 - CONTATO ALUNO - Implementação: 2025
-- Arquivo: docs/scripts/migration_contatos_aluno.sql
-- =====================================================

-- Verificar se a tabela já existe antes de criar
-- Se já existir, apenas adicionar colunas faltantes se necessário

CREATE TABLE IF NOT EXISTS contatos_aluno (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL COMMENT 'ID do aluno (tabela alunos)',
    usuario_id INT NOT NULL COMMENT 'ID do usuário que enviou (tabela usuarios)',
    tipo_assunto VARCHAR(100) NULL COMMENT 'Tipo de assunto (ex: Dúvida sobre aulas, Financeiro, Documentação, Exames, Outro)',
    assunto VARCHAR(255) NOT NULL COMMENT 'Assunto da mensagem',
    mensagem TEXT NOT NULL COMMENT 'Conteúdo da mensagem',
    aula_id INT NULL COMMENT 'ID da aula prática relacionada (opcional)',
    turma_id INT NULL COMMENT 'ID da turma teórica relacionada (opcional)',
    status ENUM('aberto', 'em_atendimento', 'respondido', 'fechado') DEFAULT 'aberto',
    resposta TEXT NULL COMMENT 'Resposta da secretaria/admin',
    respondido_por INT NULL COMMENT 'ID do usuário que respondeu',
    respondido_em DATETIME NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_aluno (aluno_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_aula (aula_id),
    INDEX idx_turma (turma_id),
    INDEX idx_status (status),
    INDEX idx_criado (criado_em),
    
    -- Foreign Keys
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (aula_id) REFERENCES aulas(id) ON DELETE SET NULL,
    FOREIGN KEY (turma_id) REFERENCES turmas_teoricas(id) ON DELETE SET NULL,
    FOREIGN KEY (respondido_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Mensagens de contato enviadas por alunos para a secretaria';

-- Verificar se a tabela foi criada com sucesso
SELECT 'Tabela contatos_aluno criada com sucesso!' AS Status;

