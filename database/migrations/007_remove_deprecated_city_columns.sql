-- Migration 007: Remover colunas DEPRECATED de cidade (varchar)
-- 
-- ⚠️ IMPORTANTE: Execute esta migration APENAS APÓS:
-- 1. Executar migrate_city_text_to_fk.php para migrar dados antigos
-- 2. Verificar que não há mais registros usando city/birth_city (varchar)
-- 3. Rodar o sistema por um período de compatibilidade
-- 4. Confirmar que todos os dados foram migrados
--
-- Para verificar antes de executar:
-- SELECT COUNT(*) FROM students WHERE (city IS NOT NULL AND city != '') OR (birth_city IS NOT NULL AND birth_city != '');
-- Se retornar 0, pode executar esta migration com segurança.

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Verificar se há dados não migrados antes de remover
-- (Descomente as linhas abaixo quando estiver pronto para executar)

/*
-- Remover índices relacionados
ALTER TABLE `students` DROP INDEX IF EXISTS `idx_city`;

-- Remover colunas DEPRECATED
ALTER TABLE `students`
  DROP COLUMN IF EXISTS `city`,
  DROP COLUMN IF EXISTS `birth_city`;

SET FOREIGN_KEY_CHECKS = 1;
*/

-- NOTA: Esta migration está comentada por segurança.
-- Descomente e execute apenas quando tiver certeza de que todos os dados foram migrados.
