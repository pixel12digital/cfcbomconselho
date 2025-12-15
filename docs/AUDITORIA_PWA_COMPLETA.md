# 📋 Auditoria PWA Completa - Sistema CFC Bom Conselho

**Data:** 2025-01-27  
**Objetivo:** Garantir instalabilidade PWA para instrutor com "1 clique"  
**Status:** Em auditoria

---

## 1. ✅ Arquivos PWA Existentes

### ✅ Manifest
- **Arquivo:** `pwa/manifest.json`
- **Status:** ✅ Existe
- **Caminho:** `/pwa/manifest.json`
- **Verificações:**
  - ✅ Caminhos absolutos (`/pwa/...`) - CORRETO
  - ✅ `start_url`: `/instrutor/dashboard.php` - CORRETO
  - ✅ `scope`: `/` (root) - CORRETO
  - ✅ `display`: `standalone` - CORRETO
  - ✅ `theme_color` e `background_color` definidos - CORRETO
  - ✅ Ícones 192 e 512 presentes - CORRETO
  - ✅ Ícones maskable presentes - CORRETO

### ✅ Service Worker
- **Arquivo:** `pwa/sw.js`
- **Status:** ✅ Existe e funcional
- **Caminho:** `/pwa/sw.js`
- **Verificações:**
  - ✅ Registrado com scope `/` (root) - CORRETO
  - ✅ Caminhos absolutos no APP_SHELL - CORRETO
  - ✅ Rotas excluídas do cache configuradas - CORRETO
  - ✅ Página offline configurada - CORRETO

### ✅ Script de Registro
- **Arquivo:** `pwa/pwa-register.js`
- **Status:** ✅ Existe
- **Caminho:** `/pwa/pwa-register.js`
- **Verificações:**
  - ✅ Registra SW com scope `/` (root) - CORRETO
  - ✅ Caminho do SW como absoluto (`/pwa/sw.js`) - CORRETO
  - ✅ Gerencia eventos `beforeinstallprompt` - CORRETO
  - ✅ Gerencia eventos `appinstalled` - CORRETO

### ✅ Ícones
- **Diretório:** `pwa/icons/`
- **Status:** ✅ Existem
- **Arquivos encontrados:**
  - ✅ icon-192.png
  - ✅ icon-512.png
  - ✅ icon-192-maskable.png
  - ✅ icon-512-maskable.png
  - ✅ icon-72.png, icon-96.png, icon-128.png, icon-144.png, icon-152.png, icon-384.png
- **⚠️ Observação:** Não verificado visualmente se contêm logo do CFC (requer verificação manual)

### ✅ Página Offline
- **Arquivo:** `pwa/offline.html`
- **Status:** ✅ Existe e funcional
- **Caminho:** `/pwa/offline.html`

---

## 2. Onde o PWA está "Plugado"

### ✅ Login Principal (`login.php`)
- **Linha 177:** `<link rel="manifest" href="/pwa/manifest.json">` - ✅ CORRETO (caminho absoluto)
- **Linha 180-184:** Meta tags PWA - ✅ CORRETO
- **Linha 187-189:** Apple Touch Icons - ✅ CORRETO
- **Linha 692:** `<script src="/pwa/pwa-register.js"></script>` - ✅ CORRETO
- **Linha 695-837:** Botão de instalação PWA - ✅ CORRETO
- **Status:** ✅ PWA está referenciado corretamente

### ❌ Dashboard Instrutor (`instrutor/dashboard.php`)
- **Linha 520-528:** Head básico SEM tags PWA
- **Linha 3403:** `<script src="/pwa/pwa-register.js"></script>` - ✅ Script presente
- **Problemas:**
  - ❌ FALTA `<link rel="manifest">` no `<head>`
  - ❌ FALTA meta tags PWA (`theme-color`, `apple-mobile-web-app-*`)
  - ❌ FALTA Apple Touch Icons
- **Status:** ❌ PWA parcialmente implementado (falta tags no head)

