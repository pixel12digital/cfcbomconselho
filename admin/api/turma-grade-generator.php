<?php
/**
 * API Gerador Automático de Grade - Turmas Teóricas
 * Sistema de Turmas Teóricas - CFC Bom Conselho
 * 
 * @author Sistema CFC Bom Conselho
 * @version 2.0 - Com disciplinas baseadas em configurações
 * @since 2024
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../includes/configuracoes_categorias.php';

header('Content-Type: application/json');

$db = Database::getInstance();
$configManager = ConfiguracoesCategorias::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $turma_id = $data['turma_id'] ?? null;
    $categoria_cnh = $data['categoria_cnh'] ?? null;
    $duracao_aula = $data['duracao_aula'] ?? 50;
    $data_inicio = $data['data_inicio'] ?? null;
    $data_fim = $data['data_fim'] ?? null;
    $horario_inicio = $data['horario_inicio'] ?? '08:00';
    $max_aulas_dia = $data['max_aulas_dia'] ?? 5;
    $dias_semana = $data['dias_semana'] ?? [1, 2, 3, 4, 5]; // Segunda a Sexta
    $acao = $data['acao'] ?? 'generate'; // 'generate' ou 'reconcile'
    
    if (!$turma_id) {
        sendJsonResponse([
            'status' => 'error',
            'message' => 'ID da turma é obrigatório'
        ], 400);
    }
    
    try {
        // Buscar informações da turma
        $turma = $db->fetch("SELECT * FROM turmas WHERE id = ?", [$turma_id]);
        if (!$turma) {
            sendJsonResponse([
                'status' => 'error',
                'message' => 'Turma não encontrada'
            ], 404);
        }
        
        // Usar categoria da turma se não fornecida
        if (!$categoria_cnh) {
            $categoria_cnh = $turma['categoria_cnh'] ?? 'AB';
        }
        
        // Buscar configurações das disciplinas
        $disciplinas = obterDisciplinasParaCategoria($categoria_cnh);
        if (empty($disciplinas)) {
            sendJsonResponse([
                'status' => 'error',
                'message' => 'Nenhuma disciplina configurada para categoria ' . $categoria_cnh
            ], 400);
        }
        
        // Calcular total de aulas por disciplina
        $total_aulas = array_sum(array_column($disciplinas, 'aulas'));
        
        if ($total_aulas == 0) {
            sendJsonResponse([
                'status' => 'error',
                'message' => 'Nenhuma aula configurada para as disciplinas'
            ], 400);
        }
        
        // Gerar grade baseada nas disciplinas
        $aulas_por_disciplina = gerarAulasPorDisciplina($disciplinas, $turma_id, $duracao_aula, $data_inicio, $data_fim, $horario_inicio, $max_aulas_dia, $dias_semana);
        
        if ($acao === 'reconcile') {
            // Modo reconciliação - não limpar aulas existentes
            $resultado = reconciliarGrade($turma_id, $aulas_por_disciplina);
        } else {
            // Modo geração - limpar e recriar
            $resultado = gerarNovaGrade($turma_id, $aulas_por_disciplina);
        }
        
        sendJsonResponse([
            'status' => 'success',
            'message' => $acao === 'reconcile' ? 'Grade reconciliada com sucesso!' : 'Grade gerada com sucesso!',
            'data' => $resultado
        ]);
        
    } catch (Exception $e) {
        sendJsonResponse([
            'status' => 'error',
            'message' => 'Erro ao gerar grade: ' . $e->getMessage()
        ], 500);
    }
    
} elseif ($method === 'GET') {
    $turma_id = $_GET['turma_id'] ?? null;
    $action = $_GET['action'] ?? 'list';
    
    if (!$turma_id) {
        sendJsonResponse([
            'status' => 'error',
            'message' => 'ID da turma é obrigatório'
        ], 400);
    }
    
    try {
        // Buscar informações da turma
        $turma = $db->fetch("
            SELECT * FROM turmas 
            WHERE id = ?
        ", [$turma_id]);
        
        if (!$turma) {
            sendJsonResponse([
                'status' => 'error',
                'message' => 'Turma não encontrada'
            ], 404);
        }
        
        if ($action === 'preview') {
            // Preview das disciplinas que serão geradas
            $categoria_preview = $_GET['categoria'] ?? $turma['categoria_cnh'];
            $disciplinas = obterDisciplinasParaCategoria($categoria_preview);
            $total_aulas = array_sum(array_column($disciplinas, 'aulas'));
            
            sendJsonResponse([
                'status' => 'success',
                'data' => [
                    'turma' => $turma,
                    'disciplinas' => $disciplinas,
                    'total_aulas' => $total_aulas
                ]
            ]);
            
        } elseif ($action === 'reconcile') {
            // Verificar se precisa de reconciliação
            $disciplinas = obterDisciplinasParaCategoria($turma['categoria_cnh']);
            $aulas_existentes = $db->fetchAll("
                SELECT * FROM turma_aulas 
                WHERE turma_id = ? 
                ORDER BY ordem ASC
            ", [$turma_id]);
            
            $aulas_realizadas = $db->fetchAll("
                SELECT * FROM turma_aulas 
                WHERE turma_id = ? AND status = 'concluida'
                ORDER BY ordem ASC
            ", [$turma_id]);
            
            $precisa_reconciliacao = false;
            $detalhes_reconciliacao = [];
            
            foreach ($disciplinas as $disciplina) {
                $aulas_existentes_disciplina = array_filter($aulas_existentes, function($aula) use ($disciplina) {
                    return ($aula['disciplina'] ?? '') === $disciplina['slug'];
                });
                
                $aulas_necessarias = $disciplina['aulas'];
                $aulas_existentes_count = count($aulas_existentes_disciplina);
                $aulas_faltantes = $aulas_necessarias - $aulas_existentes_count;
                
                if ($aulas_faltantes > 0) {
                    $precisa_reconciliacao = true;
                    $detalhes_reconciliacao[] = [
                        'disciplina' => $disciplina['nome'],
                        'slug' => $disciplina['slug'],
                        'aulas_existentes' => $aulas_existentes_count,
                        'aulas_necessarias' => $aulas_necessarias,
                        'aulas_faltantes' => $aulas_faltantes
                    ];
                }
            }
            
            sendJsonResponse([
                'status' => 'success',
                'data' => [
                    'turma' => $turma,
                    'precisa_reconciliacao' => $precisa_reconciliacao,
                    'aulas_realizadas' => count($aulas_realizadas),
                    'detalhes_reconciliacao' => $detalhes_reconciliacao
                ]
            ]);
            
        } else {
            // Listar aulas existentes
            $aulas = $db->fetchAll("
                SELECT * FROM turma_aulas 
                WHERE turma_id = ? 
                ORDER BY ordem ASC
            ", [$turma_id]);
            
            sendJsonResponse([
                'status' => 'success',
                'data' => [
                    'turma' => $turma,
                    'aulas' => $aulas,
                    'total_aulas' => count($aulas)
                ]
            ]);
        }
        
    } catch (Exception $e) {
        sendJsonResponse([
            'status' => 'error',
            'message' => 'Erro ao buscar grade: ' . $e->getMessage()
        ], 500);
    }
    
} else {
    sendJsonResponse([
        'status' => 'error',
        'message' => 'Método não permitido'
    ], 405);
}

/**
 * Calcular dias disponíveis entre duas datas, considerando apenas os dias da semana especificados
 */
