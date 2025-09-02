# Correção do Erro "Failed to fetch" na API de Instrutores

## Problema Identificado

O erro "Failed to fetch" estava ocorrendo porque a API retornava `{"error":"Usuário não está logado"}`, indicando um problema de **autenticação/sessão**, não de conectividade de rede.

## Causa do Problema

### 1. **Configuração incorreta de credenciais no fetch**
- JavaScript estava usando `credentials: 'same-origin'`
- Para requisições cross-origin ou com sessões, deve usar `credentials: 'include'`

### 2. **Headers CORS incorretos na API**
- API estava usando `Access-Control-Allow-Origin: *`
- Quando usando credenciais, não pode usar `*` - deve especificar origem específica
- Faltava `Access-Control-Allow-Credentials: true`

## Solução Implementada

### 1. **Correção no JavaScript (`admin/assets/js/instrutores.js`)**

**Antes:**
```javascript
const defaultOptions = {
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },
    credentials: 'same-origin'  // ❌ Incorreto
};
```

**Depois:**
```javascript
const defaultOptions = {
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },
    credentials: 'include'  // ✅ Correto
};
```

### 2. **Correção na API (`admin/api/instrutores.php`)**

**Antes:**
```php
header('Access-Control-Allow-Origin: *');  // ❌ Não permite credenciais
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

**Depois:**
```php
header('Access-Control-Allow-Origin: http://localhost:8080');  // ✅ Origem específica
header('Access-Control-Allow-Credentials: true');  // ✅ Permite credenciais
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
```

## Diferença entre `same-origin` e `include`

### `credentials: 'same-origin'`
- Envia cookies apenas para requisições para o mesmo domínio
- Não funciona para subdomínios ou portas diferentes
- Mais restritivo

### `credentials: 'include'`
- Sempre envia cookies, mesmo para requisições cross-origin
- Funciona com APIs em subdomínios ou portas diferentes
- Necessário quando a API verifica sessões

## Fluxo Correto Agora

1. **Usuário faz login** → Sessão criada no servidor
2. **JavaScript faz requisição** → `credentials: 'include'` envia cookies da sessão
3. **API recebe requisição** → Verifica sessão através dos cookies
4. **API retorna dados** → Se autenticado, retorna dados do instrutor

## Teste Recomendado

1. **Faça login** no sistema como administrador
2. **Acesse a página de instrutores**
3. **Clique em "Editar"** no instrutor ID 23
4. **Verifique no console** se:
   - ✅ **Não há erro** "Failed to fetch"
   - ✅ **API retorna** dados do instrutor
   - ✅ **Modal abre** com dados preenchidos

## Logs Esperados

```
🌐 Caminho da API Instrutores detectado: http://localhost:8080/cfc-bom-conselho/admin/api/instrutores.php
✅ API Instrutores acessível: 200
📡 Fazendo requisição para: http://localhost:8080/cfc-bom-conselho/admin/api/instrutores.php?id=23
📡 Método: GET
✅ Requisição bem-sucedida
```

## Arquivos Modificados

- `admin/assets/js/instrutores.js` - Corrigido `credentials` para `'include'`
- `admin/api/instrutores.php` - Corrigidos headers CORS
- `CORRECAO_ERRO_CONECTIVIDADE_API.md` - Documentação da correção

## Resultado Esperado

Agora quando você editar um instrutor:

- ✅ **Sessão é mantida** entre requisições
- ✅ **API autentica** corretamente
- ✅ **Dados são retornados** sem erro
- ✅ **Modal abre** com informações preenchidas
- ✅ **Categorias e datas** são exibidas corretamente
