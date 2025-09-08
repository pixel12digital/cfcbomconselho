-- Script para alterar o campo 'ano' de INT para VARCHAR para aceitar formato Ano/Modelo
-- Executar este script para atualizar a estrutura do banco de dados

USE cfc_sistema;

-- Alterar campo ano de INT para VARCHAR para aceitar formato Ano/Modelo
ALTER TABLE veiculos 
MODIFY COLUMN ano VARCHAR(20) NULL COMMENT 'Ano/Modelo do veículo (ex: 2020/2021)';

-- Verificar se a alteração foi aplicada
DESCRIBE veiculos;
