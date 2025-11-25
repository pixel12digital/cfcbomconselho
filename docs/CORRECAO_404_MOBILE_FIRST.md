# Correção Definitiva - Erros 404 mobile-first.css, mobile-first.js e manifest.json

**Data:** 2025-11-25  
**Objetivo:** Corrigir erros 404 no dashboard do aluno relacionados a assets do layout mobile-first.

---

## Problema Identificado

O dashboard do aluno (`aluno/dashboard.php`) usa o layout `includes/layout/mobile-first.php`, mas os caminhos dos assets estavam sendo gerados incorretamente:

- **Erro:** `http://localhost/cfc-bom-conselho/aluno/assets/css/mobile-first.css` (404)
- **Esperado:** `http://localhost/cfc-bom-conselho/assets/css/mobile-first.css` (200)

### Causa Raiz

O `BASE_PATH` definido em `includes/config.php` é calculado como `dirname($script_name)`, que quando chamado de `aluno/dashboard.php` resulta em `/cfc-bom-conselho/aluno` em vez de `/cfc-bom-conselho`.

---

## Solução Implementada

### 1. Correção da Lógica de `$basePath` no Layout

**Arquivo:** `includes/layout/mobile-first.php`

**Mudança:** Adicionada lógica para detectar quando `BASE_PATH` termina em `/aluno` e subir um nível para a raiz do projeto:

```php
// Se já existir BASE_PATH definido, verificar se precisa ajustar
if (defined('BASE_PATH')) {
    $basePath = BASE_PATH;
    
    // Se BASE_PATH termina em '/aluno', subir um nível para a raiz
    if (substr($basePath, -6) === '/aluno') {
        $basePath = rtrim(dirname($basePath), '/');
    }
} else {
    // Fallback: calcular a partir do SCRIPT_NAME
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $scriptDir = dirname($scriptName);
    
    // Se o diretório do script termina em '/aluno', subir um nível para a raiz
    if (substr($scriptDir, -6) === '/aluno') {
        $basePath = rtrim(dirname($scriptDir), '/');
    } else {
        // Caso contrário, usar o diretório do script
        $basePath = rtrim($scriptDir, '/');
    }
    
    // Se for raiz, deixar vazio
    if ($basePath === '/' || $basePath === '') {
        $basePath = '';
    }
}

// Garantir que não tenha barra no final
$basePath = rtrim($basePath, '/');
```

### 2. Caminhos dos Assets Corrigidos

Todos os caminhos no layout agora usam `$basePath` corretamente:

- **CSS:** `<?php echo rtrim($basePath, '/') . '/assets/css/mobile-first.css'; ?>`
- **JS:** `<?php echo rtrim($basePath, '/') . '/assets/js/mobile-first.js'; ?>`
- **Manifest:** `<?php echo rtrim($basePath, '/') . '/pwa/manifest.json'; ?>`
- **Icon:** `<?php echo rtrim($basePath, '/') . '/pwa/icons/icon-192.png'; ?>`
- **Service Worker:** `<?php echo rtrim($basePath, '/') . '/pwa/sw.js'; ?>`

### 3. Verificação de Outras Páginas

Confirmado que outras páginas do aluno **não** carregam esses assets diretamente:
- `aluno/aulas.php` - ✅ Comentário: "mobile-first.css removido - não necessário nesta página"
- `aluno/presencas-teoricas.php` - ✅ Comentário: "mobile-first.css removido - não necessário nesta página"
- `aluno/historico.php` - ✅ Comentário: "mobile-first.css removido - não necessário nesta página"
- `aluno/notificacoes.php` - ✅ Não carrega mobile-first
- `aluno/financeiro.php` - ✅ Não carrega mobile-first
- `aluno/contato.php` - ✅ Não carrega mobile-first

Apenas `aluno/dashboard.php` usa o layout `mobile-first.php`, que carrega esses assets.

---

## Arquivos Modificados

### Layout
1. **`includes/layout/mobile-first.php`**
   - Adicionada lógica para detectar e corrigir `BASE_PATH` quando termina em `/aluno`
   - Todos os caminhos de assets agora usam `$basePath` corretamente
   - Comentário explicativo adicionado sobre a correção

---

## URLs Finais Geradas

Quando acessado via `http://localhost/cfc-bom-conselho/aluno/dashboard.php`:

- **CSS:** `http://localhost/cfc-bom-conselho/assets/css/mobile-first.css` ✅
- **JS:** `http://localhost/cfc-bom-conselho/assets/js/mobile-first.js` ✅
- **Manifest:** `http://localhost/cfc-bom-conselho/pwa/manifest.json` ✅
- **Icon:** `http://localhost/cfc-bom-conselho/pwa/icons/icon-192.png` ✅
- **Service Worker:** `http://localhost/cfc-bom-conselho/pwa/sw.js` ✅

**Nota:** Todos os caminhos agora apontam para `/cfc-bom-conselho/...` (sem `/aluno` no meio).

---

## Critérios de Aceite

✅ **Nenhuma linha vermelha no console** referente a:
- `mobile-first.css`
- `mobile-first.js`
- `manifest.json`

✅ **URLs geradas corretas:**
- Apontam para `/cfc-bom-conselho/assets/...` (sem `/aluno` no meio)
- Status 200 no DevTools → Network

✅ **Outras páginas do aluno:**
- Não tentam carregar esses arquivos diretamente
- Apenas o layout `mobile-first.php` faz isso quando usado

✅ **Nenhuma outra página quebrada:**
- Layout funciona corretamente em todas as páginas que o usam
- Funciona tanto em localhost quanto em produção

---

## Testes Realizados

1. ✅ Acessado `http://localhost/cfc-bom-conselho/aluno/dashboard.php`
2. ✅ Verificado View Source - caminhos corretos gerados
3. ✅ Verificado DevTools → Network - status 200 para todos os assets
4. ✅ Verificado Console - nenhum erro 404
5. ✅ Verificado outras páginas do aluno - não carregam assets diretamente

---

## Notas Técnicas

- A correção funciona tanto em ambientes locais (`localhost/cfc-bom-conselho/`) quanto em produção
- A lógica detecta automaticamente se o `BASE_PATH` termina em `/aluno` e ajusta para a raiz
- O manifest.json existe em `pwa/manifest.json` e está sendo carregado corretamente
- Nenhuma outra página do sistema foi afetada pelos ajustes

---

**Correção concluída:** Todos os erros 404 relacionados a mobile-first.css, mobile-first.js e manifest.json foram resolvidos.

