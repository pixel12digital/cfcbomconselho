# 🔍 Auditoria Técnica PWA - Sistema CFC

**Data:** 2024  
**Status:** Diagnóstico Completo  
**Objetivo:** Mapear estado atual do PWA antes da implementação de white-label e instalação opcional

---

## 📋 TAREFA 1 — AUDITORIA TÉCNICA

### 1.1 Manifest

#### ✅ Status: **INCOMPLETO**

**Localização:**
- Arquivo: `public_html/manifest.json`
- Acessível via: `/manifest.json` (referenciado no `shell.php` linha 12)

**Campos Existentes:**
```json
{
  "name": "CFC Sistema de Gestão",           ✅ Existe
  "short_name": "CFC Sistema",                ✅ Existe
  "description": "Sistema de gestão...",      ✅ Existe
  "start_url": "/dashboard",                  ✅ Existe
  "scope": "/",                                ✅ Existe
  "display": "standalone",                     ✅ Existe
  "orientation": "portrait-primary",          ✅ Existe
  "theme_color": "#023A8D",                   ✅ Existe
  "background_color": "#ffffff",               ✅ Existe
  "icons": [                                   ✅ Existe (estrutura)
    {
      "src": "/icons/icon-192x192.png",
      "sizes": "192x192",
      "type": "image/png",
      "purpose": "any maskable"
    },
    {
      "src": "/icons/icon-512x512.png",
      "sizes": "512x512",
      "type": "image/png",
      "purpose": "any maskable"
    }
  ]
}
```

**Problemas Identificados:**
- ❌ **Valores hardcoded** (não dinâmicos por CFC)
- ❌ **Nome genérico** ("CFC Sistema" em vez de nome do CFC)
- ❌ **Ícones referenciados mas não verificados se existem**

---

### 1.2 Service Worker

#### ✅ Status: **OK (Parcial)**

**Localização:**
- Arquivo: `public_html/sw.js`
- Registrado em: `app/Views/layouts/shell.php` (linhas 176-214)

**Registro:**
- ✅ Verifica se `serviceWorker` está disponível
- ✅ Verifica se arquivo existe antes de registrar (evita 404)
- ✅ Registra apenas em produção OU se arquivo existir
- ✅ Atualiza automaticamente a cada 60s em produção

**Funcionalidades Implementadas:**

1. **Cache Estático (Cache-First):**
   - ✅ CSS: `tokens.css`, `components.css`, `layout.css`, `utilities.css`
   - ✅ JS: `app.js`
   - ✅ Manifest e ícones
   - ✅ Estratégia: Cache-first para assets estáticos

2. **Cache de API:**
   - ❌ **NÃO implementado** - Rotas `/api/` têm bypass total (linha 137-139)
   - ✅ **Correto para segurança** - APIs sempre buscam da rede

3. **Offline Fallback:**
   - ⚠️ **Parcial** - Retorna mensagem genérica "Offline - Conteúdo não disponível" (linha 150)
   - ❌ Não há página offline customizada
   - ❌ Não há fallback para rotas privadas offline

4. **Rotas Protegidas:**
   - ✅ Bypass total para rotas de autenticação (`/login`, `/logout`, etc.)
   - ✅ Bypass total para rotas privadas (HTML nunca é cacheado)
   - ✅ Network-first para HTML autenticado (segurança crítica)

**Estratégia de Cache:**
- ✅ Cache-first para assets estáticos
- ✅ Network-first para HTML de rotas privadas
- ✅ Bypass total para APIs e autenticação
- ✅ Limpeza automática de caches antigos

---

### 1.3 HTTPS

#### ⚠️ Status: **NÃO VERIFICADO (Assumir Problemas)**

**Detecção Automática:**
- ✅ Sistema detecta HTTPS via `base_url()` em `app/Bootstrap.php` (linha 42)
- ✅ Código: `$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';`

**Configuração de Produção:**
- ⚠️ **Não há confirmação de ambiente de produção configurado**
- ⚠️ **Não há verificação de certificado SSL válido**
- ⚠️ **Não há redirecionamento HTTP → HTTPS forçado**

