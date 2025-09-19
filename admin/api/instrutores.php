<?php
// Habilitar exibição de erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// API para gerenciamento de instrutores
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');  // Especificar origem específica
header('Access-Control-Allow-Credentials: true');  // Permitir credenciais
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Incluir arquivos na ordem correta
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/CredentialManager.php';

// =====================================================
// FUNÇÕES DE UPLOAD DE FOTO
// =====================================================

/**
 * Processa upload de foto do instrutor
 */
function processarUploadFoto($arquivo, $instrutorId = null) {
    if (!isset($arquivo) || $arquivo['error'] !== UPLOAD_ERR_OK) {
        return null; // Nenhum arquivo enviado ou erro no upload
    }
    
    // Validar tipo de arquivo - detectar automaticamente se necessário
    $tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $tipoDetectado = $arquivo['type'];
    
    // Se o tipo não foi detectado corretamente (processamento manual), detectar pela extensão
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
            error_log('Tipo detectado pela extensão: ' . $extensao . ' -> ' . $tipoDetectado);
        }
    }
    
    if (!in_array($tipoDetectado, $tiposPermitidos)) {
        error_log('Tipo de arquivo rejeitado: ' . $tipoDetectado . ' (original: ' . $arquivo['type'] . ')');
        throw new Exception('Tipo de arquivo não permitido. Use JPG, PNG, GIF ou WebP.');
    }
    
    // Validar tamanho (2MB máximo)
    $tamanhoMaximo = 2 * 1024 * 1024; // 2MB
    if ($arquivo['size'] > $tamanhoMaximo) {
        throw new Exception('Arquivo muito grande. Tamanho máximo: 2MB.');
    }
    
    // Gerar nome único para o arquivo
    $extensao = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
    $nomeArquivo = 'instrutor_' . ($instrutorId ?: uniqid()) . '_' . time() . '.' . $extensao;
    
    // Diretório de destino
    $diretorioDestino = '../../assets/uploads/instrutores/';
    
    error_log('Processando upload - Nome original: ' . $arquivo['name']);
    error_log('Processando upload - Tamanho: ' . $arquivo['size']);
    error_log('Processando upload - Tipo: ' . $arquivo['type']);
    error_log('Processando upload - Tmp_name: ' . $arquivo['tmp_name']);
    error_log('Processando upload - Erro: ' . $arquivo['error']);
    error_log('Processando upload - Nome arquivo: ' . $nomeArquivo);
    error_log('Processando upload - Diretório destino: ' . $diretorioDestino);
    
    // Garantir que o diretório existe
    if (!is_dir($diretorioDestino)) {
        error_log('Diretório não existe, criando...');
        if (!mkdir($diretorioDestino, 0755, true)) {
            error_log('Erro ao criar diretório: ' . $diretorioDestino);
            throw new Exception('Erro ao criar diretório de upload.');
        }
        error_log('Diretório criado com sucesso');
    } else {
        error_log('Diretório existe: ' . $diretorioDestino);
    }
    
    $caminhoCompleto = $diretorioDestino . $nomeArquivo;
    error_log('Caminho completo: ' . $caminhoCompleto);
    
    // Verificar se o arquivo temporário existe
    if (!file_exists($arquivo['tmp_name'])) {
        error_log('Arquivo temporário não existe: ' . $arquivo['tmp_name']);
        throw new Exception('Arquivo temporário não encontrado.');
    }
    
    // Verificar permissões do diretório
    if (!is_writable($diretorioDestino)) {
        error_log('Diretório não é gravável: ' . $diretorioDestino);
        throw new Exception('Diretório de destino não tem permissão de escrita.');
    }
    
    // Mover arquivo (usar copy() para arquivos processados manualmente)
    error_log('Tentando mover arquivo...');
    
    // Para arquivos processados manualmente, usar copy() em vez de move_uploaded_file()
    if (!copy($arquivo['tmp_name'], $caminhoCompleto)) {
        error_log('Erro no copy - tmp_name: ' . $arquivo['tmp_name']);
        error_log('Erro no copy - destino: ' . $caminhoCompleto);
        
        $ultimoErro = error_get_last();
        if ($ultimoErro) {
            error_log('Erro no copy - último erro PHP: ' . $ultimoErro['message']);
        } else {
            error_log('Erro no copy - nenhum erro PHP disponível');
        }
        
        throw new Exception('Erro ao salvar arquivo.');
    }
    
    // Remover arquivo temporário após copiar
    unlink($arquivo['tmp_name']);
    error_log('Arquivo temporário removido: ' . $arquivo['tmp_name']);
    
    error_log('Arquivo movido com sucesso para: ' . $caminhoCompleto);
    
    // Retornar caminho relativo para o banco de dados
    return 'assets/uploads/instrutores/' . $nomeArquivo;
}

