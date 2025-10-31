<?php
/**
 * Script para limpar turmas de teste do banco de dados
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Verificar autenticação usando o sistema de auth do projeto
if (!isLoggedIn()) {
    die('<h1 style="color: red;">❌ Acesso negado. Faça login primeiro.</h1><p><a href="../login.php">Clique aqui para fazer login</a></p>');
}

// Verificar se é admin - tentar ambas as chaves possíveis da sessão
$userTipo = $_SESSION['user_type'] ?? $_SESSION['tipo'] ?? 'usuario';

echo "<p style='background: #f0f0f0; padding: 10px; margin: 10px 0;'>";
echo "<strong>Debug - Informações da sessão:</strong><br>";
echo "user_type: " . ($_SESSION['user_type'] ?? 'não definido') . "<br>";
echo "tipo: " . ($_SESSION['tipo'] ?? 'não definido') . "<br>";
echo "user_id: " . ($_SESSION['user_id'] ?? 'não definido') . "<br>";
echo "user_name: " . ($_SESSION['user_name'] ?? $_SESSION['user_nome'] ?? 'não definido');
echo "</p>";

if ($userTipo !== 'admin') {
    die('<h1 style="color: red;">❌ Acesso negado. Apenas administradores podem executar esta operação.</h1><p>Seu tipo de usuário: <strong>' . htmlspecialchars($userTipo) . '</strong></p>');
}

$db = db();

header('Content-Type: text/html; charset=utf-8');

echo "<html><head><meta charset='UTF-8'><title>Limpeza de Turmas de Teste</title></head><body>";
echo "<h1>🧹 Limpeza de Turmas de Teste</h1>";

// Buscar turmas com "Teste" no nome
$turmasTeste = $db->fetchAll("
    SELECT id, nome, curso_tipo, data_inicio, data_fim, cfc_id 
    FROM turmas_teoricas 
    WHERE nome LIKE ? 
    ORDER BY id
", ['%Teste%']);

if (empty($turmasTeste)) {
    echo "<p style='color: green; font-size: 18px;'>✅ Nenhuma turma de teste encontrada.</p>";
    exit;
}

echo "<p><strong>📋 Turmas de teste encontradas: " . count($turmasTeste) . "</strong></p>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 20px 0;'>";
echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Nome</th><th>Curso</th><th>Período</th></tr>";

foreach ($turmasTeste as $turma) {
    $periodo = date('d/m/Y', strtotime($turma['data_inicio'])) . ' - ' . date('d/m/Y', strtotime($turma['data_fim']));
    echo "<tr>";
    echo "<td>{$turma['id']}</td>";
    echo "<td>{$turma['nome']}</td>";
    echo "<td>{$turma['curso_tipo']}</td>";
    echo "<td>{$periodo}</td>";
    echo "</tr>";
}
echo "</table>";

if (isset($_POST['confirmar']) && $_POST['confirmar'] === 'sim') {
    echo "<hr><h2>🗑️ Iniciando exclusão...</h2>";
    
    $totalExcluidas = 0;
    $totalAulasExcluidas = 0;
    $totalAlunosExcluidas = 0;
    
    foreach ($turmasTeste as $turma) {
        echo "<div style='border: 1px solid #ccc; padding: 15px; margin: 10px 0; background: #fff3cd;'>";
        echo "<h3>🔍 Processando: {$turma['nome']} (ID: {$turma['id']})</h3>";
        
        // Contar dependências
        $totalAulas = $db->fetch("SELECT COUNT(*) as total FROM turma_aulas_agendadas WHERE turma_id = ?", [$turma['id']]);
        $totalAlunos = $db->fetch("SELECT COUNT(*) as total FROM turma_alunos WHERE turma_id = ?", [$turma['id']]);
        
        echo "<p>📊 Aulas agendadas: <strong>{$totalAulas['total']}</strong></p>";
        echo "<p>👥 Alunos matriculados: <strong>{$totalAlunos['total']}</strong></p>";
        
        try {
            $db->beginTransaction();
            
            // 1. Excluir aulas agendadas
            $aulasDeleted = $db->delete('turma_aulas_agendadas', 'turma_id = ?', [$turma['id']]);
            echo "<p style='color: green;'>✅ Aulas excluídas: {$totalAulas['total']}</p>";
            $totalAulasExcluidas += $totalAulas['total'];
            
            // 2. Excluir matrículas de alunos
            $alunosDeleted = $db->delete('turma_alunos', 'turma_id = ?', [$turma['id']]);
            echo "<p style='color: green;'>✅ Matrículas excluídas: {$totalAlunos['total']}</p>";
            $totalAlunosExcluidas += $totalAlunos['total'];
            
            // 3. Excluir logs da turma (se existir)
            try {
                $db->delete('turma_logs', 'turma_id = ?', [$turma['id']]);
                echo "<p style='color: green;'>✅ Logs excluídos</p>";
            } catch (Exception $e) {
                echo "<p style='color: gray;'>ℹ️ Logs não existem</p>";
            }
            
            // 4. Excluir a turma
            $turmaDeleted = $db->delete('turmas_teoricas', 'id = ?', [$turma['id']]);
            
            if ($turmaDeleted) {
                $totalExcluidas++;
                echo "<p style='color: green; font-weight: bold;'>✅✅✅ Turma '{$turma['nome']}' removida com sucesso!</p>";
            } else {
                throw new Exception("Não foi possível excluir a turma");
            }
            
            $db->commit();
            
        } catch (Exception $e) {
            $db->rollback();
            echo "<p style='color: red;'>❌ Erro ao excluir turma: {$e->getMessage()}</p>";
        }
        
        echo "</div>";
    }
    
    echo "<hr>";
    echo "<div style='background: #d4edda; border: 2px solid #28a745; padding: 20px; margin: 20px 0;'>";
    echo "<h2 style='color: #155724;'>🎉 Limpeza concluída com sucesso!</h2>";
    echo "<p><strong>✅ Turmas excluídas:</strong> {$totalExcluidas}</p>";
    echo "<p><strong>✅ Aulas excluídas:</strong> {$totalAulasExcluidas}</p>";
    echo "<p><strong>✅ Matrículas excluídas:</strong> {$totalAlunosExcluidas}</p>";
    echo "</div>";
    
    echo "<p><a href='?page=turmas-teoricas' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>← Voltar para Lista de Turmas</a></p>";
    
} else {
    echo "<hr>";
    echo "<form method='POST' style='background: #fff3cd; border: 2px solid #ffc107; padding: 20px; margin: 20px 0;'>";
    echo "<h3>⚠️ ATENÇÃO!</h3>";
    echo "<p>Esta ação irá <strong>EXCLUIR PERMANENTEMENTE</strong> as turmas listadas acima e todos os dados relacionados.</p>";
    echo "<p><strong>Turmas que serão excluídas:</strong></p>";
    echo "<ul>";
    foreach ($turmasTeste as $turma) {
        echo "<li>{$turma['nome']} (ID: {$turma['id']})</li>";
    }
    echo "</ul>";
    
    echo "<p><input type='hidden' name='confirmar' value='sim'>";
    echo "<button type='submit' style='padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;'>🗑️ CONFIRMAR EXCLUSÃO</button></p>";
    echo "<p><a href='?page=turmas-teoricas' style='color: #666;'>← Cancelar e voltar</a></p>";
    echo "</form>";
}

echo "</body></html>";
?>
