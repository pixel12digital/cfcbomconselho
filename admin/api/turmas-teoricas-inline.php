<?php
/**
 * API para edição inline de turmas teóricas
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir dependências
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../includes/TurmaTeoricaManager.php';

// Instanciar o gerenciador
$turmaManager = new TurmaTeoricaManager();
$db = Database::getInstance();

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Obter dados JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

$action = $input['action'] ?? '';
$turmaId = $input['turma_id'] ?? null;
$field = $input['field'] ?? '';
$value = $input['value'] ?? '';

// Validação específica por ação
if ($action === 'update_field') {
    if (!$turmaId || !$field) {
        echo json_encode(['success' => false, 'message' => 'Parâmetros obrigatórios não fornecidos']);
        exit;
    }
} elseif ($action === 'add_disciplina' || $action === 'remove_disciplina' || $action === 'update_disciplina') {
    if (!$turmaId) {
        echo json_encode(['success' => false, 'message' => 'ID da turma é obrigatório']);
        exit;
    }
}

try {
    switch ($action) {
        case 'update_field':
            $result = updateTurmaField($turmaId, $field, $value);
            break;
            
        case 'add_disciplina':
            $disciplinaId = $input['disciplina_id'] ?? null;
            $cargaHoraria = $input['carga_horaria'] ?? null;
            $result = addDisciplinaToTurma($turmaId, $disciplinaId, $cargaHoraria);
            break;
            
        case 'remove_disciplina':
            $disciplinaId = $input['disciplina_id'] ?? null;
            $result = removeDisciplinaFromTurma($turmaId, $disciplinaId);
            break;
            
        case 'update_disciplina':
            $disciplinaIdAtual = $input['disciplina_id_atual'] ?? null;
            $disciplinaIdNova = $input['disciplina_id_nova'] ?? null;
            $cargaHoraria = $input['carga_horaria'] ?? null;
            $result = updateDisciplinaInTurma($turmaId, $disciplinaIdAtual, $disciplinaIdNova, $cargaHoraria);
            break;
            
        default:
            $result = ['success' => false, 'message' => 'Ação não reconhecida'];
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno: ' . $e->getMessage()
    ]);
}

function updateTurmaField($turmaId, $field, $value) {
    global $db;
    
    // Campos permitidos para edição
    $allowedFields = [
        'nome', 'curso_tipo', 'data_inicio', 'data_fim', 'sala_id', 
        'modalidade', 'status', 'observacoes', 'max_alunos'
    ];
    
    if (!in_array($field, $allowedFields)) {
        return ['success' => false, 'message' => 'Campo não permitido para edição'];
    }
    
    // Validar dados específicos
    $validation = validateField($field, $value);
    if (!$validation['valid']) {
        return ['success' => false, 'message' => $validation['message']];
    }
    
    // Preparar query
    $sql = "UPDATE turmas_teoricas SET {$field} = ? WHERE id = ?";
    $params = [$value, $turmaId];
    
    // Executar atualização
    $result = $db->query($sql, $params);
    
    if ($result) {
        return ['success' => true, 'message' => 'Campo atualizado com sucesso'];
    } else {
        return ['success' => false, 'message' => 'Erro ao atualizar campo'];
    }
}

function validateField($field, $value) {
    switch ($field) {
        case 'nome':
            if (empty($value) || strlen($value) < 3) {
                return ['valid' => false, 'message' => 'Nome deve ter pelo menos 3 caracteres'];
            }
            break;
            
        case 'curso_tipo':
            $validTipos = ['formacao_45h', 'formacao_acc_20h', 'reciclagem_infrator', 'atualizacao'];
            if (!in_array($value, $validTipos)) {
                return ['valid' => false, 'message' => 'Tipo de curso inválido'];
            }
            break;
            
        case 'data_inicio':
        case 'data_fim':
            if (!strtotime($value)) {
                return ['valid' => false, 'message' => 'Data inválida'];
            }
            break;
            
        case 'sala_id':
            if (!is_numeric($value) || $value <= 0) {
                return ['valid' => false, 'message' => 'Sala inválida'];
            }
            break;
            
        case 'modalidade':
            $validModalidades = ['presencial', 'online', 'hibrida'];
            if (!in_array($value, $validModalidades)) {
                return ['valid' => false, 'message' => 'Modalidade inválida'];
            }
            break;
            
        case 'status':
            $validStatus = ['criando', 'agendando', 'completa', 'ativa', 'concluida'];
            if (!in_array($value, $validStatus)) {
                return ['valid' => false, 'message' => 'Status inválido'];
            }
            break;
            
        case 'max_alunos':
            if (!is_numeric($value) || $value <= 0) {
                return ['valid' => false, 'message' => 'Número máximo de alunos inválido'];
            }
            break;
    }
    
    return ['valid' => true];
}

function addDisciplinaToTurma($turmaId, $disciplinaId, $cargaHoraria) {
    global $db;
    
    if (!$disciplinaId || !$cargaHoraria) {
        return ['success' => false, 'message' => 'Disciplina e carga horária são obrigatórios'];
    }
    
    // No sistema atual, as disciplinas são definidas automaticamente pelo tipo de curso
    // Não é necessário adicionar disciplinas manualmente
    return ['success' => false, 'message' => 'As disciplinas são definidas automaticamente pelo tipo de curso selecionado. Para alterar as disciplinas, altere o tipo de curso da turma.'];
}

function removeDisciplinaFromTurma($turmaId, $disciplinaId) {
    // No sistema atual, as disciplinas são definidas automaticamente pelo tipo de curso
    // Não é possível remover disciplinas individualmente
    return ['success' => false, 'message' => 'As disciplinas são definidas automaticamente pelo tipo de curso selecionado. Para alterar as disciplinas, altere o tipo de curso da turma.'];
}

function updateDisciplinaInTurma($turmaId, $disciplinaIdAtual, $disciplinaIdNova, $cargaHoraria) {
    global $db;
    
    if (!$disciplinaIdAtual || !$disciplinaIdNova || !$cargaHoraria) {
        return ['success' => false, 'message' => 'Parâmetros obrigatórios não fornecidos'];
    }
    
    // Verificar se a nova disciplina existe e obter seus dados
    $sql = "SELECT id, nome, cor_hex FROM disciplinas WHERE id = ?";
    $disciplina = $db->fetch($sql, [$disciplinaIdNova]);
    
    if (!$disciplina) {
        return ['success' => false, 'message' => 'Nova disciplina não encontrada'];
    }
    
    // Atualizar disciplina na turma
    $sql = "UPDATE turmas_disciplinas 
            SET disciplina_id = ?, nome_disciplina = ?, carga_horaria_padrao = ?, cor_hex = ? 
            WHERE turma_id = ? AND disciplina_id = ?";
    $result = $db->query($sql, [$disciplinaIdNova, $disciplina['nome'], $cargaHoraria, $disciplina['cor_hex'], $turmaId, $disciplinaIdAtual]);
    
    if ($result) {
        return ['success' => true, 'message' => 'Disciplina atualizada com sucesso'];
    } else {
        return ['success' => false, 'message' => 'Erro ao atualizar disciplina'];
    }
}
?>
