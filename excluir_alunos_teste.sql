-- =====================================================
-- SCRIPT SQL PARA EXCLUSÃO DE ALUNOS DE TESTE
-- =====================================================
-- 
-- Este script remove os alunos de teste (IDs: 113, 127, 128) 
-- e todos os dados relacionados de forma segura.
-- 
-- ATENÇÃO: Este script é irreversível!
-- Execute apenas após confirmar que são realmente dados de teste.
-- =====================================================

-- Verificar se os alunos existem antes da exclusão
SELECT 'VERIFICAÇÃO PRÉVIA - Alunos de teste encontrados:' as status;
SELECT id, nome, cpf, categoria_cnh, status, criado_em 
FROM alunos 
WHERE id IN (113, 127, 128);

-- Verificar aulas vinculadas
SELECT 'VERIFICAÇÃO PRÉVIA - Aulas vinculadas:' as status;
SELECT a.id as aula_id, a.aluno_id, a.data_aula, a.hora_inicio, a.tipo_aula, a.status,
       al.nome as aluno_nome
FROM aulas a
JOIN alunos al ON a.aluno_id = al.id
WHERE a.aluno_id IN (113, 127, 128);

-- Verificar slots de aulas vinculados
SELECT 'VERIFICAÇÃO PRÉVIA - Slots de aulas vinculados:' as status;
SELECT s.id as slot_id, s.aluno_id, s.tipo_aula, s.status, s.ordem,
       al.nome as aluno_nome
FROM aulas_slots s
JOIN alunos al ON s.aluno_id = al.id
WHERE s.aluno_id IN (113, 127, 128);

-- Verificar logs de auditoria
SELECT 'VERIFICAÇÃO PRÉVIA - Logs de auditoria:' as status;
SELECT l.id as log_id, l.usuario_id, l.acao, l.tabela, l.registro_id, l.criado_em
FROM logs l
WHERE l.registro_id IN (113, 127, 128) AND l.tabela = 'alunos';

-- =====================================================
-- EXECUÇÃO DA EXCLUSÃO
-- =====================================================
-- 
-- DESCOMENTE AS LINHAS ABAIXO APÓS CONFIRMAR QUE OS DADOS 
-- SÃO REALMENTE DE TESTE E DEVEM SER EXCLUÍDOS
-- =====================================================

/*
-- Iniciar transação para garantir consistência
START TRANSACTION;

-- 1. Excluir aulas vinculadas aos alunos de teste
DELETE FROM aulas 
WHERE aluno_id IN (113, 127, 128);

-- 2. Excluir slots de aulas vinculados aos alunos de teste
DELETE FROM aulas_slots 
WHERE aluno_id IN (113, 127, 128);

-- 3. Excluir logs de auditoria relacionados aos alunos de teste
DELETE FROM logs 
WHERE registro_id IN (113, 127, 128) AND tabela = 'alunos';

-- 4. Excluir os próprios alunos de teste
DELETE FROM alunos 
WHERE id IN (113, 127, 128);

-- Confirmar transação
COMMIT;

-- Verificar se a exclusão foi bem-sucedida
SELECT 'VERIFICAÇÃO PÓS-EXCLUSÃO - Alunos restantes:' as status;
SELECT COUNT(*) as total_alunos FROM alunos WHERE id IN (113, 127, 128);

SELECT 'VERIFICAÇÃO PÓS-EXCLUSÃO - Aulas restantes:' as status;
SELECT COUNT(*) as total_aulas FROM aulas WHERE aluno_id IN (113, 127, 128);

SELECT 'VERIFICAÇÃO PÓS-EXCLUSÃO - Slots restantes:' as status;
SELECT COUNT(*) as total_slots FROM aulas_slots WHERE aluno_id IN (113, 127, 128);

SELECT 'VERIFICAÇÃO PÓS-EXCLUSÃO - Logs restantes:' as status;
SELECT COUNT(*) as total_logs FROM logs WHERE registro_id IN (113, 127, 128) AND tabela = 'alunos';
*/

-- =====================================================
-- SCRIPT DE LIMPEZA ADICIONAL (OPCIONAL)
-- =====================================================
-- 
-- Se houver outros dados relacionados que não foram cobertos
-- pelo script principal, adicione aqui
-- =====================================================

/*
-- Exemplo: Se houver tabela de pagamentos vinculada aos alunos
-- DELETE FROM pagamentos WHERE aluno_id IN (113, 127, 128);

-- Exemplo: Se houver tabela de documentos vinculada aos alunos  
-- DELETE FROM documentos WHERE aluno_id IN (113, 127, 128);

-- Exemplo: Se houver tabela de avaliações vinculada aos alunos
-- DELETE FROM avaliacoes WHERE aluno_id IN (113, 127, 128);
*/

-- =====================================================
-- RELATÓRIO FINAL
-- =====================================================
SELECT 'RELATÓRIO FINAL - Estatísticas do sistema:' as status;
SELECT 
    (SELECT COUNT(*) FROM alunos) as total_alunos,
    (SELECT COUNT(*) FROM aulas) as total_aulas,
    (SELECT COUNT(*) FROM aulas_slots) as total_slots,
    (SELECT COUNT(*) FROM logs WHERE tabela = 'alunos') as total_logs_alunos;
