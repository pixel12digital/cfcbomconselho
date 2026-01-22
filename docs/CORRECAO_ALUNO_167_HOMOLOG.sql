-- =====================================================
-- CORREÇÃO TEMPORÁRIA PARA HOMOLOG - Aluno 167 (Charles)
-- =====================================================
-- 
-- OBJETIVO: Reabrir o aluno concluído para testes em homolog
-- 
-- ⚠️ IMPORTANTE: Execute estas queries APENAS em HOMOLOG!
-- ⚠️ NÃO execute em PRODUÇÃO sem validação de regra de negócio!
-- 
-- Data: 12/12/2025
-- Motivo: Aluno concluído não aparece na lista de candidatos para matrícula
--          (query exige status = 'ativo')
-- 
-- =====================================================

-- 1. Reabrir o aluno de teste (mudar status de 'concluido' para 'ativo')
UPDATE alunos 
SET status = 'ativo' 
WHERE id = 167;

-- Verificar se funcionou
SELECT id, nome, status 
FROM alunos 
WHERE id = 167;

-- =====================================================
-- 2. (OPCIONAL) Limpar matrículas órfãs
-- =====================================================
-- Se quiser deixar tudo bem limpinho, cancelar matrículas órfãs 
-- em turmas que já não existem mais

-- Primeiro, verificar se há matrículas órfãs
SELECT tm.*, 'Turma excluída' as observacao
FROM turma_matriculas tm
LEFT JOIN turmas_teoricas tt ON tm.turma_id = tt.id
WHERE tm.aluno_id = 167
  AND tt.id IS NULL
  AND tm.status IN ('matriculado', 'cursando');

-- Se houver matrículas órfãs encontradas acima, executar:
-- UPDATE turma_matriculas tm
-- LEFT JOIN turmas_teoricas tt ON tm.turma_id = tt.id
-- SET tm.status = 'cancelada', tm.atualizado_em = NOW()
-- WHERE tm.aluno_id = 167
--   AND tt.id IS NULL
--   AND tm.status IN ('matriculado', 'cursando');

-- =====================================================
-- VALIDAÇÃO PÓS-CORREÇÃO
-- =====================================================

-- Verificar status do aluno após correção
SELECT id, nome, status, cfc_id
FROM alunos 
WHERE id = 167;
-- Esperado: status = 'ativo'

-- Verificar se há matrículas ativas em turmas existentes
SELECT tm.*, tt.nome as turma_nome, tt.status as turma_status
FROM turma_matriculas tm
JOIN turmas_teoricas tt ON tm.turma_id = tt.id
WHERE tm.aluno_id = 167
  AND tm.status IN ('matriculado', 'cursando');

