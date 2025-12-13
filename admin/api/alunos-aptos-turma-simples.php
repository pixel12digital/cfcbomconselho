<?php
/**
 * API de Alunos Aptos para Matrícula em Turma Teórica
 * 
 * RESPONSABILIDADE:
 * Retornar lista de alunos elegíveis para matrícula em uma turma teórica específica,
 * aplicando todas as regras de negócio (CFC, status, exames, financeiro).
 * 
 * REGRAS DE SELEÇÃO (Pseudo-SQL):
 * 
 * SELECT alunos.*
 * FROM alunos
 * JOIN cfcs ON alunos.cfc_id = cfcs.id
 * LEFT JOIN turma_matriculas ON alunos.id = turma_matriculas.aluno_id 
 *     AND turma_matriculas.turma_id = :turma_id
 *     AND turma_matriculas.status IN ('matriculado', 'cursando')
 * WHERE 
 *     alunos.status IN (:status_permitidos)  -- ['ativo', 'em_andamento']
 *     AND alunos.cfc_id = :cfc_turma         -- CFC da turma (não da sessão)
 *     AND turma_matriculas.id IS NULL        -- Não está já matriculado nesta turma
 * 
 * Para cada candidato retornado, aplicar filtros adicionais:
 * - Exames OK: GuardsExames::alunoComExamesOkParaTeoricas()
 * - Financeiro OK: FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno()
 * - Status matrícula: 'disponivel' (não matriculado nesta turma)
 * 
 * REGRA DE CFC:
 * - Admin Global (cfc_sessao = 0): filtra alunos por cfc_turma
 * - Admin CFC específico (cfc_sessao > 0): filtra alunos por cfc_turma (que deve = cfc_sessao)
 * 
 * NOTA SOBRE CFC:
 * - CFC canônico do CFC Bom Conselho é ID 36 (não mais 1)
 * - Esta API usa SEMPRE o cfc_id real da turma/aluno vindo do banco
 * - NÃO assume valores fixos de CFC
 * - Migração CFC 1 → 36 é SEMPRE manual, via script documentado em docs/MIGRACAO_CFC_1_PARA_36.md
 * - Nenhuma rotina automática deve disparar UPDATEs de CFC
 * 
 * CORREÇÃO ROBUSTA (12/12/2025):
 * - Status permitidos agora são configuráveis via constante
 * - Query usa IN (...) ao invés de = 'ativo' hardcoded
 * - Mantém uso de funções centralizadas para exames e financeiro
 * - Ver documentação completa em: docs/AUDITORIA_API_ALUNOS_APTOS_TURMA.md
 */

// =====================================================
// CONFIGURAÇÃO: Status de Aluno Permitidos
// =====================================================
// Alunos com estes status podem aparecer na lista de candidatos
// Status excluídos: 'concluido', 'cancelado', 'inativo'
define('STATUS_ALUNO_PERMITIDOS_TURMA_TEORICA', ['ativo', 'em_andamento']);

header('Content-Type: application/json; charset=utf-8');

// Incluir dependências
$rootPath = dirname(__DIR__, 2);
require_once $rootPath . '/includes/config.php';
require_once $rootPath . '/includes/database.php';
require_once __DIR__ . '/../includes/guards_exames.php';
require_once __DIR__ . '/../includes/FinanceiroAlunoHelper.php';

// Função helper para obter usuário atual (se não existir globalmente)
if (!function_exists('getCurrentUser')) {
    function getCurrentUser() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user'] ?? null;
    }
}

