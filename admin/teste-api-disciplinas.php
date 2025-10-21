<?php
/**
 * Teste da API de Disciplinas Automáticas
 */

// Simular uma requisição GET
$_GET['acao'] = 'carregar_disciplinas';
$_GET['curso_tipo'] = 'formacao_45h';

// Capturar a saída da API
ob_start();
include 'api/disciplinas-automaticas.php';
$output = ob_get_clean();

echo "<h2>Teste da API - Disciplinas Automáticas</h2>";
echo "<h3>Parâmetros:</h3>";
echo "<ul>";
echo "<li>Ação: " . $_GET['acao'] . "</li>";
echo "<li>Curso Tipo: " . $_GET['curso_tipo'] . "</li>";
echo "</ul>";

echo "<h3>Resposta da API:</h3>";
echo "<pre>";
echo htmlspecialchars($output);
echo "</pre>";

echo "<h3>JSON Decodificado:</h3>";
$json = json_decode($output, true);
if ($json) {
    echo "<pre>";
    print_r($json);
    echo "</pre>";
    
    if (isset($json['disciplinas'])) {
        echo "<h3>Disciplinas Encontradas:</h3>";
        echo "<ul>";
        foreach ($json['disciplinas'] as $disciplina) {
            echo "<li>";
            echo "<strong>" . htmlspecialchars($disciplina['text']) . "</strong> - ";
            echo $disciplina['aulas'] . " aulas (" . $disciplina['aulas'] . "h)";
            echo "</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p style='color: red;'>Erro ao decodificar JSON</p>";
}

echo "<h3>Teste de Cursos Disponíveis:</h3>";
$cursos = ['formacao_45h', 'formacao_acc_20h', 'reciclagem_infrator', 'atualizacao'];

echo "<ul>";
foreach ($cursos as $curso) {
    echo "<li>";
    echo "<a href='?acao=carregar_disciplinas&curso_tipo=" . urlencode($curso) . "'>";
    echo htmlspecialchars($curso);
    echo "</a>";
    echo "</li>";
}
echo "</ul>";
?>
