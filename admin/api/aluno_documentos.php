<?php
/**
 * API para gerenciamento de Documentos de Alunos
 * Sistema CFC - Bom Conselho
 * 
 * Endpoints:
 * - GET ?aluno_id={id} - Listar documentos de um aluno
 * - POST ?aluno_id={id} - Upload de documento (FormData: arquivo, tipo)
 * - DELETE ?id={documento_id} - Remover documento
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
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

// Função auxiliar para enviar resposta JSON
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Verificar autenticação
    if (!isLoggedIn()) {
        sendJsonResponse(['success' => false, 'error' => 'Usuário não autenticado'], 401);
    }
    
    // Verificar permissão
    $currentUser = getCurrentUser();
    if (!$currentUser || !in_array($currentUser['tipo'], ['admin', 'secretaria'])) {
        sendJsonResponse(['success' => false, 'error' => 'Acesso negado'], 403);
    }
    
    // Criar tabela se não existir
    criarTabelaDocumentos($db);
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGet($db);
            break;
        case 'POST':
            handlePost($db);
            break;
        case 'DELETE':
            handleDelete($db);
            break;
        default:
            sendJsonResponse(['success' => false, 'error' => 'Método não permitido'], 405);
    }
    
} catch (Exception $e) {
    if (defined('LOG_ENABLED') && LOG_ENABLED) {
        error_log('[API Aluno Documentos] Erro: ' . $e->getMessage());
    }
    sendJsonResponse(['success' => false, 'error' => 'Erro interno: ' . $e->getMessage()], 500);
}

/**
 * Criar tabela de documentos se não existir
 */
