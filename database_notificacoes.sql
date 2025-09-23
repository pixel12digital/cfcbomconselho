-- =====================================================
-- TABELA DE NOTIFICAÇÕES - SISTEMA CFC
-- Central de avisos para usuários
-- =====================================================

CREATE TABLE IF NOT EXISTS notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo_usuario ENUM('admin', 'secretaria', 'instrutor', 'aluno') NOT NULL,
    tipo_notificacao VARCHAR(50) NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    mensagem TEXT NOT NULL,
    dados JSON,
    lida BOOLEAN DEFAULT FALSE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    lida_em TIMESTAMP NULL,
    
    INDEX idx_usuario_tipo (usuario_id, tipo_usuario),
    INDEX idx_nao_lidas (usuario_id, tipo_usuario, lida),
    INDEX idx_criado_em (criado_em)
);

-- =====================================================
-- TABELA DE SOLICITAÇÕES DE ALUNOS
-- Para reagendamento e cancelamento
-- =====================================================

CREATE TABLE IF NOT EXISTS solicitacoes_aluno (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    aula_id INT NOT NULL,
    tipo_solicitacao ENUM('reagendamento', 'cancelamento') NOT NULL,
    data_aula_original DATE NOT NULL,
    hora_inicio_original TIME NOT NULL,
    nova_data DATE NULL,
    nova_hora TIME NULL,
    motivo VARCHAR(100) NULL,
    justificativa TEXT NOT NULL,
    status ENUM('pendente', 'aprovado', 'negado') DEFAULT 'pendente',
    aprovado_por INT NULL,
    motivo_decisao TEXT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processado_em TIMESTAMP NULL,
    
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
    FOREIGN KEY (aula_id) REFERENCES aulas(id) ON DELETE CASCADE,
    FOREIGN KEY (aprovado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    
    INDEX idx_aluno_status (aluno_id, status),
    INDEX idx_aula (aula_id),
    INDEX idx_status (status),
    INDEX idx_criado_em (criado_em)
);

-- =====================================================
-- ATUALIZAR TABELA DE LOGS PARA SUPORTAR NOTIFICAÇÕES
-- =====================================================

-- Adicionar coluna para tipo de ação de notificação se não existir
ALTER TABLE logs 
ADD COLUMN IF NOT EXISTS tipo_acao VARCHAR(50) DEFAULT NULL AFTER acao;

-- =====================================================
-- INSERIR DADOS DE EXEMPLO (OPCIONAL)
-- =====================================================

-- Exemplo de notificação para teste
INSERT INTO notificacoes (usuario_id, tipo_usuario, tipo_notificacao, titulo, mensagem, dados) 
VALUES (1, 'admin', 'sistema_iniciado', 'Sistema de Notificações Ativado', 'O sistema de notificações foi ativado com sucesso.', '{"sistema": "notificacoes", "status": "ativo"}')
ON DUPLICATE KEY UPDATE titulo = VALUES(titulo);

-- =====================================================
-- COMENTÁRIOS DAS TABELAS
-- =====================================================

-- Tabela notificacoes: Central de avisos para todos os usuários
-- - usuario_id: ID do usuário que receberá a notificação
-- - tipo_usuario: Tipo do usuário (admin, secretaria, instrutor, aluno)
-- - tipo_notificacao: Tipo da notificação (agendamento_criado, etc.)
-- - titulo: Título da notificação
-- - mensagem: Mensagem da notificação
-- - dados: Dados adicionais em JSON
-- - lida: Se a notificação foi lida
-- - criado_em: Quando foi criada
-- - lida_em: Quando foi lida

-- Tabela solicitacoes_aluno: Solicitações de reagendamento e cancelamento
-- - aluno_id: ID do aluno que fez a solicitação
-- - aula_id: ID da aula relacionada
-- - tipo_solicitacao: Tipo da solicitação (reagendamento ou cancelamento)
-- - data_aula_original: Data original da aula
-- - hora_inicio_original: Hora original da aula
-- - nova_data: Nova data (para reagendamento)
-- - nova_hora: Nova hora (para reagendamento)
-- - motivo: Motivo da solicitação
-- - justificativa: Justificativa detalhada
-- - status: Status da solicitação (pendente, aprovado, negado)
-- - aprovado_por: Quem aprovou/negou
-- - motivo_decisao: Motivo da decisão
-- - criado_em: Quando foi criada
-- - processado_em: Quando foi processada
