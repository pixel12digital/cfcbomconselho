<?php
/**
 * Script para criar tabela contatos_aluno
 * FASE 4 - CONTATO ALUNO
 * 
 * Execute este arquivo uma vez para criar a tabela no banco de dados
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

// Verificar se está logado como admin (apenas se executado via navegador)
if (php_sapi_name() !== 'cli') {
    require_once __DIR__ . '/../includes/auth.php';
    if (!isLoggedIn()) {
        die('Você precisa estar logado para executar este script.');
    }
    $user = getCurrentUser();
    if (!in_array($user['tipo'], ['admin', 'secretaria'])) {
        die('Apenas admin/secretaria podem executar este script.');
    }
}

$db = db();

echo "<h2>Criando tabela contatos_aluno...</h2>";

try {
    // Verificar se tabela já existe
    $tabelaExiste = $db->fetch("SHOW TABLES LIKE 'contatos_aluno'");
    
    if ($tabelaExiste) {
        echo "<p style='color: orange;'>⚠️ Tabela contatos_aluno já existe. Nada a fazer.</p>";
    } else {
        // Ler script de migração
        $sql = file_get_contents(__DIR__ . '/../docs/scripts/migration_contatos_aluno.sql');
        
        // Extrair apenas o CREATE TABLE (ignorar comentários e SELECT final)
        if (preg_match('/CREATE TABLE IF NOT EXISTS contatos_aluno[^;]+;/s', $sql, $matches)) {
            $createTableSql = $matches[0];
            
            // Executar criação
            $db->query($createTableSql);
            
            echo "<p style='color: green;'>✅ Tabela contatos_aluno criada com sucesso!</p>";
            echo "<p><a href='../aluno/contato.php'>Ir para página de contato do aluno</a></p>";
        } else {
            echo "<p style='color: red;'>❌ Erro: Não foi possível extrair o SQL de criação da tabela.</p>";
        }
    }
    
    // Verificar estrutura
    echo "<h3>Estrutura da tabela:</h3>";
    $colunas = $db->fetchAll("SHOW COLUMNS FROM contatos_aluno");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Chave</th></tr>";
    foreach ($colunas as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
    error_log("Erro ao criar tabela contatos_aluno: " . $e->getMessage());
}

