<?php
// API para gerenciamento de usuários
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Log de início da API
error_log('[USUARIOS API] Iniciando - Método: ' . $_SERVER['REQUEST_METHOD'] . ' - URI: ' . $_SERVER['REQUEST_URI']);

// Usar caminho relativo que sabemos que funciona
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/CredentialManager.php';

// Log das configurações
error_log('[USUARIOS API] Ambiente: ' . (defined('ENVIRONMENT') ? ENVIRONMENT : 'indefinido'));
error_log('[USUARIOS API] Debug Mode: ' . (DEBUG_MODE ? 'true' : 'false'));

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    error_log('[USUARIOS API] Usuário não está logado');
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado', 'code' => 'NOT_LOGGED_IN']);
    exit;
}

// Verificar permissão de admin
$currentUser = getCurrentUser();
if (!$currentUser) {
    error_log('[USUARIOS API] Usuário atual não encontrado');
    http_response_code(401);
    echo json_encode(['error' => 'Sessão inválida', 'code' => 'INVALID_SESSION']);
    exit;
}

// Log do usuário atual
error_log('[USUARIOS API] Usuário logado: ' . $currentUser['email'] . ' (Tipo: ' . $currentUser['tipo'] . ')');

// Verificar se é admin ou secretaria
if (!canManageUsers()) {
    error_log('[USUARIOS API] Usuário não tem permissão para gerenciar usuários: ' . $currentUser['tipo']);
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado - Apenas administradores e atendentes podem gerenciar usuários', 'code' => 'NOT_AUTHORIZED']);
    exit;
}

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

// Log do método e parâmetros
error_log('[USUARIOS API] Método: ' . $method);
if (!empty($_GET)) {
    error_log('[USUARIOS API] GET params: ' . json_encode($_GET));
}

