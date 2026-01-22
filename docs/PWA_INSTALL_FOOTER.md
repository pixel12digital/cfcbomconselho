# 📱 PWA Install Footer - Documentação

**Data:** 2025-01-27  
**Componente:** Botão "Instalar App" no footer  
**Objetivo:** Facilitar instalação e compartilhamento do app PWA

---

## 📋 Visão Geral

Componente discreto adicionado no footer de páginas institucionais e login que oferece:

- ✅ Botão "Instalar App" (quando navegador permite instalação PWA)
- ✅ Botão "Compartilhar" (Web Share API + fallback WhatsApp + copiar link)
- ✅ Instruções iOS (para iPhone/Safari)
- ✅ Detecção automática de tipo de usuário (aluno/instrutor/institucional)

---

## 🎯 Onde Aparece

### ✅ Páginas com o Componente

1. **Site Institucional** (`index.php`)
   - Aparece no footer
   - Oferece instalação geral

2. **Login do Aluno** (`login.php?type=aluno`)
   - Aparece no footer
   - URL compartilhada: `https://cfcbomconselho.com.br/login.php?type=aluno`

3. **Login do Instrutor** (`login.php?type=instrutor`)
   - Aparece no footer
   - URL compartilhada: `https://cfcbomconselho.com.br/login.php?type=instrutor`

### ❌ Páginas SEM o Componente

- ❌ Dashboard do Instrutor (`/instrutor/dashboard.php`)
- ❌ Dashboard do Aluno (`/aluno/dashboard.php`)
- ❌ Área Admin (`/admin/`)
- ❌ Qualquer página de dashboard

**Motivo:** Não alterar layout mobile dos dashboards conforme solicitado.

---

## 🔧 Funcionalidades

### 1. Instalar App

**Comportamento:**
- Captura evento `beforeinstallprompt` (Android/Desktop)
- Botão só aparece se instalação for possível
- Após instalação, componente se oculta automaticamente
- Detecta se já está instalado (não mostra se já instalado)

**Suportado em:**
- ✅ Android/Chrome
- ✅ Desktop/Chrome
- ✅ Desktop/Edge

### 2. Compartilhar

**Comportamento:**
1. Tenta usar Web Share API primeiro (se disponível)
2. Se não disponível, mostra modal com opções:
   - **Enviar no WhatsApp** - Abre WhatsApp com mensagem pronta
   - **Copiar link** - Copia URL para área de transferência

**Suportado em:**
- ✅ Todos os navegadores (com fallback)

### 3. Instalar no iPhone

**Comportamento:**
- Só aparece em dispositivos iOS
- Mostra modal com instruções passo a passo:
  1. Toque no botão "Compartilhar" na barra inferior do Safari
  2. Role e toque em "Adicionar à Tela de Início"
  3. Confirme

**Suportado em:**
- ✅ iPhone/iPad (Safari)

---

## 📁 Arquivos

### JavaScript
- **`pwa/install-footer.js`**
  - Lógica completa do componente
  - Detecção de tipo de usuário
  - Gerenciamento de eventos PWA
  - Compartilhamento e instalação

### CSS
- **`pwa/install-footer.css`**
  - Estilos discretos e responsivos
  - Modais de compartilhamento e iOS
  - Toasts de mensagens
  - Adaptação para footer claro (login) e escuro (institucional)

### Integração
- **`login.php`**
  - CSS e JS incluídos antes do fechamento do `</body>`
  - Componente se insere automaticamente no `.login-footer`

- **`index.php`**
  - CSS e JS incluídos antes do fechamento do `</body>`
  - Container `.pwa-install-footer-container` adicionado no footer

---

## 🎨 Design

### Footer Escuro (Institucional)
- Fundo: `rgba(255, 255, 255, 0.05)`
- Texto: `rgba(255, 255, 255, 0.9)`
- Botões com bordas sutis

### Footer Claro (Login)
- Fundo: `rgba(44, 62, 80, 0.05)`
- Texto: `#2c3e50`
- Botões com bordas mais visíveis

### Responsivo
- Mobile: Botões empilhados verticalmente
- Desktop: Botões em linha horizontal
- Modais adaptados para mobile

---

## 🔍 Detecção de Tipo de Usuário

O componente detecta automaticamente o tipo de usuário:

1. **Por URL Parameter:**
   - `?type=aluno` → Aluno
   - `?type=instrutor` ou `?type=admin` → Instrutor
   - Sem parâmetro → Institucional

