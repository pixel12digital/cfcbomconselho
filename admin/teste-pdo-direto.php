<?php
/**
 * Script de teste usando PDO diretamente
 * 
 * @author Sistema CFC
 * @version 1.0
 * @since 2024
 */

require_once __DIR__ . '/../includes/config.php';

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Teste PDO Direto - Sistema CFC</title>
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
        <h1>🧪 Teste PDO Direto</h1>
        
        <hr>";

try {
    // Conectar diretamente com PDO
    echo "<div class='card'>
        <h3>🔌 Conectando com PDO...</h3>";
    
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<p class='success'>✅ Conexão PDO estabelecida com sucesso!</p>";
    echo "</div>";
    
    // Verificar estrutura da tabela
    echo "<div class='card'>
        <h3>🔍 Estrutura da Tabela 'alunos'</h3>";
    
    $stmt = $pdo->query("DESCRIBE alunos");
    $colunas = $stmt->fetchAll();
    
    echo "<p><strong>Colunas encontradas:</strong></p><ul>";
    foreach ($colunas as $coluna) {
        echo "<li>{$coluna['Field']} - {$coluna['Type']} - Null: {$coluna['Null']}</li>";
    }
    echo "</ul></div>";
    
    // Tentar inserir um aluno
    echo "<div class='card'>
        <h3>👨‍🎓 Testando Inserção de Aluno...</h3>";
    
    $sql = "INSERT INTO alunos (nome, cpf, rg, data_nascimento, endereco, telefone, email, cfc_id, categoria_cnh, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    echo "<p><strong>SQL:</strong> {$sql}</p>";
    
    $stmt = $pdo->prepare($sql);
    
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
    
    $alunoId = $pdo->lastInsertId();
    echo "<p class='success'>✅ Aluno inserido com sucesso! ID: {$alunoId}</p>";
    
    // Verificar se foi inserido
    $stmt = $pdo->prepare("SELECT * FROM alunos WHERE id = ?");
    $stmt->execute([$alunoId]);
    $aluno = $stmt->fetch();
    
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
