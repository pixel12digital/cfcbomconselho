<?php
// Script de debug para verificar estado do servidor
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 Debug do Servidor - Sistema CFC</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .section{margin:20px 0;padding:15px;border:1px solid #ccc;} .success{background:#d4edda;} .error{background:#f8d7da;} .info{background:#d1ecf1;}</style>";

echo "<div class='section info'>";
echo "<h3>📋 Informações do Servidor</h3>";
echo "<p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Server:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "</div>";

echo "<div class='section'>";
echo "<h3>📁 Verificação de Arquivos</h3>";

$arquivos = [
    'admin/pages/historico-aluno.php',
    'admin/pages/editar-aula.php',
    'admin/api/atualizar-aula.php',
    'admin/api/cancelar-aula.php',
    'admin/assets/css/admin.css',
    'admin/assets/css/action-buttons.css',
    'admin/assets/js/admin.js'
];

foreach ($arquivos as $arquivo) {
    if (file_exists($arquivo)) {
        $tamanho = filesize($arquivo);
        $modificado = date('Y-m-d H:i:s', filemtime($arquivo));
        echo "<p class='success'>✅ $arquivo existe (Tamanho: $tamanho bytes, Modificado: $modificado)</p>";
    } else {
        echo "<p class='error'>❌ $arquivo não encontrado</p>";
    }
}
echo "</div>";

echo "<div class='section'>";
echo "<h3>🔍 Verificação de Conteúdo</h3>";

// Verificar se ainda há mensagens antigas
$arquivos_historico = [
    'admin/pages/historico-aluno.php',
    'admin/pages/historico-aluno-melhorado.php',
    'admin/pages/historico-aluno-novo.php',
    'admin/pages/historico-aluno-backup.php',
    'admin/pages/historico-aluno-backup-final.php',
    'admin/pages/historico-instrutor.php'
];

foreach ($arquivos_historico as $arquivo) {
    if (file_exists($arquivo)) {
        $conteudo = file_get_contents($arquivo);
        if (strpos($conteudo, 'Funcionalidade de cancelamento será implementada em breve!') !== false) {
            echo "<p class='error'>❌ $arquivo ainda tem mensagem antiga</p>";
        } else {
            echo "<p class='success'>✅ $arquivo sem mensagem antiga</p>";
        }
        
        // Verificar se tem função cancelarAula correta
        if (strpos($conteudo, 'function cancelarAula(aulaId)') !== false) {
            if (strpos($conteudo, 'modalCancelarAula') !== false) {
                echo "<p class='success'>✅ $arquivo tem função cancelarAula correta</p>";
            } else {
                echo "<p class='error'>❌ $arquivo tem função cancelarAula mas sem modal</p>";
            }
        } else {
            echo "<p class='error'>❌ $arquivo não tem função cancelarAula</p>";
        }
    }
}
echo "</div>";

echo "<div class='section'>";
echo "<h3>🌐 Teste de URLs</h3>";

$urls = [
    '/cfc-bom-conselho/admin/index.php?page=historico-aluno&id=112',
    '/cfc-bom-conselho/admin/index.php?page=agendar-aula&action=edit&edit=7',
    '/cfc-bom-conselho/admin/api/cancelar-aula.php',
    '/cfc-bom-conselho/admin/api/atualizar-aula.php'
];

foreach ($urls as $url) {
    $full_url = 'http://' . $_SERVER['HTTP_HOST'] . $url;
    echo "<p><strong>Testando:</strong> <a href='$full_url' target='_blank'>$url</a></p>";
}
echo "</div>";

echo "<div class='section info'>";
echo "<h3>📝 Logs de Debug</h3>";
echo "<p>Verifique os logs do servidor para mensagens de debug:</p>";
echo "<pre>";
echo "tail -f /var/log/apache2/error.log | grep DEBUG\n";
echo "ou\n";
echo "tail -f /var/log/nginx/error.log | grep DEBUG\n";
echo "</pre>";
echo "</div>";

echo "<div class='section'>";
echo "<h3>🔧 Ações Recomendadas</h3>";
echo "<ol>";
echo "<li>Limpar cache do navegador (Ctrl+Shift+Delete)</li>";
echo "<li>Verificar logs do servidor</li>";
echo "<li>Testar URLs diretamente</li>";
echo "<li>Verificar se as alterações foram aplicadas no servidor</li>";
echo "</ol>";
echo "</div>";
?>
