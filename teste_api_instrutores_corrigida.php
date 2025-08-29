<?php
// =====================================================
// TESTE DA API DE INSTRUTORES CORRIGIDA
// =====================================================

// Habilitar exibição de erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🧪 Teste da API de Instrutores Corrigida</h1>";

// Simular dados de teste
$testData = [
    'nome' => 'João Silva Teste',
    'cpf' => '123.456.789-00',
    'cnh' => '12345678901',
    'data_nascimento' => '1985-05-15',
    'email' => 'joao.teste@exemplo.com',
    'telefone' => '(11) 99999-9999',
    'usuario_id' => '', // Vazio para criar novo usuário
    'cfc_id' => 1, // Ajustar conforme necessário
    'credencial' => 'CRED001',
    'categoria_habilitacao' => 'A,B,C',
    'categorias' => ['A', 'B', 'C'],
    'tipo_carga' => 'perigosa',
    'validade_credencial' => '2025-12-31',
    'observacoes' => 'Instrutor de teste para verificar API corrigida',
    'dias_semana' => ['segunda', 'terca', 'quarta', 'quinta', 'sexta'],
    'horario_inicio' => '08:00:00',
    'horario_fim' => '18:00:00',
    'ativo' => true
];

echo "<h2>📋 Dados de Teste:</h2>";
echo "<pre>" . print_r($testData, true) . "</pre>";

// Fazer requisição POST para a API
echo "<h2>🚀 Testando API POST:</h2>";

// Usar caminho relativo correto
$url = './admin/api/instrutores.php';

// IMPORTANTE: Para testar a API, precisamos simular uma sessão válida
// Vamos usar uma abordagem diferente - incluir a API diretamente
echo "<p>📡 Testando inclusão direta da API...</p>";

// Simular dados POST
$_POST = $testData;
$_SERVER['REQUEST_METHOD'] = 'POST';

// Capturar saída da API
ob_start();
include $url;
$response = ob_get_clean();

echo "<h3>📊 Resposta da API:</h3>";
echo "<p><strong>Método:</strong> Inclusão direta</p>";
echo "<p><strong>Resposta:</strong></p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Tentar decodificar JSON
$data = json_decode($response, true);
if ($data) {
    echo "<h3>✅ Resposta Decodificada:</h3>";
    echo "<pre>" . print_r($data, true) . "</pre>";
    
    if (isset($data['success']) && $data['success']) {
        echo "<p style='color: green;'>🎉 <strong>SUCESSO!</strong> Instrutor criado com sucesso!</p>";
        
        if (isset($data['data'])) {
            echo "<h3>📋 Dados do Instrutor Criado:</h3>";
            echo "<pre>" . print_r($data['data'], true) . "</pre>";
        }
    } else {
        echo "<p style='color: red;'>❌ <strong>ERRO:</strong> " . ($data['error'] ?? 'Erro desconhecido') . "</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ <strong>AVISO:</strong> Resposta não é JSON válido</p>";
}

// Remover código cURL antigo - não é mais necessário

// Verificar estrutura da tabela
echo "<h2>🔍 Verificando Estrutura da Tabela:</h2>";

try {
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    
    $db = Database::getInstance();
    
    // Verificar se a tabela instrutores existe
    $tables = $db->fetchAll("SHOW TABLES LIKE 'instrutores'");
    if (empty($tables)) {
        echo "<p style='color: red;'>❌ Tabela 'instrutores' não encontrada!</p>";
    } else {
        echo "<p style='color: green;'>✅ Tabela 'instrutores' encontrada</p>";
        
        // Verificar estrutura
        $columns = $db->fetchAll("DESCRIBE instrutores");
        echo "<h3>📋 Estrutura da Tabela:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Verificar se há dados
        $count = $db->count('instrutores');
        echo "<p><strong>Total de instrutores na tabela:</strong> $count</p>";
        
        if ($count > 0) {
            $instrutores = $db->fetchAll("SELECT * FROM instrutores ORDER BY id DESC LIMIT 5");
            echo "<h3>📊 Últimos 5 Instrutores:</h3>";
            echo "<pre>" . print_r($instrutores, true) . "</pre>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ <strong>Erro ao conectar com banco:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Teste concluído!</strong> Verifique os resultados acima.</p>";
?>
