<?php
/**
 * Script para excluir veículo e todos os dados relacionados
 * Uso: php tools/excluir_veiculo.php [placa|id]
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

// Obter parâmetro (placa ou ID)
$identifier = $argv[1] ?? null;

if (!$identifier) {
    echo "Uso: php tools/excluir_veiculo.php [placa|id]\n";
    echo "Exemplo: php tools/excluir_veiculo.php ABC-1234\n";
    echo "Exemplo: php tools/excluir_veiculo.php 1\n";
    exit(1);
}

// Buscar veículo
$vehicle = null;
if (is_numeric($identifier)) {
    // Buscar por ID
    $stmt = $db->prepare("SELECT * FROM vehicles WHERE id = ?");
    $stmt->execute([$identifier]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    // Buscar por placa (normalizar para maiúsculas)
    $plate = strtoupper(trim($identifier));
    $stmt = $db->prepare("SELECT * FROM vehicles WHERE plate = ?");
    $stmt->execute([$plate]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$vehicle) {
    echo "Veículo não encontrado!\n";
    exit(1);
}

echo "Veículo encontrado:\n";
echo "  ID: {$vehicle['id']}\n";
echo "  Placa: {$vehicle['plate']}\n";
echo "  Marca: {$vehicle['brand']}\n";
echo "  Modelo: {$vehicle['model']}\n";
echo "  Categoria: {$vehicle['category']}\n";
echo "\n";

// Confirmar exclusão
echo "ATENÇÃO: Esta ação irá excluir:\n";
echo "  - O veículo\n";
echo "  - Todas as aulas relacionadas (lessons)\n";
echo "\n";

// Contar registros relacionados
$lessonsCount = $db->query("SELECT COUNT(*) as count FROM lessons WHERE vehicle_id = {$vehicle['id']}")->fetch(PDO::FETCH_ASSOC)['count'];

echo "Registros relacionados encontrados:\n";
echo "  - Aulas: {$lessonsCount}\n";
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

    // 1. Deletar todas as aulas (lessons) relacionadas
    echo "  - Deletando aulas...\n";
    $lessons = $db->query("SELECT id FROM lessons WHERE vehicle_id = {$vehicle['id']}")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($lessons as $lesson) {
        $db->exec("DELETE FROM lessons WHERE id = {$lesson['id']}");
    }
    echo "    {$lessonsCount} aula(s) deletada(s).\n";

    // 2. Registrar auditoria
    echo "  - Registrando auditoria...\n";
    $auditService = new AuditService();
    $auditService->logDelete('veiculos', $vehicle['id'], $vehicle);
    echo "    Auditoria registrada.\n";

    // 3. Deletar o veículo
    echo "  - Deletando veículo...\n";
    $db->exec("DELETE FROM vehicles WHERE id = {$vehicle['id']}");
    echo "    Veículo deletado.\n";

    $db->commit();

    echo "\n✓ Exclusão concluída com sucesso!\n";
    echo "  Veículo '{$vehicle['plate']}' (ID: {$vehicle['id']}) foi excluído do banco de dados.\n";

} catch (Exception $e) {
    $db->rollBack();
    echo "\n✗ Erro ao excluir veículo: " . $e->getMessage() . "\n";
    echo "  Operação revertida.\n";
    exit(1);
}
