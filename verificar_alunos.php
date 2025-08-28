<?php
// Verificar se há alunos na base de dados
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Verificar Alunos na Base de Dados</h1>";

try {
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    
    $db = Database::getInstance();
    
    // Verificar se há alunos
    $alunos = $db->query("SELECT * FROM alunos LIMIT 5")->fetchAll();
    
    if (count($alunos) > 0) {
        echo "<h2>✅ Alunos encontrados: " . count($alunos) . "</h2>";
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Nome</th><th>CPF</th><th>CFC ID</th><th>Status</th></tr>";
        
        foreach ($alunos as $aluno) {
            echo "<tr>";
            echo "<td>{$aluno['id']}</td>";
            echo "<td>{$aluno['nome']}</td>";
            echo "<td>{$aluno['cpf']}</td>";
            echo "<td>{$aluno['cfc_id']}</td>";
            echo "<td>{$aluno['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Testar a API diretamente
        echo "<h2>Testando API diretamente...</h2>";
        
        $primeiroAluno = $alunos[0];
        $id = $primeiroAluno['id'];
        
        echo "Testando exclusão do aluno ID: {$id} - {$primeiroAluno['nome']}<br>";
        
        // Simular requisição DELETE
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $rawInput = json_encode(['id' => $id]);
        
        echo "Método: " . $_SERVER['REQUEST_METHOD'] . "<br>";
        echo "Raw input: " . $rawInput . "<br>";
        
        // Capturar saída da API
        ob_start();
        
        // Incluir a API
        include 'admin/api/alunos.php';
        
        $apiOutput = ob_get_clean();
        
        echo "<h3>Saída da API:</h3>";
        echo "<pre>" . htmlspecialchars($apiOutput) . "</pre>";
        
    } else {
        echo "<h2>❌ Nenhum aluno encontrado na base de dados</h2>";
        echo "<p>É necessário ter pelo menos um aluno para testar a exclusão.</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Erro:</h2>";
    echo "Mensagem: " . $e->getMessage() . "<br>";
    echo "Arquivo: " . $e->getFile() . "<br>";
    echo "Linha: " . $e->getLine() . "<br>";
}

echo "<hr>";
echo "<p><strong>Verificação concluída em:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
