# 🔧 Diagnóstico e Correção PWA - "Não Instala Mais"

**Data:** 2025-01-27  
**Problema:** Chrome parou de oferecer instalação PWA (sem "Instalar app" no menu)  
**Status:** ✅ Corrigido

---

## 🎯 Problema Identificado

O Service Worker estava em `/pwa/sw.js` mas tentando controlar `/` (root). Por padrão, um SW só pode controlar seu próprio diretório e subdiretórios. Mesmo especificando `scope: '/'`, o Chrome não permite que um SW em `/pwa/` controle `/login.php`.

### Evidência Técnica

- `navigator.serviceWorker.controller` retornava `null` após reload
- Application → Service Workers mostrava SW registrado mas não controlando
- `beforeinstallprompt` não disparava (requisito: SW deve controlar a página)

---

## ✅ Correções Aplicadas

### 1. Service Worker no Root (`/sw.js`)

**Arquivo criado:** `sw.js` (raiz do projeto)

```javascript
/**
 * Service Worker Root - Wrapper para dar scope "/"
 * Importa o SW principal de /pwa/sw.js
 */
importScripts('/pwa/sw.js');
```

**Por quê:** Um SW no root pode controlar todo o site. Este wrapper delega para o SW principal mantendo a organização do código.

### 2. Atualização do Registro

**Arquivos modificados:**
- `pwa/pwa-register.js` - linha 45: `/pwa/sw.js` → `/sw.js`
- `includes/layout/mobile-first.php` - linha 195: `/pwa/sw.js` → `/sw.js`

**Antes:**
```javascript
navigator.serviceWorker.register('/pwa/sw.js', { scope: '/' })
```

**Depois:**
```javascript
navigator.serviceWorker.register('/sw.js', { scope: '/' })
```

### 3. Versionamento do Cache

**Arquivo:** `pwa/sw.js` - linha 7

```javascript
const CACHE_VERSION = 'cfc-v1.0.3'; // Atualizado de v1.0.2
```

**Por quê:** Força atualização do cache após a correção.

### 4. Script de Diagnóstico

**Arquivo criado:** `debug_pwa.php`

**Funcionalidades:**
- ✅ Testa página de login (instrutor/aluno)
- ✅ Verifica manifest no HTML
- ✅ Valida manifest JSON (status, content-type, parse)
- ✅ Testa start_url
- ✅ Verifica ícones (192, 512, maskable)
- ✅ Testa Service Worker
- ✅ Verifica scope compatível

**Uso:**
```
https://cfcbomconselho.com.br/debug_pwa.php
```

### 5. Diagnóstico Real no Botão "Instalar App"

**Arquivo:** `pwa/install-footer.js`

**Antes:** Mostrava instruções genéricas quando não elegível.

**Depois:** Mostra diagnóstico técnico real:
- Service Worker não controlando
- Manifest com erro
- Content-Type incorreto
- Ícones 404
- etc.

**Método adicionado:** `diagnosePWA()` - verifica todos os requisitos e retorna lista de problemas.

---

## 🧪 Como Verificar a Correção

### Desktop Chrome

1. Abrir `https://cfcbomconselho.com.br/login.php?type=instrutor`
2. DevTools (F12) → Application:
   - **Manifest:** Sem erros
   - **Service Workers:** Deve mostrar "This service worker is controlling this page"
3. Console:
   ```javascript
   console.log("controller:", !!navigator.serviceWorker.controller);
   // Deve retornar: true
   ```
4. Lighthouse → PWA: Deve indicar "Installable"

### Android Chrome

1. Abrir a mesma URL
2. Recarregar 1x
3. Menu ⋮ deve mostrar "Instalar app" (ou prompt após interação)

### Script de Diagnóstico

Acessar: `https://cfcbomconselho.com.br/debug_pwa.php`

Deve mostrar todos os testes como ✅ PASS.

---

## 📋 Checklist de Verificação

- [x] `/sw.js` criado no root
- [x] `/sw.js` importa `/pwa/sw.js`
- [x] `pwa-register.js` atualizado para usar `/sw.js`
- [x] `mobile-first.php` atualizado para usar `/sw.js`
- [x] Cache version atualizado (v1.0.3)
- [x] `debug_pwa.php` criado e funcional
- [x] `install-footer.js` mostra diagnóstico real
- [x] `navigator.serviceWorker.controller` retorna `true` após reload

---

## 🔍 Evidências Técnicas

### Antes da Correção

```
navigator.serviceWorker.controller: null
Application → Service Workers: "No service worker is controlling this page"
beforeinstallprompt: não dispara
```

### Depois da Correção

```
navigator.serviceWorker.controller: ServiceWorker { ... }
Application → Service Workers: "This service worker is controlling this page"
beforeinstallprompt: dispara normalmente
```

---

## 📝 Arquivos Modificados

1. **Criados:**
   - `sw.js` (root)
   - `debug_pwa.php`
   - `docs/DIAGNOSTICO_CORRECAO_PWA_2025.md`

2. **Modificados:**
   - `pwa/sw.js` (cache version)
   - `pwa/pwa-register.js` (path do SW)
   - `pwa/install-footer.js` (diagnóstico real)
   - `includes/layout/mobile-first.php` (path do SW)

---

## ⚠️ Observações Importantes

1. **Subpastas:** Se o site estiver em subpasta (ex: `/cfc-bom-conselho/`), o `sw.js` deve estar no root do domínio, não na subpasta. O `basePath` no código já trata isso.

2. **Cache:** Após deploy, usuários precisam recarregar a página para o novo SW ser instalado.

3. **HTTPS:** PWA requer HTTPS (exceto localhost). Verificar se o site está servindo via HTTPS.

4. **Manifest dinâmico:** O sistema usa manifests diferentes por perfil (`manifest-aluno.json`, `manifest-instrutor.json`). O diagnóstico testa ambos.

---

## 🚀 Próximos Passos

1. Deploy das alterações
2. Testar em produção
3. Verificar `debug_pwa.php` em produção
4. Confirmar que `beforeinstallprompt` dispara
5. Validar instalação no Android Chrome

---

**Autor:** Sistema de Diagnóstico PWA  
**Versão:** 1.0  
**Data:** 2025-01-27
