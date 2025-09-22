<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = Database::getInstance();

echo "Criando tabela financeiro_faturas...\n";

try {
    $db->query("CREATE TABLE IF NOT EXISTS financeiro_faturas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        aluno_id INT NOT NULL,
        matricula_id INT NULL,
        titulo VARCHAR(255) NOT NULL,
        valor_total DECIMAL(10,2) NOT NULL,
        status ENUM('aberta','paga','cancelada','vencida') DEFAULT 'aberta',
        vencimento DATE NOT NULL,
        forma_pagamento ENUM('avista','parcelado','pix','boleto','cartao','dinheiro','transferencia') DEFAULT 'avista',
        parcelas INT DEFAULT 1,
        observacoes TEXT NULL,
        asaas_charge_id VARCHAR(64) NULL,
        criado_por INT NOT NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_faturas_aluno (aluno_id),
        INDEX idx_faturas_matricula (matricula_id),
        INDEX idx_faturas_status (status),
        INDEX idx_faturas_vencimento (vencimento),
        INDEX idx_faturas_criado (criado_em),
        FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
        FOREIGN KEY (matricula_id) REFERENCES matriculas(id) ON DELETE SET NULL,
        FOREIGN KEY (criado_por) REFERENCES usuarios(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    echo "✅ Tabela financeiro_faturas criada!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

echo "\nCriando tabela financeiro_pagamentos...\n";

try {
    $db->query("CREATE TABLE IF NOT EXISTS financeiro_pagamentos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fornecedor VARCHAR(255) NOT NULL,
        descricao VARCHAR(255) NULL,
        categoria ENUM('combustivel','manutencao','aluguel','taxas','salarios','material','outros') DEFAULT 'outros',
        valor DECIMAL(10,2) NOT NULL,
        status ENUM('paga','pendente','vencida') DEFAULT 'pendente',
        vencimento DATE NOT NULL,
        forma_pagamento ENUM('pix','boleto','cartao','dinheiro','transferencia') DEFAULT 'pix',
        data_pagamento DATE NULL,
        observacoes TEXT NULL,
        comprovante_url VARCHAR(255) NULL,
        criado_por INT NOT NULL,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_pagamentos_status (status),
        INDEX idx_pagamentos_categoria (categoria),
        INDEX idx_pagamentos_vencimento (vencimento),
        INDEX idx_pagamentos_criado (criado_em),
        FOREIGN KEY (criado_por) REFERENCES usuarios(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    echo "✅ Tabela financeiro_pagamentos criada!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

echo "\nCriando tabela financeiro_configuracoes...\n";

try {
    $db->query("CREATE TABLE IF NOT EXISTS financeiro_configuracoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chave VARCHAR(100) NOT NULL UNIQUE,
        valor TEXT NOT NULL,
        descricao VARCHAR(255) NULL,
        tipo ENUM('string','number','boolean','json') DEFAULT 'string',
        atualizado_por INT NOT NULL,
        atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (atualizado_por) REFERENCES usuarios(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    echo "✅ Tabela financeiro_configuracoes criada!\n";
    
    // Inserir configurações padrão
    $db->query("INSERT IGNORE INTO financeiro_configuracoes (chave, valor, descricao, tipo, atualizado_por) VALUES
        ('dias_inadimplencia', '30', 'Dias para considerar inadimplência', 'number', 1),
        ('asaas_enabled', 'false', 'Integração Asaas habilitada', 'boolean', 1),
        ('asaas_api_key', '', 'Chave da API Asaas', 'string', 1),
        ('asaas_webhook_token', '', 'Token do webhook Asaas', 'string', 1)");
    
    echo "✅ Configurações padrão inseridas!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

echo "\nAtualizando tabela alunos...\n";

try {
    $db->query("ALTER TABLE alunos 
        ADD COLUMN IF NOT EXISTS inadimplente TINYINT(1) DEFAULT 0 AFTER status_financeiro,
        ADD COLUMN IF NOT EXISTS inadimplente_desde DATE NULL AFTER inadimplente");
    
    echo "✅ Campos de inadimplência adicionados!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

echo "\n🎉 Criação das tabelas financeiras concluída!\n";