/**
 * Remove foto antiga se existir
 */
function removerFotoAntiga($caminhoFoto) {
    if ($caminhoFoto && file_exists('../../' . $caminhoFoto)) {
        unlink('../../' . $caminhoFoto);
    }
}

// Debug da sessão
error_log('Session ID: ' . (session_id() ?: 'Nenhuma'));
error_log('User ID: ' . ($_SESSION['user_id'] ?? 'Nenhum'));
error_log('User Type: ' . ($_SESSION['user_type'] ?? 'Nenhum'));
error_log('REQUEST_METHOD: ' . $_SERVER['REQUEST_METHOD']);
error_log('GET params: ' . json_encode($_GET));
error_log('POST data: ' . json_encode($_POST));

// Verificar se as funções estão disponíveis
if (!function_exists('isLoggedIn')) {
    error_log('ERRO: Função isLoggedIn não está disponível');
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno: Funções de autenticação não disponíveis']);
    exit;
}

if (!function_exists('hasPermission')) {
    error_log('ERRO: Função hasPermission não está disponível');
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno: Funções de autenticação não disponíveis']);
    exit;
}

// Verificar se o usuário está logado e tem permissão de admin ou secretaria
if (!isLoggedIn()) {
    error_log('Usuário não está logado');
    http_response_code(401);
    echo json_encode(['error' => 'Usuário não está logado']);
    exit;
}

if (!canManageUsers()) {
    $currentUser = getCurrentUser();
    error_log('Usuário não tem permissão para gerenciar instrutores: ' . ($currentUser['tipo'] ?? 'desconhecido'));
    http_response_code(403);
    echo json_encode(['error' => 'Permissão negada - Apenas administradores e atendentes']);
    exit;
}

