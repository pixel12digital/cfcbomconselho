# Correção do Erro "Já existe um instrutor cadastrado para este usuário" na Edição

## Problema Identificado

O erro "Já existe um instrutor cadastrado para este usuário" estava aparecendo mesmo quando tentando editar um instrutor existente. Isso acontecia porque o sistema estava fazendo uma requisição POST (criar novo) em vez de PUT (editar existente).

## Causa do Problema

### Inconsistência no Valor do Campo `acao`

1. **HTML do Formulário**: O campo hidden `acaoInstrutor` tinha valor padrão `"novo"`
2. **Função `abrirModalInstrutor`**: Estava definindo o valor como `"criar"`
3. **Função `editarInstrutor`**: Estava definindo o valor como `"editar"`
4. **Função `salvarInstrutor`**: Verificava se `acao === 'editar'`

### Fluxo Incorreto:

```javascript
// ❌ Função abrirModalInstrutor (ANTES)
document.getElementById('acaoInstrutor').value = 'criar'; // Valor incorreto

// ✅ Função editarInstrutor
document.getElementById('acaoInstrutor').value = 'editar'; // Valor correto

// ✅ Função salvarInstrutor
const acao = formData.get('acao'); // Pega 'criar' em vez de 'editar'
if (acao === 'editar' && instrutor_id) { // Nunca entra aqui
    instrutorData.id = instrutor_id;
}
```

## Solução Implementada

### Correção na Função `abrirModalInstrutor`

**Antes:**
```javascript
document.getElementById('acaoInstrutor').value = 'criar';
```

**Depois:**
```javascript
document.getElementById('acaoInstrutor').value = 'novo';
```

### Fluxo Correto Agora:

1. **Novo Instrutor**: `acao = 'novo'` → POST
2. **Editar Instrutor**: `acao = 'editar'` → PUT

## Logs de Debug Adicionados

Adicionei logs detalhados para monitorar o comportamento:

```javascript
console.log('🔍 Debug - Campo acao:', acao);
console.log('🔍 Debug - Campo instrutor_id:', instrutor_id);
console.log('🔍 Debug - Tipo de acao:', typeof acao);
console.log('🔍 Debug - Tipo de instrutor_id:', typeof instrutor_id);

if (acao === 'editar' && instrutor_id) {
    instrutorData.id = instrutor_id;
    console.log('✅ Modo edição detectado - ID:', instrutor_id);
} else {
    console.log('⚠️ Modo criação detectado ou ID não encontrado');
}
```

## Logs Esperados Agora

### Para Edição:
```
💾 Salvando instrutor...
🔍 Debug - Campo acao: editar
🔍 Debug - Campo instrutor_id: 23
🔍 Debug - Tipo de acao: string
🔍 Debug - Tipo de instrutor_id: string
✅ Modo edição detectado - ID: 23
📋 Dados do instrutor para salvar: {
  id: "23",
  nome: "Usuário teste 001",
  // ... outros campos
}
📡 Método: PUT
```

### Para Criação:
```
💾 Salvando instrutor...
🔍 Debug - Campo acao: novo
🔍 Debug - Campo instrutor_id: 
🔍 Debug - Tipo de acao: string
🔍 Debug - Tipo de instrutor_id: string
⚠️ Modo criação detectado ou ID não encontrado
📋 Dados do instrutor para salvar: {
  nome: "Novo Instrutor",
  // ... outros campos (sem id)
}
📡 Método: POST
```

## Arquivos Modificados

- `admin/assets/js/instrutores.js` - Corrigido valor do campo `acaoInstrutor` e adicionados logs de debug
- `CORRECAO_ERRO_EDICAO_POST.md` - Documentação da correção

## Teste Recomendado

1. **Acesse a página de instrutores**
2. **Clique em "Editar"** no instrutor ID 23
3. **Verifique no console** se:
   - ✅ **Campo acao** mostra `"editar"`
   - ✅ **Campo instrutor_id** mostra `"23"`
   - ✅ **Modo edição detectado** aparece
4. **Clique em "Salvar Instrutor"**
5. **Verifique no console** se:
   - ✅ **Método: PUT** aparece
   - ✅ **Não há erro** de "já existe instrutor"
   - ✅ **Dados são salvos** corretamente

## Resultado Esperado

Agora quando você editar um instrutor:

- ✅ **Campo `acao`** será `"editar"`
- ✅ **Campo `instrutor_id`** terá o ID correto
- ✅ **Requisição será PUT** em vez de POST
- ✅ **API receberá o ID** do instrutor
- ✅ **Não haverá erro** de duplicação
- ✅ **Edição funcionará** corretamente
