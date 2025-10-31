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
                'acoes_disponiveis' => ['criar_basica', 'agendar_aula', 'matricular_aluno', 'ativar_turma', 'excluir', 'cancelar_aula', 'editar_aula']
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
            
        case 'editar_aula':
            handleEditarAula($turmaManager, $dados);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Ação PUT não especificada ou inválida',
                'acoes_disponiveis' => ['atualizar_status', 'cancelar_aula', 'editar_aula']
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
        'turma_id' => $_GET['turma_id'] ?? null,
        'disciplina' => $_GET['disciplina'] ?? null,
        'instrutor_id' => $_GET['instrutor_id'] ?? null,
        'data_aula' => $_GET['data_aula'] ?? null,
        'hora_inicio' => $_GET['hora_inicio'] ?? null,
        'quantidade_aulas' => isset($_GET['quantidade_aulas']) ? (int)$_GET['quantidade_aulas'] : 1
    ];
    
    if (!$dados['turma_id'] || !$dados['instrutor_id'] || !$dados['data_aula'] || !$dados['hora_inicio'] || !$dados['disciplina']) {
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'disponivel' => false,
            'mensagem' => 'Parâmetros insuficientes para verificar conflitos. São necessários: turma_id, disciplina, instrutor_id, data_aula, hora_inicio'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    try {
        // Obter dados da turma
        $resultadoTurma = $turmaManager->obterTurma($dados['turma_id']);
        if (!$resultadoTurma['sucesso']) {
            http_response_code(404);
            echo json_encode([
                'sucesso' => false,
                'disponivel' => false,
                'mensagem' => 'Turma não encontrada'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        $turma = $resultadoTurma['dados'];
        
        // Usar método privado via Reflection ou criar método público
        // Por enquanto, vamos fazer a verificação diretamente aqui
        $db = Database::getInstance();
        $conflitos = [];
        $qtdAulas = $dados['quantidade_aulas'];
        
        // 1. Verificar carga horária da disciplina
        $validacaoCargaHoraria = verificarCargaHorariaDisciplinaAPI($turmaManager, $dados['turma_id'], $dados['disciplina'], $qtdAulas);
        if (!$validacaoCargaHoraria['disponivel']) {
            http_response_code(200);
            echo json_encode($validacaoCargaHoraria, JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // 2. Verificar conflitos de horário para cada aula
        for ($i = 0; $i < $qtdAulas; $i++) {
            $horaInicioAula = calcularHorarioAulaAPI($dados['hora_inicio'], $i);
            $horaFimAula = calcularHorarioFimAPI($horaInicioAula);
            
            // Verificar conflito de instrutor em aulas teóricas
            $conflitoInstrutorTeorica = $db->fetch("
                SELECT COUNT(*) as conflitos,
                       GROUP_CONCAT(CONCAT(nome_aula, ' (', hora_inicio, '-', hora_fim, ')') SEPARATOR ', ') as aulas_conflitantes
                FROM turma_aulas_agendadas 
                WHERE instrutor_id = ? 
                AND data_aula = ? 
                AND status = 'agendada'
                AND (
                    (hora_inicio < ? AND hora_fim > ?) OR
                    (hora_inicio >= ? AND hora_inicio < ?) OR
                    (hora_fim > ? AND hora_fim <= ?)
                )
            ", [
                $dados['instrutor_id'], 
                $dados['data_aula'], 
                $horaFimAula, $horaInicioAula, 
                $horaInicioAula, $horaFimAula, 
                $horaInicioAula, $horaFimAula
            ]);
            
            // Verificar conflito de instrutor em aulas práticas
            $conflitoInstrutorPratica = $db->fetch("
                SELECT COUNT(*) as conflitos
                FROM aulas 
                WHERE instrutor_id = ? 
                AND data_aula = ? 
                AND status IN ('agendada', 'confirmada')
                AND (
                    (hora_inicio < ? AND hora_fim > ?) OR
                    (hora_inicio >= ? AND hora_inicio < ?) OR
                    (hora_fim > ? AND hora_fim <= ?)
                )
            ", [
                $dados['instrutor_id'], 
                $dados['data_aula'], 
                $horaFimAula, $horaInicioAula, 
                $horaInicioAula, $horaFimAula, 
                $horaInicioAula, $horaFimAula
            ]);
            
            $totalConflitosInstrutor = ($conflitoInstrutorTeorica['conflitos'] ?? 0) + ($conflitoInstrutorPratica['conflitos'] ?? 0);
            
            if ($totalConflitosInstrutor > 0) {
                $instrutor = $db->fetch("
                    SELECT COALESCE(u.nome, i.nome, 'Instrutor') as nome
                    FROM instrutores i
                    LEFT JOIN usuarios u ON i.usuario_id = u.id
                    WHERE i.id = ?
                ", [$dados['instrutor_id']]);
                
                $nomeInstrutor = $instrutor['nome'] ?? 'Instrutor';
                $aulasConflitantes = $conflitoInstrutorTeorica['aulas_conflitantes'] ?? '';
                
                $conflitos[] = [
                    'tipo' => 'instrutor',
                    'mensagem' => "👨‍🏫 INSTRUTOR INDISPONÍVEL: O instrutor {$nomeInstrutor} já possui aula agendada no horário {$horaInicioAula} às {$horaFimAula}.",
                    'horario' => "{$horaInicioAula} - {$horaFimAula}",
                    'aulas_conflitantes' => $aulasConflitantes
                ];
            }
            
            // Verificar conflito de sala
            $conflitoSala = $db->fetch("
                SELECT COUNT(*) as conflitos,
                       GROUP_CONCAT(CONCAT(t.nome, ' - ', taa.nome_aula, ' (', taa.hora_inicio, '-', taa.hora_fim, ')') SEPARATOR ', ') as turmas_conflitantes
                FROM turma_aulas_agendadas taa
                JOIN turmas_teoricas t ON taa.turma_id = t.id
                WHERE taa.sala_id = ? 
                AND taa.data_aula = ? 
                AND taa.status = 'agendada'
                AND taa.turma_id != ?
                AND (
                    (taa.hora_inicio < ? AND taa.hora_fim > ?) OR
                    (taa.hora_inicio >= ? AND taa.hora_inicio < ?) OR
                    (taa.hora_fim > ? AND taa.hora_fim <= ?)
                )
            ", [
                $turma['sala_id'], 
                $dados['data_aula'], 
                $dados['turma_id'],
                $horaFimAula, $horaInicioAula, 
                $horaInicioAula, $horaFimAula, 
                $horaInicioAula, $horaFimAula
            ]);
            
            if ($conflitoSala && $conflitoSala['conflitos'] > 0) {
                $sala = $db->fetch("SELECT nome FROM salas WHERE id = ?", [$turma['sala_id']]);
                $nomeSala = $sala['nome'] ?? 'Sala';
                $turmasConflitantes = $conflitoSala['turmas_conflitantes'] ?? '';
                
                $conflitos[] = [
                    'tipo' => 'sala',
                    'mensagem' => "🏢 SALA INDISPONÍVEL: A sala {$nomeSala} já está ocupada no horário {$horaInicioAula} às {$horaFimAula}.",
                    'horario' => "{$horaInicioAula} - {$horaFimAula}",
                    'turmas_conflitantes' => $turmasConflitantes
                ];
            }
        }
        
        if (!empty($conflitos)) {
            http_response_code(200);
            echo json_encode([
                'sucesso' => true,
                'disponivel' => false,
                'mensagem' => '❌ Conflito de horário detectado',
                'conflitos' => $conflitos,
                'detalhes' => array_column($conflitos, 'mensagem')
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        http_response_code(200);
        echo json_encode([
            'sucesso' => true,
            'disponivel' => true,
            'mensagem' => '✅ Horário disponível! Você pode agendar as aulas.'
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        error_log("Erro ao verificar conflitos: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'sucesso' => false,
            'disponivel' => false,
            'mensagem' => 'Erro ao verificar disponibilidade: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}

// Funções auxiliares para cálculo de horários
function calcularHorarioAulaAPI($horarioInicial, $indiceAula) {
    $timestamp = strtotime($horarioInicial) + ($indiceAula * 50 * 60);
    return date('H:i:s', $timestamp);
}

function calcularHorarioFimAPI($horarioInicio) {
    $timestamp = strtotime($horarioInicio) + (50 * 60);
    return date('H:i:s', $timestamp);
}

// Função auxiliar para verificar carga horária
function verificarCargaHorariaDisciplinaAPI($turmaManager, $turmaId, $disciplina, $qtdAulasNovas) {
    try {
        $db = Database::getInstance();
        
        // Buscar curso_tipo da turma
        $turma = $db->fetch("SELECT curso_tipo FROM turmas_teoricas WHERE id = ?", [$turmaId]);
        if (!$turma) {
            return [
                'disponivel' => false,
                'mensagem' => 'Turma não encontrada'
            ];
        }
        
        // Buscar carga horária máxima
        $cargaMaxima = $db->fetch("
            SELECT aulas_obrigatorias
            FROM disciplinas_configuracao
            WHERE curso_tipo = ? AND disciplina = ? AND ativa = 1
        ", [$turma['curso_tipo'], $disciplina]);
        
        if (!$cargaMaxima) {
            return [
                'disponivel' => false,
                'mensagem' => "Disciplina '{$disciplina}' não encontrada na configuração do curso"
            ];
        }
        
        $cargaMaximaAulas = (int)$cargaMaxima['aulas_obrigatorias'];
        
        // Contar aulas já agendadas
        $aulasAgendadas = $db->fetch("
            SELECT COUNT(*) as total
            FROM turma_aulas_agendadas 
            WHERE turma_id = ? AND disciplina = ? AND status IN ('agendada', 'realizada')
        ", [$turmaId, $disciplina]);
        
        $totalAgendadas = (int)$aulasAgendadas['total'];
        $totalAposAgendamento = $totalAgendadas + $qtdAulasNovas;
        
        if ($totalAgendadas >= $cargaMaximaAulas) {
            return [
                'disponivel' => false,
                'mensagem' => "❌ DISCIPLINA COMPLETA: A disciplina já possui todas as {$cargaMaximaAulas} aulas obrigatórias agendadas."
            ];
        }
        
        if ($totalAposAgendamento > $cargaMaximaAulas) {
            $aulasRestantes = $cargaMaximaAulas - $totalAgendadas;
            return [
                'disponivel' => false,
                'mensagem' => "❌ CARGA HORÁRIA EXCEDIDA: Você ainda pode agendar apenas {$aulasRestantes} aula(s) restante(s)."
            ];
        }
        
        return ['disponivel' => true];
        
    } catch (Exception $e) {
        return [
            'disponivel' => false,
            'mensagem' => 'Erro ao verificar carga horária: ' . $e->getMessage()
        ];
    }
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
    error_log("🔧 [DEBUG] handleCancelarAula chamada com dados: " . print_r($dados, true));
    
    $aulaId = $dados['aula_id'] ?? null;
    $motivo = $dados['motivo'] ?? '';
    
    error_log("🔧 [DEBUG] aulaId: $aulaId, motivo: $motivo");
    
    if (!$aulaId) {
        error_log("❌ [DEBUG] ID da aula não fornecido");
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'ID da aula é obrigatório'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    try {
        $db = Database::getInstance();
        error_log("🔧 [DEBUG] Tentando atualizar aula ID: $aulaId");
        
        $result = $db->update('turma_aulas_agendadas', [
            'status' => 'cancelada',
            'observacoes' => $motivo
        ], 'id = ?', [$aulaId]);
        
        error_log("🔧 [DEBUG] Resultado da atualização: " . ($result ? 'sucesso' : 'falha'));
        error_log("🔧 [DEBUG] Tipo do resultado: " . gettype($result));
        error_log("🔧 [DEBUG] Valor do resultado: " . var_export($result, true));
        
        // Verificar se a atualização foi bem-sucedida
        if ($result && $result->rowCount() > 0) {
            error_log("🔧 [DEBUG] Atualização bem-sucedida, linhas afetadas: " . $result->rowCount());
            http_response_code(200);
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Aula cancelada com sucesso'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            error_log("❌ [DEBUG] Atualização falhou ou nenhuma linha foi afetada");
            http_response_code(400);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Aula não encontrada ou não foi possível cancelar'
            ], JSON_UNESCAPED_UNICODE);
        }
        
    } catch (Exception $e) {
        error_log("❌ [DEBUG] Erro ao cancelar aula: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Erro ao cancelar aula: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}

function handleEditarAula($turmaManager, $dados) {
    error_log("🔧 [DEBUG] handleEditarAula chamada com dados: " . print_r($dados, true));
    
    $aulaId = $dados['aula_id'] ?? null;
    
    if (!$aulaId) {
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'ID da aula é obrigatório'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $db = Database::getInstance();
    
    // Buscar a aula atual
    $aulaExistente = $db->fetch("SELECT * FROM turma_aulas_agendadas WHERE id = ?", [$aulaId]);
    error_log("🔧 [DEBUG] Aula existente: " . print_r($aulaExistente, true));
    
    if (!$aulaExistente) {
        http_response_code(404);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Aula não encontrada'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // Verificar se pode ser editada (apenas aulas agendadas)
    if ($aulaExistente['status'] !== 'agendada') {
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Apenas aulas agendadas podem ser editadas'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // Dados a atualizar
    $novaDataAula = $dados['data_aula'] ?? $aulaExistente['data_aula'];
    $novaHoraInicio = $dados['hora_inicio'] ?? $aulaExistente['hora_inicio'];
    $novaHoraFim = $dados['hora_fim'] ?? $aulaExistente['hora_fim'];

    // Se a hora fim não vier do formulário, calcular automaticamente (50 minutos após o início)
    if (empty($novaHoraFim) && !empty($novaHoraInicio)) {
        $tsInicio = strtotime($novaHoraInicio);
        if ($tsInicio !== false) {
            $novaHoraFim = date('H:i', $tsInicio + (50 * 60));
        }
    }
    $novoInstrutorId = $dados['instrutor_id'] ?? $aulaExistente['instrutor_id'];
    
    // Verificar conflitos de horário se houver mudança
    if ($novaDataAula != $aulaExistente['data_aula'] || 
        $novaHoraInicio != $aulaExistente['hora_inicio'] || 
        $novoInstrutorId != $aulaExistente['instrutor_id']) {
        
        // Verificar conflito de instrutor
        $conflitoInstrutor = $db->fetch("
            SELECT id FROM turma_aulas_agendadas 
            WHERE instrutor_id = ? 
            AND data_aula = ? 
            AND id != ?
            AND status != 'cancelada'
            AND (
                (hora_inicio <= ? AND hora_fim > ?) OR
                (hora_inicio < ? AND hora_fim >= ?) OR
                (hora_inicio >= ? AND hora_fim <= ?)
            )
        ", [$novoInstrutorId, $novaDataAula, $aulaId, $novaHoraInicio, $novaHoraInicio, $novaHoraFim, $novaHoraFim, $novaHoraInicio, $novaHoraFim]);
        
        if ($conflitoInstrutor) {
            http_response_code(400);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => '❌ CONFLITO DE HORÁRIO: O instrutor selecionado já possui aula agendada no horário informado.'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // Verificar conflito de sala
        $turma = $db->fetch("SELECT sala_id FROM turmas_teoricas WHERE id = ?", [$aulaExistente['turma_id']]);
        if ($turma && $turma['sala_id']) {
            $conflitoSala = $db->fetch("
                SELECT taa.id FROM turma_aulas_agendadas taa
                INNER JOIN turmas_teoricas tt ON tt.id = taa.turma_id
                WHERE tt.sala_id = ? 
                AND taa.data_aula = ? 
                AND taa.id != ?
                AND taa.status != 'cancelada'
                AND (
                    (taa.hora_inicio <= ? AND taa.hora_fim > ?) OR
                    (taa.hora_inicio < ? AND taa.hora_fim >= ?) OR
                    (taa.hora_inicio >= ? AND taa.hora_fim <= ?)
                )
            ", [$turma['sala_id'], $novaDataAula, $aulaId, $novaHoraInicio, $novaHoraInicio, $novaHoraFim, $novaHoraFim, $novaHoraInicio, $novaHoraFim]);
            
            if ($conflitoSala) {
                http_response_code(400);
                echo json_encode([
                    'sucesso' => false,
                    'mensagem' => '❌ CONFLITO DE HORÁRIO: A sala já está ocupada no horário informado.'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
        }
    }
    
    // Preparar dados para update
    $dadosUpdate = [
        'nome_aula' => $dados['nome_aula'] ?? $aulaExistente['nome_aula'],
        'data_aula' => $novaDataAula,
        'hora_inicio' => $novaHoraInicio,
        'hora_fim' => $novaHoraFim,
        'instrutor_id' => $novoInstrutorId,
        'observacoes' => $dados['observacoes'] ?? $aulaExistente['observacoes']
    ];
    
    error_log("🔧 [DEBUG] Dados para update: " . print_r($dadosUpdate, true));
    
    // Atualizar a aula
    $result = $db->update('turma_aulas_agendadas', $dadosUpdate, 'id = ?', [$aulaId]);
    
    error_log("🔧 [DEBUG] Resultado do update: " . ($result ? 'sucesso' : 'falha'));
    
    http_response_code(200);
    echo json_encode([
        'sucesso' => true,
        'mensagem' => '✅ Aula editada com sucesso!'
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
