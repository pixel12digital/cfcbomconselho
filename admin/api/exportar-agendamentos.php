<?php
/**
 * API para Exportar Agendamentos de uma Disciplina
 * Gera arquivo CSV/Excel com todos os agendamentos
 */

// Configurações
require_once '../../config/database.php';
require_once '../../config/auth.php';

// Headers para download de arquivo
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="agendamentos_' . date('Y-m-d_His') . '.csv"');

// Verificar autenticação
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo "Erro: Usuário não autenticado";
    exit;
}

// Verificar permissões
$permissoesExportar = ['admin', 'instrutor'];
if (!in_array($_SESSION['usuario_tipo'], $permissoesExportar)) {
    http_response_code(403);
    echo "Erro: Sem permissão para exportar";
    exit;
}

// Obter parâmetros
$turmaId = $_GET['turma_id'] ?? null;
$disciplinaId = $_GET['disciplina_id'] ?? null;

if (!$turmaId || !$disciplinaId) {
    http_response_code(400);
    echo "Erro: Parâmetros turma_id e disciplina_id são obrigatórios";
    exit;
}

try {
    // Buscar informações da turma
    $stmtTurma = $pdo->prepare("
        SELECT nome, data_inicio, data_fim 
        FROM turmas_teoricas 
        WHERE id = ?
    ");
    $stmtTurma->execute([$turmaId]);
    $turma = $stmtTurma->fetch(PDO::FETCH_ASSOC);
    
    if (!$turma) {
        throw new Exception("Turma não encontrada");
    }
    
    // Buscar nome da disciplina
    $stmtDisciplina = $pdo->prepare("
        SELECT nome 
        FROM disciplinas 
        WHERE codigo = ?
    ");
    $stmtDisciplina->execute([$disciplinaId]);
    $disciplina = $stmtDisciplina->fetch(PDO::FETCH_ASSOC);
    
    $nomeDisciplina = $disciplina ? $disciplina['nome'] : ucwords(str_replace('_', ' ', $disciplinaId));
    
    // Buscar todos os agendamentos da disciplina
    $stmtAgendamentos = $pdo->prepare("
        SELECT 
            aa.id,
            aa.nome_aula,
            aa.data_aula,
            aa.hora_inicio,
            aa.hora_fim,
            aa.duracao_minutos,
            aa.status,
            aa.observacoes,
            i.nome as instrutor_nome,
            s.nome as sala_nome
        FROM aulas_agendadas aa
        LEFT JOIN instrutores i ON aa.instrutor_id = i.id
        LEFT JOIN salas s ON aa.sala_id = s.id
        WHERE aa.turma_id = ? 
        AND aa.disciplina = ?
        ORDER BY aa.data_aula ASC, aa.hora_inicio ASC
    ");
    $stmtAgendamentos->execute([$turmaId, $disciplinaId]);
    $agendamentos = $stmtAgendamentos->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar estatísticas
    $stats = [
        'total' => count($agendamentos),
        'agendadas' => 0,
        'realizadas' => 0,
        'canceladas' => 0,
        'reagendadas' => 0
    ];
    
    foreach ($agendamentos as $ag) {
        $status = strtolower($ag['status']);
        if (isset($stats[$status . 's'])) {
            $stats[$status . 's']++;
        } elseif ($status === 'agendada') {
            $stats['agendadas']++;
        } elseif ($status === 'realizada') {
            $stats['realizadas']++;
        } elseif ($status === 'cancelada') {
            $stats['canceladas']++;
        } elseif ($status === 'reagendada') {
            $stats['reagendadas']++;
        }
    }
    
    // Criar arquivo CSV com BOM para UTF-8
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
    
    // Cabeçalho do relatório
    fputcsv($output, ['RELATÓRIO DE AGENDAMENTOS'], ';');
    fputcsv($output, [''], ';');
    fputcsv($output, ['Turma:', $turma['nome']], ';');
    fputcsv($output, ['Disciplina:', $nomeDisciplina], ';');
    fputcsv($output, ['Período:', date('d/m/Y', strtotime($turma['data_inicio'])) . ' a ' . date('d/m/Y', strtotime($turma['data_fim']))], ';');
    fputcsv($output, ['Gerado em:', date('d/m/Y H:i:s')], ';');
    fputcsv($output, [''], ';');
    
    // Estatísticas
    fputcsv($output, ['ESTATÍSTICAS'], ';');
    fputcsv($output, ['Total de Aulas:', $stats['total']], ';');
    fputcsv($output, ['Agendadas:', $stats['agendadas']], ';');
    fputcsv($output, ['Realizadas:', $stats['realizadas']], ';');
    fputcsv($output, ['Canceladas:', $stats['canceladas']], ';');
    fputcsv($output, ['Reagendadas:', $stats['reagendadas']], ';');
    fputcsv($output, [''], ';');
    fputcsv($output, [''], ';');
    
    // Cabeçalho da tabela de dados
    fputcsv($output, [
        'ID',
        'Aula',
        'Data',
        'Dia da Semana',
        'Horário Início',
        'Horário Fim',
        'Duração (min)',
        'Instrutor',
        'Sala',
        'Status',
        'Observações'
    ], ';');
    
    // Dados
    $diasSemana = [
        'Sunday' => 'Domingo',
        'Monday' => 'Segunda-feira',
        'Tuesday' => 'Terça-feira',
        'Wednesday' => 'Quarta-feira',
        'Thursday' => 'Quinta-feira',
        'Friday' => 'Sexta-feira',
        'Saturday' => 'Sábado'
    ];
    
    foreach ($agendamentos as $ag) {
        $dataObj = new DateTime($ag['data_aula']);
        $diaSemanaEn = $dataObj->format('l');
        $diaSemana = $diasSemana[$diaSemanaEn] ?? $diaSemanaEn;
        
        fputcsv($output, [
            $ag['id'],
            $ag['nome_aula'],
            date('d/m/Y', strtotime($ag['data_aula'])),
            $diaSemana,
            date('H:i', strtotime($ag['hora_inicio'])),
            date('H:i', strtotime($ag['hora_fim'])),
            $ag['duracao_minutos'],
            $ag['instrutor_nome'] ?? 'Não definido',
            $ag['sala_nome'] ?? 'Não definida',
            mb_strtoupper($ag['status']),
            $ag['observacoes'] ?? ''
        ], ';');
    }
    
    fclose($output);
    
} catch (Exception $e) {
    http_response_code(500);
    echo "Erro ao gerar exportação: " . $e->getMessage();
    error_log("Erro ao exportar agendamentos: " . $e->getMessage());
}

