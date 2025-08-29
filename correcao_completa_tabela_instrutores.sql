-- =====================================================
-- CORREÇÃO COMPLETA DA TABELA INSTRUTORES
-- Sistema CFC - Adicionar campos faltantes
-- =====================================================

USE u342734079_cfcbomconselho;

-- Verificar estrutura atual antes das alterações
SELECT 'ESTRUTURA ATUAL ANTES DAS ALTERAÇÕES:' as info;
DESCRIBE instrutores;

-- =====================================================
-- 1. ADICIONAR CAMPOS BÁSICOS FALTANTES
-- =====================================================

-- Adicionar campo nome (se não existir)
ALTER TABLE instrutores 
ADD COLUMN IF NOT EXISTS nome VARCHAR(100) NULL COMMENT 'Nome completo do instrutor' 
AFTER id;

-- Adicionar campo cpf (se não existir)
ALTER TABLE instrutores 
ADD COLUMN IF NOT EXISTS cpf VARCHAR(14) NULL COMMENT 'CPF do instrutor' 
AFTER nome;

-- Adicionar campo cnh (se não existir)
ALTER TABLE instrutores 
ADD COLUMN IF NOT EXISTS cnh VARCHAR(20) NULL COMMENT 'Número da CNH do instrutor' 
AFTER cpf;

-- Adicionar campo data_nascimento (se não existir)
ALTER TABLE instrutores 
ADD COLUMN IF NOT EXISTS data_nascimento DATE NULL COMMENT 'Data de nascimento do instrutor' 
AFTER cnh;

-- Adicionar campo email (se não existir)
ALTER TABLE instrutores 
ADD COLUMN IF NOT EXISTS email VARCHAR(100) NULL COMMENT 'Email do instrutor' 
AFTER data_nascimento;

-- Adicionar campo telefone (se não existir)
ALTER TABLE instrutores 
ADD COLUMN IF NOT EXISTS telefone VARCHAR(20) NULL COMMENT 'Telefone do instrutor' 
AFTER email;

-- =====================================================
-- 2. ADICIONAR CAMPOS DE ENDEREÇO
-- =====================================================

-- Adicionar campo endereco (se não existir)
ALTER TABLE instrutores 
ADD COLUMN IF NOT EXISTS endereco TEXT NULL COMMENT 'Endereço completo do instrutor' 
AFTER telefone;

-- Adicionar campo cidade (se não existir)
ALTER TABLE instrutores 
ADD COLUMN IF NOT EXISTS cidade VARCHAR(100) NULL COMMENT 'Cidade do instrutor' 
AFTER endereco;

-- Adicionar campo uf (se não existir)
ALTER TABLE instrutores 
ADD COLUMN IF NOT EXISTS uf CHAR(2) NULL COMMENT 'Estado (UF) do instrutor' 
AFTER cidade;

-- =====================================================
-- 3. ADICIONAR CAMPOS DE ESPECIALIDADES
-- =====================================================

-- Adicionar campo tipo_carga (se não existir)
ALTER TABLE instrutores 
ADD COLUMN IF NOT EXISTS tipo_carga VARCHAR(100) NULL COMMENT 'Tipo de carga que o instrutor pode transportar' 
AFTER categoria_habilitacao;

-- Adicionar campo validade_credencial (se não existir)
ALTER TABLE instrutores 
ADD COLUMN IF NOT EXISTS validade_credencial DATE NULL COMMENT 'Data de validade da credencial do instrutor' 
AFTER tipo_carga;

-- Adicionar campo observacoes (se não existir)
ALTER TABLE instrutores 
ADD COLUMN IF NOT EXISTS observacoes TEXT NULL COMMENT 'Observações e notas sobre o instrutor' 
AFTER validade_credencial;

-- =====================================================
-- 4. ADICIONAR CAMPOS DE HORÁRIOS
-- =====================================================

-- Adicionar campo horario_inicio (se não existir)
ALTER TABLE instrutores 
ADD COLUMN IF NOT EXISTS horario_inicio TIME NULL COMMENT 'Horário de início da disponibilidade' 
AFTER observacoes;

-- Adicionar campo horario_fim (se não existir)
ALTER TABLE instrutores 
ADD COLUMN IF NOT EXISTS horario_fim TIME NULL COMMENT 'Horário de fim da disponibilidade' 
AFTER horario_inicio;

