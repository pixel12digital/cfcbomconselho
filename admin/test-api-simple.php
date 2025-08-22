<?php
// Teste simples da API de alunos
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🧪 Teste Simples da API de Alunos</h1>";

// Incluir arquivos necessários
require_once '../includes/config.php';
require_once '../includes/database.php';

try {
    $db = Database::getInstance();
    echo "<p style='color: green;'>✅ Conexão com banco estabelecida</p>";
    
    // Testar busca específica do aluno ID 102
    echo "<h2>Teste: Buscar Aluno ID 102</h2>";
    
    $aluno = $db->findWhere('alunos', 'id = ?', [102], '*', null, 1);
    if ($aluno && is_array($aluno)) {
        $aluno = $aluno[0]; // Pegar o primeiro resultado
        echo "<p style='color: green;'>✅ Aluno encontrado:</p>";
        echo "<pre>";
        print_r($aluno);
        echo "</pre>";
        
        // Testar busca do CFC
        if (isset($aluno['cfc_id'])) {
            echo "<h3>Teste: Buscar CFC ID " . $aluno['cfc_id'] . "</h3>";
            $cfc = $db->findWhere('cfcs', 'id = ?', [$aluno['cfc_id']], '*', null, 1);
            if ($cfc && is_array($cfc)) {
                $cfc = $cfc[0];
                echo "<p style='color: green;'>✅ CFC encontrado:</p>";
                echo "<pre>";
                print_r($cfc);
                echo "</pre>";
            } else {
                echo "<p style='color: red;'>❌ CFC não encontrado</p>";
            }
        }
        
    } else {
        echo "<p style='color: red;'>❌ Aluno não encontrado</p>";
    }
    
    // Testar contagem de aulas vinculadas
    echo "<h2>Teste: Contar Aulas do Aluno ID 102</h2>";
    $aulasCount = $db->count('aulas', 'aluno_id = ?', [102]);
    echo "<p>Aulas vinculadas: <strong>{$aulasCount}</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>💥 Erro: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h2>🔗 Teste da API Real</h2>";
echo "<p>URL da API: <code>api/alunos.php?id=102</code></p>";
echo "<p>Status: <span id='status'>Testando...</span></p>";

?>

<script>
// Teste da API real
fetch('api/alunos.php?id=102')
    .then(response => {
        document.getElementById('status').innerHTML = `📨 Status: ${response.status} (${response.ok ? 'OK' : 'ERRO'})`;
        return response.json();
    })
    .then(data => {
        document.getElementById('status').innerHTML = `✅ Sucesso! Dados recebidos: ${JSON.stringify(data)}`;
    })
    .catch(error => {
        document.getElementById('status').innerHTML = `❌ Erro: ${error.message}`;
    });
</script>
