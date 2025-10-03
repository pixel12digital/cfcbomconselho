<?php
/**
 * 🧪 Teste do Sistema de Deploy - CFC Bom Conselho
 */

require_once 'config/deploy-info.php';

echo "<h1>🚀 Teste do Sistema de Deploy</h1>";

echo "<h2>📋 Informações de Versão</h2>";
echo "<pre>" . DeployInfo::displayVersion() . "</pre>";

echo "<h2>🔧 Status do Sistema</h2>";

// Verificar arquivos essenciais
$files = [
    'login.php' => 'Sistema de Login',
    'includes/config.php' => 'Configurações',
    'includes/auth.php' => 'Autenticação',
    '.github/workflows/production-deploy.yml' => 'GitHub Actions',
    'deploy.php' => 'Webhook Deploy'
];

echo "<ul>";
foreach ($files as $file => $description) {
    $exists = file_exists($file);
    $status = $exists ? '✅' : '❌';
    echo "<li>{$status} <strong>{$description}:</strong> " . 
         ($exists ? '<code>' . $file . '</code>' : 'FALTANDO') . "</li>";
}
echo "</ul>";

// Verificar configurações
echo "<h2>⚙️ Configurações</h2>";
echo "<ul>";
echo "<li><strong>Environment:</strong> " . (defined('ENVIRONMENT') ? ENVIRONMENT : 'N/D') . "</li>";
echo "<li><strong>Database:</strong> " . (defined('DB_HOST') ? 'Conectado (' . DB_HOST . ')' : 'N/D') . "</li>";
echo "<li><strong>Session Domain:</strong> " . (defined('SESSION_COOKIE_DOMAIN') ? SESSION_COOKIE_DOMAIN : 'N/D') . "</li>";
echo "</ul>";

// Verificar Git
echo "<h2>🔀 Git Status</h2>";
$gitStatus = shell_exec('git status --porcelain 2>&1');
if ($gitStatus !== null) {
    echo "<pre>Git Status:\n" . $gitStatus . "</pre>";
}

$gitBranch = shell_exec('git branch --show-current 2>&1');
echo "<p><strong>Branch atual:</strong> " . trim($gitBranch) . "</p>";

echo "<h2>🎯 Próximos Passos</h2>";
echo "<ol>";
echo "<li>✅ Upload manual para resolver login AGORA</li>";
echo "<li>⚙️ Configurar SSH secrets no GitHub</li>";
echo "<li>🚀 Ativar GitHub Actions workflow</li>";
echo "<li>📝 Fazer primeiro deploy automático</li>";
echo "<li>🎉 Testar login em produção</li>";
echo "</ol>";

echo "<p><a href='login.php?debug_version=1'>🔍 Ver versão detalhada</a></p>";
?>
