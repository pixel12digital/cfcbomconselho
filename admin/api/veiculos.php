<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Usar caminho relativo que sabemos que funciona
require_once '../../includes/config.php';
require_once '../../includes/database.php';

try {
    $db = new Database();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Listar veículos ou buscar por ID
            if (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                $veiculo = $db->query("
                    SELECT v.*, c.nome as cfc_nome 
                    FROM veiculos v 
                    LEFT JOIN cfcs c ON v.cfc_id = c.id 
                    WHERE v.id = ?
                ", [$id])->fetch(PDO::FETCH_ASSOC);
                
                if ($veiculo) {
                    echo json_encode(['success' => true, 'data' => $veiculo]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Veículo não encontrado']);
                }
            } else {
                // Listar todos os veículos
                $veiculos = $db->query("
                    SELECT v.*, c.nome as cfc_nome 
                    FROM veiculos v 
                    LEFT JOIN cfcs c ON v.cfc_id = c.id 
                    ORDER BY v.marca, v.modelo
                ")->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'data' => $veiculos]);
            }
            break;
            
        case 'POST':
            // Criar novo veículo
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                throw new Exception('Dados inválidos');
            }
            
            // Validações básicas
            if (empty($input['placa']) || empty($input['marca']) || empty($input['modelo'])) {
                throw new Exception('Placa, marca e modelo são obrigatórios');
            }
            
            // Verificar se a placa já existe
            $placaExistente = $db->query("SELECT id FROM veiculos WHERE placa = ?", [$input['placa']])->fetch();
            if ($placaExistente) {
                throw new Exception('Placa já cadastrada no sistema');
            }
            
            $resultado = $db->insert('veiculos', [
                'placa' => $input['placa'],
                'marca' => $input['marca'],
                'modelo' => $input['modelo'],
                'ano' => $input['ano'] ?? null,
                'cor' => $input['cor'] ?? null,
                'chassi' => $input['chassi'] ?? null,
                'renavam' => $input['renavam'] ?? null,
                'combustivel' => $input['combustivel'] ?? null,
                'quilometragem' => $input['quilometragem'] ?? 0,
                'km_manutencao' => $input['km_manutencao'] ?? null,
                'categoria_cnh' => $input['categoria_cnh'] ?? 'B',
                'cfc_id' => $input['cfc_id'] ?? null,
                'status' => $input['status'] ?? 'ativo',
                'disponivel' => $input['disponivel'] ?? true,
                'data_aquisicao' => $input['data_aquisicao'] ?? null,
                'valor_aquisicao' => $input['valor_aquisicao'] ?? null,
                'proxima_manutencao' => $input['proxima_manutencao'] ?? null,
                'observacoes' => $input['observacoes'] ?? null,
                'ativo' => $input['ativo'] ?? true,
                'criado_em' => date('Y-m-d H:i:s')
            ]);
            
            if ($resultado) {
                echo json_encode(['success' => true, 'message' => 'Veículo criado com sucesso', 'id' => $resultado]);
            } else {
                throw new Exception('Erro ao criar veículo');
            }
            break;
            
        case 'PUT':
            // Atualizar veículo existente
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['id'])) {
                throw new Exception('ID do veículo é obrigatório');
            }
            
            $id = (int)$input['id'];
            
            // Verificar se o veículo existe
            $veiculoExistente = $db->query("SELECT id FROM veiculos WHERE id = ?", [$id])->fetch();
            if (!$veiculoExistente) {
                throw new Exception('Veículo não encontrado');
            }
            
            // Verificar se a placa já existe em outro veículo
            if (isset($input['placa'])) {
                $placaExistente = $db->query("SELECT id FROM veiculos WHERE placa = ? AND id != ?", [$input['placa'], $id])->fetch();
                if ($placaExistente) {
                    throw new Exception('Placa já cadastrada em outro veículo');
                }
            }
            
            // Preparar dados para atualização
            $dadosAtualizacao = [];
            $campos = ['placa', 'marca', 'modelo', 'ano', 'cor', 'chassi', 'renavam', 'combustivel', 
                      'quilometragem', 'km_manutencao', 'categoria_cnh', 'cfc_id', 'status', 
                      'disponivel', 'data_aquisicao', 'valor_aquisicao', 'proxima_manutencao', 
                      'observacoes', 'ativo'];
            
            foreach ($campos as $campo) {
                if (isset($input[$campo])) {
                    $dadosAtualizacao[$campo] = $input[$campo];
                }
            }
            
            $dadosAtualizacao['atualizado_em'] = date('Y-m-d H:i:s');
            
            $resultado = $db->update('veiculos', $dadosAtualizacao, 'id = ?', [$id]);
            
            if ($resultado) {
                echo json_encode(['success' => true, 'message' => 'Veículo atualizado com sucesso']);
            } else {
                throw new Exception('Erro ao atualizar veículo');
            }
            break;
            
        case 'DELETE':
            // Excluir veículo
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['id'])) {
                throw new Exception('ID do veículo é obrigatório');
            }
            
            $id = (int)$input['id'];
            
            // Verificar se o veículo existe
            $veiculoExistente = $db->query("SELECT id, placa FROM veiculos WHERE id = ?", [$id])->fetch();
            if (!$veiculoExistente) {
                throw new Exception('Veículo não encontrado');
            }
            
            // Verificar se há aulas vinculadas
            $aulasVinculadas = $db->query("SELECT COUNT(*) as total FROM aulas WHERE veiculo_id = ?", [$id])->fetch();
            if ($aulasVinculadas['total'] > 0) {
                throw new Exception('Não é possível excluir veículo com aulas vinculadas');
            }
            
            // Excluir veículo
            $resultado = $db->delete('veiculos', 'id = ?', [$id]);
            
            if ($resultado) {
                echo json_encode(['success' => true, 'message' => 'Veículo excluído com sucesso']);
            } else {
                throw new Exception('Erro ao excluir veículo');
            }
            break;
            
        default:
            throw new Exception('Método não permitido');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro interno do servidor']);
}
?>
