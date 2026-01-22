<?php
/**
 * API para buscar disciplinas de um curso específico
 */

// Configurações básicas
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Verificar se é requisição OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Credenciais removidas - usar includes/config.php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';

// Função para conectar ao banco usando Database::getInstance()
function conectarBanco() {
    try {
        $db = Database::getInstance();
        return $db->getConnection();
    } catch (Exception $e) {
        return null;
    }
}

try {
    $acao = $_GET['acao'] ?? 'buscar';
    $codigoCurso = $_GET['codigo'] ?? '';
    
    if ($acao === 'buscar' && !empty($codigoCurso)) {
        $pdo = conectarBanco();
        if (!$pdo) {
            throw new Exception('Erro de conexão com banco de dados');
        }
        
        // Buscar disciplinas configuradas para este curso
        $stmt = $pdo->prepare("
            SELECT disciplina, nome_disciplina, aulas_obrigatorias, ordem 
            FROM disciplinas_configuracao 
            WHERE curso_tipo = ? AND ativa = 1 
            ORDER BY ordem
        ");
        $stmt->execute([$codigoCurso]);
        $disciplinasConfiguradas = $stmt->fetchAll();
        
        // Mapear códigos para IDs
        $mapeamentoCodigos = [
            'legislacao_transito' => 1,
            'direcao_defensiva' => 2,
            'primeiros_socorros' => 3,
            'meio_ambiente_cidadania' => 4,
            'mecanica_basica' => 5
        ];
        
        $disciplinasSelecionadas = [];
        foreach ($disciplinasConfiguradas as $disc) {
            if (isset($mapeamentoCodigos[$disc['disciplina']])) {
                $disciplinasSelecionadas[] = $mapeamentoCodigos[$disc['disciplina']];
            }
        }
        
        echo json_encode([
            'sucesso' => true,
            'disciplinas_selecionadas' => $disciplinasSelecionadas,
            'disciplinas_configuradas' => $disciplinasConfiguradas
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        throw new Exception('Código do curso não fornecido');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
