# Como Executar a Migration 031

## Opção 1: Via Script PHP (Recomendado)

Execute o script PHP criado:

```bash
php tools/run_migration_031.php
```

**Ou no Windows (se PHP estiver no PATH do XAMPP):**
```cmd
C:\xampp\php\php.exe tools\run_migration_031.php
```

## Opção 2: Via MySQL Diretamente

Execute o SQL diretamente no MySQL:

```sql
-- Migration 031: Adicionar campo gateway_payment_url
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Verificar se a coluna já existe antes de adicionar
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'enrollments'
    AND COLUMN_NAME = 'gateway_payment_url'
);

-- Adicionar coluna apenas se não existir
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `enrollments`
     ADD COLUMN `gateway_payment_url` TEXT DEFAULT NULL 
     COMMENT ''URL de pagamento (PIX QR Code ou Boleto) retornada pelo gateway'' AFTER `gateway_last_event_at`',
    'SELECT ''Coluna gateway_payment_url já existe'' AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
```

**Ou versão simplificada (se você tem certeza que a coluna não existe):**

```sql
ALTER TABLE `enrollments`
ADD COLUMN `gateway_payment_url` TEXT DEFAULT NULL 
COMMENT 'URL de pagamento (PIX QR Code ou Boleto) retornada pelo gateway'
AFTER `gateway_last_event_at`;
```

## Opção 3: Via phpMyAdmin

1. Acesse o phpMyAdmin
2. Selecione o banco de dados
3. Vá em "SQL"
4. Cole o SQL da migration 031
5. Execute

## Verificação

Após executar, verifique se a coluna foi criada:

```sql
DESCRIBE enrollments;
```

Ou:

```sql
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'enrollments'
AND COLUMN_NAME = 'gateway_payment_url';
```

A coluna `gateway_payment_url` deve aparecer como `TEXT` com `NULL` permitido.
