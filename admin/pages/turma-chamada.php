<?php
/**
 * Interface de Chamada - Turmas Teóricas
 * Sistema de Turmas Teóricas - CFC Bom Conselho
 * ETAPA 1.3: Interface de Chamada
 * 
 * @author Sistema CFC Bom Conselho
 * @version 1.0
 * @since 2024
 */

// Incluir dependências
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';

// AJUSTE DASHBOARD INSTRUTOR - Verificar autenticação
// Se estiver sendo incluído pelo router do admin, não fazer redirect aqui
// (o admin/index.php já verificou a autenticação)
if (!defined('ADMIN_ROUTING')) {
    // Se não está sendo incluído pelo router, verificar autenticação diretamente
    if (!isLoggedIn()) {
        $basePath = defined('BASE_PATH') ? BASE_PATH : '';
        header('Location: ' . $basePath . '/login.php');
        exit();
    }
}

$db = Database::getInstance();
$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'] ?? 'aluno';
$origem = $_GET['origem'] ?? '';
// CORREÇÃO 2025-01: Inicializar variável de mensagem de alerta
$mensagemAlertaInstrutor = 'Você não é o instrutor desta aula. Apenas visualização.';
// Base da aplicação (raiz do projeto, sem /admin)
$baseApp = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
// URL padrão de volta para o dashboard do instrutor
$backUrlInstrutor = $baseApp . '/instrutor/dashboard.php';

// Verificar permissões
$canEdit = ($userType === 'admin' || $userType === 'instrutor');

// Parâmetros da URL
$turmaId = $_GET['turma_id'] ?? null;
$aulaId = $_GET['aula_id'] ?? null;

// Redirecionamentos iniciais
if (!$turmaId) {
    if ($origem === 'instrutor') {
        header('Location: ' . $backUrlInstrutor);
    } else {
        header('Location: index.php?page=turmas-teoricas');
    }
    exit();
}

// Buscar dados da turma (CORRIGIDO: usar turmas_teoricas em vez de turmas)
// CORREÇÃO: turmas_teoricas não tem instrutor_id - buscar instrutor da aula se aula_id fornecido
$turma = $db->fetch("
    SELECT 
        tt.*,
        c.nome as cfc_nome
    FROM turmas_teoricas tt
    LEFT JOIN cfcs c ON tt.cfc_id = c.id
    WHERE tt.id = ?
", [$turmaId]);

if (!$turma) {
    if ($origem === 'instrutor') {
        header('Location: ' . $backUrlInstrutor);
    } else {
        header('Location: index.php?page=turmas-teoricas');
    }
    exit();
}

// Buscar instrutor da aula se aula_id fornecido, ou primeiro instrutor da turma
$instrutorNome = null;
if ($aulaId) {
    $aulaInstrutor = $db->fetch("
        SELECT i.nome as instrutor_nome
        FROM turma_aulas_agendadas taa
        LEFT JOIN instrutores i ON taa.instrutor_id = i.id
        WHERE taa.id = ? AND taa.turma_id = ?
    ", [$aulaId, $turmaId]);
    $instrutorNome = $aulaInstrutor['instrutor_nome'] ?? null;
} else {
    // Se não há aula_id, buscar primeiro instrutor da turma
    $primeiroInstrutor = $db->fetch("
        SELECT i.nome as instrutor_nome
        FROM turma_aulas_agendadas taa
        LEFT JOIN instrutores i ON taa.instrutor_id = i.id
        WHERE taa.turma_id = ?
        LIMIT 1
    ", [$turmaId]);
    $instrutorNome = $primeiroInstrutor['instrutor_nome'] ?? null;
}
$turma['instrutor_nome'] = $instrutorNome;

// AJUSTE IDENTIDADE INSTRUTOR - Lógica de permissão refinada
// Variáveis de controle claras
$modoSomenteLeitura = false;
$mostrarAlertaInstrutor = false;

// Quando origem=instrutor, usar identidade do instrutor para verificar permissão
if ($origem === 'instrutor' || $userType === 'instrutor') {
    // Obter instrutor_id real do usuário logado
    $instrutorAtualId = getCurrentInstrutorId($userId);
    
    // CORREÇÃO 2025-01: Variável para mensagem de alerta mais específica
    $mensagemAlertaInstrutor = 'Você não é o instrutor desta aula. Apenas visualização.';
    
    // CORREÇÃO 2025-01: Log de debug para diagnóstico
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("[TURMA-CHAMADA] Fluxo instrutor - user_id={$userId}, user_type={$userType}, origem={$origem}, instrutor_atual_id=" . ($instrutorAtualId ?? 'null') . ", aula_id={$aulaId}, turma_id={$turmaId}");
    }
    
    if ($aulaId) {
        // Verificar se é instrutor da aula específica
        $aulaInstrutor = $db->fetch(
            "SELECT instrutor_id FROM turma_aulas_agendadas WHERE id = ? AND turma_id = ?",
            [$aulaId, $turmaId]
        );
        $instrutorDaAulaId = $aulaInstrutor['instrutor_id'] ?? null;
        
        // CORREÇÃO 2025-01: Log de debug para diagnóstico
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("[TURMA-CHAMADA] Aula {$aulaId} - instrutor_da_aula_id=" . ($instrutorDaAulaId ?? 'null') . ", instrutor_atual_id=" . ($instrutorAtualId ?? 'null') . ", match=" . ($instrutorAtualId && $instrutorDaAulaId && $instrutorAtualId == $instrutorDaAulaId ? 'SIM' : 'NÃO'));
        }
        
        if ($instrutorAtualId && $instrutorDaAulaId && $instrutorAtualId == $instrutorDaAulaId) {
            // Instrutor logado É o instrutor da aula
            $modoSomenteLeitura = false;
            $mostrarAlertaInstrutor = false;
        } else {
            // Instrutor logado NÃO é o instrutor da aula
            $modoSomenteLeitura = true;
            $mostrarAlertaInstrutor = true;
            
            // CORREÇÃO 2025-01: Mensagem mais específica baseada no problema
            if (!$instrutorAtualId) {
                $mensagemAlertaInstrutor = 'Não foi possível identificar seu vínculo como instrutor. Entre em contato com o administrador.';
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    error_log("[TURMA-CHAMADA WARN] getCurrentInstrutorId() retornou null para user_id={$userId}. Verificar vínculo em instrutores.usuario_id");
                }
            } elseif (!$instrutorDaAulaId) {
                $mensagemAlertaInstrutor = 'Esta aula não possui instrutor atribuído. Entre em contato com o administrador.';
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    error_log("[TURMA-CHAMADA WARN] Aula {$aulaId} não tem instrutor_id definido");
                }
            } else {
                $mensagemAlertaInstrutor = 'Você não é o instrutor desta aula. Apenas visualização.';
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    error_log("[TURMA-CHAMADA WARN] Instrutor atual ({$instrutorAtualId}) não corresponde ao instrutor da aula ({$instrutorDaAulaId})");
                }
            }
        }
    } else {
        // Sem aula_id, verificar se tem alguma aula nesta turma
        if ($instrutorAtualId) {
            $temAula = $db->fetch(
                "SELECT COUNT(*) as total FROM turma_aulas_agendadas WHERE turma_id = ? AND instrutor_id = ?",
                [$turmaId, $instrutorAtualId]
            );
            if (!$temAula || $temAula['total'] == 0) {
                $modoSomenteLeitura = true;
                $mostrarAlertaInstrutor = true;
                $mensagemAlertaInstrutor = 'Você não possui aulas atribuídas nesta turma. Apenas visualização.';
            } else {
                $modoSomenteLeitura = false;
                $mostrarAlertaInstrutor = false;
            }
        } else {
            // Não encontrou instrutor_id, modo somente leitura
            $modoSomenteLeitura = true;
            $mostrarAlertaInstrutor = true;
            $mensagemAlertaInstrutor = 'Não foi possível identificar seu vínculo como instrutor. Entre em contato com o administrador.';
        }
    }
    
    // Verificar regras adicionais: turma concluída/cancelada
    if ($turma['status'] === 'cancelada') {
        // Ninguém pode editar turmas canceladas
        $modoSomenteLeitura = true;
        $mostrarAlertaInstrutor = false; // Não mostrar alerta de instrutor, mostrar alerta de turma cancelada
    } elseif ($turma['status'] === 'concluida') {
        // Instrutor não pode editar turmas concluídas (apenas admin/secretaria)
        $modoSomenteLeitura = true;
        $mostrarAlertaInstrutor = false; // Não mostrar alerta de instrutor, mostrar alerta de turma concluída
    }
    
    // Aplicar modo somente leitura ao canEdit
    if ($modoSomenteLeitura) {
        $canEdit = false;
    }
} else {
    // Fluxo admin normal - sempre pode editar (exceto turmas canceladas)
    $modoSomenteLeitura = false;
    $mostrarAlertaInstrutor = false;
    
    // Verificar regras adicionais: turma cancelada
    if ($turma['status'] === 'cancelada') {
        $modoSomenteLeitura = true;
        $canEdit = false;
    }
}

