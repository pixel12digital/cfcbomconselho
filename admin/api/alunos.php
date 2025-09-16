<?php
// API para gerenciamento de Alunos
// Configuração para produção
ini_set('display_errors', 0); // Não mostrar erros na tela para API
ini_set('log_errors', 1);
ini_set('html_errors', 0); // Desabilitar formatação HTML de erros

// Limpar qualquer saída anterior
if (ob_get_level()) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');

// Função para garantir resposta JSON válida
function sendJsonResponse($data, $httpCode = 200) {
    http_response_code($httpCode);
    
    // Limpar qualquer saída anterior
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Garantir que não há saída antes do JSON
    $output = json_encode($data, JSON_UNESCAPED_UNICODE);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $output = json_encode([
            'success' => false, 
            'error' => 'Erro ao codificar JSON: ' . json_last_error_msg()
        ], JSON_UNESCAPED_UNICODE);
    }
    
    echo $output;
    exit;
}

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
    
    require_once $basePath . '/includes/CredentialManager.php';
    if (function_exists('error_log')) {
        error_log('[API Alunos] CredentialManager.php carregado');
    }
    
} catch (Exception $e) {
    if (function_exists('error_log')) {
        error_log('[API Alunos] Erro ao carregar includes: ' . $e->getMessage());
    }
    http_response_code(500);
    sendJsonResponse(['success' => false, 'error' => 'Erro de configuração: ' . $e->getMessage()], 500);
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
            error_log('[API Alunos] Usuário não autorizado, verificando permissões específicas...');
        }
        
        // Verificar se tem permissão para gerenciar alunos (admin, secretaria)
        if (!canManageUsers()) {
            if (function_exists('error_log')) {
                error_log('[API Alunos] Usuário não tem permissão para gerenciar alunos');
            }
            sendJsonResponse(['error' => 'Acesso negado - Apenas administradores e atendentes podem gerenciar alunos'], 403);
        }
        
        // Se chegou até aqui, tem permissão
        $isLoggedIn = true;
        $hasPermission = true;
    }
    
    if (function_exists('error_log')) {
        error_log('[API Alunos] Usuário autorizado, continuando...');
    }
    
} catch (Exception $e) {
    if (function_exists('error_log')) {
        error_log('[API Alunos] Erro de autenticação: ' . $e->getMessage());
    }
    sendJsonResponse(['success' => false, 'error' => 'Erro de autenticação: ' . $e->getMessage()], 500);
} catch (Error $e) {
    if (function_exists('error_log')) {
        error_log('[API Alunos] Erro fatal de autenticação: ' . $e->getMessage());
    }
    sendJsonResponse(['success' => false, 'error' => 'Erro fatal de autenticação: ' . $e->getMessage()], 500);
}

