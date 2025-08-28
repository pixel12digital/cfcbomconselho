<?php
// Script para remover instrutor vinculado ao CFC 30
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/paths.php';
require_once INCLUDES_PATH . '/config.php';
require_once INCLUDES_PATH . '/database.php';

try {
    $db = Database::getInstance();
    
    echo "<h2>🗑️ Remoção de Instrutor Vinculado ao CFC 30</h2>";
    
    // Verificar se o CFC existe
    $cfc = $db->fetch("SELECT * FROM cfcs WHERE id = 30");
    if (!$cfc) {
        echo "<p style='color: red;'>CFC 30 não encontrado!</p>";
        exit;
    }
    
    echo "<h3>📋 Informações do CFC:</h3>";
    echo "<p><strong>Nome:</strong> {$cfc['nome']}</p>";
    echo "<p><strong>CNPJ:</strong> {$cfc['cnpj']}</p>";
    echo "<p><strong>Cidade:</strong> {$cfc['cidade']}/{$cfc['uf']}</p>";
    
    // Verificar instrutores vinculados
    $instrutores = $db->fetchAll("SELECT * FROM instrutores WHERE cfc_id = 30");
    echo "<h3>👨‍🏫 Instrutores Vinculados:</h3>";
    
    if (empty($instrutores)) {
        echo "<p style='color: green;'>✅ Nenhum instrutor vinculado. O CFC pode ser excluído.</p>";
        exit;
    }
    
    echo "<p>Total de instrutores vinculados: " . count($instrutores) . "</p>";
    
    foreach ($instrutores as $instrutor) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
        echo "<p><strong>ID:</strong> {$instrutor['id']}</p>";
        echo "<p><strong>Nome:</strong> " . ($instrutor['nome'] ?: 'N/A') . "</p>";
        echo "<p><strong>CPF:</strong> {$instrutor['cpf']}</p>";
        echo "<p><strong>CNH:</strong> {$instrutor['cnh']}</p>";
        echo "<p><strong>Telefone:</strong> " . ($instrutor['telefone'] ?: 'N/A') . "</p>";
        echo "<p><strong>Email:</strong> " . ($instrutor['email'] ?: 'N/A') . "</p>";
        echo "<p><strong>Endereço:</strong> " . ($instrutor['endereco'] ?: 'N/A') . "</p>";
        echo "<p><strong>Cidade/UF:</strong> {$instrutor['cidade']}/{$instrutor['uf']}</p>";
        echo "<p><strong>Status:</strong> {$instrutor['status']}</p>";
        echo "</div>";
    }
    
    // Perguntar se deseja remover
    if (isset($_POST['confirmar']) && $_POST['confirmar'] === 'sim') {
        echo "<h3>🔄 Processando remoção...</h3>";
        
        // Iniciar transação
        $db->getConnection()->beginTransaction();
        
        try {
            $totalRemovidos = 0;
            
            foreach ($instrutores as $instrutor) {
                echo "<p>Removendo instrutor ID {$instrutor['id']}...</p>";
                
                // Remover instrutor
                $result = $db->delete('instrutores', 'id = ?', [$instrutor['id']]);
                
                if ($result) {
                    echo "<p style='color: green;'>✅ Instrutor ID {$instrutor['id']} removido com sucesso</p>";
                    $totalRemovidos++;
                } else {
                    echo "<p style='color: red;'>❌ Erro ao remover instrutor ID {$instrutor['id']}</p>";
                    throw new Exception("Falha ao remover instrutor ID {$instrutor['id']}");
                }
            }
            
            // Verificar se ainda há registros vinculados
            $instrutoresRestantes = $db->count('instrutores', 'cfc_id = ?', [30]);
            $alunosRestantes = $db->count('alunos', 'cfc_id = ?', [30]);
            $veiculosRestantes = $db->count('veiculos', 'cfc_id = ?', [30]);
            $aulasRestantes = $db->count('aulas', 'cfc_id = ?', [30]);
            
            echo "<h3>📊 Status após remoção:</h3>";
            echo "<p>Instrutores restantes: {$instrutoresRestantes}</p>";
            echo "<p>Alunos restantes: {$alunosRestantes}</p>";
            echo "<p>Veículos restantes: {$veiculosRestantes}</p>";
            echo "<p>Aulas restantes: {$aulasRestantes}</p>";
            
            if ($instrutoresRestantes == 0 && $alunosRestantes == 0 && 
                $veiculosRestantes == 0 && $aulasRestantes == 0) {
                echo "<p style='color: green;'>✅ Agora o CFC 30 pode ser excluído!</p>";
                echo "<p>Você pode ir até a interface administrativa e excluir o CFC normalmente.</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ Ainda há registros vinculados. Verifique antes de excluir o CFC.</p>";
            }
            
            // Commit da transação
            $db->getConnection()->commit();
            echo "<p style='color: green;'>✅ Transação concluída com sucesso!</p>";
            
        } catch (Exception $e) {
            // Rollback em caso de erro
            $db->getConnection()->rollBack();
            echo "<p style='color: red;'>❌ Erro durante a remoção: " . $e->getMessage() . "</p>";
            echo "<p>Rollback realizado. Nenhuma alteração foi feita no banco.</p>";
        }
        
    } else {
        // Formulário de confirmação
        echo "<h3>⚠️ Confirmação Necessária</h3>";
        echo "<p>Para excluir o CFC 30, é necessário remover primeiro os instrutores vinculados.</p>";
        echo "<p><strong>ATENÇÃO:</strong> Esta ação é irreversível!</p>";
        
        echo "<form method='post' style='margin: 20px 0;'>";
        echo "<p><strong>Deseja remover todos os instrutores vinculados ao CFC 30?</strong></p>";
        echo "<input type='hidden' name='confirmar' value='sim'>";
        echo "<button type='submit' style='background-color: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
        echo "Sim, remover instrutores";
        echo "</button>";
        echo "</form>";
        
        echo "<p><a href='admin/index.php?page=cfcs&action=list' style='color: #007bff;'>← Voltar para lista de CFCs</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
    echo "<p>Arquivo: " . $e->getFile() . "</p>";
    echo "<p>Linha: " . $e->getLine() . "</p>";
}
?>
