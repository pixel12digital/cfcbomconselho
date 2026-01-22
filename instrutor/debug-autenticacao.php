<?php
/**
 * Script de debug para verificar autenticação e logs
 * Acesse: instrutor/debug-autenticacao.php
 */

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

echo "<h2>Debug de Autenticação - API Perfil</h2>";
echo "<pre>";

// 1. Verificar sessão
echo "=== 1. VERIFICAÇÃO DE SESSÃO ===\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'ATIVA' : 'INATIVA') . "\n";
echo "Session Name: " . session_name() . "\n";
echo "\nDados da Sessão:\n";
print_r($_SESSION);
echo "\n";

// 2. Verificar getCurrentUser()
echo "=== 2. VERIFICAÇÃO getCurrentUser() ===\n";
$user = getCurrentUser();
if ($user) {
    echo "✓ Usuário encontrado:\n";
    echo "  ID: " . $user['id'] . "\n";
    echo "  Nome: " . ($user['nome'] ?? 'N/A') . "\n";
    echo "  Email: " . ($user['email'] ?? 'N/A') . "\n";
    echo "  Tipo: " . ($user['tipo'] ?? 'N/A') . "\n";
    echo "  Status: " . ($user['status'] ?? 'N/A') . "\n";
    echo "  Ativo: " . ($user['ativo'] ?? 'N/A') . "\n";
} else {
    echo "✗ Usuário NÃO encontrado (getCurrentUser retornou null)\n";
    echo "  Possíveis causas:\n";
    echo "  - Sessão não iniciada\n";
    echo "  - user_id não está na sessão\n";
    echo "  - Usuário não existe no banco\n";
}
echo "\n";

// 3. Verificar getCurrentInstrutorId()
if ($user) {
    echo "=== 3. VERIFICAÇÃO getCurrentInstrutorId() ===\n";
    $instrutorId = getCurrentInstrutorId($user['id']);
    if ($instrutorId) {
        echo "✓ Instrutor ID encontrado: " . $instrutorId . "\n";
        
        // Buscar dados completos do instrutor
        $db = db();
        $instrutor = $db->fetch("SELECT * FROM instrutores WHERE id = ?", [$instrutorId]);
        if ($instrutor) {
            echo "\nDados do Instrutor:\n";
            echo "  ID: " . $instrutor['id'] . "\n";
            echo "  Nome: " . ($instrutor['nome'] ?? 'N/A') . "\n";
            echo "  Usuario ID: " . ($instrutor['usuario_id'] ?? 'N/A') . "\n";
            echo "  Ativo: " . ($instrutor['ativo'] ?? 'N/A') . "\n";
            echo "  Status: " . ($instrutor['status'] ?? 'N/A') . "\n";
        }
    } else {
        echo "✗ Instrutor ID NÃO encontrado\n";
        echo "  Verificando no banco...\n";
        
        $db = db();
        // Verificar se existe instrutor com este usuario_id
        $instrutorCheck = $db->fetch("SELECT * FROM instrutores WHERE usuario_id = ?", [$user['id']]);
        if ($instrutorCheck) {
            echo "  ⚠️ Instrutor encontrado no banco, mas getCurrentInstrutorId retornou null\n";
            echo "  Dados do instrutor:\n";
            echo "    ID: " . $instrutorCheck['id'] . "\n";
            echo "    Ativo: " . ($instrutorCheck['ativo'] ?? 'N/A') . "\n";
            echo "    Status: " . ($instrutorCheck['status'] ?? 'N/A') . "\n";
            echo "  Possível causa: instrutor não está ativo (ativo != 1)\n";
        } else {
            echo "  ✗ Nenhum instrutor encontrado com usuario_id = " . $user['id'] . "\n";
        }
    }
    echo "\n";
}

// 4. Verificar logs do PHP
echo "=== 4. VERIFICAÇÃO DE LOGS ===\n";
$logPath = __DIR__ . '/../logs/php_errors.log';
$logPathAlt = ini_get('error_log');

echo "Log configurado (config.php): " . ($logPath ?: 'N/A') . "\n";
echo "Log do PHP (ini_get): " . ($logPathAlt ?: 'N/A') . "\n";

if (file_exists($logPath)) {
    echo "✓ Arquivo de log encontrado: $logPath\n";
    echo "Tamanho: " . filesize($logPath) . " bytes\n";
    echo "\nÚltimas 30 linhas com '[API Perfil]':\n";
    $lines = file($logPath);
    $relevantLines = [];
    foreach (array_reverse($lines) as $line) {
        if (strpos($line, '[API Perfil]') !== false) {
            $relevantLines[] = $line;
            if (count($relevantLines) >= 30) break;
        }
    }
    if (empty($relevantLines)) {
        echo "  Nenhuma linha com '[API Perfil]' encontrada\n";
    } else {
        foreach (array_reverse($relevantLines) as $line) {
            echo "  " . trim($line) . "\n";
        }
    }
} else {
    echo "✗ Arquivo de log não encontrado: $logPath\n";
    if ($logPathAlt && file_exists($logPathAlt)) {
        echo "✓ Mas encontrado log alternativo: $logPathAlt\n";
    }
}

echo "\n=== 5. TESTE DE REQUISIÇÃO SIMULADA ===\n";
echo "Simulando verificação de autenticação como na API:\n";
echo "\n";

// Simular o que a API faz
$testUser = getCurrentUser();
if (!$testUser || $testUser['tipo'] !== 'instrutor') {
    echo "✗ FALHA: getCurrentUser() retornou null ou tipo != 'instrutor'\n";
    echo "  User: " . json_encode($testUser) . "\n";
} else {
    echo "✓ Passou verificação de getCurrentUser()\n";
    
    $testInstrutorId = getCurrentInstrutorId($testUser['id']);
    if (!$testInstrutorId) {
        echo "✗ FALHA: getCurrentInstrutorId() retornou null\n";
        echo "  User ID: " . $testUser['id'] . "\n";
    } else {
        echo "✓ Passou verificação de getCurrentInstrutorId()\n";
        echo "  Instrutor ID: " . $testInstrutorId . "\n";
    }
}

echo "\n</pre>";
echo "<hr>";
echo "<p><strong>Conclusão:</strong> Verifique os resultados acima para identificar o problema de autenticação.</p>";
