# Procedimento: Como Criar Nova Cobrança para Aluno

## 📋 Visão Geral

Este documento explica o procedimento completo para criar uma nova cobrança na Efí (Gateway de Pagamento) para um aluno que possui matrícula no sistema.

---

## ✅ Pré-requisitos

Antes de criar uma cobrança, verifique se:

1. **O aluno possui uma matrícula cadastrada**
   - A matrícula deve estar com status "Ativa" ou "Concluída"
   - Não pode estar "Cancelada"

2. **A matrícula possui saldo devedor**
   - O campo `outstanding_amount` (saldo devedor) deve ser maior que zero
   - O saldo devedor é calculado como: `Valor Final - Entrada Recebida`

3. **A matrícula possui parcelas configuradas**
   - O campo `installments` (número de parcelas) deve estar preenchido
   - Deve ser maior que zero

4. **Não existe cobrança ativa**
   - A matrícula não deve ter uma cobrança já gerada e ativa no gateway
   - Status da cobrança não pode ser: `generated` com status `waiting`, `paid`, `settled`, etc.
   - Se a cobrança anterior foi `canceled`, `expired` ou `error`, é possível gerar nova

5. **Permissões do usuário**
   - O usuário logado deve ter perfil de **Administrador** ou **Secretaria**
   - Outros perfis não têm permissão para gerar cobranças

---

## 📍 Passo a Passo

### Passo 1: Acessar a Matrícula do Aluno

1. Acesse o menu **Alunos** no sistema
2. Localize e clique no aluno desejado
3. Na página do aluno, vá para a aba **"Matrículas"** (tab `matricula`)
4. Localize a matrícula que deseja gerar cobrança
5. Clique em **"Editar"** ou acesse diretamente a página de edição da matrícula

**URL:** `/matriculas/{id}/editar` ou `/alunos/{student_id}?tab=matricula`

---

### Passo 2: Verificar Dados da Matrícula

Na página de edição da matrícula, verifique:

1. **Valor Final** (`final_price`)
   - Deve estar preenchido e maior que zero

2. **Entrada Recebida** (`entry_amount`) - Opcional
   - Se houver entrada, verifique se está correta
   - A entrada reduz o saldo devedor

3. **Saldo Devedor** (`outstanding_amount`)
   - Deve aparecer na tela e ser maior que zero
   - Este é o valor que será cobrado no gateway

4. **Forma de Pagamento** (`payment_method`)
   - Deve estar selecionada: PIX, Boleto, Cartão ou Entrada + Parcelas

5. **Número de Parcelas** (`installments`)
   - Deve estar preenchido (ex: 1x, 3x, 6x, etc.)

6. **Status da Cobrança** (`billing_status`)
   - Deve estar como: `draft` (Rascunho), `ready` (Pronto) ou `error` (Erro)
   - Se estiver como `generated` (Gerado), verifique se a cobrança anterior foi cancelada/expirada

---

### Passo 3: Gerar a Cobrança

1. **Localize o botão "Gerar Cobrança Efí"**
   - O botão aparece na parte inferior da página, ao lado do botão "Atualizar Matrícula"
   - ⚠️ **Importante:** O botão só aparece se:
     - A matrícula tem parcelas configuradas (`installments > 0`)
     - A matrícula tem saldo devedor (`outstanding_amount > 0`)
     - Não existe cobrança ativa (`billing_status` não é `generated` ou a cobrança foi cancelada/expirada)

2. **Clique no botão "Gerar Cobrança Efí"**
   - Uma janela de confirmação aparecerá mostrando:
     - Valor da entrada (se houver)
     - Saldo devedor
     - Número de parcelas
     - Valor por parcela

3. **Confirme a geração**
   - Clique em "OK" para confirmar
   - O botão ficará desabilitado e mostrará "Gerando..." durante o processamento

---

### Passo 4: Aguardar Processamento

O sistema irá:

1. **Validar os dados** no servidor
2. **Criar a cobrança na API da Efí**
   - Para PIX: usa API Pix (`/v2/cob`)
   - Para Boleto: usa API de Cobranças (`/v1/charge/one-step`)
   - Para Cartão Parcelado: usa API de Cobranças com parcelamento
   - Para Carnê (Boleto Parcelado): usa API Carnê (`/v1/carnet`)

3. **Atualizar a matrícula** com:
   - `gateway_charge_id`: ID da cobrança no gateway
   - `gateway_last_status`: Status inicial (geralmente `waiting`)
   - `billing_status`: Atualizado para `generated`
   - `gateway_payment_url`: Link para pagamento (se disponível)

4. **Exibir resultado**
   - Se sucesso: mensagem com ID da cobrança, status e link de pagamento
   - Se erro: mensagem explicando o problema

---

### Passo 5: Verificar Resultado

Após a geração:

1. **A página será recarregada automaticamente**
2. **Verifique os campos atualizados:**
   - **ID da Cobrança**: Deve aparecer na seção "Condições de Pagamento"
   - **Status no Gateway**: Deve mostrar o status atual (ex: `waiting`, `paid`, etc.)
   - **Link de Pagamento**: Deve aparecer um link clicável para o pagamento

3. **Se houver erro:**
   - Verifique a mensagem de erro exibida
   - Verifique os logs em `storage/logs/php_errors.log`
   - Verifique se a configuração da Efí está correta no arquivo `.env`

---

## 🔄 Sincronizar Cobrança

Se você já gerou uma cobrança e deseja atualizar o status:

1. **Localize o botão "Sincronizar Cobrança"**
   - Aparece na página de edição da matrícula
   - Só aparece se já existe uma cobrança gerada (`gateway_charge_id` preenchido)

