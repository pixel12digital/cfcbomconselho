<?php
/**
 * API de Relatórios - Turmas Teóricas
 * Sistema de Turmas Teóricas - CFC Bom Conselho
 * ETAPA 1.5: Relatórios e Exportações
 * 
 * @author Sistema CFC Bom Conselho
 * @version 1.0
 * @since 2024
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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

// Verificar autenticação
if (!isLoggedIn() || !hasPermission('admin')) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Usuário não autenticado'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($db);
            break;
            
        case 'POST':
            handlePostRequest($db);
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Método não permitido'
            ], JSON_UNESCAPED_UNICODE);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Manipular requisições GET
 */
function handleGetRequest($db) {
    $tipo = $_GET['tipo'] ?? null;
    $turmaId = $_GET['turma_id'] ?? null;
    
    if (!$tipo) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Tipo de relatório é obrigatório'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    switch ($tipo) {
        case 'frequencia':
            gerarRelatorioFrequencia($db, $turmaId);
            break;
            
        case 'ata':
            gerarAtaTurma($db, $turmaId);
            break;
            
        case 'presencas':
            gerarRelatorioPresencas($db, $turmaId);
            break;
            
        case 'matriculas':
            gerarRelatorioMatriculas($db, $turmaId);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Tipo de relatório não reconhecido'
            ], JSON_UNESCAPED_UNICODE);
            break;
    }
}

/**
 * Manipular requisições POST
 */
function handlePostRequest($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'JSON inválido: ' . json_last_error_msg()
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $tipo = $input['tipo'] ?? null;
    $turmaId = $input['turma_id'] ?? null;
    $formato = $input['formato'] ?? 'json';
    
    if (!$tipo || !$turmaId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Tipo e ID da turma são obrigatórios'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    switch ($tipo) {
        case 'export_csv':
            exportarCSV($db, $turmaId, $input['dados'] ?? []);
            break;
            
        case 'export_pdf':
            exportarPDF($db, $turmaId, $input['dados'] ?? []);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Tipo de exportação não reconhecido'
            ], JSON_UNESCAPED_UNICODE);
            break;
    }
}

/**
 * Gerar relatório de frequência
 */
