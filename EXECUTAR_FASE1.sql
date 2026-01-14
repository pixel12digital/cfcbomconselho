-- ============================================
-- FASE 1 - EXECUTAR ESTE ARQUIVO NO BANCO cfc_db
-- ============================================
-- Instruções:
-- 1. Abra o phpMyAdmin (http://localhost/phpmyadmin)
-- 2. Selecione o banco de dados "cfc_db"
-- 3. Vá na aba "SQL"
-- 4. Cole todo o conteúdo deste arquivo
-- 5. Clique em "Executar"
-- ============================================

USE cfc_db;

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Tabela de Serviços
CREATE TABLE IF NOT EXISTS `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cfc_id` int(11) NOT NULL DEFAULT 1,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `base_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_methods_json` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cfc_id` (`cfc_id`),
  KEY `is_active` (`is_active`),
  KEY `deleted_at` (`deleted_at`),
  CONSTRAINT `services_ibfk_1` FOREIGN KEY (`cfc_id`) REFERENCES `cfcs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Alunos
CREATE TABLE IF NOT EXISTS `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cfc_id` int(11) NOT NULL DEFAULT 1,
  `name` varchar(255) NOT NULL,
  `cpf` varchar(14) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `status` enum('lead','matriculado','em_andamento','concluido','cancelado') NOT NULL DEFAULT 'lead',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cfc_cpf` (`cfc_id`, `cpf`),
  KEY `cfc_id` (`cfc_id`),
  KEY `status` (`status`),
  KEY `name` (`name`),
  KEY `cpf` (`cpf`),
  KEY `phone` (`phone`),
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`cfc_id`) REFERENCES `cfcs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Matrículas
CREATE TABLE IF NOT EXISTS `enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cfc_id` int(11) NOT NULL DEFAULT 1,
  `student_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `discount_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `extra_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `final_price` decimal(10,2) NOT NULL,
  `payment_method` enum('pix','boleto','cartao') NOT NULL,
  `financial_status` enum('em_dia','pendente','bloqueado') NOT NULL DEFAULT 'em_dia',
  `status` enum('ativa','concluida','cancelada') NOT NULL DEFAULT 'ativa',
  `created_by_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cfc_id` (`cfc_id`),
  KEY `student_id` (`student_id`),
  KEY `service_id` (`service_id`),
  KEY `financial_status` (`financial_status`),
  KEY `status` (`status`),
  CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`cfc_id`) REFERENCES `cfcs` (`id`),
  CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  CONSTRAINT `enrollments_ibfk_3` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
  CONSTRAINT `enrollments_ibfk_4` FOREIGN KEY (`created_by_user_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Etapas (Catálogo)
CREATE TABLE IF NOT EXISTS `steps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `order` (`order`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Etapas do Aluno (Instância por matrícula)
CREATE TABLE IF NOT EXISTS `student_steps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enrollment_id` int(11) NOT NULL,
  `step_id` int(11) NOT NULL,
  `status` enum('pendente','concluida') NOT NULL DEFAULT 'pendente',
  `source` enum('cfc','aluno') DEFAULT NULL,
  `validated_by_user_id` int(11) DEFAULT NULL,
  `validated_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `enrollment_step` (`enrollment_id`, `step_id`),
  KEY `enrollment_id` (`enrollment_id`),
  KEY `step_id` (`step_id`),
  KEY `status` (`status`),
  CONSTRAINT `student_steps_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_steps_ibfk_2` FOREIGN KEY (`step_id`) REFERENCES `steps` (`id`),
  CONSTRAINT `student_steps_ibfk_3` FOREIGN KEY (`validated_by_user_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

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

-- ============================================
-- FIM - Verifique se todas as tabelas foram criadas
-- ============================================
