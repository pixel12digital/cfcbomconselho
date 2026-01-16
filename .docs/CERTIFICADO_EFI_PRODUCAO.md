# Certificado Cliente EFI - Produção

**Problema:** Erro "Connection was reset" ao autenticar com a API EFI em produção.

**Causa:** A EFI exige certificado cliente (.p12) para autenticação em produção (mutual TLS - mTLS).

---

## ✅ Solução: Obter e Configurar Certificado

### Passo 1: Obter Certificado na Dashboard EFI

1. **Acesse:** https://dev.gerencianet.com.br/ (ou dashboard da EFI)
2. **Faça login** na sua conta
3. **Navegue até:** API → Meus Certificados
4. **Selecione ambiente:** Produção (não Homologação)
5. **Crie novo certificado** (se não tiver) ou **baixe o existente**
6. **Download:** Você receberá um arquivo `.p12` (também chamado PFX)

### Passo 2: Salvar Certificado no Servidor

**Opção A: Salvar na raiz do projeto (não recomendado para produção)**
```
c:\xampp\htdocs\cfc-v.1\certificado_efi.p12
```

**Opção B: Salvar em diretório seguro (recomendado)**
```
c:\xampp\certificados\efi_producao.p12
```

**⚠️ Importante:**
- Mantenha o certificado seguro (não commitar no Git)
- Use permissões restritas (chmod 600 no Linux)
- Faça backup seguro do certificado

### Passo 3: Configurar no .env

Adicione o caminho absoluto do certificado no arquivo `.env`:

```env
# EFÍ (Gerencianet) - Gateway de Pagamento
EFI_CLIENT_ID=seu_client_id_aqui
EFI_CLIENT_SECRET=seu_client_secret_aqui
EFI_SANDBOX=false
EFI_CERT_PATH=C:\xampp\certificados\efi_producao.p12
EFI_WEBHOOK_SECRET=seu_webhook_secret_aqui
```

**Exemplo Windows:**
```env
EFI_CERT_PATH=C:\xampp\certificados\efi_producao.p12
```

**Exemplo Linux:**
```env
EFI_CERT_PATH=/var/www/certificados/efi_producao.p12
```

### Passo 4: Verificar Permissões (Linux)

Se estiver em Linux, garantir que o servidor web pode ler o certificado:

```bash
chmod 600 /var/www/certificados/efi_producao.p12
chown www-data:www-data /var/www/certificados/efi_producao.p12
```

### Passo 5: Testar

1. **Reinicie o servidor web** (Apache/XAMPP)
2. **Execute o script de teste:**
   ```
   http://localhost/cfc-v.1/public_html/tools/test_efi_auth.php
   ```
3. **Verifique:**
   - ✅ Certificado cliente (produção) - PASSOU
   - ✅ Teste de autenticação - PASSOU

---

## 🔍 Verificação

### Verificar se Certificado Está Configurado

O script de teste agora verifica automaticamente:
- Se `EFI_CERT_PATH` está no `.env`
- Se o arquivo existe no caminho especificado
- Se a autenticação funciona com o certificado

### Verificar Logs

Se ainda houver erro, verificar logs:
- **XAMPP:** `C:\xampp\apache\logs\error.log`
- Procurar por: "EFI Auth Error"

---

## ⚠️ Observações Importantes

### 1. Certificado é Obrigatório em Produção

- **Sandbox:** Geralmente não exige certificado
- **Produção:** **SEMPRE exige certificado cliente**

### 2. Um Certificado para Múltiplas Aplicações

- Você não precisa gerar um certificado por aplicação
- O mesmo certificado pode ser usado para todas as aplicações da sua conta

### 3. Segurança

- **NUNCA commitar** o certificado no Git (já está no `.gitignore`)
- **NUNCA compartilhar** o certificado por email/chat
- **Fazer backup seguro** do certificado (fora do repositório)

### 4. Senha do Certificado

Alguns certificados podem ter senha. Se necessário, adicionar suporte no código:

```php
// Em EfiPaymentService::getAccessToken()
if ($this->certPath && file_exists($this->certPath)) {
    curl_setopt($ch, CURLOPT_SSLCERT, $this->certPath);
    curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'P12');
    // Se tiver senha:
    // curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $_ENV['EFI_CERT_PASSWORD'] ?? '');
}
```

---

## 🛠️ Troubleshooting

### Erro: "Certificate file not found"

**Causa:** Caminho do certificado está incorreto ou arquivo não existe.

**Solução:**
1. Verificar se o caminho em `EFI_CERT_PATH` está correto
2. Verificar se o arquivo existe no caminho especificado
3. Usar caminho absoluto (não relativo)

### Erro: "Connection was reset" (mesmo com certificado)

**Causa:** Certificado inválido ou não corresponde às credenciais.

**Solução:**
1. Verificar se o certificado é do ambiente correto (produção)
2. Verificar se o certificado corresponde à conta que tem as credenciais
3. Gerar novo certificado na dashboard da EFI

### Erro: "SSL certificate problem"

**Causa:** Problema com o formato do certificado ou senha incorreta.

**Solução:**
1. Verificar se o arquivo é realmente `.p12`
2. Se necessário, converter para PEM:
   ```bash
   openssl pkcs12 -in certificado.p12 -out certificado.pem -nodes
   ```
3. Atualizar `EFI_CERT_PATH` para apontar para `.pem` e usar `CURLOPT_SSLCERTTYPE` como `PEM`

---

## 📋 Checklist

- [ ] Certificado baixado da dashboard EFI (ambiente Produção)
- [ ] Certificado salvo em local seguro no servidor
- [ ] `EFI_CERT_PATH` configurado no `.env` com caminho absoluto
- [ ] Arquivo existe no caminho especificado
- [ ] Permissões corretas (se Linux)
- [ ] Servidor web reiniciado após alterar `.env`
- [ ] Script de teste executado e autenticação passou

---

## 📚 Referências

- Documentação EFI: https://gerencianet.github.io/documentation/
- Dashboard EFI: https://dev.gerencianet.com.br/
- API Pagamentos: https://gerencianet.github.io/documentation/docs/apiPagamentos/Endpoints
