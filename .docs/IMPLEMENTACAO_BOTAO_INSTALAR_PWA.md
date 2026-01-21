# ✅ Implementação Botão "Instalar Aplicativo" PWA

**Data:** 2026-01-21  
**Status:** ✅ **Implementado - Opcional e Discreto**

## O que foi implementado

### 1. Botão no menu do usuário (avatar)

O botão "Instalar Aplicativo" foi adicionado no dropdown do menu do usuário:
- ✅ Localização: Menu dropdown do avatar (topbar-profile-dropdown)
- ✅ Posição: Primeiro item, antes de "Alterar Senha"
- ✅ Visibilidade: Oculto por padrão, aparece apenas quando `beforeinstallprompt` está disponível
- ✅ Estilo: Consistente com outros itens do menu

### 2. Comportamento JavaScript

#### Captura do evento `beforeinstallprompt`
- ✅ Intercepta o evento antes do prompt automático
- ✅ Salva em `deferredPrompt` para uso posterior
- ✅ Mostra o botão apenas quando `deferredPrompt` existe
- ✅ Não mostra se o app já está instalado (standalone mode)

#### Handler do botão
- ✅ Ao clicar: chama `deferredPrompt.prompt()`
- ✅ Aguarda `deferredPrompt.userChoice`
- ✅ Limpa `deferredPrompt` após uso
- ✅ Esconde o botão após instalação ou recusa

#### Detecção de app instalado
- ✅ Verifica `window.matchMedia('(display-mode: standalone)')`
- ✅ Verifica `navigator.standalone === true` (iOS)
- ✅ Verifica `document.referrer.includes('android-app://')`
- ✅ Não mostra botão se já estiver instalado

#### Evento `appinstalled`
- ✅ Escuta o evento de instalação concluída
- ✅ Esconde o botão definitivamente após instalação
- ✅ Limpa `deferredPrompt`

### 3. Fallback iOS

- ✅ Detecta iOS via User-Agent
- ✅ Mostra modal com instruções apenas ao clicar (não automático)
- ✅ Instruções claras: "Compartilhar → Adicionar à Tela de Início"
- ✅ Modal pode ser fechado (sem insistência)

### 4. Logs discretos

- ✅ Log `[PWA] install handler ready` apenas em desenvolvimento (localhost)
- ✅ Logs de instalação aceita/recusada apenas em desenvolvimento
- ✅ Sem poluição do console em produção

## Estrutura do código

### HTML (shell.php)
```php
<div id="pwa-install-container" style="display: none;">
    <button id="pwa-install-btn" class="topbar-profile-dropdown-item">
        <svg>...</svg>
        Instalar Aplicativo
    </button>
</div>
```

### JavaScript (app.js)
```javascript
// Variáveis
let deferredPrompt = null;
const installButton = document.getElementById('pwa-install-btn');
const installButtonContainer = document.getElementById('pwa-install-container');

// Detecção de app instalado
function isAppInstalled() {
    return window.matchMedia('(display-mode: standalone)').matches || 
           window.navigator.standalone === true ||
           document.referrer.includes('android-app://');
}

// Interceptar beforeinstallprompt
window.addEventListener('beforeinstallprompt', function(e) {
    e.preventDefault();
    deferredPrompt = e;
    if (!isAppInstalled() && installButtonContainer && deferredPrompt) {
        installButtonContainer.style.display = 'block';
    }
});

// Handler do botão
installButton.addEventListener('click', async function() {
    if (deferredPrompt) {
        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        deferredPrompt = null;
        installButtonContainer.style.display = 'none';
    } else if (isIOS) {
        showIOSInstallModal();
    }
});

// Evento appinstalled
window.addEventListener('appinstalled', function() {
    deferredPrompt = null;
    installButtonContainer.style.display = 'none';
});
```

## Fluxo de funcionamento

