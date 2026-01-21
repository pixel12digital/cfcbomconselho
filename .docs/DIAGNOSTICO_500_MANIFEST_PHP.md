# 🔍 Diagnóstico Completo - Erro 500 em manifest.php

**Data:** 2026-01-21  
**URL Testada:** `https://painel.cfcbomconselho.com.br/public_html/manifest.php`  
**Status:** ❌ Erro 500 (Internal Server Error)

---

## 📊 Evidências Coletadas

### 1. Prova do Erro 500

**Teste realizado:**
```powershell
$base = "https://painel.cfcbomconselho.com.br/public_html"
$u = "$base/manifest.php"
Invoke-WebRequest -Uri $u -Method GET -MaximumRedirection 5 -UseBasicParsing
```

**Resultado:**
- ❌ **STATUS: 500** (Internal Server Error)
- Headers retornados: Connection, Keep-Alive, Pragma, platform, panel, Retry-After, Content-Security-Policy, X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, alt-svc, Content-Length, Cache-Control, Content-Type, Date, Expires, Set-Cookie, Server, X-Powered-By
- **Nenhum conteúdo retornado** (body vazio ou erro não exposto)

### 2. Teste Decisivo: Bloqueio por Nome?

**Arquivo criado:** `public_html/pwa-manifest.php` (mesmo código, nome diferente)

**Resultado após deploy:**
- ❌ **STATUS: 404** (arquivo ainda não existe no servidor ou não foi deployado)
- ⚠️ **Aguardando deploy para confirmar se é bloqueio por nome**

**Interpretação:**
- Se `pwa-manifest.php` funcionar (200) e `manifest.php` continuar 500 → **bloqueio específico por nome** (WAF/ModSecurity)
- Se ambos derem 500 → problema de handler/permissão/ambiente no diretório

### 3. Verificação PHP no Mesmo Contexto

**Arquivo criado:** `public_html/tools/php_ping.php`
```php
<?php
header('Content-Type: text/plain; charset=utf-8');
echo "OK PHP " . PHP_VERSION;
```

**Resultado após deploy:**
- ❌ **STATUS: 404** (arquivo ainda não existe no servidor)

**Outros arquivos PHP testados:**
- `index.php` - ✅ Funciona (200)
- `generate-icons.php` - ✅ Funciona (200) 
- `tools/auditoria_pwa_executavel.php` - ✅ Funciona (200)

**Conclusão parcial:**
- ✅ PHP funciona normalmente no diretório
- ✅ Outros arquivos .php no mesmo diretório funcionam
- ❌ Apenas `manifest.php` retorna 500

### 4. Análise do .htaccess

**Arquivo:** `public_html/.htaccess`

**Regras relevantes:**
```apache
# 1) Se o arquivo/pasta existe fisicamente, NÃO reescreve (servir diretamente)
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
```

**Interpretação:**
- ✅ Regra deveria permitir `manifest.php` ser servido diretamente (arquivo existe)
- ❌ Mas está retornando 500 ao invés de servir o arquivo
- ⚠️ Possível: erro PHP antes do .htaccess processar OU WAF bloqueando antes

**Regras de segurança:**
- `<FilesMatch "^\.">` - Bloqueia arquivos ocultos (não afeta manifest.php)
- `RewriteRule ^storage/ - [F,L]` - Bloqueia /storage/ (não afeta manifest.php)
- Headers de segurança (não bloqueiam arquivos)

**Conclusão:**
- ❌ `.htaccess` NÃO parece ser a causa direta
- ⚠️ Pode haver WAF/ModSecurity no servidor bloqueando antes do .htaccess

---

## 🎯 Hipóteses Principais

### Hipótese 1: WAF/ModSecurity Bloqueando "manifest.php" (MAIS PROVÁVEL)

**Evidências:**
- ✅ Outros arquivos PHP funcionam
- ✅ Código mínimo também retorna 500
- ✅ Nome específico "manifest.php" pode ser bloqueado por regra de segurança

**Teste para confirmar:**
- Aguardar deploy de `pwa-manifest.php`
- Se `pwa-manifest.php` funcionar → **confirmado bloqueio por nome**

**Solução:**
- Usar `pwa-manifest.php` como endpoint
- Atualizar `<link rel="manifest">` para apontar para novo nome

### Hipótese 2: Erro PHP Fatal (MENOS PROVÁVEL)

**Evidências:**
- ❌ Código mínimo também retorna 500
- ❌ Nenhum log de erro visível (precisa acessar logs do servidor)

**Teste para confirmar:**
- Verificar logs PHP do servidor
- Verificar se há erro de sintaxe ou dependência faltando

**Solução:**
- Corrigir erro PHP específico
- Verificar se todas as dependências estão disponíveis

### Hipótese 3: Configuração do Servidor (POSSÍVEL)

**Evidências:**
- ⚠️ Hostinger pode ter regras específicas
- ⚠️ LiteSpeed pode ter configurações diferentes

**Teste para confirmar:**
- Verificar configurações do servidor (se tiver acesso)
- Verificar se há regras específicas para arquivos "manifest.*"

---

## 📝 Próximos Passos

### Imediato:
1. ✅ Aguardar deploy de `pwa-manifest.php` e `php_ping.php`
2. ✅ Testar `pwa-manifest.php` para confirmar bloqueio por nome
3. ⚠️ Se tiver SSH: verificar logs do servidor

### Se Confirmar Bloqueio por Nome:
1. ✅ Usar `pwa-manifest.php` como endpoint
2. ✅ Atualizar `shell.php` para apontar para `pwa-manifest.php`
3. ✅ Adicionar comentário explicando o bloqueio

### Se Não For Bloqueio por Nome:
1. ⚠️ Verificar logs PHP do servidor
2. ⚠️ Testar com código ainda mais simples
3. ⚠️ Contatar suporte do host (Hostinger) se necessário

---

## 🔧 Ação Cirúrgica Aplicada

**Arquivo criado:** `public_html/pwa-manifest.php`
- Mesmo código do `manifest.php`
- Nome diferente para evitar possível bloqueio
- Comentário explicando o motivo

**Próximo passo:**
- Após confirmar que `pwa-manifest.php` funciona, atualizar `shell.php`:
```php
<link rel="manifest" href="<?= base_path('/pwa-manifest.php') ?>">
```

---

## 📋 Checklist de Diagnóstico

- [x] Provar erro 500 com evidências (headers + status)
- [x] Criar `pwa-manifest.php` para teste de bloqueio por nome
- [x] Criar `php_ping.php` para verificar PHP
- [x] Testar outros arquivos PHP no mesmo diretório
- [x] Analisar `.htaccess` para regras que possam interferir
- [ ] Aguardar deploy e testar `pwa-manifest.php`
- [ ] Verificar logs do servidor (se tiver acesso SSH)
- [ ] Aplicar solução cirúrgica se confirmar bloqueio por nome

---

**Status Atual:** ⏳ Aguardando deploy e testes adicionais
