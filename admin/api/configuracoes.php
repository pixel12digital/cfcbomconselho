<?php
/**
 * API para Configurações de Categorias
 * 
 * Endpoint para gerenciar configurações de categorias de habilitação
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../includes/configuracoes_categorias.php';

// Verificar se usuário está logado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

// Verificar método da requisição
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action);
            break;
        case 'POST':
            handlePostRequest($action);
            break;
        case 'PUT':
            handlePutRequest($action);
            break;
        case 'DELETE':
            handleDeleteRequest($action);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno: ' . $e->getMessage()]);
}

/**
 * Processar requisições GET
 */
function handleGetRequest($action) {
    switch ($action) {
        case 'list':
            $configManager = ConfiguracoesCategorias::getInstance();
            $configuracoes = $configManager->getAllConfiguracoes();
            echo json_encode([
                'success' => true,
                'data' => $configuracoes
            ]);
            break;
            
        case 'get':
            $categoria = $_GET['categoria'] ?? '';
            if (empty($categoria)) {
                http_response_code(400);
                echo json_encode(['error' => 'Categoria não fornecida']);
                return;
            }
            
            $configManager = ConfiguracoesCategorias::getInstance();
            $configuracao = $configManager->getConfiguracaoByCategoria($categoria);
            if (!$configuracao) {
                http_response_code(404);
                echo json_encode(['error' => 'Configuração não encontrada']);
                return;
            }
            
            echo json_encode([
                'success' => true,
                'configuracao' => $configuracao
            ]);
            break;
            
        case 'categorias':
            $configManager = ConfiguracoesCategorias::getInstance();
            $configuracoes = $configManager->getAllConfiguracoes();
            $categorias = array_column($configuracoes, 'categoria');
            echo json_encode([
                'success' => true,
                'categorias' => $categorias
            ]);
            break;
            
        case 'primeira_habilitacao':
            $configManager = ConfiguracoesCategorias::getInstance();
            $configuracoes = $configManager->getAllConfiguracoes();
            $categorias = array_filter($configuracoes, function($c) {
                return $c['tipo'] === 'primeira_habilitacao';
            });
            echo json_encode([
                'success' => true,
                'categorias' => array_values($categorias)
            ]);
            break;
            
        case 'adicao':
            $configManager = ConfiguracoesCategorias::getInstance();
            $configuracoes = $configManager->getAllConfiguracoes();
            $categorias = array_filter($configuracoes, function($c) {
                return $c['tipo'] === 'adicao';
            });
            echo json_encode([
                'success' => true,
                'categorias' => array_values($categorias)
            ]);
            break;
            
        case 'disciplinas':
            $categoria = $_GET['categoria'] ?? '';
            if (empty($categoria)) {
                http_response_code(400);
                echo json_encode(['error' => 'Categoria não fornecida']);
                return;
            }
            
            $configManager = ConfiguracoesCategorias::getInstance();
            $disciplinas = $configManager->getDisciplinasTeoricas($categoria);
            if (!$disciplinas) {
                http_response_code(404);
                echo json_encode(['error' => 'Disciplinas não encontradas para esta categoria']);
                return;
            }
            
            echo json_encode([
                'success' => true,
                'disciplinas' => $disciplinas
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Ação não reconhecida']);
            break;
    }
}

/**
 * Processar requisições POST
 */
function handlePostRequest($action) {
    switch ($action) {
        case 'create':
        case 'save':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }
            
            $configManager = ConfiguracoesCategorias::getInstance();
            
            try {
                $resultado = $configManager->saveConfiguracao($input);
                echo json_encode([
                    'success' => true,
                    'message' => 'Configuração salva com sucesso',
                    'id' => $resultado
                ]);
            } catch (Exception $e) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
            break;
            
        case 'restore':
            $categoria = $_POST['categoria'] ?? '';
            if (empty($categoria)) {
                http_response_code(400);
                echo json_encode(['error' => 'Categoria não fornecida']);
                return;
            }
            
            $configManager = ConfiguracoesCategorias::getInstance();
            
            try {
                $resultado = $configManager->restoreDefault($categoria);
                echo json_encode([
                    'success' => true,
                    'message' => 'Configuração restaurada para valores padrão'
                ]);
            } catch (Exception $e) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Ação não reconhecida']);
            break;
    }
}

/**
 * Processar requisições PUT
 */
function handlePutRequest($action) {
    switch ($action) {
        case 'update':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                http_response_code(400);
                echo json_encode(['error' => 'Dados não fornecidos']);
                return;
            }
            
            $configManager = ConfiguracoesCategorias::getInstance();
            
            try {
                $resultado = $configManager->saveConfiguracao($input);
                echo json_encode([
                    'success' => true,
                    'message' => 'Configuração atualizada com sucesso',
                    'id' => $resultado
                ]);
            } catch (Exception $e) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Ação não reconhecida']);
            break;
    }
}

/**
 * Processar requisições DELETE
 */
function handleDeleteRequest($action) {
    switch ($action) {
        case 'delete':
            $categoria = $_GET['categoria'] ?? '';
            if (empty($categoria)) {
                http_response_code(400);
                echo json_encode(['error' => 'Categoria não fornecida']);
                return;
            }
            
            $configManager = ConfiguracoesCategorias::getInstance();
            
            try {
                $resultado = $configManager->desativarConfiguracao($categoria);
                echo json_encode([
                    'success' => true,
                    'message' => 'Configuração removida com sucesso'
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Ação não reconhecida']);
            break;
    }
}
