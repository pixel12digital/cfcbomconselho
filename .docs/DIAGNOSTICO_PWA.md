# 🔍 Diagnóstico PWA - Estado Atual do Projeto

**Data:** 2024  
**Objetivo:** Mapear arquitetura atual para implementação segura de PWA Fase 1

---

## 1. ARQUITETURA DE LAYOUT E CARREGAMENTO

### ✅ Layout Base Único

**Arquivo:** `app/Views/layouts/shell.php`

- **Status:** ✅ Existe layout único reaproveitado
- **Uso:** Todas as páginas autenticadas usam este layout via `Controller::view()`
- **Exceções:** Páginas de autenticação (`login`, `forgot-password`, `reset-password`, `ativar-conta`) não usam layout (incluem CSS diretamente)
- **Estrutura:**
  - Header (topbar) com logo, busca, notificações, seletor de papel, perfil
  - Sidebar (menu lateral) com navegação por perfil
  - Content area (área principal de conteúdo)
  - Footer não existe (não há footer no layout)

### ✅ Ponto Único de Inclusão CSS/JS

**Arquivo:** `app/Views/layouts/shell.php` (linhas 11-14 para CSS, 161 para JS)

**CSS Global (sempre carregado):**
- `assets/css/tokens.css` - Design tokens (cores, espaçamento, tipografia)
- `assets/css/components.css` - Componentes reutilizáveis
- `assets/css/layout.css` - Layout (topbar, sidebar, estrutura)
- `assets/css/utilities.css` - Utilitários

**JS Global (sempre carregado):**
- `assets/js/app.js` - JavaScript principal (sidebar toggle, role selector, profile dropdown)

**Sistema de Extensibilidade:**
- Variáveis `$additionalCSS` e `$additionalJS` disponíveis no layout (linhas 16-20 e 163-167)
- **Status:** ✅ Nenhum controller usa `additionalCSS` ou `additionalJS` atualmente
- **Conclusão:** Sistema limpo, sem scripts duplicados por página

### ✅ Estrutura de Páginas

**Padrão:** Rotas com Controller/View (MVC)

- **Router:** `app/Core/Router.php` - Sistema de rotas customizado
- **Entry Point:** `public_html/index.php` - Front controller
- **Rotas:** `app/routes/web.php` - Todas as rotas definidas aqui
- **Controllers:** `app/Controllers/*.php` - Lógica de negócio
- **Views:** `app/Views/*.php` - Templates PHP

**Não há páginas PHP diretas por URL** - tudo passa pelo router.

---

## 2. ROTAS E REWRITE

### ✅ Sistema de Rewrite

**Arquivo:** `public_html/.htaccess`

```apache
RewriteEngine On
# Permitir acesso direto a arquivos estáticos
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
# Redirecionar tudo para index.php
RewriteRule ^(.*)$ index.php [QSA,L]
```

**Status:** ✅ Rewrite configurado corretamente

### ✅ Rotas "Bonitas" Funcionam

**Exemplos de rotas:**
- `/dashboard` → `DashboardController::index()`
- `/agenda` → `AgendaController::index()`
- `/notificacoes` → `NotificationsController::index()`
- `/alunos/{id}` → `AlunosController::show($id)`

**Teste de Acesso Direto:**
- ✅ Rotas funcionam ao abrir diretamente no navegador
- ✅ Router normaliza URI removendo `/cfc-v.1/public_html` e `/index.php`
- ✅ Sistema de rotas usa regex para parâmetros dinâmicos

### ✅ Fallback 404

**Arquivo:** `app/Core/Router.php` (linhas 83-89)

```php
// 404
http_response_code(404);
if (file_exists(APP_PATH . '/Views/errors/404.php')) {
    include APP_PATH . '/Views/errors/404.php';
} else {
    echo "404 - Página não encontrada";
}
```

**Status:** ✅ 404 controlado pelo router  
**Nota:** Não existe `app/Views/errors/404.php` ainda, mas o sistema está preparado

---

## 3. REQUISITOS TÉCNICOS DO PWA (ESTADO ATUAL)

