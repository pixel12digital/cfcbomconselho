-- Seed 001: Dados Iniciais

-- Inserir CFC padrão
INSERT INTO `cfcs` (`id`, `nome`, `status`) VALUES
(1, 'CFC Principal', 'ativo')
ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`);

-- Inserir Roles
INSERT INTO `roles` (`role`, `nome`, `descricao`) VALUES
('ADMIN', 'Administrador', 'Acesso total ao sistema'),
('SECRETARIA', 'Secretaria', 'Gestão de alunos, matrículas, agenda e financeiro'),
('INSTRUTOR', 'Instrutor', 'Agenda, aulas práticas e comunicação com alunos'),
('ALUNO', 'Aluno', 'Acesso ao portal do aluno')
ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`), `descricao` = VALUES(`descricao`);

-- Inserir Admin padrão (senha: admin123)
-- IMPORTANTE: Alterar a senha após o primeiro login!
INSERT INTO `usuarios` (`id`, `cfc_id`, `nome`, `email`, `password`, `status`) VALUES
(1, 1, 'Administrador', 'admin@cfc.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ativo')
ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`);

-- Associar admin ao role ADMIN
INSERT INTO `usuario_roles` (`usuario_id`, `role`) VALUES
(1, 'ADMIN')
ON DUPLICATE KEY UPDATE `usuario_id` = VALUES(`usuario_id`);

-- Inserir Permissões Básicas
INSERT INTO `permissoes` (`modulo`, `acao`, `descricao`) VALUES
-- Alunos
('alunos', 'listar', 'Listar alunos'),
('alunos', 'criar', 'Criar novo aluno'),
('alunos', 'editar', 'Editar aluno'),
('alunos', 'excluir', 'Excluir aluno'),
('alunos', 'visualizar', 'Visualizar detalhes do aluno'),
-- Matrículas
('matriculas', 'listar', 'Listar matrículas'),
('matriculas', 'criar', 'Criar nova matrícula'),
('matriculas', 'editar', 'Editar matrícula'),
('matriculas', 'excluir', 'Excluir matrícula'),
-- Agenda
('agenda', 'listar', 'Listar agenda'),
('agenda', 'criar', 'Criar agendamento'),
('agenda', 'editar', 'Editar agendamento'),
('agenda', 'excluir', 'Excluir agendamento'),
-- Aulas
('aulas', 'listar', 'Listar aulas'),
('aulas', 'iniciar', 'Iniciar aula'),
('aulas', 'finalizar', 'Finalizar aula'),
('aulas', 'cancelar', 'Cancelar aula'),
-- Financeiro
('financeiro', 'listar', 'Listar financeiro'),
('financeiro', 'criar', 'Criar cobrança'),
('financeiro', 'editar', 'Editar cobrança'),
('financeiro', 'excluir', 'Excluir cobrança'),
-- Instrutores
('instrutores', 'listar', 'Listar instrutores'),
('instrutores', 'criar', 'Criar instrutor'),
('instrutores', 'editar', 'Editar instrutor'),
('instrutores', 'excluir', 'Excluir instrutor'),
-- Veículos
('veiculos', 'listar', 'Listar veículos'),
('veiculos', 'criar', 'Criar veículo'),
('veiculos', 'editar', 'Editar veículo'),
('veiculos', 'excluir', 'Excluir veículo'),
-- Serviços
('servicos', 'listar', 'Listar serviços'),
('servicos', 'criar', 'Criar serviço'),
('servicos', 'editar', 'Editar serviço'),
('servicos', 'excluir', 'Excluir serviço')
ON DUPLICATE KEY UPDATE `descricao` = VALUES(`descricao`);

-- Associar permissões ao role ADMIN (todas as permissões)
INSERT INTO `role_permissoes` (`role`, `permissao_id`)
SELECT 'ADMIN', `id` FROM `permissoes`
ON DUPLICATE KEY UPDATE `role` = VALUES(`role`);

-- Associar permissões ao role SECRETARIA
INSERT INTO `role_permissoes` (`role`, `permissao_id`)
SELECT 'SECRETARIA', `id` FROM `permissoes`
WHERE `modulo` IN ('alunos', 'matriculas', 'agenda', 'financeiro', 'servicos')
ON DUPLICATE KEY UPDATE `role` = VALUES(`role`);

-- Associar permissões ao role INSTRUTOR
INSERT INTO `role_permissoes` (`role`, `permissao_id`)
SELECT 'INSTRUTOR', `id` FROM `permissoes`
WHERE `modulo` IN ('agenda', 'aulas') AND `acao` IN ('listar', 'iniciar', 'finalizar')
ON DUPLICATE KEY UPDATE `role` = VALUES(`role`);
