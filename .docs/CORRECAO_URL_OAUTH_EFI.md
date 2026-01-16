# Correção: URL do Endpoint OAuth EFI

**Data:** 2025-01-14  
**Problema:** Erro HTTP 404 ao autenticar com a API EFI

---

## Problema Identificado

O sistema estava usando a URL incorreta para o endpoint de autenticação OAuth:

**❌ URL Incorreta (causava 404):**
- Produção: `https://api.gerencianet.com.br/v1/oauth/token`
- Sandbox: `https://sandbox.gerencianet.com.br/v1/oauth/token`

**✅ URL Correta:**
- Produção: `https://apis.gerencianet.com.br/oauth/token` (sem `/v1` e com "apis" no plural)
- Sandbox: `https://sandbox.gerencianet.com.br/oauth/token` (sem `/v1`)

---

## Correções Aplicadas

### 1. `app/Services/EfiPaymentService.php`

**Adicionada propriedade `$oauthUrl`:**
```php
private $oauthUrl;
```

**Atualizado construtor:**
```php
// OAuth endpoint usa URL diferente (sem /v1)
$this->oauthUrl = $this->sandbox 
    ? 'https://sandbox.gerencianet.com.br'
    : 'https://apis.gerencianet.com.br';

// API endpoints usam /v1
$this->baseUrl = $this->sandbox 
    ? 'https://sandbox.gerencianet.com.br/v1'
    : 'https://apis.gerencianet.com.br/v1';
```

**Atualizado método `getAccessToken()`:**
```php
$url = $this->oauthUrl . '/oauth/token'; // Usa $oauthUrl em vez de $baseUrl
```

### 2. `public_html/tools/test_efi_auth.php`

Atualizado para usar a URL correta do OAuth.

---

## Diferença entre URLs

- **OAuth Token Endpoint:** `https://apis.gerencianet.com.br/oauth/token` (sem `/v1`)
- **API Endpoints:** `https://apis.gerencianet.com.br/v1/charges`, `/v1/charges/{id}`, etc. (com `/v1`)

A EFI usa URLs diferentes para OAuth e para os endpoints da API.

---

## Teste

Após a correção, o script de teste deve passar no teste de autenticação:
- Acesse: `http://localhost/cfc-v.1/public_html/tools/test_efi_auth.php`
- O teste "Teste de autenticação" deve mostrar: ✅ PASSOU

---

## Referência

Documentação oficial da EFI/Gerencianet:
- OAuth Token: `POST https://apis.gerencianet.com.br/oauth/token`
- Fonte: https://gerencianet.github.io/documentation/docs/apiPagamentos/Endpoints
