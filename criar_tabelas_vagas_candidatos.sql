-- =====================================================
-- SISTEMA DE VAGAS E CANDIDATOS - CFC BOM CONSELHO
-- =====================================================

-- Tabela de vagas disponíveis
CREATE TABLE IF NOT EXISTS vagas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT,
    requisitos TEXT,
    beneficios TEXT,
    salario VARCHAR(100),
    carga_horaria VARCHAR(100),
    turno VARCHAR(100),
    localizacao VARCHAR(200) DEFAULT 'Bom Conselho - PE',
    status ENUM('ativa', 'inativa', 'pausada') DEFAULT 'ativa',
    data_publicacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_encerramento DATETIME,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de candidatos
CREATE TABLE IF NOT EXISTS candidatos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_completo VARCHAR(200) NOT NULL,
    email VARCHAR(200) NOT NULL,
    whatsapp VARCHAR(20),
    telefone VARCHAR(20),
    categoria_cnh VARCHAR(10),
    escolaridade VARCHAR(100),
    endereco_rua VARCHAR(300),
    cidade VARCHAR(100),
    estado VARCHAR(50),
    cep VARCHAR(10),
    pais VARCHAR(50) DEFAULT 'Brasil',
    indicacoes TEXT,
    mensagem TEXT,
    curriculo_arquivo VARCHAR(500),
    foto_arquivo VARCHAR(500),
    vaga_id INT,
    status ENUM('novo', 'em_analise', 'aprovado', 'rejeitado', 'contratado') DEFAULT 'novo',
    observacoes TEXT,
    data_candidatura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vaga_id) REFERENCES vagas(id) ON DELETE SET NULL
);

-- Inserir vagas padrão baseadas nas funções mencionadas
INSERT INTO vagas (titulo, descricao, requisitos, beneficios, salario, carga_horaria, turno, status) VALUES
('Serviços Gerais', 'Responsável por serviços gerais e manutenção do CFC', 'Ensino fundamental completo, experiência em serviços gerais', 'Vale refeição, plano de saúde', 'A combinar', '4 horas', 'Manhã (8h às 12h)', 'ativa'),
('Técnico em Informática (TI)', 'Responsável pela manutenção e suporte técnico dos sistemas', 'Curso técnico em informática, experiência com sistemas', 'Vale refeição, plano de saúde, vale transporte', 'A combinar', '8 horas', 'Comercial (8h às 22h)', 'ativa'),
('Diretor de Ensino', 'Responsável pela coordenação pedagógica e supervisão do ensino', 'Formação superior, experiência em educação, registro no DETRAN', 'Vale refeição, plano de saúde, vale transporte', 'A combinar', '6 horas', 'Tarde (12h às 18h)', 'ativa'),
('Diretor Geral', 'Responsável pela administração geral do CFC', 'Formação superior em administração ou área afim, experiência gerencial', 'Vale refeição, plano de saúde, vale transporte', 'A combinar', '4 horas', 'Manhã (8h às 12h)', 'ativa'),
('Instrutor Teórico', 'Responsável pelas aulas teóricas de legislação de trânsito', 'Formação superior, credenciamento DETRAN, experiência em ensino', 'Vale refeição, plano de saúde, vale transporte', 'A combinar', '4 horas', 'Noite (18h às 22h)', 'ativa'),
('Instrutor Prático', 'Responsável pelas aulas práticas de direção', 'CNH categoria B, credenciamento DETRAN, experiência em ensino', 'Vale refeição, plano de saúde, vale transporte', 'A combinar', '8 horas', 'Manhã (6h às 14h) ou Tarde (14h às 22h)', 'ativa'),
('Recepcionista', 'Responsável pelo atendimento ao público e agendamentos', 'Ensino médio completo, experiência em atendimento', 'Vale refeição, plano de saúde, vale transporte', 'A combinar', '8 horas', 'Manhã (8h às 17h) ou Tarde (17h às 22h)', 'ativa'),
('Administrativo', 'Responsável por atividades administrativas do CFC', 'Ensino médio completo, experiência administrativa', 'Vale refeição, plano de saúde, vale transporte', 'A combinar', '8 horas', 'Comercial (9h às 18h)', 'ativa');

-- Criar índices para melhor performance
CREATE INDEX idx_candidatos_vaga_id ON candidatos(vaga_id);
CREATE INDEX idx_candidatos_status ON candidatos(status);
CREATE INDEX idx_candidatos_data_candidatura ON candidatos(data_candidatura);
CREATE INDEX idx_vagas_status ON vagas(status);
CREATE INDEX idx_vagas_data_publicacao ON vagas(data_publicacao);
