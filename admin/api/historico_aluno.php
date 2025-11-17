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
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro interno: ' . $e->getMessage()]);
}

/**
 * Processar requisições GET
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
        
        // 1. Evento: Cadastro do aluno
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
        
        // 2. Eventos: Matrículas
        $matriculas = $db->fetchAll("
            SELECT id, aluno_id, categoria_cnh, tipo_servico, status, data_inicio, data_fim, criado_em
            FROM matriculas
            WHERE aluno_id = ?
            ORDER BY data_inicio DESC, id DESC
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
        
        // 3. Eventos: Exames Médico, Psicotécnico e Provas (Teórica/Prática)
        $exames = $db->fetchAll("
            SELECT id, aluno_id, tipo, status, resultado, data_agendada, data_resultado, protocolo, clinica_nome
            FROM exames
            WHERE aluno_id = ?
            AND tipo IN ('medico', 'psicotecnico', 'teorico', 'pratico')
            ORDER BY data_agendada DESC, data_resultado DESC
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
        
        // 4. Eventos: Faturas
        // Verificar se a tabela é 'faturas' ou 'financeiro_faturas'
        // Vamos tentar ambas para compatibilidade
        $faturas = [];
        
        // Tentar tabela 'faturas' primeiro (usada em admin/api/faturas.php)
        try {
            $faturas = $db->fetchAll("
                SELECT id, aluno_id, matricula_id, descricao, valor, vencimento, status, criado_em
                FROM faturas
                WHERE aluno_id = ?
                ORDER BY vencimento DESC, criado_em DESC
            ", [$alunoId]);
        } catch (Exception $e) {
            // Se não existir, tentar 'financeiro_faturas'
            try {
                $faturas = $db->fetchAll("
                    SELECT id, aluno_id, matricula_id, titulo as descricao, valor_total as valor, 
                           data_vencimento as vencimento, status, criado_em
                    FROM financeiro_faturas
                    WHERE aluno_id = ?
                    ORDER BY data_vencimento DESC, criado_em DESC
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
            if (isset($fatura['status']) && strtolower($fatura['status']) === 'paga') {
                // Tentar buscar data_pagamento da tabela pagamentos se existir
                $dataPagamento = null;
                try {
                    $pagamento = $db->fetch("
                        SELECT data_pagamento
                        FROM pagamentos
                        WHERE fatura_id = ?
                        ORDER BY data_pagamento DESC
                        LIMIT 1
                    ", [$fatura['id']]);
                    if ($pagamento && !empty($pagamento['data_pagamento'])) {
                        $dataPagamento = $pagamento['data_pagamento'];
                    }
                } catch (Exception $e) {
                    // Se não houver tabela pagamentos, usar data atual como fallback
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
            $hoje = date('Y-m-d');
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
        
        // 5. Eventos: Aulas Teóricas
        // Buscar matrícula teórica mais recente do aluno
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
        
        // 6. Eventos: Aulas Práticas
        // Buscar primeira e última aula prática do aluno
        $primeiraAulaPratica = $db->fetch("
            SELECT 
                id,
                aluno_id,
                data_aula,
                status,
                tipo_aula
            FROM aulas
            WHERE aluno_id = ?
            AND tipo_aula = 'pratica'
            AND status != 'cancelada'
            ORDER BY data_aula ASC
            LIMIT 1
        ", [$alunoId]);
        
        $ultimaAulaPratica = $db->fetch("
            SELECT 
                id,
                aluno_id,
                data_aula,
                status,
                tipo_aula
            FROM aulas
            WHERE aluno_id = ?
            AND tipo_aula = 'pratica'
            AND status = 'concluida'
            ORDER BY data_aula DESC
            LIMIT 1
        ", [$alunoId]);
        
        // Contar total de aulas práticas realizadas para descrição
        $totalAulasPraticas = $db->fetch("
            SELECT COUNT(*) as total
            FROM aulas
            WHERE aluno_id = ?
            AND tipo_aula = 'pratica'
            AND status = 'concluida'
        ", [$alunoId]);
        $totalRealizadas = $totalAulasPraticas ? (int)$totalAulasPraticas['total'] : 0;
        
        // Contar total de aulas práticas contratadas/agendadas (estimativa)
        $totalAulasAgendadas = $db->fetch("
            SELECT COUNT(*) as total
            FROM aulas
            WHERE aluno_id = ?
            AND tipo_aula = 'pratica'
            AND status != 'cancelada'
        ", [$alunoId]);
        $totalContratadas = $totalAulasAgendadas ? (int)$totalAulasAgendadas['total'] : 0;
        
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
        
        // Ordenar eventos por data (mais recente primeiro)
        usort($eventos, function($a, $b) {
            return strtotime($b['data']) - strtotime($a['data']);
        });
        
        echo json_encode([
            'success' => true,
            'eventos' => $eventos
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao buscar histórico do aluno: ' . $e->getMessage()
        ]);
    }
}

