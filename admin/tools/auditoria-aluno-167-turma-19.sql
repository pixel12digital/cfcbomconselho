-- =====================================================
-- AUDITORIA SQL - Aluno 167 + Turma 19
-- Objetivo: Descobrir em qual filtro o aluno está sendo excluído
-- =====================================================

-- 3.1 – Dados básicos do aluno 167
SELECT 
    id, 
    nome, 
    status, 
    cfc_id,
    categoria_cnh
FROM alunos
WHERE id = 167;

-- 3.2 – Matrículas do aluno 167 relacionadas a turmas teóricas
SELECT 
    m.*,
    'Matrícula ativa?' as observacao
FROM matriculas m
WHERE m.aluno_id = 167;

-- Verificar se existe matrícula ativa
SELECT 
    COUNT(*) as total_matriculas_ativas,
    GROUP_CONCAT(id) as ids_matriculas
FROM matriculas m
WHERE m.aluno_id = 167 
    AND m.status = 'ativa';

-- 3.3 – Turma teórica em questão (turma_id = 19)
SELECT 
    tt.*,
    'Turma para matrícula' as observacao
FROM turmas_teoricas tt
WHERE tt.id = 19;

-- 3.4 – Versão INCREMENTAL da query da API

-- 3.4.1 – Base: todos os alunos (verificar se aluno existe)
SELECT 
    a.id, 
    a.nome, 
    a.status, 
    a.cfc_id
FROM alunos a
WHERE a.id = 167;

-- 3.4.2 – Acrescentar filtro de status permitido
SELECT 
    a.id, 
    a.nome, 
    a.status,
    'Status OK?' as verificado
FROM alunos a
WHERE a.id = 167
  AND a.status IN ('ativo', 'em_andamento');

-- 3.4.3 – Acrescentar filtro de CFC (usar CFC da turma 19)
SELECT 
    a.id, 
    a.nome, 
    a.status,
    a.cfc_id as aluno_cfc_id,
    (SELECT cfc_id FROM turmas_teoricas WHERE id = 19) as turma_cfc_id,
    CASE 
        WHEN a.cfc_id = (SELECT cfc_id FROM turmas_teoricas WHERE id = 19) 
        THEN 'CFC OK' 
        ELSE 'CFC DIFERENTE' 
    END as verificado_cfc
FROM alunos a
WHERE a.id = 167
  AND a.status IN ('ativo', 'em_andamento')
  AND a.cfc_id = (SELECT cfc_id FROM turmas_teoricas WHERE id = 19);

-- 3.4.4 – Query completa da API (sem filtros de exames/financeiro ainda)
SELECT 
    a.id,
    a.nome,
    a.cpf,
    a.categoria_cnh,
    a.status as status_aluno,
    c.nome as cfc_nome,
    c.id as cfc_id,
    m_ativa.categoria_cnh as categoria_cnh_matricula,
    m_ativa.tipo_servico as tipo_servico_matricula,
    CASE 
        WHEN tm.id IS NOT NULL THEN 'matriculado'
        ELSE 'disponivel'
    END as status_matricula,
    tm.id as turma_matricula_id,
    tm.status as turma_matricula_status
FROM alunos a
JOIN cfcs c ON a.cfc_id = c.id
LEFT JOIN turma_matriculas tm ON a.id = tm.aluno_id 
    AND tm.turma_id = 19 
    AND tm.status IN ('matriculado', 'cursando')
LEFT JOIN (
    SELECT aluno_id, categoria_cnh, tipo_servico
    FROM matriculas
    WHERE status = 'ativa'
) m_ativa ON a.id = m_ativa.aluno_id
WHERE a.status IN ('ativo', 'em_andamento')
    AND a.cfc_id = (SELECT cfc_id FROM turmas_teoricas WHERE id = 19)
    AND a.id = 167
ORDER BY a.nome;

-- 3.5 – Verificar exames do aluno 167
SELECT 
    tipo,
    status,
    resultado,
    data_resultado,
    CASE 
        WHEN tipo = 'medico' AND status = 'concluido' AND resultado IN ('apto', 'aprovado') THEN 'OK'
        WHEN tipo = 'psicotecnico' AND status = 'concluido' AND resultado IN ('apto', 'aprovado') THEN 'OK'
        ELSE 'PENDENTE'
    END as status_exame
FROM exames
WHERE aluno_id = 167
    AND tipo IN ('medico', 'psicotecnico')
ORDER BY tipo;

