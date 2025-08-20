<?php
/**
 * VERIFICAR TABELAS EXISTENTES NO BANCO
 * Data/Hora: 19/08/2025 17:17:28
 * 
 * Este script lista todas as tabelas existentes no banco de dados
 * para identificar qual será o próximo teste CRUD
 */

// Configuração de exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html>";
echo "<html lang='pt-BR'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>VERIFICAR TABELAS EXISTENTES</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }";
echo ".container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo ".header { background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; }";
echo ".table-section { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; padding: 15px; margin-bottom: 15px; }";
echo ".table-title { color: #495057; font-weight: bold; margin-bottom: 10px; font-size: 16px; }";
echo ".success { color: #28a745; font-weight: bold; }";
echo ".error { color: #dc3545; font-weight: bold; }";
echo ".info { color: #17a2b8; font-weight: bold; }";
echo ".data-table { width: 100%; border-collapse: collapse; margin: 10px 0; }";
echo ".data-table th, .data-table td { border: 1px solid #dee2e6; padding: 8px; text-align: left; }";
echo ".data-table th { background-color: #e9ecef; font-weight: bold; }";
echo ".data-table tr:nth-child(even) { background-color: #f8f9fa; }";
echo ".summary { background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 6px; padding: 15px; margin-top: 20px; }";
echo ".summary h3 { color: #0056b3; margin-top: 0; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<div class='header'>";
echo "<h1>🔍 VERIFICAR TABELAS EXISTENTES NO BANCO</h1>";
echo "<p>Data/Hora: " . date('d/m/Y H:i:s') . "</p>";
echo "<p>Ambiente: XAMPP Local (Porta 8080)</p>";
echo "</div>";

// Inclusão de Arquivos Necessários
echo "<h2>1. Inclusão de Arquivos Necessários</h2>";

try {
    require_once '../includes/database.php';
    require_once '../includes/config.php';
    echo "<div class='table-section'>";
    echo "<div class='table-title'>✅ Arquivos necessários</div>";
    echo "<div class='success'>INCLUÍDOS COM SUCESSO</div>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='table-section'>";
    echo "<div class='table-title'>❌ Arquivos necessários</div>";
    echo "<div class='error'>ERRO: " . $e->getMessage() . "</div>";
    echo "</div>";
    exit;
}

// Conexão com Banco de Dados
echo "<h2>2. Conexão com Banco de Dados</h2>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div class='table-section'>";
    echo "<div class='table-title'>✅ Conexão PDO</div>";
    echo "<div class='success'>ESTABELECIDA COM SUCESSO</div>";
    echo "</div>";
} catch (PDOException $e) {
    echo "<div class='table-section'>";
    echo "<div class='table-title'>❌ Conexão PDO</div>";
    echo "<div class='error'>ERRO: " . $e->getMessage() . "</div>";
    echo "</div>";
    exit;
}

// Listar Todas as Tabelas
echo "<h2>3. Listar Todas as Tabelas do Banco</h2>";

