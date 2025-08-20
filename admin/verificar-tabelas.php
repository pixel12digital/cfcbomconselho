<?php
/**
 * VERIFICAR TABELAS DISPONÍVEIS
 * Script para listar todas as tabelas do banco de dados
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
echo "<title>Verificar Tabelas Disponíveis</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }";
echo ".container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo ".header { background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; }";
echo ".table-section { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; padding: 15px; margin-bottom: 15px; }";
echo ".table-title { color: #495057; font-weight: bold; margin-bottom: 10px; font-size: 16px; }";
echo ".success { color: #28a745; font-weight: bold; }";
echo ".error { color: #dc3545; font-weight: bold; }";
echo ".info { color: #17a2b8; font-weight: bold; }";
echo ".data-table { width: 100%; border-collapse: collapse; margin: 10px 0; }";
echo ".data-table th, .data-table td { border: 1px solid #dee2e6; padding: 8px; text-align: left; }";
echo ".data-table th { background-color: #e9ecef; font-weight: bold; }";
echo ".data-table tr:nth-child(even) { background-color: #f8f9fa; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<div class='header'>";
echo "<h1>🔍 VERIFICAR TABELAS DISPONÍVEIS</h1>";
echo "<p>Data/Hora: " . date('d/m/Y H:i:s') . "</p>";
echo "<p>Ambiente: XAMPP Local (Porta 8080)</p>";
echo "</div>";

try {
    // Incluir arquivos necessários
    require_once '../includes/database.php';
    require_once '../includes/config.php';
    
    echo "<div class='table-section'>";
    echo "<div class='table-title'>✅ Arquivos incluídos com sucesso</div>";
    echo "</div>";
    
    // Conectar ao banco
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='table-section'>";
    echo "<div class='table-title'>✅ Conexão PDO estabelecida</div>";
    echo "</div>";
    
    // Listar todas as tabelas
    echo "<div class='table-section'>";
    echo "<div class='table-title'>📋 TABELAS DISPONÍVEIS NO BANCO</div>";
    
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($tables)) {
        echo "<p class='success'>Total de tabelas encontradas: " . count($tables) . "</p>";
        echo "<table class='data-table'>";
        echo "<thead><tr><th>#</th><th>Nome da Tabela</th><th>Status</th></tr></thead><tbody>";
        
        foreach ($tables as $index => $table) {
            $rowClass = ($index % 2 == 0) ? '' : 'background-color: #f8f9fa;';
            echo "<tr style='{$rowClass}'>";
            echo "<td>" . ($index + 1) . "</td>";
            echo "<td><strong>{$table}</strong></td>";
            echo "<td class='success'>✅ Disponível</td>";
            echo "</tr>";
        }
        
        echo "</tbody></table>";
        
        // Verificar estrutura de algumas tabelas principais
        echo "<h3>🔍 ESTRUTURA DAS PRINCIPAIS TABELAS</h3>";
        
        $mainTables = ['cfcs', 'usuarios', 'instrutores', 'alunos', 'veiculos', 'aulas', 'sessoes', 'logs'];
        
        foreach ($mainTables as $table) {
            if (in_array($table, $tables)) {
                echo "<div class='table-section'>";
                echo "<div class='table-title'>📊 Estrutura da tabela: {$table}</div>";
                
                try {
                    $stmt = $pdo->query("DESCRIBE {$table}");
                    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (!empty($structure)) {
                        echo "<table class='data-table'>";
                        echo "<thead><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead><tbody>";
                        
                        foreach ($structure as $field) {
                            $rowClass = (array_search($field, $structure) % 2 == 0) ? '' : 'background-color: #f8f9fa;';
                            echo "<tr style='{$rowClass}'>";
                            echo "<td><strong>{$field['Field']}</strong></td>";
                            echo "<td>{$field['Type']}</td>";
                            echo "<td>{$field['Null']}</td>";
                            echo "<td>{$field['Key']}</td>";
                            echo "<td>{$field['Default']}</td>";
                            echo "<td>{$field['Extra']}</td>";
                            echo "</tr>";
                        }
                        
                        echo "</tbody></table>";
                    }
                } catch (Exception $e) {
                    echo "<p class='error'>Erro ao verificar estrutura: " . $e->getMessage() . "</p>";
                }
                
                echo "</div>";
            }
        }
        
    } else {
        echo "<p class='error'>Nenhuma tabela encontrada no banco de dados!</p>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='table-section'>";
    echo "<div class='table-title'>❌ ERRO</div>";
    echo "<p class='error'>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<div class='table-section'>";
echo "<div class='table-title'>💡 PRÓXIMOS PASSOS</div>";
echo "<p>Com base nas tabelas disponíveis, criarei o TESTE #12 correto para uma tabela que realmente existe.</p>";
echo "<p><strong>Tabelas candidatas para teste:</strong></p>";
echo "<ul>";
echo "<li><strong>relatorios</strong> - Se existir, para TESTE #12: CRUD de Relatórios</li>";
echo "<li><strong>notificacoes</strong> - Se existir, para TESTE #12: CRUD de Notificações</li>";
echo "<li><strong>documentos</strong> - Se existir, para TESTE #12: CRUD de Documentos</li>";
echo "<li><strong>pagamentos</strong> - Se existir, para TESTE #12: CRUD de Pagamentos</li>";
echo "</ul>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
