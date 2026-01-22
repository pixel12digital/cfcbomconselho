# 📱 PWA - Instalação e Configuração

**Sistema:** CFC Bom Conselho  
**Versão:** 1.0  
**Data:** 2025-01-27

---

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [O que foi Implementado](#o-que-foi-implementado)
3. [Como Testar](#como-testar)
4. [Instruções para Usuários](#instruções-para-usuários)
5. [Troubleshooting](#troubleshooting)
6. [Arquivos Modificados](#arquivos-modificados)

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

---

## ✅ O que foi Implementado

### 1. Manifest.json Corrigido

**Arquivo:** `pwa/manifest.json`

**Correções:**
- ✅ Caminhos absolutos (`/pwa/...` em vez de `../pwa/...`)
- ✅ `start_url` apontando para `/instrutor/dashboard.php`
- ✅ `scope` configurado para `/` (root) - cobre todo o site
- ✅ Nome e short_name atualizados para "CFC Instrutor"
- ✅ Ícones com caminhos absolutos

### 2. Service Worker Corrigido

**Arquivo:** `pwa/sw.js`

**Correções:**
- ✅ Caminhos absolutos em APP_SHELL
- ✅ Rotas excluídas do cache atualizadas
- ✅ Página offline com caminho absoluto

### 3. Script de Registro Atualizado

**Arquivo:** `pwa/pwa-register.js`

**Correções:**
- ✅ Service Worker registrado com scope `/` (root)
- ✅ Caminho do SW como absoluto (`/pwa/sw.js`)
- ✅ Inicialização em páginas do instrutor e login

### 4. Páginas Atualizadas

#### Login (`login.php`)
- ✅ Manifest link no `<head>`
- ✅ Meta tags PWA (theme-color, apple-mobile-web-app)
- ✅ Apple Touch Icons
- ✅ Botão discreto de instalação (Android/Desktop)
- ✅ Instruções para iOS (Safari)
- ✅ Script de registro PWA

#### Dashboard Instrutor (`instrutor/dashboard.php`)
- ✅ Manifest link no `<head>`
- ✅ Meta tags PWA
- ✅ Apple Touch Icons
- ✅ Script de registro PWA

---

## 🧪 Como Testar

### Pré-requisitos

1. **HTTPS obrigatório** (ou localhost para desenvolvimento)
2. Navegadores modernos:
   - Chrome/Edge (Android/Desktop) - ✅ Suporte completo
   - Safari (iOS) - ✅ Suporte com limitações
   - Firefox - ⚠️ Suporte parcial

### Teste 1: Android (Chrome)

1. Acesse `https://cfcbomconselho.com.br/login.php?type=admin`
2. Faça login como instrutor
3. **Resultado esperado:**
   - Banner "Instalar app" aparece automaticamente OU
   - Botão verde "Instalar App" aparece no formulário de login
4. Toque em "Instalar App"
5. Confirme a instalação
6. **Verificar:**
   - Ícone do app aparece na tela inicial
   - Ao abrir, o app abre em modo standalone (sem barra do navegador)
   - Abre diretamente no dashboard do instrutor

### Teste 2: Desktop (Chrome/Edge)

1. Acesse `https://cfcbomconselho.com.br/login.php?type=admin`
2. Faça login como instrutor
3. **Resultado esperado:**
   - Ícone de instalação aparece na barra de endereços (canto direito)
   - OU botão "Instalar App" no formulário
4. Clique no ícone de instalação ou no botão
5. Confirme a instalação
6. **Verificar:**
   - App abre em janela standalone
   - Sem barra de endereços do navegador
   - Abre no dashboard do instrutor

### Teste 3: iOS (Safari)

1. Acesse `https://cfcbomconselho.com.br/login.php?type=admin` no Safari do iPhone/iPad
2. Faça login como instrutor
3. **Resultado esperado:**
   - Card azul com instruções aparece abaixo do formulário
   - Texto: "Toque em Compartilhar 📤 e depois em Adicionar à Tela de Início"
4. Siga as instruções:
   - Toque no botão "Compartilhar" (ícone de caixa com seta)
   - Role até encontrar "Adicionar à Tela de Início"
   - Toque e confirme
5. **Verificar:**
   - Ícone do app aparece na tela inicial do iOS
   - Ao abrir, funciona como app nativo

### Teste 4: Validação Técnica (Lighthouse)

1. Abra Chrome DevTools (F12)
2. Vá para a aba "Lighthouse"
3. Selecione "Progressive Web App"
4. Clique em "Generate report"
5. **Resultado esperado:**
   - ✅ Manifest válido
   - ✅ Service Worker registrado
   - ✅ Ícones corretos (192x192 e 512x512)
   - ✅ HTTPS
   - ✅ Instalável

---

## 📱 Instruções para Usuários

### Para Instrutores (Android)

1. Abra o Chrome no seu celular
2. Acesse o sistema: `https://cfcbomconselho.com.br/login.php?type=admin`
3. Faça login normalmente
4. **Opção A:** Se aparecer um banner "Instalar app", toque em "Instalar"
5. **Opção B:** Se aparecer um botão verde "Instalar App" no formulário, toque nele
6. Confirme a instalação
7. Pronto! O app estará na sua tela inicial

### Para Instrutores (iPhone/iPad)

1. Abra o Safari no seu iPhone/iPad
2. Acesse o sistema: `https://cfcbomconselho.com.br/login.php?type=admin`
3. Faça login normalmente
4. Procure o card azul com instruções abaixo do formulário
5. Toque no botão **"Compartilhar"** 📤 (na barra inferior do Safari)
6. Role a lista e toque em **"Adicionar à Tela de Início"**
7. Confirme
8. Pronto! O app estará na sua tela inicial

### Para Instrutores (Desktop)

1. Abra Chrome ou Edge no computador
2. Acesse o sistema: `https://cfcbomconselho.com.br/login.php?type=admin`
3. Faça login normalmente
4. Procure o ícone de instalação na barra de endereços (canto direito) ou o botão "Instalar App"
5. Clique e confirme
6. O app abrirá em uma janela separada, sem barra do navegador

---

## 🔧 Troubleshooting

### Problema: Botão de instalação não aparece

**Possíveis causas:**
1. Já está instalado - verifique se o app já está na tela inicial
2. Navegador não suporta PWA (use Chrome/Edge/Safari)
3. Não está em HTTPS (PWA requer HTTPS)
4. Usuário já dispensou o prompt (aguarde 7 dias)

**Solução:**
- Verifique se está em HTTPS
- Use Chrome/Edge no Android/Desktop ou Safari no iOS
- Limpe o cache do navegador
- Tente em modo anônimo

### Problema: App não abre em modo standalone

**Possíveis causas:**
1. Manifest não está sendo carregado
2. Service Worker não está registrado

**Solução:**
1. Abra DevTools (F12)
2. Vá para "Application" > "Manifest"
3. Verifique se o manifest está carregado
4. Vá para "Application" > "Service Workers"
5. Verifique se o SW está registrado e ativo

### Problema: Ícone do app não aparece ou está errado

**Possíveis causas:**
1. Ícones não estão acessíveis (404)
2. Caminhos incorretos no manifest

**Solução:**
1. Verifique se os arquivos existem em `/pwa/icons/`
2. Teste acessando diretamente: `https://cfcbomconselho.com.br/pwa/icons/icon-192.png`
3. Verifique o Console do navegador para erros 404

### Problema: iOS não mostra instruções

**Possíveis causas:**
1. Usuário já dispensou (aguarde 7 dias)
2. JavaScript desabilitado

**Solução:**
- Verifique se JavaScript está habilitado
- Limpe localStorage: `localStorage.removeItem('pwa-install-ios-dismissed')`
- Recarregue a página

---

## 📁 Arquivos Modificados

### Arquivos Corrigidos

1. **`pwa/manifest.json`**
   - Caminhos absolutos
   - `start_url` para `/instrutor/dashboard.php`
   - `scope` para `/`
   - Nome atualizado

2. **`pwa/sw.js`**
   - Caminhos absolutos em APP_SHELL
   - Rotas excluídas atualizadas
   - Página offline com caminho absoluto

3. **`pwa/pwa-register.js`**
   - Scope do SW para `/`
   - Caminho absoluto do SW
   - Inicialização em mais páginas

4. **`login.php`**
   - Manifest e meta tags PWA
   - Apple Touch Icons
   - Botão de instalação
   - Instruções iOS
   - Script de registro

5. **`instrutor/dashboard.php`**
   - Manifest e meta tags PWA
   - Apple Touch Icons
   - Script de registro

### Arquivos Criados

1. **`AUDITORIA_PWA_CHECKLIST.md`**
   - Checklist completo da auditoria
   - Lista de problemas encontrados
   - Status de correções

2. **`PWA_INSTALL.md`** (este arquivo)
   - Documentação completa
   - Instruções de teste
   - Guia para usuários

---

## ✅ Checklist de Validação em Produção

Antes de considerar o PWA como "pronto", verifique:

- [ ] Manifest acessível: `https://cfcbomconselho.com.br/pwa/manifest.json`
- [ ] Service Worker acessível: `https://cfcbomconselho.com.br/pwa/sw.js`
- [ ] Ícones acessíveis (sem 404):
  - [ ] `/pwa/icons/icon-192.png`
  - [ ] `/pwa/icons/icon-512.png`
  - [ ] `/pwa/icons/icon-192-maskable.png`
  - [ ] `/pwa/icons/icon-512-maskable.png`
- [ ] Lighthouse PWA score >= 90
- [ ] Teste em Android (Chrome) - instalação funciona
- [ ] Teste em iOS (Safari) - instruções aparecem
- [ ] Teste em Desktop (Chrome/Edge) - instalação funciona
- [ ] App instalado abre em modo standalone
- [ ] App instalado abre no dashboard do instrutor

---

## 📞 Suporte

Em caso de problemas:

1. Verifique o Console do navegador (F12) para erros
2. Verifique a aba "Application" > "Manifest" no DevTools
3. Verifique a aba "Application" > "Service Workers"
4. Consulte este documento
5. Entre em contato com o suporte técnico

---

## 🎉 Próximos Passos (Opcional)

Melhorias futuras que podem ser implementadas:

- [ ] Notificações push
- [ ] Sincronização offline avançada
- [ ] Atualização automática do Service Worker
- [ ] Analytics de instalação
- [ ] A/B testing de prompts de instalação

---

**Última atualização:** 2025-01-27  
**Versão do PWA:** 1.0.0