### ⚠️ HTTPS

**Status Atual:**
- **Local/Homolog:** HTTP (XAMPP padrão)
- **Produção:** Não verificado (assumir que precisa configurar)

**Função `base_url()` em `app/Bootstrap.php`:**
```php
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
```

**Conclusão:**
- ✅ Sistema detecta HTTPS automaticamente
- ⚠️ Para PWA em produção, HTTPS é obrigatório
- 💡 Para desenvolvimento local, pode usar `localhost` (não requer HTTPS)

### ❌ Manifest.json

**Status:** ❌ Não existe

**Onde criar:** `public_html/manifest.json` (acessível via URL)

### ❌ Service Worker

**Status:** ❌ Não existe registro de service worker

**Onde registrar:** `app/Views/layouts/shell.php` (antes do fechamento de `</body>`)

---

## 4. ASSETS E IDENTIDADE (MANIFEST)

### ❌ Ícones Existentes

**Status:** ❌ Nenhum ícone encontrado no projeto

**Arquivos verificados:**
- ❌ Nenhum `.png` encontrado
- ❌ Nenhum `.ico` encontrado
- ❌ Nenhum `.svg` encontrado (exceto SVGs inline no HTML)

**Ação Necessária:**
- Criar ícones PWA: 192x192 e 512x512 (mínimo)
- Sugestão: Criar a partir do logo "CFC Sistema" (atualmente apenas texto)

### ✅ Cores do Tema

**Arquivo:** `assets/css/tokens.css`

**Cores Principais:**
- `--color-primary: #023A8D` (azul escuro - header)
- `--color-primary-dark: #012766`
- `--color-primary-light: #034BA8`
- `--color-secondary: #F7931E` (laranja)

**Sugestão para Manifest:**
```json
{
  "theme_color": "#023A8D",
  "background_color": "#ffffff"
}
```

### 📋 Valores Sugeridos para Manifest

```json
{
  "name": "CFC Sistema de Gestão",
  "short_name": "CFC Sistema",
  "start_url": "/dashboard",
  "scope": "/",
  "display": "standalone",
  "theme_color": "#023A8D",
  "background_color": "#ffffff",
  "orientation": "portrait-primary"
}
```

