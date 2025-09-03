<?php
// Script para verificar dependências do usuário ID=1 e simular a resposta da API
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = Database::getInstance();

echo "=== VERIFICAÇÃO DE DEPENDÊNCIAS DO USUÁRIO ID=1 ===\n\n";

// Verificar se o usuário existe
$usuario = $db->fetch("SELECT * FROM usuarios WHERE id = 1");
if (!$usuario) {
    echo "❌ Usuário ID=1 não encontrado!\n";
    exit;
}

echo "✅ Usuário encontrado:\n";
echo "   Nome: " . $usuario['nome'] . "\n";
echo "   Email: " . $usuario['email'] . "\n";
echo "   Tipo: " . $usuario['tipo'] . "\n\n";

// Verificar todas as dependências (igual à API)
$dependencias = [];

// Verificar CFCs vinculados
$cfcsVinculados = $db->fetchAll("SELECT id, nome FROM cfcs WHERE responsavel_id = 1");
if (count($cfcsVinculados) > 0) {
    $dependencias[] = [
        'tipo' => 'CFCs',
        'quantidade' => count($cfcsVinculados),
        'itens' => $cfcsVinculados,
        'instrucao' => 'Remova ou altere o responsável dos CFCs antes de excluir o usuário.'
    ];
    echo "❌ CFCs vinculados encontrados:\n";
    foreach ($cfcsVinculados as $cfc) {
        echo "   - CFC ID: " . $cfc['id'] . " - Nome: " . $cfc['nome'] . "\n";
    }
    echo "\n";
}

// Verificar registros de instrutor
$instrutoresVinculados = $db->fetchAll("SELECT id, cfc_id FROM instrutores WHERE usuario_id = 1");
if (count($instrutoresVinculados) > 0) {
    $dependencias[] = [
        'tipo' => 'Registros de Instrutor',
        'quantidade' => count($instrutoresVinculados),
        'itens' => $instrutoresVinculados,
        'instrucao' => 'Remova os registros de instrutor antes de excluir o usuário.'
    ];
    echo "❌ Registros de instrutor encontrados:\n";
    foreach ($instrutoresVinculados as $instrutor) {
        echo "   - Instrutor ID: " . $instrutor['id'] . " - CFC ID: " . $instrutor['cfc_id'] . "\n";
    }
    echo "\n";
}

// Verificar aulas como instrutor
$aulasComoInstrutor = $db->fetchAll("
    SELECT a.id, a.data_aula, a.tipo_aula FROM aulas a 
    INNER JOIN instrutores i ON a.instrutor_id = i.id 
    WHERE i.usuario_id = 1
");
if (count($aulasComoInstrutor) > 0) {
    $dependencias[] = [
        'tipo' => 'Aulas como Instrutor',
        'quantidade' => count($aulasComoInstrutor),
        'itens' => $aulasComoInstrutor,
        'instrucao' => 'Remova ou altere as aulas onde o usuário é instrutor antes de excluí-lo.'
    ];
    echo "❌ Aulas como instrutor encontradas:\n";
    foreach ($aulasComoInstrutor as $aula) {
        echo "   - Aula ID: " . $aula['id'] . " - Data: " . $aula['data_aula'] . " - Tipo: " . $aula['tipo_aula'] . "\n";
    }
    echo "\n";
}

// Verificar sessões
$sessoes = $db->fetchAll("SELECT * FROM sessoes WHERE usuario_id = 1");
if (count($sessoes) > 0) {
    echo "ℹ️  Usuário possui " . count($sessoes) . " sessões ativas\n\n";
} else {
    echo "✅ Nenhuma sessão ativa\n\n";
}

// Verificar logs
$logs = $db->fetchAll("SELECT * FROM logs WHERE usuario_id = 1");
if (count($logs) > 0) {
    echo "ℹ️  Usuário possui " . count($logs) . " registros de log\n\n";
} else {
    echo "✅ Nenhum registro de log\n\n";
}

echo "=== RESULTADO ===\n";
if (!empty($dependencias)) {
    echo "❌ USUÁRIO NÃO PODE SER EXCLUÍDO - Possui vínculos ativos\n\n";
    
    echo "📋 MENSAGEM QUE A API RETORNARIA:\n";
    echo "----------------------------------------\n";
    
    $mensagem = "Não é possível excluir o usuário pois ele possui vínculos ativos:\n\n";
    foreach ($dependencias as $dep) {
        $mensagem .= "• {$dep['tipo']}: {$dep['quantidade']} registro(s)\n";
        $mensagem .= "  Instrução: {$dep['instrucao']}\n\n";
    }
    $mensagem .= "Resolva todos os vínculos antes de tentar excluir o usuário novamente.";
    
    echo $mensagem . "\n";
    echo "----------------------------------------\n\n";
    
    echo "📝 INSTRUÇÕES PARA RESOLVER:\n";
    foreach ($dependencias as $i => $dep) {
        echo ($i + 1) . ". {$dep['instrucao']}\n";
    }
    
    echo "\n🔧 AÇÕES NECESSÁRIAS:\n";
    if (count($cfcsVinculados) > 0) {
        echo "1. Acesse o painel de CFCs\n";
        echo "2. Altere o responsável dos CFCs vinculados para outro usuário\n";
        echo "3. Ou remova os CFCs se não forem necessários\n";
    }
    if (count($instrutoresVinculados) > 0) {
        echo "4. Acesse o painel de instrutores\n";
        echo "5. Remova os registros de instrutor vinculados ao usuário\n";
    }
    if (count($aulasComoInstrutor) > 0) {
        echo "6. Acesse o painel de aulas\n";
        echo "7. Altere o instrutor das aulas ou remova as aulas\n";
    }
    
} else {
    echo "✅ USUÁRIO PODE SER EXCLUÍDO - Nenhuma dependência crítica encontrada\n";
    echo "   (Sessões e logs são removidos automaticamente durante a exclusão)\n";
}

echo "\n=== FIM DA VERIFICAÇÃO ===\n";
?>
