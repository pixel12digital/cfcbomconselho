# UX Melhorada: Cadastro de Disciplinas e Cursos por Aulas

## 📋 Objetivo

Melhorar a experiência do usuário no cadastro de disciplinas e cursos teóricos, permitindo que o CFC trabalhe com o conceito natural de "quantidade de aulas" ao invés de digitar minutos totais diretamente.

## ✅ Implementação

### 1. Campos no Banco de Dados

**Migration 028:** Adiciona campos auxiliares para UX, mantendo `minutes` como valor canônico.

#### `theory_disciplines`
- ✅ `default_lessons_count` (INT NULL) - Quantidade padrão de aulas
- ✅ `default_lesson_minutes` (INT NULL) - Minutos por aula (padrão 50)
- ✅ `default_minutes` (mantido) - **Valor canônico calculado**

#### `theory_course_disciplines`
- ✅ `lessons_count` (INT NULL) - Quantidade de aulas para este curso
- ✅ `lesson_minutes` (INT NULL) - Minutos por aula para este curso
- ✅ `minutes` (mantido) - **Valor canônico calculado**

### 2. Regras de Negócio

**Validações:**
- `lessons_count > 0` (se informado)
- `lesson_minutes` entre 1 e 180 minutos
- Backend **sempre recalcula** `minutes = lessons_count × lesson_minutes`

**Compatibilidade:**
- Disciplinas podem ter `default_minutes` vazio (variável por curso)
- Registros antigos que só têm `minutes` são inferidos automaticamente no frontend para exibição

### 3. Formulário de Disciplinas

**Campos:**
- Quantidade de Aulas (number)
- Minutos por Aula (number, default 50)
- **Total calculado** (read-only, mostra minutos)

**Comportamento:**
- Cálculo automático no frontend (feedback visual)
- Backend recalcula e valida antes de salvar
- Se quantidade de aulas vazia → `default_minutes` pode ficar NULL

### 4. Formulário de Cursos (vínculo disciplinas)

**Campos por disciplina:**
- Disciplina (select)
- Quantidade de Aulas (number)
- Minutos por Aula (number, default 50)
- **Total calculado** (read-only)
- Obrigatória (checkbox)

**Comportamento:**
- Cálculo automático por disciplina
- Backend recalcula `minutes` antes de salvar
- Compatibilidade: se `lessons_count` não existe mas `minutes` existe, infere para exibição

## 🔒 Garantias

### Minutos como Valor Canônico

1. **Persistência:** Sempre salva `minutes` calculado no backend
2. **Agenda/Sessões:** Continuam usando `minutes` (não muda nada)
3. **Integrações:** Todas as queries/relatórios continuam funcionando
4. **Backend sempre recalcula:** Não confia no valor do frontend

### Não Quebra Nada Existente

1. **Registros antigos:** Funcionam normalmente
2. **Queries existentes:** Continuam usando `minutes`
3. **Agenda/Lessons:** Sem alterações
4. **API/Integrações:** Sem alterações

## 📝 Exemplos de Uso

### Cadastrar Disciplina
```
Nome: Legislação de Trânsito
Quantidade de Aulas: 3
Minutos por Aula: 50
→ Total calculado: 150 minutos (salvo em default_minutes)
```

### Vincular Disciplina ao Curso
```
Disciplina: Legislação de Trânsito
Quantidade de Aulas: 5 (override do padrão da disciplina)
Minutos por Aula: 50
→ Total calculado: 250 minutos (salvo em minutes)
```

## 🎯 Benefícios

1. ✅ UX natural: CFC pensa em "3 aulas" não "150 minutos"
2. ✅ Reduz erros: menos digitação manual de números grandes
3. ✅ Padrão flexível: hora-aula configurável (50 min padrão)
4. ✅ Backend seguro: sempre recalcula, não confia no frontend
5. ✅ Retrocompatível: funciona com registros antigos

## ⚠️ Notas Importantes

- **Nunca usar `lessons_count` ou `lesson_minutes` em queries de agenda/sessões**
- **Sempre usar `minutes` para cálculos de duração**
- **Campos de aulas são apenas para UX de cadastro**
- **Backend sempre recalcula `minutes` antes de persistir**