// Buscar aulas da turma (CORRIGIDO: usar turma_aulas_agendadas e aula_id)
// AJUSTE DASHBOARD INSTRUTOR - Corrigir JOIN com turma_presencas
// Verificar se a tabela turma_presencas existe antes de fazer o JOIN
try {
    $tabelaExiste = $db->fetch("SHOW TABLES LIKE 'turma_presencas'");
    if ($tabelaExiste) {
        // Tabela existe, fazer JOIN normal
        // AJUSTE 2025-12 - Presenças teóricas alinhadas com turma_presencas (turma_aula_id)
        $aulas = $db->fetchAll("
            SELECT 
                taa.*,
                COUNT(DISTINCT tp.id) as presencas_registradas
            FROM turma_aulas_agendadas taa
            LEFT JOIN turma_presencas tp ON taa.id = tp.turma_aula_id AND tp.turma_id = taa.turma_id
            WHERE taa.turma_id = ?
            GROUP BY taa.id
            ORDER BY taa.ordem_global ASC
        ", [$turmaId]);
    } else {
        // Tabela não existe, buscar apenas aulas sem contar presenças
        $aulas = $db->fetchAll("
            SELECT 
                taa.*,
                0 as presencas_registradas
            FROM turma_aulas_agendadas taa
            WHERE taa.turma_id = ?
            ORDER BY taa.ordem_global ASC
        ", [$turmaId]);
    }
} catch (Exception $e) {
    // Em caso de erro, buscar apenas aulas sem contar presenças
    $aulas = $db->fetchAll("
        SELECT 
            taa.*,
            0 as presencas_registradas
        FROM turma_aulas_agendadas taa
        WHERE taa.turma_id = ?
        ORDER BY taa.ordem_global ASC
    ", [$turmaId]);
}

// Se não especificou aula, usar a primeira
if (!$aulaId && !empty($aulas)) {
    $aulaId = $aulas[0]['id'];
}

// Buscar dados da aula atual (CORRIGIDO: usar turma_aulas_agendadas)
$aulaAtual = null;
if ($aulaId) {
    $aulaAtual = $db->fetch("
        SELECT * FROM turma_aulas_agendadas WHERE id = ? AND turma_id = ?
    ", [$aulaId, $turmaId]);
}

// AJUSTE DASHBOARD INSTRUTOR - Buscar alunos matriculados na turma
// AJUSTE 2025-12 - Presenças teóricas alinhadas com turma_presencas (turma_aula_id)
// Verificar se a tabela turma_presencas existe e quais colunas tem
$tabelaPresencasExiste = false;
$colunaJustificativaExiste = false;
$colunaTurmaAulaIdExiste = false;

try {
    $tabelaPresencasExiste = $db->fetch("SHOW TABLES LIKE 'turma_presencas'");
    if ($tabelaPresencasExiste) {
        // Verificar quais colunas existem
        $colunas = $db->fetchAll("SHOW COLUMNS FROM turma_presencas");
        foreach ($colunas as $col) {
            if ($col['Field'] === 'justificativa') {
                $colunaJustificativaExiste = true;
            }
            if ($col['Field'] === 'turma_aula_id') {
                $colunaTurmaAulaIdExiste = true;
            }
        }
    }
} catch (Exception $e) {
    // Tabela não existe ou erro ao verificar
}

if ($tabelaPresencasExiste && $colunaTurmaAulaIdExiste && $aulaId) {
    // Tabela existe com estrutura esperada e temos aula_id
    if ($colunaJustificativaExiste) {
        // Coluna justificativa existe
        $alunos = $db->fetchAll("
            SELECT 
                a.*,
                tm.status as status_matricula,
                tm.data_matricula,
                tm.frequencia_percentual,
                tp.presente,
                tp.justificativa as observacao_presenca,
                tp.registrado_em as presenca_registrada_em,
                tp.id as presenca_id
            FROM alunos a
            JOIN turma_matriculas tm ON a.id = tm.aluno_id
            LEFT JOIN turma_presencas tp ON (
                a.id = tp.aluno_id 
                AND tp.turma_id = ? 
                AND tp.turma_aula_id = ?
            )
            WHERE tm.turma_id = ? 
            AND tm.status IN ('matriculado', 'cursando', 'concluido')
            ORDER BY a.nome ASC
        ", [$turmaId, $aulaId, $turmaId]);
    } else {
        // Coluna justificativa não existe, usar NULL
        $alunos = $db->fetchAll("
            SELECT 
                a.*,
                tm.status as status_matricula,
                tm.data_matricula,
                tm.frequencia_percentual,
                tp.presente,
                NULL as observacao_presenca,
                tp.registrado_em as presenca_registrada_em,
                tp.id as presenca_id
            FROM alunos a
            JOIN turma_matriculas tm ON a.id = tm.aluno_id
            LEFT JOIN turma_presencas tp ON (
                a.id = tp.aluno_id 
                AND tp.turma_id = ? 
                AND tp.turma_aula_id = ?
            )
            WHERE tm.turma_id = ? 
            AND tm.status IN ('matriculado', 'cursando', 'concluido')
            ORDER BY a.nome ASC
        ", [$turmaId, $aulaId, $turmaId]);
    }
} else {
    // Tabela não existe ou não tem turma_aula_id, buscar apenas alunos sem presenças
    $alunos = $db->fetchAll("
        SELECT 
            a.*,
            tm.status as status_matricula,
            tm.data_matricula,
            tm.frequencia_percentual,
            NULL as presente,
            NULL as observacao_presenca,
            NULL as presenca_registrada_em,
            NULL as presenca_id
        FROM alunos a
        JOIN turma_matriculas tm ON a.id = tm.aluno_id
        WHERE tm.turma_id = ? 
        AND tm.status IN ('matriculado', 'cursando', 'concluido')
        ORDER BY a.nome ASC
    ", [$turmaId]);
}

// Calcular estatísticas da turma
$estatisticasTurma = [
    'total_alunos' => count($alunos),
    'presentes' => 0,
    'ausentes' => 0,
    'sem_registro' => 0,
    'frequencia_media' => 0
];

foreach ($alunos as $aluno) {
    if ($aluno['presenca_id']) {
        if ($aluno['presente']) {
            $estatisticasTurma['presentes']++;
        } else {
            $estatisticasTurma['ausentes']++;
        }
    } else {
        $estatisticasTurma['sem_registro']++;
    }
}

if ($estatisticasTurma['total_alunos'] > 0) {
    $totalRegistradas = $estatisticasTurma['presentes'] + $estatisticasTurma['ausentes'];
    if ($totalRegistradas > 0) {
        $estatisticasTurma['frequencia_media'] = round(
            ($estatisticasTurma['presentes'] / $totalRegistradas) * 100, 2
        );
    }
}

// Buscar frequência geral da turma
// AJUSTE 2025-12-13: Replicar lógica da API diretamente aqui para evitar problemas de include
// Isso garante que a frequência seja carregada corretamente mesmo após recarregar a página
$frequenciaGeral = null;
try {
    // Usar a mesma lógica da função calcularFrequenciaTurma da API
    // Buscar dados da turma
    $turmaFreq = $db->fetch("SELECT * FROM turmas_teoricas WHERE id = ?", [$turmaId]);
    
    if ($turmaFreq) {
        // Contar aulas programadas da turma
        $aulasProgramadas = $db->fetch("
            SELECT COUNT(*) as total
            FROM turma_aulas_agendadas 
            WHERE turma_id = ? AND status IN ('agendada', 'realizada')
        ", [$turmaId]);
        
        $totalAulas = $aulasProgramadas['total'] ?? 0;
        
        // Buscar todos os alunos matriculados
        $alunosFreq = $db->fetchAll("
            SELECT 
                a.id,
                a.nome,
                a.cpf,
                tm.status as status_matricula,
                tm.data_matricula
            FROM alunos a
            JOIN turma_matriculas tm ON a.id = tm.aluno_id
            WHERE tm.turma_id = ? AND tm.status IN ('matriculado', 'cursando', 'concluido')
            ORDER BY a.nome ASC
        ", [$turmaId]);
        
        $frequencias = [];
        
        foreach ($alunosFreq as $alunoFreq) {
            // Contar presenças do aluno (usando mesma query da API)
            $presencas = $db->fetch("
                SELECT 
                    COUNT(*) as total_registradas,
                    COUNT(CASE WHEN tp.presente = 1 THEN 1 END) as presentes,
                    COUNT(CASE WHEN tp.presente = 0 THEN 1 END) as ausentes
                FROM turma_presencas tp
                INNER JOIN turma_aulas_agendadas taa ON tp.turma_aula_id = taa.id
                WHERE tp.turma_id = ? 
                AND tp.aluno_id = ?
                AND taa.status IN ('agendada', 'realizada')
            ", [$turmaId, $alunoFreq['id']]);
            
            $aulasPresentes = $presencas['presentes'] ?? 0;
            
            // Calcular percentual de frequência
            $percentualFrequencia = 0;
            if ($totalAulas > 0) {
                $percentualFrequencia = round(($aulasPresentes / $totalAulas) * 100, 2);
            }
            
            $frequencias[] = [
                'aluno' => $alunoFreq,
                'estatisticas' => [
                    'total_aulas_registradas' => $presencas['total_registradas'] ?? 0,
                    'aulas_presentes' => $aulasPresentes,
                    'aulas_ausentes' => $presencas['ausentes'] ?? 0,
                    'percentual_frequencia' => $percentualFrequencia,
                    'status_frequencia' => 'PENDENTE'
                ]
            ];
        }
        
        $frequenciaGeral = [
            'turma' => [
                'id' => $turmaFreq['id'],
                'nome' => $turmaFreq['nome'],
                'frequencia_minima' => $turmaFreq['frequencia_minima'] ?? 75.0
            ],
            'frequencias_alunos' => $frequencias
        ];
    }
} catch (Exception $e) {
    error_log("Erro ao buscar frequência geral da turma {$turmaId}: " . $e->getMessage());
    $frequenciaGeral = null;
}

// AJUSTE 2025-12 - URL base da API de presenças da turma
// Calcular caminho base relativo ao projeto de forma robusta
$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '/admin/index.php';
$baseRoot = '';

// Detectar caminho base a partir do SCRIPT_NAME
// Exemplo: /cfc-bom-conselho/admin/index.php -> /cfc-bom-conselho
if (preg_match('#^/([^/]+)/admin/#', $scriptPath, $matches)) {
    $baseRoot = '/' . $matches[1];
} elseif (strpos($scriptPath, '/admin/') !== false) {
    // Se não conseguir extrair, usar tudo antes de /admin/
    $parts = explode('/admin/', $scriptPath);
    $baseRoot = $parts[0] ?: '/cfc-bom-conselho';
} else {
    // Fallback: tentar detectar do REQUEST_URI
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('#^/([^/]+)/admin/#', $requestUri, $matches)) {
        $baseRoot = '/' . $matches[1];
    } else {
        $baseRoot = '/cfc-bom-conselho'; // Fallback padrão
    }
}

// Garantir que baseRoot não esteja vazio
if (empty($baseRoot) || $baseRoot === '/') {
    $baseRoot = '/cfc-bom-conselho';
}

$apiTurmaPresencasUrl = $baseRoot . '/admin/api/turma-presencas.php';
$apiTurmaFrequenciaUrl = $baseRoot . '/admin/api/turma-frequencia.php';

// AJUSTE 2025-12 - Debug: Log do caminho calculado (útil para diagnóstico)
// Remover em produção se necessário, mas manter para troubleshooting
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_log("[turma-chamada] baseRoot calculado: {$baseRoot}, API URL: {$apiTurmaFrequenciaUrl}");
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chamada - <?= htmlspecialchars($turma['nome']) ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        .chamada-container {
            background: #f8f9fa;
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .chamada-header {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            padding: 20px;
        }
        
        .chamada-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .aluno-item {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 10px;
            padding: 15px;
            transition: all 0.3s ease;
        }
        
        .aluno-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .aluno-item.presente {
            border-left: 4px solid #28a745;
            background: #f8fff9;
        }
        
        .aluno-item.ausente {
            border-left: 4px solid #dc3545;
            background: #fff8f8;
        }
        
        .aluno-item.sem-registro {
            border-left: 4px solid #6c757d;
        }
        
        .frequencia-badge {
            font-size: 0.8em;
            padding: 4px 8px;
            border-radius: 12px;
        }
        
        .frequencia-badge.alto {
            background: #d4edda;
            color: #155724;
        }
        
        .frequencia-badge.medio {
            background: #fff3cd;
            color: #856404;
        }
        
        .frequencia-badge.baixo {
            background: #f8d7da;
            color: #721c24;
        }
        
        .btn-presenca {
            min-width: 100px;
        }
        
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
        
        .stats-card {
            text-align: center;
            padding: 15px;
        }
        
        .stats-number {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stats-label {
            color: #6c757d;
            font-size: 0.9em;
        }
        
        .aula-selector {
            background: #e9ecef;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 20px;
        }
        
        .observacao-input {
            font-size: 0.9em;
            resize: vertical;
            min-height: 60px;
        }
        
        .btn-lote {
            margin-bottom: 15px;
        }
        
        .auditoria-info {
            font-size: 0.8em;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .status-turma {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 500;
        }
        
        .status-turma.ativo {
            background: #d4edda;
            color: #155724;
        }
        
        .status-turma.agendado {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-turma.encerrado {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* CSS Responsivo para Mobile */
        @media (max-width: 767px) {
            .btn-presenca {
                min-width: 120px;
                padding: 10px 15px;
                font-size: 0.9rem;
            }
            
            .stats-card {
                padding: 10px 5px;
            }
            
            .stats-number {
                font-size: 1.5em;
            }
            
            .stats-label {
                font-size: 0.8em;
            }
            
            .aluno-item {
                padding: 12px;
                margin-bottom: 12px;
            }
            
            .chamada-header {
                padding: 15px;
            }
            
            .chamada-header h2 {
                font-size: 1.3rem;
            }
            
            .toast-container {
                top: 10px;
                right: 10px;
                left: 10px;
            }
            
            .toast {
                max-width: 100%;
            }
            
            .btn-group {
                width: 100%;
            }
            
            .btn-group .btn {
                flex: 1;
            }
            
            .frequencia-badge {
                font-size: 0.75em;
                padding: 3px 6px;
            }
        }
    </style>
</head>
<body>
    <div class="chamada-container">
        <div class="container-fluid">
            <!-- Header da Chamada -->
            <div class="chamada-header">
                <!-- Aviso de turma concluída/cancelada -->
                <?php if (!$canEdit): ?>
                    <?php if ($turma['status'] === 'concluida'): ?>
                    <div class="alert alert-warning mb-3" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Turma concluída:</strong> Esta turma está concluída. Apenas administração pode ajustar presenças.
                    </div>
                    <?php elseif ($turma['status'] === 'cancelada'): ?>
                    <div class="alert alert-danger mb-3" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Turma cancelada:</strong> Não é possível editar presenças de turmas canceladas.
                    </div>
                    <?php elseif ($mostrarAlertaInstrutor): ?>
                    <div class="alert alert-info mb-3" role="alert">
                        <i class="fas fa-lock me-2"></i>
                        <strong>Sem permissão:</strong> <?php echo htmlspecialchars($mensagemAlertaInstrutor ?? 'Você não é o instrutor desta aula. Apenas visualização.'); ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <div class="row align-items-center">
                    <div class="col-12 col-md-8">
                        <h2 class="mb-1">
                            <i class="fas fa-clipboard-check text-primary"></i>
                            Chamada - <?= htmlspecialchars($turma['nome']) ?>
                        </h2>
                        <p class="text-muted mb-2">
                            <i class="fas fa-user-tie"></i> <?= htmlspecialchars($turma['instrutor_nome']) ?> |
                            <i class="fas fa-building"></i> <?= htmlspecialchars($turma['cfc_nome']) ?> |
                            <span class="status-turma <?= $turma['status'] ?>"><?= ucfirst($turma['status']) ?></span>
                        </p>
                        <?php if ($aulaAtual): ?>
                        <p class="text-muted mb-0">
                            <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($aulaAtual['data_aula'])) ?> |
                            <i class="fas fa-clock"></i> <?= $aulaAtual['duracao_minutos'] ?? 'N/A' ?> min |
                            <i class="fas fa-book"></i> <?= htmlspecialchars($aulaAtual['nome_aula']) ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <div class="col-12 col-md-4 text-end mt-2 mt-md-0">
                        <!-- Links Contextuais -->
                        <div class="btn-group" role="group">
                            <?php 
                            // Link para diário usando roteador admin
                            $origemParam = $origem ? '&origem=' . urlencode($origem) : '';
                            $urlDiario = 'index.php?page=turma-diario&turma_id=' . (int)$turmaId . ($aulaId ? '&aula_id=' . (int)$aulaId : '') . $origemParam;
                            ?>
                            <a href="<?php echo htmlspecialchars($urlDiario); ?>" 
                               class="btn btn-outline-info btn-sm" title="Ir para Diário desta aula">
                                <i class="fas fa-book-open"></i> Diário
                            </a>
                            <?php if ($userType === 'admin'): ?>
                                <!-- AJUSTE 2025-12 - Botão Relatório temporariamente desabilitado (página não existe) -->
                                <!-- TODO: Implementar página de relatórios ou remover botão permanentemente -->
                                <!--
                                <a href="turma-relatorios.php?turma_id=<?= $turmaId ?>" 
                                   class="btn btn-outline-success btn-sm" title="Relatórios da turma">
                                    <i class="fas fa-chart-bar"></i> Relatórios
                                </a>
                                -->
                            <?php endif; ?>
                            <?php 
                            // Botão Voltar respeitando origem
                            $backUrl   = 'index.php?page=turmas-teoricas&acao=detalhes&turma_id=' . (int)$turmaId;
                            $backTitle = 'Voltar para Gestão de Turmas';

                            if ($origem === 'instrutor') {
                                $backUrl   = $backUrlInstrutor;
                                $backTitle = 'Voltar para Dashboard do Instrutor';
                            }
                            ?>
                            <a href="<?php echo htmlspecialchars($backUrl); ?>"
                               class="btn btn-outline-secondary btn-sm"
                               title="<?php echo htmlspecialchars($backTitle); ?>">
                                <i class="fas fa-arrow-left"></i> Voltar
                            </a>
                        </div>
                        <?php if (!$modoSomenteLeitura && $canEdit): ?>
                        <button class="btn btn-primary ms-2" onclick="salvarChamada()">
                            <i class="fas fa-save"></i> Salvar Chamada
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Seletor de Aulas -->
            <?php if (count($aulas) > 1): ?>
            <div class="aula-selector">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Aula:</label>
                        <select class="form-select" id="aulaSelector" onchange="trocarAula()">
                            <?php foreach ($aulas as $aula): ?>
                            <option value="<?= $aula['id'] ?>" <?= $aula['id'] == $aulaId ? 'selected' : '' ?>>
                                Aula <?= htmlspecialchars($aula['ordem_global'] ?? $aula['ordem_disciplina'] ?? 'N/A') ?> - <?= htmlspecialchars($aula['nome_aula'] ?? 'Sem nome') ?>
                                (<?= date('d/m/Y', strtotime($aula['data_aula'])) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div class="btn-group" role="group">
                            <button class="btn btn-outline-primary btn-sm" onclick="navegarAula('anterior')">
                                <i class="fas fa-chevron-left"></i> Anterior
                            </button>
                            <button class="btn btn-outline-primary btn-sm" onclick="navegarAula('proxima')">
                                Próxima <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Estatísticas da Turma -->
            <div class="row mb-4">
                <div class="col-6 col-md-3 mb-3 mb-md-0">
                    <div class="chamada-card stats-card">
                        <div class="stats-number text-primary"><?= $estatisticasTurma['total_alunos'] ?></div>
                        <div class="stats-label">Total de Alunos</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-3 mb-md-0">
                    <div class="chamada-card stats-card">
                        <div class="stats-number text-success"><?= $estatisticasTurma['presentes'] ?></div>
                        <div class="stats-label">Presentes</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-3 mb-md-0">
                    <div class="chamada-card stats-card">
                        <div class="stats-number text-danger"><?= $estatisticasTurma['ausentes'] ?></div>
                        <div class="stats-label">Ausentes</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-3 mb-md-0">
                    <div class="chamada-card stats-card">
                        <div class="stats-number text-info"><?= $estatisticasTurma['frequencia_media'] ?>%</div>
                        <div class="stats-label">Frequência Média</div>
                    </div>
                </div>
            </div>

            <!-- Ações em Lote -->
            <?php if (!$modoSomenteLeitura && $canEdit && !empty($alunos)): ?>
            <div class="chamada-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-tasks"></i> Ações em Lote
                    </h5>
                </div>
                <div class="card-body">
                    <div class="btn-lote">
                        <button class="btn btn-success btn-lote" onclick="marcarTodos('presente')">
                            <i class="fas fa-check-circle"></i> Marcar Todos como Presentes
                        </button>
                        <button class="btn btn-warning btn-lote" onclick="marcarTodos('ausente')">
                            <i class="fas fa-times-circle"></i> Marcar Todos como Ausentes
                        </button>
                        <button class="btn btn-secondary btn-lote" onclick="limparTodos()">
                            <i class="fas fa-eraser"></i> Limpar Todas as Marcações
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Lista de Alunos -->
            <div class="chamada-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-users"></i> Lista de Chamada
                        <?php if ($modoSomenteLeitura): ?>
                        <span class="badge bg-warning ms-2">Somente Leitura</span>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($alunos)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Nenhum aluno matriculado</h5>
                        <p class="text-muted">Esta turma não possui alunos matriculados.</p>
                    </div>
                    <?php else: ?>
                    <div id="listaAlunos">
                        <?php foreach ($alunos as $aluno): ?>
                        <div class="aluno-item <?= $aluno['presenca_id'] ? ($aluno['presente'] ? 'presente' : 'ausente') : 'sem-registro' ?>" 
                             data-aluno-id="<?= $aluno['id'] ?>" 
                             data-presenca-id="<?= $aluno['presenca_id'] ?>"
                             data-frequencia-aluno-id="<?= $aluno['id'] ?>">
                            <!-- Layout Mobile-First: Empilhado em mobile, grid em desktop -->
                            <div class="row align-items-center">
                                <!-- Nome e CPF -->
                                <div class="col-12 col-md-4 mb-2 mb-md-0">
                                    <div class="d-flex align-items-center">
                                        <div class="me-2 me-md-3">
                                            <i class="fas fa-user-circle fa-2x text-muted"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center gap-2">
                                                <h6 class="mb-1">
                                                    <a href="#" onclick="visualizarAlunoInstrutor(<?= $aluno['id'] ?>, <?= $turmaId ?>); return false;" 
                                                       class="text-decoration-none text-dark" 
                                                       title="Ver detalhes do aluno">
                                                        <?= htmlspecialchars($aluno['nome']) ?>
                                                    </a>
                                                </h6>
                                                <button class="btn btn-sm btn-outline-primary p-1" 
                                                        onclick="visualizarAlunoInstrutor(<?= $aluno['id'] ?>, <?= $turmaId ?>); return false;"
                                                        title="Ver detalhes do aluno"
                                                        style="line-height: 1; min-width: 28px; height: 28px;">
                                                    <i class="fas fa-user" style="font-size: 0.75rem;"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted"><?= htmlspecialchars($aluno['cpf']) ?></small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Status -->
                                <div class="col-6 col-md-2 mb-2 mb-md-0">
                                    <span class="badge bg-<?= in_array($aluno['status_matricula'], ['cursando', 'matriculado']) ? 'success' : 'primary' ?>">
                                        <?= ucfirst($aluno['status_matricula']) ?>
                                    </span>
                                </div>
                                
                                <!-- Botões de Presença -->
                                <div class="col-12 col-md-6 mt-2 mt-md-0">
                                    <?php if (!$modoSomenteLeitura): ?>
                                    <div class="btn-group w-100 w-md-auto" role="group">
                                        <button class="btn btn-sm btn-outline-success btn-presenca <?= $aluno['presenca_id'] && $aluno['presente'] ? 'active' : '' ?>" 
                                                onclick="marcarPresenca(<?= $aluno['id'] ?>, true)">
                                            <i class="fas fa-check"></i> <span class="d-none d-md-inline">Presente</span>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger btn-presenca <?= $aluno['presenca_id'] && !$aluno['presente'] ? 'active' : '' ?>" 
                                                onclick="marcarPresenca(<?= $aluno['id'] ?>, false)">
                                            <i class="fas fa-times"></i> <span class="d-none d-md-inline">Ausente</span>
                                        </button>
                                    </div>
                                    <?php else: ?>
                                    <div class="text-muted">
                                        <?php if ($aluno['presenca_id']): ?>
                                            <?= $aluno['presente'] ? '<i class="fas fa-check text-success"></i> Presente' : '<i class="fas fa-times text-danger"></i> Ausente' ?>
                                        <?php else: ?>
                                            <i class="fas fa-minus text-muted"></i> Sem registro
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Observação -->
                            <?php if ($aluno['presenca_id'] && $aluno['observacao_presenca']): ?>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <small class="text-muted">
                                        <i class="fas fa-comment"></i> <?= htmlspecialchars($aluno['observacao_presenca']) ?>
                                    </small>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Auditoria -->
                            <?php if ($aluno['presenca_id'] && $aluno['presenca_registrada_em']): ?>
                            <div class="row mt-1">
                                <div class="col-12">
                                    <div class="auditoria-info">
                                        <i class="fas fa-clock"></i> Registrado em <?= date('d/m/Y H:i', strtotime($aluno['presenca_registrada_em'])) ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Modal para Visualizar Aluno (Modo Instrutor) -->
    <?php if ($origem === 'instrutor' || $userType === 'instrutor'): ?>
    <div class="modal fade" id="modalAlunoInstrutor" tabindex="-1" aria-labelledby="modalAlunoInstrutorLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAlunoInstrutorLabel">
                        <i class="fas fa-user"></i> Detalhes do Aluno
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body" id="modalAlunoInstrutorBody">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p class="mt-2">Carregando informações do aluno...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal de Observação -->
    <div class="modal fade" id="modalObservacao" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Observação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <textarea class="form-control observacao-input" id="observacaoInput" 
                              placeholder="Digite uma observação sobre a presença/ausência..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="confirmarPresenca()">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Variáveis globais
        let turmaId = <?= $turmaId ?>;
        let aulaId = <?= $aulaId ?>;
        let canEdit = <?= $canEdit ? 'true' : 'false' ?>;
        let modoSomenteLeitura = <?= $modoSomenteLeitura ? 'true' : 'false' ?>;
        let presencaPendente = null;
        let alteracoesPendentes = false;
        const API_TURMA_PRESENCAS = <?php echo json_encode($apiTurmaPresencasUrl); ?>;
        const API_TURMA_FREQUENCIA = <?php echo json_encode($apiTurmaFrequenciaUrl); ?>;
        const ORIGEM_FLUXO = <?php echo json_encode($origem); ?>;
        const BACK_URL_INSTRUTOR = <?php echo json_encode($backUrlInstrutor); ?>;
        
        // AJUSTE 2025-12 - Debug: Verificar constantes
        console.log('[Frequência] Constantes definidas:', {
            API_TURMA_PRESENCAS: API_TURMA_PRESENCAS,
            API_TURMA_FREQUENCIA: API_TURMA_FREQUENCIA,
            turmaId: turmaId,
            aulaId: aulaId
        });
        
        // Validar que API_TURMA_FREQUENCIA está definida e não vazia
        if (typeof API_TURMA_FREQUENCIA === 'undefined' || !API_TURMA_FREQUENCIA) {
            console.error('[Frequência] ERRO CRÍTICO: API_TURMA_FREQUENCIA não está definida ou está vazia!');
        } else {
            console.log('[Frequência] API_TURMA_FREQUENCIA válida:', API_TURMA_FREQUENCIA);
        }

        // Função para mostrar toast
        function mostrarToast(mensagem, tipo = 'success') {
            const toastContainer = document.getElementById('toastContainer');
            const toastId = 'toast-' + Date.now();
            
            const toastHtml = `
                <div class="toast" id="${toastId}" role="alert">
                    <div class="toast-header">
                        <i class="fas fa-${tipo === 'success' ? 'check-circle text-success' : 'exclamation-triangle text-warning'} me-2"></i>
                        <strong class="me-auto">Sistema</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                    </div>
                    <div class="toast-body">${mensagem}</div>
                </div>
            `;
            
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            
            const toast = new bootstrap.Toast(document.getElementById(toastId));
            toast.show();
            
            // Remover toast após 5 segundos
            setTimeout(() => {
                const toastElement = document.getElementById(toastId);
                if (toastElement) {
                    toastElement.remove();
                }
            }, 5000);
        }

        // Função para marcar presença individual
        function marcarPresenca(alunoId, presente) {
            if (modoSomenteLeitura || !canEdit) {
                mostrarToast('Você não tem permissão para editar presenças', 'error');
                return;
            }

            // Verificar se já existe presença
            const alunoItem = document.querySelector(`[data-aluno-id="${alunoId}"]`);
            const presencaId = alunoItem.dataset.presencaId;
            
            if (presencaId) {
                // Atualizar presença existente
                atualizarPresenca(presencaId, presente);
            } else {
                // Criar nova presença
                criarPresenca(alunoId, presente);
            }
        }

        // Função para criar nova presença
        function criarPresenca(alunoId, presente) {
            // AJUSTE 2025-12 - Garantir que admin/secretaria sempre enviem origem correta
            const dados = {
                turma_id: turmaId,
                turma_aula_id: aulaId,
                aluno_id: alunoId,
                presente: presente
            };
            
            // Incluir origem: se ORIGEM_FLUXO existe e não está vazio, usar; senão, usar 'admin' para admin/secretaria
            if (ORIGEM_FLUXO && ORIGEM_FLUXO.trim() !== '') {
                dados.origem = ORIGEM_FLUXO;
            } else {
                // Admin/secretaria sem origem explícita: usar 'admin'
                dados.origem = 'admin';
            }

            fetch(API_TURMA_PRESENCAS, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(dados)
            })
            .then(async response => {
                const text = await response.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Resposta não é JSON válido:', text);
                    throw new Error('Erro de comunicação com o servidor. Tente novamente.');
                }
                if (!data.success) {
                    throw new Error(data.message || 'Erro ao registrar presença.');
                }
                return data;
            })
            .then(data => {
                mostrarToast('Presença registrada com sucesso!');
                atualizarInterfaceAluno(alunoId, presente, data.presenca_id);
                alteracoesPendentes = true;
                // AJUSTE 2025-12 - Atualizar frequência do aluno após criar presença
                atualizarFrequenciaAluno(alunoId);
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarToast(error.message || 'Erro de conexão. Tente novamente.', 'error');
            });
        }

        // Função para atualizar presença existente
        function atualizarPresenca(presencaId, presente) {
            // AJUSTE 2025-12 - Garantir que admin/secretaria sempre enviem origem correta
            const dados = {
                presente: presente
            };
            
            // Incluir origem: se ORIGEM_FLUXO existe e não está vazio, usar; senão, usar 'admin' para admin/secretaria
            const origemParaEnvio = (ORIGEM_FLUXO && ORIGEM_FLUXO.trim() !== '') ? ORIGEM_FLUXO : 'admin';
            dados.origem = origemParaEnvio;
            
            // Montar URL com query string
            const params = { id: presencaId };
            params.origem = origemParaEnvio;
            const url = API_TURMA_PRESENCAS + '?' + new URLSearchParams(params).toString();

            fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(dados)
            })
            .then(async response => {
                const text = await response.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Resposta não é JSON válido:', text);
                    throw new Error('Erro de comunicação com o servidor. Tente novamente.');
                }
                if (!data.success) {
                    throw new Error(data.message || 'Erro ao atualizar presença.');
                }
                return data;
            })
            .then(data => {
                mostrarToast('Presença atualizada com sucesso!');
                const alunoItem = document.querySelector(`[data-presenca-id="${presencaId}"]`);
                const alunoId = alunoItem.dataset.alunoId;
                atualizarInterfaceAluno(alunoId, presente, presencaId);
                alteracoesPendentes = true;
                // Atualizar frequência do aluno após atualizar presença
                atualizarFrequenciaAluno(alunoId);
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarToast(error.message || 'Erro de conexão. Tente novamente.', 'error');
            });
        }
        
        // AJUSTE 2025-12 - Função para atualizar frequência do aluno após marcar presença
        function atualizarFrequenciaAluno(alunoId) {
            console.log('[Frequência] Iniciando atualização para aluno:', alunoId, 'turma:', turmaId);
            
            if (!turmaId || !alunoId) {
                console.warn('[Frequência] Turma ID ou Aluno ID não disponível:', { turmaId, alunoId });
                return;
            }
            
            // Verificar se a constante está definida
            if (typeof API_TURMA_FREQUENCIA === 'undefined') {
                console.error('[Frequência] API_TURMA_FREQUENCIA não está definida!');
                return;
            }
            
            // Buscar frequência atualizada via API usando caminho correto
            const url = `${API_TURMA_FREQUENCIA}?turma_id=${turmaId}&aluno_id=${alunoId}`;
            console.log('[Frequência] Fazendo requisição para:', url);
            
            fetch(url)
                .then(async response => {
                    console.log('[Frequência] Resposta recebida:', response.status, response.statusText);
                    
                    // AJUSTE 2025-12 - Verificar status HTTP primeiro
                    if (!response.ok) {
                        const text = await response.text();
                        console.error('[Frequência] Erro HTTP:', response.status, text.substring(0, 200));
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    // Verificar se a resposta é JSON válido
                    const contentType = response.headers.get('content-type') || '';
                    if (!contentType.includes('application/json')) {
                        const text = await response.text();
                        console.error('[Frequência] Resposta não é JSON. Content-Type:', contentType);
                        console.error('[Frequência] Resposta completa:', text.substring(0, 500));
                        throw new Error(`Resposta não é JSON (status: ${response.status}, Content-Type: ${contentType})`);
                    }
                    
                    return response.json();
                })
                .then(data => {
                    console.log('[Frequência] Dados recebidos:', data);
                    
                    if (data.success && data.data && data.data.estatisticas) {
                        const percentual = data.data.estatisticas.percentual_frequencia;
                        console.log('[Frequência] Percentual calculado:', percentual);
                        
                        const badgeElement = document.getElementById(`freq-badge-${alunoId}`);
                        console.log('[Frequência] Elemento badge encontrado:', badgeElement);
                        
                        if (badgeElement) {
                            // Atualizar valor - usar formatação brasileira (vírgula)
                            const novoValor = percentual.toFixed(1).replace('.', ',') + '%';
                            console.log('[Frequência] Atualizando badge de', badgeElement.textContent, 'para', novoValor);
                            badgeElement.textContent = novoValor;
                            
                            // Atualizar classe (alto/médio/baixo)
                            badgeElement.className = 'frequencia-badge ';
                            // Frequência mínima padrão: 75%
                            const frequenciaMinima = 75.0;
                            if (percentual >= frequenciaMinima) {
                                badgeElement.className += 'alto';
                            } else if (percentual >= (frequenciaMinima - 10)) {
                                badgeElement.className += 'medio';
                            } else {
                                badgeElement.className += 'baixo';
                            }
                            
                            console.log('[Frequência] Badge atualizado com sucesso!');
                        } else {
                            console.warn('[Frequência] Elemento badge não encontrado para aluno:', alunoId, 'ID esperado: freq-badge-' + alunoId);
                        }
                    } else {
                        console.warn('[Frequência] Resposta da API não contém dados de frequência:', data);
                    }
                })
                .catch(error => {
                    console.error('[Frequência] Erro ao atualizar frequência do aluno:', error);
                    console.error('[Frequência] URL tentada:', url);
                    // Não mostrar erro ao usuário para não poluir a interface
                    // A presença já foi registrada com sucesso, a frequência pode ser atualizada depois
                    // Tentar recarregar a página após um delay para pegar frequência atualizada
                    // setTimeout(() => {
                    //     const badgeElement = document.getElementById(`freq-badge-${alunoId}`);
                    //     if (badgeElement && badgeElement.textContent === '0,0%') {
                    //         console.log('[Frequência] Tentando recarregar frequência após erro...');
                    //         // Não recarregar automaticamente, deixar usuário recarregar se necessário
                    //     }
                    // }, 2000);
                });
        }

        // Função para atualizar interface do aluno
        function atualizarInterfaceAluno(alunoId, presente, presencaId) {
            const alunoItem = document.querySelector(`[data-aluno-id="${alunoId}"]`);
            
            // Atualizar classe do item
            alunoItem.className = `aluno-item ${presente ? 'presente' : 'ausente'}`;
            
            // Atualizar botões
            const btnPresente = alunoItem.querySelector('.btn-outline-success');
            const btnAusente = alunoItem.querySelector('.btn-outline-danger');
            
            if (presente) {
                btnPresente.classList.add('active');
                btnAusente.classList.remove('active');
            } else {
                btnPresente.classList.remove('active');
                btnAusente.classList.add('active');
            }
            
            // Atualizar presenca_id
            alunoItem.dataset.presencaId = presencaId;
            
            // Atualizar estatísticas
            atualizarEstatisticas();
        }

        // Função para marcar todos os alunos
        function marcarTodos(tipo) {
            if (modoSomenteLeitura || !canEdit) {
                mostrarToast('Você não tem permissão para editar presenças', 'error');
                return;
            }

            const presente = tipo === 'presente';
            const alunos = document.querySelectorAll('[data-aluno-id]');
            const presencas = [];

            alunos.forEach(aluno => {
                const alunoId = aluno.dataset.alunoId;
                const presencaId = aluno.dataset.presencaId;
                
                if (presencaId) {
                    // Atualizar presença existente
                    atualizarPresenca(presencaId, presente);
                } else {
                    // Adicionar à lista de novas presenças
                    presencas.push({
                        aluno_id: parseInt(alunoId),
                        presente: presente
                    });
                }
            });

            // Processar novas presenças em lote
            if (presencas.length > 0) {
                // AJUSTE 2025-12 - Garantir origem correta para admin/secretaria
                const origemParaEnvio = (ORIGEM_FLUXO && ORIGEM_FLUXO.trim() !== '') ? ORIGEM_FLUXO : 'admin';
                
                const dados = {
                    turma_id: turmaId,
                    turma_aula_id: aulaId, // AJUSTE: usar turma_aula_id (nome correto)
                    presencas: presencas,
                    origem: origemParaEnvio
                };

                fetch(API_TURMA_PRESENCAS, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(dados)
                })
                .then(async response => {
                    const text = await response.text();
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        console.error('Resposta não é JSON válido:', text);
                        throw new Error('Erro de comunicação com o servidor. Tente novamente.');
                    }
                    if (!data.success) {
                        throw new Error(data.message || 'Erro ao processar presenças.');
                    }
                    return data;
                })
                .then(data => {
                    mostrarToast(`Presenças processadas: ${data.sucessos} sucessos`);
                    if (data.erros && data.erros.length > 0) {
                        mostrarToast('Alguns erros: ' + data.erros.join(', '), 'error');
                    }
                    // Atualizar frequências de todos os alunos processados
                    presencas.forEach(presenca => {
                        atualizarFrequenciaAluno(presenca.aluno_id);
                    });
                    recarregarPagina();
                })
                .catch(error => {
                    console.error('Erro:', error);
                    mostrarToast(error.message || 'Erro de conexão. Tente novamente.', 'error');
                });
            }
        }

        // Função para limpar todas as marcações
        function limparTodos() {
            if (modoSomenteLeitura || !canEdit) {
                mostrarToast('Você não tem permissão para editar presenças', 'error');
                return;
            }

            if (!confirm('Tem certeza que deseja limpar todas as marcações?')) {
                return;
            }

            const alunos = document.querySelectorAll('[data-presenca-id]');
            let promises = [];

            alunos.forEach(aluno => {
                const presencaId = aluno.dataset.presencaId;
                if (presencaId) {
                    // Montar URL com query string
                    const params = { id: presencaId };
                    if (ORIGEM_FLUXO) {
                        params.origem = ORIGEM_FLUXO;
                    }
                    const url = API_TURMA_PRESENCAS + '?' + new URLSearchParams(params).toString();
                    promises.push(
                        fetch(url, {
                            method: 'DELETE'
                        })
                    );
                }
            });

            Promise.all(promises)
            .then(async responses => {
                const results = await Promise.all(responses.map(async r => {
                    const text = await r.text();
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Resposta não é JSON válido:', text);
                        throw new Error('Erro de comunicação com o servidor.');
                    }
                }));
                return results;
            })
            .then(data => {
                mostrarToast('Todas as marcações foram removidas!');
                recarregarPagina();
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarToast(error.message || 'Erro ao limpar marcações', 'error');
            });
        }

        // Função para atualizar estatísticas
        function atualizarEstatisticas() {
            const alunos = document.querySelectorAll('[data-aluno-id]');
            let presentes = 0;
            let ausentes = 0;
            let semRegistro = 0;

            alunos.forEach(aluno => {
                const presencaId = aluno.dataset.presencaId;
                if (presencaId) {
                    if (aluno.classList.contains('presente')) {
                        presentes++;
                    } else {
                        ausentes++;
                    }
                } else {
                    semRegistro++;
                }
            });

            // Atualizar números na interface (com verificação de existência)
            const statsPresentes = document.querySelector('.stats-number.text-success');
            const statsAusentes = document.querySelector('.stats-number.text-danger');
            const statsFrequencia = document.querySelector('.stats-number.text-info');
            
            if (statsPresentes) {
                statsPresentes.textContent = presentes;
            }
            if (statsAusentes) {
                statsAusentes.textContent = ausentes;
            }
            
            // Calcular frequência média
            const totalRegistradas = presentes + ausentes;
            let frequenciaMedia = 0;
            if (totalRegistradas > 0) {
                frequenciaMedia = Math.round((presentes / totalRegistradas) * 100);
            }
            if (statsFrequencia) {
                statsFrequencia.textContent = frequenciaMedia + '%';
            }
        }

        // AJUSTE INSTRUTOR - FLUXO CHAMADA/DIARIO - Função para trocar de aula preservando page=turma-chamada
        function trocarAula() {
            const novoAulaId = document.getElementById('aulaSelector').value;
            if (novoAulaId != aulaId) {
                // Preservar parâmetro page e origem se existir
                const urlParams = new URLSearchParams(window.location.search);
                const origem = urlParams.get('origem') || '';
                const origemParam = origem ? `&origem=${origem}` : '';
                window.location.href = `?page=turma-chamada&turma_id=${turmaId}&aula_id=${novoAulaId}${origemParam}`;
            }
        }

        // Função para navegar entre aulas
        function navegarAula(direcao) {
            const selector = document.getElementById('aulaSelector');
            const opcoes = Array.from(selector.options);
            const indiceAtual = opcoes.findIndex(opcao => opcao.value == aulaId);
            
            let novoIndice;
            if (direcao === 'anterior') {
                novoIndice = indiceAtual - 1;
            } else {
                novoIndice = indiceAtual + 1;
            }
            
            if (novoIndice >= 0 && novoIndice < opcoes.length) {
                selector.value = opcoes[novoIndice].value;
                trocarAula();
            }
        }

        // PATCH: Função para salvar chamada - atualiza status da aula para 'realizada'
        function salvarChamada() {
            if (modoSomenteLeitura || !canEdit) {
                mostrarToast('Você não tem permissão para salvar chamada', 'error');
                return;
            }
            
            // Mostrar loading
            const btnSalvar = document.querySelector('button[onclick="salvarChamada()"]');
            const textoOriginal = btnSalvar ? btnSalvar.innerHTML : '';
            if (btnSalvar) {
                btnSalvar.disabled = true;
                btnSalvar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
            }
            
            // Chamar API para finalizar chamada
            fetch(API_TURMA_PRESENCAS + '?acao=finalizar_chamada', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    turma_id: turmaId,
                    turma_aula_id: aulaId
                })
            })
            .then(async response => {
                const text = await response.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Resposta não é JSON válido:', text);
                    throw new Error('Erro de comunicação com o servidor. Tente novamente.');
                }
                if (!data.success) {
                    throw new Error(data.message || 'Erro ao salvar chamada.');
                }
                return data;
            })
            .then(data => {
                mostrarToast('Chamada salva com sucesso! A aula foi marcada como realizada.');
                alteracoesPendentes = false;
                
                // Recarregar página após 1 segundo para atualizar contadores no dashboard
                setTimeout(() => {
                    if (ORIGEM_FLUXO === 'instrutor' && typeof BACK_URL_INSTRUTOR !== 'undefined' && BACK_URL_INSTRUTOR) {
                        // Se veio do dashboard do instrutor, voltar para lá usando o caminho correto
                        window.location.href = BACK_URL_INSTRUTOR;
                    } else {
                        // Senão, recarregar a página atual
                        window.location.reload();
                    }
                }, 1000);
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarToast(error.message || 'Erro de conexão. Tente novamente.', 'error');
                
                // Reabilitar botão
                if (btnSalvar) {
                    btnSalvar.disabled = false;
                    btnSalvar.innerHTML = textoOriginal;
                }
            });
        }

        // Função para recarregar página
        function recarregarPagina() {
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }

        // Avisar sobre alterações não salvas
        window.addEventListener('beforeunload', function(e) {
            if (alteracoesPendentes) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Função para visualizar aluno (instrutor)
        function visualizarAlunoInstrutor(alunoId, turmaId) {
            const modal = new bootstrap.Modal(document.getElementById('modalAlunoInstrutor'));
            const modalBody = document.getElementById('modalAlunoInstrutorBody');
            
            // Mostrar loading
            modalBody.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="mt-2">Carregando informações do aluno...</p>
                </div>
            `;
            
            // Abrir modal
            modal.show();
            
            // Buscar dados do aluno via endpoint restrito
            fetch(`../admin/api/aluno-detalhes-instrutor.php?aluno_id=${alunoId}&turma_id=${turmaId}`)
                .then(response => {
                    if (!response.ok) {
                        if (response.status === 403) {
                            throw new Error('Você não tem permissão para visualizar este aluno');
                        }
                        throw new Error('Erro ao carregar dados do aluno');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const aluno = data.aluno;
                        const turma = data.turma;
                        const matricula = data.matricula;
                        const frequencia = data.frequencia;
                        
                        // Formatar CPF
                        function formatarCPF(cpf) {
                            if (!cpf) return 'Não informado';
                            const cpfLimpo = cpf.replace(/\D/g, '');
                            return cpfLimpo.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                        }
                        
                        // Formatar telefone
                        function formatarTelefone(tel) {
                            if (!tel) return 'Não informado';
                            const telLimpo = tel.replace(/\D/g, '');
                            if (telLimpo.length === 11) {
                                return telLimpo.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
                            } else if (telLimpo.length === 10) {
                                return telLimpo.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
                            }
                            return tel;
                        }
                        
                        // Formatar data de nascimento
                        const dataNasc = aluno.data_nascimento ? new Date(aluno.data_nascimento).toLocaleDateString('pt-BR') : 'Não informado';
                        
                        // Categoria CNH
                        const categoriaCNH = aluno.categoria_cnh || 'Não informado';
                        
                        // Formatar frequência
                        const freqPercent = frequencia ? frequencia.frequencia_percentual.toFixed(1) : '0.0';
                        const freqBadgeClass = freqPercent >= 75 ? 'bg-success' : (freqPercent >= 60 ? 'bg-warning' : 'bg-danger');
                        
                        // Montar HTML
                        let historicoHtml = '';
                        if (frequencia && frequencia.historico && frequencia.historico.length > 0) {
                            historicoHtml = `
                                <h6 class="mt-3 mb-2">Últimas Presenças:</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Data</th>
                                                <th>Aula</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${frequencia.historico.map(p => `
                                                <tr>
                                                    <td>${new Date(p.data_aula).toLocaleDateString('pt-BR')}</td>
                                                    <td>${p.nome_aula || 'N/A'}</td>
                                                    <td>
                                                        <span class="badge ${p.presente ? 'bg-success' : 'bg-danger'}">
                                                            ${p.presente ? 'PRESENTE' : 'AUSENTE'}
                                                        </span>
                                                    </td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                            `;
                        } else {
                            historicoHtml = '<p class="text-muted mt-3">Nenhuma presença registrada ainda.</p>';
                        }
                        
                        modalBody.innerHTML = `
                            <div class="text-center mb-3">
                                ${aluno.foto && aluno.foto.trim() !== '' 
                                    ? `<img src="../${aluno.foto}" 
                                           alt="Foto do aluno ${aluno.nome}" 
                                           class="rounded-circle" 
                                           style="width: 100px; height: 100px; object-fit: cover; border: 3px solid #dee2e6;"
                                           onerror="this.outerHTML='<div class=\\'rounded-circle bg-secondary d-flex align-items-center justify-content-center mx-auto\\' style=\\'width:100px;height:100px;border:3px solid #dee2e6;\\'><i class=\\'fas fa-user fa-3x text-white\\'></i></div>'">`
                                    : `<div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mx-auto" 
                                           style="width: 100px; height: 100px; border: 3px solid #dee2e6;">
                                            <i class="fas fa-user fa-3x text-white"></i>
                                          </div>`
                                }
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Dados Pessoais</h6>
                                    <dl class="row mb-3">
                                        <dt class="col-sm-4">Nome:</dt>
                                        <dd class="col-sm-8"><strong>${aluno.nome}</strong></dd>
                                        
                                        <dt class="col-sm-4">CPF:</dt>
                                        <dd class="col-sm-8">${formatarCPF(aluno.cpf)}</dd>
                                        
                                        <dt class="col-sm-4">Data Nascimento:</dt>
                                        <dd class="col-sm-8">${dataNasc}</dd>
                                        
                                        <dt class="col-sm-4">Categoria CNH:</dt>
                                        <dd class="col-sm-8">
                                            <span class="badge bg-info">${categoriaCNH}</span>
                                        </dd>
                                    </dl>
                                </div>
                                <div class="col-md-6">
                                    <h6>Contato</h6>
                                    <dl class="row mb-3">
                                        <dt class="col-sm-4">E-mail:</dt>
                                        <dd class="col-sm-8">
                                            ${aluno.email ? `<a href="mailto:${aluno.email}">${aluno.email}</a>` : 'Não informado'}
                                        </dd>
                                        
                                        <dt class="col-sm-4">Telefone:</dt>
                                        <dd class="col-sm-8">
                                            ${aluno.telefone ? `
                                                <a href="tel:${aluno.telefone.replace(/\D/g, '')}">${formatarTelefone(aluno.telefone)}</a>
                                                <a href="https://wa.me/55${aluno.telefone.replace(/\D/g, '')}" 
                                                   target="_blank" 
                                                   class="btn btn-sm btn-success ms-2" 
                                                   title="Abrir WhatsApp">
                                                    <i class="fab fa-whatsapp"></i>
                                                </a>
                                            ` : 'Não informado'}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-12">
                                    <h6>Matrícula na Turma</h6>
                                    <dl class="row mb-3">
                                        <dt class="col-sm-4">Turma:</dt>
                                        <dd class="col-sm-8">${turma.nome}</dd>
                                        
                                        <dt class="col-sm-4">Status:</dt>
                                        <dd class="col-sm-8">
                                            <span class="badge bg-primary">${matricula.status}</span>
                                        </dd>
                                        
                                        <dt class="col-sm-4">Data Matrícula:</dt>
                                        <dd class="col-sm-8">${new Date(matricula.data_matricula).toLocaleDateString('pt-BR')}</dd>
                                        
                                        <dt class="col-sm-4">Frequência:</dt>
                                        <dd class="col-sm-8">
                                            <span class="badge ${freqBadgeClass}">${freqPercent}%</span>
                                            <small class="text-muted ms-2">
                                                (${frequencia.total_presentes} presentes / ${frequencia.total_aulas} aulas)
                                            </small>
                                        </dd>
                                    </dl>
                                    
                                    ${historicoHtml}
                                </div>
                            </div>
                        `;
                    } else {
                        modalBody.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> ${data.message || 'Erro ao carregar dados do aluno'}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> ${error.message || 'Erro ao carregar dados do aluno'}
                        </div>
                    `;
                });
        }

        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Interface de chamada carregada');
            console.log('Turma ID:', turmaId);
            console.log('Aula ID:', aulaId);
            console.log('Pode editar:', canEdit);
        });
    </script>
</body>
</html>
