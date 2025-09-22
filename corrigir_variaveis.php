<?php
/**
 * Script para corrigir variáveis não definidas em todas as páginas de turmas
 */

echo "🔧 Corrigindo variáveis não definidas...\n";

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
    
    // Verificar se já tem a definição das variáveis
    if (strpos($conteudo, '$user = getCurrentUser();') === false) {
        // Adicionar definição das variáveis após o comentário de autor
        $conteudo = preg_replace(
            '/(\* @since \d+ \*\/\n)/',
            "$1\n// Obter dados do usuário (já definidos no template principal)\n\$user = getCurrentUser();\n\$userType = \$user['tipo'] ?? 'admin';\n\$userId = \$user['id'] ?? null;\n",
            $conteudo
        );
        
        // Salvar arquivo corrigido
        file_put_contents($arquivo, $conteudo);
        echo "✅ Corrigido: $pagina\n";
    } else {
        echo "ℹ️ Já corrigido: $pagina\n";
    }
}

echo "\n🎉 Todas as variáveis foram corrigidas!\n";
echo "📋 Agora todas as páginas têm as variáveis definidas corretamente.\n";
?>
