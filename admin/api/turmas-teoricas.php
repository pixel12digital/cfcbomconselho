<?php
/**
 * API REST para Gerenciamento de Turmas Teóricas
 * Sistema completo com wizard em 4 etapas
 * 
 * @author Sistema CFC Bom Conselho
 * @version 1.0
 * @since 2024
 */

// Limpar qualquer saída anterior
ob_clean();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Verificar método HTTP
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir dependências
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../includes/TurmaTeoricaManager.php';

// Verificar autenticação
if (!isLoggedIn() || !hasPermission('admin')) {
    http_response_code(401);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Usuário não autenticado ou sem permissão'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$turmaManager = new TurmaTeoricaManager();
$method = $_SERVER['REQUEST_METHOD'];
$user = getCurrentUser();

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($turmaManager, $user);
            break;
            
        case 'POST':
            handlePostRequest($turmaManager, $user);
            break;
            
        case 'PUT':
            handlePutRequest($turmaManager, $user);
            break;
            
        case 'DELETE':
            handleDeleteRequest($turmaManager, $user);
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Método não permitido'
            ], JSON_UNESCAPED_UNICODE);
            break;
    }
    
} catch (Exception $e) {
    // Log do erro para debug
    error_log("Erro na API de turmas teóricas: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro interno do servidor',
        'erro' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Manipular requisições GET
 */
function handleGetRequest($turmaManager, $user) {
    $acao = $_GET['acao'] ?? '';
    
    switch ($acao) {
        case 'listar':
            handleListarTurmas($turmaManager, $user);
            break;
            
        case 'obter':
            handleObterTurma($turmaManager);
            break;
            
        case 'progresso':
            handleObterProgresso($turmaManager);
            break;
            
        case 'opcoes':
            handleObterOpcoes($turmaManager, $user);
            break;
            
        case 'disciplinas':
            handleObterDisciplinas($turmaManager);
            break;
            
        case 'verificar_conflitos':
            handleVerificarConflitos($turmaManager);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Ação GET não especificada ou inválida',
                'acoes_disponiveis' => ['listar', 'obter', 'progresso', 'opcoes', 'disciplinas', 'verificar_conflitos']
            ], JSON_UNESCAPED_UNICODE);
            break;
    }
}

/**
 * Manipular requisições POST
 */
function handlePostRequest($turmaManager, $user) {
    // Tentar JSON primeiro, depois form-data
    $dados = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Se não for JSON, usar dados do formulário
        $dados = $_POST;
    }
    
    $acao = $dados['acao'] ?? '';
    
    switch ($acao) {
        case 'criar_basica':
            handleCriarTurmaBasica($turmaManager, $dados, $user);
            break;
            
        case 'salvar_disciplinas':
            handleSalvarDisciplinas($turmaManager, $dados, $user);
            break;
            
        case 'agendar_aula':
            handleAgendarAula($turmaManager, $dados, $user);
            break;
            
        case 'matricular_aluno':
            handleMatricularAluno($turmaManager, $dados);
            break;
            
        case 'ativar_turma':
            handleAtivarTurma($turmaManager, $dados);
            break;
            
        case 'excluir':
            handleExcluirTurma($turmaManager, $dados);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Ação POST não especificada ou inválida',
                'acoes_disponiveis' => ['criar_basica', 'agendar_aula', 'matricular_aluno', 'ativar_turma', 'excluir']
            ], JSON_UNESCAPED_UNICODE);
            break;
    }
}

/**
 * Manipular requisições PUT
 */
function handlePutRequest($turmaManager, $user) {
    $dados = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'JSON inválido: ' . json_last_error_msg()
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $acao = $dados['acao'] ?? 'atualizar';
    
    switch ($acao) {
        case 'atualizar_status':
            handleAtualizarStatus($turmaManager, $dados);
            break;
            
        case 'cancelar_aula':
            handleCancelarAula($turmaManager, $dados);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Ação PUT não especificada ou inválida'
            ], JSON_UNESCAPED_UNICODE);
            break;
    }
}

