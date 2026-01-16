# Erros Comuns - Sistema Financeiro

## 1. Erro 400: "Falha ao autenticar no gateway"

### Sintoma
```
POST /api/payments/generate: 400 (Bad Request)
Mensagem: "Erro ao gerar cobrança: Falha ao autenticar no gateway"
```

### Causas Possíveis

#### A) Credenciais não configuradas
**Sintoma:** Mensagem menciona "Configuração do gateway incompleta"

**Solução:**
1. Verificar se o arquivo `.env` existe na raiz do projeto
2. Verificar se contém as variáveis:
   ```env
   EFI_CLIENT_ID=seu_client_id_aqui
   EFI_CLIENT_SECRET=seu_client_secret_aqui
   EFI_SANDBOX=false
   ```
3. Verificar se não há espaços extras ou aspas nas variáveis
4. Reiniciar o servidor web após alterar `.env`

#### B) Credenciais inválidas
**Sintoma:** Mensagem menciona "Falha ao autenticar no gateway"

**Solução:**
1. Verificar se `EFI_CLIENT_ID` e `EFI_CLIENT_SECRET` estão corretos
2. Verificar se as credenciais são do ambiente correto (sandbox vs produção)
3. Verificar se `EFI_SANDBOX` está configurado corretamente:
   - `EFI_SANDBOX=true` → usa `https://sandbox.gerencianet.com.br/v1`
   - `EFI_SANDBOX=false` → usa `https://api.gerencianet.com.br/v1`
4. Verificar logs do servidor para detalhes do erro HTTP:
   ```
   error_log: "EFI Auth Error: HTTP {código} - {mensagem}"
   ```

#### C) Problema de conexão / Certificado obrigatório
**Sintoma:** "Connection was reset" ou "Recv failure" em produção

**Causa:** A EFI **exige certificado cliente (.p12)** em produção para autenticação OAuth (mutual TLS).

**Solução:**
1. **Obter certificado:**
   - Acesse: https://dev.gerencianet.com.br/ → API → Meus Certificados
   - Selecione ambiente: **Produção**
   - Baixe o arquivo `.p12`
2. **Salvar certificado:**
   - Salve em local seguro (ex: `C:\xampp\certificados\efi_producao.p12`)
   - **NUNCA commitar no Git**
3. **Configurar no .env:**
   ```env
   EFI_CERT_PATH=C:\xampp\certificados\efi_producao.p12
   ```
4. **Reiniciar servidor web**
5. **Testar novamente**

**Documentação completa:** Ver `.docs/CERTIFICADO_EFI_PRODUCAO.md`

#### D) Problema de conexão (outros)
**Sintoma:** Timeout ou erro de cURL (não relacionado a certificado)

**Solução:**
1. Verificar conexão com internet
2. Verificar se firewall não está bloqueando requisições HTTPS
3. Verificar se certificado SSL está atualizado
4. Verificar logs para mensagens de erro de cURL

### Como Verificar

1. **Verificar variáveis no código:**
   ```php
   // Adicionar temporariamente em EfiPaymentService::__construct()
   error_log("EFI Config: CLIENT_ID=" . ($this->clientId ? 'SET' : 'EMPTY'));
   error_log("EFI Config: SANDBOX=" . ($this->sandbox ? 'true' : 'false'));
   ```

2. **Testar autenticação manualmente:**
   ```bash
   curl -X POST https://api.gerencianet.com.br/v1/oauth/token \
     -H "Content-Type: application/x-www-form-urlencoded" \
     -H "Authorization: Basic $(echo -n 'CLIENT_ID:CLIENT_SECRET' | base64)" \
     -d "grant_type=client_credentials"
   ```

3. **Verificar logs do servidor:**
   - XAMPP: `C:\xampp\apache\logs\error.log`
   - PHP error_log: verificar configuração em `php.ini`

### Melhorias Implementadas

O sistema agora retorna mensagens mais específicas:
- Se credenciais não estão configuradas: "Configuração do gateway incompleta..."
- Se credenciais estão incorretas: "Falha ao autenticar no gateway. Verifique se as credenciais estão corretas..."

---

## 2. Erro 404: `/icons/icon-192x192.png` não encontrado

### Sintoma
```
GET /icons/icon-192x192.png: 404 (Not Found)
```

### Causa
O arquivo de ícone PWA não existe no diretório `public_html/icons/`.

### Solução

#### Opção 1: Gerar ícones automaticamente (Recomendado)
1. Acesse no navegador: `http://localhost/cfc-v.1/public_html/generate-icons.php`
2. O script criará automaticamente:
   - `icon-192x192.png`
   - `icon-512x512.png`
3. Os ícones serão criados com texto "CFC" em fundo azul (#023A8D)

#### Opção 2: Criar manualmente
1. Criar diretório: `public_html/icons/`
2. Adicionar arquivo `icon-192x192.png` (192x192 pixels)
3. Adicionar arquivo `icon-512x512.png` (512x512 pixels) se necessário

#### Opção 3: Remover referência (temporário)
Se não for usar PWA agora, comentar a linha em `app/Views/layouts/shell.php`:
```php
<!-- <link rel="apple-touch-icon" href="<?= base_path('/icons/icon-192x192.png') ?>"> -->
```

### Verificação
Após criar os ícones, verificar se o arquivo existe:
```
public_html/icons/icon-192x192.png
```

---

## 3. Outros Erros Comuns

### Erro: "Configuração do gateway não encontrada"
- **Causa:** `EFI_CLIENT_ID` ou `EFI_CLIENT_SECRET` estão vazios no `.env`
- **Solução:** Verificar e preencher as variáveis no `.env`

### Erro: "Sem saldo devedor para gerar cobrança"
- **Causa:** `outstanding_amount <= 0` na matrícula
- **Solução:** Verificar se a matrícula tem valor a cobrar

### Erro: "Cobrança já existe"
- **Causa:** Já existe uma cobrança ativa para esta matrícula
- **Solução:** Usar sincronização ou cancelar cobrança anterior

---

## Logs e Debug

### Habilitar Logs Detalhados

Adicionar temporariamente em `EfiPaymentService::getAccessToken()`:
```php
error_log("EFI Auth Request: URL={$url}, CLIENT_ID=" . substr($this->clientId, 0, 10) . "...");
error_log("EFI Auth Response: HTTP={$httpCode}, Response=" . substr($response, 0, 200));
```

### Verificar Logs
- **XAMPP Windows:** `C:\xampp\apache\logs\error.log`
- **PHP error_log:** Verificar `php.ini` para localização

---

## Checklist de Troubleshooting

- [ ] Arquivo `.env` existe na raiz do projeto
- [ ] `EFI_CLIENT_ID` está preenchido e correto
- [ ] `EFI_CLIENT_SECRET` está preenchido e correto
- [ ] `EFI_SANDBOX` está configurado corretamente (true/false)
- [ ] **`EFI_CERT_PATH` configurado (OBRIGATÓRIO em produção)**
- [ ] **Certificado (.p12) existe no caminho especificado**
- [ ] **Certificado é do ambiente correto (produção vs sandbox)**
- [ ] Servidor web foi reiniciado após alterar `.env`
- [ ] Conexão com internet está funcionando
- [ ] Firewall não está bloqueando requisições HTTPS
- [ ] Logs do servidor foram verificados para detalhes do erro