**Justificativa:**
- `name`: Nome completo do sistema
- `short_name`: Nome curto para tela inicial
- `start_url`: `/dashboard` (página principal após login)
- `scope`: `/` (todo o domínio)
- `display`: `standalone` (experiência app-like)
- `theme_color`: Azul do header (#023A8D)
- `background_color`: Branco (fundo padrão)

---

## 5. ESTRATÉGIA DE CACHE (PWA FASE 1)

### ✅ Arquivos Core Identificados

**CSS Core (estáveis):**
- `assets/css/tokens.css` ✅
- `assets/css/components.css` ✅
- `assets/css/layout.css` ✅
- `assets/css/utilities.css` ✅

**JS Core (estável):**
- `assets/js/app.js` ✅

**HTML Shell:**
- Layout base (`app/Views/layouts/shell.php`) - **NÃO cachear** (dinâmico, autenticado)

**Ícones (quando criados):**
- `public_html/icons/icon-192x192.png`
- `public_html/icons/icon-512x512.png`

**Fontes:**
- ❌ Não há fontes customizadas (usa system fonts)

### ❌ Versionamento de Assets

**Status:** ❌ Não existe versionamento

**Verificação:**
- ❌ Nenhum `?v=` encontrado nas views
- ❌ Nenhum hash nos nomes de arquivos

**Risco:** Cache quebrado após deploy

**Sugestão de Estratégia Simples:**
1. **Opção 1 (Recomendada):** Query string com timestamp de build
   ```php
   asset_url('css/tokens.css') . '?v=' . filemtime(ROOT_PATH . '/assets/css/tokens.css')
   ```
2. **Opção 2:** Versão manual em constante
   ```php
   define('ASSETS_VERSION', '1.0.0');
   asset_url('css/tokens.css') . '?v=' . ASSETS_VERSION
   ```

### 📋 Estratégia Segura para Fase 1

**Cache-First (App Shell):**
- ✅ CSS core (tokens, components, layout, utilities)
- ✅ JS core (app.js)
- ✅ Ícones PWA
- ✅ Manifest.json

**Network-First (Dados Dinâmicos):**
- ❌ HTML de páginas (não cachear - autenticado)
- ❌ Endpoints API (`/api/*`)
- ❌ Imagens de uploads (`/storage/*`)

**Bypass (Nunca Cachear):**
- ❌ Rotas de autenticação (`/login`, `/logout`, etc.)
- ❌ Endpoints com dados sensíveis
- ❌ Service worker (`/sw.js`) - sempre buscar nova versão

**Implementação Sugerida:**
```javascript
// sw.js - Estratégia Fase 1
const CACHE_NAME = 'cfc-v1';
const CORE_ASSETS = [
  '/assets/css/tokens.css',
  '/assets/css/components.css',
  '/assets/css/layout.css',
  '/assets/css/utilities.css',
  '/assets/js/app.js',
  '/manifest.json',
  '/icons/icon-192x192.png',
  '/icons/icon-512x512.png'
];

// Install: Cache app shell
// Fetch: Cache-first para assets, network-first para HTML/API
```

---

## 6. AUTENTICAÇÃO E PÁGINAS PÚBLICAS

### ✅ Rotas Públicas vs Privadas

**Rotas Públicas (sem AuthMiddleware):**
- `/` → Login
- `/login` → Login
- `/logout` → Logout
- `/forgot-password` → Recuperar senha
- `/reset-password` → Redefinir senha
- `/ativar-conta` → Ativar conta

**Rotas Privadas (com AuthMiddleware):**
- Todas as demais rotas (dashboard, alunos, agenda, etc.)

**Middleware:** `app/Middlewares/AuthMiddleware.php`
```php
if (empty($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}
```

### ✅ Comportamento PWA Deslogado

**Fluxo Esperado:**
1. Usuário abre PWA instalado
2. `start_url: "/dashboard"` tenta carregar
3. `AuthMiddleware` detecta `$_SESSION['user_id']` vazio
4. Redireciona para `/login`
5. Após login, redireciona para `/dashboard`

**Status:** ✅ Sistema já funciona assim

### ⚠️ Risco de Cache de Página Autenticada

**Risco Identificado:**
- Service worker pode cachear HTML de `/dashboard` com dados de sessão
- Usuário B pode ver dados do usuário A se cachear HTML autenticado

**Proteção Necessária:**
1. **Nunca cachear HTML de rotas autenticadas**
   ```javascript
   // sw.js
   if (request.url.includes('/dashboard') || 
       request.url.includes('/alunos') ||
       // ... outras rotas privadas
   ) {
     return fetch(request); // Sempre network, sem cache
   }
   ```

2. **Headers no servidor (recomendado):**
   ```php
   // Em rotas autenticadas
   header('Cache-Control: no-store, no-cache, must-revalidate');
   header('Pragma: no-cache');
   ```

3. **Service worker ignora HTML dinâmico:**
   - Cache apenas assets estáticos (CSS, JS, ícones)
   - HTML sempre via network

---

## 7. SAÍDA ESPERADA - ARQUIVOS E LOCALIZAÇÕES

### 📁 Arquivos a Criar/Modificar

#### 1. Manifest.json
**Path:** `public_html/manifest.json`  
**Acesso:** `http://localhost/cfc-v.1/public_html/manifest.json`  
**Referência:** Adicionar `<link rel="manifest">` em `app/Views/layouts/shell.php` (dentro de `<head>`)

#### 2. Service Worker
**Path:** `public_html/sw.js`  
**Acesso:** `http://localhost/cfc-v.1/public_html/sw.js`  
**Registro:** Adicionar script em `app/Views/layouts/shell.php` (antes de `</body>`)

#### 3. Ícones PWA
**Path:** `public_html/icons/`  
**Arquivos necessários:**
- `icon-192x192.png`
- `icon-512x512.png`
- (Opcional: `icon-144x144.png`, `icon-96x96.png`, `apple-touch-icon.png`)

#### 4. Modificações no Layout
**Arquivo:** `app/Views/layouts/shell.php`  
**Mudanças:**
- Adicionar `<link rel="manifest">` no `<head>`
- Adicionar `<meta name="theme-color">` no `<head>`
- Adicionar registro do service worker antes de `</body>`
- Adicionar `<link rel="apple-touch-icon">` (opcional, para iOS)

---

## 8. CONFLITOS E RISCOS IDENTIFICADOS

### ✅ Sem Conflitos Críticos

**Scripts:**
- ✅ Apenas `app.js` global (sem duplicação)
- ✅ Sistema de `additionalJS` disponível mas não usado (sem risco)

**Layout:**
- ✅ Layout único (`shell.php`) usado consistentemente
- ✅ Sidebar toggle já consolidado (sem conflitos)

**Rotas:**
- ✅ Rewrite funcionando
- ✅ 404 controlado

### ⚠️ Atenções Necessárias

1. **Versionamento de Assets:**
   - Implementar antes do PWA para evitar cache quebrado

2. **Cache de HTML Autenticado:**
   - Service worker deve **NUNCA** cachear HTML de rotas privadas
   - Apenas assets estáticos (CSS, JS, ícones)

3. **HTTPS em Produção:**
   - PWA requer HTTPS (exceto localhost)
   - Verificar configuração de produção

4. **Ícones:**
   - Criar ícones 192x192 e 512x512 antes do deploy
   - Sugerir usar logo "CFC" como base

---

## 9. RESUMO EXECUTIVO

### ✅ Pontos Fortes

1. ✅ Arquitetura limpa: layout único, CSS/JS centralizados
2. ✅ Sistema de rotas funcionando com rewrite
3. ✅ Autenticação robusta com middleware
4. ✅ Sem scripts duplicados ou conflitos conhecidos
5. ✅ Estrutura preparada para extensão (additionalCSS/JS disponível)

### ⚠️ Ações Necessárias Antes do PWA

1. ⚠️ Criar ícones PWA (192x192, 512x512)
2. ⚠️ Implementar versionamento de assets (query string ou constante)
3. ⚠️ Verificar/configurar HTTPS em produção
4. ⚠️ Criar página 404 customizada (opcional, mas recomendado)

### 📋 Próximos Passos (PWA Fase 1)

1. ✅ Criar `manifest.json` com valores sugeridos
2. ✅ Criar `sw.js` com estratégia cache-first para assets, network-first para HTML
3. ✅ Adicionar referências no `shell.php`
4. ✅ Testar instalação e funcionamento offline do app-shell
5. ✅ Validar que HTML autenticado nunca é cacheado

---

## 10. CHECKLIST DE IMPLEMENTAÇÃO

### Pré-requisitos
- [ ] Criar ícones PWA (192x192, 512x512)
- [ ] Implementar versionamento de assets
- [ ] Verificar HTTPS em produção

### Implementação PWA
- [ ] Criar `public_html/manifest.json`
- [ ] Criar `public_html/sw.js`
- [ ] Criar diretório `public_html/icons/` e adicionar ícones
- [ ] Modificar `app/Views/layouts/shell.php`:
  - [ ] Adicionar `<link rel="manifest">`
  - [ ] Adicionar `<meta name="theme-color">`
  - [ ] Adicionar registro do service worker
- [ ] Testar instalação do PWA
- [ ] Testar funcionamento offline (app-shell)
- [ ] Validar que HTML não é cacheado

### Validação
- [ ] PWA instalável em Chrome/Edge
- [ ] PWA instalável em Firefox (Android)
- [ ] App-shell funciona offline
- [ ] HTML autenticado sempre busca da rede
- [ ] Assets estáticos são servidos do cache quando offline

---

**Status Geral:** ✅ Projeto pronto para implementação PWA Fase 1  
**Riscos:** ⚠️ Baixos (arquitetura limpa, sem conflitos conhecidos)  
**Complexidade:** 🟢 Baixa (estrutura bem organizada)