-- 3.6 – Verificar financeiro do aluno 167
SELECT 
    id,
    valor_total,
    data_vencimento,
    status,
    CASE 
        WHEN status = 'paga' THEN 'PAGA'
        WHEN status = 'vencida' THEN 'VENCIDA'
        WHEN status IN ('aberta', 'em_aberto') AND data_vencimento < CURDATE() THEN 'VENCIDA'
        WHEN status IN ('aberta', 'em_aberto') THEN 'ABERTA'
        ELSE 'OUTRO'
    END as situacao
FROM financeiro_faturas
WHERE aluno_id = 167
    AND status != 'cancelada'
ORDER BY data_vencimento ASC;

-- Verificar se tem pelo menos uma fatura paga
SELECT 
    COUNT(*) as total_faturas_pagas
FROM financeiro_faturas
WHERE aluno_id = 167
    AND status = 'paga';

-- Verificar se tem faturas vencidas
SELECT 
    COUNT(*) as total_faturas_vencidas
FROM financeiro_faturas
WHERE aluno_id = 167
    AND (status = 'vencida' OR (status IN ('aberta', 'em_aberto') AND data_vencimento < CURDATE()))
    AND status != 'cancelada';

-- 3.7 – Verificar se aluno já está matriculado na turma 19
SELECT 
    tm.*,
    'Já matriculado?' as observacao
FROM turma_matriculas tm
WHERE tm.aluno_id = 167
    AND tm.turma_id = 19;

-- 3.8 – RESUMO: Verificar cada critério isoladamente
SELECT 
    'Status do aluno' as criterio,
    CASE 
        WHEN (SELECT status FROM alunos WHERE id = 167) IN ('ativo', 'em_andamento') 
        THEN 'OK' 
        ELSE CONCAT('FALHOU: ', (SELECT status FROM alunos WHERE id = 167))
    END as resultado
UNION ALL
SELECT 
    'CFC do aluno = CFC da turma' as criterio,
    CASE 
        WHEN (SELECT cfc_id FROM alunos WHERE id = 167) = (SELECT cfc_id FROM turmas_teoricas WHERE id = 19)
        THEN 'OK'
        ELSE CONCAT('FALHOU: aluno_cfc=', (SELECT cfc_id FROM alunos WHERE id = 167), ', turma_cfc=', (SELECT cfc_id FROM turmas_teoricas WHERE id = 19))
    END as resultado
UNION ALL
SELECT 
    'Tem matrícula ativa' as criterio,
    CASE 
        WHEN (SELECT COUNT(*) FROM matriculas WHERE aluno_id = 167 AND status = 'ativa') > 0
        THEN 'OK'
        ELSE 'FALHOU: sem matrícula ativa'
    END as resultado
UNION ALL
SELECT 
    'Exame médico OK' as criterio,
    CASE 
        WHEN EXISTS (
            SELECT 1 FROM exames 
            WHERE aluno_id = 167 
                AND tipo = 'medico' 
                AND status = 'concluido' 
                AND resultado IN ('apto', 'aprovado')
        )
        THEN 'OK'
        ELSE 'FALHOU'
    END as resultado
UNION ALL
SELECT 
    'Exame psicotécnico OK' as criterio,
    CASE 
        WHEN EXISTS (
            SELECT 1 FROM exames 
            WHERE aluno_id = 167 
                AND tipo = 'psicotecnico' 
                AND status = 'concluido' 
                AND resultado IN ('apto', 'aprovado')
        )
        THEN 'OK'
        ELSE 'FALHOU'
    END as resultado
UNION ALL
SELECT 
    'Tem fatura paga' as criterio,
    CASE 
        WHEN (SELECT COUNT(*) FROM financeiro_faturas WHERE aluno_id = 167 AND status = 'paga') > 0
        THEN 'OK'
        ELSE 'FALHOU: sem fatura paga'
    END as resultado
UNION ALL
SELECT 
    'Sem faturas vencidas' as criterio,
    CASE 
        WHEN NOT EXISTS (
            SELECT 1 FROM financeiro_faturas 
            WHERE aluno_id = 167
                AND (status = 'vencida' OR (status IN ('aberta', 'em_aberto') AND data_vencimento < CURDATE()))
                AND status != 'cancelada'
        )
        THEN 'OK'
        ELSE 'FALHOU: tem faturas vencidas'
    END as resultado
UNION ALL
SELECT 
    'NÃO está matriculado na turma 19' as criterio,
    CASE 
        WHEN NOT EXISTS (
            SELECT 1 FROM turma_matriculas 
            WHERE aluno_id = 167 
                AND turma_id = 19 
                AND status IN ('matriculado', 'cursando')
        )
        THEN 'OK'
        ELSE 'FALHOU: já está matriculado'
    END as resultado;

