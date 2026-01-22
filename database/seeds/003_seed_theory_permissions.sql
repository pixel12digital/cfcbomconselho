-- Seed 003: Permissões para módulo de Curso Teórico

-- Permissões para Disciplinas
INSERT INTO `permissoes` (`modulo`, `acao`, `descricao`) VALUES
('disciplinas', 'view', 'Visualizar disciplinas teóricas'),
('disciplinas', 'create', 'Criar disciplina teórica'),
('disciplinas', 'update', 'Editar disciplina teórica'),
('disciplinas', 'delete', 'Excluir disciplina teórica')
ON DUPLICATE KEY UPDATE `descricao` = VALUES(`descricao`);

-- Permissões para Cursos Teóricos
INSERT INTO `permissoes` (`modulo`, `acao`, `descricao`) VALUES
('cursos_teoricos', 'view', 'Visualizar cursos teóricos'),
('cursos_teoricos', 'create', 'Criar curso teórico'),
('cursos_teoricos', 'update', 'Editar curso teórico'),
('cursos_teoricos', 'delete', 'Excluir curso teórico')
ON DUPLICATE KEY UPDATE `descricao` = VALUES(`descricao`);

-- Permissões para Turmas Teóricas
INSERT INTO `permissoes` (`modulo`, `acao`, `descricao`) VALUES
('turmas_teoricas', 'view', 'Visualizar turmas teóricas'),
('turmas_teoricas', 'create', 'Criar turma teórica'),
('turmas_teoricas', 'update', 'Editar turma teórica'),
('turmas_teoricas', 'delete', 'Excluir turma teórica')
ON DUPLICATE KEY UPDATE `descricao` = VALUES(`descricao`);

-- Permissões para Presença Teórica
INSERT INTO `permissoes` (`modulo`, `acao`, `descricao`) VALUES
('presenca_teorica', 'view', 'Visualizar presença teórica'),
('presenca_teorica', 'create', 'Marcar presença teórica'),
('presenca_teorica', 'update', 'Editar presença teórica')
ON DUPLICATE KEY UPDATE `descricao` = VALUES(`descricao`);

-- Associar todas as permissões ao role ADMIN
INSERT INTO `role_permissoes` (`role`, `permissao_id`)
SELECT 'ADMIN', `id` FROM `permissoes`
WHERE `modulo` IN ('disciplinas', 'cursos_teoricos', 'turmas_teoricas', 'presenca_teorica')
ON DUPLICATE KEY UPDATE `role` = VALUES(`role`);

-- Associar permissões ao role SECRETARIA
INSERT INTO `role_permissoes` (`role`, `permissao_id`)
SELECT 'SECRETARIA', `id` FROM `permissoes`
WHERE (`modulo` = 'disciplinas' AND `acao` IN ('view', 'create', 'update'))
   OR (`modulo` = 'cursos_teoricos' AND `acao` IN ('view', 'create', 'update'))
   OR (`modulo` = 'turmas_teoricas' AND `acao` IN ('view', 'create', 'update'))
   OR (`modulo` = 'presenca_teorica' AND `acao` IN ('view', 'create', 'update'))
ON DUPLICATE KEY UPDATE `role` = VALUES(`role`);

-- Associar permissões ao role INSTRUTOR (apenas visualizar e marcar presença)
INSERT INTO `role_permissoes` (`role`, `permissao_id`)
SELECT 'INSTRUTOR', `id` FROM `permissoes`
WHERE (`modulo` = 'turmas_teoricas' AND `acao` = 'view')
   OR (`modulo` = 'presenca_teorica' AND `acao` IN ('view', 'create', 'update'))
ON DUPLICATE KEY UPDATE `role` = VALUES(`role`);
