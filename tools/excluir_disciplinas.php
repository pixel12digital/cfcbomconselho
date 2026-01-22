<?php
/**
 * Script para excluir disciplinas e todos os dados relacionados
 * Uso: php tools/excluir_disciplinas.php [nome1] [nome2] ...
 * Exemplo: php tools/excluir_disciplinas.php "Legislação de Trânsito" "Direção Defensiva"
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

// Obter nomes das disciplinas dos argumentos
$disciplineNames = array_slice($argv, 1);

if (empty($disciplineNames)) {
    echo "Uso: php tools/excluir_disciplinas.php [nome1] [nome2] ...\n";
    echo "Exemplo: php tools/excluir_disciplinas.php \"Legislação de Trânsito\" \"Direção Defensiva\"\n";
    exit(1);
}

// Buscar disciplinas
$disciplines = [];
foreach ($disciplineNames as $name) {
    $stmt = $db->prepare("SELECT * FROM theory_disciplines WHERE name = ?");
    $stmt->execute([trim($name)]);
    $discipline = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($discipline) {
        $disciplines[] = $discipline;
    } else {
        echo "⚠ Disciplina não encontrada: {$name}\n";
    }
}

if (empty($disciplines)) {
    echo "Nenhuma disciplina encontrada para exclusão!\n";
    exit(1);
}

echo "=== DISCIPLINAS ENCONTRADAS ===\n\n";
foreach ($disciplines as $discipline) {
    echo "ID: {$discipline['id']}\n";
    echo "  Nome: {$discipline['name']}\n";
    echo "  CFC ID: {$discipline['cfc_id']}\n";
    echo "  Minutos: {$discipline['default_minutes']}\n";
    echo "  Status: " . ($discipline['active'] ? 'Ativa' : 'Inativa') . "\n";
    echo "\n";
}

// Confirmar exclusão
echo "ATENÇÃO: Esta ação irá excluir:\n";
echo "  - As disciplinas listadas acima\n";
echo "  - Todas as sessões teóricas relacionadas (theory_sessions)\n";
echo "  - Todas as presenças relacionadas (theory_attendance)\n";
echo "  - Todas as relações com cursos (theory_course_disciplines)\n";
echo "\n";

// Contar registros relacionados
$totalSessions = 0;
$totalAttendance = 0;
$totalCourseDisciplines = 0;

foreach ($disciplines as $discipline) {
    $sessionsCount = $db->query("SELECT COUNT(*) as count FROM theory_sessions WHERE discipline_id = {$discipline['id']}")->fetch(PDO::FETCH_ASSOC)['count'];
    $totalSessions += $sessionsCount;
    
    // Contar presenças através das sessões
    $attendanceCount = $db->query("SELECT COUNT(*) as count FROM theory_attendance ta INNER JOIN theory_sessions ts ON ta.session_id = ts.id WHERE ts.discipline_id = {$discipline['id']}")->fetch(PDO::FETCH_ASSOC)['count'];
    $totalAttendance += $attendanceCount;
    
    $courseDisciplinesCount = $db->query("SELECT COUNT(*) as count FROM theory_course_disciplines WHERE discipline_id = {$discipline['id']}")->fetch(PDO::FETCH_ASSOC)['count'];
    $totalCourseDisciplines += $courseDisciplinesCount;
}

echo "Registros relacionados encontrados:\n";
echo "  - Sessões teóricas: {$totalSessions}\n";
echo "  - Presenças: {$totalAttendance}\n";
echo "  - Relações com cursos: {$totalCourseDisciplines}\n";
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
    $auditService = new AuditService();

    foreach ($disciplines as $discipline) {
        echo "\n  Processando: {$discipline['name']} (ID: {$discipline['id']})...\n";
        
        // 1. Deletar todas as presenças relacionadas (através das sessões)
        echo "    - Deletando presenças...\n";
        $sessions = $db->query("SELECT id FROM theory_sessions WHERE discipline_id = {$discipline['id']}")->fetchAll(PDO::FETCH_ASSOC);
        $attendanceDeleted = 0;
        foreach ($sessions as $session) {
            $count = $db->exec("DELETE FROM theory_attendance WHERE session_id = {$session['id']}");
            $attendanceDeleted += $count;
        }
        echo "      {$attendanceDeleted} presença(s) deletada(s).\n";

        // 2. Deletar todas as sessões teóricas relacionadas
        echo "    - Deletando sessões teóricas...\n";
        $sessionsDeleted = $db->exec("DELETE FROM theory_sessions WHERE discipline_id = {$discipline['id']}");
        echo "      {$sessionsDeleted} sessão(ões) deletada(s).\n";

        // 3. Deletar relações com cursos
        echo "    - Deletando relações com cursos...\n";
        $courseDisciplinesDeleted = $db->exec("DELETE FROM theory_course_disciplines WHERE discipline_id = {$discipline['id']}");
        echo "      {$courseDisciplinesDeleted} relação(ões) com curso(s) deletada(s).\n";

        // 4. Registrar auditoria antes de deletar
        echo "    - Registrando auditoria...\n";
        $auditService->logDelete('theory_disciplines', $discipline['id'], $discipline);
        echo "      Auditoria registrada.\n";

        // 5. Deletar a disciplina
        echo "    - Deletando disciplina...\n";
        $db->exec("DELETE FROM theory_disciplines WHERE id = {$discipline['id']}");
        echo "      Disciplina deletada.\n";
    }

    $db->commit();

    echo "\n✓ Exclusão concluída com sucesso!\n";
    echo "  " . count($disciplines) . " disciplina(s) foram excluídas do banco de dados.\n";

} catch (Exception $e) {
    $db->rollBack();
    echo "\n✗ Erro ao excluir disciplinas: " . $e->getMessage() . "\n";
    echo "  Operação revertida.\n";
    exit(1);
}
