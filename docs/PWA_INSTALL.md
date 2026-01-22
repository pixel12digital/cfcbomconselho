# 📱 PWA - Instalação e Configuração para Instrutor

**Sistema:** CFC Bom Conselho  
**Versão:** 1.0  
**Data:** 2025-01-27  
**Última Atualização:** 2025-01-27

---

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [O que foi Implementado](#o-que-foi-implementado)
3. [Como Testar](#como-testar)
4. [Instruções para Usuários](#instruções-para-usuários)
5. [Troubleshooting](#troubleshooting)
6. [Arquivos Modificados](#arquivos-modificados)
7. [Checklist de Validação](#checklist-de-validação)

---

## 🎯 Visão Geral

O sistema agora suporta instalação como Progressive Web App (PWA), permitindo que instrutores instalem o sistema diretamente no dispositivo (Android, iOS, Desktop) para acesso rápido e funcionalidades offline.

### Funcionalidades

- ✅ Instalação em 1 clique (Android/Desktop)
- ✅ Instruções claras para iOS (Safari)
- ✅ Ícone do app na tela inicial
- ✅ Abertura em modo standalone (sem barra do navegador)
- ✅ Funcionalidades offline básicas
- ✅ Botão discreto de instalação na tela de login
- ✅ Detecção automática de instalação disponível

---

## ✅ O que foi Implementado

### 1. Manifest.json Configurado

**Arquivo:** `pwa/manifest.json`

**Configurações:**
- ✅ Caminhos absolutos (`/pwa/...`) - não quebra em rotas diferentes
- ✅ `start_url` apontando para `/instrutor/dashboard.php`
- ✅ `scope` configurado para `/` (root) - cobre todo o site
- ✅ Nome e short_name: "CFC Instrutor"
- ✅ `display: standalone` - abre sem barra do navegador
- ✅ `theme_color` e `background_color` definidos
- ✅ Ícones com caminhos absolutos (192, 512, maskable)

### 2. Service Worker Configurado

**Arquivo:** `pwa/sw.js`

**Configurações:**
- ✅ Caminhos absolutos em APP_SHELL
- ✅ Rotas excluídas do cache (logout, login, APIs sensíveis)
- ✅ Página offline com caminho absoluto
- ✅ Estratégias de cache otimizadas

### 3. Script de Registro

**Arquivo:** `pwa/pwa-register.js`

**Funcionalidades:**
- ✅ Service Worker registrado com scope `/` (root)
- ✅ Caminho do SW como absoluto (`/pwa/sw.js`)
- ✅ Gerencia eventos `beforeinstallprompt` (Android/Desktop)
- ✅ Gerencia eventos `appinstalled` (quando instala)
- ✅ Sistema de escolhas do usuário (não incomodar repetidamente)
- ✅ Inicialização em páginas do instrutor e login

### 4. Páginas Atualizadas

#### Login (`login.php`)
- ✅ Manifest link no `<head>` com caminho absoluto
- ✅ Meta tags PWA (theme-color, apple-mobile-web-app)
- ✅ Apple Touch Icons
- ✅ Botão discreto de instalação (Android/Desktop)
- ✅ Instruções para iOS (Safari)
- ✅ Script de registro PWA

#### Dashboard Instrutor (`instrutor/dashboard.php`)
- ✅ Manifest link no `<head>` com caminho absoluto
- ✅ Meta tags PWA (theme-color, apple-mobile-web-app)
- ✅ Apple Touch Icons
- ✅ Script de registro PWA

#### Admin (`admin/index.php`)
- ✅ Manifest link no `<head>` com caminho absoluto (corrigido)
- ✅ Apple Touch Icons com caminhos absolutos (corrigido)
- ✅ Meta tags PWA

---

## 🧪 Como Testar

### Android/Chrome

1. **Abrir o sistema em produção:**
   - Acesse `https://cfcbomconselho.com.br/login.php?type=instrutor`
   - Ou acesse `https://cfcbomconselho.com.br/instrutor/dashboard.php` (se já logado)

2. **Verificar instalação automática:**
   - O Chrome deve mostrar um banner "Adicionar à tela inicial" automaticamente
   - Ou aparecer um ícone de instalação na barra de endereços

3. **Usar botão interno:**
   - Se aparecer o botão "Instalar App" na tela de login, clique nele
   - Siga as instruções do Chrome

4. **Validar instalação:**
   - O app deve aparecer na tela inicial com ícone do CFC
   - Ao abrir, deve abrir em modo standalone (sem barra do navegador)
   - Deve abrir na rota `/instrutor/dashboard.php`

### Desktop/Chrome/Edge

1. **Abrir o sistema:**
   - Acesse `https://cfcbomconselho.com.br/login.php?type=instrutor`

2. **Verificar ícone de instalação:**
   - Deve aparecer um ícone de instalação na barra de endereços (canto direito)
   - Ou um banner "Instalar app" no topo da página

3. **Instalar:**
   - Clique no ícone ou banner
   - Siga as instruções

4. **Validar instalação:**
   - O app deve abrir em uma janela standalone (sem barra do navegador)
   - Deve aparecer no menu Iniciar (Windows) ou Applications (Mac)

### iPhone/Safari

1. **Abrir o sistema:**
   - Acesse `https://cfcbomconselho.com.br/login.php?type=instrutor` no Safari

2. **Verificar instruções:**
   - Deve aparecer um card informativo com instruções
   - "Toque em Compartilhar e depois em Adicionar à Tela de Início"

3. **Instalar manualmente:**
   - Toque no botão "Compartilhar" (ícone de compartilhamento)
   - Role até encontrar "Adicionar à Tela de Início"
   - Toque e confirme

4. **Validar instalação:**
   - O app deve aparecer na tela inicial
   - Ao abrir, deve abrir em modo standalone
   - Deve abrir na rota correta

### Validação com Lighthouse

1. **Abrir DevTools:**
   - Chrome: F12 ou Ctrl+Shift+I
   - Vá para a aba "Lighthouse"

2. **Executar auditoria PWA:**
   - Selecione "Progressive Web App"
   - Clique em "Generate report"

3. **Verificar resultados:**
   - ✅ Manifest válido
   - ✅ Service Worker registrado
   - ✅ HTTPS
   - ✅ Ícones corretos (192, 512, maskable)
   - ✅ Instalável

---

## 📱 Instruções para Usuários

### Para Instrutores

#### Android

1. Abra o sistema no Chrome
2. Aguarde o banner "Adicionar à tela inicial" ou clique no botão "Instalar App"
3. Siga as instruções do Chrome
4. O app será instalado na tela inicial

#### iPhone/iPad

1. Abra o sistema no Safari
2. Toque no botão "Compartilhar" (ícone de compartilhamento na barra inferior)
3. Role até encontrar "Adicionar à Tela de Início"
4. Toque e confirme
5. O app será adicionado à tela inicial

#### Desktop (Windows/Mac)

1. Abra o sistema no Chrome ou Edge
2. Procure pelo ícone de instalação na barra de endereços (canto direito)
3. Clique no ícone e siga as instruções
4. O app será instalado e poderá ser aberto como um aplicativo

### Benefícios da Instalação

- ✅ Acesso rápido direto da tela inicial
- ✅ Funciona offline (funcionalidades básicas)
- ✅ Abre em modo app (sem barra do navegador)
- ✅ Notificações (em breve)
- ✅ Melhor desempenho

---

## 🔧 Troubleshooting

### Problema: Banner de instalação não aparece

**Soluções:**
1. Verificar se está em HTTPS (obrigatório para PWA)
2. Limpar cache do navegador (Ctrl+Shift+Del)
3. Verificar se o Service Worker está registrado (DevTools > Application > Service Workers)
4. Verificar se o manifest está acessível (DevTools > Application > Manifest)

### Problema: App instalado não abre corretamente

**Soluções:**
1. Verificar se `start_url` no manifest está correto (`/instrutor/dashboard.php`)
2. Verificar se o Service Worker está ativo
3. Desinstalar e reinstalar o app

### Problema: Ícone do app não aparece ou está incorreto

**Soluções:**
1. Verificar se os ícones existem em `/pwa/icons/`
2. Verificar se os caminhos no manifest estão corretos (absolutos)
3. Limpar cache do navegador e reinstalar

### Problema: iOS não mostra instruções

**Soluções:**
1. Verificar se está usando Safari (não Chrome/Firefox no iOS)
2. Verificar se as meta tags `apple-mobile-web-app-*` estão presentes
3. Verificar se os Apple Touch Icons estão configurados

### Problema: Service Worker não registra

**Soluções:**
1. Verificar console do navegador para erros
2. Verificar se o arquivo `/pwa/sw.js` existe e é acessível
3. Verificar se está em HTTPS
4. Verificar se o scope está correto (`/`)

---

## 📁 Arquivos Modificados

### Arquivos PWA (já existentes)

- `pwa/manifest.json` - Manifest do PWA
- `pwa/sw.js` - Service Worker
- `pwa/pwa-register.js` - Script de registro
- `pwa/offline.html` - Página offline
- `pwa/icons/` - Ícones do app

### Arquivos Modificados nesta Auditoria

1. **`instrutor/dashboard.php`**
   - ✅ Adicionado manifest link no `<head>`
   - ✅ Adicionadas meta tags PWA
   - ✅ Adicionados Apple Touch Icons
   - **Linhas modificadas:** 520-528

2. **`admin/index.php`**
   - ✅ Corrigidos caminhos relativos para absolutos
   - ✅ Manifest: `../pwa/manifest.json` → `/pwa/manifest.json`
   - ✅ Ícones: `../pwa/icons/...` → `/pwa/icons/...`
   - **Linhas modificadas:** 674, 680, 683-691

### Arquivos que Já Estavam Corretos

- ✅ `login.php` - Já tinha todas as tags PWA corretas
- ✅ `pwa/manifest.json` - Já estava com caminhos absolutos
- ✅ `pwa/sw.js` - Já estava configurado corretamente
- ✅ `pwa/pwa-register.js` - Já estava funcional

---

## ✅ Checklist de Validação

### Pré-requisitos

- [ ] Sistema em produção com HTTPS
- [ ] Todos os arquivos PWA acessíveis (sem 404)
- [ ] Service Worker registrado sem erros
- [ ] Manifest válido e acessível

### Funcionalidades

- [ ] Banner de instalação aparece (Android/Desktop)
- [ ] Botão "Instalar App" funciona (login)
- [ ] Instruções iOS aparecem (Safari)
- [ ] App instala corretamente
- [ ] App abre em modo standalone
- [ ] App abre na rota correta (`/instrutor/dashboard.php`)
- [ ] Ícone do app aparece na tela inicial
- [ ] Ícone mostra logo do CFC

### Validação Lighthouse

- [ ] Manifest válido
- [ ] Service Worker registrado
- [ ] HTTPS configurado
- [ ] Ícones corretos (192, 512, maskable)
- [ ] Score PWA > 90

### Compatibilidade

- [ ] Android/Chrome - Funciona
- [ ] iOS/Safari - Funciona (instalação manual)
- [ ] Desktop/Chrome - Funciona
- [ ] Desktop/Edge - Funciona

---

## 📝 Notas Importantes

### O que NÃO foi alterado

- ✅ Layout mobile do dashboard não foi modificado
- ✅ Funcionalidades existentes não foram alteradas
- ✅ Apenas correções pontuais foram aplicadas

### Limitações Conhecidas

- iOS requer instalação manual (limitação do Safari)
- Alguns navegadores podem não suportar PWA completamente
- Funcionalidades offline são limitadas (apenas recursos estáticos)

### Próximos Passos (Opcional)

- [ ] Adicionar notificações push
- [ ] Melhorar cache offline
- [ ] Adicionar sincronização em background
- [ ] Otimizar performance do Service Worker

---

## 🔗 Referências

- [MDN - Progressive Web Apps](https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps)
- [Web.dev - PWA Checklist](https://web.dev/pwa-checklist/)
- [Lighthouse PWA Audit](https://developers.google.com/web/tools/lighthouse)

---

## 📞 Suporte

Para problemas ou dúvidas sobre a instalação PWA:

- **Email:** suporte@cfc.com
- **Horário:** Segunda a Sexta, 8h às 18h
- **Documentação:** Ver `docs/AUDITORIA_PWA_COMPLETA.md` para detalhes técnicos

---

**Última atualização:** 2025-01-27  
**Versão do documento:** 1.0
