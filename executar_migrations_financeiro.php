<?php
/**
 * Script para executar migrations do sistema financeiro
 */

require_once 'includes/config.php';
require_once 'includes/database.php';

try {
    $db = Database::getInstance();
    
    echo "🚀 Iniciando migrations do sistema financeiro...\n";
    
    // 1.1 Ajustes mínimos em matriculas
    echo "📝 Executando ajustes na tabela matriculas...\n";
    $db->query("ALTER TABLE matriculas ADD COLUMN IF NOT EXISTS valor_total DECIMAL(10,2) DEFAULT 0 AFTER aluno_id");
    $db->query("ALTER TABLE matriculas ADD COLUMN IF NOT EXISTS forma_pagamento ENUM('avista','parcelado') DEFAULT 'avista' AFTER valor_total");
    $db->query("ALTER TABLE matriculas ADD COLUMN IF NOT EXISTS status_financeiro ENUM('regular','inadimplente') DEFAULT 'regular' AFTER forma_pagamento");
    echo "✅ Tabela matriculas atualizada\n";
    
    // 1.2 Contas a Receber — faturas
    echo "📝 Criando tabela faturas...\n";
    $db->query("
        CREATE TABLE IF NOT EXISTS faturas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            matricula_id INT NOT NULL,
            aluno_id INT NOT NULL,
            numero VARCHAR(30) UNIQUE,
            descricao VARCHAR(255),
            valor DECIMAL(10,2) NOT NULL,
            desconto DECIMAL(10,2) DEFAULT 0,
            acrescimo DECIMAL(10,2) DEFAULT 0,
            valor_liquido DECIMAL(10,2) NOT NULL DEFAULT 0,
            vencimento DATE NOT NULL,
            status ENUM('aberta','paga','cancelada','vencida','parcial') DEFAULT 'aberta',
            meio ENUM('pix','boleto','cartao','dinheiro','transferencia','outro') DEFAULT 'pix',
            asaas_charge_id VARCHAR(64) NULL,
            criado_por INT NOT NULL,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_faturas_matricula (matricula_id),
            INDEX idx_faturas_aluno (aluno_id),
            INDEX idx_faturas_status (status),
            INDEX idx_faturas_vencimento (vencimento),
            FOREIGN KEY (matricula_id) REFERENCES matriculas(id),
            FOREIGN KEY (aluno_id) REFERENCES alunos(id)
        )
    ");
    echo "✅ Tabela faturas criada\n";
    
    // 1.3 Baixas — pagamentos
    echo "📝 Criando tabela pagamentos...\n";
    $db->query("
        CREATE TABLE IF NOT EXISTS pagamentos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fatura_id INT NOT NULL,
            data_pagamento DATE NOT NULL,
            valor_pago DECIMAL(10,2) NOT NULL,
            metodo ENUM('pix','boleto','cartao','dinheiro','transferencia','outro') DEFAULT 'pix',
            comprovante_url VARCHAR(255) NULL,
            obs VARCHAR(255) NULL,
            asaas_payment_id VARCHAR(64) NULL,
            criado_por INT NOT NULL,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_pag_fatura (fatura_id),
            FOREIGN KEY (fatura_id) REFERENCES faturas(id)
        )
    ");
    echo "✅ Tabela pagamentos criada\n";
    
    // 1.4 Contas a Pagar — despesas
    echo "📝 Criando tabela despesas...\n";
    $db->query("
        CREATE TABLE IF NOT EXISTS despesas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            titulo VARCHAR(120) NOT NULL,
            fornecedor VARCHAR(120) NULL,
            categoria ENUM('combustivel','manutencao','aluguel','taxas','salarios','outros') DEFAULT 'outros',
            valor DECIMAL(10,2) NOT NULL,
            vencimento DATE NOT NULL,
            pago TINYINT(1) DEFAULT 0,
            data_pagamento DATE NULL,
            metodo ENUM('pix','boleto','cartao','dinheiro','transferencia','outro') DEFAULT 'pix',
            anexo_url VARCHAR(255) NULL,
            obs VARCHAR(255) NULL,
            criado_por INT NOT NULL,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_despesas_pago (pago),
            INDEX idx_despesas_venc (vencimento)
        )
    ");
    echo "✅ Tabela despesas criada\n";
    
    // 1.5 Consentimento LGPD (mínimo)
    echo "📝 Adicionando campos LGPD na tabela alunos...\n";
    $db->query("ALTER TABLE alunos ADD COLUMN IF NOT EXISTS lgpd_consentido TINYINT(1) DEFAULT 0 AFTER observacoes");
    $db->query("ALTER TABLE alunos ADD COLUMN IF NOT EXISTS lgpd_consentido_em DATETIME NULL AFTER lgpd_consentido");
    echo "✅ Campos LGPD adicionados na tabela alunos\n";
    
    echo "\n🎉 Migrations executadas com sucesso!\n";
    
    // Verificar estruturas das tabelas
    echo "\n📊 Verificando estruturas das tabelas...\n";
    
    $tabelas = ['matriculas', 'faturas', 'pagamentos', 'despesas', 'alunos'];
    foreach ($tabelas as $tabela) {
        echo "\n--- Estrutura da tabela $tabela ---\n";
        $result = $db->fetchAll("SHOW CREATE TABLE $tabela");
        if ($result) {
            echo $result[0]['Create Table'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erro ao executar migrations: " . $e->getMessage() . "\n";
    exit(1);
}
