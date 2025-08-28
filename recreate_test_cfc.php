<?php
// Recriar CFC de teste
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Recriando CFC de Teste</h1>";

require_once 'includes/config.php';
require_once 'includes/database.php';

try {
    $db = Database::getInstance();
    echo "<p>✅ Conexão com banco estabelecida</p>";
    
    // Verificar se já existe um CFC com o mesmo CNPJ
    $existingCFC = $db->fetch("SELECT * FROM cfcs WHERE cnpj = '12345678000197'");
    if ($existingCFC) {
        echo "<p>⚠️ CFC com CNPJ 12345678000197 já existe (ID: {$existingCFC['id']})</p>";
    } else {
        // Inserir novo CFC de teste
        $cfcData = [
            'nome' => 'CFC Teste Form',
            'cnpj' => '12345678000197',
            'razao_social' => 'CFC Teste Form Ltda',
            'endereco' => 'Rua Teste Form, 123',
            'bairro' => 'Centro',
            'cidade' => 'São Paulo',
            'uf' => 'SP',
            'cep' => '01234-567',
            'telefone' => '(11) 99999-9999',
            'email' => 'teste@cfc.com',
            'responsavel_id' => null,
            'ativo' => 1,
            'observacoes' => 'CFC criado para testes'
        ];
        
        $cfcId = $db->insert('cfcs', $cfcData);
        
        if ($cfcId) {
            echo "<p>✅ CFC criado com sucesso! ID: {$cfcId}</p>";
            echo "<p>📋 Dados do CFC:</p>";
            echo "<ul>";
            echo "<li><strong>Nome:</strong> {$cfcData['nome']}</li>";
            echo "<li><strong>CNPJ:</strong> {$cfcData['cnpj']}</li>";
            echo "<li><strong>Cidade/UF:</strong> {$cfcData['cidade']}/{$cfcData['uf']}</li>";
            echo "<li><strong>Telefone:</strong> {$cfcData['telefone']}</li>";
            echo "<li><strong>Email:</strong> {$cfcData['email']}</li>";
            echo "</ul>";
        } else {
            echo "<p>❌ Erro ao criar CFC</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
}
?>

