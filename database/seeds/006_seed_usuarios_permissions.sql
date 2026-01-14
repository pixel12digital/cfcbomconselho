-- Seed 006: Permissões para módulo de Usuários

-- Inserir Permissões de Usuários
INSERT INTO `permissoes` (`modulo`, `acao`, `descricao`) VALUES
('usuarios', 'listar', 'Listar usuários'),
('usuarios', 'criar', 'Criar novo usuário'),
('usuarios', 'editar', 'Editar usuário'),
('usuarios', 'excluir', 'Excluir usuário'),
('usuarios', 'visualizar', 'Visualizar detalhes do usuário')
ON DUPLICATE KEY UPDATE `descricao` = VALUES(`descricao`);

-- Associar permissões ao role ADMIN (todas as permissões)
INSERT INTO `role_permissoes` (`role`, `permissao_id`)
SELECT 'ADMIN', `id` FROM `permissoes`
WHERE `modulo` = 'usuarios'
ON DUPLICATE KEY UPDATE `role` = VALUES(`role`);
