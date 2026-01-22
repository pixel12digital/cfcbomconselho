# 📋 Guia de Validação PWA - Sistema CFC Bom Conselho

## 🎯 Metas de Performance

### Lighthouse Scores
- **PWA**: ≥ 95/100
- **Acessibilidade**: ≥ 92/100
- **Performance**: ≥ 90/100
- **Best Practices**: ≥ 95/100

### Core Web Vitals
- **LCP (Largest Contentful Paint)**: ≤ 2.5s
- **CLS (Cumulative Layout Shift)**: ≤ 0.1
- **TBT (Total Blocking Time)**: ≤ 200ms

### PWA Específicas
- **App Shell Size**: ≤ 1.5MB
- **Cold Start**: ≤ 1.2s até header + skeleton
- **Offline Functionality**: 100% funcional

## 🧪 Roteiro de Testes

### 1. Testes de Instalação

#### Android Chrome
```bash
# 1. Acessar /admin/
# 2. Verificar botão "Install app" na barra de endereços
# 3. Clicar em "Install app"
# 4. Verificar se abre em modo standalone (sem barra do navegador)
# 5. Verificar se ícone aparece na tela inicial
```

#### iOS Safari
```bash
# 1. Acessar /admin/ no Safari
# 2. Tocar no botão de compartilhar
# 3. Selecionar "Add to Home Screen"
# 4. Verificar se ícone aparece na tela inicial
# 5. Tocar no ícone e verificar splash screen
```

### 2. Testes Offline

#### App Shell Offline
```bash
# 1. Acessar /admin/ (logado)
# 2. Abrir DevTools → Network
# 3. Marcar "Offline"
# 4. Recarregar página (F5)
# 5. Verificar se App Shell carrega
# 6. Verificar se página offline é exibida
# 7. Verificar botões "Tentar novamente" e "Ir ao Dashboard"
```

#### Erro Amigável Offline
```bash
# 1. Com página offline, tentar uma ação que depende de rede
# 2. Exemplo: buscar turma, criar aluno, etc.
# 3. Verificar se erro amigável é exibido
# 4. Verificar se não trava a interface
# 5. Verificar se "Tentar novamente" funciona quando online
```

### 3. Testes de Atualização

#### Banner de Nova Versão
```bash
# 1. Fazer bump de versão no sw.js (cfc-v1.0.1)
# 2. Recarregar página
# 3. Verificar se banner "Nova versão disponível" aparece
# 4. Clicar em "Atualizar"
# 5. Verificar se página recarrega com assets novos
# 6. Verificar se versão foi atualizada
```

### 4. Testes Mobile

#### Navegação Mobile
```bash
# 1. Redimensionar para mobile (≤ 768px)
# 2. Verificar se botão hambúrguer aparece
# 3. Clicar no hambúrguer
# 4. Verificar se drawer abre
# 5. Verificar se scroll do body é bloqueado
# 6. Clicar em item do menu
# 7. Verificar se drawer fecha
# 8. Verificar se foco volta ao botão hambúrguer
# 9. Testar ESC para fechar
# 10. Verificar se elementos flutuantes não interceptam toques
```

### 5. Testes de Segurança

#### Cache Seguro
```bash
# 1. Fazer logout
# 2. Fazer login novamente
# 3. Verificar se SW não serve HTML antigo
# 4. Verificar se rotas admin/auth ficam fora do cache
# 5. Verificar se dados sensíveis não são cacheados
```

#### Limpeza de Cache
```bash
# 1. Abrir DevTools → Application → Storage
# 2. Clicar em "Clear storage"
# 3. Recarregar página
# 4. Verificar se App Shell recompõe sem erros
# 5. Verificar se primeiro carregamento continua rápido
```

### 6. Testes de Acessibilidade

#### Foco e Navegação
```bash
# 1. Navegar apenas com TAB
# 2. Verificar se foco é visível em todos os controles
# 3. Verificar se ordem do foco é lógica
# 4. Abrir drawer mobile
# 5. Verificar se trap de foco está ativo
# 6. Verificar se foco não sai do drawer
```

#### Contraste e ARIA
```bash
# 1. Verificar contraste do header/ícones (≥ 4.5:1)
# 2. Verificar contraste do anel de foco (≥ 3:1)
# 3. Verificar aria-label no hambúrguer
# 4. Verificar aria-expanded no hambúrguer
# 5. Verificar landmarks (header, nav, main)
# 6. Verificar se prefers-reduced-motion é respeitado
```

