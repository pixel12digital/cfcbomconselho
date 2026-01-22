# Como Executar as Migrações da Fase 2

## 📋 Instruções

As migrações da Fase 2 criam duas novas tabelas no banco de dados:
- `ocorrencias_instrutor`
- `contatos_instrutor`

## 🔧 Método 1: Via Script PHP (Recomendado)

1. **Acesse o script de migração no navegador:**
   ```
   http://localhost/cfc-bom-conselho/admin/migrate-fase2-tabelas.php
   ```
   (Ajuste a URL conforme seu ambiente)

2. **Faça login como administrador ou secretária** (se necessário)

3. **O script irá:**
   - Verificar se as tabelas já existem
   - Criar as tabelas se não existirem
   - Exibir um relatório detalhado do processo

4. **Após a execução bem-sucedida:**
   - Você pode deletar o arquivo `admin/migrate-fase2-tabelas.php` por segurança

## 🔧 Método 2: Via phpMyAdmin (Alternativo)

1. **Acesse o phpMyAdmin**

2. **Selecione o banco de dados** (`u502697186_cfcbomconselho`)

3. **Execute o primeiro script:**
   - Abra o arquivo `docs/scripts/migration_ocorrencias_instrutor.sql`
   - Copie o conteúdo do `CREATE TABLE`
   - Cole na aba SQL do phpMyAdmin
   - Execute

4. **Execute o segundo script:**
   - Abra o arquivo `docs/scripts/migration_contatos_instrutor.sql`
   - Copie o conteúdo do `CREATE TABLE`
   - Cole na aba SQL do phpMyAdmin
   - Execute

5. **Verifique se as tabelas foram criadas:**
   - Procure por `ocorrencias_instrutor` e `contatos_instrutor` na lista de tabelas

## ✅ Verificação

Após executar as migrações, verifique se as tabelas foram criadas:

```sql
SHOW TABLES LIKE 'ocorrencias_instrutor';
SHOW TABLES LIKE 'contatos_instrutor';
```

Ambas devem retornar 1 linha cada.

## 📝 Estrutura das Tabelas

### `ocorrencias_instrutor`
- Registra ocorrências reportadas por instrutores
- Campos principais: tipo, data_ocorrencia, aula_id, descricao, status, resolucao
- Foreign keys: instrutores, usuarios, aulas

### `contatos_instrutor`
- Registra mensagens de contato enviadas por instrutores para secretaria
- Campos principais: assunto, mensagem, aula_id, status, resposta
- Foreign keys: instrutores, usuarios, aulas

## ⚠️ Importante

- As migrações são **idempotentes** (podem ser executadas múltiplas vezes)
- O script verifica se as tabelas já existem antes de criar
- Não há risco de duplicação de dados

## 🗑️ Limpeza

Após confirmar que as migrações foram bem-sucedidas, você pode deletar:
- `admin/migrate-fase2-tabelas.php` (script temporário)

Os arquivos SQL em `docs/scripts/` devem ser mantidos para referência.

