<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

try {
    $db = Database::getInstance();
    
    echo "🚀 Criando tabela matriculas...\n";
    
    $db->query("
        CREATE TABLE IF NOT EXISTS matriculas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            aluno_id INT NOT NULL,
            categoria_cnh ENUM('A', 'B', 'C', 'D', 'E', 'AB', 'AC', 'AD', 'AE', 'BC', 'BD', 'BE', 'CD', 'CE', 'DE', 'ACC') NOT NULL,
            tipo_servico ENUM('primeira_habilitacao', 'adicao', 'mudanca', 'renovacao') NOT NULL,
            status ENUM('ativa', 'concluida', 'cancelada', 'suspensa') DEFAULT 'ativa',
            data_inicio DATE NOT NULL,
            data_fim DATE NULL,
            valor_total DECIMAL(10,2) DEFAULT NULL,
            forma_pagamento ENUM('a_vista', 'parcelado', 'financiado') DEFAULT NULL,
            observacoes TEXT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
            
            INDEX idx_aluno_categoria (aluno_id, categoria_cnh),
            INDEX idx_status (status),
            INDEX idx_data_inicio (data_inicio),
            
            UNIQUE KEY uk_aluno_categoria_tipo_ativa (aluno_id, categoria_cnh, tipo_servico, status)
        )
    ");
    
    echo "✅ Tabela matriculas criada com sucesso!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

