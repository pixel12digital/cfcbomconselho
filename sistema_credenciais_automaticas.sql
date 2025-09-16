-- =====================================================
-- SISTEMA DE CREDENCIAIS AUTOMÁTICAS - SISTEMA CFC
-- =====================================================

-- Adicionar campos para controle de primeiro acesso
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS primeiro_acesso BOOLEAN DEFAULT TRUE;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS senha_temporaria BOOLEAN DEFAULT TRUE;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS data_ultima_alteracao_senha TIMESTAMP NULL;

-- Atualizar enum para incluir tipo 'aluno'
ALTER TABLE usuarios MODIFY tipo ENUM(
    'admin',           -- Administrador
    'secretaria',       -- Atendente CFC  
    'instrutor',        -- Instrutor
    'aluno'             -- Aluno
) NOT NULL DEFAULT 'secretaria';

-- Adicionar campo senha na tabela alunos (se não existir)
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS senha VARCHAR(255) DEFAULT NULL;
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS usuario_id INT DEFAULT NULL;

-- Criar índices para melhor performance
CREATE INDEX IF NOT EXISTS idx_usuarios_primeiro_acesso ON usuarios(primeiro_acesso);
CREATE INDEX IF NOT EXISTS idx_usuarios_senha_temporaria ON usuarios(senha_temporaria);
CREATE INDEX IF NOT EXISTS idx_alunos_usuario_id ON alunos(usuario_id);
CREATE INDEX IF NOT EXISTS idx_alunos_cpf ON alunos(cpf);

-- Atualizar alunos existentes com senha padrão se necessário
UPDATE alunos SET senha = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE senha IS NULL;

-- Comentários para documentação
ALTER TABLE usuarios COMMENT = 'Tabela de usuários - inclui controle de primeiro acesso e senhas temporárias';
ALTER TABLE alunos COMMENT = 'Tabela de alunos - inclui campo senha e vinculação com usuarios';

-- =====================================================
-- PROCEDIMENTO PARA CRIAR CREDENCIAIS AUTOMÁTICAS
-- =====================================================

DELIMITER //

CREATE PROCEDURE IF NOT EXISTS CriarCredenciaisFuncionario(
    IN p_nome VARCHAR(100),
    IN p_email VARCHAR(100),
    IN p_tipo ENUM('admin', 'secretaria', 'instrutor'),
    OUT p_usuario_id INT,
    OUT p_senha_temporaria VARCHAR(8)
)
BEGIN
    DECLARE v_senha VARCHAR(8);
    DECLARE v_senha_hash VARCHAR(255);
    
    -- Gerar senha temporária (8 caracteres alfanuméricos)
    SET v_senha = CONCAT(
        CHAR(65 + FLOOR(RAND() * 26)),  -- Letra maiúscula
        CHAR(97 + FLOOR(RAND() * 26)),  -- Letra minúscula
        CHAR(48 + FLOOR(RAND() * 10)),  -- Número
        CHAR(65 + FLOOR(RAND() * 26)),  -- Letra maiúscula
        CHAR(97 + FLOOR(RAND() * 26)),  -- Letra minúscula
        CHAR(48 + FLOOR(RAND() * 10)),  -- Número
        CHAR(65 + FLOOR(RAND() * 26)),  -- Letra maiúscula
        CHAR(97 + FLOOR(RAND() * 26))   -- Letra minúscula
    );
    
    -- Hash da senha (simulado - em PHP seria password_hash)
    SET v_senha_hash = CONCAT('$2y$10$', SHA2(CONCAT(v_senha, 'salt'), 256));
    
    -- Inserir usuário
    INSERT INTO usuarios (nome, email, senha, tipo, ativo, primeiro_acesso, senha_temporaria, criado_em)
    VALUES (p_nome, p_email, v_senha_hash, p_tipo, TRUE, TRUE, TRUE, NOW());
    
    SET p_usuario_id = LAST_INSERT_ID();
    SET p_senha_temporaria = v_senha;
END //

DELIMITER ;

-- =====================================================
-- PROCEDIMENTO PARA CRIAR CREDENCIAIS DE ALUNO
-- =====================================================

DELIMITER //

CREATE PROCEDURE IF NOT EXISTS CriarCredenciaisAluno(
    IN p_aluno_id INT,
    IN p_nome VARCHAR(100),
    IN p_cpf VARCHAR(14),
    IN p_email VARCHAR(100),
    OUT p_usuario_id INT,
    OUT p_senha_temporaria VARCHAR(8)
)
BEGIN
    DECLARE v_senha VARCHAR(8);
    DECLARE v_senha_hash VARCHAR(255);
    DECLARE v_email_final VARCHAR(100);
    
    -- Usar email fornecido ou gerar baseado no CPF
    IF p_email IS NULL OR p_email = '' THEN
        SET v_email_final = CONCAT(p_cpf, '@aluno.cfc');
    ELSE
        SET v_email_final = p_email;
    END IF;
    
    -- Gerar senha temporária
    SET v_senha = CONCAT(
        CHAR(65 + FLOOR(RAND() * 26)),
        CHAR(97 + FLOOR(RAND() * 26)),
        CHAR(48 + FLOOR(RAND() * 10)),
        CHAR(65 + FLOOR(RAND() * 26)),
        CHAR(97 + FLOOR(RAND() * 26)),
        CHAR(48 + FLOOR(RAND() * 10)),
        CHAR(65 + FLOOR(RAND() * 26)),
        CHAR(97 + FLOOR(RAND() * 26))
    );
    
    -- Hash da senha
    SET v_senha_hash = CONCAT('$2y$10$', SHA2(CONCAT(v_senha, 'salt'), 256));
    
    -- Inserir usuário
    INSERT INTO usuarios (nome, email, senha, tipo, ativo, primeiro_acesso, senha_temporaria, criado_em)
    VALUES (p_nome, v_email_final, v_senha_hash, 'aluno', TRUE, TRUE, TRUE, NOW());
    
    SET p_usuario_id = LAST_INSERT_ID();
    
    -- Atualizar aluno com usuario_id e senha
    UPDATE alunos 
    SET usuario_id = p_usuario_id, senha = v_senha_hash
    WHERE id = p_aluno_id;
    
    SET p_senha_temporaria = v_senha;
END //

DELIMITER ;