**Páginas que Podem Quebrar Contexto Seguro:**
- ⚠️ **Não verificado** - Necessário testar em produção
- ⚠️ Possíveis recursos HTTP mistos (imagens, scripts externos)

**Recomendações:**
1. Configurar SSL válido em produção (Let's Encrypt, Cloudflare, ou certificado do host)
2. Implementar redirecionamento HTTP → HTTPS no `.htaccess` ou servidor
3. Verificar que todos os recursos são servidos via HTTPS

---

### 1.4 Critérios PWA

#### ⚠️ Status: **PARCIAL**

**Installable (Chrome DevTools → Application → Manifest):**
- ⚠️ **Não testado** - Necessário verificar em produção com HTTPS
- ✅ Manifest existe e está referenciado
- ⚠️ Ícones podem não existir (não verificados)

**Lighthouse PWA Score:**
- ⚠️ **Não verificado** - Necessário rodar Lighthouse em produção

**Ícones:**
- ⚠️ **Status desconhecido** - Diretório `public_html/icons/` existe mas está vazio
- ✅ Manifest referencia: `/icons/icon-192x192.png` e `/icons/icon-512x512.png`
- ✅ Existe script gerador: `public_html/generate-icons.php`
- ❌ **Ícones não foram gerados ainda** (diretório vazio)

**Tamanhos de Ícone:**
- ✅ Manifest especifica: 192x192 e 512x512 (correto)
- ✅ Purpose: "any maskable" (correto)

---

### 1.5 Instalação

#### ❌ Status: **NÃO IMPLEMENTADO**

**Botão Nativo do Navegador:**
- ⚠️ **Não verificado** - Depende de:
  - HTTPS em produção
  - Manifest válido
  - Ícones existentes
  - Service Worker registrado

**Código Custom (beforeinstallprompt):**
- ❌ **NÃO existe** - Nenhum código intercepta `beforeinstallprompt`
- ❌ **NÃO existe** - Nenhum botão custom de instalação
- ❌ **NÃO existe** - Nenhum banner ou aviso de instalação

**Elementos Visuais:**
- ❌ **Nenhum elemento visual relacionado a PWA encontrado**

---

## 📋 TAREFA 2 — AUDITORIA DE UX

### 2.1 Elementos Visuais Relacionados a PWA

#### ❌ Status: **INEXISTENTE**

**Resultado da Busca:**
- ❌ Nenhum botão de instalação encontrado
- ❌ Nenhum banner de instalação encontrado
- ❌ Nenhum aviso de PWA encontrado
- ❌ Nenhum elemento visual relacionado a "aplicativo" ou "instalar"

**Onde Aparece:**
- N/A (não existe)

**Para Quais Perfis:**
- N/A (não existe)

**É Forçado ou Opcional:**
- N/A (não existe)

**Conclusão:**
✅ **Estado ideal para implementação** - Não há elementos visuais que precisam ser removidos ou refatorados.

---

## 📋 TAREFA 3 — CAPACIDADE DE WHITE-LABEL

### 3.1 Logo Dinâmico por CFC

#### ❌ Status: **NÃO IMPLEMENTADO**

**Banco de Dados:**
- ✅ Tabela `cfcs` existe (migration `001_create_base_tables.sql`)
- ✅ Campo `nome` existe (varchar 255)
- ❌ **Campo `logo` NÃO existe** na tabela `cfcs`
- ❌ **Campo `logo_path` NÃO existe**
- ❌ **Nenhum campo para armazenar logo/ícone do CFC**

**Uso Atual:**
- ❌ Logo não é buscado do banco
- ❌ Logo não é exibido dinamicamente
- ✅ Logo hardcoded no topbar: texto "CFC Sistema" (linha 41 do `shell.php`)

**Conclusão:**
❌ **Não é possível gerar ícones dinâmicos por tenant** - Falta estrutura no banco e lógica de geração.

---

### 3.2 Nome do CFC do Banco

#### ⚠️ Status: **ESTRUTURA EXISTE, MAS NÃO USADA NO PWA**

**Banco de Dados:**
- ✅ Tabela `cfcs` existe
- ✅ Campo `nome` existe e armazena nome do CFC
- ✅ Seed inicial cria CFC com nome (ex: "CFC Principal")

**Uso Atual:**
- ✅ Sistema usa `cfc_id` da sessão: `$_SESSION['cfc_id'] ?? Constants::CFC_ID_DEFAULT`
- ✅ Controllers buscam dados por `cfc_id` (ex: `FinanceiroController` linha 19)
- ❌ **Nome do CFC NÃO é buscado do banco para o manifest**
- ❌ **Nome do CFC NÃO é usado no manifest.json**
- ⚠️ Nome aparece hardcoded no topbar como "CFC Sistema" (não dinâmico)

**Model de CFC:**
- ❌ **NÃO existe Model `Cfc.php`**
- ❌ **NÃO há método para buscar dados do CFC atual**

**Conclusão:**
⚠️ **Estrutura existe, mas não é usada no PWA** - Nome do CFC está no banco, mas manifest usa valores hardcoded.

---

### 3.3 Possibilidade Técnica de Manifest Dinâmico

#### ⚠️ Status: **TECNICAMENTE POSSÍVEL, MAS NÃO IMPLEMENTADO**

**Requisitos para Manifest Dinâmico:**
1. ✅ PHP pode gerar JSON dinamicamente
2. ✅ Router pode servir manifest dinâmico (ex: `/manifest.json` → Controller)
3. ⚠️ Nome do CFC existe no banco, mas não é buscado
4. ❌ Logo/ícone do CFC não existe no banco
5. ❌ Não há lógica para gerar ícones dinâmicos

**Implementação Necessária:**
- Criar endpoint PHP que gera manifest.json dinamicamente
- Buscar dados do CFC do banco (`cfcs` table)
- Gerar ícones PWA a partir do logo do CFC (se existir)
- Ou usar ícones padrão se logo não existir

**Conclusão:**
⚠️ **Tecnicamente possível, mas requer implementação completa** - Estrutura base existe, mas falta lógica de geração dinâmica.

---

### 3.4 Possibilidade Técnica de Ícones Dinâmicos

#### ❌ Status: **NÃO IMPLEMENTADO**

**Requisitos para Ícones Dinâmicos:**
1. ✅ Script gerador existe: `public_html/generate-icons.php`
2. ✅ PHP GD pode gerar imagens dinamicamente
3. ❌ Logo do CFC não existe no banco
4. ❌ Não há endpoint para gerar ícones dinamicamente
5. ❌ Não há cache de ícones gerados

**Implementação Necessária:**
- Adicionar campo `logo` ou `logo_path` na tabela `cfcs`
- Criar endpoint que gera ícones 192x192 e 512x512 a partir do logo
- Cachear ícones gerados (evitar regenerar a cada request)
- Servir ícones via URL dinâmica (ex: `/icons/cfc-{id}-192x192.png`)

**Conclusão:**
❌ **Não é possível hoje** - Falta estrutura no banco e lógica de geração dinâmica.

---

## 📊 CHECKLIST RESUMIDO

### Manifest
- ✅ Existe: `public_html/manifest.json`
- ⚠️ **Status: INCOMPLETO** (valores hardcoded, não dinâmico)

### Service Worker
- ✅ Existe: `public_html/sw.js`
- ✅ Registrado: `shell.php` (linhas 176-214)
- ✅ **Status: OK (Parcial)** - Funcional, mas sem offline fallback completo

### HTTPS
- ⚠️ **Status: NÃO VERIFICADO** - Assumir que precisa configurar em produção

### Installable
- ⚠️ **Status: PARCIAL** - Depende de HTTPS + ícones existentes

### Lighthouse PWA Score
- ⚠️ **Status: NÃO VERIFICADO** - Necessário rodar em produção

### Ícones
- ❌ **Status: INEXISTENTE** - Diretório vazio, ícones não gerados

### Instalação
- ❌ **Status: NÃO IMPLEMENTADO** - Nenhum código de instalação

### Elementos Visuais PWA
- ❌ **Status: INEXISTENTE** - Nenhum elemento visual

### White-Label (Logo)
- ❌ **Status: NÃO IMPLEMENTADO** - Campo logo não existe no banco

### White-Label (Nome)
- ⚠️ **Status: PARCIAL** - Nome existe no banco, mas não usado no PWA

### Manifest Dinâmico
- ⚠️ **Status: TECNICAMENTE POSSÍVEL** - Requer implementação

### Ícones Dinâmicos
- ❌ **Status: NÃO IMPLEMENTADO** - Requer estrutura no banco + lógica

---

## 📝 O QUE JÁ ESTÁ PRONTO

1. ✅ **Manifest.json** existe e está referenciado no HTML
2. ✅ **Service Worker** implementado e registrado
3. ✅ **Estratégia de cache** bem definida (cache-first para assets, network-first para HTML)
4. ✅ **Rotas protegidas** não são cacheadas (segurança)
5. ✅ **Estrutura multi-CFC** existe no banco (`cfcs` table)
6. ✅ **Nome do CFC** existe no banco (campo `nome`)
7. ✅ **Script gerador de ícones** existe (`generate-icons.php`)
8. ✅ **Detecção automática de HTTPS** implementada

---

## ❌ O QUE FALTA IMPLEMENTAR

1. ❌ **Ícones PWA** (192x192 e 512x512) - Diretório vazio
2. ❌ **Botão de instalação** custom (opcional, elegante)
3. ❌ **Interceptação de beforeinstallprompt** (para controle de quando mostrar botão)
4. ❌ **Campo logo** na tabela `cfcs` (para white-label)
5. ❌ **Model Cfc.php** (para buscar dados do CFC)
6. ❌ **Manifest dinâmico** (gerado por PHP com dados do CFC)
7. ❌ **Geração dinâmica de ícones** (a partir do logo do CFC)
8. ❌ **Página offline** custom (fallback melhor que mensagem genérica)
9. ❌ **Verificação de HTTPS** em produção
10. ❌ **Redirecionamento HTTP → HTTPS** forçado

---

## 🔧 O QUE PRECISA REFATORAR

1. ⚠️ **Manifest.json** - Converter de arquivo estático para endpoint dinâmico
2. ⚠️ **Topbar logo** - Usar nome do CFC do banco em vez de "CFC Sistema" hardcoded
3. ⚠️ **Service Worker** - Adicionar página offline custom (opcional, mas recomendado)
4. ⚠️ **Estrutura de ícones** - Implementar sistema de cache para ícones gerados dinamicamente

---

## 🎯 PRÓXIMOS PASSOS RECOMENDADOS

### Fase 1: Preparação Técnica
1. ✅ Verificar HTTPS em produção
2. ✅ Gerar ícones PWA básicos (usar `generate-icons.php`)
3. ✅ Testar installability no Chrome DevTools

### Fase 2: White-Label Básico
1. ✅ Adicionar campo `logo` na tabela `cfcs`
2. ✅ Criar Model `Cfc.php`
3. ✅ Converter manifest.json para endpoint PHP dinâmico
4. ✅ Usar nome do CFC do banco no manifest

### Fase 3: White-Label Completo
1. ✅ Implementar geração dinâmica de ícones a partir do logo
2. ✅ Cachear ícones gerados
3. ✅ Fallback para ícones padrão se logo não existir

### Fase 4: Instalação Opcional
1. ✅ Interceptar `beforeinstallprompt`
2. ✅ Criar botão elegante "Instalar aplicativo do CFC"
3. ✅ Mostrar apenas para usuários autenticados
4. ✅ Usar nome do CFC no botão

---

## 📌 NOTAS IMPORTANTES

1. **HTTPS é obrigatório** para PWA funcionar em produção (exceto localhost)
2. **Ícones são obrigatórios** para PWA ser installable
3. **Manifest dinâmico** requer endpoint PHP (não pode ser arquivo estático JSON)
4. **White-label completo** requer estrutura no banco + lógica de geração
5. **Instalação opcional** é a abordagem correta (não forçar, não usar banners agressivos)

---

**Fim do Relatório de Auditoria**
