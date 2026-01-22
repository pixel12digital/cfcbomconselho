<?php
/**
 * API para o instrutor atualizar seu próprio perfil
 * Permite: foto, telefone, e-mail
 * Regra: Instrutor só pode editar o próprio perfil (usando id da sessão)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// IMPORTANTE: Carregar config.php PRIMEIRO (ele inicia a sessão)
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar se sessão foi iniciada
if (session_status() !== PHP_SESSION_ACTIVE) {
    error_log('[API Perfil] AVISO: Sessão não está ativa após carregar config.php');
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        error_log('[API Perfil] Sessão iniciada manualmente');
    }
}

// Reutilizar APENAS as funções de upload (sem executar validações de permissão do admin)
// Copiar funções diretamente para evitar conflito de permissões
if (!function_exists('processarUploadFoto')) {
    function processarUploadFoto($arquivo, $instrutorId = null) {
        if (!isset($arquivo) || $arquivo['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        
        // Validar tipo de arquivo
        $tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $tipoDetectado = $arquivo['type'];
        
        // Se o tipo não foi detectado corretamente, detectar pela extensão
        if (empty($tipoDetectado) || $tipoDetectado === 'application/octet-stream') {
            $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
            $mapeamentoTipos = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp'
            ];
            
            if (isset($mapeamentoTipos[$extensao])) {
                $tipoDetectado = $mapeamentoTipos[$extensao];
            }
        }
        
        if (!in_array($tipoDetectado, $tiposPermitidos)) {
            throw new Exception('Tipo de arquivo não permitido. Use JPG, PNG, GIF ou WebP.');
        }
        
        // Validar tamanho (2MB máximo)
        $tamanhoMaximo = 2 * 1024 * 1024;
        if ($arquivo['size'] > $tamanhoMaximo) {
            throw new Exception('Arquivo muito grande. Tamanho máximo: 2MB.');
        }
        
        // Gerar nome único para o arquivo
        $extensao = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
        $nomeArquivo = 'instrutor_' . ($instrutorId ?: uniqid()) . '_' . time() . '.' . $extensao;
        
        // Diretório de destino
        $diretorioDestino = __DIR__ . '/../../assets/uploads/instrutores/';
        
        // Garantir que o diretório existe
        if (!is_dir($diretorioDestino)) {
            if (!mkdir($diretorioDestino, 0755, true)) {
                throw new Exception('Erro ao criar diretório de upload.');
            }
        }
        
        $caminhoCompleto = $diretorioDestino . $nomeArquivo;
        
        // Verificar se o arquivo temporário existe
        if (!file_exists($arquivo['tmp_name'])) {
            throw new Exception('Arquivo temporário não encontrado.');
        }
        
        // Verificar permissões do diretório
        if (!is_writable($diretorioDestino)) {
            throw new Exception('Diretório de destino não tem permissão de escrita.');
        }
        
        // Mover arquivo
        if (!copy($arquivo['tmp_name'], $caminhoCompleto)) {
            throw new Exception('Erro ao salvar arquivo.');
        }
        
        // Remover arquivo temporário após copiar
        @unlink($arquivo['tmp_name']);
        
        // Retornar caminho relativo para o banco de dados
        return 'assets/uploads/instrutores/' . $nomeArquivo;
    }
}

// Verificar autenticação
$user = getCurrentUser();
if (!$user) {
    error_log('[API Perfil] ERRO: getCurrentUser() retornou null');
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit();
}

if ($user['tipo'] !== 'instrutor') {
    error_log('[API Perfil] ERRO: Tipo de usuário incorreto. Esperado: instrutor, Recebido: ' . ($user['tipo'] ?? 'null'));
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acesso negado. Apenas instrutores podem acessar.']);
    exit();
}

// Obter ID do instrutor da sessão (NUNCA aceitar via GET/POST)
$instrutorId = getCurrentInstrutorId($user['id']);
if (!$instrutorId) {
    error_log('[API Perfil] ERRO: Instrutor não encontrado para user_id: ' . $user['id']);
    
    // Debug adicional: verificar no banco
    $db = db();
    $instrutorCheck = $db->fetch("SELECT id, ativo, status FROM instrutores WHERE usuario_id = ?", [$user['id']]);
    if ($instrutorCheck) {
        error_log('[API Perfil] Instrutor existe no banco mas getCurrentInstrutorId retornou null. ID: ' . $instrutorCheck['id'] . ', Ativo: ' . ($instrutorCheck['ativo'] ?? 'null'));
    }
    
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Instrutor não encontrado']);
    exit();
}

$db = db();
$method = $_SERVER['REQUEST_METHOD'];

// =====================================================
// GET: Buscar dados do perfil
// =====================================================
if ($method === 'GET') {
    try {
        $instrutor = $db->fetch("
            SELECT i.*, u.nome as nome_usuario, u.email as email_usuario, u.telefone as telefone_usuario
            FROM instrutores i
            LEFT JOIN usuarios u ON i.usuario_id = u.id
            WHERE i.id = ?
        ", [$instrutorId]);
        
        if (!$instrutor) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Instrutor não encontrado']);
            exit();
        }
        
        // Priorizar dados da tabela instrutores, com fallback para usuarios
        $perfil = [
            'id' => $instrutor['id'],
            'nome' => $instrutor['nome'] ?? $instrutor['nome_usuario'] ?? '',
            'email' => $instrutor['email'] ?? $instrutor['email_usuario'] ?? '',
            'telefone' => $instrutor['telefone'] ?? $instrutor['telefone_usuario'] ?? '',
            'foto' => $instrutor['foto'] ?? null,
            'credencial' => $instrutor['credencial'] ?? null
        ];
        
        echo json_encode(['success' => true, 'perfil' => $perfil]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro ao buscar perfil: ' . $e->getMessage()]);
    }
    exit();
}

// =====================================================
// PUT/POST: Atualizar perfil
// =====================================================
if ($method === 'PUT' || $method === 'POST') {
    try {
        // Ler dados do JSON ou FormData
        $data = [];
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        // IMPORTANTE: Para PUT com multipart/form-data, o PHP não popula $_POST automaticamente
        // Por isso mudamos para POST no frontend. Aqui mantemos suporte para ambos.
        if ($method === 'PUT' && strpos($contentType, 'multipart/form-data') !== false) {
            $data = $_POST; // Pode estar vazio em PUT
        } elseif (strpos($contentType, 'application/json') !== false) {
            $data = json_decode(file_get_contents('php://input'), true);
        } else {
            // POST com multipart/form-data - usar $_POST normalmente
            $data = $_POST;
        }
        
        // Buscar instrutor existente
        $existingInstrutor = $db->fetch("SELECT id, usuario_id, foto FROM instrutores WHERE id = ?", [$instrutorId]);
        if (!$existingInstrutor) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Instrutor não encontrado']);
            exit();
        }
        
        // Arrays para atualização
        $updateUserData = [];
        $updateInstrutorData = [];
        
        // Processar telefone
        if (isset($data['telefone']) && !empty(trim($data['telefone']))) {
            $telefone = trim($data['telefone']);
            $updateUserData['telefone'] = $telefone;
            $updateInstrutorData['telefone'] = $telefone;
        }
        
        // Processar e-mail
        if (isset($data['email'])) {
            $email = trim($data['email']);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'E-mail inválido']);
                exit();
            }
            
            // Verificar se email já existe em outro usuário
            $emailExistente = $db->fetch("SELECT id FROM usuarios WHERE email = ? AND id != ?", [$email, $existingInstrutor['usuario_id']]);
            if ($emailExistente) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Este e-mail já está em uso por outro usuário']);
                exit();
            }
            
            $updateUserData['email'] = $email;
            $updateInstrutorData['email'] = $email;
        }
        
        // Processar upload de foto se houver
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            try {
                // Remover foto antiga se existir
                if (isset($existingInstrutor['foto']) && !empty($existingInstrutor['foto'])) {
                    $caminhoFotoAntiga = $existingInstrutor['foto'];
                    // Caminho relativo: assets/uploads/instrutores/...
                    $caminhoCompleto = __DIR__ . '/../../' . $caminhoFotoAntiga;
                    if (file_exists($caminhoCompleto)) {
                        @unlink($caminhoCompleto);
                    }
                }
                
                $caminhoFoto = processarUploadFoto($_FILES['foto'], $instrutorId);
                if ($caminhoFoto) {
                    $updateInstrutorData['foto'] = $caminhoFoto;
                }
            } catch (Exception $e) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Erro no upload da foto: ' . $e->getMessage()]);
                exit();
            }
        }
        
        // Atualizar tabela usuarios (se houver dados)
        if (!empty($updateUserData)) {
            $updateUserData['updated_at'] = date('Y-m-d H:i:s');
            // Usar query direta para garantir que funciona
            $setParts = [];
            $params = [];
            foreach ($updateUserData as $field => $value) {
                $setParts[] = "{$field} = ?";
                $params[] = $value;
            }
            $params[] = $existingInstrutor['usuario_id'];
            $sql = "UPDATE usuarios SET " . implode(', ', $setParts) . " WHERE id = ?";
            
            try {
                $stmt = $db->query($sql, $params);
            } catch (Exception $e) {
                error_log('[API Perfil] ERRO ao atualizar usuarios: ' . $e->getMessage());
                throw $e;
            }
            
            // Atualizar sessão se email mudou
            if (isset($updateUserData['email'])) {
                $_SESSION['user_email'] = $updateUserData['email'];
            }
        }
        
        // Atualizar tabela instrutores (se houver dados)
        if (!empty($updateInstrutorData)) {
            $updateInstrutorData['updated_at'] = date('Y-m-d H:i:s');
            // Usar query direta para garantir que funciona
            $setParts = [];
            $params = [];
            foreach ($updateInstrutorData as $field => $value) {
                $setParts[] = "{$field} = ?";
                $params[] = $value;
            }
            $params[] = $instrutorId;
            $sql = "UPDATE instrutores SET " . implode(', ', $setParts) . " WHERE id = ?";
            
            try {
                $stmt = $db->query($sql, $params);
            } catch (Exception $e) {
                error_log('[API Perfil] ERRO ao atualizar instrutores: ' . $e->getMessage());
                throw $e;
            }
        }
        
        // Buscar dados atualizados
        $instrutorAtualizado = $db->fetch("
            SELECT i.*, u.nome as nome_usuario, u.email as email_usuario, u.telefone as telefone_usuario
            FROM instrutores i
            LEFT JOIN usuarios u ON i.usuario_id = u.id
            WHERE i.id = ?
        ", [$instrutorId]);
        
        $perfil = [
            'id' => $instrutorAtualizado['id'],
            'nome' => $instrutorAtualizado['nome'] ?? $instrutorAtualizado['nome_usuario'] ?? '',
            'email' => $instrutorAtualizado['email'] ?? $instrutorAtualizado['email_usuario'] ?? '',
            'telefone' => $instrutorAtualizado['telefone'] ?? $instrutorAtualizado['telefone_usuario'] ?? '',
            'foto' => $instrutorAtualizado['foto'] ?? null,
            'credencial' => $instrutorAtualizado['credencial'] ?? null
        ];
        
        echo json_encode([
            'success' => true,
            'message' => 'Perfil atualizado com sucesso',
            'perfil' => $perfil
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro ao atualizar perfil: ' . $e->getMessage()]);
        if (defined('LOG_ENABLED') && LOG_ENABLED) {
            error_log('Erro ao atualizar perfil do instrutor: ' . $e->getMessage());
        }
    }
    exit();
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Método não permitido']);
