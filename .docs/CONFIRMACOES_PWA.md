# ✅ Confirmações Técnicas - PWA Fase 1

**Data:** 2024  
**Status:** Aguardando confirmações do usuário

---

## 1. HTTPS EM PRODUÇÃO

### ❓ Pergunta
**Produção está em HTTPS?**

### 📋 Resposta Técnica

**Status Atual:**
- ✅ Sistema detecta HTTPS automaticamente via `base_url()` em `app/Bootstrap.php`
- ⚠️ **Não há confirmação de ambiente de produção configurado**

**Análise do Código:**
```php
// app/Bootstrap.php linha 33
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
```

**Recomendações:**
1. **Se produção NÃO está em HTTPS:**
   - ⚠️ **PWA não funcionará em produção** (exceto localhost)
   - Necessário configurar SSL (Let's Encrypt, Cloudflare, ou certificado do host)
   - Impacto: PWA só funcionará em desenvolvimento local até configurar HTTPS

2. **Se produção JÁ está em HTTPS:**
   - ✅ PWA funcionará normalmente
   - Verificar se certificado é válido e não expirou

**Ação Necessária:**
- [ ] Confirmar domínio/ambiente de produção
- [ ] Verificar se SSL está configurado
- [ ] Se não estiver, indicar plano (Cloudflare/Let's Encrypt/Host)

---

## 2. VERSIONAMENTO DE ASSETS

### ❓ Pergunta
**Como será o versionamento de assets? (obrigatório antes do SW)**

### ✅ Resposta: Opção (A) - filemtime() automático

**Escolha:** **(A) ?v= automático via filemtime() por arquivo**

**Justificativa:**
- ✅ Automático: não precisa lembrar de atualizar versão manualmente
- ✅ Preciso: cada arquivo tem seu próprio timestamp
- ✅ Simples: uma linha por asset no layout
- ✅ Funciona em qualquer ambiente (dev/prod)

**Implementação:**
- Modificar função `asset_url()` em `app/Bootstrap.php` para aceitar parâmetro opcional de versionamento
- OU criar nova função `asset_url_versioned()` 
- OU adicionar versionamento diretamente no `shell.php` (mais simples, menos invasivo)

**Localização:**
- Função: `app/Bootstrap.php` (linha 40-44) - `asset_url()`
- Uso: `app/Views/layouts/shell.php` (linhas 11-14, 161)

**Código Proposto:**
```php
// Opção 1: Modificar asset_url() para aceitar versionamento opcional
function asset_url($path, $versioned = true) {
    $url = base_path('assets/' . ltrim($path, '/'));
    if ($versioned) {
        $filePath = ROOT_PATH . '/assets/' . ltrim($path, '/');
        if (file_exists($filePath)) {
            $url .= '?v=' . filemtime($filePath);
        }
    }
    return $url;
}

// Opção 2: Adicionar diretamente no shell.php (mais simples)
// <?= asset_url('css/tokens.css') . '?v=' . filemtime(ROOT_PATH . '/assets/css/tokens.css') ?>
```

**Recomendação:** Opção 1 (modificar helper) para manter consistência.

**Garantia de não quebrar cache:**
- ✅ Cada arquivo tem timestamp único
- ✅ Ao alterar arquivo, timestamp muda automaticamente
- ✅ Browser busca nova versão automaticamente
- ✅ Service worker cacheia nova versão com novo timestamp

---

## 3. BASE_URL E START_URL

### ❓ Pergunta
**Qual é o base_url() real em produção e o start_url correto?**

### 📋 Resposta Técnica

**Base Path Atual:**
```php
// app/Bootstrap.php linha 20
$base = '/cfc-v.1/public_html';
```

**Base URL Atual:**
```php
// app/Bootstrap.php linha 35
$base = $protocol . '://' . $host . '/cfc-v.1/public_html';
```

**Análise:**
- ⚠️ **Path hardcoded com `/cfc-v.1/public_html`** - isso é para desenvolvimento local
- ⚠️ Em produção, provavelmente será apenas `/` ou outro path

**Start URL Proposto:**
- ✅ `/dashboard` está correto
- ✅ Redireciona para `/login` quando sem sessão (via `AuthMiddleware`)

**Fluxo Confirmado:**
1. PWA abre com `start_url: "/dashboard"`
2. `AuthMiddleware` verifica `$_SESSION['user_id']`
3. Se vazio → redireciona para `/login`
4. Após login → redireciona para `/dashboard`

**Ação Necessária:**
- [ ] Confirmar se em produção o path será diferente
- [ ] Se sim, ajustar `base_path()` para detectar ambiente automaticamente
- [ ] OU usar variável de ambiente

**Sugestão de Melhoria (opcional):**
```php
// Detectar ambiente automaticamente
$base = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) 
    ? '/cfc-v.1/public_html' 
    : '';
```

**Para PWA:**
- `start_url: "/dashboard"` ✅ Correto
- `scope: "/"` ✅ Correto (todo o domínio)

---

## 4. ÍCONES PWA

### ❓ Pergunta
**Onde estão/como serão criados os ícones?**

### 📋 Resposta Técnica

**Status Atual:**
- ❌ **Nenhum ícone encontrado no projeto**
- ❌ Não há arquivos `.png`, `.ico` ou `.svg` (exceto SVGs inline)

**Solução Proposta:**
1. **Criar ícones mínimos programaticamente** (texto "CFC" em fundo azul)
2. **OU aguardar arte/logo do cliente**

**Implementação Mínima:**
- Criar `public_html/icons/icon-192x192.png` e `icon-512x512.png`
- Design: Fundo azul (#023A8D) com texto "CFC" branco centralizado
- Usar biblioteca PHP GD ou ImageMagick (se disponível)
- OU criar manualmente com ferramenta de design

**Alternativa Rápida:**
- Usar gerador online de ícones PWA
- Ou criar SVG simples e converter para PNG nos tamanhos necessários

**Ação Necessária:**
- [ ] Confirmar se existe logo/arte para gerar ícones
- [ ] Se não existir, implementar solução mínima (texto "CFC" em fundo azul)
- [ ] Criar diretório `public_html/icons/`

**Tamanhos Necessários:**
- ✅ `icon-192x192.png` (mínimo obrigatório)
- ✅ `icon-512x512.png` (mínimo obrigatório)
- ⚠️ Opcional: `icon-144x144.png`, `icon-96x96.png`, `apple-touch-icon.png`

---

## 5. CACHE-CONTROL PARA PÁGINAS AUTENTICADAS

### ❓ Pergunta
**Confirmar se já existe header "no-store/no-cache" nas rotas privadas.**

### 📋 Resposta Técnica

**Status Atual:**
- ❌ **NÃO existe Cache-Control "no-store/no-cache" para rotas privadas**
- ⚠️ Apenas 1 caso específico encontrado: `AlunosController::foto()` com `Cache-Control: private, max-age=3600`

**Análise:**
```php
// app/Controllers/AlunosController.php linha 1028
header('Cache-Control: private, max-age=3600');
// Isso é para foto de aluno, não para HTML de páginas
```

**Risco Identificado:**
- ⚠️ HTML de páginas autenticadas pode ser cacheado pelo browser
- ⚠️ Service worker pode cachear HTML indevidamente (mas vamos prevenir no SW)

**Solução Proposta:**
1. **Adicionar headers no `AuthMiddleware`** (melhor ponto central)
2. **OU adicionar no `Controller::view()`** (afeta todas as views autenticadas)

**Implementação Recomendada:**
```php
// app/Middlewares/AuthMiddleware.php
public function handle(): bool
{
    if (empty($_SESSION['user_id'])) {
        header('Location: /login');
        exit;
    }
    
    // Adicionar headers anti-cache para rotas autenticadas
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    return true;
}
```

**Vantagens:**
- ✅ Aplica automaticamente a todas as rotas com `AuthMiddleware`
- ✅ Não afeta assets estáticos (servidos diretamente pelo Apache)
- ✅ Não afeta rotas públicas (login, etc.)
- ✅ Centralizado e fácil de manter

**Garantia:**
- ✅ Assets estáticos continuam sendo servidos normalmente (via `.htaccess`)
- ✅ Apenas HTML de páginas autenticadas recebe headers anti-cache
- ✅ Service worker também não cacheará (via lógica no `sw.js`)

---

## RESUMO DAS DECISÕES

| Item | Decisão | Status |
|------|---------|--------|
| HTTPS Produção | ⚠️ Aguardando confirmação | Pendente |
| Versionamento | ✅ Opção A (filemtime automático) | Definido |
| Base URL | ⚠️ Confirmar path de produção | Pendente |
| Start URL | ✅ `/dashboard` (correto) | Definido |
| Ícones | ⚠️ Criar solução mínima se não existir | Pendente |
| Cache-Control | ✅ Adicionar no AuthMiddleware | Definido |

---

## PRÓXIMOS PASSOS

Após confirmações:
1. ✅ Implementar versionamento de assets
2. ✅ Adicionar Cache-Control no AuthMiddleware
3. ✅ Criar ícones PWA (mínimo)
4. ✅ Criar manifest.json
5. ✅ Criar sw.js
6. ✅ Modificar shell.php
7. ✅ Testar instalação e segurança

---

**Aguardando confirmações do usuário para prosseguir com implementação.**
