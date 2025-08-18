-- =====================================================
-- SISTEMA CFC - ESTRUTURA DO BANCO DE DADOS
-- =====================================================

-- Criação do banco de dados
CREATE DATABASE IF NOT EXISTS cfc_sistema CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cfc_sistema;

-- Tabela de usuários
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('admin', 'instrutor', 'secretaria') NOT NULL DEFAULT 'secretaria',
    cpf VARCHAR(14) UNIQUE,
    telefone VARCHAR(20),
    ativo BOOLEAN DEFAULT TRUE,
    ultimo_login DATETIME,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de CFCs
CREATE TABLE cfcs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,
    cnpj VARCHAR(18) UNIQUE NOT NULL,
    endereco TEXT,
    telefone VARCHAR(20),
    email VARCHAR(100),
    responsavel_id INT,
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (responsavel_id) REFERENCES usuarios(id)
);

-- Tabela de alunos
CREATE TABLE alunos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) UNIQUE NOT NULL,
    rg VARCHAR(20),
    data_nascimento DATE,
    endereco TEXT,
    telefone VARCHAR(20),
    email VARCHAR(100),
    cfc_id INT NOT NULL,
    categoria_cnh ENUM('A', 'B', 'C', 'D', 'E', 'AB', 'AC', 'AD', 'AE') NOT NULL,
    status ENUM('ativo', 'inativo', 'concluido') DEFAULT 'ativo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cfc_id) REFERENCES cfcs(id)
);

-- Tabela de instrutores
CREATE TABLE instrutores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    cfc_id INT NOT NULL,
    credencial VARCHAR(50) UNIQUE NOT NULL,
    categoria_habilitacao VARCHAR(100),
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (cfc_id) REFERENCES cfcs(id)
);

-- Tabela de aulas
CREATE TABLE aulas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    instrutor_id INT NOT NULL,
    cfc_id INT NOT NULL,
    tipo_aula ENUM('teorica', 'pratica') NOT NULL,
    data_aula DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    status ENUM('agendada', 'em_andamento', 'concluida', 'cancelada') DEFAULT 'agendada',
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id) REFERENCES alunos(id),
    FOREIGN KEY (instrutor_id) REFERENCES instrutores(id),
    FOREIGN KEY (cfc_id) REFERENCES cfcs(id)
);

-- Tabela de veículos
CREATE TABLE veiculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cfc_id INT NOT NULL,
    placa VARCHAR(10) UNIQUE NOT NULL,
    modelo VARCHAR(100),
    marca VARCHAR(100),
    ano INT,
    categoria_cnh VARCHAR(10),
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cfc_id) REFERENCES cfcs(id)
);

-- Tabela de sessões
CREATE TABLE sessoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expira_em TIMESTAMP NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Tabela de logs
CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    acao VARCHAR(100) NOT NULL,
    tabela_afetada VARCHAR(50),
    registro_id INT,
    dados_anteriores TEXT,
    dados_novos TEXT,
    ip_address VARCHAR(45),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Inserir usuário administrador padrão
INSERT INTO usuarios (nome, email, senha, tipo, cpf, telefone) VALUES 
('Administrador', 'admin@cfc.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '000.000.000-00', '(11) 99999-9999');

-- Inserir CFC padrão
INSERT INTO cfcs (nome, cnpj, endereco, telefone, email, responsavel_id) VALUES 
('CFC Exemplo', '00.000.000/0000-00', 'Rua Exemplo, 123 - Centro', '(11) 3333-3333', 'contato@cfcexemplo.com', 1);

-- Índices para performance
CREATE INDEX idx_usuarios_email ON usuarios(email);
CREATE INDEX idx_alunos_cpf ON alunos(cpf);
CREATE INDEX idx_alunos_cfc ON alunos(cfc_id);
CREATE INDEX idx_aulas_data ON aulas(data_aula);
CREATE INDEX idx_aulas_status ON aulas(status);
CREATE INDEX idx_sessoes_token ON sessoes(token);
CREATE INDEX idx_sessoes_expira ON sessoes(expira_em);
