# Correção da API de Instrutores - Campos de Categorias

## Problema Identificado

O erro "Unexpected token '<', "<br /><b>"... is not valid JSON" estava aparecendo porque havia **inconsistências nos campos de categorias** na API, causando problemas na resposta JSON.

## Causa do Problema

### Inconsistência nos Campos de Categorias

1. **Frontend envia**: `categoria_habilitacao` (array)
2. **API tentava salvar**: `categoria_habilitacao` (string) e `categorias_json` (JSON)
3. **Banco de dados espera**: `categorias_json` (JSON)

### Problemas Específicos:

#### Na Criação (POST):
```php
// ❌ Antes (incorreto)
'categoria_habilitacao' => $data['categoria_habilitacao'] ?? '', // String
'categorias_json' => json_encode($data['categorias'] ?? []), // Campo errado
```

#### Na Edição (PUT):
```php
// ❌ Antes (incorreto)
if (isset($data['categoria_habilitacao'])) $updateInstrutorData['categoria_habilitacao'] = $data['categoria_habilitacao'];
if (isset($data['categorias'])) $updateInstrutorData['categorias_json'] = json_encode($data['categorias']);
```

## Solução Implementada

### Correção na Criação (POST):
```php
// ✅ Depois (correto)
'categorias_json' => json_encode($data['categoria_habilitacao'] ?? []),
```

### Correção na Edição (PUT):
```php
// ✅ Depois (correto)
if (isset($data['categoria_habilitacao'])) $updateInstrutorData['categorias_json'] = json_encode($data['categoria_habilitacao']);
```

## Fluxo Correto Agora

1. **Frontend envia**: `categoria_habilitacao: ["A", "B", "C"]`
2. **API recebe**: `$data['categoria_habilitacao']` (array)
3. **API salva**: `categorias_json` (JSON string)
4. **Banco armazena**: `["A", "B", "C"]` como JSON
5. **API retorna**: JSON válido

## Logs de Debug Mantidos

A API mantém logs detalhados para monitoramento:

```php
error_log('PUT - Dados recebidos na API: ' . json_encode($data));
error_log('PUT - Dados do instrutor para atualização: ' . json_encode($updateInstrutorData));
error_log('PUT - Categorias recebidas: ' . (isset($data['categoria_habilitacao']) ? json_encode($data['categoria_habilitacao']) : 'NÃO DEFINIDO'));
```

## Arquivos Modificados

- `admin/api/instrutores.php` - Corrigidos campos de categorias na criação e edição
- `admin/assets/js/instrutores.js` - Corrigido método de teste da API (HEAD → GET)
- `CORRECAO_API_CATEGORIAS.md` - Documentação da correção

## Teste Recomendado

1. **Acesse a página de instrutores**
2. **Clique em "Editar"** no instrutor ID 23
3. **Verifique se as categorias estão marcadas** (A, B, C, D, E)
4. **Clique em "Salvar Instrutor"**
5. **Verifique no console** se:
   - ✅ **Método: PUT** aparece
   - ✅ **Não há erro** de JSON inválido
   - ✅ **Dados são salvos** corretamente
6. **Verifique no banco** se `categorias_json` contém `["A","B","C","D","E"]`

## Resultado Esperado

Agora quando você salvar um instrutor:

- ✅ **API recebe** categorias corretamente
- ✅ **API salva** no campo `categorias_json`
- ✅ **API retorna** JSON válido
- ✅ **Frontend processa** resposta sem erro
- ✅ **Dados são persistidos** no banco corretamente
- ✅ **Categorias são carregadas** na edição
