-- Script de Setup Fase 1.2: Padronização de UF/Cidades
-- Execute este script para criar as tabelas e popular os dados básicos

-- 1. Criar tabelas states e cities
SOURCE database/migrations/004_create_states_cities_tables.sql;

-- 2. Adicionar city_id na tabela students
SOURCE database/migrations/005_add_city_id_to_students.sql;

-- 3. Popular estados (27 UFs)
SOURCE database/seeds/003_seed_states.sql;

-- 4. Popular cidades (amostra - expandir com dados completos do IBGE)
SOURCE database/seeds/004_seed_cities_sample.sql;

-- NOTA: Para importar todas as cidades do IBGE:
-- 1. Baixe o arquivo de municípios do IBGE
-- 2. Gere um SQL no formato do seed 004 com todos os municípios
-- 3. Execute o arquivo SQL completo
-- 4. O INSERT IGNORE evita duplicatas se executar novamente
