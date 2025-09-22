<?php
/**
 * API para gerenciamento de Documentos de Alunos
 * Sistema CFC - Bom Conselho
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Responder a requisições OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir arquivos necessários
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

try {
    $db = Database::getInstance();
    
    // Verificar autenticação
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
        exit;
    }
    
    // Verificar permissão
    $currentUser = getCurrentUser();
    if (!$currentUser || !in_array($currentUser['tipo'], ['admin', 'secretaria'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Acesso negado']);
        exit;
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGet($db);
            break;
        case 'POST':
            handlePost($db);
            break;
        case 'PUT':
            handlePut($db);
            break;
        case 'DELETE':
            handleDelete($db);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro interno: ' . $e->getMessage()]);
}

/**
 * Processar requisições GET
 */
function handleGet($db) {
    $alunoId = $_GET['aluno_id'] ?? null;
    
    if ($alunoId) {
        // Buscar documentos de um aluno específico
        $documentos = $db->fetchAll("
            SELECT d.*, a.nome as aluno_nome
            FROM aluno_documentos d
            JOIN alunos a ON d.aluno_id = a.id
            WHERE d.aluno_id = ?
            ORDER BY d.tipo_documento, d.criado_em DESC
        ", [$alunoId]);
        
        echo json_encode(['success' => true, 'documentos' => $documentos]);
    } else {
        // Listar todos os documentos
        $documentos = $db->fetchAll("
            SELECT d.*, a.nome as aluno_nome
            FROM aluno_documentos d
            JOIN alunos a ON d.aluno_id = a.id
            ORDER BY d.criado_em DESC
            LIMIT 100
        ");
        
        echo json_encode(['success' => true, 'documentos' => $documentos]);
    }
}

/**
 * Processar requisições POST
 */
function handlePost($db) {
    // Verificar se é upload de arquivo
    if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
        handleFileUpload($db);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
        return;
    }
    
    // Validar dados obrigatórios
    $required = ['aluno_id', 'tipo_documento', 'nome_arquivo', 'caminho_arquivo'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Campo obrigatório: $field"]);
            return;
        }
    }
    
    // Verificar se aluno existe
    $aluno = $db->fetch("SELECT id FROM alunos WHERE id = ?", [$input['aluno_id']]);
    if (!$aluno) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Aluno não encontrado']);
        return;
    }
    
    // Inserir novo documento
    $documentoId = $db->execute("
        INSERT INTO aluno_documentos (
            aluno_id, tipo_documento, nome_arquivo, caminho_arquivo,
            tamanho_arquivo, tipo_mime, descricao, status, observacoes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ", [
        $input['aluno_id'],
        $input['tipo_documento'],
        $input['nome_arquivo'],
        $input['caminho_arquivo'],
        $input['tamanho_arquivo'] ?? 0,
        $input['tipo_mime'] ?? 'application/octet-stream',
        $input['descricao'] ?? null,
        $input['status'] ?? 'pendente',
        $input['observacoes'] ?? null
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Documento adicionado com sucesso',
        'documento_id' => $documentoId
    ]);
}

/**
 * Processar upload de arquivo
 */
function handleFileUpload($db) {
    $alunoId = $_POST['aluno_id'] ?? null;
    $tipoDocumento = $_POST['tipo_documento'] ?? null;
    $descricao = $_POST['descricao'] ?? null;
    
    if (!$alunoId || !$tipoDocumento) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Dados obrigatórios não fornecidos']);
        return;
    }
    
    $arquivo = $_FILES['arquivo'];
    
    // Validar tipo de arquivo
    $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    if (!in_array($arquivo['type'], $tiposPermitidos)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Tipo de arquivo não permitido']);
        return;
    }
    
    // Validar tamanho (máximo 5MB)
    if ($arquivo['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Arquivo muito grande (máximo 5MB)']);
        return;
    }
    
    // Criar diretório se não existir
    $uploadDir = '../../uploads/aluno_documentos/' . $alunoId . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Gerar nome único para o arquivo
    $extensao = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
    $nomeArquivo = uniqid() . '_' . time() . '.' . $extensao;
    $caminhoCompleto = $uploadDir . $nomeArquivo;
    
    // Mover arquivo
    if (move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
        // Salvar no banco
        $documentoId = $db->execute("
            INSERT INTO aluno_documentos (
                aluno_id, tipo_documento, nome_arquivo, caminho_arquivo,
                tamanho_arquivo, tipo_mime, descricao, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendente')
        ", [
            $alunoId,
            $tipoDocumento,
            $arquivo['name'],
            $caminhoCompleto,
            $arquivo['size'],
            $arquivo['type'],
            $descricao
        ]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Arquivo enviado com sucesso',
            'documento_id' => $documentoId,
            'caminho' => $caminhoCompleto
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar arquivo']);
    }
}

/**
 * Processar requisições PUT
 */
function handlePut($db) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID do documento não fornecido']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
        return;
    }
    
    // Verificar se documento existe
    $documento = $db->fetch("SELECT * FROM aluno_documentos WHERE id = ?", [$id]);
    if (!$documento) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Documento não encontrado']);
        return;
    }
    
    // Atualizar documento
    $db->execute("
        UPDATE aluno_documentos SET
            tipo_documento = ?,
            descricao = ?,
            status = ?,
            observacoes = ?,
            atualizado_em = NOW()
        WHERE id = ?
    ", [
        $input['tipo_documento'] ?? $documento['tipo_documento'],
        $input['descricao'] ?? $documento['descricao'],
        $input['status'] ?? $documento['status'],
        $input['observacoes'] ?? $documento['observacoes'],
        $id
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Documento atualizado com sucesso']);
}

/**
 * Processar requisições DELETE
 */
function handleDelete($db) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID do documento não fornecido']);
        return;
    }
    
    // Verificar se documento existe
    $documento = $db->fetch("SELECT * FROM aluno_documentos WHERE id = ?", [$id]);
    if (!$documento) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Documento não encontrado']);
        return;
    }
    
    // Excluir arquivo físico
    if (file_exists($documento['caminho_arquivo'])) {
        unlink($documento['caminho_arquivo']);
    }
    
    // Excluir registro do banco
    $db->execute("DELETE FROM aluno_documentos WHERE id = ?", [$id]);
    
    echo json_encode(['success' => true, 'message' => 'Documento excluído com sucesso']);
}
