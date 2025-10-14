<?php
/**
 * Script para Executar Migração das Turmas Teóricas
 * 
 * ATENÇÃO: Este script deve ser executado APENAS UMA VEZ
 * Cria toda a estrutura necessária para o sistema de turmas teóricas
 * 
 * @author Sistema CFC Bom Conselho
 * @version 1.0
 * @since 2024
 */

// Verificar se é ambiente de desenvolvimento ou se foi passado o parâmetro correto
if (!isset($_GET['executar']) || $_GET['executar'] !== 'migracao_turmas_teoricas') {
    die('⚠️ Para executar esta migração, acesse: ?executar=migracao_turmas_teoricas');
}

// Incluir configurações
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

// Verificar autenticação (apenas admin pode executar)
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    die('❌ Apenas administradores podem executar migrações.');
}

echo "<h1>🚀 Migração: Sistema de Turmas Teóricas</h1>";
echo "<hr>";

try {
    $db = Database::getInstance();
    
    // Ler arquivo de migração
    $sqlFile = __DIR__ . '/migrations/001-create-turmas-teoricas-structure.sql';
    
    if (!file_exists($sqlFile)) {
        die("❌ Arquivo de migração não encontrado: {$sqlFile}");
    }
    
    $sql = file_get_contents($sqlFile);
    
    echo "<h2>📋 Executando Migração...</h2>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    
    // Dividir o SQL em comandos separados
    $commands = array_filter(
        array_map('trim', explode(';', $sql)), 
        function($cmd) {
            return !empty($cmd) && 
                   !str_starts_with($cmd, '--') && 
                   !str_starts_with($cmd, 'DELIMITER') &&
                   !str_starts_with($cmd, 'SELECT \'Migração concluída');
        }
    );
    
    $sucessos = 0;
    $erros = 0;
    
    foreach ($commands as $command) {
        $command = trim($command);
        if (empty($command)) continue;
        
        try {
            // Executar comando
            $db->query($command);
            
            // Identificar o tipo de comando para feedback
            if (str_starts_with($command, 'CREATE TABLE')) {
                preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $command, $matches);
                $tabela = $matches[1] ?? 'desconhecida';
                echo "✅ Tabela '{$tabela}' criada com sucesso<br>";
            } elseif (str_starts_with($command, 'INSERT')) {
                echo "✅ Dados inseridos com sucesso<br>";
            } elseif (str_starts_with($command, 'CREATE TRIGGER')) {
                preg_match('/CREATE TRIGGER\s+(\w+)/i', $command, $matches);
                $trigger = $matches[1] ?? 'desconhecido';
                echo "✅ Trigger '{$trigger}' criado com sucesso<br>";
            } elseif (str_starts_with($command, 'CREATE OR REPLACE VIEW')) {
                preg_match('/CREATE OR REPLACE VIEW\s+(\w+)/i', $command, $matches);
                $view = $matches[1] ?? 'desconhecida';
                echo "✅ View '{$view}' criada com sucesso<br>";
            } elseif (str_starts_with($command, 'UPDATE')) {
                echo "✅ Dados atualizados com sucesso<br>";
            } else {
                echo "✅ Comando executado com sucesso<br>";
            }
            
            $sucessos++;
            
        } catch (Exception $e) {
            echo "<div style='color: red;'>❌ Erro ao executar comando: " . $e->getMessage() . "</div>";
            $erros++;
        }
    }
    
    echo "</div>";
    
    // Resumo da migração
    echo "<h2>📊 Resumo da Migração</h2>";
    echo "<div style='background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<strong>Comandos executados com sucesso:</strong> {$sucessos}<br>";
    echo "<strong>Comandos com erro:</strong> {$erros}<br>";
    
    if ($erros === 0) {
        echo "<div style='color: green; font-size: 18px; margin-top: 15px;'>";
        echo "🎉 <strong>Migração concluída com sucesso!</strong>";
        echo "</div>";
        
        // Verificar se as tabelas foram criadas
        echo "<h3>🔍 Verificação das Tabelas Criadas</h3>";
        $tabelas = [
            'salas',
            'disciplinas_configuracao', 
            'turmas_teoricas',
            'turma_aulas_agendadas',
            'turma_matriculas',
            'turma_presencas',
            'turma_log'
        ];
        
        foreach ($tabelas as $tabela) {
            try {
                $resultado = $db->fetch("SELECT COUNT(*) as total FROM {$tabela}");
                echo "✅ Tabela '{$tabela}': {$resultado['total']} registro(s)<br>";
            } catch (Exception $e) {
                echo "❌ Erro ao verificar tabela '{$tabela}': " . $e->getMessage() . "<br>";
            }
        }
        
    } else {
        echo "<div style='color: red; font-size: 18px; margin-top: 15px;'>";
        echo "⚠️ <strong>Migração concluída com erros!</strong> Verifique os logs acima.";
        echo "</div>";
    }
    
    echo "</div>";
    
    // Próximos passos
    echo "<h2>🎯 Próximos Passos</h2>";
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<ol>";
    echo "<li>✅ Estrutura do banco de dados criada</li>";
    echo "<li>⚡ Acesse a nova interface: <a href='?page=turmas-teoricas' target='_blank'>Turmas Teóricas</a></li>";
    echo "<li>📝 Crie sua primeira turma usando o wizard</li>";
    echo "<li>🎓 Configure salas e disciplinas conforme necessário</li>";
    echo "</ol>";
    
    echo "<div style='margin-top: 20px; padding: 15px; background: #f8f9fa; border-left: 4px solid #007bff;'>";
    echo "<strong>💡 Dica:</strong> O sistema antigo de turmas continua funcionando normalmente. ";
    echo "O novo sistema é complementar e focado especificamente em turmas teóricas.";
    echo "</div>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; font-size: 18px; padding: 20px; background: #f8d7da; border-radius: 8px;'>";
    echo "❌ <strong>Erro crítico na migração:</strong><br>";
    echo $e->getMessage();
    echo "</div>";
}

echo "<hr>";
echo "<p><small>Migração executada em: " . date('d/m/Y H:i:s') . "</small></p>";
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
    background: #f8f9fa;
}

h1, h2, h3 {
    color: #023A8D;
}

hr {
    border: none;
    height: 1px;
    background: #ddd;
    margin: 20px 0;
}

a {
    color: #023A8D;
    text-decoration: none;
}

a:hover {
    color: #F7931E;
    text-decoration: underline;
}
</style>
