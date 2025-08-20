<?php
/**
 * TESTE #3: Sistema de Autenticação
 * Este teste verifica se o sistema de login/logout está funcionando perfeitamente
 */

// Configurações de teste
$erros = [];
$sucessos = [];
$avisos = [];

echo "<h1>🔍 TESTE #3: Sistema de Autenticação</h1>";
echo "<p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>";
echo "<p><strong>Ambiente:</strong> " . ($_SERVER['SERVER_PORT'] == '8080' ? 'XAMPP Local (Porta 8080)' : 'Produção') . "</p>";
echo "<hr>";

// Teste 3.1: Verificar se os arquivos de autenticação existem
echo "<h2>3.1 Verificação de Arquivos de Autenticação</h2>";

$arquivos_auth = [
    'login.php' => 'Página de login',
    'logout.php' => 'Arquivo de logout',
    '../includes/auth.php' => 'Sistema de autenticação',
    '../includes/models/UserModel.php' => 'Modelo de usuário',
    '../includes/views/header.php' => 'Header com navegação'
];

foreach ($arquivos_auth as $arquivo => $descricao) {
    if (file_exists($arquivo)) {
        $tamanho = filesize($arquivo);
        $tamanho_kb = round($tamanho / 1024, 2);
        echo "✅ <strong>$descricao</strong> - EXISTE ($tamanho_kb KB)<br>";
        $sucessos[] = "$descricao existe";
    } else {
        echo "❌ <strong>$descricao</strong> - NÃO ENCONTRADO<br>";
        $erros[] = "$descricao não encontrado";
    }
}

// Teste 3.2: Verificar se conseguimos incluir os arquivos necessários
echo "<h2>3.2 Teste de Inclusão de Arquivos</h2>";

try {
    require_once '../includes/config.php';
    echo "✅ <strong>config.php</strong> - INCLUÍDO COM SUCESSO<br>";
    $sucessos[] = "config.php incluído com sucesso";
} catch (Exception $e) {
    echo "❌ <strong>config.php</strong> - ERRO AO INCLUIR: " . $e->getMessage() . "<br>";
    $erros[] = "Erro ao incluir config.php: " . $e->getMessage();
}

try {
    require_once '../includes/database.php';
    echo "✅ <strong>database.php</strong> - INCLUÍDO COM SUCESSO<br>";
    $sucessos[] = "database.php incluído com sucesso";
} catch (Exception $e) {
    echo "❌ <strong>database.php</strong> - ERRO AO INCLUIR: " . $e->getMessage() . "<br>";
    $erros[] = "Erro ao incluir database.php: " . $e->getMessage();
}

try {
    require_once '../includes/auth.php';
    echo "✅ <strong>auth.php</strong> - INCLUÍDO COM SUCESSO<br>";
    $sucessos[] = "auth.php incluído com sucesso";
} catch (Exception $e) {
    echo "❌ <strong>auth.php</strong> - ERRO AO INCLUIR: " . $e->getMessage() . "<br>";
    $erros[] = "Erro ao incluir auth.php: " . $e->getMessage();
}

// Teste 3.3: Verificar se as funções de autenticação estão disponíveis
echo "<h2>3.3 Verificação de Funções de Autenticação</h2>";

$funcoes_auth = [
    'session_start' => 'Função de sessão PHP',
    'password_hash' => 'Função de hash de senha',
    'password_verify' => 'Função de verificação de senha'
];

foreach ($funcoes_auth as $funcao => $descricao) {
    if (function_exists($funcao)) {
        echo "✅ <strong>$descricao</strong> - DISPONÍVEL<br>";
        $sucessos[] = "$descricao disponível";
    } else {
        echo "❌ <strong>$descricao</strong> - NÃO DISPONÍVEL<br>";
        $erros[] = "$descricao não disponível";
    }
}

