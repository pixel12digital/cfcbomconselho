<?php
/**
 * Dashboard do Instrutor - Mobile First
 * Interface focada em usabilidade móvel
 * IMPORTANTE: Instrutor NÃO pode criar agendamentos, apenas cancelar/transferir
 * 
 * FASE INSTRUTOR - AULAS TEORICAS - Correção completa
 * 
 * ANTES:
 * - Dashboard mostrava apenas aulas práticas da tabela `aulas`
 * - Estatísticas do dia não incluíam aulas teóricas
 * - "Aulas de Hoje" e "Próximas Aulas" não mostravam aulas teóricas
 * 
 * DEPOIS:
 * - Dashboard agora mostra aulas práticas E teóricas
 * - Estatísticas do dia combinam dados de ambas as fontes
 * - "Aulas de Hoje" inclui aulas teóricas do instrutor
 * - "Próximas Aulas (7 dias)" inclui aulas teóricas do instrutor
 * - Aulas teóricas mostram: turma, disciplina, sala
 * - Botões de ação diferenciados para teóricas (Chamada/Diário) vs práticas (Transferir/Cancelar)
 * 
 * ARQUIVOS AFETADOS:
 * - instrutor/dashboard.php (queries e exibição)
 * - instrutor/aulas.php (queries de listagem completa)
 * 
 * LÓGICA:
 * - Aulas práticas: tabela `aulas` com `instrutor_id`
 * - Aulas teóricas: tabela `turma_aulas_agendadas` com `instrutor_id`
 * - Ambas são combinadas e ordenadas por data/hora
 * 
 * REFACTOR DASHBOARD INSTRUTOR - Reorganização de Layout/UX (2025-11)
 * 
 * MUDANÇAS REALIZADAS (REFINAMENTO FINAL):
 * 
 * 1. BLOCO SUPERIOR - Grid 2 colunas (desktop):
 *    - Coluna esquerda: Card "Próxima Aula" compacto com badge, horário, tipo, status de chamada e botões
 *    - Coluna direita: Card "Resumo de Hoje" (3 indicadores) + Card "Pendências"
 *    - Grid responsivo: 2 colunas no desktop (>= 992px), empilhado no mobile
 * 
 * 2. AÇÕES RÁPIDAS:
 *    - Movidas para logo abaixo do grid superior
 *    - Card horizontal com botões em linha (desktop) ou empilhados (mobile)
 *    - Botões menores e mais compactos
 * 
 * 3. AULAS DE HOJE:
 *    - Tabela refinada com tipografia melhorada
 *    - Hora em linha única (18:00 – 18:50)
 *    - Badges pequenos para Tipo (TEOR/PRAT) e Status (PENDENTE/CONCLUÍDA)
 *    - Disciplina/Turma em duas linhas (disciplina forte, turma menor)
 *    - Botões de ação menores com ícones e tooltips
 *    - Linhas com chamada concluída destacadas (fundo verde claro)
 * 
 * 4. AVISOS:
 *    - Card separado com menos destaque visual
 *    - Lista compacta das últimas 3 notificações
 * 
 * 5. PRÓXIMAS AULAS (7 dias):
 *    - Compactada para mostrar apenas 2-3 primeiros dias
 *    - Lista agrupada por data com resumo (primeiras 2 aulas por dia)
 *    - Link "Ver todas as aulas" no cabeçalho
 * 
 * ARQUIVOS AFETADOS:
 * - instrutor/dashboard.php (apenas HTML/CSS reorganizado, lógica PHP mantida)
 * 
 * OBSERVAÇÕES:
 * - Todas as rotas e URLs foram preservadas (origem=instrutor, etc.)
 * - Queries SQL e regras de negócio não foram alteradas
 * - Verificação de chamada registrada adicionada para aulas teóricas (consulta turma_presencas)
 * - CSS Grid usado para layout 2 colunas no desktop (grid-template-columns: 2fr 1.2fr)
 * - Responsividade mantida: mobile empilha tudo em coluna única
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/services/SistemaNotificacoes.php';

// Verificar autenticação
$user = getCurrentUser();
if (!$user || $user['tipo'] !== 'instrutor') {
    // FASE 2 - Correção: Usar BASE_PATH dinamicamente
    $basePath = defined('BASE_PATH') ? BASE_PATH : '';
    header('Location: ' . $basePath . '/login.php');
    exit();
}

$db = db();
$notificacoes = new SistemaNotificacoes();

// Verificar se precisa trocar senha - se sim, forçar redirecionamento
// Esta verificação deve estar em TODAS as páginas do instrutor
try {
    $checkColumn = $db->fetch("SHOW COLUMNS FROM usuarios LIKE 'precisa_trocar_senha'");
    if ($checkColumn) {
        $usuarioCompleto = $db->fetch("SELECT precisa_trocar_senha FROM usuarios WHERE id = ?", [$user['id']]);
        if ($usuarioCompleto && isset($usuarioCompleto['precisa_trocar_senha']) && $usuarioCompleto['precisa_trocar_senha'] == 1) {
            // Verificar se não está já na página de trocar senha
            $currentPage = basename($_SERVER['PHP_SELF']);
            if ($currentPage !== 'trocar-senha.php') {
                $basePath = defined('BASE_PATH') ? BASE_PATH : '';
                header('Location: ' . $basePath . '/instrutor/trocar-senha.php?forcado=1');
                exit();
            }
        }
    }
} catch (Exception $e) {
    // Se houver erro, continuar normalmente
    if (defined('LOG_ENABLED') && LOG_ENABLED) {
        error_log('Erro ao verificar precisa_trocar_senha: ' . $e->getMessage());
    }
}

// Buscar dados do instrutor
// A tabela instrutores tem usuario_id que referencia usuarios.id
$instrutor = $db->fetch("
    SELECT i.*, u.nome as nome_usuario, u.email as email_usuario 
    FROM instrutores i 
    LEFT JOIN usuarios u ON i.usuario_id = u.id 
    WHERE i.usuario_id = ?
", [$user['id']]);

// Se não encontrar na tabela instrutores, usar dados do usuário diretamente
if (!$instrutor) {
    $instrutor = [
        'id' => null,
        'usuario_id' => $user['id'],
        'nome' => $user['nome'] ?? 'Instrutor',
        'nome_usuario' => $user['nome'] ?? 'Instrutor',
        'email_usuario' => $user['email'] ?? '',
        'credencial' => null,
        'cfc_id' => null
    ];
}

// Garantir que temos um nome para exibir
$instrutor['nome'] = $instrutor['nome'] ?? $instrutor['nome_usuario'] ?? $user['nome'] ?? 'Instrutor';

// Obter o ID do instrutor (da tabela instrutores) para usar nas queries de aulas
// Se não tiver registro na tabela instrutores, não terá aulas
$instrutorId = $instrutor['id'] ?? null;

// FASE INSTRUTOR - AULAS TEORICAS - Buscar aulas do dia (práticas + teóricas)
$hoje = date('Y-m-d');
$aulasHoje = [];
if ($instrutorId) {
    // Buscar aulas práticas do dia
    $aulasPraticasHoje = $db->fetchAll("
        SELECT a.*, 
               al.nome as aluno_nome, al.telefone as aluno_telefone,
               v.modelo as veiculo_modelo, v.placa as veiculo_placa,
               'pratica' as tipo_aula
        FROM aulas a
        JOIN alunos al ON a.aluno_id = al.id
        LEFT JOIN veiculos v ON a.veiculo_id = v.id
        WHERE a.instrutor_id = ? 
          AND a.data_aula = ?
          AND a.status != 'cancelada'
        ORDER BY a.hora_inicio ASC
    ", [$instrutorId, $hoje]);
    
    // FASE INSTRUTOR - AULAS TEORICAS - Buscar aulas teóricas do dia
    $aulasTeoricasHoje = $db->fetchAll("
        SELECT 
            taa.id,
            taa.turma_id,
            taa.disciplina,
            taa.nome_aula,
            taa.data_aula,
            taa.hora_inicio,
            taa.hora_fim,
            taa.status,
            taa.observacoes,
            taa.sala_id,
            tt.nome as turma_nome,
            s.nome as sala_nome,
            'teorica' as tipo_aula,
            NULL as aluno_nome,
            NULL as aluno_telefone,
            NULL as veiculo_modelo,
            NULL as veiculo_placa
        FROM turma_aulas_agendadas taa
        JOIN turmas_teoricas tt ON taa.turma_id = tt.id
        LEFT JOIN salas s ON taa.sala_id = s.id
        WHERE taa.instrutor_id = ?
          AND taa.data_aula = ?
          AND taa.status != 'cancelada'
        ORDER BY taa.hora_inicio ASC
    ", [$instrutorId, $hoje]);
    
    // Combinar aulas práticas e teóricas
    $aulasHoje = array_merge($aulasPraticasHoje, $aulasTeoricasHoje);
    
    // Ordenar por horário
    usort($aulasHoje, function($a, $b) {
        $horaA = $a['hora_inicio'] ?? '00:00:00';
        $horaB = $b['hora_inicio'] ?? '00:00:00';
        return strcmp($horaA, $horaB);
    });
    
    // REFACTOR DASHBOARD INSTRUTOR - Extrair próxima aula (primeira do dia)
    $proximaAula = !empty($aulasHoje) ? $aulasHoje[0] : null;
    
    // Verificar se a próxima aula (teórica) tem chamada registrada
    if ($proximaAula && $proximaAula['tipo_aula'] === 'teorica' && isset($proximaAula['id'])) {
        try {
            $presencasCount = $db->fetch("
                SELECT COUNT(*) as total
                FROM turma_presencas
                WHERE aula_id = ? AND turma_id = ?
            ", [$proximaAula['id'], $proximaAula['turma_id'] ?? 0]);
            $proximaAula['chamada_registrada'] = ($presencasCount['total'] ?? 0) > 0;
        } catch (Exception $e) {
            $proximaAula['chamada_registrada'] = false;
        }
    } else {
        if ($proximaAula) {
            $proximaAula['chamada_registrada'] = false; // Aulas práticas não têm chamada teórica
        }
    }
}

// FASE INSTRUTOR - AULAS TEORICAS - Buscar próximas aulas (próximos 7 dias) - práticas + teóricas
$proximasAulas = [];
if ($instrutorId) {
    // Buscar aulas práticas dos próximos 7 dias
    $aulasPraticasProximas = $db->fetchAll("
        SELECT a.*, 
               al.nome as aluno_nome, al.telefone as aluno_telefone,
               v.modelo as veiculo_modelo, v.placa as veiculo_placa,
               'pratica' as tipo_aula
        FROM aulas a
        JOIN alunos al ON a.aluno_id = al.id
        LEFT JOIN veiculos v ON a.veiculo_id = v.id
        WHERE a.instrutor_id = ? 
          AND a.data_aula > ?
          AND a.data_aula <= DATE_ADD(?, INTERVAL 7 DAY)
          AND a.status != 'cancelada'
        ORDER BY a.data_aula ASC, a.hora_inicio ASC
        LIMIT 10
    ", [$instrutorId, $hoje, $hoje]);
    
    // FASE INSTRUTOR - AULAS TEORICAS - Buscar aulas teóricas dos próximos 7 dias
    $aulasTeoricasProximas = $db->fetchAll("
        SELECT 
            taa.id,
            taa.turma_id,
            taa.disciplina,
            taa.nome_aula,
            taa.data_aula,
            taa.hora_inicio,
            taa.hora_fim,
            taa.status,
            taa.observacoes,
            taa.sala_id,
            tt.nome as turma_nome,
            s.nome as sala_nome,
            'teorica' as tipo_aula,
            NULL as aluno_nome,
            NULL as aluno_telefone,
            NULL as veiculo_modelo,
            NULL as veiculo_placa
        FROM turma_aulas_agendadas taa
        JOIN turmas_teoricas tt ON taa.turma_id = tt.id
        LEFT JOIN salas s ON taa.sala_id = s.id
        WHERE taa.instrutor_id = ?
          AND taa.data_aula > ?
          AND taa.data_aula <= DATE_ADD(?, INTERVAL 7 DAY)
          AND taa.status != 'cancelada'
        ORDER BY taa.data_aula ASC, taa.hora_inicio ASC
        LIMIT 10
    ", [$instrutorId, $hoje, $hoje]);
    
    // Combinar aulas práticas e teóricas
    $proximasAulas = array_merge($aulasPraticasProximas, $aulasTeoricasProximas);
    
    // Ordenar por data e horário
    usort($proximasAulas, function($a, $b) {
        $dataA = $a['data_aula'] . ' ' . ($a['hora_inicio'] ?? '00:00:00');
        $dataB = $b['data_aula'] . ' ' . ($b['hora_inicio'] ?? '00:00:00');
        return strtotime($dataA) - strtotime($dataB);
    });
    
    // Limitar a 10 no total
    $proximasAulas = array_slice($proximasAulas, 0, 10);
}

// Buscar notificações não lidas
$notificacoesNaoLidas = $notificacoes->buscarNotificacoesNaoLidas($user['id'], 'instrutor');

// FASE 1 - PRESENCA TEORICA - Buscar turmas teóricas do instrutor
// Arquivo: instrutor/dashboard.php (linha ~117)
// CORREÇÃO: turmas_teoricas não tem instrutor_id diretamente - o instrutor está em turma_aulas_agendadas
$turmasTeoricas = [];
$turmasTeoricasInstrutor = [];
if ($instrutorId) {
    // Buscar todas as turmas teóricas do instrutor (não apenas do dia)
    // O instrutor está associado às aulas agendadas, não diretamente à turma
    $turmasTeoricasInstrutor = $db->fetchAll("
        SELECT 
            tt.id,
            tt.nome,
            tt.curso_tipo,
            tt.data_inicio,
            tt.data_fim,
            tt.status,
            COUNT(DISTINCT tm.id) as total_alunos,
            COUNT(DISTINCT CASE WHEN taa.data_aula >= CURDATE() AND taa.status = 'agendada' THEN taa.id END) as proximas_aulas
        FROM turmas_teoricas tt
        INNER JOIN turma_aulas_agendadas taa_instrutor ON tt.id = taa_instrutor.turma_id 
            AND taa_instrutor.instrutor_id = ?
        LEFT JOIN turma_matriculas tm ON tt.id = tm.turma_id 
            AND tm.status IN ('matriculado', 'cursando', 'concluido')
        LEFT JOIN turma_aulas_agendadas taa ON tt.id = taa.turma_id
        WHERE tt.status IN ('ativa', 'completa', 'cursando', 'concluida')
        GROUP BY tt.id
        ORDER BY tt.data_inicio DESC, tt.nome ASC
        LIMIT 10
    ", [$instrutorId]);
    
    // Buscar próxima aula teórica de cada turma (para link rápido)
    foreach ($turmasTeoricasInstrutor as &$turma) {
        $proximaAula = $db->fetch("
            SELECT id, data_aula, hora_inicio
            FROM turma_aulas_agendadas
            WHERE turma_id = ? 
              AND data_aula >= CURDATE()
              AND status = 'agendada'
            ORDER BY data_aula ASC, hora_inicio ASC
            LIMIT 1
        ", [$turma['id']]);
        $turma['proxima_aula'] = $proximaAula;
    }
    unset($turma);
}

// FASE INSTRUTOR - AULAS TEORICAS - Estatísticas do dia (práticas + teóricas)
// Inicializar com valores padrão para evitar erros quando não há registro na tabela instrutores
$statsHoje = [
    'total_aulas' => 0,
    'agendadas' => 0,
    'confirmadas' => 0,
    'concluidas' => 0
];

if ($instrutorId) {
    // Estatísticas de aulas práticas
    $statsPraticas = $db->fetch("
        SELECT 
            COUNT(*) as total_aulas,
            SUM(CASE WHEN status = 'agendada' THEN 1 ELSE 0 END) as agendadas,
            SUM(CASE WHEN status = 'confirmada' THEN 1 ELSE 0 END) as confirmadas,
            SUM(CASE WHEN status = 'concluida' THEN 1 ELSE 0 END) as concluidas
        FROM aulas 
        WHERE instrutor_id = ? AND data_aula = ? AND status != 'cancelada'
    ", [$instrutorId, $hoje]);
    
    // FASE INSTRUTOR - AULAS TEORICAS - Estatísticas de aulas teóricas
    $statsTeoricas = $db->fetch("
        SELECT 
            COUNT(*) as total_aulas,
            SUM(CASE WHEN status = 'agendada' THEN 1 ELSE 0 END) as agendadas,
            0 as confirmadas, -- Aulas teóricas não têm status 'confirmada'
            SUM(CASE WHEN status = 'realizada' THEN 1 ELSE 0 END) as concluidas
        FROM turma_aulas_agendadas 
        WHERE instrutor_id = ? AND data_aula = ? AND status != 'cancelada'
    ", [$instrutorId, $hoje]);
    
    // Combinar estatísticas
    $statsHoje = [
        'total_aulas' => (int)($statsPraticas['total_aulas'] ?? 0) + (int)($statsTeoricas['total_aulas'] ?? 0),
        'agendadas' => (int)($statsPraticas['agendadas'] ?? 0) + (int)($statsTeoricas['agendadas'] ?? 0),
        'confirmadas' => (int)($statsPraticas['confirmadas'] ?? 0) + (int)($statsTeoricas['confirmadas'] ?? 0),
        'concluidas' => (int)($statsPraticas['concluidas'] ?? 0) + (int)($statsTeoricas['concluidas'] ?? 0)
    ];
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($instrutor['nome'] ?? 'Instrutor'); ?></title>
    <link rel="stylesheet" href="../assets/css/mobile-first.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1>Olá, <?php echo htmlspecialchars($instrutor['nome'] ?? 'Instrutor'); ?>!</h1>
                <div class="subtitle">Gerencie suas aulas e turmas</div>
            </div>
            <!-- Dropdown de Usuário -->
            <div class="instrutor-profile-menu" style="position: relative;">
                <button class="instrutor-profile-button" id="instrutor-profile-button" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); border-radius: 8px; padding: 8px 12px; color: white; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                    <div class="instrutor-profile-avatar" style="width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.3); display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px;">
                        <?php 
                        $iniciais = strtoupper(substr($instrutor['nome'], 0, 1));
                        if (strpos($instrutor['nome'], ' ') !== false) {
                            $nomes = explode(' ', $instrutor['nome']);
                            $iniciais = strtoupper(substr($nomes[0], 0, 1) . substr(end($nomes), 0, 1));
                        }
                        echo htmlspecialchars($iniciais ?? 'IN');
                        ?>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: flex-start; text-align: left;">
                        <span style="font-size: 14px; font-weight: 600; line-height: 1.2;"><?php echo htmlspecialchars($instrutor['nome'] ?? 'Instrutor'); ?></span>
                        <span style="font-size: 12px; opacity: 0.9; line-height: 1.2;">Instrutor</span>
                    </div>
                    <i class="fas fa-chevron-down" style="font-size: 12px; margin-left: 4px;"></i>
                </button>
                <div class="instrutor-profile-dropdown" id="instrutor-profile-dropdown" style="display: none; position: absolute; top: 100%; right: 0; margin-top: 8px; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); min-width: 200px; z-index: 1000;">
                    <a href="perfil.php" class="instrutor-dropdown-item" style="display: flex; align-items: center; padding: 12px 16px; color: #333; text-decoration: none; border-bottom: 1px solid #f0f0f0;">
                        <i class="fas fa-user" style="width: 20px; margin-right: 12px; color: #666;"></i>
                        Meu Perfil
                    </a>
                    <a href="trocar-senha.php" class="instrutor-dropdown-item" style="display: flex; align-items: center; padding: 12px 16px; color: #333; text-decoration: none; border-bottom: 1px solid #f0f0f0;">
                        <i class="fas fa-key" style="width: 20px; margin-right: 12px; color: #666;"></i>
                        Trocar senha
                    </a>
                    <a href="../admin/logout.php" class="instrutor-dropdown-item" style="display: flex; align-items: center; padding: 12px 16px; color: #e74c3c; text-decoration: none;">
                        <i class="fas fa-sign-out-alt" style="width: 20px; margin-right: 12px;"></i>
                        Sair
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Layout refinado: Container principal centralizado -->
    <div class="container my-4 instrutor-dashboard">
        <!-- PRIMEIRA SEÇÃO: Visão geral do dia (2 colunas) -->
        <div class="row g-4 mb-4">
            <!-- Coluna Esquerda: Próxima Aula (2/3 da largura) -->
            <div class="col-lg-8 col-md-7 mb-3 mb-md-0">
                <?php if ($proximaAula): ?>
                <!-- Ajuste visual: Card Próxima Aula - hierarquia e espaçamentos -->
                <div class="card border-primary shadow-sm h-100">
                    <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-light text-primary me-2" style="font-size: 0.7rem; font-weight: 600;">PRÓXIMA AULA</span>
                            <strong style="font-size: 1.3rem; font-weight: 700;"><?php echo date('H:i', strtotime($proximaAula['hora_inicio'])); ?> - <?php echo date('H:i', strtotime($proximaAula['hora_fim'])); ?></strong>
                        </div>
                        <span class="badge bg-<?php echo $proximaAula['tipo_aula'] === 'teorica' ? 'info' : 'success'; ?>" style="font-size: 0.75rem; opacity: 0.9; font-weight: 500;">
                            <?php echo strtoupper($proximaAula['tipo_aula']); ?>
                        </span>
                    </div>
                    <div class="card-body py-3">
                        <?php if ($proximaAula['tipo_aula'] === 'teorica'): ?>
                            <div class="mb-2">
                                <strong><?php echo htmlspecialchars((string)($proximaAula['turma_nome'] ?? '')); ?></strong>
                            </div>
                            <?php 
                            $disciplinas = [
                                'legislacao_transito' => 'Legislação de Trânsito',
                                'direcao_defensiva' => 'Direção Defensiva',
                                'primeiros_socorros' => 'Primeiros Socorros',
                                'meio_ambiente_cidadania' => 'Meio Ambiente e Cidadania',
                                'mecanica_basica' => 'Mecânica Básica'
                            ];
                            if (!empty($proximaAula['disciplina'])): ?>
                            <div class="mb-1 text-muted small">
                                <?php echo htmlspecialchars((string)($disciplinas[$proximaAula['disciplina'] ?? ''] ?? ucfirst(str_replace('_', ' ', (string)($proximaAula['disciplina'] ?? ''))))); ?>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($proximaAula['sala_nome'])): ?>
                            <!-- Ajuste visual: Espaçamento correto entre ícone e texto da Sala -->
                            <div class="mb-2 text-muted small">
                                <i class="fas fa-door-open me-2"></i><?php echo htmlspecialchars((string)($proximaAula['sala_nome'] ?? '')); ?>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="mb-2">
                                <strong><?php echo htmlspecialchars($proximaAula['aluno_nome'] ?? 'Aluno não informado'); ?></strong>
                            </div>
                            <?php if (!empty($proximaAula['veiculo_modelo'])): ?>
                            <div class="mb-2 text-muted small">
                                <i class="fas fa-car me-1"></i><?php echo htmlspecialchars($proximaAula['veiculo_modelo'] ?? ''); ?> - <?php echo htmlspecialchars($proximaAula['veiculo_placa'] ?? ''); ?>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <!-- Estado de chamada -->
                        <div class="mb-3">
                            <?php if ($proximaAula['tipo_aula'] === 'teorica'): ?>
                                <?php if ($proximaAula['chamada_registrada'] ?? false): ?>
                                    <span class="badge bg-success-subtle text-success border border-success">
                                        <i class="fas fa-check-circle me-1"></i>Chamada concluída
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning">
                                        <i class="fas fa-exclamation-circle me-1"></i>Chamada pendente para esta aula
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?php echo ucfirst($proximaAula['status'] ?? 'Agendada'); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Ajuste visual: Botões com largura mínima confortável e espaçamento -->
                        <div class="d-flex gap-2">
                            <?php if ($proximaAula['tipo_aula'] === 'teorica'): ?>
                            <?php 
                            $baseAdmin = (defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '') . '/admin/index.php';
                            $turmaIdAula = (int)($proximaAula['turma_id'] ?? 0);
                            $aulaIdAula = (int)($proximaAula['id'] ?? 0);
                            $urlChamada = $baseAdmin . '?page=turma-chamada&turma_id=' . $turmaIdAula . '&aula_id=' . $aulaIdAula . '&origem=instrutor';
                            $urlDiario = $baseAdmin . '?page=turma-diario&turma_id=' . $turmaIdAula . '&aula_id=' . $aulaIdAula . '&origem=instrutor';
                            ?>
                            <a href="<?php echo htmlspecialchars($urlChamada); ?>" class="btn btn-primary flex-fill" style="min-height: 44px;">
                                <i class="fas fa-clipboard-list me-2"></i>Chamada
                            </a>
                            <a href="<?php echo htmlspecialchars($urlDiario); ?>" class="btn btn-secondary flex-fill" style="min-height: 44px;">
                                <i class="fas fa-book me-2"></i>Diário
                            </a>
                            <?php else: ?>
                            <button class="btn btn-warning flex-fill solicitar-transferencia" 
                                    data-aula-id="<?php echo $proximaAula['id']; ?>"
                                    data-data="<?php echo $proximaAula['data_aula']; ?>"
                                    data-hora="<?php echo $proximaAula['hora_inicio']; ?>">
                                <i class="fas fa-exchange-alt me-2"></i>Transferir
                            </button>
                            <button class="btn btn-danger flex-fill cancelar-aula" 
                                    data-aula-id="<?php echo $proximaAula['id']; ?>"
                                    data-data="<?php echo $proximaAula['data_aula']; ?>"
                                    data-hora="<?php echo $proximaAula['hora_inicio']; ?>">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="card h-100">
                    <div class="card-body text-center py-4">
                        <i class="fas fa-calendar-times fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-2">Você não possui próximas aulas agendadas</p>
                        <a href="aulas.php" class="btn btn-sm btn-outline-primary">Ver todas as aulas</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Coluna Direita: Resumo + Pendências (1/3 da largura) -->
            <div class="col-lg-4 col-md-5">
                <!-- RESUMO DE HOJE - NOVO LAYOUT (Bootstrap 4) -->
                <div class="card mb-4 instrutor-resumo-hoje">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-chart-bar me-2"></i>Resumo de Hoje</span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Aulas hoje -->
                            <div class="col-12 col-md-4 mb-3 mb-md-0">
                                <div class="resumo-card card h-100 text-center py-3">
                                    <i class="fas fa-calendar-alt text-primary mb-2 resumo-icon"></i>
                                    <div class="resumo-valor text-primary">
                                        <?php echo $statsHoje['total_aulas']; ?>
                                    </div>
                                    <div class="resumo-label">Aulas hoje</div>
                                </div>
                            </div>
                            <!-- Pendentes -->
                            <div class="col-12 col-md-4 mb-3 mb-md-0">
                                <div class="resumo-card card h-100 text-center py-3">
                                    <i class="fas fa-exclamation-circle text-warning mb-2 resumo-icon"></i>
                                    <div class="resumo-valor text-warning">
                                        <?php echo $statsHoje['agendadas']; ?>
                                    </div>
                                    <div class="resumo-label">Pendentes</div>
                                </div>
                            </div>
                            <!-- Concluídas -->
                            <div class="col-12 col-md-4">
                                <div class="resumo-card card h-100 text-center py-3">
                                    <i class="fas fa-check-circle text-success mb-2 resumo-icon"></i>
                                    <div class="resumo-valor text-success">
                                        <?php echo $statsHoje['concluidas']; ?>
                                    </div>
                                    <div class="resumo-label">Concluídas</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- FIM RESUMO DE HOJE -->
                
                <!-- Card Pendências - mais compacto -->
                <div class="card">
                    <div class="card-header bg-white border-0 pb-0">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>Pendências
                        </h5>
                    </div>
                    <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-4">
                        <?php 
                        // TODO: Implementar lógica para contar aulas anteriores sem chamada
                        $aulasSemChamada = 0; // Placeholder
                        ?>
                        <?php if ($aulasSemChamada === 0): ?>
                        <div class="pendencias-status-icon mb-2">
                            <i class="fas fa-check-circle text-success" style="font-size: 2rem;"></i>
                        </div>
                        <p class="mb-0 text-muted small">Todas as chamadas estão em dia</p>
                        <?php else: ?>
                        <div class="alert alert-warning py-2 mb-0 w-100">
                            <strong><?php echo $aulasSemChamada; ?></strong> aula(s) anteriores sem chamada
                            <br><a href="aulas.php?filtro=pendentes" class="small">Ver lista</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ACOES RAPIDAS - NOVO LAYOUT (Bootstrap 4) -->
        <div class="card mb-4 instrutor-acoes-rapidas">
            <div class="card-header">
                <i class="fas fa-bolt me-2"></i>Ações Rápidas
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12 col-sm-6 col-lg-3 mb-3 mb-lg-0">
                        <button class="btn btn-primary w-100 btn-acao-rapida" onclick="verTodasAulas()">
                            <i class="fas fa-list me-2"></i>Ver Todas as Aulas
                        </button>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3 mb-3 mb-lg-0">
                        <button class="btn btn-secondary w-100 btn-acao-rapida" onclick="verNotificacoes()">
                            <i class="fas fa-bell me-2"></i>Central de Avisos
                        </button>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3 mb-3 mb-sm-0">
                        <button class="btn btn-outline-primary w-100 btn-acao-rapida" onclick="registrarOcorrencia()">
                            <i class="fas fa-exclamation-triangle me-2"></i>Registrar Ocorrência
                        </button>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <button class="btn btn-outline-secondary w-100 btn-acao-rapida" onclick="contatarSecretaria()">
                            <i class="fas fa-phone me-2"></i>Contatar Secretaria
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- FIM ACOES RAPIDAS -->

        <!-- AULAS DE HOJE - NOVO LAYOUT -->
        <div class="card mb-4 dashboard-aulas-hoje">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calendar-day me-2"></i>Aulas de Hoje
                </h5>
                <span class="text-muted small">
                    <?php echo count($aulasHoje); ?> aula(s) agendada(s) hoje
                </span>
            </div>
            <div class="card-body">
                <?php if (empty($aulasHoje)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-calendar-times fa-2x text-muted mb-2"></i>
                    <p class="text-muted mb-0">Você não possui aulas agendadas para hoje.</p>
                </div>
                <?php else: ?>
                <!-- Ajuste visual: Tabela responsiva com fonte compacta e mais espaçamento -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 instructor-aulas-table">
                        <thead class="table-light">
                            <tr>
                                <th class="text-nowrap" style="width: 100px;">Hora</th>
                                <th class="text-nowrap" style="width: 70px;">Tipo</th>
                                <th style="width: 50%;">Disciplina / Turma</th>
                                <th class="text-nowrap" style="width: 100px;">Sala</th>
                                <th class="text-nowrap" style="width: 100px;">Status</th>
                                <th style="width: 100px;" class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                                <?php 
                                $disciplinas = [
                                    'legislacao_transito' => 'Legislação de Trânsito',
                                    'direcao_defensiva' => 'Direção Defensiva',
                                    'primeiros_socorros' => 'Primeiros Socorros',
                                    'meio_ambiente_cidadania' => 'Meio Ambiente e Cidadania',
                                    'mecanica_basica' => 'Mecânica Básica'
                                ];
                                foreach ($aulasHoje as $aula): 
                                    // Verificar se tem chamada registrada (apenas para teóricas)
                                    $chamadaRegistrada = false;
                                    if ($aula['tipo_aula'] === 'teorica' && isset($aula['id'])) {
                                        try {
                                            $presencasCount = $db->fetch("
                                                SELECT COUNT(*) as total
                                                FROM turma_presencas
                                                WHERE aula_id = ? AND turma_id = ?
                                            ", [$aula['id'], $aula['turma_id'] ?? 0]);
                                            $chamadaRegistrada = ($presencasCount['total'] ?? 0) > 0;
                                        } catch (Exception $e) {
                                            $chamadaRegistrada = false;
                                        }
                                    }
                                    
                                    $baseAdmin = (defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '') . '/admin/index.php';
                                    $turmaIdAula = (int)($aula['turma_id'] ?? 0);
                                    $aulaIdAula = (int)($aula['id'] ?? 0);
                                    $urlChamada = $baseAdmin . '?page=turma-chamada&turma_id=' . $turmaIdAula . '&aula_id=' . $aulaIdAula . '&origem=instrutor';
                                    $urlDiario = $baseAdmin . '?page=turma-diario&turma_id=' . $turmaIdAula . '&aula_id=' . $aulaIdAula . '&origem=instrutor';
                                ?>
                                <!-- Ajuste visual: Hierarquia tipográfica da tabela de aulas de hoje -->
                                <tr class="<?php echo $chamadaRegistrada ? 'table-success' : ''; ?>">
                                    <td class="text-nowrap py-3">
                                        <strong style="font-size: 0.95rem; font-weight: 600;"><?php echo date('H:i', strtotime($aula['hora_inicio'])); ?> – <?php echo date('H:i', strtotime($aula['hora_fim'])); ?></strong>
                                    </td>
                                    <td class="text-nowrap py-3">
                                        <span class="badge bg-light text-dark badge-pill">
                                            <?php echo $aula['tipo_aula'] === 'teorica' ? 'TEOR' : 'PRAT'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($aula['tipo_aula'] === 'teorica'): ?>
                                            <div class="fw-bold" style="font-size: 0.875rem; line-height: 1.3;">
                                                <?php echo htmlspecialchars((string)($disciplinas[$aula['disciplina'] ?? ''] ?? ucfirst(str_replace('_', ' ', (string)($aula['disciplina'] ?? '')) ?? 'Disciplina'))); ?>
                                            </div>
                                            <div class="text-muted small" style="font-size: 0.75rem; line-height: 1.2;">
                                                <?php echo htmlspecialchars((string)($aula['turma_nome'] ?? '')); ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="fw-bold" style="font-size: 0.875rem; line-height: 1.3;">
                                                <?php echo htmlspecialchars($aula['aluno_nome'] ?? 'Aluno não informado'); ?>
                                            </div>
                                            <?php if (!empty($aula['veiculo_modelo'])): ?>
                                            <div class="text-muted small" style="font-size: 0.75rem; line-height: 1.2;">
                                                <?php echo htmlspecialchars($aula['veiculo_modelo'] ?? ''); ?>
                                            </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($aula['tipo_aula'] === 'teorica'): ?>
                                            <span class="small" style="font-size: 0.8rem;"><?php echo htmlspecialchars((string)($aula['sala_nome'] ?? '-')); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small" style="font-size: 0.8rem;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($aula['tipo_aula'] === 'teorica'): ?>
                                            <span class="badge <?php echo $chamadaRegistrada ? 'bg-success-subtle text-success border border-success' : 'bg-warning-subtle text-warning border border-warning'; ?>" style="font-size: 0.7rem; padding: 0.3rem 0.5rem; font-weight: 500;">
                                                <?php echo $chamadaRegistrada ? 'CONCLUÍDA' : 'PENDENTE'; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.7rem; padding: 0.3rem 0.5rem; font-weight: 500;">
                                                <?php echo strtoupper($aula['status'] ?? 'AGENDADA'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <?php if ($aula['tipo_aula'] === 'teorica'): ?>
                                            <a href="<?php echo htmlspecialchars($urlChamada); ?>" 
                                               class="btn btn-primary btn-sm" 
                                               style="padding: 0.35rem 0.6rem; min-width: 38px;"
                                               title="Abrir chamada"
                                               data-bs-toggle="tooltip">
                                                <i class="fas fa-clipboard-list"></i>
                                            </a>
                                            <a href="<?php echo htmlspecialchars($urlDiario); ?>" 
                                               class="btn btn-secondary btn-sm" 
                                               style="padding: 0.35rem 0.6rem; min-width: 38px;"
                                               title="Abrir diário"
                                               data-bs-toggle="tooltip">
                                                <i class="fas fa-book"></i>
                                            </a>
                                            <?php else: ?>
                                            <button class="btn btn-warning btn-sm solicitar-transferencia" 
                                                    style="padding: 0.35rem 0.6rem; min-width: 38px;"
                                                    data-aula-id="<?php echo $aula['id']; ?>"
                                                    data-data="<?php echo $aula['data_aula']; ?>"
                                                    data-hora="<?php echo $aula['hora_inicio']; ?>"
                                                    title="Transferir"
                                                    data-bs-toggle="tooltip">
                                                <i class="fas fa-exchange-alt"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm cancelar-aula" 
                                                    style="padding: 0.35rem 0.6rem; min-width: 38px;"
                                                    data-aula-id="<?php echo $aula['id']; ?>"
                                                    data-data="<?php echo $aula['data_aula']; ?>"
                                                    data-hora="<?php echo $aula['hora_inicio']; ?>"
                                                    title="Cancelar"
                                                    data-bs-toggle="tooltip">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
        
        <!-- QUARTA SEÇÃO: Avisos (full width) -->
        <div class="card mb-4">
            <div class="card-header py-2">
                <h6 class="card-title mb-0" style="font-size: 0.9rem; font-weight: 600;">
                    <i class="fas fa-bell me-2"></i>Avisos
                </h6>
            </div>
            <div class="card-body py-2">
                <?php if (!empty($notificacoesNaoLidas)): ?>
                <div class="list-group list-group-flush">
                    <?php foreach (array_slice($notificacoesNaoLidas, 0, 3) as $notificacao): ?>
                    <div class="list-group-item px-0 py-2 border-0 border-bottom">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1" style="font-size: 0.9rem;">
                                <?php echo htmlspecialchars((string)($notificacao['titulo'] ?? '')); ?>
                            </h6>
                        </div>
                        <p class="mb-1 small text-muted" style="font-size: 0.8rem;">
                            <?php echo htmlspecialchars((string)($notificacao['mensagem'] ?? '')); ?>
                        </p>
                        <small class="text-muted">
                            <?php echo date('d/m/Y H:i', strtotime($notificacao['criado_em'])); ?>
                        </small>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted mb-0 text-center py-2">
                    <i class="fas fa-check-circle text-success me-1"></i>
                    Você não possui avisos novos.
                </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- FASE 1 - PRESENCA TEORICA - Minhas Turmas Teóricas -->
        <?php if (!empty($turmasTeoricasInstrutor)): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-users-class"></i>
                    Minhas Turmas Teóricas
                </h2>
            </div>
            <div class="turma-list">
                <?php foreach ($turmasTeoricasInstrutor as $turma): ?>
                    <?php 
                    $statusLabel = [
                        'ativa' => 'Ativa',
                        'completa' => 'Completa',
                        'cursando' => 'Cursando',
                        'concluida' => 'Concluída',
                        'cancelada' => 'Cancelada'
                    ][$turma['status']] ?? ucfirst($turma['status']);
                    
                    $statusClass = [
                        'ativa' => 'success',
                        'completa' => 'info',
                        'cursando' => 'primary',
                        'concluida' => 'secondary',
                        'cancelada' => 'danger'
                    ][$turma['status']] ?? 'secondary';
                    
                    $nomesCursos = [
                        'formacao_45h' => 'Formação 45h',
                        'formacao_acc_20h' => 'Formação ACC 20h',
                        'reciclagem_infrator' => 'Reciclagem Infrator',
                        'atualizacao' => 'Atualização'
                    ];
                    ?>
                    <div class="turma-item" style="padding: 16px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 12px;">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div style="flex: 1;">
                                <h6 style="margin: 0 0 8px 0; font-weight: 600; color: #1e293b;">
                                    <?php echo htmlspecialchars((string)($turma['nome'] ?? '')); ?>
                                </h6>
                                <div style="font-size: 13px; color: #64748b; margin-bottom: 4px;">
                                    <i class="fas fa-graduation-cap me-1"></i>
                                    <?php echo htmlspecialchars((string)($nomesCursos[$turma['curso_tipo'] ?? ''] ?? $turma['curso_tipo'] ?? '')); ?>
                                </div>
                                <div style="font-size: 13px; color: #64748b; margin-bottom: 4px;">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?php echo date('d/m/Y', strtotime($turma['data_inicio'])); ?> - 
                                    <?php echo date('d/m/Y', strtotime($turma['data_fim'])); ?>
                                </div>
                                <div style="font-size: 12px; color: #94a3b8; margin-top: 4px;">
                                    <i class="fas fa-users me-1"></i>
                                    <?php echo $turma['total_alunos']; ?> aluno(s)
                                </div>
                            </div>
                            <div style="text-align: right; margin-left: 16px;">
                                <span class="badge bg-<?php echo $statusClass; ?>" style="margin-bottom: 8px; display: block;">
                                    <?php echo $statusLabel; ?>
                                </span>
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <a href="../admin/index.php?page=turmas-teoricas&acao=detalhes&turma_id=<?php echo $turma['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary" 
                                       style="text-decoration: none; font-size: 11px; padding: 4px 8px;">
                                        <i class="fas fa-eye me-1"></i>
                                        Detalhes
                                    </a>
                                    <?php if ($turma['proxima_aula']): ?>
                                    <?php 
                                    // AJUSTE INSTRUTOR - FLUXO CHAMADA/DIARIO - Link para chamada da próxima aula
                                    $baseAdmin = (defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '') . '/admin/index.php';
                                    $turmaIdTurma = (int)($turma['id'] ?? 0);
                                    $aulaIdTurma = (int)($turma['proxima_aula']['id'] ?? 0);
                                    $urlChamadaTurma = $baseAdmin . '?page=turma-chamada&turma_id=' . $turmaIdTurma . '&aula_id=' . $aulaIdTurma . '&origem=instrutor';
                                    ?>
                                    <a href="<?php echo htmlspecialchars($urlChamadaTurma); ?>" 
                                       class="btn btn-sm btn-primary" 
                                       style="text-decoration: none; font-size: 11px; padding: 4px 8px;">
                                        <i class="fas fa-clipboard-check me-1"></i>
                                        Chamada
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- QUINTA SEÇÃO: Próximas Aulas (7 dias) - Compacto (2-3 dias) -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <h6 class="card-title mb-0" style="font-size: 0.9rem; font-weight: 600;">
                    <i class="fas fa-calendar-alt me-2"></i>Próximas Aulas (7 dias)
                </h6>
                <a href="aulas.php?periodo=proximos_7_dias" class="btn btn-sm btn-outline-primary">
                    Ver todas as aulas
                </a>
            </div>
            <div class="card-body py-2">
                <?php if (empty($proximasAulas)): ?>
                <div class="text-center py-3">
                    <i class="fas fa-calendar-times fa-2x text-muted mb-2"></i>
                    <p class="text-muted mb-0 small">Nenhuma aula agendada para os próximos 7 dias.</p>
                </div>
                <?php else: ?>
                <?php 
                // REFACTOR DASHBOARD INSTRUTOR - Agrupar próximas aulas por data (limitar a 2-3 dias)
                $aulasPorData = [];
                foreach ($proximasAulas as $aula) {
                    $data = $aula['data_aula'];
                    if (!isset($aulasPorData[$data])) {
                        $aulasPorData[$data] = [];
                    }
                    $aulasPorData[$data][] = $aula;
                }
                // Limitar a 2-3 primeiras datas
                $aulasPorData = array_slice($aulasPorData, 0, 3, true);
                ?>
                <!-- Ajuste visual: Próximas Aulas - cada dia em card com borda leve e espaçamento melhorado -->
                <div class="d-flex flex-column gap-2 proximas-aulas-list">
                    <?php foreach ($aulasPorData as $data => $aulasDoDia): ?>
                    <div class="card border proximas-aulas-dia-card">
                        <div class="card-body py-2 px-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong style="font-size: 0.9rem; font-weight: 600;"><?php echo date('d/m/Y', strtotime($data)); ?></strong>
                                <span class="text-muted small" style="font-size: 0.75rem;">· <?php echo count($aulasDoDia); ?> aula(s)</span>
                            </div>
                            <div class="ms-2">
                                <?php foreach (array_slice($aulasDoDia, 0, 2) as $aula): ?>
                                <div class="d-flex align-items-center gap-2 mb-2 proximas-aulas-item">
                                    <span class="badge bg-<?php echo $aula['tipo_aula'] === 'teorica' ? 'info' : 'success'; ?>" style="font-size: 0.7rem; padding: 0.2rem 0.4rem;">
                                        <?php echo $aula['tipo_aula'] === 'teorica' ? 'TEOR' : 'PRAT'; ?>
                                    </span>
                                    <small style="font-size: 0.85rem;">
                                        <strong><?php echo date('H:i', strtotime($aula['hora_inicio'])); ?></strong>
                                        <?php if ($aula['tipo_aula'] === 'teorica'): ?>
                                            - <?php echo htmlspecialchars((string)($aula['turma_nome'] ?? '')); ?>
                                        <?php else: ?>
                                            - <?php echo htmlspecialchars($aula['aluno_nome'] ?? 'Aluno'); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <?php endforeach; ?>
                                <?php if (count($aulasDoDia) > 2): ?>
                                <div class="mt-2 proximas-aulas-extra">
                                    <small class="text-muted fst-italic" style="font-size: 0.75rem;">+<?php echo count($aulasDoDia) - 2; ?> aula(s) neste dia</small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal de Cancelamento/Transferência -->
    <div id="modalAcao" class="modal-overlay hidden">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitulo">Cancelar Aula</h3>
            </div>
            <div class="modal-body">
                <form id="formAcao">
                    <input type="hidden" id="aulaId" name="aula_id">
                    <input type="hidden" id="tipoAcao" name="tipo_acao">
                    
                    <div class="form-group">
                        <label class="form-label">Data da Aula</label>
                        <input type="text" id="dataAula" class="form-input" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Horário</label>
                        <input type="text" id="horaAula" class="form-input" readonly>
                    </div>
                    
                    <div class="form-group" id="novaDataGroup" style="display: none;">
                        <label class="form-label">Nova Data</label>
                        <input type="date" id="novaData" name="nova_data" class="form-input">
                    </div>
                    
                    <div class="form-group" id="novaHoraGroup" style="display: none;">
                        <label class="form-label">Novo Horário</label>
                        <input type="time" id="novaHora" name="nova_hora" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Motivo</label>
                        <select id="motivo" name="motivo" class="form-select">
                            <option value="">Selecione um motivo</option>
                            <option value="problema_saude">Problema de saúde</option>
                            <option value="imprevisto_pessoal">Imprevisto pessoal</option>
                            <option value="problema_veiculo">Problema com veículo</option>
                            <option value="ausencia_aluno">Ausência do aluno</option>
                            <option value="condicoes_climaticas">Condições climáticas</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Justificativa *</label>
                        <textarea id="justificativa" name="justificativa" class="form-textarea" 
                                  placeholder="Descreva o motivo da ação..." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Política:</strong> Ações devem ser feitas com no mínimo 24 horas de antecedência.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="fecharModal()">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="enviarAcao()">Confirmar</button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer"></div>

    <script>
        // Variáveis globais
        let modalAberto = false;

        // Funções de navegação
        function verTodasAulas() {
            window.location.href = 'aulas.php';
        }

        function verNotificacoes() {
            window.location.href = 'notificacoes.php';
        }

        function registrarOcorrencia() {
            window.location.href = 'ocorrencias.php';
        }

        function contatarSecretaria() {
            window.location.href = 'contato.php';
        }

        // Funções do modal
        // FASE 1 - Ajuste: Normalizar valores de tipo_acao para corresponder à API
        // Arquivo: instrutor/dashboard.php (linha ~561)
        function abrirModal(tipo, aulaId, data, hora) {
            // Normalizar tipo: 'cancelamento' ou 'transferencia' (API espera estes valores)
            const tipoNormalizado = tipo === 'cancelamento' ? 'cancelamento' : 
                                   tipo === 'transferencia' ? 'transferencia' : 
                                   tipo === 'cancelar' ? 'cancelamento' : 
                                   tipo === 'transferir' ? 'transferencia' : tipo;
            
            document.getElementById('tipoAcao').value = tipoNormalizado;
            document.getElementById('aulaId').value = aulaId;
            document.getElementById('dataAula').value = data;
            document.getElementById('horaAula').value = hora;
            
            const modal = document.getElementById('modalAcao');
            const titulo = document.getElementById('modalTitulo');
            const novaDataGroup = document.getElementById('novaDataGroup');
            const novaHoraGroup = document.getElementById('novaHoraGroup');
            
            if (tipoNormalizado === 'transferencia') {
                titulo.textContent = 'Solicitar Transferência';
                novaDataGroup.style.display = 'block';
                novaHoraGroup.style.display = 'block';
            } else {
                titulo.textContent = 'Cancelar Aula';
                novaDataGroup.style.display = 'none';
                novaHoraGroup.style.display = 'none';
            }
            
            modal.classList.remove('hidden');
            modalAberto = true;
        }

        function fecharModal() {
            document.getElementById('modalAcao').classList.add('hidden');
            document.getElementById('formAcao').reset();
            modalAberto = false;
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Botões de cancelamento
            // FASE 1 - Ajuste: Normalizar tipo para 'cancelamento' (API espera este valor)
            // Arquivo: instrutor/dashboard.php (linha ~595)
            document.querySelectorAll('.cancelar-aula').forEach(btn => {
                btn.addEventListener('click', function() {
                    const aulaId = this.dataset.aulaId;
                    const data = this.dataset.data;
                    const hora = this.dataset.hora;
                    abrirModal('cancelamento', aulaId, data, hora);
                });
            });

            // Botões de transferência
            // FASE 1 - Ajuste: Normalizar tipo para 'transferencia' (API espera este valor)
            // Arquivo: instrutor/dashboard.php (linha ~604)
            document.querySelectorAll('.solicitar-transferencia').forEach(btn => {
                btn.addEventListener('click', function() {
                    const aulaId = this.dataset.aulaId;
                    const data = this.dataset.data;
                    const hora = this.dataset.hora;
                    abrirModal('transferencia', aulaId, data, hora);
                });
            });

            // Botões de chamada e diário
            document.querySelectorAll('.fazer-chamada').forEach(btn => {
                btn.addEventListener('click', function() {
                    const aulaId = this.dataset.aulaId;
                    window.location.href = `chamada.php?aula_id=${aulaId}`;
                });
            });

            document.querySelectorAll('.fazer-diario').forEach(btn => {
                btn.addEventListener('click', function() {
                    const aulaId = this.dataset.aulaId;
                    window.location.href = `diario.php?aula_id=${aulaId}`;
                });
            });

            // Botões de marcar notificação como lida
            document.querySelectorAll('.marcar-lida').forEach(btn => {
                btn.addEventListener('click', function() {
                    const notificacaoId = this.dataset.id;
                    marcarNotificacaoComoLida(notificacaoId);
                });
            });

            // Fechar modal ao clicar fora
            document.getElementById('modalAcao').addEventListener('click', function(e) {
                if (e.target === this) {
                    fecharModal();
                }
            });
        });

        // Função para enviar ação
        async function enviarAcao() {
            const form = document.getElementById('formAcao');
            const formData = new FormData(form);
            
            // Validação básica
            if (!formData.get('justificativa').trim()) {
                mostrarToast('Por favor, preencha a justificativa.', 'error');
                return;
            }

            try {
                // FASE 1 - Alteração: Usar nova API específica para instrutores
                // Arquivo: instrutor/dashboard.php (linha ~657)
                // Antes: admin/api/solicitacoes.php (bloqueava instrutores)
                // Agora: admin/api/instrutor-aulas.php (específica para instrutores com validação de segurança)
                const response = await fetch('../admin/api/instrutor-aulas.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        aula_id: formData.get('aula_id'),
                        tipo_acao: formData.get('tipo_acao'), // Mudou de tipo_solicitacao para tipo_acao
                        nova_data: formData.get('nova_data'),
                        nova_hora: formData.get('nova_hora'),
                        motivo: formData.get('motivo'),
                        justificativa: formData.get('justificativa')
                    })
                });

                const result = await response.json();

                if (result.success) {
                    mostrarToast('Solicitação enviada com sucesso!', 'success');
                    fecharModal();
                    // Recarregar a página após um breve delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    mostrarToast(result.message || 'Erro ao enviar solicitação.', 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                mostrarToast('Erro de conexão. Tente novamente.', 'error');
            }
        }

        // Função para marcar notificação como lida
        async function marcarNotificacaoComoLida(notificacaoId) {
            try {
                const response = await fetch('../admin/api/notificacoes.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        notificacao_id: notificacaoId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // Remover a notificação da interface
                    const notificacaoItem = document.querySelector(`[data-id="${notificacaoId}"]`);
                    if (notificacaoItem) {
                        notificacaoItem.remove();
                    }
                    
                    // Atualizar contador de notificações
                    const badge = document.querySelector('.badge');
                    if (badge) {
                        const count = parseInt(badge.textContent) - 1;
                        if (count > 0) {
                            badge.textContent = count;
                        } else {
                            badge.parentElement.parentElement.remove();
                        }
                    }
                }
            } catch (error) {
                console.error('Erro:', error);
            }
        }

        // Função para mostrar toast
        function mostrarToast(mensagem, tipo = 'info') {
            const toastContainer = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast toast-${tipo}`;
            
            toast.innerHTML = `
                <div class="toast-header">
                    <i class="fas fa-${tipo === 'success' ? 'check-circle' : tipo === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                    <span class="toast-title">${tipo === 'success' ? 'Sucesso' : tipo === 'error' ? 'Erro' : 'Informação'}</span>
                </div>
                <div class="toast-message">${mensagem}</div>
            `;
            
            toastContainer.appendChild(toast);
            
            // Remover toast após 5 segundos
            setTimeout(() => {
                toast.remove();
            }, 5000);
        }

        // Prevenir envio do formulário com Enter
        document.getElementById('formAcao').addEventListener('submit', function(e) {
            e.preventDefault();
        });
    </script>

    <style>
        /* ============================================
           ESTILOS ESPECÍFICOS - DASHBOARD INSTRUTOR
           ============================================ */
        
        /* Container principal */
        .instrutor-dashboard .card {
            border-radius: 0.75rem;
        }
        
        .instrutor-dashboard .card-title {
            font-size: 1rem;
            font-weight: 600;
        }
        
        /* Wrapper geral do dashboard do instrutor */
        .instrutor-dashboard {
            padding-bottom: 2rem;
        }
        
        /* RESUMO DE HOJE - LAYOUT DOS CARDS (Bootstrap 4) */
        /* CORREÇÃO: Removidas classes row-cols-* (Bootstrap 5) que não existem no Bootstrap 4.
           Agora usando grid clássico: row + col-12 col-md-4 */
        .instrutor-dashboard .resumo-card {
            border-radius: 0.5rem;
            border: 1px solid #f0f0f0;
            background: #f8f9fa;
            box-shadow: 0 2px 4px rgba(0,0,0,0.03);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .instrutor-dashboard .resumo-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.08);
        }
        
        .instrutor-dashboard .resumo-card .resumo-icon {
            font-size: 1.4rem;
        }
        
        .instrutor-dashboard .resumo-card .resumo-valor {
            font-size: 1.6rem;
            font-weight: 600;
            line-height: 1.1;
        }
        
        .instrutor-dashboard .resumo-card .resumo-label {
            font-size: 0.9rem;
            color: #666;
        }
        
        /* ACOES RAPIDAS - Botões estilo card clicável */
        .instrutor-dashboard .btn-acao-rapida {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            min-height: 52px;
            border-radius: 0.5rem;
            font-weight: 500;
            text-align: center;
            white-space: normal;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .instrutor-dashboard .btn-acao-rapida:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.12);
        }
        
        /* Card Próxima Aula - horário destacado */
        .instructor-next-class-time {
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .instructor-next-class-status {
            font-size: 0.85rem;
        }
        
        /* Tabela de Aulas de Hoje - fonte compacta e espaçamento melhorado */
        .instructor-aulas-table {
            font-size: 0.9rem;
        }
        
        .instructor-aulas-table th {
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0.75rem 0.5rem;
        }
        
        .instructor-aulas-table td {
            padding: 0.75rem 0.5rem;
        }
        
        /* AULAS DE HOJE - Padding lateral confortável */
        .instrutor-dashboard .card-body {
            padding: 1.25rem;
        }
        
        .instrutor-dashboard .dashboard-aulas-hoje .card-body {
            padding: 1.25rem 1.5rem;
        }
        
        .instrutor-dashboard .dashboard-aulas-hoje table th,
        .instrutor-dashboard .dashboard-aulas-hoje table td {
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
            vertical-align: middle;
        }
        
        /* Próximas Aulas - espaçamento melhorado */
        .proximas-aulas-list {
            gap: 12px;
        }
        
        .proximas-aulas-dia-card {
            border-color: #e9ecef !important;
            background: white;
        }
        
        .proximas-aulas-item {
            line-height: 1.6;
        }
        
        .proximas-aulas-extra {
            margin-top: 8px;
        }
        
        /* Responsividade mobile - ajustes de padding e espaçamento */
        @media (max-width: 767.98px) {
            .instructor-dashboard-container {
                padding: 16px 10px 24px;
            }
            
            .instructor-dashboard-container .card {
                margin-bottom: 16px;
            }
            
            .instructor-dashboard-container .card-body {
                padding: 16px;
            }
            
            /* Ações Rápidas - grid 2x2 no mobile */
            .instructor-quick-actions .btn {
                width: 100%;
            }
        }
        
        /* Estilos específicos para o dashboard do instrutor */
        .notificacao-item {
            display: flex;
            align-items: center;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 8px;
            background: #f8fafc;
        }

        .notificacao-content {
            flex: 1;
        }

        .notificacao-content h4 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
            color: #1e293b;
        }

        .notificacao-content p {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 4px;
        }

        .notificacao-content small {
            font-size: 11px;
            color: #94a3b8;
        }

        .stat-item {
            text-align: center;
            padding: 16px;
            background: #f8fafc;
            border-radius: 8px;
        }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #2563eb;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 500;
        }

        .aula-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
            flex-wrap: wrap;
        }

        .aula-actions .btn {
            flex: 1;
            min-width: 120px;
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }

        .alert i {
            font-size: 16px;
        }

        @media (max-width: 480px) {
            .aula-actions {
                flex-direction: column;
            }
            
            .aula-actions .btn {
                width: 100%;
                min-width: auto;
            }
        }

        /* Responsividade mobile - ajustes de padding e espaçamento */
        @media (max-width: 767.98px) {
            .instructor-dashboard-container {
                padding: 16px 10px 24px;
            }
            
            .instructor-dashboard-container .card {
                margin-bottom: 16px;
            }
            
            .instructor-dashboard-container .card-body {
                padding: 16px;
            }
            
            /* Ações Rápidas - grid 2x2 no mobile */
            .instructor-quick-actions .btn {
                width: 100%;
            }
        }
        
        /* Badges com cores suaves (Bootstrap 5.3+) */
        .bg-success-subtle {
            background-color: #d1e7dd !important;
        }
        
        .bg-warning-subtle {
            background-color: #fff3cd !important;
        }
        
        .bg-secondary-subtle {
            background-color: #e2e3e5 !important;
        }
        
        /* Ajuste visual: Ações Rápidas - grid responsivo */
        .acoes-rapidas-grid {
            --bs-gutter-x: 0.5rem;
        }
        
        @media (min-width: 768px) {
            .acoes-rapidas-grid .col-md-auto {
                flex: 0 0 auto;
                width: auto;
            }
        }
        
        /* Ajuste visual: Responsividade mobile - tabela de Aulas de Hoje */
        @media (max-width: 575px) {
            .instructor-aulas-table {
                font-size: 0.8rem;
            }
            
            .instructor-aulas-table th,
            .instructor-aulas-table td {
                padding: 0.4rem 0.3rem;
            }
            
            .instructor-aulas-table th {
                font-size: 0.75rem;
            }
            
            /* Permitir quebra de linha em disciplinas longas */
            .instructor-aulas-table td:nth-child(3) {
                word-break: break-word;
                max-width: 120px;
            }
            
            /* Botões de ação menores no mobile */
            .instructor-aulas-table .btn-group-sm .btn {
                padding: 0.25rem 0.4rem;
                font-size: 0.75rem;
            }
        }
        
        /* AJUSTE DASHBOARD INSTRUTOR - otimização layout desktop */
        @media (min-width: 1200px) {
            /* Reduzir padding vertical dos cards de aula em desktop */
            .aula-item {
                padding: 12px 16px !important;
            }

            .aula-item-header {
                margin-bottom: 8px !important;
            }

            .aula-detalhes {
                margin-bottom: 8px !important;
            }

            .aula-detalhe {
                font-size: 13px !important;
                padding: 4px 0 !important;
            }

            /* Destacar primeira aula de hoje */
            .aulas-hoje-card .aula-list > .aula-item:first-child {
                border: 2px solid #2563eb;
                background: #eff6ff;
                position: relative;
            }

            .aulas-hoje-card .aula-list > .aula-item:first-child::before {
                content: "Próxima aula";
                position: absolute;
                top: 8px;
                right: 8px;
                background: #2563eb;
                color: white;
                font-size: 10px;
                font-weight: 600;
                padding: 2px 8px;
                border-radius: 4px;
                text-transform: uppercase;
            }

            /* Compactar cards de próximas aulas */
            .proximas-aulas-card .aula-item {
                padding: 10px 14px !important;
            }

            .proximas-aulas-card .aula-detalhe {
                font-size: 12px !important;
            }

            .proximas-aulas-card .aula-actions .btn {
                font-size: 12px !important;
                padding: 6px 12px !important;
            }

            /* Reduzir espaçamento entre cards */
            .aula-list .aula-item {
                margin-bottom: 8px !important;
            }
        }
    </style>
    
    <!-- Script para Dropdown de Perfil -->
    <script>
        // Toggle do dropdown de perfil
        document.addEventListener('DOMContentLoaded', function() {
            const profileButton = document.getElementById('instrutor-profile-button');
            const profileDropdown = document.getElementById('instrutor-profile-dropdown');
            
            if (profileButton && profileDropdown) {
                profileButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const isVisible = profileDropdown.style.display === 'block';
                    profileDropdown.style.display = isVisible ? 'none' : 'block';
                    profileButton.classList.toggle('active', !isVisible);
                });
                
                // Fechar dropdown ao clicar fora
                document.addEventListener('click', function(e) {
                    if (!profileButton.contains(e.target) && !profileDropdown.contains(e.target)) {
                        profileDropdown.style.display = 'none';
                        profileButton.classList.remove('active');
                    }
                });
            }
        });
    </script>
</body>
</html>