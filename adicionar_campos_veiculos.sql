-- Script para adicionar campos faltantes na tabela veiculos
-- Execute este script no seu banco de dados para sincronizar com o formulário

USE u342734079_cfcbomconselho;

-- Adicionar campos faltantes na tabela veiculos
ALTER TABLE veiculos 
ADD COLUMN cor VARCHAR(50) NULL COMMENT 'Cor do veículo' AFTER categoria_cnh,
ADD COLUMN chassi VARCHAR(50) NULL COMMENT 'Número do chassi' AFTER cor,
ADD COLUMN renavam VARCHAR(20) NULL COMMENT 'Número do RENAVAM' AFTER chassi,
ADD COLUMN combustivel ENUM('gasolina', 'etanol', 'flex', 'diesel', 'eletrico', 'hibrido') NULL COMMENT 'Tipo de combustível' AFTER renavam,
ADD COLUMN quilometragem INT NULL DEFAULT 0 COMMENT 'Quilometragem atual em km' AFTER combustivel,
ADD COLUMN km_manutencao INT NULL COMMENT 'Quilometragem para próxima manutenção' AFTER quilometragem,
ADD COLUMN data_aquisicao DATE NULL COMMENT 'Data de aquisição do veículo' AFTER km_manutencao,
ADD COLUMN valor_aquisicao DECIMAL(10,2) NULL COMMENT 'Valor de aquisição' AFTER data_aquisicao,
ADD COLUMN proxima_manutencao DATE NULL COMMENT 'Data da próxima manutenção' AFTER valor_aquisicao,
ADD COLUMN disponivel BOOLEAN DEFAULT TRUE COMMENT 'Disponibilidade do veículo' AFTER proxima_manutencao,
ADD COLUMN observacoes TEXT NULL COMMENT 'Observações sobre o veículo' AFTER disponivel,
ADD COLUMN status ENUM('ativo', 'inativo', 'manutencao') DEFAULT 'ativo' COMMENT 'Status do veículo' AFTER observacoes,
ADD COLUMN atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data de atualização' AFTER status;

-- Adicionar índices para melhor performance
CREATE INDEX idx_veiculos_placa ON veiculos(placa);
CREATE INDEX idx_veiculos_status ON veiculos(status);
CREATE INDEX idx_veiculos_disponivel ON veiculos(disponivel);
CREATE INDEX idx_veiculos_cfc ON veiculos(cfc_id);
CREATE INDEX idx_veiculos_categoria ON veiculos(categoria_cnh);

-- Verificar se a alteração foi bem-sucedida
SELECT 'Tabela veiculos atualizada com sucesso!' as resultado;
