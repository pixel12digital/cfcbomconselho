<?php
// Teste de exclusão de CFC
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Teste de Exclusão de CFC</h1>";

// Incluir arquivos necessários
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

try {
    $db = Database::getInstance();
    echo "<p>✅ Conexão com banco estabelecida</p>";
    
    // Verificar se CFC existe
    $cfc = $db->fetch("SELECT * FROM cfcs WHERE id = 25");
    if ($cfc) {
        echo "<p>✅ CFC encontrado: {$cfc['nome']} (ID: {$cfc['id']})</p>";
        
        // Verificar registros vinculados
        $instrutores = $db->count('instrutores', 'cfc_id = ?', [25]);
        $alunos = $db->count('alunos', 'cfc_id = ?', [25]);
        $veiculos = $db->count('veiculos', 'cfc_id = ?', [25]);
        $aulas = $db->count('aulas', 'cfc_id = ?', [25]);
        
        echo "<p>📊 Registros vinculados:</p>";
        echo "<ul>";
        echo "<li>Instrutores: {$instrutores}</li>";
        echo "<li>Alunos: {$alunos}</li>";
        echo "<li>Veículos: {$veiculos}</li>";
        echo "<li>Aulas: {$aulas}</li>";
        echo "</ul>";
        
        if ($instrutores > 0 || $alunos > 0 || $veiculos > 0 || $aulas > 0) {
            echo "<p>⚠️ Não é possível excluir CFC com registros vinculados</p>";
        } else {
            echo "<p>✅ CFC pode ser excluído (sem registros vinculados)</p>";
            
            // Tentar exclusão
            echo "<p>🔄 Tentando excluir CFC...</p>";
            $result = $db->delete('cfcs', 'id = ?', [25]);
            
            if ($result) {
                echo "<p>✅ CFC excluído com sucesso!</p>";
            } else {
                echo "<p>❌ Erro ao excluir CFC</p>";
            }
        }
        
    } else {
        echo "<p>❌ CFC com ID 25 não encontrado</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
    echo "<p>Arquivo: " . $e->getFile() . "</p>";
    echo "<p>Linha: " . $e->getLine() . "</p>";
}
?>
