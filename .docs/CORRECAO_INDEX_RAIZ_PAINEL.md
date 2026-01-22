# 🔧 Correção: Index.php da Raiz Redireciona Subdomínio Painel

## 🎯 Problema

O subdomínio `painel.cfcbomconselho.com.br` estava abrindo o `index.php` da raiz (página institucional/landing page) ao invés do sistema de login que está em `public_html/index.php`.

## ✅ Solução Implementada

### Detecção do Subdomínio no `index.php` da Raiz

**Arquivo:** `index.php` (raiz do projeto)

**Alteração:** Adicionada detecção do subdomínio `painel` no início do arquivo. Quando detectado, o arquivo redireciona para o `public_html/index.php` (sistema de login).

```php
// Verificar se está sendo acessado pelo subdomínio painel
// Se sim, redirecionar para o sistema de login
$host = $_SERVER['HTTP_HOST'] ?? '';
$isPainelSubdomain = strpos($host, 'painel.') === 0 || $host === 'painel.cfcbomconselho.com.br';

if ($isPainelSubdomain) {
    // Se o subdomínio painel estiver acessando a raiz, redirecionar para public_html/index.php
    $publicHtmlPath = __DIR__ . '/public_html/index.php';
    
    if (file_exists($publicHtmlPath)) {
        // Incluir o index.php do sistema de login
        require_once $publicHtmlPath;
        exit;
    } else {
        // Se não encontrar, redirecionar para /login
        header('Location: /login');
        exit;
    }
}
```

## 🔍 Como Funciona

1. **Detecção do Subdomínio:** O código verifica se o `HTTP_HOST` contém `painel.` ou é exatamente `painel.cfcbomconselho.com.br`

2. **Redirecionamento:** Se for o subdomínio `painel`, o código:
   - Verifica se existe `public_html/index.php`
   - Se existir, inclui esse arquivo (sistema de login)
   - Se não existir, redireciona para `/login`

3. **Página Normal:** Se não for o subdomínio `painel`, o código continua normalmente e carrega a página institucional

## 📋 Estrutura de Arquivos

```
/
├── index.php  ← Detecta subdomínio painel e redireciona
├── public_html/
│   └── index.php  ← Sistema de login (Router)
├── app/
├── assets/
└── ...
```

## ✅ Resultado Esperado

- ✅ `cfcbomconselho.com.br` → Página institucional (landing page)
- ✅ `painel.cfcbomconselho.com.br` → Sistema de login
- ✅ `painel.cfcbomconselho.com.br/login` → Sistema de login
- ✅ `painel.cfcbomconselho.com.br/dashboard` → Dashboard (se logado)

## 🚀 Deploy

**Commit:** `fix: redireciona subdomínio painel para sistema de login no index.php da raiz`

**Arquivos Alterados:**
- `index.php` (raiz)

## 🔄 Próximos Passos

1. **Testar no servidor:**
   - Acessar `painel.cfcbomconselho.com.br`
   - Deve mostrar a página de login (não a landing page)

2. **Verificar se funcionou:**
   - Limpar cache do navegador
   - Acessar `painel.cfcbomconselho.com.br`
   - Deve redirecionar para o sistema de login

3. **Se ainda não funcionar:**
   - Verificar configuração do subdomínio no painel da Hostinger
   - Verificar se o DocumentRoot está apontando para a raiz correta
   - Verificar permissões dos arquivos

## ⚠️ Nota Importante

Esta solução funciona mesmo que o subdomínio `painel` esteja apontando para a raiz do domínio principal. O código detecta o subdomínio e redireciona automaticamente para o sistema de login.
