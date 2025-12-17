# 🔍 Diagnóstico PWA Flicker no Android - Sondas de Debug

## Objetivo
Descobrir a causa raiz do piscar do footer PWA no Android **sem alterar código de produção**, usando scripts de monitoramento no console do Chrome DevTools.

---

## 📋 Pré-requisitos

1. **Conectar Android ao PC via USB**
2. **Habilitar USB Debugging** no Android
3. **Abrir Chrome no PC**: `chrome://inspect`
4. **Selecionar o dispositivo** e clicar em "inspect" na aba do site
5. **Abrir Console** (F12 → Console)

---

## 🎯 Sondas de Diagnóstico

### **Sonda A — Verificar se o footer está sendo REMOVIDO/REINSERIDO no DOM**

**Cole no console:**
```javascript
(() => {
  const sel = '.pwa-install-footer-container, #pwa-install-footer, .pwa-install-footer';
  const log = (...a) => console.log('[PWA-FLICKER]', new Date().toISOString(), ...a);
  let addCount = 0, removeCount = 0;
  
  const obs = new MutationObserver((muts) => {
    for (const m of muts) {
      for (const n of m.addedNodes) {
        if (n.nodeType === 1 && (n.matches?.(sel) || n.querySelector?.(sel))) {
          addCount++;
          log('✅ ADDED', `#${addCount}`, n);
        }
      }
      for (const n of m.removedNodes) {
        if (n.nodeType === 1 && (n.matches?.(sel) || n.querySelector?.(sel))) {
          removeCount++;
          log('❌ REMOVED', `#${removeCount}`, n);
        }
      }
    }
  });
  
  obs.observe(document.documentElement, { childList: true, subtree: true });
  log('🔍 Observer ATIVO - Monitorando:', sel);
  log('📊 Contadores: ADDED=0, REMOVED=0');
  
  // Mostrar contadores a cada 3 segundos
  setInterval(() => {
    if (addCount > 0 || removeCount > 0) {
      log('📊 Contadores:', { ADDED: addCount, REMOVED: removeCount });
    }
  }, 3000);
})();
```

**Interpretação:**
- ✅ **Se aparecer `ADDED/REMOVED` repetindo** → Alguém está desmontando e montando o componente (ou recarregando parte do DOM)
- ✅ **Se não aparecer nada** → Não é add/remove; é style/class/viewport

---

### **Sonda B — Verificar se é "display/opacity/visibility" alternando**

**Cole no console:**
```javascript
(() => {
  const el = document.querySelector('.pwa-install-footer-container') || 
             document.querySelector('.pwa-install-footer') ||
             document.querySelector('#pwa-install-footer');
  
  if (!el) {
    console.warn('[PWA-STYLE] ⚠️ Footer não encontrado. Aguardando 2s...');
    setTimeout(() => {
      const el2 = document.querySelector('.pwa-install-footer-container') || 
                  document.querySelector('.pwa-install-footer');
      if (el2) {
        console.log('[PWA-STYLE] ✅ Footer encontrado agora, iniciando monitoramento');
        startMonitoring(el2);
      } else {
        console.error('[PWA-STYLE] ❌ Footer ainda não encontrado após 2s');
      }
    }, 2000);
    return;
  }
  
  const log = (...a) => console.log('[PWA-STYLE]', new Date().toISOString(), ...a);
  let changeCount = 0;
  let lastState = null;
  
  const checkState = () => {
    const cs = getComputedStyle(el);
    const state = {
      display: cs.display,
      opacity: cs.opacity,
      visibility: cs.visibility,
      transform: cs.transform,
      position: cs.position,
      zIndex: cs.zIndex
    };
    
    const stateStr = JSON.stringify(state);
    if (stateStr !== lastState) {
      changeCount++;
      lastState = stateStr;
      log(`🔄 MUDANÇA #${changeCount}`, state);
    }
  };
  
  const obs = new MutationObserver((muts) => {
    muts.forEach(m => {
      if (m.attributeName === 'style' || m.attributeName === 'class') {
        changeCount++;
        const cs = getComputedStyle(el);
        log(`📝 ATTR ${m.attributeName}`, {
          display: cs.display,
          opacity: cs.opacity,
          visibility: cs.visibility
        });
      }
    });
    checkState();
  });
  
  obs.observe(el, { 
    attributes: true, 
    attributeFilter: ['class', 'style', 'hidden'],
    attributeOldValue: true
  });
  
  // Verificar estado a cada 100ms
  const interval = setInterval(checkState, 100);
  
  log('🔍 Monitorando elemento:', el);
  log('📊 Mudanças detectadas: 0');
  
  // Parar após 30s (ou manualmente)
  setTimeout(() => {
    clearInterval(interval);
    log('⏹️ Monitoramento pausado após 30s');
  }, 30000);
})();
```

**Interpretação:**
- ✅ **Se disparar em loop** → Estado visual está sendo alternado (display/opacity/visibility)
- ✅ **Se não disparar** → Não é problema de CSS/estilo

---

### **Sonda C — Verificar se é loop de FOCUS/BLUR (teclado Android)**

**Cole no console:**
```javascript
(() => {
  const log = (...a) => console.log('[PWA-FOCUS]', new Date().toISOString(), ...a);
  let focusCount = 0, blurCount = 0;
  let lastEvent = null;
  let loopDetected = false;
  
  const handlers = {
    focusin: (e) => {
      const t = e.target;
      if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.isContentEditable)) {
        focusCount++;
        const info = {
          type: 'FOCUS IN',
          element: t.tagName,
          id: t.id || 'sem-id',
          name: t.name || 'sem-name',
          typeAttr: t.type || 'N/A'
        };
        log(`👁️ #${focusCount}`, info);
        
        // Detectar loop: se focus aconteceu < 500ms após blur
        if (lastEvent === 'blur' && Date.now() - lastBlurTime < 500) {
          if (!loopDetected) {
            loopDetected = true;
            console.warn('[PWA-FOCUS] ⚠️⚠️⚠️ LOOP DETECTADO: focus/blur em sequência rápida!');
          }
        }
        lastEvent = 'focus';
      }
    },
    focusout: (e) => {
      const t = e.target;
      if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.isContentEditable)) {
        blurCount++;
        lastBlurTime = Date.now();
        const info = {
          type: 'FOCUS OUT',
          element: t.tagName,
          id: t.id || 'sem-id',
          name: t.name || 'sem-name'
        };
        log(`👋 #${blurCount}`, info);
        
        // Detectar loop: se blur aconteceu < 500ms após focus
        if (lastEvent === 'focus' && Date.now() - lastFocusTime < 500) {
          if (!loopDetected) {
            loopDetected = true;
            console.warn('[PWA-FOCUS] ⚠️⚠️⚠️ LOOP DETECTADO: blur/focus em sequência rápida!');
          }
        }
        lastEvent = 'blur';
      }
    }
  };
  
  let lastFocusTime = 0;
  let lastBlurTime = 0;
  
  ['focusin', 'focusout'].forEach(evt => {
    document.addEventListener(evt, handlers[evt], true);
  });
  
  log('🔍 Focus logger ATIVO');
  log('📊 Contadores: FOCUS=0, BLUR=0');
  
  // Mostrar resumo a cada 3 segundos
  setInterval(() => {
    if (focusCount > 0 || blurCount > 0) {
      log('📊 Resumo:', { FOCUS: focusCount, BLUR: blurCount, LOOP: loopDetected ? 'SIM ⚠️' : 'não' });
    }
  }, 3000);
})();
```

**Interpretação:**
- ✅ **Se ficar logando focus/blur em loop** → Causa quase certa: alguma rotina está forçando foco (ou `blurActiveInput` está "brigando" com algo)
- ✅ **Se aparecer "LOOP DETECTADO"** → Confirmação de ping-pong entre focus/blur

---

### **Sonda D — Verificar se o viewport está mudando sem parar (teclado/URL bar)**

**Cole no console:**
```javascript
(() => {
  const vv = window.visualViewport;
  const log = (...a) => console.log('[PWA-VV]', new Date().toISOString(), ...a);
  
  if (!vv) {
    log('❌ visualViewport não disponível neste navegador');
    return;
  }
  
  let resizeCount = 0;
  let scrollCount = 0;
  let lastSize = { w: vv.width, h: vv.height };
  let lastOffset = vv.offsetTop;
  
  const fnResize = () => {
    resizeCount++;
    const newSize = { w: vv.width, h: vv.height };
    const changed = newSize.w !== lastSize.w || newSize.h !== lastSize.h;
    
    if (changed) {
      log(`📐 RESIZE #${resizeCount}`, {
        width: `${lastSize.w} → ${newSize.w}`,
        height: `${lastSize.h} → ${newSize.h}`,
        scale: vv.scale,
        offsetTop: vv.offsetTop
      });
      lastSize = newSize;
    }
  };
  
  const fnScroll = () => {
    scrollCount++;
    if (vv.offsetTop !== lastOffset) {
      log(`📜 SCROLL #${scrollCount}`, {
        offsetTop: `${lastOffset} → ${vv.offsetTop}`,
        scale: vv.scale
      });
      lastOffset = vv.offsetTop;
    }
  };
  
  vv.addEventListener('resize', fnResize);
  vv.addEventListener('scroll', fnScroll);
  
  log('🔍 VisualViewport monitor ATIVO');
  log('📊 Estado inicial:', {
    width: vv.width,
    height: vv.height,
    scale: vv.scale,
    offsetTop: vv.offsetTop
  });
  
  // Mostrar resumo a cada 3 segundos
  setInterval(() => {
    if (resizeCount > 0 || scrollCount > 0) {
      log('📊 Resumo:', { RESIZE: resizeCount, SCROLL: scrollCount });
    }
  }, 3000);
})();
```

**Interpretação:**
- ✅ **Se `resize/scroll` disparar constantemente** → Viewport está mudando (teclado abrindo/fechando, URL bar escondendo/mostrando)
- ✅ **Se disparar em loop** → Pode estar causando re-renders do footer

---

### **Sonda E — Verificar se tem RELOAD ou mudança de Service Worker**

**Cole no console:**
```javascript
(() => {
  const log = (...a) => console.log('[PWA-NAV]', new Date().toISOString(), ...a);
  let reloadCount = 0;
  let swChangeCount = 0;
  
  // Detectar reloads/navegação
  window.addEventListener('beforeunload', () => {
    reloadCount++;
    console.warn('[PWA-NAV] ⚠️⚠️⚠️ beforeunload FIRED #' + reloadCount);
  });
  
  // Detectar mudanças de página (SPA navigation)
  let lastUrl = window.location.href;
  setInterval(() => {
    if (window.location.href !== lastUrl) {
      reloadCount++;
      log('🔄 URL mudou:', lastUrl, '→', window.location.href);
      lastUrl = window.location.href;
    }
  }, 500);
  
  // Service Worker
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistration().then(r => {
      if (r) {
        log('✅ SW registrado:', {
          scope: r.scope,
          active: !!r.active,
          waiting: !!r.waiting,
          installing: !!r.installing
        });
        
        // Detectar atualizações
        r.addEventListener('updatefound', () => {
          swChangeCount++;
          console.warn('[PWA-SW] ⚠️ updatefound #' + swChangeCount);
        });
      } else {
        log('❌ Nenhum SW registrado');
      }
    });
    
    // Detectar mudança de controller
    navigator.serviceWorker.addEventListener('controllerchange', () => {
      swChangeCount++;
      console.warn('[PWA-SW] ⚠️⚠️⚠️ controllerchange #' + swChangeCount);
    });
    
    // Verificar controller periodicamente
    let lastController = navigator.serviceWorker.controller;
    setInterval(() => {
      const currentController = navigator.serviceWorker.controller;
      if (currentController !== lastController) {
        swChangeCount++;
        log('🔄 Controller mudou:', {
          anterior: lastController?.scriptURL || 'null',
          atual: currentController?.scriptURL || 'null'
        });
        lastController = currentController;
      }
    }, 1000);
  } else {
    log('❌ Service Worker não suportado');
  }
  
  log('🔍 Monitor de navegação/SW ATIVO');
  log('📊 Contadores: RELOAD=0, SW_CHANGE=0');
  
  // Mostrar resumo a cada 3 segundos
  setInterval(() => {
    if (reloadCount > 0 || swChangeCount > 0) {
      log('📊 Resumo:', { RELOAD: reloadCount, SW_CHANGE: swChangeCount });
    }
  }, 3000);
})();
```

**Interpretação:**
- ✅ **Se `beforeunload` aparecer** → Tem reload ou navegação inesperada
- ✅ **Se `controllerchange` aparecer** → SW está mudando, pode estar causando re-inicialização

---

## 🎯 Plano de Teste Recomendado

### **Passo 1: Executar Sondas A + C + E simultaneamente**
1. Cole a **Sonda A** no console
2. Cole a **Sonda C** no console  
3. Cole a **Sonda E** no console
4. Aguarde 15-30 segundos observando o console
5. **Capture um screenshot** do console mostrando os logs repetindo

### **Passo 2: Analisar resultados**

#### **Cenário 1: Sonda A mostra ADDED/REMOVED em loop**
**Causa:** Alguém está removendo e recriando o footer no DOM
**Solução:** 
- Verificar `MutationObserver` em `login.php` (linha 875-918)
- Verificar se `hide()` ou `render()` está sendo chamado em loop
- Garantir que o container nunca seja removido, apenas ocultado

#### **Cenário 2: Sonda B mostra mudanças de estilo em loop**
**Causa:** `updateInstallButton()` ou algum código está alternando display/opacity/visibility
**Solução:**
- Consolidar todas as atualizações de estilo em um único ponto
- Usar state machine para evitar alternâncias
- Debounce agressivo em `updateInstallButton()`

#### **Cenário 3: Sonda C mostra FOCUS/BLUR em loop**
**Causa:** Loop de foco no input + teclado (mais provável)
**Solução:**
- Remover qualquer `focus()` automático no mobile
- Desabilitar `IntersectionObserver` que chama `blurActiveInput()`
- Fazer `blur()` apenas em ações explícitas (clique no botão), não em observers contínuos

#### **Cenário 4: Sonda D mostra viewport resize constante**
**Causa:** `position: fixed` + teclado/URL bar causando reflows
**Solução:**
- Trocar `position: fixed` por `position: sticky` no mobile
- Usar `transform: translateZ(0)` para forçar GPU
- Debounce forte em handlers de viewport

#### **Cenário 5: Sonda E mostra RELOAD/SW change**
**Causa:** Service Worker ou código de segurança está recarregando a página
**Solução:**
- Comentar verificações de SW em `login.php` (linhas 226-289)
- Remover qualquer `window.location.reload()` automático
- Mostrar aviso ao usuário em vez de auto-reload

---

## 📊 Checklist de Diagnóstico

Após executar as sondas, preencha:

- [ ] **Sonda A:** ADDED/REMOVED apareceu? (SIM/NÃO)
- [ ] **Sonda B:** Mudanças de estilo em loop? (SIM/NÃO)
- [ ] **Sonda C:** FOCUS/BLUR em loop? (SIM/NÃO)
- [ ] **Sonda D:** Viewport resize constante? (SIM/NÃO)
- [ ] **Sonda E:** RELOAD/SW change detectado? (SIM/NÃO)

**Causa mais provável identificada:** _______________________

**Próximo passo:** Implementar solução correspondente ao cenário identificado

---

## 🔧 Soluções Definitivas (por causa identificada)

### **Se for ADDED/REMOVED em loop:**
```javascript
// Garantir que hide() nunca remova o DOM, apenas oculte
hide() {
  this.isInstalled = true;
  const footer = document.querySelector('.pwa-install-footer');
  if (footer) {
    footer.style.display = 'none';
    footer.style.visibility = 'hidden';
    // NUNCA fazer: footer.remove() ou container.innerHTML = ''
  }
}
```

### **Se for STYLE/CLASS alternando:**
```javascript
// State machine única
let footerState = 'idle'; // idle | rendering | updating | hidden

