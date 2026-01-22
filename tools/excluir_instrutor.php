<?php
/**
 * Script para excluir instrutor e todos os dados relacionados
 * Uso: php tools/excluir_instrutor.php [cpf|id]
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

// Obter parâmetro (CPF ou ID)
$identifier = $argv[1] ?? null;

if (!$identifier) {
    echo "Uso: php tools/excluir_instrutor.php [cpf|id]\n";
    echo "Exemplo: php tools/excluir_instrutor.php 38380072987\n";
    echo "Exemplo: php tools/excluir_instrutor.php 1\n";
    exit(1);
}

// Buscar instrutor
$instructor = null;
if (is_numeric($identifier)) {
    // Buscar por ID
    $stmt = $db->prepare("SELECT * FROM instructors WHERE id = ?");
    $stmt->execute([$identifier]);
    $instructor = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    // Buscar por CPF (remover formatação)
    $cpf = preg_replace('/[^0-9]/', '', $identifier);
    $stmt = $db->prepare("SELECT * FROM instructors WHERE cpf = ?");
    $stmt->execute([$cpf]);
    $instructor = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$instructor) {
    echo "Instrutor não encontrado!\n";
    exit(1);
}

echo "Instrutor encontrado:\n";
echo "  ID: {$instructor['id']}\n";
echo "  Nome: {$instructor['name']}\n";
echo "  CPF: {$instructor['cpf']}\n";
echo "  Email: {$instructor['email']}\n";
echo "\n";

// Confirmar exclusão
echo "ATENÇÃO: Esta ação irá excluir:\n";
echo "  - O instrutor\n";
echo "  - Todas as aulas relacionadas\n";
echo "  - Todas as turmas teóricas relacionadas\n";
echo "  - A disponibilidade do instrutor\n";
echo "  - A foto do instrutor (se houver)\n";
echo "  - O usuário relacionado (se não estiver vinculado a outro registro)\n";
echo "\n";

// Contar registros relacionados
$lessonsCount = $db->query("SELECT COUNT(*) as count FROM lessons WHERE instructor_id = {$instructor['id']}")->fetch(PDO::FETCH_ASSOC)['count'];
$theoryClassesCount = $db->query("SELECT COUNT(*) as count FROM theory_classes WHERE instructor_id = {$instructor['id']}")->fetch(PDO::FETCH_ASSOC)['count'];
$availabilityCount = $db->query("SELECT COUNT(*) as count FROM instructor_availability WHERE instructor_id = {$instructor['id']}")->fetch(PDO::FETCH_ASSOC)['count'];

echo "Registros relacionados encontrados:\n";
echo "  - Aulas: {$lessonsCount}\n";
echo "  - Turmas teóricas: {$theoryClassesCount}\n";
echo "  - Disponibilidades: {$availabilityCount}\n";
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
    $lessons = $db->query("SELECT id FROM lessons WHERE instructor_id = {$instructor['id']}")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($lessons as $lesson) {
        $db->exec("DELETE FROM lessons WHERE id = {$lesson['id']}");
    }
    echo "    {$lessonsCount} aula(s) deletada(s).\n";

    // 2. Deletar todas as turmas teóricas relacionadas
    // (isso deletará automaticamente as sessões e matrículas por CASCADE)
    echo "  - Deletando turmas teóricas...\n";
    $theoryClasses = $db->query("SELECT id FROM theory_classes WHERE instructor_id = {$instructor['id']}")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($theoryClasses as $theoryClass) {
        $db->exec("DELETE FROM theory_classes WHERE id = {$theoryClass['id']}");
    }
    echo "    {$theoryClassesCount} turma(s) teórica(s) deletada(s).\n";

    // 3. Deletar disponibilidade (já tem CASCADE, mas garantindo)
    echo "  - Deletando disponibilidades...\n";
    $db->exec("DELETE FROM instructor_availability WHERE instructor_id = {$instructor['id']}");
    echo "    {$availabilityCount} disponibilidade(s) deletada(s).\n";

    // 4. Remover foto do instrutor se existir
    if (!empty($instructor['photo_path'])) {
        echo "  - Removendo foto...\n";
        $filepath = __DIR__ . '/../' . $instructor['photo_path'];
        if (file_exists($filepath)) {
            @unlink($filepath);
            echo "    Foto removida.\n";
        }
    }

    // 5. Deletar usuário relacionado (se houver e não estiver vinculado a outro registro)
    if (!empty($instructor['user_id'])) {
        echo "  - Verificando usuário relacionado...\n";
        $otherInstructor = $db->query("SELECT id FROM instructors WHERE user_id = {$instructor['user_id']} AND id != {$instructor['id']}")->fetch(PDO::FETCH_ASSOC);
        $student = $db->query("SELECT id FROM students WHERE user_id = {$instructor['user_id']}")->fetch(PDO::FETCH_ASSOC);
        
        if (!$otherInstructor && !$student) {
            echo "  - Deletando usuário...\n";
            // Deletar roles do usuário
            $db->exec("DELETE FROM usuario_roles WHERE usuario_id = {$instructor['user_id']}");
            // Deletar usuário
            $db->exec("DELETE FROM usuarios WHERE id = {$instructor['user_id']}");
            echo "    Usuário deletado.\n";
        } else {
            echo "    Usuário mantido (vinculado a outro registro).\n";
        }
    }

    // 6. Registrar auditoria
    echo "  - Registrando auditoria...\n";
    $auditService = new AuditService();
    $auditService->logDelete('instrutores', $instructor['id'], $instructor);
    echo "    Auditoria registrada.\n";

    // 7. Deletar o instrutor
    echo "  - Deletando instrutor...\n";
    $db->exec("DELETE FROM instructors WHERE id = {$instructor['id']}");
    echo "    Instrutor deletado.\n";

    $db->commit();

    echo "\n✓ Exclusão concluída com sucesso!\n";
    echo "  Instrutor '{$instructor['name']}' (ID: {$instructor['id']}) foi excluído do banco de dados.\n";

} catch (Exception $e) {
    $db->rollBack();
    echo "\n✗ Erro ao excluir instrutor: " . $e->getMessage() . "\n";
    echo "  Operação revertida.\n";
    exit(1);
}
