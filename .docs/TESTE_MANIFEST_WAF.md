# Teste de Bloqueio WAF - manifest.php vs pwa-manifest.php

**Data:** 2026-01-21  
**Objetivo:** Verificar se o erro 500 em `manifest.php` é causado por bloqueio WAF/regra do servidor baseado no nome do arquivo.

## Arquivos Criados/Modificados

### ✅ Criado
- `public_html/pwa-manifest.php` - Cópia idêntica do `manifest.php` com nome alternativo

### ✅ Modificado
- `app/Views/layouts/shell.php` - Atualizado para apontar para `pwa-manifest.php` (linha 12)

## Teste Executado (Local)

```powershell
$base="https://painel.cfcbomconselho.com.br/public_html"

# Teste do original
manifest.php: 500 ❌

# Teste do alternativo (ainda não no servidor)
pwa-manifest.php: 404 ⚠️ (esperado - arquivo precisa ser enviado)
```

## Próximos Passos

### 1. Upload do arquivo para o servidor
Enviar `public_html/pwa-manifest.php` para o servidor de produção em:
`https://painel.cfcbomconselho.com.br/public_html/pwa-manifest.php`

### 2. Teste definitivo (após upload)
Executar novamente o teste PowerShell:

```powershell
$base="https://painel.cfcbomconselho.com.br/public_html"

# Teste do original (provável 500)
try { 
    $status = (Invoke-WebRequest "$base/manifest.php" -UseBasicParsing).StatusCode
    Write-Host "manifest.php: $status"
} catch { 
    $status = $_.Exception.Response.StatusCode.value__
    Write-Host "manifest.php: $status"
}

# Teste do alternativo (o decisivo)
try { 
    $status = (Invoke-WebRequest "$base/pwa-manifest.php" -UseBasicParsing).StatusCode
    Write-Host "pwa-manifest.php: $status"
} catch { 
    $status = $_.Exception.Response.StatusCode.value__
    Write-Host "pwa-manifest.php: $status"
}
```

## Interpretação dos Resultados

### ✅ Cenário 1: pwa-manifest.php = 200 e manifest.php = 500
**Conclusão:** Bloqueio específico por regra/segurança do servidor (WAF bloqueando nome "manifest.php")

**Ação:** 
- ✅ Já ajustado: `shell.php` aponta para `pwa-manifest.php`
- Manter `pwa-manifest.php` como manifest dinâmico
- White-label pode ser implementado normalmente

### ❌ Cenário 2: Ambos retornam 500
**Conclusão:** Erro de execução/permissão/handler/log (não é bloqueio por nome)

**Ação:**
- Verificar logs do servidor (`error_log`, `storage/logs/php_errors.log`)
- Verificar permissões do arquivo
- Verificar configuração PHP/handler

## Status Atual

- ✅ Arquivo `pwa-manifest.php` criado localmente
- ✅ `shell.php` atualizado para usar `pwa-manifest.php`
- ⏳ Aguardando upload para servidor e teste definitivo

## Nota

O arquivo `shell.php` já foi ajustado para usar `pwa-manifest.php`. Se o teste confirmar que funciona (200), o white-label estará habilitado automaticamente. Se não funcionar, será necessário reverter para `manifest.json` e investigar os logs.
