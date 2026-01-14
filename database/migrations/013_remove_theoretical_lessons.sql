-- Migration 013: Remover suporte a aulas teóricas
-- Conforme nova legislação, apenas aulas práticas existem

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Alterar ENUM para remover 'teorica', mantendo apenas 'pratica'
ALTER TABLE `lessons` 
MODIFY COLUMN `type` enum('pratica') NOT NULL DEFAULT 'pratica';

-- Tornar vehicle_id obrigatório (não pode ser NULL)
ALTER TABLE `lessons` 
MODIFY COLUMN `vehicle_id` int(11) NOT NULL COMMENT 'Veículo obrigatório para aulas práticas';

-- Remover comentário sobre aulas teóricas
ALTER TABLE `lessons` 
MODIFY COLUMN `vehicle_id` int(11) NOT NULL COMMENT 'Veículo da aula prática';

-- Atualizar qualquer registro existente que tenha type = 'teorica' (se houver)
UPDATE `lessons` SET `type` = 'pratica' WHERE `type` = 'teorica';

-- Garantir que não há aulas sem veículo
UPDATE `lessons` SET `vehicle_id` = (
    SELECT id FROM vehicles WHERE cfc_id = lessons.cfc_id AND is_active = 1 LIMIT 1
) WHERE `vehicle_id` IS NULL;

SET FOREIGN_KEY_CHECKS = 1;