function criarTabelaDocumentos($db) {
    try {
        // Verificar se a tabela existe
        $result = $db->query("SHOW TABLES LIKE 'alunos_documentos'");
        if ($result && $result->rowCount() > 0) {
            return; // Tabela já existe
        }
        
        // Criar tabela
        $db->query("
            CREATE TABLE IF NOT EXISTS alunos_documentos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                aluno_id INT NOT NULL,
                tipo VARCHAR(50) NOT NULL,
                nome_original VARCHAR(255) NOT NULL,
                arquivo VARCHAR(500) NOT NULL,
                mime_type VARCHAR(100) DEFAULT 'application/octet-stream',
                tamanho_bytes INT DEFAULT 0,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_aluno_id (aluno_id),
                INDEX idx_tipo (tipo),
                FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        if (defined('LOG_ENABLED') && LOG_ENABLED) {
            error_log('[API Aluno Documentos] Tabela alunos_documentos criada com sucesso');
        }
    } catch (Exception $e) {
        if (defined('LOG_ENABLED') && LOG_ENABLED) {
            error_log('[API Aluno Documentos] Erro ao criar tabela: ' . $e->getMessage());
        }
        // Não lançar exceção, apenas logar
    }
}

/**
 * Processar requisições GET - Listar documentos
 */
function handleGet($db) {
    $alunoId = $_GET['aluno_id'] ?? null;
    
    if (!$alunoId) {
        sendJsonResponse(['success' => false, 'error' => 'ID do aluno não fornecido'], 400);
    }
    
    // Verificar se aluno existe
    $aluno = $db->fetch("SELECT id FROM alunos WHERE id = ?", [$alunoId]);
    if (!$aluno) {
        sendJsonResponse(['success' => false, 'error' => 'Aluno não encontrado'], 404);
    }
    
    // Buscar documentos do aluno
    $documentos = $db->fetchAll("
        SELECT 
            id,
            tipo,
            nome_original,
            arquivo,
            mime_type,
            tamanho_bytes,
            criado_em
        FROM alunos_documentos
        WHERE aluno_id = ?
        ORDER BY criado_em DESC
    ", [$alunoId]);
    
    sendJsonResponse([
        'success' => true,
        'documentos' => $documentos ?: []
    ]);
}

/**
 * Processar requisições POST - Upload de documento
 */
function handlePost($db) {
    // Obter aluno_id: aceitar tanto de POST quanto de GET (mais tolerante)
    $alunoId = isset($_POST['aluno_id']) ? (int)$_POST['aluno_id'] 
              : (isset($_GET['aluno_id']) ? (int)$_GET['aluno_id'] : 0);
    
    // Obter tipo: aceitar tanto 'tipo' quanto 'tipo_documento' (compatibilidade)
    $tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) 
           : (isset($_POST['tipo_documento']) ? trim($_POST['tipo_documento']) : '');
    
    // Validação de obrigatórios (mantendo a mesma mensagem que já está sendo usada)
    if ($alunoId <= 0 || $tipo === '' || !isset($_FILES['arquivo'])) {
        sendJsonResponse(['success' => false, 'error' => 'Dados obrigatórios não fornecidos (aluno_id, tipo)'], 400);
    }
    
    // Verificar se aluno existe
    $aluno = $db->fetch("SELECT id FROM alunos WHERE id = ?", [$alunoId]);
    if (!$aluno) {
        sendJsonResponse(['success' => false, 'error' => 'Aluno não encontrado'], 404);
    }
    
    // Verificar se há arquivo enviado
    if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
        sendJsonResponse(['success' => false, 'error' => 'Nenhum arquivo enviado ou erro no upload'], 400);
    }
    
    $arquivo = $_FILES['arquivo'];
    
    // Validar extensão
    $fileInfo = pathinfo($arquivo['name']);
    $extension = strtolower($fileInfo['extension'] ?? '');
    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    
    if (empty($extension) || !in_array($extension, $allowedExtensions)) {
        sendJsonResponse(['success' => false, 'error' => 'Formato de arquivo não permitido. Use PDF, JPG, JPEG ou PNG.'], 400);
    }
    
    // Validar tamanho (máximo 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($arquivo['size'] > $maxSize) {
        sendJsonResponse(['success' => false, 'error' => 'Arquivo muito grande. Máximo 5MB.'], 400);
    }
    
    // Criar diretório se não existir
    $uploadDir = __DIR__ . '/../uploads/alunos_documentos/' . $alunoId . '/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            sendJsonResponse(['success' => false, 'error' => 'Erro ao criar diretório de upload'], 500);
        }
    }
    
    // Verificar permissões do diretório
    if (!is_writable($uploadDir)) {
        sendJsonResponse(['success' => false, 'error' => 'Diretório de upload não tem permissão de escrita'], 500);
    }
    
    // Gerar nome único para o arquivo
    $nomeArquivo = $tipo . '_' . time() . '_' . uniqid() . '.' . $extension;
    $caminhoCompleto = $uploadDir . $nomeArquivo;
    
    // Mover arquivo
    if (!move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
        sendJsonResponse(['success' => false, 'error' => 'Erro ao salvar arquivo no servidor'], 500);
    }
    
    // Caminho relativo para salvar no banco
    $caminhoRelativo = 'admin/uploads/alunos_documentos/' . $alunoId . '/' . $nomeArquivo;
    
    // Salvar no banco
    try {
        $documentoId = $db->insert('alunos_documentos', [
            'aluno_id' => (int)$alunoId,
            'tipo' => $tipo,
            'nome_original' => $arquivo['name'],
            'arquivo' => $caminhoRelativo,
            'mime_type' => $arquivo['type'],
            'tamanho_bytes' => (int)$arquivo['size']
        ]);
        
        // Buscar documento recém-criado
        $documento = $db->fetch("
            SELECT 
                id,
                tipo,
                nome_original,
                arquivo,
                mime_type,
                tamanho_bytes,
                criado_em
            FROM alunos_documentos
            WHERE id = ?
        ", [$documentoId]);
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Documento enviado com sucesso',
            'documento' => $documento
        ]);
        
    } catch (Exception $e) {
        // Se falhar ao salvar no banco, remover arquivo físico
        if (file_exists($caminhoCompleto)) {
            unlink($caminhoCompleto);
        }
        throw $e;
    }
}

/**
 * Processar requisições DELETE - Remover documento
 */
function handleDelete($db) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        sendJsonResponse(['success' => false, 'error' => 'ID do documento não fornecido'], 400);
    }
    
    // Buscar documento
    $documento = $db->fetch("SELECT * FROM alunos_documentos WHERE id = ?", [$id]);
    if (!$documento) {
        sendJsonResponse(['success' => false, 'error' => 'Documento não encontrado'], 404);
    }
    
    // Remover arquivo físico
    $caminhoFisico = __DIR__ . '/../' . $documento['arquivo'];
    if (file_exists($caminhoFisico)) {
        if (!unlink($caminhoFisico)) {
            if (defined('LOG_ENABLED') && LOG_ENABLED) {
                error_log('[API Aluno Documentos] Aviso: Não foi possível remover arquivo físico: ' . $caminhoFisico);
            }
        }
    }
    
    // Remover registro do banco
    $db->delete('alunos_documentos', 'id = ?', [$id]);
    
    sendJsonResponse([
        'success' => true,
        'message' => 'Documento excluído com sucesso'
    ]);
}

