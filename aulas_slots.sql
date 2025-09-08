-- Script SQL para criar tabela de slots de aulas
-- Esta tabela armazena os "espaços" disponíveis para cada aluno baseado na configuração

CREATE TABLE IF NOT EXISTS aulas_slots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    aluno_id INT NOT NULL,
    tipo_aula ENUM('teorica', 'pratica') NOT NULL,
    tipo_veiculo ENUM('moto', 'carro', 'carga', 'passageiros', 'combinacao') NULL,
    status ENUM('pendente', 'agendada', 'concluida', 'cancelada') DEFAULT 'pendente',
    ordem INT NOT NULL,
    configuracao_id INT NOT NULL,
    aula_id INT NULL, -- Referência para a aula real quando agendada
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
    FOREIGN KEY (configuracao_id) REFERENCES configuracoes_categorias(id),
    FOREIGN KEY (aula_id) REFERENCES aulas(id) ON DELETE SET NULL,
    
    INDEX idx_aluno_status (aluno_id, status),
    INDEX idx_tipo_aula (tipo_aula),
    INDEX idx_tipo_veiculo (tipo_veiculo),
    INDEX idx_ordem (ordem)
);

-- Adicionar campo na tabela de aulas para referenciar o slot
ALTER TABLE aulas ADD COLUMN IF NOT EXISTS slot_id INT DEFAULT NULL;
ALTER TABLE aulas ADD INDEX IF NOT EXISTS idx_slot_id (slot_id);
ALTER TABLE aulas ADD FOREIGN KEY IF NOT EXISTS (slot_id) REFERENCES aulas_slots(id) ON DELETE SET NULL;
