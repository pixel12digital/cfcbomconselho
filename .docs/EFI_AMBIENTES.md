# Configuração de Ambientes - EFÍ (Gerencianet)

**Data:** 2024  
**Gateway:** EFÍ (Gerencianet) - Produção

---

## Objetivo

Documentar a configuração do gateway EFÍ para diferentes ambientes (local e produção), garantindo que o mesmo código funcione em ambos os ambientes, diferenciando apenas pela configuração do `.env`.

---

## Princípios

1. **Mesmo código para todos os ambientes** - Não há flags ou travas específicas
2. **Configuração via `.env`** - Diferença entre ambientes é apenas no arquivo `.env`
3. **Produção direta** - Sistema operará diretamente em produção (`EFI_SANDBOX=false`)
4. **Testes locais com cobrança real** - Testes serão feitos localmente com cobrança real única

---

## Variáveis de Ambiente

### Variáveis Obrigatórias

```env
# EFÍ (Gerencianet) - Gateway de Pagamento
EFI_CLIENT_ID=seu_client_id_aqui
EFI_CLIENT_SECRET=seu_client_secret_aqui
EFI_SANDBOX=false
```

### Variáveis Opcionais

```env
EFI_CERT_PATH=/caminho/para/certificado.p12
EFI_WEBHOOK_SECRET=seu_webhook_secret_aqui
APP_ENV=local|production
```

**Observações:**
- `EFI_SANDBOX`: Sempre `false` (produção direta)
- `EFI_CERT_PATH`: Apenas se certificado for exigido pela Efí
- `EFI_WEBHOOK_SECRET`: Recomendado para validar assinatura do webhook
- `APP_ENV`: Opcional, apenas para identificação (não usado na lógica)

---

## Ambiente Local (Desenvolvimento)

### Configuração do `.env`

```env
# Configuração do Banco de Dados (LOCAL)
DB_HOST=localhost
DB_NAME=cfc_db
DB_USER=root
DB_PASS=

# EFÍ (Gerencianet) - Gateway de Pagamento
EFI_CLIENT_ID=Client_Id_producao_real
EFI_CLIENT_SECRET=Client_Secret_producao_real
EFI_SANDBOX=false
EFI_CERT_PATH=
EFI_WEBHOOK_SECRET=webhook_secret_producao

# Identificação do Ambiente (opcional)
APP_ENV=local
```

### Características

- ✅ **Banco de dados:** Local (MySQL local)
- ✅ **Credenciais Efí:** Reais de produção
- ✅ **EFI_SANDBOX:** `false` (produção)
- ✅ **Geração de cobrança:** Permitida normalmente
- ✅ **Webhook:** Funciona normalmente (URL local via ngrok/tunnel se necessário)

### ⚠️ IMPORTANTE - Banco de Dados

**NUNCA usar banco de produção no ambiente local!**

- Use sempre um banco de dados local separado
- Dados de teste não devem misturar com dados de produção
- Cada ambiente tem seu próprio banco

### Fluxo de Teste Recomendado

1. **Configurar `.env` local** com credenciais reais de produção
2. **Criar matrícula de teste** no banco local
3. **Gerar cobrança real única** via interface
4. **Verificar cobrança** na dashboard da Efí
5. **Processar pagamento** (ou cancelar) na dashboard da Efí
6. **Verificar webhook** (se configurado com tunnel)
7. **Encerrar teste** - cancelar/remover cobrança na Efí

---

## Ambiente de Produção

### Configuração do `.env`

```env
# Configuração do Banco de Dados (PRODUÇÃO)
DB_HOST=servidor_producao
DB_NAME=cfc_db_prod
DB_USER=usuario_prod
DB_PASS=senha_segura_prod

# EFÍ (Gerencianet) - Gateway de Pagamento
EFI_CLIENT_ID=Client_Id_producao_real
EFI_CLIENT_SECRET=Client_Secret_producao_real
EFI_SANDBOX=false
EFI_CERT_PATH=/caminho/absoluto/certificado.p12
EFI_WEBHOOK_SECRET=webhook_secret_producao

# Identificação do Ambiente (opcional)
APP_ENV=production
```

### Características

- ✅ **Banco de dados:** Produção (servidor remoto)
- ✅ **Credenciais Efí:** Reais de produção
- ✅ **EFI_SANDBOX:** `false` (produção)
- ✅ **Geração de cobrança:** Operação normal
- ✅ **Webhook:** URL pública configurada na dashboard Efí

