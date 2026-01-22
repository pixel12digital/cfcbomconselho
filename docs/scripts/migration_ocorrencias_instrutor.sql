-- =====================================================
-- MIGRAÇÃO: Tabela de Ocorrências do Instrutor
-- FASE 2 - Implementação: 2024
-- Arquivo: docs/scripts/migration_ocorrencias_instrutor.sql
-- =====================================================

-- Verificar se a tabela já existe antes de criar
-- Se já existir, apenas adicionar colunas faltantes se necessário

CREATE TABLE IF NOT EXISTS ocorrencias_instrutor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instrutor_id INT NOT NULL COMMENT 'ID do instrutor (tabela instrutores)',
    usuario_id INT NOT NULL COMMENT 'ID do usuário que registrou (tabela usuarios)',
    tipo ENUM(
        'atraso_aluno',
        'problema_veiculo',
        'infraestrutura',
        'comportamento_aluno',
        'outro'
    ) NOT NULL DEFAULT 'outro',
    data_ocorrencia DATE NOT NULL COMMENT 'Data em que a ocorrência aconteceu',
    aula_id INT NULL COMMENT 'ID da aula relacionada (opcional)',
    descricao TEXT NOT NULL COMMENT 'Descrição detalhada da ocorrência',
    status ENUM('aberta', 'em_analise', 'resolvida', 'arquivada') DEFAULT 'aberta',
    resolucao TEXT NULL COMMENT 'Resolução da ocorrência (preenchido pela secretaria/admin)',
    resolvido_por INT NULL COMMENT 'ID do usuário que resolveu',
    resolvido_em DATETIME NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_instrutor (instrutor_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_aula (aula_id),
    INDEX idx_data (data_ocorrencia),
    INDEX idx_status (status),
    INDEX idx_tipo (tipo),
    
    -- Foreign Keys
    FOREIGN KEY (instrutor_id) REFERENCES instrutores(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (aula_id) REFERENCES aulas(id) ON DELETE SET NULL,
    FOREIGN KEY (resolvido_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registro de ocorrências reportadas por instrutores';

-- Verificar se a tabela foi criada com sucesso
SELECT 'Tabela ocorrencias_instrutor criada com sucesso!' AS Status;