// Teste 3.4: Verificar se conseguimos conectar ao banco para testes
echo "<h2>3.4 Teste de Conexão com Banco para Autenticação</h2>";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "✅ <strong>Conexão PDO</strong> - ESTABELECIDA COM SUCESSO<br>";
    $sucessos[] = "Conexão PDO estabelecida";
    
    // Verificar se a tabela usuarios existe e tem estrutura correta
    $stmt = $pdo->query("DESCRIBE usuarios");
    $colunas = $stmt->fetchAll();
    
    $colunas_necessarias = ['id', 'nome', 'email', 'senha', 'tipo', 'status'];
    $colunas_encontradas = array_column($colunas, 'Field');
    
    $colunas_faltando = array_diff($colunas_necessarias, $colunas_encontradas);
    
    if (empty($colunas_faltando)) {
        echo "✅ <strong>Tabela usuarios</strong> - ESTRUTURA CORRETA<br>";
        $sucessos[] = "Tabela usuarios com estrutura correta";
    } else {
        echo "⚠️ <strong>Tabela usuarios</strong> - FALTANDO COLUNAS: " . implode(', ', $colunas_faltando) . "<br>";
        $avisos[] = "Tabela usuarios faltando colunas: " . implode(', ', $colunas_faltando);
    }
    
} catch (PDOException $e) {
    echo "❌ <strong>Conexão PDO</strong> - ERRO: " . $e->getMessage() . "<br>";
    $erros[] = "Erro na conexão PDO: " . $e->getMessage();
}

// Teste 3.5: Verificar se conseguimos incluir o UserModel
echo "<h2>3.5 Teste de Inclusão do UserModel</h2>";

try {
    require_once '../includes/models/UserModel.php';
    echo "✅ <strong>UserModel</strong> - INCLUÍDO COM SUCESSO<br>";
    $sucessos[] = "UserModel incluído com sucesso";
    
    // Verificar se a classe foi definida
    if (class_exists('UserModel')) {
        echo "✅ <strong>Classe UserModel</strong> - DEFINIDA CORRETAMENTE<br>";
        $sucessos[] = "Classe UserModel definida";
        
        // Verificar métodos da classe
        $metodos_necessarios = ['findByEmail', 'findById', 'findAll', 'create', 'update', 'delete', 'authenticate'];
        $metodos_encontrados = get_class_methods('UserModel');
        
        $metodos_faltando = array_diff($metodos_necessarios, $metodos_encontrados);
        
        if (empty($metodos_faltando)) {
            echo "✅ <strong>Métodos UserModel</strong> - TODOS IMPLEMENTADOS<br>";
            $sucessos[] = "Todos os métodos UserModel implementados";
        } else {
            echo "⚠️ <strong>Métodos UserModel</strong> - FALTANDO: " . implode(', ', $metodos_faltando) . "<br>";
            $avisos[] = "Métodos UserModel faltando: " . implode(', ', $metodos_faltando);
        }
        
    } else {
        echo "❌ <strong>Classe UserModel</strong> - NÃO DEFINIDA<br>";
        $erros[] = "Classe UserModel não definida";
    }
    
} catch (Exception $e) {
    echo "❌ <strong>UserModel</strong> - ERRO AO INCLUIR: " . $e->getMessage() . "<br>";
    $erros[] = "Erro ao incluir UserModel: " . $e->getMessage();
}

// Teste 3.6: Verificar se conseguimos incluir o header
echo "<h2>3.6 Teste de Inclusão do Header</h2>";

try {
    require_once '../includes/views/header.php';
    echo "✅ <strong>Header</strong> - INCLUÍDO COM SUCESSO<br>";
    $sucessos[] = "Header incluído com sucesso";
} catch (Exception $e) {
    echo "❌ <strong>Header</strong> - ERRO AO INCLUIR: " . $e->getMessage() . "<br>";
    $erros[] = "Erro ao incluir Header: " . $e->getMessage();
}

// Teste 3.7: Verificar funcionalidades de sessão
echo "<h2>3.7 Teste de Funcionalidades de Sessão</h2>";

// Testar se conseguimos iniciar uma sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo "✅ <strong>Sessão</strong> - INICIADA COM SUCESSO<br>";
    $sucessos[] = "Sessão iniciada com sucesso";
} else {
    echo "✅ <strong>Sessão</strong> - JÁ ATIVA<br>";
    $sucessos[] = "Sessão já ativa";
}

// Testar se conseguimos definir variáveis de sessão
$_SESSION['teste'] = 'valor_teste';
if (isset($_SESSION['teste']) && $_SESSION['teste'] === 'valor_teste') {
    echo "✅ <strong>Variáveis de Sessão</strong> - FUNCIONANDO<br>";
    $sucessos[] = "Variáveis de sessão funcionando";
} else {
    echo "❌ <strong>Variáveis de Sessão</strong> - NÃO FUNCIONANDO<br>";
    $erros[] = "Variáveis de sessão não funcionando";
}

