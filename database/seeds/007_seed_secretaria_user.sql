-- Seed 007: Criar usuário SECRETARIA para testes

-- Criar usuário Secretaria (senha: secretaria123)
-- IMPORTANTE: Alterar a senha após o primeiro login!
INSERT INTO `usuarios` (`cfc_id`, `nome`, `email`, `password`, `status`, `must_change_password`) VALUES
(1, 'Secretaria', 'secretaria@cfc.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ativo', 1)
ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`);

-- Associar secretaria ao role SECRETARIA
-- Buscar ID do usuário criado
SET @secretaria_user_id = (SELECT id FROM usuarios WHERE email = 'secretaria@cfc.local' LIMIT 1);

INSERT INTO `usuario_roles` (`usuario_id`, `role`) 
SELECT @secretaria_user_id, 'SECRETARIA'
WHERE @secretaria_user_id IS NOT NULL
ON DUPLICATE KEY UPDATE `usuario_id` = VALUES(`usuario_id`);
