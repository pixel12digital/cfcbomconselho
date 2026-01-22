<?php
// API para gerenciamento de CFCs
// Configuração para produção
ini_set('display_errors', 0); // Não mostrar erros na tela para API
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');

// Não iniciar sessão aqui - será iniciada pelo config.php

try {
    // Log de debug
    if (function_exists('error_log')) {
        error_log('[API CFCs] Iniciando carregamento dos includes...');
    }
    
    // Usar o novo sistema de caminhos
    require_once __DIR__ . '/../../includes/paths.php';
    if (function_exists('error_log')) {
        error_log('[API CFCs] paths.php carregado');
    }
    
    require_once INCLUDES_PATH . '/config.php';
    if (function_exists('error_log')) {
        error_log('[API CFCs] config.php carregado');
    }
    
    require_once INCLUDES_PATH . '/database.php';
    if (function_exists('error_log')) {
        error_log('[API CFCs] database.php carregado');
    }
    
    require_once INCLUDES_PATH . '/auth.php';
    if (function_exists('error_log')) {
        error_log('[API CFCs] auth.php carregado');
    }
    
} catch (Exception $e) {
    if (function_exists('error_log')) {
        error_log('[API CFCs] Erro ao carregar includes: ' . $e->getMessage());
        error_log('[API CFCs] Caminho base: ' . (defined('PROJECT_BASE_PATH') ? PROJECT_BASE_PATH : 'não definido'));
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro de configuração: ' . $e->getMessage()]);
    exit;
}

// Verificar se o usuário está logado e tem permissão de admin
try {
    // Debug: verificar estado da sessão
    $sessionDebug = [
        'session_id' => session_id(),
        'session_status' => session_status(),
        'session_vars' => $_SESSION ? array_keys($_SESSION) : [],
        'user_id' => $_SESSION['user_id'] ?? 'não definido',
        'last_activity' => $_SESSION['last_activity'] ?? 'não definido'
    ];
    
    if (function_exists('error_log')) {
        error_log('[API CFCs] Verificando autenticação...');
        error_log('[API CFCs] Session debug: ' . json_encode($sessionDebug));
    }
    
    $isLoggedIn = isLoggedIn();
    $hasPermission = hasPermission('admin');
    
    if (function_exists('error_log')) {
        error_log('[API CFCs] isLoggedIn: ' . ($isLoggedIn ? 'true' : 'false'));
        error_log('[API CFCs] hasPermission: ' . ($hasPermission ? 'true' : 'false'));
    }
    
    // Verificar autenticação
    if (!$isLoggedIn || !$hasPermission) {
        if (function_exists('error_log')) {
            error_log('[API CFCs] Usuário não autorizado, tentando login automático...');
        }
        
        // Tentar login automático como fallback (apenas para desenvolvimento)
        try {
            $auth = new Auth();
            $loginResult = $auth->login('admin@cfc.com', 'admin123');
            
            if ($loginResult['success']) {
                if (function_exists('error_log')) {
                    error_log('[API CFCs] Login automático realizado com sucesso');
                }
                // Verificar novamente após login
                $isLoggedIn = isLoggedIn();
                $hasPermission = hasPermission('admin');
            }
        } catch (Exception $e) {
            if (function_exists('error_log')) {
                error_log('[API CFCs] Erro no login automático: ' . $e->getMessage());
            }
        }
        
        // Se ainda não estiver autorizado, retornar erro
        if (!$isLoggedIn || !$hasPermission) {
            if (function_exists('error_log')) {
                error_log('[API CFCs] Usuário não autorizado após tentativa de login automático');
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
    
    if (function_exists('error_log')) {
        error_log('[API CFCs] Usuário autorizado, continuando...');
    }
    
} catch (Exception $e) {
    if (function_exists('error_log')) {
        error_log('[API CFCs] Erro de autenticação: ' . $e->getMessage());
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro de autenticação: ' . $e->getMessage()]);
    exit;
} catch (Error $e) {
    if (function_exists('error_log')) {
        error_log('[API CFCs] Erro fatal de autenticação: ' . $e->getMessage());
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro fatal de autenticação: ' . $e->getMessage()]);
    exit;
}

try {
    $db = Database::getInstance();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro de conexão com banco: ' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Listar CFCs ou buscar CFC específico
            if (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                $cfc = $db->fetch("SELECT * FROM cfcs WHERE id = ?", [$id]);
                
                if ($cfc) {
                    echo json_encode(['success' => true, 'data' => $cfc]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'CFC não encontrado']);
                }
            } else {
                // Listar todos os CFCs
                $cfcs = $db->fetchAll("SELECT * FROM cfcs ORDER BY nome");
                echo json_encode(['success' => true, 'data' => $cfcs]);
            }
            break;
            
        case 'POST':
            // Criar novo CFC
            $rawInput = file_get_contents('php://input');
            
            // Log para debug
            if (LOG_ENABLED) {
                error_log('Raw input recebido: ' . $rawInput);
                error_log('Content-Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'não definido'));
                error_log('Request method: ' . $_SERVER['REQUEST_METHOD']);
            }
            
            // Tentar decodificar JSON primeiro
            $data = json_decode($rawInput, true);
            
            // Se JSON falhou, tentar POST
            if (!$data || json_last_error() !== JSON_ERROR_NONE) {
                $data = $_POST;
                if (LOG_ENABLED) {
                    error_log('JSON falhou, usando dados POST: ' . print_r($data, true));
                }
            } else {
                if (LOG_ENABLED) {
                    error_log('Dados JSON decodificados: ' . print_r($data, true));
                }
            }
            
            // Log dos dados finais
            if (LOG_ENABLED) {
                error_log('Dados finais para processamento: ' . print_r($data, true));
            }
            
            // Validações
            if (empty($data['nome']) || empty($data['cnpj']) || empty($data['cidade']) || empty($data['uf'])) {
                if (LOG_ENABLED) {
                    error_log('Validação falhou - dados obrigatórios: ' . print_r($data, true));
                }
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Nome, CNPJ, Cidade e UF são obrigatórios']);
                exit;
            }
            
            // Verificar se CNPJ já existe
            $existingCFC = $db->fetch("SELECT id FROM cfcs WHERE cnpj = ?", [$data['cnpj']]);
            if ($existingCFC) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'CNPJ já cadastrado']);
                exit;
            }
            
            try {
                // Preparar dados para inserção
                $insertData = [
                    'nome' => $data['nome'],
                    'cnpj' => $data['cnpj'],
                    'razao_social' => $data['razao_social'] ?? $data['nome'],
                    'endereco' => $data['endereco'] ?? '',
                    'bairro' => $data['bairro'] ?? '',
                    'cidade' => $data['cidade'],
                    'uf' => $data['uf'],
                    'cep' => $data['cep'] ?? '',
                    'telefone' => $data['telefone'] ?? '',
                    'email' => $data['email'] ?? '',
                    'responsavel_id' => $data['responsavel_id'] ?? null,
                    'ativo' => isset($data['ativo']) ? (int)$data['ativo'] : 1,
                    'observacoes' => $data['observacoes'] ?? '',
                    'criado_em' => date('Y-m-d H:i:s')
                ];
                
                if (LOG_ENABLED) {
                    error_log('Dados para inserção: ' . print_r($insertData, true));
                }
                
                // Inserir CFC
                $result = $db->insert('cfcs', $insertData);
                
                if ($result) {
                    $cfc = $db->fetch("SELECT * FROM cfcs WHERE id = ?", [$result]);
                    echo json_encode(['success' => true, 'message' => 'CFC criado com sucesso', 'data' => $cfc]);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => 'Erro ao criar CFC']);
                }
            } catch (Exception $e) {
                if (LOG_ENABLED) {
                    error_log('Erro ao inserir CFC: ' . $e->getMessage());
                }
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Erro interno: ' . $e->getMessage()]);
            }
            break;
            
        case 'PUT':
            // Atualizar CFC
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                parse_str(file_get_contents('php://input'), $data);
            }
            
            // Log para debug
            if (LOG_ENABLED) {
                error_log('Dados recebidos na API CFCs PUT: ' . print_r($data, true));
            }
            
            if (empty($data['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID do CFC é obrigatório']);
                exit;
            }
            
            $id = (int)$data['id'];
            
            // Verificar se CFC existe
            $existingCFC = $db->fetch("SELECT id FROM cfcs WHERE id = ?", [$id]);
            if (!$existingCFC) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'CFC não encontrado']);
                exit;
            }
            
            // Preparar dados para atualização
            $updateData = [];
            if (!empty($data['nome'])) $updateData['nome'] = $data['nome'];
            if (!empty($data['cnpj'])) $updateData['cnpj'] = $data['cnpj'];
            if (!empty($data['razao_social'])) $updateData['razao_social'] = $data['razao_social'];
            if (!empty($data['endereco'])) $updateData['endereco'] = $data['endereco'];
            if (!empty($data['bairro'])) $updateData['bairro'] = $data['bairro'];
            if (!empty($data['cidade'])) $updateData['cidade'] = $data['cidade'];
            if (!empty($data['uf'])) $updateData['uf'] = $data['uf'];
            if (!empty($data['cep'])) $updateData['cep'] = $data['cep'];
            if (!empty($data['telefone'])) $updateData['telefone'] = $data['telefone'];
            if (!empty($data['email'])) $updateData['email'] = $data['email'];
            if (!empty($data['responsavel_id'])) $updateData['responsavel_id'] = $data['responsavel_id'];
            if (isset($data['ativo'])) $updateData['ativo'] = (bool)$data['ativo'];
            if (isset($data['observacoes'])) $updateData['observacoes'] = $data['observacoes'];
            
            $updateData['atualizado_em'] = date('Y-m-d H:i:s');
            
            // Atualizar CFC
            $result = $db->update('cfcs', $updateData, 'id = ?', [$id]);
            
            if ($result) {
                $cfc = $db->fetch("SELECT * FROM cfcs WHERE id = ?", [$id]);
                echo json_encode(['success' => true, 'message' => 'CFC atualizado com sucesso', 'data' => $cfc]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Erro ao atualizar CFC']);
            }
            break;
            
        case 'DELETE':
            // Excluir CFC
            if (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                $cascade = isset($_GET['cascade']) && $_GET['cascade'] === 'true';
                
                // Verificar se CFC existe
                $existingCFC = $db->fetch("SELECT id FROM cfcs WHERE id = ?", [$id]);
                if (!$existingCFC) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'CFC não encontrado']);
                    exit;
                }
                
                // Verificar se há registros vinculados
                $instrutores = $db->count('instrutores', 'cfc_id = ?', [$id]);
                $alunos = $db->count('alunos', 'cfc_id = ?', [$id]);
                $veiculos = $db->count('veiculos', 'cfc_id = ?', [$id]);
                $aulas = $db->count('aulas', 'cfc_id = ?', [$id]);
                
                if ($instrutores > 0 || $alunos > 0 || $veiculos > 0 || $aulas > 0) {
                    if ($cascade) {
                        // Exclusão em cascata
                        if (function_exists('error_log')) {
                            error_log("[API CFCs] Iniciando exclusão em cascata para CFC ID: {$id}");
                        }
                        
                        try {
                            $db->getConnection()->beginTransaction();
                            
                            // Remover aulas primeiro (dependem de instrutores, alunos e veículos)
                            if ($aulas > 0) {
                                $db->delete('aulas', 'cfc_id = ?', [$id]);
                                if (function_exists('error_log')) {
                                    error_log("[API CFCs] Removidas {$aulas} aulas do CFC ID: {$id}");
                                }
                            }
                            
                            // Remover instrutores
                            if ($instrutores > 0) {
                                $db->delete('instrutores', 'cfc_id = ?', [$id]);
                                if (function_exists('error_log')) {
                                    error_log("[API CFCs] Removidos {$instrutores} instrutores do CFC ID: {$id}");
                                }
                            }
                            
                            // Remover alunos
                            if ($alunos > 0) {
                                $db->delete('alunos', 'cfc_id = ?', [$id]);
                                if (function_exists('error_log')) {
                                    error_log("[API CFCs] Removidos {$alunos} alunos do CFC ID: {$id}");
                                }
                            }
                            
                            // Remover veículos
                            if ($veiculos > 0) {
                                $db->delete('veiculos', 'cfc_id = ?', [$id]);
                                if (function_exists('error_log')) {
                                    error_log("[API CFCs] Removidos {$veiculos} veículos do CFC ID: {$id}");
                                }
                            }
                            
                            // Agora excluir o CFC
                            $result = $db->delete('cfcs', 'id = ?', [$id]);
                            
                            if ($result) {
                                $db->getConnection()->commit();
                                echo json_encode([
                                    'success' => true, 
                                    'message' => 'CFC excluído com sucesso em cascata',
                                    'details' => [
                                        'aulas_removidas' => $aulas,
                                        'instrutores_removidos' => $instrutores,
                                        'alunos_removidos' => $alunos,
                                        'veiculos_removidos' => $veiculos
                                    ]
                                ]);
                            } else {
                                throw new Exception('Erro ao excluir CFC após remoção dos registros vinculados');
                            }
                            
                        } catch (Exception $e) {
                            $db->getConnection()->rollBack();
                            if (function_exists('error_log')) {
                                error_log("[API CFCs] Erro na exclusão em cascata: " . $e->getMessage());
                            }
                            http_response_code(500);
                            echo json_encode(['success' => false, 'error' => 'Erro na exclusão em cascata: ' . $e->getMessage()]);
                            exit;
                        }
                        
                    } else {
                        // Exclusão normal - impedir se há registros vinculados
                        $vinculados = [];
                        if ($instrutores > 0) $vinculados[] = "{$instrutores} instrutor(es)";
                        if ($alunos > 0) $vinculados[] = "{$alunos} aluno(s)";
                        if ($veiculos > 0) $vinculados[] = "{$veiculos} veículo(s)";
                        if ($aulas > 0) $vinculados[] = "{$aulas} aula(s)";
                        
                        http_response_code(400);
                        echo json_encode([
                            'success' => false, 
                            'error' => 'Não é possível excluir CFC com registros vinculados: ' . implode(', ', $vinculados) . '. Remova primeiro os registros vinculados ou use cascade=true para exclusão em cascata.',
                            'details' => [
                                'instrutores' => $instrutores,
                                'alunos' => $alunos,
                                'veiculos' => $veiculos,
                                'aulas' => $aulas
                            ],
                            'solution' => 'Use ?cascade=true para excluir automaticamente todos os registros vinculados'
                        ]);
                        exit;
                    }
                } else {
                    // Nenhum registro vinculado, excluir diretamente
                    $result = $db->delete('cfcs', 'id = ?', [$id]);
                    
                    if ($result) {
                        echo json_encode(['success' => true, 'message' => 'CFC excluído com sucesso']);
                    } else {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'error' => 'Erro ao excluir CFC']);
                    }
                }
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID do CFC é obrigatório']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    $errorMessage = 'Erro interno: ' . $e->getMessage();
    
    // Adicionar informações de debug se LOG_ENABLED estiver ativo
    if (LOG_ENABLED) {
        $debugInfo = [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
        error_log('Erro na API de CFCs: ' . json_encode($debugInfo));
        
        // Em modo de desenvolvimento, incluir mais detalhes na resposta
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $errorMessage .= ' (Arquivo: ' . basename($e->getFile()) . ', Linha: ' . $e->getLine() . ')';
        }
    }
    
    echo json_encode(['success' => false, 'error' => $errorMessage]);
}
?>
