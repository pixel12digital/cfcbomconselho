# Auditoria PWA - Checklist Completo

**Data:** 2025-01-27  
**Objetivo:** Garantir instalabilidade PWA para instrutor com "1 clique"

---

## 1. Arquivos PWA Existentes

### ✅ Manifest
- **Arquivo:** `pwa/manifest.json`
- **Status:** Existe
- **Problemas identificados:**
  - ❌ Caminhos relativos (`../pwa/...`) - podem quebrar em rotas diferentes
  - ❌ `start_url` aponta para `../admin/?source=pwa` - deveria ser para instrutor
  - ❌ `scope` está como `../pwa/` - deveria ser `/` (root)

### ✅ Service Worker
- **Arquivo:** `pwa/sw.js`
- **Status:** Existe e funcional
- **Problemas identificados:**
  - ❌ Registrado com scope `../pwa/` - deveria ser `/` (root)
  - ❌ Caminhos relativos no APP_SHELL

### ✅ Script de Registro
- **Arquivo:** `pwa/pwa-register.js`
- **Status:** Existe
- **Problemas identificados:**
  - ❌ Registra SW com scope `../pwa/` - deveria ser `/`
  - ❌ Caminho do SW é relativo

### ✅ Ícones
- **Diretório:** `pwa/icons/`
- **Status:** Existem (192, 512, maskable)
- **Arquivos encontrados:**
  - ✅ icon-192.png
  - ✅ icon-512.png
  - ✅ icon-192-maskable.png
  - ✅ icon-512-maskable.png
  - ✅ icon-72.png, icon-96.png, icon-128.png, icon-144.png, icon-152.png, icon-384.png
- **Problemas identificados:**
  - ⚠️ Não verificado se os ícones contêm o logo do CFC

### ✅ Página Offline
- **Arquivo:** `pwa/offline.html`
- **Status:** Existe e funcional

---

## 2. Onde o PWA está "Plugado"

### ✅ Admin (`admin/index.php`)
- **Linha 680:** `<link rel="manifest" href="../pwa/manifest.json">`
- **Linha 683:** Apple Touch Icons
- **Linha 2955:** `<script src="../pwa/pwa-register.js"></script>`
- **Status:** ✅ PWA está referenciado

### ❌ Login Principal (`login.php`)
- **Status:** ❌ NÃO tem referências ao PWA
- **Ação necessária:** Adicionar manifest, meta tags, apple-touch-icon

### ❌ Dashboard Instrutor (`instrutor/dashboard.php`)
- **Status:** ❌ NÃO tem referências ao PWA no `<head>`
- **Ação necessária:** Adicionar manifest, meta tags, apple-touch-icon, script de registro

### ❌ Dashboard Mobile Instrutor (`instrutor/dashboard-mobile.php`)
- **Status:** ❌ NÃO tem referências ao PWA no `<head>`
- **Ação necessária:** Adicionar manifest, meta tags, apple-touch-icon, script de registro

---

## 3. Problemas Críticos Identificados

### 🔴 CRÍTICO: Caminhos Relativos
- Manifest usa `../pwa/...` - quebra em rotas como `/instrutor/...`
- Service Worker registrado com scope relativo
- **Solução:** Usar caminhos absolutos começando com `/`

### 🔴 CRÍTICO: Start URL Incorreta
- `start_url` aponta para `/admin/` mas deveria apontar para área do instrutor
- **Solução:** Mudar para `/instrutor/dashboard.php` ou `/login.php?type=admin`

### 🔴 CRÍTICO: Scope Incorreto
- `scope` está como `../pwa/` - limita o PWA apenas à pasta pwa
- **Solução:** Mudar para `/` (root) para cobrir todo o site

### 🟡 MÉDIO: Páginas Sem PWA
- Login e dashboard do instrutor não têm referências ao PWA
- **Solução:** Adicionar tags necessárias no `<head>`

### 🟡 MÉDIO: Falta Botão de Instalação
- Não há botão discreto para instalação na tela de login
- **Solução:** Criar componente de instalação

### 🟢 BAIXO: Ícones
- Ícones existem mas não verificado se contêm logo do CFC
- **Solução:** Verificar e gerar novos se necessário

---

## 4. Checklist de Correções

- [ ] Corrigir `manifest.json` com caminhos absolutos
- [ ] Ajustar `start_url` para área do instrutor
- [ ] Corrigir `scope` para `/` (root)
- [ ] Corrigir registro do Service Worker com scope `/`
- [ ] Adicionar PWA em `login.php`
- [ ] Adicionar PWA em `instrutor/dashboard.php`
- [ ] Criar botão de instalação discreto
- [ ] Adicionar instruções iOS
- [ ] Verificar/gerar ícones com logo do CFC
- [ ] Testar em produção (Android, iOS, Desktop)

---

## 5. Próximos Passos

1. Corrigir manifest.json
2. Corrigir service worker e registro
3. Adicionar PWA nas páginas do instrutor
4. Criar componente de instalação
5. Testar e validar
