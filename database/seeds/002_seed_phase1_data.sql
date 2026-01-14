-- Seed 002: Dados Fase 1 (Serviços e Etapas Padrão)

-- Inserir Serviços Padrão
INSERT INTO `services` (`cfc_id`, `name`, `category`, `base_price`, `payment_methods_json`, `is_active`) VALUES
(1, '1ª Habilitação - Categoria B', '1ª habilitação', 2500.00, '["pix", "boleto", "cartao"]', 1),
(1, '1ª Habilitação - Categoria A', '1ª habilitação', 1800.00, '["pix", "boleto", "cartao"]', 1),
(1, '1ª Habilitação - Categoria AB', '1ª habilitação', 3000.00, '["pix", "boleto", "cartao"]', 1),
(1, 'Renovação CNH', 'Renovação', 150.00, '["pix", "boleto", "cartao"]', 1),
(1, 'Adição de Categoria', 'Adição', 800.00, '["pix", "boleto", "cartao"]', 1),
(1, 'Reciclagem', 'Reciclagem', 200.00, '["pix", "boleto", "cartao"]', 1),
(1, 'Mudança de Categoria', 'Mudança', 1200.00, '["pix", "boleto", "cartao"]', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Inserir Etapas Padrão
INSERT INTO `steps` (`code`, `name`, `description`, `order`, `is_active`) VALUES
('MATRICULA', 'Matrícula', 'Matrícula realizada no CFC', 1, 1),
('DOCUMENTOS_OK', 'Documentos OK', 'Documentação completa e validada', 2, 1),
('EXAME_MEDICO', 'Exame Médico', 'Exame médico realizado e aprovado', 3, 1),
('PSICOTECNICO', 'Psicotécnico', 'Exame psicotécnico realizado e aprovado', 4, 1),
('PROVA_TEORICA', 'Prova Teórica', 'Prova teórica realizada e aprovada', 5, 1),
('PRATICA_MINIMA', 'Prática Mínima', 'Aulas práticas mínimas concluídas', 6, 1),
('PROVA_PRATICA', 'Prova Prática', 'Prova prática realizada e aprovada', 7, 1),
('CONCLUSAO', 'Conclusão', 'Processo concluído e CNH emitida', 8, 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `order` = VALUES(`order`);

-- Adicionar permissões para Serviços
INSERT INTO `permissoes` (`modulo`, `acao`, `descricao`) VALUES
('servicos', 'view', 'Visualizar serviços'),
('servicos', 'create', 'Criar serviço'),
('servicos', 'update', 'Editar serviço'),
('servicos', 'toggle', 'Ativar/Desativar serviço')
ON DUPLICATE KEY UPDATE `descricao` = VALUES(`descricao`);

-- Adicionar permissões para Alunos
INSERT INTO `permissoes` (`modulo`, `acao`, `descricao`) VALUES
('alunos', 'view', 'Visualizar alunos'),
('alunos', 'create', 'Criar aluno'),
('alunos', 'update', 'Editar aluno')
ON DUPLICATE KEY UPDATE `descricao` = VALUES(`descricao`);

-- Adicionar permissões para Matrículas
INSERT INTO `permissoes` (`modulo`, `acao`, `descricao`) VALUES
('enrollments', 'view', 'Visualizar matrículas'),
('enrollments', 'create', 'Criar matrícula'),
('enrollments', 'update', 'Editar matrícula')
ON DUPLICATE KEY UPDATE `descricao` = VALUES(`descricao`);

-- Adicionar permissões para Etapas
INSERT INTO `permissoes` (`modulo`, `acao`, `descricao`) VALUES
('steps', 'view', 'Visualizar etapas'),
('steps', 'update', 'Atualizar etapa')
ON DUPLICATE KEY UPDATE `descricao` = VALUES(`descricao`);

-- Associar novas permissões ao role ADMIN (todas)
INSERT INTO `role_permissoes` (`role`, `permissao_id`)
SELECT 'ADMIN', `id` FROM `permissoes`
WHERE `modulo` IN ('servicos', 'alunos', 'enrollments', 'steps')
  AND `acao` IN ('view', 'create', 'update', 'toggle')
ON DUPLICATE KEY UPDATE `role` = VALUES(`role`);

-- Associar permissões ao role SECRETARIA
INSERT INTO `role_permissoes` (`role`, `permissao_id`)
SELECT 'SECRETARIA', `id` FROM `permissoes`
WHERE (`modulo` = 'servicos' AND `acao` IN ('view', 'create', 'update', 'toggle'))
   OR (`modulo` = 'alunos' AND `acao` IN ('view', 'create', 'update'))
   OR (`modulo` = 'enrollments' AND `acao` IN ('view', 'create', 'update'))
   OR (`modulo` = 'steps' AND `acao` IN ('view', 'update'))
ON DUPLICATE KEY UPDATE `role` = VALUES(`role`);
