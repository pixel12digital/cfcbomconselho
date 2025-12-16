-- Migration: Adicionar campos de KM e timestamps reais de execução na tabela aulas
-- Data: 2025-12-13
-- Descrição: Adiciona km_inicial, km_final, inicio_at e fim_at para controle de aulas práticas

-- Verificar e adicionar km_inicial
ALTER TABLE aulas 
ADD COLUMN IF NOT EXISTS km_inicial INT NULL COMMENT 'Odômetro inicial da aula prática' 
AFTER observacoes;

-- Verificar e adicionar km_final
ALTER TABLE aulas 
ADD COLUMN IF NOT EXISTS km_final INT NULL COMMENT 'Odômetro final da aula prática' 
AFTER km_inicial;

-- Verificar e adicionar inicio_at
ALTER TABLE aulas 
ADD COLUMN IF NOT EXISTS inicio_at TIMESTAMP NULL COMMENT 'Timestamp real do início da aula' 
AFTER km_final;

-- Verificar e adicionar fim_at
ALTER TABLE aulas 
ADD COLUMN IF NOT EXISTS fim_at TIMESTAMP NULL COMMENT 'Timestamp real do fim da aula' 
AFTER inicio_at;