try {
    switch ($method) {
        case 'GET':
            // Listar usuários ou buscar usuário específico
            if (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                error_log('[USUARIOS API] Buscando usuário ID: ' . $id);
                
                $usuario = $db->fetch("SELECT id, nome, email, cpf, telefone, tipo, ativo, criado_em FROM usuarios WHERE id = ?", [$id]);
                
                if ($usuario) {
                    error_log('[USUARIOS API] Usuário encontrado: ' . $usuario['email']);
                    echo json_encode(['success' => true, 'data' => $usuario]);
                } else {
                    error_log('[USUARIOS API] Usuário não encontrado - ID: ' . $id);
                    http_response_code(404);
                    echo json_encode(['error' => 'Usuário não encontrado', 'code' => 'USER_NOT_FOUND']);
                }
            } else {
                // Listar todos os usuários
                error_log('[USUARIOS API] Listando todos os usuários');
                $usuarios = $db->fetchAll("SELECT id, nome, email, tipo, ativo, criado_em FROM usuarios ORDER BY nome");
                error_log('[USUARIOS API] Total de usuários encontrados: ' . count($usuarios));
                echo json_encode(['success' => true, 'data' => $usuarios]);
            }
            break;
            
        case 'POST':
            // Criar novo usuário
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                $data = $_POST;
            }
            
            error_log('[USUARIOS API] Dados recebidos para criação: ' . json_encode(array_keys($data)));
            
            // Validações básicas
            if (empty($data['nome']) || empty($data['email']) || empty($data['tipo'])) {
                error_log('[USUARIOS API] Dados obrigatórios ausentes');
                http_response_code(400);
                echo json_encode(['error' => 'Nome, email e tipo são obrigatórios', 'code' => 'MISSING_FIELDS']);
                exit;
            }
            
            // Verificar se email já existe
            $existingUser = $db->fetch("SELECT id FROM usuarios WHERE email = ?", [$data['email']]);
            if ($existingUser) {
                error_log('[USUARIOS API] Email já existe: ' . $data['email']);
                http_response_code(400);
                echo json_encode(['error' => 'E-mail já cadastrado', 'code' => 'EMAIL_EXISTS']);
                exit;
            }
            
            // Criar credenciais automáticas
            error_log('[USUARIOS API] Criando credenciais automáticas para: ' . $data['email']);
            $credentials = CredentialManager::createEmployeeCredentials([
                'nome' => $data['nome'],
                'email' => $data['email'],
                'tipo' => $data['tipo']
            ]);
            
            if (!$credentials['success']) {
                error_log('[USUARIOS API] Erro ao criar credenciais: ' . $credentials['message']);
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao criar credenciais do usuário', 'code' => 'CREDENTIAL_ERROR']);
                exit;
            }
            
            // Enviar credenciais por email (simulado)
            CredentialManager::sendCredentials(
                $credentials['email'], 
                $credentials['senha_temporaria'], 
                $data['tipo']
            );
            
            $result = $credentials['usuario_id'];
            
            if ($result) {
                error_log('[USUARIOS API] Usuário criado com sucesso - ID: ' . $result);
                $usuario = $db->fetch("SELECT id, nome, email, tipo, ativo, criado_em FROM usuarios WHERE id = ?", [$result]);
                
                $response = [
                    'success' => true, 
                    'message' => 'Usuário criado com sucesso', 
                    'data' => $usuario
                ];
                
                // Adicionar credenciais à resposta
                $response['credentials'] = [
                    'email' => $credentials['email'],
                    'senha_temporaria' => $credentials['senha_temporaria'],
                    'message' => 'Credenciais criadas automaticamente'
                ];
                
                echo json_encode($response);
            } else {
                error_log('[USUARIOS API] Erro ao criar usuário');
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao criar usuário', 'code' => 'CREATE_FAILED']);
            }
            break;
            
        case 'PUT':
            // Atualizar usuário
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                parse_str(file_get_contents('php://input'), $data);
            }
            
            error_log('[USUARIOS API] Dados recebidos para atualização: ' . json_encode(array_keys($data)));
            
            if (empty($data['id'])) {
                error_log('[USUARIOS API] ID do usuário ausente para atualização');
                http_response_code(400);
                echo json_encode(['error' => 'ID do usuário é obrigatório', 'code' => 'MISSING_ID']);
                exit;
            }
            
            $id = (int)$data['id'];
            error_log('[USUARIOS API] Atualizando usuário ID: ' . $id);
            
            // Verificar se usuário existe
            $existingUser = $db->fetch("SELECT id FROM usuarios WHERE id = ?", [$id]);
            if (!$existingUser) {
                error_log('[USUARIOS API] Usuário não encontrado para atualização - ID: ' . $id);
                http_response_code(404);
                echo json_encode(['error' => 'Usuário não encontrado', 'code' => 'USER_NOT_FOUND']);
                exit;
            }
            
            // Preparar dados para atualização
            $updateData = [];
            if (!empty($data['nome'])) $updateData['nome'] = $data['nome'];
            if (!empty($data['email'])) $updateData['email'] = $data['email'];
            if (!empty($data['tipo'])) $updateData['tipo'] = $data['tipo'];
            if (isset($data['ativo'])) $updateData['ativo'] = (bool)$data['ativo'];
            
            // Atualizar senha se fornecida
            if (!empty($data['senha'])) {
                $updateData['senha'] = password_hash($data['senha'], PASSWORD_DEFAULT);
            }
            
            $updateData['atualizado_em'] = date('Y-m-d H:i:s');
            
            // Atualizar usuário
            $result = $db->update('usuarios', $updateData, 'id = ?', [$id]);
            
            if ($result) {
                error_log('[USUARIOS API] Usuário atualizado com sucesso - ID: ' . $id);
                $usuario = $db->fetch("SELECT id, nome, email, tipo, ativo, criado_em FROM usuarios WHERE id = ?", [$id]);
                echo json_encode(['success' => true, 'message' => 'Usuário atualizado com sucesso', 'data' => $usuario]);
            } else {
                error_log('[USUARIOS API] Erro ao atualizar usuário - ID: ' . $id);
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao atualizar usuário', 'code' => 'UPDATE_FAILED']);
            }
            break;
            
        case 'DELETE':
            // Excluir usuário
            if (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                error_log('[USUARIOS API] Tentando excluir usuário ID: ' . $id);
                
                // Verificar se usuário existe
                $existingUser = $db->fetch("SELECT id, email FROM usuarios WHERE id = ?", [$id]);
                if (!$existingUser) {
                    error_log('[USUARIOS API] Usuário não encontrado para exclusão - ID: ' . $id);
                    http_response_code(404);
                    echo json_encode(['error' => 'Usuário não encontrado', 'code' => 'USER_NOT_FOUND']);
                    exit;
                }
                
                error_log('[USUARIOS API] Usuário encontrado para exclusão: ' . $existingUser['email']);
                
                // Não permitir exclusão do próprio usuário logado
                if ($id == $currentUser['id']) {
                    error_log('[USUARIOS API] Tentativa de auto-exclusão bloqueada');
                    http_response_code(400);
                    echo json_encode(['error' => 'Não é possível excluir o próprio usuário', 'code' => 'SELF_DELETE']);
                    exit;
                }
                
                // Verificar todas as dependências do usuário
                $dependencias = [];
                
                // Verificar CFCs vinculados
                $cfcsVinculados = $db->fetchAll("SELECT id, nome FROM cfcs WHERE responsavel_id = ?", [$id]);
                if (count($cfcsVinculados) > 0) {
                    $dependencias[] = [
                        'tipo' => 'CFCs',
                        'quantidade' => count($cfcsVinculados),
                        'itens' => $cfcsVinculados,
                        'instrucao' => 'Remova ou altere o responsável dos CFCs antes de excluir o usuário.'
                    ];
                }
                
                // Verificar registros de instrutor
                $instrutoresVinculados = $db->fetchAll("SELECT id, cfc_id FROM instrutores WHERE usuario_id = ?", [$id]);
                if (count($instrutoresVinculados) > 0) {
                    $dependencias[] = [
                        'tipo' => 'Registros de Instrutor',
                        'quantidade' => count($instrutoresVinculados),
                        'itens' => $instrutoresVinculados,
                        'instrucao' => 'Remova os registros de instrutor antes de excluir o usuário.'
                    ];
                }
                
                // Verificar aulas como instrutor
                $aulasComoInstrutor = $db->fetchAll("
                    SELECT a.id, a.data_aula, a.tipo_aula FROM aulas a 
                    INNER JOIN instrutores i ON a.instrutor_id = i.id 
                    WHERE i.usuario_id = ?
                ", [$id]);
                if (count($aulasComoInstrutor) > 0) {
                    $dependencias[] = [
                        'tipo' => 'Aulas como Instrutor',
                        'quantidade' => count($aulasComoInstrutor),
                        'itens' => $aulasComoInstrutor,
                        'instrucao' => 'Remova ou altere as aulas onde o usuário é instrutor antes de excluí-lo.'
                    ];
                }
                
                // Verificar sessões e logs (apenas informativo, serão removidos automaticamente)
                $sessoes = $db->fetch("SELECT COUNT(*) as total FROM sessoes WHERE usuario_id = ?", [$id]);
                $logs = $db->fetch("SELECT COUNT(*) as total FROM logs WHERE usuario_id = ?", [$id]);
                
                if ($sessoes['total'] > 0 || $logs['total'] > 0) {
                    $dependencias[] = [
                        'tipo' => 'Dados de Sistema',
                        'quantidade' => $sessoes['total'] + $logs['total'],
                        'itens' => [
                            'sessoes' => $sessoes['total'],
                            'logs' => $logs['total']
                        ],
                        'instrucao' => 'Sessões e logs serão removidos automaticamente durante a exclusão.'
                    ];
                }
                
                // Se há dependências, retornar erro com instruções detalhadas
                if (!empty($dependencias)) {
                    error_log('[USUARIOS API] Usuário tem dependências - não pode ser excluído');
                    
                    $mensagem = "Não é possível excluir o usuário pois ele possui vínculos ativos:\n\n";
                    foreach ($dependencias as $dep) {
                        $mensagem .= "• {$dep['tipo']}: {$dep['quantidade']} registro(s)\n";
                        $mensagem .= "  Instrução: {$dep['instrucao']}\n\n";
                    }
                    $mensagem .= "Resolva todos os vínculos antes de tentar excluir o usuário novamente.";
                    
                    http_response_code(400);
                    echo json_encode([
                        'error' => $mensagem,
                        'code' => 'HAS_DEPENDENCIES',
                        'dependencias' => $dependencias,
                        'instrucoes' => array_map(function($dep) {
                            return $dep['instrucao'];
                        }, $dependencias)
                    ]);
                    exit;
                }
                
                try {
                    // Começar transação
                    $db->beginTransaction();
                    
                    // Excluir logs do usuário
                    error_log('[USUARIOS API] Excluindo logs do usuário');
                    $db->query("DELETE FROM logs WHERE usuario_id = ?", [$id]);
                    
                    // Excluir sessões do usuário
                    error_log('[USUARIOS API] Excluindo sessões do usuário');
                    $db->query("DELETE FROM sessoes WHERE usuario_id = ?", [$id]);
                    
                    // Excluir usuário usando PDO diretamente
                    error_log('[USUARIOS API] Excluindo usuário da tabela usuarios');
                    $pdo = $db->getConnection();
                    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
                    $result = $stmt->execute([$id]);
                    
                    if ($result && $stmt->rowCount() > 0) {
                        $db->commit();
                        error_log('[USUARIOS API] Usuário excluído com sucesso - ID: ' . $id . ' (' . $existingUser['email'] . ')');
                        echo json_encode(['success' => true, 'message' => 'Usuário excluído com sucesso']);
                    } else {
                        $db->rollback();
                        error_log('[USUARIOS API] Falha ao excluir usuário - ID: ' . $id);
                        http_response_code(500);
                        echo json_encode(['error' => 'Erro ao excluir usuário', 'code' => 'DELETE_FAILED']);
                    }
                } catch (Exception $e) {
                    $db->rollback();
                    error_log('[USUARIOS API] Exceção durante exclusão - ID: ' . $id . ' - Erro: ' . $e->getMessage());
                    http_response_code(500);
                    echo json_encode(['error' => 'Erro interno ao excluir usuário: ' . $e->getMessage(), 'code' => 'DELETE_EXCEPTION']);
                }
            } else {
                error_log('[USUARIOS API] ID ausente para exclusão');
                http_response_code(400);
                echo json_encode(['error' => 'ID do usuário é obrigatório', 'code' => 'MISSING_ID']);
            }
            break;
            
        default:
            error_log('[USUARIOS API] Método não permitido: ' . $method);
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido', 'code' => 'METHOD_NOT_ALLOWED']);
            break;
    }
    
} catch (Exception $e) {
    error_log('[USUARIOS API] Exceção geral: ' . $e->getMessage() . ' - Stack: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno: ' . $e->getMessage(), 'code' => 'INTERNAL_ERROR']);
}

error_log('[USUARIOS API] Finalizando processamento');
?>