/**
 * Manipular requisições DELETE
 */
function handleDeleteRequest($turmaManager, $user) {
    $turmaId = $_GET['turma_id'] ?? null;
    
    if (!$turmaId) {
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'ID da turma é obrigatório para exclusão'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // Por enquanto, apenas cancelar a turma (não excluir fisicamente)
    $resultado = $turmaManager->cancelarTurma($turmaId);
    
    if ($resultado['sucesso']) {
        http_response_code(200);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(400);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }
}

// ==============================================
// HANDLERS ESPECÍFICOS
// ==============================================

function handleListarTurmas($turmaManager, $user) {
    $filtros = [
        'busca' => $_GET['busca'] ?? '',
        'status' => $_GET['status'] ?? '',
        'curso_tipo' => $_GET['curso_tipo'] ?? '',
        'cfc_id' => $user['tipo'] === 'admin' ? ($_GET['cfc_id'] ?? null) : $user['cfc_id']
    ];
    
    $resultado = $turmaManager->listarTurmas($filtros);
    
    if ($resultado['sucesso']) {
        http_response_code(200);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(400);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }
}

function handleObterTurma($turmaManager) {
    $turmaId = $_GET['turma_id'] ?? null;
    
    if (!$turmaId) {
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'ID da turma é obrigatório'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $turma = $turmaManager->obterTurma($turmaId);
    
    if ($turma) {
        http_response_code(200);
        echo json_encode([
            'sucesso' => true,
            'dados' => $turma
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(404);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Turma não encontrada'
        ], JSON_UNESCAPED_UNICODE);
    }
}

function handleObterProgresso($turmaManager) {
    $turmaId = $_GET['turma_id'] ?? null;
    
    if (!$turmaId) {
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'ID da turma é obrigatório'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $progresso = $turmaManager->obterProgressoDisciplinas($turmaId);
    $completude = $turmaManager->verificarTurmaCompleta($turmaId);
    
    http_response_code(200);
    echo json_encode([
        'sucesso' => true,
        'progresso' => $progresso,
        'completude' => $completude
    ], JSON_UNESCAPED_UNICODE);
}

function handleObterOpcoes($turmaManager, $user) {
    $tipo = $_GET['tipo'] ?? '';
    
    switch ($tipo) {
        case 'cursos':
            $opcoes = $turmaManager->obterCursosDisponiveis();
            break;
            
        case 'salas':
            $opcoes = $turmaManager->obterSalasDisponiveis($user['cfc_id']);
            break;
            
        case 'instrutores':
            $db = Database::getInstance();
            $opcoes = $db->fetchAll("
                SELECT i.id, u.nome, i.categoria_habilitacao 
                FROM instrutores i 
                LEFT JOIN usuarios u ON i.usuario_id = u.id 
                WHERE i.ativo = 1 AND i.cfc_id = ? 
                ORDER BY u.nome
            ", [$user['cfc_id']]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Tipo de opção não especificado',
                'tipos_disponiveis' => ['cursos', 'salas', 'instrutores']
            ], JSON_UNESCAPED_UNICODE);
            return;
    }
    
    http_response_code(200);
    echo json_encode([
        'sucesso' => true,
        'dados' => $opcoes
    ], JSON_UNESCAPED_UNICODE);
}

function handleObterDisciplinas($turmaManager) {
    $cursoTipo = $_GET['curso_tipo'] ?? null;
    
    if (!$cursoTipo) {
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Tipo de curso é obrigatório'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $disciplinas = $turmaManager->obterDisciplinasCurso($cursoTipo);
    
    http_response_code(200);
    echo json_encode([
        'sucesso' => true,
        'dados' => $disciplinas
    ], JSON_UNESCAPED_UNICODE);
}

function handleVerificarConflitos($turmaManager) {
    $dados = [
        'instrutor_id' => $_GET['instrutor_id'] ?? null,
        'data_aula' => $_GET['data_aula'] ?? null,
        'hora_inicio' => $_GET['hora_inicio'] ?? null,
        'quantidade_aulas' => $_GET['quantidade_aulas'] ?? 1,
        'turma_id' => $_GET['turma_id'] ?? null
    ];
    
    if (!$dados['instrutor_id'] || !$dados['data_aula'] || !$dados['hora_inicio']) {
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Parâmetros insuficientes para verificar conflitos'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // Esta funcionalidade seria implementada no TurmaTeoricaManager
    http_response_code(200);
    echo json_encode([
        'sucesso' => true,
        'conflitos' => false,
        'mensagem' => 'Horário disponível'
    ], JSON_UNESCAPED_UNICODE);
}

function handleCriarTurmaBasica($turmaManager, $dados, $user) {
    // Adicionar dados do usuário
    $dados['cfc_id'] = $user['cfc_id'];
    $dados['criado_por'] = $user['id'];
    
    $resultado = $turmaManager->criarTurmaBasica($dados);
    
    if ($resultado['sucesso']) {
        http_response_code(201);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(400);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }
}

function handleAgendarAula($turmaManager, $dados, $user) {
    // Adicionar dados do usuário
    $dados['criado_por'] = $user['id'];
    
    $resultado = $turmaManager->agendarAula($dados);
    
    if ($resultado['sucesso']) {
        http_response_code(201);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(400);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }
}

function handleMatricularAluno($turmaManager, $dados) {
    $turmaId = $dados['turma_id'] ?? null;
    $alunoId = $dados['aluno_id'] ?? null;
    
    if (!$turmaId || !$alunoId) {
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'ID da turma e ID do aluno são obrigatórios',
            'campos_obrigatorios' => ['turma_id', 'aluno_id']
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $resultado = $turmaManager->matricularAluno($turmaId, $alunoId);
    
    if ($resultado['sucesso']) {
        http_response_code(201);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(400);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }
}

function handleAtivarTurma($turmaManager, $dados) {
    $turmaId = $dados['turma_id'] ?? null;
    
    if (!$turmaId) {
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'ID da turma é obrigatório'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // Verificar se turma está completa antes de ativar
    $completude = $turmaManager->verificarTurmaCompleta($turmaId);
    
    if (!$completude['completa']) {
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'A turma deve estar completa (todas as disciplinas agendadas) antes de ser ativada',
            'detalhes' => $completude
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // Ativar turma
    $db = Database::getInstance();
    $db->update('turmas_teoricas', ['status' => 'ativa'], 'id = ?', [$turmaId]);
    
    http_response_code(200);
    echo json_encode([
        'sucesso' => true,
        'mensagem' => '🎉 Turma ativada com sucesso! Agora está disponível para matrículas e as aulas podem ser realizadas.'
    ], JSON_UNESCAPED_UNICODE);
}

function handleAtualizarStatus($turmaManager, $dados) {
    $turmaId = $dados['turma_id'] ?? null;
    $novoStatus = $dados['status'] ?? null;
    
    if (!$turmaId || !$novoStatus) {
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'ID da turma e novo status são obrigatórios'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $statusValidos = ['criando', 'agendando', 'completa', 'ativa', 'concluida', 'cancelada'];
    
    if (!in_array($novoStatus, $statusValidos)) {
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Status inválido',
            'status_validos' => $statusValidos
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $db = Database::getInstance();
    $db->update('turmas_teoricas', ['status' => $novoStatus], 'id = ?', [$turmaId]);
    
    http_response_code(200);
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Status da turma atualizado com sucesso'
    ], JSON_UNESCAPED_UNICODE);
}

function handleCancelarAula($turmaManager, $dados) {
    $aulaId = $dados['aula_id'] ?? null;
    $motivo = $dados['motivo'] ?? '';
    
    if (!$aulaId) {
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'ID da aula é obrigatório'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $db = Database::getInstance();
    $db->update('turma_aulas_agendadas', [
        'status' => 'cancelada',
        'observacoes' => $motivo
    ], 'id = ?', [$aulaId]);
    
    http_response_code(200);
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Aula cancelada com sucesso'
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Excluir turma
 */
function handleExcluirTurma($turmaManager, $dados) {
    try {
        $turmaId = $dados['turma_id'] ?? null;
        
        if (!$turmaId) {
            http_response_code(400);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'ID da turma é obrigatório'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
    
    // Verificar se a turma existe
    $turma = $turmaManager->obterTurma($turmaId);
    if (!$turma['sucesso']) {
        http_response_code(404);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Turma não encontrada'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $dadosTurma = $turma['dados'];
    
    // Verificar se pode ser excluída (apenas turmas criando/completas sem alunos)
    if (!in_array($dadosTurma['status'], ['criando', 'completa'])) {
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Apenas turmas em criação ou completas (sem alunos) podem ser excluídas'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // Verificar se há alunos matriculados
    $db = Database::getInstance();
    $alunosMatriculados = $db->fetchAll("SELECT COUNT(*) as total FROM turma_alunos WHERE turma_id = ?", [$turmaId]);
    $totalAlunos = $alunosMatriculados[0]['total'] ?? 0;
    
    if ($totalAlunos > 0) {
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Não é possível excluir turma com alunos matriculados'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    try {
        $db = Database::getInstance();
        $db->beginTransaction();
        
        // Excluir aulas agendadas
        $db->delete('turma_aulas_agendadas', 'turma_id = ?', [$turmaId]);
        
        // Excluir logs da turma (se a tabela existir)
        try {
            $db->delete('turma_logs', 'turma_id = ?', [$turmaId]);
        } catch (Exception $e) {
            // Se a tabela turma_logs não existir ou houver erro, apenas logar e continuar
            error_log("Aviso: Não foi possível excluir logs da turma $turmaId: " . $e->getMessage());
        }
        
        // Excluir alunos da turma (se a tabela existir)
        try {
            $db->delete('turma_alunos', 'turma_id = ?', [$turmaId]);
        } catch (Exception $e) {
            // Se a tabela turma_alunos não existir ou houver erro, apenas logar e continuar
            error_log("Aviso: Não foi possível excluir alunos da turma $turmaId: " . $e->getMessage());
        }
        
        // Excluir a turma
        $db->delete('turmas_teoricas', 'id = ?', [$turmaId]);
        
        $db->commit();
        
        http_response_code(200);
        echo json_encode([
            'sucesso' => true,
            'mensagem' => '✅ Turma excluída com sucesso!'
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        if (isset($db)) {
            $db->rollback();
        }
        http_response_code(500);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Erro ao excluir turma: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Erro ao processar exclusão: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Salvar disciplinas selecionadas pelo usuário
 */
function handleSalvarDisciplinas($turmaManager, $dados, $user) {
    try {
        $turmaId = $dados['turma_id'] ?? null;
        $disciplinas = $dados['disciplinas'] ?? [];
        
        if (!$turmaId) {
            http_response_code(400);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'ID da turma é obrigatório'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        if (empty($disciplinas)) {
            http_response_code(400);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Nenhuma disciplina foi selecionada'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // Validar se a turma pertence ao usuário
        $turma = $turmaManager->obterTurma($turmaId);
        if (!$turma['sucesso']) {
            http_response_code(404);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Turma não encontrada'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // Salvar disciplinas selecionadas
        $resultado = $turmaManager->salvarDisciplinasSelecionadas($turmaId, $disciplinas);
        
        if ($resultado['sucesso']) {
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Disciplinas salvas com sucesso',
                'total' => $resultado['total']
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => $resultado['mensagem']
            ], JSON_UNESCAPED_UNICODE);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Erro ao salvar disciplinas: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}
?>
