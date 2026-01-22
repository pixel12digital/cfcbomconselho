-- =====================================================
-- MIGRAÇÃO: Corrigir Foreign Key de Pagamentos
-- Versão: 1.0
-- Data: 2025-01-XX
-- Autor: Sistema CFC Bom Conselho
-- 
-- PROBLEMA: A foreign key pagamentos.fatura_id estava apontando
-- para a tabela 'faturas' (antiga), mas a API usa 'financeiro_faturas'.
-- Isso causa erro ao tentar inserir pagamentos para faturas novas.
-- 
-- SOLUÇÃO: Remover foreign key antiga e criar nova apontando
-- para financeiro_faturas.
-- 
-- NOTA: Execute este script via executar_fix_pagamentos_fk.php
-- para tratamento seguro de erros caso a constraint não exista.
-- =====================================================

-- NOTA: O script PHP (executar_fix_pagamentos_fk.php) irá:
-- 1. Buscar o nome da constraint atual automaticamente
-- 2. Remover a constraint antiga (se existir)
-- 3. Adicionar a nova constraint apontando para financeiro_faturas

-- Adicionar nova foreign key apontando para financeiro_faturas
-- (O script PHP remove a antiga antes de executar este comando)
ALTER TABLE pagamentos
ADD CONSTRAINT fk_pagamentos_financeiro_faturas
FOREIGN KEY (fatura_id) REFERENCES financeiro_faturas(id) ON DELETE CASCADE;

