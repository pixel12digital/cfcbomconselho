# Erro: Colunas do Gateway Não Encontradas

## Problema

Erro ao acessar `/financeiro`:
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'e.gateway_charge_id' in 'where clause'
```

## Causa

A migration 030 (`030_add_gateway_fields_to_enrollments.sql`) não foi executada ainda.

## Solução

Execute a migration 030 para adicionar as colunas do gateway:

### Opção 1: Via Script PHP

```bash
php tools/run_migration_030.php
```

### Opção 2: Via MySQL Diretamente

Execute o SQL:

```sql
ALTER TABLE `enrollments`
ADD COLUMN `gateway_provider` varchar(50) DEFAULT NULL,
ADD COLUMN `gateway_charge_id` varchar(255) DEFAULT NULL,
ADD COLUMN `gateway_last_status` varchar(50) DEFAULT NULL,
ADD COLUMN `gateway_last_event_at` datetime DEFAULT NULL;

ALTER TABLE `enrollments`
ADD KEY `gateway_provider` (`gateway_provider`),
ADD KEY `gateway_charge_id` (`gateway_charge_id`),
ADD KEY `gateway_last_event_at` (`gateway_last_event_at`);
```

### Opção 3: Via Arquivo SQL

```sql
SOURCE database/migrations/030_add_gateway_fields_to_enrollments.sql;
```

## Verificação

Após executar, verifique se as colunas foram criadas:

```sql
DESCRIBE enrollments;
```

Ou:

```sql
SELECT COLUMN_NAME 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'enrollments' 
AND COLUMN_NAME LIKE 'gateway_%';
```

## Comportamento Atual

O sistema agora verifica se as colunas existem antes de usar. Se não existirem:
- A lista de pendentes retorna vazia (sem erro)
- O endpoint de sincronização retorna erro amigável

## Próximos Passos

Após executar a migration 030, também execute a migration 031 para adicionar `gateway_payment_url`:

```bash
php tools/run_migration_031.php
```

Ou:

```sql
SOURCE database/migrations/031_add_gateway_payment_url_to_enrollments.sql;
```