#### Safe Areas iOS
```bash
# 1. Testar em dispositivo iOS com notch
# 2. Verificar se safe-areas são respeitadas
# 3. Verificar se conteúdo não fica atrás do notch
# 4. Verificar se botões não ficam inacessíveis
```

## 🔧 Ferramentas de Teste

### 1. Lighthouse
```bash
# Chrome DevTools → Lighthouse
# Executar audit PWA + A11y
# Verificar scores e recomendações
```

### 2. Performance Metrics
```bash
# Console: window.getPerformanceReport()
# Verificar LCP, CLS, TBT, App Shell size
```

### 3. Automated Tests
```bash
# Acessar /pwa/validation-test.html
# Executar "Executar Todos os Testes"
# Verificar relatório final
```

### 4. Service Worker
```bash
# DevTools → Application → Service Workers
# Verificar status: activated & running
# Verificar scope: /admin/
# Verificar update disponível
```

### 5. Cache Storage
```bash
# DevTools → Application → Cache Storage
# Verificar caches: cfc-cache-v1.0.0
# Verificar App Shell cacheado
# Verificar exclusões funcionando
```

## 📊 Checklist de Validação

### ✅ Instalação
- [ ] Android Chrome: "Install app" funciona
- [ ] iOS Safari: "Add to Home Screen" funciona
- [ ] Modo standalone abre corretamente
- [ ] Ícone aparece na tela inicial
- [ ] Splash screen funciona (iOS)

### ✅ Offline
- [ ] App Shell carrega offline
- [ ] Página offline é exibida
- [ ] Botões "Tentar novamente" e "Ir ao Dashboard" funcionam
- [ ] Erro amigável para ações que dependem de rede
- [ ] Interface não trava offline

### ✅ Atualização
- [ ] Banner de nova versão aparece
- [ ] SkipWaiting funciona
- [ ] Página recarrega com assets novos
- [ ] Versão é atualizada corretamente

### ✅ Mobile
- [ ] Botão hambúrguer aparece
- [ ] Drawer abre e fecha corretamente
- [ ] Scroll lock funciona
- [ ] Foco management funciona
- [ ] ESC fecha drawer
- [ ] Elementos flutuantes não interceptam

### ✅ Segurança
- [ ] Rotas admin/auth não são cacheadas
- [ ] Logout/login não serve HTML antigo
- [ ] Dados sensíveis não são cacheados
- [ ] App Shell recompõe após limpeza

### ✅ Acessibilidade
- [ ] Foco visível em todos os controles
- [ ] Ordem do foco é lógica
- [ ] Trap de foco funciona no drawer
- [ ] Contraste AA (≥ 4.5:1)
- [ ] ARIA labels e landmarks
- [ ] prefers-reduced-motion respeitado
- [ ] Safe areas iOS respeitadas

### ✅ Performance
- [ ] LCP ≤ 2.5s
- [ ] CLS ≤ 0.1
- [ ] TBT ≤ 200ms
- [ ] App Shell ≤ 1.5MB
- [ ] Cold start ≤ 1.2s

## 🚨 Problemas Comuns

### Manifest não carrega
- Verificar se manifest.json está acessível
- Verificar se está referenciado no HTML
- Verificar se não há erros de CORS

### Service Worker não registra
- Verificar se sw.js está acessível
- Verificar se scope está correto
- Verificar se não há erros de sintaxe

### Instalação não funciona
- Verificar se manifest tem campos obrigatórios
- Verificar se ícones existem
- Verificar se start_url está correto

### Offline não funciona
- Verificar se App Shell está cacheado
- Verificar se página offline existe
- Verificar se estratégias de cache estão corretas

### Performance ruim
- Verificar tamanho do App Shell
- Verificar se recursos não críticos são lazy-loaded
- Verificar se imagens estão otimizadas

## 📈 Próximos Passos

1. **Executar Lighthouse** e registrar scores
2. **Ajustar** o que faltar para bater as metas
3. **Fazer canary release** (cfc-v1.0.1-canary)
4. **Monitorar** instalação e atualização
5. **Promover** para v1.0.1
6. **Documentar** procedimento de rollback

## 🔄 Rollback

Se necessário fazer rollback:

1. **Reverter** Service Worker para versão anterior
2. **Atualizar** CACHE_VERSION
3. **Republicar** SW anterior
4. **Forçar** atualização nos clientes

```javascript
// Exemplo de rollback no sw.js
const CACHE_VERSION = 'cfc-v1.0.0'; // Voltar para versão anterior
```
