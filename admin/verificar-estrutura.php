<?php
/**
 * Script para verificar a estrutura da tabela alunos
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
    <title>Verificar Estrutura - Sistema CFC</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; font-weight: bold; }
        .card { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; background: #f8f9fa; }
        h1 { color: #333; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 Verificar Estrutura das Tabelas</h1>
        
        <hr>";

try {
    // Verificar estrutura da tabela alunos
    echo "<div class='card'>
        <h3>👨‍🎓 Estrutura da Tabela 'alunos'</h3>";
    
    $result = $db->query("DESCRIBE alunos");
    $colunas = $result->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>
        <thead>
            <tr>
                <th>Campo</th>
                <th>Tipo</th>
                <th>Null</th>
                <th>Key</th>
                <th>Default</th>
                <th>Extra</th>
            </tr>
        </thead>
        <tbody>";
    
    foreach ($colunas as $coluna) {
        echo "<tr>
            <td>{$coluna['Field']}</td>
            <td>{$coluna['Type']}</td>
            <td>{$coluna['Null']}</td>
            <td>{$coluna['Key']}</td>
            <td>{$coluna['Default']}</td>
            <td>{$coluna['Extra']}</td>
        </tr>";
    }
    
    echo "</tbody></table></div>";
    
    // Verificar estrutura da tabela veiculos
    echo "<div class='card'>
        <h3>🚗 Estrutura da Tabela 'veiculos'</h3>";
    
    $result = $db->query("DESCRIBE veiculos");
    $colunas = $result->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>
        <thead>
            <tr>
                <th>Campo</th>
                <th>Tipo</th>
                <th>Null</th>
                <th>Key</th>
                <th>Default</th>
                <th>Extra</th>
            </tr>
        </thead>
        <tbody>";
    
    foreach ($colunas as $coluna) {
        echo "<tr>
            <td>{$coluna['Field']}</td>
            <td>{$coluna['Type']}</td>
            <td>{$coluna['Null']}</td>
            <td>{$coluna['Key']}</td>
            <td>{$coluna['Default']}</td>
            <td>{$coluna['Extra']}</td>
        </tr>";
    }
    
    echo "</tbody></table></div>";
    
    // Verificar dados existentes
    echo "<div class='card'>
        <h3>📊 Dados Existentes</h3>";
    
    $result = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo IN ('admin', 'instrutor')");
    $usuarios = $result->fetch(PDO::FETCH_ASSOC)['total'];
    
    $result = $db->query("SELECT COUNT(*) as total FROM alunos");
    $alunos = $result->fetch(PDO::FETCH_ASSOC)['total'];
    
    $result = $db->query("SELECT COUNT(*) as total FROM cfcs");
    $cfcs = $result->fetch(PDO::FETCH_ASSOC)['total'];
    
    $result = $db->query("SELECT COUNT(*) as total FROM veiculos");
    $veiculos = $result->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<p><strong>Usuários (Admin/Instrutor):</strong> <span class='info'>{$usuarios}</span></p>";
    echo "<p><strong>Alunos:</strong> <span class='info'>{$alunos}</span></p>";
    echo "<p><strong>CFCs:</strong> <span class='info'>{$cfcs}</span></p>";
    echo "<p><strong>Veículos:</strong> <span class='info'>{$veiculos}</span></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='card'>
        <h3 class='error'>❌ Erro no Sistema</h3>
        <p class='error'>Erro: " . $e->getMessage() . "</p>
        </div>";
}

echo "</div></body></html>";
?>