### ⚠️ Admin (`admin/index.php`)
- **Linha 680:** `<link rel="manifest" href="../pwa/manifest.json">` - ⚠️ CAMINHO RELATIVO
- **Linha 683-686:** Apple Touch Icons - ⚠️ CAMINHOS RELATIVOS
- **Status:** ⚠️ PWA referenciado mas com caminhos relativos (pode quebrar)

---

## 3. Problemas Identificados

### 🔴 CRÍTICO: Dashboard Instrutor sem tags PWA no head
- **Arquivo:** `instrutor/dashboard.php`
- **Problema:** Head não contém manifest, meta tags PWA nem Apple Touch Icons
- **Impacto:** Navegador pode não detectar PWA corretamente na área do instrutor
- **Solução:** Adicionar todas as tags PWA no `<head>`

### 🟡 MÉDIO: Admin usando caminhos relativos
- **Arquivo:** `admin/index.php`
- **Problema:** Manifest e ícones usam caminhos relativos (`../pwa/...`)
- **Impacto:** Pode quebrar se acessado de rotas diferentes
- **Solução:** Converter para caminhos absolutos (`/pwa/...`)

### 🟢 BAIXO: Verificação visual dos ícones
- **Problema:** Não verificado se os ícones contêm logo do CFC
- **Impacto:** Ícones podem não representar a marca corretamente
- **Solução:** Verificação manual necessária (fora do escopo desta auditoria)

---

## 4. Checklist de Correções Necessárias

- [x] ✅ Manifest.json - CORRETO (caminhos absolutos, start_url, scope)
- [x] ✅ Service Worker - CORRETO (scope, caminhos absolutos)
- [x] ✅ Script de registro - CORRETO
- [x] ✅ Login.php - CORRETO (todas as tags presentes)
- [ ] ❌ **Dashboard Instrutor** - ADICIONAR tags PWA no head
- [ ] ⚠️ **Admin** - CORRIGIR caminhos relativos para absolutos
- [x] ✅ Botão de instalação - CORRETO (já existe no login.php)
- [x] ✅ Instruções iOS - CORRETO (já existe no login.php)

---

## 5. Próximos Passos

1. ✅ **Concluído:** Auditoria completa realizada
2. ⏳ **Pendente:** Adicionar tags PWA no dashboard do instrutor
3. ⏳ **Pendente:** Corrigir caminhos relativos no admin
4. ⏳ **Pendente:** Testar em produção (Android, iOS, Desktop)
5. ⏳ **Pendente:** Validar com Lighthouse PWA

---

## 6. Critérios de Aceite (Validação)

### Android/Chrome
- [ ] Navegador oferece "Instalar app" automaticamente
- [ ] Botão interno de instalação funciona
- [ ] App instalado abre em modo standalone
- [ ] Ícone do app aparece na tela inicial

### Desktop/Chrome/Edge
- [ ] Ícone de instalar aparece na barra de endereços
- [ ] Instalação funciona em modo standalone
- [ ] App abre sem barra do navegador

### iPhone/Safari
- [ ] Instruções "Adicionar à Tela de Início" aparecem
- [ ] Instalação manual funciona corretamente
- [ ] App abre em modo standalone após instalação

### Geral
- [ ] Nenhuma alteração visual no dashboard mobile
- [ ] App instalado abre na rota correta (`/instrutor/dashboard.php`)
- [ ] Ícone do app mostra logo do CFC

---

## 7. Arquivos que Serão Modificados

1. `instrutor/dashboard.php` - Adicionar tags PWA no head
2. `admin/index.php` - Corrigir caminhos relativos para absolutos

---

## 8. Notas de Implementação

- **Sem refatoração:** Apenas correções pontuais
- **Sem mudanças visuais:** Layout mobile do dashboard não será alterado
- **Foco:** Instalabilidade e experiência de instalação
- **Compatibilidade:** Manter compatibilidade com código existente
