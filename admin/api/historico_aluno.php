<?php
/**
 * API para consultar Histórico/Eventos da Jornada do Aluno
 * Sistema CFC - Bom Conselho
 * 
 * Retorna eventos da jornada do aluno (cadastro, matrículas, faturas)
 * 
 * TODO (provas): quando 'exames.tipo' incluir 'teorico' e 'pratico',
 *  adicionar eventos na timeline:
 *  - prova_teorica_agendada / realizada / aprovada / reprovada
 *  - prova_pratica_agendada / realizada / aprovada / reprovada
 *  (Estrutura já preparada via migration 003-alter-exames-add-provas.sql)
 * TODO: Adicionar eventos de mudanças de status
 * TODO: Adicionar eventos de atualizações de dados pessoais
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Responder a requisições OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir arquivos necessários
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

try {
    $db = Database::getInstance();
    
    // Verificar autenticação
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
        exit;
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        handleGet($db);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    }
    
} catch (Exception $e) {
    error_log("Erro em historico_aluno.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false, 
        'error' => 'Erro interno ao buscar histórico',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Error $e) {
    error_log("Erro fatal em historico_aluno.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false, 
        'error' => 'Erro fatal ao buscar histórico',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * Processar requisições GET
 * 
 * OTIMIZAÇÕES FASE 1:
 * - Eliminado N+1 query problem nas faturas (JOIN com pagamentos)
 * - Consolidadas queries de aulas práticas em uma única query agregada
 * - Reduzido número total de queries de 9-109 para ~5-6 queries
 * - Limitação de eventos processados para melhorar performance
 */