2. **Clique no botão**
   - O sistema consultará o status atual na API da Efí
   - Atualizará os campos da matrícula com o status mais recente

---

## 🚨 Problemas Comuns e Soluções

### ❌ Botão "Gerar Cobrança Efí" não aparece

**Possíveis causas:**
1. Matrícula não tem parcelas configuradas
   - **Solução:** Edite a matrícula e configure o número de parcelas

2. Saldo devedor é zero ou negativo
   - **Solução:** Verifique se o valor final está correto e se a entrada não é maior que o valor final

3. Já existe cobrança ativa
   - **Solução:** Se a cobrança anterior foi cancelada/expirada, aguarde alguns minutos ou sincronize a cobrança

4. Status da cobrança não permite nova geração
   - **Solução:** Verifique o campo `billing_status` e `gateway_last_status`

### ❌ Erro ao gerar cobrança: "Configuração do gateway não encontrada"

**Causa:** Credenciais da Efí não configuradas

**Solução:**
1. Verifique o arquivo `.env` na raiz do projeto
2. Confirme se as variáveis estão preenchidas:
   - `EFI_CLIENT_ID`
   - `EFI_CLIENT_SECRET`
   - `EFI_PIX_KEY` (se for gerar PIX)
   - `EFI_SANDBOX=true` (para testes) ou `EFI_SANDBOX=false` (produção)

### ❌ Erro ao gerar cobrança: "Chave PIX não configurada"

**Causa:** Tentando gerar PIX mas `EFI_PIX_KEY` não está configurada

**Solução:**
1. Configure `EFI_PIX_KEY` no arquivo `.env`
2. A chave PIX deve ser uma chave válida cadastrada na Efí

### ❌ Erro ao gerar cobrança: "Sem saldo devedor para gerar cobrança"

**Causa:** O saldo devedor é zero ou negativo

**Solução:**
1. Verifique se o valor final está correto
2. Verifique se a entrada não é maior ou igual ao valor final
3. Se necessário, ajuste o valor da entrada ou o valor final

### ❌ Erro ao gerar cobrança: "Cobrança já existe"

**Causa:** Já existe uma cobrança ativa para esta matrícula

**Solução:**
1. Verifique se a cobrança anterior foi realmente cancelada/expirada
2. Use o botão "Sincronizar Cobrança" para atualizar o status
3. Se necessário, aguarde alguns minutos para o sistema processar

---

## 📊 Tipos de Cobrança Suportados

O sistema suporta os seguintes tipos de cobrança na Efí:

### 1. **PIX (à vista)**
- **Condição:** `installments = 1` e `payment_method = 'pix'`
- **API usada:** API Pix (`/v2/cob`)
- **Retorna:** QR Code PIX para pagamento
- **Expiração:** 1 hora (configurável)

### 2. **Boleto (à vista)**
- **Condição:** `installments = 1` e `payment_method = 'boleto'`
- **API usada:** API de Cobranças (`/v1/charge/one-step`)
- **Retorna:** Link do boleto para pagamento
- **Vencimento:** 3 dias (padrão, configurável)

### 3. **Cartão Parcelado**
- **Condição:** `installments > 1` e `payment_method = 'cartao'`
- **API usada:** API de Cobranças (`/v1/charge/one-step`)
- **Retorna:** Link para pagamento com cartão
- **Requer:** Endereço completo do aluno

### 4. **Carnê (Boleto Parcelado)**
- **Condição:** `installments > 1` e `payment_method = 'boleto'`
- **API usada:** API Carnê (`/v1/carnet`)
- **Retorna:** Múltiplos boletos (um para cada parcela)
- **Vencimento:** Baseado nas datas de vencimento configuradas

---

## 🔗 Endpoints da API

### Gerar Cobrança
```
POST /api/payments/generate
Content-Type: application/json

{
  "enrollment_id": 123
}
```

**Resposta de Sucesso:**
```json
{
  "ok": true,
  "charge_id": "charge_abc123",
  "status": "waiting",
  "payment_url": "https://..."
}
```

**Resposta de Erro:**
```json
{
  "ok": false,
  "message": "Mensagem de erro"
}
```

### Sincronizar Cobrança
```
POST /api/payments/sync
Content-Type: application/json

{
  "enrollment_id": 123
}
```

---

## 📝 Campos da Matrícula Relacionados

| Campo | Descrição | Tipo |
|-------|-----------|------|
| `outstanding_amount` | Saldo devedor (valor a ser cobrado) | DECIMAL(10,2) |
| `installments` | Número de parcelas | INT |
| `payment_method` | Forma de pagamento (pix, boleto, cartao, entrada_parcelas) | ENUM |
| `billing_status` | Status da geração de cobrança (draft, ready, generated, error) | ENUM |
| `gateway_charge_id` | ID da cobrança no gateway | VARCHAR(255) |
| `gateway_last_status` | Último status do gateway | VARCHAR(50) |
| `gateway_payment_url` | Link para pagamento | TEXT |
| `gateway_last_event_at` | Data/hora do último evento | DATETIME |

---

## 🔐 Permissões Necessárias

- **Perfil:** Administrador ou Secretaria
- **Permissão:** Acesso ao módulo de pagamentos
- **Middleware:** `AuthMiddleware` e verificação de role

---

## 📞 Suporte

Se encontrar problemas que não foram resolvidos neste documento:

1. Verifique os logs em `storage/logs/php_errors.log`
2. Verifique a documentação da API Efí
3. Entre em contato com o suporte técnico

---

**Última atualização:** 2024  
**Versão do sistema:** CFC v.1