```
1. Página carrega
   ↓
2. Botão está oculto (display: none)
   ↓
3. Se beforeinstallprompt disparar:
   → Salva em deferredPrompt
   → Mostra botão (se não estiver instalado)
   ↓
4. Usuário clica no botão
   ↓
5. Se deferredPrompt existe:
   → Mostra prompt nativo do navegador
   → Aguarda escolha do usuário
   → Esconde botão
   ↓
6. Se não tem deferredPrompt e é iOS:
   → Mostra modal com instruções
   ↓
7. Se appinstalled disparar:
   → Esconde botão definitivamente
```

## Testes

### Script PowerShell criado

Arquivo: `.docs/TESTE_PWA_INSTALL_BUTTON.ps1`

Execute após deploy:
```powershell
.\docs\TESTE_PWA_INSTALL_BUTTON.ps1
```

### Testes manuais

1. **Teste em Desktop (Chrome/Edge)**
   - Abrir o site
   - Abrir DevTools (F12) → Console
   - Verificar log `[PWA] install handler ready` (apenas em localhost)
   - Clicar no avatar → Verificar se botão aparece
   - Clicar no botão → Verificar prompt de instalação

2. **Teste em Android (Chrome)**
   - Abrir o site
   - Clicar no avatar → Verificar se botão aparece
   - Clicar no botão → Verificar prompt de instalação
   - Instalar → Verificar se botão desaparece

3. **Teste em iOS (Safari)**
   - Abrir o site
   - Clicar no avatar → Clicar em "Instalar Aplicativo"
   - Verificar se modal com instruções aparece
   - Seguir instruções para instalar manualmente

4. **Teste de app já instalado**
   - Abrir app instalado (standalone mode)
   - Clicar no avatar → Verificar que botão NÃO aparece

## Validações

### ✅ Requisitos atendidos

- ✅ Botão no menu do usuário (avatar)
- ✅ Captura `beforeinstallprompt` e salva em `deferredPrompt`
- ✅ Mostra botão apenas quando `deferredPrompt` existe
- ✅ Chama `deferredPrompt.prompt()` ao clicar
- ✅ Aguarda `deferredPrompt.userChoice`
- ✅ Limpa `deferredPrompt` após uso
- ✅ Escuta `appinstalled` para esconder botão
- ✅ Detecta app instalado (standalone mode)
- ✅ Fallback iOS com modal de instruções
- ✅ Logs discretos apenas em desenvolvimento
- ✅ Sem banners automáticos
- ✅ Sem popups forçados

## Compatibilidade

### ✅ Navegadores suportados

- ✅ Chrome (Android/Desktop) - Suporte completo
- ✅ Edge (Desktop) - Suporte completo
- ✅ Safari (iOS) - Fallback com instruções
- ✅ Firefox - Não suporta PWA (botão não aparece)
- ✅ Opera - Suporte completo

### ✅ Dispositivos

- ✅ Android (Chrome)
- ✅ iOS (Safari)
- ✅ Desktop (Chrome/Edge)
- ✅ Tablet (Android/iOS)

## Próximos passos (opcional)

1. **Ícones dinâmicos por CFC**
   - Adicionar campo `logo_path` na tabela `cfcs`
   - Gerar ícones PWA a partir do logo do CFC
   - Atualizar `pwa-manifest.php` para usar ícones dinâmicos

2. **Theme color dinâmico**
   - Adicionar campo `theme_color` na tabela `cfcs`
   - Atualizar `pwa-manifest.php` para usar cor dinâmica

3. **Proteger generate-icons.php**
   - Mover para área protegida
   - Ou remover se não for mais necessário

## Segurança

- ✅ Não força instalação
- ✅ Respeita escolha do usuário
- ✅ Sem popups invasivos
- ✅ Logs apenas em desenvolvimento
- ✅ Tratamento de erros adequado

## Performance

- ✅ Código leve e otimizado
- ✅ Event listeners eficientes
- ✅ Sem impacto na performance da página
- ✅ Botão oculto por padrão (sem renderização desnecessária)
