# Correção do Erro "Cannot set properties of null"

## Problema Identificado

O erro `Cannot set properties of null (setting 'value')` estava ocorrendo porque o script `instrutores.js` estava tentando acessar elementos do DOM **antes** do modal estar completamente carregado e visível.

## Causa do Problema

### Antes da Correção:
```javascript
// O script tentava preencher o formulário ANTES de abrir o modal
document.getElementById('nome').value = instrutor.nome || '';
document.getElementById('email').value = instrutor.email || '';
// ... outros campos

// Depois abria o modal
abrirModalInstrutor();
```

### Problema:
- Os elementos do modal não existem no DOM até que o modal seja aberto
- `document.getElementById()` retorna `null` para elementos que não existem
- Tentar definir `.value` em `null` causa o erro

## Solução Implementada

### Depois da Correção:
```javascript
// 1. Configurar modal para edição
document.getElementById('modalTitle').textContent = 'Editar Instrutor';
document.getElementById('acaoInstrutor').value = 'editar';
document.getElementById('instrutor_id').value = id;

// 2. Abrir modal PRIMEIRO
abrirModalInstrutor();

// 3. Aguardar o modal estar visível ANTES de preencher
setTimeout(() => {
    try {
        // Verificar se os elementos existem antes de usar
        const nomeField = document.getElementById('nome');
        const emailField = document.getElementById('email');
        const telefoneField = document.getElementById('telefone');
        const credencialField = document.getElementById('credencial');
        const cfcField = document.getElementById('cfc_id');
        const ativoField = document.getElementById('ativo');
        
        // Preencher apenas se os elementos existirem
        if (nomeField) nomeField.value = instrutor.nome || '';
        if (emailField) emailField.value = instrutor.email || '';
        if (telefoneField) telefoneField.value = instrutor.telefone || '';
        if (credencialField) credencialField.value = instrutor.credencial || '';
        if (cfcField) cfcField.value = instrutor.cfc_id || '';
        if (ativoField) ativoField.value = instrutor.ativo ? '1' : '0';
        
        console.log('✅ Formulário preenchido com sucesso!');
    } catch (error) {
        console.error('❌ Erro ao preencher formulário:', error);
    }
}, 100);
```

## Por que Isso Resolve o Problema

### 1. **Ordem Correta de Operações**
- Modal é aberto primeiro
- Elementos do DOM são criados
- Script aguarda 100ms para garantir que tudo está carregado
- Então preenche os campos

### 2. **Verificação de Existência**
- Cada elemento é verificado com `if (elemento)` antes de usar
- Evita erros se algum elemento não existir
- Código mais robusto e defensivo

### 3. **Tratamento de Erros**
- `try/catch` captura qualquer erro restante
- Logs detalhados para debug
- Não quebra a aplicação se algo der errado

## Campos Removidos

Removido o campo `categoria_habilitacao` que não existe no modal atual:
```javascript
// REMOVIDO - campo não existe no modal
document.getElementById('categoria_habilitacao').value = instrutor.categoria_habilitacao || '';
```

## Resultado Esperado

Agora quando você clicar em "Editar" um instrutor:

1. ✅ **Modal abre** sem erros
2. ✅ **Campos são preenchidos** corretamente
3. ✅ **Console mostra** "Formulário preenchido com sucesso!"
4. ✅ **Não há mais erros** de elementos null

## Logs Esperados

```
✏️ Editando instrutor ID: 23
📡 Fazendo requisição para: http://localhost:8080/cfc-bom-conselho/admin/api/instrutores.php?id=23
✅ Modal aberto com sucesso!
✅ Formulário preenchido com sucesso!
```

## Arquivos Modificados

- `admin/assets/js/instrutores.js` - Corrigida ordem de operações e adicionadas verificações
- `CORRECAO_ELEMENTOS_NULL.md` - Documentação da correção

## Teste Recomendado

1. **Acesse a página de instrutores**
2. **Clique em "Editar"** no instrutor ID 23
3. **Verifique se**:
   - ✅ Modal abre sem erros
   - ✅ Campos são preenchidos
   - ✅ Console não mostra erros
   - ✅ Funcionalidade completa funciona
