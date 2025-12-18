<?php
chdir('C:\xampp\htdocs\cfc-bom-conselho');

require_once 'includes/config.php';
require_once 'includes/database.php';

$token = '676f711eef98d00d96218e326f147ac79a02a2c6dc4e2775538e7e5548bc9fdd';
$tokenHash = hash('sha256', $token);

$db = db();

echo "=== VERIFICAÇÃO DE EXPIRAÇÃO ===\n\n";

// Buscar token sem filtro de expiração
$reset = $db->fetch(
    "SELECT * FROM password_resets WHERE token_hash = :hash LIMIT 1",
    ['hash' => $tokenHash]
);

if ($reset) {
    echo "Token encontrado:\n";
    echo "  expires_at: " . $reset['expires_at'] . "\n";
    echo "  used_at: " . ($reset['used_at'] ?: 'NULL') . "\n\n";
    
    // Verificar timezone
    $nowUtc = $db->fetch("SELECT UTC_TIMESTAMP() as now");
    echo "UTC_TIMESTAMP() do MySQL: " . $nowUtc['now'] . "\n";
    echo "PHP gmdate(): " . gmdate('Y-m-d H:i:s') . "\n\n";
    
    // Comparar diretamente
    echo "Comparação:\n";
    echo "  expires_at: " . $reset['expires_at'] . "\n";
    echo "  UTC_TIMESTAMP(): " . $nowUtc['now'] . "\n";
    
    // Query exata da validação
    $validationQuery = $db->fetch(
        "SELECT id, login, type, expires_at, used_at FROM password_resets 
         WHERE token_hash = :token_hash AND expires_at > UTC_TIMESTAMP() AND used_at IS NULL 
         LIMIT 1",
        ['token_hash' => $tokenHash]
    );
    
    if ($validationQuery) {
        echo "\n✅ Query de validação RETORNOU resultado (token válido)\n";
    } else {
        echo "\n❌ Query de validação NÃO retornou resultado\n";
        
        // Verificar cada condição
        $check1 = $db->fetch("SELECT id FROM password_resets WHERE token_hash = :hash", ['hash' => $tokenHash]);
        echo "  - token_hash existe? " . ($check1 ? 'SIM' : 'NÃO') . "\n";
        
        $check2 = $db->fetch("SELECT id FROM password_resets WHERE token_hash = :hash AND expires_at > UTC_TIMESTAMP()", ['hash' => $tokenHash]);
        echo "  - expires_at > UTC_TIMESTAMP()? " . ($check2 ? 'SIM' : 'NÃO') . "\n";
        
        $check3 = $db->fetch("SELECT id FROM password_resets WHERE token_hash = :hash AND used_at IS NULL", ['hash' => $tokenHash]);
        echo "  - used_at IS NULL? " . ($check3 ? 'SIM' : 'NÃO') . "\n";
        
        // Comparar timestamps
        $expiresTimestamp = strtotime($reset['expires_at']);
        $nowTimestamp = strtotime($nowUtc['now']);
        echo "\n  Comparação de timestamps:\n";
        echo "    expires_at (timestamp): $expiresTimestamp\n";
        echo "    UTC_TIMESTAMP() (timestamp): $nowTimestamp\n";
        echo "    Diferença: " . ($expiresTimestamp - $nowTimestamp) . " segundos\n";
        echo "    Expirado? " . ($expiresTimestamp < $nowTimestamp ? 'SIM' : 'NÃO') . "\n";
    }
} else {
    echo "Token NÃO encontrado\n";
}
