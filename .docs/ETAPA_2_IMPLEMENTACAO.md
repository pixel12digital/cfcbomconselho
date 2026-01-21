# ETAPA 2 - Implementação: Persistência Mínima + UNIQUE com Checagem

## Resumo

Implementação da Etapa 2 do plano: persistir dados essenciais de pagamento (PIX copia-e-cola e linha digitável) e adicionar constraint de unicidade para reduzir risco de duplicidade, sem alterar fluxos existentes.

## Arquivos Criados

### 1. Migration 033
**Arquivo:** `database/migrations/033_add_payment_tokens_to_enrollments.sql`

Adiciona dois novos campos na tabela `enrollments`:
- `gateway_pix_code` (TEXT) - PIX copia-e-cola (quando aplicável)
- `gateway_barcode` (VARCHAR(255)) - Linha digitável do boleto (quando aplicável)

### 2. Script de Verificação de Duplicados
**Arquivo:** `tools/check_duplicates_before_unique.php`

Script que verifica se existem duplicados na combinação `(gateway_provider, gateway_charge_id)` antes de aplicar a constraint UNIQUE. Se houver duplicados, exibe relatório detalhado com plano de correção.

### 3. Script de Aplicação da Constraint
**Arquivo:** `tools/apply_unique_constraint.php`

Script que aplica a constraint UNIQUE após verificar que não há duplicados. Só deve ser executado após corrigir todos os duplicados encontrados pelo script anterior.

## Arquivos Modificados

### 1. `app/Services/EfiPaymentService.php`

#### Método `createCharge()`
- **Linha ~381**: Extrai `pixCode` do retorno da API Pix (`pixCopiaECola` ou `qrCode`)
- **Linhas ~443-456**: Extrai `barcode` (linha digitável) e `pixCode` do retorno da API de Cobranças
- **Linha ~468**: Passa `pixCode` e `barcode` para `updateEnrollmentStatus()`

#### Método `updateEnrollmentStatus()`
- **Assinatura atualizada**: Adiciona parâmetros `$pixCode = null` e `$barcode = null`
- **Persistência**: Salva `gateway_pix_code` e `gateway_barcode` quando fornecidos

#### Método `parseWebhook()`
- **Linhas ~995-1020**: Extrai `pixCode` e `barcode` do payload do webhook (verifica em `current.payment` e `payment`)
- **Linhas ~1137-1149**: Atualiza `gateway_pix_code` e `gateway_barcode` ao processar webhook de parcela do carnê
- **Linhas ~1200-1218**: Atualiza `gateway_pix_code` e `gateway_barcode` ao processar webhook de carnê completo
- **Linhas ~1219-1233**: Passa `pixCode` e `barcode` para `updateEnrollmentStatus()` ao processar webhook de cobrança única

### 2. `app/Controllers/PaymentsController.php`

#### Método `generate()`
- **Linhas ~96-104**: Retorna `pix_code` e `barcode` no caso idempotente (quando cobrança já existe)

## Constraint UNIQUE

### Aplicação Segura

**IMPORTANTE:** A constraint UNIQUE só deve ser aplicada após verificar e corrigir todos os duplicados.

**Passos:**
1. Execute `php tools/check_duplicates_before_unique.php`
2. Se houver duplicados, corrija-os conforme o plano exibido
3. Execute novamente o script de verificação para confirmar que está limpo
4. Execute `php tools/apply_unique_constraint.php` para aplicar a constraint

**SQL da Constraint:**
```sql
ALTER TABLE enrollments
  ADD UNIQUE KEY uniq_gateway_provider_charge (gateway_provider, gateway_charge_id);
```

**Observação:** MySQL permite múltiplos NULLs em colunas UNIQUE, então registros sem `gateway_charge_id` não serão afetados.

## Fluxos Não Alterados

✅ **Rotas/fluxos existentes:** Nenhuma rota foi alterada ou criada
✅ **Lógica de geração:** Apenas adicionada persistência de dados adicionais
✅ **Lógica de sync:** Não alterada
✅ **Lógica de cancel:** Não alterada
✅ **Lógica de webhook:** Apenas adicionada persistência de dados adicionais
✅ **Endpoints para aluno:** Nenhum endpoint novo foi criado

## Casos de Uso

### PIX
- Ao gerar cobrança PIX, o campo `pixCopiaECola` (ou `qrCode`) é salvo em `gateway_pix_code`
- `gateway_barcode` permanece NULL (PIX não tem linha digitável)

### Boleto Único
- Ao gerar cobrança boleto, o campo `banking_billet.barcode` é salvo em `gateway_barcode`
- `gateway_pix_code` permanece NULL (boleto não tem PIX)

### Carnê
- Para carnê, os campos `gateway_pix_code` e `gateway_barcode` podem permanecer NULL
- Cada parcela tem seu próprio boleto, então não há uma linha digitável única
- Os dados completos do carnê continuam sendo salvos em `gateway_payment_url` (JSON)

### Webhook
- Se o webhook trouxer dados de pagamento (`pixCopiaECola` ou `barcode`), eles são persistidos
- Se não trouxer, os campos permanecem inalterados

### Idempotência
- Quando `generate()` é chamado e a cobrança já existe, retorna também `pix_code` e `barcode` se disponíveis

## Testes Recomendados

1. **PIX:** Gerar cobrança PIX e confirmar que `gateway_pix_code` foi salvo
2. **Boleto único:** Gerar cobrança boleto e confirmar que `gateway_barcode` foi salvo (se retorno tiver)
3. **Carnê:** Gerar carnê e confirmar que não quebrou nada; campos podem ficar nulos
4. **Idempotência:** Chamar `generate()` 2x e garantir que:
   - Não cria duplicado
   - Retorna os campos novos se existirem
5. **Migration:** Aplicar migration em ambiente de teste e confirmar que não quebra telas do aluno

## Próximos Passos

1. Executar `php tools/check_duplicates_before_unique.php` para verificar duplicados
2. Se houver duplicados, corrigi-los conforme o plano exibido
3. Aplicar a migration 033: `php tools/run_migration_033.php` (ou executar o SQL diretamente)
4. Após confirmar que não há duplicados, aplicar a constraint UNIQUE: `php tools/apply_unique_constraint.php`
5. Testar os casos de uso acima
