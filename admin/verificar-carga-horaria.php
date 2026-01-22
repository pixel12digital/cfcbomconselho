<?php
require_once '../includes/config.php';

echo "<h1>🔍 Verificação de Carga Horária das Disciplinas</h1>";

try {
    // Conectar ao banco
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>📊 Dados das Disciplinas no Banco:</h2>";
    
    // Buscar todas as disciplinas
    $stmt = $pdo->query("SELECT id, nome, codigo, carga_horaria_padrao, descricao, ativa FROM disciplinas ORDER BY id");
    $disciplinas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Nome</th><th>Código</th><th>Carga Horária</th><th>Ativa</th><th>Descrição</th>";
    echo "</tr>";
    
    $totalHoras = 0;
    $disciplinasComHoras = 0;
    
    foreach ($disciplinas as $disciplina) {
        $cargaHoraria = $disciplina['carga_horaria_padrao'];
        $totalHoras += (int)$cargaHoraria;
        
        if ($cargaHoraria > 0) {
            $disciplinasComHoras++;
        }
        
        $cor = $cargaHoraria > 0 ? '#d4edda' : '#f8d7da';
        
        echo "<tr style='background: $cor;'>";
        echo "<td>{$disciplina['id']}</td>";
        echo "<td>{$disciplina['nome']}</td>";
        echo "<td>{$disciplina['codigo']}</td>";
        echo "<td><strong>{$cargaHoraria}h</strong></td>";
        echo "<td>" . ($disciplina['ativa'] ? 'Sim' : 'Não') . "</td>";
        echo "<td>{$disciplina['descricao']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h2>📈 Resumo:</h2>";
    echo "<ul>";
    echo "<li><strong>Total de disciplinas:</strong> " . count($disciplinas) . "</li>";
    echo "<li><strong>Disciplinas com carga horária > 0:</strong> $disciplinasComHoras</li>";
    echo "<li><strong>Disciplinas com carga horária = 0:</strong> " . (count($disciplinas) - $disciplinasComHoras) . "</li>";
    echo "<li><strong>Total de horas (soma):</strong> <strong style='color: " . ($totalHoras > 0 ? 'green' : 'red') . ";'>$totalHoras horas</strong></li>";
    echo "</ul>";
    
    if ($totalHoras == 0) {
        echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>⚠️ Problema Identificado:</h3>";
        echo "<p><strong>O total de horas é 0 porque:</strong></p>";
        echo "<ul>";
        echo "<li>Todas as disciplinas têm carga_horaria_padrao = 0 ou NULL</li>";
        echo "<li>Ou as disciplinas não estão sendo carregadas corretamente</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<h3>🔧 Solução:</h3>";
        echo "<p>Para corrigir, você precisa:</p>";
        echo "<ol>";
        echo "<li>Editar as disciplinas e definir a carga horária correta</li>";
        echo "<li>Ou atualizar diretamente no banco de dados</li>";
        echo "</ol>";
        
        echo "<h4>💡 Comando SQL para atualizar (exemplo):</h4>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
        echo "UPDATE disciplinas SET carga_horaria_padrao = 16 WHERE codigo = 'direcao_defensiva';\n";
        echo "UPDATE disciplinas SET carga_horaria_padrao = 18 WHERE codigo = 'legislacao_transito';\n";
        echo "UPDATE disciplinas SET carga_horaria_padrao = 12 WHERE codigo = 'mecanica_basica';\n";
        echo "-- etc...";
        echo "</pre>";
    } else {
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>✅ Status OK:</h3>";
        echo "<p>As disciplinas têm carga horária definida. O total de horas deve aparecer corretamente no sistema.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h3>❌ Erro:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
