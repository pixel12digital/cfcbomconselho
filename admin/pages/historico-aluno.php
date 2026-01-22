<?php
// Esta página é incluída pelo sistema de roteamento do admin
// As variáveis $user, $isAdmin, $isSecretaria, $isInstrutor já estão definidas no index.php

// Verificar se estamos sendo acessados diretamente (sem template)
if (!defined('ADMIN_ROUTING')) {
    // Redirecionar para o template do admin
    $aluno_id = $_GET['id'] ?? '';
    if ($aluno_id) {
        header("Location: ../index.php?page=historico-aluno&id=$aluno_id");
        exit;
    } else {
        header('Location: ../index.php?page=alunos');
        exit;
    }
}

// Incluir dependências necessárias (caso não estejam disponíveis)
if (!function_exists('db')) {
    require_once '../../includes/database.php';
}

// Verificar se as variáveis estão definidas (fallback para compatibilidade)
if (!isset($user)) {
    $user = [
        'id' => $_SESSION['user_id'] ?? null,
        'nome' => $_SESSION['user_name'] ?? null,
        'tipo' => $_SESSION['user_type'] ?? null
    ];
}
if (!isset($isAdmin)) $isAdmin = ($user['tipo'] ?? '') === 'admin';
if (!isset($isSecretaria)) $isSecretaria = ($user['tipo'] ?? '') === 'secretaria';
if (!isset($isInstrutor)) $isInstrutor = ($user['tipo'] ?? '') === 'instrutor';

// Garantir que o banco de dados está disponível
if (!isset($db)) {
    $db = db(); // Usar função global
}

// Verificar se ID do aluno foi fornecido
$alunoId = null;
$turmaIdFoco = null; // AJUSTE 2025-12 - Turma para destacar quando vindo do Diário
if (defined('ADMIN_ROUTING')) {
    // Se estamos no sistema de roteamento, usar variável global
    $alunoId = $aluno_id ?? null;
    $turmaIdFoco = $_GET['turma_id'] ?? null;
} else {
    // Se acessado diretamente, usar GET
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        header('Location: alunos.php');
        exit;
    }
    $turmaIdFoco = $_GET['turma_id'] ?? null;
    $alunoId = (int)$_GET['id'];
}

if (!$alunoId) {
    if (defined('ADMIN_ROUTING')) {
        echo '<div class="alert alert-danger">ID do aluno não fornecido.</div>';
        return;
    } else {
        header('Location: alunos.php');
        exit;
    }
}