try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($tables)) {
        echo "<div class='table-section'>";
        echo "<div class='table-title'>✅ Tabelas Encontradas</div>";
        echo "<div class='success'>Total de " . count($tables) . " tabelas encontradas</div>";
        
        echo "<table class='data-table'>";
        echo "<thead><tr><th>#</th><th>Nome da Tabela</th><th>Status</th></tr></thead><tbody>";
        
        foreach ($tables as $index => $table) {
            $status = "✅ EXISTE";
            echo "<tr>";
            echo "<td>" . ($index + 1) . "</td>";
            echo "<td><strong>" . htmlspecialchars($table) . "</strong></td>";
            echo "<td class='success'>" . $status . "</td>";
            echo "</tr>";
        }
        
        echo "</tbody></table>";
        echo "</div>";
        
        // Verificar Estrutura de Cada Tabela
        echo "<h2>4. Estrutura das Tabelas</h2>";
        
        foreach ($tables as $table) {
            echo "<h3>Tabela: <span class='info'>" . htmlspecialchars($table) . "</span></h3>";
            
            try {
                $stmt = $pdo->query("DESCRIBE " . $table);
                $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($structure)) {
                    echo "<div class='table-section'>";
                    echo "<div class='table-title'>✅ Estrutura da tabela '" . htmlspecialchars($table) . "'</div>";
                    
                    echo "<table class='data-table'>";
                    echo "<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead><tbody>";
                    
                    foreach ($structure as $column) {
                        echo "<tr>";
                        echo "<td><strong>" . htmlspecialchars($column['Field'] ?? '') . "</strong></td>";
                        echo "<td>" . htmlspecialchars($column['Type'] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($column['Null'] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($column['Key'] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($column['Default'] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($column['Extra'] ?? '') . "</td>";
                        echo "</tr>";
                    }
                    
                    echo "</tbody></table>";
                    echo "</div>";
                }
            } catch (Exception $e) {
                echo "<div class='table-section'>";
                echo "<div class='table-title'>❌ Estrutura da tabela '" . htmlspecialchars($table) . "'</div>";
                echo "<div class='error'>ERRO: " . $e->getMessage() . "</div>";
                echo "</div>";
            }
        }
        
    } else {
        echo "<div class='table-section'>";
        echo "<div class='table-title'>❌ Nenhuma Tabela Encontrada</div>";
        echo "<div class='error'>O banco de dados não possui tabelas</div>";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<div class='table-section'>";
    echo "<div class='table-title'>❌ Erro ao Listar Tabelas</div>";
    echo "<div class='error'>ERRO: " . $e->getMessage() . "</div>";
    echo "</div>";
}

// Resumo e Próximos Passos
echo "<div class='summary'>";
echo "<h3>📊 RESUMO DA VERIFICAÇÃO</h3>";

if (!empty($tables)) {
    echo "<p class='success'>✅ <strong>BANCO DE DADOS VERIFICADO COM SUCESSO!</strong></p>";
    echo "<p><strong>Total de Tabelas:</strong> " . count($tables) . "</p>";
    
    echo "<h4>🎯 PRÓXIMOS PASSOS RECOMENDADOS:</h4>";
    echo "<ol>";
    
    // Verificar se já testamos as tabelas principais
    $tabelasTestadas = ['cfcs', 'usuarios', 'instrutores', 'alunos', 'veiculos', 'aulas', 'sessoes', 'logs'];
    $tabelasDisponiveis = [];
    
    foreach ($tables as $table) {
        if (!in_array($table, $tabelasTestadas)) {
            $tabelasDisponiveis[] = $table;
        }
    }
    
    if (!empty($tabelasDisponiveis)) {
        echo "<li><strong>Próxima tabela para teste:</strong> <span class='info'>" . $tabelasDisponiveis[0] . "</span></li>";
        echo "<li>Criar TESTE #20 para CRUD de " . ucfirst($tabelasDisponiveis[0]) . "</li>";
        echo "<li>Executar o teste e verificar resultados</li>";
    } else {
        echo "<li><strong>Todas as tabelas principais já foram testadas!</strong></li>";
        echo "<li>Verificar se há tabelas adicionais ou criar novas funcionalidades</li>";
    }
    
    echo "</ol>";
    
    echo "<h4>📋 TABELAS DISPONÍVEIS PARA TESTE:</h4>";
    echo "<ul>";
    foreach ($tabelasDisponiveis as $table) {
        echo "<li><span class='info'>" . htmlspecialchars($table) . "</span></li>";
    }
    echo "</ul>";
    
} else {
    echo "<p class='error'>❌ <strong>PROBLEMA IDENTIFICADO!</strong></p>";
    echo "<p>O banco de dados não possui tabelas ou há erro na conexão.</p>";
    echo "<p><strong>Ações Recomendadas:</strong></p>";
    echo "<ol>";
    echo "<li>Verificar se o banco de dados está correto</li>";
    echo "<li>Verificar se as tabelas foram criadas</li>";
    echo "<li>Executar scripts de criação do banco</li>";
    echo "</ol>";
}

echo "<p><strong>💡 INFORMAÇÕES ADICIONAIS</strong></p>";
echo "<p><strong>URL de Verificação:</strong> /cfc-bom-conselho/admin/verificar-tabelas-existentes.php</p>";
echo "<p><strong>Banco de Dados:</strong> " . DB_NAME . "</p>";
echo "<p><strong>Host:</strong> " . DB_HOST . "</p>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
