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
    
    // Verificar e adicionar campo renach se não existir
    try {
        $result = $db->query("SHOW COLUMNS FROM alunos LIKE 'renach'");
        $rows = $result->fetchAll();
        if (!$result || count($rows) === 0) {
            if (function_exists('error_log')) {
                error_log('[API Alunos] Campo renach não existe, adicionando...');
            }
            
            // Adicionar campo renach
            $db->query("ALTER TABLE alunos ADD COLUMN renach VARCHAR(11) DEFAULT '' AFTER rg");
            
            if (function_exists('error_log')) {
                error_log('[API Alunos] Campo renach adicionado com sucesso');
            }
        }
    } catch (Exception $e) {
        if (function_exists('error_log')) {
            error_log('[API Alunos] Erro ao verificar/adicionar campo renach: ' . $e->getMessage());
        }
        // Continuar mesmo com erro, pois pode ser problema de permissão
    }
    
    // Verificar e adicionar campo foto se não existir
    try {
        $result = $db->query("SHOW COLUMNS FROM alunos LIKE 'foto'");
        $rows = $result->fetchAll();
        if (!$result || count($rows) === 0) {
            if (function_exists('error_log')) {
                error_log('[API Alunos] Campo foto não existe, adicionando...');
            }
            
            // Adicionar campo foto
            $db->query("ALTER TABLE alunos ADD COLUMN foto VARCHAR(255) DEFAULT '' AFTER renach");
            
            if (function_exists('error_log')) {
                error_log('[API Alunos] Campo foto adicionado com sucesso');
            }
        }
    } catch (Exception $e) {
        if (function_exists('error_log')) {
            error_log('[API Alunos] Erro ao verificar/adicionar campo foto: ' . $e->getMessage());
        }
        // Continuar mesmo com erro, pois pode ser problema de permissão
    }
    
    // Verificar e adicionar campos adicionais se não existirem
    $camposAdicionais = [
        'rg_orgao_emissor' => "VARCHAR(10) DEFAULT '' AFTER rg",
        'rg_uf' => "CHAR(2) DEFAULT '' AFTER rg_orgao_emissor",
        'rg_data_emissao' => "DATE NULL AFTER rg_uf",
        'estado_civil' => "VARCHAR(50) DEFAULT '' AFTER data_nascimento",
        'profissao' => "VARCHAR(100) DEFAULT '' AFTER estado_civil",
        'escolaridade' => "VARCHAR(50) DEFAULT '' AFTER profissao",
        'telefone_secundario' => "VARCHAR(20) DEFAULT '' AFTER telefone",
        'contato_emergencia_nome' => "VARCHAR(100) DEFAULT '' AFTER telefone_secundario",
        'contato_emergencia_telefone' => "VARCHAR(20) DEFAULT '' AFTER contato_emergencia_nome",
        'lgpd_consentimento' => "TINYINT(1) DEFAULT 0 AFTER observacoes",
        'lgpd_consentimento_em' => "DATETIME NULL AFTER lgpd_consentimento",
        'numero_processo' => "VARCHAR(100) DEFAULT NULL AFTER lgpd_consentimento_em",
        'detran_numero' => "VARCHAR(100) DEFAULT NULL AFTER numero_processo",
        'status_matricula' => "VARCHAR(50) DEFAULT '' AFTER detran_numero",
        'processo_situacao' => "VARCHAR(50) DEFAULT '' AFTER status_matricula",
        'status_pagamento' => "VARCHAR(50) DEFAULT 'pendente' AFTER processo_situacao"
    ];
    
    foreach ($camposAdicionais as $campo => $definicao) {
        try {
            $result = $db->query("SHOW COLUMNS FROM alunos LIKE '{$campo}'");
            $rows = $result->fetchAll();
            if (!$result || count($rows) === 0) {
                if (function_exists('error_log')) {
                    error_log("[API Alunos] Campo {$campo} não existe, adicionando...");
                }
                
                $db->query("ALTER TABLE alunos ADD COLUMN {$campo} {$definicao}");
                
                if (function_exists('error_log')) {
                    error_log("[API Alunos] Campo {$campo} adicionado com sucesso");
                }
            }
        } catch (Exception $e) {
            if (function_exists('error_log')) {
                error_log("[API Alunos] Erro ao verificar/adicionar campo {$campo}: " . $e->getMessage());
            }
            // Continuar mesmo com erro, pois pode ser problema de permissão
        }
    }
    
    // Processar upload de foto APENAS se houver arquivo realmente enviado (CREATE)
    $caminhoFoto = '';
    
    if (LOG_ENABLED) {
        error_log('[API Alunos] Verificando upload de foto...');
        error_log('[API Alunos] $_FILES: ' . print_r($_FILES, true));
        error_log('[API Alunos] $_POST: ' . print_r($_POST, true));
    }
    
    // Verificar se há arquivo de foto enviado
    $temFotoNova = isset($_FILES['foto']) && 
                   $_FILES['foto']['error'] === UPLOAD_ERR_OK && 
                   $_FILES['foto']['size'] > 0;
    
    if ($temFotoNova) {
        // Há arquivo realmente enviado - processar upload
        $uploadDir = __DIR__ . '/../uploads/alunos/';
        
        // Criar diretório se não existir
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                if (LOG_ENABLED) {
                    error_log('[API Alunos] CREATE - Erro ao criar diretório: ' . $uploadDir);
                }
                sendJsonResponse(['success' => false, 'error' => 'Erro ao criar diretório de upload'], 500);
            }
        }
        
        // Verificar permissões do diretório
        if (!is_writable($uploadDir)) {
            if (LOG_ENABLED) {
                error_log('[API Alunos] CREATE - Diretório não tem permissão de escrita: ' . $uploadDir);
            }
            sendJsonResponse(['success' => false, 'error' => 'Diretório de upload não tem permissão de escrita'], 500);
        }
        
        $fileInfo = pathinfo($_FILES['foto']['name']);
        $extension = strtolower($fileInfo['extension'] ?? '');
        
        // Validar extensão
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (empty($extension) || !in_array($extension, $allowedExtensions)) {
            if (LOG_ENABLED) {
                error_log('[API Alunos] CREATE - Extensão não permitida: ' . $extension);
            }
            sendJsonResponse(['success' => false, 'error' => 'Formato de arquivo não permitido. Use JPG, PNG, GIF ou WebP.'], 400);
        }
        
        // Validar tamanho (2MB máximo)
        if ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
            if (LOG_ENABLED) {
                error_log('[API Alunos] CREATE - Arquivo muito grande: ' . $_FILES['foto']['size'] . ' bytes');
            }
            sendJsonResponse(['success' => false, 'error' => 'Arquivo muito grande. Máximo 2MB.'], 400);
        }
        
        // Gerar nome único para o arquivo
        $nomeArquivo = 'aluno_' . time() . '_' . uniqid() . '.' . $extension;
        $caminhoCompleto = $uploadDir . $nomeArquivo;
        
        // Mover arquivo
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $caminhoCompleto)) {
            $caminhoFoto = 'admin/uploads/alunos/' . $nomeArquivo;
            if (LOG_ENABLED) {
                error_log('[API Alunos] CREATE - Foto salva com sucesso:');
                error_log('[API Alunos] CREATE - Caminho físico: ' . $caminhoCompleto);
                error_log('[API Alunos] CREATE - Caminho lógico (banco): ' . $caminhoFoto);
                error_log('[API Alunos] CREATE - Arquivo existe? ' . (file_exists($caminhoCompleto) ? 'SIM' : 'NÃO'));
            }
        } else {
            // Erro ao mover arquivo - log detalhado
            $ultimoErro = error_get_last();
            if (LOG_ENABLED) {
                error_log('[API Alunos] CREATE - Erro ao mover arquivo:');
                error_log('[API Alunos] CREATE - tmp_name: ' . $_FILES['foto']['tmp_name']);
                error_log('[API Alunos] CREATE - destino: ' . $caminhoCompleto);
                error_log('[API Alunos] CREATE - Último erro PHP: ' . ($ultimoErro ? $ultimoErro['message'] : 'Nenhum'));
                error_log('[API Alunos] CREATE - is_uploaded_file: ' . (is_uploaded_file($_FILES['foto']['tmp_name']) ? 'SIM' : 'NÃO'));
                error_log('[API Alunos] CREATE - is_writable(dirname): ' . (is_writable(dirname($caminhoCompleto)) ? 'SIM' : 'NÃO'));
            }
            sendJsonResponse(['success' => false, 'error' => 'Erro ao salvar foto. Verifique permissões do diretório.'], 500);
        }
    } else {
        // Não há foto - deixar vazio (será salvo como string vazia no banco)
        if (LOG_ENABLED) {
            error_log('[API Alunos] CREATE - Nenhuma foto enviada');
            if (isset($_FILES['foto'])) {
                error_log('[API Alunos] CREATE - $_FILES[\'foto\'][\'error\']: ' . $_FILES['foto']['error'] . ' (UPLOAD_ERR_NO_FILE=' . UPLOAD_ERR_NO_FILE . ')');
            }
        }
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
                        // Log específico para LGPD e Observações
                        error_log('[API Alunos] GET - lgpd_consentimento: ' . ($aluno['lgpd_consentimento'] ?? 'NÃO DEFINIDO'));
                        error_log('[API Alunos] GET - lgpd_consentimento_em: ' . ($aluno['lgpd_consentimento_em'] ?? 'NÃO DEFINIDO'));
                        error_log('[API Alunos] GET - observacoes: ' . (isset($aluno['observacoes']) ? (strlen($aluno['observacoes']) > 0 ? 'PREENCHIDO (' . strlen($aluno['observacoes']) . ' chars)' : 'VAZIO') : 'NÃO DEFINIDO'));
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
                
                // Buscar matrícula ativa para incluir RENACH da matrícula e outros campos
                try {
                    // Buscar matrícula ativa - incluir todos os campos necessários
                    // Usar try-catch para cada campo individualmente para evitar erro se coluna não existir
                    $matriculaAtiva = $db->fetch("
                        SELECT 
                            renach, 
                            status, 
                            categoria_cnh,
                            tipo_servico,
                            data_fim,
                            processo_numero,
                            processo_numero_detran,
                            processo_situacao,
                            previsao_conclusao,
                            valor_total,
                            forma_pagamento,
                            status_pagamento,
                            aulas_praticas_contratadas,
                            aulas_praticas_extras
                        FROM matriculas 
                        WHERE aluno_id = ? AND status = 'ativa'
                        ORDER BY data_inicio DESC
                        LIMIT 1
                    ", [$id]);
                    
                    if ($matriculaAtiva && is_array($matriculaAtiva)) {
                        // Se houver RENACH na matrícula, usar ele; caso contrário, manter o do aluno
                        if (!empty($matriculaAtiva['renach'])) {
                            $aluno['renach_matricula'] = $matriculaAtiva['renach'];
                        }
                        // Incluir outros dados da matrícula
                        $aluno['status_matricula'] = $matriculaAtiva['status'] ?? null;
                        // Categoria e tipo de serviço da matrícula ativa (prioritários)
                        $aluno['categoria_cnh_matricula'] = $matriculaAtiva['categoria_cnh'] ?? null;
                        $aluno['tipo_servico_matricula'] = $matriculaAtiva['tipo_servico'] ?? null;
                        $aluno['data_conclusao'] = $matriculaAtiva['data_fim'] ?? null;
                        $aluno['numero_processo'] = $matriculaAtiva['processo_numero'] ?? null;
                        $aluno['detran_numero'] = $matriculaAtiva['processo_numero_detran'] ?? null;
                        $aluno['processo_situacao'] = $matriculaAtiva['processo_situacao'] ?? null;
                        $aluno['previsao_conclusao'] = $matriculaAtiva['previsao_conclusao'] ?? null;
                        $aluno['valor_total_matricula'] = $matriculaAtiva['valor_total'] ?? null;
                        // Retornar forma_pagamento com ambos os nomes para compatibilidade
                        // NÃO converter string vazia para null - manter valor exato do banco
                        $formaPagamento = $matriculaAtiva['forma_pagamento'] ?? null;
                        $aluno['forma_pagamento'] = $formaPagamento;
                        $aluno['forma_pagamento_matricula'] = $formaPagamento;
                        
                        // Log para debug
                        error_log('[DEBUG ALUNOS GET] forma_pagamento da matrícula: ' . var_export($formaPagamento, true));
                        $aluno['status_pagamento_matricula'] = $matriculaAtiva['status_pagamento'] ?? null;
                        $aluno['aulas_praticas_contratadas'] = $matriculaAtiva['aulas_praticas_contratadas'] ?? null;
                        $aluno['aulas_praticas_extras'] = $matriculaAtiva['aulas_praticas_extras'] ?? null;
                        
                        // Log para debug (apenas em desenvolvimento)
                        if (LOG_ENABLED) {
                            error_log('[API Alunos] GET - Matrícula carregada: ' . json_encode([
                                'aulas_praticas_contratadas' => $aluno['aulas_praticas_contratadas'],
                                'aulas_praticas_extras' => $aluno['aulas_praticas_extras'],
                                'forma_pagamento' => $aluno['forma_pagamento_matricula']
                            ]));
                        }
                    }
                } catch (Exception $e) {
                    // Se houver erro ao buscar matrícula (tabela não existe, coluna não existe, etc.), continuar sem dados da matrícula
                    if (LOG_ENABLED) {
                        error_log('[API Alunos] GET - Erro ao buscar matrícula ativa: ' . $e->getMessage());
                    }
                    // Não bloquear o retorno do aluno por causa disso
                }
                
                // Decodificar operações se existirem
                if (!empty($aluno['operacoes'])) {
                    $aluno['operacoes'] = json_decode($aluno['operacoes'], true);
                } else {
                    $aluno['operacoes'] = [];
                }
                
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
                
                // Decodificar operações para cada aluno
                foreach ($alunos as &$aluno) {
                    if (!empty($aluno['operacoes'])) {
                        $aluno['operacoes'] = json_decode($aluno['operacoes'], true);
                    } else {
                        $aluno['operacoes'] = [];
                    }
                }
                
                sendJsonResponse(['success' => true, 'alunos' => $alunos]);
            }
            break;
            
        case 'POST':
            // Determinar se é CREATE ou UPDATE baseado na presença de ID na query string
            $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
            $isUpdate = $id !== null && $id > 0;
            
            if (LOG_ENABLED) {
                error_log('[API Alunos] POST - Modo: ' . ($isUpdate ? 'UPDATE (id=' . $id . ')' : 'CREATE'));
            }
            
            // Se for UPDATE, usar lógica de atualização
            if ($isUpdate) {
                // ========== FLUXO DE UPDATE ==========
                
                // Ler dados do FormData (POST)
                $data = $_POST;
                
                if (LOG_ENABLED) {
                    error_log('[API Alunos] POST UPDATE - $_POST: ' . print_r($_POST, true));
                    error_log('[API Alunos] POST UPDATE - $_FILES: ' . print_r($_FILES, true));
                }
                
                // Verificar se aluno existe
                $alunoExistente = $db->findWhere('alunos', 'id = ?', [$id], '*', null, 1);
                if ($alunoExistente && is_array($alunoExistente)) {
                    $alunoExistente = $alunoExistente[0];
                }
                
                if (!$alunoExistente) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Aluno não encontrado']);
                    exit;
                }
                
                // Processar upload de foto APENAS se houver arquivo realmente enviado
                $caminhoFoto = '';
                $uploadWarning = null; // Inicializar variável de warning
                
                // Verificar se há arquivo de foto enviado
                $temFotoNova = isset($_FILES['foto']) && 
                               $_FILES['foto']['error'] === UPLOAD_ERR_OK && 
                               $_FILES['foto']['size'] > 0;
                
                if (LOG_ENABLED) {
                    error_log('[API Alunos] POST UPDATE - Verificando foto:');
                    error_log('[API Alunos] POST UPDATE - isset($_FILES[\'foto\']): ' . (isset($_FILES['foto']) ? 'sim' : 'não'));
                    if (isset($_FILES['foto'])) {
                        error_log('[API Alunos] POST UPDATE - $_FILES[\'foto\'][\'error\']: ' . $_FILES['foto']['error']);
                        error_log('[API Alunos] POST UPDATE - $_FILES[\'foto\'][\'size\']: ' . $_FILES['foto']['size']);
                        error_log('[API Alunos] POST UPDATE - UPLOAD_ERR_OK: ' . UPLOAD_ERR_OK);
                        error_log('[API Alunos] POST UPDATE - UPLOAD_ERR_NO_FILE: ' . UPLOAD_ERR_NO_FILE);
                    }
                    error_log('[API Alunos] POST UPDATE - temFotoNova: ' . ($temFotoNova ? 'SIM' : 'NÃO'));
                }
                
                // ========== CENÁRIO A: Nenhum arquivo enviado (edição apenas de dados) ==========
                if (!$temFotoNova) {
                    // Não processar upload
                    // Não alterar a coluna foto
                    // Nunca retornar erro por isso
                    if (LOG_ENABLED) {
                        error_log('[API Alunos] POST UPDATE - CENÁRIO A: Nenhuma foto nova enviada, mantendo foto existente');
                        if (isset($_FILES['foto'])) {
                            error_log('[API Alunos] POST UPDATE - $_FILES[\'foto\'][\'error\']: ' . $_FILES['foto']['error'] . ' (UPLOAD_ERR_NO_FILE=' . UPLOAD_ERR_NO_FILE . ')');
                        }
                    }
                    // Não definir $caminhoFoto - o campo foto não será atualizado no banco
                } else {
                    // ========== CENÁRIO B/C: Arquivo enviado - processar upload ==========
                    $uploadDir = __DIR__ . '/../uploads/alunos/';
                    $uploadDirRealPath = realpath($uploadDir) ?: $uploadDir;
                    
                    // Log inicial para debug
                    if (LOG_ENABLED) {
                        error_log('[API Alunos] POST UPDATE - Iniciando upload de foto');
                        error_log('[API Alunos] POST UPDATE - $uploadDir: ' . $uploadDir);
                        error_log('[API Alunos] POST UPDATE - $uploadDirRealPath: ' . $uploadDirRealPath);
                        error_log('[API Alunos] POST UPDATE - is_dir($uploadDir): ' . (is_dir($uploadDir) ? 'SIM' : 'NÃO'));
                        error_log('[API Alunos] POST UPDATE - $_FILES[\'foto\'][\'error\']: ' . $_FILES['foto']['error']);
                        error_log('[API Alunos] POST UPDATE - is_uploaded_file: ' . (is_uploaded_file($_FILES['foto']['tmp_name']) ? 'SIM' : 'NÃO'));
                    }
                    
                    // Verificar erros de upload do PHP
                    $uploadError = $_FILES['foto']['error'];
                    if ($uploadError !== UPLOAD_ERR_OK) {
                        $errorMessages = [
                            UPLOAD_ERR_INI_SIZE => 'Arquivo excede upload_max_filesize do PHP',
                            UPLOAD_ERR_FORM_SIZE => 'Arquivo excede MAX_FILE_SIZE do formulário',
                            UPLOAD_ERR_PARTIAL => 'Arquivo foi enviado parcialmente',
                            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi enviado',
                            UPLOAD_ERR_NO_TMP_DIR => 'Diretório temporário não encontrado',
                            UPLOAD_ERR_CANT_WRITE => 'Falha ao escrever arquivo no disco',
                            UPLOAD_ERR_EXTENSION => 'Upload bloqueado por extensão PHP'
                        ];
                        $errorMessage = $errorMessages[$uploadError] ?? 'Erro desconhecido no upload';
                        
                        if (LOG_ENABLED) {
                            error_log('[API Alunos] POST UPDATE - Erro no upload do PHP: ' . $errorMessage . ' (código: ' . $uploadError . ')');
                        }
                        
                        // Não bloquear salvamento dos dados - retornar warning
                        $caminhoFoto = null; // Não atualizar foto
                        $uploadWarning = 'foto_nao_atualizada: ' . $errorMessage;
                    } else {
                        // Criar diretório se não existir
                        if (!is_dir($uploadDir)) {
                            if (!mkdir($uploadDir, 0755, true)) {
                                if (LOG_ENABLED) {
                                    error_log('[API Alunos] POST UPDATE - Erro ao criar diretório: ' . $uploadDir);
                                    error_log('[API Alunos] POST UPDATE - realpath(dirname): ' . realpath(dirname($uploadDir)));
                                    error_log('[API Alunos] POST UPDATE - is_writable(dirname): ' . (is_writable(dirname($uploadDir)) ? 'SIM' : 'NÃO'));
                                }
                                // Não bloquear salvamento dos dados - retornar warning
                                $caminhoFoto = null;
                                $uploadWarning = 'foto_nao_atualizada: Erro ao criar diretório de upload';
                            } else {
                                if (LOG_ENABLED) {
                                    error_log('[API Alunos] POST UPDATE - Diretório criado com sucesso: ' . $uploadDir);
                                }
                            }
                        }
                        
                        // Verificar permissões do diretório (apenas se diretório existe)
                        if (is_dir($uploadDir) && !is_writable($uploadDir)) {
                            if (LOG_ENABLED) {
                                error_log('[API Alunos] POST UPDATE - Diretório não tem permissão de escrita: ' . $uploadDir);
                                error_log('[API Alunos] POST UPDATE - is_writable($uploadDir): ' . (is_writable($uploadDir) ? 'SIM' : 'NÃO'));
                            }
                            // Não bloquear salvamento dos dados - retornar warning
                            $caminhoFoto = null;
                            $uploadWarning = 'foto_nao_atualizada: Diretório de upload não tem permissão de escrita';
                        } else {
                            // Diretório OK - processar upload
                            $fileInfo = pathinfo($_FILES['foto']['name']);
                            $extension = strtolower($fileInfo['extension'] ?? '');
                            
                            // Validar extensão
                            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                            if (empty($extension) || !in_array($extension, $allowedExtensions)) {
                                if (LOG_ENABLED) {
                                    error_log('[API Alunos] POST UPDATE - Extensão não permitida: ' . $extension);
                                }
                                // Não bloquear salvamento dos dados - retornar warning
                                $caminhoFoto = null;
                                $uploadWarning = 'foto_nao_atualizada: Formato de arquivo não permitido. Use JPG, PNG, GIF ou WebP.';
                            } else {
                                // Validar tamanho (2MB máximo)
                                if ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
                                    if (LOG_ENABLED) {
                                        error_log('[API Alunos] POST UPDATE - Arquivo muito grande: ' . $_FILES['foto']['size'] . ' bytes');
                                    }
                                    // Não bloquear salvamento dos dados - retornar warning
                                    $caminhoFoto = null;
                                    $uploadWarning = 'foto_nao_atualizada: Arquivo muito grande. Máximo 2MB.';
                                } else {
                                    // Validações OK - tentar mover arquivo
                                    // Remover foto antiga se existir
                                    if (!empty($alunoExistente['foto'])) {
                                        $caminhoFotoAntiga = __DIR__ . '/../' . $alunoExistente['foto'];
                                        if (file_exists($caminhoFotoAntiga)) {
                                            @unlink($caminhoFotoAntiga);
                                            if (LOG_ENABLED) {
                                                error_log('[API Alunos] POST UPDATE - Foto antiga removida: ' . $caminhoFotoAntiga);
                                            }
                                        }
                                    }
                                    
                                    // Gerar nome único para o arquivo
                                    $nomeArquivo = 'aluno_' . time() . '_' . uniqid() . '.' . $extension;
                                    $caminhoCompleto = $uploadDir . $nomeArquivo;
                                    
                                    // Mover arquivo
                                    if (move_uploaded_file($_FILES['foto']['tmp_name'], $caminhoCompleto)) {
                                        // ========== CENÁRIO B: Upload OK ==========
                                        $caminhoFoto = 'admin/uploads/alunos/' . $nomeArquivo;
                                        if (LOG_ENABLED) {
                                            error_log('[API Alunos] POST UPDATE - CENÁRIO B: Foto salva com sucesso');
                                            error_log('[API Alunos] POST UPDATE - Caminho físico: ' . $caminhoCompleto);
                                            error_log('[API Alunos] POST UPDATE - Caminho lógico (banco): ' . $caminhoFoto);
                                            error_log('[API Alunos] POST UPDATE - Arquivo existe? ' . (file_exists($caminhoCompleto) ? 'SIM' : 'NÃO'));
                                        }
                                    } else {
                                        // ========== CENÁRIO C: move_uploaded_file falhou ==========
                                        $ultimoErro = error_get_last();
                                        if (LOG_ENABLED) {
                                            error_log('[API Alunos] POST UPDATE - CENÁRIO C: Erro ao mover arquivo');
                                            error_log('[API Alunos] POST UPDATE - tmp_name: ' . $_FILES['foto']['tmp_name']);
                                            error_log('[API Alunos] POST UPDATE - destino: ' . $caminhoCompleto);
                                            error_log('[API Alunos] POST UPDATE - Último erro PHP: ' . ($ultimoErro ? $ultimoErro['message'] : 'Nenhum'));
                                            error_log('[API Alunos] POST UPDATE - is_uploaded_file: ' . (is_uploaded_file($_FILES['foto']['tmp_name']) ? 'SIM' : 'NÃO'));
                                            error_log('[API Alunos] POST UPDATE - is_writable(dirname): ' . (is_writable(dirname($caminhoCompleto)) ? 'SIM' : 'NÃO'));
                                            error_log('[API Alunos] POST UPDATE - is_writable($uploadDir): ' . (is_writable($uploadDir) ? 'SIM' : 'NÃO'));
                                            error_log('[API Alunos] POST UPDATE - realpath($uploadDir): ' . realpath($uploadDir));
                                        }
                                        // Não bloquear salvamento dos dados - retornar warning
                                        $caminhoFoto = null;
                                        $uploadWarning = 'foto_nao_atualizada: Erro ao mover arquivo. Verifique permissões do diretório.';
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Lista de campos permitidos para atualização
                $camposPermitidos = [
                    'nome', 'cpf', 'rg', 'rg_orgao_emissor', 'rg_uf', 'rg_data_emissao', 'renach',
                    'data_nascimento', 'estado_civil', 'profissao', 'escolaridade',
                    'naturalidade', 'nacionalidade', 'telefone', 'telefone_secundario',
                    'contato_emergencia_nome', 'contato_emergencia_telefone', 'email',
                    'endereco', 'numero', 'bairro', 'cidade', 'estado', 'cep',
                    'categoria_cnh', 'tipo_servico', 'status', 'observacoes',
                    'atividade_remunerada', 'lgpd_consentimento', 'lgpd_consentimento_em',
                    'numero_processo', 'detran_numero', 'status_matricula', 'processo_situacao',
                    'status_pagamento'
                ];
                
                // Montar array de campos para atualização
                $alunoData = [];
                foreach ($camposPermitidos as $campo) {
                    if (isset($data[$campo])) {
                        $alunoData[$campo] = $data[$campo];
                    }
                }
                
                // Se houve upload de foto, adicionar ao array
                // Se não houve upload, não incluir campo foto (manter foto existente)
                $responseWarning = null;
                if (!empty($caminhoFoto)) {
                    $alunoData['foto'] = $caminhoFoto;
                    if (LOG_ENABLED) {
                        error_log('[API Alunos] POST UPDATE - Foto será atualizada para: ' . $caminhoFoto);
                    }
                } else {
                    if (LOG_ENABLED) {
                        error_log('[API Alunos] POST UPDATE - Foto não será atualizada (mantendo existente)');
                    }
                    // Se houve tentativa de upload mas falhou, adicionar warning
                    if (isset($uploadWarning)) {
                        $responseWarning = $uploadWarning;
                        if (LOG_ENABLED) {
                            error_log('[API Alunos] POST UPDATE - Warning de upload: ' . $uploadWarning);
                        }
                    }
                }
                
                // Processar LGPD
                if (isset($alunoData['lgpd_consentimento'])) {
                    $alunoData['lgpd_consentimento'] = (int)$alunoData['lgpd_consentimento'];
                    if ($alunoData['lgpd_consentimento'] == 1 && empty($alunoData['lgpd_consentimento_em'])) {
                        $alunoData['lgpd_consentimento_em'] = date('Y-m-d H:i:s');
                    } elseif ($alunoData['lgpd_consentimento'] == 0) {
                        $alunoData['lgpd_consentimento_em'] = null;
                    }
                }
                
                // Processar atividade remunerada
                if (isset($alunoData['atividade_remunerada'])) {
                    $alunoData['atividade_remunerada'] = (int)$alunoData['atividade_remunerada'];
                }
                
                // Validar que há campos para atualizar
                if (empty($alunoData)) {
                    if (LOG_ENABLED) {
                        error_log('[API Alunos] POST UPDATE - Nenhum campo para atualizar');
                    }
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Nenhum campo para atualizar']);
                    exit;
                }
                
                // Verificar se CPF já existe (exceto para o próprio aluno)
                if (isset($alunoData['cpf'])) {
                    $cpfExistente = $db->findWhere('alunos', 'cpf = ? AND id != ?', [$alunoData['cpf'], $id], '*', null, 1);
                    if ($cpfExistente && is_array($cpfExistente)) {
                        $cpfExistente = $cpfExistente[0];
                    }
                    if ($cpfExistente) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'error' => 'CPF já cadastrado']);
                        exit;
                    }
                }
                
                // Executar UPDATE
                try {
                    $resultado = $db->update('alunos', $alunoData, 'id = ?', [$id]);
                    
                    if (!$resultado) {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'error' => 'Erro ao atualizar aluno']);
                        exit;
                    }
                    
                    $response = ['success' => true, 'message' => 'Aluno atualizado com sucesso'];
                    if (isset($responseWarning) && $responseWarning) {
                        $response['warning'] = $responseWarning;
                        if (LOG_ENABLED) {
                            error_log('[API Alunos] POST UPDATE - Resposta com warning: ' . $responseWarning);
                        }
                    }
                    sendJsonResponse($response);
                    
                } catch (Exception $e) {
                    if (LOG_ENABLED) {
                        error_log('[API Alunos] POST UPDATE - Erro: ' . $e->getMessage());
                    }
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => 'Erro interno: ' . $e->getMessage()]);
                    exit;
                }
                
                // Não continuar para o fluxo de CREATE
                break;
            }
            
            // ========== FLUXO DE CREATE ==========
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
            if (empty($data['nome']) || empty($data['cpf']) || empty($data['cfc_id'])) {
                if (LOG_ENABLED) {
                    error_log('[API Alunos] Validação falhou - campos obrigatórios: ' . print_r([
                        'nome' => !empty($data['nome']),
                        'cpf' => !empty($data['cpf']),
                        'cfc_id' => !empty($data['cfc_id']),
                        'operacoes' => !empty($data['operacoes'])
                    ], true));
                }
                sendJsonResponse(['success' => false, 'error' => 'Nome, CPF e CFC são obrigatórios'], 400);
            }
            
            // Verificar se é salvamento apenas de Dados (sem matrícula/operações)
            // Pode vir como string '1' (FormData) ou boolean true (JSON)
            $salvarApenasDados = isset($data['salvar_apenas_dados']) && 
                                 ($data['salvar_apenas_dados'] == '1' || $data['salvar_apenas_dados'] === true || $data['salvar_apenas_dados'] === 1);
            
            if (LOG_ENABLED) {
                error_log('[API Alunos] Flag salvar_apenas_dados: ' . ($salvarApenasDados ? 'SIM' : 'NÃO'));
            }
            
            // Verificar se tem operações (apenas se NÃO for salvamento apenas de Dados)
            if (!$salvarApenasDados && empty($data['operacoes'])) {
                if (LOG_ENABLED) {
                    error_log('[API Alunos] Validação falhou - operacoes não fornecidas (e não é salvamento apenas de dados)');
                }
                sendJsonResponse(['success' => false, 'error' => 'Operações são obrigatórias'], 400);
            }
            
            // Não precisamos mais determinar tipo_servico baseado em categoria_cnh
            // Agora usamos apenas operacoes
            
            // Verificar se CPF já existe (exceto para o próprio aluno se for edição)
            $idExcluir = isset($data['id']) && !empty($data['id']) ? $data['id'] : 0;
            $cpfExistente = $db->findWhere('alunos', 'cpf = ? AND id != ?', [$data['cpf'], $idExcluir], '*', null, 1);
            if ($cpfExistente && is_array($cpfExistente)) {
                $cpfExistente = $cpfExistente[0]; // Pegar o primeiro resultado
            }
            if ($cpfExistente) {
                if (LOG_ENABLED) {
                    error_log('[API Alunos] CPF já existe: ' . $data['cpf'] . ' - ID encontrado: ' . $cpfExistente['id'] . ' - ID excluído: ' . $idExcluir);
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
            
            // Determinar categoria_cnh e tipo_servico baseado nas operações
            // Se for salvamento apenas de Dados, usar valores padrão
            $categoria_cnh = 'B'; // Padrão
            $tipo_servico = 'primeira_habilitacao'; // Padrão
            
            if (!$salvarApenasDados && !empty($data['operacoes']) && is_array($data['operacoes'])) {
                // Se não for salvamento apenas de Dados e houver operações, extrair da primeira operação
                $primeiraOperacao = $data['operacoes'][0] ?? [];
                if (isset($primeiraOperacao['categoria_cnh'])) {
                    $categoria_cnh = $primeiraOperacao['categoria_cnh'];
                }
                if (isset($primeiraOperacao['tipo_servico'])) {
                    $tipo_servico = $primeiraOperacao['tipo_servico'];
                }
            }
            
            // Se for salvamento apenas de Dados, não incluir operações no alunoData
            if ($salvarApenasDados && LOG_ENABLED) {
                error_log('[API Alunos] Salvamento apenas de Dados - usando valores padrão para categoria_cnh e tipo_servico');
            }
            
            // Se for salvamento apenas de Dados, não incluir operações no alunoData
            if ($salvarApenasDados && LOG_ENABLED) {
                error_log('[API Alunos] Salvamento apenas de Dados - usando valores padrão para categoria_cnh e tipo_servico');
            }

            $alunoData = [
                'cfc_id' => $data['cfc_id'],
                'nome' => $data['nome'],
                'cpf' => $data['cpf'],
                'rg' => $data['rg'] ?? '',
                'rg_orgao_emissor' => $data['rg_orgao_emissor'] ?? '',
                'rg_uf' => $data['rg_uf'] ?? '',
                'rg_data_emissao' => !empty($data['rg_data_emissao']) ? $data['rg_data_emissao'] : null,
                'renach' => $data['renach'] ?? '',
                'foto' => $caminhoFoto ?: ($data['foto'] ?? ''),
                'data_nascimento' => $data['data_nascimento'] ?? null,
                'estado_civil' => $data['estado_civil'] ?? '',
                'profissao' => $data['profissao'] ?? '',
                'escolaridade' => $data['escolaridade'] ?? '',
                'naturalidade' => $data['naturalidade'] ?? '',
                'nacionalidade' => $data['nacionalidade'] ?? 'Brasileira',
                'telefone' => $data['telefone'] ?? '',
                'telefone_secundario' => $data['telefone_secundario'] ?? '',
                'contato_emergencia_nome' => $data['contato_emergencia_nome'] ?? '',
                'contato_emergencia_telefone' => $data['contato_emergencia_telefone'] ?? '',
                'email' => $data['email'] ?? '',
                'endereco' => $data['endereco'] ?? '',
                'numero' => $data['numero'] ?? '',
                'bairro' => $data['bairro'] ?? '',
                'cidade' => $data['cidade'] ?? '',
                'estado' => $data['estado'] ?? '',
                'cep' => $data['cep'] ?? '',
                'categoria_cnh' => $categoria_cnh, // Campo obrigatório (padrão 'B' se salvamento apenas de Dados)
                'tipo_servico' => $tipo_servico, // Campo obrigatório (padrão 'primeira_habilitacao' se salvamento apenas de Dados)
                'status' => $data['status'] ?? 'ativo',
                'observacoes' => $data['observacoes'] ?? '',
                'operacoes' => ($salvarApenasDados ? null : (isset($data['operacoes']) ? json_encode($data['operacoes']) : null)),
                'atividade_remunerada' => isset($data['atividade_remunerada']) ? (int)$data['atividade_remunerada'] : 0,
                'lgpd_consentimento' => isset($data['lgpd_consentimento']) ? (int)$data['lgpd_consentimento'] : 0,
                'lgpd_consentimento_em' => !empty($data['lgpd_consentimento_em']) ? $data['lgpd_consentimento_em'] : (isset($data['lgpd_consentimento']) && $data['lgpd_consentimento'] == 1 ? date('Y-m-d H:i:s') : null),
                'numero_processo' => $data['numero_processo'] ?? null,
                'detran_numero' => $data['detran_numero'] ?? null,
                'status_matricula' => $data['status_matricula'] ?? '',
                'processo_situacao' => $data['processo_situacao'] ?? '',
                'status_pagamento' => $data['status_pagamento'] ?? 'pendente',
                'criado_em' => date('Y-m-d H:i:s')
            ];
            
            try {
                // Verificar se é edição (tem ID) ou criação
                if (!empty($data['id'])) {
                    // É edição - fazer UPDATE
                    if (LOG_ENABLED) {
                        error_log('[API Alunos] Dados para atualização: ' . print_r($alunoData, true));
                    }
                    
                    $resultado = $db->update('alunos', $alunoData, 'id = ?', [$data['id']]);
                    
                    if (!$resultado) {
                        if (LOG_ENABLED) {
                            error_log('[API Alunos] Erro ao atualizar aluno - update retornou false');
                        }
                        http_response_code(500);
                        echo json_encode(['success' => false, 'error' => 'Erro ao atualizar aluno']);
                        exit;
                    }
                    
                    if (LOG_ENABLED) {
                        error_log('[API Alunos] Aluno atualizado com sucesso, ID: ' . $data['id']);
                    }
                    
                    sendJsonResponse([
                        'success' => true, 
                        'message' => 'Aluno atualizado com sucesso!',
                        'aluno_id' => $data['id']
                    ]);
                } else {
                    // É criação - fazer INSERT
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
                
                // Criar credenciais automáticas para o aluno (apenas se houver email)
                // Se for salvamento apenas de Dados, não criar credenciais ainda
                if (!$salvarApenasDados && !empty($alunoData['email'])) {
                    if (LOG_ENABLED) {
                        error_log('[API Alunos] Criando credenciais automáticas para aluno ID: ' . $alunoId);
                    }
                    
                    try {
                        $credentials = CredentialManager::createStudentCredentials([
                            'aluno_id' => $alunoId,
                            'nome' => $alunoData['nome'],
                            'cpf' => $alunoData['cpf'],
                            'email' => $alunoData['email']
                        ]);
                        
                        if ($credentials['success']) {
                            if (LOG_ENABLED) {
                                $msg = isset($credentials['usuario_existente']) && $credentials['usuario_existente'] 
                                    ? 'Usuário já existe para este email' 
                                    : 'Credenciais criadas com sucesso';
                                error_log('[API Alunos] ' . $msg . ' para aluno ID: ' . $alunoId);
                            }
                            
                            // Enviar credenciais por email apenas se for novo usuário
                            if (!isset($credentials['usuario_existente']) || !$credentials['usuario_existente']) {
                                if (!empty($credentials['senha_temporaria'])) {
                                    CredentialManager::sendCredentials(
                                        $credentials['cpf'], 
                                        $credentials['senha_temporaria'], 
                                        'aluno'
                                    );
                                }
                            }
                        } else {
                            if (LOG_ENABLED) {
                                error_log('[API Alunos] Erro ao criar credenciais: ' . $credentials['message']);
                            }
                            // Não bloquear o salvamento do aluno se falhar a criação de credenciais
                            // Apenas logar o erro
                        }
                    } catch (Exception $e) {
                        if (LOG_ENABLED) {
                            error_log('[API Alunos] Exceção ao criar credenciais: ' . $e->getMessage());
                        }
                        // Não bloquear o salvamento do aluno se falhar a criação de credenciais
                        // Apenas logar o erro
                    }
                } else {
                    if (LOG_ENABLED) {
                        if ($salvarApenasDados) {
                            error_log('[API Alunos] Salvamento apenas de Dados - credenciais não serão criadas agora');
                        } else {
                            error_log('[API Alunos] Email não fornecido - credenciais não serão criadas');
                        }
                    }
                }
                
                $alunoData['id'] = $alunoId;
                $response = [
                    'success' => true, 
                    'message' => 'Aluno criado com sucesso', 
                    'data' => $alunoData
                ];
                
                // Adicionar informações de credenciais se foram criadas
                if (!$salvarApenasDados && !empty($alunoData['email']) && isset($credentials) && $credentials['success']) {
                    $response['credentials'] = [
                        'cpf' => $credentials['cpf'],
                        'senha_temporaria' => $credentials['senha_temporaria'] ?? null,
                        'message' => isset($credentials['usuario_existente']) && $credentials['usuario_existente']
                            ? 'Usuário já existe para este email'
                            : 'Credenciais criadas automaticamente'
                    ];
                }
                
                sendJsonResponse($response);
                } // Fim do bloco else para criação
                
            } catch (Exception $e) {
                if (LOG_ENABLED) {
                    error_log('[API Alunos] Erro ao inserir aluno: ' . $e->getMessage());
                }
                sendJsonResponse(['success' => false, 'error' => 'Erro interno: ' . $e->getMessage()], 500);
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
