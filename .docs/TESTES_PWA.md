# 🧪 Testes PWA Fase 1 - Guia de Validação

**Data:** 2024  
**Status:** Pronto para execução

---

## 📋 Checklist de Testes

### ✅ Teste 1: Instalabilidade

**Objetivo:** Verificar se o PWA pode ser instalado

**Pré-requisitos:**
- [ ] Ícones gerados (`public_html/icons/icon-192x192.png` e `icon-512x512.png`)
- [ ] Sistema acessível via browser
- [ ] Service Worker registrado (verificar console)

**Passos:**
1. Abrir sistema no Chrome/Edge: `http://localhost/cfc-v.1/public_html/`
2. Fazer login
3. Abrir DevTools (F12) → Console
4. Verificar mensagem: `[SW] Service Worker registrado com sucesso`
5. Verificar DevTools → Application → Service Workers
6. Deve mostrar SW ativo e escopo correto
7. Verificar se aparece ícone de instalação na barra de endereço
8. Clicar em "Instalar" ou acessar Menu → "Instalar CFC Sistema"
9. Verificar se app instala e abre em janela standalone

**Resultado Esperado:**
- ✅ Service Worker registrado sem erros
- ✅ PWA instalável (ícone aparece na barra)
- ✅ App instala com sucesso
- ✅ Abre em modo standalone (sem barra do navegador)
- ✅ Ícone aparece na tela inicial (mobile) ou área de trabalho (desktop)

**Evidências:**
- [ ] Screenshot do console mostrando SW registrado
- [ ] Screenshot do app instalado em modo standalone
- [ ] Screenshot do ícone na tela inicial/área de trabalho

---

### ✅ Teste 2: Offline Parcial (App Shell)

**Objetivo:** Verificar que CSS/JS carregam offline, mas HTML não

**Pré-requisitos:**
- [ ] PWA instalado
- [ ] Ter acessado `/dashboard` pelo menos uma vez (com internet)

**Passos:**
1. Abrir o app instalado (com internet)
2. Navegar para `/dashboard` (deve carregar normalmente)
3. Abrir DevTools (F12) → Network
4. Ativar "Offline" (checkbox no topo)
5. Recarregar a página (F5)

**Resultado Esperado:**
- ✅ CSS/JS carregam do cache (app-shell funciona)
- ✅ Layout aparece (topbar, sidebar)
- ❌ Conteúdo HTML não carrega (mostra erro ou página em branco)
- ❌ **NÃO** deve mostrar HTML cacheado do dashboard

**Verificação Adicional:**
- Abrir DevTools → Application → Cache Storage → `cfc-v1`
- Verificar que cache contém apenas:
  - ✅ `/assets/css/*`
  - ✅ `/assets/js/app.js`
  - ✅ `/icons/*`
  - ✅ `/manifest.json`
- Verificar que cache **NÃO** contém:
  - ❌ `/dashboard` (HTML)
  - ❌ `/alunos` (HTML)
  - ❌ Qualquer rota privada (HTML)

**Evidências:**
- [ ] Screenshot do app offline mostrando layout mas sem conteúdo
- [ ] Screenshot do Cache Storage mostrando apenas assets estáticos

---

### ✅ Teste 3: Segurança (Crítico)

**Objetivo:** Garantir que HTML autenticado nunca é cacheado

**Pré-requisitos:**
- [ ] PWA instalado
- [ ] Dois usuários de teste (Usuário A e Usuário B)

**Passos:**
1. Fazer login como **Usuário A**
2. Acessar `/dashboard` (deve mostrar dados do Usuário A)
3. Abrir DevTools → Application → Cache Storage → `cfc-v1`
4. Verificar que cache **NÃO** contém HTML de `/dashboard`
5. Fazer logout
6. Fazer login como **Usuário B**
7. Acessar `/dashboard` (deve mostrar dados do Usuário B)
8. Verificar novamente o Cache Storage
9. Verificar que cada usuário vê apenas seus próprios dados

**Verificação Manual no Console:**
```javascript
// Executar no console do navegador (F12)
caches.open('cfc-v1').then(cache => {
    cache.keys().then(keys => {
        console.log('Itens no cache:');
        keys.forEach(key => {
            console.log(key.url);
        });
        
        // Verificar se há HTML de rotas privadas
        const hasPrivateHTML = keys.some(key => {
            const url = key.url;
            return url.includes('/dashboard') || 
                   url.includes('/alunos') || 
                   url.includes('/agenda') ||
                   (url.includes('.html') || url.includes('.php')) &&
                   !url.includes('/assets/') &&
                   !url.includes('/icons/');
        });
        
        if (hasPrivateHTML) {
            console.error('❌ PROBLEMA: HTML de rotas privadas está no cache!');
        } else {
            console.log('✅ OK: Nenhum HTML de rota privada no cache');
        }
    });
});
```

