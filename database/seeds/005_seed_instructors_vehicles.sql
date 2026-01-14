-- Seed 005: Dados iniciais de Instrutores e Veículos

SET FOREIGN_KEY_CHECKS = 0;

-- Instrutores
INSERT INTO `instructors` (`cfc_id`, `name`, `cpf`, `phone`, `email`, `license_number`, `license_category`, `is_active`) VALUES
(1, 'João Silva', '123.456.789-00', '(11) 98765-4321', 'joao.silva@cfc.local', '12345678901', 'AB', 1),
(1, 'Maria Santos', '987.654.321-00', '(11) 91234-5678', 'maria.santos@cfc.local', '98765432109', 'AB', 1),
(1, 'Pedro Oliveira', '111.222.333-44', '(11) 95555-6666', 'pedro.oliveira@cfc.local', '11122233344', 'AB', 1);

-- Veículos
INSERT INTO `vehicles` (`cfc_id`, `plate`, `brand`, `model`, `year`, `color`, `category`, `is_active`) VALUES
(1, 'ABC-1234', 'Fiat', 'Uno', 2020, 'Branco', 'B', 1),
(1, 'DEF-5678', 'Volkswagen', 'Gol', 2021, 'Prata', 'B', 1),
(1, 'GHI-9012', 'Chevrolet', 'Onix', 2022, 'Preto', 'B', 1),
(1, 'JKL-3456', 'Fiat', 'Palio', 2019, 'Vermelho', 'B', 1);

SET FOREIGN_KEY_CHECKS = 1;
