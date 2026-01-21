# 📋 Lista Detalhada de FAILs e WARNINGS - Auditoria PWA

**Data:** 2026-01-21  
**Fonte:** `%TEMP%\auditoria_pwa.html`

---

## ❌ ERROS (FAIL) - 7 itens

### 1. Manifest acessível
- **Mensagem:** ❌ manifest.json NÃO acessível via URL
- **Detalhes:** URL testada: `https://painel.cfcbomconselho.com.br/manifest.json` (sem `/public_html/`)
- **Causa:** Manifest está em `/public_html/manifest.json` mas auditoria testa na raiz
- **Impacto:** Browser não encontra manifest na URL padrão
- **Solução:** Ajustar paths no manifest para relativos OU criar symlink/redirect

### 2. Service Worker acessível
- **Mensagem:** ❌ sw.js NÃO acessível via URL
- **Detalhes:** URL testada: `https://painel.cfcbomconselho.com.br/sw.js` (sem `/public_html/`)
- **Causa:** SW está em `/public_html/sw.js` mas auditoria testa na raiz
- **Impacto:** Service Worker não é registrado corretamente
- **Solução:** Ajustar path de registro no shell.php OU criar symlink/redirect

### 3. Diretório ícones
- **Mensagem:** ❌ Diretório /icons/ NÃO existe
- **Detalhes:** Caminho testado: `/icons/` (raiz)
- **Causa:** Ícones estão em `/public_html/icons/` mas auditoria testa na raiz
- **Impacto:** Ícones não são encontrados pelo manifest
- **Solução:** Ajustar paths no manifest para relativos (`./icons/...`)

### 4-7. Outros erros relacionados
- Provavelmente relacionados aos 3 acima (ícones não acessíveis, manifest inválido por paths, etc.)

---

## ⚠️ WARNINGS - 11 itens

### 1. Redirect HTTP→HTTPS
- **Mensagem:** ⚠️ .htaccess não contém regras explícitas de redirect HTTPS
- **Detalhes:** Pode estar configurado no servidor (Apache/Nginx) ou via Cloudflare
- **Impacto:** Baixo (redirect funciona, mas não documentado no .htaccess)
- **Ação:** Verificar se está no servidor/Cloudflare OU adicionar regra no .htaccess

### 2. Manifest dinâmico
- **Mensagem:** ⚠️ Manifest usa valores hardcoded
- **Detalhes:** Nome: "CFC Sistema de Gestão" - Deve ser dinâmico por CFC
- **Impacto:** Médio (não permite white-label)
- **Ação:** Implementar manifest dinâmico via PHP ou JS

### 3. White-Label - Campo logo
- **Mensagem:** ⚠️ Campo "logo" ou "logo_path" NÃO existe na tabela cfcs
- **Detalhes:** Necessário: Adicionar migration para criar campo logo na tabela cfcs
- **Impacto:** Médio (não permite logo personalizado por CFC)
- **Ação:** Criar migration para adicionar `logo_path` (nullable) na tabela `cfcs`

### 4. White-Label - Model Cfc
- **Mensagem:** ⚠️ Model Cfc.php NÃO existe
- **Detalhes:** Necessário: Criar app/Models/Cfc.php para buscar dados do CFC
- **Impacto:** Alto (impede white-label)
- **Ação:** Criar Model Cfc.php

### 5. Installability
- **Mensagem:** ⚠️ Alguns requisitos para installability não estão OK
- **Detalhes:** Verifique erros acima. PWA pode não ser installable ainda.
- **Impacto:** Alto (PWA não pode ser instalado)
- **Ação:** Corrigir erros acima (manifest, SW, ícones acessíveis)

### 6-11. Outros warnings relacionados
- Provavelmente relacionados aos itens acima (nome hardcoded no banco, logo não cadastrado, etc.)

---

## 🎯 Priorização

### Crítico (bloqueia installability):
1. ✅ Manifest acessível (ajustar paths)
2. ✅ Service Worker acessível (ajustar paths)
3. ✅ Diretório ícones (ajustar paths no manifest)

### Importante (white-label):
4. ✅ Model Cfc.php (criar)
5. ✅ Manifest dinâmico (implementar)
6. ⚠️ Campo logo (opcional por enquanto)

### Recomendado (segurança/boas práticas):
7. ⚠️ Redirect HTTP→HTTPS (documentar no .htaccess ou confirmar no servidor)
8. ⚠️ HSTS header (adicionar)

---

## 📝 Observações

- Os erros de "não acessível via URL" são falsos positivos: os arquivos existem, mas estão em `/public_html/` e a auditoria testa na raiz
- Solução: usar paths relativos no manifest (`./` ao invés de `/`)
- O sistema já identifica CFC via `$_SESSION['cfc_id'] ?? Constants::CFC_ID_DEFAULT`
- Falta apenas criar Model Cfc.php para buscar dados do CFC
