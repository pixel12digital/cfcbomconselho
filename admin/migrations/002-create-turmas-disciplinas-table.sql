-- =====================================================
-- MIGRAÇÃO: Criar tabela para disciplinas selecionadas pelo usuário
-- =====================================================

-- Tabela para armazenar disciplinas selecionadas pelo usuário na etapa 1
CREATE TABLE IF NOT EXISTS turmas_disciplinas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    turma_id INT NOT NULL,
    disciplina_id INT NOT NULL,
    nome_disciplina VARCHAR(100) NOT NULL,
    carga_horaria_padrao INT NOT NULL DEFAULT 10,
    cor_hex VARCHAR(7) DEFAULT '#007bff',
    ordem INT NOT NULL DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (turma_id) REFERENCES turmas_teoricas(id) ON DELETE CASCADE,
    FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id) ON DELETE CASCADE,
    UNIQUE KEY unique_turma_disciplina (turma_id, disciplina_id),
    INDEX idx_turma_ordem (turma_id, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar comentários
ALTER TABLE turmas_disciplinas 
COMMENT = 'Disciplinas selecionadas pelo usuário para cada turma específica';

-- Verificar se a tabela foi criada
SELECT 'Tabela turmas_disciplinas criada com sucesso!' as status;
