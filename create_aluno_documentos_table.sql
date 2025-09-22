-- =====================================================
-- TABELA DE DOCUMENTOS DE ALUNOS - SISTEMA CFC
-- =====================================================

CREATE TABLE IF NOT EXISTS aluno_documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    tipo_documento ENUM('cpf', 'rg', 'cnh', 'comprovante_residencia', 'certificado_medico', 'foto_3x4', 'outros') NOT NULL,
    nome_arquivo VARCHAR(255) NOT NULL,
    caminho_arquivo VARCHAR(500) NOT NULL,
    tamanho_arquivo INT NOT NULL,
    tipo_mime VARCHAR(100) NOT NULL,
    descricao TEXT NULL,
    status ENUM('pendente', 'aprovado', 'rejeitado') DEFAULT 'pendente',
    observacoes TEXT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
    
    -- Índices para performance
    INDEX idx_aluno_tipo (aluno_id, tipo_documento),
    INDEX idx_status (status),
    INDEX idx_criado_em (criado_em)
);
