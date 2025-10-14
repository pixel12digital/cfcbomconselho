<?php
/**
 * API AJAX para gerenciamento de salas
 */

// Desabilitar exibição de erros para evitar output HTML
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Limpar qualquer output anterior
while (ob_get_level()) {
    ob_end_clean();
}

// Incluir dependências com supressão de erros
@include_once __DIR__ . '/../../includes/config.php';
@include_once __DIR__ . '/../../includes/database.php';
@include_once __DIR__ . '/../../includes/auth.php';

// Verificação simplificada de autenticação
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Não autorizado']);
    exit;
}

// Usuário simulado para teste
$user = [
    'id' => $_SESSION['user_id'] ?? 1,
    'tipo' => $_SESSION['user_type'] ?? 'admin',
    'cfc_id' => $_SESSION['cfc_id'] ?? 1
];

// Conexão simplificada com banco
try {
    $db = Database::getInstance();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro de conexão com banco de dados']);
    exit;
}

// Definir headers para JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Obter ação
$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

// Iniciar buffer de output para capturar qualquer erro
ob_start();

try {
    switch ($acao) {
        case 'listar':
            // Dados mockados temporariamente para teste
            $salas = [
                [
                    'id' => 1,
                    'nome' => 'Sala 1',
                    'capacidade' => 30,
                    'ativa' => 1,
                    'turmas_ativas' => 0
                ],
                [
                    'id' => 2,
                    'nome' => 'Sala 2',
                    'capacidade' => 25,
                    'ativa' => 1,
                    'turmas_ativas' => 0
                ]
            ];
            
            // Gerar HTML das salas
            $html = '';
            if (empty($salas)) {
                $html = '<div class="text-center py-3">
                    <i class="fas fa-door-open fa-2x text-muted mb-2"></i>
                    <p class="text-muted">Nenhuma sala cadastrada</p>
                </div>';
            } else {
                foreach ($salas as $sala) {
                    $equipamentos = json_decode($sala['equipamentos'] ?? '{}', true);
                    $equipamentosList = '';
                    if (!empty($equipamentos)) {
                        $equipamentosList = '<div class="equipamentos-list mt-1">';
                        foreach ($equipamentos as $equipamento => $disponivel) {
                            if ($disponivel === true || $disponivel === 'true') {
                                $equipamentosList .= '<div><i class="fas fa-check-circle me-1 text-success"></i>' . ucfirst(str_replace('_', ' ', $equipamento)) . '</div>';
                            }
                        }
                        $equipamentosList .= '</div>';
                    }
                    
                    $statusBadge = $sala['ativa'] ? '<span class="badge bg-success">Ativa</span>' : '<span class="badge bg-secondary">Inativa</span>';
                    
                    $html .= '<div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="fas fa-door-open me-2"></i>' . htmlspecialchars($sala['nome']) . '</h6>
                                ' . $statusBadge . '
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <strong><i class="fas fa-users me-1"></i>Capacidade:</strong> ' . $sala['capacidade'] . ' alunos
                                </div>
                                ' . ($equipamentosList ? '<div class="mb-2"><strong><i class="fas fa-tools me-1"></i>Equipamentos:</strong>' . $equipamentosList . '</div>' : '') . '
                                <div class="mb-2">
                                    <strong><i class="fas fa-chalkboard-teacher me-1"></i>Turmas Ativas:</strong> ' . $sala['turmas_ativas'] . '
                                </div>
                            </div>
                        </div>
                    </div>';
                }
                $html = '<div class="row">' . $html . '</div>';
            }
            
            echo json_encode([
                'sucesso' => true,
                'salas' => $salas,
                'html' => $html
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'criar':
            // Criar nova sala
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método não permitido');
            }
            
            $nome = trim($_POST['nome'] ?? '');
            $capacidade = (int)($_POST['capacidade'] ?? 30);
            $equipamentos = $_POST['equipamentos'] ?? [];
            $ativa = 1;
            
            $erros = [];
            
            if (empty($nome)) {
                $erros[] = 'Nome da sala é obrigatório';
            }
            
            if ($capacidade <= 0) {
                $erros[] = 'Capacidade deve ser maior que zero';
            }
            
            if (!empty($erros)) {
                throw new Exception('Erros encontrados: ' . implode(', ', $erros));
            }
            
            // Verificar se já existe sala com o mesmo nome
            $salaExistente = $db->fetch(
                "SELECT id FROM salas WHERE nome = ? AND cfc_id = ?",
                [$nome, $user['cfc_id'] ?? 1]
            );
            
            if ($salaExistente) {
                throw new Exception('Já existe uma sala com este nome');
            }
            
            // Inserir nova sala
            $equipamentosJson = json_encode($equipamentos);
            
            $db->insert('salas', [
                'nome' => $nome,
                'capacidade' => $capacidade,
                'equipamentos' => $equipamentosJson,
                'ativa' => $ativa,
                'cfc_id' => $user['cfc_id'] ?? 1
            ]);
            
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Sala criada com sucesso!',
                'sala' => [
                    'id' => $db->lastInsertId(),
                    'nome' => $nome,
                    'capacidade' => $capacidade
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            throw new Exception('Ação não reconhecida');
    }
    
} catch (Exception $e) {
    // Limpar qualquer output não desejado
    ob_clean();
    
    http_response_code(400);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} finally {
    // Limpar buffer e garantir que apenas JSON seja enviado
    $output = ob_get_clean();
    if (!empty($output) && !json_decode($output)) {
        // Se houver output não-JSON, limpar e enviar erro
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Erro interno do servidor'
        ], JSON_UNESCAPED_UNICODE);
    }
}
?>
