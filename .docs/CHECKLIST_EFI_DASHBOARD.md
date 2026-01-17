# Checklist - Verificação na Dashboard EFI

**Problema:** HTTP 401 "Invalid or inactive credentials" mesmo com certificado e credenciais configurados.

**Causa:** Segundo a documentação oficial da EFI, este erro geralmente indica problemas na configuração da aplicação na dashboard.

---

## ✅ Checklist de Verificação na Dashboard EFI

Acesse: https://dev.gerencianet.com.br/ (ou https://dev.efipay.com.br/)

### 1. Verificar Aplicação Ativa

1. **Vá em:** API → Minhas Aplicações (ou Aplicações)
2. **Verifique:**
   - [ ] A aplicação está **ATIVA** (não inativa ou suspensa)
   - [ ] A aplicação é do ambiente **PRODUÇÃO** (não Homologação)
   - [ ] O `Client ID` e `Client Secret` que você está usando correspondem a esta aplicação

### 2. Verificar Escopos Habilitados

1. **Na mesma página da aplicação, verifique os Escopos:**
   - [ ] **Cobranças** está habilitado
   - [ ] **PIX** está habilitado (se usar PIX)
   - [ ] **Boletos** está habilitado (se usar boletos)
   - [ ] Outros escopos necessários estão habilitados

**⚠️ IMPORTANTE:** Se os escopos não estiverem habilitados, as credenciais serão consideradas inválidas mesmo que estejam corretas!

### 3. Verificar Certificado

1. **Vá em:** API → Meus Certificados
2. **Selecione:** Produção (não Homologação)
3. **Verifique:**
   - [ ] O certificado existe e está **ATIVO**
   - [ ] O certificado não está **expirado** ou **revogado**
   - [ ] O certificado corresponde à mesma **conta/aplicação** das credenciais
   - [ ] O certificado foi baixado do ambiente **PRODUÇÃO**

### 4. Verificar Correspondência entre Certificado e Credenciais

**CRÍTICO:** O certificado e as credenciais devem ser da **mesma aplicação**!

1. **Verifique:**
   - [ ] O `Client ID` e `Client Secret` são da mesma aplicação que gerou o certificado
   - [ ] Ambos são do ambiente **PRODUÇÃO** (não misturar produção com homologação)
   - [ ] Ambos pertencem à mesma conta EFI

### 5. Gerar Novas Credenciais (se necessário)

Se alguma das verificações acima falhar:

1. **Na dashboard EFI:**
   - Vá em: API → Credenciais → Produção
   - **Gere novas credenciais** se as atuais estiverem inativas
   - **Copie o novo `Client ID` e `Client Secret`**
   - **Atualize o `.env`** com as novas credenciais

2. **Ou gere um novo certificado:**
   - Vá em: API → Meus Certificados → Produção
   - **Gere um novo certificado** se o atual estiver expirado ou revogado
   - **Baixe o novo certificado `.p12`**
   - **Faça upload no servidor**, substituindo o antigo

---

## 🔍 Verificação Adicional: Teste com cURL

No servidor, você pode testar diretamente com cURL para verificar se o problema é do código ou da configuração:

```bash
# Teste básico (sem certificado - deve falhar em produção)
curl -X POST https://apis.gerencianet.com.br/oauth/token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Authorization: Basic $(echo -n 'Client_Id_xxx:Client_Secret_xxx' | base64)" \
  -d "grant_type=client_credentials"

# Teste com certificado (deve funcionar se tudo estiver correto)
curl -X POST https://apis.gerencianet.com.br/oauth/token \
  --cert /home/u502697186/domains/cfcbomconselho.com.br/public_html/painel/certificados/certificado.p12 \
  --cert-type P12 \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -H "Authorization: Basic $(echo -n 'Client_Id_xxx:Client_Secret_xxx' | base64)" \
  -d "grant_type=client_credentials"
```

**Substitua:**
- `Client_Id_xxx` pelo seu Client ID real
- `Client_Secret_xxx` pelo seu Client Secret real
- O caminho do certificado se for diferente

---

## 📋 Resumo das Causas Mais Comuns

Segundo a documentação oficial da EFI, o erro 401 "Invalid or inactive credentials" geralmente ocorre por:

1. ❌ **Escopos não habilitados** na aplicação
2. ❌ **Aplicação inativa** ou suspensa
3. ❌ **Certificado e credenciais não correspondem** (aplicações diferentes)
4. ❌ **Ambiente misturado** (certificado de produção com credenciais de homologação ou vice-versa)
5. ❌ **Certificado expirado ou revogado**
6. ❌ **Credenciais inativas** ou revogadas

---

## ✅ Próximos Passos

1. **Acesse a dashboard da EFI** e verifique todos os itens acima
2. **Habilite os escopos necessários** na aplicação
3. **Gere novas credenciais** se necessário
4. **Baixe um novo certificado** se o atual estiver com problema
5. **Atualize o `.env`** com as informações corretas
6. **Teste novamente**

---

**Referência:** Documentação oficial da EFI
- https://dev.efipay.com.br/docs/api-cobrancas/credenciais
- https://dev.efipay.com.br/docs/api-pix/credenciais
