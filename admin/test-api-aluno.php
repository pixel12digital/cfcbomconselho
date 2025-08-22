<?php
header('Content-Type: application/json');

// Incluir arquivos necessários
require_once '../includes/config.php';
require_once '../includes/database.php';

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Teste API Aluno</title></head><body>\n";
echo "<h1>Teste da API de Alunos</h1>\n";

try {
    $db = Database::getInstance();
    
    // Testar conexão
    echo "<h2>1. Teste de Conexão</h2>\n";
    if ($db) {
        echo "<p style='color: green;'>✅ Conexão com banco estabelecida</p>\n";
    } else {
        echo "<p style='color: red;'>❌ Erro na conexão com banco</p>\n";
    }
    
    // Testar busca de alunos
    echo "<h2>2. Teste de Busca de Alunos</h2>\n";
    $alunos = $db->fetchAll("SELECT * FROM alunos ORDER BY id ASC");
    
    if ($alunos) {
        echo "<p style='color: green;'>✅ Encontrados " . count($alunos) . " aluno(s)</p>\n";
        echo "<h3>Lista de Alunos:</h3>\n";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr><th>ID</th><th>Nome</th><th>CPF</th><th>Email</th><th>Status</th></tr>\n";
        
        foreach ($alunos as $aluno) {
            echo "<tr>\n";
            echo "<td>{$aluno['id']}</td>\n";
            echo "<td>{$aluno['nome']}</td>\n";
            echo "<td>{$aluno['cpf']}</td>\n";
            echo "<td>{$aluno['email']}</td>\n";
            echo "<td>{$aluno['status']}</td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p style='color: red;'>❌ Nenhum aluno encontrado</p>\n";
    }
    
    // Testar busca específica do "Aluno Duplicado" (ID 102)
    echo "<h2>3. Teste Específico - Aluno ID 102</h2>\n";
    $aluno102 = $db->fetch('alunos', 'id = ?', [102]);
    
    if ($aluno102) {
        echo "<p style='color: green;'>✅ Aluno ID 102 encontrado</p>\n";
        echo "<pre>\n";
        print_r($aluno102);
        echo "</pre>\n";
    } else {
        echo "<p style='color: red;'>❌ Aluno ID 102 não encontrado</p>\n";
    }
    
    // Testar simulação da requisição AJAX
    echo "<h2>4. Teste de Simulação da Requisição AJAX</h2>\n";
    
    // Simular $_GET['id'] = 102
    $_GET['id'] = 102;
    
    $id = $_GET['id'];
    $aluno = $db->fetch('alunos', 'id = ?', [$id]);
    
    if ($aluno) {
        // Buscar dados do CFC
        $cfc = $db->fetch('cfcs', 'id = ?', [$aluno['cfc_id']]);
        $aluno['cfc_nome'] = $cfc ? $cfc['nome'] : 'N/A';
        
        $response = ['success' => true, 'aluno' => $aluno];
        echo "<p style='color: green;'>✅ Resposta da API seria:</p>\n";
        echo "<pre>\n";
        echo json_encode($response, JSON_PRETTY_PRINT);
        echo "</pre>\n";
    } else {
        echo "<p style='color: red;'>❌ Erro: Aluno não encontrado na simulação</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>💥 Erro: " . $e->getMessage() . "</p>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "\n<h2>5. Teste de URL da API</h2>\n";
echo "<p>URL base da API: <code>" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "</code></p>\n";
echo "<p>URL esperada pelo JavaScript: <code>api/alunos.php?id=102</code></p>\n";

// Testar se o arquivo da API existe
$apiPath = '../api/alunos.php';
if (file_exists($apiPath)) {
    echo "<p style='color: green;'>✅ Arquivo da API existe: {$apiPath}</p>\n";
} else {
    echo "<p style='color: red;'>❌ Arquivo da API não encontrado: {$apiPath}</p>\n";
}

echo "</body></html>\n";
?>
