-- Correção LOCAL/REMOTO compartilhado: vincular instrutor Carlos da Silva ao usuário 44
-- Conferência pré-correção
SELECT id, nome, usuario_id, cfc_id, ativo, credencial FROM instrutores WHERE id = 47;
SELECT id, nome, email, tipo FROM usuarios WHERE id IN (44,45);

-- Aplicar correção
UPDATE instrutores
SET usuario_id = 44
WHERE id = 47;

-- Conferência pós-correção
SELECT id, nome, usuario_id, cfc_id, ativo, credencial FROM instrutores WHERE id = 47;