function calcularDiasDisponiveis($data_inicio, $data_fim, $dias_semana) {
    $dias = [];
    $inicio = new DateTime($data_inicio);
    $fim = new DateTime($data_fim);
    
    while ($inicio <= $fim) {
        $dia_semana = (int)$inicio->format('N'); // 1 = Segunda, 7 = Domingo
        
        if (in_array($dia_semana, $dias_semana)) {
            $dias[] = $inicio->format('Y-m-d');
        }
        
        $inicio->add(new DateInterval('P1D'));
    }
    
    return $dias;
}

/**
 * Distribuir aulas pelos dias disponíveis
 */
function distribuirAulas($dias_disponiveis, $total_aulas, $max_aulas_dia, $horario_inicio, $duracao_aula) {
    $aulas_distribuidas = [];
    $aula_atual = 0;
    
    foreach ($dias_disponiveis as $dia) {
        $aulas_no_dia = min($max_aulas_dia, $total_aulas - $aula_atual);
        
        for ($i = 0; $i < $aulas_no_dia; $i++) {
            $hora_inicio = calcularHoraInicio($horario_inicio, $i, $duracao_aula);
            $hora_fim = calcularHoraFim($hora_inicio, $duracao_aula);
            
            $aulas_distribuidas[] = [
                'data' => $dia,
                'hora_inicio' => $hora_inicio,
                'hora_fim' => $hora_fim
            ];
            
            $aula_atual++;
            
            if ($aula_atual >= $total_aulas) {
                break 2; // Sair dos dois loops
            }
        }
    }
    
    return $aulas_distribuidas;
}

