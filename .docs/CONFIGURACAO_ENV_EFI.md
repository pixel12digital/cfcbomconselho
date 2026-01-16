# Configuração do .env para EFÍ (Gerencianet)

**Localização:** Raiz do projeto (`/.env`)

---

## Como Funciona

O sistema carrega automaticamente as variáveis do arquivo `.env` através de `app/Config/Env.php`. O `EfiPaymentService` lê essas variáveis no construtor.

---

## Variáveis Necessárias

Adicione as seguintes variáveis ao seu arquivo `.env`:

```env
# EFÍ (Gerencianet) - Gateway de Pagamento
EFI_CLIENT_ID=seu_client_id_aqui
EFI_CLIENT_SECRET=seu_client_secret_aqui
EFI_SANDBOX=true
EFI_CERT_PATH=/caminho/para/certificado.p12
EFI_WEBHOOK_SECRET=seu_webhook_secret_aqui
```

---

## Detalhamento das Variáveis

### EFI_CLIENT_ID
- **Obrigatório:** Sim
- **Descrição:** Client ID da sua aplicação na Efí
- **Onde obter:** Dashboard da Efí → Credenciais
- **Exemplo:** `Client_Id_abc123def456`

### EFI_CLIENT_SECRET
- **Obrigatório:** Sim
- **Descrição:** Client Secret da sua aplicação na Efí
- **Onde obter:** Dashboard da Efí → Credenciais
- **Exemplo:** `Client_Secret_xyz789`
- **⚠️ Segurança:** Nunca commitar no Git (já está no .gitignore)

### EFI_SANDBOX
- **Obrigatório:** Sim
- **Descrição:** Define se usa ambiente sandbox ou produção
- **Valores:** `true` (sandbox) ou `false` (produção)
- **Recomendação:** Use `true` para testes, `false` para produção

### EFI_CERT_PATH
- **Obrigatório:** **SIM em produção** (opcional em sandbox)
- **Descrição:** Caminho absoluto para o certificado .p12
- **Exemplo Windows:** `C:\xampp\certificados\efi_producao.p12`
- **Exemplo Linux:** `/var/www/certs/efi_cert.p12`
- **⚠️ IMPORTANTE:** 
  - **Sandbox:** Geralmente não exige certificado
  - **Produção:** **SEMPRE exige certificado cliente** para autenticação OAuth (mutual TLS)
  - Obter certificado em: https://dev.gerencianet.com.br/ → API → Meus Certificados → Produção

### EFI_WEBHOOK_SECRET
- **Obrigatório:** Não (mas recomendado)
- **Descrição:** Secret para validar assinatura HMAC-SHA256 do webhook
- **Onde obter:** Dashboard da Efí → Webhooks → Configurações
- **Recomendação:** Configure para garantir segurança do webhook

---

## Exemplo Completo de .env

```env
# Configuração do Banco de Dados
DB_HOST=localhost
DB_NAME=cfc_db
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4

# EFÍ (Gerencianet) - Gateway de Pagamento
EFI_CLIENT_ID=Client_Id_abc123def456
EFI_CLIENT_SECRET=Client_Secret_xyz789
EFI_SANDBOX=false
EFI_CERT_PATH=C:\xampp\certificados\efi_producao.p12
EFI_WEBHOOK_SECRET=webhook_secret_123
```

**⚠️ Nota:** `EFI_CERT_PATH` é **obrigatório em produção**. Em sandbox, pode ficar vazio.

---

## Como Criar o Arquivo .env

1. **Na raiz do projeto**, crie o arquivo `.env` (se não existir)
2. **Copie as variáveis acima** e preencha com suas credenciais reais
3. **Salve o arquivo**

**Importante:** O arquivo `.env` está no `.gitignore`, então não será commitado no Git (segurança).

---

## Verificação

Após configurar, o sistema carregará automaticamente as variáveis quando `EfiPaymentService` for instanciado.

**Teste rápido:**
- Tente gerar uma cobrança na interface
- Se as credenciais estiverem incorretas, você verá erro: "Configuração do gateway não encontrada" ou "Falha ao autenticar no gateway"

---

## URLs da API

O sistema usa automaticamente as URLs corretas baseado em `EFI_SANDBOX`:

- **Sandbox (`EFI_SANDBOX=true`):** 
  - OAuth: `https://sandbox.gerencianet.com.br/oauth/token`
  - API: `https://sandbox.gerencianet.com.br/v1`
- **Produção (`EFI_SANDBOX=false`):** 
  - OAuth: `https://apis.gerencianet.com.br/oauth/token` (sem `/v1` e com "apis" no plural)
  - API: `https://apis.gerencianet.com.br/v1`

---

## Segurança

✅ **Já configurado:**
- `.env` está no `.gitignore` (não será commitado)
- `EfiPaymentService` nunca loga `client_secret` ou `cert_path`
- Logs não expõem dados sensíveis

⚠️ **Recomendações:**
- Mantenha o `.env` apenas no servidor
- Use permissões restritas no arquivo (chmod 600)
- Não compartilhe credenciais por email/chat
- Use diferentes credenciais para sandbox e produção

---

## Onde Obter as Credenciais

1. **Acesse:** https://dev.gerencianet.com.br/
2. **Faça login** na sua conta
3. **Vá em:** Minha Conta → Credenciais
4. **Copie:** Client ID e Client Secret
5. **Para Webhook Secret:** Webhooks → Configurações → Secret

---

## Troubleshooting

### Erro: "Configuração do gateway não encontrada"
- Verifique se `EFI_CLIENT_ID` e `EFI_CLIENT_SECRET` estão preenchidos
- Verifique se o arquivo `.env` está na raiz do projeto
- Verifique se `Env::load()` está sendo chamado

### Erro: "Falha ao autenticar no gateway"

### Erro: "Connection was reset" ou "Recv failure" (Produção)
- **Causa:** Certificado cliente (.p12) não configurado ou inválido
- **Solução:** 
  1. Obter certificado em: https://dev.gerencianet.com.br/ → API → Meus Certificados → Produção
  2. Salvar certificado em local seguro
  3. Configurar `EFI_CERT_PATH` no `.env` com caminho absoluto
  4. Reiniciar servidor web
- **Documentação completa:** Ver `.docs/CERTIFICADO_EFI_PRODUCAO.md`
- Verifique se as credenciais estão corretas
- Verifique se está usando o ambiente correto (sandbox vs produção)
- Verifique se há certificado necessário (alguns planos da Efí exigem)

### Webhook não valida assinatura
- Verifique se `EFI_WEBHOOK_SECRET` está configurado
- Verifique se o secret no `.env` corresponde ao configurado na dashboard da Efí
