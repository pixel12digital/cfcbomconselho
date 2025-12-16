<?php
// API completamente limpa para salas
// Sem dependências que possam gerar HTML

// Credenciais removidas - usar includes/config.php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';

// Headers primeiro
header('Content-Type: application/json; charset=utf-8');
// Cache curto para reduzir conexões (mitigação max_connections_per_hour)
header('Cache-Control: private, max-age=60, must-revalidate');

// Desabilitar qualquer output de erro
ini_set('display_errors', 0);
error_reporting(0);

// Função para conectar ao banco usando Database::getInstance()
function conectarBanco() {
    try {
        $db = Database::getInstance();
        return $db->getConnection();
    } catch (Exception $e) {
        return null;
    }
}

// Função para listar salas
function listarSalas($pdo) {
    try {
        // Verificar se tabela existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'salas'");
        if ($stmt->rowCount() == 0) {
            // Criar tabela
            $pdo->exec("
                CREATE TABLE salas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nome VARCHAR(100) NOT NULL,
                    capacidade INT NOT NULL DEFAULT 30,
                    equipamentos JSON DEFAULT NULL,
                    ativa BOOLEAN DEFAULT TRUE,
                    cfc_id INT NOT NULL,
                    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            // Inserir salas padrão
            $pdo->exec("
                INSERT INTO salas (nome, capacidade, equipamentos, ativa, cfc_id) VALUES 
                ('Sala 1', 30, '{\"projetor\": true, \"ar_condicionado\": true, \"quadro\": true}', 1, 1),
                ('Sala 2', 25, '{\"projetor\": true, \"ar_condicionado\": false, \"quadro\": true}', 1, 1)
            ");
        }
        
        // Buscar salas
        $stmt = $pdo->prepare("SELECT * FROM salas WHERE cfc_id = ? ORDER BY nome ASC");
        $stmt->execute([1]);
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        return [];
    }
}

// Função para gerar HTML das salas
function gerarHtmlSalas($salas) {
    if (empty($salas)) {
        return '<div class="text-center py-3">
            <i class="fas fa-door-open fa-2x text-muted mb-2"></i>
            <p class="text-muted">Nenhuma sala cadastrada</p>
        </div>';
    }
    
    $html = '';
    foreach ($salas as $sala) {
        $statusBadge = $sala['ativa'] ? '<span class="badge bg-success">Ativa</span>' : '<span class="badge bg-secondary">Inativa</span>';
        
        $html .= '<div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-door-open me-2"></i>' . htmlspecialchars($sala['nome']) . '</h6>
                    ' . $statusBadge . '
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong><i class="fas fa-users me-1"></i>Capacidade:</strong> ' . $sala['capacidade'] . ' alunos
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-sm btn-outline-primary me-2" onclick="editarSala(' . $sala['id'] . ', \'' . htmlspecialchars($sala['nome']) . '\', ' . $sala['capacidade'] . ', ' . $sala['ativa'] . ')">
                            <i class="fas fa-edit me-1"></i>Editar
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="excluirSala(' . $sala['id'] . ', \'' . htmlspecialchars($sala['nome']) . '\')">
                            <i class="fas fa-trash me-1"></i>Excluir
                        </button>
                    </div>
                </div>
            </div>
        </div>';
    }
    
    return '<div class="row">' . $html . '</div>';
}

// Processar requisição
try {
    $acao = $_GET['acao'] ?? $_GET['action'] ?? $_POST['acao'] ?? $_POST['action'] ?? 'listar';
    
    if ($acao === 'listar') {
        $pdo = conectarBanco();
        if (!$pdo) {
            throw new Exception('Erro de conexão com banco de dados');
        }
        
        $salas = listarSalas($pdo);
        $html = gerarHtmlSalas($salas);
        
        echo json_encode([
            'sucesso' => true,
            'salas' => $salas,
            'html' => $html
        ], JSON_UNESCAPED_UNICODE);
        
    } elseif ($acao === 'editar') {
        $pdo = conectarBanco();
        if (!$pdo) {
            throw new Exception('Erro de conexão com banco de dados');
        }
        
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $capacidade = (int)($_POST['capacidade'] ?? 30);
        $ativa = (int)($_POST['ativa'] ?? 1);
        
        if ($id <= 0) {
            throw new Exception('ID da sala inválido');
        }
        
        if (empty($nome)) {
            throw new Exception('Nome da sala é obrigatório');
        }
        
        if ($capacidade <= 0) {
            throw new Exception('Capacidade deve ser maior que zero');
        }
        
        // Verificar se sala existe
        $stmt = $pdo->prepare("SELECT id FROM salas WHERE id = ? AND cfc_id = ?");
        $stmt->execute([$id, 1]);
        if ($stmt->rowCount() == 0) {
            throw new Exception('Sala não encontrada');
        }
        
        // Verificar se nome já existe em outra sala
        $stmt = $pdo->prepare("SELECT id FROM salas WHERE nome = ? AND id != ? AND cfc_id = ?");
        $stmt->execute([$nome, $id, 1]);
        if ($stmt->rowCount() > 0) {
            throw new Exception('Já existe uma sala com este nome');
        }
        
        // Atualizar sala
        $stmt = $pdo->prepare("UPDATE salas SET nome = ?, capacidade = ?, ativa = ? WHERE id = ? AND cfc_id = ?");
        $stmt->execute([$nome, $capacidade, $ativa, $id, 1]);
        
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Sala atualizada com sucesso!'
        ], JSON_UNESCAPED_UNICODE);
        
    } elseif ($acao === 'excluir') {
        $pdo = conectarBanco();
        if (!$pdo) {
            throw new Exception('Erro de conexão com banco de dados');
        }
        
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        
        // Debug: log dos dados recebidos
        error_log("DEBUG - Ação: excluir, ID recebido: $id");
        error_log("DEBUG - POST data: " . print_r($_POST, true));
        
        if ($id <= 0) {
            throw new Exception('ID da sala inválido. ID recebido: ' . $id);
        }
        
        // Verificar se sala existe
        $stmt = $pdo->prepare("SELECT id FROM salas WHERE id = ? AND cfc_id = ?");
        $stmt->execute([$id, 1]);
        if ($stmt->rowCount() == 0) {
            throw new Exception('Sala não encontrada');
        }
        
        // Verificar se sala está sendo usada em turmas (verificar se tabela existe primeiro)
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM turmas_teoricas WHERE sala_id = ?");
            $stmt->execute([$id]);
            $resultado = $stmt->fetch();
            
            if ($resultado['total'] > 0) {
                throw new Exception('Não é possível excluir esta sala pois ela está sendo usada em turmas teóricas');
            }
        } catch (PDOException $e) {
            // Se a tabela turmas_teoricas não existir, ignorar a verificação
            error_log("DEBUG - Tabela turmas_teoricas não existe ou erro: " . $e->getMessage());
        }
        
        // Excluir sala
        $stmt = $pdo->prepare("DELETE FROM salas WHERE id = ? AND cfc_id = ?");
        $stmt->execute([$id, 1]);
        
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Sala excluída com sucesso!'
        ], JSON_UNESCAPED_UNICODE);
        
    } elseif ($acao === 'criar') {
        $pdo = conectarBanco();
        if (!$pdo) {
            throw new Exception('Erro de conexão com banco de dados');
        }
        
        $nome = trim($_POST['nome'] ?? '');
        $capacidade = (int)($_POST['capacidade'] ?? 30);
        $ativa = 1;
        
        if (empty($nome)) {
            throw new Exception('Nome da sala é obrigatório');
        }
        
        if ($capacidade <= 0) {
            throw new Exception('Capacidade deve ser maior que zero');
        }
        
        // Verificar se já existe sala com o mesmo nome
        $stmt = $pdo->prepare("SELECT id FROM salas WHERE nome = ? AND cfc_id = ?");
        $stmt->execute([$nome, 1]);
        if ($stmt->rowCount() > 0) {
            throw new Exception('Já existe uma sala com este nome');
        }
        
        // Inserir nova sala
        $stmt = $pdo->prepare("INSERT INTO salas (nome, capacidade, equipamentos, ativa, cfc_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $capacidade, '{}', $ativa, 1]);
        
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Sala criada com sucesso!',
            'sala' => [
                'id' => $pdo->lastInsertId(),
                'nome' => $nome,
                'capacidade' => $capacidade
            ]
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        throw new Exception('Ação não reconhecida');
    }
    
} catch (Exception $e) {
    // Log do erro para debug
    error_log("DEBUG - Erro na API: " . $e->getMessage());
    error_log("DEBUG - Stack trace: " . $e->getTraceAsString());
    
    http_response_code(400);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => $e->getMessage(),
        'debug' => [
            'acao' => $acao ?? 'não definida',
            'post_data' => $_POST,
            'get_data' => $_GET
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>
