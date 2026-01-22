-- Script para criar registro de instrutor para Carlos da Silva (usuario_id=44)
-- Problema: Usuário existe na tabela usuarios com tipo='instrutor', mas não existe registro em instrutores

-- Verificar se já existe
SELECT id, nome, usuario_id FROM instrutores WHERE usuario_id = 44;

-- Inserir registro de instrutor (ajuste os valores conforme necessário)
-- Campos obrigatórios baseados na estrutura da tabela:
INSERT INTO instrutores (
    nome,
    usuario_id,
    cfc_id,
    credencial,
    ativo,
    criado_em
) VALUES (
    'Carlos da Silva',  -- Nome do instrutor
    44,                 -- usuario_id (ID do usuário na tabela usuarios)
    1,                  -- cfc_id (ajuste para o CFC correto)
    'CRED-' || LPAD(44, 6, '0'),  -- Credencial gerada automaticamente
    1,                  -- ativo (1 = ativo, 0 = inativo)
    NOW()               -- criado_em
);

-- Verificar se foi criado
SELECT id, nome, usuario_id, cfc_id, credencial, ativo FROM instrutores WHERE usuario_id = 44;

