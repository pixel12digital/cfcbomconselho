<?php
// Suprimir todos os warnings e notices
error_reporting(0);
ini_set('display_errors', 0);

// Limpar qualquer output anterior
ob_clean();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../includes/config.php';
require_once '../../includes/database.php';

// API de teste sem autenticação
$db = Database::getInstance();
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
                    echo json_encode(['error' => 'Aluno não encontrado']);
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
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno: ' . $e->getMessage()]);
}
?>
