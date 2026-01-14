-- Migration 020: Adicionar campo must_change_password em usuarios

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Adicionar campo must_change_password
ALTER TABLE `usuarios` 
ADD COLUMN `must_change_password` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Obrigar troca de senha no próximo login' AFTER `status`;

SET FOREIGN_KEY_CHECKS = 1;
