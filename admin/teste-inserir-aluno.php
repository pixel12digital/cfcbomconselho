<?php
/**
 * Script de teste para inserir um aluno e identificar o problema
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
    <title>Teste Inserir Aluno - Sistema CFC</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; font-weight: bold; }
        .card { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; background: #f8f9fa; }
        h1 { color: #333; text-align: center; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🧪 Teste Inserir Aluno</h1>
        
        <hr>";

try {
    // Verificar estrutura da tabela
    echo "<div class='card'>
        <h3>🔍 Estrutura da Tabela 'alunos'</h3>";
    
    $result = $db->query("DESCRIBE alunos");
    $colunas = $result->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Colunas encontradas:</strong></p><ul>";
    foreach ($colunas as $coluna) {
        echo "<li>{$coluna['Field']} - {$coluna['Type']} - Null: {$coluna['Null']}</li>";
    }
    echo "</ul></div>";
    
    // Tentar inserir um aluno simples
    echo "<div class='card'>
        <h3>👨‍🎓 Testando Inserção de Aluno...</h3>";
    
    $sql = "INSERT INTO alunos (nome, cpf, rg, data_nascimento, endereco, telefone, email, cfc_id, categoria_cnh, status, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    echo "<p><strong>SQL:</strong> {$sql}</p>";
    
    $stmt = $db->query($sql);
    
    $alunoData = [
        'João Teste',
        '111.111.111-11',
        '11.111.111-1',
        '1990-01-01',
        'Rua Teste, 123',
        '(11) 11111-1111',
        'joao.teste@email.com',
        1, // cfc_id
        'B', // categoria_cnh
        'ativo' // status
    ];
    
    echo "<p><strong>Dados:</strong> " . json_encode($alunoData) . "</p>";
    
    $stmt->execute($alunoData);
    
    $alunoId = $db->lastInsertId();
    echo "<p class='success'>✅ Aluno inserido com sucesso! ID: {$alunoId}</p>";
    
    // Verificar se foi inserido
    $result = $db->query("SELECT * FROM alunos WHERE id = ?", [$alunoId]);
    $aluno = $result->fetch(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Aluno inserido:</strong></p>";
    echo "<ul>";
    foreach ($aluno as $campo => $valor) {
        echo "<li><strong>{$campo}:</strong> {$valor}</li>";
    }
    echo "</ul>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='card'>
        <h3 class='error'>❌ Erro no Sistema</h3>
        <p class='error'>Erro: " . $e->getMessage() . "</p>
        <p><strong>Trace:</strong></p>
        <pre>" . $e->getTraceAsString() . "</pre>
        </div>";
}

echo "</div></body></html>";
?>
