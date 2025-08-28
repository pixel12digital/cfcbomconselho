<?php
// Criar aluno de teste para testar a exclusão
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Criar Aluno de Teste</h1>";

try {
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    
    $db = Database::getInstance();
    
    // Verificar se há CFCs disponíveis
    $cfcs = $db->query("SELECT * FROM cfcs WHERE ativo = 1 LIMIT 1")->fetchAll();
    
    if (count($cfcs) === 0) {
        echo "<h2>❌ Nenhum CFC ativo encontrado</h2>";
        echo "<p>É necessário ter pelo menos um CFC ativo para criar um aluno.</p>";
        exit;
    }
    
    $cfc = $cfcs[0];
    echo "<h2>✅ CFC encontrado:</h2>";
    echo "<p>ID: {$cfc['id']}, Nome: {$cfc['nome']}</p>";
    
    // Dados do aluno de teste
    $alunoData = [
        'cfc_id' => $cfc['id'],
        'nome' => 'João Silva Teste',
        'cpf' => '12345678901',
        'rg' => '12345678',
        'data_nascimento' => '1990-01-01',
        'telefone' => '11987654321',
        'email' => 'joao.teste@email.com',
        'endereco' => 'Rua Teste, 123',
        'numero' => '123',
        'bairro' => 'Centro',
        'cidade' => 'São Paulo',
        'estado' => 'SP',
        'cep' => '01234-567',
        'categoria_cnh' => 'B',
        'status' => 'ativo',
        'observacoes' => 'Aluno criado para teste de exclusão',
        'criado_em' => date('Y-m-d H:i:s')
    ];
    
    echo "<h2>🔄 Criando aluno de teste...</h2>";
    echo "<p>Nome: {$alunoData['nome']}</p>";
    echo "<p>CPF: {$alunoData['cpf']}</p>";
    echo "<p>CFC: {$cfc['nome']}</p>";
    
    // Inserir aluno
    $alunoId = $db->insert('alunos', $alunoData);
    
    if ($alunoId) {
        echo "<h2>✅ Aluno criado com sucesso!</h2>";
        echo "<p>ID: {$alunoId}</p>";
        
        // Verificar se foi realmente inserido
        $alunoVerificado = $db->query("SELECT * FROM alunos WHERE id = ?", [$alunoId])->fetch();
        
        if ($alunoVerificado) {
            echo "<p>✅ Aluno verificado na base de dados</p>";
            echo "<p>Nome: {$alunoVerificado['nome']}</p>";
            echo "<p>CPF: {$alunoVerificado['cpf']}</p>";
            echo "<p>Status: {$alunoVerificado['status']}</p>";
            
            echo "<h2>🎯 Agora você pode testar a exclusão!</h2>";
            echo "<p>Use o ID: <strong>{$alunoId}</strong> para testar a exclusão no frontend.</p>";
            
        } else {
            echo "<p>❌ Erro: Aluno não foi encontrado após inserção</p>";
        }
        
    } else {
        echo "<h2>❌ Erro ao criar aluno</h2>";
        echo "<p>O método insert retornou false</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Erro:</h2>";
    echo "Mensagem: " . $e->getMessage() . "<br>";
    echo "Arquivo: " . $e->getFile() . "<br>";
    echo "Linha: " . $e->getLine() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><strong>Script concluído em:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
