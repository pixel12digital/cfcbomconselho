# ✅ Implementação White-Label no pwa-manifest.php

**Data:** 2026-01-21  
**Status:** ✅ **Implementado com fallback seguro**

## O que foi implementado

### 1. Manifest dinâmico baseado no CFC atual

O arquivo `public_html/pwa-manifest.php` agora:
- ✅ Busca o CFC atual via `$_SESSION['cfc_id']` ou `Constants::CFC_ID_DEFAULT`
- ✅ Usa o nome do CFC do banco de dados (`cfcs.nome`)
- ✅ Gera `short_name` automaticamente (limitado a 15 caracteres)
- ✅ Mantém todos os outros campos (start_url, scope, display, etc.)
- ✅ **Nunca retorna 500** - sempre tem fallback seguro

### 2. Fallback seguro

Se qualquer erro ocorrer (DB, session, arquivo não encontrado):
- ✅ Retorna manifest estático com valores padrão
- ✅ Status sempre 200 (nunca 500)
- ✅ JSON sempre válido

### 3. Estrutura de includes mínima

O arquivo carrega apenas o necessário:
1. `app/Bootstrap.php` - Session e helpers
2. `app/Config/Constants.php` - Constantes (CFC_ID_DEFAULT)
3. `app/Config/Env.php` - Configurações (.env)
4. `app/Config/Database.php` - Conexão com banco
5. `app/Models/Model.php` - Model base
6. `app/Models/Cfc.php` - Model do CFC

## Campos dinâmicos

### ✅ Implementado agora:
- **name**: Nome do CFC do banco (`cfcs.nome`)
- **short_name**: Versão curta (máx 15 chars, truncado se necessário)
- **description**: "Sistema de gestão para {NomeDoCFC}"

### ⏳ Preparado para futuro:
- **theme_color**: Pode ser dinâmico quando campo `theme_color` existir na tabela `cfcs`
- **icons**: Pode usar logo do CFC quando campo `logo` ou `logo_path` existir
- **background_color**: Pode ser dinâmico no futuro

## Como funciona

### Fluxo de execução:

```
1. Headers definidos (Content-Type, Cache-Control)
2. Valores padrão definidos (fallback)
3. Try/Catch:
   a. Carrega dependências (Bootstrap, Env, Database, Models)
   b. Busca CFC atual via Model Cfc
   c. Se encontrou CFC com nome:
      → Monta manifest dinâmico
   d. Se não encontrou:
      → Usa fallback
4. Se qualquer erro:
   → Usa fallback (nunca retorna 500)
5. Output JSON sempre válido
```

### Exemplo de output dinâmico:

```json
{
    "name": "CFC Bom Conselho",
    "short_name": "CFC Bom Consel...",
    "description": "Sistema de gestão para CFC Bom Conselho",
    "start_url": "./dashboard",
    "scope": "./",
    "display": "standalone",
    "orientation": "portrait-primary",
    "theme_color": "#023A8D",
    "background_color": "#ffffff",
    "icons": [
        {
            "src": "./icons/icon-192x192.png",
            "sizes": "192x192",
            "type": "image/png",
            "purpose": "any maskable"
        },
        {
            "src": "./icons/icon-512x512.png",
            "sizes": "512x512",
            "type": "image/png",
            "purpose": "any maskable"
        }
    ]
}
```

## Cache

- **Cache-Control**: `public, max-age=300` (5 minutos)
- Permite atualização rápida quando CFC muda
- Mantém performance (não recarrega a cada request)

## Testes

### Script PowerShell criado

Arquivo: `.docs/TESTE_PWA_MANIFEST_DINAMICO.ps1`

Execute após deploy:
```powershell
.\docs\TESTE_PWA_MANIFEST_DINAMICO.ps1
```

Ou manualmente:
```powershell
$u="https://painel.cfcbomconselho.com.br/public_html/pwa-manifest.php"
$r=Invoke-WebRequest $u -UseBasicParsing
$r.StatusCode
$r.Headers."Content-Type"
$j=$r.Content | ConvertFrom-Json
$j.name
$j.short_name
$j.start_url
$j.icons | Format-Table -AutoSize
```

### Validações

O script testa:
- ✅ Status Code = 200
- ✅ Content-Type correto
- ✅ JSON válido
- ✅ Campos obrigatórios presentes
- ✅ Valores dinâmicos (nome não é padrão)
- ✅ Ícones configurados

## Configuração no shell.php

✅ **Já configurado:**
```php
<link rel="manifest" href="<?= base_path('/pwa-manifest.php') ?>">
```

## Próximos passos (opcional)

### 1. Adicionar campo `theme_color` na tabela `cfcs`
```sql
ALTER TABLE cfcs ADD COLUMN theme_color VARCHAR(7) DEFAULT '#023A8D';
```

### 2. Adicionar campo `logo_path` na tabela `cfcs`
```sql
ALTER TABLE cfcs ADD COLUMN logo_path VARCHAR(255) DEFAULT NULL;
```

### 3. Implementar ícones dinâmicos
Quando `logo_path` existir, gerar ícones PWA a partir do logo do CFC.

### 4. Botão "Instalar aplicativo"
Implementar UI discreta usando `beforeinstallprompt` (commit separado).

## Segurança

- ✅ Nunca expõe erros ao cliente
- ✅ Nunca retorna 500
- ✅ Try/Catch em todos os pontos críticos
- ✅ Fallback sempre disponível
- ✅ Headers de segurança mantidos

## Performance

- ✅ Cache de 5 minutos (balance entre atualização e performance)
- ✅ Includes apenas quando necessário
- ✅ Queries otimizadas (busca apenas CFC atual)
- ✅ Fallback rápido (sem DB quando necessário)

## Compatibilidade

- ✅ Funciona mesmo sem sessão (usa CFC_ID_DEFAULT)
- ✅ Funciona mesmo sem DB (fallback estático)
- ✅ Funciona mesmo sem Model Cfc (fallback estático)
- ✅ Compatível com multi-tenant (usa `$_SESSION['cfc_id']`)
