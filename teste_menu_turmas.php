<?php
/**
 * Teste do Menu - Verificar se as novas funcionalidades estão acessíveis
 */

echo "<h1>Teste de Acesso às Páginas de Turmas</h1>";

$paginas = [
    'Dashboard' => 'admin/pages/turma-dashboard.php',
    'Calendário' => 'admin/pages/turma-calendario.php', 
    'Matrículas' => 'admin/pages/turma-matriculas.php',
    'Configurações' => 'admin/pages/turma-configuracoes.php',
    'Templates' => 'admin/pages/turma-templates.php',
    'Gerador de Grade' => 'admin/pages/turma-grade-generator.php',
    'Chamada' => 'admin/pages/turma-chamada.php',
    'Diário' => 'admin/pages/turma-diario.php',
    'Relatórios' => 'admin/pages/turma-relatorios.php'
];

echo "<h2>Status das Páginas:</h2>";
echo "<ul>";

foreach ($paginas as $nome => $arquivo) {
    $existe = file_exists($arquivo);
    $status = $existe ? "✅ Existe" : "❌ Não existe";
    $link = $existe ? "<a href='$arquivo' target='_blank'>Acessar</a>" : "N/A";
    
    echo "<li><strong>$nome:</strong> $status - $link</li>";
}

echo "</ul>";

echo "<h2>URLs Diretas para Teste:</h2>";
echo "<ul>";
echo "<li><a href='admin/pages/turma-dashboard.php' target='_blank'>Dashboard de Turmas</a></li>";
echo "<li><a href='admin/pages/turma-calendario.php' target='_blank'>Calendário de Aulas</a></li>";
echo "<li><a href='admin/pages/turma-matriculas.php' target='_blank'>Sistema de Matrículas</a></li>";
echo "<li><a href='admin/pages/turma-configuracoes.php' target='_blank'>Configurações</a></li>";
echo "<li><a href='admin/pages/turma-templates.php' target='_blank'>Templates</a></li>";
echo "<li><a href='admin/pages/turma-grade-generator.php' target='_blank'>Gerador de Grade</a></li>";
echo "</ul>";

echo "<h2>Menu Principal:</h2>";
echo "<p><a href='admin/index.php?page=turma-dashboard' target='_blank'>Acessar Dashboard via Menu</a></p>";
echo "<p><a href='admin/index.php?page=turma-calendario' target='_blank'>Acessar Calendário via Menu</a></p>";
echo "<p><a href='admin/index.php?page=turma-matriculas' target='_blank'>Acessar Matrículas via Menu</a></p>";

echo "<h2>Instruções:</h2>";
echo "<ol>";
echo "<li>Clique nos links acima para testar as páginas diretamente</li>";
echo "<li>Se as páginas funcionarem, o problema é no menu lateral</li>";
echo "<li>Se não funcionarem, há problema na implementação</li>";
echo "<li>Limpe o cache do navegador (Ctrl+F5)</li>";
echo "</ol>";
?>
