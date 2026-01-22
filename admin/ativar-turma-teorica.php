<?php
/**
 * Script para Ativar Turma Teórica
 * Valida completude e ativa a turma para uso
 */

// Verificar autenticação
session_start();
if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'instrutor'])) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/includes/TurmaTeoricaManager.php';

$turmaManager = new TurmaTeoricaManager();
$turmaId = $_GET['turma_id'] ?? null;

if (!$turmaId) {
    echo '<script>alert("ID da turma é obrigatório."); history.back();</script>';
    exit();
}

// Obter dados da turma
$turma = $turmaManager->obterTurma($turmaId);
if (!$turma) {
    echo '<script>alert("Turma não encontrada."); history.back();</script>';
    exit();
}

// Verificar se turma está pronta para ativação
if ($turma['status'] !== 'completa') {
    echo '<script>alert("A turma deve estar completa antes de ser ativada."); history.back();</script>';
    exit();
}

// Verificar completude das disciplinas
$completude = $turmaManager->verificarTurmaCompleta($turmaId);
if (!$completude['completa']) {
    echo '<script>alert("Todas as disciplinas devem estar agendadas antes de ativar a turma."); history.back();</script>';
    exit();
}

try {
    $db = Database::getInstance();
    
    // Ativar turma
    $db->update('turmas_teoricas', [
        'status' => 'ativa'
    ], 'id = ?', [$turmaId]);
    
    // Log da ativação
    $db->insert('turma_log', [
        'turma_id' => $turmaId,
        'acao' => 'ativada',
        'descricao' => 'Turma ativada e liberada para aulas',
        'dados_novos' => json_encode(['status' => 'ativa']),
        'usuario_id' => $_SESSION['user_id'],
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
    ]);
    
    // Redirecionar com sucesso
    header('Location: ?page=turmas-teoricas&acao=detalhes&turma_id=' . $turmaId . '&ativada=1');
    exit();
    
} catch (Exception $e) {
    echo '<script>alert("Erro ao ativar turma: ' . $e->getMessage() . '"); history.back();</script>';
    exit();
}
?>
