<?php
/**
 * Teste da API de Disciplinas
 * Para verificar se a API está funcionando corretamente
 */

// Configurações diretas do banco (copiadas do disciplinas-clean.php)
define('DB_HOST', 'auth-db803.hstgr.io');
define('DB_NAME', 'u502697186_cfcbomconselho');
define('DB_USER', 'u502697186_cfcbomconselho');
define('DB_PASS', 'Los@ngo#081081');

// Função para conectar ao banco diretamente
function conectarBanco() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        return $pdo;
    } catch (PDOException $e) {
        return null;
    }
}

echo "<h1>🔍 Teste da API de Disciplinas</h1>";

try {
    $db = conectarBanco();
    
    if (!$db) {
        echo "<p style='color: red;'>❌ Erro ao conectar com o banco de dados</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ Conexão com banco estabelecida</p>";
    
    // Verificar se a tabela existe
    $stmt = $db->query("SHOW TABLES LIKE 'disciplinas'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        echo "<p style='color: orange;'>⚠️ Tabela 'disciplinas' não existe</p>";
        
        // Criar tabela
        echo "<p>🔧 Criando tabela disciplinas...</p>";
        $createTable = "
            CREATE TABLE disciplinas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(255) NOT NULL,
                codigo VARCHAR(100) NOT NULL,
                carga_horaria_padrao INT DEFAULT 10,
                descricao TEXT,
                ativa TINYINT(1) DEFAULT 1,
                cfc_id INT DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $db->exec($createTable);
        echo "<p style='color: green;'>✅ Tabela criada com sucesso</p>";
        
        // Inserir disciplinas padrão
        echo "<p>📚 Inserindo disciplinas padrão...</p>";
        $disciplinasPadrao = [
            ['Legislação de Trânsito', 'legislacao_transito', 18, 'Estudo das leis de trânsito e regulamentações'],
            ['Direção Defensiva', 'direcao_defensiva', 16, 'Técnicas de direção defensiva e prevenção de acidentes'],
            ['Meio Ambiente e Cidadania', 'meio_ambiente', 4, 'Consciência ambiental e cidadania no trânsito'],
            ['Primeiros Socorros', 'primeiros_socorros', 4, 'Noções básicas de primeiros socorros'],
            ['Mecânica Básica', 'mecanica_basica', 3, 'Conhecimentos básicos sobre mecânica automotiva']
        ];
        
        $stmt = $db->prepare("INSERT INTO disciplinas (nome, codigo, carga_horaria_padrao, descricao, ativa, cfc_id) VALUES (?, ?, ?, ?, 1, 1)");
        
        foreach ($disciplinasPadrao as $disciplina) {
            $stmt->execute($disciplina);
        }
        
        echo "<p style='color: green;'>✅ Disciplinas padrão inseridas</p>";
        
    } else {
        echo "<p style='color: green;'>✅ Tabela 'disciplinas' existe</p>";
    }
    
    // Verificar quantas disciplinas existem
    $stmt = $db->query("SELECT COUNT(*) as total FROM disciplinas");
    $total = $stmt->fetch()['total'];
    
    echo "<p>📊 Total de disciplinas na tabela: <strong>{$total}</strong></p>";
    
    if ($total > 0) {
        echo "<h3>📚 Disciplinas disponíveis:</h3>";
        echo "<ul>";
        
        $stmt = $db->query("SELECT * FROM disciplinas ORDER BY nome ASC");
        $disciplinas = $stmt->fetchAll();
        
        foreach ($disciplinas as $disciplina) {
            echo "<li><strong>{$disciplina['nome']}</strong> - {$disciplina['carga_horaria_padrao']}h ({$disciplina['codigo']})</li>";
        }
        
        echo "</ul>";
    }
    
    // Testar a API diretamente
    echo "<h3>🧪 Teste da API:</h3>";
    
    $url = 'http://localhost/cfc-bom-conselho/admin/api/disciplinas-clean.php?acao=listar';
    
    echo "<p>🔗 URL da API: <a href='{$url}' target='_blank'>{$url}</a></p>";
    
    // Fazer requisição para a API
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Content-Type: application/json',
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        echo "<p style='color: red;'>❌ Erro ao fazer requisição para a API</p>";
    } else {
        echo "<p style='color: green;'>✅ Resposta da API recebida</p>";
        
        $data = json_decode($response, true);
        
        if ($data) {
            echo "<h4>📋 Resposta da API:</h4>";
            echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
            
            if (isset($data['sucesso']) && $data['sucesso']) {
                echo "<p style='color: green;'>✅ API funcionando corretamente</p>";
                echo "<p>📊 Total de disciplinas retornadas: " . count($data['disciplinas'] ?? []) . "</p>";
            } else {
                echo "<p style='color: red;'>❌ API retornou erro: " . ($data['mensagem'] ?? 'Erro desconhecido') . "</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Resposta da API não é JSON válido</p>";
            echo "<h4>Resposta bruta:</h4>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='turmas-teoricas.php'>← Voltar para Turmas Teóricas</a></p>";
?>
