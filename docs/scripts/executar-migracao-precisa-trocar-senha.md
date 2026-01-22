# Como Executar a Migração: precisa_trocar_senha

## Opção 1: Via Navegador (Recomendado)

1. Acesse o sistema como administrador
2. Navegue para: `http://seu-dominio/admin/migrate-precisa-trocar-senha.php`
3. O script irá:
   - Verificar se a coluna já existe
   - Criar a coluna se necessário
   - Exibir informações detalhadas sobre o resultado
4. Após confirmar sucesso, você pode remover o arquivo `admin/migrate-precisa-trocar-senha.php`

## Opção 2: Via phpMyAdmin

1. Acesse o phpMyAdmin
2. Selecione o banco de dados do sistema
3. Vá para a aba "SQL"
4. Execute o script: `docs/scripts/migration-precisa-trocar-senha.sql`
5. Verifique se a coluna foi criada

## Opção 3: Via Terminal (CLI)

```bash
# Navegue até a pasta do projeto
cd /caminho/para/cfc-bom-conselho

# Execute o script PHP via CLI
php admin/migrate-precisa-trocar-senha.php
```

## Verificação Manual

Após executar a migração, você pode verificar manualmente:

```sql
-- Verificar se a coluna existe
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'usuarios'
  AND COLUMN_NAME = 'precisa_trocar_senha';
```

## Segurança

- O script verifica se você é administrador antes de executar (via web)
- Pode ser executado múltiplas vezes com segurança (verifica se já existe)
- Não remove ou modifica dados existentes
- Apenas adiciona a coluna se ela não existir

## Após a Migração

Após confirmar que a migração foi bem-sucedida:

1. ✅ Teste o fluxo de redefinição de senha
2. ✅ Verifique que o flag está sendo marcado corretamente
3. ✅ Remova o arquivo `admin/migrate-precisa-trocar-senha.php` (opcional, mas recomendado)

