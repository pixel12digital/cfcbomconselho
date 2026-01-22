<?php
/**
 * Script para excluir curso teórico e todos os dados relacionados
 * Uso: php tools/excluir_curso.php [nome|id]
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

// Obter parâmetro (nome ou ID)
$identifier = $argv[1] ?? null;

if (!$identifier) {
    echo "Uso: php tools/excluir_curso.php [nome|id]\n";
    echo "Exemplo: php tools/excluir_curso.php \"Primeira Habilitação AB\"\n";
    echo "Exemplo: php tools/excluir_curso.php 1\n";
    exit(1);
}

// Buscar curso
$course = null;
if (is_numeric($identifier)) {
    // Buscar por ID
    $stmt = $db->prepare("SELECT * FROM theory_courses WHERE id = ?");
    $stmt->execute([$identifier]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    // Buscar por nome
    $stmt = $db->prepare("SELECT * FROM theory_courses WHERE name LIKE ?");
    $stmt->execute(["%{$identifier}%"]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$course) {
    echo "Curso não encontrado!\n";
    exit(1);
}

echo "Curso encontrado:\n";
echo "  ID: {$course['id']}\n";
echo "  Nome: {$course['name']}\n";
echo "  CFC ID: {$course['cfc_id']}\n";
echo "  Status: " . ($course['active'] ? 'Ativo' : 'Inativo') . "\n";
echo "\n";

// Confirmar exclusão
echo "ATENÇÃO: Esta ação irá excluir:\n";
echo "  - O curso\n";
echo "  - Todas as turmas relacionadas (theory_classes)\n";
echo "  - Todas as sessões teóricas relacionadas (theory_sessions)\n";
echo "  - Todas as presenças relacionadas (theory_attendance)\n";
echo "  - Todas as matrículas nas turmas (theory_enrollments)\n";
echo "  - Todas as relações com disciplinas (theory_course_disciplines)\n";
echo "  - Referências em matrículas serão removidas (theory_course_id = NULL)\n";
echo "\n";

// Contar registros relacionados
$classesCount = $db->query("SELECT COUNT(*) as count FROM theory_classes WHERE course_id = {$course['id']}")->fetch(PDO::FETCH_ASSOC)['count'];
$sessionsCount = $db->query("SELECT COUNT(*) as count FROM theory_sessions ts INNER JOIN theory_classes tc ON ts.class_id = tc.id WHERE tc.course_id = {$course['id']}")->fetch(PDO::FETCH_ASSOC)['count'];
$enrollmentsCount = $db->query("SELECT COUNT(*) as count FROM theory_enrollments te INNER JOIN theory_classes tc ON te.class_id = tc.id WHERE tc.course_id = {$course['id']}")->fetch(PDO::FETCH_ASSOC)['count'];
$attendanceCount = $db->query("SELECT COUNT(*) as count FROM theory_attendance ta INNER JOIN theory_sessions ts ON ta.session_id = ts.id INNER JOIN theory_classes tc ON ts.class_id = tc.id WHERE tc.course_id = {$course['id']}")->fetch(PDO::FETCH_ASSOC)['count'];
$courseDisciplinesCount = $db->query("SELECT COUNT(*) as count FROM theory_course_disciplines WHERE course_id = {$course['id']}")->fetch(PDO::FETCH_ASSOC)['count'];
$enrollmentsWithCourseCount = $db->query("SELECT COUNT(*) as count FROM enrollments WHERE theory_course_id = {$course['id']}")->fetch(PDO::FETCH_ASSOC)['count'];

echo "Registros relacionados encontrados:\n";
echo "  - Turmas: {$classesCount}\n";
echo "  - Sessões teóricas: {$sessionsCount}\n";
echo "  - Matrículas nas turmas: {$enrollmentsCount}\n";
echo "  - Presenças: {$attendanceCount}\n";
echo "  - Relações com disciplinas: {$courseDisciplinesCount}\n";
echo "  - Matrículas referenciando o curso: {$enrollmentsWithCourseCount}\n";
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

    // 1. Deletar todas as turmas relacionadas
    echo "  - Deletando turmas e dados relacionados...\n";
    $classes = $db->query("SELECT id FROM theory_classes WHERE course_id = {$course['id']}")->fetchAll(PDO::FETCH_ASSOC);
    $classesDeleted = 0;
    $sessionsDeleted = 0;
    $enrollmentsDeleted = 0;
    $attendanceDeleted = 0;
    
    foreach ($classes as $class) {
        // Deletar presenças (através das sessões)
        $sessions = $db->query("SELECT id FROM theory_sessions WHERE class_id = {$class['id']}")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($sessions as $session) {
            $count = $db->exec("DELETE FROM theory_attendance WHERE session_id = {$session['id']}");
            $attendanceDeleted += $count;
        }
        
        // Deletar sessões
        $count = $db->exec("DELETE FROM theory_sessions WHERE class_id = {$class['id']}");
        $sessionsDeleted += $count;
        
        // Deletar matrículas
        $count = $db->exec("DELETE FROM theory_enrollments WHERE class_id = {$class['id']}");
        $enrollmentsDeleted += $count;
        
        // Deletar turma
        $db->exec("DELETE FROM theory_classes WHERE id = {$class['id']}");
        $classesDeleted++;
    }
    
    echo "    {$classesDeleted} turma(s) deletada(s).\n";
    echo "    {$sessionsDeleted} sessão(ões) deletada(s).\n";
    echo "    {$enrollmentsDeleted} matrícula(s) deletada(s).\n";
    echo "    {$attendanceDeleted} presença(s) deletada(s).\n";

    // 2. Deletar relações com disciplinas
    echo "  - Deletando relações com disciplinas...\n";
    $courseDisciplinesDeleted = $db->exec("DELETE FROM theory_course_disciplines WHERE course_id = {$course['id']}");
    echo "    {$courseDisciplinesDeleted} relação(ões) com disciplina(s) deletada(s).\n";

    // 3. Atualizar matrículas para remover referência ao curso
    echo "  - Atualizando matrículas...\n";
    $enrollmentsUpdated = $db->exec("UPDATE enrollments SET theory_course_id = NULL WHERE theory_course_id = {$course['id']}");
    echo "    {$enrollmentsUpdated} matrícula(s) atualizada(s).\n";

    // 4. Registrar auditoria
    echo "  - Registrando auditoria...\n";
    $auditService = new AuditService();
    $auditService->logDelete('theory_courses', $course['id'], $course);
    echo "    Auditoria registrada.\n";

    // 5. Deletar o curso
    echo "  - Deletando curso...\n";
    $db->exec("DELETE FROM theory_courses WHERE id = {$course['id']}");
    echo "    Curso deletado.\n";

    $db->commit();

    echo "\n✓ Exclusão concluída com sucesso!\n";
    echo "  Curso '{$course['name']}' (ID: {$course['id']}) foi excluído do banco de dados.\n";

} catch (Exception $e) {
    $db->rollBack();
    echo "\n✗ Erro ao excluir curso: " . $e->getMessage() . "\n";
    echo "  Operação revertida.\n";
    exit(1);
}
