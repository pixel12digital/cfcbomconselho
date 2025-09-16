-- =====================================================
-- ATUALIZAÇÃO DO SISTEMA DE USUÁRIOS - SISTEMA CFC
-- =====================================================

-- Atualizar enum para incluir tipo 'aluno'
ALTER TABLE usuarios MODIFY tipo ENUM(
    'admin',           -- Administrador
    'secretaria',       -- Atendente CFC  
    'instrutor',        -- Instrutor
    'aluno'             -- Aluno
) NOT NULL DEFAULT 'secretaria';

-- Adicionar campo senha na tabela alunos (se não existir)
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS senha VARCHAR(255) DEFAULT NULL;

-- Atualizar alunos existentes com senha padrão (123456)
UPDATE alunos SET senha = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE senha IS NULL;

-- Criar índices para melhor performance
CREATE INDEX IF NOT EXISTS idx_alunos_cpf ON alunos(cpf);
CREATE INDEX IF NOT EXISTS idx_alunos_ativo ON alunos(ativo);
CREATE INDEX IF NOT EXISTS idx_aulas_aluno_id ON aulas(aluno_id);
CREATE INDEX IF NOT EXISTS idx_aulas_data ON aulas(data_aula);

-- Comentários para documentação
ALTER TABLE usuarios COMMENT = 'Tabela de usuários do sistema - admin: acesso total, secretaria: tudo menos configurações, instrutor: pode editar/cancelar aulas mas não adicionar, aluno: apenas visualização';
ALTER TABLE alunos COMMENT = 'Tabela de alunos - inclui campo senha para acesso ao sistema';
