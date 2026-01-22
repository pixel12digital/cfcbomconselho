# 📋 Template Completo do .env para Produção

## ✅ Use este template completo para o seu .env na Hostinger

```env
# ============================================
# CONFIGURAÇÃO DO BANCO DE DADOS (PRODUÇÃO)
# ============================================
DB_HOST=auth-db803.hstgr.io
DB_PORT=3306
DB_NAME=u502697186_cfcv1
DB_USER=u502697186_cfcv1
DB_PASS=Los@ngo#081081

# ============================================
# EFÍ (GERENCIANET) - GATEWAY DE PAGAMENTO
# ============================================
# ⚠️ IMPORTANTE: Preencha com suas credenciais reais da EFÍ
EFI_CLIENT_ID=seu_client_id_producao_aqui
EFI_CLIENT_SECRET=seu_client_secret_producao_aqui
EFI_SANDBOX=false
EFI_CERT_PATH=/caminho/completo/para/certificado.p12
EFI_WEBHOOK_SECRET=seu_webhook_secret_aqui

# ============================================
# AMBIENTE
# ============================================
APP_ENV=production
```

---

## 📝 Variáveis da EFÍ - Como Preencher

### EFI_CLIENT_ID e EFI_CLIENT_SECRET
- **Onde obter:** Dashboard da EFÍ (Gerencianet) → Minha Conta → Credenciais
- **Exemplo:** `Client_Id_abc123def456` / `Client_Secret_xyz789`
- **⚠️ Obrigatório:** Sim

### EFI_SANDBOX
- **Produção:** `false`
- **Sandbox/Testes:** `true`
- **⚠️ Obrigatório:** Sim

### EFI_CERT_PATH
- **Caminho absoluto** do certificado `.p12` na Hostinger
- **Onde obter:** Dashboard EFÍ → API → Meus Certificados → Produção
- **⚠️ Importante:** Obrigatório em produção (pode ficar vazio apenas em sandbox)
- **Exemplo Hostinger:** `/home/usuario/certificados/efi_producao.p12`

### EFI_WEBHOOK_SECRET
- **Onde obter:** Dashboard EFÍ → Webhooks → Configurações → Secret
- **⚠️ Opcional:** Mas altamente recomendado para segurança
- Use para validar assinatura dos webhooks

---

## 🔄 Se você já tinha essas configurações antes

Se você tinha essas configurações e não sabe mais quais eram:
1. **Verifique no Dashboard da EFÍ** → Credenciais (salvo lá)
2. **Verifique em backups** do .env antigo (se tiver)
3. **Se não encontrar:** Gere novas credenciais na dashboard da EFÍ

---

## ✅ Checklist

- [ ] Banco de dados preenchido ✅ (já está no seu .env)
- [ ] `APP_ENV=production` adicionado
- [ ] `EFI_CLIENT_ID` preenchido
- [ ] `EFI_CLIENT_SECRET` preenchido
- [ ] `EFI_SANDBOX=false` configurado
- [ ] `EFI_CERT_PATH` configurado (caminho do certificado na Hostinger)
- [ ] `EFI_WEBHOOK_SECRET` configurado (opcional mas recomendado)