function gerarRelatorioFrequencia($db, $turmaId) {
    if (!$turmaId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID da turma é obrigatório'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    try {
        // Buscar dados da turma
        $turma = $db->fetch("
            SELECT 
                t.*,
                i.nome as instrutor_nome,
                c.nome as cfc_nome
            FROM turmas t
            LEFT JOIN instrutores i ON t.instrutor_id = i.id
            LEFT JOIN cfcs c ON t.cfc_id = c.id
            WHERE t.id = ?
        ", [$turmaId]);
        
        if (!$turma) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Turma não encontrada'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // Buscar aulas da turma
        $aulas = $db->fetchAll("
            SELECT 
                ta.*,
                td.conteudo_ministrado
            FROM turma_aulas ta
            LEFT JOIN turma_diario td ON ta.id = td.turma_aula_id
            WHERE ta.turma_id = ?
            ORDER BY ta.ordem ASC
        ", [$turmaId]);
        
        // Buscar alunos matriculados
        $alunos = $db->fetchAll("
            SELECT 
                a.*,
                ta.status as status_matricula,
                ta.data_matricula
            FROM alunos a
            JOIN turma_alunos ta ON a.id = ta.aluno_id
            WHERE ta.turma_id = ?
            ORDER BY a.nome ASC
        ", [$turmaId]);
        
        // Calcular frequência para cada aluno
        $frequencias = [];
        foreach ($alunos as $aluno) {
            $presencas = $db->fetch("
                SELECT 
                    COUNT(*) as total_aulas,
                    COUNT(CASE WHEN presente = 1 THEN 1 END) as presentes,
                    COUNT(CASE WHEN presente = 0 THEN 1 END) as ausentes
                FROM turma_presencas tp
                JOIN turma_aulas ta ON tp.turma_aula_id = ta.id
                WHERE tp.turma_id = ? AND tp.aluno_id = ?
            ", [$turmaId, $aluno['id']]);
            
            $percentual = 0;
            if ($presencas['total_aulas'] > 0) {
                $percentual = round(($presencas['presentes'] / $presencas['total_aulas']) * 100, 2);
            }
            
            $frequencias[] = [
                'aluno' => $aluno,
                'estatisticas' => [
                    'total_aulas' => $presencas['total_aulas'],
                    'presentes' => $presencas['presentes'],
                    'ausentes' => $presencas['ausentes'],
                    'percentual_frequencia' => $percentual,
                    'aprovado_frequencia' => $percentual >= $turma['frequencia_minima']
                ]
            ];
        }
        
        // Calcular estatísticas gerais da turma
        $totalAulas = count($aulas);
        $totalAlunos = count($alunos);
        $frequenciaMedia = 0;
        
        if ($totalAlunos > 0) {
            $somaFrequencias = array_sum(array_column($frequencias, 'estatisticas'));
            $frequenciaMedia = round($somaFrequencias / $totalAlunos, 2);
        }
        
        $aprovados = count(array_filter($frequencias, function($f) {
            return $f['estatisticas']['aprovado_frequencia'];
        }));
        
        echo json_encode([
            'success' => true,
            'data' => [
                'turma' => $turma,
                'aulas' => $aulas,
                'alunos' => $alunos,
                'frequencias' => $frequencias,
                'estatisticas_gerais' => [
                    'total_aulas' => $totalAulas,
                    'total_alunos' => $totalAlunos,
                    'frequencia_media' => $frequenciaMedia,
                    'aprovados' => $aprovados,
                    'reprovados' => $totalAlunos - $aprovados,
                    'frequencia_minima' => $turma['frequencia_minima']
                ]
            ]
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao gerar relatório de frequência: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Gerar ata da turma
 */
function gerarAtaTurma($db, $turmaId) {
    if (!$turmaId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID da turma é obrigatório'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    try {
        // Buscar dados da turma
        $turma = $db->fetch("
            SELECT 
                t.*,
                i.nome as instrutor_nome,
                c.nome as cfc_nome
            FROM turmas t
            LEFT JOIN instrutores i ON t.instrutor_id = i.id
            LEFT JOIN cfcs c ON t.cfc_id = c.id
            WHERE t.id = ?
        ", [$turmaId]);
        
        if (!$turma) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Turma não encontrada'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        // Buscar aulas com conteúdo
        $aulas = $db->fetchAll("
            SELECT 
                ta.*,
                td.conteudo_ministrado,
                td.observacoes,
                td.anexos
            FROM turma_aulas ta
            LEFT JOIN turma_diario td ON ta.id = td.turma_aula_id
            WHERE ta.turma_id = ?
            ORDER BY ta.ordem ASC
        ", [$turmaId]);
        
        // Buscar alunos com frequência
        $alunos = $db->fetchAll("
            SELECT 
                a.*,
                ta.status as status_matricula,
                ta.data_matricula
            FROM alunos a
            JOIN turma_alunos ta ON a.id = ta.aluno_id
            WHERE ta.turma_id = ?
            ORDER BY a.nome ASC
        ", [$turmaId]);
        
        // Calcular frequência para cada aluno
        $frequencias = [];
        foreach ($alunos as $aluno) {
            $presencas = $db->fetch("
                SELECT 
                    COUNT(*) as total_aulas,
                    COUNT(CASE WHEN presente = 1 THEN 1 END) as presentes,
                    COUNT(CASE WHEN presente = 0 THEN 1 END) as ausentes
                FROM turma_presencas tp
                JOIN turma_aulas ta ON tp.turma_aula_id = ta.id
                WHERE tp.turma_id = ? AND tp.aluno_id = ?
            ", [$turmaId, $aluno['id']]);
            
            $percentual = 0;
            if ($presencas['total_aulas'] > 0) {
                $percentual = round(($presencas['presentes'] / $presencas['total_aulas']) * 100, 2);
            }
            
            $frequencias[] = [
                'aluno' => $aluno,
                'frequencia' => [
                    'total_aulas' => $presencas['total_aulas'],
                    'presentes' => $presencas['presentes'],
                    'ausentes' => $presencas['ausentes'],
                    'percentual' => $percentual,
                    'aprovado' => $percentual >= $turma['frequencia_minima']
                ]
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'turma' => $turma,
                'aulas' => $aulas,
                'alunos' => $frequencias,
                'estatisticas' => [
                    'total_aulas' => count($aulas),
                    'total_alunos' => count($alunos),
                    'frequencia_minima' => $turma['frequencia_minima'],
                    'data_geracao' => date('d/m/Y H:i:s')
                ]
            ]
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao gerar ata da turma: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Gerar relatório de presenças
 */
function gerarRelatorioPresencas($db, $turmaId) {
    if (!$turmaId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID da turma é obrigatório'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    try {
        $presencas = $db->fetchAll("
            SELECT 
                tp.*,
                a.nome as aluno_nome,
                a.cpf as aluno_cpf,
                ta.nome_aula,
                ta.data_aula,
                ta.ordem,
                u.nome as registrado_por_nome
            FROM turma_presencas tp
            JOIN alunos a ON tp.aluno_id = a.id
            JOIN turma_aulas ta ON tp.turma_aula_id = ta.id
            LEFT JOIN usuarios u ON tp.registrado_por = u.id
            WHERE tp.turma_id = ?
            ORDER BY ta.ordem ASC, a.nome ASC
        ", [$turmaId]);
        
        echo json_encode([
            'success' => true,
            'data' => $presencas
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao gerar relatório de presenças: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Gerar relatório de matrículas
 */
function gerarRelatorioMatriculas($db, $turmaId) {
    if (!$turmaId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID da turma é obrigatório'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    try {
        $matriculas = $db->fetchAll("
            SELECT 
                ta.*,
                a.nome as aluno_nome,
                a.cpf as aluno_cpf,
                a.categoria_cnh,
                t.nome as turma_nome
            FROM turma_alunos ta
            JOIN alunos a ON ta.aluno_id = a.id
            JOIN turmas t ON ta.turma_id = t.id
            WHERE ta.turma_id = ?
            ORDER BY a.nome ASC
        ", [$turmaId]);
        
        echo json_encode([
            'success' => true,
            'data' => $matriculas
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao gerar relatório de matrículas: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Exportar dados em CSV
 */
function exportarCSV($db, $turmaId, $dados) {
    $tipo = $dados['tipo'] ?? 'frequencia';
    
    try {
        switch ($tipo) {
            case 'frequencia':
                $csv = gerarCSVFrequencia($db, $turmaId);
                break;
            case 'presencas':
                $csv = gerarCSVPresencas($db, $turmaId);
                break;
            case 'matriculas':
                $csv = gerarCSVMatriculas($db, $turmaId);
                break;
            default:
                throw new Exception('Tipo de CSV não reconhecido');
        }
        
        // Definir headers para download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="relatorio_' . $tipo . '_turma_' . $turmaId . '.csv"');
        
        echo $csv;
        exit();
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao exportar CSV: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Gerar CSV de frequência
 */
function gerarCSVFrequencia($db, $turmaId) {
    $turma = $db->fetch("SELECT * FROM turmas WHERE id = ?", [$turmaId]);
    $alunos = $db->fetchAll("
        SELECT 
            a.*,
            ta.status as status_matricula
        FROM alunos a
        JOIN turma_alunos ta ON a.id = ta.aluno_id
        WHERE ta.turma_id = ?
        ORDER BY a.nome ASC
    ", [$turmaId]);
    
    $csv = "Relatório de Frequência - " . $turma['nome'] . "\n";
    $csv .= "Gerado em: " . date('d/m/Y H:i:s') . "\n\n";
    $csv .= "Nome,CPF,Categoria,Status,Total Aulas,Presentes,Ausentes,Frequência,Aprovado\n";
    
    foreach ($alunos as $aluno) {
        $presencas = $db->fetch("
            SELECT 
                COUNT(*) as total_aulas,
                COUNT(CASE WHEN presente = 1 THEN 1 END) as presentes,
                COUNT(CASE WHEN presente = 0 THEN 1 END) as ausentes
            FROM turma_presencas tp
            JOIN turma_aulas ta ON tp.turma_aula_id = ta.id
            WHERE tp.turma_id = ? AND tp.aluno_id = ?
        ", [$turmaId, $aluno['id']]);
        
        $percentual = 0;
        if ($presencas['total_aulas'] > 0) {
            $percentual = round(($presencas['presentes'] / $presencas['total_aulas']) * 100, 2);
        }
        
        $aprovado = $percentual >= $turma['frequencia_minima'] ? 'Sim' : 'Não';
        
        $csv .= sprintf("%s,%s,%s,%s,%d,%d,%d,%.2f%%,%s\n",
            $aluno['nome'],
            $aluno['cpf'],
            $aluno['categoria_cnh'],
            $aluno['status_matricula'],
            $presencas['total_aulas'],
            $presencas['presentes'],
            $presencas['ausentes'],
            $percentual,
            $aprovado
        );
    }
    
    return $csv;
}

/**
 * Gerar CSV de presenças
 */
function gerarCSVPresencas($db, $turmaId) {
    $presencas = $db->fetchAll("
        SELECT 
            tp.*,
            a.nome as aluno_nome,
            a.cpf as aluno_cpf,
            ta.nome_aula,
            ta.data_aula,
            ta.ordem
        FROM turma_presencas tp
        JOIN alunos a ON tp.aluno_id = a.id
        JOIN turma_aulas ta ON tp.turma_aula_id = ta.id
        WHERE tp.turma_id = ?
        ORDER BY ta.ordem ASC, a.nome ASC
    ", [$turmaId]);
    
    $csv = "Relatório de Presenças\n";
    $csv .= "Gerado em: " . date('d/m/Y H:i:s') . "\n\n";
    $csv .= "Aula,Data,Nome Aluno,CPF,Presente,Observação,Registrado Em\n";
    
    foreach ($presencas as $presenca) {
        $csv .= sprintf("%s,%s,%s,%s,%s,%s,%s\n",
            $presenca['nome_aula'],
            date('d/m/Y', strtotime($presenca['data_aula'])),
            $presenca['aluno_nome'],
            $presenca['aluno_cpf'],
            $presenca['presente'] ? 'Sim' : 'Não',
            $presenca['observacao'] ?? '',
            date('d/m/Y H:i', strtotime($presenca['registrado_em']))
        );
    }
    
    return $csv;
}

/**
 * Gerar CSV de matrículas
 */
function gerarCSVMatriculas($db, $turmaId) {
    $matriculas = $db->fetchAll("
        SELECT 
            ta.*,
            a.nome as aluno_nome,
            a.cpf as aluno_cpf,
            a.categoria_cnh,
            t.nome as turma_nome
        FROM turma_alunos ta
        JOIN alunos a ON ta.aluno_id = a.id
        JOIN turmas t ON ta.turma_id = t.id
        WHERE ta.turma_id = ?
        ORDER BY a.nome ASC
    ", [$turmaId]);
    
    $csv = "Relatório de Matrículas\n";
    $csv .= "Gerado em: " . date('d/m/Y H:i:s') . "\n\n";
    $csv .= "Nome,CPF,Categoria,Status,Data Matrícula,Data Conclusão\n";
    
    foreach ($matriculas as $matricula) {
        $csv .= sprintf("%s,%s,%s,%s,%s,%s\n",
            $matricula['aluno_nome'],
            $matricula['aluno_cpf'],
            $matricula['categoria_cnh'],
            $matricula['status'],
            date('d/m/Y', strtotime($matricula['data_matricula'])),
            $matricula['data_conclusao'] ? date('d/m/Y', strtotime($matricula['data_conclusao'])) : ''
        );
    }
    
    return $csv;
}

/**
 * Exportar dados em PDF
 */
function exportarPDF($db, $turmaId, $dados) {
    // Por enquanto, retornar JSON com dados para PDF
    // Em produção, seria integrado com biblioteca PDF (ex: TCPDF, mPDF)
    
    try {
        $ata = gerarAtaTurma($db, $turmaId);
        $ataData = json_decode($ata, true);
        
        if (!$ataData['success']) {
            throw new Exception('Erro ao gerar dados da ata');
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Dados da ata preparados para PDF',
            'data' => $ataData['data']
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao exportar PDF: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}
?>