/**
 * Calcular hora de início da aula
 */
function calcularHoraInicio($horario_base, $indice_aula, $duracao_aula) {
    $hora_base = new DateTime($horario_base);
    $minutos_adicionais = $indice_aula * $duracao_aula;
    $hora_base->add(new DateInterval('PT' . $minutos_adicionais . 'M'));
    
    return $hora_base->format('H:i:s');
}

/**
 * Calcular hora de fim da aula
 */
function calcularHoraFim($hora_inicio, $duracao_aula) {
    $hora = new DateTime($hora_inicio);
    $hora->add(new DateInterval('PT' . $duracao_aula . 'M'));
    
    return $hora->format('H:i:s');
}

/**
 * Obter disciplinas configuradas para uma categoria
 */
function obterDisciplinasParaCategoria($categoria_cnh) {
    global $configManager;
    
    $disciplinas = [];
    
    // Mapear campos da tabela para slugs das disciplinas
    $mapeamento = [
        'legislacao_transito_aulas' => [
            'slug' => 'legislacao_transito',
            'nome' => 'Legislação de Trânsito',
            'icone' => 'fas fa-gavel',
            'cor' => 'primary',
            'ordem' => 1
        ],
        'primeiros_socorros_aulas' => [
            'slug' => 'primeiros_socorros',
            'nome' => 'Primeiros Socorros',
            'icone' => 'fas fa-first-aid',
            'cor' => 'danger',
            'ordem' => 2
        ],
        'meio_ambiente_cidadania_aulas' => [
            'slug' => 'meio_ambiente',
            'nome' => 'Meio Ambiente e Cidadania',
            'icone' => 'fas fa-leaf',
            'cor' => 'success',
            'ordem' => 3
        ],
        'direcao_defensiva_aulas' => [
            'slug' => 'direcao_defensiva',
            'nome' => 'Direção Defensiva',
            'icone' => 'fas fa-shield-alt',
            'cor' => 'warning',
            'ordem' => 4
        ],
        'mecanica_basica_aulas' => [
            'slug' => 'mecanica_basica',
            'nome' => 'Mecânica Básica',
            'icone' => 'fas fa-tools',
            'cor' => 'info',
            'ordem' => 5
        ]
    ];
    
    // Para categorias combinadas (AB, AC, etc.), usar a primeira configuração
    $configuracoes = $configManager->getConfiguracoesParaCategoriaCombinada($categoria_cnh);
    $primeiraConfig = reset($configuracoes);
    
    if (!$primeiraConfig) {
        return [];
    }
    
    // Processar cada disciplina
    foreach ($mapeamento as $campo => $info) {
        $aulas = (int)($primeiraConfig[$campo] ?? 0);
        if ($aulas > 0) {
            $disciplinas[] = [
                'slug' => $info['slug'],
                'nome' => $info['nome'],
                'icone' => $info['icone'],
                'cor' => $info['cor'],
                'ordem' => $info['ordem'],
                'aulas' => $aulas
            ];
        }
    }
    
    // Ordenar por ordem definida
    usort($disciplinas, function($a, $b) {
        return $a['ordem'] <=> $b['ordem'];
    });
    
    return $disciplinas;
}

