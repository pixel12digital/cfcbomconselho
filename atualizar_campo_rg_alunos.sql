-- =====================================================
-- ATUALIZAÇÃO DO CAMPO RG NA TABELA ALUNOS
-- =====================================================
-- 
-- PROBLEMA: Campo RG estava com VARCHAR(20) e máscara JavaScript
--           restrita ao formato '00.000.000-0', impedindo cadastro
--           de RGs de outros formatos usados em diferentes estados.
--
-- SOLUÇÃO: Aumentar para VARCHAR(30) para acomodar todos os 
--          formatos de RG dos estados brasileiros.
--
-- FORMATOS DE RG NO BRASIL (exemplos):
-- - SP: 00.000.000-0 (9 dígitos + 1 verificador)
-- - RJ: 00.000.000-0 (8 dígitos)
-- - MG: MG-00.000.000 (com letras)
-- - SC: 0.000.000 (7 dígitos)
-- - RS: 0000000000 (10 dígitos)
-- - PR: 00.000.000-0 (9 dígitos)
-- - BA: 00000000-00 (10 dígitos)
-- - E outros formatos variados
-- =====================================================

-- Alterar o tamanho do campo RG
ALTER TABLE alunos MODIFY COLUMN rg VARCHAR(30) DEFAULT NULL;

-- Verificar a alteração
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    CHARACTER_MAXIMUM_LENGTH,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM 
    INFORMATION_SCHEMA.COLUMNS
WHERE 
    TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'alunos'
    AND COLUMN_NAME = 'rg';

-- =====================================================
-- FIM DO SCRIPT
-- =====================================================

