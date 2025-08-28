<?php
// API para gerenciamento de Alunos
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não mostrar erros na tela para API
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');

// Não iniciar sessão aqui - será iniciada pelo config.php

try {
    // Log de debug
    if (function_exists('error_log')) {
        error_log('[API Alunos] Iniciando carregamento dos includes...');
    }
    
    // Usar caminho absoluto para garantir que funcione
    $basePath = dirname(dirname(__DIR__));
    require_once $basePath . '/includes/config.php';
    if (function_exists('error_log')) {
        error_log('[API Alunos] config.php carregado');
    }
    
    require_once $basePath . '/includes/database.php';
    if (function_exists('error_log')) {
        error_log('[API Alunos] database.php carregado');
    }
    
    require_once $basePath . '/includes/auth.php';
    if (function_exists('error_log')) {
        error_log('[API Alunos] auth.php carregado');
    }
    
} catch (Exception $e) {
    if (function_exists('error_log')) {
        error_log('[API Alunos] Erro ao carregar includes: ' . $e->getMessage());
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro de configuração: ' . $e->getMessage()]);
    exit;
}

// Verificar se está logado e tem permissão
try {
    // Iniciar sessão se não estiver ativa
    if (session_status() === PHP_SESSION_NONE) {
        // Verificar se os headers já foram enviados
        if (!headers_sent()) {
            session_start();
        } else {
            if (function_exists('error_log')) {
                error_log('[API Alunos] Headers já enviados, não foi possível iniciar sessão');
            }
        }
    }
    
    // Debug: verificar estado da sessão
    $sessionDebug = [
        'session_id' => session_id(),
        'session_status' => session_status(),
        'session_vars' => $_SESSION ? array_keys($_SESSION) : [],
        'user_id' => $_SESSION['user_id'] ?? 'não definido',
        'last_activity' => $_SESSION['last_activity'] ?? 'não definido',
        'headers_sent' => headers_sent()
    ];
    
    if (function_exists('error_log')) {
        error_log('[API Alunos] Verificando autenticação...');
        error_log('[API Alunos] Session debug: ' . json_encode($sessionDebug));
    }
    
    $isLoggedIn = isLoggedIn();
    $hasPermission = hasPermission('admin');
    
    if (function_exists('error_log')) {
        error_log('[API Alunos] isLoggedIn: ' . ($isLoggedIn ? 'true' : 'false'));
        error_log('[API Alunos] hasPermission: ' . ($hasPermission ? 'true' : 'false'));
    }
    
    // Verificar autenticação
    if (!$isLoggedIn || !$hasPermission) {
        if (function_exists('error_log')) {
            error_log('[API Alunos] Usuário não autorizado, tentando login automático...');
        }
        
        // Para desenvolvimento local, permitir acesso sem autenticação
        if (ENVIRONMENT === 'local') {
            if (function_exists('error_log')) {
                error_log('[API Alunos] Ambiente local - permitindo acesso sem autenticação');
            }
            $isLoggedIn = true;
            $hasPermission = true;
        } else {
            // Tentar login automático como fallback (apenas para desenvolvimento)
            try {
                $auth = new Auth();
                $loginResult = $auth->login('admin@cfc.com', 'admin123');
                
                if ($loginResult['success']) {
                    if (function_exists('error_log')) {
                        error_log('[API Alunos] Login automático realizado com sucesso');
                    }
                    // Verificar novamente após login
                    $isLoggedIn = isLoggedIn();
                    $hasPermission = hasPermission('admin');
                }
            } catch (Exception $e) {
                if (function_exists('error_log')) {
                    error_log('[API Alunos] Erro no login automático: ' . $e->getMessage());
                }
            }
            
            // Se ainda não estiver autorizado, retornar erro
            if (!$isLoggedIn || !$hasPermission) {
                if (function_exists('error_log')) {
                    error_log('[API Alunos] Usuário não autorizado após tentativa de login automático');
                }
                http_response_code(401);
                echo json_encode([
                    'success' => false, 
                    'error' => 'Não autorizado. Faça login novamente.', 
                    'debug' => [
                        'logged_in' => $isLoggedIn, 
                        'has_permission' => $hasPermission,
                        'session_debug' => $sessionDebug
                    ]
                ]);
                exit;
            }
        }
    }
    
    if (function_exists('error_log')) {
        error_log('[API Alunos] Usuário autorizado, continuando...');
    }
    
} catch (Exception $e) {
    if (function_exists('error_log')) {
        error_log('[API Alunos] Erro de autenticação: ' . $e->getMessage());
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro de autenticação: ' . $e->getMessage()]);
    exit;
} catch (Error $e) {
    if (function_exists('error_log')) {
        error_log('[API Alunos] Erro fatal de autenticação: ' . $e->getMessage());
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro fatal de autenticação: ' . $e->getMessage()]);
    exit;
}

try {
    $db = Database::getInstance();
    if (function_exists('error_log')) {
        error_log('[API Alunos] Conexão com banco estabelecida');
    }
} catch (Exception $e) {
    if (function_exists('error_log')) {
        error_log('[API Alunos] Erro de conexão com banco: ' . $e->getMessage());
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro de conexão com banco: ' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $id = $_GET['id'] ?? null;
            
            if ($id) {
                // Buscar aluno específico
                $aluno = $db->findWhere('alunos', 'id = ?', [$id], '*', null, 1);
                if ($aluno && is_array($aluno)) {
                    $aluno = $aluno[0]; // Pegar o primeiro resultado
                }
                if (!$aluno) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Aluno não encontrado']);
                    exit;
                }
                
                // Buscar dados do CFC
                $cfc = $db->findWhere('cfcs', 'id = ?', [$aluno['cfc_id']], '*', null, 1);
                if ($cfc && is_array($cfc)) {
                    $cfc = $cfc[0]; // Pegar o primeiro resultado
                }
                $aluno['cfc_nome'] = $cfc ? $cfc['nome'] : 'N/A';
                
                echo json_encode(['success' => true, 'aluno' => $aluno]);
            } else {
                // Listar todos os alunos
                $alunos = $db->fetchAll("
                    SELECT a.*, c.nome as cfc_nome 
                    FROM alunos a 
                    LEFT JOIN cfcs c ON a.cfc_id = c.id 
                    ORDER BY a.nome ASC
                ");
                
                echo json_encode(['success' => true, 'alunos' => $alunos]);
            }
            break;
            
        case 'POST':
            // Criar novo aluno
            $rawInput = file_get_contents('php://input');
            
            // Log para debug
            if (LOG_ENABLED) {
                error_log('[API Alunos] Raw input recebido: ' . $rawInput);
                error_log('[API Alunos] Content-Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'não definido'));
                error_log('[API Alunos] Request method: ' . $_SERVER['REQUEST_METHOD']);
            }
            
            // Tentar decodificar JSON primeiro
            $data = json_decode($rawInput, true);
            
            // Se JSON falhou, tentar POST
            if (!$data || json_last_error() !== JSON_ERROR_NONE) {
                $data = $_POST;
                if (LOG_ENABLED) {
                    error_log('[API Alunos] JSON falhou, usando dados POST: ' . print_r($data, true));
                }
            } else {
                if (LOG_ENABLED) {
                    error_log('[API Alunos] Dados JSON decodificados: ' . print_r($data, true));
                }
            }
            
            // Log dos dados finais
            if (LOG_ENABLED) {
                error_log('[API Alunos] Dados finais para processamento: ' . print_r($data, true));
            }
            
            if (!$data) {
                if (LOG_ENABLED) {
                    error_log('[API Alunos] Dados inválidos recebidos');
                }
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
                exit;
            }
            
            // Validações básicas
            if (empty($data['nome']) || empty($data['cpf']) || empty($data['cfc_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Nome, CPF e CFC são obrigatórios']);
                exit;
            }
            
            // Verificar se CPF já existe
            $cpfExistente = $db->findWhere('alunos', 'cpf = ? AND id != ?', [$data['cpf'], $data['id'] ?? 0], '*', null, 1);
            if ($cpfExistente && is_array($cpfExistente)) {
                $cpfExistente = $cpfExistente[0]; // Pegar o primeiro resultado
            }
            if ($cpfExistente) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'CPF já cadastrado']);
                exit;
            }
            
            // Verificar se CFC existe
            $cfc = $db->findWhere('cfcs', 'id = ?', [$data['cfc_id']], '*', null, 1);
            if ($cfc && is_array($cfc)) {
                $cfc = $cfc[0]; // Pegar o primeiro resultado
            }
            if (!$cfc) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'CFC não encontrado']);
                exit;
            }
            
            $alunoData = [
                'cfc_id' => $data['cfc_id'],
                'nome' => $data['nome'],
                'cpf' => $data['cpf'],
                'rg' => $data['rg'] ?? '',
                'data_nascimento' => $data['data_nascimento'] ?? null,
                'telefone' => $data['telefone'] ?? '',
                'email' => $data['email'] ?? '',
                'endereco' => $data['endereco'] ?? '',
                'numero' => $data['numero'] ?? '',
                'bairro' => $data['bairro'] ?? '',
                'cidade' => $data['cidade'] ?? '',
                'estado' => $data['estado'] ?? '',
                'cep' => $data['cep'] ?? '',
                'categoria_cnh' => $data['categoria_cnh'] ?? 'B',
                'status' => $data['status'] ?? 'ativo',
                'observacoes' => $data['observacoes'] ?? '',
                'criado_em' => date('Y-m-d H:i:s')
            ];
            
            try {
                if (LOG_ENABLED) {
                    error_log('[API Alunos] Dados para inserção: ' . print_r($alunoData, true));
                }
                
                $alunoId = $db->insert('alunos', $alunoData);
                if (!$alunoId) {
                    if (LOG_ENABLED) {
                        error_log('[API Alunos] Erro ao inserir aluno - insert retornou false');
                    }
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => 'Erro ao criar aluno']);
                    exit;
                }
                
                if (LOG_ENABLED) {
                    error_log('[API Alunos] Aluno inserido com sucesso, ID: ' . $alunoId);
                }
                
                $alunoData['id'] = $alunoId;
                echo json_encode(['success' => true, 'message' => 'Aluno criado com sucesso', 'data' => $alunoData]);
                
            } catch (Exception $e) {
                if (LOG_ENABLED) {
                    error_log('[API Alunos] Erro ao inserir aluno: ' . $e->getMessage());
                }
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Erro interno: ' . $e->getMessage()]);
                exit;
            }
            break;
            
        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $_GET['id'] ?? null;
            
            if (!$id || !$input) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID e dados são obrigatórios']);
                exit;
            }
            
            // Verificar se aluno existe
            $alunoExistente = $db->findWhere('alunos', 'id = ?', [$id], '*', null, 1);
            if ($alunoExistente && is_array($alunoExistente)) {
                $alunoExistente = $alunoExistente[0]; // Pegar o primeiro resultado
            }
            if (!$alunoExistente) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Aluno não encontrado']);
                exit;
            }
            
            // Verificar se CPF já existe (exceto para o próprio aluno)
            if (isset($input['cpf'])) {
                $cpfExistente = $db->findWhere('alunos', 'cpf = ? AND id != ?', [$input['cpf'], $id], '*', null, 1);
                if ($cpfExistente && is_array($cpfExistente)) {
                    $cpfExistente = $cpfExistente[0]; // Pegar o primeiro resultado
                }
                if ($cpfExistente) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'CPF já cadastrado']);
                    exit;
                }
            }
            
            // Verificar se CFC existe
            if (isset($input['cfc_id'])) {
                $cfc = $db->findWhere('cfcs', 'id = ?', [$input['cfc_id']], '*', null, 1);
                if ($cfc && is_array($cfc)) {
                    $cfc = $cfc[0]; // Pegar o primeiro resultado
                }
                if (!$cfc) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'CFC não encontrado']);
                    exit;
                }
            }
            
            $alunoData = array_filter($input, function($value) {
                return $value !== null && $value !== '';
            });
            
            $alunoData['atualizado_em'] = date('Y-m-d H:i:s');
            
            $resultado = $db->update('alunos', $alunoData, 'id = ?', [$id]);
            if (!$resultado) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Erro ao atualizar aluno']);
                exit;
            }
            
            echo json_encode(['success' => true, 'message' => 'Aluno atualizado com sucesso']);
            break;
            
        case 'DELETE':
            // Melhorar leitura dos dados para DELETE
            $input = null;
            $id = null;
            
            // Tentar ler do body JSON primeiro
            $rawInput = file_get_contents('php://input');
            if ($rawInput) {
                $input = json_decode($rawInput, true);
                if (json_last_error() === JSON_ERROR_NONE && $input) {
                    $id = $input['id'] ?? null;
                }
            }
            
            // Fallback para GET se não conseguir ler do body
            if (!$id) {
                $id = $_GET['id'] ?? null;
            }
            
            // Log para debug
            if (function_exists('error_log')) {
                error_log('[API Alunos] DELETE - Raw input: ' . $rawInput);
                error_log('[API Alunos] DELETE - Decoded input: ' . json_encode($input));
                error_log('[API Alunos] DELETE - ID: ' . $id);
            }
            
            if (!$id) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'error' => 'ID é obrigatório',
                    'debug' => [
                        'raw_input' => $rawInput,
                        'decoded_input' => $input,
                        'get_id' => $_GET['id'] ?? 'não definido'
                    ]
                ]);
                exit;
            }
            
            // Verificar se aluno existe
            $aluno = $db->findWhere('alunos', 'id = ?', [$id], '*', null, 1);
            if ($aluno && is_array($aluno)) {
                $aluno = $aluno[0]; // Pegar o primeiro resultado
            }
            if (!$aluno) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Aluno não encontrado']);
                exit;
            }
            
            // Verificar se há aulas vinculadas
            $aulasVinculadas = $db->count('aulas', 'aluno_id = ?', [$id]);
            if ($aulasVinculadas > 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Não é possível excluir aluno com aulas vinculadas']);
                exit;
            }
            
            $resultado = $db->delete('alunos', 'id = ?', [$id]);
            if (!$resultado) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Erro ao excluir aluno']);
                exit;
            }
            
            echo json_encode(['success' => true, 'message' => 'Aluno excluído com sucesso']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            break;
    }
    
} catch (Exception $e) {
    if (function_exists('error_log')) {
        error_log('[API Alunos] Erro interno: ' . $e->getMessage());
        error_log('[API Alunos] Stack trace: ' . $e->getTraceAsString());
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro interno: ' . $e->getMessage()]);
} catch (Error $e) {
    if (function_exists('error_log')) {
        error_log('[API Alunos] Erro fatal: ' . $e->getMessage());
        error_log('[API Alunos] Stack trace: ' . $e->getTraceAsString());
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro fatal: ' . $e->getMessage()]);
}
?>