try {
    $db = Database::getInstance();
    
    // Obter turma_id da requisição
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    $turmaId = (int)($input['turma_id'] ?? 0);
    
    error_log("[TURMAS TEORICAS API] Requisição recebida - turma_id: {$turmaId}, input: " . json_encode($input));
    
    if (!$turmaId) {
        throw new Exception('turma_id é obrigatório');
    }
    
    // Buscar dados da turma para obter CFC e categoria (se houver)
    $turma = $db->fetch("
        SELECT cfc_id, curso_tipo 
        FROM turmas_teoricas 
        WHERE id = ?
    ", [$turmaId]);
    
    if (!$turma) {
        throw new Exception('Turma não encontrada');
    }
    
    $cfcIdTurma = (int)$turma['cfc_id'];
    
    // Obter CFC da sessão (usuário logado)
    $user = getCurrentUser();
    $cfcIdSessao = $user ? ((int)($user['cfc_id'] ?? 0)) : 0;
    
    // Determinar se é admin global (cfc_id = 0 ou null)
    $isAdminGlobal = ($cfcIdSessao === 0 || $cfcIdSessao === null);
    $sessionCfcLabel = $isAdminGlobal ? 'admin_global' : 'cfc_especifico';
    
    // REGRA DE CFC:
    // - Admin Global (cfc_id = 0): pode gerenciar qualquer CFC, não bloqueia
    // - Usuário de CFC específico (cfc_id > 0): só pode gerenciar seu próprio CFC
    // - Alunos retornados SEMPRE devem ser do CFC da turma (independente do CFC da sessão)
    $cfcIdsCoincidem = $isAdminGlobal ? true : ($cfcIdTurma === $cfcIdSessao);
    
    // Bloquear acesso apenas se usuário de CFC específico tentar acessar turma de outro CFC
    if (!$isAdminGlobal && $cfcIdSessao !== $cfcIdTurma) {
        error_log("[TURMAS TEORICAS API] BLOQUEIO: Usuário CFC {$cfcIdSessao} tentando acessar turma CFC {$cfcIdTurma}");
        throw new Exception('Acesso negado: você não tem permissão para gerenciar turmas deste CFC');
    }
    
    error_log("[TURMAS TEORICAS API] CFC da Turma: {$cfcIdTurma}, CFC da Sessão: {$cfcIdSessao} ({$sessionCfcLabel}), Admin Global: " . ($isAdminGlobal ? 'Sim' : 'Não'));
    
    // =====================================================
    // BUSCAR CANDIDATOS BRUTOS DO CFC
    // =====================================================
    // CORREÇÃO ROBUSTA (12/12/2025): Status permitidos agora são configuráveis
    // 
    // CRITÉRIO DE SELEÇÃO INICIAL:
    // - alunos.status IN (STATUS_ALUNO_PERMITIDOS_TURMA_TEORICA) - ['ativo', 'em_andamento']
    // - alunos.cfc_id = cfcIdTurma (CFC da turma, NÃO da sessão)
    // - LEFT JOIN com turma_matriculas para determinar status_matricula:
    //   - 'matriculado' se já está matriculado nesta turma (status IN ('matriculado', 'cursando'))
    //   - 'disponivel' caso contrário
    //
    // IMPORTANTE: Usar $cfcIdTurma (não $cfcIdSessao) para filtrar alunos
    // Isso garante que apenas alunos do mesmo CFC da turma sejam considerados,
    // mesmo quando o usuário é admin_global (cfc_id = 0)
    //
    // Não filtrar por exames na query inicial - a verificação será feita usando
    // a função centralizada GuardsExames::alunoComExamesOkParaTeoricas()
    
    // Preparar lista de status permitidos para a query
    $statusPermitidos = STATUS_ALUNO_PERMITIDOS_TURMA_TEORICA;
    $placeholdersStatus = implode(',', array_fill(0, count($statusPermitidos), '?'));
    
    error_log("[TURMAS TEORICAS API] Executando query - turma_id={$turmaId}, cfc_id_turma={$cfcIdTurma}, status_permitidos=" . implode(',', $statusPermitidos));
    
    try {
        // Montar query com status permitidos dinâmicos
        $params = array_merge([$turmaId], $statusPermitidos, [$cfcIdTurma]);
        $alunosCandidatos = $db->fetchAll("
            SELECT 
                a.id,
                a.nome,
                a.cpf,
                a.categoria_cnh,
                a.status as status_aluno,
                c.nome as cfc_nome,
                c.id as cfc_id,
                -- Incluir categoria da matrícula ativa (prioridade 1)
                m_ativa.categoria_cnh as categoria_cnh_matricula,
                m_ativa.tipo_servico as tipo_servico_matricula,
                CASE 
                    WHEN tm.id IS NOT NULL THEN 'matriculado'
                    ELSE 'disponivel'
                END as status_matricula
            FROM alunos a
            JOIN cfcs c ON a.cfc_id = c.id
            LEFT JOIN turma_matriculas tm ON a.id = tm.aluno_id 
                AND tm.turma_id = ? 
                AND tm.status IN ('matriculado', 'cursando')
            LEFT JOIN (
                SELECT aluno_id, categoria_cnh, tipo_servico
                FROM matriculas
                WHERE status = 'ativa'
            ) m_ativa ON a.id = m_ativa.aluno_id
            WHERE a.status IN ({$placeholdersStatus})
                AND a.cfc_id = ?
            ORDER BY a.nome
        ", $params);
    } catch (Exception $e) {
        error_log("[TURMAS TEORICAS API] ERRO na query de candidatos: " . $e->getMessage());
        error_log("[TURMAS TEORICAS API] Stack trace: " . $e->getTraceAsString());
        throw $e;
    }
    
    // Logs detalhados após a query
    error_log("[TURMAS TEORICAS API] Turma {$turmaId} - CFC Turma: {$cfcIdTurma}, CFC Sessao: {$cfcIdSessao} ({$sessionCfcLabel}), AdminGlobal=" . ($isAdminGlobal ? 'true' : 'false'));
    error_log("[TURMAS TEORICAS API] Turma {$turmaId} - Total candidatos brutos (antes de qualquer filtro): " . count($alunosCandidatos));
    
    // Se não retornou candidatos, fazer diagnóstico detalhado
    if (count($alunosCandidatos) === 0) {
        error_log("[TURMAS TEORICAS API] ⚠️ NENHUM CANDIDATO ENCONTRADO - Iniciando diagnóstico...");
        
        // Diagnóstico 1: Quantos alunos existem com os status permitidos neste CFC?
        $totalAlunosStatusOK = $db->fetchColumn("
            SELECT COUNT(*) 
            FROM alunos 
            WHERE cfc_id = ? 
            AND status IN (" . implode(',', array_fill(0, count($statusPermitidos), '?')) . ")
        ", array_merge([$cfcIdTurma], $statusPermitidos), 0);
        
        error_log("[TURMAS TEORICAS API] DIAGNÓSTICO: Total de alunos com status permitidos no CFC {$cfcIdTurma}: {$totalAlunosStatusOK}");
        
        // Diagnóstico 2: Quantos alunos existem neste CFC (qualquer status)?
        $totalAlunosCfc = $db->fetchColumn("SELECT COUNT(*) FROM alunos WHERE cfc_id = ?", [$cfcIdTurma], 0);
        error_log("[TURMAS TEORICAS API] DIAGNÓSTICO: Total de alunos no CFC {$cfcIdTurma} (qualquer status): {$totalAlunosCfc}");
        
        // Diagnóstico 3: Verificar se o CFC existe (pode estar faltando e causar problema no JOIN)
        $cfcExiste = $db->fetch("SELECT id, nome FROM cfcs WHERE id = ?", [$cfcIdTurma]);
        if (!$cfcExiste) {
            error_log("[TURMAS TEORICAS API] DIAGNÓSTICO: ⚠️ CFC {$cfcIdTurma} NÃO EXISTE na tabela cfcs - isso causaria exclusão no JOIN!");
        } else {
            error_log("[TURMAS TEORICAS API] DIAGNÓSTICO: CFC {$cfcIdTurma} existe: '{$cfcExiste['nome']}'");
        }
        
        // Diagnóstico 3: Status dos alunos neste CFC
        $statusAlunos = $db->fetchAll("
            SELECT status, COUNT(*) as total 
            FROM alunos 
            WHERE cfc_id = ? 
            GROUP BY status
        ", [$cfcIdTurma]);
        
        error_log("[TURMAS TEORICAS API] DIAGNÓSTICO: Distribuição de status dos alunos no CFC {$cfcIdTurma}:");
        foreach ($statusAlunos as $stat) {
            error_log("[TURMAS TEORICAS API]   - Status '{$stat['status']}': {$stat['total']} aluno(s)");
        }
    }
    
    // Log de cada candidato bruto encontrado
    foreach ($alunosCandidatos as $c) {
        error_log("[TURMAS TEORICAS API] CANDIDATO BRUTO - aluno_id={$c['id']}, nome={$c['nome']}, cfc_id={$c['cfc_id']}, status_aluno=" . ($c['status_aluno'] ?? 'N/A') . ", status_matricula=" . ($c['status_matricula'] ?? 'N/A'));
    }
    
    // Verificar especificamente se o aluno 167 está nos candidatos
    $aluno167Encontrado = false;
    foreach ($alunosCandidatos as $c) {
        if ((int)$c['id'] === 167) {
            $aluno167Encontrado = true;
            error_log("[TURMAS TEORICAS API] ✅ ALUNO 167 ENCONTRADO NOS CANDIDATOS BRUTOS - nome={$c['nome']}, cfc_id={$c['cfc_id']}, status_aluno={$c['status_aluno']}, status_matricula={$c['status_matricula']}");
            break;
        }
    }
    if (!$aluno167Encontrado) {
        error_log("[TURMAS TEORICAS API] ❌ ALUNO 167 NÃO ENCONTRADO NOS CANDIDATOS BRUTOS - Verificar se aluno está ativo e no CFC {$cfcIdTurma}");
        
        // Diagnóstico: buscar aluno 167 diretamente no banco
        try {
            $aluno167Diagnostico = $db->fetch("
                SELECT a.id, a.nome, a.status, a.cfc_id, c.id as cfc_id_join, c.nome as cfc_nome
                FROM alunos a
                LEFT JOIN cfcs c ON a.cfc_id = c.id
                WHERE a.id = 167
            ");
            
            if ($aluno167Diagnostico) {
                error_log("[TURMAS TEORICAS API] 🔍 DIAGNÓSTICO ALUNO 167:");
                error_log("[TURMAS TEORICAS API]   - ID: " . ($aluno167Diagnostico['id'] ?? 'N/A'));
                error_log("[TURMAS TEORICAS API]   - Nome: " . ($aluno167Diagnostico['nome'] ?? 'N/A'));
                error_log("[TURMAS TEORICAS API]   - Status: " . ($aluno167Diagnostico['status'] ?? 'N/A') . " (esperado: 'ativo')");
                error_log("[TURMAS TEORICAS API]   - CFC ID (alunos.cfc_id): " . ($aluno167Diagnostico['cfc_id'] ?? 'N/A') . " (esperado: {$cfcIdTurma})");
                error_log("[TURMAS TEORICAS API]   - CFC ID (join): " . ($aluno167Diagnostico['cfc_id_join'] ?? 'N/A'));
                error_log("[TURMAS TEORICAS API]   - CFC Nome: " . ($aluno167Diagnostico['cfc_nome'] ?? 'N/A'));
                
                // Verificar se status é diferente de 'ativo'
                if (($aluno167Diagnostico['status'] ?? '') !== 'ativo') {
                    error_log("[TURMAS TEORICAS API]   ⚠️ PROBLEMA: Status do aluno 167 não é 'ativo'!");
                }
                
                // Verificar se cfc_id é diferente do esperado
                if ((int)($aluno167Diagnostico['cfc_id'] ?? 0) !== $cfcIdTurma) {
                    error_log("[TURMAS TEORICAS API]   ⚠️ PROBLEMA: CFC do aluno 167 ({$aluno167Diagnostico['cfc_id']}) é diferente do CFC da turma ({$cfcIdTurma})!");
                }
            } else {
                error_log("[TURMAS TEORICAS API]   ❌ ERRO: Aluno 167 não existe no banco de dados!");
            }
        } catch (Exception $e) {
            error_log("[TURMAS TEORICAS API]   ❌ ERRO ao buscar diagnóstico do aluno 167: " . $e->getMessage());
        }
    }
    
    // =====================================================
    // FILTRAR ALUNOS USANDO FUNÇÃO CENTRALIZADA
    // =====================================================
    // AUDITORIA (12/12/2025): Loop de validação aplica 5 filtros sequenciais
    // 
    // Para cada aluno retornado pela query inicial, são aplicados:
    // 1. Verificação de CFC (blindagem extra - linha 200)
    // 2. Verificação de Exames (GuardsExames::alunoComExamesOkParaTeoricas)
    // 3. Verificação Financeira (FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno)
    // 4. Verificação de Categoria (sempre true por enquanto)
    // 5. Verificação de Status de Matrícula (deve ser 'disponivel')
    //
    // ALUNO É ELEGÍVEL SE TODAS AS CONDIÇÕES FOREM TRUE:
    // - examesOK === true
    // - financeiroOK === true  
    // - categoriaOK === true (sempre true hoje)
    // - status_matricula === 'disponivel' (não pode estar já matriculado nesta turma)
    //
    // Ver documentação completa em: docs/AUDITORIA_TURMAS_TEORICAS_MATRICULA.md
    
    $alunosAptos = [];
    $debugInfo = [];
    
    foreach ($alunosCandidatos as $aluno) {
        $alunoId = (int)$aluno['id'];
        $alunoCfcId = (int)($aluno['cfc_id'] ?? 0);
        
        // FILTRO 1: BLINDAGEM EXTRA - Verificar se CFC do aluno corresponde ao CFC da turma
        // Mesmo que a query já filtre por CFC, esta verificação garante que nenhum aluno
        // de outra origem (ex: importação, migração) seja considerado incorretamente
        if ($alunoCfcId !== $cfcIdTurma) {
            error_log("[TURMAS TEORICAS API] WARNING: Aluno {$alunoId} ({$aluno['nome']}) com cfc_id={$alunoCfcId} diferente do cfc da turma {$cfcIdTurma} - IGNORANDO");
            continue; // Não considera este aluno
        }
        
        // FILTRO 2: Verificar exames usando função centralizada
        // Retorna true se ambos exames (médico e psicotécnico) têm resultado 'apto'/'aprovado'
        // CORREÇÃO ROBUSTA (12/12/2025): Usa mesma função do histórico do aluno
        $examesOK = GuardsExames::alunoComExamesOkParaTeoricas($alunoId);
        
        // FILTRO 3: Verificar financeiro usando helper centralizado
        // Retorna true se: tem matrícula ativa + pelo menos uma fatura paga + sem faturas vencidas
        // CORREÇÃO ROBUSTA (12/12/2025): Usa mesma função do histórico do aluno
        // Esta função é mais completa que verificarInadimplencia() pois também verifica:
        // - Existência de matrícula ativa
        // - Pelo menos uma fatura paga
        // - Faturas vencidas (considerando data de vencimento)
        $verificacaoFinanceira = FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno($alunoId);
        $financeiroOK = $verificacaoFinanceira['liberado'];
        
        // FILTRO 4: Verificar categoria (por enquanto, não filtrar por categoria)
        // NOTA: A turma não tem campo categoria_cnh direto.
        // Se houver necessidade de filtrar por categoria, verificar através da matrícula ativa do aluno.
        // Por enquanto, aceitar qualquer categoria.
        $categoriaOK = true; // TODO: Implementar filtro de categoria se necessário
        
        // FILTRO 5: Determinar elegibilidade final
        // Aluno só é elegível se NÃO estiver já matriculado nesta turma (status_matricula === 'disponivel')
        $elegivel = ($examesOK && $financeiroOK && $categoriaOK && $aluno['status_matricula'] === 'disponivel');
        
        // Log específico para aluno 167 (Charles) - DETALHADO
        if ($alunoId === 167) {
            error_log("[TURMAS TEORICAS API] ===== ALUNO 167 (CHARLES) ===== ");
            error_log("[TURMAS TEORICAS API] Aluno 167 - turma_cfc_id={$cfcIdTurma}, session_cfc_id={$cfcIdSessao} ({$sessionCfcLabel})");
            error_log("[TURMAS TEORICAS API] Aluno 167 - exames_ok=" . ($examesOK ? 'true' : 'false') . 
                     ", financeiro_ok=" . ($financeiroOK ? 'true' : 'false') . 
                     ", categoria_ok=" . ($categoriaOK ? 'true' : 'false') . 
                     ", status_matricula=" . $aluno['status_matricula'] .
                     ", elegivel=" . ($elegivel ? 'true' : 'false'));
            error_log("[TURMAS TEORICAS API] ================================= ");
        }
        
        // Aluno é elegível se:
        // 1. Exames OK (usando função centralizada)
        // 2. Financeiro OK (sem faturas vencidas)
        // 3. Categoria OK (por enquanto sempre true)
        // 4. Não está matriculado nesta turma
        // NOTA: Não há bloqueio por CFC aqui - alunos sempre são do CFC da turma (filtro na query)
        if ($elegivel) {
            // Buscar dados dos exames para exibição
            $exames = GuardsExames::getStatusExames($alunoId);
            
            $aluno['exame_medico_resultado'] = $exames['medico']['resultado'] ?? null;
            $aluno['exame_medico_data'] = $exames['medico']['data_resultado'] ?? null;
            $aluno['exame_medico_protocolo'] = $exames['medico']['protocolo'] ?? null;
            
            $aluno['exame_psicotecnico_resultado'] = $exames['psicotecnico']['resultado'] ?? null;
            $aluno['exame_psicotecnico_data'] = $exames['psicotecnico']['data_resultado'] ?? null;
            $aluno['exame_psicotecnico_protocolo'] = $exames['psicotecnico']['protocolo'] ?? null;
            
            $alunosAptos[] = $aluno;
        }
        
        // Log para debug (formato padronizado)
        error_log("[TURMAS TEORICAS API] Candidato aluno {$alunoId} ({$aluno['nome']}) - turma_cfc_id={$cfcIdTurma}, session_cfc_id={$cfcIdSessao}, financeiro_ok=" . ($financeiroOK ? 'true' : 'false') . 
                 ", exames_ok=" . ($examesOK ? 'true' : 'false') . 
                 ", categoria_ok=" . ($categoriaOK ? 'true' : 'false') . 
                 ", status_matricula=" . $aluno['status_matricula'] .
                 ", elegivel=" . ($elegivel ? 'true' : 'false'));
        
        $debugInfo[] = [
            'aluno_id' => $alunoId,
            'nome' => $aluno['nome'],
            'exames_ok' => $examesOK,
            'financeiro_ok' => $financeiroOK,
            'categoria_ok' => $categoriaOK,
            'status_matricula' => $aluno['status_matricula'],
            'elegivel' => $elegivel
        ];
    }
    
    // Calcular contadores intermediários
    $totalComExamesOK = 0;
    $totalComFinanceiroOK = 0;
    $totalComCategoriaOK = 0;
    $totalDisponivel = 0;
    
    foreach ($debugInfo as $info) {
        if ($info['exames_ok']) $totalComExamesOK++;
        if ($info['financeiro_ok']) $totalComFinanceiroOK++;
        if ($info['categoria_ok']) $totalComCategoriaOK++;
        if ($info['status_matricula'] === 'disponivel') $totalDisponivel++;
    }
    
    // Montar debug_info com informações de CFC e contadores intermediários
    $debugInfoCompleto = [
        'turma_cfc_id' => $cfcIdTurma,
        'session_cfc_id' => $cfcIdSessao,
        'session_cfc_label' => $sessionCfcLabel,
        'is_admin_global' => $isAdminGlobal,
        'cfc_ids_match' => $cfcIdsCoincidem,
        'cfc_usado_na_query' => $cfcIdTurma, // CFC efetivamente usado na query (sempre o da turma)
        'turma_id' => $turmaId,
        'total_candidatos' => count($alunosCandidatos), // Total retornado pela query (antes de filtros de exames/financeiro)
        'total_com_exames_ok' => $totalComExamesOK,
        'total_com_financeiro_ok' => $totalComFinanceiroOK,
        'total_com_categoria_ok' => $totalComCategoriaOK,
        'total_disponivel' => $totalDisponivel, // Não matriculado nesta turma
        'total_aptos' => count($alunosAptos), // Total final após todos os filtros
        'alunos_detalhados' => $debugInfo
    ];
    
    $response = [
        'sucesso' => true,
        'alunos' => array_values($alunosAptos),
        'estatisticas' => [
            'total_candidatos' => count($alunosCandidatos),
            'total_aptos' => count($alunosAptos),
            'total_matriculados' => count($alunosCandidatos) - count($alunosAptos)
        ],
        'debug_info' => $debugInfoCompleto,
        'debug' => [
            'turma_id' => $turmaId,
            'cfc_id_turma' => $cfcIdTurma,
            'cfc_id_sessao' => $cfcIdSessao,
            'session_cfc_label' => $sessionCfcLabel,
            'is_admin_global' => $isAdminGlobal,
            'cfc_ids_match' => $cfcIdsCoincidem,
            'alunos_encontrados' => count($alunosAptos),
            'total_candidatos' => count($alunosCandidatos)
        ]
    ];
    
    error_log("[TURMAS TEORICAS API] Resposta - Total aptos: " . count($alunosAptos) . ", CFC Turma: {$cfcIdTurma}, CFC Sessão: {$cfcIdSessao}, Coincidem: " . ($cfcIdsCoincidem ? 'Sim' : 'Não'));
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro interno do servidor',
        'debug' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