/**
 * Gerar aulas organizadas por disciplina
 */
function gerarAulasPorDisciplina($disciplinas, $turma_id, $duracao_aula, $data_inicio, $data_fim, $horario_inicio, $max_aulas_dia, $dias_semana) {
    $aulas_por_disciplina = [];
    $ordem_global = 1;
    
    foreach ($disciplinas as $disciplina) {
        $aulas_disciplina = [];
        
        for ($i = 1; $i <= $disciplina['aulas']; $i++) {
            $aulas_disciplina[] = [
                'disciplina' => $disciplina['slug'],
                'nome_disciplina' => $disciplina['nome'],
                'ordem_global' => $ordem_global++,
                'ordem_disciplina' => $i,
                'total_disciplina' => $disciplina['aulas'],
                'nome_aula' => $disciplina['nome'] . ' — Aula ' . $i . '/' . $disciplina['aulas'],
                'duracao_minutos' => $duracao_aula
            ];
        }
        
        $aulas_por_disciplina[$disciplina['slug']] = [
            'disciplina' => $disciplina,
            'aulas' => $aulas_disciplina
        ];
    }
    
    // Se há datas definidas, distribuir pelos dias
    if ($data_inicio && $data_fim) {
        $dias_disponiveis = calcularDiasDisponiveis($data_inicio, $data_fim, $dias_semana);
        $total_aulas = array_sum(array_column($disciplinas, 'aulas'));
        $aulas_distribuidas = distribuirAulas($dias_disponiveis, $total_aulas, $max_aulas_dia, $horario_inicio, $duracao_aula);
        
        $indice_distribuicao = 0;
        foreach ($aulas_por_disciplina as &$disciplina_data) {
            foreach ($disciplina_data['aulas'] as &$aula) {
                if (isset($aulas_distribuidas[$indice_distribuicao])) {
                    $aula['data_aula'] = $aulas_distribuidas[$indice_distribuicao]['data'];
                    $aula['hora_inicio'] = $aulas_distribuidas[$indice_distribuicao]['hora_inicio'];
                    $aula['hora_fim'] = $aulas_distribuidas[$indice_distribuicao]['hora_fim'];
                    $indice_distribuicao++;
                }
            }
        }
    }
    
    return $aulas_por_disciplina;
}

/**
 * Gerar nova grade (modo limpar e recriar)
 */
function gerarNovaGrade($turma_id, $aulas_por_disciplina) {
    global $db;
    
    // Limpar aulas existentes
    $db->query("DELETE FROM turma_aulas WHERE turma_id = ?", [$turma_id]);
    
    // Inserir novas aulas
    $aulas_criadas = [];
    $resumo_disciplinas = [];
    
    foreach ($aulas_por_disciplina as $slug => $disciplina_data) {
        $resumo_disciplinas[$slug] = [
            'nome' => $disciplina_data['disciplina']['nome'],
            'aulas_criadas' => 0
        ];
        
        foreach ($disciplina_data['aulas'] as $aula) {
            $aula_id = $db->insert('turma_aulas', [
                'turma_id' => $turma_id,
                'ordem' => $aula['ordem_global'],
                'nome_aula' => $aula['nome_aula'],
                'duracao_minutos' => $aula['duracao_minutos'],
                'data_aula' => $aula['data_aula'] ?? null,
                'hora_inicio' => $aula['hora_inicio'] ?? null,
                'hora_fim' => $aula['hora_fim'] ?? null,
                'tipo_conteudo' => 'teorica',
                'disciplina' => $aula['disciplina'],
                'status' => 'agendada'
            ]);
            
            $aulas_criadas[] = [
                'id' => $aula_id,
                'ordem' => $aula['ordem_global'],
                'disciplina' => $aula['disciplina'],
                'nome_aula' => $aula['nome_aula'],
                'data_aula' => $aula['data_aula'] ?? null,
                'hora_inicio' => $aula['hora_inicio'] ?? null,
                'hora_fim' => $aula['hora_fim'] ?? null
            ];
            
            $resumo_disciplinas[$slug]['aulas_criadas']++;
        }
    }
    
    return [
        'total_aulas' => count($aulas_criadas),
        'disciplinas' => $resumo_disciplinas,
        'aulas_criadas' => $aulas_criadas
    ];
}