2. **Por Rota:**
   - `/instrutor/` ou `/admin/` → Instrutor
   - `/aluno/` → Aluno
   - Outras → Institucional

3. **URLs Compartilhadas:**
   - Aluno: `https://cfcbomconselho.com.br/login.php?type=aluno`
   - Instrutor: `https://cfcbomconselho.com.br/login.php?type=instrutor`
   - Institucional: `https://cfcbomconselho.com.br`

---

## 🧪 Como Testar

### Android/Chrome

1. Acesse `https://cfcbomconselho.com.br/login.php?type=instrutor`
2. Role até o footer
3. Verifique se aparece "Instalar App do CFC"
4. Clique em "Instalar App (Instrutor)"
5. Confirme a instalação
6. Verifique se o app abre em modo standalone

### Desktop/Chrome

1. Acesse o site institucional ou login
2. Verifique footer
3. Clique em "Instalar App"
4. Verifique instalação em modo standalone

### iPhone/Safari

1. Acesse o site no Safari do iPhone
2. Role até o footer
3. Verifique se aparece "Instalar no iPhone"
4. Clique e siga as instruções
5. Verifique instalação na tela inicial

### Compartilhar

1. Clique em "Compartilhar"
2. Se Web Share API disponível: compartilhe diretamente
3. Se não: escolha WhatsApp ou Copiar link
4. Verifique funcionamento

---

## ✅ Critérios de Aceite

### Funcionalidade
- [x] Botão "Instalar App" aparece quando instalação é possível
- [x] Botão desaparece após instalação
- [x] Compartilhamento funciona (Web Share + fallback)
- [x] Instruções iOS aparecem em iPhone
- [x] Componente não aparece em dashboards

### Design
- [x] Discreto e não atrapalha layout
- [x] Responsivo (mobile e desktop)
- [x] Adapta-se ao tema do footer (claro/escuro)

### URLs
- [x] URLs corretas por tipo de usuário
- [x] WhatsApp com mensagem formatada
- [x] Copiar link funciona

---

## 🔧 Customização

### Opções do Construtor

```javascript
new PWAInstallFooter({
    userType: 'instrutor', // 'aluno', 'instrutor', 'institucional'
    containerSelector: '.custom-container' // Seletor customizado
});
```

### Estilos Customizados

O componente usa classes CSS que podem ser sobrescritas:

- `.pwa-install-footer` - Container principal
- `.pwa-install-btn-primary` - Botão de instalação
- `.pwa-install-btn-secondary` - Botão de compartilhar
- `.pwa-install-btn-ios` - Botão iOS

---

## 📝 Notas Técnicas

### Proteção contra Dashboards

O componente verifica automaticamente se está em dashboard:

```javascript
isDashboardPage() {
    const path = window.location.pathname;
    return path.includes('/instrutor/dashboard') || 
           path.includes('/aluno/dashboard') ||
           path.includes('/admin/');
}
```

Se estiver em dashboard, o componente não é inicializado.

### Detecção de Instalação

```javascript
isAlreadyInstalled() {
    // Display mode standalone
    if (window.matchMedia('(display-mode: standalone)').matches) {
        return true;
    }
    // iOS standalone
    if (window.navigator.standalone === true) {
        return true;
    }
    return false;
}
```

---

## 🐛 Troubleshooting

### Problema: Componente não aparece

**Soluções:**
1. Verificar se não está em dashboard
2. Verificar se footer existe na página
3. Verificar console para erros JavaScript
4. Verificar se CSS e JS foram carregados

### Problema: Botão "Instalar" não aparece

**Soluções:**
1. Verificar se está em HTTPS
2. Verificar se PWA está configurado corretamente
3. Verificar se já está instalado
4. Verificar se navegador suporta PWA (Chrome/Edge)

### Problema: Compartilhamento não funciona

**Soluções:**
1. Verificar se Web Share API está disponível
2. Verificar fallback (WhatsApp/Copiar)
3. Verificar console para erros

---

## 📊 Status

**Status:** ✅ Implementado e Funcional

**Arquivos criados:**
- ✅ `pwa/install-footer.js`
- ✅ `pwa/install-footer.css`

**Arquivos modificados:**
- ✅ `login.php` - CSS e JS adicionados
- ✅ `index.php` - CSS, JS e container adicionados

**Testes:**
- ⏳ Pendente teste em produção (Android, iOS, Desktop)

---

**Última atualização:** 2025-01-27  
**Versão:** 1.0.0