// Buscar dados do aluno com matrícula ativa (priorizar categoria/tipo da matrícula)
// REGRA DE PADRONIZAÇÃO: Sempre priorizar dados da matrícula ativa quando existir
if (defined('ADMIN_ROUTING') && isset($aluno)) {
    // Se estamos no sistema de roteamento e já temos os dados
    $alunoData = $aluno;
    $cfcData = $cfc;
    
    // Se não vier categoria_cnh_matricula, buscar matrícula ativa
    if (empty($alunoData['categoria_cnh_matricula'])) {
        $matriculaAtiva = $db->fetch("
            SELECT categoria_cnh, tipo_servico
            FROM matriculas
            WHERE aluno_id = ? AND status = 'ativa'
            ORDER BY data_inicio DESC
            LIMIT 1
        ", [$alunoId]);
        
        if ($matriculaAtiva) {
            $alunoData['categoria_cnh_matricula'] = $matriculaAtiva['categoria_cnh'] ?? null;
            $alunoData['tipo_servico_matricula'] = $matriculaAtiva['tipo_servico'] ?? null;
        }
    }
} else {
    // Buscar dados do banco com JOIN para matrícula ativa
    $alunoData = $db->fetch("
        SELECT 
            a.*, 
            c.nome as cfc_nome, 
            c.cnpj as cfc_cnpj,
            m_ativa.categoria_cnh AS categoria_cnh_matricula,
            m_ativa.tipo_servico AS tipo_servico_matricula
        FROM alunos a 
        LEFT JOIN cfcs c ON a.cfc_id = c.id
        LEFT JOIN (
            SELECT 
                m1.aluno_id,
                m1.categoria_cnh,
                m1.tipo_servico
            FROM matriculas m1
            WHERE m1.status = 'ativa'
            GROUP BY m1.aluno_id
        ) AS m_ativa ON m_ativa.aluno_id = a.id
        WHERE a.id = ?
    ", [$alunoId]);
    
    if (!$alunoData) {
        if (defined('ADMIN_ROUTING')) {
            echo '<div class="alert alert-danger">Aluno não encontrado.</div>';
            return;
        } else {
            header('Location: alunos.php');
            exit;
        }
    }
    
    $cfcData = null;
    if ($alunoData['cfc_id']) {
        $cfcData = $db->fetch("SELECT * FROM cfcs WHERE id = ?", [$alunoData['cfc_id']]);
    }
}

if (!$alunoData) {
    if (defined('ADMIN_ROUTING')) {
        echo '<div class="alert alert-danger">Aluno não encontrado.</div>';
        return;
    } else {
        header('Location: alunos.php');
        exit;
    }
}

// Buscar histórico de aulas práticas (OTIMIZADO: limitado para melhor performance)
// Limite de 500 aulas é suficiente para histórico completo de qualquer aluno
// CORREÇÃO 2025-12: Histórico Completo de Aulas exibe apenas aulas práticas
$aulas = $db->fetchAll("
    SELECT a.*, i.credencial, COALESCE(u.nome, i.nome) as instrutor_nome, v.placa, v.modelo, v.marca
    FROM aulas a
    LEFT JOIN instrutores i ON a.instrutor_id = i.id
    LEFT JOIN usuarios u ON i.usuario_id = u.id
    LEFT JOIN veiculos v ON a.veiculo_id = v.id
    WHERE a.aluno_id = ?
    ORDER BY a.data_aula DESC, a.hora_inicio DESC
    LIMIT 500
", [$alunoId]);

// Calcular estatísticas gerais (OTIMIZADO: usar SQL COUNT ao invés de array_filter)
// Buscar estatísticas via SQL é muito mais rápido que processar em PHP
$estatisticasAulas = $db->fetch("
    SELECT 
        COUNT(*) as total_aulas,
        COUNT(CASE WHEN status = 'concluida' THEN 1 END) as aulas_concluidas,
        COUNT(CASE WHEN status = 'cancelada' THEN 1 END) as aulas_canceladas,
        COUNT(CASE WHEN status = 'agendada' THEN 1 END) as aulas_agendadas
    FROM aulas
    WHERE aluno_id = ?
", [$alunoId]);

$totalAulas = (int)($estatisticasAulas['total_aulas'] ?? count($aulas));
$aulasConcluidas = (int)($estatisticasAulas['aulas_concluidas'] ?? 0);
$aulasCanceladas = (int)($estatisticasAulas['aulas_canceladas'] ?? 0);
$aulasAgendadas = (int)($estatisticasAulas['aulas_agendadas'] ?? 0);

// Buscar exames do aluno (OTIMIZADO: limitado para melhor performance)
// Limite de 100 exames é suficiente para histórico completo
$exames = $db->fetchAll("
    SELECT * FROM exames 
    WHERE aluno_id = ? 
    ORDER BY tipo, data_agendada DESC
    LIMIT 100
", [$alunoId]);

// Separar exames por tipo
$exameMedico = null;
$examePsicotecnico = null;

foreach ($exames as $exame) {
    if ($exame['tipo'] === 'medico') {
        $exameMedico = $exame;
    } elseif ($exame['tipo'] === 'psicotecnico') {
        $examePsicotecnico = $exame;
    }
}

// =====================================================
// FUNÇÃO HELPER PARA RENDERIZAR BADGES DE EXAME
// =====================================================
// Centraliza a lógica de exibição de status e resultado
// para garantir consistência entre histórico e tela de exames
// =====================================================
function renderizarBadgesExame($exame) {
    if (!$exame) {
        return [
            'status_badge' => '',
            'resultado_badge' => '',
            'tem_resultado' => false
        ];
    }
    
    $status = $exame['status'] ?? 'agendado';
    $resultado = $exame['resultado'] ?? null;
    $dataResultado = $exame['data_resultado'] ?? null;
    
    // Log para debug
    error_log('[DEBUG EXAME] id=' . ($exame['id'] ?? 'N/A') . 
             ', status=' . $status . 
             ', resultado=' . ($resultado ?? 'NULL') . 
             ', data_resultado=' . ($dataResultado ?? 'NULL'));
    
    // Determinar se tem resultado lançado
    // Considera que tem resultado se:
    // 1. O campo resultado não está vazio/null e não é 'pendente'
    // 2. OU existe data_resultado preenchida
    $temResultado = false;
    if (!empty($resultado) && $resultado !== 'pendente' && in_array($resultado, ['apto', 'inapto', 'inapto_temporario', 'aprovado', 'reprovado'])) {
        $temResultado = true;
    } elseif (!empty($dataResultado)) {
        $temResultado = true;
    }
    
    // Badge de Status (principal)
    $statusBadge = '';
    if ($status === 'agendado') {
        $statusBadge = '<span class="badge bg-primary">Agendado</span>';
    } elseif ($status === 'concluido') {
        $statusBadge = '<span class="badge bg-success">Concluído</span>';
    } elseif ($status === 'cancelado') {
        $statusBadge = '<span class="badge bg-danger">Cancelado</span>';
    } else {
        $statusBadge = '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
    }
    
    // Badge de Resultado (secundária)
    // COMPATIBILIDADE: 'aprovado' = 'apto', 'reprovado' = 'inapto' (valores antigos)
    $resultadoBadge = '';
    if ($temResultado) {
        // Normalizar valores antigos para exibição
        $resultadoNormalizado = $resultado;
        if ($resultado === 'aprovado') {
            $resultadoNormalizado = 'apto';
        } elseif ($resultado === 'reprovado') {
            $resultadoNormalizado = 'inapto';
        }
        
        // Tem resultado lançado - mostrar o resultado
        if ($resultadoNormalizado === 'apto') {
            $resultadoBadge = '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Apto</span>';
        } elseif ($resultadoNormalizado === 'inapto') {
            $resultadoBadge = '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Inapto</span>';
        } elseif ($resultadoNormalizado === 'inapto_temporario') {
            $resultadoBadge = '<span class="badge bg-warning"><i class="fas fa-clock me-1"></i>Inapto Temporário</span>';
        } else {
            // Resultado lançado mas valor não reconhecido - mostrar como pendente por segurança
            $resultadoBadge = '<span class="badge bg-warning"><i class="fas fa-clock me-1"></i>Pendente</span>';
        }
    } else {
        // Não tem resultado lançado - mostrar pendente
        $resultadoBadge = '<span class="badge bg-warning"><i class="fas fa-clock me-1"></i>Pendente</span>';
    }
    
    return [
        'status_badge' => $statusBadge,
        'resultado_badge' => $resultadoBadge,
        'tem_resultado' => $temResultado
    ];
}

// Calcular se exames estão OK
// Usar função helper para verificar se ambos têm resultado 'apto' (ou 'aprovado' para compatibilidade)
$badgesMedicoOK = renderizarBadgesExame($exameMedico);
$badgesPsicotecnicoOK = renderizarBadgesExame($examePsicotecnico);

$examesOK = false;
// Verificar se ambos têm resultado 'apto' (ou 'aprovado' como equivalente para compatibilidade)
$resultadoMedicoOK = in_array($exameMedico['resultado'] ?? '', ['apto', 'aprovado']);
$resultadoPsicotecnicoOK = in_array($examePsicotecnico['resultado'] ?? '', ['apto', 'aprovado']);

if ($exameMedico && $badgesMedicoOK['tem_resultado'] && $resultadoMedicoOK &&
    $examePsicotecnico && $badgesPsicotecnicoOK['tem_resultado'] && $resultadoPsicotecnicoOK) {
    $examesOK = true;
}

// Verificar guards de bloqueio
require_once __DIR__ . '/../includes/guards_exames.php';
$bloqueioTeorica = GuardsExames::verificarBloqueioTeorica($alunoId);

// =====================================================
// VERIFICAÇÃO DE BLOQUEIO FINANCEIRO PARA EXAMES
// =====================================================
// FUNÇÃO CENTRAL: FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno()
// 
// Esta é a mesma função usada na tela de exames (admin/api/exames_simple.php).
// Garante que a validação financeira seja consistente em ambos os lugares.
// 
// REGRA PARA EXAMES:
// - Bloquear se não houver nenhuma fatura lançada
// - Bloquear se existir qualquer fatura em atraso
// - Permitir se houver pelo menos uma fatura PAGA e não houver faturas em atraso
// - Faturas ABERTAS com vencimento futuro NÃO bloqueiam
// =====================================================
require_once __DIR__ . '/../includes/FinanceiroAlunoHelper.php';
$verificacaoFinanceiraExames = FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno($alunoId);

error_log('[HISTORICO ALUNO] Aluno ' . $alunoId . 
         ' - Verificação Financeira: ' . json_encode($verificacaoFinanceiraExames) . 
         ' - Origem: Histórico do Aluno');

// CORREÇÃO 2025-12: Calcular aulas teóricas concluídas a partir de turma_presencas + turma_aulas_agendadas
// Fonte de verdade: turma_presencas com presente = 1 e turma_aulas_agendadas com status válido
// REGRA: Contar aulas teóricas onde o aluno está presente (presente = 1) em aulas válidas (agendada ou realizada)
$aulasTeoricasConcluidas = 0;
$disciplinasTeoricasUnicasGerais = [];

// Buscar todas as presenças teóricas do aluno em todas as turmas
$presencasTeoricas = $db->fetchAll("
    SELECT 
        tp.turma_aula_id,
        tp.presente,
        taa.disciplina,
        taa.turma_id,
        taa.status as aula_status
    FROM turma_presencas tp
    INNER JOIN turma_aulas_agendadas taa ON tp.turma_aula_id = taa.id
    WHERE tp.aluno_id = ?
    AND tp.presente = 1
    AND taa.status IN ('agendada', 'realizada')
    ORDER BY taa.data_aula ASC
", [$alunoId]);

// Contar aulas teóricas concluídas (apenas presentes em aulas válidas)
// CORREÇÃO 2025-12: Contar cada presença individual, não por disciplina única
// Cada registro em turma_presencas com presente=1 representa uma aula teórica concluída
foreach ($presencasTeoricas as $presenca) {
    // Contar cada presença individual (cada aula assistida)
    $aulasTeoricasConcluidas++;
    // Manter registro de disciplinas para outros cálculos se necessário
    $disciplina = $presenca['disciplina'] ?? 'geral';
    if (!isset($disciplinasTeoricasUnicasGerais[$disciplina])) {
        $disciplinasTeoricasUnicasGerais[$disciplina] = true;
    }
}

// Log para debug
error_log('[HISTORICO] Aulas teóricas concluídas (turma_presencas): ' . $aulasTeoricasConcluidas);

// CORREÇÃO 2025-12: Somar aulas teóricas concluídas ao total de aulas concluídas
// $aulasConcluidas conta apenas práticas, então adicionamos as teóricas
$aulasConcluidas = $aulasConcluidas + $aulasTeoricasConcluidas;
// OTIMIZADO: Calcular estatísticas de aulas práticas em uma única passada
$aulasPraticasConcluidas = 0;
$aulasPraticasPorTipo = [
    'moto' => 0,
    'carro' => 0,
    'carga' => 0,
    'passageiros' => 0,
    'combinacao' => 0
];

foreach ($aulas as $aula) {
    if ($aula['status'] === 'concluida' && $aula['tipo_aula'] === 'pratica') {
        $aulasPraticasConcluidas++;
        $tipoVeiculo = $aula['tipo_veiculo'] ?? '';
        if (isset($aulasPraticasPorTipo[$tipoVeiculo])) {
            $aulasPraticasPorTipo[$tipoVeiculo]++;
        }
    }
}

// Incluir classe de configurações
require_once __DIR__ . '/../includes/configuracoes_categorias.php';

/**
 * FUNÇÃO CENTRALIZADA PARA CALCULAR CARGA DE CATEGORIA
 * REGRA DE NEGÓCIO:
 * - Para categoria simples (ex: B): usar teoria e práticas da própria categoria
 * - Para categoria combinada (ex: AB):
 *   - Teoria: NÃO duplicar (usar valor único, ex: 45h, não 90h)
 *   - Práticas: SOMAR as práticas das categorias componentes (ex: A=20 + B=20 = 40)
 */
function calcularCargaCategoriaHistorico($categoriaCodigo, $configManager) {
    // Decompor categoria para verificar se é combinada
    $categoriasIndividuais = $configManager->decomporCategoriaCombinada($categoriaCodigo);
    $ehCombinada = count($categoriasIndividuais) > 1;
    
    if ($ehCombinada) {
        // Categoria combinada: somar práticas, teoria única
        $totalHorasTeoricas = 0;
        $totalAulasPraticas = 0;
        $primeiraConfig = null;
        
        foreach ($categoriasIndividuais as $cat) {
            $config = $configManager->getConfiguracaoByCategoria($cat);
            if ($config) {
                if ($primeiraConfig === null) {
                    $primeiraConfig = $config;
                }
                // Teoria: usar apenas da primeira categoria (não somar)
                // Práticas: somar todas as categorias componentes
                $totalAulasPraticas += (int)($config['horas_praticas_total'] ?? 0);
            }
        }
        
        // Teoria: usar da primeira categoria (valor único, não somado)
        $totalHorasTeoricas = $primeiraConfig ? (int)($primeiraConfig['horas_teoricas'] ?? 0) : 0;
        
        return [
            'total_horas_teoricas' => $totalHorasTeoricas,
            'total_aulas_praticas' => $totalAulasPraticas,
            'eh_combinada' => true,
            'categorias_componentes' => $categoriasIndividuais
        ];
    } else {
        // Categoria simples: usar valores diretos
        $config = $configManager->getConfiguracaoByCategoria($categoriaCodigo);
        if ($config) {
            return [
                'total_horas_teoricas' => (int)($config['horas_teoricas'] ?? 0),
                'total_aulas_praticas' => (int)($config['horas_praticas_total'] ?? 0),
                'eh_combinada' => false,
                'categorias_componentes' => [$categoriaCodigo]
            ];
        }
    }
    
    // Fallback se não encontrar configuração
    return [
        'total_horas_teoricas' => 45,
        'total_aulas_praticas' => 20,
        'eh_combinada' => false,
        'categorias_componentes' => []
    ];
}

// Calcular progresso baseado na configuração da categoria
// REGRA DE PADRONIZAÇÃO: Priorizar categoria da matrícula ativa quando existir
$configManager = ConfiguracoesCategorias::getInstance();
$categoriaAluno = !empty($alunoData['categoria_cnh_matricula']) 
    ? $alunoData['categoria_cnh_matricula'] 
    : $alunoData['categoria_cnh'];

// Calcular carga usando função centralizada
$cargaCategoria = calcularCargaCategoriaHistorico($categoriaAluno, $configManager);

// Log para debug
error_log('[DEBUG HISTORICO] Aluno ' . $alunoId . ' - categoria: ' . $categoriaAluno);
error_log('[DEBUG HISTORICO] Carga calculada: ' . json_encode($cargaCategoria));
error_log('[DEBUG HISTORICO] total_horas_teoricas: ' . $cargaCategoria['total_horas_teoricas']);
error_log('[DEBUG HISTORICO] total_aulas_praticas: ' . $cargaCategoria['total_aulas_praticas']);
error_log('[DEBUG HISTORICO] total_horas_necessarias: ' . ($cargaCategoria['total_horas_teoricas'] + $cargaCategoria['total_aulas_praticas']));

// Verificar se é uma categoria mudanca_categoria (ex: AB, AC, etc.)
$configuracoesCategorias = $configManager->getConfiguracoesParaCategoriaCombinada($categoriaAluno);
$ehCategoriaCombinada = count($configuracoesCategorias) > 1;

if ($ehCategoriaCombinada) {
    // Para categorias mudanca_categorias, calcular progresso separadamente para cada categoria
    // REGRA: Usar função centralizada para garantir teoria única e práticas somadas
    $aulasNecessarias = $cargaCategoria['total_aulas_praticas']; // Soma das práticas (ex: 20+20=40)
    $aulasTeoricasNecessarias = $cargaCategoria['total_horas_teoricas']; // Teoria única (ex: 45, não 90)
    $progressoDetalhado = [];
    
    // OTIMIZADO: Calcular todas as estatísticas em uma única passada pelo array de aulas
    // ao invés de múltiplos array_filter dentro do loop
    $estatisticasPorCategoria = [];
    foreach ($configuracoesCategorias as $categoria => $config) {
        $estatisticasPorCategoria[$categoria] = [
            'teoricas' => ['disciplinas' => []],
            'praticas_moto' => 0,
            'praticas_carro' => 0,
            'praticas_carga' => 0,
            'praticas_passageiros' => 0,
            'praticas_combinacao' => 0
        ];
    }
    
    // CORREÇÃO 2025-12: Para teóricas, usar turma_presencas em vez de tabela aulas
    // Buscar presenças teóricas do aluno em todas as turmas
    $presencasTeoricasDetalhadas = $db->fetchAll("
        SELECT 
            tp.turma_aula_id,
            tp.presente,
            taa.disciplina,
            taa.turma_id
        FROM turma_presencas tp
        INNER JOIN turma_aulas_agendadas taa ON tp.turma_aula_id = taa.id
        WHERE tp.aluno_id = ?
        AND tp.presente = 1
        AND taa.status IN ('agendada', 'realizada')
    ", [$alunoId]);
    
    // CORREÇÃO 2025-12: Contar cada presença individual, não por disciplina única
    // Cada registro em turma_presencas com presente=1 representa uma aula teórica concluída
    $totalPresencasTeoricasDetalhadas = 0;
    foreach ($presencasTeoricasDetalhadas as $presenca) {
        // Contar cada presença individual (cada aula assistida)
        $totalPresencasTeoricasDetalhadas++;
        $disciplina = $presenca['disciplina'] ?? 'geral';
        // Manter registro de disciplinas para outros cálculos se necessário
        // Para categorias combinadas, teoria é compartilhada entre categorias combinadas
        foreach ($estatisticasPorCategoria as $cat => $estat) {
            if (!isset($estatisticasPorCategoria[$cat]['teoricas']['disciplinas'][$disciplina])) {
                $estatisticasPorCategoria[$cat]['teoricas']['disciplinas'][$disciplina] = true;
            }
        }
    }
    
    // Uma única passada pelo array de aulas para calcular estatísticas de práticas
    foreach ($aulas as $aula) {
        if ($aula['status'] !== 'concluida') {
            continue;
        }
        
        $categoria = $aula['categoria_veiculo'] ?? null;
        if (!$categoria || !isset($estatisticasPorCategoria[$categoria])) {
            continue;
        }
        
        // CORREÇÃO 2025-12: Teóricas já foram contadas acima usando turma_presencas
        // Agora só processar práticas
        if ($aula['tipo_aula'] === 'pratica') {
            // Para práticas, contar por tipo de veículo
            $tipoVeiculo = $aula['tipo_veiculo'] ?? '';
            switch ($tipoVeiculo) {
                case 'moto':
                    $estatisticasPorCategoria[$categoria]['praticas_moto']++;
                    break;
                case 'carro':
                    $estatisticasPorCategoria[$categoria]['praticas_carro']++;
                    break;
                case 'carga':
                    $estatisticasPorCategoria[$categoria]['praticas_carga']++;
                    break;
                case 'passageiros':
                    $estatisticasPorCategoria[$categoria]['praticas_passageiros']++;
                    break;
                case 'combinacao':
                    $estatisticasPorCategoria[$categoria]['praticas_combinacao']++;
                    break;
            }
        }
    }
    
    foreach ($configuracoesCategorias as $categoria => $config) {
        // NÃO somar aqui - já foi calculado pela função centralizada
        
        // CORREÇÃO 2025-12: Usar contagem de presenças individuais, não por disciplina única
        // Para categorias combinadas, teoria é compartilhada, então usar o total de presenças
        $teoricasConcluidas = $totalPresencasTeoricasDetalhadas > 0 
            ? $totalPresencasTeoricasDetalhadas 
            : $aulasTeoricasConcluidas;
        $praticasMotoConcluidas = $estatisticasPorCategoria[$categoria]['praticas_moto'];
        $praticasCarroConcluidas = $estatisticasPorCategoria[$categoria]['praticas_carro'];
        $praticasCargaConcluidas = $estatisticasPorCategoria[$categoria]['praticas_carga'];
        $praticasPassageirosConcluidas = $estatisticasPorCategoria[$categoria]['praticas_passageiros'];
        $praticasCombinacaoConcluidas = $estatisticasPorCategoria[$categoria]['praticas_combinacao'];

        $progressoDetalhado[$categoria] = [
            'config' => $config,
            'teoricas' => [
                'concluidas' => $teoricasConcluidas,
                'necessarias' => $config['horas_teoricas'],
                'percentual' => $config['horas_teoricas'] > 0 ? min(100, ($teoricasConcluidas / $config['horas_teoricas']) * 100) : 0
            ],
            'praticas_moto' => [
                'concluidas' => $praticasMotoConcluidas,
                'necessarias' => $config['horas_praticas_moto'],
                'percentual' => $config['horas_praticas_moto'] > 0 ? min(100, ($praticasMotoConcluidas / $config['horas_praticas_moto']) * 100) : 0
            ],
            'praticas_carro' => [
                'concluidas' => $praticasCarroConcluidas,
                'necessarias' => $config['horas_praticas_carro'],
                'percentual' => $config['horas_praticas_carro'] > 0 ? min(100, ($praticasCarroConcluidas / $config['horas_praticas_carro']) * 100) : 0
            ],
            'praticas_carga' => [
                'concluidas' => $praticasCargaConcluidas,
                'necessarias' => $config['horas_praticas_carga'],
                'percentual' => $config['horas_praticas_carga'] > 0 ? min(100, ($praticasCargaConcluidas / $config['horas_praticas_carga']) * 100) : 0
            ],
            'praticas_passageiros' => [
                'concluidas' => $praticasPassageirosConcluidas,
                'necessarias' => $config['horas_praticas_passageiros'],
                'percentual' => $config['horas_praticas_passageiros'] > 0 ? min(100, ($praticasPassageirosConcluidas / $config['horas_praticas_passageiros']) * 100) : 0
            ],
            'praticas_combinacao' => [
                'concluidas' => $praticasCombinacaoConcluidas,
                'necessarias' => $config['horas_praticas_combinacao'],
                'percentual' => $config['horas_praticas_combinacao'] > 0 ? min(100, ($praticasCombinacaoConcluidas / $config['horas_praticas_combinacao']) * 100) : 0
            ]
        ];
    }
    
    // CORREÇÃO 2025-12: Atualizar contagem de teóricas usando turma_presencas
    // Contar cada presença individual, não por disciplina única
    // Cada registro em turma_presencas com presente=1 representa uma aula teórica concluída
    $presencasTeoricasParaProgresso = $db->fetchAll("
        SELECT tp.id, taa.disciplina
        FROM turma_presencas tp
        INNER JOIN turma_aulas_agendadas taa ON tp.turma_aula_id = taa.id
        WHERE tp.aluno_id = ?
        AND tp.presente = 1
        AND taa.status IN ('agendada', 'realizada')
    ", [$alunoId]);
    
    // Contar cada presença individual (cada aula assistida)
    $totalDisciplinasTeoricas = count($presencasTeoricasParaProgresso);
    
    // Atualizar progresso detalhado com contagem real de teóricas
    foreach ($progressoDetalhado as $categoria => $dados) {
        if (isset($progressoDetalhado[$categoria]['teoricas'])) {
            // Usar contagem real de presenças teóricas
            $progressoDetalhado[$categoria]['teoricas']['concluidas'] = $totalDisciplinasTeoricas;
            // Recalcular percentual
            $necessarias = $progressoDetalhado[$categoria]['teoricas']['necessarias'] ?? 0;
            if ($necessarias > 0) {
                $progressoDetalhado[$categoria]['teoricas']['percentual'] = min(100, ($totalDisciplinasTeoricas / $necessarias) * 100);
            }
        }
    }
    
    // Contar aulas práticas concluídas por tipo para categorias mudanca_categorias
    foreach ($aulas as $aula) {
        if ($aula['status'] === 'concluida') {
            // CORREÇÃO 2025-12: Teóricas já foram processadas acima usando turma_presencas
            // Agora só processar práticas
            if ($aula['tipo_aula'] === 'pratica') {
                $tipoVeiculo = $aula['tipo_veiculo'] ?? 'carro';
                // Mapear tipo de veículo para categoria
                $categoriaVeiculo = '';
                switch ($tipoVeiculo) {
                    case 'moto':
                        $categoriaVeiculo = 'A';
                        break;
                    case 'carro':
                        $categoriaVeiculo = 'B';
                        break;
                    case 'carga':
                        $categoriaVeiculo = 'C';
                        break;
                    case 'passageiros':
                        $categoriaVeiculo = 'D';
                        break;
                    case 'combinacao':
                        $categoriaVeiculo = 'E';
                        break;
                }
                
                // Adicionar à categoria específica se existir
                if (isset($progressoDetalhado[$categoriaVeiculo])) {
                    $campoPraticas = "praticas_{$tipoVeiculo}";
                    if (isset($progressoDetalhado[$categoriaVeiculo][$campoPraticas])) {
                        $progressoDetalhado[$categoriaVeiculo][$campoPraticas]['concluidas']++;
                    }
                }
            }
        }
    }
    
    // Calcular percentuais para cada categoria
    foreach ($progressoDetalhado as $categoria => $dados) {
        foreach ($dados as $tipo => $info) {
            if ($tipo !== 'config' && $info['necessarias'] > 0) {
                $progressoDetalhado[$categoria][$tipo]['percentual'] = min(100, ($info['concluidas'] / $info['necessarias']) * 100);
            }
        }
    }
} else {
    // Para categoria única, usar função centralizada para garantir consistência
    $configuracaoCategoria = $configManager->getConfiguracaoByCategoria($categoriaAluno);
    
    if ($configuracaoCategoria) {
        // REGRA: Usar função centralizada para garantir consistência
        $aulasNecessarias = $cargaCategoria['total_aulas_praticas'];
        $aulasTeoricasNecessarias = $cargaCategoria['total_horas_teoricas'];
        
        // CORREÇÃO 2025-12: $aulasTeoricasConcluidas já foi calculado usando turma_presencas acima
        // Usar o valor já calculado corretamente
        
        $progressoDetalhado = [
            'teoricas' => [
                'concluidas' => $aulasTeoricasConcluidas, // Já calculado usando turma_presencas
                'necessarias' => $aulasTeoricasNecessarias,
                'percentual' => $aulasTeoricasNecessarias > 0 ? min(100, ($aulasTeoricasConcluidas / $aulasTeoricasNecessarias) * 100) : 0
            ],
            'praticas_moto' => [
                'concluidas' => $aulasPraticasPorTipo['moto'],
                'necessarias' => $configuracaoCategoria['horas_praticas_moto'],
                'percentual' => $configuracaoCategoria['horas_praticas_moto'] > 0 ? min(100, ($aulasPraticasPorTipo['moto'] / $configuracaoCategoria['horas_praticas_moto']) * 100) : 0
            ],
            'praticas_carro' => [
                'concluidas' => $aulasPraticasPorTipo['carro'],
                'necessarias' => $configuracaoCategoria['horas_praticas_carro'],
                'percentual' => $configuracaoCategoria['horas_praticas_carro'] > 0 ? min(100, ($aulasPraticasPorTipo['carro'] / $configuracaoCategoria['horas_praticas_carro']) * 100) : 0
            ],
            'praticas_carga' => [
                'concluidas' => $aulasPraticasPorTipo['carga'],
                'necessarias' => $configuracaoCategoria['horas_praticas_carga'],
                'percentual' => $configuracaoCategoria['horas_praticas_carga'] > 0 ? min(100, ($aulasPraticasPorTipo['carga'] / $configuracaoCategoria['horas_praticas_carga']) * 100) : 0
            ],
            'praticas_passageiros' => [
                'concluidas' => $aulasPraticasPorTipo['passageiros'],
                'necessarias' => $configuracaoCategoria['horas_praticas_passageiros'],
                'percentual' => $configuracaoCategoria['horas_praticas_passageiros'] > 0 ? min(100, ($aulasPraticasPorTipo['passageiros'] / $configuracaoCategoria['horas_praticas_passageiros']) * 100) : 0
            ],
            'praticas_combinacao' => [
                'concluidas' => $aulasPraticasPorTipo['combinacao'],
                'necessarias' => $configuracaoCategoria['horas_praticas_combinacao'],
                'percentual' => $configuracaoCategoria['horas_praticas_combinacao'] > 0 ? min(100, ($aulasPraticasPorTipo['combinacao'] / $configuracaoCategoria['horas_praticas_combinacao']) * 100) : 0
            ]
        ];
    } else {
        // Fallback para valores padrão se não encontrar configuração
        $aulasNecessarias = 25;
        $aulasTeoricasNecessarias = 45;
        // CORREÇÃO 2025-12: $aulasTeoricasConcluidas já foi calculado usando turma_presencas acima
        $progressoDetalhado = [
            'teoricas' => [
                'concluidas' => $aulasTeoricasConcluidas, // Já calculado usando turma_presencas
                'necessarias' => $aulasTeoricasNecessarias,
                'percentual' => $aulasTeoricasNecessarias > 0 ? min(100, ($aulasTeoricasConcluidas / $aulasTeoricasNecessarias) * 100) : 0
            ],
            'praticas_moto' => [
                'concluidas' => 0,
                'necessarias' => 0,
                'percentual' => 0
            ],
            'praticas_carro' => [
                'concluidas' => $aulasPraticasConcluidas,
                'necessarias' => $aulasNecessarias,
                'percentual' => $aulasNecessarias > 0 ? min(100, ($aulasPraticasConcluidas / $aulasNecessarias) * 100) : 0
            ],
            'praticas_carga' => [
                'concluidas' => 0,
                'necessarias' => 0,
                'percentual' => 0
            ],
            'praticas_passageiros' => [
                'concluidas' => 0,
                'necessarias' => 0,
                'percentual' => 0
            ],
            'praticas_combinacao' => [
                'concluidas' => 0,
                'necessarias' => 0,
                'percentual' => 0
            ]
        ];
    }
}

$progressoPercentual = min(100, ($aulasConcluidas / $aulasNecessarias) * 100);

// Buscar última aula
$ultimaAula = null;
if ($aulas) {
    $ultimaAula = $aulas[0];
}

// Buscar próximas aulas
$proximasAulas = $db->fetchAll("
    SELECT a.*, i.credencial, COALESCE(u.nome, i.nome) as instrutor_nome, v.placa
    FROM aulas a
    LEFT JOIN instrutores i ON a.instrutor_id = i.id
    LEFT JOIN usuarios u ON i.usuario_id = u.id
    LEFT JOIN veiculos v ON a.veiculo_id = v.id
    WHERE a.aluno_id = ? AND a.data_aula >= CURDATE() AND a.status = 'agendada'
    ORDER BY a.data_aula ASC, a.hora_inicio ASC
    LIMIT 5
", [$alunoId]);
?>

<!-- Conteúdo da página de histórico do aluno -->

<div class="container-fluid">
        <!-- Header -->
        <div class="row bg-primary text-white p-3 mb-4">
            <div class="col">
                <h1 class="h3 mb-0">
                    <i class="fas fa-history me-2"></i>
                    Histórico do Aluno
                </h1>
            </div>
            <div class="col-auto">
                <a href="index.php?page=alunos" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-2"></i>Voltar
                </a>
            </div>
        </div>

        <!-- Informações do Aluno -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0" style="color: #6c757d !important;">
                            <i class="fas fa-user-graduate me-2"></i>
                            Informações do Aluno
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Nome:</strong> <?php echo htmlspecialchars($alunoData['nome']); ?></p>
                                <p><strong>CPF:</strong> <?php echo htmlspecialchars($alunoData['cpf']); ?></p>
                                <p><strong>Categoria CNH:</strong> 
                                    <?php 
                                    // REGRA DE PADRONIZAÇÃO: Priorizar categoria da matrícula ativa quando existir
                                    $categoriaExibicao = !empty($alunoData['categoria_cnh_matricula']) 
                                        ? $alunoData['categoria_cnh_matricula'] 
                                        : $alunoData['categoria_cnh'];
                                    $badgeClass = !empty($alunoData['categoria_cnh_matricula']) ? 'bg-primary' : 'bg-secondary';
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($categoriaExibicao); ?></span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>CFC:</strong> <?php echo htmlspecialchars($cfcData['nome'] ?? 'Não informado'); ?></p>
                                <p><strong>Status:</strong> 
                                    <span class="badge bg-<?php echo $alunoData['status'] === 'ativo' ? 'success' : ($alunoData['status'] === 'concluido' ? 'info' : 'secondary'); ?>">
                                        <?php echo ucfirst(htmlspecialchars($alunoData['status'])); ?>
                                    </span>
                                </p>
                                <p><strong>Data de Nascimento:</strong> 
                                    <?php echo $alunoData['data_nascimento'] ? date('d/m/Y', strtotime($alunoData['data_nascimento'])) : 'Não informado'; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cogs me-2"></i>
                            Configuração da Categoria
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($ehCategoriaCombinada): ?>
                        <!-- Exibição para categorias mudanca_categorias -->
                        <div class="text-center mb-3">
                            <h6 class="text-warning mb-2">
                                <i class="fas fa-layer-group me-2"></i>
                                Categoria Combinada: <?php echo htmlspecialchars($categoriaAluno); ?>
                            </h6>
                            <span class="badge bg-warning text-dark fs-6">
                                <?php echo htmlspecialchars($categoriaAluno); ?>
                            </span>
                        </div>
                        
                        <!-- Disciplinas Teóricas Compartilhadas (exibidas apenas uma vez para categorias mudanca_categorias) -->
                        <?php 
                        // Pegar a primeira configuração para obter as disciplinas teóricas
                        $primeiraConfig = reset($configuracoesCategorias);
                        if ($primeiraConfig['horas_teoricas'] > 0): 
                        ?>
                        <div class="border rounded p-3 mb-3 bg-light">
                            <h6 class="text-info mb-2">
                                <i class="fas fa-list me-1"></i>
                                Disciplinas Teóricas (Compartilhadas)
                            </h6>
                            <div class="row">
                                <?php 
                                $disciplinas = [
                                    'legislacao_transito_aulas' => ['nome' => 'Legislação de Trânsito', 'icone' => 'fas fa-gavel', 'cor' => 'primary'],
                                    'primeiros_socorros_aulas' => ['nome' => 'Primeiros Socorros', 'icone' => 'fas fa-first-aid', 'cor' => 'danger'],
                                    'meio_ambiente_cidadania_aulas' => ['nome' => 'Meio Ambiente e Cidadania', 'icone' => 'fas fa-leaf', 'cor' => 'success'],
                                    'direcao_defensiva_aulas' => ['nome' => 'Direção Defensiva', 'icone' => 'fas fa-shield-alt', 'cor' => 'warning'],
                                    'mecanica_basica_aulas' => ['nome' => 'Mecânica Básica', 'icone' => 'fas fa-tools', 'cor' => 'info']
                                ];
                                
                                foreach ($disciplinas as $campo => $info):
                                    $aulasDisciplina = $primeiraConfig[$campo] ?? 0;
                                    if ($aulasDisciplina > 0):
                                ?>
                                <div class="col-12 mb-1">
                                    <div class="d-flex justify-content-between align-items-center p-1 border rounded bg-white">
                                        <div class="d-flex align-items-center">
                                            <i class="<?php echo $info['icone']; ?> text-<?php echo $info['cor']; ?> me-2" style="font-size: 0.8em;"></i>
                                            <span class="fw-medium" style="font-size: 0.9em;"><?php echo $info['nome']; ?></span>
                                        </div>
                                        <span class="badge bg-<?php echo $info['cor']; ?>" style="font-size: 0.7em;">
                                            <?php echo $aulasDisciplina; ?> aulas
                                        </span>
                                    </div>
                                </div>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php foreach ($configuracoesCategorias as $categoria => $config): ?>
                        <div class="border rounded p-3 mb-3">
                            <h6 class="text-primary mb-2">
                                <i class="fas fa-certificate me-1"></i>
                                Categoria <?php echo $categoria; ?>: <?php echo htmlspecialchars($config['nome']); ?>
                            </h6>
                            
                            <div class="row text-center">
                                <div class="col-12">
                                    <h5 class="text-success mb-1">
                                        <i class="fas fa-car me-1"></i>
                                        <?php echo $config['horas_praticas_total']; ?> aulas
                                    </h5>
                                    <small class="text-muted">Práticas</small>
                                </div>
                            </div>
                            
                            <!-- Detalhamento Prático -->
                            <div class="mt-2">
                                <h6 class="text-success mb-2">
                                    <i class="fas fa-car me-1"></i>
                                    Detalhamento Prático
                                </h6>
                                <div class="row">
                                    <?php 
                                    $tiposVeiculo = [
                                        'moto' => ['nome' => 'Motocicleta', 'icone' => 'fas fa-motorcycle', 'cor' => 'warning'],
                                        'carro' => ['nome' => 'Automóvel', 'icone' => 'fas fa-car', 'cor' => 'primary'],
                                        'carga' => ['nome' => 'Caminhão', 'icone' => 'fas fa-truck', 'cor' => 'info'],
                                        'passageiros' => ['nome' => 'Ônibus', 'icone' => 'fas fa-bus', 'cor' => 'success'],
                                        'combinacao' => ['nome' => 'Carreta', 'icone' => 'fas fa-truck-moving', 'cor' => 'secondary']
                                    ];
                                    
                                    foreach ($tiposVeiculo as $tipo => $info):
                                        $campoAulas = "horas_praticas_{$tipo}";
                                        $aulasTipo = $config[$campoAulas] ?? 0;
                                        if ($aulasTipo > 0):
                                    ?>
                                    <div class="col-12 mb-1">
                                        <div class="d-flex justify-content-between align-items-center p-1 border rounded bg-light">
                                            <div class="d-flex align-items-center">
                                                <i class="<?php echo $info['icone']; ?> text-<?php echo $info['cor']; ?> me-2" style="font-size: 0.8em;"></i>
                                                <span class="fw-medium" style="font-size: 0.9em;"><?php echo $info['nome']; ?></span>
                                            </div>
                                            <span class="badge bg-<?php echo $info['cor']; ?>" style="font-size: 0.7em;">
                                                <?php echo $aulasTipo; ?> aulas
                                            </span>
                                        </div>
                                    </div>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                            </div>
                            
                        </div>
                        <?php endforeach; ?>
                        
                        <?php elseif ($configuracaoCategoria): ?>
                        <!-- Exibição para categoria única -->
                        <div class="text-center mb-3">
                            <h6 class="text-warning mb-2">
                                <i class="fas fa-layer-group me-2"></i>
                                <?php echo htmlspecialchars($configuracaoCategoria['nome']); ?>
                            </h6>
                            <span class="badge bg-warning text-dark fs-6">
                                Categoria <?php 
                                // REGRA DE PADRONIZAÇÃO: Priorizar categoria da matrícula ativa quando existir
                                $categoriaExibicao = !empty($alunoData['categoria_cnh_matricula']) 
                                    ? $alunoData['categoria_cnh_matricula'] 
                                    : $alunoData['categoria_cnh'];
                                echo htmlspecialchars($categoriaExibicao); 
                                ?>
                            </span>
                        </div>
                        
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border-end">
                                    <h5 class="text-info mb-1">
                                        <i class="fas fa-book me-1"></i>
                                        <?php echo $configuracaoCategoria['horas_teoricas']; ?> aulas
                                    </h5>
                                    <small class="text-muted">Teóricas</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <h5 class="text-success mb-1">
                                    <i class="fas fa-car me-1"></i>
                                    <?php echo $configuracaoCategoria['horas_praticas_total']; ?> aulas
                                </h5>
                                <small class="text-muted">Práticas</small>
                            </div>
                        </div>
                        
                        <!-- Detalhamento das Disciplinas Teóricas -->
                        <?php if ($configuracaoCategoria['horas_teoricas'] > 0): ?>
                        <div class="mt-3">
                            <h6 class="text-info mb-2">
                                <i class="fas fa-list me-1"></i>
                                Disciplinas Teóricas
                            </h6>
                            <div class="row">
                                <?php 
                                $disciplinas = [
                                    'legislacao_transito_aulas' => ['nome' => 'Legislação de Trânsito', 'icone' => 'fas fa-gavel', 'cor' => 'primary'],
                                    'primeiros_socorros_aulas' => ['nome' => 'Primeiros Socorros', 'icone' => 'fas fa-first-aid', 'cor' => 'danger'],
                                    'meio_ambiente_cidadania_aulas' => ['nome' => 'Meio Ambiente e Cidadania', 'icone' => 'fas fa-leaf', 'cor' => 'success'],
                                    'direcao_defensiva_aulas' => ['nome' => 'Direção Defensiva', 'icone' => 'fas fa-shield-alt', 'cor' => 'warning'],
                                    'mecanica_basica_aulas' => ['nome' => 'Mecânica Básica', 'icone' => 'fas fa-tools', 'cor' => 'info']
                                ];
                                
                                foreach ($disciplinas as $campo => $info):
                                    $aulasDisciplina = $configuracaoCategoria[$campo] ?? 0;
                                    if ($aulasDisciplina > 0):
                                ?>
                                <div class="col-12 mb-2">
                                    <div class="d-flex justify-content-between align-items-center p-2 border rounded bg-light">
                                        <div class="d-flex align-items-center">
                                            <i class="<?php echo $info['icone']; ?> text-<?php echo $info['cor']; ?> me-2"></i>
                                            <span class="fw-medium"><?php echo $info['nome']; ?></span>
                                        </div>
                                        <span class="badge bg-<?php echo $info['cor']; ?>">
                                            <?php echo $aulasDisciplina; ?> aulas
                                        </span>
                                    </div>
                                </div>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <strong>Detalhamento Prático:</strong><br>
                                <?php if ($configuracaoCategoria['horas_praticas_moto'] > 0): ?>
                                    🏍️ Motocicletas: <?php echo $configuracaoCategoria['horas_praticas_moto']; ?> aulas<br>
                                <?php endif; ?>
                                <?php if ($configuracaoCategoria['horas_praticas_carro'] > 0): ?>
                                    🚗 Automóveis: <?php echo $configuracaoCategoria['horas_praticas_carro']; ?> aulas<br>
                                <?php endif; ?>
                                <?php if ($configuracaoCategoria['horas_praticas_carga'] > 0): ?>
                                    🚛 Carga: <?php echo $configuracaoCategoria['horas_praticas_carga']; ?> aulas<br>
                                <?php endif; ?>
                                <?php if ($configuracaoCategoria['horas_praticas_passageiros'] > 0): ?>
                                    🚌 Passageiros: <?php echo $configuracaoCategoria['horas_praticas_passageiros']; ?> aulas<br>
                                <?php endif; ?>
                                <?php if ($configuracaoCategoria['horas_praticas_combinacao'] > 0): ?>
                                    🚛+🚗 Combinação: <?php echo $configuracaoCategoria['horas_praticas_combinacao']; ?> aulas
                                <?php endif; ?>
                            </small>
                        </div>
                        <?php else: ?>
                        <div class="text-center">
                            <i class="fas fa-exclamation-triangle fa-2x text-warning mb-2"></i>
                            <p class="text-muted mb-0">Configuração não encontrada</p>
                            <small class="text-muted">Categoria: <?php 
                            // REGRA DE PADRONIZAÇÃO: Priorizar categoria da matrícula ativa quando existir
                            $categoriaExibicao = !empty($alunoData['categoria_cnh_matricula']) 
                                ? $alunoData['categoria_cnh_matricula'] 
                                : $alunoData['categoria_cnh'];
                            echo htmlspecialchars($categoriaExibicao); 
                            ?></small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Geral -->
        <?php if ($progressoDetalhado): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0" style="color: #000000 !important; font-weight: 700 !important;">
                            <i class="fas fa-calculator me-2"></i>
                            Total Geral - Todas as Categorias
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Calcular totais gerais
                        $totalTeoricasGeral = 0;
                        $totalTeoricasConcluidasGeral = 0;
                        $totalPraticasGeral = 0;
                        $totalPraticasConcluidasGeral = 0;
                        
                        if ($ehCategoriaCombinada) {
                            // REGRA: Usar função centralizada para garantir teoria única e práticas somadas
                            $totalTeoricasGeral = $cargaCategoria['total_horas_teoricas']; // Teoria única (não somada)
                            $totalPraticasGeral = $cargaCategoria['total_aulas_praticas']; // Práticas somadas (ex: 40)
                            
                            // CORREÇÃO 2025-12: Para teóricas, NÃO somar - teoria é única e compartilhada
                            // Pegar o valor de qualquer categoria (todas têm o mesmo valor)
                            $primeiraCategoria = array_key_first($configuracoesCategorias);
                            $totalTeoricasConcluidasGeral = $progressoDetalhado[$primeiraCategoria]['teoricas']['concluidas'] ?? $aulasTeoricasConcluidas;
                            
                            // Somar apenas práticas (que são por categoria)
                            foreach ($configuracoesCategorias as $categoria => $config) {
                                foreach (['praticas_moto', 'praticas_carro', 'praticas_carga', 'praticas_passageiros', 'praticas_combinacao'] as $tipo) {
                                    $totalPraticasConcluidasGeral += $progressoDetalhado[$categoria][$tipo]['concluidas'];
                                }
                            }
                        } else {
                            // Categoria simples: usar valores da função centralizada ou configuração direta
                            if ($configuracaoCategoria) {
                                $totalTeoricasGeral = $cargaCategoria['total_horas_teoricas'];
                                $totalTeoricasConcluidasGeral = $progressoDetalhado['teoricas']['concluidas'];
                                $totalPraticasGeral = $cargaCategoria['total_aulas_praticas'];
                                $totalPraticasConcluidasGeral = $aulasPraticasConcluidas;
                            } else {
                                // Fallback para valores padrão
                                $totalTeoricasGeral = 45;
                                $totalTeoricasConcluidasGeral = $progressoDetalhado['teoricas']['concluidas'];
                                $totalPraticasGeral = 25;
                                $totalPraticasConcluidasGeral = $aulasPraticasConcluidas;
                            }
                        }
                        
                        $percentualTeoricasGeral = $totalTeoricasGeral > 0 ? min(100, ($totalTeoricasConcluidasGeral / $totalTeoricasGeral) * 100) : 0;
                        $percentualPraticasGeral = $totalPraticasGeral > 0 ? min(100, ($totalPraticasConcluidasGeral / $totalPraticasGeral) * 100) : 0;
                        
                        // Log final para debug
                        error_log('[DEBUG HISTORICO FINAL] categoria: ' . $categoriaAluno . ', ' . json_encode([
                            'totalHorasTeoricas' => $totalTeoricasGeral,
                            'totalAulasPraticas' => $totalPraticasGeral,
                            'totalHorasNecessarias' => $totalTeoricasGeral + $totalPraticasGeral
                        ]));
                        ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="border rounded p-3 mb-3">
                                    <h6 class="text-info mb-3">
                                        <i class="fas fa-book me-2"></i>
                                        Total Aulas Teóricas
                                    </h6>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold">Progresso Geral</span>
                                        <span class="badge bg-info fs-6" id="total-aulas-teoricas-badge">
                                            <?php echo $totalTeoricasConcluidasGeral; ?>/<?php echo $totalTeoricasGeral; ?>
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-info" role="progressbar" 
                                             id="total-aulas-teoricas-progress"
                                             style="width: <?php echo $percentualTeoricasGeral; ?>%">
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        Total necessário: <?php echo $totalTeoricasGeral; ?> aulas teóricas
                                    </small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="border rounded p-3 mb-3">
                                    <h6 class="text-success mb-3">
                                        <i class="fas fa-car me-2"></i>
                                        Total Aulas Práticas
                                    </h6>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold">Progresso Geral</span>
                                        <span class="badge bg-success fs-6">
                                            <?php echo $totalPraticasConcluidasGeral; ?>/<?php echo $totalPraticasGeral; ?>
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?php echo $percentualPraticasGeral; ?>%">
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        Total necessário: <?php echo $totalPraticasGeral; ?>h práticas
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="border rounded p-3 bg-light">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-chart-pie me-2"></i>
                                    Resumo Geral
                                </h6>
                                <div class="row text-center">
                                    <div class="col-md-3">
                                        <div class="p-2">
                                            <h4 class="text-primary mb-1"><?php echo $totalTeoricasGeral + $totalPraticasGeral; ?></h4>
                                            <small class="text-muted">Total de Horas Necessárias</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-2">
                                            <h4 class="text-success mb-1" id="horas-concluidas-total"><?php echo $totalTeoricasConcluidasGeral + $totalPraticasConcluidasGeral; ?></h4>
                                            <small class="text-muted">Horas Concluídas</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-2">
                                            <h4 class="text-warning mb-1" id="horas-restantes-total"><?php echo ($totalTeoricasGeral + $totalPraticasGeral) - ($totalTeoricasConcluidasGeral + $totalPraticasConcluidasGeral); ?></h4>
                                            <small class="text-muted">Horas Restantes</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-2">
                                            <h4 class="text-info mb-1" id="progresso-geral-percentual"><?php echo number_format((($totalTeoricasConcluidasGeral + $totalPraticasConcluidasGeral) / ($totalTeoricasGeral + $totalPraticasGeral)) * 100, 1); ?>%</h4>
                                            <small class="text-muted">Progresso Geral</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Exames DETRAN -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="card-title mb-0" style="color: #6c757d !important;">
                            <i class="fas fa-stethoscope me-2"></i>
                            Exames (DETRAN)
                            <?php if ($examesOK): ?>
                                <span class="badge bg-success ms-2">
                                    <i class="fas fa-check-circle me-1"></i>OK
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning ms-2">
                                    <i class="fas fa-clock me-1"></i>Pendente
                                </span>
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Exame Médico -->
                            <div class="col-md-6">
                                <div class="card border-<?php echo $exameMedico && $exameMedico['resultado'] === 'apto' ? 'success' : ($exameMedico && $exameMedico['resultado'] === 'inapto' ? 'danger' : 'warning'); ?>">
                                    <div class="card-header bg-light">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-user-md me-2"></i>
                                            Exame Médico
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($exameMedico): ?>
                                            <?php 
                                            // Usar função helper centralizada para renderizar badges
                                            $badgesMedico = renderizarBadgesExame($exameMedico);
                                            ?>
                                            <!-- Status Badge (Principal) -->
                                            <div class="mb-2">
                                                <?php echo $badgesMedico['status_badge']; ?>
                                            </div>
                                            
                                            <!-- Resultado Badge (Secundária) -->
                                            <div class="mb-2">
                                                <?php echo $badgesMedico['resultado_badge']; ?>
                                            </div>
                                            
                                            <!-- Informações -->
                                            <div class="small text-muted">
                                                <?php if ($exameMedico['data_agendada']): ?>
                                                    <p><strong>Agendado:</strong> <?php echo date('d/m/Y H:i', strtotime($exameMedico['data_agendada'])); ?></p>
                                                <?php endif; ?>
                                                <?php if ($exameMedico['clinica_nome']): ?>
                                                    <p><strong>Clínica:</strong> <?php echo htmlspecialchars($exameMedico['clinica_nome']); ?></p>
                                                <?php endif; ?>
                                                <?php if ($exameMedico['protocolo']): ?>
                                                    <p><strong>Protocolo:</strong> <?php echo htmlspecialchars($exameMedico['protocolo']); ?></p>
                                                <?php endif; ?>
                                                <?php if ($exameMedico['data_resultado']): ?>
                                                    <p><strong>Resultado em:</strong> <?php echo date('d/m/Y', strtotime($exameMedico['data_resultado'])); ?></p>
                                                <?php endif; ?>
                                                <?php if ($exameMedico['observacoes']): ?>
                                                    <p><strong>Observações:</strong> <?php echo htmlspecialchars($exameMedico['observacoes']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Ações -->
                                            <?php 
                                            // Mostrar botões apenas se exame está agendado E não tem resultado lançado
                                            $podeLancarResultado = ($exameMedico['status'] === 'agendado' || $exameMedico['status'] === 'concluido') 
                                                                   && !$badgesMedico['tem_resultado'] 
                                                                   && ($isAdmin || $isSecretaria);
                                            ?>
                                            <?php if ($podeLancarResultado): ?>
                                                <div class="mt-3">
                                                    <a href="index.php?page=exames&tipo=medico&exame_id=<?php echo (int)$exameMedico['id']; ?>&origem=historico" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit me-1"></i>Lançar Resultado
                                                    </a>
                                                    <?php if ($exameMedico['status'] === 'agendado'): ?>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="cancelarExame(<?php echo $exameMedico['id']; ?>)">
                                                            <i class="fas fa-times me-1"></i>Cancelar
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="text-center py-3">
                                                <i class="fas fa-plus-circle fa-2x text-muted mb-2"></i>
                                                <p class="text-muted mb-0">Nenhum exame agendado</p>
                                                <?php if ($isAdmin || $isSecretaria): ?>
                                                    <a href="index.php?page=exames&tipo=medico&aluno_id=<?php echo (int)$alunoId; ?>&origem=historico" 
                                                       class="btn btn-sm btn-primary mt-2 <?php echo !$verificacaoFinanceiraExames['liberado'] ? 'btn-disabled' : ''; ?>"
                                                       data-bloqueado="<?php echo $verificacaoFinanceiraExames['liberado'] ? '0' : '1'; ?>"
                                                       data-motivo="<?php echo htmlspecialchars($verificacaoFinanceiraExames['motivo']); ?>"
                                                       <?php if (!$verificacaoFinanceiraExames['liberado']): ?>style="opacity: 0.6; cursor: not-allowed;"<?php endif; ?>>
                                                        <i class="fas fa-plus me-1"></i>Agendar Exame
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Exame Psicotécnico -->
                            <div class="col-md-6">
                                <div class="card border-<?php echo $examePsicotecnico && $examePsicotecnico['resultado'] === 'apto' ? 'success' : ($examePsicotecnico && $examePsicotecnico['resultado'] === 'inapto' ? 'danger' : 'warning'); ?>">
                                    <div class="card-header bg-light">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-brain me-2"></i>
                                            Exame Psicotécnico
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($examePsicotecnico): ?>
                                            <?php 
                                            // Usar função helper centralizada para renderizar badges
                                            $badgesPsicotecnico = renderizarBadgesExame($examePsicotecnico);
                                            ?>
                                            <!-- Status Badge (Principal) -->
                                            <div class="mb-2">
                                                <?php echo $badgesPsicotecnico['status_badge']; ?>
                                            </div>
                                            
                                            <!-- Resultado Badge (Secundária) -->
                                            <div class="mb-2">
                                                <?php echo $badgesPsicotecnico['resultado_badge']; ?>
                                            </div>
                                            
                                            <!-- Informações -->
                                            <div class="small text-muted">
                                                <?php if ($examePsicotecnico['data_agendada']): ?>
                                                    <p><strong>Agendado:</strong> <?php echo date('d/m/Y H:i', strtotime($examePsicotecnico['data_agendada'])); ?></p>
                                                <?php endif; ?>
                                                <?php if ($examePsicotecnico['clinica_nome']): ?>
                                                    <p><strong>Clínica:</strong> <?php echo htmlspecialchars($examePsicotecnico['clinica_nome']); ?></p>
                                                <?php endif; ?>
                                                <?php if ($examePsicotecnico['protocolo']): ?>
                                                    <p><strong>Protocolo:</strong> <?php echo htmlspecialchars($examePsicotecnico['protocolo']); ?></p>
                                                <?php endif; ?>
                                                <?php if ($examePsicotecnico['data_resultado']): ?>
                                                    <p><strong>Resultado em:</strong> <?php echo date('d/m/Y', strtotime($examePsicotecnico['data_resultado'])); ?></p>
                                                <?php endif; ?>
                                                <?php if ($examePsicotecnico['observacoes']): ?>
                                                    <p><strong>Observações:</strong> <?php echo htmlspecialchars($examePsicotecnico['observacoes']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Ações -->
                                            <?php 
                                            // Mostrar botões apenas se exame está agendado E não tem resultado lançado
                                            $podeLancarResultadoPsico = ($examePsicotecnico['status'] === 'agendado' || $examePsicotecnico['status'] === 'concluido') 
                                                                        && !$badgesPsicotecnico['tem_resultado'] 
                                                                        && ($isAdmin || $isSecretaria);
                                            ?>
                                            <?php if ($podeLancarResultadoPsico): ?>
                                                <div class="mt-3">
                                                    <a href="index.php?page=exames&tipo=psicotecnico&exame_id=<?php echo (int)$examePsicotecnico['id']; ?>&origem=historico" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit me-1"></i>Lançar Resultado
                                                    </a>
                                                    <?php if ($examePsicotecnico['status'] === 'agendado'): ?>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="cancelarExame(<?php echo $examePsicotecnico['id']; ?>)">
                                                            <i class="fas fa-times me-1"></i>Cancelar
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="text-center py-3">
                                                <i class="fas fa-plus-circle fa-2x text-muted mb-2"></i>
                                                <p class="text-muted mb-0">Nenhum exame agendado</p>
                                                <?php if ($isAdmin || $isSecretaria): ?>
                                                    <a href="index.php?page=exames&tipo=psicotecnico&aluno_id=<?php echo (int)$alunoId; ?>&origem=historico" 
                                                       class="btn btn-sm btn-primary mt-2 <?php echo !$verificacaoFinanceiraExames['liberado'] ? 'btn-disabled' : ''; ?>"
                                                       data-bloqueado="<?php echo $verificacaoFinanceiraExames['liberado'] ? '0' : '1'; ?>"
                                                       data-motivo="<?php echo htmlspecialchars($verificacaoFinanceiraExames['motivo']); ?>"
                                                       <?php if (!$verificacaoFinanceiraExames['liberado']): ?>style="opacity: 0.6; cursor: not-allowed;"<?php endif; ?>>
                                                        <i class="fas fa-plus me-1"></i>Agendar Exame
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Status Geral dos Exames -->
                        <?php 
                        // =====================================================
                        // VERIFICAÇÃO DE EXAMES PENDENTES
                        // =====================================================
                        // Usar função helper para verificar se há exames pendentes
                        // Esconder completamente o bloco se não houver pendências
                        // =====================================================
                        $badgesMedicoCheck = renderizarBadgesExame($exameMedico);
                        $badgesPsicotecnicoCheck = renderizarBadgesExame($examePsicotecnico);
                        
                        $examesPendentes = [];
                        
                        // Exame médico pendente: não existe OU não tem resultado lançado E não está cancelado
                        if (!$exameMedico) {
                            $examesPendentes[] = 'Falta agendar exame médico';
                        } elseif (!$badgesMedicoCheck['tem_resultado'] && ($exameMedico['status'] ?? '') !== 'cancelado') {
                            $examesPendentes[] = 'Falta lançar resultado do exame médico';
                        }
                        
                        // Exame psicotécnico pendente: não existe OU não tem resultado lançado E não está cancelado
                        if (!$examePsicotecnico) {
                            $examesPendentes[] = 'Falta agendar exame psicotécnico';
                        } elseif (!$badgesPsicotecnicoCheck['tem_resultado'] && ($examePsicotecnico['status'] ?? '') !== 'cancelado') {
                            $examesPendentes[] = 'Falta lançar resultado do exame psicotécnico';
                        }
                        
                        error_log('[EXAMES PENDENTES] Aluno ' . $alunoId . 
                                 ' - Total pendentes: ' . count($examesPendentes) . 
                                 ' - Lista: ' . implode(', ', $examesPendentes));
                        
                        // Só exibir o bloco se houver pendências OU se exames estiverem OK (para mostrar status positivo)
                        // Esta variável será reutilizada no bloco de bloqueios para garantir consistência
                        $temPendencias = !empty($examesPendentes);
                        ?>
                        
                        <?php if ($examesOK): ?>
                            <!-- Exames OK - Status Positivo -->
                            <div class="mt-4 p-3 border rounded bg-success bg-opacity-10 border-success">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-check-circle fa-2x text-success me-3"></i>
                                    <div>
                                        <h6 class="mb-1 text-success">Exames OK</h6>
                                        <small class="text-muted">Aluno apto para prosseguir com aulas teóricas</small>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($temPendencias): ?>
                            <!-- Exames Pendentes - Só aparece se houver pendências -->
                            <div class="mt-4 p-3 border rounded bg-warning bg-opacity-10 border-warning">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-exclamation-triangle fa-2x text-warning me-3"></i>
                                    <div>
                                        <h6 class="mb-1 text-warning">Exames Pendentes</h6>
                                        <small class="text-muted">
                                            <?php foreach ($examesPendentes as $pendente): ?>
                                                • <?php echo htmlspecialchars($pendente); ?><br>
                                            <?php endforeach; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status de Bloqueios -->
        <?php 
        // =====================================================
        // BLOQUEIOS PARA AULAS TEÓRICAS
        // =====================================================
        // Reutilizar a mesma lógica centralizada de exames
        // para garantir consistência entre bloco de exames e bloqueios
        // =====================================================
        
        // Filtrar motivos de bloqueio: remover motivo de exames se exames estiverem OK
        // Usar a mesma variável $examesOK já calculada acima (linha ~254-267)
        // e $temPendencias calculada no bloco "Exames Pendentes" (linha ~1326)
        $motivosBloqueioFiltrados = [];
        $adicionarMotivoExames = false;
        
        foreach ($bloqueioTeorica['motivos_bloqueio'] as $motivo) {
            // Se o motivo é sobre exames, verificar usando a mesma lógica centralizada
            if (stripos($motivo, 'Exames médico e psicotécnico') !== false) {
                // Só adicionar se realmente houver pendência de exames
                // Usar a mesma lógica do bloco "Exames Pendentes"
                // Se exames estão OK, não adicionar o motivo
                if (!$examesOK && $temPendencias) {
                    $motivosBloqueioFiltrados[] = $motivo;
                    $adicionarMotivoExames = true;
                    error_log("[BLOQUEIOS TEORICAS] Aluno {$alunoId} - examesOK=false, temPendencias=true - motivo_exames_adicionado=true");
                } else {
                    error_log("[BLOQUEIOS TEORICAS] Aluno {$alunoId} - examesOK=" . ($examesOK ? 'true' : 'false') . ", temPendencias=" . ($temPendencias ? 'true' : 'false') . " - motivo_exames_adicionado=false");
                }
            } else {
                // Outros motivos (financeiro, documentação, etc.) são mantidos intocados
                $motivosBloqueioFiltrados[] = $motivo;
            }
        }
        
        // Mostrar bloco apenas se houver motivos de bloqueio após filtrar
        $mostrarBlocoBloqueios = !empty($motivosBloqueioFiltrados);
        ?>
        
        <?php if ($mostrarBlocoBloqueios): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Bloqueios para Aulas Teóricas
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning mb-0">
                            <h6 class="alert-heading">
                                <i class="fas fa-lock me-2"></i>
                                Aulas teóricas bloqueadas
                            </h6>
                            <p class="mb-2">O aluno não pode prosseguir com aulas teóricas pelos seguintes motivos:</p>
                            <ul class="mb-0">
                                <?php foreach ($motivosBloqueioFiltrados as $motivo): ?>
                                    <li><?php echo htmlspecialchars($motivo); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            Liberado para Aulas Teóricas
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success mb-0">
                            <h6 class="alert-heading">
                                <i class="fas fa-unlock me-2"></i>
                                Tudo em ordem
                            </h6>
                            <p class="mb-0">O aluno está liberado para prosseguir com aulas teóricas. Exames OK e situação financeira regularizada.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Presença Teórica -->
        <?php
        // Buscar turmas teóricas do aluno
        $turmasTeoricasAluno = $db->fetchAll("
            SELECT 
                tm.id as matricula_id,
                tm.turma_id,
                tm.status as status_matricula,
                tm.data_matricula,
                tm.frequencia_percentual,
                tt.nome as turma_nome,
                tt.curso_tipo,
                tt.data_inicio,
                tt.data_fim,
                tt.status as turma_status
            FROM turma_matriculas tm
            JOIN turmas_teoricas tt ON tm.turma_id = tt.id
            WHERE tm.aluno_id = ?
            AND tm.status IN ('matriculado', 'cursando', 'concluido')
            ORDER BY tm.data_matricula DESC
        ", [$alunoId]);
        
        // Para cada turma, buscar aulas e presenças
        $presencaTeoricaDetalhada = [];
        foreach ($turmasTeoricasAluno as $turma) {
            // AJUSTE 2025-12 - Presenças teóricas alinhadas com turma_presencas (turma_aula_id)
            // Buscar presenças do aluno nesta turma usando JOIN direto para garantir match correto
            $aulasComPresenca = [];
            $totalAulasValidas = 0;
            $totalPresentes = 0;
            
            try {
                // AJUSTE 2025-12 - Buscar presenças usando JOIN direto com turma_aulas_agendadas
                // Debug: Log dos parâmetros
                error_log("[Histórico] Buscando presenças - turma_id: {$turma['turma_id']}, aluno_id: {$alunoId}");
                
                // AJUSTE 2025-12 - Query melhorada com mais campos para debug
                // NOTA: justificativa removida - coluna não existe na tabela turma_presencas
                $presencasComAulas = $db->fetchAll("
                    SELECT 
                        taa.id as aula_id,
                        taa.nome_aula,
                        taa.disciplina,
                        taa.data_aula,
                        taa.hora_inicio,
                        taa.hora_fim,
                        taa.status as aula_status,
                        taa.ordem_global,
                        tp.id as presenca_id,
                        tp.presente,
                        tp.registrado_em,
                        tp.turma_id as presenca_turma_id,
                        tp.turma_aula_id as presenca_turma_aula_id,
                        tp.aluno_id as presenca_aluno_id
                    FROM turma_aulas_agendadas taa
                    LEFT JOIN turma_presencas tp ON (
                        tp.turma_aula_id = taa.id 
                        AND tp.turma_id = taa.turma_id
                        AND tp.aluno_id = ?
                    )
                    WHERE taa.turma_id = ?
                    AND taa.status IN ('agendada', 'realizada')
                    ORDER BY taa.ordem_global ASC
                ", [$alunoId, $turma['turma_id']]);
                
                // Debug: Log detalhado dos resultados
                error_log("[Histórico] Query executada - turma_id: {$turma['turma_id']}, aluno_id: {$alunoId}");
                error_log("[Histórico] Total de linhas retornadas: " . count($presencasComAulas));
                if (count($presencasComAulas) > 0) {
                    $primeiraLinha = $presencasComAulas[0];
                    error_log("[Histórico] Primeira linha - aula_id: {$primeiraLinha['aula_id']}, presenca_id: " . ($primeiraLinha['presenca_id'] ?? 'NULL') . ", presente: " . var_export($primeiraLinha['presente'], true));
                }
                
                error_log("[Histórico] Aulas encontradas: " . count($presencasComAulas));
                
                // Processar resultados
                foreach ($presencasComAulas as $row) {
                    // Contar aulas válidas (todas as aulas retornadas pelo LEFT JOIN)
                    $totalAulasValidas++;
                    
                    $presenca = null;
                    $statusPresenca = 'nao_registrado';
                    
                    // AJUSTE 2025-12 - Verificar se presente não é null (pode ser 0 ou 1)
                    // Debug detalhado do valor de presente
                    $presenteRaw = $row['presente'];
                    $presencaId = $row['presenca_id'] ?? null;
                    $presenteTipo = gettype($presenteRaw);
                    
                    // Debug completo da linha
                    error_log("[Histórico] Processando aula_id: {$row['aula_id']}, presenca_id: " . ($presencaId ?? 'NULL') . ", presente (raw): " . var_export($presenteRaw, true) . ", tipo: {$presenteTipo}, presenca_turma_id: " . ($row['presenca_turma_id'] ?? 'NULL') . ", presenca_turma_aula_id: " . ($row['presenca_turma_aula_id'] ?? 'NULL'));
                    
                    // Verificar se há registro de presença
                    // Critério 1: Se presenca_id existe, há registro (mesmo que presente seja null)
                    // Critério 2: Se presente não é null e não é string vazia, há registro
                    $temRegistro = false;
                    
                    if ($presencaId !== null) {
                        // Se há presenca_id, definitivamente há registro
                        $temRegistro = true;
                        error_log("[Histórico] Presença detectada por presenca_id: {$presencaId}");
                    } elseif ($presenteRaw !== null && $presenteRaw !== '') {
                        // Se presente tem valor (mesmo que seja 0 ou '0'), há registro
                        $temRegistro = true;
                        error_log("[Histórico] Presença detectada por valor de presente: " . var_export($presenteRaw, true));
                    }
                    
                    // Se presente é '0' (string) ou 0 (int), também há registro (ausente)
                    if ($presenteRaw === '0' || $presenteRaw === 0) {
                        $temRegistro = true;
                    }
                    
                    if ($temRegistro) {
                        // Normalizar presente para int (0 ou 1)
                        $presenteInt = (int)$presenteRaw;
                        if ($presenteRaw === null && $presencaId !== null) {
                            // Se presente é null mas há presenca_id, assumir que precisa buscar do banco
                            // Por enquanto, vamos tratar como ausente (0) se presente é null
                            $presenteInt = 0;
                            error_log("[Histórico] AVISO: presenca_id existe mas presente é null, assumindo 0 (ausente)");
                        }
                        
                        $presenca = [
                            'presente' => $presenteInt,
                            'registrado_em' => $row['registrado_em']
                        ];
                        $statusPresenca = ($presenteInt == 1) ? 'presente' : 'ausente';
                        
                        // Contar presentes para cálculo de frequência
                        if ($presenteInt == 1) {
                            $totalPresentes++;
                        }
                        
                        // Debug: Log de presença encontrada
                        error_log("[Histórico] Presença encontrada - aula_id: {$row['aula_id']}, presenca_id: {$presencaId}, presente: {$presenteInt}, status: {$statusPresenca}");
                    } else {
                        // Debug: Log de aula sem presença com detalhes
                        error_log("[Histórico] Aula sem presença - aula_id: {$row['aula_id']}, presenca_id: " . ($presencaId ?? 'NULL') . ", presente: " . var_export($presenteRaw, true) . " (tipo: {$presenteTipo})");
                    }
                    
                    $aulasComPresenca[] = [
                        'aula' => [
                            'aula_id' => $row['aula_id'],
                            'nome_aula' => $row['nome_aula'],
                            'disciplina' => $row['disciplina'],
                            'data_aula' => $row['data_aula'],
                            'hora_inicio' => $row['hora_inicio'],
                            'hora_fim' => $row['hora_fim'],
                            'aula_status' => $row['aula_status'],
                            'ordem_global' => $row['ordem_global']
                        ],
                        'presenca' => $presenca,
                        'status_presenca' => $statusPresenca
                    ];
                }
                
                // Calcular frequência percentual baseado em presenças reais
                $frequenciaCalculada = 0.0;
                if ($totalAulasValidas > 0) {
                    $frequenciaCalculada = ($totalPresentes / $totalAulasValidas) * 100;
                    $frequenciaCalculada = round($frequenciaCalculada, 1);
                }
                
                // Debug: Log do cálculo
                error_log("[Histórico] Frequência calculada - presentes: {$totalPresentes}, total: {$totalAulasValidas}, percentual: {$frequenciaCalculada}%");
                
                // Atualizar frequência na turma para exibição
                $turma['frequencia_percentual'] = $frequenciaCalculada;
                
            } catch (Exception $e) {
                error_log("Erro ao buscar presenças no histórico: " . $e->getMessage());
                // Fallback: buscar apenas aulas sem presenças
                try {
                    $aulasTurma = $db->fetchAll("
                        SELECT 
                            taa.id as aula_id,
                            taa.nome_aula,
                            taa.disciplina,
                            taa.data_aula,
                            taa.hora_inicio,
                            taa.hora_fim,
                            taa.status as aula_status,
                            taa.ordem_global
                        FROM turma_aulas_agendadas taa
                        WHERE taa.turma_id = ?
                        AND taa.status IN ('agendada', 'realizada')
                        ORDER BY taa.ordem_global ASC
                    ", [$turma['turma_id']]);
                    
                    $totalAulasValidas = count($aulasTurma);
                    foreach ($aulasTurma as $aula) {
                        $aulasComPresenca[] = [
                            'aula' => $aula,
                            'presenca' => null,
                            'status_presenca' => 'nao_registrado'
                        ];
                    }
                } catch (Exception $e2) {
                    error_log("Erro ao buscar aulas no fallback: " . $e2->getMessage());
                }
            }
            
            $presencaTeoricaDetalhada[] = [
                'turma' => $turma,
                'aulas' => $aulasComPresenca
            ];
        }
        
        // Mapear nomes dos cursos
        $nomesCursos = [
            'formacao_45h' => 'Formação 45h',
            'formacao_acc_20h' => 'Formação ACC 20h',
            'reciclagem_infrator' => 'Reciclagem Infrator',
            'atualizacao' => 'Atualização'
        ];
        ?>
        
        <?php if (!empty($presencaTeoricaDetalhada)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-clipboard-check me-2"></i>
                            Presença Teórica
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($presencaTeoricaDetalhada as $item): ?>
                            <?php 
                            $turma = $item['turma'];
                            $aulas = $item['aulas'];
                            $frequencia = (float)($turma['frequencia_percentual'] ?? 0);
                            
                            // AJUSTE 2025-12 - Destacar turma quando vindo do Diário
                            $isTurmaFoco = ($turmaIdFoco && (int)$turmaIdFoco === (int)$turma['turma_id']);
                            $turmaCardClass = $isTurmaFoco ? 'border-primary border-2 shadow-sm' : '';
                            
                            // CORREÇÃO 2025-12: Adicionar data-turma-id para atualização via AJAX
                            $turmaCardId = 'turma-card-' . $turma['turma_id'];
                            
                            // Determinar status da matrícula
                            $statusMatricula = $turma['status_matricula'];
                            $statusLabel = [
                                'matriculado' => 'Matriculado',
                                'cursando' => 'Cursando',
                                'concluido' => 'Concluído',
                                'evadido' => 'Evadido',
                                'transferido' => 'Transferido'
                            ][$statusMatricula] ?? ucfirst($statusMatricula);
                            
                            // Badge de frequência
                            $freqBadgeClass = 'bg-success';
                            if ($frequencia < 75) {
                                $freqBadgeClass = 'bg-danger';
                            } elseif ($frequencia < 90) {
                                $freqBadgeClass = 'bg-warning';
                            }
                            ?>
                            <div class="mb-4 pb-3 border-bottom <?= $turmaCardClass ?>" 
                                 <?= $isTurmaFoco ? 'id="turma-foco"' : '' ?>
                                 data-turma-id="<?php echo (int)$turma['turma_id']; ?>">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="mb-1">
                                            <i class="fas fa-book me-2"></i>
                                            <?php echo htmlspecialchars($turma['turma_nome']); ?>
                                        </h6>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($nomesCursos[$turma['curso_tipo']] ?? $turma['curso_tipo']); ?> | 
                                            <?php echo date('d/m/Y', strtotime($turma['data_inicio'])); ?> - 
                                            <?php echo date('d/m/Y', strtotime($turma['data_fim'])); ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <div>
                                            <span class="badge <?php echo $freqBadgeClass; ?>" 
                                                  data-turma-frequencia="<?php echo (int)$turma['turma_id']; ?>"
                                                  data-turma-id="<?php echo (int)$turma['turma_id']; ?>">
                                                Frequência: <?php echo number_format($frequencia, 1); ?>%
                                            </span>
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            Status: <?php echo $statusLabel; ?>
                                        </small>
                                    </div>
                                </div>
                                
                                <?php if (!empty($aulas)): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Data</th>
                                                <th>Disciplina</th>
                                                <th>Horário</th>
                                                <th>Presença</th>
                                                <?php if ($isAdmin || $isSecretaria): ?>
                                                <th>Ações</th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($aulas as $itemAula): ?>
                                                <?php 
                                                $aula = $itemAula['aula'];
                                                $statusPresenca = $itemAula['status_presenca'];
                                                $presencaId = $itemAula['presenca_id'] ?? null;
                                                $presencaAtual = $itemAula['presenca']['presente'] ?? null;
                                                $turmaIdAula = $itemAula['turma_id'] ?? $turma['turma_id'];
                                                
                                                $presencaBadge = '';
                                                if ($statusPresenca === 'presente') {
                                                    $presencaBadge = '<span class="badge bg-success"><i class="fas fa-check me-1"></i>PRESENTE</span>';
                                                } elseif ($statusPresenca === 'ausente') {
                                                    $presencaBadge = '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>AUSENTE</span>';
                                                } else {
                                                    $presencaBadge = '<span class="badge bg-secondary"><i class="fas fa-minus me-1"></i>NÃO REGISTRADO</span>';
                                                }
                                                
                                                // Nome da disciplina
                                                $nomesDisciplinas = [
                                                    'legislacao_transito' => 'Legislação de Trânsito',
                                                    'direcao_defensiva' => 'Direção Defensiva',
                                                    'primeiros_socorros' => 'Primeiros Socorros',
                                                    'meio_ambiente_cidadania' => 'Meio Ambiente e Cidadania',
                                                    'mecanica_basica' => 'Mecânica Básica'
                                                ];
                                                $disciplinaNome = $nomesDisciplinas[$aula['disciplina']] ?? ucfirst(str_replace('_', ' ', $aula['disciplina']));
                                                ?>
                                                <tr>
                                                    <td><?php echo date('d/m/Y', strtotime($aula['data_aula'])); ?></td>
                                                    <td><?php echo htmlspecialchars($disciplinaNome); ?></td>
                                                    <td>
                                                        <?php echo date('H:i', strtotime($aula['hora_inicio'])); ?> - 
                                                        <?php echo date('H:i', strtotime($aula['hora_fim'])); ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($isAdmin || $isSecretaria): ?>
                                                        <!-- CORREÇÃO 2025-12: Badge clicável para editar presença -->
                                                        <span class="js-editar-presenca-badge d-inline-block" 
                                                              data-turma-id="<?php echo (int)$turmaIdAula; ?>"
                                                              data-turma-aula-id="<?php echo (int)$aula['aula_id']; ?>"
                                                              data-aluno-id="<?php echo (int)$alunoId; ?>"
                                                              data-presenca-id="<?php echo $presencaId ? (int)$presencaId : ''; ?>"
                                                              data-presente="<?php echo ($presencaAtual === 1) ? '1' : ($presencaAtual === 0 ? '0' : ''); ?>"
                                                              data-aula-nome="<?php echo htmlspecialchars($aula['nome_aula']); ?>"
                                                              data-disciplina="<?php echo htmlspecialchars($disciplinaNome); ?>"
                                                              data-data-aula="<?php echo date('d/m/Y', strtotime($aula['data_aula'])); ?>"
                                                              title="Clique para editar presença desta aula">
                                                            <?php echo $presencaBadge; ?>
                                                        </span>
                                                        <?php else: ?>
                                                        <?php echo $presencaBadge; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <?php if ($isAdmin || $isSecretaria): ?>
                                                    <td>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-link text-primary p-0 js-editar-presenca" 
                                                                data-turma-id="<?php echo (int)$turmaIdAula; ?>"
                                                                data-turma-aula-id="<?php echo (int)$aula['aula_id']; ?>"
                                                                data-aluno-id="<?php echo (int)$alunoId; ?>"
                                                                data-presenca-id="<?php echo $presencaId ? (int)$presencaId : ''; ?>"
                                                                data-presente="<?php echo ($presencaAtual === 1) ? '1' : ($presencaAtual === 0 ? '0' : ''); ?>"
                                                                data-aula-nome="<?php echo htmlspecialchars($aula['nome_aula']); ?>"
                                                                data-disciplina="<?php echo htmlspecialchars($disciplinaNome); ?>"
                                                                data-data-aula="<?php echo date('d/m/Y', strtotime($aula['data_aula'])); ?>"
                                                                title="Editar presença">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    </td>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                    <p class="text-muted mb-0"><i class="fas fa-info-circle me-2"></i>Nenhuma aula agendada para esta turma.</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Progresso Detalhado por Categoria -->
        <?php if ($progressoDetalhado): ?>
        <?php
        // Calcular totais gerais ANTES de renderizar o "Progresso Detalhado por Categoria"
        // para que possam ser reutilizados no bloco de resumo teórico
        // (Essas mesmas variáveis são recalculadas no bloco "Total Geral" abaixo, mas precisamos aqui também)
        $totalTeoricasGeralDetalhado = 0;
        $totalTeoricasConcluidasGeralDetalhado = 0;
        $totalPraticasGeralDetalhado = 0;
        $totalPraticasConcluidasGeralDetalhado = 0;
        
        if ($ehCategoriaCombinada) {
            // REGRA: Usar função centralizada para garantir teoria única e práticas somadas
            $totalTeoricasGeralDetalhado = $cargaCategoria['total_horas_teoricas']; // Teoria única (não somada)
            $totalPraticasGeralDetalhado = $cargaCategoria['total_aulas_praticas']; // Práticas somadas (ex: 40)
            
            // CORREÇÃO 2025-12: Para teóricas, NÃO somar - teoria é única e compartilhada
            // Pegar o valor de qualquer categoria (todas têm o mesmo valor)
            $primeiraCategoria = array_key_first($configuracoesCategorias);
            $totalTeoricasConcluidasGeralDetalhado = $progressoDetalhado[$primeiraCategoria]['teoricas']['concluidas'] ?? $aulasTeoricasConcluidas;
            
            // Somar apenas práticas (que são por categoria)
            foreach ($configuracoesCategorias as $categoria => $config) {
                foreach (['praticas_moto', 'praticas_carro', 'praticas_carga', 'praticas_passageiros', 'praticas_combinacao'] as $tipo) {
                    $totalPraticasConcluidasGeralDetalhado += $progressoDetalhado[$categoria][$tipo]['concluidas'];
                }
            }
        } else {
            // Categoria simples: usar valores da função centralizada ou configuração direta
            if ($configuracaoCategoria) {
                $totalTeoricasGeralDetalhado = $cargaCategoria['total_horas_teoricas'];
                $totalTeoricasConcluidasGeralDetalhado = $progressoDetalhado['teoricas']['concluidas'];
                $totalPraticasGeralDetalhado = $cargaCategoria['total_aulas_praticas'];
                $totalPraticasConcluidasGeralDetalhado = $aulasPraticasConcluidas;
            } else {
                // Fallback para valores padrão
                $totalTeoricasGeralDetalhado = 45;
                $totalTeoricasConcluidasGeralDetalhado = $progressoDetalhado['teoricas']['concluidas'];
                $totalPraticasGeralDetalhado = 25;
                $totalPraticasConcluidasGeralDetalhado = $aulasPraticasConcluidas;
            }
        }
        
        $percentualTeoricasGeralDetalhado = $totalTeoricasGeralDetalhado > 0 ? min(100, ($totalTeoricasConcluidasGeralDetalhado / $totalTeoricasGeralDetalhado) * 100) : 0;
        ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0" style="color: #000000 !important; font-weight: 700 !important;">
                            <i class="fas fa-chart-line me-2"></i>
                            Progresso Detalhado por Categoria
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($ehCategoriaCombinada): ?>
                        <?php
                        // Regra de negócio:
                        // - Carga teórica é única para o curso combinado (ex.: AB) -> 45h teóricas no total.
                        // - Carga prática é por categoria (ex.: A = 20 aulas, B = 20 aulas).
                        // - Nesta tela, o resumo teórico é mostrado em um bloco único,
                        //   e o detalhamento por categoria mostra apenas as práticas.
                        
                        // Reutilizar os valores já calculados corretamente no "Total Geral"
                        // Esses valores já garantem teoria única e práticas somadas
                        ?>
                        
                        <!-- Resumo Teórico do Curso (Categorias Combinadas) -->
                        <div class="card card-progresso-teorico mb-3">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold text-primary">
                                            Resumo Teórico do Curso (Categorias combinadas)
                                        </div>
                                        <small class="text-muted">
                                            Carga teórica compartilhada para todas as categorias deste curso combinado.
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold" id="resumo-teorico-curso-badge">
                                            <?php echo $totalTeoricasConcluidasGeralDetalhado; ?> / <?php echo $totalTeoricasGeralDetalhado; ?>
                                        </div>
                                        <small class="text-muted">
                                            Total necessário: <span id="resumo-teorico-curso-total"><?php echo $totalTeoricasGeralDetalhado; ?></span> aulas teóricas
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Progresso para categorias mudanca_categorias -->
                        <?php foreach ($configuracoesCategorias as $categoria => $config): ?>
                        <?php
                        // Calcular práticas concluídas desta categoria
                        $totalPraticasConcluidas = 0;
                        $totalPraticasNecessarias = $config['horas_praticas_total'];
                        foreach (['praticas_moto', 'praticas_carro', 'praticas_carga', 'praticas_passageiros', 'praticas_combinacao'] as $tipo) {
                            $totalPraticasConcluidas += $progressoDetalhado[$categoria][$tipo]['concluidas'];
                        }
                        $percentualPraticas = $totalPraticasNecessarias > 0 ? min(100, ($totalPraticasConcluidas / $totalPraticasNecessarias) * 100) : 0;
                        ?>
                        <div class="card card-progresso-categoria mb-3">
                            <div class="card-body py-3">
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <div class="fw-semibold text-primary">
                                        Categoria <?php echo $categoria; ?>: <?php echo htmlspecialchars($config['nome']); ?>
                                    </div>
                                    <span class="badge bg-light text-primary border">
                                        Aulas práticas (<?php echo $categoria; ?>)
                                    </span>
                                </div>
                                
                                <div class="small text-muted mb-2">
                                    Categoria <?php echo $categoria; ?>: <?php echo $config['horas_praticas_total']; ?> aulas práticas
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small text-muted">Progresso</span>
                                    <span class="badge bg-success">
                                        <?php echo $totalPraticasConcluidas; ?>/<?php echo $totalPraticasNecessarias; ?>
                                    </span>
                                </div>
                                
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php echo $percentualPraticas; ?>%">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <!-- Rodapé com total combinado de práticas -->
                        <div class="text-muted small mt-1 mb-3">
                            Total combinado de aulas práticas para todas as categorias: <?php echo $cargaCategoria['total_aulas_praticas']; ?> aulas.
                        </div>
                        
                        <?php else: ?>
                        <!-- Progresso para categoria única -->
                        <?php if ($configuracaoCategoria): ?>
                        <div class="border rounded p-3 mb-3">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-certificate me-2"></i>
                                <?php echo htmlspecialchars($configuracaoCategoria['nome']); ?>
                            </h6>
                            
                            <!-- Teóricas -->
                            <?php if ($configuracaoCategoria['horas_teoricas'] > 0): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold">
                                        <i class="fas fa-book text-info me-2"></i>
                                        Aulas Teóricas
                                    </span>
                                    <span class="badge bg-info">
                                        <?php echo $progressoDetalhado['teoricas']['concluidas']; ?>/<?php echo $progressoDetalhado['teoricas']['necessarias']; ?>
                                    </span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-info" role="progressbar" 
                                         style="width: <?php echo $progressoDetalhado['teoricas']['percentual']; ?>%">
                                    </div>
                                </div>
                                <small class="text-muted">
                                    Necessário: <?php echo $configuracaoCategoria['horas_teoricas']; ?>h teóricas
                                </small>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Práticas -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold">
                                        <i class="fas fa-car text-success me-2"></i>
                                        Aulas Práticas
                                    </span>
                                    <span class="badge bg-success">
                                        <?php 
                                        // Se não há aulas necessárias calculadas, usar o total da configuração
                                        $totalNecessarias = $aulasNecessarias > 0 ? $aulasNecessarias : $configuracaoCategoria['horas_praticas_total'];
                                        echo $aulasPraticasConcluidas . '/' . $totalNecessarias;
                                        ?>
                                    </span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php 
                                         $totalNecessarias = $aulasNecessarias > 0 ? $aulasNecessarias : $configuracaoCategoria['horas_praticas_total'];
                                         $percentualCorrigido = $totalNecessarias > 0 ? min(100, ($aulasPraticasConcluidas / $totalNecessarias) * 100) : 0;
                                         echo $percentualCorrigido; ?>%">
                                    </div>
                                </div>
                                <small class="text-muted">
                                    Total necessário: <?php echo $configuracaoCategoria['horas_praticas_total']; ?> aulas práticas
                                </small>
                            </div>
                            
                            <!-- Detalhamento por tipo de veículo -->
                            <div class="row">
                                <?php if ($configuracaoCategoria['horas_praticas_moto'] > 0): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-motorcycle text-warning me-2"></i>
                                            <strong>Motocicletas</strong>
                                        </span>
                                        <span class="badge bg-warning">
                                            <?php echo $progressoDetalhado['praticas_moto']['concluidas']; ?>/<?php echo $progressoDetalhado['praticas_moto']['necessarias']; ?>
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-warning" role="progressbar" 
                                             style="width: <?php echo $progressoDetalhado['praticas_moto']['percentual']; ?>%">
                                        </div>
                                    </div>
                                    <small class="text-muted"><?php echo $configuracaoCategoria['horas_praticas_moto']; ?>h necessárias</small>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($configuracaoCategoria['horas_praticas_carro'] > 0): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-car text-primary me-2"></i>
                                            <strong>Automóveis</strong>
                                        </span>
                                        <span class="badge bg-primary">
                                            <?php echo $progressoDetalhado['praticas_carro']['concluidas']; ?>/<?php echo $progressoDetalhado['praticas_carro']['necessarias']; ?>
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-primary" role="progressbar" 
                                             style="width: <?php echo $progressoDetalhado['praticas_carro']['percentual']; ?>%">
                                        </div>
                                    </div>
                                    <small class="text-muted"><?php echo $configuracaoCategoria['horas_praticas_carro']; ?>h necessárias</small>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($configuracaoCategoria['horas_praticas_carga'] > 0): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-truck text-secondary me-2"></i>
                                            <strong>Veículos de Carga</strong>
                                        </span>
                                        <span class="badge bg-secondary">
                                            <?php echo $progressoDetalhado['praticas_carga']['concluidas']; ?>/<?php echo $progressoDetalhado['praticas_carga']['necessarias']; ?>
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-secondary" role="progressbar" 
                                             style="width: <?php echo $progressoDetalhado['praticas_carga']['percentual']; ?>%">
                                        </div>
                                    </div>
                                    <small class="text-muted"><?php echo $configuracaoCategoria['horas_praticas_carga']; ?>h necessárias</small>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($configuracaoCategoria['horas_praticas_passageiros'] > 0): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-bus text-info me-2"></i>
                                            <strong>Veículos de Passageiros</strong>
                                        </span>
                                        <span class="badge bg-info">
                                            <?php echo $progressoDetalhado['praticas_passageiros']['concluidas']; ?>/<?php echo $progressoDetalhado['praticas_passageiros']['necessarias']; ?>
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-info" role="progressbar" 
                                             style="width: <?php echo $progressoDetalhado['praticas_passageiros']['percentual']; ?>%">
                                        </div>
                                    </div>
                                    <small class="text-muted"><?php echo $configuracaoCategoria['horas_praticas_passageiros']; ?>h necessárias</small>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($configuracaoCategoria['horas_praticas_combinacao'] > 0): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-trailer text-dark me-2"></i>
                                            <strong>Combinação de Veículos</strong>
                                        </span>
                                        <span class="badge bg-dark">
                                            <?php echo $progressoDetalhado['praticas_combinacao']['concluidas']; ?>/<?php echo $progressoDetalhado['praticas_combinacao']['necessarias']; ?>
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-dark" role="progressbar" 
                                             style="width: <?php echo $progressoDetalhado['praticas_combinacao']['percentual']; ?>%">
                                        </div>
                                    </div>
                                    <small class="text-muted"><?php echo $configuracaoCategoria['horas_praticas_combinacao']; ?>h necessárias</small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Estatísticas Gerais -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-check fa-2x mb-2"></i>
                        <h4><?php echo $totalTeoricasGeral + $totalPraticasGeral; ?></h4>
                        <p class="mb-0">Total de Horas Necessárias</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h4 id="card-aulas-concluidas"><?php echo $aulasConcluidas; ?></h4>
                        <p class="mb-0">Aulas Concluídas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                        <h4><?php echo $aulasAgendadas; ?></h4>
                        <p class="mb-0">Aulas Agendadas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-times-circle fa-2x mb-2"></i>
                        <h4><?php echo $aulasCanceladas; ?></h4>
                        <p class="mb-0">Aulas Canceladas</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Histórico Completo -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="card-title mb-0" style="color: #6c757d !important; font-weight: 700 !important;">
                            <i class="fas fa-list me-2"></i>
                            Histórico Completo de Aulas
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($aulas): ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="tabelaHistorico">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Horário</th>
                                        <th>Tipo</th>
                                        <th>Instrutor</th>
                                        <th>Veículo</th>
                                        <th>Status</th>
                                        <th>Observações</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // CORREÇÃO 2025-12: Filtrar apenas aulas práticas com dados válidos (sem N/A)
                                    // Histórico Completo de Aulas exibe apenas aulas práticas
                                    $aulasValidas = array_filter($aulas, function($aula) {
                                        // Apenas aulas práticas (tipo_aula = 'pratica' ou não definido como 'teorica')
                                        $tipoAula = $aula['tipo_aula'] ?? 'pratica';
                                        if ($tipoAula === 'teorica') {
                                            return false; // Excluir teóricas
                                        }
                                        
                                        // Verificar se tem ID numérico válido
                                        $id = $aula['id'] ?? null;
                                        $temId = ($id !== null && is_numeric($id) && $id > 0);
                                        
                                        // Verificar se tem data válida
                                        $temData = !empty($aula['data_aula']);
                                        
                                        return $temId && $temData;
                                    });
                                    
                                    if (count($aulasValidas) > 0):
                                        foreach ($aulasValidas as $aula): 
                                    ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($aula['data_aula'])); ?></td>
                                        <td><?php 
                                            $horaInicio = $aula['hora_inicio'] ?? null;
                                            $horaFim = $aula['hora_fim'] ?? null;
                                            if ($horaInicio && $horaFim) {
                                                echo date('H:i', strtotime($horaInicio)) . ' - ' . date('H:i', strtotime($horaFim));
                                            } else {
                                                echo '<span class="text-muted">Não informado</span>';
                                            }
                                        ?></td>
                                        <td>
                                            <?php $tipoAula = $aula['tipo_aula'] ?? 'pratica'; ?>
                                            <span class="badge bg-<?php echo $tipoAula === 'teorica' ? 'info' : 'primary'; ?>">
                                                <?php echo strtoupper($tipoAula === 'teorica' ? 'TEÓRICA' : 'PRÁTICA'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($aula['instrutor_nome']) && $aula['instrutor_nome'] !== 'N/A'): ?>
                                            <div>
                                                <strong><?php echo htmlspecialchars($aula['instrutor_nome']); ?></strong>
                                                <?php if (!empty($aula['credencial']) && $aula['credencial'] !== 'N/A'): ?>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($aula['credencial']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <?php else: ?>
                                            <span class="text-muted">Não informado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $isTeorica = ($aula['tipo_aula'] ?? '') === 'teorica';
                                            if ($isTeorica): 
                                                // Para teóricas, mostrar turma
                                                $turmaNome = $aula['turma_nome'] ?? 'Turma não informada';
                                            ?>
                                            <div>
                                                <strong><?php echo htmlspecialchars($turmaNome); ?></strong>
                                                <?php if (!empty($aula['disciplina'])): ?>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($aula['disciplina']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <?php elseif (!empty($aula['veiculo_id'])): ?>
                                            <div>
                                                <strong><?php echo htmlspecialchars($aula['placa'] ?? 'N/A'); ?></strong>
                                                <?php if (!empty($aula['marca']) || !empty($aula['modelo'])): ?>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars(trim(($aula['marca'] ?? '') . ' ' . ($aula['modelo'] ?? ''))); ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <?php else: ?>
                                            <span class="text-muted">Não aplicável</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status = $aula['status'] ?? 'agendada';
                                            $statusClass = [
                                                'agendada' => 'warning',
                                                'em_andamento' => 'info',
                                                'concluida' => 'success',
                                                'cancelada' => 'danger'
                                            ];
                                            $statusText = [
                                                'agendada' => 'AGENDADA',
                                                'em_andamento' => 'EM ANDAMENTO',
                                                'concluida' => 'CONCLUÍDA',
                                                'cancelada' => 'CANCELADA'
                                            ];
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass[$status] ?? 'secondary'; ?>">
                                                <?php echo $statusText[$status] ?? strtoupper($status); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($aula['observacoes'])): ?>
                                            <span class="text-truncate d-inline-block" style="max-width: 200px;" 
                                                  title="<?php echo htmlspecialchars($aula['observacoes']); ?>">
                                                <?php echo htmlspecialchars($aula['observacoes']); ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="text-muted">Sem observações</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <?php if (!empty($aula['id']) && $aula['id'] > 0): ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        title="Ver detalhes da aula"
                                                        onclick="verDetalhesAula(<?php echo (int)$aula['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if (($aula['status'] ?? '') === 'agendada'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-warning" 
                                                        title="Editar aula"
                                                        onclick="editarAula(<?php echo (int)$aula['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        title="Cancelar aula"
                                                        onclick="cancelarAula(<?php echo (int)$aula['id']; ?>)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                <?php endif; ?>
                                                <?php else: ?>
                                                <span class="text-muted small">Sem ações</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php 
                                        endforeach;
                                    else:
                                    ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-info-circle text-muted me-2"></i>
                                            <span class="text-muted">Nenhuma aula registrada até o momento</span>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Nenhuma aula encontrada</h5>
                            <p class="text-muted">Este aluno ainda não possui aulas registradas no sistema.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Detalhes da Aula -->
    <div class="modal fade" id="modalDetalhesAula" tabindex="-1" aria-labelledby="modalDetalhesAulaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDetalhesAulaLabel">
                        <i class="fas fa-info-circle me-2"></i>Detalhes da Aula
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalDetalhesBody">
                    <!-- Conteúdo será carregado via JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" id="btnEditarAula" style="display: none;">
                        <i class="fas fa-edit me-1"></i>Editar Aula
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Edição de Presença Teórica - Padrão Bootstrap (igual aos outros modais do sistema) -->
    <?php if ($isAdmin || $isSecretaria): ?>
    <div class="modal fade" id="modalEditarPresenca" tabindex="-1" aria-labelledby="modalEditarPresencaLabel" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true" style="display: none;">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarPresencaLabel">
                        <i class="fas fa-edit me-2"></i>Editar Presença
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <p class="mb-1"><strong>Aluno:</strong> <span id="ep-aluno-nome"><?php echo htmlspecialchars($alunoData['nome'] ?? 'N/A'); ?></span></p>
                        <p class="mb-1"><strong>Aula:</strong> <span id="ep-aula-info"></span></p>
                        <p class="mb-3 text-muted small"><strong>Data:</strong> <span id="ep-data-aula"></span></p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status de Presença:</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="ep_presente" id="ep_presente_sim" value="1">
                            <label class="form-check-label" for="ep_presente_sim">
                                <span class="badge bg-success"><i class="fas fa-check me-1"></i>Presente</span>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="ep_presente" id="ep_presente_nao" value="0">
                            <label class="form-check-label" for="ep_presente_nao">
                                <span class="badge bg-danger"><i class="fas fa-times me-1"></i>Ausente</span>
                            </label>
                        </div>
                    </div>
                    
                    <input type="hidden" id="ep-turma-id">
                    <input type="hidden" id="ep-turma-aula-id">
                    <input type="hidden" id="ep-aluno-id">
                    <input type="hidden" id="ep-presenca-id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" id="ep-btn-salvar">
                        <i class="fas fa-save me-1"></i>Salvar
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal de Cancelamento de Aula -->
    <div class="modal fade" id="modalCancelarAula" tabindex="-1" aria-labelledby="modalCancelarAulaLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCancelarAulaLabel">
                        <i class="fas fa-times-circle me-2 text-danger"></i>Cancelar Aula
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="aulaIdCancelar">
                    
                    <div class="mb-3">
                        <label for="motivoCancelamento" class="form-label required">Motivo do Cancelamento:</label>
                        <select class="form-control" id="motivoCancelamento" required>
                            <option value="">Selecione um motivo</option>
                            <option value="aluno_ausente">Aluno ausente</option>
                            <option value="instrutor_indisponivel">Instrutor indisponível</option>
                            <option value="veiculo_quebrado">Veículo quebrado</option>
                            <option value="condicoes_climaticas">Condições climáticas</option>
                            <option value="problema_tecnico">Problema técnico</option>
                            <option value="reagendamento">Reagendamento</option>
                            <option value="outros">Outros</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacoesCancelamento" class="form-label">Observações:</label>
                        <textarea class="form-control" id="observacoesCancelamento" rows="3" placeholder="Digite observações sobre o cancelamento..."></textarea>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Atenção:</strong> Esta ação não pode ser desfeita. A aula será marcada como cancelada.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" onclick="confirmarCancelamento()">
                        <i class="fas fa-times me-1"></i>Confirmar Cancelamento
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if ($isAdmin || $isSecretaria): ?>
    <?php
    // CORREÇÃO 2025-12: Calcular URL da API (mesmo padrão usado em turma-chamada.php)
    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '/admin/index.php';
    $baseRoot = '';
    
    // Detectar caminho base a partir do SCRIPT_NAME
    if (preg_match('#^/([^/]+)/admin/#', $scriptPath, $matches)) {
        $baseRoot = '/' . $matches[1];
    } elseif (strpos($scriptPath, '/admin/') !== false) {
        $parts = explode('/admin/', $scriptPath);
        $baseRoot = $parts[0] ?: '/cfc-bom-conselho';
    } else {
        $baseRoot = '/cfc-bom-conselho'; // Fallback
    }
    
    // Garantir que baseRoot não esteja vazio
    if (empty($baseRoot) || $baseRoot === '/') {
        $baseRoot = '/cfc-bom-conselho';
    }
    
    $apiTurmaPresencasUrl = $baseRoot . '/admin/api/turma-presencas.php';
    ?>
    <script>
        // CORREÇÃO 2025-12: Edição de presença teórica pelo histórico do aluno
        (function() {
            const API_TURMA_PRESENCAS = <?php echo json_encode($apiTurmaPresencasUrl); ?>;
            
            // Log da URL da API para debug
            console.log('[HistoricoPresenca] API URL configurada:', API_TURMA_PRESENCAS);
            
            // Função para mostrar toast
            function mostrarToast(mensagem, tipo = 'success') {
                // Verificar se já existe container de toast
                let toastContainer = document.getElementById('toastContainer');
                if (!toastContainer) {
                    toastContainer = document.createElement('div');
                    toastContainer.id = 'toastContainer';
                    toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
                    toastContainer.style.zIndex = '9999';
                    document.body.appendChild(toastContainer);
                }
                
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
                
                setTimeout(() => {
                    const toastElement = document.getElementById(toastId);
                    if (toastElement) {
                        toastElement.remove();
                    }
                }, 5000);
            }
            
            // Função para abrir modal de edição de presença
            function abrirModalEditarPresenca(dados) {
                const { turmaId, turmaAulaId, alunoId, presencaId, presenteAtual, aulaNome, disciplina, dataAula } = dados;
                
                // Preencher campos do modal
                document.getElementById('ep-turma-id').value = turmaId;
                document.getElementById('ep-turma-aula-id').value = turmaAulaId;
                document.getElementById('ep-aluno-id').value = alunoId;
                document.getElementById('ep-presenca-id').value = presencaId || '';
                document.getElementById('ep-aula-info').textContent = disciplina + (aulaNome ? ' - ' + aulaNome : '');
                document.getElementById('ep-data-aula').textContent = dataAula;
                
                // Marcar radio conforme estado atual
                if (presencaId || presenteAtual !== null) {
                    document.getElementById('ep_presente_sim').checked = (presenteAtual === '1' || presenteAtual === 1);
                    document.getElementById('ep_presente_nao').checked = (presenteAtual === '0' || presenteAtual === 0);
                } else {
                    // Aula sem presença: não marcar nenhum radio
                    document.getElementById('ep_presente_sim').checked = false;
                    document.getElementById('ep_presente_nao').checked = false;
                }
                
                // Abrir modal usando Bootstrap padrão (sem mover para body - manter no lugar)
                const modalElement = document.getElementById('modalEditarPresenca');
                if (modalElement) {
                    // NÃO mover o modal para o body - deixar onde está no HTML
                    // O Bootstrap gerencia isso automaticamente
                    
                    // Verificar se já existe instância do modal
                    let modal = bootstrap.Modal.getInstance(modalElement);
                    if (!modal) {
                        // Criar nova instância Bootstrap do modal
                        modal = new bootstrap.Modal(modalElement, {
                            backdrop: true,  // Mostrar backdrop escuro
                            keyboard: true,  // Permitir fechar com ESC
                            focus: true      // Focar no modal ao abrir
                        });
                    }
                    
                    // Event listeners para garantir comportamento correto (apenas uma vez)
                    const cleanupHandler = function() {
                        // Limpar estado quando fechar
                        const radioSim = document.getElementById('ep_presente_sim');
                        const radioNao = document.getElementById('ep_presente_nao');
                        if (radioSim) radioSim.checked = false;
                        if (radioNao) radioNao.checked = false;
                    };
                    
                    // Remover listener anterior se existir e adicionar novo
                    modalElement.removeEventListener('hidden.bs.modal', cleanupHandler);
                    modalElement.addEventListener('hidden.bs.modal', cleanupHandler);
                    
                    // Mostrar modal
                    modal.show();
                }
            }
            
            // Listener para botão de editar presença e badge clicável
            document.addEventListener('click', function(e) {
                // Verificar se clicou no badge clicável
                const badge = e.target.closest('.js-editar-presenca-badge');
                if (badge) {
                    e.preventDefault();
                    const dados = {
                        turmaId: badge.dataset.turmaId,
                        turmaAulaId: badge.dataset.turmaAulaId,
                        alunoId: badge.dataset.alunoId,
                        presencaId: badge.dataset.presencaId || '',
                        presenteAtual: badge.dataset.presente || '',
                        aulaNome: badge.dataset.aulaNome || '',
                        disciplina: badge.dataset.disciplina || '',
                        dataAula: badge.dataset.dataAula || ''
                    };
                    abrirModalEditarPresenca(dados);
                    return;
                }
                
                // Verificar se clicou no botão de editar
                const btn = e.target.closest('.js-editar-presenca');
                if (!btn) return;
                
                e.preventDefault();
                
                // Ler data-atributos
                const dados = {
                    turmaId: btn.dataset.turmaId,
                    turmaAulaId: btn.dataset.turmaAulaId,
                    alunoId: btn.dataset.alunoId,
                    presencaId: btn.dataset.presencaId || '',
                    presenteAtual: btn.dataset.presente || '',
                    aulaNome: btn.dataset.aulaNome || '',
                    disciplina: btn.dataset.disciplina || '',
                    dataAula: btn.dataset.dataAula || ''
                };
                abrirModalEditarPresenca(dados);
            });
            
            // Listener para salvar presença
            document.getElementById('ep-btn-salvar').addEventListener('click', function() {
                const turmaId = document.getElementById('ep-turma-id').value;
                const turmaAulaId = document.getElementById('ep-turma-aula-id').value;
                const alunoId = document.getElementById('ep-aluno-id').value;
                const presencaId = document.getElementById('ep-presenca-id').value;
                const presente = document.querySelector('input[name="ep_presente"]:checked')?.value;
                
                if (!presente) {
                    mostrarToast('Selecione uma opção de presença', 'error');
                    return;
                }
                
                const presenteInt = parseInt(presente);
                
                // Desabilitar botão durante requisição
                const btnSalvar = document.getElementById('ep-btn-salvar');
                btnSalvar.disabled = true;
                btnSalvar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Salvando...';
                
                if (presencaId) {
                    // Atualizar presença existente
                    const params = { id: presencaId, origem: 'admin' };
                    const url = API_TURMA_PRESENCAS + '?' + new URLSearchParams(params).toString();
                    
                    console.log('[HistoricoPresenca] Atualizando presença:', { url, presencaId, presenteInt });
                    
                    // Criar AbortController para timeout
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 segundos
                    
                    fetch(url, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            presente: presenteInt,
                            origem: 'admin'
                        }),
                        signal: controller.signal
                    })
                    .then(async response => {
                        // Verificar status HTTP primeiro
                        if (!response.ok) {
                            const errorText = await response.text();
                            console.error('[HistoricoPresenca] HTTP Error:', response.status, errorText);
                            throw new Error(`Erro ${response.status}: ${response.statusText}`);
                        }
                        
                        const text = await response.text();
                        let data;
                        try {
                            data = JSON.parse(text);
                        } catch (e) {
                            console.error('[HistoricoPresenca] Erro ao parsear JSON:', text);
                            throw new Error('Erro de comunicação com o servidor. Resposta inválida.');
                        }
                        if (!data.success) {
                            throw new Error(data.message || 'Erro ao atualizar presença.');
                        }
                        return data;
                    })
                    .then(data => {
                        clearTimeout(timeoutId);
                        console.log('[HistoricoPresenca] Presença atualizada com sucesso:', data);
                        mostrarToast('Presença atualizada com sucesso!', 'success');
                        
                        // Fechar modal corretamente
                        const modalElement = document.getElementById('modalEditarPresenca');
                        const modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) {
                            modal.hide();
                        } else {
                            // Fallback: esconder manualmente se não houver instância
                            if (modalElement) {
                                modalElement.classList.remove('show');
                                modalElement.style.display = 'none';
                                document.body.classList.remove('modal-open');
                                const backdrop = document.querySelector('.modal-backdrop');
                                if (backdrop) backdrop.remove();
                            }
                        }
                        
                        // Obter estado anterior da presença antes de atualizar
                        const badgeClicavel = document.querySelector(`.js-editar-presenca-badge[data-turma-aula-id="${turmaAulaId}"][data-aluno-id="${alunoId}"]`);
                        const estadoAnterior = badgeClicavel ? (badgeClicavel.dataset.presente === '1') : false;
                        const estadoNovo = presenteInt === 1;
                        
                        // Atualizar UI sem reload
                        atualizarPresencaNaUI(turmaId, turmaAulaId, alunoId, presenteInt, presencaId);
                        // Atualizar todos os campos que dependem da presença teórica (só se mudou)
                        if (estadoAnterior !== estadoNovo) {
                            atualizarTodosCamposPresenca(alunoId, estadoNovo, estadoAnterior);
                        }
                        
                        // Reabilitar botão
                        btnSalvar.disabled = false;
                        btnSalvar.innerHTML = '<i class="fas fa-save me-1"></i>Salvar';
                    })
                    .catch(error => {
                        clearTimeout(timeoutId);
                        if (error.name === 'AbortError') {
                            console.error('[HistoricoPresenca] Timeout na requisição');
                            mostrarToast('A requisição demorou muito. Verifique sua conexão e tente novamente.', 'error');
                        } else {
                            console.error('[HistoricoPresenca] Erro ao atualizar presença:', error);
                            mostrarToast(error.message || 'Erro ao atualizar presença. Tente novamente.', 'error');
                        }
                        btnSalvar.disabled = false;
                        btnSalvar.innerHTML = '<i class="fas fa-save me-1"></i>Salvar';
                    });
                } else {
                    // Criar nova presença
                    console.log('[HistoricoPresenca] Criando nova presença:', { url: API_TURMA_PRESENCAS, turmaId, turmaAulaId, alunoId, presenteInt });
                    
                    // Criar AbortController para timeout
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 segundos
                    
                    fetch(API_TURMA_PRESENCAS, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            turma_id: parseInt(turmaId),
                            turma_aula_id: parseInt(turmaAulaId),
                            aluno_id: parseInt(alunoId),
                            presente: presenteInt,
                            origem: 'admin'
                        }),
                        signal: controller.signal
                    })
                    .then(async response => {
                        clearTimeout(timeoutId);
                        // Verificar status HTTP primeiro
                        if (!response.ok) {
                            const errorText = await response.text();
                            console.error('[HistoricoPresenca] HTTP Error:', response.status, errorText);
                            throw new Error(`Erro ${response.status}: ${response.statusText}`);
                        }
                        
                        const text = await response.text();
                        let data;
                        try {
                            data = JSON.parse(text);
                        } catch (e) {
                            console.error('[HistoricoPresenca] Erro ao parsear JSON:', text);
                            throw new Error('Erro de comunicação com o servidor. Resposta inválida.');
                        }
                        if (!data.success) {
                            throw new Error(data.message || 'Erro ao criar presença.');
                        }
                        return data;
                    })
                    .then(data => {
                        clearTimeout(timeoutId);
                        console.log('[HistoricoPresenca] Presença criada com sucesso:', data);
                        mostrarToast('Presença registrada com sucesso!', 'success');
                        
                        // Fechar modal corretamente
                        const modalElement = document.getElementById('modalEditarPresenca');
                        const modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) {
                            modal.hide();
                        } else {
                            // Fallback: esconder manualmente se não houver instância
                            if (modalElement) {
                                modalElement.classList.remove('show');
                                modalElement.style.display = 'none';
                                document.body.classList.remove('modal-open');
                                const backdrop = document.querySelector('.modal-backdrop');
                                if (backdrop) backdrop.remove();
                            }
                        }
                        
                        // Obter estado anterior da presença antes de atualizar
                        const badgeClicavel = document.querySelector(`.js-editar-presenca-badge[data-turma-aula-id="${turmaAulaId}"][data-aluno-id="${alunoId}"]`);
                        const estadoAnterior = badgeClicavel ? (badgeClicavel.dataset.presente === '1') : false;
                        const estadoNovo = presenteInt === 1;
                        
                        // Atualizar UI sem reload
                        const novoPresencaId = data.presenca_id || data.data?.id || '';
                        atualizarPresencaNaUI(turmaId, turmaAulaId, alunoId, presenteInt, novoPresencaId);
                        // Atualizar todos os campos que dependem da presença teórica (só se mudou)
                        if (estadoAnterior !== estadoNovo) {
                            atualizarTodosCamposPresenca(alunoId, estadoNovo, estadoAnterior);
                        }
                        
                        // Reabilitar botão
                        btnSalvar.disabled = false;
                        btnSalvar.innerHTML = '<i class="fas fa-save me-1"></i>Salvar';
                    })
                    .catch(error => {
                        clearTimeout(timeoutId);
                        if (error.name === 'AbortError') {
                            console.error('[HistoricoPresenca] Timeout na requisição');
                            mostrarToast('A requisição demorou muito. Verifique sua conexão e tente novamente.', 'error');
                        } else {
                            console.error('[HistoricoPresenca] Erro ao criar presença:', error);
                            mostrarToast(error.message || 'Erro ao registrar presença. Tente novamente.', 'error');
                        }
                        btnSalvar.disabled = false;
                        btnSalvar.innerHTML = '<i class="fas fa-save me-1"></i>Salvar';
                    });
                }
            });
            
            // Função para atualizar UI após salvar presença (sem reload)
            function atualizarPresencaNaUI(turmaId, turmaAulaId, alunoId, presenteInt, presencaId) {
                // Atualizar badge de presença na linha da tabela
                // Procurar pelo badge clicável que tem os data-attributes corretos
                const badgeClicavel = document.querySelector(`.js-editar-presenca-badge[data-turma-aula-id="${turmaAulaId}"][data-aluno-id="${alunoId}"]`);
                if (badgeClicavel) {
                    const novoBadge = presenteInt === 1 
                        ? '<span class="badge bg-success"><i class="fas fa-check me-1"></i>PRESENTE</span>'
                        : '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>AUSENTE</span>';
                    
                    badgeClicavel.innerHTML = novoBadge;
                    badgeClicavel.dataset.presente = presenteInt;
                    if (presencaId) {
                        badgeClicavel.dataset.presencaId = presencaId;
                    }
                    
                    // Adicionar estilo de hover se ainda não tiver
                    if (!badgeClicavel.style.textDecoration) {
                        badgeClicavel.style.textDecoration = 'underline';
                        badgeClicavel.style.textDecorationStyle = 'dotted';
                    }
                }
                
                // Atualizar também o botão de editar se existir na mesma linha
                const linha = badgeClicavel ? badgeClicavel.closest('tr') : null;
                if (linha) {
                    const btnEditar = linha.querySelector('.js-editar-presenca');
                    if (btnEditar) {
                        btnEditar.dataset.presente = presenteInt;
                        if (presencaId) {
                            btnEditar.dataset.presencaId = presencaId;
                        }
                    }
                }
                
                // Atualizar frequência da turma via API
                atualizarFrequenciaTurma(turmaId, alunoId);
            }
            
            // Função para atualizar todos os campos que dependem da presença teórica
            function atualizarTodosCamposPresenca(alunoId, estadoNovo, estadoAnterior) {
                // Determinar se estamos adicionando ou removendo presença
                const adicionarPresenca = estadoNovo && !estadoAnterior;
                const removerPresenca = !estadoNovo && estadoAnterior;
                
                // Se não houve mudança real, não fazer nada
                if (!adicionarPresenca && !removerPresenca) {
                    return;
                }
                // Obter valores atuais dos elementos
                const totalTeoricasBadge = document.getElementById('total-aulas-teoricas-badge');
                const resumoTeoricoBadge = document.getElementById('resumo-teorico-curso-badge');
                const horasConcluidas = document.getElementById('horas-concluidas-total');
                const horasRestantes = document.getElementById('horas-restantes-total');
                const progressoGeral = document.getElementById('progresso-geral-percentual');
                const cardAulasConcluidas = document.getElementById('card-aulas-concluidas');
                const progressBar = document.getElementById('total-aulas-teoricas-progress');
                
                if (!totalTeoricasBadge || !resumoTeoricoBadge) {
                    console.warn('[HistoricoPresenca] Elementos não encontrados para atualização');
                    return;
                }
                
                // Extrair valores atuais
                const badgeText = totalTeoricasBadge.textContent.trim();
                const match = badgeText.match(/(\d+)\/(\d+)/);
                if (!match) {
                    console.warn('[HistoricoPresenca] Não foi possível extrair valores do badge');
                    return;
                }
                
                let teoricasConcluidas = parseInt(match[1]);
                const teoricasTotal = parseInt(match[2]);
                
                // Ajustar contador baseado na ação (adicionar ou remover presença)
                if (adicionarPresenca) {
                    teoricasConcluidas = Math.min(teoricasConcluidas + 1, teoricasTotal);
                } else {
                    teoricasConcluidas = Math.max(teoricasConcluidas - 1, 0);
                }
                
                // Calcular percentual
                const percentualTeoricas = teoricasTotal > 0 ? Math.min(100, (teoricasConcluidas / teoricasTotal) * 100) : 0;
                
                // Atualizar badge de Total Aulas Teóricas
                totalTeoricasBadge.textContent = `${teoricasConcluidas}/${teoricasTotal}`;
                
                // Atualizar barra de progresso
                if (progressBar) {
                    progressBar.style.width = `${percentualTeoricas}%`;
                }
                
                // Atualizar Resumo Teórico do Curso
                resumoTeoricoBadge.textContent = `${teoricasConcluidas} / ${teoricasTotal}`;
                
                // Atualizar Horas Concluídas e Restantes (assumindo que cada aula teórica = 1 hora)
                if (horasConcluidas && horasRestantes) {
                    const horasConcluidasAtual = parseInt(horasConcluidas.textContent) || 0;
                    const horasRestantesAtual = parseInt(horasRestantes.textContent) || 0;
                    
                    let novasHorasConcluidas, novasHorasRestantes;
                    if (adicionarPresenca) {
                        novasHorasConcluidas = horasConcluidasAtual + 1;
                        novasHorasRestantes = Math.max(0, horasRestantesAtual - 1);
                    } else {
                        novasHorasConcluidas = Math.max(0, horasConcluidasAtual - 1);
                        novasHorasRestantes = horasRestantesAtual + 1;
                    }
                    
                    horasConcluidas.textContent = novasHorasConcluidas;
                    horasRestantes.textContent = novasHorasRestantes;
                    
                    // Atualizar Progresso Geral
                    if (progressoGeral) {
                        // Calcular percentual geral (assumindo total de 85 horas)
                        const totalHoras = 85; // Valor fixo baseado no sistema
                        const percentualGeral = totalHoras > 0 ? (novasHorasConcluidas / totalHoras) * 100 : 0;
                        progressoGeral.textContent = `${percentualGeral.toFixed(1)}%`;
                    }
                }
                
                // Atualizar card "Aulas Concluídas"
                if (cardAulasConcluidas) {
                    const aulasConcluidasAtual = parseInt(cardAulasConcluidas.textContent) || 0;
                    const novasAulasConcluidas = adicionarPresenca 
                        ? aulasConcluidasAtual + 1 
                        : Math.max(0, aulasConcluidasAtual - 1);
                    cardAulasConcluidas.textContent = novasAulasConcluidas;
                }
                
                console.log('[HistoricoPresenca] Campos atualizados:', {
                    teoricasConcluidas,
                    teoricasTotal,
                    percentualTeoricas,
                    adicionarPresenca
                });
            }
            
            // Função para atualizar frequência da turma
            function atualizarFrequenciaTurma(turmaId, alunoId) {
                // Calcular URL da API de frequência
                const scriptPath = '<?php echo $_SERVER['SCRIPT_NAME'] ?? '/admin/index.php'; ?>';
                let baseRoot = '';
                
                if (scriptPath.match(/^\/([^\/]+)\/admin\//)) {
                    baseRoot = '/' + scriptPath.match(/^\/([^\/]+)\/admin\//)[1];
                } else if (scriptPath.indexOf('/admin/') !== -1) {
                    const parts = scriptPath.split('/admin/');
                    baseRoot = parts[0] || '/cfc-bom-conselho';
                } else {
                    baseRoot = '/cfc-bom-conselho';
                }
                
                const API_TURMA_FREQUENCIA = baseRoot + '/admin/api/turma-frequencia.php';
                const url = `${API_TURMA_FREQUENCIA}?turma_id=${turmaId}&aluno_id=${alunoId}`;
                
                fetch(url)
                    .then(async response => {
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}`);
                        }
                        const contentType = response.headers.get('content-type') || '';
                        if (!contentType.includes('application/json')) {
                            throw new Error('Resposta não é JSON');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success && data.data && data.data.estatisticas) {
                            const percentual = data.data.estatisticas.percentual_frequencia;
                            
                            // Atualizar badge de frequência da turma
                            // Procurar pelo badge de frequência usando data-turma-frequencia
                            const frequenciaBadges = document.querySelectorAll(`[data-turma-frequencia="${turmaId}"]`);
                            frequenciaBadges.forEach(badge => {
                                badge.textContent = `Frequência: ${percentual.toFixed(1)}%`;
                                
                                // Atualizar classe do badge conforme frequência
                                badge.className = 'badge ';
                                if (percentual >= 75) {
                                    badge.className += 'bg-success';
                                } else if (percentual >= 65) {
                                    badge.className += 'bg-warning';
                                } else {
                                    badge.className += 'bg-danger';
                                }
                            });
                            
                            // Fallback: atualizar também badges que contenham "Frequência:" no texto dentro do card da turma
                            const cardTurma = document.querySelector(`[data-turma-id="${turmaId}"]`);
                            if (cardTurma) {
                                const todosBadges = cardTurma.querySelectorAll('.badge');
                                todosBadges.forEach(badge => {
                                    if (badge.textContent.includes('Frequência:')) {
                                        badge.textContent = `Frequência: ${percentual.toFixed(1)}%`;
                                        
                                        // Atualizar classe
                                        badge.className = 'badge ';
                                        if (percentual >= 75) {
                                            badge.className += 'bg-success';
                                        } else if (percentual >= 65) {
                                            badge.className += 'bg-warning';
                                        } else {
                                            badge.className += 'bg-danger';
                                        }
                                    }
                                });
                            }
                        }
                    })
                    .catch(error => {
                        console.error('[HistoricoPresenca] Erro ao atualizar frequência:', error);
                        // Não mostrar erro ao usuário - a presença já foi salva
                    });
            }
        })();
    </script>
    <?php endif; ?>
    
    <style>
        /* Estilos para cards compactos do Progresso Detalhado por Categoria */
        .card-progresso-teorico,
        .card-progresso-categoria {
            border-radius: 10px;
        }

        .card-progresso-teorico .card-body,
        .card-progresso-categoria .card-body {
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
        }

        /* Em telas grandes, reduzir um pouco ainda mais a "altura visual" */
        @media (min-width: 992px) {
            .card-progresso-categoria {
                margin-bottom: 0.75rem;
            }
        }
        
        /* CORREÇÃO 2025-12: Estilos para badge clicável de presença */
        .js-editar-presenca-badge {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .js-editar-presenca-badge:hover {
            opacity: 0.8;
            transform: scale(1.05);
        }
        
        .js-editar-presenca-badge:active {
            transform: scale(0.95);
        }
        
        /* CORREÇÃO 2025-12: Garantir que modal apareça corretamente sobre o layout (sem tela branca) */
        /* Sobrescrever estilos globais que forçam background branco nos modais */
        
        /* Remover background branco forçado pelo CSS global (.modal { background: var(--white); }) */
        #modalEditarPresenca.modal {
            background-color: transparent !important;
            background: transparent !important;
        }
        
        /* Garantir que o modal-content tenha o background branco (não o modal em si) */
        #modalEditarPresenca .modal-content {
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        /* Garantir que o backdrop apareça corretamente (Bootstrap padrão) */
        body.modal-open .modal-backdrop {
            z-index: 1040;
            background-color: rgba(0, 0, 0, 0.5) !important;
        }
        
        /* Garantir que o dialog esteja centralizado e com tamanho adequado */
        #modalEditarPresenca .modal-dialog {
            margin: 1.75rem auto;
            max-width: 500px;
        }
        
        /* Responsivo para telas menores */
        @media (max-width: 576px) {
            #modalEditarPresenca .modal-dialog {
                margin: 0.5rem;
                max-width: calc(100% - 1rem);
            }
        }
    </style>
    <script>
        // Funções para ações
        function verDetalhesAula(aulaId) {
            // Buscar dados da aula
            const aula = <?php echo json_encode($aulas); ?>.find(a => a.id == aulaId);
            
            if (!aula) {
                alert('Aula não encontrada!');
                return;
            }
            
            // Montar conteúdo do modal
            const modalBody = document.getElementById('modalDetalhesBody');
            const btnEditar = document.getElementById('btnEditarAula');
            
            modalBody.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-calendar-alt me-2"></i>Informações da Aula
                        </h6>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Data:</label>
                            <p class="mb-0">${formatarData(aula.data_aula)}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Horário:</label>
                            <p class="mb-0">${aula.hora_inicio} - ${aula.hora_fim}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tipo de Aula:</label>
                            <p class="mb-0">
                                <span class="badge bg-${aula.tipo_aula === 'teorica' ? 'info' : 'primary'}">
                                    ${aula.tipo_aula.toUpperCase()}
                                </span>
                            </p>
                        </div>
                        ${aula.disciplina ? `
                        <div class="mb-3">
                            <label class="form-label fw-bold">Disciplina:</label>
                            <p class="mb-0">${aula.disciplina}</p>
                        </div>
                        ` : ''}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Status:</label>
                            <p class="mb-0">
                                <span class="badge bg-${getStatusColor(aula.status)}">
                                    ${aula.status.toUpperCase()}
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-users me-2"></i>Informações dos Participantes
                        </h6>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Aluno:</label>
                            <p class="mb-0">${aula.aluno_nome || 'N/A'}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Instrutor:</label>
                            <p class="mb-0">${aula.instrutor_nome || 'N/A'}</p>
                            ${aula.credencial ? `<small class="text-muted">${aula.credencial}</small>` : ''}
                        </div>
                        ${aula.placa ? `
                        <div class="mb-3">
                            <label class="form-label fw-bold">Veículo:</label>
                            <p class="mb-0">${aula.placa} - ${aula.modelo || ''} ${aula.marca || ''}</p>
                        </div>
                        ` : `
                        <div class="mb-3">
                            <label class="form-label fw-bold">Veículo:</label>
                            <p class="mb-0 text-muted">Não aplicável</p>
                        </div>
                        `}
                    </div>
                </div>
                ${aula.observacoes ? `
                <hr>
                <div class="row">
                    <div class="col-12">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-sticky-note me-2"></i>Observações
                        </h6>
                        <div class="alert alert-light">
                            <p class="mb-0">${aula.observacoes}</p>
                        </div>
                    </div>
                </div>
                ` : ''}
                <hr>
                <div class="row">
                    <div class="col-12">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-info-circle me-2"></i>Informações do Sistema
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <strong>Criado em:</strong> ${formatarDataHora(aula.criado_em)}
                                </small>
                            </div>
                            ${aula.atualizado_em ? `
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <strong>Atualizado em:</strong> ${formatarDataHora(aula.atualizado_em)}
                                </small>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
            
            // Mostrar botão de editar apenas para aulas agendadas
            if (aula.status === 'agendada') {
                btnEditar.style.display = 'inline-block';
                btnEditar.onclick = () => {
                    window.location.href = `/cfc-bom-conselho/admin/index.php?page=agendar-aula&action=edit&edit=${aulaId}&t=${Date.now()}`;
                };
            } else {
                btnEditar.style.display = 'none';
            }
            
            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('modalDetalhesAula'));
            modal.show();
        }
        
        // Funções auxiliares
        function formatarData(data) {
            if (!data) return 'N/A';
            const date = new Date(data);
            return date.toLocaleDateString('pt-BR');
        }
        
        function formatarDataHora(dataHora) {
            if (!dataHora) return 'N/A';
            const date = new Date(dataHora);
            return date.toLocaleString('pt-BR');
        }
        
        function getStatusColor(status) {
            const colors = {
                'agendada': 'warning',
                'concluida': 'success',
                'cancelada': 'danger',
                'em_andamento': 'info'
            };
            return colors[status] || 'secondary';
        }

        function editarAula(aulaId) {
            console.log('=== DEBUG EDIÇÃO ===');
            console.log('aulaId recebido:', aulaId);
            console.log('Tipo do aulaId:', typeof aulaId);
            
            // Verificar se o Bootstrap está carregado
            if (typeof bootstrap === 'undefined') {
                console.error('❌ Bootstrap não está carregado!');
                alert('Erro: Bootstrap não está carregado. Recarregue a página.');
                return;
            }
            
            // Verificar se a função está sendo chamada
            console.log('✅ Função editarAula chamada com sucesso');
            
            // Limpar cache e redirecionar com versão forçada
            const timestamp = Date.now();
            const random = Math.random().toString(36).substring(7);
            const version = 'v' + Math.floor(Date.now() / 1000);
            
            const url = `index.php?page=agendar-aula&action=edit&edit=${aulaId}&t=${timestamp}&r=${random}&v=${version}`;
            
            console.log('URL gerada:', url);
            console.log('Redirecionando em 1 segundo...');
            
            // Adicionar delay para ver o log
            setTimeout(() => {
                console.log('Executando redirecionamento...');
                window.location.href = url;
            }, 1000);
        }

        function cancelarAula(aulaId) {
            console.log('=== DEBUG CANCELAMENTO ===');
            console.log('aulaId recebido:', aulaId);
            console.log('Tipo do aulaId:', typeof aulaId);
            
            // Verificar se o Bootstrap está carregado
            if (typeof bootstrap === 'undefined') {
                console.error('❌ Bootstrap não está carregado!');
                alert('Erro: Bootstrap não está carregado. Recarregue a página.');
                return;
            }
            
            // Verificar se o modal existe
            const modalElement = document.getElementById('modalCancelarAula');
            if (!modalElement) {
                console.error('❌ Modal modalCancelarAula não encontrado!');
                alert('Erro: Modal de cancelamento não encontrado. Recarregue a página.');
                return;
            }
            
            console.log('✅ Modal encontrado:', modalElement);
            
            if (confirm('Tem certeza que deseja cancelar esta aula?')) {
                console.log('✅ Usuário confirmou cancelamento');
                
                // Mostrar modal de cancelamento
                const modal = new bootstrap.Modal(modalElement);
                document.getElementById('aulaIdCancelar').value = aulaId;
                modal.show();
                
                console.log('✅ Modal de cancelamento exibido');
            } else {
                console.log('❌ Usuário cancelou a operação');
            }
        }
        
        function confirmarCancelamento() {
            const aulaId = document.getElementById('aulaIdCancelar').value;
            const motivo = document.getElementById('motivoCancelamento').value;
            const observacoes = document.getElementById('observacoesCancelamento').value;
            
            if (!motivo) {
                alert('Por favor, selecione um motivo para o cancelamento.');
                return;
            }
            
            // Preparar dados
            const formData = new FormData();
            formData.append('aula_id', aulaId);
            formData.append('motivo_cancelamento', motivo);
            formData.append('observacoes', observacoes);
            
            // Enviar dados
            fetch('api/cancelar-aula.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Aula cancelada com sucesso!');
                    location.reload(); // Recarregar página para atualizar dados
                } else {
                    alert('Erro ao cancelar aula: ' + data.message);
                }
            })
            .catch(error => {
                alert('Erro ao cancelar aula: ' + error.message);
            });
        }

        // =====================================================
        // FUNÇÕES PARA EXAMES
        // =====================================================

        // NOTA: Funções abrirModalAgendamento, agendarExame e lancarResultado foram removidas.
        // O agendamento de exames agora é feito através do módulo dedicado (page=exames).
        // Os botões "Agendar Exame" no histórico redirecionam para o novo módulo.

        // =====================================================
        // BLOQUEIO FINANCEIRO: Interceptar cliques em botões bloqueados
        // =====================================================
        document.addEventListener('DOMContentLoaded', function() {
            // Selecionar todos os botões "Agendar Exame" com data-bloqueado
            const botoesAgendarExame = document.querySelectorAll('a[data-bloqueado="1"]');
            
            console.log('[BLOQUEIO FINANCEIRO] Encontrados ' + botoesAgendarExame.length + ' botões bloqueados');
            
            botoesAgendarExame.forEach(function(botao) {
                botao.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const motivo = botao.getAttribute('data-motivo') || 'Não é possível avançar: situação financeira não regularizada.';
                    
                    console.log('[BLOQUEIO FINANCEIRO] Clique bloqueado - Motivo: ' + motivo);
                    
                    alert('⚠️ ' + motivo);
                    
                    return false;
                });
            });
        });

        function cancelarExame(exameId) {
            if (!confirm('Tem certeza que deseja cancelar este exame?')) {
                return;
            }

            fetch(`api/exames.php?id=${exameId}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', 'Exame cancelado com sucesso!');
                    location.reload();
                } else {
                    showToast('error', data.error || 'Erro ao cancelar exame');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showToast('error', 'Erro ao cancelar exame');
            });
        }
    </script>

    <!-- NOTA: Modais antigos de agendamento (#modalAgendamento) e resultado (#modalResultado) foram removidos.
         O agendamento de exames agora é feito através do módulo dedicado (page=exames).
         Os botões "Agendar Exame" no histórico redirecionam para o novo módulo.
         O lançamento de resultados também deve ser feito no módulo de exames. -->

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="toast" class="toast" role="alert">
            <div class="toast-header">
                <i id="toastIcon" class="fas fa-info-circle text-primary me-2"></i>
                <strong id="toastTitle" class="me-auto">Notificação</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div id="toastBody" class="toast-body"></div>
        </div>
    </div>

    <script>
        // Função para mostrar toast
        function showToast(type, message) {
            const toast = document.getElementById('toast');
            const toastIcon = document.getElementById('toastIcon');
            const toastTitle = document.getElementById('toastTitle');
            const toastBody = document.getElementById('toastBody');
            
            // Configurar ícone e título baseado no tipo
            switch(type) {
                case 'success':
                    toastIcon.className = 'fas fa-check-circle text-success me-2';
                    toastTitle.textContent = 'Sucesso';
                    toast.className = 'toast border-success';
                    break;
                case 'error':
                    toastIcon.className = 'fas fa-exclamation-circle text-danger me-2';
                    toastTitle.textContent = 'Erro';
                    toast.className = 'toast border-danger';
                    break;
                default:
                    toastIcon.className = 'fas fa-info-circle text-primary me-2';
                    toastTitle.textContent = 'Informação';
                    toast.className = 'toast border-primary';
            }
            
            toastBody.textContent = message;
            
            // Mostrar toast
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
        }
        
        // AJUSTE 2025-12 - Scroll até turma destacada quando vindo do Diário
        <?php if ($turmaIdFoco): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const turmaFoco = document.getElementById('turma-foco');
            if (turmaFoco) {
                // Scroll suave até a turma destacada
                setTimeout(() => {
                    turmaFoco.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    // Adicionar animação de destaque
                    turmaFoco.style.transition = 'all 0.3s ease';
                    turmaFoco.style.animation = 'pulse 2s ease-in-out';
                }, 300);
            }
        });
        <?php endif; ?>
    </script>
    
    <style>
        /* AJUSTE 2025-12 - Animação de destaque para turma foco */
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.4); }
            50% { box-shadow: 0 0 0 10px rgba(0, 123, 255, 0); }
        }
    </style>