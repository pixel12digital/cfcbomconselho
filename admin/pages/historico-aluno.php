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
if (defined('ADMIN_ROUTING')) {
    // Se estamos no sistema de roteamento, usar variável global
    $alunoId = $aluno_id ?? null;
} else {
    // Se acessado diretamente, usar GET
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        header('Location: alunos.php');
        exit;
    }
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

// Buscar histórico de aulas
$aulas = $db->fetchAll("
    SELECT a.*, i.credencial, COALESCE(u.nome, i.nome) as instrutor_nome, v.placa, v.modelo, v.marca
    FROM aulas a
    LEFT JOIN instrutores i ON a.instrutor_id = i.id
    LEFT JOIN usuarios u ON i.usuario_id = u.id
    LEFT JOIN veiculos v ON a.veiculo_id = v.id
    WHERE a.aluno_id = ?
    ORDER BY a.data_aula DESC, a.hora_inicio DESC
", [$alunoId]);

// Calcular estatísticas gerais
$totalAulas = count($aulas);
$aulasConcluidas = count(array_filter($aulas, fn($a) => $a['status'] === 'concluida'));
$aulasCanceladas = count(array_filter($aulas, fn($a) => $a['status'] === 'cancelada'));
$aulasAgendadas = count(array_filter($aulas, fn($a) => $a['status'] === 'agendada'));

// Buscar exames do aluno
$exames = $db->fetchAll("
    SELECT * FROM exames 
    WHERE aluno_id = ? 
    ORDER BY tipo, data_agendada DESC
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

// Calcular estatísticas por tipo de aula
// Para teóricas, contar apenas disciplinas únicas para evitar duplicação
$disciplinasTeoricasUnicasGerais = [];
$aulasTeoricasConcluidas = 0;
foreach ($aulas as $aula) {
    if ($aula['status'] === 'concluida' && $aula['tipo_aula'] === 'teorica') {
        $disciplina = $aula['disciplina'] ?? 'geral';
        if (!isset($disciplinasTeoricasUnicasGerais[$disciplina])) {
            $disciplinasTeoricasUnicasGerais[$disciplina] = true;
            $aulasTeoricasConcluidas++;
        }
    }
}
$aulasPraticasConcluidas = count(array_filter($aulas, fn($a) => $a['status'] === 'concluida' && $a['tipo_aula'] === 'pratica'));

// Calcular estatísticas por categoria de veículo (para aulas práticas)
$aulasPraticasPorTipo = [
    'moto' => count(array_filter($aulas, fn($a) => $a['status'] === 'concluida' && $a['tipo_aula'] === 'pratica' && $a['tipo_veiculo'] === 'moto')),
    'carro' => count(array_filter($aulas, fn($a) => $a['status'] === 'concluida' && $a['tipo_aula'] === 'pratica' && $a['tipo_veiculo'] === 'carro')),
    'carga' => count(array_filter($aulas, fn($a) => $a['status'] === 'concluida' && $a['tipo_aula'] === 'pratica' && $a['tipo_veiculo'] === 'carga')),
    'passageiros' => count(array_filter($aulas, fn($a) => $a['status'] === 'concluida' && $a['tipo_aula'] === 'pratica' && $a['tipo_veiculo'] === 'passageiros')),
    'combinacao' => count(array_filter($aulas, fn($a) => $a['status'] === 'concluida' && $a['tipo_aula'] === 'pratica' && $a['tipo_veiculo'] === 'combinacao'))
];

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
    
    foreach ($configuracoesCategorias as $categoria => $config) {
        // NÃO somar aqui - já foi calculado pela função centralizada
        
        // Calcular aulas concluídas por tipo para esta categoria específica
        // Para teóricas, contar apenas disciplinas únicas para evitar duplicação
        $disciplinasTeoricasUnicas = [];
        $teoricasConcluidas = 0;
        foreach ($aulas as $aula) {
            if ($aula['status'] === 'concluida' && 
                $aula['tipo_aula'] === 'teorica' && 
                $aula['categoria_veiculo'] === $categoria) {
                $disciplina = $aula['disciplina'] ?? 'geral';
                if (!isset($disciplinasTeoricasUnicas[$disciplina])) {
                    $disciplinasTeoricasUnicas[$disciplina] = true;
                    $teoricasConcluidas++;
                }
            }
        }
        
        $praticasMotoConcluidas = count(array_filter($aulas, function($a) use ($categoria) {
            return $a['status'] === 'concluida' && 
                   $a['tipo_aula'] === 'pratica' && 
                   $a['categoria_veiculo'] === $categoria &&
                   $a['tipo_veiculo'] === 'moto';
        }));
        
        $praticasCarroConcluidas = count(array_filter($aulas, function($a) use ($categoria) {
            return $a['status'] === 'concluida' && 
                   $a['tipo_aula'] === 'pratica' && 
                   $a['categoria_veiculo'] === $categoria &&
                   $a['tipo_veiculo'] === 'carro';
        }));
        
        $praticasCargaConcluidas = count(array_filter($aulas, function($a) use ($categoria) {
            return $a['status'] === 'concluida' && 
                   $a['tipo_aula'] === 'pratica' && 
                   $a['categoria_veiculo'] === $categoria &&
                   $a['tipo_veiculo'] === 'carga';
        }));
        
        $praticasPassageirosConcluidas = count(array_filter($aulas, function($a) use ($categoria) {
            return $a['status'] === 'concluida' && 
                   $a['tipo_aula'] === 'pratica' && 
                   $a['categoria_veiculo'] === $categoria &&
                   $a['tipo_veiculo'] === 'passageiros';
        }));
        
        $praticasCombinacaoConcluidas = count(array_filter($aulas, function($a) use ($categoria) {
            return $a['status'] === 'concluida' && 
                   $a['tipo_aula'] === 'pratica' && 
                   $a['categoria_veiculo'] === $categoria &&
                   $a['tipo_veiculo'] === 'combinacao';
        }));

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
    
    // Contar aulas concluídas por tipo para categorias mudanca_categorias
    $aulasTeoricasContadas = []; // Para evitar duplicação de aulas teóricas
    
    foreach ($aulas as $aula) {
        if ($aula['status'] === 'concluida') {
            if ($aula['tipo_aula'] === 'teorica') {
                // Para teóricas, contar apenas uma vez para categorias mudanca_categorias
                // Usar disciplina como identificador único para evitar duplicação
                $disciplina = $aula['disciplina'] ?? 'geral';
                if (!isset($aulasTeoricasContadas[$disciplina])) {
                    $aulasTeoricasContadas[$disciplina] = true;
                    
                    // Distribuir entre todas as categorias apenas uma vez
                    foreach ($progressoDetalhado as $categoria => $dados) {
                        if (isset($progressoDetalhado[$categoria]['teoricas'])) {
                            $progressoDetalhado[$categoria]['teoricas']['concluidas']++;
                        }
                    }
                }
            } elseif ($aula['tipo_aula'] === 'pratica') {
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
        
        $progressoDetalhado = [
            'teoricas' => [
                'concluidas' => $aulasTeoricasConcluidas,
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
        $progressoDetalhado = [
            'teoricas' => [
                'concluidas' => $aulasTeoricasConcluidas,
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
                            
                            foreach ($configuracoesCategorias as $categoria => $config) {
                                $totalTeoricasConcluidasGeral += $progressoDetalhado[$categoria]['teoricas']['concluidas'];
                                // NÃO somar práticas aqui - já foi calculado pela função centralizada
                                
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
                                        <span class="badge bg-info fs-6">
                                            <?php echo $totalTeoricasConcluidasGeral; ?>/<?php echo $totalTeoricasGeral; ?>
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-info" role="progressbar" 
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
                                            <h4 class="text-success mb-1"><?php echo $totalTeoricasConcluidasGeral + $totalPraticasConcluidasGeral; ?></h4>
                                            <small class="text-muted">Horas Concluídas</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-2">
                                            <h4 class="text-warning mb-1"><?php echo ($totalTeoricasGeral + $totalPraticasGeral) - ($totalTeoricasConcluidasGeral + $totalPraticasConcluidasGeral); ?></h4>
                                            <small class="text-muted">Horas Restantes</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-2">
                                            <h4 class="text-info mb-1"><?php echo number_format((($totalTeoricasConcluidasGeral + $totalPraticasConcluidasGeral) / ($totalTeoricasGeral + $totalPraticasGeral)) * 100, 1); ?>%</h4>
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
            // Buscar aulas agendadas da turma
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
            
            // Buscar presenças do aluno nesta turma
            $presencasAluno = $db->fetchAll("
                SELECT 
                    tp.aula_id,
                    tp.presente,
                    tp.justificativa,
                    tp.registrado_em
                FROM turma_presencas tp
                WHERE tp.turma_id = ? AND tp.aluno_id = ?
            ", [$turma['turma_id'], $alunoId]);
            
            // Criar mapa de presenças por aula_id
            $presencasMap = [];
            foreach ($presencasAluno as $presenca) {
                $presencasMap[$presenca['aula_id']] = $presenca;
            }
            
            // Montar lista de aulas com status de presença
            $aulasComPresenca = [];
            foreach ($aulasTurma as $aula) {
                $presenca = $presencasMap[$aula['aula_id']] ?? null;
                $aulasComPresenca[] = [
                    'aula' => $aula,
                    'presenca' => $presenca,
                    'status_presenca' => $presenca ? ($presenca['presente'] ? 'presente' : 'ausente') : 'nao_registrado'
                ];
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
                            <div class="mb-4 pb-3 border-bottom">
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
                                            <span class="badge <?php echo $freqBadgeClass; ?>">
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
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($aulas as $itemAula): ?>
                                                <?php 
                                                $aula = $itemAula['aula'];
                                                $statusPresenca = $itemAula['status_presenca'];
                                                
                                                $presencaBadge = '';
                                                if ($statusPresenca === 'presente') {
                                                    $presencaBadge = '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Presente</span>';
                                                } elseif ($statusPresenca === 'ausente') {
                                                    $presencaBadge = '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Ausente</span>';
                                                } else {
                                                    $presencaBadge = '<span class="badge bg-secondary"><i class="fas fa-minus me-1"></i>Não registrado</span>';
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
                                                        <?php echo $presencaBadge; ?>
                                                        <?php if ($itemAula['presenca'] && !empty($itemAula['presenca']['justificativa'])): ?>
                                                            <i class="fas fa-comment-alt text-info ms-2" 
                                                               data-bs-toggle="tooltip" 
                                                               title="<?php echo htmlspecialchars($itemAula['presenca']['justificativa']); ?>"></i>
                                                        <?php endif; ?>
                                                    </td>
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
            
            foreach ($configuracoesCategorias as $categoria => $config) {
                $totalTeoricasConcluidasGeralDetalhado += $progressoDetalhado[$categoria]['teoricas']['concluidas'];
                
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
                                        <div class="fw-bold">
                                            <?php echo $totalTeoricasConcluidasGeralDetalhado; ?> / <?php echo $totalTeoricasGeralDetalhado; ?>
                                        </div>
                                        <small class="text-muted">
                                            Total necessário: <?php echo $totalTeoricasGeralDetalhado; ?> aulas teóricas
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
                        <h4><?php echo $aulasConcluidas; ?></h4>
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
                                    <?php foreach ($aulas as $aula): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($aula['data_aula'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($aula['hora_inicio'])) . ' - ' . date('H:i', strtotime($aula['hora_fim'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $aula['tipo_aula'] === 'teorica' ? 'info' : 'primary'; ?>">
                                                <?php echo ucfirst(htmlspecialchars($aula['tipo_aula'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($aula['instrutor_nome']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($aula['credencial'] ?? 'N/A'); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($aula['veiculo_id']): ?>
                                            <div>
                                                <strong><?php echo htmlspecialchars($aula['placa']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($aula['marca'] . ' ' . $aula['modelo']); ?></small>
                                            </div>
                                            <?php else: ?>
                                            <span class="text-muted">Não aplicável</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = [
                                                'agendada' => 'warning',
                                                'em_andamento' => 'info',
                                                'concluida' => 'success',
                                                'cancelada' => 'danger'
                                            ];
                                            $statusText = [
                                                'agendada' => 'Agendada',
                                                'em_andamento' => 'Em Andamento',
                                                'concluida' => 'Concluída',
                                                'cancelada' => 'Cancelada'
                                            ];
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass[$aula['status']] ?? 'secondary'; ?>">
                                                <?php echo $statusText[$aula['status']] ?? ucfirst($aula['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($aula['observacoes']): ?>
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
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        title="Ver detalhes da aula"
                                                        onclick="verDetalhesAula(<?php echo $aula['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($aula['status'] === 'agendada'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-warning" 
                                                        title="Editar aula"
                                                        onclick="editarAula(<?php echo $aula['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        title="Cancelar aula"
                                                        onclick="cancelarAula(<?php echo $aula['id']; ?>)">
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
    </script>