### URL do Webhook em Produção

Configure na dashboard da Efí:
```
https://seudominio.com.br/api/payments/webhook/efi
```

---

## Comparação de Ambientes

| Aspecto | Local (DEV) | Produção |
|---------|-------------|----------|
| **Banco de Dados** | MySQL local | MySQL servidor |
| **EFI_CLIENT_ID** | Produção real | Produção real |
| **EFI_CLIENT_SECRET** | Produção real | Produção real |
| **EFI_SANDBOX** | `false` | `false` |
| **Cobranças** | Reais (teste único) | Reais (operacionais) |
| **Webhook URL** | Local (tunnel) | Pública |
| **Código** | Mesmo | Mesmo |

---

## Deploy para Produção

### Checklist de Deploy

1. ✅ **Código**
   - Copiar código completo (mesmo de local)
   - Nenhuma alteração de código necessária

2. ✅ **Arquivo `.env`**
   - Criar `.env` na raiz do projeto
   - Configurar variáveis de produção
   - **NUNCA commitar `.env` no Git** (já está no `.gitignore`)

3. ✅ **Banco de Dados**
   - Configurar conexão no `.env`
   - Executar migrations se necessário
   - Verificar conexão

4. ✅ **Webhook**
   - Configurar URL pública na dashboard Efí
   - Testar recebimento de webhook
   - Verificar validação de assinatura

5. ✅ **Permissões**
   - Verificar permissões do arquivo `.env` (chmod 600 recomendado)
   - Verificar permissões de diretórios

---

## Segurança

### ✅ Já Implementado

- `.env` está no `.gitignore` (não será commitado)
- `EfiPaymentService` nunca loga `client_secret` ou `cert_path`
- Validação de assinatura do webhook (se `EFI_WEBHOOK_SECRET` configurado)
- Autenticação obrigatória para geração de cobrança

### ⚠️ Recomendações

- **Nunca commitar `.env`** no Git
- **Usar permissões restritas** no `.env` (chmod 600)
- **Não compartilhar credenciais** por email/chat
- **Usar credenciais diferentes** apenas se necessário (mas no nosso caso, usamos as mesmas)
- **Backup seguro** do `.env` (fora do repositório)

---

## Troubleshooting

### Erro: "Configuração do gateway não encontrada"
- Verificar se `EFI_CLIENT_ID` e `EFI_CLIENT_SECRET` estão no `.env`
- Verificar se `.env` está na raiz do projeto
- Verificar se `Env::load()` está sendo chamado

### Erro: "Falha ao autenticar no gateway"
- Verificar se credenciais estão corretas
- Verificar se `EFI_SANDBOX=false` está configurado
- Verificar se certificado é necessário (alguns planos exigem)

### Webhook não funciona em local
- Usar tunnel (ngrok, localtunnel, etc.)
- Configurar URL do tunnel na dashboard Efí
- Verificar se `EFI_WEBHOOK_SECRET` está correto

### Webhook não funciona em produção
- Verificar se URL está acessível publicamente
- Verificar se rota está configurada corretamente
- Verificar logs do servidor
- Verificar validação de assinatura

---

## URLs da API Efí

O sistema usa automaticamente as URLs corretas baseado em `EFI_SANDBOX`:

- **Sandbox (`EFI_SANDBOX=true`):** `https://sandbox.gerencianet.com.br/v1`
- **Produção (`EFI_SANDBOX=false`):** `https://api.gerencianet.com.br/v1`

**No nosso caso:** Sempre produção (`EFI_SANDBOX=false`)

---

## Observações Importantes

1. **Mesmo código para todos os ambientes** - Não há flags ou travas
2. **Diferença apenas no `.env`** - Configuração é a única diferença
3. **Banco separado por ambiente** - Nunca misturar dados
4. **Testes com cobrança real** - Cuidado ao testar localmente
5. **Webhook público em produção** - Configurar URL correta na Efí

---

## Exemplo de Teste Local

1. Configurar `.env` local com credenciais reais
2. Criar matrícula de teste no banco local
3. Gerar cobrança via interface
4. Verificar cobrança na dashboard Efí
5. **Cancelar cobrança** na dashboard Efí após teste
6. Verificar atualização de status no sistema

---

✅ **Sistema pronto para produção!** Mesmo código, mesma lógica, apenas configuração diferente.
