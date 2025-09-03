<?php
// Script para testar a API de exclusão de usuários diretamente
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

echo "=== TESTE DIRETO DA API DE EXCLUSÃO ===\n\n";

// Simular um usuário admin logado (ID 15 - Robson Wagner)
$_SESSION['user_id'] = 15;
$_SESSION['user_email'] = 'rwavieira@gmail.com';
$_SESSION['user_name'] = 'Robson Wagner Alves Vieira';
$_SESSION['user_type'] = 'admin';
$_SESSION['last_activity'] = time();

$db = Database::getInstance();

// Verificar se o usuário logado existe
$currentUser = $db->fetch("SELECT * FROM usuarios WHERE id = 15");
if (!$currentUser) {
    echo "❌ Usuário logado não encontrado!\n";
    exit;
}

echo "✅ Usuário logado: " . $currentUser['nome'] . " (ID: " . $currentUser['id'] . ")\n\n";

// Verificar se o usuário ID=1 existe
$usuarioParaExcluir = $db->fetch("SELECT * FROM usuarios WHERE id = 1");
if (!$usuarioParaExcluir) {
    echo "❌ Usuário ID=1 não encontrado!\n";
    exit;
}

echo "✅ Usuário a ser excluído: " . $usuarioParaExcluir['nome'] . " (ID: " . $usuarioParaExcluir['id'] . ")\n\n";

// Verificar se não está tentando excluir a si mesmo
if ($currentUser['id'] == $usuarioParaExcluir['id']) {
    echo "❌ Tentativa de auto-exclusão detectada!\n";
    echo "Mensagem da API: Não é possível excluir o próprio usuário\n";
    exit;
}

echo "✅ Não é auto-exclusão\n\n";

// Verificar todas as dependências do usuário ID=1
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
}

echo "=== VERIFICAÇÃO DE DEPENDÊNCIAS ===\n";
if (!empty($dependencias)) {
    echo "❌ Dependências encontradas:\n";
    foreach ($dependencias as $dep) {
        echo "• {$dep['tipo']}: {$dep['quantidade']} registro(s)\n";
    }
    echo "\n";
} else {
    echo "✅ Nenhuma dependência crítica encontrada\n\n";
}

// Simular o processo de exclusão
echo "=== SIMULAÇÃO DO PROCESSO DE EXCLUSÃO ===\n";

if (!empty($dependencias)) {
    echo "❌ EXCLUSÃO BLOQUEADA - Dependências encontradas\n\n";
    
    $mensagem = "Não é possível excluir o usuário pois ele possui vínculos ativos:\n\n";
    foreach ($dependencias as $dep) {
        $mensagem .= "• {$dep['tipo']}: {$dep['quantidade']} registro(s)\n";
        $mensagem .= "  Instrução: {$dep['instrucao']}\n\n";
    }
    $mensagem .= "Resolva todos os vínculos antes de tentar excluir o usuário novamente.";
    
    echo "📋 MENSAGEM DA API:\n";
    echo "----------------------------------------\n";
    echo $mensagem . "\n";
    echo "----------------------------------------\n";
    
} else {
    echo "✅ INICIANDO PROCESSO DE EXCLUSÃO\n\n";
    
    try {
        // Começar transação
        $db->beginTransaction();
        
        // Excluir logs do usuário
        $logsRemovidos = $db->query("DELETE FROM logs WHERE usuario_id = 1");
        echo "✅ Logs removidos: " . $logsRemovidos->rowCount() . " registro(s)\n";
        
        // Excluir sessões do usuário
        $sessoesRemovidas = $db->query("DELETE FROM sessoes WHERE usuario_id = 1");
        echo "✅ Sessões removidas: " . $sessoesRemovidas->rowCount() . " registro(s)\n";
        
        // Excluir usuário
        $result = $db->delete('usuarios', 'id = 1', [1]);
        
        if ($result) {
            $db->commit();
            echo "✅ Usuário excluído com sucesso!\n";
            echo "📋 MENSAGEM DA API: Usuário excluído com sucesso\n";
        } else {
            $db->rollback();
            echo "❌ Falha ao excluir usuário\n";
            echo "📋 MENSAGEM DA API: Erro ao excluir usuário\n";
        }
        
    } catch (Exception $e) {
        $db->rollback();
        echo "❌ Exceção durante exclusão: " . $e->getMessage() . "\n";
        echo "📋 MENSAGEM DA API: Erro interno ao excluir usuário: " . $e->getMessage() . "\n";
    }
}

echo "\n=== FIM DO TESTE ===\n";
?>
