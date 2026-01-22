# Ajuste do Menu - Presença Teórica

**Data:** 2025-11-24  
**Objetivo:** Remover item temporário "Presenças Teóricas (Temporário)" do menu lateral ADMIN e alinhar o fluxo oficial.

## Contexto

O sistema de presença teórica possui dois caminhos possíveis:
1. **Fluxo oficial (correto):** Acadêmico → Turmas Teóricas → Detalhes da Turma → Seleção da Aula → Chamada/Frequência
2. **Fluxo temporário (removido):** Acadêmico → Presenças Teóricas (Temporário) → turma-chamada.php

O item temporário foi removido para evitar duplicidade de caminhos e garantir que todos os usuários sigam o fluxo oficial.

## Alterações Realizadas

### 1. Menu Desktop (`admin/index.php` - linha ~1556)
- **Removido:** Item `<a href="index.php?page=turma-chamada">` com texto "Presenças Teóricas (Temporário)"
- **Mantido:** Item "Turmas Teóricas" que leva ao fluxo oficial
- **Comentário adicionado:** Explicando o motivo da remoção e o fluxo oficial

### 2. Menu Mobile (`admin/index.php` - linha ~1885)
- **Removido:** Item `<a href="index.php?page=turma-chamada">` com texto "Presenças Teóricas (Temporário)"
- **Mantido:** Item "Turmas Teóricas" que leva ao fluxo oficial
- **Comentário adicionado:** Explicando o motivo da remoção e o fluxo oficial

### 3. Menu Flyout (`admin/assets/js/menu-flyout.js` - linha 27)
- **Removido:** Item `{ icon: 'fas fa-check-square', text: 'Presenças Teóricas (Temporário)', ... }`
- **Mantido:** Item "Turmas Teóricas" que leva ao fluxo oficial
- **Comentário adicionado:** Explicando o motivo da remoção e o fluxo oficial

## Fluxo Oficial Mantido

O acesso à chamada de presença teórica continua funcionando através do fluxo oficial:

1. **Acadêmico** → Menu lateral
2. **Turmas Teóricas** → Lista de turmas teóricas
3. **Detalhes da Turma** → Selecionar uma turma específica
4. **Seleção da Aula** → No calendário ou lista de aulas da turma
5. **Chamada/Frequência** → Acessar `turma-chamada.php` com `turma_id` e `aula_id`

## Arquivos Modificados

1. `admin/index.php` (2 locais: menu desktop e mobile)
2. `admin/assets/js/menu-flyout.js` (menu flyout)

## Validações

- ✅ Item "Presenças Teóricas (Temporário)" removido do menu desktop
- ✅ Item "Presenças Teóricas (Temporário)" removido do menu mobile
- ✅ Item "Presenças Teóricas (Temporário)" removido do menu flyout
- ✅ Item "Turmas Teóricas" mantido e funcional
- ✅ Fluxo via Turmas Teóricas → Detalhes → Chamada continua funcionando
- ✅ Página `turma-chamada.php` continua existindo e funcionando (apenas não acessível diretamente pelo menu)

## Notas Importantes

- A página `admin/pages/turma-chamada.php` **não foi removida** - ela continua existindo e funcionando normalmente
- O acesso direto via URL (`index.php?page=turma-chamada&turma_id=X&aula_id=Y`) ainda funciona
- O fluxo oficial garante que o usuário sempre tenha `turma_id` e `aula_id` válidos ao acessar a chamada
- Não há dependências CSS/JS específicas para o item removido que precisem ser limpas

## Próximos Passos (Opcional)

- [ ] Verificar se há outros links diretos para `turma-chamada.php` que deveriam seguir o fluxo oficial
- [ ] Considerar adicionar validação em `turma-chamada.php` para redirecionar para Turmas Teóricas se acessado sem contexto adequado

