<?php
/**
 * API REST para o Sistema de Agendamento
 * Endpoints para gerenciar aulas, verificar disponibilidade e obter estatísticas
 * 
 * @author Sistema CFC
 * @version 1.0
 * @since 2024
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratar requisições OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/controllers/AgendamentoController.php';

// Verificar autenticação
$auth = new Auth();
if (!$auth->isAuthenticated()) {
    http_response_code(401);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Usuário não autenticado',
        'tipo' => 'erro'
    ]);
    exit();
}

// Verificar permissões (apenas usuários admin ou instrutores)
$userRole = $auth->getUserRole();
if (!in_array($userRole, ['admin', 'instrutor'])) {
    http_response_code(403);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Acesso negado. Permissão insuficiente.',
        'tipo' => 'erro'
    ]);
    exit();
}

// Instanciar controller
$agendamentoController = new AgendamentoController();

// Obter método HTTP
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));
$endpoint = end($pathParts);

try {
    switch ($method) {
        case 'GET':
            handleGet($agendamentoController, $endpoint);
            break;
            
        case 'POST':
            handlePost($agendamentoController, $endpoint);
            break;
            
        case 'PUT':
            handlePut($agendamentoController, $endpoint);
            break;
            
        case 'DELETE':
            handleDelete($agendamentoController, $endpoint);
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Método não permitido',
                'tipo' => 'erro'
            ]);
            break;
    }
    
} catch (Exception $e) {
    error_log("Erro na API de agendamento: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro interno do servidor',
        'tipo' => 'erro'
    ]);
}

/**
 * Tratar requisições GET
 */
function handleGet($controller, $endpoint) {
    switch ($endpoint) {
        case 'aulas':
            // Listar aulas com filtros
            $filtros = $_GET;
            $aulas = $controller->listarAulas($filtros);
            
            echo json_encode([
                'sucesso' => true,
                'dados' => $aulas,
                'total' => count($aulas)
            ]);
            break;
            
        case 'aula':
            // Buscar aula específica
            $aulaId = $_GET['id'] ?? null;
            if (!$aulaId) {
                http_response_code(400);
                echo json_encode([
                    'sucesso' => false,
                    'mensagem' => 'ID da aula é obrigatório',
                    'tipo' => 'erro'
                ]);
                return;
            }
            
            $aula = $controller->buscarAula($aulaId);
            if (!$aula) {
                http_response_code(404);
                echo json_encode([
                    'sucesso' => false,
                    'mensagem' => 'Aula não encontrada',
                    'tipo' => 'erro'
                ]);
                return;
            }
            
            echo json_encode([
                'sucesso' => true,
                'dados' => $aula
            ]);
            break;
            
        case 'estatisticas':
            // Obter estatísticas
            $filtros = $_GET;
            $estatisticas = $controller->obterEstatisticas($filtros);
            
            echo json_encode([
                'sucesso' => true,
                'dados' => $estatisticas
            ]);
            break;
            
        case 'disponibilidade':
            // Verificar disponibilidade
            $dados = $_GET;
            $disponibilidade = $controller->verificarDisponibilidade($dados);
            
            echo json_encode([
                'sucesso' => true,
                'dados' => $disponibilidade
            ]);
            break;
            
        default:
            http_response_code(404);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Endpoint não encontrado',
                'tipo' => 'erro'
            ]);
            break;
    }
}

/**
 * Tratar requisições POST
 */
function handlePost($controller, $endpoint) {
    switch ($endpoint) {
        case 'aula':
            // Criar nova aula
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                http_response_code(400);
                echo json_encode([
                    'sucesso' => false,
                    'mensagem' => 'Dados inválidos',
                    'tipo' => 'erro'
                ]);
                return;
            }
            
            $resultado = $controller->criarAula($input);
            
            if ($resultado['sucesso']) {
                http_response_code(201);
                echo json_encode($resultado);
            } else {
                http_response_code(400);
                echo json_encode($resultado);
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Endpoint não encontrado',
                'tipo' => 'erro'
            ]);
            break;
    }
}

/**
 * Tratar requisições PUT
 */
function handlePut($controller, $endpoint) {
    switch ($endpoint) {
        case 'aula':
            // Atualizar aula existente
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['id'])) {
                http_response_code(400);
                echo json_encode([
                    'sucesso' => false,
                    'mensagem' => 'ID da aula é obrigatório',
                    'tipo' => 'erro'
                ]);
                return;
            }
            
            $aulaId = $input['id'];
            unset($input['id']); // Remover ID dos dados de atualização
            
            $resultado = $controller->atualizarAula($aulaId, $input);
            
            if ($resultado['sucesso']) {
                echo json_encode($resultado);
            } else {
                http_response_code(400);
                echo json_encode($resultado);
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Endpoint não encontrado',
                'tipo' => 'erro'
            ]);
            break;
    }
}

/**
 * Tratar requisições DELETE
 */
function handleDelete($controller, $endpoint) {
    switch ($endpoint) {
        case 'aula':
            // Excluir aula
            $aulaId = $_GET['id'] ?? null;
            
            if (!$aulaId) {
                http_response_code(400);
                echo json_encode([
                    'sucesso' => false,
                    'mensagem' => 'ID da aula é obrigatório',
                    'tipo' => 'erro'
                ]);
                return;
            }
            
            $resultado = $controller->excluirAula($aulaId);
            
            if ($resultado['sucesso']) {
                echo json_encode($resultado);
            } else {
                http_response_code(400);
                echo json_encode($resultado);
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Endpoint não encontrado',
                'tipo' => 'erro'
            ]);
            break;
    }
}
?>
