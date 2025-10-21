<?php
/**
 * API para carregar disciplinas automaticamente baseadas no tipo de curso selecionado
 * Substitui a seleção manual de disciplinas no cadastro de turmas
 */

// Desabilitar exibição de erros para evitar interferência no JSON
ini_set('display_errors', 0);
error_reporting(0);

// Limpar qualquer saída anterior
while (ob_get_level()) {
    ob_end_clean();
}

// Configurações básicas
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar método da requisição
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Iniciar buffer de saída
ob_start();

// Configurações diretas do banco (copiadas do config.php)
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

try {
    // Conectar ao banco
    $pdo = conectarBanco();
    if (!$pdo) {
        throw new Exception('Erro ao conectar com o banco de dados');
    }
    
    // Obter ação da requisição
    $acao = $_GET['acao'] ?? $_POST['acao'] ?? '';
    
    switch ($acao) {
        case 'carregar_disciplinas':
            $cursoTipo = $_GET['curso_tipo'] ?? $_POST['curso_tipo'] ?? '';
            
            if (empty($cursoTipo)) {
                throw new Exception('Tipo de curso não especificado');
            }
            
            // Carregar disciplinas automaticamente do banco
            $stmt = $pdo->prepare("
                SELECT 
                    disciplina,
                    nome_disciplina,
                    aulas_obrigatorias,
                    ordem,
                    cor_hex,
                    icone
                 FROM disciplinas_configuracao 
                 WHERE curso_tipo = ? AND ativa = 1
                 ORDER BY ordem
            ");
            $stmt->execute([$cursoTipo]);
            $disciplinas = $stmt->fetchAll();
            
            if (empty($disciplinas)) {
                // Retornar mensagem amigável em vez de erro
                ob_clean();
                echo json_encode([
                    'sucesso' => true,
                    'disciplinas' => [],
                    'total' => 0,
                    'mensagem' => 'Este tipo de curso ainda não possui disciplinas configuradas. Entre em contato com o administrador para configurar as disciplinas.',
                    'sem_disciplinas' => true
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            }
            
            // Formatar resposta para o frontend
            $disciplinasFormatadas = [];
            foreach ($disciplinas as $disciplina) {
                $disciplinasFormatadas[] = [
                    'value' => $disciplina['disciplina'],
                    'text' => $disciplina['nome_disciplina'],
                    'aulas' => $disciplina['aulas_obrigatorias'],
                    'cor' => $disciplina['cor_hex'],
                    'icone' => $disciplina['icone'],
                    'ordem' => $disciplina['ordem']
                ];
            }
            
            // Limpar buffer e retornar JSON
            ob_clean();
            echo json_encode([
                'sucesso' => true,
                'disciplinas' => $disciplinasFormatadas,
                'total' => count($disciplinasFormatadas),
                'mensagem' => 'Disciplinas carregadas automaticamente'
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;
            
        case 'salvar_disciplinas_automaticas':
            $turmaId = $_POST['turma_id'] ?? '';
            $cursoTipo = $_POST['curso_tipo'] ?? '';
            
            if (empty($turmaId) || empty($cursoTipo)) {
                throw new Exception('Turma ID e tipo de curso são obrigatórios');
            }
            
            // Verificar se a tabela turma_disciplinas existe, se não, criar
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS turma_disciplinas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    turma_id INT NOT NULL,
                    disciplina_id VARCHAR(50) NOT NULL,
                    nome_disciplina VARCHAR(100) NOT NULL,
                    carga_horaria_padrao INT NOT NULL,
                    carga_horaria_personalizada INT NOT NULL,
                    ordem INT NOT NULL,
                    cor_hex VARCHAR(7) DEFAULT '#007bff',
                    icone VARCHAR(50) DEFAULT 'book',
                    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    
                    INDEX idx_turma (turma_id),
                    INDEX idx_disciplina (disciplina_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Limpar disciplinas existentes da turma
            $stmt = $pdo->prepare("DELETE FROM turma_disciplinas WHERE turma_id = ?");
            $stmt->execute([$turmaId]);
            
            // Obter disciplinas configuradas para o curso
            $stmt = $pdo->prepare("
                SELECT 
                    disciplina,
                    nome_disciplina,
                    aulas_obrigatorias,
                    ordem,
                    cor_hex,
                    icone
                 FROM disciplinas_configuracao 
                 WHERE curso_tipo = ? AND ativa = 1
                 ORDER BY ordem
            ");
            $stmt->execute([$cursoTipo]);
            $disciplinas = $stmt->fetchAll();
            
            if (empty($disciplinas)) {
                // Retornar mensagem amigável em vez de erro
                ob_clean();
                echo json_encode([
                    'sucesso' => false,
                    'mensagem' => 'Este tipo de curso ainda não possui disciplinas configuradas. Configure as disciplinas antes de criar a turma.',
                    'sem_disciplinas' => true
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            }
            
            // Inserir disciplinas automaticamente
            $stmt = $pdo->prepare("
                INSERT INTO turma_disciplinas 
                (turma_id, disciplina_id, nome_disciplina, carga_horaria_padrao, 
                 carga_horaria_personalizada, ordem, cor_hex, icone, criado_em) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $totalInseridas = 0;
            foreach ($disciplinas as $disciplina) {
                $stmt->execute([
                    $turmaId,
                    $disciplina['disciplina'],
                    $disciplina['nome_disciplina'],
                    $disciplina['aulas_obrigatorias'],
                    $disciplina['aulas_obrigatorias'],
                    $disciplina['ordem'],
                    $disciplina['cor_hex'],
                    $disciplina['icone']
                ]);
                $totalInseridas++;
            }
            
            // Limpar buffer e retornar JSON
            ob_clean();
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Disciplinas carregadas automaticamente com sucesso',
                'total' => $totalInseridas
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;
            
        case 'obter_disciplinas_turma':
            $turmaId = $_GET['turma_id'] ?? '';
            
            if (empty($turmaId)) {
                throw new Exception('ID da turma não especificado');
            }
            
            // Obter disciplinas da turma
            $stmt = $pdo->prepare("
                SELECT 
                    disciplina_id,
                    nome_disciplina,
                    carga_horaria_padrao,
                    ordem,
                    cor_hex
                 FROM turma_disciplinas 
                 WHERE turma_id = ?
                 ORDER BY ordem
            ");
            $stmt->execute([$turmaId]);
            $disciplinas = $stmt->fetchAll();
            
            // Limpar buffer e retornar JSON
            ob_clean();
            echo json_encode([
                'sucesso' => true,
                'disciplinas' => $disciplinas,
                'total' => count($disciplinas)
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;
            
        default:
            throw new Exception('Ação não reconhecida');
    }
    
} catch (Exception $e) {
    // Limpar buffer e retornar erro em JSON
    ob_clean();
    echo json_encode([
        'sucesso' => false,
        'mensagem' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} finally {
    // Garantir que apenas JSON seja enviado
    ob_end_flush();
}
?>
