<?php
// API para remover veículos
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    
    $db = Database::getInstance();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['veiculo_id'])) {
        // Remover veículo específico
        $veiculoId = (int)$input['veiculo_id'];
        
        // Verificar se veículo existe
        $veiculo = $db->fetch("SELECT * FROM veiculos WHERE id = ?", [$veiculoId]);
        if (!$veiculo) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Veículo não encontrado']);
            exit;
        }
        
        // Remover veículo
        $result = $db->delete('veiculos', 'id = ?', [$veiculoId]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Veículo removido com sucesso']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erro ao remover veículo']);
        }
        
    } elseif (isset($input['cfc_id']) && isset($input['remover_todos'])) {
        // Remover todos os veículos de um CFC
        $cfcId = (int)$input['cfc_id'];
        
        // Verificar se CFC existe
        $cfc = $db->fetch("SELECT * FROM cfcs WHERE id = ?", [$cfcId]);
        if (!$cfc) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'CFC não encontrado']);
            exit;
        }
        
        // Contar veículos
        $totalVeiculos = $db->count('veiculos', 'cfc_id = ?', [$cfcId]);
        
        if ($totalVeiculos == 0) {
            echo json_encode(['success' => true, 'message' => 'Nenhum veículo para remover']);
            exit;
        }
        
        // Remover todos os veículos
        $result = $db->delete('veiculos', 'cfc_id = ?', [$cfcId]);
        
        if ($result) {
            echo json_encode([
                'success' => true, 
                'message' => "Todos os {$totalVeiculos} veículos foram removidos com sucesso"
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erro ao remover veículos']);
        }
        
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro interno: ' . $e->getMessage()]);
}
?>