/**
 * Reconciliar grade (modo preservar histórico)
 */
function reconciliarGrade($turma_id, $aulas_por_disciplina) {
    global $db;
    
    // Buscar aulas existentes
    $aulas_existentes = $db->fetchAll("
        SELECT * FROM turma_aulas 
        WHERE turma_id = ? 
        ORDER BY ordem ASC
    ", [$turma_id]);
    
    // Verificar se há aulas já realizadas
    $aulas_realizadas = $db->fetchAll("
        SELECT * FROM turma_aulas 
        WHERE turma_id = ? AND status = 'concluida'
        ORDER BY ordem ASC
    ", [$turma_id]);
    
    $aulas_adicionadas = [];
    $resumo_disciplinas = [];
    $delta = [];
    
    foreach ($aulas_por_disciplina as $slug => $disciplina_data) {
        $resumo_disciplinas[$slug] = [
            'nome' => $disciplina_data['disciplina']['nome'],
            'aulas_existentes' => 0,
            'aulas_adicionadas' => 0,
            'aulas_faltantes' => 0
        ];
        
        // Contar aulas existentes desta disciplina
        $aulas_existentes_disciplina = array_filter($aulas_existentes, function($aula) use ($slug) {
            return ($aula['disciplina'] ?? '') === $slug;
        });
        
        $resumo_disciplinas[$slug]['aulas_existentes'] = count($aulas_existentes_disciplina);
        $aulas_necessarias = count($disciplina_data['aulas']);
        $aulas_faltantes = $aulas_necessarias - count($aulas_existentes_disciplina);
        
        if ($aulas_faltantes > 0) {
            $resumo_disciplinas[$slug]['aulas_faltantes'] = $aulas_faltantes;
            
            // Adicionar aulas faltantes ao final
            $ultima_ordem = $db->fetch("
                SELECT MAX(ordem) as ultima_ordem 
                FROM turma_aulas 
                WHERE turma_id = ?
            ", [$turma_id]);
            
            $ordem_atual = ($ultima_ordem['ultima_ordem'] ?? 0) + 1;
            
            for ($i = 1; $i <= $aulas_faltantes; $i++) {
                $aula_template = $disciplina_data['aulas'][count($aulas_existentes_disciplina) + $i - 1];
                
                $aula_id = $db->insert('turma_aulas', [
                    'turma_id' => $turma_id,
                    'ordem' => $ordem_atual++,
                    'nome_aula' => $aula_template['nome_aula'],
                    'duracao_minutos' => $aula_template['duracao_minutos'],
                    'data_aula' => null, // Deixar para agendamento posterior
                    'hora_inicio' => null,
                    'hora_fim' => null,
                    'tipo_conteudo' => 'teorica',
                    'disciplina' => $slug,
                    'status' => 'agendada'
                ]);
                
                $aulas_adicionadas[] = [
                    'id' => $aula_id,
                    'disciplina' => $slug,
                    'nome_aula' => $aula_template['nome_aula']
                ];
                
                $resumo_disciplinas[$slug]['aulas_adicionadas']++;
            }
            
            $delta[] = "+{$aulas_faltantes} {$disciplina_data['disciplina']['nome']}";
        } else if ($aulas_faltantes < 0) {
            // Mais aulas do que necessário (não deve acontecer, mas registrar)
            $delta[] = "-" . abs($aulas_faltantes) . " {$disciplina_data['disciplina']['nome']}";
        }
    }
    
    return [
        'total_aulas_existentes' => count($aulas_existentes),
        'total_aulas_adicionadas' => count($aulas_adicionadas),
        'total_aulas_realizadas' => count($aulas_realizadas),
        'disciplinas' => $resumo_disciplinas,
        'delta' => $delta,
        'aulas_adicionadas' => $aulas_adicionadas
    ];
}
