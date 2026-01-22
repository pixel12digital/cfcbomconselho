<?php
/**
 * API para gerenciamento de ocorrências do instrutor
 * 
 * FASE 2 - Correção: 2024
 * Arquivo: admin/api/ocorrencias-instrutor.php
 * 
 * Funcionalidades:
 * - Registrar ocorrências do instrutor
 * - Listar ocorrências do instrutor
 * - Validações de segurança (instrutor_id pertence ao usuário logado)
 */

// FASE 2 - Correção: Seguir mesmo padrão da API instrutor-aulas.php
// Arquivo: admin/api/ocorrencias-instrutor.php (linha ~14)
// Não iniciar sessão manualmente - deixar includes/auth.php fazer isso
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';

// Headers JSON (enviar antes de qualquer output)
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

function returnJsonSuccess($data = null, $message = 'Sucesso') {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

function returnJsonError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// FASE 2 - Correção: Usar função centralizada de includes/auth.php
// Arquivo: admin/api/ocorrencias-instrutor.php (linha ~40)
// A função getCurrentInstrutorId() já está disponível via includes/auth.php

try {
    // Verificar método OPTIONS (CORS)
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    // Verificar autenticação
    // FASE 2 - Debug: Verificar estado da sessão
    if (defined('LOG_ENABLED') && LOG_ENABLED) {
        error_log(sprintf(
            '[OCORRENCIAS_API] Verificando autenticação - session_id=%s, user_id=%s, session_status=%d',
            session_id(),
            $_SESSION['user_id'] ?? 'não definido',
            session_status()
        ));
    }
    
    $user = getCurrentUser();
    if (!$user) {
        // FASE 2 - Debug: Log detalhado quando não autenticado
        if (defined('LOG_ENABLED') && LOG_ENABLED) {
            error_log(sprintf(
                '[OCORRENCIAS_API] Usuário não autenticado - session_id=%s, session_data=%s',
                session_id(),
                json_encode($_SESSION ?? [])
            ));
        }
        returnJsonError('Usuário não autenticado', 401);
    }

    // VALIDAÇÃO CRÍTICA: Apenas instrutores podem usar esta API
    if ($user['tipo'] !== 'instrutor') {
        returnJsonError('Acesso negado. Apenas instrutores podem usar esta API.', 403);
    }

    $db = db();

    // FASE 2 - Correção: Usar função centralizada getCurrentInstrutorId() de includes/auth.php
    // Arquivo: admin/api/ocorrencias-instrutor.php (linha ~65)
    // Mesmo padrão usado na Fase 1 (admin/api/instrutor-aulas.php)
    $instrutorId = getCurrentInstrutorId($user['id']);
    if (!$instrutorId) {
        // Log detalhado para diagnóstico
        if (defined('LOG_ENABLED') && LOG_ENABLED) {
            error_log(sprintf(
                '[OCORRENCIAS_API] Instrutor não encontrado - usuario_id=%d, tipo=%s, email=%s, timestamp=%s, ip=%s',
                $user['id'],
                $user['tipo'] ?? 'não definido',
                $user['email'] ?? 'não definido',
                date('Y-m-d H:i:s'),
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ));
        }
        returnJsonError('Instrutor não encontrado. Verifique seu cadastro.', 404);
    }

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            // Registrar nova ocorrência
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }

            // Validações obrigatórias
            if (empty($input['tipo'])) {
                returnJsonError('Tipo da ocorrência é obrigatório');
            }
            if (!in_array($input['tipo'], ['atraso_aluno', 'problema_veiculo', 'infraestrutura', 'comportamento_aluno', 'outro'])) {
                returnJsonError('Tipo de ocorrência inválido');
            }
            if (empty($input['data_ocorrencia'])) {
                returnJsonError('Data da ocorrência é obrigatória');
            }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['data_ocorrencia'])) {
                returnJsonError('Formato de data inválido. Use YYYY-MM-DD');
            }
            if (empty($input['descricao'])) {
                returnJsonError('Descrição é obrigatória');
            }
            if (strlen(trim($input['descricao'])) < 10) {
                returnJsonError('Descrição deve ter no mínimo 10 caracteres');
            }

            $tipo = $input['tipo'];
            $dataOcorrencia = $input['data_ocorrencia'];
            $descricao = trim($input['descricao']);
            $aulaId = !empty($input['aula_id']) ? (int)$input['aula_id'] : null;

            // FASE 2 - Correção: Validação de segurança - aula deve pertencer ao instrutor
            // Arquivo: admin/api/ocorrencias-instrutor.php (linha ~110)
            // Mesma lógica da Fase 1 (admin/api/instrutor-aulas.php linha 89-96)
            if ($aulaId) {
                $aula = $db->fetch("
                    SELECT id FROM aulas 
                    WHERE id = ? AND instrutor_id = ? AND status != 'cancelada'
                ", [$aulaId, $instrutorId]);

                if (!$aula) {
                    // Log de tentativa de acesso não autorizado
                    if (defined('LOG_ENABLED') && LOG_ENABLED) {
                        error_log(sprintf(
                            '[OCORRENCIAS_API] Tentativa de vincular ocorrência a aula não autorizada - usuario_id=%d, instrutor_id=%d, aula_id=%d, timestamp=%s, ip=%s',
                            $user['id'],
                            $instrutorId,
                            $aulaId,
                            date('Y-m-d H:i:s'),
                            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
                        ));
                    }
                    returnJsonError('Aula não encontrada ou não pertence a você', 403);
                }
            }

            // Verificar se tabela existe
            try {
                $tableExists = $db->fetch("SHOW TABLES LIKE 'ocorrencias_instrutor'");
                if (!$tableExists) {
                    returnJsonError('Tabela de ocorrências não está disponível. Contate o administrador.', 500);
                }
            } catch (Exception $e) {
                if (defined('LOG_ENABLED') && LOG_ENABLED) {
                    error_log('[OCORRENCIAS_API] Erro ao verificar tabela: ' . $e->getMessage());
                }
                returnJsonError('Erro ao acessar banco de dados', 500);
            }

            // Inserir ocorrência
            try {
                $sql = "INSERT INTO ocorrencias_instrutor 
                        (instrutor_id, usuario_id, tipo, data_ocorrencia, aula_id, descricao, status, criado_em)
                        VALUES (?, ?, ?, ?, ?, ?, 'aberta', NOW())";
                
                $params = [$instrutorId, $user['id'], $tipo, $dataOcorrencia, $aulaId, $descricao];
                
                $result = $db->query($sql, $params);
                
                if ($result) {
                    $ocorrenciaId = $db->lastInsertId();
                    
                    // Log de sucesso
                    if (defined('LOG_ENABLED') && LOG_ENABLED) {
                        error_log(sprintf(
                            '[OCORRENCIAS_API] Ocorrência registrada - usuario_id=%d, instrutor_id=%d, ocorrencia_id=%d, tipo=%s, timestamp=%s',
                            $user['id'],
                            $instrutorId,
                            $ocorrenciaId,
                            $tipo,
                            date('Y-m-d H:i:s')
                        ));
                    }
                    
                    returnJsonSuccess([
                        'ocorrencia_id' => $ocorrenciaId,
                        'tipo' => $tipo,
                        'data_ocorrencia' => $dataOcorrencia
                    ], 'Ocorrência registrada com sucesso');
                } else {
                    throw new Exception('Erro ao inserir ocorrência no banco de dados');
                }
            } catch (Exception $e) {
                if (defined('LOG_ENABLED') && LOG_ENABLED) {
                    error_log('[OCORRENCIAS_API] Erro ao inserir ocorrência: ' . $e->getMessage());
                }
                returnJsonError('Erro ao registrar ocorrência: ' . (DEBUG_MODE ? $e->getMessage() : 'Tente novamente mais tarde'), 500);
            }
            break;

        case 'GET':
            // Listar ocorrências do instrutor
            $ocorrencias = [];
            
            try {
                $tableExists = $db->fetch("SHOW TABLES LIKE 'ocorrencias_instrutor'");
                if ($tableExists) {
                    $ocorrencias = $db->fetchAll("
                        SELECT o.*, 
                               a.data_aula as aula_data, a.hora_inicio as aula_hora,
                               al.nome as aluno_nome
                        FROM ocorrencias_instrutor o
                        LEFT JOIN aulas a ON o.aula_id = a.id
                        LEFT JOIN alunos al ON a.aluno_id = al.id
                        WHERE o.instrutor_id = ?
                        ORDER BY o.criado_em DESC
                    ", [$instrutorId]);
                }
            } catch (Exception $e) {
                if (defined('LOG_ENABLED') && LOG_ENABLED) {
                    error_log('[OCORRENCIAS_API] Erro ao buscar ocorrências: ' . $e->getMessage());
                }
            }
            
            returnJsonSuccess($ocorrencias, 'Ocorrências carregadas');
            break;

        default:
            returnJsonError('Método não permitido', 405);
    }

} catch (Exception $e) {
    // Log detalhado do erro
    if (defined('LOG_ENABLED') && LOG_ENABLED) {
        error_log(sprintf(
            '[OCORRENCIAS_API] Erro: %s | Arquivo: %s | Linha: %d | Trace: %s',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        ));
    }
    
    // Retornar erro JSON (nunca HTML)
    returnJsonError('Erro interno: ' . (defined('DEBUG_MODE') && DEBUG_MODE ? $e->getMessage() : 'Tente novamente mais tarde'), 500);
} catch (Error $e) {
    // Log detalhado do erro fatal
    if (defined('LOG_ENABLED') && LOG_ENABLED) {
        error_log(sprintf(
            '[OCORRENCIAS_API] Erro Fatal: %s | Arquivo: %s | Linha: %d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ));
    }
    
    // Retornar erro JSON (nunca HTML)
    returnJsonError('Erro fatal: ' . (defined('DEBUG_MODE') && DEBUG_MODE ? $e->getMessage() : 'Tente novamente mais tarde'), 500);
}
?>

