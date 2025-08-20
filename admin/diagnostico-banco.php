<?php
/**
 * Script de Diagnóstico do Banco de Dados
 * Identifica problemas específicos na estrutura e execução de queries
 */

require_once '../includes/config.php';
require_once '../includes/database.php';

try {
    $db = Database::getInstance();
    
    echo "<h1>🔍 DIAGNÓSTICO COMPLETO DO BANCO DE DADOS</h1>";
    echo "<p>Identificando problemas específicos...</p>";
    echo "<hr>";
    
    // 1. Verificar conexão
    echo "<h2>1. 🔌 CONEXÃO COM BANCO</h2>";
    try {
        $conexao = $db->getConnection();
        echo "✅ Conexão PDO estabelecida<br>";
        echo "✅ Versão MySQL: " . $conexao->getAttribute(PDO::ATTR_SERVER_VERSION) . "<br>";
        echo "✅ Charset: " . $conexao->query('SELECT @@character_set_database')->fetchColumn() . "<br>";
    } catch (Exception $e) {
        echo "❌ Erro na conexão: " . $e->getMessage() . "<br>";
        return;
    }
    
    // 2. Verificar estrutura das tabelas
    echo "<h2>2. 📋 ESTRUTURA DAS TABELAS</h2>";
    
    $tabelas = ['usuarios', 'cfcs', 'alunos', 'instrutores', 'veiculos', 'aulas', 'logs'];
    
    foreach ($tabelas as $tabela) {
        try {
            $colunas = $db->fetchAll("DESCRIBE $tabela");
            echo "✅ Tabela '$tabela': " . count($colunas) . " colunas<br>";
            
            // Verificar se a tabela tem dados
            $count = $db->fetchColumn("SELECT COUNT(*) FROM $tabela");
            echo "   📊 Registros: $count<br>";
            
        } catch (Exception $e) {
            echo "❌ Erro na tabela '$tabela': " . $e->getMessage() . "<br>";
        }
    }
    
    // 3. Testar queries específicas
    echo "<h2>3. 🧪 TESTE DE QUERIES ESPECÍFICAS</h2>";
    
    // Teste 1: SELECT simples
    try {
        $resultado = $db->fetch("SELECT 1 as teste");
        echo "✅ SELECT simples: OK<br>";
    } catch (Exception $e) {
        echo "❌ SELECT simples falhou: " . $e->getMessage() . "<br>";
    }
    
    // Teste 2: INSERT em usuarios
    try {
        $senhaHash = password_hash('teste123', PASSWORD_DEFAULT);
        $db->query("
            INSERT INTO usuarios (nome, email, senha, tipo, status, criado_em) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ", ['Usuário Teste', 'teste@teste.com', $senhaHash, 'admin', 'ativo']);
        
        $id = $db->getConnection()->lastInsertId();
        echo "✅ INSERT em usuarios: OK (ID: $id)<br>";
        
        // Limpar teste
        $db->query("DELETE FROM usuarios WHERE id = ?", [$id]);
        echo "✅ DELETE de teste: OK<br>";
        
    } catch (Exception $e) {
        echo "❌ INSERT em usuarios falhou: " . $e->getMessage() . "<br>";
        
        // Verificar estrutura específica
        try {
            $colunas = $db->fetchAll("DESCRIBE usuarios");
            echo "   📋 Colunas da tabela usuarios:<br>";
            foreach ($colunas as $col) {
                echo "      - {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']}<br>";
            }
        } catch (Exception $e2) {
            echo "   ❌ Não foi possível verificar estrutura: " . $e2->getMessage() . "<br>";
        }
    }
    
    // Teste 3: INSERT em cfcs
    try {
        $db->query("
            INSERT INTO cfcs (nome, cnpj, endereco, cidade, estado, responsavel, telefone, email, status, criado_em) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ", [
            'CFC Teste',
            '11.111.111/0001-11',
            'Rua Teste, 123',
            'São Paulo',
            'SP',
            'Responsável Teste',
            '(11) 11111-1111',
            'teste@cfc.com',
            'ativo'
        ]);
        
        $id = $db->getConnection()->lastInsertId();
        echo "✅ INSERT em cfcs: OK (ID: $id)<br>";
        
        // Limpar teste
        $db->query("DELETE FROM cfcs WHERE id = ?", [$id]);
        echo "✅ DELETE de teste: OK<br>";
        
    } catch (Exception $e) {
        echo "❌ INSERT em cfcs falhou: " . $e->getMessage() . "<br>";
        
        // Verificar estrutura específica
        try {
            $colunas = $db->fetchAll("DESCRIBE cfcs");
            echo "   📋 Colunas da tabela cfcs:<br>";
            foreach ($colunas as $col) {
                echo "      - {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']}<br>";
            }
        } catch (Exception $e2) {
            echo "   ❌ Não foi possível verificar estrutura: " . $e2->getMessage() . "<br>";
        }
    }
    
    // 4. Verificar permissões
    echo "<h2>4. 🔐 VERIFICAÇÃO DE PERMISSÕES</h2>";
    
    try {
        $usuario = $db->fetchColumn("SELECT USER()");
        echo "✅ Usuário atual: $usuario<br>";
        
        $permissoes = $db->fetchAll("SHOW GRANTS");
        echo "✅ Permissões verificadas (" . count($permissoes) . " grants)<br>";
        
    } catch (Exception $e) {
        echo "❌ Erro ao verificar permissões: " . $e->getMessage() . "<br>";
    }
    
    // 5. Verificar configurações
    echo "<h2>5. ⚙️ CONFIGURAÇÕES DO BANCO</h2>";
    
    try {
        $configs = [
            'sql_mode' => $db->fetchColumn("SELECT @@sql_mode"),
            'max_allowed_packet' => $db->fetchColumn("SELECT @@max_allowed_packet"),
            'wait_timeout' => $db->fetchColumn("SELECT @@wait_timeout"),
            'interactive_timeout' => $db->fetchColumn("SELECT @@interactive_timeout")
        ];
        
        foreach ($configs as $config => $valor) {
            echo "✅ $config: $valor<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Erro ao verificar configurações: " . $e->getMessage() . "<br>";
    }
    
    echo "<hr>";
    echo "<h2>🎯 DIAGNÓSTICO COMPLETO</h2>";
    echo "<p>Verifique os resultados acima para identificar o problema específico.</p>";
    
} catch (Exception $e) {
    echo "<h2>❌ ERRO CRÍTICO NO DIAGNÓSTICO</h2>";
    echo "<p>Erro: " . $e->getMessage() . "</p>";
    echo "<p>Verifique se o banco de dados está rodando e acessível.</p>";
}
?>