-- Adicionar campo dias_semana (se não existir)
ALTER TABLE instrutores 
ADD COLUMN IF NOT EXISTS dias_semana JSON NULL COMMENT 'Dias da semana disponíveis (JSON)' 
AFTER horario_fim;

-- =====================================================
-- 5. ADICIONAR CAMPOS DE CONTROLE
-- =====================================================

-- Adicionar campo status (se não existir)
ALTER TABLE instrutores 
ADD COLUMN IF NOT EXISTS status ENUM('ativo', 'inativo', 'suspenso') DEFAULT 'ativo' COMMENT 'Status do instrutor' 
AFTER ativo;

-- Adicionar campo updated_at (se não existir)
ALTER TABLE instrutores 
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data de última atualização' 
AFTER criado_em;

-- =====================================================
-- 6. ADICIONAR CAMPOS DE CATEGORIAS (MELHORIA)
-- =====================================================

-- Adicionar campo categorias_json (se não existir) - para armazenar múltiplas categorias
ALTER TABLE instrutores 
ADD COLUMN IF NOT EXISTS categorias_json JSON NULL COMMENT 'Categorias de habilitação em formato JSON' 
AFTER categoria_habilitacao;

-- =====================================================
-- 7. VERIFICAR ESTRUTURA APÓS ALTERAÇÕES
-- =====================================================

SELECT 'ESTRUTURA APÓS AS ALTERAÇÕES:' as info;
DESCRIBE instrutores;

-- =====================================================
-- 8. VERIFICAR SE TODOS OS CAMPOS FORAM ADICIONADOS
-- =====================================================

SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'u342734079_cfcbomconselho' 
AND TABLE_NAME = 'instrutores'
ORDER BY ORDINAL_POSITION;

-- =====================================================
-- 9. VERIFICAR INTEGRIDADE REFERENCIAL
-- =====================================================

SELECT 'VERIFICANDO INTEGRIDADE REFERENCIAL:' as info;

-- Verificar se há instrutores com CFCs inválidos
SELECT 
    i.id,
    i.nome,
    i.cfc_id,
    CASE 
        WHEN c.id IS NULL THEN 'CFC NÃO ENCONTRADO'
        ELSE 'CFC VÁLIDO'
    END as status_cfc
FROM instrutores i
LEFT JOIN cfcs c ON i.cfc_id = c.id
WHERE c.id IS NULL;

-- Verificar se há usuários inválidos
SELECT 
    i.id,
    i.nome,
    i.usuario_id,
    CASE 
        WHEN u.id IS NULL THEN 'USUÁRIO NÃO ENCONTRADO'
        ELSE 'USUÁRIO VÁLIDO'
    END as status_usuario
FROM instrutores i
LEFT JOIN usuarios u ON i.usuario_id = u.id
WHERE u.id IS NULL;

-- =====================================================
-- 10. ATUALIZAR DADOS EXISTENTES (SE NECESSÁRIO)
-- =====================================================

-- Atualizar campo nome com dados do usuário (se estiver vazio)
UPDATE instrutores i 
JOIN usuarios u ON i.usuario_id = u.id 
SET i.nome = u.nome 
WHERE i.nome IS NULL OR i.nome = '';

-- Atualizar campo email com dados do usuário (se estiver vazio)
UPDATE instrutores i 
JOIN usuarios u ON i.usuario_id = u.id 
SET i.email = u.email 
WHERE i.email IS NULL OR i.email = '';

-- Atualizar campo telefone com dados do usuário (se estiver vazio)
UPDATE instrutores i 
JOIN usuarios u ON i.usuario_id = u.id 
SET i.telefone = u.telefone 
WHERE i.telefone IS NULL OR i.telefone = '';

-- =====================================================
-- 11. VERIFICAR RESULTADO FINAL
-- =====================================================

SELECT 'VERIFICAÇÃO FINAL:' as info;
SELECT 
    'Total de campos na tabela instrutores:' as info,
    COUNT(*) as total_campos
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'u342734079_cfcbomconselho' 
AND TABLE_NAME = 'instrutores';

SELECT 'Script de correção executado com sucesso!' as resultado;