try {
    $db = Database::getInstance();
    if (function_exists('error_log')) {
        error_log('[API Alunos] Conexão com banco estabelecida');
    }
    
    // Verificar e adicionar campo tipo_servico se não existir
    try {
        $result = $db->query("SHOW COLUMNS FROM alunos LIKE 'tipo_servico'");
        $rows = $result->fetchAll();
        if (!$result || count($rows) === 0) {
            if (function_exists('error_log')) {
                error_log('[API Alunos] Campo tipo_servico não existe, adicionando...');
            }
            
            // Adicionar campo tipo_servico
            $db->query("ALTER TABLE alunos ADD COLUMN tipo_servico VARCHAR(50) NOT NULL DEFAULT 'primeira_habilitacao' AFTER categoria_cnh");
            
            // Atualizar registros existentes
            $db->query("UPDATE alunos SET tipo_servico = 'primeira_habilitacao' WHERE categoria_cnh IN ('A', 'B', 'AB', 'ACC')");
            $db->query("UPDATE alunos SET tipo_servico = 'adicao' WHERE categoria_cnh IN ('C', 'D', 'E')");
            $db->query("UPDATE alunos SET tipo_servico = 'mudanca' WHERE categoria_cnh IN ('AC', 'AD', 'AE', 'BC', 'BD', 'BE', 'CD', 'CE', 'DE')");
            
            if (function_exists('error_log')) {
                error_log('[API Alunos] Campo tipo_servico adicionado com sucesso');
            }
        }
    } catch (Exception $e) {
        if (function_exists('error_log')) {
            error_log('[API Alunos] Erro ao verificar/adicionar campo tipo_servico: ' . $e->getMessage());
        }
        // Continuar mesmo com erro, pois pode ser problema de permissão
    }
    
} catch (Exception $e) {
    if (function_exists('error_log')) {
        error_log('[API Alunos] Erro de conexão com banco: ' . $e->getMessage());
    }
    sendJsonResponse(['success' => false, 'error' => 'Erro de conexão com banco: ' . $e->getMessage()], 500);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $id = $_GET['id'] ?? null;
            
            if (LOG_ENABLED) {
                error_log('[API Alunos] GET - ID solicitado: ' . $id);
            }
            
            if ($id) {
                // Buscar aluno específico
                $aluno = $db->findWhere('alunos', 'id = ?', [$id], '*', null, 1);
                if ($aluno && is_array($aluno)) {
                    $aluno = $aluno[0]; // Pegar o primeiro resultado
                }
                
                if (LOG_ENABLED) {
                    error_log('[API Alunos] GET - Aluno encontrado: ' . ($aluno ? 'sim' : 'não'));
                    if ($aluno) {
                        error_log('[API Alunos] GET - Dados do aluno: ' . json_encode($aluno));
                    }
                }
                
                if (!$aluno) {
                    sendJsonResponse(['success' => false, 'error' => 'Aluno não encontrado'], 404);
                }
                
                // Buscar dados do CFC
                $cfc = $db->findWhere('cfcs', 'id = ?', [$aluno['cfc_id']], '*', null, 1);
                if ($cfc && is_array($cfc)) {
                    $cfc = $cfc[0]; // Pegar o primeiro resultado
                }
                $aluno['cfc_nome'] = $cfc ? $cfc['nome'] : 'N/A';
                
                $response = ['success' => true, 'aluno' => $aluno];
                
                if (LOG_ENABLED) {
                    error_log('[API Alunos] GET - Resposta final: ' . json_encode($response));
                }
                
                sendJsonResponse($response);
            } else {
                // Listar todos os alunos
                $alunos = $db->fetchAll("
                    SELECT a.*, c.nome as cfc_nome 
                    FROM alunos a 
                    LEFT JOIN cfcs c ON a.cfc_id = c.id 
                    ORDER BY a.nome ASC
                ");
                
                sendJsonResponse(['success' => true, 'alunos' => $alunos]);
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
                sendJsonResponse(['success' => false, 'error' => 'Dados inválidos'], 400);
            }
            
            // Validações básicas
            if (empty($data['nome']) || empty($data['cpf']) || empty($data['cfc_id']) || empty($data['categoria_cnh'])) {
                if (LOG_ENABLED) {
                    error_log('[API Alunos] Validação falhou - campos obrigatórios: ' . print_r([
                        'nome' => !empty($data['nome']),
                        'cpf' => !empty($data['cpf']),
                        'cfc_id' => !empty($data['cfc_id']),
                        'categoria_cnh' => !empty($data['categoria_cnh']),
                        'tipo_servico' => !empty($data['tipo_servico'])
                    ], true));
                }
                sendJsonResponse(['success' => false, 'error' => 'Nome, CPF, CFC e Categoria CNH são obrigatórios'], 400);
            }
            
            // Se tipo_servico não foi enviado, determinar baseado na categoria
            if (empty($data['tipo_servico']) && !empty($data['categoria_cnh'])) {
                $categoria = $data['categoria_cnh'];
                if (in_array($categoria, ['A', 'B', 'AB', 'ACC'])) {
                    $data['tipo_servico'] = 'primeira_habilitacao';
                } elseif (in_array($categoria, ['C', 'D', 'E'])) {
                    $data['tipo_servico'] = 'adicao';
                } else {
                    $data['tipo_servico'] = 'mudanca';
                }
                
                if (LOG_ENABLED) {
                    error_log('[API Alunos] Tipo de serviço determinado automaticamente: ' . $data['tipo_servico'] . ' para categoria: ' . $categoria);
                }
            }
            
            // Verificar se CPF já existe
            $cpfExistente = $db->findWhere('alunos', 'cpf = ? AND id != ?', [$data['cpf'], $data['id'] ?? 0], '*', null, 1);
            if ($cpfExistente && is_array($cpfExistente)) {
                $cpfExistente = $cpfExistente[0]; // Pegar o primeiro resultado
            }
            if ($cpfExistente) {
                if (LOG_ENABLED) {
                    error_log('[API Alunos] CPF já existe: ' . $data['cpf']);
                }
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
                'naturalidade' => $data['naturalidade'] ?? '',
                'nacionalidade' => $data['nacionalidade'] ?? 'Brasileira',
                'telefone' => $data['telefone'] ?? '',
                'email' => $data['email'] ?? '',
                'endereco' => $data['endereco'] ?? '',
                'numero' => $data['numero'] ?? '',
                'bairro' => $data['bairro'] ?? '',
                'cidade' => $data['cidade'] ?? '',
                'estado' => $data['estado'] ?? '',
                'cep' => $data['cep'] ?? '',
                'tipo_servico' => $data['tipo_servico'] ?? '',
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
                
                // Criar credenciais automáticas para o aluno
                if (LOG_ENABLED) {
                    error_log('[API Alunos] Criando credenciais automáticas para aluno ID: ' . $alunoId);
                }
                
                $credentials = CredentialManager::createStudentCredentials([
                    'aluno_id' => $alunoId,
                    'nome' => $alunoData['nome'],
                    'cpf' => $alunoData['cpf'],
                    'email' => $alunoData['email']
                ]);
                
                if ($credentials['success']) {
                    if (LOG_ENABLED) {
                        error_log('[API Alunos] Credenciais criadas com sucesso para aluno ID: ' . $alunoId);
                    }
                    
                    // Enviar credenciais por email (simulado)
                    CredentialManager::sendCredentials(
                        $credentials['cpf'], 
                        $credentials['senha_temporaria'], 
                        'aluno'
                    );
                } else {
                    if (LOG_ENABLED) {
                        error_log('[API Alunos] Erro ao criar credenciais: ' . $credentials['message']);
                    }
                }
                
                $alunoData['id'] = $alunoId;
                $response = [
                    'success' => true, 
                    'message' => 'Aluno criado com sucesso', 
                    'data' => $alunoData
                ];
                
                if ($credentials['success']) {
                    $response['credentials'] = [
                        'cpf' => $credentials['cpf'],
                        'senha_temporaria' => $credentials['senha_temporaria'],
                        'message' => 'Credenciais criadas automaticamente'
                    ];
                }
                
                sendJsonResponse($response);
                
            } catch (Exception $e) {
                if (LOG_ENABLED) {
                    error_log('[API Alunos] Erro ao inserir aluno: ' . $e->getMessage());
                }
                sendJsonResponse(['success' => false, 'error' => 'Erro interno: ' . $e->getMessage()], 500);
            }
            break;
            
        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $_GET['id'] ?? null;
            
            if (LOG_ENABLED) {
                error_log('[API Alunos] PUT - ID recebido: ' . $id);
                error_log('[API Alunos] PUT - Input recebido: ' . json_encode($input));
            }
            
            if (!$id || !$input) {
                if (LOG_ENABLED) {
                    error_log('[API Alunos] PUT - Dados inválidos - ID: ' . $id . ', Input: ' . json_encode($input));
                }
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID e dados são obrigatórios']);
                exit;
            }
            
            // Verificar se aluno existe
            $alunoExistente = $db->findWhere('alunos', 'id = ?', [$id], '*', null, 1);
            if ($alunoExistente && is_array($alunoExistente)) {
                $alunoExistente = $alunoExistente[0]; // Pegar o primeiro resultado
            }
            
            if (LOG_ENABLED) {
                error_log('[API Alunos] PUT - Aluno existente encontrado: ' . ($alunoExistente ? 'sim' : 'não'));
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
                    if (LOG_ENABLED) {
                        error_log('[API Alunos] PUT - CPF já existe: ' . $input['cpf']);
                    }
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
                    if (LOG_ENABLED) {
                        error_log('[API Alunos] PUT - CFC não encontrado: ' . $input['cfc_id']);
                    }
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'CFC não encontrado']);
                    exit;
                }
            }
            
            // Preparar dados para atualização
            $alunoData = array_filter($input, function($value, $key) {
                // Manter campos obrigatórios mesmo se vazios
                if (in_array($key, ['tipo_servico', 'categoria_cnh', 'status'])) {
                    return true;
                }
                return $value !== null && $value !== '';
            }, ARRAY_FILTER_USE_BOTH);
            
            // Remover campos que não devem ser atualizados
            unset($alunoData['id']); // Não atualizar o ID
            unset($alunoData['criado_em']); // Não atualizar data de criação
            unset($alunoData['cfc_nome']); // Campo calculado, não existe na tabela
            unset($alunoData['atualizado_em']); // Campo não existe na tabela
            
            if (LOG_ENABLED) {
                error_log('[API Alunos] PUT - Dados para atualização: ' . json_encode($alunoData));
            }
            
            try {
                $resultado = $db->update('alunos', $alunoData, 'id = ?', [$id]);
                
                if (LOG_ENABLED) {
                    error_log('[API Alunos] PUT - Resultado da atualização: ' . ($resultado ? 'sucesso' : 'falha'));
                }
                
                if (!$resultado) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => 'Erro ao atualizar aluno']);
                    exit;
                }
                
                sendJsonResponse(['success' => true, 'message' => 'Aluno atualizado com sucesso']);
                
            } catch (Exception $e) {
                if (LOG_ENABLED) {
                    error_log('[API Alunos] PUT - Erro na atualização: ' . $e->getMessage());
                    error_log('[API Alunos] PUT - Stack trace: ' . $e->getTraceAsString());
                }
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Erro interno: ' . $e->getMessage()]);
                exit;
            }
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
            
            sendJsonResponse(['success' => true, 'message' => 'Aluno excluído com sucesso']);
            break;
            
        default:
            sendJsonResponse(['success' => false, 'error' => 'Método não permitido'], 405);
    }
    
} catch (Exception $e) {
    if (function_exists('error_log')) {
        error_log('[API Alunos] Erro interno: ' . $e->getMessage());
        error_log('[API Alunos] Stack trace: ' . $e->getTraceAsString());
    }
    sendJsonResponse(['success' => false, 'error' => 'Erro interno: ' . $e->getMessage()], 500);
} catch (Error $e) {
    if (function_exists('error_log')) {
        error_log('[API Alunos] Erro fatal: ' . $e->getMessage());
        error_log('[API Alunos] Stack trace: ' . $e->getTraceAsString());
    }
    sendJsonResponse(['success' => false, 'error' => 'Erro fatal: ' . $e->getMessage()], 500);
}
?>