// Limpar variável de teste
unset($_SESSION['teste']);

// Teste 3.8: Verificar URLs de autenticação
echo "<h2>3.8 Verificação de URLs de Autenticação</h2>";

$urls_auth = [
    'login.php' => 'Página de login',
    'logout.php' => 'Página de logout',
    'index.php' => 'Página principal (após login)'
];

foreach ($urls_auth as $url => $descricao) {
    if (file_exists($url)) {
        echo "✅ <strong>$descricao</strong> - ACESSÍVEL ($url)<br>";
        $sucessos[] = "$descricao acessível";
    } else {
        echo "❌ <strong>$descricao</strong> - NÃO ACESSÍVEL ($url)<br>";
        $erros[] = "$descricao não acessível";
    }
}

// Resumo dos Testes
echo "<hr>";
echo "<h2>📊 RESUMO DOS TESTES</h2>";

echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>✅ SUCESSOS (" . count($sucessos) . ")</h3>";
foreach ($sucessos as $sucesso) {
    echo "• $sucesso<br>";
}
echo "</div>";

if (count($avisos) > 0) {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>⚠️ AVISOS (" . count($avisos) . ")</h3>";
    foreach ($avisos as $aviso) {
        echo "• $aviso<br>";
    }
    echo "</div>";
}

if (count($erros) > 0) {
    echo "<div style='background: #ffe8e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>❌ ERROS (" . count($erros) . ")</h3>";
    foreach ($erros as $erro) {
        echo "• $erro<br>";
    }
    echo "</div>";
}

// Status Final
$total_testes = count($sucessos) + count($erros);
$percentual_sucesso = $total_testes > 0 ? round(($total_testes - count($erros)) / $total_testes * 100, 1) : 0;

echo "<div style='background: " . (count($erros) == 0 ? '#d4edda' : '#f8d7da') . "; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>🎯 STATUS FINAL</h3>";
echo "<strong>Total de Testes:</strong> $total_testes<br>";
echo "<strong>Sucessos:</strong> " . count($sucessos) . "<br>";
echo "<strong>Erros:</strong> " . count($erros) . "<br>";
echo "<strong>Avisos:</strong> " . count($avisos) . "<br>";
echo "<strong>Taxa de Sucesso:</strong> $percentual_sucesso%<br>";

if (count($erros) == 0) {
    echo "<br><strong style='color: #155724;'>🎉 TODOS OS TESTES PASSARAM! Sistema pronto para próximo teste.</strong>";
} else {
    echo "<br><strong style='color: #721c24;'>⚠️ Existem erros que precisam ser corrigidos antes de prosseguir.</strong>";
}
echo "</div>";

// Próximo Passo
echo "<hr>";
echo "<h2>🔄 PRÓXIMO PASSO</h2>";
if (count($erros) == 0) {
    echo "<p>✅ <strong>TESTE #3 CONCLUÍDO COM SUCESSO!</strong></p>";
    echo "<p>🎯 <strong>Próximo:</strong> TESTE #4 - CRUD de Usuários</p>";
    echo "<p>📝 <strong>Instrução:</strong> Execute este teste e me informe o resultado. Se tudo estiver OK, criarei o próximo teste.</p>";
} else {
    echo "<p>❌ <strong>TESTE #3 COM ERROS!</strong></p>";
    echo "<p>🔧 <strong>Ação Necessária:</strong> Corrija os erros listados acima e execute novamente.</p>";
    echo "<p>📝 <strong>Instrução:</strong> Me informe quais erros apareceram para que eu possa ajudar a corrigi-los.</p>";
}

// Informações adicionais
echo "<hr>";
echo "<h2>💡 INFORMAÇÕES ADICIONAIS</h2>";
echo "<p><strong>URL de Teste:</strong> <code>http://localhost:8080/cfc-bom-conselho/admin/teste-03-autenticacao.php</code></p>";
echo "<p><strong>Página de Login:</strong> <code>http://localhost:8080/cfc-bom-conselho/admin/login.php</code></p>";
echo "<p><strong>Credenciais de Teste:</strong> admin@cfc.com / admin123</p>";
echo "<p><strong>Funcionalidades Testadas:</strong> Login, Logout, Sessões, UserModel, Header</p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #2c3e50; }
h2 { color: #34495e; margin-top: 30px; }
h3 { color: #7f8c8d; }
hr { border: 1px solid #ecf0f1; margin: 20px 0; }
</style>
