<?php
// Debug simples para identificar erro 500 na API
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug API - Identificar Erro 500</h1>";

try {
    echo "<h2>1. Testando carregamento de includes...</h2>";
    
    // Testar config.php
    echo "Carregando config.php...<br>";
    require_once 'includes/config.php';
    echo "✅ config.php carregado com sucesso<br>";
    
    // Testar database.php
    echo "Carregando database.php...<br>";
    require_once 'includes/database.php';
    echo "✅ database.php carregado com sucesso<br>";
    
    // Testar auth.php
    echo "Carregando auth.php...<br>";
    require_once 'includes/auth.php';
    echo "✅ auth.php carregado com sucesso<br>";
    
    echo "<h2>2. Testando conexão com banco...</h2>";
    
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    if ($connection) {
        echo "✅ Conexão com banco estabelecida<br>";
    } else {
        echo "❌ Falha na conexão com banco<br>";
        exit;
    }
    
    echo "<h2>3. Testando autenticação...</h2>";
    
    // Iniciar sessão se necessário
    if (session_status() === PHP_SESSION_NONE) {
        if (!headers_sent()) {
            session_start();
            echo "✅ Sessão iniciada<br>";
        } else {
            echo "⚠️ Headers já enviados, não foi possível iniciar sessão<br>";
        }
    } else {
        echo "✅ Sessão já ativa<br>";
    }
    
    // Testar função isLoggedIn
    echo "Testando isLoggedIn()...<br>";
    $isLoggedIn = isLoggedIn();
    echo "isLoggedIn(): " . ($isLoggedIn ? 'true' : 'false') . "<br>";
    
    // Testar função hasPermission
    echo "Testando hasPermission('admin')...<br>";
    $hasPermission = hasPermission('admin');
    echo "hasPermission('admin'): " . ($isLoggedIn ? 'true' : 'false') . "<br>";
    
    echo "<h2>4. Testando método DELETE...</h2>";
    
    // Simular dados DELETE
    $_SERVER['REQUEST_METHOD'] = 'DELETE';
    $rawInput = '{"id": 105}';
    
    echo "Método: " . $_SERVER['REQUEST_METHOD'] . "<br>";
    echo "Raw input: " . $rawInput . "<br>";
    
    // Testar leitura do input
    $input = json_decode($rawInput, true);
    if ($input) {
        echo "✅ JSON decodificado com sucesso<br>";
        echo "ID: " . ($input['id'] ?? 'não definido') . "<br>";
    } else {
        echo "❌ Falha ao decodificar JSON<br>";
        echo "Erro: " . json_last_error_msg() . "<br>";
    }
    
    echo "<h2>5. Testando exclusão no banco...</h2>";
    
    $id = $input['id'] ?? 105;
    
    // Verificar se aluno existe
    $aluno = $db->findWhere('alunos', 'id = ?', [$id], '*', null, 1);
    if ($aluno && is_array($aluno)) {
        $aluno = $aluno[0];
        echo "✅ Aluno encontrado: " . $aluno['nome'] . "<br>";
        
        // Verificar se há aulas vinculadas
        $aulasVinculadas = $db->count('aulas', 'aluno_id = ?', [$id]);
        echo "Aulas vinculadas: " . $aulasVinculadas . "<br>";
        
        if ($aulasVinculadas > 0) {
            echo "⚠️ Não é possível excluir aluno com aulas vinculadas<br>";
        } else {
            echo "✅ Aluno pode ser excluído<br>";
            
            // Testar exclusão
            try {
                $resultado = $db->delete('alunos', 'id = ?', [$id]);
                if ($resultado) {
                    echo "✅ Exclusão executada com sucesso<br>";
                    
                    // Verificar se foi realmente excluído
                    $alunoVerificado = $db->findWhere('alunos', 'id = ?', [$id], '*', null, 1);
                    if (!$alunoVerificado) {
                        echo "✅ Aluno foi realmente excluído<br>";
                        
                        // Reinserir para não perder dados
                        echo "🔄 Reinserindo aluno...<br>";
                        $db->insert('alunos', [
                            'id' => $id,
                            'nome' => $aluno['nome'],
                            'cpf' => $aluno['cpf'],
                            'rg' => $aluno['rg'] ?? '',
                            'data_nascimento' => $aluno['data_nascimento'] ?? null,
                            'endereco' => $aluno['endereco'] ?? '',
                            'telefone' => $aluno['telefone'] ?? '',
                            'email' => $aluno['email'] ?? '',
                            'cfc_id' => $aluno['cfc_id'],
                            'categoria_cnh' => $aluno['categoria_cnh'] ?? 'B',
                            'status' => $aluno['status'] ?? 'ativo',
                            'observacoes' => $aluno['observacoes'] ?? '',
                            'criado_em' => date('Y-m-d H:i:s')
                        ]);
                        echo "✅ Aluno reinserido com sucesso<br>";
                    } else {
                        echo "❌ Aluno não foi excluído<br>";
                    }
                } else {
                    echo "❌ Falha na exclusão<br>";
                }
            } catch (Exception $e) {
                echo "❌ Erro na exclusão: " . $e->getMessage() . "<br>";
            }
        }
    } else {
        echo "❌ Aluno não encontrado<br>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Erro geral:</h2>";
    echo "Mensagem: " . $e->getMessage() . "<br>";
    echo "Arquivo: " . $e->getFile() . "<br>";
    echo "Linha: " . $e->getLine() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
} catch (Error $e) {
    echo "<h2>❌ Erro fatal:</h2>";
    echo "Mensagem: " . $e->getMessage() . "<br>";
    echo "Arquivo: " . $e->getFile() . "<br>";
    echo "Linha: " . $e->getLine() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><strong>Debug concluído em:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
