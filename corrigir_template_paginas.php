<?php
/**
 * Script para corrigir todas as páginas de turmas para seguir o padrão do template
 */

echo "🔧 Corrigindo páginas para seguir o padrão do template...\n";

$paginas = [
    'turma-calendario.php',
    'turma-matriculas.php', 
    'turma-configuracoes.php',
    'turma-templates.php',
    'turma-grade-generator.php'
];

foreach ($paginas as $pagina) {
    $arquivo = "admin/pages/$pagina";
    
    if (!file_exists($arquivo)) {
        echo "❌ Arquivo não encontrado: $arquivo\n";
        continue;
    }
    
    echo "📝 Processando: $pagina\n";
    
    $conteudo = file_get_contents($arquivo);
    
    // Remover DOCTYPE, html, head, body tags
    $conteudo = preg_replace('/<!DOCTYPE html[^>]*>/i', '', $conteudo);
    $conteudo = preg_replace('/<html[^>]*>/i', '', $conteudo);
    $conteudo = preg_replace('/<\/html>/i', '', $conteudo);
    $conteudo = preg_replace('/<head[^>]*>.*?<\/head>/is', '', $conteudo);
    $conteudo = preg_replace('/<body[^>]*>/i', '', $conteudo);
    $conteudo = preg_replace('/<\/body>/i', '', $conteudo);
    
    // Remover includes desnecessários (já incluídos no template)
    $conteudo = preg_replace('/require_once __DIR__ \. \'\/\.\.\/\.\.\/includes\/config\.php\';/', '', $conteudo);
    $conteudo = preg_replace('/require_once __DIR__ \. \'\/\.\.\/\.\.\/includes\/database\.php\';/', '', $conteudo);
    $conteudo = preg_replace('/require_once __DIR__ \. \'\/\.\.\/\.\.\/includes\/auth\.php\';/', '', $conteudo);
    
    // Remover verificação de autenticação (já feita no template)
    $conteudo = preg_replace('/\/\/ Verificar autenticação.*?exit\(\);.*?}/s', '', $conteudo);
    
    // Adicionar verificação de permissões no início
    $verificacao = '<?php
// Verificar permissões
$canView = ($userType === \'admin\' || $userType === \'instrutor\');
if (!$canView) {
    echo \'<div class="alert alert-danger">Acesso negado. Apenas administradores e instrutores podem acessar esta página.</div>\';
    return;
}
?>';

    // Se não tem verificação de permissões, adicionar
    if (strpos($conteudo, '$canView') === false) {
        $conteudo = $verificacao . "\n" . $conteudo;
    }
    
    // Corrigir links para usar sistema de roteamento
    $conteudo = preg_replace('/href="pages\/([^"]+)"/', 'href="?page=$1"', $conteudo);
    $conteudo = preg_replace('/href="([^"]*\.php[^"]*)"/', 'href="?page=' . str_replace('.php', '', basename($arquivo)) . '"', $conteudo);
    
    // Salvar arquivo corrigido
    file_put_contents($arquivo, $conteudo);
    
    echo "✅ Corrigido: $pagina\n";
}

echo "\n🎉 Todas as páginas foram corrigidas!\n";
echo "📋 Agora todas seguem o padrão do template do sistema.\n";
echo "🔗 Use as URLs corretas: admin/index.php?page=nome-da-pagina\n";
?>
