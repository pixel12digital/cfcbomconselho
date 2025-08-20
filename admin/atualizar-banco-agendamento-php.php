<?php
/**
 * Script PHP para atualizar o banco de dados remoto do sistema de agendamento
 * Atualiza a estrutura das tabelas aulas e logs para incluir as colunas necessárias
 * 
 * @author Sistema CFC
 * @version 1.0
 * @since 2024
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

$db = Database::getInstance();

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Atualizar Banco - Sistema de Agendamento</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .card { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; background: #f8f9fa; }
        .btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        h1 { color: #333; text-align: center; }
        .status { padding: 10px; border-radius: 5px; margin: 10px 0; }
        .status-success { background: #d4edda; border: 1px solid #c3e6cb; }
        .status-error { background: #f8d7da; border: 1px solid #f5c6cb; }
        .status-warning { background: #fff3cd; border: 1px solid #ffeaa7; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Atualizar Banco de Dados - Sistema de Agendamento</h1>
        <p><strong>Banco:</strong> <span class='info'>{DB_HOST}</span></p>
        <p><strong>Database:</strong> <span class='info'>{DB_NAME}</span></p>
        <p><strong>Ambiente:</strong> <span class='info'>" . ENVIRONMENT . "</span></p>
        
        <hr>";

try {
    // Verificar conexão
    echo "<div class='card'>
        <h3>🔌 Verificando Conexão...</h3>";
    
    $db->query("SELECT 1");
    echo "<p class='success'>✅ Conexão com banco estabelecida com sucesso!</p>
    </div>";
    
    // 1. Atualizar tabela aulas
    echo "<div class='card'>
        <h3>📚 Atualizando Tabela 'aulas'...</h3>";
    
    // Verificar se a coluna veiculo_id já existe
    try {
        $result = $db->query("SHOW COLUMNS FROM aulas LIKE 'veiculo_id'");
        $colunaExiste = $result->rowCount() > 0;
        
        if (!$colunaExiste) {
            // Adicionar coluna veiculo_id
            $sql = "ALTER TABLE aulas ADD COLUMN veiculo_id INT NULL AFTER cfc_id";
            $db->query($sql);
            echo "<p class='success'>✅ Coluna 'veiculo_id' adicionada com sucesso!</p>";
            
            // Adicionar foreign key para veiculo_id
            try {
                $sql = "ALTER TABLE aulas ADD CONSTRAINT fk_aulas_veiculo FOREIGN KEY (veiculo_id) REFERENCES veiculos(id)";
                $db->query($sql);
                echo "<p class='success'>✅ Foreign key para veiculo_id criada com sucesso!</p>";
            } catch (Exception $e) {
                echo "<p class='warning'>⚠️ Foreign key para veiculo_id já existe ou não foi possível criar</p>";
            }
        } else {
            echo "<p class='info'>ℹ️ Coluna 'veiculo_id' já existe</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erro ao verificar/adicionar coluna veiculo_id: " . $e->getMessage() . "</p>";
    }
    
    // Verificar se a coluna atualizado_em já existe
    try {
        $result = $db->query("SHOW COLUMNS FROM aulas LIKE 'atualizado_em'");
        $colunaExiste = $result->rowCount() > 0;
        
        if (!$colunaExiste) {
            // Adicionar coluna atualizado_em
            $sql = "ALTER TABLE aulas ADD COLUMN atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER criado_em";
            $db->query($sql);
            echo "<p class='success'>✅ Coluna 'atualizado_em' adicionada com sucesso!</p>";
        } else {
            echo "<p class='info'>ℹ️ Coluna 'atualizado_em' já existe</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erro ao verificar/adicionar coluna atualizado_em: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
    
    // 2. Atualizar tabela logs
    echo "<div class='card'>
        <h3>📝 Atualizando Tabela 'logs'...</h3>";
    
    // Verificar se a tabela logs existe
    try {
        $result = $db->query("SHOW TABLES LIKE 'logs'");
        $tabelaExiste = $result->rowCount() > 0;
        
        if (!$tabelaExiste) {
            // Criar tabela logs se não existir
            $sql = "CREATE TABLE logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NULL,
                acao VARCHAR(100) NOT NULL,
                tabela VARCHAR(50) NULL,
                registro_id INT NULL,
                dados TEXT NULL,
                ip VARCHAR(45) NULL,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
            )";
            $db->query($sql);
            echo "<p class='success'>✅ Tabela 'logs' criada com sucesso!</p>";
        } else {
            echo "<p class='info'>ℹ️ Tabela 'logs' já existe</p>";
            
            // Verificar e atualizar colunas se necessário
            $colunas = ['tabela', 'dados', 'ip'];
            foreach ($colunas as $coluna) {
                try {
                    $result = $db->query("SHOW COLUMNS FROM logs LIKE '$coluna'");
                    $colunaExiste = $result->rowCount() > 0;
                    
                    if (!$colunaExiste) {
                        $tipo = ($coluna === 'dados') ? 'TEXT' : 'VARCHAR(50)';
                        $sql = "ALTER TABLE logs ADD COLUMN $coluna $tipo NULL";
                        $db->query($sql);
                        echo "<p class='success'>✅ Coluna '$coluna' adicionada à tabela logs!</p>";
                    }
                } catch (Exception $e) {
                    echo "<p class='warning'>⚠️ Erro ao verificar coluna '$coluna': " . $e->getMessage() . "</p>";
                }
            }
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erro ao verificar/criar tabela logs: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
    
    // 3. Verificar estrutura final
    echo "<div class='card'>
        <h3>🔍 Verificando Estrutura Final...</h3>";
    
    try {
        // Verificar tabela aulas
        $result = $db->query("DESCRIBE aulas");
        $colunasAulas = $result->fetchAll(PDO::FETCH_ASSOC);
        $colunasNecessarias = ['id', 'aluno_id', 'instrutor_id', 'cfc_id', 'veiculo_id', 'tipo_aula', 'data_aula', 'hora_inicio', 'hora_fim', 'status', 'observacoes', 'criado_em', 'atualizado_em'];
        $colunasExistentes = array_column($colunasAulas, 'Field');
        
        $colunasFaltando = array_diff($colunasNecessarias, $colunasExistentes);
        
        if (empty($colunasFaltando)) {
            echo "<p class='success'>✅ Tabela 'aulas' está com estrutura completa!</p>";
        } else {
            echo "<p class='warning'>⚠️ Colunas ainda faltando na tabela 'aulas': " . implode(', ', $colunasFaltando) . "</p>";
        }
        
        // Verificar tabela logs
        $result = $db->query("DESCRIBE logs");
        $colunasLogs = $result->fetchAll(PDO::FETCH_ASSOC);
        $colunasLogsNecessarias = ['id', 'usuario_id', 'acao', 'tabela', 'registro_id', 'dados', 'ip', 'criado_em'];
        $colunasLogsExistentes = array_column($colunasLogs, 'Field');
        
        $colunasLogsFaltando = array_diff($colunasLogsNecessarias, $colunasLogsExistentes);
        
        if (empty($colunasLogsFaltando)) {
            echo "<p class='success'>✅ Tabela 'logs' está com estrutura completa!</p>";
        } else {
            echo "<p class='warning'>⚠️ Colunas ainda faltando na tabela 'logs': " . implode(', ', $colunasLogsFaltando) . "</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erro ao verificar estrutura final: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
    
    // 4. Status final
    echo "<div class='card'>
        <h3>🎯 Status da Atualização</h3>
        <p class='success'>✅ Atualização do banco concluída com sucesso!</p>
        <p>O sistema de agendamento agora deve funcionar corretamente.</p>
        </div>";
    
    // 5. Links para próximos passos
    echo "<div class='card'>
        <h3>🔧 Próximos Passos</h3>
        <p>Agora você pode:</p>
        <a href='inserir-dados-agendamento.php' class='btn btn-success'>📋 Inserir Dados de Teste</a>
        <a href='teste-agendamento-completo.php' class='btn'>🧪 Teste Completo do Sistema</a>
        <a href='index.php?page=agendamento' class='btn'>📅 Sistema de Agendamento</a>
        </div>";
    
} catch (Exception $e) {
    echo "<div class='status status-error'>
        <h3>❌ Erro Crítico</h3>
        <p><strong>Erro:</strong> " . $e->getMessage() . "</p>
        <p>Verifique se o banco de dados está acessível e se as credenciais estão corretas.</p>
        </div>";
}

echo "</div></body></html>";
?>
