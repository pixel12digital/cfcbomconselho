-- Seed 004: Cidades (Amostra - Fase 1.2)
-- IMPORTANTE: Este é um seed de exemplo com algumas cidades de SC.
-- Para produção, importe o arquivo completo do IBGE com todas as cidades brasileiras.
-- O arquivo deve seguir o formato: INSERT IGNORE INTO cities (state_id, name, ibge_code) VALUES ...

-- Primeiro, obter os IDs dos estados
SET @sc_id = (SELECT id FROM states WHERE uf = 'SC');
SET @sp_id = (SELECT id FROM states WHERE uf = 'SP');
SET @rs_id = (SELECT id FROM states WHERE uf = 'RS');
SET @pr_id = (SELECT id FROM states WHERE uf = 'PR');

-- Exemplo: Cidades de Santa Catarina (algumas principais)
INSERT IGNORE INTO `cities` (`state_id`, `name`, `ibge_code`) VALUES
(@sc_id, 'Florianópolis', 4205407),
(@sc_id, 'Joinville', 4209102),
(@sc_id, 'Blumenau', 4202404),
(@sc_id, 'São José', 4216602),
(@sc_id, 'Criciúma', 4204608),
(@sc_id, 'Chapecó', 4204202),
(@sc_id, 'Itajaí', 4208203),
(@sc_id, 'Lages', 4209300),
(@sc_id, 'Jaraguá do Sul', 4208906),
(@sc_id, 'Palhoça', 4211900),
(@sc_id, 'Brusque', 4202875),
(@sc_id, 'Balneário Camboriú', 4202008),
(@sc_id, 'Tubarão', 4218707),
(@sc_id, 'Rio do Sul', 4214805),
(@sc_id, 'Navegantes', 4211306);

-- Exemplo: Cidades de São Paulo (algumas principais)
INSERT IGNORE INTO `cities` (`state_id`, `name`, `ibge_code`) VALUES
(@sp_id, 'São Paulo', 3550308),
(@sp_id, 'Campinas', 3509502),
(@sp_id, 'Guarulhos', 3518800),
(@sp_id, 'São Bernardo do Campo', 3548708),
(@sp_id, 'Santo André', 3547809);

-- Exemplo: Cidades do Rio Grande do Sul (algumas principais)
INSERT IGNORE INTO `cities` (`state_id`, `name`, `ibge_code`) VALUES
(@rs_id, 'Porto Alegre', 4314902),
(@rs_id, 'Caxias do Sul', 4305108),
(@rs_id, 'Pelotas', 4314407),
(@rs_id, 'Canoas', 4304606),
(@rs_id, 'Santa Maria', 4316907);

-- Exemplo: Cidades do Paraná (algumas principais)
INSERT IGNORE INTO `cities` (`state_id`, `name`, `ibge_code`) VALUES
(@pr_id, 'Curitiba', 4106902),
(@pr_id, 'Londrina', 4113700),
(@pr_id, 'Maringá', 4115200),
(@pr_id, 'Ponta Grossa', 4119905),
(@pr_id, 'Cascavel', 4104808);

-- NOTA: Para importar todas as cidades do IBGE:
-- 1. Baixe o arquivo de municípios do IBGE
-- 2. Gere um SQL no formato acima com todos os municípios
-- 3. Execute o arquivo SQL completo
-- 4. O INSERT IGNORE evita duplicatas se executar novamente