// Um único scheduler
let renderScheduled = false;
function scheduleRender() {
  if (renderScheduled) return;
  renderScheduled = true;
  requestAnimationFrame(() => {
    render();
    renderScheduled = false;
  });
}
```

### **Se for FOCUS/BLUR em loop:**
```javascript
// Remover autofocus no mobile
if (!isMobile) {
  document.getElementById('email').focus();
}

// Desabilitar IntersectionObserver que chama blur
// OU fazer blur apenas em clique explícito
setupMobileBlurProtection(footerBlock) {
  // REMOVER ou comentar o IntersectionObserver
  // Fazer blur apenas quando clicar no botão
  footerBlock.querySelector('.pwa-install-btn')?.addEventListener('click', () => {
    document.activeElement?.blur();
  });
}
```

### **Se for VISUALVIEWPORT resize constante:**
```css
/* Trocar fixed por sticky no mobile */
@media (max-width: 768px) {
  .pwa-install-footer-container {
    position: sticky; /* em vez de fixed */
    bottom: 0;
    /* ... */
  }
}
```

### **Se for RELOAD/SW:**
```javascript
// Comentar/remover verificações de SW em login.php
// NUNCA fazer auto-reload, apenas mostrar aviso
if (!navigator.serviceWorker.controller) {
  // Mostrar toast: "Por favor, recarregue a página"
  // NÃO fazer: window.location.reload();
}
```

---

## 📝 Notas Finais

- **Execute as sondas no Android real**, não em emulador (comportamento pode diferir)
- **Aguarde pelo menos 15-30 segundos** para capturar padrões
- **Capture screenshots** do console para análise posterior
- **Execute uma sonda por vez** se o console ficar muito poluído
- **Compartilhe os resultados** para implementação da solução definitiva

---

**Última atualização:** 2025-01-16
**Status:** Aguardando resultados das sondas
