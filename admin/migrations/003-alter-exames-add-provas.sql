-- =====================================================
-- Migration: Adicionar suporte a provas teóricas e práticas na tabela exames
-- =====================================================
-- 
-- NOTA: Aplicar em ambiente de teste antes de produção.
-- 
-- Esta migration estende a tabela exames para suportar:
-- - Provas teóricas (tipo='teorico')
-- - Provas práticas (tipo='pratico')
-- 
-- Além dos exames já existentes:
-- - Exame médico (tipo='medico')
-- - Exame psicotécnico (tipo='psicotecnico')
-- 
-- Os novos tipos de resultado ('aprovado', 'reprovado') são usados para provas,
-- enquanto os existentes ('apto', 'inapto', etc.) continuam para exames médico/psicotécnico.
-- 
-- Data: 2025-01-27
-- =====================================================

-- Adicionar tipos de prova na tabela exames
ALTER TABLE exames
  MODIFY COLUMN tipo ENUM('medico', 'psicotecnico', 'teorico', 'pratico') NOT NULL;

-- Estender resultados para suportar provas
ALTER TABLE exames
  MODIFY COLUMN resultado ENUM(
    'apto',
    'inapto',
    'inapto_temporario',
    'pendente',
    'aprovado',
    'reprovado'
  ) NOT NULL DEFAULT 'pendente';

-- =====================================================
-- Fim da Migration
-- =====================================================

