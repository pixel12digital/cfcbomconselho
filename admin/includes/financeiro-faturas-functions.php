<?php
/**
 * Funções compartilhadas para o módulo de faturas
 */

/**
 * Função reutilizável para construir descrição sugerida da fatura baseada no aluno
 * 
 * @param Database $db Instância do banco de dados
 * @param int|null $alunoId ID do aluno
 * @param int|null $matriculaId ID da matrícula (opcional)
 * @return string|null Descrição sugerida ou null se não encontrada
 */
function buildDescricaoSugestaoFatura($db, $alunoId, $matriculaId = null) {
    $descricao_sugestao = null;
    
    if (!$alunoId && !$matriculaId) {
        return null;
    }
    
    try {
        // Prioridade 1: Buscar operações do campo 'operacoes' do aluno (JSON)
        // Este campo contém as operações configuradas em "Curso e Serviços"
        if ($alunoId) {
            $aluno = $db->fetch("
                SELECT operacoes, tipo_servico, categoria_cnh
                FROM alunos
                WHERE id = ?
            ", [$alunoId]);
            
            if ($aluno && !empty($aluno['operacoes'])) {
                // Tentar decodificar JSON de operações
                $operacoes = json_decode($aluno['operacoes'], true);
                
                if (is_array($operacoes) && count($operacoes) > 0) {
                    // Montar descrição baseada nas operações
                    $descricoes = [];
                    
                    foreach ($operacoes as $operacao) {
                        // Tentar diferentes nomes de campos (compatibilidade)
                        // O formulário salva como: {tipo: 'primeira_habilitacao', categoria: 'AB'}
                        // Mas pode ter variações: tipo_servico/categoria_cnh ou tipo/categoria
                        $tipo_servico = $operacao['tipo'] ?? $operacao['tipo_servico'] ?? '';
                        $categoria = $operacao['categoria'] ?? $operacao['categoria_cnh'] ?? '';
                        
                        // Formatar tipo de serviço
                        $tipo_formatado = '';
                        switch ($tipo_servico) {
                            case 'primeira_habilitacao':
                                $tipo_formatado = 'Primeira Habilitação';
                                break;
                            case 'adicao':
                                $tipo_formatado = 'Adição de Categoria';
                                break;
                            case 'mudanca':
                                $tipo_formatado = 'Mudança de Categoria';
                                break;
                            default:
                                $tipo_formatado = ucfirst(str_replace('_', ' ', $tipo_servico));
                        }
                        
                        // Formatar categoria
                        if ($categoria) {
                            $categoria_formatada = $categoria;
                            if ($categoria === 'AB') {
                                $categoria_formatada = 'AB (A + B)';
                            } elseif (strlen($categoria) > 1 && $categoria !== 'AB') {
                                // Se for combinação como "AC", formatar como "A + C"
                                $categoria_formatada = implode(' + ', str_split($categoria));
                            }
                            $descricoes[] = $tipo_formatado . ' - ' . $categoria_formatada;
                        } else {
                            $descricoes[] = $tipo_formatado;
                        }
                    }
                    
                    // Juntar todas as operações em uma descrição objetiva
                    if (count($descricoes) > 0) {
                        $descricao_sugestao = implode(' / ', $descricoes);
                    }
                }
            }
        }
        
        // Prioridade 2: Se não encontrou operações, buscar da tabela matriculas
        if (empty($descricao_sugestao)) {
            if ($matriculaId) {
                // Buscar matrícula específica
                $matricula = $db->fetch("
                    SELECT m.*, 
                           CASE 
                               WHEN m.tipo_servico = 'primeira_habilitacao' THEN 'Primeira Habilitação'
                               WHEN m.tipo_servico = 'adicao' THEN 'Adição de Categoria'
                               WHEN m.tipo_servico = 'mudanca' THEN 'Mudança de Categoria'
                               ELSE m.tipo_servico
                           END as tipo_servico_formatado
                    FROM matriculas m
                    WHERE m.id = ? AND m.status = 'ativa'
                ", [$matriculaId]);
            } elseif ($alunoId) {
                // Buscar todas as matrículas ativas do aluno
                $matriculas = $db->fetchAll("
                    SELECT m.*, 
                           CASE 
                               WHEN m.tipo_servico = 'primeira_habilitacao' THEN 'Primeira Habilitação'
                               WHEN m.tipo_servico = 'adicao' THEN 'Adição de Categoria'
                               WHEN m.tipo_servico = 'mudanca' THEN 'Mudança de Categoria'
                               ELSE m.tipo_servico
                           END as tipo_servico_formatado
                    FROM matriculas m
                    WHERE m.aluno_id = ? AND m.status = 'ativa'
                    ORDER BY m.data_inicio DESC
                ", [$alunoId]);
                
                if (count($matriculas) > 0) {
                    // Se houver múltiplas matrículas, usar a primeira
                    $matricula = $matriculas[0];
                    
                    // Se houver mais de uma, montar descrição com todas
                    if (count($matriculas) > 1) {
                        $descricoes = [];
                        foreach ($matriculas as $mat) {
                            $tipo = $mat['tipo_servico_formatado'] ?? '';
                            $cat = $mat['categoria_cnh'] ?? '';
                            
                            if ($cat) {
                                $cat_formatada = ($cat === 'AB') ? 'AB (A + B)' : (strlen($cat) > 1 ? implode(' + ', str_split($cat)) : $cat);
                                $descricoes[] = $tipo . ' - ' . $cat_formatada;
                            } else {
                                $descricoes[] = $tipo;
                            }
                        }
                        $descricao_sugestao = implode(' / ', $descricoes);
                    }
                }
            }
            
            // Montar descrição de uma única matrícula
            if (empty($descricao_sugestao) && isset($matricula)) {
                $tipo_servico = $matricula['tipo_servico_formatado'] ?? $matricula['tipo_servico'] ?? '';
                $categoria = $matricula['categoria_cnh'] ?? '';
                
                $descricao_sugestao = trim($tipo_servico);
                if ($categoria) {
                    $categoria_formatada = $categoria;
                    if ($categoria === 'AB') {
                        $categoria_formatada = 'AB (A + B)';
                    } elseif (strlen($categoria) > 1 && $categoria !== 'AB') {
                        $categoria_formatada = implode(' + ', str_split($categoria));
                    }
                    $descricao_sugestao .= ' - ' . $categoria_formatada;
                }
            }
        }
        
        // Prioridade 3: Fallback para campos diretos do aluno
        if (empty($descricao_sugestao) && $alunoId) {
            $aluno = $db->fetch("
                SELECT tipo_servico, categoria_cnh
                FROM alunos
                WHERE id = ?
            ", [$alunoId]);
            
            if ($aluno) {
                $tipo_servico = $aluno['tipo_servico'] ?? '';
                $categoria = $aluno['categoria_cnh'] ?? '';
                
                if ($tipo_servico || $categoria) {
                    $tipo_formatado = '';
                    switch ($tipo_servico) {
                        case 'primeira_habilitacao':
                            $tipo_formatado = 'Primeira Habilitação';
                            break;
                        case 'adicao':
                            $tipo_formatado = 'Adição de Categoria';
                            break;
                        case 'mudanca':
                            $tipo_formatado = 'Mudança de Categoria';
                            break;
                    }
                    
                    if ($categoria) {
                        $categoria_formatada = ($categoria === 'AB') ? 'AB (A + B)' : (strlen($categoria) > 1 ? implode(' + ', str_split($categoria)) : $categoria);
                        $descricao_sugestao = $tipo_formatado . ' - ' . $categoria_formatada;
                    } else {
                        $descricao_sugestao = $tipo_formatado;
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log('[FATURA DESC] Erro ao buscar descrição sugerida: ' . $e->getMessage());
    }
    
    return $descricao_sugestao;
}

