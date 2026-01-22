-- Migration 027: Adicionar step "Curso Teórico" no catálogo
-- Step deve aparecer antes de "Prova Teórica" (order 4, PROVA_TEORICA é 5)

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Inserir step CURSO_TEORICO (order 4, antes de PROVA_TEORICA que é 5)
INSERT INTO `steps` (`code`, `name`, `description`, `order`, `is_active`) 
VALUES ('CURSO_TEORICO', 'Curso Teórico', 'Curso teórico concluído com presença em todas as disciplinas', 4, 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`), `order` = VALUES(`order`);

-- Atualizar order de PROVA_TEORICA para 5 (se já não for)
UPDATE `steps` SET `order` = 5 WHERE `code` = 'PROVA_TEORICA' AND `order` != 5;

-- Atualizar order das etapas subsequentes (se necessário)
UPDATE `steps` SET `order` = 6 WHERE `code` = 'PRATICA_MINIMA' AND `order` < 6;
UPDATE `steps` SET `order` = 7 WHERE `code` = 'PROVA_PRATICA' AND `order` < 7;
UPDATE `steps` SET `order` = 8 WHERE `code` = 'CONCLUSAO' AND `order` < 8;

SET FOREIGN_KEY_CHECKS = 1;
