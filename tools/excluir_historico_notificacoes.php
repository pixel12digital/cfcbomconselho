<?php
/**
 * Script para excluir todo o histórico de notificações
 * Uso: php tools/excluir_historico_notificacoes.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

// Autoload
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
} else {
    require_once APP_PATH . '/autoload.php';
}

// Carregar variáveis de ambiente
use App\Config\Env;
Env::load();

use App\Config\Database;
use App\Services\AuditService;

// Inicializar banco de dados
$db = Database::getInstance()->getConnection();

echo "=== EXCLUIR HISTÓRICO DE NOTIFICAÇÕES ===\n\n";

// Contar notificações
$totalCount = $db->query("SELECT COUNT(*) as count FROM notifications")->fetch(PDO::FETCH_ASSOC)['count'];
$unreadCount = $db->query("SELECT COUNT(*) as count FROM notifications WHERE is_read = 0")->fetch(PDO::FETCH_ASSOC)['count'];
$readCount = $db->query("SELECT COUNT(*) as count FROM notifications WHERE is_read = 1")->fetch(PDO::FETCH_ASSOC)['count'];

echo "Estatísticas atuais:\n";
echo "  - Total de notificações: {$totalCount}\n";
echo "  - Não lidas: {$unreadCount}\n";
echo "  - Lidas: {$readCount}\n";
echo "\n";

if ($totalCount == 0) {
    echo "Não há notificações para excluir.\n";
    exit(0);
}

// Confirmar exclusão
echo "ATENÇÃO: Esta ação irá excluir TODAS as notificações do sistema!\n";
echo "  - Todas as notificações serão permanentemente removidas\n";
echo "  - Não há como desfazer esta ação\n";
echo "\n";

echo "Deseja continuar? (digite 'SIM' para confirmar): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

if (strtoupper($line) !== 'SIM') {
    echo "Operação cancelada.\n";
    exit(0);
}

echo "\nIniciando exclusão...\n";

try {
    $db->beginTransaction();

    // Registrar auditoria
    echo "  - Registrando auditoria...\n";
    $auditService = new AuditService();
    $auditService->log('delete_all_notifications', 'notifications', null, ['count' => $totalCount], null);
    echo "    Auditoria registrada.\n";

    // Deletar todas as notificações
    echo "  - Deletando notificações...\n";
    $db->exec("DELETE FROM notifications");
    echo "    {$totalCount} notificação(ões) deletada(s).\n";

    $db->commit();

    echo "\n✓ Exclusão concluída com sucesso!\n";
    echo "  Todo o histórico de notificações foi removido do banco de dados.\n";

} catch (Exception $e) {
    $db->rollBack();
    echo "\n✗ Erro ao excluir histórico de notificações: " . $e->getMessage() . "\n";
    echo "  Operação revertida.\n";
    exit(1);
}
