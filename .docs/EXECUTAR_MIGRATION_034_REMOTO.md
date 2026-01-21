# Executar Migration 034 no Banco Remoto

**Migration:** Adicionar campo `logo_path` na tabela `cfcs`  
**Script:** `tools/run_migration_034_remote.php`

## Opção 1: Via SSH (Recomendado)

### Passo 1: Conectar ao servidor via SSH

```bash
ssh usuario@servidor
# ou
ssh u502697186@br-asc-web803.hostinger.com
```

### Passo 2: Navegar até o diretório do projeto

```bash
cd /caminho/do/projeto
# Exemplo: cd ~/public_html/painel
```

### Passo 3: Executar o script

```bash
php tools/run_migration_034_remote.php
```

### Saída esperada:

```
=== EXECUTANDO MIGRATION 034 - ADICIONAR LOGO_PATH NA TABELA CFCS ===

1. Verificando conexão com banco de dados...
   Banco configurado: nome_do_banco
   Banco em uso: nome_do_banco
   Host: localhost

2. Verificando tabela cfcs...
   ✅ Tabela 'cfcs' existe

3. Verificando coluna logo_path...
   Coluna 'logo_path' não existe. Adicionando...
   ✅ Coluna 'logo_path' adicionada com sucesso

4. Verificação final...
   ✅ Coluna 'logo_path' existe

   Detalhes da coluna:
   - Tipo: varchar(255)
   - Null: YES
   - Default: NULL

✅ MIGRATION 034 EXECUTADA COM SUCESSO!

O campo logo_path foi adicionado à tabela cfcs.
Agora você pode fazer upload de logos por CFC para personalizar os ícones PWA.
```

## Opção 2: Via SQL Direto (Alternativa)

Se preferir executar o SQL diretamente:

### Via phpMyAdmin ou cliente MySQL:

```sql
ALTER TABLE `cfcs` 
ADD COLUMN `logo_path` VARCHAR(255) DEFAULT NULL 
COMMENT 'Caminho do arquivo de logo do CFC (para ícones PWA)' 
AFTER `email`;
```

### Via linha de comando MySQL:

```bash
mysql -u usuario -p nome_do_banco < database/migrations/034_add_logo_path_to_cfcs.sql
```

## Verificação

Após executar, verifique se a coluna foi criada:

```sql
DESCRIBE cfcs;
```

Ou:

```sql
SHOW COLUMNS FROM cfcs LIKE 'logo_path';
```

## Troubleshooting

### Erro: "Tabela 'cfcs' não existe"
- Execute primeiro as migrations base (001, 002, etc.)

### Erro: "Coluna 'logo_path' já existe"
- A migration já foi executada anteriormente
- Não é necessário executar novamente

### Erro de conexão com banco
- Verifique o arquivo `.env` com as credenciais corretas
- Verifique se o banco de dados está acessível

## Próximos Passos

Após executar a migration:

1. ✅ Acesse `/configuracoes/cfc` no sistema (como ADMIN)
2. ✅ Faça upload do logo do CFC
3. ✅ Os ícones PWA (192x192 e 512x512) serão gerados automaticamente
4. ✅ O manifest PWA usará os ícones do CFC automaticamente
