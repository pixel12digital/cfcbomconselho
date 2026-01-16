# Checklist - Configuração Completa EFI

Use este checklist para garantir que a integração EFI está configurada corretamente.

---

## ✅ Pré-requisitos

- [ ] Conta ativa na EFI/Gerencianet
- [ ] Acesso à dashboard: https://dev.gerencianet.com.br/
- [ ] Credenciais de produção obtidas

---

## 📋 Configuração do .env

### 1. Credenciais Básicas

- [ ] `EFI_CLIENT_ID` preenchido
- [ ] `EFI_CLIENT_SECRET` preenchido
- [ ] `EFI_SANDBOX=false` (produção) ou `true` (sandbox)

### 2. Certificado (OBRIGATÓRIO em Produção)

- [ ] Certificado baixado da dashboard EFI
  - [ ] Ambiente: **Produção** (não Homologação)
  - [ ] Formato: `.p12`
- [ ] Certificado salvo em local seguro
  - [ ] Caminho: `C:\xampp\certificados\efi_producao.p12` (exemplo)
  - [ ] Arquivo existe no caminho especificado
- [ ] `EFI_CERT_PATH` configurado no `.env`
  - [ ] Caminho absoluto (não relativo)
  - [ ] Sem aspas no caminho
  - [ ] Windows: usar `C:\` (não `c:\`)

### 3. Webhook (Opcional mas Recomendado)

- [ ] `EFI_WEBHOOK_SECRET` configurado (se usar webhooks)

---

## 🔧 Verificação Técnica

### 1. Arquivo .env

- [ ] Arquivo existe em: `c:\xampp\htdocs\cfc-v.1\.env`
- [ ] Formato correto (sem espaços extras, sem aspas desnecessárias)
- [ ] Todas as variáveis preenchidas

### 2. Certificado

- [ ] Arquivo `.p12` existe
- [ ] Permissões corretas (se Linux)
- [ ] Caminho no `.env` corresponde ao arquivo real

### 3. Servidor

- [ ] Servidor web reiniciado após alterar `.env`
- [ ] PHP cURL habilitado
- [ ] Conexão com internet funcionando

---

## 🧪 Testes

### 1. Script de Teste Automático

Execute: `http://localhost/cfc-v.1/public_html/tools/test_efi_auth.php`

Verificar:
- [ ] ✅ Arquivo .env existe
- [ ] ✅ EFI_CLIENT_ID configurado
- [ ] ✅ EFI_CLIENT_SECRET configurado
- [ ] ✅ Ambiente configurado
- [ ] ✅ Certificado cliente (produção) - **PASSOU**
- [ ] ✅ Teste de autenticação - **PASSOU**

### 2. Teste de Geração de Cobrança

- [ ] Acessar matrícula com saldo devedor > 0
- [ ] Clicar em "Gerar Cobrança EFI"
- [ ] Verificar se não há erros
- [ ] Verificar se `gateway_charge_id` foi salvo
- [ ] Verificar se `gateway_payment_url` foi salvo

### 3. Teste de Sincronização

- [ ] Com cobrança gerada, clicar em "Sincronizar"
- [ ] Verificar se status foi atualizado
- [ ] Verificar se `gateway_last_status` foi atualizado

---

## 🐛 Troubleshooting

### Se o teste de autenticação falhar:

1. **Verificar logs:**
   - XAMPP: `C:\xampp\apache\logs\error.log`
   - Procurar por: "EFI Auth Error"

2. **Verificar certificado:**
   - Caminho está correto?
   - Arquivo existe?
   - É do ambiente correto (produção)?

3. **Verificar credenciais:**
   - Estão corretas?
   - Correspondem ao ambiente (sandbox/produção)?

4. **Verificar conectividade:**
   - Internet funcionando?
   - Firewall não bloqueando?

---

## 📚 Documentação de Referência

- **Certificado:** `.docs/CERTIFICADO_EFI_PRODUCAO.md`
- **Erros Comuns:** `.docs/ERROS_COMUNS_FINANCEIRO.md`
- **Configuração:** `.docs/CONFIGURACAO_ENV_EFI.md`
- **Correções:** `.docs/RESUMO_CORRECOES_EFI_AUTENTICACAO.md`

---

## ✅ Status Final

Após completar todos os itens acima:

- [ ] Todos os testes passando
- [ ] Geração de cobrança funcionando
- [ ] Sincronização funcionando
- [ ] Sistema pronto para produção

**Data de conclusão:** _______________

**Observações:** _______________
