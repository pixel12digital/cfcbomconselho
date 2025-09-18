-- =====================================================
-- SCRIPT SQL PARA EXCLUSÃO DE AGENDAMENTOS DE TESTE
-- =====================================================
-- 
-- Este script remove APENAS os agendamentos (aulas) dos alunos:
-- - ID 111 (Roberio)
-- - ID 112 (Jefferson)
-- 
-- MANTÉM os cadastros dos alunos e usuários intactos.
-- Remove apenas dados relacionados a agendamentos.
-- =====================================================

-- Verificar se os alunos existem
SELECT 'VERIFICAÇÃO PRÉVIA - Alunos encontrados:' as status;
SELECT id, nome, cpf, categoria_cnh, status 
FROM alunos 
WHERE id IN (111, 112);

-- Verificar aulas vinculadas aos alunos
SELECT 'VERIFICAÇÃO PRÉVIA - Aulas agendadas:' as status;
SELECT a.id as aula_id, a.aluno_id, a.data_aula, a.hora_inicio, a.hora_fim, 
       a.tipo_aula, a.status, a.observacoes,
       al.nome as aluno_nome,
       i.credencial as instrutor_credencial,
       v.placa as veiculo_placa
FROM aulas a
JOIN alunos al ON a.aluno_id = al.id
LEFT JOIN instrutores i ON a.instrutor_id = i.id
LEFT JOIN veiculos v ON a.veiculo_id = v.id
WHERE a.aluno_id IN (111, 112)
ORDER BY a.aluno_id, a.data_aula, a.hora_inicio;

-- Verificar slots de aulas vinculados
SELECT 'VERIFICAÇÃO PRÉVIA - Slots de aulas:' as status;
SELECT s.id as slot_id, s.aluno_id, s.tipo_aula, s.status, s.ordem, s.aula_id,
       al.nome as aluno_nome
FROM aulas_slots s
JOIN alunos al ON s.aluno_id = al.id
WHERE s.aluno_id IN (111, 112)
ORDER BY s.aluno_id, s.ordem;

-- Verificar logs de auditoria relacionados às aulas
SELECT 'VERIFICAÇÃO PRÉVIA - Logs de auditoria das aulas:' as status;
SELECT l.id as log_id, l.usuario_id, l.acao, l.tabela, l.registro_id, l.criado_em,
       JSON_EXTRACT(l.dados_novos, '$.aluno_id') as aluno_id_log
FROM logs l
WHERE l.tabela = 'aulas' 
  AND JSON_EXTRACT(l.dados_novos, '$.aluno_id') IN (111, 112);

-- =====================================================
-- EXECUÇÃO DA EXCLUSÃO DE AGENDAMENTOS
-- =====================================================
-- 
-- DESCOMENTE AS LINHAS ABAIXO APÓS CONFIRMAR QUE OS DADOS 
-- SÃO REALMENTE DE TESTE E DEVEM SER EXCLUÍDOS
-- =====================================================

/*
-- Iniciar transação para garantir consistência
START TRANSACTION;

-- 1. Obter IDs das aulas que serão excluídas (para logs)
SET @aulas_ids_111 = (
    SELECT GROUP_CONCAT(id) FROM aulas WHERE aluno_id = 111
);
SET @aulas_ids_112 = (
    SELECT GROUP_CONCAT(id) FROM aulas WHERE aluno_id = 112
);

-- 2. Excluir logs de auditoria das aulas (se existirem)
DELETE FROM logs 
WHERE tabela = 'aulas' 
  AND registro_id IN (
      SELECT id FROM aulas WHERE aluno_id IN (111, 112)
  );

-- 3. Excluir aulas vinculadas aos alunos de teste
DELETE FROM aulas 
WHERE aluno_id IN (111, 112);

-- 4. Excluir slots de aulas vinculados aos alunos de teste
DELETE FROM aulas_slots 
WHERE aluno_id IN (111, 112);

-- 5. Excluir logs de auditoria relacionados aos alunos (apenas aulas)
DELETE FROM logs 
WHERE tabela = 'aulas' 
  AND JSON_EXTRACT(dados_novos, '$.aluno_id') IN (111, 112);

-- Confirmar transação
COMMIT;

-- Verificar se a exclusão foi bem-sucedida
SELECT 'VERIFICAÇÃO PÓS-EXCLUSÃO - Aulas restantes:' as status;
SELECT COUNT(*) as total_aulas FROM aulas WHERE aluno_id IN (111, 112);

SELECT 'VERIFICAÇÃO PÓS-EXCLUSÃO - Slots restantes:' as status;
SELECT COUNT(*) as total_slots FROM aulas_slots WHERE aluno_id IN (111, 112);

SELECT 'VERIFICAÇÃO PÓS-EXCLUSÃO - Logs restantes:' as status;
SELECT COUNT(*) as total_logs FROM logs 
WHERE tabela = 'aulas' 
  AND JSON_EXTRACT(dados_novos, '$.aluno_id') IN (111, 112);

-- Verificar se os alunos ainda existem (devem existir)
SELECT 'VERIFICAÇÃO PÓS-EXCLUSÃO - Alunos mantidos:' as status;
SELECT id, nome, cpf FROM alunos WHERE id IN (111, 112);
*/

-- =====================================================
-- RELATÓRIO FINAL
-- =====================================================
SELECT 'RELATÓRIO FINAL - Estatísticas do sistema:' as status;
SELECT 
    (SELECT COUNT(*) FROM alunos WHERE id IN (111, 112)) as alunos_mantidos,
    (SELECT COUNT(*) FROM aulas WHERE aluno_id IN (111, 112)) as aulas_restantes,
    (SELECT COUNT(*) FROM aulas_slots WHERE aluno_id IN (111, 112)) as slots_restantes,
    (SELECT COUNT(*) FROM logs WHERE tabela = 'aulas' AND JSON_EXTRACT(dados_novos, '$.aluno_id') IN (111, 112)) as logs_restantes;