**Resultado Esperado:**
- ✅ Cache Storage **NÃO** contém HTML de `/dashboard`
- ✅ Cache Storage **NÃO** contém HTML de qualquer rota privada
- ✅ Cache Storage contém apenas assets estáticos (CSS, JS, ícones)
- ✅ Cada usuário vê apenas seus próprios dados
- ✅ Não há vazamento de dados entre usuários

**Evidências:**
- [ ] Screenshot do Cache Storage mostrando apenas assets
- [ ] Screenshot do console mostrando verificação de segurança
- [ ] Screenshot do dashboard do Usuário A
- [ ] Screenshot do dashboard do Usuário B (dados diferentes)

---

### ✅ Teste 4: Atualização de Versão

**Objetivo:** Verificar que assets atualizam corretamente

**Pré-requisitos:**
- [ ] PWA instalado
- [ ] Ter acessado o sistema pelo menos uma vez

**Passos:**
1. Abrir DevTools → Network
2. Recarregar página (F5)
3. Verificar que assets CSS/JS incluem `?v=timestamp` na URL
4. Modificar um arquivo CSS (ex: adicionar comentário em `assets/css/tokens.css`)
5. Salvar arquivo
6. Recarregar página (F5)
7. Verificar no Network que o CSS tem novo timestamp
8. Verificar que nova versão do CSS é carregada
9. Verificar no Cache Storage que nova versão está no cache

**Resultado Esperado:**
- ✅ Assets incluem `?v=timestamp` na URL
- ✅ Ao modificar arquivo, timestamp muda
- ✅ Nova versão é carregada automaticamente
- ✅ Service worker atualiza cache automaticamente
- ✅ Não fica preso em versão antiga

**Evidências:**
- [ ] Screenshot do Network mostrando assets com `?v=timestamp`
- [ ] Screenshot após modificar arquivo mostrando novo timestamp
- [ ] Screenshot do Cache Storage mostrando nova versão

---

### ✅ Teste 5: Headers Cache-Control

**Objetivo:** Verificar que headers anti-cache estão sendo enviados

**Pré-requisitos:**
- [ ] Estar logado (sessão ativa)

**Passos:**
1. Abrir DevTools → Network
2. Acessar `/dashboard`
3. Clicar na requisição de `/dashboard` no Network
4. Verificar aba "Headers" → "Response Headers"
5. Verificar presença de:
   - `Cache-Control: no-store, no-cache, must-revalidate, max-age=0`
   - `Pragma: no-cache`
   - `Expires: 0`

**Resultado Esperado:**
- ✅ Headers anti-cache presentes em todas as rotas autenticadas
- ✅ Headers não aparecem em assets estáticos (CSS, JS)
- ✅ Headers não aparecem em rotas públicas (login)

**Evidências:**
- [ ] Screenshot dos headers de resposta de `/dashboard`
- [ ] Screenshot dos headers de resposta de um asset (CSS) - não deve ter Cache-Control

---

## 📊 Resultado dos Testes

### Status Geral
- [ ] ✅ Teste 1: Instalabilidade - **PASSOU / FALHOU**
- [ ] ✅ Teste 2: Offline Parcial - **PASSOU / FALHOU**
- [ ] ✅ Teste 3: Segurança - **PASSOU / FALHOU** (CRÍTICO)
- [ ] ✅ Teste 4: Atualização - **PASSOU / FALHOU**
- [ ] ✅ Teste 5: Headers - **PASSOU / FALHOU**

### Observações
_(Anotar qualquer problema encontrado ou comportamento inesperado)_

---

## 🐛 Problemas Encontrados

### Problema 1: [Título]
**Descrição:**  
**Severidade:** Crítico / Médio / Baixo  
**Solução:**  
**Status:** Resolvido / Pendente

---

## ✅ Aprovação Final

- [ ] Todos os testes passaram
- [ ] Segurança validada (HTML não cacheado)
- [ ] PWA instalável e funcional
- [ ] Pronto para deploy em produção (após configurar HTTPS)

**Aprovado por:** _______________  
**Data:** _______________

---

**Nota:** Se o Teste 3 (Segurança) falhar, **NÃO** fazer deploy até resolver o problema.
