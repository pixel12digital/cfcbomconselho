<?php
/**
 * Teste de variáveis do usuário
 */

// Incluir dependências
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';

echo "🔍 Testando variáveis do usuário...\n";

// Verificar se está logado
if (!isLoggedIn()) {
    echo "❌ Usuário não está logado\n";
    exit;
}

echo "✅ Usuário está logado\n";

// Obter dados do usuário
$user = getCurrentUser();
echo "📋 Dados do usuário:\n";
print_r($user);

$userType = $user['tipo'] ?? 'admin';
$userId = $user['id'] ?? null;

echo "\n📊 Variáveis definidas:\n";
echo "userType: " . $userType . "\n";
echo "userId: " . $userId . "\n";

// Verificar permissões
$canView = ($userType === 'admin' || $userType === 'instrutor');
echo "canView: " . ($canView ? 'true' : 'false') . "\n";

echo "\n✅ Teste concluído!\n";
?>