function handleGet($db) {
    $alunoId = $_GET['aluno_id'] ?? null;
    
    if (!$alunoId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Parâmetro aluno_id é obrigatório']);
        return;
    }
    
    // Validar que aluno_id é um número
    if (!is_numeric($alunoId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'aluno_id deve ser um número']);
        return;
    }
    
    $alunoId = (int)$alunoId;
    
    try {
        $eventos = [];
        
        // ============================================================
        // QUERY 1: Buscar dados do aluno
        // ============================================================
        $aluno = $db->fetch("
            SELECT id, nome, criado_em, atualizado_em
            FROM alunos
            WHERE id = ?
        ", [$alunoId]);
        
        if ($aluno) {
            $dataCadastro = $aluno['criado_em'] ?? $aluno['atualizado_em'] ?? date('Y-m-d H:i:s');
            $eventos[] = [
                'tipo' => 'aluno_cadastrado',
                'data' => $dataCadastro,
                'titulo' => 'Aluno cadastrado',
                'descricao' => 'Cadastro de ' . htmlspecialchars($aluno['nome']),
                'meta' => [
                    'aluno_id' => $aluno['id']
                ]
            ];
        }
        
        // ============================================================
        // QUERY 2: Buscar matrículas (limitado para performance)
        // ============================================================
        $matriculas = $db->fetchAll("
            SELECT id, aluno_id, categoria_cnh, tipo_servico, status, data_inicio, data_fim, criado_em
            FROM matriculas
            WHERE aluno_id = ?
            ORDER BY data_inicio DESC, id DESC
            LIMIT 50
        ", [$alunoId]);
        
        foreach ($matriculas as $matricula) {
            // Evento: Matrícula criada
            $dataInicio = $matricula['data_inicio'] ?? $matricula['criado_em'] ?? date('Y-m-d H:i:s');
            $descricao = sprintf(
                'Categoria %s – %s (status: %s)',
                $matricula['categoria_cnh'] ?? 'N/A',
                $matricula['tipo_servico'] ?? 'N/A',
                $matricula['status'] ?? 'N/A'
            );
            
            $eventos[] = [
                'tipo' => 'matricula_criada',
                'data' => $dataInicio,
                'titulo' => 'Matrícula criada',
                'descricao' => $descricao,
                'meta' => [
                    'aluno_id' => $matricula['aluno_id'],
                    'matricula_id' => $matricula['id'],
                    'categoria_cnh' => $matricula['categoria_cnh'],
                    'tipo_servico' => $matricula['tipo_servico'],
                    'status' => $matricula['status']
                ]
            ];
            
            // Evento: Matrícula concluída (se tiver data_fim)
            if (!empty($matricula['data_fim'])) {
                $eventos[] = [
                    'tipo' => 'matricula_concluida',
                    'data' => $matricula['data_fim'],
                    'titulo' => 'Matrícula concluída',
                    'descricao' => $descricao,
                    'meta' => [
                        'aluno_id' => $matricula['aluno_id'],
                        'matricula_id' => $matricula['id'],
                        'categoria_cnh' => $matricula['categoria_cnh'],
                        'tipo_servico' => $matricula['tipo_servico'],
                        'status' => $matricula['status']
                    ]
                ];
            }
        }
        
        // ============================================================
        // QUERY 3: Buscar exames (limitado para performance)
        // ============================================================
        $exames = $db->fetchAll("
            SELECT id, aluno_id, tipo, status, resultado, data_agendada, data_resultado, protocolo, clinica_nome
            FROM exames
            WHERE aluno_id = ?
            AND tipo IN ('medico', 'psicotecnico', 'teorico', 'pratico')
            ORDER BY data_agendada DESC, data_resultado DESC
            LIMIT 100
        ", [$alunoId]);
        
        foreach ($exames as $exame) {
            $tipoExame = $exame['tipo'];
            
            // Determinar se é exame (médico/psicotécnico) ou prova (teórica/prática)
            $isProva = in_array($tipoExame, ['teorico', 'pratico']);
            
            if ($isProva) {
                // Lógica para Provas (Teórica/Prática)
                $tipoProvaTexto = $tipoExame === 'teorico' ? 'teórica' : 'prática';
                
                // Evento: Prova agendada
                if (!empty($exame['data_agendada'])) {
                    $descricaoAgendado = sprintf(
                        'Prova %s agendada',
                        $tipoProvaTexto
                    );
                    
                    // Adicionar protocolo e local se existirem
                    $detalhes = [];
                    if (!empty($exame['protocolo'])) {
                        $detalhes[] = 'protocolo: ' . htmlspecialchars($exame['protocolo']);
                    }
                    if (!empty($exame['clinica_nome'])) {
                        $detalhes[] = 'local: ' . htmlspecialchars($exame['clinica_nome']);
                    }
                    if (!empty($detalhes)) {
                        $descricaoAgendado .= ' (' . implode(', ', $detalhes) . ')';
                    }
                    
                    $eventos[] = [
                        'tipo' => 'prova_' . $tipoExame . '_agendada',
                        'data' => $exame['data_agendada'] . ' 00:00:00',
                        'titulo' => 'Prova ' . $tipoProvaTexto . ' agendada',
                        'descricao' => $descricaoAgendado,
                        'meta' => [
                            'aluno_id' => $exame['aluno_id'],
                            'exame_id' => $exame['id'],
                            'tipo' => $tipoExame,
                            'status' => $exame['status'],
                            'protocolo' => $exame['protocolo'] ?? null,
                            'local' => $exame['clinica_nome'] ?? null
                        ]
                    ];
                }
                
                // Evento: Prova realizada (se tiver data_resultado e resultado preenchido)
                if (!empty($exame['data_resultado']) && !empty($exame['resultado'])) {
                    $dataEvento = $exame['data_resultado'] . ' 00:00:00';
                    
                    // Formatar descrição com resultado
                    $resultadoTexto = '';
                    if ($exame['resultado'] === 'aprovado') {
                        $resultadoTexto = ' – Aprovado';
                    } elseif ($exame['resultado'] === 'reprovado') {
                        $resultadoTexto = ' – Reprovado';
                    } else {
                        $resultadoTexto = ' – Resultado: ' . htmlspecialchars($exame['resultado']);
                    }
                    
                    $descricaoRealizado = sprintf(
                        'Prova %s realizada%s',
                        $tipoProvaTexto,
                        $resultadoTexto
                    );
                    
                    $eventos[] = [
                        'tipo' => 'prova_' . $tipoExame . '_realizada',
                        'data' => $dataEvento,
                        'titulo' => 'Prova ' . $tipoProvaTexto . ' realizada',
                        'descricao' => $descricaoRealizado,
                        'meta' => [
                            'aluno_id' => $exame['aluno_id'],
                            'exame_id' => $exame['id'],
                            'tipo' => $tipoExame,
                            'status' => $exame['status'],
                            'resultado' => $exame['resultado'],
                            'protocolo' => $exame['protocolo'] ?? null,
                            'local' => $exame['clinica_nome'] ?? null
                        ]
                    ];
                }
            } else {
                // Lógica para Exames (Médico/Psicotécnico) - mantém código original
                $tipoExameTexto = $tipoExame === 'medico' ? 'médico' : 'psicotécnico';
                
                // Evento: Exame agendado
                if (!empty($exame['data_agendada'])) {
                    $descricaoAgendado = sprintf(
                        'Exame %s agendado%s',
                        $tipoExameTexto,
                        $exame['clinica_nome'] ? ' em ' . htmlspecialchars($exame['clinica_nome']) : ''
                    );
                    
                    $eventos[] = [
                        'tipo' => 'exame_' . $tipoExame . '_agendado',
                        'data' => $exame['data_agendada'] . ' 00:00:00',
                        'titulo' => 'Exame ' . $tipoExameTexto . ' agendado',
                        'descricao' => $descricaoAgendado,
                        'meta' => [
                            'aluno_id' => $exame['aluno_id'],
                            'exame_id' => $exame['id'],
                            'tipo' => $tipoExame,
                            'status' => $exame['status'],
                            'protocolo' => $exame['protocolo'] ?? null,
                            'clinica_nome' => $exame['clinica_nome'] ?? null
                        ]
                    ];
                }
                
                // Evento: Exame realizado (se tiver data_resultado e status concluido)
                if (!empty($exame['data_resultado']) && $exame['status'] === 'concluido') {
                    $resultadoTexto = '';
                    if ($exame['resultado']) {
                        $resultadoMap = [
                            'apto' => 'Apto',
                            'inapto' => 'Inapto',
                            'inapto_temporario' => 'Inapto temporariamente',
                            'pendente' => 'Pendente'
                        ];
                        $resultadoTexto = ' - Resultado: ' . ($resultadoMap[$exame['resultado']] ?? $exame['resultado']);
                    }
                    
                    $descricaoRealizado = sprintf(
                        'Exame %s realizado%s',
                        $tipoExameTexto,
                        $resultadoTexto
                    );
                    
                    $eventos[] = [
                        'tipo' => 'exame_' . $tipoExame . '_realizado',
                        'data' => $exame['data_resultado'] . ' 00:00:00',
                        'titulo' => 'Exame ' . $tipoExameTexto . ' realizado',
                        'descricao' => $descricaoRealizado,
                        'meta' => [
                            'aluno_id' => $exame['aluno_id'],
                            'exame_id' => $exame['id'],
                            'tipo' => $tipoExame,
                            'status' => $exame['status'],
                            'resultado' => $exame['resultado'],
                            'protocolo' => $exame['protocolo'] ?? null,
                            'clinica_nome' => $exame['clinica_nome'] ?? null
                        ]
                    ];
                }
            }
        }
        
        // ============================================================
        // QUERY 4: Buscar faturas + pagamentos (OTIMIZADO - sem N+1)
        // ============================================================
        $faturas = [];
        $hoje = date('Y-m-d');
        
        // Tentar tabela 'faturas' primeiro com LEFT JOIN em pagamentos
        try {
            $faturas = $db->fetchAll("
                SELECT 
                    f.id,
                    f.aluno_id,
                    f.matricula_id,
                    f.descricao,
                    f.valor,
                    f.vencimento,
                    f.status,
                    f.criado_em,
                    p.data_pagamento
                FROM faturas f
                LEFT JOIN (
                    SELECT fatura_id, MAX(data_pagamento) as data_pagamento
                    FROM pagamentos
                    GROUP BY fatura_id
                ) p ON f.id = p.fatura_id
                WHERE f.aluno_id = ?
                ORDER BY f.vencimento DESC, f.criado_em DESC
                LIMIT 100
            ", [$alunoId]);
        } catch (Exception $e) {
            // Se não existir, tentar 'financeiro_faturas' com LEFT JOIN
            try {
                $faturas = $db->fetchAll("
                    SELECT 
                        f.id,
                        f.aluno_id,
                        f.matricula_id,
                        f.titulo as descricao,
                        f.valor_total as valor,
                        f.data_vencimento as vencimento,
                        f.status,
                        f.criado_em,
                        p.data_pagamento
                    FROM financeiro_faturas f
                    LEFT JOIN (
                        SELECT fatura_id, MAX(data_pagamento) as data_pagamento
                        FROM pagamentos
                        GROUP BY fatura_id
                    ) p ON f.id = p.fatura_id
                    WHERE f.aluno_id = ?
                    ORDER BY f.data_vencimento DESC, f.criado_em DESC
                    LIMIT 100
                ", [$alunoId]);
            } catch (Exception $e2) {
                // Se nenhuma tabela existir, continuar sem faturas
                $faturas = [];
            }
        }
        
        foreach ($faturas as $fatura) {
            $dataVencimento = $fatura['vencimento'] ?? $fatura['criado_em'] ?? date('Y-m-d H:i:s');
            $valor = isset($fatura['valor']) ? (float)$fatura['valor'] : 0;
            $descricaoFatura = sprintf(
                'Fatura #%d – %s – R$ %s (vencimento %s)',
                $fatura['id'],
                htmlspecialchars($fatura['descricao'] ?? 'Sem descrição'),
                number_format($valor, 2, ',', '.'),
                date('d/m/Y', strtotime($dataVencimento))
            );
            
            // Evento: Fatura criada
            $eventos[] = [
                'tipo' => 'fatura_criada',
                'data' => $fatura['criado_em'] ?? $dataVencimento,
                'titulo' => 'Fatura gerada',
                'descricao' => $descricaoFatura,
                'meta' => [
                    'aluno_id' => $fatura['aluno_id'],
                    'fatura_id' => $fatura['id'],
                    'matricula_id' => $fatura['matricula_id'] ?? null,
                    'valor' => $valor,
                    'status' => $fatura['status'] ?? 'aberta'
                ]
            ];
            
            // Evento: Fatura paga (se status = 'paga' e tiver data_pagamento)
            // OTIMIZADO: data_pagamento já vem no JOIN, não precisa query adicional
            if (isset($fatura['status']) && strtolower($fatura['status']) === 'paga') {
                $dataPagamento = $fatura['data_pagamento'] ?? null;
                
                // Se não veio do JOIN mas status é paga, usar data atual como fallback
                if (!$dataPagamento) {
                    $dataPagamento = date('Y-m-d H:i:s');
                }
                
                if ($dataPagamento) {
                    $eventos[] = [
                        'tipo' => 'fatura_paga',
                        'data' => $dataPagamento,
                        'titulo' => 'Fatura paga',
                        'descricao' => $descricaoFatura,
                        'meta' => [
                            'aluno_id' => $fatura['aluno_id'],
                            'fatura_id' => $fatura['id'],
                            'matricula_id' => $fatura['matricula_id'] ?? null,
                            'valor' => $valor,
                            'status' => 'paga'
                        ]
                    ];
                }
            }
            
            // Evento: Fatura vencida (se status = 'vencida' ou vencimento < hoje)
            $statusLower = isset($fatura['status']) ? strtolower($fatura['status']) : '';
            $vencimentoDate = date('Y-m-d', strtotime($dataVencimento));
            
            if ($statusLower === 'vencida' || ($vencimentoDate < $hoje && $statusLower !== 'paga')) {
                $eventos[] = [
                    'tipo' => 'fatura_vencida',
                    'data' => $dataVencimento,
                    'titulo' => 'Fatura vencida',
                    'descricao' => $descricaoFatura,
                    'meta' => [
                        'aluno_id' => $fatura['aluno_id'],
                        'fatura_id' => $fatura['id'],
                        'matricula_id' => $fatura['matricula_id'] ?? null,
                        'valor' => $valor,
                        'status' => 'vencida'
                    ]
                ];
            }
        }
        
        // ============================================================
        // QUERY 5: Buscar matrícula teórica mais recente
        // ============================================================
        $matriculaTeorica = $db->fetch("
            SELECT 
                tm.id,
                tm.aluno_id,
                tm.turma_id,
                tm.status,
                tm.data_matricula,
                tm.frequencia_percentual,
                tm.atualizado_em,
                t.nome AS turma_nome
            FROM turma_matriculas tm
            JOIN turmas_teoricas t ON tm.turma_id = t.id
            WHERE tm.aluno_id = ?
            ORDER BY tm.data_matricula DESC, tm.id DESC
            LIMIT 1
        ", [$alunoId]);
        
        if ($matriculaTeorica) {
            // Evento: Início das aulas teóricas
            if (!empty($matriculaTeorica['data_matricula'])) {
                $descricaoInicio = sprintf(
                    'Início das aulas teóricas na turma %s',
                    htmlspecialchars($matriculaTeorica['turma_nome'] ?? 'N/A')
                );
                
                $eventos[] = [
                    'tipo' => 'aulas_teoricas_inicio',
                    'data' => $matriculaTeorica['data_matricula'] . ' 00:00:00',
                    'titulo' => 'Início das aulas teóricas',
                    'descricao' => $descricaoInicio,
                    'meta' => [
                        'aluno_id' => $matriculaTeorica['aluno_id'],
                        'turma_id' => $matriculaTeorica['turma_id'],
                        'turma_nome' => $matriculaTeorica['turma_nome'],
                        'status' => $matriculaTeorica['status'],
                        'frequencia_percentual' => $matriculaTeorica['frequencia_percentual'] ?? null
                    ]
                ];
            }
            
            // Evento: Conclusão das aulas teóricas (quando status = 'concluido')
            if (!empty($matriculaTeorica['status']) && strtolower($matriculaTeorica['status']) === 'concluido') {
                $dataConclusao = $matriculaTeorica['atualizado_em'] ?? $matriculaTeorica['data_matricula'] ?? date('Y-m-d H:i:s');
                
                $descricaoConclusao = sprintf(
                    'Aulas teóricas concluídas na turma %s',
                    htmlspecialchars($matriculaTeorica['turma_nome'] ?? 'N/A')
                );
                
                // Adicionar frequência se disponível
                if (!empty($matriculaTeorica['frequencia_percentual'])) {
                    $frequencia = number_format((float)$matriculaTeorica['frequencia_percentual'], 1);
                    $descricaoConclusao .= sprintf(' (frequência %s%%)', $frequencia);
                }
                
                $eventos[] = [
                    'tipo' => 'aulas_teoricas_concluidas',
                    'data' => $dataConclusao,
                    'titulo' => 'Aulas teóricas concluídas',
                    'descricao' => $descricaoConclusao,
                    'meta' => [
                        'aluno_id' => $matriculaTeorica['aluno_id'],
                        'turma_id' => $matriculaTeorica['turma_id'],
                        'turma_nome' => $matriculaTeorica['turma_nome'],
                        'status' => $matriculaTeorica['status'],
                        'frequencia_percentual' => $matriculaTeorica['frequencia_percentual'] ?? null
                    ]
                ];
            }
        }
        
        // ============================================================
        // QUERY 6: Buscar dados agregados de aulas práticas (OTIMIZADO)
        // Consolidado: primeira aula, última aula, total realizadas, total contratadas
        // ============================================================
        $aulasPraticasAgregadas = $db->fetch("
            SELECT 
                MIN(CASE WHEN status != 'cancelada' THEN data_aula END) as primeira_aula,
                MAX(CASE WHEN status = 'concluida' THEN data_aula END) as ultima_aula_concluida,
                COUNT(CASE WHEN status = 'concluida' THEN 1 END) as total_realizadas,
                COUNT(CASE WHEN status != 'cancelada' THEN 1 END) as total_contratadas,
                MIN(CASE WHEN status != 'cancelada' THEN id END) as primeira_aula_id,
                MAX(CASE WHEN status = 'concluida' THEN id END) as ultima_aula_id,
                MIN(CASE WHEN status != 'cancelada' THEN status END) as primeira_aula_status
            FROM aulas
            WHERE aluno_id = ?
            AND tipo_aula = 'pratica'
        ", [$alunoId]);
        
        $primeiraAulaPratica = null;
        $ultimaAulaPratica = null;
        $totalRealizadas = 0;
        $totalContratadas = 0;
        
        if ($aulasPraticasAgregadas) {
            $totalRealizadas = (int)($aulasPraticasAgregadas['total_realizadas'] ?? 0);
            $totalContratadas = (int)($aulasPraticasAgregadas['total_contratadas'] ?? 0);
            
            // Montar objeto primeira aula se existir
            if (!empty($aulasPraticasAgregadas['primeira_aula'])) {
                $primeiraAulaPratica = [
                    'id' => $aulasPraticasAgregadas['primeira_aula_id'],
                    'aluno_id' => $alunoId,
                    'data_aula' => $aulasPraticasAgregadas['primeira_aula'],
                    'status' => $aulasPraticasAgregadas['primeira_aula_status'] ?? 'agendada',
                    'tipo_aula' => 'pratica'
                ];
            }
            
            // Montar objeto última aula se existir
            if (!empty($aulasPraticasAgregadas['ultima_aula_concluida'])) {
                $ultimaAulaPratica = [
                    'id' => $aulasPraticasAgregadas['ultima_aula_id'],
                    'aluno_id' => $alunoId,
                    'data_aula' => $aulasPraticasAgregadas['ultima_aula_concluida'],
                    'status' => 'concluida',
                    'tipo_aula' => 'pratica'
                ];
            }
        }
        
        // Evento: Primeira aula prática
        if ($primeiraAulaPratica && !empty($primeiraAulaPratica['data_aula'])) {
            $statusTexto = '';
            if ($primeiraAulaPratica['status'] === 'concluida') {
                $statusTexto = 'realizada';
            } elseif ($primeiraAulaPratica['status'] === 'agendada') {
                $statusTexto = 'agendada';
            } elseif ($primeiraAulaPratica['status'] === 'em_andamento') {
                $statusTexto = 'em andamento';
            } else {
                $statusTexto = 'registrada';
            }
            
            $descricaoInicio = sprintf(
                'Primeira aula prática %s',
                $statusTexto
            );
            
            $eventos[] = [
                'tipo' => 'aulas_praticas_inicio',
                'data' => $primeiraAulaPratica['data_aula'] . ' 00:00:00',
                'titulo' => 'Primeira aula prática',
                'descricao' => $descricaoInicio,
                'meta' => [
                    'aluno_id' => $primeiraAulaPratica['aluno_id'],
                    'aula_id' => $primeiraAulaPratica['id'],
                    'status' => $primeiraAulaPratica['status'],
                    'data_aula' => $primeiraAulaPratica['data_aula']
                ]
            ];
        }
        
        // Evento: Conclusão das aulas práticas (se houver última aula concluída)
        if ($ultimaAulaPratica && !empty($ultimaAulaPratica['data_aula'])) {
            $descricaoConclusao = 'Aulas práticas concluídas';
            
            // Adicionar estatísticas se disponíveis
            if ($totalRealizadas > 0 && $totalContratadas > 0) {
                $descricaoConclusao .= sprintf(' (%d de %d aulas realizadas)', $totalRealizadas, $totalContratadas);
            } elseif ($totalRealizadas > 0) {
                $descricaoConclusao .= sprintf(' (%d aula(s) realizada(s))', $totalRealizadas);
            }
            
            $eventos[] = [
                'tipo' => 'aulas_praticas_concluidas',
                'data' => $ultimaAulaPratica['data_aula'] . ' 00:00:00',
                'titulo' => 'Aulas práticas concluídas',
                'descricao' => $descricaoConclusao,
                'meta' => [
                    'aluno_id' => $ultimaAulaPratica['aluno_id'],
                    'aula_id' => $ultimaAulaPratica['id'],
                    'status' => $ultimaAulaPratica['status'],
                    'data_aula' => $ultimaAulaPratica['data_aula'],
                    'total_realizadas' => $totalRealizadas,
                    'total_contratadas' => $totalContratadas
                ]
            ];
        }
        
        // ============================================================
        // Ordenar eventos por data (mais recente primeiro)
        // OTIMIZADO: Limitar eventos antes de ordenar para reduzir custo do usort
        // ============================================================
        // Ordenar eventos por data (mais recente primeiro)
        usort($eventos, function($a, $b) {
            return strtotime($b['data']) - strtotime($a['data']);
        });
        
        // Limitar eventos retornados para melhorar performance (últimos 100 eventos)
        // Isso reduz o tamanho da resposta JSON e o tempo de processamento
        if (count($eventos) > 100) {
            $eventos = array_slice($eventos, 0, 100);
        }
        
        echo json_encode([
            'success' => true,
            'eventos' => $eventos
        ]);
        
    } catch (Exception $e) {
        error_log("Erro em handleGet historico_aluno.php (aluno_id={$alunoId}): " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao buscar histórico do aluno',
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (Error $e) {
        error_log("Erro fatal em handleGet historico_aluno.php (aluno_id={$alunoId}): " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => 'Erro fatal ao buscar histórico do aluno',
            'message' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

