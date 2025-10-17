<?php
try {
    $pdo = new PDO('mysql:host=auth-db803.hstgr.io;dbname=u502697186_cfcbomconselho;charset=utf8mb4', 'u502697186_cfcbomconselho', 'Los@ngo#081081');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Verificação de Disciplinas</h2>";
    
    // Verificar estrutura da tabela disciplinas
    $stmt = $pdo->query('DESCRIBE disciplinas');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Estrutura da tabela disciplinas:</h3>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>" . $column['Field'] . " (" . $column['Type'] . ")</li>";
    }
    echo "</ul>";
    
    // Verificar se existe a disciplina teste
    $stmt = $pdo->prepare('SELECT * FROM disciplinas WHERE codigo = ? OR nome LIKE ?');
    $stmt->execute(['teste_disciplina', '%teste%']);
    $disciplinas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Disciplinas encontradas com código 'teste_disciplina' ou nome contendo 'teste':</h3>";
    if (empty($disciplinas)) {
        echo "<p>Nenhuma disciplina encontrada.</p>";
    } else {
        echo "<ul>";
        foreach ($disciplinas as $disciplina) {
            echo "<li>ID: " . $disciplina['id'] . ", Código: " . $disciplina['codigo'] . ", Nome: " . $disciplina['nome'] . ", Ativa: " . $disciplina['ativa'] . "</li>";
        }
        echo "</ul>";
    }
    
    // Listar todas as disciplinas
    $stmt = $pdo->query('SELECT id, codigo, nome, ativa FROM disciplinas ORDER BY id DESC LIMIT 10');
    $todas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Últimas 10 disciplinas cadastradas:</h3>";
    echo "<ul>";
    foreach ($todas as $disc) {
        echo "<li>ID: " . $disc['id'] . ", Código: " . $disc['codigo'] . ", Nome: " . $disc['nome'] . ", Ativa: " . $disc['ativa'] . "</li>";
    }
    echo "</ul>";
    
    // Verificar se há filtro por CFC
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM disciplinas');
    $total = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h3>Total de disciplinas na tabela: " . $total['total'] . "</h3>";
    
} catch (Exception $e) {
    echo "<p>Erro: " . $e->getMessage() . "</p>";
}
?>
