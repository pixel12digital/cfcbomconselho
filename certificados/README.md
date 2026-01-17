# 📁 Diretório de Certificados - EFI (Gerencianet)

## ⚠️ Segurança

Este diretório contém certificados sensíveis e **NÃO** deve ser commitado no Git.

**Status de Proteção:**
- ✅ Arquivos `.p12`, `.pfx`, `.pem` são ignorados pelo `.gitignore`
- ✅ Todos os arquivos dentro de `certificados/` são ignorados (exceto este README e `.gitkeep`)

---

## 📋 Como Usar

### 1. Obter Certificado da EFI

1. Acesse: https://dev.gerencianet.com.br/
2. Faça login na sua conta
3. Vá em: **API → Meus Certificados → Produção**
4. Baixe o certificado `.p12`

### 2. Fazer Upload do Certificado

**Na Hostinger (Produção):**
- Faça upload do arquivo `.p12` para este diretório via File Manager
- Exemplo: `public_html/painel/certificados/efi_producao.p12`

**Local (Desenvolvimento):**
- Coloque o arquivo `.p12` neste diretório
- Exemplo: `c:\xampp\htdocs\cfc-v.1\certificados\efi_producao.p12`

### 3. Configurar no .env

Adicione o caminho absoluto no arquivo `.env`:

**Hostinger (Linux):**
```env
EFI_CERT_PATH=/home/usuario/public_html/painel/certificados/efi_producao.p12
```

**Local (Windows):**
```env
EFI_CERT_PATH=C:\xampp\htdocs\cfc-v.1\certificados\efi_producao.p12
```

---

## ✅ Checklist

- [ ] Certificado baixado da dashboard EFI (ambiente **Produção**)
- [ ] Certificado salvo neste diretório
- [ ] `EFI_CERT_PATH` configurado no `.env` com caminho absoluto
- [ ] Certificado **NÃO** será commitado (protegido pelo `.gitignore`)

---

## 🔒 Segurança

- ⚠️ **NUNCA commitar** certificados no Git (já protegido)
- ⚠️ **NUNCA compartilhar** certificados por email/chat
- ✅ **Fazer backup seguro** do certificado (fora do repositório)
- ✅ **Usar permissões restritas** (chmod 600 no Linux)

---

## 📝 Nome Sugerido do Arquivo

- `efi_producao.p12` (produção)
- `efi_sandbox.p12` (sandbox - geralmente não é necessário)