error_log('Usuário autenticado com sucesso. User ID: ' . $_SESSION['user_id']);

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Listar instrutores ou buscar instrutor específico
            if (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                error_log('Buscando instrutor ID: ' . $id);
                
                // Consulta simplificada para evitar timeout
                $instrutor = $db->fetch("
                    SELECT i.*, c.nome as cfc_nome 
                    FROM instrutores i 
                    LEFT JOIN cfcs c ON i.cfc_id = c.id 
                    WHERE i.id = ?
                ", [$id]);
                
                if ($instrutor) {
                    // Adicionar campos para compatibilidade com o frontend
                    $instrutor['nome'] = $instrutor['nome'] ?: 'N/A';
                    $instrutor['email'] = $instrutor['email'] ?: 'N/A';
                    $instrutor['cfc_nome'] = $instrutor['cfc_nome'] ?: 'N/A';
                    
                    error_log('Instrutor encontrado: ' . json_encode($instrutor));
                    echo json_encode(['success' => true, 'data' => $instrutor]);
                } else {
                    error_log('Instrutor não encontrado para ID: ' . $id);
                    http_response_code(404);
                    echo json_encode(['error' => 'Instrutor não encontrado']);
                }
            } else {
                // Listar todos os instrutores
                $instrutores = $db->fetchAll("
                    SELECT i.*, u.nome as nome_usuario, u.email as email_usuario, c.nome as cfc_nome 
                    FROM instrutores i 
                    LEFT JOIN usuarios u ON i.usuario_id = u.id 
                    LEFT JOIN cfcs c ON i.cfc_id = c.id 
                    ORDER BY i.nome
                ");
                
                // Adicionar campos para compatibilidade com o frontend
                foreach ($instrutores as &$instrutor) {
                    // Usar o nome do instrutor (i.nome) como principal, fallback para nome_usuario se necessário
                    $instrutor['nome'] = $instrutor['nome'] ?: ($instrutor['nome_usuario'] ?: 'N/A');
                    $instrutor['email'] = $instrutor['email'] ?: ($instrutor['email_usuario'] ?: 'N/A');
                    $instrutor['cfc_nome'] = $instrutor['cfc_nome'] ?: 'N/A';
                }
                
                echo json_encode(['success' => true, 'data' => $instrutores]);
            }
            break;
            
        case 'POST':
            // Criar novo instrutor
            // Debug completo dos headers
            error_log('POST - Headers recebidos: ' . json_encode(getallheaders()));
            error_log('POST - CONTENT_TYPE: ' . ($_SERVER['CONTENT_TYPE'] ?? 'NÃO DEFINIDO'));
            error_log('POST - REQUEST_METHOD: ' . ($_SERVER['REQUEST_METHOD'] ?? 'NÃO DEFINIDO'));
            error_log('POST - $_POST vazio? ' . (empty($_POST) ? 'SIM' : 'NÃO'));
            error_log('POST - $_FILES vazio? ' . (empty($_FILES) ? 'SIM' : 'NÃO'));
            error_log('POST - $_POST: ' . json_encode($_POST));
            error_log('POST - $_FILES: ' . json_encode(array_keys($_FILES)));
            
            // Verificar se é FormData (multipart/form-data) ou JSON
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            
            // Se é multipart/form-data mas $_POST está vazio, forçar processamento
            if (strpos($contentType, 'multipart/form-data') !== false && empty($_POST)) {
                error_log('POST - FormData detectado mas $_POST vazio, forçando processamento...');
                
                // Tentar processar manualmente o FormData
                $input = file_get_contents('php://input');
                error_log('POST - Input raw length: ' . strlen($input));
                
                // Parse manual do FormData (método simples)
                $boundary = null;
                if (preg_match('/boundary=(.+)$/', $contentType, $matches)) {
                    $boundary = $matches[1];
                    error_log('POST - Boundary encontrado: ' . $boundary);
                }
                
                if ($boundary) {
                    $parts = explode('--' . $boundary, $input);
                    $data = [];
                    
                    foreach ($parts as $part) {
                        if (empty(trim($part))) continue;
                        
                        if (preg_match('/name="([^"]+)"/', $part, $nameMatches)) {
                            $fieldName = $nameMatches[1];
                            
                            // Verificar se é um arquivo
                            if (preg_match('/filename="([^"]+)"/', $part, $fileMatches)) {
                                $filename = $fileMatches[1];
                                error_log('POST - Arquivo encontrado: ' . $fieldName . ' = ' . $filename);
                                
                                // Extrair dados do arquivo
                                $fileData = substr($part, strpos($part, "\r\n\r\n") + 4);
                                $fileData = rtrim($fileData, "\r\n");
                                
                                // Detectar tipo MIME pela extensão
                                $extensao = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                                $mapeamentoTipos = [
                                    'jpg' => 'image/jpeg',
                                    'jpeg' => 'image/jpeg',
                                    'png' => 'image/png',
                                    'gif' => 'image/gif',
                                    'webp' => 'image/webp'
                                ];
                                $tipoMime = $mapeamentoTipos[$extensao] ?? 'application/octet-stream';
                                
                                // Simular $_FILES
                                $_FILES[$fieldName] = [
                                    'name' => $filename,
                                    'type' => $tipoMime,
                                    'tmp_name' => sys_get_temp_dir() . '/php_' . uniqid(),
                                    'error' => UPLOAD_ERR_OK,
                                    'size' => strlen($fileData)
                                ];
                                
                                // Salvar arquivo temporário
                                file_put_contents($_FILES[$fieldName]['tmp_name'], $fileData);
                                
                            } else {
                                // Campo normal
                                $fieldValue = substr($part, strpos($part, "\r\n\r\n") + 4);
                                $fieldValue = rtrim($fieldValue, "\r\n");
                                $data[$fieldName] = $fieldValue;
                                error_log('POST - Campo encontrado: ' . $fieldName . ' = ' . $fieldValue);
                            }
                        }
                    }
                    
                    error_log('POST - Dados processados manualmente: ' . json_encode($data));
                    error_log('POST - Arquivos processados: ' . json_encode(array_keys($_FILES)));
                }
            }
            
            // Se $_POST não está vazio, provavelmente é FormData
            if (!empty($_POST)) {
                // Dados vêm via FormData (POST + FILES)
                $data = $_POST;
                error_log('POST - Processando como FormData');
                error_log('POST - Dados recebidos via FormData: ' . json_encode($data));
                error_log('POST - Arquivos recebidos: ' . json_encode(array_keys($_FILES)));
            } else if (isset($data) && !empty($data)) {
                // Dados processados manualmente
                error_log('POST - Usando dados processados manualmente');
            } else {
                // Dados vêm via JSON
                error_log('POST - Processando como JSON');
                $data = json_decode(file_get_contents('php://input'), true);
                if (!$data) {
                    $data = $_POST;
                }
                error_log('POST - Dados recebidos via JSON: ' . json_encode($data));
            }
            
            // Debug: Log dos dados recebidos
            error_log('Dados recebidos na API: ' . json_encode($data));
            error_log('Campo usuario_id: ' . ($data['usuario_id'] ?? 'VAZIO'));
            error_log('Campo cfc_id: ' . ($data['cfc_id'] ?? 'VAZIO'));
            error_log('Campo nome: ' . ($data['nome'] ?? 'VAZIO'));
            error_log('Campo credencial: ' . ($data['credencial'] ?? 'VAZIO'));
            error_log('Campo id: ' . ($data['id'] ?? 'VAZIO'));
            
            // Validações - verificar campos obrigatórios
            if (empty($data['cfc_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'CFC é obrigatório']);
                exit;
            }
            
            if (empty($data['nome'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Nome é obrigatório']);
                exit;
            }
            
            if (empty($data['credencial'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Credencial é obrigatória']);
                exit;
            }
            
            // Verificar se credencial já existe (única validação necessária)
            $existingCredencial = $db->fetch("SELECT id FROM instrutores WHERE credencial = ?", [$data['credencial']]);
            if ($existingCredencial) {
                error_log('POST - Credencial já existe: ' . $data['credencial']);
                http_response_code(400);
                echo json_encode(['error' => 'Credencial já cadastrada para outro instrutor']);
                exit;
            }
            
            // Verificar se CFC existe
            $existingCFC = $db->fetch("SELECT id FROM cfcs WHERE id = ?", [$data['cfc_id']]);
            if (!$existingCFC) {
                error_log('POST - CFC não encontrado: ' . $data['cfc_id']);
                http_response_code(400);
                echo json_encode(['error' => 'CFC não encontrado']);
                exit;
            }
            error_log('POST - CFC encontrado: ' . $data['cfc_id']);
            
            // Remover validação de usuário - não é mais necessária
            
            // Iniciar transação
            $db->beginTransaction();
            
            try {
                // Verificar se usuário foi selecionado (opcional agora)
                $usuario_id = $data['usuario_id'] ?? null;
                
                // Se usuário foi fornecido, verificar se existe
                if ($usuario_id) {
                    $existingUser = $db->fetch("SELECT id FROM usuarios WHERE id = ?", [$usuario_id]);
                    if (!$existingUser) {
                        error_log('POST - Usuário não encontrado: ' . $usuario_id);
                        throw new Exception('Usuário não encontrado');
                    }
                    error_log('POST - Usuário encontrado: ' . $usuario_id);
                }
                
                // Processar categorias e dias da semana para FormData
                $categorias = $data['categoria_habilitacao'] ?? [];
                $diasSemana = $data['dias_semana'] ?? [];
                
                // Se vier como array (FormData), usar diretamente
                if (is_array($categorias)) {
                    error_log('POST - Categorias como array: ' . json_encode($categorias));
                } else {
                    error_log('POST - Categorias como string: ' . $categorias);
                }
                
                if (is_array($diasSemana)) {
                    error_log('POST - Dias da semana como array: ' . json_encode($diasSemana));
                } else {
                    error_log('POST - Dias da semana como string: ' . $diasSemana);
                }
                
                // Criar instrutor com TODOS os campos
                $instrutorData = [
                    'nome' => $data['nome'] ?? '',
                    'cpf' => $data['cpf'] ?? '',
                    'cnh' => $data['cnh'] ?? '',
                    'data_nascimento' => !empty($data['data_nascimento']) ? $data['data_nascimento'] : null,
                    'email' => $data['email'] ?? '',
                    'telefone' => $data['telefone'] ?? '',
                    'endereco' => $data['endereco'] ?? '',
                    'cidade' => $data['cidade'] ?? '',
                    'uf' => $data['uf'] ?? '',
                    'usuario_id' => $usuario_id,
                    'cfc_id' => $data['cfc_id'],
                    'credencial' => $data['credencial'] ?? '',
                    'categorias_json' => json_encode($categorias),
                    'tipo_carga' => $data['tipo_carga'] ?? '',
                    'validade_credencial' => !empty($data['validade_credencial']) ? $data['validade_credencial'] : null,
                    'observacoes' => $data['observacoes'] ?? '',
                    'dias_semana' => json_encode($diasSemana),
                    'horario_inicio' => $data['horario_inicio'] ?? '',
                    'horario_fim' => $data['horario_fim'] ?? '',
                    'ativo' => isset($data['ativo']) ? (bool)$data['ativo'] : true,
                    'criado_em' => date('Y-m-d H:i:s')
                ];
                
                // Processar upload de foto se houver
                $caminhoFoto = null;
                if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                    try {
                        $caminhoFoto = processarUploadFoto($_FILES['foto']);
                        $instrutorData['foto'] = $caminhoFoto;
                        error_log('Foto processada com sucesso: ' . $caminhoFoto);
                    } catch (Exception $e) {
                        error_log('Erro no upload da foto: ' . $e->getMessage());
                        throw new Exception('Erro no upload da foto: ' . $e->getMessage());
                    }
                }
                
                // Debug: Log dos dados que serão inseridos
                error_log('Dados do instrutor para inserção: ' . json_encode($instrutorData));
                error_log('Categoria habilitação: ' . json_encode($data['categoria_habilitacao'] ?? 'VAZIO'));
                error_log('Dias da semana: ' . json_encode($data['dias_semana'] ?? 'VAZIO'));
                
                $instrutor_id = $db->insert('instrutores', $instrutorData);
                
                if (!$instrutor_id) {
                    error_log('Erro na inserção do instrutor. Último erro SQL: ' . $db->getLastError());
                    throw new Exception('Erro na execução da query: ' . $db->getLastError());
                }
                
                // Criar credenciais automáticas para o instrutor se não foi fornecido usuário_id
                if (!$usuario_id) {
                    if (LOG_ENABLED) {
                        error_log('[API Instrutores] Criando credenciais automáticas para instrutor ID: ' . $instrutor_id);
                    }
                    
                    $credentials = CredentialManager::createEmployeeCredentials([
                        'instrutor_id' => $instrutor_id,
                        'nome' => $instrutorData['nome'],
                        'email' => $instrutorData['email'],
                        'tipo' => 'instrutor'
                    ]);
                    
                    if ($credentials['success']) {
                        if (LOG_ENABLED) {
                            error_log('[API Instrutores] Credenciais criadas com sucesso para instrutor ID: ' . $instrutor_id);
                        }
                        
                        // Atualizar o instrutor com o usuario_id criado
                        $db->update('instrutores', ['usuario_id' => $credentials['usuario_id']], ['id' => $instrutor_id]);
                        
                        // Enviar credenciais por email (simulado)
                        CredentialManager::sendCredentials(
                            $credentials['email'], 
                            $credentials['senha_temporaria'], 
                            'instrutor'
                        );
                    } else {
                        if (LOG_ENABLED) {
                            error_log('[API Instrutores] Erro ao criar credenciais: ' . $credentials['message']);
                        }
                    }
                }
                
                $db->commit();
                
                // Buscar instrutor criado
                $instrutor = $db->fetch("
                    SELECT i.*, u.nome as nome_usuario, u.email as email_usuario, c.nome as cfc_nome 
                    FROM instrutores i 
                    LEFT JOIN usuarios u ON i.usuario_id = u.id 
                    LEFT JOIN cfcs c ON i.cfc_id = c.id 
                    WHERE i.id = ?
                ", [$instrutor_id]);
                
                // Adicionar campos para compatibilidade com o frontend
                $instrutor['nome'] = $instrutor['nome'] ?: ($instrutor['nome_usuario'] ?: 'N/A');
                $instrutor['email'] = $instrutor['email'] ?: ($instrutor['email_usuario'] ?: 'N/A');
                $instrutor['cfc_nome'] = $instrutor['cfc_nome'] ?: 'N/A';
                
                echo json_encode(['success' => true, 'message' => 'Instrutor criado com sucesso', 'data' => $instrutor]);
                
            } catch (Exception $e) {
                $db->rollback();
                error_log('Erro na criação do instrutor: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'Erro ao criar instrutor: ' . $e->getMessage()]);
                exit;
            }
            break;
            
        case 'PUT':
            // Atualizar instrutor
            // Debug completo dos headers
            error_log('PUT - Headers recebidos: ' . json_encode(getallheaders()));
            error_log('PUT - CONTENT_TYPE: ' . ($_SERVER['CONTENT_TYPE'] ?? 'NÃO DEFINIDO'));
            error_log('PUT - REQUEST_METHOD: ' . ($_SERVER['REQUEST_METHOD'] ?? 'NÃO DEFINIDO'));
            error_log('PUT - $_POST vazio? ' . (empty($_POST) ? 'SIM' : 'NÃO'));
            error_log('PUT - $_FILES vazio? ' . (empty($_FILES) ? 'SIM' : 'NÃO'));
            error_log('PUT - $_POST: ' . json_encode($_POST));
            error_log('PUT - $_FILES: ' . json_encode(array_keys($_FILES)));
            
            // Verificar se é FormData (multipart/form-data) ou JSON
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            
            // Se é multipart/form-data mas $_POST está vazio, forçar processamento
            if (strpos($contentType, 'multipart/form-data') !== false && empty($_POST)) {
                error_log('PUT - FormData detectado mas $_POST vazio, forçando processamento...');
                
                // Tentar processar manualmente o FormData
                $input = file_get_contents('php://input');
                error_log('PUT - Input raw length: ' . strlen($input));
                
                // Parse manual do FormData (método simples)
                $boundary = null;
                if (preg_match('/boundary=(.+)$/', $contentType, $matches)) {
                    $boundary = $matches[1];
                    error_log('PUT - Boundary encontrado: ' . $boundary);
                }
                
                if ($boundary) {
                    $parts = explode('--' . $boundary, $input);
                    $data = [];
                    
                    foreach ($parts as $part) {
                        if (empty(trim($part))) continue;
                        
                        if (preg_match('/name="([^"]+)"/', $part, $nameMatches)) {
                            $fieldName = $nameMatches[1];
                            
                            // Verificar se é um arquivo
                            if (preg_match('/filename="([^"]+)"/', $part, $fileMatches)) {
                                $filename = $fileMatches[1];
                                error_log('PUT - Arquivo encontrado: ' . $fieldName . ' = ' . $filename);
                                
                                // Extrair dados do arquivo
                                $fileData = substr($part, strpos($part, "\r\n\r\n") + 4);
                                $fileData = rtrim($fileData, "\r\n");
                                
                                // Detectar tipo MIME pela extensão
                                $extensao = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                                $mapeamentoTipos = [
                                    'jpg' => 'image/jpeg',
                                    'jpeg' => 'image/jpeg',
                                    'png' => 'image/png',
                                    'gif' => 'image/gif',
                                    'webp' => 'image/webp'
                                ];
                                $tipoMime = $mapeamentoTipos[$extensao] ?? 'application/octet-stream';
                                
                                // Simular $_FILES
                                $_FILES[$fieldName] = [
                                    'name' => $filename,
                                    'type' => $tipoMime,
                                    'tmp_name' => sys_get_temp_dir() . '/php_' . uniqid(),
                                    'error' => UPLOAD_ERR_OK,
                                    'size' => strlen($fileData)
                                ];
                                
                                // Salvar arquivo temporário
                                file_put_contents($_FILES[$fieldName]['tmp_name'], $fileData);
                                
                            } else {
                                // Campo normal
                                $fieldValue = substr($part, strpos($part, "\r\n\r\n") + 4);
                                $fieldValue = rtrim($fieldValue, "\r\n");
                                $data[$fieldName] = $fieldValue;
                                error_log('PUT - Campo encontrado: ' . $fieldName . ' = ' . $fieldValue);
                            }
                        }
                    }
                    
                    error_log('PUT - Dados processados manualmente: ' . json_encode($data));
                    error_log('PUT - Arquivos processados: ' . json_encode(array_keys($_FILES)));
                }
            }
            
            // Se $_POST não está vazio, provavelmente é FormData
            if (!empty($_POST)) {
                // Dados vêm via FormData (POST + FILES)
                $data = $_POST;
                error_log('PUT - Processando como FormData');
                error_log('PUT - Dados recebidos via FormData: ' . json_encode($data));
                error_log('PUT - Arquivos recebidos: ' . json_encode(array_keys($_FILES)));
            } else if (isset($data) && !empty($data)) {
                // Dados processados manualmente
                error_log('PUT - Usando dados processados manualmente');
            } else {
                // Dados vêm via JSON
                error_log('PUT - Processando como JSON');
                $data = json_decode(file_get_contents('php://input'), true);
                if (!$data) {
                    parse_str(file_get_contents('php://input'), $data);
                }
                error_log('PUT - Dados recebidos via JSON: ' . json_encode($data));
            }
            
            // Debug: Log dos dados recebidos
            error_log('PUT - Dados processados: ' . json_encode($data));
            
            if (empty($data['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID do instrutor é obrigatório']);
                exit;
            }
            
            $id = (int)$data['id'];
            
            // Verificar se instrutor existe
            $existingInstrutor = $db->fetch("SELECT id, usuario_id, cfc_id FROM instrutores WHERE id = ?", [$id]);
            if (!$existingInstrutor) {
                http_response_code(404);
                echo json_encode(['error' => 'Instrutor não encontrado']);
                exit;
            }
            
            // Iniciar transação
            $db->beginTransaction();
            
            try {
                // Atualizar dados do usuário
                $updateUserData = [];
                if (isset($data['nome'])) $updateUserData['nome'] = $data['nome'];
                if (isset($data['email'])) $updateUserData['email'] = $data['email'];
                if (isset($data['cpf'])) $updateUserData['cpf'] = $data['cpf'];
                if (isset($data['telefone'])) $updateUserData['telefone'] = $data['telefone'];
                if (isset($data['senha']) && !empty($data['senha'])) {
                    $updateUserData['senha'] = password_hash($data['senha'], PASSWORD_DEFAULT);
                }
                if (isset($data['ativo'])) $updateUserData['ativo'] = (bool)$data['ativo'];
                
                if (!empty($updateUserData)) {
                    $updateUserData['atualizado_em'] = date('Y-m-d H:i:s');
                    $result = $db->update('usuarios', $updateUserData, 'id = ?', [$existingInstrutor['usuario_id']]);
                    if (!$result) {
                        throw new Exception('Erro ao atualizar usuário');
                    }
                }
                
                // Processar categorias e dias da semana para FormData (PUT)
                $categorias = $data['categoria_habilitacao'] ?? null;
                $diasSemana = $data['dias_semana'] ?? null;
                
                if (is_array($categorias)) {
                    error_log('PUT - Categorias como array: ' . json_encode($categorias));
                } else if ($categorias !== null) {
                    error_log('PUT - Categorias como string: ' . $categorias);
                }
                
                if (is_array($diasSemana)) {
                    error_log('PUT - Dias da semana como array: ' . json_encode($diasSemana));
                } else if ($diasSemana !== null) {
                    error_log('PUT - Dias da semana como string: ' . $diasSemana);
                }
                
                // Atualizar dados do instrutor com TODOS os campos
                $updateInstrutorData = [];
                if (isset($data['nome'])) $updateInstrutorData['nome'] = $data['nome'];
                if (isset($data['cpf'])) $updateInstrutorData['cpf'] = $data['cpf'];
                if (isset($data['cnh'])) $updateInstrutorData['cnh'] = $data['cnh'];
                if (isset($data['data_nascimento'])) $updateInstrutorData['data_nascimento'] = !empty($data['data_nascimento']) ? $data['data_nascimento'] : null;
                if (isset($data['email'])) $updateInstrutorData['email'] = $data['email'];
                if (isset($data['telefone'])) $updateInstrutorData['telefone'] = $data['telefone'];
                if (isset($data['endereco'])) $updateInstrutorData['endereco'] = $data['endereco'];
                if (isset($data['cidade'])) $updateInstrutorData['cidade'] = $data['cidade'];
                if (isset($data['uf'])) $updateInstrutorData['uf'] = $data['uf'];
                if (isset($data['cfc_id'])) $updateInstrutorData['cfc_id'] = $data['cfc_id'];
                if (isset($data['credencial'])) $updateInstrutorData['credencial'] = $data['credencial'];
                if ($categorias !== null) $updateInstrutorData['categorias_json'] = json_encode($categorias);
                if (isset($data['tipo_carga'])) $updateInstrutorData['tipo_carga'] = $data['tipo_carga'];
                if (isset($data['validade_credencial'])) $updateInstrutorData['validade_credencial'] = !empty($data['validade_credencial']) ? $data['validade_credencial'] : null;
                if (isset($data['observacoes'])) $updateInstrutorData['observacoes'] = $data['observacoes'];
                if ($diasSemana !== null) $updateInstrutorData['dias_semana'] = json_encode($diasSemana);
                if (isset($data['horario_inicio'])) $updateInstrutorData['horario_inicio'] = $data['horario_inicio'];
                if (isset($data['horario_fim'])) $updateInstrutorData['horario_fim'] = $data['horario_fim'];
                if (isset($data['ativo'])) $updateInstrutorData['ativo'] = (bool)$data['ativo'];
                
                // Processar upload de foto se houver
                if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                    try {
                        // Remover foto antiga se existir
                        if (isset($existingInstrutor['foto']) && !empty($existingInstrutor['foto'])) {
                            removerFotoAntiga($existingInstrutor['foto']);
                        }
                        
                        $caminhoFoto = processarUploadFoto($_FILES['foto'], $id);
                        $updateInstrutorData['foto'] = $caminhoFoto;
                        error_log('Foto atualizada com sucesso: ' . $caminhoFoto);
                    } catch (Exception $e) {
                        error_log('Erro no upload da foto: ' . $e->getMessage());
                        throw new Exception('Erro no upload da foto: ' . $e->getMessage());
                    }
                }
                
                // Debug: Log dos dados que serão atualizados
                error_log('PUT - Dados do instrutor para atualização: ' . json_encode($updateInstrutorData));
                error_log('PUT - Categorias recebidas: ' . (isset($data['categorias']) ? json_encode($data['categorias']) : 'NÃO DEFINIDO'));
                error_log('PUT - Dias da semana recebidos: ' . (isset($data['dias_semana']) ? json_encode($data['dias_semana']) : 'NÃO DEFINIDO'));
                
                if (!empty($updateInstrutorData)) {
                    $updateInstrutorData['updated_at'] = date('Y-m-d H:i:s');
                    $result = $db->update('instrutores', $updateInstrutorData, 'id = ?', [$id]);
                    if (!$result) {
                        throw new Exception('Erro ao atualizar instrutor');
                    }
                }
                
                $db->commit();
                
                // Buscar instrutor atualizado
                $instrutor = $db->fetch("
                    SELECT i.*, u.nome as nome_usuario, u.email, c.nome as cfc_nome 
                    FROM instrutores i 
                    LEFT JOIN usuarios u ON i.usuario_id = u.id 
                    LEFT JOIN cfcs c ON i.cfc_id = c.id 
                    WHERE i.id = ?
                ", [$id]);
                
                // Adicionar campos para compatibilidade com o frontend
                $instrutor['nome'] = $instrutor['nome_usuario'];
                $instrutor['cfc_nome'] = $instrutor['cfc_nome'];
                
                echo json_encode(['success' => true, 'message' => 'Instrutor atualizado com sucesso', 'data' => $instrutor]);
                
            } catch (Exception $e) {
                $db->rollback();
                throw $e;
            }
            break;
            
        case 'DELETE':
            // Excluir instrutor
            if (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                
                // Log para debug
                error_log("Tentando excluir instrutor ID: $id");
                
                // Verificar se instrutor existe
                $existingInstrutor = $db->fetch("SELECT id, usuario_id FROM instrutores WHERE id = ?", [$id]);
                if (!$existingInstrutor) {
                    error_log("Instrutor ID $id não encontrado");
                    http_response_code(404);
                    echo json_encode(['error' => 'Instrutor não encontrado']);
                    exit;
                }
                
                error_log("Instrutor encontrado: " . json_encode($existingInstrutor));
                
                // Iniciar transação
                $db->beginTransaction();
                
                try {
                    // Excluir instrutor
                    $result = $db->delete('instrutores', 'id = ?', [$id]);
                    if (!$result) {
                        throw new Exception('Erro ao excluir instrutor');
                    }
                    
                    error_log("Instrutor ID $id excluído com sucesso");
                    
                    // SOFT DELETE: Marcar usuário como inativo em vez de excluí-lo
                    $result = $db->update('usuarios', [
                        'ativo' => false,
                        'atualizado_em' => date('Y-m-d H:i:s')
                    ], 'id = ?', [$existingInstrutor['usuario_id']]);
                    
                    if (!$result) {
                        error_log("AVISO: Não foi possível marcar usuário como inativo");
                        // Não falhar se não conseguir marcar como inativo
                    }
                    
                    error_log("Usuário ID {$existingInstrutor['usuario_id']} marcado como INATIVO (soft delete)");
                    
                    $db->commit();
                    echo json_encode(['success' => true, 'message' => 'Instrutor excluído com sucesso']);
                    
                } catch (Exception $e) {
                    error_log("Erro na transação: " . $e->getMessage());
                    $db->rollback();
                    throw $e;
                }
            } else {
                error_log("DELETE sem ID fornecido");
                http_response_code(400);
                echo json_encode(['error' => 'ID do instrutor é obrigatório']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
            break;
    }
    
} catch (Exception $e) {
    error_log('Erro na API de instrutores: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    // Garantir que a resposta seja JSON válido
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
} catch (Error $e) {
    error_log('Erro fatal na API de instrutores: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    // Garantir que a resposta seja JSON válido
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro fatal do servidor: ' . $e->getMessage()
    ]);
}
?>
