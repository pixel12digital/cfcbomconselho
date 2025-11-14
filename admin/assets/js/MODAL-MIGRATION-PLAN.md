# Plano de Migração de Modais para Padrão Unificado

## Status Atual

✅ **Modal de Aluno (#modalAluno)** - REFERÊNCIA COMPLETA
- CSS organizado em `admin/assets/css/modal-form.css`
- Layout flexbox implementado corretamente
- Header fixo ✅
- Abas fixas ✅
- Corpo com scroll interno ✅
- Footer sempre visível ✅

## Modais a Migrar

### 1. Modal de Instrutor (#modalInstrutor)
- **Arquivo**: `admin/pages/instrutores.php`
- **Status**: Aguardando migração
- **Prioridade**: Alta
- **Observações**: Estrutura similar ao modal de aluno

### 2. Modal de Veículo (#modalVeiculo)
- **Arquivo**: `admin/pages/veiculos.php`
- **Status**: Aguardando migração
- **Prioridade**: Média
- **Observações**: Formulário mais simples, mas deve seguir o mesmo padrão

### 3. Modais de Financeiro
- **Arquivo**: `admin/pages/financeiro-faturas.php`
- **Status**: Aguardando migração
- **Prioridade**: Média
- **Observações**: Quando tiverem formulários longos

### 4. Modais de Exames
- **Arquivo**: `admin/pages/exames.php`
- **Status**: Aguardando migração
- **Prioridade**: Baixa
- **Observações**: Verificar se aplicável

## Padrão a Seguir

Todos os modais devem seguir a estrutura definida em `admin/assets/css/modal-form.css`:

```
.custom-modal (overlay)
  └─ .custom-modal-dialog (container centralizado)
      └─ .custom-modal-content (flex column)
          └─ form (flex column)
              ├─ .modal-header (fixo, flex: 0 0 auto)
              ├─ .modal-tabs (fixo, flex: 0 0 auto) [opcional]
              ├─ .modal-body (rolável, flex: 1 1 auto, overflow-y: auto)
              └─ .modal-footer (fixo, flex: 0 0 auto)
```

## Checklist de Migração

Para cada modal migrado:

- [ ] Remover CSS inline problemático
- [ ] Usar classes do `modal-form.css`
- [ ] Garantir estrutura flexbox correta
- [ ] Testar scroll interno
- [ ] Testar header e footer fixos
- [ ] Testar responsividade mobile
- [ ] Atualizar JS de abrir/fechar
- [ ] Remover `!important` desnecessários

## Notas Técnicas

- O scroll deve acontecer APENAS dentro de `.modal-body`
- O body da página deve ter `overflow: hidden` quando modal aberto
- Não usar `position: sticky` no footer se o container for flex
- Não usar `height: 100%` fixo no body, usar `flex: 1` e `min-height: 0`

