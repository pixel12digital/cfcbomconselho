<?php
// Usar caminho relativo que sabemos que funciona
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

// Verificar se usuário está logado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

// Verificar método da requisição
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

// Verificar parâmetros
$tipo = $_GET['tipo'] ?? '';
$id = $_GET['id'] ?? '';

if (empty($tipo) || empty($id) || !is_numeric($id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Parâmetros inválidos']);
    exit;
}

$id = (int)$id;

try {
    switch ($tipo) {
        case 'aluno':
            $historico = getHistoricoAluno($id);
            break;
        case 'instrutor':
            $historico = getHistoricoInstrutor($id);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Tipo inválido']);
            exit;
    }
    
    // Retornar dados
    header('Content-Type: application/json');
    echo json_encode($historico);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor: ' . $e->getMessage()]);
}

/**
 * Buscar histórico completo do aluno
 */
function getHistoricoAluno($alunoId) {
    // Verificar se aluno existe
    $aluno = db()->fetch("SELECT * FROM alunos WHERE id = ?", [$alunoId]);
    if (!$aluno) {
        throw new Exception('Aluno não encontrado');
    }
    
    // Buscar dados do CFC
    $cfc = db()->fetch("SELECT nome FROM cfcs WHERE id = ?", [$aluno['cfc_id']]);
    
    // Buscar histórico de aulas
    $aulas = db()->fetchAll("
        SELECT a.*, i.credencial, u.nome as instrutor_nome, v.placa, v.modelo, v.marca
        FROM aulas a
        LEFT JOIN instrutores i ON a.instrutor_id = i.id
        LEFT JOIN usuarios u ON i.usuario_id = u.id
        LEFT JOIN veiculos v ON a.veiculo_id = v.id
        WHERE a.aluno_id = ?
        ORDER BY a.data_aula DESC, a.hora_inicio DESC
    ", [$alunoId]);
    
    // Calcular estatísticas
    $totalAulas = count($aulas);
    $aulasConcluidas = count(array_filter($aulas, fn($a) => $a['status'] === 'concluida'));
    $aulasCanceladas = count(array_filter($aulas, fn($a) => $a['status'] === 'cancelada'));
    $aulasAgendadas = count(array_filter($aulas, fn($a) => $a['status'] === 'agendada'));
    
    // Calcular progresso baseado na categoria
    $progressoPorCategoria = [
        'A' => 20, 'B' => 25, 'C' => 30, 'D' => 35, 'E' => 40,
        'AB' => 25, 'AC' => 30, 'AD' => 35, 'AE' => 40
    ];
    
    $aulasNecessarias = $progressoPorCategoria[$aluno['categoria_cnh']] ?? 25;
    $progressoPercentual = min(100, ($aulasConcluidas / $aulasNecessarias) * 100);
    
    // Buscar próximas aulas
    $proximasAulas = db()->fetchAll("
        SELECT a.*, i.credencial, u.nome as instrutor_nome, v.placa
        FROM aulas a
        LEFT JOIN instrutores i ON a.instrutor_id = i.id
        LEFT JOIN usuarios u ON i.usuario_id = u.id
        LEFT JOIN veiculos v ON a.veiculo_id = v.id
        WHERE a.aluno_id = ? AND a.data_aula >= CURDATE() AND a.status = 'agendada'
        ORDER BY a.data_aula ASC, a.hora_inicio ASC
        LIMIT 5
    ", [$alunoId]);
    
    // Estatísticas por mês (últimos 6 meses)
    $estatisticasMensais = [];
    for ($i = 5; $i >= 0; $i--) {
        $mes = date('Y-m', strtotime("-$i months"));
        $aulasMes = db()->fetchColumn("
            SELECT COUNT(*) FROM aulas 
            WHERE aluno_id = ? AND DATE_FORMAT(data_aula, '%Y-%m') = ? AND status = 'concluida'
        ", [$alunoId, $mes]);
        
        $estatisticasMensais[] = [
            'mes' => date('M/Y', strtotime("-$i months")),
            'aulas' => $aulasMes
        ];
    }
    
    return [
        'aluno' => [
            'id' => $aluno['id'],
            'nome' => $aluno['nome'],
            'cpf' => $aluno['cpf'],
            'categoria_cnh' => $aluno['categoria_cnh'],
            'status' => $aluno['status'],
            'cfc_nome' => $cfc['nome'] ?? 'N/A'
        ],
        'estatisticas' => [
            'total_aulas' => $totalAulas,
            'aulas_concluidas' => $aulasConcluidas,
            'aulas_canceladas' => $aulasCanceladas,
            'aulas_agendadas' => $aulasAgendadas,
            'progresso_percentual' => $progressoPercentual,
            'aulas_necessarias' => $aulasNecessarias
        ],
        'proximas_aulas' => $proximasAulas,
        'historico_completo' => $aulas,
        'estatisticas_mensais' => $estatisticasMensais
    ];
}

/**
 * Buscar histórico completo do instrutor
 */
function getHistoricoInstrutor($instrutorId) {
    // Verificar se instrutor existe
    $instrutor = db()->fetch("
        SELECT i.*, u.nome, u.email, u.telefone, c.nome as cfc_nome
        FROM instrutores i 
        LEFT JOIN usuarios u ON i.usuario_id = u.id 
        LEFT JOIN cfcs c ON i.cfc_id = c.id 
        WHERE i.id = ?
    ", [$instrutorId]);
    
    if (!$instrutor) {
        throw new Exception('Instrutor não encontrado');
    }
    
    // Buscar histórico de aulas
    $aulas = db()->fetchAll("
        SELECT a.*, al.nome as aluno_nome, al.cpf as aluno_cpf, v.placa, v.modelo, v.marca
        FROM aulas a
        LEFT JOIN alunos al ON a.aluno_id = al.id
        LEFT JOIN veiculos v ON a.veiculo_id = v.id
        WHERE a.instrutor_id = ?
        ORDER BY a.data_aula DESC, a.hora_inicio DESC
    ", [$instrutorId]);
    
    // Calcular estatísticas
    $totalAulas = count($aulas);
    $aulasConcluidas = count(array_filter($aulas, fn($a) => $a['status'] === 'concluida'));
    $aulasCanceladas = count(array_filter($aulas, fn($a) => $a['status'] === 'cancelada'));
    $aulasAgendadas = count(array_filter($aulas, fn($a) => $a['status'] === 'agendada'));
    $aulasTeoricas = count(array_filter($aulas, fn($a) => $a['tipo_aula'] === 'teorica'));
    $aulasPraticas = count(array_filter($aulas, fn($a) => $a['tipo_aula'] === 'pratica'));
    
    // Calcular taxa de conclusão
    $taxaConclusao = $totalAulas > 0 ? ($aulasConcluidas / $totalAulas) * 100 : 0;
    
    // Estatísticas por mês (últimos 6 meses)
    $estatisticasMensais = [];
    for ($i = 5; $i >= 0; $i--) {
        $mes = date('Y-m', strtotime("-$i months"));
        $aulasMes = db()->fetchColumn("
            SELECT COUNT(*) FROM aulas 
            WHERE instrutor_id = ? AND DATE_FORMAT(data_aula, '%Y-%m') = ? AND status = 'concluida'
        ", [$instrutorId, $mes]);
        
        $estatisticasMensais[] = [
            'mes' => date('M/Y', strtotime("-$i months")),
            'aulas' => $aulasMes
        ];
    }
    
    // Buscar alunos únicos atendidos
    $alunosUnicos = db()->fetchAll("
        SELECT DISTINCT al.id, al.nome, al.cpf, al.categoria_cnh
        FROM aulas a
        LEFT JOIN alunos al ON a.aluno_id = al.id
        WHERE a.instrutor_id = ?
        ORDER BY al.nome
    ", [$instrutorId]);
    
    // Buscar próximas aulas
    $proximasAulas = db()->fetchAll("
        SELECT a.*, al.nome as aluno_nome, al.cpf as aluno_cpf, v.placa
        FROM aulas a
        LEFT JOIN alunos al ON a.aluno_id = al.id
        LEFT JOIN veiculos v ON a.veiculo_id = v.id
        WHERE a.instrutor_id = ? AND a.data_aula >= CURDATE() AND a.status = 'agendada'
        ORDER BY a.data_aula ASC, a.hora_inicio ASC
        LIMIT 10
    ", [$instrutorId]);
    
    // Buscar veículos utilizados
    $veiculosUtilizados = db()->fetchAll("
        SELECT DISTINCT v.id, v.placa, v.modelo, v.marca, COUNT(a.id) as total_aulas
        FROM aulas a
        LEFT JOIN veiculos v ON a.veiculo_id = v.id
        WHERE a.instrutor_id = ? AND v.id IS NOT NULL
        GROUP BY v.id
        ORDER BY total_aulas DESC
    ", [$instrutorId]);
    
    return [
        'instrutor' => [
            'id' => $instrutor['id'],
            'nome' => $instrutor['nome'],
            'credencial' => $instrutor['credencial'],
            'categoria_habilitacao' => $instrutor['categoria_habilitacao'],
            'cfc_nome' => $instrutor['cfc_nome'] ?? 'N/A'
        ],
        'estatisticas' => [
            'total_aulas' => $totalAulas,
            'aulas_concluidas' => $aulasConcluidas,
            'aulas_canceladas' => $aulasCanceladas,
            'aulas_agendadas' => $aulasAgendadas,
            'aulas_teoricas' => $aulasTeoricas,
            'aulas_praticas' => $aulasPraticas,
            'taxa_conclusao' => $taxaConclusao
        ],
        'proximas_aulas' => $proximasAulas,
        'alunos_unicos' => $alunosUnicos,
        'veiculos_utilizados' => $veiculosUtilizados,
        'historico_completo' => $aulas,
        'estatisticas_mensais' => $estatisticasMensais
    ];
}
?>
