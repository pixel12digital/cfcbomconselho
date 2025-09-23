<?php
/**
 * Script para criar tabelas de notificações
 */

require_once 'includes/config.php';
require_once 'includes/database.php';

try {
    $db = db();
    
    echo "Criando tabela de notificações...\n";
    
    // Criar tabela de notificações
    $sql_notificacoes = "
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
    )";
    
    $db->query($sql_notificacoes);
    echo "✓ Tabela 'notificacoes' criada com sucesso!\n";
    
    // Criar tabela de solicitações
    $sql_solicitacoes = "
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
        
        INDEX idx_aluno_status (aluno_id, status),
        INDEX idx_aula (aula_id),
        INDEX idx_status (status),
        INDEX idx_criado_em (criado_em)
    )";
    
    $db->query($sql_solicitacoes);
    echo "✓ Tabela 'solicitacoes_aluno' criada com sucesso!\n";
    
    // Verificar se a coluna tipo_acao existe na tabela logs
    $check_logs = $db->fetch("SHOW COLUMNS FROM logs LIKE 'tipo_acao'");
    if (!$check_logs) {
        $db->query("ALTER TABLE logs ADD COLUMN tipo_acao VARCHAR(50) DEFAULT NULL AFTER acao");
        echo "✓ Coluna 'tipo_acao' adicionada à tabela 'logs'!\n";
    } else {
        echo "✓ Coluna 'tipo_acao' já existe na tabela 'logs'!\n";
    }
    
    // Inserir notificação de exemplo
    $sql_exemplo = "
    INSERT INTO notificacoes (usuario_id, tipo_usuario, tipo_notificacao, titulo, mensagem, dados) 
    VALUES (1, 'admin', 'sistema_iniciado', 'Sistema de Notificações Ativado', 'O sistema de notificações foi ativado com sucesso.', '{\"sistema\": \"notificacoes\", \"status\": \"ativo\"}')
    ";
    
    $db->query($sql_exemplo);
    echo "✓ Notificação de exemplo inserida!\n";
    
    echo "\n🎉 Todas as tabelas foram criadas com sucesso!\n";
    echo "O sistema de notificações está pronto para uso.\n";
    
} catch (Exception $e) {
    echo "❌ Erro ao criar tabelas: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
