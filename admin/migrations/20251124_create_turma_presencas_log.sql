-- =====================================================
-- MIGRAÇÃO: Tabela de Log de Alterações de Presença Teórica
-- Versão: 1.0
-- Data: 2025-11-24
-- Autor: Sistema CFC Bom Conselho
-- FASE 1 - LOG PRESENCA TEORICA
-- =====================================================

-- Tabela para registrar histórico de alterações de presença teórica
CREATE TABLE IF NOT EXISTS turma_presencas_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    presenca_id INT DEFAULT NULL COMMENT 'ID da presença alterada (NULL se foi delete)',
    turma_id INT NOT NULL,
    aula_id INT NOT NULL,
    aluno_id INT NOT NULL,
    
    -- Valores antes da alteração
    presente_antes TINYINT(1) DEFAULT NULL,
    justificativa_antes TEXT DEFAULT NULL,
    
    -- Valores depois da alteração
    presente_depois TINYINT(1) DEFAULT NULL,
    justificativa_depois TEXT DEFAULT NULL,
    
    -- Metadados da alteração
    acao ENUM('create', 'update', 'delete') NOT NULL,
    alterado_por INT NOT NULL,
    alterado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Índices para consultas rápidas
    INDEX idx_presenca_id (presenca_id),
    INDEX idx_turma_aula (turma_id, aula_id),
    INDEX idx_aluno_id (aluno_id),
    INDEX idx_alterado_por (alterado_por),
    INDEX idx_alterado_em (alterado_em),
    INDEX idx_acao (acao),
    
    -- Foreign keys (com ON DELETE SET NULL para presenca_id, pois pode ser deletada)
    FOREIGN KEY (presenca_id) REFERENCES turma_presencas(id) ON DELETE SET NULL,
    FOREIGN KEY (turma_id) REFERENCES turmas_teoricas(id) ON DELETE CASCADE,
    FOREIGN KEY (aula_id) REFERENCES turma_aulas_agendadas(id) ON DELETE CASCADE,
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
    FOREIGN KEY (alterado_por) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Histórico de alterações de presença teórica para auditoria';

