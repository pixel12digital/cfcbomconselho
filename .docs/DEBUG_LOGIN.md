# Debug - Login Redirect

## Problema Identificado

O form action estava usando `/login` (sem prefixo), causando POST para URL incorreta.

## Correções Aplicadas

1. ✅ Form action corrigido: `action="<?= base_path('/login') ?>"`
2. ✅ Redirects já estão usando `base_url()` (correto)

## Validação Necessária

Após as correções, validar no DevTools → Network:

1. **POST Request:**
   - URL: `POST /cfc-v.1/public_html/login` ✅
   - Status: 302 (redirect)

2. **Redirect Response:**
   - Location header: `http://localhost/cfc-v.1/public_html/dashboard` ✅
   - Não pode ser `/dashboard` ou `http://localhost/dashboard`

3. **Session Cookie:**
   - PHPSESSID deve ser setado

## Teste

1. Acessar: `http://localhost/cfc-v.1/public_html/login`
2. Preencher credenciais:
   - Email: `admin@cfc.local`
   - Senha: `admin123`
3. Clicar em "Entrar"
4. Verificar no Network:
   - POST vai para `/cfc-v.1/public_html/login`
   - Redirect vai para `http://localhost/cfc-v.1/public_html/dashboard`
   - Dashboard carrega com layout completo
